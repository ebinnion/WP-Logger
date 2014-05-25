<?php

if ( ! class_exists( 'WP_List_Table' )  ) {
	require_once( trailingslashit( dirname( __FILE__ ) ) . 'class-wp-list-table-copy.php' );
}

class WP_Logger_List_Table extends WP_List_Table {

	function __construct( $items ) {
		global $status, $page;

		$this->items = $items['entries'];
		$this->total_items = $items['count'];

		parent::__construct( 
			array(
				'singular'  => esc_html__( 'book', 'mylisttable' ),     //singular name of the listed records
				'plural'    => esc_html__( 'books', 'mylisttable' ),   //plural name of the listed records
				'ajax'      => false        //does this table support ajax?
			)
		);
	}

	public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data();

        $this->set_pagination_args( 
        	array(
	            'total_items' => $this->total_items,                  //WE have to calculate the total number of items
	            'per_page'    => 20                     //WE have to determine how many items to show on a page
        	) 
        );

        $this->_column_headers = array( $columns, $hidden, $sortable );
        $this->items = $data;
    }

    public function get_columns() {
        $columns = array(
        	'cb'           => '<input type="checkbox" />',
            'error_msg'    => 'Error Message',
            'error_plugin' => 'Plugin',
            'error_date'   => 'Date',
           
        );

        return $columns;
    }

    public function get_hidden_columns() {
    	return array();
    }

    public function get_sortable_columns() {
        return array(
        	'error_plugin' => array( 'error_plugin', false ),
        	'error_date'   => array( 'error_date', false )
        );
    }

    private function table_data() {
    	$data = array();

    	if( ! empty( $this->items ) ) {
    		foreach ( $this->items as $item ) {
    			$data[] = array(
    				'id'           => $item->comment_ID,
    				'error_msg'    => $item->comment_content,
    				'error_date'   => $item->comment_date,
    				'error_plugin' => str_replace( 'wp-logger-', '', get_comment_meta( $item->comment_ID, '_wp_logger_term', true ) )
    			);
    		}
    	}

    	return $data;
    }

    public function column_cb( $item ) {
    	return sprintf( '<input type="checkbox" name="book[]" value="%s" />', $item['id'] );
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
}
