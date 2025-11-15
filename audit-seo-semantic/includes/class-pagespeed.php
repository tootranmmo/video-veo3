<?php
/**
 * Google PageSpeed Insights Integration
 */
class Audit_SEO_PageSpeed {

    /**
     * API endpoint
     */
    private static $api_endpoint = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

    /**
     * Run PageSpeed test
     */
    public static function run_test($url, $strategy = 'mobile') {
        $settings = get_option('audit_seo_semantic_settings', array());
        $api_key = isset($settings['pagespeed_api_key']) ? $settings['pagespeed_api_key'] : '';

        // Build API URL
        $api_url = add_query_arg(array(
            'url' => urlencode($url),
            'strategy' => $strategy,
            'category' => 'performance,accessibility,best-practices,seo',
            'key' => $api_key
        ), self::$api_endpoint);

        // Make API request
        $response = wp_remote_get($api_url, array(
            'timeout' => 60,
            'sslverify' => true
        ));

        if (is_wp_error($response)) {
            return array(
                'error' => $response->get_error_message()
            );
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data || isset($data['error'])) {
            return array(
                'error' => isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error'
            );
        }

        // Parse results
        $results = self::parse_results($data);

        // Save results
        self::save_results($url, $strategy, $results);

        return $results;
    }

    /**
     * Parse PageSpeed results
     */
    private static function parse_results($data) {
        $results = array(
            'scores' => array(),
            'metrics' => array(),
            'opportunities' => array(),
            'diagnostics' => array(),
            'core_web_vitals' => array(),
            'screenshot' => null,
            'tested_at' => current_time('mysql')
        );

        // Extract scores
        if (isset($data['lighthouseResult']['categories'])) {
            foreach ($data['lighthouseResult']['categories'] as $category => $cat_data) {
                $results['scores'][$category] = array(
                    'score' => round($cat_data['score'] * 100),
                    'title' => $cat_data['title']
                );
            }
        }

        // Extract metrics
        if (isset($data['lighthouseResult']['audits'])) {
            $audits = $data['lighthouseResult']['audits'];

            // Core Web Vitals
            if (isset($audits['largest-contentful-paint'])) {
                $results['core_web_vitals']['lcp'] = array(
                    'value' => $audits['largest-contentful-paint']['displayValue'] ?? 'N/A',
                    'score' => isset($audits['largest-contentful-paint']['score']) ? round($audits['largest-contentful-paint']['score'] * 100) : 0,
                    'numeric' => $audits['largest-contentful-paint']['numericValue'] ?? 0
                );
            }

            if (isset($audits['cumulative-layout-shift'])) {
                $results['core_web_vitals']['cls'] = array(
                    'value' => $audits['cumulative-layout-shift']['displayValue'] ?? 'N/A',
                    'score' => isset($audits['cumulative-layout-shift']['score']) ? round($audits['cumulative-layout-shift']['score'] * 100) : 0,
                    'numeric' => $audits['cumulative-layout-shift']['numericValue'] ?? 0
                );
            }

            if (isset($audits['total-blocking-time'])) {
                $results['core_web_vitals']['tbt'] = array(
                    'value' => $audits['total-blocking-time']['displayValue'] ?? 'N/A',
                    'score' => isset($audits['total-blocking-time']['score']) ? round($audits['total-blocking-time']['score'] * 100) : 0,
                    'numeric' => $audits['total-blocking-time']['numericValue'] ?? 0
                );
            }

            // Other important metrics
            $metric_keys = array(
                'first-contentful-paint' => 'FCP',
                'speed-index' => 'Speed Index',
                'time-to-interactive' => 'TTI',
                'total-blocking-time' => 'TBT',
                'cumulative-layout-shift' => 'CLS'
            );

            foreach ($metric_keys as $key => $label) {
                if (isset($audits[$key])) {
                    $results['metrics'][$key] = array(
                        'label' => $label,
                        'value' => $audits[$key]['displayValue'] ?? 'N/A',
                        'score' => isset($audits[$key]['score']) ? round($audits[$key]['score'] * 100) : 0
                    );
                }
            }

            // Opportunities (things that can save time)
            foreach ($audits as $audit_id => $audit) {
                if (isset($audit['details']) && isset($audit['details']['type']) && $audit['details']['type'] === 'opportunity') {
                    $results['opportunities'][] = array(
                        'title' => $audit['title'],
                        'description' => $audit['description'],
                        'savings' => $audit['displayValue'] ?? '',
                        'score' => isset($audit['score']) ? round($audit['score'] * 100) : 0
                    );
                }
            }

            // Diagnostics
            $diagnostic_keys = array(
                'uses-text-compression',
                'uses-responsive-images',
                'uses-optimized-images',
                'modern-image-formats',
                'efficient-animated-content',
                'dom-size',
                'critical-request-chains'
            );

            foreach ($diagnostic_keys as $key) {
                if (isset($audits[$key])) {
                    $results['diagnostics'][] = array(
                        'title' => $audits[$key]['title'],
                        'description' => $audits[$key]['description'] ?? '',
                        'score' => isset($audits[$key]['score']) ? round($audits[$key]['score'] * 100) : 0
                    );
                }
            }
        }

        // Screenshot
        if (isset($data['lighthouseResult']['audits']['final-screenshot']['details']['data'])) {
            $results['screenshot'] = $data['lighthouseResult']['audits']['final-screenshot']['details']['data'];
        }

        return $results;
    }

    /**
     * Save PageSpeed results
     */
    private static function save_results($url, $strategy, $results) {
        global $wpdb;

        $table = $wpdb->prefix . 'audit_seo_pagespeed';

        // Create table if not exists
        self::maybe_create_table();

        $wpdb->insert($table, array(
            'url' => $url,
            'strategy' => $strategy,
            'performance_score' => $results['scores']['performance']['score'] ?? 0,
            'accessibility_score' => $results['scores']['accessibility']['score'] ?? 0,
            'best_practices_score' => $results['scores']['best-practices']['score'] ?? 0,
            'seo_score' => $results['scores']['seo']['score'] ?? 0,
            'lcp_value' => $results['core_web_vitals']['lcp']['numeric'] ?? 0,
            'cls_value' => $results['core_web_vitals']['cls']['numeric'] ?? 0,
            'tbt_value' => $results['core_web_vitals']['tbt']['numeric'] ?? 0,
            'full_results' => maybe_serialize($results),
            'tested_at' => current_time('mysql')
        ));

        return $wpdb->insert_id;
    }

    /**
     * Get saved results
     */
    public static function get_results($url, $strategy = 'mobile', $limit = 10) {
        global $wpdb;

        $table = $wpdb->prefix . 'audit_seo_pagespeed';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table
             WHERE url = %s AND strategy = %s
             ORDER BY tested_at DESC
             LIMIT %d",
            $url,
            $strategy,
            $limit
        ));

        // Unserialize full results
        foreach ($results as &$result) {
            if (isset($result->full_results)) {
                $result->full_results = maybe_unserialize($result->full_results);
            }
        }

        return $results;
    }

    /**
     * Get historical data
     */
    public static function get_historical_data($url, $strategy = 'mobile', $days = 30) {
        global $wpdb;

        $table = $wpdb->prefix . 'audit_seo_pagespeed';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT
                DATE(tested_at) as date,
                AVG(performance_score) as avg_performance,
                AVG(lcp_value) as avg_lcp,
                AVG(cls_value) as avg_cls,
                AVG(tbt_value) as avg_tbt
             FROM $table
             WHERE url = %s
                 AND strategy = %s
                 AND tested_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY DATE(tested_at)
             ORDER BY date ASC",
            $url,
            $strategy,
            $days
        ));

        return $results;
    }

    /**
     * Create database table
     */
    public static function maybe_create_table() {
        global $wpdb;

        $table = $wpdb->prefix . 'audit_seo_pagespeed';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            url varchar(255) NOT NULL,
            strategy varchar(20) NOT NULL DEFAULT 'mobile',
            performance_score int(3) DEFAULT 0,
            accessibility_score int(3) DEFAULT 0,
            best_practices_score int(3) DEFAULT 0,
            seo_score int(3) DEFAULT 0,
            lcp_value decimal(10,2) DEFAULT 0,
            cls_value decimal(10,4) DEFAULT 0,
            tbt_value decimal(10,2) DEFAULT 0,
            full_results longtext,
            tested_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY url (url),
            KEY strategy (strategy),
            KEY tested_at (tested_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Get Core Web Vitals assessment
     */
    public static function assess_core_web_vitals($results) {
        if (!isset($results['core_web_vitals'])) {
            return array('status' => 'unknown', 'message' => 'No Core Web Vitals data available');
        }

        $cwv = $results['core_web_vitals'];
        $passed = 0;
        $total = 0;

        $assessments = array();

        // LCP assessment (should be < 2.5s)
        if (isset($cwv['lcp'])) {
            $total++;
            $lcp_seconds = $cwv['lcp']['numeric'] / 1000;
            if ($lcp_seconds < 2.5) {
                $passed++;
                $assessments['lcp'] = array('status' => 'good', 'message' => 'LCP is good');
            } elseif ($lcp_seconds < 4.0) {
                $assessments['lcp'] = array('status' => 'needs_improvement', 'message' => 'LCP needs improvement');
            } else {
                $assessments['lcp'] = array('status' => 'poor', 'message' => 'LCP is poor');
            }
        }

        // CLS assessment (should be < 0.1)
        if (isset($cwv['cls'])) {
            $total++;
            $cls_value = $cwv['cls']['numeric'];
            if ($cls_value < 0.1) {
                $passed++;
                $assessments['cls'] = array('status' => 'good', 'message' => 'CLS is good');
            } elseif ($cls_value < 0.25) {
                $assessments['cls'] = array('status' => 'needs_improvement', 'message' => 'CLS needs improvement');
            } else {
                $assessments['cls'] = array('status' => 'poor', 'message' => 'CLS is poor');
            }
        }

        // TBT assessment (should be < 200ms for FID proxy)
        if (isset($cwv['tbt'])) {
            $total++;
            $tbt_value = $cwv['tbt']['numeric'];
            if ($tbt_value < 200) {
                $passed++;
                $assessments['tbt'] = array('status' => 'good', 'message' => 'TBT is good');
            } elseif ($tbt_value < 600) {
                $assessments['tbt'] = array('status' => 'needs_improvement', 'message' => 'TBT needs improvement');
            } else {
                $assessments['tbt'] = array('status' => 'poor', 'message' => 'TBT is poor');
            }
        }

        $overall_status = 'poor';
        if ($total > 0 && $passed === $total) {
            $overall_status = 'good';
        } elseif ($total > 0 && $passed >= $total / 2) {
            $overall_status = 'needs_improvement';
        }

        return array(
            'status' => $overall_status,
            'passed' => $passed,
            'total' => $total,
            'assessments' => $assessments
        );
    }
}

// Create table on plugin activation
register_activation_hook(AUDIT_SEO_SEMANTIC_PATH . 'audit-seo-semantic.php', array('Audit_SEO_PageSpeed', 'maybe_create_table'));
