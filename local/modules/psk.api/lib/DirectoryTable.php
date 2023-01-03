<?php
namespace Psk\Api\Orders;
//include_once $_SERVER["DOCUMENT_ROOT"]. '/local/modules/psk.api/lib/DirectoryTable.php';

/**
 *  Модель справочника по заказам в базе данных битрикса.
 *
 * @see https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=4803 ORM Концепция, описание сущности
 *
 * -- создать таблицу (создано!).
 * -- \Bitrix\Main\Entity\Base::getInstance('\Psk\Api\Orders\DirectoryTable')->createDbTable();
 *
 * -- добавить столбец через sql
 * ALTER TABLE directory_order_psk ADD COLUMN имя_столбца тип_значения;
 * ALTER TABLE directory_order_psk ADD COLUMN SHIPMENT_COST VARCHAR (20);
 */
class DirectoryTable extends \Bitrix\Main\Entity\DataManager
{
    /**
     * Возвращает имя таблицы в базе данных
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return 'directory_order_psk';

    }


    /**
     * ???
     * @return string
     */
    public static function getUfId(): string
    {
        return 'DIRECTORY_ORDER_PSK';
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

            // Номер общего заказа
            new \Bitrix\Main\Entity\StringField('ID',[
                'required' => true
            ]),

            // Дата создания заказа
            new \Bitrix\Main\Entity\DatetimeField('DATE'),

            // Контрагент GUID
            new \Bitrix\Main\Entity\StringField('PARTNER_GUID'),

            // Контрагент Имя
            new \Bitrix\Main\Entity\StringField('PARTNER_NAME'),

            // Статус заказа
            new \Bitrix\Main\Entity\StringField('STATUS'),

            // Идентификатор учетной записи пользователя в Битрикс
            new \Bitrix\Main\Entity\IntegerField('USER',[
                'required' => true
            ]),

            // Связанные заказы (массив ID записей в битрикс, сериализованное поле)
            new \Bitrix\Main\Entity\StringField('LINKED'),

            // Общая сумма заказа без скидки
            new \Bitrix\Main\Entity\StringField('COST'),

            // Список позиций
            new \Bitrix\Main\Entity\StringField('POSITIONS'),

            // Скидка числом
            new \Bitrix\Main\Entity\StringField('DISCOUNT'),

            // флаг: для зарезервированного типа заказа
//            new \Bitrix\Main\Entity\BooleanField('RESERVE',[
//                'values' => ['N','Y']
//            ]),
            new \Bitrix\Main\Entity\IntegerField('RESERVE'),
            // флаг: заказ доступен для редактирования
//            new \Bitrix\Main\Entity\BooleanField('EDITABLE',[
//                'values' => ['N','Y']
//            ])
            new \Bitrix\Main\Entity\IntegerField('EDITABLE'),

            // Стоимость отгрузки, строка 900 = 900 рублей
            new \Bitrix\Main\Entity\StringField('SHIPMENT_COST')
        ];
    }
}