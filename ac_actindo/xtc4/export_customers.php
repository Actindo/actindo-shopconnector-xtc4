<?php

/**
 * export orders
 *
 * actindo Faktura/WWS connector
 *
 * @package actindo
 * @author  Patrick Prasse <pprasse@actindo.de>
 * @version $Revision: 412 $
 * @copyright Copyright (c) 2007, Patrick Prasse (Schneebeerenweg 26, D-85551 Kirchheim, GERMANY, pprasse@actindo.de)
*/


function export_customers_count( )
{
  $counts = array();

  $res = act_db_query( "SELECT MAX(customers_id) AS cnt FROM ".TABLE_CUSTOMERS );
  $tmp = act_db_fetch_assoc($res);
  $counts['max_customers_id'] = (int)$tmp['cnt'];
  act_db_free($res);

  $res = act_db_query( "SELECT MAX(customers_cid) AS deb_kred_id FROM ".TABLE_CUSTOMERS );
  $tmp = act_db_fetch_assoc($res);
  $counts['max_deb_kred_id'] = (int)$tmp['deb_kred_id'];
  act_db_free($res);

  $res = act_db_query( "SELECT COUNT(customers_id) AS cnt FROM ".TABLE_CUSTOMERS );
  $tmp = act_db_fetch_assoc($res);
  $counts['count'] = (int)$tmp['cnt'];
  act_db_free($res);

  return array( 'ok'=>TRUE, 'counts' => $counts );
}


function export_customers_list( $just_list=TRUE, $filters=array() )
{
  $gender_map = actindo_get_gender_map( );
  $def_lang = default_lang();
  $langcode = get_language_code_by_id( $def_lang );

//  $paymentmeans = actindo_get_paymentmeans( );

  $mapping = array(
    '_customers_id' => array('cc', 'customers_id'),
    'deb_kred_id' =>   array('cc', 'customers_cid'),
    'vorname' =>       array('ab', 'customers_firstname'),
    'name' =>          array('ab', 'customers_lastname'),
    'firma' =>         array('ab', 'customers_company'),
    'land' =>          array('bc', 'countryiso'),
    'email' =>         array('cc', 'customers_email_address'),
  );
  $qry = create_query_from_filter( $filters, $mapping );
  if( $qry === FALSE )
    return array( 'ok'=>false, 'errno'=>EINVAL, 'error'=>'Error in filter definition' );

  if( $just_list )
  {
    $sql = "SELECT SQL_CALC_FOUND_ROWS cc.customers_id, cc.customers_email_address, '' AS language, cc.customers_cid, ab.customers_gender, ab.customers_firstname, ab.customers_lastname, ab.customers_company, ab.customers_street_address, ab.customers_postcode, ab.customers_city, ab.customers_country_code AS countryiso FROM (".TABLE_CUSTOMERS." AS cc, ".TABLE_CUSTOMERS_ADDRESSES." AS ab) WHERE cc.customers_id=ab.customers_id AND ab.address_class='default' AND {$qry['q_search']} ORDER BY {$qry['order']}, cc.`customers_id` DESC LIMIT {$qry['limit']}";
  }
  else
  {
    $sql = "SELECT SQL_CALC_FOUND_ROWS cc.customers_id, cc.customers_email_address, '' AS language, cc.customers_cid, cc.*, ab.*, ab.customers_country_code AS countryiso, cs.customers_status_show_price_tax FROM (".TABLE_CUSTOMERS." AS cc, ".TABLE_CUSTOMERS_ADDRESSES." AS ab) LEFT JOIN ".TABLE_CUSTOMERS_STATUS." AS cs ON (cs.customers_status_id=cc.customers_status) WHERE cc.customers_id=ab.customers_id AND ab.address_class='default' AND {$qry['q_search']} ORDER BY {$qry['order']}, cc.`customers_id` DESC LIMIT {$qry['limit']}";
  }

  $res = act_db_query( $sql );

  $res1 = act_db_query( "SELECT FOUND_ROWS()" );
  $count = act_db_fetch_row( $res1 );
  act_db_free( $res1 );

  while( $customer = act_db_fetch_assoc($res) )
  {
    $id = (int)$cust['customers_id'];
    $delivery_id = (int)$cust['address_book_id'];

    if( $just_list )
    {
      $actindocustomer = array(
        'deb_kred_id' => (int)($customer['customers_cid'] > 0 ? $customer['customers_cid'] : 0),
        'anrede' => empty($customer['customers_company']) ? 'Firma' : $gender_map[$customer['customers_gender']],
        'kurzname' => !empty($customer['customers_company']) ? $customer['customers_company'] : $customer['customers_lastname'],
        'firma' => $customer['customers_company'],
        'name' => $customer['customers_lastname'],
        'vorname' => $customer['customers_firstname'],
        'adresse' => $customer['customers_street_address'],
        'plz' => $customer['customers_postcode'],
        'ort' => $customer['customers_city'],
        'land' => $customer['countryiso'],
        'email' => $customer['customers_email_address'],
        '_customers_id' => (int)$customer['customers_id'],
      );
    }
    else
    {
      $actindocustomer = array(
        'deb_kred_id' => (int)($customer['customers_cid'] > 0 ? $customer['customers_cid'] : 0),
        'anrede' => empty($customer['customers_company']) ? 'Firma' : $gender_map[$customer['customers_gender']],
        'kurzname' => !empty($customer['customers_company']) ? $customer['customers_company'] : $customer['customers_lastname'],
        'firma' => $customer['customers_company'],
        'name' => $customer['customers_lastname'],
        'vorname' => $customer['customers_firstname'],
        'adresse' => $customer['customers_street_address'],
        'adresse2' => $customer['customers_suburb'],
        'plz' => $customer['customers_postcode'],
        'ort' => $customer['customers_city'],
        'land' => $customer['countryiso'],
        'tel' => $customer['customers_phone'],
        'gebdat' => $customer['customers_dob'],
        'fax' => $customer['customers_fax'],
        'ustid' => $customer['customers_vat_id'],
        'email' => $customer['customers_email_address'],
        'print_brutto' => $customer['customers_status_show_price_tax'] ? 1 : 0,
        'preisgruppe' => $customer['customers_status'],
        '_customers_id' => (int)$customer['customers_id'],
        'currency' => 'EUR',

        'delivery_addresses' => array(),
      );


      $sql = "SELECT ab.*, ab.customers_country_code AS countryiso FROM ".TABLE_CUSTOMERS_ADDRESSES." AS ab WHERE ab.customers_id=".(int)$customer['customers_id'];
      $res2 = act_db_query( $sql );
      while( $delivery = act_db_fetch_array($res2) )
      {
        $actindodelivery = array(
          'delivery_id' => (int)$delivery['address_book_id'],
          'delivery_anrede' => empty($delivery['customers_company']) ? 'Firma' : $gender_map[$delivery['customers_gender']],
          'delivery_kurzname' => !empty($delivery['customers_company']) ? $delivery['customers_company'] : $delivery['customers_lastname'],
          'delivery_firma' => $delivery['customers_company'],
          'delivery_name' => $delivery['customers_lastname'],
          'delivery_vorname' => $delivery['customers_firstname'],
          'delivery_adresse' => $delivery['customers_street_address'],
          'delivery_adresse2' => $delivery['customers_suburb'],
          'delivery_plz' => $delivery['customers_postcode'],
          'delivery_ort' => $delivery['customers_city'],
          'delivery_land' => $delivery['countryiso'],
        );
        if( $delivery['address_class'] == 'default' )
        {
          $actindodelivery['delivery_as_customer'] = 1;
          $actindocustomer = array_merge( $actindocustomer, $actindodelivery );
        }
        else
          $actindocustomer['delivery_addresses'][] = $actindodelivery;
      }
    }

    $customers[] = $actindocustomer;
  }
  act_db_free( $res );


  return array( 'ok'=>TRUE, 'customers'=>$customers, 'count'=>$count[0] );
}


?>