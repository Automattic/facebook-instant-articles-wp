<?php
/**
 * Facebook Instant Articles for WP.
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package default
 */

use Facebook\InstantArticles\Client\Client;
use Facebook\Facebook;

/**
 * Class responsible for drawing the meta box on the post edit page
 *
 * @since 0.1
 */
class Instant_Articles_Publisher {

	/**
	 * Inits publisher.
	 */
	public static function init() {
		add_action( 'save_post', array( 'Instant_Articles_Publisher', 'submit_article' ), 10, 2 );
		add_action( 'transition_post_status', array( 'Instant_Articles_Publisher', 'unpublish_article' ), 10, 3 );
	}

	/**
	 * Submits article to Instant Articles.
	 *
	 * @param string $post_id The identifier of post.
	 * @param Post   $post The WP Post.
	 */
	public static function submit_article( $post_id, $post ) {

		// Don't process if this is just a revision or an autosave.
		if ( wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) ) {
			return;
		}

		// Don't process if this post is not published
		if ('publish' !== $post->post_status) {
			return;
		}

		// Transform the post to an Instant Article.
		$adapter = new Instant_Articles_Post( $post );
		$article = $adapter->to_instant_article();
		
		// Import published Instant Article		
		self::import_article($article, false);

	}

	public static function unpublish_article( $new_status, $old_status, $post ) {
	  if ( $old_status == 'publish'  &&  $new_status != 'publish' ) {
			
			// Transform the post to an Instant Article.
			$adapter = new Instant_Articles_Post( $post );
			$article = $adapter->to_instant_article();
			
			// Import unpublished Instant Article		
			self::import_article($article, true);
	  }
	}

	public static function import_article($article, $shouldBeDraft){
		// Instantiate an API client.
		try {
			$fb_app_settings = Instant_Articles_Option_FB_App::get_option_decoded();
			$fb_page_settings = Instant_Articles_Option_FB_Page::get_option_decoded();
			$publishing_settings = Instant_Articles_Option_Publishing::get_option_decoded();

			$dev_mode = isset( $publishing_settings['dev_mode'] )
				? ( $publishing_settings['dev_mode'] ? true : false )
				: false;

			if ( isset( $fb_app_settings['app_id'] )
				&& isset( $fb_app_settings['app_secret'] )
				&& isset( $fb_page_settings['page_access_token'] )
				&& isset( $fb_page_settings['page_id'] ) ) {

				$client = Client::create(
					$fb_app_settings['app_id'],
					$fb_app_settings['app_secret'],
					$fb_page_settings['page_access_token'],
					$fb_page_settings['page_id'],
					$dev_mode
				);

				if ( $dev_mode || $shouldBeDraft) {
					$take_live = false;
				} else {
					// Any publish status other than 'publish' means draft for the Instant Article.
					$take_live = true;
				}

				try {
					// Import the article.
					$client->importArticle( $article, $take_live );
				} catch ( Exception $e ) {
					// Try without taking live for pages not yet reviewed.
					$client->importArticle( $article, false );
				}
			}
		} catch ( Exception $e ) {
			Logger::getLogger( 'instantarticles-wp-plugin' )->error(
				'Unable to submit article.',
				$e->getTraceAsString()
			);
		}
	} 
}
