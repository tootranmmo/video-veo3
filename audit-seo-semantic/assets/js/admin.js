/**
 * Audit SEO Semantic Admin JavaScript
 */
(function($) {
    'use strict';

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        initAuditForms();
        initTabs();
        initBacklinkManagement();
    });

    /**
     * Initialize audit forms
     */
    function initAuditForms() {
        // Technical audit form
        $('#technical-audit-form').on('submit', function(e) {
            e.preventDefault();
            runTechnicalAudit();
        });

        // Content audit form
        $('#content-audit-form').on('submit', function(e) {
            e.preventDefault();
            runContentAudit();
        });
    }

    /**
     * Run technical audit
     */
    function runTechnicalAudit() {
        var postId = $('#post_id').val();

        if (!postId) {
            showNotice('Please select a post/page', 'error');
            return;
        }

        showLoading();
        hideResults();

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
                    displayAuditResults(response.data);
                } else {
                    showNotice('Audit failed: ' + response.data, 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotice('An error occurred: ' + error, 'error');
            },
            complete: function() {
                hideLoading();
            }
        });
    }

    /**
     * Run content audit
     */
    function runContentAudit() {
        var postId = $('#post_id').val();
        var focusKeyword = $('#focus_keyword').val();

        if (!postId) {
            showNotice('Please select a post/page', 'error');
            return;
        }

        showLoading();
        hideResults();

        $.ajax({
            url: auditSeoAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'run_content_audit',
                nonce: auditSeoAjax.nonce,
                post_id: postId,
                focus_keyword: focusKeyword
            },
            success: function(response) {
                if (response.success) {
                    displayContentAuditResults(response.data);
                } else {
                    showNotice('Audit failed: ' + response.data, 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotice('An error occurred: ' + error, 'error');
            },
            complete: function() {
                hideLoading();
            }
        });
    }

    /**
     * Display audit results
     */
    function displayAuditResults(data) {
        // Update score
        updateScore(data.score);

        // Update counts
        $('#passed-count').text(data.passed.length);
        $('#issues-count').text(data.issues.length);
        $('#warnings-count').text(data.warnings ? data.warnings.length : 0);

        // Display lists
        displayList('#passed-list', data.passed, 'yes', 'passed');
        displayList('#issues-list', data.issues, 'no', 'issues');

        if (data.warnings && data.warnings.length > 0) {
            displayList('#warnings-list', data.warnings, 'warning', 'warnings');
        }

        $('#audit-results').slideDown();
    }

    /**
     * Display content audit results
     */
    function displayContentAuditResults(data) {
        // Update score
        updateScore(data.score);

        // Update counts
        $('#passed-count').text(data.passed.length);
        $('#issues-count').text(data.issues.length);
        $('#warnings-count').text(data.warnings ? data.warnings.length : 0);

        // Update stats
        if (data.stats) {
            $('#stat-word-count').text(data.stats.word_count || 0);
            $('#stat-sentences').text(data.stats.sentence_count || 0);
            $('#stat-avg-sentence').text(data.stats.avg_sentence_length || 0);
            $('#stat-readability').text(data.stats.readability_score || 'N/A');
            $('#stat-internal-links').text(data.stats.internal_links || 0);
            $('#stat-external-links').text(data.stats.external_links || 0);

            if (data.stats.keyword_count !== undefined) {
                $('#keyword-stats').show();
                $('#stat-keyword-count').text(data.stats.keyword_count);
                $('#stat-keyword-density').text(data.stats.keyword_density);
            } else {
                $('#keyword-stats').hide();
            }
        }

        // Display lists
        displayList('#passed-list', data.passed, 'yes', 'passed');
        displayList('#issues-list', data.issues, 'no', 'issues');

        if (data.warnings && data.warnings.length > 0) {
            displayList('#warnings-list', data.warnings, 'warning', 'warnings');
        }

        $('#audit-results').slideDown();
    }

    /**
     * Update score display
     */
    function updateScore(score) {
        $('#audit-score-value').text(score);

        var scoreCircle = $('.audit-score-circle');
        scoreCircle.removeClass('score-good score-medium score-bad');

        if (score >= 80) {
            scoreCircle.addClass('score-good');
        } else if (score >= 60) {
            scoreCircle.addClass('score-medium');
        } else {
            scoreCircle.addClass('score-bad');
        }
    }

    /**
     * Display list items
     */
    function displayList(selector, items, icon, type) {
        var list = $(selector);
        list.empty();

        if (items && items.length > 0) {
            items.forEach(function(item) {
                var li = $('<li>')
                    .append($('<span>').addClass('dashicons dashicons-' + icon))
                    .append(document.createTextNode(' ' + item));
                list.append(li);
            });
        } else {
            list.append('<li>No ' + type + ' found</li>');
        }
    }

    /**
     * Initialize tabs
     */
    function initTabs() {
        $('.tab-button').on('click', function() {
            var tab = $(this).data('tab');

            // Update active states
            $('.tab-button').removeClass('active');
            $(this).addClass('active');

            $('.tab-content').removeClass('active');
            $('#' + tab + '-tab').addClass('active');
        });
    }

    /**
     * Initialize backlink management
     */
    function initBacklinkManagement() {
        // Add backlink button
        $('#add-backlink-btn').on('click', function() {
            openBacklinkModal();
        });

        // Cancel button
        $('#cancel-backlink, .modal-overlay').on('click', function() {
            closeBacklinkModal();
        });

        // Save backlink form
        $('#backlink-form').on('submit', function(e) {
            e.preventDefault();
            saveBacklink();
        });

        // Edit backlink
        $('.edit-backlink').on('click', function() {
            var id = $(this).data('id');
            editBacklink(id);
        });

        // Delete backlink
        $('.delete-backlink').on('click', function() {
            var id = $(this).data('id');
            deleteBacklink(id);
        });
    }

    /**
     * Open backlink modal
     */
    function openBacklinkModal(data) {
        if (data) {
            $('#modal-title').text('Edit Backlink');
            $('#backlink-id').val(data.id);
            $('#url').val(data.url);
            $('#source_url').val(data.source_url);
            $('#anchor_text').val(data.anchor_text);
            $('#link_type').val(data.link_type);
            $('#domain_authority').val(data.domain_authority);
            $('#page_authority').val(data.page_authority);
            $('#spam_score').val(data.spam_score);
            $('#status').val(data.status);
        } else {
            $('#modal-title').text('Add Backlink');
            $('#backlink-form')[0].reset();
            $('#backlink-id').val('');
        }

        $('#backlink-form-modal').fadeIn();
    }

    /**
     * Close backlink modal
     */
    function closeBacklinkModal() {
        $('#backlink-form-modal').fadeOut();
    }

    /**
     * Save backlink
     */
    function saveBacklink() {
        var formData = {
            action: 'save_backlink',
            nonce: auditSeoAjax.nonce
        };

        $('#backlink-form').serializeArray().forEach(function(field) {
            formData[field.name] = field.value;
        });

        $.ajax({
            url: auditSeoAjax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showNotice('Backlink saved successfully!', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showNotice('Error: ' + response.data, 'error');
                }
            },
            error: function() {
                showNotice('An error occurred while saving', 'error');
            }
        });
    }

    /**
     * Delete backlink
     */
    function deleteBacklink(id) {
        if (!confirm('Are you sure you want to delete this backlink?')) {
            return;
        }

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
                    showNotice('Backlink deleted successfully!', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showNotice('Error: ' + response.data, 'error');
                }
            },
            error: function() {
                showNotice('An error occurred while deleting', 'error');
            }
        });
    }

    /**
     * Show loading indicator
     */
    function showLoading() {
        $('#audit-loading').fadeIn();
    }

    /**
     * Hide loading indicator
     */
    function hideLoading() {
        $('#audit-loading').fadeOut();
    }

    /**
     * Show results
     */
    function showResults() {
        $('#audit-results').fadeIn();
    }

    /**
     * Hide results
     */
    function hideResults() {
        $('#audit-results').fadeOut();
    }

    /**
     * Show notice message
     */
    function showNotice(message, type) {
        var noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
        var notice = $('<div>')
            .addClass('notice ' + noticeClass + ' is-dismissible')
            .append($('<p>').text(message));

        $('.wrap h1').after(notice);

        setTimeout(function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

})(jQuery);
