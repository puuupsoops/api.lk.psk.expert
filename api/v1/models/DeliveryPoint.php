<?php

namespace API\v1\Models;
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/DeliveryPointEx.php';

class DeliveryPoint extends DeliveryPointEx
{
    /**
     * @var int Идентификатор точки
     */
    private int $bitrixId;

    /**
     * Конструктор класса
     *
     * @param array $data Массив значений для инициализации
     */
    public function __construct(array $data)
    {
        $this->bitrixId     = (int) $data['ID'];
        $this->latitude     = (string) $data['latitude'];
        $this->longitude    = (string) $data['longitude'];
        $this->label        = (string) $data['label'];
        $this->address      = (string) $data['address'];
    }

    /**
     * Получить идентификатор записи в базе данных Битрикса
     *
     * @return int Идентификатор
     */
    public function Id(): int{
        return $this->bitrixId;
    }
}