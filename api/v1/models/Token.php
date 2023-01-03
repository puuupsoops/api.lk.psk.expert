<?php

namespace API\v1\Models;

/**
 * Модель токена авторизации пользователя
 *
 * @package API\v1\Models
 */
class Token
{
    /**
     * @var int Идентификатор записи пользователя в Битрикс
     */
    private int $id     = 0;

    /**
     * @var int Идентификатор инфоблока конфигурации пользователя
     */
    private int $config = 0;

    /**
     * @var int Числовая соль-подпись
     */
    private int $sign   = 0;

    public function __construct(array $tokenData)
    {
        $this->id = $tokenData['id'];
        $this->config = $tokenData['config'];
        $this->sign = ($tokenData['sign']) ?? 0;
    }

    /**
     * Получить идентификатор записи пользователя в Битрикс.
     * @return int Идентификатор пользователя в Битрикс.
     */
    public function GetId(): int { return $this->id; }

    /**
     * Получить идентификатор инфоблока конфигурации пользователя.
     *
     * @return int Идентификатор инфоблока конфигурации пользователя.
     */
    public function GetConfig(): int { return $this->config; }

    /**
     * Получить числовую соль-подпись.
     * @return int Числовая соль-подпись.
     */
    public function GetSign(): int { return $this->sign; }

    /**
     * Получить значения в виде массива.
     *
     * @return array
     */
    public function AsArray(): array { return get_object_vars($this); }
}