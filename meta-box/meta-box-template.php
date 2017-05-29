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

<?php if ( ! $should_submit_post ) : ?>
<p>
	<b>
		<span class="dashicons dashicons-no-alt"></span>
		This post will not be submitted to Instant Articles due to a rule created in your site.
	</b>
</p>
<?php elseif ( ! $published ) : ?>
<p>
	<b>
		<span class="dashicons dashicons-media-document"></span>
		Your post will be submitted to Instant Articles once it is published.
	</b>
</p>

<?php elseif ( $submission_status ) : ?>

	<!-- Display the last submission status -->
	<?php switch ( $submission_status->getStatus() ) :
		case InstantArticleStatus::SUCCESS : ?>
			<p>
				<b>
					<span class="dashicons dashicons-yes"></span>
					Your article was submitted to Instant Articles successfully
				</b>
			</p>
			<?php break; ?>

		<?php case InstantArticleStatus::FAILED : ?>
			<p>
				<b>
					<span class="dashicons dashicons-no-alt"></span>
					It wasn't possible to submit your article
				</b>
			</p>
			<?php break; ?>

		<?php case InstantArticleStatus::IN_PROGRESS : ?>
			<p>
				<b>
					<span class="dashicons dashicons-update"></span>
					Your article is being submitted...
				</b>
			</p>
			<script>
				setTimeout(function () {
					instant_articles_load_meta_box( <?php echo absint( $post->ID ); ?> );
				}, 2000);
			</script>

			<?php break; ?>

		<?php default : ?>
			<p>
				<b>
					<span class="dashicons dashicons-no-alt"></span>
					This post was not yet submitted to Instant Articles.
				</b>
			</p>
			<?php break; ?>

	<?php endswitch; ?>


	<!-- Display the submission messages if any -->
	<?php if ( count( $submission_status->getMessages() ) > 0 ) : ?>

		<p>The server responded with the following messages:</p>

		<ul class="instant-articles-messages">

			<?php foreach ( $submission_status->getMessages() as $message ) : ?>

				<?php switch ( $message->getLevel() ) :
					case ServerMessage::WARNING : ?>
						<li class="wp-ui-text-highlight">
							<span class="dashicons dashicons-warning"></span>
							<div>
								<?php echo esc_html( $message->getMessage() ); ?>
							</div>
						</li>
						<?php break; ?>

					<?php case ServerMessage::ERROR : ?>
						<li class="wp-ui-text-notification">
							<span class="dashicons dashicons-dismiss"></span>
							<div>
								<?php echo esc_html( $message->getMessage() ); ?>
							</div>
						</li>
						<?php break; ?>

					<?php case ServerMessage::FATAL : ?>
						<li class="wp-ui-text-notification">
							<span class="dashicons dashicons-sos"></span>
							<div>
								<?php echo esc_html( $message->getMessage() ); ?>
							</div>
						</li>
						<?php break; ?>

					<?php default: ?>
						<li class="wp-ui-text-highlight">
							<span class="dashicons dashicons-info"></span>
							<div>
								<?php echo esc_html( $message->getMessage() ); ?>
							</div>
						</li>

				<?php endswitch; ?>

			<?php endforeach; ?>
			</ul>

	<?php endif; ?>

<?php else : ?>

	<p>
		<b>
			<span class="dashicons dashicons-no-alt"></span>
			Could not connect to your page. Please check the
			<a href="<?php echo esc_url( $settings_page_href ); ?>">Instant Articles plugin settings</a>.
		</b>
	</p>

<?php endif; ?>

<hr>

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
			<input type="checkbox" id="instant_articles_force_submit" data-security="<?php echo $ajax_nonce; ?>" <?php checked( $force_submit , 1 ); ?> />
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
		<textarea class="source" readonly><?php echo esc_textarea( $article->render( null, true ) ); ?></textarea>
	</div>
	<br clear="all">
</div>
