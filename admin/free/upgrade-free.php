<?php
namespace Path_Pilot;

if (!defined('ABSPATH')) exit;

$upgrade_url = Path_Pilot_Admin::UPGRADE_URL;
?>
<div class="pp-home-section pp-margin-bottom">
	<h3 class="pp-section-heading"><i class="icon-pilot-icon" aria-hidden="true"></i> Upgrade to Path Pilot Pro</h3>
	<div><img src="<?php echo esc_url( plugins_url( 'assets/images/pp-hero.webp', dirname( dirname( dirname( __FILE__ ) ) ) . '/path-pilot.php' ) ); ?>" alt="Path Pilot Pro Features" style="width:100%;"></div>
	<p class="pp-stat-description" style="max-width:800px;">
		<strong>More conversions. Smarter recommendations. Faster answers.</strong> Pro adds AI chat, intelligent path suggestions, and conversion analytics so visitors find what they need and take action.
	</p>

	<div class="pp-stats-grid" style="margin:16px auto 24px; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); max-width: 1000px;">
		<div class="pp-home-stat pp-stat-card">
			<div class="pp-home-stat-label">AI Chat Assistant</div>
			<div class="pp-stat-description" style="margin-top:6px; font-size:1.25rem; line-height:1.35; font-weight:600; padding-top:0; border-top:0;">Instant, accurate answers trained on your content.</div>
		</div>
		<div class="pp-home-stat pp-stat-card">
			<div class="pp-home-stat-label">Pro Recommendations</div>
			<div class="pp-stat-description" style="margin-top:6px; font-size:1.25rem; line-height:1.35; font-weight:600; padding-top:0; border-top:0;">Smarter suggestions powered by multi‑hop journey analysis.</div>
		</div><!--
		<div class="pp-home-stat pp-stat-card">
			<div class="pp-home-stat-label">Conversion Analytics</div>
			<div class="pp-stat-description">See journeys, drop‑offs, and what drives conversions.</div>
		</div>-->
		<div class="pp-home-stat pp-stat-card">
			<div class="pp-home-stat-label">Priority Updates & Support</div>
			<div class="pp-stat-description" style="margin-top:6px; font-size:1.25rem; line-height:1.35; font-weight:600; padding-top:0; border-top:0;">New features first and premium support from the team.</div>
		</div>
	</div>

	<div style="margin-top:24px; text-align:center;">
		<a href="<?php echo esc_url($upgrade_url); ?>" target="_blank" class="btn btn-pro" style="background:#d63638; border-color:#d63638; color:#fff; font-size:18px; padding:12px 24px;">Upgrade Now</a>
	</div>
</div>

