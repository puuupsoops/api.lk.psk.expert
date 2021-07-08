<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');
$APPLICATION->SetTitle("Личный кабинет");

//подключение библиотек 
$arJsConfig = array( 
	'Calculator' => [ 
        'js' => '/test/product-page/Calculator.js', 
    ],
	'Product' => [
		'js' => '/test/product-page/Product.js',
	],
	'Order' => [
		'js' => '/test/product-page/Order.js',
	],
	'ItemPosition' => [
		'js' => '/test/product-page/ItemPosition.js',
	],
	'ItemOffer' => [
		'js' => '/test/product-page/ItemOffer.js',
	],  
); 

foreach ($arJsConfig as $ext => $arExt) { 
    \CJSCore::RegisterExt($ext, $arExt); 
}

CUtil::InitJSCore(['Calculator','Product','Order','ItemPosition','ItemOffer']);

?>
<?php if( $USER->IsAuthorized() ):?>

<div class="content-heading-wrap proudct-heading-wrap">
        <div class="content-heading-wrap-elem">
            <div class="content-heading"><lable id="product-name"></lable><lable id="product-article"> </lable></div>
        </div>

        <div class="content-heading-wrap-elem">
                <div class="content-heading-price">
                    <div class="content-heading-price-text">Ваша цена: </div>
                    <div class="content-heading-price-value"><lable id="product-price"></lable> ₽</div>
                </div>

                <div class="content-heading-btn">
                    <svg class="content-heading-btn-img" width="30" height="29" viewBox="0 0 30 29" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path class="fill" fill-rule="evenodd" clip-rule="evenodd" d="M3 2H18.75C19.3023 2 19.75 2.44772 19.75 3V11.4552C20.0954 11.414 20.447 11.3928 20.8036 11.3928C21.1233 11.3928 21.4391 11.4099 21.75 11.4431V3C21.75 1.34315 20.4069 0 18.75 0H3C1.34315 0 0 1.34315 0 3V21.8571C0 23.514 1.34315 24.8571 3 24.8571H13.3336C12.9464 24.2379 12.634 23.567 12.4092 22.8571H3C2.44772 22.8571 2 22.4094 2 21.8571V3C2 2.44772 2.44772 2 3 2Z" fill="#A5A7A9"></path>
                        <circle class="stroke" cx="20.8036" cy="20.1964" r="7.80357" stroke="#A5A7A9" stroke-width="2"></circle>
                        <rect class="fill" x="17.6072" y="5.17847" width="1.7" height="13.4643" rx="0.85" transform="rotate(90 17.6072 5.17847)" fill="#A5A7A9"></rect>
                        <rect class="fill" x="11.3928" y="13" width="1.7" height="7.25" rx="0.85" transform="rotate(90 11.3928 13)" fill="#A5A7A9"></rect>
                        <rect class="fill" x="14.5" y="9" width="1.7" height="10.3571" rx="0.85" transform="rotate(90 14.5 9)" fill="#A5A7A9"></rect>
                        <rect class="fill" x="19.79" y="15.5" width="2" height="8" rx="1" fill="#A5A7A9"></rect>
                        <rect class="fill" x="23.6001" y="19.5696" width="2" height="6" rx="1" transform="rotate(45 23.6001 19.5696)" fill="#A5A7A9"></rect>
                        <rect class="fill" x="16.5" y="20.9839" width="2" height="6" rx="1" transform="rotate(-45 16.5 20.9839)" fill="#A5A7A9"></rect>
                    </svg>
                    <div class="content-heading-btn-text">Загрузить xls</div>
                </div>
        </div>

            <div class="content-heading-info">
                <div class="content-heading-info-elem"> 
                    <span class="content-heading-info-text">Скидка: </span>
                    <span class="content-heading-info-value">Не распостроняется</span>
                </div>
                <div class="content-heading-info-elem"> 
                    <span class="content-heading-info-text">Статус товара: </span>
                    <span class="content-heading-info-value"><label id="product-status"></label></span>
                </div>
            </div>
</div>

<div class="content-wrap content-product-wrap">

<!--------------------------------------------------------------------------------------------------------------------------->
        <div class="content-wrap-elem">
            <div class="product-search content-elem">

            <div class="product-search-top">

                <div class="product-search-top-elem"> 
                <select id='query-select-option' class="custom-select" style="width: 100%" onchange="selectHistorySearchType(this)">
                    <option value="0" selected>Артикул</option>
                    <option value="1" >Наименование</option>
                    <option value="2" disabled>По совпадению</option>
                </select>
                </div>

                <div class="product-search-top-elem">
                <div class="product-search-text">Содержит: </div>
                <select class="custom-select article-history-list" style="width: 100%" onchange="doSearch(this,$('#query-select-option'))">
                    <option value="0" selected>КОС 598</option>
                    <option value="1">КОС 600</option>
                    <option value="2">КОС 358</option>
                    <option value="3">КОС 700</option>
                </select>
                <div class="product-search-clear"></div>
                </div>

            </div>

                <div class="product-search-bottom scroll-elem">
                    <div class="table-more-info-arrow"></div>
                        <div id="table-found" class="table product-search-table">
                                <div class="table-row table-heading">
                                    <div class="table-elem">Артикул</div>
                                    <div class="table-elem">Наименование</div>
                                </div>
                            </div>
                        </div>
                    </div>
        </div>
<!--------------------------------------------------------------------------------------------------------------------------->

    <div class="content-wrap-elem">
        <div class="content-elem">
            <div class="order-amount-table-wrap scroll-elem">
                    <div class="table-more-info-arrow"></div>

                    <div class="table order-amount-table">

                                    <div class="table-row table-heading">
                                        <div class="table-elem">Характеристика</div>
                                        <div class="table-elem">Остаток</div>
                                        <div class="table-elem">Цена</div>
                                        <div class="table-elem">Количество</div>
                                        <div class="table-elem">Пп / Дата</div>
                                    </div>
                                    
                            <div id="product-offer-list" class="table-wrap">

                            </div>
                    </div>
            </div>
            <div id="add-order-position-btn" class="order-amount-more">
                <span class="order-amount-more-text">+ Добавить </span>
                <span class="order-amount-more-value"></span>
            </div>
        </div>
    </div>

<!--------------------------------------------------------------------------------------------------------------------------->
</div>
<!-------------------------------------------------ШАПКА ЗАКАЗА-------------------------------------------------------->
    <div class="content-heading-wrap proudct-heading-wrap">
        <div class="content-heading-wrap-elem">
            <div class="content-heading">Заказ № <lable id="order-id">0</lable></div>
        </div>
        <div class="content-heading-wrap-elem">
            <div class="content-heading-price">
                <div class="content-heading-price-text">Сумма заказа: </div>
                <div class="content-heading-price-value"><lable id="order-total-sum">0</lable> ₽</div>
            </div>
        </div>
        <div class="content-heading-info">
            <div class="content-heading-info-elem"> 
                <span class="content-heading-info-text">Колечество едениц: </span>
                <span class="content-heading-info-value"><lable id="order-total-amount">0</lable></span>
            </div>
            <div class="content-heading-info-elem"> 
                <span class="content-heading-info-text">Общий вес: </span>
                <span class="content-heading-info-value"><lable id="order-total-weight">0</lable> кг.</span>
            </div>
            <div class="content-heading-info-elem"> 
                <span class="content-heading-info-text">Общий объем: </span>
                <span class="content-heading-info-value"><lable id="order-total-volume">0</lable> м.куб.</span>
            </div>
        </div>
    </div>
<!--------------------------------------------------------------------------------------------------------------------------->
<div class="content-wrap content-order-wrap">
<!--------------------------------------------------ОБЕРТКА ЗАКАЗА----------------------------------------------------------->
    <div class="content-wrap-elem">

                    <div class="order-list content-elem">
                        <div class="order-list-top">
                            
                            <div class="order-list-top-elem">
                                <div class="product-search-text">Контрагент:</div>
                                    <select class="custom-select" style="width: 100%">
                                        <option value="0" selected>ООО “Тристан”</option>
                                        <option value="1">ООО “Тристан #2”</option>
                                        <option value="2">ООО “Тристан #3”</option>
                                        <option value="3">ООО “Тристан #4”</option>
                                    </select>
                            </div>

                            <div class="order-list-top-elem">
                                <button class="order-list-btn">Добавить печатный каталог</button>
                            </div>
                        </div>


                        <div class="order-list-bottom scroll-elem">
                            <div class="table-more-info-arrow"></div>
                            <div id="order-table" class="order-list-bottom-wrap">
                                <div class="order-list-row order-list-heading">
                                    <div class="order-list-elem">№</div>
                                    <div class="order-list-elem">Наименование</div>
                                    <div class="order-list-elem">Цена</div>
                                    <div class="order-list-elem">Кол-во</div>
                                    <div class="order-list-elem">Стоимость</div>
                                    <div class="order-list-elem">Комп.</div>
                                </div>


                                <div class="order-list-item">
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="order-list-buttons">
                        <button class="order-list-submit gradient-btn"> 
                            <div class="gradient-btn-text">Оформить заказ</div>
                        </button>

                        <div class="order-list-buttons-wrap">

                            <div class="order-list-buttons-item later">
                                <svg class="order-list-buttons-item-img" width="31" height="32" viewBox="0 0 31 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M3 4.5H18.75C19.3023 4.5 19.75 4.94772 19.75 5.5V14.0173C20.2318 13.9356 20.727 13.8931 21.2321 13.8931C21.406 13.8931 21.5786 13.8981 21.75 13.908V5.5C21.75 3.84315 20.4069 2.5 18.75 2.5H3C1.34315 2.5 0 3.84315 0 5.5V24.3571C0 26.014 1.34315 27.3571 3 27.3571H13.762C13.3748 26.7379 13.0625 26.067 12.8377 25.3571H3C2.44772 25.3571 2 24.9094 2 24.3571V5.5C2 4.94772 2.44772 4.5 3 4.5Z" fill="#A5A7A9"></path>
                                <circle cx="21.2321" cy="22.6961" r="7.80357" stroke="#A5A7A9" stroke-width="2"></circle>
                                <rect x="19.6786" y="18.0356" width="1.7" height="6.21429" rx="0.85" fill="#A5A7A9"></rect>
                                <rect x="24.8571" y="22.6001" width="1.7" height="5.17857" rx="0.85" transform="rotate(90 24.8571 22.6001)" fill="#A5A7A9"></rect>
                                <rect x="6" y="8" width="2" height="2" rx="1" transform="rotate(90 6 8)" fill="#A5A7A9"></rect>
                                <rect x="6" y="12" width="2" height="2" rx="1" transform="rotate(90 6 12)" fill="#A5A7A9"> </rect>
                                <rect x="6" y="16" width="2" height="2" rx="1" transform="rotate(90 6 16)" fill="#A5A7A9"></rect>
                                <rect x="6" y="20" width="2" height="2" rx="1" transform="rotate(90 6 20)" fill="#A5A7A9"></rect>
                                <rect x="10" y="8" width="2" height="2" rx="1" transform="rotate(90 10 8)" fill="#A5A7A9"></rect>
                                <rect x="10" y="12" width="2" height="2" rx="1" transform="rotate(90 10 12)" fill="#A5A7A9"></rect>
                                <rect x="10" y="16" width="2" height="2" rx="1" transform="rotate(90 10 16)" fill="#A5A7A9"></rect>
                                <rect x="14" y="16" width="2" height="2" rx="1" transform="rotate(90 14 16)" fill="#A5A7A9"></rect>
                                <rect x="10" y="20" width="2" height="2" rx="1" transform="rotate(90 10 20)" fill="#A5A7A9"></rect>
                                <rect x="14" y="8" width="2" height="2" rx="1" transform="rotate(90 14 8)" fill="#A5A7A9"></rect>
                                <rect x="14" y="12" width="2" height="2" rx="1" transform="rotate(90 14 12)" fill="#A5A7A9"></rect>
                                <rect x="18" y="8" width="2" height="2" rx="1" transform="rotate(90 18 8)" fill="#A5A7A9"></rect>
                                <rect x="18" y="12" width="2" height="2" rx="1" transform="rotate(90 18 12)" fill="#A5A7A9"></rect>
                                <rect x="5" y="0.5" width="2" height="6" rx="1" fill="#A5A7A9"></rect>
                                <rect x="10" y="0.5" width="2" height="6" rx="1" fill="#A5A7A9"></rect>
                                <rect x="15" y="0.5" width="2" height="6" rx="1" fill="#A5A7A9"></rect>
                                </svg>
                            </div>

                            <div class="order-list-buttons-item mail">
                                <svg class="order-list-buttons-item-img" width="41" height="20" viewBox="0 0 41 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M37.4649 2H18.5787L26.6432 9.10183L37.4649 2ZM16.7388 3.03554L14.7128 15.9112C14.6173 16.518 15.0864 17.0667 15.7007 17.0667H35.6268C36.1191 17.0667 36.5382 16.7084 36.6147 16.2221L38.5935 3.64601L27.2621 11.0824C27.2165 11.1123 27.1693 11.138 27.1209 11.1596C26.7546 11.4186 26.2422 11.4065 25.8863 11.0931L16.7789 3.07285C16.7651 3.06071 16.7517 3.04827 16.7388 3.03554ZM14.7931 2.53369C15.0227 1.07482 16.2798 0 17.7567 0H37.6829C39.5257 0 40.9328 1.6459 40.6464 3.46631L38.5904 16.533C38.3608 17.9918 37.1037 19.0667 35.6268 19.0667H15.7007C13.8579 19.0667 12.4507 17.4208 12.7371 15.6004L14.7931 2.53369Z" fill="#A5A7A9"></path>
                                <rect x="3.7" y="4" width="8" height="2" rx="1" fill="#A5A7A9"></rect>
                                <rect y="8" width="11" height="2" rx="1" fill="#A5A7A9"></rect>
                                <rect x="5.30003" y="12" width="5" height="2" rx="1" fill="#A5A7A9"></rect>
                                </svg>
                            </div>

                            <div class="order-list-buttons-item print">
                                <svg class="order-list-buttons-item-img" width="24" height="27" viewBox="0 0 24 27" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M21 7H3C2.44772 7 2 7.44772 2 8V17C2 17.5523 2.44772 18 3 18H21C21.5523 18 22 17.5523 22 17V8C22 7.44772 21.5523 7 21 7ZM3 5C1.34315 5 0 6.34315 0 8V17C0 18.6569 1.34315 20 3 20H21C22.6569 20 24 18.6569 24 17V8C24 6.34315 22.6569 5 21 5H3Z" fill="#A5A7A9"></path>
                                <rect class="notfill" x="4" y="13" width="16" height="7" fill="#1F2227"></rect>
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M17 2H7C6.44772 2 6 2.44772 6 3V5C6 5.55228 6.44772 6 7 6H17C17.5523 6 18 5.55228 18 5V3C18 2.44772 17.5523 2 17 2ZM7 0C5.34315 0 4 1.34315 4 3V5C4 6.65685 5.34315 8 7 8H17C18.6569 8 20 6.65685 20 5V3C20 1.34315 18.6569 0 17 0H7Z" fill="#A5A7A9"></path>
                                <rect class="notfill" x="3" y="7" width="18" height="7" fill="#1F2227"></rect>
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M17 15H7C6.44772 15 6 15.4477 6 16V24C6 24.5523 6.44772 25 7 25H17C17.5523 25 18 24.5523 18 24V16C18 15.4477 17.5523 15 17 15ZM7 13C5.34315 13 4 14.3431 4 16V24C4 25.6569 5.34315 27 7 27H17C18.6569 27 20 25.6569 20 24V16C20 14.3431 18.6569 13 17 13H7Z" fill="#A5A7A9"></path>
                                <rect x="16" y="17" width="2" height="8" rx="1" transform="rotate(90 16 17)" fill="#A5A7A9"></rect>
                                <rect x="16" y="21" width="2" height="8" rx="1" transform="rotate(90 16 21)" fill="#A5A7A9"></rect>
                                <rect x="20" y="9" width="2" height="3" rx="1" transform="rotate(90 20 9)" fill="#A5A7A9"></rect>
                                </svg>
                            </div>

                            <div class="order-list-buttons-item save">
                                <svg class="order-list-buttons-item-img" width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path class="stroke" d="M1 3.5C1 2.39543 1.89543 1.5 3 1.5H16C19.866 1.5 23 4.63401 23 8.5V21.5C23 22.6046 22.1046 23.5 21 23.5H3C1.89543 23.5 1 22.6046 1 21.5V3.5Z" stroke="#A5A7A9" stroke-width="2"></path>
                                <rect x="4" y="13.5" width="2" height="10" rx="1" fill="#A5A7A9"></rect>
                                <rect x="18" y="13.5" width="2" height="10" rx="1" fill="#A5A7A9"></rect>
                                <rect x="4" y="15.5" width="2" height="16" rx="1" transform="rotate(-90 4 15.5)" fill="#A5A7A9"></rect>
                                <rect x="6" y="9.5" width="2" height="12" rx="1" transform="rotate(-90 6 9.5)" fill="#A5A7A9"></rect>
                                <rect x="8" y="9.5" width="2" height="8" rx="1" transform="rotate(180 8 9.5)" fill="#A5A7A9"></rect>
                                <rect x="18" y="9.5" width="2" height="8" rx="1" transform="rotate(180 18 9.5)" fill="#A5A7A9"></rect>
                                <rect x="15" y="6.5" width="2" height="3" rx="1" transform="rotate(-180 15 6.5)" fill="#A5A7A9"></rect>
                                </svg>
                            </div>
                        </div>
                    </div>

    </div>

    <div class="content-wrap-elem">

                    <div id="product-protect-properties" class="content-properties content-elem">
                        <div class="content-properties-text">Свойства:</div>
                        <div class="content-properties-elem"><img class="content-properties-img" src="style/img/properties/properties-1.png" alt=""></div>
                        <div class="content-properties-elem"> <img class="content-properties-img" src="style/img/properties/properties-2.png" alt=""></div>
                    </div>
                    <div class="order-product-prev content-elem">
                        <div class="order-product-prev-wrap">
                            <div class="order-product-prev-slider">
                               
                            </div>
                        </div>
                        <div class="order-product-btn-wrap"><a class="order-product-btn" href="">Детали</a><a class="order-product-btn" href="">Сертификаты</a></div>
                    </div>

    </div>
<!--------------------------------------------------------------------------------------------------------------------------->
</div>

<!-------------------------------------------------------END TAMPLATE-------------------------------------------------------->

<script>
var currentProduct;
let product;
let currentOrder;

function redrawOfferPosition(instance, position, offer){
	var cost = $(instance).parent().parent().parent().children()[4];
	$(cost).text( (offer.Price * offer.Amount).toFixed(2) + ' ₽' );
	redrawOrderPosition(instance, position);
}

function redrawOrderPosition(instance, position){
	var total_price = $(instance).parent().parent().parent().parent().parent().children('div.order-list-row.order-list-main-row').children()[2];
	var total_amount = $(instance).parent().parent().parent().parent().parent().children('div.order-list-row.order-list-main-row').children()[3];
	var total_cost = $(instance).parent().parent().parent().parent().parent().children('div.order-list-row.order-list-main-row').children()[4];

	$(total_price).text(position.totalPrice.toFixed(2) + ' ₽');
	$(total_amount).text(position.totalAmount);
	$(total_cost).text(position.totalCost.toFixed(2) + ' ₽');
	
}

function increaseOfferPosition(instance){
	let id = $(instance).parent().parent().parent().find('label').text();
	let position_uid = $(instance).parent().parent().parent().parent().parent().data('uid-position');
	currentOrder.getPositionByUid(position_uid).position.findOfferByID(id).increase();
	redrawOfferPosition(instance, currentOrder.getPositionByUid(position_uid).position, currentOrder.getPositionByUid(position_uid).position.findOfferByID(id) );
}

function decreaseOfferPosition(instance){
	let id = $(instance).parent().parent().parent().find('label').text();
	let position_uid = $(instance).parent().parent().parent().parent().parent().data('uid-position');
	currentOrder.getPositionByUid(position_uid).position.findOfferByID(id).decrease();
	redrawOfferPosition(instance, currentOrder.getPositionByUid(position_uid).position, currentOrder.getPositionByUid(position_uid).position.findOfferByID(id) );
}

function deleteOffersPosition(instance){
	let id = $(instance).parent().find('label').text();
	let position_uid = $(instance).parent().parent().parent().data('uid-position');
	//console.log(id);
	//console.log(position_uid);

	let index = 0;
	let del = new Promise((resolve,reject) => {

		currentOrder.getPositionByUid(position_uid).position.deleteOffer(id);
		resolve(true);
});

	del.then(result => {
		if(result){
			currentOrder.update();
		}})
		.then(result => {
			$(instance).parent().animate({
				opacity: "hide"
				}, 300, "swing",function(){ $(this).detach() });
			//drawOrderTable();
			drawOrderHeader();
		});

}

function deleteOrderPosition(instance){
	let _uid = $(instance).parent().parent().data('uid-position');
	currentOrder.deletePosition(_uid);
	$(instance).parent().parent().detach();
	drawOrderTable();
	drawOrderHeader();
}

function getActiveOffers(instance){
	let offers = new Array();
	let list = $(instance).parent().find('div.table-row.active');

	for(var i = 0; i < list.length; i++)
        {
            offers.push({
                id: Number.parseInt( $(list[i]).find('lable').text() ),
                amount: $(list[i]).find('input').val()
            });
        }

	return offers;
}

/*OFFERS BUTTON */
function setOfferListBtn(instance){
    if( !$(instance).hasClass('active') )
    {
        $(instance).addClass('active');
        $(instance).parent().parent().addClass('active');
        setOrderAmount(1);
    }
    else
    {
        $(instance).removeClass('active');
        $(instance).parent().parent().removeClass('active');
        setOrderAmount(0);
    }
};

/*OFFERS BUTTON UP*/
function increaseCount(instance){
    var value = Number.parseInt( $(instance).parent().find('input').val() ) + 1;
    $(instance).parent().find('input').val(value);
};

/*OFFERS BUTTON DOWN*/
function decreaseCount(instance){
    var value = Number.parseInt( $(instance).parent().find('input').val() ) - 1;
    if(value > 0){
        $(instance).parent().find('input').val(value);
    }else{
        $(instance).parent().find('input').val(0);
    }
};

/*OFFERS BUTTON DELETE*/
function deleteButton(instance){
    $(instance).parent().detach();
}

/*DRAW FUNCTIONS*/
function drawOrderTitle(){
	$('#order-id').text(currentOrder.Id)
}

function drawOrderCount(){
	$('#order-total-amount').text(currentOrder.Count)
}

function drawOrderPrice(){
	$('#order-total-sum').text(currentOrder.Price)
}

function drawOrderValume(){
	$('#order-total-volume').text(currentOrder.Valume)
}

function drawOrderWeight(){
	$('#order-total-weight').text(currentOrder.Weight)
}


function drawOrderHeader(){

		drawOrderTitle();
		drawOrderCount();
		drawOrderPrice();
		drawOrderValume();
		drawOrderWeight();

}

function drawOrderSubItem(instance, data, iterator){

    data.OffersList.forEach(function(item) {

        $( $(instance).children()[iterator+1] ).find('.order-list-sublist').append(
        `
            <div class="order-list-row">
			<div class="order-list-elem"><label style="display: none">${item.Id}</label> </div>
                <div class="order-list-elem">${item.Name}</div>
                <div class="order-list-elem">${Number.parseFloat(item._price).toFixed(2)} ₽</div>
                    <div class="order-list-elem">
                        <div class="order-amount-table-input-wrap">
                        <input class="order-amount-table-input" type="text" value="${item.Amount}">
                            <div class="order-amount-table-input-arrow plus" onclick="increaseCount(this); increaseOfferPosition(this)">
                                <svg class="order-amount-table-input-arrow-img" width="9" height="6" viewBox="0 0 9 6" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M3.71679 0.986589C4.11715 0.482264 4.88285 0.482265 5.28321 0.986589L7.9757 4.37825C8.49596 5.0336 8.02925 6 7.19249 6L1.80751 6C0.970754 6 0.504041 5.0336 1.0243 4.37824L3.71679 0.986589Z" fill="#53565B"></path>
                                </svg>
                            </div>
                            <div class="order-amount-table-input-arrow minus" onclick="decreaseCount(this); decreaseOfferPosition(this)">
                                <svg class="order-amount-table-input-arrow-img" width="9" height="6" viewBox="0 0 9 6" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5.28321 5.01341C4.88285 5.51774 4.11715 5.51774 3.71679 5.01341L1.0243 1.62176C0.504042 0.966397 0.970754 -1.64313e-07 1.80751 -2.37464e-07L7.19249 -7.08234e-07C8.02925 -7.81386e-07 8.49596 0.966397 7.9757 1.62176L5.28321 5.01341Z" fill="#53565B"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                <div class="order-list-elem">${item.Cost} ₽</div>
							<div class="order-list-elem">${item.Complictation}</div>
                <div class="order-list-elem-delete" onclick="deleteOffersPosition(this)">
                    <svg class="order-list-elem-delete-img" width="18" height="19" viewBox="0 0 18 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="0.250031" y="2.07935" width="2.25351" height="22.5351" rx="1.12676" transform="rotate(-45 0.250031 2.07935)" fill="#A5A7A9"></rect>
                        <rect width="2.25351" height="22.5351" rx="1.12676" transform="matrix(-0.707107 -0.707107 -0.707107 0.707107 17.5282 2.07935)" fill="#A5A7A9"></rect>
                    </svg>
                </div>
            </div>
        `);

    });

}

function drawOrderItem(instance, data,iterator){

    $(instance).append(`
	<div class="order-list-item" data-uid-position="${data.uid}">
	<i style="display: none">${data.position.product.Id}</i>
        <div class="order-list-row order-list-main-row">
			<div class="order-list-elem">
            ${iterator+1}
            <div class="table-arrow"></div>
            </div>
            <div class="order-list-elem">${data.position.product.Name}</div>
            <div class="order-list-elem">${Number.parseFloat(data.position.totalPrice).toFixed(2)} ₽</div>
            <div class="order-list-elem">${data.position.totalAmount}</div>
            <div class="order-list-elem">${Number.parseFloat(data.position.totalCost).toFixed(2)} ₽</div>
            <div class="order-list-elem error">- ???</div>
            <div class="order-list-elem-delete" onclick="deleteOrderPosition(this)">
                <svg class="order-list-elem-delete-img" width="18" height="19" viewBox="0 0 18 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="0.250031" y="2.07935" width="2.25351" height="22.5351" rx="1.12676" transform="rotate(-45 0.250031 2.07935)" fill="#A5A7A9"></rect>
                    <rect width="2.25351" height="22.5351" rx="1.12676" transform="matrix(-0.707107 -0.707107 -0.707107 0.707107 17.5282 2.07935)" fill="#A5A7A9"></rect>
                </svg>
            </div>
        </div>
    
        <div class="order-list-sublist">
        </div>
    </div>
    `);

    drawOrderSubItem(instance, data.position, iterator);
}

function drawOrderTable(){

    var instance = $('#order-table');
    instance.children().detach('.order-list-item');

		for(var i = 0; i < currentOrder.Positions.length; i++)
		{
			if(currentOrder.Positions[i].position._itemsOfferList.length > 0)
				drawOrderItem(instance,currentOrder.Positions[i],i);
		}
	
		$('.order-list-main-row').click(function() {
			$(this).parent().toggleClass('active').siblings().removeClass('active');
	
			$('.order-list-sublist').slideUp();
	
			drawProtection(currentOrder.Positions[Number.parseInt( $(this).find('.order-list-elem').first().text()) - 1].position.product);
	
			clearSlider();
			addInSlider(currentOrder.Positions[Number.parseInt( $(this).find('.order-list-elem').first().text()) - 1].position.product.ImagesList);
			createSlider();
	
			if($(this).parent().hasClass('active')) {
				$(this).parent().find('.order-list-sublist').slideDown();
			}
		});


}

function drawProtection(product)
{   
    $('#product-protect-properties').find('.content-properties-elem').detach();

    if(product.ProtectList)
    {
        product.ProtectList.forEach(function(item){
            $('#product-protect-properties').append(`<div class="content-properties-elem"><img class="content-properties-img" src="${item.IMAGE}" title="${item.NAME}"></div>`);
        });
    }
}

function drawProductTitle(product){
    $('#product-name').html(product.Name);
};

function drawProductArticle(product){
    $('#product-article').text(product.Article);
};

function drawProductPrice(product){
    $('#product-price').text(product.Price);
};

function drawProductStatus(product){
    $('#product-status').text(product.Status);
};

function drawProductFounds(product){
    var table_header = $('#table-found').children()[0];
    
    var clear = new Promise( (resolve, reject) => {
        $('#table-found').children().detach();
        resolve(product);
    });

    clear.then( product => {
        $('#table-found').append(table_header);
        if( product.FoundsList ){
		product.FoundsList.forEach(
			function(item)
		{	
            console.log(item);
            if(item.Article)
			    $('#table-found').append("<a class='table-row table-element' onclick='getProductByID(this)'><i style='display: none'>"+item.Id+"</i><div class='table-elem'>"+item.Article+"</div><div class='table-elem'>"+item.Name+"</div></a>");
		}
	    );
        }

    });

};

function drawProductOffers(product){
    
    var clear = new Promise( (resolve, reject) => {
        $('#product-offer-list').children().detach();
        resolve(product);
    });

    clear.then( product => {

if( product.OffersList ){
product.OffersList.forEach(
    function(item)
{	
        $('#product-offer-list').append(`
        
        <div class="table-row">
                        <lable style="display: none">${item.Id}</lable>
                        <div class="table-elem">${item.Characteristic}</div>
                        <div class="table-elem">${item.Residue}</div>
                        <div class="table-elem">${item.Price} ₽</div>

                        <div class="table-elem order-amount-table-value">
                            <div class="order-amount-table-input-wrap">

                                <input class="order-amount-table-input" type="text" value="0">

                                <div class="order-amount-table-input-arrow plus" onclick="increaseCount(this)">
                                    <svg class="order-amount-table-input-arrow-img" width="9" height="6" viewBox="0 0 9 6" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M3.71679 0.986589C4.11715 0.482264 4.88285 0.482265 5.28321 0.986589L7.9757 4.37825C8.49596 5.0336 8.02925 6 7.19249 6L1.80751 6C0.970754 6 0.504041 5.0336 1.0243 4.37824L3.71679 0.986589Z" fill="#53565B"></path>
                                    </svg>
                                </div>

                                <div class="order-amount-table-input-arrow minus" onclick="decreaseCount(this)">
                                    <svg class="order-amount-table-input-arrow-img" width="9" height="6" viewBox="0 0 9 6" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M5.28321 5.01341C4.88285 5.51774 4.11715 5.51774 3.71679 5.01341L1.0243 1.62176C0.504042 0.966397 0.970754 -1.64313e-07 1.80751 -2.37464e-07L7.19249 -7.08234e-07C8.02925 -7.81386e-07 8.49596 0.966397 7.9757 1.62176L5.28321 5.01341Z" fill="#53565B"></path>
                                    </svg>
                                </div>

                            </div>

                            <button class="order-amount-table-btn" onclick="setOfferListBtn(this)">
                                <svg class="order-amount-table-btn-img" width="15" height="10" viewBox="0 0 15 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M13.7952 0.265077C13.4418 -0.0883587 12.8687 -0.0883589 12.5153 0.265076L5.41912 7.36126L1.54498 3.48711C1.19154 3.13368 0.618512 3.13368 0.265076 3.48711C-0.0883589 3.84055 -0.0883588 4.41358 0.265077 4.76702L4.75226 9.2542C4.88175 9.38369 5.04072 9.46574 5.20757 9.50034C5.50792 9.57832 5.84052 9.49967 6.0758 9.26438L13.7952 1.54498C14.1486 1.19154 14.1486 0.618512 13.7952 0.265077Z" fill="#C4C4C4"></path>
                                </svg>
                            </button>
                            
                        </div>
                        <div class="table-elem">${item.Data}</div>
                    </div>
        
        `);
}
);
}

});

};

function OrderAmount()
{   
    var total = 0;

    return function(value) {
        if(value == 1){
            total++;
            $('.order-amount-more-value').html(`(${total})`);
        }

        if(value == 2)
        {
            total = 0;
            $('.order-amount-more-value').html("");
            $('.order-amount-more').parent().find('div.active').find('input').val(0);
            $('.order-amount-more').parent().find('.active').removeClass('active');
        }

        if(value == 0)
        {
            total--;
            if(total <= 0)
            {
                total = 0;
                $('.order-amount-more-value').html("");
            }
            else
            {
                $('.order-amount-more-value').html(`(${total})`);
            }
        }

    }
}
var setOrderAmount = OrderAmount();

function draw(product){
    drawProductTitle(product);
    drawProductArticle(product);
    drawProductPrice(product);
    drawProductStatus(product);
    drawProductFounds(product);
    drawProductOffers(product);
};

function sendData(query, option = 9)
{

	//switchProgressBar(true);

		//clearTemplate();

		BX.ajax.get(
		'/test/product-page/ajax.php',
		'OPTION=' + option + '&QUERY=' + query,
		function(e)
			{	
				product = new Product(JSON.parse(e));
				currentProduct = JSON.parse(e);

				if(currentProduct.PRODUCT.NAME == null)
                {
                    //$('#lk-search-nomatches').css('display', 'block');
                    //switchProgressBar(false);

                }else{
                    //console.log( JSON.parse(e) );
					draw(product);

                    //mountHistory({'ARTICLE': $('#product-article')[0].innerHTML,
                    //    'NAME': $('#product-name')[0].innerHTML});

                    //addInSlider( JSON.parse(e) );
                    //switchProgressBar(false);
                }
			}
		);

};

function doSearch(instance, boundInstance){

	var option = boundInstance.val();
	var query = $(instance).find('option:selected').text();;

	switch( Number.parseInt(option) )
	{
		case 0: searchByArticle(query);
		break;
		case 1: serachByTitle(query);
		break;
		default:
	}
};

function searchByArticle(query){
	var option = 0;
	sendData(query,option);
};

function serachByTitle(query){
	var option = 1;
	sendData(query,option);
};

function drawHistoryList(){
		$('.article-history-list').children().detach();
		var list = Array.from(Object.values(JSON.parse(window.localStorage.getItem('history'))));
		list.reverse();

	switch( Number.parseInt($('#query-select-option').val()) ){
		case 0:
		for(var i = 0; i < list.length; i++)
		{
			$('.article-history-list').append("<option value='"+ i +"'>"+ list[i].ARTICLE + "</option>");
		}
		break;

		case 1:
		for(var i = 0; i < list.length; i++)
		{
			$('.article-history-list').append("<option value='"+ i +"'>"+ list[i].NAME + "</option>");
		}
		break;

		default:
	}
};

function selectHistorySearchType(data){	
	
    console.log($(data).val());
	console.log($(data));

		switch( Number.parseInt($(data).val()) )
	{
		case 0: 

		$('.article-history-list').children().detach();
		var list = Array.from(Object.values(JSON.parse(window.localStorage.getItem('history'))));
		list.reverse();
		for(var i = 0; i < list.length; i++)
		{
			$('.article-history-list').append("<option value='"+ i +"'>"+ list[i].ARTICLE + "</option>");
		}

		break;
	
		case 1:

		$('.article-history-list').children().detach();
		var list = Array.from(Object.values(JSON.parse(window.localStorage.getItem('history'))));
		list.reverse();
		for(var i = 0; i < list.length; i++)
		{
			$('.article-history-list').append("<option value='"+ i +"'>"+ list[i].NAME + "</option>");
		}

		break;
	
		default:
	}
};

	/*--SLIDER--*/

function clearSlider()
{
    $('.order-product-prev-slider').slick('unslick');
	/*
	let destroy = new Promise( (resolve,reject) => {
		destroySlider();
	})

		destroy.then( (result) => { createSlider(); } );
*/};

function addInSlider(data){

    console.log(data);
    $('.order-product-prev-slider').find('.order-product-prev-slider-elem').detach();

	data.forEach(
			function(item)
		{
			$('.order-product-prev-slider').append(`<div class="order-product-prev-slider-elem">
                                    <div class="order-product-prev-slider-img-box"><img class="order-product-prev-slider-img" src="${item}" alt=""></div>
                                </div>`);
		}
	);

};

function createSlider(){
    $('.order-product-prev-slider').slick({
		slidesToShow: 3,
		slidesToScroll: 1,
		arrows: true,
		dots: false,
		responsive: [
			{
				breakpoint: 420,
				settings: {
					slidesToShow: 2,
					vertical: false,
					verticalSwiping: false,
				}
			},
		],
	});

}


$( document ).ready(function() {

			//add-order-position-btn
$('#add-order-position-btn').click(function(){
	let active_position = getActiveOffers(this);
	let position = new ItemPosition();
	position._product = product;

	if(active_position.length > 0){
		let tmp = new Promise( (resolve, reject) => {

			active_position.forEach(function(item){
	
					position.addOffer(item);
			});
	
			resolve( Object.assign(position) );
		});
	
		tmp
		.then( result => {
			console.log(result);
			currentOrder.addPosition(result);
			setOrderAmount(2);
			drawOrderTable();
			drawOrderHeader();
		});
			}else{
		return null;
			}

});


currentOrder = new Order(new Date().getMilliseconds());

drawHistoryList();
searchByArticle('КОС623');
});

</script>
<?php endif;?>
<?php require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');?>