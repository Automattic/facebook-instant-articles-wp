<?php
/**
 * FBIA status option in the submit meta box.
 *
 * @package default
 */

/**
 * Inherited template vars.
 *
 * @var array  $labels Labels for enabled or disabled.
 * @var string $status Enabled or disabled.
 */

?>
<div class="misc-pub-section misc-fbia-status">
	<span class="fbia-icon"></span>
	<?php esc_html_e( 'Instant Articles:', 'instant-articles' ); ?>
	<strong class="fbia-status-text"><?php echo esc_html( $labels[ $status ] ); ?></strong>
	<a href="#fbia_status" class="edit-fbia-status hide-if-no-js" role="button">
		<span aria-hidden="true"><?php esc_html_e( 'Edit', 'instant-articles' ); ?></span>
		<span class="screen-reader-text"><?php esc_html_e( 'Edit Status', 'instant-articles' ); ?></span>
	</a>
	<div id="fbia-status-select" class="hide-if-js" data-fbia-status="<?php echo esc_attr( $status ); ?>">
		<fieldset>
			<input id="fbia-status-enabled" type="radio" name="<?php echo esc_attr( self::STATUS_INPUT_NAME ); ?>" value="<?php echo esc_attr( self::ENABLED_STATUS ); ?>" <?php checked( self::ENABLED_STATUS, $status ); ?>>
			<label for="fbia-status-enabled" class="selectit"><?php echo esc_html( $labels['enabled'] ); ?></label>
			<br />
			<input id="fbia-status-disabled" type="radio" name="<?php echo esc_attr( self::STATUS_INPUT_NAME ); ?>" value="<?php echo esc_attr( self::DISABLED_STATUS ); ?>" <?php checked( self::DISABLED_STATUS, $status ); ?>>
			<label for="fbia-status-disabled" class="selectit"><?php echo esc_html( $labels['disabled'] ); ?></label>
			<br />
			<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>
		</fieldset>
		<div class="fbia-status-actions">
		<a href="#fbia_status" class="save-fbia-status hide-if-no-js button"><?php esc_html_e( 'OK', 'instant-articles' ); ?></a>
		<a href="#fbia_status" class="cancel-fbia-status hide-if-no-js button-cancel"><?php esc_html_e( 'Cancel', 'instant-articles' ); ?></a>
		</div>
	</div>
</div>
