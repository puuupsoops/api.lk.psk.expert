<?php

namespace API\v1\Controllers;

require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

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

        $product = json_decode($Response->getBody()->getContents(),true);

        $Response1C = $Client->get('http://91.193.222.117:12380/stimul_test_maa/hs/ex/product/' . $product['PRODUCT']['UID']);

        $result = mb_substr(trim($Response1C->getBody()->getContents()), 2, -1);

        $offers = json_decode($result,true);

        $arOffers = [];

        foreach($offers['response'][0]['characteristics'] as $characteristic){
            $arOffers[] = [
                "ID" => 0,
                "CHARACTERISTIC"=> $characteristic['characteristic'],
                "RESIDUE"=> $characteristic['quantity'],
                "PRICE" => $characteristic['price'],
                "PPDATA"=> (string) $characteristic['quantitytowait'] . '/' . date("d.m.Y",strtotime($characteristic['datetowait']))
            ];
        }

        $product['OFFERS'] = $arOffers;

        $response->getBody()->write(
            json_encode($product)
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);

        $bHttpClient = new HttpClient();
        $bHttpClient->disableSslVerification();
        $url = 'https://psk.expert/test/product-page/ajax.php?QUERY='.$query.'&OPTION=' . $options;
        $result =  $bHttpClient->query('GET',$url);
        var_dump($result);
        var_dump($bHttpClient->getError());
        $product = 'памир';

        $Client = new Client();

        try{

            $Response = $Client->get('https://psk.expert/test/product-page/ajax.php',[
                'query' => [
                    'QUERY'     => $product,
                    'OPTION'    => '1'
                ],
                'verify' => false
            ]);

            var_dump($Response->getBody()->getContents());
        }
        catch(\Psr\Http\Client\ClientExceptionInterface $e){
            echo $e->getMessage();
        }

        try{
            $Response = $Client->get('https://psk.expert/test/product-page/ajax.php');

            var_dump($Response->getBody()->getContents());
        }
        catch(\Psr\Http\Client\ClientExceptionInterface $e){

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