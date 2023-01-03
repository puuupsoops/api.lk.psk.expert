<?php

namespace API\v1\Models\Order;

/**
 * Модель характеристики позиции заказа, приходит из личного кабинета.
 *
 * @package API\v1\Models
 */
class Characteristic
{
    /** @var string XML идентфикатор характеристики */
    public string $guid     = '';

    /** @var string XML идентификатор фабрики производителя */
    public string $orgguid  = '';

    /** @var int Количество едениц */
    public int $quantity    = 0;

    //region Добавление хранения и обработки цены из ЛК и 1С
    //date: 2022-08-31
    /** @var float  Cкидка на позицию числом (0.07 = 7%) */
    public float $discount = 0.0;

    /** @var float  Цена единицы товара, без скидки */
    public float $fullprice = 0.0;

    /** @var float  Цена единицы товара, с учетом скидки (сюда записывается итоговая цена) */
    public float $price = 0.0;
    //endregion

    public function __construct(array $data)
    {
        $this->guid = $data['guid'];
        $this->orgguid = $data['orgguid'];
        $this->quantity = $data['quantity'];

        //region Добавление хранения и обработки цены из ЛК и 1С
        //date: 2022-08-31

        if( array_key_exists('discount',$data) ){
            $this->discount = $data['discount'];
        }

        if( array_key_exists('price',$data) ){
            $this->price = $data['price'];
        }

        if( array_key_exists('fullprice',$data) ){
            $this->fullprice = $data['fullprice'];
        }
        else{
            $this->fullprice = $data['price'];
        }

        //endregion
    }

    public function AsArray(): array { return get_object_vars($this); }
}