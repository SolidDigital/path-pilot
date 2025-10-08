<?php
namespace Path_Pilot;

// Path Pilot Admin Dashboard
if (!defined('ABSPATH')) exit;

// Display the analytics dashboard with new device and duration charts
function path_pilot_display_analytics($pages_coverage = 0, $days_active = 0, $pages_tracked = 0, $total_pages = 0) {
    global $wpdb;

    // Initialize variables with safe defaults
    $page_views = 0;
    $unique_visitors = 0;
    $avg_duration = 0;
    $total_completions = 0;
    $device_types = [];
    $duration_categories = [];

    try {
        // Check if tables exist before querying
        $events_table = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}path_pilot_events'");
        $paths_table = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}path_pilot_visit_paths'");

        if ($events_table) {
            // Check column existence
            $columns = $wpdb->get_results("DESCRIBE {$wpdb->prefix}path_pilot_events");
            $column_names = array_map(function($col) { return $col->Field; }, $columns);
            $has_required_columns = in_array('event_type', $column_names) &&
                                  in_array('session_id', $column_names) &&
                                  in_array('device_type', $column_names) &&
                                  in_array('duration', $column_names);

            if ($has_required_columns) {
                // Get basic stats
                $page_views = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}path_pilot_events WHERE event_type = 'pageview'");
                $unique_visitors = $wpdb->get_var("SELECT COUNT(DISTINCT session_id) FROM {$wpdb->prefix}path_pilot_events WHERE event_type = 'pageview'");
                $avg_duration = $wpdb->get_var("SELECT AVG(duration) FROM {$wpdb->prefix}path_pilot_events WHERE event_type = 'pageview' AND duration > 0");

                // Get device type distribution
                $device_types = $wpdb->get_results("
                    SELECT device_type, COUNT(*) as count
                    FROM {$wpdb->prefix}path_pilot_events
                    WHERE event_type = 'pageview' AND device_type IS NOT NULL
                    GROUP BY device_type
                ");

                // Get duration categories
                $duration_categories = $wpdb->get_results("
                    SELECT
                        CASE
                            WHEN duration < 5 THEN 'very_short'
                            WHEN duration >= 5 AND duration < 30 THEN 'short'
                            WHEN duration >= 30 AND duration < 120 THEN 'medium'
                            ELSE 'long'
                        END as duration_category,
                        COUNT(*) as count
                    FROM {$wpdb->prefix}path_pilot_events
                    WHERE event_type = 'pageview' AND duration > 0
                    GROUP BY duration_category
                ");
            }
        }

        if ($paths_table) {
            // Check column existence for completions
            $columns = $wpdb->get_results("DESCRIBE {$wpdb->prefix}path_pilot_visit_paths");
            $column_names = array_map(function($col) { return $col->Field; }, $columns);
            $total_completions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}path_pilot_visit_paths");

        }
    } catch (Exception $e) {
        // Silent error handling - keep default values
    }

    // --- Top Pages ---

    $popular_pages = $wpdb->get_results(
    "SELECT page_id, COUNT(*) as view_count
    FROM {$wpdb->prefix}path_pilot_events
    WHERE event_type = 'pageview' AND page_id > 0
    GROUP BY page_id
    ORDER BY view_count DESC
    LIMIT 3"
    );

    // --- Organic vs Direct ---
    $organic = 0; $direct = 0; $other = 0;
    $organic_domains = ['google.', 'bing.', 'yahoo.', 'duckduckgo.', 'baidu.', 'yandex.', 'aol.', 'ask.', 'ecosia.'];
    $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
    $ref_query = $wpdb->get_results("SELECT referrer FROM {$wpdb->prefix}path_pilot_events WHERE referrer IS NOT NULL");
    foreach ($ref_query as $row) {
        $ref = $row->referrer;
        if (empty($ref) || strpos($ref, $site_host) !== false) {
            $direct++;
        } else {
            $is_organic = false;
            foreach ($organic_domains as $domain) {
                if (stripos($ref, $domain) !== false) {
                    $is_organic = true;
                    break;
                }
            }
            if ($is_organic) {
                $organic++;
            } else {
                $other++;
            }
        }
    }
    $total_ref = $organic + $direct + $other;

    // --- Top Referrers ---
    $referrer_counts = [];
    foreach ($ref_query as $row) {
        $ref = $row->referrer;
        if (!empty($ref) && strpos($ref, $site_host) === false) {
            $referrer_counts[$ref] = ($referrer_counts[$ref] ?? 0) + 1;
        }
    }
    arsort($referrer_counts);
    $top_referrers = array_slice($referrer_counts, 0, 3, true);

    // Device type distribution
    $device_stats = $wpdb->get_results("
        SELECT device_type, COUNT(*) as count
        FROM {$wpdb->prefix}path_pilot_events
        WHERE event_type = 'pageview' AND device_type IS NOT NULL
        GROUP BY device_type
    ");

    $device_data = [
        'desktop' => 0,
        'tablet' => 0,
        'mobile' => 0,
        'other' => 0
    ];

    $total_with_device = 0;
    foreach ($device_stats as $stat) {
        if (array_key_exists($stat->device_type, $device_data)) {
            $device_data[$stat->device_type] = $stat->count;
        } else {
            $device_data['other'] += $stat->count;
        }
        $total_with_device += $stat->count;
    }

    // Visit duration stats
    $duration_stats = $wpdb->get_results("
        SELECT
            CASE
                WHEN duration < 10 THEN 'very_short'
                WHEN duration < 30 THEN 'short'
                WHEN duration < 120 THEN 'medium'
                ELSE 'long'
            END AS duration_type,
            COUNT(*) as count
        FROM {$wpdb->prefix}path_pilot_events
        WHERE event_type = 'pageview' AND duration > 0
        GROUP BY duration_type
    ");

    $duration_data = [
        'very_short' => ['label' => 'Less than 10s', 'count' => 0],
        'short' => ['label' => '10-30s', 'count' => 0],
        'medium' => ['label' => '30s-2m', 'count' => 0],
        'long' => ['label' => 'Over 2m', 'count' => 0]
    ];

    $total_with_duration = 0;
    foreach ($duration_stats as $stat) {
        if (array_key_exists($stat->duration_type, $duration_data)) {
            $duration_data[$stat->duration_type]['count'] = $stat->count;
        }
        $total_with_duration += $stat->count;
    }

    // Conversion Rate
    $total_sessions = $wpdb->get_var("SELECT COUNT(DISTINCT session_id) FROM {$wpdb->prefix}path_pilot_events WHERE event_type = 'pageview'");
    // commented out: $min and $max conversions were undefined
//    $target_conversions = max($min_conversions, $max_conversions - (($max_conversions - $min_conversions) * ($days_active / 14)));
    $conversion_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}path_pilot_visit_paths");
    $explicit_conversions_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}path_pilot_events WHERE event_type = 'explicit_conversion'");
    $conversion_count = intval($conversion_count) + intval($explicit_conversions_count);
    $conversion_rate = $total_sessions > 0 ? round(($conversion_count / $total_sessions) * 100, 1) : 0;

    // Path Length (average number of pageviews per session)
    $avg_path_length = $wpdb->get_var("
        SELECT AVG(cnt) FROM (
            SELECT session_id, COUNT(*) as cnt
            FROM {$wpdb->prefix}path_pilot_events
            WHERE event_type = 'pageview'
            GROUP BY session_id
        ) as path_lengths
    ");
    $avg_path_length = $avg_path_length ? round($avg_path_length, 1) : 0;

    // --- Top Conversion Landing Pages (external or direct only) ---
    $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
    $top_landing_counts = [];
    $landing_query = $wpdb->get_results("SELECT paths FROM {$wpdb->prefix}path_pilot_visit_paths WHERE paths IS NOT NULL AND paths != ''");
    foreach ($landing_query as $row) {
            $paths = json_decode($row->paths, true);
            if (is_array($paths) && count($paths) > 0) {
                $first = $paths[0];
                $top_landing_counts[$first] = ($top_landing_counts[$first] ?? 0) + 1;
            }
    }
    arsort($top_landing_counts);
    $top_landing_pages = array_slice($top_landing_counts, 0, 3, true);

    // Conversion paths tracked (Goal Completions)
    $goal_completions_tracked = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}path_pilot_visit_paths");

    // Explicit Conversions tracked
    $explicit_conversions_tracked = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}path_pilot_events WHERE event_type = 'explicit_conversion'");

    // Total conversions
    $total_conversions = $goal_completions_tracked + $explicit_conversions_tracked;

    // Display the dashboard
    ?>
    <div class="pp-stats-wrap">
        <h2>Path Pilot Analytics</h2>

        <?php if (empty($events_table) || !$has_required_columns): ?>
        <div class="notice notice-warning">
            <p>Path Pilot is still collecting data. Check back soon to see your analytics.</p>
        </div>
        <?php endif; ?>

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
            <?php if ($pages_coverage < 100): // Only show if not fully covered ?>
            <div class="pp-home-stat pp-stat-card">
                <div class="pp-home-stat-label">Content Coverage</div>
                <div class="pp-home-stat-value"><?php echo number_format($pages_tracked); ?> / <?php echo number_format($total_pages); ?></div>
                <div class="pp-home-stat-value-sub"><?php echo esc_html($pages_coverage); ?>% of site explored</div>
                <div class="pp-progress-bar">
                    <div class="pp-progress-value" style="width: <?php echo esc_attr($pages_coverage); ?>%"></div>
                </div>
                <div class="pp-stat-description">
                    <?php if ($pages_coverage < 25): ?>
                        Growing coverage! Path Pilot is mapping connections between your content.
                    <?php elseif ($pages_coverage < 75): ?>
                        Good coverage! Continue exploring your site to build a complete map.
                    <?php else: ?>
                        Excellent! Most of your site content is included in the recommendation model.
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Learning Period -->
            <?php if ($days_active < 14): // Only show if learning period is not complete yet ?>
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
            <?php endif; ?>

            <!-- Total Conversions -->
            <div class="pp-home-stat pp-stat-card">
                <div class="pp-home-stat-label">Total Conversions</div>
                <div class="pp-home-stat-value"><?php echo number_format($total_conversions); ?></div>
                <div class="pp-stat-description">
                    Combined count of explicit conversions (e.g., thank you page visits) and goal completions (paths reaching a goal page with minimum hops).
                </div>
            </div>

            <!-- Goal Completions -->
            <div class="pp-home-stat pp-stat-card">
                <div class="pp-home-stat-label">Goal Completions</div>
                <div class="pp-home-stat-value"><?php echo number_format($goal_completions_tracked); ?></div>
                <div class="pp-stat-description">
                    The number of visitor paths that successfully reached a designated goal page with the minimum required steps.
                </div>
            </div>

            <!-- Explicit Conversions -->
            <div class="pp-home-stat pp-stat-card">
                <div class="pp-home-stat-label">Explicit Conversions</div>
                <div class="pp-home-stat-value"><?php echo number_format($explicit_conversions_tracked); ?></div>
                <div class="pp-stat-description">
                    The number of visits to pages designated as direct conversion points (e.g., thank you pages).
                </div>
            </div>

            <!-- Device Type Distribution -->
            <div class="pp-home-stat pp-stat-card">
                <div class="pp-home-stat-label">Device Types</div>

                <?php if ($total_with_device > 0): ?>
                    <div class="pp-device-breakdown">
                        <div class="pp-stat-chart">
                            <div class="pp-chart-bar" style="width: <?php echo esc_attr(round(($device_data['desktop'] / $total_with_device) * 100)); ?>%" data-type="desktop"></div>
                            <div class="pp-chart-bar" style="width: <?php echo esc_attr(round(($device_data['tablet'] / $total_with_device) * 100)); ?>%" data-type="tablet"></div>
                            <div class="pp-chart-bar" style="width: <?php echo esc_attr(round(($device_data['mobile'] / $total_with_device) * 100)); ?>%" data-type="mobile"></div>
                            <div class="pp-chart-bar" style="width: <?php echo esc_attr(round(($device_data['other'] / $total_with_device) * 100)); ?>%" data-type="other"></div>
                        </div>
                        <div class="pp-chart-legend">
                            <div class="pp-legend-item" data-type="desktop">
                                <span class="pp-legend-color"></span>
                                <span class="pp-legend-label">Desktop: <?php echo esc_html(round(($device_data['desktop'] / $total_with_device) * 100)); ?>%</span>
                            </div>
                            <div class="pp-legend-item" data-type="mobile">
                                <span class="pp-legend-color"></span>
                                <span class="pp-legend-label">Mobile: <?php echo esc_html(round(($device_data['mobile'] / $total_with_device) * 100)); ?>%</span>
                            </div>
                            <div class="pp-legend-item" data-type="tablet">
                                <span class="pp-legend-color"></span>
                                <span class="pp-legend-label">Tablet: <?php echo esc_html(round(($device_data['tablet'] / $total_with_device) * 100)); ?>%</span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="pp-home-stat-value">No data yet</div>
                    <div class="pp-stat-waiting">Waiting for visitor data</div>
                <?php endif; ?>
                <div class="pp-stat-description">
                    Understand how your audience accesses your site across different devices.
                </div>
            </div>

            <!-- Visit Duration Stats -->
            <div class="pp-home-stat pp-stat-card">
                <div class="pp-home-stat-label">Visit Duration</div>

                <?php if ($total_with_duration > 0): ?>
                    <div class="pp-duration-breakdown">
                        <div class="pp-stat-chart">
                            <?php foreach ($duration_data as $type => $data): ?>
                                <div class="pp-chart-bar" style="width: <?php echo esc_attr(round(($data['count'] / $total_with_duration) * 100)); ?>%" data-type="<?php echo esc_attr($type); ?>"></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="pp-chart-legend">
                            <?php foreach ($duration_data as $type => $data):
                                if ($data['count'] > 0):
                            ?>
                                <div class="pp-legend-item" data-type="<?php echo esc_attr($type); ?>">
                                    <span class="pp-legend-color"></span>
                                    <span class="pp-legend-label"><?php echo esc_html($data['label']); ?>: <?php echo esc_html(round(($data['count'] / $total_with_duration) * 100)); ?>%</span>
                                </div>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="pp-home-stat-value">No data yet</div>
                    <div class="pp-stat-waiting">Waiting for duration data</div>
                <?php endif; ?>
                <div class="pp-stat-description">
                    See how long visitors spend on your pages. Longer visits often indicate engaging content.
                </div>
            </div>

            <!-- Conversion Rate -->
            <div class="pp-home-stat pp-stat-card">
                <div class="pp-home-stat-label">Conversion Rate</div>

                <?php if ($total_sessions > 0): ?>
                    <div class="pp-home-stat-value"><?php echo esc_html($conversion_rate); ?>%</div>
                    <?php if ($conversion_rate > 0): ?>
                        <div class="pp-stat-trend pp-trend-up">
                            <i class="emoji-hot icon-pilot-icon"></i> <?php echo esc_html($conversion_count); ?> total conversions
                        </div>
                    <?php else: ?>
                        <div class="pp-stat-waiting">No conversions tracked yet</div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="pp-home-stat-value">--</div>
                    <div class="pp-stat-waiting">Waiting for visitor data</div>
                <?php endif; ?>
                <div class="pp-stat-description">
                    <?php if ($conversion_rate == 0): ?>
                        Set up conversion tracking to see how many visitors complete key actions on your site.
                    <?php elseif ($conversion_rate < 2): ?>
                        Your conversion rate is typical. Path Pilot can help improve this over time.
                    <?php else: ?>
                        Great conversion rate! Your content is effectively guiding visitors to take action.
                    <?php endif; ?>
                </div>
            </div>

            <!-- Path Length -->
            <div class="pp-home-stat pp-stat-card">
                <div class="pp-home-stat-label">Average Path Length</div>

                <?php if ($avg_path_length > 0): ?>
                    <div class="pp-home-stat-value"><?php echo esc_html($avg_path_length); ?> pages</div>
                    <?php if ($avg_path_length > 1): ?>
                        <div class="pp-stat-trend pp-trend-up">
                            <i class="emoji-hot icon-pilot-icon"></i> Visitors exploring multiple pages
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="pp-home-stat-value">--</div>
                    <div class="pp-stat-waiting">Waiting for path data</div>
                <?php endif; ?>
                <div class="pp-stat-description">
                    <?php if ($avg_path_length <= 1): ?>
                        Most visitors currently view just one page. Path Pilot helps them discover more.
                    <?php elseif ($avg_path_length < 3): ?>
                        Visitors are exploring multiple pages. Path Pilot can help increase this further.
                    <?php else: ?>
                        Excellent! Your visitors are exploring several pages during their visits.
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top Pages -->
            <div class="pp-home-stat pp-stat-card">
                <div class="pp-home-stat-label">Top Pages</div>
                <?php if (count($popular_pages) > 0): ?>
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($popular_pages as $page):
                            $page_title = get_the_title($page->page_id);
                            if (empty($page_title)) continue;
                        ?>
                            <li><strong><?php echo esc_html($page_title); ?></strong> (<?php echo esc_html($page->view_count); ?> views)</li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="pp-home-stat-value">No data yet</div>
                    <div class="pp-stat-waiting">Waiting for pageview data</div>
                <?php endif; ?>
                <div class="pp-stat-description">Your most visited pages by total views.</div>
            </div>

            <!-- Organic vs Direct -->
            <div class="pp-home-stat pp-stat-card">
                <div class="pp-home-stat-label">Traffic Sources</div>
                <?php if ($total_ref > 0): ?>
                    <ul style="margin: 0; padding-left: 20px;">
                        <li><strong>Direct:</strong> <?php echo esc_html(round(($direct / $total_ref) * 100)); ?>%</li>
                        <li><strong>Organic:</strong> <?php echo esc_html(round(($organic / $total_ref) * 100)); ?>%</li>
                        <li><strong>Other:</strong> <?php echo esc_html(round(($other / $total_ref) * 100)); ?>%</li>
                    </ul>
                <?php else: ?>
                    <div class="pp-home-stat-value">No data yet</div>
                    <div class="pp-stat-waiting">Waiting for referrer data</div>
                <?php endif; ?>
                <div class="pp-stat-description">Breakdown of direct, organic, and other traffic sources.</div>
            </div>

            <!-- Top Referrers -->
            <div class="pp-home-stat pp-stat-card">
                <div class="pp-home-stat-label">Top Referrers</div>
                <?php if (count($top_referrers) > 0): ?>
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($top_referrers as $ref => $count): ?>
                            <li><?php echo esc_html($ref); ?> (<?php echo esc_html($count); ?>)</li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="pp-home-stat-value">No data yet</div>
                    <div class="pp-stat-waiting">Waiting for referrer data</div>
                <?php endif; ?>
                <div class="pp-stat-description">Top external sites sending visitors to you.</div>
            </div>

            <!-- Top Conversion Landing Pages -->
            <div class="pp-home-stat pp-stat-card">
                <div class="pp-home-stat-label">Top Conversion Landing Pages</div>
                <?php if (count($top_landing_pages) > 0): ?>
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($top_landing_pages as $page_id => $count):
                            $page_title = get_the_title($page_id);
                            if (empty($page_title)) continue;
                        ?>
                            <li><strong><?php echo esc_html($page_title); ?></strong> (<?php echo esc_html($count); ?> conversions)</li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="pp-home-stat-value">No data yet</div>
                    <div class="pp-stat-waiting">Waiting for conversion data</div>
                <?php endif; ?>
                <div class="pp-stat-description">Most common entry pages for sessions that converted.</div>
            </div>
        </div>
    </div>
    <?php
}
