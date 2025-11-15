<?php
/**
 * Backlink Audit view
 */
if (!defined('ABSPATH')) exit;

$backlinks = Audit_SEO_Backlink_Audit::get_backlinks(array('limit' => 100));
$stats = Audit_SEO_Backlink_Audit::run_audit();
?>

<div class="wrap audit-seo-backlinks">
    <h1>Backlink Audit</h1>

    <div class="backlink-stats">
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $stats['total_backlinks']; ?></h3>
                <p>Total Backlinks</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['dofollow']; ?></h3>
                <p>DoFollow Links</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['nofollow']; ?></h3>
                <p>NoFollow Links</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['avg_domain_authority']; ?></h3>
                <p>Avg Domain Authority</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['healthy_links']; ?></h3>
                <p>Healthy Links</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['toxic_links']; ?></h3>
                <p>Toxic Links</p>
            </div>
        </div>
    </div>

    <div class="backlink-actions">
        <button class="button button-primary" id="add-backlink-btn">Add New Backlink</button>
    </div>

    <div id="backlink-form-modal" style="display: none;">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <h2 id="modal-title">Add Backlink</h2>
            <form id="backlink-form">
                <input type="hidden" id="backlink-id" name="id" value="">

                <table class="form-table">
                    <tr>
                        <th><label for="url">Target URL *</label></th>
                        <td><input type="url" id="url" name="url" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="source_url">Source URL *</label></th>
                        <td><input type="url" id="source_url" name="source_url" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="anchor_text">Anchor Text</label></th>
                        <td><input type="text" id="anchor_text" name="anchor_text" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="link_type">Link Type</label></th>
                        <td>
                            <select id="link_type" name="link_type">
                                <option value="dofollow">DoFollow</option>
                                <option value="nofollow">NoFollow</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="domain_authority">Domain Authority</label></th>
                        <td><input type="number" id="domain_authority" name="domain_authority" min="0" max="100" value="0"></td>
                    </tr>
                    <tr>
                        <th><label for="page_authority">Page Authority</label></th>
                        <td><input type="number" id="page_authority" name="page_authority" min="0" max="100" value="0"></td>
                    </tr>
                    <tr>
                        <th><label for="spam_score">Spam Score</label></th>
                        <td><input type="number" id="spam_score" name="spam_score" min="0" max="100" value="0"></td>
                    </tr>
                    <tr>
                        <th><label for="status">Status</label></th>
                        <td>
                            <select id="status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="disavowed">Disavowed</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">Save Backlink</button>
                    <button type="button" class="button" id="cancel-backlink">Cancel</button>
                </p>
            </form>
        </div>
    </div>

    <div class="backlinks-table">
        <h2>All Backlinks</h2>
        <?php if ($backlinks): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Source URL</th>
                        <th>Target URL</th>
                        <th>Anchor Text</th>
                        <th>Type</th>
                        <th>DA</th>
                        <th>Spam Score</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backlinks as $backlink): ?>
                        <tr>
                            <td><a href="<?php echo esc_url($backlink->source_url); ?>" target="_blank"><?php echo esc_html(parse_url($backlink->source_url, PHP_URL_HOST)); ?></a></td>
                            <td><?php echo esc_html(parse_url($backlink->url, PHP_URL_PATH)); ?></td>
                            <td><?php echo esc_html($backlink->anchor_text); ?></td>
                            <td><?php echo esc_html($backlink->link_type); ?></td>
                            <td><?php echo $backlink->domain_authority; ?></td>
                            <td>
                                <span class="spam-score <?php echo $backlink->spam_score >= 50 ? 'high' : 'low'; ?>">
                                    <?php echo $backlink->spam_score; ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($backlink->status); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($backlink->created_at)); ?></td>
                            <td>
                                <button class="button button-small edit-backlink" data-id="<?php echo $backlink->id; ?>">Edit</button>
                                <button class="button button-small delete-backlink" data-id="<?php echo $backlink->id; ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No backlinks found. Add your first backlink!</p>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Add backlink
    $('#add-backlink-btn').on('click', function() {
        $('#modal-title').text('Add Backlink');
        $('#backlink-form')[0].reset();
        $('#backlink-id').val('');
        $('#backlink-form-modal').show();
    });

    // Cancel
    $('#cancel-backlink, .modal-overlay').on('click', function() {
        $('#backlink-form-modal').hide();
    });

    // Save backlink
    $('#backlink-form').on('submit', function(e) {
        e.preventDefault();

        var formData = {
            action: 'save_backlink',
            nonce: auditSeoAjax.nonce
        };

        $(this).serializeArray().forEach(function(field) {
            formData[field.name] = field.value;
        });

        $.ajax({
            url: auditSeoAjax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('Backlink saved successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            }
        });
    });

    // Delete backlink
    $('.delete-backlink').on('click', function() {
        if (!confirm('Are you sure you want to delete this backlink?')) {
            return;
        }

        var id = $(this).data('id');

        $.ajax({
            url: auditSeoAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'delete_backlink',
                nonce: auditSeoAjax.nonce,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    alert('Backlink deleted successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            }
        });
    });
});
</script>
