<?php
namespace API\v1\Controllers;

include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/managers/User.php';

include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/responses/Responses.php';

use API\v1\Managers\User;
use API\v1\Models\Response;
use Firebase\JWT\JWT;
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
        #$requestData = $request->getAttribute('tokenAuthData');

        /**
         * @var string Логин
         */
        $login = $request->getParsedBody()['login'];

        /**
         * @var string Пароль
         */
        $password = $request->getParsedBody()['password'];

        try{

            /**
             * @var User Класс менеджер пользователей
             */
            $User = new User(["username" => $login, "password" => $password]);

        }catch(\Exception $e){
            return ErrorResponse($e,$response);
        }

        $responseData = $User->GetPass();

        # Формируем ответ
        $Response = new Response();
        $Response->data = [
            "token" => JWT::encode([
                'id'    =>  $responseData['id'],
                'sign'  =>  $responseData['sign']
            ],\Environment::JWT_PRIVATE_KEY,'HS256')
        ];
        $Response->code = 200;
        
        $response->getBody()->write(
            $Response->AsJSON()
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($Response->code);
    }
}