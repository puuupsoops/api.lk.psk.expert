<?php

namespace API\v1\Managers;

include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/service/ErrorHandler.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/Partner.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/BaseModelEx.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/StorageEx.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/managers/Partner.php';

include_once $_SERVER['DOCUMENT_ROOT'] . '/Environment.php';

use Environment;

\Bitrix\Main\Loader::includeModule('iblock');

/**
 * Класс для взаимодействия с контрактами контрагентов
 *
 * @package API\v1\Managers
 */
class Contract
{
    /**
     * @var string Идентификатор инфоблока в Битрикс
     */
    private string $iBlockID = Environment::IBLOCK_ID_CONTRACTS;

    /**
     * @var array Массив с описание полей свойств элемента инфоблока в Битрикс
     */
    private array $arProps = [
        'IBLOCK_SECTION_ID',
        'PROPERTY_PARTNER',
        'PROPERTY_SPENT',
        'PROPERTY_DEFERMENT',
        'PROPERTY_DEBT',
        'PROPERTY_BALANCE',
        'PROPERTY_DISCOUNT',
        'PROPERTY_PAY_DATE'
    ];

    # Получить контракты связанные с контрагентом

    /**
     * Получить связанные контракты
     *
     * @param \API\V1\Models\Partner $partner Модель контрагента
     * @return array        Массив с моделями контракта
     * @throws \Exception   404 контракты не найдены
     */
    public function GetAll(\API\V1\Models\Partner $partner){
        return $this->GetContracts($partner);
    }

    # Получить контракты с документами связанные с контрагентом
    public function GetAllWithDocuments(\API\V1\Models\Partner $partner){

    }

    private function GetContracts(\API\V1\Models\Partner $partner): array{
        /**
         * @var array Результат выборки
         */
        $Contracts = [];

        /**
         * @var array $arSelect Выбор полей из базы Битрикс
         */
        $arSelect = [
            'ID',
            'NAME'
        ];

        $arSelect = array_merge($arSelect,$this->arProps);

        $arFilter['IBLOCK_ID']          = $this->iBlockID;
        $arFilter['PROPERTY_PARTNER']   = $partner->Id();

        $arResult = \CIBlockElement::GetList(
            [],
            $arFilter,
            false,
            false,
            $arSelect);

        while($obj = $arResult->Fetch()){
            $Contracts[] = new \API\v1\Models\Contract($obj);
        }

        if(!empty($Contracts)){
            return $Contracts;
        }else{
            throw new \Exception('Связанные контракты отсутствуют в базе данных',404);
        }
    }

    private function GetDocuments(\API\V1\Models\Partner $partner){

    }
}