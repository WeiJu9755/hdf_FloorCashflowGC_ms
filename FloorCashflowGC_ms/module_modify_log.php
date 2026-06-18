<?php

function updateFloorCashflowGCModifyLog($mDB, $case_id, $memberID)
{
	$case_id = addslashes(trim((string)$case_id));
	$memberID = addslashes(trim((string)$memberID));

	if ($case_id === '' || $memberID === '') {
		return;
	}

	$Qry = "INSERT INTO case_module_modify_log
			(case_id, floor_cashflow_gc_makeby, floor_cashflow_gc_last_modify)
		VALUES
			('$case_id', '$memberID', NOW())
		ON DUPLICATE KEY UPDATE
			floor_cashflow_gc_makeby = VALUES(floor_cashflow_gc_makeby),
			floor_cashflow_gc_last_modify = VALUES(floor_cashflow_gc_last_modify)";
	$mDB->query($Qry);
}

function updateFloorCashflowGCModifyLogByBuildingSeq($mDB, $auto_seq, $memberID)
{
	$auto_seq = addslashes(trim((string)$auto_seq));
	$Qry = "SELECT case_id FROM buildings_sub WHERE auto_seq = '$auto_seq' LIMIT 1";
	$mDB->query($Qry);
	if ($mDB->rowCount() > 0) {
		$row = $mDB->fetchRow(2);
		updateFloorCashflowGCModifyLog($mDB, $row['case_id'], $memberID);
	}
}

function updateFloorCashflowGCModifyLogByDetailSeq($mDB, $auto_seq, $memberID)
{
	$auto_seq = addslashes(trim((string)$auto_seq));
	$Qry = "SELECT case_id FROM buildings_sub_detail WHERE auto_seq = '$auto_seq' LIMIT 1";
	$mDB->query($Qry);
	if ($mDB->rowCount() > 0) {
		$row = $mDB->fetchRow(2);
		updateFloorCashflowGCModifyLog($mDB, $row['case_id'], $memberID);
	}
}
