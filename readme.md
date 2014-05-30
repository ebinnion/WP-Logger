# WP Logger

This plugin provides an API for WordPress plugin developers to log actions/errors/messages in an effort to make debugging more simple.

## Use Cases

The following are use cases for this plugin.

### Assist in Debugging Issues on a User's WordPress Installation

Developers that take advantage of the WP Logger API now have a tool to assist in the communication between non-technical users and developers.

Using the WP Logger API, a developer can log anything from an API call result to anytime an error or notice occurs. This can be of much use when attempting to identify bugs on different servers where a developer does not have direct access to code and server logs.

Once a user installs the WP Logger plugin, the WP Logger API will begin to log any message that the developer has programmed.

Then, the user can email these logs directly to the developer in as little as 2 actions (Selecting which plugin and hitting send).

## For Plugin Developers

### Registering Developer email
WP Logger allows plugin developers to register an email that users can then send logs to. This serves to simplify the process of getting information from users. Below is an example of registering an email:

```php
add_filter( 'wp_logger_author_email', 'add_logger_plugin_email' );
function add_logger_plugin_email( $emails ) {
	$emails['wp-logger-test'] = 'ericbinnion@gmail.com';
	return $emails;
}
```

### Retrieving WP Logger Version Number
You can retrieve the current WP Logger version number, and check if WP Logger is installed, by using the following:

```php
$wp_logger_version = apply_filters( 'wp_logger_version', false );

// Check if WP Logger is installed
if( $wp_logger_version ) {
	// WP Logger is installed
}
```

### There are two ways that developers may log messages with the WP Logger plugin. The simpler, and preferred, method would be to use the WordPress Hook API.

#### Using the WordPress Hook API
WP Logger hooks the `WP_Logger:add_entry()` function onto the `wp_logger_add` action. This means that you only need to add this one line of code wherever you would like to log an entry:

```php
do_action( 'wp_logger_add', $plugin_slug, $log_name, $message, $severity );
```

#### Using the Global WP_Logger instantiation
Because it is not guaranteed that users of this plugin will have WP Logger installed, you should check to see if the WP Logger plugin is installed before making any entries.

The function below simplifies the process of using WP Logger with your plugin. To customize this plugin to your needs, all you need to do is:

1. Prefix the function with something unique to your plugin.
2. Change the plugin slug that is called within `add_entry()`.

```php
<?php
function prefix_log_message(  $message = '', $log = 'message', $severity = 1 ) {
	if( isset( $GLOBALS['wp_logger'] ) ) {
		return $GLOBALS['wp_logger']->add_entry( 'wp-logger-test', $log, $message );
	} else {
		return false;
	}
}
```

From there, the only thing you need to do is call `prefix_log_message( $message, $log, $severity );` where:

- `$message` is the messsage that you would like to log.
- `$log` is the unique identifier for the type of log you would like to add the message to.
	- For example, you could create an `error` log or and `api-callback` log.
- `$severity` is an integer that describes what priority this entry should have.

Here is a class based example:

```php
class Sample {
	function __construct() {
		add_action( 'init', 'log_sample_message' );
	}

	function prefix_log_message(  $message = '', $log = 'message', $severity = 1 ) {
		if( isset( $GLOBALS['wp_logger'] ) ) {
			return $GLOBALS['wp_logger']->add_entry( 'wp-logger-test', $log, $message );
		} else {
			return false;
		}
	}

	function log_sample_message() {

		/*
		 * Logs an entry to plugin `your-plugin-slug` with message of `I am a message`
		 * in the errors log with a severity of 10
		 */
		$this->prefix_log_message( 'I am a message', 'errors', 10 );
	}
}
```
