<?php

session_start();
$memberID = $_SESSION['memberID'];
$powerkey = $_SESSION['powerkey'];


require_once '/website/os/Mobile-Detect-2.8.34/Mobile_Detect.php';
$detect = new Mobile_Detect;


@include_once("/website/class/".$site_db."_info_class.php");

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
		$scheduled_entry_date			= trim($aFormValues['scheduled_entry_date']);
		$actual_entry_date				= trim($aFormValues['actual_entry_date']);
		$construction_days_first_floor	= trim($aFormValues['construction_days_first_floor']);
		$construction_days_per_floor	= trim($aFormValues['construction_days_per_floor']);
		$std_layer_floor				= trim($aFormValues['std_layer_floor']);
		$roof_protrusion_floor			= trim($aFormValues['roof_protrusion_floor']);
		$outsourcing					= trim($aFormValues['outsourcing']);

		
		//存入實體資料庫中
		$mDB = "";
		$mDB = new MywebDB();
	  
		$Qry="UPDATE buildings_sub set
				 scheduled_entry_date	= '$scheduled_entry_date'
				,actual_entry_date	= '$actual_entry_date'
				,construction_days_first_floor	= '$construction_days_first_floor'
				,construction_days_per_floor	= '$construction_days_per_floor'
				,std_layer_floor	= '$std_layer_floor'
				,roof_protrusion_floor	= '$roof_protrusion_floor'
				,outsourcing	= '$outsourcing'
				,makeby	= '$memberID'
				,last_modify	= now()
				where auto_seq = '$auto_seq'";
				
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
	$roof_protrusion_floor = $row['roof_protrusion_floor'];
	$outsourcing = $row['outsourcing'];
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
					<div>
						<div class="field_div1">棟別:</div> 
						<div class="field_div2">
							<!--
							<input type="text" class="inputtext" id="building" name="building" size="20" style="width:100%;max-width:80px;" value="$building" onchange="setEdit();">
							-->
							<div class="size14 weight blue01">$building</div>
						</div> 
					</div>
					<div>
						<div class="field_div1">本棟預計起始日:</div> 
						<div class="field_div2">
							<div class="input-group" id="scheduled_entry_date" style="width:100%;max-width:250px;">
								<input type="text" class="form-control" name="scheduled_entry_date" aria-describedby="scheduled_entry_date" value="$scheduled_entry_date" onchange="setEdit();">
								<button class="btn btn-outline-secondary input-group-append input-group-addon" type="button" data-target="#scheduled_entry_date" data-toggle="datetimepicker"><i class="bi bi-calendar"></i></button>
							</div>
							<script type="text/javascript">
								$(function () {
									$('#scheduled_entry_date').datetimepicker({
										locale: 'zh-tw'
										,format:"YYYY-MM-DD"
										,allowInputToggle: true
									});
								});
							</script>
						</div> 
					</div>
					<div>
						<div class="field_div1">本棟實際起始日:</div> 
						<div class="field_div2">
							<div class="input-group" id="actual_entry_date" style="width:100%;max-width:250px;">
								<input type="text" class="form-control" name="actual_entry_date" aria-describedby="actual_entry_date" value="$actual_entry_date" onchange="setEdit();">
								<button class="btn btn-outline-secondary input-group-append input-group-addon" type="button" data-target="#actual_entry_date" data-toggle="datetimepicker"><i class="bi bi-calendar"></i></button>
							</div>
							<script type="text/javascript">
								$(function () {
									$('#actual_entry_date').datetimepicker({
										locale: 'zh-tw'
										,format:"YYYY-MM-DD"
										,allowInputToggle: true
									});
								});
							</script>
						</div> 
					</div>
					<div>
						<div class="field_div1">首層施作天數:</div> 
						<div class="field_div2">
							<input type="text" class="inputtext" id="construction_days_first_floor" name="construction_days_first_floor" size="20" style="width:100%;max-width:80px;" value="$construction_days_first_floor" onchange="setEdit();"/>
						</div> 
					</div>
					<div>
						<div class="field_div1">每層施作天數:</div> 
						<div class="field_div2">
							<input type="text" class="inputtext" id="construction_days_per_floor" name="construction_days_per_floor" size="20" style="width:100%;max-width:80px;" value="$construction_days_per_floor" onchange="setEdit();"/>
						</div> 
					</div>
					<div>
						<div class="field_div1">標準層範圍:</div> 
						<div class="field_div2">
							<input type="text" class="inputtext" id="std_layer_floor" name="std_layer_floor" size="20" style="width:100%;max-width:400px;" value="$std_layer_floor" onchange="setEdit();"/>
						</div> 
					</div>
					<div>
						<div class="field_div1">屋突層範圍:</div> 
						<div class="field_div2">
							<input type="text" class="inputtext" id="roof_protrusion_floor" name="roof_protrusion_floor" size="20" style="width:100%;max-width:400px;" value="$roof_protrusion_floor" onchange="setEdit();"/>
						</div> 
					</div>
					<div>
						<div class="field_div1">代工數:</div> 
						<div class="field_div2">
							<input type="text" class="inputtext" id="outsourcing" name="construction_days_fioutsourcingrst_floor" size="20" style="width:100%;max-width:80px;" value="$outsourcing" onchange="setEdit();"/>
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