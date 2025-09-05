<?php
namespace Path_Pilot;

// Path Pilot Admin functionality
if (!defined('ABSPATH')) exit;

class Path_Pilot_Admin {
    // Define constants
    const UPGRADE_URL = 'https://buy.stripe.com/4gM8wQ3L05gL3ms2Me9EI00'; // Centralized upgrade URL

    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_css']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_js']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_icon_font']);

        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_head', [$this, 'admin_menu_styles']);
        add_action('admin_post_path_pilot_save_settings', [$this, 'save_settings']);
        add_action('admin_init', [$this, 'handle_upgrade_redirect']);

        // Use Elementor's approach to render our header in the admin area
        add_action('current_screen', function() {
            if (!$this->is_path_pilot_screen()) {
                return;
            }

            add_action('in_admin_header', function() {
                $this->render_admin_header();
            });
        });

        // Add AJAX handler for dismissing setup notice
        add_action('wp_ajax_path_pilot_dismiss_setup_notice', [$this, 'ajax_dismiss_setup_notice']);
    }

    /**
     * Check if we're on a Path Pilot admin screen
     */
    protected function is_path_pilot_screen() {
        $screen = get_current_screen();
        return $screen && strpos($screen->id, 'path-pilot') !== false;
    }

    /**
     * Render the admin header for Path Pilot screens
     */
    public function render_admin_header() {
        ?>
        <div class="pp-admin-wrap">
            <?php $this->render_header(); ?>
        </div>
        <?php
    }

    /**
     * Register the admin menu.
     */
    public function admin_menu() {
        // Use a CSS class for the icon instead of SVG
        $menu_icon = 'dashicons-admin-site'; // Temporarily use dashicons until our font is ready

        // Add Path Pilot main menu
        add_menu_page(
            'Path Pilot',                            // Page title
            'Path Pilot',                            // Menu title
            'manage_options',                        // Capability
            'path-pilot',                            // Menu slug
            array($this, 'render_admin_page'),       // Callback
            $menu_icon,                              // Icon
            90                                       // Position
        );

        // Add submenus
        add_submenu_page(
            'path-pilot',                            // Parent slug
            'Path Pilot Home',                       // Page title
            'Home',                                  // Menu title
            'manage_options',                        // Capability
            'path-pilot',                            // Menu slug (same as parent)
            array($this, 'render_admin_page')        // Callback
        );

        // Create the analytics page regardless of whether tables exist
        // We'll show appropriate messages inside the page
        add_submenu_page(
            'path-pilot',
            'Path Pilot Analytics',
            'Analytics',
            'manage_options',
            'path-pilot-analytics',
            array($this, 'render_analytics_page')
        );

            add_submenu_page(
                'path-pilot',                            // Parent slug
                'Path Pilot Settings',                   // Page title
                'Settings',                              // Menu title
                'manage_options',                        // Capability
                'path-pilot-settings',                   // Menu slug
                array($this, 'render_settings_page')     // Callback
            );

        // Add the upgrade link as a submenu item
        if (!Path_Pilot::is_pro()) {
            add_submenu_page(
                'path-pilot',
                'Upgrade to Pro',
                '<span class="path-pilot-upgrade-link">Upgrade</span>',
                'manage_options',
                'path-pilot-upgrade',
                array($this, 'render_upgrade_page')
            );

        }

        // Add CSS to use our custom font icon for the menu
        add_action('admin_head', function() {
            echo '<style>
                /* Replace the default dashicon with our custom icon */
                #adminmenu #toplevel_page_path-pilot .wp-menu-image:before {
                    font-family: "path-pilot-icons" !important;
                    content: "\e900"; /* The code for our icon */
                    font-size: 20px;
                }

                /* Style the upgrade link */
                .path-pilot-upgrade-link {
                    font-weight: 600;
                    color: #f9a825;
                }
            </style>';
        });
    }

    /**
     * Add custom styles for the admin menu upgrade link
     */
    public function admin_menu_styles() {
        ?>
        <style>
            /* Style the upgrade link in the menu to stand out */
            .path-pilot-upgrade-link {
                color: #ffffff !important;
                background-color: #e02e2e; /* Red accent color */
                padding: 3px 12px;
                border-radius: 3px;
                font-weight: 600;
                display: inline-block;
                text-align: center;
                transition: all 0.2s ease;
            }

            .path-pilot-upgrade-link:hover {
                background-color: #c12323; /* Darker red on hover */
            }

            /* Remove default WP admin submenu background hover effect */
            #adminmenu .wp-submenu a[href="<?php echo esc_attr(admin_url('admin.php?page=path-pilot-upgrade')); ?>"]:hover,
            #adminmenu .wp-submenu a[href="<?php echo esc_attr(admin_url('admin.php?page=path-pilot-upgrade')); ?>"]:focus {
                background-color: transparent;
            }

            /* For when the item is active/current */
            #adminmenu .wp-submenu a.current[href="<?php echo esc_attr(admin_url('admin.php?page=path-pilot-upgrade')); ?>"] {
                background-color: transparent;
            }
        </style>
        <?php
    }

    /**
     * Render the main admin page
     */
    public function render_admin_page() {
        // Include admin CSS
        wp_enqueue_style('path-pilot-admin-style');
        echo '<div class="path-pilot-frontend">';
        // Add wrapper div with proper CSS classes
        echo '<div class="pp-admin-wrap">';
        // Include the home page content
        include_once(plugin_dir_path(dirname(__DIR__)) . 'admin/common/home.php');
        echo '</div>'; // Close pp-admin-wrap
        echo '</div>'; // Close path-pilot-frontend
        // Include the footer
        include_once(plugin_dir_path(dirname(__DIR__)) . 'admin/common/footer.php');
    }

    public function render_home_page() {
        global $wpdb;
        $ready = get_option('path_pilot_ready', false);
        $stats = get_option('path_pilot_path_stats', []);
        $total_sessions = $wpdb->get_var("SELECT COUNT(DISTINCT session_id) FROM {$wpdb->prefix}path_pilot_events WHERE event_type = 'pageview'");
        $total_pages = count(get_pages(['post_status' => 'publish']));
        $page_views = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}path_pilot_events WHERE event_type = 'pageview'");
        $readiness_percentage = $this->calculate_readiness_percentage($ready, $stats, $total_sessions, $page_views);
        $temp_level = $this->get_temperature_level($readiness_percentage);
        $temp_data = $this->get_temperature_data($temp_level);

        // Fetch remote What's New
        $whats_new = $this->get_remote_whats_new();
        $news_items = [];
        if (is_array($whats_new)) {
            if (isset($whats_new['record']) && is_array($whats_new['record'])) {
                $news_items = $whats_new['record'];
            } elseif (isset($whats_new[0])) {
                $news_items = $whats_new;
            }
        }
        echo '<div class="path-pilot-frontend">';
        ?>
        <div class="pp-content">
            <?php do_action('path_pilot_show_pro_status_message'); ?>
            <div class="pp-home-flex pp-margin-bottom">
                <div class="pp-home-section pp-home-video">
                    <h3 class="pp-section-heading"><i class="icon-pilot-icon"></i> Quick Start Guide</h3>
                    <div class="pp-video-container">
                        <iframe src="https://www.youtube.com/embed/t3oF4jb_duo" title="Getting Started with Path Pilot" allowfullscreen></iframe>
                    </div>
                </div>
                <div class="pp-home-section pp-home-news">
                    <h3 class="pp-section-heading"><i class="emoji-hot icon-pilot-icon"></i>What's New</h3>
                    <ul class="pp-home-news-list">
                        <?php
                        if (count($news_items) > 0) {
                            foreach ($news_items as $item) {
                                $date = isset($item['date']) ? gmdate('F Y', strtotime($item['date'])) : '';
                                $title = isset($item['title']) ? $item['title'] : '';
                                echo '<li><strong>' . esc_html($date) . ':</strong> ' . esc_html($title) . '</li>';
                            }
                        } else {
                            // Fallback to hardcoded news
                        ?>
                        <li><strong>September 2025:</strong> Path Pilot 1.0 released!</li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
            <div class="pp-home-section pp-margin-bottom">
                <h3 class="pp-section-heading"><i class="emoji-warm icon-pilot-icon"></i> Recommendation Engine</h3>

                <div class="pp-home-protip"><i class="icon-pilot-icon"></i> <strong>Pro Tip:</strong> Path Pilot learns from real visitor behavior. The more traffic your site gets, the smarter the recommendations become!</div>

                <!-- Temperature readiness indicator -->
                <div class="pp-temp-indicator pp-temp-<?php echo esc_attr($temp_level); ?> pp-margin-bottom">
                    <div class="pp-temp-indicator-icon emoji-<?php echo esc_attr($temp_data['emoji']); ?> icon-pilot-icon"><?php echo esc_html($temp_data['emoji_fallback']); ?></div>
                    <div class="pp-temp-indicator-label"><?php echo esc_html($temp_data['label']); ?></div>
                    <div class="pp-temp-indicator-desc"><?php echo esc_html($temp_data['description']); ?></div>
                    <div class="pp-temp-indicator-progress">
                        <div class="pp-temp-indicator-bar" style="width: <?php echo intval($readiness_percentage); ?>%;"></div>
                    </div>
                </div>

                <!-- Stats Section -->
                <?php $this->render_home_stats(); ?>

            </div>
        </div>
        <?php
        echo '</div>'; // Close path-pilot-frontend
    }

    /**
     * Calculate the readiness percentage based on multiple factors
     *
     * @param bool $ready The binary ready flag from the option table
     * @param array $stats The path stats array
     * @param int $total_sessions Number of total visitor sessions
     * @param int $page_views Total page views tracked
     * @return int Percentage from 0-100
     */
    private function calculate_readiness_percentage($ready, $stats, $total_sessions, $page_views) {
        global $wpdb;
        // --- Site Activity Readiness (0-50%) ---
        $unique_paths = isset($stats['unique']) ? intval($stats['unique']) : 0;
        $path_factor = min(17.5, $unique_paths * 1.75); // 10 unique paths = 17.5%
        $session_factor = min(12.5, $total_sessions * 0.625); // 20 sessions = 12.5%
        $page_view_factor = min(15, $page_views * 0.15); // 100 page views = 15%
        $site_activity = $path_factor + $session_factor + $page_view_factor;
        $site_activity = min(50, $site_activity);

        // --- Conversion Path Readiness (0-50%) ---
        $activation_date = get_option('path_pilot_activation_date');
        if (!$activation_date) {
            $activation_date = time();
            update_option('path_pilot_activation_date', $activation_date);
        }
        $days_active = min(14, ceil((time() - $activation_date) / (60 * 60 * 24)));
        $min_conversions = 10;
        $max_conversions = 50;
        $target_conversions = max($min_conversions, $max_conversions - (($max_conversions - $min_conversions) * ($days_active / 14)));
        $conversion_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}path_pilot_visit_paths");
        $conversion_readiness = ($conversion_count >= $target_conversions)
            ? 50
            : max(0, ($conversion_count / $target_conversions) * 50);

        // --- Total Readiness ---
        $percentage = $site_activity + $conversion_readiness;
        $percentage = min(100, $percentage);
        return round($percentage);
    }

    /**
     * Get the temperature level based on the readiness percentage
     *
     * @param int $percentage Readiness percentage (0-100)
     * @return string Temperature level identifier
     */
    private function get_temperature_level($percentage) {
        if ($percentage <= 10) return 'freezing';
        if ($percentage <= 20) return 'coldest';
        if ($percentage <= 30) return 'colder';
        if ($percentage <= 40) return 'cold';
        if ($percentage <= 50) return 'cool';
        if ($percentage <= 60) return 'lukewarm';
        if ($percentage <= 70) return 'warm';
        if ($percentage <= 80) return 'warmer';
        if ($percentage <= 90) return 'hot';
        if ($percentage <= 99) return 'hottest';
        return 'onfire';
    }

    /**
     * Get temperature data including label, description, icon info
     *
     * @param string $temp_level Temperature level identifier
     * @return array Data about this temperature level
     */
    private function get_temperature_data($temp_level) {
        $data = [
            'freezing' => [
                'label' => 'Just Getting Started',
                'description' => 'Collecting initial visitor data. Basic recommendations available.',
                'emoji' => 'freezing',
                'emoji_fallback' => '❄️'
            ],
            'coldest' => [
                'label' => 'Early Learning',
                'description' => 'Building visitor behavior patterns from early site interactions.',
                'emoji' => 'cold',
                'emoji_fallback' => '🧊'
            ],
            'colder' => [
                'label' => 'Building Data',
                'description' => 'Gathering more visitor paths. Recommendation accuracy improving.',
                'emoji' => 'cold',
                'emoji_fallback' => '🧊'
            ],
            'cold' => [
                'label' => 'Warming Up',
                'description' => 'Learning visitor preferences from growing data collection.',
                'emoji' => 'cold',
                'emoji_fallback' => '🧊'
            ],
            'cool' => [
                'label' => 'Making Progress',
                'description' => 'Visitor behavior patterns becoming clearer with more data.',
                'emoji' => 'cool',
                'emoji_fallback' => '🥶'
            ],
            'lukewarm' => [
                'label' => 'Getting Smarter',
                'description' => 'Recommendation quality improving with richer visitor behavior data.',
                'emoji' => 'cool',
                'emoji_fallback' => '🥶'
            ],
            'warm' => [
                'label' => 'Running Well',
                'description' => 'Path-based intelligence developed. System learning effectively.',
                'emoji' => 'warm',
                'emoji_fallback' => '🌡️'
            ],
            'warmer' => [
                'label' => 'High Performance',
                'description' => 'Strong visitor behavior insights driving accurate recommendations.',
                'emoji' => 'warm',
                'emoji_fallback' => '🌡️'
            ],
            'hot' => [
                'label' => 'Highly Optimized',
                'description' => 'Rich visitor behavior data enabling advanced recommendation logic.',
                'emoji' => 'hot',
                'emoji_fallback' => '🔥'
            ],
            'hottest' => [
                'label' => 'Peak Performance',
                'description' => 'Deep visitor insights driving sophisticated conversion-optimized recommendations.',
                'emoji' => 'hot',
                'emoji_fallback' => '🔥'
            ],
            'onfire' => [
                'label' => 'Maximum Intelligence',
                'description' => 'Comprehensive visitor intelligence powering optimal recommendation engine.',
                'emoji' => 'fire',
                'emoji_fallback' => '🧯'
            ]
        ];

        return isset($data[$temp_level]) ? $data[$temp_level] : $data['freezing'];
    }

    /**
     * Render the appropriate header based on whether we're running the pro version or not
     */
    private function render_header() {
        include_once(plugin_dir_path(dirname(__DIR__)) . 'admin/free/header-free.php');
    }

    public function render_settings_page() {
        include_once(plugin_dir_path(dirname(__DIR__)) . 'admin/free/settings-free.php');
    }

    public function render_reports_page() {
        global $wpdb;
        $visit_paths_table = $wpdb->prefix . 'path_pilot_visit_paths';
        echo '<div class="path-pilot-frontend">';
        ?>
        <div class="pp-content">
            <!-- Top Paths Section -->
            <div class="pp-home-section pp-margin-bottom">
                <h3 class="pp-section-heading"><i class="emoji-hot icon-pilot-icon"></i> Top Conversion Paths</h3>

                <div class="pp-home-protip">
                    <i class="icon-pilot-icon"></i>
                    <strong>Pro Tip:</strong> These paths show the most common journeys visitors take before reaching your goal pages.
                </div>

                <div class="pp-table-responsive">
                    <?php
                    $top_rows = $wpdb->get_results("SELECT paths, MAX(updated_at) as last_updated, COUNT(*) as cnt FROM {$visit_paths_table} GROUP BY paths ORDER BY cnt DESC, last_updated DESC LIMIT 10");

                    if (empty($top_rows)) {
                        echo '<div class="pp-stat-waiting" style="text-align:center;padding:20px;">No conversion paths have been tracked yet. As visitors navigate your site, paths will appear here.</div>';
                    } else {
                        echo '<table class="table table-striped">';
                        echo '<thead><tr><th>Path</th><th>Count</th><th>Last Seen</th></tr></thead><tbody>';
                        foreach ($top_rows as $row) {
                            $paths = json_decode($row->paths, true);
                            $path_titles = [];
                            if (is_array($paths)) {
                                foreach ($paths as $pid) {
                                    $post = get_post($pid);
                                    if ($post) {
                                        $title = esc_html($post->post_title);
                                        $short = esc_html(mb_strimwidth($title, 0, 32, '...'));
                                        $url = get_permalink($post->ID);
                                        $path_titles[] = '<a href="' . esc_url($url) . '" target="_blank" title="' . esc_attr($title) . '">' . $short . '</a>';
                                    }
                                }
                            }
                            $path_str = implode(' <span style="color:#888">&rarr;</span> ', $path_titles);
                            $cnt = intval($row->cnt);
                            $last = human_time_diff(strtotime($row->last_updated), current_time('timestamp')) . ' ago';
                            printf('<tr><td>%s</td><td>%d</td><td>%s</td></tr>', $path_str, $cnt, esc_html($last));
                        }
                        echo '</tbody></table>';
                    }
                    ?>
                </div>
            </div>

            <!-- Recent Paths Section -->
            <div class="pp-home-section pp-margin-bottom">
                <h3 class="pp-section-heading"><i class="emoji-cool icon-pilot-icon"></i> Recent Visitor Paths</h3>

                <?php
                $recent_count = isset($_GET['recent_count']) ? intval($_GET['recent_count']) : 10;
                $recent_options = [10, 25, 50, 100];
                ?>

                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                    <div class="pp-stat-description" style="border:none;padding:0;margin:0;">
                        See individual visitor journeys through your site in real-time.
                    </div>

                    <form method="get" class="pp-filter-form" style="display:flex;align-items:center;gap:8px;">
                        <input type="hidden" name="page" value="path-pilot-reports" />
                        <label for="recent_count">Show</label>
                        <select name="recent_count" id="recent_count" class="form-select" style="width:auto;padding:4px 8px;" onchange="this.form.submit()">
                            <?php foreach ($recent_options as $opt): ?>
                                <option value="<?php echo esc_attr($opt); ?>" <?php selected($recent_count, $opt); ?>><?php echo esc_html($opt); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span>paths</span>
                    </form>
                </div>

                <div class="pp-table-responsive">
                    <?php
                    $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$visit_paths_table} ORDER BY updated_at DESC LIMIT %d", $recent_count));

                    if (empty($rows)) {
                        echo '<div class="pp-stat-waiting" style="text-align:center;padding:20px;">No paths have been tracked yet. Check back once visitors start using your site.</div>';
                    } else {
                        echo '<table class="table table-striped">';
                        echo '<thead><tr><th>Session</th><th>Path</th><th>Last Activity</th></tr></thead><tbody>';
                        foreach ($rows as $row) {
                            $session = esc_html(substr($row->session_id, 0, 8)) . '...';
                            $paths = json_decode($row->paths, true);
                            $path_titles = [];
                            if (is_array($paths)) {
                                foreach ($paths as $pid) {
                                    $post = get_post($pid);
                                    if ($post) {
                                        $title = esc_html($post->post_title);
                                        $short = esc_html(mb_strimwidth($title, 0, 32, '...'));
                                        $url = get_permalink($post->ID);
                                        $path_titles[] = '<a href="' . esc_url($url) . '" target="_blank" title="' . esc_attr($title) . '">' . $short . '</a>';
                                    }
                                }
                            }
                            $path_str = implode(' <span style="color:#888">&rarr;</span> ', $path_titles);
                            $time_diff = human_time_diff(strtotime($row->updated_at), current_time('timestamp')) . ' ago';
                            printf('<tr><td>%s</td><td>%s</td><td>%s</td></tr>', $session, $path_str, esc_html($time_diff));
                        }
                        echo '</tbody></table>';
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
        echo '</div>'; // Close path-pilot-frontend
    }


    /**
     * Enqueue the custom icon font
     */
    public function enqueue_icon_font($hook) {
        // Register the icon font
        wp_register_style(
            'path-pilot-icons',
            plugin_dir_url(__FILE__) . '../../assets/css/path-pilot-icons.css',
            [],
            PATH_PILOT_VERSION
        );

        // Load the icon font on all admin pages
        wp_enqueue_style('path-pilot-icons');
    }

    /**
     * Enqueue admin-specific CSS
     */
    public function enqueue_admin_css($hook) {
        // Register and enqueue the admin CSS for all admin pages
        wp_register_style(
            'path-pilot-admin-style',
            plugins_url('../admin/admin.css', dirname(__FILE__)),
            [],
            PATH_PILOT_VERSION
        );

        // Only load on Path Pilot admin pages
        if ($this->is_path_pilot_screen()) {
            wp_enqueue_style('path-pilot-admin-style');
        }

        // Register additional styles for specific screens if needed
    }

    /**
     * Enqueue admin-specific JS
     */
    public function enqueue_admin_js($hook) {
        // Only load on Path Pilot admin pages
        if ($this->is_path_pilot_screen()) {
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.7.0', true);
            wp_enqueue_script('path-pilot-chart-loader', plugins_url('../scripts/chart-loader.js', dirname(__FILE__)), ['chart-js'], PATH_PILOT_VERSION, true);
        }
    }

    /**
     * Handle saving settings from the settings form
     */
    public function save_settings() {
        // Check if we're processing our settings form submission
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'path_pilot_save_settings')) {
            wp_die('Invalid nonce verification');
            return;
        }

        // Make sure the user has permissions
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }

        // Process and save each setting
        update_option('path_pilot_goal_pages', isset($_POST['path_pilot_goal_pages']) ? array_map('intval', $_POST['path_pilot_goal_pages']) : []);
        update_option('path_pilot_conversion_pages', isset($_POST['path_pilot_conversion_pages']) ? array_map('intval', $_POST['path_pilot_conversion_pages']) : []);
        update_option('path_pilot_cta_text', sanitize_text_field($_POST['path_pilot_cta_text'] ?? 'Need a hand?'));
        update_option('path_pilot_recommend_label', sanitize_text_field($_POST['path_pilot_recommend_label'] ?? 'Recommended for you:'));

        // Handle API key separately: only update if a non-empty value is provided in the POST data
        $new_api_key = sanitize_text_field($_POST['path_pilot_api_key'] ?? '');
        if (!empty($new_api_key)) {
            update_option('path_pilot_api_key', $new_api_key);
        }
        // Save minimum hops
        $min_hops = isset($_POST['path_pilot_min_hops']) ? max(1, min(10, intval($_POST['path_pilot_min_hops']))) : 3;
        update_option('path_pilot_min_hops', $min_hops);

        // Save allowed content types with validation
        $submitted_content_types = isset($_POST['path_pilot_allowed_content_types']) && is_array($_POST['path_pilot_allowed_content_types'])
            ? array_map('sanitize_text_field', $_POST['path_pilot_allowed_content_types'])
            : [];

        // Use the helper function which includes validation and logging
                    $saved_content_types = Path_Pilot_Shared::set_allowed_content_types($submitted_content_types);

        // Log what was saved for debugging
        Log::info('Path Pilot Settings: Saved content types - submitted: [' . implode(', ', $submitted_content_types) . '], final: [' . implode(', ', $saved_content_types) . ']');

        // Always redirect after processing forms to avoid resubmission on refresh
        $redirect_url = add_query_arg([
            'page' => 'path-pilot-settings',
            'updated' => 'true',
        ], admin_url('admin.php'));

        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Handle redirection for upgrade link
     */
    public function handle_upgrade_redirect($value) {
        global $pagenow;
        $page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : false);
        // Preserve optional redirect behavior only when explicitly requested
        if ($pagenow == 'admin.php' && $page == 'path-pilot-upgrade' && isset($_GET['pp_redirect']) && $_GET['pp_redirect'] === '1') {
            wp_redirect(self::UPGRADE_URL);
            exit;
        }
    }

    /**
     * Render the free upgrade page explaining Pro benefits
     */
    public function render_upgrade_page() {
        // Include admin CSS
        wp_enqueue_style('path-pilot-admin-style');

        // Wrapper to match existing admin layout
        echo '<div class="pp-admin-wrap"><div class="pp-content">';
        do_action('path_pilot_show_pro_status_message');

        // Include the upgrade template
        include_once(plugin_dir_path(dirname(__DIR__)) . 'admin/free/upgrade-free.php');

        echo '</div></div>';

        // Footer
        include_once(plugin_dir_path(dirname(__DIR__)) . 'admin/common/footer.php');
    }

    /**
     * Renders the statistics section on the home page
     */
    public function render_home_stats() {
        global $wpdb;

        // Get allowed content types instead of all public post types for consistency
        $allowed_content_types = Path_Pilot_Shared::get_allowed_content_types();

        // Get all published posts of allowed types
        $total_posts = get_posts([
            'post_type'   => $allowed_content_types,
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields'      => 'ids',
        ]);
        $total_pages = count($total_posts);

        $page_views = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}path_pilot_events WHERE event_type = 'pageview'");
        $total_sessions = $wpdb->get_var("SELECT COUNT(DISTINCT session_id) FROM {$wpdb->prefix}path_pilot_events WHERE event_type = 'pageview'");

        // Calculate visitor retention if we have enough data
        $has_returning_visitors = false;
        $visitor_retention = 0;

        if ($page_views > 10) {
            $returning_count = $wpdb->get_var("SELECT COUNT(*) FROM (SELECT session_id FROM {$wpdb->prefix}path_pilot_events WHERE event_type = 'pageview' GROUP BY session_id HAVING COUNT(*) > 1) as returning");

            if ($total_sessions > 0) {
                $visitor_retention = round(($returning_count / $total_sessions) * 100);
                $has_returning_visitors = $returning_count > 0;
            }
        }

        // Calculate time since plugin activation for the learning period context
        $activation_date = get_option('path_pilot_activation_date');
        if (!$activation_date) {
            // If not set, set it now
            $activation_date = time();
            update_option('path_pilot_activation_date', $activation_date);
        }
        $days_active = ceil((time() - $activation_date) / (60 * 60 * 24));

        // Pages tracked - Count distinct pages that have been visited
        $pages_tracked = $wpdb->get_var("SELECT COUNT(DISTINCT page_id) FROM {$wpdb->prefix}path_pilot_events WHERE page_id > 0");
        if ($pages_tracked === null) $pages_tracked = 0;
        $pages_coverage = $total_pages > 0 ? round(($pages_tracked / $total_pages) * 100) : 0;

        Log::info('Path Pilot Debug: Total pages: ' . $total_pages);
        Log::info('Path Pilot Debug: Pages tracked: ' . $pages_tracked);
        Log::info('Path Pilot Debug: Pages coverage: ' . $pages_coverage);

        // Conversion paths tracked
        $conversion_paths_tracked = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}path_pilot_visit_paths");

        echo '<div class="path-pilot-frontend">';
        ?>
        <!-- Main Stats Grid -->
        <div class="pp-stats-grid">
            <!-- Site Traffic Stat -->
            <div class="pp-home-stat pp-stat-card">
                <div class="pp-home-stat-label">Page Views Tracked</div>
                <div class="pp-home-stat-value"><?php echo number_format($page_views); ?></div>
                <?php if ($page_views < 50): ?>
                    <div class="pp-stat-waiting">Collecting data...</div>
                    <div class="pp-progress-bar">
                        <div class="pp-progress-value" style="width: <?php echo esc_attr(min(100, ($page_views / 50) * 100)); ?>%"></div>
                    </div>
                <?php endif; ?>
                <div class="pp-stat-description">
                    <?php if ($page_views < 10): ?>
                        As visitors browse your site, Path Pilot builds a map of their journeys.
                    <?php elseif ($page_views < 50): ?>
                        Good start! More page views help identify common visitor paths.
                    <?php else: ?>
                        Your visitors have generated enough data for meaningful path analysis.
                    <?php endif; ?>
                </div>
            </div>

            <!-- Content Coverage -->
            <div class="pp-home-stat pp-stat-card">
                <div class="pp-home-stat-label">Content Coverage</div>
                <div class="pp-home-stat-value"><?php echo esc_html($pages_tracked); ?> / <?php echo esc_html($total_pages); ?></div>
                <?php if ($pages_coverage < 50): ?>
                    <div class="pp-stat-waiting"><?php echo esc_html($pages_coverage); ?>% of site explored</div>
                    <div class="pp-progress-bar">
                        <div class="pp-progress-value" style="width: <?php echo esc_attr($pages_coverage); ?>%"></div>
                    </div>
                <?php endif; ?>
                <div class="pp-stat-description">
                    <?php if ($pages_coverage < 30): ?>
                        More pages viewed by visitors = better recommendations across your site.
                    <?php elseif ($pages_coverage < 70): ?>
                        Growing coverage! Path Pilot is mapping connections between your content.
                    <?php else: ?>
                        Excellent! Most of your site content is included in the recommendation model.
                    <?php endif; ?>
                </div>
            </div>

            <!-- Learning Period -->
            <div class="pp-home-stat pp-stat-card">
                <div class="pp-home-stat-label">Learning Period</div>
                <div class="pp-home-stat-value"><?php echo esc_html($days_active); ?> <?php echo $days_active == 1 ? 'day' : 'days'; ?></div>
                <?php if ($days_active < 14): ?>
                    <div class="pp-stat-waiting">Initial learning phase</div>
                    <div class="pp-progress-bar">
                        <div class="pp-progress-value" style="width: <?php echo esc_attr(min(100, ($days_active / 14) * 100)); ?>%"></div>
                    </div>
                <?php endif; ?>
                <div class="pp-stat-description">
                    <?php if ($days_active < 7): ?>
                        Path Pilot is in its initial learning phase. Give it time to observe visitor behavior.
                    <?php elseif ($days_active < 14): ?>
                        Almost there! Path Pilot is refining its understanding of visitor preferences.
                    <?php else: ?>
                        Path Pilot has collected enough historical data for reliable recommendations.
                    <?php endif; ?>
                </div>
            </div>

            <!-- Conversion Paths Tracked -->
            <div class="pp-home-stat pp-stat-card">
                <div class="pp-home-stat-label">Conversion Paths Tracked</div>
                <div class="pp-home-stat-value"><?php echo number_format($conversion_paths_tracked); ?></div>
                <div class="pp-stat-description">
                    The number of unique conversion journeys tracked on your site so far.
                </div>
            </div>

            <?php
            do_action('path_pilot_render_embedding_coverage', $total_pages);
            ?>
        </div>

        <?php
        echo '</div>'; // Close path-pilot-frontend
    }

    /**
     * Render analytics dashboard
     */
    public function render_analytics_page() {
        global $wpdb;
        // Include admin CSS
        wp_enqueue_style('path-pilot-admin-style');

        // Wrapper with proper CSS classes to match layout
        echo '<div class="pp-admin-wrap"><div class="pp-content">';

        do_action('path_pilot_show_pro_status_message');

        // Calculate pages coverage
        $total_pages = Path_Pilot_Shared::get_total_pages();
        $pages_tracked = $wpdb->get_var("SELECT COUNT(DISTINCT page_id) FROM {$wpdb->prefix}path_pilot_events WHERE page_id > 0");
        if ($pages_tracked === null) $pages_tracked = 0;
        $pages_coverage = $total_pages > 0 ? round(($pages_tracked / $total_pages) * 100) : 0;

        Log::info('Path Pilot Debug: Total pages: ' . $total_pages);
        Log::info('Path Pilot Debug: Pages tracked: ' . $pages_tracked);
        Log::info('Path Pilot Debug: Pages coverage: ' . $pages_coverage);

        // Calculate days active for learning period
        $activation_date = get_option('path_pilot_activation_date');
        if (!$activation_date) {
            $activation_date = time();
            update_option('path_pilot_activation_date', $activation_date);
        }
        $days_active = ceil((time() - $activation_date) / (60 * 60 * 24));

        // --- Daily stats chart ---
        $stats = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}path_pilot_daily_stats ORDER BY date DESC LIMIT 30");
        $stats = array_reverse($stats); // Oldest first
        $dates = [];
        $page_views = [];
        $conversions = [];
        $unique_visitors = [];
        $conversion_rates = [];
        foreach ($stats as $row) {
            $dates[] = $row->date;
            $page_views[] = (int)$row->page_views;
            $conversions[] = (int)$row->conversions;
            $unique_visitors[] = (int)$row->unique_visitors;
            $conversion_rates[] = round($row->conversion_rate, 2); // Already a percentage
        }

        wp_localize_script('chart-js', 'PathPilotChartData', [
            'dates' => $dates,
            'page_views' => $page_views,
            'conversions' => $conversions,
            'unique_visitors' => $unique_visitors,
            'conversion_rates' => $conversion_rates,
        ]);
        ?>
        <div class="pp-home-section pp-margin-bottom">
            <h3 class="pp-section-heading"><i class="emoji-chart icon-pilot-icon"></i> Daily Performance (Last 30 Days)</h3>
            <canvas id="pp-daily-stats-chart" height="120"></canvas>
        </div>

        
        <?php

        // Check if the events table exists
        $events_table = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}path_pilot_events'");

        if ($events_table) {
            // Include the analytics dashboard
            include_once(plugin_dir_path(dirname(__DIR__)) . 'admin/common/admin.php');

            // Display the analytics
            path_pilot_display_analytics($pages_coverage, $days_active, $pages_tracked, $total_pages);
        } else {
            // Show a message that Path Pilot needs to collect data first
            ?>
            <div class="notice notice-info">
                <p><strong>Path Pilot is getting ready!</strong></p>
                <p>We're waiting for visitors to interact with your site so we can start collecting analytics data. Check back soon to see your site's statistics.</p>
            </div>

            <div class="pp-home-section">
                <h2>What to expect</h2>
                <p>Once data collection begins, you'll see detailed analytics about:</p>
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <li>Visitor counts and page views</li>
                    <li>Device breakdowns (desktop, mobile, tablet)</li>
                    <li>Visit durations</li>
                    <li>Path completion rates</li>
                </ul>
            </div>
            <?php
        }

        echo '</div></div>'; // Close pp-content and pp-admin-wrap

        // Include the footer
        include_once(plugin_dir_path(dirname(__DIR__)) . 'admin/common/footer.php');
    }

    /**
     * Fetch remote What's New JSON and cache with transient
     */
    private function get_remote_whats_new() {
        $remote_url = apply_filters('path_pilot_whats_new_url', 'https://api.jsonbin.io/v3/b/6806ec078561e97a5004af0a?' . time());
        $cache_key = 'path_pilot_whats_new_cache';
        $news = get_transient($cache_key);
        if ($news === false) {
            $response = wp_remote_get($remote_url, ['timeout' => 5]);
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = wp_remote_retrieve_body($response);
                $news = json_decode($body, true);
                if (is_array($news)) {
                    set_transient($cache_key, $news, 60 * 60); // Cache for 1 hour
                } else {
                    $news = false;
                }
            }
        }
        return $news;
    }

    /**
     * AJAX handler for dismissing the Pro setup notice
     */
    public function ajax_dismiss_setup_notice() {
        check_ajax_referer('path_pilot_dismiss_setup_notice', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'path-pilot'));
        }

        // Set user meta to remember dismissal
        update_user_meta(get_current_user_id(), 'path_pilot_setup_notice_dismissed', true);

        wp_send_json_success();
    }
}
