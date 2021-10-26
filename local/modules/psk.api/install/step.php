<?php
/**
 * Страница установки шаг 0 = шаг финальный
 */
use Bitrix\Main\Localization\Loc;

//if(!chek_bitrix_sessid())
//    return;
global $APPLICATION;

if($ex = $APPLICATION->GetException() )
{
    CAdminMessage::ShowMessage(
        [
            "TYPE" => "ERROR",
            "MESSAGE" => 'Ошибка при установке.',
            "DETAILS" => $ex->GetString(),
            "HTML"=>true
        ]
    );

}
else
{
    CAdminMessage::ShowNote('Модуль успешно установлен');
}

?>

<form action="<?= $APPLICATION->GetCurPage();?>">
    <input type="hidden" name="lang" value="<?= LANGUADE_ID ?>">
    <input type="submit" name="" value="<?= Loc::getMessage("MOD_BACK"); ?>">
</form>