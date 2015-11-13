<?php
require_once WEBAPP_DIR. '/modules/repository/components/FW/BusinessBase.class.php';

/**
 * $Id: Itemedittranscheck.class.php 54767 2015-06-24 04:30:31Z tomohiro_ichikawa $
 * 
 * アイテム編集画面遷移チェックビジネスクラス
 * 
 * @author IVIS
 */
class Repository_Components_Business_Itemedittranscheck extends BusinessBase
{
    // 遷移先定義文字列
    const DISTINATION_ERROR         = "error";
    const DISTINATION_SELECTTYPE    = "selecttype";
    const DISTINATION_FILES         = "files";
    const DISTINATION_LICENSE       = "license";
    const DISTINATION_TEXTS         = "texts";
    const DISTINATION_LINKS         = "links";
    const DISTINATION_DOI           = "doi";
    const DISTINATION_CONFIRM       = "confirm";
    const DISTINATION_REDIRECT      = "redirect";
    const DISTINATION_STAY          = "stay";
    const DISTINATION_NEXT          = "next";
    
    // メンバ変数
    private $caller_ = "";
    private $target_ = "";
    private $isFile_ = 0;
    private $doiItemTypeFlag_ = false;
    private $itemId_ = 0;
    private $itemNo_ = 0;
    private $baseAttr_ = array();
    private $itemPubDate_ = array();
    private $itemAttrType_ = array();
    private $itemAttr_ = array();
    private $itemNumAttr_ = array();
    private $index_ = array();
    private $orgTarget = "";
    
    // チェック結果
    private $checkFile_ = null;
    private $checkFileInput_ = null;
    private $checkFileLicense_ = null;
    private $checkBaseInfo_ = null;
    private $checkRequired_ = null;
    private $checkIndex_ = null;
    private $checkDoi_ = null;
    
    // メッセージ
    private $errMsg_ = array();
    private $warningMsg_ = array();
    
    /**
     * アイテム情報設定
     * 
     * @param string $caller 呼び出し元
     * @param string $target 希望遷移先
     * @param int $isFile ファイルフラグ
     * @param bool $doiItemtypeFlag DOIフラグ
     * @param array $baseAttr アイテム基本情報
     * @param array $itemPubDate アイテム公開日
     * @param array $itemAttrType アイテム属性タイプ情報
     * @param array $itemAttr アイテム属性情報
     * @param array $itemNumAttr アイテム属性数情報
     * @param array $index 所属インデックス情報
     * @param int $editItemId アイテムID
     * @param int $editItemNo アイテムNo
     */
    public function setData($caller, $target, $isFile, $doiItemtypeFlag, $baseAttr, $itemPubDate,
                            $itemAttrType, $itemAttr, $itemNumAttr, $index, $editItemId=0, $editItemNo=0)
    {
        $this->caller_ = $caller;
        $this->isFile_ = intval($isFile);
        $this->doiItemTypeFlag_ = $doiItemtypeFlag;
        $this->itemId_ = $editItemId;
        $this->itemNo_ = $editItemNo;
        $this->baseAttr_ = $baseAttr;
        $this->itemPubDate_ = $itemPubDate;
        $this->itemAttrType_ = $itemAttrType;
        $this->itemAttr_ = $itemAttr;
        $this->itemNumAttr_ = $itemNumAttr;
        $this->index_ = $index;
        $this->orgTarget = $target;
        $this->setTarget($caller, $target);
    }

    /**
     * 遷移先取得
     * 
     * @return string
     */
    public function getDestination()
    {
        return $this->judgeDestination();
    }
    
    /**
     * エラーメッセージ取得
     * @return array
     */
    public function getErrorMsg(){
        return $this->errMsg_;
    }
    
    /**
     * 警告メッセージ取得
     * @return array
     */
    public function getWarningMsg(){
        return $this->warningMsg_;
    }
    
    /**
     * 指定遷移先設定
     */
    private function setTarget($caller, $target)
    {
        if($target==self::DISTINATION_STAY){
            // 呼び出し元と同一画面を指定
            $this->target_ = $caller;
        } else if($target==self::DISTINATION_NEXT){
            // 呼び出し元の次の画面を指定
            switch($caller){
                case self::DISTINATION_CONFIRM:
                    $this->target_ = self::DISTINATION_REDIRECT;
                    break;
                case self::DISTINATION_DOI:
                    $this->target_ = self::DISTINATION_CONFIRM;
                    break;
                case self::DISTINATION_LINKS:
                    if($this->getCheckDoi()){
                        $this->target_ = self::DISTINATION_DOI;
                    } else {
                        $this->target_ = self::DISTINATION_CONFIRM;
                    }
                    break;
                case self::DISTINATION_TEXTS:
                    $this->target_ = self::DISTINATION_LINKS;
                    break;
                case self::DISTINATION_LICENSE:
                    $this->target_ = self::DISTINATION_TEXTS;
                    break;
                case self::DISTINATION_FILES:
                    $this->target_ = self::DISTINATION_LICENSE;
                    // サムネイルのみしか存在しない場合はTEXTへ
                    if($this->isFile_ == 1){
                        $this->target_ = self::DISTINATION_TEXTS;
                    }
                    break;
                case self::DISTINATION_SELECTTYPE:
                    if($this->getCheckFile()){
                        $this->target_ = self::DISTINATION_FILES;
                    } else {
                        $this->target_ = self::DISTINATION_TEXTS;
                    }
                    break;
                default:
                    $this->target_ = $caller;
                    break;
            }
        } else {
            // 指定された遷移先のまま
            $this->target_ = $target;
        }
    }
    
    /**
     * 遷移先判定
     *
     * @return string
     */
    private function judgeDestination()
    {
        $ret = self::DISTINATION_ERROR;
        switch($this->target_){
            case self::DISTINATION_REDIRECT:
            case self::DISTINATION_CONFIRM:
                $ret = $this->target_;
                // サムネイル or ファイル or 課金ファイルが必須の場合：ファイルがなければ遷移不可
                // サムネイル or ファイル or 課金ファイルが必須の場合：正しくライセンス設定されていない場合は不可
                // アイテム基本情報（タイトル、公開日）がなければ遷移不可
                // メタデータ必須の場合：該当メタデータがなければ遷移不可
                // 所属インデックスがなければ遷移不可
                if( $this->getCheckFileInput() && $this->getCheckFileLicense() && $this->getCheckBaseInfo() &&
                    $this->getCheckRequired() && $this->getCheckIndex())
                {
                    break;
                }
            case self::DISTINATION_DOI:
                $ret = self::DISTINATION_DOI;
                // サムネイル or ファイル or 課金ファイルが必須の場合：ファイルがなければ遷移不可
                // サムネイル or ファイル or 課金ファイルが必須の場合：正しくライセンス設定されていない場合は不可
                // アイテム基本情報（タイトル、公開日）がなければ遷移不可
                // メタデータ必須の場合：該当メタデータがなければ遷移不可
                // 所属インデックスがなければ遷移不可
                // DOIフラグがない場合は遷移不可
                if( $this->getCheckFileInput() && $this->getCheckFileLicense() && $this->getCheckBaseInfo() &&
                    $this->getCheckRequired() && $this->getCheckIndex() && $this->getCheckDoi())
                {
                    break;
                }
            case self::DISTINATION_LINKS:
                $ret = self::DISTINATION_LINKS;
                // サムネイル or ファイル or 課金ファイルが必須の場合：ファイルがなければ遷移不可
                // サムネイル or ファイル or 課金ファイルが必須の場合：正しくライセンス設定されていない場合は不可
                // アイテム基本情報（タイトル、公開日）がなければ遷移不可
                // メタデータ必須の場合：該当メタデータがなければ遷移不可
                if( $this->getCheckFileInput() && $this->getCheckFileLicense() && $this->getCheckBaseInfo() && $this->getCheckRequired())
                {
                    break;
                }
            case self::DISTINATION_TEXTS:
                $ret = self::DISTINATION_TEXTS;
                // サムネイル or ファイル or 課金ファイルが必須の場合：ファイルがなければ遷移不可
                // サムネイル or ファイル or 課金ファイルが必須の場合：正しくライセンス設定されていない場合は不可
                if( $this->getCheckFileInput() && $this->getCheckFileLicense())
                {
                    if($this->orgTarget == self::DISTINATION_STAY){
                        // 保存ボタン押下時にエラー/警告があれば出す
                        $this->getCheckBaseInfo();
                    }
                    break;
                }
            case self::DISTINATION_LICENSE:
                $ret = self::DISTINATION_LICENSE;
                // サムネイル or ファイル or 課金ファイルのメタデータ項目がなければ遷移不可
                // サムネイル or ファイル or 課金ファイルが必須の場合：ファイルがなければ遷移不可
                if( $this->getCheckFile() && $this->getCheckFileInput())
                {
                    break;
                }
            case self::DISTINATION_FILES:
                $ret = self::DISTINATION_FILES;
                // サムネイル or ファイル or 課金ファイルのメタデータ項目がなければ遷移不可
                if( $this->getCheckFile())
                {
                    break;
                }
            case self::DISTINATION_SELECTTYPE:
                $ret = self::DISTINATION_SELECTTYPE;
                // 条件なし
                break;
            default:
                $ret = self::DISTINATION_ERROR;
                break;
        }
        return $ret;
    }
    
    /**
     * 初期化処理
     * @see BusinessBase::onInitialize()
     */
    protected function onInitialize(){
        $this->initCheckFlag();
    }
    
    /**
     * 必須条件チェックフラグ初期化
     */
    private function initCheckFlag(){
        $this->checkFile_ = null;
        $this->checkFileInput_ = null;
        $this->checkBaseInfo_ = null;
        $this->checkRequired_ = null;
        $this->checkDoi_ = null;
        $this->checkIndex_ = null;
        
        // メッセージ初期化
        $this->errMsg_ = array();
        $this->warningMsg_ = array();
    }
    
    /**
     * 必須条件判定結果取得：サムネイル or ファイル or 課金ファイルのメタデータ項目
     * @return bool
     */
    private function getCheckFile(){
        if(!isset($this->checkFile_)){
            $ret = false;
            if($this->isFile_ > 0){
                // サムネイル or ファイル or 課金ファイルのメタデータ項目が存在する
                $ret = true;
            }
            $this->checkFile_ = $ret;
            $this->debugLog("checkFile_: ".var_export($ret, true), __FILE__, __CLASS__, __LINE__);
        }
        return $this->checkFile_;
    }
    
    /**
     * 必須条件判定結果取得：サムネイル or ファイル or 課金ファイルの入力
     * @return bool
     */
    private function getCheckFileInput(){
        if(!isset($this->checkFileInput_)){
            $ret = false;
            if($this->getCheckFile()){
                // ItemRegister のチェック関数を使用
                require_once WEBAPP_DIR. '/modules/repository/components/ItemRegister.class.php';
                $this->debugLog("Get session", __FILE__, __CLASS__, __LINE__);
                $container = & DIContainerFactory::getContainer();
                $session = $container->getComponent("Session");
                $itemRegister = new ItemRegister($session, $this->Db);
                $itemRegister->setEditStartDate($this->accessDate);
                $tmpErrMsg = array();
                $tmpWarningMsg = array();
                $itemRegister->checkEntryInfo($this->itemAttrType_, $this->itemNumAttr_, $this->itemAttr_, "file", $tmpErrMsg, $tmpWarningMsg);
                $this->errMsg_ = array_merge($this->errMsg_, $tmpErrMsg);
                $this->warningMsg_ = array_merge($this->warningMsg_, $tmpWarningMsg);
                if(count($tmpErrMsg) == 0)
                {
                    $ret = true;
                }
            } else {
                // サムネイル or ファイル or 課金ファイルのメタデータ項目が存在しない場合はチェックOKとする
                $ret = true;
            }
            $this->checkFileInput_ = $ret;
            $this->debugLog("checkFileInput_: ".var_export($ret, true), __FILE__, __CLASS__, __LINE__);
        }
        return $this->checkFileInput_;
    }
    
    /**
     * 必須条件判定結果取得：ファイルライセンスの入力
     * @return bool
     */
    private function getCheckFileLicense(){
        if(!isset($this->checkFileLicense_)){
            $ret = false;
            if($this->getCheckFile()){
                // ItemRegister のチェック関数を使用
                require_once WEBAPP_DIR. '/modules/repository/components/ItemRegister.class.php';
                $this->debugLog("Get session", __FILE__, __CLASS__, __LINE__);
                $container = & DIContainerFactory::getContainer();
                $session = $container->getComponent("Session");
                $itemRegister = new ItemRegister($session, $this->Db);
                $itemRegister->setEditStartDate($this->accessDate);
                $tmpErrMsg = array();
                $tmpWarningMsg = array();
                $itemRegister->checkEntryInfo($this->itemAttrType_, $this->itemNumAttr_, $this->itemAttr_, "license", $tmpErrMsg, $tmpWarningMsg);
                $this->errMsg_ = array_merge($this->errMsg_, $tmpErrMsg);
                $this->warningMsg_ = array_merge($this->warningMsg_, $tmpWarningMsg);
                if(count($tmpErrMsg) == 0)
                {
                    $ret = true;
                }
            } else {
                // サムネイル or ファイル or 課金ファイルのメタデータ項目が存在しない場合はチェックOKとする
                $ret = true;
            }
            $this->checkFileLicense_ = $ret;
            $this->debugLog("checkFileLicense_: ".var_export($ret, true), __FILE__, __CLASS__, __LINE__);
        }
        return $this->checkFileLicense_;
    }
    
    /**
     * 必須条件判定結果取得：アイテム基本情報（タイトル、公開日）の入力
     * @return bool
     */
    private function getCheckBaseInfo(){
        if(!isset($this->checkBaseInfo_)){
            $ret = false;
            
            // ItemRegister のチェック関数を使用
            require_once WEBAPP_DIR. '/modules/repository/components/ItemRegister.class.php';
            $this->debugLog("Get session", __FILE__, __CLASS__, __LINE__);
            $container = & DIContainerFactory::getContainer();
            $session = $container->getComponent("Session");
            $itemRegister = new ItemRegister($session, $this->Db);
            $itemRegister->setEditStartDate($this->accessDate);
            $baseInfo = array(  "item_id" => $this->itemId_,
                                "item_no" => $this->itemNo_,
                                "title" => $this->baseAttr_['title'],
                                "title_english" => $this->baseAttr_['title_english'],
                                "language" => $this->baseAttr_['language'],
                                "pub_year" => $this->itemPubDate_['year'],
                                "pub_month" => $this->itemPubDate_['month'],
                                "pub_day" => $this->itemPubDate_['day']);
            $tmpErrMsg = array();
            $tmpWarningMsg = array();
            $itemRegister->checkBaseInfo($baseInfo, $tmpErrMsg, $tmpWarningMsg);
            $this->errMsg_ = array_merge($this->errMsg_, $tmpErrMsg);
            $this->warningMsg_ = array_merge($this->warningMsg_, $tmpWarningMsg);
            if(count($tmpErrMsg) == 0)
            {
                $ret = true;
            }
            $this->checkBaseInfo_ = $ret;
            $this->debugLog("checkBaseInfo_: ".var_export($ret, true), __FILE__, __CLASS__, __LINE__);
        }
        return $this->checkBaseInfo_;
    }
    
    /**
     * 必須条件判定結果取得：必須メタデータの入力
     * @return bool
     */
    private function getCheckRequired(){
        if(!isset($this->checkRequired_)){
            $ret = false;
            
            // ItemRegister のチェック関数を使用
            require_once WEBAPP_DIR. '/modules/repository/components/ItemRegister.class.php';
            $this->debugLog("Get session", __FILE__, __CLASS__, __LINE__);
            $container = & DIContainerFactory::getContainer();
            $session = $container->getComponent("Session");
            $itemRegister = new ItemRegister($session, $this->Db);
            $itemRegister->setEditStartDate($this->accessDate);
            $tmpErrMsg = array();
            $tmpWarningMsg = array();
            $itemRegister->checkEntryInfo($this->itemAttrType_, $this->itemNumAttr_, $this->itemAttr_, "meta", $tmpErrMsg, $tmpWarningMsg);
            $this->errMsg_ = array_merge($this->errMsg_, $tmpErrMsg);
            $this->warningMsg_ = array_merge($this->warningMsg_, $tmpWarningMsg);
            if(count($tmpErrMsg) == 0)
            {
                $ret = true;
            }
            $this->checkRequired_ = $ret;
            $this->debugLog("checkRequired_: ".var_export($ret, true), __FILE__, __CLASS__, __LINE__);
        }
        return $this->checkRequired_;
    }
    
    /**
     * 必須条件判定結果取得：DOI設定の有効化
     * @return bool
     */
    private function getCheckDoi(){
        if(!isset($this->checkDoi_)){
            // DOIアイテムタイプフラグで一次チェック
            $ret = false;
            if($this->doiItemTypeFlag_ === true){
                // Checkdoi クラスを使用
                require_once WEBAPP_DIR. '/modules/repository/components/Checkdoi.class.php';
                // check this item can be granted doi
                $container = & DIContainerFactory::getContainer();
                $session = $container->getComponent("Session");
                $CheckDoi = new Repository_Components_Checkdoi($session, $this->Db, $this->accessDate);
                $displays_jalcdoi_flag = $CheckDoi->checkDoiGrant($this->itemId_, $this->itemNo_, Repository_Components_Checkdoi::TYPE_JALC_DOI);
                $displays_crossref_flag = $CheckDoi->checkDoiGrant($this->itemId_, $this->itemNo_, Repository_Components_Checkdoi::TYPE_CROSS_REF);
                $displays_library_jalcdoi_flag = $CheckDoi->checkDoiGrant($this->itemId_, $this->itemNo_, Repository_Components_Checkdoi::TYPE_LIBRARY_JALC_DOI);
                $displays_datacite_flag = $CheckDoi->checkDoiGrant($this->itemId_, $this->itemNo_, Repository_Components_Checkdoi::TYPE_DATACITE);
                // check this item was already granted doi or not
                $doi_status = $CheckDoi->getDoiStatus($this->itemId_, $this->itemNo_);
                if($displays_jalcdoi_flag || $displays_crossref_flag || $displays_library_jalcdoi_flag || $displays_datacite_flag || $doi_status >= 1)
                {
                    $ret = true;
                } else {
                    if($this->orgTarget !== self::DISTINATION_NEXT){
                        $smarty_assign = $session->getParameter("smartyAssign");
                        $this->errMsg_ = array_merge($this->errMsg_, array($smarty_assign->getLang("repository_item_enterdoi_error")));
                    }
                }
            }
            $this->checkDoi_ = $ret;
            $this->debugLog("checkDoi_: ".var_export($ret, true), __FILE__, __CLASS__, __LINE__);
        }
        return $this->checkDoi_;
    }
    
    /**
     * 必須条件判定結果取得：所属インデックスの入力
     * @return bool
     */
    private function getCheckIndex(){
        if(!isset($this->checkIndex_)){
            $ret = false;
            
            // ItemRegister のチェック関数を使用
            require_once WEBAPP_DIR. '/modules/repository/components/ItemRegister.class.php';
            $this->debugLog("Get session", __FILE__, __CLASS__, __LINE__);
            $container = & DIContainerFactory::getContainer();
            $session = $container->getComponent("Session");
            $itemRegister = new ItemRegister($session, $this->Db);
            $itemRegister->setEditStartDate($this->accessDate);
            $tmpErrMsg = array();
            $tmpWarningMsg = array();
            $itemRegister->checkIndex($this->index_, $tmpErrMsg, $tmpWarningMsg);
            $this->errMsg_ = array_merge($this->errMsg_, $tmpErrMsg);
            $this->warningMsg_ = array_merge($this->warningMsg_, $tmpWarningMsg);
            if(count($tmpErrMsg) == 0)
            {
                $ret = true;
            }
            $this->checkIndex_ = $ret;
            $this->debugLog("checkIndex_: ".var_export($ret, true), __FILE__, __CLASS__, __LINE__);
        }
        return $this->checkIndex_;
    }
}
?>