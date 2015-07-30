<?php
// --------------------------------------------------------------------
//
// $Id: Multilang.class.php 15605 2012-02-20 09:30:55Z tatsuya_koyasu $
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
class Repository_Action_Edit_Itemtype_Multilang extends RepositoryAction
{
    // 2008/02/25 itemtype を item_type に変更
    // 使用コンポーネントを受け取るため
    var $Session = null;
    var $Db = null;
    
    // リクエストパラメータを受け取るため
    // リクエストパラメタ
    public $metadata_multititle = null;     // メタデータ項目名配列
    // リクエストパラメタ
    public $metadata_defaulttitle = null;     // メタデータ項目名配列
    // リクエストパラメタ
    public $default_edit_flag = null;     // デフォルトを変更するかのフラグ
    // リクエストパラメタ
    public $edit_id = null;     // 編集データID
    
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
                $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );    //主メッセージとログIDを指定して例外を作成
                $DetailMsg = null;                              //詳細メッセージ文字列作成
                sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                $exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                $this->failTrans();                                        //トランザクション失敗を設定(ROLLBACK)
                throw $exception;
            }

            $this->Session->removeParameter("error_code");
            $array_metadata_title = $this->Session->getParameter("metadata_title");
            $array_metadata_multi_title = $this->Session->getParameter("metadata_multi_title");
            if($this->default_edit_flag != "false"){
                $array_metadata_title[$this->edit_id] = $this->metadata_defaulttitle;
            }
            foreach($this->metadata_multititle as $key => $value){
                $array_metadata_multi_title[$this->edit_id][$key] = $value;
            }
            $this->Session->setParameter("metadata_title", $array_metadata_title);
            $this->Session->setParameter("metadata_multi_title", $array_metadata_multi_title);
            
            //アクション終了処理
            $result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
            if ( $result === false ) {
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );    //主メッセージとログIDを指定して例外を作成
                //$DetailMsg = null;                              //詳細メッセージ文字列作成
                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx3, $埋込み文字1, $埋込み文字2 );
                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                throw $exception;
            }
            return 'success';
        } catch (RepositoryException $Exception) {
            //アクション終了処理
            $result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
        
            return "error";
        }

    }
}
?>
