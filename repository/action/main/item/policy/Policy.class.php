<?php
// --------------------------------------------------------------------
//
// $Id: Policy.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/**
 * require once
 */
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/action/main/item/policy/format/Scpj.class.php';
require_once WEBAPP_DIR. '/modules/repository/action/main/item/policy/format/Romeo.class.php';

/**
 * get scpj
 */
class Repository_Action_Main_Item_Policy extends RepositoryAction
{
    /**
     * request parameter
     */
    public $issn = null;
    public $id_str = null;
    public $jtitle = null;
    public $type = null;
    public $acquiring = null;
    
    /* Session resource */
    public $Session = null;
    /* database resource */
    public $Db = null;
    
    //language resorce
    var $smartyAssign = null;
    
    /**
     * Policy API URL
     */
    /**
     * replace keys
     */
    /* SCPJ URL */
    const SCPJ_API_URL = 'http://scpj.tulips.tsukuba.ac.jp/';
    /* for search jounal id */
    const SCPJ_API_DETAIL_JOURNAL_ID = 'detail/journal/id/';
    /* for search jounal id format */
    const POLICY_API_FORMAT = '&format=xml';
    /* Romeo URL */
    const ROMEO_API_URL = 'http://www.sherpa.ac.uk/romeo/search.php';
    /* for search issn  */
    const ROMEO_API_ISSN = '?issn=';
    
    /**
     * XML parser const
     */
    const XML_PARSER_TAG       = "tag";
    const XML_PARSER_TYPE      = "type";
    const XML_PARSER_VALUE     = "value";
    
    /**
     * POLICY API XML Tags
     */
    const POLICY_XML_TAG_JOURNAL           = "journal";
    const POLICY_XML_TAG_JTITLE            = "jtitle";
    const POLICY_XML_TAG_ISSN              = "issn";
    const POLICY_XML_TAG_LANGUAGE          = "language";
    const POLICY_XML_TAG_POLICYCOLOUR      = "policycolour";
    const POLICY_XML_TAG_ACQUIRING         = "acquiring";
    
    const POLICY_XML_JOURNAL_ID = "journalId";
    const POLICY_XML_POLICY_JOURNAL_URL = "policyJournalUrl";
    const POLICY_XML_SCPJ_JOURNAL_URL = "scpjPolicyJournalUrl";
    const POLICY_XML_ROMEO_JOURNAL_URL = "romeoPolicyJournalUrl";
    const POLICY_XML_IS_EXIST = "isPolicyExist";
    
    /**
     * execute
     * 下記を実施する。
     * 1.リクエストパラメーターjtitleのURLエンコードをデコードする。
     * 2.SPCJのAPIに著作権ポリシーを問合せる。
     * 3.2の問合せ結果をJSON形式に整形する。
     * 4.3のJSONを出力する。
     */
    public function execute()
    {
        try {
            //アクション初期化処理
            $result = $this->initAction();
            if ( $result === false ) {
                $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 ); //主メッセージとログIDを指定して例外を作成
                $DetailMsg = null;                            //詳細メッセージ文字列作成
                $exception->setDetailMsg( $DetailMsg );      //詳細メッセージ設定
                $this->failTrans();                          //トランザクション失敗を設定(ROLLBACK)
                throw $exception;
            }
            
            // 言語リソース設定処理
            $this->setLangResource();
            $this->smartyAssign = $this->Session->getParameter("smartyAssign");
            
            // 1.リクエストパラメーターjtitleのURLエンコードをデコードする。
            $jtitle = $this->jtitle;
            $jtitle = urldecode($this->jtitle);
            $jtitle = trim(mb_convert_encoding($jtitle, "UTF-8", "ASCII,JIS,UTF-8,EUC-JP,SJIS"));
            
            $scpj = new Repository_Action_Main_Item_Policy_Scpj($this->Session, $this->Db);
            $romeo = new Repository_Action_Main_Item_Policy_Romeo($this->Session, $this->Db);
            //2    リクエストパラメータtypeが“jtitle”の場合以下を行う。
            if($this->type == 'jtitle'){
                
                //2バイトより少ない場合、何もしない
                if(strlen($jtitle) < 2){
                    return '';
                }
                
                //2.1    SPCJのAPIに雑誌名/雑誌IDを問合せる。
                $scpj_jtitle_array = null;
                $scpj_jtitle_array = $scpj->getSCPJJtitleList($jtitle);
                
                //2.2    RomeoのAPIに雑誌名/ISSNを問い合わせる。
                $romeo_jtitle_array = null;
                $romeo_jtitle_array =  $romeo->getRomeoJtitleList($jtitle);
                
                //2.3    2.1と2.2の結果を連結する。
                $jtitle_array = $this->connectionArray($scpj_jtitle_array, $romeo_jtitle_array);
                
                // 先頭一致しているものだけをJSONで返す
                $match_array = array();
                for($ii=0;$ii<count($jtitle_array); $ii++){
                    //大文字小文字を区別しない(オプションi)
                    if(preg_match('/^'.$jtitle.'/i', $jtitle_array[$ii]['jtitle']) == 1){
                        array_push($match_array, $jtitle_array[$ii]);
                    }
                }
                
                //2.4    2.3で連結した結果をJson形式にする。
                $jtitleStr = $this->convertJtitleJson( $match_array );
                
                //2.5    2.4のJsonを出力する。
                echo $jtitleStr;
                
            }
            //3    リクエストパラメータtypeが“color”の場合以下を行う。
            else if($this->type == 'color'){
                //3.1    リクエストパラメータacquiringが“SPCJ”の場合以下を行う。
                if($this->acquiring == 'SCPJ'){
                    //SCPJの著作権ポリシーを出力する。
                    $this->echoColorJosnSCPJ();
                }
                
                //3.2    リクエストパラメータacquiringが“Romeo”の場合以下を行う。
                else if($this->acquiring == 'Romeo'){
                    //Romeoの著作権ポリシーを出力する。
                    $this->echoColorJosnRomeo();
                }
            }
            
            
             // アクション終了処理
            $result = $this->exitAction();  // トランザクションが成功していればCOMMITされる
            if ( $result == false ){
                $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 ); //主メッセージとログIDを指定して例外を作成
                $DetailMsg = null;                              //詳細メッセージ文字列作成
                $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
                $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
                throw $exception;
            }
            
            exit();
        }
        catch ( RepositoryException $Exception)
        {
            echo "";
            exit();
        }
    }
    
    
    /**
     * SCPJ/ROMEOのAPIから取得したXMLをJSON形式に変換する。
     * 1.XMLをパースする。
     * 2.タグのデータをJSONに変換する。
     * 3.2で生成したJSONを返す。
     * 
     * @param $xmlArray array SCPJ&ROMEO XML 
     * @return string SCPJ&ROMEO string format JSON
     *                {
     *                 Candidate ["xxx", "xxx",   ]
     *                 JtitleCandidate [
     *                     { “journalId” : xxx, “jtitle” : xxx, “issn” : xxx, "acquiring ": xxx},
     *                     { “journalId” : xxx, “jtitle” : xxx, “issn” : xxx, "acquiring ": xxx},
     *                    ]
     *                }
     */
    private function convertJtitleJson( $xmlArray )
    {
        
        // initialize array
        $titleData = array( 
                            self::POLICY_XML_JOURNAL_ID=>"", 
                            self::POLICY_XML_TAG_JTITLE=>"", 
                            self::POLICY_XML_TAG_ISSN=>"", 
                            self::POLICY_XML_TAG_ACQUIRING=>""
                            );
                            
        // 2.$xmlArrayのデータをJSONに変換する。
        // 2-1. convert $xmlArray data to array
        $jsonStr = '';
        $jsonCandidateStr = '';
        $jsonJtitleCandidateStr = '';
        for($ii=0;$ii<count($xmlArray);$ii++){
            if($ii==0){
                $jsonCandidateStr .= '"'.'Candidate'.'"'.':[';
                $jsonJtitleCandidateStr .= '"'.'JtitleCandidate'.'"'.':[';
            }
            if(isset($xmlArray[$ii][self::POLICY_XML_JOURNAL_ID])){
                $jsonJtitleCandidateStr .= '{"'.self::POLICY_XML_JOURNAL_ID.'":"'.$this->escapeJSON($xmlArray[$ii][self::POLICY_XML_JOURNAL_ID]).'",';
            }else{
                $jsonJtitleCandidateStr .= '"'.self::POLICY_XML_JOURNAL_ID.'":"'.''.'",';
            }
            if(isset($xmlArray[$ii][self::POLICY_XML_TAG_JTITLE])){
                $jsonCandidateStr .= '"'.$xmlArray[$ii][self::POLICY_XML_TAG_JTITLE].'"';
                $jsonJtitleCandidateStr .= '"'.self::POLICY_XML_TAG_JTITLE.'":"'.$this->escapeJSON($xmlArray[$ii][self::POLICY_XML_TAG_JTITLE]).'",';
            }else{
                $jsonJtitleCandidateStr .= '"'.self::POLICY_XML_TAG_JTITLE.'":"'.''.'",';
            }
            if(isset($xmlArray[$ii][self::POLICY_XML_TAG_ISSN])){
                $jsonJtitleCandidateStr .= '"'.self::POLICY_XML_TAG_ISSN.'":"'.$this->escapeJSON($xmlArray[$ii][self::POLICY_XML_TAG_ISSN]).'",';
            }else{
                $jsonJtitleCandidateStr .= '"'.self::POLICY_XML_TAG_ISSN.'":"'.''.'",';
            }
            if(isset($xmlArray[$ii][self::POLICY_XML_TAG_ACQUIRING])){
                $jsonJtitleCandidateStr .= '"'.self::POLICY_XML_TAG_ACQUIRING.'":"'.$this->escapeJSON($xmlArray[$ii][self::POLICY_XML_TAG_ACQUIRING]).'"}';
            }else{
                $jsonJtitleCandidateStr .= '"'.self::POLICY_XML_TAG_ACQUIRING.'":"'.''.'"}';
            }
            if($ii == count($xmlArray)-1){
                $jsonCandidateStr .= '],';
                $jsonJtitleCandidateStr .= ']';
            }else{
                $jsonCandidateStr .= ',';
                $jsonJtitleCandidateStr .= ',';
            }
        }
        // 2-2. convert xml array to json string
        $jsonStr .= '{';
        $jsonStr .= $jsonCandidateStr;
        $jsonStr .= $jsonJtitleCandidateStr;
        $jsonStr .= '}';
        
        // 改行削除
        $jsonStr = str_replace("\r\n", "\n", $jsonStr);
        $jsonStr = str_replace("\n", "", $jsonStr);
        
        // 3.2で生成したJSONを返す。
        return $jsonStr;
    }
    
    /**
     * xml parse
     *
     * @param string $str
     * @return array parse array
     */
    private function parseXML( $str )
    {
        $xmlParser = xml_parser_create();
        // パースした後のキー文字列大文字化を無効に
        xml_parser_set_option($xmlParser, XML_OPTION_CASE_FOLDING, 0);
        xml_parse_into_struct($xmlParser, $str, $vals);
        xml_parser_free($xmlParser);
        return $vals;
    }
    
    
    /**
     * escape JSON 
     *
     * @param array $index_data
     */
    private function escapeJSON($str){
        
        $str = str_replace("\\", "\\\\", $str);
        $str = str_replace('[', '\[', $str);
        $str = str_replace(']', '\]', $str);
        $str = str_replace('"', '\"', $str);
        $str = str_replace("\r\n", "\n", $str);
        $str = str_replace("\n", "\\n", $str);
        if(is_string($str))
        {
            $str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
        }
        // Fix PHP Warning. is_array => prefix_name, suffix.
        if(is_array($str))
        {
            foreach ($str as $data)
            {
                foreach ($data as $key => $value)
                {
                    if(is_string($value))
                    {
                        $str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
                    }
                }
            }
        }
        return $str;
    }
    
    /**
     * 配列を連結しする
     *
     * @param array $scpj_jtitle_array
     * @param array $romeo_jtitle_array
     * @return array
     */
    private function connectionArray($scpj_jtitle_array, $romeo_jtitle_array){
        $jtitle_array = array();
        
        if($scpj_jtitle_array == null){
            $scpj_jtitle_array = array();
        }
        
        //SCPJのデータをはじめに入れる
        $jtitle_array = $scpj_jtitle_array;
        //Romeoのデータを追加する
        if($romeo_jtitle_array != null){
            for($ii=0;$ii<count($romeo_jtitle_array);$ii++){
                array_push($jtitle_array, $romeo_jtitle_array[$ii]);
            }
        }
        return $jtitle_array;
    }
    
    /**
     * SCPJの著作権ポリシーのJSONを出力する
     *
     */
    private function echoColorJosnSCPJ(){
        //3.1.1 SPCJのAPIに著作権ポリシーカラーを問い合わせる。
        $jtitle = '';
        $policycolour = '';
        
        $scpj = new Repository_Action_Main_Item_Policy_Scpj($this->Session, $this->Db);
        $xmlStr = $scpj->getSCPJXml($this->id_str);
        
        //配列にparse
        $vals = $this->parseXML($xmlStr);
        
        foreach($vals as $val)
        {
            if($val[self::XML_PARSER_TAG] == self::POLICY_XML_TAG_JTITLE){
                $jtitle = $val[self::XML_PARSER_VALUE];
            }
            else if($val[self::XML_PARSER_TAG] == 'scpjcolour'){
                $policycolour = $val[self::XML_PARSER_VALUE];
                //小文字に変換
                $policycolour = strtolower($policycolour);
            }
        }
        
        //ポリシーが存在するか
        $isPolicyExist = false;
        if(strlen($policycolour) > 0){
            $isPolicyExist = true;
        }
        
        //メール件名作成
        $jtitle = $this->escapeJSON($jtitle);   //一旦エスケープ
        $mail_title = $this->encordingJtitle($jtitle);
        
        //詳細URL
        $apiUrl = self::SCPJ_API_URL.self::SCPJ_API_DETAIL_JOURNAL_ID.$this->id_str;
        
        $policyJson = '{';
        $policyJson .= '"'.self::POLICY_XML_IS_EXIST.'":'.'"'.$isPolicyExist.'",';
        $policyJson .= '"'.self::POLICY_XML_POLICY_JOURNAL_URL.'":'.'"'.$this->escapeJSON($apiUrl).'",';
        $policyJson .= '"'.self::POLICY_XML_TAG_JTITLE.'":'.'"'.$mail_title.'",';
        $policyJson .= '"'.self::POLICY_XML_TAG_POLICYCOLOUR.'":'.'"'.$policycolour.'",';
        $policyJson .= '"'.self::POLICY_XML_TAG_ACQUIRING.'":'.'"'.'SCPJ'.'"';
        $policyJson .= '}';
        
        // 改行削除
        $policyJson = str_replace("\r\n", "\n", $policyJson);
        $policyJson = str_replace("\n", "", $policyJson);
        
        //JSONを出力する。
        echo $policyJson;
    }
    
    
    /**
     * RoMEOの著作権ポリシーのJSONを出力する
     *
     */
    private function echoColorJosnRomeo(){
        //3.2.1 SHERPA/ROMEOのAPIに著作権ポリシーカラーを問い合わせる。
        $romeo = new Repository_Action_Main_Item_Policy_Romeo($this->Session, $this->Db);
        $xmlStr = $romeo->getRomeoXml($this->issn);
        
        //配列にparse
        $vals = $this->parseXML($xmlStr);
        
        $jtitle = "";
        $policycolour = "";
        foreach($vals as $val)
        {
            if($val[self::XML_PARSER_TAG] == self::POLICY_XML_TAG_JTITLE){
                $jtitle = $val[self::XML_PARSER_VALUE];
            }
            else if($val[self::XML_PARSER_TAG] == 'romeocolour'){
                $policycolour = $val[self::XML_PARSER_VALUE];
                //小文字に変換
                $policycolour = strtolower($policycolour);
            }
        }
        
        //ポリシーが存在するか
        $isPolicyExist = false;
        if(strlen($policycolour) > 0){
            $isPolicyExist = true;
        }
        //詳細URL
        $romeoDetailUrl = 'http://www.sherpa.ac.uk/romeo/search.php?issn='.$this->issn;
        
        //JSONを出力する。
        $policyJson = '{';
        $policyJson .= '"'.self::POLICY_XML_IS_EXIST.'":'.'"'.$isPolicyExist.'",';
        $policyJson .= '"'.self::POLICY_XML_POLICY_JOURNAL_URL.'":'.'"'.$romeoDetailUrl.'",';
        $policyJson .= '"'.self::POLICY_XML_TAG_JTITLE.'":'.'"'.$this->escapeJSON($jtitle).'",';
        $policyJson .= '"'.self::POLICY_XML_TAG_POLICYCOLOUR.'":'.'"'.$policycolour.'",';
        $policyJson .= '"'.self::POLICY_XML_TAG_ACQUIRING.'":'.'"'.'Romeo'.'"';
        $policyJson .= '}';

        // 改行削除
        $policyJson = str_replace("\r\n", "\n", $policyJson);
        $policyJson = str_replace("\n", "", $policyJson);
        
        echo $policyJson;
    }

    /**
     * ブラウザ毎にメール件名作成/エンコードする。
     *
     * @param string $jtitle 雑誌名
     * @return string $mail_title エンコードされたメール件名
     */
    private function encordingJtitle($jtitle){
        //メール件名を作成
        $mail_title = $this->smartyAssign->getLang("repository_policy_feedbackmail_title");
        $mail_title .= '(';
        $mail_title .= $jtitle;
        $mail_title .= ')';
        
        //ブラウザ判定毎にエンコード
        if (stristr($_SERVER['HTTP_USER_AGENT'], "MSIE") || stristr($_SERVER['HTTP_USER_AGENT'], "Trident")){
            // IEの場合
            $mail_title = mb_convert_encoding($mail_title, 'SJIS', _CHARSET);
        } elseif (stristr($_SERVER['HTTP_USER_AGENT'], "Opera")) {
            // Operaの場合
            $mail_title = mb_convert_encoding($mail_title, 'SJIS', _CHARSET); 
        } elseif (stristr($_SERVER['HTTP_USER_AGENT'], "Firefox")) {
            // FireFoxの場合
            $mail_title = mb_convert_encoding($mail_title, _CHARSET, _CHARSET);
        } else {
            // 上記以外(Mozilla, Firefox, NetScape)
            $mail_title = mb_convert_encoding($mail_title, 'SJIS', _CHARSET);
        }
        
        //ここでURLエンコード
        $mail_title = urlencode($mail_title);
        
        return $mail_title;
    }

}
?>