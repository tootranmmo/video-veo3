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
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        MetaBox.init();
    });

})(jQuery);
