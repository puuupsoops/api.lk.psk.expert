<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

switch($_REQUEST['PAGE'])
{
	case 'product':
		header('Location: /test/product-page/product.php');
		break;
	case 'order':
		header('Location: /test/product-page/order.php');
		break;
	default:
}
?>
