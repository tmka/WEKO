<?php
// --------------------------------------------------------------------
//
// $Id: RepositoryHandleManager.class.php 43083 2014-10-20 06:45:58Z keiya_sugimoto $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics,
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryLogicBase.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/IDServer.class.php';
/**
 * repository handle IDs management class
 * 
 */
class RepositoryHandleManager extends RepositoryLogicBase
{
    const ID_LIBRARY_JALC_DOI = 50;
    const ID_JALC_DOI = 40;
    const ID_CROSS_REF_DOI = 30;
    const ID_CNRI_HANDLE = 20;
    const ID_Y_HANDLE = 10;
    const PREFIX_DOI = "http://doi.org/";
    const PREFIX_CNRI = "http://hdl.handle.net/";
    const PREFIX_Y_HANDLE = "http://id.nii.ac.jp/";
    const PREFIX_SELF_DOI = "info:doi/";
    const PREFIX_LIBRARY_DOI_INFO = "info:doi.org/";
    const PREFIX_LIBRARY_DOI_HTTP = "http://doi.org/";
    const PREFIX_LIBRARY_DOI_DOI = "doi:";
    const DOI_STATUS_GRANTED = 1;
    const DOI_STATUS_DROPED = 2;
    
    /**
     * Constructor
     *
     * @param var $session
     * @param var $dbAccess
     * @param string $TransStartDate
     */
    public function __construct($session, $dbAccess, $transStartDate)
    {
        parent::__construct($session, $dbAccess, $transStartDate);
    }
    
    /**
     * Register Y handle prefix
     * 
     * @param string $captcha_string
     * @param string $auth_session_ide
     * @return bool
     */
    public function registerYHandlePrefix($captcha_string, $auth_session_ide)
    {
        $IDServer = new IDServer($this->Session, $this->dbAccess->getDb());
        
        $YHandlePrefix = $IDServer->postCaptchaString($captcha_string, $auth_session_ide);
        // Bug fix WEKO-2014-006 2014/04/28 T.Koyasu --start--
        // return value is string, mismatch method of compare
        if(strcmp($YHandlePrefix, "false") === 0) {
            return false;
        }
        // Bug fix WEKO-2014-006 2014/04/28 T.Koyasu --end--
        
        $this->registerPrefixById(self::ID_Y_HANDLE, $YHandlePrefix);
        
        return true;
    }
    
    /**
     * Get prefix
     * 
     * @param var $prefix_id
     * @return string
     */
    public function getPrefix($prefix_id)
    {
        $query = "SELECT prefix_id".
                 " FROM ".DATABASE_PREFIX."repository_prefix".
                 " WHERE prefix_id IS NOT NULL".
                 " AND prefix_id != ''".
                 " AND id = ?".
                 " AND is_delete = ?".
                 " ;";
        $params = array();
        $params[] = $prefix_id;
        $params[] = 0;
        $result = $this->dbAccess->executeQuery($query, $params);
        if(count($result) == 0) {
            return "";
        }
        
        return $result[0]['prefix_id'];
    }
    
    /**
     * Get LibraryJalcDoi prefix
     * 
     * @return string
     */
    public function getLibraryJalcDoiPrefix()
    {
        $result = $this->getPrefix(self::ID_LIBRARY_JALC_DOI);
        
        return $result;
    }
    
    /**
     * Get JalcDoi prefix
     * 
     * @return string
     */
    public function getJalcDoiPrefix()
    {
        $result = $this->getPrefix(self::ID_JALC_DOI);
        
        return $result;
    }
    
    /**
     * Get CrossRef prefix
     * 
     * @return string
     */
    public function getCrossRefPrefix()
    {
        $result = $this->getPrefix(self::ID_CROSS_REF_DOI);
        
        return $result;
    }
    
    /**
     * Get Cnri prefix
     * 
     * @return string
     */
    public function getCnriPrefix()
    {
        $result = $this->getPrefix(self::ID_CNRI_HANDLE);
        
        return $result;
    }
    
    /**
     * Get Y handle prefix
     * 
     * @return string
     */
    public function getYHandlePrefix()
    {
        $result = $this->getPrefix(self::ID_Y_HANDLE);
        
        return $result;
    }
    
    /**
     * Register prefix
     * 
     * @param string $jalcDoiPrefix
     * @param string $crossRefDoiPrefix
     * @param string $cnriPrefix
     */
    public function registerPrefix($jalcDoiPrefix, $crossRefDoiPrefix, $cnriPrefix)
    {
        $this->registerPrefixById(self::ID_JALC_DOI, $jalcDoiPrefix);
        $this->registerPrefixById(self::ID_CROSS_REF_DOI, $crossRefDoiPrefix);
        $this->registerPrefixById(self::ID_CNRI_HANDLE, $cnriPrefix);
    }
    
    /**
     * Register Y handle suffix
     * 
     * @param string $title
     * @param int $item_id
     * @param int $item_no
     */
    public function registerYhandleSuffix($title, $item_id, $item_no)
    {
        $IDServer = new IDServer($this->Session, $this->dbAccess->getDb());
        
        $url = $IDServer->getSuffix($title, $item_id, $this->transStartDate);
        $pre = $this->getPrefix(self::ID_Y_HANDLE);
        if(empty($pre)) {
            return false;
        }
        
        $suf = str_replace(self::PREFIX_Y_HANDLE, "", $url);
        $suf = str_replace($pre, "", $suf);
        $suf = str_replace("/", "", $suf);
        
        $query = "INSERT INTO ".DATABASE_PREFIX."repository_suffix".
                 " (item_id, item_no, id, suffix, ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete)".
                 " VALUES".
                 " (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)".
                 " ON DUPLICATE KEY ". 
                 " UPDATE suffix = ?, mod_user_id = ?, mod_date = ? ". 
                 " ;";
        $params = array();
        $params[] = $item_id;
        $params[] = $item_no;
        $params[] = self::ID_Y_HANDLE;
        $params[] = $suf;
        $params[] = $this->Session->getParameter("_user_id");
        $params[] = $this->Session->getParameter("_user_id");
        $params[] = "";
        $params[] = $this->transStartDate;
        $params[] = $this->transStartDate;
        $params[] = "";
        $params[] = 0;
        $params[] = $suf;
        $params[] = $this->Session->getParameter("_user_id");
        $params[] = $this->transStartDate;
                 
        $this->dbAccess->executeQuery($query, $params);
    }
    
    /**
     * Register prefix by id
     * 
     * @param int $prefix_id
     * @param string $prefix
     */
    private function registerPrefixById($prefix_id, $prefix)
    {
        $query = "UPDATE ".DATABASE_PREFIX."repository_prefix".
                 " SET prefix_id = ?".
                 " WHERE id = ?".
                 " ;";
        $params = array();
        $params[] = $prefix;
        $params[] = $prefix_id;
             
        $this->dbAccess->executeQuery($query, $params);
    }
    
    /**
     * Get suffix
     * 
     * @param int $item_id
     * @param int $item_no
     * @param int $prefix_id
     * @return string
     */
    public function getSuffix($item_id, $item_no, $prefix_id)
    {
        $query = "SELECT suffix".
                 " FROM ".DATABASE_PREFIX."repository_suffix".
                 " WHERE item_id = ?".
                 " AND item_no = ?".
                 " AND id <= ?".
                 " AND is_delete = ?".
                 " ORDER BY id DESC".
                 " LIMIT 1".
                 " ;";
        $params = array();
        $params[] = $item_id;
        $params[] = $item_no;
        $params[] = $prefix_id;
        $params[] = 0;
        $result = $this->dbAccess->executeQuery($query, $params);
        if(count($result) == 0) {
            return "";
        }
        
        return $result[0]['suffix'];
    }
    
    /**
     * Create URI
     * 
     * @param int $item_id
     * @param int $item_no
     * @param int $prefix_id
     * @return string
     */
    public function createUri($item_id, $item_no, $prefix_id)
    {
        $pre = "";
        $suf = "";
        
        // 国会図書館JaLC DOIならば
        if($prefix_id == self::ID_LIBRARY_JALC_DOI)
        {
            $pre = $this->getLibraryJalcDoiPrefix();
            $suf = $this->getHandleSuffix($item_id, $item_no, $prefix_id);
            if(strlen($pre) == 0) {
                return "";
            }
            if(strlen($suf) == 0) {
                return "";
            }
        }
        // JaLC DOIまたはCross Refならば
        if($prefix_id == self::ID_JALC_DOI || $prefix_id == self::ID_CROSS_REF_DOI)
        {
            $pre = $this->getJalcDoiPrefix();
            $suf = $this->getHandleSuffix($item_id, $item_no, $prefix_id);
            // プレフィックスまたはサフィックスのいずれかがなく、JaLCDOIである
            if((strlen($pre) < 1 || strlen($suf) < 1) && $prefix_id == self::ID_JALC_DOI)
            {
                // Cross RefのDOIがあるか調べる
                $prefix_id = self::ID_CROSS_REF_DOI;
                $pre = $this->getPrefix($prefix_id);
                $suf = $this->getHandleSuffix($item_id, $item_no, $prefix_id);
            }
            // JaLC DOI、Cross Refともになければ
            if(strlen($pre) < 1 || strlen($suf) < 1)
            {
                $prefix_id = self::ID_CNRI_HANDLE;
                $pre = $this->getPrefix($prefix_id);
                if(strlen($pre) == 0) {
                    return "";
                }
                $suf = $this->getSuffix($item_id, $item_no, $prefix_id);
                if(strlen($suf) == 0) {
                    return "";
                }
            }
        }
        else
        {
            $pre = $this->getPrefix($prefix_id);
            if(strlen($pre) == 0) {
                return "";
            }
            $suf = $this->getSuffix($item_id, $item_no, $prefix_id);
            if(strlen($suf) == 0) {
                return "";
            }
        }
        
        $uri = "";
        if($prefix_id == self::ID_LIBRARY_JALC_DOI) {
            $uri = self::PREFIX_LIBRARY_DOI_HTTP
                 . $pre
                 . "/". $suf;
        } elseif($prefix_id == self::ID_JALC_DOI || $prefix_id == self::ID_CROSS_REF_DOI) {
            $uri = self::PREFIX_DOI
                 . $pre
                 . "/". $suf;
        } elseif($prefix_id == self::ID_CNRI_HANDLE) {
            // delete the end of a sentence "/" if CNRI
            $uri = self::PREFIX_CNRI
                 . $pre
                 . "/". $suf;
        } elseif($prefix_id == self::ID_Y_HANDLE) {
            // add the end of a sentence "/" if Y handle
            $uri = self::PREFIX_Y_HANDLE
                 . $pre
                 . "/". $suf. "/";
        }
        
        return $uri;
    }
    
    /**
     * Create URI for detail
     * 
     * @param int $item_id
     * @param int $item_no
     * @return string
     */
    public function createUriForDetail($item_id, $item_no)
    {
        $uri = $this->createUri($item_id, $item_no, self::ID_LIBRARY_JALC_DOI);
        if(strlen($uri) == 0) {
            $uri = $this->createUri($item_id, $item_no, self::ID_JALC_DOI);
        }
        if(strlen($uri) == 0) {
            $uri = $this->createUri($item_id, $item_no, self::ID_CROSS_REF_DOI);
        }
        if(strlen($uri) == 0) {
            $uri = $this->createUri($item_id, $item_no, self::ID_CNRI_HANDLE);
        }
        if(strlen($uri) == 0) {
            $uri = $this->createUri($item_id, $item_no, self::ID_Y_HANDLE);
        }
        return $uri;
    }
    
    /**
     * Create URI for JuNii2
     * 
     * @param int $item_id
     * @param int $item_no
     * @return stirng
     */
    public function createUriForJuNii2($item_id, $item_no)
    {
        $url = $this->createUri($item_id, $item_no, self::ID_CNRI_HANDLE);
        if(strlen($url) == 0) {
            $url = $this->createUri($item_id, $item_no, self::ID_Y_HANDLE);
        }
        if(strlen($url) == 0) {
            $url = $this->getSubstanceUri($item_id, $item_no);
        }
        
        return $url;
    }
    
    public function createUriForSpase($item_id, $item_no)
    {
    	$url = $this->createUri($item_id, $item_no, self::ID_CNRI_HANDLE);
    	if(strlen($url) == 0) {
    		$url = $this->createUri($item_id, $item_no, self::ID_Y_HANDLE);
    	}
    	if(strlen($url) == 0) {
    		$url = $this->getSubstanceUri($item_id, $item_no);
    	}
    
    	return $url;
    }
    
    /**
     * Get substance URI
     * 
     * @param int $item_id
     * @param int $item_no
     * @return string
     */
    public function getSubstanceUri($item_id, $item_no)
    {
        $query = "SELECT uri".
                 " FROM ".DATABASE_PREFIX."repository_item".
                 " WHERE item_id = ?".
                 " AND item_no = ?".
                 " AND is_delete = ?".
                 " ;";
        $params = array();
        $params[] = $item_id;
        $params[] = $item_no;
        $params[] = 0;
        $result = $this->dbAccess->executeQuery($query, $params);
        if(count($result) == 0) {
            return "";
        }
        
        return $result[0]['uri'];
    }
    
    /**
     * Register Y handle prefix by primary key
     * 
     * @return bool
     */
    public function registerYHandlePrefixByPriKey()
    {
        $IDServer = new IDServer($this->Session, $this->dbAccess->getDb());
        $YHandlePrefix = $IDServer->getPrefixID(true);
        if($YHandlePrefix == false) {
            return false;
        }
        
        $this->registerPrefixById(self::ID_Y_HANDLE, $YHandlePrefix);
        
        return true;
    }
    
    /**
     * Set Y-handle suffix
     *
     * @param string $title
     * @param int $itemId
     * @param int $itemNo
     * @return bool
     */
    public function setSuffix($title, $itemId, $itemNo)
    {
        // check registered suffix
        $suffix = $this->getSuffix($itemId, $itemNo, RepositoryHandleManager::ID_Y_HANDLE);
        
        if(strlen($suffix) > 0)
        {
            // suffix is registered
            return true;
        }
        
        // regist suffix
        $result = $this->registerYhandleSuffix($title, $itemId, $itemNo);
        if($result === false)
        {
            // prefix is not registered
            return false;
        }
        
        // check suffix
        $suffix = $this->getSuffix($itemId, $itemNo, RepositoryHandleManager::ID_Y_HANDLE);
        if(strlen($suffix) == 0)
        {
            // failed to regist suffix
            return false;
        }
        
        return true;
    }
    
    /**
     * insert suffix
     *
     * @param int $item_id
     * @param int $item_no
     * @param int $prefix_id
     * @param string $suffix
     * @return bool
     */
    private function registSuffix($item_id, $item_no, $prefix_id, $suffix)
    {
        $query = "INSERT INTO ".DATABASE_PREFIX."repository_suffix".
                 " (item_id, item_no, id, suffix, ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete)".
                 " VALUES".
                 " (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)".
                 " ON DUPLICATE KEY ". 
                 " UPDATE suffix = ?, mod_user_id = ?, mod_date = ? ". 
                 " ;";
        $params = array();
        $params[] = $item_id;
        $params[] = $item_no;
        $params[] = $prefix_id;
        $params[] = $suffix;
        $params[] = $this->Session->getParameter("_user_id");
        $params[] = $this->Session->getParameter("_user_id");
        $params[] = "";
        $params[] = $this->transStartDate;
        $params[] = $this->transStartDate;
        $params[] = "";
        $params[] = 0;
        $params[] = $suffix;
        $params[] = $this->Session->getParameter("_user_id");
        $params[] = $this->transStartDate;
        
        $this->dbAccess->executeQuery($query, $params);
    }
    
    /**
     * set JaLC DOI status
     *
     * @param int $item_id
     * @param int $item_no
     * @param int $status
     * @return bool
     */
    private function registDoiStatus($item_id, $item_no, $status)
    {
        $query = "INSERT INTO ".DATABASE_PREFIX."repository_doi_status".
                 " (item_id, item_no, status, ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete)".
                 " VALUES".
                 " (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)".
                 " ON DUPLICATE KEY ". 
                 " UPDATE status = ?, mod_user_id = ?, mod_date = ? ". 
                 " ;";
        $params = array();
        $params[] = $item_id;
        $params[] = $item_no;
        $params[] = $status;
        $params[] = $this->Session->getParameter("_user_id");
        $params[] = $this->Session->getParameter("_user_id");
        $params[] = "";
        $params[] = $this->transStartDate;
        $params[] = $this->transStartDate;
        $params[] = "";
        $params[] = 0;
        $params[] = $status;
        $params[] = $this->Session->getParameter("_user_id");
        $params[] = $this->transStartDate;
        
        $this->dbAccess->executeQuery($query, $params);
    }
    
    /**
     * Regist Library JaLC DOI suffix
     * 
     * @param int $item_id
     * @param int $item_no
     * @param string $suffix
     */
    public function registLibraryJalcdoiSuffix($item_id, $item_no, $uri)
    {
        $suffix = "";
        $prefix = $this->getLibraryJalcDoiPrefix();
        
        if(strpos($uri, $prefix) !== false)
        {
            $uri = str_replace(self::PREFIX_SELF_DOI.$prefix."/", "", $uri);
            $uri = str_replace(self::PREFIX_LIBRARY_DOI_INFO.$prefix."/", "", $uri);
            $uri = str_replace(self::PREFIX_LIBRARY_DOI_HTTP.$prefix."/", "", $uri);
            $uri = str_replace(self::PREFIX_LIBRARY_DOI_DOI.$prefix."/", "", $uri);
            $uri = str_replace($prefix."/", "", $uri);
            if(strpos($uri, "/") === false)
            {
                $suffix = $uri;
            }
        }
        
        if(strlen($suffix) > 0){
            $this->registSuffix($item_id, $item_no, self::ID_LIBRARY_JALC_DOI, $suffix);
            $this->registDoiStatus($item_id, $item_no, self::DOI_STATUS_GRANTED);
        }
    }
    
    /**
     * Regist JaLC DOI suffix
     * 
     * @param int $item_id
     * @param int $item_no
     * @param string $suffix
     */
    public function registJalcdoiSuffix($item_id, $item_no, $suffix)
    {
        $this->registSuffix($item_id, $item_no, self::ID_JALC_DOI, $suffix);
        $this->registDoiStatus($item_id, $item_no, self::DOI_STATUS_GRANTED);
    }
    
    /**
     * Regist Cross Ref suffix
     * 
     * @param int $item_id
     * @param int $item_no
     * @param string $suffix
     */
    public function registCrossrefSuffix($item_id, $item_no, $suffix)
    {
        $this->registSuffix($item_id, $item_no, self::ID_CROSS_REF_DOI, $suffix);
        $this->registDoiStatus($item_id, $item_no, self::DOI_STATUS_GRANTED);
    }
    
    /**
     * Get Y Handle suffix
     * 
     * @param int $item_id
     * @param int $item_no
     * @return string
     */
    public function getYHandleSuffix($item_id, $item_no)
    {
        return $this->getHandleSuffix($item_id, $item_no, self::ID_Y_HANDLE);
    }
    
    /**
     * Get Library Jalc Doi suffix
     * 
     * @param int $item_id
     * @param int $item_no
     * @return string
     */
    public function getLibraryJalcdoiSuffix($item_id, $item_no)
    {
        return $this->getHandleSuffix($item_id, $item_no, self::ID_LIBRARY_JALC_DOI);
    }
    
    /**
     * Get Jalc Doi suffix
     * 
     * @param int $item_id
     * @param int $item_no
     * @return string
     */
    public function getJalcdoiSuffix($item_id, $item_no)
    {
        return $this->getHandleSuffix($item_id, $item_no, self::ID_JALC_DOI);
    }
    
    /**
     * Get Cross Ref suffix
     * 
     * @param int $item_id
     * @param int $item_no
     * @return string
     */
    public function getCrossrefSuffix($item_id, $item_no)
    {
        return $this->getHandleSuffix($item_id, $item_no, self::ID_CROSS_REF_DOI);
    }
    
    /**
     * Get suffix
     * 
     * @param int $item_id
     * @param int $item_no
     * @param int $prefix_id
     * @return string
     */
    private function getHandleSuffix($item_id, $item_no, $prefix_id)
    {
        $query = "SELECT suffix".
                 " FROM ".DATABASE_PREFIX."repository_suffix".
                 " WHERE item_id = ?".
                 " AND item_no = ?".
                 " AND id = ?".
                 " AND is_delete = ?".
                 " LIMIT 1".
                 " ;";
        $params = array();
        $params[] = $item_id;
        $params[] = $item_no;
        $params[] = $prefix_id;
        $params[] = 0;
        $result = $this->dbAccess->executeQuery($query, $params);
        if(count($result) == 0) {
            return '';
        }
        
        return $result[0]['suffix'];
    }
    
    /**
     * DOIを取り下げる(statusを2にする)
     *
     * @param int $item_id
     * @param int $item_no
     * @return bool
     */
    public function dropDoiSuffix($item_id, $item_no)
    {
        // JaLC DOIを取り下げる
        $this->registDoiStatus($item_id, $item_no, self::DOI_STATUS_DROPED);
        
        // 国会図書館JaLC DOIを削除する
        $this->deleteSuffix($item_id, $item_no, self::ID_LIBRARY_JALC_DOI);
        
        // Cross Refを削除する
        $this->deleteSuffix($item_id, $item_no, self::ID_CROSS_REF_DOI);
        
        // JaLC DOIを削除する
        $this->deleteSuffix($item_id, $item_no, self::ID_JALC_DOI);
    }
    
    /**
     * サフィックスを削除する
     *
     * @param int $item_id
     * @param int $item_no
     * @param int $id
     * @return bool
     */
    private function deleteSuffix($item_id, $item_no, $id)
    {
        $query = "UPDATE ".DATABASE_PREFIX."repository_suffix ".
                 "SET mod_user_id = ?, del_user_id = ?, mod_date = ?, del_date = ?, is_delete = ? ".
                 "WHERE item_id = ? ".
                 "AND item_no = ? ".
                 "AND id = ? ".
                 "AND is_delete = ? ;";
        $params = array();
        $params[] = $this->Session->getParameter("_user_id");
        $params[] = $this->Session->getParameter("_user_id");
        $params[] = $this->transStartDate;
        $params[] = $this->transStartDate;
        $params[] = 1;
        $params[] = $item_id;
        $params[] = $item_no;
        $params[] = $id;
        $params[] = 0;
        $result = $this->dbAccess->executeQuery($query, $params);
    }
    
    /**
     * DOI付与状態を削除する
     *
     * @param int $item_id
     * @param int $item_no
     * @return bool
     */
    public function deleteDoiStatus($item_id, $item_no)
    {
        $query = "UPDATE ".DATABASE_PREFIX."repository_doi_status ".
                 "SET mod_user_id = ?, del_user_id = ?, mod_date = ?, del_date = ?, is_delete = ? ".
                 "WHERE item_id = ? ".
                 "AND item_no = ? ".
                 "AND is_delete = ? ;";
        $params = array();
        $params[] = $this->Session->getParameter("_user_id");
        $params[] = $this->Session->getParameter("_user_id");
        $params[] = $this->transStartDate;
        $params[] = $this->transStartDate;
        $params[] = 1;
        $params[] = $item_id;
        $params[] = $item_no;
        $params[] = 0;
        $result = $this->dbAccess->executeQuery($query, $params);
    }
    
    /**
     * Create Self DOI URI
     * 
     * @param int $item_id
     * @param int $item_no
     * @param int $prefix_id
     * @return string
     */
    public function createSelfDoiUri($item_id, $item_no, $id)
    {
        $uri = "";
        $prefix = "";
        $suffix = "";
        if($id == self::ID_LIBRARY_JALC_DOI)
        {
            $prefix = $this->getLibraryJalcDoiPrefix();
            $suffix = $this->getLibraryJalcdoiSuffix($item_id, $item_no);
        }        
        else if($id == self::ID_JALC_DOI)
        {
            $prefix = $this->getJalcDoiPrefix();
            $suffix = $this->getJalcdoiSuffix($item_id, $item_no);
        }
        else if($id == self::ID_CROSS_REF_DOI)
        {
            $prefix = $this->getCrossRefPrefix();
            $suffix = $this->getCrossrefSuffix($item_id, $item_no);
        }
        
        if(strlen($prefix) > 0 && strlen($suffix) > 0)
        {
            $uri = self::PREFIX_SELF_DOI . $prefix . "/" . $suffix;
        }
        
        return $uri;
    }

    /**
     * Register CNRI suffix
     * 
     * @param int $item_id
     * @param int $item_no
     * @param string $suffix
     */
    public function registCnriSuffix($item_id, $item_no, $suffix)
    {
        $this->registSuffix($item_id, $item_no, self::ID_CNRI_HANDLE, $suffix);
    }
    
    /**
     * Get CNRI suffix
     * 
     * @param int $item_id
     * @param int $item_no
     * @return string
     */
    public function getCnriSuffix($item_id, $item_no)
    {
        return $this->getHandleSuffix($item_id, $item_no, self::ID_CNRI_HANDLE);
    }
}
?>