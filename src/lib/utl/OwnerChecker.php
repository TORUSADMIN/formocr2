<?php

require_once(UTIL_DIR . '/trait/EmptyTrait.php');
require_once(UTIL_DIR . '/Logger.php');
require_once(UTIL_DIR . '/AddressModifier.php');
require_once(UTIL_DIR . '/PersonalNameModifier.php');
require_once(UTIL_DIR . '/AddressConfig.php');
require_once(MODEL_DIR . '/OwnerReport1.php');
require_once(MODEL_DIR . '/OwnerReport2.php');

/**
 * 所有者のリストを保持し、そのリスト内に所有者が存在するか確認するためのクラス
 * @author moroishi
 *
 */
class OwnerChecker {
	
	use EmptyTrait;
	private $logger;
	
	private $pNameMod;
	private $addrMod;
	
	private $ownerBuffers;
	
	private $useOwnerPref;
	
	// 所有者事項のタイプ
	const OR_TYPE1 = 1;
	const OR_TYPE2 = 2;
	
	const IDX_OR_OWNERS = '_owners';
	const IDX_OR_OWNER_ADDR1 = '_ownerAddr1';
	const IDX_OR_OWNER_ADDR2 = '_ownerAddr2';
	const IDX_OR_OWNER_ADDR3 = '_ownerAddr3';
	const IDX_OR_OWNER_PREF = '_ownerPref';
	
	const DUP_TH = 1;
	
	/**
	 * 
	 * @param string $useOwnerPref 所有者住所の都道府県も使って突き合せをするか？
	 */
	public function __construct($useOwnerPref = false) {
		$this->addrMod = new AddressModifier();
		$this->pNameMod = new PersonalNameModifier();
		$this->ownerBuffers = false;
		$this->useOwnerPref = $useOwnerPref;
		
		$this->logger = new Logger();
	}
	
	///////////////////////////////////////////////
	// 公開メソッド
	
	/**
	 * バッファの構築
	 * @param unknown $csvLines
	 * @param unknown $reportType
	 * @throws Exception
	 */
	public function buildOwnerBuffers($csvLines, $reportType) {
		if ($this->isEmpty($csvLines)) {
			throw new Exception("DATA IS EMPTY");
		}
		$lineIdx = ($reportType === self::OR_TYPE1)
						? $this->createOwnerIndexForType1() : $this->createOwnerIndexForType2();
		// １行づつ処理
		foreach ($csvLines as $line) {
			if ($this->isEmpty($line)) {
				continue;
			}
			$address = $this->getOwnerAddress($lineIdx, $line);
			$owners = $line[$lineIdx[self::IDX_OR_OWNERS]]; // 複数の所有者名
			$this->addBuffer($owners, $address);
		}
		
		// $this->logger->cLog($this->ownerBuffers);
	}
	
	/**
	 * 所有者と所有者住所で見て、全体のデータの中での出現回数を返す。
	 * 結果「1」なら、1回しか出ていない → 重複していない。
	 * @param unknown $ownerName
	 * @param unknown $address
	 * @throws Exception
	 * @return boolean|number
	 */
	public function countSameOwner($address, $ownerName) {
		if (!$this->ownerBuffers) {
			throw new Exception("Owner Buffer is not Initialized!!!");
		}
		// 住所の正規化
		$addrTmp = $this->addrMod->normalizeAddress($address);
		$normAddress = $addrTmp[AddressModifier::IDX_CHANGED_ADDRESS];
		
		// 所有者名の正規化
		if (is_array($ownerName) && isset($ownerName[PersonalNameModifier::IDX_LAST])) {
			$normOwnerName = $this->normalizeOwnerName($ownerName);
		} else {
			$normOwnerName = $this->normalizeOwnerName($this->pNameMod->normalizePersonalName($ownerName));
		}
		
		if (isset($this->ownerBuffers[$normAddress])) {
			if (isset($this->ownerBuffers[$normAddress][$normOwnerName])) {
				return $this->ownerBuffers[$normAddress][$normOwnerName];
			}
		}
		return false; // ここに来るのはおかしい。
	}
	
	
	///////////////////////////////////////////////
	// 内部メソッド
	
	/**
	 * バッファへ追加
	 * @param unknown $owners 複数の所有者名
	 * @param unknown $address 所有者住所
	 */
	private function addBuffer($owners, $address) {
		if ($this->isEmpty($owners) || $this->isEmpty($address)) {
			throw new Exception("Owner or Owner Address is Empty");
		}
		$normAddress = $this->normalizeAddress($address);
		// 住所の正規化
		if (!isset($this->ownerBuffers[$normAddress])) {
			$this->ownerBuffers[$normAddress] = array(); // 住所で初期化
		}
		
		// 所有者名の分解
		$ownersStruct = $this->pNameMod->splitPersonalNames($owners);
		foreach ($ownersStruct as $idx => $ownerStruct) {
			$normOwner = $this->normalizeOwnerName($ownerStruct);
			if (isset($this->ownerBuffers[$normAddress][$normOwner])) {
				$this->ownerBuffers[$normAddress][$normOwner] += 1; // 存在していれば、インクリメント
			} else {
				$this->ownerBuffers[$normAddress][$normOwner] = self::DUP_TH; // 存在していなければ1で初期化。（１回現れた）
			}
		}
	}
	
	private function normalizeAddress($address) {
		$addrTmp = $this->addrMod->normalizeAddress($address);
		$normAddress = $addrTmp[AddressModifier::IDX_CHANGED_ADDRESS];
		return $normAddress;
	}
	
	private function normalizeOwnerName($ownerStruct) {
		// 所有者名をつなげる
		$owner = $ownerStruct[PersonalNameModifier::IDX_LAST].$ownerStruct[PersonalNameModifier::IDX_FIRST];
		return $owner;
	}
	
	/**
	 * 解析データから住所関連の列を取り出し、つなげて返す
	 * @param unknown $idxes
	 * @param unknown $line
	 * @return string
	 */
	private function getOwnerAddress($idxes, $line) {
		if ($this->useOwnerPref) {
			$pref = (isset($line[$idxes[self::IDX_OR_OWNER_PREF]])) ? $line[$idxes[self::IDX_OR_OWNER_PREF]] : '';
		} else {
			$pref = '';
		}
		$addr1 = (isset($line[$idxes[self::IDX_OR_OWNER_ADDR1]])) ? $line[$idxes[self::IDX_OR_OWNER_ADDR1]] : '';
		$addr2 = (isset($line[$idxes[self::IDX_OR_OWNER_ADDR2]])) ? $line[$idxes[self::IDX_OR_OWNER_ADDR2]] : '';
		$addr3 = (isset($line[$idxes[self::IDX_OR_OWNER_ADDR3]])) ? $line[$idxes[self::IDX_OR_OWNER_ADDR3]] : '';
		
		return $pref.$addr1.$addr2.$addr3;
	}
	
	/**
	 * 所有者事項解析フォーマットタイプ１の配列インデックス構築
	 * @return number[]
	 */
	private function createOwnerIndexForType1() {
		return array(
			self::IDX_OR_OWNERS => OwnerReport1::IDX_OR1_OWNER_NAMES
			,self::IDX_OR_OWNER_PREF => OwnerReport1::IDX_OR1_PREF
			,self::IDX_OR_OWNER_ADDR1 => OwnerReport1::IDX_OR1_CITY
			,self::IDX_OR_OWNER_ADDR2 => OwnerReport1::IDX_OR1_UNDER_CITY
			,self::IDX_OR_OWNER_ADDR3 => OwnerReport1::IDX_OR1_BUILDING_NAME
		);
	}
	
	/**
	 * 所有者事項解析フォーマットタイプ１の配列インデックス構築
	 */
	private function createOwnerIndexForType2() {
	}
}