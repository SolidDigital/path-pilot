<?php
namespace Path_Pilot;

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Get the correct logo URL with fallbacks
$logo_url = plugin_dir_url(dirname(__DIR__)) . 'assets/images/path-pilot-logo.png';

?>
<div class="pp-header">
    <div class="pp-header-left">
        <img src="<?php echo esc_url($logo_url); ?>" alt="Path Pilot Logo" class="pp-logo">
        <?php
        if (Path_Pilot::is_pro()) {
            echo '<span class="pp-pro-tag">Pro</span>';
        }
        ?>
    </div>
    <?php
    if (!Path_Pilot::is_pro()) {
        ?>
        <div class="pp-header-right">
            <a href="<?php echo esc_url('https://pathpilot.app/'); ?>" target="_blank" class="btn btn-pro">Upgrade to Pro</a>
        </div>
        <?php
    }
    ?>

</div>
