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

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_filter( 'manage_wp-logger_posts_columns', array( $this, 'modify_cpt_columns' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'add_column_content' ) , 10, 2 );
	}

	static function add_error( $error ) {
		if( ! is_wp_error( $error ) ) {
			return wp_error( 'requires-wp-error', 'This method requires a WP_Error object as its parameter.' );
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
		global $wp_roles;

		register_post_type(
			'wp-logger',
			array(
				'label'           => 'Errors',
				'public'          => false,
				'show_ui'         => true,
				'rewrite'         => false,
				'menu_position'   => 100,
				'supports'        => false,
				'capabilities' => array(
					'edit_post'          => 'update_core',
					'read_post'          => 'update_core',
					'delete_post'        => 'update_core',
					'edit_posts'         => 'update_core',
					'edit_others_posts'  => 'update_core',
					'publish_posts'      => 'update_core',
					'read_private_posts' => 'update_core'
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
						<input type="text" name="_wp_logger_code" value="<?php echo $error_code; ?>" class="regular-text">
					</td>
				</tr>
			</tbody>
		</table>

		<?php
	}

	function modify_cpt_columns( $cols ) {
		$cols = array(
			'cb'         => '<input type="checkbox" />',
			'error_code' => __( 'Error Code', 'wordpress-error-logger' ),
			'error_msg'  => __( 'Error Message', 'wordpress-error-logger' ),
			'error_date' => __( 'Date', 'wordpress-error-logger' ),
		);

		return $cols;
	}

	function add_column_content( $column, $post_id ) {

		if ( 'error_code' == $column ) {
			$code = get_post_meta( $post_id, '_wp_logger_error_code', true );
			echo $code;
		}

		if ( 'error_msg' == $column ) {
			$message = get_post_meta( $post_id, '_wp_logger_error_msg', true );
			echo $message;
		}

		if( 'error_date' == $column ) {
			echo get_the_time( 'Y/m/d', $post_id );
			echo '<br>';
			echo get_the_time( 'H:i:s', $post_id );
		}
	}
}

new WP_Error_Logger();