<?php
/**
 * Facebook Instant Articles for WP.
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package default
 */

header( 'Content-Type: ' . feed_content_type( 'rss2' ) . '; charset=' . get_option( 'blog_charset' ), true );
echo '<?xml version="1.0" encoding="' . esc_attr( get_option( 'blog_charset' ) ) . '"?' . '>';

$last_modified = null;
?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
	<channel>
		<title><?php bloginfo_rss( 'name' ); ?> - <?php esc_html_e( 'Instant Articles', 'instant-articles' ); ?></title>
		<link><?php bloginfo_rss( 'url' ); ?></link>
		<description><?php bloginfo_rss( 'description' ); ?></description>
		<?php 
		while ( have_posts() ) :
			the_post(); 
			?>
			<?php
			$instant_article_post = new Instant_Articles_Post( get_post( get_the_id() ) );

			// Allow to disable post submit via filter
			if ( false === apply_filters( 'instant_articles_should_submit_post', true, $instant_article_post ) ) {
				continue;
			}

			// If we’re OK with a limited post set: Do not include posts with empty content -- FB will complain.
			if ( defined( 'INSTANT_ARTICLES_LIMIT_POSTS' ) && INSTANT_ARTICLES_LIMIT_POSTS && ! strlen( trim( $instant_article_post->get_the_content() ) ) ) {
				continue;
			}

			// Posts are sorted by modification time, so our first accepted post should be the one last modified.
			if ( is_null( $last_modified ) ) {
				$last_modified = $instant_article_post->get_the_moddate_iso();
			}
			?>
			<item>
				<title><?php echo esc_html( $instant_article_post->get_the_title() ); ?></title>
				<link><?php echo esc_url( $instant_article_post->get_canonical_url() ); ?></link>
				<content:encoded>
					<![CDATA[<?php echo $instant_article_post->to_instant_article()->render(); ?>]]>
				</content:encoded>
				<guid isPermaLink="false"><?php esc_html( the_guid() ); ?></guid>
				<description><![CDATA[<?php echo esc_html( $instant_article_post->get_the_excerpt() ); ?>]]></description>
				<pubDate><?php echo esc_html( $instant_article_post->get_the_pubdate_iso() ); ?></pubDate>
				<modDate><?php echo esc_html( $instant_article_post->get_the_moddate_iso() ); ?></modDate>
				<?php $authors = $instant_article_post->get_the_authors(); ?>
				<?php if ( is_array( $authors ) && count( $authors ) ) : ?>
					<?php foreach ( $authors as $author ) : ?>
						<author><?php echo esc_html( $author->display_name ); ?></author>
					<?php endforeach; ?>
				<?php endif; ?>
			</item>
		<?php endwhile; ?>
		<?php if ( ! is_null( $last_modified ) ) : ?>
			<lastBuildDate><?php echo esc_html( $last_modified ); ?></lastBuildDate>
		<?php endif; ?>
	</channel>
</rss>
