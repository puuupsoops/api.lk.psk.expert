<?php
require_once 'controllers/PartnerController.php';
require_once 'controllers/UserController.php';
require_once 'controllers/ProductController.php';

require_once 'middleware/AuthMiddleware.php';

use Slim\Routing\RouteCollectorProxy;

/**
 * @var RouteCollectorProxy $group
 */

$group->group(
    '/test',
    function (RouteCollectorProxy $group) {
        $group->get('/', function (Psr\Http\Message\ServerRequestInterface $request,Psr\Http\Message\ResponseInterface  $response, array $args) {

            $response->getBody()->write(json_encode("Test Hello"));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);;
        });
    }
);

# Получить данные по товару
$group->group(
    '/product',
    function (RouteCollectorProxy $group){

        # поиск позиции
        $group->get(
            '/search',
            \API\v1\Controllers\ProductController::class . ':SearchByWord'
        );

        # по bitrix-id
        $group->get(
            '/{id}',
            \API\v1\Controllers\ProductController::class . ':GetById'
        );

    }
);
# Получить данные по менеджеру
$group->get(
    '/manager',
    \API\v1\Controllers\PartnerController::class . ':Manager'
);

# Авторизация пользователя c обработкой JWT токена
$group->post(
    '/auth',
    \API\v1\Controllers\UserController::class . ':Authorization'
);
#->add(new AuthMiddleware());

$group->group(
    '/partner',
    function (RouteCollectorProxy $group) {

        $group->get(
            '/{id}',
            \API\v1\Controllers\PartnerController::class . ':GetByGUID'
        );

    }
);

$group->get(
    '/partners',
    \API\v1\Controllers\PartnerController::class . ':Partners'
);