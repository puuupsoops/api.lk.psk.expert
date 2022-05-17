<?php

namespace API\v1\Controllers;

use GuzzleHttp\Client;
use Monolog\ErrorHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

include_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

include_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/local/modules/psk.api/lib/DirectoryTable.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/managers/Partner.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/external/OrderEx.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/registers/OrderStatus.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/Environment.php';

/** class GateController Back-door для приема обращений из 1С */
class GateController
{
    /**
     * @var ContainerInterface Container Interface
     */
    protected $container;

    /**
     * @var Logger
     */
    protected $Monolog;

    /**
     * constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $this->Client = new Client();

        //region Logger
        $this->Monolog = new Logger(mb_strtolower(basename(__FILE__,'.php')));

        $logFile  = $_SERVER['DOCUMENT_ROOT'] . '/logs/api/' . str_replace('\\', '/', __CLASS__) . '/' . date(
                'Y/m/d'
            ) . '/' . mb_strtolower(basename(__FILE__, '.php')) . '.' . date('H') . '.log';

        $this->Monolog->pushProcessor(new \Monolog\Processor\IntrospectionProcessor(Logger::INFO));
        $this->Monolog->pushProcessor(new \Monolog\Processor\MemoryUsageProcessor());
        $this->Monolog->pushProcessor(new \Monolog\Processor\MemoryPeakUsageProcessor());
        $this->Monolog->pushProcessor(new \Monolog\Processor\ProcessIdProcessor());
        $this->Monolog->pushHandler(new StreamHandler($logFile, Logger::DEBUG));

        $handler = new ErrorHandler($this->Monolog);
        $handler->registerErrorHandler([], false);
        $handler->registerExceptionHandler();
        $handler->registerFatalHandler();
        //endregion
    }

    /**
     * Установить статус заказа по его идентификатору
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function SetStatusByGUID(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        /**
         * Модель на вход. JSON
         *  {
         *      "id": "155", // идентификатор записи общего заказа в битрикс
         *      "guid": "f3680bc6-880c-11ec-8cde-005056bb3b36", // идентификатор конкретного заказа  из 1С
         *      "status": "created" // новый статус
         *  }
         */
        try{

            $this->Monolog->info('Старт функционала смены статуса заказа. Запрос из 1С. ' . __FUNCTION__);

            $contents = $request->getBody()->getContents();

            $this->Monolog->debug('Получены данные из 1С, string:' ,[$contents]);

            //обрабатываем данные есть символ из 1С в начале строки U+feff, обрезаем байт
            $contents = mb_substr($contents,1,mb_strlen($contents));

            $this->Monolog->debug('Строка без первого символа, string:' ,[$contents]);

            $dataRequest = json_decode($contents,true);

            $this->Monolog->debug('Из json в array:' ,[$dataRequest]);

            $DBResult = \Psk\Api\Orders\DirectoryTable::getList([
                'select'  => ['*'], // имена полей, которые необходимо получить в результате
                'filter'  => ['INDEX' => $dataRequest['id']], // описание фильтра для WHERE и HAVING
                //'group'   => ... // явное указание полей, по которым нужно группировать результат
                //'order'   => ['INDEX' => 'DESC'] // параметры сортировки
                //'limit'   => ... // количество записей
                //'offset'  => ... // смещение для limit
                //'runtime' => ... // динамически определенные поля
            ]);

            if($row = $DBResult->Fetch()){
                $this->Monolog->debug('Полученные данные из \Psk\Api\Orders\DirectoryTable' ,[$row]);
                //$status = \API\v1\Models\Registers\OrderStatus::Get((int)$dataRequest['status'])['label'];
                $status = (int)$dataRequest['status'];
                // список подзаказов
                $orders = json_decode($row['ID'],true);
                foreach ($orders as &$order){
                    if($order['guid'] === $dataRequest['guid']){
                        $this->Monolog->debug('Найден элемент',['guid' =>$order['guid'] , 'requestGuid' => $dataRequest['guid'], 'data' => $order]);
                        $order['status'] = $status;
                        break;
                    }
                }
                $this->Monolog->debug('Новые данные для записи',[$orders]);
                //region Если все подзаказы имеют одинаковый статус, апаем основной статус.

                $arResult = array_filter($orders,function($value) use ($status){
                    if((int)$value['status'] !== $status)
                        return true;
                    return false;
                });

                $this->Monolog->debug('Проверка на изменение общего статуса заказа:',[$arResult]);

                if(!empty($arResult)){
                    // добавляем новые данные в таблицу без обновления основного статуса, т.к. массив фильтра содержит статусы отличающиеся от последнего
                    \Psk\Api\Orders\DirectoryTable::update($dataRequest['id'],[
                        'ID' => json_encode($orders)
                    ]);
                }else{
                    // если массив $arResult пуст, апаем основной статус, до последнего актуального
                    \Psk\Api\Orders\DirectoryTable::update($dataRequest['id'],[
                        'ID' => json_encode($orders),
                        'STATUS' => (string) $status// сейчас статус цифрой, в БД должен записаться как string | \API\v1\Models\Registers\OrderStatus::GetByMnemonicCode($status)
                    ]);
                }
                //endregion
            }else {throw new \Exception('Не найден заказ.',406);}

            $this->Monolog->info('Завершение. ' . __FUNCTION__);
            $this->Monolog->close();

            //var_dump($request->getBody()->getContents());
            $response->getBody()->write(json_encode(true));

        }catch (\Exception $e){
            // 406 HTTP при ошибке
            $this->Monolog->info('Поймано исключение в ' . __FUNCTION__);
            $this->Monolog->error($e->getMessage(),['code' => $e->getCode()]);
            $this->Monolog->close();

            $response->getBody()->write(json_encode(false));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(406);
        }

        // 202 HTTP при все ОК
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(202);
    }
}