<?php
namespace API\v1\Models;

include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/BaseModelEx.php';

/**
 * Внешняя модель данных контрагента
 * 
 * @package API\v1\Models
 */
class PartnerEx extends \API\v1\Models\BaseModelEx {

    /**
     * @var string Уникальный идентификатор записи в базе 1С
     */
    protected string $uid;
    
    /**
     * @var string Наименование контрагента
     */
    protected string $name;

    /**
     * @var string Город
     */
    protected string $city;

    /**
     * @var string Контактный телефон
     */
    protected string $phone;

    /**
     * @var string Адрес электронной почты
     */
    protected string $email;

    /**
     * @var string Адрес
     */
    protected string $address;

    /**
     * @var string ИНН
     */
    protected string $inn;

    /**
     * @var string БИК
     */
    protected string $bik;

    /**
     * @var string Расчётный счёт
     */
    protected string $payment;

    /**
     * @var string Корреспондентский счёт
     */
    protected string $correspondent;

    /** @var string Идентификатор менеджера */
    protected string $manager_uid;

    /** @var string  ФИО менеджера */
    protected string $manager_name;
}