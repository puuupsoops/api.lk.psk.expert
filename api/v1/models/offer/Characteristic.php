<?php

namespace API\v1\Models\Offer;

class Characteristic
{
    protected string $title;
    protected string $guid;
    protected float $amount;
    protected float $price;
    protected float $total;

    public function __construct(array $data)
    {
        $this->title = $data['CHARACTERISTIC'] ?? '';
        $this->guid = $data['GUID'] ?? '';
        $this->price = (float)$data['PRICE'] ?? 0.0;
        $this->amount = (float)$data['count'] ?? 0.0;
        $this->total = $this->amount * $this->price;
    }

    public function GetTitle(): string { return $this->title; }
    public function GetGuid(): string { return $this->guid; }
    public function GetAmount(): float { return $this->amount; }
    public function GetPrice(): float { return $this->price; }
    public function GetTotal(): float { return $this->total; }

    public function AsArray(): array {
        $arData = get_object_vars($this);

        $arData['amount'] = number_format($this->amount,1, ',', ' ');
        $arData['price'] = number_format($this->price,2, ',', ' ');
        $arData['total'] = number_format($this->total,2, ',', ' ');

        return $arData;
    }
}