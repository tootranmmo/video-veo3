<?php
/**
 * Fired during plugin deactivation
 */
class Audit_SEO_Semantic_Deactivator {

    /**
     * Deactivate plugin
     */
    public static function deactivate() {
        // Clear scheduled events if any
        wp_clear_scheduled_hook('audit_seo_semantic_daily_check');
    }
}
