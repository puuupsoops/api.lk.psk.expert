<?php

namespace API\v1\Models;

include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/Base.php';

/**
 * Модель пользователя
 */
class User extends \API\v1\Models\Base
{
    /** @var int Идентификатор записи пользователя в Битрикс */
    protected int $id = 0;

    protected string $login = '';
    protected string $name = '';
    protected string $lastname = '';
    protected string $patronymic = '';
    protected string $email = '';

    /** @var int Идентификатор конфигурации пользователя */
    protected int $config = 0;

    /**
     * @param array $data Данные
     */
    public function __construct(array $data)
    {
        $this->id = (int)$data['ID'];
        $this->login = $data['LOGIN'];
        $this->name = $data['NAME'] ?? '';
        $this->lastname = $data['LAST_NAME'] ?? '';
        $this->patronymic = $data['SECOND_NAME'] ?? '';
        $this->email = $data['EMAIL'] ?? '';
        $this->config = (int)$data['UF_PARTNERS_LIST'];
    }

    public function GetId(): int { return $this->id; }
    public function GetConfig(): int { return $this->config; }
}