<?php

include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/responses/BaseResponse.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/responses/ErrorResponse.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/responses/Response.php';

use API\v1\Models\Response,
    API\v1\Models\ErrorResponse;

/**
 * Ответ сервера в виде ошибки
 *
 * @param Exception $e                                  Исключение
 * @param \Psr\Http\Message\ResponseInterface $response Ссылка на интерфейс ответа
 * @return \Psr\Http\Message\ResponseInterface          Интерфейс ответа
 */
function ErrorResponse(
    \Exception $e,
    \Psr\Http\Message\ResponseInterface &$response) : \Psr\Http\Message\ResponseInterface{
    $ErrorResponse = new ErrorResponse();
    $ErrorResponse->code = $e->getCode() > 0 ? $e->getCode() : 500;
    $ErrorResponse->message = $e->getMessage();

    $response->getBody()->write($ErrorResponse->AsJSON());

    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($ErrorResponse->code);
}