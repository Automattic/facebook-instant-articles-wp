<?php
/**
 * Instant Articles publish meta box settings.
 *
 * @package default
 */

/**
 * Publish meta box class.
 */
class Instant_Articles_Publish_Meta_Box {

	/**
	 * Assets handle.
	 *
	 * @var string
	 */
	const ASSETS_HANDLE = 'fbia-publish-meta-box';

	/**
	 * The enabled status post meta value.
	 *
	 * @var string
	 */
	const ENABLED_STATUS = 'enabled';

	/**
	 * The disabled status post meta value.
	 *
	 * @var string
	 */
	const DISABLED_STATUS = 'disabled';

	/**
	 * The status post meta key.
	 *
	 * @var string
	 */
	const STATUS_POST_META_KEY = 'fbia_status';

	/**
	 * The field name for the enabled/disabled radio buttons.
	 *
	 * @var string
	 */
	const STATUS_INPUT_NAME = 'fbia_status';

	/**
	 * The nonce name.
	 *
	 * @var string
	 */
	const NONCE_NAME = 'fbia-status-nonce';

	/**
	 * The nonce action.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'fbia-update-status';

	/**
	 * Initialize.
	 */
	public static function hooks() {
		register_meta( 'post', self::STATUS_POST_META_KEY, array(
			'sanitize_callback' => array( __CLASS__, 'sanitize_status' ),
			'type'              => 'string',
			'description'       => __( 'FBIA status.', 'instant-articles' ),
			'show_in_rest'      => true,
			'single'            => true,
		) );

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_action( 'post_submitbox_misc_actions', array( __CLASS__, 'render_status' ) );
		add_action( 'save_post', array( __CLASS__, 'save_fbia_status' ) );
	}

	/**
	 * Sanitize status.
	 *
	 * @param string $status Status.
	 * @return string Sanitized status.
	 */
	public static function sanitize_status( $status ) {
		$status = strtolower( trim( $status ) );
		if ( ! in_array( $status, array( self::ENABLED_STATUS, self::DISABLED_STATUS ), true ) ) {
			$status = apply_filters( 'instant_articles_default_status', self::ENABLED_STATUS );
		}
		return $status;
	}

	/**
	 * Enqueue admin assets.
	 */
	public static function enqueue_admin_assets() {
		$post     = get_post();
		$screen   = get_current_screen();
		$validate = (
			isset( $screen->base )
			&&
			'post' === $screen->base
			&&
			is_post_type_viewable( $post->post_type )
		);
		if ( ! $validate ) {
			return;
		}

		// Styles.
		wp_enqueue_style(
			self::ASSETS_HANDLE,
			plugins_url( 'css/instant-articles-publish-meta-box.css', __FILE__ ),
			false,
			IA_PLUGIN_VERSION
		);

		// Scripts.
		wp_enqueue_script(
			self::ASSETS_HANDLE,
			plugins_url( 'js/instant-articles-publish-meta-box.js', __FILE__ ),
			array( 'jquery' ),
			IA_PLUGIN_VERSION
		);


		wp_add_inline_script( self::ASSETS_HANDLE, sprintf( 'fbiaPostMetaBox.boot( %s );',
			wp_json_encode( array(
				'statusInputName' => self::STATUS_INPUT_NAME,
				'canSupport'      => true,
			) )
		) );
	}

	/**
	 * Render FBIA status.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_status( $post ) {
		$supported_post_types = apply_filters( 'instant_articles_post_types', array( 'post' ) );

		$verify = (
			isset( $post->ID )
			&&
			is_post_type_viewable( $post->post_type )
			&&
			in_array( $post->post_type, $supported_post_types, true )
			&&
			current_user_can( 'edit_post', $post->ID )
		);

		if ( true !== $verify ) {
			return;
		}

		$status = self::get_status( $post->ID );
		if ( ! $status ) {
			$status = apply_filters( 'instant_articles_default_status', self::ENABLED_STATUS );
		}

		$labels = array(
			'enabled'  => __( 'Enabled', 'fbia' ),
			'disabled' => __( 'Disabled', 'fbia' ),
		);

		// The preceding variables are used inside the following fbia-status.php template.
		include IA_PLUGIN_DIRECTORY . '/fbia-status.php';
	}

	/**
	 * Save FBIA Status.
	 *
	 * @param int $post_id The Post ID.
	 *
	 * @return string Status.
	 */
	public static function get_status( $post_id ) {
		return self::sanitize_status( get_post_meta( $post_id, self::STATUS_POST_META_KEY, true ) );
	}

	/**
	 * Save FBIA Status.
	 *
	 * @param int $post_id The Post ID.
	 */
	public static function save_fbia_status( $post_id ) {
		$verify = (
			isset( $_POST[ self::NONCE_NAME ] )
			&&
			isset( $_POST[ self::STATUS_INPUT_NAME ] )
			&&
			wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION )
			&&
			current_user_can( 'edit_post', $post_id )
			&&
			! wp_is_post_revision( $post_id )
			&&
			! wp_is_post_autosave( $post_id )
		);

		if ( true === $verify ) {
			update_post_meta(
				$post_id,
				self::STATUS_POST_META_KEY,
				$_POST[ self::STATUS_INPUT_NAME ] // Note: The sanitize_callback has been supplied in the register_meta() call above.
			);
		}
	}
}

add_action( 'after_setup_theme', array( 'Instant_Articles_Publish_Meta_Box', 'hooks' ) );
