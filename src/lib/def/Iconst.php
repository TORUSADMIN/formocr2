<?php
mb_internal_encoding("UTF-8");

interface Iconst {
	// Default Main Class
	const default_Main_Class = "mainLogic/DealMainLogic";
	// logger Properties
	const loggerProperties="/../etc/log4php.properties";
	// logger name
	const loggerName="batchBase";

	// config file name
	const defaultCondfigFile="../etc/config.ini";

	// csv filename suffix
	const csvOutFileSuffix = ".csv";

	// 余白 外字コード
	const extCharSpace = "<ee8182><ee8183><ee8184>";

	// List 作成Mode
	const FROM_DB = "sql";
	const FROM_FILE = "file";

	// 設定データのセパレータ
	const DATA_SEP = ",";

	// リストデータ配列のkey
	const LIST_AID = "aid";
	const LIST_AREAID = "areaid";
	const LIST_REGISTID = "registid";

	// データ取得SQL
	const ADATA_SQL = "select * from acadata where acaid = ?";
	const AREA_M_SQL = "select * from areamaster where areaid = ?";
	const REGIST_OWNER_SQL = "select * from rightowner where registid = ? order by innernumber";
	const REGIST_CREDIT_SQL = "select * from rightother where registid = ? order by innernumber";
	const REGIST_LOT_INFO_SQL = "select * from lotinfo where registid = ? order by lotid";
	const REGIST_BUILD_ST_SQL = "select * from buildingstructure where registid = ? and registsubid is null order by buildingstructureid";
	const REGIST_LOT_DISP_SQL = "select * from lotdisplay where registid = ? order by displaynumber";
	const REGIST_BUILD_ST_ALL_SQL = "select * from buildingstructure b inner join registsubdata s on b.registid = s.registid and s.titlename like '%一棟の建物の表示%' where b.registid = ? and b.registsubid = s.registsubid order by b.buildingstructureid";
	const EXT_CHAR_SQL = "select * from ownerdataextchar where ext_char_code = ?";
	const LOCATION_MAS_SQL = "select prefectureid, lotlocationname from locationmaster where lotlocationname like ?";
	const PREF_MASTER_SQL = "select prefectureid, prefecturename from prefecturemaster where prefectureid = ?";
	const REGIST_SUB_GET_BUILD_NAME_SQL = "select buildingname from registsubdata where registid = ? and titlename = '（一棟の建物の表示）' and buildingname <> ''";
	const REGIST_JOIN_MO_LIST_SQL = "select * from jointmortgagelist where registid = ? order by mortgagelistid";
	const REGIST_JOIN_MO_LIST_INFO_SQL = "select * from jointmortgagelistinfo where mortgagelistid = ? order by innernumber";
	const REGIST_M_DATA_SQL = "select * from registdata where registid = ?";
	const REGIST_SUB_DATA_SQL = "select * from registsubdata where registid = ? order by registsubid";

	// Debug用
	const DEBUG_ORDERDETAIL_SQL = "select mod_date from orderdetail where registid = ? and ordertype = '1' and payment <> 210";

	// データ配列キー
	const REGIST_ID_DATA = "rid";
	const OUT_AID_DATA_KEY = "adata";
	const OUT_AREA_DATA_KEY = "area";
	const OUT_LOT_DATA_KEY = "lot";
	const OUT_BUILD_DATA_KEY = "build";
	const OUT_LOT_DISP_DATA_KEY = "lotdisp";
	const OUT_BUILD_ALL_DATA_KEY = "buildall";
	const OUT_ANALYZE_DATA_KEY = "analyze";
	// 20131031追加
	const REGIST_DATA_KEY = "registdata";
	const REGIST_SUB_DATA_KEY = "registsubdata";
	const KOU_DATA_KEY = "koudata";
	const OTSU_DATA_KEY = "otsudata";
	const JOINT_MO_LIST_DATA_KEY = "jointmortgagelist";
	const DB_OBJECT_KEY = "dbObject";

	/** 解析時の固定定数 **/
	// 戻り値のhash key
	const DATA_ARRAY = "data";
	const ANALYZE_WARNING_MSG = "warnigMsg";
	// 余白のコード
	const SPACE_DATA_CODE = "<ee8182><ee8183><ee8184>";
	const SPACE_DATA_STR = "余白";
	// 詳細のセパレータ
	const DETAIL_SEP = "\n";
	// RegistInfoのセパレータ
	const REG_INFO_DATA_SEP = "\n";
	// type, 日時を切り出す指標の文字列
	const TYPE_DATA_CHECK_POS = "原因";
	// 空白の正規化表現文字列
	const SPACE_REG_CODE = "[ |　]";
	// 日付の正規化表現
	const REG_DATE_PATTERN = "年.*?月.*?日";
	// 種別解析のデータ取得数
	const TYPE_DATE_REG_CHECK_COUNT = 3;
	const TYPE_REG_CHECK_COUNT = 2;
	/*** 下位クラスでの定数(解析種別) ***/
	const TYPE_REG_CHECK_NO_DATE = "noDate";
	const TYPE_REG_CHECK_IN_DATE = "inDate";
	const TYPE_REG_DATA_SPLIT = "/([昭和|平成|同]+.*?年.*?月.*?日)/u";
	const TYPE_REG_SPLIT_CHECK = 2;
	const TYPE_DATE_SAME_YEAR = "同年";
	const TYPE_DATE_SAME_MONTH = "同月";
	// 解析除外データ判定
	const NO_ANALYZE_REG_DATA = "代位";
	// 共有者/所有者/債権者 定数定義
	const PERSON_NOT_SET = 0;
	const PERSON_PART_OWNERS = 1;
	const PERSON_OWNER = 2;
	const PERSON_CREDITOR = 3;
	// 文字列
	const PERSON_PART_OWNERS_NAME = "共有者";
	const PERSON_OWNER_NAME = "所有者";
	const PERSON_CREDITOR_NAME = "債権者";
	const PERSON_PART_OWNERS_SP = "　　　";
	const PERSON_OWNER_SP = "　　　";
	const PERSON_CREDITOR_SP = "　　　";

	// 共有者/所有者/債権者 判定設定
	const PART_OWNERS_REG = "/^共有者/";
	const OWNER_REG = "/^所有者/";
	const CREDITOR_REG = "/^債権者/";

	/** 処理対象データ **/
	const PICK_UP_DATA = "/売買/";

	// 付記対応
	const CHANGE_DATA_REG = "/^付記/u";
	const ORG_CHANGE_DATA_REG = "/付記/u";

	// 固定長
	const DEF_OWNER_LEN = 4;
	const DEF_CREDITOR_LEN = 4;
	const DEF_PART_OWNERS_LEN = 0;
	// 共有者データの１データの基本Line数
	const DEF_PART_OWNERS_DATA_SPLIT_LINE = 3;
	// 最初の持分想定Line
	const DEF_PART_INIT_LINE = 1;
	// 所有者データの１データの基本Line数（持分なし）
	const DEF_OWNER_WITHOUT_PARTS = 2;
	// 所有者データの１データの基本Line数（持分あり）
	const DEF_OWNER_WITH_PARTS = 3;

	// 定数（付記対応）
	const CHG_MODE_NOSET = 0;
	const CHG_MODE_ADDR = 1;
	const CHG_MODE_NAME = 2;
	const CHG_MODE_NAME_ADDR = 3;
	const CHG_MODE_ADDR_NO_NAME = 4;
	const CHG_MODE_NAME_NO_NAME = 5;
	const CHG_MODE_NAME_ADDR_NO_NAME = 6;
	const CHG_MODE_SAKUGO = 7;

	// データの解析場所
	const DATA_POS_ADDR = 0;
	const DATA_POS_PART = 1;
	const DATA_POS_NAME = 2;

	const WARN_DATA_MAN_CHECK = "manualChecked";


	// line skip
	const LINE_SKIP_DATA = "/^原因/u";
	// Owner check
	const CHECK_OWNERS_REG = "/^[共|所]有者/u";
	const CHECK_OWNERS_ONLY_REG = "/^[共|所]有者$/u";
	const CHECK_NAME_AND_ADDR_REG = "/の氏名住所/u";
	const CHECK_NAME_AND_ADDR_STR = "の氏名住所";
	const CHECK_NAME_EXT_REG = "/の氏名/u";
	const CHECK_NAME_EXT_STR = "の氏名";
	const CHECK_ADDR_EXT_REG = "/の住所/u";
	const CHECK_ADDR_EXT_STR = "の住所";
	const CHECK_ADDR_NO_NAME_STR = "住所";
	const CHECK_NAME_NO_NAME_STR = "氏名";
	const CHECK_NAME_ADDR_NO_NAME_REG = "/^住所氏名/u";
	const CHECK_NAME_ADDR_NO_NAME_STR = "住所氏名";
	const CHECK_NAME_ADDR_NO_NAME_STR_SP = "　　　　";
	// 錯誤の対応
	const CHECK_SAKUGO_REG = "/錯誤/u";
	// 持ち分の計算チェック
	const CHECK_PART_CAL = "/\//u";
	// 抹消登記チェック
	const CHECK_DELETE_PURPOSE_REG = "/抹消$/u";
	// 付記対応
	const EXT_CHANGE_STR = "住所移転";
	// 抹消登記複数チェック
	const CHECK_DEL_NUMBERS_REG = "/、/u";
	const CHECK_DEL_NUMBERS_SEP = "、";
	// 所有権更生チェック
	const CHECK_REGE_OWNER_REG = "/所有権更正/u";


	// 持分判定文字列
	const CHECK_FST_PART_REG = "/^[持分|持ち分]/u";
	const CHECK_PART_REG = "/分の/u";
	const DELETE_PART_REG = "/(持分|持ち分)/u";
	// 氏名等判定文字列
	const CHECK_NAME_REG = "/^[　| ]{1}(<[a-z0-9A-Z]+>|[^　 ]){1}([ |　](<[a-z0-9A-Z]+>|[^　 ])){1,}$/u";
	const CHECK_COMP_REG = "/^[ |　].*?(会社|法人)/u";

	// 解析データ詳細のKey
	const CAUSE_DATA = "cause";
	const CAUSEDATE_DATA = "causeDate";
	const PERSON_TYPE = "type"; // まず1対1のデータとする(１行のデータに所有者・債権者等が複数の場合1対N側にする)
	const PERSON_DATA = "data";
	// 1対N
	const ADDRESS = "address";
	const NAME = "name";
	const PART = "part";
	// 付記専用のデータ
	const MERGE_DATA_MODE = "onParseLine";
	const CHANGE_NAME = "chgName";
	const SAKUGO_OWNER = "sOwner";
	const DELETE_NAME = "delName";

	/** 検索 条件 **/
	const ALL_PURPOSE = "/所有権移転/u";
	const PART_ALL_PURPOSE = "/共有者.*全部移転/u";
	const PART_PURPOSE = "/持分全部移転/u";
	const PART_ONE_PURPOSE = "/一部移転/u";
	const ALL_MODE = "all"; // 所有権移転
	const PART_ALL_MODE = "partAll"; // 共有者持分全部
	const PART_MODE = "part"; // [氏名]持分全部
	// 氏名抽出
	const EXT_NAME_REG = "/^(.*?)持分全部移転/u";
	const EXT_NAME_NOT_REG = "/^(.*?)を除く共有者全員持分全部移転/u";
	const EXT_NAME_SEP = "、";
	// 氏名、持分抽出
	const EXT_NAME_PART = "/(.*?)持分(.*)/u";
	// 持ち分を謄本の形式に戻す
	const ESTATE_PART_FORMAT_REG = "/(.*)\/(.*)/u";
	const ESTATE_PART_FORMAT = "%s分の%s";

	/** 検索 条件 **/
	const MERGE_ALL_PURPOSE = "/所有権移転/u";
	const MERGE_PART_ALL_PURPOSE = "/^共有者.*全部移転/u";
	const MERGE_PART_NOTALL_PURPOSE = "/を除く共有者全員持分全部移転/u";
	const MERGE_PART_PURPOSE = "/持分全部移転/u";
	const MERGE_PART_PURPOSE_PART = "/持分.+移転/u";
	const MERGE_ALL_PART_PURPOSE = "/共有者全員持分全部移転/u";
	const MERGE_PART_OWNER_PURPOSE = "/所有権一部移転/u"; // 20130916 追加
	const MERGE_ALL_MODE = "all"; // 所有権移転
	const MERGE_PART_ALL_MODE = "partAll"; // 共有者持分全部
	const MERGE_PART_NOTALL_MODE = "partNotAll"; // 共有者持分全部
	const MERGE_PART_MODE = "part"; // [氏名]持分全部

	// 解析pending
	const ANALYZE_PENDING = "pending";

	/** 削除文字列 **/
	const DEL_PART_PURPOSE_PART = "/移転/u";
	const DEL_PRICE_MARK = ",";
	const CUT_DEL_PURPOSE_STR_REG = "/(^.*番)[^番]*?抹消$/u";

	/** 抽出用正規化表現 **/
	const PUT_REMARK_NUMBER_REG = "/^(.*[０１２３４５６７８９]番).*?$/u";


	/** 解析データ の key **/
	const FROM_ANALYZE_DATA = "from";
	const TO_ANALYZE_DATA = "to";
	// 開始日
	const TO_START_DATE = "startDate";
	const TO_REGIST_DATE = "registDate";

	/*****************  フォーマッタ  *****************/
	const AREA_DOT_CHG = "/：/u";
	const AREA_DOT_STR = "：";
	const FORMAT_DATA_SEP = "/";
	const OUT_CSV_SEP = ",";
	// データDelフラグ
	const DETELE_ON = "1";
	// 区分
	const LOT_TYPE = "土地";
	// 外字コード正規化
	const EXT_CHAR_REG = "/<[a-z0-9A-Z]+?>/u";
	// 階全体のセパレータ
	const FLOOER_ALL_SEP = "階";
	// 階部分のセパレータ
	const FLOOER_ONLY_SEP = "階部分";
	// 表題日の選択文字列
	const BUILD_DATE_SEL_STR = "新築";

	// 日付の余分なデータの設定
	const DELETE_DATE_STR_1 = "推定";
	const REPLACE_DATE_STR_F1 = "月日不詳"; // -> 月１日
	const REPLACE_DATE_STR_T1 = "月１日";

	/********************** 債権解析 固有の設定 ************************/
	const CREDIT_REG_NETEITOU = "/根抵当/u";
	const CREDIT_REG_TEITOU = "/抵当/u";
	const CREDIT_PRE_REGIST = "/仮登記/u";
	const CREDIT_DELETE_REG = "/抹消$/u";
	// 数字判定
	const NUMERIC_REG = "/[0-9０１２３４５６７８９]+/u";

	// *****データ種別判定*****
	const CREDIT_OBLIGOR_REG = "/^(債務者|連帯債務者)/u";
	const CREDIT_PRICE_REG = "/^債権額/u";
	const CREDIT_INTEREST_REG = "/^利息/u";
	const CREDIT_INTEREST_S_REG = "/^利率/u";
	const CREDIT_DELAY_INTEREST_REG = "/^損害金/u";
	const CREDIT_LENDER_REG = "/^(抵当権者|権利者)/u";
	const CREDIT_MORTGATE_REG = "/^共同担保/u";
	const CREDIT_DELAY_PRICE_REG = "/^延滞税の額/u";

	const CREDIT_OBLIGOR_STR = "債務者";
	const CREDIT_OBLIGORS_STR = "連帯債務者";
	const CREDIT_PRICE_STR = "債権額";
	const CREDIT_INTEREST_STR = "利息";
	const CREDIT_INTEREST_S_STR = "利率";
	const CREDIT_DELAY_INTEREST_STR = "損害金";
	const CREDIT_LENDER_STR = "抵当権者";
	const CREDIT_LENDER_STR1 = "権利者";
	const CREDIT_MORTGATE_STR = "共同担保";
	const CREDIT_DELAY_PRICE_STR = "延滞税の額";
	// *****付記データ種別判定*****
	const CREDIT_CHG_LENDER_REG = "/^商号/u";
	const CREDIT_CHG_OBLIGOR_REG = "/相続/u";
	const CREDIT_CHG_JOIN_OBLIGOR_REG = "/債務引受/u";

	const CREDIT_CHG_LENDER_STR = "商号";
	const CREDIT_CHG_OBLIGOR_STR = "相続";
	const CREDIT_CHG_JOIN_OBLIGOR_STR = "債務引受";


	// **** 変更パターン ****/
	const CREDIT_CHANGE_KEY = "changeMode";
	const CREDIT_REPLACE = "replace";
	const CREDIT_MOVE = "move";
	const CREDIT_OB_MOVE = "obMove";
	const CREDIT_OB_ALL_REPLACE = "obAllReplace";
	const CREDIT_OB_CAHNGE_NAME = "changeObName";
	const CREDIT_OB_CAHNGE_ADDRESS = "changeObAddress";
	const CREDIT_LE_CAHNGE_NAME = "changeLeName";
	const CREDIT_LE_CAHNGE_ADDRESS = "changeLeAddress";
	const CREDIT_LE_CAHNGE_ALL = "changeLeAll";
	const CREDIT_CAHNGE_NAME = "changeName";
	const CREDIT_CAHNGE_ADDRESS = "changeAddress";

	// 利息の分割(１つ目の%で分割)
	const CREDIT_INTEREST_PARSE_REG = "/^(.*?％)(.*)$/um";

	// *****固定データ削除分の長さ*****
	const CREDIT_OBLIGOR_LENGTH = 4;

	// 削除フラグ
	const DELETE_FLAG = "isDelete";
	const DELETE_FLAG_ON = true;
	const DELETE_FLAG_OFF = false;

	/*****データ種別判定(根抵当)*****/
	const NE_CREDIT_OBLIGOR_REG = "/^(債務者|連帯債務者)/u";
	const NE_CREDIT_PRICE_REG = "/^極度額/u";
	const NE_CREDIT_PART_REG = "/^債権の範囲/u";
	const NE_CREDIT_LENDER_REG = "/^(根抵当権者|権利者)/u";
	const NE_CREDIT_MORTGATE_REG = "/^共同担保/u";

	const NE_CREDIT_OBLIGOR_STR = "債務者";
	const NE_CREDIT_OBLIGORS_STR = "連帯債務者";
	const NE_CREDIT_PRICE_STR = "極度額";
	const NE_CREDIT_PART_STR = "債権の範囲";
	const NE_CREDIT_LENDER_STR = "根抵当権者";
	const NE_CREDIT_MORTGATE_STR = "共同担保";

	// 解析データの保存キー
	// 受付日
	const CREDIT_REG_INFO = "regInfo";
	// 原因の日付
	const CREDIT_CAUSE_DATE = "causeDate";
	// 原因
	const CREDIT_CAUSE_INFO = "causeInfo";
	// 債権額
	const CREDIT_PRICE = "price";
	// 債権額
	//const CREDIT_PRICE_INFO = "priceInfo";
	// 利息
	const CREDIT_INTEREST = "interest";
	// 利息
	const CREDIT_INTEREST_MEMO = "interestMemo";
	// 延滞利息
	const CREDIT_DELAY_INTEREST = "delayInterest";
	// 債務者（連帯債務者も含む）
	const CREDIT_OBLIGOR = "obligor";
	// 連帯債務者か
	const CREDIT_IS_OBLIGORS = "isObligors";
	// 抵当権者
	const CREDIT_LENDER = "mortgageLender";
	// 共同担保目録番号
	const CREDIT_MORTGATE_NUMBER = "mortgageNumber";
	// 延滞税の額
	const CREDIT_DELAY_PRICE = "taxDelayPrice";

	const NE_CREDIT_OBLIGOR = "obligor";
	// 連帯債務者か
	const NE_CREDIT_IS_OBLIGORS = "isObligors";
	const NE_CREDIT_PRICE = "price";
	const NE_CREDIT_PART = "neCreditPart";
	const NE_CREDIT_LENDER = "mortgageLender";
	const NE_CREDIT_MORTGATE_NUMBER = "mortgageNumber";

	// データ種別
	const CREDIT_KIND_KEY = "c-type";
	const CREDIT_DATA = "credit";
	const NE_CREDIT_DATA = "neCredit";
	const PRE_CREDIT_DATA = "preCredit";
	const PRE_NE_CREDIT_DATA = "preNeCredit";


	/********************** 所有者解析 固有の設定 ************************/
	// 所有者の解析対象データ定義(登記の目的)
	const OWNER_DATA_REG = "/[所有権|移転|保存]/u";
	const OWNER_DISP_REG = "/付記/u";
	// 持分計算用
	const OWNER_PART_DEL_REG = "/持分/u";

	// ミステイクした場合の文字列
	const OWNER_MISS_STRINGS = "checking";

	/********************** Sony 生命用固有設定 **************************/
	// 出力制御(「相続税」「利子税」が入っている登記を出力)
	const SONY_OUTPUT_COND = "/(相続税|利子税)/u";
	// 金額を分割チェック
	const SONY_PRICE_CUT_CHECK_STR = "/内訳/u";
	// 金額を分割チェック
	const SONY_PRICE_CUT_STR = "/(^.*?)(内訳.*$)/u";

}