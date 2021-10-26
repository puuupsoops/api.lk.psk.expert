<?php
//header('Access-Control-Allow-Origin:*');
//header('Access-Control-Allow-Headers:X-Request-With');
//header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
//header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require_once 'v1/middleware/CORSMiddleware.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;
use Psr\Container\ContainerInterface;
use Slim\App as Slim;
use DI\Container;

require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

$container = new Container();

\Slim\Factory\AppFactory::setContainer($container);
$app = \Slim\Factory\AppFactory::create(null);

/*
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});
*/

/*
$app->add(function ($req, $res) {
    $response = $next($req, $res);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});
*/

$app->group(
    '/api',
    function (RouteCollectorProxy $group) {
        /**
         * API для получения и обмена данных из внешнего сайта Bitrix и базы 1С.
         */
        require_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/routes.php';

        #$group->group(
        #    '/1.0',
        #    function (RouteCollectorProxy $group) {
        #
        #    }
        #);
    }
);
    //->add(new \API\v1\Middleware\CORSMiddleware());

$app->run();