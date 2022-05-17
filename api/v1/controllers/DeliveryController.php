<?php

namespace API\v1\Controllers;
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/DeliveryPointEx.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/DeliveryPoint.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/managers/Delivery.php';

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Сlass DeliveryController
 * Контроллер для работы с доставкой
 *
 */
class DeliveryController
{
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
    }

    /**
     * Получить список адресов доставки, для авторизированного пользователя
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function GetAddressList(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface{
        $user = $request->getAttribute('tokenData');

        try{
            $Delivery = new \API\v1\Managers\Delivery($user);

            $response->getBody()->write(
                json_encode($Delivery->GetList())
            );

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);

        }catch (\Exception $e){
            $response->getBody()->write(
                json_encode([
                    'response' => [],
                    'error' => [
                        'code' => $e->getCode(),
                        'message' => $e->getMessage()
                    ]
                ])
            );

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($e->getCode());
        }
    }

    /**
     *  Добавить точку доставки
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function AddPoint(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface{

        try{

            $user = $request->getAttribute('tokenData');
            $content = $request->getBody()->getContents();

            if(!$content)
                throw new \Exception('Отсутсвуют данные', 400);

            $Point = new \API\v1\Models\DeliveryPointEx();
            $Delivery = new \API\v1\Managers\Delivery($user);

            $content = current(json_decode($content,true));

            $Point->address     = $content['address'];
            $Point->label       = $content['label'];
            $Point->longitude   = $content['longitude'];
            $Point->latitude    = $content['latitude'];

            $result = $Delivery->AddPoint($Point);

            $response->getBody()->write(json_encode(
                [
                    'response' => [
                        'status' => $result,
                        'data'   => $Delivery->GetList()
                    ],
                    'error' => []
                ]
            ));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);

        }catch (\Exception $e){
            $response->getBody()->write(
                json_encode([
                    'response' => [],
                    'error' => [
                        'code' => $e->getCode(),
                        'message' => $e->getMessage()
                    ]
                ])
            );

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($e->getCode());
        }
    }

    /**
     *  Обновить точку доставки
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function UpdatePoint(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface{

        try{

            $user = $request->getAttribute('tokenData');
            $content = $request->getBody()->getContents();

            if(!$content)
                throw new \Exception('Отсутсвуют данные', 400);

            $Point = new \API\v1\Models\DeliveryPointEx();
            $Delivery = new \API\v1\Managers\Delivery($user);

            $content = current(json_decode($content,true));
            $Point->address     = $content['address'];
            $Point->index       = $content['index'];
            $Point->label       = $content['label'];
            $Point->longitude   = $content['longitude'];
            $Point->latitude    = $content['latitude'];

            $result = $Delivery->UpdatePoint($Point);

            $response->getBody()->write(json_encode(
                [
                    'response' => [
                        'status' => $result,
                        'data'   => $Delivery->GetList()
                    ],
                    'error' => []
                ]
            ));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);

        }catch (\Exception $e){
            $response->getBody()->write(
                json_encode([
                    'response' => [],
                    'error' => [
                        'code' => $e->getCode(),
                        'message' => $e->getMessage()
                    ]
                ])
            );

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($e->getCode());
        }
    }

    /**
     * Удалить точку доставки
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function RemovePoint(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface{
        try{

            $user = $request->getAttribute('tokenData');
            $content = $request->getBody()->getContents();

            if(!$content)
                throw new \Exception('Отсутсвуют данные', 400);

            $Delivery = new \API\v1\Managers\Delivery($user);

            $content = current(json_decode($content,true));

            $result = $Delivery->DeletePoint((int) $content['index']);

            $response->getBody()->write(json_encode(
                [
                    'response' => [
                        'status' => $result,
                        'data'   => $Delivery->GetList()
                    ],
                    'error' => []
                ]
            ));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);

        }catch (\Exception $e){
            $response->getBody()->write(
                json_encode([
                    'response' => [],
                    'error' => [
                        'code' => $e->getCode(),
                        'message' => $e->getMessage()
                    ]
                ])
            );

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($e->getCode());
        }
    }
}