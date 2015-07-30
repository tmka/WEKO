<?php
// --------------------------------------------------------------------
//
// $Id: Treethumbnail.class.php 1441 2010-09-14 06:19:56Z yuko_nakao $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_Action_Edit_Treethumbnail
{
    var $Session = null;
    var $Db = null;
    var $uploadsAction = null;
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
        $garbage_flag = 1;
        
        // get upload file data
        $filelist = $this->uploadsAction->uploads($garbage_flag);
        if(count($filelist) > 0){
            if ($filelist[0]['upload_id'] === 0) {
                // upload file none
                return true;
            } else if(strpos($filelist[0]["mimetype"],"image") === false){
                // not image file
                return false;
            }
            // set to Session uoload image file
            $this->Session->setParameter("tree_thumbnail", $filelist[0]);
        }
        
        // upload file none
        return true;
    }
}
?>
