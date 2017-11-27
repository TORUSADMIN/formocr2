<?php

trait TrimTrait {
	
	/**
	 * 全角スペーストリム
	 * @param unknown $str
	 * @return mixed
	 */
	public function trimEmspace ($str) {
		// 先頭の半角、全角スペースを、空文字に置き換える
		$str = preg_replace('/^[ 　]+/u', '', $str);
		// 最後の半角、全角スペースを、空文字に置き換える
		$str = preg_replace('/[ 　]+$/u', '', $str);
		return trim($str);
	}
}