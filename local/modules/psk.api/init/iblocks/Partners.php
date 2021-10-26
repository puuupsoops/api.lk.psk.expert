<?php
// Инфоблок: Контрагенты
/**
 * @var int Очередь исполнения
 */
$executeQueue = 1;

/**
 * @var Array Предварительные установки для создания инфоблока
 */
$arSetting = [
    'NAME'          => 'Контрагенты', //наименование для инфоблока
    'CODE'          => 'PARTNERS', //символьный код для инфоблока
    'XML_ID'        => 'PARTNERS', //внешний код для инфоблока
    'API_CODE'      => 'Partners', //символьный код API для инфоблока
    'SITE_ID'       => ['s1'], // идентификаторы сайта, к которым будет привязан инфоблок.
    'IBLOCK_TYPE_ID' => 'partner', //тип инфоблока, (группа-родитель)
    'VERSION'       => '2', //способ хранения свойст инфоблока | 1 в общей, 2 в отдельной. (по умолчанию в общей)
    'ACTIVE'        => 'Y', //флаг активности инфоблока, Y - активен, N - нет.
    'SORT'          => '500', //сортировка (по умолчанию 500)
];

/**
 * @var Array Массив для добавления свойств инфоблока
 */
$arPropertyField = [
    [
        'NAME' => 'Внешний идентификатор',
        'ACTIVE' => 'Y',
        'SORT' => '500',
        'CODE' => 'UID',
        'IS_REQUIRED' => 'N',
        'PROPERTY_TYPE' => 'S',
        'SEARCHABLE' => 'Y'
    ],
    [
        'NAME' => 'Город',
        'ACTIVE' => 'Y',
        'SORT' => '500',
        'CODE' => 'CITY',
        'IS_REQUIRED' => 'N',
        'PROPERTY_TYPE' => 'S',
        'SEARCHABLE' => 'N'
    ],
    [
        'NAME' => 'Телефон',
        'ACTIVE' => 'Y',
        'SORT' => '500',
        'CODE' => 'PHONE',
        'IS_REQUIRED' => 'N',
        'PROPERTY_TYPE' => 'S',
        'SEARCHABLE' => 'N'
    ],
    [
        'NAME' => 'Электронная почта',
        'ACTIVE' => 'Y',
        'SORT' => '500',
        'CODE' => 'EMAIL',
        'IS_REQUIRED' => 'N',
        'PROPERTY_TYPE' => 'S',
        'SEARCHABLE' => 'N'
    ],
    [
        'NAME' => 'Адрес',
        'ACTIVE' => 'Y',
        'SORT' => '500',
        'CODE' => 'ADDRESS',
        'IS_REQUIRED' => 'N',
        'PROPERTY_TYPE' => 'S',
        'SEARCHABLE' => 'N'
    ],
    [
        'NAME' => 'ИНН',
        'ACTIVE' => 'Y',
        'SORT' => '500',
        'CODE' => 'INN',
        'IS_REQUIRED' => 'N',
        'PROPERTY_TYPE' => 'S',
        'SEARCHABLE' => 'Y'
    ],
    [
        'NAME' => 'БИК',
        'ACTIVE' => 'Y',
        'SORT' => '500',
        'CODE' => 'BIK',
        'IS_REQUIRED' => 'N',
        'PROPERTY_TYPE' => 'S',
        'SEARCHABLE' => 'N'
    ],
    [
        'NAME' => 'Расчётный счёт',
        'ACTIVE' => 'Y',
        'SORT' => '500',
        'CODE' => 'PAYMENT',
        'IS_REQUIRED' => 'N',
        'PROPERTY_TYPE' => 'S',
        'SEARCHABLE' => 'N'
    ],
    [
        'NAME' => 'Корреспондентский счёт',
        'ACTIVE' => 'Y',
        'SORT' => '500',
        'CODE' => 'CORRESPONDENT',
        'IS_REQUIRED' => 'N',
        'PROPERTY_TYPE' => 'S',
        'SEARCHABLE' => 'N'
    ],
    [
        'NAME' => 'Хэш сумма объекта(serialization/base64)',
        'ACTIVE' => 'Y',
        'SORT' => '500',
        'CODE' => 'HASH',
        'IS_REQUIRED' => 'Y',
        'PROPERTY_TYPE' => 'S',
        'SEARCHABLE' => 'N'
    ]
];