<?php
/**
 * On-Page SEO Checker
 * Comprehensive checks for images, links, content structure, readability, and technical SEO
 */
class Audit_SEO_OnPage_Checker {

    /**
     * Run all on-page checks
     */
    public static function run_all_checks($post_id, $content = '', $title = '', $focus_keyword = '') {
        if (empty($content)) {
            $post = get_post($post_id);
            $content = $post->post_content;
            $title = $post->post_title;
        }

        $checks = array(
            'image_seo' => self::check_image_seo($post_id, $content, $focus_keyword),
            'linking' => self::check_linking($post_id, $content),
            'content_structure' => self::check_content_structure($content),
            'readability' => self::check_readability($content),
            'technical_seo' => self::check_technical_seo($post_id, $title, $content),
            'engagement' => self::check_engagement_elements($content)
        );

        // Calculate overall score
        $total_checks = 0;
        $passed_checks = 0;

        foreach ($checks as $category => $category_checks) {
            foreach ($category_checks as $check) {
                $total_checks++;
                if ($check['status'] === 'passed') {
                    $passed_checks++;
                }
            }
        }

        $score = $total_checks > 0 ? round(($passed_checks / $total_checks) * 100) : 0;

        return array(
            'score' => $score,
            'checks' => $checks,
            'total_checks' => $total_checks,
            'passed_checks' => $passed_checks
        );
    }

    /**
     * Check Image SEO
     */
    public static function check_image_seo($post_id, $content, $focus_keyword = '') {
        $checks = array();

        // Count images in content
        preg_match_all('/<img[^>]+>/i', $content, $images);
        $image_count = count($images[0]);

        // Check 1: Has images
        if ($image_count === 0) {
            $checks[] = array(
                'id' => 'no_images',
                'status' => 'warning',
                'priority' => 'medium',
                'message' => 'No images found in content',
                'recommendation' => 'Add at least 1-3 relevant images to improve engagement and SEO'
            );
        } else {
            $checks[] = array(
                'id' => 'has_images',
                'status' => 'passed',
                'priority' => 'low',
                'message' => "Found {$image_count} image(s) in content",
                'recommendation' => ''
            );
        }

        // Check 2: Featured image
        $featured_image = get_post_thumbnail_id($post_id);
        if (!$featured_image) {
            $checks[] = array(
                'id' => 'no_featured_image',
                'status' => 'error',
                'priority' => 'high',
                'message' => 'No featured image set',
                'recommendation' => 'Set a featured image for social sharing and better CTR'
            );
        } else {
            $checks[] = array(
                'id' => 'has_featured_image',
                'status' => 'passed',
                'priority' => 'low',
                'message' => 'Featured image is set',
                'recommendation' => ''
            );
        }

        // Check 3: Images with missing alt text
        $missing_alt = 0;
        $has_keyword_in_alt = false;

        foreach ($images[0] as $img) {
            if (!preg_match('/alt=["\']([^"\']*)["\']/', $img, $alt_match)) {
                $missing_alt++;
            } elseif (!empty($focus_keyword) && stripos($alt_match[1], $focus_keyword) !== false) {
                $has_keyword_in_alt = true;
            }
        }

        if ($missing_alt > 0) {
            $checks[] = array(
                'id' => 'missing_alt_text',
                'status' => 'error',
                'priority' => 'high',
                'message' => "{$missing_alt} image(s) missing alt text",
                'recommendation' => 'Add descriptive alt text to all images for accessibility and SEO'
            );
        } elseif ($image_count > 0) {
            $checks[] = array(
                'id' => 'all_images_have_alt',
                'status' => 'passed',
                'priority' => 'low',
                'message' => 'All images have alt text',
                'recommendation' => ''
            );
        }

        // Check 4: Focus keyword in alt text
        if (!empty($focus_keyword) && $image_count > 0 && !$has_keyword_in_alt) {
            $checks[] = array(
                'id' => 'no_keyword_in_alt',
                'status' => 'warning',
                'priority' => 'medium',
                'message' => 'Focus keyword not found in any image alt text',
                'recommendation' => "Add focus keyword '{$focus_keyword}' to at least one image alt text"
            );
        } elseif ($has_keyword_in_alt) {
            $checks[] = array(
                'id' => 'keyword_in_alt',
                'status' => 'passed',
                'priority' => 'low',
                'message' => 'Focus keyword found in image alt text',
                'recommendation' => ''
            );
        }

        return $checks;
    }

    /**
     * Check Internal/External Linking
     */
    public static function check_linking($post_id, $content) {
        $checks = array();

        // Get all links
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $links);
        $all_links = $links[1];

        $site_url = get_site_url();
        $internal_links = array();
        $external_links = array();

        foreach ($all_links as $link) {
            if (strpos($link, $site_url) !== false || strpos($link, '/') === 0) {
                $internal_links[] = $link;
            } elseif (strpos($link, 'http') === 0) {
                $external_links[] = $link;
            }
        }

        $internal_count = count($internal_links);
        $external_count = count($external_links);

        // Check 1: Has internal links
        if ($internal_count === 0) {
            $checks[] = array(
                'id' => 'no_internal_links',
                'status' => 'error',
                'priority' => 'high',
                'message' => 'No internal links found',
                'recommendation' => 'Add 2-5 internal links to related content to improve site structure and SEO'
            );
        } elseif ($internal_count < 2) {
            $checks[] = array(
                'id' => 'few_internal_links',
                'status' => 'warning',
                'priority' => 'medium',
                'message' => "Only {$internal_count} internal link found",
                'recommendation' => 'Add more internal links to related content (recommended: 2-5 links)'
            );
        } else {
            $checks[] = array(
                'id' => 'has_internal_links',
                'status' => 'passed',
                'priority' => 'low',
                'message' => "Found {$internal_count} internal link(s)",
                'recommendation' => ''
            );
        }

        // Check 2: Has external links
        if ($external_count === 0) {
            $checks[] = array(
                'id' => 'no_external_links',
                'status' => 'warning',
                'priority' => 'low',
                'message' => 'No external links found',
                'recommendation' => 'Add 1-2 external links to authoritative sources to increase credibility'
            );
        } else {
            $checks[] = array(
                'id' => 'has_external_links',
                'status' => 'passed',
                'priority' => 'low',
                'message' => "Found {$external_count} external link(s)",
                'recommendation' => ''
            );
        }

        // Check 3: Too many links (spam signal)
        $total_links = $internal_count + $external_count;
        if ($total_links > 100) {
            $checks[] = array(
                'id' => 'too_many_links',
                'status' => 'warning',
                'priority' => 'medium',
                'message' => "Too many links ({$total_links} total)",
                'recommendation' => 'Reduce number of links to avoid appearing spammy (recommended: <100 links)'
            );
        }

        return $checks;
    }

    /**
     * Check Content Structure
     */
    public static function check_content_structure($content) {
        $checks = array();

        // Strip HTML for text analysis
        $text = wp_strip_all_tags($content);
        $word_count = str_word_count($text);

        // Check 1: Has H2/H3 headings
        preg_match_all('/<h2[^>]*>.*?<\/h2>/is', $content, $h2_matches);
        preg_match_all('/<h3[^>]*>.*?<\/h3>/is', $content, $h3_matches);

        $h2_count = count($h2_matches[0]);
        $h3_count = count($h3_matches[0]);

        if ($h2_count === 0) {
            $checks[] = array(
                'id' => 'no_h2_headings',
                'status' => 'error',
                'priority' => 'high',
                'message' => 'No H2 headings found',
                'recommendation' => 'Add H2 headings to structure your content and improve readability'
            );
        } else {
            $checks[] = array(
                'id' => 'has_h2_headings',
                'status' => 'passed',
                'priority' => 'low',
                'message' => "Found {$h2_count} H2 heading(s)",
                'recommendation' => ''
            );
        }

        // Check 2: Paragraph length
        $paragraphs = explode("\n", $text);
        $long_paragraphs = 0;

        foreach ($paragraphs as $para) {
            $para_words = str_word_count($para);
            if ($para_words > 300) {
                $long_paragraphs++;
            }
        }

        if ($long_paragraphs > 0) {
            $checks[] = array(
                'id' => 'long_paragraphs',
                'status' => 'warning',
                'priority' => 'medium',
                'message' => "{$long_paragraphs} paragraph(s) are too long (>300 words)",
                'recommendation' => 'Break long paragraphs into shorter ones for better readability'
            );
        } else {
            $checks[] = array(
                'id' => 'good_paragraph_length',
                'status' => 'passed',
                'priority' => 'low',
                'message' => 'Paragraph lengths are good',
                'recommendation' => ''
            );
        }

        // Check 3: Sentence length
        $sentences = preg_split('/[.!?]+/', $text);
        $long_sentences = 0;

        foreach ($sentences as $sentence) {
            $sentence_words = str_word_count(trim($sentence));
            if ($sentence_words > 25) {
                $long_sentences++;
            }
        }

        if ($long_sentences > count($sentences) * 0.25) { // More than 25% long sentences
            $checks[] = array(
                'id' => 'long_sentences',
                'status' => 'warning',
                'priority' => 'low',
                'message' => "Too many long sentences ({$long_sentences} sentences >25 words)",
                'recommendation' => 'Shorten some sentences to improve readability'
            );
        } else {
            $checks[] = array(
                'id' => 'good_sentence_length',
                'status' => 'passed',
                'priority' => 'low',
                'message' => 'Sentence lengths are good',
                'recommendation' => ''
            );
        }

        // Check 4: Lists/bullet points
        preg_match_all('/<(ul|ol)[^>]*>.*?<\/(ul|ol)>/is', $content, $lists);
        $list_count = count($lists[0]);

        if ($word_count > 500 && $list_count === 0) {
            $checks[] = array(
                'id' => 'no_lists',
                'status' => 'warning',
                'priority' => 'low',
                'message' => 'No lists or bullet points found',
                'recommendation' => 'Add lists to break up text and improve scannability'
            );
        } elseif ($list_count > 0) {
            $checks[] = array(
                'id' => 'has_lists',
                'status' => 'passed',
                'priority' => 'low',
                'message' => "Found {$list_count} list(s)",
                'recommendation' => ''
            );
        }

        // Check 5: Table of contents for long content
        if ($word_count > 2000) {
            $has_toc = stripos($content, 'table of contents') !== false ||
                       stripos($content, 'toc') !== false;

            if (!$has_toc) {
                $checks[] = array(
                    'id' => 'no_toc',
                    'status' => 'warning',
                    'priority' => 'low',
                    'message' => 'Long article without table of contents',
                    'recommendation' => 'Add a table of contents for articles >2000 words to improve user experience'
                );
            }
        }

        return $checks;
    }

    /**
     * Check Readability
     */
    public static function check_readability($content) {
        $checks = array();

        // Strip HTML for text analysis
        $text = wp_strip_all_tags($content);

        // Calculate Flesch Reading Ease
        $flesch_score = self::calculate_flesch_reading_ease($text);

        if ($flesch_score < 50) {
            $checks[] = array(
                'id' => 'low_readability',
                'status' => 'warning',
                'priority' => 'medium',
                'message' => "Readability score is low ({$flesch_score}/100)",
                'recommendation' => 'Simplify language, use shorter sentences, and avoid complex words'
            );
        } elseif ($flesch_score < 70) {
            $checks[] = array(
                'id' => 'medium_readability',
                'status' => 'passed',
                'priority' => 'low',
                'message' => "Readability score is fair ({$flesch_score}/100)",
                'recommendation' => ''
            );
        } else {
            $checks[] = array(
                'id' => 'good_readability',
                'status' => 'passed',
                'priority' => 'low',
                'message' => "Readability score is good ({$flesch_score}/100)",
                'recommendation' => ''
            );
        }

        // Check for passive voice
        $passive_count = self::count_passive_voice($text);
        $sentence_count = count(preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY));

        if ($sentence_count > 0) {
            $passive_percentage = ($passive_count / $sentence_count) * 100;

            if ($passive_percentage > 10) {
                $checks[] = array(
                    'id' => 'too_much_passive_voice',
                    'status' => 'warning',
                    'priority' => 'low',
                    'message' => sprintf('%.1f%% passive voice (recommended: <10%%)', $passive_percentage),
                    'recommendation' => 'Use active voice more often for clearer, more engaging writing'
                );
            } else {
                $checks[] = array(
                    'id' => 'good_passive_voice',
                    'status' => 'passed',
                    'priority' => 'low',
                    'message' => sprintf('%.1f%% passive voice', $passive_percentage),
                    'recommendation' => ''
                );
            }
        }

        // Check for transition words
        $transition_words = array(
            'however', 'therefore', 'moreover', 'furthermore', 'consequently',
            'meanwhile', 'likewise', 'additionally', 'nonetheless', 'nevertheless',
            'first', 'second', 'finally', 'in conclusion', 'for example',
            'in addition', 'on the other hand', 'as a result', 'in fact'
        );

        $has_transitions = false;
        foreach ($transition_words as $word) {
            if (stripos($text, $word) !== false) {
                $has_transitions = true;
                break;
            }
        }

        if (!$has_transitions && str_word_count($text) > 300) {
            $checks[] = array(
                'id' => 'no_transition_words',
                'status' => 'warning',
                'priority' => 'low',
                'message' => 'Few transition words found',
                'recommendation' => 'Use transition words to improve flow (e.g., however, therefore, moreover)'
            );
        } elseif ($has_transitions) {
            $checks[] = array(
                'id' => 'has_transition_words',
                'status' => 'passed',
                'priority' => 'low',
                'message' => 'Transition words found',
                'recommendation' => ''
            );
        }

        return $checks;
    }

    /**
     * Check Technical SEO
     */
    public static function check_technical_seo($post_id, $title, $content) {
        $checks = array();

        // Check 1: URL length
        $permalink = get_permalink($post_id);
        $url_length = strlen($permalink);

        if ($url_length > 75) {
            $checks[] = array(
                'id' => 'long_url',
                'status' => 'warning',
                'priority' => 'medium',
                'message' => "URL is too long ({$url_length} characters)",
                'recommendation' => 'Shorten URL to <75 characters for better SEO and user experience'
            );
        } else {
            $checks[] = array(
                'id' => 'good_url_length',
                'status' => 'passed',
                'priority' => 'low',
                'message' => 'URL length is good',
                'recommendation' => ''
            );
        }

        // Check 2: URL structure (special characters)
        $post_slug = basename($permalink);
        if (preg_match('/[^a-z0-9\-_]/', $post_slug)) {
            $checks[] = array(
                'id' => 'special_chars_in_url',
                'status' => 'warning',
                'priority' => 'medium',
                'message' => 'URL contains special characters',
                'recommendation' => 'Use only lowercase letters, numbers, and hyphens in URLs'
            );
        } else {
            $checks[] = array(
                'id' => 'clean_url',
                'status' => 'passed',
                'priority' => 'low',
                'message' => 'URL structure is clean',
                'recommendation' => ''
            );
        }

        // Check 3: Meta description
        $meta_desc = get_post_meta($post_id, '_audit_seo_meta_description', true);

        if (empty($meta_desc)) {
            $checks[] = array(
                'id' => 'no_meta_description',
                'status' => 'error',
                'priority' => 'high',
                'message' => 'No meta description set',
                'recommendation' => 'Add a meta description (120-160 characters) to improve CTR'
            );
        } else {
            $desc_length = strlen($meta_desc);
            if ($desc_length < 120 || $desc_length > 160) {
                $checks[] = array(
                    'id' => 'meta_desc_length',
                    'status' => 'warning',
                    'priority' => 'medium',
                    'message' => "Meta description length is {$desc_length} chars (recommended: 120-160)",
                    'recommendation' => 'Optimize meta description length for better display in search results'
                );
            } else {
                $checks[] = array(
                    'id' => 'good_meta_desc',
                    'status' => 'passed',
                    'priority' => 'low',
                    'message' => 'Meta description is optimized',
                    'recommendation' => ''
                );
            }
        }

        // Check 4: Content length
        $word_count = str_word_count(wp_strip_all_tags($content));

        if ($word_count < 300) {
            $checks[] = array(
                'id' => 'thin_content',
                'status' => 'error',
                'priority' => 'high',
                'message' => "Content is too short ({$word_count} words)",
                'recommendation' => 'Add more content (recommended: 300+ words minimum, 1000+ for competitive keywords)'
            );
        } elseif ($word_count < 600) {
            $checks[] = array(
                'id' => 'short_content',
                'status' => 'warning',
                'priority' => 'medium',
                'message' => "Content could be longer ({$word_count} words)",
                'recommendation' => 'Consider expanding to 1000+ words for better rankings'
            );
        } else {
            $checks[] = array(
                'id' => 'good_content_length',
                'status' => 'passed',
                'priority' => 'low',
                'message' => "{$word_count} words - good content length",
                'recommendation' => ''
            );
        }

        return $checks;
    }

    /**
     * Check Engagement Elements
     */
    public static function check_engagement_elements($content) {
        $checks = array();

        // Check 1: Call-to-action
        $cta_keywords = array(
            'click here', 'learn more', 'read more', 'download', 'subscribe',
            'sign up', 'get started', 'try now', 'contact us', 'buy now',
            'shop now', 'register', 'join', 'share'
        );

        $has_cta = false;
        foreach ($cta_keywords as $cta) {
            if (stripos($content, $cta) !== false) {
                $has_cta = true;
                break;
            }
        }

        if (!$has_cta) {
            $checks[] = array(
                'id' => 'no_cta',
                'status' => 'warning',
                'priority' => 'medium',
                'message' => 'No clear call-to-action found',
                'recommendation' => 'Add a CTA to guide users (e.g., "Learn more", "Subscribe", "Contact us")'
            );
        } else {
            $checks[] = array(
                'id' => 'has_cta',
                'status' => 'passed',
                'priority' => 'low',
                'message' => 'Call-to-action found',
                'recommendation' => ''
            );
        }

        // Check 2: Video embeds
        $has_video = preg_match('/<iframe[^>]+youtube|vimeo|video/i', $content) ||
                     preg_match('/<video[^>]*>/i', $content);

        if (!$has_video) {
            $checks[] = array(
                'id' => 'no_video',
                'status' => 'info',
                'priority' => 'low',
                'message' => 'No video content found',
                'recommendation' => 'Consider adding video content to increase engagement and time on page'
            );
        } else {
            $checks[] = array(
                'id' => 'has_video',
                'status' => 'passed',
                'priority' => 'low',
                'message' => 'Video content found',
                'recommendation' => ''
            );
        }

        // Check 3: FAQ/Schema markup hints
        $has_faq = preg_match('/(<h\d[^>]*>.*?\?.*?<\/h\d>)/i', $content);

        if ($has_faq) {
            $checks[] = array(
                'id' => 'has_questions',
                'status' => 'info',
                'priority' => 'low',
                'message' => 'Question headings found - good for FAQ schema',
                'recommendation' => 'Consider adding FAQ schema markup for rich snippets'
            );
        }

        return $checks;
    }

    /**
     * Calculate Flesch Reading Ease score
     */
    private static function calculate_flesch_reading_ease($text) {
        $text = preg_replace('/\s+/', ' ', trim($text));

        // Count syllables (simplified)
        $syllables = 0;
        $words = str_word_count($text, 1);
        foreach ($words as $word) {
            $syllables += self::count_syllables($word);
        }

        $word_count = count($words);
        $sentence_count = count(preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY));

        if ($word_count === 0 || $sentence_count === 0) {
            return 0;
        }

        $avg_syllables_per_word = $syllables / $word_count;
        $avg_words_per_sentence = $word_count / $sentence_count;

        $flesch = 206.835 - (1.015 * $avg_words_per_sentence) - (84.6 * $avg_syllables_per_word);

        return max(0, min(100, round($flesch)));
    }

    /**
     * Count syllables in a word (simplified)
     */
    private static function count_syllables($word) {
        $word = strtolower($word);
        $syllables = preg_match_all('/[aeiouy]+/', $word, $matches);

        // Adjust for silent e
        if (substr($word, -1) === 'e') {
            $syllables--;
        }

        return max(1, $syllables);
    }

    /**
     * Count passive voice instances
     */
    private static function count_passive_voice($text) {
        $passive_indicators = array(
            'was', 'were', 'been', 'being',
            'is', 'are', 'am'
        );

        $count = 0;
        foreach ($passive_indicators as $indicator) {
            $count += substr_count(strtolower($text), ' ' . $indicator . ' ');
        }

        return $count;
    }

    /**
     * Get results for a post
     */
    public static function get_results($post_id) {
        $results = get_post_meta($post_id, '_audit_seo_onpage_check', true);

        if (empty($results)) {
            return array();
        }

        return $results;
    }

    /**
     * Save results to post meta
     */
    public static function save_results($post_id, $results) {
        update_post_meta($post_id, '_audit_seo_onpage_check', $results);
    }
}
