<?php
// --------------------------------------------------------------------
//
// $Id: Confirm.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
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
require_once WEBAPP_DIR. '/modules/repository/components/ItemRegister.class.php';

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_View_Main_Item_Confirm extends RepositoryAction
{
    var $error_msg = array();        // エラーメッセージ
    var $warning = "";        // 警告
    
    // for Linefeed of textarea 
    var $Session = null;
    var $Db = null;
    var $textarea_data = array();
    var $link_data = array();
    var $license_data = array();
    
    // Set help icon setting 2010/02/10 K.Ando --start--
    var $help_icon_display =  "";
    // Set help icon setting 2010/02/10 K.Ando --end--
    
    // Add contents page Y.Nakao 2010/08/06 --start--
    var $heading = array();
    // Add contents page Y.Nakao 2010/08/06 --end--
    
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
        
        // Add theme_name for image file Y.Nakao 2011/08/03 --start--
        $this->setThemeName();
        // Add theme_name for image file Y.Nakao 2011/08/03 --end--
        
        $attr_type = $this->Session->getParameter("item_attr_type");
        $item_attr = $this->Session->getParameter("item_attr");
        $license_master = $this->Session->getParameter("license_master");
        
        $this->textarea_data = array();
        
        for($ii=0; $ii<count($attr_type); $ii++){
            if($attr_type[$ii]['input_type'] == "textarea"){
                $tmp_textarea_data = array();
                for($jj=0; $jj<count($item_attr[$ii]); $jj++){
                    $textarea_array = explode("\n", $item_attr[$ii][$jj]);
                    array_push($tmp_textarea_data, $textarea_array);
                }
                array_push($this->textarea_data, array($ii, $tmp_textarea_data));
            } else if($attr_type[$ii]['input_type'] == "link"){
                for($jj=0; $jj<count($item_attr[$ii]); $jj++){
                    $this->link_data[$ii][$jj] = explode("|", $item_attr[$ii][$jj], 2);
                }
            } else if($attr_type[$ii]['input_type'] == "file" || $attr_type[$ii]['input_type'] == "file_price"){
                for($jj=0; $jj<count($item_attr[$ii]); $jj++){
                    if(isset($item_attr[$ii][$jj]['licence']))
                    {
                        if($item_attr[$ii][$jj]['licence'] !== "licence_free"){
                            foreach($license_master as $kk){
                                if($kk['license_id'] == $item_attr[$ii][$jj]['license_id']){
                                    $this->license_data[$ii][$jj]['img_url'] = $kk['img_url'];
                                    $this->license_data[$ii][$jj]['text_url'] = $kk['text_url'];
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            // Add contents page Y.Nakao 2010/08/06 --start--
            else if($attr_type[$ii]['input_type'] == 'heading'){
                for($jj=0; $jj<count($item_attr[$ii]); $jj++){
                    $this->heading[$jj] = explode("|", $item_attr[$ii][$jj], 4);
                }
            }
            // Add contents page Y.Nakao 2010/08/06 --end--
        }
        
        // Add registered info save action 2009/02/13 Y.Nakao --start--
        // check all input data
        $base_attr = $this->Session->getParameter("base_attr");
        $item_pub_date = $this->Session->getParameter("item_pub_date");
        $item_attr_type = $this->Session->getParameter("item_attr_type");
        $item_num_attr = $this->Session->getParameter("item_num_attr");
        $item_attr = $this->Session->getParameter("item_attr");
        $item_attr_type = $this->Session->getParameter("item_attr_type");
        $indice = $this->Session->getParameter("indice");
        // 基本情報チェック
        $this->error_msg = array();
        $this->warning = '';
        if($this->Session->getParameter("error_msg") != null){
            // addDBでのエラー
            $this->error_msg = $this->Session->getParameter("error_msg");
        }
        $ItemRegister = new ItemRegister($this->Session, $this->Db);
        $item = array();
        $item["item_id"] = intval($this->Session->getParameter("edit_item_id"));
        $item["item_no"] = intval($this->Session->getParameter("edit_item_no"));
        $item['title'] = $base_attr["title"];
        $item['title_english'] = $base_attr["title_english"];
        //Add language 2009/08/26 K.Ito --start--
        $item['language'] = $base_attr["language"];
        //Add language 2009/08/26 K.Ito --end--
        $item['pub_year'] = $item_pub_date["year"];
        $item['pub_month'] = $item_pub_date["month"];
        $item['pub_day'] = $item_pub_date["day"];
        $ItemRegister->checkBaseInfo($item, $this->error_msg, $this->warning);
        $ItemRegister->checkEntryInfo($item_attr_type, $item_num_attr, $item_attr, 'all', $this->error_msg, $this->warning);
        $ItemRegister->checkIndex($indice, $this->error_msg, $this->warning);
        
        // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --start--
        $contributorErrorMsg = $this->Session->getParameter("contributorErrorMsg");
        if(strlen($contributorErrorMsg) > 0)
        {
            array_push($this->error_msg, $contributorErrorMsg);
        }
        // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --end--
        
        // 
        $this->Session->removeParameter('item_entry_flg');
        if(count($this->error_msg) == 0){
            // entry OK
            $this->Session->setParameter('item_entry_flg', 'true');
        } else {
            // entry NG
            $this->Session->setParameter('item_entry_flg', 'false');
        }
        
        // Add registered info save action 2009/02/13 Y.Nakao --end--
        
        // Add review mail setting 2009/09/30 Y.Nakao --start--
        $this->setLangResource();
        // Add review mail setting 2009/09/30 Y.Nakao --end--
        
        // Set help icon setting 2010/02/10 K.Ando --start--
        $result = $this->getAdminParam('help_icon_display', $this->help_icon_display, $Error_Msg);
        if ( $result == false ){
            $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );    //主メッセージとログIDを指定して例外を作成
            $DetailMsg = null;                              //詳細メッセージ文字列作成
            sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
            $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
            $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
            throw $exception;
        }
        // Set help icon setting 2010/02/10 K.Ando --end--
        return 'success';
    }
}
?>
