<?php
/**
 * Location meta: a manual fallback for galleries whose photos lack GPS.
 *
 * @package NZ_Nature
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the location meta box for posts and pages.
 */
function nz_nature_add_location_meta_box() {
	foreach ( array( 'post', 'page' ) as $type ) {
		add_meta_box(
			'nz-nature-location',
			__( 'Gallery location (map)', 'nz-nature' ),
			'nz_nature_render_location_meta_box',
			$type,
			'side',
			'default'
		);
	}
}
add_action( 'add_meta_boxes', 'nz_nature_add_location_meta_box' );

/**
 * Render the location meta box.
 *
 * @param WP_Post $post Current post.
 */
function nz_nature_render_location_meta_box( $post ) {
	wp_nonce_field( 'nz_nature_save_location', 'nz_nature_location_nonce' );

	$label = get_post_meta( $post->ID, '_nz_location_label', true );
	$coord = get_post_meta( $post->ID, '_nz_location', true );
	?>
	<p>
		<label for="nz_location_label"><strong><?php esc_html_e( 'Place name', 'nz-nature' ); ?></strong></label><br />
		<input type="text" class="widefat" id="nz_location_label" name="nz_location_label"
			value="<?php echo esc_attr( $label ); ?>"
			placeholder="<?php esc_attr_e( 'e.g. 48.8584, 2.2945', 'nz-nature' ); ?>" />
	</p>
	<p>
		<label for="nz_location"><strong><?php esc_html_e( 'Coordinates', 'nz-nature' ); ?></strong></label><br />
		<input type="text" class="widefat" id="nz_location" name="nz_location"
			value="<?php echo esc_attr( $coord ); ?>"
			placeholder="<?php esc_attr_e( '-44.671, 167.925', 'nz-nature' ); ?>" />
	</p>
	<p class="description">
		<?php esc_html_e( 'Used for the map when photos have no GPS data. Tip: copy “lat, lng” from Google Maps.', 'nz-nature' ); ?>
	</p>
	<?php
}

/**
 * Save the location meta box.
 *
 * @param int $post_id Post ID.
 */
function nz_nature_save_location_meta( $post_id ) {
	if ( ! isset( $_POST['nz_nature_location_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( $_POST['nz_nature_location_nonce'] ), 'nz_nature_save_location' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$label = isset( $_POST['nz_location_label'] ) ? sanitize_text_field( wp_unslash( $_POST['nz_location_label'] ) ) : '';
	$coord = isset( $_POST['nz_location'] ) ? sanitize_text_field( wp_unslash( $_POST['nz_location'] ) ) : '';

	if ( '' === $label ) {
		delete_post_meta( $post_id, '_nz_location_label' );
	} else {
		update_post_meta( $post_id, '_nz_location_label', $label );
	}

	$parsed = nz_nature_parse_latlng( $coord );
	if ( $parsed ) {
		update_post_meta( $post_id, '_nz_location', $parsed['lat'] . ',' . $parsed['lng'] );
	} else {
		delete_post_meta( $post_id, '_nz_location' );
	}
}
add_action( 'save_post', 'nz_nature_save_location_meta' );

/**
 * Parse a "lat, lng" string.
 *
 * @param string $value Raw value.
 * @return array|null [ 'lat' => float, 'lng' => float ]
 */
function nz_nature_parse_latlng( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return null;
	}
	$parts = preg_split( '/\s*,\s*/', $value );
	if ( ! is_array( $parts ) || count( $parts ) < 2 ) {
		return null;
	}
	$lat = (float) $parts[0];
	$lng = (float) $parts[1];
	if ( ! is_finite( $lat ) || ! is_finite( $lng ) || abs( $lat ) > 90 || abs( $lng ) > 180 ) {
		return null;
	}
	return array( 'lat' => round( $lat, 6 ), 'lng' => round( $lng, 6 ) );
}

/**
 * Get the manual location for a post.
 *
 * @param int $post_id Post ID.
 * @return array|null [ 'label' => string, 'lat' => float, 'lng' => float ]
 */
function nz_nature_get_manual_location( $post_id ) {
	$coord = get_post_meta( (int) $post_id, '_nz_location', true );
	$parsed = nz_nature_parse_latlng( $coord );
	if ( ! $parsed ) {
		return null;
	}
	$parsed['label'] = (string) get_post_meta( (int) $post_id, '_nz_location_label', true );
	return $parsed;
}
