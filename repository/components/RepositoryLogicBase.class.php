<?php
// --------------------------------------------------------------------
//
// $Id: RepositoryLogicBase.class.php 30197 2013-12-19 09:55:45Z rei_matsuura $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

require_once WEBAPP_DIR. '/modules/repository/components/RepositoryConst.class.php';

class RepositoryLogicBase
{

    /**
     * セッション管理
     *
     * @var session
     */
    protected $Session = null;
    
    /**
     * データベースアクセスクラス
     *
     * @var dbAccess
     */
    protected $dbAccess = null;
    
    /**
     * トランザクション開始日時
     *
     * @var transStartDate
     */  
    protected $transStartDate = '';
    
    /**
     * initialize
     *
     * @param var $Session Session
     * @param var $db DbObjectAdodb or RepositoryDbAccess
     * @param string $TransStartDate TransStartDate
     */
    protected function __construct($session, $db, $startDate)
    {
        // session
        if($session == null)
        {
            throw new InvalidArgumentException("RepositoryLogicBase : Failed construct, but argument at SessionObject.");
        }
        $this->Session = $session;
        
        // database
        if($db == null)
        {
            throw new InvalidArgumentException("RepositoryLogicBase : Failed construct, but argument at DbObjectAdodb.");
        }
        // check class format
        if(is_a($db, 'DbObjectAdodb'))
        {
            $this->dbAccess = new RepositoryDbAccess($db);
        }
        else if(is_a($db, 'RepositoryDbAccess'))
        {
            $this->dbAccess = $db;
        }
        else
        {
            throw new InvalidArgumentException("RepositoryLogicBase : Failed construct, but argument at DbObjectAdodb.");
        }
        
        // transStartDate
        if($startDate == null || strlen($startDate) < 1)
        {
            throw new InvalidArgumentException("RepositoryLogicBase : Failed construct, but argument at transStartDate.");
        }
        $this->transStartDate = $startDate;
    }
    
    /**
     * クエリパラメータに共通項目を追加する（挿入）
     * 追加するパラメータは
     * 作成ユーザーID、
     * 更新ユーザーID、
     * 作成日時、
     * 更新日時、
     * 削除フラグ
     *
     * @param array $params クエリ用パラメータ
     */
    protected function addSystemPramsForInsert(&$params)
    {
        $userId = $this->Session->getParameter("_user_id");
        $params[] = $userId;                // ins_user_id
        $params[] = $userId;                // mod_user_id
        $params[] = $this->transStartDate;  // ins_date
        $params[] = $this->transStartDate;  // mod_date
        $params[] = 0;                      // is_delete
    }
    
    /**
     * クエリパラメータに共通項目を追加する（更新）
     * 追加するパラメータは
     * 更新ユーザーID、
     * 更新日時、
     * 削除フラグ
     *
     * @param array $params クエリ用パラメータ
     */
    protected function addSystemPramsForUpdate(&$params)
    {
        $params[] = $this->Session->getParameter("_user_id");   // mod_user_id
        $params[] = $this->transStartDate;                      // mod_date
    }
    
    /**
     * クエリパラメータに共通項目を追加する（削除）
     * 追加するパラメータは
     * 更新ユーザーID、
     * 更新日時、
     * 削除フラグ
     *
     * @param array $params クエリ用パラメータ
     */
    protected function addSystemPramsForDelete(&$params)
    {
        $userId = $this->Session->getParameter("_user_id");
        $params[] = $userId;                // mod_user_id
        $params[] = $userId;                // del_user_id
        $params[] = $this->transStartDate;  // mod_date
        $params[] = $this->transStartDate;  // del_date
        $params[] = 1;                      // is_delete
    }
}
?>
