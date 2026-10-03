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
	define( 'NZ_NATURE_VERSION', '1.0.0' );
}

/**
 * Register theme supports.
 */
function nz_nature_setup() {
	load_theme_textdomain( 'nz-nature', get_template_directory() . '/languages' );

	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'custom-logo', array(
		'height'      => 96,
		'width'       => 96,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'html5', array(
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
		'style',
		'script',
	) );

	add_editor_style( 'assets/css/theme.css' );
}
add_action( 'after_setup_theme', 'nz_nature_setup' );

/**
 * Enqueue front-end styles.
 */
function nz_nature_enqueue_assets() {
	wp_enqueue_style(
		'nz-nature-theme',
		get_template_directory_uri() . '/assets/css/theme.css',
		array(),
		NZ_NATURE_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'nz_nature_enqueue_assets' );

/**
 * Register the theme's block pattern category.
 */
function nz_nature_register_pattern_categories() {
	register_block_pattern_category(
		'nz-nature',
		array( 'label' => __( 'NZ Nature', 'nz-nature' ) )
	);
}
add_action( 'init', 'nz_nature_register_pattern_categories' );

/**
 * Register custom block styles.
 */
function nz_nature_register_block_styles() {
	register_block_style(
		'core/button',
		array(
			'name'  => 'outline-pounamu',
			'label' => __( 'Outline pounamu', 'nz-nature' ),
		)
	);

	register_block_style(
		'core/group',
		array(
			'name'  => 'driftwood-card',
			'label' => __( 'Driftwood card', 'nz-nature' ),
		)
	);

	register_block_style(
		'core/separator',
		array(
			'name'  => 'wave',
			'label' => __( 'Ocean wave', 'nz-nature' ),
		)
	);
}
add_action( 'init', 'nz_nature_register_block_styles' );
