<?php
// --------------------------------------------------------------------
//
// $Id: RepositoryConst.class.php 56711 2015-08-19 13:21:44Z tomohiro_ichikawa $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics,
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/**
 * Repository module constant class
 *
 * @package repository
 * @access  public
 */
class RepositoryConst
{
    // -------------------------------------------
    // DB table name
    // -------------------------------------------
    const DBTABLE_REPOSITORY_ATTACHED_FILE = "repository_attached_file";
    const DBTABLE_REPOSITORY_BIBLIO_INFO = "repository_biblio_info";
    const DBTABLE_REPOSITORY_ELEMENT_CD = "repository_element_cd";
    const DBTABLE_REPOSITORY_EXTERNAL_AUTHOR_ID_PREFIX = "repository_external_author_id_prefix";
    const DBTABLE_REPOSITORY_EXTERNAL_AUTHOR_ID_SUFFIX = "repository_external_author_id_suffix";
    const DBTABLE_REPOSITORY_FILE = "repository_file";
    const DBTABLE_REPOSITORY_FILE_PRICE = "repository_file_price";
    const DBTABLE_REPOSITORY_FULLTEXT_DATA = "repository_fulltext_data";
    const DBTABLE_REPOSITORY_INDEX = "repository_index";
    const DBTABLE_REPOSITORY_ITEM = "repository_item";
    const DBTABLE_REPOSITORY_ITEM_ATTR = "repository_item_attr";
    const DBTABLE_REPOSITORY_ITEM_ATTR_CANDIDATE = "repository_item_attr_candidate";
    const DBTABLE_REPOSITORY_ITEM_ATTR_TYPE = "repository_item_attr_type";
    const DBTABLE_REPOSITORY_ITEM_TYPE = "repository_item_type";
    const DBTABLE_REPOSITORY_LICENSE_MASTER = "repository_license_master";
    const DBTABLE_REPOSITORY_LOG = "repository_log";
    const DBTABLE_REPOSITORY_NAME_AUTHORITY = "repository_name_authority";
    const DBTABLE_REPOSITORY_PARAMETER = "repository_parameter";
    const DBTABLE_REPOSITORY_PERSONAL_NAME = "repository_personal_name";
    const DBTABLE_REPOSITORY_POSITION_INDEX = "repository_position_index";
    const DBTABLE_REPOSITORY_RANKING = "repository_ranking";
    const DBTABLE_REPOSITORY_REFERENCE = "repository_reference";
    const DBTABLE_REPOSITORY_SUPPLE = "repository_supple";
    const DBTABLE_REPOSITORY_THUMBNAIL = "repository_thumbnail";
    const DBTABLE_REPOSITORY_USERS = "repository_users";
    const DBTABLE_REPOSITORY_HARVESTING_LOG = 'repository_harvesting_log';
    const DBTABLE_REPOSITORY_PDF_COVER_PARAMETER = 'repository_pdf_cover_parameter';
    const DBTABLE_REPOSITORY_USAGESTATISTICS = 'repository_usagestatistics';
    const DBTABLE_REPOSITORY_SEND_FEEDBACKMAIL_AUTHOR_ID = 'repository_send_feedbackmail_author_id';
    const DBTABLE_AUTHORITIES = "authorities";

    // -------------------------------------------
    // DB table column
    // -------------------------------------------
    // Common column name
    const DBCOL_COMMON_INS_USER_ID = "ins_user_id";
    const DBCOL_COMMON_MOD_USER_ID = "mod_user_id";
    const DBCOL_COMMON_DEL_USER_ID = "del_user_id";
    const DBCOL_COMMON_INS_DATE = "ins_date";
    const DBCOL_COMMON_MOD_DATE = "mod_date";
    const DBCOL_COMMON_DEL_DATE = "del_date";
    const DBCOL_COMMON_IS_DELETE = "is_delete";

    // repository_item
    const DBCOL_REPOSITORY_ITEM_ITEM_ID = "item_id";
    const DBCOL_REPOSITORY_ITEM_ITEM_NO = "item_no";
    const DBCOL_REPOSITORY_ITEM_REVISION_NO = "revision_no";
    const DBCOL_REPOSITORY_ITEM_ITEM_TYPE_ID = "item_type_id";
    const DBCOL_REPOSITORY_ITEM_PREV_REVISION_NO = "prev_revision_no";
    const DBCOL_REPOSITORY_ITEM_TITLE = "title";
    const DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH = "title_english";
    const DBCOL_REPOSITORY_ITEM_LANGUAGE = "language";
    const DBCOL_REPOSITORY_ITEM_REVIEW_STATUS = "review_status";
    const DBCOL_REPOSITORY_ITEM_REVIEW_DATE = "review_date";
    const DBCOL_REPOSITORY_ITEM_SHOWN_STATUS = "shown_status";
    const DBCOL_REPOSITORY_ITEM_SHOWN_DATE = "shown_date";
    const DBCOL_REPOSITORY_ITEM_REJECT_STATUS = "reject_status";
    const DBCOL_REPOSITORY_ITEM_REJECT_DATE = "reject_date";
    const DBCOL_REPOSITORY_ITEM_REJECT_REASON = "reject_reason";
    const DBCOL_REPOSITORY_ITEM_SEARCH_KEY = "serch_key";
    const DBCOL_REPOSITORY_ITEM_SEARCH_KEY_ENGLISH = "serch_key_english";
    const DBCOL_REPOSITORY_ITEM_REMARK = "remark";
    const DBCOL_REPOSITORY_ITEM_URI = "uri";

    // repository_item_type
    const DBCOL_REPOSITORY_ITEM_TYPE_ID = "item_type_id";
    const DBCOL_REPOSITORY_ITEM_TYPE_NAME = "item_type_name";
    const DBCOL_REPOSITORY_ITEM_TYPE_SHORT_NAME = "item_type_short_name";
    const DBCOL_REPOSITORY_ITEM_TYPE_EXPLANATION = "explanation";
    const DBCOL_REPOSITORY_ITEM_TYPE_MAPPING_INFO = "mapping_info";
    const DBCOL_REPOSITORY_ITEM_TYPE_ICON_NAME = "icon_name";
    const DBCOL_REPOSITORY_ITEM_TYPE_ICON_MIME_TYPE = "icon_mime_type";
    const DBCOL_REPOSITORY_ITEM_TYPE_ICON_EXTENSION = "icon_extension";
    const DBCOL_REPOSITORY_ITEM_TYPE_ICON = "icon";

    // repository_item_attr_type
    const DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ITEM_TYPE_ID = "item_type_id";
    const DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_ID = "attribute_id";
    const DBCOL_REPOSITORY_ITEM_ATTR_TYPE_SHOW_ORDER = "show_order";
    const DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_NAME = "attribute_name";
    const DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_SHORT_NAME = "attribute_short_name";
    const DBCOL_REPOSITORY_ITEM_ATTR_TYPE_IMPUT_TYPE = "input_type";
    const DBCOL_REPOSITORY_ITEM_ATTR_TYPE_IS_REQUIRED = "is_required";
    const DBCOL_REPOSITORY_ITEM_ATTR_TYPE_PLURAL_ENABLE = "plural_enable";
    const DBCOL_REPOSITORY_ITEM_ATTR_TYPE_LINE_FEED_ENABLE = "line_feed_enable";
    const DBCOL_REPOSITORY_ITEM_ATTR_TYPE_LIST_VIEW_ENABLE = "list_view_enable";
    const DBCOL_REPOSITORY_ITEM_ATTR_TYPE_HIDDEN = "hidden";
    const DBCOL_REPOSITORY_ITEM_ATTR_TYPE_JUNII2_MAPPING = "junii2_mapping";
    const DBCOL_REPOSITORY_ITEM_ATTR_TYPE_LOM_MAPPING = "lom_mapping";
    const DBCOL_REPOSITORY_ITEM_ATTR_TYPE_SPASE_MAPPING = "spase_mapping";
    const DBCOL_REPOSITORY_ITEM_ATTR_TYPE_DOBLIN_CORE_MAPPING = "dublin_core_mapping";
    const DBCOL_REPOSITORY_ITEM_ATTR_TYPE_DISPLAY_LANG_TYPE = "display_lang_type";

    // repository_item_attr
    const DBCOL_REPOSITORY_ITEM_ATTR_ITEM_ID = "item_id";
    const DBCOL_REPOSITORY_ITEM_ATTR_ITEM_NO = "item_no";
    const DBCOL_REPOSITORY_ITEM_ATTR_ATTRIBUTE_ID = "attribute_id";
    const DBCOL_REPOSITORY_ITEM_ATTR_ATTRIBUTE_NO = "attribute_no";
    const DBCOL_REPOSITORY_ITEM_ATTR_ATTRIBUTE_VALUE = "attribute_value";
    const DBCOL_REPOSITORY_ITEM_ATTR_ITEM_TYPE_ID = "item_type_id";

    // repository_personal_name
    const DBCOL_REPOSITORY_PERSONAL_NAME_ITEM_ID = "item_id";
    const DBCOL_REPOSITORY_PERSONAL_NAME_ITEM_NO = "item_no";
    const DBCOL_REPOSITORY_PERSONAL_NAME_ATTRIBUTE_ID = "attribute_id";
    const DBCOL_REPOSITORY_PERSONAL_NAME_PERSONAL_NAME_NO = "personal_name_no";
    const DBCOL_REPOSITORY_PERSONAL_NAME_FAMILY = "family";
    const DBCOL_REPOSITORY_PERSONAL_NAME_NAME = "name";
    const DBCOL_REPOSITORY_PERSONAL_NAME_FAMILY_RUBY = "family_ruby";
    const DBCOL_REPOSITORY_PERSONAL_NAME_NAME_RUBY = "name_ruby";
    const DBCOL_REPOSITORY_PERSONAL_NAME_E_MAIL_ADDRESS = "e_mail_address";
    const DBCOL_REPOSITORY_PERSONAL_NAME_ITEM_TYPE_ID = "item_type_id";
    const DBCOL_REPOSITORY_PERSONAL_NAME_AUTHOR_ID = "author_id";

    // repository_biblio_info
    const DBCOL_REPOSITORY_BIBLIO_INFO_ITEM_ID = "item_id";
    const DBCOL_REPOSITORY_BIBLIO_INFO_ITEM_NO = "item_no";
    const DBCOL_REPOSITORY_BIBLIO_INFO_ATTRIBUTE_ID = "attribute_id";
    const DBCOL_REPOSITORY_BIBLIO_INFO_BIBLIO_NO = "biblio_no";
    const DBCOL_REPOSITORY_BIBLIO_INFO_BIBLIO_NAME = "biblio_name";
    const DBCOL_REPOSITORY_BIBLIO_INFO_BIBLIO_NAME_ENGLISH = "biblio_name_english";
    const DBCOL_REPOSITORY_BIBLIO_INFO_VOLUME = "volume";
    const DBCOL_REPOSITORY_BIBLIO_INFO_ISSUE = "issue";
    const DBCOL_REPOSITORY_BIBLIO_INFO_START_PAGE = "start_page";
    const DBCOL_REPOSITORY_BIBLIO_INFO_END_PAGE = "end_page";
    const DBCOL_REPOSITORY_BIBLIO_INFO_DATE_OF_ISSUED = "date_of_issued";
    const DBCOL_REPOSITORY_BIBLIO_INFO_ITEM_TYPE_ID = "item_type_id";

    // repository_harvesting
    const DBCOL_REPOSITORY_HARVESTING_REPOSITORY_ID = "repository_id";
    const DBCOL_REPOSITORY_HARVESTING_REPOSITORY_NAME = "repository_name";
    const DBCOL_REPOSITORY_HARVESTING_BASE_URL = "base_url";
    const DBCOL_REPOSITORY_HARVESTING_FROM_DATE = "from_date";
    const DBCOL_REPOSITORY_HARVESTING_UNTIL_DATE = "until_date";
    const DBCOL_REPOSITORY_HARVESTING_SET_PARAM = "set_param";
    const DBCOL_REPOSITORY_HARVESTING_METADATA_PREFIX = "metadata_prefix";
    const DBCOL_REPOSITORY_HARVESTING_POST_INDEX_ID = "post_index_id";
    const DBCOL_REPOSITORY_HARVESTING_AUTOMATIC_SORTING = "automatic_sorting";
    const DBCOL_REPOSITORY_HARVESTING_EXECUTION_DATE = "execution_date";

    // repository_harvesting_log
    const DBCOL_REPOSITORY_HARVESTING_LOG_LOG_ID = "log_id";
    const DBCOL_REPOSITORY_HARVESTING_LOG_REPOSITORY_ID = "repository_id";
    const DBCOL_REPOSITORY_HARVESTING_LOG_OPERATION_ID = "oparation_id";
    const DBCOL_REPOSITORY_HARVESTING_LOG_METADATA_PREFIX = "metadata_prefix";
    const DBCOL_REPOSITORY_HARVESTING_LOG_LIST_SETS = "list_sets";
    const DBCOL_REPOSITORY_HARVESTING_LOG_SET_SPEC = "set_spec";
    const DBCOL_REPOSITORY_HARVESTING_LOG_INDEX_ID = "index_id";
    const DBCOL_REPOSITORY_HARVESTING_LOG_IDENTIFIER = "identifier";
    const DBCOL_REPOSITORY_HARVESTING_LOG_ITEM_ID = "item_id";
    const DBCOL_REPOSITORY_HARVESTING_LOG_URI = "uri";
    const DBCOL_REPOSITORY_HARVESTING_LOG_STATUS = "status";
    const DBCOL_REPOSITORY_HARVESTING_LOG_UPDATE = "`update`";
    const DBCOL_REPOSITORY_HARVESTING_LOG_ERROR_MSG = "error_msg";
    const DBCOL_REPOSITORY_HARVESTING_LOG_RESPONSE_DATE = "response_date";
    const DBCOL_REPOSITORY_HARVESTING_LOG_LAST_MOD_DATE = "last_mod_date";
    const DBCOL_REPOSITORY_HARVESTING_LOG_INS_USER_ID = "ins_user_id";
    const DBCOL_REPOSITORY_HARVESTING_LOG_INS_DATE = "ins_date";

    // repository_index
    const DBCOL_REPOSITORY_INDEX_INDEX_ID = "index_id";
    const DBCOL_REPOSITORY_INDEX_INDEX_NAME = "index_name";
    const DBCOL_REPOSITORY_INDEX_INDEX_NAME_ENGLISH = "index_name_english";
    const DBCOL_REPOSITORY_INDEX_PARENT_INDEX_ID = "parent_index_id";
    const DBCOL_REPOSITORY_INDEX_CONTENTS = "contents";
    const DBCOL_REPOSITORY_INDEX_SHOW_ORDER = "show_order";
    const DBCOL_REPOSITORY_INDEX_PUBLIC_STATE = "public_state";
    const DBCOL_REPOSITORY_INDEX_PUB_DATE = "pub_date";
    const DBCOL_REPOSITORY_INDEX_ACCESS_ROLE = "access_role";
    const DBCOL_REPOSITORY_INDEX_ACCESS_GROUP = "access_group";
    const DBCOL_REPOSITORY_INDEX_EXCLISIVE_ACL_ROLE = "exclusive_acl_role";
    const DBCOL_REPOSITORY_INDEX_EXCLISIVE_ACL_GROUP = "exclusive_acl_group";
    const DBCOL_REPOSITORY_INDEX_COMMENT = "comment";
    const DBCOL_REPOSITORY_INDEX_DISPLAY_MORE = "display_more";
    const DBCOL_REPOSITORY_INDEX_DISPLAY_TYPE = "display_type";
    const DBCOL_REPOSITORY_INDEX_RSS_DISPLAY = "rss_display";
    const DBCOL_REPOSITORY_INDEX_SELECT_INDEX_LIST_DISPLAY = "select_index_list_display";
    const DBCOL_REPOSITORY_INDEX_SELECT_INDEX_LIST_NAME = "select_index_list_name";
    const DBCOL_REPOSITORY_INDEX_SELECT_INDEX_LIST_NAME_ENGLISH = "select_index_list_name_english";
    const DBCOL_REPOSITORY_INDEX_THUMBNAIL = "thumbnail";
    const DBCOL_REPOSITORY_INDEX_THUMBNAIL_NAME = "thumbnail_name";
    const DBCOL_REPOSITORY_INDEX_THUMBNAIL_MAME_TYPE = "thumbnail_mime_type";
    const DBCOL_REPOSITORY_INDEX_REPOSITORY_ID = "repository_id";
    const DBCOL_REPOSITORY_INDEX_SET_SPEC = "set_spec";
    const DBCOL_REPOSITORY_INDEX_CREATE_COVER_FLAG = "create_cover_flag";
    const DBCOL_REPOSITORY_INDEX_OWNER_USER_ID = "owner_user_id";

    // repository_pdf_cover_parameter
    const DBCOL_REPOSITORY_PDF_COVER_PARAMETER_PARAM_NAME = "param_name";
    const DBCOL_REPOSITORY_PDF_COVER_PARAMETER_TEXT = "text";
    const DBCOL_REPOSITORY_PDF_COVER_PARAMETER_IMAGE = "image";
    const DBCOL_REPOSITORY_PDF_COVER_PARAMETER_EXTENSION = "extension";
    const DBCOL_REPOSITORY_PDF_COVER_PARAMETER_MIMETYPE = "mimetype";

    // repository_thumbnail
    const DBCOL_REPOSITORY_THUMB_ITEM_ID = "item_id";
    const DBCOL_REPOSITORY_THUMB_ITEM_NO = "item_no";
    const DBCOL_REPOSITORY_THUMB_ATTR_ID = "attribute_id";
    const DBCOL_REPOSITORY_THUMB_FILE_NO = "file_no";
    const DBCOL_REPOSITORY_THUMB_FILE_NAME = "file_name";
    const DBCOL_REPOSITORY_THUMB_MIME_TYPE = "mime_type";
    const DBCOL_REPOSITORY_THUMB_EXTENSION = "extension";
    const DBCOL_REPOSITORY_THUMB_FILE = "file";
    const DBCOL_REPOSITORY_THUMB_ITEM_TYPE_ID = "item_type_id";

    // repository_file
    const DBCOL_REPOSITORY_FILE_ITEM_ID = "item_id";
    const DBCOL_REPOSITORY_FILE_ITEM_NO = "item_no";
    const DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID = "attribute_id";
    const DBCOL_REPOSITORY_FILE_FILE_NO = "file_no";
    const DBCOL_REPOSITORY_FILE_FILE_NAME = "file_name";
    const DBCOL_REPOSITORY_FILE_DISPLAY_NAME = "display_name";
    const DBCOL_REPOSITORY_FILE_DISPLAY_TYPE = "display_type";
    const DBCOL_REPOSITORY_FILE_MIME_TYPE = "mime_type";
    const DBCOL_REPOSITORY_FILE_EXTENSION = "extension";
    const DBCOL_REPOSITORY_FILE_PREV_ID = "prev_id";
    const DBCOL_REPOSITORY_FILE_FILE_PREV = "file_prev";
    const DBCOL_REPOSITORY_FILE_FILE_PREV_NAME = "file_prev_name";
    const DBCOL_REPOSITORY_FILE_LICENSE_ID = "license_id";
    const DBCOL_REPOSITORY_FILE_LICENSE_NOTATION = "license_notation";
    const DBCOL_REPOSITORY_FILE_PUB_DATE = "pub_date";
    const DBCOL_REPOSITORY_FILE_FLASH_PUB_DATE = "flash_pub_date";
    const DBCOL_REPOSITORY_FILE_ITEM_TYPE_ID = "item_type_id";
    const DBCOL_REPOSITORY_FILE_BROWSING_FLAG = "browsing_flag";
    const DBCOL_REPOSITORY_FILE_COVER_CREATED_FLAG = "cover_created_flag";

    // repository_file_price
    const DBCOL_REPOSITORY_FILE_PRICE_ITEM_ID = "item_id";
    const DBCOL_REPOSITORY_FILE_PRICE_ITEM_NO = "item_no";
    const DBCOL_REPOSITORY_FILE_PRICE_ATTRIBUTE_ID = "attribute_id";
    const DBCOL_REPOSITORY_FILE_PRICE_FILE_NO = "file_no";
    const DBCOL_REPOSITORY_FILE_PRICE_PRICE = "price";

    // repository_license_master
    const DBCOL_REPOSITORY_LICENSE_MASTAER_LICENSE_ID = "license_id";
    const DBCOL_REPOSITORY_LICENSE_MASTAER_LICENSE_NOTATION = "license_notation";
    const DBCOL_REPOSITORY_LICENSE_MASTAER_IMG_URL = "img_url";
    const DBCOL_REPOSITORY_LICENSE_MASTAER_TEXT_URL = "text_url";

    // repository_log
    const DBCOL_REPOSITORY_LOG_LOG_NO = "log_no";
    const DBCOL_REPOSITORY_LOG_RECORD_DATE = "record_date";
    const DBCOL_REPOSITORY_LOG_USER_ID = "user_id";
    const DBCOL_REPOSITORY_LOG_OPERATION_ID = "operation_id";
    const DBCOL_REPOSITORY_LOG_ITEM_ID = "item_id";
    const DBCOL_REPOSITORY_LOG_ITEM_NO = "item_no";
    const DBCOL_REPOSITORY_LOG_ATTRIBUTE_ID = "attribute_id";
    const DBCOL_REPOSITORY_LOG_FILE_NO = "file_no";
    const DBCOL_REPOSITORY_LOG_SEARCH_KEYWORD = "search_keyword";
    const DBCOL_REPOSITORY_LOG_IP_ADDRESS = "ip_address";
    const DBCOL_REPOSITORY_LOG_HOST = "host";
    const DBCOL_REPOSITORY_LOG_USER_AGENT = "user_agent";
    const DBCOL_REPOSITORY_LOG_FILE_STATUS = "file_status";
    const DBCOL_REPOSITORY_LOG_SITE_LICENSE = "site_license";
    const DBCOL_REPOSITORY_LOG_INPUT_TYPE = "input_type";
    const DBCOL_REPOSITORY_LOG_LOGIN_STATUS = "login_status";
    const DBCOL_REPOSITORY_LOG_GROUP_ID = "group_id";

    // repository_usagestatistics
    const DBCOL_REPOSITORY_USAGESTATISTICS_RECORD_DATE = "record_date";
    const DBCOL_REPOSITORY_USAGESTATISTICS_ITEM_ID = "item_id";
    const DBCOL_REPOSITORY_USAGESTATISTICS_ITEM_NO = "item_no";
    const DBCOL_REPOSITORY_USAGESTATISTICS_ATTRIBUTE_ID = "attribute_id";
    const DBCOL_REPOSITORY_USAGESTATISTICS_FILE_NO = "file_no";
    const DBCOL_REPOSITORY_USAGESTATISTICS_OPERATION_ID = "operation_id";
    const DBCOL_REPOSITORY_USAGESTATISTICS_DOMAIN = "domain";
    const DBCOL_REPOSITORY_USAGESTATISTICS_CNT = "cnt";

    // repository_parameter
    const DBCOL_REPOSITORY_PARAMETER_PARAM_NAME = "param_name";
    const DBCOL_REPOSITORY_PARAMETER_PARAM_VALUE = "param_value";
    const DBCOL_REPOSITORY_PARAMETER_EXPLANATION = "explanation";

    // repository_reference
    const DBCOL_REPOSITORY_REF_ORG_ITEM_ID = "org_reference_item_id";
    const DBCOL_REPOSITORY_REF_ORG_ITEM_NO = "org_reference_item_no";
    const DBCOL_REPOSITORY_REF_DEST_ITEM_ID = "dest_reference_item_id";
    const DBCOL_REPOSITORY_REF_DEST_ITEM_NO = "dest_reference_item_no";
    const DBCOL_REPOSITORY_REF_REFERENCE    = "reference";

    // authorities
    const DBCOL_AUTHORITIES_ROLE_AUTHORITY_ID = "role_authority_id";

    // external_author_id_prefix
    const DBCOL_EXTERNAL_AUTHOR_ID_PREFIX_PREFIX_ID = "prefix_id";
    const DBCOL_EXTERNAL_AUTHOR_ID_PREFIX_PREFIX_NAME = "prefix_name";
    const DBCOL_EXTERNAL_AUTHOR_ID_PREFIX_BLOCK_ID = "block_id";
    const DBCOL_EXTERNAL_AUTHOR_ID_PREFIX_ROOM_ID = "room_id";
    const DBCOL_EXTERNAL_AUTHOR_ID_PREFIX_INS_USER_ID = "ins_user_id";
    const DBCOL_EXTERNAL_AUTHOR_ID_PREFIX_MOD_USER_ID = "mod_user_id";
    const DBCOL_EXTERNAL_AUTHOR_ID_PREFIX_DEL_USER_ID = "del_user_id";
    const DBCOL_EXTERNAL_AUTHOR_ID_PREFIX_INS_DATE = "ins_date";
    const DBCOL_EXTERNAL_AUTHOR_ID_PREFIX_MOD_DATE = "mod_date";
    const DBCOL_EXTERNAL_AUTHOR_ID_PREFIX_DELDATE = "del_date";
    const DBCOL_EXTERNAL_AUTHOR_ID_PREFIX_IS_DELETE = "is_delete";

    // operation id
    const HARVESTING_OPERATION_ID_REPOSITORY = 1;
    const HARVESTING_OPERATION_ID_LISTSETS = 2;
    const HARVESTING_OPERATION_ID_LISTRECORD = 3;

    // status
    const HARVESTING_LOG_STATUS_OK = 1;
    const HARVESTING_LOG_STATUS_WARNING = 0;
    const HARVESTING_LOG_STATUS_ERROR = -1;

    // update
    const HARVESTING_LOG_UPDATE_NO_UPDATE = 0;
    const HARVESTING_LOG_UPDATE_INSERT = 1;
    const HARVESTING_LOG_UPDATE_UPDATE = 2;
    const HARVESTING_LOG_UPDATE_DELETE = 3;


    // Add number of additional metadata show order to constant 2014/03/15 T.Koyasu --start--
    const HARVESTING_DC_ADD_ATTR_SHOW_ORDER = 18;
    const HARVESTING_JUNII2_ADD_ATTR_SHOW_ORDER = 62;
    // Add number of additional metadata show order to constant 2014/03/15 T.Koyasu --end--

    // -------------------------------------------
    // Item attribute type
    // -------------------------------------------
    const ITEM_ATTR_TYPE_TEXT = "text";
    const ITEM_ATTR_TYPE_TEXTAREA = "textarea";
    const ITEM_ATTR_TYPE_LINK = "link";
    const ITEM_ATTR_TYPE_CHECKBOX = "checkbox";
    const ITEM_ATTR_TYPE_RADIO = "radio";
    const ITEM_ATTR_TYPE_SELECT = "select";
    const ITEM_ATTR_TYPE_NAME = "name";
    const ITEM_ATTR_TYPE_THUMBNAIL = "thumbnail";
    const ITEM_ATTR_TYPE_FILE = "file";
    const ITEM_ATTR_TYPE_BIBLIOINFO = "biblio_info";
    const ITEM_ATTR_TYPE_DATE = "date";
    const ITEM_ATTR_TYPE_HEADING = "heading";
    const ITEM_ATTR_TYPE_SUPPLE = "supple";
    const ITEM_ATTR_TYPE_FILEPRICE = "file_price";

    // -------------------------------------------
    // NII Type
    // -------------------------------------------
    const NIITYPE_JOURNAL_ARTICLE = "Journal Article";
    const NIITYPE_THESIS_OR_DISSERTATION = "Thesis or Dissertation";
    const NIITYPE_DEPARTMENTAL_BULLETIN_PAPER = "Departmental Bulletin Paper";
    const NIITYPE_CONFERENCE_PAPER = "Conference Paper";
    const NIITYPE_PRESENTATION = "Presentation";
    const NIITYPE_BOOK = "Book";
    const NIITYPE_TECHNICAL_REPORT = "Technical Report";
    const NIITYPE_RESEARCH_PAPER = "Research Paper";
    const NIITYPE_ARTICLE = "Article";
    const NIITYPE_PREPRINT = "Preprint";
    const NIITYPE_LEARNING_MATERIAL = "Learning Material";
    const NIITYPE_DATA_OR_DATASET = "Data or Dataset";
    const NIITYPE_SOFTWARE = "Software";
    const NIITYPE_OTHERS = "Others";

    // -------------------------------------------
    // OAI-PMH metadata prefix
    // -------------------------------------------
    const OAIPMH_METADATA_PREFIX_DC = "oai_dc";
    const OAIPMH_METADATA_PREFIX_JUNII2 = "junii2";
    const OAIPMH_METADATA_PREFIX_LOM = "oai_lom";
    const OAIPMH_METADATA_PREFIX_LIDO = "lido";
    const OAIPMH_METADATA_PREFIX_SPASE = "spase";

    const OAIPMH_TAG_OAIPMH = "OAI-PMH";

    const OAIPMH_TAG_RES_DATE = "responseDate";
    const OAIPMH_TAG_REQUEST  = "request";

    const OAIPMH_TAG_IDENTIFIER  = "identifier";
    const OAIPMH_TAG_REPO_NAME = "repositoryName";
    const OAIPMH_TAG_BASE_URL  = "baseURL";
    const OAIPMH_TAG_PROT_VER = "protocolVersion";
    const OAIPMH_TAG_ADMIN_EMAIL = "adminEmail";
    const OAIPMH_TAG_EARLIEST_DATESTAMP = "earliestDatestamp";
    const OAIPMH_TAG_DEL_REC     = "deletedRecord";
    const OAIPMH_VAL_DEL_REC_NO  = "no";
    const OAIPMH_VAL_DEL_REC_PRE = "persistent";
    const OAIPMH_VAL_DEL_REC_TRN = "transient";
    const OAIPMH_TAG_GRANULARITY = "granularity";
    const OAIPMH_VAL_GRANULARITY = "YYYY-MM-DDThh:mm:ssZ";

    const OAIPMH_TAG_IDENTIFY  = "Identify";

    const OAIPMH_TAG_LIST_MATA_FORMT = "ListMetadataFormats";
    const OAIPMH_TAG_MATA_FORMT  = "metadataFormat";
    const OAIPMH_TAG_META_PREFIX = "metadataPrefix";
    const OAIPMH_TAG_SCHEMA = "schema";
    const OAIPMH_TAG_META_NAMESP = "metadataNamespace";

    const OAIPMH_TAG_LIST_SETS = "ListSets";
    const OAIPMH_TAG_RESUMP_TOKEN = "resumptionToken";
    const OAIPMH_ATTR_EXPRIRATION_DATE = "expirationDate";

    const OAIPMH_TAG_SET = "set";
    const OAIPMH_TAG_SET_SPEC = "setSpec";
    const OAIPMH_TAG_SET_NAME = "setName";

    const OAIPMH_TAG_RECORD   = "record";
    const OAIPMH_TAG_Metadata = "metadata";

    const OAIPMH_TAG_HEADER = "header";
    const OAIPMH_TAG_HEADER_DEL = 'header status="deleted"';
    const OAIPMH_TAG_DATESTAMP = "datestamp";

    const OAIPMH_TAG_ERROR = "error";

    // -------------------------------------------
    // Dublin Core
    // -------------------------------------------
    const DUBLIN_CORE_START = "oai_dc:dc";
    const DUBLIN_CORE_PREFIX = "dc:";
    const DUBLIN_CORE_TITLE = "title";
    const DUBLIN_CORE_CREATOR = "creator";
    const DUBLIN_CORE_SUBJECT = "subject";
    const DUBLIN_CORE_DESCRIPTION = "description";
    const DUBLIN_CORE_PUBLISHER = "publisher";
    const DUBLIN_CORE_CONTRIBUTOR = "contributor";
    const DUBLIN_CORE_DATE = "date";
    const DUBLIN_CORE_TYPE = "type";
    const DUBLIN_CORE_FORMAT = "format";
    const DUBLIN_CORE_IDENTIFIER = "identifier";
    const DUBLIN_CORE_SOURCE = "source";
    const DUBLIN_CORE_LANGUAGE = "language";
    const DUBLIN_CORE_RELATION = "relation";
    const DUBLIN_CORE_COVERAGE = "coverage";
    const DUBLIN_CORE_RIGHTS = "rights";

    // -------------------------------------------
    // JuNii2
    // -------------------------------------------
    const JUNII2_START = "junii2";
    const JUNII2_TITLE = "title";
    const JUNII2_ALTERNATIVE = "alternative";
    const JUNII2_CREATOR = "creator";
    const JUNII2_SUBJECT = "subject";
    const JUNII2_NII_SUBJECT = "NIIsubject";
    const JUNII2_NDC = "NDC";
    const JUNII2_NDLC = "NDLC";
    const JUNII2_BSH = "BSH";
    const JUNII2_NDLSH = "NDLSH";
    const JUNII2_MESH = "MeSH";
    const JUNII2_DDC = "DDC";
    const JUNII2_LCC = "LCC";
    const JUNII2_UDC = "UDC";
    const JUNII2_LCSH = "LCSH";
    const JUNII2_DESCRIPTION = "description";
    const JUNII2_PUBLISHER = "publisher";
    const JUNII2_CONTRIBUTOR = "contributor";
    const JUNII2_DATE = "date";
    const JUNII2_TYPE = "type";
    const JUNII2_NIITYPE = "NIItype";
    const JUNII2_FORMAT = "format";
    const JUNII2_IDENTIFIER = "identifier";
    const JUNII2_URI = "URI";
    const JUNII2_FULL_TEXT_URL = "fullTextURL";
    const JUNII2_ISSN = "issn";
    const JUNII2_NCID = "NCID";
    const JUNII2_JTITLE = "jtitle";
    const JUNII2_VOLUME = "volume";
    const JUNII2_ISSUE = "issue";
    const JUNII2_SPAGE = "spage";
    const JUNII2_EPAGE = "epage";
    const JUNII2_DATE_OF_ISSUED = "dateofissued";
    const JUNII2_SOURCE = "source";
    const JUNII2_LANGUAGE = "language";
    const JUNII2_RELATION = "relation";
    const JUNII2_PMID = "pmid";
    const JUNII2_DOI = "doi";
    const JUNII2_IS_VERSION_OF = "isVersionOf";
    const JUNII2_HAS_VERSION = "hasVersion";
    const JUNII2_IS_REPLACED_BY = "isReplacedBy";
    const JUNII2_REPLACES = "replaces";
    const JUNII2_IS_REQUIRESD_BY = "isRequiredBy";
    const JUNII2_REQUIRES = "requires";
    const JUNII2_IS_PART_OF = "isPartOf";
    const JUNII2_HAS_PART = "hasPart";
    const JUNII2_IS_REFERENCED_BY = "isReferencedBy";
    const JUNII2_REFERENCES = "references";
    const JUNII2_IS_FORMAT_OF = "isFormatOf";
    const JUNII2_HAS_FORMAT = "hasFormat";
    const JUNII2_COVERAGE = "coverage";
    const JUNII2_SPATIAL = "spatial";
    const JUNII2_NII_SPATIAL = "NIIspatial";
    const JUNII2_TEMPORAL = "temporal";
    const JUNII2_NII_TEMPORAL = "NIItemporal";
    const JUNII2_RIGHTS = "rights";
    const JUNII2_TEXTVERSION = "textversion";

    const JUNII2_ATTRIBUTE_LANG = "lang";
    const JUNII2_ATTRIBUTE_VERSION = "version";

    // Add for JuNii2 Redaction 2013/09/16 R.Matsuura --start--
    const JUNII2_SELFDOI = "selfDOI";
    const JUNII2_SELFDOI_JALC = "selfDOI(JaLC)";
    const JUNII2_SELFDOI_CROSSREF = "selfDOI(CrossRef)";
    // Add DataCite 2015/02/10 K.Sugimoto --start--
    const JUNII2_SELFDOI_DATACITE = "selfDOI(DataCite)";
    // Add DataCite 2015/02/10 K.Sugimoto --end--
    const JUNII2_ISBN = "isbn";
    const JUNII2_NAID = "NAID";
    const JUNII2_ICHUSHI = "ichushi";
    const JUNII2_GRANTID = "grantid";
    const JUNII2_DATEOFGRANTED = "dateofgranted";
    const JUNII2_DEGREENAME = "degreename";
    const JUNII2_GRANTOR = "grantor";
    const JUNII2_SELFDOI_RA_JALC = "JaLC";
    const JUNII2_SELFDOI_RA_CROSSREF = "CrossRef";
    // Add DataCite 2015/02/10 K.Sugimoto --start--
    const JUNII2_SELFDOI_RA_DATACITE = "DataCite";
    // Add DataCite 2015/02/10 K.Sugimoto --end--
    // Add for JuNii2 Redaction 2013/09/16 R.Matsuura --end--
    // Add new prefix 2014/01/09 T.Ichikawa --start--
    const JUNII2_SELFDOI_ATTRIBUTE_JALC_DOI = "ra";
    // Add new prefix 2014/01/09 T.Ichikawa --end--

    // -------------------------------------------
    // LOM
    // -------------------------------------------
    const LOM_START = "lom";
    const LOM_URI = "URI";
    const LOM_ISSN = "issn";
    const LOM_NCID = "NCID";
    const LOM_JTITLE = "jtitle";
    const LOM_VOLUME = "volume";
    const LOM_ISSUE = "issue";
    const LOM_SPAGE = "spage";
    const LOM_EPAGE = "epage";
    const LOM_DATE_OF_ISSUED = "dateofissued";
    const LOM_TEXTVERSION = "textversion";
    const LOM_PMID = "pmid";
    const LOM_DOI = "doi";
    const LOM_IS_VERSION_OF = "isversionof";
    const LOM_HAS_VERSION = "hasversion";
    const LOM_IS_REQUIRESD_BY = "isrequiredby";
    const LOM_REQUIRES = "requires";
    const LOM_IS_PART_OF = "ispartof";
    const LOM_HAS_PART = "haspart";
    const LOM_IS_REFERENCED_BY = "isreferencedby";
    const LOM_REFERENCES = "references";
    const LOM_IS_FORMAT_OF = "isformatof";
    const LOM_HAS_FORMAT = "hasformat";
    const LOM_IS_BASIS_FOR = "isbasisfor";
    const LOM_IS_BASED_ON = "isbasedon";
    const LOM_PUBLISH_DATE = "publisher";

    // -------------------------------------------
    // LOM TAG
    // -------------------------------------------
    const LOM_TAG_GENERAL = "general";
    const LOM_TAG_LIFE_CYCLE = "lifeCycle";
    const LOM_TAG_META_METADATA = "metaMetadata";
    const LOM_TAG_TECHNICAL = "technical";
    const LOM_TAG_EDUCATIONAL = "educational";
    const LOM_TAG_RIGHTS = "rights";
    const LOM_TAG_COST = "cost";
    const LOM_TAG_COPYRIGHT_AND_OTHER_RESTRICTIONS = "copyrightAndOtherRestrictions";
    const LOM_TAG_RELATION = "relation";
    const LOM_TAG_ANNOTAION = "annotation";
    const LOM_TAG_CLASSIFICATION = "classification";
    const LOM_TAG_IDENTIFIER = "identifier";
    const LOM_TAG_CATALOG = "catalog";
    const LOM_TAG_ENTRY = "entry";
    const LOM_TAG_TITLE = "title";
    const LOM_TAG_SOURCE = "source";
    const LOM_TAG_VALUE = "value";
    const LOM_TAG_LANGUAGE = "language";
    const LOM_TAG_DESCRIPTION = "description";
    const LOM_TAG_KEYWORD = "keyword";
    const LOM_TAG_COVERAGE = "coverage";
    const LOM_TAG_STRUCTURE = "structure";
    const LOM_TAG_AGGREGATION_LEVEL = "aggregationLevel";
    const LOM_TAG_VERSION = "version";
    const LOM_TAG_STATUS = "status";
    const LOM_TAG_CONTRIBUTE = "contribute";
    const LOM_TAG_METADATA_SCHEMA = "metadataSchema";
    const LOM_TAG_FORMAT = "format";
    const LOM_TAG_SIZE = "size";
    const LOM_TAG_LOCATION = "location";
    const LOM_TAG_REQUIREMENT = "requirement";
    const LOM_TAG_INSTALLATION_REMARKS = "installationRemarks";
    const LOM_TAG_OTHER_PLATFORM_REQIREMENTS = "otherPlatformRequirements";
    const LOM_TAG_DURATION = "duration";
    const LOM_TAG_INTERACTIVITY_TYPE = "interactivityType";
    const LOM_TAG_INTERACTIVITY_LEVEL = "interactivityLevel";
    const LOM_TAG_LEARNING_RESOURCE_TYPE = "learningResourceType";
    const LOM_TAG_SEMANTIC_DENSITY = "semanticDensity";
    const LOM_TAG_INTENDED_END_USER_ROLE = "intendedEndUserRole";
    const LOM_TAG_CONTEXT = "context";
    const LOM_TAG_TYPICAL_AGE_RANGE = "typicalAgeRange";
    const LOM_TAG_DIFFICULTY = "difficulty";
    const LOM_TAG_TYPICAL_LEARNING_TIME = "typicalLearningTime";
    const LOM_TAG_RESOURCE = "resource";
    const LOM_TAG_KIND = "kind";
    const LOM_TAG_DATE = "date";
    const LOM_TAG_DATE_TIME = "dateTime";
    const LOM_TAG_ENTITY = "entity";
    const LOM_TAG_PURPOSE = "purpose";
    const LOM_TAG_TAXON_PATH = "taxonPath";
    const LOM_TAG_TAXON = "taxon";
    const LOM_TAG_ID = "id";
    const LOM_TAG_STRING = "string";
    const LOM_TAG_ROLE = "role";
    const LOM_TAG_TYPE = "type";
    const LOM_TAG_OR_COMPOSITE = "orComposite";
    const LOM_TAG_NAME = "name";
    const LOM_TAG_MINIMUM_VERSION = "minimumVersion";
    const LOM_TAG_MAXIMUM_VERSION = "maximumVersion";

    // -------------------------------------------
    // LOM Mapping Attribute Name
    // -------------------------------------------
    const LOM_MAP_GNRL_IDENTIFER = "generalIdentifier";
    const LOM_MAP_GNRL_TITLE = "generalTitle";
    const LOM_MAP_GNRL_LANGUAGE = "generalLanguage";
    const LOM_MAP_GNRL_DESCRIPTION = "generalDescription";
    const LOM_MAP_GNRL_KEYWORD = "generalKeyword";
    const LOM_MAP_GNRL_COVERAGE = "generalCoverage";
    const LOM_MAP_GNRL_STRUCTURE = "generalStructure";
    const LOM_MAP_GNRL_AGGREGATION_LEVEL = "generalAggregationLevel";
    const LOM_MAP_LFCYCL_VERSION = "lifeCycleVersion";
    const LOM_MAP_LFCYCL_STATUS = "lifeCycleStatus";
    const LOM_MAP_LFCYCL_CONTRIBUTE = "lifeCycleContribute";
    const LOM_MAP_LFCYCL_CONTRIBUTE_AUTHOR = "lifeCycleContributeRoleAuthor";
    const LOM_MAP_LFCYCL_CONTRIBUTE_PUBLISHER = "lifeCycleContributeRolePublisher";
    const LOM_MAP_LFCYCL_CONTRIBUTE_PUBLISH_DATE = "lifeCycleContributeDate";
    const LOM_MAP_LFCYCL_CONTRIBUTE_UNKNOWN = "lifeCycleContributeRoleUnknown";
    const LOM_MAP_LFCYCL_CONTRIBUTE_INITIATOR = "lifeCycleContributeRoleInitiator";
    const LOM_MAP_LFCYCL_CONTRIBUTE_TERMINATOR = "lifeCycleContributeRoleTerminator";
    const LOM_MAP_LFCYCL_CONTRIBUTE_VALIDATOR = "lifeCycleContributeRoleValidator";
    const LOM_MAP_LFCYCL_CONTRIBUTE_EDITOR = "lifeCycleContributeRoleEditor";
    const LOM_MAP_LFCYCL_CONTRIBUTE_GRAPHICAL_DESIGNER = "lifeCycleContributeRoleGraphicalDesigner";
    const LOM_MAP_LFCYCL_CONTRIBUTE_TECHNICAL_IMPLEMENTER = "lifeCycleContributeRoleTechnicalImplementer";
    const LOM_MAP_LFCYCL_CONTRIBUTE_CONTENT_PROVIDER = "lifeCycleContributeRoleContentProvider";
    const LOM_MAP_LFCYCL_CONTRIBUTE_TECHNICAL_VALIDATOR = "lifeCycleContributeRoleTechnicalValidator";
    const LOM_MAP_LFCYCL_CONTRIBUTE_EDUCATIONAL_VALIDATOR = "lifeCycleContributeRoleEducationalValidator";
    const LOM_MAP_LFCYCL_CONTRIBUTE_SCRIPT_WRITER = "lifeCycleContributeRoleScriptWriter";
    const LOM_MAP_LFCYCL_CONTRIBUTE_INSTRUCTIONAL_DESIGNER = "lifeCycleContributeRoleInstructionalDesigner";
    const LOM_MAP_LFCYCL_CONTRIBUTE_SUBJECT_MATTER_EXPERT = "lifeCycleContributeRoleSubjectMatterExpert";
    const LOM_MAP_MTMTDT_IDENTIFER = "metaMetadataIdentifer";
    const LOM_MAP_MTMTDT_CONTRIBUTE = "metaMetadataContribute";
    const LOM_MAP_MTMTDT_CONTRIBUTE_CREATOR = "metaMetadataContributeRoleCreator";
    const LOM_MAP_MTMTDT_CONTRIBUTE_VALIDATOR = "metaMetadataContributeRoleValidator";
    const LOM_MAP_MTMTDT_METADATA_SCHEMA = "metaMetadataMetadataSchema";
    const LOM_MAP_MTMTDT_LANGUAGE = "metaMetadataLanguage";
    const LOM_MAP_TCHNCL_FORMAT = "technicalFormat";
    const LOM_MAP_TCHNCL_SIZE = "technicalSize";
    const LOM_MAP_TCHNCL_LOCATION = "technicalLocation";
    const LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_TYPE = "technicalRequirementOrCompositeType";
    const LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_NAME = "technicalRequirementOrCompositeName";
    const LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_MINIMUM_VERSION = "technicalRequirementOrCompositeMinimumVersion";
    const LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_MAXIMUM_VERSION = "technicalRequirementOrCompositeMaximumVersion";
    const LOM_MAP_TCHNCL_INSTALLATION_REMARKS = "technicalInstallationRemarks";
    const LOM_MAP_TCHNCL_OTHER_PLATFORM_REQUIREMENTS = "technicalOtherPlatformRequirements";
    const LOM_MAP_TCHNCL_DURATION = "technicalDuration";
    const LOM_MAP_EDUCTNL_INTERACTIVITY_TYPE = "educationalInteractivityType";
    const LOM_MAP_EDUCTNL_LEARNING_RESOURCE_TYPE = "educationalLearningResourceType";
    const LOM_MAP_EDUCTNL_INTERACTIVITY_LEVEL = "educationalInteractivityLevel";
    const LOM_MAP_EDUCTNL_SEMANTIC_DENSITY = "educationalSemanticDensity";
    const LOM_MAP_EDUCTNL_INTENDED_END_USER_ROLE = "educationalIntendedEndUserRole";
    const LOM_MAP_EDUCTNL_CONTEXT = "educationalContext";
    const LOM_MAP_EDUCTNL_TYPICAL_AGE_RANGE = "educationalTypicalAgeRange";
    const LOM_MAP_EDUCTNL_DIFFICULTY = "educationalDifficulty";
    const LOM_MAP_EDUCTNL_TYPICAL_LEARNING_TIME = "educationalTypicalLearningTime";
    const LOM_MAP_EDUCTNL_DESCRIPTION = "educationalDescription";
    const LOM_MAP_EDUCTNL_LANGUAGE = "educationalLanguage";
    const LOM_MAP_RLTN = "relationResource";
    const LOM_MAP_RLTN_IS_PART_OF = "relationIsPartOf";
    const LOM_MAP_RLTN_HAS_PART_OF = "relationHasPart";
    const LOM_MAP_RLTN_IS_VERSION_OF = "relationIsVersionOf";
    const LOM_MAP_RLTN_HAS_VERSION = "relationHasVersion";
    const LOM_MAP_RLTN_IS_FORMAT_OF = "relationIsFormatOf";
    const LOM_MAP_RLTN_HAS_FORMAT = "relationHasFormat";
    const LOM_MAP_RLTN_REFERENCES = "relationReferences";
    const LOM_MAP_RLTN_IS_REFERENCED_BY = "relationIsReferencedBy";
    const LOM_MAP_RLTN_IS_BASED_ON = "relationIsBasedOn";
    const LOM_MAP_RLTN_IS_BASIS_FOR = "relationIsBasisFor";
    const LOM_MAP_RLTN_REQUIRES = "relationRequires";
    const LOM_MAP_RLTN_IS_REQUIRED_BY = "relationIsRequiredBy";
    const LOM_MAP_RGHTS_COST = "rightsCost";
    const LOM_MAP_RGHTS_COPYRIGHT_AND_OTHER_RESTRICTIONS = "rightsCopyrightAndOtherRestrictions";
    const LOM_MAP_RGHTS_DESCRIPTION = "rightsDescription";
    const LOM_MAP_ANNTTN_ENTITY = "annotationEntity";
    const LOM_MAP_ANNTTN_DATE = "annotationDate";
    const LOM_MAP_ANNTTN_DESCRIPTION = "annotationDescription";
    const LOM_MAP_CLSSFCTN_PURPOSE = "classificationPurpose";
    const LOM_MAP_CLSSFCTN_DESCRIPTION = "classificationDescription";
    const LOM_MAP_CLSSFCTN_KEYWORD = "classificationKeyword";
    const LOM_MAP_CLSSFCTN_TAXON_PATH_SOURCE = "classificationTaxonPathSource";
    const LOM_MAP_CLSSFCTN_TAXON = "classificationTaxonPathTaxon";

    // -------------------------------------------
    // LIDO
    // -------------------------------------------
    const BLANK_WORD = "&EMPTY&";
    const XML_LF = "&#xA;";
    const LIDO_SCHEMA_ORG = "http://www.lido-schema.org";
    const LIDO_SCHEMA_XSD = "http://www.lido-schema.org/schema/v1.0/lido-v1.0.xsd";
    const LIDO_XML_SCHEMAINSTANCE = "http://www.w3.org/2001/XMLSchema-instance";
    const LIDO_XML_NAMESPACE_URL = "http://www.w3.org/XML/1998/namespace";
    const LIDO_LANG_JAPANESE = "jp";
    const LIDO_LANG_ENGLISH = "en";
    const LIDO_ATTR_LANG = "lang";
    const LIDO_ATTR_XML_LANG = "xml:lang";
    const LIDO_ATTR_XML_TYPE = "lido:type";
    const LIDO_TAG_NAMESPACE = "lido:";
    const LIDO_TAG_LIDO_LIDO = "lido:lido";
    const LIDO_TAG_LIDO_WRAP = "lido:lidoWrap";
    const LIDO_ATTRIBUTE_TYPE_URI = "URI";

    const GML_SCHEMA = "http://www.opengis.net/gml";
    const GML_SCHEMA_XSD = "http://schemas.opengis.net/gml/3.1.1/base/gml.xsd";
    const GML_TAG_NAMESPACE = "gml:";
    const GML_TAG_POINT = "Point";
    const GML_TAG_POS = "pos";
    const GML_TAG_POLYGON = "Polygon";
    const GML_TAG_EXTERIOR = "exterior";
    const GML_TAG_LINEAR_RING = "LinearRing";
    const GML_TAG_COORDINATES = "coordinates";

    // -------------------------------------------
    // LIDO TAG
    // -------------------------------------------
    const LIDO_TAG_LIDO_REC_ID = "lidoRecID";
    const LIDO_TAG_DESCRIPTIVE_METADATA = "descriptiveMetadata";
    const LIDO_TAG_OBJECT_CLASSIFICATION_WRAP = "objectClassificationWrap";
    const LIDO_TAG_OBJECT_WORK_TYPE_WRAP = "objectWorkTypeWrap";
    const LIDO_TAG_OBJECT_WORK_TYPE = "objectWorkType";
    const LIDO_TAG_CONCEPT_ID = "conceptID";
    const LIDO_TAG_TERM = "term";
    const LIDO_TAG_CLASSIFICATION_WRAP = "classificationWrap";
    const LIDO_TAG_CLASSIFICATION = "classification";
    const LIDO_TAG_OBJECT_IDENTIFICATION_WRAP = "objectIdentificationWrap";
    const LIDO_TAG_TITLE_WRAP = "titleWrap";
    const LIDO_TAG_TITLE_SET = "titleSet";
    const LIDO_TAG_APPELLATION_VALUE = "appellationValue";
    const LIDO_TAG_INSCRIPTIONS_WRAP = "inscriptionsWrap";
    const LIDO_TAG_INSCRIPTIONS = "inscriptions";
    const LIDO_TAG_INSCRIPTION_TRANSCRIPTION = "inscriptionTranscription";
    const LIDO_TAG_REPOSITORY_WRAP = "repositoryWrap";
    const LIDO_TAG_REPOSITORY_SET = "repositorySet";
    const LIDO_TAG_REPOSITORY_NAME = "repositoryName";
    const LIDO_TAG_LEGAL_BODY_NAME = "legalBodyName";
    const LIDO_TAG_LEGAL_BODY_WEB_LINK = "legalBodyWeblink";
    const LIDO_TAG_WORK_ID = "workID";
    const LIDO_TAG_DISPLAY_STATE_EDITION_WRAP = "displayStateEditionWrap";
    const LIDO_TAG_DISPLAY_STATE = "displayState";
    const LIDO_TAG_OBJECT_DESCRIPTION_WRAP = "objectDescriptionWrap";
    const LIDO_TAG_OBJECT_DESCRIPTION_SET = "objectDescriptionSet";
    const LIDO_TAG_DESCRIPTIVE_NOTE_VALUE = "descriptiveNoteValue";
    const LIDO_TAG_OBJECT_MEASUREMENTS_WRAP = "objectMeasurementsWrap";
    const LIDO_TAG_OBJECT_MEASUREMENTS_SET = "objectMeasurementsSet";
    const LIDO_TAG_DISPLAY_OBJECT_MEASUREMENTS = "displayObjectMeasurements";
    const LIDO_TAG_EVENT_WRAP = "eventWrap";
    const LIDO_TAG_EVENT_SET = "eventSet";
    const LIDO_TAG_DISPLAY_EVENT = "displayEvent";
    const LIDO_TAG_EVENT = "event";
    const LIDO_TAG_EVENT_TYPE = "eventType";
    const LIDO_TAG_EVENT_ACTOR = "eventActor";
    const LIDO_TAG_EVENT_DATE = "eventDate";
    const LIDO_TAG_DISPLAY_ACTOR_IN_ROLE = "displayActorInRole";
    const LIDO_TAG_DISPLAY_DATE = "displayDate";
    const LIDO_TAG_EARLIEST_DATE = "earliestDate";
    const LIDO_TAG_DATE = "date";
    const LIDO_TAG_LATEST_DATE = "latestDate";
    const LIDO_TAG_PERIOD_NAME = "periodName";
    const LIDO_TAG_EVENT_PLACE = "eventPlace";
    const LIDO_TAG_DISPLAY_PLACE = "displayPlace";
    const LIDO_TAG_PLACE = "place";
    const LIDO_TAG_GML = "gml";
    const LIDO_TAG_EVENT_MATERIALS_TECH = "eventMaterialsTech";
    const LIDO_TAG_DISPLAY_MATERIALS_TECH = "displayMaterialsTech";
    const LIDO_TAG_OBJECT_RELATION_WRAP = "objectRelationWrap";
    const LIDO_TAG_SUBJECT_WRAP = "subjectWrap";
    const LIDO_TAG_SUBJECT_SET = "subjectSet";
    const LIDO_TAG_DISPLAY_SUBJECT = "displaySubject";
    const LIDO_TAG_RELATED_WORKS_WRAP = "relatedWorksWrap";
    const LIDO_TAG_RELATED_WORK_SET = "relatedWorkSet";
    const LIDO_TAG_RELATED_WORK = "relatedWork";
    const LIDO_TAG_DISPLAY_OBJECT = "displayObject";
    const LIDO_TAG_ADMINISTRATIVE_METADATA = "administrativeMetadata";
    const LIDO_TAG_RECORD_WRAP = "recordWrap";
    const LIDO_TAG_RECORD_ID = "recordID";
    const LIDO_TAG_RECORD_TYPE = "recordType";
    const LIDO_TAG_RECORD_SOURCE = "recordSource";
    const LIDO_TAG_RECORD_INFO_SET = "recordInfoSet";
    const LIDO_TAG_RECORD_INFO_LINK = "recordInfoLink";
    const LIDO_TAG_RECORD_METADATA_DATE = "recordMetadataDate";
    const LIDO_TAG_RESOURCE_WRAP = "resourceWrap";
    const LIDO_TAG_RESOURCE_SET = "resourceSet";
    const LIDO_TAG_RESOURCE_REPRESENTATION = "resourceRepresentation";
    const LIDO_TAG_LINK_RESOURCE = "linkResource";
    const LIDO_TAG_RESOURCE_DESCRIPTION = "resourceDescription";
    const LIDO_TAG_RESOURCE_SOURCE = "resourceSource";
    const LIDO_TAG_RIGHT_RESOURCE = "rightsResource";
    const LIDO_TAG_CREDIT_LINE = "creditLine";

    // -------------------------------------------
    // LIDO FULL
    // -------------------------------------------
    const LIDO_FULLNAME_OBJECTWORKTYPE_CONCEPTID = "descriptiveMetadata.objectClassificationWrap.objectWorkTypeWrap.objectWorkType.conceptID";
    const LIDO_FULLNAME_OBJECTWORKTYPE_TERM = "descriptiveMetadata.objectClassificationWrap.objectWorkTypeWrap.objectWorkType.term";
    const LIDO_FULLNAME_CLASSIFICATION_CONCEPTID = "descriptiveMetadata.objectClassificationWrap.classificationWrap.classification.conceptID";
    const LIDO_FULLNAME_CLASSIFICATION_TERM = "descriptiveMetadata.objectClassificationWrap.classificationWrap.classification.term";
    const LIDO_FULLNAME_TITLESET = "descriptiveMetadata.objectIdentificationWrap.titleWrap.titleSet.appellationValue";
    const LIDO_FULLNAME_INSCRIPTIONTRANSCRIPTION = "descriptiveMetadata.objectIdentificationWrap.inscriptionsWrap.inscriptions.inscriptionTranscription";
    const LIDO_FULLNAME_REPOSITORYNAME_LEGALBODYNAME = "descriptiveMetadata.objectIdentificationWrap.repositoryWrap.repositorySet.repositoryName.legalBodyName.appellationValue";
    const LIDO_FULLNAME_REPOSITORYNAME_LEGALBODYWEBLINK = "descriptiveMetadata.objectIdentificationWrap.repositoryWrap.repositorySet.repositoryName.legalBodyWeblink";
    const LIDO_FULLNAME_WORKID = "descriptiveMetadata.objectIdentificationWrap.repositoryWrap.repositorySet.workID";
    const LIDO_FULLNAME_DISPLAYSTATE = "descriptiveMetadata.objectIdentificationWrap.displayStateEditionWrap.displayState";
    const LIDO_FULLNAME_DESCRIPTIVENOTEVALUE = "descriptiveMetadata.objectIdentificationWrap.objectDescriptionWrap.objectDescriptionSet.descriptiveNoteValue";
    const LIDO_FULLNAME_DISPLAYOBJECTMEASUREMENTS = "descriptiveMetadata.objectIdentificationWrap.objectMeasurementsWrap.objectMeasurementsSet.displayObjectMeasurements";
    const LIDO_FULLNAME_DISPLAYEVENT = "descriptiveMetadata.eventWrap.eventSet.displayEvent";
    const LIDO_FULLNAME_EVENTTYPE = "descriptiveMetadata.eventWrap.eventSet.event.eventType.term";
    const LIDO_FULLNAME_DISPLAYACTORINROLE = "descriptiveMetadata.eventWrap.eventSet.event.eventActor.displayActorInRole";
    const LIDO_FULLNAME_DISPLAYDATE = "descriptiveMetadata.eventWrap.eventSet.event.eventDate.displayDate";
    const LIDO_FULLNAME_EARLIESTDATE ="descriptiveMetadata.eventWrap.eventSet.event.eventDate.date.earliestDate";
    const LIDO_FULLNAME_LATESTDATE = "descriptiveMetadata.eventWrap.eventSet.event.eventDate.date.latestDate";
    const LIDO_FULLNAME_PERIODNAME = "descriptiveMetadata.eventWrap.eventSet.event.periodName.term";
    const LIDO_FULLNAME_DISPLAYPLACE = "descriptiveMetadata.eventWrap.eventSet.event.eventPlace.displayPlace";
    const LIDO_FULLNAME_PLACE_GML = "descriptiveMetadata.eventWrap.eventSet.event.eventPlace.place.gml";
    const LIDO_FULLNAME_DISPLAYMATERIALSTECH = "descriptiveMetadata.eventWrap.eventSet.event.eventMaterialsTech.displayMaterialsTech";
    const LIDO_FULLNAME_DISPLAYSUBJECT = "descriptiveMetadata.objectRelationWrap.subjectWrap.subjectSet.displaySubject";
    const LIDO_FULLNAME_RELATEDWORKDISPLAYOBJECT = "descriptiveMetadata.objectRelationWrap.relatedWorksWrap.relatedWorkSet.relatedWork.displayObject";
    const LIDO_FULLNAME_RECORDID = "administrativeMetadata.recordWrap.recordID";
    const LIDO_FULLNAME_RECORDTYPE = "administrativeMetadata.recordWrap.recordType.term";
    const LIDO_FULLNAME_RECORDSOURCE = "administrativeMetadata.recordWrap.recordSource.legalBodyName.appellationValue";
    const LIDO_FULLNAME_RECOURDINFOLINK = "administrativeMetadata.recordWrap.recordInfoSet.recordInfoLink";
    const LIDO_FULLNAME_RECORDMETADATADATE = "administrativeMetadata.recordWrap.recordInfoSet.recordMetadataDate";
    const LIDO_FULLNAME_LINKRESOURCE = "administrativeMetadata.resourceWrap.resourceSet.resourceRepresentation.linkResource";
    const LIDO_FULLNAME_RESOURCEDESCRIPTION = "administrativeMetadata.resourceWrap.resourceSet.resourceDescription";
    const LIDO_FULLNAME_RESOURCESOURCE = "administrativeMetadata.resourceWrap.resourceSet.resourceSource.legalBodyName.appellationValue";
    const LIDO_FULLNAME_CREDITLINE = "administrativeMetadata.resourceWrap.resourceSet.rightsResource.creditLine";

    // -------------------------------------------
    // Item's language
    // -------------------------------------------
    const ITEM_LANG_JA = "ja";
    const ITEM_LANG_EN = "en";
    const ITEM_LANG_FR = "fr";
    const ITEM_LANG_IT = "it";
    const ITEM_LANG_DE = "de";
    const ITEM_LANG_ES = "es";
    const ITEM_LANG_ZH = "zh";
    const ITEM_LANG_RU = "ru";
    const ITEM_LANG_LA = "la";
    const ITEM_LANG_MS = "ms";
    const ITEM_LANG_EO = "eo";
    const ITEM_LANG_AR = "ar";
    const ITEM_LANG_EL = "el";
    const ITEM_LANG_KO = "ko";
    // becouse 'other' is stopword of FULLTEXT
    const ITEM_LANG_OTHER = "otherlanguage";

    // -------------------------------------------
    // Item Attr Type display language
    // -------------------------------------------
    const ITEM_ATTR_TYPE_LANG_JA = "japanese";
    const ITEM_ATTR_TYPE_LANG_EN = "english";

    // -------------------------------------------
    // Google Scholar
    // -------------------------------------------
    const TAG_NAME_META = "meta";
    const TAG_ATTR_KEY_NAME = "name";
    const TAG_ATTR_KEY_CONTENT = "content";
    const GOOGLESCHOLAR_TITLE = "citation_title";
    const GOOGLESCHOLAR_AUTHOR = "citation_author";
    const GOOGLESCHOLAR_PUB_DATE = "citation_publication_date";
    const GOOGLESCHOLAR_ONLINE_DATE = "citation_online_date";
    const GOOGLESCHOLAR_JOURNAL_TITLE = "citation_journal_title";
    const GOOGLESCHOLAR_VOLUME = "citation_volume";
    const GOOGLESCHOLAR_ISSUE = "citation_issue";
    const GOOGLESCHOLAR_FIRSTPAGE = "citation_firstpage";
    const GOOGLESCHOLAR_LASTPAGE = "citation_lastpage";
    const GOOGLESCHOLAR_DISS_INST = "citation_dissertation_institution";
    const GOOGLESCHOLAR_TECH_REPO_INST = "citation_technical_report_institution";
    const GOOGLESCHOLAR_TECH_REPO_NUMBER = "citation_technical_report_number";
    const GOOGLESCHOLAR_PDF_URL = "citation_pdf_url";
    const GOOGLESCHOLAR_ABSTRACT_HTML_URL = "citation_abstract_html_url";
    const GOOGLESCHOLAR_FULLTEXT_HTML_URL = "citation_fulltext_html_url";
    const GOOGLESCHOLAR_DOI = "citation_doi";
    const GOOGLESCHOLAR_ISSN = "citation_issn";
    const GOOGLESCHOLAR_PUBLISHER = "citation_publisher";
    const GOOGLESCHOLAR_DC_CONTRIBUTOR = "dc.Contributor";
    const GOOGLESCHOLAR_KEYWORD = "citation_keywords";
    const GOOGLESCHOLAR_DISSERTATION_INSTITUTION = "citation_dissertation_institution";

    // -------------------------------------------
    // Operation ID
    // -------------------------------------------
    const LOG_OPERATION_ENTRY_ITEM = 1;
    const LOG_OPERATION_DOWNLOAD_FILE = 2;
    const LOG_OPERATION_DETAIL_VIEW = 3;
    const LOG_OPERATION_SEARCH = 4;
    const LOG_OPERATION_TOP = 5;
    const LOG_OPERATION_EBOOK_VIEW = 6;

    // -------------------------------------------
    // Contributor(Posted agency)
    // -------------------------------------------
    // item_contributor column name
    const ITEM_CONTRIBUTOR_HANDLE = "handle";
    const ITEM_CONTRIBUTOR_NAME = "name";
    const ITEM_CONTRIBUTOR_EMAIL = "email";

    // Status for function "getUserIdForContributor" in Repository_Action_Main_Item_Editlinks
    const ITEM_CONTRIBUTOR_STATUS_SUCCESS = "Success";
    const ITEM_CONTRIBUTOR_STATUS_NOTEXIST = "NotExist";
    const ITEM_CONTRIBUTOR_STATUS_CONFLICT = "Conflict";
    const ITEM_CONTRIBUTOR_STATUS_NOAUTH = "NoAuth";

    // -------------------------------------------
    // Session Parameter Name
    // -------------------------------------------
    // For item_contributor
    const SESSION_PARAM_ORG_CONTRIBUTOR_USER_ID = "orgContributorUserId";
    const SESSION_PARAM_CONTRIBUTOR_USER_ID = "contributorUserId";
    const SESSION_PARAM_ITEM_CONTRIBUTOR = "item_contributor";
    const SESSION_PARAM_CONTRIBUTOR_ERROR_MSG = "contributorErrorMsg";

    // -------------------------------------------
    // SPASE
    // -------------------------------------------
    // const SPASE_START = "SPASE";
    const SPASE_SCHEMA_ORG = "http://www.spase-group.org/data/schema/";
    const SPASE_SCHEMA_XSD = "http://www.spase-group.org/data/schema/spase-2_2_3.xsd";
    const SPASE_START = "Spase";
    const SPASE_LANGUAGE = "language";
    const SPASE_LANG_JAPANESE = "ja";
    const SPASE_LANG_ENGLISH = "en";
    const SPASE_URI = "URI";
    const SPASE_VERSION = "2.2.3";


	//SPASE_Numerical_Data
	const SPASE_NUMERICALDATA = "NumericalData";
	const SPASE_NUMERICALDATA_RESOURCEID = "NumericalData.ResourceID";
	const SPASE_NUMERICALDATA_RESOURCEHEADER_RESOURCENAME = "NumericalData.ResourceHeader.ResourceName";
	const SPASE_NUMERICALDATA_RESOURCEHEADER_RELEASEDATE = "NumericalData.ResourceHeader.ReleaseDate";
	const SPASE_NUMERICALDATA_RESOURCEHEADER_DESCRIPTION = "NumericalData.ResourceHeader.Description";
	const SPASE_NUMERICALDATA_RESOURCEHEADER_ACKNOWLEDGEMENT = "NumericalData.ResourceHeader.Acknowledgement";
	const SPASE_NUMERICALDATA_RESOURCEHEADER_CONTACT_PERSONID = "NumericalData.ResourceHeader.Contact.PersonID";
	const SPASE_NUMERICALDATA_RESOURCEHEADER_CONTACT_ROLE = "NumericalData.ResourceHeader.Contact.Role";
	const SPASE_NUMERICALDATA_ACCESSINFORMATION_REPOSITORYID = "NumericalData.AccessInformation.RepositoryID";
	const SPASE_NUMERICALDATA_ACCESSINFORMATION_AVAILABILITY = "NumericalData.AccessInformation.Availability";
	const SPASE_NUMERICALDATA_ACCESSINFORMATION_ACCESSRIGHTS = "NumericalData.AccessInformation.AccessRights";
	const SPASE_NUMERICALDATA_ACCESSINFORMATION_ACCESSURL_NAME = "NumericalData.AccessInformation.AccessURL.Name";
	const SPASE_NUMERICALDATA_ACCESSINFORMATION_ACCESSURL_URL = "NumericalData.AccessInformation.AccessURL.URL";
	const SPASE_NUMERICALDATA_ACCESSINFORMATION_ACCESSURL_DESCRIPTION = "NumericalData.AccessInformation.AccessURL.Description";
	const SPASE_NUMERICALDATA_ACCESSINFORMATION_FORMAT = "NumericalData.AccessInformation.Format";
	const SPASE_NUMERICALDATA_ACCESSINFORMATION_DATAEXTENT_QUANTITY = "NumericalData.AccessInformation.DataExtent.Quantity";
  const SPASE_NUMERICALDATA_INSTRUMENTID = "NumericalData.InstrumentID";
	const SPASE_NUMERICALDATA_PHENOMENONTYPE = "NumericalData.PhenomenonType";
	const SPASE_NUMERICALDATA_MEASUREMENTTYPE = "NumericalData.MeasurementType";
  const SPASE_NUMERICALDATA_KEYWORD = "NumericalData.Keyword";
	const SPASE_NUMERICALDATA_TEMPORALDESCRIPTION_STARTDATE = "NumericalData.TemporalDescription.StartDate";
	const SPASE_NUMERICALDATA_TEMPORALDESCRIPTION_STOPDATE = "NumericalData.TemporalDescription.StopDate";
	const SPASE_NUMERICALDATA_TEMPORALDESCRIPTION_RELATIVESTOPDATE = "NumericalData.TemporalDescription.RelativeStopDate";
	const SPASE_NUMERICALDATA_OBSERVEDREGION = "NumericalData.ObservedRegion";
	const SPASE_NUMERICALDATA_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME = "NumericalData.SpatialCoverage.CoordinateSystem.CoordinateSystemName";
	const SPASE_NUMERICALDATA_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION = "NumericalData.SpatialCoverage.CoordinateSystem.CoordinateRepresentation";
	const SPASE_NUMERICALDATA_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE = "NumericalData.SpatialCoverage.NorthernmostLatitude";
	const SPASE_NUMERICALDATA_SPATIALCOVERAGE_SOUTHERNMOSTLATITUDE = "NumericalData.SpatialCoverage.SouthernmostLatitude";
	const SPASE_NUMERICALDATA_SPATIALCOVERAGE_EASTERNMOSTLONGITUDE = "NumericalData.SpatialCoverage.EasternmostLongitude";
	const SPASE_NUMERICALDATA_SPATIALCOVERAGE_WESTERNMOSTLONGITUDE = "NumericalData.SpatialCoverage.esternmostLongitude";
	const SPASE_NUMERICALDATA_SPATIALCOVERAGE_UNIT = "NumericalData.SpatialCoverage.Unit";
	const SPASE_NUMERICALDATA_SPATIALCOVERAGE_MINIMUMALTITUDE = "NumericalData.SpatialCoverage.MinimumAltitude";
	const SPASE_NUMERICALDATA_SPATIALCOVERAGE_MAXIMUMALTITUDE = "NumericalData.SpatialCoverage.MaximumAltitude";
	const SPASE_NUMERICALDATA_SPATIALCOVERAGE_REFERENCE = "NumericalData.SpatialCoverage.Reference";
	const SPASE_NUMERICALDATA_PARAMETER_NAME = "NumericalData.Parameter.Name";
	const SPASE_NUMERICALDATA_PARAMETER_DESCRIPTION = "NumericalData.Parameter.Description";
	const SPASE_NUMERICALDATA_PARAMETER_FIELD_FIELDQUANTITY = "NumericalData.Parameter.Field.FieldQuantity";
	const SPASE_NUMERICALDATA_PARAMETER_PARTICLE_PARTICLETYPE = "NumericalData.Parameter.Particle.ParticleType";
	const SPASE_NUMERICALDATA_PARAMETER_PARTICLE_PARTICLEQUANTITY = "NumericalData.Parameter.Particle.ParticleQuantity";
	const SPASE_NUMERICALDATA_PARAMETER_WAVE_WAVETYPE = "NumericalData.Parameter.Parameter.Wave.WaveType";
	const SPASE_NUMERICALDATA_PARAMETER_WAVE_WAVEQUANTITY = "NumericalData.Parameter.Wave.WaveQuantity";
	const SPASE_NUMERICALDATA_PARAMETER_MIXED_MIXEDQUANTITY = "NumericalData.Parameter.Mixed.MixedQuantity";
	const SPASE_NUMERICALDATA_PARAMETER_SUPPORT_SUPPORTQUANTITY = "NumericalData.Parameter.Support.SupportQuantity";
	//Delete Prefix
	const SPASE_ND_RESOURCEID = "ResourceID";
	const SPASE_ND_RESOURCEHEADER_RESOURCENAME = "ResourceHeader.ResourceName";
	const SPASE_ND_RESOURCEHEADER_RELEASEDATE = "ResourceHeader.ReleaseDate";
	const SPASE_ND_RESOURCEHEADER_DESCRIPTION = "ResourceHeader.Description";
	const SPASE_ND_RESOURCEHEADER_ACKNOWLEDGEMENT = "ResourceHeader.Acknowledgement";
	const SPASE_ND_RESOURCEHEADER_CONTACT_PERSONID = "ResourceHeader.Contact.PersonID";
	const SPASE_ND_RESOURCEHEADER_CONTACT_ROLE = "ResourceHeader.Contact.Role";
	const SPASE_ND_ACCESSINFORMATION_REPOSITORYID = "AccessInformation.RepositoryID";
	const SPASE_ND_ACCESSINFORMATION_AVAILABILITY = "AccessInformation.Availability";
	const SPASE_ND_ACCESSINFORMATION_ACCESSRIGHTS = "AccessInformation.AccessRights";
	const SPASE_ND_ACCESSINFORMATION_ACCESSURL_NAME = "AccessInformation.AccessURL.Name";
	const SPASE_ND_ACCESSINFORMATION_ACCESSURL_URL = "AccessInformation.AccessURL.URL";
	const SPASE_ND_ACCESSINFORMATION_ACCESSURL_DESCRIPTION = "AccessInformation.AccessURL.Description";
	const SPASE_ND_ACCESSINFORMATION_FORMAT = "AccessInformation.Format";
	const SPASE_ND_ACCESSINFORMATION_DATAEXTENT_QUANTITY = "AccessInformation.DataExtent.Quantity";
  const SPASE_ND_INSTRUMENTID = "InstrumentID";
	const SPASE_ND_PHENOMENONTYPE = "PhenomenonType";
	const SPASE_ND_MEASUREMENTTYPE = "MeasurementType";
  const SPASE_ND_KEYWORD = "Keyword";
	const SPASE_ND_TEMPORALDESCRIPTION_STARTDATE = "TemporalDescription.StartDate";
	const SPASE_ND_TEMPORALDESCRIPTION_STOPDATE = "TemporalDescription.StopDate";
	const SPASE_ND_TEMPORALDESCRIPTION_RELATIVESTOPDATE = "TemporalDescription.RelativeStopDate";
	const SPASE_ND_OBSERVEDREGION = "ObservedRegion";
	const SPASE_ND_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME = "SpatialCoverage.CoordinateSystem.CoordinateSystemName";
	const SPASE_ND_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION = "SpatialCoverage.CoordinateSystem.CoordinateRepresentation";
	const SPASE_ND_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE = "SpatialCoverage.NorthernmostLatitude";
	const SPASE_ND_SPATIALCOVERAGE_SOUTHERNMOSTLATITUDE = "SpatialCoverage.SouthernmostLatitude";
	const SPASE_ND_SPATIALCOVERAGE_EASTERNMOSTLONGITUDE = "SpatialCoverage.EasternmostLongitude";
	const SPASE_ND_SPATIALCOVERAGE_WESTERNMOSTLONGITUDE = "SpatialCoverage.esternmostLongitude";
	const SPASE_ND_SPATIALCOVERAGE_UNIT = "SpatialCoverage.Unit";
	const SPASE_ND_SPATIALCOVERAGE_MINIMUMALTITUDE = "SpatialCoverage.MinimumAltitude";
	const SPASE_ND_SPATIALCOVERAGE_MAXIMUMALTITUDE = "SpatialCoverage.MaximumAltitude";
	const SPASE_ND_SPATIALCOVERAGE_REFERENCE = "SpatialCoverage.Reference";
	const SPASE_ND_PARAMETER_NAME = "Parameter.Name";
	const SPASE_ND_PARAMETER_DESCRIPTION = "Parameter.Description";
	const SPASE_ND_PARAMETER_FIELD_FIELDQUANTITY = "Parameter.Field.FieldQuantity";
	const SPASE_ND_PARAMETER_PARTICLE_PARTICLETYPE = "Parameter.Particle.ParticleType";
	const SPASE_ND_PARAMETER_PARTICLE_PARTICLEQUANTITY = "Parameter.Particle.ParticleQuantity";
	const SPASE_ND_PARAMETER_WAVE_WAVETYPE = "Parameter.Parameter.Wave.WaveType";
	const SPASE_ND_PARAMETER_WAVE_WAVEQUANTITY = "Parameter.Wave.WaveQuantity";
	const SPASE_ND_PARAMETER_MIXED_MIXEDQUANTITY = "Parameter.Mixed.MixedQuantity";
	const SPASE_ND_PARAMETER_SUPPORT_SUPPORTQUANTITY = "Parameter.Support.SupportQuantity";

	//SPASE_DisplayData
	const SPASE_DISPLAYDATA = "DisplayData";
	const SPASE_DISPLAYDATA_RESOURCEID = "DisplayData.ResourceID";
	const SPASE_DISPLAYDATA_RESOURCEHEADER_RESOURCENAME = "DisplayData.ResourceHeader.ResourceName";
	const SPASE_DISPLAYDATA_RESOURCEHEADER_RELEASEDATE = "DisplayData.ResourceHeader.ReleaseDate";
	const SPASE_DISPLAYDATA_RESOURCEHEADER_DESCRIPTION = "DisplayData.ResourceHeader.Description";
	const SPASE_DISPLAYDATA_RESOURCEHEADER_ACKNOWLEDGEMENT = "DisplayData.ResourceHeader.Acknowledgement";
	const SPASE_DISPLAYDATA_RESOURCEHEADER_CONTACT_PERSONID = "DisplayData.ResourceHeader.Contact.PersonID";
	const SPASE_DISPLAYDATA_RESOURCEHEADER_CONTACT_ROLE = "DisplayData.ResourceHeader.Contact.Role";
	const SPASE_DISPLAYDATA_ACCESSINFORMATION_REPOSITORYID = "DisplayData.AccessInformation.RepositoryID";
	const SPASE_DISPLAYDATA_ACCESSINFORMATION_AVAILABILITY = "DisplayData.AccessInformation.Availability";
	const SPASE_DISPLAYDATA_ACCESSINFORMATION_ACCESSRIGHTS = "DisplayData.AccessInformation.AccessRights";
	const SPASE_DISPLAYDATA_ACCESSINFORMATION_ACCESSURL_NAME = "DisplayData.AccessInformation.AccessURL.Name";
	const SPASE_DISPLAYDATA_ACCESSINFORMATION_ACCESSURL_URL = "DisplayData.AccessInformation.AccessURL.URL";
	const SPASE_DISPLAYDATA_ACCESSINFORMATION_ACCESSURL_DESCRIPTION = "DisplayData.AccessInformation.AccessURL.Description";
	const SPASE_DISPLAYDATA_ACCESSINFORMATION_FORMAT = "DisplayData.AccessInformation.Format";
	const SPASE_DISPLAYDATA_ACCESSINFORMATION_DATAEXTENT_QUANTITY = "DisplayData.AccessInformation.DataExtent.Quantity";
  const SPASE_DISPLAYDATA_INSTRUMENTID = "DisplayData.InstrumentID";
	const SPASE_DISPLAYDATA_PHENOMENONTYPE = "DisplayData.PhenomenonType";
	const SPASE_DISPLAYDATA_MEASUREMENTTYPE = "DisplayData.MeasurementType";
  const SPASE_DISPLAYDATA_KEYWORD = "DisplayData.Keyword";
	const SPASE_DISPLAYDATA_TEMPORALDESCRIPTION_STARTDATE = "DisplayData.TemporalDescription.StartDate";
	const SPASE_DISPLAYDATA_TEMPORALDESCRIPTION_STOPDATE = "DisplayData.TemporalDescription.StopDate";
	const SPASE_DISPLAYDATA_TEMPORALDESCRIPTION_RELATIVESTOPDATE = "DisplayData.TemporalDescription.RelativeStopDate";
	const SPASE_DISPLAYDATA_OBSERVEDREGION = "DisplayData.ObservedRegion";
	const SPASE_DISPLAYDATA_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME = "DisplayData.SpatialCoverage.CoordinateSystem.CoordinateSystemName";
	const SPASE_DISPLAYDATA_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION = "DisplayData.SpatialCoverage.CoordinateSystem.CoordinateRepresentation";
	const SPASE_DISPLAYDATA_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE = "DisplayData.SpatialCoverage.NorthernmostLatitude";
	const SPASE_DISPLAYDATA_SPATIALCOVERAGE_SOUTHERNMOSTLATITUDE = "DisplayData.SpatialCoverage.SouthernmostLatitude";
	const SPASE_DISPLAYDATA_SPATIALCOVERAGE_EASTERNMOSTLONGITUDE = "DisplayData.SpatialCoverage.EasternmostLongitude";
	const SPASE_DISPLAYDATA_SPATIALCOVERAGE_WESTERNMOSTLONGITUDE = "DisplayData.SpatialCoverage.esternmostLongitude";
	const SPASE_DISPLAYDATA_SPATIALCOVERAGE_UNIT = "DisplayData.SpatialCoverage.Unit";
	const SPASE_DISPLAYDATA_SPATIALCOVERAGE_MINIMUMALTITUDE = "DisplayData.SpatialCoverage.MinimumAltitude";
	const SPASE_DISPLAYDATA_SPATIALCOVERAGE_MAXIMUMALTITUDE = "DisplayData.SpatialCoverage.MaximumAltitude";
	const SPASE_DISPLAYDATA_SPATIALCOVERAGE_REFERENCE = "DisplayData.SpatialCoverage.Reference";
	const SPASE_DISPLAYDATA_PARAMETER_NAME = "DisplayData.Parameter.Name";
	const SPASE_DISPLAYDATA_PARAMETER_DESCRIPTION = "DisplayData.Parameter.Description";
	const SPASE_DISPLAYDATA_PARAMETER_FIELD_FIELDQUANTITY = "DisplayData.Parameter.Field.FieldQuantity";
	const SPASE_DISPLAYDATA_PARAMETER_PARTICLE_PARTICLETYPE = "DisplayData.Parameter.Particle.ParticleType";
	const SPASE_DISPLAYDATA_PARAMETER_PARTICLE_PARTICLEQUANTITY = "DisplayData.Parameter.Particle.ParticleQuantity";
	const SPASE_DISPLAYDATA_PARAMETER_WAVE_WAVETYPE = "DisplayData.Parameter.Parameter.Wave.WaveType";
	const SPASE_DISPLAYDATA_PARAMETER_WAVE_WAVEQUANTITY = "DisplayData.Parameter.Wave.WaveQuantity";
	const SPASE_DISPLAYDATA_PARAMETER_MIXED_MIXEDQUANTITY = "DisplayData.Parameter.Mixed.MixedQuantity";
	const SPASE_DISPLAYDATA_PARAMETER_SUPPORT_SUPPORTQUANTITY = "DisplayData.Parameter.Support.SupportQuantity";
	//Delete prefix
	const SPASE_DD_RESOURCEID = "ResourceID";
	const SPASE_DD_RESOURCEHEADER_RESOURCENAME = "ResourceHeader.ResourceName";
	const SPASE_DD_RESOURCEHEADER_RELEASEDATE = "ResourceHeader.ReleaseDate";
	const SPASE_DD_RESOURCEHEADER_DESCRIPTION = "ResourceHeader.Description";
	const SPASE_DD_RESOURCEHEADER_ACKNOWLEDGEMENT = "ResourceHeader.Acknowledgement";
	const SPASE_DD_RESOURCEHEADER_CONTACT_PERSONID = "ResourceHeader.Contact.PersonID";
	const SPASE_DD_RESOURCEHEADER_CONTACT_ROLE = "ResourceHeader.Contact.Role";
	const SPASE_DD_ACCESSINFORMATION_REPOSITORYID = "AccessInformation.RepositoryID";
	const SPASE_DD_ACCESSINFORMATION_AVAILABILITY = "AccessInformation.Availability";
	const SPASE_DD_ACCESSINFORMATION_ACCESSRIGHTS = "AccessInformation.AccessRights";
	const SPASE_DD_ACCESSINFORMATION_ACCESSURL_NAME = "AccessInformation.AccessURL.Name";
	const SPASE_DD_ACCESSINFORMATION_ACCESSURL_URL = "AccessInformation.AccessURL.URL";
	const SPASE_DD_ACCESSINFORMATION_ACCESSURL_DESCRIPTION = "AccessInformation.AccessURL.Description";
	const SPASE_DD_ACCESSINFORMATION_FORMAT = "AccessInformation.Format";
	const SPASE_DD_ACCESSINFORMATION_DATAEXTENT_QUANTITY = "AccessInformation.DataExtent.Quantity";
  const SPASE_DD_INSTRUMENTID = "InstrumentID";
	const SPASE_DD_PHENOMENONTYPE = "PhenomenonType";
	const SPASE_DD_MEASUREMENTTYPE = "MeasurementType";
  const SPASE_DD_KEYWORD = "Keyword";
	const SPASE_DD_TEMPORALDESCRIPTION_STARTDATE = "TemporalDescription.StartDate";
	const SPASE_DD_TEMPORALDESCRIPTION_STOPDATE = "TemporalDescription.StopDate";
	const SPASE_DD_TEMPORALDESCRIPTION_RELATIVESTOPDATE = "TemporalDescription.RelativeStopDate";
	const SPASE_DD_OBSERVEDREGION = "ObservedRegion";
	const SPASE_DD_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME = "SpatialCoverage.CoordinateSystem.CoordinateSystemName";
	const SPASE_DD_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION = "SpatialCoverage.CoordinateSystem.CoordinateRepresentation";
	const SPASE_DD_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE = "SpatialCoverage.NorthernmostLatitude";
	const SPASE_DD_SPATIALCOVERAGE_SOUTHERNMOSTLATITUDE = "SpatialCoverage.SouthernmostLatitude";
	const SPASE_DD_SPATIALCOVERAGE_EASTERNMOSTLONGITUDE = "SpatialCoverage.EasternmostLongitude";
	const SPASE_DD_SPATIALCOVERAGE_WESTERNMOSTLONGITUDE = "SpatialCoverage.esternmostLongitude";
	const SPASE_DD_SPATIALCOVERAGE_UNIT = "SpatialCoverage.Unit";
	const SPASE_DD_SPATIALCOVERAGE_MINIMUMALTITUDE = "SpatialCoverage.MinimumAltitude";
	const SPASE_DD_SPATIALCOVERAGE_MAXIMUMALTITUDE = "SpatialCoverage.MaximumAltitude";
	const SPASE_DD_SPATIALCOVERAGE_REFERENCE = "SpatialCoverage.Reference";
	const SPASE_DD_PARAMETER_NAME = "Parameter.Name";
	const SPASE_DD_PARAMETER_DESCRIPTION = "Parameter.Description";
	const SPASE_DD_PARAMETER_FIELD_FIELDQUANTITY = "Parameter.Field.FieldQuantity";
	const SPASE_DD_PARAMETER_PARTICLE_PARTICLETYPE = "Parameter.Particle.ParticleType";
	const SPASE_DD_PARAMETER_PARTICLE_PARTICLEQUANTITY = "Parameter.Particle.ParticleQuantity";
	const SPASE_DD_PARAMETER_WAVE_WAVETYPE = "Parameter.Parameter.Wave.WaveType";
	const SPASE_DD_PARAMETER_WAVE_WAVEQUANTITY = "Parameter.Wave.WaveQuantity";
	const SPASE_DD_PARAMETER_MIXED_MIXEDQUANTITY = "Parameter.Mixed.MixedQuantity";
	const SPASE_DD_PARAMETER_SUPPORT_SUPPORTQUANTITY = "Parameter.Support.SupportQuantity";


	// SPASE_CATALOG
	const SPASE_CATALOG = "Catalog";
	const SPASE_CATALOG_RESOURCEID = "Catalog.ResourceID";
	const SPASE_CATALOG_RESOURCEHEADER_RESOURCENAME = "Catalog.ResourceHeader.ResourceName";
	const SPASE_CATALOG_RESOURCEHEADER_RELEASEDATE = "Catalog.ResourceHeader.ReleaseDate";
	const SPASE_CATALOG_RESOURCEHEADER_DESCRIPTION = "Catalog.ResourceHeader.Description";
	const SPASE_CATALOG_RESOURCEHEADER_ACKNOWLEDGEMENT = "Catalog.ResourceHeader.Acknowledgement";
	const SPASE_CATALOG_RESOURCEHEADER_CONTACT_PERSONID = "Catalog.ResourceHeader.Contact.PersonID";
	const SPASE_CATALOG_RESOURCEHEADER_CONTACT_ROLE = "Catalog.ResourceHeader.Contact.Role";
	const SPASE_CATALOG_ACCESSINFORMATION_REPOSITORYID = "Catalog.AccessInformation.RepositoryID";
	const SPASE_CATALOG_ACCESSINFORMATION_AVAILABILITY = "Catalog.AccessInformation.Availability";
	const SPASE_CATALOG_ACCESSINFORMATION_ACCESSRIGHTS = "Catalog.AccessInformation.AccessRights";
	const SPASE_CATALOG_ACCESSINFORMATION_ACCESSURL_NAME = "Catalog.AccessInformation.AccessURL.Name";
	const SPASE_CATALOG_ACCESSINFORMATION_ACCESSURL_URL = "Catalog.AccessInformation.AccessURL.URL";
	const SPASE_CATALOG_ACCESSINFORMATION_ACCESSURL_DESCRIPTION = "Catalog.AccessInformation.AccessURL.Description";
	const SPASE_CATALOG_ACCESSINFORMATION_FORMAT = "Catalog.AccessInformation.Format";
	const SPASE_CATALOG_ACCESSINFORMATION_DATAEXTENT_QUANTITY = "Catalog.AccessInformation.DataExtent.Quantity";
  const SPASE_CATALOG_INSTRUMENTID = "Catalog.InstrumentID";
	const SPASE_CATALOG_PHENOMENONTYPE = "Catalog.PhenomenonType";
	const SPASE_CATALOG_MEASUREMENTTYPE = "Catalog.MeasurementType";
  const SPASE_CATALOG_KEYWORD = "Catalog.Keyword";
	const SPASE_CATALOG_TEMPORALDESCRIPTION_STARTDATE = "Catalog.TemporalDescription.StartDate";
	const SPASE_CATALOG_TEMPORALDESCRIPTION_STOPDATE = "Catalog.TemporalDescription.StopDate";
	const SPASE_CATALOG_TEMPORALDESCRIPTION_RELATIVESTOPDATE = "Catalog.TemporalDescription.RelativeStopDate";
	const SPASE_CATALOG_OBSERVEDREGION = "Catalog.ObservedRegion";
	const SPASE_CATALOG_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME = "Catalog.SpatialCoverage.CoordinateSystem.CoordinateSystemName";
	const SPASE_CATALOG_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION = "Catalog.SpatialCoverage.CoordinateSystem.CoordinateRepresentation";
	const SPASE_CATALOG_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE = "Catalog.SpatialCoverage.NorthernmostLatitude";
	const SPASE_CATALOG_SPATIALCOVERAGE_SOUTHERNMOSTLATITUDE = "Catalog.SpatialCoverage.SouthernmostLatitude";
	const SPASE_CATALOG_SPATIALCOVERAGE_EASTERNMOSTLONGITUDE = "Catalog.SpatialCoverage.EasternmostLongitude";
	const SPASE_CATALOG_SPATIALCOVERAGE_WESTERNMOSTLONGITUDE = "Catalog.SpatialCoverage.esternmostLongitude";
	const SPASE_CATALOG_SPATIALCOVERAGE_UNIT = "Catalog.SpatialCoverage.Unit";
	const SPASE_CATALOG_SPATIALCOVERAGE_MINIMUMALTITUDE = "Catalog.SpatialCoverage.MinimumAltitude";
	const SPASE_CATALOG_SPATIALCOVERAGE_MAXIMUMALTITUDE = "Catalog.SpatialCoverage.MaximumAltitude";
	const SPASE_CATALOG_SPATIALCOVERAGE_REFERENCE = "Catalog.SpatialCoverage.Reference";
	const SPASE_CATALOG_PARAMETER_NAME = "Catalog.Parameter.Name";
	const SPASE_CATALOG_PARAMETER_DESCRIPTION = "Catalog.Parameter.Description";
	const SPASE_CATALOG_PARAMETER_FIELD_FIELDQUANTITY = "Catalog.Parameter.Field.FieldQuantity";
	const SPASE_CATALOG_PARAMETER_PARTICLE_PARTICLETYPE = "Catalog.Parameter.Particle.ParticleType";
	const SPASE_CATALOG_PARAMETER_PARTICLE_PARTICLEQUANTITY = "Catalog.Parameter.Particle.ParticleQuantity";
	const SPASE_CATALOG_PARAMETER_WAVE_WAVETYPE = "Catalog.Parameter.Parameter.Wave.WaveType";
	const SPASE_CATALOG_PARAMETER_WAVE_WAVEQUANTITY = "Catalog.Parameter.Wave.WaveQuantity";
	const SPASE_CATALOG_PARAMETER_MIXED_MIXEDQUANTITY = "Catalog.Parameter.Mixed.MixedQuantity";
	const SPASE_CATALOG_PARAMETER_SUPPORT_SUPPORTQUANTITY = "Catalog.Parameter.Support.SupportQuantity";
	//Delete Prefix
	const SPASE_C_RESOURCEID = "ResourceID";
	const SPASE_C_RESOURCEHEADER_RESOURCENAME = "ResourceHeader.ResourceName";
	const SPASE_C_RESOURCEHEADER_RELEASEDATE = "ResourceHeader.ReleaseDate";
	const SPASE_C_RESOURCEHEADER_DESCRIPTION = "ResourceHeader.Description";
	const SPASE_C_RESOURCEHEADER_ACKNOWLEDGEMENT = "ResourceHeader.Acknowledgement";
	const SPASE_C_RESOURCEHEADER_CONTACT_PERSONID = "ResourceHeader.Contact.PersonID";
	const SPASE_C_RESOURCEHEADER_CONTACT_ROLE = "ResourceHeader.Contact.Role";
	const SPASE_C_ACCESSINFORMATION_REPOSITORYID = "AccessInformation.RepositoryID";
	const SPASE_C_ACCESSINFORMATION_AVAILABILITY = "AccessInformation.Availability";
	const SPASE_C_ACCESSINFORMATION_ACCESSRIGHTS = "AccessInformation.AccessRights";
	const SPASE_C_ACCESSINFORMATION_ACCESSURL_NAME = "AccessInformation.AccessURL.Name";
	const SPASE_C_ACCESSINFORMATION_ACCESSURL_URL = "AccessInformation.AccessURL.URL";
	const SPASE_C_ACCESSINFORMATION_ACCESSURL_DESCRIPTION = "AccessInformation.AccessURL.Description";
	const SPASE_C_ACCESSINFORMATION_FORMAT = "AccessInformation.Format";
	const SPASE_C_ACCESSINFORMATION_DATAEXTENT_QUANTITY = "AccessInformation.DataExtent.Quantity";
  const SPASE_C_INSTRUMENTID = "InstrumentID";
	const SPASE_C_PHENOMENONTYPE = "PhenomenonType";
	const SPASE_C_MEASUREMENTTYPE = "MeasurementType";
  const SPASE_C_KEYWORD = "Keyword";
	const SPASE_C_TEMPORALDESCRIPTION_STARTDATE = "TemporalDescription.StartDate";
	const SPASE_C_TEMPORALDESCRIPTION_STOPDATE = "TemporalDescription.StopDate";
	const SPASE_C_TEMPORALDESCRIPTION_RELATIVESTOPDATE = "TemporalDescription.RelativeStopDate";
	const SPASE_C_OBSERVEDREGION = "ObservedRegion";
	const SPASE_C_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME = "SpatialCoverage.CoordinateSystem.CoordinateSystemName";
	const SPASE_C_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION = "SpatialCoverage.CoordinateSystem.CoordinateRepresentation";
	const SPASE_C_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE = "SpatialCoverage.NorthernmostLatitude";
	const SPASE_C_SPATIALCOVERAGE_SOUTHERNMOSTLATITUDE = "SpatialCoverage.SouthernmostLatitude";
	const SPASE_C_SPATIALCOVERAGE_EASTERNMOSTLONGITUDE = "SpatialCoverage.EasternmostLongitude";
	const SPASE_C_SPATIALCOVERAGE_WESTERNMOSTLONGITUDE = "SpatialCoverage.esternmostLongitude";
	const SPASE_C_SPATIALCOVERAGE_UNIT = "SpatialCoverage.Unit";
	const SPASE_C_SPATIALCOVERAGE_MINIMUMALTITUDE = "SpatialCoverage.MinimumAltitude";
	const SPASE_C_SPATIALCOVERAGE_MAXIMUMALTITUDE = "SpatialCoverage.MaximumAltitude";
	const SPASE_C_SPATIALCOVERAGE_REFERENCE = "SpatialCoverage.Reference";
	const SPASE_C_PARAMETER_NAME = "Parameter.Name";
	const SPASE_C_PARAMETER_DESCRIPTION = "Parameter.Description";
	const SPASE_C_PARAMETER_FIELD_FIELDQUANTITY = "Parameter.Field.FieldQuantity";
	const SPASE_C_PARAMETER_PARTICLE_PARTICLETYPE = "Parameter.Particle.ParticleType";
	const SPASE_C_PARAMETER_PARTICLE_PARTICLEQUANTITY = "Parameter.Particle.ParticleQuantity";
	const SPASE_C_PARAMETER_WAVE_WAVETYPE = "Parameter.Parameter.Wave.WaveType";
	const SPASE_C_PARAMETER_WAVE_WAVEQUANTITY = "Parameter.Wave.WaveQuantity";
	const SPASE_C_PARAMETER_MIXED_MIXEDQUANTITY = "Parameter.Mixed.MixedQuantity";
	const SPASE_C_PARAMETER_SUPPORT_SUPPORTQUANTITY = "Parameter.Support.SupportQuantity";

	// SPASE_INSTRUMENT
	const SPASE_INSTRUMENT = "Instrument";
	const SPASE_INSTRUMENT_RESOURCEID = "Instrument.ResourceID";
	const SPASE_INSTRUMENT_RESOURCEHEADER_RESOURCENAME = "Instrument.ResourceHeader.ResourceName";
	const SPASE_INSTRUMENT_RESOURCEHEADER_RELEASEDATE = "Instrument.ResourceHeader.ReleaseDate";
	const SPASE_INSTRUMENT_RESOURCEHEADER_DESCRIPTION = "Instrument.ResourceHeader.Description";
	const SPASE_INSTRUMENT_RESOURCEHEADER_CONTACT_PERSONID = "Instrument.ResourceHeader.Contact.PersonID";
	const SPASE_INSTRUMENT_RESOURCEHEADER_CONTACT_ROLE = "Instrument.ResourceHeader.Contact.Role";
	const SPASE_INSTRUMENT_TYPE = "Instrument.Type";
	const SPASE_INSTRUMENT_INSTRUMENTTYPE = "Instrument.InstrumentType";
	const SPASE_INSTRUMENT_INVESTIGATIONNAME = "Instrument.InvestigationName";
	const SPASE_INSTRUMENT_OBSERVATORYID = "Instrument.ObservatoryID";
	//Delete Prefix
	const SPASE_I_RESOURCEID = "ResourceID";
	const SPASE_I_RESOURCEHEADER_RESOURCENAME = "ResourceHeader.ResourceName";
	const SPASE_I_RESOURCEHEADER_RELEASEDATE = "ResourceHeader.ReleaseDate";
	const SPASE_I_RESOURCEHEADER_DESCRIPTION = "ResourceHeader.Description";
	const SPASE_I_RESOURCEHEADER_CONTACT_PERSONID = "ResourceHeader.Contact.PersonID";
	const SPASE_I_RESOURCEHEADER_CONTACT_ROLE = "ResourceHeader.Contact.Role";
	const SPASE_I_TYPE = "Type";
	const SPASE_I_INSTRUMENTTYPE = "InstrumentType";
	const SPASE_I_INVESTIGATIONNAME = "InvestigationName";
	const SPASE_I_OBSERVATORYID = "ObservatoryID";

	// SPASE_OBSERVATORY
	const SPASE_OBSERVATORY = "Observatory";
	const SPASE_OBSERVATORY_RESOURCEID = "Observatory.ResourceID";
	const SPASE_OBSERVATORY_RESOURCEHEADER_RESOURCENAME = "Observatory.ResourceHeader.ResourceName";
	const SPASE_OBSERVATORY_RESOURCEHEADER_RELEASEDATE = "Observatory.ResourceHeader.ReleaseDate";
	const SPASE_OBSERVATORY_RESOURCEHEADER_DESCRIPTION = "Observatory.ResourceHeader.Description";
	const SPASE_OBSERVATORY_RESOURCEHEADER_CONTACT_PERSONID = "Observatory.ResourceHeader.Contact.PersonID";
	const SPASE_OBSERVATORY_RESOURCEHEADER_CONTACT_ROLE = "Observatory.ResourceHeader.Contact.Role";
	const SPASE_OBSERVATORY_LOCATION_OBSERVATORYREGION = "Observatory.Location.ObservatoryRegion";
	const SPASE_OBSERVATORY_LOCATION_COORDINATESYSTEMNAME_LATITUDE = "Observatory.Location.CoordinateSystemname.Latitude";
	const SPASE_OBSERVATORY_LOCATION_COORDINATESYSTEMNAME_LONGITUDE = "Observatory.Location.CoordinateSystemname.Longitude";
	const SPASE_OBSERVATORY_OPERATINGSPAN_STARTDATE = "Observatory.OperatingSpan.StartDate";
	//Delete Prefix
	const SPASE_O_RESOURCEID = "ResourceID";
	const SPASE_O_RESOURCEHEADER_RESOURCENAME = "ResourceHeader.ResourceName";
	const SPASE_O_RESOURCEHEADER_RELEASEDATE = "ResourceHeader.ReleaseDate";
	const SPASE_O_RESOURCEHEADER_DESCRIPTION = "ResourceHeader.Description";
	const SPASE_O_RESOURCEHEADER_CONTACT_PERSONID = "ResourceHeader.Contact.PersonID";
	const SPASE_O_RESOURCEHEADER_CONTACT_ROLE = "ResourceHeader.Contact.Role";
	const SPASE_O_LOCATION_OBSERVATORYREGION = "Location.ObservatoryRegion";
	const SPASE_O_LOCATION_COORDINATESYSTEMNAME_LATITUDE = "Location.CoordinateSystemname.Latitude";
	const SPASE_O_LOCATION_COORDINATESYSTEMNAME_LONGITUDE = "Location.CoordinateSystemname.Longitude";
	const SPASE_O_OPERATINGSPAN_STARTDATE = "OperatingSpan.StartDate";

	// SPASE_PERSON
	const SPASE_PERSON = "Person";
	const SPASE_PERSON_RESOURCEID = "Person.ResourceID";
	const SPASE_PERSON_RELEASEDATE = "Person.ReleaseDate";
	const SPASE_PERSON_PERSONNAME = "Person.PersonName";
	const SPASE_PERSON_ORGANIZATIONNAME = "Person.OrganizationName";
	const SPASE_PERSON_EMAIL = "Person.Email";
	//Delete Prefix
	const SPASE_P_RESOURCEID = "ResourceID";
	const SPASE_P_RELEASEDATE = "ReleaseDate";
	const SPASE_P_PERSONNAME = "PersonName";
	const SPASE_P_ORGANIZATIONNAME = "OrganizationName";
	const SPASE_P_EMAIL = "Email";

	// SPASE_REPOSITORY
	const SPASE_REPOSITORY = "Repository";
	const SPASE_REPOSITORY_RESOURCEID = "Repository.ResourceID";
	const SPASE_REPOSITORY_RESOURCEHEADER_RESOURCENAME = "Repository.ResourceHeader.ResourceName";
	const SPASE_REPOSITORY_RESOURCEHEADER_RELEASEDATE = "Repository.ResourceHeader.ReleaseDate";
	const SPASE_REPOSITORY_RESOURCEHEADER_DESCRIPTION = "Repository.ResourceHeader.Description";
	const SPASE_REPOSITORY_RESOURCEHEADER_CONTACT_PERSONID = "Repository.ResourceHeader.Contact.PersonID";
	const SPASE_REPOSITORY_RESOURCEHEADER_CONTACT_ROLE = "Repository.ResourceHeader.Contact.Role";
	const SPASE_REPOSITORY_ACCESSURL_URL = "Repository.AccessURL.URL";
	//Delete Prefix
	const SPASE_R_RESOURCEID = "ResourceID";
	const SPASE_R_RESOURCEHEADER_RESOURCENAME = "ResourceHeader.ResourceName";
	const SPASE_R_RESOURCEHEADER_RELEASEDATE = "ResourceHeader.ReleaseDate";
	const SPASE_R_RESOURCEHEADER_DESCRIPTION = "ResourceHeader.Description";
	const SPASE_R_RESOURCEHEADER_CONTACT_PERSONID = "ResourceHeader.Contact.PersonID";
	const SPASE_R_RESOURCEHEADER_CONTACT_ROLE = "ResourceHeader.Contact.Role";
	const SPASE_R_ACCESSURL_URL = "AccessURL.URL";

	// SPASE_GRANULE
	const SPASE_GRANULE = "Granule";
	const SPASE_GRANULE_RESOURCEID = "Granule.ResourceID";
	const SPASE_GRANULE_RELEASEDATE = "Granule.ReleaseDate";
	const SPASE_GRANULE_PARENTID = "Granule.ParentID";
	const SPASE_GRANULE_STARTDATE = "Granule.StartDate";
	const SPASE_GRANULE_STOPDATE = "Granule.StopDate";
	const SPASE_GRANULE_SOURCE_SOURCETYPE = "Granule.Source.SourceType";
	const SPASE_GRANULE_SOURCE_URL = "Granule.Source.URL";
	const SPASE_GRANULE_SOURCE_DATAEXTENT_QUANTITY = "Granule.Source.DataExtent.Quantity";
	const SPASE_GRANULE_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME = "Granule.SpatialCoverage.CoordinateSystem.CoordinateSystemName";
	const SPASE_GRANULE_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION = "Granule.SpatialCoverage.CoordinateSystem.CoordinateRepresentation";
	const SPASE_GRANULE_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE = "Granule.SpatialCoverage.NorthernmostLatitude";
	const SPASE_GRANULE_SPATIALCOVERAGE_SOUTHERNMOSTLATITUDE = "Granule.SpatialCoverage.SouthernmostLatitude";
	const SPASE_GRANULE_SPATIALCOVERAGE_EASTERNMOSTLONGITUDE = "Granule.SpatialCoverage.EasternmostLongitude";
	const SPASE_GRANULE_SPATIALCOVERAGE_WESTERNMOSTLONGITUDE = "Granule.SpatialCoverage.WesternmostLongitude";
	const SPASE_GRANULE_SPATIALCOVERAGE_UNIT = "Granule.SpatialCoverage.Unit";
	const SPASE_GRANULE_SPATIALCOVERAGE_MINIMUMALTITUDE = "Granule.SpatialCoverage.MinimumAltitude";
	const SPASE_GRANULE_SPATIALCOVERAGE_MAXIMUMALTITUDE = "Granule.SpatialCoverage.MaximumAltitude";
	const SPASE_GRANULE_SPATIALCOVERAGE_REFERENCE = "Granule.SpatialCoverage.Reference";
	//Delete Prefix
	const SPASE_G_RESOURCEID = "ResourceID";
	const SPASE_G_RELEASEDATE = "ReleaseDate";
	const SPASE_G_PARENTID = "ParentID";
	const SPASE_G_STARTDATE = "StartDate";
	const SPASE_G_STOPDATE = "StopDate";
	const SPASE_G_SOURCE_SOURCETYPE = "Source.SourceType";
	const SPASE_G_SOURCE_URL = "Source.URL";
	const SPSAE_G_SOUCE_DATAEXTENT_QUANTITY = "Source.DataExtent.Quantity";
	const SPASE_G_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME = "SpatialCoverage.CoordinateSystem.CoordinateSystemName";
	const SPASE_G_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION = "SpatialCoverage.CoordinateSystem.CoordinateRepresentation";
	const SPASE_G_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE = "SpatialCoverage.NorthernmostLatitude";
	const SPASE_G_SPATIALCOVERAGE_SOUTHERNMOSTLATITUDE = "SpatialCoverage.SouthernmostLatitude";
	const SPASE_G_SPATIALCOVERAGE_EASTERNMOSTLONGITUDE = "SpatialCoverage.EasternmostLongitude";
	const SPASE_G_SPATIALCOVERAGE_WESTERNMOSTLONGITUDE  = "SpatialCoverage.WesternmostLongitude";
	const SPASE_G_SPATIALCOVERAGE_UNIT = "SpatialCoverage.Unit";
	const SPASE_G_SPATIALCOVERAGE_MINIMUMALTITUDE = "SpatialCoverage.MinimumAltitude";
	const SPASE_G_SPATIALCOVERAGE_MAXIMUMALTITUDE = "SpatialCoverage.MaximumAltitude";
	const SPASE_G_SPATIALCOVERAGE_REFERENCE = "SpatialCoverage.Reference";


	// -------------------------------------------
    // File display_type
    // -------------------------------------------
    const FILE_DISPLAY_TYPE_DETAIL = "0";
    const FILE_DISPLAY_TYPE_SIMPLE = "1";
    const FILE_DISPLAY_TYPE_FLASH = "2";

    // -------------------------------------------
    // File size data
    // -------------------------------------------
    const ITEM_DATA_KEY_FILE_SIZE = "file_size";
    const ITEM_DATA_KEY_FILE_SIZE_FULL = "file_size_full";

    // Mod back from repository_item_type_confirm, icon is deleted 2012/02/15 T.Koyasu -start-
    // -------------------------------------------
    // status of edit icon
    // -------------------------------------------
    // For show icon
    // in Repository_View_Edit_Itemtype_Icon and Repository_Action_Edit_Itemtype_Icon
    const SESSION_PARAM_NEW_ITEM_TYPE = "0";
    const SESSION_PARAM_DEFAULT_ICON = "1";
    const SESSION_PARAM_DATABASE_ICON = "2";
    const SESSION_PARAM_UPLOAD_ICON = "3";
    // Mod back from repository_item_type_confirm, icon is deleted 2012/02/15 T.Koyasu -end-

    // Add tree access control list 2012/02/24 T.Koyasu -start-
    const TREE_DEFAULT_EXCLUSIVE_ACL_ROLE_ROOM = "-1";
    // Add tree access control list 2012/02/24 T.Koyasu -end-

    // -------------------------------------------
    // column name for harvesting
    // -------------------------------------------
    const HARVESTING_COL_HEADER = "HEADER";
    const HARVESTING_COL_IDENTIFIER = "IDENTIFIER";
    const HARVESTING_COL_HEADERIDENTIFIER = "HEADERIDENTIFIER";
    const HARVESTING_COL_DATESTAMP = "DATESTAMP";
    const HARVESTING_COL_SETSPEC = "SETSPEC";
    const HARVESTING_COL_SETNAME = "SETNAME";
    const HARVESTING_COL_STATUS = "STATUS";

    // ----------------------------------------------
    // param name for repository_pdf_cover_patameter
    // ----------------------------------------------
    const PDF_COVER_PARAM_NAME_HEADER_TYPE = "headerType";
    const PDF_COVER_PARAM_NAME_HEADER_TEXT = "headerText";
    const PDF_COVER_PARAM_NAME_HEADER_IMAGE = "headerImage";
    const PDF_COVER_PARAM_NAME_HEADER_ALIGN = "headerAlign";

    // ----------------------------------------------
    // repository_pdf_cover_patameter value
    // ----------------------------------------------
    const PDF_COVER_HEADER_TYPE_TEXT = "text";
    const PDF_COVER_HEADER_TYPE_IMAGE = "image";
    const PDF_COVER_HEADER_ALIGN_RIGHT = "right";
    const PDF_COVER_HEADER_ALIGN_CENTER = "center";
    const PDF_COVER_HEADER_ALIGN_LEFT = "left";

    // ----------------------------------------------
    // domain for usagestatistics
    // ----------------------------------------------
    const USAGESTATISTICS_DOMAIN_UNKNOWN = "unknown";
    const USAGESTATISTICS_DOMAIN_COM = "com";
    const USAGESTATISTICS_DOMAIN_ORG = "org";
    const USAGESTATISTICS_DOMAIN_EDU = "edu";
    const USAGESTATISTICS_DOMAIN_JP = "jp";
    const USAGESTATISTICS_DOMAIN_US = "us";
    const USAGESTATISTICS_DOMAIN_AC_JP = "ac.jp";
    const USAGESTATISTICS_DOMAIN_CO_JP = "co.jp";
    const USAGESTATISTICS_DOMAIN_GO_JP = "go.jp";

    // ----------------------------------------------
    // ffmpeg setting value
    // ----------------------------------------------
    const FFMPEG_DEFAULT_SAMPLING_RATE = "44100";
    const FFMPEG_DEFAULT_BIT_RATE = "600000";

    // ----------------------------------------------
    // column value for repository_log table
    // ----------------------------------------------
    // file_status
    const LOG_FILE_STATUS_UNKNOWN = 0;
    const LOG_FILE_STATUS_OPEN = 1;
    const LOG_FILE_STATUS_CLOSE = -1;
    // site_locense
    const LOG_SITE_LICENSE_OFF = 0;
    const LOG_SITE_LICENSE_ON = 1;
    // input_type
    const LOG_INPUT_TYPE_FILE = 0;
    const LOG_INPUT_TYPE_FILE_PRICE = 1;
    // login_status
    const LOG_LOGIN_STATUS_NO_LOGIN = 0;
    const LOG_LOGIN_STATUS_LOGIN = 1;
    const LOG_LOGIN_STATUS_GROUP = 2;
    const LOG_LOGIN_STATUS_REGISTER = 5;
    const LOG_LOGIN_STATUS_ADMIN = 10;

    // ----------------------------------------------
    // $this->getItemData
    // get item data access Key
    // ----------------------------------------------
    const ITEM_DATA_KEY_ITEM           = 'item';
    const ITEM_DATA_KEY_ITEM_TYPE      = 'item_type';
    const ITEM_DATA_KEY_ITEM_ATTR_TYPE = 'item_attr_type';
    const ITEM_DATA_KEY_ITEM_ATTR      = 'item_attr';
    const ITEM_DATA_KEY_ITEM_REFERENCE = 'reference';
    const ITEM_DATA_KEY_POSITION_INDEX = 'position_index';

    // ----------------------------------------------
    // licence id and licence notation
    // ----------------------------------------------
    const LICENCE_ID_CC_BY = 101;
    const LICENCE_ID_CC_BY_SA = 102;
    const LICENCE_ID_CC_BY_ND = 103;
    const LICENCE_ID_CC_BY_NC = 104;
    const LICENCE_ID_CC_BY_NC_SA = 105;
    const LICENCE_ID_CC_BY_NC_ND = 106;
    const LICENCE_STR_CC_BY = "CC BY";
    const LICENCE_STR_CC_BY_SA = "CC BY-SA";
    const LICENCE_STR_CC_BY_ND = "CC BY-ND";
    const LICENCE_STR_CC_BY_NC = "CC BY-NC";
    const LICENCE_STR_CC_BY_NC_SA = "CC BY-NC-SA";
    const LICENCE_STR_CC_BY_NC_ND = "CC BY-NC-ND";

    // ----------------------------------------------
    // new prefix ID
    // ----------------------------------------------
    const INDEX_JALC_DOI_NOT_SET = 0;
    const INDEX_JALC_DOI_JALC = 1;
    const INDEX_JALC_DOI_CROSS_REF = 2;

    // Bug fix WEKO-2014-031 T.Koyasu 2014/06/25 --start--
    // ----------------------------------------------
    // alternative language on repository_parameter table
    // ----------------------------------------------
    const PARAM_SHOW_ALTERNATIVE_LANG = "1";
    const PARAM_HIDE_ALTERNATIVE_LANG = "0";
    // Bug fix WEKO-2014-031 T.Koyasu 2014/06/25 --end--

    // -------------------------------------------
    // FPDF Header Status
    // -------------------------------------------
    const ALIGN_LEFT = 'L';
    const ALIGN_CENTER = 'C';
    const ALIGN_RIGHT = 'R';
    const ALIGN_CENTERLEFT = 'CL';
}
?>
