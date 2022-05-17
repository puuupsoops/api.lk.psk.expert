<?php

namespace API\v1\Controllers;

include_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use GuzzleHttp\Client;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ProxyController
{
    /**
     * @var \GuzzleHttp\Client Client
     */
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
     * Получить статус готовновсти печатных форм
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function ProxyOrderPrintingStatus(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface{
        $externalDataString = $request->getBody()->getContents();

        try{
            $Response1C = $this->Client->get('http://91.193.222.117:12380/stimul_test_maa/hs/ex/order/statusprint',[
                'auth' => ['OData', '11'],
                'json' => $externalDataString
            ]);

            //$body = json_decode(mb_substr(trim(), 2, -1));
            $contents = $Response1C->getBody()->getContents();
            $response->getBody()->write($contents);

            var_dump($contents);

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
     * Получить печатную форму
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function ProxyOrderPrinting(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface{

        $externalDataString = $request->getBody()->getContents();

        try{
            $Response1C = $this->Client->getAsync('http://91.193.222.117:12380/stimul_test_maa/hs/ex/order/printing',[
                'auth' => ['OData', '11'],
                'json' => $externalDataString
            ]);
            $Response1C = $Response1C->wait();

            $bodyContents = $Response1C->getBody()->getContents();
            $bodyContents = json_decode(mb_substr(trim($bodyContents), 2, -1),true);

//            $response->getBody()->write(json_encode([
//                'code' => $Response1C->getStatusCode(),
//                'bodyContents' => $bodyContents
//            ]));

            $data = base64_decode(current(current($bodyContents['response'])['PrintingForms'])['PrintForm']);

            $response->getBody()->write($data);

            return $response
                ->withHeader('Content-Type', 'application/pdf')
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
     * Передать данные заказа в 1С
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function ProxySendOrderData(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface{

        $externalData = json_decode($request->getBody()->getContents());

        try{
            $Response1C = $this->Client->post('http://91.193.222.117:12380/stimul_test_maa/hs/ex/order/add',[
                'auth' => ['OData', '11'],
                'json' => json_encode($externalData)
            ]);

            $bodyContents = $Response1C->getBody()->getContents();
            $bodyContents = json_decode(mb_substr(trim($bodyContents), 2, -1),true);

            $response->getBody()->write(json_encode([
                'code' => $Response1C->getStatusCode(),
                'bodyContents' => $bodyContents
            ]));

//        $response->getBody()->write(json_encode($externalData));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
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
     * Получить контрагента по идентификатору
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function GetPartnerById(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface{

        $id = $args['id'];

        try{
            $Response1C = $this->Client->get('http://91.193.222.117:12380/stimul_test_maa/hs/ex/partner/'.$id.'/',[
                'auth' => ['OData', '11']
            ]);
            $body = json_decode(mb_substr(trim($Response1C->getBody()->getContents()), 2, -1));

            $response->getBody()->write(json_encode([
                'code' => $Response1C->getStatusCode(),
                'bodyContents' => $body
            ]));

//        $response->getBody()->write(json_encode($externalData));

            return $response
                ->withHeader('Content-Type', 'application/json')
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

}