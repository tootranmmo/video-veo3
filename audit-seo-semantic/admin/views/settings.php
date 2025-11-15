<?php
/**
 * Settings view
 */
if (!defined('ABSPATH')) exit;

$settings = get_option('audit_seo_semantic_settings', array(
    'enable_technical_audit' => true,
    'enable_backlink_audit' => true,
    'enable_content_audit' => true,
    'auto_audit_on_publish' => false,
    'min_content_length' => 300,
    'max_keyword_density' => 3.5
));
?>

<div class="wrap audit-seo-settings">
    <h1>Audit SEO Semantic Settings</h1>

    <form method="post" action="">
        <?php wp_nonce_field('audit_seo_settings'); ?>

        <h2>Audit Features</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Enable Technical Audit</th>
                <td>
                    <label>
                        <input type="checkbox" name="enable_technical_audit" value="1" <?php checked($settings['enable_technical_audit'], true); ?>>
                        Enable technical SEO audit features
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">Enable Backlink Audit</th>
                <td>
                    <label>
                        <input type="checkbox" name="enable_backlink_audit" value="1" <?php checked($settings['enable_backlink_audit'], true); ?>>
                        Enable backlink audit and management
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">Enable Content Audit</th>
                <td>
                    <label>
                        <input type="checkbox" name="enable_content_audit" value="1" <?php checked($settings['enable_content_audit'], true); ?>>
                        Enable content SEO audit features
                    </label>
                </td>
            </tr>
        </table>

        <h2>Automation Settings</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Auto Audit on Publish</th>
                <td>
                    <label>
                        <input type="checkbox" name="auto_audit_on_publish" value="1" <?php checked($settings['auto_audit_on_publish'], true); ?>>
                        Automatically run audit when publishing posts/pages
                    </label>
                </td>
            </tr>
        </table>

        <h2>Content Audit Settings</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Minimum Content Length</th>
                <td>
                    <input type="number" name="min_content_length" value="<?php echo esc_attr($settings['min_content_length']); ?>" min="0" step="50">
                    <p class="description">Minimum word count for content (default: 300)</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Maximum Keyword Density</th>
                <td>
                    <input type="number" name="max_keyword_density" value="<?php echo esc_attr($settings['max_keyword_density']); ?>" min="0" max="10" step="0.1">
                    <p class="description">Maximum keyword density percentage (default: 3.5%)</p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="audit_seo_save_settings" class="button button-primary" value="Save Settings">
        </p>
    </form>

    <hr>

    <h2>Database Information</h2>
    <?php
    global $wpdb;
    $backlinks_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}audit_seo_backlinks");
    $history_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}audit_seo_history");
    ?>
    <table class="form-table">
        <tr>
            <th scope="row">Total Backlinks:</th>
            <td><?php echo $backlinks_count; ?></td>
        </tr>
        <tr>
            <th scope="row">Total Audit History:</th>
            <td><?php echo $history_count; ?></td>
        </tr>
        <tr>
            <th scope="row">Plugin Version:</th>
            <td><?php echo AUDIT_SEO_SEMANTIC_VERSION; ?></td>
        </tr>
    </table>
</div>
