<?php

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

$xajax->registerFunction("processform");
function processform($aFormValues){

	$objResponse = new xajaxResponse();
	
	$memberID							= trim($aFormValues['memberID']);
	$auto_seq							= trim($aFormValues['auto_seq']);
	$template_estimated_working_days	= trim($aFormValues['template_estimated_working_days']);
	$expected_submission_date			= trim($aFormValues['expected_submission_date']);
	$delivery_date						= trim($aFormValues['delivery_date']);
	$expected_grouting_date				= trim($aFormValues['expected_grouting_date']);
	$expected_actual_delivery_date		= trim($aFormValues['expected_actual_delivery_date']);
	$expected_actual_grouting_date		= trim($aFormValues['expected_actual_grouting_date']);
	$actual_submission_date				= trim($aFormValues['actual_submission_date']);
	$actual_grouting_date				= trim($aFormValues['actual_grouting_date']);
	$application_status					= trim($aFormValues['application_status']);
	
	/*
	if (trim($aFormValues['engineering_overview']) == "")	{
		$objResponse->script("jAlert('警示', '請輸入工程概況', 'red', '', 2000);");
		return $objResponse;
		exit;
	}
	*/

	//存入實體資料庫中
	$mDB = "";
	$mDB = new MywebDB();

	$Qry="UPDATE buildings_sub_detail set
			 template_estimated_working_days	= '$template_estimated_working_days'
			,expected_submission_date	= '$expected_submission_date'
			,delivery_date	= '$delivery_date'
			,expected_grouting_date	= '$expected_grouting_date'
			,expected_actual_delivery_date	= '$expected_actual_delivery_date'
			,expected_actual_grouting_date	= '$expected_actual_grouting_date'
			,actual_submission_date	= '$actual_submission_date'
			,actual_grouting_date= '$actual_grouting_date'
			,application_status	= '$application_status'
			,makeby	= '$memberID'
			,last_modify	= now()
			where auto_seq = '$auto_seq'";
			
	$mDB->query($Qry);
	$mDB->remove();

	$objResponse->script("setSave();");
	$objResponse->script("parent.buildings_sub_detail_myDraw();");

	$objResponse->script("art.dialog.tips('已存檔!',1);");
	$objResponse->script("parent.$.fancybox.close();");
		
	
	return $objResponse;
}

$xajax->processRequest();


$auto_seq = $_GET['auto_seq'];


$mDB = "";
$mDB = new MywebDB();


$fm = $_GET['fm'];

$mess_title = $title;

$Qry="SELECT * FROM buildings_sub_detail
WHERE auto_seq = '$auto_seq'";
$mDB->query($Qry);


$total = $mDB->rowCount();
if ($total > 0) {
    //已找到符合資料
	$row=$mDB->fetchRow(2);
	$case_id = $row['case_id'];
	$building = $row['building'];
	$floor = $row['floor'];
	$template_estimated_working_days = $row['template_estimated_working_days'];
	$expected_submission_date = $row['expected_submission_date'];
	$delivery_date = $row['delivery_date'];
	$expected_grouting_date = $row['expected_grouting_date'];
	$expected_actual_delivery_date = $row['expected_actual_delivery_date'];
	$expected_actual_grouting_date = $row['expected_actual_grouting_date'];
	$actual_submission_date = $row['actual_submission_date'];
	$actual_grouting_date = $row['actual_grouting_date'];
	$application_status = $row['application_status'];

}


$mDB->remove();



$show_savebtn=<<<EOT
<div class="btn-group vbottom" role="group" style="margin-top:5px;">
	<button id="save" class="btn btn-primary" type="button" onclick="CheckValue(this.form);" style="padding: 5px 15px;"><i class="bi bi-check-circle"></i>&nbsp;存檔</button>
	<button id="cancel" class="btn btn-secondary display_none" type="button" onclick="setCancel();" style="padding: 5px 15px;"><i class="bi bi-x-circle"></i>&nbsp;取消</button>
	<button id="close" class="btn btn-danger" type="button" onclick="parent.buildings_sub_detail_myDraw();parent.$.fancybox.close();" style="padding: 5px 15px;"><i class="bi bi-power"></i>&nbsp;關閉</button>
</div>
EOT;


if (!($detect->isMobile() && !$detect->isTablet())) {
	$isMobile = 0;
	
$style_css=<<<EOT
<style>

.card_full {
    width: 100%;
	height: 100vh;
}

#full {
    width: 100%;
	height: 100%;
}

#info_container {
	width: 100% !Important;
	max-width: 800px; !Important;
	margin: 0 auto !Important;
}

.field_div1 {width:200px;display: none;font-size:18px;color:#000;text-align:right;font-weight:700;padding:15px 10px 0 0;vertical-align: top;display:inline-block;zoom: 1;*display: inline;}
.field_div2 {width:100%;max-width:500px;display: none;font-size:18px;color:#000;text-align:left;font-weight:700;padding:8px 0 0 0;vertical-align: top;display:inline-block;zoom: 1;*display: inline;}

</style>

EOT;

} else {
	$isMobile = 1;

$style_css=<<<EOT
<style>

.card_full {
    width: 100%;
	height: 100vh;
}

#full {
    width: 100%;
	height: 100%;
}

#info_container {
	width: 100% !Important;
	margin: 0 auto !Important;
}

.field_div1 {width:100%;display: block;font-size:18px;color:#000;text-align:left;font-weight:700;padding:15px 10px 0 0;vertical-align: top;}
.field_div2 {width:100%;display: block;font-size:18px;color:#000;text-align:left;font-weight:700;padding:8px 10px 0 0;vertical-align: top;}

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
			<div class="w-100 mb-5">
				<div class="field_container3">
					<div class="container-fluid">
						<div class="row">
							<div class="col-lg-12 col-sm-12 col-md-12">
								<div class="field_div1">樓層:</div>
								<div class="field_div2">
									<div class="size12 weight blue01 pt-1">$floor</div>
								</div> 
							</div> 
						</div>
					</div>
					<div class="container-fluid">
						<div class="row">
							<div class="col-lg-12 col-sm-12 col-md-12">
								<div class="field_div1">模板預計工作日:</div> 
								<div class="field_div2">
									<input type="text" class="form-control" name="template_estimated_working_days" value="$template_estimated_working_days" style="width:100%;max-width:100px;" onchange="setEdit();">
								</div> 
							</div> 
						</div>
					</div>
					<div class="container-fluid">
						<div class="row">
							<div class="col-lg-12 col-sm-12 col-md-12">
								<div class="field_div1">預計交版日期:</div> 
								<div class="field_div2">
									<div class="input-group" id="expected_submission_date" style="width:100%;max-width:250px;">
										<input type="text" class="form-control" name="expected_submission_date" aria-describedby="expected_submission_date" value="$expected_submission_date" onchange="setEdit();">
										<button class="btn btn-outline-secondary input-group-append input-group-addon" type="button" data-target="#expected_submission_date" data-toggle="datetimepicker"><i class="bi bi-calendar"></i></button>
									</div>
									<script type="text/javascript">
										$(function () {
											$('#expected_submission_date').datetimepicker({
												locale: 'zh-tw'
												,format:"YYYY-MM-DD"
												,allowInputToggle: true
											});
										});
									</script>
								</div> 
							</div> 
						</div>
					</div>
					<div class="container-fluid">
						<div class="row">
							<div class="col-lg-12 col-sm-12 col-md-12">
								<div class="field_div1">(交版日)+N天:</div> 
								<div class="field_div2">
									<input type="text" class="form-control" name="delivery_date" value="$delivery_date" style="width:100%;max-width:100px;" onchange="setEdit();">
								</div> 
							</div> 
						</div>
					</div>
					<div class="container-fluid">
						<div class="row">
							<div class="col-lg-12 col-sm-12 col-md-12">
								<div class="field_div1">預計灌漿日期:</div> 
								<div class="field_div2">
									<div class="input-group" id="expected_grouting_date" style="width:100%;max-width:250px;">
										<input type="text" class="form-control" name="expected_grouting_date" aria-describedby="expected_grouting_date" value="$expected_grouting_date" onchange="setEdit();">
										<button class="btn btn-outline-secondary input-group-append input-group-addon" type="button" data-target="#expected_grouting_date" data-toggle="datetimepicker"><i class="bi bi-calendar"></i></button>
									</div>
									<script type="text/javascript">
										$(function () {
											$('#expected_grouting_date').datetimepicker({
												locale: 'zh-tw'
												,format:"YYYY-MM-DD"
												,allowInputToggle: true
											});
										});
									</script>
								</div> 
							</div> 
						</div>
					</div>
					<div class="container-fluid">
						<div class="row">
							<div class="col-lg-12 col-sm-12 col-md-12">
								<div class="field_div1">預計+實際交版日期:</div> 
								<div class="field_div2">
									<div class="input-group" id="expected_actual_delivery_date" style="width:100%;max-width:250px;">
										<input type="text" class="form-control" name="expected_actual_delivery_date" aria-describedby="expected_actual_delivery_date" value="$expected_actual_delivery_date" onchange="setEdit();">
										<button class="btn btn-outline-secondary input-group-append input-group-addon" type="button" data-target="#expected_actual_delivery_date" data-toggle="datetimepicker"><i class="bi bi-calendar"></i></button>
									</div>
									<script type="text/javascript">
										$(function () {
											$('#expected_actual_delivery_date').datetimepicker({
												locale: 'zh-tw'
												,format:"YYYY-MM-DD"
												,allowInputToggle: true
											});
										});
									</script>
								</div> 
							</div> 
						</div>
					</div>
					<div class="container-fluid">
						<div class="row">
							<div class="col-lg-12 col-sm-12 col-md-12">
								<div class="field_div1">預計+實際灌漿日期:</div> 
								<div class="field_div2">
									<div class="input-group" id="expected_actual_grouting_date" style="width:100%;max-width:250px;">
										<input type="text" class="form-control" name="expected_actual_grouting_date" aria-describedby="expected_actual_grouting_date" value="$expected_actual_grouting_date" onchange="setEdit();">
										<button class="btn btn-outline-secondary input-group-append input-group-addon" type="button" data-target="#expected_actual_grouting_date" data-toggle="datetimepicker"><i class="bi bi-calendar"></i></button>
									</div>
									<script type="text/javascript">
										$(function () {
											$('#expected_actual_grouting_date').datetimepicker({
												locale: 'zh-tw'
												,format:"YYYY-MM-DD"
												,allowInputToggle: true
											});
										});
									</script>
								</div> 
							</div> 
						</div>
					</div>
					<div class="container-fluid">
						<div class="row">
							<div class="col-lg-12 col-sm-12 col-md-12">
								<div class="field_div1">實際交版日期:</div> 
								<div class="field_div2">
									<div class="input-group" id="actual_submission_date" style="width:100%;max-width:250px;">
										<input type="text" class="form-control" name="actual_submission_date" aria-describedby="actual_submission_date" value="$actual_submission_date" onchange="setEdit();">
										<button class="btn btn-outline-secondary input-group-append input-group-addon" type="button" data-target="#actual_submission_date" data-toggle="datetimepicker"><i class="bi bi-calendar"></i></button>
									</div>
									<script type="text/javascript">
										$(function () {
											$('#actual_submission_date').datetimepicker({
												locale: 'zh-tw'
												,format:"YYYY-MM-DD"
												,allowInputToggle: true
											});
										});
									</script>
								</div> 
							</div> 
						</div>
					</div>
					<div class="container-fluid">
						<div class="row">
							<div class="col-lg-12 col-sm-12 col-md-12">
								<div class="field_div1">實際灌漿日期:</div> 
								<div class="field_div2">
									<div class="input-group" id="actual_grouting_date" style="width:100%;max-width:250px;">
										<input type="text" class="form-control" name="actual_grouting_date" aria-describedby="actual_grouting_date" value="$actual_grouting_date" onchange="setEdit();">
										<button class="btn btn-outline-secondary input-group-append input-group-addon" type="button" data-target="#actual_grouting_date" data-toggle="datetimepicker"><i class="bi bi-calendar"></i></button>
									</div>
									<script type="text/javascript">
										$(function () {
											$('#actual_grouting_date').datetimepicker({
												locale: 'zh-tw'
												,format:"YYYY-MM-DD"
												,allowInputToggle: true
											});
										});
									</script>
								</div> 
							</div> 
						</div>
					</div>
					<div class="container-fluid">
						<div class="row">
							<div class="col-lg-12 col-sm-12 col-md-12">
								<div class="field_div1">施作狀況:</div> 
								<div class="field_div2">
									<input type="text" class="form-control" name="application_status" value="$application_status" style="width:100%;" onchange="setEdit();">
								</div> 
							</div> 
						</div>
					</div>
					<div>
						<input type="hidden" name="fm" value="$fm" />
						<input type="hidden" name="site_db" value="$site_db" />
						<input type="hidden" name="memberID" value="$memberID" />
						<input type="hidden" name="auto_seq" value="$auto_seq" />
					</div>
				</div>
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
<script>

	var series_select_list = JSON.parse('$series_select_list');

	$( '.select2' ).select2( {
		theme: "bootstrap-5",
		data: series_select_list,
		width: $( this ).data( 'width' ) ? $( this ).data( 'width' ) : $( this ).hasClass( 'w-100' ) ? '100%' : 'style',
		placeholder: $( this ).data( 'placeholder' ),
		closeOnSelect: false,
		selectionCssClass: 'select2--large',
    	dropdownCssClass: 'select2--large',
	} );	

	var series_feed_type_list = JSON.parse('$series_feed_type_list');
	$("#feed_type").val(series_feed_type_list).select2();

</script>
<script>

	var series_select_return_type_list = JSON.parse('$series_select_return_type_list');

	$( '.select3' ).select2( {
		theme: "bootstrap-5",
		data: series_select_return_type_list,
		width: $( this ).data( 'width' ) ? $( this ).data( 'width' ) : $( this ).hasClass( 'w-100' ) ? '100%' : 'style',
		placeholder: $( this ).data( 'placeholder' ),
		closeOnSelect: false,
		selectionCssClass: 'select2--large',
    	dropdownCssClass: 'select2--large',
	} );	

	var series_return_type_list = JSON.parse('$series_return_type_list');
	$("#return_type").val(series_return_type_list).select2();

</script>
EOT;

?>