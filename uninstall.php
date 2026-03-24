<?php
/**
 * WooSEO Optimizer Uninstall
 *
 * Removes all plugin data when the plugin is deleted via WordPress admin.
 */

// If not called by WordPress, abort
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

// Delete plugin options
delete_option( 'wseo_version' );
delete_option( 'wseo_settings' );

// Delete site options (multisite)
delete_site_option( 'wseo_version' );
delete_site_option( 'wseo_settings' );

// Delete all post meta created by plugin
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_wseo_%'" );
