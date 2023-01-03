<?php

namespace API\v1\Models;
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/BaseModelEx.php';

/**
 * Модель позиции заказа, выдача списка в ЛК.
 *
 * @package API\v1\Models
 */
class OrderEx extends \API\v1\Models\BaseModelEx
{
    /** @var bool Флаг резерва заказа, по умолчанию false */
    public bool $reserved = false;

    /**
     * @var string Идентификатор заказа ID из таблицы, => из 1С
     */
    public string $id;

    /**
     * @var string Надпись заказа
     */
    public string $name;

    /**
     * @var string Наименование контрагента
     */
    public string $partner_name;

    /**
     * @var string XML идентификатор контрагента
     */
    public string $partner_guid;

    /**
     * @var int Идентификатор записи пользователя в Битрикс
     */
    public int $user_id;

    /**
     * @var string Дата создания
     */
    public string $date;

    /**
     * @var string Статус
     */
    public string $status;

    /**
     * @var int Числовой идентификатор статуса
     */
    public int $status_code;

    /**
     * @var int Идентификатор заказа (INDEX из таблицы)
     */
    public int $n;

    /** @var string Комментарий к заказу */
    public string $comment;

}