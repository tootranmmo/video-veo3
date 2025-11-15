<?php
/**
 * Backlink Audit Class
 */
class Audit_SEO_Backlink_Audit {

    /**
     * Get all backlinks
     */
    public static function get_backlinks($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'audit_seo_backlinks';

        $defaults = array(
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'status' => 'all'
        );

        $args = wp_parse_args($args, $defaults);

        $where = '1=1';
        if ($args['status'] !== 'all') {
            $where .= $wpdb->prepare(' AND status = %s', $args['status']);
        }

        $sql = $wpdb->prepare(
            "SELECT * FROM $table WHERE $where ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d",
            $args['limit'],
            $args['offset']
        );

        return $wpdb->get_results($sql);
    }

    /**
     * Get backlink by ID
     */
    public static function get_backlink($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'audit_seo_backlinks';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ));
    }

    /**
     * Add new backlink
     */
    public static function add_backlink($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'audit_seo_backlinks';

        $defaults = array(
            'url' => '',
            'source_url' => '',
            'anchor_text' => '',
            'link_type' => 'dofollow',
            'domain_authority' => 0,
            'page_authority' => 0,
            'spam_score' => 0,
            'status' => 'active'
        );

        $data = wp_parse_args($data, $defaults);

        // Validate required fields
        if (empty($data['url']) || empty($data['source_url'])) {
            return new WP_Error('missing_data', 'URL and Source URL are required');
        }

        $result = $wpdb->insert($table, $data);

        if ($result === false) {
            return new WP_Error('db_error', 'Failed to insert backlink');
        }

        return $wpdb->insert_id;
    }

    /**
     * Update backlink
     */
    public static function update_backlink($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'audit_seo_backlinks';

        $result = $wpdb->update(
            $table,
            $data,
            array('id' => $id)
        );

        if ($result === false) {
            return new WP_Error('db_error', 'Failed to update backlink');
        }

        return true;
    }

    /**
     * Delete backlink
     */
    public static function delete_backlink($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'audit_seo_backlinks';

        $result = $wpdb->delete($table, array('id' => $id));

        if ($result === false) {
            return new WP_Error('db_error', 'Failed to delete backlink');
        }

        return true;
    }

    /**
     * Run backlink audit
     */
    public static function run_audit() {
        $backlinks = self::get_backlinks(array('limit' => 1000));

        $results = array(
            'total_backlinks' => count($backlinks),
            'dofollow' => 0,
            'nofollow' => 0,
            'toxic_links' => 0,
            'healthy_links' => 0,
            'avg_domain_authority' => 0,
            'avg_spam_score' => 0,
            'by_status' => array(),
            'top_domains' => array()
        );

        if (empty($backlinks)) {
            return $results;
        }

        $total_da = 0;
        $total_spam = 0;
        $domains = array();

        foreach ($backlinks as $backlink) {
            // Count link types
            if ($backlink->link_type === 'dofollow') {
                $results['dofollow']++;
            } else {
                $results['nofollow']++;
            }

            // Count toxic vs healthy
            if ($backlink->spam_score >= 50) {
                $results['toxic_links']++;
            } else {
                $results['healthy_links']++;
            }

            // Sum for averages
            $total_da += $backlink->domain_authority;
            $total_spam += $backlink->spam_score;

            // Count by status
            if (!isset($results['by_status'][$backlink->status])) {
                $results['by_status'][$backlink->status] = 0;
            }
            $results['by_status'][$backlink->status]++;

            // Count domains
            $domain = self::extract_domain($backlink->source_url);
            if (!isset($domains[$domain])) {
                $domains[$domain] = 0;
            }
            $domains[$domain]++;
        }

        // Calculate averages
        $results['avg_domain_authority'] = round($total_da / count($backlinks), 1);
        $results['avg_spam_score'] = round($total_spam / count($backlinks), 1);

        // Get top domains
        arsort($domains);
        $results['top_domains'] = array_slice($domains, 0, 10, true);

        return $results;
    }

    /**
     * Analyze anchor text distribution
     */
    public static function analyze_anchor_text() {
        global $wpdb;
        $table = $wpdb->prefix . 'audit_seo_backlinks';

        $results = $wpdb->get_results(
            "SELECT anchor_text, COUNT(*) as count
             FROM $table
             WHERE anchor_text IS NOT NULL AND anchor_text != ''
             GROUP BY anchor_text
             ORDER BY count DESC
             LIMIT 20"
        );

        return $results;
    }

    /**
     * Get backlink statistics by date
     */
    public static function get_backlink_velocity($days = 30) {
        global $wpdb;
        $table = $wpdb->prefix . 'audit_seo_backlinks';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as date, COUNT(*) as count
             FROM $table
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            $days
        ));

        return $results;
    }

    /**
     * Extract domain from URL
     */
    private static function extract_domain($url) {
        $parsed = parse_url($url);
        return isset($parsed['host']) ? $parsed['host'] : '';
    }

    /**
     * Check if backlink exists
     */
    public static function backlink_exists($source_url, $url) {
        global $wpdb;
        $table = $wpdb->prefix . 'audit_seo_backlinks';

        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE source_url = %s AND url = %s",
            $source_url,
            $url
        ));

        return $result > 0;
    }
}
