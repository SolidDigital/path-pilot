<?php
namespace Path_Pilot;

// Free settings page
?>
<div class="pp-content">
    <?php if (isset($_GET['updated']) && $_GET['updated'] === 'true'): ?>
        <div class="notice notice-success is-dismissible" style="margin: 20px 0; padding: 12px 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; color: #155724;">
            <strong>Settings saved successfully!</strong> Your Path Pilot configuration has been updated.
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="path-pilot-settings-form">
        <input type="hidden" name="action" value="path_pilot_save_settings">
        <?php wp_nonce_field('path_pilot_save_settings'); ?>
                <?php do_action('path_pilot_render_common_settings'); ?>
                <?php
                do_action('path_pilot_additional_settings');
                ?>
                <button type="submit" class="btn btn-primary" id="save-settings-btn" style="padding:12px 24px;font-weight:600;font-size:1rem;">Save Settings</button>
            </form>
        
            <?php
            // Shared loading overlay
            do_action('path_pilot_render_settings_save_overlay');
            ?>
        </div>
