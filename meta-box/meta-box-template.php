<?php
/**
 * Facebook Instant Articles for WP.
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package default
 */

use Facebook\InstantArticles\Client\InstantArticleStatus;
use Facebook\InstantArticles\Client\ServerMessage;
?>

<?php if ( $dev_mode ) : ?>
<a href="<?php echo esc_url( $settings_page_href ); ?>" class="instant-articles-dev-mode-indicator">
	<span class="dashicons dashicons-admin-tools"></span>
	Development Mode
</a>
<?php endif; ?>

<?php if ( $adapter->should_submit_post() ) : ?>
<p>
	<b>
		<span class="dashicons dashicons-yes"></span>
		This post will be available as Instant Article once it is shared on Facebook.
	</b>
</p>
<hr>
<?php elseif ( ! $instant_articles_should_submit_post_filter ) : ?>
<p>
	<b>
		<span class="dashicons dashicons-no-alt"></span>
		This post will not be available as Instant Article due to a rule created in your site.
	</b>
</p>
<hr>
<?php elseif ( ! $published ) : ?>
<p>
	<b>
		<span class="dashicons dashicons-media-document"></span>
		This post will be available as Instant Article once it is published and shared on Facebook.
	</b>
</p>
<hr>
<?php elseif ( ! $article->getHeader() || ! $article->getHeader()->getTitle() ) : ?>
<p>
	<b>
		<span class="dashicons dashicons-no-alt"></span>
		This post will not be available as Instant Article because it is missing a title.
	</b>
</p>
<hr>
<?php elseif ( count( $article->getChildren() ) === 0 ) : ?>
<p>
	<b>
		<span class="dashicons dashicons-no-alt"></span>
		This post will not be available as Instant Article because it is missing content.
	</b>
</p>
<hr>
<?php elseif ( ! $fb_page_settings['page_id'] ) : ?>
<p>
	<b>
		<span class="dashicons dashicons-no-alt"></span>
		No Facebook Page was selected. Please configure your page in the
		<a href="<?php echo esc_url( $settings_page_href ); ?>">Instant Articles plugin settings</a>.
	</b>
</p>
<hr>
<?php elseif ( count( $adapter->transformer->getWarnings() ) > 0 ) : ?>
<p>
	<b>
		<span class="dashicons dashicons-no-alt"></span>
		This post will not be available as Instant Article because the transformation raised some warnings.
	</b>
</p>
<hr>
<?php endif; ?>

<!-- Transformer messages -->
<?php if ( count( $adapter->transformer->getWarnings() ) > 0 ) : ?>
	<p>
		<span class="dashicons dashicons-warning"></span>
		This post was transformed into an Instant Article with some warnings
		[<a href="https://wordpress.org/plugins/fb-instant-articles/faq/" target="_blank">Learn more</a> |
		<a href="<?php echo esc_url( $settings_page_href ); ?>">Transformer rule configuration</a> |
		<a href="#" class="instant-articles-toggle-debug">Toggle debug information</a>]
	</p>
	<ul class="instant-articles-messages">
		<?php foreach ( $adapter->transformer->getWarnings() as $warning ) : ?>
			<li class="wp-ui-text-highlight">
				<span class="dashicons dashicons-warning"></span>
				<div class="message">
					<?php echo esc_html( $warning ); ?>
					<span>
						<?php
						if ( $warning->getNode() ) {
							echo esc_html(
								$warning->getNode()->ownerDocument->saveHTML( $warning->getNode() )
							);
						}
						?>
					</span>
				</div>
				</dl>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php if ( ! $publish_with_warnings ) : ?>
		<hr />
		<p>
			<input type="checkbox" id="instant_articles_force_submit" data-security="<?php echo esc_attr( $ajax_nonce ); ?>" <?php checked( $force_submit, 1 ); ?> />
			Submit this article even with warnings
		</p>
	<?php endif; ?>

<?php else : ?>
	<p>
		<span class="dashicons dashicons-yes"></span>
		This post was transformed into an Instant Article with no warnings
		[<a href="#" class="instant-articles-toggle-debug">Toggle debug information]</a>
	</p>
<?php endif; ?>

<div class="instant-articles-transformer-markup">
	<div>
		<label for="source">Source Markup:</label>
		<textarea class="source" readonly><?php echo esc_textarea( $adapter->get_the_content() ); ?></textarea>
	</div>
	<div>
		<label for="transformed">Transformed Markup:</label>
		<textarea class="source" readonly><?php echo esc_textarea( $article->render( '', true ) ); ?></textarea>
	</div>
	<br clear="all">
</div>
