<?php
/**
 * Neighborhoods list table. Can be filtered to a single destination via
 * ?destination_id=.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class VNI_List_Table_Neighborhoods extends WP_List_Table {

	private $destination_id = 0;

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'neighborhood',
				'plural'   => 'neighborhoods',
				'ajax'     => false,
			)
		);

		if ( isset( $_REQUEST['destination_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->destination_id = absint( $_REQUEST['destination_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
	}

	public function get_columns() {
		return array(
			'name'        => __( 'Neighborhood', 'voyasee-ni' ),
			'destination' => __( 'Destination', 'voyasee-ni' ),
			'archetype'   => __( 'Archetype', 'voyasee-ni' ),
			'price_band'  => __( 'Price band', 'voyasee-ni' ),
			'walkability' => __( 'Walkability', 'voyasee-ni' ),
			'nightlife'   => __( 'Nightlife', 'voyasee-ni' ),
			'tier'        => __( 'Data tier', 'voyasee-ni' ),
			'synced'      => __( 'POI synced', 'voyasee-ni' ),
		);
	}

	public function prepare_items() {
		$per_page = 25;
		$page     = $this->get_pagenum();
		$search   = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$this->_column_headers = array( $this->get_columns(), array(), array() );

		$args = array(
			'destination_id' => $this->destination_id,
			'search'         => $search,
			'per_page'       => $per_page,
			'page'           => $page,
		);

		$total       = VNI_Data::count_neighborhoods( $args );
		$this->items = VNI_Data::list_neighborhoods( $args );

		$this->set_pagination_args(
			array(
				'total_items' => $total,
				'per_page'    => $per_page,
			)
		);
	}

	public function column_name( $item ) {
		$edit_url   = add_query_arg(
			array( 'page' => 'vni-neighborhoods', 'action' => 'edit', 'id' => $item['id'] ),
			admin_url( 'admin.php' )
		);
		$delete_url = wp_nonce_url(
			add_query_arg(
				array( 'page' => 'vni-neighborhoods', 'action' => 'delete', 'id' => $item['id'] ),
				admin_url( 'admin.php' )
			),
			'vni_delete_neighborhood_' . $item['id']
		);

		$actions = array(
			'edit'   => sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), esc_html__( 'Edit', 'voyasee-ni' ) ),
			'delete' => sprintf(
				'<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
				esc_url( $delete_url ),
				esc_js( __( 'Delete this neighborhood?', 'voyasee-ni' ) ),
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
		$labels = VNI_Data::archetype_labels();

		switch ( $column_name ) {
			case 'destination':
				return esc_html( $item['destination_name'] ?? '' );
			case 'archetype':
				return esc_html( $labels[ $item['archetype'] ] ?? $item['archetype'] );
			case 'price_band':
				return esc_html( str_repeat( '$', (int) $item['price_band'] ) );
			case 'walkability':
				return esc_html( $item['walkability_score'] . ' / 100' );
			case 'nightlife':
				return esc_html( $item['nightlife_score'] . ' / 100' );
			case 'tier':
				return esc_html( 'Tier ' . $item['data_tier'] );
			case 'synced':
				return $item['poi_last_synced'] ? esc_html( mysql2date( 'M j, Y', $item['poi_last_synced'] ) ) : esc_html__( 'Never', 'voyasee-ni' );
			default:
				return '';
		}
	}
}
