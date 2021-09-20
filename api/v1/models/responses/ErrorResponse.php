<?php

namespace API\v1\Models;

include_once './BaseResponse.php';

/**
 * Класс для ответов с ошибкой
 */
class ErrorResponse extends BaseResponse
{


    /**
     * Подготовить данные ответа в формате JSON
     *
     * @return false|string
     */
    public final function AsJSON(){
        return json_encode([
            "response" => [],
            "error" => [
                "code" => $this->code,
                "message" => $this->message
            ]
        ]);
    }
}