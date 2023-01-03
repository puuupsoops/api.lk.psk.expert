<?php

namespace API\v1\Repositories;

class EmailMessageRepository
{
    const BITRIX_CLASS_NAME_TABLE = 'PostMessages';
    private int $user_id = 0;

    /**
     * @param int $userId Идентификатор пользователя
     */
    public function __construct(int $userId)
    {
        $this->user_id = $userId;
    }

    /**
     * @param string $subject Тема сообщения
     * @param string $text Текст сообщения
     * @param array $address Список почтовых адресов
     *
     * @return int Идентификатор созданной записи.
     * @throws \Exception
     */
    public function Add(string $subject, string $text, array $address): int {

        if (!\Bitrix\Main\Loader::includeModule('highloadblock'))
            throw new \Exception('Не подключен Bitrix модуль HighloadBlock', 406);

        $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getList(
            [
                'filter' => ['=NAME' => self::BITRIX_CLASS_NAME_TABLE]
            ])->Fetch();

        if (!$hlblock)
            throw new \Exception('Отсутсвует таблица: ' . self::BITRIX_CLASS_NAME_TABLE, 406);

        $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);

        $result = \PostMessagesTable::add([
            'UF_SUBJECT'     => $subject ?? 'Пустая тема',
            'UF_TEXT'        => $text ?? '',
            'UF_ADDRESS'     => json_encode($address), // JSON
            'UF_USER_ID'     => $this->user_id,
            'UF_CREATE_DATE' => \Bitrix\Main\Type\DateTime::createFromTimestamp(time())
        ]);

        if (!$result->isSuccess())
            throw new \Exception($result->getErrorMessages()[0], 406);

        return $result->getId(); // ID созданного элемента хайлоадблока
    }

    /**
     * Получить сообщения из таблицы
     *
     * @param int $limit Лимит строк
     *
     * @return \stdClass[] Сообщения
     * <br>
     * <ul>
     *  <li>id - String - Идентификатор сообщения </li>
     *  <li>subject - String - Тема сообщения </li>
     *  <li>text - String - Текст сообщения </li>
     *  <li>address - String[] - Массив электронных адресов </li>
     *  <li>userId - String - Идентификатор учетной записи пользователя в Bitrix </li>
     * </ul>
     * @throws \Exception
     */
    public static function Get(int $limit): array {
        if (!\Bitrix\Main\Loader::includeModule('highloadblock'))
            throw new \Exception('Не подключен Bitrix модуль HighloadBlock', 406);

        $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getList(
            [
                'filter' => ['=NAME' => self::BITRIX_CLASS_NAME_TABLE]
            ])->Fetch();

        if (!$hlblock)
            throw new \Exception('Отсутсвует таблица: ' . self::BITRIX_CLASS_NAME_TABLE, 406);

        $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);

        $arResult = [];

        $result = \PostMessagesTable::getList([
            'select' => [
                'ID',
                'subject' => 'UF_SUBJECT',
                'text' => 'UF_TEXT',
                'address' => 'UF_ADDRESS',
                'userId' => 'UF_USER_ID'
            ],
            'filter' => ['UF_IS_SEND' => '0'],
            'limit' => $limit,
        ]);

        while($item = $result->Fetch()) {
            $item['id'] = array_shift($item);
            $item['address'] = json_decode($item['address'],true);
            $arResult[] = (object)$item;
        }

        return $arResult;
    }

    public static function Update(int $id) {
        if (!\Bitrix\Main\Loader::includeModule('highloadblock'))
            throw new \Exception('Не подключен Bitrix модуль HighloadBlock', 406);

        $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getList(
            [
                'filter' => ['=NAME' => self::BITRIX_CLASS_NAME_TABLE]
            ])->Fetch();

        if (!$hlblock)
            throw new \Exception('Отсутсвует таблица: ' . self::BITRIX_CLASS_NAME_TABLE, 406);

        $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);

        $result = \PostMessagesTable::update(
            $id,
            [
                'UF_IS_SEND' => 1,
                'UF_SEND_DATE' => \Bitrix\Main\Type\DateTime::createFromTimestamp(time())
            ]
        );

        if (!$result->isSuccess())
            throw new \Exception($result->getErrorMessages()[0], 406);
    }
}