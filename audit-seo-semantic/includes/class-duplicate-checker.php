<?php
/**
 * Duplicate Content Checker
 * Detects duplicate or highly similar content across the site
 */
class Audit_SEO_Duplicate_Checker {

    /**
     * Minimum similarity percentage to consider as duplicate
     */
    const DUPLICATE_THRESHOLD = 70; // 70% similarity

    /**
     * Minimum content length to check (words)
     */
    const MIN_CONTENT_LENGTH = 50;

    /**
     * Check for duplicate content
     */
    public static function check_duplicate($post_id, $content = '') {
        $post = get_post($post_id);

        if (!$post) {
            return array('error' => 'Post not found');
        }

        if (empty($content)) {
            $content = $post->post_content;
        }

        // Clean content
        $clean_content = wp_strip_all_tags($content);
        $word_count = str_word_count($clean_content);

        if ($word_count < self::MIN_CONTENT_LENGTH) {
            return array(
                'has_duplicates' => false,
                'message' => 'Content too short to check for duplicates'
            );
        }

        // Get all published posts/pages except current
        $args = array(
            'post_type' => array('post', 'page'),
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'post__not_in' => array($post_id),
            'fields' => 'ids'
        );

        $all_posts = get_posts($args);

        $duplicates = array();
        $highest_similarity = 0;

        foreach ($all_posts as $compare_post_id) {
            $compare_post = get_post($compare_post_id);
            $compare_content = wp_strip_all_tags($compare_post->post_content);

            // Calculate similarity
            $similarity = self::calculate_similarity($clean_content, $compare_content);

            if ($similarity >= self::DUPLICATE_THRESHOLD) {
                $duplicates[] = array(
                    'post_id' => $compare_post_id,
                    'title' => get_the_title($compare_post_id),
                    'url' => get_permalink($compare_post_id),
                    'similarity' => $similarity,
                    'excerpt' => wp_trim_words($compare_content, 30)
                );

                if ($similarity > $highest_similarity) {
                    $highest_similarity = $similarity;
                }
            }
        }

        // Sort by similarity (highest first)
        usort($duplicates, function($a, $b) {
            return $b['similarity'] - $a['similarity'];
        });

        $result = array(
            'has_duplicates' => !empty($duplicates),
            'count' => count($duplicates),
            'highest_similarity' => $highest_similarity,
            'duplicates' => $duplicates,
            'checked_at' => current_time('mysql')
        );

        // Save to post meta
        update_post_meta($post_id, '_audit_seo_duplicate_check', $result);

        return $result;
    }

    /**
     * Calculate content similarity percentage
     */
    private static function calculate_similarity($content1, $content2) {
        // Normalize content
        $content1 = strtolower(trim($content1));
        $content2 = strtolower(trim($content2));

        if (empty($content1) || empty($content2)) {
            return 0;
        }

        // Use similar_text for basic similarity
        similar_text($content1, $content2, $percent);

        // Alternative: Use word-based comparison for better accuracy
        $words1 = array_unique(str_word_count($content1, 1));
        $words2 = array_unique(str_word_count($content2, 1));

        $common_words = array_intersect($words1, $words2);
        $total_words = array_unique(array_merge($words1, $words2));

        $word_similarity = 0;
        if (count($total_words) > 0) {
            $word_similarity = (count($common_words) / count($total_words)) * 100;
        }

        // Average both methods for better accuracy
        $similarity = ($percent + $word_similarity) / 2;

        return round($similarity, 2);
    }

    /**
     * Check for duplicate title
     */
    public static function check_duplicate_title($post_id, $title = '') {
        if (empty($title)) {
            $title = get_the_title($post_id);
        }

        global $wpdb;

        $duplicate_titles = $wpdb->get_results($wpdb->prepare(
            "SELECT ID, post_title
             FROM {$wpdb->posts}
             WHERE post_title = %s
             AND ID != %d
             AND post_status = 'publish'
             AND post_type IN ('post', 'page')",
            $title,
            $post_id
        ));

        return array(
            'has_duplicate_title' => !empty($duplicate_titles),
            'count' => count($duplicate_titles),
            'duplicates' => $duplicate_titles
        );
    }

    /**
     * Check for duplicate meta description
     */
    public static function check_duplicate_meta_description($post_id, $meta_desc = '') {
        if (empty($meta_desc)) {
            $meta_desc = get_post_meta($post_id, '_audit_seo_meta_description', true);
        }

        if (empty($meta_desc)) {
            return array('has_duplicate_meta' => false);
        }

        global $wpdb;

        $duplicate_metas = $wpdb->get_results($wpdb->prepare(
            "SELECT post_id, meta_value
             FROM {$wpdb->postmeta}
             WHERE meta_key = '_audit_seo_meta_description'
             AND meta_value = %s
             AND post_id != %d",
            $meta_desc,
            $post_id
        ));

        $duplicates = array();
        foreach ($duplicate_metas as $meta) {
            $duplicates[] = array(
                'post_id' => $meta->post_id,
                'title' => get_the_title($meta->post_id),
                'url' => get_permalink($meta->post_id)
            );
        }

        return array(
            'has_duplicate_meta' => !empty($duplicates),
            'count' => count($duplicates),
            'duplicates' => $duplicates
        );
    }

    /**
     * Get all duplicate content issues across site
     */
    public static function get_all_duplicates() {
        global $wpdb;

        $results = $wpdb->get_results(
            "SELECT post_id, meta_value
             FROM {$wpdb->postmeta}
             WHERE meta_key = '_audit_seo_duplicate_check'"
        );

        $all_issues = array();

        foreach ($results as $row) {
            $data = maybe_unserialize($row->meta_value);
            if (!empty($data['has_duplicates']) && $data['has_duplicates']) {
                $all_issues[] = array(
                    'post_id' => $row->post_id,
                    'title' => get_the_title($row->post_id),
                    'url' => get_permalink($row->post_id),
                    'duplicate_count' => $data['count'],
                    'highest_similarity' => $data['highest_similarity'],
                    'checked_at' => $data['checked_at']
                );
            }
        }

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

        // Run duplicate check
        self::check_duplicate($post_id, $post->post_content);
    }

    /**
     * Get duplicate check results
     */
    public static function get_results($post_id) {
        return get_post_meta($post_id, '_audit_seo_duplicate_check', true);
    }

    /**
     * Calculate content fingerprint (for faster comparison)
     */
    private static function get_content_fingerprint($content) {
        $clean = strtolower(wp_strip_all_tags($content));
        $words = str_word_count($clean, 1);

        // Remove common words
        $stop_words = array('the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for');
        $words = array_diff($words, $stop_words);

        // Get top 50 words
        $word_freq = array_count_values($words);
        arsort($word_freq);
        $top_words = array_slice(array_keys($word_freq), 0, 50);

        return md5(implode(',', $top_words));
    }

    /**
     * Find similar posts by fingerprint
     */
    public static function find_similar_by_fingerprint($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return array();
        }

        $fingerprint = self::get_content_fingerprint($post->post_content);

        // This is a simplified version - in production you'd store fingerprints
        // in post meta and compare them for faster lookups

        return array();
    }
}

// Hook into save_post
add_action('save_post', array('Audit_SEO_Duplicate_Checker', 'auto_check_on_save'), 20, 2);
