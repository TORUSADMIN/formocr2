<?php
/**
 * Created by PhpStorm.
 * User: hoshino
 * Date: 2017/06/26
 * Time: 14:07
 */

const CSV_OUTPUT_DIR = "../CSV/";
const LOADDATA_OUTPUT_DIR = "../LoadData/";
const ACADATA_COMMAND = "php ../bin/create_acadata.php -r ";

main();

function main(){

    $acadata_bat = LOADDATA_OUTPUT_DIR."start_acadata.bat";

    if (file_exists($acadata_bat) == true){
        unlink($acadata_bat);
    }
    //$fp = fopen($acadata_bat,"w");

    foreach (glob(CSV_OUTPUT_DIR."*.csv") as $filename) {
        //$filename = mb_convert_encoding($filename,"UTF-8","SJIS");
        //$pathdata = pathinfo($filename);
        $str = ACADATA_COMMAND.$filename." -o ".LOADDATA_OUTPUT_DIR."\r\n";
        //fwrite($fp,$str);
        exec($str);
    }
    //fclose($fp);
}