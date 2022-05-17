<?php
namespace Psk\Api\Orders;
//include_once $_SERVER["DOCUMENT_ROOT"]. '/local/modules/psk.api/lib/DirectoryTable.php';

/**
 *  Модель справочника по заказам в базе данных битрикса.
 *
 * -- создать таблицу (создано!).
 * -- \Bitrix\Main\Entity\Base::getInstance('\Psk\Api\Orders\DirectoryTable')->createDbTable();
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
        ];
    }
}