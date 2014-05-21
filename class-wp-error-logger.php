<?php

/**
 * This class extends the WP_Error class in order to allow developers to 
 */
class WP_Error_Logger extends WP_Error {

	/**
	 * Constructor - Sets up error message and logs the error data.
	 *
	 * If code parameter is empty then nothing will be done. It is possible to
	 * add multiple messages to the same code, but with other methods in the
	 * class.
	 *
	 * All parameters are optional, but if the code parameter is set, then the
	 * data parameter is optional.
	 *
	 * @param string|int $code Error code
	 * @param string $message Error message
	 * @param mixed $data Optional. Error data.
	 * @return WP_Error
	 */
	function __construct( $code = '', $message = '', $data = '' ) {
		parent::__construct( $code, $message, $data );

		// Call logger here.
	}
}