<?php
namespace Path_Pilot;

// Free settings page
?>
<div class="pp-content">
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="path-pilot-settings-form">
        <input type="hidden" name="action" value="path_pilot_save_settings">
        <?php wp_nonce_field('path_pilot_save_settings'); ?>
        <?php include plugin_dir_path(__DIR__) . 'common/settings-common.php'; ?>
        <?php
        do_action('path_pilot_additional_settings');
        ?>
        <button type="submit" class="btn btn-primary" style="padding:12px 24px;font-weight:600;font-size:1rem;">Save Settings</button>
    </form>
</div>
