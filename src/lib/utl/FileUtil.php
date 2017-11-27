<?php

class FileUtil {
    // コンストラクタ instance化は許可しない
    private function __construct() {
	}

    //デストラクタ
    public function __destruct() {
	}

	// ファイルパス作成
	public static function createFilePath($baseDir, $innerDir, $timeStr, $fileName) {
		if ($baseDir === "" || $fileName === "") {
			return "";
		}
		$filePath = $baseDir;
		if ($innerDir !== "") {
			$filePath = $filePath . DIRECTORY_SEPARATOR . $innerDir;
		}
		if (isset($timeStr) && $timeStr !== "") {
			// 時間指定あり
			$filePath =
				$filePath . DIRECTORY_SEPARATOR . $timeStr . "_" . $fileName;
		} else {
			$filePath =
				$filePath . DIRECTORY_SEPARATOR . $fileName;
		}
		return $filePath;
	}

	// Directory Path 作成
	public static function createDirPath($baseDir, $innerDir) {
		if ($baseDir === "" || $innerDir === "") {
			return "";
		}
		return $baseDir . DIRECTORY_SEPARATOR . $innerDir;
	}

	// デバックファイルパス作成
	public static function createDebugFilePath($baseDir, $innerDir,
														$timeStr, $fileName) {
		if ($baseDir === "" || $fileName === "") {
			return "";
		}
		$filePath = $baseDir;
		if ($innerDir !== "") {
			$filePath = $filePath . DIRECTORY_SEPARATOR . $innerDir;
		}
		if (isset($timeStr) && $timeStr !== "") {
			// 時間指定あり
			$filePath =
				$filePath . DIRECTORY_SEPARATOR . $timeStr . "_" . $fileName . "Debug";
		} else {
			$filePath =
				$filePath . DIRECTORY_SEPARATOR . $fileName . "Debug";
		}
		return $filePath;
	}

	// ファイル書き込み
	public static function writeFile($fileName, $data) {
		$fp = fopen($fileName, "w");
		if ($fp ==="FALSE") {
			return $fp;
		}
		$len = fwrite($fp, $data, strlen($data));
		fclose($fp);
		if ($len === "FALSE") {
			return $len;
		}
		return TRUE;
	}

	// var_dumpファイル書き込み
	public static function varDumpWriteFile($filename, $data) {
		ob_start();
		var_dump($data);
		$out=ob_get_contents();
		ob_end_clean();
		file_put_contents($filename,$out,FILE_APPEND);
	}

	// ファイル読み込み(一括読み込み) Stringモード
	public static function readFile($fileName) {
		$ret = file_get_contents($fileName);
		if ($ret ==="FALSE") {
			return -1;
		}
		return $ret;
	}

	// ファイル追加書き込み
	public static function writeAppendFile($fileName, $data) {
		$fp = fopen($fileName, "ab");
		if ($fp ==="FALSE") {
			return $fp;
		}
		$len = fwrite($fp, $data, strlen($data));
		fclose($fp);
		if ($len === "FALSE") {
			return $len;
		}
		return TRUE;
	}

}
?>
