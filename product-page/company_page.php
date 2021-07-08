<?php
header('Access-Control-Allow-Origin: *');
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Личный кабинет");?>

            <div class="top-line product-page">

                <div class="top-line-menu-btn"><span></span><span></span><span></span></div>
                <div class="top-line-card scroll-elem">
                    <div class="top-line-card-more-info"></div>
                    <div class="top-line-card-wrap">
						<!--<a class="top-line-card-item green" onclick="AgentDrawTmp(this,arData)" href="#" data-code="F00002328" data-ref="0f9e375d-6e83-11e5-96ed-0025907c0298">Спецода</a>-->
                        <!--
						<a class="top-line-card-item orange" href="">ООО Столица</a>
                        <a class="top-line-card-item green" href="">ООО Вектор</a>
                        <a class="top-line-card-item green" href="">ООО Тристан</a>
                        <a class="top-line-card-item orange" href="">ООО Лама</a>
                        <a class="top-line-card-item red" href="">ИП Лавочкин</a>
                        <a class="top-line-card-add" href=""></a>
						-->
                    </div>
                </div>

            </div>

            <div class="company-head">
                <div class="company-head-elem">
                    <div class="company-head-wrap">
                        <div class="company-head-item red">
                            <div class="content-elem">
                                <div class="company-head-item-title">Эксперт Спецодежда</div>
                                <div class="company-card-sale">
                                    <div class="company-card-sale-box"><span class="company-card-sale-text">Скидка</span>
                                        <div class="company-card-sale-value">0 %</div>
                                    </div>
                                </div>
                                <div class="company-head-info">
                                    <div class="company-head-info-row">
										<div class="company-head-info-elem l highlight">Долг: <span id="spec-debt">5 123</span> ₽</div>
										<div class="company-head-info-elem r"><span class="company-head-info-title">Отстрочка</span><span class="company-head-info-val"><span id="spec-deferment">92</span> дня</span></div>
                                    </div>
                                    <div class="company-head-info-row">
                                        <div class="company-head-info-elem l">Баланс: <span id="spec-balance">315</span> ₽</div>
										<div class="company-head-info-elem r"><span class="company-head-info-title">Дата погашения</span><span class="company-head-info-val"><span id="spec-deferment-date">21.02.20</span></span></div>
                                    </div>
                                </div>
                                <div class="company-head-sale sale">
                                    <div class="sale-title">До скидки -1 %</div>
                                    <div class="sale-progressbar-wrap">
                                        <div class="sale-progressbar">
                                            <div class="sale-progressbar-line">
                                                <div class="sale-progressbar-val"><span class="sale-progressbar-val-money">(0)</span><span class="sale-progressbar-val-percent">0%</span></div>
                                            </div>
                                        </div>
                                        <div class="sale-progressbar-money">
                                            <div class="sale-progressbar-min">120 000</div>
                                            <div class="sale-progressbar-max">250 000</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

<!--
                        <div class="company-head-item yellow">
                            <div class="content-elem">
                                <div class="company-head-item-title">Фабрика рабочей обуви</div>
                                <div class="company-card-sale">
                                    <div class="company-card-sale-box"><span class="company-card-sale-text">Скидка</span>
                                        <div class="company-card-sale-value">5 %</div>
                                    </div>
                                </div>
                                <div class="company-head-info">
                                    <div class="company-head-info-row">
                                        <div class="company-head-info-elem l highlight">Долг: 5 123 ₽</div>
                                        <div class="company-head-info-elem r"><span class="company-head-info-title">Отстрочка</span><span class="company-head-info-val">92 дня</span></div>
                                    </div>
                                    <div class="company-head-info-row">
                                        <div class="company-head-info-elem l">Баланс: 315 ₽</div>
                                        <div class="company-head-info-elem r"><span class="company-head-info-title">Дата погашения</span><span class="company-head-info-val">21.02.20</span></div>
                                    </div>
                                </div>
                                <div class="company-head-sale sale">
                                    <div class="sale-title">До скидки 6 %</div>
                                    <div class="sale-progressbar-wrap">
                                        <div class="sale-progressbar">
                                            <div class="sale-progressbar-line">
                                                <div class="sale-progressbar-val"><span class="sale-progressbar-val-money">(0)</span><span class="sale-progressbar-val-percent">0%</span></div>
                                            </div>
                                        </div>
                                        <div class="sale-progressbar-money">
                                            <div class="sale-progressbar-min">120 000</div>
                                            <div class="sale-progressbar-max">250 000</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
-->
                        <div class="company-head-item documents">
                            <div class="content-elem">
                                <div class="company-head-item-title">Документы</div>
                                <ul class="company-head-list">
									<!--
                                    <li class="company-head-list-elem"> <img class="company-head-list-img" src="style/img/icon/doc.svg" alt=""><a class="company-head-list-link" href="">ЭС - счёт №2345 от 03.02.2020</a></li>
                                    <li class="company-head-list-elem"> <img class="company-head-list-img" src="style/img/icon/doc.svg" alt=""><a class="company-head-list-link" href="">ЭС - счёт №2345 от 03.02.2020</a></li>
                                    <li class="company-head-list-elem"> <img class="company-head-list-img" src="style/img/icon/doc.svg" alt=""><a class="company-head-list-link" href="">ЭС - счёт №2345 от 03.02.2020</a></li>
                                    <li class="company-head-list-elem"> <img class="company-head-list-img" src="style/img/icon/doc.svg" alt=""><a class="company-head-list-link" href="">ЭС - счёт №2345 от 03.02.2020</a></li>
                                    <li class="company-head-list-elem"> <img class="company-head-list-img" src="style/img/icon/doc.svg" alt=""><a class="company-head-list-link" href="">ЭС - счёт №2345 от 03.02.2020</a></li>
									-->
								</ul><a class="company-head-btn" href="">Подробнее</a>
                            </div>
                        </div>
                    </div>
                    <div class="company-head-nav">
                        <nav class="company-head-nav-list">
							<!--
                            <li><a class="company-head-nav-link" href="">Рабочий стол</a></li>
                            <li><a class="company-head-nav-link" href="">Взаиморасчёты</a></li>
                            <li><a class="company-head-nav-link" href="">Сертефикаты</a></li>
                            <li><a class="company-head-nav-link" href="">Комерческие предложения</a></li>
							-->
						</nav>
                    </div>
                </div>
                <div class="company-head-elem">
                    <div class="company-head-about content-elem">
                        <div class="company-head-about-info">
                            <div class="company-head-about-title">ООО "Лама"</div>
                            <div class="company-head-about-wrap">
                                <a class="company-head-about-add" href="#" onclick="alert('In progress');"> <img class="company-head-about-add-img" src="style/img/icon/doc-add.svg" alt="">Добавить заказ </a>
                                <div class="company-head-about-text"><span class="company-head-about-text-top">Город</span><span class="company-head-about-text-bottom" id="company-city-name">Пермь</span></div>
                            </div>
                            <div class="company-head-about-text-wrap">
                                <div class="company-head-about-text"><span class="company-head-about-text-top">ИНН </span><span class="company-head-about-text-bottom">5903 1023 45</span></div>
                                <div class="company-head-about-text"><span class="company-head-about-text-top">Телефон</span><span class="company-head-about-text-bottom">8 908 123 45 67</span></div>
                                <div class="company-head-about-text"><span class="company-head-about-text-top">БИК </span><span class="company-head-about-text-bottom">04 57 71 603</span></div>
                                <div class="company-head-about-text"><span class="company-head-about-text-top">E-mail</span><span class="company-head-about-text-bottom">Company@mail.ru</span></div>
                                <div class="company-head-about-text"><span class="company-head-about-text-top">Р/сч</span><span class="company-head-about-text-bottom">1234 5678 9123 4567 8912</span></div>
                                <div class="company-head-about-text"><span class="company-head-about-text-top">Адрес</span><span class="company-head-about-text-bottom">Пермь, ул. Ленина 1-101</span></div>
                                <div class="company-head-about-text"><span class="company-head-about-text-top">К/сч</span><span class="company-head-about-text-bottom">1234 5678 9123 4567 8912</span></div>
                            </div>
                        </div>
                        <div class="company-head-about-btn-wrap">
							<a class="company-head-about-btn" href="#" onclick="alert('In progress');">Договора</a>
							<a class="company-head-about-btn" href="#" onclick="alert('In progress');">Редактировать</a>
						</div>
                    </div>
                </div>
            </div>
            <div class="company-sale sale">
                <div class="sale-head">
                    <div class="sale-title">Скидка 6 %</div>
                    <div class="sale-lacks">Не хватает 51 710 до скидки 10 %</div>
                </div>
                <div class="sale-progressbar-wrap">
                    <div class="sale-progressbar">
                        <div class="sale-progressbar-line">
                            <div class="sale-progressbar-val"><span class="sale-progressbar-val-money">(195 400)</span><span class="sale-progressbar-val-percent">73%</span></div>
                        </div>
                    </div>
<!--
                    <div class="sale-progressbar-money">
                        <div class="sale-progressbar-min">120 000</div>
                        <div class="sale-progressbar-max">250 000</div>
					</div>
-->
                </div>
            </div>
            <div class="company-calendar-wrap">
                <div class="company-calendar-box content-elem">
<!--
                    <fo rm class="company-search-wrap" action="">
                        <div class="company-search-input-wrap">
                            <input class="company-search-input" type="text" placeholder="Поиск"><img class="company-search-input-clear" src="style/img/icon/cross.svg" alt="">
                        </div>
                        <button class="company-search-btn gradient-btn"><img class="company-search-btn-img" src="style/img/icon/search.svg" alt=""></button>
                    </form>
-->
                    <div class="company-calendar" id="calendar"></div>
                </div>
<!--
                <div class="company-consultant">
                    <div class="company-consultant-wrap content-elem">
                        <div class="company-consultant-info">
                            <div class="company-consultant-img-box"><img class="company-consultant-img" src="style/img/consultant.jpg" alt=""></div>
                            <div class="company-consultant-info-box">
                                <div class="company-consultant-info-about">
                                    <div class="company-consultant-name">Приходько Катерина Павловна</div>
                                    <div class="company-consultant-position">Персональный менеджер</div>
                                </div>
                                <div class="company-consultant-contact"><a class="company-consultant-phone" href="tel:+89081234567">8 908 123 45 67</a><a class="company-consultant-phone" href="tel:+89081234567">8 908 123 45 67</a></div>
                                <div class="company-consultant-time">
                                    <div class="company-consultant-time-title">Режим работы</div>
                                    <div class="company-consultant-time-val">Пн-Пт, 9:00 - 18:00 МСК</div>
                                </div>
                            </div>
                        </div>
                        <a class="company-consultant-mail" href="mailto:test@text.com"><img class="company-consultant-mail-img" src="style/img/icon/mail-send.svg" alt="">Написать</a>
                    </div>
                </div>
-->
            </div>

<script>

let arData = [
{
"Ref_Key": "0f9e375d-6e83-11e5-96ed-0025907c0298",
"DataVersion": "AAAAABMz+eI=",
"DeletionMark": false,
"Predefined": false,
"Parent_Key": "8ab1b35b-8fef-11df-b0f9-0050569a3a91",
"IsFolder": false,
"Code": "F00002328",
"Description": "Спецода",
"ИНН": "7816276550",
"Комментарий": "911-171-18-66 Артем - д.р.24.06.,  Илья Риниккер 911-920-51-49 - д.р.12.01,   911-217-97-17,  921-375-35-11 Олег,  (965) 065-45-23 Андрей - д.р. 15.05. 911-848-72-44-Михаил / 911 900 38 51 Кирилл/ (911) 900-38-45 Илья / 965-008-19-98 Альбина/ Вячеслав 952-226-66-41/ Марина гл.бух 902-576-62-77 / Анастасия 967-919-94-17 / бух Марина — 902-576-62-77",
"ДополнительноеОписание": "",
"ГоловнойКонтрагент_Key": "f5e97461-62c6-11e5-96ed-0025907c0298",
"ИсточникИнформацииПриОбращении_Key": "00000000-0000-0000-0000-000000000000",
"КодПоОКПО": "23109283",
"КПП": "781601001",
"НаименованиеПолное": "Общество с ограниченной ответственностью \"Спецода\"",
"ЮрФизЛицо": "ЮрЛицо",
"ОсновнойБанковскийСчет_Key": "1f46c972-6e83-11e5-96ed-0025907c0298",
"ОсновнойДоговорКонтрагента_Key": "193d6821-a966-11e9-8176-005056bf1558",
"ОсновнойВидДеятельности_Key": "00000000-0000-0000-0000-000000000000",
"ДокументУдостоверяющийЛичность": "",
"ОсновнойМенеджерПокупателя_Key": "24b3f998-78b9-11e6-894c-0025907c0298",
"ОсновнойМенеджерПоставщика_Key": "00000000-0000-0000-0000-000000000000",
"Покупатель": true,
"Поставщик": false,
"РасписаниеРаботыСтрокой": "",
"СрокВыполненияЗаказаПоставщиком": 0,
"ОсновноеКонтактноеЛицо_Key": "00000000-0000-0000-0000-000000000000",
"НеЯвляетсяРезидентом": false,
"ОКОПФ_Key": "00000000-0000-0000-0000-000000000000",
"Регион_Key": "9b7aa0f2-5d1f-11e1-a1c2-0050569a3a91",
"ГруппаДоступаКонтрагента_Key": "49f6b94c-80c7-11e6-9f72-0025907c0298",
"ОбособленноеПодразделение": false,
"НазваниеТК_Key": "4f192a0d-4889-11e4-8704-0025907c0298",
"ЭтоНовыйКонтрагентДата": "2015-10-09T15:40:29",
"ЭтоНовыйКонтрагентНадпись": "Этот контрагент - новый. Был создан 09.10.2015 15:40:29",
"ЭтоНовыйКонтрагент": true,
"Мастерснаб": false,
"Спецэкспо": false,
"Техно": false,
"БезНДС": false,
"ОсновнойГенДиректорПоставщика": "",
"ПолГенДир": "",
"ОтделВладелец_Key": "b0e1af1f-88ec-11df-b0f9-0050569a3a91",
"ОграничитьДоступ": false,
"БонуснаяПрограмма": true,
"РозничныйПокупатель": false,
"ТипБонуснойПрограммы": "",
"ГруппаДоступаКонтрагентаПоставщик_Key": "00000000-0000-0000-0000-000000000000",
"Эксперт_НерекомендованныйПоставщик": false,
"Эксперт_НерекомендованныйПоставщикДата": "0001-01-01T00:00:00",
"Эксперт_ДатаВыдачиФото": "0001-01-01T00:00:00",
"Эксперт_БылоВыданоФото": false,
"Эксперт_ИсточникИнформации_Key": "00000000-0000-0000-0000-000000000000",
"Эксперт_УчастникМаркировки": "Зарегистрированный",
"Эксперт_ЗапретитьИспользованиеАкций": false,
"Эксперт_ОтправкаПоЭДО": false,
"ВидыДеятельности": [],
"МенеджерыПокупателя": [
{
"Ref_Key": "0f9e375d-6e83-11e5-96ed-0025907c0298",
"LineNumber": "1",
"МенеджерПокупателя_Key": "6de4f591-af26-11de-a660-0050569a3a91"
}
],
"КопияСКонтрагента": [
{
"Ref_Key": "0f9e375d-6e83-11e5-96ed-0025907c0298",
"LineNumber": "1",
"КопияКонтрагента_Key": "f5e97461-62c6-11e5-96ed-0025907c0298"
},
{
"Ref_Key": "0f9e375d-6e83-11e5-96ed-0025907c0298",
"LineNumber": "2",
"КопияКонтрагента_Key": "d6321db3-d4f1-11e3-b967-0025907c0298"
}
],
"КарточкаРеквизитовЭкспорт": [],
"НомераКарт": [],
"Parent@navigationLinkUrl": "Catalog_Контрагенты(guid'0f9e375d-6e83-11e5-96ed-0025907c0298')/Parent",
"ГоловнойКонтрагент@navigationLinkUrl": "Catalog_Контрагенты(guid'0f9e375d-6e83-11e5-96ed-0025907c0298')/ГоловнойКонтрагент",
"ОсновнойБанковскийСчет@navigationLinkUrl": "Catalog_Контрагенты(guid'0f9e375d-6e83-11e5-96ed-0025907c0298')/ОсновнойБанковскийСчет",
"ОсновнойДоговорКонтрагента@navigationLinkUrl": "Catalog_Контрагенты(guid'0f9e375d-6e83-11e5-96ed-0025907c0298')/ОсновнойДоговорКонтрагента",
"ОсновнойМенеджерПокупателя@navigationLinkUrl": "Catalog_Контрагенты(guid'0f9e375d-6e83-11e5-96ed-0025907c0298')/ОсновнойМенеджерПокупателя",
"Регион@navigationLinkUrl": "Catalog_Контрагенты(guid'0f9e375d-6e83-11e5-96ed-0025907c0298')/Регион",
"ГруппаДоступаКонтрагента@navigationLinkUrl": "Catalog_Контрагенты(guid'0f9e375d-6e83-11e5-96ed-0025907c0298')/ГруппаДоступаКонтрагента",
"НазваниеТК@navigationLinkUrl": "Catalog_Контрагенты(guid'0f9e375d-6e83-11e5-96ed-0025907c0298')/НазваниеТК",
"ОтделВладелец@navigationLinkUrl": "Catalog_Контрагенты(guid'0f9e375d-6e83-11e5-96ed-0025907c0298')/ОтделВладелец"
}
];

	function AgentDrawTmp(data)
{
		console.log(data);
		$('.company-head-about-title').text(data.agent.НаименованиеПолное);

	let arText = [
		data.agent.ИНН, 'Телефон', '044030811', 'E-mail', '40702810236060009745', 'Адрес', '30101810300000000811'
	];

	$('#company-city-name').text('Санкт-Петербург');

	$('.company-head-about-text-wrap').find('.company-head-about-text-bottom').each(function(index,item){

		$(item).text(arText[index]);
	});

	$('#spec-debt').text(0);
	$('#spec-balance').text(0);
	$('#spec-deferment').text(0);
	$('#spec-deferment-date').text(0);

};

	function AgentDraw(data)
{
		console.log($(data).data('ref'));
		alert($(data).data('ref'));
};

	async function getData(){
	let url = 'http://10.68.5.241/Stimul_test1/odata/standard.odata/Catalog_%D0%9A%D0%BE%D0%BD%D1%82%D1%80%D0%B0%D0%B3%D0%B5%D0%BD%D1%82%D1%8B?$format=json&$filter=%D0%93%D0%BE%D0%BB%D0%BE%D0%B2%D0%BD%D0%BE%D0%B9%D0%9A%D0%BE%D0%BD%D1%82%D1%80%D0%B0%D0%B3%D0%B5%D0%BD%D1%82_Key%20eq%20guid%27f5e97461-62c6-11e5-96ed-0025907c0298%27%20or%20%D0%93%D0%BE%D0%BB%D0%BE%D0%B2%D0%BD%D0%BE%D0%B9%D0%9A%D0%BE%D0%BD%D1%82%D1%80%D0%B0%D0%B3%D0%B5%D0%BD%D1%82_Key%20eq%20guid%270f9e375d-6e83-11e5-96ed-0025907c0298%27';
	let username = 'Odata';
	let password = 'Odata_';

	let response = await fetch(url,{
		'Authorization': 'Basic ' + btoa('Odata:Odata_'),//btoa('Odata:Odata_')
		'credentials': 'include'
		})
		.then(response => response.json())
			.then(function (json){
				json.value.forEach(function(item){
				$('.top-line-card-wrap').append(`<a class="top-line-card-item red" onclick="getDatabyUid(this);" href="#" data-code="${item.Code}" data-ref="${item.Ref_Key}">${item.Description}</a>`);
			}); 
		 });
}

	async function getDatabyUid(self){

	$(self).addClass('orange').removeClass('red');


	let uid = $(self).data('ref');
	let url = `http://10.68.5.241/Stimul_test1/odata/standard.odata/Catalog_Контрагенты?$format=json&$filter=Ref_Key eq guid'${uid}'`;
	let username = 'Odata';
	let password = 'Odata_';

	let data = new Object();

	let response = await fetch(url,{
		'Authorization': 'Basic ' + btoa(`${username} + ':' + ${password}`),//btoa('Odata:Odata_')
		'credentials': 'include'
		})
	.then(response => response.json())
		.then(function(json){

			Object.defineProperty(data, 'agent', {
				__proto__: null,
				value: json.value[0]
			});

			console.log(data);
			AgentDrawTmp(data);
	})
	.then(function(){
		console.log(getRegionByUid(data.agent.Ref_Key));

	});
}

	async function getRegionByUid(uid){
		let url = `http://10.68.5.241/Stimul_test1/odata/standard.odata/Catalog_Контрагенты(guid'${uid}')/Регион?$format=json`;
		let username = 'Odata';
		let password = 'Odata_';

		let response = await fetch(url,{
		'Authorization': 'Basic ' + btoa(`${username} + ':' + ${password}`),//btoa('Odata:Odata_')
		'credentials': 'include'
		})
		.then(response => response.json());

		return response; 
}

	$( document ).ready(function() {

	getData();

});
</script>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>