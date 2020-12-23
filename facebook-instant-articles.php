<?php
/**
 * Plugin Name: Instant Articles for WP
 * Description: Add support for Instant Articles for Facebook to your WordPress site.
 * Author: Automattic, Dekode, Facebook
 * Author URI: https://vip.wordpress.com/plugins/instant-articles/
 * Version: 4.2.1
 * Text Domain: instant-articles
 * License: GPLv2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package default
 */

/**
* Prints error about incompatible version. Extracted as function issue: #390
*
* @since 3.3.4
*/
function show_version_incompatible_warning() {
	echo '<div class="error"><p>' .
		esc_html__( 'Instant Articles for WP requires PHP 5.4 to function properly. Please upgrade PHP or deactivate Instant Articles for WP.', 'instant-articles' ) . '</p></div>';
}
if ( version_compare( PHP_VERSION, '5.4', '<' ) ) {
	add_action(
		'admin_notices',
		'show_version_incompatible_warning'
	);
	return;
} else {

	$autoloader = require __DIR__ . '/vendor/autoload.php';
	$autoloader->add( 'Facebook\\', __DIR__ . '/vendor/facebook/facebook-instant-articles-sdk-php/src' );
	$autoloader->add( 'Facebook\\', __DIR__ . '/vendor/facebook/facebook-instant-articles-sdk-extensions-in-php/src' );

	defined( 'ABSPATH' ) || die( 'Shame on you' );

	define( 'IA_PLUGIN_VERSION', '4.2.1' );
	define( 'IA_PLUGIN_PATH_FULL', __FILE__ );
	define( 'IA_PLUGIN_PATH', plugin_basename( __FILE__ ) );
	define( 'IA_PLUGIN_FILE_BASENAME', pathinfo( __FILE__, PATHINFO_FILENAME ) );
	define( 'IA_PLUGIN_TEXT_DOMAIN', 'instant-articles' );
	define( 'IA_PLUGIN_FORCE_SUBMIT_KEY', 'instant_articles_force_submit' );

	// Let users define their own feed slug.
	if ( ! defined( 'INSTANT_ARTICLES_SLUG' ) ) {
		define( 'INSTANT_ARTICLES_SLUG', 'instant-articles' );
	}

	require_once( dirname( __FILE__ ) . '/embeds.php' );
	require_once( dirname( __FILE__ ) . '/class-instant-articles-post.php' );
	require_once( dirname( __FILE__ ) . '/wizard/class-instant-articles-wizard.php' );
	require_once( dirname( __FILE__ ) . '/meta-box/class-instant-articles-meta-box.php' );
	require_once( dirname( __FILE__ ) . '/class-instant-articles-amp-markup.php' );
	require_once( dirname( __FILE__ ) . '/class-instant-articles-signer.php' );

	/**
	 * Plugin activation hook to add our rewrite rules.
	 *
	 * @since 0.1
	 */
	function instant_articles_activate() {
		instant_articles_init();
		flush_rewrite_rules();
	}
	register_activation_hook( __FILE__, 'instant_articles_activate' );

	/**
	 * Show a message to set up the plugin when it is activated
	 */
	add_action( 'admin_notices', 'instant_articles_setup_admin_notice' );
	add_action( 'network_admin_notices', 'instant_articles_setup_admin_notice' ); // also show message on multisite
	function instant_articles_setup_admin_notice() {
		global $pagenow;
		if ( $pagenow === 'plugins.php' && ! Instant_Articles_Option_FB_Page::get_option_decoded()[ "page_id" ] ) {
			$settings_url = Instant_Articles_Wizard::get_url();
			echo '<div class="updated settings-error notice is-dismissible">';
			echo '<p>Congrats, you\'ve installed the Instant Articles for WP plugin. Now <a href="' . esc_url_raw($settings_url) . '">set it up</a> to start publishing Instant Articles.';
			echo '</div>';
		}
	}

	/**
	 * Plugin activation hook to remove our rewrite rules.
	 *
	 * @since 0.1
	 */
	function instant_articles_deactivate() {
		flush_rewrite_rules();
	}
	register_deactivation_hook( __FILE__, 'instant_articles_deactivate' );

	/**
	 * Load plugin textdomain.
	 *
	 * @since 0.1
	 */
	function instant_articles_load_textdomain() {
		load_plugin_textdomain( 'instant-articles', false, plugin_dir_path( __FILE__ ) . '/languages' );
	}
	add_action( 'plugins_loaded', 'instant_articles_load_textdomain' );

	/**
	 * Plugin hook to load compat layers.
	 *
	 * @since 2.0
	 */
	function instant_articles_load_compat() {
		require_once( dirname( __FILE__ ) . '/compat.php' );
	}
	add_action( 'plugins_loaded', 'instant_articles_load_compat' );

	/**
	 * Register our special feed.
	 *
	 * @since 0.1
	 */
	function instant_articles_init() {
		add_feed( INSTANT_ARTICLES_SLUG, 'instant_articles_feed' );
	}
	add_action( 'init', 'instant_articles_init' );


	/**
	 * Feed display callback.
	 *
	 * @since 0.1
	 */
	function instant_articles_feed() {

		// Load the feed template.
		include( dirname( __FILE__ ) . '/feed-template.php' );

	}

	/**
	 * Whether currently processing an instant article.
	 *
	 * @param bool Set the status
	 * @return bool
	 */
	function is_transforming_instant_article( $set_status = null ) {
		static $is_instant_article = false;

		if ( isset( $set_status ) ) {
			$is_instant_article = (bool) $set_status;
		}

		return $is_instant_article;
	}

	/**
	 * Modify the main query for our feed.
	 *
	 * We want the posts in the modified order, to provide Facebook with content updates even for older posts.
	 * Facebook will only import 100 posts at the time.
	 * Facebook will only update posts modified within the last 24 hours
	 *
	 * @param WP_Query $query  The WP_Query object. Passed by reference.
	 */
	function instant_articles_query( $query ) {

		if ( $query->is_main_query() && $query->is_feed( INSTANT_ARTICLES_SLUG ) ) {

			$query->set( 'orderby', 'modified' );
			$query->set( 'posts_per_page', 10 );
			$query->set( 'posts_per_rss', 10 );

			/**
			 * Filter the post types to include in the query.
			 *
			 * Default to `post` only, but allow other post types to be included by the theme/plugins.
			 *
			 * @since 2.12
			 *
			 * @param array $post_types Array of post types
			 */
			$post_types = apply_filters( 'instant_articles_post_types', array( 'post' ) );
			$query->set( 'post_type', $post_types );

			/**
			 * If the constant INSTANT_ARTICLES_LIMIT_POSTS is set to true, we will limit the feed
			 * to only include posts which are modified within the last 24 hours.
			 * Facebook will initially need 100 posts to pass the review, but will only update
			 * already imported articles if they are modified within the last 24 hours.
			 */
			if ( defined( 'INSTANT_ARTICLES_LIMIT_POSTS' ) && INSTANT_ARTICLES_LIMIT_POSTS ) {
				$query->set( 'date_query', array(
					array(
						'column' => 'post_modified',
						'after'  => '1 day ago',
					),
				) );
			}
		}
	}
	add_action( 'pre_get_posts', 'instant_articles_query', 10, 1 );


	/**
	 * Filter the SQL query to not include posts with empty content -- FB will complain.
	 *
	 * @since 0.1
	 * @param string   $where  The original where part of the SQL statement.
	 * @param WP_Query $query  The WP_Query instance.
	 * @return string  The modified where part of the SQL statement.
	 */
	function instant_articles_query_where( $where, $query ) {

		// Don’t modify the SQL query with a potentially expensive WHERE clause if we’re OK with fewer posts than 100 and are OK with filtering in the loop.
		if ( defined( 'INSTANT_ARTICLES_LIMIT_POSTS' ) && INSTANT_ARTICLES_LIMIT_POSTS ) {
			return $where;
		}

		if ( $query->is_main_query() && $query->is_feed( INSTANT_ARTICLES_SLUG ) ) {
			global $wpdb;
			$where .= " AND {$wpdb->posts}.post_content NOT LIKE ''";
		}
		return $where;

	}
	add_filter( 'posts_where' , 'instant_articles_query_where', 10, 2 );

	/**
	 * Register all scripts and styles for the admin UI.
	 *
	 * @since 0.4
	 */
	function instant_articles_register_scripts() {
		wp_register_style(
			'instant-articles-meta-box',
			plugins_url( '/css/instant-articles-meta-box.css', __FILE__ ),
			null,
			IA_PLUGIN_VERSION,
			false
		);
		wp_register_style(
			'instant-articles-settings',
			plugins_url( '/css/instant-articles-settings.css', __FILE__ ),
			null,
			IA_PLUGIN_VERSION,
			false
		);
		wp_register_style(
			'instant-articles-wizard',
			plugins_url( '/css/instant-articles-wizard.css', __FILE__ ),
			null,
			IA_PLUGIN_VERSION,
			false
		);
		wp_register_style(
			'instant-articles-index-column',
			plugins_url( '/css/instant-articles-index-column.css', __FILE__ ),
			null,
			IA_PLUGIN_VERSION,
			false
		);


		wp_register_script(
			'instant-articles-meta-box',
			plugins_url( '/js/instant-articles-meta-box.js', __FILE__ ),
			null,
			IA_PLUGIN_VERSION,
			false
		);
		wp_register_script(
			'instant-articles-option-ads',
			plugins_url( '/js/instant-articles-option-ads.js', __FILE__ ),
			null,
			IA_PLUGIN_VERSION,
			false
		);
		wp_register_script(
			'instant-articles-option-analytics',
			plugins_url( '/js/instant-articles-option-analytics.js', __FILE__ ),
			null,
			IA_PLUGIN_VERSION,
			false
		);
		wp_register_script(
			'instant-articles-option-publishing',
			plugins_url( '/js/instant-articles-option-publishing.js', __FILE__ ),
			null,
			IA_PLUGIN_VERSION,
			false
		);
		wp_register_script(
			'instant-articles-settings',
			plugins_url( '/js/instant-articles-settings.js', __FILE__ ),
			null,
			IA_PLUGIN_VERSION,
			false
		);
	}
	add_action( 'init', 'instant_articles_register_scripts' );

	/**
	 * Enqueue all scripts and styles for the admin UI.
	 *
	 * @since 0.4
	 */
	function instant_articles_enqueue_scripts() {
		wp_enqueue_style( 'instant-articles-meta-box' );
		wp_enqueue_style( 'instant-articles-settings-wizard' );
		wp_enqueue_style( 'instant-articles-settings' );
		wp_enqueue_style( 'instant-articles-wizard' );
		wp_enqueue_style( 'instant-articles-index-column' );

		wp_enqueue_script( 'instant-articles-meta-box' );
		wp_enqueue_script( 'instant-articles-option-ads' );
		wp_enqueue_script( 'instant-articles-option-analytics' );
		wp_enqueue_script( 'instant-articles-option-publishing' );
		wp_enqueue_script( 'instant-articles-settings' );
		wp_enqueue_script( 'instant-articles-wizard' );
	}
	add_action( 'admin_enqueue_scripts', 'instant_articles_enqueue_scripts' );

	/**
	 * Automatically add meta tag for Instant Articles URL claiming
	 * when page is set.
	 *
	 * @since 0.4
	 */
	function inject_url_claiming_meta_tag() {
		$publishing_settings = Instant_Articles_Option_Publishing::get_option_decoded();
		$fb_page_settings = Instant_Articles_Option_FB_Page::get_option_decoded();

		if ( isset( $fb_page_settings[ 'page_id' ] ) ) {
			?>
			<meta property="fb:pages" content="<?php echo esc_attr( $fb_page_settings[ 'page_id' ] ); ?>" />
			<?php
		}
	}
	add_action( 'wp_head', 'inject_url_claiming_meta_tag' );

	/**
	 * Automatically add meta tag for Instant Articles Open Publish scraper
	 *
	 * @since 4.0
	 */
	function inject_ia_markup_meta_tag() {
		$post = get_post();

		// If there's no current post, return
		if ( ! $post ) {
			return;
		}

		// Transform the post to an Instant Article.
		$adapter = new Instant_Articles_Post( $post );
		if ( $adapter->should_submit_post() ) {
			$canonical_url = $adapter->get_canonical_url();
			$url_parts     = wp_parse_url( $canonical_url );

			if ( $url_parts ) {
				$url = trailingslashit( $url_parts['scheme'] . $url_parts['host'] . $url_parts['path'] ) . 'ia/';
				if ( $url_parts['query'] ) {
					$url = $url . '?'. $url_parts['query'];
				}
			} else {
				$url = $canonical_url;
			}

			$fb_page_settings = Instant_Articles_Option_FB_Page::get_option_decoded();
			$publishing_settings = Instant_Articles_Option_Publishing::get_option_decoded();
			$dev_mode = isset( $publishing_settings['dev_mode'] )
				? ( $publishing_settings['dev_mode'] ? true : false )
				: false;

			if ( $dev_mode ) {
				?>
				<meta property="ia:markup_url_dev" content="<?php echo esc_attr( $url ); ?>" />
				<?php
			} else {
				?>
				<meta property="ia:markup_url" content="<?php echo esc_attr( $url ); ?>" />
				<?php
			}
		}
	}
	add_action( 'wp_head', 'inject_ia_markup_meta_tag' );

	// Injects the <link rel...> tag if the AMP Markup is enabled
	add_action( 'wp_head', array('Instant_Articles_AMP_Markup', 'inject_link_rel') );

	// Initialize the Instant Articles meta box.
	Instant_Articles_Meta_Box::init();

	// Add hook for generating the AMP markup
	add_action( 'wp', array('Instant_Articles_AMP_Markup', 'markup_version') );

	Instant_Articles_Wizard::init();
	Instant_Articles_Signer::init();

	function invalidate_post_transformation_info_cache( $post_id, $post ) {
		// These post metas are caches on the calculations made to decide if
		// a post is in good state to be converted to an Instant Article or not
		delete_post_meta( $post_id, '_has_warnings_after_transformation' );
		delete_post_meta( $post_id, '_is_empty_after_transformation' );
	}
	add_action( 'save_post', 'invalidate_post_transformation_info_cache', 10, 2 );

	// We also need to invalidate the transformation caches when the option containing
	// the custom transformer rules is updated
	function invalidate_all_posts_transformation_info_cache( $option ) {
		if ( $option === Instant_Articles_Option_Publishing::OPTION_KEY ) {
			// These post metas are caches on the calculations made to decide if
			// a post is in good state to be converted to an Instant Article or not
			delete_post_meta_by_key( '_has_warnings_after_transformation' );
			delete_post_meta_by_key( '_is_empty_after_transformation' );
		}
	}
	add_action( 'updated_option', 'invalidate_all_posts_transformation_info_cache', 10, 1 );

	function fbia_indicator_column_heading( $columns ) {
		$publishing_settings = Instant_Articles_Option_Publishing::get_option_decoded();
		$display_warning_column = isset( $publishing_settings[ 'display_warning_column' ] ) ? $publishing_settings[ 'display_warning_column' ] : '0';

		if( "1" === $display_warning_column ) {
			$columns[ 'FBIA' ] = "<span title='Facebook Instant Article Distribution Status' class='fbia-col-heading'>FB IA Status</span>";
		}
		return $columns;
	}
	add_filter( 'manage_posts_columns', 'fbia_indicator_column_heading' );

	function fbia_indication_column( $column_name, $post_ID ) {
		$publishing_settings = Instant_Articles_Option_Publishing::get_option_decoded();
		$display_warning_column = isset( $publishing_settings[ 'display_warning_column' ] ) ? $publishing_settings[ 'display_warning_column' ] : '0';

		if( "1" === $display_warning_column ) {
			$red_light = '<span title="Instant article is empty after transformation." class="instant-articles-col-status error"></span>';

			$yellow_light = '<span title="Instant article has warnings after transformation." class="instant-articles-col-status warning"></span>';

			$green_light = '<span title="Instant article transformed successfully." class="instant-articles-col-status ok"></span>';

			if ( $column_name === "FBIA" ) {
				$post = get_post( $post_ID );
				$instant_articles_post = new \Instant_Articles_Post( $post );

				$is_empty = $instant_articles_post->is_empty_after_transformation();
				if ( true === $is_empty ) {
					echo wp_kses_post( $red_light );
					return;
				}

				$has_warnings = $instant_articles_post->has_warnings_after_transformation();
				if ( true === $has_warnings ) {
					echo wp_kses_post( $yellow_light );
					return;
				}

				echo wp_kses_post( $green_light );

				return;
			}
		}
	}
	add_action( 'manage_posts_custom_column', 'fbia_indication_column', 10, 2 );

	function invalidate_scrape_on_update( $post_ID, $post_after, $post_before ) {
		$adapter = new Instant_Articles_Post( $post_after );
		if ( $adapter->should_submit_post() ) {
			$fb_app_settings = Instant_Articles_Option_FB_App::get_option_decoded();
			if (
				( isset( $fb_app_settings[ 'page_access_token' ] ) && $fb_app_settings[ 'page_access_token' ] ) &&
				( isset( $fb_app_settings[ 'app_id' ] ) && $fb_app_settings[ 'app_id' ] ) &&
				( isset( $fb_app_settings[ 'app_secret' ] ) && $fb_app_settings[ 'app_secret' ] )
			) {
				// Fetches the right URL to invalidate
				$url = $adapter->get_canonical_url();

				// oAuth info
				$app_id = $fb_app_settings[ 'app_id' ];
				$app_secret = $fb_app_settings[ 'app_secret' ];
				$access_token = $fb_app_settings[ 'page_access_token' ];

				// Build Graph SDK instance
				$fb = new \Facebook\Facebook([
					'app_id' => $app_id,
					'app_secret' => $app_secret,
					'default_access_token' => $access_token
				]);

				function admin_notice__scrape_invalidation_failed() {
					?>
					<div class="notice notice-error is-dismissible">
						<p>
							<?php _e( 'It was not possible to automatically invalidate the scrape for this article.', IA_PLUGIN_TEXT_DOMAIN ) ?>
							<?php _e( 'Please trigger a new scrape manually using the Facebook Share Debugger.', IA_PLUGIN_TEXT_DOMAIN ) ?>
						</p>
					</div>
					<?php
				}

				function admin_notice__scrape_invalidation_success() {
					?>
					<div class="notice notice-success is-dismissible">
						<p>
							<?php _e( 'Successfully refreshed the Instant Articles cache for this article.', IA_PLUGIN_TEXT_DOMAIN ) ?>
						</p>
					</div>
					<?php
				}


				// Make call
				$graph_api_call = '/';
				$graph_api_call = add_query_arg( 'id', rawurlencode($url), $graph_api_call);
				$graph_api_call = add_query_arg( 'scrape', 'true', $graph_api_call);

				try {
					$fb->post( $graph_api_call, [], $access_token );
					add_action( 'admin_notices', 'admin_notice__scrape_invalidation_success' );

				} catch(Facebook\Exceptions\FacebookResponseException $e) {
					echo '<pre>';
					print_r($e->getTraceAsString());

					add_action( 'admin_notices', 'admin_notice__scrape_invalidation_failed' );
				} catch(Facebook\Exceptions\FacebookSDKException $e) {

					add_action( 'admin_notices', 'admin_notice__scrape_invalidation_failed' );
				}
			}
		}
	}
	add_action( 'post_updated', 'invalidate_scrape_on_update', 10, 3 );

	function rescrape_article( $post_id, $post ) {
		$adapter = new Instant_Articles_Post( $post );
		$old_slugs = get_post_meta( $post_id, '_wp_old_slug' );
		if ( $adapter->should_submit_post() ) {
			$fb_app_settings = Instant_Articles_Option_FB_App::get_option_decoded();
			if (
				( isset( $fb_app_settings[ 'page_access_token' ] ) && $fb_app_settings[ 'page_access_token' ] ) &&
				( isset( $fb_app_settings[ 'app_id' ] ) && $fb_app_settings[ 'app_id' ] ) &&
				( isset( $fb_app_settings[ 'app_secret' ] ) && $fb_app_settings[ 'app_secret' ] )
			) {
				// Defer to access_token if configured to ensure backwards compatibility
				return;
			}

			try {
				if ( extension_loaded('openssl') ) {
					$client = Facebook\HttpClients\HttpClientsFactory::createHttpClient( null );
					$url_encoded = urlencode($adapter->get_canonical_url());
					$client->send(
						Instant_Articles_Signer::sign_request_path(
							"https://graph.facebook.com/?id=$url_encoded&scrape=true"
						),
						'POST',
						'',
						array(),
						60
					);
					foreach ( $old_slugs as $slug ) {
						$clone_post = clone $post;
						$clone_post->post_name = $slug;
						$clone_adapter = new Instant_Articles_Post( $clone_post );

						$url_encoded = urlencode($clone_adapter->get_canonical_url());
						$client->send(
							Instant_Articles_Signer::sign_request_path(
								"https://graph.facebook.com/?id=$url_encoded&scrape=true"
							),
							'POST',
							'',
							array(),
							60
						);
					}
				}
			} catch ( Exception $e ) {
				error_log( 'Unable to submit article.'.$e->getTraceAsString()	);
			}
		}
	}
	add_action( 'save_post', 'rescrape_article', 999, 2 );


	/**
	 * Add /ia/ as the URL for instant articles markup versions of articles.
	 */
	function instant_articles_rewrites() {
		add_rewrite_endpoint( 'ia', EP_PERMALINK );
	}
	add_action( 'after_setup_theme', 'instant_articles_rewrites' );

	/**
	 * Make sure the `ia` query var has an explicit value.
	 *
	 * This avoids issues when filtering the deprecated `query_string` hook.
	 *
	 * @since 0.0.0
	 *
	 * @param array $query_vars Query vars.
	 * @return array Query vars.
	 */
	function ia_force_query_var_value( $query_vars ) {
		if ( isset( $query_vars[ 'ia' ] ) && '' === $query_vars[ 'ia' ] ) {
			$query_vars[ 'ia' ] = 1;
		}
		return $query_vars;
	}
	add_filter( 'request', 'ia_force_query_var_value' );

	/**
	 * Print out the IA version of an article.
	 */
	function instant_articles_template_redirect() {
		if ( 1 === get_query_var( 'ia' ) ) {

			$adapter = new Instant_Articles_Post( get_post() );
			$post_supports_ia = apply_filters( 'instant_articles_should_submit_post', true, $adapter );

			if ( $post_supports_ia ) {
				// Transform the post to an Instant Article.
				$post = get_post();
				$adapter = new Instant_Articles_Post( $post );
				$article = $adapter->to_instant_article();
				$result = $article->render( null, true );

				echo apply_filters( 'instant_articles_rendered_content', $result );

				die();
			} else {
				wp_safe_redirect( get_permalink() );
				die();
			}
		}
	}
	add_action( 'template_redirect', 'instant_articles_template_redirect' );

}
