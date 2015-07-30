<?php
// --------------------------------------------------------------------
//
// $Id: FormatAbstract.class.php 31621 2014-02-13 02:06:16Z tomohiro_ichikawa $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryOutputFilter.class.php';

class Repository_Oaipmh_FormatAbstract
{
    const LF = "\n";
    
    const DATA_FILTER_SIMPLE = "simple";
    const DATA_FILTER_DETAIL = "detail";
    
    /**
     * Session object
     *
     * @var object
     */
    protected $Session = null;
    
    /**
     * Database object
     *
     * @var object
     */
    protected $Db = null;
    protected $dbAccess = null;
    
    /**
     * repository action class object
     *
     * @var onject
     */
    protected $RepositoryAction = null;
    
    /**
     * output data filter
     * 
     * detail : output all metadata
     * simple : output list_view_enable metadata
     */
    protected $dataFilter = self::DATA_FILTER_DETAIL;
    
    /**
     * Const
     *
     * @param object $sesssion
     * @param object $db
     * @return Repository_Oaipmh_LearningObjectMetadata
     */
    public function Repository_Oaipmh_FormatAbstract($session, $db)
    {
        if(isset($session) && $session!=null)
        {
            $this->Session = $session;
        }
        else
        {
            return null;
        }
        
        // set database object
        if(isset($db) && $db!=null)
        {
            $this->Db = $db;
        }
        else
        {
            return null;
        }
        
        // set Repository Action class
        $this->RepositoryAction = new RepositoryAction();
        $this->RepositoryAction->Session = $this->Session;
        $this->RepositoryAction->Db = $this->Db;
        $this->dbAccess = new RepositoryDbAccess($this->Db);
        $this->RepositoryAction->dbAccess = $this->dbAccess;
        
        // individual initialize
        $this->initialize();
    }
    
    /**
     * individual initialize
     */
    private function initialize()
    {
    }
    
    public function setDataFilter($filter)
    {
        if($filter == self::DATA_FILTER_SIMPLE)
        {
            $this->dataFilter = $filter;
        }
    }
    
    /**
     * output OAI-PMH metadata Tag format xxx
     *
     * @param array $itemData $this->getItemData return
     * @return string xml
     */
    public function outputRecord($itemData)
    {
        $xml = '';
        return $xml;
    }
}

?>