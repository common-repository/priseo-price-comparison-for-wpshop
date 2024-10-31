<?php
/**
 * Priseo include file
 *
 * @package Priseo
 * @subpackage utils
 */

/*	Check if file is include. No direct access possible with file url	*/
if ( !defined( 'ABSPATH' ) ) {
	die( 'Access is not allowed by this way' );
}

require_once(WPSYP_LIBS_DIR . 'core.class.php');
require_once(WPSYP_LIBS_DIR . 'display.class.php');
require_once(WPSYP_LIBS_DIR . 'init.class.php');
require_once(WPSYP_LIBS_DIR . 'ajax.class.php');
require_once(WP_INCLUDES_DIR . '/class-IXR.php');
require_once(WP_INCLUDES_DIR . '/class-wp-xmlrpc-server.php');
require_once(WPSYP_LIBS_DIR . 'listener.class.php');
require_once(WPSYP_LIBS_DIR . 'sender.class.php');
