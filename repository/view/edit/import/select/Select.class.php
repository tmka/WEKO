<?php
// --------------------------------------------------------------------
//
// $Id: Select.class.php 43857 2014-11-11 08:54:28Z tomohiro_ichikawa $
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
class Repository_View_Edit_Import_Select extends RepositoryAction
{
    // component
    var $Session = null;
    // member
    var $error_msg = "";
    
    // Set help icon setting 2010/02/10 K.Ando --start--
    var $help_icon_display =  null;
    // Set help icon setting 2010/02/10 K.Ando --end--
    
    
    // Add specialized support for open.repo "auto affiliation in private tree" Y.Nakao 2013/06/26 --start--
    public $selectedIndexIds  = "";
    public $selectedIndexName = array();
    // Add specialized support for open.repo "auto affiliation in private tree" Y.Nakao 2013/06/26 --end--
    
    // Add e-person R.Matsuura 2013/11/25 --start--
    public $import_active_tab = null;
    // Add e-person R.Matsuura 2013/11/25 --end--
    
    public $error_info = array();
    
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
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
            
            // get error msg
            $this->error_msg = $this->Session->getParameter("error_msg");
            $this->Session->removeParameter("error_msg");
            $this->Session->removeParameter("Error_Msg");
                        
            // Add for import error list 2014/11/04 T.Koyasu --start--
            // get error info
            $this->error_info = $this->Session->getParameter("error_info");
            $this->Session->removeParameter("error_info");
            // Add for import error list 2014/11/04 T.Koyasu --end--
            
            // タブ切替時にセッションの初期化
            $this->Session->removeParameter("indice");
            $this->Session->removeParameter("open_node_index_id_index");
            $this->Session->removeParameter("error_msg");
            
            // 表示中タブ情報
            if($this->import_active_tab == ""){
                $this->import_active_tab = $this->Session->getParameter("import_active_tab");
                if($this->import_active_tab == ""){
                    $this->import_active_tab = 0;
                }
            }
            $this->Session->removeParameter("import_active_tab");
            
            // Add specialized support for open.repo "auto affiliation in private tree" Y.Nakao 2013/06/26 --start--
            $indice = array();
            $indice = $this->addPrivateTreeInPositionIndex($indice);
            $this->Session->setParameter("indice", $indice);
            for($ii=0; $ii<count($indice); $ii++)
            {
                if(strlen($this->selectedIndexIds) > 0)
                {
                    $this->selectedIndexIds .= "|";
                }
                $this->selectedIndexIds .= $indice[$ii][RepositoryConst::DBCOL_REPOSITORY_INDEX_INDEX_ID];
                
                array_push($this->selectedIndexName, $indice[$ii][RepositoryConst::DBCOL_REPOSITORY_INDEX_INDEX_NAME]);
            }
            // Add specialized support for open.repo "auto affiliation in private tree" Y.Nakao 2013/06/26 --end--
            
            // Add review mail setting 2009/09/30 Y.Nakao --start--
            $this->setLangResource();
            // Add review mail setting 2009/09/30 Y.Nakao --end--
            
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
