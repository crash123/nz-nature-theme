<?php
/**
 * Title: Places photo map
 * Slug: nz-nature/places-map
 * Categories: nz-nature, gallery
 * Viewport Width: 1400
 * Description: A heading and a site-wide map of every geotagged photo.
 */
?>
<!-- wp:group {"align":"wide","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="margin-bottom:var(--wp--preset--spacing--40)">
	<!-- wp:heading -->
	<h2 class="wp-block-heading"><?php esc_html_e( 'Places', 'nz-nature' ); ?></h2>
	<!-- /wp:heading -->
	<!-- wp:paragraph {"textColor":"fern"} -->
	<p class="has-fern-color has-text-color"><?php esc_html_e( 'Every geotagged photograph, pinned. Click a marker to see the photo.', 'nz-nature' ); ?></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:shortcode {"align":"wide"} -->
[nz_photo_map scope="all" height="520" tile="osm"]
<!-- /wp:shortcode -->
