<?php
/**
 * Admin functionality
 */
class Audit_SEO_Semantic_Admin {

    private $version;

    /**
     * Constructor
     */
    public function __construct($version) {
        $this->version = $version;
    }

    /**
     * Add admin menu pages
     */
    public function add_menu_pages() {
        add_menu_page(
            'Audit SEO Semantic',
            'SEO Audit',
            'manage_options',
            'audit-seo-semantic',
            array($this, 'display_dashboard'),
            'dashicons-search',
            30
        );

        add_submenu_page(
            'audit-seo-semantic',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'audit-seo-semantic',
            array($this, 'display_dashboard')
        );

        add_submenu_page(
            'audit-seo-semantic',
            'Technical Audit',
            'Technical Audit',
            'manage_options',
            'audit-seo-technical',
            array($this, 'display_technical_audit')
        );

        add_submenu_page(
            'audit-seo-semantic',
            'Backlink Audit',
            'Backlink Audit',
            'manage_options',
            'audit-seo-backlinks',
            array($this, 'display_backlink_audit')
        );

        add_submenu_page(
            'audit-seo-semantic',
            'Content Audit',
            'Content Audit',
            'manage_options',
            'audit-seo-content',
            array($this, 'display_content_audit')
        );

        add_submenu_page(
            'audit-seo-semantic',
            'Settings',
            'Settings',
            'manage_options',
            'audit-seo-settings',
            array($this, 'display_settings')
        );
    }

    /**
     * Enqueue admin styles
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'audit-seo-semantic-admin',
            AUDIT_SEO_SEMANTIC_URL . 'assets/css/admin.css',
            array(),
            $this->version
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'audit-seo-semantic-admin',
            AUDIT_SEO_SEMANTIC_URL . 'assets/js/admin.js',
            array('jquery'),
            $this->version,
            true
        );

        wp_localize_script('audit-seo-semantic-admin', 'auditSeoAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('audit_seo_nonce')
        ));
    }

    /**
     * Display dashboard page
     */
    public function display_dashboard() {
        include AUDIT_SEO_SEMANTIC_PATH . 'admin/views/dashboard.php';
    }

    /**
     * Display technical audit page
     */
    public function display_technical_audit() {
        include AUDIT_SEO_SEMANTIC_PATH . 'admin/views/technical-audit.php';
    }

    /**
     * Display backlink audit page
     */
    public function display_backlink_audit() {
        include AUDIT_SEO_SEMANTIC_PATH . 'admin/views/backlink-audit.php';
    }

    /**
     * Display content audit page
     */
    public function display_content_audit() {
        include AUDIT_SEO_SEMANTIC_PATH . 'admin/views/content-audit.php';
    }

    /**
     * Display settings page
     */
    public function display_settings() {
        // Save settings
        if (isset($_POST['audit_seo_save_settings'])) {
            check_admin_referer('audit_seo_settings');

            $settings = array(
                'enable_technical_audit' => isset($_POST['enable_technical_audit']),
                'enable_backlink_audit' => isset($_POST['enable_backlink_audit']),
                'enable_content_audit' => isset($_POST['enable_content_audit']),
                'auto_audit_on_publish' => isset($_POST['auto_audit_on_publish']),
                'min_content_length' => intval($_POST['min_content_length']),
                'max_keyword_density' => floatval($_POST['max_keyword_density'])
            );

            update_option('audit_seo_semantic_settings', $settings);
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        }

        include AUDIT_SEO_SEMANTIC_PATH . 'admin/views/settings.php';
    }

    /**
     * AJAX: Run technical audit
     */
    public function ajax_run_technical_audit() {
        check_ajax_referer('audit_seo_nonce', 'nonce');

        $post_id = intval($_POST['post_id']);
        $results = Audit_SEO_Technical_Audit::run_audit($post_id);

        // Save to history
        $this->save_audit_history($post_id, 'technical', $results);

        wp_send_json_success($results);
    }

    /**
     * AJAX: Run backlink audit
     */
    public function ajax_run_backlink_audit() {
        check_ajax_referer('audit_seo_nonce', 'nonce');

        $results = Audit_SEO_Backlink_Audit::run_audit();

        wp_send_json_success($results);
    }

    /**
     * AJAX: Run content audit
     */
    public function ajax_run_content_audit() {
        check_ajax_referer('audit_seo_nonce', 'nonce');

        $post_id = intval($_POST['post_id']);
        $focus_keyword = sanitize_text_field($_POST['focus_keyword']);

        $results = Audit_SEO_Content_Audit::run_audit($post_id, $focus_keyword);

        // Save to history
        $this->save_audit_history($post_id, 'content', $results);

        wp_send_json_success($results);
    }

    /**
     * AJAX: Save backlink
     */
    public function ajax_save_backlink() {
        check_ajax_referer('audit_seo_nonce', 'nonce');

        $data = array(
            'url' => esc_url_raw($_POST['url']),
            'source_url' => esc_url_raw($_POST['source_url']),
            'anchor_text' => sanitize_text_field($_POST['anchor_text']),
            'link_type' => sanitize_text_field($_POST['link_type']),
            'domain_authority' => intval($_POST['domain_authority']),
            'page_authority' => intval($_POST['page_authority']),
            'spam_score' => intval($_POST['spam_score']),
            'status' => sanitize_text_field($_POST['status'])
        );

        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $result = Audit_SEO_Backlink_Audit::update_backlink(intval($_POST['id']), $data);
        } else {
            $result = Audit_SEO_Backlink_Audit::add_backlink($data);
        }

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array('id' => $result));
    }

    /**
     * AJAX: Delete backlink
     */
    public function ajax_delete_backlink() {
        check_ajax_referer('audit_seo_nonce', 'nonce');

        $id = intval($_POST['id']);
        $result = Audit_SEO_Backlink_Audit::delete_backlink($id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success();
    }

    /**
     * AJAX: Quick analyze from meta box
     */
    public function ajax_quick_analyze() {
        check_ajax_referer('audit_seo_nonce', 'nonce');

        $title = sanitize_text_field($_POST['title']);
        $content = wp_kses_post($_POST['content']);
        $focus_keyword = sanitize_text_field($_POST['focus_keyword']);

        // Use Gutenberg's analyze method
        $analysis = Audit_SEO_Gutenberg::analyze_content_raw($title, $content, $focus_keyword);

        wp_send_json_success($analysis);
    }

    /**
     * Save audit history
     */
    private function save_audit_history($post_id, $audit_type, $results) {
        global $wpdb;
        $table = $wpdb->prefix . 'audit_seo_history';

        $wpdb->insert($table, array(
            'post_id' => $post_id,
            'audit_type' => $audit_type,
            'score' => $results['score'],
            'issues' => maybe_serialize($results['issues']),
            'recommendations' => maybe_serialize($results['passed'])
        ));
    }
}
