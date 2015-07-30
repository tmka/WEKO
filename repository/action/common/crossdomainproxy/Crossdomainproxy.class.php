<?php
// --------------------------------------------------------------------
//
// $Id: Crossdomainproxy.class.php 28536 2013-11-21 08:46:23Z shota_suzuki $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/**
 * Cross Domain Proxy action
 */
 
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';

class Repository_Action_Common_Crossdomainproxy extends RepositoryAction
{
    function executeForWeko() {
        $url = html_entity_decode($_GET["ajaxRequest"]);
        
        $option = array("timeout" => 10, 
                        "allowRedirects" => "true", 
                        "maxRedirects" => 3);
        
        $request = new HTTP_Request($url, $option);
        $request->addHeader("User-Agent", $_SERVER['HTTP_USER_AGENT']);
        
        $response = $request->sendRequest(); 
        
        if (!PEAR::isError($response)) { 
            echo $request->getResponseBody();
        } else {
            echo $request->getResponseCode();
        }
    }
}
?>
