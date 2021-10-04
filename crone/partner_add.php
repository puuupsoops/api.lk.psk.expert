<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/Environment.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/managers/Partner.php';

/**
 * Определяет склад
 *
 * @param string $xml_id uid склада
 * @return string ID секции в битрикс
 */
function setStorage(string $xml_id): string{
    if($xml_id === Environment::IBLOCK_SECTION_STORAGE__CONTRACT__SPEC_ODA['HL_BLOCK_UID']){
        return Environment::IBLOCK_SECTION_STORAGE__CONTRACT__SPEC_ODA['ID'];
    }else{
        return Environment::IBLOCK_SECTION_STORAGE__CONTRACT__WORK_SHOES['ID'];
    }
}

\Bitrix\Main\Loader::includeModule('iblock');

$source =  'http://10.68.5.205/stimul_test_maa/hs/ex/partners?active=true';
$result = file_get_contents($source);
$result = mb_substr(trim($result), 2, -1);

$arActiveList = json_decode($result,true)['response'];

foreach($arActiveList as $current){

    $Partner = new \API\v1\Managers\Partner();

    $partner = $current['guid'];
    $source =  Environment::CURRENT_1C_BASE_URL_PATH . 'partner/' . $partner . '/';
    $result = file_get_contents($source);
    $result = mb_substr(trim($result), 2, -1);

    $arHttpData     = json_decode($result,true)['response'][0];

    #unset($arHttpData['storages']);
    unset($arHttpData['documents']);

    try {
        $Partner = $Partner->GetByGUID($arHttpData['uid']);
    }catch(\Exception $e){

        # Если код 404 - не найдено, добавляем нового контрагента
        if((int) $e->getCode() === 404){

            $el    = new \CIBlockElement;

            $PROPS = [
                'UID' => $arHttpData['uid'],
                'CITY' => $arHttpData['city'],
                'PHONE' => $arHttpData[''],
                'EMAIL' => $arHttpData['email'],
                'ADDRESS' => $arHttpData['address'],
                'INN' => $arHttpData['inn'],
                'BIK' => $arHttpData['bik'],
                'PAYMENT' => $arHttpData['payment'],
                'CORRESPONDENT' => $arHttpData['correspondent'],
                'HASH' => base64_encode(serialize($arHttpData))
            ];

            $arLoadProductArray = [
                'MODIFIED_BY'       => 1,
                'IBLOCK_SECTION_ID' => false,
                'IBLOCK_ID'         => Environment::IBLOCK_ID_PARTNERS,
                'PROPERTY_VALUES'   => $PROPS,
                'NAME'              => $arHttpData['name'],
                'XML_ID'            => $arHttpData['uid'],
                'ACTIVE'            => 'Y',
            ];

            $arResult = $el->Add($arLoadProductArray);

            $Partner = $Partner->GetByBitrixID($arResult);

            var_dump([
                'MSG' => 'Добавлен новый контрагент',
                'NAME' => $arHttpData['name'],
                'ID' => $arResult
            ]);
        }
    }

    #region РАБОТАЕМ СО СКЛАДАМИ И КОНТРАКТАМИ

    # Получаем существующие склады

    foreach($arHttpData['storages'] as $storage){
        $tmp = $storage;

        # Добавляем контракт к складу
        $el    = new \CIBlockElement;

        $PROPS = [
            'DEBT'      => $tmp['debt'],    //долг
            'BALANCE'   => $tmp['balance'],    // баланс
            'DEFERMENT' => $tmp['deferment'],    // дней отсрочки
            'PAY_DATE'  => date('d.m.Y H:m:s',strtotime($tmp['date'])), //дата погашения
            'DISCOUNT'  => $tmp['discount'],    //скидка
            'SPENT'     => $tmp['spent'],    //Потрачено средств всего
            'PARTNER'   => $Partner->Id()    // id элемента с контрагентом
        ];

        $arLoadProductArray = [
            'MODIFIED_BY'       => 1,
            'IBLOCK_SECTION_ID' => setStorage($tmp['guid']), // склад
            'IBLOCK_ID'         => Environment::IBLOCK_ID_CONTRACTS,
            'PROPERTY_VALUES'   => $PROPS,
            'NAME'              => $tmp['contract'], // название
            'ACTIVE'            => 'Y',
        ];

        $arStorageId = $el->Add($arLoadProductArray);

        var_dump([
            'MSG' => 'Добавлен новый контракт',
            'NAME' => $arHttpData['name'],
            'CONTRACT' => $tmp['contract'],
            'ID' => $arStorageId
        ]);

        # ДОКУМЕНТЫ

        # создать документ
        foreach ($storage['documents'] as $document){
            $el    = new \CIBlockElement;

            $PROPS = [
                'DEBT'      => $document['debt'],    //долг
                'EXPIRES'  => date('d.m.Y H:m:s',strtotime($document['expires'])), //дата погашения
                'CONTRACT'     => $arStorageId,    // id элемента с Контрактом
                'PARTNER'   => $Partner->Id()    // id элемента с контрагентом
            ];

            $arLoadProductArray = [
                'MODIFIED_BY'       => 1,
                'IBLOCK_SECTION_ID' => false, // склад
                'IBLOCK_ID'         => Environment::IBLOCK_ID_DOCUMENTS,
                'PROPERTY_VALUES'   => $PROPS,
                'NAME'              => $document['number'], // название
                'ACTIVE_FROM'       => date('d.m.Y H:m:s',strtotime($document['date'])), // дата
                'ACTIVE'            => 'Y',
            ];

            $arDocumentId = $el->Add($arLoadProductArray);
            var_dump([
                'MSG' => 'Добавлен новый документ',
                'NAME' => $arHttpData['name'],
                'CONTRACT' => $tmp['contract'],
                'ID' => $arDocumentId
            ]);
        }

    }

    #endregion

}
