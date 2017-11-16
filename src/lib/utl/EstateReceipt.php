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
    const RECEIPTNO_PATTERN = '/第[０-９]*－*[（|\(]?[ぁ-ん]?[）|\)]?号/u';
    //受付日
    //const RECEIPTDATE_PATTERN = '/(1[0-2]{1}|[1-9]{1})月(3[0-1]{1}|2[0-9]{1}|[1-9]{1})日受付/u';
    const RECEIPTDATE_PATTERN = '/(1[0-2]{1}|[1-9]{1})月(3[0-1]{1}|2[0-9]{1}|1[0-9]{1}|0[0-9]{1}|[1-9]{1})日/u';
    //順序（単独|連先|連続)
    const RECEIPTSEQ_PATTERN = '/単独|連先|連続/u';
    //区分（土地|建物|区建）
    const GROUP_PATTERN = '/土地|建物|区建/u';
    //外筆
    const SOTOFUDE_PATTERN = '/外[０-９]*$/u';
    //地番エラーパターン
    //20+1から20+C [!-,]　EEBC80+Aから[！-，]
    //30+Aから40+0[:-@] [：-＠]
    //70+Bから70+E[{-~] [［-｀]
    //e28480+0からe284b0+8[℀-ℸ]
    //e28590+4からe28680+3 [⅓-ↂ]
    //e28690+0からe28f80+F [←-⏏]
    //e291a0+0からe29b80+3[①-⛃]
    //e2ba80あたり[⼂⼅⼇⼉⼌⼍⼎⼏⼐⼔⼕⼖⼘⼙⼚⼧⼴⼹]
    //e383b0+0からe383b0＋F[ヰ-ヿ]
    //e38080+1からe380b0+F [、-〄][〆-〿] 「々」は除く
    //e38480+5からe384a [ㄅ-ㄬ]
    const BUKKEN_ADDR_PATTERN = '/[！-，：-＠［-｀｛-､!-,:-@{-~A-Za-zＡ-Ｚａ-ｚｌｉ・．／＊丶“‘’兀」儿冂冖几凵匚匸卜ト卩厂宀尸广彐℀-ℸ⅓-ↂ←-⏏①-⛃⼂⼅亠⼉⼌⼍⼎⼏⼐⼔⼕⼖⼘⼙⼚⼧⼫⼴⼹ヰ-ヿ、-〄〆-〿ㄅ-ㄬ]/u';
    //地番エラーパターン２ 「-0」「－０」を含む、ＡからＺを含む、工、ヨ、ユ、フと数字の組み合わせ、最後１字が数字・カタカナ・＊以外
    //const BUKKEN_ADDR_PATTERN2 = '/[Ａ-Ｚ]|[工|ヨ|ユ|フ][０-９]|[０-９][工|ヨ|ユ|フ]|[工|ヨ|ユ|フ]$|[^０-９ァ-ヶ＊]$/u';
    const BUKKEN_ADDR_PATTERN2 = '/[Ａ-Ｚ]|[工|ヨ|ユ|フ][０-９]|[０-９][工|ヨ|ユ|フ]|[工|ヨ|ユ|フ]$|[^０-９ァ-ヶ＊]$/u';
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

    /**
     * 受付番号が正しいかチェックする
     * @param $str
     * @return int
     */
    public function isValidReceiptNo($str) {
        if (preg_match(self::RECEIPTNO_PATTERN, $str)==true) {
            preg_match(self::RECEIPTNO_PATTERN, $str, $match);
            if ($match[0] == null){
                return false;
            }
            return true;
        } else {
            return false;
        }
        //return preg_match(self::RECEIPTNO_PATTERN,$str);
    }

    /**
     * 正しい受付番号を返す　【第１２３４５ー（あ）号】を第１２３４５-(あ)号にする
     * @param $str
     * @return mixed
     */
    public function getReceiptNo($str) {
        preg_match(self::RECEIPTNO_PATTERN, $str,$match);
        if ($match[0] == null) {
            $strtmp = str_replace('【','',$str);
            $strtmp = str_replace('】','',$strtmp);
            return $strtmp;
        } else {
            return $match[0];
        }
        //preg_match(self::RECEIPTNO_PATTERN, $str, $match);
        //return $match[0];
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
            if (preg_match(self::SOTOFUDE_PATTERN, $str)==true) {
                preg_match(self::SOTOFUDE_PATTERN, $str, $match);
                if ($match[0] == null){
                    return false;
                }
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
        //return preg_match(self::GROUP_PATTERN,$str);
    }

    /**
     * 外筆を取得　外１
     * @param $str
     * @return mixed
     */
    public function getSotofude($str){
        preg_match(self::SOTOFUDE_PATTERN, $str, $match);
        if ($match[0] == null) {
            return $str;
        } else {
            return $match[0];
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
     * 地番が正しいかチェックする
     * @param $str
     * @return int
     */
    public function isValidBukkenAddr($str) {
        //preg_match(self::BUKKEN_ADDR_PATTERN, $str, $match);
        if (preg_match(self::BUKKEN_ADDR_PATTERN, $str)==false){
            preg_match(self::BUKKEN_ADDR_PATTERN2, $str, $match);
            if (preg_match(self::BUKKEN_ADDR_PATTERN2, $str)==false){
                return true;
            } else {
                return false;
            }
            //return true;
        } else {
            return false;
        }
    }

    public function isValidAfterChome($str){
        preg_match(self::BUKKEN_ADDR_PATTERN, $str, $match);
        if (preg_match(self::BUKKEN_ADDR_PATTERN, $str)==false) {
            preg_match(self::CHOME_PATTERN, $str, $match);
            if (preg_match(self::CHOME_PATTERN, $str) == false) {
                return true;

            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function isValidEdabango($str){
        //preg_match(self::EDA_PATTERN, $str, $match);
        //if (preg_match(self::EDA_PATTERN, $str)==true) {
            preg_match(self::EDA_OK_PATTERN, $str, $match);
            if (preg_match(self::EDA_OK_PATTERN, $str)==true) {
                preg_match(self::CHOME_PATTERN, $str, $match);
                if (preg_match(self::CHOME_PATTERN, $str) == false) {
                    return true;

                } else {
                    return false;
                }
            } else {
                return false;
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