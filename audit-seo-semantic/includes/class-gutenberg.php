<?php
/**
 * Gutenberg Integration for Real-time SEO Scoring
 */
class Audit_SEO_Gutenberg {

    /**
     * Initialize Gutenberg integration
     */
    public static function init() {
        add_action('enqueue_block_editor_assets', array(__CLASS__, 'enqueue_block_editor_assets'));
        add_action('rest_api_init', array(__CLASS__, 'register_rest_routes'));
    }

    /**
     * Enqueue block editor assets
     */
    public static function enqueue_block_editor_assets() {
        global $post;

        // Enqueue sidebar plugin script
        wp_enqueue_script(
            'audit-seo-gutenberg',
            AUDIT_SEO_SEMANTIC_URL . 'assets/js/gutenberg-plugin.js',
            array('wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-compose'),
            AUDIT_SEO_SEMANTIC_VERSION,
            true
        );

        // Enqueue styles
        wp_enqueue_style(
            'audit-seo-gutenberg',
            AUDIT_SEO_SEMANTIC_URL . 'assets/css/gutenberg.css',
            array('wp-edit-post'),
            AUDIT_SEO_SEMANTIC_VERSION
        );

        // Pass data to JavaScript
        wp_localize_script('audit-seo-gutenberg', 'auditSeoGutenberg', array(
            'apiUrl' => rest_url('audit-seo/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'postId' => $post ? $post->ID : 0
        ));
    }

    /**
     * Register REST API routes
     */
    public static function register_rest_routes() {
        register_rest_route('audit-seo/v1', '/analyze', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'analyze_content'),
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ));

        register_rest_route('audit-seo/v1', '/suggestions', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'get_suggestions'),
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ));
    }

    /**
     * Analyze content in real-time
     */
    public static function analyze_content($request) {
        $title = sanitize_text_field($request->get_param('title'));
        $content = wp_kses_post($request->get_param('content'));
        $focus_keyword = sanitize_text_field($request->get_param('focus_keyword'));

        $analysis = array(
            'score' => 0,
            'checks' => array(),
            'suggestions' => array()
        );

        // Analyze title
        $title_analysis = self::analyze_title($title, $focus_keyword);
        $analysis['checks']['title'] = $title_analysis;
        $analysis['score'] += $title_analysis['score'];

        // Analyze content length
        $content_analysis = self::analyze_content_length($content);
        $analysis['checks']['content_length'] = $content_analysis;
        $analysis['score'] += $content_analysis['score'];

        // Analyze keyword usage
        if (!empty($focus_keyword)) {
            $keyword_analysis = self::analyze_keyword($title, $content, $focus_keyword);
            $analysis['checks']['keyword'] = $keyword_analysis;
            $analysis['score'] += $keyword_analysis['score'];
        }

        // Analyze headings
        $heading_analysis = self::analyze_headings($content);
        $analysis['checks']['headings'] = $heading_analysis;
        $analysis['score'] += $heading_analysis['score'];

        // Analyze readability
        $readability_analysis = self::analyze_readability($content);
        $analysis['checks']['readability'] = $readability_analysis;
        $analysis['score'] += $readability_analysis['score'];

        // Analyze links
        $links_analysis = self::analyze_links($content);
        $analysis['checks']['links'] = $links_analysis;
        $analysis['score'] += $links_analysis['score'];

        // Calculate final score (out of 100)
        $total_checks = count($analysis['checks']);
        $analysis['score'] = $total_checks > 0 ? round($analysis['score'] / $total_checks) : 0;

        // Generate suggestions
        $analysis['suggestions'] = self::generate_suggestions($analysis['checks']);

        return rest_ensure_response($analysis);
    }

    /**
     * Analyze title
     */
    private static function analyze_title($title, $focus_keyword = '') {
        $result = array(
            'score' => 0,
            'max_score' => 100,
            'status' => 'error',
            'message' => ''
        );

        $title_length = strlen($title);

        if (empty($title)) {
            $result['message'] = 'Title is empty';
        } elseif ($title_length < 30) {
            $result['score'] = 50;
            $result['status'] = 'warning';
            $result['message'] = "Title too short ({$title_length} chars). Aim for 30-60 characters.";
        } elseif ($title_length > 60) {
            $result['score'] = 70;
            $result['status'] = 'warning';
            $result['message'] = "Title too long ({$title_length} chars). Keep it under 60 characters.";
        } else {
            $result['score'] = 100;
            $result['status'] = 'success';
            $result['message'] = "Title length is optimal ({$title_length} chars).";
        }

        // Check for focus keyword in title
        if (!empty($focus_keyword) && stripos($title, $focus_keyword) !== false) {
            $result['message'] .= ' Keyword found in title.';
        } elseif (!empty($focus_keyword)) {
            $result['score'] = max(0, $result['score'] - 20);
            $result['status'] = 'warning';
            $result['message'] .= ' Keyword not found in title.';
        }

        return $result;
    }

    /**
     * Analyze content length
     */
    private static function analyze_content_length($content) {
        $result = array(
            'score' => 0,
            'max_score' => 100,
            'status' => 'error',
            'message' => ''
        );

        $clean_content = wp_strip_all_tags($content);
        $word_count = str_word_count($clean_content);

        if ($word_count < 300) {
            $result['score'] = 30;
            $result['status'] = 'error';
            $result['message'] = "Content too short ({$word_count} words). Aim for at least 300 words.";
        } elseif ($word_count < 600) {
            $result['score'] = 70;
            $result['status'] = 'warning';
            $result['message'] = "Content length is acceptable ({$word_count} words). Consider adding more.";
        } else {
            $result['score'] = 100;
            $result['status'] = 'success';
            $result['message'] = "Content length is good ({$word_count} words).";
        }

        return $result;
    }

    /**
     * Analyze keyword usage
     */
    private static function analyze_keyword($title, $content, $keyword) {
        $result = array(
            'score' => 0,
            'max_score' => 100,
            'status' => 'error',
            'message' => '',
            'density' => 0
        );

        $clean_content = wp_strip_all_tags($content);
        $word_count = str_word_count($clean_content);

        if ($word_count === 0) {
            $result['message'] = 'No content to analyze';
            return $result;
        }

        $keyword_count = substr_count(strtolower($clean_content), strtolower($keyword));
        $density = ($keyword_count / $word_count) * 100;
        $result['density'] = round($density, 2);

        if ($keyword_count === 0) {
            $result['score'] = 0;
            $result['status'] = 'error';
            $result['message'] = "Keyword '{$keyword}' not found in content.";
        } elseif ($density < 0.5) {
            $result['score'] = 50;
            $result['status'] = 'warning';
            $result['message'] = "Keyword density too low ({$result['density']}%). Use keyword more.";
        } elseif ($density > 3) {
            $result['score'] = 60;
            $result['status'] = 'warning';
            $result['message'] = "Keyword density too high ({$result['density']}%). Risk of keyword stuffing.";
        } else {
            $result['score'] = 100;
            $result['status'] = 'success';
            $result['message'] = "Keyword density is optimal ({$result['density']}%).";
        }

        // Check first paragraph
        $first_para = substr($clean_content, 0, 200);
        if (stripos($first_para, $keyword) !== false) {
            $result['message'] .= ' Found in first paragraph.';
        } else {
            $result['score'] = max(0, $result['score'] - 10);
            $result['message'] .= ' Not in first paragraph.';
        }

        return $result;
    }

    /**
     * Analyze headings
     */
    private static function analyze_headings($content) {
        $result = array(
            'score' => 0,
            'max_score' => 100,
            'status' => 'warning',
            'message' => ''
        );

        preg_match_all('/<h([1-6])[^>]*>/i', $content, $matches);
        $heading_count = count($matches[0]);

        if ($heading_count === 0) {
            $result['message'] = 'No headings found. Add headings to structure your content.';
        } elseif ($heading_count < 3) {
            $result['score'] = 60;
            $result['message'] = "Few headings found ({$heading_count}). Add more to improve structure.";
        } else {
            $result['score'] = 100;
            $result['status'] = 'success';
            $result['message'] = "Good heading structure ({$heading_count} headings).";
        }

        return $result;
    }

    /**
     * Analyze readability
     */
    private static function analyze_readability($content) {
        $result = array(
            'score' => 0,
            'max_score' => 100,
            'status' => 'warning',
            'message' => ''
        );

        $clean_content = wp_strip_all_tags($content);
        $sentences = preg_split('/[.!?]+/', $clean_content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);

        if ($sentence_count === 0) {
            $result['message'] = 'No sentences found';
            return $result;
        }

        $word_count = str_word_count($clean_content);
        $avg_sentence_length = $word_count / $sentence_count;

        if ($avg_sentence_length > 25) {
            $result['score'] = 50;
            $result['message'] = 'Sentences are too long. Aim for average of 15-20 words.';
        } elseif ($avg_sentence_length < 10) {
            $result['score'] = 70;
            $result['message'] = 'Sentences are very short. Consider varying length.';
        } else {
            $result['score'] = 100;
            $result['status'] = 'success';
            $result['message'] = 'Sentence length is good.';
        }

        return $result;
    }

    /**
     * Analyze links
     */
    private static function analyze_links($content) {
        $result = array(
            'score' => 0,
            'max_score' => 100,
            'status' => 'warning',
            'message' => ''
        );

        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);
        $link_count = count($matches[0]);

        if ($link_count === 0) {
            $result['message'] = 'No links found. Add internal and external links.';
        } elseif ($link_count < 2) {
            $result['score'] = 60;
            $result['message'] = 'Few links found. Add more internal/external links.';
        } else {
            $result['score'] = 100;
            $result['status'] = 'success';
            $result['message'] = "Good number of links ({$link_count}).";
        }

        return $result;
    }

    /**
     * Generate suggestions
     */
    private static function generate_suggestions($checks) {
        $suggestions = array();

        foreach ($checks as $check_name => $check_data) {
            if (isset($check_data['status']) && $check_data['status'] !== 'success') {
                $suggestions[] = array(
                    'type' => $check_name,
                    'priority' => $check_data['status'] === 'error' ? 'high' : 'medium',
                    'message' => $check_data['message']
                );
            }
        }

        return $suggestions;
    }

    /**
     * Get content suggestions
     */
    public static function get_suggestions($request) {
        $keyword = sanitize_text_field($request->get_param('keyword'));

        // Generate LSI keywords and suggestions
        $suggestions = array(
            'lsi_keywords' => Audit_SEO_Content_Audit::get_lsi_keywords($keyword),
            'recommended_length' => '600-1500 words',
            'recommended_headings' => '3-5 headings',
            'recommended_links' => '2-4 internal links, 1-2 external links'
        );

        return rest_ensure_response($suggestions);
    }

    /**
     * Analyze content (raw - for AJAX calls)
     */
    public static function analyze_content_raw($title, $content, $focus_keyword = '') {
        $analysis = array(
            'score' => 0,
            'checks' => array(),
            'suggestions' => array()
        );

        // Analyze title
        $title_analysis = self::analyze_title($title, $focus_keyword);
        $analysis['checks']['title'] = $title_analysis;
        $analysis['score'] += $title_analysis['score'];

        // Analyze content length
        $content_analysis = self::analyze_content_length($content);
        $analysis['checks']['content_length'] = $content_analysis;
        $analysis['score'] += $content_analysis['score'];

        // Analyze keyword usage
        if (!empty($focus_keyword)) {
            $keyword_analysis = self::analyze_keyword($title, $content, $focus_keyword);
            $analysis['checks']['keyword'] = $keyword_analysis;
            $analysis['score'] += $keyword_analysis['score'];
        }

        // Analyze headings
        $heading_analysis = self::analyze_headings($content);
        $analysis['checks']['headings'] = $heading_analysis;
        $analysis['score'] += $heading_analysis['score'];

        // Analyze readability
        $readability_analysis = self::analyze_readability($content);
        $analysis['checks']['readability'] = $readability_analysis;
        $analysis['score'] += $readability_analysis['score'];

        // Analyze links
        $links_analysis = self::analyze_links($content);
        $analysis['checks']['links'] = $links_analysis;
        $analysis['score'] += $links_analysis['score'];

        // Calculate final score (out of 100)
        $total_checks = count($analysis['checks']);
        $analysis['score'] = $total_checks > 0 ? round($analysis['score'] / $total_checks) : 0;

        // Generate suggestions
        $analysis['suggestions'] = self::generate_suggestions($analysis['checks']);

        return $analysis;
    }
}

// Initialize
Audit_SEO_Gutenberg::init();
