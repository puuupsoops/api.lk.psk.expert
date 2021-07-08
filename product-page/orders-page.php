<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("");
?>
        <div class="orders-heading">
          <div class="orders-heading-elem">
            <div class="orders-heading-item">
              <div class="orders-heading-text">Контрагент:</div>
              <select class="custom-select" style="width: 100%">
                <option value="0" selected>ООО “Вектор”</option>
                <option value="1">ООО “Вектор”</option>
                <option value="2">ООО “Вектор”</option>
                <option value="3">ООО “Вектор”</option>
              </select>
            </div>
            <div class="orders-heading-item">
              <div class="orders-heading-text">Договор:</div>
              <select class="custom-select" style="width: 100%">
                <option value="0" selected>По умолчанию</option>
                <option value="1">По умолчанию № 1</option>
                <option value="2">По умолчанию № 2</option>
                <option value="3">По умолчанию № 3</option>
              </select>
            </div>
            <div class="orders-heading-item">
              <div class="orders-heading-text">Период:</div>
              <select class="custom-select" style="width: 100%">
                <option value="0" selected>01.01.2020 - 31.01.2020</option>
                <option value="1">01.01.2020 - 31.01.2020</option>
                <option value="2">01.01.2020 - 31.01.2020</option>
                <option value="3">01.01.2020 - 31.01.2020</option>
              </select>
            </div>
            <div class="orders-heading-clean"></div>
          </div>
          <div class="orders-heading-elem">
            <div class="content-heading-btn">
              <svg class="content-heading-btn-img" width="26" height="25" viewBox="0 0 26 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path class="fill" fill-rule="evenodd" clip-rule="evenodd" d="M3 2H18.75C19.3023 2 19.75 2.44772 19.75 3V11.5H21.75V3C21.75 1.34315 20.4069 0 18.75 0H3C1.34315 0 0 1.34315 0 3V21.8571C0 23.514 1.34315 24.8571 3 24.8571H16V22.8571H3C2.44772 22.8571 2 22.4094 2 21.8571V3C2 2.44772 2.44772 2 3 2Z" fill="#A5A7A9"></path>
                <rect class="fill" x="17.6071" y="5.17871" width="1.7" height="13.4643" rx="0.85" transform="rotate(90 17.6071 5.17871)" fill="#A5A7A9"></rect>
                <rect class="fill" x="10" y="13" width="1.7" height="6" rx="0.85" transform="rotate(90 10 13)" fill="#A5A7A9"></rect>
                <rect class="fill" x="14" y="9" width="1.7" height="10" rx="0.85" transform="rotate(90 14 9)" fill="#A5A7A9"></rect>
                <path class="fill" fill-rule="evenodd" clip-rule="evenodd" d="M20.8 15C20.2478 15 19.8 15.4477 19.8 16V19H16.8C16.2478 19 15.8 19.4477 15.8 20C15.8 20.5523 16.2478 21 16.8 21H19.8V24C19.8 24.5523 20.2478 25 20.8 25C21.3523 25 21.8 24.5523 21.8 24V21H24.8C25.3523 21 25.8 20.5523 25.8 20C25.8 19.4477 25.3523 19 24.8 19H21.8V16C21.8 15.4477 21.3523 15 20.8 15Z" fill="#A5A7A9"></path>
              </svg>
              <div class="content-heading-btn-text">Новый заказ</div>
            </div>
          </div>
        </div>



        <div class="orders-heading-search">
          <div class="orders-heading-search-elem">
            <div class="product-search-text">Поиск по: </div>
            <div class="orders-heading-search-select-wrap">
              <div class="orders-heading-search-select-elem">
                <select class="custom-select" style="width: 100%">
                  <option value="0" selected>Статус</option>
                  <option value="1">Статус №2</option>
                  <option value="2">Статус №3</option>
                  <option value="3">Статус №4</option>
                </select>
              </div>
              <div class="orders-heading-search-select-elem">
                <select class="custom-select" style="width: 100%">
                  <option value="0" selected>Статус</option>
                  <option value="1">Статус №2</option>
                  <option value="2">Статус №3</option>
                  <option value="3">Статус №4</option>
                </select>
              </div>
            </div>
            <div class="product-search-clear"></div>
          </div>
          <div class="orders-heading-search-elem">
            <div class="orders-heading-search-btn gradient-btn">
              <div class="gradient-btn-text">Поиск</div>
            </div>
          </div>
        </div>


        <div class="orders-list-wrap content-elem">
          <div class="table orders-list-table scroll-elem">
            <div class="table-more-info-arrow"></div>
            <div class="orders-list-table-wrap">
              <div class="table-row table-heading">
                <div class="table-elem">№</div>
                <div class="table-elem">Наименование</div>
                <div class="table-elem">Контрагент</div>
                <div class="table-elem">Номер</div>
                <div class="table-elem">Дата создания</div>
                <div class="table-elem">Статус</div>
                <div class="table-elem">Инфо</div>
              </div>

              <div class="orders-list-item">
                <div class="table-row orders-list-title">
                  <div class="table-elem">
                     1
                    <div class="table-arrow"></div>
                  </div>
                  <div class="table-elem">Заказ № 1232 от 02.02.2020</div>
                  <div class="table-elem">ООО “Вектор”</div>
                  <div class="table-elem">1232</div>
                  <div class="table-elem">02.02.20</div>
                  <div class="table-elem">Подтверждён</div>
                  <div class="table-elem"> 
                    <button class="orders-list-more">Подробно</button>
                    <div class="orders-list-more-dropdown"><a class="orders-list-more-dropdown-link" href="">Повторить</a><a class="orders-list-more-dropdown-link" href="">Детали заказа</a><a class="orders-list-more-dropdown-link" href="">Скачать документы</a><a class="orders-list-more-dropdown-link" href="">Скачать сертификаты</a><a class="orders-list-more-dropdown-link" href="">Печать документов</a><a class="orders-list-more-dropdown-link" href="">Печать сертификатов</a></div>
                  </div>
                </div>
                <div class="orders-list-info">
                  <div class="orders-list-info-row">
                    <div class="orders-list-info-elem">ЭС</div>
                    <div class="orders-list-info-elem"> 
                      <div class="orders-list-info-about">Счёт № 12 от 02.02.2020</div>
                      <div class="orders-list-info-about">Реализация № 243 от 10.02.2020 + корректировка № 201 от 11.02.2020</div>
                      <div class="orders-list-info-about">Счёт Фактура № 243 от 10.02.2020</div>
                    </div>
                    <div class="orders-list-info-elem orders-list-info-doc-wrap"><a class="orders-list-info-doc sc" href=""></a><a class="orders-list-info-doc upd" href=""></a><a class="orders-list-info-doc sf" href=""></a><a class="orders-list-info-doc kor" href=""></a></div>
                    <div class="orders-list-info-elem"><a class="orders-list-info-link" href="">Сертификаты</a><a class="orders-list-info-link" href="">Скачать все</a></div>
                  </div>
                  <div class="orders-list-info-row">
                    <div class="orders-list-info-elem">ФРО</div>
                    <div class="orders-list-info-elem"> 
                      <div class="orders-list-info-about">Счёт № 12 от 02.02.2020</div>
                      <div class="orders-list-info-about">Реализация № 243 от 10.02.2020 + корректировка № 201 от 11.02.2020</div>
                      <div class="orders-list-info-about">Счёт Фактура № 243 от 10.02.2020</div>
                    </div>
                    <div class="orders-list-info-elem orders-list-info-doc-wrap"><a class="orders-list-info-doc sc" href=""></a><a class="orders-list-info-doc upd" href=""></a><a class="orders-list-info-doc sf" href=""></a></div>
                    <div class="orders-list-info-elem"><a class="orders-list-info-link" href="">Сертификаты</a><a class="orders-list-info-link" href="">Скачать все</a></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>