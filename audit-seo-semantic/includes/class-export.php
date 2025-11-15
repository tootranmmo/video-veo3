<?php
/**
 * Export Reports (CSV, JSON, PDF-ready HTML)
 */
class Audit_SEO_Export {

    /**
     * Export to CSV
     */
    public static function export_to_csv($data, $filename = 'seo-audit-report') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename . '-' . date('Y-m-d') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Export based on data type
        if (isset($data['audit_type'])) {
            self::export_audit_to_csv($output, $data);
        } elseif (isset($data['backlinks'])) {
            self::export_backlinks_to_csv($output, $data['backlinks']);
        }

        fclose($output);
        exit;
    }

    /**
     * Export audit results to CSV
     */
    private static function export_audit_to_csv($output, $data) {
        // Header
        fputcsv($output, array('SEO Audit Report'));
        fputcsv($output, array('Generated', date('Y-m-d H:i:s')));
        fputcsv($output, array('Post/Page', $data['post_title']));
        fputcsv($output, array('Audit Type', ucfirst($data['audit_type'])));
        fputcsv($output, array('Score', $data['score'] . '%'));
        fputcsv($output, array());

        // Passed checks
        if (!empty($data['passed'])) {
            fputcsv($output, array('Passed Checks'));
            fputcsv($output, array('Status', 'Check'));
            foreach ($data['passed'] as $item) {
                fputcsv($output, array('✓', $item));
            }
            fputcsv($output, array());
        }

        // Issues
        if (!empty($data['issues'])) {
            fputcsv($output, array('Issues Found'));
            fputcsv($output, array('Status', 'Issue'));
            foreach ($data['issues'] as $item) {
                fputcsv($output, array('✗', $item));
            }
            fputcsv($output, array());
        }

        // Warnings
        if (!empty($data['warnings'])) {
            fputcsv($output, array('Warnings'));
            fputcsv($output, array('Status', 'Warning'));
            foreach ($data['warnings'] as $item) {
                fputcsv($output, array('⚠', $item));
            }
            fputcsv($output, array());
        }

        // Statistics
        if (!empty($data['stats'])) {
            fputcsv($output, array('Statistics'));
            fputcsv($output, array('Metric', 'Value'));
            foreach ($data['stats'] as $key => $value) {
                fputcsv($output, array(ucwords(str_replace('_', ' ', $key)), $value));
            }
        }
    }

    /**
     * Export backlinks to CSV
     */
    private static function export_backlinks_to_csv($output, $backlinks) {
        // Header
        fputcsv($output, array('ID', 'Source URL', 'Target URL', 'Anchor Text', 'Type', 'DA', 'PA', 'Spam Score', 'Status', 'Date'));

        foreach ($backlinks as $link) {
            fputcsv($output, array(
                $link->id,
                $link->source_url,
                $link->url,
                $link->anchor_text,
                $link->link_type,
                $link->domain_authority,
                $link->page_authority,
                $link->spam_score,
                $link->status,
                $link->created_at
            ));
        }
    }

    /**
     * Export to JSON
     */
    public static function export_to_json($data, $filename = 'seo-audit-report') {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename . '-' . date('Y-m-d') . '.json');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Generate printable HTML report
     */
    public static function generate_html_report($data) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>SEO Audit Report - <?php echo esc_html($data['post_title']); ?></title>
            <style>
                @media print {
                    .no-print { display: none; }
                }
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 1200px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 30px;
                    border-radius: 10px;
                    margin-bottom: 30px;
                }
                .header h1 {
                    margin: 0 0 10px 0;
                    font-size: 32px;
                }
                .score-container {
                    text-align: center;
                    margin: 30px 0;
                }
                .score-circle {
                    display: inline-block;
                    width: 200px;
                    height: 200px;
                    border-radius: 50%;
                    border: 15px solid #ddd;
                    position: relative;
                    margin: 20px;
                }
                .score-circle.good { border-color: #4caf50; }
                .score-circle.medium { border-color: #ff9800; }
                .score-circle.bad { border-color: #f44336; }
                .score-value {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    font-size: 64px;
                    font-weight: bold;
                }
                .score-circle.good .score-value { color: #4caf50; }
                .score-circle.medium .score-value { color: #ff9800; }
                .score-circle.bad .score-value { color: #f44336; }
                .section {
                    background: white;
                    padding: 25px;
                    margin-bottom: 20px;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                .section h2 {
                    margin-top: 0;
                    padding-bottom: 10px;
                    border-bottom: 3px solid #f0f0f0;
                }
                .check-list {
                    list-style: none;
                    padding: 0;
                }
                .check-list li {
                    padding: 12px;
                    margin: 8px 0;
                    border-radius: 5px;
                }
                .check-list .passed {
                    background: #e8f5e9;
                    border-left: 4px solid #4caf50;
                }
                .check-list .issue {
                    background: #ffebee;
                    border-left: 4px solid #f44336;
                }
                .check-list .warning {
                    background: #fff3e0;
                    border-left: 4px solid #ff9800;
                }
                .stats-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 15px;
                    margin-top: 20px;
                }
                .stat-card {
                    background: #f9f9f9;
                    padding: 20px;
                    border-radius: 8px;
                    text-align: center;
                }
                .stat-value {
                    font-size: 32px;
                    font-weight: bold;
                    color: #2271b1;
                    display: block;
                }
                .stat-label {
                    color: #666;
                    font-size: 14px;
                }
                .footer {
                    text-align: center;
                    padding: 20px;
                    color: #666;
                    font-size: 12px;
                    border-top: 1px solid #ddd;
                    margin-top: 40px;
                }
                @page {
                    margin: 2cm;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>SEO Audit Report</h1>
                <p><strong>Page/Post:</strong> <?php echo esc_html($data['post_title']); ?></p>
                <p><strong>URL:</strong> <?php echo esc_url($data['url']); ?></p>
                <p><strong>Generated:</strong> <?php echo date('F j, Y g:i A'); ?></p>
                <p><strong>Audit Type:</strong> <?php echo ucfirst($data['audit_type']); ?></p>
            </div>

            <div class="score-container">
                <div class="score-circle <?php echo $data['score'] >= 80 ? 'good' : ($data['score'] >= 60 ? 'medium' : 'bad'); ?>">
                    <span class="score-value"><?php echo $data['score']; ?></span>
                </div>
                <h2>Overall Score: <?php echo $data['score']; ?>%</h2>
            </div>

            <?php if (!empty($data['stats'])): ?>
            <div class="section">
                <h2>Statistics</h2>
                <div class="stats-grid">
                    <?php foreach ($data['stats'] as $key => $value): ?>
                        <div class="stat-card">
                            <span class="stat-value"><?php echo esc_html($value); ?></span>
                            <span class="stat-label"><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($data['passed'])): ?>
            <div class="section">
                <h2>✓ Passed Checks (<?php echo count($data['passed']); ?>)</h2>
                <ul class="check-list">
                    <?php foreach ($data['passed'] as $item): ?>
                        <li class="passed">✓ <?php echo esc_html($item); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($data['issues'])): ?>
            <div class="section">
                <h2>✗ Issues Found (<?php echo count($data['issues']); ?>)</h2>
                <ul class="check-list">
                    <?php foreach ($data['issues'] as $item): ?>
                        <li class="issue">✗ <?php echo esc_html($item); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($data['warnings'])): ?>
            <div class="section">
                <h2>⚠ Warnings (<?php echo count($data['warnings']); ?>)</h2>
                <ul class="check-list">
                    <?php foreach ($data['warnings'] as $item): ?>
                        <li class="warning">⚠ <?php echo esc_html($item); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="footer">
                <p><strong>Generated by Audit SEO Semantic</strong></p>
                <p><?php echo get_bloginfo('name'); ?> - <?php echo home_url(); ?></p>
                <p class="no-print">
                    <button onclick="window.print()" style="padding: 10px 20px; background: #2271b1; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">
                        Print Report
                    </button>
                </p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Export printable HTML
     */
    public static function export_to_html($data, $filename = 'seo-audit-report') {
        $html = self::generate_html_report($data);

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename=' . $filename . '-' . date('Y-m-d') . '.html');

        echo $html;
        exit;
    }

    /**
     * Prepare audit data for export
     */
    public static function prepare_audit_data($post_id, $audit_results) {
        $post = get_post($post_id);

        return array(
            'post_id' => $post_id,
            'post_title' => get_the_title($post_id),
            'url' => get_permalink($post_id),
            'audit_type' => $audit_results['audit_type'] ?? 'general',
            'score' => $audit_results['score'] ?? 0,
            'passed' => $audit_results['passed'] ?? array(),
            'issues' => $audit_results['issues'] ?? array(),
            'warnings' => $audit_results['warnings'] ?? array(),
            'stats' => $audit_results['stats'] ?? array(),
            'generated_at' => current_time('mysql')
        );
    }

    /**
     * Export backlinks
     */
    public static function export_backlinks($format = 'csv') {
        $backlinks = Audit_SEO_Backlink_Audit::get_backlinks(array('limit' => 1000));

        if ($format === 'csv') {
            self::export_to_csv(array('backlinks' => $backlinks), 'backlinks-export');
        } elseif ($format === 'json') {
            self::export_to_json($backlinks, 'backlinks-export');
        }
    }
}
