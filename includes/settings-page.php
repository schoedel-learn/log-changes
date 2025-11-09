<?php
/**
 * Settings page for Log Changes plugin.
 *
 * @package LogChanges
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the settings page.
 */
function log_changes_render_settings_page() {
	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'log-changes' ) );
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		
		<form method="post" action="options.php">
			<?php
			settings_fields( 'log_changes_settings' );
			do_settings_sections( 'log_changes_settings' );
			submit_button();
			?>
		</form>
		
		<hr>
		
		<h2><?php esc_html_e( 'Manual Cleanup', 'log-changes' ); ?></h2>
		<p><?php esc_html_e( 'Run cleanup now to delete logs older than the configured period.', 'log-changes' ); ?></p>
		<form method="post" action="">
			<?php wp_nonce_field( 'log_changes_manual_cleanup', 'log_changes_cleanup_nonce' ); ?>
			<button type="submit" name="log_changes_run_cleanup" class="button button-secondary" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to run cleanup now? This will delete all logs older than the configured period.', 'log-changes' ) ); ?>');">
				<span class="dashicons dashicons-trash" style="margin-top: 3px;"></span>
				<?php esc_html_e( 'Run Cleanup Now', 'log-changes' ); ?>
			</button>
		</form>
		
		<?php
		// Handle manual cleanup.
		if ( isset( $_POST['log_changes_run_cleanup'] ) && check_admin_referer( 'log_changes_manual_cleanup', 'log_changes_cleanup_nonce' ) ) {
			global $log_changes_instance;
			if ( $log_changes_instance ) {
				$deleted = $log_changes_instance->manual_cleanup_old_logs();
				if ( $deleted !== false ) {
					echo '<div class="notice notice-success is-dismissible"><p>';
					/* translators: %d: number of deleted log entries */
					printf( esc_html( _n( 'Cleanup complete. %d log entry deleted.', 'Cleanup complete. %d log entries deleted.', $deleted, 'log-changes' ) ), (int) $deleted );
					echo '</p></div>';
				}
			}
		}
		?>
	</div>
	<?php
}

/**
 * Initialize settings.
 */
function log_changes_settings_init() {
	// Register settings.
	register_setting(
		'log_changes_settings',
		'log_changes_options',
		array(
			'sanitize_callback' => 'log_changes_sanitize_settings',
		)
	);
	
	// Option Logging Controls section.
	add_settings_section(
		'log_changes_option_controls',
		__( 'Option Logging Controls', 'log-changes' ),
		'log_changes_option_controls_callback',
		'log_changes_settings'
	);
	
	add_settings_field(
		'log_option_changes',
		__( 'Log WordPress option changes', 'log-changes' ),
		'log_changes_checkbox_field',
		'log_changes_settings',
		'log_changes_option_controls',
		array(
			'label_for' => 'log_option_changes',
			'name'      => 'log_option_changes',
			'default'   => true,
		)
	);
	
	add_settings_field(
		'option_exclusions',
		__( 'Excluded option patterns', 'log-changes' ),
		'log_changes_textarea_field',
		'log_changes_settings',
		'log_changes_option_controls',
		array(
			'label_for'   => 'option_exclusions',
			'name'        => 'option_exclusions',
			'default'     => log_changes_get_default_exclusions(),
			'description' => __( 'One pattern per line. Use * as wildcard. Example: *_transient* will skip all transients.', 'log-changes' ),
			'rows'        => 10,
		)
	);
	
	add_settings_field(
		'option_allowlist',
		__( 'Always log these options', 'log-changes' ),
		'log_changes_textarea_field',
		'log_changes_settings',
		'log_changes_option_controls',
		array(
			'label_for'   => 'option_allowlist',
			'name'        => 'option_allowlist',
			'default'     => log_changes_get_default_allowlist(),
			'description' => __( 'One option per line. These will always be logged even if they match exclusion patterns.', 'log-changes' ),
			'rows'        => 10,
		)
	);
	
	add_settings_field(
		'log_wp_user_roles',
		__( 'Log wp_user_roles changes', 'log-changes' ),
		'log_changes_checkbox_field',
		'log_changes_settings',
		'log_changes_option_controls',
		array(
			'label_for'   => 'log_wp_user_roles',
			'name'        => 'log_wp_user_roles',
			'default'     => false,
			'description' => __( 'Plugins often trigger wp_user_roles updates automatically. Uncheck to reduce noise.', 'log-changes' ),
		)
	);
	
	// Other Logging Controls section.
	add_settings_section(
		'log_changes_other_controls',
		__( 'Other Logging Controls', 'log-changes' ),
		'log_changes_other_controls_callback',
		'log_changes_settings'
	);
	
	$logging_types = array(
		'log_post_changes'   => __( 'Log post/page updates', 'log-changes' ),
		'log_user_changes'   => __( 'Log user changes', 'log-changes' ),
		'log_plugin_changes' => __( 'Log plugin changes', 'log-changes' ),
		'log_theme_changes'  => __( 'Log theme changes', 'log-changes' ),
		'log_media_changes'  => __( 'Log media uploads/deletes', 'log-changes' ),
		'log_menu_changes'   => __( 'Log menu changes', 'log-changes' ),
		'log_widget_changes' => __( 'Log widget changes', 'log-changes' ),
	);
	
	foreach ( $logging_types as $key => $label ) {
		add_settings_field(
			$key,
			$label,
			'log_changes_checkbox_field',
			'log_changes_settings',
			'log_changes_other_controls',
			array(
				'label_for' => $key,
				'name'      => $key,
				'default'   => true,
			)
		);
	}
	
	// Cleanup Settings section.
	add_settings_section(
		'log_changes_cleanup_settings',
		__( 'Cleanup Settings', 'log-changes' ),
		'log_changes_cleanup_settings_callback',
		'log_changes_settings'
	);
	
	add_settings_field(
		'cleanup_days',
		__( 'Auto-delete logs older than (days)', 'log-changes' ),
		'log_changes_number_field',
		'log_changes_settings',
		'log_changes_cleanup_settings',
		array(
			'label_for'   => 'cleanup_days',
			'name'        => 'cleanup_days',
			'default'     => 21,
			'min'         => 1,
			'max'         => 365,
			'description' => __( 'Logs older than this many days will be automatically deleted daily.', 'log-changes' ),
		)
	);
}
add_action( 'admin_init', 'log_changes_settings_init' );

/**
 * Section callback for Option Logging Controls.
 */
function log_changes_option_controls_callback() {
	echo '<p>' . esc_html__( 'Control which WordPress option changes are logged to reduce noise from automated processes.', 'log-changes' ) . '</p>';
}

/**
 * Section callback for Other Logging Controls.
 */
function log_changes_other_controls_callback() {
	echo '<p>' . esc_html__( 'Enable or disable logging for different content types.', 'log-changes' ) . '</p>';
}

/**
 * Section callback for Cleanup Settings.
 */
function log_changes_cleanup_settings_callback() {
	echo '<p>' . esc_html__( 'Configure automatic cleanup of old log entries.', 'log-changes' ) . '</p>';
}

/**
 * Render checkbox field.
 *
 * @param array $args Field arguments.
 */
function log_changes_checkbox_field( $args ) {
	$options = get_option( 'log_changes_options', array() );
	$value   = isset( $options[ $args['name'] ] ) ? $options[ $args['name'] ] : $args['default'];
	$checked = (bool) $value;
	?>
	<label>
		<input type="checkbox" 
			id="<?php echo esc_attr( $args['label_for'] ); ?>" 
			name="log_changes_options[<?php echo esc_attr( $args['name'] ); ?>]" 
			value="1" 
			<?php checked( $checked ); ?> />
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<span class="description"><?php echo esc_html( $args['description'] ); ?></span>
		<?php endif; ?>
	</label>
	<?php
}

/**
 * Render textarea field.
 *
 * @param array $args Field arguments.
 */
function log_changes_textarea_field( $args ) {
	$options = get_option( 'log_changes_options', array() );
	$value   = isset( $options[ $args['name'] ] ) ? $options[ $args['name'] ] : $args['default'];
	$rows    = isset( $args['rows'] ) ? $args['rows'] : 5;
	?>
	<textarea 
		id="<?php echo esc_attr( $args['label_for'] ); ?>" 
		name="log_changes_options[<?php echo esc_attr( $args['name'] ); ?>]" 
		rows="<?php echo esc_attr( $rows ); ?>" 
		class="large-text code"><?php echo esc_textarea( $value ); ?></textarea>
	<?php if ( ! empty( $args['description'] ) ) : ?>
		<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
	<?php endif; ?>
	<?php
}

/**
 * Render number field.
 *
 * @param array $args Field arguments.
 */
function log_changes_number_field( $args ) {
	$options = get_option( 'log_changes_options', array() );
	$value   = isset( $options[ $args['name'] ] ) ? $options[ $args['name'] ] : $args['default'];
	?>
	<input type="number" 
		id="<?php echo esc_attr( $args['label_for'] ); ?>" 
		name="log_changes_options[<?php echo esc_attr( $args['name'] ); ?>]" 
		value="<?php echo esc_attr( $value ); ?>" 
		min="<?php echo esc_attr( $args['min'] ); ?>" 
		max="<?php echo esc_attr( $args['max'] ); ?>" 
		class="small-text" />
	<?php if ( ! empty( $args['description'] ) ) : ?>
		<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
	<?php endif; ?>
	<?php
}

/**
 * Get default exclusion patterns.
 *
 * @return string Default exclusions (one per line).
 */
function log_changes_get_default_exclusions() {
	return implode( "\n", array(
		// Transients (already handled but included for completeness).
		'_transient_*',
		'_site_transient_*',
		
		// Cron.
		'cron',
		'doing_cron',
		
		// Asset versions.
		'__*_asset_version',
		'*_version_*',
		
		// Hit counters and analytics.
		'*hit_count*',
		'*page_views*',
		'*visitor_count*',
		
		// User roles (unless explicitly enabled).
		'wp_user_roles',
		'*_user_roles',
		
		// Sessions and cache.
		'*_session_*',
		'*_cache_*',
		
		// Temporary data.
		'*_temp_*',
		'*_tmp_*',
		
		// Auto-generated.
		'rewrite_rules',
		'can_compress_scripts',
		
		// Plugin internal state.
		'*_doing_*',
		'*_processing_*',
	) );
}

/**
 * Get default allowlist patterns.
 *
 * @return string Default allowlist (one per line).
 */
function log_changes_get_default_allowlist() {
	return implode( "\n", array(
		// Site settings.
		'blogname',
		'blogdescription',
		'siteurl',
		'home',
		'admin_email',
		
		// Reading/writing.
		'posts_per_page',
		'date_format',
		'time_format',
		
		// Discussion.
		'default_comment_status',
		'comment_moderation',
		
		// Permalinks.
		'permalink_structure',
		'category_base',
		'tag_base',
	) );
}

/**
 * Sanitize settings.
 *
 * @param array $input Input data.
 * @return array Sanitized data.
 */
function log_changes_sanitize_settings( $input ) {
	$sanitized = array();
	
	// Sanitize checkboxes (boolean values).
	$checkbox_fields = array(
		'log_option_changes',
		'log_wp_user_roles',
		'log_post_changes',
		'log_user_changes',
		'log_plugin_changes',
		'log_theme_changes',
		'log_media_changes',
		'log_menu_changes',
		'log_widget_changes',
	);
	
	foreach ( $checkbox_fields as $field ) {
		$sanitized[ $field ] = ! empty( $input[ $field ] ) ? 1 : 0;
	}
	
	// Sanitize textareas (patterns).
	if ( isset( $input['option_exclusions'] ) ) {
		$sanitized['option_exclusions'] = log_changes_sanitize_patterns( $input['option_exclusions'] );
	}
	
	if ( isset( $input['option_allowlist'] ) ) {
		$sanitized['option_allowlist'] = log_changes_sanitize_patterns( $input['option_allowlist'] );
	}
	
	// Sanitize cleanup days.
	if ( isset( $input['cleanup_days'] ) ) {
		$days = absint( $input['cleanup_days'] );
		$sanitized['cleanup_days'] = max( 1, min( 365, $days ) );
	}
	
	return $sanitized;
}

/**
 * Sanitize wildcard patterns to prevent regex injection.
 *
 * @param string $patterns Patterns (one per line).
 * @return string Sanitized patterns.
 */
function log_changes_sanitize_patterns( $patterns ) {
	// Split into lines.
	$lines = explode( "\n", $patterns );
	
	// Sanitize each line.
	$sanitized = array();
	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( empty( $line ) ) {
			continue;
		}
		
		// Only allow alphanumeric, underscore, dash, and asterisk.
		$line = preg_replace( '/[^a-zA-Z0-9_*-]/', '', $line );
		
		if ( ! empty( $line ) ) {
			$sanitized[] = $line;
		}
	}
	
	return implode( "\n", $sanitized );
}
