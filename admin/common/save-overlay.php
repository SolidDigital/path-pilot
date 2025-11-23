<?php
// This file contains the HTML, CSS, and JS for the settings save overlay.
// It is meant to be included in any settings page that uses the 'path-pilot-settings-form'.
?>
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
    var activateBtn = document.getElementById('pp-activate-license'); // Pro activate button
    var overlay = document.getElementById('pp-loading-overlay');
    
    var handleSubmit = function(e) {
        overlay.style.display = 'flex';
        
        // Disable both buttons if they exist
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';
        }
        if (activateBtn) {
            activateBtn.disabled = true;
            activateBtn.textContent = 'Activating...';
        }

        // Fallback timeout
        setTimeout(function() {
            overlay.style.display = 'none';
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Settings';
            }
            if (activateBtn) {
                activateBtn.disabled = false;
                activateBtn.textContent = 'Activate License Key';
            }
            
            var errorDiv = document.createElement('div');
            errorDiv.className = 'notice notice-error is-dismissible';
            errorDiv.innerHTML = '<p><strong>Request timed out.</strong> The settings may not have been saved. Please try again.</p>';
            
            var notificationsSection = document.querySelector('.pp-notifications-section');
            if (notificationsSection) {
                notificationsSection.insertBefore(errorDiv, notificationsSection.firstChild);
            } else if (form) {
                form.parentNode.insertBefore(errorDiv, form);
            }
        }, 30000);
    };
    
    if (form && overlay) {
        form.addEventListener('submit', handleSubmit);
    }
});
</script>
