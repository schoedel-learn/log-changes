<?php
/**
 * Uninstall script for Log Changes plugin.
 *
 * This file is executed when the plugin is deleted from WordPress.
 * It removes all plugin data from the database.
 *
 * @package LogChanges
 */

// Exit if not called from WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Delete the change log table.
// Table name is safely constructed: wpdb->prefix (validated by WordPress) + hardcoded string.
$table_name = $wpdb->prefix . 'change_log';
// Use esc_sql for identifier escaping (table names cannot use wpdb->prepare()).
$wpdb->query( 'DROP TABLE IF EXISTS `' . esc_sql( $table_name ) . '`' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

// Delete plugin options.
delete_option( 'log_changes_version' );

// For multisite installations, delete options from all sites.
if ( is_multisite() ) {
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );

	foreach ( $blog_ids as $blog_id ) {
		switch_to_blog( $blog_id );

		// Table name is safely reconstructed for each blog.
		$table_name = $wpdb->prefix . 'change_log';
		$wpdb->query( 'DROP TABLE IF EXISTS `' . esc_sql( $table_name ) . '`' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		delete_option( 'log_changes_version' );

		restore_current_blog();
	}
}
