<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

$loader = new Twig_Loader_Filesystem($_SERVER['DOCUMENT_ROOT'] . '/local/src/twig_templates/usertype');
$Twig = new Twig_Environment($loader);

$template = $Twig->loadTemplate('CUserTypeOrderItemTemplate.html');
echo $template->render(['itemId' => '']);