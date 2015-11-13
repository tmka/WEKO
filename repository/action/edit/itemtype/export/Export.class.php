<?php
// --------------------------------------------------------------------
//
// $Id: Export.class.php 56708 2015-08-19 13:08:03Z tomohiro_ichikawa $
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
require_once WEBAPP_DIR. '/modules/repository/action/main/export/ExportCommon.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryDownload.class.php';

class Repository_Action_Edit_Itemtype_Export extends RepositoryAction
{
	// Request param
	var $item_type_id = null;	// Select item type ID
	
	// Component
	var $uploadsView = null;
	
	public $Session = null;
	
	public $Db = null;
	
	/**
	 * [[機能説明]]
	 *
	 * @access  public
	 */
	function execute()
	{
		try {
			////////// Init //////////
			$result = $this->initAction();
			if ( $result === false ){
				$exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );	//主メッセージとログIDを指定して例外を作成
				$DetailMsg = null;							  //詳細メッセージ文字列作成
				sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
				$exception->setDetailMsg( $DetailMsg );			 //詳細メッセージ設定
				$this->failTrans();										//トランザクション失敗を設定(ROLLBACK)
				throw $exception;
			}
			$buf = "";
			$output_files = array();
			// get export common
			$export_common = new ExportCommon($this->Db, $this->Session, $this->TransStartDate);
			
			////////// mkdir //////////
            $this->infoLog("businessWorkdirectory", __FILE__, __CLASS__, __LINE__);
            $businessWorkdirectory = BusinessFactory::getFactory()->getBusiness('businessWorkdirectory');
            $tmp_dir = $businessWorkdirectory->create();
            $tmp_dir = substr($tmp_dir, 0, -1);
            
			////////// make xml text & icon file ///////////
			$buf = "<?xml version=\"1.0\"?>\n" .
					"<export>\n";
			$export_info = $export_common->createItemTypeExportFile($tmp_dir, $this->item_type_id);
			if($export_info === false){
				return false;
			}
			
			$buf .= $export_info["buf"];
			$buf .= "	</export>\n";
			
			////////// make xml file //////////
			$filename = $tmp_dir . "/import.xml";
			$fp = @fopen( $filename, "w" );
			if (!$fp){
				return false;
			}
			fputs($fp, $buf);
			if ($fp){
				fclose($fp);
			}
			////////// make zip file //////////
			array_push($output_files, $export_info["output_files"]);
			array_push($output_files, $filename );	// xml file name
			
			// make zip file
			$zip_file = "export.zip";
			File_Archive::extract(
				$output_files,
				File_Archive::toArchive($zip_file, File_Archive::toFiles( $tmp_dir."/" ))
			);
			
			/////////// Download //////////
			// DL action
			// Add RepositoryDownload action 2010/03/30 A.Suzuki --start--
			$repositoryDownload = new RepositoryDownload();
			$repositoryDownload->downloadFile($tmp_dir."/".$zip_file, "export.zip");
			//$this->uploadsView->download($bret, "export.zip");
			// Add RepositoryDownload action 2010/03/30 A.Suzuki --end--
			
			// del dir
			$this->removeDirectory($tmp_dir);

			// exit
			$result = $this->exitAction();
			if ( $result == false ){
				return false;
			}
			
			exit();
			
		} catch ( RepositoryException $exception){
			return false;
		}
	}
}
?>
