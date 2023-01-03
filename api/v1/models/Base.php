<?php

namespace API\v1\Models;

abstract class Base
{
    /**
     * Получить значения модели в виде массива
     *
     * @return array
     */
    public function AsArray(): array{
        return get_object_vars($this);
    }

    /**
     * Получить значения модели в виде строки JSON
     *
     * @return string
     */
    public function AsJSON(): string{
        return json_encode($this->AsArray());
    }
}