<?php
/**
 * 認識したCSVファイルをチェックしてからの変換です。
 * create_checkcsv.phpを実施してから、
 * 本ファイルを実行して、
 * CSVファイルをそのままコピーし、excelに書き込む。
 * excelファイルを生成し、指定フォルダの下に移動する。
 *
 * author lixin
 * ---2018/03/26---
 * */

const CSV_DIR = "../CSV/";//チェックしたのディレクトリ
require_once(__DIR__ . '/../lib/environment.php');
require_once(MODEL_DIR . '/CsvModelBase.php');
require_once(UTIL_DIR . '/PHPExcel-1.8/Classes/PHPExcel.php');
require_once(UTIL_DIR . '/PHPExcel-1.8/Classes/PHPExcel/Cell.php');
require_once(UTIL_DIR . '/PHPExcel-1.8/Classes/PHPExcel/IOFactory.php');

ChangeFileToExcel();

function main(){

}
/**
 * 処理関数
 */
function ChangeFileToExcel(){

	foreach(glob(CSV_DIR . "*.csv") as $filename){

		$book = new PHPExcel();
		$sheet = $book->getActiveSheet();
		$sheet->setTitle('Sheet1');

		$csv_file = new CsvModelBase($filename);
		$csv_title = $csv_file->getCsvHeaderData();
		$csv_body = $csv_file->getCsvBodyData();

		//print_r($csv_title);
		//タイトルをExcelに書き込む（１行目から追加）
		if(is_array($csv_title)){
			$column = 0;
			for($i=0; $i<count($csv_title); $i++){
				$sheet->setCellValueByColumnAndRow($column++, 1, $csv_title[$i]);//セルに書き込む
				//セルを文字列を設定する
				//$sheet->getCellByColumnAndRow($column, 1)->setValueExplicit($csv_title[$i], PHPExcel_Cell_DataType::TYPE_STRING);
			}
		}
		//ボディーをExcelに書き込む（２行目から）
		if(is_array($csv_body) && !empty($csv_body)){
			$row = 2;
			foreach($csv_body as $body_data){
				$col = 0;
				foreach($body_data as $value){
					//print($value);
					$sheet->setCellValueByColumnAndRow($col++, $row, $value);
					//$sheet->getCellByColumnAndRow($col, $row)->setValueExplicit($value, PHPExcel_Cell_DataType::TYPE_STRING);
				}
				$row++;
			}
		}
		$writer = PHPExcel_IOFactory::createWriter($book, 'Excel2007');
		//print_r(basename($filename));
		$writer->save(basename($filename, '.csv') . '.xlsx');
		//ファイルをCSVフォルダの下に移動する
		rename(basename($filename, '.csv') . '.xlsx', CSV_DIR . basename($filename, '.csv') . '.xlsx');
	}
}