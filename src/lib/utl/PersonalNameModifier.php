<?php
/**
 * 人名に関わるユーティリティをまとめる
 * もちろん会社名にも対応します。
 *
 * 高橋　俊之 様　高橋　惠美子 様　高橋　邦明
 *
 */
require_once(LIB_DIR . '/utl/trait/EmptyTrait.php');
require_once(LIB_DIR . '/utl/trait/TrimTrait.php');
require_once(LIB_DIR . '/utl/Logger.php');

class PersonalNameModifier {
	use EmptyTrait, TrimTrait;

	private $logger;

	const HAN_SPACE = ' ';
	const ZEN_SPACE = '　';

	const HONO_SAMA_TYPE = '10';
	const HONO_SAMA_VALUE = '様';

	const HONO_ONCHU_TYPE = '20';
	const HONO_ONCHU_VALUE = '御中';

	const HONO_UNKNOWN_TYPE = '99';
	const HONO_UNKNOWN_VALUE = '御中';

	const MARKER = '#';

	const IDX_LAST = 'last';
	const IDX_FIRST = 'first';
	const IDX_HONO = 'hono';

	public function __construct() {
		$this->logger = new Logger();
	}

	/**
	 * 人名を分ける
	 *
	 * 高橋　俊之 様　高橋　惠美子 様　金魂巻
	 *  →[0]
	 *    →['hono']10
	 *    →['last']高橋
	 *    →['first']俊之
	 *  →[1]
	 *    →['hono']10
	 *    →['last']高橋
	 *    →['first']恵美子
	 *  →[2]
	 *    →['hono']99
	 *    →['last']金魂巻
	 *    →['first']
	 */

	/**
	 * 人名を「姓」「名」「敬称」の配列に変換。敬称が判別不明なものは、「御中？」とする
	 * @param unknown $str
	 * @return unknown
	 */
	public function splitPersonalNames($str) {
		$tmp = $this->trimEmspace($str);
		if ($this->isEmpty($tmp)) {
			return $str;
		}
		$tmp = $tmp.self::MARKER.self::HONO_UNKNOWN_TYPE; // １行めの最後は常に不明

		$allPersons = array();

		///////////////
		// ' 様　'で分割
		$samas = $this->explodeSama($tmp);
		// 様で分割したので、行末に「様」マーカーをセット
		$marked = $this->addMarker($samas, self::HONO_SAMA_TYPE);

		foreach ($marked as $n => $s) {
			///////////////
			// ' 御中　'ですべての行を分割
			$r = $this->explodeOnchu($s);
			// 御中で分割したので行末に「御中」マーカーをセット
			$marked2 = $this->addMarker($r, self::HONO_ONCHU_TYPE);
			$allPersons = array_merge($allPersons, $marked2);
		}
		// $this->logger->debugLog($allPersons);

		// 姓、名、敬称の配列に変換する
		$allPersonalNames = $this->normalizePersonalNames($allPersons);

		return $allPersonalNames;
	}

	/**
	 * 姓、名、敬称に分割
	 * @param unknown $personalNames
	 */
	public function normalizePersonalNames($personalNames) {
		$result = array();
		foreach ($personalNames as $n => $personalName) {
			$result[] = $this->normalizePersonalName($personalName);
		}
		return $result;
	}

	/**
	 * フルネーム＋敬称のデータを「姓」「名」「敬称」の配列へ変換して返す
	 * @param unknown $personalName
	 * @return unknown|unknown[]
	 */
	public function normalizePersonalName($personalName) {
		if (empty($personalName)) {
			return $personalName;
		}
		// 敬称マーカーの取り出し
		$exName = explode(self::MARKER, $personalName);
		$honoType = self::HONO_UNKNOWN_TYPE;
		if (count($exName) == 2) {
			$honoType = $exName[1];
		}

		// 姓名分割
		$fullName = $exName[0];
		$splitName = explode(self::ZEN_SPACE, $fullName);

		$first = $last = $hono = null;
		if (count($splitName) == 2) {
			// 敬称不明な場合
			if ($honoType === self::HONO_UNKNOWN_TYPE) {
				$honoType = self::HONO_SAMA_TYPE;
			}
			$last = $splitName[0];
			$first = $splitName[1];
		} else {
			// １個の場合も３個以上もあるので、姓にフルネームを入れる
			$last = $fullName;
		}
		$hono = $this->getHonorificValue($honoType);

		return $this->createPersonalArray($last, $first, $hono);
	}

	/**
	 * 個人名を標準フォーマットの文字列にして返す
	 * @param unknown $pStruct
	 * @param string $withHono
	 * @return string|string|unknown
	 */
	public function getPersonalName($pStruct, $withHono = true) {
		if ($this->isEmpty($pStruct) || !is_array($pStruct)) {
			return '';
		}

		if ($this->isEmpty($pStruct[self::IDX_FIRST])) {
			$personalName = $pStruct[self::IDX_LAST];
		} else {
			$personalName = $pStruct[self::IDX_LAST].self::ZEN_SPACE.$pStruct[self::IDX_FIRST];
		}

		return ($withHono) ?
				$personalName.self::HAN_SPACE.$pStruct[self::IDX_HONO]
				: $personalName;
	}

	public function getPersonalNameNoSpace($pStruct, $withHono = true) {
		$personalName = $this->getPersonalName($pStruct, $withHono);
		return str_replace(self::ZEN_SPACE, '', $personalName);
	}

	/**
	 * 氏名を取り出す
	 * @param unknown $pStruct
	 * @param string $delimiter 姓名を分割する場合は指定する。
	 * @param string $withHono 敬称が必要な場合は指定する。
	 */
	public function getShimei($pStruct, $delimiter = '', $withHono = true) {
		if ($this->isEmpty($pStruct) || !isset($pStruct[self::IDX_LAST])) {
			return '';
		}
		$sei = $this->getSei($pStruct); // 姓
		$mei = $this->getMei($pStruct); // 名

		// 氏名構築
		$shimei = $sei.$delimiter.$mei;
		if ($withHono) {
			$shimei .= $this->getHono($pStruct); // 敬称
		}
		return $shimei;
	}

	/**
	 * 配列から姓のみ取り出す
	 * @param unknown $pStruct
	 * @return string|string|unknown
	 */
	public function getSei($pStruct) {
		return $this->getParts($pStruct, self::IDX_LAST);
	}

	/**
	 * 配列から名のみ取り出す
	 * @param unknown $pStruct
	 * @return string|string|unknown
	 */
	public function getMei($pStruct) {
		return $this->getParts($pStruct, self::IDX_FIRST);
	}

	/**
	 * 敬称を取り出す
	 * @param unknown $pStruct
	 * @return string|unknown
	 */
	public function getHono($pStruct) {
		return $this->getParts($pStruct, self::IDX_HONO);
	}

	public function getParts($pStruct, $parts) {
		if ($this->isEmpty($pStruct)) {
			return '';
		}
		return (isset($pStruct[$parts])) ? $pStruct[$parts] : '';
	}


	/**
	 * 姓名用配列の作成
	 * @param unknown $last
	 * @param unknown $first
	 * @param unknown $hono
	 * @return unknown[]
	 */
	private function createPersonalArray($last, $first, $hono) {
		return array(
				self::IDX_LAST => $last,
				self::IDX_FIRST => $first,
				self::IDX_HONO => $hono,
		);
	}

	/**
	 * 様、御中、？を返す
	 * @param unknown $honoType
	 */
	private function getHonorificValue($honoType) {
		switch ($honoType) {
			case self::HONO_SAMA_TYPE:
				return self::HONO_SAMA_VALUE;
			case self::HONO_ONCHU_TYPE:
				return self::HONO_ONCHU_VALUE;
			default:
				return self::HONO_UNKNOWN_VALUE;
		}
	}
	private function addMarker($ary, $marker) {
		$result = array();
		foreach ($ary as $n => $s) {
			$b = strpos($s, self::MARKER);
			if ($b === FALSE) {
				$result[] = $s.self::MARKER.$marker;
			} else {
				$result[] = $s;
			}
			// $this->logger->debugLog("B = " . $b);
		}
		return $result;
	}

	/**
	 * 様で分割
	 * @param unknown $str
	 */
	public function explodeSama($str) {
		$pat = self::HAN_SPACE . self::HONO_SAMA_VALUE . self::ZEN_SPACE;
		return $this->explodeMain($pat, $str);
	}

	/**
	 * 御中で分割
	 * @param unknown $str
	 */
	public function explodeOnchu($str) {
		$pat = self::HAN_SPACE . self::HONO_ONCHU_VALUE . self::ZEN_SPACE;
		return $this->explodeMain($pat, $str);
	}

	/**
	 * 指定のパターンで分割
	 * @param unknown $pat
	 * @param unknown $str
	 */
	public function explodeMain($pat, $str) {
		$tmp = $this->trimEmspace($str);
		return explode($pat, $tmp);
	}


	/**
	 * 複数人存在するか？
	 * （半角スペースが入っていれば、複数人の指定）
	 * @param unknown $str
	 * @return mixed
	 */
	public function isMoreThanOne($str) {
		$tmp = trim($str);
		return (strpos($tmp, self::HAN_SPACE) !== FALSE);
	}

	/**
	 * 「様」が含まれるかチェック
	 * @param unknown $str
	 * @return boolean
	 */
	public function isIncludeSama($str) {
		$pat = self::HAN_SPACE . self::HONO_SAMA_VALUE . self::ZEN_SPACE;
		return $this->isIncludeMain($pat, $str);
	}

	/**
	 * 「御中」が含まれるかチェック
	 * @param unknown $str
	 * @return boolean
	 */
	public function isIncludeOnchu($str) {
		$pat = self::HAN_SPACE . self::HONO_ONCHU_VALUE . self::ZEN_SPACE;
		return $this->isIncludeMain($pat, $str);
	}

	public function isIncludeMain($pat, $str) {
		$tmp = $this->trimEmspace($str);
		return (strpos($tmp, $pat) !== FALSE);
	}



}
