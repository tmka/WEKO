<?php
// --------------------------------------------------------------------
//
// $Id: RepositoryDbAccess.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';

/**
 * Repository Db Access Class
 * 
 */
class RepositoryDbAccess
{
    /**
     * Database Constance
     *
     * @var DbContainer
     */
    private $Db = null;
    
    /**
     * constructor
     * 
     * @param $Db DbObjectAdodb
     */
    public function __construct(DbObjectAdodb $Db)
    {
        if($Db == null)
        {
            throw new InvalidArgumentException("RepositoryDbAccess : Failed construct, but argument at DbObjectAdodb.");
        }
        $this->Db = $Db;
    }
    
    /**
     * Execute Query
     *
     * @var $query : query of sql
     * @var $params : array(0=>param1, 1=>param2, ...)
     * 
     * @return array() or boolean : $result
     */
    public function executeQuery($query, $params = array())
    {
        $result = $this->Db->execute($query, $params);
        if($result === false)
        {
            // Set Error Msg
            $errMsg =   $query. '\n'. 
                        print_r($params, true). '\n'. 
                        $this->Db->ErrorMsg();
            
            $exception = new RepositoryException( 'ERR_MSG_EXECUTE_QUERY', 00001 );
            $exception->setDetailMsg($errMsg);
            
            throw $exception;
        }
        
        return $result;
    }
    /**
     * 更新・削除レコード数取得
     * @return integer : 更新/削除レコード数
     *         bool    : false 更新/削除レコード無し または未サポート
     * @access public
     */
    public function affectedRows() {
        $result = $this->Db->affectedRows();
        return $result;
    }
    /**
     * LOB更新用
     * @param    string     $tableName       対象テーブル名称
     * @param    string   $column          カラム名称
     * @param    string   $path　　　　　　パス
     * @param    array    $where
     * @param    string   $blobtype
     * @return boolean true or false
     * @access    public
     */
    function updateBlobFile($tableName, $column, $path, $where, $blobtype='BLOB') {
        $result = $this->Db->UpdateBlobFile($tableName, $column, $path, $where, $blobtype);
        return $result;
    }
    /**
     * 最後の状態あるいはエラーメッセージを返します。
     *
     * @return    srting    エラーメッセージ
     * @access    public
     */
    function ErrorMsg() {
        return $this->Db->ErrorMsg();
    }
    
    /**
     * Db object getter
     *
     * @return DbObjectAdodb
     */
    public function getDb()
    {
        return $this->Db;
    }

}
?>