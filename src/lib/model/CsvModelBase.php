<?php

/**
 * モデルの基底クラス
 */

require_once(LIB_DIR.'/utl/Logger.php');
require_once(LIB_DIR.'/utl/trait/EmptyTrait.php');
require_once(LIB_DIR.'/utl/trait/TrimTrait.php');


class CsvModelBase {
	use EmptyTrait, TrimTrait;

	// Util
	protected $logger;

	// CSVファイル関連
	protected $csvObject;
	protected $csvFilePath;
	protected $csvFileName;
	protected $delimiter;
	protected $enclosure;
	protected $_csvData;

	protected $columnCount = 0;



	const CSV_DELIMITER = ",";
	const TSV_DELIMITER = "\t";
	const CSV_ENCLOSURE = "\"";
	const ENCODE = "utf-8";

	const EMPTY_COLUMN_COUNT = 0;

	const HEADER_INDEX = 'header';
	const BODY_INDEX = 'body';
	const LINE_OFFSET = 1;

	private $isWin;


	public function __construct($filePath = null, $delimiter = self::CSV_DELIMITER,
			$columnCount = self::EMPTY_COLUMN_COUNT, $enclosure = self::CSV_ENCLOSURE) {
		$this->logger = new Logger();

		$this->isWin = (strncasecmp(PHP_OS, 'WIN', 3) === 0);

		$this->delimiter = $delimiter;
		$this->columnCount = $columnCount;
		$this->enclosure = $enclosure;
		// CSV初期化
		if (!$this->isEmpty($filePath)) {
			$this->initCsv($filePath);
		}
	}

	/**
	 * CSVカラムの正しい数
	 * @param unknown $count
	 */
	protected function setColumnCount($count) {
		$this->columnCount = $count;
	}

	/**
	 * CSVの初期化
	 * @throws Exception
	 */
	private function initCsv($filePath) {
		if (!$this->fileCheck($filePath)) {
			throw new Exception('Can not find CSV:' . $this->csvFilePath);
		}

		// OSによってLocaleの設定を変更する。
		$this->setupLocalePatch();

		$this->csvObject = new SplFileObject($filePath);

		// ファイルネームの取得
		$this->csvFileName = $this->csvObject->getFilename();
		// ファイルパスの取得
		$this->csvFilePath = $this->csvObject->getPath();
		// CSV属性の指定
		$this->csvObject->setFlags(SplFileObject::READ_CSV);
		$this->csvObject->setCsvControl($this->delimiter);

		// OSによってLocaleの設定を変更する。
		$this->unSetupLocalePatch();
	}

	/**
	 * ロケールの変更
	 */
	private function setupLocalePatch() {
		if ($this->isWin) {
			setLocale(LC_ALL, 'English_United States.1252');
		}
	}

	/**
	 * ロケールを元に戻す
	 */
	private function unSetupLocalePatch() {
		if ($this->isWin) {
			setlocale(LC_ALL, "Japanese_Japan.932");
		}
	}

	/**
	 * CSVのBODY列を取得する
	 * @param string $hasHeader
	 * @return unknown|boolean
	 */
	public function getCsvBodyData($hasHeader = true) {

		// $this->logger->debugLog($this->_csvData);
		if ($this->isEmpty($this->_csvData)) {
			$this->_csvData = $this->readCsvToArray($hasHeader);
		}

		if (!$this->isEmpty($this->_csvData)
				|| isset($this->_csvData[self::BODY_INDEX])) {
					return $this->_csvData[self::BODY_INDEX];
		}
		throw new Exception('CsvData is EMPTY');
		// return false;
	}

	/**
	 * CSVのヘッダ列を取得する
	 * @param string $hasHeader
	 * @return unknown|boolean
	 */
	public function getCsvHeaderData($hasHeader = true) {
		if ($this->isEmpty($this->_csvData)) {
			$this->_csvData = $this->readCsvToArray($hasHeader);
		}
		if (!$this->isEmpty($this->_csvData)
				|| isset($this->_csvData[self::HEADER_INDEX])) {
					return $this->_csvData[self::HEADER_INDEX];
		}
		return false;

	}

	/**
	 * CSVデータ行数を取得
	 */
	protected function getCsvBodyLineCount($hasHeader = true) {
		$body = $this->getCsvBodyData($hasHeader);
		return count($body);
	}

	/**
	 * CSVを読み込んだすべてのデータを返す
	 * @param string $reRead
	 */
	protected function readCsvToArray($hasHeader = true) {

		// ロケールのパッチ設定
		$this->setupLocalePatch();

		$result = array();
		$csvFile = $this->csvObject;
		$headerReaded = false;
		$lineNo = 0;
		foreach ($csvFile as $line) {
			$recCheck = $this->checkRecord($line, $lineNo);
			if (!$recCheck) {
				$lineNo++;
				continue;
			}
			// ヘッダ行の読み込み
			if ($hasHeader && !$headerReaded) {
				$result[self::HEADER_INDEX] = $line;
				$headerReaded = true;
			} else {
				// ボディの読み込み
				$result[self::BODY_INDEX][] = $line;
			}
			$lineNo++;
		}

		// ロケールパッチの設定を元に戻す
		$this->unSetupLocalePatch();
		return $result;

	}

	/**
	 * ファイルチェック
	 * @param unknown $filePath
	 * @throws Exception
	 */
	protected function fileCheck($filePath) {

		if ($this->isEmpty($filePath)) {
			return false;
		}

		if (!file_exists($filePath)) {
			return false;
		}
		return true;
	}

	/**
	 * レコードとして正しいかチェック
	 * @param unknown $record
	 * @return boolean
	 */
	protected function checkRecord($record, $lineNo) {
		// カラム数のチェック
		if ($this->columnCount > 0) {
			$recCnt = count($record);
			if ($recCnt > 0 && $this->columnCount != $recCnt) {
				$this->logger->debugLog("LINE SKIPPED! => Invalid Column Count. exptected:"
						. $this->columnCount . " FOUND:" . $recCnt
						. " LINE:" . $lineNo . " -> " . print_r($record, true));
				return false;
			}
		}

		// なにもセットしなければちぇっくしない
		return true;
	}

	/**
	 * 先頭のSingle Quoteを削除する
	 * @param unknown $str
	 * @return unknown|string
	 */
	protected function removeSingleQuote($str) {
		if (empty($str) || strpos($str, '\'') !== 0) {
			return $str;
		}
		return substr($str, 1); // ２文字目から返す
	}

	/**
	 * CSV書き込み
	 * @param unknown $filePath
	 * @param unknown $headers
	 * @param unknown $bodyLines
	 */
	public function writeCsv($filePath, $headers, $bodyLines) {
		// $this->logger->cLog($bodyLines);
		// ヘッダデータが存在しなければ終了
		if ($this->isEmpty($headers)) {
			throw new Exception('HEADER data is empty');
		}

		// ボディデータが存在しなければ終了
		if ($this->isEmpty($bodyLines)) {
			throw new Exception('BODY data is empty');
		}

		$writeFile = new SplFileObject($filePath, 'w');
		// ヘッダの書き込み
		foreach ($headers as $i => $h) {
			$writeFile->fputcsv($h, $this->delimiter, $this->enclosure);
		}

		// データ書き込み
		foreach ($bodyLines as $no => $line) {
			$outputLine = $line;
			/*
			if (is_array($line)) {
				$outputLine = implode(',', $line);
			}
			$this->logger->cLog($line);*/
			$writeFile->fputcsv($outputLine, $this->delimiter, $this->enclosure);
		}
	}

	/**
	 * lineから、指定のインデックスデータを取得し、つなげて戻す
	 * @param unknown $line
	 * @param unknown $indexes
	 * @throws Exception
	 * @return unknown|string|mixed
	 */
	public function getColumnsValue($line, $indexes) {
		if ($this->isEmpty($line)) {
			return $line;
		}
		if ($this->isEmpty($indexes)) {
			throw new Exception('Indexes is EMPTY.');
		}

		$tmpIndexes = (is_array($indexes)) ? $indexes : array($indexes);

		// 指定のINDEXのデータをつなげて返す
		$result = '';
		foreach ($tmpIndexes as $index) {
			if (!isset($line[$index])) {
				$this->logger->debugLog($line);
				throw new Exception('Index not found. INDEX = ' . $index);
			}
			$data = $this->trimEmspace($this->checkStr($line[$index]));
			$result .= $data;
		}
		return $result;
	}

}
