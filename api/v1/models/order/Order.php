<?php

namespace API\v1\Models\Order;

include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/order/Position.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/order/delivery/DeliveryBase.php';

/**
 * Модель заказа, приходит из личного кабинета.
 *
 * @package API\v1\Models
 */
class Order
{
    /** @var  */
    private $delivery = ''; // todo: определиться с доставкой

    /** @var bool Флаг сертфиката: <br> true - добавить сертификат к итоговым документам */
    private bool $certificate = false;

    /** @var string Комментарий к заказу */
    private string $comment = '';

    /**
     * @var bool Флаг резерва:
     * <ul>
     *      <li>true - заказ под резерв</li>
     *      <li>false - обычный заказ</li>
     * </ul>
     */
    private bool $reserved   = false;

    /**
     * @var bool Флаг редактирования:
     * <ul>
     *      <li>true - данные переданы для редактирования</li>
     *      <li>false - по умолчанию.</li>
     * </ul>
     */
    private bool $edit       = false;

    /** @var int Генерируемый id из даты, на стороне личного кабинета. <br> Существует до callback регистрации в 1С */
    private int $id = 0;

    /** @var float Стоимость заказа без учета скидки */
    private float $total = 0.0;

    /** @var int Количество позиций */
    private int $count = 0;

    /** @var float Вес заказа */
    public float $weight = 0.0;

    /** @var float Обьем заказа  */
    public float $volume = 0.0;

    /** @var string XML идентификатор Контрагента */
    private string $partner_id = '';

    /** @var \API\v1\Models\Order\Position[] Массив с обьектами товаров доступными к покупке (<b>в наличии</b>) */
    public array $position = [];

    /** @var \API\v1\Models\Order\Position[] Массив с обьектами товаров недоступными к покупке (<b>предзаказ</b>) */
    public array $position_presail = [];

    public function __construct(array $data)
    {
        $this->reserved = $data['reserved'];
        $this->edit = $data['edit'];
        $this->id = $data['id'];
        $this->total = $data['total'];
        $this->count = $data['count'];
        $this->partner_id = $data['partner_id'];

        $this->comment = $data['comment'] ?? '';
        $this->certificate = (bool) $data['request_certificate'];

        //доставка
        $this->delivery = new \API\v1\Models\Order\Delivery\DeliveryBase($data['delivery'] ?? []);

        foreach ($data['position'] as $value){
            $this->position[] = new \API\v1\Models\Order\Position($value);
        }

        foreach ($data['position_presail'] as $value){
            $this->position_presail[] = new \API\v1\Models\Order\Position($value);
        }
    }

    public function IsReserved(): bool { return $this->reserved; }
    public function IsEdit(): bool { return $this->edit; }
    public function GetId(): int { return $this->id; }
    public function GetTotal(): float { return $this->total; }

    /**
     * Получить количество позиций заказа
     *
     * @return int Количество позиций.
     */
    public function GetCount(): int { return $this->count; }

    public function GetPartnerId(): string { return $this->partner_id; }

    public function IsRequestedCertificate(): bool {return $this->certificate; }
    public function GetComment(): string {return $this->comment; }
    public function GetDelivery():\API\v1\Models\Order\Delivery\DeliveryBase { return $this->delivery; }

    /**
     * Получить вес заказа
     *
     * @return float Вес. (кг)
     */
    public function GetWeight(): float { return $this->weight; }

    /**
     * Получить обьем заказа
     *
     * @return float Обьем (куб. м.)
     */
    public function GetVolume(): float { return $this->volume; }

    public function AsArray(): array {
        $arOrder = get_object_vars($this);

        foreach ($arOrder['position'] as $key => $value){
            $arOrder['position'][$key] = $value->AsArray();
        }

        foreach ($arOrder['position_presail'] as $key => $value){
            $arOrder['position_presail'][$key] = $value->AsArray();
        }

        return $arOrder;
    }
}