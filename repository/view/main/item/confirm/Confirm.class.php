<?php
// --------------------------------------------------------------------
//
// $Id: Confirm.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
require_once WEBAPP_DIR. '/modules/repository/components/common/WekoAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/ItemRegister.class.php';

/**
 * アイテム登録：確認画面表示
 *
 * @access      public
 */
class Repository_View_Main_Item_Confirm extends WekoAction
{
    // 表示用パラメーター
    /**
     * input_type : textarea 表示用配列
     * @var array
     */
    public $textarea_data = array();
    
    /**
     * input_type : link 表示用配列
     * @var array
     */
    public $link_data = array();
    
    /**
     * ファイルライセンス情報表示用配列
     * @var array
     */
    public $license_data = array();
    
    /**
     * input_type : heading 表示用配列
     * @var array
     */
    public $heading = array();
    
    /**
     * ヘルプアイコン表示フラグ
     * @var string
     */
    public $help_icon_display =  "";
    
    // リクエストパラメーター
    /**
     * 警告メッセージ配列
     * @var array
     */
    public $warningMsg = null;
    
    /**
     * 実行処理
     * @see ActionBase::executeApp()
     */
    protected function executeApp()
    {
        if(!is_array($this->warningMsg)){
            $this->warningMsg = array();
        }
        
        // RepositoryActionのインスタンス
        $repositoryAction = new RepositoryAction();
        $repositoryAction->Session = $this->Session;
        $repositoryAction->Db = $this->Db;
        $repositoryAction->dbAccess = $this->Db;
        $repositoryAction->TransStartDate = $this->accessDate;
        $repositoryAction->setLangResource();
        $smartyAssign = $this->Session->getParameter("smartyAssign");
        
        // セッション情報取得
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
            else if($attr_type[$ii]['input_type'] == 'heading'){
                for($jj=0; $jj<count($item_attr[$ii]); $jj++){
                    $this->heading[$jj] = explode("|", $item_attr[$ii][$jj], 4);
                }
            }
        }
        
        $this->Session->removeParameter('item_entry_flg');
        if(count($this->errMsg) == 0){
            // entry OK
            $this->Session->setParameter('item_entry_flg', 'true');
        } else {
            // entry NG
            $this->Session->setParameter('item_entry_flg', 'false');
        }
        
        // Set help icon setting
        $tmpErrorMsg = "";
        $result = $repositoryAction->getAdminParam('help_icon_display', $this->help_icon_display, $tmpErrorMsg);
        if ( $result === false ){
            $this->errorLog($tmpErrorMsg, __FILE__, __CLASS__, __LINE__);
            $exception = new AppException($tmpErrorMsg);
            $exception->addError($tmpErrorMsg);
            throw $exception;
        }
        
        return 'success';
    }
}
?>
