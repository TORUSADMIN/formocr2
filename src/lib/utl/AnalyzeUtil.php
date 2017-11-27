<?php


/**
 * 解析処理系の共通処理を実装します。
 *
 * @author Tsunehiko.Meguro
 *
 */
class AnalyzeUtil {

	// private コンストラクタ instance化は許可しない
	private function __construct() {
	}

	// デストラクタ
	public function __destruct() {
	}

	/**
	 * 前後の全角・半角空白を除去します。
	 *
	 * @param string $str
	 * @return string 除去後
	 */
	public static function trimEx($str) {
			if (!isset($str) || empty($str)) {
			return $str;
		}
		$retArry = preg_match("/^[ 　]*(.*)[ 　]*\s*$/u", $str, $matches);
		if (count($matches) == 2) {
			return $matches[1];
		} else {
			return $str;
		}
	}

	/**
	 * 文字列中の全角・半角空白を除去します。
	 *
	 * @param strint $str
	 * @return string 除去結果
	 */
	public static function deleteSp($str) {
		if (!isset($str) || empty($str)) {
			return $str;
		}
		$retStr = preg_replace("/[ 　]/u", "", $str);
		return $retStr;
	}

	/**
	 * 文字列中の改行を除去します。
	 *
	 * @param strint $str
	 * @return string 除去結果
	 */
	public static function deleteCrLf($str) {
		if (!isset($str) || empty($str)) {
			return $str;
		}
		$retStr = preg_replace("/[\r|\n]/u", "", $str);
		return $retStr;
	}

	/**
	 * 文字列中の全角・半角空白、改行を除去します。
	 *
	 * @param strint $str
	 * @return string 除去結果
	 */
	public static function deleteSpCrLf($str) {
		// 空白の除去
		$retStr = AnalyzeUtil::deleteCrLf($str);
		// 改行の削除
		$retStr = AnalyzeUtil::deleteSp($retStr);
		return $retStr;
	}

	/**
	 * 文字列から数字を取り出し、半角数字で返します。
	 * 取出す文字列は連続した数字のみなり、文字列中に２か所以上数字が
	 * 有る場合、最初の数字を取り出します。
	 * @param string $str
	 * @return string データ(数字が無い場合は空)
	 */
	public static function getNumeric($str) {
		if (!isset($str) || empty($str)) {
			return "";
		}
		if (preg_match("/.*?([0-9０１２３４５６７８９]+).*$/us", $str, $matches)) {
			if (count($matches) > 1) {
				return  mb_convert_kana($matches[1], 'n', "UTF-8");
			}
		}
		return "";

	}

	/**
	 * 文字列から数字と外字コードを取り出し、半角数字で返します。
	 * 取出す文字列は連続した数字については、文字列中に２か所以上数字が
	 * 有る場合、最初の数字を取り出します。
	 * @param string $str
	 * @return string データ(数字が無い場合は空)
	 */
	public static function getNumericAndExtChar($str) {
		if (!isset($str) || empty($str)) {
			return "";
		}
		// 数字のみ・・
		if (!preg_match("/<.*?>/u", $str)) {
			return AnalyzeUtil::getNumeric($str);
		}
		if (preg_match("/.*?([0-9０１２３４５６７８９]+).*?(<.*?>).*$/us", $str, $matches)) {
			if (count($matches) > 2) {
				$retVal = mb_convert_kana($matches[1], 'n', "UTF-8");
				return  $retVal . $matches[2];
			}
		}
		return "";
	}

	/**
	 * 数字のみの文字列か判定します。
	 * @param string $str
	 * @return boolean
	 */
	public static function isNumeric($str) {
		if (!isset($str) || empty($str)) {
			return false;
		}
		if (preg_match("/^[0-9０１２３４５６７８９]+$/u", $str)) {
			return true;
		}
		return false;
	}

	/**
	 * 数字が文字列に含まれているか判定します。
	 * @param string $str
	 * @return boolean
	 */
	public static function isNumberInclude($str) {
		if (!isset($str) || empty($str)) {
			return false;
		}
		if (preg_match("/^.*?[0-9０１２３４５６７８９]+.*$/u", $str)) {
			return true;
		}
		return false;
	}

	/**
	 * 期間内か判定します。
	 *
	 * @param string $now UNIX timestamp
	 * @param integer $beforeY
	 * @param string $date
	 */
	public static function isInBetweenDate($now, $beforeY, $date) {
		// パラメータなし・・・falseで
		if (!isset($now) || !isset($beforeY) || !isset($date)
				|| empty($now) || empty($beforeY) || empty($date)) {
			return false;
		}
		// 内部チェック
		if (!(AnalyzeUtil::isNumeric($now) && AnalyzeUtil::isNumeric($beforeY))) {
			return false;
		}
		// 範囲チェック
		$fromDate = date("Ymd", strtotime("-{$beforeY} year", $now));
		// 和暦 -> 西暦へ変換


	}

	/**
	 * 平成XX年月日を西暦に変換します。
	 * 変換できない場合は、falseを返します。
	 *
	 * @param string $str
	 */
	public function converAD($str) {
		if (!isset($str) || empty($str)) {
			return false;
		}

	}

	/**
	 * 数値(率)をXX.X%に変換します。
	 *
	 * @param unknown_type $str
	 */
	public static function zenToHanNumericRate($str) {
		if (!isset($str) || empty($str)) {
			return $str;
		}
		// ％ => %へ
		$tmpStr = preg_replace("/％/um", "%", $str);
		// ・ => .へ
		$tmpStr = preg_replace("/・/um", ".", $tmpStr);
		// 数字のみ半角へ
		$tmpStr = mb_convert_kana($tmpStr, 'n', "UTF-8");
		return $tmpStr;
	}

	/**
	 * 金額を半角数字に変換します。
	 *
	 * @param string $str
	 * @return string 金額
	 */
	public static function zenToHanPrice($str) {
		if (!isset($str) || empty($str)) {
			return $str;
		}
		// 金を削除
		$chgVal = preg_replace("/金/u", "", $str);
		// 円を削除(内訳分はここで切り落とし)
		$chgVal = preg_replace("/円.*$/u", "", $chgVal);
		// ，を削除
		$chgVal = preg_replace("/，/u", "", $chgVal);
		// 全角 -> 半角へ
		$chgVal = mb_convert_kana($chgVal, 'n', "UTF-8");
		// 十を変換
		if (preg_match("/^十/u", $chgVal)) {
			$chgVal = preg_replace("/十/u", "10", $chgVal);
		} else if (preg_match("/十/u", $chgVal)) {
			$chgVal = preg_replace("/十/u", "0", $chgVal);
		}
		// 百を変換
		if (preg_match("/^百/u", $chgVal)) {
			$chgVal = preg_replace("/百/u", "100", $chgVal);
		} else if (preg_match("/百/u", $chgVal)) {
			$chgVal = preg_replace("/百/u", "00", $chgVal);
		}
		// 千を変換
		if (preg_match("/^千/u", $chgVal)) {
			$chgVal = preg_replace("/千/u", "1000", $chgVal);
		} else if (preg_match("/千/u", $chgVal)) {
			$chgVal = preg_replace("/千/u", "000", $chgVal);
		}
		// 万を変換
		if (preg_match("/^万/u", $chgVal)) {
			$chgVal = preg_replace("/万/u", "10000", $chgVal);
		} else if (preg_match("/万/u", $chgVal)) {
			if (preg_match("/^(.*?)万(.*)?/u", $chgVal, $matches)) {
				if (count($matches) > 1) {
					if (isset($matches[2]) && $matches[2] !== "") {
						if (strlen($matches[2]) < 8) {
							$matches[2] = sprintf("%04s", $matches[2]);
						}
						$chgVal = sprintf("%s%s", $matches[1], $matches[2]);
					} else {
						$chgVal = sprintf("%s%s", $matches[1], "0000");
					}
				} else {
					$wMsg = sprintf(AnalyzeMessageEnum::$warningNoAnalyzeValueData->getMessage(), $chgVal);
					$this->logger->warn($wMsg);
					return "Error";
				}
			}
		}
		// 億を変換
		if (preg_match("/億/u", $chgVal)) {
			if (preg_match("/^(.*?)億(.*)?/u", $chgVal, $matches)) {
				if (count($matches) > 1) {
					if (isset($matches[2]) && $matches[2] !== "") {
						if (strlen($matches[2]) < 8) {
							$matches[2] = sprintf("%08s", $matches[2]);
						}
						$chgVal = sprintf("%s%s", $matches[1], $matches[2]);
					} else {
						$chgVal = sprintf("%s%s", $matches[1], "00000000");
					}
				} else {
					$wMsg = sprintf(AnalyzeMessageEnum::$warningNoAnalyzeValueData->getMessage(), $chgVal);
					$this->logger->warn($wMsg);
					return "Error";
				}
			}
		}
		if (preg_match("/^[0-9]+?/u", $chgVal)) {
			// ３桁単位で,を入れる
			$chgVal = number_format($chgVal);
		} else {
			// Error
			$chgVal .= "check";
		}
		return $chgVal;
	}

	/**
	 * 全角数字を半角数字に変換します。
	 * @param string $val
	 * @return atring $val
	 */
	public static function zenToHanNumeric($val) {
		if (!isset($val) || empty($val)) {
			return $val;
		}
		return mb_convert_kana(AnalyzeUtil::trimEx(trim($val)), 'n', "UTF-8");
	}

	/**
	 * 半角数字を全角数字に変換します。
	 * @param string $val
	 * @return atring $val
	 */
	public static function hanToZenNumeric($val) {
		if (!isset($val) || empty($val)) {
			return $val;
		}
		return mb_convert_kana(AnalyzeUtil::trimEx(trim($val)), 'N', "UTF-8");
	}

}
?>