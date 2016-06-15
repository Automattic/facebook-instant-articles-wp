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
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		$settings_publishing = Instant_Articles_Option_Publishing::get_option_decoded();

		if( $settings_publishing['categories'] !== '' ) {

			$post_categories = wp_get_post_categories( $post_id );
			$allowed_categories = explode(',', $settings_publishing['categories']);

			//Don't process if the post's categories are not in the list of allowed categories
			if(count( array_intersect( $post_categories, $allowed_categories ) ) == 0 ) {
				return;
			}
		}

		// Transform the post to an Instant Article.
		$adapter = new Instant_Articles_Post( $post );

		$article = $adapter->to_instant_article();

		// Skip empty articles or articles missing title.
		// This is important because the save_post action is also triggered by bulk updates, but in this case
		// WordPress does not load the content field from DB for performance reasons. In this case, articles
		// will be empty here, despite of them actually having content.
		if ( count($article->getChildren()) === 0 || ! $article->getHeader() || ! $article->getHeader()->getTitle() ) {
			return;
		}

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

				if ( $dev_mode ) {
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
