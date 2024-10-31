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

class syp_sender {
	private $user_login = '';
	private $user_pass  = '';

	private $method_name = '';
	private $method_args = '';
	private $client = null;

	public static function syp_xmlrpc_getName()
	{
		return __CLASS__;
	}

	/**
	 * CTOR
	 * Init the XMLRPC message body.
	 *
	 * @param string $method_name Optionnal
	 * @param mixed $method_args Optionnal
	 */
	public function syp_sender($method_name = '', $method_args = '')
	{
		$this->load_credentials();

		$this->method_name = $method_name;
		$this->method_args = $method_args;
		$this->client = new IXR_Client(SYP_URL . '/xmlrpc.php');
	}

	/**
	 * Used by the CTOR to init XMLRPC requests required information
	 * about the website target and authentification.
	 */
	private function load_credentials()
	{
		$current_user_id = get_current_user_id();

		$this->user_login = get_user_meta($current_user_id, '_syp_login', true);
		$this->user_pass = get_user_meta($current_user_id, '_syp_pass', true);
	}

	/**
	 * Used by send_request() to check if all required fields are filled
	 * before sending the request.
	 *
	 * @return boolean
	 */
	private function check_credentials_are_filled()
	{
		if(empty($this->user_login) || empty($this->user_pass))
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Prepare a new request from the client.
	 *
	 * @param string $method_name
	 * @param mixed $method_args
	 */
	public function prepare_request($method_name, $method_args)
	{
		$this->method_name = $method_name;
		$this->method_args = $method_args;
	}

	/**
	 * Send the XMLRPC request to the target website.
	 *
	 * @return mixed
	 */
	public function send_request()
	{
		if($this->check_credentials_are_filled())
		{
			$response = $this->client->query
			(
 				'xmlrpc.authenticate',
				$this->user_login,
				$this->user_pass,
				$this->method_name,
				$this->method_args
			);

			$response = $this->client->getResponse();
			return $response;
		}
	}


	/**
	 * Shortcut XMLRPC function to link a WPS account to a SYP account.
	 *
	 * @param array $args (see listener_server -> link_syp_account_to_wps)
	 * @return array
	 */
	public function link_wps_account_to_syp($args)
	{
		$this->method_name = 'link_wps_account_to_syp';
		$this->method_args = $args;

		$result = $this->send_request();
		return $result;
	}

	/**
	 * Shorcut XMLRPC function to contact SYP database and look for a
	 * certified product with given barcode.
	 *
	 * @param string $barcode_value
	 * @return array
	 */
	public function get_certified_product( $barcode_value )
	{
		$this->method_name = 'get_certified_product';
		$this->method_args = $barcode_value;

		$result = $this->send_request();
		return $result;
	}

	/**
	 * Shorcut XMLRPC function to contact SYP database and look for a
	 * user product (post_parent = a certified product id), with given
	 * barcode attribute.
	 *
	 * @param string barcode
	 * @return array
	 */
	public function get_user_product( $barcode_value )
	{
		$this->method_name = 'get_user_product';
		$this->method_args = $barcode_value;

		$result = $this->send_request();
		return $result;
	}

	/**
	 * Shortcut XMLRPC function to contact SYP database and look for different
	 * sellers of a product (determined by its barcode).
	 *
	 * @param array $args
	 * @return array
	 */
	public function get_product_prices( $args )
	{
		$this->method_name = 'get_product_prices';
		$this->method_args = $args;

		$result = $this->send_request();
		return $result;
	}

	/**
	 * Shortcut XMLRPC function to contact SYP database and add a user product.
	 * Also add the certified product if it does not exist yet.
	 *
	 * @param array $product_POST
	 * @return int
	 */
	public function add_product_to_syp( $product_POST )
	{
		$this->method_name = 'add_product_to_syp';
		$this->method_args = $product_POST;

		$result = $this->send_request();
		update_post_meta($product_POST['post_ID'], '_syp_last_update', date('Y-m-d H:i:s'));
		return $result;
	}

	/**
	 * Shortcut XMLRPC function to contact SYP database and update a user product.
	 *
	 * @param array $product_POST
	 */
	public function update_product_in_syp( $product_POST )
	{
		$this->method_name = 'update_product_in_syp';
		$this->method_args = $product_POST;

		$result = $this->send_request();
		update_post_meta($product_POST['post_ID'], '_syp_last_update', date('Y-m-d H:i:s'));
	}

	/**
	 * Shortcut XMLRPC function to contact SYP database and trash a user product.
	 *
	 * @param string $product_barcode
	 */
	public function trash_product_in_syp( $product_barcode )
	{
		$this->method_name = 'trash_product_in_syp';
		$this->method_args = $product_barcode;

		$this->send_request();
	}

	/**
	 * Shortcut XMLRPC function to contact SYP database and restore a trashed user product.
	 * @param string $product_barcode
	 */
	public function untrash_product_in_syp( $product_barcode )
	{
		$this->method_name = 'untrash_product_in_syp';
		$this->method_args = $product_barcode;

		$result = $this->send_request();
	}

	/**
	 * Shortcut XMLRPC function to contact SYP database and delete a trashed user product.
	 * @param string $product_barcode
	 */
	public function delete_product_in_syp($product_barcode)
	{
		$this->method_name = 'delete_product_in_syp';
		$this->method_args = $product_barcode;

		$result = $this->send_request();
		return $result;
	}

	public function dashboard_update_user_product($product_barcode, $product_attributes, $product_post)
	{
		$this->method_name = 'dashboard_update_user_product';
		$this->method_args = array
		(
			'product_barcode' => $product_barcode,
			'product_attributes' => $product_attributes,
			'product_post' => $product_post
		);

		$result = $this->send_request();
	}

	public function dashboard_update_auto_update_meta($product_barcode, $meta_value)
	{
		$this->method_name = 'dashboard_update_auto_update_meta';
		$this->method_args = array
		(
			'product_barcode' => $product_barcode,
			'meta_value' => $meta_value,
		);

		$result = $this->send_request();
	}

}

?>