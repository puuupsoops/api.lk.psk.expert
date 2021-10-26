<?php

/**
 * @var \Bitrix\Main\Config\Configuration Экземпляр класса конфигуратора, (работа с файлом .settings.php)
 */
$configuration = \Bitrix\Main\Config\Configuration::getInstance();
$arConfig = $configuration->get('api_settings');

/**
 * Environment class
 * Класс с константами
 */
class Environment
{
    /**
     * @var string Приватный ключ для Токенов
     */
    public const JWT_PRIVATE_KEY = 'XYZabc';

    #region 1C OPTIONS

    /**
     * @var string Хост базы 1С
     */
    private const HOST_NAME_1C = '10.68.5.205';
    
    /**
     * @var string Имя базы 1С
     */
    private const BASE_1C_NAME = 'stimul_test_maa';

    /**
     * @var string Адрес хоста secure
     */
    public const HOST_NAME_HTTPS = 'https://' . self::HOST_NAME_1C;
    
    /**
     * @var string Адрес хоста
     */
    public const HOST_NAME_HTTP = 'http://' . self::HOST_NAME_1C;
    
    /**
     * @var string Актуальное значение URL базы 1С
     */
    public const CURRENT_1C_BASE_URL_PATH = self::HOST_NAME_HTTP . '/' . self::BASE_1C_NAME . '/hs/ex/';
    
    #endregion

    #region Типы инфоблока
    
    /**
     * @var string Идентификатор Типа Битрикс инфоблока: Контрагенты
     */
    public const IBLOCK_TYPE_PARTNER    = 'partner';

    /**
     * @var string Идентификатор Типа Битрикс инфоблока: Склады
     */
    public const IBLOCK_TYPE_STORAGE    = 'storages';

    /**
     * @var string Идентификатор Типа Битрикс инфоблока: Пользователи
     */
    public const IBLOCK_TYPE_USERS    = 'users';
    
    #endregion

    #region SECTION CODE

    /**
     * @var array Данные о пользовательском поле для расширения настроект SECTION в инфоблоке битрикс Контракты
     */
    public const IBLOCK_2_SECTION = [
        # идентификатор
        'ID' => 24,
        # Объект в базе данных
        'OBJECT' => 'IBLOCK_2_SECTION',
        # Код поля
        'CODE' => 'UF_UID'
    ];

    /**
     * @var array Данные о секции инфоблока Склады: Контракты . Фабрика рабочей обуви
     */
    public const IBLOCK_SECTION_STORAGE__CONTRACT__WORK_SHOES = [
        'ID' => 2,
        'NAME' => 'ООО "Фабрика рабочей обуви"',
        'CODE' => 'WORK_SHOES',
        'HL_BLOCK_UID' => 'f59a4d06-2f35-11e7-8fdb-0025907c0298',
        'HL_BLOCK' => self::IBLOCK_2_SECTION
    ];

    /**
     * @var array Данные о секции инфоблока Склады: Контракты . Эксперт спецодежда
     */
    public const IBLOCK_SECTION_STORAGE__CONTRACT__SPEC_ODA = [
        'ID' => 1,
        'NAME' => 'ООО "Эксперт Спецодежда"',
        'CODE' => 'SPEC_ODA',
        'HL_BLOCK_UID' => 'b5e91d86-a58a-11e5-96ed-0025907c0298',
        'HL_BLOCK' => self::IBLOCK_2_SECTION
    ];
    #endregion

    #region Идентификаторы инфоблоков
    
    /**
     * @var string Идентификатор Битрикс инфоблока: Контрагенты
     */
    public const IBLOCK_ID_PARTNERS     = '1';

    /**
     * @var string Мнемонический код Битрикс инфоблока: Контрагенты
     */
    public const IBLOCK_ID_PARTNERS__CODE     = 'PARTNERS';
    
    /**
     * @var string API код Битрикс инфоблока: Контрагенты
     */
    public const IBLOCK_ID_PARTNERS__API_CODE     = 'Partners';
    
    /**
     * @var string Идентификатор Битрикс инфоблока: Контракты
     */
    public const IBLOCK_ID_CONTRACTS    = '2';
    
    /**
     * @var string Мнемонический код Битрикс инфоблока: Контракты
     */
    public const IBLOCK_ID_CONTRACTS__CODE    = 'CONTRACTS';
    
    /**
     * @var string API код Битрикс инфоблока: Контракты
     */
    public const IBLOCK_ID_CONTRACTS__API_CODE    = 'Contracts';
    
    /**
     * @var string Идентификатор Битрикс инфоблока: Документы (связанные с контрактами)
     */
    public const IBLOCK_ID_DOCUMENTS    = '3';
    
    /**
     * @var string Мнемонический код Битрикс инфоблока: Документы (связанные с контрактами)
     */
    public const IBLOCK_ID_DOCUMENTS__CODE    = 'DOCUMENTS';
    
    /**
     * @var string API код Битрикс инфоблока: Документы (связанные с контрактами)
     */
    public const IBLOCK_ID_DOCUMENTS__API_CODE    = 'Documents';

    /**
     * @var string Идентификатор Битрикс инфоблока: Настройки пользователей
     */
    public const IBLOCK_ID_USER    = '4';

    /**
     * @var string Мнемонический код Битрикс инфоблока: Настройки пользователей
     */
    public const IBLOCK_ID_USER__CODE    = 'USERS';

    /**
     * @var string API код Битрикс инфоблока: Настройки пользователей
     */
    public const IBLOCK_ID_USER__API_CODE    = 'Users';

    #endregion
}