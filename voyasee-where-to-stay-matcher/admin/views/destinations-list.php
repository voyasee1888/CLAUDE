<?php
/** @var VNI_List_Table_Destinations $table */
/** @var string $notice */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap vni-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Destinations', 'voyasee-ni' ); ?></h1>
	<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'vni-destinations', 'action' => 'new' ), admin_url( 'admin.php' ) ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Add New', 'voyasee-ni' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php if ( $notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
	<?php endif; ?>

	<p class="description">
		<?php esc_html_e( 'This is the destination backbone for the Best Area to Stay Finder (and any future Voyasee tool that needs neighborhood-level data). For a fast start, import your existing Destination Quiz V7 destination list via the Import CSV screen.', 'voyasee-ni' ); ?>
	</p>

	<form method="get">
		<input type="hidden" name="page" value="vni-destinations" />
		<?php $table->search_box( __( 'Search destinations', 'voyasee-ni' ), 'vni-destination-search' ); ?>
	</form>

	<form method="post">
		<?php $table->display(); ?>
	</form>
</div>
