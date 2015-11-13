<?php
require_once WEBAPP_DIR.'/modules/repository/components/FW/ActionBase.class.php';
/**
 * $Id: WekoAction.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
 * 
 * WEKO向けアクション基底クラス
 * 
 * @author IVIS
 */
abstract class WekoAction extends ActionBase
{
    public $block_id = null;
    public $repository_admin_base;
    public $repository_admin_room;
    public $wekoThemeName = 'default';
    protected $smartphoneFlg = false;
    
    /**
     * 処理開始操作ログ番号
     * @var int
     */
    private $startLogId = 0;
    
    /**
     * トランザクション外前処理
     * @see ActionBase::beforeTrans()
     */
    final protected function beforeTrans(){
        try {
            // 操作ログ記録
            $userId = $this->Session->getParameter("_user_id");
            $request = BusinessFactory::getFactory()->getBusiness("Request");
            $requestPrams = $request->getStrParameters();
            $businessOperationlog = BusinessFactory::getFactory()->getBusiness("businessOperationlog");
            $this->startLogId = $businessOperationlog->startLog($userId, $requestPrams);
        } catch (Exception $e){}
    }
    
    /**
     * トランザクション外後処理
     * @see ActionBase::afterTrans()
     */
    final protected function afterTrans(){
        try {
            // 操作ログ記録
            $userId = $this->Session->getParameter("_user_id");
            $request = BusinessFactory::getFactory()->getBusiness("Request");
            $requestPrams = $request->getStrParameters();
            $businessOperationlog = BusinessFactory::getFactory()->getBusiness("businessOperationlog");
            $businessOperationlog->endLog($this->startLogId, $userId, $requestPrams);
        } catch (Exception $e){}
    }
    
    /**
     * トランザクション内前処理
     * @see ActionBase::preExecute()
     */
    final protected function preExecute(){
        $this->setConfigAuthority();
        $this->setThemeName();
        
        require_once WEBAPP_DIR. '/modules/repository/components/RepositoryIndexManager.class.php';
        $repositoryIndexManager = new RepositoryIndexManager($this->Session, $this->Db, $this->accessDate);
        $repositoryIndexManager->createPrivateTree();
        
        if(!defined("SHIB_ENABLED")){
            define("SHIB_ENABLED", 0);
        }
        $this->shib_login_flg = SHIB_ENABLED;
        
        if(defined("_REPOSITORY_CINII"))
        {
            $this->Session->setParameter("_repository_cinii", _REPOSITORY_CINII);
        }
        
        if(isset($_SERVER['HTTP_USER_AGENT']))
        {
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
            if(preg_match('/Android..*Mobile|iPhone|IEMobile/', $userAgent) > 0){
                $this->smartphoneFlg = true;
            }
        }
        if(_REPOSITORY_SMART_PHONE_DISPLAY)
        {
            $this->smartphoneFlg = true;
        }
    }
    
    /**
     * トランザクション内後処理
     * @see ActionBase::postExecute()
     */
    final protected function postExecute(){}
    
    private function setConfigAuthority(){
        // set authority level from config file
        $config = parse_ini_file(BASE_DIR.'/webapp/modules/repository/config/main.ini');
        if( isset($config["define:_REPOSITORY_BASE_AUTH"]) &&
            strlen($config["define:_REPOSITORY_BASE_AUTH"]) > 0 &&
            is_numeric($config["define:_REPOSITORY_BASE_AUTH"])){
                $this->repository_admin_base = intval($config["define:_REPOSITORY_BASE_AUTH"]);
        } else {
            $this->repository_admin_base = _AUTH_CHIEF;
        }
        
        if( isset($config["define:_REPOSITORY_ROOM_AUTH"]) &&
            strlen($config["define:_REPOSITORY_ROOM_AUTH"]) > 0 &&
            is_numeric($config["define:_REPOSITORY_ROOM_AUTH"])){
                $this->repository_admin_room = $config["define:_REPOSITORY_ROOM_AUTH"];
        } else {
            $this->repository_admin_room = _AUTH_CHIEF;
        }
        
        // check authority level
        if($this->repository_admin_base < _AUTH_CHIEF){
            $this->repository_admin_base = _AUTH_CHIEF;
        } else if(_AUTH_ADMIN < $this->repository_admin_base){
            $this->repository_admin_base = _AUTH_ADMIN;
        }
        
        if($this->repository_admin_room < _AUTH_GUEST){
            $this->repository_admin_room = _AUTH_GUEST;
        } else if(_AUTH_CHIEF < $this->repository_admin_base){
            $this->repository_admin_room = _AUTH_CHIEF;
        }
    }
    
    private function setThemeName(){
        $getdata = BusinessFactory::getFactory()->getBusiness("GetData");
        $blocks =& $getdata->getParameter("blocks");
        
        // when weko module uninstall, $blocks==false.
        if(!isset($blocks) || !is_array($blocks) || !$blocks[$this->block_id])
        {
            $this->wekoThemeName = 'default';
            return;
        }
        $block_obj = $blocks[$this->block_id];
        $themeName = $block_obj['theme_name'];
        if(strlen($themeName) == 0){
            $pages =& $getdata->getParameter("pages");
            $themeList = $this->Session->getParameter("_theme_list");
            $themeName = "default";
            if(isset($pages[$block_obj['page_id']]) && isset($themeList[$pages[$block_obj['page_id']]['display_position']])){
                $themeName = $themeList[$pages[$block_obj['page_id']]['display_position']];
            }
        }
        if(is_numeric(strpos($themeName, 'blue'))){
            $this->wekoThemeName = 'blue';
        } else if(is_numeric(strpos($themeName, 'green'))){
            $this->wekoThemeName = 'green';
        } else if(is_numeric(strpos($themeName, 'orange'))){
            $this->wekoThemeName = 'orange';
        } else if(is_numeric(strpos($themeName, 'orange2'))){
            $this->wekoThemeName = 'orange';
        } else if(is_numeric(strpos($themeName, 'red'))){
            $this->wekoThemeName = 'red';
        } else if(is_numeric(strpos($themeName, 'red2'))){
            $this->wekoThemeName = 'red';
        } else if(is_numeric(strpos($themeName, 'pink'))){
            $this->wekoThemeName = 'pink';
        } else if(is_numeric(strpos($themeName, 'pink2'))){
            $this->wekoThemeName = 'pink';
        } else {
            $this->wekoThemeName = 'default';
        }
    }
}
?>