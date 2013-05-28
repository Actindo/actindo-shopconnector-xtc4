<?php

/**
 * various utilities for xt/os commerce
 *
 * actindo Faktura/WWS connector
 *
 * @package actindo
 * @author  Patrick Prasse <pprasse@actindo.de>
 * @version $Revision: 491 $
 * @copyright Copyright (c) 2007, Patrick Prasse (Schneebeerenweg 26, D-85551 Kirchheim, GERMANY, pprasse@actindo.de)
*/

function act_get_shop_type( )
{
  return SHOP_TYPE_XTC4;
}


function default_lang( )
{
  global $db, $_actindo_default_lang_cache;
  if( empty($_actindo_default_lang_cache) )
  {
    $res = act_db_query( "SELECT l.`languages_id` FROM ".TABLE_LANGUAGES." AS l WHERE l.`code`=".$db->Quote(_STORE_LANGUAGE) );
    $r = act_db_fetch_array( $res );
    act_db_free( $res );
    $_actindo_default_lang_cache = (int)$r['languages_id'];
  }
  return $_actindo_default_lang_cache;
}


function actindo_get_table_fields( $table )
{
  global $export;

  $cols = array();
  $result = act_db_query( "DESCRIBE $table" );
  while( $row = act_db_fetch_array( $result ) )
  {
    $cols[] = current($row);
  }
  act_db_free( $result );
  return $cols;
}


/*
 * returns information about custom product fields ("Zusatzfelder")
 * see http://webhelp-de.xt-commerce.com/HTML/index.html?zusaetzliche_felder_in_den_pro.htm
 */
function actindo_get_fields() {
	$fieldSets = array(array(
		'id'   => 0,
		'name' => 'Veyton',
	));
	
	$domtypes = array( // custom fields in the db are suffixed with _{type}
		'text' => 'textfield',
		'html' => 'textarea',
		'date' => 'datefield',
	);
	
	$fields = array();
	
	// search _products_description - internationalized fields
	foreach(actindo_get_table_fields(TABLE_PRODUCTS_DESCRIPTION) AS $field) {
		$isCustom = false;
		$domtype = null;
		foreach(array_keys($domtypes) AS $type) {
			if(str_endswith($field, '_'.$type)) {
				$isCustom = true;
				$domtype = $type;
				break;
			}
		}
		if(!$isCustom) {
			// default shop field, skip
			continue;
		}
		
		$field = 'pd_' . $field; // fields can be found in two tables, so names may collide, prefix with "pd" = _Product_Description
		
		$fieldName = explode('_', $field);
		array_pop($fieldName);
		$fieldName = implode('_', $fieldName);
		
		$fields[$field] = array(
			'field_id' => $field,
			'field_name' => $fieldName,
			'field_i18n' => 1,
			'field_set' => 'Veyton',
			'field_set_ids' => array(0),
			'field_help' => '',
			'field_noempty' => 0,
			'field_type' => $domtypes[$domtype],
		);
	}
	
	// search _products - NOT internationalized fields
	foreach(actindo_get_table_fields(TABLE_PRODUCTS) AS $field) {
		$isCustom = false;
		$domtype = null;
		foreach(array_keys($domtypes) AS $type) {
			if(str_endswith($field, '_'.$type)) {
				$isCustom = true;
				$domtype = $type;
				break;
			}
		}
		if(!$isCustom) {
			// default shop field, skip
			continue;
		}
		
		$field = 'p_' . $field; // fields can be found in two tables, so names may collide, prefix with "p" = _Products
		
		$fieldName = explode('_', $field);
		array_pop($fieldName);
		$fieldName = implode('_', $fieldName);
		
		$fields[$field] = array(
			'field_id' => $field,
			'field_name' => $fieldName,
			'field_i18n' => 0,
			'field_set' => 'Veyton',
			'field_set_ids' => array(0),
			'field_help' => '',
			'field_noempty' => 0,
			'field_type' => $domtypes[$domtype],
		);
	}
	
	return array(
		'fields'     => $fields,
		'field_sets' => $fieldSets,
	);
}

function str_endswith( $str, $sub ) {
   return ( substr( $str, strlen( $str ) - strlen( $sub ) ) === $sub );
}


function check_admin_pass( $pass, $login )
{
  global $db;
  $login = trim( $login );

  $res = $db->Execute( $q="SELECT IF(`user_password`=".$db->Quote($pass).", 1, 0) AS okay FROM `".TABLE_ADMIN_ACL_AREA_USER."` WHERE `handle`=".$db->Quote($login) );
  $row = $res->FetchRow();
  if( $row['okay'] > 0 )
    return TRUE;

  return FALSE;
}



function get_language_id_by_code( $code )
{
  global $_language_id_by_code;
  if( !is_array($_language_id_by_code) )
  {
    $_language_id_by_code = array();
    $res = act_db_query( "SELECT languages_id, code FROM ".TABLE_LANGUAGES );
    while( $row = act_db_fetch_array($res) )
      $_language_id_by_code[$row['code']] = (int)$row['languages_id'];
    act_db_free( $res );
  }
  return $_language_id_by_code[$code];
}

function get_language_code_by_id( $languages_id )
{
  global $_language_code_by_id;
  if( !is_array($_language_code_by_id) )
  {
    $_language_code_by_id = array();
    $res = act_db_query( "SELECT languages_id, code FROM ".TABLE_LANGUAGES );
    while( $row = act_db_fetch_array($res) )
      $_language_code_by_id[(int)$row['languages_id']] = $row['code'];
    act_db_free( $res );
  }
  return $_language_code_by_id[(int)$languages_id];
}



function _actindo_get_verf( $payment_modulename )
{
  $payment_modulename = 'MODULE_PAYMENT_'.strtoupper( $payment_modulename ).'_actindo_VERF';
  if( !defined($payment_modulename) )
    return null;
  return constant( $payment_modulename );
}


function act_failsave_db_query( $text )
{
  return mysql_query( $text );
}

function act_db_query( $text )
{
  global $db;
/*
  if( function_exists('xtc_db_query') )
    return xtc_db_query( $text );
  else if( function_exists('tep_db_query') )
    return tep_db_query( $text );
*/
  return $db->Execute( $text );
}

function act_db_free( &$res )
{
  if( !is_object($res) )
    return null;

  return $res->Close( );
}

function act_db_num_rows( &$res )
{
  if( !is_object($res) )
    return null;

  return $res->RecordCount();
}

function act_db_fetch_array( &$res )
{
  if( !is_object($res) )
    return null;

  return $res->FetchRow();
}

function act_db_fetch_assoc( $res )
{
  return act_db_fetch_array( $res );
}

function act_db_fetch_row( $res )
{
  $row = act_db_fetch_array( $res );
  if( !is_array($row) || !count($row) )
    return $row;
  $data = array();
  foreach( $row as $_val )
    $data[] = $_val;
  return $data;
}

function act_db_insert_id( $res=null )
{
  global $db;
  return $db->Insert_ID();
}

function esc( $str )
{
  return mysql_real_escape_string( $str );
}


function act_have_table( $name )
{
  global $act_have_table_cache;
  is_array($act_have_table_cache) or $act_have_table_cache = array();
  if( isset($act_have_table_cache[$name]) )
    return $act_have_table_cache[$name];

  $have=FALSE;
  $res = act_db_query( "SHOW TABLES LIKE '".esc($name)."'" );
  while( $n=act_db_fetch_row($res) )    // get mixed case here, therefore check again
  {
    if( !strcmp( $n[0], $name ) )
    {
      $have=TRUE;
      break;
    }
  }
  act_db_free( $res );
  $act_have_table_cache[$name] = $have;
  return $have;
}

function act_have_column( $tablename, $column )
{
  $have = FALSE;

  $res = act_db_query( "DESCRIBE {$tablename}" );
  while( $row = act_db_fetch_row($res) )
  {
    $have |= ($row[0] == $column);
  }
  act_db_free( $res );

  return $have;
}



function act_get_tax_rate( $class_id )
{
  require_once( SHOP_BASEDIR._SRV_WEB_FRAMEWORK.'classes/class.tax.php' );
  $tax_rate = new tax();
  return $tax_rate->_getTaxRates( $class_id );
}


/**
 * Construct SET statement for INSERT,UPDATE,REPLACE with escaping the data
 *
 * This method also takes care of field names which are in the array but not in
 * the table.
 *
 * @param array Array( 'fieldname'=>'data for field'
 * @param string Table name to read field descriptions from
 * @param boolean Do not escape the data to be inserted (USE WITH GREAT CARE)
 * @param boolean Encode null as NULL? (Normally null is encoded as empty string)
 * @returns array Result array( 'ok'=>TRUE/FALSE, 'set'=> string( 'SET `field1`='data1',...), 'warning'=>string() )
*/
function construct_set( $data, $table, $noescape=FALSE, $encode_null=FALSE )
{
  $fields = array();
  $set = "SET ";
  $warning = "";
  $ok = TRUE;

  $fields = actindo_get_table_fields( $table );

  foreach( $data as $field => $data )
  {
    $field = trim( $field );
    if( !in_array( $field, $fields ) )
    {
      $warning .= "Field $field does not exsist in $table!\n";
      continue;
    }

    if( $encode_null && is_null($data) )
    {
      $set .= "`$field`=NULL,";
      continue;
    }

    if( ! $noescape )
      $data = mysql_real_escape_string( $data );
    $set .= "`$field`='$data',";
  }

  if( substr( $set, strlen($set)-1, 1 ) == ',' )
    $set = substr( $set, 0, strlen($set)-1 );
  return array( "ok" => $ok, "set" => $set, "warning" => $warning );
}



/* ******** admin interface **** */

function actindo_check_config( )
{
}




/**
 * @todo
 */
function actindo_create_temporary_file( $data )
{
  $tmp_name = tempnam( "/tmp", "" );
  if( $tmp_name === FALSE || !is_writable($tmp_name) )
    $tmp_name = tempnam( ini_get('upload_tmp_dir'), "" );
  if( $tmp_name === FALSE || !is_writable($tmp_name) )
    $tmp_name = tempnam( ACTINDO_SHOP_BASEDIR.'/templates_c', "" );   // last resort: try templates_c
  if( $tmp_name === FALSE || !is_writable($tmp_name) )
    return array( 'ok' => FALSE, 'errno' => EIO, 'error' => 'Konnte keine tempöräre Datei anlegen' );
  $written = file_put_contents( $tmp_name, $data );
  if( $written != strlen($data) )
  {
    $ret = array( 'ok' => FALSE, 'errno' => EIO, 'error' => 'Fehler beim schreiben des Bildes in das Dateisystem (Pfad '.var_dump_string($tmp_name).', written='.var_dump_string($written).', filesize='.var_dump_string(@filesize($tmp_name)).')' );
    unlink( $tmp_name );
    return $ret;
  }

  return array( 'ok'=>TRUE, 'file' => $tmp_name );
}



function actindo_get_gender_map( )
{
  $gender = array(
    'm' => 'Herr',
    'f' => 'Frau',
  );
  return $gender;
}



/**
 * actindo ADODB Error Handler. This will be called with the following params
 *
 * @param $dbms         the RDBMS you are connecting to
 * @param $fn           the name of the calling function (in uppercase)
 * @param $errno                the native error number from the database
 * @param $errmsg       the native error msg from the database
 * @param $p1           $fn specific parameter - see below
 * @param $p2           $fn specific parameter - see below
 * @param $thisConn     $current connection object - can be false if no connection object created
 */
function actindo_ADODB_Error_Handler($dbms, $fn, $errno, $errmsg, $p1, $p2, &$thisConnection)
{
  if (error_reporting() == 0) return; // obey @ protocol
  switch($fn) {
          case 'EXECUTE':
                  $sql = $p1;
                  $inputparams = $p2;

                  $s = "$dbms error: [$errno: $errmsg] in $fn(\"$sql\")\n";
                  break;

          case 'PCONNECT':
          case 'CONNECT':
                  $host = $p1;
                  $database = $p2;

                  $s = "$dbms error: [$errno: $errmsg] in $fn($host, '****', '****', $database)\n";
                  break;
          default:
                  $s = "$dbms error: [$errno: $errmsg] in $fn($p1, $p2)\n";
                  break;
  }

  $t = date('Y-m-d H:i:s');
  trigger_error("ADODB_ERROR ($t) $s", E_USER_WARNING);
}



function actindo_do_checksums( $subdirectory='', $pattern='*', $checksum_type='MD5', $recursive=TRUE )
{
  $path = add_last_slash( ACTINDO_SHOP_BASEDIR ).$subdirectory;
  if( is_file($path) )
  {
    $files_arr = array( $subdirectory => _checksum_file( $path, $checksum_type ) );
  }
  else
  {
    $files_arr = array();
    $files_arr_2 = _checksum_dir( $path, $pattern, $checksum_type, $recursive );
    foreach( $files_arr_2 as $_fn => $_cs )
    {
      $_fn = substr( $_fn, strlen($path) );
      $files_arr[$_fn] = $_cs;
    }
  }

  $conn_relative_dir = 'plugins/ac_actindo/';

  foreach( array_keys($files_arr) as $fn )
  {
    if( strpos($fn, $conn_relative_dir) === 0 )
    {
      $fn1 = strtr( $fn, array($conn_relative_dir => 'SHOPCONN-'.constant('ACTINDO_PROTOCOL_REVISION').'/') );
      $files_arr[$fn1] = $files_arr[$fn];
      unset( $files_arr[$fn] );
    }
  }


  return array( 'ok'=>TRUE, 'basedir'=>$path, 'files'=>$files_arr );
}

function _checksum_dir( $dirname, $pattern, $checksum_type, $recursive )
{
  $dirs = array();
  $files = array();

  $dir = opendir( $dirname );
  if( !is_resource($dir) )
    return FALSE;

  while( $fn = readdir($dir) )
  {
    if( $fn == '.' || $fn == '..' )
      continue;

    if( $fn == 'templates_c' )
      continue;

    $basename = $fn;
    $fn = add_last_slash($dirname).$fn;

    if( is_dir($fn) )
      $dirs[] = $fn;
    else if( is_file($fn) && (!function_exists('fnmatch') || fnmatch($pattern, $basename)) )
    {
      $files[$fn] = _checksum_file( $fn, $checksum_type );
    }
  }
  closedir( $dir );

  if( $recursive && count($dirs) )
  {
    foreach( $dirs as $_dir )
    {
      $files = array_merge( $files, _checksum_dir($_dir, $pattern, $checksum_type, $recursive) );
    }
  }

  return $files;
}


function _checksum_file( $fn, $checksum_type='MD5' )
{
  if( !is_readable($fn) )
  {
    return 'UNREADABLE';
  }

  if( empty($checksum_type) )
    return 'NO-CHECKSUM-TYPE';

  if( $checksum_type == 'FILESIZE' )
    return filesize( $fn );

  $data = file_get_contents( $fn );
  if( $checksum_type == 'MD5' )
  {
    $data = md5( $data );
  }
  else if( $checksum_type == 'SHA1' )
  {
    $data = sha1( $data );
  }
  else if( $checksum_type == 'MD5-TRIM' )
  {
    $data = strtr( $data, array("\r"=>"", "\n"=>"", "\t"=>"", " "=>"") );
    $data = md5( trim($data) );
  }
  else if( $checksum_type == 'SHA1-TRIM' )
  {
    $data = strtr( $data, array("\r"=>"", "\n"=>"", "\t"=>"", " "=>"") );
    $data = sha1( trim($data) );
  }
  else if( $checksum_type == 'SIZE' )
  {
    $data = strlen( $data );
  }
  else if( $checksum_type == 'SIZE-TRIM' )
  {
    $data = strtr( $data, array("\r"=>"", "\n"=>"", "\t"=>"", " "=>"") );
    $data = strlen( trim($data) );
  }
  return $data;
}

?>