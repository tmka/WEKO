<?php
// --------------------------------------------------------------------
//
// $Id: NameAuthority.class.php 30569 2014-01-09 07:37:40Z rei_matsuura $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
include_once WEBAPP_DIR. '/modules/repository/files/pear/Date.php';

class NameAuthority extends Action
{
    // member
    private $Session = null;
    private $Db = null;
    private $user_id = null;
    private $mod_date = null;
    private $block_id = 0;
    private $room_id = 0;
    
    /**
     * INIT
     */
    public function NameAuthority($Session, $Db, $block_id=0, $room_id=0){
        if($Session != null){
            $this->Session = $Session;
        } else {
            return null;
        }
        if($Db != null){
            $this->Db = $Db;
        } else {
            return null;
        }
        $DATE = new Date();
        $this->mod_date = $DATE->getDate().".000";
        $this->setBlockId($block_id);
        $this->setRoomId($room_id);
        $this->user_id = $this->Session->getParameter("_user_id");
    }
    
    /**
     * Set block id
     *
     * @param int $block_id
     */
    public function setBlockId($block_id){
        $this->block_id = $block_id;
    }
    
    /**
     * Set room id
     *
     * @param int $room_id
     */
    public function setRoomId($room_id){
        $this->room_id = $room_id;
    }
    
    /**
     * Name authority Insert
     * @param array()
     * @return boolean
     * @access  public
     */
    public function insNameAuthority($params=array())
    {
        $result = $this->Db->insertExecute("repository_name_authority", $params);
        if ($result === false) {
            return false;
        }
        return true;
    }
    
    /**
     * Name Authority Update
     * @param array()
     * @return boolean
     * @access  public
     */
    public function updNameAuthority($params=array(), $where_params=array())
    {
        $result = $this->Db->updateExecute("repository_name_authority", $params, $where_params);
        if ($result === false) {
            return false;
        }
        return true;
    }
    
    /**
     * Name Authority Delete
     * @param array()
     * @return boolean
     * @access  public
     */
    public function delNameAuthority($where_params=array())
    {
        $result = $this->Db->deleteExecute("repository_name_authority", $where_params);
        if ($result === false) {
            return false;
        }
        return true;
    }
    
    /**
     * Name Authority Select
     * @param array where_params
     * @param array order_params
     * @param function func
     * @param array    func_param
     * @return boolean
     * @access  public
     */
    public function getNameAuthority($where_params=array(), $order_params=array(), $func = null, $func_param = null)
    {
        $result = $this->Db->selectExecute("repository_name_authority", $where_params, $order_params, null, null, $func, $func_param);
        if ($result === false) {
            return $result;
        }
        return $result;
    }
    
    /**
     * External AuthorId Prefix Insert
     * @param array()
     * @return boolean
     * @access  public
     */
    public function insExternalAuthorIdPrefix($params=array())
    {
        $result = $this->Db->insertExecute("repository_external_author_id_prefix", $params);
        if ($result === false) {
            return false;
        }
        return true;
    }
    
    /**
     * External AuthorId Prefix Update
     * @param array()
     * @return boolean
     * @access  public
     */
    public function updExternalAuthorIdPrefix($params=array(), $where_params=array())
    {
        $result = $this->Db->updateExecute("repository_external_author_id_prefix", $params, $where_params);
        if ($result === false) {
            return false;
        }
        return true;
    }
    
    /**
     * External AuthorId Prefix Delete
     * @param array()
     * @return boolean
     * @access  public
     */
    public function delExternalAuthorIdPrefix($where_params=array())
    {
        $result = $this->Db->deleteExecute("repository_external_author_id_prefix", $where_params);
        if ($result === false) {
            return false;
        }
        return true;
    }
    
    /**
     * External AuthorId Prefix Select
     * @param array where_params
     * @param array order_params
     * @param function func
     * @param array    func_param
     * @return boolean
     * @access  public
     */
    public function getExternalAuthorIdPrefix($where_params=array(), $order_params=array(), $func = null, $func_param = null)
    {
        $result = $this->Db->selectExecute("repository_external_author_id_prefix", $where_params, $order_params, null, null, $func, $func_param);
        if ($result === false) {
            return $result;
        }
        return $result;
    }
    
    /**
     * External AuthorId Suffix Insert
     * @param array()
     * @return boolean
     * @access  public
     */
    public function insExternalAuthorIdSuffix($params=array())
    {
        $result = $this->Db->insertExecute("repository_external_author_id_suffix", $params);
        if ($result === false) {
            return false;
        }
        return true;
    }
    
    /**
     * External AuthorId Suffix Update
     * @param array()
     * @return boolean
     * @access  public
     */
    public function updExternalAuthorIdSuffix($params=array(), $where_params=array())
    {
        $result = $this->Db->updateExecute("repository_external_author_id_suffix", $params, $where_params);
        if ($result === false) {
            return false;
        }
        return true;
    }
    
    /**
     * External AuthorId Suffix Delete
     * @param array()
     * @return boolean
     * @access  public
     */
    public function delExternalAuthorIdSuffix($where_params=array())
    {
        $result = $this->Db->deleteExecute("repository_external_author_id_suffix", $where_params);
        if ($result === false) {
            return false;
        }
        return true;
    }
    
    /**
     * External AuthorId Suffix Select
     * @param array where_params
     * @param array order_params
     * @param function func
     * @param array    func_param
     * @return boolean
     * @access  public
     */
    public function getExternalAuthorIdSuffix($where_params=array(), $order_params=array(), $func = null, $func_param = null)
    {
        $result = $this->Db->selectExecute("repository_external_author_id_suffix", $where_params, $order_params, null, null, $func, $func_param);
        if ($result === false) {
            return $result;
        }
        return $result;
    }
    
    /**
     * Get external authorID prefix list
     *
     * @return array() 
     */
    public function getExternalAuthorIdPrefixList(){
        $query = "SELECT prefix_id, prefix_name, block_id, room_id ".
                 "FROM ".DATABASE_PREFIX."repository_external_author_id_prefix ".
                 "WHERE ((block_id = 0 AND room_id = 0) OR (block_id = ? AND room_id = ?)) ".
                 "AND is_delete = 0 ".
                 "AND prefix_id > 0 ".
                 "ORDER BY prefix_id ASC;";
        $params = array();
        $params[] = $this->block_id;  // block_id
        $params[] = $this->room_id;  // room_id
        $result = $this->Db->execute($query, $params);
        if($result===false){
            return false;
        }
        return $result;
    }
    
    /**
     * Get external authorID prefix and suffix 
     *
     * @param int $author_id
     * @return array() 
     */
    public function getExternalAuthorIdPrefixAndSuffix($author_id, $getEmailFlag=false){
        $query = "SELECT suffix.prefix_id, suffix.suffix ".
                 "FROM ".DATABASE_PREFIX."repository_external_author_id_suffix AS suffix, ".
                 "     ".DATABASE_PREFIX."repository_external_author_id_prefix AS prefix ".
                 "WHERE suffix.author_id = ? ".
                 "AND suffix.prefix_id = prefix.prefix_id ".
                 "AND ((prefix.block_id = 0 AND prefix.room_id = 0) OR (prefix.block_id = ? AND prefix.room_id = ?)) ".
                 "AND prefix.is_delete = 0 ";
        if(!$getEmailFlag)
        {
            $query .= "AND prefix.prefix_id > 0 ";
        }
        $query .= "ORDER BY suffix.author_id ASC;";
        $params = array();
        $params[] = $author_id; // author_id
        $params[] = $this->block_id;  // block_id
        $params[] = $this->room_id;   // room_id
        $result = $this->Db->execute($query, $params);
        if($result===false){
            return false;
        }
        if(count($result)==0){
            $result = array(array('prefix_id'=>'', 'suffix'=>''));
        }
        return $result;
    }
    
    /**
     * Add external authorID prefix 
     *
     * @param string $prefix_name
     * @param int $prefix_id
     * @return int $prefix_id
     */
    public function addExternalAuthorIdPrefix($prefix_name, $prefix_id=0){
        if($prefix_id==0){
            $prefix_id = $this->getNewPrefixId();
        }
        $params = array(
                        "prefix_id" => $prefix_id,
                        "prefix_name" => $prefix_name,
                        "block_id" => $this->block_id,
                        "room_id" => $this->room_id,
                        "ins_user_id" => $this->user_id,
                        "mod_user_id" => $this->user_id,
                        "del_user_id" => 0,
                        "ins_date" => $this->mod_date,
                        "mod_date" => $this->mod_date,
                        "del_date" => "",
                        "is_delete" => 0
                    );
        $result = $this->insExternalAuthorIdPrefix($params);
        if($result === false){
            return false;
        }
        return $prefix_id;
    }
    
    /**
     * Update external authorID prefix
     *
     * @param int $prefix_id
     * @return array()
     */
    public function updateExternalAuthorIdPrefix($prefix_id){
        $params = array(
                        "mod_user_id" => $this->user_id,
                        "del_user_id" => 0,
                        "mod_date" => $this->mod_date,
                        "del_date" => "",
                        "is_delete" => 0
                    );
        $where_params = array("prefix_id" => $prefix_id);
        $result = $this->updExternalAuthorIdPrefix($params, $where_params);
        if($result===false){
            return false;
        }
        return true;
    }
    
    /**
     * Get new external authorID's prefix_id
     *
     * @return int $new_prefix_id
     */
    public function getNewPrefixId(){
        $new_prefix_id = intval($this->Db->nextSeq("repository_external_author_id_prefix"));
        return $new_prefix_id;
    }
    
    /**
     * Entry external authorID prefix 
     *
     * @param array() $prefix_data[x]["prefix_id"]
     *                               ["prefix_name"]
     */
    public function entryExternalAuthorIdPrefix($prefix_data){
        // Delete record by block_id and room_id
        $params = array(
                        "mod_user_id" => $this->user_id,
                        "del_user_id" => $this->user_id,
                        "mod_date" => $this->mod_date,
                        "del_date" => $this->mod_date,
                        "is_delete" => 1,
                    );
        $where_params = array(
                                "block_id" => $this->block_id,
                                "room_id" => $this->room_id,
                                "prefix_id!=0" => null,
                                "prefix_id!=1" => null,
                                "prefix_id!=2" => null,
                                "prefix_id!=3" => null
                            );
        $result = $this->updExternalAuthorIdPrefix($params, $where_params);
        if($result===false){
            return false;
        }
        
        // Update or Insert record
        for($ii=0;$ii<count($prefix_data);$ii++){
            if($prefix_data[$ii]["prefix_name"]!="e_mail_address" && $prefix_data[$ii]["prefix_id"]!=1 && $prefix_data[$ii]["prefix_id"]!=2 && $prefix_data[$ii]["prefix_id"]!=3){
                if(($prefix_data[$ii]["prefix_id"]==0 || $prefix_data[$ii]["prefix_id"]==null) && $prefix_data[$ii]["prefix_name"]!=""){
                    // Insert record
                    $result = $this->addExternalAuthorIdPrefix($prefix_data[$ii]["prefix_name"]);
                    if($result===false){
                        return false;
                    }
                } else {
                    // Update record
                    $result = $this->updateExternalAuthorIdPrefix($prefix_data[$ii]["prefix_id"]);
                    if($result===false){
                        return false;
                    }
                }
            }
        }
        return true;
    }
    
    /**
     * Delete external authorID suffix
     *
     * @param int $author_id
     */
    public function deleteExternalAuthorIdSuffix($author_id){
        $query = "DELETE SUFFIX ".
                 "FROM ".DATABASE_PREFIX."repository_external_author_id_suffix AS SUFFIX, ".
                 "     ".DATABASE_PREFIX."repository_external_author_id_prefix AS PREFIX ".
                 "WHERE SUFFIX.author_id = ? ".
                 "AND SUFFIX.prefix_id = PREFIX.prefix_id ".
                 "AND ((PREFIX.block_id = 0 AND PREFIX.room_id = 0) OR (PREFIX.block_id = ? AND PREFIX.room_id = ?));";
        $params = array();
        $params[] = $author_id;         // author_id
        $params[] = $this->block_id;    // block_id
        $params[] = $this->room_id;     // room_id
        $result = $this->Db->execute($query, $params);
        if($result===false){
            return false;
        }
        return true;
    }
    
    /**
     * Get new author_id
     * 
     * @return int $author_id
     */
    public function getNewAuthorId(){
        $query = "SELECT MAX(author_id) FROM ".DATABASE_PREFIX."repository_name_authority;";
        $result = $this->Db->execute($query);
        if ($result === false) {
            return false;
        }
        $author_id = intval($result[0]['MAX(author_id)'])+1;
        return $author_id;
    }
    
    /**
     * Get name authority data
     * 
     * @return int $author_id
     */
    public function getNameAuthorityData($author_id, $language){
        $where_params = array(
                                "author_id" => $author_id,
                                "language" => $language,
                                "is_delete" => 0
                            );
        $order_params = array("author_id" => "ASC");
        $result = $this->getNameAuthority($where_params, $order_params);
        return $result;
    }
    
    /**
     * Get external authorID's prefix_name by prefix_id
     *
     * @param int $prefix_id
     * @param string prefix_name
     */
    public function getExternalAuthorIdPrefixName($prefix_id){
        $where_params = array("prefix_id" => $prefix_id);
        $result = $this->getExternalAuthorIdPrefix($where_params);
        if($result===false){
            return false;
        }
        return $result[0]["prefix_name"];
    }
    
    /**
     * Get external authorID data by author_id, block_id and room_id
     *
     * @param int $prefix_id
     * @return array()
     */
    public function getExternalAuthorIdData($author_id){
        $query = "SELECT SUFFIX.author_id, SUFFIX.prefix_id, PREFIX.prefix_name, SUFFIX.suffix ".
                 "FROM ".DATABASE_PREFIX."repository_external_author_id_suffix AS SUFFIX, ".
                 "     ".DATABASE_PREFIX."repository_external_author_id_prefix AS PREFIX ".
                 "WHERE SUFFIX.author_id = ? ".
                 "AND SUFFIX.prefix_id = PREFIX.prefix_id ".
                 "AND ((PREFIX.block_id = 0 AND PREFIX.room_id = 0) OR (PREFIX.block_id = ? AND PREFIX.room_id = ?)) ".
                 "AND SUFFIX.is_delete = 0 ".
                 "AND PREFIX.prefix_id > 0 ".
                 "ORDER BY SUFFIX.author_id ASC;";
        $params = array();
        $params[] = $author_id; // author_id
        $params[] = $this->block_id;  // block_id
        $params[] = $this->room_id;   // room_id
        $result = $this->Db->execute($query, $params);
        if($result===false){
            return array();
        }
        return $result;
    }
    
    /**
     * Get external authorID prefix_id by prefix_name
     *
     * @param string $prefix_name
     * @return int $prefix_id
     */
    public function getExternalAuthorIdPrefixId($prefix_name){
        $where_params = array(
                                "prefix_name" => $prefix_name,
                                "block_id" => $this->block_id,
                                "room_id" => $this->room_id,
                                "is_delete" => 0
                            );
        $result = $this->getExternalAuthorIdPrefix($where_params);
        if($result===false){
            return false;
        }
        if(count($result)>0 && strlen($result[0]["prefix_id"])>0){
            $prefix_id = intval($result[0]["prefix_id"]);
        } else {
            $prefix_id = 0;
        }
        return $prefix_id;
    }
    

    
    /**
     * Search author data for suggest
     *
     * @param string $surName
     * @param string $givenName
     * @param string $surNameRuby
     * @param string $givenNameRuby
     * @param string $emailAddress
     * @param string $externalAuthorID
     * @param string $language 
     * @return array()
     */
    public function searchSuggestData($surName, $givenName, $surNameRuby, $givenNameRuby, $emailAddress, $externalAuthorID, $language=""){
        $query = "SELECT DISTINCT AUTHOR.author_id, AUTHOR.family, AUTHOR.name, ".
                 "AUTHOR.family_ruby, AUTHOR.name_ruby, SUFFIX.suffix ".
                 "FROM ".DATABASE_PREFIX."repository_name_authority AS AUTHOR ".
                 "LEFT JOIN ".
                 "( SELECT author_id, suffix FROM ".DATABASE_PREFIX."repository_external_author_id_suffix WHERE prefix_id = 0) AS SUFFIX ".
                 "ON AUTHOR.author_id = SUFFIX.author_id ";
        $where_query = "";
        $params = array();
        if(strlen($surName)>0){
            if(strlen($where_query)>0){
                $where_query .= "AND ";
            } else {
                $where_query .= "WHERE ";
            }
            $where_query .= "AUTHOR.family LIKE ? ";
            $params[] = $surName."%";
        }
        if(strlen($givenName)>0){
            if(strlen($where_query)>0){
                $where_query .= "AND ";
            } else {
                $where_query .= "WHERE ";
            }
            $where_query .= "AUTHOR.name LIKE ? ";
            $params[] = $givenName."%";
        }
        if(strlen($surNameRuby)>0){
            if(strlen($where_query)>0){
                $where_query .= "AND ";
            } else {
                $where_query .= "WHERE ";
            }
            $where_query .= "AUTHOR.family_ruby LIKE ? ";
            $params[] = $surNameRuby."%";
        }
        if(strlen($givenNameRuby)>0){
            if(strlen($where_query)>0){
                $where_query .= "AND ";
            } else {
                $where_query .= "WHERE ";
            }
            $where_query .= "AUTHOR.name_ruby LIKE ? ";
            $params[] = $givenNameRuby."%";
        }
        if(strlen($emailAddress)>0){
            if(strlen($where_query)>0){
                $where_query .= "AND ";
            } else {
                $where_query .= "WHERE ";
            }
            $where_query .= "SUFFIX.suffix LIKE ? ";
            $params[] = $emailAddress."%";
        }
        if(strlen($externalAuthorID)>0){
            $authorId = $this->getSuggestAuthorBySuffix($externalAuthorID);
            if(count($authorId) > 0) {
                if(strlen($where_query)>0){
                    $where_query .= "AND ";
                } else {
                    $where_query .= "WHERE ";
                }
                for($cnt = 0; $cnt < count($authorId); $cnt++)
                {
                    if($cnt == 0)
                    {
                        $where_query .= "AUTHOR.author_id IN( ?";
                        $params[] = $authorId[$cnt]["author_id"];
                    }
                    else
                    {
                        $where_query .= ", ?";
                        $params[] = $authorId[$cnt]["author_id"];
                    }
                }
                $where_query .= ") ";
            }
            else
            {
                return array();
            }
        }
        if(strlen($language)>0){
            if(strlen($where_query)>0){
                $where_query .= "AND ";
            } else {
                $where_query .= "WHERE ";
            }
            $where_query .= "(AUTHOR.language = ? ".
                            "OR AUTHOR.language = '') ";
            $params[] = $language;  // Selected languege
        }
        $query .= $where_query."ORDER BY AUTHOR.author_id ASC;";
        $result = $this->Db->execute($query, $params);
        if($result===false){
            return false;
        }
        return $result;
    }
    
    /**
     * Entry NameAuthority data
     * 
     * @param array $metadata   $metadata["family"]
     *                                   ["name"]
     *                                   ["family_ruby"]
     *                                   ["name_ruby"]
     *                                   ["e_mail_address"]
     *                                   ["author_id"]
     *                                   ["language"]
     *                                   ["external_author_id"][x]["prefix_id"]
     *                                                            ["suffix"]
     * @param string $errMsg
     * @param boolean $noMergeFlag  false: Execute merge external_author_id
     *                               true: Not execute merge external_author_id
     */
    public function entryNameAuthority($metadata, &$errMsg, $noMerge=false){
        if(count($metadata)==0){
            $errMsg = "Cannot regist author data.";
            return false;
        }
        
        // Merge external authorID
        if(!$noMerge){
            $result = $this->mergeExtAuthorId($metadata["external_author_id"], $metadata["author_id"]);
        }
        
        if(intval($metadata["author_id"])==0){
            // New author data
            $metadata["author_id"] = $this->getNewAuthorId();
        }
        
        // Check exist same author ID
        $result = $this->getNameAuthorityData($metadata["author_id"], $metadata["language"]);
        if(count($result)==0){
            // Insert
            $params = array(
                            "author_id" => $metadata["author_id"],
                            "language" => $metadata["language"],
                            "family" => $metadata["family"],
                            "name" => $metadata["name"],
                            "family_ruby" => $metadata["family_ruby"],
                            "name_ruby" => $metadata["name_ruby"],
                            "ins_user_id" => $this->user_id,
                            "mod_user_id" => $this->user_id,
                            "del_user_id" => 0,
                            "ins_date" => $this->mod_date,
                            "mod_date" => $this->mod_date,
                            "is_delete" => 0
                        );
            $result = $this->insNameAuthority($params);
            if($result === false){
                $errMsg = $this->Db->ErrorMsg();
                return false;
            }
        } else if(count($result)>0){
            // Add author data
            // 空のカラムがある場合、追加更新を行う
            $update_params = array();
            $where_params = array();
            if(strlen($result[0]["family"])==0 && strlen($metadata["family"])>0){
                $update_params["family"] = $metadata["family"];
            }
            if(strlen($result[0]["name"])==0 && strlen($metadata["name"])>0){
                $update_params["name"] = $metadata["name"];
            }
            if(strlen($result[0]["family_ruby"])==0 && strlen($metadata["family_ruby"])>0){
                $update_params["family_ruby"] = $metadata["family_ruby"];
            }
            if(strlen($result[0]["name_ruby"])==0 && strlen($metadata["name_ruby"])>0){
                $update_params["name_ruby"] = $metadata["name_ruby"];
            }
            if(count($update_params)>0){
                $update_params["mod_user_id"] = $this->user_id;
                $update_params["mod_date"] = $this->mod_date;
                $where_params = array(
                                    "author_id" => $metadata["author_id"],
                                    "language" => $metadata["language"]
                                );
                $result = $this->updNameAuthority($update_params, $where_params);
                if($result === false){
                    $errMsg = $this->Db->ErrorMsg();
                    return false;
                }
            }
        }
        
        // Regist external authorID suffix
        $result = $this->deleteExternalAuthorIdSuffix($metadata["author_id"]);
        if($result === false){
            $errMsg = $this->Db->ErrorMsg();
            $this->failTrans(); //ROLLBACK
            return false;
        }
        for($ii=0; $ii<count($metadata["external_author_id"]); $ii++){
            if($metadata["external_author_id"][$ii]["prefix_id"]!="" 
                && $metadata["external_author_id"][$ii]["suffix"] != "")
            {
                $where_params = array(
                                        "author_id" => $metadata["author_id"],
                                        "prefix_id" => $metadata["external_author_id"][$ii]["prefix_id"]
                                    );
                $result = $this->getExternalAuthorIdSuffix($where_params);
                if(count($result)==0){
                    // INSERT
                    $params =array(
                                    "author_id" => $metadata["author_id"],
                                    "prefix_id" => $metadata["external_author_id"][$ii]["prefix_id"],
                                    "suffix" => $metadata["external_author_id"][$ii]["suffix"],
                                    "ins_user_id" => $this->user_id,
                                    "mod_user_id" => $this->user_id,
                                    "del_user_id" => 0,
                                    "ins_date" => $this->mod_date,
                                    "mod_date" => $this->mod_date,
                                    "del_date" => "",
                                    "is_delete" => 0
                                );
                    $result = $this->insExternalAuthorIdSuffix($params);
                    if ($result === false) {
                        return false;
                    }
                }
            }
        }
        
        return $metadata["author_id"];
    }
    
    /**
     * Check same prefix and suffix
     *
     * @param array() $extAuthorIdArray[x]["prefix_id"]
     *                                    ["suffix"]
     * @param int $authorId
     * @return int $rtnAuthorId
     */
    public function checkSamePrefixAndSuffix($extAuthorIdArray, $authorId=0){
        $rtnAuthorId = 0;
        $excludeAuthorIdArray = array();
        for($ii=0;$ii<count($extAuthorIdArray);$ii++){
            // Get authorIDs has same external authorID
            if(strlen($authorId)!=0 && intval($authorId)!=0){
                $where_params = array(
                                        "prefix_id" => $extAuthorIdArray[$ii]["prefix_id"],
                                        "suffix" => $extAuthorIdArray[$ii]["suffix"],
                                        "author_id!=$authorId" => null,
                                        "is_delete" => 0
                                    );
            } else {
                $where_params = array(
                                        "prefix_id" => $extAuthorIdArray[$ii]["prefix_id"],
                                        "suffix" => $extAuthorIdArray[$ii]["suffix"],
                                        "is_delete" => 0
                                    );
            }
            $order_params = array("author_id" => "ASC");
            $sameExtAuthorIdArray = $this->getExternalAuthorIdSuffix($where_params, $order_params);
            if($sameExtAuthorIdArray === false){
                return false;
            }
            for($jj=0;$jj<count($sameExtAuthorIdArray);$jj++){
                $sameAuthorFlag = false;
                // Check exclude author_id
                if(in_array($sameExtAuthorIdArray[$jj]["author_id"], $excludeAuthorIdArray)){
                    break;
                }
                // Get target author data
                $where_params = array(
                                        "author_id" => $sameExtAuthorIdArray[$jj]["author_id"],
                                        "is_delete" => 0
                                    );
                $targetAuthorData = $this->getExternalAuthorIdSuffix($where_params);
                if($targetAuthorData === false){
                    return false;
                }
                $status = "none";
                for($kk=0;$kk<count($targetAuthorData);$kk++){
                    for($ll=0;$ll<count($extAuthorIdArray);$ll++){
                        // Check prefix_id
                        if($targetAuthorData[$kk]["prefix_id"] == $extAuthorIdArray[$ll]["prefix_id"]){
                            // Same prefix_id
                            // Check suffix
                            if($targetAuthorData[$kk]["suffix"] == $extAuthorIdArray[$ll]["suffix"]){
                                // Same external_author_id
                                $status = "same";
                                $sameAuthorFlag = true;
                                break;
                            } else {
                                // Conflict
                                $status = "conflict";
                                break;
                            }
                        } else {
                            // Different prefix_id
                            $status = "none";
                        }
                    }
                    if($status == "conflict"){
                        array_push($excludeAuthorIdArray, $sameExtAuthorIdArray[$jj]["author_id"]);
                        $sameAuthorFlag = false;
                        break;
                    }
                }
                if($sameAuthorFlag === true){
                    $rtnAuthorId = intval($sameExtAuthorIdArray[$jj]["author_id"]);
                    break;
                }
            }
            if(strlen($rtnAuthorId)!=0 && $rtnAuthorId!=0){
                break;
            }
        }
        return $rtnAuthorId;
    }
    
    /**
     * Merge external authorID
     *
     * @param array() &$extAuthorIdArray[x]["prefix_id"]
     *                                     ["suffix"]
     * @param int &$authorId
     * @param boolean $displayOnly  false: "extAuthorIdArray" return all.
     *                               true: "extAuthorIdArray" return without the data block_id and room_id not match..
     */
    public function mergeExtAuthorId(&$extAuthorIdArray, &$authorId=0, $displayOnly=false){
        // Get mergeable authorID
        $margeAuthorId = $this->checkSamePrefixAndSuffix($extAuthorIdArray, $authorId);
        if(strlen($margeAuthorId)!=0 && $margeAuthorId!=0 && $margeAuthorId!=$authorId){
            if($authorId == 0){
                // This author is new.
                $authorId = $margeAuthorId;
            }
            // Get target external authorID
            $where_params = array(
                                    "author_id" => $margeAuthorId,
                                    "is_delete" => 0
                                );
            $mergeExtAurhorIdArray = $this->getExternalAuthorIdSuffix($where_params);
            if($mergeExtAurhorIdArray === false){
                return false;
            }
            $addTarget = $this->addMergeExtAuthorId($extAuthorIdArray, $mergeExtAurhorIdArray, $margeAuthorId);
            if($addTarget === false){
                return false;
            }
            
            if($authorId == $margeAuthorId){
                for($ii=0;$ii<count($addTarget);$ii++){
                    array_push($mergeExtAurhorIdArray, $addTarget[$ii]);
                }
                $extAuthorIdArray = $mergeExtAurhorIdArray;
            } else {
                $addTarget = $this->addMergeExtAuthorId($mergeExtAurhorIdArray, $extAuthorIdArray, $authorId);
                if($addTarget === false){
                    return false;
                }
                for($ii=0;$ii<count($addTarget);$ii++){
                    array_push($extAuthorIdArray, $addTarget[$ii]);
                }
            }
        }
        if($displayOnly){
            $tmp = array();
            for($ii=0; $ii<count($extAuthorIdArray); $ii++){
                // Get block_id and room_id by prefix_id
                $where_params = array(
                                        "prefix_id" => $extAuthorIdArray[$ii]["prefix_id"]
                                    );
                $result = $this->getExternalAuthorIdPrefix($where_params);
                if($result === false){
                    return false;
                }
                if( ($result[0]["block_id"]==0 && $result[0]["room_id"]==0) ||
                    ($result[0]["block_id"]==$this->block_id && $result[0]["room_id"]==$this->room_id))
                {
                    array_push($tmp, $extAuthorIdArray[$ii]);
                }
            }
            $extAuthorIdArray = $tmp;
        }
        return true;
    }
    
    /**
     * Add mergedata
     *
     * @param array() $from[x]["prefix_id"]
     *                        ["suffix"]
     * @param array() $to[x]["prefix_id"]
     *                      ["suffix"]
     * @param int $targetAuthorId
     * @return array() $added
     */
    public function addMergeExtAuthorId($from, $to, $targetAuthorId){
        $added = array();
        if(intval($targetAuthorId)!=0){
            for($ii=0;$ii<count($from);$ii++){
                $addFlag = true;
                for($jj=0;$jj<count($to);$jj++){
                    if($from[$ii]["prefix_id"]==$to[$jj]["prefix_id"]
                        && $from[$ii]["suffix"]==$to[$jj]["suffix"])
                    {
                        $addFlag = false;
                        break;
                    }
                }
                if($addFlag){
                    $params = array(
                                    "author_id" => $targetAuthorId,
                                    "prefix_id" => $from[$ii]["prefix_id"],
                                    "suffix" => $from[$ii]["suffix"],
                                    "ins_user_id" => $this->user_id,
                                    "mod_user_id" => $this->user_id,
                                    "del_user_id" => "",
                                    "ins_date" => $this->mod_date,
                                    "mod_date" => $this->mod_date,
                                    "del_date" => "",
                                    "is_delete" => 0
                                );
                    $return = $this->insExternalAuthorIdSuffix($params);
                    if($return === false){
                        return false;
                    }
                    
                    $prefix_name = $this->getExternalAuthorIdPrefixName($from[$ii]["prefix_id"]);
                    array_push( $added,
                                array(  "prefix_id" => $from[$ii]["prefix_id"],
                                        "suffix" => $from[$ii]["suffix"],
                                        "prefix_name" => $prefix_name
                                )
                            );
                }
            }
        }
        return $added;
    }
    
    /**
     * Get author by PrefixID and Suffix
     *
     * @param int $prefixId
     * @param string $suffix
     * @return unknown
     */
    public function getAuthorByPrefixAndSuffix($prefixId, $suffix){
        $query = "SELECT AUTHOR.author_id, AUTHOR.language, AUTHOR.family, ".
                 "AUTHOR.name, AUTHOR.family_ruby, AUTHOR.name_ruby, ".
                 "SUFFIX.prefix_id, SUFFIX.suffix ".
                 "FROM ". DATABASE_PREFIX ."repository_external_author_id_suffix AS SUFFIX ".
                 "INNER JOIN ".DATABASE_PREFIX ."repository_name_authority AS AUTHOR ".
                 "ON SUFFIX.author_id = AUTHOR.author_id ".
                 "WHERE SUFFIX.prefix_id = ? ".
                 "AND SUFFIX.suffix = ? ".
                 "AND SUFFIX.prefix_id >= 0 ".
                 "AND SUFFIX.is_delete = 0 ".
                 "AND AUTHOR.is_delete = 0;";
        $params = array();
        $params[] = $prefixId;    // prefix_id
        $params[] = $suffix;    // suffix
        // Execution SELECT
        $author_id_suffix = $this->Db->execute($query, $params);
        if($author_id_suffix === false){
            return false;
        }
        if(count($author_id_suffix) != 0){
            for($ii=0; $ii<count($author_id_suffix); $ii++){
                $author_id_suffix[$ii]["external_author_id"] = $this->getExternalAuthorIdPrefixAndSuffix($author_id_suffix[$ii]["author_id"], true);
            }
        }
        return $author_id_suffix;
    }
    
    /**
     * Get author by PrefixID and Suffix
     *
     * @param int $prefixId
     * @param string $suffix
     * @return unknown
     */
    public function getSuggestAuthorBySuffix($suffix){
        $query = "SELECT DISTINCT author_id ".
                 "FROM ". DATABASE_PREFIX ."repository_external_author_id_suffix ".
                 "WHERE suffix LIKE ? ".
                 "AND prefix_id > 0 ".
                 "AND is_delete = 0;";
        $params = array();
        $params[] = $suffix."%";    // suffix
        // Execution SELECT
        $author_id = $this->Db->execute($query, $params);
        if($author_id === false){
            return false;
        }
        return $author_id;
    }
}

?>
