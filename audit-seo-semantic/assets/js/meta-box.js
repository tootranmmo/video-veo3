/**
 * Meta Box JavaScript
 */
(function($) {
    'use strict';

    var MetaBox = {

        /**
         * Initialize
         */
        init: function() {
            this.characterCounter();
            this.serpPreview();
            this.advancedToggle();
            this.quickAnalysis();
            this.recheckIssues();
            this.checklistToggle();
            this.refreshChecklist();
            this.liveTitleCheck();
            this.liveKeywordCheck();
        },

        /**
         * Character counter
         */
        characterCounter: function() {
            // Title counter
            $('#audit_seo_title').on('input', function() {
                var length = $(this).val().length;
                var counter = $(this).closest('.audit-seo-field').find('.current-count');
                var wrapper = $(this).closest('.audit-seo-field').find('.character-count');

                counter.text(length);

                // Color coding
                wrapper.removeClass('warning error');
                if (length < 30 || length > 60) {
                    wrapper.addClass('warning');
                }
                if (length > 70) {
                    wrapper.addClass('error');
                }
            });

            // Meta description counter
            $('#audit_seo_meta_description').on('input', function() {
                var length = $(this).val().length;
                var counter = $(this).closest('.audit-seo-field').find('.current-count');
                var wrapper = $(this).closest('.audit-seo-field').find('.character-count');

                counter.text(length);

                // Color coding
                wrapper.removeClass('warning error');
                if (length < 120 || length > 160) {
                    wrapper.addClass('warning');
                }
                if (length > 320) {
                    wrapper.addClass('error');
                }
            });
        },

        /**
         * SERP Preview
         */
        serpPreview: function() {
            // Update preview on input
            $('#audit_seo_title').on('input', function() {
                var title = $(this).val() || $('#title').val() || 'Your Page Title';
                $('.serp-title').text(title);
            });

            $('#audit_seo_meta_description').on('input', function() {
                var description = $(this).val() || 'Your meta description will appear here...';
                $('.serp-description').text(description);
            });

            // Also update when WordPress title changes
            $('#title').on('input', function() {
                if (!$('#audit_seo_title').val()) {
                    $('.serp-title').text($(this).val() || 'Your Page Title');
                }
            });
        },

        /**
         * Advanced settings toggle
         */
        advancedToggle: function() {
            $('.audit-seo-toggle-advanced').on('click', function(e) {
                e.preventDefault();
                $(this).toggleClass('active');
                $('.audit-seo-advanced-content').slideToggle(300);
            });
        },

        /**
         * Quick analysis
         */
        quickAnalysis: function() {
            $('.audit-seo-analyze-btn').on('click', function(e) {
                e.preventDefault();

                var button = $(this);
                var postId = button.data('post-id');
                var focusKeyword = $('#audit_seo_focus_keyword').val();

                if (!focusKeyword) {
                    alert('Please enter a focus keyword first.');
                    return;
                }

                // Get content
                var content = '';
                if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                    // Gutenberg
                    content = wp.data.select('core/editor').getEditedPostContent();
                } else if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                    // Classic editor
                    content = tinymce.get('content').getContent();
                }

                var title = $('#audit_seo_title').val() || $('#title').val();

                button.prop('disabled', true).text('Analyzing...');

                $.ajax({
                    url: auditSeoMetaBox.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'audit_seo_quick_analyze',
                        nonce: auditSeoMetaBox.nonce,
                        title: title,
                        content: content,
                        focus_keyword: focusKeyword
                    },
                    success: function(response) {
                        if (response.success) {
                            MetaBox.displayResults(response.data);
                        } else {
                            alert('Analysis failed: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('An error occurred during analysis.');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Analyze Content');
                    }
                });
            });
        },

        /**
         * Display analysis results
         */
        displayResults: function(data) {
            var html = '<div class="audit-seo-score-badge score-' +
                (data.score >= 80 ? 'good' : (data.score >= 60 ? 'medium' : 'bad')) + '">';
            html += 'Score: ' + data.score + '%</div>';

            html += '<div class="audit-seo-checks">';

            // Passed checks
            if (data.checks) {
                $.each(data.checks, function(checkName, check) {
                    var icon = check.status === 'success' ? '✓' :
                               check.status === 'warning' ? '⚠' : '✗';
                    var className = 'check-' + check.status;

                    html += '<div class="audit-check-item ' + className + '">';
                    html += '<span class="check-icon">' + icon + '</span>';
                    html += '<span class="check-message">' + check.message + '</span>';
                    html += '</div>';
                });
            }

            html += '</div>';

            // Suggestions
            if (data.suggestions && data.suggestions.length > 0) {
                html += '<div class="audit-suggestions">';
                html += '<h5>Suggestions:</h5>';
                html += '<ul>';
                $.each(data.suggestions, function(i, suggestion) {
                    html += '<li class="priority-' + suggestion.priority + '">';
                    html += '<strong>' + suggestion.priority.toUpperCase() + ':</strong> ';
                    html += suggestion.message;
                    html += '</li>';
                });
                html += '</ul>';
                html += '</div>';
            }

            $('.audit-seo-results-container').html(html);
            $('#audit-seo-quick-results').slideDown();
        },

        /**
         * Re-check duplicate content and keyword cannibalization
         */
        recheckIssues: function() {
            $('.audit-seo-recheck-btn').on('click', function(e) {
                e.preventDefault();

                var button = $(this);
                var postId = $('#post_ID').val();

                if (!postId) {
                    alert('Please save the post first.');
                    return;
                }

                var originalText = button.html();
                button.prop('disabled', true).html('<span class="dashicons dashicons-update audit-seo-spin"></span> Re-checking...');

                $.ajax({
                    url: auditSeoMetaBox.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'audit_seo_recheck_issues',
                        nonce: auditSeoMetaBox.nonce,
                        post_id: postId
                    },
                    success: function(response) {
                        if (response.success) {
                            // Reload the page to show updated warnings
                            location.reload();
                        } else {
                            alert('Re-check failed: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('An error occurred during re-check.');
                    },
                    complete: function() {
                        button.prop('disabled', false).html(originalText);
                    }
                });
            });
        },

        /**
         * Toggle checklist display
         */
        checklistToggle: function() {
            $('.audit-seo-toggle-checklist').on('click', function(e) {
                e.preventDefault();
                $('.audit-seo-checklist-content').slideToggle(300);
            });
        },

        /**
         * Refresh checklist
         */
        refreshChecklist: function() {
            $('.audit-seo-refresh-checklist').on('click', function(e) {
                e.preventDefault();

                var button = $(this);
                var postId = button.data('post-id');

                if (!postId) {
                    alert('Please save the post first.');
                    return;
                }

                var originalText = button.html();
                button.prop('disabled', true).html('<span class="dashicons dashicons-update audit-seo-spin"></span> Refreshing...');

                $.ajax({
                    url: auditSeoMetaBox.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'audit_seo_refresh_checklist',
                        nonce: auditSeoMetaBox.nonce,
                        post_id: postId
                    },
                    success: function(response) {
                        if (response.success) {
                            // Reload the page to show updated checklist
                            location.reload();
                        } else {
                            alert('Refresh failed: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('An error occurred during refresh.');
                    },
                    complete: function() {
                        button.prop('disabled', false).html(originalText);
                    }
                });
            });
        },

        /**
         * Live title duplicate check with debounce
         */
        liveTitleCheck: function() {
            var typingTimer;
            var doneTypingInterval = 800; // 800ms after user stops typing

            $('#audit_seo_title').on('input', function() {
                clearTimeout(typingTimer);
                var titleInput = $(this);
                var title = titleInput.val();
                var postId = titleInput.data('post-id');
                var notification = $('#audit-seo-title-notification');

                if (title.length < 3) {
                    notification.hide();
                    return;
                }

                // Show checking indicator
                notification.html('<span class="checking">🔍 Checking for duplicates...</span>').show();

                typingTimer = setTimeout(function() {
                    $.ajax({
                        url: auditSeoMetaBox.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'audit_seo_live_check_title',
                            nonce: auditSeoMetaBox.nonce,
                            title: title,
                            post_id: postId
                        },
                        success: function(response) {
                            if (response.success) {
                                MetaBox.displayTitleNotification(response.data);
                            } else {
                                notification.hide();
                            }
                        },
                        error: function() {
                            notification.hide();
                        }
                    });
                }, doneTypingInterval);
            });
        },

        /**
         * Display title notification
         */
        displayTitleNotification: function(data) {
            var notification = $('#audit-seo-title-notification');

            if (data.has_duplicate) {
                var html = '<div class="live-alert error">';
                html += '<span class="dashicons dashicons-warning"></span>';
                html += '<strong>Duplicate Title!</strong> ';
                html += data.duplicates.length + ' post(s) already have this exact title: ';
                html += '<ul class="duplicate-posts-inline">';
                data.duplicates.forEach(function(dup) {
                    html += '<li><a href="' + dup.edit_url + '" target="_blank">' + dup.title + '</a></li>';
                });
                html += '</ul>';
                html += '</div>';
                notification.html(html).show();
            } else if (data.has_similar) {
                var html = '<div class="live-alert warning">';
                html += '<span class="dashicons dashicons-info"></span>';
                html += '<strong>Similar Title Found:</strong> ';
                html += data.duplicates.length + ' post(s) have similar titles: ';
                html += '<ul class="duplicate-posts-inline">';
                data.duplicates.forEach(function(dup) {
                    html += '<li><a href="' + dup.edit_url + '" target="_blank">' + dup.title + '</a></li>';
                });
                html += '</ul>';
                html += '</div>';
                notification.html(html).show();
            } else {
                notification.hide();
            }
        },

        /**
         * Live keyword cannibalization check with debounce
         */
        liveKeywordCheck: function() {
            var typingTimer;
            var doneTypingInterval = 800; // 800ms after user stops typing

            $('#audit_seo_focus_keyword').on('input', function() {
                clearTimeout(typingTimer);
                var keywordInput = $(this);
                var keyword = keywordInput.val();
                var postId = keywordInput.data('post-id');
                var notification = $('#audit-seo-keyword-notification');

                if (keyword.length < 2) {
                    notification.hide();
                    return;
                }

                // Show checking indicator
                notification.html('<span class="checking">🔍 Checking for keyword conflicts...</span>').show();

                typingTimer = setTimeout(function() {
                    $.ajax({
                        url: auditSeoMetaBox.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'audit_seo_live_check_keyword',
                            nonce: auditSeoMetaBox.nonce,
                            keyword: keyword,
                            post_id: postId
                        },
                        success: function(response) {
                            if (response.success) {
                                MetaBox.displayKeywordNotification(response.data);
                            } else {
                                notification.hide();
                            }
                        },
                        error: function() {
                            notification.hide();
                        }
                    });
                }, doneTypingInterval);
            });
        },

        /**
         * Display keyword notification
         */
        displayKeywordNotification: function(data) {
            var notification = $('#audit-seo-keyword-notification');

            if (data.has_cannibalization) {
                var severityClass = 'severity-' + data.severity;
                var severityLabel = data.severity.toUpperCase();

                var html = '<div class="live-alert cannibalization ' + severityClass + '">';
                html += '<span class="dashicons dashicons-warning"></span>';
                html += '<strong>Keyword Cannibalization (' + severityLabel + ' Risk)!</strong> ';
                html += data.count + ' post(s) already target this keyword: ';
                html += '<ul class="duplicate-posts-inline">';
                data.competing_posts.forEach(function(post) {
                    html += '<li><a href="' + post.edit_url + '" target="_blank">' + post.title + '</a></li>';
                });
                html += '</ul>';
                html += '<p class="recommendation">💡 Consider using a different keyword variation or consolidating content.</p>';
                html += '</div>';
                notification.html(html).show();
            } else {
                // Show success message briefly
                notification.html('<div class="live-alert success"><span class="dashicons dashicons-yes"></span> Keyword is unique! ✓</div>').show();
                setTimeout(function() {
                    notification.fadeOut();
                }, 3000);
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        MetaBox.init();
    });

})(jQuery);
