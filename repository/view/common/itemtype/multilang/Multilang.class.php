<?php
// --------------------------------------------------------------------
//
// $Id: Multilang.class.php 22759 2013-05-21 04:47:11Z koji_matsuo $
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
class Repository_View_Common_Itemtype_Multilang extends RepositoryAction
{
    // 編集している項目のID
    public $edit_id = null;
    // 親ウィンドウのID
    public $sel_node_pid = null;
    // デフォルトを編集できるかのフラグ
    public $default_edit = null;

    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
        return "success";
    }
}
?>
