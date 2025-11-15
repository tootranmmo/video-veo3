<?php
/**
 * Dashboard view
 */
if (!defined('ABSPATH')) exit;

global $wpdb;

$recent_audits = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}audit_seo_history
     ORDER BY created_at DESC
     LIMIT 10"
);

$backlink_stats = Audit_SEO_Backlink_Audit::run_audit();
?>

<div class="wrap audit-seo-dashboard">
    <h1>Audit SEO Semantic Dashboard</h1>

    <div class="audit-seo-cards">
        <div class="audit-card">
            <div class="card-icon technical">
                <span class="dashicons dashicons-admin-tools"></span>
            </div>
            <div class="card-content">
                <h3>Technical Audit</h3>
                <p>Analyze technical SEO aspects of your pages</p>
                <a href="<?php echo admin_url('admin.php?page=audit-seo-technical'); ?>" class="button button-primary">
                    Run Technical Audit
                </a>
            </div>
        </div>

        <div class="audit-card">
            <div class="card-icon backlink">
                <span class="dashicons dashicons-admin-links"></span>
            </div>
            <div class="card-content">
                <h3>Backlink Audit</h3>
                <p>Manage and analyze your backlinks</p>
                <a href="<?php echo admin_url('admin.php?page=audit-seo-backlinks'); ?>" class="button button-primary">
                    Manage Backlinks
                </a>
            </div>
        </div>

        <div class="audit-card">
            <div class="card-icon content">
                <span class="dashicons dashicons-edit-page"></span>
            </div>
            <div class="card-content">
                <h3>Content Audit</h3>
                <p>Optimize your content for SEO</p>
                <a href="<?php echo admin_url('admin.php?page=audit-seo-content'); ?>" class="button button-primary">
                    Run Content Audit
                </a>
            </div>
        </div>
    </div>

    <div class="audit-seo-stats">
        <div class="stats-section">
            <h2>Backlink Statistics</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-value"><?php echo $backlink_stats['total_backlinks']; ?></span>
                    <span class="stat-label">Total Backlinks</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo $backlink_stats['dofollow']; ?></span>
                    <span class="stat-label">DoFollow Links</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo $backlink_stats['nofollow']; ?></span>
                    <span class="stat-label">NoFollow Links</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo $backlink_stats['avg_domain_authority']; ?></span>
                    <span class="stat-label">Avg Domain Authority</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo $backlink_stats['healthy_links']; ?></span>
                    <span class="stat-label">Healthy Links</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo $backlink_stats['toxic_links']; ?></span>
                    <span class="stat-label">Toxic Links</span>
                </div>
            </div>
        </div>

        <div class="stats-section">
            <h2>Recent Audits</h2>
            <?php if ($recent_audits): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Post ID</th>
                            <th>Audit Type</th>
                            <th>Score</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_audits as $audit): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo get_edit_post_link($audit->post_id); ?>">
                                        <?php echo get_the_title($audit->post_id); ?>
                                    </a>
                                </td>
                                <td><?php echo ucfirst($audit->audit_type); ?></td>
                                <td>
                                    <span class="score score-<?php echo $audit->score >= 80 ? 'good' : ($audit->score >= 60 ? 'medium' : 'bad'); ?>">
                                        <?php echo $audit->score; ?>%
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($audit->created_at)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No audits have been run yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
