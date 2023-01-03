<?php

namespace API\v1\Models\Order\Delivery;

/**
 * Перечисление видов отгрузки.
 *
 * @package API\v1\Models
 */
class ShipmentStatus
{
    /** @var int Транспортная компания: ПЭК */
    public const pek = 0;

    /** @var int Транспортная компания: Деловые Линии */
    public const del_lin = 1;

    /** @var int Транспортная компания: Байкал */
    public const baikal = 2;

    /** @var int Самовывоз */
    public const self = 3;

    /** @var int Доставка до адреса */
    public const delivery = 4;

    /** @var int Другая транспротная (за 900 рублей) */
    public const other = 5;

    /** @var array[]  */
    private const LIST = [
        0 => [
            'title' => 'Пэк',
            'label' => 'pek',
            'code' => 0,
        ],
        1 => [
            'title' => 'Деловые линии',
            'label' => 'del_line',
            'code' => 1,
        ],
        2 => [
            'title' => 'Байкал',
            'label' => 'baikal',
            'code' => 2,
        ],
        3 => [
            'title' => 'Самовывоз',
            'label' => 'self',
            'code' => 3,
        ],
        4 => [
            'title' => 'Доставка',
            'label' => 'delivery',
            'code' => 4,
        ],
        5 => [
            'title' => 'Другая транспортная',
            'label' => 'other',
            'code' => 5,
        ]
    ];

    /**
     * Получить описание статуса в виде массива
     *
     * -- title:string - текст
     * -- code:int - код
     * @param int $OrderStatus Статус константой класса
     * @return array{title:string,code:int} Статус заказа
     */
    public static function Get(int $OrderStatus): array{
        return self::LIST[$OrderStatus];
    }

    /**
     * Получить числовой статус по мнемоническому коду
     *
     * @param string $label Статус константой класса
     * @return int Статус заказа числом
     */
    public static function GetByMnemonicCode(string $label): int{
        foreach (self::LIST as $key => $item){
            if($item['label'] === $label)
                return $key;
        }
        return 0;
    }
}