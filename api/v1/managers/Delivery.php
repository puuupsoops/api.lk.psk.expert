<?php

namespace API\v1\Managers;
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/DeliveryPointEx.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/DeliveryPoint.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/Environment.php';

\Bitrix\Main\Loader::includeModule('iblock');

/**
 *  Class Delivery
 *  Класс для работы с доставкой.
 */
class Delivery
{
    /** @var int Идентификатор пользователя в битрикс */
    private $bitrixUserId = 0;

    /** @var int Идентификатор связанного инфоблока с пользователем */
    private $id = 0;

    const IBLOCK_ID = 45;

    public function __construct(array $user)
    {
        if(!$user)
            throw new \Exception(__CLASS__ . ': Отсутствуют данные о пользователе', 400);

        $this->bitrixUserId = (int)$user['id'];
        $this->id = (int)$user['config'];
    }

    public function UpdatePoint(\API\v1\Models\DeliveryPointEx $point): bool{
        /** @var array $list  Существующие точки */
        $list = $this->GetList();

        if(!$list)
            return false;

        $updatable_point = $point->AsArray();
        foreach ($list as &$point){
            if($point['index'] === $updatable_point['index']){
                $point = $updatable_point;
                break;
            }
        }

        \CIBlockElement::SetPropertyValuesEx(
            $this->id,
            self::IBLOCK_ID,
            ['DELIVERY_LIST' => json_encode($list)]
        );

        return true;

    }

    public function AddPoint(\API\v1\Models\DeliveryPointEx $point): bool {
        /** @var array $list  Существующие точки */
        $list = $this->GetList();
        $new_point = $point->AsArray();
        $count = count($list);

        $new_point['index'] = $count;
        $list[$count] = $new_point;

        \CIBlockElement::SetPropertyValuesEx(
            $this->id,
            self::IBLOCK_ID,
            ['DELIVERY_LIST' => json_encode($list)]
        );

        return true;
    }

    public function DeletePoint(int $index): bool{
        /** @var array $list  Существующие точки */
        $list = $this->GetList();

        if(!$list)
            return false;

        $new_list = array_filter($list,function($value) use ($index) {
            if($value['index'] === $index)
                return false;
            return true;

        } );

        // переопределяем числовые ключи
        $new_list = array_values($new_list);

        // обновляем индексы в соответствии с ключами массива
        foreach ($new_list as $key => &$point){
            $point['index'] = $key;
        }

        \CIBlockElement::SetPropertyValuesEx(
            $this->id,
            self::IBLOCK_ID,
            ['DELIVERY_LIST' => json_encode($new_list)]
        );

        return true;
    }

    public function GetList(): array{

        $arResult = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => self::IBLOCK_ID,
                'ID' => $this->id
            ],
            false,
            false,
            ['ID', 'PROPERTY_DELIVERY_LIST']);

        $obj = $arResult->Fetch()['PROPERTY_DELIVERY_LIST_VALUE'];

        return ($obj) ? json_decode($obj,true) : [];

    }
}