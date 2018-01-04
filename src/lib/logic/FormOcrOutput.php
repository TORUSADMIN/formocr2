<?php

require_once(UTIL_DIR . '/trait/EmptyTrait.php');
require_once(UTIL_DIR . '/Logger.php');
require_once(UTIL_DIR . '/AddressModifier.php');
require_once(UTIL_DIR . '/AddressConfig.php');
require_once(__DIR__ . '/../model/FormOcrReport1.php');
require_once(__DIR__ . '/../utl/EstateReceipt.php');



/**
 * Created by FormOCR.
 * Date: 2017/05/29
 * Time: 15:25
 * @property FormOcrReport1 $report1
 */
class FormOcrOutput
{

    use EmptyTrait;
    private $logger;
    private $addrMod;
    private $report1;

    private $report1CsvPath;
    private $outputDirPath;

    private $outputCsv;

    const NUMERIC_PATTERN ='/[0-9]+/u';

    public function __construct($report1Csv, $outputDir)
    {
        // 必要なクラスのインスタンス
        $this->logger = new Logger();
        $this->addrMod = new AddressModifier();

        $this->report1CsvPath = $report1Csv;
        $this->outputDirPath = $outputDir;

        // CSVファイル読み込み
        $this->init();
    }

    /**
     * CSVクラスのデータ取得
     */
    public function init()
    {
        $this->report1 = new FormOcrReport1($this->report1CsvPath, CsvModelBase::CSV_DELIMITER);
        $this->setupOutputFileName();
    }

    /**
     * OUTPUTファイル
     */
    public function setupOutputFileName() {
        $paths = pathinfo($this->report1CsvPath);
        $filePath = $paths['filename'] . "_output_" . date('YmdHis') . ".csv";
        $this->outputCsv = $filePath;
    }

    /**
     * FormOCRで出力したCSVファイルを読み込み、エラーチェック出力する
     */
    public function processing() {

        $this->logger->cLog("=== Statring output From " . $this->report1CsvPath );
        $this->logger->cLog('==================================================');

        // レポートCSV取得
        $hasHeader = false;
        $csv = $this->report1->getCsvBodyData($hasHeader);
        //$csv = $this->report1->getCsvBodyDataEx();

        // 出力用
        $outputBuffer = array();
        $oReceiptNo="";
        $countLine = 0;



        foreach ($csv as $line) {
            if (count($line) == 1){
                continue;
            }

            //$this->logger->cLog($key);
            //$this->logger->cLog($line);

            if ($hasHeader == true){
                if($countLine==0){
                    $countLine = $countLine + 1;
                    continue;
                }
            }
            //初期化
            $wEstateReceipt = new EstateReceipt();
            $preReceiptNo = $oReceiptNo; //1つ前の受付番号
            $oReceiptNo = "";
            $oReceiptNoErr = "";
            $oReceiptDate = "";
            $oReceiptSeq = "";
            $oReceiptSeqErr = "";
            $oGroup = "";
            $oGroupErr = "";
            $oBukkenAddr = "";
            $oBukkenAddrErr ="";
            $oPurpose = "";
            $oPurposeErr = "";
            $wBukkenAddr = "";
            $oCity ="";
            $oChome1 = "";
            $oChome2 = "";
            $oLotnumber = "";
            $oSotofude = "";
            $oPref = "";

            //受付番号
            $oReceiptNo = $this->report1->getReceiptNo($line);
			//$this->logger->cLog($oReceiptNo);

            //都道府県を取得
            $oPref = $this->report1->getPrefecture($line);


            if($oReceiptNo!== null or $oReceiptNo !== "") {
            	//$this->logger->cLog($oReceiptNo);
                $oReceiptNo = $wEstateReceipt->getReceiptNo($oReceiptNo);
                //$this->logger->cLog($oReceiptNo);
            }
            //受付番号エラー
            $oReceiptNoErr = $this->report1->getReceiptNo_Err($line);
            if ($wEstateReceipt->isValidReceiptNo($oReceiptNo) == false) {
                $oReceiptNoErr = '★' . $oReceiptNoErr;
            } else {
                if (is_null($preReceiptNo) == false) {
                    //１つ前の受付番号
                    //第と号などを取って数値のみにする
                    $oPreReceiptNoNum = mb_convert_kana($preReceiptNo,'a');
                    $matchPretmp = array();
                    if(preg_match(self::NUMERIC_PATTERN, $oPreReceiptNoNum, $matchPretmp)){
                    	$oPreReceiptNoNum = $matchPretmp[0];
                    }
                    //今の受付番号
                    $oReceiptNo = $wEstateReceipt->getReceiptNo($oReceiptNo);
                    //第と号などを取って数値のみにする
                    $oReceiptNoNum = mb_convert_kana($oReceiptNo,'a');
                    //$matchtmp = '';
                    if(preg_match(self::NUMERIC_PATTERN, $oReceiptNoNum, $matchtmp)){
                    	$oReceiptNoNum = $matchtmp[0];
                    }
                    //桁数が大きくなったら★をつける
                    if (strlen($oPreReceiptNoNum) < strlen($oReceiptNoNum)){
                        $oReceiptNoErr = '★' . $oReceiptNoErr;
                    }
                    //前の数値より小さかったら★をつける
                    if (strlen($oPreReceiptNoNum) == strlen($oReceiptNoNum) &&$oPreReceiptNoNum > $oReceiptNoNum){
                        $oReceiptNoErr = '★' . $oReceiptNoErr;
                    }
                }
            };
            //受付日
            $oReceiptDateTmp = $this->report1->getReceiptDate($line);
            //$this->logger->cLog($oReceiptDateTmp);

            $oReceiptDate = mb_substr($oReceiptDateTmp,0, mb_strpos($oReceiptDateTmp,"日")+1);

			/*
			 *ファイル名から月を取得し、受け日の月を変更する
			 *埼玉県_越谷_201711 --> 11取得
			 *目的:111月--->11月
			 * */
			$fileName = $this->report1->getFilename($line);
			//$this->logger->cLog($fileName);//埼玉県_越谷_201711
			$fileNameTmp = explode('_', $fileName);
			//$this->logger->cLog($fileNameTmp);//201711 201708...
			if(count($fileNameTmp) === 3){
				$yearAndMonth = $fileNameTmp[2];
				$oReceiptMonth = substr($yearAndMonth, -2);//11 08 01...
				if(mb_strpos($oReceiptMonth, '0') === 0){
					$oReceiptMonth = str_replace('0', '', $oReceiptMonth);
				}
			}
			if(!empty($oReceiptMonth)){
				$oReceiptDate = $wEstateReceipt->changeReceiptMonth($oReceiptMonth, $oReceiptDate);
			}

            //受付日エラー
            $oReceiptDateErr = $this->report1->getReceiptDate_Err($line);
            if ($wEstateReceipt->isValidReceiptDate($oReceiptDate) === false) {
                $oReceiptDateErr = '★' . $oReceiptDateErr;
            } else {
                $oReceiptDate = $wEstateReceipt->getReceiptDate($oReceiptDate);
            }

            //$this->logger->cLog($oReceiptDate);

            //順序（単独）、（連続）、（連先）
            $oReceiptSeq = $this->report1->getReceiptSeq($line);
            //順序（単独）、（連続）、（連先）エラー
            $oReceiptSeqErr = $this->report1->getReceiptSeq_Err($line);
            if ($wEstateReceipt->isVaildReceiptSEQ($oReceiptSeq) == false) {
                $oReceiptSeqErr = '★' . $oReceiptSeqErr;
            } else {
                $oReceiptSeq = $wEstateReceipt->getReceiptSEQ($oReceiptSeq);
            }
            //グループ（土地、建物）
            $oGroup = $this->report1->getGroup($line);
            //グループ（土地、建物）エラー
            $oGroupErr = $this->report1->getGroup_Err($line);
            if ($wEstateReceipt->isVaildGroup($oGroup) == false) {
                $oGroupErr = '★' . $oGroupErr;
            } else {
                $oGroup = $wEstateReceipt->getGroup($oGroup);
            }
            //地番
            $oBukkenAddr = $this->report1->getBukkenAddr($line);
            //地番エラー
            $oBukkenAddrErr = $this->report1->getBukkenAddr_Err($line);

            //都道府県を取得
            $oPref = $this->report1->getPrefecture($line);

            if ($oBukkenAddr<>"") {

				//マーク付ける前にチェックする
            	$city_master_array = parse_ini_file( "../lib/init/citymaster.ini",false);
            	$two_chome_array = parse_ini_file("../lib/init/twocyome_master.ini", false);
            	$oBukkenAddr = $wEstateReceipt->AddressChecker($oPref, $two_chome_array, $oBukkenAddr, $city_master_array);

                $oAddress = $oPref.$oBukkenAddr;            // 住所変換（数字は全角、丁目はハイフン、ハイフンも全角）
                $addrConfigForChiban = $this->createAddressConfigForChiban();
                $oAddrTmp1 = $this->addrMod->changeAddress($oAddress, $addrConfigForChiban);

                $cityAndTown = $this->addrMod->getParts($oAddrTmp1,AddressModifier::IDX_PARTS_CITY);
                $oAddrTmp = $this->addrMod->splitSikugun($oPref,$cityAndTown);
                $oCity = str_replace('★','',$oAddrTmp[0]);
                $oChome1 = str_replace('★','',$oAddrTmp[1]);
                $oChome2 = $this->addrMod->getParts($oAddrTmp1,AddressModifier::IDX_PARTS_CHOME);

                //枝番以降を抽出
                $oAfter = mb_substr($oBukkenAddr,mb_strlen($cityAndTown.$oChome2), mb_strlen($oBukkenAddr)-mb_strlen($cityAndTown.$oChome2));
                //外筆を除いて枝番と外筆を作る
                if(preg_match('/外/',$oAfter)) {
                    //外筆
                    $oSotofude = $wEstateReceipt->getSotofude($oAfter);
                    if (mb_strpos($oAfter,"外")>0){
                        $oLotnumber = mb_substr($oAfter,0,mb_strpos($oAfter,"外"));
                    }
                } else {
                    if(mb_strlen($oAfter)>0){
                        $oLotnumber = $oAfter;
                    }
                }
                //$oLotnumber = $this->addrMod->getParts($oAddrTmp1,AddressModifier::IDX_PARTS_AFTER);
                //$oSotofude = $this->addrMod->getParts($oAddrTmp1,AddressModifier::IDX_PARTS_MNAME);

                if ( $wEstateReceipt->isValidCity($oCity) == false){
                    $oBukkenAddrErr = '★'.$oBukkenAddrErr;
                }
                if ( $wEstateReceipt->isValidOaza($oChome1) == false){
                    $oBukkenAddrErr = '★'.$oBukkenAddrErr;
                }
                if ( $wEstateReceipt->isValidAfterChome($oChome2) == false){
                    $oBukkenAddrErr = '★'.$oBukkenAddrErr;
                }
                if ( $wEstateReceipt->isValidEdabango($oLotnumber) == false){
                    $oBukkenAddrErr = '●'.$oBukkenAddrErr;
                }
                //外筆
                //if ($oSotofude !== null && $oSotofude !== ""){
                if (! self::isEmpty($oSotofude)){
                    //$oSotofude = $wEstateReceipt->getSotofude($oSotofude);
                    if ( $wEstateReceipt->isVaildSotofude($oSotofude) == false){
                        $oBukkenAddrErr = '■'.$oBukkenAddrErr;
                    }

                }
                if($oBukkenAddrErr == '' || $oBukkenAddrErr == 'A' || $oBukkenAddrErr == 'R' || $oBukkenAddrErr == 'R  A') {
                	$split_words_array = ['区', '町', '村', '市'];

                	if ( $wEstateReceipt->isValidBukkenAddr($oBukkenAddr, $city_master_array, $split_words_array) === false){
                        $oBukkenAddrErr = '★'.$oBukkenAddrErr;

                    }
                }
            }

            //目的
            $oPurpose = $this->report1->getPurpose($line);
            $oPurpose = $wEstateReceipt->getPurpose($oPurpose);

            //目的エラー
            $oPurposeErr = $this->report1->getPurpose_Err($line);
            if ( $wEstateReceipt->isValidPurpose($oPurpose) == false){
                $oPurposeErr = '★'.$oPurposeErr;
            }
            //ファイル名
            $oFilename = $this->report1->getFilename($line);
            //ページ
            $oPage = $this->report1->getPage($line);
            //ページエラー
            $oPageErr = $this->report1->getPage_Err($line);

            $outputBuffer[] = $this->createData($oReceiptNo,$oReceiptNoErr, $oReceiptDate, $oReceiptDateErr, $oReceiptSeq, $oReceiptSeqErr, $oGroup, $oGroupErr, $oBukkenAddr, $oBukkenAddrErr, $oPurpose, $oPurposeErr, $oFilename, $oPage, $oPageErr);

            $countLine = $countLine +1;

        }

        //$this->logger->cLog($outputBuffer);
		/*
		 * ここで第一回チェック終わりました
		 *
		 * 受付日に対し
		 * -----後処理-----
		 * ここから、受付番号の修正を始める
		 * 前後を見て、前と後ろ同じの場合、真中の受付日付を修正する　マークを保留
		 * 連番間違ったら、そのまま(一番と最後を無視)
		 * 例：11月1日  11月111日  11月1日-->11月1日  11月1日  11月1日
		 * ----------------
		 * */
		for($k=1; $k<count($outputBuffer)-1; $k++){
			if($outputBuffer[$k-1][2] === $outputBuffer[$k+1][2]){
				$outputBuffer[$k][2] = $outputBuffer[$k+1][2];
			}
		}
		//$this->logger->cLog($outputBuffer);

        $header = array($this->createHeader());
        // ファイル書き込み
        $this->report1->writeCsv($this->outputDirPath.'/'.$this->outputCsv, $header, $outputBuffer);
        $this->logger->cLog('=============================================================');
        $this->logger->cLog("==== Processing completed. Output File => " . $this->outputCsv);
    }

    public function createHeader() {

        $buffer = array();
        $idx = 0;

        $buffer[$idx++] = '受付番号';
        $buffer[$idx++] = '受付番号エラー';
        $buffer[$idx++] = '受付日';
        $buffer[$idx++] = '受付日エラー';
        $buffer[$idx++] = '受付順序';
        $buffer[$idx++] = '受付順序エラー';
        $buffer[$idx++] = 'グループ';
        $buffer[$idx++] = 'グループエラー';
        $buffer[$idx++] = '地番';
        $buffer[$idx++] = '地番エラー';
        $buffer[$idx++] = '目的';
        $buffer[$idx++] = '目的エラー';
        $buffer[$idx++] = 'ファイル名';
        $buffer[$idx++] = 'ページ番号';
        $buffer[$idx++] = 'ページエラー';

        return $buffer;

    }
    /**
     * 住所向け、アドレス設定
     * @return AddressConfig
     */
    private function createAddressConfigForOwnerAddress() {
        $addrConfig = new AddressConfig();
        // 前半の設定（特に何もしない）
        $firstHalfConfig = $addrConfig->createConfigUnit();
        // 丁目、丁があればの変換設定(丁、丁目はハイフン。英数字は全角）
        $chomeConfig = $addrConfig->createConfigUnit(CNV_NOT, CNV_NOT, CNV_ZEN, CNV_ZEN, CNV_ZEN);
        // 枝番（英数字のみ半角）
        $edaConfig = $addrConfig->createConfigUnit(CNV_NOT, CNV_NOT, CNV_ZEN, CNV_ZEN);
        // 海外住所（数字のみ半角）
        $foreignConfig = $addrConfig->createConfigUnit(CNV_NOT, CNV_NOT, CNV_NOT, CNV_NOT);

        $addrConfig->setConfig(AddressConfig::TYPE_CITY, $firstHalfConfig);
        $addrConfig->setConfig(AddressConfig::TYPE_CHOME, $chomeConfig);
        $addrConfig->setConfig(AddressConfig::TYPE_AFTER, $edaConfig);
        $addrConfig->setConfig(AddressConfig::TYPE_FOREIGN, $foreignConfig);

        $this->logger->cLog($addrConfig);

        return $addrConfig;

    }

    private function createData($ReceiptNo, $ReceiptNoErr, $ReceiptDate, $ReceiptDateErr, $ReceiptSeq, $ReceiptSeqErr, $Group, $GroupErr, $BukkenAddr, $BukkenAddrErr, $Purpose, $PurposeErr, $Filename, $Page, $PageErr) {

        $buffer = array();
        $idx = 0;
        $buffer[$idx++] = $ReceiptNo; // 受付番号
        $buffer[$idx++] = $ReceiptNoErr; // 受付番号
        $buffer[$idx++] = $ReceiptDate; // 受付日
        $buffer[$idx++] = $ReceiptDateErr; // 受付日
        $buffer[$idx++] = $ReceiptSeq; // 順序（単独）、（連続）、（連先）
        $buffer[$idx++] = $ReceiptSeqErr; // 順序（単独）、（連続）、（連先）
        $buffer[$idx++] = $Group; // 区分（土地、建物）
        $buffer[$idx++] = $GroupErr; // 区分（土地、建物）
        $buffer[$idx++] = $BukkenAddr; // 地番
        $buffer[$idx++] = $BukkenAddrErr; // 地番
        $buffer[$idx++] = $Purpose; // 目的
        $buffer[$idx++] = $PurposeErr; // 目的エラー
        $buffer[$idx++] = $Filename; // ファイル名
        $buffer[$idx++] = $Page; // ページ
        $buffer[$idx++] = $PageErr; // ページエラー

        return $buffer;
    }

    public function processing2() {
        $ini_array = parse_ini_file( "../lib/init/areamaster.ini",false);

        $this->logger->cLog("=== Statring output From " . $this->report1CsvPath );

        // レポートCSV取得
        $hasHeader = true;
        $csv = $this->report1->getCsvBodyData($hasHeader);
        $arrKoOtsuHei = array("甲","乙","丙","丁","戊","己","庚","辛","壬","癸");
        //$csv = $this->report1->getCsvBodyDataEx();

        // 出力用
        $outputBuffer = array();
        $oReceiptNo="";
        $countLine = 0;

        foreach ($csv as $line) {
            if (count($line) == 1){
                continue;
            }
            if ($hasHeader == true) {
                if ($countLine == 0) {
                    $countLine = $countLine + 1;
                    continue;
                }
            }
            //初期化
            $wEstateReceipt = new EstateReceipt();
            $preReceiptNo = $oReceiptNo; //1つ前の受付番号
            $oReceiptNo = "";
            $oReceiptNoErr = "";
            $oReceiptDate = "";
            $oReceiptSeq = "";
            $oReceiptSeqErr = "";
            $oGroup = "";
            $oGroupErr = "";
            $oBukkenAddr = "";
            $oBukkenAddrErr ="";
            $oPurpose = "";
            $oPurposeErr = "";
            $wBukkenAddr = "";
            $wSotofude = "";
            $oSotofude ="";
            $oSotofudeTmp ="";
            $oPref = "";
            $oYear = "";

            //受付番号
            $oReceiptNo = $this->report1->getReceiptNo($line);
            //受付番号エラー
            $oReceiptNoErr = $this->report1->getReceiptNo_Err($line);

            //受付日
            $oReceiptDate = $this->report1->getReceiptDate($line);
            //受付日エラー
            //$oReceiptDateErr = $this->report1->getReceiptDate_Err($line);
            //順序（単独）、（連続）、（連先）
            $oReceiptSeq = $this->report1->getReceiptSeq($line);
            //順序（単独）、（連続）、（連先）エラー
            $oReceiptSeqErr = $this->report1->getReceiptSeq_Err($line);

            //グループ（土地、建物）
            $oGroup = $this->report1->getGroup($line);
            //グループ（土地、建物）エラー
            $oGroupErr = $this->report1->getGroup_Err($line);

            //地番
            $oBukkenAddr = $this->report1->getBukkenAddr($line);
            //地番エラー
            //$oBukkenAddrErr = $this->report1->getBukkenAddr_Err($line);

            //ファイル名　都道府県_市区郡_
            $oFilename = $this->report1->getFilename($line);

            //都道府県を取得
            $oPref = trim($this->report1->getPrefecture($line));

            //年を取得
            $oYear = trim($this->report1->getYear($line));

            $oAddress = $oPref.$oBukkenAddr;

            // 住所変換（数字は全角、丁目はハイフン、ハイフンも全角）
            $addrConfigForChiban = $this->createAddressConfigForChiban();
            $oAddrTmp1 = $this->addrMod->changeAddress($oAddress, $addrConfigForChiban);
            $cityAndTown = $this->addrMod->getParts($oAddrTmp1,AddressModifier::IDX_PARTS_CITY);
            $oAddrTmp = $this->addrMod->splitSikugun($oPref,$cityAndTown);
            $oCity = str_replace('★','',$oAddrTmp[0]);
            $oChome1 = str_replace('★','',$oAddrTmp[1]);
            $oChome2 = $this->addrMod->getParts($oAddrTmp1,AddressModifier::IDX_PARTS_CHOME);

            //埼玉県春日部市八丁目などの例外処理、Chome2をChome1へ入れ替え
            if ($wEstateReceipt->isChome2toChome1($oAddress) == true ){
                if ($oChome1 == "") {
                    $oChome1 = $oChome2;
                    $oChome2 = "";
                }
            }

            //枝番以降を抽出
            $oAfter = mb_substr($oBukkenAddr,mb_strlen($cityAndTown.$oChome2), mb_strlen($oBukkenAddr)-mb_strlen($cityAndTown.$oChome2));
            //外筆を除いて枝番を作る
            if(preg_match('/外/',$oAfter)) {
                //外筆
                $oSotofude = $wEstateReceipt->getSotofude($oAfter);
                if (mb_strpos($oAfter,"外")>0){
                    $oLotnumber = mb_substr($oAfter,0,mb_strpos($oAfter,"外"));
                }
            } else {
                if(mb_strlen($oAfter)>0){
                    $oLotnumber = $oAfter;
                }
            }

            $oKootu = "";
            //oChome1の最後に甲・乙・丙・丁・戊・己・庚・辛・壬・癸が含まれる場合、地番・家屋番号の先頭へ追加する
            if(in_array(mb_substr($oChome1,-1), $arrKoOtsuHei) == true) {
                $oLotnumber = mb_substr($oChome1,-1) . $oLotnumber;
                //最後の１文字削除
                $oChome1 = mb_substr($oChome1,0,-1);
            }

            //$oLotnumber = $this->addrMod->getParts($oAddrTmp1,AddressModifier::IDX_PARTS_AFTER);
            //$oAfter = $this->addrMod->getParts($oAddrTmp1,AddressModifier::IDX_PARTS_MNAME);

            //目的
            $oPurpose = $this->report1->getPurpose($line);
            //目的エラー
            //$oPurposeErr = $this->report1->getPurpose_Err($line);


            //Areaidを取得
            $PrefCity = $oPref.$oCity;
            $oAreaid = $ini_array[$PrefCity];
            if (is_null($oAreaid) == true) {
                $oAreaid = "★";
            }

            //離島かどうかチェック、離島の場合、住所変換がうまくいっていないので★を付ける
            if ($wEstateReceipt->isRitou($oAddress) == true ){
                $oAreaid = $oAreaid."★";
            }

            //受付番号を英数を半角
            $oReceiptNoTmp = mb_convert_kana($oReceiptNo,'a'); //英数半角

            //受付日をYYYY/MM/DDに変換
            $oReceiptDateTmp = mb_substr($oReceiptDate,0, mb_strpos($oReceiptDate,"日"));
            $oReceiptDateTmp = str_replace("月","/",$oReceiptDateTmp);
            $oReceiptDateTmp = str_replace("日","",$oReceiptDateTmp);
            $oReceiptDateTmp = str_replace("受付","",$oReceiptDateTmp);
            $oReceiptDateTmp = $oYear . "/" . $oReceiptDateTmp;

            //lotnumber英数を半角
            $oLotnumberTmp = mb_convert_kana($oLotnumber,'a'); //英数半角
            $oLotnumberTmp = str_replace("--","-",$oLotnumberTmp);
            //lotnumberの最後がハイフン「-」、漢数字「一」は取る
            if (mb_substr($oLotnumberTmp,-1)=="-" or mb_substr($oLotnumberTmp,-1)=="一") {
                //最後の１文字削除
                $oLotnumberTmp = mb_substr($oLotnumberTmp,0,-1);
            }
            //外筆の外を削除して半角
            $oSotofudeTmp = str_replace("外","", $oSotofude);
            $oSotofudeTmp = mb_convert_kana($oSotofudeTmp,'a'); //英数半角

            //地番をlotnumberだけ半角
            $oAddressTmp = $PrefCity.$oChome1.$oChome2.$oLotnumberTmp;

            $outputBuffer[] = $this->createData2("null",$oAreaid, $oGroup, $oChome1, $oChome2, $oLotnumberTmp, $oAddressTmp,'','','0','', $oSotofudeTmp, $oReceiptDateTmp, $oReceiptSeq, $oPurpose, $oReceiptNoTmp,'1');

        }

        $header = array($this->createHeader2());
        // ファイル書き込み
        $this->report1->writeCsv($this->outputDirPath.'/'.$this->outputCsv, $header, $outputBuffer);
        $this->logger->cLog('=============================================================');
        $this->logger->cLog("==== Processing completed. Output File => " . $this->outputCsv);

        $countLine = $countLine + 1;
    }

    /**
     * 地番向け、アドレス設定
     * @return AddressConfig
     */
    private function createAddressConfigForChiban() {
        $addrConfig = new AddressConfig();
        // 前半の設定（特に何もしない）
        $firstHalfConfig = $addrConfig->createConfigUnit();
        // 丁目、丁があればの変換設定(丁、丁目はハイフン。英数字は全角）
        $chomeConfig = $addrConfig->createConfigUnit(CNV_NOT, CNV_NOT, CNV_NOT, CNV_NOT, CNV_NOT);
        // 枝番（英数字のみ半角）
        $edaConfig = $addrConfig->createConfigUnit(CNV_NOT, CNV_NOT, CNV_NOT, CNV_NOT);
        // 海外住所（数字のみ半角）
        $foreignConfig = $addrConfig->createConfigUnit(CNV_NOT, CNV_NOT, CNV_NOT, CNV_NOT);

        $addrConfig->setConfig(AddressConfig::TYPE_CITY, $firstHalfConfig);
        $addrConfig->setConfig(AddressConfig::TYPE_CHOME, $chomeConfig);
        $addrConfig->setConfig(AddressConfig::TYPE_AFTER, $edaConfig);
        $addrConfig->setConfig(AddressConfig::TYPE_FOREIGN, $foreignConfig);

        //$this->logger->cLog($addrConfig);

        return $addrConfig;

    }

    public function createHeader2() {

        $buffer = array();
        $idx = 0;

        $buffer[$idx++] = 'null';
        $buffer[$idx++] = 'areaid';
        $buffer[$idx++] = 'グループ';// 区分（土地、建物）
        $buffer[$idx++] = 'address1';
        $buffer[$idx++] = 'address2';
        $buffer[$idx++] = 'lotnumber';
        $buffer[$idx++] = 'address';
        $buffer[$idx++] = 'raddress';//空
        $buffer[$idx++] = 'raddress1';//空
        $buffer[$idx++] = 'address_ok_flag';// 固定：0
        $buffer[$idx++] = 'name';//空
        $buffer[$idx++] = '外筆';//memo
        $buffer[$idx++] = '受付日';//regitdate
        $buffer[$idx++] = '順序';//（単独）、（連続）、（連先）
        $buffer[$idx++] = '目的'; // 目的
        $buffer[$idx++] = '受付番号';//数値は半角
        $buffer[$idx++] = 'seach_flag'; // 固定：1


        return $buffer;

    }

    private function createData2($null_str, $Areaid, $Group, $Chome1, $Chome2, $lotnumber, $Address, $Raddress, $Raddress1, $Address_ok_flag, $name, $Sotofude, $ReceiptDate, $ReceiptSeq, $Purpose, $ReceiptNo, $search_flag) {

        $buffer = array();
        $idx = 0;
        $buffer[$idx++] = $null_str; // null
        $buffer[$idx++] = $Areaid; // Areaid
        $buffer[$idx++] = $Group; // 区分（土地、建物）
        $buffer[$idx++] = $Chome1; // Chome1
        $buffer[$idx++] = $Chome2; // Chome2
        $buffer[$idx++] = $lotnumber; // lotnumber
        $buffer[$idx++] = $Address; // 地番
        $buffer[$idx++] = $RAddress; // raddress
        $buffer[$idx++] = $RAddress1; // raddress1
        $buffer[$idx++] = $Address_ok_flag; // address_ok_flag
        $buffer[$idx++] = $name; // name
        $buffer[$idx++] = $Sotofude; // 外筆
        $buffer[$idx++] = $ReceiptDate; // 受付日
        $buffer[$idx++] = $ReceiptSeq; // 順序（単独）、（連続）、（連先）
        $buffer[$idx++] = $Purpose; // 目的
        $buffer[$idx++] = $ReceiptNo; // 受付番号
        $buffer[$idx++] = $search_flag; // search_flag

        return $buffer;
    }

    /**
     * FormOCRで自動出力したCSVファイルのファイル名を取得する
     * @return mixed|string|unknown
     */
    public function getFormOcrInputFilename()
    {
        // レポートCSV取得
        $csv = $this->report1->getCsvBodyData();

        foreach ($csv as $line) {
            //ファイル名　都道府県_市区郡_
            $oFilename = $this->report1->getFilename($line);
            break;
        }
        return $oFilename;
    }

}

