<?php

class EstateUtil {
    // コンストラクタ instance化は許可しない
    private function __construct() {
	}

    //デストラクタ
    public function __destruct() {
	}

	// 配列か連想配列か判定する。
	public static function is_hash(&$array) {
		$i = 0;
		foreach($array as $k => $dummy) {
			if ( $k !== $i++ ) return true;
		}
		return false;
	}

	// Hashのマージを行う。
	// 同一キーが存在する場合、$array2が優先される(上書き)
	public static function merge_hash(array $array1, array $array2) {
		$retHash = array();
		if (self::is_hash($array1)) {
			foreach($array1 as $k => $val) {
				$retHash[$k] = $val;
			}
		}
		if (self::is_hash($array2)) {
			foreach($array2 as $k => $val) {
				$retHash[$k] = $val;
			}
		}
		return $retHash;
	}

	// 外字判定を行う
	// $str    : チェック対象文字列
	// return  : true:外字を含む、false:外字を含まない
	public static function isGaiji($str, $strEnc='UTF-8') {
		if (!isset($str) || $str === "") {
			return false;
		}
		$strArray = self::mbStringToArray($str, $strEnc);
		foreach ($strArray as $char) {
			if (self::isGaijiChar($char, $strEnc)) {
				return true;
			}
		}
		return false;
	}

	// 外字コードを<XXXXXX>に置き換える
	// $str    : 変換対象文字列
	// return  : 変換後の文字列
	public static function gaijiConv($str, $strEnc='UTF-8') {
		if (!isset($str) || $str === "") {
			return $str;
		}
		$retStr = "";
		$strArray = self::mbStringToArray($str, $strEnc);
		foreach ($strArray as $char) {
			if (self::isGaijiChar($char, $strEnc)) {
				// 外字 -> 文字コード表記へ変換
				$strCodeArray = unpack("H*", $char);
				$strCode = $strCodeArray[1];
				$retStr .= "<" . $strCode . ">";
			} else {
				$retStr .= $char;
			}
		}
		return $retStr;
	}

	// 外字か判定する、１文字より多く渡した場合falseとなる。
	// $char   : 判定対象文字(１文字であること)
	// return  : true:外字を含む、false:外字を含まない
	public static function isGaijiChar($char, $strEnc='UTF-8') {
		if (mb_strlen($char, $strEnc) != 1) {
			return false;
		}
		// U+E000 ～ U+F8FF => 「EE 80」～「EE BF」と 「EF 80」～「EF A3」
		// U+000F0000 ～ U+000FFFFD => 「F3 B0」～「F3 BF」
		// U+00100000 ～ U+0010FFFD => 「F4 80」～「F4 8F」
		// U+000FFFE、U+000FFFF、U+0010FFFE、U+0010FFFF は範囲外だが、含んでいる、弊害が出る場合は外す
		if (preg_match('/^(\xEE[\x80-\xBF])|(\xEF[\x80-\xA3])|(\xF3[\xB0-\xBF])|(\xF4[\x80-\x8F])/', $char)) {
			return true;
		}
		return false;
	}

	// マルチバイト文字列を配列に変換する。
	// $str    : 変換対象文字列
	// $strEnc : 変換対象文字列の文字コード
	public static function mbStringToArray($str, $strEnc='UTF-8') {
		$retArray = array();
		if (!isset($str) || $str === "") {
			return $retArray;
		}
		while ($iLen = mb_strlen($str, $strEnc)) {
			array_push($retArray, mb_substr($str, 0, 1, $strEnc));
			$str = mb_substr($str, 1, $iLen, $strEnc);
		}
		return $retArray;
	}

}
?>
