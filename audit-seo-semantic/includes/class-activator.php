<?php
/**
 * Fired during plugin activation
 */
class Audit_SEO_Semantic_Activator {

    /**
     * Activate plugin
     */
    public static function activate() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Create backlinks table
        $table_backlinks = $wpdb->prefix . 'audit_seo_backlinks';
        $sql_backlinks = "CREATE TABLE IF NOT EXISTS $table_backlinks (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            url varchar(255) NOT NULL,
            source_url varchar(255) NOT NULL,
            anchor_text varchar(255) DEFAULT NULL,
            link_type varchar(20) DEFAULT 'dofollow',
            domain_authority int(3) DEFAULT 0,
            page_authority int(3) DEFAULT 0,
            spam_score int(3) DEFAULT 0,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY url (url),
            KEY source_url (source_url)
        ) $charset_collate;";

        // Create audit history table
        $table_history = $wpdb->prefix . 'audit_seo_history';
        $sql_history = "CREATE TABLE IF NOT EXISTS $table_history (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            audit_type varchar(50) NOT NULL,
            score int(3) DEFAULT 0,
            issues longtext,
            recommendations longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY audit_type (audit_type)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_backlinks);
        dbDelta($sql_history);

        // Set default options
        add_option('audit_seo_semantic_version', AUDIT_SEO_SEMANTIC_VERSION);
        add_option('audit_seo_semantic_settings', array(
            'enable_technical_audit' => true,
            'enable_backlink_audit' => true,
            'enable_content_audit' => true,
            'auto_audit_on_publish' => false,
            'min_content_length' => 300,
            'max_keyword_density' => 3.5
        ));
    }
}
