<?php
/**
 * Plugin Name: Instant Articles for WP
 * Description: Add support for Instant Articles for Facebook to your WordPress site.
 * Author: Automattic, Dekode
 * Author URI: https://vip.wordpress.com/plugins/instant-articles/
 * Version: 0.1
 * Text Domain: facebook-instant-articles-wp
 * License: GPLv2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || die('Shame on you');


// Let users define their own feed slug
if ( ! defined( 'INSTANT_ARTICLES_SLUG' ) ) {
	define( 'INSTANT_ARTICLES_SLUG', 'instant-articles' );
}

require_once( dirname( __FILE__ ) . '/dom-transform-filters/class-instant-articles-dom-transform-filter.php' );
require_once( dirname( __FILE__ ) . '/class-instant-articles-dom-transform-filter-runner.php' );
require_once( dirname( __FILE__ ) . '/class-instant-articles-post.php' );

/**
 * Plugin activation hook to add our rewrite rules
 *
 * @since 0.1
 */
function instant_articles_activate(){
	instant_articles_init();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'instant_articles_activate' );

/**
 * Plugin activation hook to remove our rewrite rules
 *
 * @since 0.1
 */
function instant_articles_deactivate(){
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'instant_articles_deactivate' );

/**
 * Load plugin textdomain
 *
 * @since 0.1
 */
function instant_articles_load_textdomain() {
	load_plugin_textdomain( 'facebook-instant-articles-wp', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' ); 
}
add_action( 'plugins_loaded', 'instant_articles_load_textdomain' );

/**
 * Register our special feed
 *
 * @since 0.1
 */
function instant_articles_init() {
	add_feed( INSTANT_ARTICLES_SLUG, 'instant_articles_feed' );
}
add_action( 'init', 'instant_articles_init' );

/**
 * Feed display callback
 *
 * @since 0.1
 */
function instant_articles_feed() {

	// Load compat layers
	include( dirname( __FILE__ ) . '/compat.php' );

	// Load shortcode handlers
	include( dirname( __FILE__ ) . '/shortcodes.php' );

	// Load embedded content handlers
	include( dirname( __FILE__ ) . '/embeds.php' );

	// Load the feed template
	include( dirname( __FILE__ ) . '/feed-template.php' );

}

/**
 * Register included DOM transformation filters
 *
 * @since 0.1
 */
function instant_articles_register_transformation_filters() {

	include( dirname( __FILE__ ) . '/dom-transform-filters/class-instant-articles-dom-transform-filter-video.php' );
	Instant_Articles_DOM_Transform_Filter_Runner::register( 'Instant_Articles_DOM_Transform_Filter_Video' );

	include( dirname( __FILE__ ) . '/dom-transform-filters/class-instant-articles-dom-transform-filter-image.php' );
	Instant_Articles_DOM_Transform_Filter_Runner::register( 'Instant_Articles_DOM_Transform_Filter_Image' );

	include( dirname( __FILE__ ) . '/dom-transform-filters/class-instant-articles-dom-transform-filter-blockquote.php' );
	Instant_Articles_DOM_Transform_Filter_Runner::register( 'Instant_Articles_DOM_Transform_Filter_Blockquote' );

	include( dirname( __FILE__ ) . '/dom-transform-filters/class-instant-articles-dom-transform-filter-unordered-list.php' );
	Instant_Articles_DOM_Transform_Filter_Runner::register( 'Instant_Articles_DOM_Transform_Filter_Unordered_List' );

	include( dirname( __FILE__ ) . '/dom-transform-filters/class-instant-articles-dom-transform-filter-ordered-list.php' );
	Instant_Articles_DOM_Transform_Filter_Runner::register( 'Instant_Articles_DOM_Transform_Filter_Ordered_List' );

	include( dirname( __FILE__ ) . '/dom-transform-filters/class-instant-articles-dom-transform-filter-table.php' );
	Instant_Articles_DOM_Transform_Filter_Runner::register( 'Instant_Articles_DOM_Transform_Filter_Table' );

	include( dirname( __FILE__ ) . '/dom-transform-filters/class-instant-articles-dom-transform-filter-address.php' );
	Instant_Articles_DOM_Transform_Filter_Runner::register( 'Instant_Articles_DOM_Transform_Filter_Address' );

	//Instant articles only support h1 and h2. Convert h3-h6 to h2.
	include( dirname( __FILE__ ) . '/dom-transform-filters/class-instant-articles-dom-transform-filter-heading.php' );
	Instant_Articles_DOM_Transform_Filter_Runner::register( 'Instant_Articles_DOM_Transform_Filter_Heading' );

	// Remove empty elements
	include( dirname( __FILE__ ) . '/dom-transform-filters/class-instant-articles-dom-transform-filter-emptyelements.php' );
	Instant_Articles_DOM_Transform_Filter_Runner::register( 'Instant_Articles_DOM_Transform_Filter_Emptyelements', 90 );
}
add_action( 'instant_articles_register_dom_transformation_filters', 'instant_articles_register_transformation_filters' );


/**
 * Modify the main query for our feed.
 *
 * We want the posts in the modified order, to provide Facebook with content updates even for older posts.
 * Facebook will only import 100 posts at the time.
 * Facebook will only update posts modified within the last 24 hours
 *
 * @param WP_Query  $query  The WP_Query object. Passed by reference.
 */
function instant_articles_query( $query ) {

	if ( $query->is_main_query() && $query->is_feed( INSTANT_ARTICLES_SLUG ) ) {

		$query->set( 'orderby', 'modified' );
		$query->set( 'posts_per_page', 100 );
		$query->set( 'posts_per_rss', 100 );

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
 * Filter the SQL query to not include posts with empty content -- FB will complain
 *
 * @since 0.1
 * @param string   $where  The original where part of the SQL statement
 * @param WP_Query $query  The WP_Query instance
 * @return string  The modified where part of the SQL statement
 */
function instant_articles_query_where( $where, $query ) {

	// Don’t modify the SQL query with a potentially expensive WHERE clause if we’re OK with fewer posts than 100 and are OK with filtering in the loop
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


