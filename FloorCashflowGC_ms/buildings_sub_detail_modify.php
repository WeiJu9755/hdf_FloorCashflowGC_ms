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
	$retention_deduction_source			= deduction_source_from_checkbox($aFormValues, 'retention_deduction_source_manual');
	$advance_payment_deduction_source	= deduction_source_from_checkbox($aFormValues, 'advance_payment_deduction_source_manual');
	$second_retention_deduction_source	= deduction_source_from_checkbox($aFormValues, 'second_retention_deduction_source_manual');
	$second_advance_payment_deduction_source = deduction_source_from_checkbox($aFormValues, 'second_advance_payment_deduction_source_manual');
	$first_actual_collection_amount_source = normalize_collection_source(isset($aFormValues['first_actual_collection_amount_source']) ? $aFormValues['first_actual_collection_amount_source'] : 'auto');
	$second_actual_collection_amount_source = normalize_collection_source(isset($aFormValues['second_actual_collection_amount_source']) ? $aFormValues['second_actual_collection_amount_source'] : 'auto');
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
		floatval($second_actual_completed_qty),
		floatval($first_layout_actual_completed_qty),
		floatval($second_layout_actual_completed_qty)
	);
	if (!$actual_deduction_result['ok']) {
		$mDB->remove();
		$error_message = addslashes($actual_deduction_result['message']);
		$objResponse->script("jAlert('警示', '$error_message', 'red', '', 2500);");
		return $objResponse;
	}

	$retention_deduction_amount = ($retention_deduction_source == 'manual')
		? normalize_amount_for_sql($aFormValues['retention_deduction_amount'])
		: $actual_deduction_result['first_retention_amount'];
	$advance_payment_deduction_amount = ($advance_payment_deduction_source == 'manual')
		? normalize_amount_for_sql($aFormValues['advance_payment_deduction_amount'])
		: $actual_deduction_result['first_advance_payment_amount'];
	$second_retention_deduction_amount = ($second_retention_deduction_source == 'manual')
		? normalize_amount_for_sql($aFormValues['second_retention_deduction_amount'])
		: $actual_deduction_result['second_retention_amount'];
	$second_advance_payment_deduction_amount = ($second_advance_payment_deduction_source == 'manual')
		? normalize_amount_for_sql($aFormValues['second_advance_payment_deduction_amount'])
		: $actual_deduction_result['second_advance_payment_amount'];

	$actual_collection_result = calculate_actual_collection_amounts(
		$mDB,
		$auto_seq,
		floatval($first_actual_completed_qty),
		floatval($second_actual_completed_qty),
		floatval($first_layout_actual_completed_qty),
		floatval($second_layout_actual_completed_qty),
		floatval($retention_deduction_amount),
		floatval($advance_payment_deduction_amount),
		floatval($first_other_deduction_amount),
		floatval($second_retention_deduction_amount),
		floatval($second_advance_payment_deduction_amount),
		floatval($second_other_deduction_amount)
	);
	if (!$actual_collection_result['ok']) {
		$mDB->remove();
		$error_message = addslashes($actual_collection_result['message']);
		$objResponse->script("jAlert('警示', '$error_message', 'red', '', 2500);");
		return $objResponse;
	}
	$first_actual_collection_amount = ($first_actual_collection_amount_source == 'manual')
		? normalize_amount_for_sql($aFormValues['first_actual_collection_amount'])
		: $actual_collection_result['parts'][0];
	$second_actual_collection_amount = ($second_actual_collection_amount_source == 'manual')
		? normalize_amount_for_sql($aFormValues['second_actual_collection_amount'])
		: $actual_collection_result['parts'][2];
	$actual_collection_amount = number_format(floatval($first_actual_collection_amount) + floatval($second_actual_collection_amount), 2, '.', '');

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
			,actual_collection_amount	= $actual_collection_amount
			,first_actual_billing_amount	= ".$actual_collection_result['first_actual_billing_amount']."
			,second_actual_billing_amount	= ".$actual_collection_result['second_actual_billing_amount']."
			,first_actual_invoice_amount	= ".$actual_collection_result['first_actual_invoice_amount']."
			,second_actual_invoice_amount	= ".$actual_collection_result['second_actual_invoice_amount']."
			,first_actual_collection_amount	= $first_actual_collection_amount
			,first_actual_collection_amount_source	= '$first_actual_collection_amount_source'
			,first_layout_actual_collection_amount	= ".$actual_collection_result['parts'][1]."
			,second_actual_collection_amount	= $second_actual_collection_amount
			,second_actual_collection_amount_source	= '$second_actual_collection_amount_source'
			,second_layout_actual_collection_amount	= ".$actual_collection_result['parts'][3]."
			,first_actual_collection_date	= '$first_actual_collection_date'
			,first_layout_actual_collection_date	= '$first_layout_actual_collection_date'
			,second_actual_collection_date	= '$second_actual_collection_date'
			,second_layout_actual_collection_date	= '$second_layout_actual_collection_date'
			,retention_deduction_amount	= $retention_deduction_amount
			,retention_deduction_source	= '$retention_deduction_source'
			,advance_payment_deduction_amount	= $advance_payment_deduction_amount
			,advance_payment_deduction_source	= '$advance_payment_deduction_source'
			,first_other_deduction_amount	= $first_other_deduction_amount
			,second_retention_deduction_amount	= $second_retention_deduction_amount
			,second_retention_deduction_source	= '$second_retention_deduction_source'
			,second_advance_payment_deduction_amount	= $second_advance_payment_deduction_amount
			,second_advance_payment_deduction_source	= '$second_advance_payment_deduction_source'
			,second_other_deduction_amount	= $second_other_deduction_amount
			,payment_request_stage	= '$payment_request_stage'
			,remark	= '$remark'
			,makeby	= '$memberID'
			,last_modify	= now()
			where auto_seq = '$auto_seq'";
			
	$mDB->query($Qry);
	$mDB->remove();

	$objResponse->script("setSave();");
	$objResponse->script("showSaveNotice('已存檔!');");
	$objResponse->script("$('#first_actual_billing_amount_preview').val('".number_format(floatval($actual_collection_result['first_actual_billing_amount']), 2)."');");
	$objResponse->script("$('#second_actual_billing_amount_preview').val('".number_format(floatval($actual_collection_result['second_actual_billing_amount']), 2)."');");
	$objResponse->script("$('#first_actual_invoice_amount_preview').val('".number_format(floatval($actual_collection_result['first_actual_invoice_amount']), 2)."');");
	$objResponse->script("$('#second_actual_invoice_amount_preview').val('".number_format(floatval($actual_collection_result['second_actual_invoice_amount']), 2)."');");
	$objResponse->script("$('#first_actual_collection_amount').val('".number_format(floatval($first_actual_collection_amount), 2, '.', '')."');");
	$objResponse->script("$('#second_actual_collection_amount').val('".number_format(floatval($second_actual_collection_amount), 2, '.', '')."');");
	$objResponse->script("$('#retention_deduction_amount').val('".number_format(floatval($retention_deduction_amount), 2, '.', '')."');");
	$objResponse->script("$('#advance_payment_deduction_amount').val('".number_format(floatval($advance_payment_deduction_amount), 2, '.', '')."');");
	$objResponse->script("$('#second_retention_deduction_amount').val('".number_format(floatval($second_retention_deduction_amount), 2, '.', '')."');");
	$objResponse->script("$('#second_advance_payment_deduction_amount').val('".number_format(floatval($second_advance_payment_deduction_amount), 2, '.', '')."');");
	$objResponse->script("commitDeductionDefaults();");
	$objResponse->script("commitCollectionDefaults();");
	$objResponse->script("if (parent && typeof parent.buildings_sub_detail_myDraw == 'function') { parent.buildings_sub_detail_myDraw(); }");
		
	
	return $objResponse;
}

function normalize_amount_for_sql($value) {
	$value = str_replace(",", "", trim((string)$value));
	if ($value == "" || !is_numeric($value)) {
		return "0";
	}
	return (string)floatval($value);
}

function normalize_deduction_source($value) {
	return (strtolower(trim((string)$value)) == 'manual') ? 'manual' : 'auto';
}

function normalize_collection_source($value) {
	return (strtolower(trim((string)$value)) == 'manual') ? 'manual' : 'auto';
}

function deduction_source_from_checkbox($form_values, $field_name) {
	return (isset($form_values[$field_name]) && (string)$form_values[$field_name] == '1') ? 'manual' : 'auto';
}

/**
 * 實際計價與扣抵款均依案件原稅別保存；開票與收款時計為含稅金額後再扣抵，避免含稅減未稅。
 */
function calculate_actual_collection_amounts($mDB, $auto_seq, $first_actual_completed_qty, $second_actual_completed_qty, $first_layout_actual_completed_qty, $second_layout_actual_completed_qty, $first_retention_amount, $first_advance_payment_amount, $first_other_deduction_amount, $second_retention_amount, $second_advance_payment_amount, $second_other_deduction_amount) {
	$auto_seq_sql = addslashes($auto_seq);
	$Qry="SELECT
			 a.floor,
			 b.std_layer_floor,
			 b.roof_protrusion_floor,
			 c.std_floor_u_price,
			 c.roof_protrusion_u_price,
			 c.layout_std_floor_u_price,
			 c.square_roof_protrusion_u_price,
			 c.tax_excluded
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
	$first_retention_amount = floatval($first_retention_amount);
	$first_advance_payment_amount = floatval($first_advance_payment_amount);
	$first_other_deduction_amount = floatval($first_other_deduction_amount);
	$second_retention_amount = floatval($second_retention_amount);
	$second_advance_payment_amount = floatval($second_advance_payment_amount);
	$second_other_deduction_amount = floatval($second_other_deduction_amount);
	$tax_multiplier = actual_collection_is_tax_excluded($row['tax_excluded']) ? 1.05 : 1;
	// 實際金額與所有扣抵款維持案件原稅別：未稅案件即為未稅，含稅案件即為含稅。
	$first_actual_billing_amount = round(($unit_price * $first_qty) + ($layout_unit_price * $first_layout_qty), 2);
	$second_actual_billing_amount = round(($unit_price * $second_qty) + ($layout_unit_price * $second_layout_qty), 2);

	// 實開發票與實際收款一律以含稅金額計算，未稅案件在此才加計營業稅。
	$first_actual_invoice_amount = round(
		actual_collection_tax_included_amount($first_actual_billing_amount, $tax_multiplier)
		- actual_collection_tax_included_amount($first_advance_payment_amount, $tax_multiplier)
		- actual_collection_tax_included_amount($first_other_deduction_amount, $tax_multiplier),
		2
	);
	$second_actual_invoice_amount = round(
		actual_collection_tax_included_amount($second_actual_billing_amount, $tax_multiplier)
		- actual_collection_tax_included_amount($second_advance_payment_amount, $tax_multiplier)
		- actual_collection_tax_included_amount($second_other_deduction_amount, $tax_multiplier),
		2
	);
	$parts = array(
		round($first_actual_invoice_amount - actual_collection_tax_included_amount($first_retention_amount, $tax_multiplier), 2),
		0,
		round($second_actual_invoice_amount - actual_collection_tax_included_amount($second_retention_amount, $tax_multiplier), 2),
		0
	);
	$total_amount = round(array_sum($parts), 2);

	return array(
		'ok' => true,
		'total_amount' => number_format($total_amount, 2, '.', ''),
		'first_actual_billing_amount' => number_format($first_actual_billing_amount, 2, '.', ''),
		'second_actual_billing_amount' => number_format($second_actual_billing_amount, 2, '.', ''),
		'first_actual_invoice_amount' => number_format($first_actual_invoice_amount, 2, '.', ''),
		'second_actual_invoice_amount' => number_format($second_actual_invoice_amount, 2, '.', ''),
		'parts' => array(
			number_format($parts[0], 2, '.', ''),
			number_format($parts[1], 2, '.', ''),
			number_format($parts[2], 2, '.', ''),
			number_format($parts[3], 2, '.', '')
		)
	);
}

function actual_collection_tax_included_amount($amount, $tax_multiplier) {
	return round(floatval($amount) * floatval($tax_multiplier), 2);
}

/**
 * 判斷案件原始計價金額是否為未稅；未稅案件僅在開票、收款時計入 5% 營業稅。
 */
function actual_collection_is_tax_excluded($value) {
	$value = strtoupper(trim((string)$value));
	return in_array($value, array("1", "Y", "YES", "ON", "TRUE", "T", "是", "有", "勾選", "未稅"), true);
}

/**
 * 依樓層單價、放樣單價與第一次/第二次實際數量，自動計算實際扣抵保留款與預收款。
 */
function calculate_actual_deduction_amounts($mDB, $auto_seq, $first_actual_completed_qty, $second_actual_completed_qty, $first_layout_actual_completed_qty, $second_layout_actual_completed_qty) {
	$auto_seq_sql = addslashes($auto_seq);
	$Qry="SELECT
			 a.case_id,
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
		return array('ok' => false, 'message' => '找不到樓層資料，無法計算實際扣抵金額。');
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
		return array('ok' => false, 'message' => '此樓層不在標準層或屋突層設定範圍內，無法判斷實際扣抵單價。');
	}

	$case_id_sql = addslashes($row['case_id']);
	$retention_rate = actual_deduction_case_percentage_sum($mDB, "retention", $case_id_sql) / 100;
	$advance_payment_rate = actual_deduction_case_percentage_sum($mDB, "advance_payment", $case_id_sql) / 100;
	$first_base_amount = round((max(0, floatval($first_actual_completed_qty)) * $unit_price) + (max(0, floatval($first_layout_actual_completed_qty)) * $layout_unit_price), 2);
	$second_base_amount = round((max(0, floatval($second_actual_completed_qty)) * $unit_price) + (max(0, floatval($second_layout_actual_completed_qty)) * $layout_unit_price), 2);

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
$first_expected_work_qty = "";
$first_layout_expected_work_qty = "";
$second_expected_work_qty = "";
$second_layout_expected_work_qty = "";
$first_expected_collection_amount = "";
$first_layout_expected_collection_amount = "";
$second_expected_collection_amount = "";
$second_layout_expected_collection_amount = "";
$retention_deduction_amount = "";
$retention_deduction_source = "auto";
$advance_payment_deduction_amount = "";
$advance_payment_deduction_source = "auto";
$first_other_deduction_amount = "";
$second_retention_deduction_amount = "";
$second_retention_deduction_source = "auto";
$second_advance_payment_deduction_amount = "";
$second_advance_payment_deduction_source = "auto";
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
$first_actual_billing_amount = "";
$second_actual_billing_amount = "";
$first_actual_invoice_amount = "";
$second_actual_invoice_amount = "";
$actual_collection_amount = "";
$first_actual_collection_amount = "";
$first_actual_collection_amount_source = "auto";
$first_layout_actual_collection_amount = "";
$second_actual_collection_amount = "";
$second_actual_collection_amount_source = "auto";
$second_layout_actual_collection_amount = "";
$actual_collection_date = "";
$first_actual_collection_date = "";
$first_layout_actual_collection_date = "";
$second_actual_collection_date = "";
$second_layout_actual_collection_date = "";
$payment_request_stage = "";
$remark = "";
$tax_excluded = "";

$Qry="SELECT a.*, c.tax_excluded
FROM buildings_sub_detail a
LEFT JOIN CaseManagement c ON c.case_id = a.case_id
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
	$first_expected_work_qty = $row['first_expected_work_qty'];
	$first_layout_expected_work_qty = $row['first_layout_expected_work_qty'];
	$second_expected_work_qty = $row['second_expected_work_qty'];
	$second_layout_expected_work_qty = $row['second_layout_expected_work_qty'];
	$first_expected_collection_amount = $row['first_expected_collection_amount'];
	$first_layout_expected_collection_amount = $row['first_layout_expected_collection_amount'];
	$second_expected_collection_amount = $row['second_expected_collection_amount'];
	$second_layout_expected_collection_amount = $row['second_layout_expected_collection_amount'];
	$retention_deduction_amount = $row['retention_deduction_amount'];
	$retention_deduction_source = normalize_deduction_source($row['retention_deduction_source']);
	$advance_payment_deduction_amount = $row['advance_payment_deduction_amount'];
	$advance_payment_deduction_source = normalize_deduction_source($row['advance_payment_deduction_source']);
	$first_other_deduction_amount = $row['first_other_deduction_amount'];
	$second_retention_deduction_amount = $row['second_retention_deduction_amount'];
	$second_retention_deduction_source = normalize_deduction_source($row['second_retention_deduction_source']);
	$second_advance_payment_deduction_amount = $row['second_advance_payment_deduction_amount'];
	$second_advance_payment_deduction_source = normalize_deduction_source($row['second_advance_payment_deduction_source']);
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
	$first_actual_billing_amount = $row['first_actual_billing_amount'];
	$second_actual_billing_amount = $row['second_actual_billing_amount'];
	$first_actual_invoice_amount = $row['first_actual_invoice_amount'];
	$second_actual_invoice_amount = $row['second_actual_invoice_amount'];
	$actual_collection_amount = $row['actual_collection_amount'];
	$first_actual_collection_amount = $row['first_actual_collection_amount'];
	$first_actual_collection_amount_source = normalize_collection_source($row['first_actual_collection_amount_source']);
	$first_layout_actual_collection_amount = $row['first_layout_actual_collection_amount'];
	$second_actual_collection_amount = $row['second_actual_collection_amount'];
	$second_actual_collection_amount_source = normalize_collection_source($row['second_actual_collection_amount_source']);
	$second_layout_actual_collection_amount = $row['second_layout_actual_collection_amount'];
	$actual_collection_date = $row['actual_collection_date'];
	$first_actual_collection_date = $row['first_actual_collection_date'];
	$first_layout_actual_collection_date = $row['first_layout_actual_collection_date'];
	$second_actual_collection_date = $row['second_actual_collection_date'];
	$second_layout_actual_collection_date = $row['second_layout_actual_collection_date'];
	$payment_request_stage = $row['payment_request_stage'];
	$remark = $row['remark'];
	$tax_excluded = $row['tax_excluded'];

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

if ($first_actual_completed_qty != "" || $second_actual_completed_qty != "") {
	$preview_collection_result = calculate_actual_collection_amounts(
		$mDB,
		$auto_seq,
		floatval($first_actual_completed_qty),
		floatval($second_actual_completed_qty),
		floatval($first_layout_actual_completed_qty),
		floatval($second_layout_actual_completed_qty),
		floatval($retention_deduction_amount),
		floatval($advance_payment_deduction_amount),
		floatval($first_other_deduction_amount),
		floatval($second_retention_deduction_amount),
		floatval($second_advance_payment_deduction_amount),
		floatval($second_other_deduction_amount)
	);
	if ($preview_collection_result['ok']) {
		// 顯示值一律依目前案件稅別重算，避免案件稅別異動後仍顯示舊的含／未稅結果。
		$first_actual_billing_amount = $preview_collection_result['first_actual_billing_amount'];
		$second_actual_billing_amount = $preview_collection_result['second_actual_billing_amount'];
		$first_actual_invoice_amount = $preview_collection_result['first_actual_invoice_amount'];
		$second_actual_invoice_amount = $preview_collection_result['second_actual_invoice_amount'];
		if ($first_actual_collection_amount_source == 'auto') {
			$first_actual_collection_amount = $preview_collection_result['parts'][0];
			$first_layout_actual_collection_amount = 0;
		}
		if ($second_actual_collection_amount_source == 'auto') {
			$second_actual_collection_amount = $preview_collection_result['parts'][2];
			$second_layout_actual_collection_amount = 0;
		}
	}
}

$mDB->remove();

$show_first_expected_collection_amount = ($first_expected_collection_amount != "" && $first_expected_collection_amount != 0) ? number_format($first_expected_collection_amount, 2) : "";
$show_first_layout_expected_collection_amount = ($first_layout_expected_collection_amount != "" && $first_layout_expected_collection_amount != 0) ? number_format($first_layout_expected_collection_amount, 2) : "";
$show_second_expected_collection_amount = ($second_expected_collection_amount != "" && $second_expected_collection_amount != 0) ? number_format($second_expected_collection_amount, 2) : "";
$show_second_layout_expected_collection_amount = ($second_layout_expected_collection_amount != "" && $second_layout_expected_collection_amount != 0) ? number_format($second_layout_expected_collection_amount, 2) : "";
$show_first_expected_work_qty = ($first_expected_work_qty !== "" && $first_expected_work_qty !== null) ? number_format($first_expected_work_qty, 2) : "";
$show_first_layout_expected_work_qty = ($first_layout_expected_work_qty !== "" && $first_layout_expected_work_qty !== null) ? number_format($first_layout_expected_work_qty, 2) : "";
$show_second_expected_work_qty = ($second_expected_work_qty !== "" && $second_expected_work_qty !== null) ? number_format($second_expected_work_qty, 2) : "";
$show_second_layout_expected_work_qty = ($second_layout_expected_work_qty !== "" && $second_layout_expected_work_qty !== null) ? number_format($second_layout_expected_work_qty, 2) : "";
$show_retention_deduction_amount = ($retention_deduction_amount != "" && $retention_deduction_amount != 0) ? number_format($retention_deduction_amount, 2) : "";
$show_advance_payment_deduction_amount = ($advance_payment_deduction_amount != "" && $advance_payment_deduction_amount != 0) ? number_format($advance_payment_deduction_amount, 2) : "";
$show_first_other_deduction_amount = ($first_other_deduction_amount != "" && $first_other_deduction_amount != 0) ? number_format($first_other_deduction_amount, 2) : "";
$show_second_retention_deduction_amount = ($second_retention_deduction_amount != "" && $second_retention_deduction_amount != 0) ? number_format($second_retention_deduction_amount, 2) : "";
$show_second_advance_payment_deduction_amount = ($second_advance_payment_deduction_amount != "" && $second_advance_payment_deduction_amount != 0) ? number_format($second_advance_payment_deduction_amount, 2) : "";
$show_second_other_deduction_amount = ($second_other_deduction_amount != "" && $second_other_deduction_amount != 0) ? number_format($second_other_deduction_amount, 2) : "";
$show_first_actual_billing_amount = ($first_actual_billing_amount != "" && $first_actual_billing_amount != 0) ? number_format($first_actual_billing_amount, 2) : "";
$show_second_actual_billing_amount = ($second_actual_billing_amount != "" && $second_actual_billing_amount != 0) ? number_format($second_actual_billing_amount, 2) : "";
$show_first_actual_invoice_amount = number_format(floatval($first_actual_invoice_amount), 2);
$show_second_actual_invoice_amount = number_format(floatval($second_actual_invoice_amount), 2);
$actual_amount_tax_label = actual_collection_is_tax_excluded($tax_excluded) ? "未稅" : "含稅";
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

function build_deduction_amount_input($amount_name, $source_name, $amount_value, $source_value) {
	$source_value = normalize_deduction_source($source_value);
	$manual_checked = ($source_value == 'manual') ? ' checked' : '';
	$readonly = ($source_value == 'auto') ? ' readonly' : '';
	$amount_value = number_format(floatval($amount_value), 2, '.', '');
	return "
		<div class=\"deduction_input_row\">
			<input type=\"number\" class=\"form-control actual_field deduction_amount_input\" id=\"$amount_name\" name=\"$amount_name\" value=\"$amount_value\" step=\"0.01\"$readonly onchange=\"setEdit();\">
			<label class=\"deduction_manual_toggle\" title=\"勾選後保留手動輸入金額，不再自動覆蓋\">
				<input type=\"checkbox\" class=\"form-check-input deduction_manual_checkbox\" name=\"{$source_name}_manual\" value=\"1\" data-amount-target=\"$amount_name\"$manual_checked onchange=\"changeDeductionSource(this);\">
				<span>手動</span>
			</label>
		</div>
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

$show_first_actual_collection_amount = number_format(floatval($first_actual_collection_amount) + floatval($first_layout_actual_collection_amount), 2);
$show_second_actual_collection_amount = number_format(floatval($second_actual_collection_amount) + floatval($second_layout_actual_collection_amount), 2);
$first_collection_source_label = ($first_actual_collection_amount_source == 'manual') ? '手動' : '自動';
$second_collection_source_label = ($second_actual_collection_amount_source == 'manual') ? '手動' : '自動';

$show_actual_invoice_amount_inputs = "
	<div class=\"actual_amount_grid\">
		<div class=\"actual_amount_card\">
			<div class=\"actual_amount_card_header\"><span>第一次金額</span><span class=\"amount_lock\" title=\"系統自動計算，不可修改\"><i class=\"bi bi-lock-fill\"></i></span></div>
			<input type=\"text\" class=\"form-control actual_field\" id=\"first_actual_invoice_amount_preview\" value=\"$show_first_actual_invoice_amount\" readonly>
		</div>
		<div class=\"actual_amount_card\">
			<div class=\"actual_amount_card_header\"><span>第二次金額</span><span class=\"amount_lock\" title=\"系統自動計算，不可修改\"><i class=\"bi bi-lock-fill\"></i></span></div>
			<input type=\"text\" class=\"form-control actual_field\" id=\"second_actual_invoice_amount_preview\" value=\"$show_second_actual_invoice_amount\" readonly>
		</div>
	</div>
	<div class=\"actual_amount_hint\"><i class=\"bi bi-info-circle-fill\"></i> 實開發票金額以含稅金額計算：實際金額(含稅) − 扣抵預收款(含稅) − 其他扣抵款(含稅)。</div>
";

$show_actual_collection_amount_inputs = "
	<div class=\"actual_amount_grid\">
		<div class=\"actual_amount_card\">
			<div class=\"actual_amount_card_header\"><span>第一次金額</span><span class=\"collection_source_badge\" id=\"first_actual_collection_amount_source_label\">$first_collection_source_label</span></div>
			<div class=\"collection_amount_input_row\">
				<input type=\"number\" class=\"form-control actual_field collection_amount_input\" id=\"first_actual_collection_amount\" name=\"first_actual_collection_amount\" value=\"".number_format(floatval($first_actual_collection_amount) + floatval($first_layout_actual_collection_amount), 2, '.', '')."\" step=\"0.01\" data-source-target=\"first_actual_collection_amount_source\" oninput=\"markCollectionManual(this);\" onchange=\"setEdit();\">
				<button type=\"button\" class=\"btn btn-outline-secondary btn-sm\" onclick=\"resetCollectionAuto('first_actual_collection_amount');\">恢復自動</button>
			</div>
			<input type=\"hidden\" id=\"first_actual_collection_amount_source\" name=\"first_actual_collection_amount_source\" value=\"$first_actual_collection_amount_source\">
		</div>
		<div class=\"actual_amount_card\">
			<div class=\"actual_amount_card_header\"><span>第二次金額</span><span class=\"collection_source_badge\" id=\"second_actual_collection_amount_source_label\">$second_collection_source_label</span></div>
			<div class=\"collection_amount_input_row\">
				<input type=\"number\" class=\"form-control actual_field collection_amount_input\" id=\"second_actual_collection_amount\" name=\"second_actual_collection_amount\" value=\"".number_format(floatval($second_actual_collection_amount) + floatval($second_layout_actual_collection_amount), 2, '.', '')."\" step=\"0.01\" data-source-target=\"second_actual_collection_amount_source\" oninput=\"markCollectionManual(this);\" onchange=\"setEdit();\">
				<button type=\"button\" class=\"btn btn-outline-secondary btn-sm\" onclick=\"resetCollectionAuto('second_actual_collection_amount');\">恢復自動</button>
			</div>
			<input type=\"hidden\" id=\"second_actual_collection_amount_source\" name=\"second_actual_collection_amount_source\" value=\"$second_actual_collection_amount_source\">
		</div>
	</div>
	<div class=\"actual_amount_hint\"><i class=\"bi bi-info-circle-fill\"></i> 預設以含稅金額依公式自動計算；手動修改後會保留輸入值，不再受自動公式影響。</div>
";

$show_deduction_amount_inputs = "
	<div class=\"deduction_stage_grid\">
		<div class=\"deduction_stage_panel\">
			<div class=\"deduction_stage_title\">第一次</div>
			<div><div class=\"actual_label\">扣抵保留/租賃預收差款</div>".build_deduction_amount_input("retention_deduction_amount", "retention_deduction_source", $retention_deduction_amount, $retention_deduction_source)."</div>
			<div><div class=\"actual_label\">扣抵預收款</div>".build_deduction_amount_input("advance_payment_deduction_amount", "advance_payment_deduction_source", $advance_payment_deduction_amount, $advance_payment_deduction_source)."</div>
			<div><div class=\"actual_label\">其他扣抵款</div><input type=\"text\" class=\"form-control actual_field\" name=\"first_other_deduction_amount\" value=\"$first_other_deduction_amount\" onchange=\"setEdit();\"></div>
		</div>
		<div class=\"deduction_stage_panel\">
			<div class=\"deduction_stage_title\">第二次</div>
			<div><div class=\"actual_label\">扣抵保留/租賃預收差款</div>".build_deduction_amount_input("second_retention_deduction_amount", "second_retention_deduction_source", $second_retention_deduction_amount, $second_retention_deduction_source)."</div>
			<div><div class=\"actual_label\">扣抵預收款</div>".build_deduction_amount_input("second_advance_payment_deduction_amount", "second_advance_payment_deduction_source", $second_advance_payment_deduction_amount, $second_advance_payment_deduction_source)."</div>
			<div><div class=\"actual_label\">其他扣抵款</div><input type=\"text\" class=\"form-control actual_field\" name=\"second_other_deduction_amount\" value=\"$second_other_deduction_amount\" onchange=\"setEdit();\"></div>
		</div>
	</div>
	<div class=\"actual_amount_hint\"><i class=\"bi bi-info-circle-fill\"></i> 所有扣抵款均以案件原稅別輸入及保存；未勾選「手動」會在存檔時自動重算。</div>
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
	<button id="save" class="btn btn-primary" type="button" onclick="CheckValue();" style="padding: 5px 15px;"><i class="bi bi-check-circle"></i>&nbsp;存檔</button>
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

.basic_section_title {
	font-size: 26px;
	margin-bottom: 18px;
}

.basic_overview_grid {
	display: grid;
	grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
	grid-template-rows: repeat(6, auto);
	column-gap: 24px;
	row-gap: 0;
}

.basic_overview_column {
	display: grid;
	grid-template-rows: subgrid;
	grid-row: 1 / span 6;
	align-content: start;
}

.basic_overview_column:first-child {
	grid-column: 1;
}

.basic_overview_column:last-child {
	grid-column: 2;
}

.basic_overview_section {
	padding: 12px 14px;
	border-bottom: 1px solid #e2e8f0;
}

.basic_overview_section:last-child {
	border-bottom: 0;
}

.basic_overview_section.basic_overview_no_bottom_border {
	border-bottom: 0;
}

.basic_overview_heading {
	padding-bottom: 6px;
	font-size: 15px;
	font-weight: 700;
	color: #0b5f75;
}

.basic_deduction_list {
	display: grid;
	gap: 8px;
}

.basic_actual_qty {
	margin-top: 14px;
	padding: 12px 14px;
	border: 1px solid #d8e2ea;
	border-radius: 10px;
	background: #fbfdff;
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

.overview_pair_grid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 8px 16px;
}

.overview_pair_item .actual_label {
	margin-bottom: 1px;
}

.overview_pair_value {
	min-height: 24px;
	padding: 2px 0;
	font-size: 16px;
	font-weight: 700;
	color: #111;
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

.basic_overview_section .summary_line {
	display: grid;
	grid-template-columns: max-content minmax(0, 1fr) max-content minmax(0, 1fr);
	gap: 4px 8px;
	white-space: normal;
}

.basic_overview_section .summary_line b {
	min-width: 0;
}

.basic_overview_section .summary_gap {
	display: none;
}

.actual_grid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 8px 12px;
}

.actual_qty_grid {
	grid-template-columns: 1fr 1fr 1fr;
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

.deduction_input_row {
	display: grid;
	grid-template-columns: minmax(120px, 1fr) auto;
	gap: 6px;
	align-items: center;
}

.deduction_manual_toggle {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	margin: 0;
	white-space: nowrap;
	color: #475569;
	font-size: 13px;
	font-weight: 600;
	cursor: pointer;
}

.deduction_amount_input[readonly] {
	background-color: #eef2f6;
	color: #64748b;
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

.cashflow_panel_title_collection_date {
	margin-top: 22px;
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

.collection_amount_input_row {
	display: grid;
	grid-template-columns: minmax(0, 1fr);
	gap: 6px;
	align-items: center;
}

.collection_amount_input_row .btn {
	width: auto;
	justify-self: end;
	white-space: nowrap;
}

.collection_source_badge {
	padding: 2px 7px;
	border-radius: 999px;
	background: #e0f2fe;
	color: #0369a1;
	font-size: 11px;
}

.collection_source_badge.is_manual {
	background: #fef3c7;
	color: #92400e;
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

.basic_section_title {
	font-size: 23px;
	margin-bottom: 16px;
}

.basic_overview_grid {
	display: grid;
	grid-template-columns: 1fr;
	gap: 14px;
}

.basic_overview_column {
	display: grid;
	grid-template-columns: 1fr;
}

.basic_overview_section {
	padding: 11px 12px;
	border-bottom: 1px solid #e2e8f0;
}

.basic_overview_section:last-child {
	border-bottom: 0;
}

.basic_overview_section.basic_overview_no_bottom_border {
	border-bottom: 0;
}

.basic_overview_heading {
	padding-bottom: 5px;
	font-size: 15px;
	font-weight: 700;
	color: #0b5f75;
}

.basic_deduction_list {
	display: grid;
	gap: 8px;
}

.basic_actual_qty {
	margin-top: 14px;
	padding: 11px 12px;
	border: 1px solid #d8e2ea;
	border-radius: 10px;
	background: #fbfdff;
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

.overview_pair_grid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 8px 12px;
}

.overview_pair_item .actual_label {
	margin-bottom: 1px;
}

.overview_pair_value {
	min-height: 24px;
	padding: 2px 0;
	font-size: 16px;
	font-weight: 700;
	color: #111;
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

.basic_overview_section .summary_line {
	display: grid;
	grid-template-columns: max-content minmax(0, 1fr) max-content minmax(0, 1fr);
	gap: 4px 6px;
	white-space: normal;
}

.basic_overview_section .summary_line b {
	min-width: 0;
}

.basic_overview_section .summary_gap {
	display: none;
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

.deduction_input_row {
	display: grid;
	grid-template-columns: minmax(0, 1fr) auto;
	gap: 6px;
	align-items: center;
}

.deduction_manual_toggle {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	margin: 0;
	white-space: nowrap;
	color: #475569;
	font-size: 13px;
	font-weight: 600;
	cursor: pointer;
}

.deduction_amount_input[readonly] {
	background-color: #eef2f6;
	color: #64748b;
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

.collection_amount_input_row {
	display: grid;
	grid-template-columns: minmax(0, 1fr);
	gap: 6px;
	align-items: center;
}

.collection_amount_input_row .btn {
	width: auto;
	justify-self: end;
	white-space: nowrap;
}

.collection_source_badge {
	padding: 2px 7px;
	border-radius: 999px;
	background: #e0f2fe;
	color: #0369a1;
	font-size: 11px;
}

.collection_source_badge.is_manual {
	background: #fef3c7;
	color: #92400e;
}

</style>
EOT;

}

$actual_tax_theme_class = actual_collection_is_tax_excluded($tax_excluded) ? "tax_theme_untaxed" : "tax_theme_taxed";
$tax_theme_css=<<<EOT
<style>
.tax_theme_scope.tax_theme_taxed {
	--tax-theme-primary: #0b5f75;
	--tax-theme-accent: #0d6efd;
	--tax-theme-header: #0e7490;
	--tax-theme-surface: #f0f9ff;
	--tax-theme-border: #bae6fd;
	--tax-theme-muted: #e0f2fe;
	--tax-theme-soft-text: #0369a1;
	--tax-theme-action: #0d6efd;
	--tax-theme-action-hover: #0b5ed7;
}

.tax_theme_scope.tax_theme_untaxed {
	--tax-theme-primary: #9a3412;
	--tax-theme-accent: #ea580c;
	--tax-theme-header: #b45309;
	--tax-theme-surface: #fff7ed;
	--tax-theme-border: #fed7aa;
	--tax-theme-muted: #ffedd5;
	--tax-theme-soft-text: #9a3412;
	--tax-theme-action: #15803d;
	--tax-theme-action-hover: #166534;
}

/* 未稅案件中，含稅結果區塊仍沿用含稅介面的藍色系。 */
.tax_theme_scope.tax_theme_untaxed .tax_included_amount_panel {
	--tax-theme-primary: #0b5f75;
	--tax-theme-accent: #0d6efd;
	--tax-theme-surface: #f0f9ff;
	--tax-theme-border: #bae6fd;
	--tax-theme-muted: #e0f2fe;
	--tax-theme-soft-text: #0369a1;
}

.tax_theme_scope > .card-header {
	background-color: var(--tax-theme-header) !important;
	border-color: var(--tax-theme-header) !important;
	color: #fff !important;
}

.tax_theme_scope .tax_theme_badge {
	display: inline-flex;
	align-items: center;
	margin-left: 8px;
	padding: 2px 8px;
	border: 1px solid rgba(255, 255, 255, .55);
	border-radius: 999px;
	background: rgba(255, 255, 255, .18);
	color: #fff;
	font-size: 11px;
	line-height: 1.4;
}

.tax_theme_scope .modify_section {
	border-color: var(--tax-theme-border);
}

.tax_theme_scope .section_title,
.tax_theme_scope .basic_overview_heading,
.tax_theme_scope .deduction_stage_title,
.tax_theme_scope .cashflow_panel_title,
.tax_theme_scope .actual_amount_value {
	color: var(--tax-theme-primary);
}

.tax_theme_scope .field_value_main,
.tax_theme_scope .summary_line b,
.tax_theme_scope .cashflow_panel_title i {
	color: var(--tax-theme-accent);
}

.tax_theme_scope .basic_actual_qty,
.tax_theme_scope .cashflow_edit_panel {
	border-color: var(--tax-theme-border);
	background: var(--tax-theme-surface);
}

.tax_theme_scope .basic_overview_section,
.tax_theme_scope .deduction_stage_title,
.tax_theme_scope .actual_amount_card {
	border-color: var(--tax-theme-border);
}

.tax_theme_scope .deduction_amount_input[readonly] {
	background-color: var(--tax-theme-muted);
}

.tax_theme_scope .collection_source_badge {
	background: var(--tax-theme-muted);
	color: var(--tax-theme-soft-text);
}

.tax_theme_scope .collection_source_badge.is_manual {
	background: #fee2e2;
	color: #991b1b;
}

.tax_theme_scope #save {
	background-color: var(--tax-theme-action);
	border-color: var(--tax-theme-action);
	color: #fff;
}

.tax_theme_scope #save:hover,
.tax_theme_scope #save:focus,
.tax_theme_scope #save:active {
	background-color: var(--tax-theme-action-hover);
	border-color: var(--tax-theme-action-hover);
	color: #fff;
}

.tax_theme_scope .form-control:focus,
.tax_theme_scope .form-select:focus {
	border-color: var(--tax-theme-accent);
}
</style>
EOT;



$show_center=<<<EOT
$style_css
$tax_theme_css
<div class="card card_full tax_theme_scope $actual_tax_theme_class">
	<div class="card-header text-bg-info">
		<div class="size14 weight float-start" style="margin-top: 5px;">
			$mess_title<span class="tax_theme_badge">$actual_amount_tax_label</span>
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
						<div class="section_title basic_section_title">基本資料</div>
						<div class="basic_overview_grid">
							<div class="basic_overview_column">
								<div class="basic_overview_section">
									<div class="field_label">棟別</div>
									<div class="field_value field_value_main">$building</div>
								</div>
								<div class="basic_overview_section overview_pair_grid">
									<div class="overview_pair_item">
										<div class="actual_label">預計+實際交版日期</div>
										<div class="overview_pair_value">$expected_actual_delivery_date</div>
									</div>
									<div class="overview_pair_item">
										<div class="actual_label">預計+實際灌漿日期</div>
										<div class="overview_pair_value">$expected_actual_grouting_date</div>
									</div>
								</div>
								<div class="basic_overview_section overview_pair_grid">
									<div class="overview_pair_item">
										<div class="actual_label">實際交版日</div>
										<div class="overview_pair_value">$actual_submission_date</div>
									</div>
									<div class="overview_pair_item">
										<div class="actual_label">實際灌漿日</div>
										<div class="overview_pair_value">$actual_grouting_date</div>
									</div>
								</div>
								<div class="basic_overview_section basic_overview_heading">預計收款日</div>
								<div class="basic_overview_section field_value">$show_expected_collection_date</div>
								<div class="basic_overview_section basic_deduction_list">
									<div><div class="field_label">第一次扣抵保留/租賃預收差款</div><div class="field_value">$show_retention_deduction_amount</div></div>
									<div><div class="field_label">第一次扣抵預收款</div><div class="field_value">$show_advance_payment_deduction_amount</div></div>
									<div><div class="field_label">第一次其他扣抵款</div><div class="field_value">$show_first_other_deduction_amount</div></div>
								</div>
							</div>
							<div class="basic_overview_column">
								<div class="basic_overview_section">
									<div class="field_label">樓層</div>
									<div class="field_value field_value_main">$floor</div>
								</div>
								<div class="basic_overview_section basic_overview_heading basic_overview_no_bottom_border">預計計價與放樣數量</div>
								<div class="basic_overview_section overview_pair_grid">
									<div class="overview_pair_item"><div class="actual_label">第一次預計計價數量</div><div class="overview_pair_value">$show_first_expected_work_qty</div></div>
									<div class="overview_pair_item"><div class="actual_label">第一次預計放樣數量</div><div class="overview_pair_value">$show_first_layout_expected_work_qty</div></div>
									<div class="overview_pair_item"><div class="actual_label">第二次預計計價數量</div><div class="overview_pair_value">$show_second_expected_work_qty</div></div>
									<div class="overview_pair_item"><div class="actual_label">第二次預計放樣數量</div><div class="overview_pair_value">$show_second_layout_expected_work_qty</div></div>
								</div>
								<div class="basic_overview_section basic_overview_heading">預計收款金額</div>
								<div class="basic_overview_section field_value">$show_expected_collection_amount</div>
								<div class="basic_overview_section basic_deduction_list">
									<div><div class="field_label">第二次扣抵保留/租賃預收差款</div><div class="field_value">$show_second_retention_deduction_amount</div></div>
									<div><div class="field_label">第二次扣抵預收款</div><div class="field_value">$show_second_advance_payment_deduction_amount</div></div>
									<div><div class="field_label">第二次其他扣抵款</div><div class="field_value">$show_second_other_deduction_amount</div></div>
								</div>
							</div>
						</div>
						<div class="basic_actual_qty readonly_item">
							<div class="field_label">實際計價與放樣數量</div>
							<div class="actual_grid actual_qty_grid">
									<div>
										<div class="actual_label">第一次實際計價數量</div>
										<input type="number" class="form-control actual_field" name="first_actual_completed_qty" value="$first_actual_completed_qty" min="0" step="0.01" onchange="setEdit();" />
									</div>
									<div>
										<div class="actual_label">第一次實際放樣數量</div>
										<input type="number" class="form-control actual_field" name="first_layout_actual_completed_qty" value="$first_layout_actual_completed_qty" min="0" step="0.01" onchange="setEdit();" />
									</div>
									<div>
										<div class="actual_label">第一次實際金額($actual_amount_tax_label)</div>
										<input type="text" class="form-control actual_field" id="first_actual_billing_amount_preview" value="$show_first_actual_billing_amount" readonly />
									</div>
									<div>
										<div class="actual_label">第二次實際計價數量</div>
										<input type="number" class="form-control actual_field" name="second_actual_completed_qty" value="$second_actual_completed_qty" min="0" step="0.01" onchange="setEdit();" />
									</div>
									<div>
										<div class="actual_label">第二次實際放樣數量</div>
										<input type="number" class="form-control actual_field" name="second_layout_actual_completed_qty" value="$second_layout_actual_completed_qty" min="0" step="0.01" onchange="setEdit();" />
									</div>
									<div>
										<div class="actual_label">第二次實際金額($actual_amount_tax_label)</div>
										<input type="text" class="form-control actual_field" id="second_actual_billing_amount_preview" value="$show_second_actual_billing_amount" readonly />
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
								<div class="cashflow_panel_title cashflow_panel_title_collection_date"><i class="bi bi-calendar-event"></i><span>實際收款日</span></div>
								$show_actual_collection_date_inputs
							</div>
							<div class="cashflow_edit_panel">
								<div class="cashflow_panel_title"><i class="bi bi-dash-circle"></i><span>實際扣抵金額($actual_amount_tax_label)</span></div>
								$show_deduction_amount_inputs
							</div>
							<div class="cashflow_edit_panel tax_included_amount_panel">
								<div class="cashflow_panel_title"><i class="bi bi-receipt"></i><span>實開發票金額(含稅)</span></div>
								$show_actual_invoice_amount_inputs
							</div>
							<div class="cashflow_edit_panel tax_included_amount_panel">
								<div class="cashflow_panel_title"><i class="bi bi-cash-coin"></i><span>實際收款金額(含稅)</span></div>
								$show_actual_collection_amount_inputs
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

function CheckValue() {
	xajax_processform(xajax.getFormValues('modifyForm'));
}

function changeDeductionSource(sourceCheckbox, markEdited) {
	var amountTarget = sourceCheckbox.getAttribute('data-amount-target');
	var amountInput = document.getElementById(amountTarget);
	if (amountInput) {
		amountInput.readOnly = !sourceCheckbox.checked;
	}
	if (markEdited !== false) {
		setEdit();
	}
}

function refreshDeductionSources() {
	$('.deduction_manual_checkbox', window.document).each(function() {
		changeDeductionSource(this, false);
	});
}

function commitDeductionDefaults() {
	$('.deduction_amount_input', window.document).each(function() {
		this.defaultValue = this.value;
	});
	$('.deduction_manual_checkbox', window.document).each(function() {
		this.defaultChecked = this.checked;
	});
}

function setCollectionSourceState(amountId, sourceValue) {
	var sourceInput = document.getElementById(amountId + '_source');
	var sourceLabel = document.getElementById(amountId + '_source_label');
	if (sourceInput) {
		sourceInput.value = sourceValue;
	}
	if (sourceLabel) {
		sourceLabel.textContent = (sourceValue == 'manual') ? '手動' : '自動';
		sourceLabel.classList.toggle('is_manual', sourceValue == 'manual');
	}
}

function markCollectionManual(amountInput) {
	setCollectionSourceState(amountInput.id, 'manual');
	setEdit();
}

function resetCollectionAuto(amountId) {
	setCollectionSourceState(amountId, 'auto');
	setEdit();
}

function refreshCollectionSources() {
	$('.collection_amount_input', window.document).each(function() {
		var sourceInput = document.getElementById(this.id + '_source');
		setCollectionSourceState(this.id, sourceInput && sourceInput.value == 'manual' ? 'manual' : 'auto');
	});
}

function commitCollectionDefaults() {
	$('.collection_amount_input', window.document).each(function() {
		this.defaultValue = this.value;
		var sourceInput = document.getElementById(this.id + '_source');
		if (sourceInput) {
			sourceInput.defaultValue = sourceInput.value;
		}
	});
	refreshCollectionSources();
}

function showSaveNotice(message) {
	var noticeWindow = window;
	var noticeDocument = window.document;
	var noticeJquery = window.jQuery || window.$;

	if (!noticeJquery) {
		alert(message);
		return;
	}

	var notice = noticeJquery('#save_notice', noticeDocument);
	if (notice.length == 0) {
		notice = noticeJquery('<div id="save_notice"><span class="save_notice_icon"><i class="bi bi-check-lg"></i></span><span class="save_notice_text"></span></div>').css({
			position: 'fixed',
			top: '18px',
			right: '20px',
			zIndex: 2147483647,
			display: 'none',
			alignItems: 'center',
			gap: '10px',
			minWidth: '168px',
			maxWidth: '320px',
			padding: '12px 16px 12px 12px',
			borderRadius: '8px',
			border: '1px solid #bbf7d0',
			borderLeft: '5px solid #16a34a',
			background: '#f0fdf4',
			color: '#14532d',
			fontSize: '14px',
			fontWeight: 700,
			lineHeight: 1.35,
			boxShadow: '0 14px 30px rgba(15, 23, 42, .18)',
			transform: 'translateY(-8px)',
			opacity: 0
		});
		notice.find('.save_notice_icon').css({
			width: '26px',
			height: '26px',
			borderRadius: '50%',
			display: 'inline-flex',
			alignItems: 'center',
			justifyContent: 'center',
			flex: '0 0 26px',
			background: '#16a34a',
			color: '#fff',
			fontSize: '16px'
		});
		notice.find('.save_notice_text').css({
			display: 'block',
			overflowWrap: 'break-word'
		});
		noticeJquery(noticeDocument.body).append(notice);
	}
	notice.find('.save_notice_text').text(message);
	notice.stop(true, true)
		.css({ display: 'flex', opacity: 0, transform: 'translateY(-8px)' })
		.animate({ opacity: 1 }, {
			duration: 140,
			step: function(now) {
				var offset = -8 + (8 * now);
				notice.css('transform', 'translateY(' + offset + 'px)');
			}
		})
		.delay(1400)
		.animate({ opacity: 0 }, {
			duration: 260,
			step: function(now) {
				var offset = -8 + (8 * now);
				notice.css('transform', 'translateY(' + offset + 'px)');
			},
			complete: function() {
				notice.hide();
			}
		});

	if (noticeWindow.art && noticeWindow.art.dialog && typeof noticeWindow.art.dialog.tips == 'function') {
		noticeWindow.art.dialog.tips(message, 1);
		return;
	}
	if (parent && parent.art && parent.art.dialog && typeof parent.art.dialog.tips == 'function') {
		parent.art.dialog.tips(message, 1);
	}
}

function setEdit() {
	$('#close', window.document).addClass("display_none");
	$('#cancel', window.document).removeClass("display_none");
}

function setCancel() {
	$('#close', window.document).removeClass("display_none");
	$('#cancel', window.document).addClass("display_none");
	document.forms[0].reset();
	refreshDeductionSources();
	refreshCollectionSources();
}

function setSave() {
	$('#close', window.document).removeClass("display_none");
	$('#cancel', window.document).addClass("display_none");
}

$(function() {
	refreshCollectionSources();
});

</script>
EOT;

?>
