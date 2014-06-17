<div class="wrap">
	<h2>
		<?php esc_html_e( 'Plugin Logs', 'wp-logger' ); ?>
	</h2>

	<?php
		/*
		 * Check if email message was sent. If so, display a successful message on success or a
		 * failure message on failure.
		 */
	?>
	<?php if ( isset( $_POST['message_sent'] ) && $_POST['message_sent'] ) : ?>

		<div class="updated">
			<p><?php esc_html_e( 'Your message was sent successfully!', 'wp-logger' ); ?></p>
		</div>

	<?php elseif ( isset( $_POST['message_sent'] ) && ! $_POST['message_sent'] ) : ?>

		<div class="error">
			<p><?php esc_html_e( 'Your message failed to send.', 'wp-logger' ); ?></p>
		</div>

	<?php endif; ?>

	<form method="post" id="logger-form" action="<?php echo admin_url( 'admin.php?page=wp_logger_messages' ); ?>">
		<?php wp_nonce_field( 'wp_logger_generate_report', 'wp_logger_form_nonce' ) ?>

		<div class="tablenav top">
			<div class="alignleft actions">
				<input type="text" placeholder="Search" name="search" value="<?php echo $search; ?>">
			</div>
		</div>

		<div class="tablenav top">
			<div class="alignleft actions bulkactions">
				<select id="plugin-select" name="plugin-select">
					<option value=""><?php esc_html_e( 'All Plugins', 'wp-logger' ); ?></option>

					<?php
						foreach ( $plugins as $plugin ) {
							$temp_plugin_name = esc_attr( $plugin->name );
							echo "<option value='$temp_plugin_name'" . selected( $plugin->name, $plugin_select, false ) . ">$temp_plugin_name</option>";
						}
					?>
				</select>

				<span id="log-select-contain">
					<?php $this->build_log_select( $plugin_select, $log_id ); ?>
				</span>

				<span id="session-select-contain">
					<?php $this->build_session_select( $log_id, $session_id ); ?>
				</span>

				<input type="submit" class="button button-primary" value="Generate Report">
			</div>

			<?php $logger_table->pagination( 'top' ); ?>
			<br class="clear">
		</div>

		<table class="wp-list-table <?php echo implode( ' ', $logger_table->get_table_classes() ); ?>">
			<thead>
			<tr>
				<?php $logger_table->print_column_headers(); ?>
			</tr>
			</thead>

			<tfoot>
			<tr>
				<?php $logger_table->print_column_headers( false ); ?>
			</tr>
			</tfoot>

			<tbody>
				<?php $logger_table->display_rows_or_placeholder(); ?>
			</tbody>
		</table>
	</form>
</div>