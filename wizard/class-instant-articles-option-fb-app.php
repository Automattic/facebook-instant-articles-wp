<?php
/**
 * Facebook Instant Articles for WP.
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package default
 */

require_once dirname( __FILE__ ) . '/class-instant-articles-option.php';

/**
 * FB Page configuration.
 */
class Instant_Articles_Option_FB_App extends Instant_Articles_Option {

	const OPTION_KEY = 'instant-articles-option-fb-app';

	public static $sections = array(
		'title'       => 'Facebook App',
		'description' => '<p>Configure your Facebook App to enable auto-invalidation of the cache when updating articles</p>',
	);

	public static $fields = array(
		'app_id'            => array(
			'visible'     => true,
			'label'       => 'Facebook App ID',
			'default'     => '',
			'description' => 'Provide a valid App ID',
		),
		'app_secret'        => array(
			'visible'     => true,
			'label'       => 'Facebook App Secret',
			'default'     => '',
			'description' => 'Provide a valid App Secret',
		),

		'page_access_token' => array(
			'visible'     => true,
			'label'       => 'Page Access Token',
			'default'     => '',
			'description' => 'Provide a valid access token for your Page',
		),
	);

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 */
	public function __construct() {
		$this->options_manager = new parent(
			self::OPTION_KEY,
			self::$sections,
			self::$fields
		);
	}
}
