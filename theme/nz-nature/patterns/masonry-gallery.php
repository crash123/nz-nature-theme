<?php
/**
 * Title: Masonry gallery
 * Slug: nz-nature/masonry-gallery
 * Categories: nz-nature, gallery
 * Block Types: core/gallery
 * Viewport Width: 1400
 * Description: A variable-height masonry gallery that opens in the lightbox. Add your photos to it.
 */
?>
<!-- wp:gallery {"columns":3,"linkTo":"media","className":"is-style-masonry"} -->
<figure class="wp-block-gallery has-nested-images columns-3 is-style-masonry">
	<!-- wp:paragraph -->
	<p><?php esc_html_e( 'Add your photos here — the masonry layout fills the columns as you go.', 'nz-nature' ); ?></p>
	<!-- /wp:paragraph -->
</figure>
<!-- /wp:gallery -->
