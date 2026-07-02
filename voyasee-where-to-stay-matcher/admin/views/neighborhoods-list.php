<?php
/** @var VNI_List_Table_Neighborhoods $table */
/** @var string $notice */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap vni-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Neighborhoods', 'voyasee-ni' ); ?></h1>
	<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'vni-neighborhoods', 'action' => 'new' ), admin_url( 'admin.php' ) ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Add New', 'voyasee-ni' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php if ( $notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
	<?php endif; ?>

	<form method="get">
		<input type="hidden" name="page" value="vni-neighborhoods" />
		<?php if ( isset( $_GET['destination_id'] ) ) : ?>
			<input type="hidden" name="destination_id" value="<?php echo esc_attr( absint( $_GET['destination_id'] ) ); ?>" />
		<?php endif; ?>
		<?php $table->search_box( __( 'Search neighborhoods', 'voyasee-ni' ), 'vni-neighborhood-search' ); ?>
	</form>

	<form method="post">
		<?php $table->display(); ?>
	</form>
</div>
