<?php

require_once(UTIL_DIR . '/trait/EmptyTrait.php');
require_once(UTIL_DIR . '/Logger.php');

class HttpRequester {

	use EmptyTrait;
	private $logger;

	/*
	const IDX_RETURN_TRANSFER = '_return_transfer';
	const IDX_SSL_VERIFYPEER = '_ssl_verifypeer';
	const IDX_FOLLOWLOCATION = '_followlocation';
	const IDX_USERAGENT = '_useragent';
	*/
	// const DEF_UA = "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36";
	// const DEF_UA = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.143 Safari/537.36";
	// const DEF_UA = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/601.7.8 (KHTML, like Gecko) Version/9.1.3 Safari/601.7.8";
	const DEF_UA = "Mozilla/5.0 (Windows NT 10.0; WOW64) Gecko/20100101 Firefox/49.0";
	public function __construct() {
		$this->logger = new Logger();
	}
	
	/**
	 * URLにアクセスし、結果を返す
	 * @param unknown $url
	 * @param unknown $options
	 * @return NULL|mixed
	 */
	public function request($url, $options = null){

		// $this->logger->cLog("URL = " . $url);
		// URLが正しくなければ、NULLを返す
		if (!$this->checkUrlFormat($url)) {
			return null;
		}
		// オプション		
		$curlOpt = ($this->isEmpty($options)) ? $this->createCurlOption() : $options;
		
		// アクセス
		$ch = curl_init($url);
		curl_setopt_array($ch, $curlOpt);
		$page = curl_exec($ch);

		return $page;
	}
	
	
	/**
	 * CURLのオプション生成
	 * @return boolean[]|string[]
	 */
	public function createCurlOption($ua = self::DEF_UA, $returnTransfer = true, $sslVerifypeer = false, $followLocation = true) {
		$option = array(
				CURLOPT_RETURNTRANSFER => $returnTransfer,
				CURLOPT_SSL_VERIFYPEER => $sslVerifypeer,
				CURLOPT_FOLLOWLOCATION => $followLocation,
				CURLOPT_USERAGENT => $ua
				);
		return $option;
	}
	
	/**
	 * URLとして正しいか？
	 * @param unknown $url
	 * @return number
	 */
	public function checkUrlFormat($url) {
		return preg_match('/^(https?|ftp)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/', $url);
	}
	
}