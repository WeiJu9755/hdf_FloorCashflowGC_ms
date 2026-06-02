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
							<th scope="col" class="text-center text-nowrap vmiddle" style="width:10%;">施作數量</th>
							<th scope="col" class="text-center text-nowrap vmiddle" style="width:5%;">預計<br>收款金額</th>
							<th scope="col" class="text-center text-nowrap vmiddle" style="width:5%;">扣抵<br>保留款</th>
							<th scope="col" class="text-center text-nowrap vmiddle" style="width:5%;">扣抵<br>預收款</th>
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
							<td colspan="18" class="dataTables_empty">資料載入中...</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div> 
	</div>
</div>
EOT;



$show_buildings_sub_detail=<<<EOT
<style>
#buildings_sub_detail_table {
	width: 100% !Important;
	margin: 5px 0 0 0 !Important;
}
#buildings_sub_detail_table_wrapper .dataTables_scroll {
	width: 100%;
	margin: 0 auto;
}
#buildings_sub_detail_table th,
#buildings_sub_detail_table td {
	white-space: nowrap !important;
}
#buildings_sub_detail_table_wrapper .dataTables_scrollBody {
	overflow-y: auto !important;
	overflow-x: auto !important;
}
.work-qty-box,
.collection-amount-box {
	line-height: 1.7;
	text-align: left;
	white-space: nowrap;
}
.work-qty-box {
	min-width: 260px;
}
.collection-amount-box {
	min-width: 320px;
}
.work-qty-line,
.collection-amount-line {
	font-size: 12px;
	color: #0f172a;
}
.work-qty-label,
.collection-amount-label {
	font-weight: 700;
	color: #475569;
}
.work-qty-value,
.collection-amount-value {
	display: inline-block;
	font-weight: 700;
	text-align: right;
}
.first-stage-value {
	color: #2563eb;
}
.second-stage-value {
	color: #b45309;
}
.work-qty-value {
	min-width: 58px;
}
.collection-amount-value {
	min-width: 82px;
}
.work-qty-gap,
.collection-amount-gap {
	display: inline-block;
	width: 18px;
}
</style>

$list_view

<script>
	function getDetailOffsetByDevice() {
		const screenWidth = window.innerWidth;

		if (screenWidth <= 768) {
			return 170;
		} else if (screenWidth <= 1024) {
			return 260;
		} else {
			return 260;
		}
	}

	var oTable;
	var detailScrollY = $(window).height() - getDetailOffsetByDevice();

	$(document).ready(function() {
		$('#buildings_sub_detail_table').dataTable( {
			"processing": false,
			"serverSide": true,
			"responsive": false,
			"scrollX": true,
			"scrollY": detailScrollY + "px",
			"scrollCollapse": true,
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
				function showText(idx) {
					return (aData[idx] != null && aData[idx] != "") ? aData[idx] : "";
				}

				function showDate(idx) {
					return (aData[idx] != null && aData[idx] != "" && aData[idx] != "0000-00-00") ? aData[idx] : "";
				}

				function showAmount(idx) {
					if (aData[idx] == null || aData[idx] == "" || aData[idx] == 0) {
						return "";
					}
					return number_format(aData[idx]);
				}

				function showWorkQty(idx) {
					var values = showText(idx).split('||');
					while (values.length < 4) {
						values.push('');
					}
					for (var i = 0; i < values.length; i++) {
						values[i] = (values[i] != null && values[i] != '' && values[i] != 0) ? number_format(values[i], 2) : '';
					}
					return '<div class="work-qty-box">'
						+'<div class="work-qty-line"><span class="work-qty-label">第一次占比：</span><span class="work-qty-value first-stage-value">'+values[0]+'</span><span class="work-qty-gap"></span><span class="work-qty-label">第一次放樣：</span><span class="work-qty-value first-stage-value">'+values[1]+'</span></div>'
						+'<div class="work-qty-line"><span class="work-qty-label">第二次占比：</span><span class="work-qty-value second-stage-value">'+values[2]+'</span><span class="work-qty-gap"></span><span class="work-qty-label">第二次放樣：</span><span class="work-qty-value second-stage-value">'+values[3]+'</span></div>'
						+'</div>';
				}

				function showCollectionAmount(idx) {
					var values = showText(idx).split('||');
					while (values.length < 4) {
						values.push('');
					}
					for (var i = 0; i < values.length; i++) {
						values[i] = (values[i] != null && values[i] != '' && values[i] != 0) ? number_format(values[i], 2) : '';
					}
					return '<div class="collection-amount-box">'
						+'<div class="collection-amount-line"><span class="collection-amount-label">第一次占比：</span><span class="collection-amount-value first-stage-value">'+values[0]+'</span><span class="collection-amount-gap"></span><span class="collection-amount-label">第一次放樣：</span><span class="collection-amount-value first-stage-value">'+values[1]+'</span></div>'
						+'<div class="collection-amount-line"><span class="collection-amount-label">第二次占比：</span><span class="collection-amount-value second-stage-value">'+values[2]+'</span><span class="collection-amount-gap"></span><span class="collection-amount-label">第二次放樣：</span><span class="collection-amount-value second-stage-value">'+values[3]+'</span></div>'
						+'</div>';
				}

				function showCollectionDate(idx) {
					var values = showText(idx).split('||');
					while (values.length < 4) {
						values.push('');
					}
					for (var i = 0; i < values.length; i++) {
						values[i] = (values[i] != null && values[i] != '' && values[i] != '0000-00-00') ? values[i] : '';
					}
					return '<div class="collection-amount-box">'
						+'<div class="collection-amount-line"><span class="collection-amount-label">第一次之一：</span><span class="collection-amount-value first-stage-value">'+values[0]+'</span><span class="collection-amount-gap"></span><span class="collection-amount-label">第一次之二：</span><span class="collection-amount-value first-stage-value">'+values[1]+'</span></div>'
						+'<div class="collection-amount-line"><span class="collection-amount-label">第二次之一：</span><span class="collection-amount-value second-stage-value">'+values[2]+'</span><span class="collection-amount-gap"></span><span class="collection-amount-label">第二次之二：</span><span class="collection-amount-value second-stage-value">'+values[3]+'</span></div>'
						+'</div>';
				}

				function showFourStageDate(idx) {
					var values = showText(idx).split('||');
					while (values.length < 4) {
						values.push('');
					}
					for (var i = 0; i < values.length; i++) {
						values[i] = (values[i] != null && values[i] != '' && values[i] != '0000-00-00') ? values[i] : '';
					}
					return '<div class="collection-amount-box">'
						+'<div class="collection-amount-line"><span class="collection-amount-label">第一次占比：</span><span class="collection-amount-value first-stage-value">'+values[0]+'</span><span class="collection-amount-gap"></span><span class="collection-amount-label">第一次放樣：</span><span class="collection-amount-value first-stage-value">'+values[1]+'</span></div>'
						+'<div class="collection-amount-line"><span class="collection-amount-label">第二次占比：</span><span class="collection-amount-value second-stage-value">'+values[2]+'</span><span class="collection-amount-gap"></span><span class="collection-amount-label">第二次放樣：</span><span class="collection-amount-value second-stage-value">'+values[3]+'</span></div>'
						+'</div>';
				}

				//樓層
				var floor = showText(0);

				$('td:eq(0)', nRow).html( '<div class="d-flex justify-content-center align-items-center size12 text-center" style="height:auto;min-height:32px;">'+floor+'</div>' );

				//預計+實際交版日期
				var expected_actual_delivery_date = showDate(1);

				$('td:eq(1)', nRow).html( '<div class="d-flex justify-content-center align-items-center size12 text-center" style="height:auto;min-height:32px;">'+expected_actual_delivery_date+'</div>' );
			
				//預計+實際灌漿日期
				var expected_actual_grouting_date = showDate(2);

				$('td:eq(2)', nRow).html( '<div class="d-flex justify-content-center align-items-center size12 text-center" style="height:auto;min-height:32px;">'+expected_actual_grouting_date+'</div>' );

				//施作數量
				var work_qty = showWorkQty(3);

				$('td:eq(3)', nRow).html( '<div class="d-flex justify-content-center align-items-center size12 text-center" style="height:auto;min-height:32px;">'+work_qty+'</div>' );

				//預計收款金額
				var expected_collection_amount = showCollectionAmount(4);

				$('td:eq(4)', nRow).html( '<div class="d-flex justify-content-center align-items-center size12 text-center" style="height:auto;min-height:32px;">'+expected_collection_amount+'</div>' );

				//扣抵保留款
				var retention_deduction_amount = showAmount(5);

				$('td:eq(5)', nRow).html( '<div class="d-flex justify-content-center align-items-center size12 text-end" style="height:auto;min-height:32px;">'+retention_deduction_amount+'</div>' );

				//扣抵預收款
				var advance_payment_deduction_amount = showAmount(6);

				$('td:eq(6)', nRow).html( '<div class="d-flex justify-content-center align-items-center size12 text-end" style="height:auto;min-height:32px;">'+advance_payment_deduction_amount+'</div>' );

				//預計收款日
				var expected_collection_date = showCollectionDate(7);

				$('td:eq(7)', nRow).html( '<div class="d-flex justify-content-center align-items-center size12 text-center" style="height:auto;min-height:32px;">'+expected_collection_date+'</div>' );

				//實際交版日期
				var actual_submission_date = showDate(8);

				$('td:eq(8)', nRow).html( '<div class="d-flex justify-content-center align-items-center size12 text-center" style="height:auto;min-height:32px;">'+actual_submission_date+'</div>' );

				//實際灌漿日期
				var actual_grouting_date = showDate(9);

				$('td:eq(9)', nRow).html( '<div class="d-flex justify-content-center align-items-center size12 text-center" style="height:auto;min-height:32px;">'+actual_grouting_date+'</div>' );

				//實際計價日
				var actual_billing_date = showFourStageDate(10);

				$('td:eq(10)', nRow).html( '<div class="d-flex justify-content-center align-items-center size12 text-center" style="height:auto;min-height:32px;">'+actual_billing_date+'</div>' );

				//計價階段
				var project_progress = showText(11);

				$('td:eq(11)', nRow).html( '<div class="d-flex justify-content-start align-items-center size12 text-start" style="height:auto;min-height:32px;">'+project_progress+'</div>' );

				//計價(施作)數量
				var completed_qty = showText(12);

				$('td:eq(12)', nRow).html( '<div class="d-flex justify-content-center align-items-center size12 text-center" style="height:auto;min-height:32px;">'+completed_qty+'</div>' );

				//實際收款金額
				var actual_collection_amount = showCollectionAmount(13);

				$('td:eq(13)', nRow).html( '<div class="d-flex justify-content-center align-items-center size12 text-center" style="height:auto;min-height:32px;">'+actual_collection_amount+'</div>' );

				//實際收款日
				var actual_collection_date = showFourStageDate(14);

				$('td:eq(14)', nRow).html( '<div class="d-flex justify-content-center align-items-center size12 text-center" style="height:auto;min-height:32px;">'+actual_collection_date+'</div>' );

				//收款階段
				var payment_request_stage = showText(15);

				$('td:eq(15)', nRow).html( '<div class="d-flex justify-content-start align-items-center size12 text-start" style="height:auto;min-height:32px;">'+payment_request_stage+'</div>' );

				//備註
				var remark = showText(16);

				$('td:eq(16)', nRow).html( '<div class="d-flex justify-content-start align-items-center size12 text-start" style="height:auto;min-height:32px;">'+remark+'</div>' );

				//編輯
				var url1 = "openfancybox_edit('/index.php?ch=buildings_sub_detail_modify&auto_seq="+aData[17]+"&fm=$fm',800,'96%','');";
				//var mdel = "buildings_sub_detail_myDel('"+aData[15]+"');";

				var show_btn = '';
					show_btn = '<div class="btn-group text-nowrap">'
						+'<button type="button" class="btn btn-light" onclick="'+url1+'" title="編輯"><i class="bi bi-pencil-square"></i></button>'
						//+'<button type="button" class="btn btn-light" onclick="'+mdel+'" title="刪除"><i class="bi bi-trash"></i></button>'
						+'</div>';
				
				$('td:eq(17)', nRow).html( '<div class="d-flex justify-content-center align-items-center text-center" style="height:auto;">'+show_btn+'</div>' );

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
