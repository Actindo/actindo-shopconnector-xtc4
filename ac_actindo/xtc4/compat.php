<?php

function act_get_geo_zone_code( $country_id )
{
  $geo_zone_query = act_db_query("select geo_zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where zone_country_id = '" . $country_id . "'");
  $geo_zone = act_db_fetch_array($geo_zone_query);
  return $geo_zone['geo_zone_id'];
}


require_once( SHOP_BASEDIR._SRV_WEB_FRAMEWORK.'classes/class.product.php' );
class actindo_veyton_product extends product
{

  function actindo_fix_fields_for_update( $p1 )
  {
    $p = $p1;
    $old_product = $this->_get( $p['products_id'] );
    foreach( $old_product->data[0] as $_key => $_val )
    {
      if( (stripos($_key, "url_text") === 0 || $_key == 'products_price') && empty($p[$_key]) )      // if url_text_[langcode] is not set, set it here
        $p[$_key] = $_val;
    }
    return $p;
  }


	function _copy($ID)
  {
/*
    if( version_compare(_SYSTEM_VERSION, '4.0.11', 'gt') )
    {
      return parent::_copy($ID);
    }
    else
*/
    {
      return $this->_actindo_copy($ID);
    }
  }

  function _actindo_copy($ID)
  {
		global $xtPlugin,$db,$language,$filter,$seo,$customers_status;
		if ($this->position != 'admin') return false;

		$ID=(int)$ID;
		if (!is_int($ID)) return false;

		($plugin_code = $xtPlugin->PluginCode('class.product.php:_copy_top')) ? eval($plugin_code) : false;
		if(isset($plugin_return_value))
		return $plugin_return_value;

		$obj = new stdClass;

		// Product Data:
		$p_table_data = new adminDB_DataRead($this->_table, $this->_table_lang, $this->_table_seo, $this->_master_key, '', '', $this->perm_array);
		$p_data = $p_table_data->getData($ID);
        $p_data = $p_data[0];

        $old_product = $p_data[$this->_master_key];

		unset($p_data[$this->_master_key]);

		$oP = new adminDB_DataSave(TABLE_PRODUCTS, $p_data);
		$objP = $oP->saveDataSet();

		$obj->new_id = $objP->new_id;
		$p_data[$this->_master_key] = $objP->new_id;

		$oPD = new adminDB_DataSave(TABLE_PRODUCTS_DESCRIPTION, $p_data, true);
		$objPD = $oPD->saveDataSet();

		// Cat Data:
		$c_table_data = new adminDB_DataRead(TABLE_PRODUCTS_TO_CATEGORIES, null, null, 'categories_id', 'products_id='.$old_product);
		$c_data = $c_table_data->getData();

		for ($i = 0; $i < count($c_data); $i++) {
			$c_data[$i]['products_id'] = $obj->new_id;
       		$oC = new adminDB_DataSave(TABLE_PRODUCTS_TO_CATEGORIES, $c_data[$i], false, __CLASS__);
        	$objC2P = $oC->saveDataSet();
	    }

		// Permissions
		if(_SYSTEM_SIMPLE_GROUP_PERMISSIONS!='true'){
			$set_perm = new item_permission($this->perm_array);
			$set_perm->_saveData($p_data, $p_data[$this->_master_key]);
		}else{
			$set_perm = new item_permission($this->perm_array);
			$set_perm->_setSimplePermissionID('', $p_data[$this->_master_key], 'categories_id', $this->_master_key, TABLE_CATEGORIES, TABLE_PRODUCTS, TABLE_PRODUCTS_TO_CATEGORIES);
		}

		// Media
    if( version_compare(_SYSTEM_VERSION, '4.0.12', '<') )
    {
      $mi = new MediaImages();
      $mi->_setImageFields('product', $data, $this->_master_key);
    }

		// Special Price
		$s_table_data = new adminDB_DataRead(TABLE_PRODUCTS_PRICE_SPECIAL, null, null, 'id', ' products_id='.$ID);
		$s_data = $s_table_data->getData();

		$s_count = count($s_data);

		for ($i = 0; $i < $s_count; $i++) {
			unset($s_data[$i]['id']);
			$s_data[$i]['products_id'] = $obj->new_id;
       		$oS = new adminDB_DataSave(TABLE_PRODUCTS_PRICE_SPECIAL, $s_data[$i], false, __CLASS__);
        	$objS2P = $oS->saveDataSet();
	    }

		// Group Price:
	    foreach ($customers_status->_getStatusList('admin', 'true') as $key => $val) {

			$g_table_data = new adminDB_DataRead(TABLE_PRODUCTS_PRICE_GROUP.$val['id'], null, null, 'id', ' products_id='.$ID);
			$g_data = $g_table_data->getData();

			$g_count = count($g_data);

			for ($i = 0; $i < $g_count; $i++) {
				unset($g_data[$i]['id']);
				$g_data[$i]['products_id'] = $obj->new_id;
	       		$oG = new adminDB_DataSave(TABLE_PRODUCTS_PRICE_GROUP.$val['id'], $g_data[$i], false, __CLASS__);
	        	$objG2P = $oG->saveDataSet();
		    }
	    }

	    ($plugin_code = $xtPlugin->PluginCode('class.product.php:_copy_bottom')) ? eval($plugin_code) : false;

// actindo, PP: commented out because it is already set above
//		$obj = new stdClass;
		$obj->success = true;
		return $obj;
  }


}

?>