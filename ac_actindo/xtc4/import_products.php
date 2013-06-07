<?php

/**
 * import products
 *
 * actindo Faktura/WWS connector
 *
 * @package actindo
 * @author Daniel Haimerl <haimerl@actindo.de>
 * @version $Revision: 506 $
 * @copyright Copyright (c) 2007, Daniel Haimerl (Schneebeerenweg 26, D-85551 Kirchheim, GERMANY, haimerl@actindo.de)
*/

function import_product( $product )
{
  $failed=0;
  $success=0;
  $warning = array();

  if( !is_array( $product ) || !count( $product ) )
  {
    return array( 'ok' => FALSE, 'errno' => EINVAL );
  }

  require_once( SHOP_BASEDIR._SRV_WEB_FRAMEWORK.'classes/class.image.php' );
  require_once( SHOP_BASEDIR._SRV_WEB_FRAMEWORK.'classes/class.MediaFileTypes.php' );
  require_once( SHOP_BASEDIR._SRV_WEB_FRAMEWORK.'classes/class.MediaData.php' );
  require_once( SHOP_BASEDIR._SRV_WEB_FRAMEWORK.'classes/class.MediaImages.php' );
  require_once( SHOP_BASEDIR._SRV_WEB_FRAMEWORK.'admin/classes/class.adminDB_DataSave.php' );
  require_once( SHOP_BASEDIR._SRV_WEB_FRAMEWORK.'functions/filter_text.inc.php' );
  require_once( SHOP_BASEDIR._SRV_WEB_FRAMEWORK.'classes/class.product.php' );
  require_once( SHOP_BASEDIR._SRV_WEB_FRAMEWORK.'classes/class.product_to_cat.php' );
  require_once( SHOP_BASEDIR._SRV_WEB_FRAMEWORK.'classes/class.product_price.php' );

  // check primary category
  $res = act_db_query( "SELECT COUNT(*) FROM ".TABLE_CATEGORIES." WHERE `categories_id`=".(int)$product['swg'] );
  $cnt = act_db_fetch_row( $res );
  act_db_free( $res );
  if( $cnt[0] <= 0 )
  {
    return array( 'ok' => FALSE, 'errno' => ENOENT, 'error'=>'Kategorie nicht mehr vorhanden oder gelöscht.' );
  }


  $p = array();

  $res = act_db_query( $q="SELECT ".TABLE_PRODUCTS.".`products_id`, ".TABLE_PRODUCTS.".`products_serials`, ".TABLE_PRODUCTS_TO_CATEGORIES.".categories_id, ".TABLE_CATEGORIES.".categories_id AS verify_cat_id FROM ".TABLE_PRODUCTS." LEFT JOIN ".TABLE_PRODUCTS_TO_CATEGORIES." USING(`products_id`) LEFT JOIN ".TABLE_CATEGORIES." USING(`categories_id`) WHERE `products_model`='".esc($product['art_nr'])."'" );
  $n = act_db_num_rows( $res );
  if( $n )
  {
    $pp = act_db_fetch_array($res);
    if( $pp['products_id'] ) {
      $p['products_id'] = $pp['products_id'];
      $p['products_serials'] = $pp['products_serials'];
    }
  }
  act_db_free( $res );

  $action = $n > 0 ? 'edit' : 'new';


  if( isset($product['art_nr']) )
    $p['products_model'] = $product['art_nr'];
  if( isset($product['l_bestand']) )
    $p['products_quantity'] = (int)$product['l_bestand'];
  if( isset($product['lft']) )
    $p['manufacturers_id'] = $product['lft'];


  // taxes
  if( isset($product['taxes_advanced']) )
  {
    $res = _import_product_to_taxes_advanced( $product['taxes_advanced'], $product['leist_art'], $p['products_tax_class_id'] );
    if( !$res )
      return $res;
    $p['products_price'] = import_convert_tax( $product['grundpreis'], $product['is_brutto'], $product['mwst'], $p['products_tax_class_id'], TRUE );
  }
  else if( isset($product['leist_art']) && $product['leist_art'] > 0 )
  {
    $p['products_tax_class_id'] = $product['leist_art'];
    $p['products_price'] = import_convert_tax( $product['grundpreis'], $product['is_brutto'], $product['mwst'], $p['products_tax_class_id'], TRUE );
  }
  else
  {
    switch( $product['mwst_stkey'] )
    {
      case 3:
        $p['products_tax_class_id'] = 1;
        break;
      case 2:
        $p['products_tax_class_id'] = 2;
        break;
      case 0:
      case 1:
      case 11:
        $p['products_tax_class_id'] = 0;
        break;

      default:
        return array( 'ok' => FALSE, 'errno' => EUNKNOWN, 'error' => 'Im Shop nicht verfügbarer Steuersatz.' );
    }
    $p['products_price'] = import_convert_tax( $product['grundpreis'], $product['is_brutto'], $product['mwst'], $p['products_tax_class_id'], TRUE );
  }


  // basisdaten
  if( is_array($product['shop']['art']) && count($product['shop']['art']) )
  {
    $dont_set = array( 'art_id', 'shop_id', 'shop_upload_date', 'in_shop' );
    unset( $product['shop']['art']['id'], $pruduct['shop']['art']['art_id'], $pruduct['shop']['art']['in_shop'] );
    foreach( $product['shop']['art'] as $key => $val )
    {
      if( $key == 'products_date_available' )
        $p['date_available'] = $val;
      elseif( $key == 'info_template' )
        $p['product_template'] = $val;
      elseif( $key == 'options_template' )
        $p['products_option_template'] = $val;
      elseif( $key == 'shipping_status' )
        $p['products_shippingtime'] = $val;
      elseif( $key == 'fsk18' )
        $p['products_fsk18'] = $val;
      elseif($key == 'products_ean')
        $p['products_ean'] = $val;
      elseif( !in_array($key, $dont_set) && strlen( trim( $val ) ) )
        $p[$key] = $val;
    }
  }
  else
    return array( 'ok' => FALSE, 'errno' => EUNKNOWN, 'error' => 'Keine Shopdetails hinterlegt' );


  $pobj = new actindo_veyton_product();
  $pobj->position = 'admin';



  // descriptions
  $res = _do_import_descriptions( $product, $p );
  if( !$res['ok'] )
  {
    $res['warning'] = $warning;
    return $res;
  }
  else
  {
    if( is_array($res['warning']) )
      $warning = array_merge( $res['warning'], $warning );
  }


  // images
  $res = _do_import_images( $product, $p );
  if( !$res['ok'] )
  {
    $res['warning'] = $warning;
    return $res;
  }
  else
  {
    if( is_array($res['warning']) )
      $warning = array_merge( $res['warning'], $warning );
  }


  $_old_products_model = $p['products_model'];
  if( $action == 'edit' )
  {
    $old_product = $pobj->_get( $p['products_id'] );
    foreach( $old_product->data[0] as $_key => $_val )
    {
      if( stripos($_key, "url_text") === 0 && empty($p[$_key]) )      // if url_text_[langcode] is not set, set it here
        $p[$_key] = $_val;

      if( $_key == 'products_model' )
        $_old_products_model = $_val;
    }
  }


  $res = $pobj->_set( $p, $action );
  if( $res->failed )
    return array( 'ok'=>FALSE, 'errno'=>EUNKNOWN, 'error'=>'Fehler beim speichern des Artikels mit product::_set' );


  $products_id = $res->new_id ? $res->new_id : $p['products_id'];
  $p['products_id'] = $products_id;


  // if we created the product: RE-SAVE it. This is important for SEO URL's, do not modify!
  if( $action != 'edit' )
  {
    $p1 = $p;
    $p1 = $pobj->actindo_fix_fields_for_update( $p1 );
    $res = $pobj->_set( $p1, 'edit' );
    if( $res->failed )
      return array( 'ok'=>FALSE, 'errno'=>EUNKNOWN, 'error'=>'Fehler beim wiederholten speichern des Artikels mit product::_set' );
  }

  if( act_have_column(TABLE_PRODUCTS, 'products_option_list_template') )
  {
    !empty($p['products_option_list_template']) or $p['products_option_list_template'] = '';
    $res2 = act_db_query( "UPDATE ".TABLE_PRODUCTS." SET `products_option_list_template`='".esc($p['products_option_list_template'])."' WHERE `products_id`=".$p['products_id'] );
  }


  if( $action == 'edit' && act_have_column(TABLE_PRODUCTS, 'products_master_model') )
  {
    if( strcmp($_old_products_model, $p['products_model']) )
    {
      $sql = "UPDATE ".TABLE_PRODUCTS." SET `products_master_model`='".esc($p['products_model'])."' WHERE `products_master_model`='".esc($_old_products_model)."'";
      $res2 = act_db_query( $sql );
    }
  }



  // group permissions
  $res = _do_import_group_permissions( $product, $products_id );
  if( !$res['ok'] )
  {
    $res['warning'] = $warning;
    return $res;
  }
  else
  {
    if( is_array($res['warning']) )
      $warning = array_merge( $res['warning'], $warning );
  }


  // categories
  act_db_query( "UPDATE ".TABLE_PRODUCTS_TO_CATEGORIES." SET `master_link`=0 WHERE `products_id`=".(int)$products_id );
  $p2c = new product_to_cat();
  $p2c->setPosition( 'admin' );
  $all_cats = array( $product['swg'] );
  if( is_array($product['shop']['all_categories']) )
  {
    foreach( $product['shop']['all_categories'] as $_i => $_cat )
    {
      if( $_cat == $product['swg'] || $_cat == 0 )
      {
        continue;
      }
      $cntqry = act_db_query( "SELECT COUNT(*) AS cnt FROM ".TABLE_CATEGORIES." WHERE `categories_id`=".(int)$_cat );
      $cnt = act_db_fetch_array( $cntqry );
      act_db_free( $cntqry );
      if( $cnt['cnt'] )
        $all_cats[] = (int)$_cat;
      else
        $warning[] = sprintf( "Kategorie %d im Shop nicht mehr vorhanden", $_cat );
    }
  }
  $p2c_data = array(
    'products_id' => $products_id,
    'catIds' => join( ',', $all_cats )
  );
  $p2c->url_data = $p2c_data;
  $res = $p2c->setData( TRUE );
  //bug #90781 returned data is possible formated as json
  if(is_string($res) && false !== ($jDecoded = json_decode($res))){ $res = $jDecoded; }
  if( !$res->success )
  {
    return array( 'ok'=>FALSE, 'errno'=>EUNKNOWN, 'error'=>'Fehler beim speichern der Kategoriezuordnungen des Artikels' );
  }
  act_db_query( "UPDATE ".TABLE_PRODUCTS_TO_CATEGORIES." SET `master_link`=1 WHERE `products_id`=".(int)$products_id." AND `categories_id`=".(int)$product['swg'] );


  if( version_compare(_SYSTEM_VERSION, '4.0.12', '>=') )
  {
    $res = _do_import_images_step2( $product, $p );
    if( !$res['ok'] )
    {
      $res['warning'] = $warning;
      return $res;
    }
    else
    {
      if( is_array($res['warning']) )
        $warning = array_merge( $res['warning'], $warning );
    }
  }


  // Preisgruppen
  $res = _do_import_preisgruppen( $product, $p, $products_id );
  if( !$res['ok'] )
  {
    $res['warning'] = $warning;
    return $res;
  }
  else
  {
    if( is_array($res['warning']) )
      $warning = array_merge( $res['warning'], $warning );
  }


  // Attributes (Varianten)
  $res = _do_import_attributes( $product, $p );
  if( !$res['ok'] )
  {
    $res['warning'] = $warning;
    return $res;
  }
  else
  {
    if( is_array($res['warning']) )
      $warning = array_merge( $res['warning'], $warning );
  }
  
  
  // Properties (Zusatzfelder)
  $res = _do_import_properties($product, $products_id);
  if(!$res['ok']) {
      $res['warning'] = $warning;
      return $res;
  }
  else {
      if(is_array($res['warning'])) {
          $warning = array_merge($res['warning'], $warning);
      }
  }


  $res = _do_import_cross_selling( $product, $products_id );
  if( !$res['ok'] )
  {
    $res['warning'] = $warning;
    return $res;
  }
  else
  {
    $warning = array_merge( $res['warning'], $warning );
  }


  $success++;
  return array( 'ok' => TRUE, 'success' => $success, 'warning' => $warning );
}


function import_convert_tax( $price, $is_brutto=0, $mwst, $products_tax_class_id, $is_products_price=FALSE )
{
  $is_brutto = $is_brutto > 0;
  $shop_is_brutto = (_SYSTEM_USE_PRICE == 'true');
  $price_precision = 4;

  if( $is_brutto )
    $price = round(($price / (act_get_tax_rate($products_tax_class_id) + 100) * 100), $price_precision);

  if( $is_products_price && $shop_is_brutto )
    $price = round(($price * (act_get_tax_rate($products_tax_class_id) + 100) / 100), $price_precision);

  return $price;
}




function _do_import_descriptions( &$product, &$p, $force_texts=TRUE )
{
  if( is_array($product['shop']['desc']) && count($product['shop']['desc']) )
  {
    foreach( $product['shop']['desc'] as $num => $description )
    {
      $lang_id = $description['language_id'];
      $langcode = get_language_code_by_id( $lang_id );
      unset( $description['id'], $description['art_id'], $description['language_id'], $description['language_code'] );

      foreach( $description as $key => $item )
      {
        if( !$force_texts && !strlen(trim($item)) && $key == 'products_name' )
          continue;

        if( $key == 'products_name' )
        {
          if( !strlen( trim( $description['products_name'] ) ) && strlen($product['art_name']) )
            $item = $product['art_name'];
        }
        else if( $key == 'products_meta_description' || $key == 'products_meta_keywords' || $key == 'products_meta_title' )
          $key = strtr( $key, array('products_'=>'') );
        else if( $key == 'seo_url' )
          $key = 'url_text';
        $p[$key.'_'.$langcode] = $item;
      }
    }
  }
  else
  {
    if( $force_texts )
      return array( 'ok' => FALSE, 'errno' => EUNKNOWN, 'error' => 'Keine Shoptexte hinterlegt' );
  }
  return array( 'ok'=> TRUE );
}


function _do_import_images( &$product, &$p )
{
  $warning = array();
  $cnt = 1;
  if( is_array($product['shop']['images']) && count($product['shop']['images']) )
  {
    $media_images = new MediaImages();
    foreach( $product['shop']['images'] as $num => $image )
    {
      $image['image_name'] = $cnt.preg_replace('/[^a-zA-Z0-9_\.]/', '_', basename($image['image_name']));
	  $cnt++;
      $imagesize = strlen($image['image']);
      $errprefix = sprintf( "Bild %d: ", $num );

      $fn = "";
      $write = TRUE;
      if( !empty($image['image_name']) )
      {
        $fn = SHOP_BASEDIR._SRV_WEB_IMAGES._DIR_ORG.$image['image_name'];
        if( @file_exists($fn) && @filesize($fn) == $imagesize )
        {
          // maybe check if all image subfolders (info, etc) still exist
          $write = FALSE;
        }
        if( file_exists($fn) && !is_writable($fn) && $write )
        {
          $fn = "";
        }
      }

//      var_dump($fn, $write );
      if( $write )
      {
        if( empty($fn) )
        {
          $fn1 = tempnam( $tmpdir=SHOP_BASEDIR._SRV_WEB_IMAGES._DIR_ORG, $image['image_name'] );
          if( $fn1 === FALSE )
          {
            return array( 'ok' => FALSE, 'errno' => EIO, 'error'=> $errprefix.'Fehler beim anlegen der temporären Datei in '.var_dump_string($tmpdir) );
          }
          $ext = "";
          switch( $image['image_type'] )
          {
            case 'image/jpeg':
            case 'image/pjpeg':
              $ext = "jpg";
              break;
            case 'image/png':
              $ext = "png";
              break;
            case 'image/gif':
              $ext = "gif";
              break;
          }
          if( empty($ext) )
          {
            return array( 'ok' => FALSE, 'errno' => EIO, 'error'=> $errprefix.'Unbekannter Bildtyp '.$image['image_type'] );
          }
          if( !rename($fn1, $fn=$fn1.'.'.$ext) )
          {
            return array( 'ok' => FALSE, 'errno' => EIO, 'error'=> $errprefix.'Fehler beim umbenennen des Bildes im Dateisystem (Von '.var_dump_string($fn1).' nach '.var_dump_string($fn).')' );
          }
        }

        $written = file_put_contents( $fn, $image['image'] );
        if( $written != $imagesize )
        {
          $ret = array( 'ok' => FALSE, 'errno' => EIO, 'error' => $errprefix.'Fehler beim schreiben des Bildes in das Dateisystem (Pfad '.var_dump_string($fn).', written='.var_dump_string($written).', filesize='.var_dump_string(@filesize($fn)).')' );
          unlink( $fn );
          return $ret;
        }
      }   // if $write

      $media_images->setMediaData(array('file'=>basename($fn), 'type'=>'images', 'class'=>'product') );

// DO NOT use setMediaToCurrentType here as it does not work.
//        if( version_compare(_SYSTEM_VERSION, '4.0.12', '>=') )
//          $media_images->setMediaToCurrentType( basename($fn) );

      $media_images->processImage( basename($fn) );


      if( version_compare(_SYSTEM_VERSION, '4.0.12', '<') )
      {
        if( $num == 1 )
          $p['products_image'] = basename( $fn );
        else
        {
          // goes on with image_0, image_1, etc
          $p['image_'.($num-2)] = basename( $fn );
          $p['image_class'] = 'product';
        }
      }
      else
      {
        $p['__actindo']['images'][] = basename( $fn );
      }
    }

    return array( 'ok' => TRUE, 'warning' => $warning );
  }
  else
  {
    $warning[] = "Keine Bilder im Artikel hinterlegt";
    return array( 'ok' => TRUE, 'warning' => $warning, 'no_images'=>TRUE );
  }
}


function _do_import_images_step2( &$product, &$p )
{
  $warning = array();

  if( is_array($p['__actindo']['images']) && count($p['__actindo']['images']) )
  {
    act_db_query( $q="DELETE FROM ".TABLE_MEDIA_LINK." WHERE `link_id`=".(int)$p['products_id']." AND `class`='product' AND `type`='images'" );
//    var_dump($q);
    foreach( $p['__actindo']['images'] as $num => $imagename )
    {
      $res = act_db_query( "SELECT * FROM ".TABLE_MEDIA." WHERE `type`='images' AND `class`='product' AND `file`='".esc($imagename)."'" );
      $row = act_db_fetch_assoc( $res );
      act_db_free( $res );

      if( !is_array($row) )
      {
        $warning[] = "Bild '{$imagename}' konnte nicht in ".TABLE_MEDIA." gefunden werden.";
        continue;
      }

      if( $row['status'] != 'true' )
      {
        act_db_query( "UPDATE ".TABLE_MEDIA." SET `status`='true' WHERE id=".(int)$row['id'] );
      }

      if( $num == 0 )
      {
        act_db_query( $q="UPDATE ".TABLE_PRODUCTS." SET `products_image`='".esc($imagename)."' WHERE `products_id`=".(int)$p['products_id'] );
//        var_dump($q);
      }
      else
      {
        act_db_query( $q="INSERT INTO ".TABLE_MEDIA_LINK." SET m_id=".(int)$row['id'].", link_id=".(int)$p['products_id'].", class='product', type='images', sort_order=".(int)$num );
//        var_dump($q);
      }
    }

    return array( 'ok' => TRUE, 'warning' => $warning );
  }
  else {
      // article has no images, remove in shop
      act_db_query('DELETE FROM ' . TABLE_MEDIA_LINK . ' WHERE `link_id`= '.(int)$p['products_id'].' AND `class`= "product" AND `type` = "images"');
      act_db_query('UPDATE ' . TABLE_PRODUCTS . ' SET `products_image` = NULL WHERE `products_id` = ' . intval($p['products_id']));
  }

  // no warnings as they were emitted in _do_import_images already
  return array( 'ok' => TRUE );
}


function _do_import_preisgruppen( &$product, $p, $products_id )
{
  $groups = array_keys(export_customers_status());
  $groups[] = -1;
  foreach( $groups as $status_id )
  {
    $status_str = $status_id > 0 ? sprintf("%d", $status_id) : 'all';

    $res1 = act_db_query( $q="DELETE FROM ".TABLE_PRODUCTS_PRICE_GROUP.$status_str." WHERE `products_id`=".(int)$products_id );
    if( is_array($product['preisgruppen'][$status_id]) )
    {
      $res1 &= act_db_query( "INSERT INTO ".TABLE_PRODUCTS_PRICE_GROUP.$status_str." SET `products_id`=".(int)$products_id.", `discount_quantity`=1, `price`='".import_convert_tax( $product['preisgruppen'][$status_id]['grundpreis'], $product['preisgruppen'][$status_id]['is_brutto'], $product['mwst'], $p['products_tax_class_id'] )."'" );
      for( $i=1; $i<=4; $i++ )
      {
        if( $product['preisgruppen'][$status_id]['preis_range'.$i] <= 0 )
          continue;
        $res1 &= act_db_query( "INSERT INTO ".TABLE_PRODUCTS_PRICE_GROUP.$status_str." SET `products_id`=".(int)$products_id.", `discount_quantity`=".(float)$product['preisgruppen'][$status_id]['preis_range'.$i].", `price`='".import_convert_tax( $product['preisgruppen'][$status_id]['preis_gruppe'.$i], $product['preisgruppen'][$status_id]['is_brutto'], $product['mwst'], $p['products_tax_class_id'] )."'" );
      }
    }
    if( !$res1 )
      return array( 'ok' => FALSE, 'errno' => EIO, 'error' => 'Fehler beim anlegen der Preisgruppen' );
  }

  $pp = new product_price();
  $pp->setPosition( 'admin' );
  $pp->_updateProductsGroupPriceflag( $products_id );

  return array( 'ok' => TRUE );
}


function _do_import_group_permissions( &$product, $products_id )
{
  $cs = export_customers_status();
  $ms = export_multistores();
  $warning = array();

  $group_perm = $shop_perm = array();

  if( is_array($product['shop']['group_permission']) )
  {
    foreach( $product['shop']['group_permission'] as $on_permission )
      $group_perm[$on_permission] = 1;
    foreach( array_keys($cs) as $_cs_id )
    {
      isset($group_perm[$_cs_id]) or $group_perm[$_cs_id] = 0;
    }
  }
  else
  {
    foreach( array_keys($cs) as $_cs_id )
    {
      $group_perm[$_cs_id] = 1;
    }
  }

  if( is_array($product['shop']['multistore_permission']) )
  {
    foreach( $product['shop']['multistore_permission'] as $on_permission )
      $shop_perm[$on_permission] = 1;
    foreach( array_keys($ms) as $_ms_id )
    {
      isset($shop_perm[$_ms_id]) or $shop_perm[$_ms_id] = 0;
    }
  }
  else
  {
    foreach( array_keys($ms) as $_ms_id )
    {
      $shop_perm[$_ms_id] = 1;
    }
  }

  $res = act_db_query( "DELETE FROM ".TABLE_PRODUCTS_PERMISSION." WHERE pid=".(int)$products_id );


  foreach( $group_perm as $_id => $_perm )
  {
    if( _SYSTEM_GROUP_PERMISSIONS == 'blacklist' )
      $permission = $_perm ? 0 : 1;
    else // whitelist
      $permission = $_perm ? 1 : 0;
    if( $permission != 0 )
      $res &= act_db_query( "INSERT INTO ".TABLE_PRODUCTS_PERMISSION." SET pid=".(int)$products_id.", `permission`=".(int)$permission.", `pgroup`='group_permission_".(int)$_id."'" );
  }

  foreach( $shop_perm as $_id => $_perm )
  {
    if( _SYSTEM_GROUP_PERMISSIONS == 'blacklist' )
      $permission = $_perm ? 0 : 1;
    else // whitelist
      $permission = $_perm ? 1 : 0;

    if( $permission != 0 )
      $res &= act_db_query( "INSERT INTO ".TABLE_PRODUCTS_PERMISSION." SET pid=".(int)$products_id.", `permission`=".(int)$permission.", `pgroup`='shop_".(int)$_id."'" );
  }

  return array( 'ok' => TRUE, 'warning'=>$warning );
}


function _do_import_cross_selling( &$product, $products_id )
{
  $warning = array();
  if( act_have_table(TABLE_PRODUCTS_CROSS_SELL) && is_array($product['shop']['xselling']) )
  {
    $res = act_db_query( "DELETE FROM ".TABLE_PRODUCTS_CROSS_SELL." WHERE `products_id`=".(int)$products_id );
    $res = TRUE;
    foreach( $product['shop']['xselling'] as $_idx => $xs )
    {
      $res1 = act_db_query( "SELECT `products_id` FROM ".TABLE_PRODUCTS." WHERE `products_model`='".esc($xs['art_nr'])."'" );
      $_p = act_db_fetch_array( $res1 );
      act_db_free( $res1 );
      if( !is_array($_p) || !$_p['products_id'] )
        continue;
      $res &= act_db_query( "INSERT INTO ".TABLE_PRODUCTS_CROSS_SELL." SET `products_id`=".(int)$products_id.", `products_id_cross_sell`=".(int)$_p['products_id'] );
    }
    if( !$res )
      $warning[] = "Fehler beim schreiben der Cross-Selling-Artikel";
  }

  return array( 'ok' => TRUE, 'warning'=>$warning );
}


function _do_import_attributes( &$product, $p )
{
  if( is_array($product['shop']['attributes']) )
  {
    if( !count($product['shop']['attributes']) && act_have_column(TABLE_PRODUCTS, 'products_master_model') )
    {
      $res1 = act_db_query( "UPDATE ".TABLE_PRODUCTS." SET `products_master_flag`=0 WHERE `products_id`=".(int)$p['products_id'] );
      return array( 'ok' => TRUE );
    }

    if( count($product['shop']['attributes']) )
    {
      if( !act_have_column(TABLE_PRODUCTS, 'products_master_model') )
        return array( 'ok'=>FALSE, 'errno'=>EINVAL, 'error' => 'Das Master/Slave-Modul ist im Shop nicht aktiviert.' );

      $res1 = act_db_query( "UPDATE ".TABLE_PRODUCTS." SET `products_master_flag`=1 WHERE `products_id`=".(int)$p['products_id'] );
      if( !$res1 )
        return array( 'ok'=>FALSE, 'errno'=>EIO, 'error' => 'Fehler beim setzen des Master-Status des Artikels' );
    }

    $res = _do_import_attributes_options( $product['shop']['attributes']['names'], $product['shop']['attributes']['values'] );
    if( !$res )
      return array( 'ok' => FALSE, 'errno' => EIO, 'error' => 'Fehler beim anlegen der Attribute' );

    $res = _do_set_article_attributes( $product, $p,
        $product['shop']['attributes']['combination_advanced'], $product['shop']['attributes']['names'], $product['shop']['attributes']['values'] );
    if( !$res['ok'] )
    {
      return array( 'ok' => FALSE, 'errno' => $res['errno'], 'error' => 'Fehler beim verknüpfen der Attribute mit dem Artikel: '.$res['error'] );
    }

  }
  else if( isset($product['shop']['attributes']) )
  {
    if( act_have_column(TABLE_PRODUCTS, 'products_master_model') )
    {
      $res1 = act_db_query( "UPDATE ".TABLE_PRODUCTS." SET `products_master_flag`=0 WHERE `products_id`=".(int)$p['products_id'] );
      if( !$res1 )
        return array( 'ok'=>FALSE, 'errno'=>EIO, 'error' => 'Fehler beim setzen des Nicht-Master-Status des Artikels' );

      // remove slaves
      $pobj = new actindo_veyton_product();
      $pobj->position = 'admin';

      $result = act_db_query(sprintf('SELECT `products_id` FROM `%s` WHERE `products_master_model` = "%s"', TABLE_PRODUCTS, esc($p['products_model'])));
      while($row = act_db_fetch_array($result)) {
          $pobj->_unset($row['products_id']);
      }
      act_db_free($result);
    }
  }

  return array( 'ok' => TRUE );
}

function _do_set_article_attributes( &$product, &$p, $combination, &$options, &$values )
{
  $products_id = (int)$products_id;
  $result = array( 'ok'=>TRUE );

  $obsolete_art_ids = array();
  $res = act_db_query( "SELECT `products_id`, `products_model` FROM ".TABLE_PRODUCTS." WHERE `products_master_model`='".esc($p['products_model'])."'" );
  while( $row=act_db_fetch_array($res) )
  {
    $obsolete_art_ids[(int)$row['products_id']] = $row['products_model'];
  }
  act_db_free( $res );


  foreach( $combination as $_art_nr => $_arr )
  {
    $_art_nr = utf8_encode($_art_nr);
    $shop_name_value_ids = array();
    $shop_attr_sql = array();
    $shop_product_name_suffixes = array();
    foreach( $_arr['attribute_name_id'] as $_i => $_id )
    {
      $shop_name_id = $options[$_id]['_shop_id'];
      $shop_name_value_ids[$_i] = array('attributes_parent_id' => (int)$shop_name_id );
      $shop_attr_sql[$_i] = "(`attributes_parent_id`=".(int)$shop_name_id;
    }

    $shop_value_ids = array();
    foreach( $_arr['attribute_value_id'] as $_i => $_id )
    {
      $value = $values[$_arr['attribute_name_id'][$_i]][$_id];
      $shop_value_id = $value['_shop_id'];
      $shop_name_value_ids[$_i]['attributes_id'] = (int)$shop_value_id;
      $shop_attr_sql[$_i] .= " AND `attributes_id`=".(int)$shop_value_id.")";
      foreach( $value as $_key => $_n )
      {
        if( $_key != '_shop_id' )
          $shop_product_name_suffixes[$_key] .= " ".$_n;
      }
    }

    $shop_attr_matchcount = count($shop_attr_sql);
    $shop_attr_sql = join( ' OR ', $shop_attr_sql );

//    echo "\$_arr['attribute_name_id']=";
//    var_dump($_arr['attribute_name_id']);
//    echo "\$shop_name_value_ids="; var_dump($shop_name_value_ids);
//    echo "\$shop_product_name_suffixes="; var_dump($shop_product_name_suffixes);
//    echo "\$shop_value_ids="; var_dump($shop_value_ids);
//    var_dump($_arr);

    // match attributes with products
    $attributes_products_id = 0;
    $sql = "SELECT p2a.products_id, COUNT(p2a.products_id) AS matchcount FROM ".TABLE_PRODUCTS_TO_ATTRIBUTES." AS p2a, ".TABLE_PRODUCTS." AS p WHERE p.products_id=p2a.products_id AND p.products_master_model='".esc($p['products_model'])."' AND (".$shop_attr_sql.") GROUP BY p2a.products_id HAVING matchcount=".(int)$shop_attr_matchcount;
    $res = act_db_query( $sql );
    $row = act_db_fetch_array( $res );
    act_db_free( $res );
    if( is_array($row) )
      $attributes_products_id = (int)$row['products_id'];

    if( $attributes_products_id == 0 )
    {
      // create product
      $pobj = new actindo_veyton_product();
      $pobj->position = 'admin';
      $res = $pobj->_copy( $p['products_id'] );
      if( !$res->success )
        return array( 'ok'=>FALSE, 'errno'=>EUNKNOWN, 'error'=>'Fehler beim anlegen des Attributs-Artikels \''.$_art_nr.'\' mit product::_copy.' );

      $res1 = TRUE;
      $attributes_products_id = $res->new_id;
      foreach( $shop_name_value_ids as $_i => $_s )
      {
        $res1 &= act_db_query( "UPDATE ".TABLE_PRODUCTS." SET `products_master_flag`=0, `products_master_model`='".esc($p['products_model'])."' WHERE `products_id`=".(int)$attributes_products_id );
        $res1 &= act_db_query( "REPLACE INTO ".TABLE_PRODUCTS_TO_ATTRIBUTES." SET `products_id`=".(int)$attributes_products_id.", `attributes_id`=".(int)$_s['attributes_id'].", `attributes_parent_id`=".(int)$_s['attributes_parent_id'] );
      }
      if( !$res1 )
        return array( 'ok'=>FALSE, 'errno'=>EIO, 'error'=>'Fehler beim erzeugen des Attributsartikels (Schritt 2)' );
    }

    // re-save the slave product whether we created it or not
    if( $attributes_products_id > 0 )
    {
      // DO NOT delete it
      unset( $obsolete_art_ids[$attributes_products_id] );

      // update product
      $pobj = new actindo_veyton_product();
      $pobj->position = 'admin';

      $p1 = array( );
      $p1['products_id'] = $attributes_products_id;
      $p1['products_model'] = $_art_nr;
      $p1['products_status'] = ($_arr['data']['products_status'] && $p['products_status']) ? 1 : 0;
      $p1['products_quantity'] = $_arr['l_bestand'];
      $p1['products_price'] = import_convert_tax( $_arr['preisgruppen'][0]['grundpreis'], $_arr['preisgruppen'][0]['is_brutto'], $product['mwst'], $p['products_tax_class_id'], TRUE );
      $p1['products_image'] = $p['products_image'];   // will be overwritten when custom images are set
      $p1['products_weight'] = isset($_arr['shop']['art']['products_weight']) ? $_arr['shop']['art']['products_weight'] : $p['products_weight'];
      $p1['products_ean'] = isset($_arr['shop']['art']['products_ean']) ? $_arr['shop']['art']['products_ean'] : $p['products_ean'];
      $p1['date_available'] = $p['date_available'];
      $p1['products_shippingtime'] = (int) $_arr['data']['shipping_status'];

      $p1 = $pobj->actindo_fix_fields_for_update( $p1 );
      foreach( array_keys($p) as $_key )
      {
        if( stripos($_key, 'products_name_') === 0 )   // products_name_[langcode]
        {
          $_langcode = substr( $_key, 14 );
          $p1[$_key] = $p[$_key] . ' ' . $shop_product_name_suffixes[$_langcode];     // append attributes names to products_name because of problems with xt_seo!
        }
      }

      // überflüssige Attributszuweisungen im Slave-Artikel löschen
      $qs = array();
      foreach( $shop_name_value_ids as $_i => $_s )
        $qs[] = "(`attributes_id`=".(int)$_s['attributes_id']." AND `attributes_parent_id`=".(int)$_s['attributes_parent_id'].")";
      $sql = "DELETE FROM ".TABLE_PRODUCTS_TO_ATTRIBUTES." WHERE `products_id`=".(int)$attributes_products_id." AND NOT (".join(' OR ', $qs).")";
      $res = act_db_query( $sql );
      if( !$res )
      {
        $res['error'] = 'Fehler beim löschen der überflüssigen Attribute \''.$_art_nr.'\':'.$res['error'];
        return $res;
      }


      // images
      $res = _do_import_images( $_arr, $p1 );
      if( !$res['ok'] )
      {
        $res['error'] = 'Fehler beim anlegen der Attributs-Artikel-Bilder \''.$_art_nr.'\':'.$res['error'];
        return $res;
      }
      if( $res['no_images'] )
      {
        for( $i=0; $i<_SYSTEM_MORE_PRODUCT_IMAGES; $i++ )
          $p1['image_'.$i] = !empty($p['image_'.$i]) ? $p['image_'.$i] : "";
      }



      if( !is_array($_arr['shop']['desc']) || !count($_arr['shop']['desc']) )
      {
        $_arr['shop']['desc'] = $product['shop']['desc'];
        foreach( $_arr['shop']['desc'] as $num => $description )
        {
          unset( $_arr['shop']['desc'][$num]['products_name'] );      // FOR SEO URLS (taken care for above)
        }
      }
      else
      {
        foreach( $product['shop']['desc'] as $num => $description )
        {
          $lang_id = $description['language_id'];
          $langcode = get_language_code_by_id( $lang_id );
          unset( $description['id'], $description['art_id'], $description['language_id'], $description['language_code'] );

          foreach( $description as $key => $item )
          {
            if( !strlen(trim($_arr['shop']['desc'][$num][$key])) && $key != 'products_name' )
              $_arr['shop']['desc'][$num][$key] = $product['shop']['desc'][$num][$key];
          }
          $_arr['shop']['desc'][$num]['language_id'] = $lang_id;
          $_arr['shop']['desc'][$num]['language_code'] = $langcode;
        }
      }

      // descriptions
      $res = _do_import_descriptions( $_arr, $p1, FALSE );
      if( !$res['ok'] )
      {
        $res['error'] = 'Fehler beim anlegen der Attributs-Artikel-Beschreibungen: \''.$_art_nr.'\':'.$res['error'];
        return $res;
      }

      $res = $pobj->_set( $p1, 'edit' );
      if( $res->failed )
        return array( 'ok'=>FALSE, 'errno'=>EUNKNOWN, 'error'=>'Fehler beim ändern des Attributs-Artikels \''.$_art_nr.'\' mit product::_set' );

      // for some reason the products_status isnt saved (eventhough correctly set in $p1), set manually
      act_db_query(sprintf('UPDATE `%s` SET `products_status` = %d WHERE `products_id` = %d', TABLE_PRODUCTS, $p1['products_status'], $p1['products_id']));

      $_arr['mwst'] = $product['mwst'];
      $p1['products_tax_class_id'] = $p['products_tax_class_id'];
      $res = _do_import_preisgruppen( $_arr, $p1, $p1['products_id'] );
      if( !$res['ok'] )
        return array( 'ok' => FALSE, 'errno' => $res['errno'], 'error' => 'Fehler beim importieren der Preisgruppen für den Attributs-Artikel: '.$res['error'] );


      // do not do this above $pobj->_set( $p1, 'edit' ); !
      $res = _do_import_group_permissions( $product, $attributes_products_id );
      if( !$res['ok'] )
      {
        $res['error'] = 'Fehler beim speichern der Berechtigungen des Attributs-Artikels \''.$_art_nr.'\':'.$res['error'];
        return $res;
      }
      
      $res = _do_import_properties($_arr, $attributes_products_id);
      if(!$res['ok']) {
          $res['warning'] = $warning;
          return $res;
      }
      else {
          if(is_array($res['warning'])) {
              $warning = array_merge($res['warning'], $warning);
          }
      }

      if( version_compare(_SYSTEM_VERSION, '4.0.12', '>=') )
      {
        $res = _do_import_images_step2( $product, $p1 );
        if( !$res['ok'] )
        {
          $res['warning'] = $warning;
          return $res;
        }
      }

    }

  }


//  echo "\$obsolete_art_ids="; var_dump($obsolete_art_ids);
  foreach( $obsolete_art_ids as $_products_id => $_products_model )
  {
    $pobj = new actindo_veyton_product();
    $pobj->position = 'admin';
    $res = $pobj->_unset( $_products_id );
    if( !$res->success )
      return array( 'ok'=>FALSE, 'errno'=>EUNKNOWN, 'error'=>'Fehler beim löschen des nicht mehr benötigten Attributs-Artikels \''.$_products_model.'\' mit product::_unset.' );
  }

  return $result;
}

/**
 * insert (/ move) products options / values
 *
 * here we create (or insert and move) products options.
 *
 * With $reorder_options, products_options_id ASC order has to be the same as in actindo,
 * as we get problems with art_nr's when downloading orders otherwise.
 *
 * You are not expected to understand this.
 *
 * @param bool $reorder_options Reorder option_id's to match the order in actindo?
 * @return bool TRUE success, FALSE error
 */
function _do_import_attributes_options( &$options, &$values )
{

  // 1st: find products_options
  $options_arr = array();
  $res = act_db_query( $q="SELECT d.* FROM ".TABLE_PRODUCTS_ATTRIBUTES." AS a, ".TABLE_PRODUCTS_ATTRIBUTES_DESCRIPTION." AS d WHERE a.`attributes_parent`=0 AND d.attributes_id=a.attributes_id" );
  while( $row=act_db_fetch_array($res) )
  {
    if( !empty($row['attributes_name']) )
      $options_arr[(int)$row['attributes_id']][$row['language_code']] = $row['attributes_name'];
  }
  act_db_free( $res );

  // first try better matches (more languages match)
  uasort( $options_arr, '_attr_opts_sort' );

  foreach( $options as $id => $_arr )
  {
    foreach( $options_arr as $_i => $_oarr )
      if( _attr_opts_cmp($_arr, $_oarr) )
        $options[$id]['_shop_id'] = $_i;
  }


//  act_failsave_db_query( "LOCK TABLES ".TABLE_PRODUCTS_ATTRIBUTES_DESCRIPTION." WRITE, ".TABLE_PRODUCTS_ATTRIBUTES." WRITE, `adodb_logsql` WRITE" );
  $res = TRUE;
  foreach( $options as $id => $_arr )
  {
    if( !$just_get )
    {
      if( !$_arr['_shop_id'] )
      {
        $res &= act_db_query( "INSERT INTO ".TABLE_PRODUCTS_ATTRIBUTES." SET `attributes_parent`=0, `sort_order`=0, `status`=1" );
        $next_id = act_db_insert_id();
        if( $res )
        {
          $options[$id]['_shop_id'] = $_arr['_shop_id'] = $next_id;
          foreach( $_arr as $_code => $_text )
          {
            if( $_code == '_shop_id' )
              continue;
            $res &= act_db_query( $q="INSERT INTO ".TABLE_PRODUCTS_ATTRIBUTES_DESCRIPTION." SET `attributes_id`=".(int)$next_id.", `language_code`='".esc($_code)."', `attributes_name`='".esc($_text)."'" );
//            echo "1=";
//            var_dump($q, $res);
//            echo "\n";
          }
        }
      }
      else
      {
        foreach( $_arr as $_code => $_text )
        {
          if( $_code == '_shop_id' )
            continue;
          $res &= act_db_query( $q="UPDATE ".TABLE_PRODUCTS_ATTRIBUTES_DESCRIPTION." SET `attributes_name`='".esc($_text)."' WHERE `attributes_id`=".(int)$_arr['_shop_id']." AND `language_code`='".esc($_code)."'" );
//          echo "2=";
//          var_dump($q, $res);
//          echo "\n";
        }
      }
      if( !$res )
        break;
    }

//  var_dump($options, $values);
//  echo "RES="; var_dump($res);
//  var_dump( $options );

    $values_arr = array();
    $res = act_db_query( $q="SELECT d.* FROM ".TABLE_PRODUCTS_ATTRIBUTES." AS a, ".TABLE_PRODUCTS_ATTRIBUTES_DESCRIPTION." AS d WHERE a.`attributes_parent`=".(int)$_arr['_shop_id']." AND d.attributes_id=a.attributes_id" );
    while( $row=act_db_fetch_array($res) )
    {
      if( !empty($row['attributes_name']) )
        $values_arr[(int)$row['attributes_id']][$row['language_code']] = $row['attributes_name'];
    }
    act_db_free( $res );
/*
    $res1 = act_db_query( "SELECT * FROM ".TABLE_PRODUCTS_OPTIONS_VALUES.", ".TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS." WHERE ".TABLE_PRODUCTS_OPTIONS_VALUES.".products_options_values_id = ".TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS.".products_options_values_id AND ".TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS.".products_options_id=".(int)$_arr['_shop_id'] );
    while( $row=act_db_fetch_array($res1) )
    {
      if( !empty($row['products_options_values_name']) )
        $values_arr[(int)$row['products_options_values_id']][get_language_code_by_id($row['language_id'])] = $row['products_options_values_name'];
    }
    act_db_free( $res1 );
*/
    uasort( $values_arr, '_attr_opts_sort' );
//    echo '$values_arr=';var_dump($values_arr);

    foreach( $values[$id] as $_id => $_arr1 )
    {
      foreach( $values_arr as $_i => $_oarr )
      {
        if( _attr_opts_cmp($_arr1, $_oarr) )
        {
          $values[$id][$_id]['_shop_id'] = $_i;
          if( !$just_get )
          {
            foreach( $_arr1 as $_code => $_text )
            {
              if( $_code == '_shop_id' )
                continue;
//              $res &= act_db_query( $q="UPDATE ".TABLE_PRODUCTS_OPTIONS_VALUES." SET `products_options_values_name`='".esc($_text)."' WHERE `products_options_values_id`=".$_i." AND `language_id`=".$langid );
              $res &= act_db_query( $q="UPDATE ".TABLE_PRODUCTS_ATTRIBUTES_DESCRIPTION." SET `attributes_name`='".esc($_text)."' WHERE `attributes_id`=".(int)$_i." AND `language_code`='".esc($_code)."'" );
//              echo "3=";
//              var_dump($q, $res);
//              echo "\n";
            }
          }
        }
      }


      if( !$values[$id][$_id]['_shop_id'] && !$just_get )
      {
        $res &= act_db_query( "INSERT INTO ".TABLE_PRODUCTS_ATTRIBUTES." SET `attributes_parent`=".(int)$_arr['_shop_id'].", `sort_order`=0, `status`=1" );
        $vid = act_db_insert_id();
        if( $res )
        {
          $values[$id][$_id]['_shop_id'] = $vid;
          foreach( $_arr1 as $_code => $_text )
          {
            if( $_code == '_shop_id' )
              continue;
            $res &= act_db_query( $q="INSERT INTO ".TABLE_PRODUCTS_ATTRIBUTES_DESCRIPTION." SET `attributes_id`=".(int)$vid.", `language_code`='".esc($_code)."', `attributes_name`='".esc($_text)."'" );
  //            $res &= act_db_query( $q="INSERT INTO ".TABLE_PRODUCTS_OPTIONS_VALUES." SET `products_options_values_id`=".$vid.", `language_id`=".$langid.", `products_options_values_name`='".esc($_text)."'" );
//            echo "4=";
//            var_dump($q, $res);
//            echo "\n";
          }
        }
      }
    }

  }
//  act_failsave_db_query( "UNLOCK TABLES" );

  return $res;
}


function _do_import_properties(&$p, $products_id) {
    static $fields = null;
    if($fields === null) {
        $fields = actindo_get_fields();
        $fields = $fields['fields'];
    }
    
    $res = array(
        'ok' => true,
        'warning' => array(),
    );
    
    if(is_array($p['shop']['properties'])) {
        foreach($p['shop']['properties'] AS $field) {
            if(!isset($fields[$field['field_id']])) {
                // property given that is not defined in shop
                $res['warnings'][] = sprintf('Zusatzfeld "%s" im Shop nicht vorhanden', $field['field_id']);
                continue;
            }
            
            list($tableKey, $columnName) = explode('_', $field['field_id'], 2);
            if($tableKey == 'pd') {
                // i18n field, goes into _products_description
                if(empty($field['language_code'])) $field['language_code'] = 'de';
                $sql = sprintf('
                    UPDATE `%s` SET `%s` = "%s" WHERE `products_id` = %d AND `language_code` = "%s"
                ', TABLE_PRODUCTS_DESCRIPTION, $columnName, esc($field['field_value']), $products_id, $field['language_code']);
            }
            elseif($tableKey == 'p') {
                // non i18n field, goes into _products
                $sql = sprintf('
                    UPDATE `%s` SET `%s` = "%s" WHERE `products_id` = %d
                ', TABLE_PRODUCTS, $columnName, esc($field['field_value']), $products_id);
            }
            else {
                // d'oh?
                continue;
            }
            
            if(!act_db_query($sql)) {
                $res['ok'] = false;
            }
        }
    }
    
    if(empty($res['warning'])) {
        unset($res['warning']);
    }
    return $res;
}


function _get_next_options_id( )
{
  $r = act_db_query( "SELECT MAX(products_options_id) AS maxid FROM ".TABLE_PRODUCTS_OPTIONS );
  $tmp = act_db_fetch_array($r);
  $next_id = $tmp['maxid']+1;
  act_db_free( $r );
  return $next_id;
}

function _get_next_options_values_id( )
{
  $r = act_db_query( "SELECT MAX(products_options_values_id) AS maxid FROM ".TABLE_PRODUCTS_OPTIONS_VALUES );
  $tmp = act_db_fetch_array($r);
  $next_id = $tmp['maxid']+1;
  act_db_free( $r );
  return $next_id;
}

function _attr_opts_sort( $a, $b )
{
  return (count($a) > count($b) ? -1 : (count($a) < count($b) ? 1 : 0));
}

function _attr_opts_cmp( $a, $b )
{
  $keys = array_intersect( array_keys($a), array_keys($b) );
  $same = TRUE;
  foreach( $keys as $k )
    $same &= !strcasecmp($a[$k], $b[$k]);
  return $same;
}


/**
 * Find / create corresponding tax class
 *
 *
 * @param array $taxes_advanced Steuer-Zuordnung
 * @param int $products_tax_class_id Tax-Class-ID, dazu passend
 * @returns array Array( 'ok'=>, 'errno'=> )
 */
function _import_product_to_taxes_advanced( $taxes_adv, $leist_art, &$products_tax_class_id )
{
  $class_to_land_to_percent_xtc = array();
  $leist_art = (int)$leist_art;

  $taxes_advanced = array();
  foreach( $taxes_adv as $_land => $arr )
  {
    $taxes_advanced[$_land] = $arr['prozent'];
  }


  $r = act_db_query( "SELECT tc.tax_class_id,tc.tax_class_title,c.countries_iso_code_2, tr.tax_rate,tr.tax_priority FROM tax_class AS tc, tax_rates AS tr, zones_to_geo_zones AS zgz, countries AS c WHERE tr.tax_class_id=tc.tax_class_id AND zgz.geo_zone_id=tr.tax_zone_id AND c.countries_id=zgz.zone_country_id" );
  while( $row = act_db_fetch_array($r) )
  {
    $country = strtoupper( trim($row['countries_iso_code_2']) );
    if( !isset($taxes_advanced[$country]) )   // need only EU
      continue;
    $class_to_land_to_percent_xtc[(int)$row['tax_class_id']][$country] = (float)$row['tax_rate'];
  }
  act_db_free( $r );

  // just in case
  $ta = array();
  foreach( $taxes_advanced as $_lang => $_percent )
    $ta[strtoupper(trim($_lang))] = (float)$_percent;
  $taxes_advanced = $ta;
  unset( $ta );

  // tricky, innit?
  $diff = array_merge(
    $first= array_diff_assoc($taxes_advanced, $class_to_land_to_percent_xtc[$leist_art]),
            array_diff_assoc($class_to_land_to_percent_xtc[$leist_art], $taxes_advanced)
  );
  if( !count($diff) )
  {
    $products_tax_class_id = $leist_art;
    return array( 'ok' => TRUE );
  }

  // first: delete redundant countries
  $res1 = TRUE;
  foreach( array_keys($diff) as $_code )
    $res1 &= act_db_query( "DELETE FROM zgz USING zones_to_geo_zones AS zgz, countries AS c WHERE zgz.zone_country_id=c.countries_id AND c.countries_iso_code_2='".esc($_code)."'" );

  // second: re-create country<->rate, but only for first diff
  foreach( $first as $_code => $_rate )
  {
    $res = act_db_query( "SELECT `tax_zone_id` FROM tax_rates WHERE `tax_class_id`={$leist_art} AND tax_priority=1 AND tax_rate=".round($_rate,4) );
    $r = act_db_fetch_array( $res );
    act_db_free( $res );
    if( is_array($r) )
      $tax_zone_id = (int)$r['tax_zone_id'];
    else
      $tax_zone_id = 0;

    if( !$tax_zone_id )
    {
      $res = act_db_query( "SELECT MAX(`geo_zone_id`) AS max FROM zones_to_geo_zones" );
      $r = act_db_fetch_array( $res );
      act_db_free( $res );
      $tax_zone_id = max( 8, $r['max'] + 1 );

      $res1 &= act_db_query( "REPLACE INTO geo_zones SET geo_zone_id='".(int)$tax_zone_id."', geo_zone_name='Steuerzone Lieferschwelle', geo_zone_description='', last_modified=NOW(), date_added=NOW()" );

      $res1 &= act_db_query( "INSERT INTO tax_rates SET tax_zone_id='".(int)$tax_zone_id."', tax_class_id={$leist_art}, tax_priority=1, tax_rate='".round($_rate,4)."', `tax_description`='".esc(sprintf("UST %0.1f%%", round($_rate,4)))."', last_modified=NOW(), date_added=NOW()" );
    }

    $res1 &= act_db_query( "INSERT INTO zones_to_geo_zones (zone_country_id,zone_id,geo_zone_id,last_modified,date_added) SELECT `countries_id`,0,'".(int)$tax_zone_id."',NOW(),NOW() FROM `countries` WHERE countries_iso_code_2='".esc($_code)."'" );

    if( $tax_zone_id >= 8 )
    {
      $countries = array();
      $res = act_db_query( "SELECT `countries_iso_code_2` FROM zones_to_geo_zones AS zgz, countries AS c WHERE zgz.zone_country_id=c.countries_id AND zgz.geo_zone_id='".(int)$tax_zone_id."'" );
      while( $r = act_db_fetch_array($res) )
        $countries[] = $r['countries_iso_code_2'];
      act_db_free( $res );
      $countries = join( ', ', $countries );
      $res1 &= act_db_query( "UPDATE geo_zones SET `geo_zone_name`='Steuerzone Lieferschwelle ".esc($countries)."' WHERE `geo_zone_id`=".(int)$tax_zone_id );
    }
  }

  if( !$res1 )
    return array( 'ok' => FALSE, 'errno'=>EIO, 'error'=>'Fehler beim Update der Steuersätze' );

  $products_tax_class_id = $leist_art;
  return array( 'ok' => TRUE );
}


function import_delete_product( $art_nr )
{
  global $db;

  require_once( SHOP_BASEDIR._SRV_WEB_FRAMEWORK.'classes/class.MediaFileTypes.php' );
  require_once( SHOP_BASEDIR._SRV_WEB_FRAMEWORK.'classes/class.MediaData.php' );
  require_once( SHOP_BASEDIR._SRV_WEB_FRAMEWORK.'classes/class.MediaImages.php' );
  require_once( SHOP_BASEDIR._SRV_WEB_FRAMEWORK.'admin/classes/class.adminDB_DataSave.php' );
  require_once( SHOP_BASEDIR._SRV_WEB_FRAMEWORK.'functions/filter_text.inc.php' );
  require_once( SHOP_BASEDIR._SRV_WEB_FRAMEWORK.'classes/class.product.php' );
  require_once( SHOP_BASEDIR._SRV_WEB_FRAMEWORK.'classes/class.product_to_cat.php' );

  $product_query = act_db_query( $q="SELECT `products_id` FROM ".TABLE_PRODUCTS." WHERE `products_model`='".esc($art_nr)."'" );
  $n = act_db_num_rows( $product_query );
  if( !$n )
  {
    return array( 'ok' => FALSE, 'errno' => ENOENT );
  }
  $res = $xtp = act_db_fetch_array($product_query);
  $product_id = (int)$res['products_id'];

  $pobj = new product();
  $pobj->position = 'admin';
  $res1 = $pobj->_unset( $product_id );

  $attr_res = TRUE;
  if( act_have_column(TABLE_PRODUCTS, 'products_master_model') )
  {
    $product_query = act_db_query( $q="SELECT `products_id` FROM ".TABLE_PRODUCTS." WHERE `products_master_model`='".esc($art_nr)."'" );
    while( $xtp = act_db_fetch_array($product_query) )
    {
      $product_id = (int)$xtp['products_id'];
      $pobj = new product();
      $pobj->position = 'admin';
      $res2 = $pobj->_unset( $product_id );
      $attr_res &= $res2->success;
    }
  }

  if( !$res1->success )
    return array( 'ok'=>FALSE, 'errno'=>EUNKNOWN, 'error'=>"Fehler beim löschen des Artikels '{$art_nr}' mit product::_unset" );

  if( !$attr_res )
    return array( 'ok'=>FALSE, 'errno'=>EUNKNOWN, 'error'=>"Fehler beim löschen der Attribute von '{$art_nr}' mit product::_unset" );

  return array( 'ok' => TRUE );
}


function import_product_stock( $art )
{
  if( is_array($art) )
  {
    if( !isset($art['art_nr']) && count($art) )
    {
      $res = array( 'ok' => TRUE, 'success'=>array(), 'failed'=>array() );
      foreach( $art as $_i => $_a )
      {
        $res1 = _import_product_stock( $_a );
        $res['success'][$_i] = $res1['ok'];
        if( !$res1['ok'] )
          $res['failed'][$_i] = $res1;
      }
    }
    else
    {
      $res = _import_product_stock( $art );
    }
  }
  else
    $res = array( 'ok'=> FALSE, 'errno'=>EINVAL );

  return $res;
}

function _import_product_stock( $art )
{
  $res = act_db_query( "SELECT `products_id` FROM ".TABLE_PRODUCTS." WHERE `products_model`='".esc($art['art_nr'])."'" );
  $prod = act_db_fetch_array($res);
  act_db_free( $res );

  if( !is_array($prod) )
    return array( 'ok' => 0, 'errno' => ENOENT, 'error' => 'Keinen Artikel gefunden.' );

  $q = '';
  if( isset($art['shipping_status']) )
    $q .= ', `products_shippingtime`='.(int)$art['shipping_status'];

  if( isset($art['products_status']) )
    $q .= ', `products_status`='.(int)$art['products_status'];

  $res = act_db_query( "UPDATE ".TABLE_PRODUCTS." SET `products_quantity`=".(float)$art['l_bestand']."{$q} WHERE `products_id`=".(int)$prod['products_id'] );
  if( !$res )
    return array( 'ok' => 0, 'errno' => EIO, 'error' => 'Fehler beim Update des Bestandes' );


  if( is_array($art['attributes']) && is_array($art['attributes']['combination_advanced']) )
  {
    foreach( $art['attributes']['combination_advanced'] as $_art_nr => $_val )
    {
      $res = act_db_query( "SELECT `products_id` FROM ".TABLE_PRODUCTS." WHERE `products_model`='".esc($_art_nr)."'" );
      $prod = act_db_fetch_array($res);
      act_db_free( $res );

      if( !is_array($prod) )
        continue;

      $q = '';
      if( isset($_val['data']['shipping_status']) )
        $q .= ', `products_shippingtime`='.(int)$_val['data']['shipping_status'];

      if( isset($_val['data']['products_status']) )
        $q .= ', `products_status`='.(int)$_val['data']['products_status'];

      $res = act_db_query( "UPDATE ".TABLE_PRODUCTS." SET `products_quantity`=".(float)$_val['l_bestand']."{$q} WHERE `products_id`=".(int)$prod['products_id'] );
      if( !$res )
        return array( 'ok' => 0, 'errno' => EIO, 'error' => "Fehler beim Update des Attributsartikels '{$_art_nr}'" );
    }
  }


  return array( 'ok'=>TRUE );
}

?>