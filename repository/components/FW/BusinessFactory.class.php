<?php
/**
 * $Id: BusinessFactory.class.php 56711 2015-08-19 13:21:44Z tomohiro_ichikawa $
 * 
 * ビジネスロジックインスタンス生成抽象クラス
 * 
 * @author IVIS
 */
 class BusinessFactory
{
    private $Session = null;
    private $Db = null;
    private $accessDate = null;
    
    private $instanceList = array();
    
    protected static $instance = null;
    
    protected function __construct($_session, $_db, $_accessDate)
    {
        $this->Session = $_session;
        $this->Db = $_db;
        $this->accessDate = $_accessDate;
    }
    
    /**
     * 
     * 
     * @return Ambigous <NULL, BusinessFactory>
     */
    public static function getFactory()
    {
        return self::$instance;
    }
    
    /**
     * 初期化
     * 
     * @param Session $session
     * @param Db $db
     * @param string $accessDate
     * @static
     */
    public static function initialize($_session, $_db, $_accessDate)
    {
        if(!is_null(self::$instance))
        {
            return;
        }
        
        self::$instance = new BusinessFactory($_session, $_db, $_accessDate);
    }
    
    /**
     * 終了処理
     * 
     * @static
     */
    public static function uninitialize()
    {
        if(isset(self::$instance)) {
            for($ii = 0; $ii < count(self::$instance->instanceList); $ii++) {
                self::$instance->instanceList[$ii]->finalizeBusiness();
            }
        }
        self::$instance = null;
    }
    
    /**
     * ビジネスロジックインスタンス取得
     *
     * @param string $businessName
     * @return object
     */
    public function getBusiness($businessName)
    {
        $container =& DIContainerFactory::getContainer();
        $instance =& $container->getComponent($businessName);
        
        if(method_exists($instance, "initializeBusiness"))
        {
            // セッションからユーザーのログイン情報を取得
            $user_id = $this->Session->getParameter("_user_id");
            $handle = $this->Session->getParameter("_handle");
            $auth_id = $this->Session->getParameter("_role_auth_id");
            $instance->initializeBusiness($this->Db, $this->accessDate, $user_id, $handle, $auth_id);
            $this->instanceList[] =& $instance;
        }
        
        return $instance;
    }
}
?>
