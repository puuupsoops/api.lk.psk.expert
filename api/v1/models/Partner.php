<?php
namespace API\v1\Models;

include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/service/ErrorHandler.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/BaseModelEx.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/PartnerEx.php';

use Exception;
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
     *
     * @param array $data Массив значений для инициализации
     */
    public function __construct(array $data)
    {
            foreach($data as &$elem){
                if(is_null($elem))
                    $elem = '';
            }

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
            $this->managerUid        = $data['PROPERTY_MANAGER_UID_VALUE'];
            $this->managerName      = $data['PROPERTY_MANAGER_NAME_VALUE'];
            # throw new \API\v1\Service\ErrorHandler('',\API\v1\Service\ErrorHandler::INVALID_PARAM_TYPE,$data);
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