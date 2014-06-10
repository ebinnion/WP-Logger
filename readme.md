# WP Logger

This plugin provides an API for WordPress plugin developers to log actions/errors/messages in an effort to make debugging more simple.

## Use Cases

The following are use cases for this plugin.

### Assist in Debugging Issues on a User's WordPress Installation

Developers that take advantage of the WP Logger API now have a tool to assist in the communication between non-technical users and developers.

Using the WP Logger API, a developer can log anything from an API call result to anytime an error or notice occurs. This can be of much use when attempting to identify bugs on different servers where a developer does not have direct access to code and server logs.

Once a user installs the WP Logger plugin, the WP Logger API will begin to log any message that the developer has programmed.

Then, the user can email these logs directly to the developer in as little as 2 actions (Selecting which plugin and hitting send).

## For Website Owners and Administrators

WP Logger is a plugin that, when installed, allows developers to log messages that will assist them in debugging issues with plugins on your site.

Once WP Logger is installed, developers can log custom messsages that will allow them to more quickly diagnose issues with your website. For security, this data is stored on your website and it is only available to developers after *you* generate a report and send it to the developer by clicking a button.

Using this plugin when needed should result in quicker issue resolution and less work on your part.

## For Plugin Developers

The following are examples for plugin developers.

### Registering Developer Email

WP Logger allows plugin developers to register an email that users can then send logs to. This serves to simplify the process of getting information from users. Below is an example of registering an email:

```php
add_filter( 'wp_logger_author_email' . $plugin_name , 'add_logger_plugin_email' );
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

### Logging Errors/Messages/Etc.

WP Logger hooks the `WP_Logger::add_entry()` method onto the `wp_logger_add` action. This means that you only need to add this one line of code wherever you would like to log an entry:

```php
do_action( 'wp_logger_add', $plugin_slug, $log_name, $message, $severity );
```

### Purge Plugin Messages

Because each log entry is stored as a comment, it is advisable that your plugin only log entries as necessary and purge entries when no longer needed.

This functionality could potentially be implemented on the `register_deactivation_hook` of your plugin or after a user clicks a purge button in your plugin's settings page.

```php
do_action( 'wp_logger_purge', $plugin_slug );
```

### Increasing/Decreasing Log Limit

To minimize the performance impact of logging entries, all plugins are limited to adding 100 log entries by default. Each time a log entry is added above and beyond a plugin's limit, the oldest entry will be deleted.

Here is an example of how to modify the limit for a plugin.

```php
add_filter( 'wp_logger_limit_' . $plugin_slug, 'increase_logger_limit' );
function increase_logger_limit( $limit ) {
	return 100;
}
```

### Sample Plugin

Below is a sample plugin that demonstrates how to log messages, register a developer email, purge logs, and check whether 
WP Logger is installed by getting the current WP Logger version number.

```php
<?php
/**
 * Plugin Name: WP Logger Test
 * Plugin URI: http://automattic.com
 * Description: Acts as a test driver for the WP Logger plugin.
 * Version: 0.1
 * Author: Eric Binnion
 * Author URI: http://manofhustle.com
 * License: GPLv2 or later
 */

add_action( 'init', 'wp_logger_test_add' );
function wp_logger_test_add() {

	$wp_logger_version = apply_filters( 'wp_logger_version', false );

	if( false == $wp_logger_version ) {
		// WP Logger is not installed
	}

	// Used to log 'Hello World!' to a log named message with a severity of 6.
	do_action( 'wp_logger_add', 'wp-logger-test', 'message', 'Hello World!', 6 );
}

add_filter( 'wp_logger_author_email', 'wp_logger_add_test_email' );
function wp_logger_add_test_email( $emails ) {

	// Registers `developer@gmail.com` for the `wp-logger-test` plugin.
	$emails['wp-logger-test'] = 'developer@gmail.com';
	return $emails;
}

register_deactivation_hook( __FILE__, 'wp_logger_purge_logs' );
function wp_logger_purge_logs() {

	// Delete all log entries for the `wp-logger-test` plugin.
	do_action( 'wp_logger_purge', 'wp-logger-test' );
}

add_filter( 'wp_logger_limit_wp-logger-test', 'increase_logger_test_limit' );
function increase_logger_test_limit( $limit ) {
	return 101;
}
```
