<?php

/**
 * export products
 *
 * actindo Faktura/WWS connector
 *
 * @package actindo
 * @author Daniel Haimerl <haimerl@actindo.de>
 * @version $Revision: 505 $
 * @copyright Copyright (c) 2007, Daniel Haimerl (Schneebeerenweg 26, D-85551 Kirchheim, GERMANY, haimerl@actindo.de)
*/


function _master_slave_query( $tblalias='' )
{
  !empty($tblalias) or $tblalias = TABLE_PRODUCTS;
  if( act_have_column(TABLE_PRODUCTS, 'products_master_model') )
    return "(m.products_master_model='' OR m.products_master_model IS NULL)";
  else
    return '1';
}

function export_products_count( $categories_id=0, $products_model='' )
{
  $categories = array();

  if( $categories_id > 0 )
  {
    $q = "pc.`categories_id`={$categories_id}";
  }
  elseif( !empty($products_model) )
  {
    $q = "m.`products_model`='".esc($products_model)."'";
  }
  else
    $q = '1';

  $res = act_db_query(
    "SELECT ".($categories_id<0 ? 'pc.`categories_id` AS cid,' : '\''.(int)$categories_id.'\' AS cid,')."COUNT(*) AS cnt FROM ".TABLE_PRODUCTS." AS m, ".TABLE_PRODUCTS_TO_CATEGORIES." AS pc, ".TABLE_CATEGORIES." AS c WHERE ".
    "(c.`categories_id`=pc.`categories_id` OR (pc.`categories_id`=0 AND c.categories_id IS NULL)) AND m.`products_id`=pc.`products_id` AND "._master_slave_query('m')." AND {$q}".($categories_id<0 ? ' GROUP BY pc.`categories_id`' : '') );
  while( $c=act_db_fetch_assoc($res) )
    $categories[(int)$c['cid']] = (int)$c['cnt'];
  act_db_free($res);

  $res = act_db_query( "SELECT COUNT(*) AS cnt, pc.categories_id, c.categories_id FROM (".TABLE_PRODUCTS." AS m, ".TABLE_PRODUCTS_TO_CATEGORIES." AS pc) LEFT JOIN ".TABLE_CATEGORIES." AS c USING(`categories_id`) WHERE m.`products_id`=pc.`products_id` AND "._master_slave_query('m')." GROUP BY m.`products_id` HAVING (pc.categories_id=c.categories_id OR pc.categories_id=0)" );
  while( $c=act_db_fetch_assoc($res) )
    $categories[-1] += (int)$c['cnt'];
  act_db_free($res);

  return $categories;
}


function export_products_list( $categories_id=0, $products_model='', $lang='', $just_list=TRUE, $from=0, $count=0x7FFFFFFF, $filters=array() )
{
  global $db;

  $categories_id = (int)$categories_id;
  $products = array();

  if( !$lang )
    $lang = default_lang();

  $langcode = get_language_code_by_id( $lang );

  if( $categories_id )
  {
    $q = "pc.`categories_id`={$categories_id}";
  }
  elseif( !empty($products_model) )
  {
    $q = "m.`products_model`='".esc($products_model)."'";
  }
  else
    $q = '1';

  $mapping = array(
    'products_id' => array( 'm', 'products_id' ),
    'art_nr' => array( 'm', 'products_model' ),
    'created' => array( 'm', 'date_added' ),
    'last_modified' => array( 'm', 'last_modified' ),
    'products_status' => array( 'm', 'products_status', 'boolean' ),
    'art_name' => array( 'pd', 'products_name' ),
    'categories_id' => array( 'pc', 'categories_id' ),
  );
  $res = create_query_from_filter( $filters, $mapping );
  if( !is_array($res) )
    return array( 'ok'=> FALSE, 'errno'=>EIO, 'error'=>'create_query_from_filter returned false' );

  $exported = array();

  $res = act_db_query( "SELECT m.products_id AS `products_id`, m.`products_model` AS art_nr, m.`products_price` AS grundpreis, m.`date_added`, m.`last_modified`, m.`products_status`, pc.`categories_id` AS categories_id, pd.products_name AS art_name FROM (".TABLE_PRODUCTS." AS m, ".TABLE_PRODUCTS_TO_CATEGORIES." AS pc) LEFT JOIN ".TABLE_CATEGORIES." AS c ON (c.`categories_id`=pc.`categories_id` OR (pc.`categories_id`=0 AND c.categories_id IS NULL)) LEFT JOIN ".TABLE_PRODUCTS_DESCRIPTION." AS pd ON (m.`products_id`=pd.`products_id` AND pd.`language_code`=".$db->Quote($langcode).")  WHERE  m.`products_id`=pc.`products_id` AND "._master_slave_query('m')." AND (pc.categories_id=c.categories_id OR pc.categories_id=0) AND {$q} AND {$res['q_search']} GROUP BY m.products_id ORDER BY m.products_model, m.products_id LIMIT {$from}, {$count}" );
  while( $prod = act_db_fetch_assoc($res) )
  {
    if( !$categories_id && isset($exported[(int)$prod['products_id']]) )   // already exported, skip
      continue;

    $exported[(int)$prod['products_id']] = 1;

    $prod['products_id'] = (int)$prod['products_id'];
    $prod['grundpreis'] = (float)$prod['grundpreis'];
    $prod['categories_id'] = (int)$prod['categories_id'];
    $prod['products_status'] = (int)$prod['products_status'];
    $prod['created'] = datetime_to_timestamp( $prod['date_added'] );
    $prod['last_modified'] = datetime_to_timestamp( $prod['last_modified'] );
    $prod['products_status'] = (int)$prod['products_status'];
    if( $prod['last_modified'] <= 0 )
      $prod['last_modified'] = $prod['created'];
    unset( $prod['date_added'], $prod['last_modified'] );

/*
    $desc_query = act_db_query( "SELECT `products_name`, `language_id` FROM ".TABLE_PRODUCTS_DESCRIPTION." WHERE `products_id` = ".(int)$prod["products_id"]." ORDER BY `language_id` ASC" );
    while( $desc = act_db_fetch_array($desc_query) )
    {
      if( $desc['language_id'] == $lang )
        $prod['art_name'] = $desc['products_name'];
    }
    act_db_free($desc_query);
*/
    $products[] = $prod;
  }
  act_db_free($res);

  return array( 'ok' => TRUE, 'products' => $products );
}


function export_products( $categories_id=NULL, $products_id=NULL, $lang=NULL, $just_list=FALSE, $from=0, $count=0x7FFFFFFF, $filters=array() )
{
  $categories_id = (int)$categories_id;
  $products = array();

  if( !$lang )
    $lang = default_lang();
  $langcode = get_language_code_by_id( $lang );

  if( $categories_id )
  {
    $q = "pc.`categories_id`={$categories_id}";
  }
  elseif( !empty($products_id) )
  {
    $q = "m.`products_id`='".esc($products_id)."'";
  }
  else
    $q = '1';

  $mapping = array(
    'products_id' => array( 'm', 'products_id' ),
    'art_nr' => array( 'm', 'products_model' ),
    'created' => array( 'm', 'date_added' ),
    'last_modified' => array( 'm', 'last_modified' ),
    'products_status' => array( 'm', 'products_status', 'boolean' ),
    'art_name' => array( 'pd', 'products_name' ),
    'categories_id' => array( 'pc', 'categories_id' ),
  );
  $res = create_query_from_filter( $filters, $mapping );
  if( $res === FALSE )
    return array( 'ok'=> FALSE, 'errno'=>EIO, 'error'=>'create_query_from_filter returned false' );

  $exported = array();

  $res = act_db_query( "SELECT m.*, pc.categories_id FROM (".TABLE_PRODUCTS." AS m, ".TABLE_PRODUCTS_TO_CATEGORIES." AS pc) LEFT JOIN ".TABLE_CATEGORIES." AS c ON (c.`categories_id`=pc.`categories_id` OR (pc.`categories_id`=0 AND c.categories_id IS NULL)) LEFT JOIN ".TABLE_PRODUCTS_DESCRIPTION." AS pd ON (m.`products_id`=pd.`products_id` AND pd.`language_code`='".esc($langcode)."')  WHERE  m.`products_id`=pc.`products_id` AND "._master_slave_query('m')." AND (pc.categories_id=c.categories_id OR pc.categories_id=0) AND {$q} AND {$res['q_search']} GROUP BY m.products_id ORDER BY m.products_model, m.products_id LIMIT {$from}, {$count}" );
  while( $p = act_db_fetch_assoc($res) )
  {
    if( !$categories_id && isset($exported[(int)$p['products_id']]) )   // already exported, skip
      continue;


    $exported[(int)$p['products_id']] = 1;

    $p['products_id'] = (int)$p['products_id'];
    $p["art_nr"] = $p["products_model"];
    $p["l_bestand"] = (float)$p["products_quantity"];
    $p["weight"] = (float)$p["products_weight"];
    $p["weight_unit"] = "kg";
    $p['info_template'] = $p['product_template'];
    $p['options_template'] = $p['products_option_template'];
    $p['shipping_status'] = $p['products_shippingtime'];
    $p['products_date_available'] = $p['date_available'];
    $p['fsk18'] = $p['products_fsk18'];

    $p['created'] = datetime_to_timestamp( $p['date_added'] );
    $p['last_modified'] = datetime_to_timestamp( $p['last_modified'] );
    if( $p['last_modified'] <= 10000 )
      $p['last_modified'] = $p['created'];

    unset( $p['date_added'], $p['last_modified'] );


    // primary category
    $p['categories_id'] = (int)$p['categories_id'];

    // other categories
    $p['all_categories'] = array();
    $catid_query = act_db_query( "SELECT `categories_id` FROM ".TABLE_PRODUCTS_TO_CATEGORIES." WHERE products_id=".(int)$p["products_id"] );
    while( $cat = act_db_fetch_array($catid_query) )
    {
      $p['all_categories'][] = (int)$cat['categories_id'];
    }
    act_db_free($catid_query);


    // base price, taxes
    $p['is_brutto'] = ( _SYSTEM_USE_PRICE == 'true' );
    $p["grundpreis"] = export_convert_tax( (float)$p["products_price"], $p['is_brutto'], $p['products_tax_class_id'] );
    $p['mwst_stkey'] = -1;
    $p['mwst'] = act_get_tax_rate( $p['products_tax_class_id'] );
    switch( $p['products_tax_class_id'] )
    {
      case 0:
        $p['mwst_stkey'] = 0; // heh, 0, 1, 11 possible, we use 0, as actindo can handle this
        break;
      case 1:
        $p['mwst_stkey'] = 3;
        break;
      case 2:
        $p['mwst_stkey'] = 2;
        break;
    }


    // descriptions, names in all languages
    _do_export_descriptions( $p, $p['products_id'], $langcode );

    // price brackets
    _do_export_preisgruppen( $p, $p['products_id'], $p['products_tax_class_id'] );

    // attributes
    _do_export_attributes( $p );

    // cross-selling
    _do_export_xselling( $p );

    // group-permission
    _do_export_group_permission( $p, $p['products_id'] );

    // images
    _do_export_images( $p, $p['products_id'], $p['products_image'] );
    
	// Properties (Zusatzfelder)
	_do_export_properties($p, $p['products_id']);

    $products[] = $p;
  }
  act_db_free($res);

  return array( 'ok' => TRUE, 'products' => $products );
}


function _do_export_group_permission( &$p, $products_id )
{
  $cs = export_customers_status();
  $ms = export_multistores();

  $group_perm = $shop_perm = array();
  $res = act_db_query( "SELECT * FROM ".TABLE_PRODUCTS_PERMISSION." WHERE pid=".(int)$products_id );
  while( $row = act_db_fetch_array($res) )
  {
    if( stripos($row['pgroup'], 'group_permission_') === 0 )
    {
      $perm_id = (int)substr( $row['pgroup'], 17 );
      if( _SYSTEM_GROUP_PERMISSIONS == 'blacklist' )
        $group_perm[$perm_id] = $row['permission'] ? 0 : 1;
      else  // whitelist
        $group_perm[$perm_id] = $row['permission'] ? 1 : 0;
    }
    elseif( stripos($row['pgroup'], 'shop_') === 0 )
    {
      $perm_id = (int)substr( $row['pgroup'], 5 );
      if( _SYSTEM_GROUP_PERMISSIONS == 'blacklist' )
        $shop_perm[$perm_id] = $row['permission'] ? 0 : 1;
      else  // whitelist
        $shop_perm[$perm_id] = $row['permission'] ? 1 : 0;
    }
  }
  act_db_free( $res );

  foreach( array_keys($cs) as $_cs_id )
  {
    if( _SYSTEM_GROUP_PERMISSIONS == 'blacklist' )
    {
      isset($group_perm[$_cs_id]) or $group_perm[$_cs_id] = 1;
    }
    else  // whitelist
    {
      isset($group_perm[$_cs_id]) or $group_perm[$_cs_id] = 0;
    }
  }

  foreach( array_keys($ms) as $_ms_id )
  {
    if( _SYSTEM_GROUP_PERMISSIONS == 'blacklist' )
    {
      isset($shop_perm[$_ms_id]) or $shop_perm[$_ms_id] = 1;
    }
    else  // whitelist
    {
      isset($shop_perm[$_ms_id]) or $shop_perm[$_ms_id] = 0;
    }
  }

  ksort( $shop_perm );
  ksort( $group_perm );

  $p['group_permission'] = $p['multistore_permission'] = array();
  foreach( $group_perm as $_id => $_perm )
  {
    if( $_perm )
      $p['group_permission'][] = $_id;
  }
  foreach( $shop_perm as $_id => $_perm )
  {
    if( $_perm )
      $p['multistore_permission'][] = $_id;
  }

}

function _do_export_images( &$p, $products_id, $products_image )
{
  if( !is_array($p['images']) )
    $p['images'] = array();

  if( strlen($products_image) )
  {
    $p['images'][] = array(
      'image_nr' => 0,
      'image_name' => $products_image
    );
  }

  $img_query = act_db_query( "SELECT m.id, m.file AS image_name FROM ".TABLE_MEDIA_LINK." AS ml, ".TABLE_MEDIA." AS m WHERE m.id=ml.m_id AND m.`type`='images' AND ml.class='product' AND ml.type='images' AND ml.link_id = ".(int)$products_id." ORDER BY ml.`ml_id` ASC" );
  $i = 1;
  while( $img = act_db_fetch_array($img_query) )
  {
    $img['image_nr'] = $i++;
    $p["images"][] = $img;
  }
  act_db_free($img_query);


  foreach( $p["images"] as $idx => $img )
  {
    if( strlen($file_name = $img['image_name']) )
    {
      $path = null;
      if( defined( "_DIR_ORG" ) && is_readable($path=_SRV_WEBROOT._SRV_WEB_IMAGES._DIR_ORG.$file_name) && filesize($path) )
        $p['images'][$idx]['image_subfolder'] = 'org';
      elseif( defined( "_DIR_INFO" ) && is_readable($path=_SRV_WEBROOT._SRV_WEB_IMAGES._DIR_INFO.$file_name) && filesize($path) )
        $p['images'][$idx]['image_subfolder'] = 'info';
      if( !is_null($path) )
      {
        $p["images"][$idx]["image"] = file_get_contents( $path );
        $size = getimagesize( $path );
        $p["images"][$idx]["image_type"] = image_type_to_mime_type( $size[2] );
      }
    }
  }

}


function _do_export_descriptions( &$p, $products_id, $langcode='' )
{
  $desc_query = act_db_query( "SELECT d.*, s.meta_title AS products_meta_title, s.meta_description AS products_meta_description, s.meta_keywords AS products_meta_keywords, s.url_text AS seo_url FROM ".TABLE_PRODUCTS_DESCRIPTION." AS d LEFT JOIN ".TABLE_SEO_URL." AS s ON(s.link_type=1 AND s.link_id=d.products_id AND s.language_code=d.language_code) WHERE d.products_id=".(int)$products_id." ORDER BY d.`language_code` ASC" );
  while( $desc = act_db_fetch_array($desc_query) )
  {
    $desc['language_id'] = (int)get_language_id_by_code( $desc['language_code'] );
    if( $desc['language_code'] == $langcode )
      $p["art_name"] = $desc["products_name"];
    foreach( $desc as $key => $val )
    {
      $p["description"][(int)$desc["language_id"]][$key] = $val;
    }
  }
  act_db_free($desc_query);
}


function _do_export_attributes( &$p )
{
  if( !act_have_table(TABLE_PRODUCTS_TO_ATTRIBUTES) )
  {
    return;
  }

  $attr_query = act_db_query( $q="SELECT pa.*, p.products_model, p.products_status, p.products_quantity, p.products_tax_class_id, p.products_price, p.products_image FROM ".TABLE_PRODUCTS_TO_ATTRIBUTES." AS pa, ".TABLE_PRODUCTS." AS p WHERE pa.products_id= p.products_id AND `products_master_model`='".esc($p['products_model'])."' ORDER BY pa.`products_id` ASC, pa.`attributes_parent_id` ASC" );
  if( act_db_num_rows($attr_query) )
  {
    $p['attributes'] = array(
      'only_exported_combinations_are_valid' => 1     // to tell actindo that only combinations in 'combinations_advanced' are valid. All other possible combinations don't exist
    );
    while( $row=act_db_fetch_array($attr_query) )
    {
      $row['attributes_parent_id'] = (int)$row['attributes_parent_id'];
      $row['attributes_id'] = (int)$row['attributes_id'];
      $row['products_id'] = (int)$row['products_id'];

      if( !isset($p['attributes']['names'][$row['attributes_parent_id']]) )
      {
        $p['attributes']['names'][$row['attributes_parent_id']] = $p['attributes']['values'][$row['attributes_parent_id']] = array();
        $oid_query = act_db_query( "SELECT * FROM ".TABLE_PRODUCTS_ATTRIBUTES_DESCRIPTION." WHERE `attributes_id`={$row['attributes_parent_id']}" );
        while( $opt=act_db_fetch_array($oid_query) )
          $p['attributes']['names'][$row['attributes_parent_id']][$opt['language_code']] = $opt['attributes_name'];
        act_db_free( $oid_query );

        $vid_query = act_db_query( "SELECT ad.*, a.attributes_parent FROM ".TABLE_PRODUCTS_ATTRIBUTES_DESCRIPTION." AS ad, ".TABLE_PRODUCTS_ATTRIBUTES." AS a WHERE ad.attributes_id=a.attributes_id AND a.attributes_parent={$row['attributes_parent_id']}" );
        while( $val=act_db_fetch_array($vid_query) )
          $p['attributes']['values'][$row['attributes_parent_id']][(int)$val['attributes_id']][$val['language_code']] = $val['attributes_name'];
        act_db_free( $vid_query );
      }


      $p['attributes']['combination_simple'][$row['attributes_parent_id']][$row['attributes_id']] = array(
        'options_values_price' => 0,
        'attributes_model' => '',
        'options_values_weight' => 0,
      );

      if( !isset($p['attributes']['combination_advanced'][$row['products_model']]) )
      {
        $p['attributes']['combination_advanced'][$row['products_model']] = array(
          'attribute_name_id' => array(),
          'attribute_value_id' => array(),
          'l_bestand' => (float)$row['products_quantity'],
          'preisgruppen' => array(),
          'is_brutto' => $p['is_brutto'],
          'grundpreis' => export_convert_tax( (float)$row["products_price"], $p['is_brutto'], $row['products_tax_class_id'] ),
          'data' => array(
            'products_status' => (int)$row['products_status'],
//            'products_is_standard' => (int)$row['standard'],
          ),
        );

        _do_export_preisgruppen( $p['attributes']['combination_advanced'][$row['products_model']], $row['products_id'], $row['products_tax_class_id'] );
        _do_export_descriptions( $p['attributes']['combination_advanced'][$row['products_model']]['shop'], $row['products_id'], '' );
        _do_export_images( $p['attributes']['combination_advanced'][$row['products_model']]['shop'], $row['products_id'], $row['products_image'] );
      }
      $p['attributes']['combination_advanced'][$row['products_model']]['attribute_name_id'][] = $row['attributes_parent_id'];
      $p['attributes']['combination_advanced'][$row['products_model']]['attribute_value_id'][] = $row['attributes_id'];
    }
  }
  act_db_free($attr_query);


}

function _do_export_preisgruppen( &$p, $products_id, $products_tax_class_id )
{
  $groups = array_keys(export_customers_status());
  $preisgruppen = array();
  foreach( $groups as $status_id )
  {
    $n = 0;
    $offer_res = act_db_query( "SELECT * FROM ".TABLE_PRODUCTS_PRICE_GROUP.(int)$status_id." WHERE `products_id`=".(int)$products_id." AND `price` IS NOT NULL ORDER BY `discount_quantity` ASC" );
    while( $pg = act_db_fetch_assoc($offer_res) )
    {
      $preisgruppen[(int)$status_id]['is_brutto'] = $p['is_brutto'];
      if( $pg['discount_quantity'] == 1 )
        $preisgruppen[(int)$status_id]['grundpreis'] = export_convert_tax( (float)$pg['price'], $preisgruppen[(int)$status_id]['is_brutto'], $products_tax_class_id );
      else
      {
        $n++;
        $preisgruppen[(int)$status_id]['preis_gruppe'.$n] = export_convert_tax( (float)$pg['price'], $preisgruppen[(int)$status_id]['is_brutto'], $products_tax_class_id );
        $preisgruppen[(int)$status_id]['preis_range'.$n] = (int)$pg['discount_quantity'];
      }
    }
    act_db_free( $offer_res );
    if( !count($preisgruppen[(int)$status_id]) )
      continue;
  }
  $p['preisgruppen'] = $preisgruppen;
}


function _do_export_xselling( &$p )
{
  if( !act_have_table(TABLE_PRODUCTS_CROSS_SELL) )
  {
    return;
  }

  $p['xselling'] = array();
  $res = act_db_query( "SELECT p.products_model FROM ".TABLE_PRODUCTS_CROSS_SELL." AS px, ".TABLE_PRODUCTS." AS p WHERE px.`products_id`=".(int)$p['products_id']." AND p.products_id=px.products_id_cross_sell" );
  while( $row = act_db_fetch_array($res) )
  {
    $p['xselling'][] = array( 'art_nr'=>$row['products_model'], 'group'=>0, 'sort_order'=>0 );
  }
  act_db_free( $res );
}


function _do_export_properties(&$p, $products_id) {
	static $fields = null;
	static $defaultLanguage = null;
	if($fields === null) {
		$fields = actindo_get_fields();
		$fields = $fields['fields'];
	}
	if($defaultLanguage === null) $defaultLanguage = get_language_code_by_id(default_lang());
	$p['properties'] = array();
	
	
	// i18n fields (from _products_description)
	$sql = sprintf('SELECT * FROM `%s` WHERE `products_id` = %d ORDER BY FIELD(`language_code`, "de")', TABLE_PRODUCTS_DESCRIPTION, $products_id);
    // the order by clause will always put "de" fields to the end of the result set which should make it the "active" field in actindo
	$result = act_db_query($sql);
	while($row = act_db_fetch_array($result)) {
		foreach(array_keys($row) AS $field) {
			$columnName = $field;
			$field = 'pd_' . $field;
			if(!isset($fields[$field])) {
				// not a custom field, continue
				continue;
			}
			
			if(empty($row['language_code'])) {
				$row['language_code'] = $defaultLanguage;
			}
			$p['properties'][] = array(
				'field_id' => $field,
				'language_code' => $row['language_code'],
				'field_value' => $row[$columnName],
			);
		}
	}
	act_db_free($result);
	
	// non i18n fields (from _products)
	$sql = sprintf('SELECT * FROM `%s` WHERE `products_id` = %d', TABLE_PRODUCTS, $products_id);
	$result = act_db_query($sql);
	while($row = act_db_fetch_array($result)) {
		foreach(array_keys($row) AS $field) {
			$columnName = $field;
			$field = 'p_' . $field;
			if(!isset($fields[$field])) {
				// not a custom field
				continue;
			}
			
			$p['properties'][] = array(
				'field_id' => $field,
				'language_code' => null,
				'field_value' => $row[$columnName],
			);
		}
	}
	act_db_free($result);
}


function export_convert_tax( $price, $is_brutto=0, $products_tax_class_id )
{
  $is_brutto = $is_brutto > 0;
  $price_precision = 4;

  if( $is_brutto )      // sooo...
    $price = round(($price * (act_get_tax_rate($products_tax_class_id) + 100) / 100), $price_precision);

  return $price;
}



function export_categories( )
{
  $cats = array();
  $langs = export_shop_languages( );
  foreach( $langs as $lang )
  {
    $cats[(int)$lang['language_id']] = _do_export_categories( 0, $lang['language_code'], 0 );
  }
  return $cats;
}


function _do_export_categories( $children_of=0, $language_code='de', $depth=0 )
{
  global $db;

  $cats = array();
  $res = act_db_query( "SELECT c.categories_id, c.parent_id, d.language_code, d.categories_name FROM ".TABLE_CATEGORIES." c, ".TABLE_CATEGORIES_DESCRIPTION." d WHERE c.`categories_id`=d.`categories_id` AND d.language_code=".$db->Quote($language_code)." AND c.parent_id=".(int)$children_of." ORDER BY c.`sort_order`, d.`categories_name`" );
  while( $c = act_db_fetch_array($res) )
  {
    $cats[(int)$c['categories_id']] = $c;
    $ch = _do_export_categories( (int)$c['categories_id'], $language_code, $depth+1 );
    if( count($ch) )
      $cats[(int)$c['categories_id']]['children'] = $ch;
  }
  act_db_free( $res );

  return $cats;
}



?>