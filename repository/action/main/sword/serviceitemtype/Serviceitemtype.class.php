<?php
// --------------------------------------------------------------------
//
// $Id:Servicedocument.class.php 4173 2008-10-31 08:35:00Z nakao $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/Factory.class.php';

/**
 * Generate the Service Document for SWORD.
 */
class Repository_Action_Main_Sword_Serviceitemtype extends RepositoryAction
{
    
    function executeForWeko()
    {
        $swordManager = Repository_Components_Factory::getComponent('Repository_Components_Swordmanager');
        
        // XML文字列作成
        $xml = "";
        $swordManager->createItemtypeXml($xml);
        
        // 出力
        header("Content-Type: text/xml; charset=utf-8");
        echo $xml;
        
        //アクション終了処理
        $result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
        
        // 終了
        exit();
    }
    
}

?>
