<?php
/**
 * Collections: a category card grid for the homepage.
 *
 * @package NZ_Nature
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Collapse newlines/indentation in shortcode output.
 *
 * WordPress's wpautop turns single newlines in shortcode HTML into <br> tags,
 * which would become stray items inside CSS grids. Minifying avoids that.
 *
 * @param string $html Raw shortcode HTML.
 * @return string
 */
function nz_nature_minify_shortcode_html( $html ) {
	$html = preg_replace( '/\s*\n\s*/', '', (string) $html );
	return preg_replace( '/>\s+</', '><', $html );
}

/**
 * Render the [nz_collections] shortcode.
 *
 * Each "collection" is a post category. The cover image is the newest
 * featured image inside that category; a motif SVG is used as a fallback.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function nz_nature_collections_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'columns'      => 3,
			'exclude'      => 'uncategorized,posts',
			'count'        => 9,
			'show_count'   => 'yes',
			'show_all'     => 'no',
		),
		$atts,
		'nz_collections'
	);

	$exclude = array_filter( array_map( 'sanitize_title', explode( ',', (string) $atts['exclude'] ) ) );

	$categories = get_categories( array(
		'orderby'    => 'name',
		'order'      => 'ASC',
		'hide_empty' => true,
	) );

	if ( $categories ) {
		$categories = array_values( array_filter( $categories, function ( $term ) use ( $exclude ) {
			return ! in_array( $term->slug, $exclude, true );
		} ) );
	}

	if ( empty( $categories ) ) {
		return '';
	}

	$columns = max( 1, min( 4, absint( $atts['columns'] ) ) );
	$limit   = absint( $atts['count'] );
	if ( $limit > 0 ) {
		$categories = array_slice( $categories, 0, $limit );
	}

	$fallback = get_template_directory_uri() . '/assets/images/mountains.svg';

	ob_start();
	?>
	<div class="nz-collections nz-collections--cols-<?php echo esc_attr( $columns ); ?>">
	<?php foreach ( $categories as $category ) :
		$cover_url = '';
		$query     = new WP_Query( array(
			'category__in'        => array( $category->term_id ),
			'posts_per_page'      => 1,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'meta_key'            => '_thumbnail_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		) );
		if ( $query->have_posts() ) {
			$thumb_id  = get_post_thumbnail_id( $query->posts[0] );
			$cover_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'nz-cover' ) : '';
		}
		wp_reset_postdata();
		if ( ! $cover_url ) {
			$cover_url = $fallback;
		}
		?>
		<a class="nz-collection" href="<?php echo esc_url( get_category_link( $category->term_id ) ); ?>">
			<span class="nz-collection__cover" style="background-image:url('<?php echo esc_url( $cover_url ); ?>');" aria-hidden="true"></span>
			<span class="nz-collection__body">
				<span class="nz-collection__name"><?php echo esc_html( $category->name ); ?></span>
				<?php if ( 'yes' === $atts['show_count'] ) : ?>
					<span class="nz-collection__count">
						<?php
						printf(
							/* translators: %s: number of photos. */
							esc_html( _n( '%s photo', '%s photos', $category->count, 'nz-nature' ) ),
							esc_html( number_format_i18n( $category->count ) )
						);
						?>
					</span>
				<?php endif; ?>
			</span>
		</a>
	<?php endforeach; ?>
	</div>
	<?php
	return nz_nature_minify_shortcode_html( ob_get_clean() );
}
add_shortcode( 'nz_collections', 'nz_nature_collections_shortcode' );

/**
 * Render the [nz_latest_photos] shortcode.
 *
 * A tidy grid of recent posts that have a featured image (i.e. galleries),
 * excluding a category (the written posts by default).
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function nz_nature_latest_photos_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'count'   => 6,
			'columns' => 3,
			'exclude' => 'posts',
		),
		$atts,
		'nz_latest_photos'
	);

	$args = array(
		'post_type'           => 'post',
		'posts_per_page'      => max( 1, absint( $atts['count'] ) ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'meta_key'            => '_thumbnail_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	);

	$exclude = array_filter( array_map( 'sanitize_title', explode( ',', (string) $atts['exclude'] ) ) );
	if ( $exclude ) {
		$exclude_ids = array();
		foreach ( $exclude as $slug ) {
			$term = get_category_by_slug( $slug );
			if ( $term ) {
				$exclude_ids[] = (int) $term->term_id;
			}
		}
		if ( $exclude_ids ) {
			$args['cat'] = '-' . implode( ',', $exclude_ids );
		}
	}

	$query = new WP_Query( $args );
	if ( ! $query->have_posts() ) {
		return '';
	}

	$columns = max( 1, min( 4, absint( $atts['columns'] ) ) );

	ob_start();
	?>
	<div class="nz-photos nz-photos--cols-<?php echo esc_attr( $columns ); ?>">
		<?php
		while ( $query->have_posts() ) :
			$query->the_post();
			$cover = get_the_post_thumbnail_url( get_the_ID(), 'nz-cover' );
			?>
			<a class="nz-photo" href="<?php the_permalink(); ?>">
				<span class="nz-photo__img" style="background-image:url('<?php echo esc_url( $cover ); ?>');" aria-hidden="true"></span>
				<span class="nz-photo__body">
					<span class="nz-photo__title"><?php the_title(); ?></span>
					<span class="nz-photo__meta"><?php echo esc_html( get_the_date() ); ?></span>
				</span>
			</a>
		<?php endwhile; ?>
	</div>
	<?php
	wp_reset_postdata();
	return nz_nature_minify_shortcode_html( ob_get_clean() );
}
add_shortcode( 'nz_latest_photos', 'nz_nature_latest_photos_shortcode' );

