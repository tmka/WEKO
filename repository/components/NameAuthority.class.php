<?php
// --------------------------------------------------------------------
//
// $Id: NameAuthority.class.php 58457 2015-10-06 02:18:19Z tatsuya_koyasu $
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
    private function insNameAuthority($params=array())
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
    private function updNameAuthority($params=array(), $where_params=array())
    {
        $result = $this->Db->updateExecute("repository_name_authority", $params, $where_params);
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
    private function insExternalAuthorIdPrefix($params=array())
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
    private function updExternalAuthorIdPrefix($params=array(), $where_params=array())
    {
        $result = $this->Db->updateExecute("repository_external_author_id_prefix", $params, $where_params);
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
    private function updateExternalAuthorIdPrefix($prefix_id){
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
    private function getNewPrefixId(){
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
     * duplicate key insert external author id
     * 外部著者IDを上書き保存する
     *
     * @param array $extAuthorIdArray[$ii]["prefix_id"]
     *                                    ["suffix"]
     *                                    ["old_prefix_id"]
     *                                    ["old_suffix"]
     *                                    ["prefix_name"]
     * @param int $authorId
     */
    private function upsertExternalAuthorId($extAuthorIdArray, $authorId){
        // Prefixが配列内に含まれているので、それを利用して保存する
        // 上書きする必要があるため、Duplicate Key Insertを利用する
        $query = "INSERT INTO ". DATABASE_PREFIX. "repository_external_author_id_suffix ".
                 " (author_id, prefix_id, suffix, ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete )".  
                 " VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ". 
                 " ON DUPLICATE KEY UPDATE ". 
                 "   suffix = ?, mod_user_id = ?, mod_date = ?;";
        for($ii = 0; $ii < count($extAuthorIdArray); $ii++){
            $params = array();
            $params[] = $authorId;
            $params[] = $extAuthorIdArray[$ii]["prefix_id"];
            $params[] = $extAuthorIdArray[$ii]["suffix"];
            $params[] = $this->user_id;
            $params[] = $this->user_id;
            $params[] = "";
            $params[] = $this->mod_date;
            $params[] = $this->mod_date;
            $params[] = "";
            $params[] = 0;
            $params[] = $extAuthorIdArray[$ii]["suffix"];
            $params[] = $this->user_id;
            $params[] = $this->mod_date;
            
            $result = $this->Db->execute($query, $params);
            if($result === false){
                $ex = new Exception($this->Db->ErrorMsg());
                throw $ex;
            }
        }
    }
    
    
    
    /**
     * Get a list of the author ID that partially match the external author ID
     * 外部著者ID群に部分一致する著者IDの一覧を取得する
     *
     * @param array $extAuthorIdArray
     * @return array: 外部著者IDのいずれかに一致した著者IDの一覧
     *                $authorIds[$ii]["author_id"] = value
     */
    private function selectAuthorIdList($extAuthorIdArray){
        $params = array();
        $whereString = "";
        for($ii = 0; $ii < count($extAuthorIdArray); $ii++){
            if(strlen($whereString) > 0){
                $whereString .= " OR ";
            } else {
                $whereString = " WHERE ";
            }
            $whereString .= " ( prefix_id = ? AND suffix = ?) ";
            $params[] = $extAuthorIdArray[$ii]["prefix_id"];
            $params[] = $extAuthorIdArray[$ii]["suffix"];
        }
        
        // 入力された外部著者IDとの比較のため、suffixが部分一致する著者IDのリストを作成する
        $query = "SELECT author_id ". 
                 " FROM ". DATABASE_PREFIX. "repository_external_author_id_suffix ". 
                 $whereString. 
                 " GROUP BY author_id ". 
                 " ORDER BY COUNT(author_id) DESC, author_id ASC;";
        
        $authorIds = $this->Db->execute($query, $params);
        if($authorIds === false){
            // データベースエラー
            $ex = new Exception($this->Db->ErrorMsg());
            throw $ex;
        }
        
        return $authorIds;
    }
    
    /**
     * With the exception of the non-input, it is confirmed that there is no difference 
     * in the external author ID stick string to an external author ID and the author ID
     * 未入力を除き、外部著者ID群と著者IDに紐付く外部著者ID群に差異がないことを確認する
     *
     * @param array $extAuthorIdArray
     * @param int $authorId
     * @return boolean: true  -> データベースと入力の差異はある
     *                  false -> データベースと入力に差異がない
     */
    private function isDiffExternalAuthorId($extAuthorIdArray, $authorId){
        // 著者IDのprefixおよびsuffixを全て取得する
        $query = "SELECT prefix_id, suffix ". 
                 " FROM ". DATABASE_PREFIX. "repository_external_author_id_suffix ". 
                 " WHERE author_id = ? ". 
                 " AND is_delete = ?;";
        $params = array();
        $params[] = $authorId;
        $params[] = 0;
        $result = $this->Db->execute($query, $params);
        if($result === false){
            // データベースエラー
            $ex = new Exception($this->Db->ErrorMsg());
            throw $ex;
        }
        
        // 入力された外部著者ID群と比較する
        $isDiff = false;
        for($jj = 0; $jj < count($extAuthorIdArray); $jj++){
            for($kk = 0; $kk < count($result); $kk++){
                if($extAuthorIdArray[$jj]["prefix_id"] == $result[$kk]["prefix_id"]){
                    if(strcmp($extAuthorIdArray[$jj]["suffix"], $result[$kk]["suffix"]) == 0){
                        // 問題無し
                        break;
                    } else {
                        // 入力された外部著者ID群と著者IDを指定した外部著者ID群の間に差異がある
                        $isDiff = true;
                        break;
                    }
                }
            }
        }
        
        if($isDiff === false){
            // 外部著者IDには合致している
            return false;
        } else {
            // ここまで来て外部著者IDに合致しないものがあった
            return true;
        }
    }
    
    /**
     * identify author id by input external id list and database
     * データベースに登録されている外部著者IDと入力された外部著者ID群から著者を特定し、外部著者IDを登録する
     *
     * @param array $extAuthorIdArray
     * @param int $authorId
     * @return int
     */
    private function identifyAuthorId($extAuthorIdArray, $authorId){
        $retAuthorId = 0;
        if(!isset($authorId) || $authorId === 0){
            // 著者の新規登録時
            $retAuthorId = $this->identifyAuthorIdForNew($extAuthorIdArray);
        } else {
            // 著者の更新時
            $retAuthorId = $this->identifyAuthorIdForEdit($extAuthorIdArray, $authorId);
        }
        return $retAuthorId;
    }
    
    
    /**
     * identify author id by external id for new
     * 著者新規登録時に外部著者ID群より著者IDを特定し、外部著者IDを登録する
     *
     * @param array $extAuthorIdArray[$ii]["prefix_id"]
     *                                    ["suffix"]
     *                                    ["old_prefix_id"]
     *                                    ["old_suffix"]
     *                                    ["prefix_name"]
     * @return int: $authorId = 0 -> 該当著者無し
     *              $authorId > 0 -> 該当著者の著者ID、外部著者IDに関して更新済み
     */
    private function identifyAuthorIdForNew($extAuthorIdArray){
        $authorId = 0;
        
        // 外部著者ID群の要素が0である時、確実に該当著者は存在しない
        if(count($extAuthorIdArray) > 0){
            // 外部著者ID群に部分一致する著者IDの一覧を取得する
            $authorIds = $this->selectAuthorIdList($extAuthorIdArray);
            
            for($ii = 0; $ii < count($authorIds); $ii++){
                // 入力された外部著者IDとデータベースに保存されている著者IDに紐付く外部著者IDで差異があるかを調べる
                // 未入力分は互いに無視される
                if(!$this->isDiffExternalAuthorId($extAuthorIdArray, $authorIds[$ii]["author_id"])){
                    $authorId = $authorIds[$ii]["author_id"];
                    break;
                }
            }
            
            // upsert
            if($authorId === 0){
                $authorId = $this->getNewAuthorId();
            }
            
            $this->upsertExternalAuthorId($extAuthorIdArray, $authorId);
        } else {
            // 外部著者ID群が空だった場合でも著者IDの発番は実施する
            // 外部著者IDを登録はしないが個人名や著者名典拠にデータを入力するために必要
            $authorId = $this->getNewAuthorId();
        }
        
        return $authorId;
    }
    
    /**
     * is exists mail address by external id list
     * 外部著者ID群内にメールアドレスが存在するかを確認する
     *
     * @param array $extAuthorIdArray[$ii]["prefix_id"]
     *                                    ["suffix"]
     *                                    ["old_prefix_id"]
     *                                    ["old_suffix"]
     *                                    ["prefix_name"]
     * @return boolean: 外部著者ID群の中にメールアドレスが存在するか否か true ->  存在する
     *                                                        false -> 存在しない
     */
    private function isExistsMailaddress($extAuthorIdArray){
        // 外部著者ID群の中からpreifxが0である値がないかを探す
        for($ii = 0; $ii < count($extAuthorIdArray); $ii++){
            if($extAuthorIdArray[$ii]["prefix_id"] === 0){
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * identify author id by external id for editting
     * 編集している著者の著者IDを外部著者IDから特定し、外部著者IDを登録する
     *
     * @param array $extAuthorIdArray
     * @param int $editAuthorId: 編集中著者ID
     * @return int: 外部著者IDから特定した著者ID
     */
    private function identifyAuthorIdForEdit($extAuthorIdArray, $editAuthorId){
        if(count($extAuthorIdArray) === 0){
            // 外部著者IDが空である場合、著者の同定を実施することはできない
            // 著者の同定も行われないため、入力された著者IDを返す
            return $editAuthorId;
        }
        
        $authorId = 0;
        
        // 編集中著者IDに紐付く外部著者ID群と入力の外部著者ID群と比較する
        if($this->isDiffExternalAuthorId($extAuthorIdArray, $editAuthorId)){
            // 差異があるならば新しく同定可能な著者IDを探し、登録を実施する
            $authorId = $this->identifyAuthorIdForNew($extAuthorIdArray);
        } else {
            // 外部著者IDを保存する
            $authorId = $editAuthorId;
            
            // 差異がないなら追加分を含めて登録を実施する
            $this->upsertExternalAuthorId($extAuthorIdArray, $authorId);
        }
        
        return $authorId;
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
        
        $metadata["author_id"] = $this->identifyAuthorId($metadata["external_author_id"], $metadata["author_id"]);
        
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
        
        return $metadata["author_id"];
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
     * @return int: 著者ID
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
