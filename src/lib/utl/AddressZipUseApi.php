<?php

require_once(UTIL_DIR.'/ApiCall.php');
require_once(LIB_DIR.'/util/Logger.php');
require_once(LIB_DIR.'/util/trait/EmptyTrait.php');
require_once(LIB_DIR.'/util/trait/TrimTrait.php');

class AddressZipUseApi {
	
	use EmptyTrait, TrimTrait;
	
	private $__aCache;
	// Util
	private $logger;
	
	/**
	 * for more info
	 * http://project.iw3.org/zip_search_x0401/index.html
	 * @var string
	 */
	const BASE_URL = 'http://api.thni.net/jzip/X0401/JSON/J';
	
	const NG = '__NG';
	
	private $__specialArea = [
		'東京都' => [
				'江東区' => [
						'豊洲' => '豊洲（次のビルを除く）'
				],
				'新宿区' => [
						'西新宿' => '西新宿（次のビルを除く）'
				],
				'港区' => [
						'赤坂' => '赤坂（次のビルを除く）',
						'港南' => '港南（次のビルを除く）',
						'愛宕' => '愛宕（次のビルを除く）',
						'虎ノ門' => '虎ノ門（次のビルを除く）',
						'東新橋' => '東新橋（次のビルを除く）',
						'三田' => '三田（次のビルを除く）',
						'六本木' => '六本木（次のビルを除く）'
				],
				'千代田区' => [
						'霞が関' => '霞が関（次のビルを除く）',
						'永田町' => '永田町（次のビルを除く）',
						'丸の内' => '丸の内（次のビルを除く）',
				],
				'品川区' => [
						'大崎' => '大崎（次のビルを除く）',
				],
				'豊島区' => [
						'東池袋' => '東池袋（次のビルを除く）',
				],
				'中央区' => [
						'日本橋' => '日本橋（次のビルを除く）',
						'晴海' => '晴海（次のビルを除く）',
				],
				'渋谷区' => [
						'恵比寿' => '恵比寿（次のビルを除く）'
				]
		]	
	];
	
	private $__exceptionArea = [
		'東京都' => [
				'新宿区' => [
						'西早稲田' => '162-0051：２丁目１番１～２３号、２番／169-0051：西早稲田（その他）'
				],
				'港区' => [
						'海岸' => '105-0022：海岸（１、２丁目）／108-0022：海岸（３丁目）',
						'芝' => '105-0014：芝（１～３丁目）／108-0014：芝（４、５丁目）',
						'芝浦' => '105-0023：芝浦（１丁目）／108-0023：芝浦（２～４丁目）'
				],
				'品川区' => [
						'北品川' => '140-0001：北品川（１～４丁目）／141-0001：北品川（５、６丁目）'
				],
				'足立区' => [
						'青井' => '120-0012：青井（１～３丁目）／121-0012：青井（４～６丁目）',
						'中央本町' => '120-0011：中央本町（１、２丁目）／121-0011：中央本町（３～５丁目）'
				],
				'文京区' => [
						'白山' => '113-0001：白山（１丁目）／112-0001：白山（２～５丁目）'
				],
				'江戸川区' => [
						'江戸川' => '132-0013：江戸川（１～３丁目、４丁目１～１４番）／134-0013：江戸川（その他）',
						'西瑞江' => '132-0015：西瑞江（３丁目、４丁目３～９番）／134-0015：西瑞江（４丁目１～２番・１０～２７番、５丁目）',
						'春江町' => '132-0003：春江町（１～３丁目）／134-0003：春江町（４、５丁目）',
						'谷河内' => '132-0002：谷河内（１丁目）／133-0002：谷河内（２丁目）'
				],
				'板橋区' => [
						'西台' => '174-0045：西台（１丁目）／175-0045：西台（２～４丁目）'
				],
				'豊島区' => [
						'池袋' => '170-0014：池袋（１丁目）／171-0014：池袋（２～４丁目）'
				],
		],
		'大阪府' => [
				'大阪市中央区' => [
						'谷町' => '540-0012：谷町（１～５丁目）／542-0012：谷町（６～９丁目）',
						'道頓堀' => '542-0077：道頓堀（１丁目東）／542-0071：道頓堀（その他）',
				]	
		],
	];

	public function __construct() {
		$this->__aCache = array();
		$this->logger = new Logger();
	}
	
	/**
	 * ZIPコードの調査
	 * キャッシュに存在すればキャッシュから取得する
	 * @param unknown $pref
	 * @param unknown $city
	 * @param unknown $town
	 */
	public function getZip($pref, $city, $town, $removeHaihun = false) {
		// $pref = $this->normalizeAddress($pref);
		// $city = $this->normalizeAddress($city);
		// $town = $this->normalizeAddress($town);
		// キャッシュから郵便番号を取得
		$zip = $this->getCache($pref, $city, $town);
		if ($zip === self::NG) {
			return false;
		} else if ($zip) {
			// NG以外の値は、郵便番号が存在した。
			return $this->formatZip($zip, $removeHaihun);
		}
		// キャッシュに存在しなかったので、APIから取得
		// 一応0.3秒ぐらいはsleep
		usleep(300000);
		list($httpCode, $zipStruct) = $this->apiMain($pref, $city, $town);
		// 最終判定
		if ($this->isOk($httpCode)) {
			if (isset($zipStruct['zipcode'])) {
				$zip = $zipStruct['zipcode'];
				$this->addCache($pref, $city, $town, $zip);
				// $this->logger->debugLog($this->__aCache);
				return $this->formatZip($zip, $removeHaihun);
			}
		}
		
		$this->logger->debugLog("CAN NOT FOUND ZIP:PREF = " . $pref . " CITY = " . $city . " TOWN = " . $town);
		// ここまで来たら取得できなかった。
		// 取得できなかったところもキャッシュしておく
		$this->addCache($pref, $city, $town, self::NG);
		$this->logger->debugLog($this->__aCache);
		return false;
	}
	
	/**
	 * 郵便番号APIを使って郵便番号を取得する。
	 * @param unknown $pref
	 * @param unknown $city
	 * @param unknown $town
	 * @return unknown[]|mixed[][]
	 */
	public function apiMain($pref, $city, $town) {
		
		$httpCode = 200;
		// 丁目などで違う郵便番号などのエリア。候補を返す
		$exceptionZip = $this->exceptionArea($pref, $city, $town);
		// $this->logger->debugLog($exceptionZip);
		if (!$this->isEmpty($exceptionZip)) {
			return array($httpCode, $exceptionZip);
		}
		
		// 特別な地域かのチェック
		list($tPref, $tCity, $tTown) = $this->specialArea($pref, $city, $town);
		
		// 郵便番号API
		// まずは最初に呼ぶ
		$url = $this->createUrl($tPref, $tCity, $tTown);
		list($httpCode, $zipStruct) = ApiCall::getApi($url);
		
		// エラーがあれば、住所を修正してコール
		if (!$this->isOk($httpCode)) {
			$changed = false;
			$tmpCity = $tCity;
			$tmpTown = $tTown;
			// エラーの場合
			if (strpos($city, 'ケ') !== false) {
				// 小さいヶにする。
				$tmpCity = $this->replace($tCity, 'ケ', 'ヶ');
				$changed = true;
			} else if (strpos($tCity, 'ヶ') !== false) {
				// 大きいケにする。
				$tmpCity = $this->replace($tCity, 'ヶ', 'ケ');
				$changed = true;
			}
			// エラーの場合
			if (strpos($tTown, 'ケ') !== false) {
				// 小さいヶにする。
				$tmpTown = $this->replace($tTown, 'ケ', 'ヶ');
				$changed = true;
			} else if (strpos($tTown, 'ヶ') !== false) {
				// 大きいケにする。
				$tmpTown = $this->replace($tTown, 'ヶ', 'ケ');
				$changed = true;
			}
			if (preg_match('/字|大字/u', $tTown)) {
				// 字、大字で分解して最初の文字を節を取る
				$tmpTown = $this->firstAzaFromTown($tTown);
				$changed = true;
			}
			if ($changed) {
				$url = $this->createUrl($tPref, $tmpCity, $tmpTown);
				$this->logger->debugLog("    RESEARCH :" . $pref.$tmpCity.$tmpTown);
				list($httpCode, $zipStruct) = ApiCall::getApi($url);
			}
		}
		// とにかく返す
		return array($httpCode, $zipStruct);
		
	}
	
	/**
	 * 特殊な郵便番号を持つエリア。（丁目で郵便番号が違うなど）
	 * @param unknown $tPref
	 * @param unknown $tCity
	 * @param unknown $tTown
	 * @return string|boolean
	 */
	private function exceptionArea($tPref, $tCity, $tTown) {
		if (isset($this->__exceptionArea[$tPref][$tCity][$tTown])) {
			$this->logger->debugLog("EXCEPTION:" . $tPref.':'.$tCity.':'.$tTown);
			return array('zipcode' => $this->__exceptionArea[$tPref][$tCity][$tTown]);
		}
		return false;
	}
	
	/**
	 * ビル住所をもつちいきなどの対応
	 * @param unknown $tPref
	 * @param unknown $tCity
	 * @param unknown $tTown
	 * @return unknown|string
	 */
	private function specialArea($tPref, $tCity, $tTown) {
		$specialTown = $tTown;
		if (isset($this->__specialArea[$tPref][$tCity][$tTown])) {
			$specialTown = $this->__specialArea[$tPref][$tCity][$tTown];
			$this->logger->debugLog("Found Special PREF = " . $tPref . " CITY = " . $tCity . " TOWN = ". $tTown . " CHANGE = " . $specialTown );
		}
		return array($tPref, $tCity, $specialTown);
		
	}
	
	private function formatZip($zip, $removeHaihun) {
		if ($removeHaihun) {
			return str_replace("-", "", $zip);
		}
		return $zip;
	}
	
	public function isOk($httpCode) {
		return ($httpCode == '200');
	}
	
	public function createUrl($pref, $city, $town) {
		$url = self::BASE_URL.'/'.urlencode($pref).'/'.urlencode($city).'/'.urlencode($town).'.js';
		return $url;
	}
	
	
	/**
	 * キャッシュに存在するか？
	 * @param unknown $pref
	 * @param unknown $city
	 * @param unknown $town
	 */
	public function checkCache($pref, $city, $town) {
		return isset($this->__aCache[$pref][$city][$town]);
	}
	
	/**
	 * 都道府県市区町村のキャッシュを作成
	 * @param unknown $pref
	 * @param unknown $city
	 * @param unknown $town
	 */
	public function initCache($pref, $city, $town) {
		if (!isset($this->__aCache[$pref])) {
			$this->__aCache[$pref] = array();
		}
		if (!isset($this->__aCache[$pref][$city])) {
			$this->__aCache[$pref][$city] = array();
		}
		if (!isset($this->__aCache[$pref][$city][$town])) {
			$this->__aCache[$pref][$city][$town] = array();
		}
	}
	
	/**
	 * キャッシュにデータを追加する
	 * @param unknown $pref
	 * @param unknown $city
	 * @param unknown $town
	 * @param unknown $zip
	 */
	public function addCache($pref, $city, $town, $zipCode) {
		if (!$this->checkCache($pref, $city, $town)) {
			$this->initCache($pref, $city, $town);
		}
		// キャッシュにデータを追加する。
		$this->__aCache[$pref][$city][$town] = $zipCode;
	}
	
	/**
	 * キャッシュからデータを取得する
	 * @param unknown $pref
	 * @param unknown $city
	 * @param unknown $town
	 * @return mixed|boolean
	 */
	public function getCache($pref, $city, $town) {
		if ($this->checkCache($pref, $city, $town)) {
			$this->logger->debugLog("CACHE HIT:" . $pref.$city.$town);
			return $this->__aCache[$pref][$city][$town];
		}
		$this->logger->debugLog("NO CACHE:" . $pref.$city.$town);
		return false;
	}
	
	public function normalizeAddress($addr) {
		if (preg_match('/[ヶ]/u', $addr)) {
			return preg_replace('/ヶ/u', 'ケ', $addr);
		}
		return $addr;
	}
	
	public function replace($addr, $from, $to) {
		return preg_replace('/'.$from.'/u', $to, $addr);
	}
	
	/**
	 * ほげ字ふげ → ほげ
	 * 字ふげ → ふげ
	 * @param unknown $addr
	 */
	public function firstAzaFromTown($addr) {
		$delimiter = '';
		if (preg_match('/大字/u', $addr)) {
			$delimiter = '大字';
		} else if (preg_match('/字/u', $addr)) {
			$delimiter = '字';
		}
		if ($this->isEmpty($delimiter)) {
			$this->logger->debugLog(" EMPTY DELIMIER = " . $addr);
		}
		$tmp = explode($delimiter, $addr);
		foreach ($tmp as $t) {
			if (strlen($t) > 0) {
				return $t;
			}
		}
	}
	
}