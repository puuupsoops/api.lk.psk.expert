<!---*ЗАГЛУШКА ПРЕДВАРИТЕЛЬНОГО ПОИСКА*--->
<div id="product-heading-wrap-init" class="company-calendar-wrap">
	<div class="company-calendar-box content-elem">
		<form class="company-search-wrap" onsubmit="getData(this); return false;">

			<!--<select id='query-select-option-init' class="custom-select">
					<option value="0" selected>Артикул</option>
					<option value="1" >Наименование</option>
					<option value="2" >По совпадению</option>
			</select>-->

			<div class="company-search-input-wrap">
				<input id='query-select-query-init' class="company-search-input" type="text" placeholder="Поиск" autocomplete="off"><img class="company-search-input-clear" src="style/img/icon/cross.svg" alt="">
			</div>
			<button class="company-search-btn gradient-btn"><div class="gradient-btn-text">Поиск</div></button>
		</form>
	</div>
</div>
<!---*КОНЕЦ_КОДА: ЗАГЛУШКА ПРЕДВАРИТЕЛЬНОГО ПОИСКА*--->

<!---*ЛОАДЕР*--->
<div class="claim-success" id="lk-loader" style="display:none">
	<div class="claim-success-wrap" style="padding-bottom: 30%">
		<div class="claim-success-text"><div class="lds-ripple" style=""><div></div><div></div></div></div>
	</div>
</div>
<!---*КОНЕЦ:ЛОАДЕР*--->

<div class="claim-success" id="lk-search-nomatches" style="display:none">
	<div class="claim-success-wrap" style="padding-bottom: 30%">
		<div class="claim-success-text">Совпадений не найдено.</div>
	</div>
</div>

	<div id="product-heading-wrap" class="content-heading-wrap" style="display:none">

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
                <path class="fill" fill-rule="evenodd" clip-rule="evenodd" d="M3 2H18.75C19.3023 2 19.75 2.44772 19.75 3V15H21.75V3C21.75 1.34315 20.4069 0 18.75 0H3C1.34315 0 0 1.34315 0 3V21.8571C0 23.514 1.34315 24.8571 3 24.8571H16.4846C16.1942 24.6822 16 24.3638 16 24V22.8571H3C2.44772 22.8571 2 22.4094 2 21.8571V3C2 2.44772 2.44772 2 3 2Z" fill="#A5A7A9"></path>
                <path class="fill stroke" d="M27.614 22.1745C26.585 23.406 25.1063 24.6558 23.394 26.1031L23.3937 26.1033C22.8085 26.5978 22.1454 27.1584 21.457 27.7553M27.614 22.1745L27.7675 22.3028M27.614 22.1745L27.7675 22.3028C27.7675 22.3028 27.7675 22.3028 27.7675 22.3028M27.614 22.1745C28.7643 20.798 29.3 19.4928 29.3 18.0669C29.3 16.6816 28.8211 15.4035 27.9515 14.4679C27.0715 13.5213 25.8639 13 24.551 13C23.5697 13 22.671 13.3077 21.88 13.9144L20.8 14.703M27.7675 22.3028C26.7274 23.5476 25.2378 24.8066 23.5349 26.2458L23.5228 26.2561L23.5228 26.2561C22.9375 26.7506 22.2753 27.3104 21.5881 27.9064L21.457 27.7553M27.7675 22.3028C28.9399 20.8998 29.5 19.5508 29.5 18.0669C29.5 16.6357 29.0051 15.3076 28.098 14.3318L28.098 14.3318C27.1795 13.3437 25.9184 12.8 24.551 12.8C23.525 12.8 22.5836 13.1227 21.7583 13.7557C21.4091 14.0235 21.0888 14.3408 20.8 14.703M21.457 27.7553L21.5881 27.9064M21.457 27.7553C21.2753 27.913 21.0419 28 20.8 28C20.558 28 20.3246 27.913 20.1428 27.7551C19.456 27.1595 18.7939 26.5998 18.2098 26.1062L18.2068 26.1036C16.4941 24.6561 15.0151 23.406 13.9861 22.1747C12.8358 20.798 12.3 19.4928 12.3 18.0669C12.3 16.6816 12.779 15.4035 13.6486 14.4679C14.5286 13.5213 15.7361 13 17.0491 13C18.0304 13 18.9291 13.3077 19.7201 13.9144C20.0677 14.1811 20.387 14.4998 20.6742 14.8663L21.5881 27.9064M21.5881 27.9064C21.3698 28.0958 21.0898 28.2 20.8 28.2C20.5101 28.2 20.2301 28.0958 20.0117 27.9061C19.3261 27.3115 18.6649 26.7527 18.0807 26.2589L18.0791 26.2576L18.0791 26.2576L18.0777 26.2563L18.0761 26.255L18.0669 26.2472C16.3632 24.8074 14.873 23.5479 13.8326 22.3029L13.8326 22.3029C12.6602 20.8998 12.1 19.5508 12.1 18.0669C12.1 16.6357 12.595 15.3076 13.5021 14.3318L13.5021 14.3318C14.4206 13.3437 15.6815 12.8 17.0491 12.8C18.0752 12.8 19.0165 13.1227 19.8418 13.7557L19.8418 13.7558C20.1908 14.0235 20.5113 14.3407 20.8 14.703M21.5881 27.9064L20.8 14.703M14.9062 21.4162L14.9062 21.4162C15.8663 22.5653 17.299 23.777 18.9807 25.1985C18.9811 25.1987 18.9814 25.199 18.9817 25.1992L18.9831 25.2004L18.9848 25.2018C19.5341 25.6661 20.153 26.1893 20.7988 26.7476C21.4482 26.1882 22.0682 25.6641 22.6187 25.199M14.9062 21.4162L14.5271 15.2735C15.1778 14.5736 16.072 14.1876 17.0491 14.1876C17.7595 14.1876 18.4107 14.4107 18.9892 14.8543C19.508 15.2523 19.873 15.7588 20.0887 16.1174L20.0887 16.1175C20.2397 16.3685 20.5068 16.5186 20.8 16.5186C21.0932 16.5186 21.3602 16.3685 21.5113 16.1175C21.7271 15.7588 22.0921 15.2523 22.6108 14.8543C23.1892 14.4107 23.8404 14.1876 24.551 14.1876C25.528 14.1876 26.4223 14.5737 27.0729 15.2735C27.7343 15.9851 28.1043 16.9751 28.1043 18.0669C28.1043 19.2064 27.6808 20.2351 26.6939 21.4162C25.7336 22.5655 24.3008 23.7772 22.6187 25.199M14.9062 21.4162C13.9192 20.2351 13.4957 19.2064 13.4957 18.0669C13.4957 16.9751 13.8657 15.9851 14.5271 15.2735L14.9062 21.4162ZM22.6187 25.199L22.7478 25.3517L22.6187 25.199Z" fill="#A5A7A9" stroke="#A5A7A9" stroke-width="0.4"></path>
                <rect class="fill" x="17.6071" y="5.17856" width="1.7" height="13.4643" rx="0.85" transform="rotate(90 17.6071 5.17856)" fill="#A5A7A9"></rect>
                <rect class="fill" x="10" y="13" width="1.7" height="6" rx="0.85" transform="rotate(90 10 13)" fill="#A5A7A9"></rect>
                <rect class="fill" x="14" y="9" width="1.7" height="10" rx="0.85" transform="rotate(90 14 9)" fill="#A5A7A9"></rect>
              </svg>
                        <div class="content-heading-btn-text">В черновик </div>
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

<div id="product-content-wrap" class="content-wrap content-product-wrap" style="display:none">

                <div class="content-wrap-elem">

                        <div class="product-search content-elem">
					<div id="table-found-head-wrap">
						<div class="content-elem-heading-text" style='padding: 10px; width: 30%; font-size: 16px; color: #A5A7A9;  line-height: 25px;'>Результаты поиска:</div>
						<div id="table-found-hide-btn" class="content-hide-btn">Скрыть —</div>
					</div>
					<div class="product-search-bottom product-search-table-wrap scroll-elem">
                        <div class="table-more-info-arrow"></div>
                        <div id="table-found" class="table product-search-table">
                            <div class="table-row table-heading">
                                <div class="table-elem">Артикул</div>
                                <div class="table-elem">Наименование</div>
                            </div>
                            <!--<a class="table-row table-element" href="">
                                <div class="table-elem">КОС598</div>
                                <div class="table-elem">Костюм “Финикс” бежевый/т. бежевый NEW</div>
                            </a>
                            <a class="table-row table-element" href="">
                                <div class="table-elem">КОС598</div>
                                <div class="table-elem">Костюм “Финикс” св.серый/серый</div>
                            </a>
                            <a class="table-row table-element" href="">
                                <div class="table-elem">КОС598</div>
                                <div class="table-elem">Костюм “Финикс” бежевый/т. серый NEW</div>
                            </a>-->
                        </div>
                    </div>
					</div>
                    <div id='product-elem-content' class="content-elem product-more-info-block">
                        <div class="product-more-info-table-wrap scroll-elem">
                            <div class="table-more-info-arrow"></div>
                            <div id="table-offers" class="table more-info-table">
                                <div class="table-row table-heading">
                                    <div class="table-elem">Характеристика</div>
                                    <div class="table-elem">Остаток</div>
                                    <div class="table-elem">Цена</div>
                                    <div class="table-elem">Пп / Дата</div>
                                </div>

                            </div>
                        </div>
                    </div>
					<div id='product-elem-addinfo' class="product-addinfo content-elem content-elem-info">
                        <div class="content-elem-heading">
                            <div class="content-elem-heading-text">Доп. материал</div>
                            <div class="content-elem-heading-btn content-hide-btn">Скрыть —</div>
                        </div>
                        <div class="content-elem-bottom content-elem-desc content-hide">
                            Сообщаем вам что согласно Постановлению Правительства Российской Федерации № 216 от 29.02.2020 дата запрета оборота немаркированной обуви перенесена на 1 июля 2020 года. Сообщаем вам что согласно Постановлению Правительства Российской Федерации № 216
                            от 29.02.2020 дата запрета оборота немаркированной обуви перенесена на 1 июля 2020 года.
                        </div>
                    </div>
                </div>

	<div id="product-elem-bottom" class="content-wrap-elem">

			<div class="product-slider-block content-elem">
				<div class="product-slider-wrap">
			
				  <div class="product-slider">
					<div class="product-slider-elem"><img class="product-slider-img" src="style/img/product/product-5.jpg" alt=""></div>
					<div class="product-slider-elem"><img class="product-slider-img" src="style/img/product/product-5.jpg" alt=""></div>
					<div class="product-slider-elem"><img class="product-slider-img" src="style/img/product/product-5.jpg" alt=""></div>
					<div class="product-slider-elem"><img class="product-slider-img" src="style/img/product/product-5.jpg" alt=""></div>
				  </div>
			
				  <div class="product-slider-nav">
					<div class="product-slider-nav-elem"><img class="product-slider-nav-img" src="style/img/product/product-5.jpg" alt=""></div>
					<div class="product-slider-nav-elem"><img class="product-slider-nav-img" src="style/img/product/product-5.jpg" alt=""></div>
					<div class="product-slider-nav-elem"><img class="product-slider-nav-img" src="style/img/product/product-5.jpg" alt=""></div>
					<div class="product-slider-nav-elem"><img class="product-slider-nav-img" src="style/img/product/product-5.jpg" alt=""></div>
				  </div>
				  
				</div>
				<div class="product-slider-buttons">
					<a class="product-slider-link" href="#">Сертификаты</a>
					<a id="product-slider-buttons-order" class="product-slider-link" href="#">Заказать</a>
					<a class="product-slider-link" href="#">Добавить в КП</a>
				</div>
			</div>

                    <div class="product-parcel">
                        <div class="product-parcel-wrap">
                            <div class="product-parcel-elem content-elem">
                                <div class="content-hide">
                                    <div class="product-parcel-row">
                                        <div class="product-parcel-text">Наценка: </div>
                                        <div class="product-parcel-value">
                                            <button class="product-parcel-btn" id="product-ex-charge-count-btn" onclick="setCount(this)">1</button>
                                            <button class="product-parcel-btn" id="product-ex-charge-percent-btn" onclick="getExChargePercent()">%</button>
                                            <button class="product-parcel-btn" id="product-ex-charge-value-btn" onclick="getExChargeValue()">₽</button>
                                        </div>
                                    </div>
                                    <div class="product-parcel-row">
                                        <div class="product-parcel-text">Цена с наценкой: </div>
                                        <div class="product-parcel-value"><lable id="price-ex-charge"></lable> ₽</div>
                                    </div>
                                </div>
                                <div class="product-parcel-elem-name">Наценка</div>
                            </div>
                            <div class="product-parcel-elem content-elem">
                                <div class="content-hide">
                                    <div class="product-parcel-row">
                                        <div class="product-parcel-text">Количество: </div>
                                        <div class="product-parcel-value">
                                            <button class="product-parcel-btn" id="product-amount-btn" onclick="recalculate(this)">1</button>
                                        </div>
                                    </div>
                                    <div class="product-parcel-row">
                                        <div class="product-parcel-text">Сума: </div>
										<div class="product-parcel-value"><lable id="product-sum"></lable> ₽</div>
                                    </div>
                                    <div class="product-parcel-row">
                                        <div class="product-parcel-text">Средний вес: </div>
										<div class="product-parcel-value"><lable id="product-weight"></lable> кг</div>
                                    </div>
                                    <div class="product-parcel-row">
                                        <div class="product-parcel-text">Средний объем: </div>
										<div class="product-parcel-value"><lable id="product-valume"></lable> м³</div>
                                    </div>
                                </div>
                                <div class="product-parcel-elem-name">Количество</div>
                            </div>
                            <div class="product-parcel-hide-btn content-hide-btn"> Скрыть —</div>
                        </div>
                    </div>
                    <div class="product-info product-info-tab content-elem">
                        <div class="content-elem-heading">
                            <div class="content-elem-heading-text">Подробно</div>
                            <ul class="product-info-tab-nav">
                                <li class="product-info-tab-link active">Описание</li>
                                <li class="product-info-tab-link">Характеристика</li>
                            </ul>
                            <div class="content-elem-heading-btn content-hide-btn product-info-hide-btn">Скрыть —</div>
                        </div>
                        <div class="content-hide">
                            <div class="product-info-desc product-info-tab-elem active">
                                <div class="content-properties" id='product-protect-prop'>
                                    <div class="content-properties-text" >Свойства:</div>
                                    <div class="content-properties-elem"><img class="content-properties-img" src="/upload/uf/58f/zashchita-ot-mekhanicheskikh-vozdeystviy.png" alt=""></div>
                                    <div class="content-properties-elem"> <img class="content-properties-img" src="/upload/uf/44a/zashchita-ot-istiraniya.png" alt=""></div>
                                </div>
                                <p id="product-detatil-text">
                                </p>
                            </div>
                            <div class="product-info-table-wrap product-info-tab-elem">
                                <div class="table product-info-table scroll-elem">
                                    <div class="table-more-info-arrow"></div>
                                    <div id="product-characteristics" class="table-wrap">
<!--
 										<div class="table-row">
                                            <div class="table-elem">Цвет</div>
                                            <div class="table-elem"></div>
                                        </div>
-->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>