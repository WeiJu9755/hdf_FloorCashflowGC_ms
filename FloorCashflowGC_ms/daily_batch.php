<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

$isCronChild = getenv('HDF_CRONTAB_CHILD') === '1';
if (PHP_SAPI !== 'cli' && !$isCronChild) {
	http_response_code(403);
	exit("Cron or CLI only\n");
}

$options = (PHP_SAPI === 'cli') ? getopt('', array('site-db:', 'member-id::')) : array();
$site_db = getenv('HDF_SITE_DB');
if ($site_db === false || $site_db === '') {
	$site_db = isset($options['site-db']) ? trim($options['site-db']) : '';
}
$memberID = getenv('HDF_AUTOMATION_MEMBER_ID');
if ($memberID === false || $memberID === '') {
	$memberID = isset($options['member-id']) ? trim($options['member-id']) : 'SYSTEM_AUTO';
}

if (!preg_match('/^[A-Za-z0-9_]+$/', $site_db)) {
	file_put_contents('php://stderr', "Missing or invalid site database name\n");
	exit(2);
}

$_SESSION = array(
	'memberID' => $memberID,
	'powerkey' => 'A',
);

define('HDF_AUTOMATION_LIBRARY_MODE', true);
require_once __DIR__.'/buildings_modify.php';

$lockName = 'hdf_floor_gc_'.$site_db;
$lockDB = new MywebDB();
$lockAcquired = false;

try {
	$lockDB->query("SELECT GET_LOCK('".addslashes($lockName)."', 0) AS acquired");
	$lockRow = $lockDB->fetchRow(2);
	$lockAcquired = isset($lockRow['acquired']) && (int)$lockRow['acquired'] === 1;
	if (!$lockAcquired) {
		throw new RuntimeException('FloorCashflowGC batch is already running.');
	}

	$workDB = new MywebDB();
	$workDB->query("SELECT c.case_id
		FROM CaseManagement c
		WHERE c.status1 = '已簽約'
		   OR (c.status1 = '未簽約' AND c.status2 = '已回簽')
		ORDER BY c.case_id");
	$caseIds = array();
	while ($row = $workDB->fetchRow(2)) {
		$caseIds[] = $row['case_id'];
	}
	$workDB->remove();

	foreach ($caseIds as $caseId) {
		apply_expected_work_qty($caseId, $memberID);
	}

	echo json_encode(array(
		'module' => 'floor_cashflow_gc',
		'status' => 'success',
		'cases' => count($caseIds),
	), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
} catch (Throwable $e) {
	file_put_contents('php://stderr', $e->getMessage().PHP_EOL);
	exit(1);
}
