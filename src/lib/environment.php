<?php
/**
 * 環境設定定義
 */

$apppath = pathinfo(__DIR__);

if (!defined('READ_ENVIRONMENT')) {

	// 環境
	define('APP_ROOT', $apppath['dirname']);
	define('LOGGER_DIR', APP_ROOT.'/logs');
	define('BIN_DIR', APP_ROOT.'/bin');
	define('LIB_DIR', APP_ROOT.'/lib');
	define('DEF_DIR', LIB_DIR.'/def');
	define('MODEL_DIR', LIB_DIR.'/model');
	define('UTIL_DIR', LIB_DIR.'/utl');
	define('LOGIC_DIR', LIB_DIR.'/logic');
	define('ABSTRACT_DIR', LIB_DIR.'/abstract');
	define('EXCEPTION_DIR', LIB_DIR.'/exception');


	define('BUNJO_DIR', APP_ROOT.'/bunjo');
	define('BUNJO_LOGIC_DIR', BUNJO_DIR.'/logic');

	require_once(DEF_DIR.'/global_define.php');

	define('READ_ENVIRONMENT', true);
}
/*
 * evironment其实就是定义了各种代表路径的常量
 *
 *  1----pathinfo — 返回文件路径的信息
	$path_parts = pathinfo('/www/htdocs/inc/lib.inc.php');
	echo $path_parts['dirname'], "\n";    /www/htdocs/inc
	echo $path_parts['basename'], "\n";   lib.inc.php
	echo $path_parts['extension'], "\n";   php
	echo $path_parts['filename'], "\n"; // since PHP 5.2.0   lib.inc

	2----defined — 检查某个名称的常量是否定义
	defined 只对常数有效
	对变量要用isset查看

	3----require_once 就是包含某文件

 * */