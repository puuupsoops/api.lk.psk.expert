<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Личный кабинет");

//подключение библиотек 
$arJsConfig = array( 
	'Calculator' => [ 
        'js' => '/product-page/Calculator.js', 
    ],
	'Product' => [
		'js' => '/product-page/Product.js',
	],
	'Order' => [
		'js' => '/product-page/Order.js',
	],
	'ItemPosition' => [
		'js' => '/product-page/ItemPosition.js',
	],
	'ItemOffer' => [
		'js' => '/product-page/ItemOffer.js',
	],  
); 

foreach ($arJsConfig as $ext => $arExt) { 
    \CJSCore::RegisterExt($ext, $arExt); 
}

CUtil::InitJSCore(['Calculator','Product','Order','ItemPosition','ItemOffer']);
?>
<style>
.company-search-btn > i {
		z-index: 2;
}

.lds-ripple {
  display: block;
	position: relative;
  width: 80px;
  height: 80px;
  margin-left: auto;
  margin-right: auto;
}
.lds-ripple div {
  position: absolute;
  border: 4px solid #fff;
  opacity: 1;
  border-radius: 50%;
  animation: lds-ripple 1s cubic-bezier(0, 0.2, 0.8, 1) infinite;
}
.lds-ripple div:nth-child(2) {
  animation-delay: -0.5s;
}
@keyframes lds-ripple {
  0% {
    top: 36px;
    left: 36px;
    width: 0;
    height: 0;
    opacity: 1;
  }
  100% {
    top: 0px;
    left: 0px;
    width: 72px;
    height: 72px;
    opacity: 0;
  }
}

	.sidebar-search-input-ex{    
	padding: 15px 20px;
    width: 100%;
    font-size: 16px;
    color: #ffffff;
    border: 0;
    border-radius: 40px;
    background-color: #292C32;
	    min-width: 350px;
}

	.query-select-option-init{    position: relative;
    height: 51px;
    background-color: #292C32;
    border: 0;
    border-radius: 40px;
    outline: none;
    -webkit-transition: 0.35s;
    -moz-transition: 0.35s;
    -ms-transition: 0.35s;
    -o-transition: 0.35s;
    transition: 0.35s;}

.gg-search {
    box-sizing: border-box;
    position: relative;
    display: block;
    transform: scale(var(--ggs,1));
    width: 16px;
    height: 16px;
    border: 2px solid;
    border-radius: 100%;
    margin-left: -4px;
    margin-top: -4px
	background: white;

}
.gg-search::after {
    content: "";
    display: block;
    box-sizing: border-box;
    position: absolute;
    border-radius: 3px;
    width: 2px;
    height: 8px;
    background: white;
    transform: rotate(-45deg);
    top: 10px;
    left: 12px
}

#table-found-hide-btn{
	padding: 10px;
	text-align: end;
	width: 70%;
}

#table-found-head-wrap{
	display: flex;
	align-items: baseline;
	align-content: space-between;
}

#table-order-found-hide-btn{
	padding: 10px;
	text-align: end;
	width: 70%;
}

#table-order-found-head-wrap{
	display: flex;
	align-items: baseline;
	align-content: space-between;
}

</style>

<?//if($USER->IsAuthorized()):?>
            <nav class="nav">
                <ul id="sub-nav-bar" class="nav-list">
                    <li><a class="nav-link active" href="#" onclick="hideOrderPage(true); hideProductPage(false);">Поиск товара</a></li>
                    <li id="to-order"><a class="nav-link" href="#" onclick="hideProductPage(true); hideOrderPage(false);">К заказу</a></li>
                </ul>
            </nav>

<?php $APPLICATION->IncludeFile($APPLICATION->GetCurDir() . 'product.php', [],["MODE"=>"html"]); ?>
<?php $APPLICATION->IncludeFile($APPLICATION->GetCurDir() . 'order.php', [],["MODE"=>"html"]); ?>

<?//endif;?>
<script>

var currentProduct = null;
let product = null;
let currentOrder = null;
let searchResult = null;

function recalculate(instance){

	var instance = $(instance);
	if(!setCount(instance))
	{
		return false;
	}
	else
	{

		var price = $('#price-ex-charge').text();

		if(price != '-' && price != null && price != 'NaN')
		{
			$('#product-sum')[0].innerText =  Number.parseFloat( Number.parseFloat( price ) * Number.parseFloat( instance.text() ) ).toFixed(2);
		}
		else
		{ 
			$('#product-sum')[0].innerText =  Number.parseFloat( currentProduct.PRODUCT.PRICE * Number.parseFloat( instance.text() ) ).toFixed(2); 
		}
		$('#product-weight')[0].innerText = Number.parseFloat( ( Number.parseFloat( instance.text() ) * currentProduct.PRODUCT.WEIGHT )).toFixed(3);
		$('#product-valume')[0].innerText = Number.parseFloat( ( Number.parseFloat( instance.text() ) * currentProduct.PRODUCT.VALUME )).toFixed(3);
	}
};

function getExChargePercent(){
	var instance = $('#price-ex-charge');
	instance.text( productCalculator.getExtraChargeInPercent( currentProduct.PRODUCT.PRICE, $('#product-ex-charge-count-btn').text() ) );

	$('#product-sum').text( (Number.parseFloat(instance.text()) * Number.parseFloat($('#product-amount-btn').text())).toFixed(2) );
};

function getExChargeValue(){
	var instance = $('#price-ex-charge');
	instance.text( productCalculator.getExtraChargeInValue (currentProduct.PRODUCT.PRICE, $('#product-ex-charge-count-btn').text()) );

	$('#product-sum').text( (Number.parseFloat(instance.text()) * Number.parseFloat($('#product-amount-btn').text())).toFixed(2) );
};

function setCount(instance){

		var self = $(instance);
		var value = prompt('Укажите значение','1');
	
		if(value == '' || value == null)
		{
			return false; 
		}
		else
		{
			return self.text(value);
		}

};

	function selectHistorySearchType(data)
{	
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

	/*function redrawOptionList(instance,data)
{

};*/

	function increesValue(data)
{	
	var value;

	return value = $(data)[0].innerText = Number.parseInt($(data)[0].innerText) + 1;

};


function switchProgressBar(state){
	if(state){
		$("#lk-loader").css("display", "block");
	}
	else
	{
		$("#lk-loader").css("display", "none");
	}
};

	/*function calculateExCharge(amount,percent) 
{
	return (amount * (1 + (percent/100))).toFixed(2);
};*/

function createSlider()
{
		$('.product-slider').slick({
		slidesToShow: 1,
		slidesToScroll: 1,
		arrows: true,
		dots: false,
		fade: true,
		asNavFor: '.product-slider-nav'
	});

	$('.product-slider-nav').slick({
		slidesToShow: 1,
		slidesToScroll: 1,
		asNavFor: '.product-slider',
		dots: false,
		arrows: false,
		centerMode: false,
		focusOnSelect: true,
		vertical: true,
		verticalSwiping: true,
		responsive: [
			{
				breakpoint: 640,
				settings: {
					slidesToShow: 1,
					vertical: false,
					verticalSwiping: false,
				}
			},
			{
				breakpoint: 380,
				settings: {
					slidesToShow: 1,
					vertical: false,
					verticalSwiping: false,
				}
			},
		],
	});

	if($('.sidebar-menu-btn').hasClass('active')) {
		$('.product-slider-nav').slick({
			responsive: [
				{
					breakpoint: 1300,
					settings: {
						vertical: false,
						verticalSwiping: false,
					}
				},
			],
		});
	}
};

	function destroySlider()
{
	$('.product-slider').slick('unslick');
	$('.product-slider-nav').slick('unslick');
};

function clearSlider()
{
	var slide_count =  $('.product-slider').slick('getSlick').slideCount;
	if(slide_count > 0)
	{
		for(var i = slide_count - 1; i >= 0 ; i-- )
			{
				$('.product-slider').slick('slickRemove',i);
				$('.product-slider-nav').slick('slickRemove',i);
			}
	}
	/*
	let destroy = new Promise( (resolve,reject) => {
		destroySlider();
	})

		destroy.then( (result) => { createSlider(); } );
*/};

function addInSlider(data){


			data.IMAGES.forEach(
			function(item)
		{
			$('.product-slider').slick('slickAdd',"<div class='product-slider-elem'><img class='product-slider-img' src='"+ item +"' alt=''></div>");
			$('.product-slider-nav').slick('slickAdd',"<div class='product-slider-nav-elem'><img class='product-slider-nav-img' src='"+ item +"' alt=''></div>");

			$('.product-slider').slick('refresh');
			$('.product-slider-nav').slick('refresh');
		}
	);

};

function clearTemplate(){
	var table_head_offers = $('#table-offers').children()[0];
	var table_head_found = $('#table-found').children()[0];

	clearSlider();

	$('#product-protect-prop').children('.content-properties-elem').detach();
	$('#table-offers').children().detach();
	$('#table-found').children().detach();
	$('.product-slider').children().detach();
	$('.product-slider-nav').children().detach();

	$('#product-name')[0].innerHTML= '';
	$('#product-article')[0].innerHTML = '';
	$('#product-characteristics')[0].innerHTML = '';
	$('#price-ex-charge')[0].innerHTML = '-';
	$('#product-detatil-text')[0].innerHTML = '';
	$('#product-price')[0].innerHTML = '';
	$('#product-weight')[0].innerHTML = '';
	$('#product-valume')[0].innerHTML =  '';
	$('#product-sum')[0].innerHTML =  '';
	$('#product-amount-btn')[0].innerText = 1;
	$('#product-status')[0].innerHTML = '';
	$('#table-offers').append(table_head_offers);
	$('#table-found').append(table_head_found);
};

function drawData(data){
console.log('drawData', data);
	data.PRODUCT.CHARACTERISTICS.forEach(
		function (item){
			if(item.VALUE != '')
			$('#product-characteristics')[0].innerHTML += "<div class='table-row'><div class='table-elem'>" + item.NAME +"</div><div class='table-elem'>" + item.VALUE + "</div></div>";
		}
);

	data.OFFERS.forEach(
		function(item)
	{
		$('#table-offers').append("<div class='table-row'><div class='table-elem'>" + item.CHARACTERISTIC + "</div><div class='table-elem'>" + item.RESIDUE + "</div><div class='table-elem'>" + item.PRICE + "</div><div class='table-elem'>" + item.PPDATA + "</div></div>");
	}
);

if(data.FOUND){
		data.FOUND.forEach(
			function(item)
		{	if(item.ARTICLE)
			$('#table-found').append("<a class='table-row table-element' onclick='getProductByID(this)'><i style='display: none'>"+item.ID+"</i><div class='table-elem'>"+item.ARTICLE+"</div><div class='table-elem'>"+item.NAME+"</div></a>");
		}
	);
}
else
{
	if(searchResult && searchResult.length > 0){
		searchResult.forEach(
				function(item)
			{	if(item.Article)
				$('#table-found').append("<a class='table-row table-element' onclick='getProductByID(this)'><i style='display: none'>"+item.Id+"</i><div class='table-elem'>"+item.Article+"</div><div class='table-elem'>"+item.Name+"</div></a>");
			}
		);
	}
}

if(data.PROTECT){
		data.PROTECT.forEach(
			function(item)
		{
			$('#product-protect-prop').append("<div class='content-properties-elem'><img class='content-properties-img' src='"+item.IMAGE+"' title='"+item.NAME+"'></div>");
		}
	);
}

	$('#product-name')[0].innerHTML= data.PRODUCT.NAME ? data.PRODUCT.NAME: '';
	$('#product-article')[0].innerHTML = data.PRODUCT.ARTICLE ? " " + data.PRODUCT.ARTICLE: '';
	$('#product-price')[0].innerHTML = data.PRODUCT.PRICE ? data.PRODUCT.PRICE : '';
	$('#product-sum')[0].innerHTML =  data.PRODUCT.PRICE ? data.PRODUCT.PRICE : '';
	$('#product-weight')[0].innerHTML = data.PRODUCT.WEIGHT ? data.PRODUCT.WEIGHT: '';
	$('#product-valume')[0].innerHTML =  data.PRODUCT.VALUME ? data.PRODUCT.VALUME : '';
	$('#product-detatil-text')[0].innerHTML = data.PRODUCT.DETAIL_TEXT ? data.PRODUCT.DETAIL_TEXT: '';
	$('#product-status')[0].innerHTML = data.PRODUCT.STATUS ? data.PRODUCT.STATUS: '';

	$('#product-heading-wrap').css('display', 'flex');
	$('#product-content-wrap').css('display', 'flex');

};

function drawListOrder(product){
	    var table_header = `<div class="table-row table-heading"><div class="table-elem">Артикул</div><div class="table-elem">Наименование</div></div>`
		var list = product.FoundsList;
		console.log('drawListOrder-list',list)
		var clear = new Promise( (resolve, reject) => {
        $('#order-table-found').children().detach();
        resolve(list);
    });

    clear.then( list => {
        $('#order-table-found').append(table_header);

			for(var i = 0; i < list.length; i++)
			{
				if(list[i].Article != null)
					$('#order-table-found').append(`<a class='table-row table-element' data-product-id='${list[i].Id}' onclick="showProductOrder(this)"><div class='table-elem'>${list[i].Article}</div><div class='table-elem'>${list[i].Name}</div></a>`);
			}
    });
}

function showProductOrder(instance){
	let query = $(instance).data('product-id');

		BX.ajax.get(
		'https://psk.expert/test/product-page/ajax.php',
		'OPTION=9' + '&QUERY=' + query,
		function(e)
			{
				product = new Product( JSON.parse(e) );
				drawOrderProduct(product);
			}
		);

}

//Deprecated, do not use
function drawHistoryListOrder(){
	    var table_header = $('#order-table-found').children()[0];
    	var list = Array.from(Object.values(JSON.parse(window.localStorage.getItem('history'))));
		list.reverse();

    var clear = new Promise( (resolve, reject) => {
        $('#order-table-found').children().detach();
        resolve(list);
    });

    clear.then( list => {
        $('#order-table-found').append(table_header);

			for(var i = 0; i < list.length; i++)
			{
				$('#order-table-found').append(`<a class='table-row table-element' onclick='searchByArticle($(this).children().first().text()); setActiveSubNavLink(0);'><div class='table-elem'>${list[i].ARTICLE}</div><div class='table-elem'>${list[i].NAME}</div></a>`);
			}
    });

}

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

function makeHistory(data){

	 if(JSON.parse(window.localStorage.getItem('history')) != null)
		{	
			var history_list = JSON.parse(window.localStorage.getItem('history'));

			if(history_list.length > 4)
			{
				var tmp = Array.from(Object.values(history_list));
				var tmp2 = new Array();
				for(var i = 0; i < 4; i++)
				{
					tmp2[i] = tmp[i+1];
				}
				history_list = tmp2;
			}
			history_list[history_list.length] = data;
			console.log(history_list);
			return history_list;
		}
		else
		{
			console.log(new Array(data));
			return new Array(data);
		}


};

async function mountHistory(data){
		await window.localStorage.setItem('history', JSON.stringify(makeHistory(data)));
		await drawHistoryList();
};

function sendData(query, option = 9)
{
	console.log('sendData', query);
	$('#product-slider-buttons-order').css('display', 'none');
	hideOrderPage(true);
	switchProgressBar(true);

		clearTemplate();

		BX.ajax.get(
		'https://psk.expert/test/product-page/ajax.php',
		'OPTION=' + option + '&QUERY=' + query,
		function(e)
			{	console.log(JSON.parse(e));
				product = new Product(JSON.parse(e));
				currentProduct = JSON.parse(e);

				if(product.FoundsList && product.FoundsList.length > 0)
					searchResult = product.FoundsList.slice();

				if(currentProduct.PRODUCT.NAME == null)
{	
	$('#lk-search-nomatches').css('display', 'block');
	switchProgressBar(false);

}else{

				if(product.OffersList.length > 0)
					$('#product-slider-buttons-order').css('display', 'block');

				drawData( JSON.parse(e) );

				mountHistory({'ARTICLE': $('#product-article')[0].innerHTML,
					'NAME': $('#product-name')[0].innerHTML});

				addInSlider( JSON.parse(e) );
				switchProgressBar(false);
}
			}
		);

};

function getData(data)
{

	//var option; = $(data).find('select').val();
	var option = 2;
var query = $(data).find('input').val();


	if(query == '')
{
		return null;
}else
{
	$('#product-heading-wrap').css('display', 'none');
	$('#product-content-wrap').css('display', 'none');
	$('#lk-search-nomatches').css('display', 'none');
	sendData(query,option);
}

};

function getProductByID(data)
{

	var id = $(data).find('i')[0].innerText;

if(id == '' || id == null){
		return null;
}else
{
	$('#product-heading-wrap').css('display', 'none');
	$('#product-content-wrap').css('display', 'none');
	$('#lk-search-nomatches').css('display', 'none');
	sendData(id);
}

};

	function doSearch(instance, boundInstance)
{

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

function hideOrderPage(state){
	if(state)
	{
		$('#order-header-1').animate({
				opacity: "hide"
				}, 300, "swing",function(){ $(this).css('display', 'none');});
		$('#order-header-2').animate({
				opacity: "hide"
				}, 300, "swing",function(){ $(this).css('display', 'none');});
		$('#order-header-3').animate({
				opacity: "hide"
				}, 300, "swing",function(){ $(this).css('display', 'none');});
		$('#order-header-4').animate({
				opacity: "hide"
				}, 300, "swing",function(){ $(this).css('display', 'none');});
	}
	else
	{	setActiveSubNavLink(1);
		$('#order-header-1').animate({
				opacity: "show"
				}, 300, "swing",function(){ $(this).css('display', 'flex');});
		$('#order-header-2').animate({
				opacity: "show"
				}, 300, "swing",function(){ $(this).css('display', 'flex');});
		$('#order-header-3').animate({
				opacity: "show"
				}, 300, "swing",function(){ $(this).css('display', 'flex');});
		$('#order-header-4').animate({
				opacity: "show"
				}, 300, "swing",function(){ $(this).css('display', 'flex');});
	}
};

function hideProductPage(state){
	if(state)
	{
		$('#product-heading-wrap-init').animate({
				opacity: "hide"
				}, 300, "swing",function(){ $(this).css('display', 'none');});
		$('#product-heading-wrap').animate({
				opacity: "hide"
				}, 300, "swing",function(){ $(this).css('display', 'none');});
		$('#product-content-wrap').animate({
				opacity: "hide"
				}, 300, "swing",function(){ $(this).css('display', 'none');});
	}
	else
	{	setActiveSubNavLink(0);
		$('#product-heading-wrap-init').animate({
				opacity: "show"
				}, 300, "swing",function(){ $(this).css('display', 'flex');});

		if(currentProduct != null)
			$('#product-heading-wrap').animate({
				opacity: "show"
				}, 300, "swing",function(){ $(this).css('display', 'flex');});
		if(currentProduct != null)
			$('#product-content-wrap').animate({
				opacity: "show"
				}, 300, "swing",function(){ $(this).css('display', 'flex');});
	}
}

	//-------------------------------ORDER-FUNCTION------------------------------ORDER-FUNCTION-------------------ORDER-FUNCTION-----------------ORDER-FUNCTION-----------\\

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
	drawOrderHeader();
	
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
	$('#order-total-sum').text(currentOrder.Price.toFixed(2))
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
                            <div class="order-amount-table-input-arrow plus" onclick="increaseCount(this); increaseOfferPosition(this);">
                                <svg class="order-amount-table-input-arrow-img" width="9" height="6" viewBox="0 0 9 6" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M3.71679 0.986589C4.11715 0.482264 4.88285 0.482265 5.28321 0.986589L7.9757 4.37825C8.49596 5.0336 8.02925 6 7.19249 6L1.80751 6C0.970754 6 0.504041 5.0336 1.0243 4.37824L3.71679 0.986589Z" fill="#53565B"></path>
                                </svg>
                            </div>
                            <div class="order-amount-table-input-arrow minus" onclick="decreaseCount(this); decreaseOfferPosition(this);">
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

};

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
};

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
	
			clearOrderSlider();
			addInOrderSlider(currentOrder.Positions[Number.parseInt( $(this).find('.order-list-elem').first().text()) - 1].position.product.ImagesList);
			createOrderSlider();

			if($(this).parent().hasClass('active')) {
				$(this).parent().find('.order-list-sublist').slideDown();
			}
		});


};

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
    $('#order-lable-product-name').html(product.Name);
};

function drawProductArticle(product){
    $('#order-lable-product-article').text(product.Article);
};

function drawProductPrice(product){
    $('#order-lable-product-price').text(product.Price);
};

function drawProductStatus(product){
    $('#order-lable-product-status').text(product.Status);
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

if( product.OffersList.length > 0 ){
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
else
{	
        $('#product-offer-list').append(`
        
        <div class="table-row">
                        <lable style="display: none">${product.Id}</lable>
                        <div class="table-elem">${product.Article}</div>
                        <div class="table-elem">-</div>
                        <div class="table-elem">${product.Price} ₽</div>

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
                        <div class="table-elem">-</div>
                    </div>
        
        `);

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

function drawOrderProduct(product){
    drawProductTitle(product);
    drawProductArticle(product);
    drawProductPrice(product);
    drawProductStatus(product);
    drawProductFounds(product);
    drawProductOffers(product);
};

	/*--SLIDER--*/

function clearOrderSlider()
{
    $('.order-product-prev-slider').slick('unslick');
	/*
	let destroy = new Promise( (resolve,reject) => {
		destroySlider();
	})

		destroy.then( (result) => { createSlider(); } );
*/};

function addInOrderSlider(data){

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

function createOrderSlider(){
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

function LoaderInstance(){
	var instance = `<div class="claim-success" style="height: 100%">
	<div class="claim-success-wrap" style="margin-left: 0">
		<div class="claim-success-text"><div class="lds-ripple" style=""><div></div><div></div></div></div>
	</div>
	</div>`;
	return instance;
}

function NoMatchesFoundInstance(){
	var instance = `<div class="claim-success" style="height: 100%">
	<div class="claim-success-wrap" style="margin-left: 0">
		<div class="claim-success-text">Совпадений не найдено.</div>
	</div>
	</div>`;
	return instance;
}

//-------------------------------ORDER-FUNCTION-END------------------------------------------
function setActiveSubNavLink(index){
	let nav = $('#sub-nav-bar');

	let unactive = new Promise((resolve, reject) => {
		$('#sub-nav-bar').children().each( (index,item) => { $(item).removeClass('active') } );
		resolve(index);
	}); 

			unactive.then(result => { $( $('#sub-nav-bar').children()[result] ).addClass('active'); });
}

$( document ).ready(function() {

	$('#to-order').css('display','none');

	hideOrderPage(true);
	drawHistoryList();

	//order-search-form Поиск на странице с заказом
	$('#order-search-form').find('button').first().click(function(e){
		e.preventDefault();
		var query = $(this).parent().find('input').val();

		if(query == '')
		{
			return null;
		}
		else
		{
			$(this).attr('disabled','true');
		}	

		$('#order-table-found').find('a').fadeOut(300, function() { 
			$(this).detach(); 
		});
		
		$('#order-table-found').children().first().fadeOut(300, function() {
			
			$('#order-table-found').empty().prepend( LoaderInstance() );
		
			BX.ajax.get(
					'https://psk.expert/test/product-page/ajax.php',
					'OPTION=' + 2 + '&QUERY=' + query,
					function(e)
						{	console.log('order-search-product-result',JSON.parse(e));
							product = new Product(JSON.parse(e));
							currentProduct = JSON.parse(e);

							if(product.Name == null)
							{
								$('#order-table-found').find('.claim-success').animate({
											opacity: "hide"
								}, 300, "swing",function(){ 
									$(this).detach(); 

									$('#order-table-found').delay(300).append( NoMatchesFoundInstance() );

								});
								
							}
							else
							{
								$('#order-table-found').find('.claim-success').animate({
											opacity: "hide"
								}, 300, "swing",function(){ $(this).detach(); drawListOrder(product);  }); 
								
								mountHistory({'ARTICLE': $('#product-article')[0].innerHTML,
											'NAME': $('#product-name')[0].innerHTML});
							}

							$('#order-search-form').find('button').first().removeAttr('disabled');
						}
			);

		});
	});

	//product-slider-buttons Заказать
	$('#product-slider-buttons-order').click(function(){
		if($('#to-order').css('display') == 'none')
			$('#to-order').animate({
					opacity: "show"
					}, 300, "swing",function(){ $(this).css('display', 'flex'); setActiveSubNavLink(1); });

		drawOrderHeader();
		drawOrderProduct(product);
		drawListOrder(product);
		hideProductPage(true); 
		hideOrderPage(false);
	});

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

	//product-search-form
	$('#product-heading-wrap-init').find('.company-search-input-wrap').css('max-width','1600px');
	$('#product-heading-wrap-init').children().first().css('max-width','1900px');

	//table-found-hide-btn
	$('#table-found-hide-btn').click(function(){
		if( !$(this).hasClass('active') )
		{
			$(this).parent().parent().children().last().animate({
				opacity: "show"
				}, 300, "swing",function(){ $(this).css('display', 'block');});
		}
		else
		{
			$(this).parent().parent().children().last().animate({
				opacity: "hide"
				}, 300, "swing",function(){ $(this).css('display', 'none');});

		}

	});

	//table-order-found-hide-btn
	$('#table-order-found-hide-btn').click(function(){
		if( !$(this).hasClass('active') )
		{
			$(this).parent().parent().children().last().animate({
				opacity: "show"
				}, 300, "swing",function(){ $(this).css('display', 'block');});
		}
		else
		{
			$(this).parent().parent().children().last().animate({
				opacity: "hide"
				}, 300, "swing",function(){ $(this).css('display', 'none');});

		}

	});

	//sub-nav-bar
	setActiveSubNavLink(0);

	currentOrder = new Order(new Date().getMilliseconds());

});

</script>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>