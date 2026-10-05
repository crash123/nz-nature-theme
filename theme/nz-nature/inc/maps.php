<?php
/**
 * Maps: embed Leaflet + OpenStreetMap maps of photo locations.
 *
 * Usage: [nz_photo_map scope="gallery|all" height="460" tile="osm|topo"]
 *
 * @package NZ_Nature
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Leaflet assets (vendored, so maps work offline/GitOps).
 */
function nz_nature_register_map_assets() {
	$base = get_template_directory_uri() . '/assets';

	wp_register_style( 'leaflet', $base . '/vendor/leaflet/leaflet.css', array(), '1.9.4' );
	wp_register_style( 'leaflet-markercluster', $base . '/vendor/leaflet/MarkerCluster.css', array( 'leaflet' ), '1.5.3' );
	wp_register_style( 'leaflet-markercluster-default', $base . '/vendor/leaflet/MarkerCluster.Default.css', array( 'leaflet-markercluster' ), '1.5.3' );

	wp_register_script( 'leaflet', $base . '/vendor/leaflet/leaflet.js', array(), '1.9.4', true );
	wp_register_script( 'leaflet-markercluster', $base . '/vendor/leaflet/leaflet.markercluster.js', array( 'leaflet' ), '1.5.3', true );
	wp_register_script( 'nz-map', $base . '/js/nz-map.js', array( 'leaflet', 'leaflet-markercluster' ), NZ_NATURE_VERSION, true );
}
add_action( 'init', 'nz_nature_register_map_assets' );

/**
 * Enqueue map assets on the front end.
 */
function nz_nature_enqueue_map_assets() {
	if ( is_admin() ) {
		return;
	}
	$base = get_template_directory_uri() . '/assets';

	wp_enqueue_style( 'leaflet' );
	wp_enqueue_style( 'leaflet-markercluster' );
	wp_enqueue_style( 'leaflet-markercluster-default' );

	wp_enqueue_script( 'leaflet' );
	wp_enqueue_script( 'leaflet-markercluster' );
	wp_enqueue_script( 'nz-map' );

	wp_localize_script( 'nz-map', 'nzMapStrings', array(
		'noCoords' => __( 'No photo locations to show yet.', 'nz-nature' ),
	) );
}
add_action( 'wp_enqueue_scripts', 'nz_nature_enqueue_map_assets' );

/**
 * Recursively collect attachment IDs from a post's blocks.
 *
 * @param array $blocks Parsed blocks.
 * @param array $ids    Collector.
 */
function nz_nature_collect_block_image_ids( $blocks, &$ids ) {
	foreach ( $blocks as $block ) {
		if ( ! empty( $block['blockName'] ) ) {
			$attrs = isset( $block['attrs'] ) ? $block['attrs'] : array();
			if ( 'core/image' === $block['blockName'] && ! empty( $attrs['id'] ) ) {
				$ids[] = (int) $attrs['id'];
			}
			if ( 'core/gallery' === $block['blockName'] && ! empty( $attrs['ids'] ) ) {
				foreach ( (array) $attrs['ids'] as $gid ) {
					$ids[] = (int) $gid;
				}
			}
			if ( 'core/media-text' === $block['blockName'] && ! empty( $attrs['mediaId'] ) ) {
				$ids[] = (int) $attrs['mediaId'];
			}
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			nz_nature_collect_block_image_ids( $block['innerBlocks'], $ids );
		}
	}
}

/**
 * Image IDs used in a post (block references + attached media).
 *
 * @param int $post_id Post ID.
 * @return int[]
 */
function nz_nature_gallery_image_ids( $post_id ) {
	$ids = array();

	$content = get_post_field( 'post_content', $post_id );
	if ( $content ) {
		nz_nature_collect_block_image_ids( parse_blocks( $content ), $ids );
	}

	$attached = get_attached_media( 'image', $post_id );
	foreach ( $attached as $attachment ) {
		$ids[] = (int) $attachment->ID;
	}

	return array_values( array_unique( array_filter( $ids ) ) );
}

/**
 * Build map points for a single post.
 *
 * @param int $post_id Post ID.
 * @return array
 */
function nz_nature_points_for_post( $post_id ) {
	$points = array();

	foreach ( nz_nature_gallery_image_ids( $post_id ) as $image_id ) {
		$gps = nz_nature_gps_get( $image_id );
		if ( ! $gps ) {
			continue;
		}
		$points[] = array(
			'lat'   => $gps['lat'],
			'lng'   => $gps['lng'],
			'title' => get_the_title( $image_id ) ?: get_the_title( $post_id ),
			'thumb' => wp_get_attachment_image_url( $image_id, 'nz-thumb' ),
			'url'   => get_permalink( $image_id ) ?: '',
		);
	}

	if ( empty( $points ) ) {
		$manual = nz_nature_get_manual_location( $post_id );
		if ( $manual ) {
			$thumb = get_the_post_thumbnail_url( $post_id, 'nz-thumb' );
			$points[] = array(
				'lat'   => $manual['lat'],
				'lng'   => $manual['lng'],
				'title' => $manual['label'] ? $manual['label'] : get_the_title( $post_id ),
				'thumb' => $thumb ? $thumb : '',
				'url'   => get_permalink( $post_id ),
			);
		}
	}

	return $points;
}

/**
 * Build map points across the whole site (one pin per geotagged photo, plus
 * one pin for each post with a manual location).
 *
 * @return array
 */
function nz_nature_points_all() {
	$points = array();

	$attachments = get_posts( array(
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'post_status'    => 'inherit',
		'posts_per_page' => 500,
		'fields'         => 'ids',
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'     => '_nz_gps',
				'compare' => 'EXISTS',
			),
		),
	) );

	foreach ( $attachments as $image_id ) {
		$gps = nz_nature_gps_get( $image_id );
		if ( ! $gps ) {
			continue;
		}
		$parent = wp_get_post_parent_id( $image_id );
		$points[] = array(
			'lat'   => $gps['lat'],
			'lng'   => $gps['lng'],
			'title' => get_the_title( $image_id ),
			'thumb' => wp_get_attachment_image_url( $image_id, 'nz-thumb' ),
			'url'   => $parent ? get_permalink( $parent ) : '',
		);
	}

	// Posts/pages with a manual location (skip any already represented).
	$manual_posts = get_posts( array(
		'post_type'      => array( 'post', 'page' ),
		'post_status'    => 'publish',
		'posts_per_page' => 200,
		'fields'         => 'ids',
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'     => '_nz_location',
				'compare' => 'EXISTS',
			),
		),
	) );

	foreach ( $manual_posts as $post_id ) {
		$manual = nz_nature_get_manual_location( $post_id );
		if ( ! $manual ) {
			continue;
		}
		$points[] = array(
			'lat'   => $manual['lat'],
			'lng'   => $manual['lng'],
			'title' => $manual['label'] ? $manual['label'] : get_the_title( $post_id ),
			'thumb' => get_the_post_thumbnail_url( $post_id, 'nz-thumb' ) ?: '',
			'url'   => get_permalink( $post_id ),
		);
	}

	return $points;
}

/**
 * Render the [nz_photo_map] shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function nz_nature_photo_map_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'scope'  => 'gallery',
			'height' => 460,
			'tile'   => 'osm',
			'zoom'   => '',
			'center' => '',
		),
		$atts,
		'nz_photo_map'
	);

	$scope  = ( 'all' === $atts['scope'] ) ? 'all' : 'gallery';
	$points = ( 'all' === $scope ) ? nz_nature_points_all() : nz_nature_points_for_post( get_the_ID() );

	if ( empty( $points ) ) {
		return '<p class="nz-map-empty">' . esc_html__( 'No photo locations to show yet. Add GPS-tagged photos or set a location for this gallery.', 'nz-nature' ) . '</p>';
	}

	$height = absint( $atts['height'] );
	$height = $height ? $height : 460;
	$tile   = ( 'topo' === $atts['tile'] ) ? 'topo' : 'osm';

	$data = array(
		'points' => $points,
		'tile'   => $tile,
		'zoom'   => $atts['zoom'],
		'center' => $atts['center'],
	);

	ob_start();
	?>
	<div class="nz-map-wrap">
		<div class="nz-map"
			data-nz-map="<?php echo esc_attr( wp_json_encode( $data ) ); ?>"
			style="height:<?php echo esc_attr( $height ); ?>px;"
			role="application"
			aria-label="<?php esc_attr_e( 'Map of photo locations', 'nz-nature' ); ?>"></div>
		<ul class="nz-map-list screen-reader-text">
			<?php foreach ( $points as $point ) : ?>
				<li>
					<?php echo esc_html( $point['title'] ); ?>
					(<?php echo esc_html( $point['lat'] . ', ' . $point['lng'] ); ?>)
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
	return nz_nature_minify_shortcode_html( ob_get_clean() );
}
add_shortcode( 'nz_photo_map', 'nz_nature_photo_map_shortcode' );
