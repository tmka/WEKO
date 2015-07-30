<?php
// --------------------------------------------------------------------
//
// $Id: Fillauthor.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
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

/**
 * Fill biblio info from other site.
 * Site List
 *  - PubMed
 *  - Amazon
 *  - CiNii
 *
 */
class Repository_Action_Main_Item_Fillauthor extends RepositoryAction
{
    // conponents
    public $Session = null;
    public $Db = null;
    
    // member
    public $surName = null;
    public $givenName = null;
    public $surNameRuby = null;
    public $givenNameRuby = null;
    public $emailAddress = null;
    public $attrId = null;
    public $attrNo = null;
    public $fillStr = null;
    public $mode = null;
    public $prefixId = null;
    public $suffixId = null;
    // Add e-person 2013/11/19 R.Matsuura --start--
    public $externalAuthorID = null;
    // Add e-person 2013/11/19 R.Matsuura --end--
    
    // Form
    public $base_attr = null;                           // base info
    public $item_pub_date_year = null;                  // pub_date : year
    public $item_pub_date_month = null;                 // pub_date : month
    public $item_pub_date_day = null;                   // pub_date : day
    public $item_keyword = null;                        // keyword
    public $item_keyword_english = null;                // keyword_english
    public $item_attr_text = null;                      // text
    public $item_attr_textarea = null;                  // textarea
    public $item_attr_checkbox = null;                  // checkbox
    public $item_attr_name_family = null;               // name : surname
    public $item_attr_name_given = null;                // name : given name
    public $item_attr_name_family_ruby = null;          // name : surname ruby
    public $item_attr_name_given_ruby = null;           // name : given name ruby
    public $item_attr_name_email = null;                // name : e-mail
    public $item_attr_name_author_id_prefix = null;     // name : authorID prefix
    public $item_attr_name_author_id_suffix = null;     // name : authorID suffix
    public $item_attr_select = null;                    // select
    public $item_attr_link = null;                      // link : value
    public $item_attr_link_name = null;                 // link : name
    public $item_attr_radio = null;                     // radio
    public $item_attr_biblio_name = null;               // biblio_info : title
    public $item_attr_biblio_name_english = null;       // biblio_info : title_english
    public $item_attr_biblio_volume = null;             // biblio_info : volume
    public $item_attr_biblio_issue = null;              // biblio_info : issue
    public $item_attr_biblio_spage = null;              // biblio_info : start_page
    public $item_attr_biblio_epage = null;              // biblio_info : end_page
    public $item_attr_biblio_dateofissued_year = null;  // biblio_info : year
    public $item_attr_biblio_dateofissued_month = null; // biblio_info : month
    public $item_attr_biblio_dateofissued_day = null;   // biblio_info : day
    public $item_attr_date_year = null;                 // date : year
    public $item_attr_date_month = null;                // date : month
    public $item_attr_date_day = null;                  // date : day
    public $item_attr_heading = null;                   // heading
    public $item_attr_heading_en = null;                // heading(english)
    public $item_attr_heading_sub = null;               // subheading
    public $item_attr_heading_sub_en = null;            // subheading(english)
    
    // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --start--
    public $item_contributor = null;
    public $item_contributor_handle = null;
    public $item_contributor_name= null;
    public $item_contributor_email = null;
    // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --start--
    
    private $resolverUrl = "http://rns.nii.ac.jp/";     // http://rns.nii.ac.jp/opensearch?q5=xxxx : 科研費研究者番号で検索
                                                        // http://rns.nii.ac.jp/opensearch?q6=xxxx : 研究者リゾルバーIDで検索
    private $ciniiUrl = "http://ci.nii.ac.jp/";         // http://ci.nii.ac.jp/nrid/xxxxxxxx.rdf   : CiNiiIDで検索
    
    private $fillSurName = "";
    private $fillSurNameRuby = "";
    private $fillSurNameEn = "";
    private $fillGivenName = "";
    private $fillGivenNameRuby = "";
    private $fillGivenNameEn = "";
    private $fillOrganization = "";
    private $fillOrganizationEn = "";
    
    // add error message 2011/02/21 H.Goto --start--
    var $error_msg = null;          // error message
    var $smartyAssign = null;       // for get language resource
    // add error message 2011/02/21 H.Goto --end--
    
    /**
     * 
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
                $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );
                $DetailMsg = null;
                sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                $exception->setDetailMsg( $DetailMsg );
                $this->failTrans();
                $user_error_msg = '';
                throw $exception;
            }
            
            $NameAuthority = new NameAuthority($this->Session, $this->Db);
            
            if($this->mode == "suggest"){
                if(strlen($this->fillStr) > 0){
                    // Fill suggest data
                    $this->saveFormData();
                    $this->fillSuggestData($this->fillStr);
                    return 'success';
                }
            } else if($this->mode == "fill"){
                $this->saveFormData();
                if(strlen($this->prefixId) > 0 && strlen($this->suffixId)){
                    // Check name authority
                    
                    // Add Check name authority 2011/01/17 H.Goto --start--
                    // check Language setting
                    $item_attr_type = $this->Session->getParameter("item_attr_type");
                    $display_lang_type = $item_attr_type[$this->attrId]["display_lang_type"];
                    
                    //check wekoDB same prefixId and suffixId
                    $author_id_suffix =  $NameAuthority->getAuthorByPrefixAndSuffix($this->prefixId, $this->suffixId);
                    
                    //When there is no pertinent person,check other site
                    if($author_id_suffix == false || count($author_id_suffix)==0){
                        // Search other site
                        if($this->prefixId == "1" || $this->prefixId == "2" || $this->prefixId == "3"){
                            $name = $this->getAuthorFillData($this->prefixId, $this->suffixId,$display_lang_type);
                            if($name != false){
                                // Get item_attr by Session
                                $item_attr = $this->Session->getParameter("item_attr");
                                $item_attr[$this->attrId][$this->attrNo]["family"] = $name["familyname"];
                                $item_attr[$this->attrId][$this->attrNo]["given"] = $name["firstname"];
                                $item_attr[$this->attrId][$this->attrNo]["family_ruby"] = $name["familyname_ruby"];
                                $item_attr[$this->attrId][$this->attrNo]["given_ruby"] = $name["firstname_ruby"];
                                $item_attr[$this->attrId][$this->attrNo]["author_id"] = 0;
                                $item_attr[$this->attrId][$this->attrNo]["email"] = "";
                                // Set fill data to session
                                $this->Session->setParameter("item_attr", $item_attr);
                            }else{
                                $item_attr = $this->Session->getParameter("item_attr");
                                $item_attr[$this->attrId][$this->attrNo]["family"] = "";
                                $item_attr[$this->attrId][$this->attrNo]["given"] = "";
                                $item_attr[$this->attrId][$this->attrNo]["family_ruby"] = "";
                                $item_attr[$this->attrId][$this->attrNo]["given_ruby"] = "";
                                $item_attr[$this->attrId][$this->attrNo]["author_id"] = 0;
                                $item_attr[$this->attrId][$this->attrNo]["email"] = "";
                                $item_attr[$this->attrId][$this->attrNo]["external_author_id"] = array(array('prefix_id'=>'', 'suffix'=>''));
                                // Set fill data to session
                                $this->Session->setParameter("item_attr", $item_attr);
                            }
                        } else {
                            $item_attr = $this->Session->getParameter("item_attr");
                            $item_attr[$this->attrId][$this->attrNo]["family"] = "";
                            $item_attr[$this->attrId][$this->attrNo]["given"] = "";
                            $item_attr[$this->attrId][$this->attrNo]["family_ruby"] = "";
                            $item_attr[$this->attrId][$this->attrNo]["given_ruby"] = "";
                            $item_attr[$this->attrId][$this->attrNo]["author_id"] = 0;
                            $item_attr[$this->attrId][$this->attrNo]["email"] = "";
                            $item_attr[$this->attrId][$this->attrNo]["external_author_id"] = array(array('prefix_id'=>'', 'suffix'=>''));
                            // Set fill data to session
                            $this->Session->setParameter("item_attr", $item_attr);
                        }
                    }else{
                        // get name
                        $this->fillAuthorData($author_id_suffix,$display_lang_type);
                    }
                // Add Check name authority 2011/01/17 H.Goto --end--
                }
                return 'success';
            }
            $this->surName = urldecode($this->surName);
            $this->surName = trim(mb_convert_encoding($this->surName, "UTF-8", "ASCII,JIS,UTF-8,EUC-JP,SJIS"));
            $this->givenName = urldecode($this->givenName);
            $this->givenName = trim(mb_convert_encoding($this->givenName, "UTF-8", "ASCII,JIS,UTF-8,EUC-JP,SJIS"));
            $this->surNameRuby = urldecode($this->surNameRuby);
            $this->surNameRuby = trim(mb_convert_encoding($this->surNameRuby, "UTF-8", "ASCII,JIS,UTF-8,EUC-JP,SJIS"));
            $this->givenNameRuby = urldecode($this->givenNameRuby);
            $this->givenNameRuby = trim(mb_convert_encoding($this->givenNameRuby, "UTF-8", "ASCII,JIS,UTF-8,EUC-JP,SJIS"));
            $this->emailAddress = urldecode($this->emailAddress);
            $this->emailAddress = trim(mb_convert_encoding($this->emailAddress, "UTF-8", "ASCII,JIS,UTF-8,EUC-JP,SJIS"));
            // Add e-person 2013/11/19 R.Matsuura --start--
            $this->externalAuthorID = urldecode($this->externalAuthorID);
            $this->externalAuthorID = trim(mb_convert_encoding($this->externalAuthorID, "UTF-8", "ASCII,JIS,UTF-8,EUC-JP,SJIS"));
            // Add e-person 2013/11/19 R.Matsuura --end--
            $item_attr_type = $this->Session->getParameter("item_attr_type");
            $display_lang_type = "";
            if(count($item_attr_type) > 0)
            {
                $display_lang_type = $item_attr_type[$this->attrId]["display_lang_type"];
            }
            $str = "";
            
            $authorId = $NameAuthority->getSuggestAuthorBySuffix($this->externalAuthorID);
            if((strlen($this->surName.$this->givenName.$this->surNameRuby.$this->givenNameRuby.$this->emailAddress) > 0) || (strlen($this->externalAuthorID) > 0 && count($authorId) > 0)){
                // Get suggest data
                $result = $NameAuthority->searchSuggestData(
                                                            $this->surName,
                                                            $this->givenName,
                                                            $this->surNameRuby,
                                                            $this->givenNameRuby,
                                                            $this->emailAddress,
                                                            $this->externalAuthorID,
                                                            $display_lang_type
                                                        );
                if($result===false){
                    $error_msg = $this->Db->ErrorMsg();
                    return false;
                }
                if(count($result)!=0){
                    $str_candidate = '"candidate":[';
                    $str_authorList = '"authorList":[';
                    $prefixsufix = "";
                    for($ii=0;$ii<count($result);$ii++){
                        
                        // Add 2011/04/25 H.Ito --start--
                        $resultID = $NameAuthority->getExternalAuthorIdData($result[$ii]['author_id']);
                        $prefixsufix = $this->fillSuggestPrefixIDtoString($resultID);
                        // Add 2011/04/25 H.Ito --end--
                        
                        // Fix fill data sanitizing 2011/07/05 Y.Nakao --start--
                        $result[$ii]['family'] = $this->escapeJSON($result[$ii]['family']);
                        $result[$ii]['name'] = $this->escapeJSON($result[$ii]['name']);
                        $result[$ii]['family_ruby'] = $this->escapeJSON($result[$ii]['family_ruby']);
                        $result[$ii]['e_mail_address'] = $this->escapeJSON($result[$ii]['suffix']);
                        $resultID = $this->escapeJSON($resultID);
                        $prefixsufix = $this->escapeJSON($prefixsufix);
                        // Fix fill data sanitizing 2011/07/05 Y.Nakao --end--
                        
                        if($ii!=0){
                            $str_candidate .= ',';
                            $str_authorList .= ',';
                        }
                        $str_candidate .= '"'.
                                          $result[$ii]['family'].' '.
                                          $result[$ii]['name'].' '.
                                          $result[$ii]['family_ruby'].' '.
                                          $result[$ii]['name_ruby'].' '.
                                          $result[$ii]['e_mail_address'].'"';
                        $str_authorList .= '{'.
                                           '"surName":"'.$result[$ii]['family'].'", '.
                                           '"givenName":"'.$result[$ii]['name'].'", '.
                                           '"surNameRuby":"'.$result[$ii]['family_ruby'].'", '.
                                           '"givenNameRuby":"'.$result[$ii]['name_ruby'].'", '.
                                           '"emailAddress":"'.$result[$ii]['e_mail_address'].'", '.
                        // Add 2011/04/25 H.Ito --start--
                                           '"prefixsufix":"'.$prefixsufix.'", '.
                        // Add 2011/04/25 H.Ito --end--
                                           //'"fillStr":"'.$result[$ii]['family'].'|'.$result[$ii]['name'].'|'.$result[$ii]['family_ruby'].'|'.$result[$ii]['name_ruby'].'|'.$result[$ii]['e_mail_address'].'"}';
                                           '"fillStr":"{'.
                                                '\"family\":\"'.$result[$ii]['family'].'\",'.
                                                '\"name\":\"'.$result[$ii]['name'].'\",'.
                                                '\"family_ruby\":\"'.$result[$ii]['family_ruby'].'\",'.
                                                '\"name_ruby\":\"'.$result[$ii]['name_ruby'].'\",'.
                                                '\"e_mail_address\":\"'.$result[$ii]['e_mail_address'].'\",'.
                                                '\"author_id\":\"'.$result[$ii]['author_id'].'\",'.
                                                '\"attrId\":\"'.$this->attrId.'\",'.
                                                '\"attrNo\":\"'.$this->attrNo.'\"}"'.
                                           '}';
                    }
                    $str_candidate .= ']';
                    $str_authorList .= ']';
                    
                    $str = '{'.$str_candidate.','.$str_authorList.'}';
                    
                    // exit action
                    $result = $this->exitAction();
                    if ( $result === false ) {
                        $exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );
                        throw $exception;
                    }
                }
            }
            echo $str;
            exit();
        } catch ( RepositoryException $Exception) {
            //エラーログ出力
            $this->logFile(
                "SampleAction",                 //クラス名
                "execute",                      //メソッド名
            $Exception->getCode(),          //ログID
            $Exception->getMessage(),       //主メッセージ
            $Exception->getDetailMsg() );   //詳細メッセージ         
            //アクション終了処理
            $this->exitAction();                   //トランザクションが失敗していればROLLBACKされる        
            //異常終了
            $this->Session->setParameter("error_msg", $user_error_msg);
            return "error";
        }
    }
    
    // Add 2011/04/25 H.Ito --start--
    /**
     * Fill suggest Prefix Suffix get to string
     *
     * @param string $resultID
     */
    function fillSuggestPrefixIDtoString($resultID){
        $outStr = "";
        if($resultID===false){
            return "";
        }
        if(count($resultID)!=0){
            for($ii=0;$ii<count($resultID);$ii++){
                if(strlen($outStr) > 0){
                    $outStr .= ',';
                }
                $outStr .= $resultID[$ii]['prefix_name'].':'.$resultID[$ii]['suffix'];
            }
            return $outStr;
        }
        return "";
    }
    // Add 2011/04/25 H.Ito --end--
    
    
    /**
     * Fill suggest data to session
     *
     * @param string $fillData
     */
    function fillSuggestData($fillData){
        // Decode fill data
        $json = new Services_JSON();
        $decoded = $json->decode($fillData);
        
        $NameAuthority = new NameAuthority($this->Session, $this->Db);
        $external_author_id = $NameAuthority->getExternalAuthorIdPrefixAndSuffix($decoded->author_id);
        
        // Get item_attr by Session
        $item_attr = $this->Session->getParameter("item_attr");
        
        // Fill data
        $item_attr[$decoded->attrId][$decoded->attrNo]["family"] = $decoded->family;
        $item_attr[$decoded->attrId][$decoded->attrNo]["given"] = $decoded->name;
        $item_attr[$decoded->attrId][$decoded->attrNo]["family_ruby"] = $decoded->family_ruby;
        $item_attr[$decoded->attrId][$decoded->attrNo]["given_ruby"] = $decoded->name_ruby;
        $item_attr[$decoded->attrId][$decoded->attrNo]["email"] = $decoded->e_mail_address;
        $item_attr[$decoded->attrId][$decoded->attrNo]["author_id"] = $decoded->author_id;
        $item_attr[$decoded->attrId][$decoded->attrNo]["external_author_id"] = $external_author_id;
        
        // Set fill data to session
        $this->Session->setParameter("item_attr", $item_attr);
    }
    
    /**
     * Save form data to session
     *
     */
    function saveFormData(){
        // Get session data
        $item_type_all = $this->Session->getParameter("item_type_all");
        $item_attr_type = $this->Session->getParameter("item_attr_type");
        $item_num_cand = $this->Session->getParameter("item_num_cand");
        $option_data = $this->Session->getParameter("option_data");
        $item_num_attr = $this->Session->getParameter("item_num_attr");
        $item_attr_old = $this->Session->getParameter("item_attr");
        $item_attr = array();
        
        // counter
        $cnt_text = 0;      // text
        $cnt_textarea = 0;  // textarea
        $cnt_name = 0;      // name
        $cnt_author_id = 0; // name author_id
        $cnt_link = 0;      // link
        $cnt_select = 0;    // select
        $cnt_checkbox = 0;  // checkbox
        $cnt_radio = 0;     // radio
        $cnt_biblio = 0;    // biblio_info
        $cnt_date = 0;      // date
        
        // ------------------------------------------------------------
        // Save to session
        // ------------------------------------------------------------     
        
        // base_attr
        $this->Session->setParameter("base_attr", array( 
            "title" => ($this->base_attr[0]==' ') ? '' : $this->base_attr[0],
            "title_english" => ($this->base_attr[1]==' ') ? '' : $this->base_attr[1],
            "language" => $this->base_attr[2])
        );      
        // item_pub_date
        $this->Session->setParameter("item_pub_date", array(
                "year" => ($this->item_pub_date_year == ' ') ? '' : $this->item_pub_date_year,
                "month" => $this->item_pub_date_month,
                "day" => $this->item_pub_date_day
            )
        );
        
        // keyword
        $keywords = split("[|]", $this->item_keyword);
        $keywords_en = split("[|]", $this->item_keyword_english);
        $item_keyword_new = '';
        $item_keyword_en_new = '';
        for($ii=0; $ii<count($keywords); $ii++) {
            $keywords[$ii] = trim($keywords[$ii]);
            $item_keyword_new = $item_keyword_new . $keywords[$ii];
            if($ii != count($keywords)-1) {
                $item_keyword_new = $item_keyword_new . '|';
            }               
        }
        for($ii=0; $ii<count($keywords_en); $ii++) {
            $keywords_en[$ii] = trim($keywords_en[$ii]);
            $item_keyword_en_new = $item_keyword_en_new . $keywords_en[$ii];
            if($ii != count($keywords_en)-1) {
                $item_keyword_en_new = $item_keyword_en_new . '|';
            }               
        }
        $item_keyword = $item_keyword_new;
        $item_keyword_english = $item_keyword_en_new;
        $this->Session->setParameter("item_keyword", $item_keyword);
        $this->Session->setParameter("item_keyword_english", $item_keyword_english);
        
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
        
        // item_attr
        for($ii=0; $ii<count($item_attr_type); $ii++) {
            $attr_elm = array();
            $nCnt_attr = 0;
            $nCnt_attr_flg = 0;
            for($jj=0; $jj<$item_num_attr[$ii]; $jj++) {
                $metadata = array();
                $metadata["attribute_id"] = $item_attr_type[$ii]["attribute_id"];
                $metadata["item_type_id"] = $item_type_all["item_type_id"];
                $metadata["input_type"] = $item_attr_type[$ii]['input_type'];
                switch($item_attr_type[$ii]['input_type']) {
                case 'text':
                    $metadata["attribute_no"] = $jj+1;
                    if($this->item_attr_text[$cnt_text]==' ') {
                        array_push($attr_elm, '');
                        $metadata["attribute_value"] = '';
                    } else {
                        array_push($attr_elm, $this->item_attr_text[$cnt_text]);
                        $metadata["attribute_value"] = $this->item_attr_text[$cnt_text];
                    }
                    $cnt_text++;
                    break;
                case 'link':
                    $metadata["attribute_no"] = $jj+1;
                    // URL
                    if($this->item_attr_link[$cnt_link]==' ') {
                        $link_url = "";
                    } else {
                        $link_url = str_replace("|", "", $this->item_attr_link[$cnt_link]);
                    }
                    // link_name
                    if($this->item_attr_link_name[$cnt_link]==' ') {
                        $link_name = "";
                    } else {
                        $link_name = str_replace("|", "", $this->item_attr_link_name[$cnt_link]);
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
                    $metadata["personal_name_no"] = $jj+1;
                    $family = '';
                    $given = '';
                    $family_ruby = '';
                    $given_ruby = '';
                    $email = '';
                    $author_id = '';
                    $language = $item_attr_type[$ii]['display_lang_type'];
                    $external_author_id = array();
                    
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
                        if($this->item_attr_name_author_id_prefix[$kk+$cnt_author_id]!=0) {
                            $external_author_id_prefix = $this->item_attr_name_author_id_prefix[$kk+$cnt_author_id];
                        }
                        if($this->item_attr_name_author_id_suffix[$kk+$cnt_author_id]!=' ') {
                            $external_author_id_suffix = $this->item_attr_name_author_id_suffix[$kk+$cnt_author_id];
                        }
                        array_push($external_author_id, array('prefix_id'=>$external_author_id_prefix, 'suffix'=>$external_author_id_suffix));
                    }
                    $cnt_author_id = $cnt_author_id + $kk;
                    $author_id = intval($item_attr_old[$ii][$jj]["author_id"]);
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
                    $metadata["family"] = $family;
                    $metadata["name"] = $given;
                    $metadata["family_ruby"] = $family_ruby;
                    $metadata["name_ruby"] = $given_ruby;
                    $metadata["e_mail_address"] = $email;
                    $metadata["author_id"] = $author_id;
                    $metadata["language"] = $language;
                    $metadata["external_author_id"] = $external_author_id;
                    $cnt_name++;
                    break;
                case 'textarea':
                    $metadata["attribute_no"] = $jj+1;
                    if($this->item_attr_textarea[$cnt_textarea]==' ') {
                        array_push($attr_elm, '');
                        $metadata["attribute_value"] = '';
                    } else {
                        array_push($attr_elm, $this->item_attr_textarea[$cnt_textarea]);
                        $metadata["attribute_value"] = $this->item_attr_textarea[$cnt_textarea];
                    }
                    $cnt_textarea++;
                    break;
                case 'select':
                    $metadata["attribute_no"] = $jj+1;
                    if($this->item_attr_select[$cnt_select]=='') {
                        array_push($attr_elm, '');
                        $metadata["attribute_value"] = '';
                    } else {
                        array_push($attr_elm, $this->item_attr_select[$cnt_select]);
                        $metadata["attribute_value"] = $this->item_attr_select[$cnt_select];
                    }
                    $cnt_select++;
                    break;
                case 'checkbox':
                    $metadata["attribute_no"] = array();
                    $metadata["attribute_value"] = array();
                    for($kk=0; $kk<count($option_data[$ii]); $kk++){
                        array_push($attr_elm, $this->item_attr_checkbox[$cnt_checkbox]);    // チェックON
                        if($this->item_attr_checkbox[$cnt_checkbox] == 1){
                            $metadata["attribute_no"] = $jj + $kk + 1;
                            $metadata["attribute_value"] = $option_data[$ii][$kk];
                        }
                        $cnt_checkbox++;
                    }
                    break;
                case 'radio':
                    $metadata["attribute_no"] = $jj+1;
                    array_push($attr_elm, $this->item_attr_radio[$cnt_radio]);
                    $metadata["attribute_value"] = $option_data[$ii][$this->item_attr_radio[$cnt_radio]];
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
                    $metadata["biblio_no"] = $jj+1;
                    $metadata["biblio_name"] = $biblio_name;
                    $metadata["biblio_name_english"] = $biblio_name_english;
                    $metadata["volume"] = $volume;
                    $metadata["issue"] = $issue;
                    $metadata["start_page"] = $spage;
                    $metadata["end_page"] = $epage;
                    $metadata["date_of_issued"] = $dateofissued;
                    $cnt_biblio++;
                    break;
                case 'date':
                    $date_year = '';
                    $date_month = '';
                    $date_day = '';
                    $date = '';
                    if($this->item_attr_date_year[$cnt_date]!=' ') {
                        $date_year = trim($this->item_attr_date_year[$cnt_date]);
                    }
                    if($this->item_attr_date_month[$cnt_date]!=' ') {
                        $date_month = $this->item_attr_date_month[$cnt_date];
                    }
                    if($this->item_attr_date_day[$cnt_date]!=' ') {
                        $date_day = $this->item_attr_date_day[$cnt_date];
                    }
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
                    $metadata["attribute_no"] = $jj+1;
                    $metadata["attribute_value"] = $date;
                    $cnt_date++;
                    break;
                case 'heading':
                    $metadata["attribute_no"] = $jj+1;
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
                    $metadata["attribute_value"] = $heading."|".$heading_en."|".$heading_sub."|".$heading_sub_en; 
                    array_push($attr_elm, $metadata["attribute_value"]);
                    break;
                default :
                    array_push($attr_elm, $item_attr_old[$ii][$jj]);
                    break;
                }
            }
            array_push($item_attr, $attr_elm);      // 1メタデータ分のユーザ入力値をセット
        }
        $this->Session->setParameter("item_attr", $item_attr);
        return true;
    }
    
    /**
     * Get author fill data by external author ID
     *
     */
    function getAuthorFillData($prefix, $suffix ,$display_lang_type){
        if($prefix == "1"){
            // CiNII ID
            $reqUrl = $this->ciniiUrl."nrid/".$suffix.".rdf";
            //get XML
            $vals = $this->getXml($reqUrl);
            if ($vals == false){
                return false;
            }
            // get ID
            $name = $this->getCiNiiId($vals,$display_lang_type);
            return $name;
        } else if($prefix == "2"){
            // resolverID
            $reqUrl = $this->resolverUrl."opensearch?q6=".$suffix;
            //get XML
            $vals = $this->getXml($reqUrl);
            if ($vals == false){
                return false;
            }
            // get resolver ID
            $name = $this->getResolver($vals,$display_lang_type);
            return $name;
        } else if($prefix == "3"){
            // ResearcherNo
            $reqUrl = $this->resolverUrl."opensearch?q5=".$suffix;
            //get XML
            $vals = $this->getXml($reqUrl);
            if ($vals == false){
                return false;
            }
            // get resolver No
            $name = $this->getResolver($vals,$display_lang_type);
            return $name;
        }
    }
     /**
     * Get Xml
     */
    function getXml($reqUrl){
        $option = array( 
            "timeout" => "10",
            "allowRedirects" => true,
            "maxRedirects" => 3, 
        );
        // Modfy proxy 2011/12/06 Y.Nakao --start--
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
        // Modfy proxy 2011/12/06 Y.Nakao --end--
        $http = new HTTP_Request($reqUrl, $option);
        // setting HTTP header
        $http->addHeader("User-Agent", $_SERVER['HTTP_USER_AGENT']);
        $response = $http->sendRequest(); 
        if (!PEAR::isError($response)) { 
            $resCode = $http->getResponseCode();        // get ResponseCode(200etc.)
            $resHeader = $http->getResponseHeader();    // get ResponseHeader
            $resBody = $http->getResponseBody();        // get ResponseBody
            $resCookies = $http->getResponseCookies();  // get Cookie
        }
        /////////////////////////////
        // parse response XML
        /////////////////////////////
        $response_xml = $resBody; 
        
        // add get lang 2011/02/21 H.Goto --start--
        $this->smartyAssign = $this->Session->getParameter("smartyAssign");
        // add get lang 2011/02/21 H.Goto --end--
        try{
            $xml_parser = xml_parser_create();
            $rtn = xml_parse_into_struct( $xml_parser, $response_xml, $vals );
            if($rtn == 0){
                $this->error_msg = $this->smartyAssign->getLang("repository_item_fill_data_no_data_error");
                return false;
            }
            xml_parser_free($xml_parser);
        } catch(Exception $ex){
            $this->error_msg = $this->smartyAssign->getLang("repository_item_fill_data_no_data_error");
            return false;
        }
        return $vals;
        
    }
    
    /**
     * Get CiNII ID other site
     * @param vals  XML
     * @param display_lang_type  mapping's language setting
     */
    function getCiNiiId($vals,$display_lang_type){
        // get item's language type
        $lang_sess = $this->Session->getParameter("base_attr");
        $cnt = count($vals);
        // get name for XML
        foreach($vals as $tmp){
            if($tmp["tag"] == "FOAF:PERSON" && $tmp["type"] == "open"){
                $person_open_flg = "1";
            }else if($tmp["tag"] == "FOAF:PERSON" && $tmp["type"] == "close"){
                $person_open_flg = "0";
            }
            if($tmp["tag"]=="FOAF:NAME" && $person_open_flg == "1"){
                if($tmp["attributes"]["XML:LANG"] == "en"){
                    $name_en = explode(" ",$tmp["value"]);
                }else{
                    $name = explode(" ",$tmp["value"]);
                }
            }
        }
        //language flag
        $lang_flg = 1; // 1 englosh
        if($display_lang_type == "japanese"){
            $lang_flg = 0;
        }elseif($display_lang_type == "english"){
            $name = $name_en;
        }else{
            if($lang_sess["language"] = "ja"){
                $lang_flg = 0;
            }else{
                $name = $name_en;
            }
        }
        if($name == ""){
            $name = $name_en;
        }
        $cnt = count($name);
        $firstname = NULL;
        
        for ($ii = 0;$ii < $cnt; $ii++){
            if($ii === 0){
                $familyname = $name[$ii];
            }else{
                if($firstname == NULL){
                    $firstname = $name[$ii];
                }else{
                    $firstname = $firstname." ".$name[$ii];
                }
            }
        }
        $name["familyname"] = $familyname;
        $name["firstname"] = $firstname;
        return $name;
    }
    /**
     * Get Researcher's ResolverID
     * and Get Department laboratory expense researcher No
     * @param vals XML
     * @param lang mapping's language setting
     */
    function getResolver($vals,$lang){
        // get item's language type
        $lang_sess = $this->Session->getParameter("base_attr");
        $item_open_flg = "0";
        // get name for XML
        foreach($vals as $tmp){
            if($tmp["tag"] == "ITEM" && $tmp["type"] == "open"){
                $item_open_flg = "1";
            }else if ($tmp["tag"] == "ITEM" && $tmp["type"] == "close"){
                $item_open_flg = "0";
            }
            if($tmp["tag"] == "TITLE" && $item_open_flg == "1"){
                $name_tmp = explode(" ",$tmp["value"]);
                $name = array();
                if (preg_match("/ \| (.*)\([0-9]+\)/", $tmp["value"])){
                    // It doesn't exist japanese
                    preg_match("/ \| (.*)\([0-9]+\)/", $tmp["value"], $name);
                    break;
                }else{
                    // exist japanese
                    preg_match("/ \- (.*)\([0-9]+\)/", $tmp["value"], $name);
                    break;
                }
            }
        }
        if($name[0] == ""){
            return false;
        }
        // get name
        if($lang == "japanese"){
            // japanese
            $fillname = $this->resolverJpn($name);
        }else if($lang == "english"){
            // english
            $fillname = $this->resolverEng($name);
        }else{
            if($lang_sess["language"] = "ja"){
                // japanese
                $fillname = $this->resolverJpn($name);
            }else{
                // others
                $fillname = $this->resolverEng($name);
            }
        }
        return $fillname;
    }
    /**
     * get name for weko DB
     */
    function get_name_auth($author_id_suffix,$lang){
        $query = "SELECT * ".
         "FROM ". DATABASE_PREFIX ."repository_name_authority ".
         "WHERE author_id = ? ".
         "AND language = ? ";
        $params = null;
        // $queryの?を置き換える配列
        $params[] = $author_id_suffix;    // author_id
        $params[] = $lang;                   // language_type
        // Execution SELECT
        $name_authority = $this->Db->execute($query, $params);
        
        $firstname = $name_authority[0]["name"];
        $familyname = $name_authority[0]["family"];
        $firstname_ruby = $name_authority[0]["name_ruby"];
        $familyname_ruby = $name_authority[0]["family_ruby"];
    }
    
    /**
     * push wekoDB's data is session
     *
     * @param string $fillData
     */
    function fillAuthorData($fillNameDataArray,$lang){
        
        // Get item_attr by Session
        $item_attr = $this->Session->getParameter("item_attr");
        
        $fillNameData = null;
        $fillNameData_none = null;
        $fillNameData_othor = null;
        
        //search same language mapping setting and DB setting
        $cnt = count($fillNameDataArray);
        for ($ii = 0; $ii < $cnt; $ii++){
            if($fillNameDataArray[$ii]["language"] == $lang){
                $fillNameData = $fillNameDataArray[$ii];
                break;
            } else if($fillNameDataArray[$ii]["language"] == ""){
                if($fillNameData_none == null){
                    $fillNameData_none = $fillNameDataArray[$ii];
                }
            } else {
                if($fillNameData_othor == null){
                    $fillNameData_othor = $fillNameDataArray[$ii];
                }
            }
        }
        
        if($fillNameData != null){
            // It doesn't do at all.
        }else if ($fillNameData_none != null){
            $fillNameData = $fillNameData_none;
        }else{
            $fillNameData = $fillNameData_othor;
        }
        
        $fillExternalAuthorId = $fillNameData["external_author_id"];
        $fillNameData["external_author_id"] = array();
        $fillNameData["e_mail_address"] = "";
        for($idCnt = 0; $idCnt < count($fillExternalAuthorId); $idCnt++)
        {
            if($fillExternalAuthorId[$idCnt]["prefix_id"] == 0)
            {
                $fillNameData["e_mail_address"] = $fillExternalAuthorId[$idCnt]["suffix"];
            }
            else
            {
                array_push($fillNameData["external_author_id"], $fillExternalAuthorId[$idCnt]);
            }
        }
        if(!isset($fillNameData["external_author_id"]) || count($fillNameData["external_author_id"]) < 1)
        {
            $fillNameData["external_author_id"] = array();
            $tmpArray = array("prefix_id" => "", "suffix" => "");
            array_push($fillNameData["external_author_id"], $tmpArray);
        }
        
        $item_attr[$this->attrId][$this->attrNo]["family"] = $fillNameData["family"];
        $item_attr[$this->attrId][$this->attrNo]["given"] = $fillNameData["name"];
        $item_attr[$this->attrId][$this->attrNo]["family_ruby"] = $fillNameData["family_ruby"];
        $item_attr[$this->attrId][$this->attrNo]["given_ruby"] = $fillNameData["name_ruby"];
        $item_attr[$this->attrId][$this->attrNo]["email"] = $fillNameData["e_mail_address"];
        $item_attr[$this->attrId][$this->attrNo]["author_id"] = $fillNameData["author_id"];
        $item_attr[$this->attrId][$this->attrNo]["external_author_id"] = $fillNameData["external_author_id"];
        
        // Set fill data to session
        $this->Session->setParameter("item_attr", $item_attr);
    }
    /**
     * get resolver japanese name
     *
     * @param 
     */
    function resolverJpn($name){
        $name_knj = explode(" ",$name[1]);
        $name_kana = explode(" ",$name[2]);
        $cnt_knj = count($name_knj);
        $cnt_kana = count($name_kana);

        for ($ii = 0;$ii < $cnt_knj; $ii++){
            if($ii === 0){
                $familyname = $name_knj[$ii];
            }else{
                $firstname = $firstname." ".$name_knj[$ii];
                $firstname = ltrim($firstname);
            }
        }
        // Furigana name
        for ($ii = 0;$ii < $cnt_kana; $ii++){
            if($ii === 0){
                $familyname_ruby = $name_kana[$ii];
                $familyname_ruby = str_replace("(","",$familyname_ruby);
            }else{
                $firstname_ruby = $firstname_ruby." ".$name_kana[$ii];
                $firstname_ruby = str_replace(")","",$firstname_ruby);
                $firstname_ruby = ltrim($firstname_ruby);
            }
        }
        
            $fillname["familyname"] = $familyname;
            $fillname["firstname"] = $firstname;
            $fillname["familyname_ruby"] = $familyname_ruby;
            $fillname["firstname_ruby"] = $firstname_ruby;
        return $fillname;
    }
    /**
     * get resolver english name
     *
     * @param string
     */
    function resolverEng($name){
        $cnt = count($name);
        $alpha_flg = ctype_alpha(str_replace(" ","",$name[1]));
        $las_alpha_flg = ctype_alpha(str_replace(" ","",$name[$cnt-1]));
        if($alpha_flg == true){
            $name_eng = explode(" ",$name[1]);
        }else if($las_alpha_flg == false){
            $name_eng = explode(" ",$name[1]);
        }else{
            $name_eng = explode(" ",$name[$cnt-1]);
        }
        $cnt_eng = count($name_eng);
        for($ii = 0; $ii < $cnt_eng; $ii++){
            if ($ii === 0){
                $firstname = str_replace("(","",$name_eng[$ii]);
            }else{
                $familyname = $familyname." ".$name_eng[$ii];
                $familyname = str_replace(")","",$familyname);
                $familyname = ltrim($familyname);
                
            }
        }
        $fillname["familyname"] = $familyname;
        $fillname["firstname"] = $firstname;
        return $fillname;
    }
    
    // Fix fill data sanitizing 2011/07/05 Y.Nakao --start--
    /**
     * escape JSON
     *
     * @param array $index_data
     */
    function escapeJSON($str, $lineFlg=false){
        
        $str = str_replace("\\", "\\\\", $str);
        $str = str_replace('[', '\[', $str);
        $str = str_replace(']', '\]', $str);
        $str = str_replace('"', '\"', $str);
        if($lineFlg){
            $str = str_replace("\r\n", "\n", $str);
            $str = str_replace("\n", "\\n", $str);
        }
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
    // Fix fill data sanitizing 2011/07/05 Y.Nakao --end--
}
?>
