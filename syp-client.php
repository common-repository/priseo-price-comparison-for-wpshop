<?php
/**
 * Plugin Name: Priseo - Price comparison for WPShop
 * Plugin URI: http://www.priseo.com/
 * Description: Allows to send directly your product added with wpshop in your shop to www.priseo.com and to compare your prices with your competitors.
 * Version: 1.0.0.0
 * Author: Eoxia
 * Author URI: http://www.eoxia.com/
 */

/*	Check if file is include. No direct access possible with file url	*/
if ( !defined( 'ABSPATH' ) ) {
	die( 'Access is not allowed by this way' );
}

/*	Allows to refresh css and js file in final user browser	*/
DEFINE('SYP_VERSION', '1.0.0.0');

/*	Define the Priseo website url for xmlrpx requests */
DEFINE('SYP_URL', 'http://www.priseo.com/');
// DEFINE('SYP_URL', 'http://ks353566.kimsufi.com/~statsyou/');
DEFINE('WP_INCLUDES_DIR', ABSPATH . 'wp-includes');

/*
 *	First thing we define the main directory for our plugin in a super global var
 */
DEFINE('SYP_PLUGIN_DIR', basename(dirname(__FILE__)));

/*	Include the file containing configurations and include	*/
require_once( WP_PLUGIN_DIR  . '/' . SYP_PLUGIN_DIR . '/utils/config.php' );
require_once( WP_PLUGIN_DIR  . '/' . SYP_PLUGIN_DIR . '/utils/include.php' );

/* Init the plugin */
register_activation_hook( __FILE__, array('syp_init', 'configure_plugin'));

/*
 * Frontend Javascript support
 */
add_action('init', 'syp_js');

function syp_js() {
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-tabs');
	wp_enqueue_script('jquery-form');
	wp_enqueue_script('jquery-ui-dialog');
	wp_enqueue_script('jquery-ui-datepicker');
	wp_enqueue_script('wpshop_syp_frontend_main_js', WP_PLUGIN_URL . '/' .  SYP_PLUGIN_DIR . '/templates/syp.js', '', SYP_VERSION, true);
}

/*	Include head js	*/
add_action('admin_print_scripts', array('syp_init', 'admin_print_js'));



/*
 * Hook the WPS post publish to publish on SYP if
 * seller want to share its product on SYP.
 */
function syp_post_product($new_status, $old_status, $post)
{
	if ( $post->post_type != 'wpshop_product' || $_POST['original_publish'] != __('Publish'))
	{
		return;
	}

	if(isset($_POST['syp_product_barcode']) && !empty($_POST['syp_product_barcode']))
	{
		if ( 'publish' == $new_status && !in_array($old_status, array( 'publish', 'private', 'trash' )) )
	 	{
	 		$barcode_value = $_POST['syp_product_barcode'];
	 		$request = new syp_sender();
	 		$user_product = $request->get_user_product($barcode_value);
	 		if(empty($user_product))
	 		{
	 			if(did_action($tag))
	 			remove_action('pre_post_update', 'syp_sync_update');
	 			$thumbnail_id = get_post_meta($_POST['ID'], '_thumbnail_id', true);
	 			$image_path = get_post_meta($thumbnail_id, '_wp_attached_file', true);
	 			if(!empty($image_path))
	 			{
	 				$_POST['image_meta'] = $image_path;
	 			}

	 			$request = new syp_sender();
	 			$request->add_product_to_syp($_POST);

	 			if(isset($_POST['syp_enable_sync']) && $_POST['syp_enable_sync'] == 'on')
	 			{
	 				$user_abo_state = get_user_meta(get_current_user_id(), '_syp_abo_state', true);
	 				if($user_abo_state != 'free')
	 				{
	 					update_post_meta($_POST['ID'], '_auto_update', 'true');
	 				}
	 				else
	 				{
	 					update_post_meta($_POST['ID'], '_auto_update', 'false');
	 				}
	 			}
	 			else
	 			{
	 				update_post_meta($_POST['ID'], '_auto_update', 'false');
	 			}

	 			add_action('pre_post_update', 'syp_sync_update', 10, 3);
	 		}
	 		else
	 		{
	 			return;
	 		}
		}
	}
}
add_action('transition_post_status', 'syp_post_product', 10, 3);

function syp_sync_update()
{
	if ( $_POST['post_type'] != 'wpshop_product' )
	{
		return;
	}

	if(wp_is_post_revision($_POST['ID']) || $_POST['original_publish'] == __('Publish'))
	{
		return;
	}

	if(isset($_POST['syp_product_barcode']) && !empty($_POST['syp_product_barcode']))
	{
		remove_action('pre_post_update ', 'syp_sync_update');
		$barcode_value = $_POST['syp_product_barcode'];
		$request = new syp_sender();

		$user_product = $request->get_user_product($barcode_value);
		if(empty($user_product))
		{
			$request->add_product_to_syp($_POST);
		}
		else
		{
			$thumbnail_id = get_post_meta($_POST['ID'], '_thumbnail_id', true);
			$image_path = get_post_meta($thumbnail_id, '_wp_attached_file', true);
			if(!empty($image_path))
			{
				$_POST['image_meta'] = $image_path;
			}
			else
			{
				$_POST['image_meta'] = 'deleted';
			}
			$request->update_product_in_syp($_POST);

			if(isset($_POST['syp_enable_sync']) && $_POST['syp_enable_sync'] == 'on')
			{
				$user_abo_state = get_user_meta(get_current_user_id(), '_syp_abo_state', true);
				if($user_abo_state != 'free')
				{
					update_post_meta($_POST['ID'], '_auto_update', 'true');
				}
				else
				{
					update_post_meta($_POST['ID'], '_auto_update', 'false');
				}
			}
			else
			{
				update_post_meta($_POST['ID'], '_auto_update', 'false');
			}
		}
		add_action('pre_post_update', 'syp_sync_update', 10, 3);
	}
}
add_action('pre_post_update', 'syp_sync_update', 10, 3);


function syp_sync_trash($post_id)
{
	if ( get_post( $postID )->post_type == 'wpshop_product' )
	{
		if(!did_action('trash_post'))
		{
			$local_product = wpshop_products::get_product_data($post_id, false);
			$product_barcode = $local_product['barcode'];

			$request = new syp_sender('trash_product_in_syp', $product_barcode);
	 		$request->send_request();
		}
	}
}
add_action('wp_trash_post','syp_sync_trash', 10, 3);


function syp_sync_restore($post_id)
{
	if ( get_post( $post_id )->post_type == 'wpshop_product' )
	{
		$local_product = wpshop_products::get_product_data($post_id, false, '"publish", "draft", "trash"');
		$product_barcode = $local_product['barcode'];

		$request = new syp_sender('untrash_product_in_syp', $product_barcode);
		$request->send_request();
	}
}
add_action('untrash_post', 'syp_sync_restore');


function syp_sync_delete($post_id)
{
	if ( get_post( $postID )->post_type == 'wpshop_product' )
	{
		$local_product = wpshop_products::get_product_data($post_id, false, '"publish", "draft", "trash"');
		$product_barcode = $local_product['barcode'];

		$request = new syp_sender('delete_product_in_syp', $product_barcode);
		$request->send_request();
	}
}
add_action('before_delete_post', 'syp_sync_delete');


// Register with hook 'wp_enqueue_scripts', which can be used for front end CSS and JavaScript
function syp_add_admin_css() {
	echo '<link rel="stylesheet" type="text/css" href="' . WP_PLUGIN_URL  . '/' . SYP_PLUGIN_DIR . '/templates/syp.css" />';
}
add_action('admin_head', 'syp_add_admin_css');

function syp_admin_page()
{
		add_options_page(
		__('Priseo', 'Priseo'), __('Priseo', 'Priseo'),
		'administrator', 'syp-admin', syp_content_settings );
}

function syp_dashboard_menu()
{
	if(function_exists('add_submenu_page'))
	{
		add_menu_page
		(
			__('Priseo'),
			__('Priseo'),
			'activate_plugins',
			SYP_PLUGIN_DIR . 'statsyourprice-client.php',
			array('syp_display', 'syp_dashboard'),
			WP_PLUGIN_URL  . '/' . SYP_PLUGIN_DIR . '/templates/barcode.png',
			'59.99'
		);
	}
}
add_action('admin_menu', 'syp_dashboard_menu');


function syp_options()
{
    $title = __('Priseo Options', 'syp_options'); ?>

		<div class="wrap">
			<?php screen_icon(); ?>
			<h2><?php echo esc_html( $title ); ?></h2>

			<form method="POST" action="">
				<?php syp_content_settings(); ?>
			</form>
			<div style="display:none" id="spinner"><img alt="spinner" src=<?php echo WP_PLUGIN_URL  . '/' . SYP_PLUGIN_DIR . "/templates/ajaxSpinner.gif"; ?> /></div>
		</div>
<?php
}


function syp_content_settings()
{
	echo "<H1>Priseo options</H1>";
	$user_meta_login = get_user_meta(get_current_user_id(), '_syp_login', true);

	if(!$user_meta_login)
	{
		$login_value = '';
		$read_only = '';
		$display = '';
		$btn_name = 'save_options';
		$btn_text = __("Synchronize", "Priseo");
		echo _e('<p id="link_form_text">Fill all required fields to enable synchronization with <a href="'.SYP_URL.'">www.priseo.com</a></p>', 'Priseo');
	}
	else
	{
		$login_value = $user_meta_login;
		$read_only = 'readonly';
		$display = 'style="display:none;"';
		$btn_name = 'change_options';
		$btn_text = __('Change account', 'Priseo');
		echo _e('<p id="link_form_text">Current account : '.$login_value.'</p>', 'Priseo');
	}

	$template = '
		<div id="link_form_inputs" '.$display.'>
		    <input id="loginSYP" style="width:50%;"  type="text" name="loginSYP" required="true" value="'.$login_value.'" placeholder="'.__('Login Priseo', 'Priseo').'" '.$read_only.' /><br />
		    <input id="passSYP" style="width:50%;"  type="password" name="passSYP" required="true" placeholder="'.__('Password', 'Priseo').'" '.$read_only.' />	<br /><br />
		</div>
		<input type="button" class="button button-secondary" id="submitAdmin" name="'.$btn_name.'" value="'.$btn_text.'" />
		<img alt="spinner" id="spinner" style="display:none" src="'. WP_PLUGIN_URL  . '/' . SYP_PLUGIN_DIR . '/templates/ajaxSpinner.gif" />
 	';

	echo $template;
}

add_action('admin_menu', 'syp_admin_page');


/*
 * Add a metabox in product page
 */
function syp_metaboxes()
{
	$syp_login = get_user_meta(get_current_user_id(), '_syp_login', true);

	if(!empty($syp_login))
	{
		add_meta_box
		(
			'syp_product_box',
			__('Priseo', 'wp_syp'),
			array('syp_core', 'syp_product_metabox'),
			WPSHOP_NEWTYPE_IDENTIFIER_PRODUCT,
			'advanced',
			'high',
			array()
		);
	}
}
add_action( 'add_meta_boxes', 'syp_metaboxes' );

/*
 * AJAX Support
 */
add_action('wp_ajax_syp_search_product_by_barcode',  array('syp_ajax', 'ajax_syp_search_product_by_barcode'));
add_action('wp_ajax_syp_search_product_updates',  array('syp_ajax', 'ajax_syp_search_product_updates'));
add_action('wp_ajax_syp_search_product_prices',  array('syp_ajax', 'ajax_syp_search_product_prices'));
add_action('wp_ajax_exec_form_admin',  array('syp_ajax', 'ajax_syp_exec_form_admin'));
add_action('wp_ajax_syp_dashboard_synchronize',  array('syp_ajax', 'syp_dashboard_synchronize'));
add_action('wp_ajax_syp_dashboard_update',  array('syp_ajax', 'syp_dashboard_update'));
add_action('wp_ajax_syp_dashboard_auto_update',  array('syp_ajax', 'syp_dashboard_auto_update'));

/*
 * Extension XMLRPC
 */
add_filter('wp_xmlrpc_server_class', array('syp_listener', 'syp_xmlrpc_getName'));

?>