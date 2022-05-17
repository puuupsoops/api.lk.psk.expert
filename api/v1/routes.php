<?php
include_once 'controllers/PartnerController.php';
include_once 'controllers/UserController.php';
include_once 'controllers/ProductController.php';
include_once 'controllers/OrderController.php';
include_once 'controllers/TestController.php';
include_once 'controllers/ProxyController.php';
include_once 'controllers/DeliveryController.php';
include_once 'controllers/GateController.php';
include_once 'middleware/AuthMiddleware.php';
include_once 'middleware/AuthMiddlewareTest.php';

use Slim\Routing\RouteCollectorProxy;



/**
 * @var RouteCollectorProxy $group
 */

$group->group(
    '/tmp',
    function (RouteCollectorProxy $group){
        $group->get(
            '/hidden',
            \API\v1\Controllers\DeliveryController::class . ':GetAddressList'
        )->add(new API\v1\Middleware\AuthMiddlewareTest());
    }
);

$group->group(
    '/user',
    function (RouteCollectorProxy $group) {

        // получить заказ по идентификатору
        $group->get(
            '/order/{id}',
            \API\v1\Controllers\OrderController::class . ':GetById'
        );
        //->add(new API\v1\Middleware\AuthMiddlewareTest());

        // Получить список заявок на отгрузку
        $group->get(
            '/shipments',
            \API\v1\Controllers\UserController::class . ':GetShipmentList'
        );
        //->add(new API\v1\Middleware\AuthMiddlewareTest());

        // Получить список заявок на отгрузку
        $group->get(
            '/claims',
            \API\v1\Controllers\UserController::class . ':GetClaimList'
        );
        //->add(new API\v1\Middleware\AuthMiddlewareTest());

        // Добавить заявку на отгрузку
        $group->post(
            '/shipments/add',
            \API\v1\Controllers\UserController::class . ':AddShipment'
        );
        //->add(new API\v1\Middleware\AuthMiddlewareTest());

        // Добавить претензию
        $group->post(
            '/claims/add',
            \API\v1\Controllers\UserController::class . ':AddClaim'
        );
        //->add(new API\v1\Middleware\AuthMiddlewareTest());

        $group->group(
            '/delivery',
            function (RouteCollectorProxy $group){

                // Запросить список с адресами доставки для пользователя
                $group->get(
                    '',
                    \API\v1\Controllers\DeliveryController::class . ':GetAddressList'
                );

                // Добавить новую точку доставки
                $group->post(
                    '/add',
                    \API\v1\Controllers\DeliveryController::class . ':AddPoint'
                );

                // Обновить данные точки доставки по индексу
                $group->post(
                    '/update',
                    \API\v1\Controllers\DeliveryController::class . ':UpdatePoint'
                );

                // Удалить существующую точку доставки по индексу
                $group->post(
                    '/delete',
                    \API\v1\Controllers\DeliveryController::class . ':RemovePoint'
                );
            }
        );

    }
)->add(new API\v1\Middleware\AuthMiddlewareTest());

$group->group(
    '/test',
    function (RouteCollectorProxy $group) {
        $group->get('/', function (Psr\Http\Message\ServerRequestInterface $request,Psr\Http\Message\ResponseInterface  $response, array $args) {

            $response->getBody()->write(json_encode("Test Hello"));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        });

        // [TEMP] добавить новый заказ, тест для проверки моделей данных
        $group->post(
            '/order/add',
            \API\v1\Controllers\TestController::class . ':OrderAdd'
        );
        //->add(new \API\v1\Middleware\AuthMiddleware());

    }
);

//region Проксирующие методы (временные)

$group->group(
    '/proxy',
    function (RouteCollectorProxy $group){

        // Добавить новый заказ, для проверки моделей данных, инъекция в 1С.
        $group->post(
            '/order/add',
            \API\v1\Controllers\ProxyController::class . ':ProxySendOrderData'
        );

        // Добавить новый заказ, для проверки моделей данных, инъекция в 1С.
        $group->post(
            '/order/printing/status',
            \API\v1\Controllers\ProxyController::class . ':ProxyOrderPrintingStatus'
        );

        // Получить документ, для проверки моделей данных, инъекция в 1С.
        $group->get(
            '/order/printing',
            \API\v1\Controllers\ProxyController::class . ':ProxyOrderPrinting'
        );

        // Добавить новый заказ, для проверки моделей данных, инъекция в 1С.
        $group->get(
            '/partner/{id}',
            \API\v1\Controllers\ProxyController::class . ':GetPartnerById'
        );
    }
);

//endregion

//region Служебные методы, прим: обмен по функционалу с 1С.
$group->group(
    '/service',
    function (RouteCollectorProxy $group){

        // работа с заказом
        $group->group(
            '/order',
            function (RouteCollectorProxy $group){

                // установить статус заказа по его идентификатору
                $group->post(
                    '/status',
                    \API\v1\Controllers\GateController::class . ':SetStatusByGUID'
                );

            }
        );
    }
);
//endregion

// Получить данные по товару
$group->group(
    '/product',
    function (RouteCollectorProxy $group){

        // поиск позиции
        $group->get(
            '/search',
            \API\v1\Controllers\ProductController::class . ':SearchByWord'
        );

        // по bitrix-id
        $group->get(
            '/{id}',
            \API\v1\Controllers\ProductController::class . ':GetById'
        );

    }
);

$group->group(
    '/products',
    function (RouteCollectorProxy $group){
        // Список товаров для строки поиска с сайта.
        $group->get(
            '/list',
            \API\v1\Controllers\ProductController::class . ':SearchList'
        );

    }
);

// работа с заказами
$group->group(
    '/order',
    function(RouteCollectorProxy $group){

        // получить cтатусы печатных форм
        $group->get(
            '/{id}/documents',
            \API\v1\Controllers\OrderController::class . ':GetDocumentsStatusById'
        );


        // получить печатную форму
        $group->get(
          '/print',
            \API\v1\Controllers\OrderController::class . ':GetDocumentById'
        );

        // добавить новый заказ
        $group->post(
            '/add',
            \API\v1\Controllers\TestController::class . ':OrderAdd' //\API\v1\Controllers\OrderController::class . ':Add'
        );
            //->add(new \API\v1\Middleware\AuthMiddleware());

    }
);

// работа с заказами
$group->group(
    '/orders',
    function(RouteCollectorProxy $group){

        // добавить новый заказ
        $group->get(
            '',
            \API\v1\Controllers\OrderController::class . ':GetList'
        );
        //->add(new \API\v1\Middleware\AuthMiddleware());

    }
);

// Получить данные по менеджеру
$group->get(
    '/manager',
    \API\v1\Controllers\PartnerController::class . ':Manager'
);

// Авторизация пользователя c обработкой JWT токена
$group->post(
    '/auth',
    \API\v1\Controllers\UserController::class . ':Authorization'
);
#->add(new AuthMiddleware());

//$group->options(
//    '/auth',
//    function(
//        \Psr\Http\Message\ServerRequestInterface $request,
//        \Psr\Http\Message\ResponseInterface $response): \Psr\Http\Message\ResponseInterface{
//        return $response;
//    }
//);

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