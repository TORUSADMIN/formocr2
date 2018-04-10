<?php

/**
 * Created by PhpStorm.
 * Date: 2017/05/31
 * Time: 20:06
 * 不動産受付帳データをDB登録用データに精査用に変換するクラス
 */

class EstateReceipt
{
    //受付番号
	const RECEIPTNO_PATTERN = '/第[０-９]*[ー－−-]*[（|\(]?[ぁ-ん]?[）|\)]?号/u';
	const RECEIPTNO_PATTERN2 = '/^第([０-９]{5,})([ー－−-])([（|\(])([ぁ-ん])([）|\)])号$/u';
	const RECEIPTNO_PATTERN4 = '/^第([０-９]{5,})([ー－−-])([０-９])号$/u';
	const RECEIPTNO_PATTERN3 = '/^第([０-９]{5,})号$/u';


    //受付日
    //const RECEIPTDATE_PATTERN = '/(1[0-2]{1}|[1-9]{1})月(3[0-1]{1}|2[0-9]{1}|[1-9]{1})日受付/u';
    const RECEIPTDATE_PATTERN = '/(1[0-2]{1}|[1-9]{1})月(3[0-1]{1}|2[0-9]{1}|1[0-9]{1}|0[0-9]{1}|[1-9]{1})日/u';
    //順序（単独|連先|連続)
    const RECEIPTSEQ_PATTERN = '/単独|連先|連続/u';
    //区分（土地|建物|区建）
    const GROUP_PATTERN = '/土地|建物|区建/u';
    //外筆
    const SOTOFUDE_PATTERN = '/(外)([０-９]+)$/u';
    const SOTOFUDE_PATTERN1 = '/(外)([０-９]+)$/u';
    //地番エラーパターン
    //20+1から20+C [!-,]　EEBC80+Aから[！-，]
    //30+Aから40+0[:-@] [：-＠]
    //70+Bから70+E[{-~] [［-｀]
    //e28480+0からe284b0+8[℀-ℸ]
    //e28590+4からe28680+3 [⅓-ↂ]
    //e28690+0からe28f80+F [←-⏏]
    //e291a0+0からe29b80+3[①-⛃]
    //e2ba80あたり[⼂⼅⼇⼉⼌⼍⼎⼏⼐⼔⼕⼖⼘⼙⼚⼧⼴⼹]
    //e383b0+0からe383b0＋F[ヰ-ヿ]ーーーーーーーーーーー ヶ抜き ン抜き
    //e38080+1からe380b0+F [、-〄][〆-〿] 「々」は除く
    //e38480+5からe384a [ㄅ-ㄬ]
    const BUKKEN_ADDR_PATTERN = '/[！-，：-＠［-｀｛-､!-,:-@{-~A-Za-zＡ-Ｚａ-ｚｌｉ・．／＊丶“‘’兀」儿冂冖几凵匚匸卜ト卩厂宀尸广彐℀-ℸ⅓-ↂ←-⏏①-⛃⼂⼅亠⼉⼌⼍⼎⼏⼐⼔⼕⼖⼘⼙⼚⼧⼫⼴⼹ヰ-ヲヴ-ヵヷ-ヿ、-〄〆-〿ㄅ-ㄬ]/u';
    const BUKKEN_ADDR_PATTERN_WITHOUT_KAKO = '/[！-\'*-,：-＠［-｀｛-､!-,:-@{-~A-Za-zＡ-Ｚａ-ｚｌｉ・．／＊丶“‘’兀」儿冂冖几凵匚匸卜ト卩厂宀尸广彐℀-ℸ⅓-ↂ←-⏏①-⛃⼂⼅亠⼉⼌⼍⼎⼏⼐⼔⼕⼖⼘⼙⼚⼧⼫⼴⼹ヰ-ヲヴ-ヵヷ-ヿ、-〄〆-〿ㄅ-ㄬ]/u';
    const SIGN_PATTERN = '/[！-，：-＠［-｀｛-､!-,:-@{-~A-Za-zＡ-Ｚａ-ｚｌｉ・．／丶“‘’℀-ℸ⅓-ↂ←-⏏①-⛃⼂⼅亠⼉⼌⼍⼎⼏⼐⼔⼕⼖⼘⼙⼚⼧⼫⼴⼹ヰ-ヵヷ-ヿ、-〄〆-〿ㄅ-ㄬ]/u';
    //地番エラーパターン２ 「-0」「－０」を含む、ＡからＺを含む、工、ヨ、ユ、フと数字の組み合わせ、最後１字が数字・カタカナ・＊以外
    //const BUKKEN_ADDR_PATTERN2 = '/[Ａ-Ｚ]|[工|ヨ|ユ|フ][０-９]|[０-９][工|ヨ|ユ|フ]|[工|ヨ|ユ|フ]$|[^０-９ァ-ヶ＊]$/u';
    //const BUKKEN_ADDR_PATTERN2 = '/[Ａ-Ｚ]|[工|ヨ|ユ|フ][０-９]|[０-９][工|ヨ|ユ|フ]|[工|ヨ|ユ|フ]$|[^０-９ァ-ヵ＊]$/u';
    const BUKKEN_ADDR_PATTERN2 = '/[工ヨユフ]/u';
    //丁目エラーパターン２
    //α、σ、工、ヨ、ユ、ヱ、フ、＆、◎、コ、厂、亠、πと数字の組み合わせ
    //最後がハイフン
    //－０、－丁
    //コ目,了,丁且,丁圏,丁§,丁自、倡、－丁、眉を含む
    //全角アルファベット
    //全角カタカナ
    //0390 CE90から Α-ω
    //const CHOME_PATTERN = '/l|α|σ|工|ヨ|ユ|ヱ|フ|＆|正|◎|コ|厂|亠|π|－$|－０|－丁|コ目|了|丁且|丁圏|丁§|丁自|倡|眉|[Ａ-Ｚ]|[ァ-ヶ]|[Α-ω]/u';
    const CHOME_PATTERN = '/l|α|σ|工|ヨ|ユ|ヱ|フ|＆|◎|コ|厂|亠|π|－$|－０|－丁|コ目|了|丁且|丁圏|丁§|丁自|倡|眉|[Ａ-Ｚ]|[コトフマムレヲン]|[Α-ω]/u';
    const CHOME_PATTERN1 = '/l|α|σ|工|ヨ|ユ|ヱ|フ|＆|◎|コ|厂|亠|π|－$|－０|－丁|コ目|了|丁且|丁圏|丁§|丁自|倡|眉|[Ａ-Ｚ]|[コトフマムヲ]|[Α-ω]/u';
    //枝番号OKパターン　先頭は数値
    const EDA_PATTERN = '/^[０-９]/u';
    const EDA_OK_PATTERN = '/^((甲|乙|／|－|[０-９]))+$/u';

    //目的エラーパターン
    //20+1から20+F[!-/]
    //30+Aから40+0[:-@]
    //e28480+0からe284b0+8[℀-ℸ]
    //e28590+4からe28680+3 [⅓-ↂ]
    //e28690+0からe28f80+F [←-⏏]
    //e291a0+0からe29b80+3[①-⛃]
    //e38080+1からe380b0+F [、-〄][〆-〿] 「々」は除く
    //e38480+5からe384a [ㄅ-ㄬ]
    //言己、申請の申が（巾、伸）、贈与の贈（曽）、ついての（っ）
    //50+Bから60+0 [\]^_`
    //丶
    //地日
    //最後が一
    //const PURPOSE_PATTERN = '/[!-/0-9:-@０-９A-Za-zＡ-Ｚａ-ｚァ-ヶ．’℀-ℸ⅓-ↂ←-⏏①-⛃、-〄〆-〿←-⏏⅓-ↂㄅ-ㄬ]|[言己巾伸]|[地日]|[一]$/u';
    const PURPOSE_PATTERN = '/[\!-\/0-9\:-@０-９A-Za-zＡ-Ｚａ-ｚァ-ヶ．’℀-ℸ⅓-ↂ←-⏏①-⛃、-〄〆-〿←-⏏⅓-ↂㄅ-ㄬ]|[言己巾伸：曽っ\[\]\\\^_`丶]|地日|[一]$/u';

    //*******AddressChecker用正規パターン*****************************************
    const WRONG_CITY_PATTERN1 = '/^([^一-龠ァ-ヶぁ-ん＊]+)([一-龠ァ-ヶぁ-んー]+)/u';
    const WRONG_CITY_PATTERN2 = '/^(＊市)(.+区)([一-龠ァ-ヶぁ-んー]+)/u';
    const WRONG_CITY_PATTERN3 = '/^(＊)(.+区)([一-龠ァ-ヶぁ-んー]+)/u';
    const WRONG_CHOME_PATTERN1 = '/(^[０-９]+)([^丁]+)(目)(.*)/u';
    //const WRONG_CHOME_PATTERN2 = '/(^[０-９]+丁)([^目０-９]+)([０-９]+)/u';
    //const WRONG_CHOME_PATTERN2 = '/(^[０-９]+丁)([自|§|彎|且|圏|溷|日|ヨ|孱|餡|稷|冒|溷|曷|黨|歸|濛|釋|冐])([０-９]+)/u';//「目」の誤字
    const WRONG_CHOME_PATTERN2 = '/(^[０-９]+丁)([一-龠|§])([０-９]+)/u';//「目」の誤字
    const WRONG_ADDRESS_PATTERN1 = '/(^[一-龠ァ-ヶぁ-ん]+)([０-９]+)(丁)(.+)(目)([０-９]+)/u';

    //****************************************************************************

    private $addrMod;

    /**
     * 受付番号が正しいかチェックする
     * @param $str
     * @return int
     */
    public function isValidReceiptNo($str) {


    	$receipt_no_flag = false;
    	if(preg_match(self::RECEIPTNO_PATTERN3, $str)){
    		$receipt_no_flag = true;
    	}
		return $receipt_no_flag;


    	/*
        if (preg_match(self::RECEIPTNO_PATTERN, $str)==true) {
            preg_match(self::RECEIPTNO_PATTERN, $str, $match);
            if ($match[0] == null){
                return false;
            }
            return true;
        } else {
            return false;
        }*/
        //return preg_match(self::RECEIPTNO_PATTERN,$str);
    }

    /**
     * 正しい受付番号を返す　【第１２３４５ー（あ）号】を第１２３４５-(あ)号にする
     * @param $str
     * @return mixed
     */
    public function getReceiptNo($str) {

        if(preg_match(self::RECEIPTNO_PATTERN, $str, $match)){
        	return $match[0];

/*
        	if ($match[0] == null) {
	            $strtmp = str_replace('【','',$str);
	            $strtmp = str_replace('】','',$strtmp);
	            return $strtmp;
	        } else {
	           return $match[0];
	        }*/
        }else{
        	if(mb_strpos($str, '【') >= 0){
        		$str = str_replace('【', '', $str);
        	}
        	if(mb_strpos($str, '】') >= 0){
        		$str = str_replace('】', '', $str);
        	}
        	return $str;
        }
        //preg_match(self::RECEIPTNO_PATTERN, $str, $match);
        //return $match[0];
    }

    /**
     * ファイル名の受付の月から受付日の月を変更する
     * @param unknown $str
     */
    public function changeReceiptMonth($oReceiptMonth, $oReceiptDate){

    	if(mb_strpos($oReceiptDate, '月') >= 0){
    		$date_tmp = explode('月', $oReceiptDate);
    		if(count($date_tmp) === 2){
    			if($date_tmp[0] !== $oReceiptMonth){
    				return $oReceiptMonth . '月' . $date_tmp[1];
    			}
    		}
    	}
    	return $oReceiptDate;
    }


    /**
     * 受付日が正しいかチェックする
     * @param $str
     * @return int
     */
    public function isValidReceiptDate($str) {
        if (preg_match(self::RECEIPTDATE_PATTERN, $str)==true) {
            preg_match(self::RECEIPTDATE_PATTERN, $str, $match);
            if ($match[0] == null){
                return false;
            }
            return true;
        } else {
            return false;
        }
        //return preg_match(self::RECEIPTDATE_PATTERN, $str);
    }

    /**
     * 受付日文字列を返す
     * @param $str
     * @return mixed
     */
    public function getReceiptDate($str) {
        preg_match(self::RECEIPTDATE_PATTERN, $str,$match);
        if ($match[0] == null) {
            return $str;
        } else {
            return $match[0];
        }
        //return $match[0];
    }

    /**
     * 順序（単独|連続|連先)が正しいかチェックする
     * @param $str
     * @return int
     */
    public function isVaildReceiptSEQ($str){
        if (preg_match(self::RECEIPTSEQ_PATTERN, $str)==true) {
            preg_match(self::RECEIPTSEQ_PATTERN, $str, $match);
            if ($match[0] == null){
                return false;
            }
            return true;
        } else {
            return false;
        }
        //return preg_match(self::RECEIPTSEQ_PATTERN, $str);
    }

    /**
     * 順序（単独|連続|連先)を返す
     * @param $str
     * @return mixed
     */
    public function getReceiptSEQ($str){
        preg_match(self::RECEIPTSEQ_PATTERN, $str,$match);
        if ($match[0] == null) {
            return $str;
        } else {
            return $match[0];
        }
        //preg_match(self::RECEIPTSEQ_PATTERN,$str,$match);
        //return $match[0];
    }

    /**
     * 区分（土地|建物）が正しいかチェックする
     * @param $str
     * @return int
     */
    public function isVaildGroup($str){
        if (preg_match(self::GROUP_PATTERN, $str)==true) {
            preg_match(self::GROUP_PATTERN, $str, $match);
            if ($match[0] == null){
                return false;
            }
            return true;
        } else {
            return false;
        }
        //return preg_match(self::GROUP_PATTERN,$str);
    }

    /**
     * 区分（土地|建物）を返す
     * @param $str
     * @return mixed
     */
    public function getGroup($str){
        preg_match(self::GROUP_PATTERN, $str,$match);
        if ($match[0] == null) {
            return $str;
        } else {
            return $match[0];
        }
        //preg_match(self::GROUP_PATTERN,$str,$match);
        //return $match[0];
    }

    /**
     * 外筆が正しいかチェックする
     * @param $str
     * @return int
     */
    public function isVaildSotofude($str){

        if (mb_strpos($str,"外") >= 0) {
            if (preg_match(self::SOTOFUDE_PATTERN, $str, $match)) {
            	$soto_fude_index = count($match) - 1;

            	$soto_fude = $match[$soto_fude_index];
            	$soto_fude_number = mb_convert_kana($soto_fude, 'n');
            	$soto_fude_number = intval($soto_fude_number);
				//外筆二桁以上の場合エラーマークを付ける　要チェック--2018/01/30
				if($soto_fude_number < 10){
					return true;
				}else{
            		return false;
            	}
            } else {
                return false;
            }
        } else {
            return true;
        }
        //return preg_match(self::GROUP_PATTERN,$str);
    }
	/**
	 * 外筆最終チェック
	 */
    public function lastVaildSotofude($str){

    	$flag = true;
    	if (mb_strpos($str,"外") >= 0) {
    		if (preg_match(self::SOTOFUDE_PATTERN, $str, $match)) {
    			$soto_fude_index = count($match) - 1;
    			$soto_fude = $match[$soto_fude_index];
    			$soto_fude_number = mb_convert_kana($soto_fude, 'n');
    			$soto_fude_number = intval($soto_fude_number);
    			//外筆二桁以上の場合エラーマークを付ける　要チェック--2018/01/30
    			if($soto_fude_number > 10){
    				$flag = false;
    			}
    		}
    	}
    	return $flag;
    	//return preg_match(self::GROUP_PATTERN,$str);
    }

    /**
     * 外筆を取得　外１
     * @param $str
     * @return mixed
     */
    public function getSotofude($str){
        if(preg_match(self::SOTOFUDE_PATTERN1, $str, $match)){
	        if ($match[0] == null) {
	            return $str;
	        } else {
	            return $match[0];
	        }
        }
    }

    /**
     * 市区町村が正しいかチェックする
     * @param $str
     * @return int
     */
    public function isValidCity($str) {
        //preg_match(self::BUKKEN_ADDR_PATTERN, $str, $match);
        if (preg_match(self::BUKKEN_ADDR_PATTERN, $str)==false){
            return true;
        } else {
            return false;
        }
    }



    /**
     * 地番をチェックする前に、「：」「＊」で始まり
     * 「丁目」のところは問題がある場合、地番修正
     * 目的：AddressModifierがちゃんとpref city after分割できるように
     * --------------from lixin-------------------------------
     * @param unknown $str
     */
    public function AddressChecker($oPref, $two_chome_array, $str, $city_master_array){

    	$this->addrMod = new AddressModifier();
    	//$city_master_array = parse_ini_file("../lib/init/citymaster.ini", false);
    	$wrong_address_tmp = $this->addrMod->changeAddress($str);
    	$wrong_address_city = $this->addrMod->getParts($wrong_address_tmp, AddressModifier::IDX_PARTS_CITY);//市区町村取得
    	//**************************  city and town  *****************************************
    	//市区町村チェック
    	//：横浜市保土ヶ谷区川島町８０５－２ 　　　一番前のノイズを取り除く　ただし先頭は＊の場合無視　あと修正するから·
    	if(preg_match(self::WRONG_CITY_PATTERN1, $wrong_address_city, $match1)){
    		if(count($match1) === 3){
    			$first_noise = $match1[1];
    			$str = str_replace($first_noise, '', $str);//地番先頭のノイズ(漢字かな以外の部分)
    		}
    	}

    	//例：＊たま市中央区下落合３丁目２－５３
    	if(preg_match('/^(＊)(.+)(市)(.+)(区)/u', $str, $match_city)){

    		$first_star_address = $match_city[0];
    		$first_star_search_address = str_replace('＊', '', $first_star_address);
    		$first_star_pattern = '/' . $first_star_search_address. '$/u';
    		$first_star_result = preg_grep($first_star_pattern, $city_master_array);

    		if(count($first_star_result) === 1){
    			$right_city_result_array = array_values($first_star_result);
    			$right_city_result = $right_city_result_array[0];//city masterの中の結果

    			$str = str_replace($first_star_address, $right_city_result, $str);

    		}

    	}elseif(preg_match('/^(＊)(.+)(郡)(.+)(町|村)/u', $str, $match_gun)){

    		$first_star_address = $match_gun[0];
    		$first_star_search_address = str_replace('＊', '', $first_star_address);
    		$first_star_pattern = '/' . $first_star_search_address. '$/u';
    		$first_star_result = preg_grep($first_star_pattern, $city_master_array);

    		if(count($first_star_result) === 1){
    			$right_city_result_array = array_values($first_star_result);
    			$right_city_result = $right_city_result_array[0];//city masterの中の結果

    			$str = str_replace($first_star_address, $right_city_result, $str);

    		}
    	}


    	//第一次修正してから、もう一度AddressModifierで地番分割
    	$wrong_address_tmp = $this->addrMod->changeAddress($str);
    	$wrong_address_city = $this->addrMod->getParts($wrong_address_tmp, AddressModifier::IDX_PARTS_CITY);//市区町村取得
    	$wrong_address_after = $this->addrMod->getParts($wrong_address_tmp, AddressModifier::IDX_PARTS_AFTER);
    	//市区町村の中にもしnoiseがあったら、取り除く　例：横’浜市港北:区
    	if(mb_strpos($wrong_address_city, '市') > 0){
    		$tmp = explode('市', $wrong_address_city);
    		if(count($tmp) === 2){
    			if(preg_match(self::SIGN_PATTERN, $tmp[0], $match_noise)){
    				if(count($match_noise) === 1){
    					$changed_address_city = str_replace($match_noise[0], '', $wrong_address_city);
    					$str = str_replace($wrong_address_city, $changed_address_city, $str);
    				}
    			}
    		}
    	}elseif(mb_strpos($wrong_address_city, '郡') > 0){
    		$tmp = explode('郡', $wrong_address_city);
    		if(count($tmp) === 2){
    			if(preg_match(self::SIGN_PATTERN, $tmp[0], $match_noise)){
    				if(count($match_noise) === 1){
    					$changed_address_city = str_replace($match_noise[0], '', $wrong_address_city);
    					$str = str_replace($wrong_address_city, $changed_address_city, $str);
    				}
    			}
    		}
    	}
    	if(mb_strpos($wrong_address_city, '区') > 0){
    		$tmp = explode('区', $wrong_address_city);
    		if(count($tmp) === 2){
    			if(preg_match(self::SIGN_PATTERN, $tmp[0], $match_noise)){
    				if(count($match_noise) === 1){
    					$changed_address_city = str_replace($match_noise[0], '', $wrong_address_city);
    					$str = str_replace($wrong_address_city, $changed_address_city, $str);
    				}
    			}
    		}
    	}elseif(mb_strpos($wrong_address_city, '町') > 0){
    		$tmp = explode('町', $wrong_address_city);
    		if(count($tmp) === 2){
    			if(preg_match(self::SIGN_PATTERN, $tmp[0], $match_noise)){
    				if(count($match_noise) === 1){
    					$changed_address_city = str_replace($match_noise[0], '', $wrong_address_city);
    					$str = str_replace($wrong_address_city, $changed_address_city, $str);
    				}
    			}
    		}
    	}elseif(mb_strpos($wrong_address_city, '村') > 0){
    		$tmp = explode('村', $wrong_address_city);
    		if(count($tmp) === 2){
    			if(preg_match(self::SIGN_PATTERN, $tmp[0], $match_noise)){
    				if(count($match_noise) === 1){
    					$changed_address_city = str_replace($match_noise[0], '', $wrong_address_city);
    					$str = str_replace($wrong_address_city, $changed_address_city, $str);
    				}
    			}
    		}
    	}
    	//******************************    after   *************************************
    	//数字と丁の間のチェック
    	//２桁丁目以上のmaster iniファイルを参照し、二桁以上の丁目がある都道府県の地番の場合、丁目チェックしない
    	//横浜市保土ケ谷区桜ケ丘２：「目３９８－１－４６４
		//two_chome_array  二桁以上の丁目を含む都道府県リスト
		if(!array_search($oPref, $two_chome_array)){
	    	if(preg_match(self::WRONG_CHOME_PATTERN1, $wrong_address_after, $match2)){
	    		if(count($match2) === 5){
	    			$right_chome = $match2[1] . '丁' . $match2[3];
	    			$right_after = $match2[4];
	    			$city = $this->addrMod->getParts($wrong_address_tmp, AddressModifier::IDX_PARTS_CITY);
	    			$mName = $this->addrMod->getParts($wrong_address_tmp, AddressModifier::IDX_PARTS_MNAME);
	    			$roomNo = $this->addrMod->getParts($wrong_address_tmp, AddressModifier::IDX_PARTS_ROOM_NO);
	    			$str = $city . $right_chome . $right_after . $mName . $roomNo;
	    		}
	    	}
		}

    	//丁と目の間のチェック
    	//横浜市鶴見区鶴見中央２丁日１８６７－２４
    	if(preg_match(self::WRONG_CHOME_PATTERN2, $wrong_address_after, $match3)){
    		if(count($match3) === 4){
    			$wrong_chome = $match3[1] . $match3[2];
    			$right_chome = $match3[1] . '目';
    			$str = str_replace($wrong_chome, $right_chome, $str);
    		}
    	}

    	//「：」「丁」「目」の問題が終わったら、「＊市」を解決
    	//「丁目」があったら、大体AddressModifierが丁目で分割できる
    	$changed_one_address = $str;
    	$changed_one_address_tmp = $this->addrMod->changeAddress($changed_one_address);
    	//「＊市」があったらcity_master参照　市不明区がある場合---できれば市の名を付ける
    	//例えば　＊市保土ヶ谷区権太坂１丁目２５８－１１－２０７
    	$city_with_star = $this->addrMod->getParts($changed_one_address_tmp, AddressModifier::IDX_PARTS_CITY);

		if(preg_match(self::WRONG_CITY_PATTERN2, $city_with_star, $match_star_city1)){
    		if(count($match_star_city1) === 4){
    			$ku_name = $match_star_city1[2];//区名取得
    			$star_pattern = '/^(.+市)(' . $ku_name . ')$/u';
    			$matching_result = preg_grep($star_pattern, $city_master_array);//city_masterに検索
    			//区名でcity_masterマッチするので、重複の場合を排除、ただ一つの結果だけ取得
    			if(count($matching_result) === 1){
    				$right_city_last_array = array_values($matching_result);
    				$pattern_star = '＊市' . $ku_name;
    				$right_city_last = $right_city_last_array[0];
    				$str = str_replace($pattern_star, $right_city_last, $str);
    				//return $str;
    			}
    		}
    	}elseif(preg_match(self::WRONG_CITY_PATTERN3, $city_with_star, $match_star_city2)){//＊＊都筑区南０１田の場合
    		if(count($match_star_city2) === 4){
    			$ku_name = $match_star_city2[2];//区名取得
    			$star_pattern = '/(' . $ku_name . ')$/u';
    			$matching_result = preg_grep($star_pattern, $city_master_array);//city_masterに検索
    			if(count($matching_result) === 1){
    				$right_city_last_array = array_values($matching_result);
    				$pattern_star = '＊' . $ku_name;
    				$right_city_last = $right_city_last_array[0];
    				$str = str_replace($pattern_star, $right_city_last, $str);
    				//return $str;
    			}
    		}
    	}
    	//チェックしてから、もし市区町村不十分の場合citymaster参照する　結果が一つしかないならば、置き換える
    	//例：浜市港北区樽町３丁目９６５－１－２－７１１
    	if(mb_strpos($str, '区')){//丿崎市宮前区水沢３丁目２７６８－６外１
    		$last_tmp = explode('区', $str);//丿崎市宮前　水沢３丁目２７６８－６外１
    		if(count($last_tmp) == 2){
    			$first_half_city = $last_tmp[0];
    			$unknown_city = $first_half_city . '区';//丿崎市宮前区
    			if(mb_strpos($unknown_city, '市')){
    				$last_city_tmp = explode('市', $unknown_city);//丿崎  宮前区
    				if(count($last_city_tmp) === 2){
    					$maybe_right_ku = $last_city_tmp[1];//宮前区
    					$city_pattern1 = '/' . $maybe_right_ku . '$/u';
    					$matching_result_city = preg_grep($city_pattern1, $city_master_array);
    				}
    			}else{
	    			$city_pattern = '/' . $unknown_city . '$/u';
	    			$matching_result_city = preg_grep($city_pattern, $city_master_array);
    			}
    			if(count($matching_result_city) === 1){
    				$city_value_array = array_values($matching_result_city);
    				$right_city_value = $city_value_array[0];
    				$str = str_replace($unknown_city, $right_city_value, $str);
    			}
    		}
    	}elseif(mb_strpos($str, '町')){
    		$last_tmp = explode('町', $str);
    		if(count($last_tmp) == 2){
    			$first_half_city = $last_tmp[0];
    			$unknown_city = $first_half_city . '町';
    			if(mb_strpos($unknown_city, '郡')){
    				$last_city_tmp = explode('郡', $unknown_city);
    				if(count($last_city_tmp) === 2){
    					$maybe_right_machi = $last_city_tmp[1];
	    				$city_pattern1 = '/' . $maybe_right_machi . '$/u';
	    				$matching_result_city = preg_grep($city_pattern1, $city_master_array);
    				}
    			}else{
	    			$city_pattern = '/' . $unknown_city . '$/u';
	    			$matching_result_city = preg_grep($city_pattern, $city_master_array);
    			}
    			if(count($matching_result_city) === 1){
    				$city_value_array = array_values($matching_result_city);
    				$right_city_value = $city_value_array[0];
    				$str = str_replace($unknown_city, $right_city_value, $str);
    			}
    		}
    	}elseif(mb_strpos($str, '村')){
    		$last_tmp = explode('村', $str);
    		if(count($last_tmp) == 2){
    			$first_half_city = $last_tmp[0];
    			$unknown_city = $first_half_city . '村';
    			if(mb_strpos($unknown_city, '郡')){
    				$last_city_tmp = explode('郡', $unknown_city);
    				if(count($last_city_tmp) === 2){
    					$maybe_right_son = $last_city_tmp[1];
    					$city_pattern1 = '/' . $maybe_right_son . '$/u';
    					$matching_result_city = preg_grep($city_pattern1, $city_master_array);
    				}
    			}else{
	    			$city_pattern = '/' . $unknown_city . '$/u';
	    			$matching_result_city = preg_grep($city_pattern, $city_master_array);
    			}
    			if(count($matching_result_city) === 1){
    				$city_value_array = array_values($matching_result_city);
    				$right_city_value = $city_value_array[0];
    				$str = str_replace($unknown_city, $right_city_value, $str);
    			}
    		}
    	}elseif(mb_strpos($str, '市')){
    		$last_tmp = explode('市', $str);
    		if(count($last_tmp) == 2){
    			$first_half_city = $last_tmp[0];
    			$unknown_city = $first_half_city . '市';
    			$city_pattern = '/' . $unknown_city . '$/u';
    			$matching_result_city = preg_grep($city_pattern, $city_master_array);
    			if(count($matching_result_city) === 1){
    				$city_value_array = array_values($matching_result_city);
    				$right_city_value = $city_value_array[0];
    				$str = str_replace($unknown_city, $right_city_value, $str);
    			}
    		}
    	}

    	//前処理　地番最終チェック　丁目の間にノイズがある場合削除する
    	//例：横浜市りば区大字２３丁＆’目２３...
    	if(preg_match(self::WRONG_ADDRESS_PATTERN1, $str, $match_address)){
    		if(count($match_address) === 7){
    			$last_noise = $match_address[4];//丁と目の間のノイズ取り除く
    			$str = str_replace($last_noise, '', $str);
    		}
    	}

    	//最後、地番の漢字の部分に対し、「尸」という文字があるなら、「戸」に変換する
    	if(preg_match('/([一-龠ぁ-んァ-ヶ]+)/u', $str, $match)){
    		if(count($match) === 2){
    			$part_of_kanji = $match[0];
    			$address_tmp = explode($part_of_kanji, $str);
    			if(count($address_tmp) === 2){
    				$part_of_without_kanji = $address_tmp[1];
    				if(mb_strpos($part_of_kanji, '尸') >= 0){
    					$part_of_kanji = str_replace('尸', '戸', $part_of_kanji);
    					$str = $part_of_kanji . $part_of_without_kanji;
    				}
    			}
    		}
    	}
    	return $str;
    }

    /**
     * 地番が正しいかチェックする
     * @param $str
     * @return int
     */
    public function isValidBukkenAddr($str, $city_master_array, $split_words_array, $pref) {
        //preg_match(self::BUKKEN_ADDR_PATTERN, $str, $match);
        //最後地番チェック　
        /*
         *areamasterでエリア参照し、地番のエリアチェック
         *結果がただ一つの場合後チェックへ進む
         *                      後チェックは違法符号チェック
         *結果が複数あり、あるいはない場合false
         *
         *地番の最終チェックパタン　丁目あるなし　外筆あるなし
         *以外の場合全部マークつける
         *
    	 *地番全体に対して、最終チェック。
    	 *丁目を含むかどうか、正規パタンでエラーマークつける
    	 *
    	 *注意：西条市朔日市１２５－１７ 二つの「市」
    	 *      市川市北国分１丁目２５０１－１６外１
    	 *		市川市市川１丁目６５２－１外２
    	 *      市原市ちはら台西１丁目２９−１０------20180326
    	 *      市原市古市場２８３－１６------20180326
    	 */

    	$last_check_flag = false;

		for($k=0; $k<count($split_words_array); $k++){

	    	if(mb_strpos($str, $split_words_array[$k]) >= 0){

	    		$last_tmp = explode($split_words_array[$k], $str);

	    		if(count($last_tmp) >= 2){
	    			//print_r($last_tmp);
	    			$first_half_city = $last_tmp[0];
	    			//市川市の場合  市川市北国分１丁目２５０１－１６外１
	    			// 2018/03/26  市原市ちはら台西１丁目２９−１０ーーーー新追加
	    			if(count($last_tmp) === 3 && $split_words_array[$k] === '市' && empty($first_half_city) && $last_tmp[1] === '川'){
						$unknown_city = '市川市';
	    			}//市原市ちはら台西１丁目２９−１０ーーーー新追加
	    			elseif(count($last_tmp) === 3 && $split_words_array[$k] === '市' && empty($first_half_city) && $last_tmp[1] === '原'){
						$unknown_city = '市原市';
	    			}//市原市古市場２８３－１６  市原市二日市場６９２－２外２  ------20180326新追加
	    			elseif(count($last_tmp) === 4 && $split_words_array[$k] === '市' && empty($first_half_city) && $last_tmp[1] === '原'){
						$unknown_city = '市原市';
	    			}
	    			elseif(count($last_tmp) === 3 && !empty($first_half_city)){//西条市朔日市１２５－１７ 二つの「市」
	    				$unknown_city = $first_half_city . $split_words_array[$k];
	    			}//市川市市川１丁目６５２－１外２
	    			elseif(count($last_tmp) === 4 && $split_words_array[$k] === '市' && empty($first_half_city) && empty($last_tmp[2]) && $last_tmp[1] === '川'){
	    				$unknown_city = '市川市';
	    			}else{
	    				$unknown_city = $first_half_city . $split_words_array[$k];
	    			}
	    			$city_pattern = '/^' . $unknown_city . '$/u';//エリアマスターで絶対検索
	    			$matching_result_city = preg_grep($city_pattern, $city_master_array);
	    			//citymaster参照して、検索結果ある場合誤字ないと確信
	    			if(count($matching_result_city) > 0){

	    				//市川市の場合  市川市北国分１丁目２５０１－１６外１
	    				if(count($last_tmp) === 3 && $split_words_array[$k] === '市' && empty($first_half_city) && $last_tmp[1] === '川'){
	    					$second_half_city = $last_tmp[2];
	    				}//市原市ちはら台西１丁目２９−１０ーーーー新追加
	    				elseif(count($last_tmp) === 3 && $split_words_array[$k] === '市' && empty($first_half_city) && $last_tmp[1] === '原'){
							$second_half_city = $last_tmp[2];
	    				}
	    				elseif(count($last_tmp) === 3 && !empty($first_half_city)){//西条市朔日市１２５－１７ 二つの「市」
	    					$second_half_city = $last_tmp[1] . $split_words_array[$k] . $last_tmp[2];
	    				}//市川市市川１丁目６５２－１外２
	    				elseif(count($last_tmp) === 4 && $split_words_array[$k] === '市' && empty($first_half_city) && empty($last_tmp[2]) && $last_tmp[1] === '川'){
	    					$second_half_city = '市' . $last_tmp[3];
	    				}//市原市古市場２８３－１６  市原市二日市場６９２－２外２  ------20180326新追加
	    				elseif(count($last_tmp) === 4 && $split_words_array[$k] === '市' && $unknown_city === '市原市'){
							$second_half_city = $last_tmp[2] . '市' . $last_tmp[3];
	    				}
	    				elseif(count($last_tmp) === 2){
	    					$second_half_city = $last_tmp[1];
	    				}
	    				//後半チェック
	    				//大字名　丁目　枝番　外筆
	    				//大字名　丁目　枝番
	    				//大字名　枝番　外筆
	    				//大字名　枝場
	    				//丁目　枝番　外筆
	    				//丁目　枝番
	    				//枝番　外筆
	    				//枝番
	    				//**市大字下赤坂（元**分）１８０４－１１追加


	    				//--追加 例：横浜市保土ヶ谷区神戸町玉一１－３０５

	    				if(preg_match('/(^[一-龠ぁ-んァ-ヶ々ー]+)([０-９]{1,3}|[一二三四五六七八九十]+)(丁目)([０-９－]+|[甲乙丙丁戊己庚辛壬癸][０-９－]+)(外)([０-９]{1,3})$/u', $second_half_city)
	    						&& !preg_match(self::BUKKEN_ADDR_PATTERN, $second_half_city) && !preg_match(self::BUKKEN_ADDR_PATTERN2, $second_half_city)){

    								$last_check_flag = true;
    								break;
	    				}elseif(preg_match('/(^[一-龠ぁ-んァ-ヶ々ー]+)([０-９]{1,3}|[一二三四五六七八九十]+)(丁目)([０-９－]+|[甲乙丙丁戊己庚辛壬癸][０-９－]+)$/u', $second_half_city)
	    						&& !preg_match(self::BUKKEN_ADDR_PATTERN, $second_half_city) && !preg_match(self::BUKKEN_ADDR_PATTERN2, $second_half_city)){

	    							$last_check_flag = true;
		    						break;
	    				}elseif(preg_match('/(^[一-龠ぁ-んァ-ヶ々ー]+)([０-９－]+|[甲乙丙丁戊己庚辛壬癸][０-９－]+)(外)([０-９]{1,3})$/u', $second_half_city)
	    						&& !preg_match(self::BUKKEN_ADDR_PATTERN, $second_half_city) && !preg_match(self::BUKKEN_ADDR_PATTERN2, $second_half_city)){

	    							$last_check_flag = true;
	    							break;
	    				}elseif(preg_match('/(^[一-龠ぁ-んァ-ヶ々ー]+)([０-９－]+|[甲乙丙丁戊己庚辛壬癸][０-９－]+)$/u', $second_half_city)
	    						&& !preg_match(self::BUKKEN_ADDR_PATTERN, $second_half_city) && !preg_match(self::BUKKEN_ADDR_PATTERN2, $second_half_city)){

	    							$last_check_flag = true;
	    							break;
	    				}elseif(preg_match('/(^[０-９]{1,3})(丁目)([０-９－]+|[甲乙丙丁戊己庚辛壬癸][０-９－]+)(外)([０-９]{1,3})$/u', $second_half_city)
	    						&& !preg_match(self::BUKKEN_ADDR_PATTERN, $second_half_city) && !preg_match(self::BUKKEN_ADDR_PATTERN2, $second_half_city)){

	    							$last_check_flag = true;
	    							break;
	    				}elseif(preg_match('/(^[０-９]{1,3})(丁目)([０-９－]+|[甲乙丙丁戊己庚辛壬癸][０-９－]+)$/u', $second_half_city)
	    						&& !preg_match(self::BUKKEN_ADDR_PATTERN, $second_half_city) && !preg_match(self::BUKKEN_ADDR_PATTERN2, $second_half_city)){

	    							$last_check_flag = true;
	    							break;
	    				}elseif(preg_match('/(^[０-９－]+|[甲乙丙丁戊己庚辛壬癸][０-９－]+)(外)([０-９]{1,3})$/u', $second_half_city)
	    						&& !preg_match(self::BUKKEN_ADDR_PATTERN, $second_half_city) && !preg_match(self::BUKKEN_ADDR_PATTERN2, $second_half_city)){

	    							$last_check_flag = true;
	    							break;
	    				}elseif(preg_match('/(^[０-９－]+|[甲乙丙丁戊己庚辛壬癸][０-９－]+)$/u', $second_half_city)
	    						&& !preg_match(self::BUKKEN_ADDR_PATTERN, $second_half_city) && !preg_match(self::BUKKEN_ADDR_PATTERN2, $second_half_city)){

	    							$last_check_flag = true;
	    							break;
	    				}
						/*
						 * 岩手県向けの特別地番チェック
						 * 「地割」がある場合の正規パタン追加
						 * 例：
						 * 紫波郡矢巾町大字上矢次第４地割３１－７
						 * 盛岡市乙部４地割８３－５６外３
						 * 盛岡市西見前１７地割４８－１
						 * */
	    				if($pref == '岩手県' && $last_check_flag == false){
							if(preg_match('/(^[一-龠ぁ-んァ-ヶ々ー]+)([０-９]{1,2})(地割)([０-９]+)(－)([０-９]+)$/u', $second_half_city)
									&& !preg_match(self::BUKKEN_ADDR_PATTERN, $second_half_city) && !preg_match(self::BUKKEN_ADDR_PATTERN2, $second_half_city)){

										$last_check_flag = true;
										break;
							}elseif(preg_match('/(^[一-龠ぁ-んァ-ヶ々ー]+)([０-９]{1,2})(地割)([０-９]+)(－)([０-９]+)(外)([０-９]{1,3})$/u', $second_half_city)
									&& !preg_match(self::BUKKEN_ADDR_PATTERN, $second_half_city) && !preg_match(self::BUKKEN_ADDR_PATTERN2, $second_half_city)){

										$last_check_flag = true;
										break;
							}elseif(preg_match('/(^[一-龠ぁ-んァ-ヶ々ー]+)([０-９]{1,2})(地割)([０-９]+)$/u', $second_half_city)
									&& !preg_match(self::BUKKEN_ADDR_PATTERN, $second_half_city) && !preg_match(self::BUKKEN_ADDR_PATTERN2, $second_half_city)){

										$last_check_flag = true;
										break;
							}elseif(preg_match('/(^[一-龠ぁ-んァ-ヶ々ー]+)([０-９]{1,2})(地割)([０-９]+)(外)([０-９]{1,3})$/u', $second_half_city)
									&& !preg_match(self::BUKKEN_ADDR_PATTERN, $second_half_city) && !preg_match(self::BUKKEN_ADDR_PATTERN2, $second_half_city)){

										$last_check_flag = true;
										break;
							}elseif(preg_match('/(^[一-龠ぁ-んァ-ヶ々ー]+)([０-９]{1,2})(地割)([０-９]+)(－)([０-９]+)(－)([０-９]+)(外)([０-９]{1,3})$/u', $second_half_city)
									&& !preg_match(self::BUKKEN_ADDR_PATTERN, $second_half_city) && !preg_match(self::BUKKEN_ADDR_PATTERN2, $second_half_city)){

										$last_check_flag = true;
										break;
							}elseif(preg_match('/(^[一-龠ぁ-んァ-ヶ々ー]+)([０-９]{1,2})(地割)([０-９]+)(－)([０-９]+)(－)([０-９]+)$/u', $second_half_city)
									&& !preg_match(self::BUKKEN_ADDR_PATTERN, $second_half_city) && !preg_match(self::BUKKEN_ADDR_PATTERN2, $second_half_city)){

										$last_check_flag = true;
										break;
							}
	    				}
	    			}
	    		}
	    	}
		}
		return $last_check_flag;
    }

    /**
     * 2018/03/28新追加
     * 例：
     * 神奈川県茅ヶ崎市湘南３丁目１２－３４３－外２
     * 神奈川県茅ヶ崎市湘南３丁目１２－－３４３－外２
     * 神奈川県茅ヶ崎市湘南３丁目－１２－３４３外２
     * 神奈川県茅ヶ崎市湘南３丁目－１２－－３４３－外２
     * @param unknown $address
     * @return boolean
     */
    public function AddressLastCheck($address){
    	if(preg_match('/(\－\－|丁目－|－外２)/u', $address)){
    		return false;
    	}else{
    		return true;
    	}
    }

    /**
     *
     * 大字名チェック-------「丁目」前　「市区町村」後
     * 茅ヶ崎　防ぐために
     * from lixin
     * @param unknown $str
     */
    public function isValidOaza($str){

    	if (preg_match(self::BUKKEN_ADDR_PATTERN, $str)==false) {
    		//preg_match(self::CHOME_PATTERN, $str, $match);

    		if (preg_match(self::CHOME_PATTERN1, $str) == false) {
    			return true;
    		} else {
    			return false;
    		}
    	} else {
    		return false;
    	}
	}

    public function isValidAfterChome($str){

        //preg_match(self::BUKKEN_ADDR_PATTERN, $str, $match);
        if (preg_match(self::BUKKEN_ADDR_PATTERN, $str)==false) {
            //preg_match(self::CHOME_PATTERN, $str, $match);
            if (preg_match(self::CHOME_PATTERN, $str) == false) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 20180316修正　岩手県盛岡市乙部５地割３１６－１外２
     * の地番が多いから、枝番が「５地割３１６－１」になる。
     * 枝番チェック関数更新
     * @param unknown $str
     * @param unknown $pref
     * @return boolean
     */
    public function isValidEdabango($str, $pref){
        //preg_match(self::EDA_PATTERN, $str, $match);
        //if (preg_match(self::EDA_PATTERN, $str)==true) {
           // preg_match(self::EDA_OK_PATTERN, $str, $match);
           if($pref == '岩手県'){
	           if(preg_match('/^([０-９]+)([一-龠]+)([０-９－]+)$/u', $str) == true){
		           if(preg_match(self::CHOME_PATTERN, $str) == false) {
		               return true;
		           }else{
		               return false;
		           }
	           }elseif(preg_match(self::EDA_OK_PATTERN, $str)==true){
	           	   if(preg_match(self::CHOME_PATTERN, $str) == false) {
	           		   return true;
	           	   }else{
	           		   return false;
	           	   }
	           }else{
	           		return false;
	           }
           }else{
	            if(preg_match(self::EDA_OK_PATTERN, $str)==true) {
	                //preg_match(self::CHOME_PATTERN, $str, $match);
	                if (preg_match(self::CHOME_PATTERN, $str) == false) {
	                    return true;
	                } else {
	                    return false;
	                }
	            } else {
	                return false;
	            }
           }
        //} else {
        //    return false;
        //}
    }

    /**
     * 目的が正しいかチェックする
     * @param $str
     * @return int
     */
    public function isValidPurpose($str) {
        if (preg_match(self::PURPOSE_PATTERN, $str)==false){
            return true;
        } else {
            return false;
        }
    }

    /**
     * 目的を返す
     * @param $str
     * @return mixed
     */
    public function getPurpose($str) {
            //スラッシュ以下の文字列は削除する
            $p = mb_strpos($str,"／");
            if ($p > 0) {
                return mb_substr($str,0, $p);
            } else {
                return $str;
            }
    }

    /**
     * 町丁目１と町丁名２を交換する例外住所か判断する
     * @param $address
     * @return bool
     */
    public function isChome2toChome1($address){
        $wAddresses = $this->includeChome2ToChome1Address();
        foreach ($wAddresses as $pattern){
            if(preg_match($pattern, $address)==true){
                return true;
            }
        }
        return false;
    }

    /**
     * 町丁目１と町丁名２を交換する例外住所を作成
     * @param $address
     * @return array
     */
    private function includeChome2ToChome1Address(){
        return array(
            '/春日部市八丁目/u',
        );
    }

    /**
     * 離島所在かどうか判断
     * @param $address
     * @return bool
     */
    public function isRitou($address) {
        $wAddresses = $this->includeRitou();
        foreach ($wAddresses as $pattern){
            if(preg_match($pattern, $address)==true){
                return true;
            }
        }
        return false;
    }

    /**
     * 離島所在データを作成
     * @return array
     */
    private function includeRitou(){
        return array(
            '/東京都三宅島三宅村/u',
            '/東京都八丈島八丈町/u',
            '/東京都大島町/u',
            '/東京都小笠原村/u',
            '/東京都御蔵島村/u',
            '/東京都新島村/u',
            '/東京都利島村/u',
            '/東京都神津島村/u',
        );
    }

}