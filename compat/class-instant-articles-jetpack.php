<?php

/**
 * Support class for Jetpack
 *
 * @since 0.2
 *
 */
class Instant_Articles_Jetpack {

	/**
	 * Init the compat layer
	 *
	 */
	public function init() {
		$this->fix_youtube_embed();
		$this->fix_facebook_embed();
		add_filter( 'instant_articles_transformer_rules_loaded', array( 'Instant_Articles_Jetpack', 'transformer_loaded' ) );
	}

	/**
	 * Remove the YouTube embed handling in Jetpack
	 *
	 */
	private function fix_youtube_embed() {

		/**
		 * Do not "fix" bare URLs on their own line of the form
		 * http://www.youtube.com/v/9FhMMmqzbD8?fs=1&hl=en_US
		 * as we have oEmbed to handle those
		 * Registered in jetpack/modules/shortcodes/youtube.php
		 */
		wp_embed_unregister_handler( 'wpcom_youtube_embed_crazy_url' );
	}

	/**
	 * Fix the Facebook embed handling
	 *
	 */
	private function fix_facebook_embed() {

		// Don't try to fix facebook embeds unless we're in Instant Articles context.
		// This prevents mangled output on frontend.
		if ( ! is_transforming_instant_article() ) {
			return;
		}

		// All of these are registered in jetpack/modules/shortcodes/facebook.php

		if ( defined( 'JETPACK_FACEBOOK_EMBED_REGEX' ) ) {
			wp_embed_unregister_handler( 'facebook' );
			wp_embed_register_handler( 'facebook', JETPACK_FACEBOOK_EMBED_REGEX, array( __CLASS__, 'facebook_embed_handler' ) );
		}
		if ( defined( 'JETPACK_FACEBOOK_ALTERNATE_EMBED_REGEX' ) ) {
			wp_embed_unregister_handler( 'facebook-alternate' );
			wp_embed_register_handler( 'facebook-alternate', JETPACK_FACEBOOK_ALTERNATE_EMBED_REGEX, array( __CLASS__, 'facebook_embed_handler' ) );
		}
		if ( defined( 'JETPACK_FACEBOOK_PHOTO_EMBED_REGEX' ) ) {
			wp_embed_unregister_handler( 'facebook-photo' );
			wp_embed_register_handler( 'facebook-photo', JETPACK_FACEBOOK_PHOTO_EMBED_REGEX, array( __CLASS__, 'facebook_embed_handler' ) );
		}
		if ( defined( 'JETPACK_FACEBOOK_PHOTO_ALTERNATE_EMBED_REGEX' ) ) {
			wp_embed_unregister_handler( 'facebook-alternate-photo' );
			wp_embed_register_handler( 'facebook-alternate-photo', JETPACK_FACEBOOK_PHOTO_ALTERNATE_EMBED_REGEX, array( __CLASS__, 'facebook_embed_handler' ) );
		}
		if ( defined( 'JETPACK_FACEBOOK_VIDEO_EMBED_REGEX' ) ) {
			wp_embed_unregister_handler( 'facebook-video' );
			wp_embed_register_handler( 'facebook-video', JETPACK_FACEBOOK_VIDEO_EMBED_REGEX, array( __CLASS__, 'facebook_embed_handler' ) );
		}
		if ( defined( 'JETPACK_FACEBOOK_VIDEO_ALTERNATE_EMBED_REGEX' ) ) {
			wp_embed_unregister_handler( 'facebook-alternate-video' );
			wp_embed_register_handler( 'facebook-alternate-video', JETPACK_FACEBOOK_VIDEO_ALTERNATE_EMBED_REGEX, array( __CLASS__, 'facebook_embed_handler' ) );
		}
	}

	public static function facebook_embed_handler( $matches, $attr, $url ) {

		$locale = get_locale();

		// Source: https://www.facebook.com/translations/FacebookLocales.xml
		$fb_locales = array( 'af_ZA', 'ak_GH', 'am_ET', 'ar_AR', 'as_IN', 'ay_BO', 'az_AZ', 'be_BY', 'bg_BG', 'bn_IN', 'br_FR', 'bs_BA', 'ca_ES', 'cb_IQ', 'ck_US', 'co_FR', 'cs_CZ', 'cx_PH', 'cy_GB', 'da_DK', 'de_DE', 'el_GR', 'en_GB', 'en_IN', 'en_PI', 'en_UD', 'en_US', 'eo_EO', 'es_CL', 'es_CO', 'es_ES', 'es_LA', 'es_MX', 'es_VE', 'et_EE', 'eu_ES', 'fa_IR', 'fb_LT', 'ff_NG', 'fi_FI', 'fo_FO', 'fr_CA', 'fr_FR', 'fy_NL', 'ga_IE', 'gl_ES', 'gn_PY', 'gu_IN', 'gx_GR', 'ha_NG', 'he_IL', 'hi_IN', 'hr_HR', 'ht_HT', 'hu_HU', 'hy_AM', 'id_ID', 'ig_NG', 'is_IS', 'it_IT', 'ja_JP', 'ja_KS', 'jv_ID', 'ka_GE', 'kk_KZ', 'km_KH', 'kn_IN', 'ko_KR', 'ku_TR', 'ky_KG', 'la_VA', 'lg_UG', 'li_NL', 'ln_CD', 'lo_LA', 'lt_LT', 'lv_LV', 'mg_MG', 'mi_NZ', 'mk_MK', 'ml_IN', 'mn_MN', 'mr_IN', 'ms_MY', 'mt_MT', 'my_MM', 'nb_NO', 'nd_ZW', 'ne_NP', 'nl_BE', 'nl_NL', 'nn_NO', 'ny_MW', 'or_IN', 'pa_IN', 'pl_PL', 'ps_AF', 'pt_BR', 'pt_PT', 'qc_GT', 'qu_PE', 'rm_CH', 'ro_RO', 'ru_RU', 'rw_RW', 'sa_IN', 'sc_IT', 'se_NO', 'si_LK', 'sk_SK', 'sl_SI', 'sn_ZW', 'so_SO', 'sq_AL', 'sr_RS', 'sv_SE', 'sw_KE', 'sy_SY', 'sz_PL', 'ta_IN', 'te_IN', 'tg_TJ', 'th_TH', 'tk_TM', 'tl_PH', 'tl_ST', 'tr_TR', 'tt_RU', 'tz_MA', 'uk_UA', 'ur_PK', 'uz_UZ', 'vi_VN', 'wo_SN', 'xh_ZA', 'yi_DE', 'yo_NG', 'zh_CN', 'zh_HK', 'zh_TW', 'zu_ZA', 'zz_TR' );

		// If our locale isn’t supported by Facebook, we’ll fallback to en_US
		if ( ! in_array( $locale, $fb_locales, true ) ) {
			$locale = 'en_US';
		}

		return '<figure class="op-interactive"><iframe><script src="https://connect.facebook.net/' . $locale . '/sdk.js#xfbml=1&amp;version=v2.6" async></script><div class="fb-post" data-href="' . esc_url( $url ) . '"></div></iframe></figure>'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
	}

	public static function transformer_loaded( $transformer ) {
		// Appends more rules to transformer
		$file_path     = plugin_dir_path( __FILE__ ) . 'jetpack-rules-configuration.json';
		$configuration = file_get_contents( $file_path ); // phpcs:ignore WordPressVIPMinimum.VIP.FetchingRemoteData.fileGetContentsUknown
		$transformer->loadRules( $configuration );

		return $transformer;
	}
}
