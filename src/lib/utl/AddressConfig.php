<?php

/**
 * 住所変換設定
 * @author moroishi
 *
 */
class AddressConfig {
	
	public $firstHalfConfig;
	public $chomeConfig;
	public $edaNoConfig;
	public $buildConfig;
	public $foreignConfig;
	
	/* インデックス */
	const IDX_KANA = '_kana';
	const IDX_KANA_HAIHUN = '_kanaHaihun';
	const IDX_NUMBER_CASE = '_numberCase';
	const IDX_ALPHABET_CASE = '_alphabetCase';
	// 丁目を変換するか？
	const IDX_CNV_CHOME = '_cnvChome'; // 変換するかしないか？する場合は、NUMBERCASEに依存
	
	/* 設定タイプ */
	/**
	 * 住所の前半
	 * @var integer
	 */
	const TYPE_CITY = 1;
	/**
	 * 丁目
	 * @var integer
	 */
	const TYPE_CHOME = 2;
	/**
	 * 枝番
	 * @var integer
	 */
	const TYPE_AFTER = 3;
	/**
	 * 建物名
	 * @var integer
	 */
	const TYPE_BUILD = 4;
	/**
	 * 海外
	 * @var integer
	 */
	const TYPE_FOREIGN = 10;
	
	
	public function __construct(
			$firstHalfConfig = null,
			$chomeConfig = null,
			$edaNoConfig = null,
			$buildConfig = null,
			$foreignConfig = null
			) {
		$this->setConfig(self::TYPE_CITY);
		$this->setConfig(self::TYPE_CHOME);
		$this->setConfig(self::TYPE_AFTER);
		$this->setConfig(self::TYPE_BUILD);
		$this->setConfig(self::TYPE_FOREIGN);
	}
	
	/**
	 * コンフィグデータの設定
	 * @param unknown $type
	 * @param unknown $config
	 */
	public function setConfig($type, $config = null) {
		
		$cfg = ($config !== null) ? $config : $this->createConfigUnit();
		
		switch ($type) {
			case self::TYPE_CITY:
				$this->firstHalfConfig = $cfg; break;
			case self::TYPE_CHOME:
				$this->chomeConfig = $cfg; break;
			case self::TYPE_AFTER:
				$this->edaNoConfig = $cfg; break;
			case self::TYPE_BUILD:
				$this->buildConfig = $cfg; break;
			case self::TYPE_FOREIGN:
				$this->foreignConfig = $cfg; break;
			default:
				break;
		}
	}
	
	/**
	 * 指定のタイプで、変換が必要か否か
	 * @param unknown $type
	 * @return boolean
	 */
	public function isNeedConvert($type) {
		$indexes = $this->createIndexes();
		foreach ($indexes as $idx) {
			if (CNV_NOT !== $this->getConfigValue($type, $idx)) {
				return true;
			}
		}
		return false;
	}
	
	
	/**
	 * カナの変換設定取得
	 * @param unknown $type 前半、枝番、海外
	 * @return string|unknown
	 */
	public function getKana($type) {
		return $this->getConfigValue($type, self::IDX_KANA);
	}
	
	/**
	 * カナの長音の変換設定
	 * @param unknown $type 前半、枝番、海外
	 * @return string|unknown
	 */
	public function getKanaHaihun($type) {
		return $this->getConfigValue($type, self::IDX_KANA_HAIHUN);
	}
	
	/**
	 * 数字の変換設定
	 * @param unknown $type 前半、丁目の数字、枝番、海外
	 * @return string|unknown
	 */
	public function getNumberCase($type) {
		return $this->getConfigValue($type, self::IDX_NUMBER_CASE);
	}
	
	/**
	 * 英字の変換設定
	 * @param unknown $type
	 * @return string
	 */
	public function getAlphabetCase($type) {
		return $this->getConfigValue($type, self::IDX_ALPHABET_CASE);
	}
	
	/**
	 * 丁目（丁）を「ー」の変換をするか？
	 * @return string
	 */
	public function getCnvChome() {
		return $this->getConfigValue(self::TYPE_CHOME, self::IDX_CNV_CHOME);
	}
	
	public function getConfigValue($type, $key) {
		$config = null;
		switch ($type) {
			case self::TYPE_CITY:
				$config = $this->firstHalfConfig; break;
			case self::TYPE_CHOME:
				$config = $this->chomeConfig; break;
			case self::TYPE_AFTER:
				$config = $this->edaNoConfig; break;
			case self::TYPE_BUILD:
				$config = $this->buildConfig; break;
			case self::TYPE_FOREIGN:
				$config = $this->foreignConfig; break;
			default:
				return CNV_NOT;
		}
		if ($config !== null && isset($config[$key])) {
			return $config[$key];
		} else {
			return CNV_NOT;
		}
	}
	
	public function createConfigForNormalize() {
		$hanConfig = $this->createConfigUnit(CNV_HAN, CNV_HAN, CNV_HAN, CNV_HAN, CNV_HAN);
		$this->setConfig(self::TYPE_CITY, $hanConfig);
		$this->setConfig(self::TYPE_CHOME, $hanConfig);
		$this->setConfig(self::TYPE_AFTER, $hanConfig);
		$this->setConfig(self::TYPE_BUILD, $hanConfig);
		$this->setConfig(self::TYPE_FOREIGN, $hanConfig);
		
		return $this;
	}
	
	public function createConfigForNotConvert() {
		$notConfig = $this->createConfigUnit(CNV_NOT, CNV_NOT, CNV_NOT, CNV_NOT, CNV_NOT);
		$this->setConfig(self::TYPE_CITY, $notConfig);
		$this->setConfig(self::TYPE_CHOME, $notConfig);
		$this->setConfig(self::TYPE_AFTER, $notConfig);
		$this->setConfig(self::TYPE_BUILD, $notConfig);
		$this->setConfig(self::TYPE_FOREIGN, $notConfig);
	
		return $this;
	}
	
	/**
	 * 家屋番号チェック用住所設定
	 * @return AddressConfig
	 */
	public function createConfigForKaokuNoCheck() {
		$cityConfig = $this->createConfigUnit();
		$chomeConfig = $this->createConfigUnit(CNV_NOT, CNV_NOT, CNV_ZEN, CNV_NOT, CNV_NOT); // ３丁目
		$afterConfig = $this->createConfigUnit(CNV_NOT, CNV_NOT, CNV_HAN, CNV_HAN);
		$foreignConfig = $this->createConfigUnit(CNV_NOT, CNV_NOT, CNV_HAN, CNV_HAN);
		
		$this->setConfig(self::TYPE_CITY, $cityConfig);
		$this->setConfig(self::TYPE_CHOME, $chomeConfig);
		$this->setConfig(self::TYPE_AFTER, $afterConfig);
		$this->setConfig(self::TYPE_FOREIGN, $foreignConfig);
		
		return $this;
	}
	
	
	

	/**
	 * 単体の変換設定データの作成
	 * すべての引数は、CNV_HAN,CNV_ZEN,CNV_NOTをとり得る
	 * @param string $kana
	 * @param string $kanaHaihun
	 * @param string $numberCase
	 * @param string $convertChome 丁目を変換するか？
	 * @return string[]
	 */
	public function createConfigUnit(
			$kana = CNV_NOT,
			$kanaHaihun = CNV_NOT,
			$numberCase = CNV_NOT,
			$alphabetCase = CNV_NOT,
			$convertChome = CNV_HAN
			) {
		$config = array(
				self::IDX_KANA => $kana,
				self::IDX_KANA_HAIHUN => $kanaHaihun,
				self::IDX_NUMBER_CASE => $numberCase,
				self::IDX_ALPHABET_CASE => $alphabetCase,
				self::IDX_CNV_CHOME => $convertChome
				);
		return $config;
	}
	
	/**
	 * Loop用にインデックスの配列の作成
	 * @return string[]
	 */
	private function createIndexes() {
		$indexes = array(
				self::IDX_KANA,self::IDX_KANA_HAIHUN,
				self::IDX_NUMBER_CASE,
				self::IDX_ALPHABET_CASE,
				self::IDX_CNV_CHOME
		);
		return $indexes;
	}
	
	
}