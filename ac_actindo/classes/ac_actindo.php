<?php


require_once( 'error.php' );
require_once( 'util.php' );
require_once( 'xmlrpc/xmlrpc_client.php' );


class actindo
{
  var $actindo_func;


  function actindo( )
  {
    global $db;
    $row = null;

    $this->actindo_func = new actindo_func();
    $res = $db->Execute( "SELECT config_value FROM ".TABLE_PLUGIN_CONFIGURATION." WHERE config_key='ACTINDO_SHOP_ID'" );
    if( is_object($res) )
    {
      $row = $res->FetchRow();
      $res->Close();
    }

    if( !is_array($row) || empty($row['config_value']) )
    {
      $db->Execute( "UPDATE ".TABLE_PLUGIN_CONFIGURATION." SET config_value=".$db->Quote(md5($_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].time().rand(0, getrandmax())))." WHERE config_key='ACTINDO_SHOP_ID'" );
    }
  }



  /**
   * Encrypt using XOR
   */
  function x_Encrypt($string, $key)
  {
    for($i=0; $i<strlen($string); $i++)
    {
      for($j=0; $j<strlen($key); $j++)
      {
        $string{$i} = $string{$i}^$key{$j};
      }
    }

    return $string;
  }

  function glue_str($array, $parent='')
  {
    $params = array();
    foreach ($array as $k => $v)
    {
      if (is_array($v))
        $params[] = $this->glue_str($v, (empty($parent) ? rawurlencode($k) : $parent . '[' . rawurlencode($k) . ']'));
      else
        $params[] = (!empty($parent) ? $parent . '[' . rawurlencode($k) . ']' : rawurlencode($k)) . '=' . rawurlencode($v);
    }

    return implode('&', $params);
  }



  function actindo_token_init( $username, $password, $mand_id )
  {
    global $db;

    $token = null;

    $res = $this->actindo_func->login( $username, $password, $mand_id, FALSE );
    if( $res )
    {
      return array( 'ok' => FALSE, 'message' => 'Fehler beim Login: '.actstrerror($res).'. Bitte &uuml;berpr&uuml;fen Sie Benutzername und Passwort!' );
    }

    $res = $this->actindo_func->get_token( );
    if( !is_string($res) )
    {
      $db->Execute( "UPDATE ".TABLE_PLUGIN_CONFIGURATION." SET config_value='' WHERE config_key='ACTINDO_TOKEN'" );
      return array( 'ok' => FALSE, 'message' => 'Konnte token nicht generieren: '.actstrerror($res) );
    }
    $token = $res;

    $this->actindo_func->logout( );

    $db->Execute( "UPDATE ".TABLE_PLUGIN_CONFIGURATION." SET config_value=".$db->Quote($token)." WHERE config_key='ACTINDO_TOKEN'" );
    $db->Execute( "UPDATE ".TABLE_PLUGIN_CONFIGURATION." SET config_value='' WHERE config_key='ACTINDO_SID'" );
    $db->Execute( "UPDATE ".TABLE_PLUGIN_CONFIGURATION." SET config_value='' WHERE config_key='ACTINDO_PASSWORD'" );

    return array( 'ok' => TRUE, 'token' => $token );
  }

  function actindo_login( )
  {
    global $db;
    $cfg = array();

    // use $cfg here, as the constants sometimes give strage timing problems!

    $res = $db->Execute( "SELECT config_key,config_value FROM ".TABLE_PLUGIN_CONFIGURATION." WHERE config_key LIKE 'ACTINDO\_%'" );
    while (!$res->EOF)
    {
      $row = $res->FetchRow();
      $cfg[$row['config_key']] = $row['config_value'];
    }
    $res->Close();

    $sid = $cfg['ACTINDO_SID'];
    if( !empty($sid) )
    {
      $this->actindo_func->sid = $sid;
      $res = $this->actindo_func->ping( );
      if( $res != 'pong' )
        $this->actindo_func->sid = $sid = '';
    }

    if( empty($cfg['ACTINDO_TOKEN']) || empty($cfg['ACTINDO_USERNAME']) || empty($cfg['ACTINDO_MAND_ID']) )
    {
      return array( 'ok' => FALSE, 'not_initialized'=>TRUE );
    }

    if( empty($sid) )
    {
      $res = $this->actindo_func->login( $cfg['ACTINDO_USERNAME'], $cfg['ACTINDO_TOKEN'], $cfg['ACTINDO_MAND_ID'], TRUE );
      if( $res )
      {
        return array( 'ok' => FALSE, 'message' => 'Fehler beim Login: '.actstrerror($res).'.' );
      }
    }
    $db->Execute( "UPDATE ".TABLE_PLUGIN_CONFIGURATION." SET config_value=".$db->Quote($this->actindo_func->sid)." WHERE config_key='ACTINDO_SID'" );
    $db->Execute( "UPDATE ".TABLE_PLUGIN_CONFIGURATION." SET config_value='' WHERE config_key='ACTINDO_PASSWORD'" );
    return array( 'ok'=>TRUE );
  }


  function actindo_check_shop_connection( )
  {
    global $db;
    $shop_id = "";

    $row = null;
    $res = $db->Execute( "SELECT config_value FROM ".TABLE_PLUGIN_CONFIGURATION." WHERE config_key='ACTINDO_SHOP_ID'" );
    if( is_object($res) )
    {
      $row = $res->FetchRow();
      $res->Close();
    }
    $shop_id = !is_null($row['config_value']) ? $row['config_value'] : "";

    $res = $this->actindo_func->check_shop_connection( $shop_id );
    return $res;
  }



  function actindo_order( $is_test=1 )
  {
    global $db;

    $is_test = (int)$is_test;
    $baseurl = ACTINDO_BASEURL;


    if( empty($_SERVER['HTTP_HOST']) )
    {
      // TODO!!
      $res = $db->Execute( "SELECT * FROM ".TABLE_MANDANT_CONFIG." WHERE 1 ORDER BY shop_id ASC LIMIT 1" );
      $first_shop = $res->FetchRow();
  //    var_dump($first_shop);
    }
    else
    {
      $url = ($_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$GLOBALS['sys_dir'];
    }

    $m = $u = array();
    $r = array(
      'shop' => array(
//        'p' => // set below
        'url' => $url,
      ),
    );


    $handle = (isset($_SESSION['admin_user']) && is_array($_SESSION['admin_user']) && !empty($_SESSION['admin_user']['user_name'])) ? $_SESSION['admin_user']['user_name'] : 'admin';
    $res = $db->Execute( $q="SELECT * FROM ".TABLE_ADMIN_ACL_AREA_USER." WHERE handle=".$db->Quote($handle) );
    $admin_user = $res->FetchRow();
    if( $admin_user['firstname'] != 'Admin' && $admin_user['lastname'] != 'Admin' )
    {
      $m['name'] = $admin_user['lastname'];
      $m['vorname'] = $admin_user['firstname'];
      $u['email'] = $admin_user['email'];
    }
    $r['shop']['p'] = $admin_user['passwd'];
    $r['shop']['login'] = $admin_user['handle'];

    $m['land_2digit'] = _STORE_COUNTRY;

    $arr = compact( 'm', 'u', 'r' );
    $str = $this->glue_str($arr);

    $token_len = strlen( $str );

    $tokens = '###';
    if( function_exists('curl_init') )
    {
      $res = curl_init( ACTINDO_BASEURL.'subscribetoken.php?length='.(int)$token_len );
      curl_setopt( $res, CURLOPT_RETURNTRANSFER, true );
      $tokens = curl_exec( $res );
      curl_close( $res );
    }
    if( empty($tokens) || strlen($tokens) <= 10 )
      $tokens = rawurldecode('%D0%CE%DBad%7C%90%40%F0%B7%A6CzE%DC%B0%9Ab%AC%89%A5x%8F%82%91%3A-5z%86zJTV%97%A5%BE%28%D1%AF%CCw%DFF%A8%BB%E3B%1E%90%B8%B0%F4G2%86nL%A7%D4%BE%22%1E%FFe%A2%A4%24%B7u%BF%84%D9%9F%B6%82Z%9A%B0e%2Ah%15%1F%9C4%91%F7l9%CB%2BG%D6%2A%98y%BA%A800h%A0%F5%F3WxN%DD%28%9F%F4%7D%A0%FF%1A%C1%91%FD-%B6%C9D%E9%9F%5B%82%18%15%2B52%80%C1%28s%19%8C%AD%E3%A1M%D8%1F%DA%D7%26%9Bi%23%B4%1F%D9%E5%F5x%40x%7CB%8F%9E%60%FB_uoe%ED%1DH%8EV%20%9A0%E4%AC%B7M%BClY%95QN%F9%7E%B2v%ACB%14%F9%3D%60n%99%B2%5C%A2%E7%D7%E4%F4q%15%D8%1D%B8%26%C6%24k%5Bb%A5T%CCX%B7y%86%B7r%B0%18%CDI%B6%2A%D7%9D%ED%BB%91_%BCjiu%7C%2F%86%D4v%D4z%B7%A0%BEn%19E%26x%E1%2AF%2A%CD%5C%EDjJ%A9%E8%95fR%EB%C7%BB%1AN%90%7D%22%F64%AF%B5%8F%B5%E6%A1-%C8%B8_%DE%85%A8%CC%DC%DEv%C4t%C8%17_%90%BFf%CAO%D0%D9F%F0%88%E8%7F%3D%CF%21W%97%C5%A3vKKC%27%2A%A5%D8%8An%DB%D6%EA%9B%3C%B5%D7%F8%8F%1D%E9%17%F2iA%C1w%84Y%3C').'###0';

    $tokens = split( '###', $tokens );
    $str = $this->x_Encrypt( $str, $tokens[0] );

    $qry = array(
      '__is_test' => $is_test,
      'init' => 'init',
      'from_external' => 1,
      'data' => $str,
      'token' => $tokens[1],
      'r' => array(
        'affiliate_id' => 76002,
        'no_confirm_needed' => 1,
      ),
    );
    $qry = $this->glue_str( $qry );

    $address = ACTINDO_BASEURL.'subscribe.php?'.$qry;

/*
    $inner_html = <<<END
    <div><img src="{$baseurl}/xtc_image/bestellen.jpg"></div>
    <div><iframe src= style="border: none; background-color: white; padding: 0px; overflow: hidden; width: 550px; height: 650px; margin-top: 5px;" frameborder="no"></iframe></div>
END;
*/

    return $address;
  }





  /* ****************** OUTPUT functions start here... ************************ */

  function page( $pagename )
  {
    if( is_null($pagename) || !is_string($pagename) )
      $pagename = 'startpage';

    switch( $pagename )
    {
      case 'order_test':
      case 'order':
        return $this->page_subscribe( $pagename == 'order_test' );
//      case 'mainpage':
//        return $this->page_mainpage();
      case 'config':
        return $this->page_config();
      case 'startpage':
      default:
        return $this->page_startpage();
    }
  }

  function page_subscribe( $is_test=1 )
  {
    $template = new Template();

    $tpl_data = array(
      'address' => $this->actindo_order( $is_test ),
      'actindo_baseurl' => ACTINDO_BASEURL,
    );
    $tpl = 'subscribe.tpl';
    $template->getTemplatePath($tpl, 'ac_actindo', '', 'plugin');
    $inner_html = $template->getTemplate( 'ac_actindo_subscribe', $tpl, $tpl_data );
    return $inner_html;
  }


  function page_startpage( )
  {
    global $db;
    $template = new Template();

    $baseurl = ACTINDO_BASEURL;

    if( $_REQUEST['do_action'] == 'order_test' || $_REQUEST['do_action'] == 'order' )
    {
      return "{ success: true, load_page: '".$_REQUEST['do_action']."' }\n";
    }
    else if( $_REQUEST['do_action'] == 'already_customer' )
    {
      return "{ success: true, load_page: 'config' }\n";
    }
    else
    {
      $res = $this->actindo_login( );
//      var_dump( $res);
      if( !is_array($res) || $res['not_initialized'] )
      {
        $tpl_data = array(
        );
        $tpl = 'startpage.tpl';
        $template->getTemplatePath($tpl, 'ac_actindo', '', 'plugin');
        $inner_html = $template->getTemplate( 'ac_actindo_startpage', $tpl, $tpl_data );
      }
      else
      {
        if( $res['ok'] )
        {
          {
            $actindosession = rawurlencode( $this->actindo_func->get_application_sid() );
            $tpl_data = array(
              'actindosession' => $actindosession,
              'actindo_baseurl' => ACTINDO_BASEURL,
            );
            $tpl = 'mainpage.tpl';
            $template->getTemplatePath($tpl, 'ac_actindo', '', 'plugin');
            $inner_html = $template->getTemplate( 'ac_actindo_mainpage', $tpl, $tpl_data );
          }
        }
        else
        {
          $tpl_data = array(
          );
          $tpl = 'startpage.tpl';
          $template->getTemplatePath($tpl, 'ac_actindo', '', 'plugin');
          $inner_html = $template->getTemplate( 'ac_actindo_startpage', $tpl, $tpl_data );
        }
      }
    }

    return $inner_html;
  }


  function page_config( )
  {
    global $db;

    $template = new Template();

    if( isset($_POST['do_action']) )
    {
      if( $_POST['do_action'] == 'save_config' )
      {
        if( stripos($_POST['actindo_mand_id'], 'z.B.') !== FALSE )
          $_POST['actindo_mand_id'] = "";
        if( stripos($_POST['actindo_username'], 'z.B.') !== FALSE )
          $_POST['actindo_username'] = "";
        $db->Execute( "UPDATE ".TABLE_PLUGIN_CONFIGURATION." SET config_value=".$db->Quote($_POST['actindo_username']).", last_modified=NOW() WHERE config_key='ACTINDO_USERNAME'" );
        $db->Execute( "UPDATE ".TABLE_PLUGIN_CONFIGURATION." SET config_value=".$db->Quote($_POST['actindo_mand_id']).", last_modified=NOW() WHERE config_key='ACTINDO_MAND_ID'" );
        $db->Execute( "UPDATE ".TABLE_PLUGIN_CONFIGURATION." SET config_value='', last_modified=NOW() WHERE config_key='ACTINDO_SID'" );
        $db->Execute( "UPDATE ".TABLE_PLUGIN_CONFIGURATION." SET config_value='', last_modified=NOW() WHERE config_key='ACTINDO_PASSWORD'" );
        $db->Execute( "UPDATE ".TABLE_PLUGIN_CONFIGURATION." SET config_value='', last_modified=NOW() WHERE config_key='ACTINDO_TOKEN'" );
        if( empty($_POST['actindo_mand_id']) && empty($_POST['actindo_username']) )
        {
          return json_encode( array('success'=>true, 'message'=>"Die Account-Anbindung wurde deaktiviert.", 'load_page'=>'startpage') );
        }
        else
        {
          $res = $this->actindo_token_init( $_POST['actindo_username'], $_POST['actindo_password'], $_POST['actindo_mand_id'] );
          if( !$res['ok'] )
          {
            return json_encode( array('success'=>false, 'error'=>$res['message']) );
          }
          else
          {
            return json_encode( array('success'=>true, 'message'=>"Ihr actindo Account wurde angebunden.", 'load_page'=>'startpage') );
          }
        }
      }
      return;
    }

    $tpl_data = array(
      'actindo_mand_id' => ACTINDO_MAND_ID,
      'actindo_username' => ACTINDO_USERNAME,
    );
//    var_dump(get_defined_constants());
    $tpl = 'config.tpl';
    $template->getTemplatePath($tpl, 'ac_actindo', '', 'plugin');
    $inner_html = $template->getTemplate( 'ac_actindo_config', $tpl, $tpl_data );
    return $inner_html;
  }




}


?>
