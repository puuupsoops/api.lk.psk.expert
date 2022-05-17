<?php

namespace API\v1\Models\Registers;

/**
 * OrderStatus Class
 *
 * Перечесления статусов заказа
 */
class OrderStatus
{
    /**
     * @var int Создан
     */
    public const created = 0;

    /**
     * @var int В ожидании.
     * когда клиент сформировал заказ и он полетел в 1с -его статус  в лк - в ожидание.
     */
    public const waiting = 1;

    /**
     * @var int Подтвержден.
     * 2. Когда менеджер проставил размещение,заполнил заказ, сформировал счет, провел,
     * он должен нажать и поменять статус лк и выбрать Подтвержден, этим самым меняется на такой статус заказ в лк и появляется счет.
     *
     */
    public const confirmed = 2;

    /**
     * @var int Передан на склад.
     * Менеджер делает на реализацию, проводит, после проведения на складе из этой реализации печатают сборочный лист и реализация меняет статус на ПЕРЕДАНО НА СКЛАД,
     * вот по этому моменту надо менять статус в лк на Передан на склад.
     */
    public const on_warehouse = 3;

    /**
     * @var int Собран.
     * Заказ собрали, складские опять заходят в 1с печатают упаковочный лист, и 1с автоматом меняет статус на ГОТОВ К ОТГРУЗКЕ,
     * и тут должен так же в лк поменяется на Собран или (готов к отгрузке).
     */
    public const assembled = 4;

    /**
     * @var int Отгружен.
     * Клиент забрал заказ реализация меняет статус на отгружен, ну и так же в лк.+ летят в лк отгрузочные документы
     */
    public const transferred = 5;

    /**
     * @var array[] описание статусов в виде массива
     */
    private const LIST = [
        0 => [
            'title' => 'Создан.',
            'label' => 'created',
            'code' => 0,
        ],
        1 => [
            'title' => 'В ожидании.',
            'label' => 'waiting',
            'code' => 1,
        ],
        2 => [
            'title' => 'Подтвержден.',
            'label' => 'confirmed',
            'code' => 2,
        ],
        3 => [
            'title' => 'Передан на склад.',
            'label' => 'on_warehouse',
            'code' => 3,
        ],
        4 => [
            'title' => 'Собран.',
            'label' => 'assembled',
            'code' => 4,
        ],
        5 => [
            'title' => 'Отгружен.',
            'label' => 'transferred',
            'code' => 5,
        ],
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