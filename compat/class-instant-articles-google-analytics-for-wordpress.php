<?php

/**
 * Support class for Google Analytics for WordPress (Google Analytics by MonsterInsights)
 *
 * @since 0.1
 */
class Instant_Articles_Google_Analytics_For_WordPress {

	/**
	 * File and path for analytics.
	 *
	 * @var string $plugin_file File and path of googleanalytics.
	 */
	public static $plugin_file = 'google-analytics-for-wordpress/googleanalytics.php';

	/**
	 * Init the compat layer
	 */
	function init() {
		add_action( 'instant_articles_compat_registry_analytics', array( $this, 'add_to_registry' ) );
	}

	/**
	 * Adds identifying information about this 3rd party plugin
	 * to the wider registry.
	 *
	 * @since 0.3
	 * @param array $registry Reference param. The registry where it will be stored.
	 */
	function add_to_registry( &$registry ) {

		$display_name = 'Google Analytics by MonsterInsights';

		$identifier = 'google-analytics-for-wordpress';

		$embed_code = $this->get_raw_embed_code();

		$registry[ $identifier ] = array(
			'name' => $display_name,
			'payload' => $embed_code,
		);
	}

	/**
	 * Returns the GA tracking code
	 *
	 * @since 0.3
	 */
	function get_raw_embed_code() {

		ob_start();

		if ( function_exists( 'monsterinsights_tracking_script' ) ) {
			monsterinsights_tracking_script();
		} else {
			$options = Yoast_GA_Options::instance()->options;

			if ( isset( $options['enable_universal'] ) && 1 === intval( $options['enable_universal'] ) ) {
				$tracker = new Yoast_GA_Universal;
			} else {
				$tracker = new Yoast_GA_JS;
			}

			$tracker->tracking();
		}

		$ga_code = ob_get_clean();

		return $ga_code;
	}
}
