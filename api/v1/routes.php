<?php
include_once 'controllers/PartnerController.php';
include_once 'controllers/UserController.php';
include_once 'controllers/ProductController.php';
include_once 'controllers/OrderController.php';
include_once 'controllers/TestController.php';
include_once 'controllers/ProxyController.php';
include_once 'controllers/DeliveryController.php';
include_once 'controllers/GateController.php';
include_once 'controllers/DebugController.php';
include_once 'controllers/ProposalController.php';
include_once 'controllers/DdataController.php';
include_once 'middleware/AuthMiddleware.php';
include_once 'middleware/AuthMiddlewareTest.php';
include_once 'middleware/GetPartnerDdataMiddleware.php';

use Slim\Routing\RouteCollectorProxy;



/**
 * @var RouteCollectorProxy $group
 */

// Для отладки
$group->group(
    '/debugger',
    function (RouteCollectorProxy $group){
        $group->post(
            '/order/add',
            \API\v1\Controllers\DebugController::class .':AddOrderExtend'
        )->add(new API\v1\Middleware\AuthMiddlewareTest());
    }
);

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

        //region запросы из ЛК
        $group->group(
            '/request',
            function (RouteCollectorProxy $group) {

                // Запросить счёт (запрос идет в 1С, меняет статус, отправляет уведомление менеджеру).
                $group->post(
                    '/check/{id}',
                    \API\v1\Controllers\UserController::class . ':RequestCheck'
                );

            }
        );
        //endregion

        //region Настройки для пользователя
        $group->group(
            '/settings',
            function (RouteCollectorProxy $group){

                // получить настройки конкретного пользователя
                $group->get(
                    '',
                    \API\v1\Controllers\UserController::class . ':GetSettings'
                );

                // установить настройки для уведомлений
                $group->post(
                    '/notifications/update',
                    \API\v1\Controllers\UserController::class . ':UpdateNotificationsSetup'
                );

                // установить персональные настройки
                $group->post(
                    '/personal/update',
                    \API\v1\Controllers\UserController::class . ':UpdatePersonalData'
                );

            }
        );
        //endregion

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

                $group->post(
                    '/expired',
                    \API\v1\Controllers\GateController::class . ':CloseReservedOrderById'
                );

                // для модели отредактированного заказа в 1С
                $group->post(
                    '/edit',
                    \API\v1\Controllers\GateController::class . ':EditOrderReserveByIdFrom1C'
                );

                // для модели отредактированного заказа в 1С
                $group->post(
                    '/cost',
                    \API\v1\Controllers\GateController::class . ':SetShipmentCost'
                );
            }
        );
    }
);
//endregion

//region Службы расширяющие функционал ЛК
$group->group(
    '/services',
    function (RouteCollectorProxy $group) {

        // работа с Коммерческим предложением
        $group->group(
            '/proposal',
            function (RouteCollectorProxy $group) {

                // добавить конфигурацию коммерческого предложения
                $group->post(
                    '/add',
                    \API\v1\Controllers\ProposalController::class . ':Add')
                    ->add(new API\v1\Middleware\GetPartnerDdataMiddleware());

                // удалить конфигурацию коммерческого предложения
                $group->post(
                    '/{id:[0-9]+}/delete',
                    \API\v1\Controllers\ProposalController::class . ':DeleteProposalById');

                // список конфигураций коммерческого предложения
                $group->get(
                    '/list',
                    \API\v1\Controllers\ProposalController::class . ':GetProposalList');

                // конфигурация коммерческого предложения по идентификатору
                $group->get(
                    '/{id:[0-9]+}',
                    \API\v1\Controllers\ProposalController::class . ':GetProposalById');

                // логотипы
                $group->group(
                    '/logo',
                    function (RouteCollectorProxy $group) {

                        // добавить логотип
                        $group->post(
                            '/add',
                            \API\v1\Controllers\ProposalController::class . ':AddLogo'
                        );

                        // удалить логотип по идентификатору
                        $group->post(
                            '/delete',
                            \API\v1\Controllers\ProposalController::class . ':DeleteLogoById'
                        );

                        // список логотипов
                        $group->get(
                            '/list',
                            \API\v1\Controllers\ProposalController::class . ':GetLogoList'
                        );

                });

                // шапки коммерческого предложения
                $group->group(
                    '/header',
                    function (RouteCollectorProxy $group) {

                        // добавить шапку коммерческого предложения
                        $group->post(
                            '/add',
                            \API\v1\Controllers\ProposalController::class . ':AddPreamble'
                        );

                        // удалить шапку коммерческого предложения по идентификатору
                        $group->post(
                            '/delete',
                            \API\v1\Controllers\ProposalController::class . ':DeletePreambleById'
                        );

                        // список шапок коммерческого предложения
                        $group->get(
                            '/list',
                            \API\v1\Controllers\ProposalController::class . ':GetPreambleList'
                        );

                    });

                // получить данные контрагента с сервиса ddata
                $group->get(
                    '/org/{id:[0-9]+}',
                    \API\v1\Controllers\DdataController::class . ':GetByInn'
                );

            }
        );

    }
)->add(new API\v1\Middleware\AuthMiddlewareTest());

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

        // получить статусы печатных форм
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
        //$group->post(
        //    '/add',
        //    \API\v1\Controllers\TestController::class . ':OrderAdd' //\API\v1\Controllers\OrderController::class . ':Add'
        //);
            //->add(new \API\v1\Middleware\AuthMiddleware());
        $group->post(
            '/add',
            \API\v1\Controllers\OrderController::class . ':AddExtend' //\API\v1\Controllers\OrderController::class . ':Add'
        )->add(new API\v1\Middleware\AuthMiddlewareTest());

        $group->post(
            '/{id}/edit',
            \API\v1\Controllers\OrderController::class . ':EditReserve' //\API\v1\Controllers\OrderController::class . ':Add'
        )->add(new API\v1\Middleware\AuthMiddlewareTest());

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