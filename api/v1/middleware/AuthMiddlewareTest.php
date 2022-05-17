<?php

namespace API\v1\Middleware;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Environment.php';

use Firebase\JWT\JWT;

class AuthMiddlewareTest
{
    public function __invoke(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Server\RequestHandlerInterface $handler
    ): \Psr\Http\Message\ResponseInterface
    {

        /**
         * Ключ авторизации.
         *
         * array(1) {
         *  [0]=>string(115) "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6Miwic2lnbiI6bnVsbH0.CY3YWWOTGTv_4tjREeQxVuht6R7tmsIQ0fkgajTd29Q"
         * }
         *
         * если нет заголовка авторизации: array(0) {}
         */
        $header = $request->getHeader('Authorization');

        /** Данные о маршруте прим: string(15) "/api/tmp/hidden" */
        $route = $request->getUri()->getPath();

        try{

            if (preg_match('/Bearer\s+(.*)$/i', $header[0] ?? '', $matches)) {
                /** @var string $token Строка с токеном */
                $token = $matches[1] ?? '';
            }

            // ошибка, если нет заготовка авторизации с ключом.
            if(!$token)
                throw new \Exception('Authorization header is empty. Отсутсвует заголовок авторизации.',401);

            $arAlgs    = ['HS256', 'HS512', 'HS384'];
            /** @var array $tokenData Массив с данными авторизации: ['id' => 1, 'config' => 'int id элемента с конфигурацией пользователя', 'sign' => 'код сессии битрикса'] */
            $tokenData = (array)JWT::decode($token ?? '', \Environment::JWT_PRIVATE_KEY, $arAlgs);

            // передаем расшифрованные данные в обработчик, устанавливаем аттрибут tokenData с массивом данных об авторизации пользователя.
            // !!! обработчик передаст данные по цепочки, следующему обработчику.
            return $handler->handle($request->withAttribute('tokenData',$tokenData));

        }catch(\Exception $e){
            // ловим исключения, если есть ошибка, возвращаем response сервера.
            $response = new \Slim\Psr7\Response();

            $response->getBody()->write(json_encode([
                'message' => 'Unauthorized. Broken token: ' . $e->getMessage(),
                'field'   => 'token',
            ]));

            $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($e->getCode());

            return $response;
        }

    }
}