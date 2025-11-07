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
		
		// If no user is logged in, check if it's a cron job or automated action.
		if ( ! $user_id ) {
			if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
				$user_login = 'wp-cron';
			} elseif ( defined( 'WP_CLI' ) && WP_CLI ) {
				$user_login = 'wp-cli';
			} else {
				$user_login = 'system';
			}
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
	 * Get user IP address.
	 *
	 * @return string IP address.
	 */
	private function get_user_ip() {
		$ip_keys = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR' );
		
		foreach ( $ip_keys as $key ) {
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
		// Skip transients.
		if ( strpos( $option, '_transient' ) === 0 || strpos( $option, '_site_transient' ) === 0 ) {
			return true;
		}
		
		// Skip internal WordPress options that change frequently.
		$skip_options = array(
			'cron',
			'_site_transient_timeout',
			'_site_transient',
			'_transient_timeout',
			'_transient',
			'doing_cron',
		);
		
		foreach ( $skip_options as $skip ) {
			if ( strpos( $option, $skip ) !== false ) {
				return true;
			}
		}
		
		return false;
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
		
		wp_enqueue_style(
			'log-changes-admin',
			LOG_CHANGES_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			LOG_CHANGES_VERSION
		);
		
		wp_enqueue_script(
			'log-changes-admin',
			LOG_CHANGES_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			LOG_CHANGES_VERSION,
			true
		);
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
		include LOG_CHANGES_PLUGIN_DIR . 'includes/admin-page.php';
	}
}

// Initialize the plugin.
new Log_Changes();
