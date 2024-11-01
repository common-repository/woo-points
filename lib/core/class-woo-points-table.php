<?php

// WP_List_Table is not loaded automatically so we need to load it in our application
if ( !class_exists( 'WP_List_Table' ) ) {
	require_once (ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class WooPoints_List_Table extends WP_List_Table {

	/**
	 * Class constructor
	 *
	 * @param array $args  additional arguments/overrides
	 *
	 * @see WP_List_Table::__construct()
	 */
	public function __construct( $args = array() ) {
		$args = wp_parse_args( $args, array(
				'singular' => 'point',
				'plural'   => 'points',
				'ajax'     => true,
				'screen'   => get_current_screen(),
		) );

		parent::__construct( $args );
	}

	/**
	 * Prepare the items for the table to process
	 *
	 * @return Void
	 */
	public function prepare_items() {
		$columns = $this->get_columns();
		$hidden = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();
		
		$data = $this->table_data();
		usort( $data, array(
				&$this,
				'sort_data' 
		) );

		$perPage = 20;
		$currentPage = $this->get_pagenum();
		$totalItems = count( $data );
		
		$this->set_pagination_args( array(
				'total_items' => $totalItems,
				'per_page' => $perPage 
		) );

		$data = array_slice( $data, (($currentPage - 1) * $perPage), $perPage );

		$this->_column_headers = array(
				$columns,
				$hidden,
				$sortable
		);
		$this->items = $data;

		$this->process_actions();

	}

	/**
	 * Override the parent columns method.
	 * Defines the columns to use in your listing table
	 *
	 * @return Array
	 */
	public function get_columns() {
		$columns = array(
				'cb'          => '<input type="checkbox" />',
				'point_id'    => 'ID',
				'user_id'     => 'User ID',
				'points'      => 'Points',
				'description' => 'Description',
				'datetime'    => 'Date & Time',
				'status'      => 'Status',
				'actions'     => 'Actions'
		);

		return $columns;
	}

	/**
	 * Define which columns are hidden
	 *
	 * @return Array
	 */
	public function get_hidden_columns() {
		return array();
	}

	/**
	 * Define the sortable columns
	 *
	 * @return Array
	 */
	public function get_sortable_columns() {
		return array(
				'point_id' => array(
						'point_id',
						false 
				),
				'user_id' => array(
						'user_id',
						false
				),
				'points' => array(
						'points',
						false
				),
				'description' => array(
						'description',
						false
				),
				'datetime' => array(
						'datetime',
						false
				),
				'status' => array(
						'status',
						false
				)
		);
	}

	/**
	 * Get the table data
	 *
	 * @return Array
	 */
	private function table_data() {
		$data = array();

		$data = WooPoints::get_points( null, null, null, ARRAY_A );

		return $data;
	}

	/**
	 * Define what data to show on each column of the table
	 *
	 * @param Array $item
	 *        	Data
	 * @param String $column_name
	 *        	- Current column name
	 *        	
	 * @return Mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'point_id' :
			case 'user_id' :
			case 'description' :
			case 'points' :
			case 'datetime' :
			case 'status' :
				return $item[$column_name];
				break;
			case 'actions':
				$actions = array(
						'edit'      => sprintf('<a href="?page=%s&action=%s&point_id=%s">Edit</a>',$_REQUEST['page'],'edit',$item['point_id']),
						'delete'    => sprintf('<a href="?page=%s&action=%s&point_id=%s">Delete</a>',$_REQUEST['page'],'delete',$item['point_id']),
				);

				//Return the title contents
				return sprintf('%1$s%2$s',
						isset( $item[$column_name] ) ? $item[$column_name]:'',
						$this->row_actions($actions, true)
				);
				break;
			default :
				return print_r( $item, true );
		}
	}

	/**
	 * Allows you to sort the data by the variables set in the $_GET
	 *
	 * @return Mixed
	 */
	private function sort_data( $a, $b ) {
		// Set defaults
		$orderby = 'point_id';
		$order = 'desc';

		// If orderby is set, use this as the sort column
		if ( !empty( $_GET['orderby'] ) ) {
			$orderby = sanitize_text_field( $_GET['orderby'] );
		}

		// If order is set use this as the order
		if ( !empty( $_GET['order'] ) ) {
			$order = sanitize_text_field( $_GET['order'] );
		}

		$result = strnatcmp( $a[$orderby], $b[$orderby] );

		if ( $order === 'asc' ) {
			return $result;
		}

		return -$result;
	}

	function show_table(){
	
		$table = new WooPoints_List_Table();
		$table->prepare_items();

		echo '<form method="post">';
		$this->nonce();
		$table->display();
		echo '</form>';
	}

	/**
	 * Display the checkbox.
	 * All other columns are using the common column_default() as we don't
	 * define specific methods for each here.
	 *
	 * @param array $item row data
	 * @return string
	 */
	function column_cb( $item ){
		return sprintf( '<input type="checkbox" name="_point_id[]" value="%d" />', $item['point_id'] );
	}

	/**
	 * Defines bulk actions available.
	 *
	 * @see WP_List_Table::get_bulk_actions()
	 */
	function get_bulk_actions() {
		$actions = array(
			'pay' => __( 'Mark as Paid', 'woocommerce-points' )
		);
		return $actions;
	}

	/**
	 * Handler for the different bulk actions
	 */
	public function process_actions() {
		switch ( $this->current_action() ) {
			case 'pay':
				// mark the points as paid
				if ( !empty( $_REQUEST['_point_id'] ) ) {
					$point_ids = $_REQUEST['_point_id'];
					if ( is_array( $point_ids ) ) {
						foreach ( $point_ids as $point_id ) {
							WooPoints::update_points( $point_id, array( 'status' => WOO_POINTS_STATUS_PAID ) );
						}
					}
				}
				break;
		}
	}

}
