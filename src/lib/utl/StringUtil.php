<?php



class StringUtil {

	// private コンストラクタ instance化は許可しない
	private function __construct() {
	}

	//デストラクタ
	public function __destruct() {
	}

	/**
	 * 漢数字を半角数字に変換します。
	 * @param string $val
	 * @return string $val
	 */
	public static function _zenKanToHanNumeric($val) {
		if (!isset($val) || empty($val)) {
			return $val;
		}
		$chgVal = mb_convert_kana($val, 'n', "UTF-8");
		if (preg_match("/百分の/u", $chgVal)) {
			$chgVal = preg_replace("/百/u", "00", $chgVal);
		} else if (preg_match("/百/u", $chgVal)) {
			if (preg_match("/^(.*?)百(.*)?/u", $chgVal, $matches)) {
				if (count($matches) > 2) {
					$chgVal = sprintf("%s%02s", $matches[1], $matches[2]);
				} else {
					$wMsg = sprintf(AnalyzeMessageEnum::$warningNoAnalyzeValueData->getMessage(), $val);
					$this->logger->warn($wMsg);
					return "Error";
				}
			}
		}
		if (preg_match("/千分の/u", $chgVal)) {
			$chgVal = preg_replace("/千/u", "000", $chgVal);
		} else if (preg_match("/千/u", $chgVal)) {
			if (preg_match("/^(.*?)千(.*)?/u", $chgVal, $matches)) {
				if (count($matches) > 2) {
					$chgVal = sprintf("%s%03s", $matches[1], $matches[2]);
				} else {
					$wMsg = sprintf(AnalyzeMessageEnum::$warningNoAnalyzeValueData->getMessage(), $val);
					$this->logger->warn($wMsg);
					return "Error";
				}
			}
		}

		if (preg_match("/万分の/u", $chgVal)) {
			$chgVal = preg_replace("/万/u", "0000", $chgVal);
		} else if (preg_match("/万/u", $chgVal)) {
			if (preg_match("/^(.*?)万(.*)?/u", $chgVal, $matches)) {
				if (count($matches) > 2) {
					$chgVal = sprintf("%s%04s", $matches[1], $matches[2]);
				} else {
					$wMsg = sprintf(AnalyzeMessageEnum::$warningNoAnalyzeValueData->getMessage(), $val);
					$this->logger->warn($wMsg);
					return "Error";
				}
			}
		}
		if (preg_match("/億分の/u", $chgVal)) {
			$chgVal = preg_replace("/億/u", "00000000", $chgVal);
		} else if (preg_match("/億/u", $chgVal)) {
			if (preg_match("/^(.*?)億(.*)?/u", $chgVal, $matches)) {
				if (count($matches) > 1) {
					if (isset($matches[2]) && $matches[2] !== "") {
						$chgVal = sprintf("%s%s", $matches[1], $matches[2]);
					} else {
						$chgVal = sprintf("%s%s", $matches[1], "00000000");
					}
				} else {
					$wMsg = sprintf(AnalyzeMessageEnum::$warningNoAnalyzeValueData->getMessage(), $val);
					$this->logger->warn($wMsg);
					return "Error";
				}
			}
		}
		return $chgVal;
	}

	/**
	 * 分数の文字列から分子・分母の数字を抽出します。
	 * @param string $val
	 * @return array(分子、分母)
	 */
	public static function splitFractionStr($val) {
		if (!isset($val) || empty($val)) {
			return array("", "");
		}
		if (preg_match("/^(.*?)分の(.*)/u", $val, $matches)) {
			if (count($matches) > 2) {
				return array($matches[2], $matches[1]);
			}
		}
		return array("", "");
	}

	/**
	 * 持ち分の計算結果を、謄本の表記に変更します。
	 * 「/」の無いまたは２つ以上ある文字列は変換対象外とします。
	 *
	 * @param string $str
	 * @return string 変換文字列
	 */
	public static function partHanToZenDisplay($str) {
		if (!isset($str) || empty($str) || !preg_match("/\//u", $str)) {
			return $str;
		}
		// /の数確認
		if (substr_count($str, "/") > 1) {
			return $str;
		}
		if (preg_match("/(.*)\/(.*)/u", $str, $matches)) {
			if (count($matches) > 2) {
				$savePart = sprintf("%s分の%s",
						AnalyzeUtil::hanToZenNumeric($matches[2]),
						AnalyzeUtil::hanToZenNumeric($matches[1]));
				return $savePart;
			}
		}
		return $str;
	}


	/**
	 * 持ち分の計算結果を、謄本表記の持ち分を半角数字・通常表記に変更します。(UTF-8)
	 * 「分の」が無い文字列は変換対象外とします。
	 *
	 * @param string $val
	 * @return string 変換文字列
	 */
	public static function partZenToHanDisplay($val, $lang = "UTF-8") {
		if (!isset($val) || empty($val) || !preg_match("/分の/u", $val)) {
			return $val;
		}
		if (preg_match("/^(.*?)分の(.*)/u", $val, $matches)) {
			if (count($matches) > 2) {
				if ($matches[1] === $matches[2]) {
					return "";
				}
				$retStr = sprintf("%s分の%s", StringUtil::_zenKanToHanNumeric($matches[1]),
											StringUtil::_zenKanToHanNumeric($matches[2]));
				$retStr = mb_convert_kana($retStr, "n", $lang);
				return $retStr;
			}
		}
		return $val;
	}


	/**
	 * 西暦を和暦に変換します。
	 * @param string $str
	 * @param string 変換後のデータ
	 */
	public static function adToWareki($str, $topStr = "平成", $convVal = 1988) {
		if (!isset($str) || empty($str)) {
			return $str;
		}
		// 前後をトリム
		$tmpStr = trim($str);
		$retStr = "";
		// データを分解(年、月、日)
		if (preg_match("/^([0-9].+)\-([0-9].+)\-([0-9].+)$/u", $tmpStr, $matches)) {
			if (count($matches) > 3) {
				/** パターン一致 **/
				$m = preg_replace("/^0/u", "", $matches[2]);
				$d = preg_replace("/^0/u", "", $matches[3]);
				// 西暦 -> 和暦(平成)
				$y = $matches[1];
				$wy = $y - $convVal;
				// データ整形
				$retStr = sprintf("%s%s年%s月%s日", $topStr, $wy, $m, $d);
				return $retStr;
			}
		}
		// 日付にマッチしない・・・
		return $str;
	}


	/**
	 * 西暦を和暦に変換します。
	 * 変換後全角に変換します。
	 *
	 * @param string $str
	 * @param string 変換後のデータ
	 */
	public static function adToWarekiZen($str, $topStr = "平成", $convVal = 1988, $lang = "UTF-8") {
		if (!isset($str) || empty($str)) {
			return $str;
		}
		// 西暦 -> 和暦
		$wStr = StringUtil::adToWareki($str, $topStr, $convVal);
		if ($wStr === "") {
			// 変換できない。
			return $str;
		}
		// 半角文字列 -> 全角文字列
		$retStr = mb_convert_kana($wStr, "N", $lang);
		return $retStr;
	}


	/**
	 * 漢数字文字列を全角数字に変換します。
	 * 対象は「一二三四五六七八九十〇零」
	 *
	 * @param string $val
	 * @return string 全角数字
	 */
	public static function numericStringKanToZen($val) {
		if (!isset($val) || empty($val)) {
			return $val;
		}
		// 漢数字以外はそのままreturn
		if (!preg_match("/^[一二三四五六七八九十〇零]+$/u", $val)) {
			return $val;
		}
		// 十の位置を判定(十のみ)
		if (preg_match("/^十$/u", $val)) {
			return "１０";
		}
		// 先頭が十の場合 -> 一に変換
		if (preg_match("/^十/u", $val)) {
			$tmpVal = preg_replace("/十/u", "一", $val);
			return StringUtil::numericKanToZen($tmpVal);
		}
		// 最後が十
		if (preg_match("/十$/u", $val)) {
			$tmpVal = preg_replace("/十/u", "〇", $val);
			return StringUtil::numericKanToZen($tmpVal);
		}
		// 真ん中に十
		$tmpVal = preg_replace("/十/u", "", $val);

		return StringUtil::numericKanToZen($tmpVal);
	}

	/**
	 * 漢数字「一二三四五六七八九〇零」を全角数字「１２３４５６７８９００」に変換します。
	 * @param string $val
	 * @return string 全角数字
	 */
	public static function numericKanToZen($val) {
		if (!isset($val) || empty($val)) {
			return $val;
		}
		// 対象文字以外はそのままreturn
		if (!preg_match("/^[一二三四五六七八九〇零]+$/u", $val)) {
			return $val;
		}
		// 変換
		$retVal = "";
		// 一
		$retVal = preg_replace("/一/u", "１", $val);
		// 二
		$retVal = preg_replace("/二/u", "２", $retVal);
		// 三
		$retVal = preg_replace("/三/u", "３", $retVal);
		// 四
		$retVal = preg_replace("/四/u", "４", $retVal);
		// 五
		$retVal = preg_replace("/五/u", "５", $retVal);
		// 六
		$retVal = preg_replace("/六/u", "６", $retVal);
		// 七
		$retVal = preg_replace("/七/u", "７", $retVal);
		// 八
		$retVal = preg_replace("/八/u", "８", $retVal);
		// 九
		$retVal = preg_replace("/九/u", "９", $retVal);
		// 〇
		$retVal = preg_replace("/〇/u", "０", $retVal);
		// 零
		$retVal = preg_replace("/零/u", "０", $retVal);

		return $retVal;
	}


	/**
	 * 枝番(ＸＸ番ＹＹ号)をXX-YYに変換します。
	 * @param string $val
	 * @return string 変換文字列
	 */
	public static function numericStringLotNumber($val) {
		if (!isset($val) || empty($val)) {
			return $val;
		}
		// 号室を削除
		$tmpVal = preg_replace("/号室/u", "", $val);
		if (preg_match("/号$/u", $tmpVal)) {
			// 末尾が号なので削除
			$tmpVal = preg_replace("/号/u", "", $tmpVal);
		} else {
			// 号の後に文字がある -> 号を-へ変換
			$tmpVal = preg_replace("/号-/u", "-", $tmpVal);
			$tmpVal = preg_replace("/号/u", "-", $tmpVal);
		}
		// 番地だけなら削除
		$tmpVal = preg_replace("/番地$/u", "", $tmpVal);
		if (preg_match("/番地/u", $tmpVal)) {
			if (preg_match("/番地[１２３４５６７８９]+/u", $tmpVal)) {
				$tmpVal = preg_replace("/番地/u", "-", $tmpVal);
			} else {
				$tmpVal = preg_replace("/番地/u", "", $tmpVal);
			}
			// 全角 -> 半角へ
			$retVal = mb_convert_kana($tmpVal, 'n', "UTF-8");
			return $retVal;
		}
		// 番と後に文字が有る場合
		if (preg_match("/番[^号]{1,}/u", $tmpVal)) {
			$tmpVal = preg_replace("/番/u", "-", $tmpVal);
		} else {
			$tmpVal = preg_replace("/番/u", "", $tmpVal);
		}
		// 全角 -> 半角へ
		$retVal = mb_convert_kana($tmpVal, 'n', "UTF-8");
		return $retVal;
	}

}

?>
