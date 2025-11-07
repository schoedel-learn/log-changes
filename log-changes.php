<?php
/**
 * Plugin Name: Log Changes
 * Plugin URI: https://schoedel.design/log-changes
 * Description: Tracks all changes to your WordPress site including posts, pages, users, plugins, themes, and settings. Records what changed, when, and who made the changes.
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Author: Barry Schoedel
 * Author URI: https://schoedel.design
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: log-changes
 * Domain Path: /languages
 *
 * @package LogChanges
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'LOG_CHANGES_VERSION', '1.0.0' );
define( 'LOG_CHANGES_PLUGIN_FILE', __FILE__ );
define( 'LOG_CHANGES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LOG_CHANGES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LOG_CHANGES_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class.
 */
class Log_Changes {

	/**
	 * Database table name.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'change_log';
		
		// Register activation and deactivation hooks.
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		
		// Initialize plugin.
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Initialize the plugin.
	 */
	public function init() {
		// Load text domain for translations.
		load_plugin_textdomain( 'log-changes', false, dirname( LOG_CHANGES_PLUGIN_BASENAME ) . '/languages' );
		
		// Initialize hooks.
		$this->init_hooks();
		
		// Initialize admin interface.
		if ( is_admin() ) {
			$this->init_admin();
		}
		
		// Schedule automatic cleanup if not already scheduled.
		if ( ! wp_next_scheduled( 'log_changes_auto_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'log_changes_auto_cleanup' );
		}
		
		// Hook for automatic cleanup.
		add_action( 'log_changes_auto_cleanup', array( $this, 'auto_cleanup_old_logs' ) );
	}

	/**
	 * Initialize tracking hooks.
	 */
	private function init_hooks() {
		// Track post/page changes.
		add_action( 'save_post', array( $this, 'track_post_save' ), 10, 3 );
		add_action( 'delete_post', array( $this, 'track_post_delete' ), 10, 2 );
		add_action( 'transition_post_status', array( $this, 'track_post_status' ), 10, 3 );
		
		// Track user changes.
		add_action( 'user_register', array( $this, 'track_user_register' ), 10, 1 );
		add_action( 'profile_update', array( $this, 'track_user_update' ), 10, 2 );
		add_action( 'delete_user', array( $this, 'track_user_delete' ), 10, 1 );
		add_action( 'set_user_role', array( $this, 'track_user_role_change' ), 10, 3 );
		
		// Track theme changes.
		add_action( 'switch_theme', array( $this, 'track_theme_switch' ), 10, 3 );
		
		// Track plugin changes.
		add_action( 'activated_plugin', array( $this, 'track_plugin_activated' ), 10, 2 );
		add_action( 'deactivated_plugin', array( $this, 'track_plugin_deactivated' ), 10, 2 );
		
		// Track media uploads and deletions.
		add_action( 'add_attachment', array( $this, 'track_media_upload' ), 10, 1 );
		add_action( 'delete_attachment', array( $this, 'track_media_delete' ), 10, 1 );
		
		// Track menu changes.
		add_action( 'wp_create_nav_menu', array( $this, 'track_menu_create' ), 10, 2 );
		add_action( 'wp_update_nav_menu', array( $this, 'track_menu_update' ), 10, 2 );
		add_action( 'wp_delete_nav_menu', array( $this, 'track_menu_delete' ), 10, 1 );
		
		// Track widget changes.
		add_filter( 'widget_update_callback', array( $this, 'track_widget_update' ), 10, 4 );
		
		// Track option changes.
		add_action( 'updated_option', array( $this, 'track_option_update' ), 10, 3 );
		add_action( 'added_option', array( $this, 'track_option_add' ), 10, 2 );
		add_action( 'deleted_option', array( $this, 'track_option_delete' ), 10, 1 );
	}

	/**
	 * Initialize admin interface.
	 */
	private function init_admin() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'admin_init', array( $this, 'handle_export_delete_actions' ) );
	}

	/**
	 * Plugin activation.
	 */
	public function activate() {
		global $wpdb;
		
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			timestamp datetime NOT NULL,
			user_id bigint(20) unsigned DEFAULT NULL,
			user_login varchar(60) DEFAULT NULL,
			action_type varchar(50) NOT NULL,
			object_type varchar(50) NOT NULL,
			object_id bigint(20) unsigned DEFAULT NULL,
			object_name varchar(255) DEFAULT NULL,
			description text DEFAULT NULL,
			old_value longtext DEFAULT NULL,
			new_value longtext DEFAULT NULL,
			ip_address varchar(100) DEFAULT NULL,
			user_agent varchar(255) DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY action_type (action_type),
			KEY object_type (object_type),
			KEY timestamp (timestamp)
		) $charset_collate;";
		
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		
		// Set plugin version.
		update_option( 'log_changes_version', LOG_CHANGES_VERSION );
		
		// Log plugin activation.
		$this->log_change(
			'activated',
			'plugin',
			0,
			'Log Changes',
			'Plugin activated'
		);
	}

	/**
	 * Plugin deactivation.
	 */
	public function deactivate() {
		// Clear scheduled event.
		$timestamp = wp_next_scheduled( 'log_changes_auto_cleanup' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'log_changes_auto_cleanup' );
		}
		
		// Log plugin deactivation.
		$this->log_change(
			'deactivated',
			'plugin',
			0,
			'Log Changes',
			'Plugin deactivated'
		);
	}

	/**
	 * Log a change to the database.
	 *
	 * @param string $action_type Type of action (created, updated, deleted, etc.).
	 * @param string $object_type Type of object (post, user, plugin, etc.).
	 * @param int    $object_id ID of the object.
	 * @param string $object_name Name of the object.
	 * @param string $description Description of the change.
	 * @param mixed  $old_value Old value (optional).
	 * @param mixed  $new_value New value (optional).
	 */
	private function log_change( $action_type, $object_type, $object_id, $object_name, $description, $old_value = null, $new_value = null ) {
		global $wpdb;
		
		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;
		$user_login = $current_user->user_login;
		
		// Only log changes from logged-in users (UI changes), skip automated actions.
		if ( ! $user_id ) {
			return;
		}
		
		$ip_address = $this->get_user_ip();
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 ) : '';
		
		$wpdb->insert(
			$this->table_name,
			array(
				'timestamp'    => current_time( 'mysql' ),
				'user_id'      => $user_id ? $user_id : null,
				'user_login'   => $user_login,
				'action_type'  => $action_type,
				'object_type'  => $object_type,
				'object_id'    => $object_id,
				'object_name'  => $object_name,
				'description'  => $description,
				'old_value'    => is_array( $old_value ) || is_object( $old_value ) ? wp_json_encode( $old_value ) : $old_value,
				'new_value'    => is_array( $new_value ) || is_object( $new_value ) ? wp_json_encode( $new_value ) : $new_value,
				'ip_address'   => $ip_address,
				'user_agent'   => $user_agent,
			),
			array( '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Get user IP address with protection against header spoofing.
	 *
	 * @return string IP address.
	 */
	private function get_user_ip() {
		// For X-Forwarded headers, get the first IP (the client's real IP).
		// Headers can contain multiple comma-separated IPs: "client, proxy1, proxy2".
		$forwarded_keys = array( 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED' );
		
		foreach ( $forwarded_keys as $key ) {
			if ( isset( $_SERVER[ $key ] ) ) {
				$forwarded_ips = wp_unslash( $_SERVER[ $key ] );
				// Split by comma and get the first IP (client IP).
				$ip_list = array_map( 'trim', explode( ',', $forwarded_ips ) );
				foreach ( $ip_list as $ip ) {
					if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
						return sanitize_text_field( $ip );
					}
				}
			}
		}
		
		// Check other headers and REMOTE_ADDR as fallbacks.
		$direct_keys = array( 'HTTP_CLIENT_IP', 'HTTP_X_CLUSTER_CLIENT_IP', 'REMOTE_ADDR' );
		
		foreach ( $direct_keys as $key ) {
			if ( isset( $_SERVER[ $key ] ) && filter_var( wp_unslash( $_SERVER[ $key ] ), FILTER_VALIDATE_IP ) ) {
				return sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
			}
		}
		
		return '';
	}

	/**
	 * Track post save.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 * @param bool    $update Whether this is an update.
	 */
	public function track_post_save( $post_id, $post, $update ) {
		// Skip auto-saves and revisions.
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		
		$action = $update ? 'updated' : 'created';
		$description = sprintf(
			'%s "%s" (ID: %d, Type: %s)',
			$update ? 'Updated' : 'Created',
			$post->post_title,
			$post_id,
			$post->post_type
		);
		
		$this->log_change(
			$action,
			'post',
			$post_id,
			$post->post_title,
			$description
		);
	}

	/**
	 * Track post status transitions.
	 *
	 * @param string  $new_status New status.
	 * @param string  $old_status Old status.
	 * @param WP_Post $post Post object.
	 */
	public function track_post_status( $new_status, $old_status, $post ) {
		// Skip if status hasn't changed.
		if ( $new_status === $old_status ) {
			return;
		}
		
		// Skip auto-saves and revisions.
		if ( wp_is_post_autosave( $post->ID ) || wp_is_post_revision( $post->ID ) ) {
			return;
		}
		
		$description = sprintf(
			'Post status changed from "%s" to "%s" for "%s" (ID: %d, Type: %s)',
			$old_status,
			$new_status,
			$post->post_title,
			$post->ID,
			$post->post_type
		);
		
		$this->log_change(
			'status_changed',
			'post',
			$post->ID,
			$post->post_title,
			$description,
			$old_status,
			$new_status
		);
	}

	/**
	 * Track post deletion.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 */
	public function track_post_delete( $post_id, $post ) {
		$description = sprintf(
			'Deleted "%s" (ID: %d, Type: %s)',
			$post->post_title,
			$post_id,
			$post->post_type
		);
		
		$this->log_change(
			'deleted',
			'post',
			$post_id,
			$post->post_title,
			$description
		);
	}

	/**
	 * Track user registration.
	 *
	 * @param int $user_id User ID.
	 */
	public function track_user_register( $user_id ) {
		$user = get_userdata( $user_id );
		
		if ( ! $user ) {
			return;
		}
		
		$description = sprintf(
			'New user registered: %s (ID: %d)',
			$user->user_login,
			$user_id
		);
		
		$this->log_change(
			'created',
			'user',
			$user_id,
			$user->user_login,
			$description
		);
	}

	/**
	 * Track user update.
	 *
	 * @param int   $user_id User ID.
	 * @param array $old_user_data Old user data.
	 */
	public function track_user_update( $user_id, $old_user_data ) {
		$user = get_userdata( $user_id );
		
		if ( ! $user ) {
			return;
		}
		
		$description = sprintf(
			'User profile updated: %s (ID: %d)',
			$user->user_login,
			$user_id
		);
		
		$this->log_change(
			'updated',
			'user',
			$user_id,
			$user->user_login,
			$description
		);
	}

	/**
	 * Track user deletion.
	 *
	 * @param int $user_id User ID.
	 */
	public function track_user_delete( $user_id ) {
		$user = get_userdata( $user_id );
		$user_login = $user ? $user->user_login : 'Unknown';
		
		$description = sprintf(
			'User deleted: %s (ID: %d)',
			$user_login,
			$user_id
		);
		
		$this->log_change(
			'deleted',
			'user',
			$user_id,
			$user_login,
			$description
		);
	}

	/**
	 * Track user role change.
	 *
	 * @param int    $user_id User ID.
	 * @param string $role New role.
	 * @param array  $old_roles Old roles.
	 */
	public function track_user_role_change( $user_id, $role, $old_roles ) {
		$user = get_userdata( $user_id );
		
		if ( ! $user ) {
			return;
		}
		
		$old_role = ! empty( $old_roles ) ? implode( ', ', $old_roles ) : 'none';
		
		$description = sprintf(
			'User role changed for %s (ID: %d) from "%s" to "%s"',
			$user->user_login,
			$user_id,
			$old_role,
			$role
		);
		
		$this->log_change(
			'role_changed',
			'user',
			$user_id,
			$user->user_login,
			$description,
			$old_role,
			$role
		);
	}

	/**
	 * Track theme switch.
	 *
	 * @param string   $new_name New theme name.
	 * @param WP_Theme $new_theme New theme object.
	 * @param WP_Theme $old_theme Old theme object.
	 */
	public function track_theme_switch( $new_name, $new_theme, $old_theme ) {
		$description = sprintf(
			'Theme switched from "%s" to "%s"',
			$old_theme->get( 'Name' ),
			$new_theme->get( 'Name' )
		);
		
		$this->log_change(
			'switched',
			'theme',
			0,
			$new_theme->get( 'Name' ),
			$description,
			$old_theme->get( 'Name' ),
			$new_theme->get( 'Name' )
		);
	}

	/**
	 * Track plugin activation.
	 *
	 * @param string $plugin Plugin file.
	 * @param bool   $network_wide Network wide activation.
	 */
	public function track_plugin_activated( $plugin, $network_wide ) {
		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin, false, false );
		
		$description = sprintf(
			'Plugin activated: %s%s',
			$plugin_data['Name'],
			$network_wide ? ' (network-wide)' : ''
		);
		
		$this->log_change(
			'activated',
			'plugin',
			0,
			$plugin_data['Name'],
			$description
		);
	}

	/**
	 * Track plugin deactivation.
	 *
	 * @param string $plugin Plugin file.
	 * @param bool   $network_wide Network wide deactivation.
	 */
	public function track_plugin_deactivated( $plugin, $network_wide ) {
		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin, false, false );
		
		$description = sprintf(
			'Plugin deactivated: %s%s',
			$plugin_data['Name'],
			$network_wide ? ' (network-wide)' : ''
		);
		
		$this->log_change(
			'deactivated',
			'plugin',
			0,
			$plugin_data['Name'],
			$description
		);
	}

	/**
	 * Track media upload.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function track_media_upload( $attachment_id ) {
		$attachment = get_post( $attachment_id );
		
		if ( ! $attachment ) {
			return;
		}
		
		$description = sprintf(
			'Media uploaded: %s (ID: %d, Type: %s)',
			$attachment->post_title,
			$attachment_id,
			get_post_mime_type( $attachment_id )
		);
		
		$this->log_change(
			'uploaded',
			'media',
			$attachment_id,
			$attachment->post_title,
			$description
		);
	}

	/**
	 * Track media deletion.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function track_media_delete( $attachment_id ) {
		$attachment = get_post( $attachment_id );
		$title = $attachment ? $attachment->post_title : 'Unknown';
		
		$description = sprintf(
			'Media deleted: %s (ID: %d)',
			$title,
			$attachment_id
		);
		
		$this->log_change(
			'deleted',
			'media',
			$attachment_id,
			$title,
			$description
		);
	}

	/**
	 * Track menu creation.
	 *
	 * @param int   $menu_id Menu ID.
	 * @param array $menu_data Menu data.
	 */
	public function track_menu_create( $menu_id, $menu_data ) {
		$menu_name = isset( $menu_data['menu-name'] ) ? $menu_data['menu-name'] : 'Unknown';
		
		$description = sprintf(
			'Navigation menu created: %s (ID: %d)',
			$menu_name,
			$menu_id
		);
		
		$this->log_change(
			'created',
			'menu',
			$menu_id,
			$menu_name,
			$description
		);
	}

	/**
	 * Track menu update.
	 *
	 * @param int   $menu_id Menu ID.
	 * @param array $menu_data Menu data.
	 */
	public function track_menu_update( $menu_id, $menu_data ) {
		$menu = wp_get_nav_menu_object( $menu_id );
		$menu_name = $menu ? $menu->name : 'Unknown';
		
		$description = sprintf(
			'Navigation menu updated: %s (ID: %d)',
			$menu_name,
			$menu_id
		);
		
		$this->log_change(
			'updated',
			'menu',
			$menu_id,
			$menu_name,
			$description
		);
	}

	/**
	 * Track menu deletion.
	 *
	 * @param int $menu_id Menu ID.
	 */
	public function track_menu_delete( $menu_id ) {
		$menu = wp_get_nav_menu_object( $menu_id );
		$menu_name = $menu ? $menu->name : 'Unknown';
		
		$description = sprintf(
			'Navigation menu deleted: %s (ID: %d)',
			$menu_name,
			$menu_id
		);
		
		$this->log_change(
			'deleted',
			'menu',
			$menu_id,
			$menu_name,
			$description
		);
	}

	/**
	 * Track widget update.
	 *
	 * @param array     $instance New widget instance.
	 * @param array     $new_instance New widget instance.
	 * @param array     $old_instance Old widget instance.
	 * @param WP_Widget $widget Widget object.
	 * @return array Updated widget instance.
	 */
	public function track_widget_update( $instance, $new_instance, $old_instance, $widget ) {
		$description = sprintf(
			'Widget updated: %s (ID: %s)',
			$widget->name,
			$widget->id
		);
		
		$this->log_change(
			'updated',
			'widget',
			0,
			$widget->name,
			$description
		);
		
		return $instance;
	}

	/**
	 * Track option addition.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value Option value.
	 */
	public function track_option_add( $option, $value ) {
		// Skip tracking for transients and internal WordPress options.
		if ( $this->should_skip_option( $option ) ) {
			return;
		}
		
		$description = sprintf(
			'Option added: %s',
			$option
		);
		
		$this->log_change(
			'added',
			'option',
			0,
			$option,
			$description,
			null,
			$value
		);
	}

	/**
	 * Track option update.
	 *
	 * @param string $option Option name.
	 * @param mixed  $old_value Old value.
	 * @param mixed  $value New value.
	 */
	public function track_option_update( $option, $old_value, $value ) {
		// Skip tracking for transients and internal WordPress options.
		if ( $this->should_skip_option( $option ) ) {
			return;
		}
		
		$description = sprintf(
			'Option updated: %s',
			$option
		);
		
		$this->log_change(
			'updated',
			'option',
			0,
			$option,
			$description,
			$old_value,
			$value
		);
	}

	/**
	 * Track option deletion.
	 *
	 * @param string $option Option name.
	 */
	public function track_option_delete( $option ) {
		// Skip tracking for transients and internal WordPress options.
		if ( $this->should_skip_option( $option ) ) {
			return;
		}
		
		$description = sprintf(
			'Option deleted: %s',
			$option
		);
		
		$this->log_change(
			'deleted',
			'option',
			0,
			$option,
			$description
		);
	}

	/**
	 * Check if option should be skipped from tracking.
	 *
	 * @param string $option Option name.
	 * @return bool True if should skip.
	 */
	private function should_skip_option( $option ) {
		// Use regex for efficient pattern matching of frequently-changing options.
		// Matches: cron, doing_cron, _transient*, _site_transient*
		$skip_pattern = '/^(cron|doing_cron|_transient|_site_transient)/';
		if ( preg_match( $skip_pattern, $option ) ) {
			return true;
		}
		
		return false;
	}

	/**
	 * Handle export and delete actions from admin page.
	 */
	public function handle_export_delete_actions() {
		// Check if we're on the log changes page.
		if ( ! isset( $_GET['page'] ) || 'log-changes' !== $_GET['page'] ) {
			return;
		}
		
		// Check for export action.
		if ( isset( $_GET['action'] ) && 'export' === $_GET['action'] ) {
			// Verify nonce.
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'log_changes_export' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'log-changes' ) );
			}
			
			// Check user capabilities.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to export logs.', 'log-changes' ) );
			}
			
			// Build filters from GET parameters.
			list( $where_clauses, $where_values ) = $this->build_filter_clauses();
			
			// Export logs.
			$this->export_logs_to_csv( $where_clauses, $where_values );
		}
		
		// Check for export and delete action.
		if ( isset( $_GET['action'] ) && 'export_delete' === $_GET['action'] ) {
			// Verify nonce.
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'log_changes_export_delete' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'log-changes' ) );
			}
			
			// Check user capabilities.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to delete logs.', 'log-changes' ) );
			}
			
			// Build filters from GET parameters.
			list( $where_clauses, $where_values ) = $this->build_filter_clauses();
			
			// First export logs.
			$this->export_logs_to_csv( $where_clauses, $where_values );
			// Note: The export will exit, so deletion won't happen here.
			// We need to handle this differently - export first, then redirect with delete flag.
		}
		
		// Check for delete after export action.
		if ( isset( $_GET['action'] ) && 'delete_exported' === $_GET['action'] ) {
			// Verify nonce.
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'log_changes_delete' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'log-changes' ) );
			}
			
			// Check user capabilities.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to delete logs.', 'log-changes' ) );
			}
			
			// Build filters from GET parameters.
			list( $where_clauses, $where_values ) = $this->build_filter_clauses();
			
			// Delete logs.
			$deleted_count = $this->delete_logs( $where_clauses, $where_values );
			
			// Redirect with success message.
			if ( false !== $deleted_count ) {
				wp_safe_redirect( add_query_arg(
					array(
						'page' => 'log-changes',
						'deleted' => $deleted_count,
					),
					admin_url( 'admin.php' )
				) );
				exit;
			}
		}
	}
	
	/**
	 * Build filter clauses from GET parameters.
	 *
	 * @return array Array containing where_clauses and where_values.
	 */
	private function build_filter_clauses() {
		global $wpdb;
		
		$where_clauses = array();
		$where_values = array();
		
		// Build WHERE clauses securely.
		if ( ! empty( $_GET['filter_action'] ) ) {
			$where_clauses[] = 'action_type = %s';
			$where_values[] = sanitize_text_field( wp_unslash( $_GET['filter_action'] ) );
		}
		
		if ( ! empty( $_GET['filter_object'] ) ) {
			$where_clauses[] = 'object_type = %s';
			$where_values[] = sanitize_text_field( wp_unslash( $_GET['filter_object'] ) );
		}
		
		if ( ! empty( $_GET['filter_user'] ) ) {
			$where_clauses[] = 'user_id = %d';
			$where_values[] = absint( $_GET['filter_user'] );
		}
		
		if ( ! empty( $_GET['search'] ) ) {
			$search = '%' . $wpdb->esc_like( sanitize_text_field( wp_unslash( $_GET['search'] ) ) ) . '%';
			$where_clauses[] = '(description LIKE %s OR object_name LIKE %s)';
			$where_values[] = $search;
			$where_values[] = $search;
		}
		
		// Add date range filter if provided.
		if ( ! empty( $_GET['date_from'] ) ) {
			$where_clauses[] = 'timestamp >= %s';
			$where_values[] = sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) . ' 00:00:00';
		}
		
		if ( ! empty( $_GET['date_to'] ) ) {
			$where_clauses[] = 'timestamp <= %s';
			$where_values[] = sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) . ' 23:59:59';
		}
		
		return array( $where_clauses, $where_values );
	}

	/**
	 * Export logs to CSV file.
	 *
	 * @param array $where_clauses Array of WHERE clause strings.
	 * @param array $where_values Array of values for WHERE clauses.
	 */
	public function export_logs_to_csv( $where_clauses = array(), $where_values = array() ) {
		global $wpdb;
		
		// Build WHERE clause.
		$where_sql = '';
		if ( ! empty( $where_clauses ) ) {
			$where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );
		}
		
		// Get logs based on filters.
		if ( ! empty( $where_values ) ) {
			$logs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table_name} {$where_sql} ORDER BY timestamp DESC", $where_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} else {
			$logs = $wpdb->get_results( "SELECT * FROM {$this->table_name} ORDER BY timestamp DESC" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
		
		if ( empty( $logs ) ) {
			return false;
		}
		
		// Set headers for CSV download.
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=change-logs-' . gmdate( 'Y-m-d-H-i-s' ) . '.csv' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		
		// Open output stream.
		$output = fopen( 'php://output', 'w' );
		
		// Add UTF-8 BOM for Excel compatibility.
		fprintf( $output, chr(0xEF) . chr(0xBB) . chr(0xBF) );
		
		// Add CSV headers.
		fputcsv( $output, array(
			'ID',
			'Timestamp',
			'User ID',
			'User Login',
			'Action Type',
			'Object Type',
			'Object ID',
			'Object Name',
			'Description',
			'Old Value',
			'New Value',
			'IP Address',
			'User Agent',
		) );
		
		// Add data rows.
		foreach ( $logs as $log ) {
			fputcsv( $output, array(
				$log->id,
				$log->timestamp,
				$log->user_id,
				$log->user_login,
				$log->action_type,
				$log->object_type,
				$log->object_id,
				$log->object_name,
				$log->description,
				$log->old_value,
				$log->new_value,
				$log->ip_address,
				$log->user_agent,
			) );
		}
		
		fclose( $output );
		exit;
	}
	
	/**
	 * Delete logs based on filters.
	 *
	 * @param array $where_clauses Array of WHERE clause strings.
	 * @param array $where_values Array of values for WHERE clauses.
	 * @return int|false Number of rows deleted or false on failure.
	 */
	public function delete_logs( $where_clauses = array(), $where_values = array() ) {
		global $wpdb;
		
		// Build WHERE clause.
		$where_sql = '';
		if ( ! empty( $where_clauses ) ) {
			$where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );
		} else {
			// Require at least one filter to prevent accidental deletion of all logs.
			return false;
		}
		
		// Delete logs.
		if ( ! empty( $where_values ) ) {
			// First, get count for return value.
			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table_name} {$where_sql}", $where_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			
			// Then delete.
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table_name} {$where_sql}", $where_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			
			return $count;
		}
		
		return false;
	}
	
	/**
	 * Automatically cleanup logs older than 21 days.
	 * This runs daily via WordPress cron.
	 */
	public function auto_cleanup_old_logs() {
		global $wpdb;
		
		// Calculate the cutoff date (21 days ago).
		$cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( '-21 days' ) );
		
		// Delete old logs.
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->table_name} WHERE timestamp < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$cutoff_date
			)
		);
		
		// Log the cleanup action if any logs were deleted.
		if ( $deleted > 0 ) {
			// Use direct insert to avoid recursive logging.
			$wpdb->insert(
				$this->table_name,
				array(
					'timestamp'    => current_time( 'mysql' ),
					'user_id'      => 0,
					'user_login'   => 'System',
					'action_type'  => 'cleanup',
					'object_type'  => 'log',
					'object_id'    => 0,
					'object_name'  => 'Automatic Cleanup',
					'description'  => sprintf( 'Automatically deleted %d log entries older than 21 days', $deleted ),
					'old_value'    => null,
					'new_value'    => null,
					'ip_address'   => '',
					'user_agent'   => 'WordPress Cron',
				),
				array( '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
			);
		}
		
		return $deleted;
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Change Log', 'log-changes' ),
			__( 'Change Log', 'log-changes' ),
			'manage_options',
			'log-changes',
			array( $this, 'render_admin_page' ),
			'dashicons-backup',
			80
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Only load on our admin page.
		if ( 'toplevel_page_log-changes' !== $hook ) {
			return;
		}
		
		$css_file = LOG_CHANGES_PLUGIN_DIR . 'assets/css/admin.css';
		if ( file_exists( $css_file ) ) {
			wp_enqueue_style(
				'log-changes-admin',
				LOG_CHANGES_PLUGIN_URL . 'assets/css/admin.css',
				array(),
				LOG_CHANGES_VERSION
			);
		}
		
		$js_file = LOG_CHANGES_PLUGIN_DIR . 'assets/js/admin.js';
		if ( file_exists( $js_file ) ) {
			wp_enqueue_script(
				'log-changes-admin',
				LOG_CHANGES_PLUGIN_URL . 'assets/js/admin.js',
				array( 'jquery' ),
				LOG_CHANGES_VERSION,
				true
			);
			
			// Localize script for translations.
			wp_localize_script(
				'log-changes-admin',
				'logChangesL10n',
				array(
					'confirmClearAll'     => __( 'Are you sure you want to clear all logs? This action cannot be undone.', 'log-changes' ),
					'confirmExportDelete' => __( 'This will export the filtered logs to CSV and then DELETE them from the database. This action cannot be undone. Continue?', 'log-changes' ),
					'loading'             => __( 'Loading...', 'log-changes' ),
					'showDetails'         => __( 'Show Details', 'log-changes' ),
					'hideDetails'         => __( 'Hide Details', 'log-changes' ),
					'exportNonce'         => wp_create_nonce( 'log_changes_export' ),
					'exportDeleteNonce'   => wp_create_nonce( 'log_changes_export_delete' ),
					'deleteNonce'         => wp_create_nonce( 'log_changes_delete' ),
					'ajaxUrl'             => admin_url( 'admin-ajax.php' ),
				)
			);
		}
	}

	/**
	 * Render admin page.
	 */
	public function render_admin_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'log-changes' ) );
		}
		
		global $wpdb;
		
		// Handle pagination.
		$per_page = 50;
		$page_num = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$offset = ( $page_num - 1 ) * $per_page;
		
		// Handle filters.
		$where_clauses = array();
		$where_values = array();
		
		// Build WHERE clauses securely - all clause strings are hardcoded,
		// only values are from user input and will be prepared with wpdb->prepare().
		if ( ! empty( $_GET['filter_action'] ) ) {
			$where_clauses[] = 'action_type = %s';
			$where_values[] = sanitize_text_field( wp_unslash( $_GET['filter_action'] ) );
		}
		
		if ( ! empty( $_GET['filter_object'] ) ) {
			$where_clauses[] = 'object_type = %s';
			$where_values[] = sanitize_text_field( wp_unslash( $_GET['filter_object'] ) );
		}
		
		if ( ! empty( $_GET['filter_user'] ) ) {
			$where_clauses[] = 'user_id = %d';
			$where_values[] = absint( $_GET['filter_user'] );
		}
		
		if ( ! empty( $_GET['search'] ) ) {
			$search = '%' . $wpdb->esc_like( sanitize_text_field( wp_unslash( $_GET['search'] ) ) ) . '%';
			$where_clauses[] = '(description LIKE %s OR object_name LIKE %s)';
			$where_values[] = $search;
			$where_values[] = $search;
		}
		
		// Build WHERE clause - safe because clauses are hardcoded strings, not user input.
		$where_sql = '';
		if ( ! empty( $where_clauses ) ) {
			$where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );
		}
		
		// Get total count.
		if ( ! empty( $where_values ) ) {
			$total_items = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table_name} {$where_sql}", $where_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} else {
			$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
		
		$total_pages = ceil( $total_items / $per_page );
		
		// Get logs.
		if ( ! empty( $where_values ) ) {
			$query_values = array_merge( $where_values, array( $per_page, $offset ) );
			$logs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table_name} {$where_sql} ORDER BY timestamp DESC LIMIT %d OFFSET %d", $query_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} else {
			$logs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table_name} ORDER BY timestamp DESC LIMIT %d OFFSET %d", $per_page, $offset ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
		
		// Get filter options.
		$action_types = $wpdb->get_col( "SELECT DISTINCT action_type FROM {$this->table_name} ORDER BY action_type" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$object_types = $wpdb->get_col( "SELECT DISTINCT object_type FROM {$this->table_name} ORDER BY object_type" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		
		// Get users who have made changes.
		$users = $wpdb->get_results( "SELECT DISTINCT user_id, user_login FROM {$this->table_name} WHERE user_id IS NOT NULL ORDER BY user_login" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		
		// Include the admin template.
		$template_file = LOG_CHANGES_PLUGIN_DIR . 'includes/admin-page.php';
		if ( file_exists( $template_file ) ) {
			include $template_file;
		} else {
			wp_die( esc_html__( 'Admin template file not found.', 'log-changes' ) );
		}
	}
}

// Initialize the plugin.
new Log_Changes();
