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
class Instant_Articles_Option_FB_Page extends Instant_Articles_Option {

	const OPTION_KEY = 'instant-articles-option-fb-page';

	public static $sections = array(
		'title'       => 'Facebook Page',
		'description' => '<p>Follow <a href="https://www.facebook.com/instant_articles/signup?__mref=facebook-instant-articles-wp" target="_blank">these instructions</a> to sign up to Instant Articles and get your Facebook Page ID.</p>',
	);

	public static $fields = array(

		'page_id' => array(
			'visible'     => true,
			'label'       => 'Facebook Page ID',
			'default'     => '',
			'description' => 'Fill in your Facebook Page ID.',
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
