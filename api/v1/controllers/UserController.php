<?php
namespace API\v1\Controllers;
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

include_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/local/modules/psk.api/lib/DirectoryTable.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/managers/User.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/managers/Settings.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/responses/Responses.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/responses/ErrorResponse.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/Environment.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/Token.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/OrderCheckEx.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/service/Postman.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/registers/OrderStatus.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/managers/Shipment.php';

use API\v1\Managers\User;
use API\v1\Models\Response;
use Firebase\JWT\JWT;
use Monolog\ErrorHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UserController
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
     * Смена статуса: Запросить счёт (запрос идет в 1С).
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function RequestCheck(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface{
        try{
            //флаг перенаправления на тестовую 1С
            $redirect1CTestDB = !\Configuration::GetInstance()::IsProduction();

            /** @var \API\v1\Models\Token Модель данных из токена авторизации */
            $Token = new \API\v1\Models\Token($request->getAttribute('tokenData'));

            // счет строкой
            $contents = $request->getBody()->getContents();

            /** @var array $check Счёт массивом*/
            $check = json_decode($contents,true);

            $OrderCheckEx = new \API\v1\Models\OrderCheckEx($check);

            /** @var int $order_id Идентификатор общего заказа */
            $order_id = (int) $args['id'];
            $DBResult = \Psk\Api\Orders\DirectoryTable::getList([
                'select'  => ['*'], // имена полей, которые необходимо получить в результате
                'filter'  => ['INDEX' => $order_id], // описание фильтра для WHERE и HAVING
                //'group'   => ... // явное указание полей, по которым нужно группировать результат
                //'order'   => ['INDEX' => 'DESC'] // параметры сортировки
                //'limit'   => ... // количество записей
                //'offset'  => ... // смещение для limit
                //'runtime' => ... // динамически определенные поля
            ])->Fetch();

            $orderStack = json_decode($DBResult['ID'],true);

            //region отправка в 1С
            $Client = new \GuzzleHttp\Client();

            // запрос позиции товара
            if($redirect1CTestDB){
                $Response1C = $Client->post('http://91.193.222.117:12380/stimul_test_maa/hs/ex/order/statuschange',[
                    //'auth' => ['OData', '11'],
                    'json' => [
                        'guid'=> $OrderCheckEx->guid,
                        'status' => \API\v1\Models\Registers\OrderStatus::requested
                    ]
                ]);
            }else{
                $Response1C = $Client->post('http://10.68.5.205/StimulBitrix/hs/ex/order/statuschange',[
                    'auth' => ['OData', '11'],
                    'json' => [
                        'guid'=> $OrderCheckEx->guid,
                        'status' => \API\v1\Models\Registers\OrderStatus::requested
                    ]
                ]);
            }

            // code - 201 если изменения успешны,
            /* если ошибка, код 200, json-body:
             *  array(2) {
             *    ["response"]=>
             *    array(0) {
             *    }
             *    ["error"]=>
             *    array(2) {
             *      ["code"]=>
             *      int(404)
             *      ["message"]=>
             *      string(34) "Неверный GUID заказа"
             *    }
             *  }
             */
            $arResponse = json_decode(mb_substr(trim($Response1C->getBody()->getContents()), 2, -1),true);

            if(array_key_exists('error',$arResponse ?? [])) {
                throw new \Exception($arResponse['error']['message'],$arResponse['error']['code']);
            }
            //endregion

            $OrderCheckEx->status = 10;

            //region Меняем данные статуса в Битрикс

            foreach ($orderStack as &$order) {
                if($order['guid'] === $OrderCheckEx->guid){
                    $order['status'] = 10;
                    break;
                }
            }

            \Psk\Api\Orders\DirectoryTable::update($order_id,[
                'ID' => json_encode($orderStack)
            ]);

            //endregion

            //region Отправляем почтовое сообщение
            if(\Configuration::GetInstance()::IsProduction()){
                $Postman = new \API\v1\Service\Postman();

                try {
                    /** @var array $arEmailAddress  адреса для отправки */
                    $arEmailAddress = [];

                    $rsUser = \CUser::GetByID($Token->GetId());
                    /** @var array $arUser Данные пользователя */
                    $arUser = $rsUser->Fetch();

                    //region Добавляем пользователя к рассылке, если установлены настройки

                    /** @var \API\v1\Managers\Settings $SettingsManager Получение настроект пользователя*/
                    //$SettingsManager = new \API\v1\Managers\Settings();

                    /** @var \API\v1\Models\NotificationSettings $NotificationSettings Настройки уведомлений */
                    //$NotificationSettings = $SettingsManager->GetOrderNotificationSettingsByUserConfigId($arUser['UF_PARTNERS_LIST']);

                    //if($NotificationSettings->order_email_states) {
                    //    $arEmailAddress[] = $arUser['EMAIL'];
                    //}

                    //endregion

                    //region Вычисляем и добавляем менеджера
                    if($Token->GetId() && $DBResult['PARTNER_GUID']) {
                        $Partner = new \API\v1\Managers\Partner();

                        global $USER;
                        $rsUser = $USER->GetByID($Token->GetId());
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
                            if($partner->GetUID() === $DBResult['PARTNER_GUID'])
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
                        (string)$order_id,
                        'Заказ: №' . $order_id . '. Пользователь запрашивает счёт.',
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

        }catch (\Exception $e){
            return ErrorResponse($e,$response);
        }

        $Response = new \API\v1\Models\Response();
        $Response->data = [$OrderCheckEx->AsArray()];
        $Response->code = 200;

        $response->getBody()->write($Response->AsJSON());
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($Response->code);
    }

    /**
     * Получить настройки конкретного пользователя
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function GetSettings(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface{

        try{

            /** @var \API\v1\Models\Token Модель данных из токена авторизации */
            $Token = new \API\v1\Models\Token($request->getAttribute('tokenData'));

            /** @var \API\v1\Managers\Settings $SettingsManager Получение настроект пользователя*/
            $SettingsManager = new \API\v1\Managers\Settings();

            /** @var \API\v1\Models\PersonalSettings $PersonalSetting Персональные настройки */
            $PersonalSettings = $SettingsManager->GetPersonalSettingsByUserId($Token->GetId());

            /** @var \API\v1\Models\NotificationSettings $NotificationSettings Настройки уведомлений */
            $NotificationSettings = $SettingsManager->GetOrderNotificationSettingsByUserConfigId($Token->GetConfig());

        }catch (\Exception $e){
            return ErrorResponse($e,$response);
        }

        $Response = new \API\v1\Models\Response();

        $Response->data = [
          'personal' => $PersonalSettings->AsArray(),
          'notifications' => $NotificationSettings->AsArray()
        ];

        $Response->code = 200;

        $response->getBody()->write($Response->AsJSON());
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($Response->code);
    }

    /**
     * Установить настройки для уведомлений
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function UpdateNotificationsSetup(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface{
        try {
            $contents = $request->getBody()->getContents();

            /** @var \API\v1\Models\Token Модель данных из токена авторизации */
            $Token = new \API\v1\Models\Token($request->getAttribute('tokenData'));

            /** @var \API\v1\Managers\Settings $SettingsManager Получение настроект пользователя */
            $SettingsManager = new \API\v1\Managers\Settings();

            /** @var \API\v1\Models\NotificationSettings $NotificationSettings Модель настроект уведомлений */
            $NotificationSettings = (new \API\v1\Models\NotificationSettings())->Set(json_decode($contents,true));

            $Result = $SettingsManager->UpdateOrderNotificationSettings(
                $Token->GetConfig(),
                $NotificationSettings
            );

        }catch (\Exception $e){
            return ErrorResponse($e,$response);
        }

        $Response = new \API\v1\Models\Response();
        $Response->data = $Result->AsArray();
        $Response->code = 201;

        $response->getBody()->write($Response->AsJSON());

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($Response->code);
    }

    /**
     * Установить персональные настройки
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function UpdatePersonalData(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface{
        try {
            /** @var \API\v1\Models\Token Модель данных из токена авторизации */
            $Token = new \API\v1\Models\Token($request->getAttribute('tokenData'));

            /** @var array $arData Входные параметры form-data */
            $arData = $request->getParsedBody();

            /** @var array $arUserParams Массив полей для обновления */
            $arUserParams = [];

            // проверяем имя
            if(array_key_exists('name',$arData)) {
                $arUserParams['NAME'] = $arData['name'];
            }

            // проверяем фамилию
            if(array_key_exists('lastname',$arData)) {
                $arUserParams['LAST_NAME'] = $arData['lastname'];
            }

            // проверяем отчество
            if(array_key_exists('patronymic',$arData)) {
                $arUserParams['SECOND_NAME'] = $arData['patronymic'];
            }

            // проверяем адрес электронной почты
            if(array_key_exists('email',$arData)) {
                $arUserParams['EMAIL'] = $arData['email'];
            }

            // проверяем номер телефона
            if(array_key_exists('phone',$arData)) {
                if((preg_match('/^\+(\d{1,4})(\d{10,})$/', $arData['phone']) == 0)) {
                    $phone = $arData['phone'];
                    $phone = str_replace(['(',')','-','_','+',' '],'',$phone); // удаляем другие символы
                    $phone = trim($phone); //удаляем пробелы
                    $phone = preg_replace('/^(8|7)(\d+)$/i', '+7$2', $phone); //удаляем 8
                    if(strlen($phone) === 10) // если отсутсвует код страны
                        $phone = '+7' . $phone;

                    $arUserParams['PERSONAL_PHONE'] = $phone;
                }else{
                    $arUserParams['PERSONAL_PHONE'] = $arData['phone'];
                }
            }

            //проверяем на изображение
            /** @var \Slim\Psr7\UploadedFile $file */
            if($file = current($request->getUploadedFiles())){
                if($file_id = \CFile::SaveFile([
                    'name'    => $file->getClientFilename(),
                    'size'    => $file->getSize(),
                    'type'    => $file->getClientMediaType(),
                    'content' => (string) $file->getStream()
                ], '/main/users/' . $Token->GetId() . '/avatar')) {
                    $arUserParams['PERSONAL_PHOTO'] = \CFile::MakeFileArray($file_id, '/main/users/' . $Token->GetId() . '/avatar');
                }
            }

            $user = new \CUser;
            if(!$user->Update($Token->GetId(), $arUserParams))
                throw new \Exception($user->LAST_ERROR,400);

            /** @var \API\v1\Models\PersonalSettings $PersonalSetting Персональные настройки */
            $PersonalSettings = (new \API\v1\Managers\Settings())->GetPersonalSettingsByUserId($Token->GetId());

        }catch (\Exception $e){
            return ErrorResponse($e,$response);
        }

        $Response = new \API\v1\Models\Response();
        $Response->data = $PersonalSettings->AsArray();
        $Response->code = 201;

        $response->getBody()->write($Response->AsJSON());

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($Response->code);

    }

    /**
     * Авторизация пользователя
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function Authorization(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface{
        $this->Monolog->info('Попытка авторизации пользователя');
        /**
         * @var array{username: string, password: string} $requestData Массив с данными авторизации пользователя
         */
        #$requestData = $request->getAttribute('tokenAuthData');

        $body = json_decode($request->getBody()->getContents(),true);

        /**
         * @var string Логин
         */
        $login = $body['login'];

        /**
         * @var string Пароль
         */
        $password = $body['password'];
        $this->Monolog->debug('Входные данные', ['login' => $login, 'pass' => $password]);

        try{

            /**
             * @var User Класс менеджер пользователей
             */
            $User = new User(["username" => $login, "password" => $password]);

        }catch(\Exception $e){
            $this->Monolog->error('Поймано исключение', ['message' => $e->getMessage(), 'code' => $e->getCode()]);
            return ErrorResponse($e,$response);
        }

        $responseData = $User->GetPass();
        $this->Monolog->debug('Найден пользователь',[$responseData]);

        # Формируем ответ
        $Response = new Response();
        $Response->data = [
            "token" => JWT::encode([
                'id'    =>  $responseData['id'],
                'config' => $responseData['config'],
                'sign'  =>  $responseData['sign']
            ],\Environment::JWT_PRIVATE_KEY,'HS256')
        ];
        $Response->code = 200;

        $this->Monolog->debug('Выдан токен', [$Response->data]);

        $response->getBody()->write(
            $Response->AsJSON()
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($Response->code);
    }

    /**
     * Получить список отгрузок пользователя
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function GetShipmentList(
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
            \Bitrix\Main\Loader::includeModule('iblock');

            $CIBlockElement = \CIBlockElement::GetList(
                [],
                ['ID' => $tokenData['config']], false,false,['ID', 'NAME', 'PROPERTY_SHIPMENTS','PROPERTY_CLAIMS']);

            // получаем id раздела хранения заявок
            if($arElement = $CIBlockElement->Fetch())
                $id = $arElement['PROPERTY_SHIPMENTS_VALUE'];

            /** @var array $arResponse Массив с данными ответа. */
            $arResponse = [];

            if(!$id){ throw new \Exception('Раздел с заявками не найден, необходимо создать заявку.',404);}
            else{
                $CIBlockElement = \CIBlockElement::GetList(
                    [],
                    ['IBLOCK_ID' => \Environment::IBLOCK_ID_SHIPMENTS,'IBLOCK_SECTION_ID' => $id], false,false,['*']);
                while ($element = $CIBlockElement->GetNextElement()){
                    $fields = $element->GetFields();
                    $props = $element->GetProperties();

                    $arResponse[] = [
                        'bitrix_id' => $fields['ID'],
                        'date_create' => $fields['DATE_CREATE'],
                        'status' => 0,// TODO: пока что 0, нужно уточнить по статусам!
                        'title' => $fields['NAME'],
                        'partner_name' => $props['PARTNER_NAME']['VALUE'],
                        'partner_guid' => $props['PARTNER_GUID']['VALUE'],
                        'id' => $props['ORDER_ID']['VALUE'],
                        'case' => $props['CASE']['VALUE'],
                        'message' => $props['REPRESENT']['VALUE']['TEXT'],
                        'files' => [],
                        'amount' => $props['AMOUNT']['VALUE'],
                        'weight' => $props['WEIGHT']['VALUE'],
                        'volume' => $props['VOLUME']['VALUE'],
                        'carriers' => '',
                        'date' => $props['DATE_SHIPMENT']['VALUE'],
                        'address' => $props['ADDRESS']['VALUE'],
                        'comment' => $props['COMMENT']['VALUE'],
                        'extra' => $props['EXTRA']['VALUE'],
                        //'urgently' => ''
                    ];
                }
            }

            $response->getBody()->write(json_encode([
                'response' => $arResponse,
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
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($e->getCode());
        }

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Получить список претензий пользователя
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function GetClaimList(
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
            \Bitrix\Main\Loader::includeModule('iblock');

            $CIBlockElement = \CIBlockElement::GetList(
                [],
                ['ID' => $tokenData['config']], false,false,['ID', 'NAME', 'PROPERTY_SHIPMENTS','PROPERTY_CLAIMS']);

            // получаем id раздела хранения заявок
            if($arElement = $CIBlockElement->Fetch())
                $id = $arElement['PROPERTY_CLAIMS_VALUE'];

            /** @var array $arResponse Массив с данными ответа. */
            $arResponse = [];

            if(!$id){ throw new \Exception('Раздел с заявками не найден, необходимо создать заявку.',404);}
            else{
                $CIBlockElement = \CIBlockElement::GetList(
                    [],
                    ['IBLOCK_ID' => \Environment::IBLOCK_ID_CLAIMS,'IBLOCK_SECTION_ID' => $id], false,false,['*']);
                while ($element = $CIBlockElement->GetNextElement()){
                    $fields = $element->GetFields();
                    $props = $element->GetProperties();

                    $arResponse[] = [
                        'bitrix_id' => $fields['ID'],
                        'date_create' => $fields['DATE_CREATE'],
                        'status' => 0,// TODO: пока что 0, нужно уточнить по статусам!
                        'title' => $fields['NAME'],
                        'partner_name' => $props['PARTNER_NAME']['VALUE'],
                        'partner_guid' => $props['PARTNER_GUID']['VALUE'],
                        'id' => $props['ORDER_ID']['VALUE'],
                        'case' => $props['CASE']['VALUE'],
                        'products' => json_decode($props['PRODUCTS']['~VALUE'],true) ?? [],
                        'files' => [],
                    ];
                }

                $response->getBody()->write(json_encode([
                    'response' => $arResponse,
                    'error' => []
                ]));
            }

        }catch (\Exception $e){
            $response->getBody()->write(json_encode([
                'response' => [],
                'error' => [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                ]
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        }

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Добавить заявку на отгрузку
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function AddShipment(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface{
        try{
            /** @var array $arParsedBody Внешние данные
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
             * }
             */
            $arParsedBody = $request->getParsedBody();

            if(empty($arParsedBody))
                throw new \Exception('Request params is empty!', 400);

            /** @var \Slim\Psr7\UploadedFile[] $arUploadedFiles Массив с Файлами вложения, ключ 'files' или пустой массив */
            $arUploadedFiles = $request->getUploadedFiles();

            /** @var array Массив с подготовленными файлами */
            $files = [];

            // если присутствуют файлы
            if($arUploadedFiles){
                // $arUploadedFiles по ключу files - должен содержать массив с \Slim\Psr7\UploadedFile
                // {"files":[ {"Slim\\Psr7\\UploadedFile":[]}, {"Slim\\Psr7\\UploadedFile":[]} ] }
                /** @var \Slim\Psr7\UploadedFile $file */
                foreach ($arUploadedFiles['files'] as $file){
                    $fileId = \CFile::SaveFile(
                        [
                            'name'    => $file->getClientFilename(),
                            'size'    => $file->getSize(),
                            'type'    => $file->getClientMediaType(),
                            'content' => (string) $file->getStream()
                        ],
                        '/shipment' // Путь к папке в которой хранятся файлы (относительно папки /upload).
                    );

                    if($fileId) {
                        //$arFilesId[] = $fileId;
                        $files[] = \CFile::MakeFileArray($fileId, '/shipment');
                    }
                }
            }

            /** @var bool Флаг перенаправления на тестовую 1С */
            $redirect1CTestDB = !\Configuration::GetInstance()::IsProduction();

            /** @var \API\v1\Models\Token Модель данных из токена авторизации */
            $Token = new \API\v1\Models\Token($request->getAttribute('tokenData'));

            /** @var \API\v1\Managers\Shipment Репозиторий работы с отгрузками */
            $Shipment = new \API\v1\Managers\Shipment($Token);

            /** @var int Идентификатор новой заявки */
            $shipmentId = $Shipment->Add($arParsedBody,$files);

        }catch (\Exception $e){
            return ErrorResponse($e,$response);
        }

        //region Отправляем почтовое сообщение.
        try{

            $CIBlockElement = \CIBlockElement::GetList(
                [],
                ['IBLOCK_ID' => \Environment::IBLOCK_ID_SHIPMENTS,
                 'SECTION_ID ' => $Shipment->GetSectionId(),
                 'ID' => $shipmentId
                ], false,false,['*']
            );

            if($element = $CIBlockElement->GetNextElement()) {
                $fields = $element->GetFields();
                $props = $element->GetProperties();

                $TwigLoader = new \Twig_Loader_Filesystem($_SERVER['DOCUMENT_ROOT'] . '/local/src/twig_templates/post');
                $Twig = new \Twig_Environment($TwigLoader);
                $template = $Twig->loadTemplate('Shipment.html');

                $message = $template->render([
                    'TITLE' => $fields['NAME'],
                    'ORDER_ID' => $props['ORDER_ID']['VALUE'],
                    'DATE_SHIPMENT' => $props['DATE_SHIPMENT']['VALUE'],
                    'ADDRESS' => $props['ADDRESS']['VALUE'],
                    'AMOUNT' => $props['AMOUNT']['VALUE'],
                    'WEIGHT' => $props['WEIGHT']['VALUE'],
                    'VOLUME' => $props['VOLUME']['VALUE'],
                    'CASE' => $props['CASE']['VALUE'],
                    'CARRIERS' => $props['CARRIERS']['VALUE'],
                    'COMMENT' => $props['COMMENT']['VALUE'],
                    'PARTNER_NAME' => $props['PARTNER_NAME']['~VALUE'],
                ]);

                // отсылаем почтовое сообщение
                $this->SendMessage(
                    'Новая заявка на отгрузку для ' . $arParsedBody['title'],
                    $message,
                    []
                );
            }
        } catch (\Exception $e) {
            $this->Monolog->error('Ошибка при формировании почтового сообщения.',
                [
                    'msg' => $e->getMessage(),
                    'code' => $e->getCode()
                ]);
        }

        //endregion

        $Response = new \API\v1\Models\Response();
        $Response->data = ['id' => $shipmentId];
        $Response->code = 200;

        $response->getBody()->write($Response->AsJSON());

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($Response->code);
//
//        try {
//            /** @var array{id: int, config: int, sign: int} $tokenData Данные из токена
//             *  id - идентификатор пользователя
//             *  config - идентификатор элемента инфоблока конфигурации пользователя
//             *  sign - кодовая подпись
//             */
//            $tokenData = $request->getAttribute('tokenData');
//            //var_dump($tokenData);
//
//            $rsUser = \CUser::GetByID((int)$tokenData['id']);
//            /** @var array $arUser Данные пользователя */
//            $arUser = $rsUser->Fetch();
//
//            \Bitrix\Main\Loader::includeModule('iblock');
//
//            $CIBlockElement = \CIBlockElement::GetList(
//                [],
//                ['ID' => $tokenData['config']], false,false,['ID', 'NAME', 'PROPERTY_SHIPMENTS','PROPERTY_CLAIMS']);
//
//            // получаем id раздела хранения заявок
//            if($arElement = $CIBlockElement->Fetch())
//                $id = $arElement['PROPERTY_SHIPMENTS_VALUE'];
//
//            //var_dump($id);
//
//            //region Создаем раздел, если он отсутствует
//            if(!$id){
//                // если id отсутствует создаем раздел
//                global $USER;
//                if( $arUser = $USER->GetByID((int)$tokenData['id'])->Fetch() ){
//
//                    $name = $arUser['LAST_NAME'] . ' ' . $arUser['NAME'] . ' ' . $arUser['SECOND_NAME'] . ' #' . $arUser['ID'];
//                }else{ throw new \Exception('Не найден пользователь по ID.',409); }
//
//                $CIBlockSection = new \CIBlockSection;
//
//                if($sectionId = $CIBlockSection->Add([
//                    'IBLOCK_ID' => \Environment::IBLOCK_ID_SHIPMENTS,
//                    'NAME' => $name,
//                ]))
//                {
//                    // записываем id раздела
//                    \CIBlockElement::SetPropertyValuesEx(
//                        (int) $tokenData['config'],
//                        \Environment::GetInstance()['iblocks']['Users'],
//                        ['SHIPMENTS' => $sectionId],
//                    );
//                }else{ throw new \Exception('Не удается создать раздел для заявок.',409);}
//
//
//            }
//            //endregion
//
//            // добавляем заявку в раздел
//
//            /** @var array $arParsedBody Внешние данные
//             *array(15) {
//             *      ["title"]=>
//             *      string(34) "Заказ № 192 от 10.03.2022"
//             *      ["partner_name"]=>
//             *      string(20) "ООО  Мастер"
//             *      ["partner_guid"]=>
//             *      string(36) "8152948b-ace6-11de-a660-0050569a3a91"
//             *      ["id"]=>
//             *      string(3) "148"
//             *      ["case"]=>
//             *      string(1) "1"
//             *      ["message"]=>
//             *      string(62) "СОПРОВОДИТЕЛЬНЫЙ ТЕКСТ ПРЕТЕНЗИИ"
//             *      ["amount"]=>
//             *      string(2) "17"
//             *      ["weight"]=>
//             *      string(2) "20"
//             *      ["volume"]=>
//             *      string(2) "45"
//             *      ["carriers"]=>
//             *      string(1) "0"
//             *      ["date"]=>
//             *      string(13) "1647370039349"
//             *      ["address"]=>
//             *      string(77) "100100 г. Москва, ул Пушкина, дом колотушкина. "
//             *      ["comment"]=>
//             *      string(20) "фывапролдж"
//             *      ["extra"]=>
//             *      string(7) "[1,2]"
//             *      ["urgently"]=>
//             *      string(1) "1"
//             * }
//             */
//            $arParsedBody = $request->getParsedBody();
//
//            //$this->Monolog->debug('parsedBody',[$arParsedBody]);
//
//            if(empty($arParsedBody))
//                throw new \Exception('Request params is empty!', 400);
//
//            /** @var \Slim\Psr7\UploadedFile[] $arUploadedFiles Массив с Файлами вложения, ключ 'files' или пустой массив */
//            $arUploadedFiles = $request->getUploadedFiles();
//
//            //$this->Monolog->debug('uploadFiles',[$arUploadedFiles]);
//
//            //var_dump(json_decode($arParsedBody['extra'],true));
//            //var_dump($arUploadedFiles);
//
//            $CIBlockElement = new \CIBlockElement;
//
//            /** @var array $arProps Массив свойств элемента */
//            $arProps = [
//                'PARTNER_NAME'  => $arParsedBody['partner_name'],   // Контрагент
//                'PARTNER_GUID'  => $arParsedBody['partner_guid'],   // Идентификатор контрагента
//                'ORDER_ID'      => $arParsedBody['id'],       // Идентификатор общего заказа
//                'AMOUNT'        => $arParsedBody['amount'], // Количество едениц
//                'WEIGHT'        => $arParsedBody['weight'], // Общий вес
//                'VOLUME'        => $arParsedBody['volume'], // Общий объем
//                'DATE_SHIPMENT' => date('d.m.Y',((int)$arParsedBody['date']/1000)), // Дата отгрузки
//                'ADDRESS'       => $arParsedBody['address'], // Адрес
//                'COMMENT'       => $arParsedBody['comment'], // Комментарий
//                'REPRESENT'     => ['VALUE' => ['TEXT' => $arParsedBody['message'], 'TYPE' => 'html']], // HTML/TEXT Представление
//
//            ];
//
//            //shipment status Вид отгрузки. [0 - Самовывоз, 1 - Доставка, 2 - До транспортной]
//            switch ((int)$arParsedBody['case']) {
//                case 1 : $arProps['CASE'] = 10; // код из битрикса: Доставка
//                    break;
//                case 2 : $arProps['CASE'] = 11; // код из битрикса: До транспортной
//                    break;
//
//                default: $arProps['CASE'] = 9; // код из битрикса: Самовывоз
//                    break;
//            }
//
//            //Транспортные компании
//            if($arProps['CASE'] === 11){
//                //region carriers status Транспортные компании, если не до транспортной, то пустой параметр или 0 [1 - другая, 2 - ПЭК, 3 - Деловые линии, 4 - Байкал]
//                $arProps['CARRIERS']  = ['VALUE' => 'other'];//(int)$arParsedBody['carriers']
//                switch ((int)$arParsedBody['carriers']){
//                    case 2 : $arProps['CARRIERS'] = ['VALUE' => 'pek']; // код из битрикса: Доставка
//                        break;
//                    case 3 : $arProps['CARRIERS'] = ['VALUE' => 'lines']; // код из битрикса: До транспортной
//                        break;
//                    case 4 : $arProps['CARRIERS'] = ['VALUE' => 'baikal']; // код из битрикса: До транспортной
//                        break;
//                    default: $arProps['CASE'] = ['VALUE' => 'other']; // код из битрикса: Самовывоз
//                        break;
//                }
//            }
//
//            //Дополнительные условия к доставке
//            if($arParsedBody['extra']) {
//                //JSON Дополнительное условие к доставке, если есть. [1 - Жесткая упаковка, 2 - Ополечивание], перечисление через массив. или пустой параметр
//                $extra = json_decode($arParsedBody['extra'],true);
//                foreach ($extra as $item){
//                    if((int)$item === 1)
//                        $arProps['EXTRA'][] = 12; // Жесткая упаковка
//
//                    if((int)$item === 2)
//                        $arProps['EXTRA'][] = 13; // Ополечивание
//                }
//            }
//
//            //Срочно
//            if((int)$arParsedBody['urgently'] === 1){
//                $arProps['IS_URGENTLY'] = 14; // Да
//            }
//
//            /** @var array $arFilesId Массив с идентификаторами загруженных файлов */
//            $arFilesId = [];
//
//            // если присутствуют файлы
//            if($arUploadedFiles){
//                // $arUploadedFiles по ключу files - должен содержать массив с \Slim\Psr7\UploadedFile
//                // {"files":[ {"Slim\\Psr7\\UploadedFile":[]}, {"Slim\\Psr7\\UploadedFile":[]} ] }
//                /** @var \Slim\Psr7\UploadedFile $file */
//                foreach ($arUploadedFiles['files'] as $file){
//                    $fileId = \CFile::SaveFile(
//                        [
//                            'name'    => $file->getClientFilename(),
//                            'size'    => $file->getSize(),
//                            'type'    => $file->getClientMediaType(),
//                            'content' => (string) $file->getStream()
//                        ],
//                        '/shipment' // Путь к папке в которой хранятся файлы (относительно папки /upload).
//                    );
//
//                    if($fileId) {
//                        $arFilesId[] = $fileId;
//                        $arProps['FILES'][] = \CFile::MakeFileArray($fileId, '/shipment');
//                    }
//                }
//            }
//
//            // добавляет запись в инфоблок
//            if($ElementId = $CIBlockElement->Add([
//                'MODIFIED_BY'    => 1, // элемент изменен текущим пользователем
//                'IBLOCK_SECTION_ID' => $id, // идентификатор секции, выбирается выше по коду
//                'IBLOCK_ID'      => \Environment::IBLOCK_ID_SHIPMENTS,
//                'PROPERTY_VALUES'=> $arProps,
//                'NAME'           => $arParsedBody['title'], // title сообщения
//                'ACTIVE'         => 'Y',            // активен
//                //'DETAIL_TEXT'    => $arParsedBody['comment'], // текст сообщения (Сопроводительный текст претензии)
//            ])){
//
//                $response->getBody()->write(json_encode([
//                    'response' => [
//                        'id' => $ElementId
//                    ],
//                    'error' => []
//                ]));
//
//            }else{
//                throw new \Exception($CIBlockElement->LAST_ERROR, 400);
//            }
//
//            // выбираем отгрузку
//            $CIBlockElement = \CIBlockElement::GetList(
//                [],
//                ['IBLOCK_ID' => \Environment::IBLOCK_ID_SHIPMENTS,'SECTION_ID ' => $id, 'ID' => $ElementId], false,false,['*']);
//
//            $element = $CIBlockElement->GetNextElement();
//            if($element){
//                $fields = $element->GetFields();
//                $props = $element->GetProperties();
//
//                $TwigLoader = new \Twig_Loader_Filesystem($_SERVER['DOCUMENT_ROOT'] . '/local/src/twig_templates/post');
//                $Twig = new \Twig_Environment($TwigLoader);
//                $template = $Twig->loadTemplate('Shipment.html');
//
//                $message = $template->render([
//                    'TITLE' => $fields['NAME'],
//                    'ORDER_ID' => $props['ORDER_ID']['VALUE'],
//                    'DATE_SHIPMENT' => $props['DATE_SHIPMENT']['VALUE'],
//                    'ADDRESS' => $props['ADDRESS']['VALUE'],
//                    'AMOUNT' => $props['AMOUNT']['VALUE'],
//                    'WEIGHT' => $props['WEIGHT']['VALUE'],
//                    'VOLUME' => $props['VOLUME']['VALUE'],
//                    'CASE' => $props['CASE']['VALUE'],
//                    'CARRIERS' => $props['CARRIERS']['VALUE'],
//                    'COMMENT' => $props['COMMENT']['VALUE'],
//                    'PARTNER_NAME' => $props['PARTNER_NAME']['~VALUE'],
//                ]);
//
//                // отсылаем почтовое сообщение
//                $this->SendMessage(
//                    'Новая заявка на отгрузку для ' . $arParsedBody['title'],
//                    $message,
//                    [$arUser['EMAIL']]
//                );
//            }
//
//        }catch(\Exception $e){
//            $response->getBody()->write(json_encode([
//                'response' => [],
//                'error' => [
//                    'code' => $e->getCode(),
//                    'message' => $e->getMessage(),
//                ]
//            ]));
//
//            return $response
//                ->withHeader('Content-Type', 'application/json')
//                ->withStatus($e->getCode());
//        }
//        return $response
//            ->withHeader('Content-Type', 'application/json')
//            ->withStatus(200);
    }

    /**
     * Добавить претензию
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function AddClaim(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface{
        try {
            /** @var array{id: int, config: int, sign: int} $tokenData Данные из токена
             *  id - идентификатор пользователя
             *  config - идентификатор элемента инфоблока конфигурации пользователя
             *  sign - кодовая подпись
             */
            $tokenData = $request->getAttribute('tokenData');
            \Bitrix\Main\Loader::includeModule('iblock');

            $rsUser = \CUser::GetByID((int)$tokenData['id']);
            /** @var array $arUser Данные пользователя */
            $arUser = $rsUser->Fetch();

            $CIBlockElement = \CIBlockElement::GetList(
                [],
                ['ID' => $tokenData['config']], false,false,['ID', 'NAME', 'PROPERTY_SHIPMENTS','PROPERTY_CLAIMS']);

            // получаем id раздела хранения претензий
            if($arElement = $CIBlockElement->Fetch())
                $id = $arElement['PROPERTY_CLAIMS_VALUE'];

            //region Создаем раздел, если он отсутствует
            if(!$id){
                // если id отсутствует создаем раздел
                global $USER;
                if( $arUser = $USER->GetByID((int)$tokenData['id'])->Fetch() ){

                    $name = $arUser['LAST_NAME'] . ' ' . $arUser['NAME'] . ' ' . $arUser['SECOND_NAME'] . ' #' . $arUser['ID'];
                }else{ throw new \Exception('Не найден пользователь по ID.',409); }

                $CIBlockSection = new \CIBlockSection;

                if($sectionId = $CIBlockSection->Add([
                    'IBLOCK_ID' => \Environment::IBLOCK_ID_CLAIMS,
                    'NAME' => $name,
                ]))
                {
                    // записываем id раздела
                    \CIBlockElement::SetPropertyValuesEx(
                        (int) $tokenData['config'],
                        \Environment::GetInstance()['iblocks']['Users'],
                        ['CLAIMS' => $sectionId],
                    );
                }else{ throw new \Exception('Не удается создать раздел для заявок.',409);}


            }
            //endregion

            // добавляем претензию в раздел
            //var_dump($id);
            //var_dump($request->getParsedBody());
            //var_dump($request->getUploadedFiles());

            /** @var array $arParsedBody Внешние данные
             *array(6) {
             *   ["title"]=>
             *   string(34) "Заказ № 192 от 10.03.2022"
             *   ["partner_name"]=>
             *   string(20) "ООО  Мастер"
             *   ["partner_guid"]=>
             *   string(36) "8152948b-ace6-11de-a660-0050569a3a91"
             *   ["id"]=>
             *   string(3) "148"
             *   ["case"]=>
             *   string(1) "3"
             *   ["products"]=>
             *   string(175) "{"guid":"5b0ea2b5-5109-11e3-9e4c-0025907c0298","characteristics":[{"guid":"5b0ea2bc-5109-11e3-9e4c-0025907c0298","orgguid":"b5e91d86-a58a-11e5-96ed-0025907c0298","quantity":1}"
             *   ["message"]=>
             *   string(62) "СОПРОВОДИТЕЛЬНЫЙ ТЕКСТ ПРЕТЕНЗИИ"
             *   }
             */
            $arParsedBody = $request->getParsedBody();

            $this->Monolog->debug('parsedBody',[$arParsedBody]);

            if(empty($arParsedBody))
                throw new \Exception('Request params is empty!', 400);

            /** @var \Slim\Psr7\UploadedFile[] $arUploadedFiles Массив с Файлами вложения, ключ 'files' или пустой массив */
            $arUploadedFiles = $request->getUploadedFiles();

            $this->Monolog->debug('uploadFiles',[$arUploadedFiles]);

            $CIBlockElement = new \CIBlockElement;

            // свойства элемента
            $arProps = [
                'PARTNER_NAME' => $arParsedBody['partner_name'], // Имя контрагента
                'PARTNER_GUID' => $arParsedBody['partner_guid'], // Идентификатор контрагента
                'ORDER_ID' => $arParsedBody['id'], // Идентификатор общего заказа в таблице заказов Битрикс
                //'CASE' => '', // Причина притензии. [0 - другое, 1 - недосдача, 2 - пересорт , 3 - качество ]
                'PRODUCTS' => $arParsedBody['products'], // Перечень товаров json строкой (просто чтобы было)
            ];

            //region claims status Причина притензии. [0 - другое, 1 - недосдача, 2 - пересорт , 3 - качество ]
            switch ((int)$arParsedBody['case']){
                case 1 : $arProps['CASE'] = 6; // код из битрикса
                    break;
                case 2 : $arProps['CASE'] = 7; // код из битрикса
                    break;
                case 3 : $arProps['CASE'] = 8; // код из битрикса
                    break;
                default: $arProps['CASE'] = 5; // код из битрикса
                    break;
            }
            //endregion
            /** @var array $arFilesId Массив с идентификаторами загруженных файлов */
            $arFilesId = [];

            // если присутствуют файлы
            if($arUploadedFiles){
                // $arUploadedFiles по ключу files - должен содержать массив с \Slim\Psr7\UploadedFile
                // {"files":[ {"Slim\\Psr7\\UploadedFile":[]}, {"Slim\\Psr7\\UploadedFile":[]} ] }
                /** @var \Slim\Psr7\UploadedFile $file */
                foreach ($arUploadedFiles['files'] as $file){
                    $fileId = \CFile::SaveFile(
                        [
                            'name'    => $file->getClientFilename(),
                            'size'    => $file->getSize(),
                            'type'    => $file->getClientMediaType(),
                            'content' => (string) $file->getStream()
                        ],
                        '/claims' // Путь к папке в которой хранятся файлы (относительно папки /upload).
                    );

                    if($fileId) {
                        $arFilesId[] = $fileId;
                        $arProps['FILES'][] = \CFile::MakeFileArray($fileId, '/claims');
                    }
                }
            }

            if($ClaimId = $CIBlockElement->Add([
                'MODIFIED_BY'    => 1, // элемент изменен текущим пользователем
                'IBLOCK_SECTION_ID' => $id, // идентификатор секции, выбирается выше по коду
                'IBLOCK_ID'      => \Environment::IBLOCK_ID_CLAIMS,
                'PROPERTY_VALUES'=> $arProps,
                'NAME'           => $arParsedBody['title'], // title сообщения
                'ACTIVE'         => 'Y',            // активен
                'DETAIL_TEXT'    => $arParsedBody['message'], // текст сообщения (Сопроводительный текст претензии)
                //'DETAIL_PICTURE' => CFile::MakeFileArray($_SERVER["DOCUMENT_ROOT"]."/image.gif")
            ])){

                $response->getBody()->write(json_encode([
                    'response' => [
                        'id' => $ClaimId
                    ],
                    'error' => []
                ]));

            }else{
                throw new \Exception($CIBlockElement->LAST_ERROR, 400);
            }

            // выбираем претензию
            if(\Configuration::GetInstance()::IsProduction()) {
                $CIBlockElement = \CIBlockElement::GetList(
                    [],
                    ['IBLOCK_ID' => \Environment::IBLOCK_ID_CLAIMS,'SECTION_ID ' => $id, 'ID' => $ClaimId], false,false,['*']);

                $element = $CIBlockElement->GetNextElement();
                if($element){
                    $fields = $element->GetFields();
                    $props = $element->GetProperties();

                    $TwigLoader = new \Twig_Loader_Filesystem($_SERVER['DOCUMENT_ROOT'] . '/local/src/twig_templates/post');
                    $Twig = new \Twig_Environment($TwigLoader);
                    $template = $Twig->loadTemplate('Claim.html');

                    $message = $template->render([
                        'TITLE' => $fields['~NAME'], // заголовок
                        'TEXT' => $fields['~DETAIL_TEXT'], // текст претензии
                        'PARTNER_NAME' => $props['PARTNER_NAME']['~VALUE'], // контрагент
                        'ORDER_ID' => $props['ORDER_ID']['VALUE'], // идентификатор главного заказа
                        'CASE' => $props['CASE']['VALUE'], // причина претензии
                    ]);

                    // отсылаем почтовое сообщение
                    $this->SendMessage(
                        'Добавлена новая претензия на ' . $arParsedBody['title'],
                        $message,
                        [$arUser['EMAIL']]
                    );
                }
            }


//            /** @var string $eventName Код почтового события */
//            $eventName = 'CLAIM_FORM';
//            /** @var int $formId Идентификатор почтовой формы */
//            $formId = 51;
//            /** @var  array $arEventFields Массив полей типа почтового события идентификатор которого задается в параметре event_type. */
//            $arEventFields = [
//                'AUTHOR' => $arParsedBody['title'],
//                'TEXT' => $arParsedBody['message']
//            ];
//
//            //if(empty($arFilesId)){
//                // если нет файлов
//                /** @see https://dev.1c-bitrix.ru/api_help/main/reference/cevent/sendimmediate.php */
//                //\Bitrix\Main\Mail\Event::SendImmediate($eventName,'s1',$arEventFields,'N',$formId);
//                \Bitrix\Main\Mail\Event::SendImmediate([
//                    'EVENT_NAME' => $eventName,
//                    'LID' => 's1',
//                    'C_FIELDS' => $arEventFields,
//                    'FILE' => $arFilesId,
//                    'MESSAGE_ID' => $formId
//                ]);
            //}else{
                // если есть файлы
                //\Bitrix\Main\Mail\Event::SendImmediate($eventName,'s1',$arEventFields,'N',$formId,$arFilesId);
            //}

        }catch(\Exception $e){
            $response->getBody()->write(json_encode([
                'response' => [],
                'error' => [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                ]
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($e->getCode());
        }
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Отправить почтовое сообщение
     * @param string $subject Тема письма
     * @param string $message Текст сообщения
     * @param array | null $args Массив дополнительных почтовых адресов. (почта менеджера, пользователя и т.д.)
     *
     * @throws \PHPMailer\PHPMailer\Exception
     * @return boolean
     */
    private function SendMessage(string $subject, string $message, array $args = null): bool{
        $PHPMailer = new \PHPMailer\PHPMailer\PHPMailer();
        //кодировка сообщения
        $PHPMailer->CharSet = 'UTF-8';

        // Настройки SMTP
        $PHPMailer->isSMTP();
        $PHPMailer->SMTPAuth = true;
        $PHPMailer->SMTPDebug = 0;

        $PHPMailer->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $PHPMailer->Host = 'smtp.spaceweb.ru';
        $PHPMailer->Port = 25;
        $PHPMailer->Username = 'lk.psk@devoops2.online';
        $PHPMailer->Password = '970aP6DUnN4Y';

        // От кого
        $PHPMailer->setFrom('lk.psk@devoops2.online', 'LK PSK');

        // Кому
        $PHPMailer->addAddress('lk.psk@devoops2.online', 'LK PSK');

        //дополнительные адреса, если есть
        if($args){
            foreach ($args as $email){
                if($email && is_string($email) ){
                    $PHPMailer->addAddress($email);
                }
            }
        }

        // Тема письма
        $PHPMailer->Subject = $subject;

        // Тело письма
        //$body = '<p><strong>«Hello, world!» </strong></p>';
        $PHPMailer->msgHTML($message);

        // Приложение
        //$PHPMailer->addAttachment(__DIR__ . '/image.jpg');

        // отправить
        return $PHPMailer->send();
    }
}