<?php

/**
 * Support class for Get The Image plugin
 *
 * @since 3.0.2+
 *
 */
class Instant_Articles_Get_The_Image {

	/**
	 * Init the compat layer
	 *
	 */
	public function init() {
		add_filter( 'instant_articles_transformer_rules_loaded', array( 'Instant_Articles_Get_The_Image', 'transformer_loaded' ) );
	}

	public static function transformer_loaded( $transformer ) {
		// Appends more rules to transformer
		$file_path     = plugin_dir_path( __FILE__ ) . 'get-the-image-rules-configuration.json';
		$configuration = file_get_contents( $file_path ); // phpcs:ignore WordPressVIPMinimum.VIP.FetchingRemoteData.fileGetContentsUknown
		$transformer->loadRules( $configuration );

		return $transformer;
	}
}
