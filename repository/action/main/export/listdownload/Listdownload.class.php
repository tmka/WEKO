<?php
// --------------------------------------------------------------------
//
// $Id: Listdownload.class.php 56716 2015-08-19 14:05:15Z tomohiro_ichikawa $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

include_once MAPLE_DIR.'/includes/pear/File/Archive.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/action/main/export/ExportCommon.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryDownload.class.php';
require_once WEBAPP_DIR. '/modules/repository/action/edit/cinii/ElsCommon.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryOutputTSV.class.php';

class Repository_Action_Main_Export_Listdownload extends RepositoryAction
{

    // ダウンロード用メンバ
    var $uploadsView = null;
    
    /**
     * ダウンロード形式のタイプ
     * 0・・・WEKOImport形式
     * 1・・・BIBTEX形式
     * 2・・・OAI-PMH形式
     * 3・・・SWRC形式
     * 4・・・ELS形式
     * 5・・・TSV形式
     * 
     * @var integer
     */
    public $select_export= null;
    
    /**
     * export対象のアイテムID一覧
     *
     * @var array
     */
    public $exportItemId = null;
    
    /**
     * export対象のアイテムNO一覧
     *
     * @var array
     */
    public $exportItemNo = null;
    
    /**
     * 改行(Linux)
     *
     * @var string
     */
    private $LF = "\n";
    
    /**
     * 改行(Windouws)
     *
     * @var string
     */
    private $CRLF = "\r\n";
    
    /**
     * サーバー名
     *
     * @var string
     */
    private $server_name = null;
    /**
     * 現在日時
     *
     * @var Date
     */
    private $responseDate = null;
    /**
     * SettionのsmartyAssignを格納するオブジェクト
     *
     * @var Object
     */
    private $smartyAssign = null;
    
    /**
     * 一覧画面の表示対象となった、アイテム情報
     *
     * @var array
     */
    private $item_infos = null;
    
    /**
     * 作業用ディレクトリ
     *
     * @var string
     */
    private $tmp_dir = null;
    
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function executeApp()
    {
        ini_set('memory_limit', -1);
        // セッション情報が設定されていない場合は、異常終了とする
        if ($this->Session != null) {
            // エラー処理を記述する。（未実装）
            // return 'false'
        }

        // 共通の初期処理
        $result = $this->initAction();
        if ( $result == false ){
            // 未実装
            print "初期処理でエラー発生";
        }
        
        // Modify Export data not mediation session for all_Export and contens_all_print Y.Nakao 2013/05/09 --start-
        
        // 一覧画面の表示対象となった、アイテム情報を取得する
        $this->Session->removeParameter("item_info");
        if($this->exportItemId == null || !is_array($this->exportItemId) || count($this->exportItemId)==0){
            return 'false';
        }
        $this->item_infos = array();
        for($ii=0;$ii<count($this->exportItemId);$ii++)
        {
            $itemData = array();
            $this->getItemTableData($this->exportItemId[$ii], 
                                    $this->exportItemNo[$ii], 
                                    $itemData, 
                                    $errMsg);
            array_push($this->item_infos, array('item_id' => $itemData['item'][0]['item_id'], 
                                                'item_no' => $itemData['item'][0]['item_no'], 
                                                'ins_user_id' => $itemData['item'][0]['ins_user_id'], 
                                                'title' => $itemData['item'][0]['title'], 
                                                'title_english' => $itemData['item'][0]['title_english']));
        }
        // Modify Export data not mediation session for all_Export and contens_all_print Y.Nakao 2013/05/09 --end-
        
            
        // 作業用ディレクトリ作成
        $this->infoLog("businessWorkdirectory", __FILE__, __CLASS__, __LINE__);
        $businessWorkdirectory = BusinessFactory::getFactory()->getBusiness("businessWorkdirectory");
        
        $this->tmp_dir = $businessWorkdirectory->create();

        // WEKOImport形式の場合
        if($this->select_export == 0){
            // WEKOImportファイル作成/ダウンロード
            $this->downloadWekoimportFile();
        }
        //BIBTEX形式の場合
        else if($this->select_export == 1){
            // BIBTEXファイル作成/ダウンロード
            $this->downloadBibtexFile();
        }
        //OAI-PMH形式の場合
        else if($this->select_export == 2){
            // OAI-PMHファイル作成/ダウンロード
            $this->downloadOaiPmhFile();
        }
        //SWRC形式の場合
        else if($this->select_export == 3){
            // SWRCファイル作成/ダウンロード
            $this->downloadSwrcFile();
        }
        //ELS形式の場合
        else if($this->select_export == 4){
            // ELSファイル作成/ダウンロード
            $this->downloadElsFile();
            
        }
        else if($this->select_export == 5){
            // TSVファイル作成/ダウンロード
            $this->downloadTsvFile();
        }
        else{}  //あり得ない
        
        // アクション終了処理
        $result = $this->exitAction();  // トランザクションが成功していればCOMMITされる
        if ( $result == false ){
            // 未実装
            print "終了処理失敗";
        }
        
        // zipファイル損傷対応 2008/08/25 Y.Nakao --start--
        exit();
        // zipファイル損傷対応 2008/08/25 Y.Nakao --end--
    }

    /*
     * DBから指定された条件でExport情報を取得する
     * $query   ：クエリ
     * $param   ：パラメータ
     */
    function getExportInfo($query, $param){

        /*
         echo "実行クエリ=[" . $query . "]<br>";
         for ($cnt = 0; $cnt < count($param); $cnt++){
            echo "Param[" . $cnt . "]=[" . $param[$cnt] . "]<br>";
            }
            */

        // クエリ実行
        $export_infos = $this->Db->execute( $query, $param );

        // echo "レコード件数=[" . count($export_infos) . "]<br>";

        // データが取得できなかった場合
        if (!(isset($export_infos[0]))){
            // エラー処理を記述（未実装）
            // echo "取得データ件数０だよ<br>";
            // Exception をThrow

            // DB登録時のエラー処理を記述（共通化する）
        }

        // 実行結果がエラーの場合
        if ( $export_infos == false ){
            // エラー処理を記述（未実装）
            // echo "DB実行時にエラー発生<br>";
            // Exception をThrow
            // DB登録時のエラー処理を記述（共通化する）
        }
        return $export_infos;
    }
    /**
     * Body文字列から、指定されたタグ部分を切り取る処理
     *
     * @param string $body Body文字列
     * @param string $startStr 切り取り開始文字列
     * @param string $endStr 切り取り終了後削除する文字列
     */
    private function getTagContent($body,$tagStr){
        // 改行を削除
        $body = str_replace($this->CRLF,'',$body);
        $body = str_replace($this->LF,'',$body);
        $search = "/(<".$tagStr.".*>.*<\/".$tagStr.">)/";
        
        if(preg_match($search,$body,$matchs)){
            //matchi!!
            return $matchs[0];
        }else{
            //no match!
            return '';
        }
    }
    
    /**
     * 日時をY-m-d H:i:s形式に変換する
     *
     * @param integer 0
     * @return Y-m-d H:i:s形式の日時
     */
    private function dateGet ($tmp) {
        if ($tmp=='') {
            $tmp = 0;
        }
        
        $return_date = date("Y-m-d H:i:s");
        $tmp2 = date("Z");
        $tmp2 = $tmp2 / 3600;
        $tmp2 = $tmp2 - $tmp;
        if ($tmp2 >= 0) {
            $return_date .= '+'.$tmp2;
        } else {
            $return_date .= '-'.$tmp2;
        }
        $return_date = $this->dateChg ($return_date);
        
        return $return_date;
    }

    /**
     * 時間をH:i:s形式に変換する
     *
     * @param Y-m-d 00:00:00+0形式の日時
     * @return Y-m-d H:i:s形式の日時
     */
    private function dateChg ($tmp) {
        if (ereg("^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$", $tmp)) {
            $tmp_date = $tmp;
        } else {
            $tmp = str_replace('-', ' ', $tmp);
            $tmp = str_replace(':', ' ', $tmp);
            $tmp = str_replace('.', ' ', $tmp);
            $tmp = str_replace('+', ' ', $tmp);
            $dateArray = split(" ", $tmp, 7);
            // ( 時 , 分 , 秒 , 月 , 日 , 年 , サマータイム ) 
            $DATE = new Date($dateArray[0].'-'.$dateArray[1].'-'.$dateArray[2].' '.$dateArray[3].':'.$dateArray[4].':'.$dateArray[5]);
            $DATE->toUTC();
            $tmp_date = $DATE->getDate(DATE_FORMAT_ISO_EXTENDED);
        }
        
        return $tmp_date;
    }
    
    /**
     * WEKOImportファイル作成/ダウンロード処理
     *
     */
    private function downloadWekoimportFile(){
        // Exportファイルはimport.xml（仮）とする
        $filename = $this->tmp_dir . "import.xml";

        $buf = "<?xml version=\"1.0\"?>\n" .
               "    <export>\n";

        // 管理画面からパラメータ取得
        $query = "SELECT param_value ".
                 "FROM ". DATABASE_PREFIX ."repository_parameter ".
                 "WHERE param_name = 'export_is_include_files' ; ";
        $result = $this->Db->execute( $query, $param );
        if($result === false) {
            echo 'false';
            return;
        }
        if($result[0]['param_value']==1){
            $file_flg = true;
        } else {
            $file_flg = false;
        }
        
        // new export common class
        $export_common = new ExportCommon($this->Db, $this->Session, $this->TransStartDate);
        if($export_common === null){
            echo 'false';
            return false;
        }
        
        // 指定されているアイテムから付随する情報を取得する
        $output_files = array();
        foreach ($this->item_infos as $key => $value){

            // アイテムを取得する
            if(!array_key_exists("export_flg", $value) || (array_key_exists("export_flg", $value) && isset($value["export_flg"]) && $value["export_flg"])){
                // Exportファイル生成
                // 2008/07/09 Y.Nakao --start--
                $export_info = $export_common->createExportFile($value, $this->tmp_dir, $file_flg, "", true);
                // 2008/07/09 Y.Nakao --end--
                $buf .= $export_info["buf"];
                array_push( $output_files, $export_info["output_files"] );
            }

        } // アイテムのループ
        $buf .= "   </export>\n";

        // Zipファイル生成
        $zip_file = "export.zip";
        
        // ファイルオープン
        $fp = fopen( $filename, "w" );
        if (!$fp){
            // ファイルのオープンに失敗した場合
            // エラー処理を実行（未実装）
            echo "ファイルオープンエラー<br>";
        }

        // Txtファイルへ出力する
        fputs($fp, $buf);
        fclose($fp);

        // 出力したファイルをZip形式で圧縮する
        array_push( $output_files, $filename );

        File_Archive::extract(
            $output_files,
            File_Archive::toArchive($zip_file, File_Archive::toFiles( $this->tmp_dir ))
        );
        
        //ダウンロードアクション処理
        // Add RepositoryDownload action 2010/03/30 A.Suzuki --start--
        $repositoryDownload = new RepositoryDownload();
        $repositoryDownload->downloadFile($this->tmp_dir.$zip_file, "export.zip");
        // Add RepositoryDownload action 2010/03/30 A.Suzuki --end--
    }

    /**
     * BIBTEXファイル作成/ダウンロード処理
     *
     */
    private function downloadBibtexFile(){
        $buf = "";  //出力文字列
        // Exportファイルはbibtex.txtとする
        $filename = "bibtex.txt";
        $filepath = $this->tmp_dir .$filename;
        
        // ファイルをオープンする
        $fp = fopen( $filepath, "w" );
        if (!$fp){
            // ファイルのオープンに失敗した場合
            // エラー処理を実行（未実装）
            echo "ファイルオープンエラー<br>";
        }
        
        foreach ($this->item_infos as $key => $value){
            if(!array_key_exists("export_flg", $value) || (array_key_exists("export_flg", $value) && isset($value["export_flg"]) && $value["export_flg"])){
                // Exportファイル生成
                $bibtex_export_url = BASE_URL."/?action=repository_bibtex";
                $bibtex_export_url .= "&itemId=";
                $bibtex_export_url .= $value["item_id"];
                $bibtex_export_url .= "&itemNo=";
                $bibtex_export_url .= $value["item_no"];
                
                $proxy = $this->getProxySetting();
                $option = "";
                if($proxy['proxy_mode'] == 1)
                {
                    $option = array( 
                            "timeout" => "10",
                            "allowRedirects" => true, 
                            "maxRedirects" => 3,
                            "proxy_host"=>$proxy['proxy_host'],
                            "proxy_port"=>$proxy['proxy_port'],
                            "proxy_user"=>$proxy['proxy_user'],
                            "proxy_pass"=>$proxy['proxy_pass']
                        );
                }else{
                    
                }
                $http = new HTTP_Request($bibtex_export_url, $option);
                $response = $http->sendRequest();
                $resBody="";
                if (!PEAR::isError($response)) { 
                    $resBody = $http->getResponseBody();        // get ResponseBody
                }else{
                    continue;
                }
                
                //2012/3/12 ファイル不備の場合データを無視する-- jin add--
                //エラーメッセージが返却された場合、無視する。
                if(($resBody == "This item is private.")
                || ($resBody == "Database access error.")
                || ($resBody == "This item data is not found.")
                || ($resBody == "This item has no mapping info.")){
                    $resBody = "";
                }
                
                $buf .= $resBody;
                
                // ファイルに出力
                fputs($fp, $resBody);
            }
            $bibtex_export_url = "";
        }
        
        //ファイルクローズ
        fclose($fp);
        
        // 出力データなし
        if($buf == ""){
            //ERROR
            unlink($filename);
            $filename = "error.txt";
            $filepath = $this->tmp_dir .$filename;
            $fp = fopen( $filepath, "w" );
            $buf = "Data was not able to be outputted.";
            fputs($fp, $buf);
            // ファイルクローズ
            fclose($fp);
        }

        //ダウンロードアクション処理
        $repositoryDownload = new RepositoryDownload();
        $repositoryDownload->downloadFile($filepath, $filename);
    }
    
    /**
     * OAI-PMHファイル作成/ダウンロード処理
     *
     */
    private function downloadOaiPmhFile(){
        // Exportファイルはoai_pmh.xmlとする
        $filename = "oai_pmh.xml";
        $filepath = $this->tmp_dir .$filename;
        
        // ファイルオープン
        $fp = fopen( $filepath, "w" );
        if (!$fp){
            // ファイルのオープンに失敗した場合
            // エラー処理を実行（未実装）
            echo "ファイルオープンエラー<br>";
        }
        
        // oaipmh export url
        $oai_pmh_export_url = "";
        $recode_type_start = "";    //<GetRecords>か？<ListRecodes>か?
        $recode_type_end = "";      //</GetRecords>か？</ListRecodes>か?
        //downloadOKか？
        $isDownloadFlg = false;
        
        $this->server_name = BASE_URL;
        if(substr($this->server_name, -1, 1)!="/"){
            $this->server_name .= "/";
        }
        $this->responseDate = $this->dateGet(0);
        
        $item_infos_cnt = count($this->item_infos);
        if($item_infos_cnt ==1){
            $item_id = sprintf("%08d",$item_infos[0]["item_id"]);
            $temp_host = "oai:".$_SERVER['HTTP_HOST'].":".$item_id;
            $oai_hissu = ' verb="GetRecord" metadataPrefix="junii2" identifier="oai:'.$_SERVER['HTTP_HOST'].':'.$item_id.'"';
            $recode_type_start = '<GetRecord>';
            $recode_type_end = '</GetRecord>';
        }
        else if($item_infos_cnt >1){
            $oai_hissu = ' verb="ListRecords" metadataPrefix="junii2"';
            $recode_type_start = '<ListRecords>';
            $recode_type_end = '</ListRecords>';
        }else{
            // 0件の場合
            return 'false';
        }
        //ヘッダの生成
        $header = '<?xml version="1.0" encoding="UTF-8" ?>';
        $header .= '<OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">';
        $header .= '<responseDate>'.$this->responseDate.'</responseDate>';
        $header .= '<request'.$oai_hissu.'>'.$this->server_name.'</request>';
        $header .= $recode_type_start;
        // ヘッダーの書き込み
        fputs($fp, $header);
        
        foreach ($this->item_infos as $key => $value){
            if(!array_key_exists("export_flg", $value) || (array_key_exists("export_flg", $value) && isset($value["export_flg"]) && $value["export_flg"])){
                $item_id = sprintf("%08d",$value["item_id"]);
                // Exportファイル生成
                $oai_pmh_export_url = BASE_URL."/?action=repository_oaipmh";
                $oai_pmh_export_url .= "&verb=GetRecord&metadataPrefix=junii2&identifier=oai:";
                $oai_pmh_export_url .= $_SERVER['HTTP_HOST'].":".$item_id;
                
                $proxy = $this->getProxySetting();
                $option = "";
                if($proxy['proxy_mode'] == 1)
                {
                    $option = array( 
                            "timeout" => "10",
                            "allowRedirects" => true, 
                            "maxRedirects" => 3,
                            "proxy_host"=>$proxy['proxy_host'],
                            "proxy_port"=>$proxy['proxy_port'],
                            "proxy_user"=>$proxy['proxy_user'],
                            "proxy_pass"=>$proxy['proxy_pass']
                        );
                }else{
                }
                
                $http = new HTTP_Request($oai_pmh_export_url, $option);
                $response = $http->sendRequest();
                $resBody="";
                if (!PEAR::isError($response)) { 
                    $resBody = $http->getResponseBody();        // get ResponseBody
                }else{
                    continue;
                }
                
                //$resBodyからrecord タグのみ切り取る
                $tempbuf = $this->getTagContent($resBody,"record");
                
                // フォーマットチェック
                try{
                    $xml_parser = xml_parser_create();
                    xml_parse_into_struct($xml_parser,$tempbuf,$vals);
                    xml_parser_free($xml_parser);
                }catch (Exception $ex){
                    continue;   // 無視して次のレコードへ
                }
                
                ////TODO:2012/3/12 OAI-PMHテストコード jin add--start--
                //if(strstr($tempbuf,"metadata") === false){
                //    $tempbuf = "";
                //}else{
                //    $isDownloadFlg = true;
                //}
                ////TODO:2012/3/12 OAI-PMHテストコード jin add--end--
                
                //download is OK?
                if(strlen($tempbuf)>=1){
                    $isDownloadFlg = true;
                    // ファイルへ出力する
                    fputs($fp, $tempbuf);
                }
            }
        }
        // フッダの生成
        $fooder = "";
        $fooder .= $recode_type_end;
        $fooder .= "</OAI-PMH>";
        // ファイルへ出力する
        fputs($fp, $fooder);
        
        //ファイルクローズ
        fclose($fp);
        
        //出力データが無い場合
        if($isDownloadFlg == false){
            //ERROR
            unlink($filename);
            $filename = "error.txt";
            $filepath = $this->tmp_dir .$filename;
            $fp = fopen( $filepath, "w" );
            $buf = "Data was not able to be outputted.";
            fputs($fp, $buf);
            
            // ファイルクローズ
            fclose($fp);
        }
        
        //ダウンロードアクション処理
        $repositoryDownload = new RepositoryDownload();
        $repositoryDownload->downloadFile($filepath, $filename);
    }
    
    /**
     * SWRCファイル作成/ダウンロード処理
     *
     */
    private function downloadSwrcFile(){
        // Exportファイルはswrc.xmlとする
        $filename = "swrc.xml";
        $filepath = $this->tmp_dir .$filename;

        //download is OK?
        $isDownloadFlg = false;
        // swrc export url
        $swrc_export_url = "";
        
        // ファイルオープン
        $fp = fopen( $filepath, "w" );
        if (!$fp){
            // ファイルのオープンに失敗した場合
            // エラー処理を実行（未実装）
            echo "ファイルオープンエラー<br>";
        }

        //ヘッダの生成
        $header = '<?xml version="1.0" encoding="UTF-8" ?>'.$this->LF.
        '<rdf:RDF'.$this->LF.
        'xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"'.$this->LF.
        'xmlns:owl="http://www.w3.org/2002/07/owl#"'.$this->LF.
        'xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"'.$this->LF.
        'xmlns:swrc="http://swrc.ontoware.org/ontology#"'.$this->LF.
        'xmlns:dc="http://purl.org/dc/elements/1.1/">'.$this->LF.$this->LF;
        
        fputs($fp, $header);

        foreach ($this->item_infos as $key => $value){
            if(!array_key_exists("export_flg", $value) || (array_key_exists("export_flg", $value) && isset($value["export_flg"]) && $value["export_flg"])){
                // Exportファイル生成
                $swrc_export_url = BASE_URL."/?action=repository_swrc";
                $swrc_export_url .= "&itemId=";
                $swrc_export_url .= $value["item_id"];
                $swrc_export_url .= "&itemNo=";
                $swrc_export_url .= $value["item_no"];
                
                $proxy = $this->getProxySetting();
                $option = "";
                if($proxy['proxy_mode'] == 1)
                {
                    $option = array( 
                            "timeout" => "10",
                            "allowRedirects" => true, 
                            "maxRedirects" => 3,
                            "proxy_host"=>$proxy['proxy_host'],
                            "proxy_port"=>$proxy['proxy_port'],
                            "proxy_user"=>$proxy['proxy_user'],
                            "proxy_pass"=>$proxy['proxy_pass']
                        );
                }else{
                }
                
                $http = new HTTP_Request($swrc_export_url, $option);
                $response = $http->sendRequest();
                $resBody="";
                if (!PEAR::isError($response)) { 
                    $resBody = $http->getResponseBody();        // get ResponseBody
                }else{
                    continue;
                }
                
                //$resBodyからrdf:Description タグのみ切り取る
                $tempbuf = $this->getTagContent($resBody,"rdf:Description");
                
                // フォーマットチェック
                try{
                    $xml_parser = xml_parser_create();
                    xml_parse_into_struct($xml_parser,$tempbuf,$vals);
                    xml_parser_free($xml_parser);
                }catch (Exception $ex){
                    continue;   // 無視して次のレコードへ
                }
                
                // ファイルへ出力
                fputs($fp, $tempbuf);
                
                // download is OK?
                if(strlen($tempbuf)>=1){
                    $isDownloadFlg = true;
                }
            }
        }
        //フッダの生成
        $fooder = "</rdf:RDF>";
        fputs($fp, $fooder);
        
        // ファイルクローズ
        fclose($fp);
        
        // 出力データが無い場合
        if($isDownloadFlg == false){
            //ERROR
            unlink($filename);
            $filename = "error.txt";
            $filepath = $this->tmp_dir .$filename;
            $fp = fopen( $filepath, "w" );
            $buf = "Data was not able to be outputted.";
            fputs($fp, $buf);
            // ファイルクローズ
            fclose($fp);
        }

        //ダウンロードアクション処理
        $repositoryDownload = new RepositoryDownload();
        $repositoryDownload->downloadFile($filepath, $filename);
    }
    
    /**
     * ELSファイル作成/ダウンロード処理
     *
     */
    private function downloadElsFile(){
        // Exportファイルはels.tsvとする
        $filename = "els.tsv";
        $filepath = $this->tmp_dir .$filename;
        
        $this->smartyAssign = $this->Session->getParameter("smartyAssign");
        $els_common = new ElsCommon($this->Session, $this->Db, $this->smartyAssign);
        
        //出力文字列
        $buf = "";
        
        // els export url 
        $els_export_url = "";

        $export_data = array();
        foreach ($this->item_infos as $key => $value){
            if(!array_key_exists("export_flg", $value) || (array_key_exists("export_flg", $value) && isset($value["export_flg"]) && $value["export_flg"])){
                $export_data[$key] = $value;
            }
        }
        //Common呼び出し
        $els_common->createElsData($export_data, $buf, $result_message, $els_file_data);
        
        // ファイルオープン
        $fp = fopen( $filepath, "w" );
        if (!$fp){
            // ファイルのオープンに失敗した場合
            // エラー処理を実行（未実装）
            echo "ファイルオープンエラー<br>";
        }
        //ファイルへ出力する
        fputs($fp, $buf);
        fclose($fp);
        
        //出力データが無い場合
        if(strlen($buf) < 1){
            unlink($filename);
            $filename = "error.txt";
            $filepath = $this->tmp_dir .$filename;
            $fp = fopen( $filepath, "w" );
            // エラーメッセージを出力
            $buf = "Data was not able to be outputted.";
            //ファイルへ出力する
            fputs($fp, $buf);
            fclose($fp);
        }

        //ダウンロードアクション処理
        $repositoryDownload = new RepositoryDownload();
        $repositoryDownload->downloadFile($filepath, $filename);
    }
    /**
     * TSVファイル作成/ダウンロード処理
     *
     */
    private function downloadTsvFile(){
        $filename = "export.tsv";
        $filepath = $this->tmp_dir .$filename;
        $repositoryOutputTSV = new RepositoryOutputTSV($this->Db, $this->Session);
        // TSV作成
        if (!$repositoryOutputTSV->outputTsv( $filepath, $this->item_infos )){
            $filename = "error.txt";
            $filepath = $this->tmp_dir .$filename;
            $fp = fopen( $filepath, "w" );
            // エラーメッセージを出力
            $buf = "Data was not able to be outputted.";
            //ファイルへ出力する
            fputs($fp, $buf);
            fclose($fp);
        }
        
        $output_files = array();
        array_push( $output_files, $filepath );
        $zip_file = "export.zip";
        $this->createExportZipFile($output_files, $zip_file);
        //ダウンロードアクション処理
        $repositoryDownload = new RepositoryDownload();
        $repositoryDownload->downloadFile($this->tmp_dir.$zip_file, "exportTSV.zip");
        
    }
    
    /**
     * zipファイル作成処理
     *
     * @param output_files zipファイルにするファイルリスト(エクスポートアイテムのファイル以外を追加しておく)
     * @param zipFileName zipファイル名
     */
    private function createExportZipFile($output_files, $zipFileName){
        // new export common class
        $export_common = new ExportCommon($this->Db, $this->Session, $this->TransStartDate);
        if($export_common === null){
            echo 'false';
            return false;
        }
        // 管理画面からパラメータ取得
        $query = "SELECT param_value ".
                 "FROM ". DATABASE_PREFIX ."repository_parameter ".
                 "WHERE param_name = 'export_is_include_files' ; ";
        $result = $this->Db->execute( $query );
        if($result === false) {
            echo 'false';
            return;
        }
        if($result[0]['param_value']==1){
            $file_flg = true;
        } else {
            $file_flg = false;
        }
    
        // 指定されているアイテムから付随する情報を取得する
        foreach ($this->item_infos as $key => $value){
            // アイテムを取得する
            if(!array_key_exists("export_flg", $value) || (array_key_exists("export_flg", $value) && isset($value["export_flg"]) && $value["export_flg"])){
                // Exportファイル生成
                $export_info = $export_common->createExportFile($value, $this->tmp_dir, $file_flg, "", true);
                array_push( $output_files, $export_info["output_files"] );
            }

        } // アイテムのループ

        File_Archive::extract(
            $output_files,
            File_Archive::toArchive($zipFileName, File_Archive::toFiles( $this->tmp_dir ))
        );
    }
    
}
?>
