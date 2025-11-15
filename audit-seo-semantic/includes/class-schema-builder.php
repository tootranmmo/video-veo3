<?php
/**
 * Schema Markup Builder
 */
class Audit_SEO_Schema_Builder {

    /**
     * Available schema types
     */
    private static $schema_types = array(
        'Article' => 'Article',
        'BlogPosting' => 'Blog Posting',
        'NewsArticle' => 'News Article',
        'Product' => 'Product',
        'Review' => 'Review',
        'Recipe' => 'Recipe',
        'Event' => 'Event',
        'Organization' => 'Organization',
        'Person' => 'Person',
        'LocalBusiness' => 'Local Business',
        'FAQPage' => 'FAQ Page',
        'HowTo' => 'How To',
        'VideoObject' => 'Video',
        'Course' => 'Course'
    );

    /**
     * Get schema types
     */
    public static function get_schema_types() {
        return self::$schema_types;
    }

    /**
     * Generate Article schema
     */
    public static function generate_article_schema($post_id, $custom_data = array()) {
        $post = get_post($post_id);
        if (!$post) {
            return null;
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => get_the_title($post_id),
            'description' => get_the_excerpt($post_id),
            'author' => array(
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', $post->post_author),
                'url' => get_author_posts_url($post->post_author)
            ),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => get_site_icon_url()
                )
            ),
            'datePublished' => get_the_date('c', $post_id),
            'dateModified' => get_the_modified_date('c', $post_id),
            'mainEntityOfPage' => array(
                '@type' => 'WebPage',
                '@id' => get_permalink($post_id)
            )
        );

        // Add featured image if exists
        if (has_post_thumbnail($post_id)) {
            $image = wp_get_attachment_image_src(get_post_thumbnail_id($post_id), 'full');
            $schema['image'] = array(
                '@type' => 'ImageObject',
                'url' => $image[0],
                'width' => $image[1],
                'height' => $image[2]
            );
        }

        // Merge with custom data
        if (!empty($custom_data)) {
            $schema = array_merge($schema, $custom_data);
        }

        return $schema;
    }

    /**
     * Generate Product schema
     */
    public static function generate_product_schema($product_data) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product_data['name'],
            'description' => $product_data['description'],
            'image' => $product_data['image'],
            'brand' => array(
                '@type' => 'Brand',
                'name' => isset($product_data['brand']) ? $product_data['brand'] : get_bloginfo('name')
            ),
            'offers' => array(
                '@type' => 'Offer',
                'price' => $product_data['price'],
                'priceCurrency' => isset($product_data['currency']) ? $product_data['currency'] : 'USD',
                'availability' => isset($product_data['availability']) ? $product_data['availability'] : 'https://schema.org/InStock',
                'url' => $product_data['url']
            )
        );

        // Add rating if available
        if (isset($product_data['rating'])) {
            $schema['aggregateRating'] = array(
                '@type' => 'AggregateRating',
                'ratingValue' => $product_data['rating'],
                'reviewCount' => isset($product_data['review_count']) ? $product_data['review_count'] : 1
            );
        }

        return $schema;
    }

    /**
     * Generate Recipe schema
     */
    public static function generate_recipe_schema($recipe_data) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Recipe',
            'name' => $recipe_data['name'],
            'description' => $recipe_data['description'],
            'author' => array(
                '@type' => 'Person',
                'name' => $recipe_data['author']
            ),
            'datePublished' => $recipe_data['date_published'],
            'image' => $recipe_data['image'],
            'recipeYield' => $recipe_data['yield'],
            'prepTime' => isset($recipe_data['prep_time']) ? $recipe_data['prep_time'] : '',
            'cookTime' => isset($recipe_data['cook_time']) ? $recipe_data['cook_time'] : '',
            'totalTime' => isset($recipe_data['total_time']) ? $recipe_data['total_time'] : '',
            'recipeIngredient' => $recipe_data['ingredients'],
            'recipeInstructions' => $recipe_data['instructions']
        );

        // Add nutrition if available
        if (isset($recipe_data['nutrition'])) {
            $schema['nutrition'] = array(
                '@type' => 'NutritionInformation',
                'calories' => $recipe_data['nutrition']['calories']
            );
        }

        return $schema;
    }

    /**
     * Generate FAQ schema
     */
    public static function generate_faq_schema($faqs) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array()
        );

        foreach ($faqs as $faq) {
            $schema['mainEntity'][] = array(
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => array(
                    '@type' => 'Answer',
                    'text' => $faq['answer']
                )
            );
        }

        return $schema;
    }

    /**
     * Generate Local Business schema
     */
    public static function generate_local_business_schema($business_data) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $business_data['name'],
            'description' => $business_data['description'],
            'image' => $business_data['image'],
            'address' => array(
                '@type' => 'PostalAddress',
                'streetAddress' => $business_data['street'],
                'addressLocality' => $business_data['city'],
                'addressRegion' => $business_data['state'],
                'postalCode' => $business_data['zip'],
                'addressCountry' => $business_data['country']
            ),
            'telephone' => $business_data['phone'],
            'url' => $business_data['url']
        );

        // Add opening hours if available
        if (isset($business_data['opening_hours'])) {
            $schema['openingHoursSpecification'] = array();
            foreach ($business_data['opening_hours'] as $hours) {
                $schema['openingHoursSpecification'][] = array(
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => $hours['day'],
                    'opens' => $hours['opens'],
                    'closes' => $hours['closes']
                );
            }
        }

        // Add geo coordinates if available
        if (isset($business_data['latitude']) && isset($business_data['longitude'])) {
            $schema['geo'] = array(
                '@type' => 'GeoCoordinates',
                'latitude' => $business_data['latitude'],
                'longitude' => $business_data['longitude']
            );
        }

        return $schema;
    }

    /**
     * Generate HowTo schema
     */
    public static function generate_howto_schema($howto_data) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'HowTo',
            'name' => $howto_data['name'],
            'description' => $howto_data['description'],
            'image' => $howto_data['image'],
            'totalTime' => isset($howto_data['total_time']) ? $howto_data['total_time'] : '',
            'step' => array()
        );

        foreach ($howto_data['steps'] as $index => $step) {
            $schema['step'][] = array(
                '@type' => 'HowToStep',
                'position' => $index + 1,
                'name' => $step['name'],
                'text' => $step['text'],
                'image' => isset($step['image']) ? $step['image'] : ''
            );
        }

        return $schema;
    }

    /**
     * Save schema for post
     */
    public static function save_schema($post_id, $schema_type, $schema_data) {
        $schema = null;

        switch ($schema_type) {
            case 'Article':
            case 'BlogPosting':
            case 'NewsArticle':
                $schema = self::generate_article_schema($post_id, $schema_data);
                break;
            case 'Product':
                $schema = self::generate_product_schema($schema_data);
                break;
            case 'Recipe':
                $schema = self::generate_recipe_schema($schema_data);
                break;
            case 'FAQPage':
                $schema = self::generate_faq_schema($schema_data);
                break;
            case 'LocalBusiness':
                $schema = self::generate_local_business_schema($schema_data);
                break;
            case 'HowTo':
                $schema = self::generate_howto_schema($schema_data);
                break;
        }

        if ($schema) {
            update_post_meta($post_id, '_audit_seo_schema', $schema);
            update_post_meta($post_id, '_audit_seo_schema_type', $schema_type);
            return true;
        }

        return false;
    }

    /**
     * Get schema for post
     */
    public static function get_schema($post_id) {
        return get_post_meta($post_id, '_audit_seo_schema', true);
    }

    /**
     * Output schema in head
     */
    public static function output_schema($post_id) {
        $schema = self::get_schema($post_id);

        if ($schema && is_array($schema)) {
            echo '<script type="application/ld+json">';
            echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            echo '</script>' . "\n";
        }
    }

    /**
     * Validate schema
     */
    public static function validate_schema($schema) {
        $errors = array();

        if (!isset($schema['@context'])) {
            $errors[] = 'Missing @context';
        }

        if (!isset($schema['@type'])) {
            $errors[] = 'Missing @type';
        }

        // Type-specific validation
        if (isset($schema['@type'])) {
            switch ($schema['@type']) {
                case 'Article':
                case 'BlogPosting':
                    if (!isset($schema['headline'])) {
                        $errors[] = 'Missing headline for Article';
                    }
                    if (!isset($schema['author'])) {
                        $errors[] = 'Missing author for Article';
                    }
                    break;

                case 'Product':
                    if (!isset($schema['name'])) {
                        $errors[] = 'Missing name for Product';
                    }
                    if (!isset($schema['offers'])) {
                        $errors[] = 'Missing offers for Product';
                    }
                    break;
            }
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }

    /**
     * Auto-generate schema based on content
     */
    public static function auto_generate_schema($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }

        // Determine best schema type based on post type and content
        $post_type = get_post_type($post_id);

        if ($post_type === 'post') {
            return self::generate_article_schema($post_id);
        } elseif ($post_type === 'page') {
            // Check if it's an FAQ page
            if (stripos($post->post_content, '<h') !== false && stripos($post->post_content, '?') !== false) {
                // Might be FAQ, but need better detection
                return self::generate_article_schema($post_id);
            }
        }

        return self::generate_article_schema($post_id);
    }
}

// Hook to output schema in head
add_action('wp_head', function() {
    if (is_singular()) {
        $post_id = get_the_ID();
        Audit_SEO_Schema_Builder::output_schema($post_id);
    }
}, 1);
