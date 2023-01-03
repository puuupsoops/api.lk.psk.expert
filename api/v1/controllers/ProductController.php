<?php
namespace API\v1\Controllers;

require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use API\v1\Models\ErrorResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Bitrix\Main\Web\HttpClient;

class ProductController
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

    /**
     * Получение товара по Битрикс ID
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function GetById(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface{
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(400);
    }

    /**
     * Список товаров для строки поиска с сайта.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function SearchList(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface{
        /*
         * json [{
                    "article": "КОС604",
                    "name": "Костюм \"Страйк 2\" василек/т.синий"
                 },...]
        */
        try{

        $Client = new Client();
        $Request = $Client->get('https://psk.expert/test/api/dashboard/catalog_page/ajax_catalog_list.php');

        $data = $Request->getBody()->getContents();

        $response->getBody()->write($data);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);

        }catch(\GuzzleHttp\Exception\GuzzleException $e){
            $ErrorResponse = new ErrorResponse();
            $ErrorResponse->message = $e->getMessage();
            $ErrorResponse->code = $e->getCode();

            $response->getBody()->write($ErrorResponse->AsJSON());

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($e->getCode());
        }
    }

    /**
     * Поиск товара по ключевому слову
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function SearchByWord(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface{
        //флаг перенаправления на тестовую 1С
        $redirect1CTestDB = !\Configuration::GetInstance()::IsProduction();

        $params = $request->getQueryParams();

        $query = $params['QUERY'];
        $options = $params['OPTION'];

        $Client = new Client();

        $Response = $Client->get('https://psk.expert/test/product-page/ajax.php',[
            'query' => [
                'QUERY'     => $query,
                'OPTION'    => $options
            ],
            'verify' => false
        ]);

        /**
         * @var array Массив с данными о запрашиваемом товаре
         */
        $arProduct = json_decode($Response->getBody()->getContents(),true);

        // убираем в массиве с найденными значениями, значения с артикулами null
        $arProduct['FOUND'] =  array_values(array_filter($arProduct['FOUND'],function($value){
            if($value['ARTICLE'])
                return true;
            return false;
        }));

        if($arProduct['PRODUCT']['UID'] || $arProduct['FOUND'])
        {
            // если есть совпадения и UID продукта, обращаемся к 1С, актуализируем данные.
            if($redirect1CTestDB){
                $Response1C = $Client->get('http://91.193.222.117:12380/stimul_test_maa/hs/ex/product/' . $arProduct['PRODUCT']['UID'],[
                    //'auth' => ['OData', '11']
                ]);
            }else{
                $Response1C = $Client->get('http://10.68.5.205/StimulBitrix/hs/ex/product/' . $arProduct['PRODUCT']['UID'],[
                    'auth' => ['OData', '11']
                ]);
            }

            $result = mb_substr(trim($Response1C->getBody()->getContents()), 2, -1);

            /**
             * @var array Массив с актуальными данными характеристик (предложения в Битрикс) из базы 1С
             */
            $offers = json_decode($result,true);

            $arOffers = [];

            //region Фильтруем склады, исключаем ненужные
            foreach ($offers['response'][0]['characteristics'] as $key => $characteristic) {

                $offers['response'][0]['characteristics'][$key]['storages'] = array_values(
                    array_filter(
                        $characteristic['storages'],
                        function ($value) {
                            return !in_array($value['guid'],$this->arIDsStorageIgnore);
                        }
                    )
                );

            }
            //endregion

            //region Фильтруем позиции с guid 0000-0000-0000-0000 если присутствуют иные позиции. для СИЗ от 2022-11-08
            if(count($offers['response'][0]['characteristics']) > 1){
                $offers['response'][0]['characteristics'] = array_values(
                    array_filter(
                        $offers['response'][0]['characteristics'],
                        function ($value) {
                            if($value['guid'] === '00000000-0000-0000-0000-000000000000'){
                                return false;
                            }
                            return true;
                        }
                    )
                );
            }
            //endregion
            /*
            echo '<pre>';
            var_dump($offers['response'][0]['characteristics']);
            echo '</pre>';
            die();
            */
            /** @var int $current_time текущее время timestamp */
            $current_time = time();

            foreach($offers['response'][0]['characteristics'] as $characteristic){
                foreach ($arProduct['OFFERS'] as $offer){
                    if($characteristic['guid'] === $offer['GUID']) {

                        $date_to_wait = strtotime($characteristic['datetowait']);
                        /** @var string $date_fix
                         * 2022-11-23
                         * Если дата больше текущей, оставляем, если меньше, ставим ожидается
                         */
                        $date_fix = $date_to_wait > $current_time ? date("d.m.Y",$date_to_wait) : 'ожидается';

                        $ppdata = (string) $characteristic['quantitytowait'] . '/' . $date_fix;

                        /**
                         * В общем, от 23.03.2022 количество для RESIDUE берется, для
                         * одежды из склада: №1 Спецодежда Дубровки eba0e5be-fc57-11e3-8704-0025907c0298
                         * обуви из склада: №3 Обувь Дубровки ФРО   0c329eed-30a1-11e7-8fdb-0025907c0298
                         *
                         * от 06.09.2022 количество для RESIDUE дополнено логикой, для
                         * обуви суммируются склады:
                         * №3 Обувь (Дубровки) ФРО  0c329eed-30a1-11e7-8fdb-0025907c0298 (главные остатки для обуви)
                         * №3 Обувь (Дубровки)      f61480c8-fc57-11e3-8704-0025907c0298
                         *
                         * ORG-20 от 14.12.2022 временно плюсуем остатки из новых складов
                         * №1 Спецодежда (Химки) 7f40490b-6ee4-11ed-bb50-005056bb1249
                         *  №3 Обувь (Химки) ЭС  98f1def1-6ee4-11ed-bb50-005056bb1249
                         * №3 Обувь (Химки) ФРО a5a13418-6ee4-11ed-bb50-005056bb1249
                         */
                        $quantity = 0;
                        $reserved = 0;
                        foreach ($characteristic['storages'] as $storage){

                            //region Исключаем склады
                            // Магазин главный склад
                            //if($storage['guid'] === 'edcb8a4f-5fc8-11e7-8fdb-0025907c0298')
                            //    $quantity += 0;
                            // Обувь (Шеризон) ФРО
                            //if($storage['guid'] === '0e0dff55-b6fb-11eb-baa1-005056bb1249')
                            //    $quantity += 0;
                            // Обувь (Шеризон) ЭС
                            //if($storage['guid'] === 'cde3f7d7-bd61-11eb-baa3-005056bb1249')
                            //    $quantity += 0;
                            //endregion

                            if($storage['guid'] === '0c329eed-30a1-11e7-8fdb-0025907c0298' ||
                                $storage['guid'] === 'eba0e5be-fc57-11e3-8704-0025907c0298' ||
                                $storage['guid'] === 'f61480c8-fc57-11e3-8704-0025907c0298') {
                                $quantity += (int) $storage['quantity'];
                                $reserved += (int) $storage['reserved']; // плюсуем резервы
                            }

                            // плюсуем резервы и остатки с временных складов
                            if($storage['guid'] === '7f40490b-6ee4-11ed-bb50-005056bb1249' ||
                                $storage['guid'] === '98f1def1-6ee4-11ed-bb50-005056bb1249' ||
                                $storage['guid'] === 'a5a13418-6ee4-11ed-bb50-005056bb1249') {
                                $quantity += (int) $storage['quantity'];
                                $reserved += (int) $storage['reserved'];
                            }

//                            if($storage['guid'] === '0c329eed-30a1-11e7-8fdb-0025907c0298' ||
//                                $storage['guid'] === 'eba0e5be-fc57-11e3-8704-0025907c0298' ||
//                                $storage['guid'] === 'f61480c8-fc57-11e3-8704-0025907c0298') {
//                                $quantity += (int) $storage['quantity'];
//                                break;
//                            }
                        }

                        // позиция характеристики товара
                        $position = [
                            'ID' => $offer['ID'],
                            'GUID' => $characteristic['guid'],
                            'ORGGUID' => $offers['response'][0]['organization_guid'],
                            'CHARACTERISTIC'=> $characteristic['characteristic'],
                            //'RESIDUE'=> (int) $characteristic['quantity'], //+ (int) $characteristic['quantitytowait']
                            'RESIDUE'=> ($quantity ?? 'ожидается'), // доступно
                            'RESERVED' => $reserved, //$characteristic['reserved'],// в резерве
                            'PRICE' => $characteristic['price'],
                            'PPDATA'=> ((int) $characteristic['quantitytowait'] == 0 || $ppdata === '' ) ? 'ожидается' : $ppdata,
                            'MAX_DISCOUNT' => (float)$offers['response'][0]['max_discount'] ?? 0.0, // максимальная скидка
                            'STORAGES' => []
                        ];

                        // склады с характеристикой товара
                        $storages = [];

                        foreach ($characteristic['storages'] as $storage){
                            $storages[] = [
                                'NAME' => $storage['storage'], // наименование склада
                                'GUID' => $storage['guid'], // идентификатор XML
                                'WAIT' => $storage['quantitytowait'], // количество ожидаемой поставки
                                'SHOWCASE' => $storage['quantity'], // в наличии на складе
                            ];
                        }

                        if(!empty($storages))
                            $position['STORAGES'] = $storages;

                        if($quantity > 0) // если меньше нуля, не выводим в список. костыль от 2022-10-24
                            $arOffers[] = $position;
                        break;
                    }
                }
//            $arOffers[] = [
//                "ID" => 0,
//                "CHARACTERISTIC"=> $characteristic['characteristic'],
//                "RESIDUE"=> $characteristic['quantity'],
//                "PRICE" => $characteristic['price'],
//                "PPDATA"=> (string) $characteristic['quantitytowait'] . '/' . date("d.m.Y",strtotime($characteristic['datetowait']))
//            ];
            }

            $arProduct['OFFERS'] = $arOffers;

            //region Если товар без характеристики, добавляем характеристику из 1С guid: 00000000-0000-0000-0000-000000000000
            // обычно это для СИЗ
            // если отсутствуют характеристики
            if(empty($arProduct['OFFERS'])) {
                $position = [
                    'ID' => 0,
                    'GUID' => $offers['response'][0]['characteristics'][0]['guid'],
                    'ORGGUID' => $offers['response'][0]['organization_guid'],
                    'CHARACTERISTIC'=> $offers['response'][0]['characteristics'][0]['characteristic'],
                    //'RESIDUE'=> (int) $characteristic['quantity'], //+ (int) $characteristic['quantitytowait']
                    'PRICE' => $offers['response'][0]['characteristics'][0]['price'],
                    'MAX_DISCOUNT' => (float)$offers['response'][0]['max_discount'] ?? 0.0, // максимальная скидка
                    'STORAGES' => []
                ];

                /** @var string $siz_storage_guid  №2 СИЗ/Инвентарь (Дубровки) */
                $siz_storage_guid = '065f052d-fc58-11e3-8704-0025907c0298';
                // добавляем дополнительный СИЗ (временно) от 14.12.2022 №2 СИЗ (Химки)
                $siz_storage_guid_tmp = 'c2071226-6ee4-11ed-bb50-005056bb1249';

                foreach($offers['response'][0]['characteristics'][0]['storages'] as $storage){
                    if($storage['guid'] === $siz_storage_guid ||
                        $storage['guid'] === $siz_storage_guid_tmp ) {
                        $position['RESIDUE'] = $storage['quantity'] ?? 'ожидается';
                        $position['PPDATA'] = ( (int) $storage['quantitytowait'] == 0 ||
                            $offers['response'][0]['characteristics'][0]['datetowait'] === '' ) ?
                            'ожидается' : $offers['response'][0]['characteristics'][0]['datetowait'];

                        $position['STORAGES'] = [
                            'NAME' => $storage['storage'], // наименование склада
                            'GUID' => $storage['guid'], // идентификатор XML
                            'WAIT' => $storage['quantitytowait'], // количество ожидаемой поставки
                            'SHOWCASE' => $storage['quantity'], // в наличии на складе
                        ];
                        break;
                    }
                }

                $arProduct['OFFERS'][] = $position;
            }

            //endregion

            $response->getBody()->write(
                json_encode($arProduct)
            );

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        }else{
            $response->getBody()->write(
                json_encode([
                  'response' => [],
                  'error' => [
                      'code' => 404,
                      'message' => 'Результаты не найдены.'
                  ]
                ])
            );

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

    }

    private function GetByUID(string $uid){

        $Client = new Client();

        try{
            $Response = $Client->get('http://10.68.5.205/stimul_test_maa/hs/ex/product/' . $uid);
            json_decode($Response->getBody()->getContents())['response'];

        }catch(\Psr\Http\Client\ClientExceptionInterface $e){

        }
    }
}