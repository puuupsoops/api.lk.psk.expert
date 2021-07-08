<div id="order-header-1" class="content-heading-wrap proudct-heading-wrap">
        <div class="content-heading-wrap-elem">
            <div class="content-heading"><lable id="order-lable-product-name"></lable><lable id="order-lable-product-article"> </lable></div>
        </div>

        <div class="content-heading-wrap-elem">
                <div class="content-heading-price">
                    <div class="content-heading-price-text">Ваша цена: </div>
                    <div class="content-heading-price-value"><lable id="order-lable-product-price"></lable> ₽</div>
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
                    <span class="content-heading-info-value"><label id="order-lable-product-status"></label></span>
                </div>
            </div>
</div>

<div id="order-header-2" class="content-wrap content-product-wrap">

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
        <div class="content-wrap-elem">
            <div class="product-search content-elem">

				<form class="company-search-wrap" id='order-search-form' style='margin: 10px'>
				<div class="company-search-input-wrap">
					<input class="company-search-input" type="text" placeholder="Поиск" autocomplete="off"><img class="company-search-input-clear" src="style/img/icon/cross.svg" alt="">
				</div>
				<button class="company-search-btn gradient-btn"><div class="gradient-btn-text">Поиск</div></button>
				</form>

				<div id="table-order-found-head-wrap" class="">
						<div class="content-elem-heading-text" style="padding: 10px; width: 30%; font-size: 16px; color: #A5A7A9;  line-height: 25px;">Результаты поиска:</div>
						<div id="table-order-found-hide-btn" class="content-hide-btn">Скрыть —</div>
					</div>

                <div class="product-search-bottom scroll-elem">
                    <div class="table-more-info-arrow"></div>
                        <div id="order-table-found" class="table product-search-table">
                                <div class="table-row table-heading">
                                    <div class="table-elem">Артикул</div>
                                    <div class="table-elem">Наименование</div>
                                </div>
                            </div>
                        </div>
                    </div>
        </div>
<!--------------------------------------------------------------------------------------------------------------------------->

<!--------------------------------------------------------------------------------------------------------------------------->
</div>
<!-------------------------------------------------ШАПКА ЗАКАЗА-------------------------------------------------------->
    <div id="order-header-3" class="content-heading-wrap proudct-heading-wrap">
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
<div id="order-header-4" class="content-wrap content-order-wrap">
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
