<?php
/**
 * Content SEO Audit Class
 */
class Audit_SEO_Content_Audit {

    /**
     * Run content audit for a post
     */
    public static function run_audit($post_id, $focus_keyword = '') {
        $post = get_post($post_id);
        if (!$post) {
            return array('error' => 'Post not found');
        }

        $results = array(
            'score' => 0,
            'max_score' => 100,
            'issues' => array(),
            'passed' => array(),
            'warnings' => array(),
            'stats' => array()
        );

        // Get content
        $content = wp_strip_all_tags($post->post_content);
        $title = get_the_title($post->ID);

        // Basic stats
        $results['stats'] = self::get_content_stats($content);

        // Check content length
        $length_results = self::check_content_length($content);
        $results = self::merge_results($results, $length_results);

        // Check readability
        $readability_results = self::check_readability($content);
        $results = self::merge_results($results, $readability_results);

        // Check keyword optimization (if keyword provided)
        if (!empty($focus_keyword)) {
            $keyword_results = self::check_keyword_optimization($post, $content, $title, $focus_keyword);
            $results = self::merge_results($results, $keyword_results);
        }

        // Check paragraph structure
        $paragraph_results = self::check_paragraph_structure($post->post_content);
        $results = self::merge_results($results, $paragraph_results);

        // Check internal links
        $link_results = self::check_internal_links($post);
        $results = self::merge_results($results, $link_results);

        // Check external links
        $external_link_results = self::check_external_links($post);
        $results = self::merge_results($results, $external_link_results);

        // Calculate final score
        $results['score'] = round(($results['score'] / $results['max_score']) * 100);

        return $results;
    }

    /**
     * Get content statistics
     */
    public static function get_content_stats($content) {
        $words = str_word_count($content);
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);

        return array(
            'word_count' => $words,
            'character_count' => strlen($content),
            'sentence_count' => $sentence_count,
            'avg_sentence_length' => $sentence_count > 0 ? round($words / $sentence_count, 1) : 0,
            'paragraphs' => substr_count($content, "\n\n") + 1
        );
    }

    /**
     * Check content length
     */
    private static function check_content_length($content) {
        $results = array('score' => 0, 'max_score' => 15, 'issues' => array(), 'passed' => array());

        $word_count = str_word_count($content);

        if ($word_count < 300) {
            $results['issues'][] = 'Content is too short (' . $word_count . ' words). Recommended minimum: 300 words';
        } elseif ($word_count < 600) {
            $results['score'] += 8;
            $results['warnings'][] = 'Content length is acceptable but could be longer (' . $word_count . ' words)';
        } else {
            $results['score'] += 15;
            $results['passed'][] = 'Content length is good (' . $word_count . ' words)';
        }

        return $results;
    }

    /**
     * Check readability (Flesch Reading Ease)
     */
    private static function check_readability($content) {
        $results = array('score' => 0, 'max_score' => 15, 'issues' => array(), 'passed' => array());

        $word_count = str_word_count($content);
        if ($word_count === 0) {
            $results['issues'][] = 'No content to analyze';
            return $results;
        }

        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);

        $syllable_count = self::count_syllables($content);

        // Flesch Reading Ease formula
        $flesch_score = 206.835 - 1.015 * ($word_count / $sentence_count) - 84.6 * ($syllable_count / $word_count);
        $flesch_score = round($flesch_score, 1);

        $results['stats']['readability_score'] = $flesch_score;

        if ($flesch_score >= 60) {
            $results['score'] += 15;
            $results['passed'][] = 'Readability score is good (' . $flesch_score . ')';
        } elseif ($flesch_score >= 40) {
            $results['score'] += 10;
            $results['warnings'][] = 'Readability score is acceptable (' . $flesch_score . ')';
        } else {
            $results['issues'][] = 'Content is difficult to read (' . $flesch_score . '). Try shorter sentences.';
        }

        return $results;
    }

    /**
     * Check keyword optimization
     */
    private static function check_keyword_optimization($post, $content, $title, $keyword) {
        $results = array('score' => 0, 'max_score' => 30, 'issues' => array(), 'passed' => array());

        $keyword_lower = strtolower($keyword);
        $content_lower = strtolower($content);
        $title_lower = strtolower($title);

        // Check keyword in title
        if (strpos($title_lower, $keyword_lower) !== false) {
            $results['score'] += 10;
            $results['passed'][] = 'Focus keyword found in title';
        } else {
            $results['issues'][] = 'Focus keyword not found in title';
        }

        // Check keyword density
        $word_count = str_word_count($content);
        $keyword_count = substr_count($content_lower, $keyword_lower);
        $keyword_density = $word_count > 0 ? ($keyword_count / $word_count) * 100 : 0;

        $results['stats']['keyword_count'] = $keyword_count;
        $results['stats']['keyword_density'] = round($keyword_density, 2);

        if ($keyword_density >= 0.5 && $keyword_density <= 2.5) {
            $results['score'] += 10;
            $results['passed'][] = 'Keyword density is optimal (' . round($keyword_density, 2) . '%)';
        } elseif ($keyword_density < 0.5) {
            $results['issues'][] = 'Keyword density too low (' . round($keyword_density, 2) . '%). Use keyword more.';
        } else {
            $results['issues'][] = 'Keyword density too high (' . round($keyword_density, 2) . '%). Risk of keyword stuffing.';
        }

        // Check keyword in first paragraph
        $first_paragraph = substr($content, 0, 200);
        if (stripos($first_paragraph, $keyword) !== false) {
            $results['score'] += 10;
            $results['passed'][] = 'Focus keyword found in first paragraph';
        } else {
            $results['issues'][] = 'Focus keyword not found in first paragraph';
        }

        return $results;
    }

    /**
     * Check paragraph structure
     */
    private static function check_paragraph_structure($content) {
        $results = array('score' => 0, 'max_score' => 10, 'issues' => array(), 'passed' => array());

        // Remove HTML tags except <p>
        $content_with_p = strip_tags($content, '<p>');
        $paragraphs = explode('</p>', $content_with_p);
        $long_paragraphs = 0;

        foreach ($paragraphs as $paragraph) {
            $word_count = str_word_count(strip_tags($paragraph));
            if ($word_count > 150) {
                $long_paragraphs++;
            }
        }

        if ($long_paragraphs === 0) {
            $results['score'] += 10;
            $results['passed'][] = 'Paragraph lengths are appropriate';
        } else {
            $results['issues'][] = $long_paragraphs . ' paragraph(s) are too long (>150 words)';
        }

        return $results;
    }

    /**
     * Check internal links
     */
    private static function check_internal_links($post) {
        $results = array('score' => 0, 'max_score' => 10, 'issues' => array(), 'passed' => array());

        $content = $post->post_content;
        $site_url = get_site_url();

        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);
        $internal_links = 0;

        foreach ($matches[1] as $url) {
            if (strpos($url, $site_url) !== false || strpos($url, '/') === 0) {
                $internal_links++;
            }
        }

        $results['stats']['internal_links'] = $internal_links;

        if ($internal_links >= 2) {
            $results['score'] += 10;
            $results['passed'][] = 'Good number of internal links (' . $internal_links . ')';
        } elseif ($internal_links === 1) {
            $results['score'] += 5;
            $results['warnings'][] = 'Only 1 internal link found. Add more for better SEO.';
        } else {
            $results['issues'][] = 'No internal links found. Add links to related content.';
        }

        return $results;
    }

    /**
     * Check external links
     */
    private static function check_external_links($post) {
        $results = array('score' => 0, 'max_score' => 10, 'issues' => array(), 'passed' => array());

        $content = $post->post_content;
        $site_url = get_site_url();

        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);
        $external_links = 0;

        foreach ($matches[1] as $url) {
            if (strpos($url, 'http') === 0 && strpos($url, $site_url) === false) {
                $external_links++;
            }
        }

        $results['stats']['external_links'] = $external_links;

        if ($external_links >= 1) {
            $results['score'] += 10;
            $results['passed'][] = 'External links found (' . $external_links . ')';
        } else {
            $results['warnings'][] = 'No external links. Consider linking to authoritative sources.';
            $results['score'] += 5;
        }

        return $results;
    }

    /**
     * Count syllables (simplified)
     */
    private static function count_syllables($text) {
        $words = str_word_count(strtolower($text), 1);
        $syllable_count = 0;

        foreach ($words as $word) {
            $syllable_count += self::count_word_syllables($word);
        }

        return $syllable_count;
    }

    /**
     * Count syllables in a word (simplified)
     */
    private static function count_word_syllables($word) {
        $word = strtolower($word);
        $syllables = 0;
        $vowels = array('a', 'e', 'i', 'o', 'u', 'y');
        $previous_was_vowel = false;

        for ($i = 0; $i < strlen($word); $i++) {
            $is_vowel = in_array($word[$i], $vowels);
            if ($is_vowel && !$previous_was_vowel) {
                $syllables++;
            }
            $previous_was_vowel = $is_vowel;
        }

        // Adjust for silent e
        if (substr($word, -1) === 'e') {
            $syllables--;
        }

        // Every word has at least one syllable
        return max(1, $syllables);
    }

    /**
     * Get LSI keyword suggestions
     */
    public static function get_lsi_keywords($keyword) {
        // This is a simplified version. In production, you'd use an API or more sophisticated method
        $lsi_keywords = array();

        // Basic related terms (you can expand this or use an API)
        $common_relations = array(
            'seo' => array('optimization', 'search engine', 'ranking', 'keywords', 'backlinks'),
            'content' => array('article', 'blog', 'writing', 'text', 'post'),
            'marketing' => array('advertising', 'promotion', 'strategy', 'campaign', 'digital')
        );

        $keyword_lower = strtolower($keyword);
        foreach ($common_relations as $term => $related) {
            if (strpos($keyword_lower, $term) !== false) {
                $lsi_keywords = array_merge($lsi_keywords, $related);
            }
        }

        return array_unique($lsi_keywords);
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
        if (isset($new['stats'])) {
            $base['stats'] = array_merge($base['stats'], $new['stats']);
        }
        return $base;
    }
}
