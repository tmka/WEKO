<?php
// --------------------------------------------------------------------
//
// $Id:Servicedocument.class.php 4173 2008-10-31 08:35:00Z nakao $
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
require_once WEBAPP_DIR. '/modules/repository/action/main/sword/SwordUtility.class.php';    // SWORD for WEKO Utility
require_once WEBAPP_DIR. '/modules/repository/action/main/sword/import/Import.class.php';
require_once WEBAPP_DIR. '/modules/repository/files/sword/utils.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryIndexAuthorityManager.class.php';

//  function generateSwordElements($infos, &$response){
//      return 'dummy';
//  }

/**
 * Generate the Service Document for SWORD.
 */
class Repository_Action_Main_Sword_Servicedocument extends RepositoryAction
{
    // 使用コンポーネントを受け取るため
    var $uploadsView = null;
    var $Session = null;
    var $request = null;        // kawa add
    var $response = null;
    var $index_array = array(); // インデックスレコードを階層構造込みでソートた配列(表示用)

    // DBの結果格納
    var $result_item = null; // アイテムテーブル
    var $utility = null;     // SWORD Utility

    // Add flag for get index info or not. 2009/05/11 Y.Nakao
    var $index_flg = null;

    function execute()
    {
        try {
            $result = $this->initAction();
            if ( $result === false ) {
                $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 ); //主メッセージとログIDを指定して例外を作成
                $DetailMsg = null;                              //詳細メッセージ文字列作成
                sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                $exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                $this->failTrans();                                        //トランザクション失敗を設定(ROLLBACK)
                throw $exception;
            }
            $this->utility = new SwordUtility($this->Session, $this->Db, $this->TransStartDate);
            $this->response->setContentType( "application/atomsvc+xml" );
            // Add flag for get index info or not. 2009/05/11 Y.Nakao
            if(strlen($this->index_flg) == 0 || $this->index_flg == "true"){
                // get index tree data paformance up 2010/03/02 Y.Nakao --start--
                $this->index_array = array();
                $this->index_array = $this->getIndexTree();

                // get index tree data paformance up 2010/03/02 Y.Nakao --start--
            }
            //アクション終了処理
            $result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
            if ( $result === false ) {
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );  //主メッセージとログIDを指定して例外を作成
                //$DetailMsg = null;                              //詳細メッセージ文字列作成
                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx3, $埋込み文字1, $埋込み文字2 );
                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                throw $exception;
            }
            // generate Service Document
            $this->generateServiceDocument($str_repository_xml);
            // encode to UTF-8
            $str_repository_xml_utf8 = mb_convert_encoding($str_repository_xml, "UTF-8", "auto");

            //send the Service Document
            header("Content-Type: text/xml; charset=utf-8");
            echo $str_repository_xml_utf8;
            exit();
            //$this->uploadsView->download($str_repository_xml_utf8, "repository.xml", "application/atomsvc+xml");

        }
        catch ( RepositoryException $Exception) {
            //アクション終了処理
            $this->exitAction();                   //トランザクションが失敗していればROLLBACKされる
            // 空っぽを配信
            $this->result_item = array();
            $this->generateServiceDocument($str_repository_xml);
            // 文字コード変更
            $str_repository_xml_utf8 = mb_convert_encoding($str_repository_xml, "UTF-8", "auto");
            //ダウンロード処理をCALL=>これでRSSが相手側に送信される
            //$this->uploadsView->download($str_repository_xml_utf8, "repository.xml", "application/atomsvc+xml");

            //異常終了
            //return "error";
        }
    }

    /**
     * [[generate Service Document]]
     */
    function generateServiceDocument(&$str){
        // ------------------------------------------------------------
        // This method is implemented based on SWORD profile 1.3.
        // http://www.swordapp.org/docs/sword-profile-1.3.html
        // ------------------------------------------------------------
        $error_msg = '';
        $workspace;     // Worlspace Infos
        // Get SWORD for WEKO workspace informations
        $res = $this->utility->getSwordWorkspace($workspace, $error_msg);
        if($res == false) {
            return;
        }
        $collect_num = count($workspace['collections']);

        // Header
        $str = "<?xml version=\"1.0\" encoding=\"utf-8\"?>" . "\n";
        // Service/app and SWORD extention
        //  sword:level (0 or 1)         :
        //   Level 0 requires implementation of a minimal set of mandatory elements as defined below.
        //   Full level 1 compliance REQUIRES implementation of the full set of extension elements and compliance with APP [ATOMPUB] as indicated by the SWORD profile of APP specified in this document.
        //  sword:verbose (true or false): Verbose Supported
        //  sword:noOp (true or false)   : no-Op Supported
        $str .="<service xmlns:dcterms=\"http://purl.org/dc/terms/\" xmlns=\"http://www.w3.org/2007/app\" xmlns:atom=\"http://www.w3.org/2005/Atom\" xmlns:sword=\"http://purl.org/net/sword/\">" . "\n";
        // V1.2 => V1.3 : <sword:level> removed.
//      $str .="  <sword:level>" . $workspace['workspace']['level']  ."</sword:level>\n";
        // V1.2 => V1.3 : <sword:version> added.
        $str .="  <sword:version>".$workspace['workspace']['version']."</sword:version>" . "\n";
        $str .="  <sword:verbose>".$workspace['workspace']['verbose']."</sword:verbose>" . "\n";
        $str .="  <sword:noOp>".   $workspace['workspace']['noOp']   ."</sword:noOp>" . "\n";
        if(isset($workspace['workspace']['maxUploadSize'])) {
            // V1.2 => V1.3 : <sword:maxUploadSize> added (option).
            $str .="  <sword:maxUploadSize>".$workspace['workspace']['maxUploadSize']."</sword:maxUploadSize>" . "\n";
        }
        // Workspace/app
        //  atom:title    : Repository Name
        $str .="  <workspace>" . "\n";
        $str .="    <atom:title>". htmlspecialchars($workspace['workspace']['repository_name']) ."</atom:title>" . "\n";
        // nCnt-th Collection
        for ( $nCnt = 0; $nCnt < $collect_num; $nCnt++ ){
            //  Collection    : Collection uri
            //   atom:title   : Collection Name
            //   atom:accept  : accepted file type
            $str .="    <collection href=\"". htmlspecialchars($workspace['collections'][$nCnt]['collection'], ENT_QUOTES, 'UTF-8')."\">" . "\n";
            $str .="      <atom:title>". $workspace['collections'][$nCnt]['collection_name'] ."</atom:title>" . "\n";
            for ( $nCnt2 = 0; $nCnt2 < count($workspace['collections'][$nCnt]['accept']); $nCnt2++ ){
                $str .="      <accept>". $workspace['collections'][$nCnt]['accept'][$nCnt2] ."</accept>" . "\n";
            }
            // Workspace/SWORD extention
            //   dcterms:abstract       : Collection description
            //   sword:mediation        : Mediation allowed
            //   sword:treatment        : Treatment description
            //   sword:collectionPolicy : Collection Policy
            //   sword:formatNamespace  : Format Namespace
            $str .="      <dcterms:abstract>". $workspace['collections'][$nCnt]['abstract'] ."</dcterms:abstract>" . "\n";
            $str .="      <sword:mediation>". $workspace['collections'][$nCnt]['mediation'] ."</sword:mediation>" . "\n";
            $str .="      <sword:treatment>". $workspace['collections'][$nCnt]['treatment'] ."</sword:treatment>" . "\n";
            $str .="      <sword:collectionPolicy>". $workspace['collections'][$nCnt]['collectionPolicy'] ."</sword:collectionPolicy>" . "\n";
            $str .="      <sword:formatNamespace>". $workspace['collections'][$nCnt]['formatNamespace'] ."</sword:formatNamespace>" . "\n";
            // Hooter
            $str .="    </collection>" . "\n";
        }
        // Workspace/WEKO extention (?)
        // send indextree information (import to)
        //   repository_index       : =>fired
        //   rdf:Description        : index(uri?)
        //    dc:identifier         : index_id
        //    dc:title              : index_name
        $str .= "    <rdf:RDF xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\" xmlns:dc=\"http://purl.org/metadata/dublin_core#\">" . "\n";

        // get index tree data paformance up 2010/03/02 Y.Nakao --start--
        if(count($this->index_array) > 0)
        {
            $this->outIndexTree($this->index_array, $this->index_array[0], "", $str);
        }
        // get index tree data paformance up 2010/03/02 Y.Nakao --end--

        $str .= "    </rdf:RDF>" . "\n";
        $str .="  </workspace>" . "\n";
        $str .="</service>" . "\n";
    }

    // get index tree data paformance up 2010/03/02 Y.Nakao --start--
    /**
     * get index tree
     *
     * @return unknown
     */
    function getIndexTree(){

        // Fix check view authority for servicedocument. Y.Nakao 2013/06/12 --start--
        $user_auth_id = $this->Session->getParameter("_user_auth_id");
        $user_id = $this->Session->getParameter("_user_id");
        // files/servicedosument.php -> Login.class.php でSessionに_auth_idは格納しているが、
        // ここに来た時点でページ情報(block_id等)がなくなっているのでページ権限を示す_auth_idは1になっている
        // このため、再取得を行う
        $auth_id = $this->getRoomAuthorityID($user_id);

        $swordImport = new Repository_Action_Main_Sword_Import($this->Session, $this->Db, $this->TransStartDate);

        $index = array();
        // log report have closed indexs.
        $query = " SELECT idx.index_id, idx.index_name, idx.index_name_english, idx.parent_index_id, idx.access_role, idx.access_group, idx.owner_user_id ".
                " FROM ".DATABASE_PREFIX."repository_index AS idx ";
        //検索特化対応
        if(_REPOSITORY_HIGH_SPEED){
            $query .= " WHERE owner_user_id = '".$user_id."' AND idx.is_delete = '0' ";
        }else{
            // Mod OpenDepo 2014/01/31 S.Arata --start--
            if($user_auth_id >= $this->repository_admin_base && $auth_id >= $this->repository_admin_room)
            {
                // 管理者は全て閲覧可能
            }
            else
            {
                // 管理者ではない場合、閲覧権限がある かつ 投稿権限がある
                // Add OpenDepo 2013/12/02 R.Matsuura --start--
                $this->setConfigAuthority();
                $indexAuthorityManager = new RepositoryIndexAuthorityManager($this->Session, $this->dbAccess, $this->TransStartDate);
                $publicIndexQuery = $indexAuthorityManager->getPublicIndexQuery(false, $this->repository_admin_base, $this->repository_admin_room);
                // Add OpenDepo 2013/12/02 R.Matsuura --end--
                $query .= " INNER JOIN (".$publicIndexQuery.") pub ON idx.index_id = pub.index_id ";
            }
            $query .= " WHERE idx.is_delete = '0' ";
            // Mod OpenDepo 2014/01/31 S.Arata --end--
        }
        $query .= " ORDER BY show_order, index_id ";
        $result = $this->Db->execute($query);
        if($result === false){
            $this->error_msg = $this->Db->ErrorMsg();
            //アクション終了処理
            $result = $this->exitAction();   //トランザクションが成功していればCOMMITされる
            return false;
        }
        for($ii=0; $ii<count($result); $ii++)
        {
            // 投稿権限チェック
            $access_role = ",".$result[$ii]["access_role"].",";
            $access_group = ",".$result[$ii]["access_group"].",";
            // Fix check index authority 2013/06/12 Y.Nakao --start--
            if( !$swordImport->checkAccessIndex($access_role, $access_group, $result[$ii]["owner_user_id"]) )
            {
                // プライベートツリーではない かつ 投稿権限がない
                continue;
            }
            // Fix check index authority 2013/06/12 Y.Nakao --end--

            $node = array(
                        'id'=>$result[$ii]['index_id'],
                        'name'=>"",
                        'pid'=>$result[$ii]['parent_index_id']);
            if(strlen($result[$ii]['index_name']) > 0){
                $node['name'] = $result[$ii]['index_name'];
            } else {
                $node['name'] = $result[$ii]['index_name_english'];
            }

            if(!isset($index[$node['pid']])){
                $index[$node['pid']] = array();
            }
            array_push($index[$node['pid']], $node);
        }
        // Fix check view authority for servicedocument. Y.Nakao 2013/06/12 --end--
        return $index;
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $all_index
     * @param unknown_type $index
     * @param unknown_type $parent_name
     */
    function outIndexTree(&$all_index, $index, $parent_name, &$str){
        //$log_file = "IndexTree.txt";
        //$log_report = fopen($log_file, "a");
        foreach ($index as $key => $val){
            $val['disp_name'] = $val['name'];
            if(strlen($parent_name) > 0){
                $val['disp_name'] = $parent_name.$val['name'];
            }
            $indexUri = htmlspecialchars(BASE_URL.'/?action=repository_oaiore&indexId='.$val['id'], ENT_QUOTES, 'UTF-8');
            $str .= "      <rdf:Description rdf:about=\"" . $indexUri . "\">" . "\n";
            $str .= "        <dc:identifier>". $val['id'] ."</dc:identifier>" . "\n";
            $str .= "        <dc:title>".htmlspecialchars($val['disp_name'], ENT_QUOTES, "UTF-8")."</dc:title>" . "\n";
            $str .= "      </rdf:Description>\n";
            if(isset($all_index[$val['id']]) && is_array($all_index[$val['id']]) && count($all_index[$val['id']]) > 0){
                $this->outIndexTree($all_index, $all_index[$val['id']], str_replace($val['name'], "", $val['disp_name'])."--", $str);
                unset($all_index[$val['id']]);
            }
        }
    }
    // get index tree data paformance up 2010/03/02 Y.Nakao --end--
}

?>
