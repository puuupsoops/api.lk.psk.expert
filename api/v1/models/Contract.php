<?php

namespace API\v1\Models;
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/service/ErrorHandler.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/BaseModelEx.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/StorageEx.php';

/**
 * Модель представления данных договора (контракта)
 *
 * @package API\v1\Models
 */
class Contract extends \API\v1\Models\StorageEx
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
     * @var int Идентификатор секции битрикс
     */
    private int $sectionBitrixId;

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
        $this->partnerBitrixId  = (int) $data['PROPERTY_PARTNER_VALUE'];

        $this->contract         = $data['NAME'];
        $this->spent            = (float) $data['PROPERTY_SPENT_VALUE'];
        $this->deferment        = (int)   $data['PROPERTY_DEFERMENT_VALUE'];
        $this->debt             = (float) $data['PROPERTY_DEBT_VALUE'];
        $this->balance          = (float) $data['PROPERTY_BALANCE_VALUE'];
        $this->discount         = (float) $data['PROPERTY_DISCOUNT_VALUE'];
        $this->date             = $data['PROPERTY_PAY_DATE_VALUE']; # дата погашения

        $this->SetSection($data['IBLOCK_SECTION_ID']);
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
     * Получить идентификатор секции (раздела)
     *
     * @return int Идентификатор
     */
    public function SectionId(): int{
        return $this->sectionBitrixId;
    }

    /**
     * Устанавливает данные для секции (раздела) в битрикс
     *
     * @param int $id
     */
    private function SetSection(int $id){
        $section = [];

        /**
         *  Идентификатор секции инфоблока
         */
        $specOda = \Environment::GetInstance()['iblocks']['sections']['Contracts']['SPEC_ODA'];
        /**
         *  Идентификатор секции инфоблока
         */
        $workShoes = \Environment::GetInstance()['iblocks']['sections']['Contracts']['WORK_SHOES'];

        switch ($id){
            /**
             * Установки складов
             */
            case $specOda:
                //$section = \Environment::IBLOCK_SECTION_STORAGE__CONTRACT__SPEC_ODA;
                $section = [
                    'ID' => $specOda,
                    'NAME' => 'ООО "Эксперт Спецодежда"',
                    'CODE' => 'SPEC_ODA',
                    'HL_BLOCK_UID' => 'b5e91d86-a58a-11e5-96ed-0025907c0298',
                    //'HL_BLOCK' =>
                ];
                break;
            case $workShoes:
                //$section = \Environment::IBLOCK_SECTION_STORAGE__CONTRACT__WORK_SHOES;
                $section = [
                    'ID' => $workShoes,
                    'NAME' => 'ООО "Фабрика рабочей обуви"',
                    'CODE' => 'WORK_SHOES',
                    'HL_BLOCK_UID' => 'f59a4d06-2f35-11e7-8fdb-0025907c0298',
                    //'HL_BLOCK' =>
                ];
                break;
            default:
                break;
        }

        $this->sectionBitrixId  = $id;
        $this->name             = $section['NAME']; # наименование склада
        $this->guid             = $section['HL_BLOCK_UID']; # xml идентификатор склада
    }
}