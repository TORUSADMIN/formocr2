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
