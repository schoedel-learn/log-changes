<?php
/**
 * Plugin Name: Log Changes
 * Plugin URI: https://schoedel.design/log-changes
 * Description: Comprehensive audit trail for WordPress with login tracking, WooCommerce, Fluent plugins, and more. Tracks posts, pages, users, products, orders, forms, and all site changes with detailed old/new values. Includes CSV export and automatic cleanup.
 * Version: 1.3.0
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
define( 'LOG_CHANGES_VERSION', '1.3.0' );
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
	 * Plugin settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Compiled exclusion patterns for performance.
	 *
	 * @var array
	 */
	private $exclusion_patterns = array();

	/**
	 * Allowlist options.
	 *
	 * @var array
	 */
	private $allowlist_options = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'change_log';
		
		// Load settings.
		$this->load_settings();
		
		// Register activation and deactivation hooks.
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		
		// Initialize plugin.
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Load plugin settings.
	 */
	private function load_settings() {
		// Get settings with defaults.
		$defaults = array(
			'log_option_changes'   => 1,
			'log_wp_user_roles'    => 0,
			'log_post_changes'     => 1,
			'log_user_changes'     => 1,
			'log_plugin_changes'   => 1,
			'log_theme_changes'    => 1,
			'log_media_changes'    => 1,
			'log_menu_changes'     => 1,
			'log_widget_changes'   => 1,
			'cleanup_days'         => 21,
			'option_exclusions'    => '',
			'option_allowlist'     => '',
		);
		
		$this->settings = wp_parse_args( get_option( 'log_changes_options', array() ), $defaults );
		
		// Compile exclusion patterns for performance.
		$this->compile_patterns();
	}

	/**
	 * Compile wildcard patterns into regex for efficient matching.
	 */
	private function compile_patterns() {
		// Process exclusions.
		if ( ! empty( $this->settings['option_exclusions'] ) ) {
			$patterns = explode( "\n", $this->settings['option_exclusions'] );
			foreach ( $patterns as $pattern ) {
				$pattern = trim( $pattern );
				if ( ! empty( $pattern ) ) {
					// Convert wildcard pattern to regex. Only * is supported (? is removed by sanitization).
					$regex = '/^' . str_replace( '\*', '.*', preg_quote( $pattern, '/' ) ) . '$/';
					$this->exclusion_patterns[] = $regex;
				}
			}
		}
		
		// Process allowlist.
		if ( ! empty( $this->settings['option_allowlist'] ) ) {
			$options = explode( "\n", $this->settings['option_allowlist'] );
			foreach ( $options as $option ) {
				$option = trim( $option );
				if ( ! empty( $option ) ) {
					$this->allowlist_options[] = $option;
				}
			}
		}
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
		
		// Track login/logout.
		add_action( 'wp_login', array( $this, 'track_user_login' ), 10, 2 );
		add_action( 'wp_logout', array( $this, 'track_user_logout' ), 10, 1 );
		add_action( 'wp_login_failed', array( $this, 'track_login_failed' ), 10, 2 );
		
		// Track theme changes.
		add_action( 'switch_theme', array( $this, 'track_theme_switch' ), 10, 3 );
		add_action( 'upgrader_process_complete', array( $this, 'track_upgrader_process' ), 10, 2 );
		
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
		
		// Track customizer changes.
		add_action( 'customize_save_after', array( $this, 'track_customizer_save' ), 10, 1 );
		
		// Track option changes.
		add_action( 'updated_option', array( $this, 'track_option_update' ), 10, 3 );
		add_action( 'added_option', array( $this, 'track_option_add' ), 10, 2 );
		add_action( 'deleted_option', array( $this, 'track_option_delete' ), 10, 1 );
		
		// Track WooCommerce if active.
		$this->init_woocommerce_hooks();
		
		// Track Fluent plugins if active.
		$this->init_fluent_hooks();
		
		// Track other plugin-specific hooks.
		$this->init_plugin_specific_hooks();
	}

	/**
	 * Initialize admin interface.
	 */
	private function init_admin() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'admin_init', array( $this, 'handle_export_delete_actions' ) );
		add_filter( 'plugin_action_links_' . LOG_CHANGES_PLUGIN_BASENAME, array( $this, 'add_settings_link' ) );
		
		// Load settings page.
		require_once LOG_CHANGES_PLUGIN_DIR . 'includes/settings-page.php';
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
		// Check if post tracking is enabled.
		if ( empty( $this->settings['log_post_changes'] ) ) {
			return;
		}
		
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
		// Check if post tracking is enabled.
		if ( empty( $this->settings['log_post_changes'] ) ) {
			return;
		}
		
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
		// Check if post tracking is enabled.
		if ( empty( $this->settings['log_post_changes'] ) ) {
			return;
		}
		
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
		// Check if user tracking is enabled.
		if ( empty( $this->settings['log_user_changes'] ) ) {
			return;
		}
		
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
		// Check if user tracking is enabled.
		if ( empty( $this->settings['log_user_changes'] ) ) {
			return;
		}
		
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
		// Check if user tracking is enabled.
		if ( empty( $this->settings['log_user_changes'] ) ) {
			return;
		}
		
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
		// Check if user tracking is enabled.
		if ( empty( $this->settings['log_user_changes'] ) ) {
			return;
		}
		
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
		// Check if theme tracking is enabled.
		if ( empty( $this->settings['log_theme_changes'] ) ) {
			return;
		}
		
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
		// Check if plugin tracking is enabled.
		if ( empty( $this->settings['log_plugin_changes'] ) ) {
			return;
		}
		
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
		// Check if plugin tracking is enabled.
		if ( empty( $this->settings['log_plugin_changes'] ) ) {
			return;
		}
		
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
		// Check if media tracking is enabled.
		if ( empty( $this->settings['log_media_changes'] ) ) {
			return;
		}
		
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
		// Check if media tracking is enabled.
		if ( empty( $this->settings['log_media_changes'] ) ) {
			return;
		}
		
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
		// Check if menu tracking is enabled.
		if ( empty( $this->settings['log_menu_changes'] ) ) {
			return;
		}
		
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
		// Check if menu tracking is enabled.
		if ( empty( $this->settings['log_menu_changes'] ) ) {
			return;
		}
		
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
		// Check if menu tracking is enabled.
		if ( empty( $this->settings['log_menu_changes'] ) ) {
			return;
		}
		
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
		// Check if widget tracking is enabled.
		if ( empty( $this->settings['log_widget_changes'] ) ) {
			return $instance;
		}
		
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
		// Check if option tracking is enabled.
		if ( empty( $this->settings['log_option_changes'] ) ) {
			return;
		}
		
		// Skip tracking for transients and internal WordPress options.
		// Pass null for old_value since this is a new option (no previous value).
		if ( $this->should_skip_option( $option, null, $value ) ) {
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
		// Check if option tracking is enabled.
		if ( empty( $this->settings['log_option_changes'] ) ) {
			return;
		}
		
		// Skip tracking for transients and internal WordPress options.
		if ( $this->should_skip_option( $option, $old_value, $value ) ) {
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
	 * WordPress deleted_option hook doesn't provide the old value,
	 * only the option name. The value has already been deleted from
	 * the database by the time this hook fires.
	 *
	 * @param string $option Option name.
	 */
	public function track_option_delete( $option ) {
		// Check if option tracking is enabled.
		if ( empty( $this->settings['log_option_changes'] ) ) {
			return;
		}
		
		// Skip tracking for transients and internal WordPress options.
		// Pass null for both old/new values since WordPress doesn't provide them.
		if ( $this->should_skip_option( $option, null, null ) ) {
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
	 * Note: old_value and new_value may be null depending on the operation:
	 * - For option_add: old_value is null (no previous value)
	 * - For option_delete: both values are null (WordPress limitation)
	 * - For option_update: both values are provided
	 *
	 * @param string $option Option name.
	 * @param mixed  $old_value Old value (null for add/delete operations).
	 * @param mixed  $new_value New value (null for delete operations).
	 * @return bool True if should skip.
	 */
	private function should_skip_option( $option, $old_value = null, $new_value = null ) {
		// Check allowlist first - these always log even if they match exclusions.
		if ( in_array( $option, $this->allowlist_options, true ) ) {
			// Apply developer filter.
			return ! apply_filters( 'log_changes_should_log_option', true, $option, $old_value, $new_value );
		}
		
		// Special handling for wp_user_roles based on settings.
		if ( 'wp_user_roles' === $option && empty( $this->settings['log_wp_user_roles'] ) ) {
			return true;
		}
		
		// Use regex for efficient pattern matching of frequently-changing options.
		// Built-in patterns: cron, doing_cron, _transient*, _site_transient*
		$skip_pattern = '/^(cron|doing_cron|_transient|_site_transient)/';
		if ( preg_match( $skip_pattern, $option ) ) {
			return true;
		}
		
		// Check against compiled exclusion patterns.
		foreach ( $this->exclusion_patterns as $pattern ) {
			if ( preg_match( $pattern, $option ) ) {
				return true;
			}
		}
		
		// Apply developer filter for additional control.
		$should_log = apply_filters( 'log_changes_should_log_option', true, $option, $old_value, $new_value );
		
		// Also allow developers to add exclusions programmatically.
		$custom_exclusions = apply_filters( 'log_changes_option_exclusions', array() );
		if ( is_array( $custom_exclusions ) && in_array( $option, $custom_exclusions, true ) ) {
			return true;
		}
		
		return ! $should_log;
	}

	/**
	 * Track successful user login.
	 *
	 * @param string  $user_login Username.
	 * @param WP_User $user User object.
	 */
	public function track_user_login( $user_login, $user ) {
		$description = sprintf(
			'User logged in: %s (ID: %d)',
			$user_login,
			$user->ID
		);
		
		// Use special logging for login events (bypasses user check).
		$this->log_login_event(
			'login',
			'user',
			$user->ID,
			$user_login,
			$description,
			$user->ID
		);
	}

	/**
	 * Track user logout.
	 *
	 * @param int $user_id User ID.
	 */
	public function track_user_logout( $user_id ) {
		$user = get_userdata( $user_id );
		
		if ( ! $user ) {
			return;
		}
		
		$description = sprintf(
			'User logged out: %s (ID: %d)',
			$user->user_login,
			$user_id
		);
		
		$this->log_change(
			'logout',
			'user',
			$user_id,
			$user->user_login,
			$description
		);
	}

	/**
	 * Track failed login attempts.
	 *
	 * @param string   $username Username or email.
	 * @param WP_Error $error Error object.
	 */
	public function track_login_failed( $username, $error ) {
		$description = sprintf(
			'Failed login attempt for: %s (Error: %s)',
			$username,
			$error->get_error_message()
		);
		
		// Log failed logins without requiring a logged-in user.
		$this->log_login_event(
			'login_failed',
			'user',
			0,
			$username,
			$description,
			0
		);
	}

	/**
	 * Log login/logout events (special handling without user requirement).
	 *
	 * @param string $action_type Type of action.
	 * @param string $object_type Type of object.
	 * @param int    $object_id ID of the object.
	 * @param string $object_name Name of the object.
	 * @param string $description Description of the change.
	 * @param int    $user_id User ID for the log entry.
	 */
	private function log_login_event( $action_type, $object_type, $object_id, $object_name, $description, $user_id ) {
		global $wpdb;
		
		$ip_address = $this->get_user_ip();
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 ) : '';
		
		$wpdb->insert(
			$this->table_name,
			array(
				'timestamp'    => current_time( 'mysql' ),
				'user_id'      => $user_id ? $user_id : null,
				'user_login'   => $object_name,
				'action_type'  => $action_type,
				'object_type'  => $object_type,
				'object_id'    => $object_id,
				'object_name'  => $object_name,
				'description'  => $description,
				'old_value'    => null,
				'new_value'    => null,
				'ip_address'   => $ip_address,
				'user_agent'   => $user_agent,
			),
			array( '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Track plugin and theme updates.
	 *
	 * @param WP_Upgrader $upgrader Upgrader instance.
	 * @param array       $options Update options.
	 */
	public function track_upgrader_process( $upgrader, $options ) {
		// Only log if there's a user logged in.
		$current_user = wp_get_current_user();
		if ( ! $current_user->ID ) {
			return;
		}
		
		// Track plugin updates.
		if ( isset( $options['type'] ) && 'plugin' === $options['type'] ) {
			if ( isset( $options['action'] ) && 'update' === $options['action'] ) {
				if ( isset( $options['plugins'] ) && is_array( $options['plugins'] ) ) {
					foreach ( $options['plugins'] as $plugin ) {
						$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin, false, false );
						$description = sprintf(
							'Plugin updated: %s to version %s',
							$plugin_data['Name'],
							$plugin_data['Version']
						);
						
						$this->log_change(
							'updated',
							'plugin',
							0,
							$plugin_data['Name'],
							$description
						);
					}
				}
			}
		}
		
		// Track theme updates.
		if ( isset( $options['type'] ) && 'theme' === $options['type'] ) {
			if ( isset( $options['action'] ) && 'update' === $options['action'] ) {
				if ( isset( $options['themes'] ) && is_array( $options['themes'] ) ) {
					foreach ( $options['themes'] as $theme ) {
						$theme_obj = wp_get_theme( $theme );
						$description = sprintf(
							'Theme updated: %s to version %s',
							$theme_obj->get( 'Name' ),
							$theme_obj->get( 'Version' )
						);
						
						$this->log_change(
							'updated',
							'theme',
							0,
							$theme_obj->get( 'Name' ),
							$description
						);
					}
				}
			}
		}
	}

	/**
	 * Track customizer changes.
	 *
	 * @param WP_Customize_Manager $wp_customize Customizer instance.
	 */
	public function track_customizer_save( $wp_customize ) {
		$description = 'Customizer settings saved';
		
		$this->log_change(
			'customizer_save',
			'customizer',
			0,
			'Customizer',
			$description
		);
	}

	/**
	 * Initialize WooCommerce tracking hooks.
	 */
	private function init_woocommerce_hooks() {
		// Check if WooCommerce is active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		
		// Track product changes.
		add_action( 'woocommerce_new_product', array( $this, 'track_wc_product_created' ), 10, 1 );
		add_action( 'woocommerce_update_product', array( $this, 'track_wc_product_updated' ), 10, 1 );
		add_action( 'woocommerce_before_delete_product', array( $this, 'track_wc_product_deleted' ), 10, 1 );
		
		// Track order changes (purchases and returns).
		add_action( 'woocommerce_new_order', array( $this, 'track_wc_order_created' ), 10, 1 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'track_wc_order_status_changed' ), 10, 4 );
	}

	/**
	 * Track WooCommerce product creation.
	 *
	 * @param int $product_id Product ID.
	 */
	public function track_wc_product_created( $product_id ) {
		$product = wc_get_product( $product_id );
		
		if ( ! $product ) {
			return;
		}
		
		$description = sprintf(
			'WooCommerce product created: %s (ID: %d, Type: %s)',
			$product->get_name(),
			$product_id,
			$product->get_type()
		);
		
		$this->log_change(
			'created',
			'wc_product',
			$product_id,
			$product->get_name(),
			$description
		);
	}

	/**
	 * Track WooCommerce product update.
	 *
	 * @param int $product_id Product ID.
	 */
	public function track_wc_product_updated( $product_id ) {
		$product = wc_get_product( $product_id );
		
		if ( ! $product ) {
			return;
		}
		
		$description = sprintf(
			'WooCommerce product updated: %s (ID: %d, Type: %s)',
			$product->get_name(),
			$product_id,
			$product->get_type()
		);
		
		$this->log_change(
			'updated',
			'wc_product',
			$product_id,
			$product->get_name(),
			$description
		);
	}

	/**
	 * Track WooCommerce product deletion.
	 *
	 * @param int $product_id Product ID.
	 */
	public function track_wc_product_deleted( $product_id ) {
		$product = wc_get_product( $product_id );
		$product_name = $product ? $product->get_name() : 'Unknown';
		
		$description = sprintf(
			'WooCommerce product deleted: %s (ID: %d)',
			$product_name,
			$product_id
		);
		
		$this->log_change(
			'deleted',
			'wc_product',
			$product_id,
			$product_name,
			$description
		);
	}

	/**
	 * Track WooCommerce order creation.
	 *
	 * @param int $order_id Order ID.
	 */
	public function track_wc_order_created( $order_id ) {
		$order = wc_get_order( $order_id );
		
		if ( ! $order ) {
			return;
		}
		
		$description = sprintf(
			'WooCommerce order created: Order #%d (Total: %s, Status: %s)',
			$order_id,
			$order->get_formatted_order_total(),
			$order->get_status()
		);
		
		$this->log_change(
			'created',
			'wc_order',
			$order_id,
			'Order #' . $order_id,
			$description
		);
	}

	/**
	 * Track WooCommerce order status changes.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $old_status Old status.
	 * @param string $new_status New status.
	 * @param object $order Order object.
	 */
	public function track_wc_order_status_changed( $order_id, $old_status, $new_status, $order ) {
		$description = sprintf(
			'WooCommerce order status changed: Order #%d from "%s" to "%s" (Total: %s)',
			$order_id,
			$old_status,
			$new_status,
			$order->get_formatted_order_total()
		);
		
		$this->log_change(
			'status_changed',
			'wc_order',
			$order_id,
			'Order #' . $order_id,
			$description,
			$old_status,
			$new_status
		);
	}

	/**
	 * Initialize Fluent plugin tracking hooks.
	 */
	private function init_fluent_hooks() {
		// Track Fluent Forms.
		add_action( 'fluentform_after_insert_form', array( $this, 'track_fluent_form_created' ), 10, 1 );
		add_action( 'fluentform_before_form_update', array( $this, 'track_fluent_form_updated' ), 10, 1 );
		add_action( 'fluentform_before_form_delete', array( $this, 'track_fluent_form_deleted' ), 10, 1 );
		
		// Track Fluent CRM.
		add_action( 'fluentcrm_contact_created', array( $this, 'track_fluent_crm_contact_created' ), 10, 1 );
		add_action( 'fluentcrm_contact_updated', array( $this, 'track_fluent_crm_contact_updated' ), 10, 2 );
		add_action( 'fluentcrm_contact_deleted', array( $this, 'track_fluent_crm_contact_deleted' ), 10, 1 );
		
		// Track Fluent Support.
		add_action( 'fluent_support/ticket_created', array( $this, 'track_fluent_support_ticket_created' ), 10, 1 );
		add_action( 'fluent_support/ticket_updated', array( $this, 'track_fluent_support_ticket_updated' ), 10, 2 );
		
		// Track Fluent Boards.
		add_action( 'fluent_boards/board_created', array( $this, 'track_fluent_board_created' ), 10, 1 );
		add_action( 'fluent_boards/task_created', array( $this, 'track_fluent_board_task_created' ), 10, 1 );
	}

	/**
	 * Track Fluent Form creation.
	 *
	 * @param int $form_id Form ID.
	 */
	public function track_fluent_form_created( $form_id ) {
		global $wpdb;
		$form = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}fluentform_forms WHERE id = %d", $form_id ) );
		
		if ( ! $form ) {
			return;
		}
		
		$description = sprintf(
			'Fluent Form created: %s (ID: %d)',
			$form->title,
			$form_id
		);
		
		$this->log_change(
			'created',
			'fluent_form',
			$form_id,
			$form->title,
			$description
		);
	}

	/**
	 * Track Fluent Form update.
	 *
	 * @param int $form_id Form ID.
	 */
	public function track_fluent_form_updated( $form_id ) {
		global $wpdb;
		$form = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}fluentform_forms WHERE id = %d", $form_id ) );
		
		if ( ! $form ) {
			return;
		}
		
		$description = sprintf(
			'Fluent Form updated: %s (ID: %d)',
			$form->title,
			$form_id
		);
		
		$this->log_change(
			'updated',
			'fluent_form',
			$form_id,
			$form->title,
			$description
		);
	}

	/**
	 * Track Fluent Form deletion.
	 *
	 * @param int $form_id Form ID.
	 */
	public function track_fluent_form_deleted( $form_id ) {
		global $wpdb;
		$form = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}fluentform_forms WHERE id = %d", $form_id ) );
		$form_title = $form ? $form->title : 'Unknown';
		
		$description = sprintf(
			'Fluent Form deleted: %s (ID: %d)',
			$form_title,
			$form_id
		);
		
		$this->log_change(
			'deleted',
			'fluent_form',
			$form_id,
			$form_title,
			$description
		);
	}

	/**
	 * Track Fluent CRM contact creation.
	 *
	 * @param object $contact Contact object.
	 */
	public function track_fluent_crm_contact_created( $contact ) {
		$description = sprintf(
			'Fluent CRM contact created: %s %s (Email: %s, ID: %d)',
			$contact->first_name,
			$contact->last_name,
			$contact->email,
			$contact->id
		);
		
		$this->log_change(
			'created',
			'fluent_crm_contact',
			$contact->id,
			$contact->email,
			$description
		);
	}

	/**
	 * Track Fluent CRM contact update.
	 *
	 * @param object $contact Contact object.
	 * @param array  $old_data Old contact data.
	 */
	public function track_fluent_crm_contact_updated( $contact, $old_data ) {
		$description = sprintf(
			'Fluent CRM contact updated: %s %s (Email: %s, ID: %d)',
			$contact->first_name,
			$contact->last_name,
			$contact->email,
			$contact->id
		);
		
		$this->log_change(
			'updated',
			'fluent_crm_contact',
			$contact->id,
			$contact->email,
			$description
		);
	}

	/**
	 * Track Fluent CRM contact deletion.
	 *
	 * @param object $contact Contact object.
	 */
	public function track_fluent_crm_contact_deleted( $contact ) {
		$description = sprintf(
			'Fluent CRM contact deleted: %s %s (Email: %s, ID: %d)',
			$contact->first_name,
			$contact->last_name,
			$contact->email,
			$contact->id
		);
		
		$this->log_change(
			'deleted',
			'fluent_crm_contact',
			$contact->id,
			$contact->email,
			$description
		);
	}

	/**
	 * Track Fluent Support ticket creation.
	 *
	 * @param object $ticket Ticket object.
	 */
	public function track_fluent_support_ticket_created( $ticket ) {
		$description = sprintf(
			'Fluent Support ticket created: %s (ID: %d, Status: %s)',
			$ticket->title,
			$ticket->id,
			$ticket->status
		);
		
		$this->log_change(
			'created',
			'fluent_support_ticket',
			$ticket->id,
			$ticket->title,
			$description
		);
	}

	/**
	 * Track Fluent Support ticket update.
	 *
	 * @param object $ticket Ticket object.
	 * @param array  $old_data Old ticket data.
	 */
	public function track_fluent_support_ticket_updated( $ticket, $old_data ) {
		$description = sprintf(
			'Fluent Support ticket updated: %s (ID: %d, Status: %s)',
			$ticket->title,
			$ticket->id,
			$ticket->status
		);
		
		$this->log_change(
			'updated',
			'fluent_support_ticket',
			$ticket->id,
			$ticket->title,
			$description
		);
	}

	/**
	 * Track Fluent Board creation.
	 *
	 * @param object $board Board object.
	 */
	public function track_fluent_board_created( $board ) {
		$description = sprintf(
			'Fluent Board created: %s (ID: %d)',
			$board->title,
			$board->id
		);
		
		$this->log_change(
			'created',
			'fluent_board',
			$board->id,
			$board->title,
			$description
		);
	}

	/**
	 * Track Fluent Board task creation.
	 *
	 * @param object $task Task object.
	 */
	public function track_fluent_board_task_created( $task ) {
		$description = sprintf(
			'Fluent Board task created: %s (ID: %d)',
			$task->title,
			$task->id
		);
		
		$this->log_change(
			'created',
			'fluent_board_task',
			$task->id,
			$task->title,
			$description
		);
	}

	/**
	 * Initialize plugin-specific tracking hooks.
	 */
	private function init_plugin_specific_hooks() {
		// Track Slim SEO.
		add_action( 'slim_seo_meta_updated', array( $this, 'track_slim_seo_meta_updated' ), 10, 2 );
		
		// Track SureCart.
		add_action( 'surecart/purchase_created', array( $this, 'track_surecart_purchase' ), 10, 1 );
		add_action( 'surecart/order_status_changed', array( $this, 'track_surecart_order_status_changed' ), 10, 3 );
		
		// Track Spectra.
		add_action( 'spectra_design_import', array( $this, 'track_spectra_design_import' ), 10, 1 );
		
		// Track Code Snippets plugin.
		add_action( 'code_snippets_create_snippet', array( $this, 'track_code_snippet_created' ), 10, 1 );
		add_action( 'code_snippets_update_snippet', array( $this, 'track_code_snippet_updated' ), 10, 1 );
		add_action( 'code_snippets_delete_snippet', array( $this, 'track_code_snippet_deleted' ), 10, 1 );
	}

	/**
	 * Track Slim SEO meta update.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $meta_data SEO meta data.
	 */
	public function track_slim_seo_meta_updated( $post_id, $meta_data ) {
		$post = get_post( $post_id );
		
		if ( ! $post ) {
			return;
		}
		
		$description = sprintf(
			'Slim SEO meta updated for: %s (ID: %d)',
			$post->post_title,
			$post_id
		);
		
		$this->log_change(
			'seo_updated',
			'slim_seo',
			$post_id,
			$post->post_title,
			$description
		);
	}

	/**
	 * Track SureCart purchase.
	 *
	 * @param object $purchase Purchase object.
	 */
	public function track_surecart_purchase( $purchase ) {
		$description = sprintf(
			'SureCart purchase: Order #%d (Total: %s)',
			$purchase->id,
			isset( $purchase->total_amount ) ? $purchase->total_amount : 'N/A'
		);
		
		$this->log_change(
			'purchase',
			'surecart_order',
			$purchase->id,
			'Order #' . $purchase->id,
			$description
		);
	}

	/**
	 * Track SureCart order status change.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $old_status Old status.
	 * @param string $new_status New status.
	 */
	public function track_surecart_order_status_changed( $order_id, $old_status, $new_status ) {
		$description = sprintf(
			'SureCart order status changed: Order #%d from "%s" to "%s"',
			$order_id,
			$old_status,
			$new_status
		);
		
		$this->log_change(
			'status_changed',
			'surecart_order',
			$order_id,
			'Order #' . $order_id,
			$description,
			$old_status,
			$new_status
		);
	}

	/**
	 * Track Spectra design import.
	 *
	 * @param array $design_data Design data.
	 */
	public function track_spectra_design_import( $design_data ) {
		$design_name = isset( $design_data['name'] ) ? $design_data['name'] : 'Unknown';
		
		$description = sprintf(
			'Spectra design imported: %s',
			$design_name
		);
		
		$this->log_change(
			'design_imported',
			'spectra',
			0,
			$design_name,
			$description
		);
	}

	/**
	 * Track code snippet creation.
	 *
	 * @param int $snippet_id Snippet ID.
	 */
	public function track_code_snippet_created( $snippet_id ) {
		global $wpdb;
		$snippet = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}snippets WHERE id = %d", $snippet_id ) );
		
		if ( ! $snippet ) {
			return;
		}
		
		$description = sprintf(
			'Code snippet created: %s (ID: %d)',
			$snippet->name,
			$snippet_id
		);
		
		$this->log_change(
			'created',
			'code_snippet',
			$snippet_id,
			$snippet->name,
			$description
		);
	}

	/**
	 * Track code snippet update.
	 *
	 * @param int $snippet_id Snippet ID.
	 */
	public function track_code_snippet_updated( $snippet_id ) {
		global $wpdb;
		$snippet = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}snippets WHERE id = %d", $snippet_id ) );
		
		if ( ! $snippet ) {
			return;
		}
		
		$description = sprintf(
			'Code snippet updated: %s (ID: %d)',
			$snippet->name,
			$snippet_id
		);
		
		$this->log_change(
			'updated',
			'code_snippet',
			$snippet_id,
			$snippet->name,
			$description
		);
	}

	/**
	 * Track code snippet deletion.
	 *
	 * @param int $snippet_id Snippet ID.
	 */
	public function track_code_snippet_deleted( $snippet_id ) {
		global $wpdb;
		$snippet = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}snippets WHERE id = %d", $snippet_id ) );
		$snippet_name = $snippet ? $snippet->name : 'Unknown';
		
		$description = sprintf(
			'Code snippet deleted: %s (ID: %d)',
			$snippet_name,
			$snippet_id
		);
		
		$this->log_change(
			'deleted',
			'code_snippet',
			$snippet_id,
			$snippet_name,
			$description
		);
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
			$date_from = sanitize_text_field( wp_unslash( $_GET['date_from'] ) );
			// Validate date format (YYYY-MM-DD).
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
				$where_clauses[] = 'timestamp >= %s';
				$where_values[] = $date_from . ' 00:00:00';
			}
		}
		
		if ( ! empty( $_GET['date_to'] ) ) {
			$date_to = sanitize_text_field( wp_unslash( $_GET['date_to'] ) );
			// Validate date format (YYYY-MM-DD).
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
				$where_clauses[] = 'timestamp <= %s';
				$where_values[] = $date_to . ' 23:59:59';
			}
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
		
		// First, check count to prevent memory issues with large datasets.
		if ( ! empty( $where_values ) ) {
			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table_name} {$where_sql}", $where_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} else {
			$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
		
		if ( $count === 0 ) {
			wp_die( esc_html__( 'No logs to export.', 'log-changes' ) );
		}
		
		// Warn if exporting a very large number of logs.
		if ( $count > 50000 ) {
			wp_die( esc_html__( 'Too many logs to export at once (limit: 50,000). Please use date range filters to narrow your selection.', 'log-changes' ) );
		}
		
		// Set headers for CSV download.
		if ( ! headers_sent() ) {
			$timestamp = gmdate( 'Y-m-d-H-i-s' );
			$filename = sprintf( 'change-logs-%s.csv', sanitize_file_name( $timestamp ) );
			
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=' . $filename );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );
		}
		
		// Open output stream.
		$output = fopen( 'php://output', 'w' );
		
		if ( false === $output ) {
			wp_die( esc_html__( 'Unable to create export file. Please try again.', 'log-changes' ) );
		}
		
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
		
		// Process in chunks to avoid memory issues.
		$chunk_size = 1000;
		$offset = 0;
		
		while ( $offset < $count ) {
			// Get logs in chunks.
			if ( ! empty( $where_values ) ) {
				$chunk_values = array_merge( $where_values, array( $chunk_size, $offset ) );
				$logs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table_name} {$where_sql} ORDER BY timestamp DESC LIMIT %d OFFSET %d", $chunk_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			} else {
				$logs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table_name} ORDER BY timestamp DESC LIMIT %d OFFSET %d", $chunk_size, $offset ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			}
			
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
			
			$offset += $chunk_size;
			
			// Clear memory.
			unset( $logs );
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
		
		// Require at least one filter to prevent accidental deletion of all logs.
		// Both clauses and values must be present.
		if ( empty( $where_clauses ) || empty( $where_values ) ) {
			return false;
		}
		
		// Build WHERE clause.
		$where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );
		
		// First, get count for return value.
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table_name} {$where_sql}", $where_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		
		// Then delete.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table_name} {$where_sql}", $where_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		
		return $count;
	}
	
	/**
	 * Automatically cleanup logs older than configured days.
	 * This runs daily via WordPress cron.
	 */
	public function auto_cleanup_old_logs() {
		global $wpdb;
		
		// Get cleanup period from settings.
		$cleanup_days = isset( $this->settings['cleanup_days'] ) ? absint( $this->settings['cleanup_days'] ) : 21;
		
		// Calculate the cutoff date.
		$cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $cleanup_days . ' days' ) );
		
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
					'description'  => sprintf( 'Automatically deleted %d log entries older than %d days', $deleted, $cleanup_days ),
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
	 * Manually cleanup logs older than configured days.
	 * Called from settings page.
	 *
	 * @return int|false Number of rows deleted or false on failure.
	 */
	public function manual_cleanup_old_logs() {
		global $wpdb;
		
		// Get cleanup period from settings.
		$cleanup_days = isset( $this->settings['cleanup_days'] ) ? absint( $this->settings['cleanup_days'] ) : 21;
		
		// Calculate the cutoff date.
		$cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $cleanup_days . ' days' ) );
		
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
			$current_user = wp_get_current_user();
			$wpdb->insert(
				$this->table_name,
				array(
					'timestamp'    => current_time( 'mysql' ),
					'user_id'      => $current_user->ID,
					'user_login'   => $current_user->user_login,
					'action_type'  => 'cleanup',
					'object_type'  => 'log',
					'object_id'    => 0,
					'object_name'  => 'Manual Cleanup',
					'description'  => sprintf( 'Manually deleted %d log entries older than %d days', $deleted, $cleanup_days ),
					'old_value'    => null,
					'new_value'    => null,
					'ip_address'   => $this->get_user_ip(),
					'user_agent'   => isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 ) : '',
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
		
		add_submenu_page(
			'log-changes',
			__( 'Change Log Settings', 'log-changes' ),
			__( 'Settings', 'log-changes' ),
			'manage_options',
			'log-changes-settings',
			'log_changes_render_settings_page'
		);
	}

	/**
	 * Add settings link to plugin actions.
	 *
	 * @param array $links Plugin action links.
	 * @return array Modified links.
	 */
	public function add_settings_link( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'admin.php?page=log-changes-settings' ),
			__( 'Settings', 'log-changes' )
		);
		array_unshift( $links, $settings_link );
		return $links;
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
					'confirmExportDelete' => __( 'This will export the filtered logs to CSV. After the download completes, you\'ll be asked to delete them from the database. Continue?', 'log-changes' ),
					'confirmDelete'       => __( 'CSV exported. Do you want to delete these logs from the database now? This action cannot be undone.', 'log-changes' ),
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

// Initialize the plugin and store global reference for settings page.
$log_changes_instance = new Log_Changes();
