<?php

namespace Psk\Api\Orders;

/**
 *  Модель уведомлений по заказам в базе данных битрикса, уведомления для ЛК
 *
 * @see https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=4803 ORM Концепция, описание сущности
 *
 * -- создать таблицу (создано!).
 * -- \Bitrix\Main\Entity\Base::getInstance('\Psk\Api\Orders\NotifierTable')->createDbTable();
 *
 * -- добавить столбец через sql
 * ALTER TABLE notifier_order_psk ADD COLUMN имя_столбца тип_значения;
 * ALTER TABLE notifier_order_psk ADD COLUMN SHIPMENT_COST VARCHAR (20);
 *
 * -- вставка записи
 * INSERT INTO notifier_order_psk SET USER_ID = 1, CREATE_DATE = NOW(), TEXT = "Это текст тестового уведомления" ;
 */
class NotifierTable extends \Bitrix\Main\Entity\DataManager
{
    /**
     * Возвращает имя таблицы в базе данных
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return 'notifier_order_psk';

    }

    /**
     * ???
     * @return string
     */
    public static function getUfId(): string
    {
        return 'NOTIFIER_ORDER_PSK';
    }

    /**
     * Возвращает имя подключения к сущности базы данных
     *
     * @return string
     */
    public static function getConnectionName(): string
    {
        // имя сущности, если используем несколько баз данных в файле .settings.php
        // default поумолчанию
        return 'default';
    }

    /**
     * Описание полей для инициализации сущности таблицы в базе данных битрикс
     *
     * @return array
     */
    public static function getMap(): array
    {
        return [
            // Идентификатор позиции (счётчик)
            new \Bitrix\Main\Entity\IntegerField('INDEX',[
                    'primary' => true,
                    'autocomplete' => true
                ]
            ),

            // Идентификатор пользователя в Битрикс
            new \Bitrix\Main\Entity\StringField('USER_ID',[
                'required' => true
            ]),

            // Дата создания
            new \Bitrix\Main\Entity\DatetimeField('CREATE_DATE'),

            // Текст уведомления
            new \Bitrix\Main\Entity\StringField('TEXT'),

            // Дата отправки уведомления
            new \Bitrix\Main\Entity\DatetimeField('SEND_DATE'),

            // Отправлено 1 / не отправлено 0
            new \Bitrix\Main\Entity\IntegerField('IS_SEND'),

            // Дата получения пользователем уведомления
            new \Bitrix\Main\Entity\DatetimeField('RECEIVED_DATE'),

            // Получено 1 / не отправлено 0
            new \Bitrix\Main\Entity\IntegerField('IS_RECEIVED'),
        ];
    }
}