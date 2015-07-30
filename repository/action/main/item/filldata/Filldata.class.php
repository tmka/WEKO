<?php
// --------------------------------------------------------------------
//
// $Id: Filldata.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics,
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/JSON.php';
require_once WEBAPP_DIR. '/modules/repository/components/NameAuthority.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryIndexAuthorityManager.class.php';

/**
 * Fill biblio info from other site.
 * Site List
 *  - PubMed
 *  - Amazon
 *  - CiNii
 *
 */
class Repository_Action_Main_Item_Filldata extends RepositoryAction
{
    // const
    const DOI_URI_PREFIX = "http://dx.doi.org/";
    
    // requestparameter of fill
    var $type_fill = null;                    // site name for data get
    var $id_fill = null;                    // id for data get

    // requestparameter of edit item data
    var $base_attr = null;                    // 基本情報部分のリクエストパラメタ配列
    var $item_attr_text = null;                // "text"属性
    var $item_attr_textarea = null;            // "textarea"属性
    var $item_attr_checkbox = null;            // "checkbox"属性
    var $item_attr_name_family = null;        // "name"属性, 姓
    var $item_attr_name_given = null;        // "name"属性, 名
    var $item_attr_name_email = null;        // "name"属性, E-mail
    var $item_attr_select = null;            // "select"属性
    var $item_attr_link = null;                // "link"属性 : URL
    var $item_attr_link_name = null;        // "link"属性 : 表示名
    var $item_attr_radio = null;            // "radio"属性
    
    var $item_pub_date_year = null;            // アイテム公開日 : 年
    var $item_pub_date_month = null;        // アイテム公開日 : 月
    var $item_pub_date_day = null;            // アイテム公開日 : 日    
    var $item_keyword = null;                // アイテムキーワード
    var $item_keyword_english = null;        // アイテムキーワード(英)
    // Add join set insert index and set item links 2008/12/17 Y.Nakao --start--
    //var $OpendIds = null;                    // 開いているインデックスのID列(,区切り)
    //var $CheckedIds = null;                    // チェックされているインデックスのID列(|区切り)
    //var $CheckedNames = null;                // チェックされているインデックスの名前列(|区切り)
    // Add join set insert index and set item links 2008/12/17 Y.Nakao --end--

    var $item_attr_biblio_name = null;        // 書誌情報 : 雑誌名
    var $item_attr_biblio_name_english = null;        // 書誌情報 : 雑誌名(英)
    var $item_attr_biblio_volume = null;    // 書誌情報 : 巻
    var $item_attr_biblio_issue = null;        // 書誌情報 : 号
    var $item_attr_biblio_spage = null;        // 書誌情報 : 開始ページ
    var $item_attr_biblio_epage = null;        // 書誌情報 : 終了ページ
    var $item_attr_biblio_dateofissued = null;            // 書誌情報 : 発行年月日
    var $item_attr_biblio_dateofissued_year = null;        // 書誌情報 : 発行年
    var $item_attr_biblio_dateofissued_month = null;    // 書誌情報 : 発行月
    var $item_attr_biblio_dateofissued_day = null;        // 書誌情報 : 発行日
    var $item_attr_date = null;            // 日付
    var $item_attr_date_year = null;    // 日付 : 年
    var $item_attr_date_month = null;    // 日付 : 月
    var $item_attr_date_day = null;        // 日付 : 日
    
    // Add contents page 2010/08/06 Y.Nakao --start--
    var $item_attr_heading = null;          // heading
    var $item_attr_heading_en = null;       // heading(english)
    var $item_attr_heading_sub = null;      // subheading
    var $item_attr_heading_sub_en = null;   // subheading(english)
    // Add contents page 2010/08/06 Y.Nakao --end--
    
    // Add author ruby 2010/11/01 A.Suzuki --start--
    var $item_attr_name_family_ruby = null; // "name", surname ruby
    var $item_attr_name_given_ruby = null;  // "name", given name ruby
    var $item_attr_name_author_id_prefix = null; // "name", authorID prefix
    var $item_attr_name_author_id_suffix = null; // "name", authorID suffix
    // Add author ruby 2010/11/01 A.Suzuki --end--
    
    // Add fill data more 2008/11/21 Y.Nakao --end--
    // member
    var $title = "";                // for title
    var $language = "";                // for language
    var $keyword = array();            // for keyword
    var $alternative = array();        // for altenative
    var $creator = array();            // for creator
    var $description = array();        // for description
    var $jtitle = "";                // for jtitle 
    var $volume = "";                // for volume 
    var $issue = "";                // for issue
    var $spage = "";                // for spage
    var $epage = "";                // for epage
    var $dateofissued = "";            // for dateofissued
    var $publisher = array();        // for publisher
    var $issn = "";                    // for issn
    // 2012/11/26 A.jin --start--
    var $e_issn = "";                    // for e-issn
    // 2012/11/26 A.jin --end--
    var $pmid = "";                    // for pmid
    var $doi = "";                    // for doi
    // Add fill data more 2008/11/21 Y.Nakao --end--
    
    // add error message 2008/12/17 A.Suzuki --start--
    var $error_msg = array();        // error message
    var $smartyAssign = null;        // for get language resource
    // add error message 2008/12/17 A.Suzuki --end--
    
    // add metadata select language type 2009/08/04 A.Suzuki --start--
    var $title_english = "";                // for title_english
    var $keyword_english = array();            // for keyword_english
    var $creator_english = array();            // for creator in english
    var $description_english = array();        // for description in english
    var $jtitle_english = "";                // for jtitle in english
    var $publisher_english = array();        // for publisher in english
    var $contributor = array();                // for contributor
    var $contributor_english = array();        // for contributor in english
    // add metadata select language type 2009/08/04 A.Suzuki --end--
    
    // Add flag&reult 2010/02/16 S.Nonomura --start--
    var $fill_type = 0;  // Branching process flag
    var $Result_List = array();  // get of metadata from DB
    var $null_check = 0;     // if get of metadata from DB is null
    // Add flag&reult 2010/02/16 S.Nonomura --end--

    /**
     * Form data : contributor radio button select
     *
     * @var int
     */
    public $item_contributor = null;
    
    /**
     * Form data : contributor(handle)
     *
     * @var string
     */
    public $item_contributor_handle = null;
    
    /**
     * Form data : contributor(name)
     *
     * @var string
     */
    public $item_contributor_name= null;
    
    /**
     * Form data : contributor(email)
     *
     * @var string
     */
    public $item_contributor_email = null;
    
    // Modfy proxy 2011/12/06 Y.Nakao --start--
    private $proxy = array();
    // Modfy proxy 2011/12/06 Y.Nakao --end--
    
    // Add JuNii2 ver3 2013/09/24 R.Matsuura --start--
    /**
     * Form data : isbn
     *
     * @var string
     */
    private $isbn = null;
    /**
     * Form data : naid
     *
     * @var string
     */
    private $naid = null;
    // Add NCID 2014/04/03 A.Suzuki --start--
    /**
     * Form data : ncid
     *
     * @var string
     */
    private $ncid = null;
    // Add NCID 2014/04/03 A.Suzuki --end--
    /**
     * Form data : self_crossref_doi
     *
     * @var string
     */
    private $self_crossref_doi = null;
    /**
     * Form data : self_doi
     *
     * @var string
     */
    private $self_doi = null;
    /**
     * Form data : ichushi_id
     *
     * @var string
     */
    private $ichushi_id = null;
    // Add JuNii2 ver3 2013/09/24 R.Matsuura --end--
    
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
        try {
            ////////////////////////////////
            // init action
            ////////////////////////////////
            $result = $this->initAction();
            if ( $result === false ) {
                $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );    //主メッセージとログIDを指定して例外を作成
                $DetailMsg = null;                              //詳細メッセージ文字列作成
                sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
                $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
                $user_error_msg = 'initで既に・・・';
                throw $exception;
            }
            
            // Add addin S.Arata 2012/08/02 --start--
            $this->addin->preExecute();
            // Add addin S.Arata 2012/08/02 --end--
            
            // add get lang 2008/12/17 A.Suzuki --start--
            $this->smartyAssign = $this->Session->getParameter("smartyAssign");
            // add get lang 2008/12/17 A.Suzuki --end--
            
            ////////////////////////////////
            // edit data fill in session
            ////////////////////////////////
            $this->setEditData();
            
            // Modfy proxy 2011/12/06 Y.Nakao --start--
            $this->proxy = $this->getProxySetting();
            // Modfy proxy 2011/12/06 Y.Nakao --end--
            
            ////////////////////////////////
            // switch data get site
            ////////////////////////////////
            switch ($this->type_fill){
                case "pubmed":
                    $result = $this->fillPubMed($this->id_fill);
                    break;
                case "amazon":
                    $result = $this->fillAmazon($this->id_fill);
                    break;
                case "cinii":
                    $result = $this->fillCiNii($this->id_fill);
                    break;
                case "itemid":
                    $result = $this->fillItem_id($this->id_fill);
                    break;
                case "ichushi":
                    $result = $this->fillIchushi($this->id_fill);
                    break;
                default:
                    break;
            }

            if($result === false){
                $this->Session->setParameter("error_msg", $this->error_msg);
                return 'error';
            }
            
            ////////////////////////////////
            // set get data fill in session
            ////////////////////////////////
            if($this->fill_type == 0) {
                // fill PubMed or CiNii or Amazon
                // init edit data
                $this->initEditData();
                $this->setFillData();
            } elseif($this->fill_type == 1)  {
                // fill same item_id
                $this->setItemIDSameData($this->Result_List);
            }elseif($this->fill_type == 2) {
                // fill different item_id
                $this->setItemIDJunii2Data($this->Result_List);
            }
            // Add addin S.Arata 2012/08/02 --start--
            $this->addin->postExecute();
            // Add addin S.Arata 2012/08/02 --end--
            return 'success';

        } catch ( RepositoryException $Exception) {
            // error log
            $this->logFile(
                "SampleAction",
                "execute",
                $Exception->getCode(),
                $Exception->getMessage(),
                $Exception->getDetailMsg() );            
            // end action
            $this->exitAction(); // rollback
            // error
            $this->Session->setParameter("error_msg", $user_error_msg);
            return "error";
        }
    }
    
    /**
     * fill from PubMed
     *  + title (english)
     *  + language
     *  + keyword (english)
     *  + creator (english)
     *  + description (english)
     *  + jtitle (english)
     *  + volume
     *  + issue
     *  + spage
     *  + epage
     *  + dateofissued
     *  + issn
     *  + pmid
     *  + doi
     */
    function fillPubMed( $pmid ){
        // check space
        $pmid = str_replace(" ", "", $pmid); // str replace space
        $pmid = str_replace("　", "", $pmid); // str replace 2byte spase
        if($pmid==null || $pmid==""){
            $error = $this->smartyAssign->getLang("repository_item_fill_data_type_pubmed")
                    .$this->smartyAssign->getLang("repository_item_fill_data_no_metadata_error");
            array_push($this->error_msg, $error );
            return false;
        }
        
        // explode not number
        if(preg_match("/[^0-9]/", $pmid)){
            $error = $this->smartyAssign->getLang("repository_item_fill_data_type_pubmed")
                    .$this->smartyAssign->getLang("repository_item_fill_data_no_metadata_error");
            array_push($this->error_msg, $error );
            return false;
        }
    
        // request URL send for pubmed
        $fetch_url = 'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi';
        $fetch_arguments['db'] = 'pubmed';
        $fetch_arguments['retmode'] = 'xml';
        $url = $fetch_url.'?db='.$fetch_arguments['db'].'&id='.$pmid.'&retmode='.$fetch_arguments['retmode'];
        
        // Modfy proxy 2011/12/06 Y.Nakao --start--
        // get XML from pubmed
        $option = array( 
            "timeout" => "10",
            "allowRedirects" => true, 
            "maxRedirects" => 3
        );
        
        if($this->proxy['proxy_mode'] == 1)
        {
            $option = array( 
                    "timeout" => "10",
                    "allowRedirects" => true, 
                    "maxRedirects" => 3,
                    "proxy_host"=>$this->proxy['proxy_host'],
                    "proxy_port"=>$this->proxy['proxy_port'],
                    "proxy_user"=>$this->proxy['proxy_user'],
                    "proxy_pass"=>$this->proxy['proxy_pass']
                );
        }
        
        $http = new HTTP_Request($url, $option);
        // setting HTTP header
        $http->addHeader("User-Agent", $_SERVER['HTTP_USER_AGENT']); 
        $http->addHeader("Referer", $_SERVER['HTTP_REFERER']);
        
        /////////////////////////////
        // run HTTP request 
        /////////////////////////////
        $response = $http->sendRequest(); 
        if (!PEAR::isError($response)) { 
            $charge_code = $http->getResponseCode();// ResponseCode(200等)を取得 
            $charge_header = $http->getResponseHeader();// ResponseHeader(レスポンスヘッダ)を取得 
            $charge_body = $http->getResponseBody();// ResponseBody(レスポンステキスト)を取得 
            $charge_Cookies = $http->getResponseCookies();// クッキーを取得 
        }
        // Modfy proxy 2011/12/06 Y.Nakao --end--
        
        // get response
        $response_xml = $charge_body;
        $xml_parser = xml_parser_create();
        xml_parse_into_struct($xml_parser,$response_xml,$vals);
        xml_parser_free($xml_parser);
        
        $article_flg = true;
        $pubdate_flg = false;
        $article_list_flg = false;
        $mesh_head_flg = false;
        $auth_flg = false;
        $year = "";
        $month = "";
        $day = "";
        foreach($vals as $val){
            ///////////////////////////////
            // ARTICLE
            //////////////////////////////
            // tag check
            if($val['tag'] == "ARTICLE" && $val['type'] == "open"){
                $article_flg = true;
            } else if($val['tag'] == "ARTICLE" && $val['type'] == "close"){
                $article_flg = false;
            }
            // when in article tag
            if($article_flg == true){
                switch($val['tag']){
                    case "ARTICLETITLE":
                        $this->title_english = $val['value'];
                        break;
                    case "LANGUAGE":
                        // Fix pubmed fill 2011/12/16 Y.Nakao --start--
                        if($val['value']=="jpn")
                        {
                            $this->language = "ja";
                        }
                        else if($val['value']=="fra")
                        {
                            $this->language = "fr";
                        }
                        else if($val['value']=="ita")
                        {
                            $this->language = "it";
                        }
                        else if($val['value']=="deu")
                        {
                            $this->language = "de";
                        }
                        else if($val['value']=="esp")
                        {
                            $this->language = "es";
                        }
                        else if($val['value']=="rus")
                        {
                            $this->language = "ru";
                        }
                        else if($val['value']=="msr")
                        {
                            $this->language = "ms";
                        }
                        else if($val['value']=="arg")
                        {
                            $this->language = "ar";
                        }
                        else if($val['value']=="arg")
                        {
                            $this->language = "ar";
                        }
                        else
                        {
                            $this->language = "en";
                        }
                        // Fix pubmed fill 2011/12/16 Y.Nakao --end--
                        break;
                    case "ABSTRACTTEXT":
                        array_push($this->description_english, $val['value']);
                        break;
                    case "ISSN":
                        $this->issn = $val['value'];
                        break;
                    case "VOLUME":
                        $this->volume = $val['value'];
                        break;
                    case "ISSUE":
                        $this->issue = $val['value'];
                        break;
                    case "TITLE":
                        $this->jtitle_english = $val['value'];
                        break;
                    case "MEDLINEPGN":
                        $pages = $val['value'];
                        if($pages != ""){
                            $page = explode("-", $pages);
                            if($page[0]!=null && $page[0]!=""){
                                $this->spage = $page[0];
                            }
                            if(isset($page[1]) && $page[1]!=""){
                                if(intval($this->spage) > intval($page[1])){
                                    $page[1] = substr($page[0], 0, -(strlen($page[1]))).$page[1];
                                }
                                $this->epage = $page[1];
                            }
                        }
                        break;
                    case "PUBDATE":
                        if($val['type'] == "open"){
                            $pubdate_flg = true;
                        }else if($val['type'] == "close"){
                            $pubdate_flg = false;
                        }
                        break;
                    case "MEDLINEDATE":
                        if($pubdate_flg == true){
                            $this->dateofissued = $val['value'];
                        }
                        break;
                    case "YEAR":
                        if($pubdate_flg == true){
                            $year = $val['value'];
                        }
                        break;
                    case "MONTH":
                        if($pubdate_flg == true){
                            $month = $val['value'];
                        }
                        break;
                    case "DAY":
                        if($pubdate_flg == true){
                            $day = $val['value'];
                        }
                        break;
                    default:
                }
                ////////////////////////
                // tag author 
                ////////////////////////
                if($val['tag'] == "AUTHOR" && $val['type'] == "open"){
                    $author = array("family"=>"","given"=>"","family_ruby"=>"","given_ruby"=>"","email"=>"","author_id"=>"","language"=>"","external_author_id"=>array(array("prefix_id"=>"", "suffix"=>"", "old_prefix_id"=>"", "old_suffix"=>"", "prefix_name"=>"")));
                    $auth_flg = true;
                } else if($val['tag'] == "AUTHOR" && $val['type'] == "close"){
                    if($author["family"]!=""){
                        array_push($this->creator_english, $author);
                    }
                    $auth_flg = false;
                }
                if($auth_flg){
                    if($val['tag'] =="LASTNAME"){
                        $author["family"] = $val['value'];
                    }
                    if($val['tag'] =="FORENAME"){
                        if($author["family"]!=null && $author["family"]!=""){
                            $author["given"] = $val['value'];
                        }
                    }
                }
            }
            
            ///////////////////////////////
            // MeshHeadingList
            //////////////////////////////
            // tag check MeshHeadingList
            if($val['tag'] == "MESHHEADINGLIST" && $val['type'] == "open"){
                $mesh_head_flg = true;
            } else if($val['tag'] == "MESHHEADINGLIST" && $val['type'] == "close"){
                $mesh_head_flg = false;
            }
            // when in MeshHeadingList tag
            if($mesh_head_flg){
                switch($val['tag']){
                    case "DESCRIPTORNAME":
                        array_push($this->keyword_english, $val['value']);
                        break;
                    default:
                        break;
                }
            }
            
            ///////////////////////////////
            // ArticleIdList
            //////////////////////////////
            // tag check ArticleIdList
            if($val['tag'] == "ARTICLEIDLIST" && $val['type'] == "open"){
                $article_list_flg = true;
            } else if($val['tag'] == "ARTICLEIDLIST" && $val['type'] == "close"){
                $article_list_flg = false;
            }
            // when in ArticleIdList tag
            if($article_list_flg && isset($val['attributes']['IDTYPE'])){
                switch($val['attributes']['IDTYPE']){
                    case "pubmed":
                        if($pmid != $val['value']){
                            $error = $this->smartyAssign->getLang("repository_item_fill_data_type_pubmed")
                                    .$this->smartyAssign->getLang("repository_item_fill_data_no_metadata_error");
                            array_push($this->error_msg, $error );
                            return false;
                        }
                        $this->pmid = $val['value'];
                        break;
                    case "doi":
                        $this->doi = $val['value'];
                        break;
                    default:
                        break;
                }
            }
        }
        
        if($this->pmid == null){
            $error = $this->smartyAssign->getLang("repository_item_fill_data_type_pubmed")
                    .$this->smartyAssign->getLang("repository_item_fill_data_no_metadata_error");
            array_push($this->error_msg, $error );
            return false;
        }

        // 月の変換
        if($month != null) {
            switch($month) {
                case "Jan":
                    $month = "1";
                    break;
                case "Feb":
                    $month = "2";
                    break;
                case "Mar":
                    $month = "3";
                    break;
                case "Apr":
                    $month = "4";
                    break;
                case "May":
                    $month = "5";
                    break;
                case "Jun":
                    $month = "6";
                    break;
                case "Jul":
                    $month = "7";
                    break;
                case "Aug":
                    $month = "8";
                    break;
                case "Sep":
                    $month = "9";
                    break;
                case "Oct":
                    $month = "10";
                    break;
                case "Nov":
                    $month = "11";
                    break;
                case "Dec":
                    $month = "12";
                    break;
                default:
                    $month = "";
                    $day = "";
            }
        }
        
        // 日付の連結
        if($year != ""){
            $this->dateofissued = $year;
            if($month =! ""){
                $this->dateofissued .= "-".$month;
                if($day =! ""){
                    $this->dateofissued .= "-".$day;
                }
            }
        }

        return true;
    }

    /**
     * fill from Amazon
     *  + title (japanese, english)
     *  + language
     *  + publisher (japanese, english)
     *  + jtitle (japanese, english)
     *  + spage
     *  + epage
     *  + dateofissued
     */
    function fillAmazon( $asin ){
        /////////////////////////////
        // check asin
        /////////////////////////////
        $asin = str_replace(" ", "", $asin); // str replace space
        $asin = str_replace("　", "", $asin); // str replace 2byte spase
        if($asin==null || $asin==""){
            $error = $this->smartyAssign->getLang("repository_item_fill_data_type_amazon")
                    .$this->smartyAssign->getLang("repository_item_fill_data_no_metadata_error");
            array_push($this->error_msg, $error );
            return false;
        }
        
        // add ISBN-10 to ISBN-13 convert 2008/11/20 A.Suzuki --start--
        // ハイフンと空白を削除
        $asin = str_replace("-", "", trim($asin));

        // 13桁ならばISBN-10に変換
        if(strlen($asin) == 13) {
            $asin = substr($asin, 3, -1);
            
            // 1文字ずつ分解
            $isbn_array = str_split($asin);
            
            $digit = 0;
                for($ii=0; $ii<count($isbn_array); $ii++){
                $digit += $isbn_array[$ii] * (10-$ii);
            }
            $digit %= 11;
            $digit = 11 - $digit;
            if($digit == 10){
                $digit = 'X';
            }
            $asin .= $digit;
        }
        // add ISBN-10 to ISBN-13 convert 2008/11/20 A.Suzuki --end--
        
        /////////////////////////////
        // send data init
        /////////////////////////////
        //$send_param = '';
        
        /////////////////////////////
        // set url
        /////////////////////////////
        $lang = $this->Session->getParameter("_lang");
        switch ( $lang ) {
            case "japanese": // japan
                $amazon_url = 'http://ecs.amazonaws.jp/onca/xml';
                break;
            //case "french": // france
            //    $send_param .= 'http://webservices.amazon.fr/onca/xml';
            //    break;
            //case "german": // german
            //    $send_param .= 'http://webservices.amazon.de/onca/xml';
            //    break;
            default: // us
                $amazon_url = 'http://ecs.amazonaws.jp/onca/xml';
                break;
        }
        
        /////////////////////////////
        // check AWSAccessKeyId
        /////////////////////////////
        $query = "SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter ".
                 "WHERE param_name = ?; ";
        $param = array();
        $param[] = "AWSSecretAccessKey";
        $result = $this->Db->execute($query, $param);
        if($result === false){
            $error = $this->smartyAssign->getLang("repository_item_fill_data_type_amazon")
                    .$this->smartyAssign->getLang("repository_item_fill_data_no_metadata_error");
            array_push($this->error_msg, $error );
            return false;
        }
        if(count($result)!=1 || $result[0]["param_value"]==""){
            array_push($this->error_msg, $this->smartyAssign->getLang("repository_item_fill_data_no_key_id"));
            return false;
        }

        ///////////////////////////////
        //secret_access_key
        //////////////////////////////
        $secret_access_key = $result[0]["param_value"];
        
        /////////////////////////////
        // set fetcher conditions
        /////////////////////////////
        $query = "SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter ".
                 "WHERE param_name = ?; ";
        $param = array();
        $param[] = "AWSAccessKeyId";
        $result = $this->Db->execute($query, $param);
        if($result === false){
            $error = $this->smartyAssign->getLang("repository_item_fill_data_type_amazon")
                    .$this->smartyAssign->getLang("repository_item_fill_data_no_metadata_error");
            array_push($this->error_msg, $error );
            return false;
        }
        if(count($result)!=1 || $result[0]["param_value"]==""){
            array_push($this->error_msg, $this->smartyAssign->getLang("repository_item_fill_data_no_key_id"));
            return false;
        }
        $AWSAccessKeyId = $result[0]['param_value'];
        
        // Add AssociateTag for modify API Y.Nakao 2011/10/19 --start--
        /////////////////////////////
        // set AssociateTag
        /////////////////////////////
        $query = "SELECT param_value ".
                " FROM ". DATABASE_PREFIX ."repository_parameter ".
                " WHERE param_name = ?; ";
        $param = array();
        $param[] = "AssociateTag";
        $result = $this->Db->execute($query, $param);
        if($result === false){
            $error = $this->smartyAssign->getLang("repository_item_fill_data_type_amazon")
                    .$this->smartyAssign->getLang("repository_item_fill_data_no_metadata_error");
            array_push($this->error_msg, $error );
            return false;
        }
        if(count($result)!=1 || $result[0]["param_value"]==""){
            array_push($this->error_msg, $this->smartyAssign->getLang("repository_item_fill_data_no_key_id"));
            return false;
        }
        $AssociateTag = $result[0]['param_value'];
        // Add AssociateTag for modify API Y.Nakao 2011/10/19 --end--
        
        $send_param = array();
        $send_param['AssociateTag'] = $AssociateTag;
        $send_param['AWSAccessKeyId']=$AWSAccessKeyId;
        $send_param['ContentType']='text/xml';
        $send_param['Operation']='ItemLookup';
        $send_param['Service']='AWSECommerceService';
        $send_param['Version']='2011-10-26';          // version is check this site.http://aws.amazon.com/releasenotes/Product%20Advertising%20API?_encoding=UTF8&jiveRedirect=1
        
        // ItemLookupRequest
        $send_param['IdType']='ISBN';
        $send_param['MerchantId']='All';
        $send_param['ItemId']=$asin;
        $send_param['ResponseGroup']='Medium';
        $send_param['SearchIndex'] = 'Books';
        
        $send_param['Timestamp']=gmdate("Y-m-d\TH:i:s\Z");

        //Sort an array by Hashkey
        ksort($send_param);

        //create REST Paramater
        $parameters = array();
        foreach ($send_param as $key => $value) {
         $parameters[] = "{$key}=". rawurlencode($value);
        }
        $queryString = implode('&', $parameters);
        
        /* add paramater */
        $signatureRequestFormat = "GET\necs.amazonaws.jp\n/onca/xml\n%s";
        $signatureRequestUrl = sprintf($signatureRequestFormat, $queryString);

        /* sha256 Hash Calculation */
        $hashString = base64_encode(hash_hmac("sha256", $signatureRequestUrl, $secret_access_key, true));
        $signature = rawurlencode($hashString);

        /* create With Prominent Request */
        $requestWithSignature = "http://ecs.amazonaws.jp/onca/xml?{$queryString}&Signature={$signature}";
        
        /////////////////////////////
        // HTTP_Request init
        /////////////////////////////
        // send http request
        $option = array( 
            "timeout" => "10",
            "allowRedirects" => true, 
            "maxRedirects" => 3, 
        );
        // Modfy proxy 2011/12/06 Y.Nakao --start--
        if($this->proxy['proxy_mode'] == 1)
        {
            $option = array( 
                    "timeout" => "10",
                    "allowRedirects" => true, 
                    "maxRedirects" => 3,
                    "proxy_host"=>$this->proxy['proxy_host'],
                    "proxy_port"=>$this->proxy['proxy_port'],
                    "proxy_user"=>$this->proxy['proxy_user'],
                    "proxy_pass"=>$this->proxy['proxy_pass']
                );
        }
        // Modfy proxy 2011/12/06 Y.Nakao --end--
        
        $http = new HTTP_Request($requestWithSignature, $option);
        // setting HTTP header
        $http->addHeader("User-Agent", $_SERVER['HTTP_USER_AGENT']); 
        $http->addHeader("Referer", $_SERVER['HTTP_REFERER']);
        
        /////////////////////////////
        // run HTTP request 
        /////////////////////////////
        $response = $http->sendRequest(); 
        if (!PEAR::isError($response)) { 
            $charge_code = $http->getResponseCode();// ResponseCode(200等)を取得 
            $charge_header = $http->getResponseHeader();// ResponseHeader(レスポンスヘッダ)を取得 
            $charge_body = $http->getResponseBody();// ResponseBody(レスポンステキスト)を取得 
            $charge_Cookies = $http->getResponseCookies();// クッキーを取得 
        }
        // get response
        $response_xml = $charge_body;
        
        /////////////////////////////
        // parse response XML
        /////////////////////////////
        try{
            $xml_parser = xml_parser_create();
            $rtn = xml_parse_into_struct( $xml_parser, $response_xml, $vals );
            if($rtn == 0){
                $error = $this->smartyAssign->getLang("repository_item_fill_data_type_amazon")
                        .$this->smartyAssign->getLang("repository_item_fill_data_no_metadata_error");
                array_push($this->error_msg, $error );
                return false;
            }
            xml_parser_free($xml_parser);
        } catch(Exception $ex){
            $error = $this->smartyAssign->getLang("repository_item_fill_data_type_amazon")
                    .$this->smartyAssign->getLang("repository_item_fill_data_no_metadata_error");
            array_push($this->error_msg, $error );
            return false;
        }
        
        /////////////////////////////
        // get fill data
        /////////////////////////////
        $item_info = false;
        $lang_flg = false;
        $get_ASIN = null;
        $title = "";
        $jtitle = "";
        $publisher = array();
        $creator = array();
        for ( $ii = 0; $ii < count($vals); $ii++ ) {
            // check tag
            if($vals[$ii]['tag'] == "ITEMATTRIBUTES"){
                if($vals[$ii]['type'] == "open"){
                    // item attributes tag open
                    $item_info = true;
                }
                if($vals[$ii]['type'] == "close"){
                    // item attributes tag close
                    $item_info = false;
                }
            }
            // check tag level
            if($item_info){
                // fill biblio info
                switch($vals[$ii]['tag']){
                    case "TITLE":
                        // title
                        $title = $vals[$ii]['value'];
                        // jtitle
                        $jtitle = $vals[$ii]['value'];
                        break;
                    case "NUMBEROFPAGES":
                        // spage, epage
                        $this->spage = "1";
                        $this->epage = $vals[$ii]['value'];
                        break;
                    case "PUBLICATIONDATE":
                        // dateofissued
                        $this->dateofissued = $vals[$ii]['value'];
                        // delimit year, month, day
                        $date = explode("-", $vals[$ii]['value']);
                        if(isset($date[0])) {
                            $this->year = $date[0];
                        }
                        if(isset($date[1])){
                            $this->month = $date[1];
                        }
                        if(isset($date[2])){
                            $this->day = $date[2];
                        }
                        break;
                    case "PUBLISHER":
                        array_push($publisher, $vals[$ii]['value']);
                        break;
                    case "AUTHOR":
                        // creator
                        $author_array = explode(" ", $vals[$ii]['value'],2);
                        for($jj=0; $jj<count($author_array);$jj+=2){
                            $author = array("family"=>"","given"=>"","family_ruby"=>"","given_ruby"=>"","email"=>"","author_id"=>"","language"=>"","external_author_id"=>array(array("prefix_id"=>"", "suffix"=>"", "old_prefix_id"=>"", "old_suffix"=>"", "prefix_name"=>"")));
                            $author["family"] = $author_array[$jj];
                            $author["given"] = "";
                            if(isset($author_array[$jj+1]))
                            {
                                $author["given"] = $author_array[$jj+1];
                            }
                            array_push($creator, $author);
                        }
                        break;
                    // Add JuNii2 ver3 2013/09/24 R.Matsuura --start--
                    case "ISBN":
                        // isbn
                        $this->isbn = $vals[$ii]['value'];
                        break;
                    // Add JuNii2 ver3 2013/09/24 R.Matsuura --end--
                    default:
                        break;
                }
                //////////////
                // lang tag
                /////////////
                if($vals[$ii]['tag'] == "LANGUAGES"){
                    if($vals[$ii]['type'] == "open"){
                        // languages tag open
                        $lang_flg = true;
                    }
                    if($vals[$ii]['type'] == "close"){
                        // languages tag close
                        $lang_flg = false;
                    }
                }
                if($lang_flg){
                    switch($vals[$ii]['tag']){
                        case "NAME":
                            if($lang == "japanese"){
                                if($vals[$ii]['value'] == "日本語"){
                                    $this->language = "ja";
                                } else if($vals[$ii]['value'] == "英語"){
                                    $this->language = "en";
                                }
                            } else {
                                if($vals[$ii]['value'] == "Japanese"){
                                    $this->language = "ja";
                                } else if($vals[$ii]['value'] == "English"){
                                    $this->language = "en";
                                }
                            }
                            break;
                        default:
                            break;
                    }
                }
            }
            
            if($vals[$ii]['tag'] == "ASIN") {
                if($vals[$ii]['value'] == $asin){
                    $get_ASIN = $vals[$ii]['value'];
                }
            }
        }
        
        if($get_ASIN == null){
            $error = $this->smartyAssign->getLang("repository_item_fill_data_type_amazon")
                    .$this->smartyAssign->getLang("repository_item_fill_data_no_metadata_error");
            array_push($this->error_msg, $error );
            return false;
        }
        
        // 日英チェック
        if($this->language == "en"){
            $this->title_english = $title;
            $this->jtitle_english = $jtitle;
            $this->publisher_english = $publisher;
            $this->creator_english = $creator;
        } else {
            $this->title = $title;
            $this->jtitle = $jtitle;
            $this->publisher = $publisher;
            $this->creator = $creator;
        }
        
        return true;
    }
    
    /**
     * fill from CiNii
     *  + title (japanese, english)
     *  + language
     *  + keyword (japanese, english)
     *  + creator (japanese, english)
     *  + contributor (japanese, english)
     *  + publisher (japanese, english)
     *  + jtitle (japanese, english)
     *  + spage
     *  + epage
     *  + dateofissued
     */
    function fillCiNii( $naid ){
        // check space
        $naid = str_replace(" ", "", $naid); // str replace space
        $naid = str_replace("　", "", $naid); // str replace 2byte spase
        if($naid==null || $naid==""){
            $error = $this->smartyAssign->getLang("repository_item_fill_data_type_cinii")
                    .$this->smartyAssign->getLang("repository_item_fill_data_no_metadata_error");
            array_push($this->error_msg, $error );
            return false;
        }
        
        // explode not number
        if(preg_match("/[^0-9]/", $naid)){
            $error = $this->smartyAssign->getLang("repository_item_fill_data_type_cinii")
                    .$this->smartyAssign->getLang("repository_item_fill_data_no_metadata_error");
            array_push($this->error_msg, $error );
            return false;
        }
    
        // request URL send for CiNii
        $send_param = "";
        $send_param .= 'http://ci.nii.ac.jp/naid/';
        $send_param .= $naid;
        $send_param .= '.rdf';
        
        /////////////////////////////
        // HTTP_Request init
        /////////////////////////////
        // send http request
        $option = array( 
            "timeout" => "10",
            "allowRedirects" => true, 
            "maxRedirects" => 3, 
        );
        
        // Modfy proxy 2011/12/06 Y.Nakao --start--
        if($this->proxy['proxy_mode'] == 1)
        {
            $option = array( 
                    "timeout" => "10",
                    "allowRedirects" => true, 
                    "maxRedirects" => 3,
                    "proxy_host"=>$this->proxy['proxy_host'],
                    "proxy_port"=>$this->proxy['proxy_port'],
                    "proxy_user"=>$this->proxy['proxy_user'],
                    "proxy_pass"=>$this->proxy['proxy_pass']
                );
        }
        // Modfy proxy 2011/12/06 Y.Nakao --end--
        
        $http = new HTTP_Request($send_param, $option);
        // setting HTTP header
        $http->addHeader("User-Agent", $_SERVER['HTTP_USER_AGENT']); 
        $http->addHeader("Referer", $_SERVER['HTTP_REFERER']);
        
        /////////////////////////////
        // run HTTP request 
        /////////////////////////////
        $response = $http->sendRequest(); 
        if (!PEAR::isError($response)) { 
            $charge_code = $http->getResponseCode();// ResponseCode(200等)を取得 
            $charge_header = $http->getResponseHeader();// ResponseHeader(レスポンスヘッダ)を取得 
            $charge_body = $http->getResponseBody();// ResponseBody(レスポンステキスト)を取得 
            $charge_Cookies = $http->getResponseCookies();// クッキーを取得 
        }
        // get response
        $response_xml = $charge_body;
        
        /////////////////////////////
        // parse response XML
        /////////////////////////////
        try{
            $xml_parser = xml_parser_create();
            $rtn = xml_parse_into_struct( $xml_parser, $response_xml, $vals );
            if($rtn == 0){
                $error = $this->smartyAssign->getLang("repository_item_fill_data_type_cinii")
                        .$this->smartyAssign->getLang("repository_item_fill_data_no_metadata_error");
                array_push($this->error_msg, $error );
                return false;
            }
            xml_parser_free($xml_parser);
        } catch(Exception $ex){
            $error = $this->smartyAssign->getLang("repository_item_fill_data_type_cinii")
                    .$this->smartyAssign->getLang("repository_item_fill_data_no_metadata_error");
            array_push($this->error_msg, $error );
            return false;
        }
        
        /////////////////////////////
        // get fill data
        /////////////////////////////
        $english_flg = false;
        $organization_flg = false;
        
        foreach($vals as $val){
            switch($val['tag']){
                case "DC:TITLE":
                    if($val['value'] != null && $val['value'] != ""){
                        if($this->title == "" || $this->title_english == ""){
                            if ($english_flg == true){
                                $this->title_english = $val['value'];
                            } else {
                                $this->title = $val['value'];
                            }
                        } else {
                            array_push($this->alternative, $val['value']);
                        }
                    }
                    break;
                case "FOAF:TOPIC":
                    if($val['attributes']['RDF:RESOURCE'] != null && $val['attributes']['RDF:RESOURCE'] != ""){
                        // 1回のURLデコードではなぜか戻らない場合があるため2重にデコード
                        $keyword = urldecode(str_replace("http://ci.nii.ac.jp/keyword/", "", $val['attributes']['RDF:RESOURCE']));
                        if($english_flg == true){
                            array_push($this->keyword_english, urldecode($keyword));
                        } else {
                            array_push($this->keyword, urldecode($keyword));
                        }
                        
                    }
                    break;
                case "FOAF:NAME":
                    if($val['value'] != null && $val['value'] != ""){
                        if($organization_flg == false){
                            // 著者名
                            $author = array("family"=>"","given"=>"","family_ruby"=>"","given_ruby"=>"","email"=>"","author_id"=>"","language"=>"","external_author_id"=>array(array("prefix_id"=>"", "suffix"=>"", "old_prefix_id"=>"", "old_suffix"=>"", "prefix_name"=>"")));
                            // Fix filldata for NAID 2012/01/17 Y.Nakao --start--
                            $author_array = explode(" ", $val['value']);
                            if(isset($author_array[0]))
                            {
                                 $author["family"] = array_shift($author_array);
                            }
                            if(count($author_array) > 0)
                            {
                                 $author["given"] = implode(" ", $author_array);
                            }
                            // Fix filldata for NAID 2012/01/17 Y.Nakao --start--

                            if(isset($val['attributes']['XML:LANG']) && $val['attributes']['XML:LANG'] == "en"){
                                array_push($this->creator_english, $author);
                            } else {
                                array_push($this->creator, $author);
                            }
                        } else {
                            // 著者所属
                            if(isset($val['attributes']['XML:LANG']) && $val['attributes']['XML:LANG'] == "en"){
                                array_push($this->contributor_english, $val['value']);
                            } else {
                                array_push($this->contributor, $val['value']);
                            }
                        }
                    }
                    break;
                case 'DC:PUBLISHER':
                    if($english_flg == true){
                        if($val['value'] != null && $val['value'] != ""){
                            array_push($this->publisher_english, $val['value']);
                        }
                    } else {
                        if($val['value'] != null && $val['value'] != ""){
                            array_push($this->publisher, $val['value']);
                        }
                    }
                    break;
                case "PRISM:PUBLICATIONNAME":
                    if ($english_flg == true){
                        $this->jtitle_english = $val['value'];
                    } else {
                        $this->jtitle = $val['value'];
                    }
                    break;
                case "PRISM:ISSN":
                    if($this->issn == ""){
                        if($val['value'] != null && $val['value'] != ""){
                            $this->issn = $val['value'];
                        }
                    }
                    break;
                case "PRISM:VOLUME":
                    if($this->volume == ""){
                        if($val['value'] != null && $val['value'] != ""){
                            $this->volume = ltrim($val['value'], "0");
                        }
                    }
                    break;
                case "PRISM:NUMBER":
                    if($this->issue == ""){
                        if($val['value'] != null && $val['value'] != ""){
                            $this->issue = ltrim($val['value'], "0");
                        }
                    }
                    break;
                case "PRISM:STARTINGPAGE":
                    if($this->spage == ""){
                        if($val['value'] != null && $val['value'] != ""){
                            $this->spage = $val['value'];
                        }
                    }
                    break;
                case "PRISM:ENDINGPAGE":
                    if($this->epage == ""){
                        if($val['value'] != null && $val['value'] != ""){
                            $this->epage = $val['value'];
                        }
                    }
                    break;
                // Add case "PRISM:PAGERANGE" 2014/04/03 A.Suzuki --start--
                case "PRISM:PAGERANGE":
                    if(strlen($this->spage)==0 && strlen($this->epage)==0){
                        $tmp = explode("-",$val['value'], 2);
                        $this->spage = $tmp[0];
                        if(isset($tmp[1])){
                            $this->epage = $tmp[1];
                        }
                    }
                    break;
                // Add case "PRISM:PAGERANGE" 2014/04/03 A.Suzuki --end--
                case "PRISM:PUBLICATIONDATE":
                    if($this->dateofissued == ""){
                        if($val['value'] != null && $val['value'] != ""){
                            $this->dateofissued = $val['value'];
                        }
                    }
                    break;
                case "DC:DESCRIPTION":
                    if($english_flg == true){
                        if($val['value'] != null && $val['value'] != ""){
                            array_push($this->description_english, $val['value']);
                        }
                    } else {
                        if($val['value'] != null && $val['value'] != ""){
                            array_push($this->description, $val['value']);
                        }
                    }
                    break;
                case "RDF:DESCRIPTION":
                    if(isset($val['attributes']['XML:LANG']) && $val['attributes']['XML:LANG'] == "en"){
                        $english_flg = true;
                    }
                    if($english_flg == true && $val['type'] == "close"){
                        $english_flg = false;
                    }
                    break;
                case "FOAF:ORGANIZATION":
                    if($val['type'] == "open"){
                        $organization_flg = true;
                    }
                    if($organization_flg == true && $val['type'] == "close"){
                        $organization_flg = false;
                    }
                    break;
                case "CINII:NAID":
                    $this->naid = $val['value'];
                    break;
                // Add NCID 2014/04/03 A.Suzuki --start--
                case "CINII:NCID":
                    $this->ncid = $val['value'];
                    break;
                // Add NCID 2014/04/03 A.Suzuki --end--
                default:
            }
        }
        
        if($this->title != ""){
            $this->language = "ja";
        } else {
            $this->language = "en";
        }
        
        if($this->title == "" && $this->title_english == ""){
            $error = $this->smartyAssign->getLang("repository_item_fill_data_type_cinii")
                    .$this->smartyAssign->getLang("repository_item_fill_data_no_metadata_error");
            array_push($this->error_msg, $error );
            return false;
        }
        
        // explode spage
        if($this->spage != "" && $this->epage == ""){
            $pages = explode("-", $this->spage);
            $this->spage = $pages[0];
            $this->epage = $pages[1];
        }
        
        return true;
    }
        
    /**
     * Edit data fill in session
     */
    function setEditData(){
        // セッション情報取得
        $item_type_all = $this->Session->getParameter("item_type_all");            // 1.アイテムタイプ,  アイテムタイプのレコードをそのまま保存したものである。
        $item_attr_type = $this->Session->getParameter("item_attr_type");        // 2.アイテム属性タイプ (Nレコード, Order順) : ""[N][''], アイテム属性タイプの必要部分を連想配列で保持したものである。
        $item_num_cand = $this->Session->getParameter("item_num_cand");            // 3.アイテム属性選択肢数 (N) : "item_num_cand"[N], 選択肢のない属性タイプは0を設定
        $option_data = $this->Session->getParameter("option_data");                // 4.アイテム属性選択肢 (N): "option_data"[N][M], N属性タイプごとの選択肢。Mはアイテム属性選択肢数に対応。0～                
        // ユーザ入力値＆変数
        $item_num_attr = $this->Session->getParameter("item_num_attr");            // 5.アイテム属性数 (N): "item_num_attr"[N], N属性タイプごとの属性数-。複数可な属性タイプのみ>1の値をとる。
        $item_attr_old = $this->Session->getParameter("item_attr");                // 6.アイテム属性 (N) : "item_attr"[N][L], N属性タイプごとの属性。Lはアイテム属性数に対応。1～        
        $item_attr = array();                                                    // 6.アイテム属性 。これはリクエストから全部
        $edit_flag = $this->Session->getParameter("edit_flag");                    // X.処理モード : edit_flag (0:新規作成, 1:既存編集)
        
        // カウンタ
        $cnt_text = 0;        // "text"属性カウンタ
        $cnt_textarea = 0;    // "textarea"属性カウンタ
        $cnt_name = 0;        // "name"属性カウンタ
        $cnt_link = 0;        // "link"属性カウンタ
        $cnt_select = 0;    // "select"属性カウンタ
        $cnt_checkbox = 0;    // "checkbox"属性カウンタ
        $cnt_radio = 0;        // "radio"属性カウンタ
        $cnt_biblio = 0;    // "biblio_info"属性カウンタ 2008/08/11
        $cnt_date = 0;        // "date"属性カウンタ 2008/10/14
        $cnt_author_id = 0; // "name"属性外部著者IDカウンタ

        // ------------------------------------------------------------
        // セッション情報保存 (全オプション共通)
        // ------------------------------------------------------------        
        
        // アイテム基本属性をセッションに保存        
        $this->Session->setParameter("base_attr", array( 
                "title" => ($this->base_attr[0]==' ') ? '' : $this->base_attr[0],            // ?3項演算子OK.
            "title_english" => ($this->base_attr[1]==' ') ? '' : $this->base_attr[1],    // ?3項演算子OK.
            "language" => $this->base_attr[2])
        );        
        // アイテム公開日ををセッションに保存    
        $this->Session->setParameter("item_pub_date", array(
                "year" => ($this->item_pub_date_year == ' ') ? '' : $this->item_pub_date_year,
                "month" => $this->item_pub_date_month,
                "day" => $this->item_pub_date_day
            )
        );
        // アイテムキーワードをセッションに保存        
        $this->Session->setParameter("item_keyword", $this->item_keyword);
        $this->Session->setParameter("item_keyword_english", $this->item_keyword_english);

        // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --start--
        // ------------------------------------------------------------------
        // Contributor
        // ------------------------------------------------------------------
        $item_contributor = null;
        if($this->item_contributor_handle == " ")
        {
            $this->item_contributor_handle = "";
        }
        if($this->item_contributor_name == " ")
        {
            $this->item_contributor_name = "";
        }
        if($this->item_contributor_email == " ")
        {
            $this->item_contributor_email = "";
        }
        
        if(strlen($this->item_contributor) > 0 && $this->item_contributor == "1")
        {
            $item_contributor = array(
                RepositoryConst::ITEM_CONTRIBUTOR_HANDLE => $this->item_contributor_handle,
                RepositoryConst::ITEM_CONTRIBUTOR_NAME => $this->item_contributor_name,
                RepositoryConst::ITEM_CONTRIBUTOR_EMAIL => $this->item_contributor_email);
        }
        $this->Session->setParameter(RepositoryConst::SESSION_PARAM_ITEM_CONTRIBUTOR, $item_contributor);
        // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --end--

        // アイテム追加属性(メタデータ)をセッションに保存
        // ii-thメタデータのリクエストを保存
        for($ii=0; $ii<count($item_attr_type); $ii++) {
            $attr_elm = array();        // 1メタデータの値列
            // ii-thメタデータのjj-th属性値のリクエストを保存
            for($jj=0; $jj<$item_num_attr[$ii]; $jj++) {
                // ii-thメタデータの入力形式ごとのリクエストを保存
                switch($item_attr_type[$ii]['input_type']) {
                case 'text':
                    // 空白チェック
                    if($this->item_attr_text[$cnt_text]==' ') {
                        array_push($attr_elm, '');
                    } else {
                        array_push($attr_elm, $this->item_attr_text[$cnt_text]);
                    }
                    $cnt_text++;
                    break;
                case 'link':
                    // 空白チェック
                    //URL
                    if($this->item_attr_link[$cnt_link]==' ') {
                        $link_url = "";
                    } else {
                        $link_url = $this->item_attr_link[$cnt_link];
                    }
                    // 表示名
                    if($this->item_attr_link_name[$cnt_link]==' ') {
                        $link_name = "";
                    } else {
                        $link_name = $this->item_attr_link_name[$cnt_link];
                    }
                    if($link_name != ""){
                        array_push($attr_elm, $link_url."|".$link_name);
                        $metadata["attribute_value"] = $link_url."|".$link_name;
                    } else {
                        array_push($attr_elm, $link_url);
                        $metadata["attribute_value"] = $link_url;
                    }
                    $cnt_link++;
                    break;
                case 'name':
                    $family = '';
                    $given = '';
                    $family_ruby = '';
                    $given_ruby = '';
                    $email = '';
                    $author_id = '';
                    $language = $item_attr_type[$ii]['display_lang_type'];
                    $external_author_id = array();
                    
                    // 空白チェック
                    if($this->item_attr_name_family[$cnt_name]!=' ') {
                        $family = $this->item_attr_name_family[$cnt_name];
                    }
                    if($this->item_attr_name_given[$cnt_name]!=' ') {
                        $given = $this->item_attr_name_given[$cnt_name];
                    }
                    if($language == "japanese"){
                        if($this->item_attr_name_family_ruby[$cnt_name]!=' ') {
                            $family_ruby = $this->item_attr_name_family_ruby[$cnt_name];
                        }
                        if($this->item_attr_name_given_ruby[$cnt_name]!=' ') {
                            $given_ruby = $this->item_attr_name_given_ruby[$cnt_name];
                        }
                    }
                    if($this->item_attr_name_email[$cnt_name]!=' ') {
                        $email = $this->item_attr_name_email[$cnt_name];
                    }
                    
                    for($kk=0; $kk<count($item_attr_old[$ii][$jj]["external_author_id"]); $kk++){
                        $external_author_id_prefix = '';
                        $external_author_id_suffix = '';
                        $external_author_id_prefix_name = '';
                        if($this->item_attr_name_author_id_prefix[$kk+$cnt_author_id]!=0) {
                            $external_author_id_prefix = $this->item_attr_name_author_id_prefix[$kk+$cnt_author_id];
                        }
                        if($this->item_attr_name_author_id_suffix[$kk+$cnt_author_id]!=' ') {
                            $external_author_id_suffix = $this->item_attr_name_author_id_suffix[$kk+$cnt_author_id];
                        }
                        array_push($external_author_id, array('prefix_id'=>$external_author_id_prefix, 'suffix'=>$external_author_id_suffix, 'old_prefix_id'=>$item_attr_old[$ii][$jj]["external_author_id"][$kk]["prefix_id"], 'old_suffix'=>$item_attr_old[$ii][$jj]["external_author_id"][$kk]["suffix"], 'prefix_name'=>$external_author_id_prefix_name));
                    }
                    $cnt_author_id = $cnt_author_id + $kk;
                    
                    $author_id = intval($item_attr_old[$ii][$jj]["author_id"]);
                    // 氏名属性は1属性につき姓、名、E-mailを保存
                    array_push($attr_elm, array(
                            'family' => $family,
                            'given' => $given,
                            'family_ruby' => $family_ruby,
                            'given_ruby' => $given_ruby,
                            'email' => $email,
                            'author_id' => $author_id,
                            'language' => $language,
                            'external_author_id' => $external_author_id
                        )
                    );
                    $cnt_name++;
                    break;
                case 'textarea':
                    // 空白チェック
                    if($this->item_attr_textarea[$cnt_textarea]==' ') {
                        array_push($attr_elm, '');
                    } else {
                        array_push($attr_elm, $this->item_attr_textarea[$cnt_textarea]);
                    }
                    $cnt_textarea++;
                    break;
                case 'select':
                    array_push($attr_elm, $this->item_attr_select[$cnt_select]);
                    $cnt_select++;
                    break;
                case 'checkbox':
                    // 0 or 1(チェックボックスの数だけ)
                    for($kk=0; $kk<count($option_data[$ii]); $kk++){
                        array_push($attr_elm, $this->item_attr_checkbox[$cnt_checkbox]);    // チェックON
                        $cnt_checkbox++;
                    }
                    break;
                case 'radio':
                    // 選択番号。
                    array_push($attr_elm, $this->item_attr_radio[$cnt_radio]);
                    $cnt_radio++;
                    break;
                case 'biblio_info':
                    $biblio_name = '';
                    $biblio_name_english = '';
                    $volume = '';
                    $issue = '';
                    $spage = '';
                    $epage = '';
                    $year = '';
                    $month = '';
                    $day = '';
                    $dateofissued = '';
                    
                    // 空白チェック
                    if($this->item_attr_biblio_name[$cnt_biblio]!=' ') {
                        $biblio_name = $this->item_attr_biblio_name[$cnt_biblio];
                    }
                    if($this->item_attr_biblio_name_english[$cnt_biblio]!=' ') {
                        $biblio_name_english = $this->item_attr_biblio_name_english[$cnt_biblio];
                    }
                    if($this->item_attr_biblio_volume[$cnt_biblio]!=' ') {
                        $volume = $this->item_attr_biblio_volume[$cnt_biblio];
                    }
                    if($this->item_attr_biblio_issue[$cnt_biblio]!=' ') {
                        $issue = $this->item_attr_biblio_issue[$cnt_biblio];
                    }
                    if($this->item_attr_biblio_spage[$cnt_biblio]!=' ') {
                        $spage = $this->item_attr_biblio_spage[$cnt_biblio];
                    }
                    if($this->item_attr_biblio_epage[$cnt_biblio]!=' ') {
                        $epage = $this->item_attr_biblio_epage[$cnt_biblio];
                    }
                    if($this->item_attr_biblio_dateofissued_year[$cnt_biblio]!=' ') {
                        $year = trim($this->item_attr_biblio_dateofissued_year[$cnt_biblio]);
                    }
                    if($this->item_attr_biblio_dateofissued_month[$cnt_biblio]!=' ') {
                        $month = $this->item_attr_biblio_dateofissued_month[$cnt_biblio];
                    }
                    if($this->item_attr_biblio_dateofissued_day[$cnt_biblio]!=' ') {
                        $day = $this->item_attr_biblio_dateofissued_day[$cnt_biblio];
                    }

                    // 発行年月日を連結する
                    if($year != '') {
                        $dateofissued = $year;
                        if($month != '') {
                            if (strlen($month) == 1) {
                                $dateofissued = $dateofissued.'-0'.$month;
                            } else {
                                $dateofissued = $dateofissued.'-'.$month;
                            }
                            if($day != '') {
                                if (strlen($day) == 1) {
                                    $dateofissued = $dateofissued.'-0'.$day;
                                } else {
                                    $dateofissued = $dateofissued.'-'.$day;
                                }
                            }
                        }
                    }
                    
                    // 書誌情報属性は1属性につき雑誌名、巻、号、ページ、発行年を保存
                    array_push($attr_elm, array(
                            'biblio_name' => $biblio_name,
                            'biblio_name_english' => $biblio_name_english,
                            'volume' => $volume,
                            'issue' => $issue,
                            'spage' => $spage,
                            'epage' => $epage,
                            'date_of_issued' => $dateofissued,
                            'year' => $year,
                            'month' => $month,
                            'day' => $day
                        )
                    );
                    $cnt_biblio++;
                    break;
                    
                case 'date':
                    $date_year = '';
                    $date_month = '';
                    $date_day = '';
                    $date = '';
                    
                    // 空白チェック
                    if($this->item_attr_date_year[$cnt_date]!=' ') {
                        $date_year = trim($this->item_attr_date_year[$cnt_date]);
                    }
                    if($this->item_attr_date_month[$cnt_date]!=' ') {
                        $date_month = $this->item_attr_date_month[$cnt_date];
                    }
                    if($this->item_attr_date_day[$cnt_date]!=' ') {
                        $date_day = $this->item_attr_date_day[$cnt_date];
                    }

                    // 年月日を連結する
                    if($date_year != '') {
                        $date = $date_year;
                        if($date_month != '') {
                            if (strlen($date_month) == 1) {
                                $date = $date.'-0'.$date_month;
                            } else {
                                $date = $date.'-'.$date_month;
                            }
                            if($date_day != '') {
                                if (strlen($date_day) == 1) {
                                    $date = $date.'-0'.$date_day;
                                } else {
                                    $date = $date.'-'.$date_day;
                                }
                            }
                        }
                    }

                    array_push($attr_elm, array(
                            'date' => $date,
                            'date_year' => $date_year,
                            'date_month' => $date_month,
                            'date_day' => $date_day
                        )
                    );
                    $cnt_date++;
                    break;
                // Add contents page Y.Nakao 2010/08/06 --start--
                case 'heading':
                    $heading = "";
                    $heading_en = "";
                    $heading_sub = "";
                    $heading_sub_en = "";
                    // check string empty
                    if($this->item_attr_heading!=' ') {
                        $heading = $this->item_attr_heading; 
                    }
                    if($this->item_attr_heading_en!=' ') {
                        $heading_en = $this->item_attr_heading_en;
                    }
                    if($this->item_attr_heading_sub!=' ') {
                        $heading_sub = $this->item_attr_heading_sub;
                    }
                    if($this->item_attr_heading_sub_en!=' ') {
                        $heading_sub_en = $this->item_attr_heading_sub_en;
                    }
                    array_push($attr_elm, $heading."|".$heading_en."|".$heading_sub."|".$heading_sub_en);
                    break;
                // Add contents page Y.Nakao 2010/08/06 --end--
                default :
                    // ファイルとサムネイルはセッション情報をそのままコピー
                    array_push($attr_elm, $item_attr_old[$ii][$jj]);
                    break;
                }
                
            }
            array_push($item_attr, $attr_elm);        // 1メタデータ分のユーザ入力値をセット
        }
        
        // set session
        $this->Session->setParameter("item_attr", $item_attr);
        
        return true;
        
    }
    
    /**
     * set get fill data to session data 
     */
    function setFillData(){
        ///////////////////////////////
        // get session data
        ///////////////////////////////
        $item_base = $this->Session->getParameter("base_attr");                    // item base data
        $item_keyword = $this->Session->getParameter("item_keyword");            // item keyword
        $item_keyword_english = $this->Session->getParameter("item_keyword_english");    // item keyword in English
        $item_attr_type = $this->Session->getParameter("item_attr_type");        // 2.アイテム属性タイプ (Nレコード, Order順) : ""[N][''], アイテム属性タイプの必要部分を連想配列で保持したものである。
        $item_num_attr = $this->Session->getParameter("item_num_attr");            // 5.アイテム属性数 (N): "item_num_attr"[N], N属性タイプごとの属性数-。複数可な属性タイプのみ>1の値をとる。
        $item_attr_old = $this->Session->getParameter("item_attr");                // 6.アイテム属性 (N) : "item_attr"[N][L], N属性タイプごとの属性。Lはアイテム属性数に対応。1～
        $item_attr = array();                                                    // 6.アイテム属性 。これはリクエストから全部
        
        // Fix language null Y.Nakao 2011/09/29 --start--
        if(strlen($this->language) == 0)
        {
            $this->language = $this->Session->getParameter("_lang");
            if($this->language == "japanese")
            {
                $this->language = "ja";
            }
            else
            {
                $this->language = "en";
            }
        }
        // Fix language null Y.Nakao 2011/09/29 --end--
        
        // counter
        $cnt_text = 0;        // "text"属性カウンタ
        $cnt_textarea = 0;    // "textarea"属性カウンタ
        $cnt_name = 0;        // "name"属性カウンタ
        $cnt_link = 0;        // "link"属性カウンタ
        $cnt_select = 0;    // "select"属性カウンタ
        $cnt_checkbox = 0;    // "checkbox"属性カウンタ
        $cnt_radio = 0;        // "radio"属性カウンタ
        $cnt_biblio = 0;    // "biblio_info"属性カウンタ 2008/08/11
        $cnt_date = 0;        // "date"属性カウンタ 2008/10/14
        
        // Add fill data more 2008/11/25 A.Suzuki --start--
        $biblio_info_flg = false;        // for biblio_info
        // Add fill data more 2008/11/25 A.Suzuki --end--

        //////////////////////////////////////////////////
        // set fill data to session
        //////////////////////////////////////////////////
        ///////////////////////
        // set base data
        ///////////////////////
        // TITLE
        $item_base["title"] = $this->title;
        $item_base["title_english"] = $this->title_english;

        // language
        if($this->language != ""){
            $item_base["language"] = $this->language;
        }
        $this->Session->setParameter("base_attr", $item_base);
        // keyword
        $word = "";
        for($ii=0; $ii<count($this->keyword); $ii++){
            if($ii != 0){
                $word .= "|";
            }
            $word .= $this->keyword[$ii];
        }
        $this->Session->setParameter("item_keyword", $word);
        // keyword_english
        $word = "";
        for($ii=0; $ii<count($this->keyword_english); $ii++){
            if($ii != 0){
                $word .= "|";
            }
            $word .= $this->keyword_english[$ii];
        }
        $this->Session->setParameter("item_keyword_english", $word);

        ///////////////////////
        // set item data
        ///////////////////////
        for($ii=0; $ii<count($item_attr_type); $ii++) {
            $attr_elm = array();
            // fill data
            // Fix filldata for NAID 2012/01/17 Y.Nakao --start--
            switch($item_attr_type[$ii]['input_type']) {
                case 'text':
                case 'textarea':
                    /////////////////////////////
                    // set data from junii2
                    /////////////////////////////
                    // Add JuNii2 ver3 2013/09/24 R.Matsuura --start--
                    $this->getMetadataOfInputTypeText($item_attr_type[$ii]['junii2_mapping'], $item_attr_type[$ii]['display_lang_type'], 
                                                      $item_attr_type[$ii]['plural_enable'], $item_attr_old[$ii], $item_num_attr[$ii], $attr_elm);
                    break;
                case 'link':
                    // if isVersionOf etc..?
                    for($jj=0; $jj<$item_num_attr[$ii]; $jj++) {
                        array_push($attr_elm, $item_attr_old[$ii][$jj]);
                    }
                    break;
                case 'name':
                    if($item_attr_type[$ii]['junii2_mapping'] == "creator" || $item_attr_type[$ii]['junii2_mapping'] == "")
                    {
                        if(count($this->creator) > 0 && ($item_attr_type[$ii]['display_lang_type'] == "japanese" || $item_attr_type[$ii]['display_lang_type'] == ""))
                        {
                            if($item_attr_type[$ii]['plural_enable'] == 1)
                            {
                                for($jj=0; $jj<count($this->creator); $jj++){
                                    if($this->creator[$jj]["family"]!=""){
                                        array_push($attr_elm, $this->creator[$jj]);
                                    }
                                }
                                $item_num_attr[$ii] = count($this->creator);
                                $this->creator = array();
                            }
                            else
                            {
                                array_push($attr_elm, array_shift($this->creator));
                                $item_num_attr[$ii] = 1;
                            }
                        }
                        else if(count($this->creator_english) > 0 && ($item_attr_type[$ii]['display_lang_type'] == "english" || $item_attr_type[$ii]['display_lang_type'] == ""))
                        {
                            if($item_attr_type[$ii]['plural_enable'] == 1)
                            {
                                for($jj=0; $jj<count($this->creator_english); $jj++){
                                    if($this->creator_english[$jj]["family"]!=""){
                                        array_push($attr_elm, $this->creator_english[$jj]);
                                    }
                                }
                                $item_num_attr[$ii] = count($this->creator_english);
                                $this->creator_english = array();
                            }
                            else
                            {
                                array_push($attr_elm, array_shift($this->creator_english));
                                $item_num_attr[$ii] = 1;
                            }
                        }
                        else
                        {
                            for($jj=0; $jj<$item_num_attr[$ii]; $jj++) {
                                array_push($attr_elm, $item_attr_old[$ii][$jj]);
                            }
                        }
                    }
                    else
                    {
                        // old data copy
                        for($jj=0; $jj<$item_num_attr[$ii]; $jj++) {
                            array_push($attr_elm, $item_attr_old[$ii][$jj]);
                        }
                    }
                    break;
                case 'biblio_info':
                    if($biblio_info_flg == false){
                        $biblio_info = array(  "biblio_name"=>'',
                                               "biblio_name_english"=>'',
                                               "volume"=>'',
                                               "issue"=>'',
                                               "spage"=>'',
                                               "epage"=>'',
                                               "date_of_issued"=>'',
                                               "year"=>'',
                                               "month"=>'',
                                               "day"=>'');
                        if($this->jtitle != ""){
                            $biblio_info["biblio_name"] = $this->jtitle;
                            $this->jtitle = '';
                        }
                        if($this->jtitle_english != ""){
                            $biblio_info["biblio_name_english"] = $this->jtitle_english;
                            $this->jtitle_english = '';
                        }
                        if($this->volume != ""){
                            $biblio_info["volume"] = $this->volume;
                            $this->volume = '';
                        }
                        if($this->issue != ""){
                            $biblio_info["issue"] = $this->issue;
                            $this->issue = '';
                        }
                        if($this->spage != ""){
                            $biblio_info["spage"] = $this->spage;
                            $this->spage = '';
                        }
                        if($this->epage != ""){
                            $biblio_info["epage"] = $this->epage;
                            $this->epage = '';
                        }
                        if($this->dateofissued != ""){
                            $biblio_info["date_of_issued"] = $this->dateofissued;
                            $date = explode("-", $this->dateofissued);
                            if(isset($date[0]) && $date[0]!=""){
                                $biblio_info["year"] = $date[0];
                            }
                            if(isset($date[1]) && $date[1]!=""){
                                $biblio_info["month"] = $date[1];
                            }
                            if(isset($date[2]) && $date[2]!=""){
                                $biblio_info["day"] = $date[2];
                            }
                            $this->dateofissued = '';
                        }
                        if(count($biblio_info)>0){
                            $item_num_attr[$ii] = 1;
                            array_push($attr_elm, $biblio_info);
                        } else {
                            for($jj=0; $jj<$item_num_attr[$ii]; $jj++) {
                                array_push($attr_elm, $item_attr_old[$ii][$jj]);
                            }
                        }
                        $biblio_info_flg = true;
                    }
                    else
                    {
                        // old data copy
                        for($jj=0; $jj<$item_num_attr[$ii]; $jj++) {
                            array_push($attr_elm, $item_attr_old[$ii][$jj]);
                        }
                    }
                    break;
                    
                case 'date':
                    if($item_attr_type[$ii]['junii2_mapping'] == "dateofissued") {
                        if($this->dateofissued != ""){
                            $date_info = array();
                            $date_info["date"] = $this->dateofissued;
                            $date = explode("-", $this->dateofissued);
                            $date_info = array('date_year'=>'', 'date_month'=>'', 'date_day'=>'');
                            if(isset($date[0]) && strlen($date[0]) > 0){
                                $date_info["date_year"] = $date[0];
                            }
                            if(isset($date[1]) && strlen($date[1]) > 0){
                                $date_info["date_month"] = $date[1];
                            }
                            if(isset($date[2]) && strlen($date[2]) > 0){
                                $date_info["date_day"] = $date[2];
                            }
                            $item_num_attr[$ii] = 1;
                            array_push($attr_elm, $date_info);
                            $this->dateofissued = '';
                        } else {
                            for($jj=0; $jj<$item_num_attr[$ii]; $jj++) {
                                array_push($attr_elm, $item_attr_old[$ii][$jj]);
                            }
                        }
                    } else {
                        for($jj=0; $jj<$item_num_attr[$ii]; $jj++) {
                            array_push($attr_elm, $item_attr_old[$ii][$jj]);
                        }
                    }
                    break;
                case 'select':
                case 'checkbox':
                case 'radio':
                    default :
                // file, thum, sel, check, radio copy old info
                for($jj=0; $jj<$item_num_attr[$ii]; $jj++) {
                    array_push($attr_elm, $item_attr_old[$ii][$jj]);
                }
                break;
            }
            array_push($item_attr, $attr_elm);        // 1メタデータ分のユーザ入力値をセット
        }
        // Fix filldata for NAID 2012/01/17 Y.Nakao --start--
        
        // set session
        $this->Session->setParameter("item_attr", $item_attr);
        $this->Session->setParameter("item_num_attr", $item_num_attr);
        
        return true;
        
    }
    // Add fill data more 2008/11/21 Y.Nakao --end--
    
    /**
     * init session data 
     */
    function initEditData(){
        // セッション情報取得
        $item_type_all = $this->Session->getParameter("item_type_all");            // 1.アイテムタイプ,  アイテムタイプのレコードをそのまま保存したものである。
        $item_attr_type = $this->Session->getParameter("item_attr_type");        // 2.アイテム属性タイプ (Nレコード, Order順) : ""[N][''], アイテム属性タイプの必要部分を連想配列で保持したものである。
        $item_num_cand = $this->Session->getParameter("item_num_cand");            // 3.アイテム属性選択肢数 (N) : "item_num_cand"[N], 選択肢のない属性タイプは0を設定
        $option_data = $this->Session->getParameter("option_data");                // 4.アイテム属性選択肢 (N): "option_data"[N][M], N属性タイプごとの選択肢。Mはアイテム属性選択肢数に対応。0～                
        // ユーザ入力値＆変数
        $item_num_attr_old = $this->Session->getParameter("item_num_attr");        // 5.アイテム属性数 (N): "item_num_attr"[N], N属性タイプごとの属性数-。複数可な属性タイプのみ>1の値をとる。
        $item_attr_old = $this->Session->getParameter("item_attr");                // 6.アイテム属性 (N) : "item_attr"[N][L], N属性タイプごとの属性。Lはアイテム属性数に対応。1～        
        $item_attr = array();                                                    // 6.アイテム属性 。これはリクエストから全部
        $item_num_attr = array();

        // アイテム基本属性をセッションから削除
        $this->Session->removeParameter("base_attr");        
        // アイテムキーワードをセッションから削除    
        $this->Session->removeParameter("item_keyword");
        $this->Session->removeParameter("item_keyword_english");

        // カウンタ
        $cnt_select = 0;
        $cnt_checkbox = 0;
        $cnt_radio = 0;
        
        // アイテム追加属性(メタデータ)をセッションから削除
        // ii-thメタデータのリクエストを保存
        for($ii=0; $ii<count($item_attr_type); $ii++) {
            // メタデータフラグ
            $text_flg = false;
            $textarea_flg = false;
            $name_flg = false;
            $link_flg = false;
            $biblio_flg = false;
            $date_flg = false;
            
            $attr_elm = array();        // 1メタデータの値列
            // ii-thメタデータのjj-th属性値のリクエストを保存
            for($jj=0; $jj<$item_num_attr_old[$ii]; $jj++) {
                // ii-thメタデータの入力形式ごとのリクエストを保存
                switch($item_attr_type[$ii]['input_type']) {
                case 'text':
                    if($text_flg == false) {
                        array_push($attr_elm, '');
                        $text_flg = true;
                        $item_num_attr[$ii] = 1;
                    }
                    break;
                case 'link':
                    if($link_flg == false){
                        array_push($attr_elm, '');
                        $link_flg = true;
                        $item_num_attr[$ii] = 1;
                    }
                    break;
                case 'name':
                    if($name_flg == false){
                        array_push($attr_elm, array(
                                'family' => '',
                                'given' => '',
                                'family_ruby' => '',
                                'given_ruby' => '',
                                'email' => '',
                                'author_id' => '',
                                'language' => '',
                                'external_author_id' => array(array('prefix_id'=>'', 'suffix'=>'', 'old_prefix_id'=>'', 'old_suffix'=>'', 'prefix_name'=>''))
                            )
                        );
                        $name_flg = true;
                        $item_num_attr[$ii] = 1;
                    }
                    break;
                case 'textarea':
                    if($textarea_flg == false) {
                        array_push($attr_elm, '');
                        $textarea_flg = true;
                        $item_num_attr[$ii] = 1;
                    }
                    break;
                case 'select':
                    array_push($attr_elm, $this->item_attr_select[$cnt_select]);
                    $cnt_select++;
                    $item_num_attr[$ii] = $item_num_attr_old[$ii];
                    break;
                case 'checkbox':
                    // 0 or 1(チェックボックスの数だけ)
                    for($kk=0; $kk<count($option_data[$ii]); $kk++){
                        array_push($attr_elm, $this->item_attr_checkbox[$cnt_checkbox]);    // チェックON
                        $cnt_checkbox++;
                    }
                    $item_num_attr[$ii] = $item_num_attr_old[$ii];
                    break;
                case 'radio':
                    // 選択番号。
                    array_push($attr_elm, $this->item_attr_radio[$cnt_radio]);
                    $cnt_radio++;
                    $item_num_attr[$ii] = $item_num_attr_old[$ii];
                    break;
                case 'biblio_info':
                    if($biblio_flg == false){
                        array_push($attr_elm, array(
                                'biblio_name' => '',
                                'biblio_name_english' => '',
                                'volume' => '',
                                'issue' => '',
                                'spage' => '',
                                'epage' => '',
                                'date_of_issued' => '',
                                'year' => '',
                                'month' => '',
                                'day' => ''
                            )
                        );
                        $biblio_flg = true;
                        $item_num_attr[$ii] = 1;
                    }
                    break;
                    
                case 'date':
                    if($date_flg == false){
                        array_push($attr_elm, array(
                                'date' => '',
                                'date_year' => '',
                                'date_month' => '',
                                'date_day' => ''
                            )
                        );
                        $date_flg = true;
                        $item_num_attr[$ii] = 1;
                    }
                    break;
                default :
                    // ファイルとサムネイルはセッション情報をそのままコピー
                    array_push($attr_elm, $item_attr_old[$ii][$jj]);
                    $item_num_attr[$ii] = $item_num_attr_old[$ii];
                    break;
                }
                
            }
            array_push($item_attr, $attr_elm);        // 1メタデータ分のユーザ入力値をセット
        }
        
        // set session
        $this->Session->setParameter("item_attr", $item_attr);
        $this->Session->setParameter("item_num_attr", $item_num_attr);
        
        return true;
    }

    // Add fill item_id 2010/02/16 S.Nonomura --start--
    /**
     *
     */
    function fillItem_id( $item_id ){
        $item_type_all_info = $this->Session->getParameter("item_type_all");
        $user_auth_id = $this->Session->getParameter("_user_auth_id");
        $auth_id = $this->getRoomAuthorityID();
        $user_id = $this->Session->getParameter("_user_id");
        $item_no = 1;

        $item_id = str_replace(" ", "", $item_id); // str replace space
        $item_id = str_replace("　", "", $item_id); // str replace 2byte spase

        if($item_id==null || $item_id==""){
            $error = $this->smartyAssign->getLang("repository_item_fill_data_type_itemid")
                    .$this->smartyAssign->getLang("repository_item_fill_data_no_metadata_error");
            array_push($this->error_msg, $error );
            return false;
        }

        // explode not number
        if(preg_match("/[^0-9]/", $item_id)){
            $error = $this->smartyAssign->getLang("repository_item_fill_data_type_itemid")
                    .$this->smartyAssign->getLang("repository_item_fill_data_no_metadata_error");
            array_push($this->error_msg, $error );
            return false;
        }
        
        // Add A.Jin 2012/11/29 --start--
        $chk_num = intval($item_id);
        if($chk_num <= 0){
            $error = $this->smartyAssign->getLang("repository_item_fill_data_type_itemid")
                    .$this->smartyAssign->getLang("repository_item_fill_data_no_metadata_error");
            array_push($this->error_msg, $error );
            return false;
        }
        // Add A.Jin 2012/11/29 --end--

        // Add check index public status 2010/01/04 Y.Nakao --start--
        // Add OpenDepo 2013/12/02 R.Matsuura --start--
        $this->setConfigAuthority();
        $indexAuthorityManager = new RepositoryIndexAuthorityManager($this->Session, $this->dbAccess, $this->TransStartDate);
        $publicIndexQuery = $indexAuthorityManager->getPublicIndexQuery(false, $this->repository_admin_base, $this->repository_admin_room);
        // Add OpenDepo 2013/12/02 R.Matsuura --end--

        $query = null;
        // キーワード検索結果から並び変えに必要な情報を格納
        // if ($user_auth_id >= _AUTH_MODERATE) {
        if($user_auth_id >= $this->repository_admin_base && $auth_id >= $this->repository_admin_room){
        $query = "SELECT item_id ".
             "FROM ".DATABASE_PREFIX."repository_item ".
             "WHERE is_delete = 0 ".
             "AND item_id = ".$item_id.";";
        } else if ($user_auth_id >= _AUTH_GENERAL) {
        $query = "SELECT DISTINCT item.item_id from (".
             "SELECT item.item_id ".
             "FROM ".DATABASE_PREFIX."repository_item AS item, ".
                 DATABASE_PREFIX."repository_position_index AS pos ".
             "WHERE item.item_id = pos.item_id ".
             "AND item.item_no = pos.item_no ".
             "AND item.is_delete = 0 ".
             "AND pos.is_delete = 0 ".
             "AND (item.ins_user_id = '".$user_id."' OR pos.index_id IN (".$publicIndexQuery.") ) ".
             "AND (item.shown_date <= '".$this->TransStartDate."' OR (item.shown_date > '".$this->TransStartDate."' AND item.ins_user_id = '".$user_id."')) ".
             "AND (item.shown_status = 1 OR (item.shown_status = 0 AND item.ins_user_id =  '".$user_id."'))) as item ".
             "WHERE item.item_id = ".$item_id.";";
        } else {
        return true;
        }
        
        $result = $this->Db->execute($query);
        if($result === false){
            $error = $this->smartyAssign->getLang("repository_item_fill_data_type_itemid")
                    .$this->smartyAssign->getLang("repository_item_fill_data_no_metadata_error");
            array_push($this->error_msg, $error );
            return false;
        }

        if(count($result) == 0){
            $error = $this->smartyAssign->getLang("repository_item_fill_data_type_itemid")
                    .$this->smartyAssign->getLang("repository_item_fill_data_no_metadata_error");
            array_push($this->error_msg, $error );
            return false;
        }
        
        //get of metadata from DB
        $metadata_db_error_msg = "";
        $result = $this->getItemData($item_id, $item_no, $this->Result_List, $metadata_db_error_msg);
        if($result === false) {
            array_push($this->error_msg, $metadata_db_error_msg );
            $this->Session->setParameter("error_msg", $this->error_msg);
            //end action process
            $result = $this->exitAction();     //if transaction success, do commit
            if ( $result === false) {
                // Message and logID are specific and create Exception
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );    
                throw $exception;
            }
        }
        if($this->Result_List['item'][0]['item_type_id'] == $item_type_all_info['item_type_id']) {
            $this->fill_type = 1;
        } else {
            $this->fill_type = 2;
        }
        
        return true;
    }
    // Add fill item_id 2010/02/16 S.Nonomura --end--

    // Add ichushi fill 2012/11/21 A.jin --start--
    /**
     * 医中誌書誌メタデータフィル機能
     *
     * @param string $ichushi_id
     */
    private function fillIchushi( $ichushi_id )
    {
        //初期化
        $login_id = "";
        $login_passwd = "";
        $cookie = false;
        
        //1. 医中誌連携有無チェック
        /////////////////////////////
        // check ichushi_is_connect
        /////////////////////////////
        $query = "SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter ".
                 "WHERE param_name = ?; ";
        $param = array();
        $param[] = "ichushi_is_connect";
        $result = $this->Db->execute($query, $param);
        if($result === false){
            $error = $this->smartyAssign->getLang("repository_item_fill_data_type_ichushi")
                    .$this->smartyAssign->getLang("repository_item_fill_data_no_metadata_error");
            array_push($this->error_msg, $error );
            return false;
        }
        if(count($result)!=1 || $result[0]["param_value"]==""){
            array_push($this->error_msg, $this->smartyAssign->getLang("repository_item_fill_data_no_key_id"));
            return false;
        }
        if($result[0]["param_value"] == 0)
        {
            $error = $this->smartyAssign->getLang("repository_item_fill_data_type_ichushi")
                    .$this->smartyAssign->getLang("repository_item_fill_data_no_metadata_error");
            array_push($this->error_msg, $error);
            //医中誌連携なし
            return false;
        }
        
        //2. ログイン情報参照
        $result = $this->getLoginInfoIchushi($login_id, $login_passwd);
        if($result === false)
        {
            $error = $this->smartyAssign->getLang("repository_item_fill_data_type_ichushi")
                    .$this->smartyAssign->getLang("repository_item_fill_data_no_metadata_error");
            array_push($this->error_msg, $error);
           return false;
        }
        
        //3. ログイン
        $result = $this->loginIchushi($login_id, $login_passwd, $cookie);
        //テストコード
        //$result= false;
        if($result === false)
        {
            //エラーメッセージ「医中誌にアクセスできません。管理者にお問い合わせください。」
            $error = $this->smartyAssign->getLang("repository_item_fill_data_type_ichushi")
                    .$this->smartyAssign->getLang("repository_item_fill_data_access_error")
                    .$this->smartyAssign->getLang("repository_item_fill_data_callback_admin");
            array_push($this->error_msg, $error);
            return false;
        }
        
        //4. SRUリクエストを生成する。
        $sru_request = "";
        //$url = "http://ts10.jamas.or.jp/";    //テスト環境
        $url = "http://search.jamas.or.jp/";    //本番環境
        $sru_request .= $url;
        $sru_request .= "api/sru?operation=searchRetrieve&version=1.2";
        $sru_request .= "&query=";
        $temp_str = "dc.identifier=".$ichushi_id;
        $encord_str = mb_convert_encoding($temp_str, 'UTF-8', 'auto');
        $encord_str = urlencode($encord_str);
        $sru_request .= $encord_str;
        $sru_request .= "&startRecord=1&recordPacking=xml&recordSchema=pam";
        
        //5. SRUリクエストを送信する。
        /////////////////////////////
        // HTTP_Request init
        /////////////////////////////
        // send http request
        $option = array( 
            "timeout" => "10",
            "allowRedirects" => true, 
            "maxRedirects" => 3, 
        );
        if($this->proxy['proxy_mode'] == 1)
        {
            $option = array( 
                    "timeout" => "10",
                    "allowRedirects" => true, 
                    "maxRedirects" => 3,
                    "proxy_host"=>$this->proxy['proxy_host'],
                    "proxy_port"=>$this->proxy['proxy_port'],
                    "proxy_user"=>$this->proxy['proxy_user'],
                    "proxy_pass"=>$this->proxy['proxy_pass']
                );
        }
        $http = new HTTP_Request($sru_request, $option);
        // setting HTTP header
        $http->addHeader("User-Agent", $_SERVER['HTTP_USER_AGENT']); 
        $http->addHeader("Referer", $_SERVER['HTTP_REFERER']);
        $http->addCookie($cookie[0]['name'],$cookie[0]['value']);
        
        /////////////////////////////
        // run HTTP request 
        /////////////////////////////
        $response = $http->sendRequest(); 
        
        //6. 医中誌からログアウトする。
        $this->logoutIchushi($cookie);
        
        if (!PEAR::isError($response)) { 
            $charge_code = $http->getResponseCode();// ResponseCode(200等)を取得 
            $charge_header = $http->getResponseHeader();// ResponseHeader(レスポンスヘッダ)を取得 
            $charge_body = $http->getResponseBody();// ResponseBody(レスポンステキスト)を取得 
            $charge_Cookies = $http->getResponseCookies();// クッキーを取得 
        }

        //7. SRUレスポンスを受信する。
        // get response
        $response_xml = $charge_body;
        
        
        //TODO:2012/12/4 削除すること
        //echo htmlspecialchars($response_xml);
        
        /////////////////////////////
        // Error judge
        /////////////////////////////
        $isSuccess = $this->isSuccessResponseXml($response_xml);
        if($isSuccess === false){
            $error = $this->smartyAssign->getLang("repository_item_fill_data_type_ichushi")
                    .$this->smartyAssign->getLang("repository_item_fill_data_no_metadata_error");
            array_push($this->error_msg, $error);
            return false;
        }
        /////////////////////////////
        // parse response XML
        /////////////////////////////
        try{
            $xml_parser = xml_parser_create();
            $rtn = xml_parse_into_struct( $xml_parser, $response_xml, $vals );
            if($rtn == 0){
                $error = $this->smartyAssign->getLang("repository_item_fill_data_type_ichushi")
                        .$this->smartyAssign->getLang("repository_item_fill_data_no_metadata_error");
                array_push($this->error_msg, $error);
                return false;
            }
            xml_parser_free($xml_parser);
        } catch(Exception $ex){
            $error = $this->smartyAssign->getLang("repository_item_fill_data_type_ichushi")
                    .$this->smartyAssign->getLang("repository_item_fill_data_no_metadata_error");
            array_push($this->error_msg, $error);
            return false;
        }
        
        //8. メタデータにSRUレスポンスから取得した各データを格納する。
        $this->setIchushiData($vals);
        
        return ture;
    }
    
    /**
     * 医中誌レスポンスのエラーチェック
     *
     * @param unknown_type $response_xml
     * @return unknown
     */
    private function isSuccessResponseXml( $response_xml )
    {
        try{
            $xml_parser = xml_parser_create();
            $rtn = xml_parse_into_struct( $xml_parser, $response_xml, $vals );
            if($rtn == 0){
                return false;
            }
            xml_parser_free($xml_parser);
        } catch(Exception $ex){
            return false;
        }
        
        $message = "";
        foreach ($vals as $val){
            if($val['tag'] == "MESSAGE"){
                $message = $val['value'];
                break;
            }
        }
        //SRUで使用されているエラーメッセージ
        switch($message){
            case "System temporarily unavailable":
            case "Authentication error":
            case "Unsupported operation":
            case "Unsupported version":
            case "Unsupported parameter value":
            case "Mandatory parameter not supplied":
            case "Unsupported Parameter":
            case "Query syntax error":
            case "Record does not exist":
                return false;
                break;
            default:
                break;
        }
        
        return true;
    }
    
    /**
     * 医中誌書誌メタデータフィルの実処理
     * 
     *
     * @param unknown_type $vals
     */
    private function setIchushiData($vals) {
        $creator = array();
        $title = "";
        $creator = array();
        $description = array();
        $jtitle = "";
        $keyword = array();
        foreach($vals as $val){
            switch($val['tag']){
                //論文標題
                case "DC:TITLE":
                    if($val['value'] != null && $val['value'] != ""){
                        if($this->title == "" || $this->title_english == ""){
                            $title = $val['value'];
                        } else {
                            array_push($this->alternative, $val['value']);
                        }
                    }
                    break;
                //著者名
                case "DC:CREATOR":
                    $split_array = explode("|",$val['value'], 2);
                    for($ii=0; $ii<count($split_array);$ii++){
                        $split_array[$ii] = str_replace(', '," ",$split_array[$ii]);
                        $split_array[$ii] = str_replace(','," ",$split_array[$ii]);
                        $split_array[$ii] = str_replace('/'," ",$split_array[$ii]);
                        
                        // '|'で分割
                        $author = array("family"=>"","given"=>"","family_ruby"=>"","given_ruby"=>"","email"=>"","author_id"=>"","language"=>"","external_author_id"=>array(array("prefix_id"=>"", "suffix"=>"", "old_prefix_id"=>"", "old_suffix"=>"", "prefix_name"=>"")));
                        $author_array = explode(" ", $split_array[$ii]);
                        if(isset($author_array[0])){
                            $author["family"] = array_shift($author_array);
                        }
                        if(count($author_array) > 0){
                            $author["given"] = implode(" ", $author_array);
                        }
                        
                       array_push($creator, $author);
                    }
                    
                    break;
                //論文言語
                case "DC:LANGUAGE":
                    if($val['value'] == "日本語"){
                        $this->language = "ja";
                    } else {
                        $this->language = "en";
                    }
                    break;
                //抄録
                case "DC:DESCRIPTION":
                    array_push($description, $val['value']);
                    break;
                // Add JuNii2 ver3 2013/09/24 R.Matsuura --start--
                //ID
                case "DC:IDENTIFIER":
                    $this->ichushi_id = $val['value'];
                    break;
                // Add JuNii2 ver3 2013/09/24 R.Matsuura --end--
                //収載誌名
                case "PRISM:PUBLICATIONNAME":
                    $jtitle = $val['value'];
                    break;
                //P-ISSN
                case "PRISM:ISSN":
                    if($val['value'] != null && $val['value'] != ""){
                        $this->issn = $val['value'];
                    }
                    break;
                //E-ISSN
                case "PRISM:EISSN":
                    if($val['value'] != null && $val['value'] != ""){
                        $this->e_issn = $val['value'];
                    }
                    break;
                //巻
                case "PRISM:VOLUME":
                    if($val['value'] != null && $val['value'] != ""){
                        $this->volume = ltrim($val['value'], "0");
                    }
                    break;
                //号
                case "PRISM:NUMBER":
                    if($val['value'] != null && $val['value'] != ""){
                        $this->issue = ltrim($val['value'], "0");
                    }
                    break;
                //開始ページ
                case "PRISM:STARTINGPAGE":
                    if($this->spage == ""){
                        $this->spage = $val['value'];
                    }
                    break;
                //終了ページ
                case "PRISM:ENDINGPAGE":
                    if($this->epage == ""){
                        $this->epage = $val['value'];
                    }
                    break;
                //ページ範囲
                case "PRISM:PAGERANGE":
                    if($this->epage == ""){
                        $this->epage = $val['value'];
                        $tmp = explode("-",$val['value']);
                        if(count($tmp) > 1){
                            $this->spage = $tmp[0];
                            $this->epage = $tmp[1];
                        }
                    }
                    break;
                //発行年月日
                case "PRISM:PUBLICATIONDATE":
                    if($this->dateofissued == ""){
                        if($val['value'] != null && $val['value'] != ""){
                            // '.'を'-'に置換する
                            $this->dateofissued = str_replace(".","-",$val['value']);
                        }
                    }
                    break;
                //キーワード
                case "PRISM:KEYWORD":
                    //array_push($this->keyword, $val['value']);
                    array_push($keyword, $val['value']);
                    break;
                    
                default:
                    break;
            }
        }
        
        if($this->language == "ja"){
            
            $this->title = $title;
            $this->orderCreator($creator, "ja");
            $this->creator = $creator;
            $this->description = $description;
            $this->jtitle = $jtitle;
            $this->keyword = $keyword;
        }else{
            $this->title_english = $title;
            $this->orderCreator($creator,"en");
            $this->creator_english = $creator;
            $this->description_english = $description;
            $this->jtitle_english = $jtitle;
            $this->keyword_english = $keyword;
        }
    }
    
    /**
     * 著者名入れ替え処理
     * 
     * @param array 著者名リスト
     * @param string 言語
     *
     */
    private function orderCreator(&$creator,$language=""){
        foreach($creator as $key => $value){
            //分割可能か判定
            $is_split = true;
            // 姓名どちらか一方しか値が入っていないか。
            if( (($value['family'] != "") && ($value['given'] == ""))
                || (($value['given'] != "") && ($value['family'] == ""))){
                //分割不可
                $is_split = false;
            }
            
            //姓名取得
            $family = $value['family'];
            $given = $value['given'];
            
            //分解可能
            if($is_split === true){
                //日本語以外の場合
                if($language != "ja"){
                    $creator[$key]['family'] = $given;
                    $creator[$key]['given'] = $family;
                }
            }
            //分解不可能
            if($is_split === false) {
                //全て姓にフィル
                $creator[$key]['family'] = $family.$given;
                $creator[$key]['given'] = "";
            }
        }
    }
    
    // Add ichushi fill 2012/11/21 A.jin --end--
    
    // ItemIDFillDataCopy(ResultList[item_type] == session[item_type]) 2010/02/16 S.Nonomura --start--
    // Reference Source => action/main/item/editfiles.class.php
    function setItemIDSameData($Result_List) {
        $NameAuthority = new NameAuthority($this->Session, $this->Db);
        $item_attr_old = $this->Session->getParameter("item_attr");
        // Base Information
        $item_element = $Result_List['item_attr_type'];
        $counter = count($Result_List['item_attr_type']);

        // create Dummy array
        $item_num_cand = array();    
        $option_data = array();        

        $show_no = 1;

        for ($ii = 0; $ii < $counter; $ii++) {
            for ($ii2 = 0; $ii2 < $counter; $ii2++) {
                if ($item_element[$ii2]["show_order"] == $show_no) {
                    $num_cand = 0;        // number of choice
                    $options = array();    // choice
                    // if check and radio, get choice and number of choice
                    if($item_element[$ii2]["input_type"] == "select" ||
                        $item_element[$ii2]["input_type"] == "checkbox" ||
                        $item_element[$ii2]["input_type"] == "radio") {
                        // create SQL
                        $params_cand = "select candidate_value from ".DATABASE_PREFIX."repository_item_attr_candidate ".
                                       "WHERE item_type_id = ? and attribute_id = ? and is_delete = ?;";
                        $params = array();
                        $params[] = $Result_List["item"][0]["item_type_id"];    // item_type_id
                        $params[] = $item_element[$ii2]["attribute_id"];    // attribute_id
                        $params[] = 0;    // is_delete
            
                        // get of choice from DB
                        $option = $this->Db->execute($params_cand, $params);
                        if ($option === false) {
                            return "error";
                        }
                        $num_cand = count($option);
                        for($ii3=0; $ii3<$num_cand ; $ii3++) {
                            array_push($options, $option[$ii3]["candidate_value"]);
                        }
                    }
                    array_push( $option_data, $options);
                    $show_no++;
                    break;
                }
            }
        }

        // Initialization(Number of ItemAttributes／ItemAttributes)
        $item_num_attr = array_fill(0, $counter, 1);        // Number of ItemAttributes [N]
        $item_attr = array_fill(0, $counter, array(""));    // ItemAttributes [N][L]

        $base_attr = array(
                       'title' => $Result_List['item'][0]['title'],
                       'title_english' => $Result_List['item'][0]['title_english'],
                       'language' => $Result_List['item'][0]['language']
                      );

        // Item Keyword
        $item_keyword = $Result_List['item'][0]['serch_key'];
        $item_keyword_english = $Result_List['item'][0]['serch_key_english'];

        // Meta Data
        for ($ii = 0; $ii < $counter; $ii++) {
        // Unregistered Metadata attributes is OK by default
            if(count($Result_List['item_attr'][$ii]) <= 0) {
                // Fix initialize for name Y.Nakao 2013.12.17 --start--
                if( $item_element[$ii]['input_type'] == "name" )
                {
                    $edit_attr = array();
                    $external_author_id = $NameAuthority->getExternalAuthorIdPrefixAndSuffix('');
                    if ($external_author_id === false) {
                        return "error";
                    }
                    $name_init = array(
                                       'family' => '',
                                       'given' => '',
                                       'family_ruby' => '',
                                       'given_ruby' => '',
                                       'email' => '',
                                       'author_id' => '',
                                       'language' => '',
                                       'external_author_id' => $external_author_id
                                       );
                    array_push($edit_attr, $name_init);
                    $item_attr[$ii] = $edit_attr;
                    $item_num_attr[$ii] = count($edit_attr);
                }
                // Fix initialize for name Y.Nakao 2013.12.17 --end--
                continue;
            }

            $edit_attr = array();

            // checkbox : Reference => $Result_List['item_attr'][m][n]['attribute_value']
            // Create an array of choices and the same number
            // insert in the position as an integer.
            if( $item_element[$ii]['input_type'] == "checkbox" ) {
                $option_cnt = count($option_data[$ii]);
                for ($kk = 0; $kk < $option_cnt; $kk++) {
                    $isGot = false;
                    for ($jj = 0; $jj < count($Result_List['item_attr'][$ii]); $jj++) {
                        if($option_data[$ii][$kk] == $Result_List['item_attr'][$ii][$jj]['attribute_value']) {
                            array_push($edit_attr, 1);        // Selected
                            $isGot = true;
                            break;
                        }
                    }
                    if(!$isGot) {
                        array_push($edit_attr, 0);        // Unselected
                    }
                }
                $item_attr[$ii] = $edit_attr;
                continue;
            }

            for ($jj = 0; $jj < count($Result_List['item_attr'][$ii]); $jj++) {
                // radio button
                if( $item_element[$ii]['input_type'] == 'radio' ) {
                    // set the value matched index option
                    for ($kk = 0; $kk < count($option_data[$ii]); $kk++) {
                        if($option_data[$ii][$kk] == $Result_List['item_attr'][$ii][$jj]['attribute_value']) {
                            array_push($edit_attr, $kk);        // 0-th candidate is checked.
                            break;
                        }
                    }
                }
            
                // name
                elseif( $item_element[$ii]['input_type'] == "name" ) {
                    $external_author_id = $NameAuthority->getExternalAuthorIdPrefixAndSuffix($Result_List['item_attr'][$ii][$jj]['author_id']);
                    if ($external_author_id === false) {
                        return "error";
                    }
                    $name_init = array(
                                       'family' => $Result_List['item_attr'][$ii][$jj]['family'],
                                       'given' => $Result_List['item_attr'][$ii][$jj]['name'],
                                       'family_ruby' => $Result_List['item_attr'][$ii][$jj]['family_ruby'],
                                       'given_ruby' => $Result_List['item_attr'][$ii][$jj]['family_ruby'],
                                       'email' => $Result_List['item_attr'][$ii][$jj]['e_mail_address'],
                                       'author_id' => $Result_List['item_attr'][$ii][$jj]['author_id'],
                                       'language' => $Result_List['item_attr_type'][$ii]['display_lang_type'],
                                       'external_author_id' => $external_author_id
                                       );
                    array_push($edit_attr, $name_init);
                }
        
                // biblio_info
                elseif( $item_element[$ii]['input_type'] == "biblio_info" ) {
                    $date = explode("-", $Result_List['item_attr'][$ii][$jj]['date_of_issued']);
                    if(count($date) == 2){
                        array_push($date, "");
                    } else if(count($date) == 1){
                        array_push($date, "");
                        array_push($date, "");
                    }

                    if(strlen($date[1]) == 2){
                        $temp_month = str_split($date[1]);
                        if($temp_month[0] == "0"){
                            $date[1] = $temp_month[1];
                        }
                    }
                    if(strlen($date[2]) == 2){
                        $temp_day = str_split($date[2]);
                        if($temp_day[0] == "0"){
                            $date[2] = $temp_day[1];
                        }
                    }

                    $biblio_init = array(
                                         'biblio_name' => $Result_List['item_attr'][$ii][$jj]['biblio_name'],
                                         'biblio_name_english' => $Result_List['item_attr'][$ii][$jj]['biblio_name_english'],
                                         'volume' => $Result_List['item_attr'][$ii][$jj]['volume'],
                                         'issue' => $Result_List['item_attr'][$ii][$jj]['issue'],
                                         'spage' => $Result_List['item_attr'][$ii][$jj]['start_page'],
                                         'epage' => $Result_List['item_attr'][$ii][$jj]['end_page'],
                                         'date_of_issued' => $Result_List['item_attr'][$ii][$jj]['date_of_issued'],
                                         'year' =>$date[0],
                                         'month' =>$date[1],
                                         'day' =>$date[2]
                                         );
                    array_push($edit_attr, $biblio_init);
                }

                // date
                elseif( $item_element[$ii]['input_type'] == "date" ) {
                    $date = explode("-", $Result_List['item_attr'][$ii][$jj]['attribute_value']);
                    if(count($date) == 2){
                        array_push($date, "");
                    } else if(count($date) == 1){
                        array_push($date, "");
                        array_push($date, "");
                    }

                    if(strlen($date[1]) == 2){
                        $temp_month = str_split($date[1]);
                        if($temp_month[0] == "0"){
                            $date[1] = $temp_month[1];
                        }
                    }

                    if(strlen($date[2]) == 2){
                        $temp_day = str_split($date[2]);
                        if($temp_day[0] == "0"){
                            $date[2] = $temp_day[1];
                        }
                    }
                    $date_init = array(
                                       'date' => $Result_List['item_attr'][$ii][$jj]['attribute_value'],
                                       'date_year' =>$date[0],
                                       'date_month' =>$date[1],
                                       'date_day' =>$date[2],
                                      );
                    array_push($edit_attr, $date_init);
                }

                // other item information
                elseif( $item_element[$ii]['input_type'] === "thumbnail" || $item_element[$ii]['input_type'] === "file" ||
                    $item_element[$ii]['input_type'] === "file_price" || $item_element[$ii]['input_type'] === "supple") {
                    $edit_attr[] = $item_attr_old[$ii][$jj];
                }else{
                    array_push($edit_attr, $Result_List['item_attr'][$ii][$jj]['attribute_value']);
                }

                // Re-set the initial attributes
                $item_attr[$ii] = $edit_attr;
                $item_num_attr[$ii] = count($edit_attr);
            }
        }
            // session is hold the item information
            $this->Session->setParameter("item_num_attr",$item_num_attr);
            $this->Session->setParameter("item_attr",$item_attr);
            $this->Session->setParameter("base_attr",$base_attr);
            $this->Session->setParameter("item_keyword",$item_keyword);
            $this->Session->setParameter("item_keyword_english",$item_keyword_english);
    }
    // ItemFillDataCopy 2010/02/16 S.Nonomura --end--

    // ItemIDFillDataCopy(ResultList[item_type] != session[item_type]) 2010/02/16 S.Nonomura --start--
    function setItemIDJunii2Data($Result_List) {
        // Acquisition of Session Information
        $item_type_all = $this->Session->getParameter('item_type_all');
        $item_attr_type = $this->Session->getParameter('item_attr_type');
        $item_attr = $this->Session->getParameter('item_attr');
        $item_num_cand = $this->Session->getParameter('item_num_cand');    
        $option_data = $this->Session->getParameter('option_data');
        // Acquisition of Session Information --end--
        
        $NameAuthority = new NameAuthority($this->Session, $this->Db);
        
        // Initialization
        $counter = count($item_attr_type);             // Number of elements
        $new_item_num_attr = array_fill(0, $counter, 1);       // Number of ItemAttributes
        $new_item_attr = array_fill(0, $counter, array(''));   // ItemAttributes        
        $item_old = $Result_List['item'][0];           // Reference Item Information
        $item_attr_type_old = $Result_List['item_attr_type'];  // Reference ItemType Information
        $item_attr_old = $Result_List['item_attr'];        // Reference ItemAttributes Informaiton
        $counter_old = count($item_attr_type_old);         // Number of elements

        // Reference Item Title & Titl_english & language
        //          ↓replaced
        // New Item Title & Titl_english & language
        $base_attr = array(
                   'title' => $item_old['title'],
                   'title_english' => $item_old['title_english'],
                   'language' => $item_old['language']
                  );

        // Reference Item Keyword
        //      ↓replaced
        // New Item Keyword
        $item_keyword = $item_old['serch_key'];
        $item_keyword_english = $item_old['serch_key_english'];
        
        //////////////////////////////////////////////////////////////////////////
        //　　　　　         Common terms                 //
        // unset(～～) ⇒　remove elements                    //
        // $new_item_attr[$ii][n] = ～～～  ⇒  the extracted elements insert     //
        // $new_item_num_attr[$ii] += 1;  ⇒  update (Number of ItemAttributes)  //
        // $break_sign = 1;  ⇒  update (break_sign)                 //
        // if($plural_cnt != 0)  ⇒  If The Number of elements is nonzero    //
        // if($plural_enable == 0)  ⇒  if Number of elements is Singular    //
        // $plural_enable == 1)  ⇒  if Number of elements is multiple       //
        // old_input_type  ⇒  $item_attr_type_old[$ii]['input_type']        //
        // new_input_type  ⇒  $item_attr_type[$ii]['input_type']        //
        //////////////////////////////////////////////////////////////////////////

        // Reference Item matadata
        //      ↓replaced
        // New Item matadata
        for($ii=0; $ii<$counter; $ii++) {
            // Initialization
            $plural_cnt = 0;  // Number of elements to insert
            $break_sign = 0;  // break point sign

            // Reference Other items information
            //      ↓replaced
            // New Other items information
            if($item_attr_type[$ii]['input_type'] === "supple"
               || $item_attr_type[$ii]['input_type'] === "thumbnail" 
               || $item_attr_type[$ii]['input_type'] === "file"
               || $item_attr_type[$ii]['input_type'] === "file_price"){
                $cnt = count($item_attr[$ii]);
                for($kk=0; $kk<$cnt; $kk++){
                    $new_item_attr[$ii][$kk] = $item_attr[$ii][$kk];
                    if($kk >= 1) {
                        $new_item_num_attr[$ii] += 1;
                    }
                }
                continue;
            }

            //create Dummy array
            $edit_biblio_info = array(
                                      'biblio_name' => '',
                                      'biblio_name_english' => '',
                                      'volume' => '',
                                      'issue' => '',
                                      'spage' => '',
                                      'epage' => '',
                                      'date_of_issued' => '',
                                      'year' => '',
                                      'month' => '',
                                      'day' => ''
                                      );

            for($jj=0; $jj<$counter_old; $jj++) {
                // biblio_info data check
                $spell_check = "";
                if(($item_attr_type[$ii]["junii2_mapping"] === "jtitle"
                   || $item_attr_type[$ii]["junii2_mapping"] === "volume" 
                   || $item_attr_type[$ii]["junii2_mapping"] === "issue" 
                   || $item_attr_type[$ii]["junii2_mapping"] === "spage" 
                   || $item_attr_type[$ii]["junii2_mapping"] === "epage" 
                   || $item_attr_type[$ii]["junii2_mapping"] === "dateofissued")
                   && $item_attr_type_old[$jj]["junii2_mapping"] === "jtitle,volume,issue,spage,epage,dateofissued")
                  {
                   $spell_check = $item_attr_type[$ii]["junii2_mapping"];
                }

                if(($item_attr_type_old[$jj]["junii2_mapping"] === "jtitle" 
                   || $item_attr_type_old[$jj]["junii2_mapping"] === "volume" 
                   || $item_attr_type_old[$jj]["junii2_mapping"] === "issue" 
                   || $item_attr_type_old[$jj]["junii2_mapping"] === "spage" 
                   || $item_attr_type_old[$jj]["junii2_mapping"] === "epage" 
                   || $item_attr_type_old[$jj]["junii2_mapping"] === "dateofissued")
                   && $item_attr_type[$ii]["junii2_mapping"] === "jtitle,volume,issue,spage,epage,dateofissued")
                  {
                   $spell_check = $item_attr_type_old[$jj]["junii2_mapping"];
                }
                
                // Compare junii2_mapping
                if(($item_attr_type[$ii]["junii2_mapping"] === $item_attr_type_old[$jj]["junii2_mapping"] || $spell_check !== "")
                    && $item_attr_type[$ii]["junii2_mapping"] !== "" && $item_attr_type_old[$jj]["junii2_mapping"] !== "") {
                    // Number of elements is Singular or multiple
                    $plural_enable = $item_attr_type[$ii]["plural_enable"];
                    if($plural_cnt == 0) {
                        $plural_cnt = count($item_attr_old[$jj]);
                    }

                    switch($item_attr_type_old[$jj]["input_type"]) {
                        // if old_input_type === "textarea" or "link" or "text"
                        case "textarea":
                        case "link":
                        case "text":
                            if($plural_cnt != 0){
                                // if old_input_type === "textarea"
                                if($item_attr_type[$ii]["input_type"] !== "textarea") {
                                    // Remove a KAIGYOU of the array and replace it with HANKAKU_SPACE
                                    for($ll=0; $ll<$plural_cnt; $ll++) {
                                        $item_attr_old[$jj][$ll]["attribute_value"] = str_replace("\n"," ",$item_attr_old[$jj][$ll]["attribute_value"]);
                                    }
                                }
                                // if old_input_type === "textarea" or "link" or "text"
                                // if new_input_type === "textarea" or "link" or "text"
                                if($item_attr_type[$ii]["input_type"] === "text"
                                   || $item_attr_type[$ii]["input_type"] === "textarea"
                                   || $item_attr_type[$ii]["input_type"] === "link"){
                                    if($plural_enable == 0 && $plural_cnt != 0) {
                                        $new_item_attr[$ii][0] = $item_attr_old[$jj][0]["attribute_value"];
                                        unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                    }elseif($plural_enable == 1 && $plural_cnt != 0) {
                                        for($ll=0; $ll<$plural_cnt; $ll++) {
                                            $new_item_attr[$ii][$ll] = $item_attr_old[$jj][$ll]["attribute_value"];
                                            if($ll >= 1) {
                                                $new_item_num_attr[$ii] += 1;
                                            }
                                        }
                                        unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                        $break_sign = 1;
                                    }
                                // if old_input_type === "textarea" or "link" or "text"
                                // if new_input_type === "name"
                                }elseif($item_attr_type[$ii]["input_type"] === "name"){
                                    if($plural_enable == 0) {
                                        $new_item_attr[$ii][0] = array(
                                                                       "family" => $item_attr_old[$jj][0]["attribute_value"],
                                                                       "given" => "",
                                                                       "family_ruby" => "",
                                                                       "given_ruby" => "",
                                                                       "email" => "",
                                                                       "author_id" => "",
                                                                       "language" => "",
                                                                       "external_author_id" => array(array('prefix_id'=>'', 'suffix'=>'', 'old_prefix_id'=>'', 'old_suffix'=>'', 'prefix_name'=>''))
                                                                       );
                                        unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                        $break_sign = 1;
                                    } elseif($plural_enable == 1) {
                                        for($ll=0; $ll<$plural_cnt; $ll++) {
                                            $new_item_attr[$ii][$ll] = array(
                                                                             "family" => $item_attr_old[$jj][$ll]["attribute_value"],
                                                                             "given" => "",
                                                                             "family_ruby" => "",
                                                                             "given_ruby" => "",
                                                                             "email" => "",
                                                                             "author_id" => "",
                                                                             "language" => "",
                                                                             "external_author_id" => array(array('prefix_id'=>'', 'suffix'=>'', 'old_prefix_id'=>'', 'old_suffix'=>'', 'prefix_name'=>''))
                                                                             );
                                            if($ll >= 1) {
                                                $new_item_num_attr[$ii] += 1;
                                            }
                                        }
                                        unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                        $break_sign = 1;
                                    }
                                // if old_input_type === "textarea" or "link" or "text"
                                // if new_input_type === "biblio_info"
                                }elseif($item_attr_type[$ii]["input_type"] === "biblio_info"){
                                    if($spell_check === "volume"){
                                        if($edit_biblio_info["volume"] === ""){
                                            //update Dummy array
                                            $edit_biblio_info["volume"] = $item_attr_old[$jj][0]["attribute_value"];
                                            $new_item_attr[$ii][0] = $edit_biblio_info;
                                            unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                        }
                                    }elseif($spell_check === "issue"){
                                        if($edit_biblio_info["issue"] === ""){
                                            //update Dummy array
                                            $edit_biblio_info["issue"] = $item_attr_old[$jj][0]["attribute_value"];
                                            $new_item_attr[$ii][0] = $edit_biblio_info;
                                            unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                        }
                                    }elseif($spell_check === "spage"){
                                        if($edit_biblio_info["spage"] === ""){
                                            //update Dummy array
                                            $edit_biblio_info["spage"] = $item_attr_old[$jj][0]["attribute_value"];
                                            $new_item_attr[$ii][0] = $edit_biblio_info;
                                            unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                        }
                                    }elseif($spell_check === "epage"){
                                        if($edit_biblio_info["epage"] === ""){
                                            //update Dummy array
                                            $edit_biblio_info["epage"] = $item_attr_old[$jj][0]["attribute_value"];
                                            $new_item_attr[$ii][0] = $edit_biblio_info;
                                            unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                        }
                                    }elseif($spell_check === "dateofissued"){
                                        if($edit_biblio_info["year"] === ""){
                                            //update Dummy array
                                            $edit_biblio_info["year"] = $item_attr_old[$jj][0]["attribute_value"];
                                            $new_item_attr[$ii][0] = $edit_biblio_info;
                                            unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                        }
                                    }elseif($spell_check === "jtitle"){
                                        if($edit_biblio_info["biblio_name"] === ""){
                                            //update Dummy array
                                            $edit_biblio_info["biblio_name"] = $item_attr_old[$jj][0]["attribute_value"];
                                            $new_item_attr[$ii][0] = $edit_biblio_info;
                                            unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                        }
                                    }
                                    if($edit_biblio_info["biblio_name"] !== ""
                                       && $edit_biblio_info["volume"] !== ""
                                       && $edit_biblio_info["issue"] !== ""
                                       && $edit_biblio_info["spage"] !== ""
                                       && $edit_biblio_info["epage"] !== ""
                                       && $edit_biblio_info["year"] !== ""){
                                        $break_sign = 1;
                                    }
                                    break;
                                }
                            }
                            break;

                        // if old_input_type === "radio" or "select"
                        case "radio":
                        case "select":
                            if($plural_cnt != 0){
                                // if old_input_type === "radio" or "select"
                                // if new_input_type === "textarea" or "text"
                                if($item_attr_type[$ii]["input_type"] === "text" || $item_attr_type[$ii]["input_type"] === "textarea") {
                                    $new_item_attr[$ii][0] = $item_attr_old[$jj][0]["attribute_value"];
                                    unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                    $break_sign = 1;
                                // if old_input_type === "radio" or "select"
                                // if new_input_type === "radio" or "select" or "checkbox"
                                }elseif($item_attr_type[$ii]["input_type"] === "radio"
                                    || $item_attr_type[$ii]["input_type"] === "select"
                                    || $item_attr_type[$ii]["input_type"] === "checkbox"){
                                    // create Dummy array
                                    $option_values = array();
                                    // create SQL array
                                    $params_cand = "select candidate_value from ".DATABASE_PREFIX."repository_item_attr_candidate ".
                                                   "WHERE item_type_id = ? and attribute_id = ? and is_delete = ?;";
                                    $params = array();
                                    $params[] = $Result_List["item"][0]["item_type_id"];    // item_type_id
                                    $params[] = $item_element[$ii2]["attribute_id"];    // attribute_id
                                    $params[] = 0;    // is_delete

                                    // get of choice from DB
                                    $option = $this->Db->execute($params_cand, $params);
                                    if ($option === false) {
                                        break;
                                    }

                                    // Number of elements
                                    $num_cand = count($option);
                                    // update Dummy array (Value to use for insert)
                                    for($kk=0; $kk<$num_cand ; $kk++) {
                                        $option_values[] = $option[$kk]["candidate_value"];
                                    }

                                    if($item_attr_type[$ii]["input_type"] === "radio"){
                                        for($kk=0; $kk<$num_cand ; $kk++) {
                                            if($item_attr_old[$jj][0]["attribute_value"] === $option_values[$kk]) {
                                                // insert in the position as an integer.
                                                $new_item_attr[$ii][0] = $kk;
                                                unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                                $break_sign = 1;
                                            }
                                        }
                                    }elseif($item_attr_type[$ii]["input_type"] === "select"){
                                        for($kk=0; $kk<$num_cand ; $kk++) {
                                            if($item_attr_old[$jj][0]["attribute_value"] === $option_values[$kk]) {
                                                $new_item_attr[$ii][0] = $item_attr_old[$jj][0]["attribute_value"];
                                                unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                                $break_sign = 1;
                                            }
                                        }
                                    }elseif($item_attr_type[$ii]["input_type"] === "checkbox"){
                                        // create Dummy array
                                        $edit_option = array();
                                        for($kk=0; $kk<$num_cand ; $kk++) {
                                        // Initialization (Dummy array)
                                        $edit_option[] = 0;
                                        }
                                        for($kk=0; $kk<$num_cand ; $kk++) {
                                            if($item_attr_old[$jj][0]["attribute_value"] === $option_values[$kk]) {
                                                // insert in the position as an integer.
                                                $edit_option[$kk] = 1;
                                                $edit_count = count($edit_option);
                                                for($n=0; $n<$edit_count; $n++) {
                                                    $new_item_attr[$ii][$n] = $edit_option[$n];
                                                }
                                                unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                                $break_sign = 1;
                                            }
                                        }
                                    }
                                }
                            }
                            break;

                        // if old_input_type === "radio" or "checkbox"
                        case "checkbox":
                            if($plural_cnt != 0){
                                // if old_input_type === "checkbox"
                                // if new_input_type === "text" or "textarea"
                                if($item_attr_type[$ii]["input_type"] === "text" || $item_attr_type[$ii]["input_type"] === "textarea") {
                                    if($plural_enable == 0) {
                                        $new_item_attr[$ii][0] = $item_attr_old[$jj][0]["attribute_value"];
                                        unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                        $break_sign = 1;
                                    } elseif($plural_enable == 1) {
                                        for($ll=0; $ll<$plural_cnt; $ll++) {
                                            $new_item_attr[$ii][$ll] = $item_attr_old[$jj][$ll]["attribute_value"];
                                            if($ll >= 1) {
                                                $new_item_num_attr[$ii] += 1;
                                            }
                                        }
                                        unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                        $break_sign = 1;
                                    }
                                // if old_input_type === "checkbox"
                                // if new_input_type === "checkbox"
                                }elseif($item_attr_type[$ii]["input_type"] === "checkbox"){
                                    // create Dummy array
                                    $option_values = array();
                                    // create SQL array
                                    $params_cand = "select candidate_value from ".DATABASE_PREFIX."repository_item_attr_candidate ".
                                                   "WHERE item_type_id = ? and attribute_id = ? and is_delete = ?;";
                                    $params = array();
                                    $params[] = $Result_List["item"][0]["item_type_id"];    // item_type_id
                                    $params[] = $item_element[$ii2]["attribute_id"];    // attribute_id
                                    $params[] = 0;    // is_delete

                                    // get of choice from DB
                                    $option = $this->Db->execute($params_cand, $params);
                                    if ($option === false) {
                                        break;
                                    }
                                    // Number of elements
                                    $num_cand = count($option);
                                    // create Dummy array
                                    $edit_option = array();
                                    for($kk=0; $kk<$num_cand ; $kk++) {
                                        // update Dummy array (Value to use for insert)
                                        $option_values[] = $option[$kk]["candidate_value"];
                                        // update Dummy array
                                        $edit_option[] = 0;
                                    }
                                    // Elements exist flag
                                    $jadge = 0;

                                    if($plural_cnt != 0) {
                                        for($ll=0; $ll<$plural_cnt; $ll++) {
                                            for($kk=0; $kk<$num_cand ; $kk++) {
                                                if($item_attr_old[$jj][$ll]["attribute_value"] === $option_values[$kk]) {
                                                    // insert in the position as an integer.
                                                    $edit_option[$kk] = 1;
                                                    // update Elements exist flag
                                                    $jadge += 1;
                                                }
                                            }
                                        }
                                        // If the search string is found
                                        if($jadge != 0) {
                                            $edit_count = count($edit_option);
                                        for($n=0; $n<$edit_count; $n++) {
                                            // insert to array
                                            $new_item_attr[$ii][$n] = $edit_option[$n];
                                        }
                                        unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                        $break_sign = 1;
                                        }
                                    }
                                }
                            }
                            break;

                        // if old_input_type === "name"
                        case "name":
                            if($plural_cnt != 0){
                                // if old_input_type === "name"
                                // if new_input_type === "text" or "textarea"
                                if($item_attr_type[$ii]["input_type"] === "text" || $item_attr_type[$ii]["input_type"] === "textarea") {
                                    if($plural_enable == 0) {
                                        $new_item_attr[$ii][0] = $item_attr_old[$jj][0]["family"]." ".$item_attr_old[$jj][0]["name"]." ".$item_attr_old[$jj][0]["e_mail_address"];
                                        unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                        $break_sign = 1;
                                    } elseif($plural_enable == 1) {
                                        for($ll=0; $ll<$plural_cnt; $ll++) {
                                            $new_item_attr[$ii][$ll] = $item_attr_old[$jj][$ll]["family"]." ".$item_attr_old[$jj][$ll]["name"]." ".$item_attr_old[$jj][$ll]["e_mail_address"];
                                            if($ll >= 1) {
                                                $new_item_num_attr[$ii] += 1;
                                            }
                                        }
                                        unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                        $break_sign = 1;
                                    }
                                // if old_input_type === "name"
                                // if new_input_type === "name"
                                }elseif($item_attr_type[$ii]["input_type"] === "name"){
                                    if($plural_enable == 0) {
                                        $external_author_id = $NameAuthority->getExternalAuthorIdPrefixAndSuffix($item_attr_old[$jj][0]["author_id"]);
                                        if ($external_author_id === false) {
                                            return "error";
                                        }
                                        $new_item_attr[$ii][0] = array(
                                                                       "family" => $item_attr_old[$jj][0]["family"],
                                                                       "given" => $item_attr_old[$jj][0]["name"],
                                                                       "family_ruby" => $item_attr_old[$jj][0]["family_ruby"],
                                                                       "given_ruby" => $item_attr_old[$jj][0]["name_ruby"],
                                                                       "email" => $item_attr_old[$jj][0]["e_mail_address"],
                                                                       "author_id" => $item_attr_old[$jj][0]["author_id"],
                                                                       "language" => $item_attr_type_old[$jj]["display_lang_type"],
                                                                       "external_author_id" => $external_author_id
                                                                      );
                                        unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                        $break_sign = 1;
                                    } elseif($plural_enable == 1 && $plural_cnt != 0) {
                                        for($ll=0; $ll<$plural_cnt; $ll++) {
                                            $external_author_id = $NameAuthority->getExternalAuthorIdPrefixAndSuffix($item_attr_old[$jj][$ll]["author_id"]);
                                            if ($external_author_id === false) {
                                                return "error";
                                            }
                                            $new_item_attr[$ii][$ll] = array(
                                                                             "family" => $item_attr_old[$jj][$ll]["family"],
                                                                             "given" => $item_attr_old[$jj][$ll]["name"],
                                                                             "family_ruby" => $item_attr_old[$jj][$ll]["family_ruby"],
                                                                             "given_ruby" => $item_attr_old[$jj][$ll]["name_ruby"],
                                                                             "email" => $item_attr_old[$jj][$ll]["e_mail_address"],
                                                                             "author_id" => $item_attr_old[$jj][$ll]["author_id"],
                                                                             "language" => $item_attr_type_old[$jj]["display_lang_type"],
                                                                             "external_author_id" => $external_author_id
                                                                             );
                                            if($ll >= 1) {
                                                $new_item_num_attr[$ii] += 1;
                                            }
                                        }
                                        unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                        $break_sign = 1;
                                    }
                                }
                                break;
                            }

                        // if old_input_type === "biblio_info"
                        case "biblio_info":
                            if($plural_cnt != 0){
                                // if old_input_type === "biblio_info"
                                // if new_input_type === "text" or "textarea"
                                if($item_attr_type[$ii]["input_type"] === "text" || $item_attr_type[$ii]["input_type"] === "textarea") {
                                    if($spell_check === "volume"){
                                        // If the search string is not found
                                        if($item_attr_old[$jj][0]["volume"] === ""){
                                            break;
                                        }
                                        $new_item_attr[$ii][0] = $item_attr_old[$jj][0]["volume"];
                                        $item_attr_old[$jj][0]["volume"] = "";
                                        $break_sign = 1;
                                    }elseif($spell_check === "issue"){
                                        // If the search string is not found
                                        if($item_attr_old[$jj][0]["issue"] === ""){
                                            break;
                                        }
                                        $new_item_attr[$ii][0] = $item_attr_old[$jj][0]["issue"];
                                        $item_attr_old[$jj][0]["issue"] = "";
                                        $break_sign = 1;
                                    }elseif($spell_check === "spage"){
                                        // If the search string is not found
                                        if($item_attr_old[$jj][0]["start_page"] === ""){
                                            break;
                                        }
                                        $new_item_attr[$ii][0] = $item_attr_old[$jj][0]["start_page"];
                                        $item_attr_old[$jj][0]["start_page"] = "";
                                        $break_sign = 1;
                                    }elseif($spell_check === "epage"){
                                        // If the search string is not found
                                        if($item_attr_old[$jj][0]["end_page"] === ""){
                                            break;
                                        }
                                        $new_item_attr[$ii][0] = $item_attr_old[$jj][0]["end_page"];
                                        $item_attr_old[$jj][0]["end_page"] = "";
                                        $break_sign = 1;
                                    }elseif($spell_check === "dateofissued"){
                                        // If the search string is not found
                                        if($item_attr_old[$jj][0]["date_of_issued"] === ""){
                                            break;
                                        }
                                        $new_item_attr[$ii][0] = $item_attr_old[$jj][0]["date_of_issued"];
                                        $item_attr_old[$jj][0]["date_of_issued"] = "";
                                        $break_sign = 1;
                                    }elseif($spell_check === "jtitle"){
                                        // If the search string is not found
                                        if($item_attr_old[$jj][0]["biblio_name"] === "" && $item_attr_old[$jj][0]["biblio_name_english"] === ""){
                                            break;
                                        }
                                        if($item_attr_type[$ii]["input_type"] === "text"){
                                            $new_item_attr[$ii][0] = $item_attr_old[$jj][0]["biblio_name"]."\n".$item_attr_old[$jj][0]["biblio_name_english"];
                                        }elseif($item_attr_type[$ii]["input_type"] === "textarea"){
                                            $new_item_attr[$ii][0] = $item_attr_old[$jj][0]["biblio_name"]."\n".$item_attr_old[$jj][0]["biblio_name_english"];
                                        }
                                        $break_sign = 1;
                                        $item_attr_old[$jj][0]["biblio_name"] = "";
                                        $item_attr_old[$jj][0]["biblio_name_english"] = "";
                                    }elseif($spell_check === ""){
                                        if($item_attr_type[$ii]["input_type"] === "text"){
                                            $new_item_attr[$ii][0] = $item_attr_old[$jj][0]["biblio_name"]." ".
                                             $item_attr_old[$jj][0]["biblio_name_english"]." ".
                                             $item_attr_old[$jj][0]["volume"]." ".
                                             $item_attr_old[$jj][0]["issue"]." ".
                                             $item_attr_old[$jj][0]["start_page"]." ".
                                             $item_attr_old[$jj][0]["end_page"]." ".
                                             $item_attr_old[$jj][0]["date_of_issued"];
                                        }elseif($item_attr_type[$ii]["input_type"] === "textarea"){
                                            $new_item_attr[$ii][0] = $item_attr_old[$jj][0]["biblio_name"]."\n".
                                             $item_attr_old[$jj][0]["biblio_name_english"]."\n".
                                             $item_attr_old[$jj][0]["volume"]."\n".
                                             $item_attr_old[$jj][0]["issue"]."\n".
                                             $item_attr_old[$jj][0]["start_page"]."\n".
                                             $item_attr_old[$jj][0]["end_page"]."\n".
                                             $item_attr_old[$jj][0]["date_of_issued"];
                                        }
                                        unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                        $break_sign = 1;
                                    }
                                    if($item_attr_old[$jj][0]["biblio_name"] === ""
                                        && $item_attr_old[$jj][0]["biblio_name_english"] === ""
                                        && $item_attr_old[$jj][0]["volume"] === ""
                                        && $item_attr_old[$jj][0]["issue"] === ""
                                        && $item_attr_old[$jj][0]["start_page"] === ""
                                        && $item_attr_old[$jj][0]["end_page"] === ""
                                        && $item_attr_old[$jj][0]["date_of_issued"] === ""){
                                        unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                        $break_sign = 1;
                                    }

                                // if old_input_type === "biblio_info"
                                // if new_input_type === "biblio_info"
                                }elseif($item_attr_type[$ii]["input_type"] === "biblio_info"){
                                    // Split a string by haihun(-)
                                    $date = explode("-", $item_attr_old[$jj][0]["date_of_issued"]);
                                    if(count($date) == 2){
                                        $date[] = "";
                                    } else if(count($date) == 1){
                                        $date[] = "";
                                        $date[] = "";
                                    }
                                    // update to single digits
                                    if(strlen($date[1]) == 2){
                                        $temp_month = str_split($date[1]);
                                        if($temp_month[0] == "0"){
                                            $date[1] = $temp_month[1];
                                        }
                                    }
                                    // update to single digits
                                    if(strlen($date[2]) == 2){
                                        $temp_day = str_split($date[2]);
                                        if($temp_day[0] == "0"){
                                            $date[2] = $temp_day[1];
                                        }
                                    }

                                    $new_item_attr[$ii][0] = array(
                                                                   "biblio_name" => $item_attr_old[$jj][0]["biblio_name"],
                                                                   "biblio_name_english" => $item_attr_old[$jj][0]["biblio_name_english"],
                                                                   "volume" => $item_attr_old[$jj][0]["volume"],
                                                                   "issue" => $item_attr_old[$jj][0]["issue"],
                                                                   "spage" => $item_attr_old[$jj][0]["start_page"],
                                                                   "epage" => $item_attr_old[$jj][0]["end_page"],
                                                                   "date_of_issued" => $item_attr_old[$jj][0]["date_of_issued"],
                                                                   "year" =>$date[0],
                                                                   "month" =>$date[1],
                                                                   "day" =>$date[2]
                                                                   );
                                    unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                    $break_sign = 1;

                                // if old_input_type === "biblio_info"
                                // if new_input_type === "date"
                                }elseif($item_attr_type[$ii]["input_type"] === "date"){
                                    if($item_attr_old[$jj][0]["date_of_issued"] === ""){
                                        break;
                                    }
                                    // Split a string by haihun(-)
                                    $date = explode("-", $item_attr_old[$jj][0]["date_of_issued"]);
                                    if(count($date) == 2){
                                        $date[] = "";
                                    } else if(count($date) == 1){
                                        $date[] = "";
                                        $date[] = "";
                                    }
                                    // update to single digits
                                    if(strlen($date[1]) == 2){
                                        $temp_month = str_split($date[1]);
                                        if($temp_month[0] == "0"){
                                            $date[1] = $temp_month[1];
                                        }
                                    }
                                    // update to single digits
                                    if(strlen($date[2]) == 2){
                                        $temp_day = str_split($date[2]);
                                        if($temp_day[0] == "0"){
                                            $date[2] = $temp_day[1];
                                        }
                                    }
                                    $new_item_attr[$ii][0] = array(
                                                                   "date" => $item_attr_old[$jj][$kk]["attribute_value"],
                                                                   "date_year" =>$date[0],
                                                                   "date_month" =>$date[1],
                                                                   "date_day" =>$date[2]
                                                                   );
                                    $item_attr_old[$jj][0]["date_of_issued"] = "";
                                    $break_sign = 1;
                                }
                            }
                            break;

                        // if old_input_type === "date"
                        case "date":
                            if($plural_cnt != 0) {
                                // if old_input_type === "date"
                                // if new_input_type === "text" or "textarea"
                                if($item_attr_type[$ii]["input_type"] === "text" || $item_attr_type[$ii]["input_type"] === "textarea") {
                                    if($plural_enable == 0) {
                                        $new_item_attr[$ii][0] = $item_attr_old[$jj][0]["attribute_value"];
                                        unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                        $break_sign = 1;
                                    } elseif($plural_enable == 1) {
                                        for($ll=0; $ll<$plural_cnt; $ll++) {
                                            $new_item_attr[$ii][$ll] = $item_attr_old[$jj][$ll]["attribute_value"];
                                            if($ll >= 1) {
                                                $new_item_num_attr[$ii] += 1;
                                            }
                                        }
                                        unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                        $break_sign = 1;
                                    }
                                // if old_input_type === "date"
                                // if new_input_type === "biblio_info" or "date"
                                }elseif($item_attr_type[$ii]["input_type"] === "biblio_info" || $item_attr_type[$ii]["input_type"] === "date"){
                                // if old_input_type === "date"
                                // if new_input_type === "biblio_info"
                                    if($item_attr_type[$ii]["input_type"] === "biblio_info"
                                       && $edit_biblio_info["date_of_issued"] === ""
                                       && $edit_biblio_info["year"] === ""
                                       && $edit_biblio_info["month"] === ""
                                       && $edit_biblio_info["day"] === ""){

                                        // Split a string by haihun(-)
                                        $date = explode("-", $item_attr_old[$jj][0]["attribute_value"]);
                                        if(count($date) == 2){
                                            $date[] = "";
                                        } else if(count($date) == 1){
                                            $date[] = "";
                                            $date[] = "";
                                        }

                                        // update to single digits
                                        if(strlen($date[1]) == 2){
                                            $temp_month = str_split($date[1]);
                                            if($temp_month[0] == "0"){
                                                $date[1] = $temp_month[1];
                                            }
                                        }
                                        // update to single digits
                                        if(strlen($date[2]) == 2){
                                            $temp_day = str_split($date[2]);
                                            if($temp_day[0] == "0"){
                                                $date[2] = $temp_day[1];
                                            }
                                        }
                                        $edit_biblio_info["date_of_issued"] = $item_attr_old[$jj][0]["attribute_value"];
                                        $edit_biblio_info["year"] = $date[0];
                                        $edit_biblio_info["month"] = $date[1];
                                        $edit_biblio_info["day"] = $date[2];

                                        $new_item_attr[$ii][0] = $edit_biblio_info;

                                        unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                        if($edit_biblio_info["biblio_name"] !== ""
                                           && $edit_biblio_info["volume"] !== ""
                                           && $edit_biblio_info["issue"] !== ""
                                           && $edit_biblio_info["spage"] !== ""
                                           && $edit_biblio_info["epage"] !== ""
                                           && $edit_biblio_info["date_of_issued"] !== ""
                                           && $edit_biblio_info["year"] !== ""
                                           && $edit_biblio_info["month"] !== ""
                                           && $edit_biblio_info["day"] !== ""){
                                        $break_sign = 1;
                                        }
                                    // if old_input_type === "date"
                                    // if new_input_type === "date"
                                    }elseif($item_attr_type[$ii]["input_type"] === "date") {
                                        // create Dummy array
                                        $edit_attr = array();
                                        for($kk=0; $kk<$plural_cnt; $kk++) {
                                            // Split a string by haihun(-)
                                            $date = explode("-", $item_attr_old[$jj][$kk]["attribute_value"]);
                                            if(count($date) == 2){
                                                $date[] = "";
                                            } else if(count($date) == 1){
                                                $date[] = "";
                                                $date[] = "";
                                            }

                                        // update to single digits
                                        if(strlen($date[1]) == 2){
                                            $temp_month = str_split($date[1]);
                                            if($temp_month[0] == "0"){
                                                $date[1] = $temp_month[1];
                                            }
                                        }
                                        // update to single digits
                                        if(strlen($date[2]) == 2){
                                            $temp_day = str_split($date[2]);
                                            if($temp_day[0] == "0"){
                                                $date[2] = $temp_day[1];
                                            }
                                        }
                                        // create Dummy elements array
                                        $date_init = array(
                                                           "date" => $item_attr_old[$jj][$kk]["attribute_value"],
                                                           "date_year" =>$date[0],
                                                           "date_month" =>$date[1],
                                                           "date_day" =>$date[2]
                                                          );
                                        // insert to Dummy elements array
                                        $edit_attr[] = $date_init;
                                        }

                                        if($plural_enable == 0) {
                                            $new_item_attr[$ii][0] = $edit_attr[0];
                                        unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                            $break_sign = 1;
                                        } elseif($plural_enable == 1){
                                            for($ll=0; $ll<$plural_cnt; $ll++) {
                                                $new_item_attr[$ii][$ll] = $edit_attr[$ll];
                                                if($ll >= 1) {
                                                    $new_item_num_attr[$ii] += 1;
                                                }
                                            }
                                            unset($item_attr_old[$jj],$item_attr_type_old[$jj]);
                                            $break_sign = 1;
                                        }
                                    }
                                }
                            }
                            break;

                        default:
                            break;
                    }
                }
                if($break_sign == 1){
                    break;
                }
            }
        }
        // session is hold the item information
        $this->Session->setParameter("item_num_attr",$new_item_num_attr);
        $this->Session->setParameter("item_attr",$new_item_attr);
        $this->Session->setParameter("base_attr",$base_attr);
        $this->Session->setParameter("item_keyword",$item_keyword);
        $this->Session->setParameter("item_keyword_english",$item_keyword_english);
    }
    // ItemIDFillDataCopy(ResultList[item_type] != session[item_type]) 2010/02/16 S.Nonomura --end--
    
    // Add JuNii2 ver3 2013/09/24 R.Matsuura --start--
    /**
     * get metadata of input type Text
     * 
     * @param string $strMapping
     * @param string $displayLangType
     * @param int $pluralEnable
     * @param array $oldMetadataArray
     * @param int $itemAttrNum
     * @param array $metadataArray
     */
    private function getMetadataOfInputTypeText($strMapping, $displayLangType, $pluralEnable, $oldMetadataArray, &$itemAttrNum, &$metadataArray)
    {
        /////////////////////////////
        // set data from junii2
        /////////////////////////////
        if($strMapping == "creator")
        {
            
            if(count($this->creator) > 0 && ($displayLangType == "japanese" || $displayLangType == ""))
            {
                if($pluralEnable == 1)
                {
                    for($ii=0; $ii<count($this->creator); $ii++){
                        if($this->creator[$ii]["family"]!=""){
                            array_push($metadataArray, $this->creator[$ii]["family"]." ".$this->creator[$ii]["given"]);
                        }
                    }
                    $itemAttrNum = count($this->creator);
                    $this->creator = array();
                }
                else
                {
                    array_push($metadataArray, $this->creator[0]["family"]." ".$this->creator[0]["given"]);
                    $itemAttrNum = 1;
                    array_shift($this->creator);
                }
            }
            else if(count($this->creator_english) > 0 && ($displayLangType == "english" || $displayLangType == ""))
            {
                if($pluralEnable == 1)
                {
                    for($ii=0; $ii<count($this->creator_english); $ii++){
                        if($this->creator_english[$ii]["family"]!=""){
                            array_push($metadataArray, $this->creator_english[$ii]["family"]." ".$this->creator_english[$ii]["given"]);
                        }
                    }
                    $itemAttrNum = count($this->creator_english);
                    $this->creator_english = array();
                }
                else
                {
                    array_push($metadataArray, $this->creator_english[0]["family"]." ".$this->creator_english[0]["given"]);
                    $itemAttrNum = 1;
                    array_shift($this->creator_english);
                }
            }
            else
            {
                for($ii=0; $ii<$itemAttrNum; $ii++) {
                    array_push($metadataArray, $oldMetadataArray[$ii]);
                }
            }
        } else if($strMapping == "description") {
            
            if(count($this->description) > 0 && ($displayLangType == "japanese" || $displayLangType == ""))
            {
                if($pluralEnable == 1)
                {
                    for($ii=0; $ii<count($this->description); $ii++){
                        array_push($metadataArray, $this->description[$ii]);
                    }
                    $itemAttrNum = count($this->description);
                    $this->description = array();
                }
                else
                {
                    array_push($metadataArray, array_shift($this->description));
                    $itemAttrNum = 1;
                }
            }
            else if(count($this->description_english) > 0 && ($displayLangType == "english" || $displayLangType == ""))
            {
                if($pluralEnable == 1)
                {
                    for($ii=0; $ii<count($this->description_english); $ii++){
                        array_push($metadataArray, $this->description_english[$ii]);
                    }
                    $itemAttrNum = count($this->description_english);
                }
                else
                {
                    array_push($metadataArray, array_shift($this->description_english));
                    $itemAttrNum = 1;
                }
            }
            else
            {
                for($ii=0; $ii<$itemAttrNum; $ii++) {
                    array_push($metadataArray, $oldMetadataArray[$ii]);
                }
            }
            
        } else if($strMapping == "publisher") {
            
            if(count($this->publisher) > 0 && ($displayLangType == "japanese" || $displayLangType == ""))
            {
                if($pluralEnable == 1)
                {
                    for($ii=0; $ii<count($this->publisher); $ii++){
                        array_push($metadataArray, $this->publisher[$ii]);
                    }
                    $itemAttrNum = count($this->publisher);
                    $this->publisher = array();
                }
                else
                {
                    array_push($metadataArray, array_shift($this->publisher));
                    $itemAttrNum = 1;
                }
            }
            else if(count($this->publisher_english) > 0 && ($displayLangType == "english" || $displayLangType == ""))
            {
                if($pluralEnable == 1)
                {
                    for($ii=0; $ii<count($this->publisher_english); $ii++){
                        array_push($metadataArray, $this->publisher_english[$ii]);
                    }
                    $itemAttrNum = count($this->publisher_english);
                    $this->publisher_english = array();
                }
                else
                {
                    array_push($metadataArray, array_shift($this->publisher_english));
                    $itemAttrNum = 1;
                }
            }
            else
            {
                for($ii=0; $ii<$itemAttrNum; $ii++) {
                    array_push($metadataArray, $oldMetadataArray[$ii]);
                }
            }
        } else if($strMapping == "issn") {
            if($this->issn != ""){
                array_push($metadataArray, $this->issn);
                $itemAttrNum = 1;
                $this->issn = '';
            } else if($this->e_issn != ""){
                array_push($metadataArray, $this->e_issn);
                $itemAttrNum = 1;
                $this->e_issn = '';
            }
            else {
                for($ii=0; $ii<$itemAttrNum; $ii++) {
                    array_push($metadataArray, $oldMetadataArray[$ii]);
                }
            }
        } else if($strMapping == "jtitle") {
            
            if(strlen($this->jtitle) > 0 && ($displayLangType == "japanese" || $displayLangType == ""))
            {
                array_push($metadataArray, $this->jtitle);
                $itemAttrNum = 1;
                $this->jtitle = '';
            }
            else if(count($this->jtitle_english) > 0 && ($displayLangType == "english" || $displayLangType == ""))
            {
                array_push($metadataArray, $this->jtitle_english);
                $itemAttrNum = 1;
                $this->jtitle_english = '';
            }
            else
            {
                for($ii=0; $ii<$itemAttrNum; $ii++) {
                    array_push($metadataArray, $oldMetadataArray[$ii]);
                }
            }
            
        } else if($strMapping == "volume") {
            if($this->volume != ""){
                array_push($metadataArray, $this->volume);
                $itemAttrNum = 1;
                $this->volume = '';
            } else {
                for($ii=0; $ii<$itemAttrNum; $ii++) {
                    array_push($metadataArray, $oldMetadataArray[$ii]);
                }
            }
        } else if($strMapping == "issue") {
            if($this->issue != ""){
                array_push($metadataArray, $this->issue);
                $itemAttrNum = 1;
                $this->issue = '';
            } else {
                for($ii=0; $ii<$itemAttrNum; $ii++) {
                    array_push($metadataArray, $oldMetadataArray[$ii]);
                }
            }
        } else if($strMapping == "spage") {
            if($this->spage != ""){
                array_push($metadataArray, $this->spage);
                $itemAttrNum = 1;
                $this->spage = '';
            } else {
                for($ii=0; $ii<$itemAttrNum; $ii++) {
                    array_push($metadataArray, $oldMetadataArray[$ii]);
                }
            }
        } else if($strMapping == "epage") {
            if($this->epage != ""){
                array_push($metadataArray, $this->epage);
                $itemAttrNum = 1;
                $this->epage = '';
            } else {
                for($ii=0; $ii<$itemAttrNum; $ii++) {
                    array_push($metadataArray, $oldMetadataArray[$ii]);
                }
            }
        } else if($strMapping == "dateofissued") {
            if($this->dateofissued != ""){
                array_push($metadataArray, $this->dateofissued);
                $itemAttrNum = 1;
                $this->dateofissued = '';
            } else {
                for($ii=0; $ii<$itemAttrNum; $ii++) {
                    array_push($metadataArray, $oldMetadataArray[$ii]);
                }
            }
        } else if($strMapping == "pmid") {
            if($this->pmid != ""){
                array_push($metadataArray, $this->pmid);
                $itemAttrNum = 1;
                $this->pmid = '';
            } else {
                for($ii=0; $ii<$itemAttrNum; $ii++) {
                    array_push($metadataArray, $oldMetadataArray[$ii]);
                }
            }
        } else if($strMapping == "doi") {
            if($this->doi != ""){
                array_push($metadataArray, $this->doi);
                $itemAttrNum = 1;
                $this->doi = '';
            } else {
                for($ii=0; $ii<$itemAttrNum; $ii++) {
                    array_push($metadataArray, $oldMetadataArray[$ii]);
                }
            }
        // add alternative 2008/11/26 A.Suzuki --start--
        } else if($strMapping == "alternative") {
            if(count($this->alternative) > 0)
            {
                if($pluralEnable == 1)
                {
                    for($ii=0; $ii<count($this->alternative); $ii++){
                        array_push($metadataArray, $this->alternative[$ii]);
                    }
                    $itemAttrNum = count($this->alternative);
                    $this->alternative = array();
                }
                else
                {
                    array_push($metadataArray, array_shift($this->alternative));
                    $itemAttrNum = 1;
                }
            } else {
                for($ii=0; $ii<$itemAttrNum; $ii++) {
                    array_push($metadataArray, $oldMetadataArray[$ii]);
                }
            }
        // add alternative 2008/11/26 A.Suzuki --end--
        // add contributor 2009/08/04 A.Suzuki --start--
        } else if($strMapping == "contributor") {
            if(count($this->contributor) > 0 && ($displayLangType == "japanese" || $displayLangType == ""))
            {
                if($pluralEnable == 1)
                {
                    for($ii=0; $ii<count($this->contributor); $ii++){
                        array_push($metadataArray, $this->contributor[$ii]);
                    }
                    $itemAttrNum = count($this->contributor);
                    $this->contributor = array();
                }
                else
                {
                    array_push($metadataArray, array_shift($this->contributor));
                    $itemAttrNum = 1;
                }
            }
            else if(count($this->contributor_english) > 0 && ($displayLangType == "english" || $displayLangType == ""))
            {
                if($pluralEnable == 1)
                {
                    for($ii=0; $ii<count($this->contributor_english); $ii++){
                        array_push($metadataArray, $this->contributor_english[$ii]);
                    }
                    $itemAttrNum = count($this->contributor_english);
                    $this->contributor_english = array();
                }
                else
                {
                    array_push($metadataArray, array_shift($this->contributor_english));
                    $itemAttrNum = 1;
                }
            }
            else
            {
                for($ii=0; $ii<$itemAttrNum; $ii++) {
                    array_push($metadataArray, $oldMetadataArray[$ii]);
                }
            }
        }
        else if($strMapping == "isbn")
        {
            if($this->isbn != "")
            {
                array_push($metadataArray, $this->isbn);
                $this->isbn = "";
            }
            else
            {
                for($ii=0; $ii<$itemAttrNum; $ii++) {
                    array_push($metadataArray, $oldMetadataArray[$ii]);
                }
            }
        }
        else if($strMapping == "NAID")
        {
            if($this->naid != "")
            {
                array_push($metadataArray, $this->naid);
                $this->naid = "";
            }
            else
            {
                for($ii=0; $ii<$itemAttrNum; $ii++) {
                    array_push($metadataArray, $oldMetadataArray[$ii]);
                }
            }
        }
        // Add NCID 2014/04/03 A.Suzuki --start--
        else if($strMapping == "NCID")
        {
            if(strlen($this->ncid) > 0)
            {
                array_push($metadataArray, $this->ncid);
                $this->ncid = "";
            }
            else
            {
                for($ii=0; $ii<$itemAttrNum; $ii++) {
                    array_push($metadataArray, $oldMetadataArray[$ii]);
                }
            }
        }
        // Add NCID 2014/04/03 A.Suzuki --end--
        else if($strMapping == "ichushi")
        {
            if($this->ichushi_id != "")
            {
                array_push($metadataArray, $this->ichushi_id);
                $this->ichushi_id = "";
            }
            else
            {
                for($ii=0; $ii<$itemAttrNum; $ii++) {
                    array_push($metadataArray, $oldMetadataArray[$ii]);
                }
            }
        }
        // add contributor 2009/08/04 A.Suzuki --end--
        else
        {
            // old data copy
            for($ii=0; $ii<$itemAttrNum; $ii++)
            {
                array_push($metadataArray, $oldMetadataArray[$ii]);
            }
        }
    }
    // Add JuNii2 ver3 2013/09/24 R.Matsuura --end--
}
?>
