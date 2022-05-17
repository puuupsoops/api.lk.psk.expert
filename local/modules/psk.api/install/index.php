<?php
/**
 * Содержит код установки и удаления модуля,основные параметры модуля.
 */
class psk_api extends CModule{

    function __construct()
    {
        /**
         * @var array Версия модуля
         */
        $arModuleVersion = [];
        include(__DIR__.'/version.php');

        // id модуля = имя папки с модулем
        $this->MODULE_ID = 'psk.api';

        // версия модуля
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];

        // дата модуля
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];

        // имя модуля в битрикс админки
        $this->MODULE_NAME = 'API для личного кабинета';

        // описание модуля в битрикс админки
        $this->MODULE_DESCRIPTION = 'Ядро функционала API для личного кабинета (таблицы, функции, и т.д.)';

        // имя разработчика
        $this->PARTNER_NAME = 'Vasilii_Polshakov<45201a@gmail.com>';

        // ссылка разработчика (адрес сайта)
        $this->PARTNER_URI = 'https://vk.com/id16868583';

        // позиция сортировки модуля в списке модулей
        $this->MODULE_SORT = '10';

        // Y - модуль показывается на странице редактирования прав, Группы доступа: вкладка Доступ
        $this->MODULE_GROUP_RIGHTS = 'Y';

        // Y - показывает группу администраторов, на странице настроект модуля на вкладке доступа
        $this->SHOW_SUPER_ADMIN_GROUP_RIGHTS = 'N';
    }

    /**
     * Действия при установке модуля
     */
    public function DoInstall()
    {
        global $APPLICATION;

        // поддержка D7
        if(CheckVersion(\Bitrix\Main\ModuleManager::getVersion('main'),'14.00.00')){

            //region Действия при установке модуля

            try {
                // создание таблиц (инфоблоков)
                $arConfig = $this->InstallDB();

                // Установка дополнительных значений в конфигурационный массив
                $this->SetUp($arConfig);

            }catch(\Exception $e){
                CAdminMessage::ShowMessage(
                    [
                        "TYPE" => "ERROR",
                        "MESSAGE" => 'Ошибка при миграции данных модуля',
                        "DETAILS" => $e->getMessage(),
                        "HTML"=>true
                    ]
                );
                $APPLICATION->ThrowException($e->getMessage());
            }

            //endregion

            /**
             * @var \Bitrix\Main\Config\Configuration Экземпляр класса конфигуратора, (работа с файлом .settings.php)
             */
            $configuration = \Bitrix\Main\Config\Configuration::getInstance();
            $configuration->add('api_settings',$arConfig);

            try{
                // сохраняем значения в конфигурационный файл.
                $configuration->saveConfiguration();
            }catch(\Bitrix\Main\InvalidOperationException $e){
                CAdminMessage::ShowMessage(
                    [
                        "TYPE" => "ERROR",
                        "MESSAGE" => 'Ошибка при записи в файл конфигурации .settings.php',
                        "DETAILS" => $e->getMessage(),
                        "HTML"=>true
                    ]
                );
            }

            // зарегистрировать модуль в системе
            \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);

        }else{
            $APPLICATION->ThrowException('Установка прервана. Версия главного модуля не поддерживает D7. Требуется версия 14.00.00 и выше.');
        }

    }


    /**
     * Действия при удаления модуля
     */
    public function DoUninstall()
    {
        // снять модуль с учета в системе
        \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);

        /**
         * @var \Bitrix\Main\Config\Configuration Экземпляр класса конфигуратора, (работа с файлом .settings.php)
         */
        $configuration = \Bitrix\Main\Config\Configuration::getInstance();
        $arConfig = $configuration->get('api_settings');

        //region Действия при удаления модуля

        try {

            // удаление установленных значений (пользовательские поля)
            $this->UnSet($arConfig);

            // удаление инфоблоков и типов инфоблоков
            $this->UnInstallDB();

        }catch (\Exception $e){
            CAdminMessage::ShowMessage(
                [
                    "TYPE" => "ERROR",
                    "MESSAGE" => 'Ошибка при удалении. Удалите остаточные данные вручную.',
                    "DETAILS" => $e->getMessage(),
                    "HTML"=>true
                ]
            );
        }

        //endregion

        // удаление данных из файла конфигурации

        $configuration->delete('api_settings');

        try{
            // сохраняем значения в конфигурационный файл.
            $configuration->saveConfiguration();
        }catch(\Bitrix\Main\InvalidOperationException $e){
            CAdminMessage::ShowMessage(
                [
                    "TYPE" => "ERROR",
                    "MESSAGE" => 'Ошибка при записи в файл конфигурации .settings.php',
                    "DETAILS" => $e->getMessage(),
                    "HTML"=>true
                ]
            );
        }
    }

    /**
     * Установка дополнительных значений в конфигурационный массив
     *
     * @param array $arConfig Массив с параметрами
     *
     */
    private function SetUp(array &$arConfig){

        global $USER_FIELD_MANAGER;
        // Установка Уникальных, внешних идентификаторов

        // ООО "Эксперт Спецодежда"
        $USER_FIELD_MANAGER->Update(
            'IBLOCK_'.$arConfig['iblocks']['Contracts'].'_SECTION',
            $arConfig['iblocks']['sections']['Contracts']['SPEC_ODA'],
            [
                'UF_UID' => 'b5e91d86-a58a-11e5-96ed-0025907c0298'
            ]
        );

        // ООО "Фабрика Рабочей Обуви"
        $USER_FIELD_MANAGER->Update(
            'IBLOCK_'.$arConfig['iblocks']['Contracts'].'_SECTION',
            $arConfig['iblocks']['sections']['Contracts']['WORK_SHOES'],
            [
                'UF_UID' => 'f59a4d06-2f35-11e7-8fdb-0025907c0298'
            ]
        );
    }

    /**
     * Удалить установленные значения
     *
     * @param array $arConfig Массив с параметрами
     */
    private function UnSet(array &$arConfig){
        $UserFieldEntity = new \CUserTypeEntity();

        // удалить пользовательские поля
        foreach ($arConfig['user_fields'] as $filedId){
            $UserFieldEntity->Delete($filedId);
        }

        foreach ($arConfig['iblocks']['user_fields'] as $key=>$filed){
            foreach ($filed as $id){
                $UserFieldEntity->Delete($id);
            }
        }

  /* присутствует в конфигурации .settings.php:
        $ar = [
            'api_settings' =>
                array (
                    'value' =>
                        array (
                            'types' =>
                                array (
                                    0 => 'partner',
                                    1 => 'storages',
                                    2 => 'users',
                                ),
                            'iblocks' =>
                                array (
                                    'Partners' => 32,
                                    'Users' => 33,
                                    'Contracts' => 34,
                                    'user_fields' =>
                                        array (
                                            'Contracts' =>
                                                array (
                                                    0 => 18,
                                                ),
                                        ),
                                    'sections' =>
                                        array (
                                            'Contracts' =>
                                                array (
                                                    'SPEC_ODA' => 40,
                                                    'WORK_SHOES' => 41,
                                                ),
                                        ),
                                    'Documents' => 35,
                                ),
                            'user_fields' =>
                                array (
                                    0 => 19,
                                ),
                        )
                )
        ];
  */
    }

    /**
     * Возвращает путь до корня модуля
     *  - true -- путь от корня сайта до корня модуля   /local/modules/psk.api
     *  - false -- путь от корня сервера (по умолчанию) /home/bitrix/www/local/modules/psk.api
     * @param false $notDocumentRoot
     * @return string Путь до корня модуля
     */
    public function GetPath($notDocumentRoot = false): string{
        if($notDocumentRoot){

            // путь от корня сайта до корня модуля
            return str_ireplace(Bitrix\Main\Application::getDocumentRoot(),'',dirname(__DIR__));

        }else{
            // путь от корня сервера
            return dirname(__DIR__);
        }
    }

    /**
     * Установка таблиц в базу данных
     *
     * @return array        Массив конфигураций для .settings.php
     * @throws Exception
     */
    public function InstallDB(): array
    {
        CModule::IncludeModule("iblock");

        /**
         * @var array Идентификаторы для конфигурации
         */
        $arSettings = [];

        try{
            // создание типов инфоблоков
            $arSettings['types'] = $this->CreateIBlockType();

            // создание инфоблоков
            $arSettings['iblocks'] = $this->CreateIBlocks();

            // создание пользовательских полей
            $arSettings['user_fields'] = $this->CreateCustomUserFields($arSettings);

        }catch(\Exception $e){
            throw new Exception( $e->getMessage() );
        }

        //region создать сущности таблиц напрямую

        foreach(glob('/home/bitrix/www/local/modules/psk.api' . '/lib/*.php') as $file){
            include($file);
        }

        if(\Bitrix\Main\Application::getConnection(\Psk\Api\Orders\DirectoryTable::getConnectionName())
            ->isTableExists(\Bitrix\Main\Entity\Base::getInstance('\Psk\Api\Orders\DirectoryTable')->getDBTableName()))
        {
            \Bitrix\Main\Entity\Base::getInstance('\Psk\Api\Orders\DirectoryTable')->createDbTable();
        }

        //endregion
        return $arSettings;
    }

    /**
     * Удаление таблиц из базы данных
     *
     * @throws Exception
     */
    public function UnInstallDB()
    {
        CModule::IncludeModule($this->MODULE_ID);
        CModule::IncludeModule("iblock");

        /**
         * @var \Bitrix\Main\Config\Configuration Экземпляр класса конфигуратора, (работа с файлом .settings.php)
         */
        $configuration = \Bitrix\Main\Config\Configuration::getInstance();
        // данные о конфигурации
        $arSettings = $configuration->get('api_settings');

        // удаление информационных блоков
        foreach ($arSettings['iblocks'] as $key=>$id){
            try{
                if(is_array($id))
                    continue;

                $this->DeleteIBlock($id);
            }catch (\Exception $e){
                throw new Exception($e->getMessage());
            }
        }

        // удаление типов инфоблоков
        foreach ($arSettings['types'] as $type){
            try{
                $this->DeleteIBlockType($type);
            }catch (\Exception $e){
                throw new Exception($e->getMessage());
            }
        }

        //region удаление сущностей созданных таблиц в битрикс напрямую

        \Bitrix\Main\Application::getConnection(\Psk\Api\Orders\DirectoryTable::getConnectionName())
            ->queryExecute('drop table if exists ' . \Bitrix\Main\Entity\Base::getInstance('\Psk\Api\Orders\DirectoryTable')->getDBTableName());

        //endregion

        // Удалит все переменные модуля (см. на страницу с настройками модуля):
        // \Bitrix\Main\Config\Option::delete($this->MODULE_ID);

    }

    /**
     * Создать типы инфоблоков (множество типов)
     *
     * @return array        Массив с идентификаторами созданных типов инфоблоков
     * @throws Exception    Ошибка при создании типа инфоблока
     */
    private function CreateIBlockType(): array {
        // инстанс для управления транзакциями базы данных
        global $DB;

        /**
         * @var array Типы инфоблоков с их идентификаторами
         */
        $arSettings = [];

        /**
         * @var array Массивы настроект для формирования используемых типов инфоблоков
         */
        $arCreateTypes = [];
        // файл с данными для инициализации
        include($this->GetPath() . '/init/Types.php');

        $iBlockType = new \CIBlockType();

        // процесс создания типов инфоблоков
        foreach ($arCreateTypes as $element){
            $DB->StartTransaction();
            $result = $iBlockType->Add($element);

            if(!$result){
                $DB->Rollback();
                throw new Exception('Ошибка при создании типа инфоблока ID: ' . $element['ID']);
            }else{
                $DB->Commit();
                $arSettings[] = $element['ID'];
            }
        }

        return $arSettings;
    }

    /**
     * Удалить тип инфоблока из системы битрикс (удаление по одному типу)
     *
     * @param string $type  Тип инфоблока (ID-типа)
     * @throws Exception    Ошибка удаления типа инфоблока
     */
    private function DeleteIBlockType(string $type) {
        // инстанс для управления транзакциями базы данных
        global $DB;

        $DB->StartTransaction();
        if( !\CIBlockType::Delete($type) )
        {
            $DB->Rollback();
            throw new Exception('Ошибка удаления типа инфоблока ID:' . $type);
        }
        $DB->Commit();
    }

    // создать инфоблоки
    private function CreateIBlocks(): array {
        /**
         * @var array Типы инфоблоков с их идентификаторами
         */
        $arSettings = [];

        /**
         * @var array Файлы с данными для инициализации инфоблоков
         */
        $arFiles = [];

        // процесс подключения файлов и формирование данных
        foreach (glob($this->GetPath() . '/init/iblocks/*.php') as $file){
            // очередь выполнения
            $executeQueue = 0;

            include($file);

            $arFiles[$executeQueue-1] = $file;
        }

        // сортировка по возрастанию (!соблюдаем порядок создания инфоблоков)
        ksort($arFiles);

        /**
         * @var CIBlock Объект для создания инфоблока
         */
        $iBlock = new \CIBlock;

        /**
         * @var CIBlockProperty Объект для создания полей свойств в инфоблоке.
         */
        $iBlockProperty = new \CIBlockProperty;

        // создание инфоблоков и их свойств
        foreach ($arFiles as $file){
            /**
             * @var Array Предварительные установки для создания инфоблока
             */
            $arSetting = [];

            /**
             * @var Array Массив для добавления свойств инфоблока
             */
            $arPropertyField = [];

            /**
             * @var array Массив с пользовательскими полями
             */
            $aUserFields = [];

            /**
             * @var Array Массив c разделами
             */
            $arSections = [];

            // подключаем данные из файла
            include($file);

            // массив с настройками для инициализации инфоблока
            $arFields = [
                'IBLOCK_TYPE_ID'    => $arSetting['IBLOCK_TYPE_ID'],
                'ACTIVE'            => $arSetting['ACTIVE'],
                'NAME'              => $arSetting['NAME'],
                'CODE'              => $arSetting['CODE'],
                'XML_ID'            => $arSetting['XML_ID'],
                'API_CODE'          => $arSetting['API_CODE'],
                'SORT'              => $arSetting['SORT'],
                'SITE_ID'           => $arSetting['SITE_ID'],
                'VERSION'           => $arSetting['VERSION']
            ];

            /**
             * @var integer Идентификатор созданного инфоблока
             */
            $addResultID = $iBlock->Add($arFields);

            if(!$addResultID){
                throw new Exception($iBlock->LAST_ERROR);
            }

            foreach ($arPropertyField as $propertyField){

                #note: Добавить в итериациях к массиву 'IBLOCK_ID' => $addResultID,
                $propertyField['IBLOCK_ID'] = $addResultID;

                $iBlockProperty->Add($propertyField);

            }

            $arSettings[$arSetting['API_CODE']] = $addResultID;

            // создание пользовательских полей
            if($aUserFields){

                $UserFieldEntity = new \CUserTypeEntity();

                foreach ($aUserFields as $filed){

                    $filed['ENTITY_ID'] = 'IBLOCK_'.$arSettings[$arSetting['API_CODE']].'_SECTION';

                    $fieldID = $UserFieldEntity->Add($filed);

                    $arSettings['user_fields'][$arSetting['API_CODE']][] = $fieldID;
                }
            }

            // создание разделов в инфоблоках
            if($arSections){
                $iBlockSection = new \CIBlockSection;

                foreach ($arSections as $section){
                    $section['IBLOCK_ID'] = $arSettings[$arSetting['API_CODE']];

                    $sectionId = $iBlockSection->Add($section);

                    $arSettings['sections'][$arSetting['API_CODE']][$section['CODE']] = $sectionId;
                }
            }
        }

        return $arSettings;
    }

    /**
     * Удаляет информационный блок по его идентификатору
     *
     * @param string $id    Идентификатор инфоблока в битрикс
     * @throws Exception    Ошибка удаления инфоблока
     */
    private function DeleteIBlock(string $id){
        // инстанс для управления транзакциями базы данных
        global $DB;

        $DB->StartTransaction();
        if( !\CIBlock::Delete($id) )
        {
            $DB->Rollback();
            throw new Exception('Ошибка удаления инфоблока ID:' . $id);
        }
        $DB->Commit();
    }

    /**
     * Создать пользовательские поля
     *
     * @param array $arSettings Массив с параметрами
     * @return array            Массив с параметрами
     */
    private function CreateCustomUserFields(array &$arSettings): array {

        $arSetting = [];

        /**
         * @var array Массив с пользовательскими полями
         */
        $aUserFields = [];

        include($this->GetPath() . '/init/UserFields.php');

        $UserFieldEntity = new \CUserTypeEntity();

        if($aUserFields){
            foreach ($aUserFields as $field){
                $fieldId = $UserFieldEntity->Add($field);
                $arSetting[] = $fieldId;
            }
        }

        return $arSetting;
    }
}