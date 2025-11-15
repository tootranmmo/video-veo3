<?php
/**
 * Technical SEO Audit Class
 */
class Audit_SEO_Technical_Audit {

    /**
     * Run technical audit for a post/page
     */
    public static function run_audit($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return array('error' => 'Post not found');
        }

        $results = array(
            'score' => 0,
            'max_score' => 100,
            'issues' => array(),
            'passed' => array(),
            'warnings' => array()
        );

        // Check meta tags
        $meta_results = self::check_meta_tags($post);
        $results = self::merge_results($results, $meta_results);

        // Check heading structure
        $heading_results = self::check_heading_structure($post);
        $results = self::merge_results($results, $heading_results);

        // Check URL structure
        $url_results = self::check_url_structure($post);
        $results = self::merge_results($results, $url_results);

        // Check images
        $image_results = self::check_images($post);
        $results = self::merge_results($results, $image_results);

        // Check SSL/HTTPS
        $ssl_results = self::check_ssl();
        $results = self::merge_results($results, $ssl_results);

        // Check structured data
        $schema_results = self::check_structured_data($post);
        $results = self::merge_results($results, $schema_results);

        // Check robots meta
        $robots_results = self::check_robots_meta($post);
        $results = self::merge_results($results, $robots_results);

        // Calculate final score
        $results['score'] = round(($results['score'] / $results['max_score']) * 100);

        return $results;
    }

    /**
     * Check meta tags
     */
    private static function check_meta_tags($post) {
        $results = array('score' => 0, 'max_score' => 20, 'issues' => array(), 'passed' => array());

        // Check title
        $title = get_the_title($post->ID);
        if (empty($title)) {
            $results['issues'][] = 'Missing page title';
        } elseif (strlen($title) < 30) {
            $results['issues'][] = 'Page title too short (minimum 30 characters)';
        } elseif (strlen($title) > 60) {
            $results['issues'][] = 'Page title too long (maximum 60 characters)';
        } else {
            $results['score'] += 10;
            $results['passed'][] = 'Page title length is optimal (' . strlen($title) . ' characters)';
        }

        // Check meta description
        $meta_desc = get_post_meta($post->ID, '_meta_description', true);
        if (empty($meta_desc)) {
            $results['issues'][] = 'Missing meta description';
        } elseif (strlen($meta_desc) < 120) {
            $results['issues'][] = 'Meta description too short (minimum 120 characters)';
        } elseif (strlen($meta_desc) > 160) {
            $results['issues'][] = 'Meta description too long (maximum 160 characters)';
        } else {
            $results['score'] += 10;
            $results['passed'][] = 'Meta description length is optimal (' . strlen($meta_desc) . ' characters)';
        }

        return $results;
    }

    /**
     * Check heading structure
     */
    private static function check_heading_structure($post) {
        $results = array('score' => 0, 'max_score' => 15, 'issues' => array(), 'passed' => array());

        $content = $post->post_content;

        // Check H1
        preg_match_all('/<h1[^>]*>(.*?)<\/h1>/i', $content, $h1_matches);
        $h1_count = count($h1_matches[0]);

        if ($h1_count === 0) {
            $results['issues'][] = 'No H1 heading found';
        } elseif ($h1_count > 1) {
            $results['issues'][] = 'Multiple H1 headings found (' . $h1_count . ')';
        } else {
            $results['score'] += 8;
            $results['passed'][] = 'Single H1 heading found';
        }

        // Check H2-H6
        preg_match_all('/<h[2-6][^>]*>/i', $content, $heading_matches);
        $heading_count = count($heading_matches[0]);

        if ($heading_count === 0) {
            $results['issues'][] = 'No subheadings (H2-H6) found';
        } else {
            $results['score'] += 7;
            $results['passed'][] = 'Subheadings found (' . $heading_count . ' headings)';
        }

        return $results;
    }

    /**
     * Check URL structure
     */
    private static function check_url_structure($post) {
        $results = array('score' => 0, 'max_score' => 10, 'issues' => array(), 'passed' => array());

        $permalink = get_permalink($post->ID);
        $slug = $post->post_name;

        // Check if URL contains post ID (not SEO friendly)
        if (preg_match('/\?p=\d+/', $permalink)) {
            $results['issues'][] = 'URL is not SEO-friendly (contains ?p=ID)';
        } else {
            $results['score'] += 5;
            $results['passed'][] = 'URL structure is SEO-friendly';
        }

        // Check slug length
        if (strlen($slug) > 0 && strlen($slug) < 100) {
            $results['score'] += 5;
            $results['passed'][] = 'URL slug length is appropriate';
        } elseif (strlen($slug) >= 100) {
            $results['issues'][] = 'URL slug is too long';
        }

        return $results;
    }

    /**
     * Check images
     */
    private static function check_images($post) {
        $results = array('score' => 0, 'max_score' => 15, 'issues' => array(), 'passed' => array());

        $content = $post->post_content;
        preg_match_all('/<img[^>]+>/i', $content, $img_matches);
        $images = $img_matches[0];

        if (count($images) === 0) {
            $results['issues'][] = 'No images found in content';
            return $results;
        }

        $missing_alt = 0;
        foreach ($images as $img) {
            if (!preg_match('/alt=["\'][^"\']*["\']/i', $img)) {
                $missing_alt++;
            }
        }

        if ($missing_alt > 0) {
            $results['issues'][] = $missing_alt . ' image(s) missing alt text';
        } else {
            $results['score'] += 15;
            $results['passed'][] = 'All images have alt text';
        }

        return $results;
    }

    /**
     * Check SSL/HTTPS
     */
    private static function check_ssl() {
        $results = array('score' => 0, 'max_score' => 10, 'issues' => array(), 'passed' => array());

        if (is_ssl()) {
            $results['score'] += 10;
            $results['passed'][] = 'Site is using HTTPS';
        } else {
            $results['issues'][] = 'Site is not using HTTPS/SSL';
        }

        return $results;
    }

    /**
     * Check structured data
     */
    private static function check_structured_data($post) {
        $results = array('score' => 0, 'max_score' => 15, 'issues' => array(), 'passed' => array());

        $content = $post->post_content;

        // Check for JSON-LD
        if (preg_match('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>/i', $content)) {
            $results['score'] += 15;
            $results['passed'][] = 'Structured data (JSON-LD) found';
        } else {
            // Check for microdata
            if (preg_match('/itemscope|itemtype|itemprop/i', $content)) {
                $results['score'] += 10;
                $results['passed'][] = 'Microdata structured data found';
            } else {
                $results['issues'][] = 'No structured data (Schema.org) found';
            }
        }

        return $results;
    }

    /**
     * Check robots meta
     */
    private static function check_robots_meta($post) {
        $results = array('score' => 0, 'max_score' => 15, 'issues' => array(), 'passed' => array());

        $robots_meta = get_post_meta($post->ID, '_robots_meta', true);

        if (strpos($robots_meta, 'noindex') !== false) {
            $results['issues'][] = 'Page is set to noindex (will not be indexed by search engines)';
        } else {
            $results['score'] += 8;
            $results['passed'][] = 'Page is indexable';
        }

        if (strpos($robots_meta, 'nofollow') !== false) {
            $results['issues'][] = 'Page is set to nofollow';
        } else {
            $results['score'] += 7;
            $results['passed'][] = 'Page allows following links';
        }

        return $results;
    }

    /**
     * Merge audit results
     */
    private static function merge_results($base, $new) {
        $base['score'] += $new['score'];
        $base['max_score'] += $new['max_score'];
        $base['issues'] = array_merge($base['issues'], $new['issues']);
        $base['passed'] = array_merge($base['passed'], $new['passed']);
        if (isset($new['warnings'])) {
            $base['warnings'] = array_merge($base['warnings'], $new['warnings']);
        }
        return $base;
    }
}
