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
				'singular'  => esc_html__( 'log', 'wp-logger' ),
				'plural'    => esc_html__( 'logs', 'wp-logger' ),
				'ajax'      => false
			)
		);

		$this->custom_query_string = '';

		if ( ! empty( $_POST['search'] ) && ! isset( $_GET['search'] ) ) {
			$this->custom_query_string .= "&search={$_POST['search']}";
		}

		if ( ! empty( $_POST['plugin-select'] ) && ! isset( $_GET['plugin-select'] ) ) {
			$this->custom_query_string .= "&plugin-select={$_POST['plugin-select']}";
		}

		if ( ! empty( $_POST['log-select'] ) && ! isset( $_GET['log-select'] ) ) {
			$this->custom_query_string .= "&log-select={$_POST['log-select']}";
		}

		if ( ! empty( $_POST['session-select'] ) && ! isset( $_GET['session-select'] ) ) {
			$this->custom_query_string .= "&session-select={$_POST['session-select']}";
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
	 * Overrides parent class in order to no set the 'fixed' class for the <table> tag.
	 * @return array Of Classes to apply to <table> tag.
	 */
	function get_table_classes() {
		return array( 'widefat', $this->_args['plural'] );
	}

	/**
	 * Sets column ids and labels.
	 *
	 * @return array $args {
	 *     @string $log_msg The label for the log column.
	 *     @string $log_severity The label for the Severity column.
	 *     @string $log_plugin The label for the plugin name column.
	 *     @string $log_date The label for the date the log entry was created.
	 * }
	 */
	public function get_columns() {
		$columns = array(
			'log_msg'      => esc_html__( 'Log Message', 'wp-logger' ),
			'log_severity' => esc_html__( 'Severity', 'wp-logger' ),
			'log_plugin'   => esc_html__( 'Plugin', 'wp-logger' ),
			'log_date'     => esc_html__( 'Date', 'wp-logger' ),

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
	 *     @string $log_severity Allows the severity column to be sorted.
	 *     @string $log_plugin Allows the plugin name column to be sorted.
	 *     @string $log_date Allows the date column to be sorted.
	 * }
	 */
	public function get_sortable_columns() {
		return array(
			'log_severity' => array( 'log_severity', false ),
			'log_plugin'   => array( 'log_plugin', false ),
			'log_date'     => array( 'log_date', false ),
		);
	}

	/**
	 * Returns an array of data, where each row is an associatve array that represents the data for that row.
	 *
	 * @return  @array Arrays of associative arrays.
	 */
	private function table_data() {
		$data = array();

		if ( ! empty( $this->items ) ) {
			foreach ( $this->items as $item ) {
				$data[] = array(
					'id'           => $item->the_ID,
					'log_severity' => $item->severity,
					'log_msg'      => $item->message,
					'log_date'     => $item->the_date,
					'log_plugin'   => $item->log_plugin,
					'session'      => $item->session
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
	 * Returns the value for the id column.
	 *
	 * @param array $args {
	 *     @int $id The ID for the current comment.
	 *     @string $log_msg The value for the log column.
	 *     @string $log_severity The value for the Severity column.
	 *     @string $log_plugin The value for the plugin name column.
	 *     @string $log_date The value for the date the log entry was created.
	 * }
	 *
	 * @return @int The ID for the current comment.
	 */
	public function column_id( $item ) {
		return $item['id'];
	}

	/**
	 * Returns the value for the log_msg column.
	 *
	 * @param array $args {
	 *     @int $id The ID for the current comment.
	 *     @string $log_msg The value for the log column.
	 *     @string $log_severity The value for the Severity column.
	 *     @string $log_plugin The value for the plugin name column.
	 *     @string $log_date The value for the date the log entry was created.
	 * }
	 *
	 * @return @string The log message for the current comment.
	 */
	public function column_log_msg( $item ) {
		if ( 1 == $item['session'] ) {
			$session_url = esc_url( admin_url( 'admin.php?page=wp_logger_messages&session-select=' . $item['id'] . $this->custom_query_string ) );
			$message = "<a href='{$session_url}' class='thickbox'>{$item['log_msg']}</a>";
		} else {
			$message = $item['log_msg'];
		}

		return $message;
	}

	/**
	 * Returns the value for the log_date column.
	 *
	 * @param array $args {
	 *     @int $id The ID for the current comment.
	 *     @string $log_msg The value for the log column.
	 *     @string $log_severity The value for the Severity column.
	 *     @string $log_plugin The value for the plugin name column.
	 *     @string $log_date The value for the date the log entry was created.
	 * }
	 *
	 * @return @string Date the current entry was added.
	 */
	public function column_log_date( $item ) {
		return $item['log_date'];
	}

	/**
	 * Returns the value for the log_plugin column.
	 *
	 * @param array $args {
	 *     @int $id The ID for the current comment.
	 *     @string $log_msg The value for the log column.
	 *     @string $log_severity The value for the Severity column.
	 *     @string $log_plugin The value for the plugin name column.
	 *     @string $log_date The value for the date the log entry was created.
	 * }
	 *
	 * @return @string The plugin which the current entry is assigned to.
	 */
	public function column_log_plugin( $item ) {
		return $item['log_plugin'];
	}

	/**
	 * Returns the value for the log_severity column.
	 *
	 * @param array $args {
	 *     @int $id The ID for the current comment.
	 *     @string $log_msg The value for the log column.
	 *     @string $log_severity The value for the Severity column.
	 *     @string $log_plugin The value for the plugin name column.
	 *     @string $log_date The value for the date the log entry was created.
	 * }
	 *
	 * @return @int The severity for the current entry. Used for triaging logs.
	 */
	public function column_log_severity( $item ) {
		return $item['log_severity'];
	}
}
