<?php

namespace API\v1\Models\Order;

include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/order/Characteristic.php';

/**
 * Модель позиции заказа, приходит из личного кабинета.
 *
 * @package API\v1\Models
 */
class Position
{
    /** @var string XML идентификатор товарной позиции */
    public string $guid = '';

    /** @var \API\v1\Models\Order\Characteristic[] Массив с обьектами характеристик товара */
    public array $characteristics = [];

    public function __construct(array $data)
    {
        $this->guid = $data['guid'];

        foreach ($data['characteristics'] as $value){
            $this->characteristics[] = new \API\v1\Models\Order\Characteristic($value);
        }
    }

    /**
     * Обьеденить одинаковые позиции.
     *
     * @param Position $position
     * @return Position
     */
    public function Merge(\API\v1\Models\Order\Position $position): \API\v1\Models\Order\Position{
        if($position->guid === $this->guid){
            $this->characteristics = array_merge($this->characteristics,$position->characteristics);
        }

        return $this;
    }

    public function AsArray(): array {
        $characteristics = [];

        foreach ($this->characteristics as $value){
            $characteristics[] = $value->AsArray();
        }

        return [
            'guid' => $this->guid,
            'characteristics' => $characteristics
        ];
    }
}