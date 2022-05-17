<?php
namespace API\v1\Controllers;
include_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

include_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/local/modules/psk.api/lib/DirectoryTable.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/managers/Partner.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/external/OrderEx.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/registers/OrderStatus.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/Environment.php';

use Firebase\JWT\JWT;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Monolog\Logger;
use Monolog\ErrorHandler;
use Monolog\Handler\StreamHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use Bitrix\Main\Web\HttpClient;

class OrderController
{
    // идентификаторы фабрик
    protected $SPEC_ODA_ID      = 'b5e91d86-a58a-11e5-96ed-0025907c0298';
    protected $WORK_SHOES_ID    = 'f59a4d06-2f35-11e7-8fdb-0025907c0298'; // это обувь

    protected $Client;
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
     * Возвратит статусы печатных форм по id заказа
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function GetDocumentsStatusById(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        try{
            $Response1C = $this->Client->get('http://91.193.222.117:12380/stimul_test_maa/hs/ex/order/statusprint',[
                'auth' => ['OData', '11'],
                'json' => [
                'Orders' => [
                    [
                        'id' => $args['id']
                    ]
                ]
            ]]);

            $response->getBody()->write(str_replace(['"StatusSF": true,'],'"StatusSF": false,',$Response1C->getBody()->getContents()));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);

        }catch (\GuzzleHttp\Exception\GuzzleException $e){
            $response->getBody()->write(json_encode([
                'code' => $e->getCode(),
                'message' => $e->getTraceAsString()
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($e->getCode());
        }
    }

    /**
     * Возвратит страницу с печатной формой по id заказа
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function GetDocumentById(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {

        // идентификатор заказа
        $id = $request->getQueryParams()['id'];

        // имя формы
        $form_name = $request->getQueryParams()['name'];

        // флаг для скачивания
        $is_download = (bool)$request->getQueryParams()['download'];

        // данные для отправки
        $DataString = json_encode([
            'Orders' => [
                [
                    'id' => $id,
                    'PrintingForm' => $form_name
                ]
            ]
        ]);

        try{
            $Response1C = $this->Client->getAsync('http://91.193.222.117:12380/stimul_test_maa/hs/ex/order/printing',['auth' => ['OData', '11'],'json' => $DataString]);
            $Response1C = $Response1C->wait();

            $bodyContents = $Response1C->getBody()->getContents();
            $bodyContents = json_decode(mb_substr(trim($bodyContents), 2, -1),true);

//            $response->getBody()->write(json_encode([
//                'code' => $Response1C->getStatusCode(),
//                'bodyContents' => $bodyContents
//            ]));
            //var_dump((string)strlen(current(current($bodyContents['response'])['PrintingForms'])['PrintForm']));
            $data = base64_decode(current(current($bodyContents['response'])['PrintingForms'])['PrintForm']);

            $response->getBody()->write($data);



            if($is_download){
                return $response
                    ->withHeader('Content-Length',(string)strlen($data))
                    ->withHeader('Content-Disposition','attachment; filename="'. $form_name . '_' . $id .'.pdf"')
                    ->withHeader('Content-Type', 'application/x-force-download;')
                    ->withStatus(200);
            }

            return $response
                ->withHeader('Content-Type', 'application/pdf')
                ->withStatus(200);

        }catch (\GuzzleHttp\Exception\GuzzleException $e){
            $response->getBody()->write(json_encode([
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($e->getCode());
        }

    }

    /**
     * Добавить заказ
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function Add(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface{

        $header = $request->getHeader('Authorization');
        if (preg_match('/Bearer\s+(.*)$/i', $header[0] ?? '', $matches)) {
            $token = $matches[1] ?? '';
        }
        $arAlgs    = ['HS256', 'HS512', 'HS384'];
        $tokenData = (array)JWT::decode($token ?? '', \Environment::JWT_PRIVATE_KEY, $arAlgs);

        /**
         * @var array Данные о заказе
         */
        $requestData = json_decode($request->getBody()->getContents(),true);

        $this->Monolog->debug('Authorization',[ $request->getHeader('Authorization')]);
        $this->Monolog->debug('TokenData',[ $tokenData ]);
        $this->Monolog->debug('RequestBody', ['bodyContents' => $request->getBody()->getContents()]);
        $this->Monolog->debug('RequestData',[$requestData]);

        /**
         * @var
         */
        //$userId = $request->getAttribute('tokenAuthData')['id']; // Идентификатор пользователя в битрикс
        $userId = $tokenData['id'];

        /**
         * @var string XML Идентификатор контрагента
         */
        $partnerGUID = $requestData['partner_id'];

        $Partner = new \API\v1\Managers\Partner();
        $Partner = $Partner->GetByGUID($partnerGUID);
        $partner = $Partner->AsArray();

        //region Работа с позициями заказа
        /**
         * @var array Позиции с обувью
         */
        $arShoesPositions = [];

        /**
         * @var array (предзаказа) Позиции с обувью
         */
        $arShoesPositionsPre = [];

        /**
         * @var array Позиции
         */
        $arPositions = [];

        /**
         * @var array (предзаказ) Позиции
         */
        $arPositionsPre = [];

        foreach ($requestData['position'] as $position){

            // распределяем товар обувь отдельно, остальное отдельно
            if(current($position['characteristics'])['orgguid'] === $this->WORK_SHOES_ID){
                $arShoesPositions[] = $position;
            }else{
                $arPositions[] = $position;
            }
        }

        foreach ($requestData['position_presail'] as $position){
            // распределяем предзаказанные товары обувь отдельно, остальное отдельно
            if(current($position['characteristics'])['orgguid'] === $this->WORK_SHOES_ID){
                $arShoesPositionsPre[] = $position;
            }else{
                $arPositionsPre[] = $position;
            }
        }

        // логер
        $this->Monolog->debug('PositionStack',[
            '$arPositions' => $arPositions,
            '$arPositionsPre' => $arPositionsPre,
            '$arShoesPositions' => $arShoesPositions,
            '$arShoesPositionsPre' => $arShoesPositionsPre
        ]);
        //endregion

        /**
         * @var string Номер общего заказа
         */
        $orderRootID = (string)$requestData['id'] . (string)$userId;

        /**
         * @var array Массив связанных заказов
         *
         * - positions --
         * - positions_pre --
         * - shoes -- обувь
         * - shoes_pre -- предзаказ обуви
         */
        $arLinkerOrder = [];


        /**
         * @var \CIBlockElement $el
         */
        $el = new \CIBlockElement;

        if($arPositions){
            //region Добавляем позиции в битрикс: Заказы

            $list = [];

            foreach ($arPositions as $pos){
                $list[] = serialize($pos);
            }

            $arProps = [
                'FACTORY'   => ['VALUE' => 2], //enum: 1 фабрика рабочей обуви, 2 эксперт спецодежда
                'POSITIONS' => $list //string
            ];

            $arLoadProductArray = [
                'MODIFIED_BY'       => 1,
                'IBLOCK_SECTION_ID' => false,
                'IBLOCK_ID'         => 48,
                'PROPERTY_VALUES'   => $arProps,
                'NAME'              => $orderRootID .'#'.$userId, //имя заказа P_*
                'ACTIVE'            => 'Y',            // активен
            ];

            $arLinkerOrder['positions'] = $el->Add($arLoadProductArray);
            //endregion
        }

        if($arPositionsPre){
            //region Добавляем позиции для предзаказа в битрикс: Предзаказы
            $list = [];

            foreach ($arPositionsPre as $pos){
                $list[] = serialize($pos);
            }

            $arProps = [
                'FACTORY'   => ['VALUE' => 4], //enum: 3 фабрика рабочей обуви, 4 эксперт спецодежда
                'POSITIONS' => $list //string
            ];

            $arLoadProductArray = [
                'MODIFIED_BY'       => 1,
                'IBLOCK_SECTION_ID' => false,
                'IBLOCK_ID'         => 49,
                'PROPERTY_VALUES'   => $arProps,
                'NAME'              => $orderRootID .'#'.$userId, //имя заказа P_*
                'ACTIVE'            => 'Y',            // активен
            ];

            $arLinkerOrder['positions_pre'] = $el->Add($arLoadProductArray);
            //endregion
        }

        if($arShoesPositions){
            //region Добавляем обувь в битрикс: Заказы
            $list = [];

            foreach ($arShoesPositions as $pos){
                $list[] = serialize($pos);
            }
            $arProps = [
                'FACTORY'   => ['VALUE' => 1], //enum: 1 фабрика рабочей обуви, 2 эксперт спецодежда
                'POSITIONS' => $list //string
            ];

            $arLoadProductArray = [
                'MODIFIED_BY'       => 1,
                'IBLOCK_SECTION_ID' => false,
                'IBLOCK_ID'         => 48,
                'PROPERTY_VALUES'   => $arProps,
                'NAME'              => $orderRootID .'#'.$userId, //имя заказа P_*
                'ACTIVE'            => 'Y',            // активен
            ];

            $arLinkerOrder['shoes'] = $el->Add($arLoadProductArray);
            //endregion
        }

        if($arShoesPositionsPre){
            //region Добавляем обувь для предзаказа в битрикс: Предзаказы
            $list = [];

            foreach ($arShoesPositionsPre as $pos){
                $list[] = serialize($pos);
            }
            $arProps = [
                'FACTORY'   => ['VALUE' => 3], //enum: 3 фабрика рабочей обуви, 4 эксперт спецодежда
                'POSITIONS' => $list //string
            ];

            $arLoadProductArray = [
                'MODIFIED_BY'       => 1,
                'IBLOCK_SECTION_ID' => false,
                'IBLOCK_ID'         => 49,
                'PROPERTY_VALUES'   => $arProps,
                'NAME'              => $orderRootID .'#'.$userId, //имя заказа P_*
                'ACTIVE'            => 'Y',            // активен
            ];

            $arLinkerOrder['shoes_pre'] = $el->Add($arLoadProductArray);
            //endregion
        }

        try{
            \Psk\Api\Orders\DirectoryTable::add([
                'ID' => (int)($orderRootID), // Номер общего заказа \Bitrix\Main\Entity\IntegerField
                'DATE' => new \Bitrix\Main\Type\DateTime(date('d.m.Y H:m:s')), // Дата заказа    \Bitrix\Main\Entity\DatetimeField(
                'PARTNER_GUID' => $partnerGUID, //Контрагент GUID \Bitrix\Main\Entity\StringField
                'PARTNER_NAME' => $partner['name'],// Контрагент Имя  \Bitrix\Main\Entity\StringField
                'STATUS' => '0', // Статус заказа    \Bitrix\Main\Entity\StringField
                'USER' => (int)$userId, // Идентификатор учетной записи пользователя в Битрикс \Bitrix\Main\Entity\IntegerField
                'LINKED' => serialize($arLinkerOrder), // Связанные заказы (массив ID записей в битрикс, сериализованное поле) \Bitrix\Main\Entity\StringField
                'COST' => (string)$requestData['total'], // Общая сумма заказа без скидки  \Bitrix\Main\Entity\StringField
                'DISCOUNT' => '0', // Скидка числом  \Bitrix\Main\Entity\StringField
            ]);

            $response->getBody()->write(json_encode([
                'response' => [
                    'id' => (int) $orderRootID,
                    'message' => 'Заказ: №' . $orderRootID . ' зарегистрирован.'
                ],
                'error' => []
            ]));

            $this->Monolog->close();

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);

        }catch (\Exception $e){
            var_dump($e);

            $response->getBody()->write(json_encode([
                'response' => [],
                'error' => [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage()
                ]
            ]));

            $this->Monolog->close();

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

    }

    /**
     * Получить список заказов
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function GetList(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface{
        $header = $request->getHeader('Authorization');
        if (preg_match('/Bearer\s+(.*)$/i', $header[0] ?? '', $matches)) {
            $token = $matches[1] ?? '';
        }
        $arAlgs    = ['HS256', 'HS512', 'HS384'];
        $tokenData = (array)JWT::decode($token ?? '', \Environment::JWT_PRIVATE_KEY, $arAlgs);

        /**
         * @var int Идентификатор пользователя в Битрикс
         */
        $userId = $tokenData['id'];

        /**
         * @var array Массив с заказами
         */
        $ordersStack = [];

        $DBResult = \Psk\Api\Orders\DirectoryTable::getList([
            'select'  => ['*'], // имена полей, которые необходимо получить в результате
            'filter'  => ['USER' => $userId], // описание фильтра для WHERE и HAVING
            //'group'   => ... // явное указание полей, по которым нужно группировать результат
            'order'   => ['INDEX' => 'DESC'] // параметры сортировки
            //'limit'   => ... // количество записей
            //'offset'  => ... // смещение для limit
            //'runtime' => ... // динамически определенные поля
        ]);

        while ($item = $DBResult->Fetch()){
            $orderPosition = new \API\v1\Models\OrderEx();
            $orderPosition->id = ''; // deprecated - оставлено чтобы не сломать логику в ЛК
            $orderPosition->n = $item['INDEX']; // номер
            $orderPosition->name = 'Заказ № ' . $item['INDEX'] . ' от ' . $item['DATE']->format('d.m.Y');
            $orderPosition->date = $item['DATE']->format('d.m.Y H:i:s');
            $orderPosition->partner_guid = $item['PARTNER_GUID'];
            $orderPosition->partner_name = $item['PARTNER_NAME'];
            $orderPosition->status = \API\v1\Models\Registers\OrderStatus::Get((int)$item['STATUS'])['title'];
            $orderPosition->status_code = (int)$item['STATUS'];
            $orderPosition->user_id = $item['USER'];

            // временный массив заглушка для счетов и всего остального
            $orderPosition->checks = json_decode($item['ID'],true); // теперь модель сформированных счетов;
            //var_dump($item);

            $ordersStack[] = $orderPosition->AsArray();
        }

        $response->getBody()->write(json_encode($ordersStack));

        return $response
                ->withHeader('Content-Type','application/json')
                ->withStatus(200);
    }

    /**
     * Формирует позиции
     *
     * @param array $position
     * @return array
     */
    private function createPositions(array $position): array {

        /**
         * @var array Массив с товарами
         */
        $arPosition = [];

        $Response1C = $this->Client->get('http://91.193.222.117:12380/stimul_test_maa/hs/ex/product/' . $position['guid'],[
            'auth' => ['OData', '11']
        ]);
        $result = mb_substr(trim($Response1C->getBody()->getContents()), 2, -1);

        /**
         * @var array Массив с актуальными данными характеристик (предложения в Битрикс) из базы 1С
         */
        $offers = json_decode($result,true);
        $product = current($offers['response']);

        $arPosition['guid'] = $position['guid'];
        $arPosition['orgguid'] = $product['organization_guid'];

        foreach ($position['characteristics'] as $element){

            foreach ($product['characteristics'] as $characteristic){
                if($element['guid'] === $characteristic['guid']){
                    $arPosition['characteristics'][] = [
                        'guid' => $element['guid'],
                        'quantity' => $element['quantity'],
                        'price' => $characteristic['price'],
                        'cost' => (float)$element['quantity'] * (float)$characteristic['price']
                    ];
                    break;
                }
            }
        }


        return $arPosition;
    }

    /**
     * Получить заказ по общему идентификатору
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     *
     * @return ResponseInterface
     */
    public function GetById(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface{
        try{
            /** @var array{id: int, config: int, sign: int} $tokenData Данные из токена
             *  id - идентификатор пользователя
             *  config - идентификатор элемента инфоблока конфигурации пользователя
             *  sign - кодовая подпись
             */
            $tokenData = $request->getAttribute('tokenData');

            $userId = $tokenData['id'];
            $orderId = $args['id'];

            $DBResult = \Psk\Api\Orders\DirectoryTable::getList([
                'select'  => ['*'], // имена полей, которые необходимо получить в результате
                'filter'  => ['USER' => $userId, 'INDEX' => $orderId], // описание фильтра для WHERE и HAVING
                //'group'   => ... // явное указание полей, по которым нужно группировать результат
                'order'   => ['INDEX' => 'DESC'] // параметры сортировки
                //'limit'   => ... // количество записей
                //'offset'  => ... // смещение для limit
                //'runtime' => ... // динамически определенные поля
            ]);

            $result = $DBResult->Fetch();

            if(!$result)
                throw new \Exception('Заказ не найден.',404);

            $ResponseData = ($result['POSITIONS']) ? json_decode($result['POSITIONS'],true) : [];

            unset($ResponseData['id']);

            $Client = new \GuzzleHttp\Client();

            foreach ($ResponseData['position'] as &$position){

                $Response = $Client->get('https://psk.expert/test/product-page/ajax.php',[
                    'query' => [
                        'QUERY'     => $position['guid'],
                        'OPTION'    => 8 // поиск по XML_ID
                    ],
                    'verify' => false
                ]);
                /** @var array Массив с данными о запрашиваемом товаре */
                $arProduct = json_decode($Response->getBody()->getContents(),true);

                $position['name'] = $arProduct['PRODUCT']['NAME'];
                $position['id'] = $arProduct['PRODUCT']['ID'];
                $position['article'] = $arProduct['PRODUCT']['ARTICLE'];
                $position['images'] = $arProduct['IMAGES'];

                foreach ($position['characteristics'] as &$characteristic){
                    foreach ($arProduct['OFFERS'] as $offer){
                        if($characteristic['guid'] === $offer['GUID']){
                            $characteristic['id'] = $offer['ID'];
                            $characteristic['title'] = $offer['CHARACTERISTIC'];
                            $characteristic['price'] = $offer['PRICE'];
                            break;
                        }
                    }
                }

            }

            foreach ($ResponseData['position_presail'] as &$position_presail){

                $Response = $Client->get('https://psk.expert/test/product-page/ajax.php',[
                    'query' => [
                        'QUERY'     => $position_presail['guid'],
                        'OPTION'    => 8 // поиск по XML_ID
                    ],
                    'verify' => false
                ]);
                /** @var array Массив с данными о запрашиваемом товаре */
                $arProduct = json_decode($Response->getBody()->getContents(),true);

                $position_presail['name'] = $arProduct['PRODUCT']['NAME'];
                $position_presail['id'] = $arProduct['PRODUCT']['ID'];
                $position_presail['article'] = $arProduct['PRODUCT']['ARTICLE'];
                $position_presail['images'] = $arProduct['IMAGES'];

                foreach ($position_presail['characteristics'] as &$characteristic){
                    foreach ($arProduct['OFFERS'] as $offer){
                        if($characteristic['guid'] === $offer['GUID']){
                            $characteristic['id'] = $offer['ID'];
                            $characteristic['title'] = $offer['CHARACTERISTIC'];
                            $characteristic['price'] = $offer['PRICE'];
                            break;
                        }
                    }
                }
            }

            $ResponseData['partner_name'] = $result['PARTNER_NAME'];

            $response->getBody()->write(json_encode([
                'response' => $ResponseData,
                'error' => []
            ]));

        }catch (\Exception $e){
            $response->getBody()->write(json_encode([
                'response' => [],
                'error' => [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                ]
            ]));

            return $response
                ->withHeader('Content-Type','application/json')
                ->withStatus($e->getCode());
        }

        return $response
            ->withHeader('Content-Type','application/json')
            ->withStatus(200);
    }

    /**
     * Создать позицию заказа в битрикс
     *
     * @param array $position   Данные с позициями заказа
     * @return int              Идентификатор элемента инфоблока в битрикс
     */
    private function createOrderElement(array $position): int{
        return 0;
    }
}