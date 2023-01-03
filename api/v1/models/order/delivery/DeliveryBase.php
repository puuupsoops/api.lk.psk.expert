<?php

namespace API\v1\Models\Order\Delivery;

include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/BaseModelEx.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/order/delivery/ShipmentStatus.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/order/delivery/Shipment.php';

/**
 * Базовая модель кейса отгрузки заказа
 *
 * @package API\v1\Models
 */
class DeliveryBase extends \API\v1\Models\BaseModelEx
{
    /** @var string Адрес */
    protected string $address = '';

    /** @var string Вид отгрузки */
    protected string $case = '';

    /** @var int  Дата отгрузки */
    protected int $date = 0;

    /** @var int[]  Дополнительные условия к доставке */
    protected array $extra = [];

    /** @var float Стоимость отгрузки <br>
     *  Доставка: 900 рублей, но может меняться менджером. <br>
     *  Остальные: 0 рублей.
     */
    protected float $cost = 0;

    /** @var string XML_ID организации. Включить счёт доставки в заказ организации: ФРО или ЭС */
    protected string $bill_to = '';

    /** @var  \API\v1\Models\Order\Delivery\Shipment | null Модель отгрузки */
    private ?\API\v1\Models\Order\Delivery\Shipment $shipment = null;

    public function __construct(array $data)
    {
        $this->address = $data['address'] ?? '';
        $this->case = $data['case'] ?? '';
        $this->date = $data['date'] ?? 0;
        $this->extra = []; //$this->extra = $data['extra'] ?? []; пока что не используем, т.к. тестовый вариант.

        //пока что костыль
        //$this->cost = $data['cost'] ?? 0;

        $this->cost = ($data['case'] === 'other') ? (float)900 : (float)0;

        $this->bill_to = $data['bill_to'] ?? '';

        $this->shipment = new \API\v1\Models\Order\Delivery\Shipment(
            \API\v1\Models\Order\Delivery\ShipmentStatus::Get(
                \API\v1\Models\Order\Delivery\ShipmentStatus::GetByMnemonicCode($data['case'] ?? 'self')
            )
        );

    }

    /**
     * Получить указанный XML_ID организации,
     * <br>для: Включить счёт доставки в заказ организации: ФРО или ЭС
     *
     * @return string XML_ID организации
     */
    public function GetBilling(): string { return $this->bill_to; }

    public function GetAddress(): string { return $this->address; }
    public function GetCase(): string { return $this->case; }

    /**
     * Получить дату
     *
     * @return int timestamp даты (<b>в миллисекундах</b>)
     */
    public function GetDate(): int { return $this->date; }

    /**
     * Получить дополнительные условия к доставке. <br>
     * Коды:
     * <ul>
     *  <li>1 - Жесткая упаковка</li>
     *  <li>2 - Ополечивание</li>
     * </ul>
     * @return int[]|array Массив с кодами дополнительных услуг или пустой массив.
     */
    public function GetExtra(): array { return $this->extra; }

    /**
     * Получить стоимость отгрузки
     *
     * @return float Стоимость отгрузки.
     */
    public function GetCost(): float { return $this->cost; }

    /**
     * Установить стоимость отгрузки
     *
     * @param float $value Стоимость отгрузки.
     */
    public function SetCost(float $value) { $this->cost = $value; }

    /**
     * Получить значения полей модели в виде массива
     *
     * @return array Массив значений.
     */
    public function AsArray(): array
    {
        return [
            'address' => $this->address,
            'case' => $this->case,
            'date' => date('d.m.Y',$this->date / 1000), // делим на микросекунды, приходит из ЛК
            'extra' => $this->extra
        ];
    }

    /**
     * Получить модель отгрузки.
     *
     * @return \API\v1\Models\Order\Delivery\Shipment Модель отгрузки
     */
    public function GetShipment(): \API\v1\Models\Order\Delivery\Shipment { return $this->shipment; }
}