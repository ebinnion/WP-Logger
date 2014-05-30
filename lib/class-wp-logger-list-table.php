<?php

if ( ! class_exists( 'WP_List_Table' )  ) {
	require_once( trailingslashit( dirname( __FILE__ ) ) . 'class-wp-list-table-copy.php' );
}

class WP_Logger_List_Table extends WP_List_Table {

	function __construct( $items ) {
		global $status, $page;

		$this->items       = $items['entries'];
		$this->total_items = $items['count'];

		parent::__construct(
			array(
				'singular'  => esc_html__( 'Error', 'wp-logger' ),
				'plural'    => esc_html__( 'Errors', 'wp-logger' ),
				'ajax'      => false
			)
		);
	}

	public function prepare_items() {
        $columns  = $this->get_columns();
        $hidden   = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data();

        $this->set_pagination_args(
        	array(
	            'total_items' => $this->total_items,
	            'per_page'    => 20
        	)
        );

        $this->_column_headers = array( $columns, $hidden, $sortable );
        $this->items = $data;
    }

    public function get_columns() {
        $columns = array(
        	'cb'             => '<input type="checkbox" />',
            'error_msg'      => esc_html__( 'Error Message', 'wp-logger' ),
            'error_severity' => esc_html__( 'Severity', 'wp-logger' ),
            'error_plugin'   => esc_html__( 'Plugin', 'wp-logger' ),
            'error_date'     => esc_html__( 'Date', 'wp-logger' ),

        );

        return $columns;
    }

    public function get_hidden_columns() {
    	return array();
    }

    public function get_sortable_columns() {
        return array(
            'error_severity' => array( 'error_severity', false ),
        	'error_plugin'   => array( 'error_plugin', false ),
        	'error_date'     => array( 'error_date', false ),
        );
    }

    private function table_data() {
    	$data = array();

    	if( ! empty( $this->items ) ) {
    		foreach ( $this->items as $item ) {
    			$data[] = array(
    				'id'            => $item->comment_ID,
                    'error_severity'=> $item->user_id,
    				'error_msg'     => $item->comment_content,
    				'error_date'    => $item->comment_date,
    				'error_plugin'  => $item->comment_author,
    			);
    		}
    	}

    	return $data;
    }

    public function column_cb( $item ) {
    	return sprintf( '<input type="checkbox" name="logs[]" value="%s" />', $item['id'] );
    }

    public function column_id( $item ) {
        return $item['id'];
    }

    public function column_error_msg( $item ) {
        return $item['error_msg'];
    }

    public function column_error_date( $item ) {
        return $item['error_date'];
    }

    public function column_error_plugin( $item ) {
        return $item['error_plugin'];
    }

    public function column_error_severity( $item ) {
        return $item['error_severity'];
    }

	function get_bulk_actions() {
		$actions = array(
			'delete'    => esc_html__( 'Delete', 'wp-logger' )
		);
		return $actions;
	}
}
