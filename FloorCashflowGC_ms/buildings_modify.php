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