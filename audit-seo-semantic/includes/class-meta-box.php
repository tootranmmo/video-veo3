<?php
/**
 * Meta Box Handler for SEO Fields
 */
class Audit_SEO_Meta_Box {

    /**
     * Initialize meta boxes
     */
    public static function init() {
        add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
        add_action('save_post', array(__CLASS__, 'save_meta_box'), 10, 2);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
    }

    /**
     * Add meta boxes
     */
    public static function add_meta_boxes() {
        $post_types = array('post', 'page');

        foreach ($post_types as $post_type) {
            add_meta_box(
                'audit-seo-meta-box',
                'SEO Settings',
                array(__CLASS__, 'render_meta_box'),
                $post_type,
                'normal',
                'high'
            );
        }
    }

    /**
     * Render meta box
     */
    public static function render_meta_box($post) {
        wp_nonce_field('audit_seo_meta_box', 'audit_seo_meta_box_nonce');

        // Get saved values
        $seo_title = get_post_meta($post->ID, '_audit_seo_title', true);
        $meta_description = get_post_meta($post->ID, '_audit_seo_meta_description', true);
        $focus_keyword = get_post_meta($post->ID, '_audit_seo_focus_keyword', true);
        $canonical_url = get_post_meta($post->ID, '_audit_seo_canonical_url', true);
        $robots_meta = get_post_meta($post->ID, '_audit_seo_robots_meta', true);

        // Default values
        if (empty($seo_title)) {
            $seo_title = get_the_title($post->ID);
        }
        if (empty($canonical_url)) {
            $canonical_url = get_permalink($post->ID);
        }
        ?>

        <div class="audit-seo-meta-box-wrapper">

            <!-- SEO Title -->
            <div class="audit-seo-field">
                <label for="audit_seo_title">
                    <strong>SEO Title</strong>
                    <span class="description">Recommended: 30-60 characters</span>
                </label>

                <!-- Live Title Notification -->
                <div id="audit-seo-title-notification" class="audit-seo-live-notification" style="display: none;"></div>

                <input
                    type="text"
                    id="audit_seo_title"
                    name="audit_seo_title"
                    value="<?php echo esc_attr($seo_title); ?>"
                    class="widefat audit-seo-title-input"
                    maxlength="70"
                    placeholder="Enter SEO title..."
                    data-post-id="<?php echo $post->ID; ?>"
                >
                <div class="character-count">
                    <span class="current-count"><?php echo strlen($seo_title); ?></span> / 60 characters
                </div>
            </div>

            <!-- Meta Description -->
            <div class="audit-seo-field">
                <label for="audit_seo_meta_description">
                    <strong>Meta Description</strong>
                    <span class="description">Recommended: 120-160 characters</span>
                </label>
                <textarea
                    id="audit_seo_meta_description"
                    name="audit_seo_meta_description"
                    rows="3"
                    class="widefat audit-seo-meta-desc-input"
                    maxlength="320"
                    placeholder="Enter meta description..."
                ><?php echo esc_textarea($meta_description); ?></textarea>
                <div class="character-count">
                    <span class="current-count"><?php echo strlen($meta_description); ?></span> / 160 characters
                </div>
            </div>

            <!-- Focus Keyword -->
            <div class="audit-seo-field">
                <label for="audit_seo_focus_keyword">
                    <strong>Focus Keyword</strong>
                    <span class="description">Main keyword you want to rank for</span>
                </label>

                <!-- Live Keyword Notification -->
                <div id="audit-seo-keyword-notification" class="audit-seo-live-notification" style="display: none;"></div>

                <input
                    type="text"
                    id="audit_seo_focus_keyword"
                    name="audit_seo_focus_keyword"
                    value="<?php echo esc_attr($focus_keyword); ?>"
                    class="widefat audit-seo-keyword-input"
                    placeholder="e.g., WordPress SEO"
                    data-post-id="<?php echo $post->ID; ?>"
                >
                <?php if (!empty($focus_keyword)): ?>
                    <button type="button" class="button audit-seo-analyze-btn" data-post-id="<?php echo $post->ID; ?>">
                        Analyze Content
                    </button>
                <?php endif; ?>
            </div>

            <?php
            // Check for warnings
            $duplicate_result = Audit_SEO_Duplicate_Checker::get_results($post->ID);
            $cannibalization_result = Audit_SEO_Keyword_Cannibalization::get_results($post->ID);
            $has_warnings = false;

            if (!empty($duplicate_result['has_duplicates']) || !empty($cannibalization_result['has_cannibalization'])) {
                $has_warnings = true;
            }
            ?>

            <?php if ($has_warnings): ?>
            <!-- SEO Warnings -->
            <div class="audit-seo-warnings">
                <h4>⚠️ SEO Warnings</h4>

                <?php if (!empty($duplicate_result['has_duplicates'])): ?>
                <div class="audit-warning duplicate-warning">
                    <div class="warning-header">
                        <span class="dashicons dashicons-warning"></span>
                        <strong>Duplicate Content Detected</strong>
                    </div>
                    <div class="warning-body">
                        <p>Found <?php echo $duplicate_result['count']; ?> post(s) with similar content (<?php echo $duplicate_result['highest_similarity']; ?>% similarity)</p>
                        <ul class="duplicate-list">
                            <?php foreach (array_slice($duplicate_result['duplicates'], 0, 3) as $dup): ?>
                                <li>
                                    <a href="<?php echo get_edit_post_link($dup['post_id']); ?>" target="_blank">
                                        <?php echo esc_html($dup['title']); ?>
                                    </a>
                                    - <?php echo $dup['similarity']; ?>% similar
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if ($duplicate_result['count'] > 3): ?>
                            <p><em>+ <?php echo ($duplicate_result['count'] - 3); ?> more...</em></p>
                        <?php endif; ?>
                        <p class="warning-tip">💡 Consider consolidating or significantly differentiating content.</p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($cannibalization_result['has_cannibalization'])): ?>
                <div class="audit-warning cannibalization-warning severity-<?php echo $cannibalization_result['severity']; ?>">
                    <div class="warning-header">
                        <span class="dashicons dashicons-warning"></span>
                        <strong>Keyword Cannibalization (<?php echo ucfirst($cannibalization_result['severity']); ?> Risk)</strong>
                    </div>
                    <div class="warning-body">
                        <p><?php echo $cannibalization_result['competing_posts_count']; ?> other post(s) targeting "<strong><?php echo esc_html($cannibalization_result['focus_keyword']); ?></strong>"</p>
                        <ul class="competing-posts-list">
                            <?php foreach (array_slice($cannibalization_result['competing_posts'], 0, 3) as $competitor): ?>
                                <li>
                                    <a href="<?php echo get_edit_post_link($competitor['post_id']); ?>" target="_blank">
                                        <?php echo esc_html($competitor['title']); ?>
                                    </a>
                                    <?php if ($competitor['match_type'] === 'similar'): ?>
                                        <span class="match-badge">Similar</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="warning-recommendations">
                            <strong>Recommendations:</strong>
                            <ul>
                                <?php foreach ($cannibalization_result['recommendations'] as $rec): ?>
                                    <li><?php echo esc_html($rec); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <button type="button" class="button button-secondary audit-seo-recheck-btn">
                    <span class="dashicons dashicons-update"></span> Re-check Now
                </button>
            </div>
            <?php endif; ?>

            <!-- On-Page SEO Checklist -->
            <?php
            $onpage_results = Audit_SEO_OnPage_Checker::get_results($post->ID);
            if (!empty($onpage_results) && !empty($onpage_results['checks'])):
            ?>
            <div class="audit-seo-checklist">
                <button type="button" class="audit-seo-toggle-checklist">
                    <span class="dashicons dashicons-yes"></span>
                    SEO Checklist
                    <span class="checklist-score <?php echo $onpage_results['score'] >= 80 ? 'good' : ($onpage_results['score'] >= 60 ? 'medium' : 'bad'); ?>">
                        <?php echo $onpage_results['score']; ?>%
                    </span>
                    <span class="checklist-stats">
                        (<?php echo $onpage_results['passed_checks']; ?>/<?php echo $onpage_results['total_checks']; ?> passed)
                    </span>
                </button>

                <div class="audit-seo-checklist-content" style="display: none;">
                    <?php
                    $category_labels = array(
                        'image_seo' => 'Image SEO',
                        'linking' => 'Internal & External Links',
                        'content_structure' => 'Content Structure',
                        'readability' => 'Readability',
                        'technical_seo' => 'Technical SEO',
                        'engagement' => 'Engagement Elements'
                    );

                    foreach ($onpage_results['checks'] as $category => $checks):
                        if (empty($checks)) continue;
                    ?>
                        <div class="checklist-category">
                            <h4><?php echo $category_labels[$category]; ?></h4>
                            <ul class="checklist-items">
                                <?php foreach ($checks as $check): ?>
                                    <li class="checklist-item status-<?php echo $check['status']; ?>">
                                        <span class="check-icon">
                                            <?php if ($check['status'] === 'passed'): ?>
                                                ✓
                                            <?php elseif ($check['status'] === 'error'): ?>
                                                ✗
                                            <?php elseif ($check['status'] === 'warning'): ?>
                                                ⚠
                                            <?php else: ?>
                                                ℹ
                                            <?php endif; ?>
                                        </span>
                                        <div class="check-content">
                                            <div class="check-message"><?php echo esc_html($check['message']); ?></div>
                                            <?php if (!empty($check['recommendation'])): ?>
                                                <div class="check-recommendation"><?php echo esc_html($check['recommendation']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>

                    <button type="button" class="button button-secondary audit-seo-refresh-checklist" data-post-id="<?php echo $post->ID; ?>">
                        <span class="dashicons dashicons-update"></span> Refresh Checklist
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- SERP Preview -->
            <div class="audit-seo-field">
                <label><strong>Google Preview</strong></label>
                <div class="audit-seo-serp-preview">
                    <div class="serp-url"><?php echo esc_url(get_permalink($post->ID)); ?></div>
                    <div class="serp-title"><?php echo esc_html($seo_title); ?></div>
                    <div class="serp-description"><?php echo esc_html($meta_description); ?></div>
                </div>
            </div>

            <!-- Advanced Settings (Collapsible) -->
            <div class="audit-seo-advanced">
                <button type="button" class="audit-seo-toggle-advanced">
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                    Advanced Settings
                </button>

                <div class="audit-seo-advanced-content" style="display: none;">
                    <!-- Canonical URL -->
                    <div class="audit-seo-field">
                        <label for="audit_seo_canonical_url">
                            <strong>Canonical URL</strong>
                            <span class="description">Leave empty to use default permalink</span>
                        </label>
                        <input
                            type="url"
                            id="audit_seo_canonical_url"
                            name="audit_seo_canonical_url"
                            value="<?php echo esc_url($canonical_url); ?>"
                            class="widefat"
                            placeholder="https://example.com/page"
                        >
                    </div>

                    <!-- Robots Meta -->
                    <div class="audit-seo-field">
                        <label>
                            <strong>Robots Meta</strong>
                            <span class="description">Control search engine indexing</span>
                        </label>
                        <div class="audit-seo-checkboxes">
                            <label>
                                <input
                                    type="checkbox"
                                    name="audit_seo_robots_noindex"
                                    value="1"
                                    <?php checked(strpos($robots_meta, 'noindex') !== false); ?>
                                >
                                No Index (hide from search engines)
                            </label>
                            <label>
                                <input
                                    type="checkbox"
                                    name="audit_seo_robots_nofollow"
                                    value="1"
                                    <?php checked(strpos($robots_meta, 'nofollow') !== false); ?>
                                >
                                No Follow (don't follow links)
                            </label>
                            <label>
                                <input
                                    type="checkbox"
                                    name="audit_seo_robots_noarchive"
                                    value="1"
                                    <?php checked(strpos($robots_meta, 'noarchive') !== false); ?>
                                >
                                No Archive (don't cache page)
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Analysis Results -->
            <div id="audit-seo-quick-results" style="display: none;">
                <h4>Quick SEO Analysis</h4>
                <div class="audit-seo-results-container"></div>
            </div>

        </div>

        <style>
        .audit-seo-meta-box-wrapper {
            padding: 10px 0;
        }
        .audit-seo-field {
            margin-bottom: 20px;
        }
        .audit-seo-field label {
            display: block;
            margin-bottom: 5px;
        }
        .audit-seo-field label strong {
            font-size: 13px;
        }
        .audit-seo-field .description {
            color: #666;
            font-size: 12px;
            font-weight: normal;
            margin-left: 10px;
        }
        .character-count {
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }
        .character-count .current-count {
            font-weight: bold;
            color: #2271b1;
        }
        .character-count.warning .current-count {
            color: #ff9800;
        }
        .character-count.error .current-count {
            color: #f44336;
        }

        /* SERP Preview */
        .audit-seo-serp-preview {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-top: 5px;
            font-family: Arial, sans-serif;
        }
        .serp-url {
            color: #006621;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .serp-title {
            color: #1a0dab;
            font-size: 20px;
            font-weight: normal;
            margin-bottom: 5px;
            cursor: pointer;
        }
        .serp-title:hover {
            text-decoration: underline;
        }
        .serp-description {
            color: #545454;
            font-size: 14px;
            line-height: 1.4;
        }

        /* Advanced Settings */
        .audit-seo-toggle-advanced {
            background: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 8px 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            width: 100%;
            text-align: left;
            margin-bottom: 10px;
        }
        .audit-seo-toggle-advanced:hover {
            background: #e5e5e5;
        }
        .audit-seo-toggle-advanced .dashicons {
            margin-right: 5px;
            transition: transform 0.3s;
        }
        .audit-seo-toggle-advanced.active .dashicons {
            transform: rotate(180deg);
        }
        .audit-seo-advanced-content {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            background: #fafafa;
        }
        .audit-seo-checkboxes label {
            display: block;
            margin-bottom: 8px;
        }
        .audit-seo-analyze-btn {
            margin-top: 8px;
        }

        /* Results */
        #audit-seo-quick-results {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .audit-seo-results-container {
            margin-top: 10px;
        }
        </style>
        <?php
    }

    /**
     * Save meta box data
     */
    public static function save_meta_box($post_id, $post) {
        // Verify nonce
        if (!isset($_POST['audit_seo_meta_box_nonce']) ||
            !wp_verify_nonce($_POST['audit_seo_meta_box_nonce'], 'audit_seo_meta_box')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save SEO Title
        if (isset($_POST['audit_seo_title'])) {
            update_post_meta($post_id, '_audit_seo_title', sanitize_text_field($_POST['audit_seo_title']));
        }

        // Save Meta Description
        if (isset($_POST['audit_seo_meta_description'])) {
            update_post_meta($post_id, '_audit_seo_meta_description', sanitize_textarea_field($_POST['audit_seo_meta_description']));
        }

        // Save Focus Keyword
        $focus_keyword = '';
        if (isset($_POST['audit_seo_focus_keyword'])) {
            $focus_keyword = sanitize_text_field($_POST['audit_seo_focus_keyword']);
            update_post_meta($post_id, '_audit_seo_focus_keyword', $focus_keyword);
        }

        // Save Canonical URL
        if (isset($_POST['audit_seo_canonical_url'])) {
            update_post_meta($post_id, '_audit_seo_canonical_url', esc_url_raw($_POST['audit_seo_canonical_url']));
        }

        // Save Robots Meta
        $robots_meta = array();
        if (isset($_POST['audit_seo_robots_noindex'])) {
            $robots_meta[] = 'noindex';
        }
        if (isset($_POST['audit_seo_robots_nofollow'])) {
            $robots_meta[] = 'nofollow';
        }
        if (isset($_POST['audit_seo_robots_noarchive'])) {
            $robots_meta[] = 'noarchive';
        }
        update_post_meta($post_id, '_audit_seo_robots_meta', implode(',', $robots_meta));

        // Run on-page SEO checks
        if ($post->post_status === 'publish' || $post->post_status === 'draft') {
            $title = isset($_POST['audit_seo_title']) ? sanitize_text_field($_POST['audit_seo_title']) : $post->post_title;
            $results = Audit_SEO_OnPage_Checker::run_all_checks($post_id, $post->post_content, $title, $focus_keyword);
            Audit_SEO_OnPage_Checker::save_results($post_id, $results);
        }
    }

    /**
     * Enqueue scripts
     */
    public static function enqueue_scripts($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }

        wp_enqueue_script(
            'audit-seo-meta-box',
            AUDIT_SEO_SEMANTIC_URL . 'assets/js/meta-box.js',
            array('jquery'),
            AUDIT_SEO_SEMANTIC_VERSION,
            true
        );

        wp_localize_script('audit-seo-meta-box', 'auditSeoMetaBox', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('audit_seo_nonce')
        ));
    }

    /**
     * Output meta tags in front-end
     */
    public static function output_meta_tags() {
        if (!is_singular()) {
            return;
        }

        $post_id = get_the_ID();

        // SEO Title
        $seo_title = get_post_meta($post_id, '_audit_seo_title', true);
        if (!empty($seo_title)) {
            echo '<title>' . esc_html($seo_title) . '</title>' . "\n";
        }

        // Meta Description
        $meta_description = get_post_meta($post_id, '_audit_seo_meta_description', true);
        if (!empty($meta_description)) {
            echo '<meta name="description" content="' . esc_attr($meta_description) . '">' . "\n";
        }

        // Canonical URL
        $canonical_url = get_post_meta($post_id, '_audit_seo_canonical_url', true);
        if (!empty($canonical_url)) {
            echo '<link rel="canonical" href="' . esc_url($canonical_url) . '">' . "\n";
        }

        // Robots Meta
        $robots_meta = get_post_meta($post_id, '_audit_seo_robots_meta', true);
        if (!empty($robots_meta)) {
            echo '<meta name="robots" content="' . esc_attr($robots_meta) . '">' . "\n";
        }

        // Open Graph
        if (!empty($seo_title)) {
            echo '<meta property="og:title" content="' . esc_attr($seo_title) . '">' . "\n";
        }
        if (!empty($meta_description)) {
            echo '<meta property="og:description" content="' . esc_attr($meta_description) . '">' . "\n";
        }

        // Twitter Card
        if (!empty($seo_title)) {
            echo '<meta name="twitter:title" content="' . esc_attr($seo_title) . '">' . "\n";
        }
        if (!empty($meta_description)) {
            echo '<meta name="twitter:description" content="' . esc_attr($meta_description) . '">' . "\n";
        }
    }
}

// Initialize
Audit_SEO_Meta_Box::init();

// Output meta tags in head
add_action('wp_head', array('Audit_SEO_Meta_Box', 'output_meta_tags'), 1);
