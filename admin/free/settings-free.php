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
        <?php include plugin_dir_path(__DIR__) . 'common/settings-common.php'; ?>
        <?php
        do_action('path_pilot_additional_settings');
        ?>
        <button type="submit" class="btn btn-primary" id="save-settings-btn" style="padding:12px 24px;font-weight:600;font-size:1rem;">Save Settings</button>
    </form>
    
    <!-- Loading overlay -->
    <div id="pp-loading-overlay" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;justify-content:center;align-items:center;">
        <div style="background:#fff;padding:30px;border-radius:8px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,0.3);">
            <div style="width:40px;height:40px;border:4px solid #f3f3f3;border-top:4px solid #1976d2;border-radius:50%;animation:spin 1s linear infinite;margin:0 auto 15px;"></div>
            <div style="font-size:16px;font-weight:600;color:#333;">Saving Settings...</div>
        </div>
    </div>
    
    <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('path-pilot-settings-form');
        var saveBtn = document.getElementById('save-settings-btn');
        var overlay = document.getElementById('pp-loading-overlay');
        
        if (form && saveBtn && overlay) {
            form.addEventListener('submit', function(e) {
                // Show loading overlay
                overlay.style.display = 'flex';
                
                // Disable save button to prevent double submission
                saveBtn.disabled = true;
                saveBtn.textContent = 'Saving...';
                
                // Hide overlay after 30 seconds as fallback (in case redirect doesn't work)
                setTimeout(function() {
                    overlay.style.display = 'none';
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save Settings';
                    
                    // Show error message
                    var errorDiv = document.createElement('div');
                    errorDiv.style.cssText = 'margin: 20px 0; padding: 12px 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24;';
                    errorDiv.innerHTML = '<strong>Request timed out.</strong> The settings may not have been saved. Please try again.';
                    
                    // Insert error message before the form
                    form.parentNode.insertBefore(errorDiv, form);
                    
                    // Remove error message after 10 seconds
                    setTimeout(function() {
                        if (errorDiv.parentNode) {
                            errorDiv.parentNode.removeChild(errorDiv);
                        }
                    }, 10000);
                }, 30000);
            });
        }
    });
    </script>
</div>
