<?php
$_SERVER['DOCUMENT_ROOT'] = '/home/bitrix/www';
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

include_once $_SERVER['DOCUMENT_ROOT'] . '/Environment.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/managers/Partner.php';

use Monolog\ErrorHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

//region Настройки журналирования
$Monolog = new \Monolog\Logger(mb_strtolower(basename(__FILE__, '.php')));

$logFile = $_SERVER['DOCUMENT_ROOT'] . '/logs/cron/' . date(
        'Y/m/d'
    ) . '/' . mb_strtolower(basename(__FILE__, '.php')) . '.' . date('H') . '.log';

$Monolog->pushProcessor(new \Monolog\Processor\IntrospectionProcessor(Logger::INFO));
$Monolog->pushProcessor(new \Monolog\Processor\MemoryUsageProcessor());
$Monolog->pushProcessor(new \Monolog\Processor\MemoryPeakUsageProcessor());
$Monolog->pushProcessor(new \Monolog\Processor\ProcessIdProcessor());
$Monolog->pushHandler(new StreamHandler($logFile, Logger::DEBUG));

$handler = new ErrorHandler($Monolog);
$handler->registerErrorHandler([], false);
$handler->registerExceptionHandler();
$handler->registerFatalHandler();

//endregion
$Monolog->info('Старт скрипта обновления: ' . __FILE__);

$configuration = \Bitrix\Main\Config\Configuration::getInstance();
$Environment = $configuration->get('api_settings');

if(!$Environment){
    $Monolog->error('Ошибка обновления, не найдена конфгурация в файле /bitrix/.settings.php',['$Environment' => $Environment]);
    $Monolog->close();
    die('Ошибка обновления, не найдена конфгурация в файле /bitrix/.settings.php');
}

/**
 * Определяет склад
 *
 * @param string $xml_id uid склада
 * @return string ID секции в битрикс
 */
function setStorage(string $xml_id): string{
    global $Environment;

    if($xml_id === 'b5e91d86-a58a-11e5-96ed-0025907c0298'){
        //return Environment::IBLOCK_SECTION_STORAGE__CONTRACT__SPEC_ODA['ID'];
        return $Environment['iblocks']['sections']['Contracts']['SPEC_ODA'];
    }else{
        //return Environment::IBLOCK_SECTION_STORAGE__CONTRACT__WORK_SHOES['ID'];
        return $Environment['iblocks']['sections']['Contracts']['WORK_SHOES'];
    }
}

\Bitrix\Main\Loader::includeModule('iblock');

//$source =  'http://10.68.5.205/stimul_test_maa/hs/ex/partners?active=true';
$source =  'http://10.68.5.241/stimul/hs/ex/partners?active=true';
$result = file_get_contents($source, false, stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Authorization: Basic ' . \Environment::AUTH_1C_KEY_BASE
    ]
]));
$result = mb_substr(trim($result), 2, -1);

$arActiveList = json_decode($result,true)['response'];

$Monolog->debug('Активные контрагенты из 1С',['data' => $arActiveList]);

$Monolog->info('Старт очистки документов.');
//чистим документы
$CIBlockElement = CIBlockElement::GetList(
    [],
    ['IBLOCK_ID' => [46,47]],
    false,
    false,
    ['ID', 'NAME']
);

while($arResult = $CIBlockElement->Fetch()){
    \CIBlockElement::Delete($arResult['ID']);
}
$Monolog->info('Завершение очистки документов');

foreach($arActiveList as $current){

    $Partner = new \API\v1\Managers\Partner();

    $partner = $current['guid'];
    $source =  'http://10.68.5.241/stimul/hs/ex/' . 'partner/' . $partner . '/';
    $result = file_get_contents($source,false, stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Authorization: Basic ' . \Environment::AUTH_1C_KEY_BASE
        ]
    ]));
    $result = mb_substr(trim($result), 2, -1);

    $arHttpData     = json_decode($result,true)['response'][0];

    $Monolog->debug('Получен контрагент из 1С.', ['data' => $arHttpData]);

    #unset($arHttpData['storages']);
    unset($arHttpData['documents']);

    try {
        // достаём запись контрагента из базы
        $Partner = $Partner->GetByGUID($arHttpData['uid']);

        /** @var array $PartnerData данные контрагента на стороне битрикс */
        $PartnerData = $Partner->AsArray();

        $Monolog->debug('Достаём запись контрагента из базы',['data' => $PartnerData]);

        // сравниваем данные
        if(
            $PartnerData['name'] !== $arHttpData['name'] ||
            $PartnerData['city'] !== $arHttpData['city'] ||
            $PartnerData['phone'] !== $arHttpData['phone'] ||
            $PartnerData['email'] !== $arHttpData['email'] ||
            $PartnerData['address'] !== $arHttpData['address'] ||
            $PartnerData['inn'] !== $arHttpData['inn'] ||
            $PartnerData['bik'] !== $arHttpData['bik'] ||
            $PartnerData['payment'] !== $arHttpData['payment'] ||
            $PartnerData['correspondent'] !== $arHttpData['correspondent'] ||
            $PartnerData['managerUid'] !== $arHttpData['manager_uid'] ||
            $PartnerData['managerName'] !== $arHttpData['manager_name']
        )
        {
            //если есть различия, обновляем данные записи.

            \CIBlockElement::SetPropertyValuesEx(
                $Partner->Id(),//element id
                $Environment['iblocks']['Partners'],//iblock Id
                [
                    'NAME' => $arHttpData['name'],
                    'UID' => $arHttpData['uid'],
                    'CITY' => $arHttpData['city'],
                    'PHONE' => $arHttpData['phone'],
                    'EMAIL' => $arHttpData['email'],
                    'ADDRESS' => $arHttpData['address'],
                    'INN' => $arHttpData['inn'],
                    'BIK' => $arHttpData['bik'],
                    'PAYMENT' => $arHttpData['payment'],
                    'CORRESPONDENT' => $arHttpData['correspondent'],
                    'HASH' => base64_encode(serialize($arHttpData)),
                    'MANAGER_UID' => $arHttpData['manager_uid'],
                    'MANAGER_NAME' => $arHttpData['manager_name']
                ]
            );
            $Monolog->debug('Обновлен контрагент',['name' => $arHttpData['name'], 'id' => $Partner->Id()]);
            var_dump([
                'MSG' => 'Обновлен контрагент',
                'NAME' => $arHttpData['name'],
                'BITRIX_ID' => $Partner->Id()
            ]);
        }

    }catch(\Exception $e){
        $Monolog->alert('Если код 404 - не найдено, добавляем нового контрагента',['msg' => $e->getMessage(),'code' => $e->getCode()]);
        // Если код 404 - не найдено, добавляем нового контрагента
        if((int) $e->getCode() === 404){

            $el    = new \CIBlockElement;

            $PROPS = [
                'UID' => $arHttpData['uid'],
                'CITY' => $arHttpData['city'],
                'PHONE' => $arHttpData['phone'],
                'EMAIL' => $arHttpData['email'],
                'ADDRESS' => $arHttpData['address'],
                'INN' => $arHttpData['inn'],
                'BIK' => $arHttpData['bik'],
                'PAYMENT' => $arHttpData['payment'],
                'CORRESPONDENT' => $arHttpData['correspondent'],
                'HASH' => base64_encode(serialize($arHttpData)),
                'MANAGER_UID' => $arHttpData['manager_uid'],
                'MANAGER_NAME' => $arHttpData['manager_name']
            ];

            $arLoadProductArray = [
                'MODIFIED_BY'       => 1,
                'IBLOCK_SECTION_ID' => false,
                'IBLOCK_ID'         => $Environment['iblocks']['Partners'],
                'PROPERTY_VALUES'   => $PROPS,
                'NAME'              => $arHttpData['name'],
                'XML_ID'            => $arHttpData['uid'],
                'ACTIVE'            => 'Y',
            ];

            $arResult = $el->Add($arLoadProductArray);

            $Partner = $Partner->GetByBitrixID($arResult);

            $Monolog->debug('Добавлен новый контрагент',['name' => $arHttpData['name'], 'id' => $arResult,'$Partner' => $Partner->AsArray()]);

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
            'IBLOCK_ID'         => $Environment['iblocks']['Contracts'],
            'PROPERTY_VALUES'   => $PROPS,
            'NAME'              => $tmp['contract'], // название
            'ACTIVE'            => 'Y',
        ];

        $arStorageId = $el->Add($arLoadProductArray);

        $Monolog->debug('Добавлен новый контракт',['name' => $arHttpData['name'],'contract' => $tmp['contract'], 'id' => $arStorageId]);

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
                'IBLOCK_ID'         => $Environment['iblocks']['Documents'],
                'PROPERTY_VALUES'   => $PROPS,
                'NAME'              => $document['number'], // название
                'ACTIVE_FROM'       => date('d.m.Y H:m:s',strtotime($document['date'])), // дата
                'ACTIVE'            => 'Y',
            ];

            $arDocumentId = $el->Add($arLoadProductArray);

            $Monolog->debug('Добавлен новый документ', ['name' => $arHttpData['name'], 'document' => $tmp['contract'], 'id' => $arDocumentId]);

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
$Monolog->info('Завершение работы скрипта: ' . __FILE__);
$Monolog->close();