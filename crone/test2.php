<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<? include($_SERVER["DOCUMENT_ROOT"] . "/include/php/colors.php"); ?>

<?
/**
 * @global array $arResult
 */
/*
if($USER->IsAdmin() && $USER->IsAuthorized()){

var_dump(__DIR__);

echo $_SERVER["DOCUMENT_ROOT"].$templateFolder."/basket_items.php" . PHP_EOL;

echo $_SERVER["DOCUMENT_ROOT"] . PHP_EOL;
echo $templateFolder . PHP_EOL;
echo "/basket_items.php" . PHP_EOL;

echo '<pre>';
var_dump($arResult);
echo '</pre>';
}
*/
?>

<? if (count($arResult["ITEMS"]["AnDelCanBuy"]) > 0):
//_p( $_SESSION["oooooooooo"])
?>

<div id="order_form_div">
    <div class="progress">
        <div class="step step-1">
            <div class="step__img"></div>
            <div class="step__name">Оформление</div>
        </div>
        <div class="divider">- - -</div>
        <div class="step step-2">
            <div class="step__img"></div>
            <div class="step__name">Параметры</div>
        </div>
        <div class="divider">- - -</div>
        <div class="step step-3">
            <div class="step__img"></div>
            <div class="step__name">Подтверждение</div>
        </div>
        <div class="divider">- - -</div>
        <div class="step step-4">
            <div class="step__img"></div>
        </div>
    </div>

    <div id="form_new">


        <form method="POST" action="" name="" id="ORDER_FORM_ID">
            <?= bitrix_sessid_post(); ?>
            <div>
                <input type="hidden" name="form" value="Y"/>
                <input type="hidden" value="" name="BasketRefresh" onClick="submitForm();">
                <div class="goods-cont">
                    <div class="goods-cont__inner">


                        <? foreach ($arResult["ITEMS"]["AnDelCanBuy"] as $item) {
                            if ($item['PRODUCT_PRICE_ID'] == '45991') {
                                $retail = true;
                                break;
                            } else {
                                $retail = false;
                            }
                        }
                        ?>

                        <? foreach ($arResult["BASKET_ITEMS"] as $id => $arBasketItems) {
                            $file_img = CFile::ResizeImageGet($arBasketItems['PREVIEW_PICTURE'], array('width' => 246, 'height' => 370), BX_RESIZE_IMAGE_EXACT, true);
                            $action = $arBasketItems["PROPERTIES"]["SVOYSTVAPRAYSA"]["VALUE"];
                            ?>

                            <div class="goods" data-id="<?= $arBasketItems['ID'] ?>"
                                 data-article="<?= $arBasketItems['PROPERTY_CML2_ARTICLE_VALUE'] ?>">
                                <div class="goods__top">
                                    <a href="<?= $arBasketItems['DETAIL_PAGE_URL'] ?>" class="goods__top-img">
                                        <?

                                        switch ($action) {
                                            case "Распродажа":
                                                echo "<img class='action_icon' src='/include/css/icons/icon_saleProd.png' alt='{$action}' title='{$action}' />";
                                                break;
                                            case "Новинка":
                                                echo "<img class='action_icon' src='/include/css/icons/icon_newProd.png' alt='{$action}' title='{$action}' />";
                                                break;
                                            case "Акция":
                                                echo "<img class='action_icon' src='/include/css/icons/icon_akciiProd.png' alt='{$action}' title='{$action}' />";
                                                break;
                                            default:
                                                // echo "параметр спецпредложение имеет не зарегистрированное значение: {$action}";
                                        } ?>
                                        <img src="<?= $file_img['src'] ?>">
                                    </a>
                                    <?
                                    if(empty($arBasketItems['OFFERS'])){
                                        $arBasketItems['OFFERS'][] = $arBasketItems['OFFERS'];
                                    }
                                    foreach ($arBasketItems['OFFERS'] as $position) {
                                        $positionsIds[] = $position['BASKET_ITEM']['ID'];
                                    }
                                    $positionsCount = count($positionsIds);
                                    ?>
                                    <a href="<?= $APPLICATION->GetCurPage() . "?action=delete_element&data-id=" . implode(',', $positionsIds) ?>"
                                       class="_top-delete" data-id="<?= implode(',', $positionsIds) ?>">
                                        <?
                                        unset($positionsIds); ?>
                                        <span class="icon"></span>
                                        <span class="label">Удалить <br>позицию <br>из корзины</span>
                                    </a>
                                </div>
                                <div class="goods__body">

                                    <div class="name"><a
                                            href="<?= $arBasketItems['DETAIL_PAGE_URL'] ?>"><?= $arBasketItems['PROPERTY_KRASIVOENAZVANIE_VALUE'] ?></a>
                                    </div>
                                    <div class="desc desc1">
                                        Артикул: <?= $arBasketItems['PROPERTY_CML2_ARTICLE_VALUE'] ?></div>
                                    <div class="row">
                                        <div class="desc desc2"><?= $arBasketItems['PROPERTY_TIPTOVARASPISOK_VALUE'] ?></div>

                                        <div class="info-pricer">

                                            <div class="info-pricer__type <?= (!$retail) ? 'active' : '' ?>">опт</div>
                                            <div class="info-pricer__type <?= ($retail) ? 'active' : '' ?>">розница
                                            </div>
                                        </div>

                                    </div>
                                    <div class="positions">
                                        <?
                                        foreach ($arBasketItems['OFFERS'] as $position):?>
                                            <? if (empty($position['PROPERTIES']['CML2_LINK']['VALUE'])){
                                                $position['PROPERTIES']['CML2_LINK']['VALUE']=$position["ID"];
                                            }
                                            asort($position['PROPERTIES']['_03RAZMER']['VALUE']);
                                            asort($position['PROPERTIES']['_04ROST']['VALUE']);
                                            ?>
                                            <div class="position" id="position_<?= $position['ID'] ?>">
                                                <div class="position__top">
                                                    <div class="position__labels">

                                                        <?if(count($arResult['SIZES'][$arBasketItems['ID']]) > 0):?>
                                                            <? foreach ($position['PROPERTIES'] as $prop): ?>

                                                                <? if ($prop['CODE'] == '_04ROST' && $prop['VALUE'] != ''): ?>
                                                                    <span>РАЗМЕРЫ И РОСТ</span>

                                                                    <? break; ?>
                                                                <? elseif ($prop['CODE'] == '_04ROST' && $prop['VALUE'] == ''): ?>

                                                                    <span>РАЗМЕР</span>
                                                                    <? break; ?>
                                                                <? elseif ($prop['CODE'] == '_03RAZMER' && $prop['VALUE'] == 'Универсальный'): ?>

                                                                    <span>РАЗМЕР</span>
                                                                    <? break; ?>
                                                                <? endif; ?>

                                                            <? endforeach; ?>
                                                        <? endif; ?>
                                                        <? if (!empty($arResult['COLOR_HL'])): ?>
                                                            <?if(count($arResult['COLOR_HL'][$arBasketItems['ID']]) > 0):?>
                                                                <span>ЦВЕТ</span>
                                                            <? endif; ?>
                                                        <? endif; ?>

                                                    </div>
                                                    <div class="position__selects">
                                                        <?
                                                        if (!empty($position['PROPERTIES'])) {

                                                            foreach ($position['PROPERTIES'] as $prop) {
                                                                $position_props[$prop['CODE']] = $prop['VALUE'];
                                                            }
                                                            $position_props_flag = 'Y';


                                                        } else {
                                                            unset($position_props);
                                                            $position_props['_03RAZMER'] = 'Универсальный';
                                                            $position_props_flag = 'N';
                                                        }
                                                        $propIsset = false;
                                                        ?>
                                                        <?if(count($arResult['SIZES'][$arBasketItems['ID']]) > 0):
                                                            $propIsset = true;?>
                                                            <div class="sizes">
                                                                <input type="checkbox" id="sizes_<?= $position['ID'] ?>"
                                                                       class="sizes__input always-uncheck">
                                                                <?
                                                                if ($position_props_flag == 'Y'):?>
                                                                    <label for="sizes_<?= $position['ID'] ?>"
                                                                           data-id="<?= $position['ID'] ?>"
                                                                           class="sizes__inner s1 <?= (!empty($position_props['_04ROST'])) ? 'both-sizes' : '' ?> ">
                                                                        <span><?= $position_props['_03RAZMER'] ?></span>
                                                                        <span class="growth"><?= $position_props['_04ROST'] ?></span>
                                                                        <ul class="select">
                                                                            <? if (!empty($arResult['HEIGHT'])): ?>
                                                                                <? foreach ($arResult['SIZES'][$arBasketItems['ID']] as $key => $size):
                                                                                    if($size == ""){
                                                                                        continue;
                                                                                    }
                                                                                    ?>
                                                                                    <li class="option <?//= $arResult['HEIGHT'][$arBasketItems['ID']]['QUANTITY'][$key] == 0 ? 'backet__disabled-li' : '' ?> "
                                                                                        data-values="<?= $size ?>;<?= $arResult['HEIGHT'][$arBasketItems['ID']]['VALUE'][$key] ?>">
                                                                                        <span><?= $size ?></span><span><?= $arResult['HEIGHT'][$arBasketItems['ID']]['VALUE'][$key] ?></span>
                                                                                    </li>
                                                                                <? endforeach; ?>
                                                                            <? elseif (empty($arResult['HEIGHT']) && !empty($arResult['SIZES'])): ?>
                                                                                <? foreach ($arResult['SIZES'] as $size): ?>
                                                                                    <li class="option"
                                                                                        data-values="<?= $size ?>;0">
                                                                                        <span><?= $size ?></span></li>
                                                                                <? endforeach; ?>
                                                                            <? elseif ($position_props['SIZE'] == 'Универсальный'): ?>
                                                                                <li class="option" data-values="0"><span>Универсальный</span>
                                                                                </li>
                                                                                <? unset($position_props); ?>
                                                                            <? endif; ?>

                                                                        </ul>
                                                                    </label>
                                                                <? endif; ?>

                                                            </div>
                                                        <? endif; ?>
                                                        <?if(count($arResult['COLOR_HL'][$arBasketItems['ID']]) > 0):
                                                            $propIsset = true;?>
                                                            <div class="colors">
                                                                <? if ($position_props_flag == 'Y'): ?>
                                                                    <input type="checkbox"
                                                                           id="colors_<?= $position['ID'] ?>"
                                                                           class="colors__input always-uncheck">
                                                                    <input style="display: none" name="cur_color"
                                                                           value="<?= $position['PROPERTIES']['CML2_LINK']['VALUE'] ?>">
                                                                    <label for="colors_<?= $position['ID'] ?>"
                                                                           data-id="<?= $position['ID'] ?>"
                                                                           class="colors__inner">
                                                                        <? if (count($arResult['COLOR_HL'][$arBasketItems['ID']])) {
                                                                            $colorsArr = [];
                                                                            $dataCountColor = 1; ?>
                                                                            <div class="colors-tools__colors"
                                                                                <? foreach ($arResult['COLOR_HL'][$arBasketItems['ID']] as $key => $item) {
                                                                                $colorsArr[] = trim(substr($item['UF_NAME'], 0, strpos($item['UF_NAME'], "["))); ?>
                                                                                 data-color<?= $dataCountColor ?>="<?= $item['UF_NAME'] ?>"
                                                                                 <?
                                                                                 $dataCountColor++;
                                                                                 }
                                                                                 ?>data-name="<?= implode('/', $colorsArr) ?>"></div>
                                                                        <? } ?>

                                                                        <div class="color-name-cart"><?= implode('/', $colorsArr) ?></div>

                                                                        <div class="select">
                                                                            <span>изменить <br>цвет</span>
                                                                            <div class="select__pieces">
                                                                                <? foreach ($arResult['COLOR_ARTICLE'][$arBasketItems['PROPERTY_CML2_ARTICLE_VALUE']] as $key => $arItem):
                                                                                    $countColor = 1;
                                                                                    $colorsArr = [];
                                                                                    foreach ($arItem as $key2 => $item) :
                                                                                        if ($item['QUANTITY'][$arBasketItems['ID']]) : ?>
                                                                                            <div class="colors-tools__colors colors__pieces data-color-id"
                                                                                            <? $colorsArr[] = trim(substr($item['UF_NAME'], 0, strpos($item['UF_NAME'], "["))); ?>
                                                                                                 data-color<?= $countColor ?>="<?= $item['UF_NAME'] ?>"
                                                                                                 data-color-id="<?= $key ?>"
                                                                                            <? $countColor++; ?>
                                                                                            <? if (!$arItem[$key2 + 1]['QUANTITY'][$arBasketItems['ID']]) : ?>
                                                                                                data-name="<?= implode('/', $colorsArr) ?>"></div>
                                                                                            <? endif; ?>
                                                                                        <? else :
                                                                                            if ($item['QUANTITY'])
                                                                                                ?>
                                                                                                <div class="colors-tools__colors colors__pieces data-color-id"
                                                                                            <? $colorsArr[] = trim(substr($item['UF_NAME'], 0, strpos($item['UF_NAME'], "["))); ?>
                                                                                            data-color<?= $countColor ?>="<?= $item['UF_NAME'] ?>"
                                                                                            data-color-id="<?= $key ?>"
                                                                                            <? $countColor++; ?>
                                                                                            <? if (!$arItem[$key2 + 1]['QUANTITY']): ?>
                                                                                            data-name="<?= implode('/', $colorsArr) ?>"></div>
                                                                                        <? endif; ?>
                                                                                        <? endif; ?>
                                                                                    <? endforeach; ?>
                                                                                <? endforeach; ?>
                                                                            </div>
                                                                        </div>
                                                                    </label>
                                                                <? endif; ?>
                                                                <? unset($position_props); ?>
                                                            </div>
                                                        <? endif; ?>
                                                    </div>
                                                </div>
                                                <div class="position__bottom">



                                                    <?
                                                    if($position['PROPERTIES']['CML2_BASE_UNIT']['VALUE'] == 'упа') {
                                                        if($position['PROPERTIES']['UPAKOVKA']['VALUE'] > 1){
                                                            $step = $position['PROPERTIES']['UPAKOVKA']['VALUE'];
                                                        } else {
                                                            $step = 1;
                                                        }
                                                    } else {
                                                        $step = 1;
                                                    }
                                                    ?>

                                                    <input type="hidden" name="step_<?= $position['BASKET_ITEM']['ID'] ?>" value="<?=$step;?>" />

                                                    <div class="quantity">
                                                        <div class="quantity__box">
                                                            <button class="quantity__box-minus"
                                                                    id="cart-quantity-minus"></button>
                                                            <input type="number"
                                                                   value="<?= $position['BASKET_ITEM']['QUANTITY'] ?>"
                                                                   id="QUANTITY_<?= $position['BASKET_ITEM']['ID'] ?>"
                                                                   name="QUANTITY_<?= $position['BASKET_ITEM']['ID'] ?>">
                                                            <button class="quantity__box-plus"
                                                                    id="cart-quantity-plus"></button>
                                                        </div>
                                                        <span class="quantity__label">
														<?if($step > 1):?>
                                                            шт
                                                        <?else:?>
                                                            <?= $position['BASKET_ITEM']['MEASURE_NAME'] ?>
                                                        <?endif?>
                                                            .</span>
                                                        <a href="<?= $APPLICATION->GetCurPage() . "?action=delete&id=" . $position['BASKET_ITEM']['ID'] ?>"
                                                           class="quantity__delete"
                                                           data-id="<?= $position['BASKET_ITEM']['ID'] ?>"></a>
                                                    </div>
                                                    <div class="price">
                                                        <div class="price__type"><?= (!$retail) ? 'опт' : 'розница' ?></div>
                                                        <div class="price__value">
                                                            <div class="price__value-inner">
                                                                <div>
                                                                    <?
                                                                    $price = floatval($position['BASKET_ITEM']['PRICE']) * intval($position['BASKET_ITEM']['QUANTITY']);
                                                                    ?>
                                                                    <ins class="big"><?= round($price, 2)/*substr($position['PRICE']*$position['QUANTITY'], 0, -2)*/ ?></ins>
                                                                    <span><?= $GLOBALS["FormatCur"] ?></span>
                                                                </div>
                                                                <div class="price-type-label">у вас выбрана
                                                                    <ins><?= ($retail) ? 'розничная' : 'оптовая' ?></ins>
                                                                    цена
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <? endforeach; ?>

                                    </div>
                                    <?//if($positionsCount > 1):?>
                                    <? if ($propIsset){?>
                                        <a id="cart-position-add" class="position-add">
                                            <div class="icon"></div>
                                            <span>Добавить другой цвет или размер</span>
                                        </a>
                                    <? }?>
                                    <?//endif;?>
                                </div>
                            </div>
                        <? } ?>
                    </div>
                </div>


                <? else: ?>
                    <p style="padding: 40px 0 50px 19px;
    text-align: center;
    font-size: 18px;">В вашей корзине <b style="color:red">нет товара</b>, перейдите в <a href="/catalog/">каталог</a>,
                        чтобы добавить товар в корзину! <br>
                        Также обратите внимание на товарные <a href="/novosti/novinki-assortimenta/">новинки</a> и <a
                            href="/spetspredlozheniya/rasprodazha.php">специальные предложения</a>!
                    </p>
                <? endif; ?>
