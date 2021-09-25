<?php

namespace API\v1\Models;
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/BaseModelEx.php';

class StorageEx extends \API\v1\Models\BaseModelEx
{
    /**
     * @var string XML Идентификатор склада
     */
    protected string $guid;

    /**
     * @var string Наименование склада
     */
    protected string $name;

    /**
     * @var float Потрачено средств (в рублях)
     */
    protected float $spent;

    /**
     * @var string Код документа-контракта контрагента со складом
     */
    protected string $contract;

    /**
     * @var int Предоставленная отсрочка в днях
     */
    protected int $deferment;

    /**
     * @var float Долг (в рублях)
     */
    protected float $debt;

    /**
     * @var float Баланс средств (в рублях)
     */
    protected float $balance;

    /**
     * @var float Скидка (в процентах)
     */
    protected float $discount;

    /**
     * @var string Дата погашения
     */
    protected string $date;
}