<?php


/**
 * 計算用ユーティリティ
 * @author Tsunehiko.Meguro
 *
 */
class CalculateUtil {
	// private コンストラクタ instance化は許可しない
	private function __construct() {
	}

	// デストラクタ
	public function __destruct() {
	}



	public static function _primeFactors($data) {
		if (!isset($data) || empty($data)) {
			return array();
		}
		$dataCnt = count($data);
		$prime = array(2,3);
		$parse = array();
		foreach ($data as $int) {
			$work = array();
			for ($i = 2; true; ) {
				if (($int % $i) === 0) {
					$work[] = $i;
					$int = $int / $i;
				} else {
					++$i;
				}
				if ($int <= $i) {
					if($int === 1){
						$work[] =  "1";
					} else if ($int == $i) {
						$work[] =  $i;
					}
					break;
				}
			}
			$parse[] = $work;
		}
		return $parse;
	}

	/**
	 * 素因数分解を行います。
	 * @param array $data
	 * @return array 素因数分解後
	 */
	public static function primeFactors($data) {
		if (!isset($data) || empty($data)) {
			return array();
		}
		$dataCnt = count($data);
		$prime = array(2,3);
		$parse = array();
		foreach ($data as $n) {
			$work = array();
			for ($i = 0; pow($prime[$i], 2) <= $n; $i++) {
				if (!($n % $prime[$i] || $prime[$i] == $n)) {
					array_push($work, $prime[$i]);
					$n /= $prime[$i];
					$i--;
					continue;
				}
				if ($i == count($prime) -1) {
					$p = end($prime) + 2;
					for ($j = 1; pow($prime[$j], 2) <= $p; $j++) {
						if (!($p % $prime[$j])) {
							$p += 2; $j = 1;
							$j--;
							continue;
						}
					}
					array_push($prime, $p);
				}
			}
			array_push($work, $n);
			$parse[] = $work;
		}
		return $parse;
	}

	/**
	 * 素因数から最小購買数を算出します。TODO 桁あふれする場合は要対応
	 * @param array $data
	 * @return interger 最小公倍数
	 */
	public static function lcm($data) {
		if (!isset($data) || empty($data) || !is_array($data)) {
			return 0;
		}
		$retLcm = floatval("1");
		$lcmArray = array();
		$cntArray = array();
		// １つ目の配列を機銃とする
		$lcmArray = array_shift($data);
		// 素数の値と数を解析
		$cntArray = array_count_values($lcmArray);
		// 素因数のマージ
		foreach ($data as $margeData) {
			$tmpMArray = array_count_values($margeData);
			foreach ($tmpMArray as $keyVal => $valCount) {
				// 無い場合は追加・ある場合多い時は差分を追加
				if (isset($cntArray[$keyVal])) {
					// ある場合
					$crtCount = $cntArray[$keyVal];
					for ($i = 0; $i < ($valCount - $crtCount); $i++) {
						$lcmArray[] = $keyVal;
					}
				} else {
					// 無い場合
					for ($i = 0; $i < $valCount; $i++) {
						$lcmArray[] = $keyVal;
					}
				}
			}
		}

		foreach ($lcmArray as $val) {
			$retLcm *= floatval($val);
		}
		return $retLcm;
	}

}

?>
