<?php
/**
 * 日時用汎用クラス
*/
class DatetimeUtil {
	public static function convGtJDate($src) {
		list($year, $month, $day) = explode('/', $src);
		if (!@checkdate($month, $day, $year) || $year < 1869 || strlen($year) !== 4
				|| strlen($month) !== 2 || strlen($day) !== 2) return false;
				$date = str_replace('/', '', $src);
				if ($date >= 19890108) {
					$gengo = '平成';
					$wayear = $year - 1988;
				} elseif ($date >= 19261225) {
					$gengo = '昭和';
					$wayear = $year - 1925;
				} elseif ($date >= 19120730) {
					$gengo = '大正';
					$wayear = $year - 1911;
				} else {
					$gengo = '明治';
					$wayear = $year - 1868;
				}
				switch ($wayear) {
					case 1:
						$wadate = $gengo.'元年'.$month.'月'.$day.'日';
						break;
					default:
						$wadate = $gengo.sprintf("%02d", $wayear).'年'.$month.'月'.$day.'日';
				}
				return $wadate;
	}
	
	public static function convJtGDate($src, $format = 'Y-m-d') {
		$tmp = mb_convert_kana($src, "n");
		$a = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '0');
		$g = mb_substr($tmp, 0, 2, 'UTF-8');
		array_unshift($a, $g);
		
		// error_log("G = " . $g . "\n", 3, "/tmp/debug.log");
		// error_log("REPL = " . str_replace($a, '', $tmp) . "\n", 3, "/tmp/debug.log");
		
		if (($g !== '明治' && $g !== '大正' && $g !== '昭和' && $g !== '平成')
				|| (str_replace($a, '', $tmp) !== '年月日' && str_replace($a, '', $tmp) !== '元年月日')) return false;
		
		$y = strtok(str_replace($g, '', $tmp), '年月日');
		$m = sprintf("%02d", strtok('年月日'));
		$d = sprintf("%02d", strtok('年月日'));
		if (mb_strpos($tmp, '元年') !== false) {
			$y = 1;
		}
		if ($g === '平成') {
			$y += 1988;
		} else if ($g === '昭和') {
			$y += 1925;
		} else if ($g === '大正') {
			$y += 1911;
		} else if ($g === '明治') {
			$y += 1868;
		}
		if (strlen($y) !== 4 || strlen($m) !== 2 || strlen($d) !== 2 || !@checkdate($m, $d, $y)) {
			// error_log("FORMAT ERROR = " . $y.$m.$d . "\n", 3, "/tmp/debug.log");
			return false;
		}
		// error_log("PRE RETURN = " . $y.$m.$d . "\n", 3, "/tmp/debug.log");
		return date($format, strtotime($y.'/'.$m.'/'.$d));
	}
	
}