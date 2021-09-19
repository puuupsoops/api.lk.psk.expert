<?php
namespace API\v1\Managers;
use JetBrains\PhpStorm\ArrayShape;

include_once $_SERVER['DOCUMENT_ROOT'] . '/Environment.php';

\Bitrix\Main\Loader::includeModule('iblock');
/**
 * Класс для взаимодействия с данными пользователей
 *
 * @package API\v1\Managers
 */
class User
{
    #region Константы класса
    /**
     * @var string Идентификатор инфоблока в Битрикс
     */
    public const IBLOCK_ID = \Environment::IBLOCK_ID_USER;

    /**
     * @var string Мнемонический код
     */
    public const IBLOCK_CODE = \Environment::IBLOCK_ID_USER__CODE;

    /**
     * @var string API код
     */
    public const IBLOCK_API_CODE = \Environment::IBLOCK_ID_USER__API_CODE;
    #endregion

    /**
     * @var int Идентификатор пользователя в базе Битрикс
     */
    private $id;

    /**
     * @var int Идентификатор элемента инфоблока с конфигурацией пользователя
     */
    private $idConfig;

    /**
     * @var string Ключ
     */
    private $key;

    public function __construct(array $authData = null){

        global $USER;

        /**
         * @var bool Результат авторизации true - доступ разрешен, false - запрещен
         */
        $arAuthResult = $USER->Login($authData['username'], $authData['password'], "N");

        if($arAuthResult === true){

            $rsUser = $USER->GetByLogin($authData['username']);
            $rsUser = $rsUser->Fetch();

            $this->id       = (int) $rsUser['ID'];
            $this->idConfig = (int) $rsUser['UF_PARTNERS_LIST'];
            $this->key      = $rsUser['BX_USER_ID'];

        }else{
            throw new \Exception($arAuthResult['MESSAGE'],401);
        }
    }

    /**
     * Получить пропуск с данными
     *
     * @return array Массив с данными
     */
    #[ArrayShape(['id' => "int", 'config' => "int", 'sign'=>'string'])]
    public function GetPass(): array{
        return [
            'id' => $this->id,
            'config' => $this->idConfig,
            'sign' => $this->key
        ];
    }
}