<?php
/**
 * Fired when the plugin is uninstalled
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete custom tables
$table_backlinks = $wpdb->prefix . 'audit_seo_backlinks';
$table_history = $wpdb->prefix . 'audit_seo_history';

$wpdb->query("DROP TABLE IF EXISTS $table_backlinks");
$wpdb->query("DROP TABLE IF EXISTS $table_history");

// Delete options
delete_option('audit_seo_semantic_version');
delete_option('audit_seo_semantic_settings');

// Clear any scheduled hooks
wp_clear_scheduled_hook('audit_seo_semantic_daily_check');

// Delete post meta (if any were added)
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'audit_seo_%'");
