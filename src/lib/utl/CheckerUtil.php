<?php

final class CheckerUtil {

	/**
	 * private コンストラクタ instance化は許可しない
	 */
	private function __construct() {
	}

	/**
	 * デストラクタ
	 */
	public function __destruct() {
	}

	/**
	 * 全角数字チェック
	 * @param String $str
	 * @return boolean true: 全角数字 false: ずべて全角数字ではない
	 */
	public static function isFullNumeric($str) {
		if (is_null($str)) {
			return false;
		}
		if (preg_match("/^[０-９]+$/", $str)) {
			return true;
		} else {
			return false;
		}
	}

	// 半角数字チェック(
	// true: 半角数字 false: ずべて半角数字ではない。
	public static function isHalfNumeric($str) {
		if (is_null($str)) {
			return false;
		}
		if (preg_match("/^[0-9]+$/", $str)) {
			return true;
		} else {
			return false;
		}
	}

	// 全角文字チェック
	// true: 全て全角文字 false: ずべて全角文字ではない。
	public static function isFullStrings($str, $encoding = null) {
		if (is_null($encoding)) {
			$encoding = mb_internal_encoding();
		}
		$len = mb_strlen($str, $encoding);
		for ($i = 0; $i < $len; $i++) {
			$char = mb_substr($str, $i, 1, $encoding);
			if (EstateUtil::isGaijiChar($char)) {
				continue;
			}
			if (self::isHalfStrings($char, false, true, $encoding)) {
				return false;
			}
		}
		return true;
	}

	// 半角文字チェック(
	// true: 半角文字 false: ずべて半角文字ではない。
	public static function isHalfStrings($str,
			$include_kana = false,
			$include_controls = false,
			$encoding = null) {
		if (!$include_controls && !ctype_print($str)) {
			return false;
		}
		if (is_null($encoding)) {
			$encoding = mb_internal_encoding();
		}
		if ($include_kana) {
			$to_encoding = 'SJIS';
		} else {
			$to_encoding = 'UTF-8';
		}
		$str = mb_convert_encoding($str, $to_encoding, $encoding);
		if (strlen($str) === mb_strlen($str, $to_encoding)) {
			return true;
		} else {
			return false;
		}
	}
}


?>
