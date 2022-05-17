<?php

namespace API\v1\Managers;

include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/service/ErrorHandler.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/Partner.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/Document.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/BaseModelEx.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/StorageDocumentEx.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/managers/Partner.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/managers/Contract.php';

include_once $_SERVER['DOCUMENT_ROOT'] . '/Environment.php';

\Bitrix\Main\Loader::includeModule('iblock');

/**
 * Класс для взаимодействия с документами привязанными к контракту
 *
 * @package API\v1\Managers
 */
class Document
{
    /**
     * @var string Идентификатор инфоблока в Битрикс
     */
    private $iBlockID;

    /**
     * @var array Массив с описанием полей свойств элемента инфоблока в Битрикс
     */
    private array $arProps = [
        'PROPERTY_DEBT',
        'PROPERTY_EXPIRES',
        'PROPERTY_CONTRACT',
        'PROPERTY_PARTNER',
    ];

    public function __construct(){
        $this->iBlockID = (string) \Environment::GetInstance()['iblocks']['Documents'];

    }

    # получить массив документов связанных с контрактом
    public function GetBounds(\API\V1\Models\Contract $contract): array {
        /**
         * @var array Результат выборки
         */
        $Documents = [];

        /**
         * @var array $arSelect Выбор полей из базы Битрикс
         */
        $arSelect = [
            'ID',
            'NAME',
            'ACTIVE_FROM'
        ];

        $arSelect = array_merge($arSelect,$this->arProps);

        $arFilter = [
            'IBLOCK_ID' => $this->iBlockID,
            'PROPERTY_CONTRACT' => $contract->Id()
        ];

        $arResult = \CIBlockElement::GetList(
            [],
            $arFilter,
            false,
            false,
            $arSelect);

        while($obj = $arResult->Fetch() ){

            $Documents[] = new \API\v1\Models\Document($obj);
        }

        if(!empty($Documents)){
            return $Documents;
        }else{
            return [];
        }
    }
}