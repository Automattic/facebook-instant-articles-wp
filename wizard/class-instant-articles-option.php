<?php
/**
 * Facebook Instant Articles for WP.
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package default
 */

/**
 * Base class for configurations.
 */
class Instant_Articles_Option {

	const PAGE_OPTION_GROUP        = 'instant-articles-settings';
	const PAGE_OPTION_GROUP_WIZARD = 'instant-articles-settings-wizard';
	public static $fields          = array();

	/**
	 * Settings for options.
	 *
	 * @var array $settings The settings for each option.
	 */
	public static $settings = array();

	/**
	 * The key for field.
	 *
	 * @var string $key The key for field.
	 */
	private $key;

	/**
	 * Each section.
	 *
	 * @var array $sections The sections where fields will be stored/showed.
	 */
	private $sections;

	/**
	 * Page options.
	 *
	 * @var array $page_option_group
	 */
	private $page_option_group;

	/**
	 * Contains all the fields for the option. Supported field properties:
	 *
	 * Id - String. COMING SOON (to avoid using the key as the identifier)
	 * visible - Boolean. Use this to hide the entire row of the rendered field in the UI. Defaults to true.
	 * disable - Boolean|Array. Use to place the `disabled` attribute on the field. For fields with multiple options (<select>) an Array containing keys of all disabled `select_options` can be defined. Defaults to false.
	 * serialized_with_group - String|false/null. Controls whether a particular field of a setting is serialized with the group or saved as its own independant option. Defaults to true.
	 * label - String|null. The label for the field.
	 * description - String. The description for the field, rendered as additional information below the field.
	 * render - String|Function. How the field should be rendered as. Non-strings are assumed to be a function for rendering the field (third parameter of `add_settings_field()`). If a String, it is meant to be any of the standard <form> input types (checkbox, radio, textarea, hidden, select, textarea, password etc.') and the rendering will be handled by self::universal_render_handler(); if not defined, "text" is assumed
	 * select_options - Array. Defines the <options> for a checkbox (key of this Array is used as its `value` attribute). Only used if the `render` is "select".
	 * radio_options - Array. TO BE IMPLEMENTED (should mimic the "select_options" functionality)
	 * placeholder - String. Used as the `placeholder` attribute for the <form> field.
	 * default - String|null. Used as a default value for the field when there is no value yet saved in the db.
	 *
	 * @var array $fields The fields for option and its properties.
	 * @since 0.4
	 */
	private $field_definitions;

	/**
	 * If $option_group is not specified, the Option will be registered with a
	 * "default" page group (the first argument of `register_setting()` and called
	 * by `settings_fields()`).
	 *
	 * @param string $option_key The ID for the field.
	 * @param array  $sections The sections for each field.
	 * @param array  $option_fields All the fields.
	 * @param array  $option_group Optional, if not informed will use self::PAGE_OPTION_GROUP.
	 * @since 0.4
	 */
	public function __construct( $option_key, $sections, $option_fields, $option_group = null ) {
		$this->key               = $option_key;
		$this->sections          = $sections;
		$this->field_definitions = $option_fields;
		$this->page_option_group = null === $option_group
			? self::PAGE_OPTION_GROUP
			: $option_group;

		$this->init();
	}

	/**
	 * Option initiator.
	 *
	 * @since 0.4
	 */
	private function init() {
		$saved_options = self::get_option_decoded( $this->key );

		foreach ( $this->field_definitions as $field_key => $field ) {
			self::$settings[ $field_key ] = isset( $saved_options[ $field_key ] )
				? $saved_options[ $field_key ]
				: $field['default'];
		}

		$this->wp_bootstrap_register_option();
		$this->wp_bootstrap_create_sections();
		$this->wp_bootstrap_add_fields_to_section();
	}

	/**
	 * Decodes the option.
	 *
	 * @param string $option_key to be returned.
	 * @return array from a json decoded content.
	 * @since 0.4
	 */
	public static function get_option_decoded( $option_key = null ) {
		if ( ! isset( $option_key ) ) {
			// Late Static Binding to use the const OPTION_KEY from the child class which called this function.
			$option_key = static::OPTION_KEY;
		}

		$raw_data = get_option( $option_key );

		// Hack which creates an empty setting if it doesn't yet exist.
		// Temporary solution to an unknown oddity which is double-escaping the JSON
		// data as a string when attempting to access a setting that doesn't exist.
		if ( false === $raw_data ) {
			add_option( $option_key );
			$raw_data = get_option( $option_key );
		}

		return json_decode( $raw_data, true );
	}

	/**
	 * Obtains the compat related to $action_tag.
	 *
	 * @param string $action_tag The tag registered compat will be retrieved.
	 * @since 0.4
	 */
	public static function get_registered_compat( $action_tag ) {
		$registered_compat_integrations = array();

		do_action_ref_array(
			$action_tag,
			array( &$registered_compat_integrations )
		);
		return $registered_compat_integrations;
	}

	/**
	 * Registers the sanitization and encoding handler.
	 *
	 * @since 0.4
	 */
	private function wp_bootstrap_register_option() {
		register_setting(
			$this->page_option_group,
			$this->key,
			array( $this, 'universal_sanitize_and_encode_handler' )
		);
	}

	/**
	 * Create title and description sections.
	 *
	 * @since 0.4
	 */
	private function wp_bootstrap_create_sections() {
		$title = isset( $this->sections['title'] )
			? $this->sections['title']
			: '';

		$description = isset( $this->sections['description'] )
			? wp_kses(
				$this->sections['description'],
				array(
					'a'      => array(
						'href'   => array(),
						'target' => array(),
					),
					'em'     => array(),
					'p'      => array(),
					'strong' => array(),
				)
			)
			: '';

		add_settings_section(
			$this->key,
			esc_html( $title ),
			function () use ( $description ) {
				echo wp_kses_post( $description );
			},
			$this->key
		);
	}

	/**
	 * Add fields to defined section.
	 *
	 * @since 0.4
	 */
	private function wp_bootstrap_add_fields_to_section() {
		foreach ( $this->field_definitions as $field_key => $field ) {
			$standalone_id = $this->key . '-' . $field_key;

			// Default values of arguments for renderer.
			$renderer_args = array(
				// The "label_for" arg causes WordPress to wrap the label of the field with a <label> tag.
				'label_for'             => $standalone_id,
				'serialized_with_group' => $this->key,
				'render'                => 'text',
				'value'                 => self::$settings[ $field_key ],
			);

			// Override default arguments for renderer.
			foreach ( $field as $key => $val ) {
				$renderer_args[ $key ] = $val;

				// The WordPress do_settings_fields() will add a `class` attribute to
				// the <tr> tag of the rendered output with value of anything defined in
				// a "class" key of the args for the renderer.
				// We force it to include a value of "hidden" since this class is
				// defined in WordPress's global CSS with `display: none;`.
				if ( 'visible' === $key && false === $val ) {
					$renderer_args['class'] = ( ! empty( $renderer_args['class'] )
						? $renderer_args['class'] . ' '
						: '' ) . 'hidden';
				}
			}

			$renderer_handle = 'string' === gettype( $renderer_args['render'] )
				? array( 'Instant_Articles_Option', 'universal_render_handler' )
				: $renderer_args['render'];

			add_settings_field(
				$standalone_id,
				$field['label'],
				$renderer_handle,
				$this->key,
				$this->key,
				$renderer_args
			);
		}
	}

	/**
	 * Function to render all fields with its labels and inputs.
	 *
	 * @param array $args array map with its field names and values.
	 * @since 0.4
	 */
	public static function universal_render_handler( $args = null ) {
		$id = isset( $args['label_for'] )
			? $args['label_for']
			: '';

		$type = isset( $args['render'] ) && gettype( 'string' === $args['render'] )
			? $args['render']
			: 'text';

		if ( ! empty( $args['value'] ) ) {
			$option_value = $args['value'];
		} elseif ( ! empty( $args['default'] ) ) {
			$option_value = $args['default'];
		} else {
			$option_value = '';
		}

		// Determines correct values based on whether the settings option
		// is intended to be serialized as a field of a parent option name.
		if ( gettype( 'string' === $args['serialized_with_group'] ) ) {
			$group     = $args['serialized_with_group'];
			$group_key = substr( $id, strlen( $group . '-' ) );
			$name      = $group . '[' . $group_key . ']';
		} else {
			$name = $id;
		}

		$placeholder = isset( $args['placeholder'] )
			? $args['placeholder']
			: '';

		$attr_disabled = isset( $args['disable'] ) && true === $args['disable']
			? disabled()
			: '';

		$field_description = isset( $args['description'] )
			? wp_kses(
				$args['description'],
				array(
					'a'      => array(
						'href'   => array(),
						'target' => array(),
					),
					'em'     => array(),
					'strong' => array(),
				)
			)
			: '';

		$field_checkbox_label = isset( $args['checkbox_label'] )
			? $args['checkbox_label']
			: '';

		switch ( $type ) {
			case 'hidden':
				?>
				<input
					type="hidden"
					name="<?php echo esc_attr( $name ); ?>"
					id="<?php echo esc_attr( $id ); ?>"
					value="<?php echo esc_attr( $option_value ); ?>"
				/>
				<?php if ( $field_description ) : ?>
					<p class="description">
						<?php echo wp_kses_post( $field_description ); ?>
					</p>
				<?php endif; ?>
				<?php
				break;

			case 'checkbox':
				$attr_checked = checked( 1, $option_value, false );
				?>
				<label>
					<input
						type="checkbox"
						value="1"
						name="<?php echo esc_attr( $name ); ?>"
						id="<?php echo esc_attr( $id ); ?>"
						<?php echo esc_attr( $attr_checked ); ?>
						<?php echo esc_attr( $attr_disabled ); ?>
					/>
					<?php echo esc_html( $field_checkbox_label ); ?>
				</label>
				<?php if ( $field_description ) : ?>
					<p class="description">
						<?php echo wp_kses_post( $field_description ); ?>
					</p>
				<?php endif; ?>
				<?php
				break;

			case 'select':
				?>
				<select
					id="<?php echo esc_attr( $id ); ?>"
					name="<?php echo esc_attr( $name ); ?>"
					<?php echo esc_html( $attr_disabled ); ?>
				>
				<?php foreach ( $args['select_options'] as $option_key => $option_name ) : ?>
					<option
						value="<?php echo esc_attr( $option_key ); ?>"
						<?php echo selected( $option_key, $option_value ); ?>
						<?php 
						echo isset( $args['disable'] )
							&& gettype( $args['disable'] ) === 'array'
							&& in_array( $option_key, $args['disable'], true )
						? disabled()
						: '';
						?>
					>
					<?php echo esc_html( $option_name ); ?>
					</option>
				<?php endforeach; ?>
				</select>
				<?php if ( $field_description ) : ?>
					<p class="description">
						<?php echo wp_kses_post( $field_description ); ?>
					</p>
				<?php endif; ?>
				<?php
				break;

			case 'textarea':
				?>
				<textarea
					name="<?php echo esc_attr( $name ); ?>"
					id="<?php echo esc_attr( $id ); ?>"
					<?php if ( $placeholder ) : ?>
						placeholder="<?php echo esc_attr( $placeholder ); ?>"
					<?php endif; ?>
					<?php echo esc_attr( $attr_disabled ); ?>
					class="large-text code"
					rows="8"
				><?php echo array_key_exists( 'double_encode', $args ) && $args['double_encode'] ? htmlspecialchars( $option_value ) : esc_html( $option_value ); ?></textarea>
				<?php if ( $field_description ) : ?>
					<p class="description">
						<?php echo wp_kses_post( $field_description ); ?>
					</p>
				<?php endif; ?>
				<?php
				break;

			case 'text':
			case 'password':
			default:
				?>
				<input
					type="<?php echo esc_attr( $type ); ?>"
					name="<?php echo esc_attr( $name ); ?>"
					id="<?php echo esc_attr( $id ); ?>"
					<?php if ( $placeholder ) : ?>
						placeholder="<?php echo esc_attr( $placeholder ); ?>"
					<?php endif; ?>
					<?php echo esc_attr( $attr_disabled ); ?>
					value="<?php echo esc_attr( $option_value ); ?>"
					class="regular-text"
				/>
				<?php if ( $field_description ) : ?>
					<p class="description">
						<?php echo wp_kses_post( $field_description ); ?>
					</p>
				<?php endif; ?>
				<?php
				break;
		}
	}

	/**
	 * Intercepts the form data for an individual option on its way to the server.
	 * Receives one argument containing the payload data and passes it along to
	 * the child's own sanitation method. Returns an encoded payload for it
	 * to continue its way to the server.
	 *
	 * @param array $payload map of fields and their values.
	 * @return string encoded json with fields.
	 * @since 0.5
	 */
	public function universal_sanitize_and_encode_handler( $payload ) {
		// Handle empty payload.
		if ( ! is_array( $payload ) ) {
			$payload = array();
		}

		// Remove any fields which could have been injected into the payload client-side.
		$allowed_payload = array_intersect_key( $payload, static::$fields );
		$allowed_payload = $payload;

		// Pass the value along to the Child class's method to perform sanitation on its fields.
		$sanitized_payload = static::sanitize_option_fields( $allowed_payload );

		// Encode the payload into JSON before it's sent off to be saved.
		return wp_json_encode( $sanitized_payload );
	}

	/**
	 * "Pass through" function. This should be overridden in child classes which
	 * are responsible for sanitizing its own $field_values.
	 *
	 * @param array $field_values array of values for fields.
	 * @since 0.5
	 */
	public function sanitize_option_fields( $field_values ) {
		return $field_values;
	}

	/**
	 * Updates options from decoded map
	 *
	 * @param string $option_key to be returned.
	 * @return array from a json decoded content.
	 * @since 0.4
	 */
	public static function update_option( $option = array(), $option_key = null ) {
		if ( ! isset( $option_key ) ) {
			// Late Static Binding to use the const OPTION_KEY from the child class which called this function.
			$option_key = static::OPTION_KEY;
		}

		wp_cache_delete( 'alloptions', 'options' );
		update_option( $option_key, $option );
	}
	/**
	 * Updates options from decoded map
	 *
	 * @param string $option_key to be returned.
	 * @return array from a json decoded content.
	 * @since 0.4
	 */
	public static function delete_option( $option_key = null ) {
		if ( ! isset( $option_key ) ) {
			// Late Static Binding to use the const OPTION_KEY from the child class which called this function.
			$option_key = static::OPTION_KEY;
		}

		wp_cache_delete( 'alloptions', 'options' );
		delete_option( $option_key );
	}
}
