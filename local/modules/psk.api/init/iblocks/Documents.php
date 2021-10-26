<?php
// Инфоблок: Документы Контракты
/**
 * @var array $arSettings Массив с идентификаторами инфоблоков
 */

/**
 * @var int Очередь исполнения
 */
$executeQueue = 4;

/**
 * @var Array Предварительные установки для создания инфоблока
 */
$arSetting = [
    'NAME'          => 'Документы', //наименование для инфоблока
    'CODE'          => 'DOCUMENTS', //символьный код для инфоблока
    'XML_ID'        => 'DOCUMENTS', //внешний код для инфоблока
    'API_CODE'      => 'Documents', //символьный код API для инфоблока
    'SITE_ID'       => ['s1'], // идентификаторы сайта, к которым будет привязан инфоблок.
    'IBLOCK_TYPE_ID' => 'storages', //тип инфоблока, (группа-родитель)
    'VERSION'       => '2', //способ хранения свойст инфоблока | 1 в общей, 2 в отдельной. (по умолчанию в общей)
    'ACTIVE'        => 'Y', //флаг активности инфоблока, Y - активен, N - нет.
    'SORT'          => '500', //сортировка (по умолчанию 500)
];

/**
 * @var Array Массив для добавления свойств инфоблока
 */
$arPropertyField = [
    [
        'NAME' => 'Долг',
        'ACTIVE' => 'Y',
        'SORT' => '500',
        'CODE' => 'DEBT',
        'IS_REQUIRED' => 'N',
        'PROPERTY_TYPE' => 'N',
    ],
    [
        'NAME' => 'Срок оплаты',
        'ACTIVE' => 'Y',
        'SORT' => '500',
        'CODE' => 'EXPIRES',
        'IS_REQUIRED' => 'N',
        'PROPERTY_TYPE' => 'S',
        'USER_TYPE' => 'DateTime'
    ],
    [
        'NAME' => 'Контракт',
        'ACTIVE' => 'Y',
        'SORT' => '500',
        'CODE' => 'CONTRACT',
        'IS_REQUIRED' => 'N',
        'PROPERTY_TYPE' => 'E',
        'LINK_IBLOCK_ID' => $arSettings['Contracts'] //инфоблок для привязки
    ],
    [
        'NAME' => 'Контрагент',
        'ACTIVE' => 'Y',
        'SORT' => '500',
        'CODE' => 'PARTNER',
        'IS_REQUIRED' => 'N',
        'PROPERTY_TYPE' => 'E',
        'LINK_IBLOCK_ID' => $arSettings['Partners'] //инфоблок для привязки
    ]
];