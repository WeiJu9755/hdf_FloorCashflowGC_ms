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

/* 使用xajax */
@include_once '/website/xajax/xajax_core/xajax.inc.php';
$xajax = new xajax();


$xajax->registerFunction("DeleteRow");
function DeleteRow($auto_seq){

	$objResponse = new xajaxResponse();
	
	$mDB = "";
	$mDB = new MywebDB();

	//刪除主資料
	$Qry="delete from buildings_sub_detail where auto_seq = '$auto_seq'";
	$mDB->query($Qry);
	
	$mDB->remove();
	
    $objResponse->script("oTable = $('#buildings_sub_detail_table').dataTable();oTable.fnDraw(false)");
	$objResponse->script("autoclose('提示', '資料已刪除！', 500);");

	return $objResponse;
	
}


$xajax->registerFunction("returnValue");
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
function buildings_sub_DeleteRow($auto_seq){

	$objResponse = new xajaxResponse();

	$mDB = "";
	$mDB = new MywebDB();

	//刪除主資料
	$Qry="DELETE FROM buildings_sub WHERE auto_seq = '$auto_seq'";
	$mDB->query($Qry);
	
	$mDB->remove();
	
    $objResponse->script("oTable = $('#buildings_sub_table').dataTable();oTable.fnDraw(false)");
	$objResponse->script("autoclose('提示', '資料已刪除！', 1500);");

	return $objResponse;
	
}

$xajax->registerFunction("apply_expected_work_qty");
function apply_expected_work_qty($case_id,$memberID){

	$objResponse = new xajaxResponse();

	$case_id = trim($case_id);
	$memberID = trim($memberID);
	$case_id_sql = addslashes($case_id);
	$memberID_sql = addslashes($memberID);

	$mDB = "";
	$mDB = new MywebDB();

	ensure_expected_work_qty_columns($mDB);
	ensure_expected_collection_amount_columns($mDB);

	$Qry="SELECT * FROM CasePricingGC_sub WHERE case_id = '$case_id_sql'";
	$mDB->query($Qry);
	$pricing_count = $mDB->rowCount();
	if ($pricing_count == 0) {
		$mDB->remove();
		$objResponse->script("jAlert('警示', '缺少 CasePricingGC_sub 計價設定，無法套用預計施作數量。', 'red', '', 2500);");
		return $objResponse;
	}
	if ($pricing_count > 1) {
		$mDB->remove();
		$objResponse->script("jAlert('警示', 'CasePricingGC_sub 同案件設定不唯一，請先確認計價設定。', 'red', '', 2500);");
		return $objResponse;
	}

	$pricing_row = $mDB->fetchRow(2);
	$first_rate = floatval($pricing_row['first_percentage']) / 100;
	$second_rate = floatval($pricing_row['second_percentage']) / 100;

	$Qry="SELECT std_floor_u_price,roof_protrusion_u_price,layout_std_floor_u_price,square_roof_protrusion_u_price FROM CaseManagement WHERE case_id = '$case_id_sql'";
	$mDB->query($Qry);
	if ($mDB->rowCount() == 0) {
		$mDB->remove();
		$objResponse->script("jAlert('警示', '找不到 CaseManagement 案件單價資料，無法套用預計收款金額。', 'red', '', 2500);");
		return $objResponse;
	}
	$case_row = $mDB->fetchRow(2);
	$std_floor_u_price = floatval($case_row['std_floor_u_price']);
	$roof_protrusion_u_price = floatval($case_row['roof_protrusion_u_price']);
	$layout_std_floor_u_price = floatval($case_row['layout_std_floor_u_price']);
	$square_roof_protrusion_u_price = floatval($case_row['square_roof_protrusion_u_price']);

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

		$Qry="SELECT auto_seq,floor FROM buildings_sub_detail WHERE case_id = '$case_id_sql' AND building = '$building_sql'";
		$mDB->query($Qry);

		$details = array();
		while ($row=$mDB->fetchRow(2)) {
			$details[] = $row;
		}

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

			if ($std_count > 0 && isset($std_floors[$floor_key])) {
				$first_expected_work_qty = round(($std_qty / $std_count) * $first_rate, 2);
				$first_layout_expected_work_qty = round(($std_layout_qty / $std_count) * $first_rate, 2);
				$second_expected_work_qty = round(($std_qty / $std_count) * $second_rate, 2);
				$second_layout_expected_work_qty = round(($std_layout_qty / $std_count) * $second_rate, 2);
				$first_expected_collection_amount = round($std_floor_u_price * $first_expected_work_qty, 2);
				$first_layout_expected_collection_amount = round($layout_std_floor_u_price * $first_layout_expected_work_qty, 2);
				$second_expected_collection_amount = round($std_floor_u_price * $second_expected_work_qty, 2);
				$second_layout_expected_collection_amount = round($layout_std_floor_u_price * $second_layout_expected_work_qty, 2);
			} else if ($roof_count > 0 && isset($roof_floors[$floor_key])) {
				$first_expected_work_qty = round(($roof_qty / $roof_count) * $first_rate, 2);
				$first_layout_expected_work_qty = round(($roof_layout_qty / $roof_count) * $first_rate, 2);
				$second_expected_work_qty = round(($roof_qty / $roof_count) * $second_rate, 2);
				$second_layout_expected_work_qty = round(($roof_layout_qty / $roof_count) * $second_rate, 2);
				$first_expected_collection_amount = round($roof_protrusion_u_price * $first_expected_work_qty, 2);
				$first_layout_expected_collection_amount = round($square_roof_protrusion_u_price * $first_layout_expected_work_qty, 2);
				$second_expected_collection_amount = round($roof_protrusion_u_price * $second_expected_work_qty, 2);
				$second_layout_expected_collection_amount = round($square_roof_protrusion_u_price * $second_layout_expected_work_qty, 2);
			} else {
				continue;
			}

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
				,makeby = '$memberID_sql'
				,last_modify = now()
				WHERE auto_seq = '$detail_auto_seq'";
			$mDB->query($Qry);
			$updated_count++;
		}
	}

	$mDB->remove();

	$warning_text = "";
	if (count($warning_list) > 0) {
		$warning_text = "\\n提醒：".implode("、", array_unique($warning_list));
	}
	$tip_text = addslashes("已套用預計施作數量與收款金額：".$updated_count." 筆".$warning_text);

	$objResponse->script("buildings_sub_myDraw();");
	$objResponse->script("if (typeof buildings_sub_detail_myDraw == 'function') { buildings_sub_detail_myDraw(); }");
	$objResponse->script("art.dialog.tips('".$tip_text."',2);");

	return $objResponse;
}

function ensure_expected_work_qty_columns($mDB) {
	$columns = array(
		"first_expected_work_qty" => "ALTER TABLE buildings_sub_detail ADD COLUMN first_expected_work_qty DECIMAL(12,2) DEFAULT NULL COMMENT '第一次預計施作數量' AFTER expected_actual_grouting_date",
		"first_layout_expected_work_qty" => "ALTER TABLE buildings_sub_detail ADD COLUMN first_layout_expected_work_qty DECIMAL(12,2) DEFAULT NULL COMMENT '第一次放樣預計施作數量' AFTER first_expected_work_qty",
		"second_expected_work_qty" => "ALTER TABLE buildings_sub_detail ADD COLUMN second_expected_work_qty DECIMAL(12,2) DEFAULT NULL COMMENT '第二次預計施作數量' AFTER first_layout_expected_work_qty",
		"second_layout_expected_work_qty" => "ALTER TABLE buildings_sub_detail ADD COLUMN second_layout_expected_work_qty DECIMAL(12,2) DEFAULT NULL COMMENT '第二次放樣預計施作數量' AFTER second_expected_work_qty"
	);

	foreach ($columns as $column_name => $alter_sql) {
		$Qry="SHOW COLUMNS FROM buildings_sub_detail LIKE '$column_name'";
		$mDB->query($Qry);
		if ($mDB->rowCount() == 0) {
			$mDB->query($alter_sql);
		}
	}
}

function ensure_expected_collection_amount_columns($mDB) {
	$columns = array(
		"first_expected_collection_amount" => "ALTER TABLE buildings_sub_detail ADD COLUMN first_expected_collection_amount DECIMAL(15,2) DEFAULT NULL COMMENT '第一次預計收款金額' AFTER second_layout_expected_work_qty",
		"first_layout_expected_collection_amount" => "ALTER TABLE buildings_sub_detail ADD COLUMN first_layout_expected_collection_amount DECIMAL(15,2) DEFAULT NULL COMMENT '第一次放樣預計收款金額' AFTER first_expected_collection_amount",
		"second_expected_collection_amount" => "ALTER TABLE buildings_sub_detail ADD COLUMN second_expected_collection_amount DECIMAL(15,2) DEFAULT NULL COMMENT '第二次預計收款金額' AFTER first_layout_expected_collection_amount",
		"second_layout_expected_collection_amount" => "ALTER TABLE buildings_sub_detail ADD COLUMN second_layout_expected_collection_amount DECIMAL(15,2) DEFAULT NULL COMMENT '第二次放樣預計收款金額' AFTER second_expected_collection_amount"
	);

	foreach ($columns as $column_name => $alter_sql) {
		$Qry="SHOW COLUMNS FROM buildings_sub_detail LIKE '$column_name'";
		$mDB->query($Qry);
		if ($mDB->rowCount() == 0) {
			$mDB->query($alter_sql);
		}
	}
}

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

function parse_single_floor_key($floor_text) {
	$floor = parse_floor_code($floor_text);
	if ($floor === false) {
		return "";
	}
	return $floor['prefix'].":".$floor['number'];
}

function parse_floor_code($floor_text) {
	$floor_text = strtoupper(trim($floor_text));
	$floor_text = str_replace(" ", "", $floor_text);
	if (preg_match("/^(R)?([0-9]+)F?$/", $floor_text, $matches)) {
		$prefix = ($matches[1] == "R") ? "R" : "N";
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
