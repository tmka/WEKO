<?php
// --------------------------------------------------------------------
//
// $Id: Admineditrow.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
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
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHarvesting.class.php';

/**
 * repository module admin action
 *
 * @package	 NetCommons
 * @author	  S.Kawasaki(IVIS)
 * @copyright   2006-2008 NetCommons Project
 * @license	 http://www.netcommons.org/license.txt  NetCommons License
 * @project	 NetCommons Project, supported by National Institute of Informatics
 * @access	  public
 */
class Repository_Action_Edit_Admineditrow extends RepositoryAction
{
    public $uploadsAction = null;
    
	// リクエストパラメタ (配列ではなく個別で渡す)	
	var $OS_type = null;						// All : OS kind
	var $disp_index_type = null;				// All : first index view setting
	var $default_disp_index = null;				// All : first index
	var $ranking_term_recent_regist = null;		// ranking : new insert time
	var $ranking_term_stats = null;				// ranking : ranking time
	var $ranking_disp_num = null;	   			// ranking : disp num
	var $ranking_is_disp_browse_item = null;	// ランキング表示可否, 最も閲覧されたアイテム
	var $ranking_is_disp_download_item = null;	// ランキング表示可否, 最もダウンロードされたアイテム
	var $ranking_is_disp_item_creator = null;	// ランキング表示可否, 最もアイテムを作成したユーザ
	var $ranking_is_disp_keyword = null;		// ランキング表示可否, 最も検索されたキーワード
	var $ranking_is_disp_recent_item = null;	// ランキング表示可否, 新着アイテム
//	var $item_coef_cp = null;					// アイテム管理 : 係数Cp
//	var $item_coef_ci = null;					// アイテム管理 : 係数Ci
//	var $file_coef_cp = null;					// アイテム管理 : 係数Cpf
//	var $file_coef_ci = null;					// アイテム管理 : 係数Cif
	var $export_is_include_files = null;		// アイテム管理 : Export ファイル出力の可否
	var $prvd_Identify_adminEmail = null;		// OAI-PMH管理 : 管理者メールアドレス
	var $prvd_Identify_repositoryName = null;	// OAI-PMH管理 : リポジトリ名
	var $prvd_Identify_earliestDatestamp_year = null;	// OAI-PMH管理 : earliest_datestamp : year
	var $prvd_Identify_earliestDatestamp_month = null;	// OAI-PMH管理 : earliest_datestamp : month
	var $prvd_Identify_earliestDatestamp_day = null;	// OAI-PMH管理 : earliest_datestamp : day
	var $prvd_Identify_earliestDatestamp_hour = null;	// OAI-PMH管理 : earliest_datestamp : hour
	var $prvd_Identify_earliestDatestamp_minute = null;	// OAI-PMH管理 : earliest_datestamp : minute
	var $prvd_Identify_earliestDatestamp_second = null;	// OAI-PMH管理 : earliest_datestamp : second	
    // Add Selective Harvesting 2013/09/04 R.Matsuura --start--
    public $harvesting_from_date_year = null;	// Selective Harvesting : from : year
    public $harvesting_from_date_month = null;	// Selective Harvesting : from : month
    public $harvesting_from_date_day = null;	// Selective Harvesting : from : day
    public $harvesting_from_date_hour = null;	// Selective Harvesting : from : hour
    public $harvesting_from_date_minute = null;	// Selective Harvesting : from : minute
    public $harvesting_from_date_second = null;	// Selective Harvesting : from : second
    public $harvesting_until_date_year = null;	// Selective Harvesting : until : year
    public $harvesting_until_date_month = null;	// Selective Harvesting : until : month
    public $harvesting_until_date_day = null;	// Selective Harvesting : until : day
    public $harvesting_until_date_hour = null;	// Selective Harvesting : until : hour
    public $harvesting_until_date_minute = null;// Selective Harvesting : until : minute
    public $harvesting_until_date_second = null;// Selective Harvesting : until : second
    public $harvesting_set_param = null;		// Selective Harvesting : set
    public $harvesting_execution_date = null;	// Selective Harvesting : execution date
    // Add Selective Harvesting 2013/09/04 R.Matsuura --end--
    
	var $path_wvWare = null;	// Commd "wvWare"
	var $path_xlhtml = null;	// Commd "xlhtml"
	var $path_poppler = null;	// Commd "poppler"
	var $path_ImageMagick = null;	// Commd "ImageMagick"
    var $path_pdftk = null;   // Commd "pdftk"  // Add pdftk 2012/06/07 A.Suzuki --start--
    public $path_ffmpeg = null; // Commd "ffmpeg"   // Add multimedia support 2012/08/27 T.Koyasu
    public $path_mecab = null; // Commd "mecab"   // Add external search word 2014/05/23 K.Matsuo

	var $review_flg = null;			// アイテム管理 : 査読・承認ON/OFF 
	var $item_auto_public = null;	// アイテム管理 : 承認済みアイテムの自動公開ss
	
	var $log_exclusion = null;	// log restriction
	
	var $sitelicense_org = null;	// org name for site license
	var $ip_sitelicense_from = null;	// ip address from for site license
	var $ip_sitelicense_to = null;	// ip address to for site license
	// Add mail address for feedback mail 2014/04/11 T.Ichikawa --start--
	var $sitelicense_mail = null; // mail address for site license
	// Add mail address for feedback mail 2014/04/11 T.Ichikawa --end--
	// Add multi ip address 2015/01/20 T.Ichikawa --start--
	var $sitelicense_id = null;
	var $sitelicense_group = null;
	// Add multi ip address 2015/01/20 T.Ichikawa --end--
	var $edit_type = null;			// edit type (addrow, shufflerow, deleterow, addHarvestRow, delHarvestRow, killHarvestingProcess, addExcludeAddress, killSendFeedbackMailProcess)
	var $edit_idx = null;			// edit row number
	
	// Add AWSAccessKeyId 2008/11/18 A.Suzuki --start--
	var $AWSAccessKeyId = null;	// 書誌情報簡単登録 : AWSAccessKeyId
	// Add AWSAccessKeyId 2008/11/18 A.Suzuki --end--
	
	// Add ranking disp　setting 2008/12/2 A.Suzuki --start--
	var $ranking_disp_setting = null;	// ranking : display　setting
	// Add ranking disp　setting 2008/12/2 A.Suzuki --end--
	
	// Add default display type 2008/12/8 A.Suzuki --start--
	var $default_disp_type = null;		// All : default display type setting
	// Add default display type 2008/12/8 A.Suzuki --end--
	
	// Add select item type for Site License 2009/01/06 A.Suzuki --start--
	var $sitelicense_itemtype = null; 	// allow item type ids
	var $allow_item_type = null;		// allow item type name
	var $not_allow_item_type = null;	// not allow item type name
	// Add select item type for Site License 2009/01/06 A.Suzuki --end--
	
	var $admin_active_tab = null;
	
	// Add search result setting 2009/03/13 A.Suzuki --start--
	var $sort_not_disp = null;
	var $sort_disp = null;
	var $sort_disp_default_index = null;
	var $sort_disp_default_keyword = null;
	// Add search result setting 2009/03/13 A.Suzuki --end--
	
	// Add default_list_view_num 2009/03/27 A.Suzuki --start--
	var $default_list_view_num = null;
	// Add default_list_view_num 2009/03/27 A.Suzuki --end--
	
	// Add currency_setting 2009/06/29 A.Suzuki --start--
	var $currency_setting = null;
	// Add currency_setting 2009/06/29 A.Suzuki --end--
	
	// Add select_language 2009/07/01 A.Suzuki --start--
	var $select_language = null;
	// Add select_language 2009/07/01 A.Suzuki --end--
	
	// Add alternative language setting 2009/08/11 A.Suzuki --start--
	var $alternative_language_ja = null;
	var $alternative_language_en = null;
	// Add alternative language setting 2009/08/11 A.Suzuki --end--
	
	// Add supple WEKO setting 2009/09/09 A.Suzuki --start--
	var $supple_weko_url = null;
	var $review_flg_supple = null;
	// Add supple WEKO setting 2009/09/09 A.Suzuki --end--
	
	// Add review mail setting 2009/09/24 Y.Nakao --start--
	var $review_mail_flg = null;
	var $review_mail = null;
	// Add review mail setting 2009/09/24 Y.Nakao --end--
	
    // Add external author ID prefix 2010/11/11 A.Suzuki --start--
    var $external_author_id_prefix_text = null;
    // Add external author ID prefix 2010/11/11 A.Suzuki --end--
    // Add index list 2011/4/7 S.Abe --start--
    var $select_index_list_display = null;
    // Add index list 2011/4/7 S.Abe --end--
    
    // Add AssociateTag for modify API Y.Nakao 2011/10/19 --start--
    public $AssociateTag = null;
    // Add AssociateTag for modify API Y.Nakao 2011/10/19 --end--
    
    // Add private tree parameter K.matsuo 2013/4/5 --start--
	public $is_make_privatetree = null;		// プライベートツリー : プライベートツリー作成の可否
	public $privatetree_sort_order = null;	// プライベートツリー : プライベートツリーのソート順
	public $privatetree_parent_indexid = NULL;	// プライベートツリー : プライベートツリーの親インデックスのID
    // Add private tree parameter K.matsuo 2013/4/5 --end--
    // Add harvesting 2012/03/05 A.Suzuki --start--
    /**
     * repositoryIDs for harvesting
     *
     * @var array
     */
    public $harvesting_repositoryId = null;
    /**
     * repositoryNames for harvesting
     *
     * @var array
     */
    public $harvesting_repositoryName = null;
    
    /**
     * baseUrl for harvesting
     *
     * @var array
     */
    public $harvesting_baseUrl = null;
    
    /**
     * metadataPrefix for harvesting
     *
     * @var array
     */
    public $harvesting_metadataPrefix = null;
    
    /**
     * post_index for harvesting
     *
     * @var array
     */
    public $harvesting_post_index = null;
    
    /**
     * harvesting_post_name for harvesting
     *
     * @var array
     */
    public $harvesting_post_name = null;
    
    /**
     * automatic_sorting for harvesting
     *
     * @var array
     */
    public $harvesting_automatic_sorting = null;
    // Add harvesting 2012/03/05 A.Suzuki --end--
    
    /**
     * PDF cover header type
     *
     * @var string
     */
    public $pdf_cover_header_type = null;
    
    /**
     * PDF cover header text
     *
     * @var string
     */
    public $pdf_cover_header_text = null;
    
    /**
     * PDF cover header align
     *
     * @var string
     */
    public $pdf_cover_header_align = null;
    
    /**
     * PDF cover header image delete flag
     *
     * @var int
     */
    public $pdf_cover_header_image_del = null;
    
    /**
     * Exclude address list for feedback mail
     *
     * @var int
     */
    public $feedback_exclude_address_list = null;
    
    /**
     * Additional exclude address for feedback mail
     *
     * @var int
     */
    public $feedback_exclude_address_add = null;
    
    /**
     * is connect checked ICHUSHI
     *
     * @var string
     */
    public $ichushiIsConnect = null;
    
    /**
     * ICHUSHI login id
     *
     * @var string
     */
    public $ichushiLoginId = null;
    
    /**
     * ICHUSHI login password
     *
     * @var string
     */
    public $ichushiLoginPasswd = null;
    
    /**
     * Feedback send mail activate flag
     *
     * @var string
     */
    public $feedbackSendMailActivateFlag = null;
    
    /**
     * sitelicense  mail activate flag
     *
     * @var string
     */
    public $sitelicenseSendMailActivateFlag = null;
    
    // Bug Fix WEKO-2014-039 no inherit prefix from html 2014/07/11 --start--
    public $prefixJalcDoi = null;
    public $prefixCrossRef = null;
    // Add DataCite 2015/02/09 K.Sugimoto --start--
    public $prefixDataCite = null;
    // Add DataCite 2015/02/09 K.Sugimoto --end--
    public $prefixCnri = null;
    // Bug Fix WEKO-2014-039 no inherit prefix from html 2014/07/11 --end--
    
    // OAI-PMH Output Flag
    public $oaipmh_output_flag = null;
    
    // Institution Name
    public $institutionName = null;
    
    // Add Default Search Type 2014/12/03 K.Sugimoto --start--
    // Default Search Type
    public $default_search_type = null;
    // Add Default Search Type 2014/12/03 K.Sugimoto --end--

    // Add Usage Statistics link display setting 2014/12/16 K.Matsushita --start--
    public $usagestatistics_link_display = null;
    // Add Usage Statistics link display setting 2014/12/16 K.Matsushita --end--

    // Add ranking tab display setting 2014/12/19 K.Matsushita --start--
    public $ranking_tab_display = null;
    // Add ranking tab display setting 2014/12/19 K.Matsushita --end--
    
    // Add DataCite 2015/02/09 K.Sugimoto --start--
    // PrefixID Add Flag
    public $prefix_flag = null;
    // Add DataCite 2015/02/09 K.Sugimoto --end--

    // Auto Input Metadata by CrossRef DOI 2015/03/02 K.Sugimoto --start--
    public $CrossRefQueryServicesAccount = null;
    // Auto Input Metadata by CrossRef DOI 2015/03/02 K.Sugimoto --end--
    
	/**
	 * @access  public
	 */
	function executeApp()
	{
		$istest = true;	// test flag
		try {
			// ----------------------------------------------------
			// call init action
			// ----------------------------------------------------
			$result = $this->initAction();
			if ( $result === false ) {
				$exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );	//主メッセージとログIDを指定して例外を作成
				$DetailMsg = null;							  //詳細メッセージ文字列作成
				sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
				$exception->setDetailMsg( $DetailMsg );			 //詳細メッセージ設定
				$this->failTrans();										//トランザクション失敗を設定(ROLLBACK)
				throw $exception;
			}
			
			// 表示中タブ情報
			$this->Session->setParameter("admin_active_tab", $this->admin_active_tab);
			
			// ----------------------------------------------------
			// Get admin_params from session
			// ----------------------------------------------------
			$admin_params = $this->Session->getParameter("admin_params");
			
			// ----------------------------------------------------
			// set session
			// ----------------------------------------------------
			/* OS設定：自動判別を行うため削除 2009/03/18 A.Suzuki
			$admin_params["OS_type"]["param_value"] = $this->OS_type;
			*/
	    	
			$admin_params["disp_index_type"]["param_value"] = $this->disp_index_type;
			$admin_params["default_disp_index"]["param_value"] = $this->default_disp_index;
			$admin_params["ranking_term_recent_regist"]["param_value"] = $this->ranking_term_recent_regist;
			$admin_params["ranking_term_stats"]["param_value"] = $this->ranking_term_stats;			
			$admin_params["ranking_disp_num"]["param_value"] = $this->ranking_disp_num;

			// Add select_language 2009/07/01 A.Suzuki --start--
			$admin_params["select_language"]["param_value"] = $this->select_language;
			// Add select_language 2009/07/01 A.Suzuki --end--
			
			// Add currency_setting 2009/06/29 A.Suzuki --start--
			$admin_params["currency_setting"]["param_value"] = $this->currency_setting;
			// Add currency_setting 2009/06/29 A.Suzuki --end--
			
			// ランキング表示可否, 最も閲覧されたアイテム
			if( $this->ranking_is_disp_browse_item == null ){
				$admin_params["ranking_is_disp_browse_item"]["param_value"] = "0";
			} else {
				$admin_params["ranking_is_disp_browse_item"]["param_value"] = "1";
			}
			// ランキング表示可否, 最もダウンロードされたアイテム
			if( $this->ranking_is_disp_download_item == null ){
				$admin_params["ranking_is_disp_download_item"]["param_value"] = "0";
			} else {
				$admin_params["ranking_is_disp_download_item"]["param_value"] = "1";
			}
			// ランキング表示可否, 最もアイテムを作成したユーザ
			if( $this->ranking_is_disp_item_creator == null ){
				$admin_params["ranking_is_disp_item_creator"]["param_value"] = "0";
			} else {
				$admin_params["ranking_is_disp_item_creator"]["param_value"] = "1";
			}
			// ランキング表示可否, 最も検索されたキーワード
			if( $this->ranking_is_disp_keyword == null ){
				$admin_params["ranking_is_disp_keyword"]["param_value"] = "0";
			} else {
				$admin_params["ranking_is_disp_keyword"]["param_value"] = "1";
			}
			// ランキング表示可否, 新着アイテム
			if( $this->ranking_is_disp_recent_item == null ){
				$admin_params["ranking_is_disp_recent_item"]["param_value"] = "0";
			} else {
				$admin_params["ranking_is_disp_recent_item"]["param_value"] = "1";
			}
			// アイテム管理 : Export ファイル出力の可否
			$admin_params["export_is_include_files"]["param_value"] = $this->export_is_include_files;
			// アイテム管理 : 査読承認のON/OFF
			if( $this->review_flg == null ){
				$admin_params["review_flg"]["param_value"] = "0";
			} else {
				$admin_params["review_flg"]["param_value"] = "1";
			}
			// アイテム管理 : 査読後のアイテム公開方式
			$admin_params["item_auto_public"]["param_value"] = $this->item_auto_public;
			
			// OAI-PMH管理 : 管理者メールアドレス
			$admin_params["prvd_Identify_adminEmail"]["param_value"] = $this->prvd_Identify_adminEmail;
			
			// OAI-PMH管理 : リポジトリ名
			$admin_params["prvd_Identify_repositoryName"]["param_value"] = $this->prvd_Identify_repositoryName;
			// OAI-PMH管理 : earliest_datestamp
			$admin_params['prvd_Identify_earliestDatestamp']['year'] = $this->prvd_Identify_earliestDatestamp_year;
    		$admin_params['prvd_Identify_earliestDatestamp']['month'] = $this->prvd_Identify_earliestDatestamp_month;
    		$admin_params['prvd_Identify_earliestDatestamp']['day'] = $this->prvd_Identify_earliestDatestamp_day;
    		$admin_params['prvd_Identify_earliestDatestamp']['hour'] = $this->prvd_Identify_earliestDatestamp_hour;
    		$admin_params['prvd_Identify_earliestDatestamp']['minute'] = $this->prvd_Identify_earliestDatestamp_minute;
    		$admin_params['prvd_Identify_earliestDatestamp']['second'] = $this->prvd_Identify_earliestDatestamp_second;
						
			// Server environment : pass to wvWare
			if(strlen($this->path_wvWare) > 0 && $this->path_wvWare[strlen($this->path_wvWare)-1] != DIRECTORY_SEPARATOR){
				$this->path_wvWare .= DIRECTORY_SEPARATOR;
			}
			$admin_params["path_wvWare"]["param_value"] = $this->path_wvWare;
			// Server environment : pass to xlhtml
			if(strlen($this->path_xlhtml) > 0 && $this->path_xlhtml[strlen($this->path_xlhtml)-1] != DIRECTORY_SEPARATOR){
				$this->path_xlhtml .= DIRECTORY_SEPARATOR;
			}
			$admin_params["path_xlhtml"]["param_value"] = $this->path_xlhtml;
			// Server environment : pass to poppler
			if(strlen($this->path_poppler) > 0 && $this->path_poppler[strlen($this->path_poppler)-1] != DIRECTORY_SEPARATOR){
				$this->path_poppler .= DIRECTORY_SEPARATOR;
			}
			$admin_params["path_poppler"]["param_value"] = $this->path_poppler;
			// Server environment : pass to ImageMagick
			if(strlen($this->path_ImageMagick) > 0 && $this->path_ImageMagick[strlen($this->path_ImageMagick)-1] != DIRECTORY_SEPARATOR){
				$this->path_ImageMagick .= DIRECTORY_SEPARATOR;
			}
			$admin_params["path_ImageMagick"]["param_value"] = $this->path_ImageMagick;
            // Add pdftk 2012/06/07 A.Suzuki --start--
            // Server environment : path to pdftk
            if(strlen($this->path_pdftk) > 0 && $this->path_pdftk[strlen($this->path_pdftk)-1] != DIRECTORY_SEPARATOR){
                $this->path_pdftk .= DIRECTORY_SEPARATOR;
            }
            $admin_params["path_pdftk"]["param_value"] = $this->path_pdftk;
            // Add pdftk 2012/06/07 A.Suzuki --end--
            // Add multimedia support 2012/08/27 T.Koyasu -start-
            if(strlen($this->path_ffmpeg) > 0 && $this->path_ffmpeg[strlen($this->path_ffmpeg) - 1] != DIRECTORY_SEPARATOR){
                $this->path_ffmpeg .= DIRECTORY_SEPARATOR;
            }
            $admin_params["path_ffmpeg"]["param_value"] = $this->path_ffmpeg;
            // Add multimedia support 2012/08/27 T.Koyasu -end-
            // Add external search word 2014/05/23 K.Matsuo -start-
            if(strlen($this->path_mecab) > 0 && $this->path_mecab[strlen($this->path_mecab) - 1] != DIRECTORY_SEPARATOR){
                $this->path_mecab .= DIRECTORY_SEPARATOR;
            }
            $admin_params["path_mecab"]["param_value"] = $this->path_mecab;
            // Add external search word 2014/05/23 K.Matsuo -end-
			// wvHtml
			if($admin_params['path_wvWare']['param_value']!=""){ 
	            if(file_exists($admin_params['path_wvWare']['param_value']."wvHtml") || 
	            	file_exists($admin_params['path_wvWare']['param_value']."wvHtml.exe")){
       				$admin_params['path_wvWare']['path'] = "true";
       			} else {
       				$admin_params['path_wvWare']['path'] = "false";
       			}
	        } else {
	        	$admin_params['path_wvWare']['path'] = "false";
	        }
	        // xlhtml
       		if($admin_params['path_xlhtml']['param_value']!=""){
       			if(file_exists($admin_params['path_xlhtml']['param_value']."xlhtml") || 
       				file_exists($admin_params['path_xlhtml']['param_value']."xlhtml.exe")){
       				$admin_params['path_xlhtml']['path'] = "true";
       			} else {
       				$admin_params['path_xlhtml']['path'] = "false";
       			}
	        } else {
	        	$admin_params['path_xlhtml']['path'] = "false";
	        }
       		// poppler
       		if($admin_params['path_poppler']['param_value']!=""){
	        	if(file_exists($admin_params['path_poppler']['param_value']."pdftotext") || 
	        		file_exists($admin_params['path_poppler']['param_value']."pdftotext.exe")){
       				$admin_params['path_poppler']['path'] = "true";
       			} else {
       				$admin_params['path_poppler']['path'] = "false";
       			}
	        } else {
	        	$admin_params['path_poppler']['path'] = "false";
	        }
      		// ImageMagick
       		if($admin_params['path_ImageMagick']['param_value']!=""){
       			if(file_exists($admin_params['path_ImageMagick']['param_value']."convert") || 
       				file_exists($admin_params['path_ImageMagick']['param_value']."convert.exe")){
		        	$admin_params['path_ImageMagick']['path'] = "true";
       			} else {
       				$admin_params['path_ImageMagick']['path'] = "false";
       			}
	        } else {
	        	$admin_params['path_ImageMagick']['path'] = "false";
	        }
            // Add pdftk 2012/06/07 A.Suzuki --start--
            // pdftk
            if($admin_params['path_pdftk']['param_value']!=""){
                if(file_exists($admin_params['path_pdftk']['param_value']."pdftk") || 
                    file_exists($admin_params['path_pdftk']['param_value']."pdftk.exe")){
                    $admin_params['path_pdftk']['path'] = "true";
                } else {
                    $admin_params['path_pdftk']['path'] = "false";
                }
            } else {
                $admin_params['path_pdftk']['path'] = "false";
            }
            // Add pdftk 2012/06/07 A.Suzuki --end--
            // Add multimedia support 2012/08/27 T.Koyasu -start-
            // ffmpeg
            if($admin_params['path_ffmpeg']['param_value'] != ""){
                if(file_exists($admin_params['path_ffmpeg']['param_value']. "ffmpeg") || 
                   file_exists($admin_params['path_ffmpeg']['param_value']. "ffmpeg.exe")){
                    $admin_params['path_ffmpeg']['path'] = "true";
                } else {
                    $admin_params['path_ffmpeg']['path'] = "false";
                }
            } else {
                $admin_params['path_ffmpeg']['path'] = "false";
            }
            // Add multimedia support 2012/08/27 T.Koyasu -end-
            // Add external search word 2014/05/23 K.Matsuo -start-
            // mecab
            if($admin_params['path_mecab']['param_value'] != ""){
                if(file_exists($admin_params['path_mecab']['param_value']. "mecab") || 
                   file_exists($admin_params['path_mecab']['param_value']. "mecab.exe")){
                    $admin_params['path_mecab']['path'] = "true";
                } else {
                    $admin_params['path_mecab']['path'] = "false";
                }
            } else {
                $admin_params['path_mecab']['path'] = "false";
            }
            // Add external search word 2014/05/23 K.Matsuo -end-
            
			// Add privatetree setting 2013/04/05 K.Matsuo -start-
			
			$define_inc_file_path = WEBAPP_DIR. '/modules/repository/config/define.inc.php';
			// define.inc.phpに書き込み権限があるかをセッションに保存
			$is_writable_define = is_writable($define_inc_file_path);
			$is_writable_define = false;
			if($is_writable_define){
				$admin_params["is_make_privatetree"]["param_value"] = $this->is_make_privatetree;
				$admin_params["privatetree_sort_order"]["param_value"] = $this->privatetree_sort_order;
				$admin_params["privatetree_parent_indexid"]["param_value"] = $this->privatetree_parent_indexid;
				$query = "SELECT index_name, index_name_english ".
						 " FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_INDEX.
						 " WHERE index_id = ? ".
						 " AND is_delete = ? ";
				$params = array();
				$params[] = $admin_params["privatetree_parent_indexid"]["param_value"];
				$params[] = 0;
				$ret = $this->Db->execute($query, $params);
				if($ret != false || count($ret) == 1){
					
					if($this->Session->getParameter("_lang") == "japanese")
					{
						$admin_params["privatetree_parent_indexname"]["param_value"] = $ret[0]['index_name'];
					}
					else
					{
						$admin_params["privatetree_parent_indexname"]["param_value"] = $ret[0]['index_name_english'];
					}
				}
			} else {
				$admin_params["is_make_privatetree"]["param_value"] = 0;
			}
			// Add privatetree setting 2013/04/05 K.Matsuo -end-
			
            // Set SiteLicense
            for($ii=0; $ii<count($this->sitelicense_id); $ii++){
                $this->sitelicense_org[$ii] = str_replace(" ", "", $this->sitelicense_org[$ii]);
                $this->sitelicense_group[$ii] = str_replace(" ", "", $this->sitelicense_group[$ii]);
                $this->sitelicense_mail[$ii] = str_replace(" ", "", $this->sitelicense_mail[$ii]);
                // IPは$ip[$ii][$jj][$kk]の形式で渡される
                // $ii : 機関の数を表す, $jj : 機関に設定されたIPの数を表す, $kk : IPアドレスを表す。セグメントで分割されており0～3になる
                $ipaddress_from = array();
                for($jj = 0; $jj < count($this->ip_sitelicense_from[$ii]); $jj++) {
                    array_push($ipaddress_from, array(
                                                       str_replace(" ", "", $this->ip_sitelicense_from[$ii][$jj][0]), 
                                                       str_replace(" ", "", $this->ip_sitelicense_from[$ii][$jj][1]), 
                                                       str_replace(" ", "", $this->ip_sitelicense_from[$ii][$jj][2]), 
                                                       str_replace(" ", "", $this->ip_sitelicense_from[$ii][$jj][3])
                                                     )
                              );
                }
                $this->ip_sitelicense_from[$ii] = $ipaddress_from;
                
                $ipaddress_to = array();
                for($jj = 0; $jj < count($this->ip_sitelicense_to[$ii]); $jj++) {
                    array_push($ipaddress_to, array(
                                                         str_replace(" ", "", $this->ip_sitelicense_to[$ii][$jj][0]), 
                                                         str_replace(" ", "", $this->ip_sitelicense_to[$ii][$jj][1]), 
                                                         str_replace(" ", "", $this->ip_sitelicense_to[$ii][$jj][2]), 
                                                         str_replace(" ", "", $this->ip_sitelicense_to[$ii][$jj][3])
                                                        )
                              );
                }
                $this->ip_sitelicense_to[$ii] = $ipaddress_to;
            }
            if($this->edit_type == "addrow"){
                // 各配列の末尾に空の値の要素を挿入する
                $this->sitelicense_id[] = 0;
                $this->sitelicense_org[] = "";
                $this->sitelicense_group[] = "";
                $this->ip_sitelicense_from[] = array(array("", "", "", ""));
                $this->ip_sitelicense_to[] = array(array("", "", "", ""));
                $this->sitelicense_mail[] = "";
            } else if($this->edit_type == "shfrow_up"){
                // 一つ前の要素と入れ替える
                if($this->edit_idx > 0) {
                    $tmp_id = $this->sitelicense_id[$this->edit_idx];
                    $this->sitelicense_id[$this->edit_idx] = $this->sitelicense_id[$this->edit_idx-1];
                    $this->sitelicense_id[$this->edit_idx-1] = $tmp_id;
                    
                    $tmp_org = $this->sitelicense_org[$this->edit_idx];
                    $this->sitelicense_org[$this->edit_idx] = $this->sitelicense_org[$this->edit_idx-1];
                    $this->sitelicense_org[$this->edit_idx-1] = $tmp_org;
                    
                    $tmp_group = $this->sitelicense_group[$this->edit_idx];
                    $this->sitelicense_group[$this->edit_idx] = $this->sitelicense_group[$this->edit_idx-1];
                    $this->sitelicense_group[$this->edit_idx-1] = $tmp_group;
                    
                    $tmp_start_ip = $this->ip_sitelicense_from[$this->edit_idx];
                    $this->ip_sitelicense_from[$this->edit_idx] = $this->ip_sitelicense_from[$this->edit_idx-1];
                    $this->ip_sitelicense_from[$this->edit_idx-1] = $tmp_start_ip;
                    
                    $tmp_finish_ip = $this->ip_sitelicense_to[$this->edit_idx];
                    $this->ip_sitelicense_to[$this->edit_idx] = $this->ip_sitelicense_to[$this->edit_idx-1];
                    $this->ip_sitelicense_to[$this->edit_idx-1] = $tmp_finish_ip;
                    
                    $tmp_mail = $this->sitelicense_mail[$this->edit_idx];
                    $this->sitelicense_mail[$this->edit_idx] = $this->sitelicense_mail[$this->edit_idx-1];
                    $this->sitelicense_mail[$this->edit_idx-1] = $tmp_mail;
                }
            } else if($this->edit_type == "shfrow_dw"){
                // 一つ後の要素と入れ替える
                if($this->edit_idx < count($this->sitelicense_id)-1) {
                    $tmp_id = $this->sitelicense_id[$this->edit_idx];
                    $this->sitelicense_id[$this->edit_idx] = $this->sitelicense_id[$this->edit_idx+1];
                    $this->sitelicense_id[$this->edit_idx+1] = $tmp_id;
                    
                    $tmp_org = $this->sitelicense_org[$this->edit_idx];
                    $this->sitelicense_org[$this->edit_idx] = $this->sitelicense_org[$this->edit_idx+1];
                    $this->sitelicense_org[$this->edit_idx+1] = $tmp_org;
                    
                    $tmp_group = $this->sitelicense_group[$this->edit_idx];
                    $this->sitelicense_group[$this->edit_idx] = $this->sitelicense_group[$this->edit_idx+1];
                    $this->sitelicense_group[$this->edit_idx+1] = $tmp_group;
                    
                    $tmp_start_ip = $this->ip_sitelicense_from[$this->edit_idx];
                    $this->ip_sitelicense_from[$this->edit_idx] = $this->ip_sitelicense_from[$this->edit_idx+1];
                    $this->ip_sitelicense_from[$this->edit_idx+1] = $tmp_start_ip;
                    
                    $tmp_finish_ip = $this->ip_sitelicense_to[$this->edit_idx];
                    $this->ip_sitelicense_to[$this->edit_idx] = $this->ip_sitelicense_to[$this->edit_idx+1];
                    $this->ip_sitelicense_to[$this->edit_idx+1] = $tmp_finish_ip;
                    
                    $tmp_mail = $this->sitelicense_mail[$this->edit_idx];
                    $this->sitelicense_mail[$this->edit_idx] = $this->sitelicense_mail[$this->edit_idx+1];
                    $this->sitelicense_mail[$this->edit_idx+1] = $tmp_mail;
                }
            } else if($this->edit_type == "delrow"){
                // 指定された要素を削除する
                array_splice($this->sitelicense_id, $this->edit_idx, 1);
                array_splice($this->sitelicense_org, $this->edit_idx, 1);
                array_splice($this->sitelicense_group, $this->edit_idx, 1);
                array_splice($this->ip_sitelicense_from, $this->edit_idx, 1);
                array_splice($this->ip_sitelicense_to, $this->edit_idx, 1);
                array_splice($this->sitelicense_mail, $this->edit_idx, 1);
            } else if($this->edit_type == "addiprow") {
                // IP配列の末尾に空の要素を追加する
                $this->ip_sitelicense_from[$this->edit_idx][] = array("", "", "", "");
                $this->ip_sitelicense_to[$this->edit_idx][] = array("", "", "", "");
            }
            $admin_params["sitelicense_id"]["param_value"] = $this->sitelicense_id;
            $admin_params["sitelicense_org"]["param_value"] = $this->sitelicense_org;
            $admin_params["sitelicense_group"]["param_value"] = $this->sitelicense_group;
            $admin_params["ip_sitelicense_from"]["param_value"] = $this->ip_sitelicense_from;
            $admin_params["ip_sitelicense_to"]["param_value"] = $this->ip_sitelicense_to;
            $admin_params["sitelicense_mail"]["param_value"] = $this->sitelicense_mail;
            
			if(!isset($this->sitelicenseSendMailActivateFlag))
            {
                $admin_params["send_sitelicense_mail_activate_flg"]["param_value"] = "0";
            }
            else
            {
                $admin_params["send_sitelicense_mail_activate_flg"]["param_value"] = "1";
            }
			// Add mail address for feedback mail 2014/04/11 T.Ichikawa --end--
			
			// Add AWSAccessKeyId 2008/11/18 A.Suzuki --start--
			$admin_params["AWSAccessKeyId"]["param_value"] = $this->AWSAccessKeyId;
			// Add AWSAccessKeyId 2008/11/18 A.Suzuki --end--
			
            // Add AssociateTag for modify API Y.Nakao 2011/10/19 --start--
            $admin_params["AssociateTag"]["param_value"] = $this->AssociateTag;
            // Add AssociateTag for modify API Y.Nakao 2011/10/19 --end--
			
			// Add ranking disp setting 2008/12/2 A.Suzuki --start--
			$admin_params["ranking_disp_setting"]["param_value"] = $this->ranking_disp_setting;
			// Add ranking disp setting 2008/12/2 A.Suzuki --end--
			
			// Add default display type setting 2008/12/8 A.Suzuki --start--
			$admin_params["default_disp_type"]["param_value"] = $this->default_disp_type;
			// Add default display type setting 2008/12/8 A.Suzuki --end--
			// Add index list 2011/4/7 S.Abe --start--
			$admin_params["select_index_list_display"]["param_value"] = $this->select_index_list_display;
			// Add index list 2011/4/7 S.Abe --end--
			
			
			// Add default display type setting 2009/01/06 A.Suzuki --start--
			
			
			$site_license_item_id = explode(",", $this->sitelicense_itemtype);
			// viewからそのまま移植 --ここから--			
			// get item_type_id and item_type_name
			$query = "SELECT item_type_id, item_type_name ".
					 "FROM ". DATABASE_PREFIX ."repository_item_type ".
	       			 "WHERE is_delete = ?; ";
			$params = null;
	       	$params[] = 0;
	    	//　SELECT実行
	        $result = $this->Db->execute($query, $params);
       		if ($result === false) {
		        $errNo = $this->Db->ErrorNo();
		        $errMsg = $this->Db->ErrorMsg();
		        $this->Session->setParameter("error_code",$errMsg);
       			if($istest) { echo $errMsg . "<br>"; }
		        return 'error';
	    	}
	    	$sitelicense_itemtype_array = array();
	    	$sitelicense_not_itemtype_array = array();
	    	for($ii=0; $ii<count($site_license_item_id); $ii++){
	    		for($jj=0; $jj<count($result); $jj++){
	    			if($site_license_item_id[$ii] == $result[$jj]['item_type_id']){
	    				array_push($sitelicense_not_itemtype_array, array($result[$jj]['item_type_id'], $result[$jj]['item_type_name']));
	    				array_splice($result, $jj, 1);
	    				break;
	    			}
	    		}
	    	}
	    	for($ii=0; $ii<count($result); $ii++){
	    		array_push($sitelicense_itemtype_array, array($result[$ii]['item_type_id'], $result[$ii]['item_type_name']));
	    	}
	    	$admin_params["sitelicense_itemtype_array"]["param_value"] = $sitelicense_itemtype_array;
	    	$admin_params["sitelicense_not_itemtype_array"]["param_value"] = $sitelicense_not_itemtype_array;
	    	// viewからそのまま移植 --ここまで--
	    	$admin_params["site_license_item_type_id"]["param_value"] = $this->sitelicense_itemtype;
			// Add default display type setting 2009/01/06 A.Suzuki --end--
			
	    	// Add search result setting 2009/03/06 A.Suzuki --start--
	        // ----------------------------------------------------
	        // search result setting	        
	        // ----------------------------------------------------
	        $admin_params["sort_disp"]["param_value"] = $this->sort_disp;
	        $admin_params["sort_not_disp"]["param_value"] = $this->sort_not_disp;
	        
	        $admin_params["sort_disp_default"]["param_value"] = $this->sort_disp_default_index."|".$this->sort_disp_default_keyword;
	    	
	        $sort_disp_num = explode("|", $admin_params["sort_disp"]["param_value"]);
	        $sort_not_disp_num = explode("|", $admin_params["sort_not_disp"]["param_value"]);
	        
	        $admin_params["sort_disp_num"]["param_value"] = $sort_disp_num;
	        $admin_params["sort_not_disp_num"]["param_value"] = $sort_not_disp_num;
	        
	    	// Add search result setting 2009/03/06 A.Suzuki --end--
	    	
	        // Add default_list_view_num 2009/03/27 A.Suzuki --start--
			$admin_params["default_list_view_num"]["param_value"] = $this->default_list_view_num;
			// Add default_list_view_num 2009/03/27 A.Suzuki --end--
			
			// Add alternative language setting 2009/08/11 A.Suzuki --start--
			$lang_array = array();
			if($this->alternative_language_ja == null){
				$lang_array["japanese_flag"] = 0;
			} else {
				$lang_array["japanese_flag"] = 1;
			}
			if($this->alternative_language_en == null){
				$lang_array["english_flag"] = 0;
			} else {
				$lang_array["english_flag"] = 1;
			}
	        $admin_params["alternative_language"]["param_value"] = $lang_array;
			// Add alternative language setting 2009/08/11 A.Suzuki --end--
	    	
			
			// Add supple WEKO setting 2009/09/09 A.Suzuki --start--
			// サプリWEKOのURL
			$admin_params["supple_weko_url"]["param_value"] = $this->supple_weko_url;
			
			// サプリコンテンツの査読 ON/OFF
			if( $this->review_flg_supple == null ){
				$admin_params["review_flg_supple"]["param_value"] = "0";
			} else {
				$admin_params["review_flg_supple"]["param_value"] = "1";
			}
			// Add supple WEKO setting 2009/09/09 A.Suzuki --end--
			
			// Add review mail setting 2009/09/24 Y.Nakao --start--
			// 査読通知メール設定
			if( $this->review_flg == null ){
				$admin_params["review_mail_flg"]["param_value"] = "0";
			} else {
				$admin_params["review_mail_flg"]["param_value"] = "1";
			}
			$admin_params["review_mail"]["param_value"] = $this->review_mail;
	        // Add review mail setting 2009/09/24 Y.Nakao --end--
	        
			$admin_params["log_exclusion"]["param_value"] = $this->log_exclusion;
			
			$admin_params["author_id_prefix"]["text"] = $this->external_author_id_prefix_text;
			$admin_params["author_id_prefix"]["list"] = array();
			$author_id_prefix_text_array = explode("|", $this->external_author_id_prefix_text);
			for($ii=0;$ii<count($author_id_prefix_text_array);$ii++){
			    $data = explode(",", $author_id_prefix_text_array[$ii]);
			    $author_id_prefix_array = array("prefix_id"=>$data[0], "prefix_name"=>$data[1]);
			    array_push($admin_params["author_id_prefix"]["list"], $author_id_prefix_array);
			}
			
            // Add harvesting 2012/03/05 A.Suzuki --start--
            $harvestingRepositories = array();
            for($ii=0;$ii<count($this->harvesting_repositoryName);$ii++)
            {
                // If input text data is half space only, change to empty.
                if($this->harvesting_repositoryName[$ii] == " ")
                {
                    $this->harvesting_repositoryName[$ii] = "";
                }
                if($this->harvesting_baseUrl[$ii] == " ")
                {
                    $this->harvesting_baseUrl[$ii] = "";
                }
                if($this->harvesting_post_index[$ii] == " ")
                {
                    $this->harvesting_post_index[$ii] = "";
                }
                if($this->harvesting_post_name[$ii] == " ")
                {
                    $this->harvesting_post_name[$ii] = "";
                }
                
                // Add Selective Harvesting 2013/09/04 R.Matsuura --start--
                // Replace double-byte space to half space
                $this->harvesting_set_param[$ii] = str_replace("　", " ", $this->harvesting_set_param[$ii]);
                // When harvesting_set_param is only half space, change to empty
                if($this->harvesting_set_param[$ii] == " ");
                {
                    $this->harvesting_set_param[$ii] = "";
                }
                // When harvesting_from_date_~ is not numeric, return error
                if(!(is_numeric($this->harvesting_from_date_year[$ii])
                    && is_numeric($this->harvesting_from_date_month[$ii])
                    && is_numeric($this->harvesting_from_date_day[$ii])
                    && is_numeric($this->harvesting_from_date_hour[$ii])
                    && is_numeric($this->harvesting_from_date_minute[$ii])
                    && is_numeric($this->harvesting_from_date_second[$ii])))
                {
                    return "error";
                }
                // When harvesting_untile_date_~ is not numeric, return error
                if(!(is_numeric($this->harvesting_until_date_year[$ii])
                    && is_numeric($this->harvesting_until_date_month[$ii])
                    && is_numeric($this->harvesting_until_date_day[$ii])
                    && is_numeric($this->harvesting_until_date_hour[$ii])
                    && is_numeric($this->harvesting_until_date_minute[$ii])
                    && is_numeric($this->harvesting_until_date_second[$ii])))
                {
                    return "error";
                }
                // When more than five digits, return error
                if(strlen($this->harvesting_from_date_year[$ii]) >= 5
                    || strlen($this->harvesting_until_date_year[$ii]) >= 5)
                {
                    return "error";
                }
                // When the three digits or less, do to 4-digit zero-fill
                $this->harvesting_from_date_year[$ii] = sprintf("%04d", $this->harvesting_from_date_year[$ii]);
                $this->harvesting_until_date_year[$ii] = sprintf("%04d", $this->harvesting_until_date_year[$ii]);
                
                // Consolidated inthe form of YYYY-MM-DDThh:mm:ssZ
                $from_date = $this->harvesting_from_date_year[$ii] . "-" .
                            $this->harvesting_from_date_month[$ii] . "-" .
                            $this->harvesting_from_date_day[$ii] . "T" .
                            $this->harvesting_from_date_hour[$ii] . ":" .
                            $this->harvesting_from_date_minute[$ii] . ":" .
                            $this->harvesting_from_date_second[$ii] . "Z";
                // Consolidated inthe form of YYYY-MM-DDThh:mm:ssZ
                $until_date = $this->harvesting_until_date_year[$ii] . "-" .
                            $this->harvesting_until_date_month[$ii] . "-" .
                            $this->harvesting_until_date_day[$ii] . "T" .
                            $this->harvesting_until_date_hour[$ii] . ":" .
                            $this->harvesting_until_date_minute[$ii] . ":" .
                            $this->harvesting_until_date_second[$ii] . "Z";
                if($from_date > $until_date) {
                    $this->Session->setParameter("error_msg", "error : invalid date input.");
                    $this->failTrans();     //ROLLBACK
                    $this->exitAction();
                    return 'error';
                }
                
                $from_date_array[RepositoryHarvesting::DATE_YEAR] = $this->harvesting_from_date_year[$ii];
                $from_date_array[RepositoryHarvesting::DATE_MONTH] = $this->harvesting_from_date_month[$ii];
                $from_date_array[RepositoryHarvesting::DATE_DAY] = $this->harvesting_from_date_day[$ii];
                $from_date_array[RepositoryHarvesting::DATE_HOUR] = $this->harvesting_from_date_hour[$ii];
                $from_date_array[RepositoryHarvesting::DATE_MINUTE] = $this->harvesting_from_date_minute[$ii];
                $from_date_array[RepositoryHarvesting::DATE_SECOND] = $this->harvesting_from_date_second[$ii];
                $until_date_array[RepositoryHarvesting::DATE_YEAR] = $this->harvesting_until_date_year[$ii];
                $until_date_array[RepositoryHarvesting::DATE_MONTH] = $this->harvesting_until_date_month[$ii];
                $until_date_array[RepositoryHarvesting::DATE_DAY] = $this->harvesting_until_date_day[$ii];
                $until_date_array[RepositoryHarvesting::DATE_HOUR] = $this->harvesting_until_date_hour[$ii];
                $until_date_array[RepositoryHarvesting::DATE_MINUTE] = $this->harvesting_until_date_minute[$ii];
                $until_date_array[RepositoryHarvesting::DATE_SECOND] = $this->harvesting_until_date_second[$ii];
                // Add Selective Harvesting 2013/09/04 R.Matsuura --end--
                
                $repoData = array(
                                "repository_id" => $this->harvesting_repositoryId[$ii],
                                "repository_name" => $this->harvesting_repositoryName[$ii],
                                "base_url" => $this->harvesting_baseUrl[$ii],
                                "metadata_prefix" => $this->harvesting_metadataPrefix[$ii],
                                "post_index_id" => $this->harvesting_post_index[$ii],
                                "post_index_name" => $this->harvesting_post_name[$ii],
                                "automatic_sorting" => $this->harvesting_automatic_sorting[$ii],
                                
                                // Add Selective Harvesting 2013/09/04 R.Matsuura --start--
                                "from_date_array" => $from_date_array,
                                "until_date_array" => $until_date_array,
                                "set_param" => $this->harvesting_set_param[$ii],
                                "execution_date" => $this->harvesting_execution_date[$ii]
                                // Add Selective Harvesting 2013/09/04 R.Matsuura --end--
                            );
                array_push($harvestingRepositories, $repoData);
            }
            
            if($this->edit_type == "addHarvestRow")
            {
                // Add Selective Harvesting 2013/09/04 R.Matsuura --start--
                $Harvesting = new RepositoryHarvesting($this->Session, $this->Db);
                $from_date_array = array();
                $until_date_array = array();
                $Harvesting->dividDatestamp(RepositoryHarvesting::DEFAULT_FROM_DATE, $from_date_array);
                $Harvesting->dividDatestamp(RepositoryHarvesting::DEFAULT_UNTIL_DATE, $until_date_array);
                // Add Selective Harvesting 2013/09/04 R.Matsuura --end--
                
                // Add new element
                array_push($harvestingRepositories,
                            array(
                                "repository_id" => "0",
                                "repository_name" => "",
                                "base_url" => "",
                                "metadata_prefix" => "oai_dc",
                                "post_index_id" => "",
                                "post_index_name" => "",
                                "automatic_sorting" => "0",
                                // Add Selective Harvesting 2013/09/04 R.Matsuura --start--
                                "from_date_array" => $from_date_array,
                                "until_date_array" => $until_date_array,
                                "set_param" => "",
                                "execution_date" => ""
                                // Add Selective Harvesting 2013/09/04 R.Matsuura --end--
                            )
                        );
            }
            else if($this->edit_type == "delHarvestRow")
            {
                if(strlen($this->edit_idx) > 0 && is_numeric($this->edit_idx))
                {
                    if(array_key_exists($this->edit_idx, $harvestingRepositories))
                    {
                        // Delete select element
                        array_splice($harvestingRepositories, $this->edit_idx, 1);
                    }
                }
            }
            else if($this->edit_type == "killHarvestingProcess")
            {
                $repositoryHarvesting = new RepositoryHarvesting($this->Session, $this->Db);
                $repositoryHarvesting->killProcess();
                $repositoryHarvesting->openProgressFile(false);
                $this->Session->setParameter("harvestingStatus", $repositoryHarvesting->getStatus());
                $harvestingRepositories = $this->Session->getParameter("harvestingRepositories");
            }
            $this->Session->setParameter("harvestingRepositories", $harvestingRepositories);
            // Add harvesting 2012/03/05 A.Suzuki --end--
            
            // Add PDF cover page 2012/06/13 A.Suzuki --start--
            // PDF cover header type
            if($this->pdf_cover_header_type != null){
                $admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_TYPE] = trim($this->pdf_cover_header_type);
                
            }
            if(strlen($admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_TYPE]) == 0)
            {
                $admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_TYPE]
                    = RepositoryConst::PDF_COVER_HEADER_TYPE_TEXT;
            }
            
            // PDF cover header text
            if($this->pdf_cover_header_text != null){
                if(strlen($this->pdf_cover_header_text) > 100)
                {
                    $this->Session->setParameter("error_msg", "Length of characters has been exceeded in Header text.");
                }
                $admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_TEXT] = trim($this->pdf_cover_header_text);
            }
            
            // PDF cover header image
            $uploadFile = $this->Session->getParameter("repositoryAdminFileList");
            if(isset($this->pdf_cover_header_image_del) && $this->pdf_cover_header_image_del == 1)
            {
                 // delete upload file
                $this->Session->removeParameter("repositoryAdminFileList");
                if(is_array($uploadFile) && count($uploadFile) > 0)
                {
                    $this->uploadsAction->delUploadsById($uploadFile["upload_id"]);
                }
                
                // delete image
                $admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_IMAGE]["exists"] = "false";
                $admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_IMAGE]["src"] = "";
                $admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_IMAGE]["width"] = "";
                $admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_IMAGE]["height"] = "";
            }
            else if(is_array($uploadFile) && count($uploadFile) > 0)
            {
                $admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_IMAGE]["exists"] = "false";
                $admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_IMAGE]["src"] = "";
                $admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_IMAGE]["width"] = "";
                $admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_IMAGE]["height"] = "";
                
                if(!is_numeric(strpos($uploadFile["mimetype"],"image")) || !$this->checkHeaderImageExtension($uploadFile["extension"]))
                {
                    $this->Session->setParameter("error_msg", "Uploaded file to the Header Image cannot be used!");
                    
                    // delete upload file
                    $this->Session->removeParameter("repositoryAdminFileList");
                    $this->uploadsAction->delUploadsById($uploadFile["upload_id"]);
                }
                else
                {
                    // Resize
                    $width = 0;
                    $height = 0;
                    $maxWidth = 200;
                    $maxHeight = 100;
                    $path = WEBAPP_DIR. "/uploads/repository/".$uploadFile['physical_file_name'];
                    list($width, $height) = getimagesize($path);
                    $this->resizeImage($width, $height, $maxWidth, $maxHeight);
                    
                    // Set params
                    $admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_IMAGE]["exists"] = "true";
                    $admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_IMAGE]["src"]
                        = BASE_URL."/?action=common_download_main&upload_id=".$uploadFile['upload_id'];
                    $admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_IMAGE]["width"] = $width;
                    $admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_IMAGE]["height"] = $height;
                }
            }
            
            // PDF cover header align
            if($this->pdf_cover_header_align != null){
                $admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_ALIGN] = trim($this->pdf_cover_header_align);
            }
            if(strlen($admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_ALIGN]) == 0)
            {
                $admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_ALIGN]
                    = RepositoryConst::PDF_COVER_HEADER_ALIGN_RIGHT;
            }
            // Add PDF cover page 2012/06/13 A.Suzuki --end--
            
            // Add send feedback mail 2012/08/24 A.Suzuki --start--
            if( $this->feedbackSendMailActivateFlag == null )
            {
                $admin_params["send_feedback_mail_activate_flg"]["param_value"] = "0";
            }
            else
            {
                $admin_params["send_feedback_mail_activate_flg"]["param_value"] = "1";
            }
            
            $excludeUserDataList = array();
            $excludeUserDataText = "";
            $excludeAddressList = explode(",", trim($this->feedback_exclude_address_list));
            foreach($excludeAddressList as $address)
            {
                if(strlen($address) == 0)
                {
                    continue;
                }
                $userName = $this->getUserNameByAddress($address);
                $authorName = $this->getAuthorNameByAddress($address);
                if(strlen($userName . $authorName) == 0)
                {
                    continue;
                }
                if(strlen($userName) > 0)
                {
                    array_push($excludeUserDataList, array("display" => $userName." ".$address, "value" => $address));
                }
                else
                {
                    array_push($excludeUserDataList, array("display" => $authorName." ".$address, "value" => $address));
                }
                if(strlen($excludeUserDataText) > 0)
                {
                    $excludeUserDataText .= ",";
                }
                $excludeUserDataText .= $address;
            }
            
            if($this->edit_type == "addExcludeAddress")
            {
                if(strlen(trim($this->feedback_exclude_address_add)) > 0)
                {
                    if(in_array($this->feedback_exclude_address_add, $excludeAddressList))
                    {
                        $this->Session->setParameter("error_msg", "Same email address has already been registered.");
                    }
                    else
                    {
                        $this->feedback_exclude_address_add = trim($this->feedback_exclude_address_add);
                        $userName = $this->getUserNameByAddress($this->feedback_exclude_address_add);
                        $authorName = $this->getAuthorNameByAddress($this->feedback_exclude_address_add);
                        if(strlen($userName) > 0)
                        {
                            array_push($excludeUserDataList, array("display" => $userName." ".$this->feedback_exclude_address_add, "value" => $this->feedback_exclude_address_add));
                            if(strlen($excludeUserDataText) > 0)
                            {
                                $excludeUserDataText .= ",";
                            }
                            $excludeUserDataText .= $this->feedback_exclude_address_add;
                        }
                        else if(strlen($authorName) > 0)
                        {
                            array_push($excludeUserDataList, array("display" => $authorName." ".$this->feedback_exclude_address_add, "value" => $this->feedback_exclude_address_add));
                            if(strlen($excludeUserDataText) > 0)
                            {
                                $excludeUserDataText .= ",";
                            }
                            $excludeUserDataText .= $this->feedback_exclude_address_add;
                        }
                        else
                        {
                            $this->Session->setParameter("error_msg", "Could not find a user or a author with the email address that you specify.");
                        }
                    }
                }
            }
            else if($this->edit_type == "killSendFeedbackMailProcess")
            {
                $this->infoLog("businessSendusagestatisticsmail", __FILE__, __CLASS__, __LINE__);
                $repositoryUsagestatisticsmail = BusinessFactory::getFactory()->getBusiness("businessSendusagestatisticsmail");
                $repositoryUsagestatisticsmail->killProcess();
                // 引数は一番最後以外ダミー（リファレンス渡しになっているので一応変数を書いている）
                $status = $repositoryUsagestatisticsmail->openProgressFile($mailAddress, $orderNum, $isAuhtor, $authorId, false);
                $this->Session->setParameter("sendFeedbackStatus", $status);
            }
            $this->Session->setParameter("excludeUserDataList", $excludeUserDataList);
            $this->Session->setParameter("excludeUserDataText", $excludeUserDataText);
            // Add send feedback mail 2012/08/24 A.Suzuki --end--
            
            // Add ichuushi fill 2012/11/21 A.jin --start--
            if($this->ichushiIsConnect == null){
                $flg = 0;
            } else {
                $flg = 1;
            }
            $admin_params["ichushi_is_connect"]["param_value"] = $flg;
            $admin_params["ichushi_login_id"]["param_value"] = $this->ichushiLoginId;
            $admin_params["ichushi_login_passwd"]["param_value"] = $this->ichushiLoginPasswd;
            //再描画時に入力値がクリアされる問題対応 A.Jin 2012/11/30 --start--
            $this->Session->setParameter("is_display_passwd",0);
            $this->Session->setParameter("ichushi_checked",$flg);
            $this->Session->setParameter("ichushi_login_id",$this->ichushiLoginId);
            $this->Session->setParameter("ichushi_login_passwd",$this->ichushiLoginPasswd);
            //再描画時に入力値がクリアされる問題対応 A.Jin 2012/11/30 --end--
            // Add ichuushi fill 2012/11/21 A.jin --end--
            
            // OAI-PMH Output Flag
            $admin_params["oaipmh_output_flag"]["param_value"] = $this->oaipmh_output_flag;
            
            // Institution Name
            $admin_params["institution_name"]["param_value"] = $this->institutionName;
            
            // Add Default Search Type 2014/12/03 K.Sugimoto --start--
            // Default Search Type
            $admin_params["default_search_type"]["param_value"] = $this->default_search_type;
            // Add Default Search Type 2014/12/03 K.Sugimoto --end--

            // Add Usage Statistics link display setting 2014/12/16 K.Matsushita --start--
            $admin_params["usagestatistics_link_display"]["param_value"] = $this->usagestatistics_link_display;
            // Add Usage Statistics link display setting 2014/12/16 K.Matsushita --end--

            // Add ranking tab display setting 2014/12/19 K.Matsushita --start--
            $admin_params["ranking_tab_display"]["param_value"] = $this->ranking_tab_display;
            // Add ranking tab display setting 2014/12/19 K.Matsushita --end--
            
            // Add DataCite 2015/02/09 K.Sugimoto --start--
            // PrefixID Add Flag
            $admin_params["prefix_flag"]["param_value"] = $this->prefix_flag;
            
            // Prefix
            $admin_params["prefixCnri"]["param_value"] = $this->prefixCnri;
            $admin_params["prefixJalcDoi"]["param_value"] = $this->prefixJalcDoi;
            $admin_params["prefixCrossRef"]["param_value"] = $this->prefixCrossRef;
            $admin_params["prefixDataCite"]["param_value"] = $this->prefixDataCite;
            // Add DataCite 2015/02/09 K.Sugimoto --end--

		    // Auto Input Metadata by CrossRef DOI 2015/03/02 K.Sugimoto --start--
            $admin_params["CrossRefQueryServicesAccount"]["param_value"] = $this->CrossRefQueryServicesAccount;
		    // Auto Input Metadata by CrossRef DOI 2015/03/02 K.Sugimoto --end--
            
            $this->Session->setParameter("admin_params", $admin_params);
            
			// アクション終了処理
			$result = $this->exitAction();	// トランザクションが成功していればCOMMITされる
			return 'success';
		}
		catch ( RepositoryException $Exception) {
			//エラーログ出力
			$this->logFile(
				"SampleAction",					//クラス名
				"execute",						//メソッド名
				$Exception->getCode(),			//ログID
				$Exception->getMessage(),		//主メッセージ
				$Exception->getDetailMsg() );	//詳細メッセージ			
			//アクション終了処理
	  		$this->exitAction();				   //トランザクションが失敗していればROLLBACKされる		
			//異常終了
			return "error";
		}
	}
}
?>
