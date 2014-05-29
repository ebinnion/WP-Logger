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
	 * Constant for the WP Logger taxonomy
	 */
	const TAXONOMY = 'plugin-errors';

	/**
	 * Constant for the WP Logger custom post type
	 */
	const CPT = 'wp-logger';

	/**
	 * Adds all of the filters and hooks and enforces singleton pattern
	 */
	function __construct() {

		// Enforces a single instance of this class.
		if ( isset( self::$instance ) ) {
			wp_die( esc_html__( 'The WP_Logger class has already been loaded.', 'wp-logger' ) );
		}

		self::$instance = $this;

		add_action( 'init',             array( $this, 'init' ), 1 );
		add_action( 'admin_menu',       array( $this, 'add_menu_page' ) );
		add_action( 'admin_footer',     array( $this, 'admin_footer' ) );
		add_filter( 'comments_clauses', array( $this, 'add_comment_author' ), 10, 2 );
	}

	/**
	 * Prefixes a string with 'wp-logger-' or $plugin_name
	 *
	 * @param  string $slug Plugin slug.
	 * @param  string $plugin_name If set, slug will be prefixed with plugin_name
	 * @return string String that being with 'wp-logger-'
	 */
	static function prefix_slug( $slug, $plugin_name = '' ) {
		if ( ! empty( $plugin_name ) ) {
			return sanitize_title( $plugin_name ) . '-' . sanitize_title( $slug );
		} else {
			return 'log-' . sanitize_title( $slug );
		}
	}

	/**
	 * Exposes method used to register a developer's email for sending logs.
	 *
	 * @param  string $plugin_name Plugin slug.
	 * @param  string $email The developer's email address.
	 * @return bool|WP_error Returns true on success, false if $email is empty or if update/add fails, or WP_Error object if email is not valid.
	 */
	function register_plugin_email( $plugin_name, $email = '' ) {
		if ( is_email( $email ) ) {
			return update_option( $plugin_name . '_email', sanitize_email( $email ) );
		} else {
			return new WP_Error( 'not-an-email', esc_html__( 'The second parameter for register_plugin_email must be a valid email address.', 'wp-logger-api' ) );
		}

		return false;
	}

	/**
	 * Will retrieve the developer email for the current plugin
	 *
	 * @param  string $plugin_name The unique string identifying this plugin. Also acts as term for plugin.
	 * @return string The developers email or empty string.
	 */
	function get_plugin_email( $plugin_name ) {
		return get_option( $plugin_name . '_email', '' );
	}

	/**
	 * Exposes a method to allow developers to log an error.
	 *
	 * @global WP_Post $post The global WP_Post object.
	 *
	 * @param  string $log String identifying this plugin.
	 * @param  string $plugin_name The plugin's slug.
	 * @return bool Returns true if entry was successfully added and false if entry failed.
	 */
	function add_entry( $plugin_name, $log = 'message', $message ) {
		global $post;

		$prefixed_term = self::prefix_slug( $plugin_name );

		// If there is not currently a term (category) for this plugin, create it.
		if( ! term_exists( $prefixed_term, self::TAXONOMY ) ) {

			// Create a taxonomy term that distinguishes current plugin from others.
			$registered = wp_insert_term(
				$plugin_name,
				self::TAXONOMY,
				array(
					'slug' => $prefixed_term
				)
			);

			// Check if taxonomy term was succeessfully added and return if not.
			if( is_wp_error( $registered ) ) {
				return false;
			}
		}

		$log_exists = new WP_Query(
			array(
				'post_type' => self::CPT,
				'post_name' => self::prefix_slug( $log, $plugin_name ),
				'tax_query' => array(
					array(
						'taxonomy' => self::TAXONOMY,
						'field'    => 'slug',
						'terms'    => $prefixed_term
					)
				)
			)
		);

		/*
		 * If the log that the developer wants to write to exists, add a comment.
		 * Else, create log then add comment.
		 */
		if( $log_exists->have_posts() ) {
			$log_exists->the_post();
			$post_id = $post->ID;

		} else {
			$post_id = wp_insert_post(
				array(
					'post_title'     => $log,
					'post_name'      => self::prefix_slug( $log, $plugin_name ),
					'post_type'      => self::CPT,
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
					'post_status'    => 'publish',
				)
			);

			if( 0 == $post_id ) {
				return false;
			}

			$add_terms = wp_set_post_terms(
				$post_id,
				$prefixed_term,
				self::TAXONOMY
			);

			/*
			 * A successful call to wp_set_post_terms will return an array. A failure could return
			 * a WP_Error object, false, or a string.
			 */
			if( is_wp_error( $add_terms ) || false == $add_terms || ! is_array( $add_terms ) ) {
				return false;
			}
		}

		$comment_data = array(
			'comment_post_ID'      => $post_id,
			'comment_content'      => $message,
			'comment_author'       => $plugin_name,
			'comment_approved'     => self::CPT,
			'comment_author_IP'    => '',
			'comment_author_url'   => '',
			'comment_author_email' => '',
		);

		$comment_id = wp_insert_comment( wp_filter_comment( $comment_data ) );

		// Returns true if comment/entry was successfully added and false on falure.
		return (boolean) $comment_id;
	}

	/**
	 * Register the wp-logger post type and plugin-errors taxonomy. Also, process bulk delete action and log mailing.
	 */
	function init() {
		register_post_type(
			self::CPT,
			array(
				'public'        => false,
				'show_ui'       => false,
				'rewrite'       => false,
				'menu_position' => 100,
				'supports'      => false,
				'labels'        => array(
					'name'               => esc_html__( 'Errors'                  , 'wp-logger' ),
					'singular_name'      => esc_html__( 'Error'                   , 'wp-logger' ),
					'add_new'            => esc_html__( 'Add New Error'           , 'wp-logger' ),
					'add_new_item'       => esc_html__( 'Add New Error'           , 'wp-logger' ),
					'edit_item'          => esc_html__( 'Edit Error'              , 'wp-logger' ),
					'new_item'           => esc_html__( 'Add New Error'           , 'wp-logger' ),
					'view_item'          => esc_html__( 'View Error'              , 'wp-logger' ),
					'search_items'       => esc_html__( 'Search Errors'           , 'wp-logger' ),
					'not_found'          => esc_html__( 'No errors found'         , 'wp-logger' ),
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
			'name'              => esc_html__( 'Plugins'        , 'wp-logger' ),
			'singular_name'     => esc_html__( 'Plugin'         , 'wp-logger' ),
			'search_items'      => esc_html__( 'Search Plugins' , 'wp-logger' ),
			'all_items'         => esc_html__( 'All Plugins'    , 'wp-logger' ),
			'parent_item'       => esc_html__( 'Parent Plugin'  , 'wp-logger' ),
			'parent_item_colon' => esc_html__( 'Parent Plugin:' , 'wp-logger' ),
			'edit_item'         => esc_html__( 'Edit Plugin'    , 'wp-logger' ),
			'update_item'       => esc_html__( 'Update Plugin'  , 'wp-logger' ),
			'add_new_item'      => esc_html__( 'Add New Plugin' , 'wp-logger' ),
			'new_item_name'     => esc_html__( 'New Plugin Name', 'wp-logger' ),
			'menu_name'         => esc_html__( 'Plugins'        , 'wp-logger' ),
		);

		register_taxonomy(
			self::TAXONOMY,
			self::CPT,
			array(
				'labels'            => $labels,
				'show_in_nav_menus' => true,
				'show_ui'           => true,
				'query_var'         => true,
			)
		);

		// Delete any error entries that were checked through the bulk action interface.
		if( is_admin() && isset( $_GET['page'] ) ) {

			if( 'wp_logger_errors' == $_GET['page'] && isset( $_POST['action'] ) && 'delete' == $_POST['action'] ) {

				check_admin_referer( 'wp_logger_generate_report', 'wp_logger_form_nonce' );

				if( ! empty( $_POST['logs'] ) ) {

					foreach( $_POST['logs'] as $log ) {
						wp_delete_comment( intval( $log ), true );
					}
				}
			}
		}

		// Condition for emailing logs. Logs are emailed as a JSON object.
		if( isset( $_POST['send_logger_email'] ) ) {
			$this->process_email_log();
		}
	}

	/**
	 * Will check if request to email log was sent from WordPress admin, then will attempt
	 * to create a JSON log and send as an attachment. Falls back to sending JSON log
	 * directly within email message.
	 */
	function process_email_log() {
		check_admin_referer( 'wp_logger_generate_report', 'wp_logger_form_nonce' );

		$entries = $this->get_entries();

		if( ! empty( $entries ) ) {

			// Build an array of just the data that needs to be sent to the developer.
			$data = array();

			foreach( $entries['entries'] as $entry ){

				$data[] = array(
					'id'           => $entry->comment_ID,
					'error_msg'    => $entry->comment_content,
					'error_date'   => $entry->comment_date,
					'error_plugin' => $entry->comment_author
				);
			}

			$plugin_email = sanitize_email( $_POST['email-logs'] );
			$current_site = get_option( 'home' );
			$time         = time();

			// Make sure that wp-content/wp-logger exists, if not, create it.
    		if ( ! is_dir( WP_CONTENT_DIR . '/wp-logger' ) ) {
    			mkdir( WP_CONTENT_DIR . '/wp-logger' );
    		}

    		// Test again to make sure that wp-content/wp-logger exists before attempting to open a file in the directory.
			if ( is_dir( WP_CONTENT_DIR . '/wp-logger' ) ) {
				$file = fopen( WP_CONTENT_DIR . "/wp-logger/{$time}.json",'w' );

				/*
				 * If the JSON file was created successfully, then let's send that as an attachment.
				 * If the file was no created successfully, then attempt to send the logs directly within the email message.
				 */
				if( false !== $file ) {
					fwrite( $file, json_encode( $data ) );

					$_POST['message_sent'] = wp_mail(
						$plugin_email,
						sprintf( __( 'Logs from %s', 'wp-logger' ), $current_site ),
						sprintf( __( 'Attached is a log in JSON format from %s', 'wp-logger' ), $current_site ),
						'From: WP Logger Logs' . "\r\n",
						array( WP_CONTENT_DIR . "/wp-logger/{$time}.json" )
					);
				} else {
					$_POST['message_sent'] = wp_mail(
						$plugin_email,
						sprintf( __( 'Logs from %s', 'wp-logger' ), $current_site ),
						json_encode( $data ),
						'From: WP Logger Logs' . "\r\n"
					);
				}

				fclose( $file );
			}
		}
	}

	/**
	 * Adds a menu page to the WordPress admin with a title of Errors
	 */
	function add_menu_page() {
		add_menu_page( esc_html__( 'Errors', 'wp-logger' ), esc_html__( 'Errors', 'wp-logger' ), 'update_core', 'wp_logger_errors', array( $this, 'generate_menu_page' ), 'dashicons-editor-help', 100 );
	}

	/**
	 * Adds the ability to query by comment author using the WP_Comment_Query class.
	 *
	 * @global wpdb $wpdb Global insantiation of WordPress database class wpdb.
	 *
	 * @param array $pieces Array containing the comment query arguments.
	 * @param WP_Comment_Quey &Comment Reference to the WP_Comment_Query object.
	 * @return array Containing the comment query arguments.
	 */
	function add_comment_author( $pieces, &$comment ) {
		global $wpdb;

		if( isset( $comment->query_vars['comment_author'] ) ) {
			$pieces['where'] .= $wpdb->prepare( ' AND comment_author = %s', $comment->query_vars['comment_author'] );
		}

		return $pieces;
	}

	/**
	 * Returns an array of logger messages (comments) and the count of those entries.
	 *
	 * @return array $args {
	 *     int $count The number of entries that fit the paraemters.
	 *     array $entries An array of comment comment rows.
	 * }
	 */
	function get_entries() {
		$log_query = new WP_Comment_Query;

		// The CPT slug is stored in comment status, so we are querying for comment status here.
		$args = array(
			'status' => self::CPT,
		);

		if ( isset( $_GET['orderby'] ) ) {

			if ( 'error_plugin' == $_GET['orderby'] ) {
				$args['orderby'] = 'comment_author';
			} else if ( 'error_date' == $_GET['orderby'] ) {
				$args['orderby'] = 'comment_date';
			}

			if( isset( $_GET['order'] ) ) {
				$args['order'] = $_GET['order'];
			} else {
				$args['order'] = 'desc';
			}
		}

		if ( ! empty( $_POST['search'] ) ) {
			$args['search'] = $_POST['search'];
		}

		if ( ! empty( $_POST['plugin-select'] ) ) {
			$args['comment_author'] = $_POST['plugin-select'];
		}

		if ( isset( $_POST['log-select'] ) ) {
			$args['post_id'] = $_POST['log-select'];
		}

		// Initialize an array to return the entries and count.
		$return = array();

		// Get the count of all comments that fit these arguments.
		$args['count']   = true;
		$return['count'] = $log_query->query( $args );


		$args['count'] = false;

		// If sending an email of logs, then return as many entries as possible.
		if( ! isset( $_POST['send_logger_email'] ) ) {

			// Get up to 20 of entries that match parameters.
			$args['number'] = 20;

			// Update the offset value based on what page query is running on.
			if( isset( $_GET['paged'] ) && intval( $_GET['paged'] ) > 1 ) {
				$args['offset'] = ( intval( $_GET['paged'] ) - 1 ) * 20;
			}
		}

		// Only return the first 20 of the comments that fit these arguments
		$return['entries'] = $log_query->query( $args );

		return $return;
	}

	/**
	 * Return the posts (logs) for the posts with $plugin_term term
	 *
	 * @param  string $plugin_term The term that for the current plugin.
	 * @return false|WP_Query False if no $plugin_term is passed or WP_Query object containing the posts for this plugin.
	 */
	function get_logs( $plugin_term ) {
		if( ! $plugin_term ) {
			return false;
		}

		$logs = new WP_Query(
			array(
				'post_type' => self::CPT,
				'tax_query' => array(
					array(
						'taxonomy' => self::TAXONOMY,
						'field'    => 'slug',
						'terms'    => self::prefix_slug( $plugin_term )
					)
				)
			)
		);

		return $logs;
	}

	/**
	 * Retrieves the terms (plugins) for the plugin-errors taxonomy.
	 *
	 * @return array. An array of term objects.
	 */
	function get_plugins() {
		$plugins = get_terms(
			self::TAXONOMY,
			array(
				'orderby' => 'name',
				'order'   => 'ASC'
			)
		);

		return $plugins;
	}

	/**
	 * Adds a hidden input field to the wp_logger_errors page and then submits the form. The hiddne field
	 * acts as a flag to send logs to an email.
	 */
	function admin_footer() {
		if( isset( $_GET['page'] ) && 'wp_logger_errors' == $_GET['page'] ) {
			?>

			<script>
				(function( $ ) {
				 	$( '#send-logger-email' ).click( function(){
				 		var form = $( '#logger-form' );
				 		form.prepend( '<input type="hidden" name="send_logger_email" value="1" >' );
				 		form.submit();
				 	});
				})( jQuery );
			</script>

			<?php
		}
	}

	/**
	 * Outputs the errors menu page.
	 *
	 * @global WP_Post $post The global WP_Post object.
	 *
	 */
	function generate_menu_page() {
		global $post;

		// Include WP Logger copy of core WP_List_Table class
		require_once( trailingslashit( dirname( __FILE__ ) ) . 'lib/class-wp-logger-list-table.php' );

		$plugin_select = isset( $_POST['plugin-select'] ) ? $_POST['plugin-select'] : false;
		$log_id        = isset( $_POST['log-select'] ) ? $_POST['log-select'] : false;
		$search        = isset( $_POST['search'] ) ? $_POST['search'] : '';

		$logs          = $this->get_logs( $plugin_select );
		$entries       = $this->get_entries();
		$plugins       = $this->get_plugins();

		$logger_table  = new WP_Logger_List_Table( $entries );
		$logger_table->prepare_items();

		?>

		<div class="wrap">
			<h2><?php esc_html_e( 'Errors', 'wp-logger' ); ?></h2>

			<?php

				/*
				 * Check if email message was sent. If so, display a successful message on success or a
				 * failure message on failure.
				 */
				if( isset( $_POST['message_sent'] ) && $_POST['message_sent'] ) : ?>

				<div class="updated">
					<p><?php esc_html_e( 'Your message was sent successfully!', 'wp-logger' ); ?></p>
				</div>

			<?php elseif( isset( $_POST['message_sent'] ) && ! $_POST['message_sent'] ) : ?>

				<div class="error">
					<p><?php esc_html_e( 'Your message failed to send.', 'wp-logger' ); ?></p>
				</div>

			<?php endif; ?>

			<form method="post" id="logger-form" action="<?php echo admin_url( 'admin.php?page=wp_logger_errors' ); ?>">
				<?php wp_nonce_field( 'wp_logger_generate_report', 'wp_logger_form_nonce' ) ?>
				<div id="col-container">
					<div id="col-right">
						<div class="col-wrap">
							<?php

								// Uses WP_Logger_List_Table to display the logger entries.
								$logger_table->display();
							?>
						</div>
					</div>

					<div id="col-left">
						<h3><?php esc_html_e( 'Generate Error Report', 'wp-logger' ); ?></h3>

						<div class="form-field">
							<label for="search"><?php esc_html_e( 'Search', 'wp-logger' ); ?></label>
							<input name="search" id="search" type="text" value="<?php if( isset( $_POST['search'] ) ) { echo esc_attr( $_POST['search'] ); } ?>" size="40" aria-required="true">
						</div>

						<?php if ( ! empty( $plugins ) ) : ?>

							<div class="form-field">
								<p>
									<label for="plugin-select"><?php esc_html_e( 'Plugin', 'wp-logger' ); ?></label>
									<br />
									<select id="plugin-select" name="plugin-select">
										<option value=""><?php esc_html_e( 'All Plugins', 'wp-logger' ); ?></option>

										<?php
											foreach ( $plugins as $plugin ) {
												$temp_plugin_name = esc_attr( $plugin->name );
												echo "<option value='$temp_plugin_name'" . selected( $plugin->name, $plugin_select, false ) . ">$temp_plugin_name</option>";
											}
										?>
									</select>
									<br />
									<?php esc_html_e( 'Select a plugin to view errors for.', 'wp-logger' ); ?>
								</p>
							</div>

						<?php endif; ?>

						<?php if ( false != $logs && $logs->have_posts() ): ?>

							<div class="form-field">
								<p>
									<label for="log-select"><?php esc_html_e( 'Log', 'wp-logger' ); ?></label>
									<br />
									<select id="log-select" name="log-select">
										<option value=""><?php esc_html_e( 'All Logs', 'wp-logger' ); ?></option>

										<?php
											while ( $logs->have_posts() ) {
												$logs->the_post();
												$temp_log_id    = esc_attr( $post->ID );
												$temp_log_title = esc_attr( $post->post_title );
												echo "<option value='$temp_log_id'" . selected( $post->ID, $log_id, false ) . ">$temp_log_title</option>";
											}
										?>
									</select>
									<br />
									<?php esc_html_e( 'Select a log for this plugin.', 'wp-loggger' ); ?>
								</p>
							</div>

						<?php endif; ?>

						<button class="button button-primary"><?php esc_html_e( 'Generate Report', 'wp-logger' ); ?></button>

						<p>
							<hr>
						</p> <!-- Seperator -->

						<h3><?php esc_html_e( 'Email Results', 'wp-logger' ); ?></h3>

						<p><?php esc_html_e( 'You can easily email the current log report that you have generated by entering an email below and clicking send!', 'wp-logger' ); ?></p>

						<div class="form-field">
							<label for="email-results">Email</label>
							<input name="email-logs" value="<?php echo esc_attr( $this->get_plugin_email( $plugin_select ) ); ?>" id="email-results" type="text" size="40" aria-required="true">
							<p><?php esc_html_e( 'Enter an email above to email a log.', 'wp-logger' ); ?></p>
						</div>

						<p>
							<a id="send-logger-email" class="button"><?php esc_html_e( 'Send', 'wp-logger' ); ?></a>
						</p>

					</div>

				</div>
			</form>
		</div>

		<?php
	}
}

$wp_logger = new WP_Logger();