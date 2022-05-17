<?php
AddEventHandler("main", "OnBuildGlobalMenu", "OnBuildGlobalMenuHandler");

// Константы
require dirname(__FILE__) . '/constants.php';

// Автозагрузка классов
require dirname(__FILE__) . '/autoload.php';

// Обработка событий
require dirname(__FILE__) . '/event_handler.php';

/**
 * Добавление пункта меню на административную страницу
 *
 * @param $aGlobalMenu
 * @param $aModuleMenu
 */
function OnBuildGlobalMenuHandler(&$aGlobalMenu, &$aModuleMenu)
{
    $menuElement = [ // кнопка в левом меню (главный компонент для вложенных элементов из $subMenuElement)
        'global_menu_api_psk' => [
            'menu_id' => 'apiPsk',
            'text' => 'Api ЛК',
            'title' => 'Api личного кабинета',
            'sort' => 1000,
            'items_id' => 'global_menu_api_psk',
            'help_section' => 'api_psk_lk',
            'items' => []
        ]
    ];

    $subMenuElement = [
        [// вложенный элемент
            'parent_menu' => 'global_menu_api_psk', // родительский элемент меню (идентификатор кнопки слева <$menuElement['items_id']>)
            'section' => 'root_api_psk_lk',              // идентификатор секции
            'sort' => 50,                          // порядок отображения элемента
            'text' => 'Страница управления',               // Надпись
            'title' => 'Страница управления функциями API',         // ?

            // название иконки слева от text меню, !спрайт, конфигурируется в имя class'a в теге span
            'icon' => '',

            'page_icon' => '',                            // иконка на странице
            'items_id' => 'menu_root_api_psk_lk',        // идентификатор элемента

            // ссылка перехода к файлу, !путь формируется от: 'корень_сайта/bitrix/admin/' . 'url'
            'url' => 'api_psk_dashboard.php' . '?lang=' . LANGUAGE_ID,

            'items' => [
                // дочерние элементы
            ]
        ],
            [ // вложенный элемент
                'parent_menu' => 'global_menu_api_psk', // родительский элемент меню (идентификатор кнопки слева <$menuElement['items_id']>)
                'section' => 'api_psk_lk',              // идентификатор секции
                'sort' => 100,                          // порядок отображения элемента
                'text' => 'Пункт меню со вложенными элементами', // Надпись
                'title' => 'Пункт со вложенными элементами',     // ?
                'icon' => '',                           // название иконки слева от text меню, !спрайт, конфигурируется в имя class'a в теге span
                'page_icon' => '',                      // иконка на странице
                'items_id' => 'menu_api_psk_lk',        // идентификатор элемента
                'items' => [
                    // дочерние элементы
                    [
                        'text' => 'Вложенный пункт меню',
                        'items_id' => 'menu_api_psk_lk_sub',
                        'title' => 'Тайтл вложенного пункта меню',
                        'more_url' => [
                            'choto_tam.php'
                        ],
                        'items' => [
                            // ...больше вложенных элементов
                        ]
                    ]
                ]
            ]
    ];

    $aGlobalMenu = array_merge($aGlobalMenu, $menuElement);

    // добавление позиций меню
    foreach ($subMenuElement as $subElement){
        $aModuleMenu[count($aModuleMenu)] = $subElement;
    }

    //echo '<pre>';
    //var_dump($aModuleMenu);
    //echo '</pre>';
}