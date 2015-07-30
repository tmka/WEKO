<?php
// --------------------------------------------------------------------
//
// $Id: Validator_DownloadCheck.class.php 43338 2014-10-29 05:14:00Z tomohiro_ichikawa $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics,
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/JSON.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryIndexAuthorityManager.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryDbAccess.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryCheckFileTypeUtility.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHandleManager.class.php';

/**
 * validator file download.
 *
 */
class Repository_Validator_DownloadCheck extends Validator
{
    const ACCESS_OPEN  = 0;
    const ACCESS_LOGIN = 1;
    const ACCESS_CLOSE = 2;
    
    // conponents
    private $Session = null;
    private $Db = null;
    private $RepositoryAction = null;
    private $block_id = "";
    private $page_id = "";
    
    // for index thumbnail download
    private $index_id = "";
    
    // Add PDF cover page 2012/06/13 A.Suzuki --start--
    // for PDF cover page header image download
    private $pdf_cover_header = "";
    // Add PDF cover page 2012/06/13 A.Suzuki --end--
    
    
    // for file download
    private $item_id = "";
    private $item_no = "";
    private $attribute_id = "";
    private $file_no = "";
    
    // for download content type
    private $file_prev = "";        // downlaod file preview
    private $img = "";              // download thumbnail
    private $item_type_id = "";     // download item type icon
    private $flash = "";            // download flash
    private $pay = "false";         // user agree pay for view file.
    
    // Fix jump to close detail page. 2012/01/30 Y.Nakao --start--
    private $itemPubFlg = 1;
    // Fix jump to close detail page. 2012/01/30 Y.Nakao --end--
    
    // Fix when this class user else action_common_download, not access idserver 2013/04/10 Y.Nakao --start--
    // action_common_download以外からのアクセスだった場合、課金サーバーにcreateChargeリクエストが飛ばないように対応
    private $fromCommonDownload = false;
    // Fix when this class user else action_common_download, not access idserver 2013/04/10 Y.Nakao --end--
    
    // for openaccess download 2013/06/12 K.Matsuo --start--
    public $openAccessDate = "";
    // for openaccess download 2013/06/12 K.Matsuo --end--
    
    private $dbAccess = null;
    
    /**
     * setting components
     *
     * @param SessionObject $session
     * @param DbObject $db
     * @param blockId $blockId
     * @return boolean true / false
     */
    public function setComponents($session, $db)
    {
        if($session==null || $db==null)
        {
            return false;
        }
        $this->Session = $session;
        $this->Db = $db;
        $this->RepositoryAction = new RepositoryAction();
        $this->RepositoryAction->Session = $this->Session;
        $this->RepositoryAction->Db = $this->Db;
        $result = $this->RepositoryAction->initAction();
        $result = $this->RepositoryAction->exitAction();
        if ( $result === false )
        {
            return false;
        }
        return true;
    }
    
    /**
     * file download check
     * */
    function validate($attributes, $errStr, $params)
    {
        ////////// set parameter //////////
        $this->setAttributesParameter($attributes);
        
        // ファイルダウンロード処理呼び出しの場合
        if( strlen($this->item_type_id) > 0 ||
            strlen($this->index_id) > 0 ||
            strlen($this->pdf_cover_header) > 0 ||
            strlen($this->file_prev) > 0 ||
            strlen($this->img) > 0)
        {
            // アイテムタイプアイコン、インデックスサムネイル、PDFカバーへーヘッダー画像、ファイルのサムネイル画像、
            // サムネイルメタデータ登録画像の場合はバリデートなしでDL可能
            return;
        }
        
        // error message
        $errorMsg = explode(',', $errStr);
        
        // Parameter invalid check
        if(!is_numeric($this->item_id) || $this->item_id < 1 || !is_numeric($this->item_no) || $this->item_no < 1 ||
           !is_numeric($this->attribute_id) || $this->attribute_id < 1 || !is_numeric($this->file_no) || $this->file_no < 1)
        {
            return "error:$errorMsg[0]:$this->page_id:$this->block_id";
        }
        
        // download request file info.
        $fileinfo = $this->item_id."_".$this->item_no."_".$this->attribute_id."_".$this->file_no;
        
        // from common_download action
        $this->fromCommonDownload = true;
        
        $status = $this->checkFileDownloadViewStatus();
        if($status == "login")
        {
            // ----------------------------------------
            // reload url(for visible login dialog)
            // ----------------------------------------
            $container =& DIContainerFactory::getContainer();
            $actionChain =& $container->getComponent("ActionChain");
            if($this->Session->getParameter("_mobile_flag") != _ON && $actionChain->_recursive_action != 'pages_view_main')
            {
                // get action_name
                $_request =& $container->getComponent("Request");
                $actionName = $_request->getParameter("action");
                $url = BASE_URL.'/?action=pages_view_main'.
                        "&active_action=$actionName".
                        "&item_id=$this->item_id&item_no=$this->item_no&attribute_id=$this->attribute_id&file_no=$this->file_no";
                $url .= '&block_id='.$this->block_id.
                        '&page_id='.$this->page_id;
                
                // reload
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: ".$url);
                
                exit();
            }
            
            // ----------------------------------------
            // remove or set reload parameter
            // ----------------------------------------
            if($this->Session->getParameter("_mobile_flag") == _OFF)
            {
                $this->Session->setParameter('repository'.$this->block_id.'FileDownloadKey', $this->item_no."_".$this->attribute_id."_".$this->file_no);
            }
            
            // ----------------------------------------
            // login error
            // ----------------------------------------
            $loginInfo = $this->makeLoginInformation();
            if($this->openAccessDate == ""){
                return "loginRequest:$errorMsg[1]:$loginInfo:$fileinfo:$this->page_id:$this->block_id:".$this->itemPubFlg;
            }
            else {
                $tmpPubDate = explode("-", $this->openAccessDate);
                $tmpErrMsg = str_replace("YYYY", $tmpPubDate[0], $errorMsg[7]);
                $tmpErrMsg = str_replace("MM", $tmpPubDate[1], $tmpErrMsg);
                $tmpErrMsg = str_replace("DD", $tmpPubDate[2], $tmpErrMsg);
                return "loginRequest:$tmpErrMsg:$loginInfo:$fileinfo:$this->page_id:$this->block_id:".$this->itemPubFlg;
            }
        }
        else if($status == "delete")
        {
            return "$status:$errorMsg[5]:$this->page_id:$this->block_id";
        }
        else if($status == "close" || $status == "false")
        {
            // false => file_price and ID server not link
            return "$status:$errorMsg[3]:$fileinfo:$this->page_id:$this->block_id:".$this->itemPubFlg;
        }
        else if($status == "error")
        {
            return "$status:$errorMsg[4]";
        }
        else if($status == 'creditError')
        {
            return "$status";
        }
        else if($status == 'GMOError')
        {
            return "$status:$this->page_id:$this->block_id:$this->item_id:$this->item_no";
        }
        // Bug Fix setting not download file by bill server 2014/10/20 T.Koyasu --start--
        else if($status == "shared"){
            // this user can not credit card info, bacause user is shared account
            return "$status:$fileinfo:$this->page_id:$this->block_id:". $this->itemPubFlg;
        }
        else if($status == "unknown"){
            // this user is not regist credit card info
            return "$status";
        }
        // Bug Fix setting not download file by bill server 2014/10/20 T.Koyasu --end--
        else if($status == "free" || $status == "already" || $status == "admin" || $status == "license")
        {
            return;
        }
        else
        {
            // return "trade_id:price"
            if($this->pay == 'true')
            {
                $trade_id_price = split(":", $status, 2);
                if($trade_id_price[0] == ""){
                    return "error:$errorMsg[4]";
                }
                if($this->closeCharge($trade_id_price[0]))
                {
                    $this->Session->setParameter('repository'.$this->block_id.'FileDownloadKey', $this->item_no."_".$this->attribute_id."_".$this->file_no);
                    
                    $url = BASE_URL.'/?action=pages_view_main'.
                            "&active_action=repository_view_main_item_detail".
                            "&item_id=$this->item_id&item_no=$this->item_no".
                            '&block_id='.$this->block_id.
                            '&page_id='.$this->page_id;
                    // reload
                    header("HTTP/1.1 301 Moved Permanently");
                    header("Location: ".$url);
                    exit();
                } else {
                    return "error:$errorMsg[4]";
                }
            }
            //status = trade_id:price
            return "needPay:$status:$errorMsg[6]:$fileinfo:$this->page_id:$this->block_id";
        }
    }
    
    /**
     * Check File Download Status
     *
     * @return string
     */
    private function checkFileDownloadViewStatus()
    {
        $login_id = $this->Session->getParameter("_login_id");
        $user_id = $this->Session->getParameter("_user_id");
        $user_auth_id = $this->Session->getParameter("_user_auth_id");
        $auth_id = $this->RepositoryAction->getRoomAuthorityID();
        
        // アイテムの公開状況
        $this->itemPubFlg = 1;
        if($this->checkCanItemAccess($this->item_id, $this->item_no) == false)
        {
            // アイテム非公開 / item closed
            $this->itemPubFlg = 0;
            // アイテムもしくは所属インデックスが非公開 => 会員のみ閲覧を許可される場合と同等
            // ログインしているかで判定
            if($user_id != "0" && strlen($login_id) != 0)
            {
                // Ok login
                return "close";
            }
            else
            {
                // not login
                return "login";
            }
        }
        
        // ファイルデータ取得
        $fileData = $this->getFileData($this->item_id, $this->item_no, $this->attribute_id, $this->file_no);
        if(count($fileData) == 0)
        {
            return "delete";
        }
        
        // ファイル/FLASHの公開状況をチェック
        $status = "close";
        if($this->flash == "true")
        {
            $status = $this->checkFlashAccessStatus($fileData);
        }
        else
        {
            $status = $this->checkFileAccessStatus($fileData);
        }
        
        return $status;
    }
    
    /**
     * check flash access status
     *
     * @param array $fileData file table record. count == 1.
     */
    public function checkFlashAccessStatus($fileData)
    {
        // set flash pub date in 'pub_date'
        if(strlen($fileData[RepositoryConst::DBCOL_REPOSITORY_FILE_FLASH_PUB_DATE]) > 0)
        {
            $fileData[RepositoryConst::DBCOL_REPOSITORY_FILE_PUB_DATE] = $fileData[RepositoryConst::DBCOL_REPOSITORY_FILE_FLASH_PUB_DATE];
        }
        
        // check flash exists.
        if($this->existsFlashContents(  $fileData[RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID], 
                                        $fileData[RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO], 
                                        $fileData[RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID], 
                                        $fileData[RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO]) === false)
        {
            return "delete";
        }
        
        // when display_typ != flash ivew, flash can't download.
        $displayType = $fileData['display_type'];
        if($displayType != RepositoryConst::FILE_DISPLAY_TYPE_FLASH)
        {
            return "close";
        }
        
        // check file status.
        return $this->checkFileAccessStatus($fileData);
    }
    
    /**
     * check file status
     *
     * @param array $fileData file table record. count == 1.
     * @param boolean $accessChargeFlg default true // Add Charge status is not check by snippet T.Koyasu 2014/09/24 
     */
    public function checkFileAccessStatus($fileData, $accessChargeFlg = true)
    {
        $login_id = $this->Session->getParameter("_login_id");
        $user_id = $this->Session->getParameter("_user_id");
        $user_auth_id = $this->Session->getParameter("_user_auth_id");
        $auth_id = $this->RepositoryAction->getRoomAuthorityID();
        
        $itemId = $fileData[RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID];
        $itemNo = $fileData[RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO];
        $attributeId = $fileData[RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID];
        $fileNo = $fileData[RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO];
        
        // check site license
        $siteLicense = $this->checkSiteLicense($itemId, $itemNo);
        
        // check admin user
        $adminUser = false;
        if( $user_auth_id >= $this->RepositoryAction->repository_admin_base && 
            $auth_id >= $this->RepositoryAction->repository_admin_room)
        {
            $adminUser = true;
        }
        
        // check insert user
        $insUser = false;
        if( $user_id == $fileData[RepositoryConst::DBCOL_COMMON_INS_USER_ID])
        {
            $insUser = true;
        }
        
        // check file exists
        $fileName = $itemId."_".$attributeId."_".$fileNo.".".$fileData[RepositoryConst::DBCOL_REPOSITORY_FILE_EXTENSION];
        if($this->existsFileContents($fileName) === false)
        {
            return "delete";
        }
        
        // check hidden metadata.
        if( !$adminUser && !$insUser && $this->checkHiddenMetadata($fileData[RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_TYPE_ID], $attributeId))
        {
            // admin or inser user => download OK.
            // hidden metadata is close
            return "close";
        }
        
        // check file pub date.
        $pubDate = $fileData[RepositoryConst::DBCOL_REPOSITORY_FILE_PUB_DATE];
        $accessFlag = $this->checkFileDownloadViewFlag($pubDate, $this->RepositoryAction->TransStartDate);
        if($accessFlag == self::ACCESS_OPEN)
        {
            // open access file
            // オープンアクセスファイルなので"free"を返す
            return "free";
        }
        else if($adminUser || $insUser)
        {
            // supar user （管理者 または 登録者）
            return "admin";
        }
        else if($accessFlag == self::ACCESS_CLOSE)
        {
            // ファイルを公開しない
            return "close";
        }
        else if($siteLicense == "true")
        {
            // サイトライセンスが有効である
            return "license";
        }
        else if($user_id == "0" || strlen($login_id) == 0)
        {
            // not login user. （未ログインユーザー）
            return "login";
        }
        
        // check file price
        $priceStatus = $this->checkFilePrice($itemId, $itemNo, $attributeId, $fileNo);
        // Add Charge status is not check by snippet T.Koyasu 2014/09/24 --start--
        if($priceStatus != "free" && $priceStatus != "login" && $priceStatus != "close" && $accessChargeFlg == true)
        {
            // $priceStatus = file price
            // check pay status
            $priceStatus = $this->accessChargeServer($itemId, $itemNo, $attributeId, $fileNo, $priceStatus);
        }
        // Add Charge status is not check by snippet T.Koyasu 2014/09/24 --end--
        return $priceStatus;
    }
    
        /**
     * check file price
     *
     * @return string viewFlag_downloadFlag
     *                error
     *                free  
     *                unknown
     *                creditError
     *                close
     *                GMOError
     *                trade_id:price
     */
    public function checkFilePrice($item_id, $item_no, $attribute_id, $file_no){
        // check input_type (file or file_price)
        // Select 
        $query = "SELECT pub_date, flash_pub_date, ins_user_id ".
                 "FROM ". DATABASE_PREFIX ."repository_file ".
                 "WHERE item_id = ? ".
                 "  AND item_no = ? ".
                 "  AND attribute_id = ? ".
                 "  AND file_no = ? ".
                 "  AND is_delete = 0";
        $params = array();
        $params[] = $item_id;
        $params[] = $item_no;
        $params[] = $attribute_id;
        $params[] = $file_no;
        $file = $this->Db->execute($query, $params);
        if ($file === null) {
            return "error";
        }
        $user_auth_id = $this->Session->getParameter("_user_auth_id");
        $auth_id = $this->RepositoryAction->getRoomAuthorityID();
        $user_id = $this->Session->getParameter("_user_id");

        if(($user_auth_id >= $this->RepositoryAction->repository_admin_base && $auth_id >= $this->RepositoryAction->repository_admin_room) || $file[0]['ins_user_id'] === $user_id){
            return 'free';    
        }
        $price = $this->getFilePriceTable($item_id, $item_no, $attribute_id, $file_no);
        // End when retrieval result is not one
        if(count($price) > 1){
            return "error";
        } else if (count($price) == 1) {
            // input_type == file_price
            // get file price
            $file_price = $this->getFilePrice($price[0]["price"]);         
            // get file price
            if($file_price === "0"){
                return 'free';
            } else if($file_price === ""){
                $login_id = $this->Session->getParameter("_login_id");
                if($user_id == "0" || strlen($login_id) == 0){
                    return "login";
                }
                return 'close';
            } else {
                $login_id = $this->Session->getParameter("_login_id");
                $user_id = $this->Session->getParameter("_user_id");
                if($user_id == "0" || strlen($login_id) == 0){
                    return "login";
                }
                return $file_price;
            }
        // no price
        } else {
            return "free";
        }
    }
    
    /**
     * getFilePriceTable
     * 課金情報を取得する
     * 
     * @param $file_info ファイル情報
     * @return $result_file_price_Table 課金ファイル情報
     */
    function getFilePriceTable($item_id, $item_no, $attribute_id, $file_no){
        // 課金情報をチェック
        $query = "SELECT * ".
                 "FROM ". DATABASE_PREFIX. "repository_file_price ".
                 "WHERE item_id = ? ".
                 "AND item_no = ? ".
                 "AND attribute_id = ? ".
                 "AND file_no = ? ".
                 "AND is_delete = 0;";
        $params = array();
        $params[] = $item_id;
        $params[] = $item_no;
        $params[] = $attribute_id;
        $params[] = $file_no;
        $result_file_price_Table = $this->Db->execute($query, $params);
        if($result_file_price_Table === false)
        {
            return array();
        }
        return $result_file_price_Table;
    }
    
    /**
     * get access user's file price
     *
     * @param string $price room_id,rpice|room_id,price|...
     * @return string file price
     */
    public function getFilePrice($price)
    {
        ///// get groupID and price /////
        $room_price = explode("|",$price);
        ///// ユーザが入っているグループIDを取得 /////
        $result = $this->RepositoryAction->getUsersGroupList($user_group,$error_msg);
        if($result===false){
            return false;
        }
        $file_price = "";
        for($price_Cnt=0;$price_Cnt<count($room_price);$price_Cnt++){
            $price = explode(",", $room_price[$price_Cnt]);
            // There is a pair of room_id and the price. 
            if($price!=null && count($price)==2)
            {
                // It is judged whether it is user's belonging group.
                for($user_group_cnt=0;$user_group_cnt<count($user_group);$user_group_cnt++){
                    if($price[0] == $user_group[$user_group_cnt]["room_id"]){
                        // When the price is set to the belonging group
                        if($file_price==""){
                            // The price is maintained at the unsetting. 
                            $file_price = $price[1];
                        } else if(intval($file_price) > intval($price[1])){
                            // It downloads it by the lowest price. 
                            $file_price = $price[1];
                        }
                    }
                }
            }
        }
        return $file_price;
    }
    
    /**
     * check hidden metadata
     * 
     * @param int $itemTypeId
     * @return bool true:hidden metadata, false:not hidden metadata
     */
    private function checkHiddenMetadata($itemTypeId, $attributeId)
    {
        $query = " SELECT hidden ".
                 " FROM ".DATABASE_PREFIX."repository_item_attr_type ".
                 " WHERE item_type_id = ? ".
                 "   AND attribute_id = ? ".
                 "   AND is_delete = ? ";
        $params = array();
        $params[] = $itemTypeId;
        $params[] = $attributeId;
        $params[] = 0;
        $itemAttrType = $this->Db->execute($query, $params);
        if($itemAttrType === false)
        {
            return true;
        }
        if(count($itemAttrType) != 1)
        {
            return true;
        }
        if($itemAttrType[0][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_HIDDEN] == 0)
        {
            return false;
        }
        return true;
    }
    
    /**
     * get filedata
     * 
     * @param int $itemId
     * @param int $itemNo
     * @param int $attributeId
     * @param int $fileNo
     */
    private function getFileData($itemId, $itemNo, $attributeId, $fileNo)
    {
        // ファイルデータの存在チェック
        $query = " SELECT * ".
                 " FROM ".DATABASE_PREFIX."repository_file ".
                 " WHERE item_id = ? ".
                 "  AND item_no = ? ".
                 "  AND attribute_id = ? ".
                 "  AND file_no = ? ".
                 "  AND is_delete = ? ";
        $params = array();
        $params[] = $itemId;
        $params[] = $itemNo;
        $params[] = $attributeId;
        $params[] = $fileNo;
        $params[] = 0;
        $file = $this->Db->execute($query, $params);
        if($file === false)
        {
            return array();
        }
        else if(count($file) != 1)
        {
            return array();
        }
        return $file[0];
    }
    
    /**
     * check flash contents exists
     *
     * @param int $item_id
     * @param int $item_no
     * @param int $attribute_id
     * @param int $file_no
     * @return bool true:exists, false:not exists
     */
    function existsFlashContents($item_id, $item_no, $attribute_id, $file_no)
    {
        $flash_contents_path = $this->RepositoryAction->getFlashFolder($item_id,$attribute_id, $file_no);
        // get directory path of image
        $image_contents_path = $this->RepositoryAction->getFileSavePath('file');
        if(strlen($image_contents_path) == 0){
            // default directory
            $image_contents_path = BASE_DIR.'/webapp/uploads/repository/files';
        }
        // get file extension
        $query = "SELECT mime_type, extension FROM ". DATABASE_PREFIX ."repository_file ".
                 "WHERE item_id = ? ".
                 "AND item_no = ? ".
                 "AND attribute_id = ? ".
                 "AND file_no = ? ".
                 "AND is_delete = ? ;";
        $params = array();
        $params[] = $item_id;
        $params[] = $item_no;
        $params[] = $attribute_id;
        $params[] = $file_no;
        $params[] = 0;
        $result = $this->Db->execute($query, $params);
        if($result === false){
            return false;
        }
        
        if(file_exists($flash_contents_path.DIRECTORY_SEPARATOR.'/weko.swf'))
        {
            return true;
        }
        else if(file_exists($flash_contents_path.DIRECTORY_SEPARATOR.'/weko1.swf'))
        {
            return true;
        }
        // Add multimedia support 2012/08/27 T.Koyasu -start-
        // add weko.flv to flash contents
        else if(file_exists($flash_contents_path.DIRECTORY_SEPARATOR.'/weko.flv'))
        {
            return true;
        }
        // Add multimedia support 2012/08/27 T.Koyasu -end-
        // Add image support 2014/01/16 R.Matsuura --start--
        else if( RepositoryCheckFileTypeUtility::isImageFile($result[0]['mime_type'], $result[0]['extension']) 
              && file_exists($image_contents_path.DIRECTORY_SEPARATOR.$item_id.'_'.$attribute_id.'_'.$file_no.'.'.$result[0]['extension']) )
        {
            return true;
        }
        // Add image support 2014/01/16 R.Matsuura --end--
        else
        {
            return false;
        }
    }
    
    /**
     * check file contents exists
     *
     * @param int $item_id
     * @param int $item_no
     * @param int $attribute_id
     * @param int $file_no
     * @return bool true:exists, false:not exists
     */
    function existsFileContents($fileName)
    {
        $filecontents_path = $this->RepositoryAction->getFileSavePath("file");
        if(strlen($filecontents_path) == 0)
        {
            // default directory
            $filecontents_path = BASE_DIR.'/webapp/uploads/repository/files';
        }
        if(file_exists($filecontents_path.DIRECTORY_SEPARATOR.$fileName))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    /**
     * Check file download view flag
     * 
     * @param string $pubDate
     * @param string $transStartDate
     * @param bool $isFlash
     * @return string accessFlag 0: open
     *                           1: need login
     *                           2: not access
     */
    public function checkFileDownloadViewFlag($pubDate, $transStartDate)
    {
        $accessFlag = self::ACCESS_CLOSE;
        $this->openAccessDate = "";
        // toInt now date
        $date = explode(" ", $transStartDate);
        $nowDate = implode('', explode("-", $date[0]));
        
        // toInt pub date
        $divPubDate = explode(" ", $pubDate);
        $tmpPubDate = implode('', explode("-", $divPubDate[0]));
        if($tmpPubDate == '99991231')
        {
            // not access
            $accessFlag = self::ACCESS_CLOSE;
        }
        else if($tmpPubDate == '99990101')
        {
            // login only or flash publish date is future.
            // need login.
            $accessFlag = self::ACCESS_LOGIN;
        }
        else if($tmpPubDate > $nowDate)
        {
            // login only or flash publish date is future.
            // need login.
            $this->openAccessDate = $divPubDate[0];
            $accessFlag = self::ACCESS_LOGIN;
        }
        else
        {
            // open
            $accessFlag = self::ACCESS_OPEN;
        }
        
        return $accessFlag;
    }
    
    /**
     * check item access flag
     *
     * @return bool item_accessFlag  true:canAccess, false:cannnotAccess  
     */
    public function checkCanItemAccess($item_id, $item_no)
    {
        // Fix insert user fileDL 2012/01/30 Y.Nakao --start--
        $user_id = $this->Session->getParameter("_user_id");
        $user_auth_id = $this->Session->getParameter("_user_auth_id");
        $auth_id = $this->RepositoryAction->getRoomAuthorityID();
        
        if($user_auth_id >= $this->RepositoryAction->repository_admin_base && $auth_id >= $this->RepositoryAction->repository_admin_room)
        {
            // for admin
            return true;
        }
        // Fix insert user fileDL 2012/01/30 Y.Nakao --end--
        
        // check item public
        $query = "SELECT shown_date, shown_status, ins_user_id".
                " FROM ".DATABASE_PREFIX."repository_item ".
                " WHERE item_id = ? ".
                " AND item_no = ? ".
                 "AND is_delete = ? ";
        $param = array();
        $param[] = $item_id;
        $param[] = $item_no;
        $param[] = 0;
        $result = $this->Db->execute($query, $param);
        // check get data.
        if($result === false){
            return false;
        } else if(count($result) == 0){
            return false;
        } else if(count($result) > 1){
            return false;
        } else if(!isset($result[0])){
            return false;
        }
        
        // Fix insert user fileDL 2012/01/30 Y.Nakao --start--
        if($user_id === $result[0]['ins_user_id'])
        {
            // for insert user.
            return true;
        }
        if($result[0]['shown_date'] > $this->RepositoryAction->TransStartDate || $result[0]['shown_status'] != 1)
        {
            // item close.
            return false;
        }
        // Fix insert user fileDL 2012/01/30 Y.Nakao --end--
        
        // check index public status.
        $public_index = array();
        // Add Open Depo 2013/12/03 R.Matsuura --start--
        // Mod OpenDepo 2014/01/31 S.Arata --start--
        $this->RepositoryAction->setConfigAuthority();
        $this->dbAccess = new RepositoryDbAccess($this->Db);
        $indexAuthorityManager = new RepositoryIndexAuthorityManager($this->Session, $this->dbAccess, $this->RepositoryAction->TransStartDate);
        // Mod OpenDepo 2014/01/31 S.Arata --end--
        // Add Open Depo 2013/12/03 R.Matsuura --end--
        
        // check position index public
        $query = "SELECT index_id ".
                " FROM ".DATABASE_PREFIX."repository_position_index ".
                " WHERE item_id = ? ".
                " AND item_no = ? ".
                " AND is_delete = 0 ; ";
        $param = array();
        $param[] = $item_id;
        $param[] = $item_no;
        $result = $this->Db->execute($query, $param);
        if($result === false){
            return false;
        } else if(count($result) == 0){
            return false;
        }else if(count($result) > 0){
            for($ii=0; $ii<count($result); $ii++){
                $public_index = $indexAuthorityManager->getPublicIndex(false, $this->RepositoryAction->repository_admin_base, $this->RepositoryAction->repository_admin_room, $result[$ii]["index_id"]);
                if(count($public_index) > 0){
                    // index is public
                    // and item public
                    return true;
                }
            }
        }
        
        // item is public, index is close.
        return false;
    }
    
    // Add check site license 2008/10/20 Y.Nakao --start--
    public function checkSiteLicense($item_id="", $item_no=""){
        // get user ip address
        if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && strlen($_SERVER['HTTP_X_FORWARDED_FOR']) > 0) {
            $access_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $access_ip = getenv("REMOTE_ADDR");
        }
        $ipaddress = explode(".", $access_ip);
        // get param table data : site_license
        $query = "SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter ".
                 "WHERE param_name = 'site_license'; ";
        $result = $this->Db->execute($query);
        if($result === false){
            return "false";
        }
        $site_license = explode("|", $result[0]['param_value']);
        
        $site_license_item_type_id = "";
        $item_type_id = "";
        if(strlen($item_id)>0 && strlen($item_no)>0)
        {
            // Add item_type_id for site license 2009/01/07 A.Suzuki --start--
            // get param table data : site_license_item_type_id
            $query = "SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter ".
                     "WHERE param_name = 'site_license_item_type_id'; ";
            $result = $this->Db->execute($query);
            if($result === false){
                return "false";
            }
            $site_license_item_type_id = explode(",", $result[0]['param_value']);
            
            // get item_type_id
            $query = "SELECT item_type_id ".
                     "FROM ".DATABASE_PREFIX."repository_item ".
                     "WHERE  item_id = ? ".
                     "AND    item_no = ? ".
                     "AND    is_delete = 0;";
            $params = null;
            $params[] = $item_id;
            $params[] = $item_no;
            $result = $this->Db->execute($query, $params);
            if($result === false){
                return "false";
            }
            $item_type_id = $result[0]['item_type_id'];
            // Add item_type_id for site license 2009/01/07 A.Suzuki --end--
        }
        
        for($ii=0; $ii<count($site_license); $ii++){
            $param_site_license = explode(",", $site_license[$ii]);
            $ipaddress_from = array("");
            if(isset($param_site_license[1])){
                $ipaddress_from = explode(",", $param_site_license[1]);
            }
            if(isset($param_site_license[2]) && $param_site_license[2] != ""){
                // from to
                // Fix roop bug 2010/10/14 Y.Nakao --start--
                $ip = sprintf("%03d", $ipaddress[0]).
                      sprintf("%03d", $ipaddress[1]).
                      sprintf("%03d", $ipaddress[2]).
                      sprintf("%03d", $ipaddress[3]);
                $ipaddress_to = explode(",", $param_site_license[2]);
                $ipaddress_from = explode(".", $ipaddress_from[0]);
                $ipaddress_to = explode(".", $ipaddress_to[0]);
                $from = sprintf("%03d", $ipaddress_from[0]).
                        sprintf("%03d", $ipaddress_from[1]).
                        sprintf("%03d", $ipaddress_from[2]).
                        sprintf("%03d", $ipaddress_from[3]);
                $to   = sprintf("%03d", $ipaddress_to[0]).
                        sprintf("%03d", $ipaddress_to[1]).
                        sprintf("%03d", $ipaddress_to[2]).
                        sprintf("%03d", $ipaddress_to[3]);  
                if( $from <= $ip && $ip <= $to ){
                    if(strlen($item_id)>0 && strlen($item_no)>0)
                    {
                        for($jj=0; $jj<count($site_license_item_type_id); $jj++){
                            if($site_license_item_type_id[$jj] == $item_type_id){
                                return "false";
                            }
                        }
                    }
                    return "true";
                }
                // Fix roop bug 2010/10/14 Y.Nakao --end--
            } else {
                // same ip
                if($access_ip == $ipaddress_from[0]){
                    if(strlen($item_id)>0 && strlen($item_no)>0)
                    {
                        for($jj=0; $jj<count($site_license_item_type_id); $jj++){
                            if($site_license_item_type_id[$jj] == $item_type_id){
                                return "false";
                            }
                        }
                    }
                    return "true";
                }
            }
        }
        return "false";
    }
    
    /**
     * set parameter
     *
     * @param unknown_type $attributes
     */
    private function setAttributesParameter($attributes)
    {
        $container =& DIContainerFactory::getContainer();
        $this->Session =& $container->getComponent("Session");
        $this->Db =& $container->getComponent("DbObject");
        $this->RepositoryAction = new RepositoryAction();
        $this->RepositoryAction->Session = $this->Session;
        $this->RepositoryAction->Db = $this->Db;
        $result = $this->RepositoryAction->initAction();
        $result = $this->RepositoryAction->exitAction();
        
        // item_id
        if(isset($attributes[0]) && strlen($attributes[0]) > 0){
            $this->item_id = $attributes[0];
        }
        // item_no
        if(isset($attributes[1]) && strlen($attributes[1]) > 0){
            $this->item_no = $attributes[1];
        }
        // attribute_id
        if(isset($attributes[2]) && strlen($attributes[2]) > 0){
            $this->attribute_id = $attributes[2];
        }
        // file_no
        if(isset($attributes[3]) && strlen($attributes[3]) > 0){
            $this->file_no = $attributes[3];
        }
        // block id
        if(isset($attributes[4]) && strlen($attributes[4]) > 0 && $attributes[4] != "0"){
            $this->block_id = $attributes[4];
        }
        // page id
        if(isset($attributes[5]) && strlen($attributes[5]) > 0 && $attributes[4] != "0"){
            $this->page_id = $attributes[5];
        }
        
        // when block_id or page_id is not set, set parameter.
        if(strlen($this->block_id) == 0 || strlen($this->page_id) == 0)
        {
            $block_info = $this->RepositoryAction->getBlockPageId();
            $this->block_id = $block_info["block_id"];
            $this->page_id  = $block_info["page_id"];
        }
        
        // file prev
        if(isset($attributes[6]) && strlen($attributes[6]) > 0){
            $this->img = $attributes[6];
        }
        // file prev
        if(isset($attributes[7]) && strlen($attributes[7]) > 0){
            $this->item_type_id = $attributes[7];
        }
        // file prev
        if(isset($attributes[8]) && strlen($attributes[8]) > 0){
            $this->file_prev = $attributes[8];
        }
        // index thumnail
        if(isset($attributes[9]) && strlen($attributes[9]) > 0){
            $this->index_id = $attributes[9];
        }
        // The intention to pay 
        if(isset($attributes[10]) && strlen($attributes[10]) > 0){
            $this->flash = $attributes[10];
        }
        // The intention to pay 
        if(isset($attributes[11]) && strlen($attributes[11]) > 0){
            $this->pay = $attributes[11];
        }
        // PDF cover page header image
        if(isset($attributes[12]) && strlen($attributes[12]) > 0){
            $this->pdf_cover_header = $attributes[12];
        }
        if($this->pdf_cover_header != null){
            return;
        }
    }
    
    /**
     * make login error parameter
     *
     * @return string NC2version flg:shibboleth flg
     */
    private function makeLoginInformation(){
        $version = 0;
        $container =& DIContainerFactory::getContainer();
        $configView =& $container->getComponent("configView");
        $config_version = $configView->getConfigByConfname(_SYS_CONF_MODID, "version");
        if(isset($config_version) && isset($config_version['conf_value'])) {
            $version = $config_version['conf_value'];
        } else {
            $version = _NC_VERSION;
        }
        if(str_replace(".", "", $version) < 2301){
          // under ver.2.3.0.1
          $version = "0";
        }else{
          // over ver.2.3.0.1
          $version = "1";
        }
        $shibboleth = SHIB_ENABLED;
        $shibboleth = intval($shibboleth);
        
        // return error message
        // NC2version flg:shibboleth flg
        return "$version:$shibboleth";
    }
    
    /***************** charge class **************************/
    
    /**
     * check can access Charge Server
     *
     * @param unknown_type $item_id
     * @param unknown_type $item_no
     * @param unknown_type $attribute_id
     * @param unknown_type $file_no
     * @param unknown_type $file_price
     * @return unknown
     */
    private function accessChargeServer($item_id, $item_no, $attribute_id, $file_no, $file_price)
    {
        // set user page url.
        $charge_pass = $this->getChargePass();
        $user_info_url = "https://".$charge_pass["user_fqdn"]."/user/menu/".
                         $charge_pass["sys_id"];//"/".$login_id;
        $this->Session->setParameter("user_info_url", $user_info_url);
        
        // create charge
        $trade_id = $this->createCharge($credit_url, $item_id, $item_no, $attribute_id, $file_no);
        if($trade_id == "unknown"){
            return 'unknown';
        }
        if($trade_id == "shared"){
            return 'shared';
        }
        if(strlen($trade_id) == 0 || $trade_id == "credit"){
            return 'creditError';
        }
        else if($trade_id == "false"){
            return "false";
        }else if($trade_id == "close"){
            return "close";
        }
        // Add GMO error 2009/06/19 A.Suzuki --start--
        else if($trade_id == "connection"){
            // go view action
            return "GMOError";
        } else if($trade_id == "free"){
            // paid
            return "free";
        } else if($trade_id == "already"){
            // already
            return "already";
        } else {
            return "$trade_id:$file_price";
        }
        // Add GMO error 2009/06/19 A.Suzuki --end--
    }
    
    /**
     * create charge action
     *
     */    
    function createCharge(&$credit_url, $item_id, $item_no, $attribute_id, $file_no){
        $result = $this->checkChargeRecord($item_id, $item_no, $credit_url);
        if($result != "true"){
            return $result;
        }
        // get title
        $return = $this->RepositoryAction->getItemTableData($item_id, $item_no, $item_data);
        if($return === false || count($item_data["item"])==0){
            return "unknown";
        }
        // search price
        $price = $this->getFilePriceTable($item_id, $item_no, $attribute_id, $file_no);
        if(count($price) == 0){
            // not price file
            return "";
        }
        $file_price = $this->getFilePrice($price[0]["price"]);
        if($file_price == "0"){
            // not charge
            return "free";
        }
        if($file_price == ""){
            return "false";
        }
        
        // Fix when this class user else action_common_download, not access idserver 2013/04/10 Y.Nakao --start--
        if($this->fromCommonDownload == false)
        {
            // ダウンロードではなく、ファイルの課金状態チェックなのでcreateChargeまで実施せずに戻る
            return "true";
        }
        // Fix when this class user else action_common_download, not access idserver 2013/04/10 Y.Nakao --end--
        
        // Modify add memo for charge record. 2012/02/28 Y.Nakao --start--
        $memo = $this->getChargeMemo($item_id, $item_no);
        // Modify add memo for charge record. 2012/02/28 Y.Nakao --end--
        
        ////////// create charge record //////////
        $block_info = $this->RepositoryAction->getBlockPageId();
        
        $repositoryDbAccess = new RepositoryDbAccess($this->Db);
        
        // Bug Fix TransStartDate is set in RepositoryAction instance T.Koyasu 2014/07/31 --start--
        $repositoryHandleManager = new RepositoryHandleManager($this->Session, $repositoryDbAccess, $this->RepositoryAction->TransStartDate);
        // Bug Fix TransStartDate is set in RepositoryAction instance T.Koyasu 2014/07/31 --end--
        
        $prefixID = $repositoryHandleManager->getPrefix(RepositoryHandleManager::ID_Y_HANDLE);
        $suffixID = $repositoryHandleManager->getSuffix($item_id, $item_no, RepositoryHandleManager::ID_Y_HANDLE);
        
        if (empty($prefixID) || empty($suffixID)) {
            return false;
        }
        
        // request uri is write ASCII
        // "/"->"%2F", ":"->"%3A", "&"->"%26", "."->"%2e"
        // change redirect url 2008/11/19 Y.Nakao --start--
        $url = str_replace("/", "%2F", BASE_URL);
        $url .= "%2F%3Faction=repository_uri".
                "%26item_id=".$item_id.
                "%26file_id=".$attribute_id;
        // change redirect url 2008/11/19 Y.Nakao --end--
        // create charge URL
        $charge_pass = $this->getChargePass();
        $send_param =   "https://".$charge_pass["charge_id"].":".$charge_pass["charge_pass"]."@".
                        $charge_pass["charge_fqdn"].
                        "/charge/create?".
                        "sys_id=".$charge_pass["sys_id"]. //sys_id :WEKOシステムを識別するID(現在 "weko01" のみ有効です)
                        // Fix change WEKO's user_id to WEKO'slogin_id 2008/10/30 Y.Nakao
                        "&user_id=".$this->Session->getParameter("_login_id").// user_id :利用者のWEKO_ID(LDAPと連携までは何でも通します)
                        "&content_id=".$prefixID."_".$suffixID.
                        "&price=".$file_price.
                        "&title=".urlencode($item_data["item"][0]["title"]).
                        "&uri=".$url.
                         // Modify add memo for charge record. 2012/02/28 Y.Nakao --start--
                         "&memo=".$memo;
                         // Modify add memo for charge record. 2012/02/28 Y.Nakao --end--
        // HTTP_Request init
        // send http request
        $option = array( 
            "timeout" => "10",
            "allowRedirects" => true, 
            "maxRedirects" => 3, 
        );
        // Modfy proxy 2011/12/06 Y.Nakao --start--
        $proxy = $this->RepositoryAction->getProxySetting();
        if($proxy['proxy_mode'] == 1)
        {
            $option = array( 
                    "timeout" => "10",
                    "allowRedirects" => true, 
                    "maxRedirects" => 3,
                    "proxy_host"=>$proxy['proxy_host'],
                    "proxy_port"=>$proxy['proxy_port'],
                    "proxy_user"=>$proxy['proxy_user'],
                    "proxy_pass"=>$proxy['proxy_pass']
                );
        }
        // Modfy proxy 2011/12/06 Y.Nakao --end--
        $http = new HTTP_Request($send_param, $option);
        
        // setting HTTP header
        $http->addHeader("User-Agent", $_SERVER['HTTP_USER_AGENT']); 
        $http->addHeader("Referer", $_SERVER['HTTP_REFERER']);
        
        // run HTTP request 
        $response = $http->sendRequest(); 
        if (!PEAR::isError($response)) { 
            $charge_code = $http->getResponseCode();// ResponseCode(200等)を取得 
            $charge_header = $http->getResponseHeader();// ResponseHeader(レスポンスヘッダ)を取得 
            $charge_body = $http->getResponseBody();// ResponseBody(レスポンステキスト)を取得 
            $charge_Cookies = $http->getResponseCookies();// クッキーを取得 
        }
        
        $result_js = $charge_body;
        
        $json = new Services_JSON();
        $decoded = $json->decode($result_js);
        
        // オーソリエラー(カード番号が未登録か無効)
        if($charge_header["weko_charge_status"] == -128){
            $credit_url = str_replace("\\", "", $decoded->location);
            return "credit";
        }
        
        // GMO通信エラー
        if($charge_header["weko_charge_status"] == -64){
            return "connection";
        }
        
        if($decoded->charge_status == "1"){
            // already download
            return "already";
        }
        
        return $decoded->trade_id;
    }
    
    // TODO スタブ解除
    /**
     * checkChargeRecord
     * IDServerと連携している場合、課金ログをチェックする
     *  
     * @param  $item_id
     *         $item_no
     *         &$credit_url 
     * @return "true"       IDServerと連携している、課金前
     *         "false"      IDServerと連携していない
     *         "unknown"    クレジットカード情報なし
     *       "shared"     クレジットカード登録不可(共有アカウント)
     *         "credit"     クレジットカード情報エラー
     *         "already"    課金済
     */
    function checkChargeRecord($item_id, $item_no, &$credit_url){
        // getPrefixID
        $prefixID_flg = false;

        $repositoryDbAccess = new RepositoryDbAccess($this->Db);
        // Bug Fix TransStartDate is set in RepositoryAction instance T.Koyasu 2014/07/31 --start--
        $repositoryHandleManager = new RepositoryHandleManager($this->Session, $repositoryDbAccess, $this->RepositoryAction->TransStartDate);
        // Bug Fix TransStartDate is set in RepositoryAction instance T.Koyasu 2014/07/31 --end--
        
        $prefixID = $repositoryHandleManager->getPrefix(RepositoryHandleManager::ID_Y_HANDLE);
        
        if (strlen($prefixID) != 0) {
            $prefixID_flg = true;
        }
        
        // get suffixID
        $suffixID_flg = false;
        
        $suffixID = $repositoryHandleManager->getSuffix($item_id, $item_no, RepositoryHandleManager::ID_Y_HANDLE);
        
        if (strlen($suffixID) != 0) {
            $suffixID_flg = true;
        }
        
        if($prefixID_flg && $suffixID_flg){
            // check charge record
            $result_js = $this->getChargeRecord($prefixID."_".$suffixID);
            $json = new Services_JSON();
            $decoded = $json->decode($result_js);
            if($decoded->message == "unknown_user_id"){
                // there is no credit card info
                return "unknown";
            } else if($decoded->message == "this_user_is_not_permit_to_use_credit_card" || $decoded->message == "this_user_does_not_have_permission_for_credit_card"){
                // Unable to register credit card info (Shared account user)
                return "shared";
            } else if($decoded->location != ""){
                // credit card info error
                $credit_url = str_replace("\\", "", $decoded->location);
                return "credit";
            } else if($decoded[0] != null || $decoded[0] != ""){
                // already charge
                return "already";
            } else {
                return "true";
            }
        } else {
            return "false";
        }
    }
    
    // TODO スタブ解除
    /**
     * stub for price test checkChargeRecord
     *
     * @param  $item_id
     *       $item_no
     *       &$credit_url 
     * @return "true"      IDServerと連携している、課金前
     *       "false"      IDServerと連携していない
     *       "unknown"  クレジットカード情報なし
     *       "shared"     クレジットカード登録不可(共有アカウント)
     *       "credit"    クレジットカード情報エラー
     *       "already"  課金済
     */
/*    function checkChargeRecord($item_id, $item_no, &$credit_url){
        $user = $this->Session->getParameter("_login_id");
        if($user == "userCmn" || $user == "userCmnGrp"){
            return "shared";
        }
        
        if($user == "userGuest_ER" || $user == "userAAA_ER"){
            return "credit";
        }
        
        if($user == "kuserGuest" 
        || $user == "kuserGuest_ER" 
        || $user == "kuserIns" 
        || $user == "kuserAAA" 
        || $user == "kuserAAA_ER" 
        || $user == "kadmin" ){
            return "already";
        }
        
//      return "credit";
//      return "connection";
//      return "unknown";
//      return "shared";
//      return "already";
        $testPay = $this->Session->getParameter("testPay");
        if($testPay == "true")
        {
            return "already";
        }
        if($this->pay === "true"){
            $this->Session->setParameter("testPay" ,"true");
        }
        
        if($this->fromCommonDownload)
        {
            $trade_id = 1;
            return $trade_id;
        }
        else
        {
            return "true";
        }
    }
    */
    // TODO スタブ解除
    
    /**
     * close charge action
     *
     */
    function closeCharge($trade_id){
        // close charge URL
        $charge_pass = $this->getChargePass();
        $send_param =   "https://".$charge_pass["charge_id"].":".$charge_pass["charge_pass"]."@".
                        $charge_pass["charge_fqdn"].
                        "/charge/close?".
                        "sys_id=".$charge_pass["sys_id"]. //sys_id :WEKOシステムを識別するID(現在 "weko01" のみ有効です)
                        // Fix change WEKO's user_id to WEKO'slogin_id 2008/10/30 Y.Nakao
                        "&user_id=".$this->Session->getParameter("_login_id").// user_id :利用者のWEKO_ID(LDAPと連携までは何でも通します)
                        "&trade_id=".$trade_id; // trade_id
        // HTTP_Request init
        // send http request
        $option = array( 
            "timeout" => "10",
            "allowRedirects" => true, 
            "maxRedirects" => 3, 
        );
        // Modfy proxy 2011/12/06 Y.Nakao --start--
        $proxy = $this->RepositoryAction->getProxySetting();
        if($proxy['proxy_mode'] == 1)
        {
            $option = array( 
                    "timeout" => "10",
                    "allowRedirects" => true, 
                    "maxRedirects" => 3,
                    "proxy_host"=>$proxy['proxy_host'],
                    "proxy_port"=>$proxy['proxy_port'],
                    "proxy_user"=>$proxy['proxy_user'],
                    "proxy_pass"=>$proxy['proxy_pass']
                );
        }
        // Modfy proxy 2011/12/06 Y.Nakao --end--
        $http = new HTTP_Request($send_param, $option);
        
        // setting HTTP header
        $http->addHeader("User-Agent", $_SERVER['HTTP_USER_AGENT']); 
        $http->addHeader("Referer", $_SERVER['HTTP_REFERER']);
        
        // run HTTP request 
        $response = $http->sendRequest(); 
        if (!PEAR::isError($response)) { 
            $charge_code = $http->getResponseCode();// ResponseCode(200等)を取得 
            $charge_header = $http->getResponseHeader();// ResponseHeader(レスポンスヘッダ)を取得 
            $charge_body = $http->getResponseBody();// ResponseBody(レスポンステキスト)を取得 
            $charge_Cookies = $http->getResponseCookies();// クッキーを取得 
        }
        $result_js = $charge_body;
        
        $json = new Services_JSON();
        $decoded = $json->decode($result_js);
        
        if($decoded->charge_status == "0"){
            return false;
        }
        
        return true;
    }
    // Add download action for repository_uri called 2009/10/02 A.Suzuki --end--
    
    /**
     * Enter description here...
     *
     * @return array()
     */
    function getChargePass(){
        $config = parse_ini_file(BASE_DIR.'/webapp/modules/repository/config/main.ini');
        $ret_info = array();
        // Fix there parameter get from config file 2008/10/30 Y.Nakao --start--
        $ret_info = array("charge_id" => $config["define:_REPOSITORY_CHARGE_ID"],
                          "charge_pass" => $config["define:_REPOSITORY_CHARGE_PASS"],
                          "charge_fqdn" => $config["define:_REPOSITORY_CHARGE_FQDN"],
                          "user_fqdn" => $config["define:_REPOSITORY_USER_FQDN"],
                          "sys_id" => $config["define:_REPOSITORY_CHARGE_SYSID"]
                    );
        // Fix there parameter get from config file 2008/10/30 Y.Nakao --end--
        return $ret_info;
    }
    
    /**
     * check price for access user, and highlight
     *
     * @param array $file_info
     * @param string $status <= "true", "already"
     * @return unknown
     */
    function checkPriceAccent($file_info, $status="true"){
        ///// get groupID and price /////
        $query = "SELECT price FROM ". DATABASE_PREFIX ."repository_file_price ".
                 "WHERE item_id = ? AND ".
                 "item_no = ? AND ".
                 "attribute_id = ? AND ".
                 "file_no = ? AND ".
                 "is_delete = 0; ";
        $params = array();
        $params[] = $file_info["item_id"];
        $params[] = $file_info["item_no"];
        $params[] = $file_info["attribute_id"];
        $params[] = $file_info["file_no"];
        $group_price = $this->Db->execute( $query, $params );
        if($group_price === false){
            return false;
        }
        $accent_array = array();
        $accent_room_id = array();
        if(!isset($group_price[0]["price"])){
            return $accent_array;
        }
        $room_price = explode("|", $group_price[0]["price"]);
        ///// ユーザが入っているグループIDを取得 /////
        $result = $this->RepositoryAction->getUsersGroupList($user_group,$error_msg);
        if($result===false){
            return false;
        }
        $file_price = "";
        for($price_Cnt=0;$price_Cnt<count($room_price);$price_Cnt++){
            $accent_flg = "false";
            $price = explode(",", $room_price[$price_Cnt]);
            // There is a pair of room_id and the price. 
            if($price!=null && count($price)==2 && $this->Session->getParameter("_user_id")!="0")
            {
                // It is judged whether it is user's belonging group.
                for($user_group_cnt=0;$user_group_cnt<count($user_group);$user_group_cnt++){
                    if($price[0] == $user_group[$user_group_cnt]["room_id"]){
                        // When the price is set to the belonging group
                        if($file_price==""){
                            // The price is maintained at the unsetting.
                            $file_price = $price[1];
                            $accent_flg = "true";
                        } else if(intval($file_price) > intval($price[1])){
                            // It downloads it by the lowest price. 
                            $file_price = $price[1];
                            $accent_flg = "true";
                        } else if(intval($file_price) == intval($price[1])){
                            // same the lowest price. 
                            $accent_flg = "same";
                        }
                    }
                }
            }
            
            // アクセントフラグチェック
            if($accent_flg == "true"){
                // 最安値更新
                for($ii=0;$ii<count($accent_array);$ii++){
                    $accent_array[$ii] = "false";
                }
                array_push($accent_array, $status);
                array_push($accent_room_id, $price[0]);
            } else if($accent_flg == "same"){
                // 同価格
                for($ii=0;$ii<count($accent_array);$ii++){
                    if($accent_array[$ii] == "true"){
                        // 非会員と価格が同じ場合、非会員はハイライトをつけない
                        if($accent_room_id[$ii] == 0){
                            $accent_array[$ii] = "false";
                        }
                    }
                }
                array_push($accent_array, $status);
                array_push($accent_room_id, $price[0]);
            } else {
                // 該当せず
                array_push($accent_array, "false");
                array_push($accent_room_id, $price[0]);
            }
        }
        return $accent_array;
    }
    // Add put the accent on user's price 2009/05/28 A.Suzuki --end--
    
    // Modify add memo for charge record. 2012/02/28 Y.Nakao --start--
    /**
     * get charge memo
     *
     * @param int $item_id
     * @param int $item_no
     * @return string charge memo
     */
    private function getChargeMemo($item_id, $item_no)
    {
        // memo for index tree list
        $memo = '';
        
        // get position index_id
        $query = "SELECT index_id ".
                " FROM ".DATABASE_PREFIX."repository_position_index ".
                " WHERE item_id = ? ".
                " AND   item_no = ? ".
                " AND   is_delete = ? ";
        $params = array();
        $params[] = $item_id;
        $params[] = $item_no;
        $params[] = 0;
        $posIdxList = $this->Db->execute($query, $params);
        if($posIdxList === false || count($posIdxList) == 0)
        {
            return "";
        }
        for($ii=0; $ii<count($posIdxList); $ii++)
        {
            $index = '';
            $idxList = array();
            $this->RepositoryAction->getParentIndex($posIdxList[$ii]['index_id'], $idxList);
            for($jj=0; $jj<count($idxList); $jj++)
            {
                if(strlen($index) > 0)
                {
                    $index .= ',';
                }
                if(strlen($idxList[$jj]['index_name']) > 0)
                {
                    $index .= $idxList[$jj]['index_name'];
                }
                else
                {
                    $index .= $idxList[$jj]['index_name_english'];
                }
            }
            if(strlen($memo) > 0)
            {
                $memo .= '|';
            }
            $memo .= $index;
        }
        return urlencode($memo);
    }
    // Modify add memo for charge record. 2012/02/28 Y.Nakao --end--
    
        // Add check charge record from log table 2008/10/16 Y.Nakao --start--
    function getChargeRecord($content_id){
        ////////// get charge record //////////
        // request uri is write ASCII
        // create charge URL
        $charge_pass = $this->getChargePass();
        $send_param =   "https://".$charge_pass["charge_id"].":".$charge_pass["charge_pass"]."@".
                        $charge_pass["charge_fqdn"].
                        "/charge/show?".
                        "sys_id=".$charge_pass["sys_id"]. //sys_id :WEKOシステムを識別するID(現在 "weko01" のみ有効です)
                        // Fix change WEKO's user_id to WEKO'slogin_id 2008/10/30 Y.Nakao
                        "&user_id=".$this->Session->getParameter("_login_id");// user_id :利用者のWEKO_ID(LDAPと連携までは何でも通します)
        if($content_id != ""){
            $send_param .= "&content_id=".$content_id;
        }
        // HTTP_Request init
        // send http request
        $option = array( 
            "timeout" => "10",
            "allowRedirects" => true, 
            "maxRedirects" => 3, 
        );
        // Modfy proxy 2011/12/06 Y.Nakao --start--
        $proxy = $this->RepositoryAction->getProxySetting();
        if($proxy['proxy_mode'] == 1)
        {
            $option = array( 
                    "timeout" => "10",
                    "allowRedirects" => true, 
                    "maxRedirects" => 3,
                    "proxy_host"=>$proxy['proxy_host'],
                    "proxy_port"=>$proxy['proxy_port'],
                    "proxy_user"=>$proxy['proxy_user'],
                    "proxy_pass"=>$proxy['proxy_pass']
                );
        }
        // Modfy proxy 2011/12/06 Y.Nakao --end--
        $http = new HTTP_Request($send_param, $option);
        
        // setting HTTP header
        $http->addHeader("User-Agent", $_SERVER['HTTP_USER_AGENT']); 
        $http->addHeader("Referer", $_SERVER['HTTP_REFERER']);
        
        // run HTTP request 
        $response = $http->sendRequest(); 
        if (!PEAR::isError($response)) { 
            $charge_code = $http->getResponseCode();// ResponseCode(200等)を取得 
            $charge_header = $http->getResponseHeader();// ResponseHeader(レスポンスヘッダ)を取得 
            $charge_body = $http->getResponseBody();// ResponseBody(レスポンステキスト)を取得 
            $charge_Cookies = $http->getResponseCookies();// クッキーを取得 
        }
        $result_js = $charge_body;

        if($charge_code == "200"){
            return $result_js;
        } else {
            return "false";
        }
        
    }
    // Add check charge record from log table 2008/10/16 Y.Nakao --end--
    
}
?>