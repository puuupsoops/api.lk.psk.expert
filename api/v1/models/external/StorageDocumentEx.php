<?php

namespace API\v1\Models;
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/BaseModelEx.php';

class StorageDocumentEx extends \API\v1\Models\BaseModelEx
{
    /**
     * @var string Дата оплаты (день для календаря)
     */
    protected string $expires;

    /**
     * @var string Дата документа
     */
    protected string $date;

    /**
     * @var string Номер документа
     */
    protected string $number;

    /**
     * @var float Долг (в рублях)
     */
    protected float $debt;


    /**
     * Получить значения модели в виде массива
     */
    public function AsArray(): array{

        if($this->expires !== ''){
            $this->expires = strtotime($this->expires);
        }

        if($this->date !== ''){
            $this->date = strtotime($this->date);
        }

        return get_object_vars($this);
    }
}