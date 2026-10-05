<?php
/**
 * NZ Nature theme bootstrap.
 *
 * @package NZ_Nature
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'NZ_NATURE_VERSION' ) ) {
	define( 'NZ_NATURE_VERSION', '1.1.0' );
}

/**
 * Load theme modules.
 */
foreach ( array( 'setup', 'collections', 'gps', 'meta', 'maps' ) as $nz_nature_module ) {
	$nz_nature_file = get_template_directory() . '/inc/' . $nz_nature_module . '.php';
	if ( file_exists( $nz_nature_file ) ) {
		require_once $nz_nature_file;
	}
}
