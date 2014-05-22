<?php
/**
 * Plugin Name: WP Logger
 * Plugin URI: http://automattic.com
 * Description: Provides an interface to log errors and actions within a WordPress installation.
 * Version: 0.1
 * Author: Eric Binnion
 * Author URI: http://manofhustle.com
 * License: GPLv2 or later
 * Text Domain: wp-logger
 */

class WP_Logger {

	/**
	 * Memeber data for ensuring singleton pattern
	 */
	private static $instance = null;

	/**
	 * Adds all of the filters and hooks and enforces singleton pattern
	 */
	function __construct() {

		// Enforces a single instance of this class.
		if ( isset( self::$instance ) ) {
			wp_die( esc_html__( 'The WP_Logger class has already been loaded.', 'wp-logger' ) );
		}

		self::$instance = $this;

		// Should there be spaces before each callback for better formatting?
		// Lots of space in some of these, but definitely more readable.

		add_action( 'init',                                   array( $this, 'init' ) );
		add_filter( 'manage_wp-logger_posts_columns',         array( $this, 'modify_cpt_columns' ) );
		add_action( 'manage_posts_custom_column',             array( $this, 'add_column_content' ) , 10, 2 );
		add_filter( 'manage_edit-wp-logger_sortable_columns', array( $this, 'add_sortable_columns' ) );
		add_action( 'pre_get_posts',                          array( $this, 'filter_logger_admin_search' ) );
		add_filter( 'get_search_query',                       array( $this, 'filter_search_query' ) );
	}

	/**
	 * Exposes a method to allow developers to log an error.
	 *
	 * @param  WP_Error $error A WP_Error object containing an error code and error message
	 * @return null|WP_Error Returns null on success or WP_Error object on failure
	 */
	static function add_error( $error ) {
		if( ! is_wp_error( $error ) ) {
			return wp_error( 'requires-wp-error', esc_html__( 'This method requires a WP_Error object as its parameter.', 'wp-logger' ) );
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

	/**
	 * Register the wp-logger post type.
	 *
	 * Registers the wp-logger post type such that only admins can see the errors in the admin
	 * and admins can not add new errors through the admin UI interface.
	 */
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
					'name'               => esc_html__( 'Errors', 'wp-logger' ),
					'singular_name'      => esc_html__( 'Error', 'wp-logger' ),
					'add_new'            => esc_html__( 'Add New Error', 'wp-logger' ),
					'add_new_item'       => esc_html__( 'Add New Error', 'wp-logger' ),
					'edit_item'          => esc_html__( 'Edit Error', 'wp-logger' ),
					'new_item'           => esc_html__( 'Add New Error', 'wp-logger' ),
					'view_item'          => esc_html__( 'View Error', 'wp-logger' ),
					'search_items'       => esc_html__( 'Search Errors', 'wp-logger' ),
					'not_found'          => esc_html__( 'No errors found', 'wp-logger' ),
					'not_found_in_trash' => esc_html__( 'No errors found in trash', 'wp-logger' )
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
	
		$labels = array(
			'name'              => esc_html__( 'Plugins', 'wp-logger' ),
			'singular_name'     => esc_html__( 'Plugin', 'wp-logger' ),
			'search_items'      => esc_html__( 'Search Plugins', 'wp-logger' ),
			'all_items'         => esc_html__( 'All Plugins', 'wp-logger' ),
			'parent_item'       => esc_html__( 'Parent Plugin', 'wp-logger' ),
			'parent_item_colon' => esc_html__( 'Parent Plugin:', 'wp-logger' ),
			'edit_item'         => esc_html__( 'Edit Plugin', 'wp-logger' ),
			'update_item'       => esc_html__( 'Update Plugin', 'wp-logger' ),
			'add_new_item'      => esc_html__( 'Add New Plugin', 'wp-logger' ),
			'new_item_name'     => esc_html__( 'New Plugin Name', 'wp-logger' ),
			'menu_name'         => esc_html__( 'Plugins', 'wp-logger' ),
		);

		register_taxonomy( 
			'plugin-errors', 
			'wp-logger', 
			array(
				'labels'            => $labels,
				'show_in_nav_menus' => true,
				'show_ui'           => true,
				'query_var'         => true,
			)
		);
	}

	/**
	 * Modifies the columns that show in the admin UI for the wp-logger CPT.
	 *
	 * @param  array $cols An array of column => label. 
	 * @return array $args {
	 *     @string string $cb Allows multiple checking of posts in WordPress admin UI.
	 *     @string string $error_code Returns the Error Code label.
	 *     @string string $error_msg Return the Error Message label.
	 *     @string string $error_date Returns the Date label.
	 * }
	 */
	function modify_cpt_columns( $cols ) {
		
		return array(
			'cb'         => '<input type="checkbox" />',
			'error_code' => esc_html__( 'Error Code', 'wp-logger' ),
			'error_msg'  => esc_html__( 'Error Message', 'wp-logger' ),
			'error_date' => esc_html__( 'Date', 'wp-logger' ),
		);

	}

	/**
	 * Adds custom meta content to custom columns for wp-logger CPT.
	 *
	 * @param  string $column The name of the column to display.
	 * @param  int $post_id The ID of the current post.
	 */
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

	/**
	 * Allows custom columns for wp-logger CPT to be sorted.
	 *
	 * @return array $args {
 	 *     @string string $error_code
 	 *     @string string $error_msg
 	 *     @string string $error_date
 	 * }
	 */
	function add_sortable_columns() {

		return array(
			'error_code' => 'error_code',
			'error_msg'  => 'error_msg',
			'error_date' => 'error_date'
		);

	}

	/**
	 * Modifies the WP_Query object for wp-logger CPT for better search.
	 *
	 * Iff search parameter is set, query is in admin, and current post type is wp-logger,
	 * this method will update the query to allow searching of custom meta fields instead of
	 * post_title and post_content. Uses a LIKE comparison for somewhat search results.
	 *
	 * @param WP_Query $query The WP_Query instance (passed by reference).
	 */
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

	/**
	 * Will update search header to use $_GET['s'] in order to correctly show search results header.
	 *
	 * Will update search header to use $_GET['s'] in order to correctly show search results header
	 * because filter_logger_admin_search removes search paremter within query. Lack of the search
	 * parameter in query causes the header to show `Search results for ""` with no search query.
	 *
	 * @param mixed $search Contents of the search query variable.
	 * @return mixed $search Udpated contents of the search query variable.
	 */
	function filter_search_query( $search ) {
		global $post;

		if ( is_admin() && 'wp-logger' == $post->post_type && ! empty( $_GET['s'] ) ) {
			return esc_html( urldecode( $_GET['s'] ) );
		}

		return $search;
	}
}

new WP_Logger();