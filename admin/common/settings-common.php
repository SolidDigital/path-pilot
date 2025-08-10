<?php
namespace Path_Pilot;

// Common settings for both free and pro
$goal_pages_selected = get_option('path_pilot_goal_pages', []);
$conversion_pages_selected = get_option('path_pilot_conversion_pages', []);
$min_hops = (int)get_option('path_pilot_min_hops', 3);
$pages = get_posts([
    'post_type'   => 'page',
    'post_status' => 'publish',
    'numberposts' => -1
]);

// Get content types and current selection
$allowed_content_types = Path_Pilot_Shared::get_allowed_content_types();
$all_post_types = get_post_types(['public' => true], 'objects');
// Remove attachment and nav_menu_item as they're not suitable for recommendations
unset($all_post_types['attachment']);
unset($all_post_types['nav_menu_item']);
?>

<div class="pp-home-section pp-margin-bottom pp-content-types-section">
    <h3 class="pp-section-heading"><i class="emoji-gear"></i> Content Types for Recommendations</h3>
    <div class="pp-home-protip">
        <i class="icon-pilot-icon"></i>
        <strong>Pro Tip:</strong> Select which types of content can appear in recommendations and AI chat responses. This affects both the drawer recommendations and AI chat suggestions.
    </div>
    <div class="pp-content-types-container" style="margin-top: 18px; background: #fff; border: 1px solid #e5e5e5; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.03); padding: 18px 20px; max-width: 600px;">
        <div style="margin-bottom: 12px; color: #666; font-size: 0.95rem;">
            Choose the content types that should be included in recommendations:
        </div>
        <div class="pp-content-types-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
            <?php foreach ($all_post_types as $post_type_slug => $post_type_obj): ?>
                <?php
                $count = wp_count_posts($post_type_slug);
                $published_count = isset($count->publish) ? $count->publish : 0;
                $is_checked = in_array($post_type_slug, $allowed_content_types);
                ?>
                <div class="pp-content-type-item" style="background: #f9f9f9; padding: 12px 14px; border-radius: 6px; border: 2px solid <?php echo $is_checked ? '#1976d2' : '#e0e0e0'; ?>;">
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; font-weight: 500;">
                        <input type="checkbox"
                               name="path_pilot_allowed_content_types[]"
                               value="<?php echo esc_attr($post_type_slug); ?>"
                               <?php checked($is_checked); ?>
                               style="margin-top: 2px;">
                        <div>
                            <div style="font-size: 1rem; margin-bottom: 2px;">
                                <?php echo esc_html($post_type_obj->labels->name); ?>
                            </div>
                            <div style="font-size: 0.85rem; color: #666;">
                                <?php echo $published_count; ?> published
                            </div>
                            <?php if (!empty($post_type_obj->description)): ?>
                                <div style="font-size: 0.8rem; color: #888; margin-top: 4px; font-style: italic;">
                                    <?php echo esc_html($post_type_obj->description); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
        <div style="margin-top: 14px; padding: 8px 12px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; font-size: 0.9rem; color: #856404;">
            <strong>Note:</strong> At least one content type must be selected. Changes will affect future recommendations and AI responses.
        </div>
    </div>
</div>

<div class="pp-home-section pp-margin-bottom pp-goal-pages-section">
    <h3 class="pp-section-heading"><i class="emoji-target"></i> Goal Pages</h3>
    <div class="pp-home-protip">
        <i class="icon-pilot-icon"></i>
        <strong>Pro Tip:</strong> Select pages that represent key objectives for your users. Path Pilot will recommend paths that guide users towards these goals.
    </div>
    <div class="pp-goal-pages-columns" style="display: flex; gap: 40px; align-items: flex-start; margin-top: 18px;">
        <div class="pp-goal-pages-available-box" style="background: #fff; border: 1px solid #e5e5e5; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.03); padding: 18px 16px; width: 340px; min-width: 240px; max-width: 400px; max-height: 420px; overflow-y: auto;">
            <label for="pp-goal-search" class="pp-goal-search-label" style="font-weight:600;margin-bottom:10px;font-size:1.08rem;">Website Pages</label>
            <input type="text" id="pp-goal-search" class="pp-goal-search" placeholder="Search pages..." style="width:100%;padding:8px 12px;margin:10px 0 16px 0;border:1px solid #ddd;border-radius:6px;font-size:1rem;">
            <ul class="pp-goal-pages-list" style="list-style:none;padding:0;margin:0;max-height:300px;overflow-y:auto;">
                <?php foreach ($pages as $page): ?>
                <li style="padding:6px 0;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;">
                    <label style="display:flex;align-items:center;gap:8px;width:100%;cursor:pointer;">
                    <input type="checkbox" name="path_pilot_goal_pages[]" value="<?php echo $page->ID; ?>" <?php checked(in_array($page->ID, $goal_pages_selected)); ?> style="margin-right:8px;">
                    <span><?php echo esc_html($page->post_title); ?></span>
                    </label>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="pp-goal-pages-selected-box" style="background: #fff; border: 1px solid #e5e5e5; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.03); padding: 18px 16px; min-width: 220px; max-width: 320px;">
            <div class="pp-goal-selected-title" style="font-weight:600;margin-bottom:10px;font-size:1.08rem;">Selected Goal Pages</div>
            <ul class="pp-goal-selected-list" style="list-style:none;padding:0;margin:0;">
                <?php foreach ($goal_pages_selected as $page_id):
                $page = get_post($page_id);
                if (!$page) continue;
                ?>
                <li style="padding:7px 0;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;gap:8px;">
                    <span class="pp-goal-checkmark" style="color:#2ecc40;font-size:1.2em;">&#10003;</span>
                    <span><?php echo esc_html($page->post_title); ?></span>
                </li>
                <?php endforeach; ?>
                <?php if (empty($goal_pages_selected)): ?>
                <li style="color:#aaa;font-style:italic;">No goal pages selected</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<div class="pp-home-section pp-margin-bottom pp-conversion-pages-section">
    <h3 class="pp-section-heading"><i class="emoji-star"></i> Conversion Pages</h3>
    <div class="pp-home-protip">
        <i class="icon-pilot-icon"></i>
        <strong>Pro Tip:</strong> Select pages that signify a completed action, like a "Thank You" page after a form submission or a purchase confirmation. These will be tracked as explicit conversions.
    </div>
    <div class="pp-conversion-pages-columns" style="display: flex; gap: 40px; align-items: flex-start; margin-top: 18px;">
        <div class="pp-conversion-pages-available-box" style="background: #fff; border: 1px solid #e5e5e5; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.03); padding: 18px 16px; width: 340px; min-width: 240px; max-width: 400px; max-height: 420px; overflow-y: auto;">
            <label for="pp-conversion-search" class="pp-conversion-search-label" style="font-weight:600;margin-bottom:10px;font-size:1.08rem;">Website Pages</label>
            <input type="text" id="pp-conversion-search" class="pp-conversion-search" placeholder="Search pages..." style="width:100%;padding:8px 12px;margin:10px 0 16px 0;border:1px solid #ddd;border-radius:6px;font-size:1rem;">
            <ul class="pp-conversion-pages-list" style="list-style:none;padding:0;margin:0;max-height:300px;overflow-y:auto;">
                <?php foreach ($pages as $page): ?>
                <li style="padding:6px 0;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;">
                    <label style="display:flex;align-items:center;gap:8px;width:100%;cursor:pointer;">
                    <input type="checkbox" name="path_pilot_conversion_pages[]" value="<?php echo $page->ID; ?>" <?php checked(in_array($page->ID, $conversion_pages_selected)); ?> style="margin-right:8px;">
                    <span><?php echo esc_html($page->post_title); ?></span>
                    </label>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="pp-conversion-pages-selected-box" style="background: #fff; border: 1px solid #e5e5e5; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.03); padding: 18px 16px; min-width: 220px; max-width: 320px;">
            <div class="pp-conversion-selected-title" style="font-weight:600;margin-bottom:10px;font-size:1.08rem;">Selected Conversion Pages</div>
            <ul class="pp-conversion-selected-list" style="list-style:none;padding:0;margin:0;">
                <?php foreach ($conversion_pages_selected as $page_id):
                $page = get_post($page_id);
                if (!$page) continue;
                ?>
                <li style="padding:7px 0;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;gap:8px;">
                    <span class="pp-goal-checkmark" style="color:#2ecc40;font-size:1.2em;">&#10003;</span>
                    <span><?php echo esc_html($page->post_title); ?></span>
                </li>
                <?php endforeach; ?>
                <?php if (empty($conversion_pages_selected)): ?>
                <li style="color:#aaa;font-style:italic;">No conversion pages selected</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function setupSearch(searchInputId, listSelector) {
        var search = document.getElementById(searchInputId);
        if (search) {
            search.addEventListener('input', function() {
                var filter = this.value.toLowerCase();
                document.querySelectorAll(listSelector).forEach(function(li) {
                    var text = li.textContent.toLowerCase();
                    li.style.display = text.includes(filter) ? '' : 'none';
                });
            });
        }
    }

    setupSearch('pp-goal-search', '.pp-goal-pages-list li');
    setupSearch('pp-conversion-search', '.pp-conversion-pages-list li');
});
</script>

<div class="pp-home-section pp-margin-bottom">
    <h3 class="pp-section-heading"><i class="emoji-cool"></i> Interface Settings</h3>
    <div class="pp-home-stat pp-stat-card">
        <div class="pp-home-stat-label">Minimum Path Hops</div>
        <div style="margin:20px 0;">
            <input type="number" min="1" max="10" step="1" name="path_pilot_min_hops" id="path_pilot_min_hops" value="<?php echo esc_attr($min_hops); ?>" style="width:60px; font-size:1.1rem; text-align:center;" />
            <label for="path_pilot_min_hops" style="margin-left:10px;font-weight:500;">Minimum steps required for a path to be saved (recommended: 3)</label>
        </div>
        <div class="pp-stat-description">
            Only paths with at least this many steps (pages) will be recorded as conversions. This helps ensure meaningful journeys are tracked.
        </div>
    </div>
</div>
