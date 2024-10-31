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


class syp_listener extends wp_xmlrpc_server
{
	private $user = null;

	public function __construct()
	{
		parent::__construct();

		$methods = array
		(
			'xmlrpc.authenticate' => 'this:authenticate',
		);
		$this->methods = array_merge($this->methods, $methods);
	}

	public static function syp_xmlrpc_getName()
	{
		return __CLASS__;
	}

	/**
	 * Start point for any XMLRPC request.
	 * Checks user identity through basic authentification.
	 * Executes the request and returns its result if true, false otherwise.
	 *
	 * @param array $args
	 * @return boolean
	 */
	public function authenticate( $args )
	{
    	global $wpshop_account;

    	$user_login = $args[0];
    	$user_pass = $args[1];
    	$method_name = $args[2];
    	$method_args = $args[3];

		$isAuth = syp_core::compare_encrypted_passwords($user_login, $user_pass);

    	if($isAuth)
    	{
    		$this->user = get_user_by('login', $user_login);
    		return self::$method_name($method_args);
    	}
    	else
    	{
    		return false;
    	}
	}

	/**
	 * Import a certified product into the WPS.
	 *
	 * @param array $product_args
	 * @return int
	 */
	public function import_product( $product_args )
	{
		$product_args['user_ID'] = $this->user->ID;
		$product_args['post_author'] = $this->user->ID;

		foreach ( $product_args['wpshop_product_attribute'] as $attribute_type => $attribute )
		{
			foreach ( $attribute as $attribute_code => $attribute_value )
			{
				$attributes[$attribute_code] = $attribute_value;
			}
		}
		$new_product = wpshop_entities::create_new_entity(WPSHOP_NEWTYPE_IDENTIFIER_PRODUCT, $product_args['post_title'], $product_args['product_content'], $attributes, array('attribute_set_id' => $attributes['product_attribute_set_id']));
		wpshop_attributes::saveAttributeForEntity($product_args[wpshop_products::currentPageCode . '_attribute'], wpshop_entities::get_entity_identifier_from_code(wpshop_products::currentPageCode), $new_product[1], get_locale(), '');

		$post['ID'] = $new_product[1];
		$post['post_author'] = $this->user->ID;
		wp_update_post($post);

		if(isset($product_args['image_meta']) && !empty($product_args['image_meta']))
		{
			syp_core::import_attachment($new_product[1], $product_args['image_meta']);
		}

		return $new_product[1];
	}
}
?>