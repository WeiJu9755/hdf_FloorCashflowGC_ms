<?php

session_start();

$memberID = $_SESSION['memberID'];
$powerkey = $_SESSION['powerkey'];


require_once '/website/os/Mobile-Detect-2.8.34/Mobile_Detect.php';
$detect = new Mobile_Detect;


//載入公用函數
@include_once '/website/include/pub_function.php';

//連結資料
@include_once("/website/class/".$site_db."_info_class.php");
require_once __DIR__."/module_modify_log.php";

/* 使用xajax */
@include_once '/website/xajax/xajax_core/xajax.inc.php';
$xajax = new xajax();

$xajax->registerFunction("processform");
function processform($aFormValues){

	$objResponse = new xajaxResponse();
	
	$memberID							= trim($aFormValues['memberID']);
	$auto_seq							= trim($aFormValues['auto_seq']);
	$first_actual_billing_date			= trim($aFormValues['first_actual_billing_date']);
	$first_layout_actual_billing_date	= trim($aFormValues['first_layout_actual_billing_date']);
	$second_actual_billing_date			= trim($aFormValues['second_actual_billing_date']);
	$second_layout_actual_billing_date	= trim($aFormValues['second_layout_actual_billing_date']);
	$project_progress					= trim($aFormValues['project_progress']);
	$first_actual_completed_qty			= normalize_amount_for_sql($aFormValues['first_actual_completed_qty']);
	$second_actual_completed_qty		= normalize_amount_for_sql($aFormValues['second_actual_completed_qty']);
	$first_layout_actual_completed_qty	= normalize_amount_for_sql($aFormValues['first_layout_actual_completed_qty']);
	$second_layout_actual_completed_qty	= normalize_amount_for_sql($aFormValues['second_layout_actual_completed_qty']);
	$completed_qty						= normalize_amount_for_sql(floatval($first_actual_completed_qty) + floatval($second_actual_completed_qty));
	$first_actual_collection_date		= trim($aFormValues['first_actual_collection_date']);
	$first_layout_actual_collection_date	= trim($aFormValues['first_layout_actual_collection_date']);
	$second_actual_collection_date		= trim($aFormValues['second_actual_collection_date']);
	$second_layout_actual_collection_date	= trim($aFormValues['second_layout_actual_collection_date']);
	$first_other_deduction_amount		= normalize_amount_for_sql($aFormValues['first_other_deduction_amount']);
	$second_other_deduction_amount		= normalize_amount_for_sql($aFormValues['second_other_deduction_amount']);
	$payment_request_stage				= trim($aFormValues['payment_request_stage']);
	$remark								= trim($aFormValues['remark']);
	
	/*
	if (trim($aFormValues['engineering_overview']) == "")	{
		$objResponse->script("jAlert('警示', '請輸入工程概況', 'red', '', 2000);");
		return $objResponse;
		exit;
	}
	*/

	//存入實體資料庫中
	$mDB = "";
	$mDB = new MywebDB();

	$actual_deduction_result = calculate_actual_deduction_amounts(
		$mDB,
		$auto_seq,
		floatval($first_actual_completed_qty),
		floatval($second_actual_completed_qty)
	);
	if (!$actual_deduction_result['ok']) {
		$mDB->remove();
		$error_message = addslashes($actual_deduction_result['message']);
		$objResponse->script("jAlert('警示', '$error_message', 'red', '', 2500);");
		return $objResponse;
	}

	$first_deduction_amount = floatval($actual_deduction_result['first_retention_amount']) + floatval($actual_deduction_result['first_advance_payment_amount']) + floatval($first_other_deduction_amount);
	$second_deduction_amount = floatval($actual_deduction_result['second_retention_amount']) + floatval($actual_deduction_result['second_advance_payment_amount']) + floatval($second_other_deduction_amount);

	$actual_collection_result = calculate_actual_collection_amounts(
		$mDB,
		$auto_seq,
		floatval($first_actual_completed_qty),
		floatval($second_actual_completed_qty),
		floatval($first_layout_actual_completed_qty),
		floatval($second_layout_actual_completed_qty),
		$first_deduction_amount,
		$second_deduction_amount
	);
	if (!$actual_collection_result['ok']) {
		$mDB->remove();
		$error_message = addslashes($actual_collection_result['message']);
		$objResponse->script("jAlert('警示', '$error_message', 'red', '', 2500);");
		return $objResponse;
	}

	updateFloorCashflowGCModifyLogByDetailSeq($mDB, $auto_seq, $memberID);

	$Qry="UPDATE buildings_sub_detail set
			 first_actual_billing_date	= '$first_actual_billing_date'
			,first_layout_actual_billing_date	= '$first_layout_actual_billing_date'
			,second_actual_billing_date	= '$second_actual_billing_date'
			,second_layout_actual_billing_date	= '$second_layout_actual_billing_date'
			,project_progress	= '$project_progress'
			,completed_qty	= $completed_qty
			,first_actual_completed_qty	= $first_actual_completed_qty
			,second_actual_completed_qty	= $second_actual_completed_qty
			,first_layout_actual_completed_qty	= $first_layout_actual_completed_qty
			,second_layout_actual_completed_qty	= $second_layout_actual_completed_qty
			,actual_collection_amount	= ".$actual_collection_result['total_amount']."
			,first_actual_collection_amount	= ".$actual_collection_result['parts'][0]."
			,first_layout_actual_collection_amount	= ".$actual_collection_result['parts'][1]."
			,second_actual_collection_amount	= ".$actual_collection_result['parts'][2]."
			,second_layout_actual_collection_amount	= ".$actual_collection_result['parts'][3]."
			,first_actual_collection_date	= '$first_actual_collection_date'
			,first_layout_actual_collection_date	= '$first_layout_actual_collection_date'
			,second_actual_collection_date	= '$second_actual_collection_date'
			,second_layout_actual_collection_date	= '$second_layout_actual_collection_date'
			,retention_deduction_amount	= ".$actual_deduction_result['first_retention_amount']."
			,advance_payment_deduction_amount	= ".$actual_deduction_result['first_advance_payment_amount']."
			,first_other_deduction_amount	= $first_other_deduction_amount
			,second_retention_deduction_amount	= ".$actual_deduction_result['second_retention_amount']."
			,second_advance_payment_deduction_amount	= ".$actual_deduction_result['second_advance_payment_amount']."
			,second_other_deduction_amount	= $second_other_deduction_amount
			,payment_request_stage	= '$payment_request_stage'
			,remark	= '$remark'
			,makeby	= '$memberID'
			,last_modify	= now()
			where auto_seq = '$auto_seq'";
			
	$mDB->query($Qry);
	$mDB->remove();

	$objResponse->script("setSave();");
	$objResponse->script("parent.buildings_sub_detail_myDraw();");

	$objResponse->script("art.dialog.tips('已存檔!',1);");
	$objResponse->script("parent.$.fancybox.close();");
		
	
	return $objResponse;
}

function normalize_amount_for_sql($value) {
	$value = str_replace(",", "", trim((string)$value));
	if ($value == "" || !is_numeric($value)) {
		return "0";
	}
	return (string)floatval($value);
}

/**
 * 依樓層類型取得標準/屋突與放樣單價，分別乘上實際數量後扣除該次扣抵款。
 */
function calculate_actual_collection_amounts($mDB, $auto_seq, $first_actual_completed_qty, $second_actual_completed_qty, $first_layout_actual_completed_qty, $second_layout_actual_completed_qty, $first_deduction_amount, $second_deduction_amount) {
	$auto_seq_sql = addslashes($auto_seq);
	$Qry="SELECT
			 a.floor,
			 b.std_layer_floor,
			 b.roof_protrusion_floor,
			 c.std_floor_u_price,
			 c.roof_protrusion_u_price,
			 c.layout_std_floor_u_price,
			 c.square_roof_protrusion_u_price
		FROM buildings_sub_detail a
		LEFT JOIN buildings_sub b ON b.case_id = a.case_id AND b.building = a.building
		LEFT JOIN CaseManagement c ON c.case_id = a.case_id
		WHERE a.auto_seq = '$auto_seq_sql'";
	$mDB->query($Qry);
	if ($mDB->rowCount() == 0) {
		return array('ok' => false, 'message' => '找不到樓層資料，無法計算實際收款金額。');
	}

	$row = $mDB->fetchRow(2);
	$floor_key = actual_collection_floor_key($row['floor']);
	$std_floors = actual_collection_floor_range_list($row['std_layer_floor']);
	$roof_floors = actual_collection_floor_range_list($row['roof_protrusion_floor']);

	if ($floor_key != "" && isset($std_floors[$floor_key])) {
		$unit_price = floatval($row['std_floor_u_price']);
		$layout_unit_price = floatval($row['layout_std_floor_u_price']);
	} else if ($floor_key != "" && isset($roof_floors[$floor_key])) {
		$unit_price = floatval($row['roof_protrusion_u_price']);
		$layout_unit_price = floatval($row['square_roof_protrusion_u_price']);
	} else {
		return array('ok' => false, 'message' => '此樓層不在標準層或屋突層設定範圍內，無法判斷實際收款單價。');
	}

	$first_qty = max(0, floatval($first_actual_completed_qty));
	$second_qty = max(0, floatval($second_actual_completed_qty));
	$first_layout_qty = max(0, floatval($first_layout_actual_completed_qty));
	$second_layout_qty = max(0, floatval($second_layout_actual_completed_qty));
	$first_deduction_amount = floatval($first_deduction_amount);
	$second_deduction_amount = floatval($second_deduction_amount);
	$parts = array(
		round(($unit_price * $first_qty) - $first_deduction_amount, 2),
		round(($layout_unit_price * $first_layout_qty) - $first_deduction_amount, 2),
		round(($unit_price * $second_qty) - $second_deduction_amount, 2),
		round(($layout_unit_price * $second_layout_qty) - $second_deduction_amount, 2)
	);
	$total_amount = round(array_sum($parts), 2);

	return array(
		'ok' => true,
		'total_amount' => number_format($total_amount, 2, '.', ''),
		'parts' => array(
			number_format($parts[0], 2, '.', ''),
			number_format($parts[1], 2, '.', ''),
			number_format($parts[2], 2, '.', ''),
			number_format($parts[3], 2, '.', '')
		)
	);
}

/**
 * 依樓層單價與第一次/第二次實際計價數量，自動計算實際扣抵保留款與預收款。
 */
function calculate_actual_deduction_amounts($mDB, $auto_seq, $first_actual_completed_qty, $second_actual_completed_qty) {
	$auto_seq_sql = addslashes($auto_seq);
	$Qry="SELECT
			 a.case_id,
			 a.floor,
			 b.std_layer_floor,
			 b.roof_protrusion_floor,
			 c.std_floor_u_price,
			 c.roof_protrusion_u_price
		FROM buildings_sub_detail a
		LEFT JOIN buildings_sub b ON b.case_id = a.case_id AND b.building = a.building
		LEFT JOIN CaseManagement c ON c.case_id = a.case_id
		WHERE a.auto_seq = '$auto_seq_sql'";
	$mDB->query($Qry);
	if ($mDB->rowCount() == 0) {
		return array('ok' => false, 'message' => '找不到樓層資料，無法計算實際扣抵金額。');
	}

	$row = $mDB->fetchRow(2);
	$floor_key = actual_collection_floor_key($row['floor']);
	$std_floors = actual_collection_floor_range_list($row['std_layer_floor']);
	$roof_floors = actual_collection_floor_range_list($row['roof_protrusion_floor']);

	if ($floor_key != "" && isset($std_floors[$floor_key])) {
		$unit_price = floatval($row['std_floor_u_price']);
	} else if ($floor_key != "" && isset($roof_floors[$floor_key])) {
		$unit_price = floatval($row['roof_protrusion_u_price']);
	} else {
		return array('ok' => false, 'message' => '此樓層不在標準層或屋突層設定範圍內，無法判斷實際扣抵單價。');
	}

	$case_id_sql = addslashes($row['case_id']);
	$retention_rate = actual_deduction_case_percentage_sum($mDB, "retention", $case_id_sql) / 100;
	$advance_payment_rate = actual_deduction_case_percentage_sum($mDB, "advance_payment", $case_id_sql) / 100;
	$first_base_amount = max(0, floatval($first_actual_completed_qty)) * $unit_price;
	$second_base_amount = max(0, floatval($second_actual_completed_qty)) * $unit_price;

	return array(
		'ok' => true,
		'first_retention_amount' => number_format(round($first_base_amount * $retention_rate, 2), 2, '.', ''),
		'first_advance_payment_amount' => number_format(round($first_base_amount * $advance_payment_rate, 2), 2, '.', ''),
		'second_retention_amount' => number_format(round($second_base_amount * $retention_rate, 2), 2, '.', ''),
		'second_advance_payment_amount' => number_format(round($second_base_amount * $advance_payment_rate, 2), 2, '.', '')
	);
}

function actual_deduction_case_percentage_sum($mDB, $table_name, $case_id_sql) {
	if (!in_array($table_name, array("retention", "advance_payment"), true)) {
		return 0;
	}

	$Qry="SELECT COALESCE(SUM(percentage), 0) AS percentage_total FROM $table_name WHERE case_id = '$case_id_sql'";
	$mDB->query($Qry);
	if ($mDB->rowCount() == 0) {
		return 0;
	}

	$row = $mDB->fetchRow(2);
	return floatval($row['percentage_total']);
}

function actual_collection_floor_range_list($floor_text) {
	$result = array();
	$floor_text = trim((string)$floor_text);
	if ($floor_text == "") {
		return $result;
	}

	$floor_text = str_replace(array("，","、","；",";","~","～","－","—","至","到"), array(",",",",",",",","-","-","-","-","-","-"), $floor_text);
	$floor_text = str_replace(" ", "", $floor_text);
	$parts = preg_split("/,+/", $floor_text);
	for ($i = 0; $i < count($parts); $i++) {
		$part = trim($parts[$i]);
		if ($part == "") {
			continue;
		}
		if (strpos($part, "-") !== false) {
			$range_parts = explode("-", $part);
			if (count($range_parts) != 2) {
				return array();
			}
			$start = actual_collection_floor_code($range_parts[0]);
			$end = actual_collection_floor_code($range_parts[1]);
			if ($start === false || $end === false || $start['prefix'] != $end['prefix']) {
				return array();
			}
			for ($floor_no = min($start['number'], $end['number']); $floor_no <= max($start['number'], $end['number']); $floor_no++) {
				$result[$start['prefix'].":".$floor_no] = true;
			}
		} else {
			$single = actual_collection_floor_code($part);
			if ($single === false) {
				return array();
			}
			$result[$single['prefix'].":".$single['number']] = true;
		}
	}
	return $result;
}

function actual_collection_floor_key($floor_text) {
	$floor = actual_collection_floor_code($floor_text);
	return ($floor === false) ? "" : $floor['prefix'].":".$floor['number'];
}

function actual_collection_floor_code($floor_text) {
	$floor_text = strtoupper(str_replace(" ", "", trim((string)$floor_text)));
	if (preg_match("/^(R|B)?([0-9]+)F?$/", $floor_text, $matches)) {
		return array(
			'prefix' => ($matches[1] == "") ? "N" : $matches[1],
			'number' => intval($matches[2])
		);
	}
	return false;
}

$xajax->processRequest();


$auto_seq = $_GET['auto_seq'];


$mDB = "";
$mDB = new MywebDB();


$fm = $_GET['fm'];

$mess_title = $title;

$case_id = "";
$building = "";
$floor = "";
$expected_actual_delivery_date = "";
$expected_actual_grouting_date = "";
$first_expected_collection_amount = "";
$first_layout_expected_collection_amount = "";
$second_expected_collection_amount = "";
$second_layout_expected_collection_amount = "";
$retention_deduction_amount = "";
$advance_payment_deduction_amount = "";
$first_other_deduction_amount = "";
$second_retention_deduction_amount = "";
$second_advance_payment_deduction_amount = "";
$second_other_deduction_amount = "";
$first_expected_collection_date_1 = "";
$first_expected_collection_date_2 = "";
$second_expected_collection_date_1 = "";
$second_expected_collection_date_2 = "";
$actual_submission_date = "";
$actual_grouting_date = "";
$actual_billing_date = "";
$first_actual_billing_date = "";
$first_layout_actual_billing_date = "";
$second_actual_billing_date = "";
$second_layout_actual_billing_date = "";
$project_progress = "";
$completed_qty = "";
$first_actual_completed_qty = "";
$second_actual_completed_qty = "";
$first_layout_actual_completed_qty = "";
$second_layout_actual_completed_qty = "";
$actual_collection_amount = "";
$first_actual_collection_amount = "";
$first_layout_actual_collection_amount = "";
$second_actual_collection_amount = "";
$second_layout_actual_collection_amount = "";
$actual_collection_date = "";
$first_actual_collection_date = "";
$first_layout_actual_collection_date = "";
$second_actual_collection_date = "";
$second_layout_actual_collection_date = "";
$payment_request_stage = "";
$remark = "";

$Qry="SELECT a.*
FROM buildings_sub_detail a
WHERE a.auto_seq = '$auto_seq'";
$mDB->query($Qry);


$total = $mDB->rowCount();
if ($total > 0) {
    //已找到符合資料
	$row=$mDB->fetchRow(2);
	$case_id = $row['case_id'];
	$building = $row['building'];
	$floor = $row['floor'];
	$expected_actual_delivery_date = $row['expected_actual_delivery_date'];
	$expected_actual_grouting_date = $row['expected_actual_grouting_date'];
	$first_expected_collection_amount = $row['first_expected_collection_amount'];
	$first_layout_expected_collection_amount = $row['first_layout_expected_collection_amount'];
	$second_expected_collection_amount = $row['second_expected_collection_amount'];
	$second_layout_expected_collection_amount = $row['second_layout_expected_collection_amount'];
	$retention_deduction_amount = $row['retention_deduction_amount'];
	$advance_payment_deduction_amount = $row['advance_payment_deduction_amount'];
	$first_other_deduction_amount = $row['first_other_deduction_amount'];
	$second_retention_deduction_amount = $row['second_retention_deduction_amount'];
	$second_advance_payment_deduction_amount = $row['second_advance_payment_deduction_amount'];
	$second_other_deduction_amount = $row['second_other_deduction_amount'];
	$first_expected_collection_date_1 = $row['first_expected_collection_date_1'];
	$first_expected_collection_date_2 = $row['first_expected_collection_date_2'];
	$second_expected_collection_date_1 = $row['second_expected_collection_date_1'];
	$second_expected_collection_date_2 = $row['second_expected_collection_date_2'];
	$actual_submission_date = $row['actual_submission_date'];
	$actual_grouting_date = $row['actual_grouting_date'];
	$actual_billing_date = $row['actual_billing_date'];
	$first_actual_billing_date = $row['first_actual_billing_date'];
	$first_layout_actual_billing_date = $row['first_layout_actual_billing_date'];
	$second_actual_billing_date = $row['second_actual_billing_date'];
	$second_layout_actual_billing_date = $row['second_layout_actual_billing_date'];
	$project_progress = $row['project_progress'];
	$completed_qty = $row['completed_qty'];
	$first_actual_completed_qty = $row['first_actual_completed_qty'];
	$second_actual_completed_qty = $row['second_actual_completed_qty'];
	$first_layout_actual_completed_qty = $row['first_layout_actual_completed_qty'];
	$second_layout_actual_completed_qty = $row['second_layout_actual_completed_qty'];
	$actual_collection_amount = $row['actual_collection_amount'];
	$first_actual_collection_amount = $row['first_actual_collection_amount'];
	$first_layout_actual_collection_amount = $row['first_layout_actual_collection_amount'];
	$second_actual_collection_amount = $row['second_actual_collection_amount'];
	$second_layout_actual_collection_amount = $row['second_layout_actual_collection_amount'];
	$actual_collection_date = $row['actual_collection_date'];
	$first_actual_collection_date = $row['first_actual_collection_date'];
	$first_layout_actual_collection_date = $row['first_layout_actual_collection_date'];
	$second_actual_collection_date = $row['second_actual_collection_date'];
	$second_layout_actual_collection_date = $row['second_layout_actual_collection_date'];
	$payment_request_stage = $row['payment_request_stage'];
	$remark = $row['remark'];

}

if ($first_actual_billing_date == "" && $first_layout_actual_billing_date == "" && $second_actual_billing_date == "" && $second_layout_actual_billing_date == "" && $actual_billing_date != "") {
	$first_actual_billing_date = $actual_billing_date;
}
if ($first_actual_collection_amount == "" && $first_layout_actual_collection_amount == "" && $second_actual_collection_amount == "" && $second_layout_actual_collection_amount == "" && $actual_collection_amount != "") {
	$first_actual_collection_amount = $actual_collection_amount;
}
if ($first_actual_collection_date == "" && $first_layout_actual_collection_date == "" && $second_actual_collection_date == "" && $second_layout_actual_collection_date == "" && $actual_collection_date != "") {
	$first_actual_collection_date = $actual_collection_date;
}
if ($first_actual_completed_qty == "" && $second_actual_completed_qty == "" && $completed_qty != "") {
	$first_actual_completed_qty = $completed_qty;
}
if ($first_layout_actual_completed_qty == "" && $second_layout_actual_completed_qty == "") {
	$first_layout_actual_completed_qty = $first_actual_completed_qty;
	$second_layout_actual_completed_qty = $second_actual_completed_qty;
}


$mDB->remove();

$show_first_expected_collection_amount = ($first_expected_collection_amount != "" && $first_expected_collection_amount != 0) ? number_format($first_expected_collection_amount, 2) : "";
$show_first_layout_expected_collection_amount = ($first_layout_expected_collection_amount != "" && $first_layout_expected_collection_amount != 0) ? number_format($first_layout_expected_collection_amount, 2) : "";
$show_second_expected_collection_amount = ($second_expected_collection_amount != "" && $second_expected_collection_amount != 0) ? number_format($second_expected_collection_amount, 2) : "";
$show_second_layout_expected_collection_amount = ($second_layout_expected_collection_amount != "" && $second_layout_expected_collection_amount != 0) ? number_format($second_layout_expected_collection_amount, 2) : "";
$show_retention_deduction_amount = ($retention_deduction_amount != "" && $retention_deduction_amount != 0) ? number_format($retention_deduction_amount, 2) : "";
$show_advance_payment_deduction_amount = ($advance_payment_deduction_amount != "" && $advance_payment_deduction_amount != 0) ? number_format($advance_payment_deduction_amount, 2) : "";
$show_first_other_deduction_amount = ($first_other_deduction_amount != "" && $first_other_deduction_amount != 0) ? number_format($first_other_deduction_amount, 2) : "";
$show_second_retention_deduction_amount = ($second_retention_deduction_amount != "" && $second_retention_deduction_amount != 0) ? number_format($second_retention_deduction_amount, 2) : "";
$show_second_advance_payment_deduction_amount = ($second_advance_payment_deduction_amount != "" && $second_advance_payment_deduction_amount != 0) ? number_format($second_advance_payment_deduction_amount, 2) : "";
$show_second_other_deduction_amount = ($second_other_deduction_amount != "" && $second_other_deduction_amount != 0) ? number_format($second_other_deduction_amount, 2) : "";
$show_expected_collection_amount = "
	<div class=\"summary_line\"><span>第一次金額：</span><b>$show_first_expected_collection_amount</b><span class=\"summary_gap\"></span><span>第一次放樣：</span><b>$show_first_layout_expected_collection_amount</b></div>
	<div class=\"summary_line\"><span>第二次金額：</span><b>$show_second_expected_collection_amount</b><span class=\"summary_gap\"></span><span>第二次放樣：</span><b>$show_second_layout_expected_collection_amount</b></div>
";
$show_first_expected_collection_date_1 = ($first_expected_collection_date_1 != "" && $first_expected_collection_date_1 != "0000-00-00") ? $first_expected_collection_date_1 : "";
$show_first_expected_collection_date_2 = ($first_expected_collection_date_2 != "" && $first_expected_collection_date_2 != "0000-00-00") ? $first_expected_collection_date_2 : "";
$show_second_expected_collection_date_1 = ($second_expected_collection_date_1 != "" && $second_expected_collection_date_1 != "0000-00-00") ? $second_expected_collection_date_1 : "";
$show_second_expected_collection_date_2 = ($second_expected_collection_date_2 != "" && $second_expected_collection_date_2 != "0000-00-00") ? $second_expected_collection_date_2 : "";
$show_expected_collection_date = "
	<div class=\"summary_line\"><span>第一次之一：</span><b>$show_first_expected_collection_date_1</b><span class=\"summary_gap\"></span><span>第一次之二：</span><b>$show_first_expected_collection_date_2</b></div>
	<div class=\"summary_line\"><span>第二次之一：</span><b>$show_second_expected_collection_date_1</b><span class=\"summary_gap\"></span><span>第二次之二：</span><b>$show_second_expected_collection_date_2</b></div>
";

function build_actual_date_input($field_name, $field_value) {
	return "
		<div class=\"input-group actual_field\" id=\"$field_name\">
			<input type=\"text\" class=\"form-control\" name=\"$field_name\" aria-describedby=\"$field_name\" value=\"$field_value\" onchange=\"setEdit();\">
			<button class=\"btn btn-outline-secondary input-group-append input-group-addon\" type=\"button\" data-target=\"#$field_name\" data-toggle=\"datetimepicker\"><i class=\"bi bi-calendar\"></i></button>
		</div>
		<script type=\"text/javascript\">
			$(function () {
				$('#$field_name').datetimepicker({
					locale: 'zh-tw'
					,format:\"YYYY-MM-DD\"
					,allowInputToggle: true
				});
			});
		</script>
	";
}

$show_actual_billing_date_inputs = "
	<div class=\"actual_grid\">
		<div><div class=\"actual_label\">第一次日期</div>".build_actual_date_input("first_actual_billing_date", $first_actual_billing_date)."</div>
		<div><div class=\"actual_label\">第一次放樣</div>".build_actual_date_input("first_layout_actual_billing_date", $first_layout_actual_billing_date)."</div>
		<div><div class=\"actual_label\">第二次日期</div>".build_actual_date_input("second_actual_billing_date", $second_actual_billing_date)."</div>
		<div><div class=\"actual_label\">第二次放樣</div>".build_actual_date_input("second_layout_actual_billing_date", $second_layout_actual_billing_date)."</div>
	</div>
";

$show_first_actual_collection_amount = number_format(floatval($first_actual_collection_amount), 2);
$show_first_layout_actual_collection_amount = number_format(floatval($first_layout_actual_collection_amount), 2);
$show_second_actual_collection_amount = number_format(floatval($second_actual_collection_amount), 2);
$show_second_layout_actual_collection_amount = number_format(floatval($second_layout_actual_collection_amount), 2);

$show_actual_collection_amount_inputs = "
	<div class=\"actual_amount_grid\">
		<div class=\"actual_amount_card\">
			<div class=\"actual_amount_card_header\"><span>第一次金額</span><span class=\"amount_lock\" title=\"系統自動計算，不可修改\"><i class=\"bi bi-lock-fill\"></i></span></div>
			<div class=\"actual_amount_value\"><span class=\"currency\">NT$</span><strong>$show_first_actual_collection_amount</strong></div>
		</div>
		<div class=\"actual_amount_card\">
			<div class=\"actual_amount_card_header\"><span>第一次放樣</span><span class=\"amount_lock\" title=\"系統自動計算，不可修改\"><i class=\"bi bi-lock-fill\"></i></span></div>
			<div class=\"actual_amount_value\"><span class=\"currency\">NT$</span><strong>$show_first_layout_actual_collection_amount</strong></div>
		</div>
		<div class=\"actual_amount_card\">
			<div class=\"actual_amount_card_header\"><span>第二次金額</span><span class=\"amount_lock\" title=\"系統自動計算，不可修改\"><i class=\"bi bi-lock-fill\"></i></span></div>
			<div class=\"actual_amount_value\"><span class=\"currency\">NT$</span><strong>$show_second_actual_collection_amount</strong></div>
		</div>
		<div class=\"actual_amount_card\">
			<div class=\"actual_amount_card_header\"><span>第二次放樣</span><span class=\"amount_lock\" title=\"系統自動計算，不可修改\"><i class=\"bi bi-lock-fill\"></i></span></div>
			<div class=\"actual_amount_value\"><span class=\"currency\">NT$</span><strong>$show_second_layout_actual_collection_amount</strong></div>
		</div>
	</div>
	<div class=\"actual_amount_hint\"><i class=\"bi bi-info-circle-fill\"></i> 存檔時依樓層類型的標準/屋突單價、放樣單價、第一次/第二次實際數量與各次扣抵款自動計算。</div>
";

$show_deduction_amount_inputs = "
	<div class=\"deduction_stage_grid\">
		<div class=\"deduction_stage_panel\">
			<div class=\"deduction_stage_title\">第一次</div>
			<div><div class=\"actual_label\">扣抵保留/租賃預收差款</div><input type=\"text\" class=\"form-control actual_field\" value=\"$show_retention_deduction_amount\" readonly></div>
			<div><div class=\"actual_label\">扣抵預收款</div><input type=\"text\" class=\"form-control actual_field\" value=\"$show_advance_payment_deduction_amount\" readonly></div>
			<div><div class=\"actual_label\">其他扣抵款</div><input type=\"text\" class=\"form-control actual_field\" name=\"first_other_deduction_amount\" value=\"$first_other_deduction_amount\" onchange=\"setEdit();\"></div>
		</div>
		<div class=\"deduction_stage_panel\">
			<div class=\"deduction_stage_title\">第二次</div>
			<div><div class=\"actual_label\">扣抵保留/租賃預收差款</div><input type=\"text\" class=\"form-control actual_field\" value=\"$show_second_retention_deduction_amount\" readonly></div>
			<div><div class=\"actual_label\">扣抵預收款</div><input type=\"text\" class=\"form-control actual_field\" value=\"$show_second_advance_payment_deduction_amount\" readonly></div>
			<div><div class=\"actual_label\">其他扣抵款</div><input type=\"text\" class=\"form-control actual_field\" name=\"second_other_deduction_amount\" value=\"$second_other_deduction_amount\" onchange=\"setEdit();\"></div>
		</div>
	</div>
	<div class=\"actual_amount_hint\"><i class=\"bi bi-info-circle-fill\"></i> 存檔時依第一次/第二次實際計價數量、樓層單價、保留款總%與預收款總%自動計算。</div>
";

$show_actual_collection_date_inputs = "
	<div class=\"actual_grid\">
		<div><div class=\"actual_label\">第一次之一</div>".build_actual_date_input("first_actual_collection_date", $first_actual_collection_date)."</div>
		<div><div class=\"actual_label\">第一次之二</div>".build_actual_date_input("first_layout_actual_collection_date", $first_layout_actual_collection_date)."</div>
		<div><div class=\"actual_label\">第二次之一</div>".build_actual_date_input("second_actual_collection_date", $second_actual_collection_date)."</div>
		<div><div class=\"actual_label\">第二次之二</div>".build_actual_date_input("second_layout_actual_collection_date", $second_layout_actual_collection_date)."</div>
	</div>
";

$mDB = "";
$mDB = new MywebDB();

//載入計價階段
$pro_id = "project_progress";

$Qry="select caption from items where pro_id = '$pro_id' order by pro_id,orderby";
$mDB->query($Qry);
$select_project_progress = "";
$select_project_progress .= "<option></option>";

if ($mDB->rowCount() > 0) {
	while ($row=$mDB->fetchRow(2)) {
		$ch_caption = $row['caption'];
		$select_project_progress .= "<option value=\"$ch_caption\" ".mySelect($ch_caption,$project_progress).">$ch_caption</option>";
	}
}

//載入收款階段
$pro_id = "payment_request_stage";
$Qry="select caption from items where pro_id = '$pro_id' order by pro_id,orderby";
$mDB->query($Qry);
$select_payment_request_stage = "";
$select_payment_request_stage .= "<option></option>";

if ($mDB->rowCount() > 0) {
	while ($row=$mDB->fetchRow(2)) {
		$ch_caption = $row['caption'];
		$select_payment_request_stage .= "<option value=\"$ch_caption\" ".mySelect($ch_caption,$payment_request_stage).">$ch_caption</option>";
	}
}

$mDB->remove();



$show_savebtn=<<<EOT
<div class="btn-group vbottom" role="group" style="margin-top:5px;">
	<button id="save" class="btn btn-primary" type="button" onclick="CheckValue(this.form);" style="padding: 5px 15px;"><i class="bi bi-check-circle"></i>&nbsp;存檔</button>
	<button id="cancel" class="btn btn-secondary display_none" type="button" onclick="setCancel();" style="padding: 5px 15px;"><i class="bi bi-x-circle"></i>&nbsp;取消</button>
	<button id="close" class="btn btn-danger" type="button" onclick="parent.buildings_sub_detail_myDraw();parent.$.fancybox.close();" style="padding: 5px 15px;"><i class="bi bi-power"></i>&nbsp;關閉</button>
</div>
EOT;


if (!($detect->isMobile() && !$detect->isTablet())) {
	$isMobile = 0;
	
$style_css=<<<EOT
<style>

.card_full {
    width: 100%;
	height: 100vh;
}

#full {
    width: 100%;
	height: 100%;
}

#info_container {
	width: 100% !Important;
	max-width: 960px !Important;
	margin: 0 auto !Important;
}

.field_div1 {width:200px;display: none;font-size:18px;color:#000;text-align:right;font-weight:700;padding:15px 10px 0 0;vertical-align: top;display:inline-block;zoom: 1;*display: inline;}
.field_div2 {width:100%;max-width:500px;display: none;font-size:18px;color:#000;text-align:left;font-weight:700;padding:8px 0 0 0;vertical-align: top;display:inline-block;zoom: 1;*display: inline;}

.modify_section {
	border-top: 1px solid #d7dee4;
	padding: 18px 0 4px 0;
	margin-top: 12px;
}

.modify_section:first-child {
	border-top: 0;
	margin-top: 0;
}

.section_title {
	font-size: 18px;
	font-weight: 700;
	color: #0b5f75;
	margin-bottom: 12px;
}

.readonly_grid,
.edit_grid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 12px 24px;
}

.readonly_item,
.edit_item {
	min-height: 52px;
}

.readonly_grid {
	gap: 6px 24px;
}

.readonly_item {
	min-height: 38px;
}

.field_label {
	font-size: 14px;
	font-weight: 700;
	color: #555;
	margin-bottom: 4px;
}

.field_value {
	min-height: 32px;
	padding: 6px 0;
	font-size: 16px;
	font-weight: 700;
	color: #111;
	word-break: break-word;
}

.readonly_item .field_label {
	margin-bottom: 1px;
}

.readonly_item .field_value {
	min-height: 24px;
	padding: 2px 0;
}

.field_value_main {
	color: #0d6efd;
}

.maxwidth {
    width: 100%;
    max-width: 250px;
}

.remark_textarea {
	width: 100%;
	min-height: 88px;
}

.summary_line {
	font-size: 13px;
	line-height: 1.7;
	white-space: nowrap;
}

.summary_line span {
	color: #475569;
}

.summary_line b {
	display: inline-block;
	min-width: 82px;
	color: #0d6efd;
	text-align: right;
}

.summary_gap {
	display: inline-block;
	width: 18px;
}

.actual_grid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 8px 12px;
}

.deduction_stage_grid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 12px;
}

.deduction_stage_panel {
	display: grid;
	gap: 8px;
	align-content: start;
}

.deduction_stage_title {
	font-size: 13px;
	font-weight: 700;
	color: #0b5f75;
	padding-bottom: 4px;
	border-bottom: 1px solid #d8e2ea;
}

.cashflow_edit_grid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 14px;
	align-items: start;
}

.cashflow_edit_panel {
	border: 1px solid #d8e2ea;
	border-radius: 8px;
	background: #fbfdff;
	padding: 12px;
	min-height: 100%;
}

.cashflow_edit_panel_wide {
	grid-column: 1 / -1;
}

.cashflow_panel_title {
	display: flex;
	align-items: center;
	gap: 8px;
	font-size: 14px;
	font-weight: 700;
	color: #0b5f75;
	margin-bottom: 10px;
}

.cashflow_panel_title i {
	color: #0d6efd;
}

.stage_grid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 12px 18px;
}

.stage_item_wide {
	grid-column: 1 / -1;
}

.actual_label {
	font-size: 12px;
	font-weight: 700;
	color: #475569;
	margin-bottom: 3px;
}

.actual_field {
	width: 100%;
	max-width: 200px;
}

.actual_amount_grid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 10px;
}

.actual_amount_card {
	padding: 10px 12px;
	border: 1px solid #d8e2ea;
	border-radius: 8px;
	background-color: #fff;
}

.actual_amount_card_header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 8px;
	margin-bottom: 8px;
	color: #475569;
	font-size: 12px;
	font-weight: 700;
}

.amount_lock {
	flex: 0 0 auto;
	color: #64748b;
	font-size: 12px;
	line-height: 1;
}

.amount_lock i {
	font-size: 12px;
}

.actual_amount_value {
	display: flex;
	align-items: baseline;
	justify-content: flex-end;
	gap: 6px;
	color: #0b5f75;
	font-variant-numeric: tabular-nums;
	white-space: nowrap;
}

.actual_amount_value .currency {
	color: #64748b;
	font-size: 11px;
	font-weight: 700;
}

.actual_amount_value strong {
	font-size: 18px;
	line-height: 1.2;
	letter-spacing: .2px;
}

.actual_amount_hint {
	margin-top: 10px;
	color: #64748b;
	font-size: 13px;
	font-weight: 600;
	line-height: 1.5;
}

</style>

EOT;

} else {
	$isMobile = 1;

$style_css=<<<EOT
<style>

.card_full {
    width: 100%;
	height: 100vh;
}

#full {
    width: 100%;
	height: 100%;
}

#info_container {
	width: 100% !Important;
	margin: 0 auto !Important;
}

.field_div1 {width:100%;display: block;font-size:18px;color:#000;text-align:left;font-weight:700;padding:15px 10px 0 0;vertical-align: top;}
.field_div2 {width:100%;display: block;font-size:18px;color:#000;text-align:left;font-weight:700;padding:8px 10px 0 0;vertical-align: top;}

.modify_section {
	border-top: 1px solid #d7dee4;
	padding: 16px 10px 4px 10px;
	margin-top: 10px;
}

.modify_section:first-child {
	border-top: 0;
	margin-top: 0;
}

.section_title {
	font-size: 18px;
	font-weight: 700;
	color: #0b5f75;
	margin-bottom: 12px;
}

.readonly_grid,
.edit_grid {
	display: grid;
	grid-template-columns: 1fr;
	gap: 10px;
}

.readonly_item,
.edit_item {
	min-height: 50px;
}

.readonly_grid {
	gap: 6px;
}

.readonly_item {
	min-height: 38px;
}

.field_label {
	font-size: 14px;
	font-weight: 700;
	color: #555;
	margin-bottom: 4px;
}

.field_value {
	min-height: 30px;
	padding: 6px 0;
	font-size: 16px;
	font-weight: 700;
	color: #111;
	word-break: break-word;
}

.readonly_item .field_label {
	margin-bottom: 1px;
}

.readonly_item .field_value {
	min-height: 24px;
	padding: 2px 0;
}

.field_value_main {
	color: #0d6efd;
}

.maxwidth {
    width: 100%;
}

.remark_textarea {
	width: 100%;
	min-height: 96px;
}

.summary_line {
	font-size: 13px;
	line-height: 1.7;
	white-space: nowrap;
}

.summary_line span {
	color: #475569;
}

.summary_line b {
	display: inline-block;
	min-width: 82px;
	color: #0d6efd;
	text-align: right;
}

.summary_gap {
	display: inline-block;
	width: 18px;
}

.actual_grid {
	display: grid;
	grid-template-columns: 1fr;
	gap: 8px;
}

.deduction_stage_grid {
	display: grid;
	grid-template-columns: 1fr;
	gap: 12px;
}

.deduction_stage_panel {
	display: grid;
	gap: 8px;
}

.deduction_stage_title {
	font-size: 13px;
	font-weight: 700;
	color: #0b5f75;
	padding-bottom: 4px;
	border-bottom: 1px solid #d8e2ea;
}

.cashflow_edit_grid {
	display: grid;
	grid-template-columns: 1fr;
	gap: 12px;
}

.cashflow_edit_panel {
	border: 1px solid #d8e2ea;
	border-radius: 8px;
	background: #fbfdff;
	padding: 12px;
}

.cashflow_panel_title {
	display: flex;
	align-items: center;
	gap: 8px;
	font-size: 14px;
	font-weight: 700;
	color: #0b5f75;
	margin-bottom: 10px;
}

.cashflow_panel_title i {
	color: #0d6efd;
}

.stage_grid {
	display: grid;
	grid-template-columns: 1fr;
	gap: 10px;
}

.actual_label {
	font-size: 12px;
	font-weight: 700;
	color: #475569;
	margin-bottom: 3px;
}

.actual_field {
	width: 100%;
}

.actual_amount_grid {
	display: grid;
	grid-template-columns: 1fr;
	gap: 8px;
}

.actual_amount_card {
	padding: 10px 12px;
	border: 1px solid #d8e2ea;
	border-radius: 8px;
	background-color: #fff;
}

.actual_amount_card_header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 8px;
	margin-bottom: 8px;
	color: #475569;
	font-size: 12px;
	font-weight: 700;
}

.amount_lock {
	flex: 0 0 auto;
	color: #64748b;
	font-size: 12px;
	line-height: 1;
}

.amount_lock i {
	font-size: 12px;
}

.actual_amount_value {
	display: flex;
	align-items: baseline;
	justify-content: flex-end;
	gap: 6px;
	color: #0b5f75;
	font-variant-numeric: tabular-nums;
	white-space: nowrap;
}

.actual_amount_value .currency {
	color: #64748b;
	font-size: 11px;
	font-weight: 700;
}

.actual_amount_value strong {
	font-size: 18px;
	line-height: 1.2;
	letter-spacing: .2px;
}

.actual_amount_hint {
	margin-top: 12px;
	color: #64748b;
	font-size: 13px;
	font-weight: 600;
	line-height: 1.5;
}

</style>
EOT;

}



$show_center=<<<EOT
$style_css
<div class="card card_full">
	<div class="card-header text-bg-info">
		<div class="size14 weight float-start" style="margin-top: 5px;">
			$mess_title
		</div>
		<div class="float-end" style="margin-top: -5px;">
			$show_savebtn
		</div>
	</div>
	<div id="full" class="card-body data-overlayscrollbars-initialize">
		<div id="info_container">
			<form method="post" id="modifyForm" name="modifyForm" enctype="multipart/form-data" action="javascript:void(null);">
				<div class="field_container3">
					<div class="modify_section">
						<div class="section_title">基本資料</div>
						<div class="readonly_grid">
							<div class="readonly_item">
								<div class="field_label">棟別</div>
								<div class="field_value field_value_main">$building</div>
							</div>
							<div class="readonly_item">
								<div class="field_label">樓層</div>
								<div class="field_value field_value_main">$floor</div>
							</div>
							<div class="readonly_item">
								<div class="field_label">預計+實際交版日期</div>
								<div class="field_value">$expected_actual_delivery_date</div>
							</div>
							<div class="readonly_item">
								<div class="field_label">預計+實際灌漿日期</div>
								<div class="field_value">$expected_actual_grouting_date</div>
							</div>
							<div class="readonly_item">
								<div class="field_label">預計收款日</div>
								<div class="field_value">$show_expected_collection_date</div>
							</div>
							<div class="readonly_item">
								<div class="field_label">實際交版日</div>
								<div class="field_value">$actual_submission_date</div>
							</div>
							<div class="readonly_item">
								<div class="field_label">實際灌漿日</div>
								<div class="field_value">$actual_grouting_date</div>
							</div>
							<div class="readonly_item">
								<div class="field_label">預計收款金額</div>
								<div class="field_value">$show_expected_collection_amount</div>
							</div>
							<div class="readonly_item">
								<div class="field_label">第一次扣抵保留/租賃預收差款</div>
								<div class="field_value">$show_retention_deduction_amount</div>
							</div>
							<div class="readonly_item">
								<div class="field_label">第一次扣抵預收款</div>
								<div class="field_value">$show_advance_payment_deduction_amount</div>
							</div>
							<div class="readonly_item">
								<div class="field_label">第一次其他扣抵款</div>
								<div class="field_value">$show_first_other_deduction_amount</div>
							</div>
							<div class="readonly_item">
								<div class="field_label">第二次扣抵保留/租賃預收差款</div>
								<div class="field_value">$show_second_retention_deduction_amount</div>
							</div>
							<div class="readonly_item">
								<div class="field_label">第二次扣抵預收款</div>
								<div class="field_value">$show_second_advance_payment_deduction_amount</div>
							</div>
							<div class="readonly_item">
								<div class="field_label">第二次其他扣抵款</div>
								<div class="field_value">$show_second_other_deduction_amount</div>
							</div>
							<div class="readonly_item">
								<div class="field_label">實際計價與放樣數量</div>
								<div class="actual_grid">
									<div>
										<div class="actual_label">第一次實際計價數量</div>
										<input type="number" class="form-control actual_field" name="first_actual_completed_qty" value="$first_actual_completed_qty" min="0" step="0.01" onchange="setEdit();" />
									</div>
									<div>
										<div class="actual_label">第一次實際放樣數量</div>
										<input type="number" class="form-control actual_field" name="first_layout_actual_completed_qty" value="$first_layout_actual_completed_qty" min="0" step="0.01" onchange="setEdit();" />
									</div>
									<div>
										<div class="actual_label">第二次實際計價數量</div>
										<input type="number" class="form-control actual_field" name="second_actual_completed_qty" value="$second_actual_completed_qty" min="0" step="0.01" onchange="setEdit();" />
									</div>
									<div>
										<div class="actual_label">第二次實際放樣數量</div>
										<input type="number" class="form-control actual_field" name="second_layout_actual_completed_qty" value="$second_layout_actual_completed_qty" min="0" step="0.01" onchange="setEdit();" />
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="modify_section">
						<div class="section_title">計價與收款</div>
						<div class="cashflow_edit_grid">
							<div class="cashflow_edit_panel">
								<div class="cashflow_panel_title"><i class="bi bi-calendar-check"></i><span>實際計價日</span></div>
								$show_actual_billing_date_inputs
							</div>
							<div class="cashflow_edit_panel">
								<div class="cashflow_panel_title"><i class="bi bi-cash-coin"></i><span>實際收款金額</span></div>
								$show_actual_collection_amount_inputs
							</div>
							<div class="cashflow_edit_panel">
								<div class="cashflow_panel_title"><i class="bi bi-dash-circle"></i><span>實際扣抵金額</span></div>
								$show_deduction_amount_inputs
							</div>
							<div class="cashflow_edit_panel">
								<div class="cashflow_panel_title"><i class="bi bi-calendar-event"></i><span>實際收款日</span></div>
								$show_actual_collection_date_inputs
							</div>
							<div class="cashflow_edit_panel">
								<div class="cashflow_panel_title"><i class="bi bi-list-check"></i><span>階段與備註</span></div>
								<div class="stage_grid">
									<div>
										<div class="field_label">計價階段</div>
										<select id="project_progress" name="project_progress" class="form-select maxwidth" placeholder="請選擇" onchange="setEdit();">
											$select_project_progress
										</select>
									</div>
									<div>
										<div class="field_label">收款階段</div>
										<select id="payment_request_stage" name="payment_request_stage" class="form-select maxwidth" placeholder="請選擇" onchange="setEdit();">
											$select_payment_request_stage
										</select>
									</div>
									<div class="stage_item_wide">
										<div class="field_label">備註</div>
										<textarea class="form-control remark_textarea" name="remark" onchange="setEdit();">$remark</textarea>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="form_btn_div mt-5">
						<input type="hidden" name="fm" value="$fm" />
						<input type="hidden" name="site_db" value="$site_db" />
						<input type="hidden" name="memberID" value="$memberID" />
						<input type="hidden" name="auto_seq" value="$auto_seq" />
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
<script>

function CheckValue(thisform) {
	xajax_processform(xajax.getFormValues('modifyForm'));
	thisform.submit();
}

function setEdit() {
	$('#close', window.document).addClass("display_none");
	$('#cancel', window.document).removeClass("display_none");
}

function setCancel() {
	$('#close', window.document).removeClass("display_none");
	$('#cancel', window.document).addClass("display_none");
	document.forms[0].reset();
}

function setSave() {
	$('#close', window.document).removeClass("display_none");
	$('#cancel', window.document).addClass("display_none");
}

</script>
EOT;

?>
