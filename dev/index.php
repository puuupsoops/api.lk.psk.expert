<?php
//include_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

$phpWord = new \PhpOffice\PhpWord\PhpWord();
$section = $phpWord->addSection();

\PhpOffice\PhpWord\Shared\Html::addHtml($section, $html, false, false);