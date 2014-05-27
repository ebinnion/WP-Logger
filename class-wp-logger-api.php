<?php
/**
 * This file is a library to be included with WordPress plugins.
 *
 * This class handles provides a simple interface that plugin developers can use
 * to connect to the WP_Logger class. All public APIs of this class will check to 
 * make sure that the WP Logger plugin is installed before making API calls. If
 * plugin developers want to simplify the process of users reporting bugs to the
 * developer, the developer should call `register_plugin_email()` on plugin activation.
 * 
 * @since unknown
 */

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

	/**
	 * Adds a callback for the init action hook.
	 */
	function __construct() {

		add_action( 'init', array( $this, 'init' ), 2 );
	}

	/**
	 * Checks if WP Logger plugin is installed, initializes $plugin_slug member data
	 * and initializes array of logs in $logs member data.
	 *
	 * @see Function/method/class relied on
	 * @link URL
	 * @global WP_Post $post The global WP_Post object.
	 *
	 */
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

			// Query to get all of the logs (posts) associated with this plugin
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

			// Save these logs in an array of post_name => WP_Post
			if ( $logs->have_posts() ) {
				while ( $logs->have_posts() ) {
					$logs->the_post();

					$this->logs[ $post->post_name ] = $post;
				}
			}
		}
	}

	/**
	 * Saves the developers support email address in the options table.
	 *
	 * @param  string $email The email address.
	 * @return bool True if email was saved/updated and false if email save/update failed.
	 */
	function register_plugin_email( $email ) {
		$plugin_slug = preg_replace( '#(^[^\/]+).*#', '$1', plugin_basename( __FILE__ ) );
		$key = $plugin_slug . '_email';
		
		return update_option( $key, sanitize_email( $email ) );
	}

	/**
	 * Acts as a wrapper to the add_entry method in WP_Logger.
	 *
	 * First, checks to see if WP Logger plugin is installed before going any further. Will
	 * then check to see if there is an existing log matching the $log that the developer has called.
	 * If there is an existing log with the same slug, then a WP_Post object is passed to WP_Logger:add_entry,
	 * else, a the sluggified log name is passed to WP_Logger:add_entry().
	 *
	 * @param  string $message The message to log.
	 * @param  string $log The log to add the message to.
	 * @return WP_Error
	 */
	function add_entry( $message = '', $log = 'message' ) {
		if ( ! self::$wp_logger_exists ) {
			return new WP_Error( 'plugin-not-installed', esc_html__( 'The WP Logger plugin must be installed.', 'wp-logger-api' ) );
		}

		if ( empty( $message ) ) {
			return new WP_Error( 'missing-parameter', esc_html__( 'You must pass a message as the first parameter.', 'wp-logger-api' ) );
		}

		// Call WP_Logger's prefix_slug method to sluggify post_name. Used to compare against $this->logs 
		// and see if log already exists.
		$prefixed_log = WP_Logger::prefix_slug( $log, $this->plugin_slug );

		// If log already exists, then pass the WP_Post object. Else, pass the $log name and the log post
		// will be created in WP_Logger:add_entry().
		if( isset( $this->logs[ $prefixed_log ] ) ) {
			return WP_Logger::add_entry( $this->logs[ $prefixed_log ], $message, $this->plugin_slug );
		} else {
			return WP_Logger::add_entry( sanitize_title( $log ), $message, $this->plugin_slug );
		}
	}	
}