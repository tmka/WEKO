<?php
// --------------------------------------------------------------------
//
// $Id: RepositoryOutputFilter.class.php 41901 2014-09-22 12:39:14Z tatsuya_koyasu $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics,
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/**
 * output format filter class
 *
 * return format string. when not allow format, return '';
 *
 */
class RepositoryOutputFilter
{
    // Add JuNii2Ver3 R.Matsuura 2013/09/19
    const RESEACHER_RESOLVER_ID_PREFIX = "http://rns.nii.ac.jp/nr/";

    // Mod name delimiter changes to comma T.Koyasu 2014/09/12 --start--
    const NAME_DELIMITER_IS_SPACE = 1;
    const NAME_DELIMITER_IS_COMMA = 2;
    // Add name delimiter changes to comma T.Koyasu 2014/09/12 --end--


    /**
     * format string
     *  to lower
     *  " +" -> " "
     *
     * @param string $str
     * @return string
     */
    static public function string($str)
    {
        if($str == null)
        {
            return "";
        }
        $str = str_replace("　", " ", $str);
        $str = preg_replace("/ +/", " ", $str);
        $str = preg_replace("/ +/", " ", $str);
        $str = strtolower($str);
        return $str;
    }

    /**
     * language filter
     *   allow language is item language select data.
     * ISO639
     *
     *
     * @param string $str
     * @return string
     */
    static public function language($str)
    {
        $str = self::string($str);
        switch($str)
        {
            case 'japanese':
            case 'ja':
            case 'jpn':
            case 'jp':
                $str = RepositoryConst::ITEM_LANG_JA;
                break;
            case 'english':
            case 'en':
            case 'eng':
                $str = RepositoryConst::ITEM_LANG_EN;
                break;
            case 'fr':
            case 'fre':
            case 'fra':
                $str = RepositoryConst::ITEM_LANG_FR;
                break;
            case 'it':
            case 'ita':
                $str = RepositoryConst::ITEM_LANG_IT;
                break;
            case 'de':
            case 'ger':
            case 'deu':
                $str = RepositoryConst::ITEM_LANG_DE;
                break;
            case 'es':
            case 'spa':
                $str = RepositoryConst::ITEM_LANG_ES;
                break;
            case 'zh':
            case 'zho':
                $str = RepositoryConst::ITEM_LANG_ZH;
                break;
            case 'ru':
            case 'rus':
                $str = RepositoryConst::ITEM_LANG_RU;
                break;
            case 'la':
            case 'lat':
                $str = RepositoryConst::ITEM_LANG_LA;
                break;
            case 'ms':
            case 'may':
            case 'msa':
                $str = RepositoryConst::ITEM_LANG_MS;
                break;
            case 'eo':
            case 'epo':
                $str = RepositoryConst::ITEM_LANG_EO;
                break;
            case 'ar':
            case 'ara':
                $str = RepositoryConst::ITEM_LANG_AR;
                break;
            case 'el':
            case 'gre':
            case 'ell':
                $str = RepositoryConst::ITEM_LANG_EL;
                break;
            case 'ko':
            case 'kor':
                $str = RepositoryConst::ITEM_LANG_KO;
                break;
            default:
                $str = RepositoryConst::ITEM_LANG_OTHER;
                break;
        }
        return $str;
    }

    /**
     * date format
     * allow format YYYY-MM-DD or YYYY-MM or YYYY
     *
     * @param string $str
     * @return string
     */
    static public function date($str)
    {
        $str = self::string($str);
        $str = str_replace("/", "-", $str);
        $year = '';
        $month = '';
        $day = '';

        // separate date
        if(preg_match("/^[0-9]+$/", $str)==1)
        {
            $len = strlen($str);
            if($len < 5)
            {
                $year = $str;
            }
            else if($len < 6)
            {
                $year  = substr($str, 0, 4);
                $month = self::month(substr($str, 4));
            }
            else if($len < 8)
            {
                $year  = substr($str, 0, 4);
                $month = self::month(substr($str, 4, 2));
                $day   = self::day(substr($str, 6, 2));
            }
        }
        else if(preg_match("/^[0-9\-]+$/", $str)==1)
        {
            $tmp = explode("-", $str);
            if(count($tmp) == 1)
            {
                $year = $tmp[0];
            }
            else if(count($tmp) == 2)
            {
                $year  = $tmp[0];
                $month = self::month($tmp[1]);
            }
            else if(count($tmp) == 3)
            {
                $year  = $tmp[0];
                $month = self::month($tmp[1]);
                $day   = self::day($tmp[2]);
            }
        }

        // set date
        $str = '';
        if(strlen($year) > 0)
        {
            $str = $year;
            if(strlen($month) > 0)
            {
                $str .= "-$month";
                if(strlen($day) > 0)
                {
                    $str .= "-$day";
                }
            }
        }

        return $str;
    }

    /**
     * month format
     * allow format MM
     *
     * @param string $str
     * @return string month
     */
    static public function month($str)
    {
        $month = intval($str);
        if(0 < $month && $month < 13)
        {
            $str = sprintf("%02d", $month);
        }
        else
        {
            $str = '';
        }
        return $str;
    }

    /**
     * month format
     * allow format DD
     *
     * @param string $str
     * @return string day
     */
    static public function day($str)
    {
        $day = intval($str);
        if(0 < $day && $day < 32)
        {
            $str = sprintf("%02d", $day);
        }
        else
        {
            $str = '';
        }
        return $str;
    }

    /**
     * インプットタイプに応じた出力文字列を作成
     *
     * @param string $inputType
     * @param string $itemAttr
     * @param int $biblioFormat 書誌情報の出力フォーマット デフォルト「false」
     *                           1: $jtitle = $jtitle_en, $volume($issue), $spage-$epage($dateofissued)
     *                           2: $jtitle = $jtitle_en||$volume||$issue||$spage||$epage||$dateofissued
     *                           3: $jtitle, $volume($issue), $spage-$epage($dateofissued)
     *                              or
     *                              $jtitle_en, $volume($issue), $spage-$epage($dateofissued)
     * @param int $nameFormat 名前出力のフォーマット
     *                           1: Taro Yamada     スペース
     *                           2: 山田, 太郎      カンマとスペース
     * @return string $this->RepositoryAction->forXmlChange実施済のattribute_value文字列
     */
    static public function attributeValue($itemAttrType, $itemAttr, $biblioFormat=1, $nameFormat=1)
    {
        $attrName  = $itemAttrType[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_NAME];
        $inputType = $itemAttrType[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_IMPUT_TYPE];
        $language  = $itemAttrType[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_DISPLAY_LANG_TYPE];
        if($language != RepositoryConst::ITEM_ATTR_TYPE_LANG_JA && $language != RepositoryConst::ITEM_ATTR_TYPE_LANG_EN)
        {
            $language = "";
        }
        $value = '';
        switch ($inputType)
        {
            case RepositoryConst::ITEM_ATTR_TYPE_TEXT:
            case RepositoryConst::ITEM_ATTR_TYPE_TEXTAREA:
            case RepositoryConst::ITEM_ATTR_TYPE_CHECKBOX:
            case RepositoryConst::ITEM_ATTR_TYPE_RADIO:
            case RepositoryConst::ITEM_ATTR_TYPE_SELECT:
            case RepositoryConst::ITEM_ATTR_TYPE_DATE:
                $value = $itemAttr[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_ATTRIBUTE_VALUE];
                break;
            case RepositoryConst::ITEM_ATTR_TYPE_LINK:
                $value = explode("|", $itemAttr[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_ATTRIBUTE_VALUE]);
                if(isset($value[0]))
                {
                    $value = $value[0];
                }
                else
                {
                    $value = '';
                }
                break;
            case RepositoryConst::ITEM_ATTR_TYPE_HEADING:
                $value = $itemAttr[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_ATTRIBUTE_VALUE];
                $value = preg_replace("/\|+/", '|', $value);
                $value = preg_replace("/^\|/", '', $value);
                $value = preg_replace("/\|$/", '', $value);
                if($value == '|'){
                    // when value null, hidden.
                    $value = '';
                }
                if(strlen($value) == 0){
                    // when strlen == 0, hidden.
                    $value = '';
                }
                break;
            case RepositoryConst::ITEM_ATTR_TYPE_NAME:
                if($language == RepositoryConst::ITEM_ATTR_TYPE_LANG_EN)
                {
                    // 名 性
                    $value = "";
                    if(strlen($itemAttr[RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_NAME]) > 0)
                    {
                        $value .= $itemAttr[RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_NAME];
                    }
                    if(strlen($itemAttr[RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_FAMILY]) > 0)
                    {
                        if(strlen($value) > 0)
                        {
                            // Mod name delimiter changes to comma T.Koyasu 2014/09/12 --start--
                            if ($nameFormat == self::NAME_DELIMITER_IS_COMMA) {
                                $value .= ", ";
                            }
                            else {
                                $value .= " ";
                            }
                            // Mod name delimiter changes to comma T.Koyasu 2014/09/12 --end--
                        }
                        $value .= $itemAttr[RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_FAMILY];
                    }
                }
                else
                {
                    // 姓 名
                    $value = "";
                    if(strlen($itemAttr[RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_FAMILY]) > 0)
                    {
                        $value .= $itemAttr[RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_FAMILY];
                    }
                    if(strlen($itemAttr[RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_NAME]) > 0)
                    {
                        if(strlen($value) > 0)
                        {
                            // Mod name delimiter changes to comma T.Koyasu 2014/09/12 --start--
                            if ($nameFormat == self::NAME_DELIMITER_IS_COMMA) {
                                $value .= ", ";
                            }
                            else {
                                $value .= " ";
                            }
                            // Mod name delimiter changes to comma T.Koyasu 2014/09/12 --end--
                        }
                        $value .= $itemAttr[RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_NAME];
                    }
                }
                if(strlen($value) == 1)
                {
                    // 値が空
                    $value = '';
                }
                break;
            case RepositoryConst::ITEM_ATTR_TYPE_THUMBNAIL:
                $value = BASE_URL.'/?action=repository_action_common_download'.
                         '&item_id='.$itemAttr[RepositoryConst::DBCOL_REPOSITORY_THUMB_ITEM_ID].
                         '&item_no='.$itemAttr[RepositoryConst::DBCOL_REPOSITORY_THUMB_ITEM_NO].
                         '&attribute_id='.$itemAttr[RepositoryConst::DBCOL_REPOSITORY_THUMB_ATTR_ID].
                         '&file_no='.$itemAttr[RepositoryConst::DBCOL_REPOSITORY_THUMB_FILE_NO].
                         '&img=true';
                break;
            case RepositoryConst::ITEM_ATTR_TYPE_FILEPRICE:
            case RepositoryConst::ITEM_ATTR_TYPE_FILE:
                $value = BASE_URL.'/?action=repository_action_common_download'.
                         '&item_id='.$itemAttr[RepositoryConst::DBCOL_REPOSITORY_THUMB_ITEM_ID].
                         '&item_no='.$itemAttr[RepositoryConst::DBCOL_REPOSITORY_THUMB_ITEM_NO].
                         '&attribute_id='.$itemAttr[RepositoryConst::DBCOL_REPOSITORY_THUMB_ATTR_ID].
                         '&file_no='.$itemAttr[RepositoryConst::DBCOL_REPOSITORY_THUMB_FILE_NO];
                break;
            case RepositoryConst::ITEM_ATTR_TYPE_BIBLIOINFO:
                $jtitle       = $itemAttr[RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_BIBLIO_NAME];
                $jtitleEn     = $itemAttr[RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_BIBLIO_NAME_ENGLISH];
                $volume       = $itemAttr[RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_VOLUME];
                $issue        = $itemAttr[RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_ISSUE];
                $spage        = $itemAttr[RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_START_PAGE];
                $epage        = $itemAttr[RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_END_PAGE];
                $dateofissued = $itemAttr[RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_DATE_OF_ISSUED];

                // set jtitle
                if($biblioFormat == 3)
                {
                    if($this->Session->getParameter("_lang") == RepositoryConst::ITEM_ATTR_TYPE_LANG_JA)
                    {
                        if(strlen($jtitle) > 0)
                        {
                            $jtitle = $jtitle;
                        } else {
                            $jtitle = $jtitleEn;
                        }
                    } else {
                        if(strlen($jtitleEn) > 0)
                        {
                            $jtitle = $jtitleEn;
                        } else {
                            $jtitle = $jtitle;
                        }
                    }
                }
                else
                {
                    if(strlen($jtitle) > 0)
                    {
                        if(strlen($jtitleEn) > 0)
                        {
                            $jtitle = $jtitle.' = '.$jtitleEn;
                        }
                    }
                    else
                    {
                        $jtitle = $jtitleEn;
                    }
                }

                if($biblioFormat == 1 || $biblioFormat == 3)
                {
                    // format : $jtitle = $jtitle_en, $volume($issue), $spage-$epage($dateofissued)

                    $value .= $jtitle;
                    if(strlen($volume) > 0)
                    {
                        if(strlen($value) > 0)
                        {
                            $value .= ", ";
                        }
                        $value .= $volume;
                    }

                    if(strlen($issue) > 0)
                    {
                        if(strlen($value) > 0 && strlen($volume) == 0)
                        {
                            $value .= ", ";
                        }
                        $value .= '('.$issue.')';
                    }
                    $page = "";
                    if(strlen($spage) > 0)
                    {
                        $page .= $spage;
                    }
                    if(strlen($epage) > 0)
                    {
                        if(strlen($page) > 0)
                        {
                            $page .= '-';
                        }
                        $page .= $epage;
                    }
                    if(strlen($page) > 0)
                    {
                        if(strlen($value) > 0)
                        {
                            $value .= ", ";
                        }
                        $value .= $page;
                    }

                    if(strlen($dateofissued) > 0)
                    {
                        if(strlen($value) > 0 && strlen($page) == 0)
                        {
                            $value .= ', ';
                        }
                        $value .= '('.$dateofissued.')';
                    }
                }
                else
                {
                    // format : $jtitle = $jtitle_en||$volume||$issue||$spage||$epage||$dateofissued
                    $jtitle       = preg_replace("/\|+/", "|", $jtitle);
                    $volume       = preg_replace("/\|+/", "|", $volume);
                    $issue        = preg_replace("/\|+/", "|", $issue);
                    $spage        = preg_replace("/\|+/", "|", $spage);
                    $epage        = preg_replace("/\|+/", "|", $epage);
                    $dateofissued = preg_replace("/\|+/", "|", $dateofissued);
                    $value = $jtitle.'||'.$volume.'||'.$issue.'||'.$spage.'||'.$epage.'||'.$dateofissued;
                }
                break;
            case RepositoryConst::ITEM_ATTR_TYPE_SUPPLE:
                // fix output supple data 2013/08/01 Y.Nakao --start--
                $value = $itemAttr['uri'];
                break;
                // fix output supple data 2013/08/01 Y.Nakao --end--
            default:
                $value = '';
                break;
        }

        // 不備文字を除去
        if(RepositoryOutputFilter::string($value) == " ")
        {
            $value = '';
        }

        return $value;
    }

    // Add JuNii2Ver3 R.Matsuura 2013/09/19 --start--
    /**
     * check format id attribution
     *
     * @param array $authorIdArray
     */
    static public function creatorId($authorIdArray)
    {
        if(isset($authorIdArray["prefix_id"]) && $authorIdArray["prefix_id"] == 2)
        {
            if(preg_match("/^[0-9]+$/", $authorIdArray["suffix"]))
            {
                $URI = self::RESEACHER_RESOLVER_ID_PREFIX . $authorIdArray["suffix"];
                return $URI;
            }
            else
            {
                if(substr($authorIdArray["suffix"], 0, strlen(self::RESEACHER_RESOLVER_ID_PREFIX)) == self::RESEACHER_RESOLVER_ID_PREFIX)
                {
                    return $authorIdArray["suffix"];
                }
                else
                {
                    return "";
                }
            }
        }
        else
        {
            return "";
        }
    }

    /**
     * return license notation from file infomation
     *
     * @param array $fileInfo
     */
    static public function fileLicence($fileInfo)
    {
        $licenseNotation = "";
        if($fileInfo["license_id"] == 0)
        {
            $licenseNotation = $fileInfo["license_notation"];
        }
        else
        {
            if($fileInfo["license_id"] == RepositoryConst::LICENCE_ID_CC_BY)
            {
                $licenseNotation = RepositoryConst::LICENCE_STR_CC_BY;
            }
            else if($fileInfo["license_id"] == RepositoryConst::LICENCE_ID_CC_BY_SA)
            {
                $licenseNotation = RepositoryConst::LICENCE_STR_CC_BY_SA;
            }
            else if($fileInfo["license_id"] == RepositoryConst::LICENCE_ID_CC_BY_ND)
            {
                $licenseNotation = RepositoryConst::LICENCE_STR_CC_BY_ND;
            }
            else if($fileInfo["license_id"] == RepositoryConst::LICENCE_ID_CC_BY_NC)
            {
                $licenseNotation = RepositoryConst::LICENCE_STR_CC_BY_NC;
            }
            else if($fileInfo["license_id"] == RepositoryConst::LICENCE_ID_CC_BY_NC_SA)
            {
                $licenseNotation = RepositoryConst::LICENCE_STR_CC_BY_NC_SA;
            }
            else if($fileInfo["license_id"] == RepositoryConst::LICENCE_ID_CC_BY_NC_ND)
            {
                $licenseNotation = RepositoryConst::LICENCE_STR_CC_BY_NC_ND;
            }
        }
        return $licenseNotation;
    }
    // Add JuNii2Ver3 R.Matsuura 2013/09/19 --end--

    // Add OpenDepo S.Arata 2013/12/20 --start--
    /**
     * @param string $date 0埋めしていない日付（YYYY-M-D形式）
     * @$return 0埋めした日付（YYYY-MM-DD形式）
     *          -1:日付フォーマットエラー
     *          -2:日付範囲エラー
     */
    static public function zeroPaddingDate($date) {
        // 日付を年月日に分割する
        $divideDate = explode("-", $date);

        // フォーマットチェック
        if(count($divideDate) != 3){
            return -1;
        }

        // 日付チェック
        if( checkdate($divideDate[1], $divideDate[2], $divideDate[0]) == false) {
            return -2;
        }
        // 月、日の0埋めした値を返す
        return $divideDate[0]."-".sprintf("%02d",$divideDate[1])."-".sprintf("%02d",$divideDate[2]);
    }
    // Add OpenDepo S.Arata 2013/12/20 --end--

    // Add LIDO S.Suzuki 2014/05/09 --start--
    /**
     * exclusive reserved words
     *
     * @param string $str
     * @return string
     */
    static public function exclusiveReservedWords($str)
    {
        if ($str === RepositoryConst::BLANK_WORD) {
            return '';
        }
        else {
            return $str;
        }
    }
    // Add LIDO S.Suzuki 2014/05/09 --end--

    // Add json format escape method T.Koyasu 2014/09/12 --start--
    /**
     * escape JSON
     *
     * @param array $index_data
     */
    static public function escapeJSON($str, $lineFlg = false){

        $str = str_replace("\\", "\\\\", $str);
        $str = str_replace('"', '\"', $str);
        if($lineFlg){
            $str = str_replace("\r\n", "\n", $str);
            $str = str_replace("\n", "\\n", $str);
        }
        $str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');

        return $str;
    }
    // Add json format escape method T.Koyasu 2014/09/12 --end--
}

/**
 * output filter for format:DublinCore class
 *
 */
class RepositoryOutputFilterDublinCore extends RepositoryOutputFilter
{
}


class RepositoryOutputFilterJuNii2 extends RepositoryOutputFilter
{
    // ---------------------------------------------
    // Const
    // ---------------------------------------------
    // Prefix
    const DOI_PREFIX = "info:doi/";
    const PMID_PREFIX = "info:pmid/";
    const NAID_PREFIX = "http://ci.nii.ac.jp/naid/";
    const ICHUSHI_PREFIX = "http://search.jamas.or.jp/link/ui/";

    /**
     * ISSN : \d{4}\-?\d{3}[\dXx]
     *
     * @param string $str
     */
    static public function issn($str)
    {
        $str = RepositoryOutputFilter::string($str);
        if(preg_match("/\d{4}\-?\d{3}[\dXx]/", $str) == 0)
        {
            $str = '';
        }
        return $str;
    }

    /**
     * textversion
     *
     * @param string $str
     * @return string
     */
    static public function textversion($str)
    {
        $str = RepositoryOutputFilter::string($str);
        if($str != 'author' && $str != 'publisher' && $str != 'etd' && $str != 'none')
        {
            $str = '';
        }
        //etdの場合、小文字から大文字に変換
        if($str == 'etd'){
            $str = strtoupper($str);
        }
        return $str;
    }

    /**
     * convert language notation to RFC3066 format
     *
     * @param string $strLang
     * @return string
     */
    static public function languageToRFC($strLang)
    {
        $englishRfcArray = array("en-US", "es-US", "haw", "ik", "nv", "oj", "yi");
        $frenchRfcArray = array("br", "co", "de-FR", "fr-FR", "oc");
        $italianRfcArray = array("co", "de-IT", "fr-IT", "it-IT", "sc");
        $germanRfcArray = array("da-DE", "de-1901", "de-1996", "de-AT-1901", "de-AT-1996",
                                "de-CH-1901", "de-CH-1996", "de-DE", "de-DE-1901", "de-DE-1996",
                                "dsb", "fy-DE", "hsb", "lb", "nds", "wen", "yi");
        $spanishRfcArray = array("an", "ca", "es-ES", "eu", "gl");
        $chineseRfcArray = array("bo", "i-hak", "ii", "za", "zh-CN", "zh-gan", "zh-guoyu", "zh-hakka",
                                "zh-Hans", "zh-Hant", "zh-wuu", "zh-xiang", "zh-yue");
        $russianRfcArray = array("av", "ba", "ce", "cu", "cv", "kv", "os", "ru-RU", "tt", "yi");

        if($strLang == "japanese" || $strLang == "jpn")
        {
            $strLang = "ja";
            return $strLang;
        }
        else if($strLang == "english" || $strLang == "en" || $strLang == "eng")
        {
            $strLang = "en";
            return $strLang;
        }
        else if($strLang === "ja" ||
                in_array($strLang, $englishRfcArray) ||
                in_array($strLang, $frenchRfcArray) ||
                in_array($strLang, $italianRfcArray) ||
                in_array($strLang, $germanRfcArray) ||
                in_array($strLang, $spanishRfcArray) ||
                in_array($strLang, $chineseRfcArray) ||
                in_array($strLang, $russianRfcArray) ||
                $strLang === "ar-AE" ||
                $strLang === "el-GR" ||
                $strLang === "ko-KP")
        {
            return $strLang;
        }
        else
        {
            return "";
        }
    }

    /**
     * convert language notation to ISO-639 format
     *
     * @param string $strLang
     * @return string
     */
    static public function languageToISO($strLang)
    {
        $japaneseArray = array("japanese", "ja", "jpn");
        $englishArray = array("english", "en", "eng");
        $frenchArray = array("fr", "fre", "fra");
        $italianArray = array("it", "ita");
        $germanArray = array("de", "ger", "deu");
        $spanishArray = array("es", "spa");
        $chineseArray = array("zh", "zho");
        $russianArray = array("ru", "rus");
        $latinArray = array("la", "lat");
        $malayArray = array("ms", "may", "msa");
        $esperantoArray = array("eo", "epo");
        $arabicArray = array("ar", "ara");
        $greekArray = array("el", "gre", "ell");
        $koreanArray = array("ko", "kor");

        if(in_array($strLang, $japaneseArray))
        {
            $strLang = "jpn";
        }
        else if(in_array($strLang, $englishArray))
        {
            $strLang = "eng";
        }
        else if(in_array($strLang, $frenchArray))
        {
            $strLang = "fre";
        }
        else if(in_array($strLang, $italianArray))
        {
            $strLang = "ita";
        }
        else if(in_array($strLang, $germanArray))
        {
            $strLang = "ger";
        }
        else if(in_array($strLang, $spanishArray))
        {
            $strLang = "spa";
        }
        else if(in_array($strLang, $chineseArray))
        {
            $strLang = "zho";
        }
        else if(in_array($strLang, $russianArray))
        {
            $strLang = "rus";
        }
        else if(in_array($strLang, $latinArray))
        {
            $strLang = "lat";
        }
        else if(in_array($strLang, $malayArray))
        {
            $strLang = "may";
        }
        else if(in_array($strLang, $esperantoArray))
        {
            $strLang = "epo";
        }
        else if(in_array($strLang, $arabicArray))
        {
            $strLang = "ara";
        }
        else if(in_array($strLang, $greekArray))
        {
            $strLang = "gre";
        }
        else if(in_array($strLang, $koreanArray))
        {
            $strLang = "kor";
        }
        else
        {
            $strLang = "";
        }
        return $strLang;
    }

    /**
     * check string format whether grantid or not
     *
     * @param string $strTarget
     * @return string
     */
    static public function grantid($strTarget)
    {
        $pattern_zero = "/^\d{5}[AB]\d+$/";
        $pattern_one = "/^\d{5}.*第.*\d+号$/";

        if(preg_match($pattern_zero, $strTarget) || preg_match($pattern_one, $strTarget))
        {
            return $strTarget;
        }
        else
        {
            return "";
        }
    }

    /**
     * check string format whether pmid or not
     *
     * @param string $strTarget
     * @return string
     */
    static public function pmid($strTarget)
    {
        if(preg_match("/^[!-~]+$/", $strTarget))
        {
            if(substr($strTarget, 0, strlen(self::PMID_PREFIX)) == self::PMID_PREFIX)
            {
                return $strTarget;
            }
            else
            {
                return self::PMID_PREFIX . $strTarget;
            }
        }
        else
        {
            return "";
        }
    }

    /**
     * check string format whether DOI or not
     *
     * @param string $strTarget
     * @return string
     */
    static public function doi($strTarget)
    {
        if(preg_match("/^[!-~]+$/", $strTarget))
        {
            if(substr($strTarget, 0, strlen(self::DOI_PRIFIX)) == self::DOI_PREFIX)
            {
                return $strTarget;
            }
            else
            {
                return self::DOI_PREFIX . $strTarget;
            }
        }
        else
        {
            return "";
        }
    }

    /**
     * check string format whether NAID or not
     *
     * @param string $strTarget
     * @return string
     */
    static public function naid($strTarget)
    {
        if(preg_match("/^[!-~]+$/", $strTarget))
        {
            if(substr($strTarget, 0, strlen(self::NAID_PREFIX)) == self::NAID_PREFIX)
            {
                return $strTarget;
            }
            else
            {
                return self::NAID_PREFIX . $strTarget;
            }
        }
        else
        {
            return "";
        }
    }

    /**
     * check string format whether ICHUSHI or not
     *
     * @param string $strTarget
     * @return string
     */
    static public function ichushi($strTarget)
    {
        if(preg_match("/^[!-~]+$/", $strTarget))
        {
            if(substr($strTarget, 0, strlen(self::ICHUSHI_PREFIX)) == self::ICHUSHI_PREFIX)
            {
                return $strTarget;
            }
            else
            {
                return self::ICHUSHI_PREFIX . $strTarget;
            }
        }
        else
        {
            return "";
        }
    }

    /**
     * convert uri to reseacher resolver id prefix
     *
     * @param string $strTarget
     * @return string
     */
    static public function convertId($strUri)
    {
        $checkStr = "";
        if(substr($strUri, 0, strlen(RepositoryOutputFilter::RESEACHER_RESOLVER_ID_PREFIX)) == RepositoryOutputFilter::RESEACHER_RESOLVER_ID_PREFIX)
        {
            $checkStr = substr($strUri, strlen(RepositoryOutputFilter::RESEACHER_RESOLVER_ID_PREFIX));
        }
        else
        {
            $checkStr = $strUri;
        }
        if(preg_match("/^[0-9]+$/", $checkStr))
        {
            return $checkStr;
        }
        else
        {
            return "";
        }
    }

    /**
     * convert language notation to ISO-639-2 format
     *
     * @param string $strLang
     * @return string
     */
    static public function langISOForWEKO($strLang)
    {
        $japaneseArray = array("ja", "jpn");
        $englishArray = array("en", "eng", "en-US", "es-US", "haw", "ik", "nv", "oj", "yi");
        $frenchArray = array("fr", "fre", "br", "co", "de-FR", "fr-FR", "oc");
        $italianArray = array("it", "ita", "co", "de-IT", "fr-IT", "it-IT", "sc");
        $germanArray = array("de", "ger", "da-DE", "de-1901", "de-1996", "de-AT-1901", "de-AT-1996",
                                "de-CH-1901", "de-CH-1996", "de-DE", "de-DE-1901", "de-DE-1996",
                                "dsb", "fy-DE", "hsb", "lb", "nds", "wen", "yi");
        $spanishArray = array("es", "spa", "an", "ca", "es-ES", "eu", "gl");
        $chineseArray = array("zh", "zho", "bo", "i-hak", "ii", "za", "zh-CN", "zh-gan", "zh-guoyu", "zh-hakka",
                                "zh-Hans", "zh-Hant", "zh-wuu", "zh-xiang", "zh-yue");
        $russianArray = array("ru", "rus", "av", "ba", "ce", "cu", "cv", "kv", "os", "ru-RU", "tt", "yi");
        $latinArray = array("la", "lat");
        $malayArray = array("ms", "may");
        $esperantoArray = array("eo", "epo");
        $arabicArray = array("ar", "ara", "ar-AE");
        $greekArray = array("el", "gre", "el-GR");
        $koreanArray = array("ko", "kor", "ko-KP");

        if(in_array($strLang, $japaneseArray))
        {
            $strLang = "ja";
        }
        else if(in_array($strLang, $englishArray))
        {
            $strLang = "en";
        }
        else if(in_array($strLang, $frenchArray))
        {
            $strLang = "fr";
        }
        else if(in_array($strLang, $italianArray))
        {
            $strLang = "it";
        }
        else if(in_array($strLang, $germanArray))
        {
            $strLang = "de";
        }
        else if(in_array($strLang, $spanishArray))
        {
            $strLang = "es";
        }
        else if(in_array($strLang, $chineseArray))
        {
            $strLang = "zh";
        }
        else if(in_array($strLang, $russianArray))
        {
            $strLang = "ru";
        }
        else if(in_array($strLang, $latinArray))
        {
            $strLang = "la";
        }
        else if(in_array($strLang, $malayArray))
        {
            $strLang = "ms";
        }
        else if(in_array($strLang, $esperantoArray))
        {
            $strLang = "eo";
        }
        else if(in_array($strLang, $arabicArray))
        {
            $strLang = "ar";
        }
        else if(in_array($strLang, $greekArray))
        {
            $strLang = "el";
        }
        else if(in_array($strLang, $koreanArray))
        {
            $strLang = "ko";
        }
        else
        {
            $strLang = "otherlanguage";
        }
        return $strLang;
    }

    /**
     * convert language notation to RFC3066 format
     *
     * @param string $strLang
     * @return string
     */
    static public function langRFCForWEKO($strLang)
    {
        $japaneseArray = array("jpn", "ja");
        $englishArray = array("eng", "en-US", "es-US", "haw", "ik", "nv", "oj", "yi");
        if(in_array($strLang, $japaneseArray))
        {
            $strLang = "japanese";
        }
        else if(in_array($strLang, $englishArray))
        {
            $strLang = "english";
        }
        else
        {
            $strLang = "";
        }
        return $strLang;
    }
}

// SPASE filter
//must fix because this class is based on LOM template.
class RepositoryOutputFilterSPASE extends RepositoryOutputFilter
{
  /**
   * general structure
   *  allow : 'atomic', 'collection', 'networked', hierarchical', 'linear'
   *
   * @param string $str
   */

  static public function retValue($str){
    $str = RepositoryOutputFilter::string($str);
    return $str;
  }


  static public function generalResourceID($str)
  {
      $str = RepositoryOutputFilter::string($str);
      if(!preg_match('/spase*/', $str))
      {
          $str = '';
      }
      return $str;
  }

  /**
   * general aggregation level
   *  allow : '1', '2', '3', 4'
   *
   * @param string $str
   */
  static public function generalAggregationLevelValue($str)
  {
      $str = RepositoryOutputFilter::string($str);
      if( $str != '1' && $str != '2' && $str != '3' && $str != '4')
      {
          $str = '';
      }
      return $str;
  }

  /**
   * lifeCycle statue
   *  allow : 'draft', 'final', 'revised', 'unavailable'
   *
   * @param string $str
   */
  static public function lifeCycleStatusValue($str)
  {
      $str = RepositoryOutputFilter::string($str);
      if( $str != 'draft' && $str != 'final' && $str != 'revised' && $str != 'unavailable')
      {
          $str = '';
      }
      return $str;
  }

  /**
   * lifeCycle contribute role
   *  allow : 'author', 'publisher', 'unknown', 'initiator', 'terminator', 'validator', 'editor',
   *          'graphical designer', 'technical implementer', 'content provider', 'technical validator',
   *          'educational validator', 'script writer', 'instructional designer', 'subject matter expert'
   *
   * @param string $str
   */
  static public function lyfeCycleContributeRole($str)
  {
      $str = RepositoryOutputFilter::string($str);
      if($str == 'graphicaldesigner'){
          $str = 'graphical designer';
      }else if($str == 'technicalimplementer'){
          $str = 'technical implementer';
      }else if($str == 'contentprovider'){
          $str = 'content provider';
      }else if($str == 'technicalvalidator'){
          $str = 'technical validator';
      }else if($str == 'educationalvalidator'){
          $str = 'educational validator';
      }else if($str == 'scriptwriter'){
          $str = 'script writer';
      }else if($str == 'instructionaldesigner'){
          $str = 'instructional designer';
      }else if($str == 'subjectmatterexpert'){
          $str = 'subject matter expert';
      }

      if( $str != 'author' && $str != 'publisher' && $str != 'unknown' && $str != 'initiator' &&
          $str != 'terminator' && $str != 'validator' && $str != 'editor' && $str != 'graphical designer' &&
          $str != 'technical implementer' && $str != 'content provider' && $str != 'technical validator' &&
          $str != 'educational validator' && $str != 'script writer' && $str != 'instructional designer' &&
          $str != 'subject matter expert')//unknown???
      {
          $str = '';
      }
      return $str;
  }

  /**
   * metaMetadata contribute role
   *  allow : 'creator', 'validator'
   *
   * @param string $str
   */
  static public function metaMetadataContributeRole($str)
  {
      $str = RepositoryOutputFilter::string($str);
      if( $str != 'creator' && $str != 'validator')
      {
          $str = '';
      }
      return $str;
  }

  /*
   * technical size
   * @param string $str
   * @return string
   */
  static public function technicalSize($str){

      $str = preg_replace("/[^0-9]+/", "", $str);

      return $str;
  }

  /**
   * technical requirement orComposite type value
   *  allow : 'operating system', 'browser'
   *
   * @param string $str
   * @return string
   */
  static public function technicalRequirementOrCompositeTypeValue($str)
  {
      $str = RepositoryOutputFilter::string($str);
      if($str != 'operating system' && $str != 'browser')
      {
          $str = '';
      }
      return $str;
  }

  /**
   * technical Requirement OrComposite Name Value
   * when technical requirement orComposite type value is 'operating system'
   *
   * allow : 'pc-dos', 'ms-windows', 'macos', 'unix', 'multi-os', 'none'
   *
   * @param string $str
   * @return string
   */
  static public function technicalRequirementOrCompositeNameValueForOperatingSystem($str)
  {
      $str = RepositoryOutputFilter::string($str);
      if( $str != 'pc-dos' && $str != 'ms-windows' && $str != 'macos' &&
          $str != 'unix' && $str != 'multi-os' && $str != 'none' )
      {
          $str = '';
      }
      return $str;
  }

  /**
   * technical Requirement OrComposite Name Value
   * when technical requirement orComposite type value is 'browser'
   *
   * allow : 'any', 'netscape', 'communicator', 'ms-internet explorer', 'opera', 'amaya'
   *
   * @param string $str
   * @return string
   */
  static public function technicalRequirementOrCompositeNameValueForBrowser($str)
  {
      $str = RepositoryOutputFilter::string($str);
      if( $str != 'any' && $str != 'netscape' && $str != 'communicator' &&
          $str != 'ms-internet explorer' && $str != 'opera' && $str != 'amaya' )
      {
          $str = '';
      }
      return $str;
  }

  /**
   * technical Requirement OrComposite Combination Type Name Value
   * when technical requirement orComposite type value is 'operating system'
   * allow : 'pc-dos', 'ms-windows', 'macos', 'unix', 'multi-os', 'none'
   *
   * when technical requirement orComposite type value is 'browser'
   * allow : 'any', 'netscape', 'communicator', 'ms-internet explorer', 'opera', 'amaya'
   *
   * @param string $type
   * @param string $name
   * @return bool
   */
  static public function technicalRequirementOrCompositeCombination($type, $name)
  {
      $name = RepositoryOutputFilter::string($name);
      $type = RepositoryOutputFilter::string($type);
      $type = RepositoryOutputFilterLOM::technicalRequirementOrCompositeTypeValue($type);
      if($type == 'operating system')
      {
          $name = RepositoryOutputFilterLOM::technicalRequirementOrCompositeNameValueForOperatingSystem($name);
      }
      else if($type == 'browser')
      {
          $name = RepositoryOutputFilterLOM::technicalRequirementOrCompositeNameValueForBrowser($name);
      }
      else
      {
          return false;
      }

      if(strlen($type)>0 && strlen($name)>0)
      {
          return true;
      }

      return false;
  }

  /**
   * duration
   *
   * P[yY][mM][dD][T[hH][nM][s[.s]S]] where:
   * y = number of years
   * (integer, > 0, not restricted)
   * m = number of months
   * (integer, > 0, not restricted, e.g., > 12 is acceptable)
   * d = number of days
   * (integer, > 0, not restricted, e.g., > 31 is acceptable)
   * h = number of hours
   * (integer, > 0, not restricted, e.g., > 23 is acceptable)
   * n = number of minutes
   * (integer, > 0, not restricted, e.g., > 59 is acceptable)
   * s = number of seconds or fraction of seconds
   * (integer, > 0, not restricted, e.g., > 59 is acceptable)
   *
   * The character literal designators "P", "Y", "M", "D", "T", "H", "M", "S" must
   * appear if the corresponding nonzero value is present.
   * @param string $str
   * @return string
   */
  static public function duration($str)
  {
      $match = array();

      if(preg_match("/^P([0-9]+Y)*([0-9]+M)*([0-9]+D)*((T)([0-9]+H)*([0-9]+M)*([0-9]+(\.[0-9]+)*S)*)*$/", $str, $match)==1)
      {
          $str = 'P';
          $T = false;
          for($ii=1; $ii<count($match); $ii++)
          {
              $time = substr($match[$ii], 0, strlen($match[$ii])-1);
              $key  = substr($match[$ii], strlen($match[$ii])-1);
              if(!$T && $key == 'Y' && 0 < intval($time))
              {
                  // year
                  $str .= $match[$ii];
              }
              else if(!$T && $key == 'M' && 0 < intval($time) && intval($time) < 13)
              {
                  // month
                  $str .= $match[$ii];
              }
              else if(!$T && $key == 'D' && 0 < intval($time) && intval($time) < 32)
              {
                  // days
                  $str .= $match[$ii];
              }
              else if(!$T && $key == 'T')
              {
                  $T = true;
              }
              else if($T && $key == 'H' && 0 < intval($time) && intval($time) < 24)
              {
                  // hour
                  $str .= $match[$ii];
              }
              else if($T && $key == 'M' && 0 < intval($time) && intval($time) < 60)
              {
                  // minutes
                  $str .= $match[$ii];
              }
              else if($T && $key == 'S' && 0 < intval($time) && intval($time) < 60)
              {
                  // minutes
                  $str .= $match[$ii];
              }
          }
      }
      else
      {
          $str = '';
      }
      return $str;
  }

  /**
   * educational interactivity type
   * allow : 'active', 'expositive', 'mixed'
   * @param string $str
   * @return string
   */
  static public function educationalInteractivityType($str)
  {
      $str = RepositoryOutputFilter::string($str);
      if($str != 'active' && $str != 'expositive' && $str != 'mixed')
      {
          $str = '';
      }
      return $str;
  }

  /**
   * educational learning resource type
   * allow : 'exercise', 'simulation', 'questionnaire', 'diagram', 'figure',
   *         'graph', 'index', 'slide', 'table', 'narrative text', 'exam',
   *         'experiment', 'problem statement', 'self assessment', 'lecture'
   * @param string $str
   * @return string
   */
  static public function educationalLearningResourceType($str)
  {
      $str = RepositoryOutputFilter::string($str);
      if( $str != 'exercise' && $str != 'simulation' && $str != 'questionnaire' &&
          $str != 'diagram' && $str != 'figure' && $str != 'graph' && $str != 'index' &&
          $str != 'slide' && $str != 'table' && $str != 'narrative text' && $str != 'exam' &&
          $str != 'experiment' && $str != 'problem statement' && $str != 'self assessment' &&
          $str != 'lecture')
      {
          $str = '';
      }
      return $str;
  }

  /**
   * educational interactivity level
   * allow : 'very low', 'low', 'medium', 'high','very high'
   * @param string $str
   * @return string
   */
  static public function educationalInteractivityLevel($str)
  {
      $str = RepositoryOutputFilter::string($str);
      if($str != 'very low' && $str != 'low' && $str != 'medium' && $str != 'high' && $str != 'very high')
      {
          $str = '';
      }
      return $str;
  }

  /**
   * educational semantic oensity
   * allow : 'very low', 'low', 'medium', 'high','very high'
   * @param string $str
   * @return string
   */
  static public function educationalSemanticDensity($str)
  {
      return self::educationalInteractivityLevel($str);
  }

  /**
   * educational intended end user role
   * allow : 'teacher', 'author', 'learner', 'manager'
   * @param string $str
   * @return string
   */
  static public function educationalIntendedEndUserRole($str)
  {
      $str = RepositoryOutputFilter::string($str);
      if($str != 'teacher' && $str != 'author' && $str != 'learner' && $str != 'manager')
      {
          $str = '';
      }
      return $str;
  }

  /**
   * educational context
   * allow : 'school', 'higher education', 'training', 'other'
   * @param string $str
   * @return string
   */
  static public function educationalContext($str)
  {
      $str = RepositoryOutputFilter::string($str);
      if($str != 'school' && $str != 'higher education' && $str != 'training' && $str != 'other')
      {
          $str = '';
      }
      return $str;
  }

  /**
   * educational difficulty
   * allow : 'very easy', 'easy', 'medium', 'difficult', 'very difficult'
   * @param string $str
   * @return string
   */
  static public function educationalDifficulty($str)
  {
      $str = RepositoryOutputFilter::string($str);
      if($str != 'very easy' && $str != 'easy' && $str != 'medium' && $str != 'difficult' && $str != 'very difficult')
      {
          $str = '';
      }
      return $str;
  }

  /**
   * YesNo
   * allow : 'yes', 'no'
   * @param string $str
   * @return string
   */
  static public function yesno($str)
  {
      $str = RepositoryOutputFilter::string($str);
      if($str != 'yes' && $str != 'no')
      {
          $str = '';
      }
      return $str;
  }

  /**
   * relation
   * allow : 'ispartof', 'haspart', 'isversionof', 'hasversion', 'isformatof',
   *         'hasformat', 'references', 'isreferencedby', 'isbasedon', 'isbasisfor',
   *         'requires', 'isrequiredby'
   *
   * @param string $str
   * @return string
   */
  static public function relation($str)
  {
      $str = RepositoryOutputFilter::string($str);
      if( $str !='ispartof' && $str != 'haspart' && $str != 'isversionof' && $str != 'hasversion' &&
          $str != 'isformatof' && $str != 'hasformat' && $str != 'references' && $str != 'isreferencedby' &&
          $str != 'isbasedon' && $str != 'isbasisfor' && $str != 'requires' && $str != 'isrequiredby')
      {
          $str = '';
      }
      return $str;
  }

  /**
   * classification purpose
   * allow : 'discipline', 'idea', 'prerequisite', 'educational objective',
   *         'accessibility', 'restrictions', 'educational level', 'skill level',
   *         'security level', 'competency
   * @param string $str
   * @return string
   */
  static public function classificationPurpose($str)
  {
      $str = RepositoryOutputFilter::string($str);
      if( $str != 'discipline' && $str != 'idea' && $str != 'prerequisite' && $str != 'educational objective' &&
          $str != 'accessibility' && $str != 'restrictions' && $str != 'educational level' && $str != 'skill level' &&
          $str != 'security level' && $str != 'competency')
      {
          $str = '';
      }
      return $str;
  }

}

/**
 * output filter for format:LOM class
 *
 */
class RepositoryOutputFilterLOM extends RepositoryOutputFilter
{
    /**
     * general structure
     *  allow : 'atomic', 'collection', 'networked', hierarchical', 'linear'
     *
     * @param string $str
     */
    static public function generalStructureValue($str)
    {
        $str = RepositoryOutputFilter::string($str);
        if( $str != 'atomic' && $str != 'collection' && $str != 'networked' &&
            $str != 'hierarchical' && $str != 'linear')
        {
            $str = '';
        }
        return $str;
    }

    /**
     * general aggregation level
     *  allow : '1', '2', '3', 4'
     *
     * @param string $str
     */
    static public function generalAggregationLevelValue($str)
    {
        $str = RepositoryOutputFilter::string($str);
        if( $str != '1' && $str != '2' && $str != '3' && $str != '4')
        {
            $str = '';
        }
        return $str;
    }

    /**
     * lifeCycle statue
     *  allow : 'draft', 'final', 'revised', 'unavailable'
     *
     * @param string $str
     */
    static public function lifeCycleStatusValue($str)
    {
        $str = RepositoryOutputFilter::string($str);
        if( $str != 'draft' && $str != 'final' && $str != 'revised' && $str != 'unavailable')
        {
            $str = '';
        }
        return $str;
    }

    /**
     * lifeCycle contribute role
     *  allow : 'author', 'publisher', 'unknown', 'initiator', 'terminator', 'validator', 'editor',
     *          'graphical designer', 'technical implementer', 'content provider', 'technical validator',
     *          'educational validator', 'script writer', 'instructional designer', 'subject matter expert'
     *
     * @param string $str
     */
    static public function lyfeCycleContributeRole($str)
    {
        $str = RepositoryOutputFilter::string($str);
        if($str == 'graphicaldesigner'){
            $str = 'graphical designer';
        }else if($str == 'technicalimplementer'){
            $str = 'technical implementer';
        }else if($str == 'contentprovider'){
            $str = 'content provider';
        }else if($str == 'technicalvalidator'){
            $str = 'technical validator';
        }else if($str == 'educationalvalidator'){
            $str = 'educational validator';
        }else if($str == 'scriptwriter'){
            $str = 'script writer';
        }else if($str == 'instructionaldesigner'){
            $str = 'instructional designer';
        }else if($str == 'subjectmatterexpert'){
            $str = 'subject matter expert';
        }

        if( $str != 'author' && $str != 'publisher' && $str != 'unknown' && $str != 'initiator' &&
            $str != 'terminator' && $str != 'validator' && $str != 'editor' && $str != 'graphical designer' &&
            $str != 'technical implementer' && $str != 'content provider' && $str != 'technical validator' &&
            $str != 'educational validator' && $str != 'script writer' && $str != 'instructional designer' &&
            $str != 'subject matter expert')//unknown???
        {
            $str = '';
        }
        return $str;
    }

    /**
     * metaMetadata contribute role
     *  allow : 'creator', 'validator'
     *
     * @param string $str
     */
    static public function metaMetadataContributeRole($str)
    {
        $str = RepositoryOutputFilter::string($str);
        if( $str != 'creator' && $str != 'validator')
        {
            $str = '';
        }
        return $str;
    }

    /*
     * technical size
     * @param string $str
     * @return string
     */
    static public function technicalSize($str){

        $str = preg_replace("/[^0-9]+/", "", $str);

        return $str;
    }

    /**
     * technical requirement orComposite type value
     *  allow : 'operating system', 'browser'
     *
     * @param string $str
     * @return string
     */
    static public function technicalRequirementOrCompositeTypeValue($str)
    {
        $str = RepositoryOutputFilter::string($str);
        if($str != 'operating system' && $str != 'browser')
        {
            $str = '';
        }
        return $str;
    }

    /**
     * technical Requirement OrComposite Name Value
     * when technical requirement orComposite type value is 'operating system'
     *
     * allow : 'pc-dos', 'ms-windows', 'macos', 'unix', 'multi-os', 'none'
     *
     * @param string $str
     * @return string
     */
    static public function technicalRequirementOrCompositeNameValueForOperatingSystem($str)
    {
        $str = RepositoryOutputFilter::string($str);
        if( $str != 'pc-dos' && $str != 'ms-windows' && $str != 'macos' &&
            $str != 'unix' && $str != 'multi-os' && $str != 'none' )
        {
            $str = '';
        }
        return $str;
    }

    /**
     * technical Requirement OrComposite Name Value
     * when technical requirement orComposite type value is 'browser'
     *
     * allow : 'any', 'netscape', 'communicator', 'ms-internet explorer', 'opera', 'amaya'
     *
     * @param string $str
     * @return string
     */
    static public function technicalRequirementOrCompositeNameValueForBrowser($str)
    {
        $str = RepositoryOutputFilter::string($str);
        if( $str != 'any' && $str != 'netscape' && $str != 'communicator' &&
            $str != 'ms-internet explorer' && $str != 'opera' && $str != 'amaya' )
        {
            $str = '';
        }
        return $str;
    }

    /**
     * technical Requirement OrComposite Combination Type Name Value
     * when technical requirement orComposite type value is 'operating system'
     * allow : 'pc-dos', 'ms-windows', 'macos', 'unix', 'multi-os', 'none'
     *
     * when technical requirement orComposite type value is 'browser'
     * allow : 'any', 'netscape', 'communicator', 'ms-internet explorer', 'opera', 'amaya'
     *
     * @param string $type
     * @param string $name
     * @return bool
     */
    static public function technicalRequirementOrCompositeCombination($type, $name)
    {
        $name = RepositoryOutputFilter::string($name);
        $type = RepositoryOutputFilter::string($type);
        $type = RepositoryOutputFilterLOM::technicalRequirementOrCompositeTypeValue($type);
        if($type == 'operating system')
        {
            $name = RepositoryOutputFilterLOM::technicalRequirementOrCompositeNameValueForOperatingSystem($name);
        }
        else if($type == 'browser')
        {
            $name = RepositoryOutputFilterLOM::technicalRequirementOrCompositeNameValueForBrowser($name);
        }
        else
        {
            return false;
        }

        if(strlen($type)>0 && strlen($name)>0)
        {
            return true;
        }

        return false;
    }

    /**
     * duration
     *
     * P[yY][mM][dD][T[hH][nM][s[.s]S]] where:
     * y = number of years
     * (integer, > 0, not restricted)
     * m = number of months
     * (integer, > 0, not restricted, e.g., > 12 is acceptable)
     * d = number of days
     * (integer, > 0, not restricted, e.g., > 31 is acceptable)
     * h = number of hours
     * (integer, > 0, not restricted, e.g., > 23 is acceptable)
     * n = number of minutes
     * (integer, > 0, not restricted, e.g., > 59 is acceptable)
     * s = number of seconds or fraction of seconds
     * (integer, > 0, not restricted, e.g., > 59 is acceptable)
     *
     * The character literal designators "P", "Y", "M", "D", "T", "H", "M", "S" must
     * appear if the corresponding nonzero value is present.
     * @param string $str
     * @return string
     */
    static public function duration($str)
    {
        $match = array();

        if(preg_match("/^P([0-9]+Y)*([0-9]+M)*([0-9]+D)*((T)([0-9]+H)*([0-9]+M)*([0-9]+(\.[0-9]+)*S)*)*$/", $str, $match)==1)
        {
            $str = 'P';
            $T = false;
            for($ii=1; $ii<count($match); $ii++)
            {
                $time = substr($match[$ii], 0, strlen($match[$ii])-1);
                $key  = substr($match[$ii], strlen($match[$ii])-1);
                if(!$T && $key == 'Y' && 0 < intval($time))
                {
                    // year
                    $str .= $match[$ii];
                }
                else if(!$T && $key == 'M' && 0 < intval($time) && intval($time) < 13)
                {
                    // month
                    $str .= $match[$ii];
                }
                else if(!$T && $key == 'D' && 0 < intval($time) && intval($time) < 32)
                {
                    // days
                    $str .= $match[$ii];
                }
                else if(!$T && $key == 'T')
                {
                    $T = true;
                }
                else if($T && $key == 'H' && 0 < intval($time) && intval($time) < 24)
                {
                    // hour
                    $str .= $match[$ii];
                }
                else if($T && $key == 'M' && 0 < intval($time) && intval($time) < 60)
                {
                    // minutes
                    $str .= $match[$ii];
                }
                else if($T && $key == 'S' && 0 < intval($time) && intval($time) < 60)
                {
                    // minutes
                    $str .= $match[$ii];
                }
            }
        }
        else
        {
            $str = '';
        }
        return $str;
    }

    /**
     * educational interactivity type
     * allow : 'active', 'expositive', 'mixed'
     * @param string $str
     * @return string
     */
    static public function educationalInteractivityType($str)
    {
        $str = RepositoryOutputFilter::string($str);
        if($str != 'active' && $str != 'expositive' && $str != 'mixed')
        {
            $str = '';
        }
        return $str;
    }

    /**
     * educational learning resource type
     * allow : 'exercise', 'simulation', 'questionnaire', 'diagram', 'figure',
     *         'graph', 'index', 'slide', 'table', 'narrative text', 'exam',
     *         'experiment', 'problem statement', 'self assessment', 'lecture'
     * @param string $str
     * @return string
     */
    static public function educationalLearningResourceType($str)
    {
        $str = RepositoryOutputFilter::string($str);
        if( $str != 'exercise' && $str != 'simulation' && $str != 'questionnaire' &&
            $str != 'diagram' && $str != 'figure' && $str != 'graph' && $str != 'index' &&
            $str != 'slide' && $str != 'table' && $str != 'narrative text' && $str != 'exam' &&
            $str != 'experiment' && $str != 'problem statement' && $str != 'self assessment' &&
            $str != 'lecture')
        {
            $str = '';
        }
        return $str;
    }

    /**
     * educational interactivity level
     * allow : 'very low', 'low', 'medium', 'high','very high'
     * @param string $str
     * @return string
     */
    static public function educationalInteractivityLevel($str)
    {
        $str = RepositoryOutputFilter::string($str);
        if($str != 'very low' && $str != 'low' && $str != 'medium' && $str != 'high' && $str != 'very high')
        {
            $str = '';
        }
        return $str;
    }

    /**
     * educational semantic oensity
     * allow : 'very low', 'low', 'medium', 'high','very high'
     * @param string $str
     * @return string
     */
    static public function educationalSemanticDensity($str)
    {
        return self::educationalInteractivityLevel($str);
    }

    /**
     * educational intended end user role
     * allow : 'teacher', 'author', 'learner', 'manager'
     * @param string $str
     * @return string
     */
    static public function educationalIntendedEndUserRole($str)
    {
        $str = RepositoryOutputFilter::string($str);
        if($str != 'teacher' && $str != 'author' && $str != 'learner' && $str != 'manager')
        {
            $str = '';
        }
        return $str;
    }

    /**
     * educational context
     * allow : 'school', 'higher education', 'training', 'other'
     * @param string $str
     * @return string
     */
    static public function educationalContext($str)
    {
        $str = RepositoryOutputFilter::string($str);
        if($str != 'school' && $str != 'higher education' && $str != 'training' && $str != 'other')
        {
            $str = '';
        }
        return $str;
    }

    /**
     * educational difficulty
     * allow : 'very easy', 'easy', 'medium', 'difficult', 'very difficult'
     * @param string $str
     * @return string
     */
    static public function educationalDifficulty($str)
    {
        $str = RepositoryOutputFilter::string($str);
        if($str != 'very easy' && $str != 'easy' && $str != 'medium' && $str != 'difficult' && $str != 'very difficult')
        {
            $str = '';
        }
        return $str;
    }

    /**
     * YesNo
     * allow : 'yes', 'no'
     * @param string $str
     * @return string
     */
    static public function yesno($str)
    {
        $str = RepositoryOutputFilter::string($str);
        if($str != 'yes' && $str != 'no')
        {
            $str = '';
        }
        return $str;
    }

    /**
     * relation
     * allow : 'ispartof', 'haspart', 'isversionof', 'hasversion', 'isformatof',
     *         'hasformat', 'references', 'isreferencedby', 'isbasedon', 'isbasisfor',
     *         'requires', 'isrequiredby'
     *
     * @param string $str
     * @return string
     */
    static public function relation($str)
    {
        $str = RepositoryOutputFilter::string($str);
        if( $str !='ispartof' && $str != 'haspart' && $str != 'isversionof' && $str != 'hasversion' &&
            $str != 'isformatof' && $str != 'hasformat' && $str != 'references' && $str != 'isreferencedby' &&
            $str != 'isbasedon' && $str != 'isbasisfor' && $str != 'requires' && $str != 'isrequiredby')
        {
            $str = '';
        }
        return $str;
    }

    /**
     * classification purpose
     * allow : 'discipline', 'idea', 'prerequisite', 'educational objective',
     *         'accessibility', 'restrictions', 'educational level', 'skill level',
     *         'security level', 'competency
     * @param string $str
     * @return string
     */
    static public function classificationPurpose($str)
    {
        $str = RepositoryOutputFilter::string($str);
        if( $str != 'discipline' && $str != 'idea' && $str != 'prerequisite' && $str != 'educational objective' &&
            $str != 'accessibility' && $str != 'restrictions' && $str != 'educational level' && $str != 'skill level' &&
            $str != 'security level' && $str != 'competency')
        {
            $str = '';
        }
        return $str;
    }
}
?>
