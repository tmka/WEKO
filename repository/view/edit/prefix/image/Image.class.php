<?php
// --------------------------------------------------------------------
//
// $Id: Image.class.php 1153 2010-08-06 08:03:49Z atsushi_suzuki $
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

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_View_Edit_Prefix_Image extends RepositoryAction
{
	var $nowtime = null;
	
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
    	$DATE = new Date();
    	$this->nowtime = $DATE->getDate(DATE_FORMAT_TIMESTAMP);
    	return 'success';
    }
}
?>
