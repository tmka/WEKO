<?php
// --------------------------------------------------------------------
//
// $Id: SwordUpdate.class.php 58676 2015-10-10 12:33:17Z tatsuya_koyasu $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics,
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

include_once MAPLE_DIR.'/includes/pear/File/Archive.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/action/edit/import/ImportCommon.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/ItemRegister.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/NameAuthority.class.php';
require_once WEBAPP_DIR. '/components/mail/Main.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryOutputFilter.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositorySearchTableProcessing.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHandleManager.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/util/ZipUtility.class.php';

/**
 * Sword update class
 *
 */
class SwordUpdate extends RepositoryAction
{
    // member
    /**
     * @var int
     */
    private $itemId = null;

    /**
     * @var int
     */
    private $itemNo = null;

    /**
     * @var string
     */
    private $insertUser = null;

    /**
     * @var string
     */
    private $loginId = null;

    /**
     * @var string
     */
    private $password = null;

    /**
     * Index id string separated by "|"
     *  delimiter is "|"
     *  index_id|index_id|index_id|...
     *
     * @var string
     */
    private $checkedIds = null;

    /**
     * New index data
     *  delimiter is "," and "|"
     *  pid,name,pubdate|pid,name,pubdate|...
     *
     * @var string
     */
    private $newIndex = null;

    /**
     * @var ImportCommon
     */
    private $importCommon_ = null;

    /**
     * @var ItemRegister
     */
    private $itemRegister_ = null;

    /**
     * @var string
     */
    private $encode_ = _CHARSET;

    /**
     * Log file name
     *
     * @var string
     */
    private $logFile = "";

    /**
     * Whether to create a log
     *  default: true
     * @var bool
     */
    private $isCreateLog = true;

    /**
     * Whether put a date in log file name
     *  default: false
     * @var bool
     */
    private $isAddDateToLogName = false;

    /**
     * Whether to delete uploaded file
     *  default: true
     * @var bool
     */
    private $deleteUploadFile = true;

    /**
     * RepositoryHandleManager instance
     *
     * @var RepositoryHandleManager
     */
    private $repositoryHandleManager = null;

    // Metadata array for ItemRegister
    const KEY_ITEM_ID = "item_id";
    const KEY_ITEM_NO = "item_no";
    // Add add fix number T.Koyasu 2014/09/12 --start--
    const KEY_REVISION_NO = "revision_no";
    // Add add fix number T.Koyasu 2014/09/12 --end--
    const KEY_ITEM_TYPE_ID = "item_type_id";
    // Add add fix number T.Koyasu 2014/09/12 --start--
    const KEY_PREV_REVISION_NO = "prev_revision_no";
    // Add add fix number T.Koyasu 2014/09/12 --end--
    const KEY_TITLE = "title";
    const KEY_TITLE_EN = "title_english";
    const KEY_LANGUAGE = "language";
    const KEY_PUB_YEAR = "pub_year";
    const KEY_PUB_MONTH = "pub_month";
    const KEY_PUB_DAY = "pub_day";
    const KEY_SEARCH_KEY = "serch_key";
    const KEY_SEARCH_KEY_EN = "serch_key_english";
    const KEY_SHOWN_STATUS = "shown_status";
    const KEY_ATTR_ID = "attribute_id";
    const KEY_ATTR_NO = "attribute_no";
    const KEY_INPUT_TYPE = "input_type";
    const KEY_ATTR_VALUE = "attribute_value";
    const KEY_FAMILY = "family";
    const KEY_NAME = "name";
    const KEY_FAMILY_RUBY = "family_ruby";
    const KEY_NAME_RUBY = "name_ruby";
    const KEY_EMAIL = "e_mail_address";
    const KEY_AUTHOR_ID = "author_id";
    const KEY_NAME_NO = "personal_name_no";
    const KEY_PREFIX_ID = "prefix_id";
    const KEY_SUFFIX = "suffix";
    const KEY_EX_AUTHOR_ID = "external_author_id";
    const KEY_BIBLIO_NAME = "biblio_name";
    const KEY_BIBLIO_NAME_EN = "biblio_name_english";
    const KEY_VOLUME = "volume";
    const KEY_ISSUE = "issue";
    const KEY_SPAGE = "start_page";
    const KEY_EPAGE = "end_page";
    const KEY_DATE_OF_ISSUED = "date_of_issued";
    const KEY_BIBLIO_NO = "biblio_no";
    const KEY_FILE_NAME = "file_name";
    const KEY_SHOW_ORDER = "show_order";
    const KEY_MIMETYPE = "mimetype";
    const KEY_EXTENSION = "extension";
    const KEY_PHYSICAL_FILE_NAME = "physical_file_name";
    const KEY_FILE_NO = "file_no";
    const KEY_UPLOAD = "upload";
    const KEY_WIDTH = "width";
    const KEY_HEIGHT = "height";
    const KEY_FILE_DIR = "file_dir";
    const KEY_DISPLAY_NAME = "display_name";
    const KEY_DISPLAY_TYPE = "display_type";
    const KEY_LICENSE_ID = "license_id";
    const KEY_LICENSE_NOTATION = "license_notation";
    const KEY_EMBARGO_FLAG = "embargo_flag";
    const KEY_EMBARGO_YEAR = "embargo_year";
    const KEY_EMBARGO_MONTH = "embargo_month";
    const KEY_EMBARGO_DAY = "embargo_day";
    const KEY_FLASH_EMBARGO_FLAG = "flash_embargo_flag";
    const KEY_FLASH_EMBARGO_YEAR = "flash_embargo_year";
    const KEY_FLASH_EMBARGO_MONTH = "flash_embargo_month";
    const KEY_FLASH_EMBARGO_DAY = "flash_embargo_day";
    const KEY_PRICE_NUM = "price_num";
    const KEY_ROOM_ID = "room_id";
    const KEY_PRICE_VALUE = "price_value";
    const KEY_INDEX_ID = "index_id";
    // add e-person 2013/10/23 R.Matsuura
    const KEY_FEEDBACK_MAILADDRESS = "feedback_mailaddress";
    // add cnri handle 2014/09/22 T.Ichikawa
    const KEY_CNRI_SUFFIX = "cnri_suffix";
    
    // add revision no 2014/09/11 T.Ichikawa
    const KEY_SELF_DOI = "selfdoi";
    const REVISION_NO = 1;
    const PREV_REVISION_NO = 0;
    
    //-----------------------------------------------
    // Public method
    //-----------------------------------------------
    /**
     * Constructor
     *
     * @param Session $session
     * @param Db $db
     * @param string $transStartDate
     * @param bool $isCreateLog
     * @access public
     */
    public function SwordUpdate($session, $db, $transStartDate, $isCreateSwordLog=false)
    {
        if(!isset($session) || !isset($db) || !isset($transStartDate))
        {
            return null;
        }
        $this->Session = $session;
        $this->Db = $db;
        $this->dbAccess = new RepositoryDbAccess($this->Db);
        $this->TransStartDate = $transStartDate;

        // Set ImportCommon class
        $this->importCommon_ = new ImportCommon($this->Session, $this->Db, $this->TransStartDate);

        // Set ItemRegister class
        $this->itemRegister_ = new ItemRegister($this->Session, $this->Db);
        $this->itemRegister_->setEditStartDate($this->TransStartDate);

        // Set encode
        $this->setEncode();

        $this->setConfigAuthority();

        // Create log file
        if($this->isCreateLog && $isCreateSwordLog)
        {
            $this->logFile = WEBAPP_DIR."/logs/weko/sword/sword_update_log.txt";
            if($this->isAddDateToLogName)
            {
                // Add date to logName
                $this->logFile = WEBAPP_DIR."/logs/weko/sword/sword_update_log_".date("YmdHis").".txt";
            }
            if(file_exists($this->logFile))
            {
                chmod($this->logFile, 0600);
                unlink($this->logFile);
            }
            $logFh = fopen($this->logFile, "w");
            chmod($this->logFile, 0600);
            fwrite($logFh, "Start SWORD Update. (".date("Y/m/d H:i:s").")\n");
            fwrite($logFh, "\n");
            fclose($logFh);
        }
    }

    /**
     * Init
     *
     * @param int $itemId
     * @param int $itemNo
     * @param string $loginId
     * @param string $password
     * @param string $checkedIds
     * @param string $newIndex
     * @param string $insertUser
     */
    public function init($itemId, $itemNo, $loginId, $password, $checkedIds, $newIndex, $insertUser="")
    {
        $this->itemId = $itemId;
        $this->itemNo = $itemNo;
        $this->loginId = $loginId;
        $this->password = $password;
        $this->checkedIds = $checkedIds;
        $this->newIndex = $newIndex;

        if(strlen($insertUser)>0)
        {
            $this->insertUser = $insertUser;
        }
        else
        {
            $this->insertUser = $loginId;
        }

        $this->writeLog("Init params.\n");
        $this->writeLog("  itemId: ".$this->itemId."\n");
        $this->writeLog("  itemNo: ".$this->itemNo."\n");
        $this->writeLog("  loginId: ".$this->loginId."\n");
        $this->writeLog("  checkedIds: ".$this->checkedIds."\n");
        $this->writeLog("  newIndex: ".$this->newIndex."\n");
        $this->writeLog("  insertUser: ".$this->insertUser."\n\n");
    }

    /**
     * Execute sword update
     *
     * @param int $statusCode
     * @return bool
     */
    public function executeSwordUpdate(&$statusCode, &$error_list)
    {
        $this->writeLog("-- Start executeSwordUpdate (".date("Y/m/d H:i:s").") --\n");

        // Init status code
        $statusCode = 200;

        $tmpDir = "";
        try
        {
            $logFile = WEBAPP_DIR."/logs/weko/sword/sword_import_common_log.txt";
            $logFh = fopen($logFile, "w");
            $this->importCommon_->setLogFileHandle($logFh);
            // 1. Check user authority
            if(!$this->checkSwordLogin($statusCode, $userId))
            {
                // Error
                $this->writeLog("  Failed: checkSwordLogin.\n");
                $this->writeLog("-- End executeSwordUpdate (".date("Y/m/d H:i:s").") --\n\n");
                fclose($logFh);
                return false;
            }

            // 2. Get file data
            $fileData = $this->Session->getParameter("swordFileData");
            $this->Session->removeParameter("swordFileData");
            if(!isset($fileData))
            {
                // Error
                $statusCode = 400;
                $this->writeLog("  Failed: Get file data from session.\n");
                $this->writeLog("-- End executeSwordUpdate (".date("Y/m/d H:i:s").") --\n\n");
                fclose($logFh);
                return false;
            }

            // 3. Get update XML data
            if(!$this->getUpdateXmlData($fileData, $xmlData, $tmpDir, $error_list))
            {
                // Error
                $statusCode = 500;
                if(strlen($tmpDir)>0)
                {
                    $this->removeDirectory($tmpDir);
                }
                $this->writeLog("  Failed: getUpdateXmlData.\n");
                $this->writeLog("-- End executeSwordUpdate (".date("Y/m/d H:i:s").") --\n\n");
                fclose($logFh);
                return false;
            }

            // 4. Insert new index
            $insIndexArray = array();
            if(!$this->setInsertIndexIdArray($this->newIndex, $this->checkedIds, $userId, $insIndexArray))
            {
                // Error
                $statusCode = 500;
                if(strlen($tmpDir)>0)
                {
                    $this->removeDirectory($tmpDir);
                }
                $this->writeLog("  Failed: insertNewIndex.\n");
                $this->writeLog("-- End executeSwordUpdate (".date("Y/m/d H:i:s").") --\n\n");
                fclose($logFh);
                return false;
            }

            // 5. Update item
            if(!$this->executeUpdateFromXmlData($xmlData, $tmpDir, $userId, $this->itemId, $this->itemNo, $insIndexArray,$error_list))
            {
                // Error
                $statusCode = 500;
                if(strlen($tmpDir)>0)
                {
                    $this->removeDirectory($tmpDir);
                }
                $this->writeLog("  Failed: executeUpdateFromXmlData.\n");
                $this->writeLog("-- End executeSwordUpdate (".date("Y/m/d H:i:s").") --\n\n");
                fclose($logFh);
                return false;
            }

            // 6. return status
            $statusCode = 200;
            if(strlen($tmpDir)>0)
            {
                $this->removeDirectory($tmpDir);
            }
            $this->writeLog("  Success update item_id=".$this->itemId."\n");
            $this->writeLog("-- End executeSwordUpdate (".date("Y/m/d H:i:s").") --\n\n");
            fclose($logFh);
            return true;
        }
        catch(Exception $ex)
        {
            // Error
            $statusCode = 500;
            if(strlen($tmpDir)>0)
            {
                $this->removeDirectory($tmpDir);
            }
            $this->writeLog("  Failed: Exception occurred.\n");
            $this->writeLog("-- End executeSwordUpdate (".date("Y/m/d H:i:s").") --\n\n");
            fclose($logFh);
            return false;
        }
    }

    /**
     * Execute update item
     *
     * 戻り値の仕様
     *   正常完了の場合： $errorMsg を空にして、true を返す
     *   更新はしないが、処理続行の場合： $errorMsg を詰めて、true を返す
     *   エラーにより処理中断の場合： $errorMsg を詰めて、false を返す
     *
     * @param array $xmlItemData
     * @param array $xmlItemTypeData
     * @param string $tmpDir
     * @param string $userId
     * @param int $itemId
     * @param int $itemNo
     * @param array $itemInfo
     * @param array $insIndexArray
     * // Add param item_type_info 2013/09/09 R.Matsuura
     * @param array $item_type_info
     * @param string $detailUrl
     * @param string $errorMsg
     * @param string $waringMsg
     * @return bool
     */
    public function executeUpdate(
                $xmlItemData, $xmlItemTypeData, $tmpDir, $userId,
                $itemId, $itemNo, &$itemInfo, $insIndexArray, $item_type_info, &$detailUrl, &$errorMsg, &$warningMsg)
    {
        $this->writeLog("-- Start executeUpdate (".date("Y/m/d H:i:s").") --\n");

        // Init error_msg
        $errorMsg = "";

        // Set item info
        $reviewStatus = -1;
        $itemTypeName = $xmlItemTypeData['item_type_array'][0]['ITEM_TYPE_NAME'];
        // Add 2013/09/09 R.Matsuura --start--
        $itemTypeId = $item_type_info['item_type_id'];
        // Add 2013/09/09 R.Matsuura --end--
        array_push($itemInfo,
                 array(
                    "title" => $xmlItemData['item_array'][0]['TITLE'],
                    "title_english" => $xmlItemData['item_array'][0]['TITLE_ENGLISH'],
                    "item_type" => $itemTypeName,
                    "review_status" => $reviewStatus,
                    "mode" => "update",
                    "status" => "failed"
                 )
            );

        // アップデートデータチェック
        $this->writeLog("  Call checkUpdate.\n");
        $result = $this->checkUpdate($xmlItemData, $xmlItemTypeData, $itemTypeName, $itemId, $itemNo, $userId, $errorMsg);
        $itemInfo[count($itemInfo)-1]["item_type"] = $itemTypeName;
        if(strlen($errorMsg) > 0)
        {
            $this->writeLog("\n  ".$errorMsg."\n");
            $this->writeLog("-- End executeUpdate (".date("Y/m/d H:i:s").") --\n\n");
            return $result;
        }
        $this->writeLog("  checkUpdate complete.\n");

        // ItemRegiser用配列作成
        $this->writeLog("  Call setItemDataForItemRegister.\n");
        // Update 2013/09/09 R.Matsuura (Add new argument "$itemTypeId")
        $result = $this->setItemDataForItemRegister(
                        $xmlItemData, $xmlItemTypeData, $itemId, $itemNo, $itemTypeId, $insIndexArray,
                        $tmpDir, $irBasic, $irMetadataArray, $indexInfo, $errorMsg);
        if(strlen($errorMsg) > 0)
        {
            $this->writeLog("\n  ".$errorMsg."\n");
            $this->writeLog("-- End executeUpdate (".date("Y/m/d H:i:s").") --\n\n");
            return $result;
        }
        $this->writeLog("  setItemDataForItemRegister complete.\n");

        // ItemRegisterの更新処理実行
        $this->writeLog("  Call executeUpdateByItemRegister.\n");
        $result = $this->executeUpdateByItemRegister($irBasic, $irMetadataArray, $indexInfo, $userId, $detailUrl, $errorMsg, $reviewStatus, $warningMsg);
        if(strlen($errorMsg) > 0)
        {
            $this->writeLog("\n  ".$errorMsg."\n");
            $this->writeLog("-- End executeUpdate (".date("Y/m/d H:i:s").") --\n\n");
            return $result;
        }
        $this->writeLog("  executeUpdateByItemRegister complete.\n");

        // Add suppleContentsEntry Y.Yamazawa --start-- 2015/03/24 --start--
        $this->entrySupple($itemId,$itemNo,$xmlItemData['supple_info_array'], $warningMsg);
        // Add suppleContentsEntry Y.Yamazawa --end-- 2015/03/24 --end--

        // Update review status
        $itemInfo[count($itemInfo)-1]["review_status"] = $reviewStatus;
        $itemInfo[count($itemInfo)-1]["status"] = "success";

        $this->writeLog("  executeUpdate completed.\n");
        $this->writeLog("-- End executeUpdate (".date("Y/m/d H:i:s").") --\n\n");

        return true;
    }

    /**
     * Check sword login
     *
     * @param int $statusCode
     * @param string $userId
     * @return bool
     */
    public function checkSwordLogin(&$statusCode, &$userId)
    {
        $this->writeLog("-- Start checkSwordLogin (".date("Y/m/d H:i:s").") --\n\n");

        $userId = "";
        if(strlen($this->loginId)==0 || strlen($this->password)==0)
        {
            $statusCode = 401;
            $this->writeLog("  Failed: login_id or password is null.\n");
            $this->writeLog("-- End checkSwordLogin (".date("Y/m/d H:i:s").") --\n\n");
            return false;
        }

        // send http request
        $option = array(
            "timeout" => "10",
            "allowRedirects" => true,
            "maxRedirects" => 3,
        );
        $proxy = $this->getProxySetting();
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

        $url = BASE_URL.'/?action=repository_action_main_sword_login'.
                '&login_id='.$this->loginId.'&password='.$this->password;
        $http = new HTTP_Request($url, $option);
        $response = $http->sendRequest();
        if (!PEAR::isError($response))
        {
            $body = $http->getResponseBody();
            if(strpos($body, 'success') === false)
            {
                $statusCode = 401;
                $this->writeLog("  Failed: login action.\n");
                $this->writeLog("-- End checkSwordLogin (".date("Y/m/d H:i:s").") --\n\n");
                return false;
            }

            $query = "SELECT user_id FROM ".DATABASE_PREFIX."users ".
                     "WHERE login_id = ?; ";
            $params = array();
            $params[] = $this->insertUser;
            $result = $this->Db->execute($query, $params);
            if($result === false || count($result)!=1){
                // is not user
                $statusCode = 401;
                $this->writeLog("  Failed: owner(".$this->insertUser.") is not found.\n");
                $this->writeLog("-- End checkSwordLogin (".date("Y/m/d H:i:s").") --\n\n");
                return false;
            }
            $userId = $result[0]["user_id"];
        }
        else
        {
            $statusCode = 401;
            $this->writeLog("  Failed: Error occurred in login action.\n");
            $this->writeLog("-- End checkSwordLogin (".date("Y/m/d H:i:s").") --\n\n");
            return false;
        }

        $this->writeLog("  checkSwordLogin completed.\n");
        $this->writeLog("-- End checkSwordLogin (".date("Y/m/d H:i:s").") --\n\n");
        return true;
    }

    /**
     * Set http header
     *
     * @param int $code
     */
    public function setHeader($code, $error_list=array())
    {
        // protocol
        if(isset($_SERVER['SERVER_PROTOCOL']))
        {
            $protocol = $_SERVER['SERVER_PROTOCOL'];
        }
        else
        {
            $protocol = 'HTTP/1.0';
        }

        $text = "";
        switch ($code)
        {
            case 200:
                $text = 'OK';
                break;
            case 204:
                $text = 'No Content';
                break;
            case 400:
                $text = 'Bad Request';
                break;
            case 401:
                $text = 'Unauthorized';
                break;
            case 403:
                $text = 'Forbidden';
                break;
            case 500:
                $text = 'Internal Server Error';
                break;
            default:
                $code = 500;
                $text = 'Internal Server Error';
                break;
        }

        // header
        header($protocol . ' ' . $code . ' ' . $text);
        if($code == 401)
        {
            header("WWW-Authenticate: Basic realm=\"SWORD\"");
        }
        if($code != 200 && $code != 204)
        {
            // Update suppleContentsEntry Y.Yamazawa --start-- 2015/03/24 --start--
            $this->outputErrorXML($error_list,$text);
        }

        if($code == 200){
            $this->outputSuccessXML($this->itemId,$error_list);
        }
        return;
    }

    /**
     * Get item_id by URL
     *
     * @param string $url
     * @return int
     */
    public function getUrlItemId($url)
    {
        // Init
        $itemId = 0;

        // URL内のitem_idを取得
        preg_match("/^.*\?action=repository_uri&item_id=([0-9]+).*$/", $url, $matches);
        if(isset($matches[1]))
        {
            $itemId = intval($matches[1]);
        }
        return $itemId;
    }

    //-----------------------------------------------
    // Private method
    //-----------------------------------------------
    /**
     * Get update XML data
     *
     * @return bool
     */
    private function getUpdateXmlData($filedata, &$xmlData, &$tmpDir, &$error_list)
    {
        // Extract zip file
        $xmlData = array();
        
        $this->infoLog("businessWorkdirectory", __FILE__, __CLASS__, __LINE__);
        $businessWorkdirectory = BusinessFactory::getFactory()->getBusiness('businessWorkdirectory');
        $tmpDir = $businessWorkdirectory->create();
        $tmpDir = substr($tmpDir, 0, -1);
        
        $filePath = $filedata["upload_dir"].DIRECTORY_SEPARATOR.$filedata["physical_file_name"];
        if(!$this->extraction($filePath, $tmpDir))
        {
            $this->errorLog("Failed extraction", __FILE__, __CLASS__, __LINE__);
            return false;
        }

        // Get update data by xml
        if(!$this->importCommon_->XMLAnalysis($tmpDir, $xmlData, $error_list))
        {
            $this->errorLog("Failed XMLAnalysis", __FILE__, __CLASS__, __LINE__);
            return false;
        }

        return true;
    }

    /**
     * Execute update from XML data
     *
     * @param array $xmlData
     * @param string $tmpDir
     * @param string $userId
     * @param int $itemId
     * @param array $insIndexArray
     * @return bool
     */
    private function executeUpdateFromXmlData($xmlData, $tmpDir, $userId, $itemId, $itemNo, $insIndexArray,&$error_list)
    {
        $this->writeLog("-- Start executeUpdateFromXmlData (".date("Y/m/d H:i:s").") --\n");

        $updateFlag = false;
        $this->importCommon_->itemtypeEntry($xmlData['item_type'], $tmpDir, $item_type_info, $error_msg);

        // 1アイテムずつループ
        for($nCnt=0; $nCnt<count($xmlData['item']); $nCnt++)
        {
            // 1アイテム分のアップデート処理呼出
            // アップデートに成功したらループを抜ける
            $detailUrl = "";
            $errorMsg = "";
            $warningMsg = "";
            $itemInfo = array();

            // Update SuppleContentsEntry Y.Yamazawa 2015/04/06 --start--
            $result = $this->executeUpdate(
                                $xmlData['item'][$nCnt], $xmlData['item_type'][$nCnt], $tmpDir,
                                $userId, $itemId, $itemNo, $itemInfo, $insIndexArray, $item_type_info[$nCnt], $detailUrl, $errorMsg, $warningMsg);
            if($result && strlen($errorMsg)==0 && strlen($warningMsg)==0)
            {
                $updateFlag = true;
                $this->writeLog("  Success update and no error.\n");
                break;
            }
            else if($result && strlen($warningMsg) > 0){
                $updateFlag = true;
                array_push($error_list, $warningMsg);
                $this->writeLog("  Success update and error.\n");
                break;
            }
            else if(!$result)
            {
                $this->writeLog("  Update error:\n");
                $this->writeLog("    XML_item_id:".$xmlData['item'][$nCnt]["item_array"][0]["ITEM_ID"]."\n");
                $this->writeLog("    ".$errorMsg."\n");
            }
            // Update SuppleContentsEntry Y.Yamazawa 2015/04/06 --end--
        }

        if($updateFlag)
        {
            $this->writeLog("  Update OK.");
            if(isset($itemInfo[0]["review_status"]) && $itemInfo[0]["review_status"]==0)
            {
                // 査読通知メール送付処理呼出
                $this->writeLog(" It is a peer-reviewed target.\n");
                $this->sendReviewMail($itemId, $itemNo);
            }
            else
            {
                $this->writeLog(" Not peer-reviewed target.\n");
            }
        }
        else
        {
            $this->writeLog("  Failed update.\n");
        }

        $this->writeLog("  executeUpdateFromXmlData completed.\n");
        $this->writeLog("-- End executeUpdateFromXmlData (".date("Y/m/d H:i:s").") --\n\n");

        return $updateFlag;
    }

    /**
     * Check update
     *
     * 戻り値の仕様
     *   正常完了の場合： $errorMsg を空にして、true を返す
     *   更新はしないが、処理続行の場合： $errorMsg を詰めて、true を返す
     *   エラーにより処理中断の場合： $errorMsg を詰めて、false を返す
     *
     * @param array $xmlItemData
     * @param array $xmlItemTypeData
     * @param string $itemTypeName
     * @param int $itemId
     * @param int $itemNo
     * @param string $userId
     * @param string $errorMsg
     * @return bool
     */
    private function checkUpdate(&$xmlItemData, &$xmlItemTypeData, &$itemTypeName, $itemId, $itemNo, $userId, &$errorMsg)
    {
        $this->writeLog("-- Start checkUpdate (".date("Y/m/d H:i:s").") --\n");

        // XMLの配列から詳細画面URLを取得
        $uri = $xmlItemData["edit_array"][0]["URL"];

        // -----------------------
        // ドメイン一致チェック
        // -----------------------
        $this->writeLog("  Domain check: ");
        $matches = array();
        preg_match("/^https?:\/\/(([^\/]+)).*$/", $uri, $matches);
        $xmlDomain = $matches[1];

        $matches = array();
        preg_match("/^https?:\/\/(([^\/]+)).*$/", BASE_URL, $matches);
        $baseDomain = $matches[1];

        if($xmlDomain != $baseDomain)
        {
            // [Warning]ドメインが異なるためアップデート不可
            $errorMsg = "UPDATE ERROR: Domain is different.";
            $this->writeLog("failed\n");
            $this->writeLog("-- End checkUpdate (".date("Y/m/d H:i:s").") --\n\n");
            return true;
        }
        $this->writeLog("success\n");

        // -----------------------
        // itemIdチェック
        // -----------------------
        $this->writeLog("  item_id check: ");
        $itemId = intval($itemId);
        $urlItemId = $this->getUrlItemId($uri);
        if($itemId < 1 || $urlItemId < 1)
        {
            // [Warning]更新対象外
            $errorMsg = "UPDATE ERROR: Not found target item.";
            $this->writeLog("failed\n");
            $this->writeLog("-- End checkUpdate (".date("Y/m/d H:i:s").") --\n\n");
            return true;
        }
        else if($itemId != $urlItemId)
        {
            // [Warning]引数のitemIdとURLのitemIdが一致しない
            $errorMsg = "UPDATE ERROR: Not found target item.";
            $this->writeLog("failed\n");
            $this->writeLog("-- End checkUpdate (".date("Y/m/d H:i:s").") --\n\n");
            return true;
        }
        $this->writeLog("success\n");

        // -----------------------
        // DB内のアイテムデータ取得
        // -----------------------
        // Get item data from DB
        $this->writeLog("  Get item data: ");
        $dbItemData = array();
        if(!$this->getItemData($itemId, $itemNo, $dbItemData, $tmpErrorMsg))
        {
            // [Error]DBエラー
            $errorMsg = $tmpErrorMsg;
            $this->writeLog("failed\n");
            $this->writeLog("-- End checkUpdate (".date("Y/m/d H:i:s").") --\n\n");
            return false;
        }
        $this->writeLog("success\n");

        // -----------------------
        // アイテム存在チェック
        // -----------------------
        // Check item exists
        $this->writeLog("  item exists check: ");
        if(!$this->checkItemExists($dbItemData))
        {
            // [Warning]アイテム削除済みのため更新しない
            $errorMsg = "UPDATE ERROR: This item has already been deleted.";
            $this->writeLog("failed\n");
            $this->writeLog("-- End checkUpdate (".date("Y/m/d H:i:s").") --\n\n");
            return true;
        }
        $this->writeLog("success\n");

        // -----------------------
        // アイテムタイプ一致チェック
        // -----------------------
        // Check item type
        $this->writeLog("  item type check: ");
        if(!$this->checkItemTypeMatch($dbItemData, $xmlItemData, $xmlItemTypeData, $itemTypeName))
        {
            // [Warning]アイテムタイプが一致しないため更新不可
            $errorMsg = "UPDATE ERROR: Itemtype does not match.";
            $this->writeLog("failed\n");
            $this->writeLog("-- End checkUpdate (".date("Y/m/d H:i:s").") --\n\n");
            return true;
        }
        $this->writeLog("success\n");

        // -----------------------
        // アイテム更新権限チェック
        // -----------------------
        // Check update authority
        $this->writeLog("  update authority check: ");
        if(!$this->checkUpdateAuthority($itemId, $itemNo, $userId))
        {
            // [Warning]更新権限なし
            $errorMsg = "UPDATE ERROR: You do not have permission to update this item.";
            $this->writeLog("failed\n");
            $this->writeLog("-- End checkUpdate (".date("Y/m/d H:i:s").") --\n\n");
            return true;
        }

        $errorMsg = "";

        $this->writeLog("  checkUpdate completed.\n");
        $this->writeLog("-- End checkUpdate (".date("Y/m/d H:i:s").") --\n\n");
        return true;
    }

    /**
     * Check item exists
     *
     * @param array $dbItemData
     * @return bool
     */
    private function checkItemExists($dbItemData)
    {
        if(!isset($dbItemData["item"][0]["is_delete"]) || $dbItemData["item"][0]["is_delete"] != 0)
        {
            return false;
        }
        return true;
    }

    /**
     * Check item type match
     *
     * @param array $dbItemData
     * @param array $xmlItemData
     * @param array $xmlItemTypeData
     * @param string $itemTypeName
     * @return bool
     */
    private function checkItemTypeMatch($dbItemData, &$xmlItemData, &$xmlItemTypeData, &$itemTypeName)
    {
        $itemTypeId = $dbItemData["item"][0]["item_type_id"];

        // アイテムタイプ名称チェック
        //  DB内のアイテムタイプ名称がXML上のアイテムタイプ名称から始まっているか(前方一致)
        //  [example]
        //      1. XML: abc    / DB: abc    => OK
        //      2. XML: abc    / DB: abc_01 => OK
        //      3. XML: abc    / DB: xyz    => NG
        //      4. XML: abc_01 / DB: abc_02 => NG
        if(strpos($dbItemData["item_type"][0]["item_type_name"], $xmlItemTypeData['item_type_array'][0]['ITEM_TYPE_NAME'], 0) !== 0)
        {
            // 前方一致しないため更新不可
            return false;
        }
        $itemTypeName = $dbItemData["item_type"][0]["item_type_name"];

        // アイテムタイプメタデータ一致チェック
        $conflict = $this->importCommon_->checkMetadata(
                        $itemTypeId,
                        $xmlItemTypeData["item_attr_type_array"],
                        $xmlItemTypeData["item_attr_candidate_array"]);
        if(!$conflict)
        {
            // 一致しないため更新不可
            return false;
        }

        // XMLから取得した attribute_id は、ずれている場合があるので show_orderに合わせて補正する
        // Adjust attribute ID to show_order
        $this->importCommon_->adjustAttributeId(
                        $xmlItemData,
                        array("item_type_id" => $itemTypeId),
                        $xmlItemTypeData["item_attr_type_array"]);

        // XMLから取得したデータのアイテムタイプIDをDBのものに差し替える
        $this->replaceXmlItemData($dbItemData, $xmlItemData, $xmlItemTypeData);
        
        // Bug Fix WEKO-2014-046 T.Koyasu 2014/08/07 --start--
        // xmlItemTypeDataを実際に登録してあるデータから修正する(アイテムタイプのメタデータセットのチェックはアイテムの存在確認時に行っているのでエラーはここでは出ないはず、この前で出る)
        $this->importCommon_->validateItemTypeXmlData($itemTypeId, $xmlItemTypeData);
        // Bug Fix WEKO-2014-046 T.Koyasu 2014/08/07 --end--
        
        return true;
    }

    /**
     * Replace xml item data
     *
     * @param array $dbItemData
     * @param array $xmlItemData
     * @param array $xmlItemTypeData
     */
    private function replaceXmlItemData($dbItemData, &$xmlItemData, &$xmlItemTypeData)
    {
        $itemTypeId = $dbItemData["item"][0]["item_type_id"];

        // XML item data
        foreach($xmlItemData as $itemDataKey => $itemDataVal)
        {
            // ITEM_TYPE_ID を持つ配列群
            if( $itemDataKey == "item_array" || $itemDataKey == "item_attr_array" ||
                $itemDataKey == "personal_name_array" || $itemDataKey == "thumbnail_array" ||
                $itemDataKey == "file_array" || $itemDataKey == "biblio_info_array")
            {
                // Bug Fix WEKO-2014-046 T.Koyasu 2014/08/07 --start--
                // foreachで変数を再定義してしまうとせっかく引数が参照型になっている意味がない
                for($cnt = 0; $cnt < count($xmlItemData[$itemDataKey]); $cnt++)
                {
                    if(isset($xmlItemData[$itemDataKey][$cnt]['ITEM_TYPE_ID']))
                    {
                        $xmlItemData[$itemDataKey][$cnt]['ITEM_TYPE_ID'] = $itemTypeId;
                    }
                }
                // Bug Fix WEKO-2014-046 T.Koyasu 2014/08/07 --end--
            }
        }

        // XML item type data
        foreach($xmlItemTypeData as $itemTypeDataKey => $itemTypeDataVal)
        {
            // ITEM_TYPE_ID を持つ配列群
            if( $itemTypeDataKey == "item_type_array" ||
                $itemTypeDataKey == "item_attr_type_array" ||
                $itemTypeDataKey == "item_attr_candidate_array")
            {
                // Bug Fix WEKO-2014-046 T.Koyasu 2014/08/07 --start--
                // foreachで変数を再定義してしまうとせっかく引数が参照型になっている意味がない
                for($cnt = 0; $cnt < count($xmlItemTypeData[$itemTypeDataKey]); $cnt++)
                {
                    if(isset($xmlItemTypeData[$itemTypeDataKey][$cnt]['ITEM_TYPE_ID']))
                    {
                        $xmlItemTypeData[$itemTypeDataKey][$cnt]['ITEM_TYPE_ID'] = $itemTypeId;
                    }
                }
                // Bug Fix WEKO-2014-046 T.Koyasu 2014/08/07 --end--
            }
        }
    }

    /**
     * Check update authority
     *
     * @param int $itemId
     * @param int $itemNo
     * @param string $userId
     * @return bool
     */
    private function checkUpdateAuthority($itemId, $itemNo, $userId)
    {
        if(strlen($userId)==0)
        {
            $this->writeLog("NG / Guest user.\n");
            return false;
        }

        // user_id から user_auth_id, auth_id を取得
        $query = "SELECT role_authority_id FROM ".DATABASE_PREFIX."users ".
                 "WHERE user_id = ?; ";
        $params = array();
        $params[] = $userId;
        $result = $this->Db->execute($query, $params);
        if($result === false || count($result)!=1)
        {
            $this->writeLog("NG / User that does not exist.\n");
            return false;
        }
        $query = "SELECT user_authority_id FROM ".DATABASE_PREFIX."authorities ".
                 "WHERE role_authority_id = ".$result[0]["role_authority_id"]." ";
        $result = $this->Db->execute($query);
        if($result === false || count($result) != 1)
        {
            $this->writeLog("NG / Failed to get role_authority_id.\n");
            return false;
        }
        $user_auth_id = $result[0]["user_authority_id"];
        $auth_id = $this->getRoomAuthorityID($userId);

        // このユーザーが管理者か否か
        if($user_auth_id >= $this->repository_admin_base && $auth_id >= $this->repository_admin_room)
        {
            // WEKO管理者
            $this->writeLog("OK / Admin.\n");
            return true;
        }
        else
        {
            // 一般ユーザーならばアイテム登録者と一致するかどうか
            $query = "SELECT ".RepositoryConst::DBCOL_COMMON_INS_USER_ID." ".
                     "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_ITEM." ".
                     "WHERE ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_ID." = ? ".
                     "AND ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_NO." = ? ;";
            $params = array();
            $params[] = $itemId;
            $params[] = $itemNo;
            $result = $this->Db->execute($query, $params);
            if($result === false)
            {
                $this->writeLog("NG / Failed to get insert user's ID.\n");
                return false;
            }
            if($result[0][RepositoryConst::DBCOL_COMMON_INS_USER_ID] == $userId)
            {
                // アイテム登録者のuser_idと一致
                $this->writeLog("OK / Insert user\n");
                return true;
            }
        }
        $this->writeLog("NG / General user.\n");
        return false;
    }

    /**
     * Set item data array for ItemRegister
     *
     * 戻り値の仕様
     *   正常完了の場合： $errorMsg を空にして、true を返す
     *   更新はしないが、処理続行の場合： $errorMsg を詰めて、true を返す
     *   エラーにより処理中断の場合： $errorMsg を詰めて、false を返す
     *
     * @param array $xmlItemData
     * @param array $xmlItemTypeData
     * @param int $itemId
     * @param int $itemNo
     * // Add param itemTypeId 2013/09/09 R.Matsuura
     * @param int $itemTypeId
     * @param array $indexArray
     * @param string $tmpDir
     * @param array $irBasic
     * @param array $irMetadataArray
     * @param array $indexInfo
     * @param string $errorMsg
     * @return bool
     */
    private function setItemDataForItemRegister(
        $xmlItemData, $xmlItemTypeData, $itemId, $itemNo, $itemTypeId, $indexArray, $tmpDir, &$irBasic, &$irMetadataArray, &$indexInfo, &$errorMsg)
    {
        $this->writeLog("-- Start setItemDataForItemRegister (".date("Y/m/d H:i:s").") --\n");

        // ----------------------------------------------------------
        // アイテム基本情報更新用データ
        // ----------------------------------------------------------

        // language
        $language = $this->importCommon_->setLanguage($xmlItemData["item_array"][0]["LANGUAGE"]);

        // Title
        $titleArray = $this->importCommon_->validateTitle($xmlItemData["item_array"][0]["TITLE"], $xmlItemData["item_array"][0]["TITLE_ENGLISH"], $language);

        // アイテムの公開日
        $pubDate = $this->importCommon_->validatePubDate($xmlItemData["item_array"][0]["SHOWN_DATE"]);

        $irBasic = array(
                self::KEY_ITEM_ID => $itemId,
                self::KEY_ITEM_NO => $itemNo,
                self::KEY_REVISION_NO => self::REVISION_NO,
                self::KEY_ITEM_TYPE_ID => $itemTypeId,
                self::KEY_PREV_REVISION_NO => self::PREV_REVISION_NO,
                self::KEY_TITLE => $titleArray[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE],
                self::KEY_TITLE_EN => $titleArray[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH],
                self::KEY_LANGUAGE => $language,
                self::KEY_PUB_YEAR => intval($pubDate[ImportCommon::YEAR]),
                self::KEY_PUB_MONTH => intval($pubDate[ImportCommon::MONTH]),
                self::KEY_PUB_DAY => intval($pubDate[ImportCommon::DAY]),
                self::KEY_SEARCH_KEY => $xmlItemData["item_array"][0]["SERCH_KEY"],
                self::KEY_SEARCH_KEY_EN => $xmlItemData["item_array"][0]["SERCH_KEY_ENGLISH"],
                self::KEY_SHOWN_STATUS => $xmlItemData["item_array"][0]["SHOWN_STATUS"],
                self::KEY_FEEDBACK_MAILADDRESS => $xmlItemData["feedback_mailaddress_array"],
                self::KEY_CNRI_SUFFIX => $xmlItemData["cnri_array"],
                self::KEY_SELF_DOI => $xmlItemData["selfdoi_array"]
            );

        // ----------------------------------------------------------
        // メタデータ更新用データ
        // ----------------------------------------------------------
        $NameAuthority = new NameAuthority($this->Session, $this->Db);
        $irMetadataArray = array();

        // input_type, display_lang_type, plural_enable をattribute_idから参照する配列を作る
        $inputTypeList = array();
        $dispLangTypeList = array();
        $pluralEnableList = array();
        $tmpAttrId = 0;
        $pluralFlag = false;
        foreach($xmlItemTypeData["item_attr_type_array"] as $itemAttrType)
        {
            $attrId = intval($itemAttrType["ATTRIBUTE_ID"]);
            $inputTypeList[$attrId] = $itemAttrType["INPUT_TYPE"];
            $dispLangTypeList[$attrId] = $itemAttrType["DISPLAY_LANG_TYPE"];
            $pluralEnableList[$attrId] = $itemAttrType["PLURAL_ENABLE"];
        }

        // candidate をattribute_idから参照する配列を作る
        $candidateList = array();
        foreach($xmlItemTypeData["item_attr_candidate_array"] as $itemAttrCandidate)
        {
            $attrId = intval($itemAttrCandidate["ATTRIBUTE_ID"]);
            $candidateValue = $itemAttrCandidate["CANDIDATE_VALUE"];
            if(!array_key_exists($attrId, $candidateList))
            {
                $candidateList[$attrId] = array();
            }
            array_push($candidateList[$attrId], $candidateValue);
        }

        // item_attr
        for($ii=0; $ii<count($xmlItemData["item_attr_array"]); $ii++)
        {
            // attribute_id
            $attrId = intval($xmlItemData["item_attr_array"][$ii]["ATTRIBUTE_ID"]);
            if($attrId == 0)
            {
                continue;
            }

            // attribute_no
            $attrNo = intval($xmlItemData["item_attr_array"][$ii]["ATTRIBUTE_NO"]);
            if($attrNo == 0)
            {
                continue;
            }

            // Check plural enable
            if($tmpAttrId != $attrId)
            {
                $tmpAttrId = $attrId;
                $pluralFlag = false;
            }
            else if($pluralEnableList[$attrId] != "1" && $pluralFlag)
            {
                // warning
                $this->writeLog("  Warning: Unable to plural. attribute_id=".$attrId.". attribute_no=".$attrNo."\n");
                continue;
            }

            $inputType = $inputTypeList[$attrId];
            $attrValue = str_replace("\\n", "\n", $xmlItemData["item_attr_array"][$ii]["ATTRIBUTE_VALUE"]);

            if($inputType == RepositoryConst::ITEM_ATTR_TYPE_CHECKBOX ||
               $inputType == RepositoryConst::ITEM_ATTR_TYPE_SELECT ||
               $inputType == RepositoryConst::ITEM_ATTR_TYPE_RADIO)
            {
                // 候補チェック: 一致しなければ空にする
                if(array_search($attrValue, $candidateList[$attrId])===false)
                {
                    $attrValue = "";
                }
                if($inputType == RepositoryConst::ITEM_ATTR_TYPE_RADIO && strlen($attrValue)==0)
                {
                    // ラジオボタンなら先頭の選択肢を設定する
                    $attrValue = $candidateList[$attrId][0];
                }
            }
            else if($inputType == RepositoryConst::ITEM_ATTR_TYPE_DATE)
            {
                $attrValue = RepositoryOutputFilter::date($attrValue);
            }
            else if($inputType == RepositoryConst::ITEM_ATTR_TYPE_LINK)
            {
                $attrValue = $this->importCommon_->validateLink($attrValue);
            }

            // Check attribute value
            if(strlen(RepositoryOutputFilter::string($attrValue)) == 0)
            {
                continue;
            }

            $metadata = array(  self::KEY_ITEM_ID => $itemId,
                                self::KEY_ITEM_NO => $itemNo,
                                self::KEY_ITEM_TYPE_ID => $itemTypeId,
                                self::KEY_ATTR_ID => $attrId,
                                self::KEY_ATTR_NO => $attrNo,
                                self::KEY_INPUT_TYPE => $inputType,
                                self::KEY_ATTR_VALUE => $attrValue);
            array_push($irMetadataArray, $metadata);
            $pluralFlag = true;
        }

        // personal_name
        for($ii=0; $ii<count($xmlItemData["personal_name_array"]); $ii++)
        {
            // attribute_id
            $attrId = intval($xmlItemData["personal_name_array"][$ii]["ATTRIBUTE_ID"]);
            if($attrId == 0)
            {
                continue;
            }

            // personal_name_no
            $attrNo = intval($xmlItemData["personal_name_array"][$ii]["PERSONAL_NAME_NO"]);
            if($attrNo == 0)
            {
                continue;
            }

            // Check plural enable
            if($tmpAttrId != $attrId)
            {
                $tmpAttrId = $attrId;
                $pluralFlag = false;
            }
            else if($pluralEnableList[$attrId] != "1" && $pluralFlag)
            {
                $this->writeLog("  Warning: Unable to plural. attribute_id=".$attrId.". personal_name_no=".$attrNo."\n");
                continue;
            }

            // Check value
            if(strlen(RepositoryOutputFilter::string($xmlItemData["personal_name_array"][$ii]["FAMILY"])) == 0 &&
               strlen(RepositoryOutputFilter::string($xmlItemData["personal_name_array"][$ii]["NAME"])) == 0 &&
               strlen(RepositoryOutputFilter::string($xmlItemData["personal_name_array"][$ii]["FAMILY_RUBY"])) == 0 &&
               strlen(RepositoryOutputFilter::string($xmlItemData["personal_name_array"][$ii]["NAME_RUBY"])) == 0 &&
               strlen(RepositoryOutputFilter::string($xmlItemData["personal_name_array"][$ii]["E_MAIL_ADDRESS"])) == 0)
            {
                continue;
            }

            // Regist Name Authority
            $prefixNameArray = explode("|", $xmlItemData["personal_name_array"][$ii]['PREFIX_NAME']);
            $suffixArray = explode("|", $xmlItemData["personal_name_array"][$ii]['SUFFIX']);
            $externalAuthorIds = array();
            for($cnt=0; $cnt<count($prefixNameArray); $cnt++)
            {
                $prefixName = $prefixNameArray[$cnt];
                if(strlen($prefixName)>0)
                {
                    // Search same prefix_name in DB
                    $suffix = $suffixArray[$cnt];
                    $prefixId = $NameAuthority->getExternalAuthorIdPrefixId($prefixName);
                    if($prefixId === false) {
                        // [Error]
                        $errorMsg = "UPDATE ERROR: Failed to get external authorID prefix.";
                        $this->writeLog("  ".$errorMsg."\n");
                        $this->writeLog("-- End setItemDataForItemRegister (".date("Y/m/d H:i:s").") --\n\n");
                        return false;
                    }
                    if($prefixId == 0){
                        // No hit -> Regist new prefix
                        $prefixId = $NameAuthority->addExternalAuthorIdPrefix($prefixName);
                        if($prefixId === false){
                            // [Error]
                            $errorMsg = "UPDATE ERROR: Failed to add external authorID prefix.";
                            $this->writeLog("  ".$errorMsg."\n");
                            $this->writeLog("-- End setItemDataForItemRegister (".date("Y/m/d H:i:s").") --\n\n");
                            return false;
                        }
                    }
                    array_push($externalAuthorIds,
                                array(  self::KEY_PREFIX_ID => $prefixId,
                                        self::KEY_SUFFIX => $suffix)
                                );
                }
            }
            // Add e-person 2013/11/05 R.Matsuura --start--
            // when exist mail address
            if(strlen($xmlItemData["personal_name_array"][$ii]["E_MAIL_ADDRESS"]) > 0) {
                $extAuthorIds = array(
                                        self::KEY_PREFIX_ID => 0,
                                        self::KEY_SUFFIX => $xmlItemData["personal_name_array"][$ii]["E_MAIL_ADDRESS"]
                                    );
                array_push($externalAuthorIds, $extAuthorIds);
            }
            // Add e-person 2013/11/05 R.Matsuura --end--

            $metadata = array(  self::KEY_ITEM_ID => $itemId,
                                self::KEY_ITEM_NO => $itemNo,
                                self::KEY_ITEM_TYPE_ID => $itemTypeId,
                                self::KEY_ATTR_ID => $attrId,
                                self::KEY_NAME_NO => $attrNo,
                                self::KEY_FAMILY => $xmlItemData["personal_name_array"][$ii]["FAMILY"],
                                self::KEY_NAME => $xmlItemData["personal_name_array"][$ii]["NAME"],
                                self::KEY_FAMILY_RUBY => $xmlItemData["personal_name_array"][$ii]["FAMILY_RUBY"],
                                self::KEY_NAME_RUBY => $xmlItemData["personal_name_array"][$ii]["NAME_RUBY"],
                                self::KEY_EMAIL => $xmlItemData["personal_name_array"][$ii]["E_MAIL_ADDRESS"],
                                self::KEY_AUTHOR_ID => 0,
                                self::KEY_LANGUAGE => $dispLangTypeList[$attrId],
                                self::KEY_EX_AUTHOR_ID => $externalAuthorIds,
                                self::KEY_INPUT_TYPE => $inputTypeList[$attrId]);
            array_push($irMetadataArray, $metadata);
            $pluralFlag = true;
        }

        // biblio_info
        for($ii=0; $ii<count($xmlItemData["biblio_info_array"]); $ii++)
        {
            // attribute_id
            $attrId = intval($xmlItemData["biblio_info_array"][$ii]["ATTRIBUTE_ID"]);
            if($attrId == 0)
            {
                continue;
            }

            // biblio_no
            $attrNo = intval($xmlItemData["biblio_info_array"][$ii]["BIBLIO_NO"]);
            if($attrNo == 0)
            {
                continue;
            }

            // Check plural enable
            if($tmpAttrId != $attrId)
            {
                $tmpAttrId = $attrId;
                $pluralFlag = false;
            }
            else if($pluralEnableList[$attrId] != "1" && $pluralFlag)
            {
                $this->writeLog("  Warning: Unable to plural. attribute_id=".$attrId.". biblio_no=".$attrNo."\n");
                continue;
            }

            // Check value
            if(strlen(RepositoryOutputFilter::string($xmlItemData["biblio_info_array"][$ii]["BIBLIO_NAME"])) == 0 &&
               strlen(RepositoryOutputFilter::string($xmlItemData["biblio_info_array"][$ii]["BIBLIO_NAME_ENGLISH"])) == 0 &&
               strlen(RepositoryOutputFilter::string($xmlItemData["biblio_info_array"][$ii]["VOLUME"])) == 0 &&
               strlen(RepositoryOutputFilter::string($xmlItemData["biblio_info_array"][$ii]["ISSUE"])) == 0 &&
               strlen(RepositoryOutputFilter::string($xmlItemData["biblio_info_array"][$ii]["START_PAGE"])) == 0 &&
               strlen(RepositoryOutputFilter::string($xmlItemData["biblio_info_array"][$ii]["END_PAGE"])) == 0 &&
               strlen(RepositoryOutputFilter::date($xmlItemData["biblio_info_array"][$ii]["DATE_OF_ISSUED"])) == 0)
            {
                continue;
            }

            $metadata = array(  self::KEY_ITEM_ID => $itemId,
                                self::KEY_ITEM_NO => $itemNo,
                                self::KEY_ITEM_TYPE_ID => $itemTypeId,
                                self::KEY_ATTR_ID => $attrId,
                                self::KEY_BIBLIO_NO => $attrNo,
                                self::KEY_BIBLIO_NAME => $xmlItemData["biblio_info_array"][$ii]["BIBLIO_NAME"],
                                self::KEY_BIBLIO_NAME_EN => $xmlItemData["biblio_info_array"][$ii]["BIBLIO_NAME_ENGLISH"],
                                self::KEY_VOLUME => $xmlItemData["biblio_info_array"][$ii]["VOLUME"],
                                self::KEY_ISSUE => $xmlItemData["biblio_info_array"][$ii]["ISSUE"],
                                self::KEY_SPAGE => $xmlItemData["biblio_info_array"][$ii]["START_PAGE"],
                                self::KEY_EPAGE => $xmlItemData["biblio_info_array"][$ii]["END_PAGE"],
                                self::KEY_DATE_OF_ISSUED => RepositoryOutputFilter::date($xmlItemData["biblio_info_array"][$ii]["DATE_OF_ISSUED"]),
                                self::KEY_INPUT_TYPE => $inputTypeList[$attrId]);
            array_push($irMetadataArray, $metadata);
            $pluralFlag = true;
        }

        $existingThumbnailData = array();
        $nextThumnailNo = array();
        // thumbnail
        for($ii=0; $ii<count($xmlItemData["thumbnail_array"]); $ii++)
        {
            // attribute_id
            $attrId = intval($xmlItemData["thumbnail_array"][$ii]["ATTRIBUTE_ID"]);
            if($attrId == 0)
            {
                continue;
            }

            // file_no
            $attrNo = intval($xmlItemData["thumbnail_array"][$ii]["FILE_NO"]);
            if($attrNo == 0)
            {
                continue;
            }

            // Check plural enable
            if($tmpAttrId != $attrId)
            {
                $tmpAttrId = $attrId;
                $pluralFlag = false;
            }
            else if($pluralEnableList[$attrId] != "1" && $pluralFlag)
            {
                $this->writeLog("  Warning: Unable to plural. attribute_id=".$attrId.". file_no=".$attrNo."\n");
                continue;
            }

            // Check file_name
            if(strlen(RepositoryOutputFilter::string($xmlItemData["thumbnail_array"][$ii]["FILE_NAME"])) == 0)
            {
                continue;
            }

            // 1. XML内のfile_nameと一致するファイルが存在するかどうかをチェック
            //    -> 該当ファイルが無い場合はエラーとする。
            $fileName = mb_convert_encoding($xmlItemData["thumbnail_array"][$ii]["FILE_NAME"], $this->getEncodeByOS(), "auto");
            $tmpPath = $tmpDir.DIRECTORY_SEPARATOR.$fileName;
            
            if(!file_exists($tmpPath))
            {
                // [Warning]該当ファイルが無いためこのアイテムは更新しない
                $errorMsg = "UPDATE ERROR: File not exists '".$xmlItemData["thumbnail_array"][$ii]["FILE_NAME"]."'.";
                $this->writeLog("  ".$errorMsg."\n");
                $this->writeLog("-- End setItemDataForItemRegister (".date("Y/m/d H:i:s").") --\n\n");
                return true;
            }

            if(!array_key_exists($attrId, $existingThumbnailData)){
                $query = " SELECT file_no, file_name, show_order ".
                         " FROM ". DATABASE_PREFIX ."repository_thumbnail ".
                         " WHERE item_id = ? ".
                         " AND item_no = ? ".
                         " AND attribute_id = ? ".
                         " AND is_delete = ? ;";

                $params = null;
                $params[] = $itemId;
                $params[] = $itemNo;
                $params[] = $attrId;
                $params[] = 0;
                // SELECT実行
                $result = $this->Db->execute($query, $params);
                if($result === false){
                    $Error_Msg = $this->Db->ErrorMsg();
                    $this->Session->setParameter("error_cord",-1);
                    return false;
                }
                $existingThumbnailData[$attrId] = $result;
                for($jj = 0; $jj < count($existingThumbnailData[$attrId]); $jj++){
                    $existingThumbnailData[$attrId][$jj]['entry_flag'] = true;
                }
                $fileNo = $this->calcMaxThumbnailNo($itemId, $itemNo, $attrId, $errMsg);
                $nextThumnailNo[$attrId] = $fileNo+1;
            }
            $setFileNoFlag = false;
            for($jj = 0; $jj < count($existingThumbnailData[$attrId]); $jj++){
                if($existingThumbnailData[$attrId][$jj]['entry_flag'] && $fileName == $existingThumbnailData[$attrId][$jj][self::KEY_FILE_NAME]){
                    $attrNo = $existingThumbnailData[$attrId][$jj][self::KEY_FILE_NO];
                    $show_order = $existingThumbnailData[$attrId][$jj]['show_order'];
                    $existingThumbnailData[$attrId][$jj]['entry_flag'] = false;
                    $setFileNoFlag = true;
                }
            }
            if(!$setFileNoFlag){
                $attrNo = $nextThumnailNo[$attrId];
                $show_order = $attrNo;
                $nextThumnailNo[$attrId] += 1;
            }
            // 2. 更新用ファイル配列を作成
            $thumbnail = array();
            $thumbnail[self::KEY_FILE_NAME] = $xmlItemData["thumbnail_array"][$ii]["FILE_NAME"];
            $thumbnail[self::KEY_MIMETYPE] = $xmlItemData["thumbnail_array"][$ii]["MIME_TYPE"];
            $thumbnail[self::KEY_EXTENSION] = $xmlItemData["thumbnail_array"][$ii]["EXTENSION"];
            $thumbnail[self::KEY_PHYSICAL_FILE_NAME] = $fileName;

            $metadata = array(  self::KEY_ITEM_ID => $itemId,
                                self::KEY_ITEM_NO => $itemNo,
                                self::KEY_ITEM_TYPE_ID => $itemTypeId,
                                self::KEY_ATTR_ID => $attrId,
                                self::KEY_FILE_NO => $attrNo,
                                self::KEY_INPUT_TYPE => $inputTypeList[$attrId],
                                self::KEY_SHOW_ORDER => $show_order,
                                self::KEY_UPLOAD => $thumbnail,
                                self::KEY_WIDTH => 0,
                                self::KEY_HEIGHT => 0,
                                self::KEY_FILE_DIR => $tmpDir);
            array_push($irMetadataArray, $metadata);
            $pluralFlag = true;
        }

        $existingFileData = array();
        $nextFileNo = array();
        // file_array
        for($ii=0; $ii<count($xmlItemData["file_array"]); $ii++)
        {
            // attribute_id
            $attrId = intval($xmlItemData["file_array"][$ii]["ATTRIBUTE_ID"]);
            if($attrId == 0)
            {
                continue;
            }

            // file_no
            $attrNo = intval($xmlItemData["file_array"][$ii]["FILE_NO"]);
            if($attrNo == 0)
            {
                continue;
            }

            // Check plural enable
            if($tmpAttrId != $attrId)
            {
                $tmpAttrId = $attrId;
                $pluralFlag = false;
            }
            else if($pluralEnableList[$attrId] != "1" && $pluralFlag)
            {
                $this->writeLog("  Warning: Unable to plural. attribute_id=".$attrId.". file_no=".$attrNo."\n");
                continue;
            }

            // Check file_name
            if(strlen(RepositoryOutputFilter::string($xmlItemData["file_array"][$ii]["FILE_NAME"])) == 0)
            {
                continue;
            }

            // 1. XML内のfile_nameと一致するファイルが存在するかどうかをチェック
            //    -> 該当ファイルが無い場合はエラーとする。
            $fileName = mb_convert_encoding($xmlItemData["file_array"][$ii]["FILE_NAME"], $this->getEncodeByOS(), "auto");
            $tmpPath = $tmpDir.DIRECTORY_SEPARATOR.$fileName;
            
            if(!file_exists($tmpPath))
            {
                // [Warning]該当ファイルが無いためこのアイテムは更新しない
                $errorMsg = "UPDATE ERROR: File not exists '".$xmlItemData["file_array"][$ii]["FILE_NAME"]."'.";
                $this->writeLog("  ".$errorMsg."\n");
                $this->writeLog("-- End setItemDataForItemRegister (".date("Y/m/d H:i:s").") --\n\n");
                return true;
            }

            if(!array_key_exists($attrId, $existingFileData)){
                $query = " SELECT file_no, file_name, show_order ".
                         " FROM ". DATABASE_PREFIX ."repository_file ".
                         " WHERE item_id = ? ".
                         " AND item_no = ? ".
                         " AND attribute_id = ? ".
                         " AND is_delete = ? ;";

                $params = null;
                $params[] = $itemId;
                $params[] = $itemNo;
                $params[] = $attrId;
                $params[] = 0;
                // SELECT実行
                $result = $this->Db->execute($query, $params);
                if($result === false){
                    $Error_Msg = $this->Db->ErrorMsg();
                    $this->Session->setParameter("error_cord",-1);
                    return false;
                }
                $existingFileData[$attrId] = $result;
                for($jj = 0; $jj < count($existingFileData[$attrId]); $jj++){
                    $existingFileData[$attrId][$jj]['entry_flag'] = true;
                }
                $fileNo = $this->getFileNo($itemId, $itemNo, $attrId, $errMsg);
                $nextFileNo[$attrId] = $fileNo;
            }
            $setFileNoFlag = false;
            for($jj = 0; $jj < count($existingFileData[$attrId]); $jj++){
                if($existingFileData[$attrId][$jj]['entry_flag'] && $fileName == $existingFileData[$attrId][$jj][self::KEY_FILE_NAME]){
                    $attrNo = $existingFileData[$attrId][$jj][self::KEY_FILE_NO];
                    $show_order = $existingFileData[$attrId][$jj]['show_order'];
                    $existingFileData[$attrId][$jj]['entry_flag'] = false;
                    $setFileNoFlag = true;
                }
            }
            if(!$setFileNoFlag){
                $attrNo = $nextFileNo[$attrId];
                $show_order = $attrNo;
                $nextFileNo[$attrId] += 1;
            }
            // 2. 更新用ファイル配列を作成
            //    -> 課金ファイルの場合は課金情報もまとめておく
            // -----------------------
            // File data
            // -----------------------
            // display_type
            $displayType = $this->importCommon_->validateFileDisplayType($xmlItemData["file_array"][$ii]["DISPLAY_TYPE"]);

            // license
            $license = $this->importCommon_->validateFileLicense($xmlItemData["file_array"][$ii]["LICENSE_ID"], $xmlItemData["file_array"][$ii]["LICENSE_NOTATION"]);

            // ファイルおよびフラッシュの公開日
            $filePubDate = $this->importCommon_->validatePubDate($xmlItemData["file_array"][$ii]["PUB_DATE"]);
            $flashPubDate = array(ImportCommon::YEAR => "", ImportCommon::MONTH => "", ImportCommon::DAY => "");
            if($displayType == RepositoryConst::FILE_DISPLAY_TYPE_FLASH)
            {
                $flashPubDate = $this->importCommon_->validatePubDate($xmlItemData["file_array"][$ii]["FLASH_PUB_DATE"]);
            }

            $file = array();
            $file[self::KEY_FILE_NAME] = $xmlItemData["file_array"][$ii]["FILE_NAME"];
            $file[self::KEY_MIMETYPE] = $xmlItemData["file_array"][$ii]["MIME_TYPE"];
            $file[self::KEY_EXTENSION] = $xmlItemData["file_array"][$ii]["EXTENSION"];
            $file[self::KEY_PHYSICAL_FILE_NAME] = $fileName;

            $metadata = array(  self::KEY_ITEM_ID => $itemId,
                                self::KEY_ITEM_NO => $itemNo,
                                self::KEY_ITEM_TYPE_ID => $itemTypeId,
                                self::KEY_ATTR_ID => $attrId,
                                self::KEY_FILE_NO => $attrNo,
                                self::KEY_DISPLAY_NAME => $xmlItemData["file_array"][$ii]["DISPLAY_NAME"],
                                self::KEY_DISPLAY_TYPE => $displayType,
                                self::KEY_SHOW_ORDER => $show_order,
                                self::KEY_LICENSE_ID => $license[RepositoryConst::DBCOL_REPOSITORY_FILE_LICENSE_ID],
                                self::KEY_LICENSE_NOTATION => str_replace("\\n", "\n", $license[RepositoryConst::DBCOL_REPOSITORY_FILE_LICENSE_NOTATION]),
                                self::KEY_EMBARGO_FLAG => 2,
                                self::KEY_EMBARGO_YEAR => intval($filePubDate[ImportCommon::YEAR]),
                                self::KEY_EMBARGO_MONTH => intval($filePubDate[ImportCommon::MONTH]),
                                self::KEY_EMBARGO_DAY => intval($filePubDate[ImportCommon::DAY]),
                                self::KEY_FLASH_EMBARGO_FLAG => 2,
                                self::KEY_FLASH_EMBARGO_YEAR => intval($flashPubDate[ImportCommon::YEAR]),
                                self::KEY_FLASH_EMBARGO_MONTH => intval($flashPubDate[ImportCommon::MONTH]),
                                self::KEY_FLASH_EMBARGO_DAY => intval($flashPubDate[ImportCommon::DAY]),
                                self::KEY_INPUT_TYPE => $inputTypeList[$attrId],
                                self::KEY_UPLOAD => $file,
                                self::KEY_FILE_DIR => $tmpDir);

            // -----------------------
            // Price data
            // -----------------------
            if($inputTypeList[$attrId] == "file_price")
            {
                // file_price_array
                foreach($xmlItemData["file_price_array"] as $priceData)
                {
                    if( $priceData["ITEM_ID"] == $xmlItemData["file_array"][$ii]["ITEM_ID"] &&
                        $priceData["ITEM_NO"] == $xmlItemData["file_array"][$ii]["ITEM_NO"] &&
                        $priceData["ATTRIBUTE_ID"] == $xmlItemData["file_array"][$ii]["ATTRIBUTE_ID"] &&
                        $priceData["FILE_NO"] == $xmlItemData["file_array"][$ii]["FILE_NO"])
                    {
                        $roomPrice = explode("|", $priceData["PRICE"]);
                        $priceNum = 0;
                        $priceValue = array();
                        $priceRoomId = array();
                        for($priceCnt=0; $priceCnt<count($roomPrice); $priceCnt++)
                        {
                            $price = explode(",", $roomPrice[$priceCnt], 2);
                            if($price!=null && count($price)==2)
                            {
                                $roomId = 0;
                                if($price[0] != '0'){
                                    $query = "SELECT room_id FROM ".DATABASE_PREFIX."pages ".
                                             "WHERE page_name = ?; ";
                                    $params = array();
                                    $params[] = $price[0];
                                    $ret = $this->Db->execute($query, $params);
                                    if($ret === false){
                                        // [Error]DBエラー
                                        $errorMsg = "UPDATE ERROR: ".$this->Db->ErrorMsg();
                                        $this->writeLog("  ".$errorMsg."\n");
                                        $this->writeLog("-- End setItemDataForItemRegister (".date("Y/m/d H:i:s").") --\n\n");
                                        return false;
                                    }
                                    if(count($ret)!=1){
                                        continue;
                                    }
                                    $roomId = $ret[0]["room_id"];
                                }
                                array_push($priceRoomId, $roomId);
                                array_push($priceValue, $price[1]);
                                $priceNum++;
                            }
                        }
                        $metadata[self::KEY_PRICE_NUM] = $priceNum;
                        $metadata[self::KEY_ROOM_ID] = $priceRoomId;
                        $metadata[self::KEY_PRICE_VALUE] = $priceValue;
                        break;
                    }
                }
            }
            array_push($irMetadataArray, $metadata);
            $pluralFlag = true;
        }
        // ---------------------------------
        // 所属インデックス
        // ---------------------------------
        $indexInfo = array();
        for($ii=0; $ii<count($indexArray); $ii++)
        {
            $tmpIndexInfo = array(self::KEY_INDEX_ID => $indexArray[$ii]);
            if(array_search($tmpIndexInfo, $indexInfo)===false)
            {
                array_push($indexInfo, $tmpIndexInfo);
            }
        }

        $errorMsg = "";

        $this->writeLog("  setItemDataForItemRegister completed.\n");
        $this->writeLog("-- End setItemDataForItemRegister (".date("Y/m/d H:i:s").") --\n\n");

        return true;
    }

    /**
     * Execute update by ItemRegister
     *
     * 戻り値の仕様
     *   正常完了の場合： $errorMsg を空にして、true を返す
     *   更新はしないが、処理続行の場合： $errorMsg を詰めて、true を返す
     *   エラーにより処理中断の場合： $errorMsg を詰めて、false を返す
     *
     * @param array $irBasic
     * @param array $irMetadataArray
     * @param array $indexInfo
     * @param string $userId
     * @param string $detailUrl
     * @param string $errorMsg
     * @param int $reviewStatus
     * @param string $warningMsg
     * @return bool
     */
    private function executeUpdateByItemRegister($irBasic, $irMetadataArray, $indexInfo, $userId, &$detailUrl, &$errorMsg, &$reviewStatus, &$warningMsg)
    {
        $this->writeLog("-- Start executeUpdateByItemRegister (".date("Y/m/d H:i:s").") --\n");

        $itemId = $irBasic[self::KEY_ITEM_ID];
        $itemNo = $irBasic[self::KEY_ITEM_NO];

        // Set user_id to ItemRegister class
        $this->itemRegister_->setInsUserId($userId);
        $this->itemRegister_->setModUserId($userId);
        $this->itemRegister_->setDelUserId($userId);

        // 登録中にする / Item edit start
        $this->writeLog("  Call editItem at itemRegister.");
        $result = $this->itemRegister_->editItem($itemId, $itemNo, $tmpErrorMsg);
        if($result === false)
        {
            // [Error]
            $errorMsg = "UPDATE ERROR: ".$tmpErrorMsg;
            $this->writeLog("\n  ".$errorMsg."\n");
            $this->writeLog("-- End executeUpdateByItemRegister (".date("Y/m/d H:i:s").") --\n\n");
            return false;
        }
        $this->writeLog(" ...complete.\n");

        // Set review status
        $reviewStatus = -1;

        // アイテム基本情報更新 / Update item data
        $this->writeLog("  Call updateItem at itemRegister.");
        $result = $this->itemRegister_->updateItem($irBasic, $tmpErrorMsg, $warningMsg);
        if($result === false)
        {
            // [Error]
            $errorMsg = "UPDATE ERROR: ".$tmpErrorMsg;
            $this->writeLog("\n  ".$errorMsg."\n");
            $this->writeLog("-- End executeUpdateByItemRegister (".date("Y/m/d H:i:s").") --\n\n");
            return false;
        }
        $this->writeLog(" ...complete.\n");

        // 既存メタデータ削除 / Delete metadata
        $this->writeLog("  Call deleteItemAttrData.");
        $result = $this->deleteItemAttrData($itemId, $itemNo, $userId, $tmpErrorMsg);
        if($result === false)
        {
            // [Error]
            $errorMsg = "UPDATE ERROR: ".$tmpErrorMsg;
            $this->writeLog("\n  ".$errorMsg."\n");
            $this->writeLog("-- End executeUpdateByItemRegister (".date("Y/m/d H:i:s").") --\n\n");
            return false;
        }
        $this->writeLog(" ...complete.\n");

        // ファイル情報全削除 ＆ サムネイル情報全削除 / Delete file and thumbnail
        $this->writeLog("  Call deleteFileAndThumbnail.");
        $result = $this->deleteFileAndThumbnail($itemId, $itemNo);
        if($result === false)
        {
            // [Error]
            $errorMsg = "UPDATE ERROR: Failed delete files.";
            $this->writeLog("\n  ".$errorMsg."\n");
            $this->writeLog("-- End executeUpdateByItemRegister (".date("Y/m/d H:i:s").") --\n\n");
            return false;
        }
        $this->writeLog(" ...complete.\n");

        // メタデータ更新
        $this->writeLog("  Start loop for metadata regist.\n");
        foreach($irMetadataArray as $irMetadata)
        {
            if( $irMetadata[self::KEY_INPUT_TYPE] == RepositoryConst::ITEM_ATTR_TYPE_FILE ||
                $irMetadata[self::KEY_INPUT_TYPE] == RepositoryConst::ITEM_ATTR_TYPE_FILEPRICE)
            {
                // ファイル情報登録
                $result = $this->itemRegister_->entryFile($irMetadata, $tmpErrorMsg, $irMetadata[self::KEY_FILE_DIR], false, true);
                if($result === false)
                {
                    // [Error]
                    $errorMsg = "UPDATE ERROR: ".$tmpErrorMsg;
                    $this->writeLog("  ".$errorMsg."\n");
                    $this->writeLog("-- End executeUpdateByItemRegister (".date("Y/m/d H:i:s").") --\n\n");
                    return false;
                }
                // ライセンス情報更新
                $result = $this->itemRegister_->updateFileLicense($irMetadata, $tmpErrorMsg);
                if($result === false)
                {
                    // [Error]
                    $errorMsg = "UPDATE ERROR: ".$tmpErrorMsg;
                    $this->writeLog("  ".$errorMsg."\n");
                    $this->writeLog("-- End executeUpdateByItemRegister (".date("Y/m/d H:i:s").") --\n\n");
                    return false;
                }
                if($irMetadata[self::KEY_INPUT_TYPE] == RepositoryConst::ITEM_ATTR_TYPE_FILEPRICE)
                {
                    // 課金情報レコード作成
                    $result = $this->itemRegister_->entryFilePrice($irMetadata, $tmpErrorMsg, true);
                    if($result === false)
                    {
                        // [Error]
                        $errorMsg = "UPDATE ERROR: ".$tmpErrorMsg;
                        $this->writeLog("  ".$errorMsg."\n");
                        $this->writeLog("-- End executeUpdateByItemRegister (".date("Y/m/d H:i:s").") --\n\n");
                        return false;
                    }
                    // 課金情報更新
                    $result = $this->itemRegister_->updatePrice($irMetadata, $tmpErrorMsg);
                    if($result === false)
                    {
                        // [Error]
                        $errorMsg = "UPDATE ERROR: ".$tmpErrorMsg;
                        $this->writeLog("  ".$errorMsg."\n");
                        $this->writeLog("-- End executeUpdateByItemRegister (".date("Y/m/d H:i:s").") --\n\n");
                        return false;
                    }
                }
            }
            else if($irMetadata[self::KEY_INPUT_TYPE] == RepositoryConst::ITEM_ATTR_TYPE_THUMBNAIL)
            {
                // サムネイル情報登録
                $result = $this->itemRegister_->entryThumbnail($irMetadata, $tmpErrorMsg, $irMetadata[self::KEY_FILE_DIR], false, true);
                if($result === false)
                {
                    // [Error]
                    $errorMsg = "UPDATE ERROR: ".$tmpErrorMsg;
                    $this->writeLog("  ".$errorMsg."\n");
                    $this->writeLog("-- End executeUpdateByItemRegister (".date("Y/m/d H:i:s").") --\n\n");
                    return false;
                }
            }
            else
            {
                // メタデータ更新
                $result = $this->itemRegister_->entryMetadata($irMetadata, $tmpErrorMsg);
                if($result === false)
                {
                    // [Error]
                    $errorMsg = "UPDATE ERROR: ".$tmpErrorMsg;
                    $this->writeLog("  ".$errorMsg."\n");
                    $this->writeLog("-- End executeUpdateByItemRegister (".date("Y/m/d H:i:s").") --\n\n");
                    return false;
                }
            }
        }
        $this->writeLog("  End loop for metadata regist.\n");
        
        // BugFix when before and after update, assignment doi is failed T.Koyasu 2015/03/09 --start--
        // must check self_doi when after update item metadatas
        $this->writeLog("  Check self doi and Add self doi");
        try{
            $this->itemRegister_->updateSelfDoi($irBasic);
        } catch(AppException $ex){
            $smartyAssign = $this->Session->getParameter('smartyAssign');
            if(strlen($warningMsg) > 0){
                $warningMsg .= "/";
            }
            $warningMsg .= $smartyAssign->getLang($ex->getMessage());
            $this->debugLog($ex->getMessage(). "::itemId=". $itemId, __FILE__, __CLASS__, __LINE__);
        }
        // BugFix when before and after update, assignment doi is failed T.Koyasu 2015/03/09 --end--

        // attribute_no 振り直し
        $this->writeLog("  Call reissueAttrNo at importCommon.");
        $result = $this->importCommon_->reissueAttrNo($itemId, $itemNo, $tmpErrorMsg);
        if($result === false)
        {
            // [Error]
            $errorMsg = "UPDATE ERROR: ".$tmpErrorMsg;
            $this->writeLog("\n  ".$errorMsg."\n");
            $this->writeLog("-- End executeUpdateByItemRegister (".date("Y/m/d H:i:s").") --\n\n");
            return false;
        }
        $this->writeLog("  ...complete.\n");

        // 所属インデックス更新
        $this->writeLog("  Call entryPositionIndex at importCommon.");
        $result = $this->itemRegister_->entryPositionIndex($irBasic, $indexInfo, $tmpErrorMsg);
        if($result === false)
        {
            // [Error]
            $errorMsg = "UPDATE ERROR: ".$tmpErrorMsg;
            $this->writeLog("\n  ".$errorMsg."\n");
            $this->writeLog("-- End executeUpdateByItemRegister (".date("Y/m/d H:i:s").") --\n\n");
            return false;
        }
        $this->writeLog("  ...complete.\n");


        // 必須項目チェック
        $this->writeLog("  Call requiredCheck at importCommon.\n");
        $tmpErrorMsg = "";
        $tmpWarningMsg = "";
        $result = $this->importCommon_->requiredCheck($itemId, $itemNo, $tmpErrorMsg, $tmpWarningMsg);
        if($result)
        {
            $this->writeLog("  requiredCheck OK.\n");

            // 所属インデックス情報取得
            $this->writeLog("  Call getItemIndexData.");
            $indexIds = array();
            if(!$this->getItemIndexData($itemId, $itemNo, $resultList, $tmpErrorMsg))
            {
                // [Error]
                $errorMsg = "UPDATE ERROR: ".$tmpErrorMsg;
                $this->writeLog("\n  ".$errorMsg."\n");
                $this->writeLog("-- End executeUpdateByItemRegister (".date("Y/m/d H:i:s").") --\n\n");
                return false;
            }
            $this->writeLog("  ...complete.\n");
            foreach($resultList["position_index"] as $indexData)
            {
                array_push($indexIds, $indexData[RepositoryConst::DBCOL_REPOSITORY_INDEX_INDEX_ID]);
            }

            // suffix更新
            $this->writeLog("  Call setSuffix at importCommon.");
            $this->getRepositoryHandleManager();
            
            try{
                $isGetSuffix = $this->repositoryHandleManager->setSuffix($irBasic[self::KEY_TITLE], $itemId, $itemNo);
            } catch(AppException $ex){
                $smartyAssign = $this->Session->getParameter('smartyAssign');
                if(strlen($warningMsg) > 0){
                    $warningMsg .= "/";
                }
                $this->debugLog($ex->getMessage(). "::itemId=". $itemId, __FILE__, __CLASS__, __LINE__);
                $warningMsg .= $smartyAssign->getLang($ex->getMessage());
                $isGetSuffix = false;
            }
            
            if(!$isGetSuffix)
            {
                // [Warning]
                $errorMsg = "UPDATE WARNING: Failed to setSuffix.";
                $this->writeLog("\n  ".$errorMsg."\n");
            }
            $this->writeLog("  ...complete.\n");
            
            // ファイルFlash化
            $this->writeLog("  Call convertToFlash.");
            if(!$this->importCommon_->convertToFlash($itemId, $itemNo, $errorMsg))
            {
                // [Warning]
                $errorMsg = "UPDATE ERROR: Failed to file convert to flash.";
                $this->writeLog("\n  ".$errorMsg."\n");
                $this->writeLog("-- End executeUpdateByItemRegister (".date("Y/m/d H:i:s").") --\n\n");
                return true;
            }
            $this->writeLog("  ...complete.\n");

            // アイテム公開状態変更処理
            $this->writeLog("  Call setItemStatus at importCommon.");
            if(!$this->importCommon_->setItemStatus($itemId, $itemNo, $userId, $indexIds, $irBasic[self::KEY_SHOWN_STATUS], $reviewStatus))
            {
                // [Error]
                $errorMsg = "UPDATE ERROR: Failed to set item status.";
                $this->writeLog("\n  ".$errorMsg."\n");
                $this->writeLog("-- End executeUpdateByItemRegister (".date("Y/m/d H:i:s").") --\n\n");
                return false;
            }
            $this->writeLog("  ...complete.\n");
        }
        else
        {
            $this->writeLog("  requiredCheck NG.\n");

            // [Warning]
            $errorMsg = "UPDATE ERROR: ".$tmpErrorMsg;
            $this->writeLog("  ".$errorMsg."\n");
            $this->writeLog("-- End executeUpdateByItemRegister (".date("Y/m/d H:i:s").") --\n\n");
            return false;
        }

        // フルテキストデータ更新
        $this->writeLog("  Call updateSearchTableForItem.");
        // Add detail search 2013/11/25 K.Matsuo --start--
        $searchTableProcessing = new RepositorySearchTableProcessing($this->Session, $this->Db);
        $searchTableProcessing->updateSearchTableForItem($itemId, $itemNo);
        // Add detail search 2013/11/25 K.Matsuo --end--
        $this->writeLog("  ...complete.\n");
        $errorMsg = "";

        $this->writeLog("  executeUpdateByItemRegister completed.\n");
        $this->writeLog("-- End executeUpdateByItemRegister (".date("Y/m/d H:i:s").") --\n\n");

        return true;
    }

    /**
     * Delete file and thumbnail
     *
     * @param int $itemId
     * @param int $itemNo
     * @return bool
     */
    private function deleteFileAndThumbnail($itemId, $itemNo)
    {
        // アイテムに紐づく論理削除済みデータを含むファイル情報取得
        $query = "SELECT ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID.", ".
                           RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO.", ".
                           RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID.", ".
                           RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO." ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_FILE." ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO." = ? ;";
        $params = array();
        $params[] = $itemId;
        $params[] = $itemNo;
        $result = $this->Db->execute($query, $params);
        if($result === false)
        {
            return false;
        }
        for($ii=0; $ii<count($result); $ii++)
        {
            // ファイル削除処理
            $ret = $this->itemRegister_->deleteFile($result[$ii], true, $errMsg);
            if($ret === false)
            {
                return false;
            }
        }

        // アイテムに紐づく論理削除済みデータを含むサムネイル情報取得
        $query = "SELECT ".RepositoryConst::DBCOL_REPOSITORY_THUMB_ITEM_ID.", ".
                           RepositoryConst::DBCOL_REPOSITORY_THUMB_ITEM_NO.", ".
                           RepositoryConst::DBCOL_REPOSITORY_THUMB_ATTR_ID.", ".
                           RepositoryConst::DBCOL_REPOSITORY_THUMB_FILE_NO." ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_THUMBNAIL." ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_THUMB_ITEM_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_THUMB_ITEM_NO." = ? ;";
        $params = array();
        $params[] = $itemId;
        $params[] = $itemNo;
        $result = $this->Db->execute($query, $params);
        if($result === false)
        {
            return false;
        }
        for($ii=0; $ii<count($result); $ii++)
        {
            // サムネイル削除処理
            $ret = $this->itemRegister_->deleteThumbnail($result[$ii], $errMsg);
            if($ret === false)
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Send review mail
     *
     * @param int $itemId
     * @param int $itemNo
     * @return bool
     */
    private function sendReviewMail($itemId, $itemNo)
    {
        $this->writeLog("-- Start sendReviewMail (".date("Y/m/d H:i:s").") --\n");

        // 査読通知メールを送信するか否か
        $query = "SELECT ".RepositoryConst::DBCOL_REPOSITORY_PARAMETER_PARAM_VALUE." ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_PARAMETER." ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_PARAMETER_PARAM_NAME." = ?;";
        $params =array();
        $params[] = "review_mail_flg";
        $ret = $this->Db->execute($query, $params);
        if ($ret === false)
        {
            $this->writeLog("  Failed: ".$this->Db->ErrorMsg().".\n");
            $this->writeLog("-- End sendReviewMail (".date("Y/m/d H:i:s").") --\n\n");
            return false;
        }
        $reviewMailFlag = intval($ret[0][RepositoryConst::DBCOL_REPOSITORY_PARAMETER_PARAM_VALUE]);
        if($reviewMailFlag == 1)
        {
            $this->writeLog("  Send review mail.\n");

            // 送信先ユーザを取得
            $users = array();
            $this->getReviewMailInfo($users);

            // 送信者がいる場合、メールを作成する
            if(count($users) > 0)
            {
                // メール送信用クラス生成
                $mailMain = new Mail_Main();

                // 言語リソース取得
                $this->setLangResource();
                $smartyAssign = $this->Session->getParameter("smartyAssign");

                // 件名 / subject
                $subj = $smartyAssign->getLang("repository_mail_review_subject");
                $mailMain->setSubject($subj);

                // page_idおよびblock_idを取得
                $blockInfo = $this->getBlockPageId();

                // 本文 / body
                $ret = $this->getItemTableData($itemId, $itemNo, $resultList, $errMsg);
                $body = '';
                $body .= $smartyAssign->getLang("repository_mail_review_body")."\n\n";
                $body .= $smartyAssign->getLang("repository_mail_review_contents")."\n";
                $body .= $smartyAssign->getLang("repository_mail_review_title");
                if($this->Session->getParameter("_lang") == "japanese"){
                    if(strlen($resultList["item"][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE]) > 0){
                        $body .= $resultList["item"][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE];
                    } else if(strlen($resultList["item"][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH]) > 0){
                        $body .= $resultList["item"][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH];
                    } else {
                        $body .= "no title";
                    }
                } else {
                    if(strlen($resultList["item"][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH]) > 0){
                        $body .= $resultList["item"][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH];
                    } else if(strlen($resultList["item"][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE]) > 0){
                        $body .= $resultList["item"][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE];
                    } else {
                        $body .= "no title";
                    }
                }

                $body .= "\n";
                $body .= $smartyAssign->getLang("repository_mail_review_detailurl").$resultList["item"][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_URI]."\n";
                $body .= "\n";
                $body .= $smartyAssign->getLang("repository_mail_review_reviewurl")."\n";
                $body .= BASE_URL;
                if(substr(BASE_URL,-1,1) != "/"){
                    $body .= "/";
                }
                $body .= "?active_action=repository_view_edit_review&page_id=".$blockInfo["page_id"]."&block_id=".$blockInfo["block_id"];
                $body .= "\n\n".$smartyAssign->getLang("repository_mail_review_close");
                $mailMain->setBody($body);

                // ---------------------------------------------
                // 送信先を設定
                // set send to user
                //
                // $usersの中身
                // $users["email"] : 送信先メールアドレス
                // $user["handle"] : ハンドルネーム
                //                   なければ空白が自動設定される
                // $user["type"]   : type (html(email) or text(mobile_email))
                //                   なければhtmlが自動設定される
                // $user["lang_dirname"] : 言語
                //                         なければ現在の選択言語が自動設定される
                // ---------------------------------------------
                $mailMain->setToUsers($users);

                // ---------------------------------------------
                // メール送信
                // send confirm mail
                // ---------------------------------------------
                $return = $mailMain->send();

                // 言語リソース開放
                $this->Session->removeParameter("smartyAssign");

                $this->writeLog("  Reeview mail was sent.\n");
            }
            else
            {
                $this->writeLog("  There is no users to receive mail.\n");
            }
        }
        else
        {
            $this->writeLog("  Not send review mail.\n");
        }

        $this->writeLog("  sendReviewMail completed.\n");
        $this->writeLog("-- End sendReviewMail (".date("Y/m/d H:i:s").") --\n\n");
    }

    /**
     * Set insert index id array
     *
     * @param string $newIndex
     * @param string $checkedIds
     * @param string $userId
     * @param array $insIndexArray
     */
    private function setInsertIndexIdArray($newIndex, $checkedIds, $userId, &$insIndexArray)
    {
        $this->writeLog("-- Start insertNewIndex (".date("Y/m/d H:i:s").") --\n");

        $insIndexArray = array();

        // Set sword import class
        require_once WEBAPP_DIR. '/modules/repository/action/main/sword/import/Import.class.php';
        $swordImport = new Repository_Action_Main_Sword_Import($this->Session, $this->Db, $this->TransStartDate);
        $swordImport->setConfigAuthority();

        // Add SuppleContents Y.Yamazawa 2015/04/06 --start--
        $logName = WEBAPP_DIR."/logs/weko/sword/sword_import_update_log.txt";
        $swordImport->createSwordUpdateLogFile($logName);
        // Add SuppleContents Y.Yamazawa 2015/04/06 --end--

        // Fix check index authority 2013/06/12 Y.Nakao --start--
        // Set user_id
        if($swordImport->setSessionUserAuthority($this->loginId) === false)
        {
            // error user data.
            $this->writeLog("  Error: user data. login id : ".$this->loginId.".\n");
            return false;
        }
        // Fix check index authority 2013/06/12 Y.Nakao --end--

        // Fix if(strlen($newIndex) > 0 || strlen($checkedIds) > 0) => delete. for _REPOSITORY_PRIVATETREE_AUTO_AFFILIATION is true Y.Nakao 2013/07/05 --start--
        // Insert index and select check index
        $result = $swordImport->insertNewIndexAndSelectCheckIndex(
                        $newIndex, $checkedIds, $insIndexArray, $errorMsg, $userId, false);

        // Add SuppleContents Y.Yamazawa 2015/04/06 --start--
        $swordImport->closeLogFile();
        // Add SuppleContents Y.Yamazawa 2015/04/06 --end--

        // Fix for private tree 2014/3/5 R.Matsuura --start--
        // get index_id and owner user id before update
        $itemIndexIdAndOwnerUser = array();
        $this->getIndexIdAndOwner($this->itemId, $this->itemNo, $itemIndexIdAndOwnerUser);
        // Be private tree, owner user is not update user, not exist in ins index array
        for($cnt = 0; $cnt < count($itemIndexIdAndOwnerUser); $cnt++)
        {
            if(strlen($itemIndexIdAndOwnerUser[$cnt]['owner_user_id']) > 0 && $itemIndexIdAndOwnerUser[$cnt]['owner_user_id'] != $userId && !in_array($itemIndexIdAndOwnerUser[$cnt]['index_id'], $insIndexArray))
            {
                array_push($insIndexArray, $itemIndexIdAndOwnerUser[$cnt]['index_id']);
            }
        }
        // Fix for private tree 2014/3/5 R.Matsuura --end--
        if($result===false)
        {
            // Fix check index_id Y.Nakao 2013/06/07 --start--
            // 指定したインデックスが存在しないもしくは投稿権限がない場合
            $this->writeLog("  Warning: ".$errorMsg.".\n");
            for($ii=0; $ii<count($insIndexArray); $ii++)
            {
                $this->writeLog("  insert index id : ".$insIndexArray[$ii]."\n");
            }
            // Fix check index_id Y.Nakao 2013/06/07 --end--
        }
        if(strlen($newIndex) > 0 && strlen($checkedIds) > 0)
        {
            $this->writeLog("  this contents not belong to an index.\n");
        }
        // Fix if(strlen($newIndex) > 0 || strlen($checkedIds) > 0) => delete. for _REPOSITORY_PRIVATETREE_AUTO_AFFILIATION is true Y.Nakao 2013/07/05 --end--

        $this->writeLog("  insertNewIndex completed.\n");
        $this->writeLog("-- End insertNewIndex (".date("Y/m/d H:i:s").") --\n\n");

        return true;
    }

    /*
     * zip file extract to tmpDirPath
     * @param ファイルパス $filePath string
     * @param 出力先パス $tmpDirPath string
     * @return ファイルの出力の成功失敗
     */
    private function extraction($filePath, $tmpDirPath){

        // Check file exists
        if(!file_exists($filePath)){
            return false;
        }
        
        // Update SuppleContentsEntry Y.Yamazawa 2015/04/07 --satrt--
        // extract zip file
        $result = Repository_Components_Util_ZipUtility::extract($filePath, $tmpDirPath);
        if($result === false){
            unlink($filePath);
            return false;
        }
        // Update SuppleContentsEntry Y.Yamazawa 2015/04/07 --end--

        unlink($filePath);

        return true;
    }

    /**
     * Set encode charset
     */
    private function setEncode()
    {
        if (stristr($_SERVER['HTTP_USER_AGENT'], "Mac"))
        {
            // For Mac
            $this->encode_ = "UTF-8";
        }
        else if (stristr($_SERVER['HTTP_USER_AGENT'], "Windows"))
        {
            // For Windows
            $this->encode_ = "SJIS";
        }
        else
        {
            // Default
            $this->encode_ = _CHARSET;
        }
    }

    /**
     * Write log to file
     *
     * @param string $string
     * @param int $length [optional]
     * @return int
     */
    private function writeLog($string, $length=null)
    {
        if($this->isCreateLog && strlen($this->logFile)>0)
        {
            $ret = "";
            $fp = fopen($this->logFile, "a");
            if(isset($length))
            {
                $ret = fwrite($fp, $string, $length);
            }
            else
            {
                $ret = fwrite($fp, $string);
            }
            fclose($fp);

            return $ret;
        }
        else
        {
            return false;
        }
    }

    private function getRepositoryHandleManager()
    {
        if(!isset($this->repositoryHandleManager))
        {
            $this->repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->Db, $this->TransStartDate);
        }
    }

    private function getIndexIdAndOwner($itemId, $itemNo, &$itemIndexData)
    {
        // get index ID and index owner user from item Id and item No
        $query = "SELECT index_id ".
                 "FROM ".DATABASE_PREFIX."repository_position_index ".
                 "WHERE item_id = ? ".
                 "AND item_no = ? ".
                 "AND is_delete = ? ;";
        $params[] = $itemId;
        $params[] = $itemNo;
        $params[] = 0;
        $result = $this->dbAccess->executeQuery($query, $params);
        
        if(count($result) > 0)
        {
            $params = array();
            $query = "SELECT index_id, owner_user_id ".
                     "FROM ".DATABASE_PREFIX."repository_index ".
                     "WHERE index_id IN ( ";
            for($cnt = 0; $cnt < count($result); $cnt++)
            {
                if($cnt != 0)
                {
                    $query .= ",";
                }
                $query .= "?";
                $params[] = $result[$cnt]['index_id'];
            }
            $query .= ") AND is_delete = ? ;";
            $params[] = 0;
            
            $itemIndexData = $this->dbAccess->executeQuery($query, $params);
        }
    }

    // Add suppleContentsEntry Y.Yamazawa --start-- 2015/03/30 --start--
    /**
     * サプリコンテンツを登録する
     *
     * @param アイテムID $itemId string
     * @param アイテムNo $itemNo string
     * @param XML内のサプリコンテンツ情報 $supple_info_array array
     * @param エラーメッセージ string
     */
    private function entrySupple($itemId,$itemNo,$supple_info_array,&$error_msg)
    {
        // サプリコンテンツ情報を取得する。この時点では、ユーザーがサプリコンテンツの登録を行うか判断できないため
        // ビジネスロジック内のメソッドは使用しない。
        $suppleInfoList = $this->suppleContentInfo($itemId);

        // サプリコンテンツURLが空か確認
        if(((!isset($supple_info_array[0]) || !(strlen($supple_info_array[0]) > 0)) && count($suppleInfoList) == 0))
        {
            return false;
        }

        $smartyAssign = $this->Session->getParameter("smartyAssign");
        try{
            $this->writeLog("businessSupple", __FILE__, __CLASS__, __LINE__);
            $businessSupple = BusinessFactory::getFactory()->getBusiness("businessSupple");
        }
        catch (AppException $e){
            $msg = $e->getMessage();
            $msg = $smartyAssign->getLang($msg);
            $this->writeLog($msg, __FILE__, __CLASS__, __LINE__);
            $error_msg .= $msg;
            return false;
        }

        try {
            $businessSupple->updateAllSuppleContentsOfOneItemForImport($itemId,$itemNo,$supple_info_array);
        }
        catch(AppException $e)
        {
            // サプリWEKO側のPrefixID情報の取得及びサプリコンテンツ登録・更新・削除に必要な情報の取得に失敗した場合
            // 失敗した場合はcatchする
            $code = $e->getMessage();
            $msg = $smartyAssign->getLang($code);
            $this->errorLog($msg, __FILE__, __CLASS__, __LINE__);
            $error_msg .= $msg;

            return false;
        }
        return true;
    }
    // Add suppleContentsEntry Y.Yamazawa --end-- 2015/03/30 --end--

    // Add suppleContentsEntry Y.Yamazawa --start-- 2015/03/30 --start--
    /**
     * エラー時のXML出力
     *
     * @param エラー情報 $error_list array array
     */
    private function outputErrorXML($error_list,$text)
    {
        header("X-Error-Code: ". $text);

        // header
        header("Content-Type: text/xml; charset=utf-8");
        // -------------------------
        // XML
        // -------------------------
        $ret_xml = '<?xml version="1.0" encoding="UTF-8" ?>'."\n";
        $ret_xml .= '<sword:error xmlns="http://www.w3.org/2005/Atom" xmlns:sword="http://purl.org/net/sword/" xmlns:arxiv="http://arxiv.org/schemas/atom" href="http://example.org/errors/BadManifest">'."\n";
        $ret_xml .= '<title>ERROR</title>'."\n";
        $ret_xml .= '<version>2.0</version>'."\n";
        $ret_xml .= '<updated>2013-08-20JST16:46:0432400</updated>'."\n";
        $ret_xml .= '<author>'."\n";
        $ret_xml .= '<name></name>'."\n";
        $ret_xml .= '<email></email>'."\n";
        $ret_xml .= '</author>'."\n";
        $ret_xml .= '<source>'."\n";
        $ret_xml .= '<generator uri="'.BASE_URL.'/weko/sword/deposit.php" version="2"/>'."\n";
        $ret_xml .= '</source>'."\n";
        $ret_xml .= '<sword:treatment>Deposited items(zip) will be treated as WEKO import file which contains any WEKO contents information, and will be imported to WEKO.</sword:treatment>'."\n";
        $ret_xml .= '<summary>Contents update failed</summary>'."\n";
        if(count($error_list) > 0) {
            // sword description
            $description = "";
            for($ii = 0; $ii < count($error_list); $ii++) {
                $description .= "ERROR: ".$error_list[$ii]->error." ";
                if($error_list[$ii]->item_id > 0) {
                    $description .= "at Item ID ".$error_list[$ii]->item_id.";";
                }
            }
            $ret_xml .= '<sword:verboseDescription>'.$description.'</sword:verboseDescription>';
        }
        $ret_xml .= "</sword:error>" . "\n";
        
        print $ret_xml;
    }
    // Add suppleContentsEntry Y.Yamazawa --end-- 2015/03/30 --end--

    /**
     * アイテム更新成功時のXML出力
     *
     * @param アイテムID $item_id string
     * @param 警告 $warning_msg array
     */
    private function outputSuccessXML($item_id,$warning_msg)
    {
        // header
        header("Content-Type: text/xml; charset=utf-8");
        // -------------------------
        // XML
        // -------------------------
        $ret_xml = '<?xml version="1.0" encoding="UTF-8" ?>'."\n";
        $ret_xml .= '<entry xmlns="http://www.w3.org/2005/Atom" xmlns:sword="http://purl.org/net/sword/">'."\n";
        $ret_xml .= '<title>Repository Review</title>'."\n";
        $ret_xml .= '<version>2.0</version>'."\n";
        $ret_xml .= '<id>'.$item_id.'-'.$item_id.'</id>'."\n";
        $ret_xml .= '<updated>2013-08-20JST16:46:0432400</updated>'."\n";
        $ret_xml .= '<author>'."\n";

        // ユーザーIDの取得
        $user_id = $this->Session->getParameter("_user_id");
        $ret_xml .= '<name>'.$user_id.'</name>'."\n";

        // Emailアドレスの取得
        $result = $this->emailAddress($user_id, $login_email);
        if($result === false){
            $login_email = "";
        }
        $ret_xml .= '<email>'.$login_email.'</email>'."\n";
        $ret_xml .= '</author>'."\n";

        // アイテム詳細画面のURLと警告のメッセージ
        foreach ($warning_msg as $msg){
            $ret_xml .= '<content type="text/html" src="'.BASE_URL.'/?action=repository_uri&amp;item_id='.$item_id.'" message="'.$msg.'"/>'."\n";
        }

        $ret_xml .= '<source>'."\n";
        $ret_xml .= '<generator uri="'.BASE_URL.'/weko/sword/deposit.php" version="2"/>'."\n";
        $ret_xml .= '</source>'."\n";
        $ret_xml .= '<sword:treatment>Deposited items(zip) will be treated as WEKO import file which contains any WEKO contents information, and will be imported to WEKO.</sword:treatment>'."\n";
        $ret_xml .= '<sword:formatNamespace>WEKO</sword:formatNamespace>'."\n";
        $ret_xml .= '<sword:userAgent>SWORD Client for WEKO V2.0</sword:userAgent>'."\n";
        $ret_xml .= '</entry>';

        print $ret_xml;
    }

    // Add suppleContentsEntry Y.Yamazawa --start-- 2015/03/24 --start--
    /**
     * Emailアドレスの取得
     *
     * @param ユーザーID $user_id string
     * @param メールアドレス $email string
     * @return boolean 取得結果
     */
    private function emailAddress($user_id, &$email)
    {
        // init
        $email = "";
        // SQL query for get email address from user_id
        $query = "SELECT links.content ".
                 "FROM ". DATABASE_PREFIX ."items AS items, ".
                          DATABASE_PREFIX ."users_items_link AS links ".
                 "WHERE items.type = 'email' ".
                 "  AND items.item_id = links.item_id ".
                 "  AND links.user_id = ?; ";
        // get login user's email address
        $params = array();
        $params = $user_id;
        $result = $this->Db->execute( $query, $params );
        if($result === false){
        // not user?
            return false;
        }
        if(count($result) == 1){
        // get email address
            $email = $result[0]["content"];
        }

        return true;
    }
        // Add suppleContentsEntry Y.Yamazawa --end-- 2015/03/24 --end--

    /**
     * アイテムIDをキーとしてサプリコンテンツ情報を取得する
     * @param string $itemID アイテムID
     * @throws AppException
     * @return boolean|mixed
     */
    private function suppleContentInfo($itemID)
    {
        $query = "SELECT uri FROM ".DATABASE_PREFIX."repository_supple ".
                "WHERE item_id = ? " .
                "AND is_delete = 0 ";
        $params = array();
        $params[] = $itemID;	// item_id
        $result = $this->Db->execute($query,$params);
        if($result === false){
            return false;
        }

        return $result;
    }
    
    /**
     * get OS's encode
     *
     * @return string
     */
    private function getEncodeByOS(){
        if(stristr(php_uname(), "Linux")){
            return "UTF-8";
        } else if(stristr(php_uname(), "Windows")){
            return "SJIS";
        } else {
            return _CHARSET;
        }
    }
}
?>
