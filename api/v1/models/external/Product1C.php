<?php

namespace API\v1\Models;
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/BaseModelEx.php';

/**
 * Модель продукта заказа, для добавления в 1С базу.
 *
 * @package API\v1\Models
 */
class Product1C extends \API\v1\Models\BaseModelEx
{
    /** @var int Количество едениц товара */
    public int $quantity = 0;

    /** @var string XML идентификатор товара */
    public string $productid = '';

    /** @var string XML идентификатор характеристики товара */
    public string $characteristicsid = '';

    /** @var float Цена за еденицу товара */
    public float $price = 0.0;

    /** @var float Общая стоимость товарной позиции $price * $quantity */
    public float $total = 0.0;

    /** @var float Скидка, в 1С передаётся нулём */
    public float $discount = 0.0;

    /** @var string XML идентификатор склада, на котором располагается товар */
    public string $storage = '';

}