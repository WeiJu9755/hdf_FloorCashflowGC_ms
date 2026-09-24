<?php

/**
 * 依匯入的政府辦公日曆判斷工作日。
 *
 * 有匯入該日期時，以 holiday_calendar.is_workday 為準，因此可正確處理
 * 國定假日、補假及週末補班；沒有資料時退回週一至週五的既有規則。
 */

if (!function_exists('holiday_calendar_is_valid_date')) {
	function holiday_calendar_is_valid_date($date) {
		$date = substr(trim((string)$date), 0, 10);
		if ($date == '' || $date == '0000-00-00') {
			return false;
		}

		$d = DateTime::createFromFormat('Y-m-d', $date);
		return $d && $d->format('Y-m-d') === $date;
	}
}

if (!function_exists('holiday_calendar_load_year')) {
	function holiday_calendar_load_year($year) {
		static $year_cache = array();

		$year = (int)$year;
		if (array_key_exists($year, $year_cache)) {
			return $year_cache[$year];
		}

		$year_cache[$year] = array();
		if ($year < 1900 || $year > 2200 || !class_exists('MywebDB')) {
			return $year_cache[$year];
		}

		$mDB = new MywebDB();
		$mDB->query("SELECT 1
			FROM information_schema.TABLES
			WHERE TABLE_SCHEMA = DATABASE()
			  AND TABLE_NAME = 'holiday_calendar'
			LIMIT 1");

		if ($mDB->rowCount() > 0) {
			$mDB->query("SELECT calendar_date, is_workday
				FROM holiday_calendar
				WHERE calendar_year = '$year'");

			while ($row = $mDB->fetchRow(2)) {
				$calendar_date = substr((string)$row['calendar_date'], 0, 10);
				if (holiday_calendar_is_valid_date($calendar_date)) {
					$year_cache[$year][$calendar_date] = ((int)$row['is_workday'] === 1);
				}
			}
		}

		$mDB->remove();
		return $year_cache[$year];
	}
}

if (!function_exists('holiday_calendar_is_workday')) {
	function holiday_calendar_is_workday($date) {
		if (!holiday_calendar_is_valid_date($date)) {
			return false;
		}

		$date = substr((string)$date, 0, 10);
		$calendar = holiday_calendar_load_year((int)substr($date, 0, 4));
		if (array_key_exists($date, $calendar)) {
			return $calendar[$date];
		}

		// 尚未匯入該年度或日期時，維持原本只避開週末的行為。
		$d = new DateTime($date);
		return (int)$d->format('N') <= 5;
	}
}

if (!function_exists('holiday_calendar_move_to_next_workday')) {
	function holiday_calendar_move_to_next_workday($date) {
		if (!holiday_calendar_is_valid_date($date)) {
			return '';
		}

		$d = new DateTime(substr((string)$date, 0, 10));
		for ($i = 0; $i < 370; $i++) {
			$current_date = $d->format('Y-m-d');
			if (holiday_calendar_is_workday($current_date)) {
				return $current_date;
			}
			$d->modify('+1 day');
		}

		return '';
	}
}

