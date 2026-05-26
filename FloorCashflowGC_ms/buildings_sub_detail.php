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


//載入公用函數
@include_once '/website/include/pub_function.php';

//取得預設值
$xml_lang=simplexml_load_file("/website/locale/locale.xml");

function getlang($key) {
	$gb_xml = $GLOBALS['xml_lang'];
	//$myLang = $_COOKIE["lang"];
	$gb_row_web = $GLOBALS['row_web_lang'];
	$myLang = $gb_row_web["appId"];
	$result = $gb_xml->xpath('//LOCALES/LOCALE[@key="'.$key.'"]')[0][$myLang];
	if (isnullorempty($result))
		$result = $key;
	return $result;
}

function getDataTable_de() {
	//$myLang = $_COOKIE["lang"];
	$gb_row_web = $GLOBALS['row_web_lang'];
	$myLang = $gb_row_web["appId"];
	if ($myLang == "en_US") {
		$dataTable_de = "/pub_style/de_US.txt";
	} else if ($myLang == "zh_CN") {
		$dataTable_de = "/pub_style/de_CN.txt";
	} else if ($myLang == "ja_JP") {
		$dataTable_de = "/pub_style/de_JP.txt";
	} else {
		$dataTable_de = "/pub_style/de_TW.txt";
	}
	return $dataTable_de;
}


$fm = $_GET['fm'];
$site_db = $_GET['site_db'];
$templates = $_GET['templates'];
$case_id = $_GET['case_id'];
$building = $_GET['building'];



$dataTable_de = getDataTable_de();

$sure_to_delete = getlang("您確定要刪除此筆資料嗎?");
$Prompt = getlang("提示訊息");
$Confirm = getlang("確認");
$Cancel = getlang("取消");


$list_view=<<<EOT
<div class="container-fluid">
	<div class="row">
		<div class="col-lg-12 col-sm-12 col-md-12">
			<div>
				<div class="inline size14 weight text-nowrap">棟別：</div>
				<div class="inline size14 weight blue01 text-nowrap me-5">$building</div>
			</div>
			<div>
				<table class="table table-bordered border-dark w-100" id="buildings_sub_detail_table">
					<thead class="table-light border-dark">
						<tr style="border-bottom: 1px solid #000;">
							<th scope="col" class="text-center text-nowrap vmiddle" style="width:5%;">樓層</th>
							<th scope="col" class="text-center text-nowrap vmiddle" style="width:5%;">預計+實際<br>交版日期</th>
							<th scope="col" class="text-center text-nowrap vmiddle" style="width:5%;">預計+實際<br>灌漿日期</th>
							<th scope="col" class="text-center text-nowrap vmiddle" style="width:5%;">預計<br>施作數量</th>
							<th scope="col" class="text-center text-nowrap vmiddle" style="width:5%;">預計<br>收款金額</th>
							<th scope="col" class="text-center text-nowrap vmiddle" style="width:5%;">預計收款日</th>
							<th scope="col" class="text-center text-nowrap vmiddle" style="width:5%;">實際<br>交版日期</th>
							<th scope="col" class="text-center text-nowrap vmiddle" style="width:5%;">實際<br>灌漿日期</th>
							<th scope="col" class="text-center text-nowrap vmiddle" style="width:5%;">實際計價日</th>
							<th scope="col" class="text-center text-nowrap vmiddle" style="width:5%;">計價階段</th>
							<th scope="col" class="text-center text-nowrap vmiddle" style="width:5%;">計價(施作)數量</th>
							<th scope="col" class="text-center text-nowrap vmiddle" style="width:5%;">實際<br>收款金額</th>
							<th scope="col" class="text-center text-nowrap vmiddle" style="width:5%;">實際<br>收款日</th>
							<th scope="col" class="text-center text-nowrap vmiddle" style="width:5%;">收款階段</th>
							<th scope="col" class="text-center text-nowrap vmiddle" style="width:5%;">備註</th>
							<th scope="col" class="text-center text-nowrap vmiddle" style="width:5%;">處理</th>
						</tr>
					</thead>
					<tbody class="table-group-divider">
						<tr>
							<td colspan="16" class="dataTables_empty">資料載入中...</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div> 
	</div>
</div>
EOT;



$scroll = true;
if (!($detect->isMobile() && !$detect->isTablet())) {
	$scroll = false;
}


$show_buildings_sub_detail=<<<EOT
<style>
#buildings_sub_detail_table {
	width: 100% !Important;
	margin: 5px 0 0 0 !Important;
}
#buildings_sub_detail_table {
	width: 100% !Important;
	margin: 5px 0 0 0 !Important;
}
</style>

$list_view

<script>
	var oTable;
	$(document).ready(function() {
		$('#buildings_sub_detail_table').dataTable( {
			"processing": false,
			"serverSide": true,
			"responsive":  {
				details: true
			},//RWD響應式
			"scrollX": '$scroll',
			"paging": false,
			"searching": false,  //禁用原生搜索
			"ordering": false,
			"ajaxSource": "/smarty/templates/$site_db/$templates/sub_modal/project/func07/FloorCashflowGC_ms/server_buildings_sub_detail.php?site_db=$site_db&case_id=$case_id&building=$building",
			"info": false,
			"language": {
						"sUrl": "$dataTable_de"
					},
			"fixedHeader": true,
			"fixedColumns": {
        		left: 1,
    		},
			"fnRowCallback": function( nRow, aData, iDisplayIndex ) { 

				//樓層
				var floor = "";
				if (aData[0] != null && aData[0] != "")
					floor = aData[0];

				$('td:eq(0)', nRow).html( '<div class="d-flex justify-content-center align-items-center size12 text-center" style="height:auto;min-height:32px;">'+floor+'</div>' );

				//模板預計工作日
				var template_estimated_working_days = "";
				if (aData[1] != null && aData[1] != "")
					template_estimated_working_days = aData[1];

				$('td:eq(1)', nRow).html( '<div class="d-flex justify-content-center align-items-center size12 text-center" style="height:auto;min-height:32px;">'+template_estimated_working_days+'</div>' );
			
				//預計交版日期
				var expected_submission_date = "";
				if (aData[2] != null && aData[2] != "" && aData[2] != "0000-00-00")
					expected_submission_date = aData[2];

				$('td:eq(2)', nRow).html( '<div class="d-flex justify-content-center align-items-center size12 text-center" style="height:auto;min-height:32px;">'+expected_submission_date+'</div>' );

				//(交版日)+N天
				var delivery_date = "";
				if (aData[3] != null && aData[3] != "")
					delivery_date = aData[3];

				$('td:eq(3)', nRow).html( '<div class="d-flex justify-content-center align-items-center size12 text-center" style="height:auto;min-height:32px;">'+delivery_date+'</div>' );

				//預計灌漿日期
				var expected_grouting_date = "";
				if (aData[4] != null && aData[4] != "" && aData[4] != "0000-00-00")
					expected_grouting_date = aData[4];

				$('td:eq(4)', nRow).html( '<div class="d-flex justify-content-center align-items-center size12 text-center" style="height:auto;min-height:32px;">'+expected_grouting_date+'</div>' );

				//預計+實際交版日期
				var expected_actual_delivery_date = "";
				if (aData[5] != null && aData[5] != "" && aData[5] != "0000-00-00")
					expected_actual_delivery_date = aData[5];

				$('td:eq(5)', nRow).html( '<div class="d-flex justify-content-center align-items-center size12 text-center" style="height:auto;min-height:32px;">'+expected_actual_delivery_date+'</div>' );

				//預計+實際灌漿日期
				var expected_actual_grouting_date = "";
				if (aData[6] != null && aData[6] != "" && aData[6] != "0000-00-00")
					expected_actual_grouting_date = aData[6];

				$('td:eq(6)', nRow).html( '<div class="d-flex justify-content-center align-items-center size12 text-center" style="height:auto;min-height:32px;">'+expected_actual_grouting_date+'</div>' );

				//實際交版日期
				var actual_submission_date = "";
				if (aData[7] != null && aData[7] != "" && aData[7] != "0000-00-00")
					actual_submission_date = aData[7];

				$('td:eq(7)', nRow).html( '<div class="d-flex justify-content-center align-items-center size12 text-center" style="height:auto;min-height:32px;">'+actual_submission_date+'</div>' );

				//實際灌漿日期
				var actual_grouting_date = "";
				if (aData[8] != null && aData[8] != "" && aData[8] != "0000-00-00")
					actual_grouting_date = aData[8];

				$('td:eq(8)', nRow).html( '<div class="d-flex justify-content-center align-items-center size12 text-center" style="height:auto;min-height:32px;">'+actual_grouting_date+'</div>' );

				//施作狀況
				var application_status = "";
				if (aData[9] != null && aData[9] != "")
					application_status = aData[9];

				$('td:eq(9)', nRow).html( '<div class="d-flex justify-content-start align-items-center size12 text-start" style="height:auto;min-height:32px;">'+application_status+'</div>' );

				//編輯
				var url1 = "openfancybox_edit('/index.php?ch=buildings_sub_detail_modify&auto_seq="+aData[10]+"&fm=$fm',800,'96%','');";
				//var mdel = "buildings_sub_detail_myDel('"+aData[10]+"');";

				var show_btn = '';
					show_btn = '<div class="btn-group text-nowrap">'
						+'<button type="button" class="btn btn-light" onclick="'+url1+'" title="編輯"><i class="bi bi-pencil-square"></i></button>'
						//+'<button type="button" class="btn btn-light" onclick="'+mdel+'" title="刪除"><i class="bi bi-trash"></i></button>'
						+'</div>';
				
				$('td:eq(15)', nRow).html( '<div class="d-flex justify-content-center align-items-center text-center" style="height:auto;">'+show_btn+'</div>' );

				return nRow;
			}
			
		});
	
		
		oTable = $('#buildings_sub_detail_table').dataTable();
		
	} );
	

var buildings_sub_detail_myDel = function(auto_seq) {

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


var buildings_sub_detail_myDraw = function(){
	var oTable;
	oTable = $('#buildings_sub_detail_table').dataTable();
	oTable.fnDraw(false);
}


</script>

EOT;

echo $show_buildings_sub_detail;

?>