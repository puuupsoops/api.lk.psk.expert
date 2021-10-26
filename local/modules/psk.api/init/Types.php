<?php
/**
 * @var array Типы инфоблоков с идентификаторами
 */
$arCreateTypes = [
    [
        'ID' => 'partner',
        'SECTIONS' => 'N',
        'IN_RSS' => 'N',
        'SORT' => '500',
        'LANG' => [
            'ru' => [
                'NAME'=>'Контрагенты',
                'SECTION_NAME'=>'',
                'ELEMENT_NAME'=>'Контрагенты'
            ],
            'en' => [
                'NAME'=>'Partners',
                'SECTION_NAME'=>'',
                'ELEMENT_NAME'=>'Partners'
            ]
        ]
    ],
    [
        'ID' => 'storages',
        'SECTIONS' => 'Y',
        'IN_RSS' => 'N',
        'SORT' => '500',
        'LANG' => [
            'ru' => [
                'NAME'=>'Склады',
                'SECTION_NAME'=>'Склады',
                'ELEMENT_NAME'=>'Склады'
            ],
            'en' => [
                'NAME'=>'Storages',
                'SECTION_NAME'=>'Storages',
                'ELEMENT_NAME'=>'Storages'
            ]
        ]
    ],
    [
        'ID' => 'users',
        'SECTIONS' => 'N',
        'IN_RSS' => 'N',
        'SORT' => '500',
        'LANG' => [
            'ru' => [
                'NAME'=>'Пользователи',
                'SECTION_NAME'=>'',
                'ELEMENT_NAME'=>'Пользователи'
            ],
            'en' => [
                'NAME'=>'Users',
                'SECTION_NAME'=>'',
                'ELEMENT_NAME'=>'Users'
            ]
        ]
    ]
];