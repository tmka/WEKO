<?php
/**
 * $Id: Sendsitelicensemail.class.php 51333 2015-04-01 05:35:29Z tomohiro_ichikawa $
 * 
 * アイテム削除ビジネスクラス
 * 
 * @author IVIS
 * @sinse 2014/11/11
 */
require_once WEBAPP_DIR. '/modules/repository/components/FW/BusinessBase.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/LogAnalyzor.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/business/Logmanager.class.php';

class Repository_Components_Business_Sendsitelicensemail extends BusinessBase
{
    // 送付するZIPを作成する作業ディレクトリ
    private $zip_dir = "";
    // レポートファイルを作成する作業ディレクトリ
    private $tmp_file_dir = "";
    
    /**
     * constructer
     */
    public function __construct()
    {
        // メンバ変数は文字列連結で定義できないのでコンストラクタで設定する
        $this->zip_dir = WEBAPP_DIR."/uploads/repository/";
    }
    
    /**
    * 作業ディレクトリ作成処理
    * 
    */
    public function createSitelicenseMailTmpDir() {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        // create temporary directory
        $this->infoLog("businessWorkDirectory", __FILE__, __CLASS__, __LINE__);
        $businessWorkDirectory = BusinessFactory::getFactory()->getBusiness("businessWorkdirectory");
        $this->tmp_file_dir = $businessWorkDirectory->create();
    }
    
    /**
    * サイトライセンスメール送信の可否の判定処理
    * 
    * @return bool
    */
    public function checkSendSitelicense() {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        // サイトライセンスメール送信フラグのチェック
        $query = "SELECT param_value FROM ". DATABASE_PREFIX. "repository_parameter ".
                 "WHERE param_name = ? ;";
        $params = array();
        $params[] = "send_sitelicense_mail_activate_flg";
        $result = $this->Db->execute($query, $params);
        if($result === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        // 送信フラグが0ならfalse
        if($result[0]["param_value"] == 0) {
            return false;
        }
        
        // サイトライセンスメール対象者の確認
        $query = "SELECT no FROM ". DATABASE_PREFIX. "repository_send_mail_sitelicense ;";
        $result = $this->Db->execute($query);
        if($result === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        // 送信対象者がいればfalse
        if(count($result) > 0) {
            return false;
        }
        
        // すべての条件をクリアすればtrueを返す
        return true;
        
    }
    
    /**
    * サイトライセンスメール送信対象リストの作成処理
    * 
    * @return bool
    */
    public function insertSendSitelicenseMailList() {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        // サイトライセンス基本情報の取得
        $query = "SELECT * FROM ". DATABASE_PREFIX. "repository_sitelicense_info ".
                 "WHERE is_delete = ? ;";
        $params = array();
        $params[] = 0;
        $slBaseInfo = $this->Db->execute($query, $params);
        if($slBaseInfo === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        // サイトライセンス基本情報のレコード数だけ行う
        for($ii = 0; $ii < count($slBaseInfo); $ii++) {
            // 送信対象者リストテーブルへの挿入
            $query = "INSERT INTO ". DATABASE_PREFIX. "repository_send_mail_sitelicense ".
                     "(no, organization_name, mail_address) ".
                     "VALUES ".
                     "(?, ?, ?)";
            $params = array();
            $params[] = $slBaseInfo[$ii]["organization_id"];
            $params[] = $slBaseInfo[$ii]["organization_name"];
            $params[] = $slBaseInfo[$ii]["mail_address"];
            $result = $this->Db->execute($query, $params);
            if($result === false) {
                $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
                throw new AppException($this->Db->ErrorMsg());
            }
        }
        
        return true;
    }
    
    /**
    * サイトライセンスメール送信対象者取得処理
    * 
    * @return array
    */
    public function getSendSitelicenseUser() {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        // サイトライセンスユーザー情報配列
        $sl = array();
        
        // サイトライセンスメール送信対象者を先頭の1件取得する
        $query = "SELECT * FROM ". DATABASE_PREFIX. "repository_send_mail_sitelicense ". 
                 "ORDER BY no ASC ".
                 "LIMIT 0,1 ;";
        $slBaseInfo = $this->Db->execute($query);
        if($slBaseInfo === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        // テーブルにレコードが無い場合は処理を終了する
        if(count($slBaseInfo) == 0) {
            // 空のまま返す
            return $sl;
        }
        
        // 出力用配列の作成
        $sl[0]["organization_id"] = $slBaseInfo[0]["no"];
        $sl[0]["organization_name"] = $slBaseInfo[0]["organization_name"];
        $sl[0]["mail_address"] = $slBaseInfo[0]["mail_address"];
        
        return $sl;
    }
    
    /**
    * サイトライセンス送信対象者データの削除処理
    * 
    * @param int $id sitelicense id
    * 
    * @return bool
    */
    public function deleteSendSitelicenseuser($id) {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        // 指定したサイトライセンスIDのレコードを削除
        $query = "DELETE FROM ". DATABASE_PREFIX. "repository_send_mail_sitelicense ".
                 "WHERE no = ? ;";
        $params = array();
        $params[] = $id;
        $result = $this->Db->execute($query, $params);
        if($result === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        return true;
    }
    
    /**
    * サイトライセンスID毎のログ件数取得処理
    * 
    * @param string $start_date
    * @param string $finish_date
    * @param int $sitelicense_id
    * @param int $operation_id
    * 
    * @return array
    */
    public function getLogCountBySitelicenseId($start_date, $finish_date, $sitelicense_id, $operation_id) {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        if($operation_id != Repository_Components_Business_Logmanager::LOG_OPERATION_SEARCH)
        {
            $subQuery = Repository_Components_Business_Logmanager::getSubQueryForAnalyzeLog();
        }
        else
        {
            $subQuery = Repository_Components_Business_Logmanager::getSubQueryForAnalyzeLog(Repository_Components_Business_Logmanager::SUB_QUERY_TYPE_RANKING);
        }
        
        $query = " SELECT COUNT(LOG.record_date) AS CNT, ". 
                          Repository_Components_Loganalyzor::dateformatMonthlyQuery("LOG"). 
                 $subQuery[Repository_Components_Business_Logmanager::SUB_QUERY_KEY_FROM].
                 " WHERE ".$subQuery[Repository_Components_Business_Logmanager::SUB_QUERY_KEY_WHERE].
                 " AND LOG.record_date >= ? ". 
                 " AND LOG.record_date <= ? ". 
                 " AND LOG.operation_id = ? ". 
                 " AND LOG.site_license_id = ? ". 
                 " AND NOT(LOG.search_keyword=?) ". 
                 Repository_Components_Loganalyzor::perMonthlyQuery(). " ;";
        $params = array();
        $params[] = $start_date;
        $params[] = $finish_date;
        $params[] = $operation_id;
        $params[] = $sitelicense_id;
        $params[] = ''; // search_keyword
        $result = $this->Db->execute($query, $params);
        if($result === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        $this->debugLog($query, __FILE__, __CLASS__, __LINE__);
        $this->debugLog(print_r($params, true), __FILE__, __CLASS__, __LINE__);
        $this->debugLog(print_r($result, true), __FILE__, __CLASS__, __LINE__);
        
        // サイトライセンスフラグON、サイトライセンスID=0での追加計算
        // WEKO ver.2.1.7⇒ver.2.2.0へのアップデート月以降はこの処理は不要
        // しかしサイトライセンスフィードバックメールの再送機能が追加されるため、
        // 処理としてここに記載
        $ret = $this->calcSearchLogBySitelicenseFlg($start_date, $finish_date, $sitelicense_id, $operation_id, $subQuery);
        
        // キーワード検索数は要素1で固定である
        if(count($ret)> 0)
        {
            $result[0]['CNT'] = $result[0]['CNT'] + $ret[0]['CNT'];
        }
        
        $this->debugLog(print_r($result, true), __FILE__, __CLASS__, __LINE__);
        
        // サイトライセンス利用統計再送機能追加時に
        // さらに過去のログも問題なく集計できるようにしなくてはいけない
        
        return $result;
    }
    
    /**
     * calc search log result by sitelicense flag
     * サイトライセンスフラグからサイトライセンス機関の検索数を取得する
     *
     * @param string $start_date
     * @param string $finish_date
     * @param int $sitelicense_id
     * @param int $operation_id
     * @param array $subQuery
     * @return array: サイトライセンス機関の検索結果
     *           ex) array[0]['CNT'] = 123
     *                       ['MONTHLY'] = '07'
     */
    private function calcSearchLogBySitelicenseFlg($start_date, $finish_date, $sitelicense_id, $operation_id, $subQuery){
        $result = array();
        
        if($this->isCalcLogBySitelicenseFlg($start_date, $finish_date)){
            // サイトライセンスアクセス特定用のWhere句作成
            $whereString = $this->createWhereQueryForSpecificSiteLicenseLog($start_date, $finish_date, $sitelicense_id);
            
            // サイトライセンスフラグON、サイトライセンスID=0での追加集計
            $query = " SELECT COUNT(LOG.record_date) AS CNT, ". 
                              Repository_Components_Loganalyzor::dateformatMonthlyQuery("LOG"). 
                     $subQuery[Repository_Components_Business_Logmanager::SUB_QUERY_KEY_FROM].
                     " WHERE ".$subQuery[Repository_Components_Business_Logmanager::SUB_QUERY_KEY_WHERE].
                     " AND LOG.record_date >= ? ".
                     " AND LOG.record_date <= ? ". 
                     " AND LOG.operation_id = ? ". 
                     " AND LOG.site_license = ? ".
                     " AND LOG.site_license_id = ? ".
                     " AND NOT(LOG.search_keyword=?) ".
                     $whereString. 
                     Repository_Components_Loganalyzor::perMonthlyQuery(). " ;";
            
            $params = array();
            $params[] = $start_date;
            $params[] = $finish_date;
            $params[] = $operation_id;
            $params[] = 1; // log.site_license
            $params[] = 0; // log.site_license_id
            $params[] = ''; // search_keyword
            
            $this->debugLog($query, __FILE__, __CLASS__, __LINE__);
            $this->debugLog(print_r($params, true), __FILE__, __CLASS__, __LINE__);
            
            $result = $this->Db->execute($query, $params);
            if($result === false){
                $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
                throw new AppException($this->Db->ErrorMsg());
            }
        }
        
        return $result;
    }
    
    /**
    * サイトライセンスID毎の操作ログ取得処理
    * 
    * @param string $issn
    * @param string $start_date
    * @param string $finish_date
    * @param int $sitelicense_id
    * @param int $operation_id
    * 
    * @return array
    */
    public function getLogBySitelicenseId($issn, $start_date, $finish_date, $sitelicense_id, $operation_id) {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        if($operation_id != Repository_Components_Business_Logmanager::LOG_OPERATION_SEARCH) {
            $subQuery = Repository_Components_Business_Logmanager::getSubQueryForAnalyzeLog();
        } else {
            $subQuery = Repository_Components_Business_Logmanager::getSubQueryForAnalyzeLog(Repository_Components_Business_Logmanager::SUB_QUERY_TYPE_RANKING);
        }
        
        // 該当の期間の操作ログを全て取得する
        $logs = $this->searchOperationLog($subQuery, $start_date, $finish_date, $operation_id, $sitelicense_id);
        
        // ISSN毎の操作ログを集計する
        $result = $this->calcOperationLogCountByIssn($logs, $issn);
        
        // サイトライセンスフラグON、サイトライセンスID=0での追加計算
        // WEKO ver.2.1.7⇒ver.2.2.0へのアップデート月以降はこの処理は不要
        // しかしサイトライセンスフィードバックメールの再送機能が追加されるため、
        // 処理としてここに記載
        $ret = $this->calcLogBySitelicenseFlg($issn, $start_date, $finish_date, $sitelicense_id, $operation_id, $subQuery);
        
        // 取得した結果を加算する
        $result = $this->addResultForDownloadOrDetail($result, $ret);
        
        // サイトライセンスフラグNULL時の追加計算
        // WEKO ver.2.0.2⇒ver.2.0.3へのアップデート月以降はこの処理は不要
        // しかしサイトライセンスフィードバックメールの再送機能が追加されるため、処理としてここに記載する
        
        return $result;
    }
    
    /**
    * ISSN取得処理
    * 
    * @return array
    */
    public function getOnlineIssn() {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        // ISSN値を全て取得する
        $query = "SELECT issn, jtitle, jtitle_en, set_spec FROM ". DATABASE_PREFIX. "repository_issn ". 
                 "WHERE is_delete = ? ;";
        $params = array();
        $params[] = 0;
        $result = $this->Db->execute($query, $params);
        if($result === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        return $result;
    }
    
    /**
    * レポートファイル作成処理
    * 
    * @param string $file_name
    * @param string $file_body
    * 
    * @return bool
    */
    public function createReport($file_name, $file_body) {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        $BOM = pack('C*',0xEF,0xBB,0xBF);
        $logReport = fopen($this->tmp_file_dir.$file_name, "w");
        fwrite($logReport, $BOM.$file_body);
        fclose($logReport);
        
        return true;
    }
    
    /** 
     * 除外アイテムタイプサブクエリ取得処理
     * 
     * @param string $abbreviation
     * 
     * @return array
     */
    private function getExclusiveSitelicenseItemtype($abbreviation) {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        $query = "SELECT param_value FROM ".DATABASE_PREFIX ."repository_parameter ".
                 "WHERE param_name = ? ;";
        $params = array();
        $params = "site_license_item_type_id";
        $result = $this->Db->execute($query, $params);
        if($result === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        $sitelicense_item_type_id = array();
        if(strlen($result[0]["param_value"]) > 0) {
            $sitelicense_item_type_id = explode(",", $result[0]["param_value"]);
        }
        $item_type_id_query = Repository_Components_Loganalyzor::exclusiveSitelicenseItemtypeQuery($abbreviation, $sitelicense_item_type_id);
        
        return $item_type_id_query;
    }
    
    /**
    * ZIPファイル作成処理
    * 
    * @param string $zip_name
    * 
    * @return bool
    */
    public function compressToZip($zip_name) {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        // 一時ディレクトリを送付用のZIPに圧縮する
        $output_files = array($this->tmp_file_dir);
        File_Archive::extract($output_files, 
                              File_Archive::toArchive($zip_name, 
                                                      File_Archive::toFiles($this->zip_dir)
                                                     )
                             );
        // 一時ディレクトリ削除
        if ($handle = opendir($this->tmp_file_dir)) {
            while (false !== ($file = readdir($handle))) {
                chmod($this->tmp_file_dir. $file, 0777 );
                unlink($this->tmp_file_dir. $file);
            }
            closedir($handle);
        }
        chmod($this->tmp_file_dir, 0777 );
        rmdir($this->tmp_file_dir);
    }
    
    /**
     * return is exists record of sitelicense is ON and sitelicense_id=0
     * サイトライセンスフラグONでありながらサイトライセンスID=0があるかの判定結果を返す
     *
     * @param string $start_date
     * @param string $finish_date
     * @return boolean
     */
    private function isCalcLogBySitelicenseFlg($start_date, $finish_date){
        // 処理が必要かどうか(集計月内でサイトライセンスフラグONでありながらサイトライセンスID=0となっているレコードがあるかを判定する)
        $query = " SELECT log_no ". 
                 " FROM ". DATABASE_PREFIX. "repository_log ". 
                 " WHERE site_license = ? ". 
                 " AND site_license_id = ? ". 
                 " AND record_date >= ? ". 
                 " AND ? >= record_date ".
                 " LIMIT 0,1 ;";
        $params = array();
        $params[] = 1;
        $params[] = 0;
        $params[] = $start_date;
        $params[] = $finish_date;
        $ret = $this->Db->execute($query, $params);
        if($ret === false){
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        if(count($ret) > 0){
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * calc sitelicense usagestatics by log.sitelicense_flg
     * サイトライセンスフラグからサイトライセンス利用統計を取得する
     *
     * @param string $issn
     * @param string $start_date
     * @param string $finish_date
     * @param int $sitelicense_id
     * @param int $operation_id
     * @param array $subQuery: 同時アクセス除外用のクエリ
     * @return サイトライセンスフラグから判定した利用統計
     *         array[$ii]['CNT']
     *                   ['online_issn']
     *         月内のログに全てサイトライセンスIDが存在する場合は空配列を返す
     */
    private function calcLogBySitelicenseFlg($issn, $start_date, $finish_date, $sitelicense_id, $operation_id, $subQuery){
        $result = array();
        
        if($this->isCalcLogBySitelicenseFlg($start_date, $finish_date)){
            // サイトライセンスアクセス特定用のWhere句作成
            $whereString = $this->createWhereQueryForSpecificSiteLicenseLog($start_date, $finish_date, $sitelicense_id);
            
            // サイトライセンスフラグON、サイトライセンスID=0での追加集計
            // 該当の期間の操作ログを全て取得する
            $logs = $this->searchOperationLog($subQuery, $start_date, $finish_date, $operation_id, 0, $whereString);
            
            // ISSN毎の操作ログを集計する
            $result = $this->calcOperationLogCountByIssn($logs, $issn);
        }
        
        return $result;
    }
    
    /**
     * create query parts for specific Sitelicense access
     * サイトライセンスアクセス特定用のwhere句を作成する
     *
     * @param string $start_date
     * @param string $finish_date
     * @param int $sitelicense_id
     * @return string: 数値化したIPアドレスを利用する方法と文字列型のIPアドレスを利用する方法の二つがある
     *           ex1) AND LOG.ip_addoress IN('172.17.72.11', '172.17.72.19')
     */
    private function createWhereQueryForSpecificSiteLicenseLog($start_date, $finish_date, $sitelicense_id){
        $this->debugLog(__FUNCTION__, __FILE__, __CLASS__, __LINE__);
        // IPアドレスを12ケタの数値にしたログとしていないログがあるためここで判定する
        // 対象月のログ内部でnumeric_ip_address=-1がなければnumeric_ip_addressでIPアドレスの特定する
        // numeric_ip_address=-1があるのであればIPアドレスを参照して特定する
        
        $whereString = "";
        
        // ver.2.1.1⇒ver.2.1.7でサイトライセンス利用統計の集計高速化のため、
        // IPアドレスを数値に変換した値で計算している
        $query = " SELECT log_no ". 
                 " FROM ". DATABASE_PREFIX. "repository_log ". 
                 " WHERE numeric_ip_address = ?".
                 " AND record_date >= ? ". 
                 " AND ? >= record_date ".
                 " LIMIT 0,1 ;";
        $params = array();
        $params[] = -1;
        $params[] = $start_date;
        $params[] = $finish_date;
        $ret = $this->Db->execute($query, $params);
        if($ret === false){
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        $whereIpRange = "";
        if(count($ret) === 0){
            $whereIpRange = $this->createWhereQueryByNumericIpAddress($start_date, $finish_date, $sitelicense_id);
        } else {
            // TODO: サイトライセンス利用統計再送機能開発時に実装する必要あり
        }
        
        if(strlen($whereIpRange) > 0){
            $whereString = " AND ( ". $whereIpRange. ") ";
        }
        
        return $whereString;
    }
    
    /**
     * 数値化したIPアドレスを利用してwhere句を作成する
     * create where query by numeric ip addoress
     *
     * @param string $start_date
     * @param string $finish_date
     * @param int $sitelicense_id
     * @return string
     *         ex) LOG.numeric_ip_address >= 172017072019 AND LOG.numeric_ip_address <= 172017072020 ) OR (LOG.numeric_ip_address = 172017072214
     */
    private function createWhereQueryByNumericIpAddress($start_date, $finish_date, $sitelicense_id){
        $this->debugLog(__FUNCTION__, __FILE__, __CLASS__, __LINE__);
        // IPアドレスを数値に変換した値でwhere句を作成する
        // IPアドレスは複数設定することが可能なので、それに合わせてwhere句を作成する
        $whereString = "";
        
        // サイトライセンスIPアドレス範囲情報を取得
        $sitelicense_ip = $this->selectSitelicenseIpRange($sitelicense_id);
        
        $whereParts = array();
        for($ii = 0; $ii < count($sitelicense_ip); $ii++) {
        $this->debugLog("3", __FILE__, __CLASS__, __LINE__);
            $start_ip = 0;
            $finish_ip = 0;
            if(strlen($sitelicense_ip[$ii]["start_ip_address"]) > 0) {
                $start_ip_elements = explode(".", $sitelicense_ip[$ii]["start_ip_address"]);
                $start_ip = sprintf("%d", $start_ip_elements[0]).
                            sprintf("%03d", $start_ip_elements[1]).
                            sprintf("%03d", $start_ip_elements[2]).
                            sprintf("%03d", $start_ip_elements[3]);
            }
            if(strlen($sitelicense_ip[$ii]["finish_ip_address"]) > 0) {
                $finish_ip_elements = explode(".", $sitelicense_ip[$ii]["finish_ip_address"]);
                $finish_ip = sprintf("%d", $finish_ip_elements[0]).
                             sprintf("%03d", $finish_ip_elements[1]).
                             sprintf("%03d", $finish_ip_elements[2]).
                             sprintf("%03d", $finish_ip_elements[3]);
            }
            
            // IPレンジは開始のみ、開始&終了だけが設定可能
            if($start_ip > 0 && $finish_ip > 0){
                $whereParts[] = " ( LOG.numeric_ip_address >= ". $start_ip. 
                                " AND LOG.numeric_ip_address <= ". $finish_ip. " )";
            } else if($start_ip > 0 && $finish_ip === 0){
                $whereParts[] = " ( LOG.numeric_ip_address = ". $start_ip. " )";
            }
        }
        
        // AND ( (n_ip >= 172017072019 AND n_ip <= 172017072020 ) OR (n_ip = 172017072214) )
        for($ii = 0; $ii < count($whereParts); $ii++){
            if(strlen($whereString) > 0){
                $whereString .= " OR ";
            }
            $whereString .= $whereParts[$ii];
        }
        
        return $whereString;
    }
    
    /**
     * select sitelicense ip addoress
     * DBからサイトライセンスのIPレンジを取得する
     *
     * @param int $sitelicense_id
     * @return array
     */
    private function selectSitelicenseIpRange($sitelicense_id){
        // サイトライセンスIPアドレス範囲情報を取得
        $query = " SELECT * ". 
                 " FROM ".DATABASE_PREFIX ."repository_sitelicense_ip_address ".
                 " WHERE organization_id = ? ".
                 " AND is_delete = ? ;";
        $params = array();
        $params[] = $sitelicense_id;
        $params[] = 0;
        $sitelicense_ip = $this->Db->execute($query, $params);
        if($sitelicense_ip === false){
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        return $sitelicense_ip;
    }
    
    /**
     * log result(latest ver) + log result(old ver)
     * バージョンアップによって集計方法が変わったログ集計結果を足し合わせる
     *
     * @param array $origin
     * @param array $additions
     * @return array: 二つのログ集計結果を合わせた結果
     *           ex) array[0]['CNT']
     *                       ['online_issn']
     *                    [1]['CNT']
     *                       ['online_issn']
     *                     .
     *                     .
     *                     .
     */
    private function addResultForDownloadOrDetail($origin, $additions){
        for($ii = 0; $ii < count($additions); $ii++){
            $issnStrAdd = $additions[$ii]['online_issn'];
            $pushFlag = true;
            for($jj = 0; $jj < count($origin); $jj++){
                $issnStrOrg = $origin[$jj]['online_issn'];
                if(strcmp($issnStrAdd, $issnStrOrg) === 0){
                    $origin[$jj]['CNT'] = $origin[$jj]['CNT'] + $additions[$ii]['CNT'];
                    $pushFlag = false;
                    break;
                }
            }
            if($pushFlag){
                array_push($origin, $additions[$ii]);
            }
        }
        
        return $origin;
    }
    
    /**
     * 指定した機関・集計範囲・サイトライセンスIDに一致するログを取得する
     *
     * @param string $subQuery       join elapsed time table
     * @param string $start_date     [YYYY-MM-DD hh:mm:ss.000]
     * @param string $finish_date    [YYYY-MM-DD hh:mm:ss.000]
     * @param int    $operation_id
     * @param int    $sitelicense_id
     * @return array $result         [0]["item_id"] アイテムID
     *                                  ["CNT"]     アイテム毎の操作ログの件数
     *                                  ["MONTHLY"] 集計対象の月(01-12)
     */
    private function searchOperationLog($subQuery, $start_date, $finish_date, $operation_id, $sitelicense_id, $ip_address_query="") {
        $query = "SELECT LOG.item_id, ".
                        "COUNT(LOG.record_date) AS CNT, ".
                         Repository_Components_Loganalyzor::dateformatMonthlyQuery("LOG").
                 $subQuery[Repository_Components_Business_Logmanager::SUB_QUERY_KEY_FROM]." ".
                 "WHERE ".$subQuery[Repository_Components_Business_Logmanager::SUB_QUERY_KEY_WHERE]." ".
                 "AND LOG.record_date >= ? ".
                 "AND LOG.record_date <= ? ".
                 "AND LOG.operation_id = ? ".
                 "AND LOG.site_license = ? ".
                 "AND LOG.site_license_id = ? ".
                 $ip_address_query.
                 Repository_Components_Loganalyzor::perMonthlyQuery(). ", LOG.item_id ;";
        $params = array();
        $params[] = $start_date;
        $params[] = $finish_date;
        $params[] = $operation_id;
        $params[] = 1;
        $params[] = $sitelicense_id;
        $result = $this->Db->execute($query, $params);
        if($logs === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        return $result;
    }
    
    /**
     * 指定したISSNに所属するアイテムIDを取得する
     *
     * @param string $item_ids [xxx,yyy,zzz,...]
     * @param string $issn     [xxx,yyy,zzz,...]
     * @return array $result   [0]["item_id"]     アイテムID
     *                            ["online_issn"] アイテムが所属するインデックスに設定されたISSN値
     */
    private function filterItemIdByOnlineIssn($itemIds, $issn) {
        $query = "SELECT ITEM.item_id, IDX.online_issn ".
                 "FROM nc2_repository_index AS IDX ".
                 "INNER JOIN nc2_repository_position_index AS POS ON IDX.index_id = POS.index_id ".
                 "INNER JOIN nc2_repository_item AS ITEM ON ITEM.item_id = POS.item_id ".
                 "WHERE IDX.biblio_flag = ? ".
                 "AND ITEM.item_id IN (".$itemIds.") ". 
                 "AND IDX.online_issn IN ( ". $issn. " ) ".
                 $this->getExclusiveSitelicenseItemtype("ITEM").";";
        $params = array();
        $params[] = 1;
        $result = $this->Db->execute($query, $params);
        if($result === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        return $result;
    }
    
    /**
     * 配列内のISSNを検索する
     *
     * @param  array  $output [0]["online_issn"] ISSN配列
     * @param  string $issn   [XXXX-YYYY]
     * @return int    $index  条件に一致した要素のインデックス値
     */
    private function checkExistOnlineIssn($output, $issn) {
        $index = "";
        for($ii = 0; $ii < count($output); $ii++) {
            if($output[$ii]["online_issn"] == $issn) {
                $index = $ii;
                break;
            }
        }
        
        return $index;
    }
    
    /**
     * ISSN毎に集計結果をまとめる
     *
     * @param  array  $logs   [0]["item_id"] アイテムID
     *                           ["CNT"]     アイテム毎の操作ログの件数
     *                           ["MONTHLY"] 集計対象の月(01-12)
     * @param  string $issn   "AAAA-BBBB,CCCC-DDDD,..."
     * @return array  $result [0]["CNT"]         ISSN毎の操作ログの件数
     *                           ["online_issn"] ONLINE ISSN
     *                           ["MONTHLY"]     集計対象の月(01-12)
     */
    private function calcOperationLogCountByIssn($logs, $issn) {
        // 出力用配列
        $result = array();
        // 集計対象ログが無い場合終了する
        if(count($logs) == 0) {
            return $result;
        }
        
        // クエリ用のアイテムID文字列を作成する
        $itemIds = "";
        for($ii = 0; $ii < count($logs); $ii++) {
            if(strlen($itemIds) > 0) {
                $itemIds .= ",";
            }
            $itemIds .= $logs[$ii]["item_id"];
        }
        
        // ISSNが設定されているアイテムを絞り込む
        $item = $this->filterItemIdByOnlineIssn($itemIds, $issn);
        
        // ISSN毎に操作ログ件数をまとめなおす
        $dataCnt = 0;
        for($ii = 0; $ii < count($item); $ii++) {
            for($jj = 0; $jj < count($logs); $jj++) {
                if($item[$ii]["item_id"] == $logs[$jj]["item_id"]) {
                    // 出力配列に同じISSNが既に存在するか確認する
                    $issnIndex = $this->checkExistOnlineIssn($result, $item[$ii]["online_issn"]);
                    if(strlen($issnIndex) > 0) {
                        // 同じISSNがある場合カウント値を加算する
                        $result[$issnIndex]["CNT"] += $logs[$jj]["CNT"];
                    } else {
                        $result[$dataCnt]["CNT"] = $logs[$jj]["CNT"];
                        $result[$dataCnt]["online_issn"] = $item[$ii]["online_issn"];
                        $result[$dataCnt]["MONTHLY"] = $logs[$jj]["MONTHLY"];
                        $dataCnt++;
                    }
                }
            }
        }
        
        return $result;
    }
}
?>