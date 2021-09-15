<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/Environment.php';

\Bitrix\Main\Loader::includeModule('iblock');

$partner = 'f168528d-631b-11df-bfa2-0050569a3a91';
$source =  Environment::CURRENT_1C_BASE_URL_PATH . 'partner/' . $partner . '/';
$result         = file_get_contents($source);
$result = mb_substr(trim($result), 2, -1);

$arHttpData     = json_decode($result,true)['response'][0];

unset($arHttpData['storages']);
unset($arHttpData['documents']);

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
var_dump($arResult);
