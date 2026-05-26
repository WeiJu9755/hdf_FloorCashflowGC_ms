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

				//表格化
				var mtable = "";
					mtable = '<div class="mytable w-100 size14">'
						+'<div class="myrow w-100"><div class="mycell text-nowrap w-75 px-1">標準層範圍：</div><div class="mycell text-end px-1 text-nowrap blue02 weight">'+aData[7]+'</div></div>'
						+'<div class="myrow w-100"><div class="mycell text-nowrap px-1">標準層數量(M2)：</div><div class="mycell text-end px-1 text-nowrap blue02 weight">'+aData[8]+'</div></div>'
						+'<div class="myrow w-100"><div class="mycell text-nowrap px-1">屋突層範圍：</div><div class="mycell text-end px-1 text-nowrap blue02 weight">'+aData[9]+'</div></div>'
						+'<div class="myrow w-100"><div class="mycell text-nowrap px-1">屋突層數量(M2)：</div><div class="mycell text-end px-1 text-nowrap blue02 weight">'+aData[10]+'</div></div>'
						+'<div class="myrow w-100"><div class="mycell text-nowrap px-1">放樣標準層數量(M2)：</div><div class="mycell text-end px-1 text-nowrap blue02 weight">'+aData[11]+'</div></div>'
						+'<div class="myrow w-100"><div class="mycell text-nowrap px-1">放樣屋突層數量(M2)：</div><div class="mycell text-end px-1 text-nowrap blue02 weight">'+aData[12]+'</div></div>'
						+'<div class="myrow w-100"><div class="mycell text-nowrap px-1">首層施作天數：</div><div class="mycell text-end px-1 text-nowrap blue02 weight">'+aData[5]+'</div></div>'
						+'<div class="myrow w-100"><div class="mycell text-nowrap px-1">每層施作天數：</div><div class="mycell text-end px-1 text-nowrap blue02 weight">'+aData[6]+'</div></div>'
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




function LoadBuilding(building) {

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
