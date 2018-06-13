<?php
/**
 * Created by PhpStorm.
 * User: hoshino
 * Date: 2018/06/06
 * Time: 17:13
 */

require_once(LIB_DIR.'/model/ExcelModelBase.php');

class FormOcrReportExcel extends ExcelModelBase {
    // AddressModifierクラス
    private $addrMod;
    // ファイルデータそのもの
    private $DataOrg;
    // EXCEL解析後データ
    private $excelDataOrg;
    /**
     * コンストラクタ
     * @param unknown $report1CsvPath CSV
     * @param string $delimiter 基本的には「,」
     */
    public function __construct($filePath, $sheetIndex) {

        parent::__construct($filePath, $sheetIndex);

    }

    //////////////////////////////////////////////////////////////////////
    // 初期化
    //////////////////////////////////////////////////////////////////////

    /**
     * ファイルの存在確認とデータの読み込み
     */
    private function initFormOcrReport1() {

        $this->excelDataOrg = $this->getExcelBodyData(false);
    }

}