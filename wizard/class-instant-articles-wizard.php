<?php
/**
 * Facebook Instant Articles for WP.
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package default
 */
require_once( dirname( __FILE__ ) . '/class-instant-articles-option-ads.php' );
require_once( dirname( __FILE__ ) . '/class-instant-articles-option-analytics.php' );
require_once( dirname( __FILE__ ) . '/class-instant-articles-option-fb-page.php' );
require_once( dirname( __FILE__ ) . '/class-instant-articles-option-fb-app.php' );
require_once( dirname( __FILE__ ) . '/class-instant-articles-option-publishing.php' );
require_once( dirname( __FILE__ ) . '/class-instant-articles-option-styles.php' );

use Facebook\InstantArticles\Client\Client;
use Facebook\InstantArticles\Client\ClientException;

/**
* Controller for Set-up Wizard
*
* @since 3.1
*/
class Instant_Articles_Wizard {

	public static function init() {

		add_action( 'admin_menu', array( 'Instant_Articles_Wizard', 'menu_items' ) );

		add_filter( 'plugin_action_links_' . IA_PLUGIN_PATH, array( 'Instant_Articles_Wizard', 'add_settings_link_to_plugin_actions' ) );

		add_action( 'admin_init', function () {
			new Instant_Articles_Option_FB_Page();
			new Instant_Articles_Option_FB_App();
			new Instant_Articles_Option_Styles();
			new Instant_Articles_Option_Ads();
			new Instant_Articles_Option_Analytics();
			new Instant_Articles_Option_Publishing();
		});

	}

	public static function add_settings_link_to_plugin_actions( $links ) {
		$link_text = __( 'Settings' );
		$settings_href = self::get_url();
		$settings_link = '<a href="' . esc_url( $settings_href ) . '">' . esc_html( $link_text ) . '</a>';
		array_push( $links, $settings_link );
		return $links;
	}

	public static function menu_items() {
		add_menu_page(
			'Instant Articles Setup Wizard',
			'Instant Articles',
			'manage_options',
			'instant-articles-wizard',
			array( 'Instant_Articles_Wizard', 'render' )
			,'dashicons-facebook'
		);
		// Hack to let the URL visible to ajax handlers
		update_option( 'instant-articles-wizard-url', menu_page_url( 'instant-articles-wizard', false) );
	}

	public static function get_url() {
		$url = menu_page_url( 'instant-articles-wizard', false );

		// Needed when calling from ajax
		if ( ! $url ) {
			$url = get_option( 'instant-articles-wizard-url' );
		}

		return $url;
	}


	public static function get_admin_url() {
		$url = parse_url( admin_url() );
		return $url['host'];
	}

	public static function render( $ajax = false ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html( 'You do not have sufficient permissions to access this page.' ) );
		}

		try {
			// Read options (they are used on the templates)
			$fb_page_settings = Instant_Articles_Option_FB_Page::get_option_decoded();
			$settings_url = self::get_url();

			include( dirname( __FILE__ ) . '/templates/advanced-template.php' );
		} catch (Exception $e) {
			if ( Instant_Articles_Wizard_State::get_current_state() !== Instant_Articles_Wizard_State::STATE_REVIEW_SUBMISSION ) {
				// Restarts the wizard
				Instant_Articles_Wizard_State::do_transition( Instant_Articles_Wizard_State::STATE_APP_SETUP );
				echo '<div class="error settings-error notice is-dismissible"><p><strong>'.
					esc_html(
						'Error processing your request. Check server log for more details. Setup and login again to renew Application credentials. Error message: ' .
						$e->getMessage()
					) . '</strong></p></div>';
				Instant_Articles_Wizard::render( $ajax, true );
			}
		}
	}

 }
