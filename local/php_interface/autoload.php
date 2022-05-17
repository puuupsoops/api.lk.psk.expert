<?php
use Bitrix\Main\Loader;

# Автозагрузка классов
Loader::registerAutoLoadClasses(
    null,
    ['lib\usertype\CUserTypeOrderItem' => APP_CLASS_FOLDER . 'usertype/CUserTypeOrderItem.php']
);