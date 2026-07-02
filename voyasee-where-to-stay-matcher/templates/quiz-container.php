<?php
/** @var array $atts */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( self::$rendered_once ) {
	// Only one instance can initialize per page (matcher.js only ever
	// binds to the first .vwtsm-root it finds). Visitors just see nothing
	// extra; admins/editors get a visible notice so a duplicated
	// shortcode/block doesn't look like a silent bug during setup.
	if ( current_user_can( 'edit_posts' ) ) {
		echo '<p style="padding:1rem;border:1px dashed #c9a24b;border-radius:8px;color:#c9a24b;font-size:0.85rem;">' .
			esc_html__( 'Voyasee Where to Stay Matcher: only one instance of this tool can be shown per page. This second instance is hidden. (Only visible to editors/admins.)', 'voyasee-wtsm' ) .
			'</p>';
	}
	return;
}
self::$rendered_once = true;

$prefill = sanitize_title( $atts['destination'] ?? '' );

$affiliate_fields = WTSM_Settings::affiliate_fields();
$tool_fields      = WTSM_Settings::tool_fields();
$about_text       = WTSM_Settings::get( 'about_text', __( 'Voyasee helps travelers plan smarter trips with free, practical tools -- from budgeting to packing to figuring out exactly where to stay.', 'voyasee-wtsm' ) );

$tool_groups = array(
	'plan'   => __( 'Plan the Trip', 'voyasee-wtsm' ),
	'safety' => __( 'Safety & Documents', 'voyasee-wtsm' ),
);
?>
<div class="vwtsm-root" data-prefill-destination="<?php echo esc_attr( $prefill ); ?>" data-prefill-name="<?php echo esc_attr( $atts['prefill_name'] ?? '' ); ?>">

	<div class="vwtsm-hero-scene">
		<div class="vwtsm-bg-scene" aria-hidden="true">
			<div class="vwtsm-bg-gradient"></div>
			<div class="vwtsm-globe-grid"></div>
			<div class="vwtsm-stars"></div>

			<svg class="vwtsm-route-network" viewBox="0 0 1000 500" preserveAspectRatio="none">
				<path class="vwtsm-route-arc vwtsm-route-arc-1" d="M -30,420 C 180,180 320,470 560,240" />
				<path class="vwtsm-route-arc vwtsm-route-arc-2" d="M 120,60 C 320,260 520,20 780,200" />
				<path class="vwtsm-route-arc vwtsm-route-arc-3" d="M 420,460 C 600,320 700,440 1030,120" />
				<path class="vwtsm-route-arc vwtsm-route-arc-4" d="M -20,140 C 220,40 380,150 640,40" />

				<circle class="vwtsm-route-node" cx="-30" cy="420" r="3" />
				<circle class="vwtsm-route-node" cx="560" cy="240" r="3" />
				<circle class="vwtsm-route-node" cx="120" cy="60" r="3" />
				<circle class="vwtsm-route-node" cx="780" cy="200" r="3" />
				<circle class="vwtsm-route-node" cx="420" cy="460" r="3" />
				<circle class="vwtsm-route-node" cx="1030" cy="120" r="3" />
				<circle class="vwtsm-route-node" cx="-20" cy="140" r="3" />
				<circle class="vwtsm-route-node" cx="640" cy="40" r="3" />
			</svg>

			<div class="vwtsm-route-pulse vwtsm-pulse-1"></div>
			<div class="vwtsm-route-pulse vwtsm-pulse-2"></div>
			<div class="vwtsm-route-pulse vwtsm-pulse-3"></div>

			<!-- Floating travel-iconography: small glyphs drifting through the
			     scene, echoing the "budget icons / weather symbols / visa
			     hints" language already used across Voyasee's other tools. -->
			<div class="vwtsm-float-icon vwtsm-float-1" aria-hidden="true">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="4" y="7" width="16" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M4 12h16"/></svg>
			</div>
			<div class="vwtsm-float-icon vwtsm-float-2" aria-hidden="true">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M12 21s-7-5.5-7-11a7 7 0 0 1 14 0c0 5.5-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
			</div>
			<div class="vwtsm-float-icon vwtsm-float-3" aria-hidden="true">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="3" y="4" width="18" height="14" rx="2"/><path d="M3 9h18"/><path d="M8 4v3M16 4v3"/></svg>
			</div>

			<div class="vwtsm-compass-emblem">
				<svg viewBox="0 0 100 100" width="100%" height="100%">
					<circle cx="50" cy="50" r="46" fill="none" stroke="currentColor" stroke-width="0.75" />
					<circle cx="50" cy="50" r="38" fill="none" stroke="currentColor" stroke-width="0.5" />
					<path d="M50 8 L54 46 L50 50 L46 46 Z" fill="currentColor" />
					<path d="M50 92 L46 54 L50 50 L54 54 Z" fill="currentColor" opacity="0.5" />
					<path d="M8 50 L46 46 L50 50 L46 54 Z" fill="currentColor" opacity="0.7" />
					<path d="M92 50 L54 54 L50 50 L54 46 Z" fill="currentColor" opacity="0.7" />
				</svg>
			</div>
		</div>

		<div class="vwtsm-hero-header">
			<p class="vwtsm-eyebrow">🧭 <?php esc_html_e( 'Live neighborhood intelligence', 'voyasee-wtsm' ); ?></p>
			<h2 class="vwtsm-hero-title">Voyasee <em>Where to Stay</em> Matcher</h2>
			<p class="vwtsm-hero-tagline"><?php esc_html_e( 'Match your trip to the right neighborhood, not just the right city.', 'voyasee-wtsm' ); ?></p>
			<div class="vwtsm-hero-badges" data-vwtsm-hero-badges>
				<span class="vwtsm-hero-badge" data-vwtsm-badge-destinations><?php esc_html_e( 'Loading coverage…', 'voyasee-wtsm' ); ?></span>
				<span class="vwtsm-hero-badge"><?php esc_html_e( 'Coordinate-based matching', 'voyasee-wtsm' ); ?></span>
				<span class="vwtsm-hero-badge"><?php esc_html_e( 'OpenStreetMap-powered', 'voyasee-wtsm' ); ?></span>
				<span class="vwtsm-hero-badge"><?php esc_html_e( 'Explainable Match Score', 'voyasee-wtsm' ); ?></span>
			</div>
		</div>

		<div class="vwtsm-card vwtsm-quiz" data-vwtsm-step-container>
			<div class="vwtsm-loading-fallback">
				<p><?php esc_html_e( 'Loading the matcher...', 'voyasee-wtsm' ); ?></p>
			</div>
		</div>
	</div>

	<footer class="vwtsm-footer">
		<div class="vwtsm-footer-texture" aria-hidden="true"></div>
		<div class="vwtsm-footer-inner">

			<div class="vwtsm-footer-col vwtsm-footer-about">
				<p class="vwtsm-footer-wordmark">
					<svg class="vwtsm-footer-emblem" viewBox="0 0 100 100" aria-hidden="true"><circle cx="50" cy="50" r="46" fill="none" stroke="currentColor" stroke-width="2"/><path d="M50 12 L56 44 L50 50 L44 44 Z" fill="currentColor"/><path d="M50 88 L44 56 L50 50 L56 56 Z" fill="currentColor" opacity="0.5"/><path d="M12 50 L44 44 L50 50 L44 56 Z" fill="currentColor" opacity="0.7"/><path d="M88 50 L56 56 L50 50 L56 44 Z" fill="currentColor" opacity="0.7"/></svg>
					Voyasee
				</p>
				<p><?php echo esc_html( $about_text ); ?></p>
				<p class="vwtsm-trust-badge" data-vwtsm-coverage-counter><?php esc_html_e( 'Loading coverage stats...', 'voyasee-wtsm' ); ?></p>
			</div>

			<?php
			$group_icons = array(
				'plan'   => '<path d="M3 11l18-8-8 18-2-8-8-2z"/>',
				'safety' => '<path d="M12 3l7 3v5c0 4.5-3 7.7-7 10-4-2.3-7-5.5-7-10V6z"/><path d="M9 12l2 2 4-4"/>',
			);
			foreach ( $tool_groups as $group_key => $group_label ) :
				?>
				<div class="vwtsm-footer-col">
					<h4>
						<svg class="vwtsm-footer-col-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><?php echo $group_icons[ $group_key ] ?? ''; ?></svg>
						<?php echo esc_html( $group_label ); ?>
					</h4>
					<ul class="vwtsm-footer-links">
						<?php
						foreach ( $tool_fields as $key => $field ) :
							if ( $field['group'] !== $group_key ) {
								continue;
							}
							$url = WTSM_Settings::get( $key );
							if ( empty( $url ) ) {
								continue;
							}
							?>
							<li><a href="<?php echo esc_url( $url ); ?>"><span class="vwtsm-footer-link-arrow">&rarr;</span> <?php echo esc_html( $field['label'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>

			<div class="vwtsm-footer-col">
				<h4>
					<svg class="vwtsm-footer-col-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><path d="M8 12a4 4 0 1 1 8 0v3l2 2-2 2v0a4 4 0 0 1-8 0v0l-2-2 2-2z"/><path d="M8 8V6a2 2 0 0 1 2-2h1M16 16v2a2 2 0 0 1-2 2h-1"/></svg>
					<?php esc_html_e( 'Travel Partners', 'voyasee-wtsm' ); ?>
				</h4>
				<ul class="vwtsm-footer-links">
					<?php
					foreach ( $affiliate_fields as $key => $field ) :
						$url = WTSM_Settings::get( $key );
						if ( empty( $url ) ) {
							continue;
						}
						$label = ( 'booking_affiliate_url' === $key ) ? 'Booking.com' : $field['fixed_label'];
						?>
						<li><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="nofollow sponsored noopener"><span class="vwtsm-footer-link-arrow">&rarr;</span> <?php echo esc_html( $label ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="vwtsm-footer-col vwtsm-footer-legal">
				<h4>
					<svg class="vwtsm-footer-col-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 16v-5M12 8h.01"/></svg>
					<?php esc_html_e( 'Good to know', 'voyasee-wtsm' ); ?>
				</h4>
				<p>
					<?php esc_html_e( 'Neighborhood data is general guidance built from curated research and OpenStreetMap point-of-interest density -- not a guarantee. Use normal travel precautions and check current local conditions before booking.', 'voyasee-wtsm' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'Map data', 'voyasee-wtsm' ); ?> © <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a> <?php esc_html_e( 'contributors, ODbL. Basemap style by', 'voyasee-wtsm' ); ?> <a href="https://carto.com/attributions" target="_blank" rel="noopener noreferrer">CARTO</a>.
				</p>
				<p class="vwtsm-affiliate-disclosure">
					<?php esc_html_e( 'Some links on this page are affiliate links. If you book through them, Voyasee may earn a small commission at no extra cost to you.', 'voyasee-wtsm' ); ?>
				</p>
			</div>

		</div>

		<div class="vwtsm-footer-bottom">
			<span>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> Voyasee</span>
		</div>
	</footer>
</div>
