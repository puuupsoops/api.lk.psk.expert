<?php

namespace API\v1\Models;

include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/Base.php';

/**
 * Модель полей персональных настроек.
 */
class PersonalSettings extends \API\v1\Models\Base
{
    /** @var string Имя */
    public string $name = '';

    /** @var string Фамилия */
    public string $lastname = '';

    /** @var string Отчество */
    public string $patronymic = '';

    /** @var string Адрес электронной почты */
    public string $email = '';

    /** @var string Номер телефона (в формате +79993332211) */
    public string $phone = '';

    /** @var string Файл-Изображения или URL Путь к изображению */
    public string $image = '';

}