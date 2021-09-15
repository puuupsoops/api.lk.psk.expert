<?php
namespace API\v1\Models;

/**
 * Внешняя модель данных контрагента
 * 
 * @package API\v1\Models
 */
class PartnerEx {
    /**
     * @var string Уникальный идентификатор записи в базе 1С
     */
    protected $uid;
    
    /**
     * @var string Наименование контрагента
     */
    protected $name;

    /**
     * @var string Город
     */
    protected $city;

    /**
     * @var string Контакнтный телефон
     */
    protected $phone;

    /**
     * @var string Адрес электронной почты
     */
    protected $email;

    /**
     * @var string Адрес
     */
    protected $address;

    /**
     * @var string ИНН
     */
    protected $inn;

    /**
     * @var string БИК
     */
    protected $bik;

    /**
     * @var string Рассчётный счёт
     */
    protected $payment;

    /**
     * @var string Корреспондентский счёт
     */
    protected $correspondent;

    /**
     * Получить значения модели в виде массива
     */
    public function AsArray(){
        return get_object_vars($this);
    }
}