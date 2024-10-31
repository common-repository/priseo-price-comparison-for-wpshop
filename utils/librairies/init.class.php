<?php

class syp_init
{
	/**
	 *	Admin javascript "header script" part definition
	 */
	function admin_print_js() {

		/*	Désactivation de l'enregistrement automatique pour certains type de post	*/
		global $post;
		if ( $post && ( (get_post_type($post->ID) === WPSHOP_NEWTYPE_IDENTIFIER_ORDER) ||  (get_post_type($post->ID) === WPSHOP_NEWTYPE_IDENTIFIER_MESSAGE) || (get_post_type($post->ID) === WPSHOP_NEWTYPE_IDENTIFIER_ENTITIES) ) ) {
			wp_dequeue_script('autosave');
		}

		echo '
		<script type="text/javascript">
			var SYP_URL = "'.SYP_URL.'";
			var SYP_ABO_STATE = "'.get_user_meta(get_current_user_id(), '_syp_abo_state', true).'";
			var SYP_GO_TO = "'.__('Go to', 'Priseo').'";
			var SYP_NO_PRODUCT = "'.__('No product found with barcode : ', 'Priseo').'";
			var SYP_NO_RESULT = "'.__('No result found.', 'Priseo').'";
			var SYP_ALREADY_HAVE_PRODUCT = "'.__('You already have a product with barcode : ', 'Priseo').'";
			var SYP_NO_UPDATE = "'.__('No updates available.', 'Priseo').'";
			var SYP_FILL_FIELDS = "'.__('Please fill all required fields.', 'Priseo').'";
			var SYP_CHANGE_ACCOUNT = "'.__('Change account', 'Priseo').'";
			var SYP_SYNCHRONIZE = "'.__('Synchronize', 'Priseo').'";
			var SYP_ACCOUNTS_LINKED = "'.__('Your account was successfully linked with Priseo.', 'Priseo').'";
			var SYP_CURRENT_ACCOUNT = "'.__('Current account : ', 'Priseo').'";
			var SYP_ACCOUNTS_ERROR = "'.__('Unable to link your account to Priseo. Please check your login/password and try again.', 'Priseo').'";
			var SYP_ACCOUNTS_INSTRUCTIONS = "'.__('Fill all required fields to enable synchronization with <a href=\''.SYP_URL.'\'>Priseo</a>', 'Priseo').'";
			var SYP_STATE_SYNCHRONIZED = "'.__('Synchronized', 'Priseo').'";
		</script>';
	}

	/**
	 * Configure the plugin installation.
	 */
	function configure_plugin()
	{
		global $wpdb;

		$user_id = username_exists( 'Priseo' );
		if ( !$user_id )
		{
			$random_password = wp_generate_password( $length = 20, $include_standard_special_chars = true );
			$user_id = wp_create_user( 'Priseo', $random_password );

			$userdata = array
			(
				'ID' => $user_id,
				'user_nicename' => 'Priseo',
				'display_name' => 'Priseo',
				'last_name' => 'Priseo',
				'role' => 'author',
			);
			wp_update_user($userdata);
		}

		// Barcode attribute activation
		$barcode_status = $wpdb->get_var('SELECT status FROM ' . WPSHOP_DBT_ATTRIBUTE . ' WHERE code = "barcode"');
		if('valid' != $barcode_status)
		{
			$wpdb->query
			(
				$wpdb->prepare
				(
					'UPDATE ' . WPSHOP_DBT_ATTRIBUTE .
					' SET status = "valid"
					WHERE code = "barcode"'
				)
			);
		}

		$entity_type_id = wpshop_entities::get_entity_identifier_from_code(WPSHOP_NEWTYPE_IDENTIFIER_PRODUCT);
		$attribute_id = $wpdb->get_var('SELECT id FROM ' . WPSHOP_DBT_ATTRIBUTE . ' WHERE code = "barcode"');
		$attribute_set_id = $wpdb->get_var('SELECT id FROM ' . WPSHOP_DBT_ATTRIBUTE_SET . ' WHERE name = "default"');
		$attribute_set_section_id = $wpdb->get_var('SELECT id FROM ' . WPSHOP_DBT_ATTRIBUTE_GROUP . ' WHERE code = "general"');
		$attribute_detail_id = $wpdb->get_var
		(
			'SELECT id
			FROM ' . WPSHOP_DBT_ATTRIBUTE_DETAILS . '
			WHERE attribute_id = ' . $attribute_id . '
			AND status = "valid"'
		);
		$position = $wpdb->get_var
		(
			'SELECT MAX( position )
			FROM wp_wpshop__attribute_set_section_details
			WHERE attribute_set_id = '. $attribute_set_id . '
			AND attribute_group_id = ' . $attribute_set_section_id . '
			AND status = "valid"'
		);

		if(empty($attribute_detail_id))
		{
			$wpdb->query
			(
				$wpdb->prepare
				(
					'INSERT INTO ' . WPSHOP_DBT_ATTRIBUTE_DETAILS . ' (status, creation_date, entity_type_id, attribute_set_id, attribute_group_id, attribute_id, position)
					VALUES ("valid", %s, %d, %d, %d, %d, %d)'
					, date("Y-m-d H:i:s"), $entity_type_id, $attribute_set_id, $attribute_set_section_id, $attribute_id, $position
				)
			);
		}
		else
		{
			$wpdb->query
			(
				$wpdb->prepare
				(
					'UPDATE ' . WPSHOP_DBT_ATTRIBUTE_DETAILS .
					' SET attribute_set_id = %d, attribute_group_id = %d, position = %d
					WHERE attribute_id = %d
					AND status = "valid"'
					, $attribute_set_id, $attribute_set_section_id, $position, $attribute_id
				)
			);
		}

	}
}