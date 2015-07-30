<?php
// --------------------------------------------------------------------
//
// $Id: Scpj.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
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
class Repository_Action_Main_Item_Policy_Scpj extends RepositoryAction
{
    /* Session resource */
    public $Session = null;
    /* database resource */
    public $Db = null;

    /**
     * replace keys
     */
    /* replace keys for "jtitle" */
    const SCPJ_API_REPLACE_KEY_JTITLE    = '$$jtitle$$';
    /* replace keys for "jounal id" */
    const SCPJ_API_REPLACE_KEY_JOUNAL_ID = '$$journalId$$';
    /* SCPJ URL */
    const SCPJ_API_URL = 'http://scpj.tulips.tsukuba.ac.jp/';
    /* for search "jtitle" */
    const SCPJ_API_SEARCH_JTITLE = 'search/journal?keyword=$$jtitle$$&format=xml';
    /* for search jounal id */
    const SCPJ_API_JOURNAL_ID = 'detail/journal/id/$$journalId$$?format=xml';
    /* for search jounal id format */
    const SCPJ_API_FORMAT = '&format=xml';
    
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
     * SCPJ API XML Tags
     */
    const SCPJ_XML_TAG_JOURNAL           = "journal";
    const SCPJ_XML_TAG_JTITLE            = "jtitle";
    const SCPJ_XML_TAG_ISSN              = "issn";
    const SCPJ_XML_TAG_LANGUAGE          = "language";
    const POLICY_XML_TAG_ACQUIRING         = "acquiring";
    
    /**
     * SCPJ API XML attribute
     */
    const SCPJ_XML_ATTRIBUTE_ID       = "id";
    const SCPJ_XML_ATTRIBUTE_TYPE     = "type";
    const SCPJ_XML_ATTRIBUTE_VALUE    = "value";
    
    /* journal id */
    const SCPJ_XML_JOURNAL_ID = "journalId";


    /**
     * コンストラクタ
     */
    public function __construct($session, $db)
    {
        $this->Session = $session;
        $this->Db = $db;
    }
    
    /**
     * SPCJのAPIに雑誌名、雑誌IDを問合せる。
     * @return array SCPJからの検索結果
     */
    public function getSCPJJtitleList( $jtitleStr )
    {
        // APIから雑誌名での検索結果を取得する。
        $jtitleStr = str_replace(' ', '%20', $jtitleStr);
        $apiUrl   = str_replace( self::SCPJ_API_REPLACE_KEY_JTITLE, $jtitleStr, self::SCPJ_API_URL.self::SCPJ_API_SEARCH_JTITLE );
        $response = $this->sendHttpRequest( $apiUrl );
        
        $xmlStr   = $response["body"];
        if( strlen( $xmlStr ) == 0)
        {
            return '';
        }
        
        // parse xml
        $vals = $this->parseXml( $xmlStr );
        
        $return_array = array();
        $temp_array = array(self::SCPJ_XML_JOURNAL_ID=>"", 
                            self::SCPJ_XML_TAG_JTITLE=>"", 
                            self::SCPJ_XML_TAG_ISSN=>"", 
                            self::POLICY_XML_TAG_ACQUIRING=>"SCPJ"
                            );
        foreach($vals as $val)
        {
            switch($val[self::XML_PARSER_TAG]){
                //読み出し開始/終了
                case self::SCPJ_XML_TAG_JOURNAL:
                    if($val[self::XML_PARSER_TYPE] == self::XML_PARSER_OPEN){
                        //読み出し開始
                        $temp_array = array(
                                              self::SCPJ_XML_JOURNAL_ID=>"", 
                                              self::SCPJ_XML_TAG_JTITLE=>"", 
                                              self::SCPJ_XML_TAG_ISSN=>"", 
                                              self::POLICY_XML_TAG_ACQUIRING=>"SCPJ"
                                              );
                        //id取得
                        if($val[self::XML_PARSER_TYPE] == self::XML_PARSER_OPEN){
                            if( isset($val[self::XML_PARSER_ATTRIBUTE][self::SCPJ_XML_ATTRIBUTE_ID]) )
                            {
                                $temp_array[self::SCPJ_XML_JOURNAL_ID] = $val[self::XML_PARSER_ATTRIBUTE][self::SCPJ_XML_ATTRIBUTE_ID];
                            }
                        }
                    }
                    else if($val[self::XML_PARSER_TYPE] == self::XML_PARSER_CLOSE){
                        //読み出し終了
                        array_push($return_array, $temp_array);
                    }
                    break;
                
                //jtitle
                case self::SCPJ_XML_TAG_JTITLE:
                    $temp_array[self::SCPJ_XML_TAG_JTITLE] = $val[self::XML_PARSER_VALUE];
                    break;
                //issn
                case self::SCPJ_XML_TAG_ISSN:
                    $temp_array[self::SCPJ_XML_TAG_ISSN] = "";
                    if(isset($val[self::XML_PARSER_VALUE]))
                    {
                        $temp_array[self::SCPJ_XML_TAG_ISSN] = $val[self::XML_PARSER_VALUE];
                    }
                    break;
                default:break;
            }
        }
        return $return_array;
    }
    
    /**
     * SPCJのAPIに著作権ポリシーを問合せる。
     * 1.雑誌IDから雑誌著作権ポリシーを取得する。
     *   下記APIから雑誌の著作権ポリシーを取得する。
     *   http://scpj.tulips.tsukuba.ac.jp/detail/journal/id/[雑誌ID]?format=xml
     * 2.1の取得結果を返す。
     * 
     * @param $jtitle_id string 検索対象の雑誌ID
     * @return array SCPJ string format XML
     */
    public function getSCPJXml( $jtitle_id )
    {
        if( $jtitle_id == '')
        {
            return "";
        }
        
        // APIから雑誌の著作権ポリシーを取得する。
        $apiUrl   = str_replace( self::SCPJ_API_REPLACE_KEY_JOUNAL_ID, $jtitle_id, self::SCPJ_API_URL.self::SCPJ_API_JOURNAL_ID );
        $response = $this->sendHttpRequest( $apiUrl );
        $xmlStr   = $response["body"];
        
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