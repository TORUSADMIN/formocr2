<?php
/**
 * Created by PhpStorm.
 * Date: 2017/06/15
 * Time: 12:40
 */

const CSV_DIR = "../CSV/";
const CSV_OUTPUT_DIR = "../PDF/textout/";

//const CSV_DIR = "/Users/ocruser/Desktop/PDF/textout/";

const CHECK_COMMAND = "php ../bin/create_formocr.php -r ";

main();

/**
 *　CSV項目ごとにエラーチェックし新規CSVファイルを出力するスクリプトを生成する
 */
function main(){

    $checkcsv_bat = CSV_DIR."start_checkcsv.bat";

    if (file_exists($checkcsv_bat) == true){
        unlink($checkcsv_bat);
    }
    //$fp = fopen($checkcsv_bat,"w");

    foreach (glob(CSV_OUTPUT_DIR."*.csv") as $filename) {
        //$pathdata = pathinfo($filename);
        $str = CHECK_COMMAND.$filename." -o ".CSV_DIR."\r\n";
        //fwrite($fp,$str);
        exec($str);
    }
    //fclose($fp);
}
