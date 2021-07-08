<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

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

$app->group(
    '/api',
    function (RouteCollectorProxy $group) {
        /**
         * API для получения и обмена данных из внешнего сайта Bitrix и базы 1С.
         */
        $group->group(
            '/1.0',
            function (RouteCollectorProxy $group) {
                require_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/routes.php';
            }
        );
    }
);

$app->run();