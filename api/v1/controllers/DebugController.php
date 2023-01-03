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

class DebugController
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
     * constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $this->Client = new Client();
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
    public function AddOrderExtend(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface{

        //region Конфигурируем структуру заказа под битрикс и сохраняем его в общей таблице заказов
        try{
            //die(123);
            //флаг перенаправления на тестовую 1С
            $redirect1CTestDB = !\Configuration::GetInstance()::IsProduction();

            /**
             * @var \API\v1\Models\Token Модель данных из токена авторизации
             */
            $Token = new \API\v1\Models\Token($request->getAttribute('tokenData'));
            print_r('Токен:' . PHP_EOL);
            var_dump($Token);

            $contents = $request->getBody()->getContents();
            /**
             * @var array Массив с данными о заказе
             */
            $requestData = json_decode($contents,true);

            //var_dump( (new \API\v1\Models\Order\Order($requestData))->AsArray() );

            /** @var \API\v1\Models\Order\Order $Order Модель заказа из личного кабинета */
            $Order = new \API\v1\Models\Order\Order($requestData);

            print_r('Модель заказа из личного кабинета:' . PHP_EOL);
            var_dump($Order);

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
            unset($position);
            //endregion
            //var_dump($Order);
            //die();

            $Partner = new \API\v1\Managers\Partner();
            $Partner = $Partner->GetByGUID($Order->GetPartnerId());
            $partner = $Partner->AsArray();
            print_r('Контрагент, как массив:' . PHP_EOL);
            var_dump($partner);

            //var_dump($partner);

            /** @var \API\v1\Managers\Order Репозиторий для работы с заказом */
            $OrderManager = new \API\v1\Managers\Order($Order);

            print_r('Инициализация: Репозиторий для работы с заказом :' . PHP_EOL);

            print_r('Позиции:' . PHP_EOL);
            var_dump($OrderManager->arPositions);

            print_r('(предзаказ) Позиции:' . PHP_EOL);
            var_dump($OrderManager->arPositionsPre);

            print_r(' Позиции с обувью:' . PHP_EOL);
            var_dump($OrderManager->arShoesPositions);

            print_r('(предзаказ) Позиции с обувью :' . PHP_EOL);
            var_dump($OrderManager->arShoesPositionsPre);


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
//            $arLinkerOrder = $OrderManager->WritePositionStackInBitrixDB(
//                (string) $Order->GetId() . (string) $Token->GetId(),
//                (string) $Token->GetId()
//            );

            //var_dump($arLinkerOrder);

            // Реузальтат добавления записи общего заказа
//            $CompleteOrderID = \Psk\Api\Orders\DirectoryTable::add([
//                'ID' => (int)((string) $Order->GetId() . (string) $Token->GetId()), // Номер общего заказа \Bitrix\Main\Entity\IntegerField
//                'DATE' => new \Bitrix\Main\Type\DateTime(date('d.m.Y H:m:s')), // Дата заказа    \Bitrix\Main\Entity\DatetimeField(
//                'PARTNER_GUID' => $Order->GetPartnerId(), //Контрагент GUID \Bitrix\Main\Entity\StringField
//                'PARTNER_NAME' => $partner['name'],// Контрагент Имя  \Bitrix\Main\Entity\StringField
//                'STATUS' => '0', // Статус заказа    \Bitrix\Main\Entity\StringField
//                'USER' => $Token->GetId(), // Идентификатор учетной записи пользователя в Битрикс \Bitrix\Main\Entity\IntegerField
//                'LINKED' => serialize($arLinkerOrder), // Связанные заказы (массив ID записей в битрикс, сериализованное поле) \Bitrix\Main\Entity\StringField
//                'COST' => (string)$Order->GetTotal(), // Общая сумма заказа без скидки  \Bitrix\Main\Entity\StringField
//                'POSITIONS' => json_encode($requestData), // Список позиций  \Bitrix\Main\Entity\StringField
//                'DISCOUNT' => '0', // Скидка числом  \Bitrix\Main\Entity\StringField
//                'RESERVE' => (int) $Order->IsReserved() , // для зарезервированного типа заказа 1 | 0 \Bitrix\Main\Entity\IntegerField
//                'EDITABLE' => (int) $Order->IsEdit(), // доступен для редактирования 1 | 0 \Bitrix\Main\Entity\IntegerField
//                'SHIPMENT_COST' => (string)$Order->GetDelivery()->GetCost() ?? '' // Стоимость отгрузки, если ДРУГАЯ ТРАНСПОРТНАЯ = 900 , остальные = 0
//            ]);

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

            $request_certificate = true; // Добавить сертификат true | false
            $comment = 'комментарий';
            $delivery_terms = 'отгрузка';

            // товары
            if($OrderManager->arPositions) {
                $arPositions = [];
                foreach ($OrderManager->arPositions as $value){
                    $arPositions[] = $value->AsArray();
                }
                //var_dump($arPositions);

                $Products = [];

                foreach ($arPositions as $key => $position) {
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
                unset($position);

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
                unset($position);
                //$arPositions = $Products;

                $arOrders1C[] = [
                    'reserved' => $Order->IsReserved(), //флаг резерва
                    'request_certificate' => $request_certificate, // Добавить сертификат true | false
                    'comment' => $comment, // Комментарий
                    'delivery_terms' => $delivery_terms, // Название отгрузки
                    'id' => 1,
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

                foreach ($arPositions as $key => $position) {
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
                unset($position);

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
                foreach ($products as &$position){
                    //var_dump($position);
                    $position['total'] = $position['quantity'] * $position['price'];
                    $total += ($position['total']) ?? 0;
                }
                unset($position);
                //$arPositions = $Products;
                $arOrders1C[] = [
                    'reserved' => $Order->IsReserved(), //флаг резерва
                    'request_certificate' => $request_certificate, // Добавить сертификат true | false
                    'comment' => $comment, // Комментарий
                    'delivery_terms' => $delivery_terms, // Название отгрузки
                    'id' => 2,
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
                    foreach ($this->RESIDUE_SHOES as &$position){
                        //var_dump($position);
                        $total += ($position['total']) ?? 0;
                    }
                    //unset($position);

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
                    $arLinkerOrder['shoes_expert'] = 123321;

                    // создать для 1С со СпецОдеждой
                    $arOrders1C[] = [
                        'reserved' => $Order->IsReserved(), //флаг резерва
                        'request_certificate' => $request_certificate, // Добавить сертификат true | false
                        'comment' => $comment, // Комментарий
                        'delivery_terms' => $delivery_terms, // Название отгрузки
                        'id' => 2,
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
                unset($position);

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
                //unset($position);

                //$arPositions = $Products;
                $arOrders1C[] = [
                    'reserved' => false, //флаг резерва
                    'request_certificate' => $request_certificate, // Добавить сертификат true | false
                    'comment' => $comment, // Комментарий
                    'delivery_terms' => $delivery_terms, // Название отгрузки
                    'id' => 3,
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
            if($OrderManager->arShoesPositionsPre) {
                $arPositions = [];
                foreach ($OrderManager->arShoesPositionsPre as $value){
                    $arPositions[] = $value->AsArray();
                }
                //var_dump($arPositions);

                $Products = [];

                foreach ($arPositions as $key => $position) {
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
                //unset($position);

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
                //unset($position);

                //$arPositions = $Products;
                $arOrders1C[] = [
                    'reserved' => false, //флаг резерва
                    'request_certificate' => $request_certificate, // Добавить сертификат true | false
                    'comment' => $comment, // Комментарий
                    'delivery_terms' => $delivery_terms, // Название отгрузки
                    'id' => 4,
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
                    foreach ($arOrders1C as &$order){
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
                }

            }
            //endregion

            $Response1CData = [
                'edit' => true,
                'id' => 123321, // идентификатор общего заказа
                'data' => date('d.m.Y'), // дата заказа в формате d.m.Y 22.01.2022
                'user' => $Token->GetId(), // идентификатор записи пользователя в Битрикс
                'partnerID' => $Order->GetPartnerId(), // контрагент
                //'total' => $requestData['total'], // итоговая стоимость
                'orders' => $arOrders1C
            ];

            print_r('Данные для 1С :' . PHP_EOL);
            var_dump($Response1CData);
            die();

            //$response->getBody()->write(json_encode($Response1CData));
            //$response->getBody()->write(json_encode($Response1CData));

        }catch (\GuzzleHttp\Exception\GuzzleException $e){
            // исключение на позицию товара

            print_r('исключение на позицию товара :' . PHP_EOL);
            var_dump($e);
            die();

            $response->getBody()->write(json_encode([
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ]));


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

            foreach($body_response['response'] as $item){

                foreach ($Response1CData['orders'] as $data){

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

            \Psk\Api\Orders\DirectoryTable::update($CompleteOrderID->getId(),[
                'ID' => null,
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


        //region Формируем тело ответа
        $response->getBody()->write(json_encode([
            'response' => [
                'id' => (int) $CompleteOrderID->getId(),
                'message' => 'Заказ: №' . $CompleteOrderID->getId() . ' зарегистрирован.'
            ],
            'error' => []
        ]));
        //endregion

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
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


}