<?php

/**
 * export settings
 *
 * actindo Faktura/WWS connector
 *
 * @package actindo
 * @author Daniel Haimerl <haimerl@actindo.de>
 * @version $Revision: 460 $
 * @copyright Copyright (c) 2007, Daniel Haimerl (Schneebeerenweg 26, D-85551 Kirchheim, GERMANY, haimerl@actindo.de)
*/

require_once( 'export_customers.php' );
require_once( 'export_orders.php' );
require_once( 'export_products.php' );


function export_shop_languages( )
{
  global $language;

  $lang = array();
  foreach( $language->_getLanguageList() as $key => $val )
  {
    $lang[(int)$val['languages_id']] = array(
      "language_id" => (int)$val['languages_id'],
      "language_name" => $val['name'],
      'language_code' => $val['code'],
      'is_default' => $val['code'] == _STORE_LANGUAGE,
    );
  }
  return $lang;
}


function export_customers_status( )
{
  global $__actindo_customers_status_cache;

  if( !isset($__actindo_customers_status_cache) )
  {
    $status = array();
    $res = act_db_query( "SELECT cs.*, csd.`language_code`, csd.`customers_status_name` FROM ".TABLE_CUSTOMERS_STATUS." AS cs, ".TABLE_CUSTOMERS_STATUS_DESCRIPTION." AS csd WHERE cs.customers_status_id=csd.customers_status_id" );
    while( $val = act_db_fetch_array( $res ) )
    {
      if( !isset($status[(int)$val['customers_status_id']]) )
      {
        $val1 = $val;
        unset( $val1['customers_status_name'] );
        $status[(int)$val['customers_status_id']] = $val1;
      }
      $status[(int)$val['customers_status_id']]['customers_status_name'][get_language_id_by_code( $val['language_code'] )] = $val['customers_status_name'];
    }
    act_db_free( $res );
    $__actindo_customers_status_cache = $status;
  }

  return $__actindo_customers_status_cache;
}


function export_multistores( )
{
  global $__actindo_multistores_cache;

  if( !isset($__actindo_multistores_cache) )
  {
    $stores = array();
    if( !act_have_table(TABLE_MANDANT_CONFIG) )
    {
      $stores[1] = array(
        'id' => 1,
        'name' => 'Main Store',
        'active' => 1
      );
    }
    else
    {
      $res = act_db_query( "SELECT * FROM ".TABLE_MANDANT_CONFIG );
      while( $val = act_db_fetch_array($res) )
      {
        $stores[(int)$val['shop_id']] = array(
          'id' => (int)$val['shop_id'],
          'name' => $val['shop_title'],
          'url_http' => $val['shop_http'],
          'url_https' => $val['shop_https'],
          'active' => $val['shop_status'],
        );
      }
      act_db_free( $res );
    }
    $__actindo_multistores_cache = $stores;
  }

  return $__actindo_multistores_cache;
}


function export_xsell_groups( )
{
  $grps = array();
  $res = act_db_query( "SELECT * FROM ".TABLE_PRODUCTS_XSELL_GROUPS );
  while( $val = act_db_fetch_array( $res ) )
  {
    if( !isset($grps[(int)$val['products_xsell_grp_name_id']]) )
    {
      $val1 = $val;
      unset( $val1['groupname'] );
      unset( $val1['language_id'] );
      $grps[(int)$val['products_xsell_grp_name_id']] = $val1;
    }
    $grps[(int)$val['products_xsell_grp_name_id']]['groupname'][(int)$val['language_id']] = $val['groupname'];
  }
  act_db_free( $res );
  return $grps;
}

function export_shop_settings()
{
  global $template;
  $template = new template();

  $ss = new system_status();

  $ret = array();
  $ret['languages'] = export_shop_languages();
  $default_langid = default_lang();
  $default_langcode = get_language_code_by_id( default_lang() );

  $ret['vpe'] = array();
  foreach( $ss->values['base_price'] as $val )
  {
    $ret['vpe'][$val['id']][$default_langid] = array( 'products_vpe' => $val['id'], 'vpe_name' => $val['name'] );
  }

  $res = act_db_query( "select manufacturers_id, manufacturers_name from ".TABLE_MANUFACTURERS." order by manufacturers_name" );
  while( $val = act_db_fetch_array( $res ) )
  {
    $ret['manufacturers'][] = array(
      "manufacturers_id" => $val['manufacturers_id'],
      "manufacturers_name" => $val['manufacturers_name']
    );
  }
  act_db_free( $res );


  $ret['shipping'] = array();
  foreach( $ss->values['shipping_status'] as $val )
  {
    $ret['shipping'][] = array( 'id' => $val['id'], 'text' => $val['name'] );
  }


  // do not comment this out as it's needed by veyton!!!
  $_POST['query'] = '';

//  var_dump( _SRV_WEBROOT.'templates/'.$template->selected_template.'/' );
  $ret['info_template'] = array ();
  foreach( get_dropdown_data_non_json('product_template') as $tl )
  {
    $ret['info_template'][] = array ('id' => $tl['id'], 'text' => $tl['name'] );
  }

  $ret['options_template'] = array ();
  foreach( get_dropdown_data_non_json('products_option_template') as $tl )
  {
    $ret['options_template'][] = array ('id' => $tl['id'], 'text' => $tl['name'] );
  }

  $ret['options_list_template'] = array ();
  foreach( get_dropdown_data_non_json('products_option_list_template') as $tl )
  {
    $ret['options_list_template'][] = array ('id' => $tl['id'], 'text' => $tl['name'] );
  }

  /* Template für Liste der SlaveArtikel */
//  var_dump(get_dropdown_data_non_json('products_option_list_template'));

  /* Template für Artikeloptionen */
//  var_dump(get_dropdown_data_non_json('products_option_template'));

//  var_dump($ret['info_template'], $ret['options_template'], $ret['options_list_template'] );

  $ret['orders_status'] = array();
  foreach( $ss->values['order_status'] as $val )
  {
    $ret['orders_status'][$val['id']][default_lang()] = $val['name'];
  }

  $ret['customers_status'] = export_customers_status( );
/*
    $ret['xsell_groups'] = export_xsell_groups( );
*/

  $ret['multistores'] = export_multistores();

  $ret['installed_payment_modules'] = array();
  $res = act_db_query( "SELECT p.payment_id, p.payment_code, p.status, pd.payment_name FROM ".TABLE_PAYMENT." AS p LEFT JOIN ".TABLE_PAYMENT_DESCRIPTION." AS pd ON(pd.payment_id=p.payment_id AND pd.language_code='".esc($default_langcode)."') ORDER BY p.payment_code" );
  while( $row = act_db_fetch_array($res) )
  {
    $ret['installed_payment_modules'][$row['payment_code']] = array(
      'id' => (int)$row['payment_id'],
      'code' => $row['payment_code'],
      'active' => (int)$row['status'],
      'name' => $row['payment_name']
    );
  }
  act_db_free( $res );

  $ret['installed_shipping_modules'] = array();
  $res = act_db_query( "SELECT p.shipping_id, p.shipping_code, p.status, pd.shipping_name FROM ".TABLE_SHIPPING." AS p LEFT JOIN ".TABLE_SHIPPING_DESCRIPTION." AS pd ON(pd.shipping_id=p.shipping_id AND pd.language_code='".esc($default_langcode)."') ORDER BY p.shipping_code" );
  while( $row = act_db_fetch_array($res) )
  {
    $ret['installed_shipping_modules'][$row['shipping_code']] = array(
      'id' => (int)$row['shipping_id'],
      'code' => $row['shipping_code'],
      'active' => (int)$row['status'],
      'name' => $row['shipping_name']
    );
  }
  act_db_free( $res );
  
  $res = actindo_get_fields();
  $ret['artikel_properties'] = $res['fields'];
  $ret['artikel_property_sets'] = $res['field_sets'];

  return $ret;
}



function get_dropdown_data_non_json( $get )
{
  global $xtPlugin;
  ob_start();

  // from xtAdmin/DropdownData.php

  $request['get'] = $get;
  $dropdown = new getAdminDropdownData();

	switch ($get) {
		// languages
		case "language_codes":
			$result = $dropdown->getLanguageCodes();
			break;
		case "language_classes":
			$result = $dropdown->getLanguageClasses();
			break;
		case "language_nondefines":
			$result = $dropdown->getLanguageNonDefines();
			break;
			// languages end
		case "mail_types":
			$result = $dropdown->getMailTypes();
			break;
			// status
		case "status_truefalse":
			$result = $dropdown->getTrueFalse();
			break;
			// status
		case "download_status":
			$result = $dropdown->getDownloadStatus();
			break;
			// status
		case "conf_shippingtype":
			$result = $dropdown->getShippingType();
			break;
		case "conf_truefalse":
			$result = $dropdown->getConfTrueFalse();
			break;
		case "status_ascdesc":
			$result = $dropdown->getAscDesc();
			break;
			// status end
			// sort
		case "sort_defaults":
			$result = $dropdown->getSortDefaults();
			break;
		case "manufacturers":
			$result = $dropdown->getManufacturers();
			break;
			// product
		case "product_template":
			$result = $dropdown->getProductTemplate();
			break;
		case "product_list_template":
			$result = $dropdown->getProductListTemplate();
			break;
		case "options_template":
			$result = $dropdown->getProductOptionTemplate();
			break;
			// product end
		case "conf_storelogo":
			$result = $dropdown->getStoreLogo();
			break;
		case "categories_template":
			$result = $dropdown->getCategoryTemplate();
			break;
		case "listing_template":
			$result = $dropdown->getProductListingTemplate();
			break;
		case "micropages":
			$result = $dropdown->getMicropages();
			break;
		case "customers_status":
			$result = $dropdown->getCustomersStatus();
			break;
			// tax
		case "tax_zones":
			$result = $dropdown->getTaxZones();
			break;
		case "tax_classes":
			$result = $dropdown->getTaxClasses();
			break;
		case "category_sort":
			$result = $dropdown->getCategorySort();
			break;
		case "currencies":
			$result = $dropdown->getCurrencies();
			break;
		case "countries":
			$result = $dropdown->getCountries();
			break;
		case "stores":
			$result = $dropdown->getStores();
			break;
		case "gender":
			$result = $dropdown->getGender();
			break;
		case "address_types":
			$result = $dropdown->getAddressTypes();
			break;
		case "content_blocks":
			$result = $dropdown->getContentHooks();
			break;
		case "content_list":
			$result = $dropdown->getContentList();
			break;
		case "content_forms":
			$result = $dropdown->getContentForms();
			break;
		case "permission_areas":
			$result = $dropdown->getPermissionAreas();
			break;
		case "acl_group_list":
			$result = $dropdown->getACLGroupList();
			break;
		case "shipping_time":
			$result = $dropdown->getShippingTime();
			break;
		case "order_status":
			$result = $dropdown->getOrderStatus();
			break;
		case "admin_perm":
			$result = $dropdown->getAdminPerm();
			break;
		case "admin_rights":
			$result = $dropdown->getAdminRights();
			break;
		case "cat_tree":
			$result = $dropdown->getCatTree();
			break;
		case "file_types":
			$result = $dropdown->getFileTypes();
			break;
		case "image_classes":
			$result = $dropdown->getImageClasses();
			break;
		case "templateSets":
			$result = $dropdown->getTemplateSets();
			break;
		default:
            if(is_file($adminHandlerFile = SHOP_BASEDIR._SRV_WEB_FRAMEWORK.'admin/classes/class.ExtFunctions.php')) {
                require_once($adminHandlerFile);
            }
            if(is_file($adminHandlerFile = SHOP_BASEDIR._SRV_WEB_FRAMEWORK.'admin/classes/class.ExtEditForm.php')) {
                require_once($adminHandlerFile);
            }
            if(is_file($adminHandlerFile = SHOP_BASEDIR._SRV_WEB_FRAMEWORK.'admin/classes/class.ExtGrid.php')) {
                require_once($adminHandlerFile);
            }
            if(is_file($adminHandlerFile = SHOP_BASEDIR._SRV_WEB_FRAMEWORK.'admin/classes/class.ExtAdminHandler.php')) {
                require_once($adminHandlerFile);
            }
            
			($plugin_code = $xtPlugin->PluginCode('admin_dropdown.php:dropdown')) ? eval($plugin_code) : false;
	}

  ob_end_clean();
  return $result;
}

?>