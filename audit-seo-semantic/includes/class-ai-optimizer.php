<?php
/**
 * AI Content Optimizer
 * Advanced content optimization with intelligent suggestions
 */
class Audit_SEO_AI_Optimizer {

    /**
     * Generate LSI keywords based on topic analysis
     */
    public static function generate_lsi_keywords($keyword, $content = '') {
        $lsi_keywords = array();

        // Advanced keyword database
        $keyword_database = array(
            'seo' => array(
                'primary' => array('search engine optimization', 'organic traffic', 'SERP ranking'),
                'secondary' => array('meta description', 'title tag', 'alt text', 'internal linking', 'backlinks'),
                'related' => array('google analytics', 'keyword research', 'content marketing', 'on-page SEO', 'off-page SEO')
            ),
            'wordpress' => array(
                'primary' => array('CMS', 'plugin', 'theme', 'gutenberg'),
                'secondary' => array('shortcode', 'widget', 'custom post type', 'taxonomy'),
                'related' => array('WooCommerce', 'multisite', 'REST API', 'hooks', 'filters')
            ),
            'marketing' => array(
                'primary' => array('digital marketing', 'content strategy', 'brand awareness'),
                'secondary' => array('conversion rate', 'lead generation', 'customer engagement'),
                'related' => array('social media', 'email marketing', 'paid advertising', 'analytics')
            ),
            'content' => array(
                'primary' => array('blog post', 'article writing', 'copywriting'),
                'secondary' => array('headline', 'call to action', 'storytelling'),
                'related' => array('user intent', 'audience targeting', 'engagement metrics')
            ),
            'web design' => array(
                'primary' => array('UI/UX', 'responsive design', 'user experience'),
                'secondary' => array('navigation', 'layout', 'typography', 'color scheme'),
                'related' => array('mobile-first', 'accessibility', 'wireframe', 'prototype')
            )
        );

        $keyword_lower = strtolower($keyword);

        // Find matching keywords from database
        foreach ($keyword_database as $topic => $keywords) {
            if (stripos($keyword_lower, $topic) !== false) {
                $lsi_keywords = array_merge(
                    $keywords['primary'],
                    $keywords['secondary'],
                    $keywords['related']
                );
                break;
            }
        }

        // Extract keywords from content if available
        if (!empty($content)) {
            $content_keywords = self::extract_keywords_from_content($content);
            $lsi_keywords = array_merge($lsi_keywords, $content_keywords);
        }

        // Add semantic variations
        $lsi_keywords = array_merge($lsi_keywords, self::generate_semantic_variations($keyword));

        return array_unique($lsi_keywords);
    }

    /**
     * Extract keywords from content using TF-IDF approach
     */
    private static function extract_keywords_from_content($content) {
        $keywords = array();

        // Remove HTML tags
        $clean_content = wp_strip_all_tags($content);

        // Split into words
        $words = preg_split('/\s+/', strtolower($clean_content));

        // Common stop words
        $stop_words = array(
            'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
            'of', 'as', 'by', 'with', 'from', 'is', 'was', 'are', 'were', 'be',
            'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'should',
            'can', 'could', 'may', 'might', 'this', 'that', 'these', 'those'
        );

        // Count word frequency
        $word_freq = array();
        foreach ($words as $word) {
            $word = trim($word, '.,;:!?"\'');
            if (strlen($word) > 3 && !in_array($word, $stop_words)) {
                if (!isset($word_freq[$word])) {
                    $word_freq[$word] = 0;
                }
                $word_freq[$word]++;
            }
        }

        // Sort by frequency and get top words
        arsort($word_freq);
        $keywords = array_slice(array_keys($word_freq), 0, 10);

        return $keywords;
    }

    /**
     * Generate semantic variations of a keyword
     */
    private static function generate_semantic_variations($keyword) {
        $variations = array();

        // Add plural/singular forms
        if (substr($keyword, -1) === 's') {
            $variations[] = substr($keyword, 0, -1);
        } else {
            $variations[] = $keyword . 's';
        }

        // Add common prefixes/suffixes
        $prefixes = array('best', 'top', 'how to', 'guide to', 'learn');
        $suffixes = array('tips', 'guide', 'tutorial', 'examples', 'strategies');

        foreach ($prefixes as $prefix) {
            $variations[] = $prefix . ' ' . $keyword;
        }

        foreach ($suffixes as $suffix) {
            $variations[] = $keyword . ' ' . $suffix;
        }

        return $variations;
    }

    /**
     * Analyze content and provide optimization suggestions
     */
    public static function optimize_content($post_id, $focus_keyword = '') {
        $post = get_post($post_id);
        if (!$post) {
            return array('error' => 'Post not found');
        }

        $content = wp_strip_all_tags($post->post_content);
        $title = get_the_title($post_id);

        $suggestions = array(
            'title_suggestions' => array(),
            'content_suggestions' => array(),
            'keyword_suggestions' => array(),
            'structure_suggestions' => array(),
            'readability_suggestions' => array()
        );

        // Title optimization
        $suggestions['title_suggestions'] = self::suggest_title_improvements($title, $focus_keyword);

        // Content optimization
        $suggestions['content_suggestions'] = self::suggest_content_improvements($content, $focus_keyword);

        // Keyword optimization
        if (!empty($focus_keyword)) {
            $suggestions['keyword_suggestions'] = self::suggest_keyword_improvements($content, $focus_keyword);
        }

        // Structure optimization
        $suggestions['structure_suggestions'] = self::suggest_structure_improvements($post->post_content);

        // Readability optimization
        $suggestions['readability_suggestions'] = self::suggest_readability_improvements($content);

        // Generate priority score for each suggestion
        $suggestions = self::prioritize_suggestions($suggestions);

        return $suggestions;
    }

    /**
     * Suggest title improvements
     */
    private static function suggest_title_improvements($title, $keyword = '') {
        $suggestions = array();

        $title_length = strlen($title);

        if ($title_length < 30) {
            $suggestions[] = array(
                'type' => 'length',
                'priority' => 'high',
                'message' => 'Title is too short. Expand to 30-60 characters for better SEO.',
                'suggestion' => 'Add descriptive words or include year/numbers to make it more specific.'
            );
        } elseif ($title_length > 60) {
            $suggestions[] = array(
                'type' => 'length',
                'priority' => 'high',
                'message' => 'Title is too long. Shorten to under 60 characters.',
                'suggestion' => 'Remove filler words and focus on core message.'
            );
        }

        // Check for keyword at beginning
        if (!empty($keyword) && stripos($title, $keyword) !== 0) {
            $suggestions[] = array(
                'type' => 'keyword_position',
                'priority' => 'medium',
                'message' => 'Place focus keyword at the beginning of title for better SEO.',
                'suggestion' => 'Restructure title to start with "' . $keyword . '"'
            );
        }

        // Check for power words
        $power_words = array('ultimate', 'complete', 'essential', 'proven', 'effective', 'best', 'top');
        $has_power_word = false;
        foreach ($power_words as $word) {
            if (stripos($title, $word) !== false) {
                $has_power_word = true;
                break;
            }
        }

        if (!$has_power_word) {
            $suggestions[] = array(
                'type' => 'engagement',
                'priority' => 'low',
                'message' => 'Add power words to increase click-through rate.',
                'suggestion' => 'Consider words like: ' . implode(', ', $power_words)
            );
        }

        // Check for numbers
        if (!preg_match('/\d+/', $title)) {
            $suggestions[] = array(
                'type' => 'numbers',
                'priority' => 'low',
                'message' => 'Add numbers to make title more compelling.',
                'suggestion' => 'e.g., "7 Ways...", "10 Tips...", "2024 Guide..."'
            );
        }

        return $suggestions;
    }

    /**
     * Suggest content improvements
     */
    private static function suggest_content_improvements($content, $keyword = '') {
        $suggestions = array();

        $word_count = str_word_count($content);

        if ($word_count < 300) {
            $suggestions[] = array(
                'type' => 'length',
                'priority' => 'high',
                'message' => "Content is too short ({$word_count} words). Add more valuable information.",
                'suggestion' => 'Aim for at least 600 words. Add examples, case studies, or detailed explanations.'
            );
        } elseif ($word_count > 3000) {
            $suggestions[] = array(
                'type' => 'length',
                'priority' => 'medium',
                'message' => "Content is very long ({$word_count} words). Consider splitting into multiple posts.",
                'suggestion' => 'Break into series or add table of contents for easier navigation.'
            );
        }

        // Check first paragraph
        $first_para = substr($content, 0, 200);
        if (!empty($keyword) && stripos($first_para, $keyword) === false) {
            $suggestions[] = array(
                'type' => 'keyword_placement',
                'priority' => 'high',
                'message' => 'Focus keyword not found in first paragraph.',
                'suggestion' => 'Mention your keyword naturally in the opening paragraph.'
            );
        }

        return $suggestions;
    }

    /**
     * Suggest keyword improvements
     */
    private static function suggest_keyword_improvements($content, $keyword) {
        $suggestions = array();

        $word_count = str_word_count($content);
        $keyword_count = substr_count(strtolower($content), strtolower($keyword));
        $density = $word_count > 0 ? ($keyword_count / $word_count) * 100 : 0;

        if ($density < 0.5) {
            $suggestions[] = array(
                'type' => 'density',
                'priority' => 'high',
                'message' => "Keyword density too low ({$density}%).",
                'suggestion' => 'Use keyword ' . ceil((0.5 * $word_count / 100) - $keyword_count) . ' more times naturally.'
            );
        } elseif ($density > 3) {
            $suggestions[] = array(
                'type' => 'density',
                'priority' => 'high',
                'message' => "Keyword density too high ({$density}%). Risk of keyword stuffing.",
                'suggestion' => 'Use synonyms and LSI keywords instead.'
            );
        }

        // Suggest LSI keywords
        $lsi = self::generate_lsi_keywords($keyword, $content);
        $missing_lsi = array();

        foreach ($lsi as $lsi_keyword) {
            if (stripos($content, $lsi_keyword) === false) {
                $missing_lsi[] = $lsi_keyword;
            }
        }

        if (!empty($missing_lsi)) {
            $suggestions[] = array(
                'type' => 'lsi_keywords',
                'priority' => 'medium',
                'message' => 'Missing related keywords. Add semantic variations.',
                'suggestion' => 'Consider adding: ' . implode(', ', array_slice($missing_lsi, 0, 5))
            );
        }

        return $suggestions;
    }

    /**
     * Suggest structure improvements
     */
    private static function suggest_structure_improvements($content) {
        $suggestions = array();

        // Check headings
        preg_match_all('/<h([1-6])[^>]*>/i', $content, $heading_matches);
        $heading_count = count($heading_matches[0]);

        if ($heading_count < 3) {
            $suggestions[] = array(
                'type' => 'headings',
                'priority' => 'high',
                'message' => 'Add more headings to structure your content.',
                'suggestion' => 'Use H2 and H3 tags to break content into scannable sections.'
            );
        }

        // Check for lists
        $has_lists = preg_match('/<[ou]l[^>]*>/i', $content);
        if (!$has_lists) {
            $suggestions[] = array(
                'type' => 'formatting',
                'priority' => 'medium',
                'message' => 'Add bullet points or numbered lists.',
                'suggestion' => 'Lists make content more readable and scannable.'
            );
        }

        // Check for images
        preg_match_all('/<img[^>]+>/i', $content, $image_matches);
        $image_count = count($image_matches[0]);

        if ($image_count === 0) {
            $suggestions[] = array(
                'type' => 'media',
                'priority' => 'medium',
                'message' => 'Add images to break up text and improve engagement.',
                'suggestion' => 'Include at least 1-2 relevant images with proper alt text.'
            );
        }

        return $suggestions;
    }

    /**
     * Suggest readability improvements
     */
    private static function suggest_readability_improvements($content) {
        $suggestions = array();

        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $word_count = str_word_count($content);

        if ($sentence_count > 0) {
            $avg_sentence_length = $word_count / $sentence_count;

            if ($avg_sentence_length > 25) {
                $suggestions[] = array(
                    'type' => 'sentence_length',
                    'priority' => 'medium',
                    'message' => 'Sentences are too long (average ' . round($avg_sentence_length) . ' words).',
                    'suggestion' => 'Break long sentences into shorter ones. Aim for 15-20 words per sentence.'
                );
            }
        }

        // Check paragraph length
        $paragraphs = explode("\n\n", $content);
        $long_paragraphs = 0;

        foreach ($paragraphs as $para) {
            if (str_word_count($para) > 150) {
                $long_paragraphs++;
            }
        }

        if ($long_paragraphs > 0) {
            $suggestions[] = array(
                'type' => 'paragraph_length',
                'priority' => 'medium',
                'message' => "{$long_paragraphs} paragraph(s) are too long.",
                'suggestion' => 'Break paragraphs at 100-150 words for better readability.'
            );
        }

        return $suggestions;
    }

    /**
     * Prioritize suggestions
     */
    private static function prioritize_suggestions($all_suggestions) {
        $prioritized = array(
            'critical' => array(),
            'high' => array(),
            'medium' => array(),
            'low' => array()
        );

        foreach ($all_suggestions as $category => $suggestions) {
            foreach ($suggestions as $suggestion) {
                $priority = $suggestion['priority'];
                $suggestion['category'] = $category;
                $prioritized[$priority][] = $suggestion;
            }
        }

        return $prioritized;
    }

    /**
     * Generate content outline based on keyword
     */
    public static function generate_content_outline($keyword) {
        // This would ideally use an API or ML model
        // For now, providing a template-based approach

        $outline = array(
            'title_suggestions' => array(
                "The Ultimate Guide to {$keyword}",
                "How to Master {$keyword} in 2024",
                "10 Proven {$keyword} Strategies That Work",
                "{$keyword}: Everything You Need to Know"
            ),
            'sections' => array(
                array(
                    'heading' => "What is {$keyword}?",
                    'description' => 'Define the topic and explain why it matters'
                ),
                array(
                    'heading' => "Why {$keyword} Matters",
                    'description' => 'Explain the benefits and importance'
                ),
                array(
                    'heading' => "How to Get Started with {$keyword}",
                    'description' => 'Step-by-step guide for beginners'
                ),
                array(
                    'heading' => "Advanced {$keyword} Techniques",
                    'description' => 'Expert tips and strategies'
                ),
                array(
                    'heading' => "Common {$keyword} Mistakes to Avoid",
                    'description' => 'What not to do'
                ),
                array(
                    'heading' => "Conclusion",
                    'description' => 'Summary and call to action'
                )
            ),
            'recommended_length' => '1500-2000 words',
            'target_keywords' => self::generate_lsi_keywords($keyword)
        );

        return $outline;
    }
}
