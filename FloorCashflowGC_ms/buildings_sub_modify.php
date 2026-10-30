<?php

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

$xajax->registerFunction("processform");

function processform($aFormValues){

	$objResponse = new xajaxResponse();
	
	$bError = false;
	
	/*
	if (trim($aFormValues['building']) == "")	{
		$objResponse->script("jAlert('警示', '請選擇棟別', 'red', '', 2000);");
		return $objResponse;
		exit;
	}
	*/

	if (!$bError) {
		$fm								= trim($aFormValues['fm']);
		$auto_seq						= trim($aFormValues['auto_seq']);
		$memberID						= trim($aFormValues['memberID']);
		//存入實體資料庫中
		$mDB = "";
		$mDB = new MywebDB();

		$quantity_fields = ['std_layer_qty', 'roof_protrusion_qty', 'layout_std_layer_qty', 'layout_roof_protrusion_qty'];
		$quantities = [];
		foreach ($quantity_fields as $field) {
			$value = trim($aFormValues[$field] ?? '');
			if ($value === '') {
				$value = '0';
			}
			if (!preg_match('/^\d+(?:\.\d+)?$/D', $value)) {
				$objResponse->script("jAlert('警示', '請輸入有效的非負數量。', 'red', '', 2000);");
				return $objResponse;
			}
			$quantities[$field] = addslashes($value);
		}
		$std_layer_qty = $quantities['std_layer_qty'];
		$roof_protrusion_qty = $quantities['roof_protrusion_qty'];
		$layout_std_layer_qty = $quantities['layout_std_layer_qty'];
		$layout_roof_protrusion_qty = $quantities['layout_roof_protrusion_qty'];

		$auto_seq_sql = addslashes($auto_seq);
		$Qry="SELECT bs.case_id FROM buildings_sub bs
			 WHERE bs.auto_seq = '$auto_seq_sql'
			 LIMIT 1";
		$mDB->query($Qry);
		if ($mDB->rowCount() == 0) {
			$mDB->remove();
			$objResponse->script("jAlert('警示', '找不到棟別資料，無法更新。', 'red', '', 2000);");
			return $objResponse;
		}
		$building_row = $mDB->fetchRow(2);
		$case_id_sql = addslashes($building_row['case_id']);

		updateFloorCashflowGCModifyLogByBuildingSeq($mDB, $auto_seq, $memberID);
	  
		$Qry="UPDATE buildings_sub set
				 std_layer_qty	= '$std_layer_qty'
				,roof_protrusion_qty	= '$roof_protrusion_qty'
				,layout_std_layer_qty	= '$layout_std_layer_qty'
				,layout_roof_protrusion_qty	= '$layout_roof_protrusion_qty'
				,makeby	= '$memberID'
				,last_modify	= now()
				where auto_seq = '$auto_seq_sql'";
				
		$mDB->query($Qry);
		$Qry="UPDATE CaseManagement cm SET
			cm.tot_std_contract_layers = (SELECT COALESCE(SUM(std_layer_qty), 0) FROM buildings_sub WHERE case_id = '$case_id_sql'),
			cm.tot_houses_multi_floors = (SELECT COALESCE(SUM(roof_protrusion_qty), 0) FROM buildings_sub WHERE case_id = '$case_id_sql'),
			cm.layout_std_floor_total_qty = (SELECT COALESCE(SUM(layout_std_layer_qty), 0) FROM buildings_sub WHERE case_id = '$case_id_sql'),
			cm.layout_roof_protrusion_total_qty = (SELECT COALESCE(SUM(layout_roof_protrusion_qty), 0) FROM buildings_sub WHERE case_id = '$case_id_sql')
			WHERE cm.case_id = '$case_id_sql'";
		$mDB->query($Qry);
        $mDB->remove();

	};
	
	$objResponse->script("setSave();");
	$objResponse->script("myDraw();");

	$objResponse->script("art.dialog.tips('已存檔!',1);");
	$objResponse->script("parent.$.fancybox.close();");

	return $objResponse;	
}


$xajax->processRequest();


$fm = $_GET['fm'];
$auto_seq = $_GET['auto_seq'];


$mess_title = $title;
/*
$super_admin = "N";
$mem_row = getkeyvalue2('memberinfo','member',"member_no = '$memberID'",'admin,admin_readonly');
$super_admin = $mem_row['admin'];
$admin_readonly = $mem_row['admin_readonly'];


$cando = true;

if ($cando == true) {
*/

/*
//取得上層工程概況的代工單位
$overview_sub_row = getkeyvalue2($site_db.'_info','overview_sub',"case_id = '$case_id' and auto_seq = '$seq'",'builder_id');
$builder_id = $overview_sub_row['builder_id'];
*/


//讀取資料
$mDB = "";
$mDB=new MywebDB();

$std_floor_u_price = '';
$roof_protrusion_u_price = '';
$layout_std_floor_u_price = '';
$square_roof_protrusion_u_price = '';

//取得棟別
$Qry="select * from buildings_sub where auto_seq = '$auto_seq' order by auto_seq";
$mDB->query($Qry);

if ($mDB->rowCount() > 0) {
	$row=$mDB->fetchRow(2);
	$case_id = $row['case_id'];
	$building = $row['building'];
	$scheduled_entry_date = $row['scheduled_entry_date'];
	$actual_entry_date = $row['actual_entry_date'];
	$construction_days_first_floor = $row['construction_days_first_floor'];
	$construction_days_per_floor = $row['construction_days_per_floor'];
	$std_layer_floor = $row['std_layer_floor'];
	$std_layer_qty = $row['std_layer_qty'];
	$roof_protrusion_floor = $row['roof_protrusion_floor'];
	$roof_protrusion_qty = $row['roof_protrusion_qty'];
	$layout_std_layer_qty = $row['layout_std_layer_qty'];
	$layout_roof_protrusion_qty = $row['layout_roof_protrusion_qty'];
	$outsourcing = $row['outsourcing'];
}

// 單價仍由 CaseManagement 提供，數量使用此棟別的 buildings_sub 資料。
if (!empty($case_id)) {
	$case_id_sql = addslashes($case_id);
	$Qry="SELECT construction_id,
				std_floor_u_price,
				roof_protrusion_u_price,
				layout_std_floor_u_price,
				square_roof_protrusion_u_price
			  FROM CaseManagement
			 WHERE case_id = '$case_id_sql'
			 LIMIT 1";
	$mDB->query($Qry);
	if ($mDB->rowCount() > 0) {
		$case_qty_row = $mDB->fetchRow(2);
		$construction_id = $case_qty_row['construction_id'];
		$std_floor_u_price = $case_qty_row['std_floor_u_price'];
		$roof_protrusion_u_price = $case_qty_row['roof_protrusion_u_price'];
		$layout_std_floor_u_price = $case_qty_row['layout_std_floor_u_price'];
		$square_roof_protrusion_u_price = $case_qty_row['square_roof_protrusion_u_price'];

	}
}

$mDB->remove();


$show_savebtn=<<<EOT
<div class="btn-group vbottom" role="group" style="margin-top:5px;">
	<button id="save" class="btn btn-primary" type="button" onclick="CheckValue(this.form);" style="padding: 5px 15px;"><i class="bi bi-check-circle"></i>&nbsp;存檔</button>
	<button id="cancel" class="btn btn-secondary display_none" type="button" onclick="setCancel();" style="padding: 5px 15px;"><i class="bi bi-x-circle"></i>&nbsp;取消</button>
	<button id="close" class="btn btn-danger" type="button" onclick="parent.$.fancybox.close();" style="padding: 5px 15px;"><i class="bi bi-power"></i>&nbsp;關閉</button>
</div>
EOT;



if (!($detect->isMobile() && !$detect->isTablet())) {
	$isMobile = 0;

$style_css=<<<EOT
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
	width: 800px !Important;
	margin: 0 auto !Important;
}

.field_div1 {width:250px;display: none;font-size:18px;color:#000;text-align:right;font-weight:700;padding:15px 10px 0 0;vertical-align: top;display:inline-block;zoom: 1;*display: inline;}
.field_div2 {width:100%;max-width:520px;display: none;font-size:18px;color:#000;text-align:left;font-weight:700;padding:8px 0 0 0;vertical-align: top;display:inline-block;zoom: 1;*display: inline;}

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

.basic_category + .basic_category {
	border-top: 1px dashed #d7dee4;
	margin-top: 14px;
	padding-top: 14px;
}

.basic_category_title {
	font-size: 15px;
	font-weight: 700;
	color: #333;
	margin-bottom: 8px;
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

.field_value_main {
	color: #0d6efd;
}

.label_highlight {
	color: #dc3545;
}

.qty_input {
	width: 100%;
	max-width: 180px;
}

.maxwidth {
    width: 100%;
    max-width: 250px;
}

</style>
EOT;

} else {
	$isMobile = 1;
$style_css=<<<EOT
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

.basic_category + .basic_category {
	border-top: 1px dashed #d7dee4;
	margin-top: 12px;
	padding-top: 12px;
}

.basic_category_title {
	font-size: 15px;
	font-weight: 700;
	color: #333;
	margin-bottom: 8px;
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

.field_value_main {
	color: #0d6efd;
}

.label_highlight {
	color: #dc3545;
}

.maxwidth {
    width: 100%;
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
						<div class="basic_category">
							<div class="basic_category_title">案件資訊</div>
							<div class="readonly_grid">
								<div class="readonly_item">
									<div class="field_label">案件編號</div>
									<div class="field_value field_value_main">$case_id</div>
								</div>
								<div class="readonly_item">
									<div class="field_label">工程名稱</div>
									<div class="field_value field_value_main">$construction_id</div>
								</div>
							</div>
						</div>
						<div class="basic_category">
							<div class="basic_category_title">棟別概況</div>
							<div class="readonly_grid">
								<div class="readonly_item">
									<div class="field_label">棟別</div>
									<div class="field_value field_value_main">$building</div>
								</div>
								<div class="readonly_item">
									<div class="field_label">代工數</div>
									<div class="field_value">$outsourcing</div>
								</div>
								<div class="readonly_item">
									<div class="field_label">本棟預計起始日</div>
									<div class="field_value">$scheduled_entry_date</div>
								</div>
								<div class="readonly_item">
									<div class="field_label">本棟實際起始日</div>
									<div class="field_value">$actual_entry_date</div>
								</div>
							</div>
						</div>
						<div class="basic_category">
							<div class="basic_category_title">施作設定</div>
							<div class="readonly_grid">
								<div class="readonly_item">
									<div class="field_label">首層施作天數</div>
									<div class="field_value">$construction_days_first_floor</div>
								</div>
								<div class="readonly_item">
									<div class="field_label">每層施作天數</div>
									<div class="field_value">$construction_days_per_floor</div>
								</div>
								<div class="readonly_item">
									<div class="field_label">標準層範圍</div>
									<div class="field_value">$std_layer_floor</div>
								</div>
								<div class="readonly_item">
									<div class="field_label">屋突層範圍</div>
									<div class="field_value">$roof_protrusion_floor</div>
								</div>
							</div>
						</div>
						<div class="basic_category">
							<div class="basic_category_title">計價設定</div>
							<div class="readonly_grid">
								<div class="readonly_item">
									<div class="field_label">標準層單價(元)</div>
									<div class="field_value">$std_floor_u_price</div>
								</div>
								<div class="readonly_item">
									<div class="field_label">屋突層單價(元)</div>
									<div class="field_value">$roof_protrusion_u_price</div>
								</div>
								<div class="readonly_item">
									<div class="field_label">放樣標準層單價(元)</div>
									<div class="field_value">$layout_std_floor_u_price</div>
								</div>
								<div class="readonly_item">
									<div class="field_label">放樣屋突層單價(元)</div>
									<div class="field_value">$square_roof_protrusion_u_price</div>
								</div>
							</div>
						</div>
					</div>
					<div class="modify_section">
						<div class="section_title">數量設定</div>
						<div class="edit_grid">
							<div class="edit_item">
								<div class="field_label">標準層數量(M2)</div>
								<input type="number" min="0" step="any" class="inputtext qty_input" id="std_layer_qty" name="std_layer_qty" size="20" value="$std_layer_qty" onchange="setEdit();"/>
							</div>
							<div class="edit_item">
								<div class="field_label">屋突層數量(M2)</div>
								<input type="number" min="0" step="any" class="inputtext qty_input" id="roof_protrusion_qty" name="roof_protrusion_qty" size="20" value="$roof_protrusion_qty" onchange="setEdit();"/>
							</div>
							<div class="edit_item">
								<div class="field_label"><span class="label_highlight">放樣</span>標準層數量(M2)</div>
								<input type="number" min="0" step="any" class="inputtext qty_input" id="layout_std_layer_qty" name="layout_std_layer_qty" size="20" value="$layout_std_layer_qty" onchange="setEdit();"/>
							</div>
							<div class="edit_item">
								<div class="field_label"><span class="label_highlight">放樣</span>屋突層數量(M2)</div>
								<input type="number" min="0" step="any" class="inputtext qty_input" id="layout_roof_protrusion_qty" name="layout_roof_protrusion_qty" size="20" value="$layout_roof_protrusion_qty" onchange="setEdit();"/>
							</div>
						</div>
					</div>
				</div>
				<div class="form_btn_div mt-5">
					<input type="hidden" name="fm" value="$fm" />
					<input type="hidden" name="site_db" value="$site_db" />
					<input type="hidden" name="memberID" value="$memberID" />
					<input type="hidden" name="auto_seq" value="$auto_seq" />
					<!--
					<button class="btn btn-primary" type="button" onclick="CheckValue(this.form);" style="padding: 10px;margin-right: 10px;"><i class="bi bi-check-lg green"></i>&nbsp;確定新增</button>
					<button class="btn btn-danger" type="button" onclick="parent.$.fancybox.close();" style="padding: 10px;"><i class="bi bi-power"></i>&nbsp關閉</button>
					-->
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

var myDraw = function(){
	var oTable;
	oTable = parent.$('#buildings_sub_table').dataTable();
	oTable.fnDraw(false);
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

//}

?>
