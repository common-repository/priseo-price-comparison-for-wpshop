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


/**
 * Priseo core element management
 *
 * @since 1
 * @package Priseo
 * @subpackage librairies
 */
class syp_core
{
	/**
	 * Define product page metabox for chart display
	 *
	 * @param object $product The complete definition of the product
	 * @param array $args Complementary parameters for the metabox
	 */
	function syp_product_metabox( $product, $args )
	{
		$barcode = wpshop_attributes::get_attribute_value_content('barcode', $product->ID, WPSHOP_NEWTYPE_IDENTIFIER_PRODUCT);
		$abo_state = get_user_meta(get_current_user_id(), '_syp_abo_state', true);
		$screen = get_current_screen();

		$barcode_value = '';
		if(!empty($barcode))
		{
			$barcode_value = $barcode->value;
		}

		$disabled = '';
		if($abo_state == 'free')
		{
			$disabled = 'disabled="disabled"';
		}

		$read_only = '';
		$search_template = 'syp_search_product_search';
		if('add' != $screen->action)
		{
			$read_only = 'readonly';
			$search_template = 'syp_search_product_search_updates';
		}

		$output = '';
		$output .= syp_display::display_template_element( 'syp_search_product_barcode', array('PRODUCT_BARCODE' => $barcode_value, 'READ_ONLY' => $read_only));
		$output .= syp_display::display_template_element( $search_template, array());
		$output .= syp_display::display_template_element( 'syp_search_product_import', array());
		$output .= syp_display::display_template_element( 'syp_search_product_parameters', array('AUTO_UPDATE_DISABLED' => $disabled));
		$output .= syp_display::display_template_element( 'syp_search_product_prices_btn', array());
		$output .= syp_display::display_template_element( 'syp_search_product_prices_result', array());
		echo $output;
	}

	/**
	 * Compare two wpshop_product on a range of determined fields.
	 * Returns the differences.
	 *
	 * @param array $certified_product
	 * @param array $user_product
	 * @return array
	 */
	static function compare_products($certified_product, $user_product)
	{
		$certified_product = array
		(
			'product_name' => $certified_product['product_name'],
			'product_content' => $certified_product['product_content'],
			'tx_tva' => $certified_product['tx_tva'],
			'product_weight' => $certified_product['product_weight'],
			'product_height' => $certified_product['product_height'],
			'product_width' => $certified_product['product_width'],
			'product_reference' => $certified_product['product_reference'],
		);

		$user_product = array
		(
			'product_name' => $user_product['product_name'],
			'product_content' => $user_product['product_content'],
			'tx_tva' => $user_product['tx_tva'],
			'product_weight' => $user_product['product_weight'],
			'product_height' => $user_product['product_height'],
			'product_width' => $user_product['product_width'],
			'product_reference' => $user_product['product_reference'],
		);

		return array_diff($certified_product, $user_product);
	}

	/**
	 * Authenticate users through basic authentification.
	 * Compare encrypted WP passwords.
	 *
	 * @param string $user_login
	 * @param string $password
	 * @return boolean
	 */
	static function compare_encrypted_passwords( $user_login, $password )
	{
		$user = get_user_by('login', $user_login);

		if(!is_wp_error($user))
		{
			if($user->user_pass == $password)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * Retrieve good-looking label of attribute code for displaying.
	 *
	 * @param string $attribute_code
	 */
	static function get_attribute_frontend_label( $attribute_code )
	{
		global $wpdb;
		$query = $wpdb->prepare('
			SELECT frontend_label
			FROM wp_wpshop__attribute
			WHERE code = %s',
			$attribute_code);

		$response = $wpdb->get_row($query);
		return $response->frontend_label;
	}

	/**
	 * Locally retrieve a WPS_product in WP database according to barcode value.
	 *
	 * @param string $barcode_value
	 * @param WP_User $user
	 * @return array|boolean
	 */
	static function get_user_product( $barcode_value, $user )
	{
		if(get_site_url() != SYP_URL)
		{
			$user_product = self::get_product_by_barcode($barcode_value, 0, $user);
			if(!empty($user_product) && $user_product != null)
			{
				return $user_product;
			}
			else
			{
				return false;
			}
		}
	}

	/**
	 * Retrieve product informationa according to given args.
	 *
	 * @param string $barcode_value
	 * @param int $parent_product_id
	 * @param WP_User $user
	 * @param string $status
	 * @return array
	 */
	static function get_product_by_barcode( $barcode_value, $parent_product_id, $user, $status = 'publish')
	{
		global $wpdb;

		$definition_attribut_codebarre = wpshop_attributes::getElement('barcode', "'valid'", 'code');
		$table_name = WPSHOP_DBT_ATTRIBUTE_VALUES_PREFIX . $definition_attribut_codebarre->data_type;

		$query = $wpdb->prepare('
			SELECT P.*, PM.meta_value AS attribute_set_id, A.*
			FROM '.$wpdb->posts.' AS P
				INNER JOIN '.$wpdb->postmeta.' AS PM ON (PM.post_id=P.ID)
				INNER JOIN '.$table_name.' AS A ON (A.entity_id = P.ID)
			WHERE P.post_status = "'.$status.'"
				AND P.post_parent = %d
				AND A.value = %s',
				$parent_product_id, $barcode_value );

		if($parent_product_id != 0)
		{
			$query .= '
				AND post_author = '. $user->ID .'';
		}

		$query .= '
			LIMIT 1';

		$product = $wpdb->get_row($query);

		$product_data = array();
		$product_meta = array();

		if(!empty($product))
		{
			$product_data['product_id'] = $product->ID;
			$product_data['post_name'] = $product->post_name;
			$product_data['product_name'] = $product->post_title;
			$product_data['post_title'] = $product->post_title;

			$product_data['product_author_id'] = $product->post_author;
			$product_data['product_date'] = $product->post_date;
			$product_data['product_content'] = $product->post_content;
			$product_data['product_excerpt'] = $product->post_excerpt;

			$data = wpshop_attributes::get_attribute_list_for_item(wpshop_entities::get_entity_identifier_from_code('wpshop_product'), $product->ID, get_locale(), WPSHOP_NEWTYPE_IDENTIFIER_PRODUCT);
			foreach($data as $attribute){
				$data_type = 'attribute_value_'.$attribute->data_type;
				$value = $attribute->$data_type;
				if (in_array($attribute->backend_input, array('select','multiple-select', 'radio','checkbox'))) {
					$value = wpshop_attributes::get_attribute_type_select_option_info($value, 'value');
				}

				// Special traitment regarding attribute_code
				switch($attribute->attribute_code) {
					default:
						$value = !empty($value) ? $value : 0;
						break;
				}
				$product_data[$attribute->attribute_code] = $value;
			}

			if ( $product->post_type == WPSHOP_NEWTYPE_IDENTIFIER_PRODUCT_VARIATION) {
				$variation_details = get_post_meta($product->ID, '_wpshop_variations_attribute_def', true);
				foreach ( $variation_details as $attribute_code => $attribute_value) {

					$attribute_definition = wpshop_attributes::getElement($attribute_code, "'valid'", 'code');

					$product_meta['variation_definition'][$attribute_code]['UNSTYLED_VALUE'] = stripslashes(wpshop_attributes::get_attribute_type_select_option_info($attribute_value, 'label', $attribute_definition->data_type_to_use, true));
					$product_meta['variation_definition'][$attribute_code]['NAME'] = $attribute_definition->frontend_label;
					switch( $attribute_definition->backend_input ) {
						case 'select':
						case 'multiple-select':
						case 'radio':
						case 'checkbox':
							$attribute_value = wpshop_attributes::get_attribute_type_select_option_info($attribute_value, 'label', $attribute_definition->data_type_to_use);
							break;
					}
					$product_meta['variation_definition'][$attribute_code]['VALUE'] = stripslashes($attribute_value);
				}
			}

			$product_data['item_meta'] = !empty($product_meta) ? $product_meta : array();
			/*
			 * Get the display definition for the current product for checking custom display
			*/
			$product_data['custom_display'] = get_post_meta($product_id, WPSHOP_PRODUCT_FRONT_DISPLAY_CONF, true);
		}

		return $product_data;
	}

	/**
	 * Retrieve product barcode according to given id
	 *
	 * @param int $product_id
	 */
	static function get_product_barcode($product_id)
	{
		global $wpdb;

		$definition_attribut_codebarre = wpshop_attributes::getElement('barcode', "'valid'", 'code');
		$table_name = WPSHOP_DBT_ATTRIBUTE_VALUES_PREFIX . $definition_attribut_codebarre->data_type;

		$query = $wpdb->prepare('
			SELECT A.value
			FROM '.$wpdb->posts.' AS P
				INNER JOIN '.$table_name.' AS A ON (A.entity_id = P.ID)
			WHERE P.post_status = "publish"
				AND P.ID= %d
				AND A.attribute_id =
				(
					SELECT id
					FROM '.WPSHOP_DBT_ATTRIBUTE.'
					WHERE code = "barcode"
				)',
			$product_id);

		$barcode = $wpdb->get_var($query);
		return $barcode;
	}

	/**
	 * Import the image attachment for a WPSHOP Product from SYP.
	 *
	 * @param int $post_id
	 * @param string $image_meta
	 */
	static function import_attachment($post_id, $image_meta)
	{
		if( !function_exists( 'wp_generate_attachment_data' ) )
			require_once(ABSPATH . "wp-admin" . '/includes/image.php');

		$syp_file = SYP_URL . '/wp-content/uploads/' . $image_meta;
		$local_file = ABSPATH . '/wp-content/uploads/' . $image_meta;

		$filename = explode('/', $image_meta);
		$c = count($filename);
		$filename = $filename[$c-1];


		$local_file = wp_upload_bits( $filename, null, file_get_contents($syp_file));
		$mime = wp_check_filetype($local_file['file']);
		$mime = $mime['type'];

		$attachment_infos = array
		(
			'post_title' => $filename,
			'post_content' => '',
			'post_status' => 'inherit',
			'post_mime_type' => $mime,
			'guid' => $local_file['url'],
		);
		$attachment_id = wp_insert_attachment( $attachment_infos, $local_file['file'], $new_product[1] );

		if ( false !== $local_file['file'] && file_exists( $local_file['file'] ) )
		{
			@set_time_limit( 900 );
			$metadata = wp_generate_attachment_metadata( $attachment_id, $local_file['file'] );

			if ( !is_wp_error( $metadata ) && !empty($metadata))
			{
				$result = wp_update_attachment_metadata( $attachment_id, $metadata );
				if($result)
				{
					update_post_meta($post_id, '_thumbnail_id', $attachment_id);
				}
			}
		}
	}
}

?>