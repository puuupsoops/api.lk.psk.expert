<?php
namespace API\v1\Controllers;
include_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

include_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/managers/Settings.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/local/modules/psk.api/lib/DirectoryTable.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/managers/Partner.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/external/OrderEx.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/registers/OrderStatus.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/Token.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/order/Order.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/managers/Order.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/external/Product1C.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/external/Order1CAdd.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/external/Order1CEdit.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/service/Postman.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/managers/Manager.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/managers/Shipment.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/registers/Services1C.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/order/delivery/ShipmentStatus.php';

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
use function DI\value;

class OrderController
{
    /** @var string[] Список Идентификаторов складов, для игнорирования  */
    private array $arIDsStorageIgnore = [
        'edcb8a4f-5fc8-11e7-8fdb-0025907c0298', // Магазин главный склад
        '0e0dff55-b6fb-11eb-baa1-005056bb1249', // Обувь (Шеризон) ФРО
        'cde3f7d7-bd61-11eb-baa3-005056bb1249', // Обувь (Шеризон) ЭС
        'f9f1e2b9-036a-11e9-814c-005056bf1558', // Шоурум_Эксперт
        'ca55a20e-ddb1-11de-9c79-0050569a3a91', // Бухгалтерия
        '088b2fb0-495a-11e8-80f4-000c2938f7da', // №1 Спецодежда (Лобня)
        '9d701705-2123-11e8-80df-000c2938f7da', // Гладиолус главный склад
        '4cc31c3b-e56c-11ec-bad0-005056bb1249', // Экспериментальный цех Изделия
        'd474ce74-4eec-11e4-8704-0025907c0298'  // Логотипы в производстве
    ];

    /** @var int Индикатор остатков для заказа со склада №3 Обувь (Дубровки).  */
    protected array $RESIDUE_SHOES = []; // для №3 Обувь (Дубровки)      f61480c8-fc57-11e3-8704-0025907c0298

    // идентификаторы фабрик
    /** @var string  СО - XML идентификатор фабрики спец одежды */
    protected $SPEC_ODA_ID      = 'b5e91d86-a58a-11e5-96ed-0025907c0298';
    /** @var string  ФРО - XML идентификатор фабрики рабочей обуви*/
    protected $WORK_SHOES_ID    = 'f59a4d06-2f35-11e7-8fdb-0025907c0298';

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
            $this->Monolog->notice('Старт функционала выдачи статуса печатных форм' . __FUNCTION__);
            //флаг перенаправления на тестовую 1С
            $redirect1CTestDB = !\Configuration::GetInstance()::IsProduction();

            $this->Monolog->debug('$redirect1CTestDB',[$redirect1CTestDB]);

            // запрос позиции товара
            if($redirect1CTestDB){
                $Response1C = $this->Client->get('http://91.193.222.117:12380/stimul_test_maa/hs/ex/order/statusprint',[
                    //'auth' => ['OData', '11'],
                    'json' => [
                        'Orders' => [
                            [
                                'id' => $args['id']
                            ]
                        ]
                    ]]);
            }else{
                $Response1C = $this->Client->get('http://10.68.5.205/StimulBitrix/hs/ex/order/statusprint',[
                    'auth' => ['OData', '11'],
                    'json' => [
                        'Orders' => [
                            [
                                'id' => $args['id']
                            ]
                        ]
                    ]]);
            }
            $contents = $Response1C->getBody()->getContents();
            $statuses = str_replace(['"StatusSF": true,'],'"StatusSF": false,',$contents);

            $this->Monolog->debug('$contents',[$contents]);
            $this->Monolog->debug('$statuses',[$statuses]);

            $response->getBody()->write($statuses);

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);

        }catch (\GuzzleHttp\Exception\GuzzleException $e){
            $response->getBody()->write(json_encode([
                'code' => $e->getCode(),
                'message' => $e->getTraceAsString()
            ]));
            $this->Monolog->notice('Завершение функционала выдачи статуса печатных форм' . __FUNCTION__);
            $this->Monolog->close();
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
        //флаг перенаправления на тестовую 1С
        $redirect1CTestDB = !\Configuration::GetInstance()::IsProduction();

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
            if($redirect1CTestDB){
                $Response1C = $this->Client->getAsync('http://91.193.222.117:12380/stimul_test_maa/hs/ex/order/printing',
                    [
                        //'auth' => ['OData', '11'],
                        'json' => $DataString
                    ]
                );
                $Response1C = $Response1C->wait();
            }else{
                $Response1C = $this->Client->getAsync('http://10.68.5.205/StimulBitrix/hs/ex/order/printing',
                    [
                        'auth' => ['OData', '11'],
                        'json' => $DataString
                    ]
                );
                $Response1C = $Response1C->wait();
            }

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

        }catch (\Exception $e){
            //catch (\GuzzleHttp\Exception\GuzzleException $e){
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
     * Редактирование резерва (поддержка резерва)
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     *
     */
    public function EditReserve(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface{
        $this->Monolog->notice('Старт Редактирование резерва ' . __FUNCTION__);

        //флаг перенаправления на тестовую 1С
        $redirect1CTestDB = !\Configuration::GetInstance()::IsProduction();

        try{
            //var_dump($args['id']);

            $DBResult = \Psk\Api\Orders\DirectoryTable::getList([
                'select'  => ['*'], // имена полей, которые необходимо получить в результате
                'filter'  => ['INDEX' => $args['id']], // описание фильтра для WHERE и HAVING
                //'group'   => ... // явное указание полей, по которым нужно группировать результат
                'order'   => ['INDEX' => 'DESC'] // параметры сортировки
                //'limit'   => ... // количество записей
                //'offset'  => ... // смещение для limit
                //'runtime' => ... // динамически определенные поля
            ])->Fetch();

            if(!$DBResult)
                throw new \Exception('Заказ № '. $args['id'] .' не найден.',404);

            /**
             * @var array $arLinkerOrder Идентификаторы элементов основного заказа.
             *
             * [ нужно проверять ключи на существование, т.к. разный состав заказа!
                "positions": int,
                "positions_pre": int,
                "shoes": int,
                "shoes_pre": int
                ]
             */
            $arLinkerOrder = unserialize($DBResult['LINKED']) ?? [];
            $arLinkerOrderOld = unserialize($DBResult['LINKED']) ?? [];
            //var_dump($arLinkerOrder);

            /**
             * @var \API\v1\Models\Token Модель данных из токена авторизации
             */
            $Token = new \API\v1\Models\Token($request->getAttribute('tokenData'));

            /**
             * @var array Массив с данными о заказе
             */
            $contents = $request->getBody()->getContents();
            $requestData = json_decode($contents,true);
            $requestData['id'] = $args['id'];

            $this->Monolog->debug('Вшение данные string: ', ['data' => $contents]);
            $this->Monolog->debug('Вшение данные', ['data' => $requestData]);
            //var_dump( (new \API\v1\Models\Order\Order($requestData))->AsArray() );

            /** @var \API\v1\Models\Order\Order $Order Модель заказа из личного кабинета */
            $Order = new \API\v1\Models\Order\Order($requestData);

            $Partner = new \API\v1\Managers\Partner();
            $Partner = $Partner->GetByGUID($Order->GetPartnerId());
            $partner = $Partner->AsArray();

            $OrderManager = new \API\v1\Managers\Order($Order);


            //перезаписываем, т.к. возможно добавлены были новые типы-позиций при редактировании, но до этого они не существовали.
            // требуется для создания отдельных документов в 1С.
            $arLinkerOrder = $OrderManager->SetLinks($arLinkerOrder)->Update($Token->GetId());

            $TableUpdateResult = \Psk\Api\Orders\DirectoryTable::update($Order->GetId(),[
                'LINKED' => serialize($arLinkerOrder), // Связанные заказы (массив ID записей в битрикс, сериализованное поле) \Bitrix\Main\Entity\StringField
                'POSITIONS' => json_encode($requestData),
                'EDITABLE' => 1
            ]);
            //var_dump($arLinkerOrder);

            //region Собираем массив с существующими guid-1С заказами
            $arLinkerOrder1CGUID = [];
            $tmp = json_decode($DBResult['ID'],true);
            //var_dump($tmp);
            foreach ($tmp as $value){
                $arLinkerOrder1CGUID[$value['id']] = $value['guid'];
            }

            //var_dump($arLinkerOrder1CGUID);
            //die();
            //endregion

            //die();
            //var_dump($TableUpdateResult);

        }catch (\Exception $e){
            $response->getBody()->write(json_encode([
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($e->getCode());
        }

        //region Формируем модель для 1С

        try{
            /** @var array $arOrders1CEdit Массив с заказами на редактирование, для 1С базы */
            $arOrders1CEdit = [];

            /** @var array $arOrders1CCreate Массив с заказами на создание, для 1С базы */
            $arOrders1CCreate = [];

            $rsUser = \CUser::GetByID($Token->GetId());
            /** @var array $arUser Данные пользователя */
            $arUser = $rsUser->Fetch();

            $request_certificate = (bool)$requestData['request_certificate']; // Добавить сертификат true | false
            $comment = sprintf('%s%s%s',
                $arUser['NAME'],
                ($Order->IsRequestedCertificate()) ? '/Cерт.' : '',
                ($requestData['comment']) ? '/' . $requestData['comment'] : ''); // Комментарий
            $delivery_terms = \API\v1\Models\Order\Delivery\ShipmentStatus::Get(
                \API\v1\Models\Order\Delivery\ShipmentStatus::GetByMnemonicCode($requestData['delivery']['case'])
            )['title']; // Название отгрузки

            //region Проверка на отсутствие пустых позиций, под удаление.
            // проверяем отсутствуют ли позиции в заказе, которые присутствую в базе данных Битрикс,
            // т.е. позиции под удаление.

            if(!$OrderManager->arPositions
                && array_key_exists('positions',$arLinkerOrderOld)
                && $arLinkerOrder1CGUID[$arLinkerOrderOld['positions']] ) {
                // если позиция отсутствует на входящем заказе, но присутствует запись в битрикс и документ из 1С
                // то создаем и добавляем модель с пустыми заказами.
                $key = 'positions';

                $orderPositions1C =  new \API\v1\Models\Order1CEdit($arLinkerOrder1CGUID[$arLinkerOrderOld[$key]]);

                $orderPositions1C->reserved = true;
                $orderPositions1C->id = $arLinkerOrderOld[$key];

                $orderPositions1C->request_certificate = $request_certificate;
                $orderPositions1C->comment = $comment;
                $orderPositions1C->delivery_terms = $delivery_terms;

                foreach ($tmp as $value){
                    if($value['id'] === $orderPositions1C->id) {
                        $orderPositions1C->organizationid = $value['organization_id'] ?? $this->SPEC_ODA_ID;
                    }
                }

                $arOrders1CEdit[] = $orderPositions1C->AsArray();

                if($arLinkerOrder[$key]){

                    if ( \Bitrix\Main\Loader::includeModule('iblock') ){
                        \CIBlockElement::Delete($arLinkerOrder[$key]);
                    }

                    unset($arLinkerOrder[$key]);

                    $TableUpdateResult = \Psk\Api\Orders\DirectoryTable::update($Order->GetId(),[
                        'LINKED' => serialize($arLinkerOrder), // Связанные заказы (массив ID записей в битрикс, сериализованное поле) \Bitrix\Main\Entity\StringField
                        'EDITABLE' => 1
                    ]);
                }

            }

            if(!$OrderManager->arShoesPositions
                && array_key_exists('shoes',$arLinkerOrderOld)
                && $arLinkerOrder1CGUID[$arLinkerOrderOld['shoes']] ) {
                // если позиция отсутствует на входящем заказе, но присутствует запись в битрикс и документ из 1С
                // то создаем и добавляем модель с пустыми заказами.
                $key = 'shoes';

                $orderPositions1C =  new \API\v1\Models\Order1CEdit($arLinkerOrder1CGUID[$arLinkerOrderOld[$key]]);

                $orderPositions1C->reserved = true;
                $orderPositions1C->id = $arLinkerOrderOld[$key];

                $orderPositions1C->request_certificate = $request_certificate;
                $orderPositions1C->comment = $comment;
                $orderPositions1C->delivery_terms = $delivery_terms;

                foreach ($tmp as $value){
                    if($value['id'] === $orderPositions1C->id) {
                        $orderPositions1C->organizationid = $value['organization_id'] ?? $this->WORK_SHOES_ID;
                    }
                }

                $arOrders1CEdit[] = $orderPositions1C->AsArray();

                if($arLinkerOrder[$key]){

                    if ( \Bitrix\Main\Loader::includeModule('iblock') ){
                        \CIBlockElement::Delete($arLinkerOrder[$key]);
                    }

                    unset($arLinkerOrder[$key]);

                    $TableUpdateResult = \Psk\Api\Orders\DirectoryTable::update($Order->GetId(),[
                        'LINKED' => serialize($arLinkerOrder), // Связанные заказы (массив ID записей в битрикс, сериализованное поле) \Bitrix\Main\Entity\StringField
                        'EDITABLE' => 1
                    ]);
                }
            }

            if(!$OrderManager->arPositionsPre
                && array_key_exists('positions_pre',$arLinkerOrderOld)
                && $arLinkerOrder1CGUID[$arLinkerOrderOld['positions_pre']] ) {
                // если позиция отсутствует на входящем заказе, но присутствует запись в битрикс и документ из 1С
                // то создаем и добавляем модель с пустыми заказами.
                $key = 'positions_pre';
                //var_dump($tmp);
                //var_dump($arLinkerOrderOld);
                //var_dump($arLinkerOrder);
                //var_dump($arLinkerOrder1CGUID);

                $orderPositions1C =  new \API\v1\Models\Order1CEdit($arLinkerOrder1CGUID[$arLinkerOrderOld[$key]]);

                $orderPositions1C->reserved = false;
                $orderPositions1C->id = $arLinkerOrderOld[$key];

                $orderPositions1C->request_certificate = $request_certificate;
                $orderPositions1C->comment = $comment;
                $orderPositions1C->delivery_terms = $delivery_terms;

                foreach ($tmp as $value){
                    if($value['id'] === $orderPositions1C->id) {
                        $orderPositions1C->organizationid = $value['organization_id'] ?? $this->SPEC_ODA_ID;
                    }
                }

                $arOrders1CEdit[] = $orderPositions1C->AsArray();
                //var_dump($arOrders1CEdit);
                //die();

                if($arLinkerOrder[$key]){

                    if ( \Bitrix\Main\Loader::includeModule('iblock') ){
                        \CIBlockElement::Delete($arLinkerOrder[$key]);
                    }

                    unset($arLinkerOrder[$key]);

                    $TableUpdateResult = \Psk\Api\Orders\DirectoryTable::update($Order->GetId(),[
                        'LINKED' => serialize($arLinkerOrder), // Связанные заказы (массив ID записей в битрикс, сериализованное поле) \Bitrix\Main\Entity\StringField
                        'EDITABLE' => 1
                    ]);
                }
            }

            if(!$OrderManager->arShoesPositionsPre
                && array_key_exists('shoes_pre',$arLinkerOrderOld)
                && $arLinkerOrder1CGUID[$arLinkerOrderOld['shoes_pre']] ) {
                // если позиция отсутствует на входящем заказе, но присутствует запись в битрикс и документ из 1С
                // то создаем и добавляем модель с пустыми заказами.
                $key = 'shoes_pre';

                $orderPositions1C =  new \API\v1\Models\Order1CEdit($arLinkerOrder1CGUID[$arLinkerOrderOld[$key]]);

                $orderPositions1C->reserved = false;
                $orderPositions1C->id = $arLinkerOrderOld[$key];

                $orderPositions1C->request_certificate = $request_certificate;
                $orderPositions1C->comment = $comment;
                $orderPositions1C->delivery_terms = $delivery_terms;

                foreach ($tmp as $value){
                    if($value['id'] === $orderPositions1C->id) {
                        $orderPositions1C->organizationid = $value['organization_id'] ?? $this->WORK_SHOES_ID;
                    }
                }

                $arOrders1CEdit[] = $orderPositions1C->AsArray();

                if($arLinkerOrder[$key]){

                    if ( \Bitrix\Main\Loader::includeModule('iblock') ){
                        \CIBlockElement::Delete($arLinkerOrder[$key]);
                    }

                    unset($arLinkerOrder[$key]);

                    $TableUpdateResult = \Psk\Api\Orders\DirectoryTable::update($Order->GetId(),[
                        'LINKED' => serialize($arLinkerOrder), // Связанные заказы (массив ID записей в битрикс, сериализованное поле) \Bitrix\Main\Entity\StringField
                        'EDITABLE' => 1
                    ]);
                }
            }

            //endregion


            if($OrderManager->arPositions && array_key_exists('positions',$arLinkerOrder)){
                $key = 'positions';

                //проверям существует ли заказ в 1С
                if($arLinkerOrder1CGUID[$arLinkerOrder[$key]]){
                    // модель для обновления
                    $orderPositions1C =  new \API\v1\Models\Order1CEdit($arLinkerOrder1CGUID[$arLinkerOrder[$key]]);

                    $orderPositions1C->reserved = true;
                    $orderPositions1C->id = $arLinkerOrder[$key];
                    $orderPositions1C->organizationid = $OrderManager->arPositions[0]->characteristics[0]->orgguid;

                    $orderPositions1C->request_certificate = $request_certificate;
                    $orderPositions1C->comment = $comment;
                    $orderPositions1C->delivery_terms = $delivery_terms;

                    foreach ($OrderManager->arPositions as $value){
                        //запрашиваем данные из 1С (нужно для склада)

                        //region запрос позиции товара

//                        if($redirect1CTestDB){
//                            $Response = $this->Client->get('http://91.193.222.117:12380/stimul_test_maa/hs/ex/product/' . $value->guid,[
//                                //'auth' => ['OData', '11']
//                            ]);
//                        }else{
//                            $Response = $this->Client->get('http://10.68.5.205/StimulBitrix/hs/ex/product/' . $value->guid,[
//                                'auth' => ['OData', '11']
//                            ]);
//                        }
//                        $result = mb_substr(trim($Response->getBody()->getContents()), 2, -1);
//                        $extendPosition = current(json_decode($result,true)['response']);

                        /** @var array $extendPosition Модель позиции товара из 1С */
                        $extendPosition = $this->GetProductPositionFrom1CByXmlId($value->guid);
                        //echo json_encode($extendPosition);
                        //echo json_encode($value);
                        //endregion

                        $orderPositions1C->AddProduct($value,$extendPosition);
                    }

                    $arOrders1CEdit[] = $orderPositions1C->AsArray();

                }else{
                    // модель для создания
                    $orderPositions1C =  new \API\v1\Models\Order1CAdd();

                    $orderPositions1C->reserved = true;
                    $orderPositions1C->id = $arLinkerOrder[$key];
                    $orderPositions1C->organizationid = $OrderManager->arPositions[0]->characteristics[0]->orgguid;

                    $orderPositions1C->request_certificate = $request_certificate;
                    $orderPositions1C->comment = $comment;
                    $orderPositions1C->delivery_terms = $delivery_terms;

                    foreach ($OrderManager->arPositions as $value){
                        //запрашиваем данные из 1С (нужно для склада)

                        //region запрос позиции товара

//                        if($redirect1CTestDB){
//                            $Response = $this->Client->get('http://91.193.222.117:12380/stimul_test_maa/hs/ex/product/' . $value->guid,[
//                                //'auth' => ['OData', '11']
//                            ]);
//                        }else{
//                            $Response = $this->Client->get('http://10.68.5.205/StimulBitrix/hs/ex/product/' . $value->guid,[
//                                'auth' => ['OData', '11']
//                            ]);
//                        }
//                        $result = mb_substr(trim($Response->getBody()->getContents()), 2, -1);
//                        $extendPosition = current(json_decode($result,true)['response']);

                        /** @var array $extendPosition Модель позиции товара из 1С */
                        $extendPosition = $this->GetProductPositionFrom1CByXmlId($value->guid);

                        //endregion

                        $orderPositions1C->AddProduct($value,$extendPosition);
                    }

                    $arOrders1CCreate[] = $orderPositions1C->AsArray();
                }

            }

            if($OrderManager->arShoesPositions && array_key_exists('shoes',$arLinkerOrder)){
                $key = 'shoes';

                //проверям существует ли заказ в 1С
                if($arLinkerOrder1CGUID[$arLinkerOrder[$key]]){
                    // модель для обновления
                    $orderPositions1C =  new \API\v1\Models\Order1CEdit($arLinkerOrder1CGUID[$arLinkerOrder[$key]]);

                    $orderPositions1C->reserved = true;
                    $orderPositions1C->id = $arLinkerOrder[$key];
                    $orderPositions1C->organizationid = $OrderManager->arShoesPositions[0]->characteristics[0]->orgguid;

                    $orderPositions1C->request_certificate = $request_certificate;
                    $orderPositions1C->comment = $comment;
                    $orderPositions1C->delivery_terms = $delivery_terms;

                    foreach ($OrderManager->arShoesPositions as $value){
                        //запрашиваем данные из 1С (нужно для склада)

                        //region запрос позиции товара

//                        if($redirect1CTestDB){
//                            $Response = $this->Client->get('http://91.193.222.117:12380/stimul_test_maa/hs/ex/product/' . $value->guid,[
//                                //'auth' => ['OData', '11']
//                            ]);
//                        }else{
//                            $Response = $this->Client->get('http://10.68.5.205/StimulBitrix/hs/ex/product/' . $value->guid,[
//                                'auth' => ['OData', '11']
//                            ]);
//                        }
//                        $result = mb_substr(trim($Response->getBody()->getContents()), 2, -1);
//                        $extendPosition = current(json_decode($result,true)['response']);

                        /** @var array $extendPosition Модель позиции товара из 1С */
                        $extendPosition = $this->GetProductPositionFrom1CByXmlId($value->guid);

                        //endregion

                        $orderPositions1C->AddProduct($value,$extendPosition);
                    }

                    $arOrders1CEdit[] = $orderPositions1C->AsArray();

                }else{
                    // модель для создания
                    $orderPositions1C =  new \API\v1\Models\Order1CAdd();

                    $orderPositions1C->reserved = true;
                    $orderPositions1C->id = $arLinkerOrder[$key];
                    $orderPositions1C->organizationid = $OrderManager->arShoesPositions[0]->characteristics[0]->orgguid;

                    $orderPositions1C->request_certificate = $request_certificate;
                    $orderPositions1C->comment = $comment;
                    $orderPositions1C->delivery_terms = $delivery_terms;

                    foreach ($OrderManager->arShoesPositions as $value){
                        //запрашиваем данные из 1С (нужно для склада)

                        //region запрос позиции товара

//                        if($redirect1CTestDB){
//                            $Response = $this->Client->get('http://91.193.222.117:12380/stimul_test_maa/hs/ex/product/' . $value->guid,[
//                                //'auth' => ['OData', '11']
//                            ]);
//                        }else{
//                            $Response = $this->Client->get('http://10.68.5.205/StimulBitrix/hs/ex/product/' . $value->guid,[
//                                'auth' => ['OData', '11']
//                            ]);
//                        }
//                        $result = mb_substr(trim($Response->getBody()->getContents()), 2, -1);
//                        $extendPosition = current(json_decode($result,true)['response']);

                        /** @var array $extendPosition Модель позиции товара из 1С */
                        $extendPosition = $this->GetProductPositionFrom1CByXmlId($value->guid);

                        //endregion

                        $orderPositions1C->AddProduct($value,$extendPosition);
                    }

                    $arOrders1CCreate[] = $orderPositions1C->AsArray();
                }
            }

            if($OrderManager->arPositionsPre && array_key_exists('positions_pre',$arLinkerOrder)){
                $key = 'positions_pre';

                //проверям существует ли заказ в 1С
                if($arLinkerOrder1CGUID[$arLinkerOrder[$key]]){
                    // модель для обновления
                    $orderPositions1C =  new \API\v1\Models\Order1CEdit($arLinkerOrder1CGUID[$arLinkerOrder[$key]]);

                    $orderPositions1C->reserved = false;
                    $orderPositions1C->id = $arLinkerOrder[$key];
                    $orderPositions1C->organizationid = $OrderManager->arPositionsPre[0]->characteristics[0]->orgguid;

                    $orderPositions1C->request_certificate = $request_certificate;
                    $orderPositions1C->comment = $comment;
                    $orderPositions1C->delivery_terms = $delivery_terms;

                    foreach ($OrderManager->arPositionsPre as $value){
                        //запрашиваем данные из 1С (нужно для склада)

                        //region запрос позиции товара

//                        if($redirect1CTestDB){
//                            $Response = $this->Client->get('http://91.193.222.117:12380/stimul_test_maa/hs/ex/product/' . $value->guid,[
//                                //'auth' => ['OData', '11']
//                            ]);
//                        }else{
//                            $Response = $this->Client->get('http://10.68.5.205/StimulBitrix/hs/ex/product/' . $value->guid,[
//                                'auth' => ['OData', '11']
//                            ]);
//                        }
//                        $result = mb_substr(trim($Response->getBody()->getContents()), 2, -1);
//                        $extendPosition = current(json_decode($result,true)['response']);

                        /** @var array $extendPosition Модель позиции товара из 1С */
                        $extendPosition = $this->GetProductPositionFrom1CByXmlId($value->guid);

                        //endregion
                        //var_dump($extendPosition);
                        $orderPositions1C->AddProduct($value,$extendPosition);
                    }

                    $arOrders1CEdit[] = $orderPositions1C->AsArray();

                }else{
                    // модель для создания
                    $orderPositions1C =  new \API\v1\Models\Order1CAdd();

                    $orderPositions1C->reserved = false;
                    $orderPositions1C->id = $arLinkerOrder[$key];
                    $orderPositions1C->organizationid = $OrderManager->arPositionsPre[0]->characteristics[0]->orgguid;

                    $orderPositions1C->request_certificate = $request_certificate;
                    $orderPositions1C->comment = $comment;
                    $orderPositions1C->delivery_terms = $delivery_terms;

                    foreach ($OrderManager->arPositionsPre as $value){
                        //запрашиваем данные из 1С (нужно для склада)

                        //region запрос позиции товара

//                        if($redirect1CTestDB){
//                            $Response = $this->Client->get('http://91.193.222.117:12380/stimul_test_maa/hs/ex/product/' . $value->guid,[
//                                //'auth' => ['OData', '11']
//                            ]);
//                        }else{
//                            $Response = $this->Client->get('http://10.68.5.205/StimulBitrix/hs/ex/product/' . $value->guid,[
//                                'auth' => ['OData', '11']
//                            ]);
//                        }
//                        $result = mb_substr(trim($Response->getBody()->getContents()), 2, -1);
//                        $extendPosition = current(json_decode($result,true)['response']);

                        /** @var array $extendPosition Модель позиции товара из 1С */
                        $extendPosition = $this->GetProductPositionFrom1CByXmlId($value->guid);

                        //endregion

                        $orderPositions1C->AddProduct($value,$extendPosition);
                    }

                    $arOrders1CCreate[] = $orderPositions1C->AsArray();
                }
            }

            if($OrderManager->arShoesPositionsPre && array_key_exists('shoes_pre',$arLinkerOrder)){
                $key = 'shoes_pre';

                //проверям существует ли заказ в 1С
                if($arLinkerOrder1CGUID[$arLinkerOrder[$key]]){
                    // модель для обновления
                    $orderPositions1C =  new \API\v1\Models\Order1CEdit($arLinkerOrder1CGUID[$arLinkerOrder[$key]]);

                    $orderPositions1C->reserved = false;
                    $orderPositions1C->id = $arLinkerOrder[$key];
                    $orderPositions1C->organizationid = $OrderManager->arShoesPositionsPre[0]->characteristics[0]->orgguid;

                    $orderPositions1C->request_certificate = $request_certificate;
                    $orderPositions1C->comment = $comment;
                    $orderPositions1C->delivery_terms = $delivery_terms;

                    foreach ($OrderManager->arShoesPositionsPre as $value){
                        //запрашиваем данные из 1С (нужно для склада)

                        //region запрос позиции товара

//                        if($redirect1CTestDB){
//                            $Response = $this->Client->get('http://91.193.222.117:12380/stimul_test_maa/hs/ex/product/' . $value->guid,[
//                                //'auth' => ['OData', '11']
//                            ]);
//                        }else{
//                            $Response = $this->Client->get('http://10.68.5.205/StimulBitrix/hs/ex/product/' . $value->guid,[
//                                'auth' => ['OData', '11']
//                            ]);
//                        }
//                        $result = mb_substr(trim($Response->getBody()->getContents()), 2, -1);
//                        $extendPosition = current(json_decode($result,true)['response']);

                        /** @var array $extendPosition Модель позиции товара из 1С */
                        $extendPosition = $this->GetProductPositionFrom1CByXmlId($value->guid);

                        //endregion

                        $orderPositions1C->AddProduct($value,$extendPosition);
                    }

                    $arOrders1CEdit[] = $orderPositions1C->AsArray();

                }else{
                    // модель для создания
                    $orderPositions1C =  new \API\v1\Models\Order1CAdd();

                    $orderPositions1C->reserved = false;
                    $orderPositions1C->id = $arLinkerOrder[$key];
                    $orderPositions1C->organizationid = $OrderManager->arShoesPositionsPre[0]->characteristics[0]->orgguid;

                    $orderPositions1C->request_certificate = $request_certificate;
                    $orderPositions1C->comment = $comment;
                    $orderPositions1C->delivery_terms = $delivery_terms;

                    foreach ($OrderManager->arShoesPositionsPre as $value){
                        //запрашиваем данные из 1С (нужно для склада)

                        //region запрос позиции товара

//                        if($redirect1CTestDB){
//                            $Response = $this->Client->get('http://91.193.222.117:12380/stimul_test_maa/hs/ex/product/' . $value->guid,[
//                                //'auth' => ['OData', '11']
//                            ]);
//                        }else{
//                            $Response = $this->Client->get('http://10.68.5.205/StimulBitrix/hs/ex/product/' . $value->guid,[
//                                'auth' => ['OData', '11']
//                            ]);
//                        }
//                        $result = mb_substr(trim($Response->getBody()->getContents()), 2, -1);
//                        $extendPosition = current(json_decode($result,true)['response']);

                        /** @var array $extendPosition Модель позиции товара из 1С */
                        $extendPosition = $this->GetProductPositionFrom1CByXmlId($value->guid);

                        //endregion

                        $orderPositions1C->AddProduct($value,$extendPosition);
                    }

                    $arOrders1CCreate[] = $orderPositions1C->AsArray();
                }
            }
            //var_dump($arOrders1CEdit);
            //var_dump($arOrders1CCreate);

/* LEGACY
 *             if($OrderManager->arPositions && array_key_exists('positions',$arLinkerOrder)){
                $arPositions = [];
                foreach ($OrderManager->arPositions as $value){
                    $arPositions[] = $value->AsArray();
                }
                //var_dump($arPositions);

                $Products = [];

                foreach ($arPositions as $key => $position){
                    // GUID товара
                    $positionGUID = $position['guid'];
                    //массив характеристик товара
                    $arCharacteristics = $position['characteristics'];

                    // запрос позиции товара
                    if($redirect1CTestDB){
                        $Response = $this->Client->get('http://91.193.222.117:12380/stimul_test_maa/hs/ex/product/' . $positionGUID,[
                            //'auth' => ['OData', '11']
                        ]);
                    }else{
                        $Response = $this->Client->get('http://10.68.5.205/StimulBitrix/hs/ex/product/' . $positionGUID,[
                            'auth' => ['OData', '11']
                        ]);
                    }

                    $result = mb_substr(trim($Response->getBody()->getContents()), 2, -1);
                    // модель позиции товара из 1С
                    $extendPosition = current(json_decode($result,true)['response']);

                    $arPositions[$key]['organization_guid'] = $extendPosition['organization_guid'];
                    $arPositions[$key]['name'] = $extendPosition['product'];

                    //var_dump($arCharacteristics);
                    //var_dump($extendPosition);

                    foreach($arCharacteristics as &$characteristic){
                        foreach ($extendPosition['characteristics'] as $positionEx){
                            if($characteristic['guid'] === $positionEx['guid']){

                                $characteristic['productid'] = $position['guid'];// UID товара/номенклатуры
                                $characteristic['characteristicsid'] = $characteristic['guid'];// UID характеристики товара

                                $price = (float)$characteristic['fullprice'] ?? (float)$positionEx['price'];

                                $characteristic['total'] = $price * (float)$characteristic['quantity']; // цена без учета скидки
                                // $characteristic['quantity'] - есть по умолчанию количество позиций
                                $characteristic['price'] = $price; // цена без скидки


                                $characteristic['discount'] = 0; // скидка (числом)

                                $guidStorage = null;

                                foreach ($positionEx['storages'] as $item){
                                    if((int)$characteristic['quantity'] <= (int)$item['quantity']){
                                        $guidStorage = $item['guid']; // UID склада
                                        break;
                                    }
                                }

                                $characteristic['storage'] = ($guidStorage) ?? current($positionEx['storages'])['guid'];

                                unset($characteristic['guid']);
                                unset($characteristic['orgguid']);
                                unset($characteristic['fullprice']);
                                break;
                            }
                        }
                    }

                    $Products[] = $arCharacteristics;
                    //var_dump($arCharacteristics);
                }

                //var_dump($Products);
                //var_dump($Products);

                $products = [];
                foreach ($Products as $product_position){
                    if(count($product_position) === 1){
                        $products[] = current($product_position);
                    }else{
                        foreach ($product_position as $product){
                            $products[] = $product;
                        }
                    }
                }
                //var_dump($products);
                //die();
                $total = 0;
                foreach ($products as $position){
                    $total += ($position['total']) ?? 0;
                }
                //$arPositions = $Products;

                //если нет guid то создаем документ в 1С, если есть обновляем
                $guid = $arLinkerOrder1CGUID[$arLinkerOrder['positions']];
                if(!$guid){
                    // модель на создание
                    $arOrders1CCreate[] = [
                        'reserved' => $Order->IsReserved(), //флаг резерва
                        'id' => $arLinkerOrder['positions'],
                        'organizationid' => $this->SPEC_ODA_ID, // UID фабрики, обувь или одежда
                        'contractid' => '', // UID договора с контрагентом (пока что береться на стороне 1С первый попавшийся)
                        'total' => $total, // сумма заказа с учетом скидки
                        'products' => $products
                    ];
                }else{
                    // модель на обновление
                    $arOrders1CEdit[] = [
                        'reserved' => $Order->IsReserved(), //флаг резерва
                        'guid' => $guid,
                        'id' => $arLinkerOrder['positions'],
                        'organizationid' => $this->SPEC_ODA_ID, // UID фабрики, обувь или одежда
                        'contractid' => '', // UID договора с контрагентом (пока что береться на стороне 1С первый попавшийся)
                        'total' => $total, // сумма заказа с учетом скидки
                        'products' => $products
                    ];
                }

                //var_dump($arOrders1C);
                //$response->getBody()->write(json_encode($arOrders1C));
            }

            if($OrderManager->arShoesPositions && array_key_exists('shoes',$arLinkerOrder)){
                $arPositions = [];
                foreach ($OrderManager->arShoesPositions as $value){
                    $arPositions[] = $value->AsArray();
                }
                //var_dump($arPositions);

                $Products = [];

                foreach ($arPositions as $key => $position){
                    // GUID товара
                    $positionGUID = $position['guid'];
                    //массив характеристик товара
                    $arCharacteristics = $position['characteristics'];

                    // запрос позиции товара
                    if($redirect1CTestDB){
                        $Response = $this->Client->get('http://91.193.222.117:12380/stimul_test_maa/hs/ex/product/' . $positionGUID,[
                            //'auth' => ['OData', '11']
                        ]);
                    }else{
                        $Response = $this->Client->get('http://10.68.5.205/StimulBitrix/hs/ex/product/' . $positionGUID,[
                            'auth' => ['OData', '11']
                        ]);
                    }

                    $result = mb_substr(trim($Response->getBody()->getContents()), 2, -1);
                    // модель позиции товара из 1С
                    $extendPosition = current(json_decode($result,true)['response']);

                    $arPositions[$key]['organization_guid'] = $extendPosition['organization_guid'];
                    $arPositions[$key]['name'] = $extendPosition['product'];

                    foreach($arCharacteristics as &$characteristic){
                        foreach ($extendPosition['characteristics'] as $positionEx){
                            if($characteristic['guid'] === $positionEx['guid']){

                                $characteristic['productid'] = $position['guid'];// UID товара/номенклатуры
                                $characteristic['characteristicsid'] = $characteristic['guid'];// UID характеристики товара

                                $price = (float)$characteristic['fullprice'] ?? (float)$positionEx['price'];

                                $characteristic['total'] = $price * (float)$characteristic['quantity']; // цена без учета скидки
                                // $characteristic['quantity'] - есть по умолчанию количество позиций
                                $characteristic['price'] = $price; // цена без скидки


                                $characteristic['discount'] = 0; // скидка (числом)

                                $guidStorage = null;

                                foreach ($positionEx['storages'] as $item){
                                    if((int)$characteristic['quantity'] <= (int)$item['quantity']){
                                        $guidStorage = $item['guid']; // UID склада
                                        break;
                                    }
                                }

                                $characteristic['storage'] = ($guidStorage) ?? current($positionEx['storages'])['guid'];

                                unset($characteristic['guid']);
                                unset($characteristic['orgguid']);
                                unset($characteristic['fullprice']);
                                break;
                            }
                        }
                    }

                    $Products[] = $arCharacteristics;
                }

                $products = [];
                foreach ($Products as $product_position){
                    if(count($product_position) === 1){
                        $products[] = current($product_position);
                    }else{
                        foreach ($product_position as $product){
                            $products[] = $product;
                        }
                    }
                }

                $total = 0;
                foreach ($products as $position){
                    $total += ($position['total']) ?? 0;
                }
                //$arPositions = $Products;

                $guid = $arLinkerOrder1CGUID[$arLinkerOrder['shoes']];
                if(!$guid){
                    //модель на создание
                    $arOrders1CCreate[] = [
                        'reserved' => $Order->IsReserved(), //флаг резерва
                        'id' => $arLinkerOrder['shoes'],
                        'organizationid' => $this->WORK_SHOES_ID, // UID фабрики, обувь или одежда
                        'contractid' => '', // UID договора с контрагентом (пока что береться на стороне 1С первый попавшийся)
                        'total' => $total, // сумма заказа с учетом скидки
                        'products' => $products
                    ];
                }else{
                    //модель на обновление
                    $arOrders1CEdit[] = [
                        'reserved' => $Order->IsReserved(), //флаг резерва
                        'guid' => $guid,
                        'id' => $arLinkerOrder['shoes'],
                        'organizationid' => $this->WORK_SHOES_ID, // UID фабрики, обувь или одежда
                        'contractid' => '', // UID договора с контрагентом (пока что береться на стороне 1С первый попавшийся)
                        'total' => $total, // сумма заказа с учетом скидки
                        'products' => $products
                    ];
                }

                //var_dump($arOrders1C);
                //$response->getBody()->write(json_encode($arOrders1C));
            }

            if($OrderManager->arPositionsPre && array_key_exists('positions_pre',$arLinkerOrder)){
                $arPositions = [];
                foreach ($OrderManager->arPositionsPre as $value){
                    $arPositions[] = $value->AsArray();
                }
                //var_dump($arPositions);

                $Products = [];

                foreach ($arPositions as $key => $position){
                    // GUID товара
                    $positionGUID = $position['guid'];
                    //массив характеристик товара
                    $arCharacteristics = $position['characteristics'];

                    // запрос позиции товара
                    if($redirect1CTestDB){
                        $Response = $this->Client->get('http://91.193.222.117:12380/stimul_test_maa/hs/ex/product/' . $positionGUID,[
                            //'auth' => ['OData', '11']
                        ]);
                    }else{
                        $Response = $this->Client->get('http://10.68.5.205/StimulBitrix/hs/ex/product/' . $positionGUID,[
                            'auth' => ['OData', '11']
                        ]);
                    }

                    $result = mb_substr(trim($Response->getBody()->getContents()), 2, -1);
                    // модель позиции товара из 1С
                    $extendPosition = current(json_decode($result,true)['response']);

                    $arPositions[$key]['organization_guid'] = $extendPosition['organization_guid'];
                    $arPositions[$key]['name'] = $extendPosition['product'];

                    foreach($arCharacteristics as &$characteristic){
                        foreach ($extendPosition['characteristics'] as $positionEx){
                            if($characteristic['guid'] === $positionEx['guid']){

                                $characteristic['productid'] = $position['guid'];// UID товара/номенклатуры
                                $characteristic['characteristicsid'] = $characteristic['guid'];// UID характеристики товара

                                $price = (float)$characteristic['fullprice'] ?? (float)$positionEx['price'];

                                $characteristic['total'] = $price * (float)$characteristic['quantity']; // цена без учета скидки
                                // $characteristic['quantity'] - есть по умолчанию количество позиций
                                $characteristic['price'] = $price; // цена без скидки


                                $characteristic['discount'] = 0; // скидка (числом)

                                $guidStorage = null;

                                foreach ($positionEx['storages'] as $item){
                                    if((int)$characteristic['quantity'] <= (int)$item['quantity']){
                                        $guidStorage = $item['guid']; // UID склада
                                        break;
                                    }
                                }

                                $characteristic['storage'] = ($guidStorage) ?? current($positionEx['storages'])['guid'];

                                unset($characteristic['guid']);
                                unset($characteristic['orgguid']);
                                unset($characteristic['fullprice']);
                                break;
                            }
                        }
                    }

                    $Products[] = $arCharacteristics;
                }

                $products = [];
                foreach ($Products as $product_position){
                    if(count($product_position) === 1){
                        $products[] = current($product_position);
                    }else{
                        foreach ($product_position as $product){
                            $products[] = $product;
                        }
                    }
                }

                $total = 0;
                foreach ($products as $position){
                    $total += ($position['total']) ?? 0;
                }
                //$arPositions = $Products;

                $guid = $arLinkerOrder1CGUID[$arLinkerOrder['positions_pre']];
                if(!$guid){
                    //модель на создание
                    $arOrders1CCreate[] = [
                        'reserved' => false, //флаг резерва
                        'id' => $arLinkerOrder['positions_pre'],
                        'organizationid' => $this->SPEC_ODA_ID, // UID фабрики, обувь или одежда
                        'contractid' => '', // UID договора с контрагентом (пока что береться на стороне 1С первый попавшийся)
                        'total' => $total, // сумма заказа с учетом скидки
                        'products' => $products
                    ];
                }else{
                    //модель на обновление
                    $arOrders1CEdit[] = [
                        'reserved' => false, //флаг резерва
                        'guid' => $guid,
                        'id' => $arLinkerOrder['positions_pre'],
                        'organizationid' => $this->SPEC_ODA_ID, // UID фабрики, обувь или одежда
                        'contractid' => '', // UID договора с контрагентом (пока что береться на стороне 1С первый попавшийся)
                        'total' => $total, // сумма заказа с учетом скидки
                        'products' => $products
                    ];
                }


                //var_dump($arOrders1C);
                //$response->getBody()->write(json_encode($arOrders1C));
            }

            if($OrderManager->arShoesPositionsPre && array_key_exists('shoes_pre',$arLinkerOrder)){
                $arPositions = [];
                foreach ($OrderManager->arShoesPositionsPre as $value){
                    $arPositions[] = $value->AsArray();
                }
                //var_dump($arPositions);

                $Products = [];

                foreach ($arPositions as $key => $position){
                    // GUID товара
                    $positionGUID = $position['guid'];
                    //массив характеристик товара
                    $arCharacteristics = $position['characteristics'];

                    // запрос позиции товара
                    if($redirect1CTestDB){
                        $Response = $this->Client->get('http://91.193.222.117:12380/stimul_test_maa/hs/ex/product/' . $positionGUID,[
                            //'auth' => ['OData', '11']
                        ]);
                    }else{
                        $Response = $this->Client->get('http://10.68.5.205/StimulBitrix/hs/ex/product/' . $positionGUID,[
                            'auth' => ['OData', '11']
                        ]);
                    }

                    $result = mb_substr(trim($Response->getBody()->getContents()), 2, -1);
                    // модель позиции товара из 1С
                    $extendPosition = current(json_decode($result,true)['response']);

                    $arPositions[$key]['organization_guid'] = $extendPosition['organization_guid'];
                    $arPositions[$key]['name'] = $extendPosition['product'];

                    foreach($arCharacteristics as &$characteristic){
                        foreach ($extendPosition['characteristics'] as $positionEx){
                            if($characteristic['guid'] === $positionEx['guid']){

                                $characteristic['productid'] = $position['guid'];// UID товара/номенклатуры
                                $characteristic['characteristicsid'] = $characteristic['guid'];// UID характеристики товара

                                $price = (float)$characteristic['fullprice'] ?? (float)$positionEx['price'];

                                $characteristic['total'] = $price * (float)$characteristic['quantity']; // цена без учета скидки
                                // $characteristic['quantity'] - есть по умолчанию количество позиций
                                $characteristic['price'] = $price; // цена без скидки


                                $characteristic['discount'] = 0; // скидка (числом)

                                $guidStorage = null;

                                foreach ($positionEx['storages'] as $item){
                                    if((int)$characteristic['quantity'] <= (int)$item['quantity']){
                                        $guidStorage = $item['guid']; // UID склада
                                        break;
                                    }
                                }

                                $characteristic['storage'] = ($guidStorage) ?? current($positionEx['storages'])['guid'];

                                unset($characteristic['guid']);
                                unset($characteristic['orgguid']);
                                unset($characteristic['fullprice']);
                                break;
                            }
                        }
                    }

                    $Products[] = $arCharacteristics;
                }

                $products = [];
                foreach ($Products as $product_position){
                    if(count($product_position) === 1){
                        $products[] = current($product_position);
                    }else{
                        foreach ($product_position as $product){
                            $products[] = $product;
                        }
                    }
                }

                $total = 0;
                foreach ($products as $position){
                    $total += ($position['total']) ?? 0;
                }
                //$arPositions = $Products;

                $guid = $arLinkerOrder1CGUID[$arLinkerOrder['shoes_pre']];
                if(!$guid){
                    //модель на создание
                    $arOrders1CCreate[] = [
                        'reserved' => false, //флаг резерва
                        'id' => $arLinkerOrder['shoes_pre'],
                        'organizationid' => $this->WORK_SHOES_ID, // UID фабрики, обувь или одежда
                        'contractid' => '', // UID договора с контрагентом (пока что береться на стороне 1С первый попавшийся)
                        'total' => $total, // сумма заказа с учетом скидки
                        'products' => $products
                    ];
                }else{
                    //модель на обновление
                    $arOrders1CEdit[] = [
                        'reserved' => false, //флаг резерва
                        'guid' => $guid,
                        'id' => $arLinkerOrder['shoes_pre'],
                        'organizationid' => $this->WORK_SHOES_ID, // UID фабрики, обувь или одежда
                        'contractid' => '', // UID договора с контрагентом (пока что береться на стороне 1С первый попавшийся)
                        'total' => $total, // сумма заказа с учетом скидки
                        'products' => $products
                    ];
                }

                //var_dump($arOrders1C);
                //$response->getBody()->write(json_encode($arOrders1C));
            }*/

            $this->Monolog->debug('Заказы на редактирование',[$arOrders1CEdit]);
            $this->Monolog->debug('Заказы на создание',[$arOrders1CCreate]);

            // для заказов которые уже созданы, редактирование
            if($arOrders1CEdit){
                $Response1CDataEdit = [
                    'edit' => true,
                    'id' => $args['id'], // идентификатор общего заказа
                    'data' => date('d.m.Y'), // дата заказа в формате d.m.Y 22.01.2022
                    'user' => $Token->GetId(), // идентификатор записи пользователя в Битрикс
                    'partnerID' => $Order->GetPartnerId(), // контрагент
                    //'total' => $requestData['total'], // итоговая стоимость
                    'orders' => $arOrders1CEdit
                ];
            }

            // для заказов, которые будут созданы.
            if($arOrders1CCreate){
                $Response1CDataCreate = [
                    'edit' => false,
                    'id' => $args['id'], // идентификатор общего заказа
                    'data' => date('d.m.Y'), // дата заказа в формате d.m.Y 22.01.2022
                    'user' => $Token->GetId(), // идентификатор записи пользователя в Битрикс
                    'partnerID' => $Order->GetPartnerId(), // контрагент
                    //'total' => $requestData['total'], // итоговая стоимость
                    'orders' => $arOrders1CCreate
                ];
            }

            //echo json_encode($arOrders1CEdit);
            //echo json_encode($Response1CDataCreate);
            //die();

            $this->Monolog->debug('Данные для 1С сервера на Редактирование',[$Response1CDataEdit]);
            $this->Monolog->debug('Данные для 1С сервера string:',['data' => json_encode($Response1CDataEdit)]);
            $this->Monolog->debug('Данные для 1С сервера на Создание:',[$Response1CDataCreate]);
            $this->Monolog->debug('Данные для 1С сервера string:',['data' => json_encode($Response1CDataCreate)]);
            //$response->getBody()->write(json_encode($Response1CData));
            //$response->getBody()->write(json_encode($Response1CData));

            //$response->getBody()->write(json_encode($Response1CData));
            //return $response
            //   ->withHeader('Content-Type', 'application/json')
            //  ->withStatus(201);

        }catch (\GuzzleHttp\Exception\GuzzleException $e){
            // исключение на позицию товара
            $this->Monolog->warning('Поймано исключение \GuzzleHttp\Exception\GuzzleException',['msg' => $e->getMessage(), 'code' =>$e->getCode()] );

            $response->getBody()->write(json_encode([
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ]));

            $this->Monolog->close();

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($e->getCode());

        }catch (\Exception $e){
            $this->Monolog->warning('Поймано простое \Exception',['msg' => $e->getMessage(), 'code' =>$e->getCode()] );
            $response->getBody()->write(json_encode([
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($e->getCode());
        }

        //endregion

        //region Отправляем данные в 1С

        try {
            //region Отправка в 1С
            if($redirect1CTestDB){
                $Response1C = $this->Client->post('http://91.193.222.117:12380/stimul_test_maa/hs/ex/order/add',[
                        //'auth' => ['OData', '11'],
                        'json' => json_encode($Response1CDataEdit)]
                );
            }else{
                $Response1C = $this->Client->post('http://10.68.5.205/StimulBitrix/hs/ex/order/add',[
                        'auth' => ['OData', '11'],
                        'json' => json_encode($Response1CDataEdit)]
                );
            }

            $body_response = json_decode(mb_substr(trim($Response1C->getBody()->getContents()), 2, -1),true);
            $code_response = $Response1C->getStatusCode();
            //endregion

            //var_dump(current($body_response['response'])['guid']);

            $this->Monolog->debug('Ответ от 1С, редактирование заказа', ['body' => $body_response, 'code' => $code_response]);

            //region меняем статус заказа и его id на идентификатор из 1С

            // в ID теперь храним модели в JSON формате, т.к. формируются отдельные счета.
            /**
             * @var array[] Массив позиций.
             */
            $arPosition1C = [];

            foreach($body_response['response'] as $item){
                foreach ($Response1CDataEdit['orders'] as $data){

                    if($item['id'] == $data['id']){
                        $arPosition1C[] = [
                            'id' => $item['id'], // ид элемента заказа в битрикс
                            'guid' => $item['guid'], // guid элемента заказа в 1С
                            'status' => $item['status'], // статус из 1С (передаётся цифрой)
                            'organization_id' => $data['organizationid'], // идентификатор организации: ФРО или ЭС
                            'n' => $item['numberorder']
                        ];
                        break;
                    }

                }
            }

            //region Если есть заказы, которые нужно создать

            if($arOrders1CCreate){
                if($redirect1CTestDB){
                    $Response1C = $this->Client->post('http://91.193.222.117:12380/stimul_test_maa/hs/ex/order/add',[
                            //'auth' => ['OData', '11'],
                            'json' => json_encode($Response1CDataCreate)]
                    );
                }else{
                    $Response1C = $this->Client->post('http://10.68.5.205/StimulBitrix/hs/ex/order/add',[
                            'auth' => ['OData', '11'],
                            'json' => json_encode($Response1CDataCreate)]
                    );
                }

                $body_response = json_decode(mb_substr(trim($Response1C->getBody()->getContents()), 2, -1),true);
                $code_response = $Response1C->getStatusCode();
                //endregion

                //var_dump(current($body_response['response'])['guid']);

                $this->Monolog->debug('Ответ от 1С, создание заказа', ['body' => $body_response, 'code' => $code_response]);

                /**
                 * @var array[] Массив позиций.
                 */
                $arPositionCreate = [];

                foreach($body_response['response'] as $item){
                    foreach ($Response1CDataCreate['orders'] as $data){

                        if($item['id'] == $data['id']){
                            $arPositionCreate[] = [
                                'id' => $item['id'], // ид элемента заказа в битрикс
                                'guid' => $item['guid'], // guid элемента заказа в 1С
                                'status' => $item['status'], // статус из 1С (передаётся цифрой)
                                'organization_id' => $data['organizationid'], // идентификатор организации: ФРО или ЭС
                                'n' => $item['numberorder']
                            ];
                            break;
                        }

                    }
                }

                if($arPositionCreate){
                    $arPosition1C = array_merge($arPosition1C,$arPositionCreate);
                }
            }

            //endregion

            $this->Monolog->debug('Новый стек заказов', $arPosition1C);
            \Psk\Api\Orders\DirectoryTable::update($args['id'],[
                'ID' => json_encode($arPosition1C),
                'EDITABLE' => 1
            ]);
        }catch (\Exception $e){
            // исключение на добавление заказа

            $this->Monolog->warning('Поймано исключение при попытки создания заказа в 1С \Exception',['msg' => $e->getMessage(), 'code' =>$e->getCode()] );

            \Psk\Api\Orders\DirectoryTable::update($args['id'],[
                'ID' => null,
            ]);

            $this->Monolog->debug('Модель ответа от сервера',[
                'response' => [
                    'id' => (int) $args['id'],
                    'message' => 'Резерв: №' . $args['id'] . ' изменен.'
                ],
                'error' => []
            ]);

            $response->getBody()->write(json_encode([
                'response' => [
                    'id' => (int) $args['id'],
                    'message' => 'Резерв: №' . $args['id'] . ' изменен.'
                ],
                'error' => []
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        }

        //endregion

        $this->Monolog->notice('Завершение Редактирование резерва ' . __FUNCTION__);


        //region Отправляем почтовое сообщение
        if(\Configuration::GetInstance()::IsProduction()){
            $Postman = new \API\v1\Service\Postman();
            $userId = 0;
            $partnerId = '';
            try {
                /** @var array $arEmailAddress  адреса для отправки */
                $arEmailAddress = [];

                $userId = $Token->GetId();
                $partnerId = $Order->GetPartnerId();

                $rsUser = \CUser::GetByID($Token->GetId());
                /** @var array $arUser Данные пользователя */
                $arUser = $rsUser->Fetch();

                //region Добавляем пользователя к рассылке, если установлены настройки

                /** @var \API\v1\Managers\Settings $SettingsManager Получение настроект пользователя*/
                $SettingsManager = new \API\v1\Managers\Settings();

                /** @var \API\v1\Models\NotificationSettings $NotificationSettings Настройки уведомлений */
                $NotificationSettings = $SettingsManager->GetOrderNotificationSettingsByUserConfigId($Token->GetConfig());

                if($NotificationSettings->order_email_changed) {
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

                $Postman->SendMessage(
                    (string)$Order->GetId(),
                    'Заказ: №' . $Order->GetId() . ' успешно отредактирован.',
                    $arEmailAddress
                );
            }catch (\PHPMailer\PHPMailer\Exception $e){
                $this->Monolog->error('Ошибка отправки почтового сообщения',[
                    'message'   => $e->getMessage(),
                    'code'      => $e->getCode()
                ]);
            }
        }
        //endregion

        //$response->getBody()->write(json_encode($arLinked));
        $response->getBody()->write(json_encode([
            'response' => [
                'id' => (int) $args['id'],
                'message' => 'Резерв: №' . $args['id'] . ' изменен.'
            ],
            'error' => []
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    }

    /**
     * Добавить заказ (поддержка резерва)
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     *
     */
    public function AddExtend(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface{
        $this->Monolog->info('Старт функции добавления заказа ' . __FUNCTION__);
        //region Конфигурируем структуру заказа под битрикс и сохраняем его в общей таблице заказов
        try{
            //die(123);
            //флаг перенаправления на тестовую 1С
            $redirect1CTestDB = !\Configuration::GetInstance()::IsProduction();

            /**
             * @var \API\v1\Models\Token Модель данных из токена авторизации
             */
            $Token = new \API\v1\Models\Token($request->getAttribute('tokenData'));

            $contents = $request->getBody()->getContents();
            /**
             * @var array Массив с данными о заказе
             */
            $requestData = json_decode($contents,true);

            //var_dump( (new \API\v1\Models\Order\Order($requestData))->AsArray() );

            /** @var \API\v1\Models\Order\Order $Order Модель заказа из личного кабинета */
            $Order = new \API\v1\Models\Order\Order($requestData);

            /** @var int количество позиций физически */
            $orderTotalCount = 0;

            //region Вычисляем массу заказа
            $Client = new \GuzzleHttp\Client();

                foreach (array_merge($Order->position,$Order->position_presail) as $position) {

                    $Response = $Client->get('https://psk.expert/test/product-page/ajax.php',[
                        'query' => [
                            'QUERY'     => $position->guid,
                            'OPTION'    => 8 // поиск по XML_ID
                        ],
                        'verify' => false
                    ]);

                    /** @var array Массив с данными о запрашиваемом товаре */
                    $arProduct = json_decode($Response->getBody()->getContents(),true);

                    /** @var int  Количество позиций в заказе */
                    $position_count = count($position->characteristics);

                    $orderTotalCount += $position_count;

                    $Order->weight += (float)$arProduct['PRODUCT']['WEIGHT'] * $position_count;
                    $Order->volume += (float)$arProduct['PRODUCT']['VALUME'] * $position_count;
                }
            //endregion
            //var_dump($Order);
            //die();

            $Partner = new \API\v1\Managers\Partner();
            $Partner = $Partner->GetByGUID($Order->GetPartnerId());
            $partner = $Partner->AsArray();

            //var_dump($partner);

            /** @var \API\v1\Managers\Order Репозиторий для работы с заказом */
            $OrderManager = new \API\v1\Managers\Order($Order);

            $this->Monolog->debug('Token',['data' => get_object_vars($Token)]);
            $this->Monolog->debug('Входные данные сторокой',['data' => $contents]);
            $this->Monolog->debug('Входные данные',$requestData);
            //var_dump($OrderManager->arPositions);
            //var_dump($OrderManager->arPositionsPre);
            //var_dump($OrderManager->arShoesPositions);
            //var_dump($OrderManager->arShoesPositionsPre);

            /**
             * @var array Массив связанных заказов
             *
             * - positions --
             * - positions_pre --
             * - shoes -- обувь
             * - shoes_pre -- предзаказ обуви
             */
            $arLinkerOrder = $OrderManager->WritePositionStackInBitrixDB(
                (string) $Order->GetId() . (string) $Token->GetId(),
                (string) $Token->GetId()
            );

            //var_dump($arLinkerOrder);

            // Реузальтат добавления записи общего заказа
            $CompleteOrderID = \Psk\Api\Orders\DirectoryTable::add([
                'ID' => (int)((string) $Order->GetId() . (string) $Token->GetId()), // Номер общего заказа \Bitrix\Main\Entity\IntegerField
                'DATE' => new \Bitrix\Main\Type\DateTime(date('d.m.Y H:m:s')), // Дата заказа    \Bitrix\Main\Entity\DatetimeField(
                'PARTNER_GUID' => $Order->GetPartnerId(), //Контрагент GUID \Bitrix\Main\Entity\StringField
                'PARTNER_NAME' => $partner['name'],// Контрагент Имя  \Bitrix\Main\Entity\StringField
                'STATUS' => '0', // Статус заказа    \Bitrix\Main\Entity\StringField
                'USER' => $Token->GetId(), // Идентификатор учетной записи пользователя в Битрикс \Bitrix\Main\Entity\IntegerField
                'LINKED' => serialize($arLinkerOrder), // Связанные заказы (массив ID записей в битрикс, сериализованное поле) \Bitrix\Main\Entity\StringField
                'COST' => (string)$Order->GetTotal(), // Общая сумма заказа без скидки  \Bitrix\Main\Entity\StringField
                'POSITIONS' => json_encode($requestData), // Список позиций  \Bitrix\Main\Entity\StringField
                'DISCOUNT' => '0', // Скидка числом  \Bitrix\Main\Entity\StringField
                'RESERVE' => (int) $Order->IsReserved() , // для зарезервированного типа заказа 1 | 0 \Bitrix\Main\Entity\IntegerField
                'EDITABLE' => (int) $Order->IsEdit(), // доступен для редактирования 1 | 0 \Bitrix\Main\Entity\IntegerField
                'SHIPMENT_COST' => (string)$Order->GetDelivery()->GetCost() ?? '' // Стоимость отгрузки, если ДРУГАЯ ТРАНСПОРТНАЯ = 900 , остальные = 0
            ]);

            //var_dump($CompleteOrderID->getId());

        }catch (\Exception $e){
            $response->getBody()->write(json_encode([
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($e->getCode());
        }
        //endregion

        //region Конфигурируем структуру заказа под базу 1С
        try{

            /** @var array $arOrders1C Массив с заказами для 1С базы */
            $arOrders1C = [];

            $rsUser = \CUser::GetByID($Token->GetId());
            /** @var array $arUser Данные пользователя */
            $arUser = $rsUser->Fetch();

            $request_certificate = (bool)$requestData['request_certificate']; // Добавить сертификат true | false
            $comment = sprintf('%s%s%s',
                $arUser['NAME'],
                ($Order->IsRequestedCertificate()) ? '/Cерт.' : '',
                ($requestData['comment']) ? '/' . $requestData['comment'] : ''); // Комментарий
            $delivery_terms = \API\v1\Models\Order\Delivery\ShipmentStatus::Get(
                \API\v1\Models\Order\Delivery\ShipmentStatus::GetByMnemonicCode($requestData['delivery']['case'] ?? 'other')
            )['title']; // Название отгрузки

            // товары
            if($OrderManager->arPositions) {
                $arPositions = [];
                foreach ($OrderManager->arPositions as $value){
                    $arPositions[] = $value->AsArray();
                }
                //var_dump($arPositions);

                $Products = [];

                foreach ($arPositions as $key => $position){
                    // GUID товара
                    $positionGUID = $position['guid'];
                    //массив характеристик товара
                    $arCharacteristics = $position['characteristics'];

//                    // запрос позиции товара
//                    if($redirect1CTestDB){
//                        $Response = $this->Client->get('http://91.193.222.117:12380/stimul_test_maa/hs/ex/product/' . $positionGUID,[
//                            //'auth' => ['OData', '11']
//                        ]);
//                    }else{
//                        $Response = $this->Client->get('http://10.68.5.205/StimulBitrix/hs/ex/product/' . $positionGUID,[
//                            'auth' => ['OData', '11']
//                        ]);
//                    }
//
//                    $result = mb_substr(trim($Response->getBody()->getContents()), 2, -1);
//                    // модель позиции товара из 1С
//                    $extendPosition = current(json_decode($result,true)['response']);

                    /** @var array $extendPosition Модель позиции товара из 1С */
                    $extendPosition = $this->GetProductPositionFrom1CByXmlId($positionGUID);

                    $arPositions[$key]['organization_guid'] = $extendPosition['organization_guid'];
                    $arPositions[$key]['name'] = $extendPosition['product'];

                    foreach($arCharacteristics as &$characteristic) {
                        foreach ($extendPosition['characteristics'] as $positionEx){
                            if($characteristic['guid'] === $positionEx['guid']){

                                $characteristic['productid'] = $position['guid'];// UID товара/номенклатуры
                                $characteristic['characteristicsid'] = $characteristic['guid'];// UID характеристики товара

                                $price = (float)$characteristic['fullprice'] ?? (float)$positionEx['price'];

                                $characteristic['total'] = $price * (float)$characteristic['quantity']; // цена без учета скидки
                                // $characteristic['quantity'] - есть по умолчанию количество позиций
                                $characteristic['price'] = $price; // цена без скидки


                                $characteristic['discount'] = 0; // скидка (числом)

                                $guidStorage = null;

                                foreach ($positionEx['storages'] as $item){
                                    if((int)$characteristic['quantity'] <= (int)$item['quantity']){
                                        $guidStorage = $item['guid']; // UID склада
                                        break;
                                    }
                                }

                                if($characteristic['characteristicsid'] === '00000000-0000-0000-0000-000000000000'){
                                    // №2 СИЗ/Инвентарь (Дубровки)
                                    $characteristic['storage'] = '065f052d-fc58-11e3-8704-0025907c0298';
                                }else{
                                    $characteristic['storage'] = ($guidStorage) ?? current($positionEx['storages'])['guid'];
                                }

                                unset($characteristic['guid']);
                                unset($characteristic['orgguid']);
                                unset($characteristic['fullprice']);
                                break;
                            }
                        }
                    }
                    unset($characteristic);
                    $Products[] = $arCharacteristics;
                }


                /*
                $products = [];
                foreach (current($Products) as $product){
                    $products[] = $product;
                }
                */
                $products = [];
                foreach ($Products as $product_position){
                    if(count($product_position) === 1){
                        $products[] = current($product_position);
                    }else{
                        foreach ($product_position as $product){
                            $products[] = $product;
                        }
                    }
                }

                $total = 0;
                foreach ($products as $position){
                    $total += ($position['total']) ?? 0;
                }
                //$arPositions = $Products;

                $arOrders1C[] = [
                    'reserved' => $Order->IsReserved(), //флаг резерва
                    'request_certificate' => $request_certificate, // Добавить сертификат true | false
                    'comment' => $comment, // Комментарий
                    'delivery_terms' => $delivery_terms, // Название отгрузки
                    'id' => $arLinkerOrder['positions'],
                    'organizationid' => $this->SPEC_ODA_ID, // UID фабрики, обувь или одежда
                    'contractid' => '', // UID договора с контрагентом (пока что береться на стороне 1С первый попавшийся)
                    'total' => $total, // сумма заказа с учетом скидки
                    'products' => $products,
                    'services' => []
                ];
                //var_dump($arOrders1C);
                //$response->getBody()->write(json_encode($arOrders1C));
            }

            // обувь
            if($OrderManager->arShoesPositions){
                $arPositions = [];
                foreach ($OrderManager->arShoesPositions as $value){
                    $arPositions[] = $value->AsArray();
                }
                //var_dump($arPositions);

                $Products = [];

                foreach ($arPositions as $key => $position){
                    // GUID товара
                    $positionGUID = $position['guid'];
                    //массив характеристик товара
                    $arCharacteristics = $position['characteristics'];

//                    // запрос позиции товара
//                    if($redirect1CTestDB){
//                        $Response = $this->Client->get('http://91.193.222.117:12380/stimul_test_maa/hs/ex/product/' . $positionGUID,[
//                            //'auth' => ['OData', '11']
//                        ]);
//                    }else{
//                        $Response = $this->Client->get('http://10.68.5.205/StimulBitrix/hs/ex/product/' . $positionGUID,[
//                            'auth' => ['OData', '11']
//                        ]);
//                    }
//
//                    $result = mb_substr(trim($Response->getBody()->getContents()), 2, -1);
//                    // модель позиции товара из 1С
//                    $extendPosition = current(json_decode($result,true)['response']);

                    /** @var array $extendPosition Модель позиции товара из 1С */
                    $extendPosition = $this->GetProductPositionFrom1CByXmlId($positionGUID);

                    $arPositions[$key]['organization_guid'] = $extendPosition['organization_guid'];
                    $arPositions[$key]['name'] = $extendPosition['product'];

                    foreach($arCharacteristics as &$characteristic){
                        foreach ($extendPosition['characteristics'] as $positionEx){
                            if($characteristic['guid'] === $positionEx['guid']){

                                $characteristic['productid'] = $position['guid'];// UID товара/номенклатуры
                                $characteristic['characteristicsid'] = $characteristic['guid'];// UID характеристики товара


                                $price = (float)$characteristic['fullprice'] ?? (float)$positionEx['price'];

                                $characteristic['total'] = $price * (float)$characteristic['quantity']; // цена без учета скидки
                                // $characteristic['quantity'] - есть по умолчанию количество позиций
                                $characteristic['price'] = $price; // цена без скидки


                                $characteristic['discount'] = 0; // скидка (числом)

                                //region Определяем склад
                                /**
                                 * Правки от 06.09.2022
                                 *
                                 * №3 Обувь (Дубровки) ФРО  0c329eed-30a1-11e7-8fdb-0025907c0298 (главные остатки для обуви)
                                 * №3 Обувь (Дубровки)      f61480c8-fc57-11e3-8704-0025907c0298 (если не хватает на ФРО) - Экспер (СО)
                                 *
                                 *
                                 */
                                $guidStorage = null;
                                $storage = null;

                                //todo: проверяем наличие склада №3 Обувь (Дубровки) ФРО
                                //todo: проверяем (int)$characteristic['quantity'] на склад №3 Обувь (Дубровки) ФРО
                                //todo: делаем заказ на количество
                                //todo: если есть ОСТАТОК делаем заказ на количество в №3 Обувь (Дубровки)

                                //todo: ЕСЛИ НЕТ, делаем заказ на количество в №3 Обувь (Дубровки)

                                //проверяем наличие склада №3 Обувь (Дубровки) ФРО
                                foreach ($positionEx['storages'] as $item){
                                    if($item['guid'] === '0c329eed-30a1-11e7-8fdb-0025907c0298') {
                                        $guidStorage = $item['guid'];
                                        $storage = $item;
                                        break;
                                    }
                                }
                                //var_dump($characteristic['quantity']);
                                //var_dump($guidStorage);
                                //var_dump($storage);

                                // ЕСЛИ СКЛАД доступен.
                                if($guidStorage){
                                    //проверяем (int)$characteristic['quantity'] на склад №3 Обувь (Дубровки) ФРО
                                    //если количество на складе меньше, требуемого и больше нуля
                                    if( (int)$characteristic['quantity'] > (int)$storage['quantity'] &&
                                        (int)$storage['quantity'] > 0) {

                                        // получаем остаток, для заказа.
                                        $this->RESIDUE_SHOES[] = [
                                            'quantity' => (int)$characteristic['quantity'] - (int)$storage['quantity'],
                                            'discount' => $characteristic['discount'],
                                            'price' => $characteristic['price'],
                                            'productid' => $characteristic['productid'],
                                            'characteristicsid' => $characteristic['characteristicsid'],
                                            'total' => floatval(((int)$characteristic['quantity'] - (int)$storage['quantity']) * $characteristic['price']),
                                            'storage' => 'f61480c8-fc57-11e3-8704-0025907c0298' // №3 Обувь (Дубровки)
                                        ];
                                        // переопределяем количество, в соответствии с доступным лимитом на складе.
                                        $characteristic['quantity'] = (int)$storage['quantity'];
                                    }
                                }
                                //var_dump($this->RESIDUE_SHOES);
                                //var_dump($characteristic['quantity']);
                                //die();
                                //ЕСЛИ НЕТ, склада №3 Обувь (Дубровки) ФРО,
                                //      делаем заказ на количество в №3 Обувь (Дубровки)
                                if(is_null($guidStorage)){
                                    foreach ($positionEx['storages'] as $item){
                                        if($item['guid'] === 'f61480c8-fc57-11e3-8704-0025907c0298'){
                                            $guidStorage = $item['guid'];
                                            break;
                                        }
                                    }
                                }

                                // для всех остальных:
                                if(is_null($guidStorage)){
                                    foreach ($positionEx['storages'] as $item){
                                        if((int)$characteristic['quantity'] <= (int)$item['quantity']){
                                            $guidStorage = $item['guid']; // UID склада
                                            break;
                                        }
                                    }
                                }


                                if($characteristic['characteristicsid'] === '00000000-0000-0000-0000-000000000000'){
                                    // №2 СИЗ/Инвентарь (Дубровки)
                                    $characteristic['storage'] = '065f052d-fc58-11e3-8704-0025907c0298';
                                }else{
                                    // если склад так и не определен, берем первый доступный.
                                    $characteristic['storage'] = ($guidStorage) ?? current($positionEx['storages'])['guid'];
                                }

//                                foreach ($positionEx['storages'] as $item){
//                                    if((int)$characteristic['quantity'] <= (int)$item['quantity']){
//                                        $guidStorage = $item['guid']; // UID склада
//                                        break;
//                                    }
//                                }
//
//                                $characteristic['storage'] = ($guidStorage) ?? current($positionEx['storages'])['guid'];

                                //endregion

                                unset($characteristic['guid']);
                                unset($characteristic['orgguid']);
                                unset($characteristic['fullprice']);
                                break;
                            }
                        }
                    }
                    unset($characteristic);
                    $Products[] = $arCharacteristics;
//                    var_dump($Products);
                }

                // $products = [];
//                foreach ($Products as $product){
//                    $products[] = current($product);
//                }
                //var_dump($Products);
                $products = [];
                foreach ($Products as $product_position){
                    if(count($product_position) === 1){
                        $products[] = current($product_position);
                    }else{
                        foreach ($product_position as $product){
                        $products[] = $product;
                        }
                    }
                }

//                var_dump($products);
//                die();

                $total = 0;
                foreach ($products as $position){
                    //var_dump($position);
                    $position['total'] = $position['quantity'] * $position['price'];
                    $total += ($position['total']) ?? 0;
                }

                //$arPositions = $Products;
                $arOrders1C[] = [
                    'reserved' => $Order->IsReserved(), //флаг резерва
                    'request_certificate' => $request_certificate, // Добавить сертификат true | false
                    'comment' => $comment, // Комментарий
                    'delivery_terms' => $delivery_terms, // Название отгрузки
                    'id' => $arLinkerOrder['shoes'],
                    'organizationid' => $this->WORK_SHOES_ID, // UID фабрики, обувь или одежда
                    'contractid' => '', // UID договора с контрагентом (пока что береться на стороне 1С первый попавшийся)
                    'total' => $total, // сумма заказа с учетом скидки
                    'products' => $products,
                    'services' => []
                ];
                //var_dump($arOrders1C);
                //$response->getBody()->write(json_encode($arOrders1C));

                //region Проверяем остатки для обуви, для заказа на складе СпецОдежда
                if($this->RESIDUE_SHOES){
                    //var_dump($this->RESIDUE_SHOES);

                    $total = 0;
                    foreach ($this->RESIDUE_SHOES as $position){
                        //var_dump($position);
                        $total += ($position['total']) ?? 0;
                    }

                    /** @var \API\v1\Models\Order\Position[] $arResiduePositions */
                    $arResiduePositions = [];
                    foreach ($this->RESIDUE_SHOES as $item){
                        $arResiduePositions[] = $OrderManager->CreateOrderPositionFrom1CPrepareData($item);
                    }

                    //var_dump($arResiduePositions);
                    //for($i = 1; $i < count($arResiduePositions); $i++){
                    //    $data = $arResiduePositions[0]->Merge($arResiduePositions[$i]);
                    //}
                    while( count($arResiduePositions) ){
                        $tmp = $arResiduePositions[0];
                        unset($arResiduePositions[0]);

                        foreach ($arResiduePositions as $key => $item){
                            if($tmp->guid === $item->guid){

                                $tmp->Merge($item);

                                unset($arResiduePositions[$key]);
                            }
                        }

                        $list[] = $tmp;
                        if(count($arResiduePositions)){
                            $arResiduePositions = array_values($arResiduePositions);
                        }else{
                            break;
                        }
                    }

                    $arResiduePositions = $list;

                    //var_dump($arResiduePositions);
                    //var_dump($list);
                    //die();
                    //var_dump($this->RESIDUE_SHOES[0]);
                    //todo: добавить в битрикс.
                    //var_dump($OrderManager->CreateOrderPositionFrom1CPrepareData($this->RESIDUE_SHOES[0]));
                    $arLinkerOrder['shoes_expert'] = $OrderManager->WriteAnyPositionInBitrixDB(
                        $CompleteOrderID->getId(),
                        $Token->GetId(),
                        $arResiduePositions
                    );

                    \Psk\Api\Orders\DirectoryTable::update($CompleteOrderID->getId(),[
                        'LINKED' => serialize($arLinkerOrder), // Связанные заказы (массив ID записей в битрикс, сериализованное поле)
                    ]);

                    // создать для 1С со СпецОдеждой
                    $arOrders1C[] = [
                        'reserved' => $Order->IsReserved(), //флаг резерва
                        'request_certificate' => $request_certificate, // Добавить сертификат true | false
                        'comment' => $comment, // Комментарий
                        'delivery_terms' => $delivery_terms, // Название отгрузки
                        'id' => $arLinkerOrder['shoes_expert'],
                        'organizationid' => $this->SPEC_ODA_ID, // UID фабрики, обувь или одежда
                        'contractid' => '', // UID договора с контрагентом (пока что береться на стороне 1С первый попавшийся)
                        'total' => $total, // сумма заказа с учетом скидки
                        'products' => $this->RESIDUE_SHOES,
                        'services' => []
                    ];

                    $this->RESIDUE_SHOES = [];
                }
                //endregion
                //var_dump($arOrders1C);
                //die();
            }

            // предзаказ товаров
            if($OrderManager->arPositionsPre){
                $arPositions = [];
                foreach ($OrderManager->arPositionsPre as $value){
                    $arPositions[] = $value->AsArray();
                }
                //var_dump($arPositions);

                $Products = [];

                foreach ($arPositions as $key => $position){
                    // GUID товара
                    $positionGUID = $position['guid'];
                    //массив характеристик товара
                    $arCharacteristics = $position['characteristics'];

//                    // запрос позиции товара
//                    if($redirect1CTestDB){
//                        $Response = $this->Client->get('http://91.193.222.117:12380/stimul_test_maa/hs/ex/product/' . $positionGUID,[
//                            //'auth' => ['OData', '11']
//                        ]);
//                    }else{
//                        $Response = $this->Client->get('http://10.68.5.205/StimulBitrix/hs/ex/product/' . $positionGUID,[
//                            'auth' => ['OData', '11']
//                        ]);
//                    }
//
//                    $result = mb_substr(trim($Response->getBody()->getContents()), 2, -1);
//                    // модель позиции товара из 1С
//                    $extendPosition = current(json_decode($result,true)['response']);

                    /** @var array $extendPosition Модель позиции товара из 1С */
                    $extendPosition = $this->GetProductPositionFrom1CByXmlId($positionGUID);

                    $arPositions[$key]['organization_guid'] = $extendPosition['organization_guid'];
                    $arPositions[$key]['name'] = $extendPosition['product'];

                    foreach($arCharacteristics as &$characteristic) {
                        foreach ($extendPosition['characteristics'] as $positionEx){
                            if($characteristic['guid'] === $positionEx['guid']){

                                $characteristic['productid'] = $position['guid'];// UID товара/номенклатуры
                                $characteristic['characteristicsid'] = $characteristic['guid'];// UID характеристики товара


                                $price = (float)$characteristic['fullprice'] ?? (float)$positionEx['price'];

                                $characteristic['total'] = $price * (float)$characteristic['quantity']; // цена без учета скидки
                                // $characteristic['quantity'] - есть по умолчанию количество позиций
                                $characteristic['price'] = $price; // цена без скидки


                                $characteristic['discount'] = 0; // скидка (числом)

                                $guidStorage = null;

                                foreach ($positionEx['storages'] as $item){
                                    if((int)$characteristic['quantity'] <= (int)$item['quantity']){
                                        $guidStorage = $item['guid']; // UID склада
                                        break;
                                    }
                                }

                                if($characteristic['characteristicsid'] === '00000000-0000-0000-0000-000000000000'){
                                    // №2 СИЗ/Инвентарь (Дубровки)
                                    $characteristic['storage'] = '065f052d-fc58-11e3-8704-0025907c0298';
                                }else{
                                    // если склад так и не определен, берем первый доступный.
                                    $characteristic['storage'] = ($guidStorage) ?? current($positionEx['storages'])['guid'];
                                }

                                unset($characteristic['guid']);
                                unset($characteristic['orgguid']);
                                unset($characteristic['fullprice']);
                                break;
                            }
                        }
                    }
                    unset($characteristic);
                    $Products[] = $arCharacteristics;
                }

                $products = [];
                foreach ($Products as $product_position){
                    if(count($product_position) === 1){
                        $products[] = current($product_position);
                    }else{
                        foreach ($product_position as $product){
                            $products[] = $product;
                        }
                    }
                }

                $total = 0;
                foreach ($products as $position){
                    $total += ($position['total']) ?? 0;
                }
                //$arPositions = $Products;
                $arOrders1C[] = [
                    'reserved' => false, //флаг резерва
                    'request_certificate' => $request_certificate, // Добавить сертификат true | false
                    'comment' => $comment, // Комментарий
                    'delivery_terms' => $delivery_terms, // Название отгрузки
                    'id' => $arLinkerOrder['positions_pre'],
                    'organizationid' => $this->SPEC_ODA_ID, // UID фабрики, обувь или одежда
                    'contractid' => '', // UID договора с контрагентом (пока что береться на стороне 1С первый попавшийся)
                    'total' => $total, // сумма заказа с учетом скидки
                    'products' => $products,
                    'services' => []
                ];
                //var_dump($arOrders1C);
                //$response->getBody()->write(json_encode($arOrders1C));
            }

            // предзаказ обуви
            if($OrderManager->arShoesPositionsPre){
                $arPositions = [];
                foreach ($OrderManager->arShoesPositionsPre as $value){
                    $arPositions[] = $value->AsArray();
                }
                //var_dump($arPositions);

                $Products = [];

                foreach ($arPositions as $key => $position){
                    // GUID товара
                    $positionGUID = $position['guid'];
                    //массив характеристик товара
                    $arCharacteristics = $position['characteristics'];

//                    // запрос позиции товара
//                    if($redirect1CTestDB){
//                        $Response = $this->Client->get('http://91.193.222.117:12380/stimul_test_maa/hs/ex/product/' . $positionGUID,[
//                            //'auth' => ['OData', '11']
//                        ]);
//                    }else{
//                        $Response = $this->Client->get('http://10.68.5.205/StimulBitrix/hs/ex/product/' . $positionGUID,[
//                            'auth' => ['OData', '11']
//                        ]);
//                    }
//
//                    $result = mb_substr(trim($Response->getBody()->getContents()), 2, -1);
//                    // модель позиции товара из 1С
//                    $extendPosition = current(json_decode($result,true)['response']);

                    /** @var array $extendPosition Модель позиции товара из 1С */
                    $extendPosition = $this->GetProductPositionFrom1CByXmlId($positionGUID);

                    $arPositions[$key]['organization_guid'] = $extendPosition['organization_guid'];
                    $arPositions[$key]['name'] = $extendPosition['product'];

                    foreach($arCharacteristics as &$characteristic) {
                        foreach ($extendPosition['characteristics'] as $positionEx){
                            if($characteristic['guid'] === $positionEx['guid']){

                                $characteristic['productid'] = $position['guid'];// UID товара/номенклатуры
                                $characteristic['characteristicsid'] = $characteristic['guid'];// UID характеристики товара


                                $price = (float)$characteristic['fullprice'] ?? (float)$positionEx['price'];

                                $characteristic['total'] = $price * (float)$characteristic['quantity']; // цена без учета скидки
                                // $characteristic['quantity'] - есть по умолчанию количество позиций
                                $characteristic['price'] = $price; // цена без скидки


                                $characteristic['discount'] = 0; // скидка (числом)

                                $guidStorage = null;

                                foreach ($positionEx['storages'] as $item){
                                    if((int)$characteristic['quantity'] <= (int)$item['quantity']){
                                        $guidStorage = $item['guid']; // UID склада
                                        break;
                                    }
                                }

                                if($characteristic['characteristicsid'] === '00000000-0000-0000-0000-000000000000'){
                                    // №2 СИЗ/Инвентарь (Дубровки)
                                    $characteristic['storage'] = '065f052d-fc58-11e3-8704-0025907c0298';
                                }else{
                                    // если склад так и не определен, берем первый доступный.
                                    $characteristic['storage'] = ($guidStorage) ?? current($positionEx['storages'])['guid'];
                                }

                                unset($characteristic['guid']);
                                unset($characteristic['orgguid']);
                                unset($characteristic['fullprice']);
                                break;
                            }
                        }
                    }
                    unset($characteristic);
                    $Products[] = $arCharacteristics;
                }

                $products = [];
                foreach ($Products as $product_position){
                    if(count($product_position) === 1){
                        $products[] = current($product_position);
                    }else{
                        foreach ($product_position as $product){
                            $products[] = $product;
                        }
                    }
                }

                $total = 0;
                foreach ($products as $position){
                    $total += ($position['total']) ?? 0;
                }
                //$arPositions = $Products;
                $arOrders1C[] = [
                    'reserved' => false, //флаг резерва
                    'request_certificate' => $request_certificate, // Добавить сертификат true | false
                    'comment' => $comment, // Комментарий
                    'delivery_terms' => $delivery_terms, // Название отгрузки
                    'id' => $arLinkerOrder['shoes_pre'],
                    'organizationid' => $this->WORK_SHOES_ID, // UID фабрики, обувь или одежда
                    'contractid' => '', // UID договора с контрагентом (пока что береться на стороне 1С первый попавшийся)
                    'total' => $total, // сумма заказа с учетом скидки
                    'products' => $products,
                    'services' => []
                ];
                //var_dump($arOrders1C);
                //$response->getBody()->write(json_encode($arOrders1C));
            }

            //region Костыль, добавляем Услугу по отгрузки за 900 в первую попавшуюся запись.
            /**
             * Логика:
             * $requestData['delivery']['bill_to'] - содержит XML_ID фабрики, это ЭС или ФРО.
             *
             * ['bill_to']:
             * - если пустое значение: добавляем услугу на первую попавшеюся позицию в списке.
             * - если заполнено: ищем соответсвии по XML_ID
             */
            if($requestData['delivery']['case'] === 'other') {

                // если пусто, добавляем на первую позицию
                if(empty($requestData['delivery']['bill_to'])) {
                    $arOrders1C[0]['services'][] = [
                        'quantity' => 1,
                        'productid' => 'deb4f821-b4ca-11df-9585-0050569a3a91',
                        'price' => 900,
                        'total' => 900,
                        'discount' => 0
                    ];
                }else{
                    // если задан guid фабрики, ищем в заказе для 1С.
                    foreach ($arOrders1C as &$order) {
                        if($order['organizationid'] === $requestData['delivery']['bill_to']) {
                            $order['services'][] = [
                                'quantity' => 1,
                                'productid' => 'deb4f821-b4ca-11df-9585-0050569a3a91',
                                'price' => 900,
                                'total' => 900,
                                'discount' => 0
                            ];
                            break;
                        }
                    }
                    unset($order);
                }

            }
            //endregion

            $Response1CData = [
                'edit' => $Order->IsEdit(),
                'id' => $CompleteOrderID->getId(), // идентификатор общего заказа
                'data' => date('d.m.Y'), // дата заказа в формате d.m.Y 22.01.2022
                'user' => $Token->GetId(), // идентификатор записи пользователя в Битрикс
                'partnerID' => $Order->GetPartnerId(), // контрагент
                //'total' => $requestData['total'], // итоговая стоимость
                'orders' => $arOrders1C
            ];

            $this->Monolog->debug('Данные для 1С сервера',$Response1CData);
            $this->Monolog->debug('Данные для 1С сервера string:',['data' => json_encode($Response1CData)]);
            //$response->getBody()->write(json_encode($Response1CData));
            //$response->getBody()->write(json_encode($Response1CData));

        }catch (\GuzzleHttp\Exception\GuzzleException $e){
            // исключение на позицию товара
            $this->Monolog->warning('Поймано исключение \GuzzleHttp\Exception\GuzzleException',['msg' => $e->getMessage(), 'code' =>$e->getCode()] );

            $response->getBody()->write(json_encode([
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ]));

            $this->Monolog->close();

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($e->getCode());

        }catch (\Exception $e){
            $response->getBody()->write(json_encode([
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($e->getCode());
        }
        //endregion

        //region Отправлям в 1С
        try {
            //region Отправка в 1С
            if($redirect1CTestDB){
                $Response1C = $this->Client->post('http://91.193.222.117:12380/stimul_test_maa/hs/ex/order/add',[
                        //'auth' => ['OData', '11'],
                        'json' => json_encode($Response1CData)]
                );
            }else{
                $Response1C = $this->Client->post('http://10.68.5.205/StimulBitrix/hs/ex/order/add',[
                        'auth' => ['OData', '11'],
                        'json' => json_encode($Response1CData)]
                );
            }

            $body_response = json_decode(mb_substr(trim($Response1C->getBody()->getContents()), 2, -1),true);
            $code_response = $Response1C->getStatusCode();
            //endregion

            //var_dump(current($body_response['response'])['guid']);

            $this->Monolog->debug('Ответ от 1С, создание заказа', ['body' => $body_response, 'code' => $code_response]);

            //проверяем на ошибки из 1С.
            //body: {"response":[],"error":{"code":400,"message":"Нет действующего договора с контрагентом!"}
            //code: 200
            if(array_key_exists('message',$body_response['error'])) {
                //кидаем ошибку
                throw new \Exception(
                    $body_response['error']['message'] ?? 'FATAL_ERROR',
                    $body_response['error']['code'] ?? 400);
            }

            //region меняем статус заказа и его id на идентификатор из 1С

            // в ID теперь храним модели в JSON формате, т.к. формируются отдельные счета.
            /**
             * @var array[] Массив позиций.
             */
            $arPosition1C = [];
            $this->Monolog->notice('Старт цикла формирования позиции счетов 1С, для записи в таблицу Битрикс');

            foreach($body_response['response'] as $item){
                $this->Monolog->debug('Позиция из 1С',['data' => $item]);
                foreach ($Response1CData['orders'] as $data){

                    if($item['id'] == $data['id']){
                        $this->Monolog->notice('Найдено совпадение.');
                        $this->Monolog->debug('Позиция отправленная в 1С',['data' => $data]);
                        $arPosition1C[] = [
                            'id' => $item['id'], // ид элемента заказа в битрикс
                            'guid' => $item['guid'], // guid элемента заказа в 1С
                            'status' => $item['status'], // статус из 1С (передаётся цифрой)
                            'organization_id' => $data['organizationid'], // идентификатор организации: ФРО или ЭС
                            'n' => $item['numberorder']
                        ];
                        break;
                    }

                }
            }

            $this->Monolog->debug('Сформированные позиции счетов из 1С, для записи',$arPosition1C);

            \Psk\Api\Orders\DirectoryTable::update($CompleteOrderID->getId(),[
                'ID' => json_encode($arPosition1C),
                'STATUS' => !$Order->IsReserved() ?
                    (string) \API\v1\Models\Registers\OrderStatus::waiting :
                    (string) \API\v1\Models\Registers\OrderStatus::reserved,
                'EDITABLE' => !$Order->IsReserved() ? 0 : 1
            ]);
        }catch (\Exception $e){
            // исключение на добавление заказа
            $msg = $e->getMessage() . ' Обратитесь к мендежеру.' ?? '';

            $this->Monolog->warning('Поймано исключение при попытке создания заказа в 1С \Exception',['msg' => $e->getMessage(), 'code' =>$e->getCode()] );

            \Psk\Api\Orders\DirectoryTable::update($CompleteOrderID->getId(),[
                'ID' => null,
            ]);

            $this->Monolog->debug('Модель ответа от сервера',[
                'response' => [
                    'id' => (int) $CompleteOrderID->getId(),
                    'message' => 'Заказ: №' . $CompleteOrderID->getId() . ' зарегистрирован с ошибкой. ' . $msg
                ],
                'error' => []
            ]);

            $response->getBody()->write(json_encode([
                'response' => [
                    'id' => (int) $CompleteOrderID->getId(),
                    'message' => 'Заказ: №' . $CompleteOrderID->getId() . ' зарегистрирован с ошибкой. ' . $msg
                ],
                'error' => []
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        }
        //endregion

        $partnerId = $Order->GetPartnerId();
            $Partner = new \API\v1\Managers\Partner();

            global $USER;
            $userId = $Token->GetId();
            $rsUser = $USER->GetByID($userId);
            $userLink = $rsUser->Fetch()['UF_PARTNERS_LIST']; //id массива с конфигурацией пользователя

            $sizes =  \CIBlockElement::GetProperty(
                \Environment::GetInstance()['iblocks']['Users'],
                $userLink,
                [],
                ['CODE' => 'PARTNERS']
            );

            while($size = $sizes->GetNext()) {

                # получаем ID связанных записей контрагентов
                $partner = $Partner->GetByBitrixID($size['VALUE']);
                if($partner->GetUID() === $partnerId)
                    break;
            }
        $this->Monolog->debug('Data:',['partnerId' => $partnerId, 'partner' => get_object_vars($partner)]);

        //region Отправляем почтовое сообщение
        if(\Configuration::GetInstance()::IsProduction()) {
            $Postman = new \API\v1\Service\Postman();

            $userId = 0;
            $partnerId = '';
            try {
                /** @var array $arEmailAddress  адреса для отправки */
                $arEmailAddress = [];

                $userId = $Token->GetId();
                $partnerId = $Order->GetPartnerId();

                $rsUser = \CUser::GetByID($Token->GetId());
                /** @var array $arUser Данные пользователя */
                $arUser = $rsUser->Fetch();

                //region Добавляем пользователя к рассылке, если установлены настройки

                /** @var \API\v1\Managers\Settings $SettingsManager Получение настроект пользователя*/
                $SettingsManager = new \API\v1\Managers\Settings();

                /** @var \API\v1\Models\NotificationSettings $NotificationSettings Настройки уведомлений */
                $NotificationSettings = $SettingsManager->GetOrderNotificationSettingsByUserConfigId($Token->GetConfig());

                if($NotificationSettings->order_email_created) {
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

                    while($size = $sizes->GetNext()) {

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

                //region Формируем позиции товара для письма
                $arOrderMail = [];
                foreach ($Order->position as $item) {
                    $ResponseSite = $this->Client->get('https://psk.expert/test/product-page/ajax.php',[
                        'query' => [
                            'QUERY'     => $item->guid,
                            'OPTION'    => 8
                        ],
                        'verify' => false
                    ]);
                    $arProduct = json_decode($ResponseSite->getBody()->getContents(),true,
                        512,
                        JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);

                    $arCharacteristics = [];

                    foreach ($item->characteristics as $characteristic){
                        foreach ($arProduct['OFFERS'] as $offer){
                            if($offer['GUID'] === $characteristic->guid){
                                $arCharacteristics[] = [
                                    'title' => $offer['CHARACTERISTIC'],
                                    'quantity' => $characteristic->quantity,
                                    'price' => $characteristic->price
                                ];
                            }
                        }
                    }

                    $arOrderMail[] = [
                        'pre' => '',
                        'name' => str_replace('&quot;','"',$arProduct['PRODUCT']['NAME']),
                        'article' => $arProduct['PRODUCT']['ARTICLE'],
                        'image' => $arProduct['IMAGES'][0],
                        'characteristics' => $arCharacteristics
                    ];
                }

                foreach ($Order->position_presail as $item){
                    $ResponseSite = $this->Client->get('https://psk.expert/test/product-page/ajax.php',[
                        'query' => [
                            'QUERY'     => $item->guid,
                            'OPTION'    => 8
                        ],
                        'verify' => false
                    ]);
                    $arProduct = json_decode($ResponseSite->getBody()->getContents(),true,
                        512,
                        JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);

                    $arCharacteristics = [];

                    foreach ($item->characteristics as $characteristic){
                        foreach ($arProduct['OFFERS'] as $offer){
                            if($offer['GUID'] === $characteristic->guid){
                                $arCharacteristics[] = [
                                    'title' => $offer['CHARACTERISTIC'],
                                    'quantity' => $characteristic->quantity,
                                    'price' => $characteristic->price
                                ];
                            }
                        }
                    }

                    $arOrderMail[] = [
                        'pre' => 'Предзаказ',
                        'name' => str_replace('&quot;','"',$arProduct['PRODUCT']['NAME']),
                        'article' => $arProduct['PRODUCT']['ARTICLE'],
                        'image' => $arProduct['IMAGES'][0],
                        'characteristics' => $arCharacteristics
                    ];
                }
                //endregion

                //region TWIG
                $TwigLoader = new \Twig_Loader_Filesystem($_SERVER['DOCUMENT_ROOT'] . '/local/src/twig_templates/post');
                $Twig = new \Twig_Environment($TwigLoader);
                $template = $Twig->loadTemplate('OrderAdd.html');

                $message = $template->render([
                    'ORDER_ID' => $CompleteOrderID->getId(),
                    'SUM' => $Order->GetTotal(),
                    'ORDER' => $arOrderMail
                ]);

                $this->Monolog->debug('TWIG RENDER', [$message]);
                //endregion

                $Postman->SendMessage(
                    (string)$CompleteOrderID->getId(),
                    $message,
                    $arEmailAddress
                );
            }catch (\PHPMailer\PHPMailer\Exception $e){
                $this->Monolog->error('Ошибка отправки почтового сообщения',[
                    'message'   => $e->getMessage(),
                    'code'      => $e->getCode()
                ]);
            }
        }
        //endregion

        //если есть доставка
        if( !empty($requestData['delivery']) && $requestData['delivery']['case'] !== 'pickup' ) {

            //region Создаем отгрузку
            try{
                /** @var \API\v1\Managers\Shipment Репозиторий работы с отгрузками */
                $Shipment = new \API\v1\Managers\Shipment($Token);

                /** @var array Массив с файлами */
                $files = [];

                /** @var array Массив с параметрами для отгрузки
                 *array(15) {
                 *      ["title"]=>
                 *      string(34) "Заказ № 192 от 10.03.2022"
                 *      ["partner_name"]=>
                 *      string(20) "ООО  Мастер"
                 *      ["partner_guid"]=>
                 *      string(36) "8152948b-ace6-11de-a660-0050569a3a91"
                 *      ["id"]=>
                 *      string(3) "148"
                 *      ["case"]=>
                 *      string(1) "1"
                 *      ["message"]=>
                 *      string(62) "СОПРОВОДИТЕЛЬНЫЙ ТЕКСТ ПРЕТЕНЗИИ"
                 *      ["amount"]=>
                 *      string(2) "17"
                 *      ["weight"]=>
                 *      string(2) "20"
                 *      ["volume"]=>
                 *      string(2) "45"
                 *      ["carriers"]=>
                 *      string(1) "0"
                 *      ["date"]=>
                 *      string(13) "1647370039349"
                 *      ["address"]=>
                 *      string(77) "100100 г. Москва, ул Пушкина, дом колотушкина. "
                 *      ["comment"]=>
                 *      string(20) "фывапролдж"
                 *      ["extra"]=>
                 *      string(7) "[1,2]"
                 *      ["urgently"]=>
                 *      string(1) "1"
                 */
                $arParams = [
                    'title' => sprintf('Заказ № %s от %s',
                        $CompleteOrderID->getId(),
                        date('d.m.Y')),
                    'partner_name' => $partner->GetManagerName() ?? '',
                    'partner_guid' => $Order->GetPartnerId(),
                    'id' => $CompleteOrderID->getId(),
                    'message' => '', // представление
                    'amount' => $orderTotalCount,
                    'weight' => $Order->GetWeight(),
                    'volume' => $Order->GetVolume(),
                    'date' => $Order->GetDelivery()->GetDate(),
                    'address' => $Order->GetDelivery()->GetAddress(),
                    'comment' => $requestData['comment'], //комментарий
                    'extra' => [], // Дополнительно, пока не используем
                    'urgently' => 0  // Срочно, пока не используем
                ];

                //Вид отгрузки
                //shipment status . [0 - Самовывоз, 1 - Доставка, 2 - До транспортной]
                switch($requestData['delivery']['case']) {
                    //самовывоз
                    case 'self': $arParams['case'] = 0;
                        break;

                    //доставка
                    case 'delivery': $arParams['case'] = 1;
                        break;

                    //до транспортной
                    default: $arParams['case'] = 2;
                        break;
                }

                //Транспортные компании
                if($arParams['case'] === 2){
                    switch ($requestData['delivery']['case']) {

                        //ПЭК
                        case 'pek': $arParams['carriers'] = 2;
                            break;

                        //Деловые линии
                        case 'del_lin': $arParams['carriers'] = 3;
                            break;

                        //Байкал
                        case 'baikal': $arParams['carriers'] = 4;
                            break;

                        //ДРУГАЯ ТРАНСПОРТНАЯ
                        default: $arParams['carriers'] = 1;
                            break;
                    }
                }else{
                    $arParams['carriers'] = ''; // carriers status , если не до транспортной, то пустой параметр или
                    // $arParams['case'] = 2 [1 - ДРУГАЯ ТРАНСПОРТНАЯ = 900 рублей, 2 - ПЭК, 3 - Деловые линии, 4 - Байкал]
                }

                /** @var int Идентификатор новой заявки */
                $shipmentId = $Shipment->Add($arParams,$files);

            }catch (\Exception $e){
                $this->Monolog->error('Ошибка при создании отгрузки',[
                    'msg' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'line' => $e->getLine()
                    ]);
            }
            //endregion

            //region Отправляем почтовое сообщение: Отгрузка
            if(\Configuration::GetInstance()::IsProduction()) {
                try {
                    $Postman = new \API\v1\Service\Postman();

                    $CIBlockElement = \CIBlockElement::GetList(
                        [],
                        [
                            'IBLOCK_ID'   => \Environment::IBLOCK_ID_SHIPMENTS,
                            'SECTION_ID ' => $Shipment->GetSectionId(),
                            'ID'          => $shipmentId
                        ], false, false, ['*']
                    );

                    if ($element = $CIBlockElement->GetNextElement()) {
                        $fields = $element->GetFields();
                        $props = $element->GetProperties();

                        $TwigLoader = new \Twig_Loader_Filesystem($_SERVER['DOCUMENT_ROOT'] . '/local/src/twig_templates/post');
                        $Twig = new \Twig_Environment($TwigLoader);
                        $template = $Twig->loadTemplate('Shipment.html');

                        $message = $template->render([
                            'TITLE'         => $fields['NAME'],
                            'ORDER_ID'      => $props['ORDER_ID']['VALUE'],
                            'DATE_SHIPMENT' => $props['DATE_SHIPMENT']['VALUE'],
                            'ADDRESS'       => $props['ADDRESS']['VALUE'],
                            'AMOUNT'        => $props['AMOUNT']['VALUE'],
                            'WEIGHT'        => $props['WEIGHT']['VALUE'],
                            'VOLUME'        => $props['VOLUME']['VALUE'],
                            'CASE'          => $props['CASE']['VALUE'],
                            'CARRIERS'      => $Order->GetDelivery()->GetShipment()->GetTitle(),//$props['CARRIERS']['VALUE'],
                            'COMMENT'       => $props['COMMENT']['VALUE'],
                            'PARTNER_NAME'  => $props['PARTNER_NAME']['~VALUE'],
                        ]);

                        // отсылаем почтовое сообщение
                        $Postman->SendMessage(
                            'Новая заявка на отгрузку для ' . $arParams['title'],
                            $message,
                            []
                        );
                    }
                } catch (\Exception $e) {
                    $this->Monolog->error('Ошибка при формировании почтового сообщения.',
                        [
                            'msg'  => $e->getMessage(),
                            'code' => $e->getCode()
                        ]);
                }
            }
            //endregion

        }

        //region ТЕСТ СОЗДАНИЕ УВЕДОМЛЕНИЯ ДЛЯ ЛК

        $dbPath = '/srv/db_notification/NotificationsUserList.db';

        try{
            $dbh = new \PDO('sqlite:' . $dbPath);

            $sql = sprintf(
                "INSERT INTO messages (USER_ID,CREATE_DATE,MESSAGE,SEND_DATE,IS_SEND,RECEIVED_DATE,IS_RECEIVED) VALUES (%d,'%s','%s','%s',%d,'%s',%d)",
                $Token->GetId(),
                date('d.m.Y H:m:s'),
                'Заказ №' . $CompleteOrderID->getId() . ' успешно создан.',
                '00.00.0000 00:00:00',
                0,
                '00.00.0000 00:00:00',
                0
            );

            $result = $dbh->exec($sql);

            $this->Monolog->debug('Создано записей для уведомления ЛК', ['count' => $result]);

        }catch (\PDOException $e) {
            $this->Monolog->error('Ошибка при создании уведомления для ЛК.',['msg' => $e->getMessage()]);
        }

        //endregion

        //region Формируем тело ответа
        $response->getBody()->write(json_encode([
            'response' => [
                'id' => (int) $CompleteOrderID->getId(),
                'message' => 'Заказ: №' . $CompleteOrderID->getId() . ' зарегистрирован.'
            ],
            'error' => []
        ]));
        //endregion

        $this->Monolog->info('Завершение функции добавления заказа ' . __FUNCTION__);
        $this->Monolog->close();

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    }

    /**
     * Добавить заказ
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     *
     * @deprecated
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
            $orderPosition->reserved = (bool) $item['RESERVE'];
            $orderPosition->id = ''; // deprecated - оставлено чтобы не сломать логику в ЛК
            $orderPosition->n = $item['INDEX']; // номер
            $orderPosition->name = 'Заказ № ' . $item['INDEX'] . ' от ' . $item['DATE']->format('d.m.Y');
            $orderPosition->date = $item['DATE']->format('d.m.Y H:i:s');
            $orderPosition->partner_guid = $item['PARTNER_GUID'];
            $orderPosition->partner_name = $item['PARTNER_NAME'];
            $orderPosition->status = \API\v1\Models\Registers\OrderStatus::Get((int)$item['STATUS'])['title'];
            $orderPosition->status_code = (int)$item['STATUS'];
            $orderPosition->user_id = $item['USER'];
            $orderPosition->comment = (json_decode($item['POSITIONS']))->comment ?? '';
            // временный массив заглушка для счетов и всего остального
            $orderPosition->checks = json_decode($item['ID'],true); // теперь модель сформированных счетов;
            //var_dump($item);

            //region Фильтруем счета, если есть в массиве LINKED, выводим

            $arLinked = unserialize($item['LINKED']);
            $arChecks = json_decode($item['ID'],true);

            $stack = [];
            foreach ($arChecks as $value){
                foreach ($arLinked as $sub_value){
                    if($sub_value === $value['id']){
                        $stack[] = $value;
                        break;
                    }
                }
            }

            $orderPosition->checks = $stack;
            //endregion

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

        $Response1C = $this->Client->get('http://10.68.5.205/StimulBitrix/hs/ex/product/' . $position['guid'],[
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
        //флаг перенаправления на тестовую 1С
        $redirect1CTestDB = !\Configuration::GetInstance()::IsProduction();

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

            foreach ($ResponseData['position'] as &$position) {

                $Response = $Client->get('https://psk.expert/test/product-page/ajax.php',[
                    'query' => [
                        'QUERY'     => $position['guid'],
                        'OPTION'    => 8 // поиск по XML_ID
                    ],
                    'verify' => false
                ]);

                //region запрос позиции товара из 1С
/*
                if($redirect1CTestDB){
                    $Response1C = $Client1C->get('http://91.193.222.117:12380/stimul_test_maa/hs/ex/product/' . $position['guid'],[
                        //'auth' => ['OData', '11']
                    ]);
                }else{
                    $Response1C = $Client1C->get('http://10.68.5.205/StimulBitrix/hs/ex/product/' . $position['guid'],[
                        'auth' => ['OData', '11']
                    ]);
                }

                $result1C = mb_substr(trim($Response1C->getBody()->getContents()), 2, -1);
*/
                /** @var array Модель позиции товара из 1С */
                //$extendPosition1C = current(json_decode($result1C,true)['response']);

                //$arCharacteristic1C = [];
                //foreach ($extendPosition1C['characteristics'] as $characteristic){
                //    $arCharacteristic1C[$characteristic['guid']] = $characteristic['quantity'];
                //}

                //var_dump($extendPosition);
                //die();

                //endregion

                /** @var array Массив с данными о запрашиваемом товаре */
                $arProduct = json_decode($Response->getBody()->getContents(),true);

                $position['name'] = $arProduct['PRODUCT']['NAME'];
                $position['id'] = $arProduct['PRODUCT']['ID'];
                $position['article'] = $arProduct['PRODUCT']['ARTICLE'];
                $position['images'] = $arProduct['IMAGES'];

                foreach ($position['characteristics'] as &$characteristic){
                    foreach ($arProduct['OFFERS'] as $offer){
                        if($characteristic['guid'] === $offer['GUID']){

                            //$characteristic['available'] =
                            //    $arCharacteristic1C[$characteristic['guid']] ?? 0;

                            $characteristic['id'] = $offer['ID'];
                            $characteristic['title'] = $offer['CHARACTERISTIC'];
                            $characteristic['price'] = $characteristic['price'] ?? $offer['PRICE'];
                            break;
                        }
                    }
                }
                unset($characteristic);
            }
            unset($position);

            foreach ($ResponseData['position_presail'] as &$position_presail) {

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

                foreach ($position_presail['characteristics'] as &$characteristic) {
                    foreach ($arProduct['OFFERS'] as $offer){
                        if($characteristic['guid'] === $offer['GUID']){
                            $characteristic['id'] = $offer['ID'];
                            $characteristic['title'] = $offer['CHARACTERISTIC'];
                            $characteristic['price'] = $characteristic['price'] ?? $offer['PRICE'];
                            break;
                        }
                    }
                }
                unset($characteristic);
            }
            unset($position_presail);

            $ResponseData['partner_name'] = $result['PARTNER_NAME'];
            $ResponseData['delivery']['cost'] = (float)$result['SHIPMENT_COST'] ?? 0.0;
            //region Добавляем остатки
//            $Client1C = new \GuzzleHttp\Client();
//
//            foreach ($ResponseData['position'] as $value){
//
//                //region запрос позиции товара из 1С
//
//                if($redirect1CTestDB){
//                    $Response1C = $Client1C->get('http://91.193.222.117:12380/stimul_test_maa/hs/ex/product/' . $value['guid'],[
//                        //'auth' => ['OData', '11']
//                    ]);
//                }else{
//                    $Response1C = $Client1C->get('http://10.68.5.205/StimulBitrix/hs/ex/product/' . $value['guid'],[
//                        'auth' => ['OData', '11']
//                    ]);
//                }
//
//                $result1C = mb_substr(trim($Response1C->getBody()->getContents()), 2, -1);
//
//                /** @var array Модель позиции товара из 1С */
//                $extendPosition1C = current(json_decode($result1C,true)['response']);
//
//                $arCharacteristic1C = [];
//                foreach ($extendPosition1C['characteristics'] as $characteristic){
//                    $arCharacteristic1C[$characteristic['guid']] = $characteristic['quantity'];
//                }
//
//                //var_dump($extendPosition);
//                //die();
//
//                //endregion
//
//                foreach ($value['characteristics'] as $key => $subvalue){
//                    $value['characteristics'][$key]['available'] =
//                        $arCharacteristic1C[$subvalue['guid']] ?? 0;
//                }
//            }
            //endregion Добавляем остатки

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
     * Получить позицию товара из 1С
     *
     * @param string $id XML идентификатор позиции товара
     *
     * @return array Позиция товара
     */
    private function GetProductPositionFrom1CByXmlId(string $id): array {

        //флаг перенаправления на тестовую 1С
        $redirect1CTestDB = !\Configuration::GetInstance()::IsProduction();

        if($redirect1CTestDB){
            $Response = $this->Client->get('http://91.193.222.117:12380/stimul_test_maa/hs/ex/product/' . $id,[
                //'auth' => ['OData', '11']
            ]);
        }else{
            $Response = $this->Client->get('http://10.68.5.205/StimulBitrix/hs/ex/product/' . $id,[
                'auth' => ['OData', '11']
            ]);
        }

        $result = mb_substr(trim($Response->getBody()->getContents()), 2, -1);

        // модель позиции товара из 1С
        $extendPosition = current(json_decode($result,true)['response']);

        //region Фильтруем склады, исключаем ненужные
        foreach ($extendPosition['characteristics'] as $key => $characteristic) {

            $extendPosition['characteristics'][$key]['storages'] = array_values(
                array_filter(
                    $characteristic['storages'],
                    function ($value) {
                        return !in_array($value['guid'],$this->arIDsStorageIgnore);
                    }
                )
            );

        }
        //endregion

        return $extendPosition;
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