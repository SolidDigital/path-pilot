<?php
namespace Path_Pilot;

// Shared code for both free and pro versions of Path Pilot
if (!defined('ABSPATH')) exit;


// Register the action to enqueue icon font on the frontend
add_action('wp_enqueue_scripts', [__NAMESPACE__ . '\\Path_Pilot_Shared', 'enqueue_icon_font_frontend']);

class Path_Pilot_Shared {
    // --- Constants ---
    const SLUG = 'path-pilot';
    const REST_NAMESPACE = 'path-pilot/v1';

    // --- Activation: Create/alter custom tables ---
    public static function activate() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Create visit paths table
        $table_name = $wpdb->prefix . 'path_pilot_visit_paths';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(64) NOT NULL,
            paths text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY session_id (session_id)
        ) $charset_collate;";

        dbDelta($sql);

        // Create recommendation clicks table
        $table_name = $wpdb->prefix . 'path_pilot_rec_clicks';

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(64) NOT NULL,
            page_id bigint(20) NOT NULL,
            rec_page_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY session_id (session_id)
        ) $charset_collate;";

        dbDelta($sql);

        // Create vectors table
        $table_name = $wpdb->prefix . 'path_pilot_vectors';

        $sql = "CREATE TABLE $table_name (
            post_id bigint(20) NOT NULL,
            embedding text NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (post_id)
        ) $charset_collate;";

        dbDelta($sql);

        // Create events table for more detailed analytics
        $table_name = $wpdb->prefix . 'path_pilot_events';

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(64) NOT NULL,
            event_type varchar(50) NOT NULL,
            page_id bigint(20) DEFAULT NULL,
            device_type varchar(20) DEFAULT NULL,
            path varchar(255) DEFAULT NULL,
            duration int(11) DEFAULT 0 NOT NULL,
            screen_width int(11) DEFAULT 0,
            screen_height int(11) DEFAULT 0,
            viewport_width int(11) DEFAULT 0,
            viewport_height int(11) DEFAULT 0,
            referrer varchar(255) DEFAULT '',
            metadata text DEFAULT NULL,
            is_conversion tinyint(1) DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY session_id (session_id),
            KEY event_type (event_type),
            KEY page_id (page_id),
            KEY device_type (device_type),
            KEY is_conversion (is_conversion)
        ) $charset_collate;";

        dbDelta($sql);

        // Create daily stats table for snapshots
        $table_name = $wpdb->prefix . 'path_pilot_daily_stats';
        $sql = "CREATE TABLE $table_name (
            date date NOT NULL,
            conversions int(11) DEFAULT 0,
            conversion_rate float DEFAULT 0,
            page_views int(11) DEFAULT 0,
            unique_visitors int(11) DEFAULT 0,
            PRIMARY KEY (date)
        ) $charset_collate;";
        dbDelta($sql);

        // Initialize options
        add_option('path_pilot_ready', false);
        add_option('path_pilot_dev_mode', false);
        add_option('path_pilot_goal_pages', []);
        add_option('path_pilot_conversion_pages', []);
    }

    // --- Register shared REST endpoints (except chat which is pro-only) ---
    public static function register_rest_endpoints($instance) {
        register_rest_route(self::REST_NAMESPACE, '/nonce', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'handle_nonce'],
            'permission_callback' => '__return_true',
        ]);
        register_rest_route(self::REST_NAMESPACE, '/event', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_event'],
            'permission_callback' => [__CLASS__, 'check_rest_api_nonce'],
        ]);
        register_rest_route(self::REST_NAMESPACE, '/suggest', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_suggest'],
            'permission_callback' => [__CLASS__, 'check_rest_api_nonce'],
        ]);
        register_rest_route(self::REST_NAMESPACE, '/status', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'handle_status'],
            'permission_callback' => [__CLASS__, 'check_rest_api_nonce'],
        ]);
        register_rest_route(self::REST_NAMESPACE, '/rec-click', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_rec_click'],
            'permission_callback' => [__CLASS__, 'check_rest_api_nonce'],
        ]);
    }

    public static function handle_nonce() {
        return new \WP_REST_Response(['nonce' => wp_create_nonce('wp_rest')]);
    }

    /**
     * Check nonce for REST API requests.
     *
     * @param \WP_REST_Request $request
     * @return bool
     */
    public static function check_rest_api_nonce(\WP_REST_Request $request) {
        $nonce = $request->get_header('X-WP-Nonce');
        if (!$nonce) {
            return false;
        }
        return wp_verify_nonce($nonce, 'wp_rest');
    }

    // --- Shared REST Handlers ---
    /**
     * Handle the event REST endpoint
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
	public static function handle_event( $request ) {
		global $wpdb;

		// Start PHP session if not already started
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}

		$params = $request->get_params();
		if (isset($params['data'])) {
			$json = base64_decode($params['data']);
			$params = json_decode($json, true);
		}
		$sid        = sanitize_text_field( $params['sid'] ?? '' );
		$path       = isset( $params['path'] ) ? sanitize_text_field( $params['path'] ) : '';
		$post_id    = isset( $params['post_id'] ) ? intval( $params['post_id'] ) : 0;
		$device     = isset( $params['device_type'] ) ? sanitize_text_field( $params['device_type'] ) : '';
		$referrer   = isset( $params['referrer'] ) ? esc_url_raw( $params['referrer'] ) : '';
		$duration   = isset( $params['duration'] ) ? intval( $params['duration'] ) : 0;
		$screen_w   = isset( $params['screen_width'] ) ? intval( $params['screen_width'] ) : 0;
		$screen_h   = isset( $params['screen_height'] ) ? intval( $params['screen_height'] ) : 0;
		$event_type = isset( $params['event_type'] ) ? sanitize_text_field( $params['event_type'] ) : 'pageview';

		// New metadata fields
		$browser_name    = isset( $params['browser_name'] ) ? sanitize_text_field( $params['browser_name'] ) : '';
		$browser_version = isset( $params['browser_version'] ) ? sanitize_text_field( $params['browser_version'] ) : '';
		$os_name         = isset( $params['os_name'] ) ? sanitize_text_field( $params['os_name'] ) : '';
		$time_on_page    = isset( $params['time_on_page'] ) ? intval( $params['time_on_page'] ) : 0;
		$entrance        = isset( $params['entrance'] ) ? (bool) $params['entrance'] : false;
		$exit            = isset( $params['exit'] ) ? (bool) $params['exit'] : false;
		$scroll_depth    = isset( $params['scroll_depth'] ) ? intval( $params['scroll_depth'] ) : 0;
		$viewport_width  = isset( $params['viewport_width'] ) ? intval( $params['viewport_width'] ) : 0;
		$viewport_height = isset( $params['viewport_height'] ) ? intval( $params['viewport_height'] ) : 0;

		if ( empty( $device ) ) {
			$device = self::detect_device_type();
		}

		// --- SESSION PATH STORAGE ---
		if (!isset($_SESSION['path_pilot_paths'])) {
			$_SESSION['path_pilot_paths'] = [];
		}
		if (!isset($_SESSION['path_pilot_metadata'])) {
			$_SESSION['path_pilot_metadata'] = [];
		}
		$paths = isset($_SESSION['path_pilot_paths'][$sid]) ? $_SESSION['path_pilot_paths'][$sid] : [];
		if ($post_id > 0 && (empty($paths) || end($paths) != $post_id)) {
			$paths[] = $post_id;
		}
		$_SESSION['path_pilot_paths'][$sid] = $paths;

		// Store/update metadata in session
		$metadata = [
			'device_type' => $device,
			'referrer' => $referrer,
			'screen_width' => $screen_w,
			'screen_height' => $screen_h,
			'browser_name' => $browser_name,
			'browser_version' => $browser_version,
			'os_name' => $os_name,
			'time_on_page' => $time_on_page,
			'entrance' => $entrance,
			'exit' => $exit,
			'scroll_depth' => $scroll_depth,
			'viewport_width' => $viewport_width,
			'viewport_height' => $viewport_height
		];
		if (isset($params['metadata']) && is_array($params['metadata'])) {
			foreach ($params['metadata'] as $key => $value) {
				$metadata['custom_' . sanitize_key($key)] = $value;
			}
		}
		$_SESSION['path_pilot_metadata'][$sid] = $metadata;

		// Determine if this is a goal or conversion page
		$is_goal_page = false;
		$is_conversion_page = false;

		$goal_pages = self::get_goal_pages();
		$conversion_pages = self::get_conversion_pages();

		if ( in_array( $post_id, $goal_pages ) ) {
			$is_goal_page = true;
		}
		if ( in_array( $post_id, $conversion_pages ) ) {
			$is_conversion_page = true;
		}

		// Always log the event in the events table
		self::track_event($sid, $event_type, $post_id, [
			'path'       => $path,
			'referrer'   => $referrer,
			'device_type'=> $device,
			'duration'   => $duration,
			'metadata'   => json_encode($params['metadata'] ?? array()),
			'is_conversion' => ($event_type === 'pageview' && ($is_goal_page || $is_conversion_page)) ? 1 : 0,
			'created_at' => current_time('mysql', true),
		]);

		// If this is an explicit conversion pageview, record an 'explicit_conversion' event
		if ($event_type === 'pageview' && $is_conversion_page) {
			self::track_event($sid, 'explicit_conversion', $post_id, [
				'path'       => $path,
				'referrer'   => $referrer,
				'device_type'=> $device,
				'duration'   => $duration,
				'metadata'   => json_encode($params['metadata'] ?? array()),
				'is_conversion' => 1, // Explicit conversion, always set to 1
				'created_at' => current_time('mysql', true),
			]);
		}

		// If this is a goal pageview, track goal completion (requires min hops)
		if ($event_type === 'pageview' && $is_goal_page) {
			self::track_goal_completion($sid);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'session_id' => $sid,
				'is_goal_page' => $is_goal_page,
				'is_conversion_page' => $is_conversion_page,
			),
			200
		);
	}

    public static function handle_suggest($request) {
        $params = $request->get_json_params();
        $current_path = isset($params['current']) ? sanitize_text_field($params['current']) : '';
        $history = isset($params['history']) && is_array($params['history']) ? array_map('sanitize_text_field', $params['history']) : [];
        $dev_mode = (bool) get_option('path_pilot_dev_mode', false);
        $ready = $dev_mode ? true : (bool) get_option('path_pilot_ready', false);
        $goal_pages = get_option('path_pilot_goal_pages', []);
        $goal_pages = array_map('intval', (array)$goal_pages); // Ensure integer IDs
        $fallback = [];

        // Always call the recommender - it has its own comprehensive fallback logic
        $recs = Path_Pilot_Recommender::get_recommendations($current_path, $history);
        Log::info('Path Pilot: Got ' . count($recs) . ' recommendations from recommender');

        // Filter out any recs that are conversion pages (defensive, in case recommender changes)
        $conversion_pages = get_option('path_pilot_conversion_pages', []);
        $conversion_pages = array_map('intval', (array)$conversion_pages);

        $original_count = count($recs);
        $recs = array_filter($recs, function($rec) use ($conversion_pages) {
            $is_conversion = in_array($rec['page_id'], $conversion_pages);
            if ($is_conversion) {
                Log::info('Path Pilot: Filtering out conversion page: ' . $rec['title'] . ' (ID: ' . $rec['page_id'] . ')');
            }
            return !$is_conversion;
        });
        $recs = array_values($recs); // Reindex
        Log::info('Path Pilot: After conversion page filtering: ' . count($recs) . ' recommendations (filtered out ' . ($original_count - count($recs)) . ')');

        apply_filters('path_pilot_rewrite_recommendation_descriptions', $recs);

        return [
            'mode' => 'suggest',
            'recommendations' => $recs
        ];
    }

    public static function handle_status($request) {
        $dev_mode = (bool) get_option('path_pilot_dev_mode', false);
        $ready = $dev_mode ? true : (bool) get_option('path_pilot_ready', false);
        $cta_text = get_option('path_pilot_cta_text', 'Need a hand?');
        $chat_label = get_option('path_pilot_chat_label', 'Path Pilot');

        return [
            'ready' => $ready,
            'dev_mode' => $dev_mode,
            'cta_text' => $cta_text,
            'chat_label' => $chat_label,
            'stats' => get_option('path_pilot_path_stats', [])
        ];
    }

    public static function handle_rec_click($request) {
        global $wpdb;
        $params = $request->get_json_params();
        $session_id = sanitize_text_field($params['session_id'] ?? self::get_session_id());
        $page_id = intval($params['page_id'] ?? 0);

        if (!$page_id) return ['ok' => false, 'error' => 'Missing page_id'];

        $wpdb->insert($wpdb->prefix . 'path_pilot_rec_clicks', [
            'session_id' => $session_id,
            'page_id' => $page_id,
            'clicked_at' => current_time('mysql')
        ]);

        return ['ok' => true];
    }

    // --- Shared Utility Methods ---

    // Common session ID handling
    public static function get_session_id() {
        if (isset($_COOKIE['path_pilot_sid'])) return sanitize_text_field($_COOKIE['path_pilot_sid']);
        $sid = wp_generate_uuid4();
        setcookie('path_pilot_sid', $sid, time() + 3600 * 24 * 30, COOKIEPATH, COOKIE_DOMAIN);
        return $sid;
    }

    // Get goal pages (utility)
    public static function get_goal_pages() {
        $goal_pages = get_option('path_pilot_goal_pages', []);
        return array_map('intval', (array)$goal_pages);
    }

    // Get conversion pages (utility)
    public static function get_conversion_pages() {
        $conversion_pages = get_option('path_pilot_conversion_pages', []);
        return array_map('intval', (array)$conversion_pages);
    }

    // Get allowed content types (utility)
    public static function get_allowed_content_types() {
        $allowed = get_option('path_pilot_allowed_content_types', null);
        if ($allowed === null || empty($allowed)) {
            // Fallback to current behavior for backward compatibility
            return ['page', 'post'];
        }
        return array_values((array)$allowed); // Ensure array and reindex
    }

    // Set allowed content types (utility)
    public static function set_allowed_content_types($content_types) {
        $content_types = array_values((array)$content_types); // Ensure array and reindex
        if (empty($content_types)) {
            // Don't allow empty selection - fallback to defaults
            $content_types = ['page', 'post'];
            Log::info('Path Pilot: Empty content types provided, using defaults');
        }
        update_option('path_pilot_allowed_content_types', $content_types);
        Log::info('Path Pilot: Updated allowed content types to: ' . implode(', ', $content_types));
        return $content_types;
    }

    // Path analysis
    public static function analyze_paths() {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT paths FROM {$wpdb->prefix}path_pilot_visit_paths");
        $path_counts = [];

        foreach ($rows as $row) {
            $path_json = $row->paths;
            $path_counts[$path_json] = ($path_counts[$path_json] ?? 0) + 1;
        }

        arsort($path_counts);
        $unique = count($path_counts);
        $ready = false;
        $top_paths = array_slice($path_counts, 0, 5, true);

        // Robust readiness criteria for meaningful recommendations:
        // Check installation age (minimum 2 weeks)
        $activation_date = get_option('path_pilot_activation_date');
        if (!$activation_date) {
            // If not set, set it now and assume fresh install
            $activation_date = time();
            update_option('path_pilot_activation_date', $activation_date);
        }
        $days_since_activation = (time() - $activation_date) / (60 * 60 * 24);

        // Get additional metrics for robust readiness check
        $total_sessions = $wpdb->get_var("SELECT COUNT(DISTINCT session_id) FROM {$wpdb->prefix}path_pilot_events WHERE event_type = 'pageview'");
        $total_pageviews = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}path_pilot_events WHERE event_type = 'pageview'");

        // Multi-factor readiness criteria:
        $ready = false;

        // Option 1: Met minimum thresholds (can activate before 2 weeks)
        if ($unique >= 50 && // At least 50 unique visitor paths
            $total_sessions >= 100 && // At least 100 unique visitors
            $total_pageviews >= 500) { // At least 500 total page views

            // Additional check: ensure we have meaningful traffic distribution
            $counts = array_values($path_counts);
            if (count($counts) >= 10 && $counts[9] >= 2) { // Even the 10th most popular path has 2+ visits
                $ready = true;
            }
        }

        // Option 2: Auto-enable after 2 weeks regardless of data volume
        if ($days_since_activation >= 14) {
            $ready = true;
        }

        update_option('path_pilot_ready', $ready);
        update_option('path_pilot_path_stats', [
            'unique' => $unique,
            'top_paths' => $top_paths
        ]);
    }

    // Common enqueue scripts for both free and pro (but with condition for AI features)
    public static function enqueue_scripts() {

        // Check if we're on admin and exit early
        if (is_admin()) {
            Log::info('Path Pilot: Exiting early - is_admin() is true');
            return;
        }

        $has_valid_api_key = false;
        $chat_enabled = apply_filters('path_pilot_chat_enabled', false);
        $dev_mode = (bool) get_option('path_pilot_dev_mode', false);
        $ready = $dev_mode ? true : (bool) get_option('path_pilot_ready', false);
        $cta_text = get_option('path_pilot_cta_text', 'Need a hand?');
        $recommend_label = get_option('path_pilot_recommend_label', 'Recommended for you:');
        $chat_label = get_option('path_pilot_chat_label', 'Path Pilot');

        // Get the main plugin file path
        $main_plugin_file = dirname(dirname(dirname(__FILE__))) . '/path-pilot-pro.php';

        // Enqueue CSS
        wp_enqueue_style(
            self::SLUG . '-styles',
            plugins_url('assets/css/style.css', $main_plugin_file),
            [],
            PATH_PILOT_VERSION
        );

        // Always enqueue tracking script
        wp_enqueue_script(
            self::SLUG . '-tracking',
            plugins_url('scripts/tracking.js', $main_plugin_file),
            [],
            PATH_PILOT_VERSION,
            false // Load in header instead of footer
        );

        // Add localization data for tracking
        $localize_result = wp_localize_script(
            self::SLUG . '-tracking',
            'path_pilot_data',
            [
                'rest_url' => rest_url(self::REST_NAMESPACE . '/'),
                'nonce' => wp_create_nonce('wp_rest'),
                'version' => PATH_PILOT_VERSION,
                'dev_mode' => $dev_mode,
                'is_pro' => Path_Pilot::is_pro(),
                'has_valid_api_key' => $has_valid_api_key,
                'post_id' => get_the_ID(),
            ]
        );
        Log::info('Path Pilot: wp_localize_script result = ' . ($localize_result ? 'success' : 'failed'));

        // Always enqueue UI/interactivity - widget should always be available
        // Only the chat feature is gated behind Pro license, not the widget itself
            Log::info('Path Pilot: Enqueuing index.js script...');
            $script_url = plugins_url('scripts/index.js', $main_plugin_file);
            Log::info('Path Pilot: Script URL = ' . $script_url);

            wp_enqueue_script(
                'path-pilot-interactivity',
                $script_url,
                [],
                PATH_PILOT_VERSION,
                false // Load in header instead of footer
            );

            wp_localize_script(
                'path-pilot-interactivity',
                'PathPilotStatus',
                [
                    'ready' => $ready,
                    'dev_mode' => $dev_mode,
                    'cta_text' => $cta_text,
                    'recommend_label' => $recommend_label,
                    'chat_label' => $chat_label,
                    'is_pro' =>Path_Pilot::is_pro(),
                    'has_valid_api_key' => $has_valid_api_key,
                    'chat_enabled' => $chat_enabled,
                    'icon_css_url' => plugins_url('assets/css/path-pilot-icons.css', $main_plugin_file),
                    'nonce' => wp_create_nonce('wp_rest'),
                ]
            );
    }

    // Get most common page 2 hops before goal page (for basic recommendations)
    public static function get_top_pre_goal_page($current_path = '', $goal_pages = []) {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT paths FROM {$wpdb->prefix}path_pilot_visit_paths");
        $counts = [];

        foreach ($rows as $row) {
            $path = json_decode($row->paths, true);
            if (!is_array($path) || count($path) < 3) continue;

            $goal_id = end($path);
            if (!in_array($goal_id, $goal_pages)) continue;

            $pre2_id = $path[count($path) - 3];

            // Exclude goal pages and current page
            if (in_array($pre2_id, $goal_pages)) continue;

            if ($current_path) {
                $current_id = Path_Pilot_Recommender::get_page_id_by_path($current_path);
                if ($pre2_id == $current_id) continue;
            }

            // Optionally exclude home page (ID 1)
            if ($pre2_id == 1) continue;

            $counts[$pre2_id] = ($counts[$pre2_id] ?? 0) + 1;
        }

        if (empty($counts)) return null;

        arsort($counts);
        return array_key_first($counts);
    }

    // --- Helper method to detect device type ---
    private static function detect_device_type() {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';

        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $user_agent)) {
            return 'tablet';
        }

        if (preg_match('/Mobile|Android|iP(hone|od)|IEMobile|BlackBerry|Kindle|Silk-Accelerated|Opera Mini|Opera Mobi|webOS/i', $user_agent)) {
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Get the total number of published pages and posts.
     * @return int Total number of pages and posts.
     */
    public static function get_total_pages() {
        // Use allowed content types for consistency with filtering
        $allowed_content_types = self::get_allowed_content_types();

        $total_pages_count = 0;
        foreach ($allowed_content_types as $post_type) {
            $count = wp_count_posts($post_type);
            $total_pages_count += isset($count->publish) ? $count->publish : 0;
        }

        return $total_pages_count;
    }

    /**
     * Records a goal completion event with metadata (requires minimum path hops)
     *
     * @param string $session_id The session ID
     * @return bool Whether the goal completion was successfully recorded
     */
    public static function track_goal_completion($session_id) {
        global $wpdb;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $table_name = $wpdb->prefix . 'path_pilot_visit_paths';
        // Retrieve path and metadata from session
        $paths = isset($_SESSION['path_pilot_paths'][$session_id]) ? $_SESSION['path_pilot_paths'][$session_id] : [];
        // Get minimum hops from settings (default 3)
        $min_hops = (int) get_option('path_pilot_min_hops', 3);

        // Only track if the path meets the minimum hop requirement AND the last page is a goal page
        $goal_pages = self::get_goal_pages();
        $last_page_id = !empty($paths) ? end($paths) : null;

        if (!empty($paths) && count($paths) >= $min_hops && $last_page_id && in_array($last_page_id, $goal_pages)) {
            $wpdb->insert(
                $table_name,
                array(
                    'session_id' => $session_id,
                    'paths' => json_encode($paths),
                    'created_at' => current_time('mysql', true)
                ),
                array('%s', '%s', '%s')
            );
            // Also record this as an event (optional, as handle_event already records pageview conversions)
            self::track_event($session_id, 'goal_completion', $last_page_id);
        }

        // Clear the session path and metadata for this session after a completion (goal or explicit)
        unset($_SESSION['path_pilot_paths'][$session_id]);
        unset($_SESSION['path_pilot_metadata'][$session_id]);
        return true;
    }

    // Daily snapshot cron job
    public static function run_daily_snapshot() {
        global $wpdb;

        // Calculate conversions for the previous day
        $yesterday = gmdate('Y-m-d', strtotime('-1 day'));

        // Total unique sessions that had a pageview event for the previous day
        $unique_visitors_yesterday = $wpdb->get_var(
            $wpdb->prepare("
                SELECT COUNT(DISTINCT session_id)
                FROM {$wpdb->prefix}path_pilot_events
                WHERE event_type = 'pageview'
                AND DATE(created_at) = %s
            ", $yesterday)
        );
        if (is_null($unique_visitors_yesterday)) $unique_visitors_yesterday = 0;

        // Total page views for the previous day
        $page_views_yesterday = $wpdb->get_var(
            $wpdb->prepare("
                SELECT COUNT(*)
                FROM {$wpdb->prefix}path_pilot_events
                WHERE event_type = 'pageview'
                AND DATE(created_at) = %s
            ", $yesterday)
        );
        if (is_null($page_views_yesterday)) $page_views_yesterday = 0;

        // Count explicit conversions for the previous day
        $explicit_conversions_yesterday = $wpdb->get_var(
            $wpdb->prepare("
                SELECT COUNT(*)
                FROM {$wpdb->prefix}path_pilot_events
                WHERE event_type = 'explicit_conversion'
                AND DATE(created_at) = %s
            ", $yesterday)
        );
        if (is_null($explicit_conversions_yesterday)) $explicit_conversions_yesterday = 0;

        // Count goal completions (paths) for the previous day
        $goal_completions_yesterday = $wpdb->get_var(
            $wpdb->prepare("
                SELECT COUNT(*)
                FROM {$wpdb->prefix}path_pilot_visit_paths
                WHERE DATE(created_at) = %s
            ", $yesterday)
        );
        if (is_null($goal_completions_yesterday)) $goal_completions_yesterday = 0;

        // Total conversions for the day (explicit + goal paths)
        $total_conversions_yesterday = $explicit_conversions_yesterday + $goal_completions_yesterday;

        // Calculate conversion rate for the day
        $conversion_rate_yesterday = $unique_visitors_yesterday > 0 ?
            round(($total_conversions_yesterday / $unique_visitors_yesterday) * 100, 2) : 0;

        // Insert/update daily stats
        $table_name = $wpdb->prefix . 'path_pilot_daily_stats';
        $wpdb->replace(
            $table_name,
            array(
                'date' => $yesterday,
                'conversions' => $total_conversions_yesterday,
                'conversion_rate' => $conversion_rate_yesterday,
                'page_views' => $page_views_yesterday,
                'unique_visitors' => $unique_visitors_yesterday,
            ),
            array('%s', '%d', '%f', '%d', '%d')
        );
    }

    /**
     * Enqueue icon font CSS on the frontend
     */
    public static function enqueue_icon_font_frontend() {
        $main_plugin_file = dirname(dirname(dirname(__FILE__))) . '/path-pilot-pro.php';
        wp_enqueue_style(
            'path-pilot-icons',
            plugins_url('assets/css/path-pilot-icons.css', $main_plugin_file),
            [],
            PATH_PILOT_VERSION
        );
    }

    /**
     * Tracks an event in the path_pilot_events table.
     *
     * @param string $session_id
     * @param string $event_type
     * @param int|null $page_id
     * @param array $extra (optional) - Additional fields to merge into the event row
     * @return void
     */
    public static function track_event($session_id, $event_type, $page_id = null, $extra = []) {
        global $wpdb;
        $row = array_merge([
            'session_id' => $session_id,
            'event_type' => $event_type,
            'page_id'    => $page_id,
            'created_at' => current_time('mysql')
        ], $extra);
        $wpdb->insert($wpdb->prefix . 'path_pilot_events', $row);
    }

    // --- Multisite-safe activation redirect logic ---
    public static function register_activation_hook($file) {
        register_activation_hook($file, [__CLASS__, 'activation']);
    }

    public static function activation($network_wide) {
        if (is_multisite() && $network_wide) {
            return;
        }
        add_option('path_pilot_do_activation_redirect', true);
    }

    public static function maybe_redirect_to_settings() {
        if (get_option('path_pilot_do_activation_redirect', false)) {
            delete_option('path_pilot_do_activation_redirect');
            if (is_network_admin()) {
                return;
            }
            wp_safe_redirect(admin_url('admin.php?page=path-pilot-settings'));
            exit;
        }
    }
}
