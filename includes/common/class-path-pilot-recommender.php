<?php
namespace Path_Pilot;

// Path Pilot Recommendation logic
if (!defined('ABSPATH')) exit;

class Path_Pilot_Recommender {
    // Get recommendations for the current session
    public static function get_recommendations($current_path, $history, $metadata = []) {
        Log::info('Path Pilot: get_recommendations called with current_path=' . $current_path);
        global $wpdb;

        // Get allowed content types from settings instead of hard-coding
        $allowed_content_types = Path_Pilot_Shared::get_allowed_content_types();
        Log::info('Path Pilot: Using content types for recommendations: ' . implode(', ', $allowed_content_types));

        // Get both pages and posts for recommendations
        $pages = get_posts([
            'post_type' => $allowed_content_types,
            'post_status' => 'publish',
            'numberposts' => -1
        ]);
        $goal_pages = get_option('path_pilot_goal_pages', []);
        $goal_pages = array_map('intval', (array)$goal_pages); // Ensure integer IDs
        $conversion_pages = get_option('path_pilot_conversion_pages', []);
        $conversion_pages = array_map('intval', (array)$conversion_pages); // Ensure integer IDs

        // Convert current_path and history to page IDs
        $user_path = array_map(function($p) {
            $id = intval($p);
            if ($id > 0) return $id;
            // Try to resolve if not numeric (for safety)
            return Path_Pilot_Recommender::get_page_id_by_path($p);
        }, array_merge($history, [$current_path]));
        $user_path = array_filter($user_path);
        $N = 3;
        $user_path = array_slice($user_path, -$N);
        $next_counts = [];

        $rows = $wpdb->get_results("SELECT paths FROM {$wpdb->prefix}path_pilot_visit_paths WHERE 1=1");

        foreach ($rows as $row) {
            $path_arr = json_decode($row->paths, true);
            // Ignore legacy URL-based paths
            if (!is_array($path_arr) || !is_numeric(implode('', $path_arr))) continue;

            // Calculate weight based on metadata match
            $weight = 1.0;

            for ($i = 0; $i < count($path_arr) - count($user_path); $i++) {
                $match = true;
                for ($j = 0; $j < count($user_path); $j++) {
                    if ($path_arr[$i + $j] != $user_path[$j]) {
                        $match = false;
                        break;
                    }
                }
                if ($match && isset($path_arr[$i + count($user_path)])) {
                    $next = $path_arr[$i + count($user_path)];
                    // Exclude conversion pages and current path from being recommended
                    if (!in_array($next, $user_path) && !in_array($next, $conversion_pages)) {
                        $next_counts[$next] = ($next_counts[$next] ?? 0) + $weight;
                    }
                }
            }
        }
        arsort($next_counts);
        $recs = [];
        $related_path_recs = [];
        $most_viewed_recs = [];
        $newest_recs = [];
        $total = array_sum($next_counts);
        // Collect related path recommendations (priority)
        foreach (array_keys($next_counts) as $page_id) {
            if (in_array($page_id, $conversion_pages)) continue; // Exclude conversion pages
            if (in_array($page_id, $user_path)) continue;
            $page = get_post($page_id);
            if ($page && $page->post_status === 'publish') {
                $url = parse_url(get_permalink($page->ID), PHP_URL_PATH);
                $synopsis = get_post_meta($page->ID, 'path_pilot_synopsis', true);
                if (!$synopsis) {
                    $synopsis = has_excerpt($page->ID) ? get_the_excerpt($page->ID) : wp_trim_words($page->post_content, 40, '...');
                }
                $percent = $total > 0 ? round(100 * $next_counts[$page_id] / $total) : 0;
                $related_path_recs[] = [
                    'title' => $page->post_title,
                    'url' => $url,
                    'page_id' => $page->ID,
                    'score' => $percent,
                    'synopsis' => $synopsis ? $synopsis : '',
                    'badge' => 'conversion_path'
                ];
            }
            if (count($related_path_recs) >= 3) break;
        }
        // Fallback: most viewed pages from events table
        if (count($related_path_recs) < 3) {
            $most_viewed = $wpdb->get_results($wpdb->prepare('
                SELECT page_id, COUNT(*) as view_count
                FROM ' . $wpdb->prefix . 'path_pilot_events
                WHERE event_type = %s AND page_id > 0
                GROUP BY page_id
                ORDER BY view_count DESC
                LIMIT %d
            ', 'pageview', 5));
            foreach ($most_viewed as $row) {
                if (in_array($row->page_id, $conversion_pages)) continue; // Exclude conversion pages
                if (in_array($row->page_id, $user_path)) continue;
                if (!in_array($row->page_id, array_column($related_path_recs, 'page_id'))) {
                    $post = get_post($row->page_id);
                    if (!$post) continue;
                    $url = parse_url(get_permalink($post->ID), PHP_URL_PATH);
                    $synopsis = get_post_meta($post->ID, 'path_pilot_synopsis', true);
                    if (!$synopsis) {
                        $synopsis = has_excerpt($post->ID) ? get_the_excerpt($post->ID) : wp_trim_words($post->post_content, 40, '...');
                    }
                    $most_viewed_recs[] = [
                        'title' => $post->post_title,
                        'url' => $url,
                        'page_id' => $post->ID,
                        'score' => 0,
                        'synopsis' => $synopsis ? $synopsis : '',
                        'badge' => 'popular'
                    ];
                }
            }
        }
        // Fallback: newest published pages and posts
        if (count($related_path_recs) + count($most_viewed_recs) < 3) {
            $newest_pages = get_posts([
                'post_type' => $allowed_content_types,
                'sort_column' => 'post_date',
                'sort_order' => 'desc',
                'number' => 5,
                'post_status' => 'publish'
            ]);
            foreach ($newest_pages as $page) {
                if (in_array($page->ID, $conversion_pages)) continue; // Exclude conversion pages
                if (in_array($page->ID, $user_path)) continue;
                if (!in_array($page->ID, array_column($related_path_recs, 'page_id')) && !in_array($page->ID, array_column($most_viewed_recs, 'page_id'))) {
                    $url = parse_url(get_permalink($page->ID), PHP_URL_PATH);
                    $synopsis = get_post_meta($page->ID, 'path_pilot_synopsis', true);
                    if (!$synopsis) {
                        $synopsis = has_excerpt($page->ID) ? get_the_excerpt($page->ID) : wp_trim_words($page->post_content, 40, '...');
                    }
                    $newest_recs[] = [
                        'title' => $page->post_title,
                        'url' => $url,
                        'page_id' => $page->ID,
                        'score' => 0,
                        'synopsis' => $synopsis ? $synopsis : '',
                        'badge' => 'newest'
                    ];
                }
            }
        }
        // Always prioritize related path recs, then randomly mix the rest
        $final_recs = [];
        $seen_page_ids = [];

        // Add related path recommendations first
        foreach ($related_path_recs as $rec) {
            if (!in_array($rec['page_id'], $seen_page_ids)) {
                $final_recs[] = $rec;
                $seen_page_ids[] = $rec['page_id'];
            }
            if (count($final_recs) >= 3) break;
        }

        // Fill from most viewed if needed
        if (count($final_recs) < 3) {
            foreach ($most_viewed_recs as $rec) {
                if (!in_array($rec['page_id'], $seen_page_ids)) {
                    $final_recs[] = $rec;
                    $seen_page_ids[] = $rec['page_id'];
                }
                if (count($final_recs) >= 3) break;
            }
        }

        // Fill from newest if needed
        if (count($final_recs) < 3) {
            foreach ($newest_recs as $rec) {
                if (!in_array($rec['page_id'], $seen_page_ids)) {
                    $final_recs[] = $rec;
                    $seen_page_ids[] = $rec['page_id'];
                }
                if (count($final_recs) >= 3) break;
            }
        }

        // If still less than 3, fill with any other published page/post
        if (count($final_recs) < 3) {
            Log::info('Path Pilot: Only ' . count($final_recs) . ' recommendations so far, filling with random pages');
            $any_pages = get_posts([
                'post_type' => $allowed_content_types,
                'post_status' => 'publish',
                'numberposts' => 10, // Fetch a few more to find unique ones
                'orderby' => 'rand', // Get random ones for variety
            ]);
            Log::info('Path Pilot: Found ' . count($any_pages) . ' potential pages for fallback');

            foreach ($any_pages as $page) {
                if (count($final_recs) >= 3) break;

                Log::info('Path Pilot: Checking page ID ' . $page->ID . ' (' . $page->post_title . ')');

                // Exclude conversion pages, user path pages, and already seen pages
                if (in_array($page->ID, $conversion_pages)) {
                    Log::info('Path Pilot: Skipping page ID ' . $page->ID . ' - is conversion page');
                    continue;
                }
                if (in_array($page->ID, $user_path)) {
                    Log::info('Path Pilot: Skipping page ID ' . $page->ID . ' - is in user path');
                    continue;
                }
                if (in_array($page->ID, $seen_page_ids)) {
                    Log::info('Path Pilot: Skipping page ID ' . $page->ID . ' - already seen');
                    continue;
                }

                $url = parse_url(get_permalink($page->ID), PHP_URL_PATH);
                $synopsis = get_post_meta($page->ID, 'path_pilot_synopsis', true);
                if (!$synopsis) {
                    $synopsis = has_excerpt($page->ID) ? get_the_excerpt($page->ID) : wp_trim_words($page->post_content, 40, '...');
                }
                $final_recs[] = [
                    'title' => $page->post_title,
                    'url' => $url,
                    'page_id' => $page->ID,
                    'score' => 0, // No specific score for these
                    'synopsis' => $synopsis ? $synopsis : '',
                    'badge' => 'popular' // Generic badge
                ];
                $seen_page_ids[] = $page->ID;
                Log::info('Path Pilot: Added fallback page ID ' . $page->ID . ' (' . $page->post_title . ')');
            }
            Log::info('Path Pilot: Final recommendation count: ' . count($final_recs));
        }

        Log::info('Path Pilot: Returning ' . count($final_recs) . ' final recommendations');
        return $final_recs;
    }

    // Helper: get page ID by path
    public static function get_page_id_by_path($path) {
        // Convert path to full URL if needed
        if (strpos($path, 'http') !== 0) {
            $site_url = get_site_url();
            $url = rtrim($site_url, '/') . $path;
        } else {
            $url = $path;
        }

        // Try url_to_postid first (works for pages and some posts)
        $post_id = url_to_postid($url);

        // If that fails, try alternative methods for blog posts
        if (!$post_id) {
            // Parse the URL to get the path
            $parsed_url = parse_url($url);
            $path_only = isset($parsed_url['path']) ? $parsed_url['path'] : '';

            // Try to get post by path (for blog posts with custom permalinks)
            $post = get_page_by_path($path_only, OBJECT, 'post');
            if ($post) {
                $post_id = $post->ID;
            } else {
                // Try to match against all posts by comparing permalink
                $allowed_content_types = Path_Pilot_Shared::get_allowed_content_types();
                $posts = get_posts([
                    'post_type' => $allowed_content_types,
                    'post_status' => 'publish',
                    'numberposts' => -1,
                    'fields' => 'ids'
                ]);

                foreach ($posts as $pid) {
                    $permalink = get_permalink($pid);
                    if ($permalink === $url || parse_url($permalink, PHP_URL_PATH) === $path_only) {
                        $post_id = $pid;
                        break;
                    }
                }
            }
        }

        return $post_id ? intval($post_id) : 0;
    }
}
