<?php
/**
 * Plugin Name: Instant Articles for WP
 * Description: Add support for Instant Articles for Facebook to your WordPress site.
 * Author: Automattic, Dekode, Facebook
 * Author URI: https://vip.wordpress.com/plugins/instant-articles/
 * Version: 2.11
 * Text Domain: instant-articles
 * License: GPLv2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package default
 */

if ( version_compare( PHP_VERSION, '5.4', '<' ) ) {
	add_action(
		'admin_notices',
		create_function(
			'',
			"echo '<div class=\"error\"><p>".
				__(
					'Instant Articles for WP requires PHP 5.4 to function properly. '.
						'Please upgrade PHP or deactivate Instant Articles for WP.',
					'instant-articles'
				).
				"</p></div>';"
		)
	);
	return;
} else {

	$autoloader = require __DIR__ . '/vendor/autoload.php';
	$autoloader->add( 'Facebook\\', __DIR__ . '/vendor/facebook/facebook-instant-articles-sdk-php/src' );

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

	define( 'IA_PLUGIN_VERSION', '2.11' );
	define( 'IA_PLUGIN_PATH_FULL', __FILE__ );
	define( 'IA_PLUGIN_PATH', plugin_basename( __FILE__ ) );
	define( 'IA_PLUGIN_FILE_BASENAME', pathinfo( __FILE__, PATHINFO_FILENAME ) );
	define( 'IA_PLUGIN_TEXT_DOMAIN', 'instant-articles' );

	// Let users define their own feed slug.
	if ( ! defined( 'INSTANT_ARTICLES_SLUG' ) ) {
		define( 'INSTANT_ARTICLES_SLUG', 'instant-articles' );
	}

	require_once( dirname( __FILE__ ) . '/embeds.php' );
	require_once( dirname( __FILE__ ) . '/class-instant-articles-post.php' );
	require_once( dirname( __FILE__ ) . '/settings/class-instant-articles-settings.php' );
	require_once( dirname( __FILE__ ) . '/meta-box/class-instant-articles-meta-box.php' );
	require_once( dirname( __FILE__ ) . '/class-instant-articles-publisher.php' );

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

			$settings_publishing = Instant_Articles_Option_Publishing::get_option_decoded();

			if( $settings_publishing['categories'] !== '' ) {
				$query->set( 'cat', $settings_publishing['categories'] );
			}

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
			plugins_url( '/css/instant-articles-meta-box.css', __FILE__ )
		);
		wp_register_style(
			'instant-articles-settings-wizard',
			plugins_url( '/css/instant-articles-settings-wizard.css', __FILE__ )
		);
		wp_register_style(
			'instant-articles-settings',
			plugins_url( '/css/instant-articles-settings.css', __FILE__ )
		);

		wp_register_script(
			'instant-articles-meta-box',
			plugins_url( '/js/instant-articles-meta-box.js', __FILE__ )
		);
		wp_register_script(
			'instant-articles-option-ads',
			plugins_url( '/js/instant-articles-option-ads.js', __FILE__ ),
			null,
			null,
			true
		);
		wp_register_script(
			'instant-articles-option-analytics',
			plugins_url( '/js/instant-articles-option-analytics.js', __FILE__ ),
			null,
			null,
			true
		);
		wp_register_script(
			'instant-articles-option-publishing',
			plugins_url( '/js/instant-articles-option-publishing.js', __FILE__ ),
			null,
			null,
			true
		);
		wp_register_script(
			'instant-articles-settings',
			plugins_url( '/js/instant-articles-settings.js', __FILE__ ),
			null,
			null,
			true
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

		wp_enqueue_script( 'instant-articles-meta-box' );
		wp_enqueue_script( 'instant-articles-option-ads' );
		wp_enqueue_script( 'instant-articles-option-analytics' );
		wp_enqueue_script( 'instant-articles-option-publishing' );
		wp_enqueue_script( 'instant-articles-settings' );
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

		if ( isset( $fb_page_settings['page_id'] ) ) {
			?>
			<meta property="fb:pages" content="<?php echo esc_attr( $fb_page_settings['page_id'] ); ?>" />
			<?php
		}
	}
	add_action( 'wp_head', 'inject_url_claiming_meta_tag' );

	// Initialize the Instant Articles settings page.
	Instant_Articles_Settings::init();

	// Initialize the Instant Articles meta box.
	Instant_Articles_Meta_Box::init();

	// Initialize the Instant Articles publisher.
	Instant_Articles_Publisher::init();
}
