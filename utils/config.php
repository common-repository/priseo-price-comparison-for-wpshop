<?php
/**
 * Priseo config file
 *
 * @package Priseo
 * @subpackage utils
 */

/*	Check if file is include. No direct access possible with file url	*/
if ( !defined( 'ABSPATH' ) ) {
	die( 'Access is not allowed by this way' );
}

/*	Define directories names	*/
DEFINE( 'WPSYP_UTILS_DIR', WP_PLUGIN_DIR  . '/' . SYP_PLUGIN_DIR . '/utils/' );
DEFINE( 'WPSYP_LIBS_DIR', WPSYP_UTILS_DIR . 'librairies/' );