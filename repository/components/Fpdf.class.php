<?php
/**
 * $Id: Fpdf.class.php 57059 2015-08-25 06:43:36Z tomohiro_ichikawa $
 *
 * ファイルシステムの操作を行う
 *
 * @author IVIS
 */
require_once WEBAPP_DIR. '/modules/repository/files/fpdf/mc_table.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryConst.class.php';

class Repository_Components_Fpdf extends PDF_MC_Table
{
    // Header
    private $headerAlign = "R";
    private $headerType = array();
    private $headerText = "";
    private $imageBlob = "";
    private $imageName = "";
    // Footer
    private $licenseId = "";
    private $notation = "";
    private $licenseImagePath = "";
    private $licenseTextUrl = "";
    
    // work directory
    private $tmpDir = "";
    
    // const
    // "SJIS" or "Unicode"
    const ENCODE_TO = "SJIS";
    const ENCODE_FROM = "UTF-8";
    // Font
    const FONT_NAME_MSMINCHO = "MSMincho";
    const FONT_NAME_MSPMINCHO = "MSPMincho";
    const FONT_NAME_MSGOTHIC = "MSGothic";
    const FONT_NAME_MSPGOTHIC = "MSPGothic";
    const FONT_ARIAL = "arial";
    const FONT_FAMILY_MSMINCHO = "MSMincho";
    const FONT_FAMILY_MSPMINCHO = "MSPMincho";
    const FONT_FAMILY_MSGOTHIC = "MSGothic";
    const FONT_FAMILY_MSPGOTHIC = "MSPGothic";
    // Font size
    const FONTSIZE_HEADER = 9;
    const FONTSIZE_FOOTER = 10;
    const FONTSIZE_TITLE = 20;
    const FONTSIZE_TITLE_SUB = 15;
    const FONTSIZE_METADATA = 14;
    const FONTSIZE_DOI = 10;
    // Margin
    const MARGIN_TOP = 35.0;
    const MARGIN_LEFT = 22.0;
    const MARGIN_RIGHT = 22.0;
    const MARGIN_TITLE_TOP = 5.0;
    const MARGIN_TITLE_UNDER = 10.0;
    // Header margin
    const HEADER_TOP = 17.0;
    const HEADER_HEIGHT = 15.0;
    const FOOTER_TOP = -22.0;
    const FOOTER_HEIGHT = 15.0;
    // Biblio
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
    
    // -------------------------------------------------------
    // CONSTRUCTER
    // -------------------------------------------------------
    /**
     * construct
     *
     */
    public function __construct($dir) {
        $this->tmpDir = $dir;
        parent::FPDF();
    }
    
    // -------------------------------------------------------
    // SETTER
    // -------------------------------------------------------
    /**
     * Set for text header
     *
     */
    public function setHeaderTextParam($align, $type, $text) {
        $this->headerAlign = $align;
        $this->headerType = $type;
        $this->headerText = $text;
    }
    
    /**
     * Set for image header
     *
     */
    public function setHeaderImageParam($align, $type, $blob, $name) {
        $this->headerAlign = $align;
        $this->headerType = $type;
        $this->imageBlob = $blob;
        $this->imageName = $name;
    }
    
    /**
     * Set for license footer
     *
     */
    public function setFooterLicenseParam($license_id, $notation, $license_image_path, $license_url) {
        $this->licenseId = $license_id;
        $this->notation = $notation;
        $this->licenseImagePath = $license_image_path;
        $this->licenseTextUrl = $license_url;
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
        $this->SetFont(self::FONT_FAMILY_MSMINCHO,"",self::FONTSIZE_HEADER);
        $this->SetY(self::HEADER_TOP);
        
        if($this->headerType[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_TEXT] == RepositoryConst::PDF_COVER_HEADER_TYPE_TEXT) {
            $this->MultiCell(0, $this->FontSize+$this->cMargin, mb_convert_encoding($this->stripAccent($this->headerText), self::ENCODE_TO, self::ENCODE_FROM), 0, $this->headerAlign);
        } else if($this->headerType[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_TEXT] == RepositoryConst::PDF_COVER_HEADER_TYPE_IMAGE) {
            if(strlen($this->imageBlob) > 0) {
                // Create physical file
                $handle = fopen($this->tmpDir.$this->imageName, "w");
                fwrite($handle, $this->imageBlob);
                fclose($handle);
                if(file_exists($this->tmpDir.$this->imageName)) {
                    // Get Image size
                    $a = getimagesize($this->tmpDir.$this->imageName);
                    $w_px = $a[0]; // Unit: px
                    $h_px = $a[1]; // Unit: px
                    
                    // Convert to FPDF unit
                    $w_unit = $w_px*72/96/$this->k;
                    $h_unit = $h_px*72/96/$this->k;
                    
                    // Resize
                    $this->resizeImage(
                            $w_unit,
                            $h_unit,
                            $this->w-self::MARGIN_LEFT-self::MARGIN_RIGHT,
                            self::HEADER_HEIGHT
                        );
                    if($this->headerAlign == RepositoryConst::ALIGN_RIGHT) {
                        // right
                        $this->SetX($this->w-self::MARGIN_RIGHT-$w_unit);
                    } else if($this->headerAlign == RepositoryConst::ALIGN_CENTER) {
                        // center
                        $this->SetX($this->w/2 - $w_unit/2);
                    } else if($this->headerAlign == RepositoryConst::ALIGN_LEFT) {
                        // left
                        $this->SetX(self::MARGIN_LEFT );
                    }
                    
                    $this->Image($this->tmpDir.$this->imageName, $this->GetX(), $this->GetY(), $w_unit, $h_unit);
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
        if(strlen($this->notation) > 0) {
            // Set license to PDF
            $this->SetY(self::FOOTER_TOP);
            $this->SetFont(self::FONT_FAMILY_MSMINCHO, '', 9);
            
            if($this->licenseId == "0") {
                // Free
                $this->MultiCell(0, $this->FontSize+$this->cMargin, mb_convert_encoding($this->stripAccent($this->notation), self::ENCODE_TO, self::ENCODE_FROM), 0, RepositoryConst::ALIGN_RIGHT);
            } else if(strlen($this->licenseId) > 0) {
                if(strlen($this->licenseImagePath) > 0) {
                    // Get Image size
                    $a = getimagesize($this->licenseImagePath);
                    $w_px = $a[0]; // Unit: px
                    $h_px = $a[1]; // Unit: px
                    
                    // Convert to FPDF unit
                    $w_unit = $w_px*72/96/$this->k;
                    $h_unit = $h_px*72/96/$this->k;
                    
                    // Resize
                    $this->resizeImage($w_unit, $h_unit, $this->w-self::MARGIN_LEFT-self::MARGIN_RIGHT, self::FOOTER_HEIGHT);
                    $this->SetX($this->w-self::MARGIN_RIGHT-$w_unit);
                    $this->Image($this->licenseImagePath, $this->GetX(), $this->GetY(), $w_unit, $h_unit);
                    $this->SetX(self::MARGIN_LEFT);
                    $this->MultiCell($this->w-self::MARGIN_LEFT-self::MARGIN_RIGHT-$w_unit, $this->FontSize+$this->cMargin, mb_convert_encoding($this->stripAccent($this->notation), self::ENCODE_TO, self::ENCODE_FROM), 0, RepositoryConst::ALIGN_RIGHT);
                    $this->SetX(self::MARGIN_LEFT);
                    $this->MultiCell($this->w-self::MARGIN_LEFT-self::MARGIN_RIGHT-$w_unit, $this->FontSize+$this->cMargin, mb_convert_encoding($this->stripAccent($this->licenseTextUrl), self::ENCODE_TO, self::ENCODE_FROM), 0, RepositoryConst::ALIGN_RIGHT);
                } else {
                    $this->MultiCell(0, $this->FontSize+$this->cMargin, mb_convert_encoding($this->stripAccent($this->notation), self::ENCODE_TO, self::ENCODE_FROM), 0, RepositoryConst::ALIGN_RIGHT);
                }
            }
        }
        $this->SetY(self::MARGIN_TOP);
        $this->SetX(self::MARGIN_LEFT);
    }
    
    /**
     * Make cover page
     * 
     * @param array  $itemData
     * @param string $uri
     * @return bool
     */
    public function makeCoverPage($itemData, $uri, $outputFile)
    {
        // Add font
        $this->setFontByEncode();
        
        // Set Margin
        $this->SetMargins(self::MARGIN_LEFT, self::MARGIN_TOP, self::MARGIN_RIGHT);
        
        // Add Page
        $this->AddPage();
        
        // Item langage
        $itemLang = $itemData["item"][0]["language"];
        
        // title
        $this->setPdfCoverTitle($itemData, $itemLang);
        
        // Matadata
        $this->setPdfCoverMetadata($itemData, $uri, $itemLang);
        
        $this->Output($outputFile, 'F');
        
        return true;
    }
    
    /**
     * Resize image file
     *
     * @param int &$width
     * @param int &$height
     * @param int $maxWidth
     * @param int $maxHeight
     * @return bool
     */
    public function resizeImage(&$width, &$height, $maxWidth=100, $maxHeight=100)
    {
        if($width == 0 || $height == 0) {
            return false;
        }
        // resize
        if($width >= $height) {
            if($width > $maxWidth) {
                $height = $maxWidth / $width * $height;
                $width = $maxWidth;
            }
            if($height > $maxHeight) {
                $width = $maxHeight / $height * $width;
                $height = $maxHeight;
            }
        } else {
            if($height > $maxHeight) {
                $width = $maxHeight / $height * $width;
                $height = $maxHeight;
            }
            if($width > $maxWidth) {
                $height = $maxWidth / $width * $height;
                $width = $maxWidth;
            }
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
    private static function stripAccent($str)
    {
        $convStr = "";
        
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
    
    /**
     * set font
     * 
     */
    private function setFontByEncode()
    {
        if(strtolower(self::ENCODE_TO) == 'sjis') {
            $this->AddSJISFont(self::FONT_NAME_MSMINCHO, self::FONT_FAMILY_MSMINCHO);
            $this->AddSJISFont(self::FONT_NAME_MSPMINCHO, self::FONT_FAMILY_MSPMINCHO);
            $this->AddSJISFont(self::FONT_NAME_MSGOTHIC, self::FONT_FAMILY_MSGOTHIC);
            $this->AddSJISFont(self::FONT_NAME_MSPGOTHIC, self::FONT_FAMILY_MSPGOTHIC);
        } else {
            $this->AddUniJISFont(self::FONT_NAME_MSMINCHO, self::FONT_FAMILY_MSMINCHO);
            $this->AddUniJISFont(self::FONT_NAME_MSPMINCHO, self::FONT_FAMILY_MSPMINCHO);
            $this->AddUniJISFont(self::FONT_NAME_MSGOTHIC, self::FONT_FAMILY_MSGOTHIC);
            $this->AddUniJISFont(self::FONT_NAME_MSPGOTHIC, self::FONT_FAMILY_MSPGOTHIC);
        }
    }
    
    /**
     * set pdf cover title
     * 
     */
    private function setPdfCoverTitle($itemData, $itemLang)
    {
        $itemTitle = "";
        $itemTitleSub = "";
        
        if($itemLang == "ja") {
            if(strlen($itemData["item"][0]["title"]) > 0) {
                $itemTitle = $itemData["item"][0]["title"];
            }
            if(strlen($itemTitle) > 0) {
                $itemTitleSub = $itemData["item"][0]["title_english"];
            } else {
                $itemTitle = $itemData["item"][0]["title_english"];
            }
        } else {
            if(strlen($itemData["item"][0]["title_english"]) > 0) {
                $itemTitle = $itemData["item"][0]["title_english"];
            }
            if(strlen($itemTitle) == 0) {
                $itemTitle = $itemData["item"][0]["title"];
            }
        }
        
        $this->SetY(self::MARGIN_TOP+self::MARGIN_TITLE_TOP);
        $this->SetFont(self::FONT_FAMILY_MSGOTHIC, 'B', self::FONTSIZE_TITLE);
        $this->SetDrawColor(150, 150, 200);
        $this->SetLineWidth(0.5);
        $this->MultiCell(0, $this->FontSize+$this->cMargin, mb_convert_encoding($this->stripAccent($itemTitle), self::ENCODE_TO, self::ENCODE_FROM), 0, RepositoryConst::ALIGN_CENTERLEFT);
        $this->SetY($this->y+self::MARGIN_TITLE_UNDER);
    }
    
    /**
     * set pdf cover metadata
     * 
     * @param array  $itemData
     * @param string $itemLang
     * @param string &$doiStr
     * 
     */
    private function setPdfCoverMetadata($itemData, $uri, $itemLang)
    {
        $this->SetFont(self::FONT_FAMILY_MSMINCHO, '', self::FONTSIZE_METADATA);
        $this->SetDrawColor(0);
        $this->SetLineWidth(0.2);
        $rowWidth = $this->w-self::MARGIN_LEFT-self::MARGIN_RIGHT;
        $this->SetWidths(array($rowWidth*0.3, $rowWidth*0.7));
        $this->SetAligns(array(RepositoryConst::ALIGN_LEFT, RepositoryConst::ALIGN_LEFT));
        $this->SetRowFillColor(array(array(200,200,250), array(255,255,255)));
        
        $doiStr = "";
        
        // Loop for metadata
        $metadataList = array();
        for($ii=0; $ii<count($itemData['item_attr_type']); $ii++) {
            // 下記はカバーページに使用しない
            if($itemData['item_attr_type'][$ii]['input_type'] == "thumbnail" ||
               $itemData['item_attr_type'][$ii]['input_type'] == "file" ||
               $itemData['item_attr_type'][$ii]['input_type'] == "file_price" ||
               $itemData['item_attr_type'][$ii]['input_type'] == "supple" ||
               $itemData['item_attr_type'][$ii]['input_type'] == "heading") {
                continue;
            }
            
            // check exists doi and $doiStr is void
            if($itemData['item_attr_type'][$ii]['junii2_mapping'] == "doi" && strlen($doiStr) == 0) {
                if($itemData['item_attr_type'][$ii]['input_type'] == "biblio_info") {
                    continue;
                }
                $doiStr = $this->makeDoiStr($itemData, $ii);
                if(strlen($doiStr) > 0) {
                    continue;
                }
            }
            
            // Check option
            if($itemData['item_attr_type'][$ii]['list_view_enable'] != "1") {
                continue;
            }
            
            // Check mapping
            if(strlen($itemData['item_attr_type'][$ii]['display_lang_type']) > 0) {
                if(($itemLang == "ja" && strtolower($itemData['item_attr_type'][$ii]['display_lang_type']) != "japanese") ||
                   ($itemLang != "ja" && strtolower($itemData['item_attr_type'][$ii]['display_lang_type']) != "english")) {
                    continue;
                }
            }
            
            // Check metadata
            $metadata = array();
            $metadata = $this->makePdfCoverMetadataList($itemData, $ii, $itemLang);
            for($jj = 0; $jj < count($metadata); $jj++) {
                array_push($metadataList, $metadata[$jj]);
            }
        }
        
        // URL
        array_push($metadataList, array("name" => "URL", "value" => $uri));
        
        foreach($metadataList as $metadata) {
            $this->Row(
                       array(mb_convert_encoding($this->stripAccent($metadata["name"]), self::ENCODE_TO, self::ENCODE_FROM),
                             mb_convert_encoding($this->stripAccent($metadata["value"]), self::ENCODE_TO, self::ENCODE_FROM))
                      );
        }
        
        // Set doi
        $doiStr = mb_ereg_replace('[^\x00-\x7f]', "", $doiStr);
        if(strlen($doiStr) > 0) {
            // set font=arial, fontsize=10
            $this->SetFont(self::FONT_ARIAL, '', self::FONTSIZE_DOI);
            // add doi string under metadata list table
            $this->MultiCell(0, $this->FontSize+$this->cMargin, mb_convert_encoding($this->stripAccent("doi: ".$doiStr), self::ENCODE_TO, self::ENCODE_FROM), 0, RepositoryConst::ALIGN_RIGHT);
        }
    }
    
    /**
     * set pdf cover metadata
     * 
     * @param array $itemData
     * @param int   $index
     */
    private function makeDoiStr($itemData, $index)
    {
        $doiStr = "";
        if($itemData['item_attr_type'][$index]['input_type'] == "name") {
            if(strtolower($itemData['item_attr_type'][$index]['display_lang_type']) == "english") {
                $nameStr = $itemData['item_attr'][$index][0]['name'];
                if(strlen($itemData['item_attr'][$index][0]['family']) > 0) {
                    if(strlen($nameStr) > 0) {
                        $nameStr .= " ";
                    }
                    $nameStr .= $itemData['item_attr'][$index][0]['family'];
                }
            } else {
                $nameStr = $itemData['item_attr'][$index][0]['family'];
                if(strlen($itemData['item_attr'][$index][0]['name']) > 0) {
                    if(strlen($nameStr) > 0) {
                        $nameStr .= " ";
                    }
                    $nameStr .= $itemData['item_attr'][$index][0]['name'];
                }
            }
            
            if(strlen($nameStr) > 0) {
                $doiStr = $nameStr;
            }
        } else if($itemData['item_attr_type'][$index]['input_type'] == "link") {
            if(strlen($itemData['item_attr'][$index][0]['attribute_value']) > 0) {
                $links = explode("|", $itemData['item_attr'][$index][0]['attribute_value'], 2);
                $linkStr = "";
                if(strlen($links[0]) > 0 && isset($links[1]) && strlen($links[1]) > 0) {
                    $linkStr = $links[1]."(".$links[0].")";
                } else if(strlen(isset($links[1]) && strlen($links[1]) > 0)) {
                    $linkStr = $links[1];
                } else {
                    $linkStr = $links[0];
                }
                
                if(strlen($linkStr) > 0) {
                    $doiStr = $linkStr;
                }
            }
        } else {
            $doiStr = $itemData['item_attr'][$index][0]['attribute_value'];
        }
        
        return $doiStr;
    }
    
    /**
     * set pdf cover metadata
     * 
     * @param array  $itemData
     * @param int    $index
     * @param string $itemLang
     * @return array $metadataList
     * 
     */
    private function makePdfCoverMetadataList($itemData, $index, $itemLang)
    {
        $metadataList = array();
        $metadata = array();
        
        if($itemData['item_attr_type'][$index]['input_type'] == "name") {
            $metadata["name"] = $itemData['item_attr_type'][$index]['attribute_name'];
            $metadata["value"] = "";
            for($jj=0; $jj<count($itemData['item_attr'][$index]); $jj++) {
                if(strtolower($itemData['item_attr_type'][$index]['display_lang_type']) == "english") {
                    $nameStr = $itemData['item_attr'][$index][$jj]['name'];
                    if(strlen($itemData['item_attr'][$index][$jj]['family']) > 0) {
                        if(strlen($nameStr) > 0) {
                            $nameStr .= " ";
                        }
                        $nameStr .= $itemData['item_attr'][$index][$jj]['family'];
                    }
                } else {
                    $nameStr = $itemData['item_attr'][$index][$jj]['family'];
                    if(strlen($itemData['item_attr'][$index][$jj]['name']) > 0) {
                        if(strlen($nameStr) > 0) {
                            $nameStr .= " ";
                        }
                        $nameStr .= $itemData['item_attr'][$index][$jj]['name'];
                    }
                }
                
                if(strlen($nameStr) > 0) {
                    if(strlen($metadata["value"]) > 0) {
                        $metadata["value"] .= ", ";
                    }
                    $metadata["value"] .= $nameStr;
                }
            }
            if(strlen($metadata["value"]) > 0) {
                array_push($metadataList, $metadata);
            }
        } else if($itemData['item_attr_type'][$index]['input_type'] == "biblio_info") {
            // jtitle
            $jtitle = "";
            if($itemLang == "ja") {
                if(strlen($itemData['item_attr'][$index][0]['biblio_name']) > 0) {
                    $jtitle = $itemData['item_attr'][$index][0]['biblio_name'];
                }
                if(strlen($jtitle) == 0) {
                    $jtitle = $itemData['item_attr'][$index][0]['biblio_name_english'];
                }
            } else {
                if(strlen($itemData['item_attr'][$index][0]['biblio_name_english']) > 0) {
                    $jtitle = $itemData['item_attr'][$index][0]['biblio_name_english'];
                }
                if(strlen($jtitle) == 0) {
                    $jtitle = $itemData['item_attr'][$index][0]['biblio_name'];
                }
            }
            if(strlen($jtitle) >0) {
                if($itemLang == "ja") {
                    $metadata["name"] = self::BIBLIO_JTITLE_JP;
                } else {
                    $metadata["name"] = self::BIBLIO_JTITLE_EN;
                }
                $metadata["value"] = $jtitle;
                array_push($metadataList, $metadata);
            }
            
            // volume
            if(strlen($itemData['item_attr'][$index][0]['volume']) > 0) {
                if($itemLang == "ja") {
                    $metadata["name"] = self::BIBLIO_VOLUME_JP;
                } else {
                    $metadata["name"] = self::BIBLIO_VOLUME_EN;
                }
                $metadata["value"] = $itemData['item_attr'][$index][0]['volume'];
                array_push($metadataList, $metadata);
            }
            
            // issue
            if(strlen($itemData['item_attr'][$index][0]['issue']) > 0) {
                if($itemLang == "ja") {
                    $metadata["name"] = self::BIBLIO_ISSUE_JP;
                } else {
                    $metadata["name"] = self::BIBLIO_ISSUE_EN;
                }
                $metadata["value"] = $itemData['item_attr'][$index][0]['issue'];
                array_push($metadataList, $metadata);
            }
            
            // pages
            $pages = $itemData['item_attr'][$index][0]['start_page'];
            if(strlen($itemData['item_attr'][$index][0]['end_page']) > 0) {
                if(strlen($pages) > 0) {
                    $pages .= "-";
                }
                $pages .= $itemData['item_attr'][$index][0]['end_page'];
            }
            if(strlen($pages) >0) {
                if($itemLang == "ja") {
                    $metadata["name"] = self::BIBLIO_PAGE_JP;
                } else {
                    $metadata["name"] = self::BIBLIO_PAGE_EN;
                }
                $metadata["value"] = $pages;
                array_push($metadataList, $metadata);
            }
            
            // dateofissued
            if(strlen($itemData['item_attr'][$index][0]['date_of_issued']) > 0) {
                if($itemLang == "ja") {
                    $metadata["name"] = self::BIBLIO_DATEOFISSUED_JP;
                } else {
                    $metadata["name"] = self::BIBLIO_DATEOFISSUED_EN;
                }
                $metadata["value"] = $itemData['item_attr'][$index][0]['date_of_issued'];
                array_push($metadataList, $metadata);
            }
        } else if($itemData['item_attr_type'][$index]['input_type'] == "link") {
            $metadata["name"] = $itemData['item_attr_type'][$index]['attribute_name'];
            $metadata["value"] = "";
            for($jj=0; $jj<count($itemData['item_attr'][$index]); $jj++) {
                if(strlen($itemData['item_attr'][$index][$jj]['attribute_value']) > 0) {
                    $links = explode("|", $itemData['item_attr'][$index][$jj]['attribute_value'], 2);
                    $linkStr = "";
                    if(strlen($links[0]) > 0 && isset($links[1]) && strlen($links[1]) > 0) {
                        $linkStr = $links[1]."(".$links[0].")";
                    } else if(strlen(isset($links[1]) && strlen($links[1]) > 0)) {
                        $linkStr = $links[1];
                    } else {
                        $linkStr = $links[0];
                    }
                    
                    if(strlen($linkStr) > 0) {
                        if(strlen($metadata["value"]) > 0) {
                            $metadata["value"] .= "\n";
                        }
                        $metadata["value"] .= $linkStr;
                    }
                }
            }
            if(strlen($metadata["value"]) > 0) {
                array_push($metadataList, $metadata);
            }
        } else {
            $metadata["name"] = $itemData['item_attr_type'][$index]['attribute_name'];
            $metadata["value"] = "";
            for($jj=0; $jj<count($itemData['item_attr'][$index]); $jj++) {
                if(strlen($itemData['item_attr'][$index][$jj]['attribute_value']) > 0) {
                    if(strlen($metadata["value"]) > 0) {
                        $metadata["value"] .= "\n";
                    }
                    $metadata["value"] .= $itemData['item_attr'][$index][$jj]['attribute_value'];
                }
            }
            if(strlen($metadata["value"]) > 0) {
                array_push($metadataList, $metadata);
            }
        }
        
        return $metadataList;
    }
}
?>
