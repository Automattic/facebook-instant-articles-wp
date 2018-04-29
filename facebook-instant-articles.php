<?php
/**
 * Plugin Name: Instant Articles for WP
 * Description: Add support for Instant Articles for Facebook to your WordPress site.
 * Author: Automattic, Dekode, Facebook
 * Author URI: https://vip.wordpress.com/plugins/instant-articles/
 * Version: 4.0.6
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

	// Configures log to go through console.
	\Logger::configure(
		array(
			'rootLogger' => array(
				'appenders' => array( 'facebook-instantarticles-transformer' ),
			),
			'appenders' => array(
				'facebook-instantarticles-transformer' => array(
					'class' => 'LoggerAppenderConsole',
					'threshold' => 'INFO',
					'layout' => array(
						'class' => 'LoggerLayoutSimple',
					),
				),
				'facebook-instantarticles-client' => array(
					'class' => 'LoggerAppenderConsole',
					'threshold' => 'INFO',
					'layout' => array(
						'class' => 'LoggerLayoutSimple',
					),
				),
				'instantarticles-wp-plugin' => array(
					'class' => 'LoggerAppenderConsole',
					'threshold' => 'INFO',
					'layout' => array(
						'class' => 'LoggerLayoutSimple',
					),
				),
			),
		)
	);


	defined( 'ABSPATH' ) || die( 'Shame on you' );

	define( 'IA_PLUGIN_VERSION', '4.0.6' );
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
			$url = $adapter->get_canonical_url();
			$url = add_query_arg( 'ia_markup', '1', $url );
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

	function ia_markup_version( ) {
		$post = get_post();

		if (isset( $_GET[ 'ia_markup' ] ) && $_GET[ 'ia_markup' ]) {
			// Transform the post to an Instant Article.
			$adapter = new Instant_Articles_Post( $post );
			$article = $adapter->to_instant_article();
			echo $article->render( null, true );

			die();
		}
	}
	add_action( 'wp', 'ia_markup_version' );

	// Add hook for generating the AMP markup
	add_action( 'wp', array('Instant_Articles_AMP_Markup', 'markup_version') );

	Instant_Articles_Wizard::init();

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

}
