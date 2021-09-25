<?php

namespace API\v1\Models;
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/service/ErrorHandler.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/BaseModelEx.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/StorageDocumentEx.php';

/**
 * Модель представления данных документа (связанного с контрактом склада)
 *
 * @package API\v1\Models
 */
class Document extends \API\v1\Models\StorageDocumentEx
{
    /**
     * @var int Идентификатор записи в базе данных Битрикс
     */
    private int $bitrixId;

    /**
     * @var int Идентификатор связанной записи Контрагента в базе данных Битрикс
     */
    private int $partnerBitrixId;

    /**
     * @var int Идентификатор связанной записи Контракта в базе данных Битрикс
     */
    private int $contractBitrixId;

    /**
     * Конструктор класса
     *
     * @param array $data Массив значений для инициализации
     */
    public function __construct(array $data)
    {
        foreach($data as &$elem){
            if(is_null($elem))
                $elem = '';
        }

            $this->bitrixId         = (int) $data['ID'];
            $this->contractBitrixId = (int) $data['PROPERTY_CONTRACT'];
            $this->partnerBitrixId  = (int) $data['PROPERTY_PARTNER'];

            $this->number           = $data['NAME'];
            $this->date             = $data['ACTIVE_FROM'];
            $this->expires          = $data['PROPERTY_EXPIRES'];
            $this->debt             = (float) $data['PROPERTY_DEBT'];

    }

    /**
     * Получить идентификатор записи в базе данных Битрикса
     *
     * @return int Идентификатор
     */
    public function Id(): int{
        return $this->bitrixId;
    }

    /**
     * Получить идентификатор связанной записи Контрагента в базе данных Битрикс
     *
     * @return int Идентификатор
     */
    public function PartnerId(): int{
        return $this->partnerBitrixId;
    }

    /**
     * Получить идентификатор связанной записи Контракта в базе данных Битрикс
     *
     * @return int Идентификатор
     */
    public function ContractId(): int{
        return $this->contractBitrixId;
    }
}