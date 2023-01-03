<?php

namespace API\v1\Models;
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/BaseModelEx.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/Product1C.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/Service1C.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/order/Position.php';

/**
 * Модель позиции заказа для добавления в 1С базу.
 *
 * @package API\v1\Models
 */
class Order1CAdd extends \API\v1\Models\BaseModelEx
{
    /** @var bool Флаг резерва, для позиций вналичии - true, для предзаказа - false */
    public bool $reserved = false;

    /** @var bool Флаг сертификата: true - добавить сертификат. */
    public bool $request_certificate = false;

    /** @var string Текст комментария */
    public string $comment = '';

    /** @var string Наименование вида отгрузки */
    public string $delivery_terms = '';

    /** @var int Идентификатор записи Элемента Битрикса, куда записаны позиции заказа */
    public int $id = 0;

    /** @var string XML идентификатор организации (ФРО или СО) */
    public string $organizationid = '';

    /** @var string XML Идентификатор контракта, временно пустой, береться на стороне 1С */
    public string $contractid = '';

    /** @var float Итоговая сумма позиций заказа. */
    public float $total = 0.0;

    /** @var \API\v1\Models\Product1C[] Массив товаров */
    public array $products = [];

    /** @var \API\v1\Models\Service1C[] Массив услуг */
    public array $services = [];

    public function __construct()
    {

    }

    /**
     * Добавить продукт в стек.
     *
     * @param Order\Position $position Товарная позиция
     * @param array $extendPosition Позиция с характеристиками из 1С.
     *
     * @throws \Exception Отсутствуют характеристики {product_name}, в базе 1С.
     */
    public function AddProduct(\API\v1\Models\Order\Position $position, array $extendPosition){

        if(empty($extendPosition['characteristics']))
            throw new \Exception(
                'Отсутствуют характеристики: ' . $extendPosition['product'] . ', в базе 1С.',
                404
            );

        foreach ($position->characteristics as $value){
            $product = new \API\v1\Models\Product1C();

            $product->quantity = $value->quantity;
            $product->productid = $position->guid;
            $product->characteristicsid = $value->guid;
            $product->price = $value->price;
            $this->total += $product->total = floatval($product->quantity * $product->price);
            //$product->discount = $value->discount;

            //region склад
            foreach ($extendPosition['characteristics'] as $characteristic){
                if($characteristic['guid'] === $product->characteristicsid){
                    //проходимся по складам.
                    foreach ($characteristic['storages'] as $storage) {
                        if((int)$storage['quantity'] >= $product->quantity) {
                            $product->storage = $storage['guid']; // UID склада
                            break;
                        }
                    }
                    break;
                }
            }

            if($product->characteristicsid === '00000000-0000-0000-0000-000000000000') {
                // №2 СИЗ/Инвентарь (Дубровки)
                $product->storage = '065f052d-fc58-11e3-8704-0025907c0298';
            }

            if($product->storage === '') {
                $product->storage = $extendPosition['characteristics'][0]['storages'][0]['guid'] ?? '';
            }
            //endregion

            $this->products[] = $product;
        }
    }

    /**
     * Добавить услугу в стэк
     *
     * @param \API\v1\Models\Service1C $service Модель услуги.
     */
    public function AddService(\API\v1\Models\Service1C $service) {
        $this->services[] = $service;
    }

    public function AsArray(): array
    {
        $data = get_object_vars($this);

        $products = [];
        $services = [];

        foreach ($this->products as $product){
            $products[] = $product->AsArray();
        }

        $data['products'] = $products;

        foreach ($this->services as $service) {
            $services[] = $service->AsArray();
        }

        $data['services'] = $services;

        return $data;
    }
}