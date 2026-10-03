<?php
/**
 * Title: Recent field notes
 * Slug: nz-nature/recent-posts
 * Categories: nz-nature, posts
 * Block Types: core/query
 * Viewport Width: 1400
 * Description: A three-column grid of the latest posts.
 */
?>
<!-- wp:group {"align":"wide","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
<div class="wp-block-group alignwide" style="margin-bottom:var(--wp--preset--spacing--50)">
	<!-- wp:heading {"level":2} -->
	<h2 class="wp-block-heading">Recent field notes</h2>
	<!-- /wp:heading -->
	<!-- wp:paragraph {"textColor":"fern"} -->
	<p class="has-fern-color has-text-color">Latest entries from the trail.</p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:query {"queryId":10,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"align":"wide"} -->
<div class="wp-block-query alignwide">
	<!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->
		<!-- wp:group {"className":"is-style-driftwood-card","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"radius":"14px"}},"backgroundColor":"base","layout":{"type":"constrained"}} -->
		<div class="wp-block-group is-style-driftwood-card has-base-background-color has-background" style="border-radius:14px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
			<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"4/3","style":{"border":{"radius":"10px"}}} /-->
			<!-- wp:post-title {"isLink":true,"level":3,"fontSize":"large"} /-->
			<!-- wp:post-date {"fontSize":"small","textColor":"fern"} /-->
		</div>
		<!-- /wp:group -->
	<!-- /wp:post-template -->
</div>
<!-- /wp:query -->
