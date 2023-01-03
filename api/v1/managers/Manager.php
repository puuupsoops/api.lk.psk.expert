<?php

namespace API\v1\Managers;
include_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/Manager.php';

class Manager
{
    private static string $server_url = '';

    /**
     * @var \API\v1\Models\Manager[] Массив с доступными менеджерами
     */
    private static array $arManagers = [];

    private static $instance = null;

    public static function GetInstance(): \API\v1\Managers\Manager {
        if(is_null(self::$instance)) {
            self::init();
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * Получить менеджера по XML идентификатору.
     *
     * @param string $id XML Идентификатор
     * @return \API\v1\Models\Manager Модель данных менеджера
     */
    public static function GetByXmlId(string $id): \API\v1\Models\Manager {
        return self::$arManagers[$id] ?? new \API\v1\Models\Manager();
    }

    private static function init(){
        if(!\Bitrix\Main\Loader::IncludeModule('highloadblock'))
            throw new \Exception(
                'Ошибка конфигурации окружения сервера API. Отсутствует модуль highloadblock',
                500
            );

        \Bitrix\Highloadblock\HighloadBlockTable::compileEntity(
            \Bitrix\Highloadblock\HighloadBlockTable::getList(['filter'=>['=NAME'=>'Managers']])->fetch()
        );

        if( \Configuration::GetInstance()::IsProduction() ){
            self::$server_url = 'http://91.232.12.198:82';
        }else{
            self::$server_url = 'http://89.111.136.61';
        }


        $Result = \ManagersTable::getList(['select' => ['*']]);

        while($item = $Result->fetch()) {
            $manager = new \API\v1\Models\Manager();

            $manager->name = $item['UF_NAME'];
            $manager->contact = $item['UF_PHONE'];
            //$manager->image = self::$server_url . \CFile::GetPath($item['UF_IMAGE']);
            $manager->email = $item['UF_EMAIL'];
            $manager->phone1 = $item['UF_PHONE'];
            $manager->phone2 = $item['UF_SECOND_PHONE'];

            $path = $_SERVER['DOCUMENT_ROOT'] . \CFile::GetPath($item['UF_IMAGE']);
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $manager->image = 'data:image/' . $type . ';base64,' . base64_encode($data);

            //todo head manager
            //$manager->header = [$item['']];

            self::$arManagers[$item['UF_UID']] = $manager;
        }
    }
}