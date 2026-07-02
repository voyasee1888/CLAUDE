<?php
/** @var array $destinations */
/** @var array $drafts */
/** @var array|null $discovery_result */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap vni-wrap">
	<h1><?php esc_html_e( 'Neighborhood Discovery', 'voyasee-wtsm' ); ?></h1>

	<p class="description">
		<?php esc_html_e( 'Finds candidate neighborhood names and coordinates for a destination from OpenStreetMap (the same place=suburb/neighbourhood/quarter tag the boundary sync uses) and adds them as DRAFT rows below -- nothing here is ever shown to a visitor until you review it, set a real archetype, and publish it, exactly like adding a neighborhood by hand. This is meant to remove the slowest step (finding out what a city\'s neighborhoods are even called), not the editorial work.', 'voyasee-wtsm' ); ?>
	</p>

	<?php if ( null !== $discovery_result ) : ?>
		<?php if ( ! empty( $discovery_result['errors'] ) ) : ?>
			<div class="notice notice-error">
				<?php foreach ( $discovery_result['errors'] as $err ) : ?>
					<p><?php echo esc_html( $err ); ?></p>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<div class="notice notice-success">
				<p>
					<?php
					printf(
						/* translators: 1: found count, 2: created (new draft) count, 3: duplicate/already-existing count */
						esc_html__( 'Found %1$d named areas on OpenStreetMap. Added %2$d as new drafts below. Skipped %3$d (already exist for this destination).', 'voyasee-wtsm' ),
						(int) $discovery_result['found'],
						(int) $discovery_result['created'],
						(int) $discovery_result['duplicate']
					);
					?>
				</p>
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Run discovery for a destination', 'voyasee-wtsm' ); ?></h2>
	<form method="post">
		<?php wp_nonce_field( 'wtsm_discovery_run', 'wtsm_discovery_nonce' ); ?>
		<select name="destination_id" required>
			<option value=""><?php esc_html_e( '-- choose a destination --', 'voyasee-wtsm' ); ?></option>
			<?php foreach ( $destinations as $dest ) : ?>
				<option value="<?php echo esc_attr( $dest['id'] ); ?>"><?php echo esc_html( $dest['name'] . ' (' . $dest['country'] . ')' ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php submit_button( __( 'Discover neighborhoods', 'voyasee-wtsm' ), 'secondary', 'submit', false ); ?>
	</form>

	<hr style="margin:32px 0" />

	<h2>
		<?php
		printf(
			/* translators: %d: number of drafts awaiting review */
			esc_html__( 'Drafts awaiting review (%d)', 'voyasee-wtsm' ),
			count( $drafts )
		);
		?>
	</h2>

	<?php if ( empty( $drafts ) ) : ?>
		<p class="description"><?php esc_html_e( 'No drafts right now. Run discovery for a destination above to find some.', 'voyasee-wtsm' ); ?></p>
	<?php else : ?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'voyasee-wtsm' ); ?></th>
					<th><?php esc_html_e( 'Destination', 'voyasee-wtsm' ); ?></th>
					<th><?php esc_html_e( 'Coordinates', 'voyasee-wtsm' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'voyasee-wtsm' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $drafts as $draft ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $draft['name'] ); ?></strong></td>
						<td><?php echo esc_html( $draft['destination_name'] ?? '' ); ?></td>
						<td><?php echo esc_html( $draft['lat'] . ', ' . $draft['lng'] ); ?></td>
						<td>
							<a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'vni-neighborhoods', 'action' => 'edit', 'id' => $draft['id'] ), admin_url( 'admin.php' ) ) ); ?>">
								<?php esc_html_e( 'Edit', 'voyasee-wtsm' ); ?>
							</a>
							<a class="button button-small button-primary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'wtsm-discovery', 'action' => 'publish', 'id' => $draft['id'] ), admin_url( 'admin.php' ) ), 'wtsm_publish_draft_' . $draft['id'] ) ); ?>">
								<?php esc_html_e( 'Publish', 'voyasee-wtsm' ); ?>
							</a>
							<a class="button button-small" style="color:#a83b3b" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'wtsm-discovery', 'action' => 'discard', 'id' => $draft['id'] ), admin_url( 'admin.php' ) ), 'wtsm_discard_draft_' . $draft['id'] ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Discard this draft permanently?', 'voyasee-wtsm' ) ); ?>');">
								<?php esc_html_e( 'Discard', 'voyasee-wtsm' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description">
			<?php esc_html_e( 'A draft defaults to "Residential / Quiet" and Tier 2 with no editorial text -- click Edit to set the real archetype, price/safety tiers, and write why_fits/why_caution in your own words before publishing. Publish makes it visible to visitors immediately; it does not need to be re-saved first (though editing it first is strongly recommended -- a published draft with no real detail is just as honest-but-thin as any other Tier 2 zone until you improve it).', 'voyasee-wtsm' ); ?>
		</p>
	<?php endif; ?>
</div>
