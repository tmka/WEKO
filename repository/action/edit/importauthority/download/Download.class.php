<?php
// --------------------------------------------------------------------
//
// $Id: Download.class.php 57144 2015-08-26 06:55:05Z tatsuya_koyasu $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

include_once MAPLE_DIR.'/includes/pear/File/Archive.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryDownload.class.php';

class Repository_Action_Edit_Importauthority_Download extends RepositoryAction
{
    /**
     * 作業用ディレクトリ
     *
     * @var string
     */
    private $tmp_dir = null;
    
    const FILE_NAME = "templateTSV.tsv";
    
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function executeApp()
    {
        // 作業用ディレクトリ作成
        $this->infoLog("businessWorkdirectory", __FILE__, __CLASS__, __LINE__);
        $businessWorkdirectory = BusinessFactory::getFactory()->getBusiness('businessWorkdirectory');
        $this->tmp_dir = $businessWorkdirectory->create();
        $this->tmp_dir = substr($this->tmp_dir, 0, -1);
        
        $this->downloadNameAuthorityTSV();
        
        // 本来であればexitActionをよび、その中で
        // finalize処理を実施するべきだが、
        // コミットによる影響があるため、finalize処理のみを実施
        $this->finalize();
        
        exit();
    }
    
    /**
     * export NameAuthority Data
     *
     */
    private function downloadNameAuthorityTSV()
    {
        $prefixData = array();
        $nameAuthorityData = array();
        $this->getNameAuthorityData($prefixData, $nameAuthorityData);
        
        $tsvData = "";
        // write header line
        $tsvData = "名\t姓\tメールアドレス\t名(ヨミ)\t姓(ヨミ)";
        for($prefixCnt = 1; $prefixCnt < count($prefixData); $prefixCnt++)
        {
            $tsvData .= "\t".$prefixData[$prefixCnt]['prefix_name'];
        }
        $tsvData .= "\n";
        // write author data by line
        for($rowCnt = 0; $rowCnt < count($nameAuthorityData); $rowCnt++)
        {
            if($nameAuthorityData[$rowCnt]["name"] == null){
                $nameAuthorityData[$rowCnt]["name"] = "";
            }
            if($nameAuthorityData[$rowCnt]["family"] == null){
                $nameAuthorityData[$rowCnt]["family"] = "";
            }
            if($nameAuthorityData[$rowCnt]["suffix_0"] == null){
                $nameAuthorityData[$rowCnt]["suffix_0"] = "";
            }
            if($nameAuthorityData[$rowCnt]["name_ruby"] == null){
                $nameAuthorityData[$rowCnt]["name_ruby"] = "";
            }
            if($nameAuthorityData[$rowCnt]["family_ruby"] == null){
                $nameAuthorityData[$rowCnt]["family_ruby"] = "";
            }
            $tsvData .= $nameAuthorityData[$rowCnt]["name"]. "\t" .
                        $nameAuthorityData[$rowCnt]["family"]. "\t" .
                        $nameAuthorityData[$rowCnt]["suffix_0"]. "\t".
                        $nameAuthorityData[$rowCnt]["name_ruby"]. "\t" .
                        $nameAuthorityData[$rowCnt]["family_ruby"];
            for($prefixCnt = 1; $prefixCnt < count($prefixData); $prefixCnt++)
            {
                $keyName = "suffix_". $prefixData[$prefixCnt]['prefix_id'];
                $tsvData .= "\t";
                if($nameAuthorityData[$rowCnt][$keyName] != null)
                {
                    $tsvData .= $nameAuthorityData[$rowCnt][$keyName];
                }
            }
            $tsvData .= "\n";
        }
        $filepath = $this->tmp_dir. "/". self::FILE_NAME;
        // output tsv
        $fp = fopen($filepath, "w");
        fwrite($fp, $tsvData);
        fclose($fp);
        
        //ダウンロードアクション処理
        $repositoryDownload = new RepositoryDownload();
        $repositoryDownload->downloadFile($filepath, self::FILE_NAME);
        
        return true;
    }
    
    /**
     * get Name Authority Data
     *
     * @param array $prefix I/O ($prefix[N][prefix_id], $prefix[N][prefix_name])
     * @param array $authority I/O ($authority[N][author_id], ..., $authority[N][family_ruby], $authority[N][suffix_0], $authority[N][suffix_1], ...)
     */
    private function getNameAuthorityData(&$prefix, &$authority)
    {
        // get prefix id & name
        $query = "SELECT prefix_id, prefix_name ".
                 "FROM ". DATABASE_PREFIX ."repository_external_author_id_prefix ".
                 "WHERE is_delete = ? ".
                 "ORDER BY prefix_id ASC ;";
        $params = array();
        $params[] = 0;  //is_delete
        $prefix = $this->dbAccess->executeQuery($query, $params);
        
        // get name authority data
        $query = "SELECT AUTHOR.author_id, AUTHOR.name, AUTHOR.family, AUTHOR.name_ruby, AUTHOR.family_ruby";
        $params = array();
        for($cnt = 0; $cnt < count($prefix); $cnt++)
        {
            $query .= ", SUFFIX_".$prefix[$cnt]['prefix_id']. ".suffix AS suffix_". $prefix[$cnt]['prefix_id']." ";
        }
        $query .= "FROM ". DATABASE_PREFIX ."repository_name_authority AS AUTHOR ";
        for($cnt = 0; $cnt < count($prefix); $cnt++)
        {
            $query .= "LEFT JOIN (SELECT suffix, author_id FROM ".DATABASE_PREFIX ."repository_external_author_id_suffix WHERE prefix_id = ". $prefix[$cnt]['prefix_id']. " AND is_delete = ?) AS SUFFIX_". $prefix[$cnt]['prefix_id']. " ".
                      "ON AUTHOR.author_id = SUFFIX_". $prefix[$cnt]['prefix_id']. ".author_id ";
            $params[] = 0;  //is_delete
        }
        $query .= "WHERE AUTHOR.is_delete = ? ".
                  "ORDER BY AUTHOR.author_id ASC ;";
        $params[] = 0;  //is_delete
        $authority = $this->dbAccess->executeQuery($query, $params);
        
        return true;
    }
}
?>
