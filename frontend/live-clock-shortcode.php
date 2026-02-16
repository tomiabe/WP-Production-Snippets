<?php
/**
 * Live Clock (Shortcode + Optional Footer Display)
 *
 * Shortcode: [live_clock]
 *
 * Displays a real-time clock showing either:
 * - The site’s configured timezone, or
 * - The visitor’s local timezone.
 *
 * Features:
 * - Shortcode support
 * - Optional automatic footer display
 * - Lightweight inline CSS & JS
 *
 * Tested up to: WordPress 6.x
 * Scope: Frontend only
 *
 * Usage:
 * 1. Copy into your theme's functions.php, OR
 * 2. Add via a functionality plugin / Code Snippets.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Quick settings for the live clock.
 *
 * mode:
 *   - 'site'    → show site timezone
 *   - 'visitor' → show visitor's device timezone
 *
 * site_timezone:
 *   - Any valid PHP timezone string (e.g. 'Africa/Lagos', 'Europe/London')
 *
 * site_label:
 *   - Text to display as the location label when mode = 'site'
 *
 * show_in_footer:
 *   - true  → automatically output in wp_footer
 *   - false → only via shortcode
 */
function wpps_clock_settings() {
	return array(
		'mode'           => 'site',          // 'site' or 'visitor'
		'site_timezone'  => 'Africa/Lagos', // e.g. Africa/Lagos, Europe/London
		'site_label'     => 'Lagos, NG',
		'show_in_footer' => false,
	);
}

/**
 * Generate the HTML markup for the live clock.
 *
 * @return string
 */
function wpps_live_clock_markup() {
	$s = wpps_clock_settings();

	$mode = ( isset( $s['mode'] ) && $s['mode'] === 'visitor' ) ? 'visitor' : 'site';
	$tz   = ! empty( $s['site_timezone'] ) ? $s['site_timezone'] : wp_timezone_string();
	$lbl  = ! empty( $s['site_label'] ) ? $s['site_label'] : 'My location';

	ob_start();
	?>
	<div class="wpps-live-clock-wrap">
		<div class="wpps-live-clock"
			data-mode="<?php echo esc_attr( $mode ); ?>"
			data-timezone="<?php echo esc_attr( $tz ); ?>"
			data-label="<?php echo esc_attr( $lbl ); ?>">
			<span class="wpps-live-clock-time">--:--:--</span>
			<span class="wpps-live-clock-sep">•</span>
			<span class="wpps-live-clock-location">Loading...</span>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Shortcode handler: [live_clock]
 */
add_shortcode(
	'live_clock',
	function () {
		return wpps_live_clock_markup();
	}
);

/**
 * Optional: auto-output the clock in the site footer.
 */
add_action(
	'wp_footer',
	function () {
		$s = wpps_clock_settings();

		if ( ! empty( $s['show_in_footer'] ) ) {
			echo wpps_live_clock_markup();
		}
	},
	20
);

/**
 * Inline styles + script for the live clock.
 *
 * For production, you could move this to proper enqueued assets,
 * but for a snippet this keeps it self‑contained.
 */
add_action(
	'wp_footer',
	function () {
		?>
		<style>
		.wpps-live-clock-wrap {
			margin: 0;
			padding: 0;
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 0.4rem;
		}

		.wpps-live-clock {
			display: inline-flex;
			align-items: baseline;
			gap: 4px; /* reduced space */
			flex-wrap: nowrap;
			white-space: nowrap;
			font-size: inherit;
			line-height: inherit;
			opacity: .9;
			margin: 0;
		}

		.wpps-live-clock-time,
		.wpps-live-clock-sep,
		.wpps-live-clock-location {
			display: inline !important;
			margin: 0 !important;
			padding: 0 !important;
			font-weight: 400 !important; /* remove bold */
			line-height: inherit !important;
			vertical-align: baseline !important;
		}

		.wpps-live-clock-sep {
			opacity: .6;
		}
		</style>

		<script>
		(function () {
			function cityFromTimeZone(tz) {
				if (!tz || tz.indexOf('/') === -1) return tz || 'Your location';
				return tz.split('/').pop().replace(/_/g, ' ');
			}

			function initClock(el) {
				var mode     = el.getAttribute('data-mode') || 'site';
				var timezone = el.getAttribute('data-timezone') || 'UTC';
				var label    = el.getAttribute('data-label') || 'My location';

				var timeEl = el.querySelector('.wpps-live-clock-time');
				var locEl  = el.querySelector('.wpps-live-clock-location');

				var userTZ = Intl.DateTimeFormat().resolvedOptions().timeZone || '';

				function render() {
					var now      = new Date();
					var timeText = '';

					if (mode === 'visitor') {
						timeText = now.toLocaleTimeString([], {
							hour:   '2-digit',
							minute: '2-digit',
							second: '2-digit'
						});
						locEl.textContent = userTZ ? cityFromTimeZone(userTZ) : 'Your local time';
					} else {
						timeText = now.toLocaleTimeString([], {
							hour:   '2-digit',
							minute: '2-digit',
							second: '2-digit',
							timeZone: timezone
						});
						locEl.textContent = label;
					}

					if (timeEl) {
						timeEl.textContent = timeText;
					}
				}

				render();
				setInterval(render, 1000);
			}

			document.querySelectorAll('.wpps-live-clock').forEach(initClock);
		})();
		</script>
		<?php
	},
	99
);
