<?php
/**
 * Facebook Instant Articles for WP.
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package default
 */

use Facebook\InstantArticles\Client\Client;
use Facebook\InstantArticles\Client\InstantArticleStatus;
use Facebook\InstantArticles\Client\ServerMessage;

/**
 * Class responsible for drawing the meta box on the post edit page
 *
 * @since 0.1
 */
class Instant_Articles_Meta_Box {

	/**
	 * Initiator for Metabox.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( 'Instant_Articles_Meta_Box', 'register_meta_box' ) );
		add_action(
			'wp_ajax_instant_articles_meta_box',
			array( 'Instant_Articles_Meta_Box', 'render_meta_box' )
		);
		add_action(
			'wp_ajax_instant_articles_force_submit',
			array( 'Instant_Articles_Meta_Box', 'force_submit' )
		);
	}

	/**
	 * Register Meta Box renderer.
	 */
	public static function register_meta_box() {
		add_meta_box(
			'instant_article_meta_box',
			'Facebook Instant Articles',
			array( 'Instant_Articles_Meta_Box', 'render_meta_box_loader' ),
			/** This filter is defined in facebook-instant-articles.php. */
			apply_filters( 'instant_articles_post_types', array( 'post' ) ),
			'normal',
			'default'
		);
	}

	/**
	 * Includes the template for Metabox.
	 *
	 * @param Post $post the post request content.
	 */
	public static function render_meta_box_loader( $post ) {
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return;
		}

		include dirname( __FILE__ ) . '/meta-box-loader-template.php';
	}

	/**
	 * Renderer for the Metabox.
	 */
	public static function force_submit() {
		$post_id = isset( $_POST['post_ID'] ) ? intval( $_POST['post_ID'] ) : 0; // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( -1, 403 );
		}

		check_ajax_referer( 'instant-articles-force-submit-' . $post_id, 'security' );
		$force = isset( $_POST['force'] ) ? sanitize_text_field( $_POST['force'] ) === 'true' : false; // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected
		update_post_meta( $post_id, IA_PLUGIN_FORCE_SUBMIT_KEY, $force );
	}

	/**
	 * Renderer for the Metabox.
	 */
	public static function render_meta_box() {
		$post_id = filter_input( INPUT_POST, 'post_ID', FILTER_SANITIZE_NUMBER_INT );

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( -1, 403 );
		}

		$ajax_nonce = wp_create_nonce( 'instant-articles-force-submit-' . $post_id ); // phpcs:ignore WordPressVIPMinimum.Variables.VariableAnalysis.UnusedVariable
		$post       = get_post( $post_id );
		$adapter    = new Instant_Articles_Post( $post );

		try {
			$article                                    = $adapter->to_instant_article(); // phpcs:ignore WordPressVIPMinimum.Variables.VariableAnalysis.UnusedVariable
			$published                                  = ( 'publish' === $post->post_status ); // phpcs:ignore WordPressVIPMinimum.Variables.VariableAnalysis.UnusedVariable
			$force_submit                               = get_post_meta( $post_id, IA_PLUGIN_FORCE_SUBMIT_KEY, true ); // phpcs:ignore WordPressVIPMinimum.Variables.VariableAnalysis.UnusedVariable
			$instant_articles_should_submit_post_filter = apply_filters( 'instant_articles_should_submit_post', true, $adapter ); // phpcs:ignore WordPressVIPMinimum.Variables.VariableAnalysis.UnusedVariable

			Instant_Articles_Wizard::menu_items();
			$settings_page_href = Instant_Articles_Wizard::get_url(); // phpcs:ignore WordPressVIPMinimum.Variables.VariableAnalysis.UnusedVariable

			$publishing_settings   = Instant_Articles_Option_Publishing::get_option_decoded();
			$publish_with_warnings = isset( $publishing_settings['publish_with_warnings'] ) ? $publishing_settings['publish_with_warnings'] : false; // phpcs:ignore WordPressVIPMinimum.Variables.VariableAnalysis.UnusedVariable
			$fb_page_settings      = Instant_Articles_Option_FB_Page::get_option_decoded(); // phpcs:ignore WordPressVIPMinimum.Variables.VariableAnalysis.UnusedVariable
			$publishing_settings   = Instant_Articles_Option_Publishing::get_option_decoded();

			$dev_mode = isset( $publishing_settings['dev_mode'] ) // phpcs:ignore WordPressVIPMinimum.Variables.VariableAnalysis.UnusedVariable
				? ( $publishing_settings['dev_mode'] ? true : false )
				: false;

			include dirname( __FILE__ ) . '/meta-box-template.php';
		} catch ( Exception $e ) { // phpcs:ignore WordPressVIPMinimum.Variables.VariableAnalysis.UnusedVariable
			include dirname( __FILE__ ) . '/meta-box-error.php';
		}

		die();
	}
}
