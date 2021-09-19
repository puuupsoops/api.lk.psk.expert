<?php
namespace API\v1\Controllers;

include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/managers/User.php';

use API\v1\Managers\User;
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
     * constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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

        /**
         * @var array{username: string, password: string} $requestData Массив с данными авторизации пользователя
         */
        $requestData = $request->getAttribute('tokenAuthData');

        try{

            /**
             * @var User Класс менеджер пользователей
             */
            $User = new User($requestData);

        }catch(\Exception $e){
            $response->getBody()->write(
                json_encode([
                    'error' => $e->getMessage()
                ])
            );

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($e->getCode());
        }

        $responseData = $User->GetPass();

        $response->getBody()->write(
            json_encode($responseData)
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}