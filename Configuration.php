<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

/**
 * Конфигурация сервера api
 */
class Configuration
{
    private static $instance = null;

    private static array $arEnvironment = [];

    private function __construct(){}
    private function __clone(){}
    public function __wakeup(){ throw new Exception('Class is Singleton',500);}

    private static function Init() {

        if(!\Bitrix\Main\Loader::IncludeModule('highloadblock'))
            throw new \Exception(
                'Ошибка конфигурации окружения сервера API. Отсутствует модуль highloadblock',
                500
            );

        //region компилируем сущности highloadblock'ов
        \Bitrix\Highloadblock\HighloadBlockTable::compileEntity(
            \Bitrix\Highloadblock\HighloadBlockTable::getList(['filter'=>['=NAME'=>'ApiEnvironment']])->fetch()
        );
        //endregion

        $EnvironmentResult = \ApiEnvironmentTable::getList(['select' => ['*']]);

        $arEnvironmentResult = [];
        while($item = $EnvironmentResult->fetch()){
            $arEnvironmentResult[$item['UF_CODE']] = $item['UF_VALUE'];
        }

        // устанавливаем массив с переменными окружениями api-сервера
        self::$arEnvironment = $arEnvironmentResult;
    }

    public static function GetInstance(): \Configuration {

        if (!isset(self::$instance)) {
            self::Init();
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * Флаг боевой конфигурации сервера api.
     *
     * @return bool true - боевой сервер, false - тестовый.
     */
    public static function IsProduction(): bool {
        return self::GetBooleanValue('PRODUCTION');
    }

    /**
     * Флаг дебага конфигурации сервера api.
     *
     * @return bool true - дебаг включен, false - дебаг выключен.
     */
    public static function IsDebugMode() : bool {
        return self::GetBooleanValue('DEBUG');
    }

    /**
     * Получить значение в виде булевого типа.
     *
     * @param string $key Ключ
     * @return bool Значение
     */
    private static function GetBooleanValue(string $key): bool {
        if(self::$arEnvironment[$key] === 'Y')
            return true;
        return false;
    }
}