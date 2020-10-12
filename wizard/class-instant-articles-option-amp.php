<?php
/**
 * Facebook Instant Articles for WP.
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package default
 */

require_once dirname( __FILE__ ) . '/class-instant-articles-option.php';
require_once dirname( dirname( __FILE__ ) ) . '/class-instant-articles-amp-markup.php';

use Facebook\InstantArticles\Validators\Type;

/**
 * AMP Generation Settings
 */
class Instant_Articles_Option_AMP extends Instant_Articles_Option {

	const OPTION_KEY = 'instant-articles-option-amp';

	public static $sections = array(
		'title'       => 'AMP Markup Generation',
		'description' => '<p>Settings to control the IA to AMP conversion, check the <a href="https://github.com/facebook/facebook-instant-articles-sdk-extensions-in-php" target="_blank">project\'s repository</a> for more info.</p>',
	);

	public static $fields = array(

		Instant_Articles_AMP_Markup::SETTING_AMP_MARKUP => array(
			'label'          => 'Enable Markup (experimental)',
			'description'    => 'With this option enabled, posts will also be available in AMP markup. The generated mark-up can be accessed by adding ?amp_markup=1 to the URL of a post.',
			'render'         => 'checkbox',
			'default'        => false,
			'checkbox_label' => 'Enable AMP markup generation',
		),

		Instant_Articles_AMP_Markup::SETTING_STYLE      => array(
			'label'       => 'Instant Article JSON Style',
			'description' => 'Please paste the contents of the Style JSON file (<a href="https://developers.facebook.com/docs/instant-articles/other-formats#style" target="_blank">downloaded from the Publishing Tools</a>)',
			'default'     => '',
			'render'      => 'textarea',
		),

		Instant_Articles_AMP_Markup::SETTING_DL_MEDIA   => array(
			'label'          => 'Automatic Image sizing',
			'description'    => 'With this option enabled, images in other servers/domains will be downloaded to get their width and height. Learn more in <a href="https://developers.facebook.com/docs/instant-articles/other-formats#https://developers.facebook.com/docs/instant-articles/other-formats#advanced-media-element-sizing" target="_blank"> the official docs</a>',
			'render'         => 'checkbox',
			'checkbox_label' => 'Download all external images to get their dimensions (slow)',
			'default'        => false,
		),
	);
	/**
	 * Sanitize and return all the field values.
	 *
	 * This method receives a payload containing all value for its fields and
	 * should return the same payload after having been sanitized.
	 *
	 * Do not encode the payload as this is performed by the
	 * universal_sanitize_and_encode_handler() of the parent class.
	 *
	 * @param array $field_values array map with key field_id => value.
	 * @since 4.0
	 */
	public function sanitize_option_fields( $field_values ) {
		$old_settings = Instant_Articles_AMP_Markup::get_settings();

		if ( isset( $field_values[ Instant_Articles_AMP_Markup::SETTING_STYLE ] ) && ! empty( $field_values[ Instant_Articles_AMP_Markup::SETTING_STYLE ] ) ) {
			if ( ! Instant_Articles_AMP_Markup::validate_json( $field_values[ Instant_Articles_AMP_Markup::SETTING_STYLE ] ) ) {
				add_settings_error(
					Instant_Articles_AMP_Markup::SETTING_STYLE,
					'invalid_json',
					'Invalid Style JSON provided'
				);

				$field_values[ Instant_Articles_AMP_Markup::SETTING_STYLE ] =
						isset( $old_settings[ Instant_Articles_AMP_Markup::SETTING_STYLE ] )
						? $old_settings[ Instant_Articles_AMP_Markup::SETTING_STYLE ]
						: '';
			}
		}

		return $field_values;
	}

	/**
	 * Constructor.
	 *
	 * @since 4.0
	 */
	public function __construct() {
		parent::__construct(
			self::OPTION_KEY,
			self::$sections,
			self::$fields
		);
	}
}
