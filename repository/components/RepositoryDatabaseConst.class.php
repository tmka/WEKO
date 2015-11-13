<?php
// --------------------------------------------------------------------
//
// $Id: RepositoryDatabaseConst.class.php 58145 2015-09-28 04:23:40Z keiya_sugimoto $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/**
 * Repository module constant class
 *
 * @package repository
 * @access  public
 */
class RepositoryDatabaseConst
{
    /**
      * repository_cover_delete_status
      */
    // status
    const COVER_DELETE_STATUS_NONE = null;
    const COVER_DELETE_STATUS_NOTYET = "0";
    const COVER_DELETE_STATUS_DONE = "1";

    /**
      * repository_robotlist_data_status
      */
    // status
    const ROBOTLIST_DATA_STATUS_DISABLED = "-1";
    const ROBOTLIST_DATA_STATUS_NOTDELETED = "0";
    const ROBOTLIST_DATA_STATUS_DELETED = "1";

    /**
      * repository_robotlist_master_is_robotlist_use
      */
    // status
    const ROBOTLIST_MASTER_NOTUSED = 0;
    const ROBOTLIST_MASTER_USED = 1;
}
?>
