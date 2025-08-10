<?php
namespace Path_Pilot;

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Get home data from admin class
$admin = new Path_Pilot_Admin();
$admin->render_home_page();
?>
