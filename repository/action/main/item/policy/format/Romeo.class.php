<?php
// --------------------------------------------------------------------
//
// $Id: Romeo.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
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

/**
 * get scpj
 */
class Repository_Action_Main_Item_Policy_Romeo extends RepositoryAction
{
    /* Session resource */
    public $Session = null;
    /* database resource */
    public $Db = null;

    /* Romeo URL */
    const ROMEO_API_URL = 'http://www.sherpa.ac.uk/romeo/api29.php';
    const ROMEO_API_JTITLE = '?jtitle=';
    const ROMEO_API_STARTS = '&qtype=starts';
    const ROMEO_API_ISSN = '?issn=';
    const ROMEO_API_VERSIONALL = '&version=all';
    const ROMEO_API_AK = '&ak=';
    
    /**
     * XML parser const
     */
    const XML_PARSER_TAG       = "tag"; 
    const XML_PARSER_TYPE      = "type";
    const XML_PARSER_VALUE     = "value";
    const XML_PARSER_OPEN      = "open";
    const XML_PARSER_COMPLETE  = "complete";
    const XML_PARSER_CLOSE     = "close";
    const XML_PARSER_ATTRIBUTE = "attributes";
    
    /**
     * Romeo API XML Tags
     */
    const ROMEO_XML_TAG_JOURNAL           = "journal";
    const ROMEO_XML_TAG_JTITLE            = "jtitle";
    const ROMEO_XML_TAG_ISSN              = "issn";
    const ROMEO_XML_TAG_LANGUAGE          = "language";
    const POLICY_XML_TAG_ACQUIRING        = "acquiring";
    /* journal id */
    const ROMEO_XML_JOURNAL_ID = "journalId";
    
    /* romeo register key */
    /* */
    const ROMEO_REGISTER_KEY = 'E9DKYZC7MVE';
    
    /**
     * コンストラクタ
     */
    public function __construct($session, $db)
    {
        $this->Session = $session;
        $this->Db = $db;
    }
    
    /**
     * execute
     * 下記を実施する。
     * 1.リクエストパラメーターjtitleのURLエンコードをデコードする。
     * 2.RomeoのAPIに著作権ポリシーを問合せる。
     * 3.2の問合せ結果をJSON形式に整形する。
     * 4.3のJSONを出力する。
     */
    public function getRomeoJtitleList($jtitleStr)
    {
        //シングルバイトしかゆるさない
        if (strlen($jtitleStr) != mb_strlen($jtitleStr, 'UTF-8')) {
            return null;
        }
        
        // APIから雑誌名での検索結果を取得する。
        $jtitleStr = str_replace(' ', '%20', $jtitleStr);
        //$apiUrl   = self::ROMEO_API_URL.self::ROMEO_API_JTITLE.$jtitleStr.self::ROMEO_API_STARTS.'&ak=E9DKYZC7MVE';
        $apiUrl   = self::ROMEO_API_URL.self::ROMEO_API_JTITLE.$jtitleStr.self::ROMEO_API_STARTS;
        $response = $this->sendHttpRequest( $apiUrl );
        
        $xmlStr   = $response["body"];
        if( strlen( $xmlStr ) == 0)
        {
            return '';
        }
        
        // parse xml
        $vals = $this->parseXml( $xmlStr );
        
        $return_array = array();
        $temp_array = array(self::ROMEO_XML_JOURNAL_ID=>"", 
                            self::ROMEO_XML_TAG_JTITLE=>"", 
                            self::ROMEO_XML_TAG_ISSN=>"", 
                            self::POLICY_XML_TAG_ACQUIRING=>"Romeo"
                            );
        foreach($vals as $val)
        {
            switch($val[self::XML_PARSER_TAG]){
                //読み出し開始/終了
                case self::ROMEO_XML_TAG_JOURNAL:
                    if($val[self::XML_PARSER_TYPE] == self::XML_PARSER_OPEN){
                        //読み出し開始
                        $temp_array = array(
                                              self::ROMEO_XML_JOURNAL_ID=>"", 
                                              self::ROMEO_XML_TAG_JTITLE=>"", 
                                              self::ROMEO_XML_TAG_ISSN=>"", 
                                              self::POLICY_XML_TAG_ACQUIRING=>"Romeo"
                                              );
                    }
                    else if($val[self::XML_PARSER_TYPE] == self::XML_PARSER_CLOSE){
                        //読み出し終了
                        array_push($return_array, $temp_array);
                    }
                    break;
                
                //jtitle
                case self::ROMEO_XML_TAG_JTITLE:
                    $temp_array[self::ROMEO_XML_TAG_JTITLE] = $val[self::XML_PARSER_VALUE];
                    break;
                //issn
                case self::ROMEO_XML_TAG_ISSN:
                    $temp_array[self::ROMEO_XML_TAG_ISSN] = "";
                    if(isset($val[self::XML_PARSER_VALUE]))
                    {
                        $temp_array[self::ROMEO_XML_TAG_ISSN] = $val[self::XML_PARSER_VALUE]; 
                    }
                    break;
                default:break;
            }
        }
        
        return $return_array;
    }
    
    /**
     * RomeoのAPIに著作権ポリシーを問合せる。
     * 1.下記APIから雑誌の著作権ポリシーを取得する。
     *   http://www.sherpa.ac.uk/romeo/api29.php?issn=[ISSN]&version=all
     * 2.1の取得結果を返す。
     * 
     * @param $jtitle string 検索対象のISSN
     * @return string Romeo string format XML
     */
    public function getRomeoXml( $issn )
    {
        // initialize response string
        $xmlStr = "";
        
        // 1.APIから雑誌名での検索結果を取得する。
        //$apiUrl   = self::ROMEO_API_URL.self::ROMEO_API_ISSN.$issn.self::ROMEO_API_VERSIONALL.'&ak=E9DKYZC7MVE';
        $apiUrl   = self::ROMEO_API_URL.self::ROMEO_API_ISSN.$issn.self::ROMEO_API_VERSIONALL;
        $response = $this->sendHttpRequest( $apiUrl );
        $xmlStr   = $response["body"];
        
        // 3の取得結果を返す。
        return $xmlStr;
    }

    /**
     * http request send method
     * 
     * @param $url string send request url
     * @return array result
     */
    private function sendHttpRequest( $reqUrl )
    {
        // initialize response array
        $res = array( "code"=>"", "header"=>array(), "body"=>"", "cookies"=>array() );
        
        // make request parameter
        $option = array(
            "timeout" => "10",
            "allowRedirects" => true,
            "maxRedirects" => 3, 
        );
        
        // get proxy
        $proxy = $this->getProxySetting();
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
        }
        
        // make http request
        $http = new HTTP_Request($reqUrl, $option);
        $http->addHeader("User-Agent", $_SERVER['HTTP_USER_AGENT']);
        $response = $http->sendRequest();
        if (!PEAR::isError($response))
        {
            $res["code"]    = $http->getResponseCode();     // get ResponseCode(200etc.)
            $res["header"]  = $http->getResponseHeader();   // get ResponseHeader
            $res["body"]    = $http->getResponseBody();     // get ResponseBody
            $res["cookies"] = $http->getResponseCookies();  // get Cookie
        }
        
        // return response
        return $res;
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
    
}
?>