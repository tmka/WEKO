<?php
require_once WEBAPP_DIR.'/modules/repository/components/FW/BusinessBase.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/components/mail/Main.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHandleManager.class.php';
/**
 * $Id: Supple.class.php 2015-03-18 06:07:18Z yuya_yamazawa $
 *
 * サプリコンテンツの登録・更新・削除を行う
 *
 * @author IVIS
 */
class Repository_Components_Business_Supple extends BusinessBase
{
    /**
     * メールに表示するサプリコンテンツ情報の最大数
     * @var string
     */
    const SEND_MAIL_DISP_SUPPLE_ITEM_MAX_NUM = 50;

    /**
     * セッション
     * @var セッション
     */
    private $session = null;

    /**
     * サプリWEKOURL
     * @var サプリWEKOURL string
     */
    private $suppleWEKOURL = null;

    /**
     * サプリコンテンツを登録する
     * 1.サプリコンテンツ登録のための情報取得
     * 1-1.サプリWEKO側からサプリコンテンツ情報を取得する
     * 1-2.アイテムタイプ情報を取得する
     * 1-3.サプリコンテンツのサプリNoを取得
     * 1-4.査読メール送信フラグ取得
     * 2.サプリコンテンツの登録
     * 2-1.登録するサプリコンテンツが既に登録されているか確認(登録されている場合は、例外を投げる)
     * 2-2.サプリコンテンツを登録する
     * 3.査読メールを送信する(査読メールフラグでメールを送るか判断)
     *
     * @param アイテムID $itemID string
     * @param アイテムNo $itemNo string
     * @param サプリコンテンツURL $suppleContentsURL string
     * @throws AppException 例外のコード
     */
    public function entrySuppleContents($itemID, $itemNo, $suppleContentsURL)
    {
        // 登録情報を取得
        // サプリ情報を取得
        $supple_data = $this->suppleInfoFromSuppleWEKO($suppleContentsURL);

        // アイテムタイプ情報を取得
        $item_type_result = $this->itemTypeInfo($itemID, $itemNo);

        // サプリコンテンツのサプリNoを取得
        $supple_num = 0;
        $supple_num = $this->entrySuppleNo($itemID, $itemNo,$item_type_result);

        // 査読メール送信フラグ取得
        $sendflag = $this->sendMailFlag($review_mail_flg,$review_flg_supple);

        // 登録するサプリコンテンツが既に登録されているか
        $resulta = $this->existEntrySuppleContents($itemID,$itemNo,$supple_data["supple_weko_item_id"],$exitSuppleInfo);
        if($resulta === true){
            throw new AppException("repository_entry_exist_supple_contents");
        }
        // サプリコンテンツの登録を行う
        $result = $this->entrySupple($itemID,$itemNo,$supple_num,$item_type_result,$supple_data,$review_flg_supple);

        // 査読メールの送信
        $suppleInfoForSendMail = array();
        array_push($suppleInfoForSendMail, $supple_data);
        $this->sendSuppleMail($sendflag,$suppleInfoForSendMail,$item_type_result);
    }

    /**
     * インポート時のサプリメンタルコンテンツ登録
     * @param string $itemID アイテムID
     * @param string $itemNo アイテムNo
     * @param array $suppleContentsURL サプリメンタルコンテンツURL
     * @throws AppException
     */
    public function entrySuppleContentsForImport($itemID, $itemNo, $suppleContentsURL)
    {
        $suppleInfoForSendMail = array();
        $errorList = array();
        $sendflag = 0;

        // アイテムタイプ情報を取得
        $item_type_result = $this->itemTypeInfo($itemID, $itemNo);

        // 査読メール送信フラグ取得
        $sendflag = $this->sendMailFlag($review_mail_flg,$review_flg_supple);

        foreach ($suppleContentsURL as $url)
        {
            try {
                // サプリ情報を取得
                $supple_data = $this->suppleInfoFromSuppleWEKO($url);
                array_push($suppleInfoForSendMail, $supple_data);

                // サプリコンテンツのサプリNoを取得
                $supple_num = 0;
                $supple_num = $this->entrySuppleNo($itemID, $itemNo,$item_type_result);

                // 登録するサプリコンテンツが既に登録されているか
                $resulta = $this->existEntrySuppleContents($itemID,$itemNo,$supple_data["supple_weko_item_id"],$exitSuppleInfo);
                if($resulta === true){
                    throw new AppException("repository_entry_exist_supple_contents");
                }
                // サプリコンテンツの登録を行う
                $result = $this->entrySupple($itemID,$itemNo,$supple_num,$item_type_result,$supple_data,$review_flg_supple);
            }
            catch(AppException $e)
            {
                // 例外が発生した場合、一度キャッチ。すべての例外を出力するのは
                array_push($errorList, $e->getMessage());
            }
        }
        if(count($suppleInfoForSendMail) > 0)
        {
            // 査読メールの送信
            $this->sendSuppleMail($sendflag,$suppleInfoForSendMail,$item_type_result);
        }

        if(count($errorList) > 0)
        {
            // 複数のサプリでエラーが発生する場合があるため一つの警告メッセージとしてまとめる
            throw new AppException("repository_entry_failed_some_of_supple_entry");
        }
    }

    /**
     * サプリコンテンツを削除する
     * 1.クエリの作成
     * 2.パラメータの設定
     * 3.削除の実行
     *
     * @param アイテムID $itemId string
     * @param アイテムNo $itemNo string
     * @param サプリNo $suppleNo string
     * @param ワークフローかどうかのフラグ $workflow_flag boolean
     * @param ワークフロータブ情報 $workflow_active_tab string
     * @return string
     */
    public function deleteSuppleContents($itemId,$itemNo,$suppleNo)
    {
        $query = "UPDATE ".DATABASE_PREFIX."repository_supple ".
                "SET mod_user_id = ?, del_user_id = ?, mod_date = ?, del_date = ?, is_delete = ? ".
                "WHERE item_id = ? ".
                "AND item_no = ? ".
                "AND supple_no = ?;";
        $params = array();
        $params[] = $this->user_id;				// mod_user_id
        $params[] = $this->user_id;				// del_user_id
        $params[] = $this->accessDate;  	// mod_date
        $params[] = $this->accessDate;  	// del_date
        $params[] = 1;						// is_delete
        $params[] = $itemId;			// item_id
        $params[] = $itemNo;			// item_no
        $params[] = $suppleNo;		// supple_no
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $this->errorLog("MySQL ERROR : For delete SuppleContents", __FILE__, __CLASS__, __LINE__);
            // 削除失敗の例外を上位に投げる
            throw new AppException("repository_delete_failed_delete_supple_contents");
        }
    }

    /**
     * サプリコンテンツの更新を行う
     * 1.サプリコンテンの情報取得
     * 1-1.サプリWEKO側からサプリコンテンツ情報を取得する
     * 1-2.アイテムタイプ情報を取得
     * 1-3.査読メール送信フラグ取得
     * 2.サプリコンテンツ更新
     * 2-1.サプリコンテンツの更新
     * 3.査読メールを送信する
     *
     * @param アイテムID $item_id string
     * @param アイテムNo $item_no string
     * @param attribute_id $attribute_id string
     * @param サプリNo $supple_no string
     * @param Suffix $weko_id string
     * @param サプリ情報 $supple_data string
     * @param リダイレクトURL $redirect_url string
     * @throws AppException
     */
    public function updateSuppleContents($item_id,$item_no,$attribute_id,$supple_no,$weko_id)
    {
        // サプリコンテンツの情報取得
        $supple_data = array();
        $supple_data = $this->suppleInfoByOpenSearch("weko_id", $weko_id);

        // アイテムタイプ情報を取得
        $item_type_result = $this->itemTypeInfo($item_id, $item_no);

        // 査読メール送信フラグ取得
        $sendflag = $this->sendMailFlag($review_mail_flg,$review_flg_supple);

        // サプリコンテンツの更新
        $this->updateSupple($item_id,$item_no,$attribute_id,$supple_no,$supple_data,$review_flg_supple);

        // 査読メールを送信する
        $suppleInfoForSendMail = array();
        array_push($suppleInfoForSendMail, $supple_data);
        $this->sendSuppleMail($sendflag,$suppleInfoForSendMail,$item_type_result);
    }

    /**
     * 1つのアイテム内に登録されているすべてのサプリコンテンツの更新を行う
     * @param string $itemID アイテムID
     * @param string $itemNo アイテムNo
     * @param array $arraySuppleContentsURL サプリコンテンツURLリスト
     * @throws AppException
     */
    public function updateAllSuppleContentsOfOneItemForImport($itemID, $itemNo, $arraySuppleContentsURL)
    {
        $this->updateAllSuppleContentsOfOneItem($itemID, $itemNo, $arraySuppleContentsURL, $errorInfoList);

        // サプリコンテンツ登録・更新・削除中にエラーが発生された場合、1つの警告メッセージとしてまとめる
        // 複数のサプリコンテンツエラーが発生した場合を考慮。
        if(count($errorInfoList)>0)
        {
            $this->errorLog("Failed to entry some of suppleContents", __FILE__, __CLASS__, __LINE__);
            throw new AppException("repository_entry_failed_some_of_supple_entry");
        }
    }



    /**
     * 初期化を行う
     * 1.サプリWEKOのURLを取得する
     * 2.YハンドルのPrefixを取得する
     * 3.サプリWEKOのURLとYハンドルのPrefixIDが設定されているか確認
     *
     * @see BusinessBase::onInitialize()
     */
    public function onInitialize()
    {
        $container = & DIContainerFactory::getContainer();
        $this->session = $container->getComponent("Session");

        // サプリWEKOのURLを取得する
        $this->suppleWEKOURL = $this->suppleWEKOURL();

        // RepositoryHandleManagerのインスタンス作成
        $repositoryHandleManager = new RepositoryHandleManager($this->session, $this->Db, $this->accessDate);

        $prefixId = "";
        if(isset($repositoryHandleManager))
        {
            // YハンドルのPrefixを取得する
            $prefixId = $repositoryHandleManager->getPrefix(RepositoryHandleManager::ID_Y_HANDLE);
        }

        // サプリWEKOURLが未設定
        if(!(strlen($this->suppleWEKOURL) > 0))
        {
            $this->errorLog("Not set suppleWEKO URL", __FILE__, __CLASS__, __LINE__);
            throw new AppException("repository_entry_no_supple_weko_url");
        }
        else if(!is_numeric($prefixId))
        {
            $this->errorLog("Not set PrefixID", __FILE__, __CLASS__, __LINE__);
            throw new AppException("repository_entry_no_prefix_id");
        }

        return true;
    }

    /**
     * サプリWEKOからprefixIDを取得する
     * 1.サプリWEKOのOpenSearchのURLを取得する
     * 2.OpenSearchでサプリコンテンツのレスポンスを取得
     * 3.取得したレスポンス（XML）を解析
     * 4.XML内のデータ取得
     *
     * @return PrefixID $supple_prefix_id string
     */
    public function supplePrefixIDByOpenSearch(){
        // サプリWEKOのOpenSearchのURLを取得する
        $send_param = $this->openSearchUrlOfSuppleWeko("prefix",0);

        // OpenSearchでサプリコンテンツのレスポンスを取得
        $response_xml = $this->suppleContentsInfoHttpReqest($send_param);//$charge_body;

        // 取得したレスポンス（XML）を解析
        $vals = $this->parseXmlOfSuppleContent($response_xml);

        /////////////////////////////
        // get XML data
        /////////////////////////////
        $supple_prefix_id = "";
        foreach($vals as $val){
            switch($val['tag']){
                case "DC:IDENTIFIER":
                    // prefixID
                    $supple_prefix_id = $val['value'];
                    break;
                default :
                    break;
            }
        }
        return $supple_prefix_id;
    }

    /**
     * WEKOIDをキーに、サプリWEKOからサプリアイテム情報を取得する
     * 1.サプリWEKOのOpenSearchのURLを取得する
     * 2.OpenSearchでサプリコンテンツのレスポンスを取得
     * 3.取得したレスポンス（XML）を解析
     * 4.XMLデータ検証
     *
     * @param URLのパラメータの種類 $mode "weko_id" or "item_ids" or "prefix"
     * @param URLのパラメータ値 $id
     * @return array $supple_data array
     */
    public function suppleInfoByOpenSearch($mode, $id)
    {
        // サプリWEKOのOpenSearchのURLを取得する
        $send_param = $this->openSearchUrlOfSuppleWeko($mode,$id);

        // OpenSearchでサプリコンテンツのレスポンスを取得
        $response_xml = $this->suppleContentsInfoHttpReqest($send_param);

        // レスポンス（XML）を解析
        $vals = $this->parseXmlOfSuppleContent($response_xml);

        // XMLデータ検証
        $supple_data = $this->analysisXmlDataOfSuppleContentsInfo($vals);

        return $supple_data;
    }

    /**
     * suppleWorkFlowActiveタブへのリダイレクトURLの取得
     * @throws AppException
     * @return number URLのパラメータ値
     */
    public function redirectURLParameterOfSuppleWorkflowActiveTab()
    {
        $supple_workflow_active_tab = 1;

        // サプリコンテンツの査読フラグを取得する
        $query = "SELECT param_value ".
                "FROM ".DATABASE_PREFIX."repository_parameter ".
                "WHERE param_name = 'review_flg_supple';";
        $result = $this->Db->execute($query);
        if($result === false){
            $this->errorLog("MySQL ERROR : For get redirect URL", __FILE__, __CLASS__, __LINE__);
            throw new AppException("repository_update_failed_update_supple_contents");
        }
        $review_flg_supple = $result[0]['param_value'];

        if($review_flg_supple == 1){
            // 査読を行う
            $supple_workflow_active_tab = 1;			// 承認待タブへ
        } else {
            // 査読を行わない（自動的に承認する）
            $supple_workflow_active_tab = 2;			// 承認済タブへ
        }

        return $supple_workflow_active_tab;
    }

    /**
     * 査読承認
     * @param string $itemID アイテムID
     * @param string $itemNo アイテムNo
     * @param string $attributeID attribute_id
     * @param string $suppleNo サプリNo
     * @throws AppException
     */
    public function acceptReview($itemID,$itemNo,$attributeID,$suppleNo)
    {
        // 承認処理
        $query = "UPDATE ". DATABASE_PREFIX ."repository_supple ".
                "SET supple_review_status = ?, ".
                "supple_review_date = ?, ".
                "mod_user_id = ?, ".
                "mod_date = ? ".
                "WHERE item_id = ? ".
                "AND item_no = ? ".
                "AND attribute_id = ? ".
                "AND supple_no = ?;";
        $params = array();
        $params[] = 1;						// supple_review_status
        $params[] = $this->accessDate;	// supple_review_date
        $params[] = $this->user_id;	// mod_user_id
        $params[] = $this->accessDate;	// mod_date
        $params[] = $itemID;			// item_id
        $params[] = $itemNo;			// item_no
        $params[] = $attributeID;	// attribute_id
        $params[] = $suppleNo;		// supple_no
        //UPDATE実行
        $result = $this->Db->execute($query,$params);
        if($result === false){
            $msg = $this->Db->ErrorMsg();
            $this->errorLog($msg, __FILE__, __CLASS__, __LINE__);
            throw new AppException($msg);
        }
    }

    /**
     * 査読却下
     * @param string $itemID アイテムID
     * @param string $itemNo アイテムNo
     * @param string $attributeID $attribute_id
     * @param string $suppleNo サプリNo
     * @param string $rejectReson 却下理由
     * @throws AppException
     */
    public function rejectReview($itemID,$itemNo,$attributeID,$suppleNo,$rejectReson)
    {
        $query = "UPDATE ". DATABASE_PREFIX ."repository_supple ".
                "SET supple_review_status = ?, ".
                "supple_review_date = ?, ".
                "supple_reject_status = ?, ".
                "supple_reject_date = ?, ".
                "supple_reject_reason = ?, ".
                "mod_user_id = ?, ".
                "mod_date = ? ".
                "WHERE item_id = ? ".
                "AND item_no = ? ".
                "AND attribute_id = ? ".
                "AND supple_no = ?;";
        $params = array();
        $params[] = 0;						// supple_review_status
        $params[] = $this->accessDate;	// supple_review_date
        $params[] = 1;						// supple_reject_status
        $params[] = $this->accessDate;	// supple_reject_date
        if($rejectReson == " "){
            $params[] = "";
        } else {
            $params[] = $rejectReson;	// supple_reject_reason
        }
        $params[] = $this->user_id;	// mod_user_id
        $params[] = $this->accessDate;	// mod_date
        $params[] = $itemID;			// item_id
        $params[] = $itemNo;			// item_no
        $params[] = $attributeID;	// attribute_id
        $params[] = $suppleNo;		// supple_no
        //UPDATE実行
        $result = $this->Db->execute($query,$params);
        if($result === false){
            $errMsg = $this->Db->ErrorMsg();
            $this->errorLog($errMsg, __FILE__, __CLASS__, __LINE__);
            throw new AppException($errMsg);
        }
    }

    /**
     * 1つのアイテム内に登録されているすべてのサプリコンテンツの更新を行う
     *
     * 1.登録されているサプリコンテンツURLの情報取得
     * 2.登録するサプリコンテンツのYハンドル用URLを取得
     * 3.登録・更新・削除に必要な情報の取得
     * 4.登録されているYハンドル用URLと登録するYハンドル用URLを比較
     * 4-1.登録されているYハンドル用URLに登録するYハンドル用URLが無い場合は新規登録処理
     * 4-2.登録されているYハンドル用URLに登録するYハンドル用URLがある場合は更新処理
     * 4-2.登録するYハンドル用URL以外のURLが登録されているYハンドル用URLにあった場合は削除処理
     * 7.査読メールを送る
     * @param string $itemID アイテムID
     * @param string $itemNo アイテムNo
     * @param array $suppleContentsURL サプリコンテンツURL
     * @param array $errorInfoList 登録・更新・削除で発生したエラーリスト
     * @throws AppException
     */
    private function updateAllSuppleContentsOfOneItem($itemID, $itemNo, $arraySuppleContentsURL,&$errorInfoList)
    {
        // DBからサプリコンテンツURLを取得
        $arrayExitEntrySuppleContentsURL = array();
        $suppleInfoList = $this->suppleContentInfo($itemID);
        foreach ($suppleInfoList as $suppleInfo)
        {
            foreach ($suppleInfo as $supple)
            {
                array_push($arrayExitEntrySuppleContentsURL, $supple);
            }
        }
        // 登録するサプリコンテンツのYハンドルURLを取得
        $arrayYHandle = $this->yHandleUrl($arraySuppleContentsURL,$invalidUrl);
        // 登録しようとしているサプリコンテンツURLが無いかつ登録されているサプリコンテンツ情報が無い
        if(count($arrayYHandle) == 0 && count($suppleInfoList) == 0)
        {
            // YハンドルURLが1つもない場合はエラーとして投げる。yHandleUrlメソッドの引数$invalidUrlから不正なURLの情報が取得できるが、
            // 複数サプリコンテンツ登録の事を考えるとここで例外を投げるのは好ましくないため例外は投げない。
            $this->errorLog("Invalid all supple contents url", __FILE__, __CLASS__, __LINE__);
            throw new AppException("repository_entry_invalid_all_supple_contents_url");
        }

        // 必要情報を取得
        $item_type_result = $this->itemTypeInfo($itemID, $itemNo);
        $sendflag = $this->sendMailFlag($review_mail_flg,$review_flg_supple);

        // 新規登録
        $arrayEntrySuppleInfo = array();
        $failedEntryUrlInfo = array();
        $arrayEntrySupple = array_diff($arrayYHandle, $arrayExitEntrySuppleContentsURL);
        $entrySupple = array_unique($arrayEntrySupple);
        $this->entrySupples($entrySupple,$itemID, $itemNo,$item_type_result,$review_flg_supple,$arrayEntrySuppleInfo,$failedEntryUrlInfo);

        // 更新
        $arrayUpdateSuppleInfo = array();
        $failedUpdateUrlInfo = array();
        $arrayupdateSupple = array_intersect($arrayYHandle, $arrayExitEntrySuppleContentsURL);
        $this->updateSupples($arrayupdateSupple,$itemID,$itemNo,$item_type_result[0]['attribute_id'],$review_flg_supple,$arrayUpdateSuppleInfo,$failedUpdateUrlInfo);

        // 削除
        $faileddeleteUrlInfo = array();
        $arraydeleteSupple = array_diff($arrayExitEntrySuppleContentsURL, $arrayYHandle);
        $deleteSupple = array_unique($arraydeleteSupple);
        $this->deleteSupples($deleteSupple,$itemID,$itemNo,$faileddeleteUrlInfo);

        // 査読メールを送る
        $this->sendSuppleMailForSupples($sendflag,$item_type_result,$arrayEntrySuppleInfo,$arrayUpdateSuppleInfo);

        // 登録・更新・削除で発生したエラー情報を取得する
        $errorInfoList = $this->errorInfo($failedEntryUrlInfo,$failedUpdateUrlInfo,$faileddeleteUrlInfo,$invalidUrl);
    }

    /**
     * サプリWEKOのOpenSearchURLの取得
     * 1.サプリWEKOのURLを取得
     * 2.OpenSearchのURLの生成
     *
     * @param URLのパラメータの種類 $mode "weko_id" or "item_ids" or "prefix" string
     * @param URLのパラメータ値 $id string
     * @return OpenSearchURL string
     */
    private function openSearchUrlOfSuppleWeko($mode,$id)
    {
        // サプリWEKOのURLを取得
        $send_param = $this->suppleWEKOURL;

        // OpenSearchのURLの生成
        if($mode == "weko_id"){
            $send_param .= "/?action=repository_opensearch&weko_id=".$id."&format=rss";
        } else if($mode == "item_ids"){
            $send_param .= "/?action=repository_opensearch&item_ids=".$id."&format=rss";
        }else if ($mode == "prefix"){
            $send_param .= "/?action=repository_opensearch&prefix=true&format=rss";
        }

        return $send_param;
    }

    /**
     * Proxy情報の取得
     * 1.Proxy情報の取得
     * 2.データをまとめる
     *
     * @return Proxy情報 array
     */
    private function proxyInfo()
    {
        $proxy = array('proxy_mode'=>0, 'proxy_host'=>'', 'proxy_port'=>'', 'proxy_user'=>'', 'proxy_pass'=>'');
        $query = "SELECT conf_name, conf_value ".
                " FROM ".DATABASE_PREFIX."config ".
                " WHERE conf_name = ? ".
                " OR conf_name = ? ".
                " OR conf_name = ? ".
                " OR conf_name = ? ".
                " OR conf_name = ? ";
        $param = array();
        $param[] = 'proxy_mode';
        $param[] = 'proxy_host';
        $param[] = 'proxy_port';
        $param[] = 'proxy_user';
        $param[] = 'proxy_pass';

        // Proxy情報の取得
        $result = $this->Db->execute($query, $param);
        if($result === false)
        {
            $this->errorLog("MySQL ERROR : For get proxyInfo", __FILE__, __CLASS__, __LINE__);
            return $proxy;
        }
        // データをまとめる
        for($ii=0; $ii<count($result); $ii++)
        {
            if($result[$ii]['conf_name'] == 'proxy_mode')
            {
                    $proxy['proxy_mode'] = $result[$ii]['conf_value'];
            }
            else if($result[$ii]['conf_name'] == 'proxy_host')
                {
                $proxy['proxy_host'] = $result[$ii]['conf_value'];
            }
            else if($result[$ii]['conf_name'] == 'proxy_port')
            {
            $proxy['proxy_port'] = $result[$ii]['conf_value'];
            }
            else if($result[$ii]['conf_name'] == 'proxy_user')
                {
                $proxy['proxy_user'] = $result[$ii]['conf_value'];
            }
            else if($result[$ii]['conf_name'] == 'proxy_pass')
            {
                $proxy['proxy_pass'] = $result[$ii]['conf_value'];
            }
        }
        return $proxy;
    }

    /**
     * HTTPリクエスト(サプリコンテンツ情報取得)
     * 1.HTTPリクエストを投げる
     * 2.リクエストの返り値からレスポンステキストを受けとる
     *
     * @param URL値 $send_param string
     * @throws AppException
     * @return レスポンステキスト string
     */
    private function suppleContentsInfoHttpReqest($send_param)
    {
        $option = array(
                "timeout" => "10",
                "allowRedirects" => true,
                "maxRedirects" => 3,
        );
        $proxy = $this->proxyInfo();

        if($proxy['proxy_mode'] == 1)
        {
            $option = array(
                    "timeout" => "10",
                    "allowRedirects" => true,
                    "maxRedirects" => 3,
                    "proxy_host"=>$proxy['proxy_host'],
                    "proxy_port"=>$proxy['proxy_port'],
                    "proxy_user"=>$proxy['proxy_user'],
                    "proxy_pass"=>$proxy['proxy_pass']
            );
        }

        // HTTPリクエストを投げる
        $http = new HTTP_Request($send_param, $option);
        // setting HTTP header
        $http->addHeader("User-Agent", $_SERVER['HTTP_USER_AGENT']);

        // レスポンステキストを受けとる
        $response = $http->sendRequest();
        if (!PEAR::isError($response)) {
            $charge_code = $http->getResponseCode();// ResponseCode(200等)を取得
            $charge_header = $http->getResponseHeader();// ResponseHeader(レスポンスヘッダ)を取得
            $charge_body = $http->getResponseBody();// ResponseBody(レスポンステキスト)を取得
            $charge_Cookies = $http->getResponseCookies();// クッキーを取得
        }else{
            $this->errorLog("HTTPRequest ERROR : For get supple contents", __FILE__, __CLASS__, __LINE__);
            throw new AppException("repository_entry_failed_entry_supple_contents");
        }
        return $charge_body;
    }

    /**
     * サプリコンテンツのXMLを解析
     *
     * @param XML $response_xml string
     * @throws AppException
     * @return パースしたXMLの情報 array
     */
    private function parseXmlOfSuppleContent($response_xml)
    {
        try{
            $xml_parser = xml_parser_create();
            $rtn = xml_parse_into_struct( $xml_parser, $response_xml, $vals );
            if($rtn == 0){
                $this->session->setParameter("supple_error", 2);
                $this->errorLog("Failed to parse of xml", __FILE__, __CLASS__, __LINE__);
                throw new AppException("repository_entry_failed_entry_supple_contents");
            }
            xml_parser_free($xml_parser);

            return $vals;
        } catch(Exception $ex){
            $this->session->setParameter("supple_error", 2);
            $this->errorLog("Failed to parse of xml", __FILE__, __CLASS__, __LINE__);
            throw new AppException("repository_entry_failed_entry_supple_contents");
        }
    }

    /**
     * XMLデータの解析
     * 1.XMLのデータから値を取得
     *
     * @param XMLデータ $vals array
     * @return サプリコンテンツ情報 array
     */
    private function analysisXmlDataOfSuppleContentsInfo($vals)
    {
        $item_flag = false;
        $supple_data = array();
        foreach($vals as $val){
            if($val['tag'] == "ITEM"){
                    if($val['type'] == "open"){
                        $item_flag = true;
                    }
                    if($item_flag == true && $val['type'] == "close"){
                        $item_flag = false;
                        if($supple_data["supple_weko_item_id"] != "" && $supple_data["supple_weko_item_id"] != null){
                            break;
                        }
                    }
            }
            if($item_flag){
                switch($val['tag']){
                    case "TITLE":   // サプリアイテム:タイトル
                        $supple_data["supple_title"] = $val['value'];
                        break;
                    case "DCTERMS:ALTERNATIVE": // サプリアイテム:タイトル(英)
                        $supple_data["supple_title_en"] = $val['value'];
                        break;
                    case "LINK":    // サプリアイテム:詳細画面URL
                        $supple_data["uri"] = $val['value'];
                        break;
                    case "RDFS:SEEALSO":
                        if(preg_match("/itemId=([0-9]+)/",$val['attributes']['RDF:RESOURCE'], $matchItemId)){
                            $supple_data["supple_weko_item_id"] = $matchItemId[1];
                        }
                        break;
                    case "DC:IDENTIFIER":
                        if(preg_match("/item_id=([0-9]+)/",$val['value'], $matchItemId)){
                            $supple_data["supple_weko_item_id"] = $matchItemId[1];
                        }

                        if(preg_match("/file_id=([0-9]+)/",$val['value'], $matchFileId)){
                            $supple_data["file_id"] = $matchFileId[1];
                        }
                        else{
                            $supple_data["file_id"] = 0;
                        }
                        break;
                    case "DC:TYPE": // サプリアイテム:アイテムタイプ名
                        $supple_data["supple_item_type_name"] = $val['value'];
                        break;
                    case "DC:FORMAT":   // サプリアイテム:マイムタイプ
                        $supple_data["mime_type"] = $val['value'];
                        break;
                    default :
                        break;
                }
            }
        }

        return $supple_data;
    }

    /**
     * サプリコンテンツ情報取得
     * 1.OpenSearchのためのパラメータ取得
     * 2.サプリWEKOから対象アイテムの情報を取得
     *
     * @param サプリコンテンツURL $suppleContentsURL string
     * @return サプリコンテンツ情報 array
     */
    private function suppleInfoFromSuppleWEKO($suppleContentsURL)
    {
        $supple_data = array();

        $urlParamId = 0;
        $openSearchParam = $this->openSearchUrlParameter($suppleContentsURL,$urlParamId);

        // サプリコンテンツ情報の取得
        $supple_data =$this->suppleInfoByOpenSearch($openSearchParam, $urlParamId);

        // サプリコンテンツ情報の取得に失敗した場合
        if($supple_data === false)
        {
            $this->errorLog("Failed to get supple contentInfo", __FILE__, __CLASS__, __LINE__);
            throw new AppException("repository_entry_failed_entry_supple_contents");
        }

        if(count($supple_data) == 0)
        {
            $this->errorLog("There is no supple contentInfo", __FILE__, __CLASS__, __LINE__);
            throw new AppException("repository_entry_failed_entry_supple_contents");
        }

        return $supple_data;
    }

    /**
     * OpenSearchのパラメータを取得
     * 1.WEKOURLか確認
     * 2.Yハンドル用URLか確認
     *
     * @param サプリコンテンツURL $suppleContentsURL string
     * @throws AppException
     * @return パラメータ string
     */
    private function openSearchUrlParameter($suppleContentsURL,&$urlParamId)
    {
        $result = $this->isWekoUrl($suppleContentsURL);
        if($result === true){
            // WEKO URL からアイテムIDを取得
            $urlParamId = $this->wekoUrlItemId($suppleContentsURL);
            if($urlParamId == -1){
                $this->errorLog("Invalid supple contents url", __FILE__, __CLASS__, __LINE__);
                throw new AppException("repository_entry_invalid_supple_contents_url");
            }

            // Open
            return "item_ids";
        }

        $result = $this->isYHandleUrl($suppleContentsURL,$urlParamId);
        if($result === false){
            $this->errorLog("Invalid supple contents url", __FILE__, __CLASS__, __LINE__);
            throw new AppException("repository_entry_invalid_supple_contents_url");
        }
        else{
            return "weko_id";
        }
    }

    /**
     * サプリコンテンツURLからYハンドルURLの取得
     *
     * 1.サプリコンテンツURLがWEKOURLの場合、サプリWEKO側からYハンドルURLを取得する
     * 2.サプリコンテンツURLがYハンドルの場合は、サプリWEKO側からYハンドルURLを取得する必要なし。
     * @param string $suppleContentsURL サプリコンテンツURL
     * @param string $invalidUrl不正なURL
     * @return array Yハンドル用URL
     */
    private function yHandleUrl($suppleContentsURL,&$invalidUrl)
    {
        $arrayYHandleUrl = array();
        $invalidUrl = array();
        foreach ($suppleContentsURL as $url)
        {
            // WEKOURLの場合はサプリWEKO側からYハンドルURLを取得する
            $result = $this->isWekoUrl($url);
            if($result === true)
            {
                // WEKO URL からアイテムIDを取得
                $urlParamId = $this->wekoUrlItemId($url);

                // サプリコンテンツ情報の取得
                $supple_data =$this->suppleInfoByOpenSearch("item_ids", $urlParamId);

                if($supple_data === false || count($supple_data) == 0)
                {
                    $tmpError = array('url'=>$url,'errorMsg'=>"");

                    // サプリコンテンツ情報の取得に失敗した場合
                    array_push($invalidUrl, $tmpError);
                }
                else
                {
                    array_push($arrayYHandleUrl, $supple_data["uri"]);
                }

                continue;
            }

            $result = $this->isYHandleUrl($url,$urlParamId);
            if($result === false){
                $tmpError = array('url'=>$url,'errorMsg'=>"");
                array_push($invalidUrl, $tmpError);
            }
            else{
                array_push($arrayYHandleUrl, $url);
            }
        }

        return $arrayYHandleUrl;
    }

    /**
     * サプリコンテンツURLはWEKO URL
     * 1.WEKO URL形式のパターンを作成
     * 2.比較
     *
     * @param サプリコンテンツURL $suppleContentsURL string
     * @throws AppException
     * @return true:WEKO URL false:WEKO URLでない boolean
     */
    private function isWekoUrl($suppleContentsURL)
    {
        $url_pattern = $this->suppleWEKOURL;
        // サプリコンテンツURLの最後の文字列が"/"であるか確認。"/"の場合は最後の1文字"/"を削除
        $result = preg_match("/\/$/", $url_pattern, $resultStr);
        if($result === false){
            return false;
        }
        else if($result == 1){
            // サプリコンテンツURLの最後の文字列が"/"である場合最後の1文字"/"を消す
            substr($url_pattern, 0, -1);
        }

        // メタ文字の前にエスケープ文字を配置する
        $url_pattern = preg_replace("/\/|\?/", "\/", $url_pattern);
        if(is_null($url_pattern)){
            return false;
        }

        $url_pattern = "/".$url_pattern;
        $url_pattern .= "\/\?action=repository_uri&item_id=[0-9]+$/";
        $result = preg_match($url_pattern, $suppleContentsURL,$result);
        if($result === false){
            return false;
        }
        else if($result == 0){
            return false;
        }
        return true;
    }

    /**
     * サプリコンテンツURLはYハンドル用URL
     * 1.Yハンドル用のURL形式とサプリコンテンツURLを比較
     *
     * @param サプリコンテンツURL $suppleContentsURL string
     * @param サプリコンテンツ側のPrefixID $prefixId string
     * @return true:Yハンドル用URL false:Yハンドル用URLでない boolean
     */
    private function isYHandleUrl($suppleContentsURL,&$prefixId)
    {
        $id_server = RepositoryHandleManager::PREFIX_Y_HANDLE;

        // サプリWEKOのPrefixIDを取得
        $prefix_id = $this->prefixIDFromSuppleWeko();

        // メタ文字の前にエスケープ文字を配置する
        $suppleContentsURL = str_replace(" ", "", $suppleContentsURL);
        $tmpid_server = preg_replace("/\//", "\/", $id_server);
        if(is_null($tmpid_server)){
            return false;
        }

        if(preg_match("/".$tmpid_server.$prefix_id."/", $suppleContentsURL)){
            // 入力されたアドレスからsuffixを取得
            $suppleContentsURL = str_replace("Permalink:", "", $suppleContentsURL);
            $suppleContentsURL = str_replace($id_server.$prefix_id, "", $suppleContentsURL);
            $suppleContentsURL = str_replace("/", "", $suppleContentsURL);
        }
        else if(preg_match("/".$prefix_id."\/[0-9]{8}/", $suppleContentsURL)){
            // "Prefix/Suffix"形式で入力の場合
            $suppleContentsURL = str_replace($prefix_id, "", $suppleContentsURL);
            $suppleContentsURL = str_replace("/", "", $suppleContentsURL);
        }
        else {
            // その他: "Suffix"のみでの入力の場合
            $suppleContentsURL = str_replace("/", "", $suppleContentsURL);
        }

        // 取得したアイテム番号が8桁の数値か確認
        if(!preg_match("/^[0-9]+$/", $suppleContentsURL)){
            return false;
        }
        $suppleContentsURL = intval($suppleContentsURL);
        if($suppleContentsURL == "" || $suppleContentsURL == null){
            return false;
        }

        if($suppleContentsURL == 0){
            return false;
        }
        if(strlen($suppleContentsURL) <= 8){
            // フォーマットした文字列を取得
            $suppleContentsURL = sprintf("%08d", $suppleContentsURL);
            $prefixId = $suppleContentsURL;
        } else {
            return false;
        }
        return true;
    }

    /**
     * WEKO URLからアイテムIDを取得する
     *
     * @param WEKO URL $suppleContentsURL string
     * @return アイテムID 取得失敗の場合は-1 number
     */
    private function wekoUrlItemId($suppleContentsURL)
    {
        // サプリコンテンツURLからアイテムIDを抽出する
        $result = preg_match("/item_id=[0-9]+$/", $suppleContentsURL, $itemIds);
        if($result === false)
        {
            return -1;
        }

        $result = preg_match("/[0-9]+$/", $itemIds[0],$itemId);
        if($result === false)
        {
            return -1;
        } else {
            return $itemId[0];
        }
    }

    /**
     * サプリWEKOからPrefixIDを取得
     *
     * @return PrefixID string
     */
    private function prefixIDFromSuppleWeko()
    {
        $prefix_id = $this->supplePrefixIDByOpenSearch();
        if($prefix_id === false){
            $this->errorLog("Failed to get prefixID from suppleWEKO", __FILE__, __CLASS__, __LINE__);
            throw new AppException("repository_entry_no_prefix_id");
        }
        else if($prefix_id == "")
        {
            $this->errorLog("Not set prefixID of suppleWEKO", __FILE__, __CLASS__, __LINE__);
            throw new AppException("repository_entry_no_prefix_id");
        }

        return $prefix_id;
    }

    /**
     * 登録・更新・削除時に発生したエラーをまとめる
     * @param array $failedEntryUrlInfo 登録エラー情報
     * @param array $failedUpdateUrlInfo 更新エラー情報
     * @param array $faileddeleteUrlInfo 削除エラー情報
     * @return array エラー情報
     */
    private function errorInfo($failedEntryUrlInfo,$failedUpdateUrlInfo,$faileddeleteUrlInfo,$invalidUrl)
    {
        $errorList = array();

        // 登録時のエラーをまとめる
        foreach ($failedEntryUrlInfo as $errorInfo)
        {
            array_push($errorList, $errorInfo);
        }

        // 更新時のエラーをまとめる
        foreach ($failedUpdateUrlInfo as $errorInfo)
        {
            array_push($errorList, $errorInfo);
        }

        // 削除時のエラーをまとめる
        foreach ($faileddeleteUrlInfo as $errorInfo)
        {
            array_push($errorList, $errorInfo);
        }

        // サプリコンテンツURLの不正値情報をまとめる
        foreach ($invalidUrl as $errorInfo)
        {
            array_push($errorList, $errorInfo);
        }

        return $errorList;
    }

    /**
     * アイテムタイプ情報を取得
     *
     * @param アイテムID $itemID string
     * @param アイテムNo $itemNo string
     * @throws AppException
     * @return アイテムタイプ情報 array
     */
    private function itemTypeInfo($itemID, $itemNo)
    {
        $query = "SELECT attr_type.item_type_id, attr_type.attribute_id, item.uri, item.title, item.title_english ".
                "FROM ".DATABASE_PREFIX."repository_item_attr_type AS attr_type, ".
                DATABASE_PREFIX."repository_item AS item ".
                "WHERE item.item_id = ? ".
                "AND item.item_no = ? ".
                "AND item.item_type_id = attr_type.item_type_id ".
                "AND attr_type.input_type = 'supple' ".
                "AND item.is_delete = 0 ".
                "AND attr_type.is_delete = 0;";
        $params = array();
        $params[] = $itemID;	// item_id
        $params[] = $itemNo;	// item_no
        $item_type_result = $this->Db->execute($query, $params);
        if($item_type_result === false){
            $this->errorLog("MySQL ERROR : For get itemtype info", __FILE__, __CLASS__, __LINE__);
            throw new AppException("repository_entry_failed_entry_supple_contents");
        }

        return $item_type_result;
    }

    /**
     * 登録するサプリコンテンツのサプリNo取得
     *
     * @param アイテムID $itemID string
     * @param アイテムNo $itemNo string
     * @param アイテムタイプ情報 array
     * @return サプリNo string
     */
    private function entrySuppleNo($itemID, $itemNo,$item_type_result)
    {
        $supple_num = 0;
        $query = "SELECT MAX(supple_no) FROM ".DATABASE_PREFIX."repository_supple ".
                "WHERE item_id = ? ".
                "AND item_no = ? ".
                "AND attribute_id = ? ".
                "AND item_type_id = ?;";
        $params = array();
        $params[] = $itemID;	// item_id
        $params[] = $itemNo;	// item_no
        if(isset($item_type_result[0]['attribute_id'])){
            $params[] = $item_type_result[0]['attribute_id'];	// attribute_id
        }else{
            $params[] ="";
        }

        if(isset($item_type_result[0]['item_type_id'])){
            $params[] = $item_type_result[0]['item_type_id'];	// item_type_id
        }else{
            $params[] ="";
        }

        $result = $this->Db->execute($query, $params);
        if($result === false){
            $this->errorLog("MySQL ERROR : For get suppleNo", __FILE__, __CLASS__, __LINE__);
            throw new AppException("repository_entry_failed_entry_supple_contents");
        }

        if($result[0]['MAX(supple_no)'] == null){
            $supple_num = 1;
        } else {
            $supple_num = $result[0]['MAX(supple_no)'] + 1;
        }
        return $supple_num;
    }

    /**
     * メールを送るか否かのフラグを取得
     * 1.サプリコンテンツの査読通知メールを送信フラグを取得する
     * 2.サプリコンテンツの査読フラグを取得する
     * 3.メールを送るか否かの判断
     *
     * @param 査読メールフラグ $review_mail_flg string
     * @param サプリ査読フラグ $review_flg_supple string
     * @throws AppException
     * @return number 1:送る 0: 送らない number
     */
    private function sendMailFlag(&$review_mail_flg,&$review_flg_supple)
    {
        // サプリコンテンツの査読通知メールを送信フラグを取得する
        $query = "SELECT param_value ".
                "FROM ".DATABASE_PREFIX."repository_parameter ".
                "WHERE param_name = 'review_mail_flg';";
        $result = $this->Db->execute($query);
        if ($result === false) {
            $this->errorLog("MySQL ERROR : For get review mail flg", __FILE__, __CLASS__, __LINE__);
            throw new AppException("repository_entry_failed_entry_supple_contents");
        }
        $review_mail_flg = $result[0]['param_value'];

        // サプリコンテンツの査読フラグを取得する
        $query = "SELECT param_value ".
                "FROM ".DATABASE_PREFIX."repository_parameter ".
                "WHERE param_name = 'review_flg_supple';";
        $result = $this->Db->execute($query);
        if($result === false){
            $this->errorLog("MySQL ERROR : For get review flg supple", __FILE__, __CLASS__, __LINE__);
            throw new AppException("repository_entry_failed_entry_supple_contents");
        }
        $review_flg_supple = $result[0]['param_value'];

        if($review_flg_supple == 1 && $review_mail_flg == 1){
            return 1;
        }else{
            return 0;
        }
    }

    /**
     * 既に登録しようとしているサプリコンテンツが登録されているか
     *
     * @param アイテムID $itemID string
     * @param アイテムNo $itemNo string
     * @param サプリWEKOアイテムID $supple_weko_item_id string
     * @return 1:登録されていない true:登録されている number
     */
    private function existEntrySuppleContents($itemID,$itemNo,$supple_weko_item_id,&$result)
    {
        $query = "SELECT supple_no FROM ".DATABASE_PREFIX."repository_supple ".
                "WHERE item_id = ? " .
                "AND item_no = ? ".
                "AND is_delete = 0 ".
                "AND supple_weko_item_id = ? ;";
        $params = array();
        $params[] = $itemID;	// item_id
        $params[] = $itemNo;	// item_no
        $params[] = $supple_weko_item_id;
        $result = $this->Db->execute($query,$params);
        if($result === false){
            $this->errorLog("MySQL ERROR : For get suppleNo", __FILE__, __CLASS__, __LINE__);
            throw new AppException("repository_entry_failed_entry_supple_contents");
            return true;
        }
        if(count($result) == 0){
            return false;
        }
        else{
            return true;
        }
    }

    /**
     * アイテムIDをキーとしてサプリコンテンツ情報を取得する
     * @param string $itemID アイテムID
     * @throws AppException
     * @return boolean|mixed
     */
    private function suppleContentInfo($itemID)
    {
        $query = "SELECT uri FROM ".DATABASE_PREFIX."repository_supple ".
                "WHERE item_id = ? " .
                "AND is_delete = 0 ";
        $params = array();
        $params[] = $itemID;	// item_id
        $result = $this->Db->execute($query,$params);
        if($result === false){
            $this->errorLog("MySQL ERROR : For get supple contents info", __FILE__, __CLASS__, __LINE__);
            throw new AppException("repository_entry_failed_entry_supple_contents");
            return true;
        }

        return $result;
    }

    /**
     * サプリコンテンツの登録を行う
     *
     * @param string $itemID アイテムID
     * @param string $itemNo アイテムNo
     * @param number $supple_number サプリNo
     * @param array $item_type_result アイテムタイプ情報
     * @param array $supple_data サプリコンテンツ情報
     * @param string $review_flg_supple 査読フラグ
     * @throws AppException
     */
    private function entrySupple($itemID,$itemNo,$supple_num,$item_type_result,$supple_data,$review_flg_supple)
    {
        $user_id = $this->user_id;

        $query = "INSERT INTO ". DATABASE_PREFIX ."repository_supple ".
                "(item_id, item_no, attribute_id, supple_no, ".
                " item_type_id, supple_weko_item_id, supple_title, supple_title_en,".
                " uri, supple_item_type_name, mime_type, file_id, supple_review_status,".
                " supple_review_date, ins_user_id, mod_user_id, ins_date, mod_date, del_date, is_delete) ".
                "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
        $params = array();
        $params[] = $itemID;	// item_id
        $params[] = $itemNo;	// item_no
        $params[] = $item_type_result[0]['attribute_id'];	// attribute_id
        $params[] = $supple_num;		// supple_no
        $params[] = $item_type_result[0]['item_type_id'];	// item_type_id
        if(isset($supple_data["supple_weko_item_id"])){
            $params[] = $supple_data["supple_weko_item_id"];	// supple_weko_item_id
        }
        else{
            $params[] ="";
        }
        $params[] = $supple_data["supple_title"];	// supple_title
        $params[] = $supple_data["supple_title_en"];	// supple_title_en
        $params[] = $supple_data["uri"];	// uri
        $params[] = $supple_data["supple_item_type_name"];	// supple_item_type_name
        if(isset($supple_data["mime_type"])){
            $params[] = $supple_data["mime_type"];	// mime_type
        }
        else{
            $params[] ="";
        }

        if(isset($supple_data["file_id"])){
            $params[] = $supple_data["file_id"];	// file_id
        }
        else{
            $params[] = 0;
        }

        if($review_flg_supple == 1){
            // 査読を行う
            $params[] = 0;	// supple_review_status
            $params[] = "";	// supple_review_date
        } else {
            // 査読を行わない（自動的に承認する）
            $params[] = 1;	// supple_review_status
            $params[] = $this->accessDate;	// supple_review_date
        }
        $params[] = $user_id;	// ins_user_id
        $params[] = $user_id;	// mod_user_id
        $params[] = $this->accessDate;	// ins_date
        $params[] = $this->accessDate;	// mod_date
        $params[] = "";	// del_date
        $params[] = 0;	// is_delete
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $this->errorLog("MySQL ERROR : For entry supple contents", __FILE__, __CLASS__, __LINE__);
            throw new AppException("repository_entry_failed_entry_supple_contents");
        }
        return;
    }

    /**
     * 複数のサプリコンテンツを登録
     * @param array $arraySuppleURL サプリコンテンツURL
     * @param string $itemID アイテムID
     * @param string $itemNo アイテムNo
     * @param array $item_type_result アイテムタイプ情報
     * @param array $review_flg_supple 査読フラグ
     * @param array $failedSuppeUrl 登録に失敗したサプリコンテンツURL
     */
    private function entrySupples($arraySuppleURL,$itemID, $itemNo,$item_type_result,$review_flg_supple,&$arraySuppleInfo,&$failedSuppeUrl)
    {
        $arraySuppleInfo = array();
        $failedSuppeUrl = array();
        foreach ($arraySuppleURL as $url)
        {
            try {
                // サプリ情報を取得
                $supple_data = $this->suppleInfoFromSuppleWEKO($url);
                array_push($arraySuppleInfo, $supple_data);

                // サプリコンテンツのサプリNoを取得
                $supple_num = 0;
                $supple_num = $this->entrySuppleNo($itemID, $itemNo,$item_type_result);

                // サプリコンテンツの登録を行う
                $result = $this->entrySupple($itemID,$itemNo,$supple_num,$item_type_result,$supple_data,$review_flg_supple);
            }
            catch(AppException $e)
            {
                $tmpError = array('url'=>$url,'errorMsg'=>$e->getMessage());

                // どのサプリコンテンツURLが失敗したかの情報取得のため例外をcatchする
                array_push($failedSuppeUrl, $tmpError);
            }
        }
    }

    /**
     * サプリコンテンツの更新を行う
     *
     * @param アイテムID $item_id string
     * @param アイテムNo $item_no string
     * @param AttributeID $attribute_id string
     * @param サプリNo $supple_no string
     * @param サプリコンテンツ情報 $supple_data array
     * @param サプリ査読フラグ $review_flg_supple string
     * @param ワークフローアクティブタグ値 $supple_workflow_active_tab number
     * @throws AppException
     */
    private function updateSupple($item_id,$item_no,$attribute_id,$supple_no,$supple_data,$review_flg_supple)
    {
        // サプリアイテム更新
        $query = "UPDATE ". DATABASE_PREFIX ."repository_supple ".
                "SET supple_title = ?, ".
                "supple_title_en = ?, ".
                "uri = ?, ".
                "supple_item_type_name = ?, ".
                "mime_type = ?, ".
                "file_id = ?, ".
                "supple_review_status = ?, ".
                "supple_review_date = ?, ".
                "supple_reject_status = ?, ".
                "supple_reject_date = ?, ".
                "supple_reject_reason = ?, ".
                "mod_user_id = ?, ".
                "del_user_id = ?, ".
                "mod_date = ?, ".
                "del_date = ?, ".
                "is_delete = ? ".
                "WHERE item_id = ? ".
                "AND item_no = ? ".
                "AND attribute_id = ? ".
                "AND supple_no = ? ;";
        $params = array();
        $params[] = $supple_data["supple_title"];			// supple_title
        $params[] = $supple_data["supple_title_en"];		// supple_title_en
        $params[] = $supple_data["uri"];					// uri
        $params[] = $supple_data["supple_item_type_name"];	// supple_item_type_name
        if(isset($supple_data["mime_type"])){
            $params[] = $supple_data["mime_type"];				// mime_type
        }
        else{
            $params[] ="";
        }
        $params[] = $supple_data["file_id"];				// file_id
        if($review_flg_supple == 1){
            // 査読を行う
            $params[] = 0;									// supple_review_status
            $params[] = "";									// supple_review_date
        } else {
            // 査読を行わない（自動的に承認する）
            $params[] = 1;									// supple_review_status
            $params[] = $this->accessDate;				// supple_review_date
        }
        $params[] = 0;										// supple_reject_status
        $params[] = "";										// supple_reject_date
        $params[] = "";										// supple_reject_reason
        $params[] = $this->user_id;								// mod_user_id
        $params[] = 0;									// del_user_id
        $params[] = $this->accessDate;					// mod_date
        $params[] = "";									// del_date
        $params[] = 0;										// is_delete
        $params[] = $item_id;							// item_id
        $params[] = $item_no;							// item_no
        $params[] = $attribute_id;					// attribute_id
        $params[] = $supple_no;						// supple_no
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $this->errorLog("MySQL ERROR : For update supple contents", __FILE__, __CLASS__, __LINE__);
            throw new AppException("repository_update_failed_update_supple_contents");
        }
    }

    /**
     * 複数のサプリコンテンツを更新する
     * @param array $arraySuppleURL 更新するサプリコンテンツURL
     * @param string $item_id アイテムID
     * @param string $item_no アイテムNo
     * @param string $attribute_id 属性ID
     * @param string $review_flg_supple 査読フラグ
     * @param array $failedUpdateUrl 更新に失敗したサプリコンテンツURL
     */
    private function updateSupples($arraySuppleURL,$itemID,$itemNo,$attribute_id,$review_flg_supple,&$arraySuppleInfo,&$failedUpdateUrl)
    {
        $arraySuppleInfo = array();
        $failedUpdateUrl = array();
        foreach ($arraySuppleURL as $url)
        {
            try {
                // isYHandleUrlでPrefixIdが取得できるため、isYHandleUrlを使用してPrefixIDを取得
                $this->isYHandleUrl($url, $prefixId);

                // サプリコンテンツの情報取得
                $supple_data = array();
                $supple_data = $this->suppleInfoByOpenSearch("weko_id", $prefixId);
                $supple_weko_item_id = $supple_data["supple_weko_item_id"];
                array_push($arraySuppleInfo, $supple_data);

                // サプリNoの取得
                $exitSuppleResult = $this->existEntrySuppleContents($itemID,$itemNo,$supple_weko_item_id,$exitSuppleInfo);
                if($exitSuppleResult === false)
                {
                    $tmpError = array('url'=>$url,'errorMsg'=>"");
                    array_push($failedUpdateUrl, $url);
                }
                $suppleNo = $exitSuppleInfo[0]['supple_no'];

                // サプリコンテンツの更新
                $this->updateSupple($itemID,$itemNo,$attribute_id,$suppleNo,$supple_data,$review_flg_supple);
            }
            catch(AppException $e)
            {
                $tmpError = array('url'=>$url,'errorMsg'=>$e->getMessage());

                // どのサプリコンテンツURLが失敗したかの情報取得のため例外をcatchする
                array_push($failedUpdateUrl, $tmpError);
            }
        }
    }

    /**
     * 複数のサプリコンテンツを削除する
     * @param array $arrayDeleteSupple サプリコンテンツURL
     * @param string $itemID アイテムID
     * @param string $itemNo アイテムNo
     * @param array $failedUpdateUrl 削除に失敗したサプリコンテンツURL
     */
    private function deleteSupples($arrayDeleteSupple,$itemID,$itemNo,&$failedDeleteUrl)
    {
        $failedDeleteUrl = array();
        foreach ($arrayDeleteSupple as $url)
        {
            try {
                // isYHandleUrlでPrefixIdが取得できるため、isYHandleUrlを使用してPrefixIDを取得
                $this->isYHandleUrl($url, $prefixId);

                // サプリコンテンツの情報取得
                $supple_data = array();
                $supple_data = $this->suppleInfoByOpenSearch("weko_id", $prefixId);
                $supple_weko_item_id = $supple_data['supple_weko_item_id'];

                // サプリNoの取得
                $exitSuppleResult = $this->existEntrySuppleContents($itemID,$itemNo,$supple_weko_item_id,$exitSuppleInfo);
                if($exitSuppleResult === false)
                {
                    // 存在しなければ削除は行わない
                    $tmpError = array('url'=>$url,'errorMsg'=>"");
                    array_push($failedDeleteUrl, $url);
                }
                $suppleNo = $exitSuppleInfo[0]['supple_no'];

                $this->deleteSuppleContents($itemID,$itemNo,$suppleNo);
            }
            catch(AppException $e)
            {
                $tmpError = array('url'=>$url,'errorMsg'=>$e->getMessage());

                // どのサプリコンテンツURLが失敗したかの情報取得のため例外をcatchする
                array_push($failedDeleteUrl, $tmpError);
            }
        }
    }

    /**
     * サプリWEKOURLの取得
     *
     * @throws AppException
     */
    private function suppleWEKOURL()
    {
        $query = "SELECT * ".
                "FROM ". DATABASE_PREFIX ."repository_parameter ".
                "WHERE param_name = 'supple_weko_url' ".
                "AND is_delete = 0; ";

        // execute SQL
        $result = $this->Db->execute($query);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->errorLog($error_msg, __FILE__, __CLASS__, __LINE__);
            $this->sessionn->setParameter("error_cord",-1);
            throw new AppException("repository_entry_no_supple_weko_url");
        }
        if(count($result)!= 1)
        {
            $error_msg = "The parameter is illegal. there is a lot of data gotten.";
            $this->errorLog($error_msg, __FILE__, __CLASS__, __LINE__);
            $this->sessionn->setParameter("error_cord",-1);
            throw new AppException("repository_entry_no_supple_weko_url");
        }

        return $result[0]['param_value'];
    }

    /**
     * 複数のサプリコンテンツ登録・更新の際のメール送信を行う
     * @param string $sendflag メール送信フラグ
     * @param array $item_type_result アイテムタイプ情報
     * @param array $arrayEntrySuppleInfo 登録時のサプリコンテンツ情報
     * @param array $arrayUpdateSuppleInfo 更新時のサプリコンテンツ情報
     */
    private function sendSuppleMailForSupples($sendflag,$item_type_result,$arrayEntrySuppleInfo,$arrayUpdateSuppleInfo)
    {
        $suppleInfoForSendMail = array();
        // 登録したサプリコンテンツの情報をメール送信用の配列に詰めなおす
        foreach ($arrayEntrySuppleInfo as $entrySuppleInfo)
        {
            array_push($suppleInfoForSendMail, $entrySuppleInfo);
        }

        // 更新したサプリコンテンツの情報をメール送信用の配列に詰めなおす
        foreach ($arrayUpdateSuppleInfo as $updateSuppleInfo)
        {
            array_push($suppleInfoForSendMail, $updateSuppleInfo);
        }

        // 査読メールの送信
        $this->sendSuppleMail($sendflag,$suppleInfoForSendMail,$item_type_result);
    }

    /**
     * 査読通知メールを送信する
     * 1.本文の作成
     * 2.送信
     *
     * @param 送信するか否かのフラグ値 $flag number
     * @param サプリコンテンツ情報 $supple_data array
     * @param アイテムタイプ情報 $item_type_result array
     */
    private function sendSuppleMail($flag,$arraySupple_data,$item_type_result)
    {
        // 新規査読サプリコンテンツ登録メール送信処理
        if($flag == 1){
            $container = & DIContainerFactory::getContainer();
            $mailMain = $container->getComponent("mailMain");

            // 言語リソース取得
            $smartyAssign = $this->session->getParameter("smartyAssign");

            // set subject
            $subj = $smartyAssign->getLang("repository_mail_review_subject");
            $mailMain->setSubject($subj);

            // page_idおよびblock_idを取得
            $block_info = $this->blockPageId();
            // メール本文をリソースから読み込む
            // set Mail body
            $body = '';
            $body .= $smartyAssign->getLang("repository_mail_review_body")."\n\n";
            $body .= $smartyAssign->getLang("repository_mail_review_suppple_contents")."\n";

            $dispCont = 0;
            foreach ($arraySupple_data as $supple_data)
            {
                if(self::SEND_MAIL_DISP_SUPPLE_ITEM_MAX_NUM  == $dispCont)
                {
                    break;
                }

                $body .= $smartyAssign->getLang("repository_mail_review_supple_title");
                if($this->session->getParameter("_lang") == "japanese"){
                    if($supple_data["supple_title"] != ""){
                        $body .= $supple_data["supple_title"];
                    } else if($supple_data["supple_title_en"] != ""){
                        $body .= $supple_data["supple_title_en"];
                    } else {
                        $body .= "no title";
                    }
                } else {
                    if($supple_data["supple_title_en"] != ""){
                        $body .= $supple_data["supple_title_en"];
                    } else if($supple_data["supple_title"] != ""){
                        $body .= $supple_data["supple_title"];
                    } else {
                        $body .= "no title";
                    }
                }

                $body .= "\n";
                $body .= $smartyAssign->getLang("repository_mail_review_supple_detailurl").$supple_data["uri"]."\n"."\n";

                $dispCont++;
            }

            $body .= $smartyAssign->getLang("repository_mail_review_supple_title_is_registed");
            if($this->session->getParameter("_lang") == "japanese"){
                if($item_type_result[0]['title'] != ""){
                    $body .= $item_type_result[0]['title'];
                } else if($item_type_result[0]['title_english'] != ""){
                    $body .= $item_type_result[0]['title_english'];
                } else {
                    $body .= "no title";
                }
            } else {
                if($item_type_result[0]['title_english'] != ""){
                    $body .= $item_type_result[0]['title_english'];
                } else if($item_type_result[0]['title'] != ""){
                    $body .= $item_type_result[0]['title'];
                } else {
                    $body .= "no title";
                }
            }
            $body .= "\n";
            $body .= $smartyAssign->getLang("repository_mail_review_supple_detailurl_is_registed").$item_type_result[0]['uri']."\n";
            $body .= "\n";
            $body .= $smartyAssign->getLang("repository_mail_review_reviewurl")."\n";
            $body .= BASE_URL;
            if(substr(BASE_URL,-1,1) != "/"){
                $body .= "/";
            }
            $body .= "?active_action=repository_view_edit_review&review_active_tab=1&page_id=".$block_info["page_id"]."&block_id=".$block_info["block_id"];
            $body .= "\n\n".$smartyAssign->getLang("repository_mail_review_close");
            $mailMain->setBody($body);

            // 送信メール情報取得
            $users = array();
            $this->reviewMailInfo($users);

            // 送信先を設定
            $mailMain->setToUsers($users);

            // メール送信
            if(count($users) > 0){
                // 送信者がいる場合は送信
                $return = $mailMain->send();
            }
        }
    }

    /**
     * 査読通知メール送信に必要なメール情報を取得
     *
     * @param ユーザー情報 $users string
     * @return 登録結果 boolean
     */
    private function reviewMailInfo(&$users){
        $users = array();       // メール送信先
        $query = "SELECT param_name, param_value ".
                " FROM ". DATABASE_PREFIX ."repository_parameter ".     // パラメタテーブル
                " WHERE is_delete = ? ".
                " AND param_name = ? ";
        $params = array();
        $params[] = 0;
        $params[] = 'review_mail';
        // SELECT実行
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $this->errorLog("MySQL ERROR : For get review mail info", __FILE__, __CLASS__, __LINE__);
            return false;
        }
        if(count($result) == 1){
            if($result[0]['param_name'] == 'review_mail'){
                $result[0]['param_value'] = str_replace("\r\n", "\n", $result[0]['param_value']);
                $email = explode("\n", $result[0]['param_value']);
                for($jj=0; $jj<count($email); $jj++){
                    if(strlen($email[$jj]) > 0){
                        array_push($users, array("email" => $email[$jj]));
                    }
                }
            }
        }

        return true;
    }

    /**
     * ブロックページIDの取得
     *
     * @throws AppException
     * @return ブロックページID string
     */
    private function blockPageId(){
        // get NC version
        $version = $this->ncVersion();
        $return_array = null;

        if(str_replace(".", "", $version) < 2300){

            $return_array = $this->blockIdAndPageIdBeforeVersionNetCommons23();

        } else {
            $return_array = $this->blockIdAndPageIdAfterVersionNetCommons23();
        }
        return $return_array;
    }

    /**
     * BlockIdとPageIdを取得（NetCommons2.3以前）
     *
     * @throws AppException
     * @return BlockIdとPageId array
     */
    private function blockIdAndPageIdBeforeVersionNetCommons23()
    {
        $return_array = null;
        $query = "SELECT blocks.block_id, blocks.page_id, pages.room_id, pages.private_flag, pages.space_type ".
                "FROM ". DATABASE_PREFIX ."blocks AS blocks, ".
                DATABASE_PREFIX ."pages AS pages ".
                "WHERE blocks.action_name = ? ".
                "AND blocks.page_id = pages.page_id ".
                "ORDER BY blocks.insert_time ASC; ";
        $params = array();
        $params[] = "repository_view_main_item_snippet";
        $container =& DIContainerFactory::getContainer();
        $filterChain =& $container->getComponent("FilterChain");
        $smartyAssign =& $filterChain->getFilterByName("SmartyAssign");
        $result = $this->Db->execute($query,$params);
        if($result === false){
            $this->errorLog("MySQL ERROR : For get id", __FILE__, __CLASS__, __LINE__);
            throw new AppException("repository_mail_failed_send_mail");
        }
        if(count($result)==1){
            // WEKOがNC上に一つしかない
            $return_array = array('block_id'=>$result[0]['block_id'],'page_id'=>$result[0]['page_id'],'room_id'=>$result[0]['room_id']);
        }else{
            // WEKOがNC上に複数ある場合はパブリックに配置されているもののみ有効
            for($ii=0; $ii<count($result); $ii++){
                if($result[$ii]['private_flag']==0 && $result[$ii]['space_type']==_SPACE_TYPE_PUBLIC){
                    $return_array = array('block_id'=>$result[$ii]['block_id'],'page_id'=>$result[$ii]['page_id'],'room_id'=>$result[$ii]['room_id'],'space_type'=>$result[$ii]['space_type']);
                    break;
                }
            }
        }

        return $return_array;
    }

    /**
     * BlockIdとPageIdを取得（NetCommons2.3以降）
     *
     * @throws AppException
     * @return BlockIdとPageId array
     */
    private function blockIdAndPageIdAfterVersionNetCommons23()
    {
        $return_array = null;

        $query = "SELECT blocks.block_id, blocks.page_id, pages.room_id, pages.private_flag, pages.space_type, pages.lang_dirname ".
                "FROM ". DATABASE_PREFIX ."blocks AS blocks, ".
                DATABASE_PREFIX ."pages AS pages ".
                "WHERE blocks.action_name = ? ".
                "AND blocks.page_id = pages.page_id ".
                "ORDER BY blocks.insert_time ASC; ";
        $params = array();
        $params[] = "repository_view_main_item_snippet";
        $result = $this->Db->execute($query,$params);
        if($result === false){
            $this->errorLog("MySQL ERROR : For get id", __FILE__, __CLASS__, __LINE__);
            throw new AppException("repository_mail_failed_send_mail");
        }
        $lang = $this->session->getParameter("_lang");
        if(count($result)==1){
            // WEKOがNC上に一つしかない
            $return_array = array('block_id'=>$result[0]['block_id'],'page_id'=>$result[0]['page_id'],'room_id'=>$result[0]['room_id'],'space_type'=>$result[0]['space_type']);
        }else{
            // WEKOがNC上に複数ある場合はパブリックに配置されているもののみ有効
            for($ii=0; $ii<count($result); $ii++){
                if($result[$ii]['private_flag']==0 && $result[$ii]['space_type']==_SPACE_TYPE_PUBLIC && $result[$ii]['lang_dirname']==$lang){
                    $return_array = array('block_id'=>$result[$ii]['block_id'],'page_id'=>$result[$ii]['page_id'],'room_id'=>$result[$ii]['room_id'],'space_type'=>$result[$ii]['space_type']);
                } else if($result[$ii]['private_flag']==0 && $result[$ii]['space_type']==_SPACE_TYPE_PUBLIC && $result[$ii]['lang_dirname']==""){
                    $tmp_array = array('block_id'=>$result[$ii]['block_id'],'page_id'=>$result[$ii]['page_id'],'room_id'=>$result[$ii]['room_id'],'space_type'=>$result[$ii]['space_type']);
                }
            }
            if(empty($return_array) && isset($tmp_array)){
                $return_array = $tmp_array;
            }
        }
        return $return_array;
    }

    /**
     * 現在稼働しているNetCommonsのバージョンを取得する
     *
     * @return バージョン値 $version
     */
    private function ncVersion(){
        $container =& DIContainerFactory::getContainer();
        $configView =& $container->getComponent("configView");
        $config_version = $configView->getConfigByConfname(_SYS_CONF_MODID, "version");
        if(isset($config_version) && isset($config_version['conf_value'])) {
            $version = $config_version['conf_value'];
        } else {
            $version = _NC_VERSION;
        }
        return $version;
    }
}
?>