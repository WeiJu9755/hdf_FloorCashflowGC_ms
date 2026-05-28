<?php

session_start();

$memberID = $_SESSION['memberID'];
$powerkey = $_SESSION['powerkey'];


//載入公用函數
@include_once '/website/include/pub_function.php';

@include_once("/website/class/".$site_db."_info_class.php");


$m_location		= "/website/smarty/templates/".$site_db."/".$templates;
$m_pub_modal	= "/website/smarty/templates/".$site_db."/pub_modal";

$sid = "";
if (isset($_GET['sid']))
	$sid = $_GET['sid'];


//程式分類
$ch = empty($_GET['ch']) ? 'default' : $_GET['ch'];
switch($ch) {
	case 'edit':
		$title = "收款金流";
		$sid = "view01";
		$modal = $m_location."/sub_modal/project/func07/FloorCashflowGC_ms/buildings_modify.php";
		include $modal;
		$smarty->assign('show_center',$show_center);
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
		/*
	case 'buildings_sub_add':
		$title = "新增總棟數";
		$sid = "view01";
		$modal = $m_location."/sub_modal/project/func07/FloorCashflowGC_ms/buildings_sub_add.php";
		include $modal;
		$smarty->assign('show_center',$show_center);
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
			*/
			
	case 'buildings_sub_modify':
		$title = "編輯總棟數";
		$sid = "view01";
		$modal = $m_location."/sub_modal/project/func07/FloorCashflowGC_ms/buildings_sub_modify.php";
		include $modal;
		$smarty->assign('show_center',$show_center);
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
		/*
	case 'buildings_sub_detail_add':
		$title = "新增樓層";
		$sid = "view01";
		$modal = $m_location."/sub_modal/project/func07/FloorCashflowGC_ms/buildings_sub_detail_add.php";
		include $modal;
		$smarty->assign('show_center',$show_center);
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
				*/
	case 'buildings_sub_detail_modify':
		$title = "編輯樓層";
		$sid = "view01";
		$modal = $m_location."/sub_modal/project/func07/FloorCashflowGC_ms/buildings_sub_detail_modify.php";
		include $modal;
		$smarty->assign('show_center',$show_center);
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
	default:
		if (empty($sid))
			$sid = "mbpjitem";
		$modal = $m_location."/sub_modal/project/func07/FloorCashflowGC_ms/FloorCashflowGC.php";
		include $modal;
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
};

?>