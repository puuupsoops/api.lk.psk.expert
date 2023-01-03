<?php

namespace API\v1\Managers;
include_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/order/Order.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/local/modules/psk.api/lib/DirectoryTable.php';

/**
 * Класс для взаимодействия с логикой заказов
 *
 * @package API\v1\Managers
 */
class Order
{
    // идентификаторы фабрик
    protected $SPEC_ODA_ID      = 'b5e91d86-a58a-11e5-96ed-0025907c0298';
    protected $WORK_SHOES_ID    = 'f59a4d06-2f35-11e7-8fdb-0025907c0298'; // это обувь

    /** @var \API\v1\Models\Order\Order Модель заказа из личного кабинета */
    private $order;

    /** @var \CIBlockElement Класс для работы с инфоблоками Битрикс */
    private $CIBlockElement;

    /** @var array Массив связанных элементов Инфоблока (используется для обновления) */
    private array $Links = [];

    /** @var \API\v1\Models\Order\Position[] Позиции с обувью */
    public $arShoesPositions = [];

    /** @var \API\v1\Models\Order\Position[] (предзаказа) Позиции с обувью */
    public $arShoesPositionsPre = [];

    /** @var \API\v1\Models\Order\Position[] Позиции */
    public $arPositions = [];

    /** @var \API\v1\Models\Order\Position[] (предзаказ) Позиции */
    public $arPositionsPre = [];

    public function __construct(\API\v1\Models\Order\Order $Order)
    {
        $this->order = $Order;
        $this->CIBlockElement = new \CIBlockElement;

        foreach ($Order->position as $position){
            // распределяем товар обувь отдельно, остальное отдельно
            if(current($position->characteristics)->orgguid === $this->WORK_SHOES_ID){
               $this->arShoesPositions[] = $position;
            }else{
                $this->arPositions[] = $position;
            }
        }

        foreach ($Order->position_presail as $position){
            // распределяем предзаказанные товары обувь отдельно, остальное отдельно
            if(current($position->characteristics)->orgguid === $this->WORK_SHOES_ID){
                $this->arShoesPositionsPre[] = $position;
            }else{
                $this->arPositionsPre[] = $position;
            }
        }
    }

    /**
     * Создать позицию из данных характеристик для 1С. Пока что для СпецОды БОТИНОК.
     *
     * @param array $data Данные характеристики для 1С
     *
     * @return \API\v1\Models\Order\Position Позиция
     */
    public function CreateOrderPositionFrom1CPrepareData(array $data): \API\v1\Models\Order\Position {
        return new \API\v1\Models\Order\Position([
            'guid' => $data['productid'],
            'characteristics' => [
                [
                    'guid' => $data['characteristicsid'],
                    'orgguid' => $this->SPEC_ODA_ID,
                    'quantity' => (float) $data['quantity'],
                    'discount' => (float) $data['discount'],
                    'fullprice' => (float) $data['price']
                ]
            ]
        ]);
    }

    /**
     * Записать позиции в Битрикс
     *
     * @param string $orderRootId Идентификатор общего заказа
     * @param string $userId Идентификатор пользователя
     * @param \API\v1\Models\Order\Position[] $positions Стэк позиций
     * @param int $iblockId 48 - заказы, 49 - предзаказы
     * @param int $factory  1 фабрика рабочей обуви, 2 эксперт спецодежда
     *
     * @return int Идентификатор созданного элемента битрикс
     */
    public function WriteAnyPositionInBitrixDB(
        string $orderRootId,
        string $userId,
        array $positions,
        int $iblockId = 48,
        int $factory = 2
    ): int{
        if(empty($positions))
            return 0;

        $list = [];

        foreach ($positions as $position){
            $list[] = serialize($position->AsArray());
        }
        //var_dump($list);
        //die();

        return $this->WriteInBitrixIBlock([
            'MODIFIED_BY'       => 1,
            'IBLOCK_SECTION_ID' => false,
            'IBLOCK_ID'         => $iblockId,
            'PROPERTY_VALUES'   => [
                'FACTORY'   => ['VALUE' => $factory], //enum: 1 фабрика рабочей обуви, 2 эксперт спецодежда
                'POSITIONS' => $list //string
            ],
            'NAME'              => $orderRootId .'#'.$userId, //имя заказа
            'ACTIVE'            => 'Y',            // активен
        ]);
    }

    /**
     * Записать все заполненные позиции в инфоблоки битрикса.
     *
     * @param string $orderRootId Общий идентификатор заказа.
     * @param int $userId Идентификатор учетной записи пользователя в Битрикс.
     *
     * @return array Массив с идентификаторами позиции.
     */
    public function WritePositionStackInBitrixDB(string $orderRootId, string $userId): array{

        /**
         * @var array Массив связанных заказов
         *
         * - positions --
         * - positions_pre --
         * - shoes -- обувь
         * - shoes_pre -- предзаказ обуви
         */
        $arLinkerOrder = [];

        if($this->arPositions){
            $arLinkerOrder['positions'] = $this->WritePosition($orderRootId,$userId);
        }

        if($this->arPositionsPre){
            $arLinkerOrder['positions_pre'] = $this->WritePositionPre($orderRootId,$userId);
        }

        if($this->arShoesPositions){
            $arLinkerOrder['shoes'] = $this->WriteShoesPosition($orderRootId,$userId);
        }

        if($this->arShoesPositionsPre){
            $arLinkerOrder['shoes_pre'] = $this->WriteShoesPositionPre($orderRootId,$userId);
        }

        return $arLinkerOrder;
    }

    public function WritePosition(string $orderRootId, string $userId): int {
        if(empty($this->arPositions))
            return 0;

        $list = [];

        foreach ($this->arPositions as $position){
            $list[] = serialize($position->AsArray());
        }
        //var_dump($list);
        //die();

        return $this->WriteInBitrixIBlock([
            'MODIFIED_BY'       => 1,
            'IBLOCK_SECTION_ID' => false,
            'IBLOCK_ID'         => 48,
            'PROPERTY_VALUES'   => [
                'FACTORY'   => ['VALUE' => 2], //enum: 1 фабрика рабочей обуви, 2 эксперт спецодежда
                'POSITIONS' => $list //string
            ],
            'NAME'              => $orderRootId .'#'.$userId, //имя заказа
            'ACTIVE'            => 'Y',            // активен
        ]);
    }

    public function WritePositionPre(string $orderRootId, string $userId): int {
        if(empty($this->arPositionsPre))
            return 0;

        $list = [];

        foreach ($this->arPositionsPre as $position){
            $list[] = serialize($position->AsArray());
        }

        return $this->WriteInBitrixIBlock([
            'MODIFIED_BY'       => 1,
            'IBLOCK_SECTION_ID' => false,
            'IBLOCK_ID'         => 49,
            'PROPERTY_VALUES'   => [
                'FACTORY'   => ['VALUE' => 4], //enum: 3 фабрика рабочей обуви, 4 эксперт спецодежда
                'POSITIONS' => $list //string
            ],
            'NAME'              => $orderRootId .'#'.$userId, //имя заказа
            'ACTIVE'            => 'Y',            // активен
        ]);
    }

    public function WriteShoesPosition(string $orderRootId, string $userId): int {
        if(empty($this->arShoesPositions))
            return 0;

        $list = [];

        foreach ($this->arShoesPositions as $position){
            $list[] = serialize($position->AsArray());
        }

        return $this->WriteInBitrixIBlock([
            'MODIFIED_BY'       => 1,
            'IBLOCK_SECTION_ID' => false,
            'IBLOCK_ID'         => 48,
            'PROPERTY_VALUES'   => [
                'FACTORY'   => ['VALUE' => 1], //enum: 1 фабрика рабочей обуви, 2 эксперт спецодежда
                'POSITIONS' => $list //string
            ],
            'NAME'              => $orderRootId .'#'.$userId, //имя заказа
            'ACTIVE'            => 'Y',            // активен
        ]);
    }

    public function WriteShoesPositionPre(string $orderRootId, string $userId): int {
        if(empty($this->arShoesPositionsPre))
            return 0;

        $list = [];

        foreach ($this->arShoesPositionsPre as $position){
            $list[] = serialize($position->AsArray());
        }

        return $this->WriteInBitrixIBlock([
            'MODIFIED_BY'       => 1,
            'IBLOCK_SECTION_ID' => false,
            'IBLOCK_ID'         => 49,
            'PROPERTY_VALUES'   => [
                'FACTORY'   => ['VALUE' => 3], //enum: 3 фабрика рабочей обуви, 4 эксперт спецодежда
                'POSITIONS' => $list //string
            ],
            'NAME'              => $orderRootId .'#'.$userId, //имя заказа
            'ACTIVE'            => 'Y',            // активен
        ]);
    }

    /**
     * Установить ссылки элементов инфоблока. (для обновления)
     *
     * @param array $links массив с ссылками элементов инфоблока.
     *
     * @return $this контектс класса
     */
    public function SetLinks(array $links): \API\v1\Managers\Order {
        $this->Links = $links;
        return $this;
    }

    public function GetLinks(): array{
        return $this->Links;
    }

    /**
     * Обновляет все позиции.
     * @param int $userID Идентификатор пользователя.
     *
     * @return array Массив с идентификаторами инфоблоков
     */
    public function Update(int $userID): array {
        if($this->arPositions){
            if(!$this->UpdatePosition())
                $this->Links['positions'] = $this->WritePosition($this->order->GetId(),$userID);
        }

        if($this->arPositionsPre){
            if(!$this->UpdatePositionPre())
                $this->Links['positions_pre'] = $this->WritePositionPre($this->order->GetId(),$userID);
        }

        if($this->arShoesPositions){
            if(!$this->UpdateShoesPosition())
                $this->Links['shoes'] = $this->WriteShoesPosition($this->order->GetId(),$userID);
        }

        if($this->arShoesPositionsPre){
            if(!$this->UpdateShoesPositionPre())
                $this->Links['shoes_pre'] = $this->WriteShoesPositionPre($this->order->GetId(),$userID);
        }

        return $this->GetLinks();
    }

    public function UpdatePosition() : bool {
        if(array_key_exists('positions',$this->Links) && $this->arPositions){
            $this->UpdatePositionsBitrixIBlockElement($this->Links['positions'],$this->arPositions);
            return true;
        }

        return false;
    }

    public function UpdatePositionPre() : bool {
        if(array_key_exists('positions_pre',$this->Links) && $this->arPositionsPre){
            $this->UpdatePositionsBitrixIBlockElement($this->Links['positions_pre'],$this->arPositionsPre);
            return true;
        }

        return false;
    }

    public function UpdateShoesPosition() : bool {
        if(array_key_exists('shoes',$this->Links) && $this->arShoesPositions){
            $this->UpdatePositionsBitrixIBlockElement($this->Links['shoes'],$this->arShoesPositions);
            return true;
        }

        return false;
    }

    public function UpdateShoesPositionPre() : bool {
        if(array_key_exists('shoes_pre',$this->Links) && $this->arShoesPositionsPre){
            $this->UpdatePositionsBitrixIBlockElement($this->Links['shoes_pre'],$this->arShoesPositionsPre);
            return true;
        }

        return false;
    }

    /**
     * Обновляет свойство позиции элемента в Битрикс
     *
     * @param int $element_id идентификатор элемента битрикс
     * @param array $data данные в виде массива
     */
    private function UpdatePositionsBitrixIBlockElement(int $element_id, array $data){
        if(empty($data))
            return 0;

        $list = [];

        foreach ($data as $position){
            $list[] = serialize($position->AsArray());
        }

        //Метод возвращает Null.
        \CIBlockElement::SetPropertyValuesEx(
            $element_id,//int ELEMENT_ID,
            false,//int IBLOCK_ID,
            [
                'POSITIONS' => $list
            ]//array PROPERTY_VALUES,
        //array FLAGS = array()
        );
    }

    /**
     * Запись в инфоблок Битрикс.
     *
     * @param array $arLoadProductArray Массив с конфигурацией
     *
     * @return int Идентификатор записи или 0 если ошибка.
     *
     * @see https://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/add.php
     */
    private function WriteInBitrixIBlock(array $arLoadProductArray): int {
        return (int) $this->CIBlockElement->Add($arLoadProductArray);
    }
}