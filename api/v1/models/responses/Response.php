<?php

namespace API\v1\Models;
include_once './BaseResponse.php';

class Response extends BaseResponse
{
    /**
     * @var array Данные для ответа
     */
    public array $data = [];

    /**
     * Подготовить данные ответа в формате JSON
     *
     * @return false|string
     */
    public final function AsJSON(){
        return json_encode([
            "response" => $this->data,
            "error" => null
        ]);
    }
}