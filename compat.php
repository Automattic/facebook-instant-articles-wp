<?php
/**
 * Facebook Instant Articles for WP.
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package default
 */

// Load compat layer for Co-Authors Plus.
if ( function_exists( 'get_coauthors' ) && ! defined( 'CAP_IA_COMPAT' ) ) {
	include dirname( __FILE__ ) . '/compat/class-instant-articles-co-authors-plus.php';
	$cap = new Instant_Articles_Co_Authors_Plus();
	$cap->init();
}

// Load compat layer for Yoast SEO.
if ( defined( 'WPSEO_VERSION' ) && ! defined( 'WPSEO_IA_COMPAT' ) ) {
	include dirname( __FILE__ ) . '/compat/class-instant-articles-yoast-seo.php';
	$yseo = new Instant_Articles_Yoast_SEO();
	$yseo->init();
}

// Load support for Google Analytics for WordPress by MonsterInsights.
if ( ( defined( 'GAWP_VERSION' ) || function_exists( 'MonsterInsights' ) ) && ! defined( 'GAWP_IA_COMPAT' ) ) {
	include dirname( __FILE__ ) . '/compat/class-instant-articles-google-analytics-for-wordpress.php';
	$gawp = new Instant_Articles_Google_Analytics_For_WordPress();
	$gawp->init();
}

// Load support for Google Tag Manager for WordPress by Duracelltomi.
if ( defined( 'GTM4WP_VERSION' ) ) {
	include dirname( __FILE__ ) . '/compat/class-instant-articles-google-tag-manager-for-wordpress.php';
	$gtm4wp = new Instant_Articles_Google_Tag_Manager_For_WordPress();
	$gtm4wp->init();
}

// Load support for Jetpack
if ( defined( 'JETPACK__VERSION' ) ) {
	include dirname( __FILE__ ) . '/compat/class-instant-articles-jetpack.php';
	$jp = new Instant_Articles_Jetpack();
	$jp->init();
}

// Load support for Get The Image plugin
if ( function_exists( 'get_the_image' ) ) {
	include dirname( __FILE__ ) . '/compat/class-instant-articles-get-the-image.php';
	$gti = new Instant_Articles_Get_The_Image();
	$gti->init();
}

// Load support for Playbuzz plugin by default #515
require dirname( __FILE__ ) . '/compat/class-instant-articles-playbuzz.php';
$playbuzz = new Instant_Articles_Playbuzz();
$playbuzz->init();

// Load support for Apester's plugin Medias
require dirname( __FILE__ ) . '/compat/class-instant-articles-apester.php';
$apester = new Instant_Articles_Apester();
$apester->init();

// Load support for Comscore
require dirname( __FILE__ ) . '/compat/class-instant-articles-comscore.php';
$comscore = new Instant_Articles_Comscore();
$comscore->init();
