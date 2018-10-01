<?php

/**
 * Support class for Themify
 *
 * @since 3.3.2
 *
 */
class Instant_Articles_Themify {

	/**
	 * Init the compat layer
	 *
	 */
	function init() {
		add_filter( 'instant_articles_transformer_rules_loaded', array( 'Instant_Articles_Themify', 'transformer_loaded' ) );
	}

	public static function transformer_loaded( $transformer ) {
		// Appends more rules to transformer
		$file_path = plugin_dir_path( __FILE__ ) . 'themify-rules-configuration.json';
		$configuration = file_get_contents( $file_path );
		$transformer->loadRules( $configuration );

		return $transformer;
	}
}
