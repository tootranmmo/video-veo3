<?php
/**
 * Scheduled Audits Handler
 */
class Audit_SEO_Scheduler {

    /**
     * Initialize scheduler
     */
    public static function init() {
        add_action('audit_seo_scheduled_audit', array(__CLASS__, 'run_scheduled_audit'));
        add_action('audit_seo_send_report', array(__CLASS__, 'send_email_report'));
    }

    /**
     * Schedule audit
     */
    public static function schedule_audit($post_id, $frequency = 'weekly', $audit_types = array()) {
        $schedules = get_option('audit_seo_scheduled_audits', array());

        $schedule = array(
            'post_id' => $post_id,
            'frequency' => $frequency,
            'audit_types' => $audit_types,
            'next_run' => self::calculate_next_run($frequency),
            'last_run' => null,
            'enabled' => true
        );

        $schedules[$post_id] = $schedule;
        update_option('audit_seo_scheduled_audits', $schedules);

        // Schedule WP cron event
        if (!wp_next_scheduled('audit_seo_scheduled_audit', array($post_id))) {
            wp_schedule_event(time(), $frequency, 'audit_seo_scheduled_audit', array($post_id));
        }

        return true;
    }

    /**
     * Unschedule audit
     */
    public static function unschedule_audit($post_id) {
        $schedules = get_option('audit_seo_scheduled_audits', array());

        if (isset($schedules[$post_id])) {
            unset($schedules[$post_id]);
            update_option('audit_seo_scheduled_audits', $schedules);
        }

        $timestamp = wp_next_scheduled('audit_seo_scheduled_audit', array($post_id));
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'audit_seo_scheduled_audit', array($post_id));
        }

        return true;
    }

    /**
     * Run scheduled audit
     */
    public static function run_scheduled_audit($post_id) {
        $schedules = get_option('audit_seo_scheduled_audits', array());

        if (!isset($schedules[$post_id]) || !$schedules[$post_id]['enabled']) {
            return;
        }

        $schedule = $schedules[$post_id];
        $results = array();

        // Run requested audit types
        foreach ($schedule['audit_types'] as $type) {
            switch ($type) {
                case 'technical':
                    $results['technical'] = Audit_SEO_Technical_Audit::run_audit($post_id);
                    break;
                case 'content':
                    $results['content'] = Audit_SEO_Content_Audit::run_audit($post_id);
                    break;
                case 'broken_links':
                    $results['broken_links'] = Audit_SEO_Link_Checker::check_post($post_id);
                    break;
            }
        }

        // Update schedule
        $schedules[$post_id]['last_run'] = current_time('mysql');
        $schedules[$post_id]['next_run'] = self::calculate_next_run($schedule['frequency']);
        $schedules[$post_id]['last_results'] = $results;
        update_option('audit_seo_scheduled_audits', $schedules);

        // Send email report if configured
        $settings = get_option('audit_seo_semantic_settings', array());
        if (!empty($settings['send_email_reports'])) {
            self::send_email_report($post_id, $results);
        }

        return $results;
    }

    /**
     * Send email report
     */
    public static function send_email_report($post_id, $results = null) {
        $settings = get_option('audit_seo_semantic_settings', array());
        $email = !empty($settings['report_email']) ? $settings['report_email'] : get_option('admin_email');

        if (!$email) {
            return false;
        }

        $post = get_post($post_id);
        if (!$post) {
            return false;
        }

        $subject = sprintf('[%s] SEO Audit Report - %s', get_bloginfo('name'), $post->post_title);

        $message = self::generate_email_html($post, $results);

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );

        return wp_mail($email, $subject, $message, $headers);
    }

    /**
     * Generate email HTML
     */
    private static function generate_email_html($post, $results) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2271b1; color: white; padding: 20px; text-align: center; }
                .score { font-size: 48px; font-weight: bold; margin: 20px 0; }
                .score.good { color: #4caf50; }
                .score.medium { color: #ff9800; }
                .score.bad { color: #f44336; }
                .section { margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 5px; }
                .section h3 { margin-top: 0; }
                ul { list-style: none; padding: 0; }
                li { padding: 5px 0; }
                .passed { color: #4caf50; }
                .issue { color: #f44336; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>SEO Audit Report</h1>
                    <p><?php echo esc_html($post->post_title); ?></p>
                    <p><?php echo date('F j, Y g:i a'); ?></p>
                </div>

                <?php if ($results): ?>
                    <?php foreach ($results as $type => $data): ?>
                        <div class="section">
                            <h3><?php echo ucfirst($type); ?> Audit</h3>

                            <?php if (isset($data['score'])): ?>
                                <div class="score <?php echo $data['score'] >= 80 ? 'good' : ($data['score'] >= 60 ? 'medium' : 'bad'); ?>">
                                    <?php echo $data['score']; ?>%
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($data['passed'])): ?>
                                <h4 style="color: #4caf50;">✓ Passed Checks</h4>
                                <ul>
                                    <?php foreach ($data['passed'] as $item): ?>
                                        <li class="passed">✓ <?php echo esc_html($item); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <?php if (!empty($data['issues'])): ?>
                                <h4 style="color: #f44336;">✗ Issues Found</h4>
                                <ul>
                                    <?php foreach ($data['issues'] as $item): ?>
                                        <li class="issue">✗ <?php echo esc_html($item); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="section">
                    <h3>Next Steps</h3>
                    <p>Review the issues above and make necessary improvements to boost your SEO score.</p>
                    <p><a href="<?php echo admin_url('admin.php?page=audit-seo-semantic'); ?>" style="background: #2271b1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">View Full Report</a></p>
                </div>

                <div class="footer">
                    <p>This is an automated report from Audit SEO Semantic plugin.</p>
                    <p><?php echo get_bloginfo('name'); ?> - <?php echo home_url(); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Calculate next run time
     */
    private static function calculate_next_run($frequency) {
        $intervals = array(
            'hourly' => '+1 hour',
            'twicedaily' => '+12 hours',
            'daily' => '+1 day',
            'weekly' => '+1 week',
            'monthly' => '+1 month'
        );

        $interval = isset($intervals[$frequency]) ? $intervals[$frequency] : '+1 week';
        return date('Y-m-d H:i:s', strtotime($interval));
    }

    /**
     * Add custom cron schedules
     */
    public static function add_cron_schedules($schedules) {
        if (!isset($schedules['weekly'])) {
            $schedules['weekly'] = array(
                'interval' => 604800,
                'display' => __('Once Weekly')
            );
        }
        if (!isset($schedules['monthly'])) {
            $schedules['monthly'] = array(
                'interval' => 2635200,
                'display' => __('Once Monthly')
            );
        }
        return $schedules;
    }

    /**
     * Get all scheduled audits
     */
    public static function get_scheduled_audits() {
        return get_option('audit_seo_scheduled_audits', array());
    }

    /**
     * Get schedule for specific post
     */
    public static function get_schedule($post_id) {
        $schedules = self::get_scheduled_audits();
        return isset($schedules[$post_id]) ? $schedules[$post_id] : null;
    }
}

// Initialize scheduler
add_filter('cron_schedules', array('Audit_SEO_Scheduler', 'add_cron_schedules'));
Audit_SEO_Scheduler::init();
