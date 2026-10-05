<?php
/**
 * GPS: extract and cache photo coordinates.
 *
 * WordPress core records camera/lens/ISO etc. in image meta, but NOT GPS.
 * We read GPS straight from the original file and cache it in attachment
 * meta (`_nz_gps` => [ 'lat' => float, 'lng' => float ]) so maps are cheap
 * to render.
 *
 * @package NZ_Nature
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Convert an EXIF rational (e.g. "43/1" or "12345/100") to a float.
 *
 * @param mixed $value EXIF value.
 * @return float
 */
function nz_nature_gps_frac( $value ) {
	if ( is_numeric( $value ) ) {
		return (float) $value;
	}
	if ( is_string( $value ) && false !== strpos( $value, '/' ) ) {
		list( $num, $den ) = array_pad( explode( '/', $value, 2 ), 2, 1 );
		$den               = (float) $den;
		return $den ? ( (float) $num / $den ) : 0.0;
	}
	return 0.0;
}

/**
 * Convert [ deg, min, sec ] to decimal degrees.
 *
 * @param array $dms DMS values.
 * @return float
 */
function nz_nature_gps_dms( $dms ) {
	if ( ! is_array( $dms ) || count( $dms ) < 3 ) {
		return 0.0;
	}
	$deg = nz_nature_gps_frac( $dms[0] );
	$min = nz_nature_gps_frac( $dms[1] );
	$sec = nz_nature_gps_frac( $dms[2] );
	return $deg + ( $min / 60 ) + ( $sec / 3600 );
}

/**
 * Read GPS coordinates from an image file.
 *
 * @param string $path Absolute path to the original image.
 * @return array|null [ 'lat' => float, 'lng' => float ] or null.
 */
function nz_nature_gps_read_file( $path ) {
	if ( ! $path || ! is_readable( $path ) || ! function_exists( 'exif_read_data' ) ) {
		return null;
	}

	$exif = @exif_read_data( $path, 'GPS' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	if ( ! is_array( $exif ) || empty( $exif['GPSLatitude'] ) || empty( $exif['GPSLongitude'] ) ) {
		return null;
	}

	$lat = nz_nature_gps_dms( $exif['GPSLatitude'] );
	$lng = nz_nature_gps_dms( $exif['GPSLongitude'] );

	if ( isset( $exif['GPSLatitudeRef'] ) && 'S' === strtoupper( substr( (string) $exif['GPSLatitudeRef'], 0, 1 ) ) ) {
		$lat = -$lat;
	}
	if ( isset( $exif['GPSLongitudeRef'] ) && 'W' === strtoupper( substr( (string) $exif['GPSLongitudeRef'], 0, 1 ) ) ) {
		$lng = -$lng;
	}

	if ( ! is_finite( $lat ) || ! is_finite( $lng ) || ( 0.0 === $lat && 0.0 === $lng ) ) {
		return null;
	}
	if ( abs( $lat ) > 90 || abs( $lng ) > 180 ) {
		return null;
	}

	return array( 'lat' => round( $lat, 6 ), 'lng' => round( $lng, 6 ) );
}

/**
 * Get the cached GPS coordinates for an attachment.
 *
 * @param int $attachment_id Attachment ID.
 * @return array|null
 */
function nz_nature_gps_get( $attachment_id ) {
	$gps = get_post_meta( (int) $attachment_id, '_nz_gps', true );
	if ( is_array( $gps ) && isset( $gps['lat'], $gps['lng'] ) ) {
		return array( 'lat' => (float) $gps['lat'], 'lng' => (float) $gps['lng'] );
	}
	return null;
}

/**
 * Extract and cache GPS for an attachment.
 *
 * @param int $attachment_id Attachment ID.
 * @return array|null
 */
function nz_nature_gps_store( $attachment_id ) {
	$file = get_attached_file( (int) $attachment_id );
	$gps  = nz_nature_gps_read_file( $file );
	if ( $gps ) {
		update_post_meta( (int) $attachment_id, '_nz_gps', $gps );
		return $gps;
	}
	delete_post_meta( (int) $attachment_id, '_nz_gps' );
	return null;
}

/**
 * Cache GPS whenever attachment metadata is (re)generated.
 *
 * @param array $metadata      Attachment metadata.
 * @param int   $attachment_id Attachment ID.
 * @return array
 */
function nz_nature_gps_on_metadata( $metadata, $attachment_id ) {
	nz_nature_gps_store( $attachment_id );
	return $metadata;
}
add_filter( 'wp_generate_attachment_metadata', 'nz_nature_gps_on_metadata', 20, 2 );

/**
 * Backfill GPS meta for existing images.
 *
 * Run with: wp eval 'echo nz_nature_gps_backfill();'
 *
 * @return int Number of attachments that gained coordinates.
 */
function nz_nature_gps_backfill() {
	$query = new WP_Query( array(
		'post_type'      => 'attachment',
		'post_mime_type' => array( 'image/jpeg', 'image/tiff', 'image/webp' ),
		'post_status'    => 'inherit',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'     => '_nz_gps',
				'compare' => 'NOT EXISTS',
			),
		),
	) );

	$found = 0;
	foreach ( $query->posts as $attachment_id ) {
		if ( nz_nature_gps_store( $attachment_id ) ) {
			$found++;
		}
	}
	return $found;
}
