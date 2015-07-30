<?php
// --------------------------------------------------------------------
//
// $Id: RepositoryCheckFileTypeUtility.class.php 30910 2014-01-20 07:33:12Z rei_matsuura $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryCheckFileTypeUtility.class.php';

class RepositoryCheckFileTypeUtility
{
    
    /**
     * file is iage file or not
     *
     * @param string $mimeType
     * @param string $extension
     * @return bool
     */
    public static function isImageFile($mimeType, $extension)
    {
        if(preg_match('/^image\/([a-z]|-|\.)+$/', $mimeType) === 1 ||
           $extension == "emf" ||
           $extension == "wmf" ||
           $extension == "bmp" ||
           $extension == "png" ||
           $extension == "gif" ||
           $extension == "tiff" ||
           $extension == "jpg" ||
           $extension == "jp2") {
            return true;
        } else {
            return false;
        }
    }
    
}

?>
