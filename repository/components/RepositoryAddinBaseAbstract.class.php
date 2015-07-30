<?php
// --------------------------------------------------------------------
//
// $Id: RepositoryAddinBaseAbstract.class.php 20287 2012-11-20 04:59:31Z yuko_nakao $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

class RepositoryAddinBaseAbstract
{
    
    /**
     * invoker class
     *
     * @var object
     */
    protected $invoker = null;
    
    /**
     * setter for invoker_
     *
     * @param object $value
     */
    public function setInvoker($value)
    {
        // invoker_ setting once.
        if(isset($value) && !isset($this->invoker))
        {
            // set invoker_
            $this->invoker = $value;
            
            // check Session components.
            if(!isset($this->invoker->Session))
            {
                // When nothing Session, setting Session components.
                $container =& DIContainerFactory::getContainer();
                $this->invoker->Session =& $container->getComponent("Session");
            }
            
            if(!isset($this->invoker->Db))
            {
                // When nothing Db, setting Db components.
                $container =& DIContainerFactory::getContainer();
                $this->invoker->Db =& $container->getComponent("DbObject");
            }
        }
    }
    
    /**
     * preExecute
     *
     */
    public function preExecute()
    {
        
    }
    
    /**
     * postExecute
     *
     */
    public function postExecute()
    {
        
    }
    
}
?>
