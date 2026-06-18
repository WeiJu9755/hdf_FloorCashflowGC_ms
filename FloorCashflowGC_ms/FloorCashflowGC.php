<?php

//error_reporting(E_ALL); 
//ini_set('display_errors', '1');

session_start();

$memberID = $_SESSION['memberID'];
$powerkey = $_SESSION['powerkey'];


require_once '/website/os/Mobile-Detect-2.8.34/Mobile_Detect.php';
$detect = new Mobile_Detect;

if (!($detect->isMobile() && !$detect->isTablet())) {
	$isMobile = "0";
} else {
	$isMobile = "1";
}

@include_once("/website/class/".$site_db."_info_class.php");
require_once __DIR__."/module_modify_log.php";

/* 使用xajax */
@include_once '/website/xajax/xajax_core/xajax.inc.php';
$xajax = new xajax();


$xajax->registerFunction("owner_builder");
function owner_builder($auto_seq,$check,$memberID){

	$objResponse = new xajaxResponse();

	$mDB = "";
	$mDB = new MywebDB();
	$Qry = "update CaseManagement set 
			owner_builder = '$check' 
			,makeby8	= '$memberID'
			,last_modify8 = now()
			where auto_seq = '$auto_seq'";
	$mDB->query($Qry);
	$Qry = "SELECT case_id FROM CaseManagement WHERE auto_seq = '$auto_seq' LIMIT 1";
	$mDB->query($Qry);
	if ($mDB->rowCount() > 0) {
		$row = $mDB->fetchRow(2);
		updateFloorCashflowGCModifyLog($mDB, $row['case_id'], $memberID);
	}
	$mDB->remove();
	
    $objResponse->script("oTable = $('#db_table').dataTable();oTable.fnDraw(false)");

	return $objResponse;
	
}

$xajax->registerFunction("owner_contractor");
function owner_contractor($auto_seq,$check,$memberID){

	$objResponse = new xajaxResponse();

	$mDB = "";
	$mDB = new MywebDB();
	$Qry = "update CaseManagement set 
			owner_contractor = '$check' 
			,makeby8	= '$memberID'
			,last_modify8 = now()
			where auto_seq = '$auto_seq'";
	$mDB->query($Qry);
	$Qry = "SELECT case_id FROM CaseManagement WHERE auto_seq = '$auto_seq' LIMIT 1";
	$mDB->query($Qry);
	if ($mDB->rowCount() > 0) {
		$row = $mDB->fetchRow(2);
		updateFloorCashflowGCModifyLog($mDB, $row['case_id'], $memberID);
	}
	$mDB->remove();
	
    $objResponse->script("oTable = $('#db_table').dataTable();oTable.fnDraw(false)");

	return $objResponse;
	
}


$xajax->processRequest();


$fm = $_GET['fm'];
//$pjt = $_GET['pjt'];
//$project_id = $_GET['project_id'];
//$auth_id = $_GET['auth_id'];

$project_id = "202601220001";
$auth_id = "PF001";
if (isset($_GET['pjt']))
	$pjt = $_GET['pjt'];
else
	$pjt = "上包樓層金流維護";


$tb = "CaseManagement";
$pro_id = "FloorCashflowGC";

$m_t = urlencode($_GET['pjt']);

$mess_title = $pjt;


$today = date("Y-m-d");

$dataTable_de = getDataTable_de();
$Prompt = getlang("提示訊息");
$Confirm = getlang("確認");
$Cancel = getlang("取消");

$pubweburl = "//".$domainname;



//網頁標題
$page_title = $pjt;
$page_description = trim(strip_tags($pjt));
$page_description = utf8_substr($page_description,0,1024);
$page_keywords = $pjt;

//載入上方索引列模組
@include $m_location."/sub_modal/base/project_index.php";


$m_pjt = urlencode($_GET['pjt']);

$mk = $_GET['mk'];
$start_date = $_GET['start_date'];
$end_date = $_GET['end_date'];


$today = date("Y-m-d");


$pubweburl = "//".$domainname;


//載入功能選單模組
@include $m_location."/sub_modal/base/project_menu.php";


$fellow_count = 0;
//取得指定管理人數
$pjmyfellow_row = getkeyvalue2($site_db."_info","pjmyfellow","web_id = '$web_id' and project_id = '$project_id' and auth_id = '$auth_id' and pro_id = '$pro_id'","count(*) as fellow_count");
$fellow_count =$pjmyfellow_row['fellow_count'];
if ($fellow_count == 0)
	$fellow_count = "";

/*
$warning_count = 0;
//取得指定管理人數(警訊通知對象)
$pjmyfellow_row = getkeyvalue2($site_db."_info","pjmyfellow","web_id = '$web_id' and project_id = '$project_id' and auth_id = '$auth_id' and pro_id = 'alertlist'","count(*) as warning_count");
$warning_count =$pjmyfellow_row['warning_count'];
if ($warning_count == 0)
	$warning_count = "";
*/

$pjItemManager = false;
//檢查是否為指定管理人
$pjmyfellow_row = getkeyvalue2($site_db."_info","pjmyfellow","web_id = '$web_id' and project_id = '$project_id' and auth_id = '$auth_id' and pro_id = '$pro_id' and member_no = '$memberID'","count(*) as enable_count");
$enable_count =$pjmyfellow_row['enable_count'];
if ($enable_count > 0)
	$pjItemManager = true;


//設定權限
$cando = "N";
if (($powerkey=="A") || ($super_admin=="Y") || ($pjItemManager == true)) {
	$cando = "Y";
}


//取得使用者員工身份
$member_picture = getmemberpict160($memberID);

$member_row = getkeyvalue2("memberinfo","member","member_no = '$memberID'","member_name");
$member_name = $member_row['member_name'];

$employee_row = getkeyvalue2($site_db."_info","employee","member_no = '$memberID'","count(*) as manager_count,employee_name,employee_type,team_id");
$manager_count =$employee_row['manager_count'];
$team_id = $employee_row['team_id'];
if ($manager_count > 0) {
	$employee_name = $employee_row['employee_name'];
	$employee_type = $employee_row['employee_type'];

	$team_row = getkeyvalue2($site_db."_info","team","team_id = '$team_id'","team_name");
	$team_name = $team_row['team_name'];
} else {
	$employee_name = $member_name;
	$team_name = "未在員工名單";
}


$member_logo=<<<EOT
<div class="mytable bg-white m-auto rounded">
	<div class="myrow">
		<div class="mycell" style="text-align:center;width:73px;padding: 5px 0;">
			<img src="$member_picture" height="75" class="rounded">
		</div>
		<div class="mycell text-start p-2 vmiddle" style="width:107px;">
			<div class="size14 blue02 weight mb-1 text-nowrap">$employee_name</div>
			<div class="size12 weight text-nowrap">$team_name</div>
			<div class="size12 weight text-nowrap">$employee_type</div>
		</div>
	</div>
</div>
EOT;


$show_disabled = "";
$show_disabled_warning = "";

if (($super_admin == "Y") && ($admin_readonly == "Y")) {
	$show_disabled = "disabled";
	$show_disabled_warning = "<div class=\"size12 red weight text-center p-2\">此區為管理人專區，非經授權請勿進行任何處理</div>";
}


$show_admin_list = "";


if ($cando == "Y") {

	$show_modify_btn = "";

		if (($powerkey == "A") || (($super_admin=="Y") && ($admin_readonly <> "Y"))) {
$show_admin_list=<<<EOT
<div class="text-center">
	<div class="btn-group me-2 mb-2" role="group">
		<a role="button" class="btn btn-light" href="javascript:void(0);" onclick="openfancybox_edit('/index.php?ch=fellowlist&project_id=$project_id&auth_id=$auth_id&pro_id=$pro_id&t=指定管理人&fm=base',850,'96%',true);" title="指定管理人"><i class="bi bi-shield-fill-check size14 red inline me-2 vmiddle"></i><div class="inline size12 me-2">指定管理人</div><div class="inline red weight vmiddle">$fellow_count</div></a>
	</div>
</div>
EOT;
		}

$show_modify_btn=<<<EOT
<div class="text-center my-2">
	<div class="btn-group me-2 mb-2" role="group">
		<button type="button" class="btn btn-success text-nowrap" onclick="myDraw();"><i class="bi bi-arrow-repeat"></i>&nbsp;重整</button>
		<button type="button" class="btn btn-warning text-nowrap" onclick="add_shortcuts('$site_db','$web_id','$templates','$project_id','$auth_id','$pjcaption','$i_caption','$fm','$memberID');"><i class="bi bi-lightning-fill red"></i>&nbsp;加入至快捷列</button>
	</div>
</div>
$show_admin_list
EOT;





$list_view=<<<EOT
<div class="w-100 m-auto p-1 mb-5 bg-white">
	<div style="width:auto;padding: 5px;">
		<div class="inline float-start me-1 mb-2">$left_menu</div>
		<a role="button" class="btn btn-light px-2 py-1 float-start inline me-3 mb-2" href="javascript:void(0);" onClick="parent.history.back();"><i class="bi bi-chevron-left"></i>&nbsp;回上頁</a>
		<a role="button" class="btn btn-light p-1" href="/">回首頁</a>$mess_title
	</div>
	<div class="container-fluid">
		<div class="row">
			<div class="col-lg-2 col-sm-12 col-md-12 p-1 d-flex flex-column justify-content-center align-items-center">
				$member_logo
			</div> 
			<div class="col-lg-8 col-sm-12 col-md-12 p-1">
				<div class="size20 pt-1 text-center">$pjt</div>
				$show_modify_btn
				$show_disabled_warning
			</div> 
			<div class="col-lg-2 col-sm-12 col-md-12">
			</div> 
		</div>
	</div>
	$show_ConfirmSending_btn
	<div id="top-scrollbar-wrapper" style="overflow-x: auto; overflow-y: hidden; width: 100%; display: none;">
		<div id="top-scrollbar-content" style="height: 1px;"></div>
	</div>
	<table class="table table-bordered border-dark w-100" id="db_table" style="min-width:1200px;">
		<thead class="table-light border-dark">
			<tr style="border-bottom: 1px solid #000;">
				<th class="text-center text-nowrap" style="width:5%;padding: 10px;background-color: #CBF3FC;">狀態(1)</th>
				<th class="text-center text-nowrap" style="width:5%;padding: 10px;background-color: #CBF3FC;">狀態(2)</th>
				<th class="text-center text-nowrap" style="width:5%;padding: 10px;background-color: #CBF3FC;">案件編號</th>
				<th class="text-center text-nowrap" style="width:10%;padding: 10px;background-color: #CBF3FC;">工程名稱</th>
				<th class="text-center text-nowrap" style="width:5%;padding: 10px;background-color: #CBF3FC;">承攬模式</th>
				<th class="text-center text-nowrap" style="width:8%;padding: 10px;background-color: #CBF3FC;">上包建商</th>
				<th class="text-center text-nowrap" style="width:8%;padding: 10px;background-color: #CBF3FC;">上包營造廠</th>
				<th class="text-center text-nowrap" style="width:5%;padding: 10px;background-color: #CBF3FC;">所屬公司</th>
				<th class="text-center text-nowrap" style="width:6%;padding: 10px;background-color: #CBF3FC;">ERP專案代號</th>
				<th class="text-center text-nowrap" style="width:5%;padding: 10px;background-color: #CBF3FC;">棟別／樓層</th>
				<th class="text-center text-nowrap" style="width:7%;padding: 10px;background-color: #CBF3FC;">預計收款</th>
				<th class="text-center text-nowrap" style="width:7%;padding: 10px;background-color: #CBF3FC;">實際收款</th>
				<th class="text-center text-nowrap" style="width:7%;padding: 10px;background-color: #CBF3FC;">未收金額</th>
				<th class="text-center text-nowrap" style="width:5%;padding: 10px;background-color: #CBF3FC;">樓層金流</th>
				<th class="text-center text-nowrap" style="width:7%;padding: 10px;background-color: #CBF3FC;">最後修改</th>
			</tr>
		</thead>
		<tbody class="table-group-divider">
			<tr>
				<td colspan="15" class="dataTables_empty">資料載入中...</td>
			</tr>
		</tbody>
	</table>
</div>
EOT;



$scroll = true;
if (!($detect->isMobile() && !$detect->isTablet())) {
	$scroll = false;
}
	
	
$show_view=<<<EOT

<style type="text/css">
#db_table {
	width: 100% !Important;
	margin: 5px 0 0 0 !Important;
}


/* 加入這段到 <style> 內 */
#top-scrollbar-wrapper {
    margin-bottom: 0px;
}

/* 配合您原本的媒體查詢，同步上方捲動條容器的寬度 */
@media screen and (min-width: 768px) {
    #top-scrollbar-wrapper {
        width: calc(100vw - 370px) !important;
        margin: 0 auto;
    }
}



/* 預設：手機版或小螢幕 (100% 寬度) */
#db_table_wrapper .dataTables_scroll {
    width: 100%; 
    margin: 0 auto;
}

/* 當螢幕寬度大於 768px 時 (桌機版) */
@media screen and (min-width: 768px) {
    #db_table_wrapper .dataTables_scroll {
        width: calc(100vw - 370px); 
        margin: 0 auto;
    }
    /* 桌機版維持不換行，讓橫向拉霸正常運作 */
    #db_table td, #db_table th {
        white-space: nowrap !important;
    }
}

/* 專門處理手機版 (小於 768px) 的換行與寬度 */
@media screen and (max-width: 767px) {
    /* 1. 允許前三欄換行，並限制最大寬度 */
    #db_table thead th:nth-child(1), #db_table tbody td:nth-child(1),
    #db_table thead th:nth-child(2), #db_table tbody td:nth-child(2),
    #db_table thead th:nth-child(3), #db_table tbody td:nth-child(3) {
        white-space: normal !important; /* 允許換行 */
        word-break: break-all !important; /* 強制長文字斷行 */
        min-width: 80px !important;      /* 設定一個最小寬度防止縮太扁 */
        max-width: 100px !important;      /* 設定最大寬度，避免佔據太多螢幕 */
        font-size: 12px !important;
        padding: 5px 2px !important;
    }

    /* 2. 修正原本程式碼中 div 的 d-flex 限制 */
    /* 因為原本有 text-nowrap 類別，要在手機版強制解除 */
    #db_table td:nth-child(-n+3) div.text-nowrap {
        white-space: normal !important;
    }

    /* 3. 調整 DataTables 凍結容器的總寬度 */
    /* 確保這三個欄位加起來的總和不會超過螢幕的一半(例如控制在 180px 內) */
    #db_table_wrapper .DTFC_LeftWrapper, 
    #db_table_wrapper .DTFC_LeftBodyLiner,
    #db_table_wrapper .DTFC_LeftHeadWrapper {
        width: 180px !important; 
    }
}

/* 其他共用樣式保持不變 */
#db_table td, #db_table th {
    background-color: #fff;
}
.dataTables_scrollBody {
    max-height: 500px !important;
    overflow-y: auto !important;
    overflow-x: auto !important;
}
</style>

$list_view

<script type="text/javascript" charset="utf-8">

	function getOffsetByDevice() {
		const screenWidth = window.innerWidth;

		if (screenWidth <= 768) {
			// 手機裝置
			return 170;
		} else if (screenWidth <= 1024) {
			// 平板裝置
			return 300;
		} else {
			// 桌機裝置
			return 300;
		}
	}

	var oTable;
	var scrollY = $(window).height() - getOffsetByDevice();
	$(document).ready(function() {

		var windowWidth = $(window).width();
    	var fixedLeftCount = (windowWidth < 768) ? 3 : 3;

		$('#db_table').dataTable( {
			"processing": true,
			"serverSide": true,
			"responsive": false,       // 必須關閉，否則會與 FixedColumns 衝突
			"scrollX": true,           // 啟用左右拉霸
			"scrollY": scrollY + "px",        // 固定高度 500px
			"scrollCollapse": true,    // 當資料筆數少時，高度自動收縮
			"paging": true,
			"pageLength": 50,
			"lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
			"pagingType": "full_numbers",  //分页样式： simple,simple_numbers,full,full_numbers
			"searching": true,  //禁用原生搜索
			"ordering": false,
			"ajaxSource": "/smarty/templates/$site_db/$templates/sub_modal/project/func07/FloorCashflowGC_ms/server_FloorCashflowGC.php?site_db=$site_db&fm=$fm",
			"language": {
						"sUrl": "$dataTable_de"
						/*"sUrl": '//cdn.datatables.net/plug-ins/1.12.1/i18n/zh-HANT.json'*/
					},
			"fixedHeader": true,
			"fixedColumns": {left:fixedLeftCount },
			"fnRowCallback": function( nRow, aData, iDisplayIndex ) { 
				var showText = function(index) {
					return (aData[index] != null && aData[index] != "") ? aData[index] : "";
				};
				var showAmount = function(index) {
					var value = parseFloat(aData[index]);
					return isNaN(value) ? "0" : number_format(Math.round(value));
				};

				$('td:eq(0)', nRow).html('<div class="text-center size12 text-nowrap">'+showText(0)+'</div>');
				$('td:eq(1)', nRow).html('<div class="text-center size12 text-nowrap">'+showText(1)+'</div>');
				$('td:eq(2)', nRow).html('<div class="text-center size12 weight text-nowrap">'+showText(2)+'</div>');
				$('td:eq(3)', nRow).html('<div class="text-center size12">'+showText(3)+'</div>');
				$('td:eq(4)', nRow).html('<div class="text-center size12 text-nowrap">'+showText(4)+'</div>');
				$('td:eq(5)', nRow).html('<div class="text-center size12">'+showText(5)+'</div>');
				$('td:eq(6)', nRow).html('<div class="text-center size12">'+showText(6)+'</div>');

				var companyName = showText(8) || showText(7);
				var companyId = showText(9);
				$('td:eq(7)', nRow).html('<div class="text-center size12">'+companyName+(companyId ? '<div class="size09">'+companyId+'</div>' : '')+'</div>');
				$('td:eq(8)', nRow).html('<div class="text-center size12 text-nowrap">'+showText(10)+'</div>');
				$('td:eq(9)', nRow).html('<div class="text-center size12 text-nowrap"><span class="weight">'+showText(11)+'</span> 棟／<span class="weight">'+showText(12)+'</span> 層</div>');
				$('td:eq(10)', nRow).html('<div class="text-end size12 text-nowrap">'+showAmount(13)+'</div>');
				$('td:eq(11)', nRow).html('<div class="text-end size12 text-nowrap blue02">'+showAmount(14)+'</div>');

				var outstanding = parseFloat(aData[15]);
				var outstandingClass = (!isNaN(outstanding) && outstanding > 0) ? 'red weight' : 'blue02';
				$('td:eq(12)', nRow).html('<div class="text-end size12 text-nowrap '+outstandingClass+'">'+showAmount(15)+'</div>');

				var url1 = "openfancybox_edit('/index.php?ch=edit&auto_seq="+showText(16)+"&fm=$fm',1900,'96%','');";
				var showBtn = '<button type="button" class="btn btn-light btn-sm text-nowrap" onclick="'+url1+'" title="樓層金流"><i class="bi bi-building"></i>&nbsp;樓層金流</button>';
				$('td:eq(13)', nRow).html('<div class="text-center">'+showBtn+'</div>');

				var lastModify = showText(18) ? '<div class="text-nowrap">'+moment(showText(18)).format('YYYY-MM-DD HH:mm')+'</div>' : '';
				var memberName = showText(19) ? '<div class="text-nowrap">'+showText(19)+'</div>' : '';
				$('td:eq(14)', nRow).html('<div class="text-center size12">'+lastModify+memberName+'</div>');


				return nRow;
			
			}
			
		});
	
		/* Init the table */
		oTable = $('#db_table').dataTable();
		
	} );

var myDel = function(auto_seq) {

	Swal.fire({
	title: "您確定要刪除此筆資料嗎?",
	text: "此項作業會刪除所有與此筆案件記錄有關的資料",
	icon: "question",
	showCancelButton: true,
	confirmButtonColor: "#3085d6",
	cancelButtonColor: "#d33",
	cancelButtonText: "取消",
	confirmButtonText: "刪除"
	}).then((result) => {
		if (result.isConfirmed) {
			xajax_DeleteRow(auto_seq);
		}
	});

};

var myDraw = function(){
	var oTable;
	oTable = $('#db_table').dataTable();
	oTable.fnDraw(false);
}

	
</script>

EOT;

} else {

	$sid = "mbwarning";
	$show_view = mywarning("很抱歉! 目前此功能只開放給本站特定會員，或是您目前的權限無法存取此頁面。");

}

?>
