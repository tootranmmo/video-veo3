<?php
/**
 * Keyword Cannibalization Detector
 * Detects when multiple pages target the same keyword
 */
class Audit_SEO_Keyword_Cannibalization {

    /**
     * Check for keyword cannibalization
     */
    public static function check_cannibalization($post_id, $focus_keyword = '') {
        if (empty($focus_keyword)) {
            $focus_keyword = get_post_meta($post_id, '_audit_seo_focus_keyword', true);
        }

        if (empty($focus_keyword)) {
            return array(
                'has_cannibalization' => false,
                'message' => 'No focus keyword set'
            );
        }

        // Find other posts with same focus keyword
        $competing_posts = self::find_competing_posts($post_id, $focus_keyword);

        $result = array(
            'has_cannibalization' => !empty($competing_posts),
            'focus_keyword' => $focus_keyword,
            'competing_posts_count' => count($competing_posts),
            'competing_posts' => $competing_posts,
            'severity' => self::calculate_severity(count($competing_posts)),
            'recommendations' => self::get_recommendations($focus_keyword, count($competing_posts)),
            'checked_at' => current_time('mysql')
        );

        // Save to post meta
        update_post_meta($post_id, '_audit_seo_keyword_cannibalization', $result);

        return $result;
    }

    /**
     * Find posts competing for same keyword
     */
    private static function find_competing_posts($post_id, $keyword) {
        global $wpdb;

        // Exact match on focus keyword
        $exact_matches = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, p.post_title, pm.meta_value as focus_keyword
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE pm.meta_key = '_audit_seo_focus_keyword'
             AND LOWER(pm.meta_value) = LOWER(%s)
             AND p.ID != %d
             AND p.post_status = 'publish'
             AND p.post_type IN ('post', 'page')
             ORDER BY p.post_date DESC",
            $keyword,
            $post_id
        ));

        $competing = array();

        foreach ($exact_matches as $match) {
            $competing[] = array(
                'post_id' => $match->ID,
                'title' => $match->post_title,
                'url' => get_permalink($match->ID),
                'keyword' => $match->focus_keyword,
                'match_type' => 'exact',
                'published_date' => get_the_date('Y-m-d', $match->ID),
                'score' => self::get_post_seo_score($match->ID)
            );
        }

        // Also check for similar keywords (variations)
        $similar_keywords = self::find_similar_keywords($keyword);
        foreach ($similar_keywords as $similar) {
            $similar_matches = $wpdb->get_results($wpdb->prepare(
                "SELECT p.ID, p.post_title, pm.meta_value as focus_keyword
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                 WHERE pm.meta_key = '_audit_seo_focus_keyword'
                 AND LOWER(pm.meta_value) = LOWER(%s)
                 AND p.ID != %d
                 AND p.post_status = 'publish'
                 AND p.post_type IN ('post', 'page')
                 LIMIT 5",
                $similar,
                $post_id
            ));

            foreach ($similar_matches as $match) {
                $competing[] = array(
                    'post_id' => $match->ID,
                    'title' => $match->post_title,
                    'url' => get_permalink($match->ID),
                    'keyword' => $match->focus_keyword,
                    'match_type' => 'similar',
                    'published_date' => get_the_date('Y-m-d', $match->ID),
                    'score' => self::get_post_seo_score($match->ID)
                );
            }
        }

        return $competing;
    }

    /**
     * Find similar keyword variations
     */
    private static function find_similar_keywords($keyword) {
        $variations = array();
        $keyword_lower = strtolower($keyword);

        // Plural/singular
        if (substr($keyword_lower, -1) === 's') {
            $variations[] = substr($keyword_lower, 0, -1);
        } else {
            $variations[] = $keyword_lower . 's';
        }

        // Common variations
        $words = explode(' ', $keyword_lower);
        if (count($words) > 1) {
            // Reverse word order
            $variations[] = implode(' ', array_reverse($words));
        }

        return array_unique($variations);
    }

    /**
     * Calculate cannibalization severity
     */
    private static function calculate_severity($count) {
        if ($count === 0) {
            return 'none';
        } elseif ($count === 1) {
            return 'low';
        } elseif ($count <= 3) {
            return 'medium';
        } else {
            return 'high';
        }
    }

    /**
     * Get SEO score for a post
     */
    private static function get_post_seo_score($post_id) {
        global $wpdb;

        $latest_audit = $wpdb->get_row($wpdb->prepare(
            "SELECT score FROM {$wpdb->prefix}audit_seo_history
             WHERE post_id = %d
             ORDER BY created_at DESC
             LIMIT 1",
            $post_id
        ));

        return $latest_audit ? $latest_audit->score : 0;
    }

    /**
     * Get recommendations
     */
    private static function get_recommendations($keyword, $count) {
        $recommendations = array();

        if ($count === 0) {
            $recommendations[] = "No cannibalization detected. You're the only one targeting '{$keyword}'.";
        } elseif ($count === 1) {
            $recommendations[] = "Low risk: 1 other post targets '{$keyword}'.";
            $recommendations[] = "Consider: Differentiate content or consolidate if topics overlap.";
        } elseif ($count <= 3) {
            $recommendations[] = "Medium risk: {$count} posts compete for '{$keyword}'.";
            $recommendations[] = "Action needed: Review competing pages and consolidate or differentiate.";
            $recommendations[] = "Tip: Use long-tail variations to reduce competition.";
        } else {
            $recommendations[] = "High risk: {$count} posts compete for '{$keyword}'!";
            $recommendations[] = "Urgent: Consolidate pages or use different keywords.";
            $recommendations[] = "Best practice: Keep 1 authoritative page per keyword.";
            $recommendations[] = "Consider: 301 redirects from weaker pages to strongest one.";
        }

        return $recommendations;
    }

    /**
     * Get all cannibalization issues
     */
    public static function get_all_issues() {
        global $wpdb;

        $results = $wpdb->get_results(
            "SELECT post_id, meta_value
             FROM {$wpdb->postmeta}
             WHERE meta_key = '_audit_seo_keyword_cannibalization'"
        );

        $all_issues = array();
        $keyword_groups = array();

        foreach ($results as $row) {
            $data = maybe_unserialize($row->meta_value);

            if (!empty($data['has_cannibalization']) && $data['has_cannibalization']) {
                $keyword = $data['focus_keyword'];

                if (!isset($keyword_groups[$keyword])) {
                    $keyword_groups[$keyword] = array();
                }

                $keyword_groups[$keyword][] = array(
                    'post_id' => $row->post_id,
                    'title' => get_the_title($row->post_id),
                    'url' => get_permalink($row->post_id),
                    'competing_count' => $data['competing_posts_count'],
                    'severity' => $data['severity']
                );
            }
        }

        // Convert to issues format
        foreach ($keyword_groups as $keyword => $posts) {
            if (count($posts) > 1) {
                $all_issues[] = array(
                    'keyword' => $keyword,
                    'affected_posts' => $posts,
                    'total_posts' => count($posts),
                    'severity' => self::calculate_severity(count($posts) - 1)
                );
            }
        }

        // Sort by severity
        usort($all_issues, function($a, $b) {
            $severity_order = array('high' => 3, 'medium' => 2, 'low' => 1, 'none' => 0);
            return $severity_order[$b['severity']] - $severity_order[$a['severity']];
        });

        return $all_issues;
    }

    /**
     * Auto-check on save
     */
    public static function auto_check_on_save($post_id, $post) {
        // Skip autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Only check published posts/pages
        if (!in_array($post->post_status, array('publish'))) {
            return;
        }

        if (!in_array($post->post_type, array('post', 'page'))) {
            return;
        }

        // Get focus keyword
        $focus_keyword = get_post_meta($post_id, '_audit_seo_focus_keyword', true);

        if (!empty($focus_keyword)) {
            self::check_cannibalization($post_id, $focus_keyword);
        }
    }

    /**
     * Get results for a post
     */
    public static function get_results($post_id) {
        return get_post_meta($post_id, '_audit_seo_keyword_cannibalization', true);
    }

    /**
     * Get keyword usage statistics
     */
    public static function get_keyword_stats() {
        global $wpdb;

        $keyword_usage = $wpdb->get_results(
            "SELECT meta_value as keyword, COUNT(*) as usage_count
             FROM {$wpdb->postmeta}
             WHERE meta_key = '_audit_seo_focus_keyword'
             AND meta_value != ''
             GROUP BY meta_value
             HAVING usage_count > 1
             ORDER BY usage_count DESC"
        );

        return $keyword_usage;
    }

    /**
     * Suggest alternative keywords
     */
    public static function suggest_alternatives($keyword) {
        // Generate variations
        $suggestions = array();

        $keyword_lower = strtolower($keyword);
        $words = explode(' ', $keyword_lower);

        // Add location modifiers
        $suggestions[] = $keyword . ' near me';
        $suggestions[] = 'best ' . $keyword;
        $suggestions[] = $keyword . ' guide';
        $suggestions[] = $keyword . ' tips';
        $suggestions[] = 'how to ' . $keyword;
        $suggestions[] = $keyword . ' tutorial';

        // Add year
        $current_year = date('Y');
        $suggestions[] = $keyword . ' ' . $current_year;

        // Add question words
        $suggestions[] = 'what is ' . $keyword;
        $suggestions[] = 'why ' . $keyword;
        $suggestions[] = 'when to use ' . $keyword;

        return array_unique($suggestions);
    }
}

// Hook into save_post
add_action('save_post', array('Audit_SEO_Keyword_Cannibalization', 'auto_check_on_save'), 20, 2);
