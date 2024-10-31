<?php
/**
 * Priseo core elements
 *
 * @package Priseo
 * @subpackage librairies
 */

/*	Check if file is include. No direct access possible with file url	*/
if ( !defined( 'ABSPATH' ) ) {
	die( 'Access is not allowed by this way' );
}


class syp_ajax
{
	/**
	 *  Look in SYP database for a product with barcode value in syp_product_barcode input.
	 */
	function ajax_syp_search_product_by_barcode()
	{
		if(isset($_POST['product_barcode']) && !empty($_POST['product_barcode']) && is_string($_POST['product_barcode']))
		{
			$barcode_value = $_POST['product_barcode'];
			$request = new syp_sender();

			$certified_product = $request->get_certified_product($barcode_value);
			if(!empty($certified_product) && $certified_product != null)
			{
				$user_product = $request->get_user_product($barcode_value);
				if(empty($user_product))
				{
					echo "
						<div id='product_data_container'>
							<h1 id='syp_search_import_product_title'>". $certified_product['product_name'] . "</h1>

							<div id='product_data_content' class='postbox'>
								<label>".__('Product name : ', 'Priseo')."</label>
								<span id='syp_search_import_product_name' class='single_content'>
									" . $certified_product['product_name'] ."
								</span>

							<label>".__('Product description : ', 'Priseo')."</label>
								<span id='syp_search_import_product_content' class='single_content'>
									" . $certified_product['product_content'] ."
								</span>

							<label>".__('Product weight : ', 'Priseo')."</label>
								<span id='syp_search_import_product_weight' class='single_content'>
									" . $certified_product['product_weight'] ."
								</span>

							<label>".__('Product height : ', 'Priseo')."</label>
								<span id='syp_search_import_product_height' class='single_content'>
									" . $certified_product['product_height'] ."
								</span>

							<label>".__('Product width : ', 'Priseo')."</label>
								<span id='syp_search_import_product_width' class='single_content'>
									" . $certified_product['product_width'] ."
								</span>
							</div>
						</div>
					";
				}
				else
				{
					global $current_user;
					get_currentuserinfo();

					$user_product = syp_core::get_user_product($barcode_value, $current_user);
					$product_url = get_site_url() . '/wp-admin/post.php?post=' . $user_product['product_id'] . '&action=edit';
					echo 'already_inserted||' . $product_url . '||' . $user_product['product_name'];
				}
			}
		}
		else
		{
			echo false;
		}
		die();
	}

	/**
	 * Looks in SYP database for the latest version of a certified product and compare locally
	 * with user product.
	 */
	function ajax_syp_search_product_updates()
	{
		if(isset($_POST['product_barcode']) && !empty($_POST['product_barcode']) && is_string($_POST['product_barcode']))
		{
			$barcode_value = $_POST['product_barcode'];
			$request = new syp_sender();

			$user_product = $request->get_user_product($barcode_value);
			$certified_product = $request->get_certified_product($barcode_value);

			$differences = syp_core::compare_products($certified_product, $user_product);

			if(empty($differences))
			{
				echo "noupdates";
			}
			else
			{
				// Affichage mise à jour
				echo '
					<div id="product_found_container" class="success">

						<h1 id="div_header">'.__('Update found !', 'Priseo').'</h1>
						<div id="product_data_content_found">';

				foreach($differences as $attribute => $value)
				{
					$nice_attribute = syp_core::get_attribute_frontend_label($attribute);
					if($nice_attribute == null)
					{
						$nice_attribute = ucwords(str_replace('_', ' ', $attribute));
					}

					echo "
					<label><strong>$nice_attribute :</strong></label>
					<span id='syp_search_import_$attribute' class='single_content'>
					" . $value . "</span>";
				}
				echo '<button type="button" id="syp_import_update" value="'.$barcode_value.'" class="button">Update</button>
					<span  style="display:none" id="update_spinner"><img alt="spinner" src="'. plugins_url() ."/" .SYP_PLUGIN_DIR .'/templates/ajaxSpinner.gif" /></span></div></div>';
			}
		}
		die();
	}

	/**
	 * Looks in SYP database for others sellers' name and prices for a given product.
	 */
	function ajax_syp_search_product_prices()
	{
		if(isset($_POST['product_barcode']) && !empty($_POST['product_barcode']) && is_string($_POST['product_barcode']))
		{
			$barcode_value = $_POST['product_barcode'];
			$request = new syp_sender('get_certified_product', $barcode_value);

			$certified_product = $request->send_request();
			$sellers = $request->get_product_prices(array('barcode_value' => $barcode_value, 'parent_product_id' => $certified_product['product_id']));
			if(!empty($sellers))
			{
				$html = '';
				foreach($sellers as $product)
				{
					$html .= '
						<tr>
							<td>'.$product["product_author_name"].'</td>
							<td>'.$product["product_price"].'</td>
							<td>'.$product["post_modified"].'</td>
						</tr>';
				}
				echo $html;
			}
			else
			{
				echo false;
			}
		}
	}

	/**
	 * Launch XMLRPC request for linking WPS and SYP accounts.
	 * Updates user_meta on success, delete them otherwise.
	 */
	function ajax_syp_exec_form_admin()
	{
		$statsyourprice_user = get_user_by('login', 'Priseo');

		if(isset($_POST['loginSYP']) && is_string($_POST['loginSYP']) && !empty($_POST['loginSYP']))
		{
			$syp_login = $_POST['loginSYP'];
		}
		if(isset($_POST['passSYP']) && !empty($_POST['passSYP']))
		{
			$syp_pass = $_POST['passSYP'];
		}

		$user_array_info = 	array
		(	'syp_credentials' => array
			(
				'user_login' => $syp_login,
				'user_pass' => $syp_pass,
			),
			'wp_credentials' => array
			(
				'user_pass' => $statsyourprice_user->data->user_pass,
				'user_url' => get_site_url(),
			),
		);

		update_user_meta(get_current_user_id(), '_syp_login', $syp_login);
		update_user_meta(get_current_user_id(), '_syp_pass', $syp_pass);

		$request = new syp_sender('link_wps_account_to_syp', $user_array_info);
		$result = $request->send_request();

		if ($result != false)
		{
			update_user_meta(get_current_user_id(), '_syp_pass', $result['user_pass']);
			update_user_meta(get_current_user_id(), '_syp_abo_state', $result['user_abo_state']);
 			echo true;
		}
		else
		{
			delete_user_meta(get_current_user_id(), '_syp_login');
			delete_user_meta(get_current_user_id(), '_syp_pass');
			delete_user_meta(get_current_user_id(), '_syp_abo_state');
 			echo false;
		}
		die();
	}

	function syp_dashboard_synchronize()
	{
		if(isset($_POST['product_id']) && !empty($_POST['product_id']))
		{
			global $current_user;
			get_currentuserinfo();

			$product_id = $_POST['product_id'];
			$date = explode('-', date('d-m-Y'));
			$time = explode(':', date('H:i:s'));

			$product = wpshop_products::get_product_data($product_id);

			$thumbnail_id = get_post_meta($product_id, '_thumbnail_id', true);
			$image_path = get_post_meta($thumbnail_id, '_wp_attached_file', true);

			$product_args = array
			(
				'post_ID' => $product_id,
				'post_type' => 'wpshop_product',
				'original_post_status' => 'auto-draft',
				'auto_draft' => '0',
				'post_title' => $product['post_title'],
				'post_content' => $product['product_content'],
				'content' => $product['product_content'],
				'hidden_post_status' => 'draft',
				'post_status' => 'publish',
				'hidden_post_password' => '',
				'hidden_post_visibility' => 'public',
				'visibility' => 'public',
				'wpshop_product_attribute' => array
				(
					'decimal' => array
					(
						'product_weight' => $product['product_weight']/1000,
						'product_width' => $product['product_width'],
						'product_height' => $product['product_height'],
						'product_price' => $product['product_price'],
						'product_stock' => $product['product_stock'],
					),
					'varchar' => array
					(
						'product_reference' => $product['product_reference'],
						'barcode' => $product['barcode'],
					),
					'integer' => array
					(
						'tx_tva' => $product['tx_tva'],
						'product_attribute_set_id' => $product['product_attribute_set_id'],
					),
				),
				'jj' => $date[0],
				'mm' => $date[1],
				'aa' => $date[2],
				'hh' => $time[0],
				'mn' => $time[1],
				'ss' => $time[2],
				'hidden_mm' => $date[1],
				'cur_mm' => $date[1],
				'hidden_jj' => $date[0],
				'cur_jj' => $date[0],
				'hidden_aa' => $date[2],
				'cur_aa' => $date[2],
				'hidden_hh' => $time[0],
				'cur_hh' => $time[0],
				'hidden_mn' => $time[1],
				'cur_mn' => $time[1],
				'original_publish' => __('Publish'),
				'publish' => __('Publish'),
				'post_format' => '0',
				'image_meta' => $image_path,
			);

			$request = new syp_sender();
			$new_product_id = $request->add_product_to_syp($product_args);

			if(!empty($new_product_id) && is_numeric($new_product_id))
			{
				$syp_last_update = get_post_meta($product_id, '_syp_last_update', true);
				if(!empty($syp_last_update))
				{
					echo $syp_last_update;
				}
				else
				{
					echo false;
				}
			}
			else
			{
				echo false;
			}
		}
	}


	function syp_dashboard_update()
	{
		if(isset($_POST['product']) && !empty($_POST['product']))
		{
			global $current_user;
			get_currentuserinfo();

			$new_product = $_POST['product'];
			$user_product = syp_core::get_user_product($new_product['barcode'], $current_user);

			foreach($new_product as $attribute_key => $value)
			{
				if(empty($value))
				{
					unset($new_product[$attribute_key]);
				}
			}

			$product_attributes = array();
			$data = wpshop_attributes::get_attribute_list_for_item(wpshop_entities::get_entity_identifier_from_code(WPSHOP_NEWTYPE_IDENTIFIER_PRODUCT), $user_product['product_id']);
 			foreach($data as $attribute)
			{
				if(array_key_exists($attribute->data_type, $product_attributes))
				{
					if(isset($new_product[$attribute->code]))
					{
						$product_attributes[$attribute->data_type][$attribute->code] = $new_product[$attribute->code];
					}
					else
					{
						$product_attributes[$attribute->data_type][$attribute->code] = $user_product[$attribute->code];
					}
				}
				else
				{
					if(isset($new_product[$attribute->code]))
					{
						$product_attributes[$attribute->data_type] = array($attribute->code => $new_product[$attribute->code]);
					}
					else
					{
						$product_attributes[$attribute->data_type] = array($attribute->code => $user_product[$attribute->code]);
					}
				}
			}

			wpshop_attributes::saveAttributeForEntity($product_attributes, wpshop_entities::get_entity_identifier_from_code(wpshop_products::currentPageCode), $user_product['product_id'], get_locale(), '');

			$post['ID'] = $user_product['product_id'];
			if(isset($new_product['product_name']) && !empty($new_product['product_name']))
			{
				$post['post_title'] = $new_product['product_name'];
			}
			if(isset($new_product['product_content']) && !empty($new_product['product_content']))
			{
				$post['post_content'] = $new_product['product_content'];
			}
			wp_update_post($post);

			$request = new syp_sender();
			unset($post['ID']);
			$request->dashboard_update_user_product($new_product['barcode'], $product_attributes, $post);
		}
	}

	function syp_dashboard_auto_update()
	{
		if(isset($_POST['product_id']) && isset($_POST['auto_update']))
		{
			$product_id = $_POST['product_id'];
			$auto_update = $_POST['auto_update'];
			update_post_meta($product_id, '_auto_update', $auto_update);

			$product = wpshop_products::get_product_data($product_id);
			$request = new syp_sender();
			$request->dashboard_update_auto_update_meta($product['barcode'], $auto_update);
		}
	}
}