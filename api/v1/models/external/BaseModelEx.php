<?php

namespace API\v1\Models;

/**
 * Базовый абстрактный класс для представления внешних моделей с данными.
 */
abstract class BaseModelEx
{
    /**
     * Получить значения модели в виде массива
     */
    public function AsArray(): array{
        return get_object_vars($this);
    }
}