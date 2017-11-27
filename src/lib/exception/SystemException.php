<?php

// システム例外
class SystemException extends Exception {

	// 例外を再定義し、メッセージをオプションではなくする
	public function __construct($message, $code = 0, Exception $previous = null) {
		// 全てを正しく確実に代入する
		parent::__construct($message, $code, $previous);
	}

	// オブジェクトの文字列表現を独自に定義する
	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}

}

?>
