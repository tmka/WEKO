<?php
// --------------------------------------------------------------------
//
// $Id: Upload.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
//require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
//class Repository_Action_Edit_Importauthority_Upload extends RepositoryAction
class Repository_Action_Edit_Importauthority_Upload
{

    public $Session = null;
    public $Db = null;
    public $uploadsAction = null;
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    public function execute()
    {
        // ガーベージフラグが"1"の場合、いつかファイル・DB共にクリアしてくれる。
        // ただし、詳細なタイミングは不明。
        $garbage_flag = 1;

        // アップロードしたファイルの情報を取得する。
        // 形式はuploadテーブルをSELECT *した結果と同等。
        $filelist = $this->uploadsAction->uploads($garbage_flag);
        for ($ii = 0; $ii < count($filelist); $ii++){
            if(!isset($filelist[$ii]))
            {
                continue;
            }
            if ($filelist[$ii]['upload_id'] === 0) {
                return false;
            }
        }

        // sessionにアップロードしたファイルの情報を設定
        $this->Session->setParameter("filelist", $filelist);
        //'success'ではなく、trueを返す
        return true;
    }
}
?>
