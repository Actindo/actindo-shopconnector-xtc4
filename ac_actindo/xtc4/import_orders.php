<?php

/**
 * import orders, specifically: set status, etc
 *
 * actindo Faktura/WWS connector
 *
 * @package actindo
 * @author  Patrick Prasse <pprasse@actindo.de>
 * @version $Revision: 299 $
 * @copyright Copyright (c) 2008, Patrick Prasse (Schneebeerenweg 26, D-85551 Kirchheim, GERMANY, pprasse@actindo.de)
*/

function import_orders_set_status( $oID, $status, $comments, $notify_customer, $notify_comments=0 )
{

  $ord = new order( $oID, -1 );
  $ord->setPosition( 'admin' );
  $ord->url_data['get_data'] = TRUE;
  $o = $ord->_get( $oID );
  if( !is_array($o->data[0]) )
    return array( 'ok'=>FALSE, 'errno'=>ENOENT );

  $res = $ord->_updateOrderStatus( $status, $comments, $notify_customer ? 'true' : 'false', $notify_comments ? 'true' : 'false' );
//  var_dump( $res );
//  if( !$res )
//    return array( 'ok'=>FALSE, 'errno'=>EIO );

  return array( 'ok'=>TRUE );
}



?>