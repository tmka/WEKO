<?php
// --------------------------------------------------------------------
//
// $Id: RepositoryProcessUtility.class.php 30197 2013-12-19 09:55:45Z rei_matsuura $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryProcessUtility.class.php';

class RepositoryProcessUtility
{
    
    /**
     * Call another process by async
     *
     * @paarm nextRequest I Next, the request to perform 
     */
    public static function callAsyncProcess($nextRequest)
    {
        // Call oneself by async
        $host = array();
        preg_match("/^https?:\/\/(([^\/]+)).*$/", BASE_URL, $host);
        $hostName = $host[1];
        if($hostName == "localhost"){
            $hostName = gethostbyname($_SERVER['SERVER_NAME']);
        }
        if($_SERVER["SERVER_PORT"] == 443)
        {
            $hostName = "ssl://".$hostName;
        }
        $handle = fsockopen($hostName, $_SERVER["SERVER_PORT"], $errNo, $errMsg, 10);
        if (!$handle)
        {
            return false;
        }
        stream_set_blocking($handle, false);
        fwrite($handle, "GET ".$nextRequest." HTTP/1.0\r\n\r\n");
        fclose ($handle);
        
        return true;
    }
    
}

?>
