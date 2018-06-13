<?php

/**
 * Created by PhpStorm.
 * User: hoshino
 * Date: 2018/06/06
 * Time: 12:41
 */

/**
 * EXCELモデルの基底クラス
 */

require_once(LIB_DIR.'/utl/Logger.php');
require_once(LIB_DIR.'/utl/trait/EmptyTrait.php');
require_once(LIB_DIR.'/utl/trait/TrimTrait.php');
require_once(UTIL_DIR . '/PHPExcel-1.8/Classes/PHPExcel.php');
require_once(UTIL_DIR . '/PHPExcel-1.8/Classes/PHPExcel/Cell.php');
require_once(UTIL_DIR . '/PHPExcel-1.8/Classes/PHPExcel/IOFactory.php');


class ExcelModelBase
{
    use EmptyTrait, TrimTrait;

    // Util
    protected $logger;

    // EXCELファイル関連
    protected $excelObject;
    protected $excelFilePath;
    protected $excelFileName;
    protected $sheetIndex;
    protected $_excelData;

    const ENCODE = "utf-8";
    const EMPTY_COLUMN_COUNT = 0;

    const HEADER_INDEX = 'header';
    const BODY_INDEX = 'body';
    const LINE_OFFSET = 1;

    public function __construct($filePath = null, $sheetIndex) {
        $this->logger = new Logger();

        //$this->isWin = (strncasecmp(PHP_OS, 'WIN', 3) === 0);

        $this->columnCount = 0;
        // CSV初期化
        if (!$this->isEmpty($filePath)) {
            $this->initExcel($filePath,$sheetIndex);
        }
    }

    /**
     * EXCELの初期化
     * @throws Exception
     */
    private function initExcel($filePath, $sheetIndex = 0) {
        if (!$this->fileCheck($filePath)) {
            throw new Exception('Can not find EXCEL:' . $this->excelFilePath);
        }

        // OSによってLocaleの設定を変更する。
        //$this->setupLocalePatch();

        $tmpfile = "tmp_excel.xlsx";

        $ret = copy($filePath, dirname($filePath) . '/' . $tmpfile);

        // Excel2007形式(xlsx)テンプレートの読み込み
        $reader = PHPExcel_IOFactory::createReader('Excel2007');
        $this->excelObject = $reader->load(dirname($filePath) . '/' . $tmpfile);

        //テンポラリーファイル削除
        unlink(dirname($filePath) . '/' . $tmpfile);

        // ファイルネームの取得
        $this->excelFileName = basename($filePath);
        // ファイルパスの取得
        $this->excelFilePath = dirname($filePath) . '/';
        // CSV属性の指定
        $this->SheetIndex = $sheetIndex;

        // OSによってLocaleの設定を変更する。
        //$this->unSetupLocalePatch();
    }

    /**
     * EXCELのBODY列を取得する
     * @param string $hasHeader
     * @return unknown|boolean
     */
    public function getExcelBodyData($hasHeader = true) {

        // $this->logger->debugLog($this->_csvData);
        if ($this->isEmpty($this->_excelData)) {
            $this->_excelData = $this->readExcelToArray($hasHeader);
        }

        if (!$this->isEmpty($this->_excelData)
            || isset($this->_excelData[self::BODY_INDEX])) {
            return $this->_excelData[self::BODY_INDEX];
        }
        throw new Exception('ExcelData is EMPTY');
        // return false;
    }

    /**
     * EXCELのヘッダ列を取得する
     * @param string $hasHeader
     * @return unknown|boolean
     */
    public function getExcelHeaderData($hasHeader = true) {
        if ($this->isEmpty($this->_excelData)) {
            $this->_excelData = $this->readExcelToArray($hasHeader);
        }
        if (!$this->isEmpty($this->_excelData)
            || isset($this->_excelData[self::HEADER_INDEX])) {
            return $this->_excelData[self::HEADER_INDEX];
        }
        return false;

    }

    /**
     * EXCELデータ行数を取得
     */
    protected function getExcelBodyLineCount($hasHeader = true) {
        $body = $this->getExcelBodyData($hasHeader);
        return count($body);
    }

    /**
     * EXCELを読み込んだすべてのデータを返す
     * @param string $reRead
     */
    protected function readExcelToArray($hasHeader = true) {

        // ロケールのパッチ設定
        //$this->setupLocalePatch();

        $result = array();
        $book = $this->excelObject;
        $headerReaded = false;
        $lineNo = 0;

        //有効
        $book->setActiveSheetIndex($this->SheetIndex);
        // 最初のシートを取得
        $sheet = $book->getActiveSheet();

        $headerReaded = false;

        // 行
        foreach ($sheet->getRowIterator() as $row)
        {
            // セル
            foreach ($row->getCellIterator() as $cell)
            {
                // 各セルの値を取得
                echo $cell->getValue();
                $line[] = $cell->getValue();
            }

            $recCheck = $this->checkRecord($line, $lineNo);
            if (!$recCheck) {
                $lineNo++;
                continue;
            }

            // ヘッダ行の読み込み
            if ($hasHeader && !$headerReaded) {
                $result[self::HEADER_INDEX] = $line;
                $headerReaded = true;
                $line =array();
            } else {
                $result[self::BODY_INDEX][] = $line;
                $line =array();
            }
            $lineNo++;
        }

        // メモリの解放
        $this->excelObject->disconnectWorksheets();
        unset($this->excelObject);

        // ロケールパッチの設定を元に戻す
        //$this->unSetupLocalePatch();
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

    /**
     * CSV書き込み
     * @param unknown $filePath
     * @param unknown $headers
     * @param unknown $bodyLines
     */
    public function write($filePath, $headers, $bodyLines) {
        // $this->logger->cLog($bodyLines);
        // ヘッダデータが存在しなければ終了
        if ($this->isEmpty($headers)) {
            throw new Exception('HEADER data is empty');
        }

        // ボディデータが存在しなければ終了
        if ($this->isEmpty($bodyLines)) {
            throw new Exception('BODY data is empty');
        }

        $csv_title = $headers;
        $csv_body = $bodyLines;

        //EXCEL新規作成
        $book = new PHPExcel();
        $sheet = $book->getActiveSheet();

        //タイトルをExcelに書き込む（１行目から追加）
        if(is_array($csv_title)){
            foreach ($csv_title as $header_data){
                $column = 0;
                foreach($header_data as $value){
                    $sheet->setCellValueByColumnAndRow($column++, 1, $value);//セルに書き込む
                }
            }
            /*for($i=0; $i<count($csv_title); $i++){
                $sheet->setCellValueByColumnAndRow($column++, 1, $csv_title[$i]);//セルに書き込む
                //セルを文字列を設定する
                //$sheet->getCellByColumnAndRow($column, 1)->setValueExplicit($csv_title[$i], PHPExcel_Cell_DataType::TYPE_STRING);]
            }*/
        }
        //ボディーをExcelに書き込む（２行目から）
        if(is_array($csv_body) && !empty($csv_body)){
            $row = 2;
            foreach($csv_body as $body_data){
                $col = 0;
                foreach($body_data as $value){
                    //print($value);
                    $sheet->getCellByColumnAndRow($col, $row)->setValueExplicit($value, PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValueByColumnAndRow($col, $row, $value);
                    $col++;
                }
                $row++;
            }
        }

        //print_r(basename($filename));
        $tmpfilename = "tmp_excel01.xlsx";
        $filename = $this->excelFileName;

        //ファイルに書き込み
        $writer = PHPExcel_IOFactory::createWriter($book, 'Excel2007');
        $writer->save(dirname($filePath) . "/" . $tmpfilename);

        //メモリから解放
        $book->disconnectWorksheets();
        unset($book);

        //ファイルを指定フォルダの下に移動する
        rename(dirname($filePath) . "/" .$tmpfilename, $filePath);
        // メモリの解放

    }

}