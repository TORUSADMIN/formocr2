<?php

/** 定数定義 */
// Logger
define('LOGGER_DEBUG', 'LOGGER_DEBUG');
define('LOGGER_INFO', 'LOGGER_INFO');
define('LOGGER_ERR', 'LOGGER_ERR');

// 有効/無効
define('VALID', true);
define('INVALID', true);
define('VALID_MARK', '○');
define('INVALID_MARK', '☓');

// 半角、全角、変換無し
define('CNV_HAN', 1); // 半角
define('CNV_ZEN', 2); // 全角
define('CNV_KAN', 3); // 漢字
define('CNV_NOT', 99);

// 住所ステータス
define('ST_ADDR_EMPTY', 1); // 空
define('ST_ADDR_YOHAKU', 2); // '余白'
define('ST_ADDR_JAPAN', 3); // 日本の住所
define('ST_ADDR_FOREIGN', 4); // 海外の住所
define('ST_ADDR_INCLUDE_ERROR', 98); // 変換失敗文字「？」などが含まれれている。
define('ST_ADDR_UNKNOWN', 99); // 怪しい

// 判定不能か手作業を必要とするものに対するプリフィックス
define('PREFIX_UNKNWON', '__XXX_');

// キー用デリミタ
define('DATA_DELIM', '_#_');


