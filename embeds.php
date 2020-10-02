<?php
/**
 * Facebook Instant Articles for WP.
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package default
 */

/**
 * Initialize the embed filters for the feed
 */
function instant_articles_init_embed_filters() {
	remove_all_filters( 'embed_oembed_html' );
	add_filter( 'embed_oembed_html', 'instant_articles_embed_oembed_html', 10, 4 );
}

/**
 * Filter the oembed results to see if we should do some extra handling
 *
 * @since 0.1
 * @param string $html     The original HTML returned from the external oembed provider.
 * @param string $url      The URL found in the content.
 * @param mixed  $attr     An array with extra attributes.
 * @param int    $post_id  The post ID.
 * @return string The potentially filtered HTML.
 */
function instant_articles_embed_oembed_html( $html, $url, $attr, $post_id ) {

	$cache_key = md5( $url . ':instant_articles_oembed_provider' );
	$provider_name = get_transient( $cache_key );

	if ( false === $provider_name ) {
		if ( ! class_exists( 'WP_oEmbed' ) ) {
			include_once( ABSPATH . WPINC . '/class-oembed.php' );
		}

		// Instead of checking all possible URL variants, use the provider list from WP_oEmbed.
		$wp_oembed = new WP_oEmbed();
		$provider_url = $wp_oembed->get_provider( $url );

		$provider_name = false;
		if ( false !== strpos( $provider_url, 'instagram.com' ) ) {
			$provider_name = 'instagram';
		} elseif ( false !== strpos( $provider_url, 'twitter.com' ) ) {
			$provider_name = 'twitter';
		} elseif ( false !== strpos( $provider_url, 'youtube.com' ) ) {
			$provider_name = 'youtube';
		} elseif( false !== strpos( $provider_url, 'vimeo.com' ) ) {
			$provider_name = 'vimeo';
		} elseif( false !== strpos( $provider_url, 'vine.co' ) ) {
			$provider_name = 'vine';
		} elseif( false !== strpos( $provider_url, 'facebook.com' ) ) {
			$provider_name = 'facebook';
		}

		$provider_name = apply_filters( 'instant_articles_social_embed_type', $provider_name, $url );

		if ( false === $provider_name ) {
			// We cannot properly cache `false`, so let's use a different value we can check for.
			set_transient( $cache_key, 'no_provider', HOUR_IN_SECONDS * 12 );
		} else {
			set_transient( $cache_key, $provider_name );
		}
	}

	// Change cacheable `'no_provider'` to `false`.
	if ( 'no_provider' === $provider_name ) {
		$provider_name = false;
	}

	if ( $provider_name ) {
		$html = instant_articles_embed_get_html( $provider_name, $html, $url, $attr, $post_id );
		delete_transient( $cache_key );
	}

	return $html;

}


/**
 * Filter the embed results for embeds.
 *
 * @since 0.1
 * @param string $provider_name  The name of the embed provider. E.g. “instagram” or “youtube”.
 * @param string $html           The original HTML returned from the external oembed/embed provider.
 * @param string $url            The URL found in the content.
 * @param mixed  $attr           An array with extra attributes.
 * @param int    $post_id        The post ID.
 * @return string The filtered HTML.
 */
function instant_articles_embed_get_html( $provider_name, $html, $url, $attr, $post_id ) {

	// Don't try to fix embeds unless we're in Instant Articles context.
	// This prevents mangled output on frontend.
	if ( ! is_transforming_instant_article() ) {
			return $html;
	}

	/**
	 * Filter the HTML that will go into the Instant Article Social Embed markup.
	 *
	 * @since 0.1
	 * @param string $html     The HTML.
	 * @param string $url      The URL found in the content.
	 * @param mixed  $attr     An array with extra attributes.
	 * @param int    $post_id  The post ID.
	 */
	$html = apply_filters( "instant_articles_social_embed_{$provider_name}", $html, $url, $attr, $post_id );

	$html = sprintf( '<div class="embed">%s</div>', $html );

	/**
	 * Filter the Instant Article Social Embed markup.
	 *
	 * @since 0.1
	 * @param string $html     The Social Embed markup.
	 * @param string $url      The URL found in the content.
	 * @param mixed  $attr     An array with extra attributes.
	 * @param int    $post_id  The post ID.
	 */
	$html = apply_filters( 'instant_articles_social_embed', $html, $url, $attr, $post_id );

	return $html;
}
