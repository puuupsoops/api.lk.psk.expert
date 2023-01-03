<?php

namespace API\v1\Managers;

include_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/Token.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/User.php';

include_once $_SERVER['DOCUMENT_ROOT'] . '/Environment.php';

\Bitrix\Main\Loader::includeModule('iblock');

/**
 * Репозиторий работы с отгрузками
 */
class Shipment
{

    /** @var \API\v1\Models\Token|null Токен пользователя */
    protected ?\API\v1\Models\Token $token = null;

    /** @var int Идентификатор хранилища заявок пользователя */
    protected int $id = 0;

    /**
     * @param \API\v1\Models\Token $token Модель токена
     *
     * @throws \Exception
     */
    public function __construct(\API\v1\Models\Token $token)
    {
        $this->token = $token;

        $this->id = $this->Init();
    }

    /**
     * Получить идентификатор секции хранилища заявок пользователя
     *
     * @return int Идентификатор хранилища заявок пользователя.
     */
    public function GetSectionId():int { return $this->id; }

    public function Add(array $data, array $files = []):int {
        /** @var array $arData Данные */
        $arData = $data;

        if(!array_key_exists('title',$arData)) {
            $title = sprintf('Заказ № %s от $s', $arData['id'] ?? 0, date('d.m.Y'));
        }else{
            $title = $arData['title'];
        }

        return $this->CIBlockElementAdd($title,$this->PrepareProperties($arData,$files));
    }

    public function Update() {

    }

    /**
     * Подготовить свойства
     *
     * @param array $data Данные
     * @param array $files Массив с подготовленными в Битрикс файлами \CFile::MakeFileArray($fileId, '/shipment');
     * @return array
     */
    private function PrepareProperties(array $data, array $files = []):array {
        /** @var array $arProps Массив свойств элемента */
        $arProps = [
            'PARTNER_NAME'  => $data['partner_name'],   // Контрагент
            'PARTNER_GUID'  => $data['partner_guid'],   // Идентификатор контрагента
            'ORDER_ID'      => $data['id'],       // Идентификатор общего заказа
            'AMOUNT'        => $data['amount'], // Количество едениц
            'WEIGHT'        => $data['weight'], // Общий вес
            'VOLUME'        => $data['volume'], // Общий объем
            'DATE_SHIPMENT' => date('d.m.Y',((int)$data['date']/1000)), // Дата отгрузки
            'ADDRESS'       => $data['address'], // Адрес
            'COMMENT'       => $data['comment'], // Комментарий
            'REPRESENT'     => ['VALUE' => ['TEXT' => $data['message'], 'TYPE' => 'html']], // HTML/TEXT Представление
        ];

        //shipment status Вид отгрузки. [0 - Самовывоз, 1 - Доставка, 2 - До транспортной]
        switch ((int)$data['case']) {
            case 1 : $arProps['CASE'] = 10; // код из битрикса: Доставка
                break;
            case 2 : $arProps['CASE'] = 11; // код из битрикса: До транспортной
                break;

            default: $arProps['CASE'] = 9; // код из битрикса: Самовывоз
                break;
        }

        //Транспортные компании
        if($arProps['CASE'] === 11) {
            //region carriers status Транспортные компании, если не до транспортной,
            //       то пустой параметр или 0 [1 - другая, 2 - ПЭК, 3 - Деловые линии, 4 - Байкал]
            $arProps['CARRIERS']  = ['VALUE' => 'other'];//(int)$arParsedBody['carriers']
            switch ((int)$data['carriers']){
                case 2 : $arProps['CARRIERS'] = ['VALUE' => 'pek']; // код из битрикса: Доставка
                    break;
                case 3 : $arProps['CARRIERS'] = ['VALUE' => 'lines']; // код из битрикса: До транспортной
                    break;
                case 4 : $arProps['CARRIERS'] = ['VALUE' => 'baikal']; // код из битрикса: До транспортной
                    break;
                default: $arProps['CASE'] = ['VALUE' => 'other']; // код из битрикса: Самовывоз
                    break;
            }
        }

        //Дополнительные условия к доставке
        if(!empty($data['extra'])) {
            //Дополнительное условие к доставке, если есть.
            // [1 - Жесткая упаковка, 2 - Ополечивание], перечисление через массив. или пустой параметр
            foreach ($data['extra'] as $item){
                if((int)$item === 1)
                    $arProps['EXTRA'][] = 12; // Жесткая упаковка

                if((int)$item === 2)
                    $arProps['EXTRA'][] = 13; // Ополечивание
            }
        }

        //Срочно
        if((int)$data['urgently'] === 1) {
            $arProps['IS_URGENTLY'] = 14; // Да
        }

        // Файлы, вносить результаты \CFile::MakeFileArray($fileId, '/shipment');
        if(!empty($files)) {
            $arProps['FILES'] = $files;
        }

        return $arProps;
    }
    /**
     * Добавить элемент в Битрикс.
     *
     * @param string $title Название записи
     * @param array $arProps Массив свойств для записи
     *
     * @return int Идентификатор записи
     *
     * @throws \Exception
     */
    private function CIBlockElementAdd(string $title, array $arProps):int {
        $CIBlockElement = new \CIBlockElement;

        if($ElementId = $CIBlockElement->Add([
            'MODIFIED_BY'    => 1, // элемент изменен текущим пользователем
            'IBLOCK_SECTION_ID' => $this->id, // идентификатор секции, выбирается выше по коду
            'IBLOCK_ID'      => \Environment::IBLOCK_ID_SHIPMENTS,
            'PROPERTY_VALUES'=> $arProps,
            'NAME'           => $title, // title сообщения
            'ACTIVE'         => 'Y',            // активен
            //'DETAIL_TEXT'    => $arParsedBody['comment'], // текст сообщения (Сопроводительный текст претензии)
        ])){
            return (int)$ElementId;
        }else{
            throw new \Exception($CIBlockElement->LAST_ERROR, 400);
        }
    }

    private function Init(): int {

        $CIBlockElement = \CIBlockElement::GetList(
            [],
            ['ID' => $this->token->GetConfig()], false,false,['ID', 'NAME', 'PROPERTY_SHIPMENTS','PROPERTY_CLAIMS']);

        $arElement = $CIBlockElement->Fetch();

        if(empty($arElement)) {
            throw new \Exception('Не найдена Конфигурация пользователя.',404);
        }

        return $arElement['PROPERTY_SHIPMENTS_VALUE'] ?? $this->CreateFolder();
    }

    /**
     * Создает хранилище для заявок пользователя.
     *
     * @return int Идентификатор секции инфоблока.
     * @throws \Exception
     */
    private function CreateFolder(): int {

        global $USER;
        if( $arUser = $USER->GetByID($this->token->GetId())
            ->Fetch()) {
            $name = sprintf('%s %s %s #%s',
                $arUser['LAST_NAME'] ?? $arUser['LAST_NAME'],
                $arUser['NAME'] ?? '',
                $arUser['SECOND_NAME'] ?? '',
                $arUser['ID']
            );

        } else{ throw new \Exception('Не найден пользователь по ID.',404); }

        $CIBlockSection = new \CIBlockSection;

        if($sectionId = $CIBlockSection->Add([
            'IBLOCK_ID' => \Environment::IBLOCK_ID_SHIPMENTS,
            'NAME' => $name,
        ]))
        {
            // записываем id раздела
            \CIBlockElement::SetPropertyValuesEx(
                $this->token->GetConfig(),
                \Environment::GetInstance()['iblocks']['Users'],
                ['SHIPMENTS' => $sectionId],
            );
        }else{ throw new \Exception('Не удается создать раздел для заявок.',409);}

        return (int)$sectionId;
    }
}