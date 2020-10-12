<?php
/**
 * Facebook Instant Articles for WP.
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package default
 */

	?>
<?php if ( ! $ajax ) : ?>
	<h1>Facebook Instant Articles Settings</h1>
	<div id="instant_articles_wizard_messages"><?php settings_errors(); ?></div>

	<div class="instant-articles-wizard-advanced-settings-box">

		<div class="instant-articles-card">
			<div class="instant-articles-card-content">
				<div class="instant-articles-card-content-box instant-articles-card-content-full">
					<form method="post" action="options.php">
						<?php settings_fields( Instant_Articles_Option::PAGE_OPTION_GROUP ); ?>
						<?php do_settings_sections( Instant_Articles_Option_FB_Page::OPTION_KEY ); ?>
						<?php $fb_page_settings = Instant_Articles_Option_FB_Page::get_option_decoded(); ?>
						<div <?php if ( isset( $fb_page_settings[ 'page_id' ] ) && ! $fb_page_settings[ 'page_id' ] ) : ?>style="display: none;"<?php endif; ?>>
							<hr />
							<?php do_settings_sections( Instant_Articles_Option_FB_App::OPTION_KEY ); ?>
							<hr />
							<p>Configure settings for your styles, ads, analytics and publishing in Instant Articles. Review our <a href="https://developers.facebook.com/docs/instant-articles" target="_blank">developer documentation</a> to learn more.</p>
							<hr />
							<?php do_settings_sections( Instant_Articles_Option_Styles::OPTION_KEY ); ?>
							<hr />
							<?php do_settings_sections( Instant_Articles_Option_Ads::OPTION_KEY ); ?>
							<hr />
							<?php do_settings_sections( Instant_Articles_Option_Analytics::OPTION_KEY ); ?>
							<hr />
							<?php do_settings_sections( Instant_Articles_Option_Publishing::OPTION_KEY ); ?>
							<hr />
						</div>
						<?php submit_button( __( 'Save changes' ) ); ?>
					</form>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>
