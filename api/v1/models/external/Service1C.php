<?php

namespace API\v1\Models;

include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/BaseModelEx.php';
/**
 * Модель услуги у заказа, для добавления в 1С базу.
 */
class Service1C extends \API\v1\Models\BaseModelEx
{
    /** @var int Количество */
    public int $quantity = 0;

    /** @var string XML идентификатор */
    public string $productid = "";

    /** @var float Стоимость позиции */
    public float $price = 0.0;

    /** @var float Сумма позиций */
    public float $total = 0.0;

    /** @var float Скидка процент */
    public float $discount = 0.0;
}