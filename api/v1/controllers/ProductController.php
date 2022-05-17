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
            $Response1C = $Client->get('http://91.193.222.117:12380/stimul_test_maa/hs/ex/product/' . $arProduct['PRODUCT']['UID'],[
                'auth' => ['OData', '11']
            ]);

            $result = mb_substr(trim($Response1C->getBody()->getContents()), 2, -1);

            /**
             * @var array Массив с актуальными данными характеристик (предложения в Битрикс) из базы 1С
             */
            $offers = json_decode($result,true);

            $arOffers = [];

            foreach($offers['response'][0]['characteristics'] as $characteristic){
                foreach ($arProduct['OFFERS'] as $offer){
                    if($characteristic['guid'] === $offer['GUID']){

                        $ppdata = (string) $characteristic['quantitytowait'] . '/' . date("d.m.Y",strtotime($characteristic['datetowait']));

                        /**
                         * В общем, от 23.03.2022 количество для RESIDUE берется, для
                         * одежды из склада: №1 Спецодежда Дубровки eba0e5be-fc57-11e3-8704-0025907c0298
                         * обуви из склада: №3 Обувь Дубровки ФРО 0c329eed-30a1-11e7-8fdb-0025907c0298
                         */
                        $quantity = 0;

                        foreach ($characteristic['storages'] as $storage){
                            if($storage['guid'] === '0c329eed-30a1-11e7-8fdb-0025907c0298' || $storage['guid'] === 'eba0e5be-fc57-11e3-8704-0025907c0298'){
                                $quantity = (int) $storage['quantity'];
                                break;
                            }
                        }

                        // позиция характеристики товара
                        $position = [
                            'ID' => $offer['ID'],
                            'GUID' => $characteristic['guid'],
                            'ORGGUID' => $offers['response'][0]['organization_guid'],
                            'CHARACTERISTIC'=> $characteristic['characteristic'],
                            //'RESIDUE'=> (int) $characteristic['quantity'], //+ (int) $characteristic['quantitytowait']
                            'RESIDUE'=> $quantity ?? 'ожидается',
                            'PRICE' => $characteristic['price'],
                            'PPDATA'=> ((int) $characteristic['quantitytowait'] == 0 || $ppdata === '' ) ? 'ожидается' : $ppdata,
                            'STORAGES' => []
                        ];

                        // склады с характеристикой товара
                        $storages = [];

                        foreach ($characteristic['storages'] as $storage){
                            $storages[] = [
                                'NAME' => $storage['storage'], // наименование склада
                                'GUID' => $storage['guid'], // идентификатор XML
                                'WAIT' => $storage['quantitytowait'], // количество ожидаемой поставки
                                'SHOWCASE' => $storage['quantity'] // в наличии на складе
                            ];
                        }

                        if(!empty($storages))
                            $position['STORAGES'] = $storages;

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