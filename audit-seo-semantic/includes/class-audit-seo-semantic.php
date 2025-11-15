<?php
/**
 * Core plugin class
 */
class Audit_SEO_Semantic {

    /**
     * Plugin loader
     */
    protected $loader;

    /**
     * Plugin version
     */
    protected $version;

    /**
     * Initialize the plugin
     */
    public function __construct() {
        $this->version = AUDIT_SEO_SEMANTIC_VERSION;
        $this->load_dependencies();
        $this->define_admin_hooks();
    }

    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        // Load admin class
        require_once AUDIT_SEO_SEMANTIC_PATH . 'admin/class-admin.php';

        // Load audit modules
        require_once AUDIT_SEO_SEMANTIC_PATH . 'includes/class-technical-audit.php';
        require_once AUDIT_SEO_SEMANTIC_PATH . 'includes/class-backlink-audit.php';
        require_once AUDIT_SEO_SEMANTIC_PATH . 'includes/class-content-audit.php';

        // Load advanced features
        require_once AUDIT_SEO_SEMANTIC_PATH . 'includes/class-scheduler.php';
        require_once AUDIT_SEO_SEMANTIC_PATH . 'includes/class-link-checker.php';
        require_once AUDIT_SEO_SEMANTIC_PATH . 'includes/class-schema-builder.php';
        require_once AUDIT_SEO_SEMANTIC_PATH . 'includes/class-export.php';
        require_once AUDIT_SEO_SEMANTIC_PATH . 'includes/class-gutenberg.php';
        require_once AUDIT_SEO_SEMANTIC_PATH . 'includes/class-ai-optimizer.php';
        require_once AUDIT_SEO_SEMANTIC_PATH . 'includes/class-pagespeed.php';
        require_once AUDIT_SEO_SEMANTIC_PATH . 'includes/class-duplicate-checker.php';
        require_once AUDIT_SEO_SEMANTIC_PATH . 'includes/class-keyword-cannibalization.php';
        require_once AUDIT_SEO_SEMANTIC_PATH . 'includes/class-onpage-checker.php';
        require_once AUDIT_SEO_SEMANTIC_PATH . 'includes/class-meta-box.php';
    }

    /**
     * Register admin hooks
     */
    private function define_admin_hooks() {
        $admin = new Audit_SEO_Semantic_Admin($this->version);

        add_action('admin_menu', array($admin, 'add_menu_pages'));
        add_action('admin_enqueue_scripts', array($admin, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($admin, 'enqueue_scripts'));

        // AJAX handlers
        add_action('wp_ajax_run_technical_audit', array($admin, 'ajax_run_technical_audit'));
        add_action('wp_ajax_run_backlink_audit', array($admin, 'ajax_run_backlink_audit'));
        add_action('wp_ajax_run_content_audit', array($admin, 'ajax_run_content_audit'));
        add_action('wp_ajax_save_backlink', array($admin, 'ajax_save_backlink'));
        add_action('wp_ajax_delete_backlink', array($admin, 'ajax_delete_backlink'));
        add_action('wp_ajax_audit_seo_quick_analyze', array($admin, 'ajax_quick_analyze'));
        add_action('wp_ajax_audit_seo_recheck_issues', array($admin, 'ajax_recheck_seo_issues'));
        add_action('wp_ajax_audit_seo_refresh_checklist', array($admin, 'ajax_refresh_checklist'));
    }

    /**
     * Run the plugin
     */
    public function run() {
        // Plugin is initialized through hooks
    }

    /**
     * Get plugin version
     */
    public function get_version() {
        return $this->version;
    }
}
