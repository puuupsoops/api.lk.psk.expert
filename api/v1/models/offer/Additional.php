<?php

namespace API\v1\Models\Offer;

class Additional
{
    /** @var bool Флаг предоплаты */
    private bool $prepayment = false;
    /** @var float Коэффициент Процента предоплаты */
    private float $prepaymentValue = 0.0;
    /** @var float Коэффициент Процента предоплаты Остаток */
    private float $prepaymentResidue = 0.0;
    /** @var bool Флаг отсрочки */
    private bool $delay = false;
    /** @var int Рабочих дней отсрочки */
    private int $delayWorkValue = 0;
    /** @var int Календарных дней отсрочки */
    private int $delayCalendarValue = 0;
    /** @var bool Флаг самовывоза */
    private bool $pickup = false;
    /** @var string Адрес самовывоза */
    private string $pickupValue = '';
    /** @var bool Флаг доставки */
    private bool $delivery = false;
    /** @var float Стоимость доставки */
    private float $deliveryValue = 0.0;

    //текстовый формат
    /** @var string Коэффициент Процента предоплаты */
    private string $prepaymentValueText = '';
    /** @var string Коэффициент Процента предоплаты Остаток */
    private string $prepaymentResidueText = '';
    /** @var string Стоимость доставки */
    private string $deliveryValueText = '';

    public function __construct(array $data)
    {
        //предоплата
        $this->prepayment = $data['prepayment'] ?? false;
        $this->prepaymentValue = (float)$data['prepaymentValue'] ?? 0.0;
        $this->prepaymentResidue = 1.00 - $this->prepaymentValue;

        //отсрочка
        $this->delay =  $data['delay'] ?? false;
        $this->delayWorkValue  = $data['delayWorkValue'] ?? 0;
        $this->delayCalendarValue  = (int)$data['delayCalendarValue'] ?? 0;

        //самовывоз
        $this->pickup  = $data['pickup'] ?? false;
        $this->pickupValue = $data['pickupValue'] ?? '';

        //доставка
        $this->delivery  = $data['delivery'] ?? false;
        $this->deliveryValue  = (float)$data['deliveryValue'] ?? 0.0;
    }

    /**
     * Флаг доставки
     * @return bool
     */
    public function IsDelivery(): bool {
        return $this->delivery;
    }

    /**
     * Стоимость доставки
     * @return float
     */
    public function GetDeliveryCost(): float {
        return $this->deliveryValue;
    }

    public function AsArray(): array {
        $arData = get_object_vars($this);

        //процентом
        $arData['prepaymentValueText'] = ($this->prepaymentValue * 100) . ' %';
        $arData['prepaymentResidueText'] = ($this->prepaymentResidue * 100) . ' %';
        $arData['deliveryValueText'] = number_format($this->deliveryValue,2, ',', ' ');

        return $arData;
    }
}