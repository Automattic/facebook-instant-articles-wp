<?php
/**
 * Facebook Instant Articles for WP.
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package default
 */

require_once( dirname( __FILE__ ) . '/class-instant-articles-option.php' );

/**
 * Class for publishing configuration.
 */
class Instant_Articles_Option_Publishing extends Instant_Articles_Option {

	const OPTION_KEY = 'instant-articles-option-publishing';

	public static $sections = array(
		'title' => 'Publishing Settings',
	);

	public static $fields = array(

		'categories' => array(
			'label' => 'Categories',
			'description' => 'Limit your feed to the selected categories. Hold down CTRL/Command to select multiple categories. If you do not select any categories, they will all be included in the feed.',
			'render' => array( 'Instant_Articles_Option_Publishing', 'custom_render_categories' ),
			'default' => '',
		),

		'dev_mode' => array(
			'label' => 'Development Mode',
			'description' => 'Articles published while in Development Mode are saved as "drafts" within Facebook and will not be made live. Note: Since articles in "draft" are not reviewed, Development Mode should be disabled when publishing articles to Facebook which you intend to use in your <a href="https://developers.facebook.com/docs/instant-articles/publishing#review" target="_blank">one-time review</a>.',
			'render' => 'checkbox',
			'default' => false,
			'checkbox_label' => 'Enable development mode',
		),

		'custom_rules_enabled' => array(
			'label' => 'Custom transformer rules',
			'render' => 'checkbox',
			'default' => '',
			'checkbox_label' => 'Enable custom transformer rules',
			'description' => 'Define your own rules to customize the transformation of your content into Instant Articles',
			'default' => '',
		),

		'custom_rules' => array(
			'label' => '',
			'render' => 'textarea',
			'placeholder' => '{ "rules": [{ "class": "BoldRule", "selector": "span.bold" }, ... ] }',
			'description' => 'Read more about <a href="https://github.com/facebook/facebook-instant-articles-sdk-php/blob/master/docs/QuickStart.md#custom-transformer-rules" target="_blank">defining your own custom rules</a> to extend/override the <a href="https://github.com/Automattic/facebook-instant-articles-wp/blob/master/rules-configuration.json" target="_blank">built-in ruleset</a>. If you\'ve defined a common rule which you think this plugin should include by default, <a href="https://github.com/Automattic/facebook-instant-articles-wp/issues/new" target="_blank">tell us about it</a>!',
			'default' => ''
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
		wp_localize_script( 'instant-articles-option-publishing', 'INSTANT_ARTICLES_OPTION_PUBLISHING', array(
			'option_field_id_custom_rules_enabled' => self::OPTION_KEY . '-custom_rules_enabled',
			'option_field_id_custom_rules'         => self::OPTION_KEY . '-custom_rules',
			'option_field_id_categories' 		   => self::OPTION_KEY . '-custom_rules_categories'
		) );
	}

	/**
	 * Renders the category list.
	 *
	 * @param array $args configuration fields.
	 * @since 0.4
	 */
	public static function custom_render_categories( $args ) {
		$id = $args['label_for'];
		$name = $args['serialized_with_group'] . '[categories][]';

		$args['select_options'] = get_terms('category', array('fields' => 'id=>name'));

		$description = isset( $args['description'] )
			? '<p class="description">' . esc_html( $args['description'] ) . '</p>'
			: '';

		?>
		<select
			id="<?php echo esc_attr( $id ); ?>"
			name="<?php echo esc_attr( $name ); ?>"
			multiple
		>
		<?php foreach ( $args['select_options'] as $category_id => $category_name ) : ?>
			<option
				value="<?php echo esc_attr( $category_id ); ?>"
				<?php echo in_array($category_id, explode(',', self::$settings['categories'])) ? 'selected' : ''; ?>
			>
			<?php echo esc_html( $category_name ); ?>
			</option>
		<?php endforeach; ?>

		</select>
		<?php echo $description; ?>
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
	 * @param array $field_values array map with key field_id => value.
	 * @since 0.5
	 */
	public function sanitize_option_fields( $field_values ) {
		foreach ( $field_values as $field_id => $field_value ) {
			$field = self::$fields[ $field_id ];

			switch ( $field_id ) {

				case 'categories':
					$field_values[ $field_id ] = implode( ',', $field_values[ $field_id ] );

					foreach( $field_values[ $field_id ] as $category_id ) {
						$term = term_exists($category_id, 'category');
						if ( $term === 0 || $term === null ) {
							add_settings_error(
								$field_id,
								'invalid_category',
								'Invalid category provided'
							);

							$field_values[ $field_id ] = $field['default'];
						}
					}
				break;

				case 'dev_mode':
					$field_values[ $field_id ] = (bool) $field_value
						? (string) true
						: (string) $field['default'];
				break;

				case 'custom_rules':
					if ( isset( $field_values['custom_rules_enabled'] ) && $field_values['custom_rules_enabled'] ) {
						$custom_rules_json = json_decode( $field_values['custom_rules'] );
						if ( $custom_rules_json === null ) {
							$field_values['custom_rules'] = $field['default'];
							add_settings_error(
								'custom_embed',
								'invalid_json',
								'Invalid JSON provided for custom rules code'
							);
						}
					}
				break;

				default:
					// Should never happen.
				break;
			}
		}

		return $field_values;
	}
}
