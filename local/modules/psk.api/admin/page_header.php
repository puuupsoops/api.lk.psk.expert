<?php
const ADMIN_MODULE_NAME = 'psk.api';
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin.php");

if( !\Bitrix\Main\Loader::includeModule(ADMIN_MODULE_NAME) ){
    //ShowError(\Bitrix\Main\Localization\Loc::getMessage("MAIN_MODULE_NOT_INSTALLED"));
    ShowError('Модуль API для личного кабинета: не установлен!');
}

global $APPLICATION;
// стили битрикса
$APPLICATION->SetAdditionalCSS('/bitrix/css/main/grid/webform-button.css');

