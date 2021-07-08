<?php


use Slim\Routing\RouteCollectorProxy;

$group->group(
    '/test',
    function (RouteCollectorProxy $group) {
        $group->get('/', function (Request $request, Response $response, array $args) {

            $response->getBody()->write("Test Hello");
            return $response;

        });
    }
);