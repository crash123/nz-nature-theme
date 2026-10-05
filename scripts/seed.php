<?php
/**
 * Seed the NZ Nature photography site with sample galleries, pages and a
 * journal entry. Idempotent — safe to re-run.
 *
 * Run with:
 *   docker compose run --rm wpcli wp eval-file /scripts/seed.php
 *   docker compose run --rm wpcli wp eval-file /scripts/seed.php -- --force
 *
 * @package NZ_Nature
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via: wp eval-file /scripts/seed.php\n" );
	exit( 1 );
}

$nz_args  = isset( $args ) ? (array) $args : array();
$nz_force = in_array( 'force', $nz_args, true ) || in_array( '--force', $nz_args, true );

$log = function ( $message ) {
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::log( $message );
	} else {
		echo $message . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
};

if ( get_option( 'nz_nature_seeded' ) && ! $nz_force ) {
	$log( 'Seed already applied on ' . get_option( 'nz_nature_seeded' ) . '. Use -- --force to re-run.' );
	return;
}

/**
 * Import a placeholder image once (deduped by filename).
 *
 * @param string $path  Absolute source path.
 * @param string $title Attachment title.
 * @return int Attachment ID (0 on failure).
 */
function nz_seed_import_image( $path, $title ) {
	$key      = basename( $path );
	$existing = get_posts( array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_key'       => '_nz_seed', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_value'     => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
	) );
	if ( $existing ) {
		return (int) $existing[0];
	}

	$upload = wp_upload_bits( $key, null, file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}

	$filetype = wp_check_filetype( $upload['file'] );
	$id       = wp_insert_attachment( array(
		'post_mime_type' => $filetype['type'],
		'post_title'     => $title,
		'post_status'    => 'inherit',
	), $upload['file'] );

	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
	update_post_meta( $id, '_nz_seed', $key );

	return (int) $id;
}

/**
 * Build a masonry Gallery block from attachment IDs.
 *
 * @param int[] $ids Attachment IDs.
 * @return string
 */
function nz_seed_gallery_block( $ids ) {
	$inner = '';
	foreach ( $ids as $id ) {
		$url    = wp_get_attachment_image_url( $id, 'large' );
		$inner .= sprintf(
			'<!-- wp:image {"id":%d,"sizeSlug":"large","linkDestination":"media"} -->' .
			'<figure class="wp-block-image size-large"><a href="%s"><img src="%s" alt="" class="wp-image-%d"/></a></figure>' .
			'<!-- /wp:image -->' . "\n",
			$id,
			esc_url( $url ),
			esc_url( $url ),
			$id
		);
	}
	return '<!-- wp:gallery {"linkTo":"media","align":"wide","className":"is-style-masonry","columns":3} -->' . "\n" .
		'<figure class="wp-block-gallery has-nested-images columns-3 is-cropped is-style-masonry alignwide">' . "\n" .
		$inner .
		'</figure>' . "\n" .
		'<!-- /wp:gallery -->';
}

// -------------------------------------------------------------------------
// 1. Categories.
// -------------------------------------------------------------------------
$nz_cats = array(
	'travel'    => 'Travel',
	'landscape' => 'Landscape',
	'wildlife'  => 'Wildlife',
	'journal'   => 'Journal',
);
$nz_cat_ids = array();
foreach ( $nz_cats as $slug => $name ) {
	$term = get_term_by( 'slug', $slug, 'category' );
	if ( ! $term ) {
		$res = wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
		if ( ! is_wp_error( $res ) ) {
			$nz_cat_ids[ $slug ] = (int) $res['term_id'];
		}
	} else {
		$nz_cat_ids[ $slug ] = (int) $term->term_id;
	}
}
$log( 'Categories ready: ' . implode( ', ', $nz_cats ) );

// -------------------------------------------------------------------------
// 2. Pages.
// -------------------------------------------------------------------------
$nz_pages = array(
	'home'      => array( 'Home', '' ),
	'galleries' => array( 'Galleries', '' ),
	'places'    => array( 'Places', '<!-- wp:shortcode -->[nz_photo_map scope="all" height="560" tile="topo"]<!-- /wp:shortcode -->' ),
	'about'     => array( 'About', '<!-- wp:paragraph --><p>Kia ora — I\'m an amateur photographer based in Aotearoa New Zealand. This site collects the landscapes, coastline and wildlife I\'m lucky enough to point a camera at.</p><!-- /wp:paragraph -->' ),
	'contact'   => array( 'Contact', '<!-- wp:paragraph --><p>Ngā mihi for stopping by. Say hello at <a href="mailto:hello@example.com">hello@example.com</a>.</p><!-- /wp:paragraph -->' ),
);

$nz_page_ids = array();
foreach ( $nz_pages as $slug => $data ) {
	$page = get_page_by_path( $slug );
	if ( $page ) {
		$nz_page_ids[ $slug ] = (int) $page->ID;
	} else {
		$nz_page_ids[ $slug ] = (int) wp_insert_post( array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $data[0],
			'post_name'    => $slug,
			'post_content' => $data[1],
		) );
	}
}
$log( 'Pages ready: ' . implode( ', ', array_keys( $nz_pages ) ) );

// Static front page + Galleries as the posts page.
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $nz_page_ids['home'] );
update_option( 'page_for_posts', $nz_page_ids['galleries'] );
update_option( 'blogdescription', 'Amateur photography from Aotearoa New Zealand' );
update_option( 'timezone_string', 'Pacific/Auckland' );

// Drop any DB "Front Page" template that would shadow the theme file.
foreach ( get_posts( array( 'post_type' => 'wp_template', 'name' => 'front-page', 'posts_per_page' => -1 ) ) as $tpl ) {
	wp_delete_post( $tpl->ID, true );
}

// -------------------------------------------------------------------------
// 3. Import placeholder images.
// -------------------------------------------------------------------------
$nz_dir   = get_template_directory() . '/assets/images/placeholders/';
$nz_files = glob( $nz_dir . '*.png' );
sort( $nz_files );
$nz_image_ids = array();
foreach ( $nz_files as $i => $file ) {
	$id = nz_seed_import_image( $file, sprintf( 'Placeholder photo %02d', $i + 1 ) );
	if ( $id ) {
		$nz_image_ids[] = $id;
	}
}
$log( 'Imported ' . count( $nz_image_ids ) . ' placeholder photos.' );

if ( count( $nz_image_ids ) < 12 ) {
	$log( 'Warning: fewer than 12 placeholders imported.' );
}

$chunk = array_chunk( $nz_image_ids, 4 );

// -------------------------------------------------------------------------
// 4. Sample galleries.
// -------------------------------------------------------------------------
$nz_galleries = array(
	array(
		'title'   => 'Milford Sound & Fiordland',
		'slug'    => 'milford-sound-fiordland',
		'cat'     => 'travel',
		'excerpt' => 'Rain, waterfalls and silence in the deep south.',
		'intro'   => 'They say Fiordland gets seven metres of rain a year, and it shows. Between the downpours the valleys filled with mist and the waterfalls multiplied.',
		'label'   => 'Milford Sound',
		'lat'     => -44.6414,
		'lng'     => 167.8974,
		'images'  => isset( $chunk[0] ) ? $chunk[0] : array(),
	),
	array(
		'title'   => 'Tongariro Alpine Crossing',
		'slug'    => 'tongariro-alpine-crossing',
		'cat'     => 'landscape',
		'excerpt' => 'An early start for the best day walk in the North Island.',
		'intro'   => 'By the time the sun cleared Ngauruhoe, the whole plateau was glowing. Three volcanoes, one endless sky, and the quiet crunch of scoria underfoot.',
		'label'   => 'Tongariro National Park',
		'lat'     => -39.2812,
		'lng'     => 175.5667,
		'images'  => isset( $chunk[1] ) ? $chunk[1] : array(),
	),
	array(
		'title'   => 'Kōwhai in spring',
		'slug'    => 'kowhai-in-spring',
		'cat'     => 'wildlife',
		'excerpt' => 'Golden blooms and the birds that love them.',
		'intro'   => 'Each spring the kōwhai erupts in gold and the tūī descend. A good excuse to stand still with a long lens for an afternoon.',
		'label'   => 'Wellington',
		'lat'     => -41.2866,
		'lng'     => 174.7756,
		'images'  => isset( $chunk[2] ) ? $chunk[2] : array(),
	),
);

foreach ( $nz_galleries as $gallery ) {
	$images  = array_values( array_filter( $gallery['images'] ) );
	$content = '<!-- wp:paragraph --><p>' . esc_html( $gallery['intro'] ) . '</p><!-- /wp:paragraph -->' . "\n\n" .
		nz_seed_gallery_block( $images );

	$existing = get_page_by_path( $gallery['slug'], OBJECT, 'post' );
	$postarr  = array(
		'post_type'    => 'post',
		'post_status'  => 'publish',
		'post_title'   => $gallery['title'],
		'post_name'    => $gallery['slug'],
		'post_content' => $content,
		'post_excerpt' => $gallery['excerpt'],
	);
	if ( $existing ) {
		$postarr['ID'] = $existing->ID;
		$post_id       = wp_update_post( $postarr );
	} else {
		$post_id = wp_insert_post( $postarr );
	}

	if ( is_wp_error( $post_id ) || ! $post_id ) {
		continue;
	}

	if ( isset( $nz_cat_ids[ $gallery['cat'] ] ) ) {
		wp_set_post_categories( $post_id, array( $nz_cat_ids[ $gallery['cat'] ] ) );
	}
	if ( $images ) {
		set_post_thumbnail( $post_id, $images[0] );
	}
	update_post_meta( $post_id, '_nz_location', $gallery['lat'] . ',' . $gallery['lng'] );
	update_post_meta( $post_id, '_nz_location_label', $gallery['label'] );
	$log( 'Gallery: ' . $gallery['title'] );
}

// -------------------------------------------------------------------------
// 5. Journal entry.
// -------------------------------------------------------------------------
if ( ! get_page_by_path( 'first-light', OBJECT, 'post' ) ) {
	$journal_id = wp_insert_post( array(
		'post_type'    => 'post',
		'post_status'  => 'publish',
		'post_title'   => 'Why I started carrying a camera into the hills',
		'post_name'    => 'first-light',
		'post_content' => '<!-- wp:paragraph --><p>The best light never waits. A few notes on slowing down, looking up, and learning to be patient with the weather in Aotearoa.</p><!-- /wp:paragraph -->',
	) );
	if ( $journal_id && ! is_wp_error( $journal_id ) && isset( $nz_cat_ids['journal'] ) ) {
		wp_set_post_categories( $journal_id, array( $nz_cat_ids['journal'] ) );
	}
	$log( 'Journal entry created.' );
}

// -------------------------------------------------------------------------
// 6. Page order (navigation page list).
// -------------------------------------------------------------------------
$order = array( 'home' => 1, 'galleries' => 2, 'places' => 3, 'about' => 4, 'contact' => 5 );
foreach ( $order as $slug => $menu_order ) {
	if ( isset( $nz_page_ids[ $slug ] ) ) {
		wp_update_post( array( 'ID' => $nz_page_ids[ $slug ], 'menu_order' => $menu_order ) );
	}
}

// Clean up the default "Sample Page" if present.
$sample = get_page_by_path( 'sample-page' );
if ( $sample ) {
	wp_delete_post( $sample->ID, true );
	$log( 'Removed default Sample Page.' );
}

update_option( 'nz_nature_seeded', current_time( 'mysql' ) );
$log( 'Done. Seeded galleries, collections, pages and a journal entry.' );
