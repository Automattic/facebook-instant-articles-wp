<?php
/**
 * Compatibility layer for MonsterInsights SEO
 *
 * @since 0.1
 */
class Instant_Articles_MonsterInsights_SEO {

	/**
	 * Init the compat layer.
	 */
	function init() {
		add_filter( 'instant_articles_featured_image', array( $this, 'override_featured_image' ), 10, 2 );
		// Hook in after other author modifications (like the Co-Authors Plus plugin).
		add_filter( 'instant_articles_authors', array( $this, 'user_url' ), 11 , 2 );
	}

	/**
	 * Override the featured image with the one set for Facebook.
	 *
	 * @since 0.1
	 * @param array $image_data  The current image data.
	 * @param int   $post_id  The instant article post.
	 * @return array The filtered image data.
	 */
	function override_featured_image( $image_data, $post_id ) {

		$image_url = get_post_meta( $post_id, '_yoast_wpseo_opengraph-image', true );

		if ( strlen( $image_url ) ) {
			$image_data['src'] = $image_url;

			$desc = get_post_meta( $post_id, '_yoast_wpseo_opengraph-description', true );

			if ( strlen( $desc ) ) {
				$image_data['caption'] = $desc;
			}
		}

		return $image_data;
	}

	/**
	 * Use the facebook URL as user_url if no other is set.
	 *
	 * @since 0.1
	 * @param array $authors  The current post author(s).
	 * @param int   $post_id  The instant article post.
	 * @return array The filtered authors.
	 */
	function user_url( $authors, $post_id ) {

		foreach ( $authors as $author ) {
			if ( ! strlen( $author->user_url ) ) {
				$facebook_profile_url = get_user_meta( $author->ID, 'facebook', true );
				if ( strlen( $facebook_profile_url ) ) {
					$author->user_url = $facebook_profile_url;
					$author->user_url_rel = 'facebook';
				}
			}
		}

		return $authors;

	}
}
