<?php

require_once(UTIL_DIR . '/trait/EmptyTrait.php');
require_once(UTIL_DIR . '/Logger.php');

require_once(UTIL_DIR . '/HttpRequester.php');


/**
 * 
 * @author moroishi
 *
 */
class AddressChecker {
	
	use EmptyTrait;
	private $logger;
	
	private $httpRequester;
	
	/**
	 * 定数
	 * @var string
	 */
	
	// GOOGLE
	const GOOGLE_SEARCH_URL = 'http://www.google.co.jp/search?q=';
	const GOOGLE_MAP_PATTERN = '@<div class="vk_sh vk_bk" style=".+?">(.+?)</div>@';
	const GOOGLE_RESULT_PATTERN = '@<h3 class="r"><a href="(.+?)" onmousedown="(.+?)">(.+?)</a></h3>@';
	
	// HOMES
	// const HOMES_ARCHIVE_PATTERN = '@<th>所在地.*</th>.*<td>(.*)<a id="prg-view-zenrin-map".*@su';
	const HOMES_ARCHIVE_PATTERN = '@<th>所在地.*?</th>.+?<td>(.+?)<a id="prg-view-zenrin-map".+?@s';
	const HOMES_ARCHIVE_PATTERN2 = '@<th>所在地.*?</th>.*?<td>(.+?)</td>.*?</tr>.*?<tr class="col2">.*?<th>交通</th>@su';
	const HOMES_ARCHIVE_URL = '/.+www.homes.co.jp\/archive.+/';
	const HOMES_CHINTAI_PATTERN = '@<dd id="chk-bkc-fulladdress">(.+?)<p>.+?@s';
	const HOMES_CHINTAI_URL = '/.+www.homes.co.jp\/chintai.+/';
	const HOMES_MANSION_URL = '/.+www.homes.co.jp\/mansion.+/';
	/*
	 * <dd id="chk-bkc-fulladdress">
        神奈川県川崎市多摩区南生田2丁目
            
<p>
	 */
	
	// 検索結果インデックス
	const RES_IDX_GMAP = '_gmap'; // Google検索の結果に表示される地図に記載される住所
	const RES_IDX_HOMES = '_homes'; // ホームズから取得できる住所
	
	const IDX_ADDRESS = '_addr';
	const IDX_ZIP = '_zip';
	const IDX_BUKKEN = '_bukken';
	
	const IDX_SEARCH_BUKKEN = '_searchBukken';
	const IDX_SEARCH_ADDRESS = '_searchAddress';
	const IDX_SEARCH_RESULTS = '_results';
	
	
	
	/*
	 preg_match_all('@<div class="vk_sh vk_bk" style=".+?">(.+?)</div>@', $page, $m, PREG_SET_ORDER);
	 $this->logger->debugLog($m);
	 foreach ($m as $value) {
	 if (strpos($value[1], 'www.homes.co.jp') !== FALSE) {
	 $result[] = ["url" => $value[1], "title" => $value[3]];
	 }
	 }
	 */

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		$this->logger = new Logger();
		$this->httpRequester = new HttpRequester();
	}
	
	/**
	 * 
	 * @param unknown $bukkenName
	 * @param string $address
	 * @return string|unknown[]
	 */
	public function searchHomesLoop($bukkenName, $address = '', $maxPage = 1) {
		if ($this->isEmpty($bukkenName) && $this->isEmpty($address)) {
			return '';
		}
		
		// $queryValueOrg = urlencode($bukkenName.' '.$address);
		$tmp = (empty($address)) ? $bukkenName : $bukkenName . ' ' . $address;
		$queryValueOrg = urlencode($tmp);
		$homesUnit = null;
		
		// google検索を繰り返し、homesのデータを探す
		for ($loopCount = 0;$maxPage > $loopCount; $loopCount++) {
			if ($loopCount > 0) {
				$queryValue = $queryValueOrg.'&start='.$loopCount*10;
			} else {
				$queryValue = $queryValueOrg;
			}
			$this->logger->cLog("QUERY = " . $queryValue);
			// Google検索
			sleep(1);
			$page = $this->googleSearch($queryValue);
			// $this->logger->cLog($page);
			// 検索結果の表題とURLを抜き出し
			$searchResults = $this->extractGoogleResult($page);
			// $this->logger->debugLog($searchResults);
			
			// ホームズのURLを探す
			$homesItem = $this->extractHomesUrl($searchResults);
			if (!$this->isEmpty($homesItem)) {
				$this->logger->debugLog("HOMES URL = " . print_r($homesItem, true));
				// ホームズへのアクセス
				$homesPage = $this->homesSearch($homesItem['url']);
				// ホームズの住所
				$homesAddress = $this->extractHomesResult($homesPage, $homesItem['url']);
				$this->logger->debugLog("HOMES ADDR = " . $homesAddress);
				$homesUnit = $this->createAddressUnit(null, $homesAddress, null);
				// 見つけたので終了
				break;
			} else {
				// HOMESの結果が無い
				continue;
			}
		}

		if ($this->isEmpty($homesUnit)) {
			$results = null;
		} else {
			$results = array(
					self::RES_IDX_GMAP => null,
					self::RES_IDX_HOMES => $homesUnit
			);
		}
			
		return $this->createAddressResult($bukkenName, $address, $results);
		
		
	}
	
	/**
	 * 物件名と住所の一部から、物件の住所を取り出す
	 * 戻りの型は配列なので、中身を確認して使うこと。
	 * このファイルの最下部に、ダンプ内容をコメント化
	 * @param unknown $bukkenName
	 * @param string $address
	 * @return string|unknown[]
	 */
	public function checkBukkenAddress($bukkenName, $address = '') {
		if ($this->isEmpty($bukkenName) && $this->isEmpty($address)) {
			return '';
		}
		
		
		$queryValue = urlencode($bukkenName.' '.$address);
		// Google検索
		$page = $this->googleSearch($queryValue);
		
		// 検索結果からGoogleMapが表示されている部分の抜き出し。（無いかも）
		$addrFromMap = $this->extractGoogleMapResult($page);
		$googleUnit = null;
		$this->logger->debugLog("GOOGLE MAP = " . $addrFromMap);
		if (!$this->isEmpty($addrFromMap)) {
			$googleUnit = $this->disasembleGoogleMapResult($addrFromMap);
		}
		
		// 検索結果の表題とURLを抜き出し
		$searchResults = $this->extractGoogleResult($page);
		// $this->logger->debugLog($searchResults);
		
		// ホームズのURLを探す
		$homesItem = $this->extractHomesUrl($searchResults);
		$homesUnit = null;
		if (!$this->isEmpty($homesItem)) {
			$this->logger->debugLog("HOMES URL = " . print_r($homesItem, true));
			// ホームズへのアクセス
			$homesPage = $this->homesSearch($homesItem['url']);
			// ホームズの住所
			$homesAddress = $this->extractHomesResult($homesPage, $homesItem['url']);
			$this->logger->debugLog("HOMES ADDR = " . $homesAddress);
			$homesUnit = $this->createAddressUnit(null, $homesAddress, null);
		}
		
		
		if ($this->isEmpty($googleUnit) && $this->isEmpty($homesUnit)) {
			$results = null;
		} else {
			$results = array(
					self::RES_IDX_GMAP => $googleUnit,
					self::RES_IDX_HOMES => $homesUnit
			);
		}
		
		return $this->createAddressResult($bukkenName, $address, $results);
	}
	
	//////////////////////////////////////
	// HOMES
	//////////////////////////////////////
	
	/**
	 * ホームズのサイトへアクセス
	 * @param unknown $url
	 */
	public function homesSearch($url) {
		$this->logger->debugLog("HOMES URL = " . $url);
		$homesPage = $this->httpRequester->request($url);
		return $homesPage;
		// $this->logger->debugLog($homesPage);
	}
	
	/**
	 * ホームズのサイトの検索結果から住所を出す
	 * @param unknown $homesPage
	 * @return unknown
	 */
	public function extractHomesResult($homesPage, $homesUrl) {
		
		if (preg_match(self::HOMES_ARCHIVE_URL, $homesUrl)) {
			return $this->extractHomesArchiveResult($homesPage);
		} else if (preg_match(self::HOMES_CHINTAI_URL, $homesUrl)
				|| preg_match(self::HOMES_MANSION_URL, $homesUrl)) {
			return $this->extractHomesChintaiResult($homesPage);
		} else {
			return null;
		}
		
	}
	
	/**
	 * homesアーカイブの検索結果から、住所を取り出す
	 * @param unknown $homesPage
	 * @return unknown|NULL
	 */
	public function extractHomesArchiveResult($homesPage) {
		$st = preg_match_all(self::HOMES_ARCHIVE_PATTERN, $homesPage, $m, PREG_SET_ORDER);
		$this->logger->cLog($m);
		if (!$st) {
			preg_match_all(self::HOMES_ARCHIVE_PATTERN2, $homesPage, $m, PREG_SET_ORDER);
			$this->logger->cLog($m);
		}
		foreach ($m as $value) {
			if (!$this->isEmpty($value) && count($value) > 1) {
				return trim($value[1]);
			}
		}
		return null;
	}
	
	public function extractHomesChintaiResult($homesPage) {
		preg_match_all(self::HOMES_CHINTAI_PATTERN, $homesPage, $m, PREG_SET_ORDER);
		foreach ($m as $value) {
			if (!$this->isEmpty($value) && count($value) > 1) {
				return trim($value[1]);
			}
		}
		return null;
	}
	
	//////////////////////////////////////
	// Google
	//////////////////////////////////////
	
	public function googleSearch($target) {
		$url = self::GOOGLE_SEARCH_URL.$target;
		$page = $this->httpRequester->request($url);
		return $page;
	}
	
	/**
	 * Googleの検索結果から
	 * ・グーグルMAP住所
	 * @param unknown $page
	 * @return NULL|unknown
	 */
	private function extractGoogleMapResult($page) {
		// Google MAPの結果が出ているか？
		$resGoogleMap = null;
		
		// GoogleMapが表示されているところから、住所部分を抜き出す
		$m = null;
		if (preg_match_all(self::GOOGLE_MAP_PATTERN, $page, $m, PREG_SET_ORDER)) {
			foreach ($m as $value) {
				if (!$this->isEmpty($value) && count($value) > 1) {
					$resGoogleMap = $value[1];
					break;
				}
			}
		}
		
		return $resGoogleMap;
	}
	
	/**
	 * Google検索結果のメイン部分を抽出する
	 * @param unknown $page
	 * @return NULL
	 */
	private function extractGoogleResult($page) {
		$result = null;
		preg_match_all(self::GOOGLE_RESULT_PATTERN, $page, $result, PREG_SET_ORDER);
		return $result;		
	}
	
	/**
	 * ホームズのURLをGoogle検索結果から抽出する
	 * @param unknown $page
	 */
	private function extractHomesUrl($results) {
		if ($this->isEmpty($results)) {
			return null;
		}
		// ホームズの検索結果があるか？
		foreach ($results as $value) {
			if (strpos($value[1], 'www.homes.co.jp') !== FALSE) {
				$result = ["url" => $value[1], "title" => $value[3]];
				return $result;
			}
		}
		// 見つからない
		return null;
	}
	
	private function disasembleGoogleMapResult($mapResult) {
		if ($this->isEmpty($mapResult)) {
			return null;
		}
		// ユーカリシティ志津, 〒285-0855 千葉県佐倉市井野１４１４−５
		// ,で区切る
		$tmps = explode(",", $mapResult);
		$bukkenName = $tmps[0];
		$remains = trim($tmps[1]);
		// スペースで区切る
		$tmp2s = explode(" ", $remains);
		$zip = trim(str_replace("〒", '', $tmp2s[0]));
		$address = trim($tmp2s[1]);
		
		return $this->createAddressUnit($zip, $address, $bukkenName);
	}
	
	private function createAddressUnit($zip, $address, $bukken) {
		if ($this->isEmpty($zip) && $this->isEmpty($address) && $this->isEmpty($bukken)) {
			return null;
		}
		return array(
				self::IDX_ZIP => $zip,
				self::IDX_ADDRESS => $address,
				self::IDX_BUKKEN => $bukken
		);
	}
	
	private function createAddressResult($searchBukken, $searchAddress, $result) {
		return array(
				self::IDX_SEARCH_BUKKEN => $searchBukken,
				self::IDX_SEARCH_ADDRESS => $searchAddress,
				self::IDX_SEARCH_RESULTS => $result				
		);
	}
	
}

/* 
 * 戻りの型
 * Array
(
    [_searchBukken] => ユーカリシティ志津
    [_searchAddress] => 
    [_results] => Array
        (
            [_gmap] => Array
                (
                    [_zip] => 285-0855
                    [_addr] => 千葉県佐倉市井野１４１４−５
                    [_bukken] => ユーカリシティ志津
                )

            [_homes] => Array
                (
                    [_zip] => 
                    [_addr] => 千葉県佐倉市井野1414-5
                    [_bukken] => 
                )

        )

)
*/
