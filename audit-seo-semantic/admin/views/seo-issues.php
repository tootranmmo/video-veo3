<?php
/**
 * SEO Issues Report - Duplicate Content & Keyword Cannibalization
 */
if (!defined('ABSPATH')) exit;

// Get all cannibalization issues
$cannibalization_issues = Audit_SEO_Keyword_Cannibalization::get_all_issues();

// Get all keyword usage stats
$keyword_stats = Audit_SEO_Keyword_Cannibalization::get_keyword_stats();

// Get posts with duplicate content
global $wpdb;
$duplicate_posts = $wpdb->get_results(
    "SELECT post_id, meta_value
     FROM {$wpdb->postmeta}
     WHERE meta_key = '_audit_seo_duplicate_check'"
);

$duplicates_list = array();
foreach ($duplicate_posts as $row) {
    $data = maybe_unserialize($row->meta_value);
    if (!empty($data['has_duplicates']) && $data['has_duplicates']) {
        $duplicates_list[] = array(
            'post_id' => $row->post_id,
            'title' => get_the_title($row->post_id),
            'url' => get_permalink($row->post_id),
            'edit_url' => get_edit_post_link($row->post_id),
            'count' => $data['count'],
            'highest_similarity' => $data['highest_similarity'],
            'checked_at' => $data['checked_at']
        );
    }
}

// Sort duplicates by similarity (highest first)
usort($duplicates_list, function($a, $b) {
    return $b['highest_similarity'] - $a['highest_similarity'];
});
?>

<div class="wrap audit-seo-issues-page">
    <h1>SEO Issues Report</h1>
    <p>Overview of duplicate content and keyword cannibalization issues across your site.</p>

    <!-- Statistics Summary -->
    <div class="audit-seo-stats">
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo count($duplicates_list); ?></h3>
                <p>Posts with Duplicate Content</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count($cannibalization_issues); ?></h3>
                <p>Keyword Cannibalization Issues</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count($keyword_stats); ?></h3>
                <p>Keywords Used Multiple Times</p>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="audit-results-tabs">
        <div class="tabs">
            <button class="tab-button active" data-tab="cannibalization">
                Keyword Cannibalization
            </button>
            <button class="tab-button" data-tab="duplicates">
                Duplicate Content
            </button>
            <button class="tab-button" data-tab="keywords">
                Keyword Usage
            </button>
        </div>

        <!-- Keyword Cannibalization Tab -->
        <div class="tab-content active" id="cannibalization-tab">
            <div class="audit-form-section">
                <h2>Keyword Cannibalization Issues</h2>
                <p>Multiple pages targeting the same keyword can confuse search engines and split your ranking power.</p>

                <?php if (!empty($cannibalization_issues)): ?>
                    <?php foreach ($cannibalization_issues as $issue): ?>
                        <div class="cannibalization-issue-card severity-<?php echo $issue['severity']; ?>">
                            <div class="issue-header">
                                <h3>
                                    <span class="dashicons dashicons-warning"></span>
                                    Keyword: "<?php echo esc_html($issue['keyword']); ?>"
                                </h3>
                                <span class="severity-badge severity-<?php echo $issue['severity']; ?>">
                                    <?php echo ucfirst($issue['severity']); ?> Risk
                                </span>
                            </div>
                            <div class="issue-body">
                                <p><strong><?php echo $issue['total_posts']; ?> posts</strong> are competing for this keyword:</p>
                                <ul class="competing-posts-list">
                                    <?php foreach ($issue['affected_posts'] as $post): ?>
                                        <li>
                                            <a href="<?php echo esc_url($post['url']); ?>" target="_blank">
                                                <?php echo esc_html($post['title']); ?>
                                            </a>
                                            <a href="<?php echo get_edit_post_link($post['post_id']); ?>" class="edit-link">
                                                (Edit)
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <div class="issue-actions">
                                    <strong>Recommendations:</strong>
                                    <ul>
                                        <li>Consolidate similar content into one comprehensive page</li>
                                        <li>Differentiate each page with unique long-tail keywords</li>
                                        <li>Use 301 redirects from weaker pages to the strongest one</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="audit-notice success">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <p>No keyword cannibalization issues detected! Each keyword is targeted by only one page.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Duplicate Content Tab -->
        <div class="tab-content" id="duplicates-tab">
            <div class="audit-form-section">
                <h2>Duplicate Content Issues</h2>
                <p>Pages with similar content (70%+ similarity) can hurt your SEO rankings.</p>

                <?php if (!empty($duplicates_list)): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Post Title</th>
                                <th>Duplicate Posts</th>
                                <th>Highest Similarity</th>
                                <th>Last Checked</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($duplicates_list as $dup): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo esc_url($dup['url']); ?>" target="_blank">
                                            <?php echo esc_html($dup['title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo $dup['count']; ?> posts</td>
                                    <td>
                                        <span class="similarity-badge <?php echo $dup['highest_similarity'] >= 90 ? 'high' : 'medium'; ?>">
                                            <?php echo $dup['highest_similarity']; ?>%
                                        </span>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($dup['checked_at'])); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url($dup['edit_url']); ?>" class="button button-small">
                                            Edit Post
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="audit-notice success">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <p>No duplicate content detected! All your content is unique.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Keyword Usage Tab -->
        <div class="tab-content" id="keywords-tab">
            <div class="audit-form-section">
                <h2>Keyword Usage Statistics</h2>
                <p>Keywords that are being used by multiple posts (potential cannibalization).</p>

                <?php if (!empty($keyword_stats)): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Keyword</th>
                                <th>Used by Posts</th>
                                <th>Risk Level</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($keyword_stats as $stat): ?>
                                <?php
                                $risk_level = 'low';
                                if ($stat->usage_count > 3) {
                                    $risk_level = 'high';
                                } elseif ($stat->usage_count > 1) {
                                    $risk_level = 'medium';
                                }
                                ?>
                                <tr>
                                    <td><strong><?php echo esc_html($stat->keyword); ?></strong></td>
                                    <td><?php echo $stat->usage_count; ?> posts</td>
                                    <td>
                                        <span class="spam-score <?php echo $risk_level; ?>">
                                            <?php echo ucfirst($risk_level); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="audit-notice success">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <p>All keywords are unique! No keywords are being reused across multiple posts.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.audit-seo-issues-page .stats-grid {
    margin: 20px 0 30px;
}

.cannibalization-issue-card {
    background: #fff;
    border: 1px solid #ddd;
    border-left: 4px solid;
    border-radius: 4px;
    margin-bottom: 20px;
    overflow: hidden;
}

.cannibalization-issue-card.severity-low {
    border-left-color: #ff9800;
}

.cannibalization-issue-card.severity-medium {
    border-left-color: #ff5722;
}

.cannibalization-issue-card.severity-high {
    border-left-color: #d63638;
}

.cannibalization-issue-card .issue-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: #f9f9f9;
    border-bottom: 1px solid #ddd;
}

.cannibalization-issue-card .issue-header h3 {
    margin: 0;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.severity-badge {
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.severity-badge.severity-low {
    background: #fff3e0;
    color: #e65100;
}

.severity-badge.severity-medium {
    background: #fbe9e7;
    color: #d84315;
}

.severity-badge.severity-high {
    background: #ffebee;
    color: #b71c1c;
}

.cannibalization-issue-card .issue-body {
    padding: 20px;
}

.issue-body .competing-posts-list {
    margin: 15px 0;
    padding-left: 20px;
}

.issue-body .competing-posts-list li {
    margin-bottom: 8px;
}

.edit-link {
    color: #666;
    font-size: 12px;
    text-decoration: none;
    margin-left: 5px;
}

.edit-link:hover {
    color: #2271b1;
}

.issue-actions {
    margin-top: 15px;
    padding: 15px;
    background: #f0f6fc;
    border-left: 3px solid #2196f3;
    border-radius: 4px;
}

.issue-actions ul {
    margin: 10px 0 0;
    padding-left: 20px;
}

.issue-actions li {
    margin-bottom: 6px;
}

.similarity-badge {
    padding: 4px 10px;
    border-radius: 4px;
    font-weight: bold;
}

.similarity-badge.high {
    background: #ffebee;
    color: #d63638;
}

.similarity-badge.medium {
    background: #fff3e0;
    color: #ff9800;
}

.audit-notice {
    padding: 20px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.audit-notice.success {
    background: #e8f5e9;
    color: #2e7d32;
    border-left: 4px solid #4caf50;
}

.audit-notice .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}

.audit-notice p {
    margin: 0;
    font-size: 14px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.tab-button').on('click', function() {
        var tab = $(this).data('tab');

        $('.tab-button').removeClass('active');
        $(this).addClass('active');

        $('.tab-content').removeClass('active');
        $('#' + tab + '-tab').addClass('active');
    });
});
</script>
