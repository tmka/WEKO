<?php
// --------------------------------------------------------------------
//
// $Id: QueryGeneratorInterFace.class.php 40582 2014-08-28 02:04:16Z tomohiro_ichikawa $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

interface Repository_Components_Querygeneratorinterface
{
    /**
     * create detail search query
     *
     * @param RepositorySearchQueryParameter
     * @param string
     * @param array
     * 
     */
    public function createDetailSearchQuery($searchInfo, &$searchQuery, &$searchQueryParameter);
}
?>
