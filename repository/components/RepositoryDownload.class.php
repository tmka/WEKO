<?php
// --------------------------------------------------------------------
//
// $Id: RepositoryDownload.class.php 31589 2014-02-12 02:00:46Z tomohiro_ichikawa $
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

class RepositoryDownload extends RepositoryAction
{
	/**
	 * INIT
	 */
	function RepositoryDownload() {
		
    }
    
    /**
     * download binary
     *
     * @param string $data file data (binary)
     * @param string $filename
     * @param string $mimetype
     * @param string $str_code
     */
	function download($data, $filename, $mimetype = null, $str_code=null) {
		if($mimetype == null) {
			$mimetype = $this->mimeinfo("type", $filename);
		}
		$this->_headerOutput($filename, strlen($data), $mimetype, $str_code);
		
		echo $data;
    }
    
    /**
     * download file
     *
     * @param string $filepath file path
     * @param string $fileName
     * @param string $mimetype
     * @param string $str_code
     */
    function downloadFile($filepath, $filename, $mimetype = null, $str_code=null)
    {
        if($mimetype == null) {
            $mimetype = $this->mimeinfo("type", $filename);
        }
        $this->_headerOutput($filename, filesize($filepath), $mimetype, $str_code);
        
        // Fix 大容量ファイルのダウンロード 2013/07/10 Y.Nakao --start--
        // 大容量ファイルのダウンロードについて
        // 200MBまでなら readfile($filepath); でダウンロードできる
        // それ以上となると ob_xxx によるバッファ対応が必要。
        // ob_start(); で始まって ob_end_clean(); で終了（これは対になっている/fopen fcloseと同じ関係）
        // 下記のようにネストにできる
        // ob_start(); 
        //   ob_start();
        //   ob_end_clean();
        //   ob_start();
        //     ob_start();
        //     ob_end_clean();
        //   ob_end_clean();
        // ob_end_clean();
        // バッファは ob_flush(); flush(); で出力できる ※ただしネストが1の場合のみ有効
        // ブラウザによってob_flush(); flush();のどちらか、もしくはどちらでも動作するので両方書く
        // 下記ではflushがネスト1のみ有効なのでネスト数を取得する ob_get_level(); を使って
        // 全ネストをクリアするように対応している
        
        // キャッシュクリア
        clearstatcache();
        // バッファのネストを全てクリア
        $lev = ob_get_level();
        while(ob_get_level() != 0)
        {
            ob_end_clean();
        }
        // ファイルを出力
        ob_start();
        $handle = fopen($filepath, 'rb');
        while (!feof($handle))
        {  
            echo fread($handle, 4096);
            ob_flush();
            flush();
        }
        fclose($handle);
        ob_end_clean();
        // Fix 大容量ファイルのダウンロード 2013/07/10 Y.Nakao --end--
    }
	
	function _headerOutput($filename, $filesize, $mimetype, $str_code) {
    	if (stristr($_SERVER['HTTP_USER_AGENT'], "MSIE") || stristr($_SERVER['HTTP_USER_AGENT'], "Trident")) {
			// IEの場合
			//header("Content-disposition: inline; filename=\"".mb_convert_encoding($filename, "SJIS", _CHARSET)."\"");
			header("Content-disposition: attachment; filename=\"".mb_convert_encoding($filename, "SJIS", _CHARSET)."\"");
            // for IE8 over version.
            header("X-Content-Type-Options: nosniff");
		} elseif (stristr($_SERVER['HTTP_USER_AGENT'], "Opera")) {
			// Operaの場合
			header("Content-disposition: attachment; filename=\"".$filename."\"");
		} elseif (stristr($_SERVER['HTTP_USER_AGENT'], "Firefox")) {
			// FireFoxの場合
			header("Content-disposition: attachment; filename=\"".$filename."\"");
		} else {
			// 上記以外(Mozilla, Firefox, NetScape)
			//header("Content-disposition: inline; filename=\"".$filename."\"");
			header("Content-disposition: attachment; filename=\"".$filename."\"");
		}
		
		//header("Content-disposition: inline; filename=\"".$filename."\"");
		//TODO:設定によっては画像キャッシュをさせる設定があってもよいきはする。
		//システム設定等にローカルキャッシュを有効にする設定を設ける？？
    	//header("Cache-Control: no-store, no-cache, must-revalidate");
		//header("Pragma: no-cache");
		if(stristr($_SERVER['HTTP_USER_AGENT'], "MSIE") || stristr($_SERVER['HTTP_USER_AGENT'], "Trident")){
			if ( (false === empty($_SERVER['HTTPS']))&&('off' !==$_SERVER['HTTPS'])) {
				// HTTPS
				header("Pragma: public");
			} else {
				// HTTP
				header("Pragma: no-cache");
			}
		} else {
			header("Pragma: no-cache");
		}
		header("Cache-Control: public");//キャッシュを有効にする設定(private or public)
		if($str_code == null){
		header("Content-type: document/unknown;charset=UTF-8;");
		} else {
			header("Content-type: document/unknown;charset=".$str_code.";");
		}
		
		header("Content-length: ".$filesize);
		header("Content-type: ".$mimetype);
		
		//header("Content-type: application/force-download");
		//header("Content-type: ForceType application/octet-stream");
		//header("Content-type: AddType application/octet-stream");
		//header("Content-type: application/octet-stream");
    }
    
    /**
	 * Mimeタイプ取得
	 * @param int key(type or icon)
	 * @return string mime_type
	 * @access	public
	 */
    function mimeinfo($key, $filename) {
	    $mimeinfo = array (
	        "xxx"  => array ("type"=>"document/unknown", "icon"=>"unknown.gif"),
	        "3gp"  => array ("type"=>"video/quicktime", "icon"=>"video.gif"),
	        "ai"   => array ("type"=>"application/postscript", "icon"=>"image.gif"),
	        "aif"  => array ("type"=>"audio/x-aiff", "icon"=>"audio.gif"),
	        "aiff" => array ("type"=>"audio/x-aiff", "icon"=>"audio.gif"),
	        "aifc" => array ("type"=>"audio/x-aiff", "icon"=>"audio.gif"),
	        "applescript"  => array ("type"=>"text/plain", "icon"=>"text.gif"),
	        "asc"  => array ("type"=>"text/plain", "icon"=>"text.gif"),
	        "au"   => array ("type"=>"audio/au", "icon"=>"audio.gif"),
	        "avi"  => array ("type"=>"video/x-ms-wm", "icon"=>"avi.gif"),
	        "bmp"  => array ("type"=>"image/bmp", "icon"=>"image.gif"),
	        "cs"   => array ("type"=>"application/x-csh", "icon"=>"text.gif"),
	        "css"  => array ("type"=>"text/css", "icon"=>"text.gif"),
	        "csv"  => array ("type"=>"text/plain", "icon"=>"csv.gif"),
	        "dv"   => array ("type"=>"video/x-dv", "icon"=>"video.gif"),
	        "doc"  => array ("type"=>"application/msword", "icon"=>"word.gif"),
	        "docx"  => array ("type"=>"application/vnd.openxmlformats-officedocument.wordprocessingml.document", "icon"=>"word.gif"),
	        "dif"  => array ("type"=>"video/x-dv", "icon"=>"video.gif"),
	        "eps"  => array ("type"=>"application/postscript", "icon"=>"pdf.gif"),
	        "gif"  => array ("type"=>"image/gif", "icon"=>"image.gif"),
	        "gtar" => array ("type"=>"application/x-gtar", "icon"=>"zip.gif"),
	        "gz"   => array ("type"=>"application/g-zip", "icon"=>"zip.gif"),
	        "gzip" => array ("type"=>"application/g-zip", "icon"=>"zip.gif"),
	        "h"    => array ("type"=>"text/plain", "icon"=>"text.gif"),
	        "hqx"  => array ("type"=>"application/mac-binhex40", "icon"=>"zip.gif"),
	        "html" => array ("type"=>"text/html", "icon"=>"html.gif"),
	        "htm"  => array ("type"=>"text/html", "icon"=>"html.gif"),
	        "jpe"  => array ("type"=>"image/jpeg", "icon"=>"image.gif"),
	        "jpeg" => array ("type"=>"image/jpeg", "icon"=>"image.gif"),
	        "jpg"  => array ("type"=>"image/jpeg", "icon"=>"image.gif"),
	        "js"   => array ("type"=>"application/x-javascript", "icon"=>"text.gif"),
	        "latex"=> array ("type"=>"application/x-latex", "icon"=>"text.gif"),
	        "m"    => array ("type"=>"text/plain", "icon"=>"text.gif"),
	    	"flv"  => array ("type"=>"video/x-flv", "icon"=>"video.gif"),
	        "mov"  => array ("type"=>"video/quicktime", "icon"=>"video.gif"),
	        "movie"=> array ("type"=>"video/x-sgi-movie", "icon"=>"video.gif"),
	        "m3u"  => array ("type"=>"audio/x-mpegurl", "icon"=>"audio.gif"),
	        "mp3"  => array ("type"=>"audio/mp3", "icon"=>"audio.gif"),
	        "mp4"  => array ("type"=>"video/mp4", "icon"=>"video.gif"),
	        "mpeg" => array ("type"=>"video/mpeg", "icon"=>"video.gif"),
	        "mpe"  => array ("type"=>"video/mpeg", "icon"=>"video.gif"),
	        "mpg"  => array ("type"=>"video/mpeg", "icon"=>"video.gif"),
	        "pct"  => array ("type"=>"image/pict", "icon"=>"image.gif"),
	        "pdf"  => array ("type"=>"application/pdf", "icon"=>"pdf.gif"),
	        "php"  => array ("type"=>"text/plain", "icon"=>"text.gif"),
	        "pic"  => array ("type"=>"image/pict", "icon"=>"image.gif"),
	        "pict" => array ("type"=>"image/pict", "icon"=>"image.gif"),
	        "png"  => array ("type"=>"image/png", "icon"=>"image.gif"),
	        "pps"  => array ("type"=>"application/vnd.ms-powerpoint", "icon"=>"powerpoint.gif"),
	        "ppt"  => array ("type"=>"application/vnd.ms-powerpoint", "icon"=>"powerpoint.gif"),
	        "pptx"  => array ("type"=>"application/vnd.openxmlformats-officedocument.presentationml.presentation", "icon"=>"powerpoint.gif"),
	        "ps"   => array ("type"=>"application/postscript", "icon"=>"pdf.gif"),
	        "qt"   => array ("type"=>"video/quicktime", "icon"=>"video.gif"),
	        "ra"   => array ("type"=>"audio/x-realaudio", "icon"=>"audio.gif"),
	        "ram"  => array ("type"=>"audio/x-pn-realaudio", "icon"=>"audio.gif"),
	        "rm"   => array ("type"=>"audio/x-pn-realaudio", "icon"=>"audio.gif"),
	        "rtf"  => array ("type"=>"text/rtf", "icon"=>"text.gif"),
	        "rtx"  => array ("type"=>"text/richtext", "icon"=>"text.gif"),
	        "sh"   => array ("type"=>"application/x-sh", "icon"=>"text.gif"),
	        "sit"  => array ("type"=>"application/x-stuffit", "icon"=>"zip.gif"),
	        "smi"  => array ("type"=>"application/smil", "icon"=>"text.gif"),
	        "smil" => array ("type"=>"application/smil", "icon"=>"text.gif"),
	        "swf"  => array ("type"=>"application/x-shockwave-flash", "icon"=>"flash.gif"),
	        "tar"  => array ("type"=>"application/x-tar", "icon"=>"zip.gif"),
	        "tgz"  => array ("type"=>"application/x-tar", "icon"=>"zip.gif"),
	        "tif"  => array ("type"=>"image/tiff", "icon"=>"image.gif"),
	        "tiff" => array ("type"=>"image/tiff", "icon"=>"image.gif"),
	        "tex"  => array ("type"=>"application/x-tex", "icon"=>"text.gif"),
	        "texi" => array ("type"=>"application/x-texinfo", "icon"=>"text.gif"),
	        "texinfo"  => array ("type"=>"application/x-texinfo", "icon"=>"text.gif"),
	        "tsv"  => array ("type"=>"text/tab-separated-values", "icon"=>"text.gif"),
	        "txt"  => array ("type"=>"text/plain", "icon"=>"text.gif"),
	        "wav"  => array ("type"=>"audio/wav", "icon"=>"audio.gif"),
	        "wmv"  => array ("type"=>"video/x-ms-wmv", "icon"=>"avi.gif"),
	        "asf"  => array ("type"=>"video/x-ms-asf", "icon"=>"avi.gif"),
	        "xls"  => array ("type"=>"application/vnd.ms-excel", "icon"=>"excel.gif"),
	        "xlsx"  => array ("type"=>"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", "icon"=>"excel.gif"),
	        "xml"  => array ("type"=>"text/xml", "icon"=>"xml.gif"),
	        "xsl"  => array ("type"=>"text/xml", "icon"=>"xml.gif"),
	        "zip"  => array ("type"=>"application/zip", "icon"=>"zip.gif"),
	        "tex"  => array ("type"=>"application/x-tex", "icon"=>"text.gif"),
	        "dvi"  => array ("type"=>"application/x-dvi", "icon"=>"text.gif"),
	        "ps"   => array ("type"=>"application/postscript", "icon"=>"text.gif"),
	        "ics"  => array ("type"=>"application/octet-stream", "icon"=>"outlook.gif")
	    );
	
	    if (eregi("\.([a-z0-9]+)$", $filename, $match)) {
	        if(isset($mimeinfo[strtolower($match[1])][$key])) {
	            return $mimeinfo[strtolower($match[1])][$key];
	        } else {
	            return $mimeinfo["xxx"][$key];   // By default
	        }
	    } else {
	        return $mimeinfo["xxx"][$key];   // By default
	    }
	}
}

?>
