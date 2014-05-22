<?php
/**
 * Plugin Name: WordPress Error Logger
 * Plugin URI: http://automattic.com
 * Description: Provides an interface to log errors and actions within a WordPress installation.
 * Version: 0.1
 * Author: Eric Binnion
 * Author URI: http://manofhustle.com
 * License: GPLv2 or later
 * Text Domain: wordpress-error-logger
 */

class WP_Error_Logger {

	/**
	 * Memeber data for ensuring singleton pattern
	 */
	private static $instance = null;

	/**
	 * Adds all of the filters and hooks
	 */
	function __construct() {

		// Enforces a single instance of this class.
		if ( isset( self::$instance ) ) {
			wp_die( esc_html__( 'The WP_Logger class has already been loaded.', 'wordpress-error-logger' ) );
		}

		self::$instance = $this;

		// Should there be spaces before each callback for better formatting?
		// Lots of space in some of these, but definitely more readable.

		add_action( 'init',                                   array( $this, 'init' ) );
		add_action( 'add_meta_boxes',                         array( $this, 'add_meta_boxes' ) );
		add_filter( 'manage_wp-logger_posts_columns',         array( $this, 'modify_cpt_columns' ) );
		add_action( 'manage_posts_custom_column',             array( $this, 'add_column_content' ) , 10, 2 );
		add_filter( 'manage_edit-wp-logger_sortable_columns', array( $this, 'add_sortable_columns' ) );
		add_action( 'pre_get_posts',                          array( $this, 'filter_logger_admin_search' ) );
		add_filter( 'get_search_query',                       array( $this, 'filter_search_query' ) );
	}

	static function add_error( $error ) {
		if( ! is_wp_error( $error ) ) {
			return wp_error( 'requires-wp-error', esc_html__( 'This method requires a WP_Error object as its parameter.', 'wordpress-error-logger' ) );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'      => 'wp-logger',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				'post_status'    => 'publish',
			)
		);

		if( $post_id > 0 ) {
			add_post_meta( $post_id, '_wp_logger_error_code', $error->get_error_code() );
			add_post_meta( $post_id, '_wp_logger_error_msg', $error->get_error_message() );
		}
	}

	function init() {

		register_post_type(
			'wp-logger',
			array(
				'public'        => false,
				'show_ui'       => true,
				'rewrite'       => false,
				'menu_position' => 100,
				'supports'      => false,
				'labels'        => array(
					'name'               => esc_html__( 'Errors', 'wordpress-error-logger' ),
					'singular_name'      => esc_html__( 'Error', 'wordpress-error-logger' ),
					'add_new'            => esc_html__( 'Add New Error', 'wordpress-error-logger' ),
					'add_new_item'       => esc_html__( 'Add New Error', 'wordpress-error-logger' ),
					'edit_item'          => esc_html__( 'Edit Error', 'wordpress-error-logger' ),
					'new_item'           => esc_html__( 'Add New Error', 'wordpress-error-logger' ),
					'view_item'          => esc_html__( 'View Error', 'wordpress-error-logger' ),
					'search_items'       => esc_html__( 'Search Errors', 'wordpress-error-logger' ),
					'not_found'          => esc_html__( 'No errors found', 'wordpress-error-logger' ),
					'not_found_in_trash' => esc_html__( 'No errors found in trash', 'wordpress-error-logger' )
				),
				'capabilities' => array(
					'edit_post'          => 'update_core',
					'read_post'          => 'update_core',
					'delete_post'        => 'update_core',
					'edit_posts'         => 'update_core',
					'edit_others_posts'  => 'update_core',
					'publish_posts'      => 'update_core',
					'read_private_posts' => 'update_core',
					'create_posts'       => false,
				),
			)
		);

	}

	function add_meta_boxes() {
		add_meta_box( 'wp-logger', 'Logger Information', array( $this, 'wp_logger_metabox' ), 'wp-logger', 'normal', 'high' );
	}

	function wp_logger_metabox() {
		global $post;

		$meta       = get_post_meta( $post->ID );
		$error_code = isset( $meta['_wp_logger_error_code'][0] ) ? $meta['_wp_logger_error_code'][0] : '';
		$error      = isset( $meta['_wp_logger_error_msg'][0] ) ? $meta['_wp_logger_error_msg'][0] : null;

		wp_nonce_field( 'verify-wp-logger-metabox', 'wp-logger-metabox' ); 

		if( isset( $error ) ) {
			echo '<pre>';
			print_r( $error );
			echo '</pre>';
		}

		?>

		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">Error Code</th>
					<td>
						<input type="text" name="_wp_logger_code" value="<?php echo esc_html( $error_code ); ?>" class="regular-text">
					</td>
				</tr>
			</tbody>
		</table>

		<?php
	}

	function modify_cpt_columns( $cols ) {
		
		return array(
			'cb'         => '<input type="checkbox" />',
			'error_code' => esc_html__( 'Error Code', 'wordpress-error-logger' ),
			'error_msg'  => esc_html__( 'Error Message', 'wordpress-error-logger' ),
			'error_date' => esc_html__( 'Date', 'wordpress-error-logger' ),
		);

	}

	function add_column_content( $column, $post_id ) {

		if ( 'error_code' == $column ) {
			echo get_post_meta( $post_id, '_wp_logger_error_code', true );
		}

		if ( 'error_msg' == $column ) {
			echo get_post_meta( $post_id, '_wp_logger_error_msg', true );
		}

		if( 'error_date' == $column ) {
			echo get_the_time( 'Y/m/d', $post_id );
			echo '<br>';
			echo get_the_time( 'H:i:s', $post_id );
		}
	}

	function add_sortable_columns() {

		return array(
			'error_code' => 'error_code',
			'error_msg'  => 'error_msg',
			'error_date' => 'error_date'
		);

	}

	function filter_logger_admin_search( $query ) {

		// Only filter the search on the admin side and for WordPress error log post types 
		// and only if search parameter is set
		if ( is_admin() && 'wp-logger' == $query->get( 'post_type' ) && ! empty( $_GET['s'] ) ) {

			// Get the existing meta_query so we can update it
			$meta_query = $query->get( 'meta_query' );

			// Get the search parameter
			$search = $query->get( 's' );

			// Any meta key added here will be added to search
			$meta_keys = array( '_wp_logger_error_msg', '_wp_logger_error_code' );

			foreach ( $meta_keys as $meta_key ) {
				$meta_query[] = array(
					'key'     => $meta_key,
					'compare' => 'LIKE',
					'value'   => $search
				);
			}

			// OR the different meta_queries 
			$meta_query['relation'] = 'OR';

			// Update the query to include 
			$query->set( 'meta_query', $meta_query );

			// Remove default search query parameters
			$query->set( 's', '' );
			$query->set( 'post_title', '' );
			$query->set( 'post_content', '' );
		}
	}

	function filter_search_query( $search ) {
		global $post;

		if ( is_admin() && 'wp-logger' == $post->post_type && ! empty( $_GET['s'] ) ) {
			return esc_html( urldecode( $_GET['s'] ) );
		}

		return $search;
	}
}

new WP_Error_Logger();