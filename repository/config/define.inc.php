<?php
// --------------------------------------------------------------------
//
// $Id: define.inc.php 57337 2015-08-29 06:00:09Z yuko_nakao $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics,
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

// 管理者権限以上(管理者非表示用)
define("REPOSITORY_ADMIN_MORE",6);

//--------------アイテム登録可能権限---------------------
// REPOSITORY_ADMIN_MORE : 管理者より上
// _AUTH_ADMIN    : 管理者
// _AUTH_CHIEF    : 主担
// _AUTH_MODERATE : モデレータ
// _AUTH_GENERAL  : 一般
// _AUTH_GUEST    : ゲスト（未ログイン含）
define("REPOSITORY_ITEM_REGIST_AUTH",_AUTH_GENERAL);

//--------------利用明細表示可能権限---------------------
define("REPOSITORY_CHARGE_AUTH", REPOSITORY_ADMIN_MORE);

//--------------Shibboleth定数未設定時の初期化---------------------
if(!defined('SHIB_ENABLED'))
{
	 define('SHIB_ENABLED', '0');
}

//--------------プライベートツリー作成権限---------------------
// プライベートツリーが作成しない設定の時は、REPOSITORY_ADMIN_MOREの管理者権限以上(作成できない)
// 作成する設定の時は、REPOSITORY_ITEM_REGIST_AUTHのアイテム登録権限
// 管理画面で、プライベートツリー作成の設定を切り替えたとき下記権限を自動で書き換える
define("_REPOSITORY_PRIVATETREE_AUTHORITY", REPOSITORY_ADMIN_MORE);		// CHANGE_TEXT_PRIVATETREE

//--------------プライベートツリー公開---------------------
// 通常プライベートツリーは非公開(ユーザーおよび管理者のみ投稿・閲覧可)であるが
// これを意図的に「公開」扱いとするためのフラグ
// 主にOpenDepo用の特化対応
// true：公開、false：非公開(通常はこちら)
define("_REPOSITORY_PRIVATETREE_PUBLIC",false);

//--------------プライベートツリー自動所属---------------------
// アイテム登録時にプライベートツリーへの自動所属を行うかどうか
// true：実施する、false：実施しない(通常はこちら)
define("_REPOSITORY_PRIVATETREE_AUTO_AFFILIATION", false);

//---------------スマートフォンフラグ---------------------
// スマートフォン対応表示を行うかどうか判別するフラグ
// true：実施する、false：実施しない(通常はこちら)
define("_REPOSITORY_SMART_PHONE_DISPLAY", false);

//---------------デバッグフラグ---------------------
// true：実施する、false：実施しない(通常はこちら)
define("_DEBUG_FLG", false);

//---------------画像スライド再生スピード設定---------------------
// 画像をflash表示に指定した時に表示される画像スライドの再生スピード
// 次の画像に変わり始めてから、次の画像が完全に表示されるまでの時間(ミリ秒単位)
// default value: 1000
define("_REPOSITORY_THUMBNAIL_TRANSITION_SPEED", 1000);

//---------------画像スライド再生インターバル設定---------------------
// 画像をflash表示に指定した時に表示される画像スライドの再生インターバル
// 画像が表示されてから、次の画像に変わり始めるまでの時間(ミリ秒単位)
// default value: 4000
define("_REPOSITORY_THUMBNAIL_TRANSITION_INTERVAL", 4000);

//---------------WEKO高速化対応(大量のアイテム登録時のボトルネック対応)---------------------
// 全てのインデックスは公開インデックスであると判定される
// 検索テーブルがひとつになる
// 詳細検索の検索項目が制限される
// falseからtrueにしたときは、検索テーブルの再構成を行うこと
// MySQLv4.1.1以上(MATCH AGAINSTが使用できる環境)でないと動作しない
// default value: false
define("_REPOSITORY_HIGH_SPEED", false);

//---------------TOP画面非表示---------------------
// TOP画面が表示されなくなる
// default value: false
define("_REPOSITORY_NOT_SHOW_TOP_PAGE", false);

//---------------DOI表示設定---------------------
// 使用するDOIの設定
// default value: false
define("_REPOSITORY_JALC_DOI", false);
define("_REPOSITORY_JALC_CROSSREF_DOI", false);
define("_REPOSITORY_JALC_DATACITE_DOI", false);

?>
