<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

const MY_HL_BLOCK_ID = 3;

\Bitrix\Main\Loader::includeModule('catalog');
\Bitrix\Main\Loader::includeModule('iblock');

use Bitrix\Highloadblock\HighloadBlockTable as HLBT;

CModule::IncludeModule('search');
CModule::IncludeModule('highloadblock');

function getProduct($id)
{
	
	$arResult = [];
	/*
	| Запрос данных по продукту.
	*/
	$ID = $id; // ID элемента инфоблока.
	$product = CCatalogProduct::GetByIDEx($ID);
	
	$arResult['PRODUCT'] = [
		'ID' => $ID,
		'NAME' => $product['NAME'],
		'ARTICLE' => !empty($product['PROPERTIES']['CML2_ARTICLE']['VALUE']) ? $product['PROPERTIES']['CML2_ARTICLE']['VALUE'] : '',
		'PRICE' => !empty($product['PRICES']['7']['PRICE']) ? $product['PRICES']['7']['PRICE'] : 0,
		'WEIGHT' => !empty($product['PRODUCT']['WEIGHT']) && $product['PRODUCT']['WEIGHT'] != 0 ? ($product['PRODUCT']['WEIGHT']/ 1000) : 0,
		'VALUME' => !empty($product['PROPERTIES']['VALUME']['VALUE']) && $product['PROPERTIES']['VALUME']['VALUE'] != 0 ? ($product['PROPERTIES']['VALUME']['VALUE'] / 1000) : 0,
		'DETAIL_TEXT' => $product['DETAIL_TEXT'],
		'STATUS' => !empty($product['PROPERTIES']['SVOYSTVAPRAYSA']['VALUE_ENUM']) ? $product['PROPERTIES']['SVOYSTVAPRAYSA']['VALUE_ENUM'] : '-',
		'CHARACTERISTICS' => [
			['NAME' => 'Цвет', 'VALUE' => !empty($product['PROPERTIES']['SVTSVET']['VALUE_ENUM']) ? $product['PROPERTIES']['SVTSVET']['VALUE_ENUM'] : '' ],
			['NAME' => 'Стандарт', 'VALUE' => !empty($product['PROPERTIES']['GOST']['VALUE_ENUM']) ? $product['PROPERTIES']['GOST']['VALUE_ENUM'] : ''],
			['NAME' => 'Материал', 'VALUE' => !empty($product['PROPERTIES']['MATERIAL']['VALUE_ENUM']) ? $product['PROPERTIES']['MATERIAL']['VALUE_ENUM'] : ''],
			['NAME' => 'Минпромторг', 'VALUE' => !empty($product['PROPERTIES']['ZAKLYUCHENIEMINPROMTORG']['VALUE_ENUM']) ? $product['PROPERTIES']['ZAKLYUCHENIEMINPROMTORG']['VALUE_ENUM'] : ''],
			['NAME' => 'Размер', 'VALUE' => !empty($product['PROPERTIES']['SVRAZMER']['VALUE_ENUM']) ? $product['PROPERTIES']['SVRAZMER']['VALUE_ENUM'] : ''],
			['NAME' => 'Бренд','VALUE'=> !empty($product['PROPERTIES']['BREND']['VALUE_ENUM']) ? $product['PROPERTIES']['BREND']['VALUE_ENUM'] : ''],
			['NAME' => 'Рост', 'VALUE' => !empty($product['PROPERTIES']['SVROST']['VALUE_ENUM']) ? $product['PROPERTIES']['SVROST']['VALUE_ENUM'] : ''],
			['NAME' => 'Упаковка', 'VALUE' => !empty($product['PROPERTIES']['UPAKOVKA']['VALUE']) ? $product['PROPERTIES']['UPAKOVKA']['VALUE'] : ''],
		],
	];
	
	$arResult['IMAGES'][0] = CFile::GetPath($product['PREVIEW_PICTURE']);

	/*
	| Формирование ссылок на изображения из свойства MORE_PHOTO(картинки).
	*/
	$elements = \Bitrix\Iblock\Elements\ElementGoodsTable::getList([
		'select' => ['ID', 'MORE_PHOTO.FILE'],
		'filter' => [
			'ID' => $id,
		],
	])->fetchCollection();
	
	$i = 1;
	foreach ($elements as $element) {
	
		foreach ($element->getMorePhoto()->getAll() as $value) 
		{
			$arResult['IMAGES'][$i] ='/upload/' . $value->getFile()->getSubdir().'/'.$value->getFile()->getFileName();
			$i++;
		}

	}
	/*
	| Запрос данных по предложениям продукта.
	*/
	
	$prop = CCatalogSKU::getOffersList($ID, 31);
	$arOffers = [];
	$i = 0;
	foreach($prop[$ID] as $key => $value)
	{
			$count = 0;
			$offers = CCatalogProduct::GetByIDEx($key);
		
		$rsStoreProduct = \Bitrix\Catalog\StoreProductTable::getList(array(
			'filter' => array('=PRODUCT_ID'=>$key,'=STORE.ACTIVE'=>'Y'),
			'select' => array('AMOUNT','STORE_ID','STORE_TITLE' => 'STORE.TITLE'),
		));
		
		while($arStoreProduct=$rsStoreProduct->fetch())
		{
		
			$count += $arStoreProduct['AMOUNT'];
		}
			if( !empty($offers['PRICES']['7']['PRICE']) )
			{
				$arOffers[$i] = [
				'ID' => $key,
				'CHARACTERISTIC' => $offers['PROPERTIES']['_03RAZMER']['VALUE_ENUM'] . ' ' .$offers['PROPERTIES']['CML2_ATTRIBUTES']['VALUE'],
				'RESIDUE'=> $count,
				'PRICE'=>$offers['PRICES']['7']['PRICE'],
				'PPDATA'=>' ',
				];
			}
	
		$i++;

	}

	$arResult['OFFERS'] = array_values($arOffers);

	/*
	| Запрос картинок с защитными свойствами.
	*/

	$arDefenderProps = [];
	$arDefProps = [];
	
	$arDefenderProps[0] = $product['PROPERTIES']['ZASHCHITNYESVOYSTVA'];
	$arDefenderProps[1] = $product['PROPERTIES']['ZASHCHITNYESVOYSTVA2'];
	$arDefenderProps[2] = $product['PROPERTIES']['ZASHCHITNYESVOYSTVA3'];
	$arDefenderProps[3] = $product['PROPERTIES']['ZASHCHITNYESVOYSTVA4'];
	$arDefenderProps[4] = $product['PROPERTIES']['ZASHCHITNYESVOYSTVA5'];
	$arDefenderProps[5] = $product['PROPERTIES']['ZASHCHITNYESVOYSTVA6'];

	foreach($arDefenderProps as $props){
		if( !empty($props['VALUE_XML_ID']) )
		{
			$arDefProps[] = [ 'NAME' => $props['VALUE_ENUM'], 'XML_ID' => $props['VALUE_XML_ID'], 'IMAGE' => '' ];
		}
	}
	
	$hlblock = HLBT::getById(MY_HL_BLOCK_ID)->fetch();
	$entity = HLBT::compileEntity($hlblock);
	$entity_data_class = $entity->getDataClass();
	
	foreach($arDefProps as &$props) 
	{
		$result = $entity_data_class::getList(array(
		   'filter' => ['=UF_XML_ID' => $props['XML_ID'] ]
		))->Fetch();
	
		$props['IMAGE'] = CFile::GetPath($result['UF_FILE']);
	}

	$arResult['PROTECT'] = $arDefProps;

	return $arResult;
}

function getFound($search_field,$query)
{
	
	$arResult = [];
	
	/*
	| Поиск продукта и его вариаций или поиск совпадений
	*/
		$query_result = CIBlockElement::GetList( 
	  [], 
	  [ 
		'IBLOCK_ID' => $iblock_id,  
		  //'SECTION_ID' => $section_id,  
		'ACTIVE' => 'Y', 
		$search_field => $query
	  ], false, false, 
	  ['ID', 'NAME', 'PROPERTY_CML2_ARTICLE'] 
	);
	
	$query_list = [];
	$i = 0;
	while( $el = $query_result->Fetch() ){
		$query_list[$i] = $el;
		$i++;
	}
	
	$i=0;
	
	foreach($query_list as $el)
	{
		$arResult['FOUND'][$i] =  [
			'ID'=> $el['ID'],
			'NAME'=>$el['NAME'],
			'ARTICLE' => $el['PROPERTY_CML2_ARTICLE_VALUE']
		];
		$i++;
	}
	
	return array_merge($arResult,getProduct($query_list[0]['ID']));
}

function Search($query)
{
$arResult = [];

$obSearch = new CSearch;
$obSearch->Search(["QUERY" => $query, "PARAM1" => 'catalog']);

$i=0;
while( $result = $obSearch->Fetch() )
{
	$arResult['FOUND'][$i] = [
	'ID' => $result['ITEM_ID'],
	'NAME' => $result['TITLE']
	];
	$i++;
}

foreach($arResult['FOUND'] as &$el)
{
	$product = CCatalogProduct::GetByIDEx($el['ID']);
	$el['ARTICLE'] = $product['PROPERTIES']['CML2_ARTICLE']['VALUE'];
}

	return array_merge($arResult,getProduct($arResult['FOUND'][0]['ID']));
}


switch((int)$_REQUEST['OPTION'])
{
	case 0:
	header('Content-type: application/json');
	echo json_encode(getFound('=PROPERTY_CML2_ARTICLE',trim((string)$_REQUEST['QUERY'])));
break;

	case 1:
	header('Content-type: application/json');
	echo json_encode(getFound('%NAME',trim((string)$_REQUEST['QUERY'])));
break;

	case 2:
	header('Content-type: application/json');
	echo json_encode( Search(trim((string)$_REQUEST['QUERY'])) );
break;

	case 9:
	header('Content-type: application/json');
	echo json_encode( getProduct( trim((string)$_REQUEST['QUERY']) ) );
break;

	default:

}

?>