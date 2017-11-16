<?php
/**
 * Created by PhpStorm.
 * Date: 2017/06/15
 * Time: 10:05
 * INPUTディレクトリからPDFファイル１つをPDFディレクトリへコピーする
 * FORMOCRを起動して認識処理、出力処理まで行う
 */

const PDF_INPUT_DIR = "../INPUT/"; //PDFファイルをまとめておくディレクトリ
const PDF_COPY_DIR = "../PDF/"; //PDFファイルをFormOCRで認識させるディレクトリ

//const PDF_INPUT_DIR = "/Users/ocruser/Desktop/INPUT/";
//const PDF_COPY_DIR = "/Users/ocruser/Desktop/PDF/";

//FormOCRの起動ディレクトリ
const FRP_DIR ="C:\Program Files (x86)\FRP";

/**
 * 開始
 */
$ret = CopyNewOcrFile();
if ($ret <> 0 ) {
    exit(-1);
}
$ret = ExecFormOcr();
exit($ret);

/**
 * FORMOCRを起動して認識処理、出力処理まで行う
 */
function ExecFormOcr(){
    foreach (glob(PDF_COPY_DIR."*.pdf") as $filename) {
        chdir(FRP_DIR);
        system('"FrpMain.exe" "不動産受付帳"',$retval);
        //0:正常終了、それ以外異常終了
        if ($retval<>0) {
            echo "ErrorCode:$retval FormOCRでエラーが発生しました。";
        }
        echo "戻り値:$retval";
        return $retval;
    }
}

/**
 * INPUTディレクトリからPDFファイル１つをPDFディレクトリへコピーする
 */
function CopyNewOcrFile(){
    $ret = false;
    //OUTPUTフォルダにPDFが存在しない場合
    foreach (glob(PDF_COPY_DIR."*.pdf") as $filename) {
        $ret = true;
        break;
    }
    if ($ret == false) {
        $count = 0;
        //INPUTフォルダから１ファイルをコピーする
        foreach (glob(PDF_INPUT_DIR."*.pdf") as $filename) {
            $count++;
            copy($filename,PDF_COPY_DIR.basename($filename));
            unlink($filename);
            //break;
            return 0;
        }
        if ($count == 0) {
            echo "PDFファイルが存在していません。";
            return -1;
        }
    }
    return 0;
}