<?php
/**
 * Support class for Google Tag Manager for WordPress by DuracellTomi
 *
 * @since 4.0.7
 */
class Instant_Articles_Google_Tag_Manager_For_WordPress {

	/**
	 * Init the compat layer and add the instantArticle variable to the GTM4WP dataLayer.
	 */
	public function init() {
		add_action( 'instant_articles_compat_registry_analytics', array( $this, 'add_to_registry' ), 10, 1 );
		add_filter( 'gtm4wp_compile_datalayer', array( $this, 'add_ia_status_to_data_layer' ), 10, 1 );
	}
	/**
	 * Adds identifying information about this 3rd party plugin
	 * to the wider registry.
	 *
	 * @since 4.0.7
	 * @param array $registry Reference param. The registry where it will be stored.
	 */
	public function add_to_registry( &$registry ) {
		if ( ! function_exists( 'gtm4wp_wp_header_begin' ) ) {
			include_once WP_PLUGIN_DIR . '/duracelltomi-google-tag-manager/public/frontend.php';
		}

		$display_name            = 'Google Tag Manager by DuracellTomi (will also add an instantArticle variable to dataLayer)';
		$identifier              = 'duracelltomi-google-tag-manager';
		$registry[ $identifier ] = array(
			'name'    => $display_name,
			'payload' => gtm4wp_wp_header_begin( false ),
		);
	}

	public function add_ia_status_to_data_layer( $data_layer ) {
		$data_layer['instantArticle'] = \is_transforming_instant_article();

		return $data_layer;
	}
}
