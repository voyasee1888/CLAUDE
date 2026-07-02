<?php
/** @var int|null $processed */
/** @var int $total */
/** @var int $never */
/** @var int|false $next_cron */
/** @var array|null $photo_result */
/** @var int $missing_photos */
/** @var array|null $boundary_result */
/** @var int $with_boundary */
/** @var int $boundary_unattempted */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap vni-wrap">
	<h1><?php esc_html_e( 'Sync Data', 'voyasee-ni' ); ?></h1>

	<h2><?php esc_html_e( 'Point-of-interest scores (OpenStreetMap)', 'voyasee-ni' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'This refreshes walkability, nightlife, and transit-density scores from the OpenStreetMap Overpass API. It runs automatically once a day in small batches and never blocks a visitor request. Use the button below only to test the connection or to force an immediate batch.', 'voyasee-ni' ); ?>
	</p>

	<?php if ( null !== $processed ) : ?>
		<div class="notice notice-success">
			<p><?php printf( esc_html__( 'Processed %d neighborhoods in this batch.', 'voyasee-ni' ), (int) $processed ); ?></p>
		</div>
	<?php endif; ?>

	<table class="widefat" style="max-width:600px">
		<tbody>
			<tr>
				<td><?php esc_html_e( 'Total neighborhoods', 'voyasee-ni' ); ?></td>
				<td><strong><?php echo esc_html( $total ); ?></strong></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Never synced', 'voyasee-ni' ); ?></td>
				<td><strong><?php echo esc_html( $never ); ?></strong></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Next scheduled run', 'voyasee-ni' ); ?></td>
				<td><strong><?php echo $next_cron ? esc_html( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $next_cron ), 'M j, Y H:i' ) ) : esc_html__( 'Not scheduled', 'voyasee-ni' ); ?></strong></td>
			</tr>
		</tbody>
	</table>

	<form method="post" style="margin-top:20px">
		<?php wp_nonce_field( 'vni_sync_now', 'vni_sync_nonce' ); ?>
		<?php submit_button( __( 'Sync next batch now (25 neighborhoods)', 'voyasee-ni' ) ); ?>
	</form>

	<p class="description">
		<?php esc_html_e( 'Attribution reminder: OpenStreetMap data is ODbL-licensed. Commercial use is fine, but the Where to Stay Matcher\'s footer must credit OpenStreetMap wherever this data is shown to visitors.', 'voyasee-ni' ); ?>
	</p>

	<hr style="margin:32px 0" />

	<h2><?php esc_html_e( 'Real neighborhood photos (Pexels)', 'voyasee-ni' ); ?></h2>

	<?php if ( empty( WTSM_Settings::get( 'pexels_api_key', '' ) ) ) : ?>
		<div class="notice notice-warning inline">
			<p>
				<?php
				printf(
					/* translators: %s: link to settings page */
					esc_html__( 'No Pexels API key configured yet. Add one (free, instant) on the %s page first.', 'voyasee-ni' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=wtsm-settings' ) ) . '">' . esc_html__( 'Settings & Footer', 'voyasee-ni' ) . '</a>'
				);
				?>
			</p>
		</div>
	<?php else : ?>
		<p class="description">
			<?php esc_html_e( 'Fetches one real photo per neighborhood from Pexels for neighborhoods that don\'t have one yet. Runs automatically once a day in small batches (never on a live visitor request), and can also be triggered manually below. The photographer is credited automatically wherever a photo appears.', 'voyasee-ni' ); ?>
		</p>

		<?php if ( null !== $photo_result ) : ?>
			<div class="notice <?php echo empty( $photo_result['errors'] ) ? 'notice-success' : 'notice-warning'; ?>">
				<p>
					<?php
					printf(
						/* translators: 1: processed count, 2: skipped count */
						esc_html__( 'Fetched %1$d photos. Skipped: %2$d (no good match found, or rate limit reached -- they\'ll retry on the next batch).', 'voyasee-ni' ),
						(int) $photo_result['processed'],
						(int) $photo_result['skipped']
					);
					?>
				</p>
				<?php foreach ( $photo_result['errors'] as $err ) : ?>
					<p><?php echo esc_html( $err ); ?></p>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<table class="widefat" style="max-width:600px">
			<tbody>
				<tr>
					<td><?php esc_html_e( 'Neighborhoods without a photo yet', 'voyasee-ni' ); ?></td>
					<td><strong><?php echo esc_html( $missing_photos ); ?></strong></td>
				</tr>
			</tbody>
		</table>

		<form method="post" style="margin-top:20px">
			<?php wp_nonce_field( 'wtsm_photo_sync_now', 'wtsm_photo_sync_nonce' ); ?>
			<?php submit_button( __( 'Fetch neighborhood photos now (20 neighborhoods)', 'voyasee-ni' ) ); ?>
		</form>
	<?php endif; ?>

	<hr style="margin:32px 0" />

	<h2><?php esc_html_e( 'Real neighborhood boundaries (OpenStreetMap)', 'voyasee-ni' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Looks up each neighborhood\'s real boundary shape on OpenStreetMap so the overview map can draw its actual outline instead of a plain dot. Coverage varies a lot by city -- very complete in many US/European cities, patchy elsewhere -- so this is a best-effort lookup, not a guarantee. Neighborhoods without a match simply keep the existing dot marker; nothing breaks either way. Runs automatically once a day in small batches.', 'voyasee-ni' ); ?>
	</p>

	<?php if ( null !== $boundary_result ) : ?>
		<div class="notice notice-success">
			<p>
				<?php
				printf(
					/* translators: 1: found count, 2: not-found count */
					esc_html__( 'Found boundaries for %1$d neighborhoods. No match for %2$d (they\'ll keep the dot marker).', 'voyasee-ni' ),
					(int) $boundary_result['found'],
					(int) $boundary_result['not_found']
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<table class="widefat" style="max-width:600px">
		<tbody>
			<tr>
				<td><?php esc_html_e( 'Neighborhoods with a real boundary shape', 'voyasee-ni' ); ?></td>
				<td><strong><?php echo esc_html( $with_boundary ); ?></strong></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Not yet attempted', 'voyasee-ni' ); ?></td>
				<td><strong><?php echo esc_html( $boundary_unattempted ); ?></strong></td>
			</tr>
		</tbody>
	</table>

	<form method="post" style="margin-top:20px">
		<?php wp_nonce_field( 'wtsm_boundary_sync_now', 'wtsm_boundary_sync_nonce' ); ?>
		<?php submit_button( __( 'Look up next batch now (15 neighborhoods)', 'voyasee-ni' ) ); ?>
	</form>
</div>

