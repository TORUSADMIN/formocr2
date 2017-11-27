<?php
/**
 * ログ出力
 */

class Logger {

	const LOG_SUFFIX = '.log';
	const DEBUG_LOG_FILENAME = 'debug_';
	const INFO_LOG_FILENAME = 'info_';
	const ERR_LOG_FILENAME = 'error_';
	
	
	/**
	 * メソッド開始ログ
	 */
	public function methodStart()
	{
		$bt = debug_backtrace();
	
		$this->doOutputLog(LOGGER_DEBUG, $bt[0]['file'] . '::' . $bt[1]['function'] . '():' . $bt[0]['line'], 'start');
	}
	
	/**
	 * メソッド終了ログ
	 */
	public function methodEnd()
	{
		$bt = debug_backtrace();
	
		$this->doOutputLog(LOGGER_DEBUG, $bt[0]['file'] . '::' . $bt[1]['function'] . '():' . $bt[0]['line'], 'end');
	}

	public function cLog($message = '') {
		
		$bt = debug_backtrace();
		$path = pathinfo($bt[0]['file']);
		$filename = $path['filename'];
	
		$func = (isset($bt[1]['function'])) ? $bt[1]['function'] . '():' : ''; // テストプログラムで使うことを考慮
		
		$this->doOutputLog(LOGGER_DEBUG, $filename . '::' .$func. $bt[0]['line'], $message, false);
	}
	
	
	/**
	 * デバッグログを出力する
	 * @param mixed $message
	 */
	public function debugLog($message = '')
	{
		$bt = debug_backtrace();
		$path = pathinfo($bt[0]['file']);
		$filename = $path['filename'];
	
		$this->doOutputLog(LOGGER_DEBUG, $filename . '::' . $bt[1]['function'] . '():' . $bt[0]['line'], $message);
	}
	
	/**
	 * インフォログを出力する
	 * @param mixed $message
	 */
	public function infoLog($message)
	{
		$bt = debug_backtrace();
	
		$this->doOutputLog(LOGGER_INFO, $bt[0]['file'] . '::' . $bt[1]['function'] . '():' . $bt[0]['line'], $message);
	}
	
	/**
	 * エラーログを出力する
	 * @param mixed $message
	 */
	public function errorLog($message)
	{
		$bt = debug_backtrace();
	
		$this->doOutputLog(LOGGER_ERR, $bt[0]['file'] . '::' . $bt[1]['function'] . '():' . $bt[0]['line'], $message);
	}
	
	/**
	 * ログを出力する
	 * @param mixed $logType
	 * @param mixed $message
	 */
	public function outputLog($logType, $message, $printBackTrace = false)
	{
		if ($printBackTrace === true) {
			$bt = debug_backtrace();
			$backTrace = $bt[0]['file'] . '::' . $bt[1]['function'] . '():' . $bt[0]['line'];
		}
	
		$this->doOutputLog($logType, $backTrace, $message);
	}
	
	/**
	 * ログを出力する
	 * @param mixed $logType
	 * @param mixed $message
	 */
	private function doOutputLog($logType, $backTrace, $message, $toFile = true)
	{
		$logFilename = $this->getLogFileName($logType);
		$logFilePath = LOGGER_DIR . '/' . $logFilename;
		$ts = date('Ymd H:i:s');
		
		$printData = null; 
		if (is_array($message) || is_object($message)) {
			$printData = '// ' . $ts . ' ' . $backTrace . ' ' . print_r($message, true) . "\n";
		} else {
			$printData = '// ' . $ts . ' ' . $backTrace . ' ' . $message . "\n";
		}
		
		if ($toFile) {
			error_log($printData, 3, $logFilePath);
		} else {
			echo $printData;
		}
	}
	
	/**
	 * ログファイル名の生成
	 * @param unknown $logType
	 */
	private function getLogFileName($logType) {
		$d = date('Ymd');
		$fName = self::INFO_LOG_FILENAME;
		
		if ($logType === LOGGER_DEBUG) {
			$fName = self::DEBUG_LOG_FILENAME;
		} else if ($logType === LOGGER_ERR) {
			$fName = self::ERR_LOG_FILENAME;
		} else if ($logType === LOGGER_INFO) {
			$fName = self::INFO_LOG_FILENAME;
		}
		// ファイル名生成
		$logFilename = $fName . $d . self::LOG_SUFFIX;
		return $logFilename;
	}
	
}