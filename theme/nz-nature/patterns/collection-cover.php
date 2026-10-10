<?php
/**
 * Title: Collection cover
 * Slug: nz-nature/collection-cover
 * Categories: nz-nature, featured
 * Viewport Width: 1400
 * Description: A full-width cover panel with heading, intro and a link to a collection.
 */
?>
<!-- wp:cover {"url":"","dimRatio":40,"overlayColor":"pounamu-deep","minHeight":420,"style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70);min-height:420px">
	<span aria-hidden="true" class="wp-block-cover__background has-pounamu-deep-background-color has-background-dim-40 has-background-dim"></span>
	<div class="wp-block-cover__inner-container">
		<!-- wp:heading {"textColor":"sand","fontSize":"xx-large"} -->
		<h2 class="wp-block-heading has-sand-color has-text-color has-xx-large-font-size"><?php esc_html_e( 'Collection name', 'nz-nature' ); ?></h2>
		<!-- /wp:heading -->
		<!-- wp:paragraph {"textColor":"limestone"} -->
		<p class="has-limestone-color has-text-color"><?php esc_html_e( 'A short description of this trip or collection.', 'nz-nature' ); ?></p>
		<!-- /wp:paragraph -->
		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button -->
			<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/galleries/"><?php esc_html_e( 'View collection', 'nz-nature' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
</div>
<!-- /wp:cover -->
