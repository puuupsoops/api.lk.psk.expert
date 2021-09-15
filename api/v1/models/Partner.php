<?php
namespace API\v1\Models;

use Exception;
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/service/ErrorHandler.php.';

include_once './external/PartnerEx.php';

/**
 * Модель данных контрагента
 * 
 * @package API\v1\Models
 */
class Partner extends \API\v1\Models\PartnerEx {
    /**
     * @var int Идентификатор записи в базе данных Битрикса 
     */
    private $bitrixId;

    /**
     * Конструктор класса
     * @param array $data Массив значений для инициализации
     * @throws \Exception
     */
    public function __construct(array $data)
    {
        if( is_array($data) ){
            $this->bitrixId         = $data['ID'];
            $this->name             = $data['NAME'];
            $this->uid              = $data['PROPERTY_UID_VALUE'];
            $this->city             = $data['PROPERTY_CITY_VALUE'];
            $this->phone            = $data['PROPERTY_PHONE_VALUE'];
            $this->email            = $data['PROPERTY_EMAIL_VALUE'];
            $this->address          = $data['PROPERTY_ADDRESS_VALUE'];
            $this->inn              = $data['PROPERTY_INN_VALUE'];
            $this->bik              = $data['PROPERTY_BIK_VALUE'];
            $this->payment          = $data['PROPERTY_PAYMENT_VALUE'];
            $this->correspondent    = $data['PROPERTY_CORRESPONDENT_VALUE'];
        }else{
            throw new \API\v1\Service\ErrorHandler('',\API\v1\Service\ErrorHandler::INVALID_PARAM_TYPE,$data);
        }
    }

    /**
     * Получить идентификатор записи в базе данных Битрикса
     */
    public function ID(){
        return $this->bitrixId;
    }
}