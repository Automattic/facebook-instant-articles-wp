<?php

use Facebook\InstantArticles\Elements\Analytics;

// Wrap the wpcom tracking pixel to comply with the FBIA spec
// https://developers.facebook.com/docs/instant-articles/reference/analytics
function wpcom_fbia_remove_stats_pixel() {
	if ( ! defined( 'INSTANT_ARTICLES_SLUG' ) ) {
		return;
	}

	if ( ! is_feed( INSTANT_ARTICLES_SLUG ) ) {
		return;
	}

	// Stop wpcom adding the tracking pixel
	remove_filter( 'the_content', 'add_bug_to_feed', 100 );

}
add_action( 'template_redirect', 'wpcom_fbia_remove_stats_pixel' );

function wpcom_fbia_add_stats_pixel( $ia_post ) {
	global $current_blog;

	// Get the IA article.
	$instant_article = $ia_post->instant_article;

	// Create the wpcom stats code.
	$hostname = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : ''; // input var okay

	$url = 'https://pixel.wp.com/b.gif?host=' . $hostname . '&blog=' . $current_blog->blog_id . '&post=' . $ia_post->get_the_id() . '&subd=' . str_replace( '.wordpress.com', '', $current_blog->domain ) . '&ref=&feed=1';

	$pixel_html = '<script>
		var x = new Image(); x.src = "' . esc_js( $url ) . '&rand=" +Math.random();
	</script>';

	// Create our FBIA markup
	$fbia_markup = Analytics::create();
	$fbia_markup->withHTML( $pixel_html );

	// Add the FBIA-compatible stats markup to the IA content
	$instant_article->addChild( $fbia_markup );

}
add_action( 'instant_articles_after_transform_post', 'wpcom_fbia_add_stats_pixel' );

// make sure these function run in wp.com environment where `plugins_loaded` is already fired when loading the plugin
add_action( 'after_setup_theme', 'instant_articles_load_textdomain' );
add_action( 'after_setup_theme', 'instant_articles_load_compat' );
