<?php

if ( ! class_exists( 'WP_List_Table' )  ) {
	require_once( trailingslashit( dirname( __FILE__ ) ) . 'class-wp-list-table-copy.php' );
}

/**
 * Extends the WP_List_Table and builds the UI for WP Logger
 */
class WP_Logger_List_Table extends WP_List_Table {

	/**
	 * Runs when WP_Logger_List_Table class is instantiated.
	 *
	 * @param array $args {
	 *     @object $entries Object that represents a row from the comments table.
	 *     @int $count The total number of objects.
	 * }
	 */
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

		$this->custom_query_string = '';

		if( ! empty( $_POST['search'] ) && ! isset( $_GET['search'] ) ) {
			$this->custom_query_string .= "&search={$_POST['search']}";
		}

		if( ! empty( $_POST['plugin-select'] ) && ! isset( $_GET['plugin-select'] ) ) {
			$this->custom_query_string .= "&plugin-select={$_POST['plugin-select']}";
		}

		if( ! empty( $_POST['log-select'] ) && ! isset( $_GET['log-select'] ) ) {
			$this->custom_query_string .= "&log-select={$_POST['log-select']}";
		}
	}

	/**
	 * Initializes member data.
	 */
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

	/**
	 * Sets column ids and labels.
	 *
	 * @return array $args {
	 *     @string $cb Adds a checkbox to the table that is used for bulk actions.
	 *     @string $error_msg The label for the Error column.
	 *     @string $error_severity The label for the Severity column.
	 *     @string $error_plugin The label for the plugin name column.
	 *     @string $error_date The label for the date the log entry was created.
	 * }
	 */
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

	/**
	 * Returns array of hidden columns.
	 * @return array Empty.
	 */
	public function get_hidden_columns() {
		return array();
	}

	/**
	 * Sets the sortable columns for the WP Logger plugin.
	 *
	 * @return array $args {
	 *     @string $error_severity Allows the severity column to be sorted.
	 *     @string $error_plugin Allows the plugin name column to be sorted.
	 *     @string $error_date Allows the date column to be sorted.
	 * }
	 */
	public function get_sortable_columns() {
		return array(
			'error_severity' => array( 'error_severity', false ),
			'error_plugin'   => array( 'error_plugin', false ),
			'error_date'     => array( 'error_date', false ),
		);
	}

	/**
	 * Returns an array of data, where each row is an associatve array that represents the data for that row.
	 *
	 * @return  @array Arrays of associative arrays.
	 */
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

	/**
	 * Overrides WP_List_Table pagination method in order to modify the pagination links.
	 *
	 * @see WP_List_Table.
	 */
	function pagination( $which ) {
		if ( empty( $this->_pagination_args ) )
			return;

		extract( $this->_pagination_args, EXTR_SKIP );

		$output = '<span class="displaying-num">' . sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

		$current = $this->get_pagenum();

		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

		$current_url = remove_query_arg( array( 'hotkeys_highlight_last', 'hotkeys_highlight_first' ), $current_url );

		$page_links = array();

		$disable_first = $disable_last = '';
		if ( $current == 1 )
			$disable_first = ' disabled';
		if ( $current == $total_pages )
			$disable_last = ' disabled';

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'first-page' . $disable_first,
			esc_attr__( 'Go to the first page' ),
			esc_url( remove_query_arg( 'paged', $current_url ) . $this->custom_query_string ),
			'&laquo;'
		);

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'prev-page' . $disable_first,
			esc_attr__( 'Go to the previous page' ),
			esc_url( add_query_arg( 'paged', max( 1, $current-1 ), $current_url ) . $this->custom_query_string ),
			'&lsaquo;'
		);

		if ( 'bottom' == $which )
			$html_current_page = $current;
		else
			$html_current_page = sprintf( "<input class='current-page' title='%s' type='text' name='paged' value='%s' size='%d' />",
				esc_attr__( 'Current page' ),
				$current,
				strlen( $total_pages )
			);

		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
		$page_links[] = '<span class="paging-input">' . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . '</span>';

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'next-page' . $disable_last,
			esc_attr__( 'Go to the next page' ),
			esc_url( add_query_arg( 'paged', min( $total_pages, $current+1 ), $current_url ) . $this->custom_query_string ),
			'&rsaquo;'
		);

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'last-page' . $disable_last,
			esc_attr__( 'Go to the last page' ),
			esc_url( add_query_arg( 'paged', $total_pages, $current_url ) . $this->custom_query_string ),
			'&raquo;'
		);

		$pagination_links_class = 'pagination-links';
		if ( ! empty( $infinite_scroll ) )
			$pagination_links_class = ' hide-if-js';
		$output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';

		if ( $total_pages )
			$page_class = $total_pages < 2 ? ' one-page' : '';
		else
			$page_class = ' no-pages';

		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

		echo $this->_pagination;
	}

	/**
	 * Overrides the WP_List_Table print_column_headers method in order to modify the orderby links.
	 *
	 * @see WP_List_Table
	 */
	function print_column_headers( $with_id = true ) {
		list( $columns, $hidden, $sortable ) = $this->get_column_info();

		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
		$current_url = remove_query_arg( 'paged', $current_url );

		if ( isset( $_GET['orderby'] ) )
			$current_orderby = $_GET['orderby'];
		else
			$current_orderby = '';

		if ( isset( $_GET['order'] ) && 'desc' == $_GET['order'] )
			$current_order = 'desc';
		else
			$current_order = 'asc';

		if ( ! empty( $columns['cb'] ) ) {
			static $cb_counter = 1;
			$columns['cb'] = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . __( 'Select All' ) . '</label>'
				. '<input id="cb-select-all-' . $cb_counter . '" type="checkbox" />';
			$cb_counter++;
		}

		foreach ( $columns as $column_key => $column_display_name ) {
			$class = array( 'manage-column', "column-$column_key" );

			$style = '';
			if ( in_array( $column_key, $hidden ) )
				$style = 'display:none;';

			$style = ' style="' . $style . '"';

			if ( 'cb' == $column_key )
				$class[] = 'check-column';
			elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ) ) )
				$class[] = 'num';

			if ( isset( $sortable[$column_key] ) ) {
				list( $orderby, $desc_first ) = $sortable[$column_key];

				if ( $current_orderby == $orderby ) {
					$order = 'asc' == $current_order ? 'desc' : 'asc';
					$class[] = 'sorted';
					$class[] = $current_order;
				} else {
					$order = $desc_first ? 'desc' : 'asc';
					$class[] = 'sortable';
					$class[] = $desc_first ? 'asc' : 'desc';
				}

				$column_display_name = '<a href="' . esc_url( add_query_arg( compact( 'orderby', 'order' ), $current_url ) . $this->custom_query_string ) . '"><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
			}

			$id = $with_id ? "id='$column_key'" : '';

			if ( !empty( $class ) )
				$class = "class='" . join( ' ', $class ) . "'";

			echo "<th scope='col' $id $class $style>$column_display_name</th>";
		}
	}

	/**
	 * Returns the value for the cb column.
	 *
	 * @param array $args {
	 *     @int $id The ID for the current comment.
	 *     @string $error_msg The value for the Error column.
	 *     @string $error_severity The value for the Severity column.
	 *     @string $error_plugin The value for the plugin name column.
	 *     @string $error_date The value for the date the log entry was created.
	 * }
	 *
	 * @return @string Checkbox for bulk edit functionality.
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="logs[]" value="%s" />', $item['id'] );
	}

	/**
	 * Returns the value for the id column.
	 *
	 * @param array $args {
	 *     @int $id The ID for the current comment.
	 *     @string $error_msg The value for the Error column.
	 *     @string $error_severity The value for the Severity column.
	 *     @string $error_plugin The value for the plugin name column.
	 *     @string $error_date The value for the date the log entry was created.
	 * }
	 *
	 * @return @int The ID for the current comment.
	 */
	public function column_id( $item ) {
		return $item['id'];
	}

	/**
	 * Returns the value for the error_msg column.
	 *
	 * @param array $args {
	 *     @int $id The ID for the current comment.
	 *     @string $error_msg The value for the Error column.
	 *     @string $error_severity The value for the Severity column.
	 *     @string $error_plugin The value for the plugin name column.
	 *     @string $error_date The value for the date the log entry was created.
	 * }
	 *
	 * @return @string The error message for the current comment.
	 */
	public function column_error_msg( $item ) {
		return $item['error_msg'];
	}

	/**
	 * Returns the value for the error_date column.
	 *
	 * @param array $args {
	 *     @int $id The ID for the current comment.
	 *     @string $error_msg The value for the Error column.
	 *     @string $error_severity The value for the Severity column.
	 *     @string $error_plugin The value for the plugin name column.
	 *     @string $error_date The value for the date the log entry was created.
	 * }
	 *
	 * @return @string Date the current entry was added.
	 */
	public function column_error_date( $item ) {
		return $item['error_date'];
	}

	/**
	 * Returns the value for the error_plugin column.
	 *
	 * @param array $args {
	 *     @int $id The ID for the current comment.
	 *     @string $error_msg The value for the Error column.
	 *     @string $error_severity The value for the Severity column.
	 *     @string $error_plugin The value for the plugin name column.
	 *     @string $error_date The value for the date the log entry was created.
	 * }
	 *
	 * @return @string The plugin which the current entry is assigned to.
	 */
	public function column_error_plugin( $item ) {
		return $item['error_plugin'];
	}

	/**
	 * Returns the value for the error_severity column.
	 *
	 * @param array $args {
	 *     @int $id The ID for the current comment.
	 *     @string $error_msg The value for the Error column.
	 *     @string $error_severity The value for the Severity column.
	 *     @string $error_plugin The value for the plugin name column.
	 *     @string $error_date The value for the date the log entry was created.
	 * }
	 *
	 * @return @int The severity for the current entry. Used for triaging errors.
	 */
	public function column_error_severity( $item ) {
		return $item['error_severity'];
	}

	/**
	 * Returns a list of bulk actions used to create the bulk action select field.
	 *
	 * @return array $args {
	 *     @string $delete The label of the delete action.
	 * }
	 */
	public function get_bulk_actions() {
		$actions = array(
			'delete'    => esc_html__( 'Delete', 'wp-logger' )
		);
		return $actions;
	}
}
