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
 * Analytics configuration class.
 */
class Instant_Articles_Option_Analytics extends Instant_Articles_Option {

	const OPTION_KEY = 'instant-articles-option-analytics';

	public static $sections = array(
		'title'       => 'Analytics',
		'description' => '<p>Enable 3rd-party analytics to be used with Instant Articles.</p><p>If you already use a WordPress plugin to manage analytics, you can enable it below. You can also embed code to insert your own trackers and analytics. <a href="https://developers.facebook.com/docs/instant-articles/ads-analytics#analytics" target="_blank">Learn more about Analytics in Instant Articles</a>.</p>',
	);

	public static $fields = array(

		'integrations'       => array(
			'label'   => '3rd party integrations',
			'render'  => array( 'Instant_Articles_Option_Analytics', 'custom_render_integrations' ),
			'default' => array(),
		),

		'comscore_id'        => array(
			'label'   => 'Comscore ID',
			'render'  => 'text',
			'default' => false,
		),

		'embed_code_enabled' => array(
			'label'          => 'Embed code',
			'render'         => 'checkbox',
			'default'        => false,
			'description'    => 'Add code for any other analytics services you wish to use.',
			'checkbox_label' => 'Enable custom embed code',
		),

		'embed_code'         => array(
			'label'         => '',
			'render'        => 'textarea',
			'placeholder'   => '<script>...</script>',
			'description'   => 'Note: You do not need to include any &lt;op-tracker&gt; tags. The plugin will automatically include them in the article markup.',
			'default'       => '',
			'double_encode' => true,
		),
	);

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 */
	public function __construct() {
		parent::__construct(
			self::OPTION_KEY,
			self::$sections,
			self::$fields
		);
		wp_localize_script(
			'instant-articles-option-analytics',
			'INSTANT_ARTICLES_OPTION_ANALYTICS',
			array(
				'option_field_id_embed_code_enabled' => self::OPTION_KEY . '-embed_code_enabled',
				'option_field_id_embed_code'         => self::OPTION_KEY . '-embed_code',
			)
		);
	}

	/**
	 * Renders the markup for the `integrations` field.
	 *
	 * @param array $args The array with configuration of fields.
	 * @since 0.4
	 */
	public static function custom_render_integrations( $args ) {
		$name = $args['serialized_with_group'] . '[integrations][]';

		$compat_plugins = parent::get_registered_compat( 'instant_articles_compat_registry_analytics' );

		if ( empty( $compat_plugins ) ) {
			?>
			<em>
				<?php echo esc_html( 'No supported analytics plugins are installed nor activated' ); ?>
			</em>
			<?php

			return;
		}

		asort( $compat_plugins );
		foreach ( $compat_plugins as $plugin_id => $plugin_info ) {
			?>
			<label>
				<input
					type="checkbox"
					name="<?php echo esc_attr( $name ); ?>"
					value="<?php echo esc_attr( $plugin_id ); ?>"
					<?php echo checked( in_array( $plugin_id, self::$settings['integrations'], true ) ); ?>
				>
				<?php echo esc_html( $plugin_info['name'] ); ?>
			</label>
			<br />
			<?php
		}
		?>
		<p class="description">Select which analytics services you'd like to use with Instant Articles.</p>
		<?php
	}

	/**
	 * Sanitize and return all the field values.
	 *
	 * This method receives a payload containing all value for its fields and
	 * should return the same payload after having been sanitized.
	 *
	 * Do not encode the payload as this is performed by the
	 * universal_sanitize_and_encode_handler() of the parent class.
	 *
	 * @param array $field_values The values in an array mapped keys.
	 * @since 0.5
	 */
	public function sanitize_option_fields( $field_values ) {
		foreach ( $field_values as $field_id ) {

			switch ( $field_id ) {
				case 'embed_code':
					if ( isset( $field_values['embed_code_enabled'] ) && $field_values['embed_code_enabled'] ) {
						$document = new DOMDocument();
						$fragment = $document->createDocumentFragment();
						if ( ! $fragment->appendXML( $field_values[ $field_id ] ) ) {
							add_settings_error(
								'embed_code',
								'invalid_markup',
								'Invalid HTML markup provided for custom analytics tracker code'
							);
						}
					}

					break;
			}
		}

		return $field_values;
	}
}
