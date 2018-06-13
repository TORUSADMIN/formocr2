<?php
/**
 * Created by PhpStorm.
 * Date: 2017/05/29
 * Time: 15:32
 */

require_once(LIB_DIR.'/model/CsvModelBase.php');
require_once(LIB_DIR.'/utl/AddressModifier.php');
require_once(LIB_DIR.'/utl/PersonalNameModifier.php');

class FormOcrReport1 extends CsvModelBase {
    // AddressModifierクラス
    private $addrMod;
    // ファイルデータそのもの
    private $txtDataOrg;
    // CSV解析後データ
    private $csvDataOrg;

    ////////////////
    // 定数
    const DATA_DELIM = '_#_';

    const DEFAULT_COL_CNT = 0; // 受付番号,受付番号Err,受付日,受付日Err,順序,順序Err,区分,区分Err,地番,地番Err,目的,目的Err,ファイル名,ページ,ページエラー

    const CHG_ADDR_CHOME_KAN2ZEN = 'CHK_ADDR_CHOME_KAN2ZEN';
    const CHG_ADDR_CHOME_ZEN2HAN = 'CHK_ADDR_CHOME_ZEN2HAN';
    const CHG_ADDR_EDA_NO_ZEN2HAN = 'CHK_ADDR_EDA_NO_ZEN2HAN';
    const CHG_ADDR_KANA_ZEN2HAN = 'CHG_ADDR_KANA_ZEN2HAN';

    /** 所有者事項＿レポート型式１.CSV INDEX */
    const IDX_RECEIPT_NO = 0; 		// 受付番号
    const IDX_RECEIPT_NO_ERR = 1;	// 受付番号エラー（A、Rが付くもの）
    const IDX_RECEIPT_DATE = 2;		// 受付日
    const IDX_RECEIPT_DATE_ERR = 3;	// 受付日エラー（A、Rが付くもの）
    const IDX_RECEIPT_SEQ = 4;	    // 順序（単独）、（連続）、（連先）
    const IDX_RECEIPT_SEQ_ERR = 5;  // 順序（単独）、（連続）、（連先）エラー（A、Rが付くもの）
    const IDX_GROUP = 6;             // 区分　{新）土地・建物　既）土地・建物}
    const IDX_GROUP_ERR = 7;        // 区分エラー　{新）土地・建物　既）土地・建物}（A、Rが付くもの）
    const IDX_BUKKEN_ADDR = 8;      // 地番
    const IDX_BUKKEN_ADDR_ERR=9;   // 地番エラー（A、Rが付くもの）
    const IDX_PURPOSE = 10;		    // 目的
    const IDX_PURPOSE_ERR = 11;		// 目的エラー（A、Rが付くもの）
    const IDX_FILE_NAME = 12;		// ファイル名＿拡張子なし
    const IDX_PAGE = 13;		        // ページ数
    const IDX_PAGE_ERR = 14;		    // ページエラー（A、Rが付くもの）

    /**
     * コンストラクタ
     * @param unknown $report1CsvPath CSV
     * @param string $delimiter 基本的には「,」
     */
    public function __construct() {
        $this->addrMod = new AddressModifier();
    }

    //////////////////////////////////////////////////////////////////////
    // 初期化
    //////////////////////////////////////////////////////////////////////

    /**
     * ファイルの存在確認とデータの読み込み
     */
    public function initFormOcrReport1($reportCsvPath
        , $delimiter = CsvModelBase::CSV_DELIMITER
        , $columnCnt = self::DEFAULT_COL_CNT) {

        parent::__construct($reportCsvPath, $delimiter, $columnCnt);

        $this->csvDataOrg = $this->getCsvBodyData(false);
    }

    public function initCsvData($csvData){
        $this->csvDataOrg = $csvData;
    }

    /**
     * CSVのBODY列を取得する
     * @param string $hasHeader
     * @return unknown|boolean
     */
    public function getCsvBodyDataEx($hasHeader = true) {

        $this->logger->debugLog($this->_csvData);
        if ($this->isEmpty($this->_csvData)) {
            $this->_csvData = $this->readCsvToArrayEx($hasHeader);
        }

        if (!$this->isEmpty($this->_csvData)
            || isset($this->_csvData[self::BODY_INDEX])) {
            return $this->_csvData[self::BODY_INDEX];
        }
        throw new Exception('CsvData is EMPTY');
        // return false;
    }

    private function readCsvToArrayEx($hasHeader = true) {
        $result = array();
        $csvFile = $this->csvObject;
        echo $this->csvFilePath;
        echo $this->csvFileName;

        $headerReaded = false;

        $fileData = file_get_contents($this->csvFilePath . "/" . $this->csvFileName);
        //$fileData = file_get_contents("./in/test_org.csv");

        $fileDataUtf8 = mb_convert_encoding($fileData, 'UTF-8','Shift-JIS');
        $temp = tmpfile();
        $meta = stream_get_meta_data($temp);
        //一時ファイルに書き込み
        fwrite($temp,$fileDataUtf8);
        //ファイルポイントの位置を先頭に
        rewind($temp);

        $fp = fopen($meta['uri'],"r");

        $lineNo = 0;
        while ($line = fgets($fp)) {
            //LF,CRを削除
            $wline = str_replace( array("\x0a", "\x0d"), '', $line );
            $wline = explode(",",$wline);
            $result[self::BODY_INDEX][] = $wline;
            $lineNo++;
        }
        return $result;

    }

    /**
     * 受付番号を取得する
     * @param unknown $line
     * @return unknown|string|mixed
     */
    public function getReceiptNo($line) {
        return $this->getColumnsValue($line, array(self::IDX_RECEIPT_NO));
    }

    /**
     * 受付番号エラーを取得する
     * @param unknown $line
     * @return unknown|string|mixed
     */
    public function getReceiptNo_Err($line) {
        return $this->getColumnsValue($line, array(self::IDX_RECEIPT_NO_ERR));
    }

    /**
     * 受付日を取得する
     * @param unknown $line
     * @return unknown|string|mixed
     */
    public function getReceiptDate($line) {
        return $this->getColumnsValue($line, array(self::IDX_RECEIPT_DATE));
    }

    /**
     * 受付日エラーを取得する
     * @param unknown $line
     * @return unknown|string|mixed
     */
    public function getReceiptDate_Err($line) {
        return $this->getColumnsValue($line, array(self::IDX_RECEIPT_DATE_ERR));
    }

    /**
     * 順序（単独）、（連続）、（連先）を取得する
     * @param unknown $line
     * @return unknown|string|mixed
     */
    public function getReceiptSeq($line) {
        return $this->getColumnsValue($line, array(self::IDX_RECEIPT_SEQ));
    }

    /**
     * 順序（単独）、（連続）、（連先）エラーを取得する
     * @param unknown $line
     * @return unknown|string|mixed
     */
    public function getReceiptSeq_Err($line) {
        return $this->getColumnsValue($line, array(self::IDX_RECEIPT_SEQ_ERR));
    }

    /**
     * 区分　{新）土地・建物　既）土地・建物}を取得する
     * @param unknown $line
     * @return unknown|string|mixed
     */
    public function getGroup($line) {
        return $this->getColumnsValue($line, array(self::IDX_GROUP));
    }

    /**
     * 区分　{新）土地・建物　既）土地・建物}エラーを取得する
     * @param unknown $line
     * @return unknown|string|mixed
     */
    public function getGroup_Err($line) {
        return $this->getColumnsValue($line, array(self::IDX_GROUP_ERR));
    }

    /**
     * 地番を取得する
     * @param unknown $line
     * @return unknown|string|mixed
     */
    public function getBukkenAddr($line) {
        $str = $this->getColumnsValue($line, array(self::IDX_BUKKEN_ADDR));
        //「－－」は「－」に変換
        $Tmp1 = str_replace('－－','－', $str);
        $Tmp1 = str_replace('−−','－', $Tmp1);
        //UTF8の「−」(UNICODE表 e28890+2）は「－」に変換
        $Tmp1 = str_replace('−','－', $Tmp1);
        return $Tmp1;

    }

    /**
     * 地番エラーを取得する
     * @param unknown $line
     * @return unknown|string|mixed
     */
    public function getBukkenAddr_Err($line) {
        return $this->getColumnsValue($line, array(self::IDX_BUKKEN_ADDR_ERR));
    }

    /**
     * 目的を取得する
     * @param unknown $line
     * @return unknown|string|mixed
     */
    public function getPurpose($line) {
        return $this->getColumnsValue($line, array(self::IDX_PURPOSE));
    }

    /**
     * 目的エラーを取得する
     * @param unknown $line
     * @return unknown|string|mixed
     */
    public function getPurpose_Err($line) {
        return $this->getColumnsValue($line, array(self::IDX_PURPOSE_ERR));
    }

    /**
     * ファイル名を取得する
     * @param unknown $line
     * @return unknown|string|mixed
     */
    public function getFilename($line) {
        return $this->getColumnsValue($line, array(self::IDX_FILE_NAME));
    }

    /**
     * ページを取得する
     * @param unknown $line
     * @return unknown|string|mixed
     */
    public function getPage($line) {
        return $this->getColumnsValue($line, array(self::IDX_PAGE));
    }

    /**
     * ページを取得する
     * @param unknown $line
     * @return unknown|string|mixed
     */
    public function getPage_Err($line) {
        return $this->getColumnsValue($line, array(self::IDX_PAGE_ERR));
    }

    public function getPrefecture($line){
        $FilenameTmp = $this->getColumnsValue($line, array(self::IDX_FILE_NAME));
        $FilenameTmp = str_replace("＿","_",$FilenameTmp);
        $oArraytmp = explode("_", $FilenameTmp);

        if (count($oArraytmp) >= 3) {
            return $oArraytmp[0];
        } else {
            throw new Exception('Prefecture not found. FileName = ' . $FilenameTmp);
        }
    }

    public function getYear($line){
        $FilenameTmp = $this->getColumnsValue($line, array(self::IDX_FILE_NAME));
        $FilenameTmp = str_replace("＿","_",$FilenameTmp);
        $oArraytmp = explode("_", $FilenameTmp);
        if (count($oArraytmp) >= 3) {
            return substr($oArraytmp[2],0,4);
        } else {
            throw new Exception('YEAR not found. FileName = ' . $FilenameTmp);
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

        // 指定のINDEXのデータをつなげて返す
        $result = '';
        foreach ($indexes as $index) {
            if (!isset($line[$index])) {
                throw new Exception('Index not found. INDEX = ' . $index);
            }
            $data = $this->trimEmspace($this->checkStr($line[$index]));
            $result .= $data;
        }
        return $result;
    }

}