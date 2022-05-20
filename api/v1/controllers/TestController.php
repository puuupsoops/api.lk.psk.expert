<?php

namespace API\v1\Controllers;
include_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

include_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/local/modules/psk.api/lib/DirectoryTable.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/managers/Partner.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/service/Postman.php';
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

class TestController
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
     * Добавить заказ
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function OrderAdd(
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
            // Реузальтат добавления записи общего заказа
            $CompleteOrderID = \Psk\Api\Orders\DirectoryTable::add([
                'ID' => (int)($orderRootID), // Номер общего заказа \Bitrix\Main\Entity\IntegerField
                'DATE' => new \Bitrix\Main\Type\DateTime(date('d.m.Y H:m:s')), // Дата заказа    \Bitrix\Main\Entity\DatetimeField(
                'PARTNER_GUID' => $partnerGUID, //Контрагент GUID \Bitrix\Main\Entity\StringField
                'PARTNER_NAME' => $partner['name'],// Контрагент Имя  \Bitrix\Main\Entity\StringField
                'STATUS' => '0', // Статус заказа    \Bitrix\Main\Entity\StringField
                'USER' => (int)$userId, // Идентификатор учетной записи пользователя в Битрикс \Bitrix\Main\Entity\IntegerField
                'LINKED' => serialize($arLinkerOrder), // Связанные заказы (массив ID записей в битрикс, сериализованное поле) \Bitrix\Main\Entity\StringField
                'COST' => (string)$requestData['total'], // Общая сумма заказа без скидки  \Bitrix\Main\Entity\StringField
                'POSITIONS' => json_encode($requestData), // Список позиций  \Bitrix\Main\Entity\StringField
                'DISCOUNT' => '0', // Скидка числом  \Bitrix\Main\Entity\StringField
            ]);

            $this->Monolog->debug('Добавлена запись общего заказа ID: ', [$CompleteOrderID->getId()]);
            //region подготовка данных для 1С и отправка в 1С
            $this->Monolog->info('Подготовка данных для 1С и отправка в 1С');
            //var_dump($arPositions);
            if($arPositions){
                $Products = [];

                foreach ($arPositions as $key => $position){
                    // GUID товара
                    $positionGUID = $position['guid'];
                    //массив характеристик товара
                    $arCharacteristics = $position['characteristics'];

                    // запрос позиции товара
                    $Response = $this->Client->get('http://10.68.5.205/StimulBitrix/hs/ex/product/' . $positionGUID,[
                        'auth' => ['OData', '11']
                    ]);
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
                                $characteristic['price'] = (float)$positionEx['price'] * (float)$characteristic['quantity']; // цена без учета скидки
                                // $characteristic['quantity'] - есть по умолчанию количество позиций
                                $characteristic['total'] = $characteristic['price']; // сумма с учётом скидки
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
                                break;
                            }
                        }
                    }

                    $Products[] = $arCharacteristics;
                }

                $arPositions = $Products;
            }

            if($arPositionsPre){
                $Products = [];

                foreach ($arPositionsPre as $key => $position){
                    // GUID товара
                    $positionGUID = $position['guid'];
                    //массив характеристик товара
                    $arCharacteristics = $position['characteristics'];

                    // запрос позиции товара
                    $Response = $this->Client->get('http://10.68.5.205/StimulBitrix/hs/ex/product/' . $positionGUID,[
                        'auth' => ['OData', '11']
                    ]);
                    $result = mb_substr(trim($Response->getBody()->getContents()), 2, -1);
                    // модель позиции товара из 1С
                    $extendPosition = current(json_decode($result,true)['response']);

                    $arPositionsPre[$key]['organization_guid'] = $extendPosition['organization_guid'];
                    $arPositionsPre[$key]['name'] = $extendPosition['product'];

                    foreach($arCharacteristics as &$characteristic){
                        foreach ($extendPosition['characteristics'] as $positionEx){
                            if($characteristic['guid'] === $positionEx['guid']){

                                $characteristic['productid'] = $position['guid'];// UID товара/номенклатуры
                                $characteristic['characteristicsid'] = $characteristic['guid'];// UID характеристики товара
                                $characteristic['price'] = (float)$positionEx['price'] * (float)$characteristic['quantity']; // цена без учета скидки
                                // $characteristic['quantity'] - есть по умолчанию количество позиций
                                $characteristic['total'] = $characteristic['price']; // сумма с учётом скидки
                                $characteristic['discount'] = 0; // скидка (числом)
                                $characteristic['storage'] = current($positionEx['storages'])['guid'];

                                unset($characteristic['guid']);
                                unset($characteristic['orgguid']);
                                break;
                            }
                        }
                    }

                    $Products[] = $arCharacteristics;
                }

                $arPositionsPre = $Products;
            }

            if($arShoesPositions){
                $Products = [];

                foreach ($arShoesPositions as $key => $position){
                    // GUID товара
                    $positionGUID = $position['guid'];
                    //массив характеристик товара
                    $arCharacteristics = $position['characteristics'];

                    // запрос позиции товара
                    $Response = $this->Client->get('http://10.68.5.205/StimulBitrix/hs/ex/product/' . $positionGUID,[
                        'auth' => ['OData', '11']
                    ]);
                    $result = mb_substr(trim($Response->getBody()->getContents()), 2, -1);
                    // модель позиции товара из 1С
                    $extendPosition = current(json_decode($result,true)['response']);

                    $arShoesPositions[$key]['organization_guid'] = $extendPosition['organization_guid'];
                    $arShoesPositions[$key]['name'] = $extendPosition['product'];

                    foreach($arCharacteristics as &$characteristic){
                        foreach ($extendPosition['characteristics'] as $positionEx){
                            if($characteristic['guid'] === $positionEx['guid']){

                                $characteristic['productid'] = $position['guid'];// UID товара/номенклатуры
                                $characteristic['characteristicsid'] = $characteristic['guid'];// UID характеристики товара
                                $characteristic['price'] = (float)$positionEx['price'] * (float)$characteristic['quantity']; // цена без учета скидки
                                // $characteristic['quantity'] - есть по умолчанию количество позиций
                                $characteristic['total'] = $characteristic['price']; // сумма с учётом скидки
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
                                break;
                            }
                        }
                    }

                    $Products[] = $arCharacteristics;
                }

                $arShoesPositions = $Products;
            }

            if($arShoesPositionsPre){
                $Products = [];

                foreach ($arShoesPositionsPre as $key => $position){
                    // GUID товара
                    $positionGUID = $position['guid'];
                    //массив характеристик товара
                    $arCharacteristics = $position['characteristics'];

                    // запрос позиции товара
                    $Response = $this->Client->get('http://10.68.5.205/StimulBitrix/hs/ex/product/' . $positionGUID,[
                        'auth' => ['OData', '11']
                    ]);
                    $result = mb_substr(trim($Response->getBody()->getContents()), 2, -1);
                    // модель позиции товара из 1С
                    $extendPosition = current(json_decode($result,true)['response']);

                    $arShoesPositionsPre[$key]['organization_guid'] = $extendPosition['organization_guid'];
                    $arShoesPositionsPre[$key]['name'] = $extendPosition['product'];

                    foreach($arCharacteristics as &$characteristic){
                        foreach ($extendPosition['characteristics'] as $positionEx){
                            if($characteristic['guid'] === $positionEx['guid']){

                                $characteristic['productid'] = $position['guid'];// UID товара/номенклатуры
                                $characteristic['characteristicsid'] = $characteristic['guid'];// UID характеристики товара
                                $characteristic['price'] = (float)$positionEx['price'] * (float)$characteristic['quantity']; // цена без учета скидки
                                // $characteristic['quantity'] - есть по умолчанию количество позиций
                                $characteristic['total'] = $characteristic['price']; // сумма с учётом скидки
                                $characteristic['discount'] = 0; // скидка (числом)
                                $characteristic['storage'] = current($positionEx['storages'])['guid']; // UID склада

                                unset($characteristic['guid']);
                                unset($characteristic['orgguid']);
                                break;
                            }
                        }
                    }

                    $Products[] = $arCharacteristics;
                }

                $arShoesPositionsPre = $Products;
            }

            //var_dump($arPositions);
            //var_dump($arPositionsPre);
            //var_dump($arShoesPositions);
            //var_dump($arShoesPositionsPre);

            //массив с заказами
            $orders = [];

            if($arPositions){
                $total = 0;

                foreach (current($arPositions) as $position){
                    $total += ($position['total']) ?? 0;
                }

                $orders[] = [
                    'id' => $arLinkerOrder['positions'],
                    'organizationid' => $this->SPEC_ODA_ID, // UID фабрики, обувь или одежда
                    'contractid' => '', // UID договора с контрагентом (пока что береться на стороне 1С первый попавшийся)
                    'total' => $total, // сумма заказа с учетом скидки
                    'products' => current($arPositions)
                ];
            }

            if($arPositionsPre){
                $total = 0;

                foreach (current($arPositionsPre) as $position){
                    $total+= ($position['total']) ?? 0;
                }

                $orders[] = [
                    'id' => $arLinkerOrder['positions_pre'],
                    'organizationid' => $this->SPEC_ODA_ID, // UID фабрики, обувь или одежда
                    'contractid' => '', // UID договора с контрагентом (пока что береться на стороне 1С первый попавшийся)
                    'total' => $total, // сумма заказа с учетом скидки
                    'products' => current($arPositionsPre)
                ];
            }

            if($arShoesPositions){
                $total = 0;

                foreach (current($arShoesPositions) as $position){
                    $total+= ($position['total']) ?? 0;
                }

                $orders[] = [
                    'id' => $arLinkerOrder['shoes'],
                    'organizationid' => $this->WORK_SHOES_ID, // UID фабрики, обувь или одежда
                    'contractid' => '', // UID договора с контрагентом (пока что береться на стороне 1С первый попавшийся)
                    'total' => $total, // сумма заказа с учетом скидки
                    'products' => current($arShoesPositions)
                ];
            }

            if($arShoesPositionsPre){
                $total = 0;

                foreach (current($arShoesPositionsPre) as $position){
                    $total+= ($position['total']) ?? 0;
                }

                $orders[] = [
                    'id' => $arLinkerOrder['shoes_pre'],
                    'organizationid' => $this->WORK_SHOES_ID, // UID фабрики, обувь или одежда
                    'contractid' => '', // UID договора с контрагентом (пока что береться на стороне 1С первый попавшийся)
                    'total' => $total, // сумма заказа с учетом скидки
                    'products' => current($arShoesPositionsPre)
                ];
            }


            $Response1CData = [
                'id' => $CompleteOrderID->getId(), // идентификатор общего заказа
                'data' => date('d.m.Y'), // дата заказа в формате d.m.Y 22.01.2022
                'user' => $userId, // идентификатор записи пользователя в Битрикс
                'partnerID' => $partnerGUID, // контрагент
                //'total' => $requestData['total'], // итоговая стоимость
                'orders' => $orders
            ];

            //endregion

            $this->Monolog->debug('Модель для 1С',[$Response1CData]);

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
            $this->Monolog->warning('Поймано исключение \Exception',['msg' => $e->getMessage(), 'code' =>$e->getCode()] );

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

        try {
            //region Отправка в 1С
            $Response1C = $this->Client->post('http://10.68.5.205/StimulBitrix/hs/ex/order/add',[
                'auth' => ['OData', '11'],
                'json' => json_encode($Response1CData)]
            );

            $body_response = json_decode(mb_substr(trim($Response1C->getBody()->getContents()), 2, -1),true);
            $code_response = $Response1C->getStatusCode();
            //endregion

            //var_dump(current($body_response['response'])['guid']);

            $this->Monolog->debug('Ответ от 1С, создание заказа', ['body' => $body_response, 'code' => $code_response]);

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
                'STATUS' => (string) \API\v1\Models\Registers\OrderStatus::waiting
            ]);
        }catch (\Exception $e){
            // исключение на добавление заказа

            $this->Monolog->warning('Поймано исключение при попытки создания заказа в 1С \Exception',['msg' => $e->getMessage(), 'code' =>$e->getCode()] );

            \Psk\Api\Orders\DirectoryTable::update($CompleteOrderID->getId(),[
                'ID' => null,
            ]);

            $this->Monolog->debug('Модель ответа от сервера',[
                'response' => [
                    'id' => (int) $CompleteOrderID->getId(),
                    'message' => 'Заказ: №' . $CompleteOrderID->getId() . ' зарегистрирован.'
                ],
                'error' => []
            ]);


            $response->getBody()->write(json_encode([
                'response' => [
                    'id' => (int) $CompleteOrderID->getId(),
                    'message' => 'Заказ: №' . $CompleteOrderID->getId() . ' зарегистрирован.'
                ],
                'error' => []
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        }

        $this->Monolog->debug('Модель ответа от сервера',[
            'response' => [
                'id' => (int) $CompleteOrderID->getId(),
                'message' => 'Заказ: №' . $CompleteOrderID->getId() . ' зарегистрирован.'
            ],
            'error' => []
        ]);

        $response->getBody()->write(json_encode([
            'response' => [
                'id' => (int) $CompleteOrderID->getId(),
                'message' => 'Заказ: №' . $CompleteOrderID->getId() . ' зарегистрирован.'
            ],
            'error' => []
        ]));

        //region Отправляем почтовое сообщение
        $Postman = new \API\v1\Service\Postman();

        try {
            $rsUser = \CUser::GetByID((int)$userId);
            /** @var array $arUser Данные пользователя */
            $arUser = $rsUser->Fetch();

            $Postman->SendMessage(
                $arUser['EMAIL'],
                'Заказ: №' . $orderRootID . ' зарегистрирован.'
            );
        }catch (\PHPMailer\PHPMailer\Exception $e){
            $this->Monolog->error('Ошибка отправки почтового сообщения',[
                'message'   => $e->getMessage(),
                'code'      => $e->getCode()
            ]);
        }

        //endregion

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);

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
     * Создать позицию заказа в битрикс
     *
     * @param array $position   Данные с позициями заказа
     * @return int              Идентификатор элемента инфоблока в битрикс
     */
    private function createOrderElement(array $position): int{



        return 0;
    }
}