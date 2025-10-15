<?php
namespace Path_Pilot;

if (!defined('ABSPATH')) exit;

/**
 * Handles the rendering of the Path Analysis page.
 */
class Path_Pilot_Path_Analysis {

    /**
     * Renders the root element for the React app.
     */
    public function render_page_content() {
        echo '<div id="path-pilot-path-analysis-root" class="path-pilot-frontend"></div>';
    }
}
