<?php
namespace lib\usertype;

require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use Bitrix\Main\Loader,
    Bitrix\Main\Localization\Loc,
    Bitrix\Iblock;

class CUserTypeOrderItem
{

    /**
     * Метод возвращает массив описания собственного типа свойств
     *
     * @return array
     */
    public function GetUserTypeDescription(): array{
        return [
            'USER_TYPE_ID' => 'order_item',                         // уникальный идентификатор типа свойств
            'USER_TYPE' => 'ORDER_ITEM',                            // наименование пользовательского типа
            'CLASS_NAME' => __CLASS__,                              // имя класса
            'DESCRIPTION' => 'Позиция заказа',                      // текст описание
            'PROPERTY_TYPE' => Iblock\PropertyTable::TYPE_ELEMENT,  // тип свойства
            'ConvertToDB' => [__CLASS__, 'ConvertToDB'],
            'ConvertFromDB' => [__CLASS__, 'ConvertFromDB'],
            'GetPropertyFieldHtml' => [__CLASS__, 'GetPropertyFieldHtml'],
        ];
    }

    /**
     * Конвертирует данные перед сохранением в БД
     *
     * @param array $arProperty     Массив с описанием поля свойства в битрикс
     * @param array $value          Массив со значениями [ [VALUE] => array|value|empty , [DESCRIPTION] => value|empty ]
     * @return array|false
     */
    public static function ConvertToDB(array $arProperty,array $value){

        #print_p($arProperty,'$arProperty:');
        #print_p($value, '$value:');
        #die();

//        echo '<pre>';
//        var_dump($arProperty);
//        echo '</pre>';
//        echo PHP_EOL;
//        echo PHP_EOL;
//        echo '<pre>';
//        var_dump($value);
//        echo '</pre>';
//        die();

        //region Модель данных для конвертации:
        /*
         * pos1 a:2:
         *      {s:4:"guid";
         *       s:36:"16bcf118-b7c2-11eb-baa1-005056bb1249";
         *       s:15:"characteristics";
         *          a:2:{
         *              i:0;
         *              a:3:{s:4:"guid";s:36:"dc520c49-b7dc-11eb-baa1-005056bb1249";s:7:"orgguid";s:36:"b5e91d86-a58a-11e5-96ed-0025907c0298";s:8:"quantity";s:1:"2";}
         *              i:1;
         *              a:3:{s:4:"guid";s:36:"e430b430-b7dc-11eb-baa1-005056bb1249";s:7:"orgguid";s:36:"b5e91d86-a58a-11e5-96ed-0025907c0298";s:8:"quantity";s:1:"3";}
         *              }
         *      }
         * pos 2 a:2:
         *       {s:4:"guid";
         *        s:36:"d8964918-1adc-11e8-80dc-000c2938f7da";
         *        s:15:"characteristics";
         *          a:3:
         *              {
         *              i:0;
         *              a:3:{s:4:"guid";s:36:"05b66a93-1add-11e8-80dc-000c2938f7da";s:7:"orgguid";s:36:"b5e91d86-a58a-11e5-96ed-0025907c0298";s:8:"quantity";s:1:"1";}
         *              i:1;
         *              a:3:{s:4:"guid";s:36:"0e3eaa31-1add-11e8-80dc-000c2938f7da";s:7:"orgguid";s:36:"b5e91d86-a58a-11e5-96ed-0025907c0298";s:8:"quantity";s:1:"1";}
         *              i:2;
         *              a:3:{s:4:"guid";s:36:"0e3eaa32-1add-11e8-80dc-000c2938f7da";s:7:"orgguid";s:36:"b5e91d86-a58a-11e5-96ed-0025907c0298";s:8:"quantity";s:1:"2";}
         *              }
         *         }
         */
        //endregion
        if($value['VALUE']['GUID'] && $value['VALUE']['CHARACTERISTICS']){

            $arData['guid'] = $value['VALUE']['GUID'];

            foreach ($value['VALUE']['CHARACTERISTICS'] as $item){
                $arData['characteristics'][] = [
                    'guid' => $item['GUID'],
                    'orgguid' => $item['ORGGUID'],
                    'quantity' => $item['QUANTITY'],
                    //'price' => $item['PRICE'] будет добавлено позже - вохможно.
                ];
            }

            $value['VALUE'] = base64_encode(serialize($arData));

            return $value;

        }else{
            return false;
        }
    }

    /**
     * Конвертирует данные при извлечении из БД
     *
     * @param array $arProperty     Массив с описанием поля свойства в битрикс
     * @param array $value          Массив со значениями [ [VALUE] => array|value|empty , [DESCRIPTION] => value|empty ]
     * @param string $format
     * @return array|false
     */
    public static function ConvertFromDB(array $arProperty, array $value, $format = ''){

        #print_p($arProperty,'$arProperty:');
        #print_p($value, '$value:');
        #print_p($format,'$format:');

        if($value['VALUE'])
        {
            $tmp = $value['VALUE'];
            $tmp = unserialize(htmlspecialcharsback($tmp));

            if($tmp){
                return $value;
            }

            $value['VALUE'] = base64_decode($value["VALUE"]);
            return $value;

        }else{
            return false;
        }

    }

    /**
     * Представление формы редактирования значения
     *
     * @param array $arProperty     Массив с описанием поля свойства в битрикс
     * @param array $value          Массив значений из базы данных, функция $this->ConvertFromDB()
     * @param array $arHtmlControl  Служебный массив с данными для HTML разметки
     * @return string               Строка с HTML кодом разметки элемента
     */
    public static function GetPropertyFieldHtml(array $arProperty, array $value, array $arHtmlControl): string{

        #print_p($arProperty,'$arProperty:');
        #print_p($value,'$value:');
        #print_p(unserialize($value['VALUE']), 'Unserialize $value[VALUE]:');
        #print_p($arHtmlControl, '$arHtmlControl:');

        //region model
        /*
         * ['VALUE' => [
         *      'guid' => '16bcf118-b7c2-11eb-baa1-005056bb1249',
         *      'characteristics' => [
         *          [
         *              'guid' => 'dc520c49-b7dc-11eb-baa1-005056bb1249',
         *              'orgguid' => 'b5e91d86-a58a-11e5-96ed-0025907c0298',
         *              'quantity' => '2'
         *          ],
         *          [
         *              'guid' => 'dc520c49-b7dc-11eb-baa1-005056bb1249',
         *              'orgguid' => 'b5e91d86-a58a-11e5-96ed-0025907c0298',
         *              'quantity' => '1'
         *          ]
         *      ]
         * ]];
         */
        //endregion
        /**
         * @var array уникальные значения котроллера html, генерируются битриксом
         *
         * [0] => PROP  - не меняется, слово PROP
         * [1] => 1723  - $arProperty['ID'] идентификатор свойства, уникальный
         * [2] => n0     - уникальный номер поля свойства, инкримеруется битриксом, при отрисовки шаблона.
         *
         */
        $arSetting = array_values(array_filter(preg_split("/[\[.\]]/",$arHtmlControl['VALUE']))); //[VALUE] => PROP[1723][n0][VALUE]

        /**
         * @var string Идентификатор связанной таблицы с номенклатурами
         */
        //$iBlockIDNomenclature = \Environment::IBLOCK_ID_NOMENCLATURE;

        /**
         * @var string JS запрос для открытия окна с выбором связанного элемента номенклатуры.
         */
        //$openWindow = 'jsUtils.OpenWindow(\'/bitrix/admin/iblock_element_search.php?lang=ru&IBLOCK_ID='.$iBlockIDNomenclature.'&n=PROP['.$arProperty['ID'].']&k='.$arSetting[2].'&iblockfix=y&tableId=iblockprop-E-512-5\', 900, 700);';

        /**
         * @var string сгенерированный идентификатор для подстановки ID из высплывающего окна на JS (привязка элемента номенклатуры)
         */
        $inputID__NomenclatureID = 'PROP['.$arSetting[1].']['.$arSetting[2].']';

        /**
         * @var string сгенерированный идентификатор для подстановки названия из всплывающего окна на JS (привязка элемента номенклатуры)
         */
        $inputID__NomenclatureName = 'sp_' .md5('PROP['.$arSetting[1].']'). '_'. $arSetting[2];

        $itemId = 'row_' . substr(md5($arHtmlControl['VALUE']), 0, 10); //ID для js

        # htmlspecialcharsback нужен для того, чтобы избавиться от многобайтовых символов из-за которых не работает unserialize()
        $arValue = unserialize(htmlspecialcharsback($value['VALUE']));

        /**
         * @var string Идентификатор связанного элемента номенклатуры
         */
        //$nomenclatureID = ($arValue['NOMENCLATURE_ID']) ? $arValue['NOMENCLATURE_ID'] : '';

        /**
         * @var string Количество позиций элемента номенклатуры
         */
        //$quantity = ($arValue['AMOUNT']) ? $arValue['AMOUNT'] : '';

        /**
         * @var string Стоимость за 1 еденицу товара
         */
        //$cost = ($arValue['COST']) ? $arValue['COST'] : '';

        /**
         * @var string Наименование номенклатуры
         */
        //$nomenclatureName = '';
/*
        #Обертка над элементом
        $html  = '<div class="property_row" id="'. $itemId .'">';
        #Поле для значения идентификатора номенклатуры
        $html .= '<input name="'.$arHtmlControl['VALUE'] .'[NOMENCLATURE_ID]" id="'.$inputID__NomenclatureID.'" value="'. $nomenclatureID.'" size="5" type="text">';
        #Кнопка для выбора идентификатора номенклатуры
        $html .= '<input type="button" value="..." onclick="alert(\'Тестовый режим!\')">';
        #Поле для количества позиций у идентификатора номенклатуры
        $html .= '&nbsp;Количество: <input name="'.$arHtmlControl['VALUE'].'[AMOUNT]" id="" value="'. $quantity .'" size="5" type="text">';

        //Поле стоимость за еденинцу товара
        $html .= '&nbsp;Цена за ед.: <input name="'.$arHtmlControl['VALUE'].'[COST]" id="" value="'. $cost .'" size="5" type="text">';

        # Кнопка удалить элемент
        if($nomenclatureID){
            $html .= '&nbsp;&nbsp;<input type="button" style="height: auto;" value="x" title="Удалить"';
            $html .= 'onclick="document.getElementById(\''. $itemId .'\').parentNode.parentNode.remove()"';
            $html .= '>';
        }

        # Подпись (имя номенклатуры ![доработать] - появляется только при выборе элемента, при обновлении пропадает)
        $html .= '</br><b><span id="'.$inputID__NomenclatureName.'">'.$nomenclatureName.'</span></b>';
        $html .= '</div>';
*/
        //return $html;

        $TwigLoader = new \Twig_Loader_Filesystem($_SERVER['DOCUMENT_ROOT'] . '/local/src/twig_templates/usertype');
        $Twig = new \Twig_Environment($TwigLoader);
        $template = $Twig->loadTemplate('CUserTypeOrderItemTemplate.html');

        return $template->render([
            'itemId' => $itemId,
            'setting' => $arSetting,
            'product' => (!$arValue) ? [] : $arValue
        ]);
    }

}