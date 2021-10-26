<?php

use Bitrix\Main\Localization\Loc;

if(!check_bitrix_sessid())
    return;

global $APPLICATION;

if( $ex = $APPLICATION->GetException() )
{
    CAdminMessage::ShowMessage(array(
        "TYPE" => "ERROR",
        "MESSAGE" => 'Ошибка при удалении модуля.',
        "DETAILS" => $ex->GetString(),
        "HTML"=>true
    ));
}
else
{
    CAdminMessage::ShowNote('Модуль успешно удалён.');
}

?>

<form action="<?=$APPLICATION->GetCurPage();?>">
    <input type="hidden" name="lang" value="<?=LANGUADE_ID?>">
    <input type="submit" name="" value="<?=Loc::getMessage("MOD_BACK");?>">
</form>