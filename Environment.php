<?php

/**
 * Environment class
 * Класс с константами
 */
class Environment
{
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

    #endregion
}