<?php

/**
 * 樓層合併計價（材料買賣／租賃案件）
 * 依棟別設定收款期數與期數樓層，例如第一期 10F、第二期 15F；
 * 套用預計數量/金額/收款日時，上一期樓層（不含）到本期樓層（含）的預計數量與金額會加總到本期樓層。
 * 期數資料存於 buildings_sub_period，每日批次重算時同樣會套用。
 */

session_start();
$memberID = $_SESSION['memberID'];
$powerkey = $_SESSION['powerkey'];


require_once '/website/os/Mobile-Detect-2.8.34/Mobile_Detect.php';
$detect = new Mobile_Detect;


@include_once("/website/class/".$site_db."_info_class.php");
require_once __DIR__."/module_modify_log.php";

/* 使用xajax */
@include_once '/website/xajax/xajax_core/xajax.inc.php';
$xajax = new xajax();


/**
 * 樓層合併計價可使用的承攬模式。
 */
function floor_period_allowed_model($contracting_model) {
	return in_array(trim((string)$contracting_model), array("材料買賣(BH)", "租賃(RH)"), true);
}

/**
 * 將樓層文字轉為排序值（地下層在前、一般樓層其次、屋突層最後），無法解析時回傳 false。
 * 規則與 buildings_modify.php 的 get_floor_sort_value() 一致。
 */
function floor_period_sort_value($floor_text) {
	$floor_text = strtoupper(str_replace(" ", "", trim((string)$floor_text)));
	if (!preg_match("/^(R|B)?([0-9]+)F?$/", $floor_text, $matches)) {
		return false;
	}
	$number = intval($matches[2]);
	if ($matches[1] == "R") {
		return 1000 + $number;
	}
	if ($matches[1] == "B") {
		return 0 - $number;
	}
	return $number;
}


$xajax->registerFunction("save_floor_periods");
/**
 * 儲存指定棟別的樓層合併計價期數，完成後由母視窗重新套用預計數量/金額/收款日。
 */
function save_floor_periods($case_id, $building, $floors_text, $memberID) {

	$objResponse = new xajaxResponse();

	$case_id = trim($case_id);
	$building = trim($building);
	$memberID = trim($memberID);
	$case_id_sql = addslashes($case_id);
	$building_sql = addslashes($building);
	$memberID_sql = addslashes($memberID);

	$mDB = "";
	$mDB = new MywebDB();

	$Qry="SELECT ContractingModel FROM CaseManagement WHERE case_id = '$case_id_sql' LIMIT 1";
	$mDB->query($Qry);
	$case_row = ($mDB->rowCount() > 0) ? $mDB->fetchRow(2) : array("ContractingModel" => "");
	if (!floor_period_allowed_model($case_row['ContractingModel'])) {
		$mDB->remove();
		$objResponse->script("jAlert('警示', '此案件承攬模式不是「材料買賣(BH)」或「租賃(RH)」，無法設定樓層合併計價。', 'red', '', 2500);");
		return $objResponse;
	}

	//此棟別所有可用樓層
	$Qry="SELECT floor FROM buildings_sub_detail WHERE case_id = '$case_id_sql' AND building = '$building_sql'";
	$mDB->query($Qry);
	$valid_floors = array();
	while ($row = $mDB->fetchRow(2)) {
		$valid_floors[$row['floor']] = floor_period_sort_value($row['floor']);
	}
	if (count($valid_floors) == 0) {
		$mDB->remove();
		$objResponse->script("jAlert('警示', '找不到此棟別的樓層資料。', 'red', '', 2000);");
		return $objResponse;
	}

	$floors = array();
	$used_sort = array();
	$parts = explode("|", (string)$floors_text);
	foreach ($parts as $floor) {
		$floor = trim($floor);
		if ($floor === "") {
			continue;
		}
		if (!isset($valid_floors[$floor]) || $valid_floors[$floor] === false) {
			$mDB->remove();
			$objResponse->script("jAlert('警示', ".json_encode("樓層「".$floor."」不屬於此棟別，請重新選擇。", JSON_UNESCAPED_UNICODE).", 'red', '', 2000);");
			return $objResponse;
		}
		$sort_value = $valid_floors[$floor];
		if (isset($used_sort[$sort_value])) {
			$mDB->remove();
			$objResponse->script("jAlert('警示', ".json_encode("樓層「".$floor."」重複設定，請重新選擇。", JSON_UNESCAPED_UNICODE).", 'red', '', 2000);");
			return $objResponse;
		}
		$used_sort[$sort_value] = true;
		$floors[] = array("floor" => $floor, "sort" => $sort_value);
	}

	usort($floors, function($a, $b) {
		if ($a['sort'] == $b['sort']) {
			return 0;
		}
		return ($a['sort'] < $b['sort']) ? -1 : 1;
	});

	//以本次設定取代此棟別原有期數；全部刪除時即恢復各樓層原本數值
	$Qry="DELETE FROM buildings_sub_period WHERE case_id = '$case_id_sql' AND building = '$building_sql'";
	$mDB->query($Qry);

	for ($i = 0; $i < count($floors); $i++) {
		$period_no = $i + 1;
		$floor_sql = addslashes($floors[$i]['floor']);
		$Qry="INSERT INTO buildings_sub_period (case_id, building, period_no, floor, makeby, last_modify)
			VALUES ('$case_id_sql', '$building_sql', '$period_no', '$floor_sql', '$memberID_sql', now())";
		$mDB->query($Qry);
	}

	updateFloorCashflowGCModifyLog($mDB, $case_id, $memberID);

	$mDB->remove();

	//由母視窗重新套用預計數量/金額/收款日（合併邏輯在 apply_expected_work_qty 內）
	$js_case_id = json_encode($case_id, JSON_UNESCAPED_UNICODE);
	$js_member_id = json_encode($memberID, JSON_UNESCAPED_UNICODE);
	$objResponse->script("if (typeof parent.xajax_apply_expected_work_qty == 'function') { parent.xajax_apply_expected_work_qty($js_case_id, $js_member_id); }");
	$objResponse->script("parent.$.fancybox.close();");

	return $objResponse;
}


$xajax->processRequest();


$fm = isset($_GET['fm']) ? $_GET['fm'] : "";
$case_id = isset($_GET['case_id']) ? trim($_GET['case_id']) : "";
$default_building = isset($_GET['building']) ? trim($_GET['building']) : "";
$case_id_sql = addslashes($case_id);

$mess_title = $title;

$mDB = "";
$mDB = new MywebDB();

$construction_id = "";
$ContractingModel = "";
$Qry="SELECT construction_id, ContractingModel FROM CaseManagement WHERE case_id = '$case_id_sql' LIMIT 1";
$mDB->query($Qry);
if ($mDB->rowCount() > 0) {
	$row = $mDB->fetchRow(2);
	$construction_id = $row['construction_id'];
	$ContractingModel = $row['ContractingModel'];
}

//棟別、樓層與既有期數
$building_data = array();
$Qry="SELECT building FROM buildings_sub WHERE case_id = '$case_id_sql' ORDER BY auto_seq";
$mDB->query($Qry);
while ($row = $mDB->fetchRow(2)) {
	$building_data[$row['building']] = array("floors" => array(), "periods" => array());
}

$Qry="SELECT building, floor FROM buildings_sub_detail WHERE case_id = '$case_id_sql'";
$mDB->query($Qry);
while ($row = $mDB->fetchRow(2)) {
	$sort_value = floor_period_sort_value($row['floor']);
	if ($sort_value === false || !isset($building_data[$row['building']])) {
		continue;
	}
	$building_data[$row['building']]['floors'][] = array("floor" => $row['floor'], "sort" => $sort_value);
}

$Qry="SELECT building, floor FROM buildings_sub_period WHERE case_id = '$case_id_sql' ORDER BY building, period_no, auto_seq";
$mDB->query($Qry);
while ($row = $mDB->fetchRow(2)) {
	if (isset($building_data[$row['building']])) {
		$building_data[$row['building']]['periods'][] = $row['floor'];
	}
}

$mDB->remove();

foreach ($building_data as $b => $data) {
	usort($building_data[$b]['floors'], function($a, $c) {
		if ($a['sort'] == $c['sort']) {
			return 0;
		}
		return ($a['sort'] < $c['sort']) ? -1 : 1;
	});
}

if ($default_building === "" || !isset($building_data[$default_building])) {
	$keys = array_keys($building_data);
	$default_building = (count($keys) > 0) ? $keys[0] : "";
}

$building_options = "";
foreach ($building_data as $b => $data) {
	$b_html = htmlspecialchars($b, ENT_QUOTES, 'UTF-8');
	$selected = ($b === $default_building) ? " selected" : "";
	$building_options .= "<option value=\"$b_html\"$selected>$b_html</option>";
}

$json_building_data = json_encode($building_data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$json_case_id = json_encode($case_id, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$json_member_id = json_encode($memberID, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$json_default_building = json_encode($default_building, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

$case_id_html = htmlspecialchars($case_id, ENT_QUOTES, 'UTF-8');
$construction_id_html = htmlspecialchars($construction_id, ENT_QUOTES, 'UTF-8');
$ContractingModel_html = htmlspecialchars($ContractingModel, ENT_QUOTES, 'UTF-8');

$allowed = floor_period_allowed_model($ContractingModel);


$show_savebtn=<<<EOT
<div class="btn-group vbottom" role="group" style="margin-top:5px;">
	<button id="save" class="btn btn-primary" type="button" onclick="savePeriods();" style="padding: 5px 15px;"><i class="bi bi-check-circle"></i>&nbsp;確認</button>
	<button id="close" class="btn btn-danger" type="button" onclick="parent.$.fancybox.close();" style="padding: 5px 15px;"><i class="bi bi-power"></i>&nbsp;關閉</button>
</div>
EOT;

if (!$allowed) {
	$show_savebtn=<<<EOT
<div class="btn-group vbottom" role="group" style="margin-top:5px;">
	<button id="close" class="btn btn-danger" type="button" onclick="parent.$.fancybox.close();" style="padding: 5px 15px;"><i class="bi bi-power"></i>&nbsp;關閉</button>
</div>
EOT;
}

$show_body = "";
if (!$allowed) {
	$show_body = "<div class=\"alert alert-warning mt-3\">此案件承攬模式為「".$ContractingModel_html."」，只有「材料買賣(BH)」或「租賃(RH)」案件可使用樓層合併計價。</div>";
} else if (count($building_data) == 0) {
	$show_body = "<div class=\"alert alert-warning mt-3\">此案件尚無棟別資料。</div>";
} else {
	$show_body=<<<EOT
<div class="period_section">
	<div class="d-flex align-items-center flex-wrap gap-2 mb-3">
		<div class="field_label mb-0">棟別</div>
		<select id="period_building" class="form-select form-select-sm" style="width:auto;min-width:160px;" onchange="changeBuilding(this.value);">
			$building_options
		</select>
		<div class="text-muted size12">每個棟別各自設定，按「確認」只儲存目前棟別。</div>
	</div>
	<div class="period_hint mb-3">
		說明：每一期選擇一個樓層，上一期樓層（不含）到該期樓層（含）之間的預計數量與預計收款金額，會全部加總到該期樓層，其餘樓層歸零。<br>
		例如 1F~15F，第一期 10F、第二期 15F：1F~9F 加總到 10F，11F~14F 加總到 15F。最後一期之後的樓層維持原值。<br>
		刪除期數後按「確認」，該範圍樓層會恢復原本各自的數值。
	</div>
	<table class="table table-bordered border-dark w-100 mb-2" id="period_table">
		<thead class="table-light border-dark">
			<tr>
				<th class="text-center text-nowrap" style="width:15%;">期數</th>
				<th class="text-center text-nowrap" style="width:30%;">樓層</th>
				<th class="text-center text-nowrap" style="width:45%;">合併範圍</th>
				<th class="text-center text-nowrap" style="width:10%;">刪除</th>
			</tr>
		</thead>
		<tbody id="period_tbody"></tbody>
	</table>
	<button type="button" class="btn btn-success btn-sm" onclick="addPeriod();"><i class="bi bi-plus-circle"></i>&nbsp;新增期數</button>
</div>
EOT;
}


$show_center=<<<EOT
<style>
.card_full {
	width: 100vw;
	height: 100vh;
}
#full {
	width: 100vw;
	height: 100vh;
}
#info_container {
	width: 100%;
	max-width: 760px !Important;
	margin: 0 auto !Important;
}
.field_label {
	font-size: 14px;
	font-weight: 700;
	color: #555;
}
.field_value {
	font-size: 16px;
	font-weight: 700;
	color: #0d6efd;
}
.period_hint {
	font-size: 13px;
	color: #555;
	background-color: #f8f9fa;
	border-left: 4px solid #ffc107;
	padding: 8px 12px;
}
#period_table td {
	vertical-align: middle;
}
</style>
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
		<div id="info_container" class="p-2">
			<div class="d-flex flex-wrap gap-4 mb-3">
				<div><div class="field_label">案件編號</div><div class="field_value">$case_id_html</div></div>
				<div><div class="field_label">工程名稱</div><div class="field_value">$construction_id_html</div></div>
				<div><div class="field_label">承攬模式</div><div class="field_value">$ContractingModel_html</div></div>
			</div>
			$show_body
		</div>
	</div>
</div>
<script>

var periodData = $json_building_data;
var periodCaseId = $json_case_id;
var periodMemberId = $json_member_id;
var periodBuilding = $json_default_building;
var periodRows = [];

function escapeHtml(text) {
	return String(text).replace(/[&<>"']/g, function(c) {
		return {"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[c];
	});
}

function getFloors() {
	return (periodData[periodBuilding] && periodData[periodBuilding].floors) ? periodData[periodBuilding].floors : [];
}

function getFloorSort(floor) {
	var floors = getFloors();
	for (var i = 0; i < floors.length; i++) {
		if (floors[i].floor === floor) return floors[i].sort;
	}
	return null;
}

function changeBuilding(building) {
	periodBuilding = building;
	periodRows = (periodData[building] && periodData[building].periods) ? periodData[building].periods.slice() : [];
	renderPeriods();
}

function addPeriod() {
	periodRows.push("");
	renderPeriods();
}

function removePeriod(index) {
	periodRows.splice(index, 1);
	renderPeriods();
}

function setPeriodFloor(index, floor) {
	periodRows[index] = floor;
	renderPeriods();
}

//依目前選擇的期數樓層，計算每一期實際合併的樓層範圍
function getMergeRangeText(index) {
	var floor = periodRows[index];
	var sortValue = getFloorSort(floor);
	if (floor === "" || sortValue === null) return '<span class="text-muted">請選擇樓層</span>';

	var prevSort = null;
	var duplicate = false;
	for (var i = 0; i < periodRows.length; i++) {
		if (i === index) continue;
		var s = getFloorSort(periodRows[i]);
		if (s === null) continue;
		if (s === sortValue) duplicate = true;
		if (s < sortValue && (prevSort === null || s > prevSort)) prevSort = s;
	}
	if (duplicate) return '<span class="text-danger weight">樓層重複</span>';

	var floors = getFloors();
	var members = [];
	for (var j = 0; j < floors.length; j++) {
		if (floors[j].sort <= sortValue && (prevSort === null || floors[j].sort > prevSort)) {
			members.push(floors[j].floor);
		}
	}
	if (members.length <= 1) return escapeHtml(floor) + '（不合併）';
	return escapeHtml(members[0]) + ' ~ ' + escapeHtml(members[members.length - 1])
		+ '，共 ' + members.length + ' 層 → 加總到 <span class="weight text-primary">' + escapeHtml(floor) + '</span>';
}

function renderPeriods() {
	var floors = getFloors();
	var html = '';
	if (periodRows.length === 0) {
		html = '<tr><td colspan="4" class="text-center text-muted">尚未設定期數，各樓層使用原本數值。</td></tr>';
	}
	for (var i = 0; i < periodRows.length; i++) {
		var options = '<option value="">請選擇</option>';
		for (var j = 0; j < floors.length; j++) {
			var selected = (floors[j].floor === periodRows[i]) ? ' selected' : '';
			options += '<option value="' + escapeHtml(floors[j].floor) + '"' + selected + '>' + escapeHtml(floors[j].floor) + '</option>';
		}
		html += '<tr>'
			+ '<td class="text-center weight">第' + (i + 1) + '期</td>'
			+ '<td class="text-center"><select class="form-select form-select-sm" onchange="setPeriodFloor(' + i + ', this.value);">' + options + '</select></td>'
			+ '<td class="size12">' + getMergeRangeText(i) + '</td>'
			+ '<td class="text-center"><button type="button" class="btn btn-light btn-sm" title="刪除" onclick="removePeriod(' + i + ');"><i class="bi bi-trash"></i></button></td>'
			+ '</tr>';
	}
	$('#period_tbody').html(html);
}

function savePeriods() {
	var floors = [];
	var used = {};
	for (var i = 0; i < periodRows.length; i++) {
		var floor = periodRows[i];
		if (floor === "") {
			jAlert('警示', '第' + (i + 1) + '期尚未選擇樓層，請選擇或刪除該期。', 'red', '', 2000);
			return;
		}
		var s = getFloorSort(floor);
		if (used[s]) {
			jAlert('警示', '樓層「' + floor + '」重複設定，請重新選擇。', 'red', '', 2000);
			return;
		}
		used[s] = true;
		floors.push(floor);
	}
	//期數依樓層由低到高排序
	floors.sort(function(a, b) { return getFloorSort(a) - getFloorSort(b); });

	Swal.fire({
		title: "確認樓層合併計價?",
		text: "儲存棟別「" + periodBuilding + "」的期數後，會重新套用整個案件的預計數量/金額/收款日。",
		icon: "question",
		showCancelButton: true,
		confirmButtonColor: "#3085d6",
		cancelButtonColor: "#d33",
		cancelButtonText: "取消",
		confirmButtonText: "確認"
	}).then((result) => {
		if (result.isConfirmed) {
			xajax_save_floor_periods(periodCaseId, periodBuilding, floors.join("|"), periodMemberId);
		}
	});
}

$(document).ready(function() {
	if ($('#period_tbody').length > 0) {
		changeBuilding(periodBuilding);
	}
});

</script>
EOT;

?>
