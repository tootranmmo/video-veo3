<?php
/**
 * Broken Link Checker
 */
class Audit_SEO_Link_Checker {

    /**
     * Check post for broken links
     */
    public static function check_post($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return array('error' => 'Post not found');
        }

        $results = array(
            'total_links' => 0,
            'broken_links' => array(),
            'redirects' => array(),
            'internal_links' => 0,
            'external_links' => 0,
            'working_links' => 0,
            'checked_at' => current_time('mysql')
        );

        // Extract all links from content
        $content = $post->post_content;
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);

        if (empty($matches[1])) {
            return $results;
        }

        $links = array_unique($matches[1]);
        $results['total_links'] = count($links);

        foreach ($links as $url) {
            // Skip anchors and javascript
            if (strpos($url, '#') === 0 || strpos($url, 'javascript:') === 0) {
                continue;
            }

            // Convert relative URLs to absolute
            if (strpos($url, 'http') !== 0) {
                $url = home_url($url);
            }

            // Check if internal or external
            $site_url = get_site_url();
            $is_internal = strpos($url, $site_url) === 0;

            if ($is_internal) {
                $results['internal_links']++;
            } else {
                $results['external_links']++;
            }

            // Check link status
            $status = self::check_url($url);

            if ($status['code'] === 404 || $status['code'] === 0) {
                $results['broken_links'][] = array(
                    'url' => $url,
                    'status_code' => $status['code'],
                    'error' => $status['error'],
                    'type' => $is_internal ? 'internal' : 'external'
                );
            } elseif ($status['code'] >= 300 && $status['code'] < 400) {
                $results['redirects'][] = array(
                    'url' => $url,
                    'status_code' => $status['code'],
                    'redirect_to' => $status['redirect_url'],
                    'type' => $is_internal ? 'internal' : 'external'
                );
            } else {
                $results['working_links']++;
            }
        }

        // Save results
        self::save_results($post_id, $results);

        return $results;
    }

    /**
     * Check URL status
     */
    private static function check_url($url) {
        $result = array(
            'code' => 0,
            'error' => '',
            'redirect_url' => ''
        );

        // Use WordPress HTTP API
        $response = wp_remote_head($url, array(
            'timeout' => 10,
            'redirection' => 5,
            'sslverify' => false,
            'user-agent' => 'Mozilla/5.0 (compatible; AuditSEO/1.0; +' . home_url() . ')'
        ));

        if (is_wp_error($response)) {
            $result['error'] = $response->get_error_message();
            return $result;
        }

        $result['code'] = wp_remote_retrieve_response_code($response);

        // Check for redirects
        if ($result['code'] >= 300 && $result['code'] < 400) {
            $headers = wp_remote_retrieve_headers($response);
            $result['redirect_url'] = isset($headers['location']) ? $headers['location'] : '';
        }

        return $result;
    }

    /**
     * Check all posts
     */
    public static function check_all_posts($post_type = 'post', $limit = 10) {
        $posts = get_posts(array(
            'post_type' => $post_type,
            'posts_per_page' => $limit,
            'post_status' => 'publish'
        ));

        $results = array();

        foreach ($posts as $post) {
            $results[$post->ID] = self::check_post($post->ID);
        }

        return $results;
    }

    /**
     * Save check results
     */
    private static function save_results($post_id, $results) {
        update_post_meta($post_id, '_audit_seo_link_check', $results);
        update_post_meta($post_id, '_audit_seo_link_check_date', current_time('mysql'));
    }

    /**
     * Get saved results
     */
    public static function get_results($post_id) {
        return get_post_meta($post_id, '_audit_seo_link_check', true);
    }

    /**
     * Get all broken links across site
     */
    public static function get_all_broken_links() {
        global $wpdb;

        $results = $wpdb->get_results("
            SELECT post_id, meta_value
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_audit_seo_link_check'
        ");

        $broken_links = array();

        foreach ($results as $row) {
            $data = maybe_unserialize($row->meta_value);
            if (!empty($data['broken_links'])) {
                foreach ($data['broken_links'] as $link) {
                    $broken_links[] = array(
                        'post_id' => $row->post_id,
                        'post_title' => get_the_title($row->post_id),
                        'url' => $link['url'],
                        'status_code' => $link['status_code'],
                        'error' => $link['error'],
                        'type' => $link['type']
                    );
                }
            }
        }

        return $broken_links;
    }

    /**
     * Fix broken link (replace in content)
     */
    public static function fix_broken_link($post_id, $old_url, $new_url) {
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }

        $content = $post->post_content;
        $new_content = str_replace($old_url, $new_url, $content);

        if ($content !== $new_content) {
            wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $new_content
            ));

            // Re-check links
            self::check_post($post_id);

            return true;
        }

        return false;
    }

    /**
     * Remove broken link from content
     */
    public static function remove_broken_link($post_id, $url) {
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }

        $content = $post->post_content;

        // Remove the entire <a> tag but keep the text
        $pattern = '/<a[^>]+href=["\']' . preg_quote($url, '/') . '["\'][^>]*>(.*?)<\/a>/i';
        $new_content = preg_replace($pattern, '$1', $content);

        if ($content !== $new_content) {
            wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $new_content
            ));

            // Re-check links
            self::check_post($post_id);

            return true;
        }

        return false;
    }

    /**
     * Get link check statistics
     */
    public static function get_statistics() {
        global $wpdb;

        $stats = array(
            'total_checked' => 0,
            'total_links' => 0,
            'total_broken' => 0,
            'total_redirects' => 0,
            'last_check' => null
        );

        $results = $wpdb->get_results("
            SELECT meta_value
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_audit_seo_link_check'
        ");

        foreach ($results as $row) {
            $data = maybe_unserialize($row->meta_value);
            if ($data) {
                $stats['total_checked']++;
                $stats['total_links'] += $data['total_links'];
                $stats['total_broken'] += count($data['broken_links']);
                $stats['total_redirects'] += count($data['redirects']);
            }
        }

        $last_check = $wpdb->get_var("
            SELECT meta_value
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_audit_seo_link_check_date'
            ORDER BY meta_value DESC
            LIMIT 1
        ");

        $stats['last_check'] = $last_check;

        return $stats;
    }
}
