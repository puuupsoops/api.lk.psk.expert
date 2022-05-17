<?php
namespace API\v1\Middleware;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Environment.php';

use Firebase\JWT\JWT;

/**
 * Прослойка авторизации API версии 1.0
 */
class AuthMiddleware
{
    public function __invoke(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Server\RequestHandlerInterface $handler
    ): \Psr\Http\Message\ResponseInterface {

        /**
         * Получение заголовков
         */
        $header = $request->getHeader('Authorization');

        /**
         * Данные о маршруте
         */
        $route = $request->getAttribute('route', '');

        # region Обработка пустого заголовка | header
        if(empty($header)){

            $response = new \Slim\Psr7\Response();

            $response->getBody()->write(json_encode([
                'message' => 'Unauthorized',
                'field'   => 'token',
            ]));

            $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);

            return $response;
        }
        # endregion

        $header = $request->getHeader('Authorization');
        if (preg_match('/Bearer\s+(.*)$/i', $header[0] ?? '', $matches)) {
            $token = $matches[1] ?? '';
        }

        try {
            $arAlgs    = ['HS256', 'HS512', 'HS384'];
            $tokenData = (array)JWT::decode($token ?? '', \Environment::JWT_PRIVATE_KEY, $arAlgs);

//            $request = $request->withAttribute('tokenAuthData', [
//                'username' => $tokenData['username'],
//                'password' => $tokenData['password']
//            ]);

            $request = $request->withAttribute('tokenAuthData', [
                'id' => $tokenData['id'],
                'sign' => $tokenData['sign']
            ]);

            $response = $handler->handle($request);

            return $response;
        } catch (Exception $exception) {

            $response = new \Slim\Psr7\Response();

            $response->getBody()->write(json_encode([
                'message' => 'Unauthorized. Broken token: ' . $exception->getMessage(),
                'field'   => 'token',
            ]));

            $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);

            return $response;
        }
    }
}