<?php
/**
 * Created by PhpStorm.
 * Date: 2017/06/06
 * Time: 16:46
 */

//require_once(__DIR__ . '/../../../ownerreport/OwnerReport/src/lib/environment.php');
require_once(__DIR__ . '/../lib/environment.php');
//require_once(__DIR__ . '/../lib/logic/FormOcrOutput.php');
require_once(LOGIC_DIR . '/FormOcrOutput.php');

$version = "0.01";

// 引数最低値
$limitCount = 3;
// 引数の数
$argvCount = count($argv);
// 現在のディレクトリ
$curDir = getcwd();

/**********************************************************************************
 * 関数定義
 */

/**
 * 使い方の表示
 * @param unknown $errMessage
 */
function printUsage($errMessage = null) {
    global $version;

    printf('//'. "\n");
    printf('// ACADATA作成:' . $version . "\n");
    printf('// VERSION: ' . $version . "\n");
    printf('// 仕様： '.'無し' . "\n");
    printf('//'. "\n");
    printf('// r: ソース(CSV)'. "\n");
    printf('// o: 出力用ディレクトリ'. "\n\n");
    printf("// Usage: create_acadata.php -r report1.csv -o dirname\n");

    if (!empty($errMessage)) {
        printf("// \n");
        printf("// \n");
        printf("// Error:" . $errMessage . "\n");
    }
}

/**
 * パラメータチェック
 * @param unknown $options
 * @return string
 */
function paramCheck($options) {

    if ($options == null || !is_array($options) || count($options) < 1) {
        return 'parameters empty';
    }

    if (!isset($options['r'])) {
        return 'Missing parameter.';
    }
}

/**
 * ファイルパスの取得
 */
function getFilePath($fileName) {
    global $curDir;

    // 絶対パスチェック
    if (strpos($fileName, '/') === 0) {
        // 最初が「/」なので絶対パスなので、そのまま返す
        return $fileName;
    }
    // 相対パスなら、現在ディレクトリをつけて返す
    return $curDir . '/' . $fileName;
}

/**
 * @param $filePath
 * @param bool $dirMode
 */
function fileCheckAndExit($filePath, $dirMode = false) {

    $check = false;
    if (file_exists($filePath)) {
        if (!$dirMode) {
            $check = is_file($filePath);
        } else {
            $check = is_dir($filePath);
        }
    }

    // printf("FILE: " . $filePath . " : '" . $check . "'\n" );

    if (!$check) {
        printf("// ERROR: %s is NOT FOUND OR INVALID FILE TYPE.\n", $filePath);
        exit(1);
    }
}

/**********************************************************************************
 * 開始
 */
if ($argvCount < $limitCount) {
    printUsage();
    exit(1);
}

$shortOptions = "r:o:";
// コマンドライン解析
$options = getopt($shortOptions);
$errMsg = paramCheck($options);
if (!empty($errMsg)) {
    printUsage($errMsg);
    exit(1);
}

// ファイルパス取得
$report1Path = getFilePath($options['r']);
$output1Path = realpath($options['o']);

// ファイル存在チェック
fileCheckAndExit($report1Path);

//データファイルと同じ場所へ出力
$logic = new FormOcrOutput($report1Path, $output1Path);
$logic->processing2();

