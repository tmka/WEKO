<?php
// --------------------------------------------------------------------
//
// $Id: Swordmanager.class.php 43075 2014-10-20 06:12:22Z yuko_nakao $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

require_once WEBAPP_DIR. '/modules/repository/components/RepositoryLogicBase.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/Factory.class.php';
require_once WEBAPP_DIR. '/modules/repository/action/main/export/ExportCommon.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryOutputTSV.class.php';
include_once WEBAPP_DIR. '/modules/repository/files/pear/Date.php';

class Repository_Components_Swordmanager extends RepositoryLogicBase
{
    const TAG_ROOT = 'wekoDataConvertFilter';
    const TAG_METADATA = 'metadata';
    const TAG_ITEMTYPES = 'itemTypes';
    const TAG_ITEMTYPE = 'itemType';
    const TAG_ITEMTYPE_NAME = 'name';
    const TAG_BASICATTRIBUTES = 'basicAttributes';
    const TAG_TITLE = 'title';
    const TAG_TITLEINENGLISH = 'titleInEnglish';
    const TAG_LANGUAGE = 'language';
    const TAG_KEYWORDS = 'keywords';
    const TAG_KEYWORDSINENGLISH = 'keywordsInEnglish';
    const TAG_PUBLICATIONDATE = 'publicationDate';
    const TAG_ADDITIONALATTRIBUTES = 'additionalAttributes';
    const TAG_ADDITIONALATTRIBUTE = 'additionalAttribute';
    const TAG_ADD_ATTR_NAME = 'name';
    const TAG_ADD_ATTR_CANDIDATES = 'candidates';
    
    const ATTRIBUTE_TYPE = 'type';
    const ATTRIBUTE_MAPPING_INFO = 'mapping_info';
    const ATTRIBUTE_REQUIRED = 'required';
    const ATTRIBUTE_ALLOWMULTIPLEINPUT = 'allowmultipleinput';
    const ATTRIBUTE_LISTING = 'listing';
    const ATTRIBUTE_SPECIFYNEWLINE = 'specifynewline';
    const ATTRIBUTE_HIDDEN = 'hidden';
    const ATTRIBUTE_JUNII2_MAPPING = 'junii2_mapping';
    const ATTRIBUTE_DUBLIN_CORE_MAPPING = 'dublin_core_mapping';
    const ATTRIBUTE_DELIMITERS = 'delimiters';
    const ATTRIBUTE_DISPLAY_LANG_TYPE = 'display_lang_type';
    
    const ATTRIBUTE_COLNAME_ITEMTYPE = 'columnname_itemtype';
    const ATTRIBUTE_COLNAME_VALUE = 'columnname_value';
    const ATTRIBUTE_ISFAMILYGIVENCONNECT = 'isfamilygivenconnected';
    const ATTRIBUTE_COLNAME_FAMILY = 'columnname_family';
    const ATTRIBUTE_COLNAME_GIVEN = 'columnname_given';
    const ATTRIBUTE_COLNAME_FAMILYRUBY = 'columnname_familyruby';
    const ATTRIBUTE_COLNAME_GIVENRUBY = 'columnname_givenruby';
    const ATTRIBUTE_COLNAME_EMAILADDRESS = 'columnname_emailaddress';
    const ATTRIBUTE_COLNAME_AUTHORIDS = 'columnname_authorids';
    const ATTRIBUTE_ISSTARTENDPAGECONNECT = 'isstartendpageconnected';
    const ATTRIBUTE_COLNAME_BIBLIONAME = 'columnname_biblioname';
    const ATTRIBUTE_COLNAME_BIBLIONAMEENGLISH = 'columnname_biblionameenglish';
    const ATTRIBUTE_COLNAME_VOLUME = 'columnname_volume';
    const ATTRIBUTE_COLNAME_ISSUE = 'columnname_issue';
    const ATTRIBUTE_COLNAME_STARTPAGE = 'columnname_startpage';
    const ATTRIBUTE_COLNAME_ENDPAGE = 'columnname_endpage';
    const ATTRIBUTE_COLNAME_DATEOFISSUED = 'columnname_dateofissued';
    const ATTRIBUTE_COLNAME_LINKNAME = 'columnname_linkname';
    const ATTRIBUTE_COLNAME_LINKURL = 'columnname_linkurl';
    const ATTRIBUTE_DISPLAYTYPE = 'displaytype';
    const ATTRIBUTE_COLNAME_FILENAME = 'columnname_filename';
    const ATTRIBUTE_COLNAME_DISPLAYNAME = 'columnname_displayname';
    const ATTRIBUTE_COLNAME_PUBDATE = 'columnname_pubdate';
    const ATTRIBUTE_COLNAME_LICENSE_CC = 'columnname_license_cc';
    const ATTRIBUTE_COLNAME_LICENSE_FREE = 'columnname_license_free';
    const ATTRIBUTE_COLNAME_FLASH_PUBDATE = 'columnname_flashpubdate';
    const ATTRIBUTE_COLNAME_ACCOUNTING_NONSUBSCRIBER = 'columnname_accounting_nonsubscriber';
    const ATTRIBUTE_COLNAME_ACCOUNTING = 'columnname_accounting';
    const ATTRIBUTE_COLNAME_HEADINGJP = 'columnname_headingjp';
    const ATTRIBUTE_COLNAME_HEADINGEN = 'columnname_headingen';
    const ATTRIBUTE_COLNAME_HEADINGSUBJP = 'columnname_headingsubjp';
    const ATTRIBUTE_COLNAME_HEADINGSUBEN = 'columnname_headingsuben';
    
    /**
     * create xml
     *
     * @param string $xml_str
     * @return bool  
     */
    public function createItemtypeXml(&$xml_str)
    {
        $smartyAssign = $this->Session->getParameter("smartyAssign");
        if($smartyAssign == null){
            // A resource tidy because it is not a call from view action is not obtained. 
            // However, it doesn't shutdown. 
            $RepositoryAction = new RepositoryAction();
            $RepositoryAction->Session = $this->Session;
            $RepositoryAction->setLangResource();
            $smartyAssign = $this->Session->getParameter("smartyAssign");
        }
        $column_item_type = $smartyAssign->getLang("repository_itemtype");
        
        $xml_str = "";
        $xml_str .= "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
                   ."<". self::TAG_ROOT .">\n"
                   ."<". self::TAG_METADATA ." ".self::ATTRIBUTE_COLNAME_ITEMTYPE."=\"".$column_item_type."\" />\n"
                   ."<". self::TAG_ITEMTYPES .">\n";
        // get the mapping information and item type ID of the item type of harvest for default item type other than
        $query = "SELECT item_type_id, mapping_info ".
                 "FROM ".DATABASE_PREFIX."repository_item_type ".
                 "WHERE ( item_type_id < ? OR item_type_id  > ? ) ".
                 "AND is_delete = ? ;";
        $params = array();
        $params[] = 20001;
        $params[] = 20016;
        $params[] = 0;
        $itemtype_mapping = $this->dbAccess->executeQuery($query, $params);
        
        // convert to XML in a format that SCfW can recognize the item type information of all
        //$exportCommon = new ExportCommon($this->Db, $this->Session, $this->TransStartDate);
        $exportCommon = Repository_Components_Factory::getComponent('ExportCommon');
        for($cnt = 0; $cnt < count($itemtype_mapping); $cnt++)
        {
            $DATE = new Date();
            $execute_time = str_replace(":","-",$DATE->getDate());
            $tmp_dir = WEBAPP_DIR. '/uploads/repository/'.$execute_time;
            // get xml of item type information from ExportCommon
            $item_type_xml = "<?xml version=\"1.0\"?>\n".
                             "<export>\n";
            $result = $exportCommon->createItemTypeExportFile($tmp_dir, $itemtype_mapping[$cnt]['item_type_id']);
            if($result === false)
            {
                return false;
            }
            $item_type_xml .= $result['buf'];
            $item_type_xml .= "</export>\n";
            // change xml format into SCfW format by result gotten ExportCommon
            $filter_xml = $this->convertItemtypeXmlToFilterXml($item_type_xml, $itemtype_mapping[$cnt]['item_type_id']);
            if($filter_xml === false)
            {
                return false;
            }
            $xml_str .= "<". self::TAG_ITEMTYPE . " " . 
                        self::ATTRIBUTE_MAPPING_INFO . "=\"" . 
                        $itemtype_mapping[$cnt]['mapping_info'] . 
                        "\">\n" . 
                        $filter_xml .
                        "</" . self::TAG_ITEMTYPE .">\n";
        }
        
        // delete temporary directory
        $exportCommon->removeDirectory($tmp_dir);
        
        $xml_str .= "</" . self::TAG_ITEMTYPES . ">\n".
                    "</" . self::TAG_ROOT . ">\n";
    }
    
    /**
     * convert item type xml to filter xml
     *
     * @param string $item_type_xml
     * @param int $item_type_id
     * @return string  
     */
    private function convertItemtypeXmlToFilterXml($item_type_xml, $item_type_id)
    {
        try
        {
            // parse xml
            $output_array = array();
            $xml_parser = xml_parser_create();
            $result = xml_parse_into_struct($xml_parser, $item_type_xml, $output_array);
            
            if($result === 0)
            {
                $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );
                throw $exception;
            }
            
            // get header string
            $header_str = '';
            //$outputTsv = new RepositoryOutputTSV($this->Db, $this->Session);
            $outputTsv = Repository_Components_Factory::getComponent('RepositoryOutputTSV');
            $header_str = $outputTsv->getTsvHeader($item_type_id);
            // divide header string
            $header_str_array = explode("\t", $header_str);
            
            // create base metadata
            $update_xml = "";
            $update_xml .= "<" . self::TAG_ITEMTYPE_NAME . ">" .
                               $output_array[1]['attributes']['ITEM_TYPE_NAME'] . 
                           "</".self::TAG_ITEMTYPE_NAME.">\n";
            
            $update_xml .= "<" . self::TAG_BASICATTRIBUTES . ">\n" .
                               "<" . self::TAG_TITLE . " " . self::ATTRIBUTE_COLNAME_VALUE . "=\"" . $header_str_array[2] ."\"/>\n" .
                               "<" . self::TAG_TITLEINENGLISH . " " . self::ATTRIBUTE_COLNAME_VALUE . "=\"" . $header_str_array[3] ."\"/>\n" .
                               "<" . self::TAG_LANGUAGE . " " . self::ATTRIBUTE_COLNAME_VALUE . "=\"" . $header_str_array[4] ."\"/>\n" .
                               "<" . self::TAG_KEYWORDS . " " . self::ATTRIBUTE_COLNAME_VALUE . "=\"" . $header_str_array[5] ."\"/>\n" .
                               "<" . self::TAG_KEYWORDSINENGLISH . " " . self::ATTRIBUTE_COLNAME_VALUE . "=\"" . $header_str_array[6] ."\"/>\n" .
                               "<" . self::TAG_PUBLICATIONDATE . " " . self::ATTRIBUTE_COLNAME_VALUE . "=\"" . $header_str_array[7] ."\"/>\n" .
                           "</" . self::TAG_BASICATTRIBUTES . ">\n";
            // get options
            $options_array = array();
            for($cnt = 0; $cnt < count($output_array); $cnt++)
            {
                $tag_name = $output_array[$cnt]['tag'];
                if($tag_name === 'REPOSITORY_ITEM_ATTR_CANDIDATE')
                {
                    $attribute_id = $output_array[$cnt]['attributes']['ATTRIBUTE_ID'];
                    $options = $output_array[$cnt]['attributes']['CANDIDATE_VALUE'];
                    
                    if(isset($options_array[$attribute_id]))
                    {
                        $options_array[$attribute_id] .= '|';
                    }
                    else
                    {
                        $options_array[$attribute_id] = '';
                    }
                    $options_array[$attribute_id] .= $options;
                }
            }
            $update_xml .= "<" . self::TAG_ADDITIONALATTRIBUTES . ">\n";
            // add metadata node
            $header_num = 8;
            for($cnt = 0; $cnt < count($output_array); $cnt++)
            {
                $tag_name = $output_array[$cnt]['tag'];
                if($tag_name === 'REPOSITORY_ITEM_ATTR_TYPE')
                {
                    // Initialize variables.
                    $attribute_name = $output_array[$cnt]['attributes']['ATTRIBUTE_NAME'];
                    $input_type = $output_array[$cnt]['attributes']['INPUT_TYPE'];
                    $is_required = $output_array[$cnt]['attributes']['IS_REQUIRED'];
                    $plural_enable = $output_array[$cnt]['attributes']['PLURAL_ENABLE'];
                    $list_view_enable = $output_array[$cnt]['attributes']['LIST_VIEW_ENABLE'];
                    $line_feed_enable = $output_array[$cnt]['attributes']['LINE_FEED_ENABLE'];
                    $hidden = $output_array[$cnt]['attributes']['HIDDEN'];
                    $junii2_mapping = $output_array[$cnt]['attributes']['JUNII2_MAPPING'];
                    $dublin_core_mapping = $output_array[$cnt]['attributes']['DUBLIN_CORE_MAPPING'];
                    $lom_mapping = $output_array[$cnt]['attributes']['LOM_MAPPING'];
                    $spase_mapping = $output_array[$cnt]['attributes']['SPASE_MAPPING'];
                    $display_lang_type = $output_array[$cnt]['attributes']['DISPLAY_LANG_TYPE'];
                    $attribute_id = $output_array[$cnt]['attributes']['ATTRIBUTE_ID'];
                    
                    if($input_type == 'select')
                    {
                        $input_type = 'pulldownmenu';
                    }
                    else if($input_type == 'radio')
                    {
                        $input_type = 'radiobutton';
                    }
                    else if($input_type == 'biblio_info')
                    {
                        $input_type = 'biblioinfo';
                    }
                    
                    $common_attr_str = '';
                    $common_attr_str .= self::ATTRIBUTE_TYPE."=\"".$input_type."\" ".
                                        self::ATTRIBUTE_REQUIRED."=\"".$this->boolToString($is_required)."\" ".
                                        self::ATTRIBUTE_ALLOWMULTIPLEINPUT."=\"".$this->boolToString($plural_enable)."\" ".
                                        self::ATTRIBUTE_LISTING."=\"".$this->boolToString($list_view_enable)."\" ".
                                        self::ATTRIBUTE_SPECIFYNEWLINE."=\"".$this->boolToString($line_feed_enable)."\" ".
                                        self::ATTRIBUTE_HIDDEN."=\"".$this->boolToString($hidden)."\" ".
                                        self::ATTRIBUTE_JUNII2_MAPPING."=\"".$junii2_mapping."\" ".
                                        self::ATTRIBUTE_DUBLIN_CORE_MAPPING."=\"".$dublin_core_mapping."\" ".
                                        self::ATTRIBUTE_DELIMITERS."=\"|\" ".
                                        self::ATTRIBUTE_DISPLAY_LANG_TYPE."=\"".$display_lang_type."\" ";
                    
                    switch($input_type)
                    {
                        case "text":
                        case "date":
                            $this->addTextDateXmlNode($attribute_name, $common_attr_str, $header_str_array, $header_num, $update_xml);
                            break;
                        case "textarea":
                            $this->addTextareaXmlNode($attribute_name, $common_attr_str, $header_str_array, $header_num, $update_xml);
                            break;
                        case "name":
                            $this->addNameXmlNode($attribute_name, $common_attr_str, $header_str_array, $header_num, $update_xml);
                            break;
                        case "biblioinfo":
                            $this->addBiblioXmlNode($attribute_name, $common_attr_str, $header_str_array, $header_num, $update_xml);
                            break;
                        case "link":
                            $this->addLinkXmlNode($attribute_name, $common_attr_str, $header_str_array, $header_num, $update_xml);
                            break;
                        case "file":
                            $this->addFileXmlNode($attribute_name, $common_attr_str, $header_str_array, $header_num, $update_xml);
                            break;
                        case "file_price":
                            $this->addFilePriceXmlNode($attribute_name, $common_attr_str, $header_str_array, $header_num, $update_xml);
                            break;
                        case "thumbnail":
                            $this->addThumbnailXmlNode($attribute_name, $common_attr_str, $header_str_array, $header_num, $update_xml);
                            break;
                        case "heading":
                            $this->addHeadingXmlNode($attribute_name, $common_attr_str, $header_str_array, $header_num, $update_xml);
                            break;
                        case "checkbox":
                        case "radiobutton":
                        case "pulldownmenu":
                            $this->addCandidateXmlNode($attribute_name, $common_attr_str, $header_str_array, $header_num, $update_xml, $options_array, $attribute_id);
                            break;
                        default:
                            break;
                    }
                }
            }
            $update_xml .= "</" . self::TAG_ADDITIONALATTRIBUTES . ">\n";
            // return updated xml
            return $update_xml;
        
        }
        catch(RepositoryException $Exception)
        {
            return false;
        }
    }
    
    /**
     * add xml node for 'text' and 'date'
     *
     * @param string $attribute_name
     * @param string $common_attr_str
     * @param array $header_str_array
     * @param int $header_num
     * @param string $update_xml
     */
    private function addTextDateXmlNode($attribute_name, $common_attr_str, $header_str_array, &$header_num, &$update_xml)
    {
        $update_xml .= "<".self::TAG_ADDITIONALATTRIBUTE." ".
                           self::ATTRIBUTE_COLNAME_VALUE."=\"".$header_str_array[$header_num]."\" ".
                           $common_attr_str.">\n".
                       "<".self::TAG_ADD_ATTR_NAME.">".
                       $attribute_name.
                       "</".self::TAG_ADD_ATTR_NAME.">\n".
                       "</".self::TAG_ADDITIONALATTRIBUTE.">\n";
        $header_num++;
        return true;
    }
    
    /**
     * add xml node for 'textarea'
     *
     * @param string $attribute_name
     * @param string $common_attr_str
     * @param array $header_str_array
     * @param int $header_num
     * @param string $update_xml
     */
    private function addTextareaXmlNode($attribute_name, $common_attr_str, $header_str_array, &$header_num, &$update_xml)
    {
        $update_xml .= "<".self::TAG_ADDITIONALATTRIBUTE." ".
                           self::ATTRIBUTE_COLNAME_VALUE."=\"".$attribute_name."\" ".
                           $common_attr_str.">\n".
                       "<".self::TAG_ADD_ATTR_NAME.">".
                       $attribute_name.
                       "</".self::TAG_ADD_ATTR_NAME.">\n".
                       "</".self::TAG_ADDITIONALATTRIBUTE.">\n";
        
        $query = "SELECT MAX(attr.count) ".
                 "FROM ( ".
                 "SELECT item_id, item_no, attribute_id, count(attribute_no) as count ".
                 "FROM ".DATABASE_PREFIX."repository_item_attr ".
                 "WHERE ( `item_type_id`, `attribute_id` ) IN ".
                 "( ".
                 "SELECT item_type_id, attribute_id ".
                 "FROM ".DATABASE_PREFIX."repository_item_attr_type ".
                 "WHERE attribute_name = ? ".
                 "AND input_type = 'textarea' ".
                 ") ".
                 "GROUP BY item_id, item_no, attribute_id ".
                 ") AS attr ; ";
        $params = array();
        $params[] = $attribute_name;
        $result = $this->dbAccess->executeQuery($query, $params);
        if($result[0]['MAX(attr.count)'] == null)
        {
            $result[0]['MAX(attr.count)'] = 0;
        }
        $additional_num = intval($result[0]['MAX(attr.count)']);
        $header_num += $additional_num;
    }
    
    /**
     * add xml node for 'name'
     *
     * @param string $attribute_name
     * @param string $common_attr_str
     * @param array $header_str_array
     * @param int $header_num
     * @param string $update_xml
     */
    private function addNameXmlNode($attribute_name, $common_attr_str, $header_str_array, &$header_num, &$update_xml)
    {
        $update_xml .= "<".self::TAG_ADDITIONALATTRIBUTE." ".
                           self::ATTRIBUTE_ISFAMILYGIVENCONNECT."=\"true\" ".
                           self::ATTRIBUTE_COLNAME_FAMILY."=\"".$header_str_array[$header_num]."\" ".
                           self::ATTRIBUTE_COLNAME_FAMILYRUBY."=\"".$header_str_array[$header_num+1]."\" ".
                           self::ATTRIBUTE_COLNAME_EMAILADDRESS."=\"".$header_str_array[$header_num+2]."\" ".
                           self::ATTRIBUTE_COLNAME_AUTHORIDS."=\"".$header_str_array[$header_num+3]."\" ".
                           $common_attr_str.">\n".
                       "<".self::TAG_ADD_ATTR_NAME.">".
                       $attribute_name.
                       "</".self::TAG_ADD_ATTR_NAME.">\n".
                       "</".self::TAG_ADDITIONALATTRIBUTE.">\n";
        $header_num += 3;
        
        $query = "SELECT COUNT(prefix_id) AS CNT ".
                 "FROM ".DATABASE_PREFIX."repository_external_author_id_prefix ".
                 "WHERE prefix_id > ? ".
                 "AND is_delete = ? ; ";
        $params = array();
        $params[] = 0;
        $params[] = 0;
        $result = $this->dbAccess->executeQuery($query, $params);
        
        $header_num += $result[0]['CNT'];
    }
    
    /**
     * add xml node for 'biblioinfo'
     *
     * @param string $attribute_name
     * @param string $common_attr_str
     * @param array $header_str_array
     * @param int $header_num
     * @param string $update_xml
     */
    private function addBiblioXmlNode($attribute_name, $common_attr_str, $header_str_array, &$header_num, &$update_xml)
    {
        $update_xml .= "<".self::TAG_ADDITIONALATTRIBUTE." ".
                           self::ATTRIBUTE_ISSTARTENDPAGECONNECT."=\"false\" ".
                           self::ATTRIBUTE_COLNAME_BIBLIONAME."=\"".$header_str_array[$header_num]."\" ".
                           self::ATTRIBUTE_COLNAME_BIBLIONAMEENGLISH."=\"".$header_str_array[$header_num+1]."\" ".
                           self::ATTRIBUTE_COLNAME_VOLUME."=\"".$header_str_array[$header_num+2]."\" ".
                           self::ATTRIBUTE_COLNAME_ISSUE."=\"".$header_str_array[$header_num+3]."\" ".
                           self::ATTRIBUTE_COLNAME_STARTPAGE."=\"".$header_str_array[$header_num+4]."\" ".
                           self::ATTRIBUTE_COLNAME_ENDPAGE."=\"".$header_str_array[$header_num+5]."\" ".
                           self::ATTRIBUTE_COLNAME_DATEOFISSUED."=\"".$header_str_array[$header_num+6]."\" ".
                           $common_attr_str.">\n".
                       "<".self::TAG_ADD_ATTR_NAME.">".
                       $attribute_name.
                       "</".self::TAG_ADD_ATTR_NAME.">\n".
                       "</".self::TAG_ADDITIONALATTRIBUTE.">\n";
        $header_num += 7;
    }
    
    /**
     * add xml node for 'link'
     *
     * @param string $attribute_name
     * @param string $common_attr_str
     * @param array $header_str_array
     * @param int $header_num
     * @param string $update_xml
     */
    private function addLinkXmlNode($attribute_name, $common_attr_str, $header_str_array, &$header_num, &$update_xml)
    {
        $update_xml .= "<".self::TAG_ADDITIONALATTRIBUTE." ".
                           self::ATTRIBUTE_COLNAME_LINKNAME."=\"".$header_str_array[$header_num]."\" ".
                           self::ATTRIBUTE_COLNAME_LINKURL."=\"".$header_str_array[$header_num+1]."\" ".
                           $common_attr_str.">\n".
                       "<".self::TAG_ADD_ATTR_NAME.">".
                       $attribute_name.
                       "</".self::TAG_ADD_ATTR_NAME.">\n".
                       "</".self::TAG_ADDITIONALATTRIBUTE.">\n";
        $header_num += 2;
    }
    
    /**
     * add xml node for 'file'
     *
     * @param string $attribute_name
     * @param string $common_attr_str
     * @param array $header_str_array
     * @param int $header_num
     * @param string $update_xml
     */
    private function addFileXmlNode($attribute_name, $common_attr_str, $header_str_array, &$header_num, &$update_xml)
    {
        $update_xml .= "<".self::TAG_ADDITIONALATTRIBUTE." ".
                           self::ATTRIBUTE_DISPLAYTYPE."=\"detail\" ".
                           self::ATTRIBUTE_COLNAME_FILENAME."=\"".$header_str_array[$header_num]."\" ".
                           self::ATTRIBUTE_COLNAME_DISPLAYNAME."=\"".$header_str_array[$header_num+1]."\" ".
                           self::ATTRIBUTE_COLNAME_PUBDATE."=\"".$header_str_array[$header_num+2]."\" ".
                           self::ATTRIBUTE_COLNAME_FLASH_PUBDATE."=\"".$header_str_array[$header_num+3]."\" ".
                           self::ATTRIBUTE_COLNAME_LICENSE_CC."=\"".$header_str_array[$header_num+4]."\" ".
                           self::ATTRIBUTE_COLNAME_LICENSE_FREE."=\"".$header_str_array[$header_num+5]."\" ".
                           $common_attr_str.">\n".
                       "<".self::TAG_ADD_ATTR_NAME.">".
                       $attribute_name.
                       "</".self::TAG_ADD_ATTR_NAME.">\n".
                       "</".self::TAG_ADDITIONALATTRIBUTE.">\n";
        $header_num += 6;
    }
    
    /**
     * add xml node for 'file_price'
     *
     * @param string $attribute_name
     * @param string $common_attr_str
     * @param array $header_str_array
     * @param int $header_num
     * @param string $update_xml
     */
    private function addFilePriceXmlNode($attribute_name, $common_attr_str, $header_str_array, &$header_num, &$update_xml)
    {
        $update_xml .= "<".self::TAG_ADDITIONALATTRIBUTE." ".
                           self::ATTRIBUTE_DISPLAYTYPE."=\"detail\" ".
                           self::ATTRIBUTE_COLNAME_FILENAME."=\"".$header_str_array[$header_num]."\" ".
                           self::ATTRIBUTE_COLNAME_DISPLAYNAME."=\"".$header_str_array[$header_num+1]."\" ".
                           self::ATTRIBUTE_COLNAME_PUBDATE."=\"".$header_str_array[$header_num+2]."\" ".
                           self::ATTRIBUTE_COLNAME_FLASH_PUBDATE."=\"".$header_str_array[$header_num+3]."\" ".
                           self::ATTRIBUTE_COLNAME_LICENSE_CC."=\"".$header_str_array[$header_num+4]."\" ".
                           self::ATTRIBUTE_COLNAME_LICENSE_FREE."=\"".$header_str_array[$header_num+5]."\" ".
                           self::ATTRIBUTE_COLNAME_ACCOUNTING_NONSUBSCRIBER."=\"".$header_str_array[$header_num+6]."\" ".
                           self::ATTRIBUTE_COLNAME_ACCOUNTING."=\"".$header_str_array[$header_num+7]."\" ".
                           $common_attr_str.">\n".
                       "<".self::TAG_ADD_ATTR_NAME.">".
                       $attribute_name.
                       "</".self::TAG_ADD_ATTR_NAME.">\n".
                       "</".self::TAG_ADDITIONALATTRIBUTE.">\n";
        $header_num += 8;
    }
    
    /**
     * add xml node for 'thumbnail'
     *
     * @param string $attribute_name
     * @param string $common_attr_str
     * @param array $header_str_array
     * @param int $header_num
     * @param string $update_xml
     */
    private function addThumbnailXmlNode($attribute_name, $common_attr_str, $header_str_array, &$header_num, &$update_xml)
    {
        $update_xml .= "<".self::TAG_ADDITIONALATTRIBUTE." ".
                           self::ATTRIBUTE_COLNAME_FILENAME."=\"".$header_str_array[$header_num]."\" ".
                           $common_attr_str.">\n".
                       "<".self::TAG_ADD_ATTR_NAME.">".
                       $attribute_name.
                       "</".self::TAG_ADD_ATTR_NAME.">\n".
                       "</".self::TAG_ADDITIONALATTRIBUTE.">\n";
        $header_num++;
    }
    
    /**
     * add xml node for 'heading'
     *
     * @param string $attribute_name
     * @param string $common_attr_str
     * @param array $header_str_array
     * @param int $header_num
     * @param string $update_xml
     */
    private function addHeadingXmlNode($attribute_name, $common_attr_str, $header_str_array, &$header_num, &$update_xml)
    {
        $update_xml .= "<".self::TAG_ADDITIONALATTRIBUTE." ".
                           self::ATTRIBUTE_COLNAME_HEADINGJP."=\"".$header_str_array[$header_num]."\" ".
                           self::ATTRIBUTE_COLNAME_HEADINGEN."=\"".$header_str_array[$header_num+1]."\" ".
                           self::ATTRIBUTE_COLNAME_HEADINGSUBJP."=\"".$header_str_array[$header_num+2]."\" ".
                           self::ATTRIBUTE_COLNAME_HEADINGSUBEN."=\"".$header_str_array[$header_num+3]."\" ".
                           $common_attr_str.">\n".
                       "<".self::TAG_ADD_ATTR_NAME.">".
                       $attribute_name.
                       "</".self::TAG_ADD_ATTR_NAME.">\n".
                       "</".self::TAG_ADDITIONALATTRIBUTE.">\n";
        $header_num += 4;
    }
    
    /**
     * add xml node for 'checkbox' and 'radiobutton' and 'pulldownmenu'
     *
     * @param string $attribute_name
     * @param string $common_attr_str
     * @param array $header_str_array
     * @param int $header_num
     * @param string $update_xml
     * @param array $options_array
     * @param int $attribute_id
     */
    private function addCandidateXmlNode($attribute_name, $common_attr_str, $header_str_array, &$header_num, &$update_xml, $options_array, $attribute_id)
    {
        $update_xml .= "<".self::TAG_ADDITIONALATTRIBUTE." ".
                           self::ATTRIBUTE_COLNAME_VALUE."=\"".$header_str_array[$header_num]."\" ".
                           $common_attr_str.">\n".
                       "<".self::TAG_ADD_ATTR_NAME.">".
                       $attribute_name.
                       "</".self::TAG_ADD_ATTR_NAME.">\n";
        if(isset($options_array[$attribute_id]) && strlen($options_array[$attribute_id]) > 0)
        {
            $update_xml .=  "<".self::TAG_ADD_ATTR_CANDIDATES.">".
                            $options_array[$attribute_id].
                            "</".self::TAG_ADD_ATTR_CANDIDATES.">\n";
        }
        $update_xml .= "</".self::TAG_ADDITIONALATTRIBUTE.">\n";
        $header_num++;
    }
    
    /**
     * call superclass' __construct
     *
     * @param var $session Session
     * @param var $dbAccess Db
     * @param string $transStartDate TransStartDate
     */
    public function __construct($session, $db, $transStartDate)
    {
        parent::__construct($session, $db, $transStartDate);
    }
    
    /**
     * bool to string
     *
     * @param bool $bool
     * @return string $bool_str
     */
    private function boolToString($bool)
    {
        if($bool)
        {
            return 'true';
        }
        else
        {
            return 'false';
        }
    }
}

?>
