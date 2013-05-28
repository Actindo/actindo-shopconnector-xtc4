<?php

/**
 * include various files
 **
 * actindo Faktura/WWS connector
 *
 * @package actindo
 * @author  Patrick Prasse <pprasse@actindo.de>
 * @version $Revision: 516 $
 * @copyright Copyright (c) 2007, Patrick Prasse (Schneebeerenweg 26, D-85551 Kirchheim, GERMANY, pprasse@actindo.de)
*/

define( 'ACTINDO_SHOPCONN_REVISION', '$Revision: 516 $' );
define( 'ACTINDO_PROTOCOL_REVISION', '2.'.substr( ACTINDO_SHOPCONN_REVISION, 11, -2 ) );


/* define some shop constants */
define( 'SESSION_FORCE_COOKIE_USE', 0 );
define( 'SESSION_CHECK_SSL_SESSION_ID', 0 );
define( 'SESSION_CHECK_USER_AGENT', 0 );
define( 'SESSION_CHECK_IP_ADDRESS', 0 );
define( 'DB_CACHE', 'false' );

/* actindo extensions */
define( 'SUPPRESS_REDIRECT', 1 );       // for application_top.php
define( 'SUPPRESS_UPLOAD_CHECKS', 1 );  // for upload.php
define( 'SUPPRESS_DIE', 1 );            // for database.php, xtc_db_error


/* change dir into admin interface and include application_top.php */
if( !strlen($wd) || !strlen($dwd) || $dwd == '/' || !is_file($dwd.'/xtFramework/admin/main.php') )
{
  $wd = $_SERVER['SCRIPT_FILENAME'];
  $dwd = realpath( dirname($wd).'/../../' );
  if( $dwd === FALSE )
    $dwd = dirname( dirname( dirname($wd) ) );
}
if( !strlen($wd) || !strlen($dwd) || $dwd == '/' || !is_file($dwd.'/xtFramework/admin/main.php') )
{
  $wd = $_SERVER['ORIG_SCRIPT_FILENAME'];
  $dwd = realpath( dirname($wd).'/../../' );
  if( $dwd === FALSE )
    $dwd = dirname( dirname( dirname($wd) ) );
}
if( !strlen($wd) || !strlen($dwd) || $dwd == '/' || !is_file($dwd.'/xtFramework/admin/main.php') )
{
  $wd = trim( $_SERVER['PATH_TRANSLATED'] );
  $dwd = realpath( dirname($wd).'/../../' );
  if( $dwd === FALSE )
    $dwd = dirname( dirname( dirname($wd) ) );
}

define( 'ACTINDO_SHOP_BASEDIR', $dwd );

define( 'ACTINDO_SHOP_CHARSET', 'UTF-8' );

if( !chdir($p=$dwd.'/xtCore/') )
  _actindo_report_init_error( 14, "Error while chdir to &#39;{$p}&#39;" );

/*
if( !is_readable($f=$dwd.'/xtFramework/admin/main.php') )
  _actindo_report_init_error( 14, 'file '.$f.' does not exist' );
require_once( $f );
*/

define('ADODB_ERROR_HANDLER', 'actindo_ADODB_Error_Handler');

$funcs = array(
  'xtFramework/admin/main.php',
  'xtFramework/admin/functions.inc.php',
  'xtFramework/admin/classes/getAdminDropdownData.php'
);
foreach( $funcs as $filename )
{
  if( !is_readable($f=$dwd.'/'.$filename) )
    _actindo_report_init_error( 14, 'file '.$f.' does not exist' );
  require_once( $f );
}

define( 'SHOP_BASEDIR', $dwd.'/' );

error_reporting( E_ALL & ~E_NOTICE );
set_error_handler( 'actindo_error_handler' );


require_once( 'util.php' );
require_once( 'compat.php' );

require_once( 'import.php' );
require_once( 'export.php' );



function categories_get( $params )
{
  if( !parse_args($params,$ret) )
    return $ret;

  ob_start();
  $cat = call_user_func_array( 'export_categories', $params );
  if( !is_array($cat) )
    return xmlrpc_error( EINVAL );
  if( !count($cat) )
    return xmlrpc_error( ENOENT );

  return resp( array( 'ok' => TRUE, 'categories' => $cat ) );
}

function category_action( $params )
{
  if( !parse_args($params,$ret) )
    return $ret;

  require_once( SHOP_BASEDIR._SRV_WEB_FRAMEWORK.'classes/class.MediaFileTypes.php' );
  require_once( SHOP_BASEDIR._SRV_WEB_FRAMEWORK.'classes/class.MediaData.php' );
  require_once( SHOP_BASEDIR._SRV_WEB_FRAMEWORK.'classes/class.MediaImages.php' );
  require_once( SHOP_BASEDIR._SRV_WEB_FRAMEWORK.'admin/classes/class.adminDB_DataSave.php' );
  require_once( SHOP_BASEDIR._SRV_WEB_FRAMEWORK.'classes/class.category.php' );

  $catfunc = new category();
  $catfunc->_setAdmin();

  global $export;
  $default_lang = default_lang();

  list( $point, $id, $pid, $aid, $data ) = $params;

  if( $point == 'add' )
  {
    $sort_order = null;
    if( $aid )
    {
      $res = act_db_query( "SELECT `sort_order` FROM ".TABLE_CATEGORIES." WHERE `categories_id`=".(int)$aid." AND `parent_id`=".(int)$pid );
      $row = act_db_fetch_array( $res );
      if( is_array($row) )
        $sort_order = (int)$row['sort_order'];
      act_db_free( $res );
    }

    if( !is_null($sort_order) )
    {
      $res = act_db_query( "UPDATE ".TABLE_CATEGORIES." SET `sort_order`=`sort_order`+1 WHERE `sort_order`>".(int)$sort_order." AND `parent_id`=".(int)$pid );
      if( !$res )
        return xmlrpc_error( EIO, 'Datenbank-Fehler beim verschieben der Kategorie' );
      $sort_order++;
    }


    $category_data = $category_data1 = array(
//      'id' => $id,
      'sort_order' => (int)$sort_order,
      'categories_status' => 1,
      'permission_id' => 0,
      'categories_owner' => 0,
      'categories_image' => '',
      'products_sorting' => 'p.products_sort',
      'products_sorting2'=> 'ASC',
      'categories_template' => '',
      'listing_template' => '',
      'parent_id' => $pid,
    );
    $default_lang = default_lang();
    foreach( array_keys(export_shop_languages()) as $_lang_id )
    {
      $langcode = get_language_code_by_id( $_lang_id );
      $desc = isset($data['description'][$_lang_id]) ? $data['description'][$_lang_id] : $data['description'][$default_lang];
      $category_data['categories_name_'.$langcode] = $desc['name'];
      $category_data['categories_heading_title'.$langcode] = isset($desc['title']) ? $desc['title'] : $desc['name'];
      if( isset($desc['description']) )
        $category_data['categories_description'.$langcode] = $desc['description'];
      if( isset($desc['meta_title']) )
        $category_data['categories_meta_title'.$langcode] = $desc['meta_title'];
      if( isset($desc['meta_description']) )
        $category_data['categories_meta_description'.$langcode] = $desc['meta_description'];
      if( isset($desc['meta_keywords']) )
        $category_data['categories_meta_keywords'.$langcode] = $desc['meta_keywords'];
      $category_data['url_text_'.$langcode] = !empty($desc['url_text']) ? $desc['url_text'] : $desc['name'];
      $category_data1['url_text_'.$langcode] = $category_data['url_text_'.$langcode];
    }


    $res = $catfunc->_set( $category_data, 'new' );
    if( $res->failed )
      return resp( array( 'ok'=>FALSE, 'errno'=>EUNKNOWN, 'error'=>'Fehler beim speichern der Kategorie mit category::_set' ) );
    $categories_id = $res->new_id;

    $category_data1['categories_id'] = $categories_id;

    // to rebuild SEO URL, do not remove this
    $res = $catfunc->_set( $category_data1, 'edit' );
    if( $res->failed )
      return resp( array( 'ok'=>FALSE, 'errno'=>EUNKNOWN, 'error'=>'Fehler beim speichern der Kategorie mit category::_set' ) );

    return resp( array('ok' => TRUE, 'id'=>(int)$categories_id) );
  }
  else if( $point == 'delete' )
  {
    $res = $catfunc->_unset( $id );
    if( !$res->success )
      return resp( array( 'ok'=>FALSE, 'errno'=>EUNKNOWN, 'error'=>'Fehler beim löschen der Kategorie mit category::_unset' ) );

    return resp( array('ok' => TRUE) );
  }
  else if( $point == 'above' || $point == 'below' || $point == 'append' )
  {
    $catInfo = act_db_fetch_array(act_db_query(sprintf('SELECT `categories_status` FROM `%s` WHERE `categories_id` = %d', TABLE_CATEGORIES, $id)));
    $category_data = array(
      'categories_id' => $id,
      'parent_id' => $pid,
      'categories_status' => empty($catInfo) ? 1 : $catInfo['categories_status'],
    );

    $sort_order = null;
    if( $aid )
    {
      $res = act_db_query( "SELECT `sort_order` FROM ".TABLE_CATEGORIES." WHERE `categories_id`=".(int)$aid." AND `parent_id`=".(int)$pid );
      $row = act_db_fetch_array( $res );
      if( is_array($row) )
        $sort_order = (int)$row['sort_order'];
      act_db_free( $res );
    }
    else if( $point == 'above' )
      $sort_order = -1;

    if( !is_null($sort_order) )
    {
      $res = act_db_query( "UPDATE ".TABLE_CATEGORIES." SET `sort_order`=`sort_order`+1 WHERE `sort_order`>".(int)$sort_order." AND `parent_id`=".(int)$pid );
      if( !$res )
        return xmlrpc_error( EIO, 'Datenbank-Fehler beim verschieben der Kategorie' );
      $sort_order++;
      $category_data['sort_order'] = $sort_order;
    }

    $res = $catfunc->_set( $category_data, 'edit' );
    if( $res->failed )
      return resp( array( 'ok'=>FALSE, 'errno'=>EUNKNOWN, 'error'=>'Fehler beim speichern der Kategorie mit category::_set' ) );

    return resp( array('ok' => TRUE) );
  }
  else if( $point == 'textchange' )
  {
    $res = TRUE;
    
    // to prevent a cat from resetting other fields we need to fill the data array with all the existing values (will be passed to _set() further down)
    $raw = $catfunc->_get($id);
    $category_data = array_shift($raw->data);
    
    foreach( array_keys(export_shop_languages()) as $_lang_id )
    {
      if( !isset($data['description'][$_lang_id]) )
        continue;

      $langcode = get_language_code_by_id( $_lang_id );
      $desc = $data['description'][$_lang_id];

      $category_data['categories_name_'.$langcode] = $desc['name'];
      $category_data['categories_heading_title'.$langcode] = isset($desc['title']) ? $desc['title'] : $desc['name'];
      if( isset($desc['description']) )
        $category_data['categories_description_'.$langcode] = $desc['description'];
      if( isset($desc['meta_title']) )
        $category_data['categories_meta_title_'.$langcode] = $desc['meta_title'];
      if( isset($desc['meta_description']) )
        $category_data['categories_meta_description_'.$langcode] = $desc['meta_description'];
      if( isset($desc['meta_keywords']) )
        $category_data['categories_meta_keywords_'.$langcode] = $desc['meta_keywords'];
      $category_data['url_text_'.$langcode] = !empty($desc['url_text']) ? $desc['url_text'] : $desc['name'];
    }

    // to rebuild SEO URL, do not remove this
    $res = $catfunc->_set( $category_data, 'edit' );
    if( $res->failed )
      return resp( array( 'ok'=>FALSE, 'errno'=>EUNKNOWN, 'error'=>'Fehler beim speichern der Kategorie mit category::_set' ) );

    return resp( array('ok' => TRUE) );
  }

  return resp( array('ok' => TRUE) );
}



/**
 * @done
 */
function settings_get( $params )
{
  if( !parse_args($params,$ret) )
    return $ret;

  ob_start();
  $settings = call_user_func_array( 'export_shop_settings', $params );
  if( !is_array($settings) )
    return xmlrpc_error( EINVAL );
  if( !count($settings) )
    return xmlrpc_error( ENOENT );

  return resp( array( 'ok' => TRUE, 'settings' => $settings ) );
}


/**
 * @done
 */
function product_count( $params )
{
  if( !parse_args($params,$ret) )
    return $ret;

  ob_start();
  $count = call_user_func_array( 'export_products_count', $params );
  if( !is_array($count) )
    return xmlrpc_error( EINVAL );

  return resp( array('ok'=>TRUE, 'count'=>$count) );
}


/**
 * @done
 */
function product_get( $params )
{
  if( !parse_args($params,$ret) )
    return $ret;

  ob_start();
  if( !$params[3] )
    $prod = call_user_func_array( 'export_products', $params );
  else
    $prod = call_user_func_array( 'export_products_list', $params );
  if( !$prod['ok'] )
    return xmlrpc_error( $prod['errno'], $prod['error'] );

  return resp( $prod );
}


/**
 * @done
 */
function product_create_update( $params )
{
  if( !parse_args($params,$ret) )
    return $ret;

  ob_start();
  $res = call_user_func_array( 'import_product', $params );
  if( !$res['ok'] )
    return xmlrpc_error( $res['errno'], $res['error'] );

  return resp( $res );
}


/**
 * @done
 */
function product_update_stock( $params )
{
  if( !parse_args($params,$ret) )
    return $ret;

  ob_start();
  $res = call_user_func_array( 'import_product_stock', $params );
  if( !$res['ok'] )
    return xmlrpc_error( $res['errno'], $res['error'] );

  return resp( $res );
}


/**
 * @done
 */
function product_delete( $params )
{
  if( !parse_args($params,$ret) )
    return $ret;

  ob_start();
  $res = call_user_func_array( 'import_delete_product', $params );
  if( !$res['ok'] )
    return xmlrpc_error( $res['errno'], $res['error'] );

  return resp( $res );
}


/**
 * @done
 */
function orders_count( $params )
{
  if( !parse_args($params,$ret) )
    return $ret;

  ob_start();
  return resp( call_user_func_array('export_orders_count', $params) );
}


/**
 * @done
 */
function orders_list( $params )
{
  if( !parse_args($params,$ret) )
    return $ret;

  ob_start();
  return resp( call_user_func_array('export_orders_list', $params) );
}


/**
 * @done
 */
function orders_list_positions( $params )
{
  if( !parse_args($params,$ret) )
    return $ret;

  ob_start();
  return resp( call_user_func_array('export_orders_positions', $params) );
}


/**
 * @done
 */
function orders_set_status( $params )
{
  if( !parse_args($params,$ret) )
    return $ret;

  ob_start();
  $res = call_user_func_array( 'import_orders_set_status', $params );

  return resp( $res );
}


/**
 * @done
 * @todo xt:C does not yet support this
 */
function orders_set_trackingcode( $params )
{
  if( !parse_args($params,$ret) )
    return $ret;

  return xmlrpc_error( ENOSYS );
}


/**
 * @done
 */
function customer_set_deb_kred_id( $params )
{
  if( !parse_args($params,$ret) )
    return $ret;

  ob_start();
  $res = call_user_func_array( 'import_customer_set_deb_kred_id', $params );

  return resp( $res );
}


/**
 * @done
 */
function customers_count($params)
{
  if( !parse_args($params,$ret) )
    return $ret;

  ob_start();
  return resp( call_user_func_array('export_customers_count', $params) );
}


/**
 * @done
 */
function customers_list( $params )
{
  if( !parse_args($params,$ret) )
    return $ret;

  ob_start();
  return resp( call_user_func_array('export_customers_list', $params) );
}


/**
 * @done
 */
function actindo_set_token( $params )
{
  if( !parse_args($params,$ret) )
    return $ret;

  actindo_check_config();
  if( empty($params[0]) || empty($params[1]) || empty($params[2]) )
    return xmlrpc_error( EINVAL, 'Invalid parameters' );

  $res = act_db_query( "UPDATE ".TABLE_PLUGIN_CONFIGURATION." SET `config_value`='".esc($params[0])."', last_modified=NOW() WHERE `config_key`='ACTINDO_MAND_ID'" );
  $res &= act_db_query( "UPDATE ".TABLE_PLUGIN_CONFIGURATION." SET `config_value`='".esc($params[1])."', last_modified=NOW() WHERE `config_key`='ACTINDO_USERNAME'" );
  $res &= act_db_query( "UPDATE ".TABLE_PLUGIN_CONFIGURATION." SET `config_value`='".esc($params[2])."', last_modified=NOW() WHERE `config_key`='ACTINDO_TOKEN'" );
  $res &= act_db_query( "UPDATE ".TABLE_PLUGIN_CONFIGURATION." SET `config_value`='', last_modified=NOW() WHERE `config_key`='ACTINDO_SID'" );
//  $res &= act_db_query( "UPDATE ".TABLE_PLUGIN_CONFIGURATION." SET `config_value`='true' WHERE `config_key`='ACTINDO_ACTIVE'" );
  if( !$res )
    return xmlrpc_error( EIO, 'Error inserting into DB' );

  return resp( array('ok'=>TRUE) );
}


/**
 * @done
 */
function actindo_get_time( $params )
{
  if( !parse_args($params,$ret) )
    return $ret;

  $res = act_db_query( "SHOW VARIABLES LIKE 'version'" );
  $v_db = act_db_fetch_array( $res );
  act_db_free( $res );

  $res = act_db_query( "SELECT NOW() as datetime" );
  $time_database = act_db_fetch_array( $res );
  act_db_free( $res );

  if( version_compare($v_db['Value'], "4.1.1") > 0 )
  {
    $res = act_db_query( "SELECT UTC_TIMESTAMP() as datetime" );
    $utctime_database = act_db_fetch_array( $res );
    act_db_free( $res );
  }
  else
  {
    // we hope that utctime_database is the same as gmtime-server
    $utctime_database = array( 'datetime'=> '' );
  }


  $arr = array(
    'time_server' => date( 'Y-m-d H:i:s' ),
    'gmtime_server' => gmdate( 'Y-m-d H:i:s' ),
    'time_database' => $time_database['datetime'],
    'gmtime_database' => $utctime_database['datetime'],
  );

  if( !empty($arr['gmtime_database']) )
  {
    $diff = strtotime( $arr['time_database'] ) - strtotime( $arr['gmtime_database'] );
  }
  else
  {
    $diff = strtotime( $arr['time_server'] ) - strtotime( $arr['gmtime_server'] );
  }
  $arr['diff_seconds'] = $diff;
  $diff_neg = $diff < 0;
  $diff = abs( $diff );
  $arr['diff'] = ($diff_neg ? '-':'').sprintf( "%02d:%02d:%02d", floor($diff / 3600), floor( ($diff % 3600) / 60 ), $diff % 60 );

  return resp( $arr );
}


/**
 * @done
 */
function shop_get_connector_version( &$arr, $params )
{
  $revision = '$Revision: 516 $';
  $arr = array(
    'revision' => $revision,
    'protocol_version' => '2.'.substr( $revision, 11, -2 ),
    'shop_type' => act_get_shop_type( ),
    'shop_version' => _SYSTEM_VERSION,
    'default_charset' => 'UTF-8',
    'capabilities' => act_shop_get_capabilities(),
  );
}


/**
 * @done
 */
function act_shop_get_capabilities()
{
  $is_xtcommerce = act_shop_is( SHOP_TYPE_XTCOMMERCE ) ? 1 : 0;
  return array(
    'artikel_vpe' => $is_xtcommerce,
    'artikel_shippingtime' =>1,
    'artikel_properties' => 1,
    'artikel_property_sets' => 1,
    'artikel_contents' => 1,
    'artikel_attributsartikel' => 1,    // Attributs-Kombinationen werden tatsächlich eigene Artikel
    'wg_sync' => 1,
    'multi_livelager' => 1,
//    'artikel_list_filters' => 1,  // ????
  );
}


?>