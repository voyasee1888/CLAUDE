<?php
/**
 * Destinations list table (standard WP admin list table UX: search,
 * pagination, row actions).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class VNI_List_Table_Destinations extends WP_List_Table {

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'destination',
				'plural'   => 'destinations',
				'ajax'     => false,
			)
		);
	}

	public function get_columns() {
		return array(
			'name'             => __( 'Destination', 'voyasee-ni' ),
			'country'          => __( 'Country', 'voyasee-ni' ),
			'tier'             => __( 'Tier', 'voyasee-ni' ),
			'neighborhoods'    => __( 'Neighborhoods', 'voyasee-ni' ),
			'source_dataset'   => __( 'Source dataset', 'voyasee-ni' ),
		);
	}

	public function get_sortable_columns() {
		return array(
			'name'    => array( 'name', false ),
			'country' => array( 'country', false ),
			'tier'    => array( 'tier', false ),
		);
	}

	public function prepare_items() {
		$per_page = 20;
		$page     = $this->get_pagenum();
		$search   = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$total = VNI_Data::count_destinations( array( 'search' => $search ) );

		$this->items = VNI_Data::list_destinations(
			array(
				'search'   => $search,
				'per_page' => $per_page,
				'page'     => $page,
			)
		);

		$this->set_pagination_args(
			array(
				'total_items' => $total,
				'per_page'    => $per_page,
			)
		);
	}

	public function column_name( $item ) {
		$edit_url   = add_query_arg(
			array( 'page' => 'vni-destinations', 'action' => 'edit', 'id' => $item['id'] ),
			admin_url( 'admin.php' )
		);
		$delete_url = wp_nonce_url(
			add_query_arg(
				array( 'page' => 'vni-destinations', 'action' => 'delete', 'id' => $item['id'] ),
				admin_url( 'admin.php' )
			),
			'vni_delete_destination_' . $item['id']
		);

		$actions = array(
			'edit'   => sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), esc_html__( 'Edit', 'voyasee-ni' ) ),
			'delete' => sprintf(
				'<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
				esc_url( $delete_url ),
				esc_js( __( 'Delete this destination and all of its neighborhoods?', 'voyasee-ni' ) ),
				esc_html__( 'Delete', 'voyasee-ni' )
			),
		);

		return sprintf(
			'<strong><a href="%s">%s</a></strong> %s',
			esc_url( $edit_url ),
			esc_html( $item['name'] ),
			$this->row_actions( $actions )
		);
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'tier':
				return esc_html( 'Tier ' . $item['tier'] );
			case 'neighborhoods':
				$count = VNI_Data::count_neighborhoods( array( 'destination_id' => $item['id'] ) );
				$url   = add_query_arg(
					array( 'page' => 'vni-neighborhoods', 'destination_id' => $item['id'] ),
					admin_url( 'admin.php' )
				);
				return sprintf( '<a href="%s">%d</a>', esc_url( $url ), $count );
			case 'country':
			case 'source_dataset':
				return esc_html( $item[ $column_name ] ?? '' );
			default:
				return '';
		}
	}
}
