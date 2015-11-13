<?php
// --------------------------------------------------------------------
//
// $Id: Fillcontributor.class.php 57381 2015-08-31 00:32:36Z tatsuya_koyasu $
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

/**
 * Search user from NC2 by form input data.
 *
 */
class Repository_Action_Main_Item_Fillcontributor extends RepositoryAction
{
    /**
     * Conponent : Session
     *
     * @var Session
     */
    public $Session = null;
    
    /**
     * Conponent : Db
     *
     * @var Db
     */
    public $Db = null;
    
    /**
     * Request parameter : handle
     *
     * @var string
     */
    public $handle = null;
    
    /**
     * Request parameter : name
     *
     * @var string
     */
    public $name = null;
    
    /**
     * Request parameter : email
     *
     * @var string
     */
    public $email = null;
    
    /**
     * Request parameter : mode
     *
     * @var string
     */
    public $mode = null;
    
    /**
     * Form data : base info
     *
     * @var array
     */
    public $base_attr = null;
    
    /**
     * Form data : pub_date(year)
     *
     * @var string
     */
    public $item_pub_date_year = null;
    
    /**
     * Form data : pub_date(month)
     *
     * @var int
     */
    public $item_pub_date_month = null;
    
    /**
     * Form data : pub_date(day)
     *
     * @var int
     */
    public $item_pub_date_day = null;
    
    /**
     * Form data : keyword
     *
     * @var string
     */
    public $item_keyword = null;
    
    /**
     * Form data : keyword_english
     *
     * @var string
     */
    public $item_keyword_english = null;
    
    /**
     * Form data : text
     *
     * @var array
     */
    public $item_attr_text = null;
    
    /**
     * Form data : textarea
     *
     * @var array
     */
    public $item_attr_textarea = null;
    
    /**
     * Form data : checkbox
     *
     * @var array
     */
    public $item_attr_checkbox = null;
    
    /**
     * Form data : name(surname)
     *
     * @var array
     */
    public $item_attr_name_family = null;
    
    /**
     * Form data : name(given name)
     *
     * @var array
     */
    public $item_attr_name_given = null;
    
    /**
     * Form data : name(surname ruby)
     *
     * @var array
     */
    public $item_attr_name_family_ruby = null;
    
    /**
     * Form data : name(given name ruby)
     *
     * @var array
     */
    public $item_attr_name_given_ruby = null;
    
    /**
     * Form data : name(e-mail)
     *
     * @var array
     */
    public $item_attr_name_email = null;
    
    /**
     * Form data : name(authorID prefix)
     *
     * @var array
     */
    public $item_attr_name_author_id_prefix = null;
    
    /**
     * Form data : name(authorID suffix)
     *
     * @var array
     */
    public $item_attr_name_author_id_suffix = null;
    
    /**
     * Form data : select
     *
     * @var array
     */
    public $item_attr_select = null;
    
    /**
     * Form data : link(value)
     *
     * @var array
     */
    public $item_attr_link = null;
    
    /**
     * Form data : link(name)
     *
     * @var array
     */
    public $item_attr_link_name = null;
    
    /**
     * Form data : radio
     *
     * @var array
     */
    public $item_attr_radio = null;
    
    /**
     * Form data : biblio_info(title)
     *
     * @var array
     */
    public $item_attr_biblio_name = null;
    
    /**
     * Form data : biblio_info(title_english)
     *
     * @var array
     */
    public $item_attr_biblio_name_english = null;
    
    /**
     * Form data : biblio_info(volume)
     *
     * @var array
     */
    public $item_attr_biblio_volume = null;
    
    /**
     * Form data : biblio_info(issue)
     *
     * @var array
     */
    public $item_attr_biblio_issue = null;
    
    /**
     * Form data : biblio_info(start_page)
     *
     * @var array
     */
    public $item_attr_biblio_spage = null;
    
    /**
     * Form data : biblio_info(end_page)
     *
     * @var array
     */
    public $item_attr_biblio_epage = null;
    
    /**
     * Form data : biblio_info(year)
     *
     * @var array
     */
    public $item_attr_biblio_dateofissued_year = null;
    
    /**
     * Form data : biblio_info(month)
     *
     * @var array
     */
    public $item_attr_biblio_dateofissued_month = null;
    
    /**
     * Form data : biblio_info(day)
     *
     * @var array
     */
    public $item_attr_biblio_dateofissued_day = null;
    
    /**
     * Form data : date(year)
     *
     * @var array
     */
    public $item_attr_date_year = null;
    
    /**
     * Form data : date(month)
     *
     * @var array
     */
    public $item_attr_date_month = null;
    
    /**
     * Form data : date(day)
     *
     * @var array
     */
    public $item_attr_date_day = null;
    
    /**
     * Form data : heading
     *
     * @var array
     */
    public $item_attr_heading = null;
    
    /**
     * Form data : heading(english)
     *
     * @var array
     */
    public $item_attr_heading_en = null;
    
    /**
     * Form data : subheading
     *
     * @var array
     */
    public $item_attr_heading_sub = null;
    
    /**
     * Form data : subheading(english)
     *
     * @var array
     */
    public $item_attr_heading_sub_en = null;
    
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
            
            if($this->mode == "suggest"){
                if(strlen($this->handle.$this->name.$this->email) > 0){
                    // Fill suggest data
                    $this->saveFormData();
                    $this->fillSuggestData($this->handle, $this->name, $this->email);
                    return 'success';
                }
            }
            $this->handle = urldecode($this->handle);
            $this->handle = trim(mb_convert_encoding($this->handle, "UTF-8", "ASCII,JIS,UTF-8,EUC-JP,SJIS"));
            $this->name = urldecode($this->name);
            $this->name = trim(mb_convert_encoding($this->name, "UTF-8", "ASCII,JIS,UTF-8,EUC-JP,SJIS"));
            $this->email = urldecode($this->email);
            $this->email = trim(mb_convert_encoding($this->email, "UTF-8", "ASCII,JIS,UTF-8,EUC-JP,SJIS"));
            $str = "";
            if(strlen(trim($this->handle.$this->name.$this->email)) > 0){
                // Get suggest data
                $result = $this->searchSuggestData($this->handle, $this->name, $this->email);
                if($result===false){
                    return false;
                }
                if(count($result)!=0){
                    $str_candidate = '"candidate":[';
                    $str_contributor = '"contributor":[';
                    for($ii=0;$ii<count($result);$ii++){
                        $result[$ii][RepositoryConst::ITEM_CONTRIBUTOR_HANDLE]
                            = $this->escapeJSON($result[$ii][RepositoryConst::ITEM_CONTRIBUTOR_HANDLE]);
                        $result[$ii][RepositoryConst::ITEM_CONTRIBUTOR_NAME]
                            = $this->escapeJSON($result[$ii][RepositoryConst::ITEM_CONTRIBUTOR_NAME]);
                        $result[$ii][RepositoryConst::ITEM_CONTRIBUTOR_EMAIL]
                            = $this->escapeJSON($result[$ii][RepositoryConst::ITEM_CONTRIBUTOR_EMAIL]);
                        
                        if($ii != 0){
                            $str_candidate .= ',';
                            $str_contributor .= ',';
                        }
                        
                        $str_candidate .= '"'.
                                          $result[$ii][RepositoryConst::ITEM_CONTRIBUTOR_HANDLE].' '.
                                          $result[$ii][RepositoryConst::ITEM_CONTRIBUTOR_NAME].' '.
                                          $result[$ii][RepositoryConst::ITEM_CONTRIBUTOR_EMAIL].'"';
                        $str_contributor .= '{'.
                                           '"'.RepositoryConst::ITEM_CONTRIBUTOR_HANDLE.'":"'.$result[$ii][RepositoryConst::ITEM_CONTRIBUTOR_HANDLE].'", '.
                                           '"'.RepositoryConst::ITEM_CONTRIBUTOR_NAME.'":"'.$result[$ii][RepositoryConst::ITEM_CONTRIBUTOR_NAME].'", '.
                                           '"'.RepositoryConst::ITEM_CONTRIBUTOR_EMAIL.'":"'.$result[$ii][RepositoryConst::ITEM_CONTRIBUTOR_EMAIL].'"'.
                                           '}';
                    }
                    $str_candidate .= ']';
                    $str_contributor .= ']';
                    
                    $str = '{'.$str_candidate.','.$str_contributor.'}';
                    
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
    
    /**
     * Fill suggest data to session
     *
     * @param string $fillData
     */
    function fillSuggestData($fillData){
        // Fill data
        $item_contributor = array(
                    RepositoryConst::ITEM_CONTRIBUTOR_HANDLE => $this->handle,
                    RepositoryConst::ITEM_CONTRIBUTOR_NAME => $this->name,
                    RepositoryConst::ITEM_CONTRIBUTOR_EMAIL => $this->email);
        
        // Set fill data to session
        $this->Session->setParameter(RepositoryConst::SESSION_PARAM_ITEM_CONTRIBUTOR, $item_contributor);
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
        $this->item_contributor_handle = ($this->item_contributor_handle == " ") ? "":$this->item_contributor_handle;
        $this->item_contributor_name = ($this->item_contributor_name == " ") ? "":$this->item_contributor_name;
        $this->item_contributor_email = ($this->item_contributor_email == " ") ? "":$this->item_contributor_email;
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
        $str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
        
        return $str;
    }
    
    /**
     * Search user data for suggest
     *
     * @param string $handle
     * @param string $name
     * @param string $email
     * @return array()
     */
    private function searchSuggestData($handle, $name, $email){
        $handle = trim($handle);
        $name = trim($name);
        $email = trim($email);
        
        $params = array();
        $query = "SELECT ".
                 "users.user_id, ".
                 "users.handle AS ".RepositoryConst::ITEM_CONTRIBUTOR_HANDLE.", ".
                 "linkName.content AS ".RepositoryConst::ITEM_CONTRIBUTOR_NAME.", ".
                 "linkEmail.content AS ".RepositoryConst::ITEM_CONTRIBUTOR_EMAIL." ".
                 "FROM ".DATABASE_PREFIX."users AS users ".
                 "LEFT JOIN ".DATABASE_PREFIX."users_items_link AS linkName ".
                 "ON users.user_id = linkName.user_id AND linkName.item_id = 4 ".
                 "LEFT JOIN ".DATABASE_PREFIX."users_items_link AS linkEmail ".
                 "ON users.user_id = linkEmail.user_id AND linkEmail.item_id = 5 ";
        $where_query = "";
        if (strlen($handle))
        {
            $where_query .= "WHERE users.handle LIKE ? ";
            $params[] = $handle."%";
        }
        if (strlen($name))
        {
            $where_query .= strlen($where_query) > 0 ? "AND " : "WHERE ";
            $where_query .= "linkName.content LIKE ? ";
            $params[] = $name."%";
        }
        if (strlen($email))
        {
            $where_query .= strlen($where_query) > 0 ? "AND " : "WHERE ";
            $where_query .= "linkEmail.content LIKE ? ";
            $params[] = $email."%";
        }
        $query .= $where_query;
        $query .= "ORDER BY users.handle ASC;";
        $result = $this->Db->execute($query, $params);
        
        $retArray = array();
        foreach($result as $userData)
        {
            $authId = $this->getRoomAuthorityID($userData["user_id"]);
            if($authId >= REPOSITORY_ITEM_REGIST_AUTH)
            {
                array_push($retArray, $userData);
            }
        }
        
        return $retArray;
    }
}
?>
