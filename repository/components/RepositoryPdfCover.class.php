<?php
// --------------------------------------------------------------------
//
// $Id: RepositoryPdfCover.class.php 36229 2014-05-26 05:49:55Z satoshi_arata $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
require_once WEBAPP_DIR. '/modules/repository/files/fpdf/mc_table.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryConst.class.php';

/**
 * Repository module create PDF cover class
 *
 * @package repository
 * @access  public
 */
class RepositoryPdfCover extends PDF_MC_Table
{
    // -------------------------------------------------------
    // Member
    // -------------------------------------------------------
    /**
     * Session
     *
     * @var Session
     * @access private
     */
    private $Session = null;
    
    /**
     * Db
     *
     * @var Db
     * @access private
     */
    private $Db = null;
    
    /**
     * TransStartDate
     *
     * @var string
     * @access private
     */
    private $TransStartDate = null;
    
    /**
     * User ID
     *
     * @var string
     * @access private
     */
    private $userId = "";
    
    /**
     * RepositoryAction class instance
     *
     * @var RepositoryAction
     * @access private
     */
    private $repositoryAction = null;
    
    /**
     * Item ID
     *
     * @var int
     * @access private
     */
    private $itemId = 0;
    
    /**
     * Item No
     *
     * @var int
     * @access private
     */
    private $itemNo = 0;
    
    /**
     * Attribute ID
     *
     * @var int
     * @access private
     */
    private $attributeId = 0;
    
    /**
     * File No
     *
     * @var int
     * @access private
     */
    private $fileNo = 0;
    
    /**
     * Temporary directory path
     *
     * @var string
     * @access private
     */
    private $tmpDir = "";
    
    /**
     * PDFTK enable flag
     *
     * @var bool
     * @access private
     */
    private $enablePdftk = "";
    
    /**
     * PDFTK command path
     *
     * @var string
     * @access private
     */
    private $cmdPathPdftk = "";
    
    /**
     * ImageMagick enable flag
     *
     * @var bool
     * @access private
     */
    private $enableImageMagick = "";
    
    /**
     * ImageMagick command path
     *
     * @var string
     * @access private
     */
    private $cmdPathImageMagick = "";
    
    /**
     * Target file path
     *
     * @var string
     * @access private
     */
    private $targetFilePath = "";
    
    /**
     * Error message
     *
     * @var string
     * @access private
     */
    private $errorMsg = "";
    
    // -------------------------------------------------------
    // Const
    // -------------------------------------------------------
    const WEKO_UPLOAD_DIR = "/uploads/repository/";
    const ENCODE_TO = "SJIS";   // "SJIS" or "Unicode"
    const ENCODE_FROM = "UTF-8";
    const FONT_NAME_MSMINCHO = "MSMincho";
    const FONT_NAME_MSPMINCHO = "MSPMincho";
    const FONT_NAME_MSGOTHIC = "MSGothic";
    const FONT_NAME_MSPGOTHIC = "MSPGothic";
    // Mod pdf cover page remove line and show doi 2012/10/10 T.Koyasu -start-
    // add font for read doi on Mendeley Desktop
    const FONT_ARIAL = "arial";
    // Mod pdf cover page remove line and show doi 2012/10/10 T.Koyasu -end-
    const FONT_FAMILY_MSMINCHO = "MSMincho";
    const FONT_FAMILY_MSPMINCHO = "MSPMincho";
    const FONT_FAMILY_MSGOTHIC = "MSGothic";
    const FONT_FAMILY_MSPGOTHIC = "MSPGothic";
    const FONTSIZE_HEADER = 9;
    const FONTSIZE_FOOTER = 10;
    const FONTSIZE_TITLE = 20;
    const FONTSIZE_TITLE_SUB = 15;
    const FONTSIZE_METADATA = 14;
    // Mod pdf cover page remove line and show doi 2012/10/10 T.Koyasu -start-
    const FONTSIZE_DOI = 10;
    // Mod pdf cover page remove line and show doi 2012/10/10 T.Koyasu -end-
    const MARGIN_TOP = 35.0;
    const MARGIN_LEFT = 22.0;
    const MARGIN_RIGHT = 22.0;
    const MARGIN_TITLE_TOP = 5.0;
    const MARGIN_TITLE_UNDER = 10.0;
    const HEADER_TOP = 17.0;
    const HEADER_HEIGHT = 15.0;
    const FOOTER_TOP = -22.0;
    const FOOTER_HEIGHT = 15.0;
    const ALIGN_LEFT = 'L';
    const ALIGN_CENTER = 'C';
    const ALIGN_RIGHT = 'R';
    const ALIGN_CENTERLEFT = 'CL';
    const PDF_NAME_TARGET = "target.pdf";
    const PDF_NAME_TMP_COVER = "tmpcover.pdf";
    const PDF_NAME_COVER = "cover.pdf";
    const PDF_NAME_COMBINED = "combined.pdf";
    const PDF_NAME_ORG_TARGET = "org_target.pdf";
    const BIBLIO_JTITLE_JP = "雑誌名";
    const BIBLIO_VOLUME_JP = "巻";
    const BIBLIO_ISSUE_JP = "号";
    const BIBLIO_PAGE_JP = "ページ";
    const BIBLIO_DATEOFISSUED_JP = "発行年";
    const BIBLIO_JTITLE_EN = "journal or publication title";
    const BIBLIO_VOLUME_EN = "volume";
    const BIBLIO_ISSUE_EN = "number";
    const BIBLIO_PAGE_EN = "page range";
    const BIBLIO_DATEOFISSUED_EN = "year";
    
    // Error message
    const ERR_CANNOT_CREATE = "Could not create PDF cover page.";
    
    // -------------------------------------------------------
    // Constructor
    // -------------------------------------------------------
    /**
     * Constructor
     *
     * @param Session $Session
     * @param Db $Db
     * @param string $TransStartDate
     * @param int $itemId
     * @param int $itemNo
     * @param int $attributeId
     * @param int $fileNo
     * @return RepositoryPdfCover
     * @access public
     */
    public function RepositoryPdfCover(
        $Session, $Db, $TransStartDate, $userId="", $itemId=0, $itemNo=0, $attributeId=0, $fileNo=0)
    {
        // Set member
        $this->Session = $Session;
        $this->Db = $Db;
        $this->TransStartDate = $TransStartDate;
        $this->userId = $userId;
        $this->itemId = $itemId;
        $this->itemNo = $itemNo;
        $this->attributeId = $attributeId;
        $this->fileNo = $fileNo;
        $this->setDefaltTmpDirPath();
        
        if(strlen($this->userId) == 0)
        {
            // Get now user's ID
            $this->userId = $this->Session->getParameter("_user_id");
        }
        
        // Create RepositoryAction class instance
        $this->repositoryAction = new RepositoryAction();
        $this->repositoryAction->Session = $Session;
        $this->repositoryAction->Db = $Db;
        $this->repositoryAction->TransStartDate = $TransStartDate;
        
        // Call original constructor
        parent::FPDF();
    }
    
    // -------------------------------------------------------
    // Getter / Setter
    // -------------------------------------------------------
    /**
     * Setter for userId
     *
     * @param int $userId
     * @access public
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }
    
    /**
     * Setter for itemId
     *
     * @param int $itemId
     * @access public
     */
    public function setItemId($itemId)
    {
        $this->itemId = intval($itemId);
        
        // Reset file path
        $this->targetFilePath = "";
    }
    
    /**
     * Setter for itemNo
     *
     * @param int $itemNo
     * @access public
     */
    public function setItemNo($itemNo)
    {
        $this->itemNo = intval($itemNo);
        
        // Reset file path
        $this->targetFilePath = "";
    }
    
    /**
     * Setter for attributeId
     *
     * @param int $attributeId
     * @access public
     */
    public function setAttributeId($attributeId)
    {
        $this->attributeId = intval($attributeId);
        
        // Reset file path
        $this->targetFilePath = "";
    }
    
    /**
     * Setter for fileNo
     *
     * @param int $fileNo
     * @access public
     */
    public function setFileNo($fileNo)
    {
        $this->fileNo = intval($fileNo);
        
        // Reset file path
        $this->targetFilePath = "";
    }
    
    /**
     * Setter for tmpDir
     *
     * @param string $tmpDir
     * @access public
     */
    public function setTmpDir($tmpDir)
    {
        $this->tmpDir = $tmpDir;
    }
    
    /**
     * Getter for errorMsg
     *
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->errorMsg;
    }
    
    // -------------------------------------------------------
    // PUBLIC
    // -------------------------------------------------------
    /**
     * Execute create PDF cover page
     *
     * @return bool
     * @access public
     */
    public function execute()
    {
        $success = true;
        
        // Check PDFTK
        if(!$this->chkCmdPdftk())
        {
            $success = false;
        }
        
        // Make temporary directory
        if($success && !$this->makeTmpDir())
        {
            $success = false;
        }
        
        // Get target file path
        if($success && strlen($this->getTargetPdfFilePath()) == 0)
        {
            $success = false;
        }
        
        // Copy Target PDF to temporary directory
        if($success && !copy($this->getTargetPdfFilePath(), $this->tmpDir.self::PDF_NAME_TARGET))
        {
            $success = false;
        }
        
        // Make cover page
        if($success && !$this->makeCoverPage())
        {
            $success = false;
        }
        
        // Check existing cover page
        $coverCreatedFlag = 0;
        if($success && $this->chkExistCover($coverCreatedFlag))
        {
            // Divide PDF pages
            if(!$this->dividePdf(
                    $this->tmpDir.self::PDF_NAME_TARGET,
                    $this->tmpDir.self::PDF_NAME_ORG_TARGET,
                    sprintf(($coverCreatedFlag+1)."-end")))
            {
                $success = false;
            }
        }
        
        // Combine PDF pages
        if($success && !$this->combinePdf())
        {
            $success = false;
        }
        
        // Combined PDF replace to file directory
        if($success && !copy($this->tmpDir.self::PDF_NAME_COMBINED, $this->getTargetPdfFilePath()))
        {
            $success = false;
        }
        
        // Update cover created flag
        if($success && !$this->updateCoverCreatedFlag())
        {
            $success = false;
        }
        
        // Create PDF thumbnail
        if($success)
        {
            $this->makeThumbnail();
        }
        
        $this->removeTmpDir();
        
        if(!$success)
        {
            $this->errorMsg = self::ERR_CANNOT_CREATE;
        }
        
        return $success;
    }
    
    // -------------------------------------------------------
    // PRIVATE
    // -------------------------------------------------------
    /**
     * Set defaut temporary directory path
     *
     * @return bool
     */
    private function setDefaltTmpDirPath()
    {
        $query = "SELECT DATE_FORMAT(NOW(), '%Y%m%d%H%i%s') AS now_date;";
        $result = $this->Db->execute($query);
        if($result === false || count($result) != 1){
            return false;
        }
        $date = $result[0]['now_date'];
        $this->tmpDir = WEBAPP_DIR.self::WEKO_UPLOAD_DIR."_".$date."/";
        
        return true;
    }
    
    /**
     * Make temporary directory
     *
     * @return bool
     * @access private
     */
    private function makeTmpDir()
    {
        // Check directory path
        if(strlen($this->tmpDir) == 0)
        {
            // Set directory path
            if($this->setDefaltTmpDirPath())
            {
                return false;
            }
        }
        
        // Make directory
        if(!file_exists($this->tmpDir))
        {
            if(!mkdir($this->tmpDir, 0777 ))
            {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Remove temporary directory
     *
     * @return bool
     * @access private
     */
    private function removeTmpDir()
    {
        if(strlen($this->tmpDir) == 0)
        {
            return false;
        }
        
        if (file_exists($this->tmpDir)) {
            chmod ($this->tmpDir, 0777 );
        }
        if ($handle = opendir("$this->tmpDir")) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    if (is_dir("$this->tmpDir/$file")) {
                        $this->removeDirectory("$this->tmpDir/$file");
                        rmdir("$this->tmpDir/$file");
                    } else {
                        chmod ("$this->tmpDir/$file", 0777 );
                        unlink("$this->tmpDir/$file");
                    }
                }
            }
            closedir($handle);
            rmdir($this->tmpDir);
        }
        
        return true;
    }
    
    /**
     * Check PDFTK command
     *
     * @return bool
     * @access private
     */
    private function chkCmdPdftk()
    {
        if(strlen($this->enablePdftk) != 0)
        {
            return $this->enablePdftk;
        }
        else
        {
            // Get PDFTK command path
            $query = "SELECT param_value ".
                     "FROM ".DATABASE_PREFIX."repository_parameter ".
                     "WHERE param_name = 'path_pdftk';";
            $ret = $this->Db->execute($query);
            if ($ret === false) {
                $this->enablePdftk = false;
                return false;
            }
            $cmd_path = $ret[0]['param_value'];
            if(strlen($cmd_path)==0)
            {
                $this->enablePdftk = false;
                return false;
            }
            
            if(file_exists($cmd_path."pdftk") || file_exists($cmd_path."pdftk.exe")){
                $this->enablePdftk = true;
                $this->cmdPathPdftk = $cmd_path."pdftk";
                return true;
            } else {
                $this->enablePdftk = false;
                return false;
            }
        }
    }
    
    /**
     * Check ImageMagick command
     *
     * @return bool
     * @access private
     */
    private function chkCmdImageMagick()
    {
        if(strlen($this->enableImageMagick) != 0)
        {
            return $this->enableImageMagick;
        }
        else
        {
            // Get ImageMagick command path
            $query = "SELECT param_value ".
                     "FROM ".DATABASE_PREFIX."repository_parameter ".
                     "WHERE param_name = 'path_ImageMagick';";
            $ret = $this->Db->execute($query);
            if ($ret === false) {
                $this->enableImageMagick = false;
                return false;
            }
            $cmd_path = $ret[0]['param_value'];
            if(strlen($cmd_path)==0)
            {
                $this->enableImageMagick = false;
                return false;
            }
            
            if(file_exists($cmd_path."convert") || file_exists($cmd_path."convert.exe")){
                $this->enableImageMagick = true;
                $this->cmdPathImageMagick = $cmd_path."convert";
                return true;
            } else {
                $this->enableImageMagick = false;
                return false;
            }
        }
    }
    
    /**
     * Make cover page
     *
     * @return bool
     * @access private
     */
    private function makeCoverPage()
    {
        // Get item data array
        $itemData = $this->getItemData();
        if(!isset($itemData))
        {
            return false;
        }
        
        // Add font
        if(strtolower(self::ENCODE_TO) == 'sjis')
        {
            $this->AddSJISFont(self::FONT_NAME_MSMINCHO, self::FONT_FAMILY_MSMINCHO);
            $this->AddSJISFont(self::FONT_NAME_MSPMINCHO, self::FONT_FAMILY_MSPMINCHO);
            $this->AddSJISFont(self::FONT_NAME_MSGOTHIC, self::FONT_FAMILY_MSGOTHIC);
            $this->AddSJISFont(self::FONT_NAME_MSPGOTHIC, self::FONT_FAMILY_MSPGOTHIC);
        }
        else
        {
            $this->AddUniJISFont(self::FONT_NAME_MSMINCHO, self::FONT_FAMILY_MSMINCHO);
            $this->AddUniJISFont(self::FONT_NAME_MSPMINCHO, self::FONT_FAMILY_MSPMINCHO);
            $this->AddUniJISFont(self::FONT_NAME_MSGOTHIC, self::FONT_FAMILY_MSGOTHIC);
            $this->AddUniJISFont(self::FONT_NAME_MSPGOTHIC, self::FONT_FAMILY_MSPGOTHIC);
        }
        
        // Set Margin
        $this->SetMargins(self::MARGIN_LEFT, self::MARGIN_TOP, self::MARGIN_RIGHT);
        
        // Add Page
        $this->AddPage();
        
        // Item langage
        $itemLang = $itemData["item"][0]["language"];
        
        // TITLE --start--
        $itemTitle = "";
        $itemTitleSub = "";
        if($itemLang == "ja")
        {
            if(strlen($itemData["item"][0]["title"]) > 0)
            {
                $itemTitle = $itemData["item"][0]["title"];
            }
            if(strlen($itemTitle) > 0)
            {
                $itemTitleSub = $itemData["item"][0]["title_english"];
            }
            else
            {
                $itemTitle = $itemData["item"][0]["title_english"];
            }
        }
        else
        {
            if(strlen($itemData["item"][0]["title_english"]) > 0)
            {
                $itemTitle = $itemData["item"][0]["title_english"];
            }
            if(strlen($itemTitle) == 0)
            {
                $itemTitle = $itemData["item"][0]["title"];
            }
        }
        
        $this->SetY(self::MARGIN_TOP+self::MARGIN_TITLE_TOP);
        $this->SetFont(self::FONT_FAMILY_MSGOTHIC, 'B', self::FONTSIZE_TITLE);
        $this->SetDrawColor(150, 150, 200);
        $this->SetLineWidth(0.5);
        // Mod pdf cover page remove line and show doi 2012/10/10 T.Koyasu -start-
        // remove title line
        //$this->Line(self::MARGIN_LEFT, $this->GetY(), $this->w-self::MARGIN_RIGHT, $this->GetY());
        $this->MultiCell(0, $this->FontSize+$this->cMargin, mb_convert_encoding($this->stripAccent($itemTitle), self::ENCODE_TO, self::ENCODE_FROM), 0, self::ALIGN_CENTERLEFT);
        // remove sub title 2012/10/12 T.Koyasu -start-
        //if(strlen($itemTitleSub) > 0)
        //{
        //    $this->SetFont(self::FONT_FAMILY_MSPGOTHIC, 'B', self::FONTSIZE_TITLE_SUB);
        //    $this->MultiCell(0, $this->FontSize+$this->cMargin, mb_convert_encoding($this->stripAccent($itemTitleSub), self::ENCODE_TO, self::ENCODE_FROM), 0, self::ALIGN_CENTER);
        //}
        // remove sub title 2012/10/12 T.Koyasu -end-
        //$this->Line(self::MARGIN_LEFT, $this->GetY(), $this->w-self::MARGIN_RIGHT, $this->GetY());
        // Mod pdf cover page remove line and show doi 2012/10/10 T.Koyasu -start-
        $this->SetY($this->y+self::MARGIN_TITLE_UNDER);
        // TITLE --end--
        
        // METADATA --start--
        $this->SetFont(self::FONT_FAMILY_MSMINCHO, '', self::FONTSIZE_METADATA);
        $this->SetDrawColor(0);
        $this->SetLineWidth(0.2);
        $rowWidth = $this->w-self::MARGIN_LEFT-self::MARGIN_RIGHT;
        $this->SetWidths(array($rowWidth*0.3, $rowWidth*0.7));
        $this->SetAligns(array(self::ALIGN_LEFT, self::ALIGN_LEFT));
        $this->SetRowFillColor(array(array(200,200,250), array(255,255,255)));
        
        // Mod pdf cover page remove line and show doi 2012/10/10 T.Koyasu -start-
        // save string for add doi under metadata list table
        $doiStr = "";
        // Mod pdf cover page remove line and show doi 2012/10/10 T.Koyasu -end-
        
        // Loop for metadata
        $metadataList = array();
        for($ii=0; $ii<count($itemData['item_attr_type']); $ii++)
        {
            $metadata = array();
            
            // Mod pdf cover page remove line and show doi 2012/10/10 T.Koyasu -start-
            // check exists doi and $doiStr is void
            if($itemData['item_attr_type'][$ii]['junii2_mapping'] == "doi" && strlen($doiStr) == 0){
                if($itemData['item_attr_type'][$ii]['input_type'] == "thumbnail" || 
                   $itemData['item_attr_type'][$ii]['input_type'] == "file" || 
                   $itemData['item_attr_type'][$ii]['input_type'] == "file_price" || 
                   $itemData['item_attr_type'][$ii]['input_type'] == "supple" || 
                   $itemData['item_attr_type'][$ii]['input_type'] == "heading" || 
                   $itemData['item_attr_type'][$ii]['input_type'] == "biblio_info"){
                    continue;
                }
                else if($itemData['item_attr_type'][$ii]['input_type'] == "name")
                {
                    if(strtolower($itemData['item_attr_type'][$ii]['display_lang_type']) == "english")
                    {
                        $nameStr = $itemData['item_attr'][$ii][0]['name'];
                        if(strlen($itemData['item_attr'][$ii][0]['family']) > 0)
                        {
                            if(strlen($nameStr) > 0)
                            {
                                $nameStr .= " ";
                            }
                            $nameStr .= $itemData['item_attr'][$ii][0]['family'];
                        }
                    }
                    else
                    {
                        $nameStr = $itemData['item_attr'][$ii][0]['family'];
                        if(strlen($itemData['item_attr'][$ii][0]['name']) > 0)
                        {
                            if(strlen($nameStr) > 0)
                            {
                                $nameStr .= " ";
                            }
                            $nameStr .= $itemData['item_attr'][$ii][0]['name'];
                        }
                    }
                    
                    if(strlen($nameStr) > 0)
                    {
                        $doiStr = $nameStr;
                        continue;
                    }
                }
                else if($itemData['item_attr_type'][$ii]['input_type'] == "link")
                {
                    if(strlen($itemData['item_attr'][$ii][0]['attribute_value']) > 0)
                    {
                        $links = explode("|", $itemData['item_attr'][$ii][0]['attribute_value'], 2);
                        $linkStr = "";
                        if(strlen($links[0]) > 0 && isset($links[1]) && strlen($links[1]) > 0)
                        {
                            $linkStr = $links[1]."(".$links[0].")";
                        }
                        else if(strlen(isset($links[1]) && strlen($links[1]) > 0))
                        {
                            $linkStr = $links[1];
                        }
                        else
                        {
                            $linkStr = $links[0];
                        }
                        
                        if(strlen($linkStr) > 0)
                        {
                            $doiStr = $linkStr;
                            continue;
                        }
                    }
                }
                else
                {
                    $doiStr = $itemData['item_attr'][$ii][0]['attribute_value'];
                    continue;
                }
            }
            // Mod pdf cover page remove line and show doi 2012/10/10 T.Koyasu -end-
            
            // Check option
            if($itemData['item_attr_type'][$ii]['list_view_enable'] != "1")
            {
                continue;
            }
            
            // Check mapping
            if(strlen($itemData['item_attr_type'][$ii]['display_lang_type']) > 0)
            {
                if($itemLang == "ja")
                {
                    if(strtolower($itemData['item_attr_type'][$ii]['display_lang_type']) != "japanese")
                    {
                        continue;
                    }
                }
                else
                {
                    if(strtolower($itemData['item_attr_type'][$ii]['display_lang_type']) != "english")
                    {
                        continue;
                    }
                }
            }
            
            // Check metadata
            if($itemData['item_attr_type'][$ii]['input_type'] == "thumbnail"
                || $itemData['item_attr_type'][$ii]['input_type'] == "file"
                || $itemData['item_attr_type'][$ii]['input_type'] == "file_price"
                || $itemData['item_attr_type'][$ii]['input_type'] == "supple"
                || $itemData['item_attr_type'][$ii]['input_type'] == "heading")
            {
                continue;
            }
            else if($itemData['item_attr_type'][$ii]['input_type'] == "name")
            {
                $metadata["name"] = $itemData['item_attr_type'][$ii]['attribute_name'];
                $metadata["value"] = "";
                for($jj=0; $jj<count($itemData['item_attr'][$ii]); $jj++)
                {
                    if(strtolower($itemData['item_attr_type'][$ii]['display_lang_type']) == "english")
                    {
                        $nameStr = $itemData['item_attr'][$ii][$jj]['name'];
                        if(strlen($itemData['item_attr'][$ii][$jj]['family']) > 0)
                        {
                            if(strlen($nameStr) > 0)
                            {
                                $nameStr .= " ";
                            }
                            $nameStr .= $itemData['item_attr'][$ii][$jj]['family'];
                        }
                    }
                    else
                    {
                        $nameStr = $itemData['item_attr'][$ii][$jj]['family'];
                        if(strlen($itemData['item_attr'][$ii][$jj]['name']) > 0)
                        {
                            if(strlen($nameStr) > 0)
                            {
                                $nameStr .= " ";
                            }
                            $nameStr .= $itemData['item_attr'][$ii][$jj]['name'];
                        }
                    }
                    
                    if(strlen($nameStr) > 0)
                    {
                        if(strlen($metadata["value"]) > 0)
                        {
                            $metadata["value"] .= ", ";
                        }
                        $metadata["value"] .= $nameStr;
                    }
                }
                if(strlen($metadata["value"]) > 0)
                {
                    array_push($metadataList, $metadata);
                }
            }
            else if($itemData['item_attr_type'][$ii]['input_type'] == "biblio_info")
            {
                // jtitle
                $jtitle = "";
                if($itemLang == "ja")
                {
                    if(strlen($itemData['item_attr'][$ii][0]['biblio_name']) > 0)
                    {
                        $jtitle = $itemData['item_attr'][$ii][0]['biblio_name'];
                    }
                    if(strlen($jtitle) == 0)
                    {
                        $jtitle = $itemData['item_attr'][$ii][0]['biblio_name_english'];
                    }
                }
                else
                {
                    if(strlen($itemData['item_attr'][$ii][0]['biblio_name_english']) > 0)
                    {
                        $jtitle = $itemData['item_attr'][$ii][0]['biblio_name_english'];
                    }
                    if(strlen($jtitle) == 0)
                    {
                        $jtitle = $itemData['item_attr'][$ii][0]['biblio_name'];
                    }
                }
                if(strlen($jtitle) >0)
                {
                    if($itemLang == "ja")
                    {
                        $metadata["name"] = self::BIBLIO_JTITLE_JP;
                    }
                    else
                    {
                        $metadata["name"] = self::BIBLIO_JTITLE_EN;
                    }
                    $metadata["value"] = $jtitle;
                    array_push($metadataList, $metadata);
                }
                
                // volume
                if(strlen($itemData['item_attr'][$ii][0]['volume']) >0)
                {
                    if($itemLang == "ja")
                    {
                        $metadata["name"] = self::BIBLIO_VOLUME_JP;
                    }
                    else
                    {
                        $metadata["name"] = self::BIBLIO_VOLUME_EN;
                    }
                    $metadata["value"] = $itemData['item_attr'][$ii][0]['volume'];
                    array_push($metadataList, $metadata);
                }
                
                // issue
                if(strlen($itemData['item_attr'][$ii][0]['issue']) >0)
                {
                    if($itemLang == "ja")
                    {
                        $metadata["name"] = self::BIBLIO_ISSUE_JP;
                    }
                    else
                    {
                        $metadata["name"] = self::BIBLIO_ISSUE_EN;
                    }
                    $metadata["value"] = $itemData['item_attr'][$ii][0]['issue'];
                    array_push($metadataList, $metadata);
                }
                
                // pages
                $pages = $itemData['item_attr'][$ii][0]['start_page'];
                if(strlen($itemData['item_attr'][$ii][0]['end_page']) > 0)
                {
                    if(strlen($pages) > 0)
                    {
                        $pages .= "-";
                    }
                    $pages .= $itemData['item_attr'][$ii][0]['end_page'];
                }
                if(strlen($pages) >0)
                {
                    if($itemLang == "ja")
                    {
                        $metadata["name"] = self::BIBLIO_PAGE_JP;
                    }
                    else
                    {
                        $metadata["name"] = self::BIBLIO_PAGE_EN;
                    }
                    $metadata["value"] = $pages;
                    array_push($metadataList, $metadata);
                }
                
                // dateofissued
                if(strlen($itemData['item_attr'][$ii][0]['date_of_issued']) >0)
                {
                    if($itemLang == "ja")
                    {
                        $metadata["name"] = self::BIBLIO_DATEOFISSUED_JP;
                    }
                    else
                    {
                        $metadata["name"] = self::BIBLIO_DATEOFISSUED_EN;
                    }
                    $metadata["value"] = $itemData['item_attr'][$ii][0]['date_of_issued'];
                    array_push($metadataList, $metadata);
                }
            }
            else if($itemData['item_attr_type'][$ii]['input_type'] == "link")
            {
                $metadata["name"] = $itemData['item_attr_type'][$ii]['attribute_name'];
                $metadata["value"] = "";
                for($jj=0; $jj<count($itemData['item_attr'][$ii]); $jj++)
                {
                    if(strlen($itemData['item_attr'][$ii][$jj]['attribute_value']) > 0)
                    {
                        $links = explode("|", $itemData['item_attr'][$ii][$jj]['attribute_value'], 2);
                        $linkStr = "";
                        if(strlen($links[0]) > 0 && isset($links[1]) && strlen($links[1]) > 0)
                        {
                            $linkStr = $links[1]."(".$links[0].")";
                        }
                        else if(strlen(isset($links[1]) && strlen($links[1]) > 0))
                        {
                            $linkStr = $links[1];
                        }
                        else
                        {
                            $linkStr = $links[0];
                        }
                        
                        if(strlen($linkStr) > 0)
                        {
                            if(strlen($metadata["value"]) > 0)
                            {
                                $metadata["value"] .= "\n";
                            }
                            $metadata["value"] .= $linkStr;
                        }
                    }
                }
                if(strlen($metadata["value"]) > 0)
                {
                    array_push($metadataList, $metadata);
                }
            }
            else
            {
                $metadata["name"] = $itemData['item_attr_type'][$ii]['attribute_name'];
                $metadata["value"] = "";
                for($jj=0; $jj<count($itemData['item_attr'][$ii]); $jj++)
                {
                    if(strlen($itemData['item_attr'][$ii][$jj]['attribute_value']) > 0)
                    {
                        if(strlen($metadata["value"]) > 0)
                        {
                            $metadata["value"] .= "\n";
                        }
                        $metadata["value"] .= $itemData['item_attr'][$ii][$jj]['attribute_value'];
                    }
                }
                if(strlen($metadata["value"]) > 0)
                {
                    array_push($metadataList, $metadata);
                }
            }
        }
        
        // Add PDF file URL
        $url = $itemData["item"][0]["uri"];
        array_push($metadataList, array("name" => "URL", "value" => $url));
        
        foreach($metadataList as $metadata)
        {
            $this->Row(
                array(
                    mb_convert_encoding($this->stripAccent($metadata["name"]), self::ENCODE_TO, self::ENCODE_FROM),
                    mb_convert_encoding($this->stripAccent($metadata["value"]), self::ENCODE_TO, self::ENCODE_FROM)
                )
            );
        }
        // METADATA --end--
        // Mod pdf cover page remove line and show doi 2012/10/10 T.Koyasu -start-
        $doiStr = mb_ereg_replace('[^\x00-\x7f]', "", $doiStr);
        if(strlen($doiStr) > 0)
        {
            // set font=arial, fontsize=10
            $this->SetFont(self::FONT_ARIAL, '', self::FONTSIZE_DOI);
            // add doi string under metadata list table
            
            $this->MultiCell(0, $this->FontSize+$this->cMargin, mb_convert_encoding($this->stripAccent("doi: ".$doiStr), self::ENCODE_TO, self::ENCODE_FROM), 0, self::ALIGN_RIGHT);
        }
        // Mod pdf cover page remove line and show doi 2012/10/10 T.Koyasu -end-
        
        $this->Output($this->tmpDir.self::PDF_NAME_COVER, 'F');
        
        return true;
    }
    
    /**
     * Check existing cover page
     *
     * @param int &$coverCreatedFlag
     * @return bool false: Not exist / true: Existing
     * @access private
     */
    private function chkExistCover(&$coverCreatedFlag)
    {
        $coverCreatedFlag = 0;
        $query = "SELECT ".RepositoryConst::DBCOL_REPOSITORY_FILE_COVER_CREATED_FLAG." ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_FILE." ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO." = ? ".
                 "AND ".RepositoryConst::DBCOL_COMMON_IS_DELETE." = ?;";
        $params = array();
        $params[] = $this->itemId;
        $params[] = $this->itemNo;
        $params[] = $this->attributeId;
        $params[] = $this->fileNo;
        $params[] = 0;
        $result = $this->Db->execute($query, $params);
        if($result === false){
            return false;
        }
        if(count($result[0]) == 0)
        {
            return false;
        }
        $coverCreatedFlag = intval($result[0][RepositoryConst::DBCOL_REPOSITORY_FILE_COVER_CREATED_FLAG]);
        if($coverCreatedFlag == 0)
        {
            return false;
        }
        
        return true;
    }
    
    /**
     * Divide PDF pages
     *
     * @param string $target
     * @param string $tmpTarget
     * @param string $range
     * @return bool
     * @access private
     */
    private function dividePdf($target, $tmpTarget, $range)
    {
        if(!$this->chkCmdPdftk())
        {
            return false;
        }
        
        // #pdftk [target_path] cat [page_range] output [output_path]
        if(!rename($target, $tmpTarget))
        {
            return false;
        }
        $cmd = "\"".$this->cmdPathPdftk."\" ".
               "\"".$tmpTarget."\" ".
               "cat ".$range." output ".
               "\"".$target."\"";
        exec(escapeshellcmd($cmd));
        
        if(!file_exists($target))
        {
            return false;
        }
        
        return true;
    }
    
    /**
     * Combine PDF pages
     *
     * @return bool
     * @access private
     */
    private function combinePdf()
    {
        if(!$this->chkCmdPdftk())
        {
            return false;
        }
        
        // #pdftk [cover_path] [target_path] cat output [output_path]
        $cmd = "\"".$this->cmdPathPdftk."\" ".
               "\"".$this->tmpDir.self::PDF_NAME_COVER."\" ".
               "\"".$this->tmpDir.self::PDF_NAME_TARGET."\" ".
               "cat output ".
               "\"".$this->tmpDir.self::PDF_NAME_COMBINED."\"";
        
        exec(escapeshellcmd($cmd));
        
        if(!file_exists($this->tmpDir.self::PDF_NAME_COMBINED))
        {
            return false;
        }
        
        return true;
    }
    
    /**
     * Make PDF thumbnail
     *
     * @return bool
     * @access private
     */
    private function makeThumbnail()
    {
        $isSuccess = false;
        
        // Check ImageMagick command
        if(!$this->chkCmdImageMagick())
        {
            return $isSuccess;
        }
        
        // PDF -> PNG
        $cmd = "\"".$this->cmdPathImageMagick."\" ".
               "-quality 100 ".
               "\"".$this->tmpDir.self::PDF_NAME_COMBINED."\"[0] ".
               "\"".$this->tmpDir.self::PDF_NAME_COMBINED.".png\"";
        exec(escapeshellcmd($cmd));
        
        if(file_exists($this->tmpDir.self::PDF_NAME_COMBINED.".png"))
        {
            // Success
            // Get image size
            $imgSize = array();
            $imgSize = getimagesize($this->tmpDir.self::PDF_NAME_COMBINED.".png");
            $width = $imgsize[0];
            $height = $imgsize[1];
            if(unlink($this->tmpDir.self::PDF_NAME_COMBINED.".png"))
            {
                // Resize
                if($height > $width)
                {
                    // Height is longer than width
                    $cmd = "\"".$this->cmdPathImageMagick."\" ".
                           "-quality 100 -density 200x200 -resize 200x ".
                           "\"".$this->tmpDir.self::PDF_NAME_COMBINED."\"[0] ".
                           "\"".$this->tmpDir.self::PDF_NAME_COMBINED.".png\"";
                }
                else
                {
                    // Width is longer than height
                    $cmd = "\"".$this->cmdPathImageMagick."\" ".
                           "-quality 100 -density 200x200 -resize x280 ".
                           "\"".$this->tmpDir.self::PDF_NAME_COMBINED."\"[0] ".
                           "\"".$this->tmpDir.self::PDF_NAME_COMBINED.".png\"";
                }
                exec(escapeshellcmd($cmd));
                
                if(file_exists($this->tmpDir.self::PDF_NAME_COMBINED.".png"))
                {
                    // Success
                    $this->updateThumbnail($this->tmpDir.self::PDF_NAME_COMBINED.".png");
                    $isSuccess = true;
                }
            }
        }
        
        return $isSuccess;
    }
    
    /**
     * Update file thumbnail
     *
     * @param string $filePath
     * @return bool
     */
    private function updateThumbnail($filePath)
    {
        $whereParams = RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID." = ".$this->itemId." ".
                       "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO." = ".$this->itemNo." ".
                       "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID." = ".$this->attributeId." ".
                       "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO." = ".$this->fileNo;
        $ret = $this->Db->updateBlobFile(
                    RepositoryConst::DBTABLE_REPOSITORY_FILE,
                    RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_PREV,
                    $filePath,
                    $whereParams,
                    "LONGBLOB"
                );
        if ($ret === false) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get item data
     *
     * @return array
     * @access private
     */
    private function getItemData()
    {
        $resultList = null;
        $errMsg = "";
        $result = $this->repositoryAction->getItemData($this->itemId, $this->itemNo, $resultList, $errMsg, false, true);
        if($result == false)
        {
            $resultList = null;
        }
        
        return $resultList;
    }
    
    /**
     * Get target PDF file Path
     *
     * @return string
     * @access private
     */
    private function getTargetPdfFilePath()
    {
        if(strlen($this->targetFilePath) == 0)
        {
            $filePath = "";
            if(($this->itemId * $this->attributeId * $this->fileNo) > 0)
            {
                $fileName = $this->itemId."_".$this->attributeId."_".$this->fileNo.".pdf";
                
                $dirPath = $this->repositoryAction->getFileSavePath("file");
                if(strlen($dirPath) == 0)
                {
                    // default directory
                    $dirPath = BASE_DIR.'/webapp/uploads/repository/files/';
                }
                
                if(substr($dirPath, -1, 1) != "/"){
                    $dirPath .= "/";
                }
                
                $this->targetFilePath = $dirPath.$fileName;
            }
        }
        
        return $this->targetFilePath;
    }
    
    /**
     * Update cover_created_flag
     *
     * @return bool
     * @access private
     */
    private function updateCoverCreatedFlag()
    {
        $query = "UPDATE ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_FILE." ".
                 "SET ".RepositoryConst::DBCOL_REPOSITORY_FILE_COVER_CREATED_FLAG." = ?, ".
                 RepositoryConst::DBCOL_COMMON_MOD_USER_ID." = ?, ".
                 RepositoryConst::DBCOL_COMMON_MOD_DATE." = ? ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO." = ?; ";
        $params = array();
        $params[] = intval($this->page);
        $params[] = $this->userId;
        $params[] = $this->TransStartDate;
        $params[] = $this->itemId;
        $params[] = $this->itemNo;
        $params[] = $this->attributeId;
        $params[] = $this->fileNo;
        $result = $this->Db->execute($query, $params);
        if($result === false)
        {
            return false;
        }
        
        return true;
    }
    
    /**
     * Strip accent string
     *
     * @param string $str
     * @return string
     * @access private
     */
    private function stripAccent($str)
    {
        $convStr = "";
        
        //$encStr = mb_convert_encoding($str, 'HTML-ENTITIES', 'UTF-8');
        //$decStr = mb_convert_encoding($encStr, 'UTF-8', 'HTML-ENTITIES');
        
        
        $chars = array(
                // ----------------------------------
                // Remove accent
                // ----------------------------------
                // Decompositions for Latin-1 Supplement
                '&#192;' => '&#65;', '&#193;' => '&#65;',
                '&#194;' => '&#65;', '&#195;' => '&#65;',
                '&#196;' => '&#65;', '&#197;' => '&#65;',
                '&#198;' => '&#65;&#69;', '&#199;' => '&#67;',
                '&#200;' => '&#69;', '&#201;' => '&#69;',
                '&#202;' => '&#69;', '&#203;' => '&#69;',
                '&#204;' => '&#73;', '&#205;' => '&#73;',
                '&#206;' => '&#73;', '&#207;' => '&#73;',
                '&#208;' => '&#68;', '&#209;' => '&#78;',
                '&#210;' => '&#79;', '&#211;' => '&#79;',
                '&#212;' => '&#79;', '&#213;' => '&#79;',
                '&#214;' => '&#79;',
                '&#216;' => '&#79;', '&#217;' => '&#85;',
                '&#218;' => '&#85;', '&#219;' => '&#85;',
                '&#220;' => '&#85;', '&#221;' => '&#89;',
                '&#222;' => '&#80;', '&#223;' => '&#115;',
                '&#224;' => '&#97;', '&#225;' => '&#97;',
                '&#226;' => '&#97;', '&#227;' => '&#97;',
                '&#228;' => '&#97;', '&#229;' => '&#97;',
                '&#230;' => '&#97;&#101;', '&#231;' => '&#99;',
                '&#232;' => '&#101;', '&#233;' => '&#101;',
                '&#234;' => '&#101;', '&#235;' => '&#101;',
                '&#236;' => '&#105;', '&#237;' => '&#105;',
                '&#238;' => '&#105;', '&#239;' => '&#105;',
                '&#240;' => '&#100;', '&#241;' => '&#110;',
                '&#242;' => '&#111;', '&#243;' => '&#111;',
                '&#244;' => '&#111;', '&#245;' => '&#111;',
                '&#246;' => '&#111;',
                '&#248;' => '&#111;', '&#249;' => '&#117;',
                '&#250;' => '&#117;', '&#251;' => '&#117;',
                '&#252;' => '&#117;', '&#253;' => '&#121;',
                '&#254;' => '&#112;', '&#255;' => '&#121;',
                // Decompositions for Latin Extended-A
                '&#256;' => '&#65;', '&#257;' => '&#97;',
                '&#258;' => '&#65;', '&#259;' => '&#97;',
                '&#260;' => '&#65;', '&#261;' => '&#97;',
                '&#262;' => '&#67;', '&#263;' => '&#99;',
                '&#264;' => '&#67;', '&#265;' => '&#99;',
                '&#266;' => '&#67;', '&#267;' => '&#99;',
                '&#268;' => '&#67;', '&#269;' => '&#99;',
                '&#270;' => '&#68;', '&#271;' => '&#100;',
                '&#272;' => '&#68;', '&#273;' => '&#100;',
                '&#274;' => '&#69;', '&#275;' => '&#101;',
                '&#276;' => '&#69;', '&#277;' => '&#101;',
                '&#278;' => '&#69;', '&#279;' => '&#101;',
                '&#280;' => '&#69;', '&#281;' => '&#101;',
                '&#282;' => '&#69;', '&#283;' => '&#101;',
                '&#284;' => '&#71;', '&#285;' => '&#103;',
                '&#286;' => '&#71;', '&#287;' => '&#103;',
                '&#288;' => '&#71;', '&#289;' => '&#103;',
                '&#290;' => '&#71;', '&#291;' => '&#103;',
                '&#292;' => '&#72;', '&#293;' => '&#104;',
                '&#294;' => '&#72;', '&#295;' => '&#104;',
                '&#296;' => '&#73;', '&#297;' => '&#105;',
                '&#298;' => '&#73;', '&#299;' => '&#105;',
                '&#300;' => '&#73;', '&#301;' => '&#105;',
                '&#302;' => '&#73;', '&#303;' => '&#105;',
                '&#304;' => '&#73;', '&#305;' => '&#105;',
                '&#306;' => '&#73;&#74;', '&#307;' => '&#105;&#106;',
                '&#308;' => '&#74;', '&#309;' => '&#106;',
                '&#310;' => '&#75;', '&#311;' => '&#107;',
                '&#312;' => '&#107;', '&#313;' => '&#76;',
                '&#314;' => '&#108;', '&#315;' => '&#76;',
                '&#316;' => '&#108;', '&#317;' => '&#76;',
                '&#318;' => '&#108;', '&#319;' => '&#76;',
                '&#320;' => '&#108;', '&#321;' => '&#76;',
                '&#322;' => '&#108;', '&#323;' => '&#78;',
                '&#324;' => '&#110;', '&#325;' => '&#78;',
                '&#326;' => '&#110;', '&#327;' => '&#78;',
                '&#328;' => '&#110;', '&#329;' => '&#78;',
                '&#330;' => '&#110;', '&#331;' => '&#78;',
                '&#332;' => '&#79;', '&#333;' => '&#111;',
                '&#334;' => '&#79;', '&#335;' => '&#111;',
                '&#336;' => '&#79;', '&#337;' => '&#111;',
                '&#338;' => '&#79;&#69;', '&#339;' => '&#111;&#101;',
                '&#340;' => '&#82;', '&#341;' => '&#114;',
                '&#342;' => '&#82;', '&#343;' => '&#114;',
                '&#344;' => '&#82;', '&#345;' => '&#114;',
                '&#346;' => '&#83;', '&#347;' => '&#115;',
                '&#348;' => '&#83;', '&#349;' => '&#115;',
                '&#350;' => '&#83;', '&#351;' => '&#115;',
                '&#352;' => '&#83;', '&#353;' => '&#115;',
                '&#354;' => '&#84;', '&#355;' => '&#116;',
                '&#356;' => '&#84;', '&#357;' => '&#116;',
                '&#358;' => '&#84;', '&#359;' => '&#116;',
                '&#360;' => '&#85;', '&#361;' => '&#117;',
                '&#362;' => '&#85;', '&#363;' => '&#117;',
                '&#364;' => '&#85;', '&#365;' => '&#117;',
                '&#366;' => '&#85;', '&#367;' => '&#117;',
                '&#368;' => '&#85;', '&#369;' => '&#117;',
                '&#370;' => '&#85;', '&#371;' => '&#117;',
                '&#372;' => '&#87;', '&#373;' => '&#119;',
                '&#374;' => '&#89;', '&#375;' => '&#121;',
                '&#376;' => '&#89;', '&#377;' => '&#90;',
                '&#378;' => '&#122;', '&#379;' => '&#90;',
                '&#380;' => '&#122;', '&#381;' => '&#90;',
                '&#382;' => '&#122;', '&#383;' => '&#115;',
                
                // ----------------------------------
                // Convert to other string
                // ----------------------------------
                '&#169;' => '&#40;&#67;&#41;',          // 著作権記号 -> (C)
                '&#174;' => '&#40;&#82;&#41;',          // 登録商標記号 -> (R)
                '&#8482;' => '&#40;&#84;&#77;&#41;',    // 商標記号 -> (TM)
                '&#189;' => '&#49;&#47;&#50;',          // 2分の1 -> 1/2
                '&#188;' => '&#49;&#47;&#52;',          // 4分の1 -> 1/4
                '&#190;' => '&#51;&#47;&#52;',          // 4分の3 -> 3/4
                '&#8721;' => '&#931;',                  // 数列の和 -> Σ(ギリシャ文字シグマ)
                '&#8719;' => '&#928;',                  // 数列の積、直積 -> Π(ギリシャ文字パイ)
                
                // ----------------------------------
                // Convert to half space
                // ----------------------------------
                '&#166;' => '&#32;',    // 破断縦線
                '&#181;' => '&#32;',    // マイクロ記号
                '&#164;' => '&#32;',    // 一般通貨記号
                '&#128;' => '&#32;',    // ユーロ記号
                '&#8596;' => '&#32;',   // 左右両向き矢印
                '&#8657;' => '&#32;',   // 上向き二重矢印
                '&#8659;' => '&#32;',   // 下向き二重矢印
                '&#8656;' => '&#32;',   // 左向き二重矢印
                '&#9824;' => '&#32;',   // スペードマーク
                '&#9827;' => '&#32;',   // クラブマーク
                '&#9829;' => '&#32;',   // ハートマーク
                '&#9830;' => '&#32;',   // ダイヤマーク
                '&#170;' => '&#32;',    // 女性序数標識
                '&#186;' => '&#32;',    // 男性序数標識
                '&#185;' => '&#32;',    // 上付き1
                '&#178;' => '&#32;',    // 上付き2
                '&#179;' => '&#32;',    // 上付き3
                '&#183;' => '&#32;',    // 中点
                '&#161;' => '&#32;',    // 逆さ感嘆符
                '&#191;' => '&#32;',    // 逆さ疑問符
                '&#171;' => '&#32;',    // 左二重角引用符
                '&#187;' => '&#32;',    // 右二重角引用符
                '&#8195;' => '&#32;',   // “m”幅空白
                '&#8194;' => '&#32;',   // “n”幅空白
                '&#8201;' => '&#32;',   // 狭い空白
                '&#8212;' => '&#32;',   // “m”幅ダッシュ
                '&#8211;' => '&#32;',   // “n”幅ダッシュ
                '&#8226;' => '&#32;',   // 行頭文字
                '&#9674;' => '&#32;',   // 菱形
                '&#8776;' => '&#32;',   // ほぼ等しい
                '&#8773;' => '&#32;',   // およそ等しい
                '&#8805;' => '&#32;',   // 大なりまたは等しい
                '&#8804;' => '&#32;',   // 小なりまたは等しい
                '&#402;' => '&#32;',    // 関数記号
                '&#8465;' => '&#32;',   // 虚数
                '&#8476;' => '&#32;',   // 実数
                '&#8472;' => '&#32;',   // ワイエルシュトラスのP
                '&#8764;' => '&#32;',   // チルダ演算子
                '&#982;' => '&#32;',    // パイ記号
                '&#8709;' => '&#32;',   // 空集合
                '&#8713;' => '&#32;',   // ～の要素ではない
                '&#8836;' => '&#32;',   // 含まれない
                '&#8901;' => '&#32;',   // 点演算子
                '&#8727;' => '&#32;',   // アスタリスク演算子
                '&#8853;' => '&#32;',   // 丸囲み加算(直和)
                '&#8855;' => '&#32;',   // 丸囲み乗算(直積)
                '&#8968;' => '&#32;',   // 左上限
                '&#8970;' => '&#32;',   // 左下限
                '&#8969;' => '&#32;',   // 右上限
                '&#8971;' => '&#32;',   // 右下限
                '&#184;' => '&#32;',    // セディラ(セディーユ)
                '&#710;' => '&#32;',    // サーカムフレックス
                '&#175;' => '&#32;',    // マクロン
                '&#732;' => '&#32;'     // チルダ
            );
        
        $convMap = array(0x0, 0xffff, 0, 0xffff);
        $encStr = mb_encode_numericentity($str, $convMap, 'UTF-8');
        $convEncStr = strtr($encStr, $chars);
        $decStr = mb_decode_numericentity($convEncStr, $convMap, 'UTF-8');
        
        $convStr = $decStr;
        
        return $convStr;
    }
    
    // -------------------------------------------------------
    // OVERRIDE
    // -------------------------------------------------------
    /**
     * Header setting
     *
     */
    function Header()
    {
        $this->SetFont(self::FONT_FAMILY_MSMINCHO,'',self::FONTSIZE_HEADER);
        $this->SetY(self::HEADER_TOP);
        
        // Header align
        $headerAlign = self::ALIGN_RIGHT;
        $result = $this->repositoryAction->getPdfCoverParamRecord(RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_ALIGN);
        switch($result[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_TEXT])
        {
            case RepositoryConst::PDF_COVER_HEADER_ALIGN_RIGHT:
                $headerAlign = self::ALIGN_RIGHT;
                break;
            case RepositoryConst::PDF_COVER_HEADER_ALIGN_CENTER:
                $headerAlign = self::ALIGN_CENTER;
                break;
            case RepositoryConst::PDF_COVER_HEADER_ALIGN_LEFT:
                $headerAlign = self::ALIGN_LEFT;
                break;
            default:
                $headerAlign = self::ALIGN_RIGHT;
                break;
        }
        
        // Header type
        $headerType = $this->repositoryAction->getPdfCoverParamRecord(RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_TYPE);
        if($headerType[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_TEXT] == RepositoryConst::PDF_COVER_HEADER_TYPE_TEXT)
        {
            // Header type : text
            $result = $this->repositoryAction->getPdfCoverParamRecord(RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_TEXT);
            $headerText = $result[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_TEXT];
            $this->MultiCell(0, $this->FontSize+$this->cMargin, mb_convert_encoding($this->stripAccent($headerText), self::ENCODE_TO, self::ENCODE_FROM), 0, $headerAlign);
        }
        else if($headerType[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_TEXT] == RepositoryConst::PDF_COVER_HEADER_TYPE_IMAGE)
        {
            // Header type : image
            $result = $this->repositoryAction->getPdfCoverParamRecord(RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_IMAGE);
            $imageBlob = $result[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_IMAGE];
            $imageName = $result[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_TEXT];
            
            if(strlen($imageBlob) > 0)
            {
                // Create physical file
                $handle = fopen($this->tmpDir.$imageName, "w");
                fwrite($handle, $imageBlob);
                fclose($handle);
                if(file_exists($this->tmpDir.$imageName))
                {
                    // Get Image size
                    $a = getimagesize($this->tmpDir.$imageName);
                    $w_px = $a[0]; // Unit: px
                    $h_px = $a[1]; // Unit: px
                    
                    // Convert to FPDF unit
                    $w_unit = $w_px*72/96/$this->k;
                    $h_unit = $h_px*72/96/$this->k;
                    
                    // Resize
                    $this->repositoryAction->resizeImage(
                            $w_unit,
                            $h_unit,
                            $this->w-self::MARGIN_LEFT-self::MARGIN_RIGHT,
                            self::HEADER_HEIGHT
                        );
                    if($headerAlign == self::ALIGN_RIGHT)
                    {
                        // right
                        $this->SetX($this->w-self::MARGIN_RIGHT-$w_unit);
                    }
                    else if($headerAlign == self::ALIGN_CENTER)
                    {
                        // center
                        $this->SetX($this->w/2 - $w_unit/2);
                    }
                    else if($headerAlign == self::ALIGN_LEFT)
                    {
                        // left
                        $this->SetX(self::MARGIN_LEFT - $w_unit);
                    }
                    
                    $this->Image($this->tmpDir.$imageName, $this->GetX(), $this->GetY(), $w_unit, $h_unit);
                }
            }
        }
        $this->SetY(self::MARGIN_TOP);
        $this->SetX(self::MARGIN_LEFT);
    }
    
    /**
     * Footer setting
     *
     */
    function Footer()
    {
        // Get license
        $query = "SELECT ".RepositoryConst::DBCOL_REPOSITORY_FILE_LICENSE_ID.", ".
                 "       ".RepositoryConst::DBCOL_REPOSITORY_FILE_LICENSE_NOTATION." ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_FILE." ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO." = ? ".
                 "AND ".RepositoryConst::DBCOL_COMMON_IS_DELETE." = ?; ";
        $params = array();
        $params[] = $this->itemId;
        $params[] = $this->itemNo;
        $params[] = $this->attributeId;
        $params[] = $this->fileNo;
        $params[] = 0;
        $result = $this->Db->execute($query, $params);
        if($result === false || (isset($result) && count($result[0]) == 0)){
            return false;
        }
        
        $licenseId = $result[0][RepositoryConst::DBCOL_REPOSITORY_FILE_LICENSE_ID];
        $notation = $result[0][RepositoryConst::DBCOL_REPOSITORY_FILE_LICENSE_NOTATION];
        
        if(strlen($notation) > 0)
        {
            // Set license to PDF
            $this->SetY(self::FOOTER_TOP);
            $this->SetFont(self::FONT_FAMILY_MSMINCHO, '', 9);
            
            if($licenseId == "0")
            {
                // Free
                $this->MultiCell(0, $this->FontSize+$this->cMargin, mb_convert_encoding($this->stripAccent($notation), self::ENCODE_TO, self::ENCODE_FROM), 0, self::ALIGN_RIGHT);
            }
            else if(strlen($licenseId) > 0)
            {
                // Criative Commons
                $query = "SELECT * ".
                         "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_LICENSE_MASTER." ".
                         "WHERE ".RepositoryConst::DBCOL_REPOSITORY_LICENSE_MASTAER_LICENSE_ID." = ?; ";
                $params = array();
                $params[] = $licenseId;
                $result = $this->Db->execute($query, $params);
                if($result === false || (isset($result) && count($result[0]) == 0)){
                    return false;
                }
                $licenseImagePath = $result[0][RepositoryConst::DBCOL_REPOSITORY_LICENSE_MASTAER_IMG_URL];
                $licenseTextUrl = $result[0][RepositoryConst::DBCOL_REPOSITORY_LICENSE_MASTAER_TEXT_URL];
                if(strlen($licenseImagePath) > 0)
                {
                    // Get Image size
                    $a = getimagesize($licenseImagePath);
                    $w_px = $a[0]; // Unit: px
                    $h_px = $a[1]; // Unit: px
                    
                    // Convert to FPDF unit
                    $w_unit = $w_px*72/96/$this->k;
                    $h_unit = $h_px*72/96/$this->k;
                    
                    // Resize
                    $this->repositoryAction->resizeImage(
                            $w_unit,
                            $h_unit,
                            $this->w-self::MARGIN_LEFT-self::MARGIN_RIGHT,
                            self::FOOTER_HEIGHT
                        );
                    $this->SetX($this->w-self::MARGIN_RIGHT-$w_unit);
                    $this->Image($licenseImagePath, $this->GetX(), $this->GetY(), $w_unit, $h_unit);
                    $this->SetX(self::MARGIN_LEFT);
                    $this->MultiCell($this->w-self::MARGIN_LEFT-self::MARGIN_RIGHT-$w_unit, $this->FontSize+$this->cMargin, mb_convert_encoding($this->stripAccent($notation), self::ENCODE_TO, self::ENCODE_FROM), 0, self::ALIGN_RIGHT);
                    $this->SetX(self::MARGIN_LEFT);
                    $this->MultiCell($this->w-self::MARGIN_LEFT-self::MARGIN_RIGHT-$w_unit, $this->FontSize+$this->cMargin, mb_convert_encoding($this->stripAccent($licenseTextUrl), self::ENCODE_TO, self::ENCODE_FROM), 0, self::ALIGN_RIGHT);
                }
                else
                {
                    $this->MultiCell(0, $this->FontSize+$this->cMargin, mb_convert_encoding($this->stripAccent($notation), self::ENCODE_TO, self::ENCODE_FROM), 0, self::ALIGN_RIGHT);
                }
            }
        }
        $this->SetY(self::MARGIN_TOP);
        $this->SetX(self::MARGIN_LEFT);
    }
}

?>
