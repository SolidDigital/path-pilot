<?php
/**
 * Plugin Name: Path Pilot
 * Description: Modern WordPress plugin for smart recommendations and analytics.
 * Version: 1.0.0
 * Author: Solid Digital
 * Author URI: https://www.soliddigital.com
 * Text Domain: path-pilot
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */
namespace Path_Pilot;

if (!defined('ABSPATH')) exit;
define('PATH_PILOT_VERSION', '1.0.0');

// This is the FREE version. Pro features are only available in the Pro build.

define('E_INFO', E_NOTICE);
class Log {
    // E_ERROR - log only errors
    // E_ERROR | E_INFO - errors and info
    // E_INFO - info only
    private const LEVEL = E_ERROR;
    static public function error($message) {
        if (Log::LEVEL & E_ERROR) {
            error_log($message);
        }
    }
    static public function info($message) {
        if (Log::LEVEL & E_NOTICE) {
            error_log($message);
        }
    }
}

Log::info('Path Pilot: Plugin file loaded for URL: ' . (esc_url_raw($_SERVER['REQUEST_URI'] ?? 'unknown')));

// Core includes
require_once __DIR__ . '/includes/common/class-path-pilot-admin.php';
require_once __DIR__ . '/includes/common/class-path-pilot-recommender.php';
require_once __DIR__ . '/includes/common/class-path-pilot-shared.php';

// Register multisite-safe activation redirect
Path_Pilot_Shared::register_activation_hook(__FILE__);
add_action('admin_init', [__NAMESPACE__ . '\\Path_Pilot_Shared', 'maybe_redirect_to_settings']);
Log::info("--- Path_Pilot ---");
Log::info("---");
class Path_Pilot {
    // Use constants from shared class
    const SLUG = Path_Pilot_Shared::SLUG;
    const REST_NAMESPACE = Path_Pilot_Shared::REST_NAMESPACE;

    public static function is_pro() {
        // We cannot check for PATH_PILOT_PRO because the free plugin loads first
        return is_plugin_active('path-pilot-pro/path-pilot-pro.php');
    }

    public function __construct() {
        // Activation hook for DB setup
        register_activation_hook(__FILE__, [__NAMESPACE__ . '\\Path_Pilot_Shared', 'activate']);

        // Register REST endpoints (shared, non-AI endpoints)
        add_action('rest_api_init', function() {
            Path_Pilot_Shared::register_rest_endpoints($this);
        });

        // Enqueue front-end JS
        add_action('wp_enqueue_scripts', [__NAMESPACE__ . '\\Path_Pilot_Shared', 'enqueue_scripts']);

        // Scheduled path analysis
        add_action('path_pilot_analyze_paths', [__NAMESPACE__ . '\\Path_Pilot_Shared', 'analyze_paths']);
        if (!wp_next_scheduled('path_pilot_analyze_paths')) {
            wp_schedule_event(time(), 'hourly', 'path_pilot_analyze_paths');
        }

        new Path_Pilot_Admin();
    }

    // No need to implement REST handlers or other methods
    // as they're all in Path_Pilot_Shared
}

// Initialize plugin
new Path_Pilot();
