<?php
/**
 * Created by PhpStorm.
 * Date: 2017/06/15
 * Time: 10:05
 * INPUTディレクトリからPDFファイル分バッチ処理する
 */

const PDF_INPUT_DIR = "../INPUT/"; //PDFファイルをまとめておくディレクトリ

/**
 * 開始
 */

Main();


/**
 * INPUTディレクトリからPDFファイル分バッチ処理する
 */
function Main(){
    foreach (glob(PDF_INPUT_DIR."*.pdf") as $filename) {
        echo "{$filename}のstart_formocr処理を開始します。\n";
        exec('call 1_start_formocr.bat', $arr, $retval);
        if ($retval <> 0) {
            echo "エラーが発生しましたので、処理を停止しました。\n";
            break;
        } else {
            echo "{$filename}の処理が終わりました。after_formocrを開始します。\n";
            exec('call 2_after_formocr.bat');
        }
    }
}