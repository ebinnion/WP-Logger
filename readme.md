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

Because it is not guaranteed that users of this plugin will have WP Logger installed, you should check to see if the WP Logger plugin is installed before making any entries.

The function below simplifies the process of using WP Logger with your plugin. To customize this plugin to your needs, all you need to do is:

1. Prefix the function with something unique to your plugin.
2. Change the plugin slug that is called within `add_entry()`.

```php
<?php
function prefix_log_message(  $message = '', $log = 'message' ) {
    if( isset( $GLOBALS['wp_logger'] ) ) {
	    global $wp_logger;
	    return $wp_logger->add_entry( 'your-plugin-slug', $log, $message );
    } else {
	    return false;
    }
}
```

From there, the only thing you need to do is call `prefix_log_message( $message, $log );` where:

- `$message` is the messsage that you would like to log
- `$log` is the unique identifier for the type of log you would like to add the message to.
	- For example, you could create an `error` log or and `api-callback` log.