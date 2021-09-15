<?php
namespace API\v1\Managers;

use Environment;
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/service/ErrorHandler.php.';

include_once $_SERVER['DOCUMENT_ROOT'] . '/Environment.php';

\Bitrix\Main\Loader::includeModule('iblock');

/**
 * Класс для взаимодействия с данными контрагентов
 * 
 * @package API\v1\Managers
 */
class Partner {
    /**
     * @var string Идентификатор инфоблока в Битрикс
     */
    private $iBlockID = Environment::IBLOCK_ID_PARTNERS;

    /**
     * @var array Массив с описание полей свойств элемента инфоблока в Битрикс
     */
    private $arProps = [
        'PROPERTY_UID',
        'PROPERTY_CITY',
        'PROPERTY_PHONE',
        'PROPERTY_EMAIL',
        'PROPERTY_ADDRESS',
        'PROPERTY_INN',
        'PROPERTY_BIK',
        'PROPERTY_PAYMENT',
        'PROPERTY_CORRESPONDENT',
    ];

    /**
     * @param string $guid Внешний XML идентификатор контрагента
     * @throws \API\v1\Service\ErrorHandler 
     * @return API\v1\Models\Partner    Объект(ы) с данными о контрагенте
     */
    public function GetByGUID(string $guid): \API\v1\Models\Partner{
        return $this->GetPartner(['XML_ID' => $guid]);
    }

    /**
     * @param int $id Идентификатор записи контрагента в Битрикс
     * @throws \API\v1\Service\ErrorHandler
     * @return API\v1\Models\Partner    Объект(ы) с данными о контрагенте 
     */
    public function GetByBitrixID(int $id): \API\v1\Models\Partner{
        return $this->GetPartner(['ID' => $id]);
    }

    /**
     * Возвращает данные о контрагенте
     * 
     * @param array $arFilter           Массив с параметрами для выбора значений
     * @throws \API\v1\Service\ErrorHandler 
     * @return API\v1\Models\Partner    Объект(ы) с данными о контрагенте
     */
    private function GetPartner(array $arFilter): \API\v1\Models\Partner{
        /**
         * @var array Результат выборки
         */
        $Partners = [];

        /**
         * @var array $arSelect Выбор полей из базы Битрикс
         */
        $arSelect = [
            'ID',
            'NAME'
        ];

        $arSelect = array_merge($arSelect,$this->arProps);

        $arFilter['IBLOCK_ID'] = $this->iBlockID;

        $arResult = \CIBlockElement::GetList(
            [],
            $arFilter,
            false,
            [],
            $arSelect);
        
        while($obj = $arResult->Fetch()){
                $Partners[] = new \API\v1\Models\Partner($obj);
        } 

        if( count($Partners) == 1 ){
            return $Partners[0];
        }else{
            return $Partners;
        }
    }
}