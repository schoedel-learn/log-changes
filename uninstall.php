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
// Table name is constructed from wpdb prefix + hardcoded string, safe from injection.
$table_name = $wpdb->prefix . 'change_log';
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS `%s`', $table_name ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder

// Delete plugin options.
delete_option( 'log_changes_version' );

// For multisite installations, delete options from all sites.
if ( is_multisite() ) {
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
	
	foreach ( $blog_ids as $blog_id ) {
		switch_to_blog( $blog_id );
		
		// Table name is reconstructed for each blog with safe wpdb prefix.
		$table_name = $wpdb->prefix . 'change_log';
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS `%s`', $table_name ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
		
		delete_option( 'log_changes_version' );
		
		restore_current_blog();
	}
}
