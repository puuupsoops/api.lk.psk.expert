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
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/managers/Settings.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/local/modules/psk.api/lib/DirectoryTable.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/managers/Partner.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/external/OrderEx.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/registers/OrderStatus.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/order/Order.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/managers/Order.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/Environment.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/service/Postman.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/managers/Manager.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/repositories/EmailMessageRepository.php';

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
     * @var \API\v1\Repositories\NotificationMessageRepository
     */
    protected $NotificationMessageRepository;

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

        $this->NotificationMessageRepository = $this->container->get(\API\v1\Repositories\NotificationMessageRepository::class);
    }

    /**
     * Установить стоимость отгрузки
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function SetShipmentCost(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        /** SHIPMENT_COST
         * Модель на вход. JSON
         *  {
         *      "id": "155", // идентификатор записи общего заказа в битрикс
         *      "guid": "f3680bc6-880c-11ec-8cde-005056bb3b36", // идентификатор конкретного заказа  из 1С
         *      "cost": 900 // стоимость отгрузки заказа
         *  }
         */
        try {
            $this->Monolog->info('Старт функционала Установить стоимость отгрузки. Запрос из 1С. ' . __FUNCTION__);

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

                // заказ
                $order = json_decode($row['POSITIONS'],true);

                //записываем новую стоимость отгрузки
                $order['delivery']['cost'] = (float)$dataRequest['cost'];

                $this->Monolog->debug('Новые данные для записи',[$order]);
                //region

                // добавляем новые данные в таблицу без обновления основного статуса, т.к. массив фильтра содержит статусы отличающиеся от последнего
                \Psk\Api\Orders\DirectoryTable::update($dataRequest['id'],[
                    'POSITIONS' => json_encode($order),
                    'SHIPMENT_COST' => (string)$dataRequest['cost']
                ]);
                //endregion

            }else {throw new \Exception('Не найден заказ.',406);}

            $this->Monolog->info('Завершение. ' . __FUNCTION__);
            $this->Monolog->close();
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

        //region СОЗДАНИЕ УВЕДОМЛЕНИЯ ДЛЯ ЛК

        try{
            $resultRecord = $this->NotificationMessageRepository->add(
                (int)$row['USER'],
                'Заказ: №' . $dataRequest['id'] . '. Стоимость отгрузки заказа изменена на: ' . number_format((float)$dataRequest['cost'],2) . ' ₽'
            );
            $this->Monolog->debug('Создано записей для уведомления ЛК', ['count' => $resultRecord]);

        }catch (\PDOException $e) {
            $this->Monolog->error('Ошибка при создании уведомления для ЛК.',['msg' => $e->getMessage()]);
        }

        //endregion

        // 202 HTTP при все ОК
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(202);
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

            /** @var int $userId Идентификатор пользователя */
            $userId = 0;
            /** @var string $partnerId Идентификатор контрагента */
            $partnerId = '';

            if($row = $DBResult->Fetch()){
                $this->Monolog->debug('Полученные данные из \Psk\Api\Orders\DirectoryTable' ,[$row]);
                //$status = \API\v1\Models\Registers\OrderStatus::Get((int)$dataRequest['status'])['label'];
                $status = (int)$dataRequest['status'];

                $userId = (int)$row['USER'];
                $partnerId = $row['PARTNER_GUID'];

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

                //region СОЗДАНИЕ УВЕДОМЛЕНИЯ ДЛЯ ЛК

                try{
                    $rsUser = \CUser::GetByID((int)$row['USER']);
                    /** @var array $arUser Данные пользователя */
                    $arUser = $rsUser->Fetch();

                    //region Добавляем пользователя к рассылке, если установлены настройки

                    /** @var \API\v1\Managers\Settings $SettingsManager Получение настроект пользователя*/
                    $SettingsManager = new \API\v1\Managers\Settings();

                    /** @var \API\v1\Models\NotificationSettings $NotificationSettings Настройки уведомлений */
                    $NotificationSettings = $SettingsManager->GetOrderNotificationSettingsByUserConfigId($arUser['UF_PARTNERS_LIST']);

                    if($NotificationSettings->order_lk_states) {
                        $resultRecord = $this->NotificationMessageRepository->add(
                            (int)$row['USER'],
                            'Заказ: №' . $dataRequest['id'] . '. Статус обновлен. Новый статус: ' . \API\v1\Models\Registers\OrderStatus::Get($status)['title']
                        );
                        $this->Monolog->debug('Создано записей для уведомления ЛК', ['count' => $resultRecord]);
                    }

                }catch (\PDOException $e) {
                    $this->Monolog->error('Ошибка при создании уведомления для ЛК.',['msg' => $e->getMessage()]);
                }

                //endregion

                //region Отправляем почтовое сообщение

                    try {
                        $EmailMessageRepository = new \API\v1\Repositories\EmailMessageRepository((int)$row['USER']);

                        /** @var array $arEmailAddress  адреса для отправки */
                        $arEmailAddress = [];

                        if($NotificationSettings->order_email_states) {
                            $arEmailAddress[] = $arUser['EMAIL'];
                        }

                        //endregion

                        //region Вычисляем и добавляем менеджера
                        if($userId && $partnerId) {
                            $Partner = new \API\v1\Managers\Partner();

                            global $USER;
                            $rsUser = $USER->GetByID($userId);
                            $userLink = $rsUser->Fetch()['UF_PARTNERS_LIST']; //id массива с конфигурацией пользователя

                            $sizes =  \CIBlockElement::GetProperty(
                                \Environment::GetInstance()['iblocks']['Users'],
                                $userLink,
                                [],
                                ['CODE' => 'PARTNERS']
                            );

                            while($size = $sizes->GetNext()){

                                # получаем ID связанных записей контрагентов
                                $partner = $Partner->GetByBitrixID($size['VALUE']);
                                if($partner->GetUID() === $partnerId)
                                    break;
                            }

                            if($partner){
                                $managerEmail = \API\v1\Managers\Manager::GetInstance()::GetByXmlId($partner->GetManagerUID())
                                    ->GetEmail();
                            }

                            if($managerEmail) {
                                $arEmailAddress[] = $managerEmail;
                            }
                        }
                        //endregion
                        $this->Monolog->debug('Список email адресов для рассылки',$arEmailAddress);

                        $EmailMessageRepository->Add(
                            (string)$dataRequest['id'],
                            'Заказ: №' . $dataRequest['id'] . '. Статус обновлен. Новый статус: ' . \API\v1\Models\Registers\OrderStatus::Get($status)['title'],
                            $arEmailAddress
                        );
                    }catch (\PHPMailer\PHPMailer\Exception $e){
                        $this->Monolog->error('Ошибка отправки почтового сообщения',[
                            'message'   => $e->getMessage(),
                            'code'      => $e->getCode()
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

    /**
     * Редактировать заказ из 1С (правки из 1С)
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function EditOrderReserveByIdFrom1C(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {

        /**
         * Input Model
         * @see https://galactic-capsule-829510.postman.co/workspace/lk.psk.expert~ea187e48-c2ee-4e00-a330-a7bc6e4c445c/request/15374835-09c82615-f14d-4373-a34a-5cc837d7a5b5
         */

        try{
            $this->Monolog->info('Старт функционала Редактировать заказ из 1С. Запрос из 1С. ' . __FUNCTION__);

            \Bitrix\Main\Loader::includeModule('iblock');

            /*
             * входящие данные строкой
             * {
             *   "id": "189" // id общего заказа
             * }
             */
            $contents = $request->getBody()->getContents();

            $this->Monolog->debug('Получены данные из 1С, string:' ,['data' => $contents]);

            //обрабатываем данные есть символ из 1С в начале строки U+feff, обрезаем байт
            $contents = mb_substr($contents,1,mb_strlen($contents));

            $this->Monolog->debug('Строка без первого символа, string:' ,[$contents]);

            /** @var array $dataRequest  Входные данные */
            $dataRequest = json_decode($contents,true);

            $this->Monolog->debug('Декод данных из 1С, array:' ,['data' => $dataRequest]);

            if(!$contents) {
                $this->Monolog->error('Пустые данные.');
                throw new \Exception('Пустые данные.',406);
            }

            /** @var int $rootOrderId Идентификатор главного заказа. */
            $rootOrderId = (int) $dataRequest['id'];

            $DBResult = \Psk\Api\Orders\DirectoryTable::getList([
                'select'  => ['*'], // имена полей, которые необходимо получить в результате
                'filter'  => ['INDEX' => $rootOrderId], // описание фильтра для WHERE и HAVING
                //'group'   => ... // явное указание полей, по которым нужно группировать результат
                'order'   => ['INDEX' => 'DESC'] // параметры сортировки
                //'limit'   => ... // количество записей
                //'offset'  => ... // смещение для limit
                //'runtime' => ... // динамически определенные поля
            ]);

            $result = $DBResult->Fetch();

            $order = json_decode($result['POSITIONS'],true);

            //var_dump($order);
            //die();

            /** @var \API\v1\Models\Order\Order $Order Модель заказа из личного кабинета */
            $Order = new \API\v1\Models\Order\Order($order);
            $OrderManager = new \API\v1\Managers\Order($Order);

            /** @var array{id:int,guid:string,status:int,organization_id:string,n:string}[] $registers Записи заказа из 1С в Таблице главного заказа в Битрикс  */
            $registers = json_decode($result['ID'],true);

            //var_dump($registers);
            //die();

            //region ищем совпадение по guid заказу и если найдено, перезаписываем заказы
            /** @var array $value Данные заказа из 1С */
            foreach ($dataRequest['orders'] as $value) {

                /** @var array $sub_value Данные заказа 1С из таблицы главного заказа в Битрикс */
                foreach ($registers as $sub_value){

                    if($value['guid'] === $sub_value['guid']){
                        //записываем новые данные по идентификатору элемента битрикс
                        $element_id = $sub_value['id'];

                        $list = [];

                        $arProductList = [];

                        // формируем позиции
                        foreach ($value['products'] as $key => $position){
                            $arProductList[$key]['guid'] = $position['productid'];
                            $arProductList[$key]['characteristics'][] = [
                                    'guid' => $position['characteristicsid'],
                                    'orgguid' => $value['organizationid'],
                                    'quantity' => $position['quantity'],
                                    'discount' => (float) $position['discount'],
                                    'fullprice' => (float) $position['total'] / $position['quantity'], // полная цена без скидки
                                    'price' => (float) $position['price']
                            ];
                        }
                        //var_dump($value);
                        //var_dump($sub_value);
                        //var_dump($arProductList);
                        //die();

                        // совмещаем идентичные товары с характеристиками
                        while((bool)count($arProductList)){
                            $tmp = $arProductList[0];

                            //var_dump($tmp);

                            unset($arProductList[0]);
                            foreach ($arProductList as $key => $position){
                                if($tmp['guid'] === $position['guid']){

                                    //var_dump($position['characteristics']);
                                    //var_dump($tmp['characteristics']);
                                    $tmp['characteristics'][] = current($position['characteristics']);

                                    //var_dump($tmp['characteristics']);

                                    unset($arProductList[$key]);
                                }
                            }
                            $list[] = $tmp;
                            if(count($arProductList)){
                                $arProductList = array_values($arProductList);
                            }else{
                                break;
                            }
                        }
                        //var_dump($list);
                        //die();

                        $data = [];
                        foreach ($list as $value){
                            $data[] = serialize($value);
                        }
                        //var_dump($data);
                        //die();
                        \CIBlockElement::SetPropertyValuesEx(
                            $element_id,//int ELEMENT_ID,
                            false,//int IBLOCK_ID,
                            [
                                'POSITIONS' => $data
                            ]//array PROPERTY_VALUES,
                            //array FLAGS = array()
                        );

                    }

                }
            }
            //endregion

            //region извлекаем данные
            $arLinked = unserialize($result['LINKED']);

            $arPosition = [];
            $arPositionPresale = [];
            $ids = [];
            $arNav = [];
            // Меняем ключи и id местами в массиве.
            foreach ($arLinked as $key => $value){
                $arNav[$value] = $key;
            }

            // вспомогательный массив для выборки ID
            foreach ($arLinked as $key => $value){
                $ids[] = (int)$value;
            }

            //фильтруем массив, проверяем есть ли связанные документы из 1С по id
            //var_dump(json_decode($result['ID'],true));
            $stack = json_decode($result['ID'],true);
            //var_dump($ids);
            $ids = array_filter($ids, function ($value) use ($stack){
                foreach ($stack as $item){
                    if($item['id'] === $value)
                        return true;
                }
                return false;
            });
            //var_dump($ids);
            //die();
            $CIBlockElement = \CIBlockElement::GetList(
                [],
                ['ID' => $ids], false,false,['*']);

            while ( $element = $CIBlockElement->GetNextElement() ) {
                $fields = $element->GetFields();
                $props = $element->GetProperties();
                //var_dump($fields);
                //var_dump($props);
                if($arNav[$fields['ID']] === 'positions' || $arNav[$fields['ID']] === 'shoes') {

                    if(count($props['POSITIONS']['VALUE']) === 1){
                        $arPosition[] = current($props['POSITIONS']['VALUE']);
                    }else{
                        foreach ($props['POSITIONS']['VALUE'] as $value){
                            $arPosition[] = $value;
                        }
                    }

                }else{

                    if(count($props['POSITIONS']['VALUE']) === 1){
                        $arPositionPresale[] = current($props['POSITIONS']['VALUE']);
                    }else{
                        foreach ($props['POSITIONS']['VALUE'] as $value){
                            $arPositionPresale[] = $value;
                        }
                    }

                }
                //var_dump($fields['ID']);
                //var_dump($props['POSITIONS']['VALUE']);
            }

            //endregion

            //region Модифицируем старые данные в заказе

            $order['position'] = $arPosition;
            $order['position_presail'] = $arPositionPresale;

            //var_dump($arPosition);
            //var_dump($arPositionPresale);
            //die();

            //endregion

            //region Записываем новые данные
            $arNewData = [
                'POSITIONS' => json_encode($order),
            ];

            //Костыль, ищем Услугу по доставке с новой ценой.
            $shipmentCost = 0;
            foreach ($dataRequest['orders'] as $order) {
                if(!empty($order['services'])) {
                    $shipmentCost = $order['services'][0]['price'];
                }
            }
            if($shipmentCost) {
                $arNewData['SHIPMENT_COST'] = (string)$shipmentCost;
            }

            $this->Monolog->debug('Новая модель данных для записи в таблицу заказа. POSITIONS', $order);
            \Psk\Api\Orders\DirectoryTable::update($rootOrderId,$arNewData);

            //endregion

            //var_dump($registers);
            //var_dump($dataRequest);

            //var_dump($result);
            //var_dump($order);
            //var_dump($OrderManager);
            //die();

            $this->Monolog->info('Завершение. ' . __FUNCTION__);
            $this->Monolog->close();

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


        //region Получение настроект пользователя для конфигурации уведомлений
        try{
            /** @var \API\v1\Managers\Settings $SettingsManager Получение настроект пользователя */
            $SettingsManager = new \API\v1\Managers\Settings();

            $rsUser = \CUser::GetByID($result['USER']);
            /** @var array $arUser Данные пользователя */
            $arUser = $rsUser->Fetch();

            //region Добавляем пользователя к рассылке, если установлены настройки

            /** @var \API\v1\Models\NotificationSettings $NotificationSettings Настройки уведомлений */
            $NotificationSettings = $SettingsManager->GetOrderNotificationSettingsByUserConfigId($arUser['UF_PARTNERS_LIST']);

            //region СОЗДАНИЕ УВЕДОМЛЕНИЯ ДЛЯ ЛК
            if($NotificationSettings->order_lk_changed) {
                $resultRecord = $this->NotificationMessageRepository->add(
                    (int)$result['USER'],
                    'Заказ №' . $rootOrderId . ' отредактирован менеджером.'
                );
                $this->Monolog->debug('Создано записей для уведомления ЛК', ['count' => $resultRecord]);
            }
            //endregion

        }catch (\PDOException $e) {
            $this->Monolog->error('Ошибка при создании уведомления для ЛК.',['msg' => $e->getMessage()]);
        }
        catch (\Exception $e){
            $this->Monolog->error('Ошибка при получении настроект пользователя для конфигурации уведомлений.',
                ['msg' => $e->getMessage()]);
        }

        //endregion

        //region Формируем почтовое сообщение
        //
        $EmailMessageRepository = new \API\v1\Repositories\EmailMessageRepository((int)$result['USER']);

        try {
            /** @var array $arEmailAddress  адреса для отправки */
            $arEmailAddress = [];

            $userId = $result['USER'];
            $partnerId = $Order->GetPartnerId();

            if($NotificationSettings->order_email_changed) {
                $arEmailAddress[] = $arUser['EMAIL'];
            }

            //endregion

            //region Вычисляем и добавляем менеджера
            if($userId && $partnerId) {
                $Partner = new \API\v1\Managers\Partner();

                global $USER;
                $rsUser = $USER->GetByID($userId);
                $userLink = $rsUser->Fetch()['UF_PARTNERS_LIST']; // id массива с конфигурацией пользователя

                $sizes =  \CIBlockElement::GetProperty(
                    \Environment::GetInstance()['iblocks']['Users'],
                    $userLink,
                    [],
                    ['CODE' => 'PARTNERS']
                );

                while($size = $sizes->GetNext()){

                    # получаем ID связанных записей контрагентов
                    $partner = $Partner->GetByBitrixID($size['VALUE']);
                    if($partner->GetUID() === $partnerId)
                        break;
                }

                if($partner){
                    $managerEmail = \API\v1\Managers\Manager::GetInstance()::GetByXmlId($partner->GetManagerUID())
                        ->GetEmail();
                }

                if($managerEmail) {
                    $arEmailAddress[] = $managerEmail;
                }
            }
            //endregion
            $this->Monolog->debug('Список email адресов для рассылки',$arEmailAddress);

            $EmailMessageRepository->Add(
                (string)$rootOrderId,
                'Заказ: №' . (string)$rootOrderId . ' успешно отредактирован менеджером в 1С.',
                $arEmailAddress);

        }catch (\Exception $e){
            $this->Monolog->error('Ошибка формирования почтового сообщения',[
                'message'   => $e->getMessage(),
                'code'      => $e->getCode()
            ]);
        }

        //endregion

        // 202 HTTP при все ОК
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(202);
    }

    /**
     * [deprecated - Закрываем через общий статус]
     * Закрыть заказ по истечению резерва по его общему ID
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     *
     * @deprecated
     */
    public function CloseReservedOrderById(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        try{
            $this->Monolog->info('Старт функционала Закрыть заказ по истечению резерва по его общему ID. Запрос из 1С. ' . __FUNCTION__);

            /*
             * входящие данные строкой
             * {
             *   "id": "189" // id общего заказа
             * }
             */
            $contents = $request->getBody()->getContents();

            $this->Monolog->debug('Получены данные из 1С, string:' ,[$contents]);

            $dataRequest = json_decode($contents,true);

            if(!$contents) {
                throw new \Exception('Пустые данные: id заказа',406);
            }

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

                //var_dump($row);

                //todo: status

                // устанавливает флаг редактирования на 0
                \Psk\Api\Orders\DirectoryTable::update($dataRequest['id'],[
                    'EDITABLE' => 0
                ]);

            }else{ throw new \Exception('Не найден заказ.',406); }

            $this->Monolog->info('Завершение. ' . __FUNCTION__);
            $this->Monolog->close();

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