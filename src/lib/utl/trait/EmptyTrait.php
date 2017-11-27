<?php

/**
 * 配列を含む変数が空かどうか判別する。
 * 配列があっても、要素が0ならtrue
 * @param Array $value
 * @return boolean
 */
trait EmptyTrait {
	public static function isEmpty($value) {
	
		if ($value === false) {
			return true;
		}
		if (empty($value) === true) {
			return true;
		}
		if (is_array($value)) {
			return count($value) === 0;
		}
		return false;
	}
	
	public static function checkStr($str, $result = '') {
		if (empty(trim($str))) {
			return $result;
		}
		return $str;
	}
}