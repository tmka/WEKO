<?php
// --------------------------------------------------------------------
//
// $Id: Adddb.class.php 48623 2015-02-18 11:48:38Z tomohiro_ichikawa $
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
require_once WEBAPP_DIR. '/modules/repository/components/ItemtypeManager.class.php';

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_Action_Edit_Itemtype_Auth_Adddb extends RepositoryAction
{

    var $item_type_id = null;
    var $exclusive_base_auth = null;
    var $exclusive_room_auth = null;
    
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function executeApp()
    {
        // アイテムタイプ管理クラス
        $itemtypeManager = new Repository_Components_ItemtypeManager($this->Session, $this->Db, $this->TransStartDate);
        
        // カンマ区切りのベース権限文字列を配列にする
        if(strlen($this->exclusive_base_auth) > 0) {
            $base_auth = explode(",", $this->exclusive_base_auth);
        } else {
            // 空配列
            $base_auth = array();
        }
        
        // アイテムタイプ権限をDBに登録する
        $result = $itemtypeManager->setExclusiveItemtypeAuthority($this->item_type_id, $base_auth, $this->exclusive_room_auth);
        if($result) {
            $this->Session->setParameter("redirect_flg", "itemtype");
            return "redirect";
        } else {
            echo "error";
            return "error";
        }
    }
}
?>
