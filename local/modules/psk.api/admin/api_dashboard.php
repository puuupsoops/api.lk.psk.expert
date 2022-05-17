<?php
require_once(__DIR__ . '/page_header.php');

/**
 * @global $APPLICATION
 */
$APPLICATION->SetTitle("Страница управления API личного кабинета");

\Bitrix\Main\UI\Extension::load('ui.buttons');
\Bitrix\Main\UI\Extension::load('ui.buttons.icons');
\Bitrix\Main\UI\Extension::load('ui.notification');
\Bitrix\Main\UI\Extension::load("ui.dialogs.messagebox");
CJSCore::Init(array('ajax','window','jquery'));

?>

<!--<button class="ui-btn ui-btn-success ui-btn-wait">Обновить</button>-->

<div id="container">
    <strong style="font-size: 16px">Обновить документы из 1С: </strong>
</div>
<pre>
<div id="result-area">

</div>
</pre>
<script>
    (function() {
        var button = new BX.UI.Button(
            {
                text: "Обновить",
                className: "ui-btn-success",
                id: 'document-update-btn',
                onclick: function(btn, event) {
                    console.log("onclick", btn);

                    BX.UI.Dialogs.MessageBox.show(
                        {
                            message: "Обновление запустится в тестовом режиме",
                            modal: true,
                            buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
                            onOk: function(messageBox)
                            {
                                console.log("onOk");
                                messageBox.close();
                                console.log(button.set);

                                BX.ajax({
                                    url: '/crone/partner_add.php',
                                    data: {},
                                    method: 'GET',
                                    async: false,
                                    processData: true,
                                    scriptsRunFirst: true,
                                    emulateOnload: true,
                                    onsuccess: function(data){
                                        console.log('это ajax детка:',data);
                                        document.getElementById('result-area').textContent = data;
                                        alert('Обновлено.');
                                    },
                                    onfailure: function(status,code,data){
                                        console.log('это ajax детка:', status,code,data);
                                        alert('Сервис недоступен!');
                                    }
                                });

                            },
                            onCancel: function(messageBox)
                            {
                                console.log("onCancel");
                                messageBox.close();
                            },
                        }
                    );

                },
            }
        );

        var container = document.getElementById("container");
        button.renderTo(container);
    })();
</script>

<?php
require_once(__DIR__ . '/page_footer.php');
?>

