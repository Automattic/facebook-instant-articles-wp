<?php
/**
 * Facebook Instant Articles for WP.
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package default
 */

require_once dirname( __DIR__ ) . '/class-instant-articles-post.php';

/**
 * Test class responsible for constructing article content
 *
 * @since 0.1
 */
class InstantArticlesPost extends WP_UnitTestCase {

	protected $post_id;

	public function setup() {
		$user_id                     = $this->factory->user->create();
		$post                        = $this->factory->post->create_and_get(
			array(
				'post_author'   => $user_id,
				'post_title'    => 'Article title',
				'post_content'  => 'something',
				'post_excerpt'  => 'This is the excerpt.',
				'post_date'     => '',
				'post_modified' => '',
			)
		);
		$this->post_id               = $post->ID;
		$this->instant_articles_post = new Instant_Articles_Post( $post );
	}

	public function testCreateInstance() {
		$this->assertInstanceOf( 'Instant_Articles_Post', $this->instant_articles_post );
	}

	public function testGetPostFields() {

		$this->assertEquals( 'Article title', $this->instant_articles_post->get_the_title() );
		$this->assertEquals( 'Article title', $this->instant_articles_post->get_the_title_rss() );
		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN . '/?p=' . $this->post_id, $this->instant_articles_post->get_canonical_url() );
		$this->assertTrue( is_string( $this->instant_articles_post->get_the_excerpt() ), 'Expected string assertion failed.' );
		$this->assertTrue( is_string( $this->instant_articles_post->get_the_excerpt_rss() ), 'Expected string assertion failed.' );
	}

	public function testGetFeaturedImage_NoImage_HasArray() {
		$this->assertTrue( is_array( $this->instant_articles_post->get_the_featured_image() ) );
	}

	public function testGetTheKicker_NoCategory() {
		$this->assertEmpty( $this->instant_articles_post->get_the_kicker() );
	}
}
