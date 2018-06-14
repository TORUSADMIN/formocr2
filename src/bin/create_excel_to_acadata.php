<?php
/**
 * Created by PhpStorm.
 * User: hoshino
 * Date: 2018/06/06
 * Time: 10:08
 */

//const CSV_DIR = "../CSV/";//チェックしたのディレクトリ
require_once(__DIR__ . '/../lib/environment.php');
require_once(MODEL_DIR . '/CsvModelBase.php');
require_once(UTIL_DIR . '/Logger.php');
require_once(UTIL_DIR . '/PHPExcel-1.8/Classes/PHPExcel.php');
require_once(UTIL_DIR . '/PHPExcel-1.8/Classes/PHPExcel/Cell.php');
require_once(UTIL_DIR . '/PHPExcel-1.8/Classes/PHPExcel/IOFactory.php');

main();

function main (){
    make_acadata();
}

function make_acadata(){

    ini_set('memory_limit', '1G');
    $logger = new Logger();

    foreach(glob("*.xlsx") as $filename) {
    	//$logger->cLog($filename);
        //$filename = mb_convert_encoding($filename,"UTF-8","SJIS");
        $logger->cLog('ファイル名:');
        $logger->cLog($filename);
        //$tmpfile = "tmp_excel.xlsx";
        //$ret = copy($filename, $tmpfile);
        // Excel2007形式(xlsx)テンプレートの読み込み
        $reader = PHPExcel_IOFactory::createReader('Excel2007');
        //$logger->cLog($reader);

        //$book = $reader->load(CSV_DIR .$tmpfile);
        $book = $reader->load($filename);
        //有効
        $book->setActiveSheetIndex(0);
        // 最初のシートを取得
        $sheet = $book->getActiveSheet();



        // 行
        foreach ($sheet->getRowIterator() as $row)
        {
            // セル
            foreach ($row->getCellIterator() as $cell)
            {
                // 各セルの値を取得
                $logger->cLog($cell->getValue());
                //echo $cell->getValue();
            }
        }

        //setlocale(LC_ALL, "Japanese_Japan.932");
    }
}

function open_excel($filename){
    //PHPExcelの新規オブジェクト作成
    $excel = new PHPExcel();

    // Excel2007形式(xlsx)テンプレートの読み込み
    $reader = PHPExcel_IOFactory::createReader('Excel2007');
    $excel = $reader->load($filename);
    return $excel;
}