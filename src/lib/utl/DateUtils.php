<?php

/**
 * DateUtils
 */

class DateUtils
{
	private $logger;

    /**
     * 月初の日付を取得する
     * @param string $month 'Y-m' or null
     * @param string $format date format on return value
     * @return string 月初の日付
     */
    public static function getBeginningOfMonth($month, $format)
    {
        return date($format, strtotime('first day of ' . $month));
    }

    /**
     * 月末の日付を取得する
     * @param string $month 'Y-m' or null
     * @param string $format date format on return value
     * @return string 月末の日付
     */
    public static function getEndOfMonth($month, $format)
    {
        return date($format, strtotime('last day of ' . $month));
    }

    /**
     * 日付の形式が正しいかチェックする
     * @param string $date
     * @return boolean true:valid, false:invalid
     */
    public static function isValidDate($date)
    {
        if (empty($date)) {
            return false;
        }

        if (strtotime($date) === false) {
            return false;
        }

        $dt = new DateTime($date);

        $y = $dt->format('Y');
        $m = $dt->format('m');
        $d = $dt->format('d');

        return checkdate($m, $d, $y);
    }

    /**
     * 誕生日日付をYMDにパースする
     * @param int $birthday
     * @return array 'Y' => $y, 'm' => $m, 'd' => $d
     */
    public static function parseBirthdayToYMD($birthday)
    {
        if (empty($birthday)) {
            return false;
        }
        
        try {
            $dt = new DateTime($birthday);

            $y = $dt->format('Y');
            $m = $dt->format('m');
            $d = $dt->format('d');

            return array('Y' => $y, 'm' => $m, 'd' => $d);
        } catch (Exception $e) {
            return false;
        }
    }
	
	/**
	 * $dayを基準に次の日を返す
	 * @param type $day
	 * @param type $format
	 * @return type
	 */
	public static function nextDay($day, $format = "Ymd") {
		$base = strtotime($day);
		return date($format, strtotime("+1 day", $base));
	}
	
	/**
	 * $dayを基準に、前日を返す
	 * @param type $day
	 * @param type $format
	 * @return type
	 */
	public static function previousDay($day, $format = "Ymd") {
		$base = strtotime($day);
		return date($format, strtotime("-1 day", $base));
	}
	
	/**
	 * $endDay を基準に、次の１週間の始まりと終わりを返す。
	 * @param type $endDay
	 * @param type $format
	 * @return type array('start' => $start, 'end' => $end);
	 */
	public static function next7($endDay, $format = "Ymd") {
		$base = strtotime($endDay);
		$start = date($format, strtotime("+1 day", $base));
		$end = date($format, strtotime("+7 day", $base));
		
		return array('start' => $start, 'end' => $end);
		
	}
	
	/**
	 * $endDayを基準に、前の一週間の始まりを終わりを返す
	 * @param type $endDay
	 * @param type $format
	 * @return type array('start' => $start, 'end' => $end);
	 */
	public static function previous7($endDay, $format = "Ymd") {
		$base = strtotime($endDay);
		$start = date($format, strtotime("-14 day", $base));
		$end = date($format, strtotime("-7 day", $base));
		
		return array('start' => $start, 'end' => $end);
	}
	
	public static function current7($endDay, $format = "Ymd") {
		$base = strtotime($endDay);
		$start = date($format, strtotime("-6 day", $base)); // endが最終日なので、-6
		$end = date($format, strtotime($endDay));
		
		return array('start' => $start, 'end' => $end);
	}
	
	/**
	 * YYYY/MMの日付を開始日と終了日の配列で返す
	 * @param type $month
	 * @param type $format
	 * @param type $incrementEnd
	 * @return type
	 */
	public static function monthDays($month, $format = "Ymd", $incrementEnd = false) {
		
		// error_log("MONTH = " . $month . " INC = " . $incrementEnd . "\n", 3, "/tmp/dateutil.log");
		$day = $month;
		if (strlen($day) < 8) {
			$day = sprintf("%s%02d", $month, 1);
		}
		// error_log("DAY = " . $day . "\n", 3, "/tmp/dateutil.log");
		
		$tmpStart = date('Y-m', strtotime($day)) . '-01';
		$start = date($format, strtotime($tmpStart));
		// error_log("START = " . $start . "\n", 3, "/tmp/dateutil.log");
		$timeEnd = strtotime(date('Y-m-t', strtotime($start)));
		// error_log("TIMEEND = " . $timeEnd . "\n", 3, "/tmp/dateutil.log");
		
		if ($incrementEnd) {
			// error_log("TIMEEND2 = " . $timeEnd . "\n", 3, "/tmp/dateutil.log");
			$timeEnd = strtotime($timeEnd, "+1 day");
		}
		$end = date($format, $timeEnd);
		return array('start' => $start, 'end' => $end);
		
	}
	
	/**
	 * $monthを基準に、翌月を返す
	 * @param type $month
	 * @param type $format
	 * @return type
	 */
	public static function nextMonth($month, $format = "Ymd") {
		
		$day = $month;
		if (strlen($day) < 8) {
			$day = sprintf("%s%02d", $month, 1);
		}
		$d = new DateTime($day);
		$d->add(new DateInterval('P1M'));
		
		return $d->format($format);
	}

	/**
	 * $monthを基準に前月を返す
	 * @param type $month
	 * @param type $format
	 * @return type
	 */
	public static function previousMonth($month, $format = "Ymd") {

		$day = $month;
		if (strlen($day) < 8) {
			$day = sprintf("%s%02d", $month, 1);
		}
		$d = new DateTime($day);
		$d->sub(new DateInterval('P1M'));
		
		return $d->format($format);
	}	

	
	public static function diffDay($datetime1, $datetime2) {
		$d1 = new DateTime($datetime1);
		$d2 = new DateTime($datetime2);
		
		$diff = $d1->diff($d2);
		return $diff->format('%a');
	}
	
	public static function diffMonth($datetime1, $datetime2) {
		$d1 = new DateTime($datetime1);
		$d2 = new DateTime($datetime2);
		
		$diff = $d1->diff($d2);
		return $diff->format('%m');		
	}
	
	public static function mmyyTommddyy($mmyy) {
		$t = explode("/", $mmyy);
		if (strlen($t[0]) == 4) {
			return sprintf('%s/01', $mmyy); // YYYY/mm
		} else {
			return sprintf('%s/01/%s', $t[0], $t[1]); // mm/YYYY
		}
	}
	
	public static function changeDateOptionFromDate($date) {
		$year = date('Y', strtotime($date));
		$month = date('m', strtotime($date));
		$day = date('d', strtotime($date));
		$dateOption = array('year' => $year,'month' => $month,'day' => $day);
		return $dateOption;
	}

	public static function changeDateFromDateOption($dateOption) {
		if (!isset($dateOption['year']) || !isset($dateOption['month']) || !isset($dateOption['day'])) {
			return date();
		}
		return sprintf("%02d/%02d/%d", $dateOption['month'], $dateOption['day'], $dateOption['year']);
	}
	
	public static function calcDate($baseDate, $diff, $format = 'Y-m-d') {
		$date = new DateTime($baseDate);
		$date->modify($diff . ' days');
		return $date->format($format);
	}
	
	
}
