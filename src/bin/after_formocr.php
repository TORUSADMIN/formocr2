<?php
/**
 * Created by PhpStorm.
 * Date: 2017/06/13
 * Time: 21:46
 */

require_once(__DIR__ . '/../../../ownerreport/OwnerReport/src/lib/environment.php');
require_once(__DIR__ . '/../lib/logic/FormOcrOutput.php');

const PDF_INPUT_DIR = "../INPUT/";
const OCR_OUTPUT_DIR = "../PDF/";
const OCR_OUTPUT_FILE = "../PDF/textout/output.csv";

//const PDF_INPUT_DIR = "/Users/ocruser/Desktop/INPUT/";
//const OCR_OUTPUT_DIR = "/Users/ocruser/Desktop/PDF/";
//const OCR_OUTPUT_FILE = "/Users/ocruser/Desktop/PDF/textout/output.csv";

CreateNewFile();

/**
 * CSVデータからファイル名を抽出する
 * @return mixed|string|unknown
 */
function RenameFilename(){
    $pathdata = pathinfo(OCR_OUTPUT_FILE);
    $logic = new FormOcrOutput(OCR_OUTPUT_FILE, $pathdata["dirname"]);
    $logic->init();
    $renamefile = $logic->getFormOcrInputFilename();
    return $renamefile;
}

/**
 * ファイル名をSJISからUTF-8へ文字コード変換し、ファイル名を都道府県_市区_YYYYMMに変更する
 */
function CreateNewFile(){
    if (file_exists(OCR_OUTPUT_FILE)==true){
        SjisToUtf8(OCR_OUTPUT_FILE);
        $pathdata = pathinfo(OCR_OUTPUT_FILE);
        $renametmp = RenameFilename();
        $rename = mb_convert_encoding($renametmp,"SJIS","UTF-8");
        $new_file = $pathdata["dirname"]."/".$rename.".".$pathdata["extension"];
        rename(OCR_OUTPUT_FILE,$new_file);
    }
}

/**
 * SJISからUTF-8へ文字コード変換する
 * @param $filePath
 */
function SjisToUtf8($filePath){
    // setlocaleをまずは設定
    setlocale(LC_ALL, 'ja_JP.UTF-8');
    // 読み込んだSJISのデータをUTF-8に変換して保存
    file_put_contents($filePath, mb_convert_encoding(file_get_contents($filePath), 'UTF-8', 'SJIS'));
}

