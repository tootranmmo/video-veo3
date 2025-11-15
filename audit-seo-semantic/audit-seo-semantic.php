<?php
/**
 * Plugin Name: Audit SEO Semantic
 * Plugin URI: https://example.com/audit-seo-semantic
 * Description: Comprehensive SEO audit tool with Technical Audit, Backlink Audit, and Content Audit features
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: audit-seo-semantic
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Plugin version
define('AUDIT_SEO_SEMANTIC_VERSION', '1.0.0');

// Plugin path
define('AUDIT_SEO_SEMANTIC_PATH', plugin_dir_path(__FILE__));

// Plugin URL
define('AUDIT_SEO_SEMANTIC_URL', plugin_dir_url(__FILE__));

/**
 * Activation hook
 */
function activate_audit_seo_semantic() {
    require_once AUDIT_SEO_SEMANTIC_PATH . 'includes/class-activator.php';
    Audit_SEO_Semantic_Activator::activate();
}
register_activation_hook(__FILE__, 'activate_audit_seo_semantic');

/**
 * Deactivation hook
 */
function deactivate_audit_seo_semantic() {
    require_once AUDIT_SEO_SEMANTIC_PATH . 'includes/class-deactivator.php';
    Audit_SEO_Semantic_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'deactivate_audit_seo_semantic');

/**
 * Core plugin class
 */
require_once AUDIT_SEO_SEMANTIC_PATH . 'includes/class-audit-seo-semantic.php';

/**
 * Initialize the plugin
 */
function run_audit_seo_semantic() {
    $plugin = new Audit_SEO_Semantic();
    $plugin->run();
}
run_audit_seo_semantic();
