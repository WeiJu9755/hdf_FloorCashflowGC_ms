<?php

	//載入公用函數
	@include_once '/website/include/pub_function.php';

	$site_db = isset($_GET['site_db']) ? $_GET['site_db'] : "";
	$case_id = isset($_GET['case_id']) ? $_GET['case_id'] : "";
	$building = isset($_GET['building']) ? $_GET['building'] : "";
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Easy set variables 詮能
	 */
	
	/* Array of database columns which should be read and sent back to DataTables. Use a space where
	 * you want to insert a non-database field (for example a counter or static image)
	 */
	$aColumns = array( 'a.floor','a.expected_actual_delivery_date','a.expected_actual_grouting_date','a.first_expected_work_qty','a.first_layout_expected_work_qty','a.second_expected_work_qty','a.second_layout_expected_work_qty','a.first_expected_collection_amount','a.first_layout_expected_collection_amount','a.second_expected_collection_amount','a.second_layout_expected_collection_amount','a.retention_deduction_amount','a.advance_payment_deduction_amount','a.first_expected_collection_date_1','a.first_expected_collection_date_2','a.second_expected_collection_date_1','a.second_expected_collection_date_2','a.actual_submission_date','a.actual_grouting_date','a.actual_billing_date','a.first_actual_billing_date','a.first_layout_actual_billing_date','a.second_actual_billing_date','a.second_layout_actual_billing_date','a.project_progress','b.completed_qty','a.actual_collection_amount','a.first_actual_collection_amount','a.first_layout_actual_collection_amount','a.second_actual_collection_amount','a.second_layout_actual_collection_amount','a.actual_collection_date','a.first_actual_collection_date','a.first_layout_actual_collection_date','a.second_actual_collection_date','a.second_layout_actual_collection_date','a.payment_request_stage','a.remark','a.auto_seq');
	
	/* Indexed column (used for fast and accurate table cardinality) */
	$sIndexColumn = "auto_seq";
	
	/* DB table to use */
	$sTable = "buildings_sub_detail";
	
//	include( $_SERVER['DOCUMENT_ROOT']."/class/products_db.php" );
	include( "/website/class/".$site_db."_db.php" );
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * If you just want to use the basic configuration for DataTables with PHP server-side, there is
	 * no need to edit below this line
	 */
	
	/* 
	 * MySQL connection
	 */
	$gaSql['link'] =  mysql_pconnect( $gaSql['server'], $gaSql['user'], $gaSql['password'] ) or
		die( 'Could not open connection to server' );
	
	mysql_select_db( $gaSql['db'], $gaSql['link'] ) or 
		die( 'Could not select database '. $gaSql['db'] );

	$case_id_sql = mysql_real_escape_string($case_id);
	$building_sql = mysql_real_escape_string($building);
	
	/* 
	 * Paging
	 */
	$sLimit = "";
	if ( isset( $_GET['iDisplayStart'] ) && isset( $_GET['iDisplayLength'] ) && $_GET['iDisplayLength'] != '-1' )
	{
		$sLimit = "LIMIT ".mysql_real_escape_string( $_GET['iDisplayStart'] ).", ".
			mysql_real_escape_string( $_GET['iDisplayLength'] );
	}
	
	
	/*
	 * Ordering
	 */
	$sOrder = "ORDER BY
		CASE
			WHEN a.floor REGEXP '^R[0-9]+F$' THEN 1
			ELSE 0
		END,
		CAST(REPLACE(REPLACE(a.floor, 'R', ''), 'F', '') AS UNSIGNED) ";
	/*
	if ( isset( $_GET['iSortCol_0'] ) )
	{
		$sOrder = "ORDER BY  ";
		for ( $i=0 ; $i<intval( $_GET['iSortingCols'] ) ; $i++ )
		{
			if ( $_GET[ 'bSortable_'.intval($_GET['iSortCol_'.$i]) ] == "true" )
			{
				$sOrder .= $aColumns[ intval( $_GET['iSortCol_'.$i] ) ]."
				 	".mysql_real_escape_string( $_GET['sSortDir_'.$i] ) .", ";
			}
		}
		
		$sOrder = substr_replace( $sOrder, "", -2 );
		if ( $sOrder == "ORDER BY" )
		{
			$sOrder = "";
		}
	}
	*/
	
	/* 
	 * Filtering
	 * NOTE this does not match the built-in DataTables filtering which does it
	 * word by word on any field. It's possible to do here, but concerned about efficiency
	 * on very large tables, and MySQL's regex functionality is very limited
	 */
	$sWhere = "";
	$sSearch = isset($_GET['sSearch']) ? $_GET['sSearch'] : "";
	if ( $sSearch != "" )
	{
		$sWhere = "WHERE (";
		for ( $i=0 ; $i<count($aColumns) ; $i++ )
		{
			$sWhere .= $aColumns[$i]." LIKE '%".mysql_real_escape_string( $sSearch )."%' OR ";
		}
		$sWhere = substr_replace( $sWhere, "", -3 );
		$sWhere .= ')';
	}
	
	/* Individual column filtering */
	for ( $i=0 ; $i<count($aColumns) ; $i++ )
	{
		$bSearchable = isset($_GET['bSearchable_'.$i]) ? $_GET['bSearchable_'.$i] : "false";
		$sSearchColumn = isset($_GET['sSearch_'.$i]) ? $_GET['sSearch_'.$i] : "";
		if ( $bSearchable == "true" && $sSearchColumn != '' )
		{
			if ( $sWhere == "" )
			{
				$sWhere = "WHERE ";
			}
			else
			{
				$sWhere .= " AND ";
			}
			$sWhere .= $aColumns[$i]." LIKE '%".mysql_real_escape_string($sSearchColumn)."%' ";
		}
	}
	
	/*
	 * SQL queries
	 * Get data to display
	 */

	
	if ($sWhere=="")
		$sWhere = "WHERE a.case_id = '$case_id_sql' AND a.building = '$building_sql' ";
	else
		$sWhere .= " and a.case_id = '$case_id_sql' AND a.building = '$building_sql' ";
	
	 
	$sQuery = "
		SELECT SQL_CALC_FOUND_ROWS ".str_replace(" , ", " ", implode(", ", $aColumns))."
		FROM   $sTable a
		LEFT JOIN pjprogress_sub b ON b.case_id = a.case_id AND b.building = a.building AND b.floor = a.floor
		$sWhere
		$sOrder
		$sLimit
	";
	$rResult = mysql_query( $sQuery, $gaSql['link'] ) or die(mysql_error());
	
	/* Data set length after filtering */
	$sQuery = "
		SELECT FOUND_ROWS()
	";
	$rResultFilterTotal = mysql_query( $sQuery, $gaSql['link'] ) or die(mysql_error());
	$aResultFilterTotal = mysql_fetch_array($rResultFilterTotal);
	$iFilteredTotal = $aResultFilterTotal[0];
	
	/* Total data set length */
	$sQuery = "
		SELECT COUNT(".$sIndexColumn.")
		FROM   $sTable
	";
	$rResultTotal = mysql_query( $sQuery, $gaSql['link'] ) or die(mysql_error());
	$aResultTotal = mysql_fetch_array($rResultTotal);
	$iTotal = $aResultTotal[0];
	
	
	/*
	 * Output
	 */
	$output = array(
		"sEcho" => isset($_GET['sEcho']) ? intval($_GET['sEcho']) : 0,
		"iTotalRecords" => $iTotal,
		"iTotalDisplayRecords" => $iFilteredTotal,
		"aaData" => array()
	);
	
	while ( $aRow = mysql_fetch_array( $rResult ) )
	{
		$row = array();
		$row[] = $aRow['floor'];
		$row[] = $aRow['expected_actual_delivery_date'];
		$row[] = $aRow['expected_actual_grouting_date'];
		$row[] = $aRow['first_expected_work_qty']."||".$aRow['first_layout_expected_work_qty']."||".$aRow['second_expected_work_qty']."||".$aRow['second_layout_expected_work_qty'];
		$row[] = $aRow['first_expected_collection_amount']."||".$aRow['first_layout_expected_collection_amount']."||".$aRow['second_expected_collection_amount']."||".$aRow['second_layout_expected_collection_amount'];
		$row[] = $aRow['retention_deduction_amount'];
		$row[] = $aRow['advance_payment_deduction_amount'];
		$row[] = $aRow['first_expected_collection_date_1']."||".$aRow['first_expected_collection_date_2']."||".$aRow['second_expected_collection_date_1']."||".$aRow['second_expected_collection_date_2'];
		$row[] = $aRow['actual_submission_date'];
		$row[] = $aRow['actual_grouting_date'];
		$actual_billing_dates = array(
			$aRow['first_actual_billing_date'],
			$aRow['first_layout_actual_billing_date'],
			$aRow['second_actual_billing_date'],
			$aRow['second_layout_actual_billing_date']
		);
		if (implode("", $actual_billing_dates) == "" && $aRow['actual_billing_date'] != "") {
			$actual_billing_dates[0] = $aRow['actual_billing_date'];
		}
		$row[] = implode("||", $actual_billing_dates);
		$row[] = $aRow['project_progress'];
		$row[] = $aRow['completed_qty'];
		$actual_collection_amounts = array(
			$aRow['first_actual_collection_amount'],
			$aRow['first_layout_actual_collection_amount'],
			$aRow['second_actual_collection_amount'],
			$aRow['second_layout_actual_collection_amount']
		);
		if (implode("", $actual_collection_amounts) == "" && $aRow['actual_collection_amount'] != "") {
			$actual_collection_amounts[0] = $aRow['actual_collection_amount'];
		}
		$row[] = implode("||", $actual_collection_amounts);
		$actual_collection_dates = array(
			$aRow['first_actual_collection_date'],
			$aRow['first_layout_actual_collection_date'],
			$aRow['second_actual_collection_date'],
			$aRow['second_layout_actual_collection_date']
		);
		if (implode("", $actual_collection_dates) == "" && $aRow['actual_collection_date'] != "") {
			$actual_collection_dates[0] = $aRow['actual_collection_date'];
		}
		$row[] = implode("||", $actual_collection_dates);
		$row[] = $aRow['payment_request_stage'];
		$row[] = $aRow['remark'];
		$row[] = $aRow['auto_seq'];
		$output['aaData'][] = $row;
	}
	
	echo json_encode( $output );

?>
