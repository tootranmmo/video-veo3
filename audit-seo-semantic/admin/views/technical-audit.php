<?php
/**
 * Technical Audit view
 */
if (!defined('ABSPATH')) exit;

$posts = get_posts(array(
    'post_type' => array('post', 'page'),
    'posts_per_page' => -1,
    'post_status' => 'publish'
));
?>

<div class="wrap audit-seo-technical">
    <h1>Technical SEO Audit</h1>

    <div class="audit-form-section">
        <h2>Select Page/Post to Audit</h2>
        <form id="technical-audit-form">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="post_id">Select Post/Page:</label>
                    </th>
                    <td>
                        <select name="post_id" id="post_id" class="regular-text">
                            <option value="">-- Select --</option>
                            <?php foreach ($posts as $post): ?>
                                <option value="<?php echo $post->ID; ?>">
                                    <?php echo $post->post_title . ' (' . $post->post_type . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary">Run Technical Audit</button>
            </p>
        </form>
    </div>

    <div id="audit-results" style="display: none;">
        <h2>Audit Results</h2>

        <div class="audit-score-container">
            <div class="audit-score-circle">
                <span id="audit-score-value">0</span>
                <span class="score-label">Score</span>
            </div>
        </div>

        <div class="audit-results-tabs">
            <div class="tabs">
                <button class="tab-button active" data-tab="passed">
                    <span class="dashicons dashicons-yes"></span> Passed (<span id="passed-count">0</span>)
                </button>
                <button class="tab-button" data-tab="issues">
                    <span class="dashicons dashicons-no"></span> Issues (<span id="issues-count">0</span>)
                </button>
                <button class="tab-button" data-tab="warnings">
                    <span class="dashicons dashicons-warning"></span> Warnings (<span id="warnings-count">0</span>)
                </button>
            </div>

            <div id="passed-tab" class="tab-content active">
                <ul id="passed-list" class="audit-list passed-list"></ul>
            </div>

            <div id="issues-tab" class="tab-content">
                <ul id="issues-list" class="audit-list issues-list"></ul>
            </div>

            <div id="warnings-tab" class="tab-content">
                <ul id="warnings-list" class="audit-list warnings-list"></ul>
            </div>
        </div>
    </div>

    <div id="audit-loading" style="display: none;">
        <div class="spinner is-active"></div>
        <p>Running technical audit...</p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#technical-audit-form').on('submit', function(e) {
        e.preventDefault();

        var postId = $('#post_id').val();
        if (!postId) {
            alert('Please select a post/page');
            return;
        }

        $('#audit-loading').show();
        $('#audit-results').hide();

        $.ajax({
            url: auditSeoAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'run_technical_audit',
                nonce: auditSeoAjax.nonce,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    displayResults(response.data);
                }
            },
            error: function() {
                alert('An error occurred');
            },
            complete: function() {
                $('#audit-loading').hide();
            }
        });
    });

    function displayResults(data) {
        $('#audit-score-value').text(data.score);
        $('#passed-count').text(data.passed.length);
        $('#issues-count').text(data.issues.length);
        $('#warnings-count').text(data.warnings ? data.warnings.length : 0);

        // Update score color
        var scoreCircle = $('.audit-score-circle');
        scoreCircle.removeClass('score-good score-medium score-bad');
        if (data.score >= 80) {
            scoreCircle.addClass('score-good');
        } else if (data.score >= 60) {
            scoreCircle.addClass('score-medium');
        } else {
            scoreCircle.addClass('score-bad');
        }

        // Display passed items
        var passedList = $('#passed-list');
        passedList.empty();
        if (data.passed.length > 0) {
            data.passed.forEach(function(item) {
                passedList.append('<li><span class="dashicons dashicons-yes"></span> ' + item + '</li>');
            });
        } else {
            passedList.append('<li>No items passed</li>');
        }

        // Display issues
        var issuesList = $('#issues-list');
        issuesList.empty();
        if (data.issues.length > 0) {
            data.issues.forEach(function(item) {
                issuesList.append('<li><span class="dashicons dashicons-no"></span> ' + item + '</li>');
            });
        } else {
            issuesList.append('<li>No issues found</li>');
        }

        // Display warnings
        var warningsList = $('#warnings-list');
        warningsList.empty();
        if (data.warnings && data.warnings.length > 0) {
            data.warnings.forEach(function(item) {
                warningsList.append('<li><span class="dashicons dashicons-warning"></span> ' + item + '</li>');
            });
        } else {
            warningsList.append('<li>No warnings</li>');
        }

        $('#audit-results').show();
    }

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
