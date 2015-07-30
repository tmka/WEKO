<?php
// --------------------------------------------------------------------
//
// $Id: Editlinks.class.php 39149 2014-07-28 08:37:06Z rei_matsuura $
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

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_View_Main_Item_Editlinks extends RepositoryAction
{
    // 使用コンポーネントを受け取るため
    var $Session = null;
    var $Db = null;
    
    //メンバ変数
    var $relationArray = array();   // relation選択肢
    var $error_msg = array();       // エラーメッセージ
    var $warning = "";      // 警告
    var $relationArray_count = 0;   // relation個数
    
    // Set help icon setting 2010/02/10 K.Ando --start--
    var $help_icon_display =  "";
    // Set help icon setting 2010/02/10 K.Ando --end--
    
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
        $istest = true;             // テスト用フラグ
        try {           
            //アクション初期化処理
            $result = $this->initAction();
            if ( $result === false ) {
                $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 ); //主メッセージとログIDを指定して例外を作成
                $DetailMsg = null;                              //詳細メッセージ文字列作成
                sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
                $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
                throw $exception;
            }
            
            // Add addin S.Arata 2012/08/02 --start--
            $this->addin->preExecute();
            // Add addin S.Arata 2012/08/02 --end--
            
            // Add relation options 2008/10/16 A.Suzuki --start--
            $container =& DIContainerFactory::getContainer();
            $filterChain =& $container->getComponent("FilterChain");
            $smartyAssign =& $filterChain->getFilterByName("SmartyAssign");
            
            $this->relationArray = array(
                //languageリソースから項目を取得する
                array("isVersionOf", $smartyAssign->getLang("repository_item_relation_is_version_of")),
                array("hasVersion", $smartyAssign->getLang("repository_item_relation_has_version")),
                array("isReplacedBy", $smartyAssign->getLang("repository_item_relation_is_replaced_by")),
                array("replaces", $smartyAssign->getLang("repository_item_relation_replaces")),
                array("isRequiredBy", $smartyAssign->getLang("repository_item_relation_is_required_by")),
                array("requires", $smartyAssign->getLang("repository_item_relation_requires")),
                array("isPartOf", $smartyAssign->getLang("repository_item_relation_is_part_of")),
                array("hasPart", $smartyAssign->getLang("repository_item_relation_has_part")),
                array("isReferencedBy", $smartyAssign->getLang("repository_item_relation_is_referenced_by")),
                array("references", $smartyAssign->getLang("repository_item_relation_references")),
                array("isFormatOf", $smartyAssign->getLang("repository_item_relation_is_format_of")),
                array("hasFormat", $smartyAssign->getLang("repository_item_relation_has_format"))
            );
            $this->Session->setParameter("relationArray", $this->relationArray);
            $this->relationArray_count = count($this->relationArray);
            // Add relation options 2008/10/16 A.Suzuki --end--
            
            // ツリー開閉情報
            $indice = $this->Session->getParameter("indice");
            $open_ids = $this->Session->getParameter("view_open_node_index_id_item_link");
            if(strlen($open_ids) > 0)
            {
                for($ii=0; $ii<count($indice); $ii++){
                    $parent_index = array();
                    $this->getParentIndex($indice[$ii]["index_id"], $parent_index);
                    for($jj=0; $jj<count($parent_index); $jj++){
                        if(!is_numeric(strpos($open_ids, ",".$parent_index[$jj]["index_id"].","))){
                            if(substr_count($open_ids, ",".$parent_index[$jj]["index_id"].",")==0 &&
                              $parent_index[$jj]["index_id"] != $indice[$ii]["index_id"]){
                                $open_ids .= ",".$parent_index[$jj]["index_id"].",";
                            }
                        }
                    }
                }
                $this->Session->setParameter("view_open_node_index_id_item_link", $open_ids);
            }
            
            // セッションからエラーメッセージのコピー
            $this->error_msg = $this->Session->getParameter("error_msg");
            if(!is_array($this->error_msg))
            {
                $this->error_msg = array();
            }
            $this->Session->removeParameter("error_msg");
            $this->setLangResource();
            // Bugfix error_meg 2011/06/03 Y.Nakao --start--
            if($this->Session->getParameter("link_search_error") != null){
                array_push($this->error_msg, sprintf($this->Session->getParameter("smartyAssign")->getLang("repository_item_link_search_error"), $this->Session->getParameter("link_search_error")));
            }
            if($this->Session->getParameter("link_search_no_item") != null){
                array_push($this->error_msg, sprintf($this->Session->getParameter("smartyAssign")->getLang("repository_item_link_search_no_item"), $this->Session->getParameter("link_search_no_item")));
            }
            // Bugfix error_meg 2011/06/03 Y.Nakao --end--
            $this->Session->removeParameter("link_search_error");
            $this->Session->removeParameter("link_search_no_item");
            $this->warning = $this->Session->getParameter("warning");
            $this->Session->removeParameter("warning");
            
            $this->Session->removeParameter("edit_jalc_flag");

            // Set help icon setting 2010/02/10 K.Ando --start--
            $result = $this->getAdminParam('help_icon_display', $this->help_icon_display, $Error_Msg);
            if ( $result == false ){
                $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 ); //主メッセージとログIDを指定して例外を作成
                $DetailMsg = null;                              //詳細メッセージ文字列作成
                sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
                $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
                throw $exception;
            }
            // Set help icon setting 2010/02/10 K.Ando --end--
            
            // Add addin S.Arata 2012/08/02 --start--
            $this->addin->postExecute();
            // Add addin S.Arata 2012/08/02 --end--
            
            // アクション終了処理
            $result = $this->exitAction();  // トランザクションが成功していればCOMMITされる
            if ( $result == false ){
                $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 ); //主メッセージとログIDを指定して例外を作成
                $DetailMsg = null;                              //詳細メッセージ文字列作成
                sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
                $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
                throw $exception;
            }
            return 'success';
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
}
?>
