<?php
use Slim\Routing\RouteCollectorProxy;

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