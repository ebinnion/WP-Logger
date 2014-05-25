<?php

class WP_Logger_API {

	/**
	 * Static variable to store whether WP Logger plugin is installed.
	 */
	public static $wp_logger_exists = false;


	/**
	 * Member data that is the plugin slug. Derived from plugin_basename().
	 */
	private $plugin_slug;

	/**
	 * Member data that stores array of post_name => WP_Post
	 */
	private $logs;

	function __construct() {

		add_action( 'init', array( $this, 'init' ), 2 );
	}

	function init() {
		global $post;

		// Set one static variable to test whether WP Logger plugin is installed
		if( class_exists( 'WP_Logger' ) && false == self::$wp_logger_exists ) {
			self::$wp_logger_exists = true;
		}

		/* The following is to get the plugin slug, accounting for the fact that this file
		 * may not be in the root directory of the plugin.
		 * 
		 * Match this pattern:
		 * - Start from beginning of string
		 * - Folowed by any character that is not a /, 1 or more times
		 * - Followed by any series of characters (representing path)
		 *
		 * Replace that pattern with the first capture group (all characters up to the first /).
		*/
		$this->plugin_slug = preg_replace( '#(^[^\/]+).*#', '$1', plugin_basename( __FILE__ ) );

 
		if( self::$wp_logger_exists ) {
			WP_Logger::register_plugin( $this->plugin_slug );

			$logs = new WP_Query(
				array(
					'post_type' => 'wp-logger',
					'tax_query' => array(
						array(
							'taxonomy' => WP_Logger::TAXONOMY,
							'field'    => 'slug',
							'terms'    => WP_Logger::prefix_slug( $this->plugin_slug )
						)
					)
				)
			);

			if ( $logs->have_posts() ) {
				while ( $logs->have_posts() ) {
					$logs->the_post();

					$this->logs[ $post->post_name ] = $post;
				}
			}
		}
	}

	function register_plugin_email( $email ) {
		$plugin_slug = preg_replace( '#(^[^\/]+).*#', '$1', plugin_basename( __FILE__ ) );
		$key = $plugin_slug . '_email';
		
		update_option( $key, sanitize_email( $email ) );
	}

	function build_slug( $string ) {
		return 'wp-' . sanitize_title( $string );
	}

	function add_entry( $message = '', $log = 'message' ) {
		if ( ! self::$wp_logger_exists ) {
			return false;
		}

		if ( empty( $message ) ) {
			return new WP_Error( 'missing-parameter', esc_html__( 'You must pass a message as the first parameter.', 'wp-logger-api' ) );
		}

		$prefixed_log = WP_Logger::prefix_slug( $log, $this->plugin_slug );

		if( isset( $this->logs[ $prefixed_log ] ) ) {
			return WP_Logger::add_entry( $this->logs[ $prefixed_log ], $message, $this->plugin_slug );
		} else {
			return WP_Logger::add_entry( sanitize_title( $log ), $message, $this->plugin_slug );
		}
	}	
}