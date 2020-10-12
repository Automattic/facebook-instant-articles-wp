<?php
/**
 * Facebook Instant Articles for WP.
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package default
 * @since 4.0
 */

use Facebook\InstantArticles\AMP\AMPArticle;
use Facebook\InstantArticles\Validators\Type;

class Instant_Articles_AMP_Markup {

	const SETTING_AMP_MARKUP = 'amp_markup'; // Setting to check if AMP Markup generation is enabled
	const SETTING_STYLE      = 'amp_stylesheet'; // Setting that stores the JSON stylesheet
	const SETTING_DL_MEDIA   = 'amp_download_media'; // Enable or disable media download option
	const QUERY_ARG          = 'amp_markup'; // Query argument that will trigger the AMP markup generation

	// To memoize the settings
	public static $settings = null;

	/**
	 * Gets the settings
	 *
	 * @return array The settings, check Instant_Articles_Option::get_option_decoded()
	 * @since 4.0
	 */
	public static function get_settings() {
		if ( null === self::$settings ) {
			self::$settings = Instant_Articles_Option_AMP::get_option_decoded();
		}

		return self::$settings;
	}

	/**
	 * Checks if the AMP markup is enabled
	 *
	 * @return bool true if markup is enabled
	 * @since 4.0
	 */
	public static function is_markup_enabled() {
		$settings = self::get_settings();

		return isset( $settings[ self::SETTING_AMP_MARKUP ] )
			? (bool) $settings[ self::SETTING_AMP_MARKUP ]
			: false;
	}

	/**
	 * Adds the meta tag for AMP Markup if enabled
	 *
	 * @since 4.0
	 */
	public static function inject_link_rel() {

		if ( ! self::is_markup_enabled() ) {
			return;
		}

		// Transform the post to an Instant Article.
		$adapter = new Instant_Articles_Post( get_post() );

		if ( ! $adapter->should_submit_post() ) {
			return;
		}

		$url = $adapter->get_canonical_url();
		$url = add_query_arg( self::QUERY_ARG, '1', $url );

		?>
		<link rel="amphtml" href="<?php echo esc_url( $url ); ?>">
		<?php
	}

	/**
	 * Generates the AMP markup if post has amp_markup
	 *
	 * NOTE: side-effect: function calls die() in the end.
	 *
	 * @since 4.0
	 */
	public static function markup_version() {
		if ( ! ( isset( $_GET[ self::QUERY_ARG ] ) && sanitize_text_field( $_GET[ self::QUERY_ARG ] ) ) ) { // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected
			return;
		}

		$settings = self::get_settings();

		if ( ! self::is_markup_enabled() ) {
			return;
		}

		$has_stylesheet =
			isset( $settings[ self::SETTING_STYLE ] )
			? self::validate_json( $settings[ self::SETTING_STYLE ] )
			: false;

		$properties = array();

		$download_media =
			isset( $settings[ self::SETTING_DL_MEDIA ] )
			? (bool) $settings[ self::SETTING_DL_MEDIA ]
			: false;

		$properties[ AMPArticle::ENABLE_DOWNLOAD_FOR_MEDIA_SIZING_KEY ] = $download_media;

		if ( $has_stylesheet ) {
			$properties[ AMPArticle::OVERRIDE_STYLES_KEY ] =
				json_decode( $settings[ self::SETTING_STYLE ], true );
		}

		$post = get_post();

		// This array will hold the image sizes
		$media_sizes = array();

		// Get all children with mime type image that are attached to the posts
		// NOTE: this will not get images that are not hosted in the WP
		$query_args     = array(
			'post_parent'    => $post->ID,
			'post_type'      => 'attachment',
			'numberposts'    => 100,
			'post_mime_type' => 'image',
		);
		$image_children = get_children( $query_args );

		foreach ( $image_children as $img_id => $img ) {
			$meta = wp_get_attachment_metadata( $img_id );

			// Removes the file name from the URL
			$url_chunks = explode( '/', $img->guid );
			array_pop( $url_chunks );
			$base_image_url = implode( '/', $url_chunks ) . '/';

			// This is the uploaded original file
			$media_sizes[ $img->guid ] = array( $meta['width'], $meta['height'] );

			// These are the possible redimensions
			foreach ( $meta['sizes'] as $size ) {
				$size_url                 = $base_image_url . $size['file'];
				$media_sizes[ $size_url ] = array( $size['width'], $size['height'] );
			}
		}

		$properties[ AMPArticle::MEDIA_SIZES_KEY ] = $media_sizes;

		// Transform the post to an Instant Article.
		$adapter      = new Instant_Articles_Post( $post );
		$article      = $adapter->to_instant_article();
		$article_html = $article->render();
		$amp          = AMPArticle::create( $article_html, $properties );
		echo $amp->render();

		die();
	}

	/**
	 * Helper function to validate the json string
	 *
	 * @param $json_str string JSON string
	 * @return bool true for valid JSON
	 * @since 4.0
	 */
	public static function validate_json( $json_str ) {
		if ( Type::isTextEmpty( $json_str ) ) {
			return false;
		}

		json_decode( $json_str );
		if ( json_last_error() === JSON_ERROR_NONE ) {
			return true;
		}

		return false;
	}
}
