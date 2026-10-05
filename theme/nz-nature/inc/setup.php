<?php
/**
 * Theme setup: supports, image sizes, assets and block styles.
 *
 * @package NZ_Nature
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register theme supports and image sizes.
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

	/*
	 * Photography image sizes.
	 * nz-thumb  – square crop for grids and map popups.
	 * nz-cover  – landscape crop for gallery/collection covers.
	 * nz-large  – generous long-edge render for the lightbox.
	 */
	add_image_size( 'nz-thumb', 700, 700, true );
	add_image_size( 'nz-cover', 1200, 800, true );
	add_image_size( 'nz-large', 1600, 1600, false );

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
	register_block_style( 'core/button', array(
		'name'  => 'outline-pounamu',
		'label' => __( 'Outline pounamu', 'nz-nature' ),
	) );

	register_block_style( 'core/group', array(
		'name'  => 'driftwood-card',
		'label' => __( 'Driftwood card', 'nz-nature' ),
	) );

	register_block_style( 'core/separator', array(
		'name'  => 'wave',
		'label' => __( 'Ocean wave', 'nz-nature' ),
	) );

	register_block_style( 'core/gallery', array(
		'name'  => 'masonry',
		'label' => __( 'Masonry', 'nz-nature' ),
	) );

	register_block_style( 'core/gallery', array(
		'name'  => 'full-bleed',
		'label' => __( 'Full bleed', 'nz-nature' ),
	) );

	register_block_style( 'core/gallery', array(
		'name'  => 'tight',
		'label' => __( 'Tight grid', 'nz-nature' ),
	) );

	register_block_style( 'core/image', array(
		'name'  => 'frameless',
		'label' => __( 'Frameless', 'nz-nature' ),
	) );
}
add_action( 'init', 'nz_nature_register_block_styles' );

/**
 * Expose the photography image sizes to the block editor.
 */
function nz_nature_image_size_names( $sizes ) {
	return array_merge( $sizes, array(
		'nz-thumb' => __( 'NZ Thumbnail', 'nz-nature' ),
		'nz-cover' => __( 'NZ Cover', 'nz-nature' ),
		'nz-large' => __( 'NZ Large', 'nz-nature' ),
	) );
}
add_filter( 'image_size_names_choose', 'nz_nature_image_size_names' );

/**
 * The Galleries page (posts page) lists photo galleries, so keep written
 * posts (category: posts) out of it.
 *
 * @param WP_Query $query Main query.
 */
function nz_nature_exclude_posts_from_home( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_home() ) {
		return;
	}
	$posts_cat = get_category_by_slug( 'posts' );
	if ( $posts_cat ) {
		$query->set( 'cat', '-' . (int) $posts_cat->term_id );
	}
}
add_action( 'pre_get_posts', 'nz_nature_exclude_posts_from_home' );
