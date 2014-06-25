<?php

/**
 * export orders
 *
 * actindo Faktura/WWS connector
 *
 * @package actindo
 * @author  Patrick Prasse <pprasse@actindo.de>
 * @version $Revision: 516 $
 * @copyright Copyright (c) 2007, Patrick Prasse (Schneebeerenweg 26, D-85551 Kirchheim, GERMANY, pprasse@actindo.de)
*/


function export_orders_count( )
{
  $counts = array();

  $res = act_db_query( "SELECT COUNT(*) AS cnt FROM ".TABLE_ORDERS );
  $tmp = act_db_fetch_assoc($res);
  $counts['count'] = (int)$tmp['cnt'];
  act_db_free($res);


  $res = act_db_query( "SELECT MAX(orders_id) AS cnt FROM ".TABLE_ORDERS );
  $tmp = act_db_fetch_assoc($res);
  $counts['max_order_id'] = (int)$tmp['cnt'];
  act_db_free($res);

  return array( 'ok'=>TRUE, 'counts' => $counts );
}


function export_orders_list( $filters=array(), $from=0, $count=0x7FFFFFFF )
{
  isset($filters['start']) or $filters['start'] = (int)$from;
  isset($filters['limit']) or $filters['limit'] = (int)$count;
  !empty($filters['sortColName']) or $filters['sortColName'] = 'order_id';
  !empty($filters['sortOrder']) or $filters['sortOrder'] = 'DESC';

  $gender_map = actindo_get_gender_map( );
  $def_lang = default_lang();

  $mapping = array(
    'order_id' => array('o', 'orders_id'),
    'deb_kred_id' => array('o', 'customers_cid'),
    '_customers_id' => array('o', 'customers_id'),
    'orders_status' => array('o', 'orders_status'),
  );
  $qry = create_query_from_filter( $filters, $mapping );
  if( $qry === FALSE )
    return array( 'ok'=>false, 'errno'=>EINVAL, 'error'=>'Error in filter definition' );


  $orders = array();

  $res = act_db_query( "SELECT o.*, cc.customers_cid AS cc_cid, o.language_code AS `langcode` FROM ".TABLE_ORDERS." AS o LEFT JOIN ".TABLE_CUSTOMERS." AS cc ON (cc.customers_id=o.customers_id) WHERE {$qry['q_search']} ORDER BY {$qry['order']} LIMIT {$qry['limit']}" );
  while( $order = act_db_fetch_assoc($res) )
  {
    /*
     * `comments` in the orders table are varchar(255) and might cut off the customers order comment
     * the full comment can be found in _orders_status_history (which has several rows per order)
     * if comment appears to be cut off (len = 255), find full length comment (_orders_status_history is badly indexed, should have an index on orders_id but hasnt)
     */
    if(strlen($order['comments']) == 255) {
        $check = act_db_query(sprintf('SELECT `comments` FROM `%s` WHERE `orders_id` = %d AND `comments` LIKE "%s%%"', TABLE_ORDERS_STATUS_HISTORY, $order['orders_id'], esc($order['comments'])));
        // order by `orders_status_history_id` might do the trick aswell, not sure if its always the first entry of an order though
        while($r = act_db_fetch_assoc($check)) { // should only yield one row
            $order['comments'] = $r['comments'];
        }
        act_db_free($check);
    }

    $mapping = array(
      'orders_id' => 'order_id',
      'customers_id' => '_customers_id',
      'customers_cid' => 'deb_kred_id',
      'customers_vat_id' => 'customer[ustid]',
      'customers_status' => 'customer[preisgruppe]',
      'billing_name' => 'customer[kurzname]',
      'billing_firstname' => 'customer[vorname]',
      'billing_lastname' => 'customer[name]',
      'billing_company' => 'customer[firma]',
      'billing_street_address' => 'customer[adresse]',
      'billing_suburb' => 'customer[adresse2]',
      'billing_city' => 'customer[ort]',
      'billing_postcode' => 'customer[plz]',
      'billing_state' => 'customer[blnd]',
      'billing_country_code' => 'customer[land]',
      'billing_phone' => 'customer[tel]',
      'billing_fax' => 'customer[fax]',
      'customers_email_address' => 'customer[email]',
      'billing_dob' => 'customer[gebdat]',

      'delivery_name' => 'delivery[kurzname]',
      'delivery_firstname' => 'delivery[vorname]',
      'delivery_lastname' => 'delivery[name]',
      'delivery_company' => 'delivery[firma]',
      'delivery_street_address' => 'delivery[adresse]',
      'delivery_suburb' => 'delivery[adresse2]',
      'delivery_city' => 'delivery[ort]',
      'delivery_postcode' => 'delivery[plz]',
      'delivery_state' => 'delivery[blnd]',
      'delivery_country_code' => 'delivery[land]',
      'delivery_phone' => 'delivery[tel]',
      'delivery_email_address' => 'delivery[email]',

      // 'payment_method' needs special mapping

      'comments' => 'beleg_status_text',

      'last_modified' => 'tstamp',
      'date_purchased' => 'bill_date',

      'currency_code' => 'currency',
      'currency_value' => 'currency_value',

      'langcode' => 'langcode',

      'payment_code' => '_payment_method',

      'orders_status' => 'orders_status',

      'shop_id' => 'subshop_id',
    );
    $actindoorder = _actindo_generic_mapper( $order, $mapping );

    preg_match( '/^(\d{4}-\d{2}-\d{2})(\s+(\d+:\d+:\d+))?$/', $order['date_purchased'], $matches );
    $actindoorder['webshop_order_date'] = $matches[1];
    $actindoorder['webshop_order_time'] = $matches[3];

    if( isset($order['billing_gender']) && isset($gender_map[$order['billing_gender']]) )
      $actindoorder['customer']['anrede'] = $gender_map[$order['billing_gender']];
    if(isset($order['delivery_gender']) && isset($gender_map[$order['delivery_gender']])) {
        $actindoorder['delivery']['anrede'] = $gender_map[$order['delivery_gender']];
    }

    if( empty($actindoorder['customer']['vorname']) || empty($actindoorder['customer']['name']) )
    {
      $n = split( " ", trim($order['billing_name']) );
      $nn = array_pop( $n );
      if( empty($actindoorder['customer']['vorname']) )
        $actindoorder['customer']['vorname'] = join( " ", $n );
      if( empty($actindoorder['customer']['name']) )
        $actindoorder['customer']['name'] = $nn;
    }

    if( empty($actindoorder['delivery']['vorname']) || empty($actindoorder['delivery']['name']) )
    {
      $n = split( " ", trim($order['delivery_name']) );
      $nn = array_pop( $n );
      if( empty($actindoorder['delivery']['vorname']) )
        $actindoorder['delivery']['vorname'] = join( " ", $n );
      if( empty($actindoorder['delivery']['name']) )
        $actindoorder['delivery']['name'] = $nn;
    }

    // if no customers_cid in order table
    if( !$actindoorder['deb_kred_id'] )
      $actindoorder['deb_kred_id'] = (int)$order['cc_cid'];

    if( empty($actindoorder['customer']['gebdat']) )
    {
      $res1 = act_db_query( "SELECT customers_dob FROM ".TABLE_CUSTOMERS_ADDRESSES." WHERE customers_id=".(int)$order['customers_id']." AND `address_book_id`=".(int)$order['billing_address_book_id'] );
      $data = act_db_fetch_assoc($res1);
      act_db_free( $res1 );
      $actindoorder['customer']['gebdat'] = $data['customers_dob'];
    }

    $verfmap = array(
      'xt_invoice' => 'U',
      'xt_paypal' => 'PP',
      'xt_sofortueberweisung' => 'SU',
      'xt_moneybookers_cc' => 'KK',
      'xt_cashondelivery' => 'NN',
      'xt_banktransfer'=>'LSCORE',
    );

    $actindoorder['customer']['verf'] = $verfmap[$order['payment_code']];
    if( is_null($actindoorder['customer']['verf']) )
      $actindoorder['customer']['verf'] = 'VK';         // generic prepaid

    $actindoorder['customer']['langcode'] = strtolower( $order['langcode'] );
    $actindoorder['delivery']['langcode'] = strtolower( $order['langcode'] );

    $actindoorder['val_date'] = $actindoorder['bill_date'];
    !empty($actindoorder['tstamp']) or $actindoorder['tstamp'] = $actindoorder['bill_date'];

    if( $filters['quick'] )
    {
      $orders[] = $actindoorder;
      continue;
    }

    $ord = new order();
    $ord->setPosition( 'admin' );
    $ord->url_data['get_data'] = TRUE;
    $o = $ord->_get( $order['orders_id'] );
    $order_data = $o->data[0];

    _export_payment( $order['orders_id'], $order['payment_code'], $actindoorder, $order, $order_data );


    $res1 = act_db_query( "SELECT cs.customers_status_show_price_tax, c.customers_status FROM ".TABLE_CUSTOMERS_STATUS." AS cs, ".TABLE_CUSTOMERS." AS c WHERE cs.customers_status_id=c.customers_status AND c.customers_id=".(int)$order['customers_id'] );
    $customer_status = act_db_fetch_assoc($res1);
    act_db_free( $res1 );
    $actindoorder['customer']['print_brutto'] = (int)$customer_status['customers_status_show_price_tax'];
    $actindoorder['customer']['_customers_status'] = (int)$customer_status['customers_status'];



    $actindoorder['saldo'] = (float)$o->data[0]['order_total']['total']['plain'];
    $actindoorder['netto2'] = (float)$o->data[0]['order_total']['total_otax']['plain'];

    // TODO: discount

/*
    if( isset($totals['ot_discount']) )
    {
      $totals['ot_discount'] = -abs( $totals['ot_discount'] );
      if( !isset($customer_status) || $customer_status===FALSE || $customer_status['customers_status_show_price_tax'] > 0 )
      {
        $actindoorder['rabatt_betrag'] = round( $p=$totals['ot_discount'] / ($actindoorder['saldo']/$actindoorder['netto2']), 2 )*-1;
      }
      else
      {
        $actindoorder['rabatt_betrag'] = round( $p=$totals['ot_discount'], 2 )*-1;
      }
    }
    else
      $actindoorder['rabatt_betrag'] = 0.00;
*/
    $actindoorder['netto'] = $actindoorder['netto2'] - $actindoorder['rabatt_betrag'];
    $actindoorder['_shoporder'] = $order;

    $orders[] = $actindoorder;
  }
  act_db_free($res);

  return $orders;
}

function _export_payment( $orders_id, $payment_method, &$actindoorder, $order, $order_data )
{
  $data = @unserialize( $order_data['order_data']['orders_data'] );
  is_array($data) or $data = array();
  switch( strtolower($payment_method) )
  {
    case 'xt_banktransfer':
      $actindoorder['customer']['iban'] = $data['banktransfer_iban'];
      $actindoorder['customer']['swift'] = $data['banktransfer_bic'];
      $actindoorder['customer']['bankname'] = $data['banktransfer_bank_name'];
      $actindoorder['customer']['kto_inhaber'] = $data['banktransfer_owner'];
      return TRUE;
  }

  $actindoorder['_payment'] = $data;
  return FALSE;
}



function export_orders_positions( $order_id )
{
  global $order;
  $def_lang = default_lang();
  $langcode = get_language_code_by_id( $def_lang );

//  require_once( 'compat.php' );

  $products = array();

  $ord = new order();
  $ord->setPosition( 'admin' );
  $ord->url_data['get_data'] = TRUE;
  $o = $ord->_get( $order_id );
  if( !is_array($o->data) || !count($o->data) )
    return ENOENT;

  $order = $o->data[0];
  $order = (array)$order;

  foreach( array_keys($order['order_products']) as $i )
  {
    $prod = $order['order_products'][$i];
    $langtext = '';
    $attributes = array();

    if( act_have_table(TABLE_PRODUCTS_TO_ATTRIBUTES) )
    {
      $res1 = act_db_query( "SELECT d1.attributes_name AS option_name, d2.attributes_name AS value_name, a.attributes_id, a.attributes_parent_id FROM ".TABLE_PRODUCTS_TO_ATTRIBUTES." AS a LEFT JOIN ".TABLE_PRODUCTS_ATTRIBUTES_DESCRIPTION." AS d1 ON(d1.attributes_id=a.attributes_parent_id) LEFT JOIN ".TABLE_PRODUCTS_ATTRIBUTES_DESCRIPTION." AS d2 ON(d2.attributes_id=a.attributes_id) WHERE `products_id`=".(int)$prod['products_id']." GROUP BY a.attributes_id, a.attributes_parent_id ORDER BY (d1.language_code='".esc($langcode)."'),(d2.language_code='".esc($langcode)."')" );
      while( $row = act_db_fetch_assoc($res1) )
      {
        $attributes[] = array( $row['option_name'], $row['value_name'] );
      }
      act_db_free( $res1 );
    }

    $product = array(
      'art_nr' => $prod['products_model'],
      'art_nr_base' => $prod['products_model'],
      'art_name' => decode_entities($prod['products_name']),
      'preis' => (float)$prod['products_price'][$prod['allow_tax'] != 0 ? 'plain' : 'plain_otax'],
      'is_brutto' => $prod['allow_tax'] != 0,
      'type' => 'Lief',
      'mwst' => $prod['products_tax_rate'],
      'menge' => $prod['products_quantity'],
      'langtext' => $langtext,
      'attributes' => $attributes,
    );
    $product['vk'] = $product['preis'];

    $products[] = $product;
  }


  foreach( $order['order_total_data'] as $_key => $_val )
  {
    $_key = $_val['orders_total_key'];
    $shipping_type = null;
    $art_nr = $_key;
    switch( $_key )
    {
      // don't need those.
      case 'subtotal':
      case 'subtotal_no_tax':
      case 'tax':
      case 'total':
        continue;

      // already handled above in export_orders_list
      case 'discount':
        continue;

      default:
      case 'shipping':
        $art_nr .= '_'.$order['order_data']['shipping_code'];
      case 'cod_fee':
      case 'coupon':
      case 'gv':
      case 'loworderfee':
      case 'ps_fee':
        $products[] = array(
          'art_nr' => strtoupper($art_nr),
          'art_nr_base' => strtoupper($art_nr),
          'art_name' => $_val['orders_total_name'],
          'preis' => $_val['orders_total_final_price'][$prod['allow_tax'] != 0 ? 'plain' : 'plain_otax'],
          'is_brutto' => $_val['allow_tax'] != 0,
          'type' => 'NLeist',
          'mwst' => $_val['orders_total_tax_rate'],
          'menge' => 1,
        );
        break;
    }
  }

  return $products;
}



?>