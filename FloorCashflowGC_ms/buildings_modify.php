<?php

//error_reporting(E_ALL); 
//ini_set('display_errors', '1');

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


$xajax->registerFunction("DeleteRow");
/**
 * 刪除指定的樓層明細資料，並重新整理樓層明細列表。
 */
function DeleteRow($auto_seq){

	$objResponse = new xajaxResponse();
	
	$mDB = "";
	$mDB = new MywebDB();
	updateFloorCashflowGCModifyLogByDetailSeq($mDB, $auto_seq, $_SESSION['memberID']);

	//刪除主資料
	$Qry="delete from buildings_sub_detail where auto_seq = '$auto_seq'";
	$mDB->query($Qry);
	
	$mDB->remove();
	
    $objResponse->script("oTable = $('#buildings_sub_detail_table').dataTable();oTable.fnDraw(false)");
	$objResponse->script("autoclose('提示', '資料已刪除！', 500);");

	return $objResponse;
	
}


$xajax->registerFunction("returnValue");
/**
 * 依放樣廠商代號查出廠商名稱，回填到指定畫面欄位。
 */
function returnValue($auto_seq,$layout){
	$objResponse = new xajaxResponse();

	$mDB = "";
	$mDB = new MywebDB();
	
	//放樣
	$Qry="SELECT subcontractor_name FROM subcontractor WHERE subcontractor_id = '$layout'";
	$mDB->query($Qry);
	if ($mDB->rowCount() > 0) {
		$row=$mDB->fetchRow(2);
		$layout_name = $row['subcontractor_name'];
	}
	//$show_layout_name = "<div class=\"size12\">".$layout_name."</div><div class=\"size08\">".$layout."</div>";
	$show_layout_name = $layout_name;
	$objResponse->assign("layout".$auto_seq,"innerHTML",$show_layout_name);	

	$mDB->remove();
	

    return $objResponse;
	
}

$xajax->registerFunction("buildings_sub_DeleteRow");
/**
 * 刪除指定的棟別資料，並重新整理棟別列表。
 */
function buildings_sub_DeleteRow($auto_seq){

	$objResponse = new xajaxResponse();

	$mDB = "";
	$mDB = new MywebDB();
	updateFloorCashflowGCModifyLogByBuildingSeq($mDB, $auto_seq, $_SESSION['memberID']);

	//刪除主資料
	$Qry="DELETE FROM buildings_sub WHERE auto_seq = '$auto_seq'";
	$mDB->query($Qry);
	
	$mDB->remove();
	
    $objResponse->script("oTable = $('#buildings_sub_table').dataTable();oTable.fnDraw(false)");
	$objResponse->script("autoclose('提示', '資料已刪除！', 1500);");

	return $objResponse;
	
}

$xajax->registerFunction("apply_expected_work_qty");
/**
 * 依案件計價設定、棟別樓層範圍與單價，批次套用預計施作數量、扣抵金額、預計收款金額與預計收款日。
 */
function apply_expected_work_qty($case_id,$memberID){

	$objResponse = new xajaxResponse();

	$case_id = trim($case_id);
	$memberID = trim($memberID);
	$case_id_sql = addslashes($case_id);
	$memberID_sql = addslashes($memberID);

	$mDB = "";
	$mDB = new MywebDB();




	$Qry="SELECT * FROM CasePricingGC_sub WHERE case_id = '$case_id_sql'";
	$mDB->query($Qry);
	$pricing_count = $mDB->rowCount();
	if ($pricing_count == 0) {
		$mDB->remove();
		$objResponse->script("jAlert('警示', '缺少計價設定，無法套用預計施作數量，請至該工程案的「 案件計價維護-上包 」設定', 'red', '', 2500);");
		return $objResponse;
	}
	if ($pricing_count > 1) {
		$mDB->remove();
		$objResponse->script("jAlert('警示', '同案件設定不唯一，請先確認計價設定。', 'red', '', 2500);");
		return $objResponse;
	}

	$pricing_row = $mDB->fetchRow(2);
	$first_rate = floatval($pricing_row['first_percentage']) / 100;
	$second_rate = floatval($pricing_row['second_percentage']) / 100;

	$retention_rate = get_case_retention_percentage_sum($mDB, $case_id_sql) / 100;
	$advance_payment_rate = get_case_percentage_sum($mDB, "advance_payment", $case_id_sql) / 100;

	$Qry="SELECT std_floor_u_price,roof_protrusion_u_price,layout_std_floor_u_price,square_roof_protrusion_u_price,gc_price_base_date,tax_excluded FROM CaseManagement WHERE case_id = '$case_id_sql'";
	$mDB->query($Qry);
	if ($mDB->rowCount() == 0) {
		$mDB->remove();
		$objResponse->script("jAlert('警示', '找不到案件單價資料，無法套用預計收款金額，請至該工程案的「 案件計價維護-上包 」設定', 'red', '', 2500);");
		return $objResponse;
	}
	$case_row = $mDB->fetchRow(2);
	$std_floor_u_price = floatval($case_row['std_floor_u_price']);
	$roof_protrusion_u_price = floatval($case_row['roof_protrusion_u_price']);
	$layout_std_floor_u_price = floatval($case_row['layout_std_floor_u_price']);
	$square_roof_protrusion_u_price = floatval($case_row['square_roof_protrusion_u_price']);
	$gc_price_base_date = $case_row['gc_price_base_date'];
	$tax_multiplier = is_tax_excluded_checked($case_row['tax_excluded']) ? 1.05 : 1;

	$Qry="SELECT * FROM buildings_sub WHERE case_id = '$case_id_sql' ORDER BY auto_seq";
	$mDB->query($Qry);

	$buildings = array();
	while ($row=$mDB->fetchRow(2)) {
		$buildings[] = $row;
	}

	$updated_count = 0;
	$warning_list = array();

	for ($i = 0; $i < count($buildings); $i++) {
		$building_row = $buildings[$i];
		$building = $building_row['building'];
		$building_sql = addslashes($building);

		$std_floors = parse_floor_range_list($building_row['std_layer_floor']);
		$roof_floors = parse_floor_range_list($building_row['roof_protrusion_floor']);

		if (trim($building_row['std_layer_floor']) == "") {
			$warning_list[] = $building." 標準層範圍未設定";
		} else if (count($std_floors) == 0) {
			$warning_list[] = $building." 標準層範圍無法解析";
		}
		if (trim($building_row['roof_protrusion_floor']) == "") {
			$warning_list[] = $building." 屋突層範圍未設定";
		} else if (count($roof_floors) == 0) {
			$warning_list[] = $building." 屋突層範圍無法解析";
		}

		$std_count = count($std_floors);
		$roof_count = count($roof_floors);

		$std_qty = floatval($building_row['std_layer_qty']);
		$std_layout_qty = floatval($building_row['layout_std_layer_qty']);
		$roof_qty = floatval($building_row['roof_protrusion_qty']);
		$roof_layout_qty = floatval($building_row['layout_roof_protrusion_qty']);

		$Qry="SELECT auto_seq,floor,expected_actual_delivery_date,expected_actual_grouting_date FROM buildings_sub_detail WHERE case_id = '$case_id_sql' AND building = '$building_sql'";
		$mDB->query($Qry);

		$details = array();
		while ($row=$mDB->fetchRow(2)) {
			$details[] = $row;
		}
		usort($details, "compare_detail_floor_order");

		for ($j = 0; $j < count($details); $j++) {
			$detail_row = $details[$j];
			$floor_key = parse_single_floor_key($detail_row['floor']);
			if ($floor_key == "") {
				continue;
			}

			$first_expected_work_qty = "NULL";
			$first_layout_expected_work_qty = "NULL";
			$second_expected_work_qty = "NULL";
			$second_layout_expected_work_qty = "NULL";
			$first_expected_collection_amount = "NULL";
			$first_layout_expected_collection_amount = "NULL";
			$second_expected_collection_amount = "NULL";
			$second_layout_expected_collection_amount = "NULL";
			$retention_deduction_amount = "NULL";
			$advance_payment_deduction_amount = "NULL";
			$first_expected_collection_date_1 = "NULL";
			$first_expected_collection_date_2 = "NULL";
			$second_expected_collection_date_1 = "NULL";
			$second_expected_collection_date_2 = "NULL";

			if ($std_count > 0 && isset($std_floors[$floor_key])) {
				$work_qty = $std_qty / $std_count;
				$layout_work_qty = $std_layout_qty / $std_count;
				$unit_price = $std_floor_u_price;
				$layout_unit_price = $layout_std_floor_u_price;
			} else if ($roof_count > 0 && isset($roof_floors[$floor_key])) {
				$work_qty = $roof_qty / $roof_count;
				$layout_work_qty = $roof_layout_qty / $roof_count;
				$unit_price = $roof_protrusion_u_price;
				$layout_unit_price = $square_roof_protrusion_u_price;
			} else {
				continue;
			}

			$first_expected_work_qty = round($work_qty * $first_rate, 2);
			$first_layout_expected_work_qty = round($layout_work_qty * $first_rate, 2);
			$second_expected_work_qty = round($work_qty * $second_rate, 2);
			$second_layout_expected_work_qty = round($layout_work_qty * $second_rate, 2);

			$base_collection_amount = round((($unit_price * $work_qty) + ($layout_unit_price * $layout_work_qty)) * $tax_multiplier, 2);
			$retention_deduction_amount = round($base_collection_amount * $retention_rate, 2);
			$advance_payment_deduction_amount = round($base_collection_amount * $advance_payment_rate, 2);
			$net_collection_amount = $base_collection_amount - $retention_deduction_amount - $advance_payment_deduction_amount;

			$collection_parts = allocate_expected_collection_amounts(
				$net_collection_amount,
				array(
					$unit_price * $work_qty * $first_rate,
					$layout_unit_price * $layout_work_qty * $first_rate,
					$unit_price * $work_qty * $second_rate,
					$layout_unit_price * $layout_work_qty * $second_rate
				)
			);
			$first_expected_collection_amount = $collection_parts[0];
			$first_layout_expected_collection_amount = $collection_parts[1];
			$second_expected_collection_amount = $collection_parts[2];
			$second_layout_expected_collection_amount = $collection_parts[3];

			$next_expected_actual_grouting_date = "";
			if (isset($details[$j + 1])) {
				$next_expected_actual_grouting_date = $details[$j + 1]['expected_actual_grouting_date'];
			}

			$expected_collection_dates = calculate_floor_expected_collection_dates(
				$pricing_row,
				$gc_price_base_date,
				$detail_row['expected_actual_delivery_date'],
				$detail_row['expected_actual_grouting_date'],
				$next_expected_actual_grouting_date,
				$building,
				$detail_row['floor'],
				$warning_list
			);
			$first_expected_collection_date_1 = sql_date_value($expected_collection_dates[0]);
			$first_expected_collection_date_2 = sql_date_value($expected_collection_dates[1]);
			$second_expected_collection_date_1 = sql_date_value($expected_collection_dates[2]);
			$second_expected_collection_date_2 = sql_date_value($expected_collection_dates[3]);

			$detail_auto_seq = $detail_row['auto_seq'];
			$Qry="UPDATE buildings_sub_detail SET
				 first_expected_work_qty = $first_expected_work_qty
				,first_layout_expected_work_qty = $first_layout_expected_work_qty
				,second_expected_work_qty = $second_expected_work_qty
				,second_layout_expected_work_qty = $second_layout_expected_work_qty
				,first_expected_collection_amount = $first_expected_collection_amount
				,first_layout_expected_collection_amount = $first_layout_expected_collection_amount
				,second_expected_collection_amount = $second_expected_collection_amount
				,second_layout_expected_collection_amount = $second_layout_expected_collection_amount
				,retention_deduction_amount = $retention_deduction_amount
				,advance_payment_deduction_amount = $advance_payment_deduction_amount
				,first_expected_collection_date_1 = $first_expected_collection_date_1
				,first_expected_collection_date_2 = $first_expected_collection_date_2
				,second_expected_collection_date_1 = $second_expected_collection_date_1
				,second_expected_collection_date_2 = $second_expected_collection_date_2
				,makeby = '$memberID_sql'
				,last_modify = now()
				WHERE auto_seq = '$detail_auto_seq'";
			$mDB->query($Qry);
			$updated_count++;
		}
	}

	if ($updated_count > 0) {
		updateFloorCashflowGCModifyLog($mDB, $case_id, $memberID);
	}

	$mDB->remove();

	$warning_text = "";
	if (count($warning_list) > 0) {
		$warning_text = "\\n提醒：".implode("、", array_unique($warning_list));
	}
	$tip_text = addslashes("已套用預計施作數量、收款金額與收款日：".$updated_count." 筆".$warning_text);

	$objResponse->script("buildings_sub_myDraw();");
	$objResponse->script("if (typeof buildings_sub_detail_myDraw == 'function') { buildings_sub_detail_myDraw(); }");
	$objResponse->script("art.dialog.tips('".$tip_text."',2);");

	return $objResponse;
}

/**
 * 判斷案件的稅別欄位是否代表未稅，未稅時預計金額需乘上稅率。
 */
function is_tax_excluded_checked($value) {
	$value = strtoupper(trim((string)$value));
	return in_array($value, array("1", "Y", "YES", "ON", "TRUE", "T", "是", "有", "勾選", "未稅"), true);
}

/**
 * 加總指定案件新版 retention 所有期數的保留款佔比。
 */
function get_case_retention_percentage_sum($mDB, $case_id_sql) {
	$Qry="SELECT COALESCE(SUM(percentage), 0) AS percentage_total FROM retention WHERE case_id = '$case_id_sql'";
	$mDB->query($Qry);
	if ($mDB->rowCount() == 0) {
		return 0;
	}

	$row = $mDB->fetchRow(2);
	return floatval($row['percentage_total']);
}

/**
 * 加總指定案件在百分比資料表中的佔比。
 */
function get_case_percentage_sum($mDB, $table_name, $case_id_sql) {
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

/**
 * 將扣抵後的預計收款淨額依四個原始權重分配，並用最後一格吸收四捨五入差額。
 */
function allocate_expected_collection_amounts($net_amount, $weights) {
	$total_weight = 0;
	for ($i = 0; $i < count($weights); $i++) {
		$weights[$i] = floatval($weights[$i]);
		$total_weight += $weights[$i];
	}

	if ($total_weight == 0) {
		return array("NULL", "NULL", "NULL", "NULL");
	}

	$parts = array();
	$allocated_amount = 0;
	for ($i = 0; $i < count($weights); $i++) {
		if ($i == count($weights) - 1) {
			$parts[$i] = round($net_amount - $allocated_amount, 2);
		} else {
			$parts[$i] = round($net_amount * ($weights[$i] / $total_weight), 2);
			$allocated_amount += $parts[$i];
		}
	}

	return $parts;
}



/**
 * 檢查字串是否為可用的 YYYY-MM-DD 日期，並排除空值與 0000-00-00。
 */
function is_valid_floorcashflow_date($date) {
	if ($date == "" || $date == "0000-00-00") {
		return false;
	}
	$date = substr($date, 0, 10);
	$d = DateTime::createFromFormat("Y-m-d", $date);
	return $d && $d->format("Y-m-d") === $date;
}

/**
 * 若日期落在週末，順延到下一個星期一。
 */
function move_weekend_to_next_monday($date) {
	if (!is_valid_floorcashflow_date($date)) {
		return "";
	}
	$d = new DateTime(substr($date, 0, 10));
	$week = intval($d->format("N"));
	if ($week == 6) {
		$d->modify("+2 day");
	} else if ($week == 7) {
		$d->modify("+1 day");
	}
	return $d->format("Y-m-d");
}

/**
 * 將「當月、次月、次次月、次次次月」轉為月份位移數。
 */
function get_month_offset($monthText) {
	switch (trim((string)$monthText)) {
		case "當月":
			return 0;
		case "次月":
			return 1;
		case "次次月":
			return 2;
		case "次次次月":
			return 3;
		default:
			return 0;
	}
}

/**
 * 從「10日」這類付款日文字取出日期數字。
 */
function get_day_number($dayText) {
	return intval(str_replace("日", "", trim((string)$dayText)));
}

/**
 * 依基準日期、截止月份與截止日計算該月的截止日期。
 */
function get_cutoff_date($baseDate, $cutoffMonth, $deadline) {
	if (!is_valid_floorcashflow_date($baseDate)) {
		return null;
	}

	$monthOffset = get_month_offset($cutoffMonth);
	$day = get_day_number($deadline);
	if ($day <= 0) {
		$day = 1;
	}

	$date = new DateTime(substr($baseDate, 0, 10));
	$date->modify("first day of this month");
	$date->modify("+{$monthOffset} month");

	$year = $date->format("Y");
	$month = $date->format("m");
	$lastDay = intval(date("t", strtotime("$year-$month-01")));
	if ($day > $lastDay) {
		$day = $lastDay;
	}

	return new DateTime("$year-$month-" . str_pad($day, 2, "0", STR_PAD_LEFT));
}

/**
 * 依基準日期、付款月份、付款日與加計天數算出預計收款日。
 */
function get_payment_date($baseDate, $paymentMonths, $paymentDate, $paymentDays = 0) {
	if (!is_valid_floorcashflow_date($baseDate)) {
		return "";
	}

	$monthOffset = get_month_offset($paymentMonths);
	$day = get_day_number($paymentDate);
	if ($day <= 0) {
		$day = 1;
	}

	$date = new DateTime(substr($baseDate, 0, 10));
	$date->modify("first day of this month");
	$date->modify("+{$monthOffset} month");

	$year = $date->format("Y");
	$month = $date->format("m");
	$lastDay = intval(date("t", strtotime("$year-$month-01")));
	if ($day > $lastDay) {
		$day = $lastDay;
	}

	$result = new DateTime("$year-$month-" . str_pad($day, 2, "0", STR_PAD_LEFT));
	if (intval($paymentDays) != 0) {
		$result->modify("+" . intval($paymentDays) . " day");
	}

	return $result->format("Y-m-d");
}

/**
 * 依收款條件的 A/B 截止規則，計算單一階段的預計收款日期。
 */
function calculate_expected_receiving_payment_date(
	$completion_date,
	$cutoff_month_a,
	$deadline_a,
	$payment_months_a,
	$payment_date_a,
	$payment_days_a,
	$cutoff_month_b = "",
	$deadline_b = "",
	$payment_months_b = "",
	$payment_date_b = "",
	$payment_days_b = 0
) {
	if (!is_valid_floorcashflow_date($completion_date)) {
		return "";
	}

	$baseDate = new DateTime(substr($completion_date, 0, 10));
	$cutoffDateA = get_cutoff_date($completion_date, $cutoff_month_a, $deadline_a);
	if (!$cutoffDateA) {
		return "";
	}

	$hasB = (trim((string)$cutoff_month_b) != "" && trim((string)$deadline_b) != "");
	if (!$hasB) {
		if ($baseDate <= $cutoffDateA) {
			return get_payment_date($completion_date, $payment_months_a, $payment_date_a, $payment_days_a);
		}
		$nextBaseDate = new DateTime(substr($completion_date, 0, 10));
		$nextBaseDate->modify("first day of next month");
		return get_payment_date($nextBaseDate->format("Y-m-d"), $payment_months_a, $payment_date_a, $payment_days_a);
	}

	$cutoffDateB = get_cutoff_date($completion_date, $cutoff_month_b, $deadline_b);
	if (!$cutoffDateB) {
		return "";
	}

	if ($baseDate <= $cutoffDateA) {
		return get_payment_date($completion_date, $payment_months_a, $payment_date_a, $payment_days_a);
	}
	if ($baseDate <= $cutoffDateB) {
		return get_payment_date($completion_date, $payment_months_b, $payment_date_b, $payment_days_b);
	}

	$nextBaseDate = new DateTime(substr($completion_date, 0, 10));
	$nextBaseDate->modify("first day of next month");
	return get_payment_date($nextBaseDate->format("Y-m-d"), $payment_months_a, $payment_date_a, $payment_days_a);
}

/**
 * 正規化案件的計價日基準文字，讓後續可穩定判斷交版日、灌漿日、清運日等關鍵字。
 */
function normalize_gc_price_base_date($base_date) {
	$normalized_base_date = str_replace(
		array(" ", "　", "+", "＋", "(", ")", "（", "）", "-", "－"),
		"",
		trim((string)$base_date)
	);

	return str_replace(
		array("兩", "二", "佔"),
		array("2", "2", "占"),
		$normalized_base_date
	);
}

/**
 * 依欄位前綴與序號讀取 CasePricingGC_sub 設定，計算對應階段的預計收款日。
 */
function calculate_expected_receiving_payment_date_by_prefix($completion_date, $row, $prefix, $seq) {
	$date = calculate_expected_receiving_payment_date(
		$completion_date,
		isset($row[$prefix . "_cutoff_month_" . $seq . "a"]) ? $row[$prefix . "_cutoff_month_" . $seq . "a"] : "",
		isset($row[$prefix . "_deadline_" . $seq . "a"]) ? $row[$prefix . "_deadline_" . $seq . "a"] : "",
		isset($row[$prefix . "_payment_months_" . $seq . "a"]) ? $row[$prefix . "_payment_months_" . $seq . "a"] : "",
		isset($row[$prefix . "_payment_date_" . $seq . "a"]) ? $row[$prefix . "_payment_date_" . $seq . "a"] : "",
		isset($row[$prefix . "_payment_days_" . $seq . "a"]) ? $row[$prefix . "_payment_days_" . $seq . "a"] : 0,
		isset($row[$prefix . "_cutoff_month_" . $seq . "b"]) ? $row[$prefix . "_cutoff_month_" . $seq . "b"] : "",
		isset($row[$prefix . "_deadline_" . $seq . "b"]) ? $row[$prefix . "_deadline_" . $seq . "b"] : "",
		isset($row[$prefix . "_payment_months_" . $seq . "b"]) ? $row[$prefix . "_payment_months_" . $seq . "b"] : "",
		isset($row[$prefix . "_payment_date_" . $seq . "b"]) ? $row[$prefix . "_payment_date_" . $seq . "b"] : "",
		isset($row[$prefix . "_payment_days_" . $seq . "b"]) ? $row[$prefix . "_payment_days_" . $seq . "b"] : 0
	);

	return move_weekend_to_next_monday($date);
}

/**
 * 依樓層的交版日、灌漿日與案件計價日基準，計算四個預計收款日。
 */
function calculate_floor_expected_collection_dates($pricing_row, $gc_price_base_date, $delivery_date, $grouting_date, $next_grouting_date, $building, $floor, &$warning_list) {
	$normalized_base_date = normalize_gc_price_base_date($gc_price_base_date);
	$has_delivery_date = strpos($normalized_base_date, "交版日") !== false;
	$has_grouting_date = strpos($normalized_base_date, "灌漿日") !== false;
	$has_cleaning_date = strpos($normalized_base_date, "清運日") !== false;
	$has_next_floor_grouting = strpos($normalized_base_date, "下一層灌漿日") !== false;
	$is_grouting_cleaning = (strpos($normalized_base_date, "灌漿日清運日") !== false && $has_next_floor_grouting);
	$is_two_ratio = (strpos($normalized_base_date, "2次占比") !== false || strpos($normalized_base_date, "分2次占比") !== false);
	$use_two_ratio = $is_two_ratio || $is_grouting_cleaning;

	$dates = array("", "", "", "");
	$delivery_base = is_valid_floorcashflow_date($delivery_date) ? substr($delivery_date, 0, 10) : "";
	$grouting_base = is_valid_floorcashflow_date($grouting_date) ? substr($grouting_date, 0, 10) : "";
	$next_grouting_base = is_valid_floorcashflow_date($next_grouting_date) ? substr($next_grouting_date, 0, 10) : "";
	$use_next_base = ($has_cleaning_date || $has_next_floor_grouting);

	if ($use_next_base && $next_grouting_base == "") {
		$warning_list[] = $building." ".$floor." 缺少下一層灌漿日";
	}

	if ($use_next_base && $next_grouting_base != "") {
		$dates[0] = calculate_expected_receiving_payment_date_by_prefix($next_grouting_base, $pricing_row, "fp", 1);
		$dates[1] = calculate_expected_receiving_payment_date_by_prefix($next_grouting_base, $pricing_row, "fp", 2);
		$dates[2] = calculate_expected_receiving_payment_date_by_prefix($next_grouting_base, $pricing_row, "sp", 1);
		$dates[3] = calculate_expected_receiving_payment_date_by_prefix($next_grouting_base, $pricing_row, "sp", 2);
		return $dates;
	}

	if (($use_two_ratio || $has_delivery_date) && $delivery_base != "") {
		$dates[0] = calculate_expected_receiving_payment_date_by_prefix($delivery_base, $pricing_row, "fp", 1);
		$dates[1] = calculate_expected_receiving_payment_date_by_prefix($delivery_base, $pricing_row, "fp", 2);
	} else if ($has_delivery_date) {
		$warning_list[] = $building." ".$floor." 缺少交版日";
	}

	if (($use_two_ratio || $has_grouting_date) && $grouting_base != "") {
		$dates[2] = calculate_expected_receiving_payment_date_by_prefix($grouting_base, $pricing_row, "sp", 1);
		$dates[3] = calculate_expected_receiving_payment_date_by_prefix($grouting_base, $pricing_row, "sp", 2);
	} else if ($has_grouting_date) {
		$warning_list[] = $building." ".$floor." 缺少灌漿日";
	}

	if (!$use_next_base && !$has_delivery_date && !$has_grouting_date && !$use_two_ratio) {
		$warning_list[] = $building." ".$floor." 無法判斷上包計價日基準";
	}

	return $dates;
}

/**
 * 將有效日期轉為 SQL 日期值，無效日期回傳 NULL。
 */
function sql_date_value($date) {
	if (!is_valid_floorcashflow_date($date)) {
		return "NULL";
	}
	return "'" . addslashes(substr($date, 0, 10)) . "'";
}

/**
 * 解析樓層範圍文字，支援逗號分隔與連續區間，回傳可快速查找的樓層 key 清單。
 */
function parse_floor_range_list($floor_text) {
	$result = array();
	$floor_text = trim($floor_text);
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
			$start = parse_floor_code($range_parts[0]);
			$end = parse_floor_code($range_parts[1]);
			if ($start === false || $end === false || $start['prefix'] != $end['prefix']) {
				return array();
			}
			$from = min($start['number'], $end['number']);
			$to = max($start['number'], $end['number']);
			for ($floor_no = $from; $floor_no <= $to; $floor_no++) {
				$result[$start['prefix'].":".$floor_no] = true;
			}
		} else {
			$single = parse_floor_code($part);
			if ($single === false) {
				return array();
			}
			$result[$single['prefix'].":".$single['number']] = true;
		}
	}

	return $result;
}

/**
 * 解析單一樓層文字並轉為樓層 key。
 */
function parse_single_floor_key($floor_text) {
	$floor = parse_floor_code($floor_text);
	if ($floor === false) {
		return "";
	}
	return $floor['prefix'].":".$floor['number'];
}

/**
 * 將樓層文字轉為排序用數值，地下層在前、一般樓層其次、屋突層最後。
 */
function get_floor_sort_value($floor_text) {
	$floor = parse_floor_code($floor_text);
	if ($floor === false) {
		return 999999;
	}
	if ($floor['prefix'] == "R") {
		return 1000 + $floor['number'];
	}
	if ($floor['prefix'] == "B") {
		return 0 - $floor['number'];
	}
	return $floor['number'];
}

/**
 * buildings_sub_detail 樓層排序比較函式，供 usort 使用。
 */
function compare_detail_floor_order($a, $b) {
	$a_value = get_floor_sort_value($a['floor']);
	$b_value = get_floor_sort_value($b['floor']);
	if ($a_value == $b_value) {
		return 0;
	}
	return ($a_value < $b_value) ? -1 : 1;
}

/**
 * 解析樓層代碼，支援一般樓層、B 地下層與 R 屋突層。
 */
function parse_floor_code($floor_text) {
	$floor_text = strtoupper(trim($floor_text));
	$floor_text = str_replace(" ", "", $floor_text);
	if (preg_match("/^(R|B)?([0-9]+)F?$/", $floor_text, $matches)) {
		$prefix = ($matches[1] == "") ? "N" : $matches[1];
		return array("prefix" => $prefix, "number" => intval($matches[2]));
	}
	return false;
}

$xajax->processRequest();


$auto_seq = $_GET['auto_seq'];
$fm = $_GET['fm'];

$mess_title = $title;


$mDB = "";
$mDB = new MywebDB();
$Qry="SELECT a.* FROM CaseManagement a
WHERE a.auto_seq = '$auto_seq'";
$mDB->query($Qry);
$total = $mDB->rowCount();
if ($total > 0) {
    //已找到符合資料
	$row=$mDB->fetchRow(2);
	$case_id = $row['case_id'];
	$construction_id = $row['construction_id'];
	$makeby9 = $row['makeby9'];
	$last_modify9 = $row['last_modify9'];

}

$mDB->remove();


$show_closebtn=<<<EOT
<div class="btn-group vbottom" role="group" style="margin-top:5px;">
	<button id="close" class="btn btn-danger" type="button" onclick="parent.myDraw();parent.$.fancybox.close();" style="padding: 5px 15px;"><i class="bi bi-power"></i>&nbsp;關閉</button>
</div>
EOT;


//取得使用者員工身份
if (empty($makeby9))
	$makeby9 = $memberID;

$member_picture = getmemberpict50($makeby9);

$member_row = getkeyvalue2("memberinfo","member","member_no = '$makeby9'","member_name");
$member_name = $member_row['member_name'];

$employee_row = getkeyvalue2($site_db."_info","employee","member_no = '$makeby9'","count(*) as manager_count,employee_name,employee_type");
$manager_count =$employee_row['manager_count'];
if ($manager_count > 0) {
	$employee_name = $employee_row['employee_name'];
	$employee_type = $employee_row['employee_type'];
} else {
	$employee_name = $member_name;
	$employee_type = "未在員工名單";
}

$member_logo=<<<EOT
<div class="float-end text-nowrap me-2 size14 weight">
	<div class="inline mytable bg-white rounded">
		<div class="myrow">
			<div class="mycell text-center text-nowrap">
				<div class="inline me-1">By：</div>
				<img src="$member_picture" height="32" class="inline rounded">
			</div>
			<div class="mycell text-start ps-1 w-auto">
				<div class="size08 blue02 weight text-nowrap">$employee_name</div>
				<div class="size06 weight text-nowrap">$employee_type</div>
			</div>
		</div>
	</div>
</div>
EOT;


$m_location		= "/website/smarty/templates/".$site_db."/".$templates;
include $m_location."/sub_modal/project/func07/FloorCashflowGC_ms/buildings_sub.php";


$show_center=<<<EOT
<style>

.card_full {
    width: 100%;
	height: 100vh;
}

#full {
    width: 100%;
	height: 100%;
}

</style>
<div class="card card_full">
	<div class="card-header text-bg-info">
		<div class="size14 weight float-start" style="margin-top: 5px;">
			$mess_title
		</div>
		<div class="float-end" style="margin-top: -5px;">
			$show_closebtn
		</div>
	</div>
	<div id="full" class="card-body p-1" data-overlayscrollbars-initialize>
		<div class="w-100">
			$member_logo
			<div class="container-fluid size14 mt-2">
				<div class="row">
					<div class="col-lg-2 col-sm-12 col-md-12">
						<div class="inline weight">案件編號：</div> 
						<div class="inline">
							<div class="inline weight blue02 me-2">$case_id</div>
						</div> 
					</div> 
					<div class="col-lg-10 col-sm-12 col-md-12">
						<div class="inline weight">工程名稱：</div> 
						<div class="inline blue02">
							$construction_id
						</div> 
					</div> 
				</div>
			</div>
		</div>
		$show_buildings_sub
	</div>
</div>

<script>

var updispatch = function(dispatch_id){

	var site_db = '$site_db';
	var templates = '$templates';
	//var dispatch_id = '$dispatch_id';

	var url = '/smarty/templates/'+site_db+'/'+templates+'/sub_modal/project/func08/dispatch_ms/ajax_update_dispatch.php'; 

	$.ajax({
		url: url, 
		type: 'GET',
		data: { dispatch_id: dispatch_id },
		dataType: 'text', 
		success: function(data) {
		},
		error: function() {
		}
	});

}

</script>

EOT;

?>
