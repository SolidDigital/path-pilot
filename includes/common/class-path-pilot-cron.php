<?php
namespace Path_Pilot;

if (!defined('ABSPATH')) exit;

/**
 * Handles cron jobs for Path Pilot
 */
class Path_Pilot_Cron {

    /**
     * Path_Pilot_Cron constructor.
     */
    public function __construct() {
        add_action('path_pilot_daily_cleanup', [$this, 'cleanup_old_data']);

        if (!wp_next_scheduled('path_pilot_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'path_pilot_daily_cleanup');
        }
    }

    /**
     * Deletes data older than 30 days from the events and visit paths tables.
     */
    public function cleanup_old_data() {
        global $wpdb;

        $events_table = $wpdb->prefix . 'path_pilot_events';
        $paths_table = $wpdb->prefix . 'path_pilot_visit_paths';
        $days_to_keep = 30;
        $limit_date = gmdate('Y-m-d H:i:s', strtotime("-{$days_to_keep} days"));

        $wpdb->query($wpdb->prepare("DELETE FROM {$events_table} WHERE created_at < %s", $limit_date));
        $wpdb->query($wpdb->prepare("DELETE FROM {$paths_table} WHERE created_at < %s", $limit_date));
    }
}
