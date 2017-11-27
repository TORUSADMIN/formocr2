<?php

final class HttpUtil {

	// private コンストラクタ instance化は許可しない
	private function __construct() {
	}

	//デストラクタ
	public function __destruct() {
	}

	// HTTP CODEを取得します。
	public static function getHttpCode($info) {
		$code = "Unknown";
		if (isset($info) && isset($info["http_code"])) {
			$code = $info["http_code"];
		}
		return $code;
	}

	// Redirect URLをレスポンスデータから取得する
	// $data: レスポンスデータ
	public static function getRedirectUrl($data) {
		// URL取得正規化表現
		$aTagregPatten = "/^.*?This document has moved <a.+?href=\"(.*)\">.*/is";
		$urlArray = array();
		// URL 抽出
		preg_match($aTagregPatten, $data, $urlArray);
		if (count($urlArray) < 2) {
			return "";
		}
		return $urlArray[1];
	}

	// Tokenを取得する
	// $tokenTagName: Token Tag Name
	// $data        : レスポンスデータ
	public static function getToken($tokenTagName, $data) {
		// Token取得正規化表現
		$regPatten = "/^.*?<input.+?type='hidden'.+?name='$tokenTagName'.+?value='(.*?)'.+?\/>.*/is";
		$tokenArray = array();
		// Token 抽出
		preg_match($regPatten, $data, $tokenArray);
		if (count($tokenArray) < 2) {
			return "";
		}
		return $tokenArray[1];
	}


}
?>
