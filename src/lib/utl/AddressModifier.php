<?php
/**
 * 住所・地番の変換を行う
 */
require_once(UTIL_DIR . '/trait/EmptyTrait.php');
require_once(UTIL_DIR . '/Logger.php');
require_once(UTIL_DIR . '/AddressConfig.php');

/**
 * 住所変換ユーティリティ
 * 未対応パターン
 * 　新潟市西区五十嵐1の町6398-18
 * 　福岡で町の後に区がくるパターン
 * @todo 「春日部市八丁目」などの普通の「丁目」ではなく所在の場合は変換してはダメなので、例外処理を入れる
 * @author moroishi
 *
 */
class AddressModifier {

	use EmptyTrait;

	private $logger;

	/* JIS都道府県CD => 都道府県名 */
	private $prefCdKeys;
	/* 都道府県名 => JIS都道府県CD */
	private $prefNameKeys;
	/* エンコード */
	private $modEncode;

	/* 七丁目や十四丁目など。四ツ谷や八丁堀などはひっかからない　*/
	const KANSUJI_CHOME_PATTERN = '/^.*[一二三四五六七八九十壱弐参拾百千万萬億兆〇]+丁目.*$/u';
	// const EXTRACT_KANSUJI_CHOME_PATTERN = '/(.*)([一二三四五六七八九十壱弐参拾百千万萬億兆〇]+丁目)(.*)/u';

	const EXTRACT_ALL_CHOME_PATTERN = '/(.*[^一二三四五六七八九十壱弐参拾百千万萬億兆〇0-9０-９])([一二三四五六七八九十壱弐参拾百千万萬億兆〇0-9０-９]+丁目)(.*)/u';
	const EXTRACT_ALL_CHO_PATTERN = '/(.*堺市.*[^一二三四五六七八九十壱弐参拾百千万萬億兆〇0-9０-９])([一二三四五六七八九十壱弐参拾百千万萬億兆〇0-9０-９]+丁)([^目]*)/u';
	const EXTRACT_FIRST_NUMBER_PATTERN = '/^([^0-9０-９]*)([0-9０-９]+.*)$/u';

	/* 丁目を含む文字列か検索する */
	const CHOME_PATTERN = '/^.+丁目.*$/u';
	const CHO_PATTERN = '/^.*堺市.+[一二三四五六七八九十壱弐参拾百千万萬億兆〇0-9０-９]+丁[^目]*$/u';

	const KANSUJI_PATTERN = '/[一二三四五六七八九十壱弐参拾百千万萬億兆〇]/u'; // 漢数字のみ
	const KANSUJI_DIG = '/[拾百千万萬億兆]/u'; // 漢数字で桁を表す文字


	/* 丁目以下切り捨て用 */
	const REMOVE_HANKAKU_CHOME_PATTERN = '/([^0-9]*)([0-9\-]+)/u';

	/* 字以下抽出 */
	const SPLIT_AZA_PATTERN = '/^(.+)(字.+)$/u';

	/* 番地を含む文字列 */
	const BANCHI_PATTERN = '/^.+(?:番地|番地の)[^(（]+/u';
	// const BANCHI_PATTERN2 = '/^.+番地（.+/u';

	/* 番を含む文字列 */
	const BAN_PATTERN = '/^.+番.+/u';

	/* 最後が号 */
	const GOU_LAST_PATTERN = '/^.+号$/u';

	/* ()が入るか */
	const KAKKO_PATTERN = '/^.+[\(（].+[\)）].*$/u';

	const AFTER_MNAME_BUNKATSU_PATTERN = '/^([一二三四五六七八九十壱弐参拾百千万萬億兆〇0-9０-９\-\ｰ\-\−\―\‐\－]+)([^一二三四五六七八九十壱弐参拾百千万萬億兆〇0-9０-９\-\ｰ\-\−\―\‐\－]+.*)$/u';
	/* すべてのハイフンを抽出 */
	// const ALL_HAIHUN_PATTERN = '/[\x{30FC}\x{2010}-\x{2015}\x{2212}\x{FF70}－-]/u';
	// const ALL_HAIHUN = '[\x{30FC}\x{2010}-\x{2015}\x{2212}\x{FF70}\－-]';

	const ALL_HAIHUN_PATTERN = '/[\-\ｰ\-\−\ー\―\‐\－]/u';
	const ALL_HAIHUN = '[\-\ｰ\-\−\ー\―\‐\－]';
	const ALL_HAIHUN_WITHOUT_CHOON = '[\-\ｰ\-\−\―\‐\－]';



	/* クエスチョンチェック */
	const QUESTION_PATTERN = '/[?？]/u';

	/* アルファベットチェック */
	const ALPHABET_PATTERN = '/[a-zA-Zａ-ｚＡ-Ｚ]/u';


	/* 海外判別で利用する文字数 */
	const FOREIGN_EVAL_LENGTH = 8;

	/* 変換用 */
	const STR_CHOME = '丁目';
	const STR_ZEN_HAIHUN = '－';
	const STR_ZEN_HAIHUN2 = 'ｰ';
	const STR_ZEN_CHOON = 'ー';
	const STR_BANCHI = '番地';
	const STR_BAN = '番';

	/* カナのハイフン変換用 */
	// const REPLACE_HAN_HAIHUN = '-';
	const REPLACE_HAN_HAIHUN = '-';
	// const REPLACE_ZEN_HAIHUN = '−';
	const REPLACE_ZEN_HAIHUN = '－'; // 解析結果から出てくる全角「－」へ変更

	const ENCODE = 'utf-8';

	/* 住所分解配列インデックス */
	const IDX_ADDRESS_STATUS = '_addressStatus';
	const IDX_CHANGED_ADDRESS = '_changedAddress';
	const IDX_PARTS = '_parts';

	const IDX_PARTS_PREF = '_pref';
	const IDX_PARTS_CITY = '_cityAndTown';
	const IDX_PARTS_CHOME = '_chome';
	const IDX_PARTS_AFTER = '_after';
	const IDX_PARTS_MNAME = '_mName';
	const IDX_PARTS_ROOM_NO = '_roomNo';

	// 政令指定都市用
	const IDX_PARTS_GOV_PREF = '_govPref'; // 政令指定都市県
	const IDX_PARTS_GOV_CITY = '_govCity'; // 政令指定都市
	const IDX_PARTS_GOV_TOWN = '_govTown'; // 町丁名


	const TYPE_NUM = 1;
	const TYPE_KANA = 2;
	const TYPE_ALPHA = 3;

	const ERROR_MARKAR = '★';
	const ERROR_PATTERN = '/[\?<>]/u';

	private $govDesignatedCity;

	private $kansujiPattern = [
		'一' => '１','壱' => '１'
		,'ニ' => '２', '弐' => '２'
		,'三' => '３', '参' => '３'
		,'四' => '４','五' => '５','六' => '６','七' => '７', '八' => '８','九' => '９'
		,'〇' => '０','零' => '０'
	];

	private $kansujiTable = [
			'三〇' => '３０',	'三十' => '３０', '参〇' => '３０', '参拾' => '３０',
			'二九' => '２９',	'二十九' => '２９', '弐九' => '２９',
			'二八' => '２８',	'二十八' => '２８', '弐八' => '２８',
			'二七' => '２７',	'二十七' => '２７', '弐七' => '２７',
			'二六' => '２６',	'二十六' => '２６', '弐六' => '２６',
			'二五' => '２５',	'二十五' => '２５', '弐五' => '２５',
			'二四' => '２４',	'二十四' => '２４', '弐四' => '２４',
			'二三' => '２３',	'二十三' => '２３', '弐三' => '２３','弐参' => '２３',
			'二二' => '２２',	'二十二' => '２２', '弐ニ' => '２２','弐弐' => '２２',
			'二一' => '２１',	'二十一' => '２１', '弐一' => '２１','弐壱' => '２１',
			'二〇' => '２０',	'二十' => '２０', '弐〇' => '２０', '弐拾' => '２０',
			'一九' => '１９', '十九' => '１９', '壱九' => '１９',
			'一八' => '１８', '十八' => '１８', '壱八' => '１８',
			'一七' => '１７', '十七' => '１７', '壱七' => '１７',
			'一六' => '１６', '十六' => '１６', '壱六' => '１６',
			'一五' => '１５', '十五' => '１５', '壱五' => '１５',
			'一四' => '１４', '十四' => '１４', '壱四' => '１４',
			'一三' => '１３', '十三' => '１３', '壱三' => '１３', '壱参' => '１３',
			'一二' => '１２', '十二' => '１２', '壱二' => '１２', '壱弐' => '１２',
			'一一' => '１１', '十一' => '１１', '壱一' => '１１', '壱壱' => '１１',
			'一〇' => '１０', '十' => '１０', '壱〇' => '１０',
			'九' => '９',
			'八' => '８',
			'七' => '７',
			'六' => '６',
			'五' => '５',
			'四' => '４',
			'三' => '３', '参' => '３',
			'二' => '２', '弐' => '２',
			'一' => '１', '壱' => '１',
	];

	/**
	 * コンストラクタ
	 * 都道府県データの構築
	 * 文字コードの設定
	 */
	public function __construct() {
		// 都道府県の構築
		$this->buildPrefecture();
		$this->modEncode = 'utf-8';
		$this->logger = new Logger();

		// 政令指定都市データの作成
		$this->govDesignatedCity = $this->createGovOrdinanceDesignatedCity();
	}


	/////////////////////////////////////////////////
	// アドレスデータ構造作成
	/////////////////////////////////////////////////

	/**
	 * アドレス構造の作成
	 * @param unknown $isForeign
	 * @param unknown $fullAddress
	 * @param unknown $parts
	 */
	public function buildAddressStruct($addressStatus, $changedAddress, $parts) {
		return array(
			self::IDX_ADDRESS_STATUS => $addressStatus,
				self::IDX_CHANGED_ADDRESS => $changedAddress,
				self::IDX_PARTS => $parts
		);
	}

	/**
	 * パーツデータの作成
	 * @param unknown $pref
	 * @param unknown $city
	 * @param unknown $chome
	 * @param unknown $after
	 * @return unknown[]
	 */
	public function buildPartsStruct($pref, $city, $chome, $after, $mName = '',$roomNo = '' ) {
		return array(
				self::IDX_PARTS_PREF => $pref,
				self::IDX_PARTS_CITY => $city,
				self::IDX_PARTS_CHOME => $chome,
				self::IDX_PARTS_AFTER => $after,
				self::IDX_PARTS_MNAME => $mName,
				self::IDX_PARTS_ROOM_NO => $roomNo,
		);
	}


	/////////////////////////////////////////////////
	// 住所変換メイン（基本的にはこのメソッドだけで事足りるように
	/////////////////////////////////////////////////

	/**
	 * AddressConfigで指定された型式に住所（地番）を変更する
	 * @param unknown $address
	 * @param AddressConfig $addressConfig
	 * @return unknown|NULL
	 */
	public function changeAddress($inAddress, AddressConfig $addressConfig = null) {

		$address = trim($inAddress);

		if ($this->isEmpty($addressConfig)) {
			// throw new Exception('AddressConfig is Empty');
			$nAddressConfig = new AddressConfig();
			$addressConfig = $nAddressConfig->createConfigForNotConvert();
		}

		//////////////////////////////////////////////////////
		// 住所が空
		if ($this->isEmpty($address)) {
			$parts = $this->buildPartsStruct('', '', '', '');
			return $this->buildAddressStruct(ST_ADDR_EMPTY, $address, $parts);
		}

		//////////////////////////////////////////////////////
		// 住所が余白
		if ($this->isYohaku($address)) {
			$parts = $this->buildPartsStruct('', $address, '', '');
			return $this->buildAddressStruct(ST_ADDR_YOHAKU, $address, $parts);
		}

		//////////////////////////////////////////////////////
		// 住所に外字や？が含まれているか？
		// 含まれていれば、★を先頭に付けて返す
		if ($this->isIncludeErrorChar($address)) {
			$parts = false;
			return $this->buildAddressStruct(ST_ADDR_INCLUDE_ERROR, self::ERROR_MARKAR.$address, $parts);
		}


		//////////////////////////////////////////////////////
		// 解析ミスがあるパターン
		// 含まれていれば、★を先頭に付けて返す
		if ($this->isIncludeErrorPattern($address)) {
			$parts = false;
			return $this->buildAddressStruct(ST_ADDR_INCLUDE_ERROR, self::ERROR_MARKAR.$address, $parts);
		}


		//////////////////////////////////////////////////////
		// 海外の住所か否か
		$isForeign = $this->isForeignCountry($address);

		// 海外の住所だった場合は、数字の全角半角、カナの全角半角を変更して終わり
		if ($isForeign) {
			$parts = $this->changeForeignAddress($address, $addressConfig);
			return $this->buildAddressStruct(ST_ADDR_FOREIGN, $parts[self::IDX_PARTS_CITY], $parts);
		}

		//////////////////////////////////////////////////////
		// 判定不能の住所か？（手作業の必要な住所）
		$isChome = $this->isIncludeChome($address);
		// $this->logger->cLog("CHOME = " . $address .":". (($isChome) ? 'true' : 'false'));
		if ($this->isManually($address) && !$isChome) {
			$parts = $this->buildPartsStruct('', PREFIX_UNKNWON . $address, '', '');
			return $this->buildAddressStruct(ST_ADDR_UNKNOWN, $parts[self::IDX_PARTS_CITY], $parts);
		}

		//////////////////////////////////////////////////////
		// 国内住所
		// 住所の分解
		$disassembledAddress = $this->disassembleAddress($address);
		// $this->logger->cLog($disassembledAddress);

		// $this->logger->cLog(print_r($disassembledAddress, true));

		//////////////////////////////////////////////////////
		// 国内住所:変換対象

		// 分解した住所を変更する
		$modifiedDisassembledAddress = $this->modifyDisassembledAddress($disassembledAddress, $addressConfig);
		// 変更した住所の組み立て
		$assembledAddress = $this->assembleAddress($modifiedDisassembledAddress);

		// 住所データ構造で返す
		return $this->buildAddressStruct(ST_ADDR_JAPAN, $assembledAddress, $modifiedDisassembledAddress);

	}

	/**
	 * 住所の正規化。
	 * 出来る限り比較できるように統一化する
	 * @param unknown $address
	 * @param unknown $config
	 * @return unknown|NULL
	 */
	public function normalizeAddress($address, $config = null) {
		$addrConfig = $config;
		if ($this->isEmpty($addrConfig)) {
			$ac = new AddressConfig();
			$addrConfig = $ac->createConfigForNormalize();
		}

		// 正規化の時のみ
		$tmp = $this->normalizeKatanaka($address);
		return $this->changeAddress($tmp, $addrConfig);
	}

	/**
	 * 突き合せ用に、半角にできるものをすべて半角へ
	 * @param unknown $bukkenName
	 * @return unknown|string|mixed
	 */
	public function normalizeBukkenName($bukkenName) {
		if ($this->isEmpty(trim($bukkenName))) {
			return $bukkenName;
		}

		// 正規化の時のみ
		$tmp = $this->normalizeKatanaka($bukkenName);
		// すべてを半角へ
		$tmp = $this->modifyKana($bukkenName, CNV_HAN);
		// $this->logger->cLog("KANA = " . $tmp);
		$tmp = $this->modifyAlphabet($tmp, CNV_HAN);
		// $this->logger->cLog("ALPHA = " . $tmp);
		$tmp = $this->modifyNumber($tmp, CNV_HAN);
		// $this->logger->cLog("NUM = " . $tmp);
		$tmp = $this->zenHaihun2Han($tmp);
		// $this->logger->cLog("ZEN = " . $tmp);

		return $tmp;
	}

	/**
	 * 小さい「っ」や「ヶ」を大文字に変換する。
	 * @param unknown $address
	 */
	public function normalizeKatanaka($str) {
		$tmp = str_replace('っ', 'つ', $str);
		$tmp = str_replace('ッ', 'ツ', $tmp);
		$tmp = str_replace('ヶ', 'ケ', $tmp);
		return $tmp;
	}

	public function changeBukkenName($bukkenName, $kana = CNV_HAN, $alpha = CNV_HAN, $num = CNV_HAN) {
		// すべてを半角へ
		$tmp = $this->modifyKana($bukkenName, $kana);
		// $this->logger->cLog("KANA = " . $tmp);
		$tmp = $this->modifyAlphabet($tmp, $alpha);
		// $this->logger->cLog("ALPHA = " . $tmp);
		$tmp = $this->modifyNumber($tmp, $num);
		// $this->logger->cLog("NUM = " . $tmp);
		if ($kana === CNV_HAN) {
			$tmp = $this->zenHaihun2Han($tmp);
		}
		// $this->logger->cLog("ZEN = " . $tmp);

		return $tmp;

	}

	/**
	 * 住所解析後のデータからパーツを取り出す
	 * 海外の場合はfalse
	 * @param unknown $modified
	 * @param unknown $part AddressModifier::IDX_PARTS_XXX
	 * @return boolean|unknown
	 */
	public function getParts($modified, $part) {
		if ($modified === AddressConfig::TYPE_FOREIGN)
		if (!isset($modified[self::IDX_PARTS])) {
			return false;
		}
		if (!isset($modified[self::IDX_PARTS][$part])) {
			return false;
		}
		return $modified[self::IDX_PARTS][$part];
	}

	public function getChangedAddress($struct, $withMansionName = true) {
		if ($withMansionName) {
			return $struct[self::IDX_CHANGED_ADDRESS].$struct[self::IDX_PARTS][self::IDX_PARTS_MNAME];
		} else {
			return $struct[self::IDX_CHANGED_ADDRESS];
		}
	}



	/////////////////////////////////////////////////
	// 住所分解用パーツ
	/////////////////////////////////////////////////

	/**
	 * 住所の分解
	 *
	 * @param unknown $address
	 */
	public function disassembleAddress($address) {
		$tmpAdd = $address;
		$disassembledAddress = null;

		// 都道府県抽出
		$prefecture = $this->getPrefectureFromAddress($tmpAdd);
		if (!$this->isEmpty($prefecture)) {
			$prefLen = mb_strlen($prefecture);
			// 都道府県があったら、都道府県を消す
			$tmpAdd = mb_substr($tmpAdd, $prefLen);
		}

		// $this->logger->cLog("pref：" . $prefecture . " tmpAdd = " . $tmpAdd );

		$tmpAddrStrcut = null;
		if ($this->isIncludeChome($tmpAdd) || $this->isIncludeCho($tmpAdd)) {
			// 丁、丁目で分割
			$tmpAddrStrcut = $this->disassembleByChome($tmpAdd);
		} else {
			// 最初に現れた数字で分割
			$tmpAddrStrcut = $this->disassembleByFirstNumber($tmpAdd);
		}
		// 配列が空であれば何かおかしい
		if ($this->isEmpty($tmpAddrStrcut)) {
			throw new Exception("Address Struct is Empty");
		}
		// 都道府県を追加（あれば）
		$tmpAddrStrcut[self::IDX_PARTS_PREF] = (!$this->isEmpty($prefecture)) ? $prefecture : '';

		$rAfter = $tmpAddrStrcut[self::IDX_PARTS_AFTER];
		$rRoomNo = $tmpAddrStrcut[self::IDX_PARTS_ROOM_NO];
		$rMansionName = $tmpAddrStrcut[self::IDX_PARTS_MNAME];

		// 枝番に（）が含まれていればマンション名と部屋番号の切り出しを行う
		// $kakkoPattan = '/^.+[\(（].+[\)）].+$/u'; // 「熊本市中央区呉服町三丁目９番地（５０３号）エイルマンション熊本駅東Ⅱ」 など（）が含まれているパターン
		if ($this->isIncludeKakko($tmpAddrStrcut[self::IDX_PARTS_AFTER])) {
			list($rAfter, $rRoomNo, $rMansionName) = $this->dissassembleAfterByKakko($tmpAddrStrcut[self::IDX_PARTS_AFTER]);
		} else if (preg_match('/^(.+)[ |　](.+)$/u', $tmpAddrStrcut[self::IDX_PARTS_AFTER])) {
			// ()は含まれないけど、スペースがあって、その後ろにマンション名っぽいものが付いている。
			list($rAfter, $rRoomNo, $rMansionName) = $this->dissassembleAfterBySpace($tmpAddrStrcut[self::IDX_PARTS_AFTER]);
		} else {
			if (preg_match('/^.+番[地]*$/u', $tmpAddrStrcut[self::IDX_PARTS_AFTER]) ||
					preg_match('/^.+番[地]*.*号$/u', $tmpAddrStrcut[self::IDX_PARTS_AFTER]) ||
					preg_match('/^([0-9０-９]+番(?:地|地の)*[0-9０-９]+(?:号|号室)*)(.*)/u', $tmpAddrStrcut[self::IDX_PARTS_AFTER])) {
				list($rAfter, $rRoomNo, $rMansionName) = $this->dissassembleAfterByBanchi($tmpAddrStrcut[self::IDX_PARTS_AFTER]);
			} else if (preg_match('/(号|号室)/u', $tmpAddrStrcut[self::IDX_PARTS_AFTER])) {
				// ()は含まれないけど、号があって、その後ろにマンション名っぽいものが付いている。
				list($rAfter, $rRoomNo, $rMansionName) = $this->dissassembleAfterByGou($tmpAddrStrcut[self::IDX_PARTS_AFTER]);
			}
			/*
			else {
				// 最終手段
				list($rAfter, $rRoomNo, $rMansionName) = $this->dissassembleAfterByNotNumber($tmpAddrStrcut[self::IDX_PARTS_AFTER]);
			}
			*/
		}
		$tmpAddrStrcut[self::IDX_PARTS_AFTER] = $rAfter;
		$tmpAddrStrcut[self::IDX_PARTS_ROOM_NO] = $rRoomNo;
		$tmpAddrStrcut[self::IDX_PARTS_MNAME] = $rMansionName;
		/*
		$this->logger->cLog("==== DEBUG");
		$this->logger->cLog($tmpAddrStrcut);
		$this->logger->cLog("==== DEBUG");
		*/
		return $tmpAddrStrcut;
	}

	public function dissassembleAfterByBanchi($after) {
		$pat = '/^([0-9０-９]+番(?:地|地の)*[0-9０-９]+(?:号|号室)*)(.*)/u';
		return $this->dissassembleAfterCommon($pat, $after);
	}

	/**
	 * 枝番をさらに解析。
	 * 例：熊本市中央区呉服町三丁目９ー５０３エイルマンション熊本駅東
	 * 　熊本市中央区呉服町三丁目９番地
	 * 　５０３号
	 * 　エイルマンション熊本駅東
	 * @param unknown $after
	 */
	public function dissassembleAfterByNotNumber($after) {
		return $this->dissassembleAfterCommon(self::AFTER_MNAME_BUNKATSU_PATTERN, $after);
	}

	/**
	 * 枝番をさらに解析。
	 * 例：熊本市中央区呉服町三丁目９番地５０３号エイルマンション熊本駅東
	 * 　熊本市中央区呉服町三丁目９番地
	 * 　５０３号
	 * 　エイルマンション熊本駅東
	 * @param unknown $after
	 */
	public function dissassembleAfterBySpace($after) {
		$patExplodeGou = '/^(.+)[ |　](.+)$/u';
		return $this->dissassembleAfterCommon($patExplodeGou, $after);
	}

	public function dissassembleAfterCommon($pattern ,$after) {
		$matches = array();
		$rAfter = $after;
		$rMansionName = '';
		$rRoomNo = '';
		// $this->logger->cLog($after);
		// $this->logger->cLog($pattern);
		if (preg_match($pattern, $after, $matches)) {
			$rAfter = $matches[1]; // 枝番
			$rAfter = $this->removeLastHaihun($rAfter);
			$rMansionName = (isset($matches[2])) ? $matches[2] : '';
		}
		return array(trim($rAfter), trim($rRoomNo), trim($rMansionName));
	}

	/**
	 * 枝番をさらに解析。
	 * 例：熊本市中央区呉服町三丁目９番地５０３号エイルマンション熊本駅東
	 * 　熊本市中央区呉服町三丁目９番地
	 * 　５０３号
	 * 　エイルマンション熊本駅東
	 * @param unknown $after
	 */
	public function dissassembleAfterByGou($after) {
		$patExplodeGou = '/^([0-9０-９]+)(.+[^0-9０-９])([0-9０-９]+(?:号室|号))(.*)$/u';

		$patExplode2Gou = '/^(.+[^0-9０-９])([0-9０-９]+(?:号室|号))(.+(?:号室|号))$/u';
		$matches = array();

		$rAfter = $after;
		$rMansionName = '';
		$rRoomNo = '';

		$g2 = false;
		if (preg_match($patExplode2Gou, $after, $matches)) { // 号が重なるパターンで解析
			$this->logger->cLog("GOU2");
			$g2 = true;
			$rAfter = (isset($matches[1])) ? $matches[1] : ''; // 枝番
			$rAfter = $this->removeLastHaihun($rAfter);
			$rRoomNo = (isset($matches[2])) ? $matches[2] : '';
			$rMansionName = (isset($matches[3])) ? $matches[3] : '';
			return array(trim($rAfter), trim($rRoomNo), trim($rMansionName));
		}
		if (!$g2) {
			if (preg_match($patExplodeGou, $after, $matches)) {
				// ２０２８番地シルキーハイツ２０２号
				// $this->logger->cLog("GOU1");
				$g2 = true;
				$rAfter = (isset($matches[1])) ? $matches[1] : ''; // 枝番
				$rAfter = $this->removeLastHaihun($rAfter);
				$rRoomNo = (isset($matches[3])) ? $matches[3] : '';
				$rMansionName = (isset($matches[2])) ? $matches[2] : '';
				return array(trim($rAfter), trim($rRoomNo), trim($rMansionName));
			}
		}
		if (!$g2) {
			return array(self::ERROR_MARKAR.$rAfter, '', '');
		}
	}

	public function removeLastHaihun($address) {
		$pat = '/^(.+[0-9０-９])'.self::ALL_HAIHUN_WITHOUT_CHOON.'$/u';
		if (preg_match($pat, $address, $matches)) {
			return $matches[1];
		}
		return $address;
	}


	/**
	 * 枝番をさらに解析。「（部屋番号）」を含むパターン
	 * 例：熊本市中央区呉服町三丁目９番地(５０３号)エイルマンション熊本駅東
	 * 　熊本市中央区呉服町三丁目９番地
	 * 　５０３号
	 * 　エイルマンション熊本駅東
	 * @param unknown $after
	 * @return unknown[]|mixed[]|string[]
	 */
	public function dissassembleAfterByKakko($after) {
		// （）の中が部屋番号のパターン
		$patRoomNoInKakko = '/^(.+)[\(（]([0-9０-９]+(?:号室|号))[\)）](.*)$/u';
		$matches = array();
		$rAfter = $after;
		$rMansionName = '';
		$rRoomNo = '';
		if (preg_match($patRoomNoInKakko, $after, $matches)) {
			$rAfter = $matches[1]; // 枝番
			$rRoomNo = (isset($matches[2])) ? $matches[2] : '';
			$rMansionName = (isset($matches[3])) ? $matches[3] : '';
		} else {
			// ()が含まれるのに、パターンマッチしなかったのでエラー付きにする。
			$rAfter = self::ERROR_MARKAR.$rAfter;
		}

		if (!$this->isEmpty($rRoomNo) && preg_match('/^.*[0-9０-９]$/u', $rAfter)) {
			$rAfter = $rAfter.'-'.$rRoomNo; // 最後が数字で終わってたらハイフンで挟む
		} else {
			$rAfter = $rAfter.$rRoomNo;
		}

		return array(trim($rAfter), trim($rRoomNo), trim($rMansionName));
	}

	/**
	 * 最初に現れた数字で住所を区切る。
	 * この場合には、漢数字ではチェックしない。
	 * （漢数字で分割してしまうと、四ツ谷などで分割されてしまうため）
	 * @param unknown $address
	 */
	public function disassembleByFirstNumber($address) {
		if ($this->isEmpty($address)) {
			return $address;
		}
		if ($this->isIncludeChome($address) || $this->isIncludeCho($address)) {
			throw new Exception("This address has chome. " . $address);
		}

		// パターンで分解
		$matches = null;
		$ret = preg_match(self::EXTRACT_FIRST_NUMBER_PATTERN, $address, $matches);
		$chome = '';
		if ($ret) {
			$city = $matches[1];
			$after = (isset($matches[2])) ? $matches[2]:'';
		} else {
			$city = $address;
			$after = '';
		}
		return $this->buildPartsStruct('', $city, $chome, $after);

	}

	/**
	 * 丁[目]で住所を区切る
	 * @param unknown $address
	 * @return unknown|boolean|unknown[]|string[]|NULL[]|mixed[]
	 */
	public function disassembleByChome($address) {
		if ($this->isEmpty($address)) {
			return $address;
		}

		if ($this->isIncludeChome($address)) {
			// 丁目で区切る
			return $this->disassembleByChomePattern(self::EXTRACT_ALL_CHOME_PATTERN, $address);
		} else if ($this->isIncludeCho($address)) {
			// 丁で区切る
			return $this->disassembleByChomePattern(self::EXTRACT_ALL_CHO_PATTERN, $address);
		}
		throw new Exception('This address does not have chome. ' . $address);
	}


	/**
	 * パターンで「丁目」を分解する
	 * 全角か半角かはパターンで決まる
	 * @param unknown $pattern
	 * @param unknown $address
	 */
	private function disassembleByChomePattern($pattern, $address) {
		// パターンで分解
		$matches = null;
		$ret = preg_match($pattern, $address, $matches);
		if ($ret) {
			$city = $matches[1];
			$chome = $matches[2];
			$after = (isset($matches[3])) ? $matches[3]:'';
		} else {
			$city = $address;
			$chome = '';
			$after = '';
		}
		return $this->buildPartsStruct('', $city, $chome, $after);
	}

	/////////////////////////////////////////////////
	// 住所変更
	/////////////////////////////////////////////////

	/**
	 * 海外住所の変更
	 * @param unknown $address
	 * @param AddressConfig $config
	 */
	public function changeForeignAddress($address, AddressConfig $config) {
		$kana = $config->getKana(AddressConfig::TYPE_FOREIGN);
		$kanaHaihun = $config->getKanaHaihun(AddressConfig::TYPE_FOREIGN);
		$numberCase = $config->getNumberCase(AddressConfig::TYPE_FOREIGN);
		$alphabetCase = $config->getAlphabetCase(AddressConfig::TYPE_FOREIGN);

		$tmp = $address;
		$tmp = $this->modifyKana($tmp, $kana);
		$tmp = $this->modifyKanaHaihun($tmp, $kanaHaihun);
		$tmp = $this->modifyNumber($tmp, $numberCase);
		$tmp = $this->modifyAlphabet($tmp, $alphabetCase);

		// 外国の場合は、cityにすべてのアドレスを入れる
		return $this->buildPartsStruct('', $tmp, '', '');
	}

	/**
	 * 分割された住所データを変更する
	 * @param unknown $disassembledAddress
	 * @param unknown $returnMode
	 */
	public function modifyDisassembledAddress($disassembledAddress, AddressConfig $addressConfig) {
		if ($this->isEmpty($disassembledAddress) || $this->isEmpty($addressConfig)) {
			throw new Exception('Address data or Config is EMPTY');
		}

		// 初期化
		$parts = $disassembledAddress;
		$city = $chome = $after = '';
		$pref = $parts[self::IDX_PARTS_PREF];
		$after = '';
		$mName = '';
		$roomNo = '';

		// CITYの変更：
		$city = $this->modifyCity($parts[self::IDX_PARTS_CITY],
				$addressConfig->getKana(AddressConfig::TYPE_CITY),
				$addressConfig->getKanaHaihun(AddressConfig::TYPE_CITY),
				$addressConfig->getNumberCase(AddressConfig::TYPE_CITY),
				$addressConfig->getAlphabetCase(AddressConfig::TYPE_CITY)
				);

		// CHOMEの変更：
		if (!$this->isEmpty($parts[self::IDX_PARTS_CHOME])) {
			// CHOMEの変更：
			$chome = $this->modifyChome($parts[self::IDX_PARTS_CHOME],
					$parts[self::IDX_PARTS_AFTER],
					$addressConfig->getNumberCase(AddressConfig::TYPE_CHOME),
					$addressConfig->getAlphabetCase(AddressConfig::TYPE_CHOME),
					$addressConfig->getCnvChome());
		}
		// 市区町村丁目以下の変更
		if (!$this->isEmpty($parts[self::IDX_PARTS_AFTER])) {
			// AFTERの変更：
			$after = $this->modifyAfter($parts[self::IDX_PARTS_AFTER],
					$addressConfig->getKana(AddressConfig::TYPE_AFTER),
					$addressConfig->getKanaHaihun(AddressConfig::TYPE_AFTER),
					$addressConfig->getNumberCase(AddressConfig::TYPE_AFTER),
					$addressConfig->getAlphabetCase(AddressConfig::TYPE_AFTER)
					);

			// もし、部屋番号があれば
			$roomNo = $this->modifyAfter($parts[self::IDX_PARTS_ROOM_NO],
					$addressConfig->getKana(AddressConfig::TYPE_AFTER),
					$addressConfig->getKanaHaihun(AddressConfig::TYPE_AFTER),
					$addressConfig->getNumberCase(AddressConfig::TYPE_AFTER),
					$addressConfig->getAlphabetCase(AddressConfig::TYPE_AFTER)
					);

			$mName = $parts[self::IDX_PARTS_MNAME];
			/*
			$hasMansionName = $this->exceptMansionNameFromAfter($after);
			if ($hasMansionName) {
				$after = $hasMansionName[0];
				$mName = $hasMansionName[1];
			}
			*/
		}

		// $this->logger->cLog("CHOME = " . $chome);
		// 変更後のデータ
		$changedParts = $this->buildPartsStruct($pref, $city, $chome, $after, $mName, $roomNo);
		/*
		$this->logger->cLog("#######==== DEBUG");
		$this->logger->cLog($changedParts);
		$this->logger->cLog("#######==== DEBUG");
		*/
		return $changedParts;

	}

	/**
	 * ケツからマンション名と思われるものを抽出
	 * 「-− 」で分割して、その中が数字以外が入る且つ5文字以上である＝マンション名
	 * @param unknown $after
	 */
	public function exceptMansionNameFromAfter($after) {
		if ($this->isEmpty($after)) {
			return false;
		}
		$matches = null;
		if (preg_match('/(.+)[\-\- ](.+)$/u', $after, $matches)) {
			if (is_array($matches) && count($matches) > 2) {
				// 要素が３個以上であれば、検索条件にマッチした。
				$afterEda = $matches[1];
				$afterMansionName = $matches[2];
				if (preg_match('/^[0-9０-９]+$/u', $afterMansionName)) {
					; // 部屋番号とか住所だとか
				} else {
					if (strlen($afterMansionName) > 4) {
						// マンション名
						return array($afterEda, $afterMansionName);
					}
				}
			}
		}
		return false;
	}

	/**
	 * 市区町村の変更
	 * @param unknown $city
	 * @param unknown $kana
	 * @param unknown $kanaHan
	 * @param unknown $alphaNum
	 * @return unknown|unknown|mixed
	 */
	public function modifyCity($city, $kana, $kanaHan, $number, $alpha) {
		if ($this->isEmpty($city)) {
			return $city;
		}
		// 変換
		$tmp = trim($city);
		$tmp = $this->modifyKana($tmp, $kana);
		$tmp = $this->modifyKanaHaihun($tmp, $kanaHan);
		$tmp = $this->modifyNumber($tmp, $number);
		$tmp = $this->modifyAlphabet($tmp, $alpha);

		return $tmp;
	}

	/**
	 * 市区町村[丁目]以降のデータを変換する（枝番）
	 * @param unknown $after
	 * @param unknown $kana
	 * @param unknown $kanaHaihun
	 * @param unknown $alphaNum
	 */
	public function modifyAfter($after, $kana, $kanaHan, $number, $alpha) {
		if ($this->isEmpty($after)) {
			return $after;
		}
		// パターン別に変換をする必要性

		// 変換
		$tmp = trim($after);
		$tmp = $this->modifyBanchi($after);
		// $this->logger->cLog("番地：".$after . " CHG:" . $tmp);
		$tmp = $this->modifyBan($tmp);
		// 枝番であれば、漢数字を数字に置き換えても大丈夫
		$tmp = $this->kansuji2ZenkakuSingle($tmp);

		$tmp = $this->modifyKana($tmp, $kana);
		// $this->logger->cLog("KANA：".$after . " CHG:" . $tmp);
		$tmp = $this->modifyKanaHaihun($tmp, $kanaHan);
		// $this->logger->cLog("KANAHIHUN：".$after . " CHG:" . $tmp);
		$tmp = $this->modifyNumber($tmp, $number);
		// $this->logger->cLog("NUMBER：".$after . " CHG:" . $tmp);
		$tmp = $this->modifyAlphabet($tmp, $alpha);
		// $this->logger->cLog("ALPHA：".$after . " CHG:" . $tmp);

		// この処理はここでやらないほうが良い
		$tmp = $this->modifyGouLast($tmp);
		// $this->logger->cLog("GOU LAST：".$after . " CHG:" . $tmp);
		return $tmp;

	}

	/////////////////////////////////////////////////
	// 住所変更用パーツ

	/**
	 * カナの半角全角変換
	 * @param unknown $str
	 * @param unknown $config CNV_HAN:半角、CNV_ZEN:全角、CNV_NOT:変換なし
	 */
	public function modifyKana($str, $config) {
		if ($config === CNV_NOT || $this->isEmpty($str)) {
			return $str;
		}
		if ($config === CNV_HAN) {
			// 文字列に含まれるカタカナを半角へ変換する
			return mb_convert_kana($str, 'kv', self::ENCODE);
		} else if ($config === CNV_ZEN) {
			// 文字列に含まれるカナタナを全角へ変換する。濁点付き文字は一文字へ
			return mb_convert_kana($str, 'KV', self::ENCODE);
		} else {
			return $str;
		}
	}

	/**
	 * 文字列に含まれる「ハイフン」「長音」を強制的に「ハイフン」へ変換する。
	 * @param unknown $str
	 * @param unknown $config
	 */
	public function modifyKanaHaihun($str, $config) {
		if ($config === CNV_NOT || $this->isEmpty($str)) {
			return $str;
		}

		$haihunPattern = self::ALL_HAIHUN_PATTERN;
		$tmpA = preg_replace($haihunPattern, self::REPLACE_HAN_HAIHUN, $str);

		$replaceTo = self::REPLACE_HAN_HAIHUN;
		if ($config === CNV_HAN) {
			$replaceTo = self::REPLACE_HAN_HAIHUN;
		} else if ($config === CNV_ZEN) {
			$replaceTo = self::REPLACE_HAN_HAIHUN;
		} else {
			return $str;
		}
		return preg_replace($haihunPattern, $replaceTo, $str);
	}

	/**
	 * 数字を変換する。マイナスがある場合にはマイナスも変換する
	 * @param unknown $str
	 * @param unknown $config
	 */
	public function modifyNumber($str, $config) {
		if ($config === CNV_NOT || $this->isEmpty($str)) {
			return $str;
		}
		$result = $str;
		if ($config === CNV_HAN) {
			// 英数字を半角へ変換
			$tmp = mb_convert_kana($str, 'n', self::ENCODE);
			// ハイフンを半角へ変換
			$result = $this->modifyHaihun(self::TYPE_NUM, $tmp, $config);
		} else if ($config === CNV_ZEN) {
			// 英数字を全角へ変換
			$tmp = mb_convert_kana($str, 'N', self::ENCODE);
			// ハイフンを全角へ変換
			$result = $this->modifyHaihun(self::TYPE_NUM, $tmp, $config);
		}
		return $result;
	}

	/**
	 * 英字を変換する。
	 * @param unknown $str
	 * @param unknown $config
	 */
	public function modifyAlphabet($str, $config) {
		if ($config === CNV_NOT || $this->isEmpty($str)) {
			return $str;
		}
		$result = $str;
		if ($config === CNV_HAN) {
			// 英数字を半角へ変換
			$tmp = mb_convert_kana($str, 'r', self::ENCODE);
			$result = $this->modifyHaihun(self::TYPE_ALPHA, $tmp, $config);
		} else if ($config === CNV_ZEN) {
			// 英数字を全角へ変換
			$tmp = mb_convert_kana($str, 'R', self::ENCODE);
			$result = $this->modifyHaihun(self::TYPE_ALPHA, $tmp, $config);
		}

		// $this->logger->cLog("CONF = " . $config .  "STR = " . $str . " CNV = " . $tmp);
		return $result;
	}

	public function modifyHaihun($type, $str, $config) {

		// $this->logger->cLog("TYPE = " . $type . " STR = " . $str  . " CONFIG = " . $config);

		// number=trueなら、数字の後に続くハイフンすべて
		if ($type === self::TYPE_NUM
				&& preg_match_all('/[0-9０-９]' . self::ALL_HAIHUN . '/u', $str, $targets)) {
					// $this->logger->cLog($targets);
			return $this->convertHaihun($str, $targets, $config);
		} else if ($type === self::TYPE_KANA
				&& preg_match_all('/[ァ-ヶｦ-ﾟ]' . self::ALL_HAIHUN . '/u', $str, $targets)) {
				// number=falseなら、カナの後に続くハイフンすべて
				return $this->convertHaihun($str, $targets, $config);
		} else if ($type === self::TYPE_ALPHA
				&& preg_match_all('/[a-zA-Zａ-ｚＡ-Ｚ]' . self::ALL_HAIHUN . '/u', $str, $targets)) {
				// number=falseなら、カナの後に続くハイフンすべて
				return $this->convertHaihun($str, $targets, $config);
		}
		// $this->logger->cLog("AFTER TYPE = " . $type . " STR = " . $str  . " CONFIG = " . $config);
		return $str;
	}

	private function convertHaihun($orgStr, $targets, $config) {

		$replaceHaihun = self::REPLACE_HAN_HAIHUN;
		$tmpStr = $orgStr;

		// $this->logger->cLog("ORG = " . $orgStr);
		// $this->logger->cLog($targets);

		if ($config === CNV_ZEN) {
			$replaceHaihun = self::REPLACE_ZEN_HAIHUN;
		}
		foreach ($targets as $target) {
			$targHaihun = preg_replace('/' . self::ALL_HAIHUN . '/u', $replaceHaihun, $target);
			$tmpStr = str_replace($target, $targHaihun, $tmpStr);
		}
		// $this->logger->cLog($tmpStr);
		return $tmpStr;
	}

	/**
	 * 丁目の変換
	 * @param unknown $str
	 * @param unknown $zenHan
	 * @param unknown $cnvChome
	 */
	public function modifyChome($chomePart, $afterPart, $numberCase, $alphaCase, $cnvChome) {

		// $this->logger->cLog("CHOME:" .$chomePart."/". $afterPart ."/".  $numberCase."/".  $alphaCase."/".$cnvChome);
		if ($this->isEmpty($chomePart) ||
				($numberCase === CNV_NOT && $cnvChome === CNV_NOT)) {
			return $chomePart;
		}
		// $this->logger->cLog("CHOME = " . $chomePart . " AFTER = " . $afterPart . " CASE = "  . $numberCase . " CNV = " . $cnvChome);
		// 数字部と丁目部に分解
		$aTmp = null;
		$ret = preg_match('/^([0-9０-９一二三四五六七八九十壱弐参拾百千万萬億兆〇]+)([丁目|丁]+)$/u', $chomePart, $aTmp);
		$tmpNumberPart = '';
		$tmpChomePart = '';

		if ($ret && count($aTmp) === 3) {
			$tmpNumberPart = $aTmp[1];
			$tmpChomePart = $aTmp[2];
		} else {
			// 丁目で区切れなかった
			return $chomePart;
		}

		// $this->logger->cLog("丁目 -> " . $tmpNumberPart);

		// 漢数字なら全角へ変換
		if (preg_match('/^[一二三四五六七八九十壱弐参拾百千万萬億兆〇]+$/u', $tmpNumberPart)) {
			$tmpNumberPart = $this->kansuji2Zenkaku($tmpNumberPart);
			// $this->logger->cLog(" NUMBER => " . $tmpNumberPart);
		}
		// 指定のケースに数字を変換
		$resultNumberPart = $this->modifyNumber($tmpNumberPart, $numberCase);

		// $this->logger->cLog("RESULT NUM = " . $resultNumberPart);
		$resultChomePart = $tmpChomePart;
		// 丁目のままでよければそのままつなげて返却
		if ($cnvChome === CNV_NOT) {
			return $resultNumberPart . $tmpChomePart;
		}

		// 丁目を変換する
		// 丁目以降が存在すればハイフンが必要
		if (!$this->isEmpty($afterPart)) {
			$resultChomePart = ($cnvChome === CNV_HAN) ? self::REPLACE_HAN_HAIHUN:self::REPLACE_ZEN_HAIHUN;
		} else {
			$resultChomePart = '';
		}
		// $this->logger->cLog("MOD = " . $resultChomePart . "  = " . $resultNumberPart);

		return $resultNumberPart . $resultChomePart;
	}

	/**
	 * '番地'をとりあえずハイフンへ変換する
	 * 番地の後ろに何もない場合は''に変換。何か文字列があれば、replaceで変換する
	 * @param unknown $address
	 */
	public function modifyBanchi($address, $replace = self::REPLACE_HAN_HAIHUN) {
		$repStr = $replace;
		/*
		if (preg_match(self::BANCHI_PATTERN2, $address)) {
			return str_replace(self::STR_BANCHI, '', $address);
		}
		*/

		// $this->logger->cLog("FROM:" . $address);
		if (preg_match(self::BANCHI_PATTERN, $address)) {
			if (preg_match('/番地の/', $address)) {
				return str_replace('番地の', $repStr, $address);
			} else {
				return str_replace('番地', $repStr, $address);
			}
		} else {
			// 後ろに何もない場合は空文字
			if (preg_match('/番地の/', $address)) {
				return str_replace('番地の', '', $address);
			} else {
				return str_replace('番地', '', $address);
			}
		}
	}

	/**
	 * '番'をとりあえずハイフンへ変換する
	 * 番の後ろに何もない場合は''に変換。何か文字列があれば、replaceで変換する
	 * @param unknown $address
	 */
	public function modifyBan($address, $replace = self::REPLACE_HAN_HAIHUN) {
		$repStr = $replace;
		if (preg_match(self::BAN_PATTERN, $address)) {
			return str_replace(self::STR_BAN, $repStr, $address);
		} else {
			// 後ろに何もない場合は空文字
			return str_replace(self::STR_BAN, '', $address);
		}
	}

	public function modifyGouLast($address, $replace = self::REPLACE_HAN_HAIHUN) {
		$repStr = $replace;
		if (preg_match(self::GOU_LAST_PATTERN, $address)) {
			return preg_replace('/号$/u', "", $address);
		}
		return $address;
	}


	/////////////////////////////////////////////////
	// 住所組み立て
	/////////////////////////////////////////////////

	/**
	 * 分解データから住所を作成する
	 * @param unknown $disassembledAddress
	 * @return string
	 */
	public function assembleAddress($disassembledAddress) {
		$parts = $disassembledAddress;
		return $parts[self::IDX_PARTS_PREF]
				.$parts[self::IDX_PARTS_CITY]
				.$parts[self::IDX_PARTS_CHOME]
				.$parts[self::IDX_PARTS_AFTER];
	}


	/////////////////////////////////////////////////
	// ユーティリティ
	/////////////////////////////////////////////////

	/**
	 * 先頭にカッコがあったら取り除く
	 * @param unknown $str
	 */
	public function removeTopKakko($str) {
		if ($this->isEmpty($str)) {
			return $str;
		}
		$pat = '/^[（|(].*$/u';
		$repPat = '/^[（|(]/u';

		if (preg_match($pat, $str)) {
			return preg_replace($repPat, '', $str);
		}
		return $str;
	}

	/**
	 * 最後にハイフンがあったら「号」に変換する。
	 * @param unknown $str
	 * @return unknown|mixed
	 */
	public function changeLastHaihun2Gou($str) {
		if ($this->isEmpty($str)) {
			return $str;
		}
		$pat = '/.+\-$/u';
		$repPat = '/\-$/u';
		if (preg_match($pat, $str)) {
			return preg_replace($repPat, '号', $str);
		}
		return $str;
	}

	const IDX_PARTS_S_PREF = '__s_pref';
	const IDX_PARTS_S_CITY = '__s_city';
	const IDX_PARTS_S_TOWN = '__s_town';

	/**
	 * 都道府県、市区町村を
	 * 都道府県、市区郡、町村へ分ける
	 * @param unknown $pref
	 * @param unknown $city
	 */
	public function splitShikugunTown($pref, $cityAndTown) {
		// 政令指定都市かどうか？
		$govCity = $this->analyzeGovOrdinanceDesignatedCityMain($pref, $cityAndTown);
		$this->logger->cLog($govCity);

		$aPref = $aCity = $aTown = '';
		if ($govCity) {
			// 政令指定都市
			$aPref = $govCity[self::IDX_PARTS_GOV_PREF];
			$aCity = $govCity[self::IDX_PARTS_GOV_CITY];
			$aTown = $govCity[self::IDX_PARTS_GOV_TOWN];
		} else {
			$aPref = $pref;
			$found = false;
			// 市でわけて、残りが町
			$tmp = explode('市', $cityAndTown);
			if (count($tmp) > 1) $found = true;
			if (!$found) {
				// ダメなら群で分けて、残りが町
				$tmp = explode('郡', $cityAndTown);
				if (count($tmp) > 1) $found = true;
			}
			if (!$found) {
				// 区で分けて残りが町
				$tmp = explode('区', $cityAndTown);
				if (count($tmp) > 1) $found = true;
			}
			if (!$found) {
				// 何も見つからなかった
				$aCity = $cityAndTown;
				$aTown = '';
			} else {

			}
			if (!$found && count($tmp) > 1) {
				$aCity = $tmp[0];
				$aTown = isset($tmp[1]) ? $tmp[1] : '';
			}
		}

		return array(
			self::IDX_PARTS_S_PREF => $aPref,
				self::IDX_PARTS_S_CITY => $aCity,
				self::IDX_PARTS_S_TOWN => $aTown
		);
	}

	/**
	 * 字で分ける
	 * @param unknown $address
	 * @return NULL|unknown[]
	 */
	public function splitAza($address) {
		$matches = null;
		// $this->logger->cLog("IN:".$address);
		if (preg_match(self::SPLIT_AZA_PATTERN, $address, $matches)) {
			// $this->logger->cLog($matches);
			return $matches;
		}
		return array($address);
	}


	/**
	 * 住所の最後に現れる「-」以降で分割する。
	 * 最後に数字以外も含まれていた場合、数字のみ返す場合は、$numberOnlyをtrueへ
	 * すべて文字列を返す場合には、$numberOnlyをfalseへ
	 * 千代田区麹町３丁目３−２１−５−２０３
	 * [0] => 千代田区麹町３丁目３−２１−５
	 * [1] => ２０３
	 *
	 * 千代田区麹町３丁目３−２１−５−２０３号もしくは号室 → 千代田区麹町３丁目３−２１−５
	 * ↑に同じ
	 *
	 * @param unknown $address
	 */
	public function splitHaihunLastPart($address, $numberOnly = false) {

		if ($numberOnly) {
			$pattern = '/(.+)'.self::ALL_HAIHUN.'([0-9０-９]+).*$/u';
		} else {
			$pattern = '/(.+)'.self::ALL_HAIHUN.'([0-9０-９]+.*)$/u';
		}

		$ret = preg_match($pattern, $address, $result);
		// $this->logger->cLog($result);
		if ($ret) {
			array_shift($result);
			return $result;
		} else {
			return false;
		}

	}


	/**
	 * ハイフンで区切られた家屋番号の最後の英数字番号の桁数を取得する。
	 * ハイフンで区切られた最後の番号が取得出来ない、もしくは、英数字で構成されてなければ
	 * -1を返す
	 * @param unknown $normKaokuNo
	 */
	public function extractKaokuEdaLastDigit($normKaokuNo) {

		if ($this->isEmpty($normKaokuNo)) {
			throw new Exception("Error!!! 家屋番号が空");
		}

		$kaokuTmp = explode(self::REPLACE_HAN_HAIHUN, $normKaokuNo);
		$eCount = count($kaokuTmp);

		if ($eCount < 2) {
			$this->logger->debugLog("*********** explode count under 2 ****************");
			$this->logger->debugLog($normKaokuNo);
			$this->logger->debugLog($kaokuTmp);
			$this->logger->debugLog("***************************");
			return -1;
		}

		$lastKaokuNo = $kaokuTmp[$eCount-1];
		$kaokuDigit = strlen($lastKaokuNo);

		return $kaokuDigit;
	}


	/**
	 * ハイフンを半角へ
	 * @param unknown $str
	 * @return string|mixed
	 */
	public function zenHaihun2Han($str) {
		if (empty(trim($str))) {
			return '';
		}

		// $haihunPattern = '/[\x{30FC}\x{2010}-\x{2015}\x{2212}\x{FF70}-]/u';
		$haihunPattern = self::ALL_HAIHUN_PATTERN;
		$tmpA = preg_replace($haihunPattern, self::REPLACE_HAN_HAIHUN, $str);
		// $tmpA = preg_replace('/[\-\−\ー\―\‐\－]/u', self::REPLACE_HAN_HAIHUN, $str);

		return $tmpA;
	}

	/**
	 * 「ー1」「ー２」などの全角ハイフンを半角に治す。
	 * 「コーポ」などは直さない
	 * @param unknown $str
	 * @return string|mixed
	 */
	public function zenHaihunWithNumber2Han($str) {
		if (empty(trim($str))) {
			return '';
		}
		// '/[ー‐]([0-9０-９])/u', '-$1'
		$haihunPattern = '/['.self::STR_ZEN_HAIHUN.self::STR_ZEN_HAIHUN2.self::STR_ZEN_CHOON.']([0-9０-９]/u';
		$replacePattern = '-$1';
		$tmpA = preg_replace($haihunPattern, $replacePattern, $str);

		return $tmpA;

	}

	/**
	 * 半角ハイフンを全角ハイフンへ修正
	 * @param unknown $str
	 * @return string|mixed
	 */
	public function hanHaihun2Zen($str) {
		if (empty(trim($str))) {
			return '';
		}
		$tmpA = str_replace(self::REPLACE_HAN_HAIHUN, self::STR_ZEN_HAIHUN, $str);
		// $tmpB = str_replace(self::REPLACE_HAN_HAIHUN, self::STR_ZEN_CHOON, $tmpA);

		return $tmpA;
	}

	/**
	 * 漢数字を全角数字へ変換する。変換テーブルを利用
	 * @param unknown $kansuji
	 */
	public function kansuji2Zenkaku($kansuji) {
		if (empty($kansuji)) {
			return '';
		}

		// 漢数字に対応する全角数字を返す
		$kansujiTable = $this->buildKansujiTable();
		return (isset($kansujiTable[$kansuji]))
				? $kansujiTable[$kansuji] : $kansuji;
	}

	/**
	 * 漢数字を全角数字へ変換する。単純な文字置き換え
	 * @param unknown $kansuji
	 * @return string
	 */
	public function kansuji2ZenkakuSingle($kansuji) {
		if (empty($kansuji)) {
			return '';
		}
		// ループするので漢数字がなければなにもしない
		if (!$this->isIncludeKansuji($kansuji)) {
			return $kansuji;
		}
		$kansujiList = $this->kansujiPattern;
		$kanStr = $kansuji;
		foreach ($kansujiList as $kan => $zen) {
			$kanStr = preg_replace('/'.$kan.'/u', $zen, $kanStr);
		}
		return $kanStr;
	}

	/**
	 * 丁目、番地を削除する
	 * @param unknown $address
	 */
	public function removeUnderTown($address) {
		$result = null;
		$ret = preg_match(self::REMOVE_HANKAKU_CHOME_PATTERN, $address, $result);
		if ($ret) {
			return $result[1];
		}
		return $address;
	}

	/////////////////////////////////////////////////
	// 判定用関数
	/////////////////////////////////////////////////

	/**
	 * 解析ミスによるエラーのパターンをチェックする。
	 * @param unknown $address
	 */
	public function isIncludeErrorPattern($address) {
		// XX市X-町XX-XX などの町名の前が-になっている。三番町などの番を-に変換してしまうため。
		$banHaihunPat = '/'.self::ALL_HAIHUN_WITHOUT_CHOON.'町/u';
		if (preg_match($banHaihunPat, $address)) {
			return true;
		}

		// XX町XX-室
		$sitsuHaihunPat = '/'.self::ALL_HAIHUN_WITHOUT_CHOON.'室/u';
		if (preg_match($sitsuHaihunPat, $address)) {
			return true;
		}

		return false;
	}

	public function isIncludeKakko($address) {
		return preg_match(self::KAKKO_PATTERN, $address);
	}

	public function isError($struct) {
		if (isset($struct[self::IDX_ADDRESS_STATUS]) &&
				($struct[self::IDX_ADDRESS_STATUS] === ST_ADDR_INCLUDE_ERROR
						|| $struct[self::IDX_ADDRESS_STATUS] === ST_ADDR_UNKNOWN
						|| $struct[self::IDX_ADDRESS_STATUS] === ST_ADDR_EMPTY)) {
			return true;
		} else {
			return false;
		}

	}

	/*
	public function isIncludeKansuji($str) {
		$ret = preg_match(self::KANSUJI_PATTERN, $str);
		return ($ret);
	}*/

	/**
	 * 漢数字を含む丁目か？
	* @param unknown $address
	*/
	public function isIncludeKansujiChome($address) {
		if ($this->isEmpty($address)) {
			return false;
		}
		$ret = preg_match(self::KANSUJI_CHOME_PATTERN, $address, $result);
		return ($ret);
	}

	/**
	 * 漢数字が含まれるか？
	 * @param unknown $str
	 */
	public function isIncludeKansuji($str) {
		if ($this->isEmpty($str)) {
			return false;
		}
		// $ret = preg_match(self::KANSUJI_CHOME_PATTERN, $address, $result);
		$ret = preg_match(self::KANSUJI_PATTERN, $str, $result);
		return ($ret);
	}

	/**
	 * 桁を表す漢数字が含まれるか？
	 * 例外処理用
	 * @param unknown $str
	 * @return boolean|number
	 */
	public function isIncludeKansujiDigit($str) {
		if ($this->isEmpty($address)) {
			return false;
		}
		// $ret = preg_match(self::KANSUJI_CHOME_PATTERN, $address, $result);
		$ret = preg_match(self::KANSUJI_DIG, $address, $result);
		return ($ret);
	}

	/**
	 * 文字列に「丁目」が入っているかどうか判定
	 * @param unknown $address
	 */
	public function isIncludeChome($address) {
		return preg_match(self::CHOME_PATTERN, $address);
	}

	public function isIncludeCho($address) {
		return preg_match(self::CHO_PATTERN, $address);
	}

	/**
	 * クエスチョンを含むか？
	 * @param unknown $address
	 * @return number
	 */
	public function isIncludeQuestion($address) {
		return preg_match(self::QUESTION_PATTERN, $address);
	}

	/**
	 * アルファベットを含むか？
	 * @param unknown $address
	 * @return number
	 */
	public function isIncludeAlphabet($address) {
		return preg_match(self::ALPHABET_PATTERN, $address);
	}

	public function isIncludeErrorChar($address) {
		return preg_match(self::ERROR_PATTERN, $address);
	}

	/**
	 * 漢数字対応表
	 * @return string[]
	 */
	private function buildKansujiTable() {
		return $this->kansujiTable;
	}


	/**
	 * 日本の都道府県か？
	 * @param unknown $prefName
	 */
	public function isPrefectureOfJapan($prefName) {
		return isset($this->prefNameKeys[$prefName]);
	}

	private function buildPrefecture() {
		$prefectures = Array(
				1 => '北海道',
				2 => '青森県',
				3 => '岩手県',
				4 => '宮城県',
				5 => '秋田県',
				6 => '山形県',
				7 => '福島県',
				8 => '茨城県',
				9 => '栃木県',
				10 => '群馬県',
				11 => '埼玉県',
				12 => '千葉県',
				13 => '東京都',
				14 => '神奈川県',
				15 => '新潟県',
				16 => '富山県',
				17 => '石川県',
				18 => '福井県',
				19 => '山梨県',
				20 => '長野県',
				21 => '岐阜県',
				22 => '静岡県',
				23 => '愛知県',
				24 => '三重県',
				25 => '滋賀県',
				26 => '京都府',
				27 => '大阪府',
				28 => '兵庫県',
				29 => '奈良県',
				30 => '和歌山県',
				31 => '鳥取県',
				32 => '島根県',
				33 => '岡山県',
				34 => '広島県',
				35 => '山口県',
				36 => '徳島県',
				37 => '香川県',
				38 => '愛媛県',
				39 => '高知県',
				40 => '福岡県',
				41 => '佐賀県',
				42 => '長崎県',
				43 => '熊本県',
				44 => '大分県',
				45 => '宮崎県',
				46 => '鹿児島県',
				47 => '沖縄県',
				);
		$this->prefCdKeys = $prefectures;
		$this->prefNameKeys = array_flip($prefectures);
	}

	/**
	 * 住所から都道府県を抜き出す。
	 * 都道府県が発見できない場合は、海外か、市区町村から始まっているか。
	 * @param unknown $address
	 * @return boolean
	 */
	public function getPrefectureFromAddress($address) {
		if ($this->isEmpty($address)) {
			return false;
		}
		foreach ($this->prefCdKeys as $prefCd => $prefName) {
			if (preg_match('/^' . $prefName . '.*/u', $address)) {
				return $prefName;
			}
		}
		return false;
	}

	/**
	 * 政令指定都市であれば、政令指定都市の分け方に変換して配列で返す
	 * @param unknown $pref
	 * @param unknown $city
	 * @param unknown $town
	 */
	public function analyzeGovOrdinanceDesignatedCity($pref, $city, $town) {
		if ($this->isEmpty($pref) || !isset($this->govDesignatedCity[$pref])) {
			return false;
		}

		$inCityTowns = $city.$town;
		return $this->analyzeGovOrdinanceDesignatedCityMain($pref, $inCityTowns);

	}

	/**
	 * 政令指定都市での分割
	 * @param unknown $pref
	 * @param unknown $inCityTowns
	 * @return unknown[]|boolean
	 */
	public function analyzeGovOrdinanceDesignatedCityMain($pref, $inCityTowns) {

		if ($this->isEmpty($pref) || !isset($this->govDesignatedCity[$pref])) {
			return false;
		}

		// 県以下の市区
		$gCities = $this->govDesignatedCity[$pref];
		foreach ($gCities as $gCity => $gTowns) {
			// 市以下の区
			// $this->logger->cLog($pref);
			foreach ($gTowns as $gTown) {
				// 市と区をつなげて
				$govCityTown = $gCity.$gTown;
				// $this->logger->cLog($govCityTown);
				$matchPattern = '/^'.$govCityTown.'(.*)$/u';
				$result = null;

				// 川崎市幸区ほげほげ町 → 川崎市幸区、ほげほげ町
				if (preg_match($matchPattern, $inCityTowns, $result)) {
					// $this->logger->cLog($result);
					return $this->createGovOrdinanceDesignatedCityStruct($pref, $govCityTown, $result[1]);
				}
			}
		}
		return false;
	}

	/**
	 * 家屋番号チェック用などの市区郡、町を区切るための関数
	 * @param unknown $pref
	 * @param unknown $cityAndTown
	 * @return unknown[]
	 */
	public function splitSikugun($pref, $cityAndTown) {

		/**
		 * 岩手県など、XX群XX町XX町と町名が重なる。それが、後方参照だと問題になる。
		 * 以下が正解
		 * 市区：XX群XX町
		 * 町名：XX町
		 * このため、市区に「町」が２回現れる場合の対応を行うまで使用禁止
		 */
		// throw new Exception('Conding not completed!!!!!');

		if ($pref == '余白' || $cityAndTown == '余白') {
			return $this->splitMatches(array('余白', '余白'));
		}

		// 東京都＋区なら、区で区切る
		if ($pref === '東京都') {
			if (preg_match('/^(.+区)(.*)$/u', $cityAndTown, $matches)) {
				return $this->splitMatches($matches);
			}
		}

		// 1.政令指定都市で判断
		$govCity = $this->analyzeGovOrdinanceDesignatedCityMain($pref, $cityAndTown);
		if ($govCity) {
			return array(
					$govCity[self::IDX_PARTS_GOV_CITY], $govCity[self::IDX_PARTS_GOV_TOWN]
			);
		}

		// 3.郡＋町
		if (preg_match('/^(.+郡.+町)(.*)$/u', $cityAndTown, $matches)) {
			return $this->splitMatches($matches);
		}
		// 3-1.郡＋村
		if (preg_match('/^(.+郡.+村)(.*)$/u', $cityAndTown, $matches)) {
			return $this->splitMatches($matches);
		}

		// 4.市
		if (preg_match('/^.+市市.+$/u', $cityAndTown)) {
			if (preg_match('/^(.+市)(市.+)$/u', $cityAndTown, $matches)) {
				return $this->splitMatches($matches);
			}
		} else {
			if (preg_match('/^(.+市)(.+)$/u', $cityAndTown, $matches)) {
				return $this->splitMatches($matches);
			}
		}

		// 5.区で区切る
		if (preg_match('/^(.+区)(.+)$/u', $cityAndTown, $matches)) {
			return $this->splitMatches($matches);
		}
		// $this->logger->cLog("PATTERN CITY AND TOWN:" . $cityAndTown);
		return array(self::ERROR_MARKAR.$cityAndTown, '');
		// throw new Exception("パターン外:".$cityAndTown);
	}

	private function splitMatches($matches) {
		if ($this->isEmpty($matches) || count($matches) < 2) {
			throw new Exception('Can not Split');
		}
		$shiku = $matches[1];
		$chou = (isset($matches[2]) && !$this->isEmpty($matches[2]))
		? $matches[2] : '';

		/**
		 * 市区に同一の区切りもじが複数回入る場合、オカシイ可能性がある
		 * @var string $doubleChar
		 */
		$doubleChar = false;
		if ($this->checkDouble($shiku, '町')) {
			$doubleChar = '町';
		}
		if (!$doubleChar && $this->checkDouble($shiku, '市')) {
			$doubleChar = '市';
		}
		if (!$doubleChar && $this->checkDouble($shiku, '村')) {
			$doubleChar = '村';
		}
		if ($doubleChar) {
			list($shiku, $chou) = $this->repairDouble($shiku, $chou, $doubleChar);
		}
		return array($shiku, $chou);
	}

	public function repairDouble($shiku, $chou, $doubleChar) {
		$nShiku = $shiku;
		$nChou = $chou;
		$tmp = explode($doubleChar, $shiku);
		if (count($tmp) > 2) { // doubleが２つ以上
			$first = self::ERROR_MARKAR.array_shift($tmp);
			$nShiku = $first.$doubleChar;
			$nChou = implode($doubleChar, $tmp).$nChou;
		}
		return array($nShiku, $nChou);

	}

	public function checkDouble($str, $target) {
		return (mb_substr_count($str, $target) > 1);
	}

	/**
	 * 物件名の後ろに数字と号、号室があれば部屋番号とみなす。
	 * 戻りはarray(マンション名、部屋番号)
	 * @param unknown $mansionName
	 * @return unknown[]|string[]
	 */
	public function splitRoomNo($mansionName, $withGousitu = true, $withHaihun = true) {
		$mName = $mansionName;
		$roomNo = '';
		$status = $matches = false;

		// どちらも無しはありえない
		if (!$withGousitu && !$withHaihun) {
			throw new Exception("Developer Error!!!");
		}

		// XXX号室対応
		if ($withGousitu) {
			list($status, $matches) = $this->splitRoomNoWithGousitsu($mansionName);
		}
		// XXX-XXX対応
		// 号室が見つからなくて且つ、ハイフンでも調査
		if (!$status && $withHaihun) {
			list($status, $matches) = $this->splitRoomNoWithHaihun($mansionName);
		}
		// 返却データ作成
		if ($status) {
			if (count($matches) > 2) {
				$mName = preg_replace('/'.self::ALL_HAIHUN_WITHOUT_CHOON.'$/u', '', $matches[1]);
				$roomNo = $matches[2];
			}
		}
		return array($mName, $roomNo);
	}

	public function splitRoomNoWithGousitsu($mansionName) {
		$pattern = '/^(.+[^0-9０-９]+)([0-9０-９]+)[号|室]+$/u';
		$status = preg_match($pattern, $mansionName, $matches);
		// $this->logger->cLog($matches);
		return array($status, $matches);
	}

	public function splitRoomNoWithHaihun($mansionName) {
		$pattern = '/^(.+)'.self::ALL_HAIHUN_WITHOUT_CHOON.'+([0-9０-９]{1,4})[号|号室]*$/u';
		$status = preg_match($pattern, $mansionName, $matches);
		return array($status, $matches);
	}

	/**
	 * 東京都杉並区上高井戸２丁目(9-14付近)
	 * を
	 * 東京都杉並区上高井戸２丁目-9
	 * にして返す
	 * (*付近)がなければそのまま返す
	 * @param unknown $chibanAddress
	 */
	public function modifyFukinAddress($fukinAddress) {
		// (*付近)が入っていなければそのまま
		if (!preg_match('/^.+\(.+付近\).*$/u', $fukinAddress)) {
			return $fukinAddress;
		}
		// 分解して繋げて返す
		$oya = $oyaban = '';
		if (preg_match('/^(.*)\(([0-9０-９]+)\-*.*付近\).*$/u', $fukinAddress, $matches)) {
			$oya = $matches[1];
			$oyaban = $matches[2];
		}
		return $oya.$oyaban;
	}


	/**
	 * 政令指定都市用配列
	 * @param unknown $pref
	 * @param unknown $city
	 * @param unknown $town
	 * @return unknown[]
	 */
	private function createGovOrdinanceDesignatedCityStruct(
			$pref, $city, $town
			) {
		return array(
				self::IDX_PARTS_GOV_PREF => $pref,
				self::IDX_PARTS_GOV_CITY => $city,
				self::IDX_PARTS_GOV_TOWN => $town,
		);
	}

	/**
	 * 政令指定都市データ
	 * @return string[][][]
	 */
	private function createGovOrdinanceDesignatedCity() {
		// 	Flag of Sapporo, Hokkaido.svg 札幌市	北海道	地図	1972年（昭和47年）4月1日	全10区： 中央区・北区・東区・白石区・豊平区・南区・西区・厚別区・手稲区・清田区
		$cities = array(
				'北海道' => array(
						'札幌市' => array(
							'中央区','北区','東区','白石区','豊平区','南区','西区','厚別区','手稲区','清田区'
						)
				),
				'宮城県' => array(
						'仙台市' => array(
								'青葉区','宮城野区','若林区','太白区','泉区'
						)
				),
				'埼玉県' => array(
						'さいたま市' => array(
								'西区','北区','大宮区','見沼区','中央区','桜区','浦和区','南区','緑区','岩槻区'
						)
				),
				'千葉県' => array(
						'千葉市' => array(
								'中央区','花見川区','稲毛区','若葉区','緑区','美浜区'
						)
				),
				'神奈川県' => array(
						'横浜市' => array(
								'鶴見区','神奈川区','西区','中区','南区','港南区','保土ケ谷区','旭区','磯子区','金沢区','港北区','緑区','青葉区','都筑区','戸塚区','栄区','泉区','瀬谷区'
						),
						'川崎市' => array(
								'川崎区','幸区','中原区','高津区','宮前区','多摩区','麻生区'
						),
						'相模原市' => array(
								'緑区','中央区','南区'
						)
				),
				'新潟県' => array(
						'新潟市' => array(
								'北区','東区','中央区','江南区','秋葉区','南区','西区','西蒲区'
						)
				),
				'静岡県' => array(
						'静岡市' => array(
								'葵区','駿河区','清水区'
						),
						'浜松市' => array(
								'中区','東区','西区','南区','北区','浜北区','天竜区'
						)
				),
				'愛知県' => array(
						'名古屋市' => array(
								'千種区','東区','北区','西区','中村区','中区','昭和区','瑞穂区','熱田区','中川区','港区','南区','守山区','緑区','名東区','天白区'
						)
				),
				'京都府' => array(
						'京都市' => array(
								'北区','上京区','左京区','中京区','東山区','下京区','南区','右京区','伏見区','山科区','西京区'
						)

				),
				'大阪府' => array(
						'大阪市' => array(
								'都島区','福島区','此花区','西区','港区','大正区','天王寺区','浪速区','西淀川区','東淀川区','東成区','生野区','旭区','城東区','阿倍野区','住吉区','東住吉区','西成区','淀川区','鶴見区','住之江区','平野区','北区','中央区'
						),
						'堺市' => array(
								'堺区','中区','東区','西区','南区','北区','美原区'
						)
				),
				'兵庫県' => array(
						'神戸市' => array(
								'東灘区','灘区','兵庫区','長田区','須磨区','垂水区','北区','中央区','西区'
						)
				),
				'岡山県' => array(
						'岡山市' => array(
								'北区','中区','東区','南区'
						)
 				),
				'広島県' => array(
						'広島市' => array(
								'中区','東区','南区','西区','安佐南区','安佐北区','安芸区','佐伯区'
						)
				),
				'福岡県' => array(
						'北九州市' => array(
								'門司区','若松区','戸畑区','小倉北区','小倉南区','八幡東区','八幡西区'
						),
						'福岡市' => array(
								'東区','博多区','中央区','南区','西区','城南区','早良区'
						)
				),
				'熊本県' => array(
						'熊本市' => array(
								'中央区','東区','西区','南区','北区'
						)
				)
		);
		return $cities;
	}

	/**
	 * 海外住所かどうか
	 * @param unknown $mStruct
	 * @return boolean
	 */
	public function isForeignStruct($mStruct) {
		$status = $mStruct[self::IDX_ADDRESS_STATUS];
		return $status === ST_ADDR_FOREIGN;
	}

	public function isJapanStruct($mStruct) {
		$status = $mStruct[self::IDX_ADDRESS_STATUS];
		return $status === ST_ADDR_JAPAN;
	}


	/**
	 * 海外の住所か判別する。
	 * @param unknown $address
	 * @return boolean
	 */
	public function isForeignCountry($address) {

		// カナを含む市区
		$kanaCity = $this->includedKanaCityOfJapan();
		foreach ($kanaCity as $pattern) {
			if (preg_match($pattern, $address)) {
				return false;
			}
		}
		$fCountry = $this->foreginCountryPatterns();
		// 漢字の国名で始まる海外
		foreach ($fCountry as $pattern) {
			if (preg_match($pattern, $address)) {
				return true;
			}
		}
		// 日本語で最初の8文字以内にカタカナが二文字以上存在する −＞ 海外
		// 一旦、全角カナへ変換
		$aTmp = mb_convert_kana($address, 'K', self::ENCODE);
		$first5moji = mb_substr($aTmp, 0, self::FOREIGN_EVAL_LENGTH);
		return preg_match('/^.*[ァ-ヶー].*[ァ-ヶー].+$/u', $first5moji);

	}

	public function includedKanaCityOfJapan() {
		return array(
				'/青[ケヶｹ]島村/u',
				'/上北郡六[ケヶｹ]所村/u',
				'/下高井郡山[ノﾉ]内町/u',
				'/不破郡関[ケヶｹ]原町/u',
				'/刈田郡七[ケヶｹ]宿町/u',
				'/東津軽郡外[ケヶｹ]浜町/u',
				'/龍[ケヶｹ]崎市/u',
				'/袖[ケヶｹ]浦市/u',
				'/隠岐郡西[ノﾉ]島町/u',
				'/駒[ケヶｹ]根市/u',
				'/西臼杵郡五[ケヶｹ]瀬町/u',
				'/鳩[ケヶｹ]谷市/u',
				'/鎌[ケヶｹ]谷市/u',
				'/茅[ケヶｹ]崎市/u',
				'/虻田郡[ニﾆ][セｾ][コｺ]町/u',
				'/神埼郡吉野[ケヶｹ]里町/u',
				'/檜山郡上[ノﾉ]国町/u',
				'/西津軽郡鰺[ケヶｹ]沢町/u',
				'/鶴[ケヶｹ]島市/u',
				'/横浜市保土[ケヶｹ]谷区/u',
				'/宮城郡七[ケヶｹ]浜町/u',
				'/南[アｱ][ルﾙ][プﾌﾟ][スｽ]市/u',
				'/胆沢郡金[ケヶｹ]崎町/u',
				);

	}

	/**
	 * 海外で漢字で始まる国
	 * @return string[]
	 */
	public function foreginCountryPatterns() {
		$patterns = array(
			'/^中華人民共和国.+/u',
			'/^中国.+/u',
			'/^台湾.+/u',
			'/^香港.+/u',
			'/^英国.+/u',
			'/^英連合王国.+/u',
			'/^大韓民国.+/u',
			'/^米国.+/u',
		);
		return $patterns;
	}

	/**
	 * 手作業が必要な住所かどうか？
	 * @param unknown $address
	 * @return boolean
	 */
	public function isManually($address) {

		return false;


		$patterns = $this->manuallyAddressPatterns();
		foreach ($patterns as $pat) {
			if (preg_match($pat, $address)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * 手作業の必要なパターン
	 */
	public function manuallyAddressPatterns() {
		$patterns = array(
				'/^.*[一二三四五六七八九十壱弐参拾百千万萬億兆〇0-9０-９]+条.*[一二三四五六七八九十壱弐参拾百千万萬億兆〇0-9０-９]+丁目.*/u'
		);
		return $patterns;
	}

	public function isYohaku($address) {
		$pattern = '/^余白$/u';
		return preg_match($pattern, trim($address));
	}


}

