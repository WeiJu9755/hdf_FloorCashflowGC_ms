<?php


//error_reporting(E_ALL); 
//ini_set('display_errors', '1');


require_once '/website/os/Mobile-Detect-2.8.34/Mobile_Detect.php';
$detect = new Mobile_Detect;

if( $detect->isMobile() && !$detect->isTablet() ){
	$isMobile = 1;
} else {
	$isMobile = 0;
}


//$fm = $_GET['fm'];
//$case_id = $_GET['case_id'];

$sure_to_delete = getlang("您確定要刪除此筆資料嗎?");

$dataTable_de = getDataTable_de();
$Prompt = getlang("提示訊息");
$Confirm = getlang("確認");
$Cancel = getlang("取消");


//取得 buildings_sub 第一筆
$buildings_sub_row = getkeyvalue2($site_db."_info","buildings_sub","case_id = '$case_id' LIMIT 1","building");
$building = $buildings_sub_row['building'];

//承攬模式為「材料買賣」或「租賃」時，才顯示「樓層合併計價」按鈕
$case_mode_row = getkeyvalue2($site_db."_info","CaseManagement","case_id = '$case_id'","ContractingModel");
$ContractingModel = trim($case_mode_row['ContractingModel']);
$show_floor_merge_btn = "";
if (in_array($ContractingModel, array("材料買賣(BH)", "租賃(RH)"), true)) {
	$show_floor_merge_btn = "<button type=\"button\" class=\"btn btn-warning btn-sm text-nowrap mb-1 ms-1\" onclick=\"openFloorMergePeriod();\"><i class=\"bi bi-layers\"></i>&nbsp;樓層合併計價</button>";
}

/*
$show_fellow_btn=<<<EOT
<div class="btn-group" role="group">
	<button type="button" class="btn btn-danger mb-1 px-4" onclick="openfancybox_edit('/index.php?ch=buildings_sub_add&case_id=$case_id&fm=$fm',800,'96%','');"><i class="bi bi-plus-circle"></i>&nbsp;新增棟別</button>
	<button type="button" class="btn btn-success text-nowrap mb-1 px-4" onclick="buildings_sub_myDraw();"><i class="bi bi-arrow-repeat"></i>&nbsp;重整</button>
</div>
EOT; 
*/

$list_view=<<<EOT
<div class="w-100 overflow-auto">
	<div class="w-100" style="min-width:1800px;">
		<div class="container-fluid">
			<div class="row">
				<div class="col-3 p-1">
					<div>
						<div class="inline size14 weight text-nowrap me-5">總棟數</div>
						<button type="button" class="btn btn-primary btn-sm text-nowrap mb-1" onclick="applyExpectedWorkQty();"><i class="bi bi-calculator"></i>&nbsp;套用預計數量/金額/收款日</button>
						$show_floor_merge_btn
					</div>
					<div>
						<table class="table table-bordered border-dark w-100" id="buildings_sub_table">
							<thead class="table-light border-dark">
								<tr style="border-bottom: 1px solid #000;">
									<th scope="col" class="text-center text-nowrap vmiddle" style="width:20%;">棟別</th>
									<th scope="col" class="text-center text-nowrap vmiddle" style="width:70%;">內容</th>
									<th scope="col" class="text-center text-nowrap vmiddle" style="width:10%;">處理</th>
								</tr>
							</thead>
							<tbody class="table-group-divider">
								<tr>
									<td colspan="3" class="dataTables_empty">資料載入中...</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div> 
				<div class="col-9 p-1">
					<div id="BuildingBox"></div>
				</div> 
			</div>
		</div>
	</div>
</div>
EOT;



$scroll = true;
if (!($detect->isMobile() && !$detect->isTablet())) {
	$scroll = false;
}


$show_buildings_sub=<<<EOT
<style>
#buildings_sub_table {
	width: 100% !Important;
	margin: 5px 0 0 0 !Important;
}
#buildings_sub_detail_table {
	width: 100% !Important;
	margin: 5px 0 0 0 !Important;
}


a:link {
  color: #20809B !important; /* 未造訪的連結 */
}

a:visited {
  color: #6610f2 !important; /* 已造訪的連結 */
}

a:hover {
  color: #0056b3 !important; /* 滑鼠懸停 */
  text-decoration: underline; /* 顯示底線（可選） */
}

a:active {
  color: #dc3545 !important; /* 點擊當下 */
}

.load-building-content {
	cursor: pointer;
}

.load-building-content:hover {
	background-color: #f8f9fa;
}

.building-summary {
	padding: 4px;
	font-size: 13px;
}

.building-summary-group + .building-summary-group {
	border-top: 1px solid #dee2e6;
	margin-top: 5px;
	padding-top: 5px;
}

.building-summary-title {
	color: #000;
	font-size: 12px;
	font-weight: 700;
	margin-bottom: 3px;
}

.building-summary-grid {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: 3px 12px;
}

.building-summary-item {
	display: flex;
	justify-content: space-between;
	align-items: baseline;
	gap: 4px;
	min-width: 0;
}

.building-summary-label {
	color: #555;
	font-size: 12px;
	white-space: nowrap;
}

.building-summary-value {
	color: #20809B;
	font-weight: 700;
	text-align: right;
	word-break: break-word;
}

@media (max-width: 767px) {
	.building-summary-grid {
		grid-template-columns: 1fr;
	}
}

</style>

$list_view

<script>
	var oTable;
	$(document).ready(function() {
		$('#buildings_sub_table').dataTable( {
			"processing": false,
			"serverSide": true,
			"responsive":  {
				details: true
			},//RWD響應式
			"scrollX": false,
			"paging": false,
			"searching": false,  //禁用原生搜索
			"ordering": false,
			"ajaxSource": "/smarty/templates/$site_db/$templates/sub_modal/project/func07/FloorCashflowGC_ms/server_buildings_sub.php?site_db=$site_db&case_id=$case_id",
			"info": false,
			"language": {
						"sUrl": "$dataTable_de"
					},
			"fixedHeader": true,
			"fixedColumns": {
        		left: 1,
    		},
			"fnRowCallback": function( nRow, aData, iDisplayIndex ) { 

				var buildings_sub_detail = "LoadBuilding('"+aData[2]+"');";


				//工程概況
				var engineering_overview = "";
				if (aData[2] != null && aData[2] != "")
					engineering_overview = aData[2];

				$('td:eq(0)', nRow).html( '<div class="d-flex justify-content-center align-items-center size12 text-start" style="height:auto;min-height:32px;"><a href="javascript:void(0);" class="blue02 weight" onclick="'+buildings_sub_detail+'">'+engineering_overview+'</a></div>' );

				// 棟別摘要：以基本、數量與單價分組，減少左側列表的垂直長度。
				var mtable = "";
					mtable = '<div class="building-summary load-building-content" onclick="'+buildings_sub_detail+'">'
						+'<div class="building-summary-group"><div class="building-summary-title">基本資料</div><div class="building-summary-grid">'
							+'<div class="building-summary-item"><span class="building-summary-label">標準層範圍</span><span class="building-summary-value">'+aData[7]+'</span></div>'
							+'<div class="building-summary-item"><span class="building-summary-label">屋突層範圍</span><span class="building-summary-value">'+aData[9]+'</span></div>'
							+'<div class="building-summary-item"><span class="building-summary-label">首層天數</span><span class="building-summary-value">'+aData[5]+'</span></div>'
							+'<div class="building-summary-item"><span class="building-summary-label">每層天數</span><span class="building-summary-value">'+aData[6]+'</span></div>'
						+'</div></div>'
						+'<div class="building-summary-group"><div class="building-summary-title">數量 (M2)</div><div class="building-summary-grid">'
							+'<div class="building-summary-item"><span class="building-summary-label">標準層</span><span class="building-summary-value">'+aData[8]+'</span></div>'
							+'<div class="building-summary-item"><span class="building-summary-label">屋突層</span><span class="building-summary-value">'+aData[10]+'</span></div>'
							+'<div class="building-summary-item"><span class="building-summary-label">放樣標準層</span><span class="building-summary-value">'+aData[11]+'</span></div>'
							+'<div class="building-summary-item"><span class="building-summary-label">放樣屋突層</span><span class="building-summary-value">'+aData[12]+'</span></div>'
						+'</div></div>'
						+'<div class="building-summary-group"><div class="building-summary-title">單價 (元)</div><div class="building-summary-grid">'
							+'<div class="building-summary-item"><span class="building-summary-label">標準層</span><span class="building-summary-value">'+aData[16]+'</span></div>'
							+'<div class="building-summary-item"><span class="building-summary-label">屋突層</span><span class="building-summary-value">'+aData[17]+'</span></div>'
							+'<div class="building-summary-item"><span class="building-summary-label">放樣標準層</span><span class="building-summary-value">'+aData[18]+'</span></div>'
							+'<div class="building-summary-item"><span class="building-summary-label">放樣屋突層</span><span class="building-summary-value">'+aData[19]+'</span></div>'
						+'</div></div>'
						+'</div>';


				$('td:eq(1)', nRow).html( mtable );


				//處理
				var url1 = "openfancybox_edit('/index.php?ch=buildings_sub_modify&auto_seq="+aData[0]+"&fm=$fm',800,'96%','');";
				//var mdel = "buildings_sub_myDel('"+aData[0]+"');";

				var show_btn = '';
					show_btn = '<div class="btn-group text-nowrap">'
						+'<button type="button" class="btn btn-light btn-sm" onclick="'+url1+'" title="編輯"><i class="bi bi-pencil-square"></i></button>'
						//+'<button type="button" class="btn btn-light btn-sm" onclick="'+mdel+'" title="刪除"><i class="bi bi-trash"></i></button>'
						+'</div>';

				$('td:eq(2)', nRow).html( '<div class="d-flex justify-content-center align-items-center text-center" style="height:auto;">'+show_btn+'</div>' );


				return nRow;
			}
			
		});
	
		/* Init the table */
		oTable = $('#buildings_sub_table').dataTable();
		
	} );
	

var buildings_sub_myDraw = function(){
	var oTable;
	oTable = $('#buildings_sub_table').dataTable();
	oTable.fnDraw(false);
}

function applyExpectedWorkQty() {
	Swal.fire({
		title: "套用預計數量/金額/收款日?",
		text: "此作業會覆蓋第一次/第二次與放樣的預計施作數量、預計收款金額、預計收款日公式欄位。",
		icon: "question",
		showCancelButton: true,
		confirmButtonColor: "#3085d6",
		cancelButtonColor: "#d33",
		cancelButtonText: "取消",
		confirmButtonText: "套用"
	}).then((result) => {
		if (result.isConfirmed) {
			xajax_apply_expected_work_qty("$case_id","$memberID");
		}
	});
}




//樓層合併計價：開啟期數設定視窗，預設帶入目前選取的棟別
var currentBuilding = "$building";
function openFloorMergePeriod() {
	openfancybox_edit('/index.php?ch=buildings_sub_period&case_id=$case_id&building='+encodeURIComponent(currentBuilding)+'&fm=$fm',800,'96%','');
}

function LoadBuilding(building) {

currentBuilding = building;

var site_db = "$site_db"; 
var case_id = "$case_id"; 
var templates = "$templates"; 
var fm = "$fm"; 
var skins = "$skins"; 

var murl = '/smarty/templates/'+site_db+'/'+templates+'/sub_modal/project/func07/FloorCashflowGC_ms/buildings_sub_detail.php';

$.ajax({
	url: murl,
	cache: false,
	dataType: 'html',
	type:'GET',
	data: { "site_db": site_db,"case_id": case_id,"building": building,"templates": templates,"fm": fm},
	success: function(response) {
		$('#BuildingBox').html(response).fadeIn();
		
	}
});

}

LoadBuilding('$building');


</script>

EOT;

?>
