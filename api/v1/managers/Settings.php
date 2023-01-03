<?php

namespace API\v1\Managers;
include_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/settings/PersonalSettings.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/settings/NotificationSettings.php';

class Settings
{
    private string $BITRIX_IBLOCK_PROPERTY_CODE__ORDER_NOTIFICATION_EMAIL = 'ORDER_NOTIFICATION_EMAIL';
    private string $BITRIX_IBLOCK_PROPERTY_CODE__ORDER_NOTIFICATION_LK = 'ORDER_NOTIFICATION_LK';

    /**
     * Получить персональные настройки по идентификатору записи пользователя в Битрикс
     *
     * @param int $id Идентификатор учетной записи пользователя в Битрикс
     *
     * @return \API\v1\Models\PersonalSettings Модель с данными
     *
     * @throws \Exception Пользователь не найден.
     */
    public function GetPersonalSettingsByUserId(int $id): \API\v1\Models\PersonalSettings {
        $CDBResult = \CUser::GetByID($id);

        if(!$arUser = $CDBResult->Fetch()) {
            throw new \Exception('Пользователь не найден.',404);
        }

        $PersonalSettings = new \API\v1\Models\PersonalSettings();

        if(\Configuration::GetInstance()::IsProduction()){
            $url = 'http://91.232.12.198:82';
        }else{
            $url = 'http://89.111.136.61';
        }

        $PersonalSettings->name = $arUser['NAME'] ?? '';
        $PersonalSettings->lastname = $arUser['LAST_NAME'] ?? '';
        $PersonalSettings->patronymic = $arUser['SECOND_NAME'] ?? '';
        $PersonalSettings->phone = $arUser['PERSONAL_PHONE'] ?? '';
        $PersonalSettings->email = $arUser['EMAIL'] ?? '';
        $PersonalSettings->image = '';

        if($file = \CFile::GetPath($arUser['PERSONAL_PHOTO'])) {
            $path = $_SERVER['DOCUMENT_ROOT'] . $file;
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $PersonalSettings->image = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        return $PersonalSettings;
    }

    /**
     * Получить настройки уведомлений заказа по идентификатору конфигурации пользователя
     *
     * @param int $id Идентификатор конфигурации пользователя
     *
     * @return \API\v1\Models\NotificationSettings Модель с данными
     *
     * @throws \Exception Ошибка подключения модуля инфоблока.
     */
    public function GetOrderNotificationSettingsByUserConfigId(int $id): \API\v1\Models\NotificationSettings {
        if(!\Bitrix\Main\Loader::includeModule('iblock'))
            throw new \Exception('Ошибка подключения модуля инфоблока.',500);

        $CIBlockResult = \CIBlockElement::GetList([],['ID' => $id]);

        $NotificationSettings = new \API\v1\Models\NotificationSettings();

        if($next = $CIBlockResult->GetNextElement()){
            $arProps = $next->getProperties();

            $ORDER_NOTIFICATION_EMAIL = $arProps['ORDER_NOTIFICATION_EMAIL']['VALUE_XML_ID'];
            if($ORDER_NOTIFICATION_EMAIL) {
                $NotificationSettings->order_email_created = in_array('created',$ORDER_NOTIFICATION_EMAIL);
                $NotificationSettings->order_email_changed = in_array('changed',$ORDER_NOTIFICATION_EMAIL);
                $NotificationSettings->order_email_states = in_array('states',$ORDER_NOTIFICATION_EMAIL);
            }

            $ORDER_NOTIFICATION_LK = $arProps['ORDER_NOTIFICATION_LK']['VALUE_XML_ID'];
            if($ORDER_NOTIFICATION_LK) {
                $NotificationSettings->order_lk_created = in_array('created', $ORDER_NOTIFICATION_LK);
                $NotificationSettings->order_lk_changed = in_array('changed', $ORDER_NOTIFICATION_LK);
                $NotificationSettings->order_lk_states = in_array('states', $ORDER_NOTIFICATION_LK);
            }

        }

        return $NotificationSettings;
    }

    /**
     * Обновить параметры уведомления.
     *
     * @param int $id Идентификатор инфоблока с конфигурацией пользователя
     * @param \API\v1\Models\NotificationSettings $notification_settings Модель с параметрами уведомлений
     *
     * @return \API\v1\Models\NotificationSettings Модель с обновленными параметрами уведомлений
     *
     * @throws \Exception Ошибка подключения модуля инфоблока
     *
     */
    public function UpdateOrderNotificationSettings(
        int $id,
        \API\v1\Models\NotificationSettings $notification_settings
    ): \API\v1\Models\NotificationSettings {
        if(!\Bitrix\Main\Loader::includeModule('iblock'))
            throw new \Exception('Ошибка подключения модуля инфоблока.',500);

        /** @var array $arIDs Массив идентификаторов для свойств элемента инфоблока */
        $arEnumIDs = [];

        /** @var array | \API\v1\Models\NotificationSettings $notification_settings
         *  Преобразуем в массив модель полей уведомлений
         */
        $notification_settings = $notification_settings->AsArray();

        //region Запрос в Битрикс, получаем идентификаторы ключей списка перечесления
        $CDBResult = \CIBlockPropertyEnum::GetList([],
            [
                'CODE' => [
                    $this->BITRIX_IBLOCK_PROPERTY_CODE__ORDER_NOTIFICATION_EMAIL,
                    $this->BITRIX_IBLOCK_PROPERTY_CODE__ORDER_NOTIFICATION_LK
                ]
            ]
        );

        while($next = $CDBResult->Fetch()) {
            /** @var array $next
             * array(11) {
             * ["ID"]=>
             * string(2) "16"
             * ["PROPERTY_ID"]=>
             * string(3) "299"
             * ["VALUE"]=>
             * string(28) "Отредактирован"
             * ["DEF"]=>
             * string(1) "Y"
             * ["SORT"]=>
             * string(3) "500"
             * ["XML_ID"]=>
             * string(7) "changed"
             * ["TMP_ID"]=>
             * NULL
             * ["EXTERNAL_ID"]=>
             * string(7) "changed"
             * ["PROPERTY_NAME"]=>
             * string(78) "Уведомления о заказе, на электронную почту"
             * ["PROPERTY_CODE"]=>
             * string(24) "ORDER_NOTIFICATION_EMAIL"
             * ["PROPERTY_SORT"]=>
             * string(3) "500"
             * }
             */

            switch ($next['PROPERTY_CODE']){
                case $this->BITRIX_IBLOCK_PROPERTY_CODE__ORDER_NOTIFICATION_EMAIL :
                    $arEnumIDs[$this->BITRIX_IBLOCK_PROPERTY_CODE__ORDER_NOTIFICATION_EMAIL][$next['EXTERNAL_ID']] = $next['ID'];
                    break;
                case $this->BITRIX_IBLOCK_PROPERTY_CODE__ORDER_NOTIFICATION_LK :
                    $arEnumIDs[$this->BITRIX_IBLOCK_PROPERTY_CODE__ORDER_NOTIFICATION_LK][$next['EXTERNAL_ID']] = $next['ID'];
                    break;
            }
        }
        //endregion

        //фильтруем ключи, оставляем только активные.
        foreach ($arEnumIDs as $i_key => $item) {
            $arEnumIDs[$i_key] = array_filter($item, function ($value,$key) use ($notification_settings,$i_key) {

                if($i_key === $this->BITRIX_IBLOCK_PROPERTY_CODE__ORDER_NOTIFICATION_EMAIL) {
                    // для почтовых уведомлений
                    return $notification_settings['order']['email'][$key];
                }

                if($i_key === $this->BITRIX_IBLOCK_PROPERTY_CODE__ORDER_NOTIFICATION_LK) {
                    // для всплывающих уведомлений в ЛК
                    return $notification_settings['order']['lk'][$key];
                }

                return false;

            },ARRAY_FILTER_USE_BOTH );
        }

        //записываем значения в базу
        /** метод возвращает null
         * @see https://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/setpropertyvaluesex.php
         */
        \CIBlockElement::SetPropertyValuesEx($id, false,
            [
               $this->BITRIX_IBLOCK_PROPERTY_CODE__ORDER_NOTIFICATION_EMAIL =>
                   (!empty($arEnumIDs[$this->BITRIX_IBLOCK_PROPERTY_CODE__ORDER_NOTIFICATION_EMAIL])) ?
                       array_values($arEnumIDs[$this->BITRIX_IBLOCK_PROPERTY_CODE__ORDER_NOTIFICATION_EMAIL]) : false,

               $this->BITRIX_IBLOCK_PROPERTY_CODE__ORDER_NOTIFICATION_LK =>
                   (!empty($arEnumIDs[$this->BITRIX_IBLOCK_PROPERTY_CODE__ORDER_NOTIFICATION_LK])) ?
                       array_values($arEnumIDs[$this->BITRIX_IBLOCK_PROPERTY_CODE__ORDER_NOTIFICATION_LK]) : false,
            ]
        );

        return $this->GetOrderNotificationSettingsByUserConfigId($id);
    }
}