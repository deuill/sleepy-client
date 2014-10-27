<?php
/**
 * Gateway to initializing the Sleepy client framework.
 *
 * Sleepy, the lightweight web application framework.
 * Copyright 2012 - 2014, Alex Palaistras (http://deuill.org)
 *
 * @copyright	Copyright 2012 - 2014, Alex Palaistras (http://deuill.org)
 * @link		http://deuill.org/sleepy
 * @package		Sleepy.Core
 * @since		Sleepy 0.1.0
 */
 
/*
 * Define directories used system-wide.
 * 
 */

define('CLIENT_DIR', __DIR__.'/');
define('CORE_DIR',   CLIENT_DIR.'core/');
define('CONF_DIR',   CLIENT_DIR.'config/');
define('MODULE_DIR', CLIENT_DIR.'modules/');

/*
 * Include base files.
 * 
 */

require(CORE_DIR.'Sleepy.php');
require(CONF_DIR.'config.php');

Sleepy::load('Dispatcher', 'core');
Sleepy::load('Exceptions', 'core');
Sleepy::load('Inflector',  'core');
Sleepy::load('Mediator',   'core');
Sleepy::load('Router',     'core');

/*
 * Load global configuration.
 * 
 */

Sleepy::set($config);

/*
 * Prepare the environment.
 *
 */

// Connect to server.
Sleepy::connect(
	Sleepy::get('server', 'address'),
	Sleepy::get('server', 'port')
);

// Set up error reporting depending on the environment.
switch (Sleepy::get('client', 'environment')) {
	// Same as development below, only ignore fatal errors.
	case 'testing':
		Sleepy::set('client', 'show-errors', false);
	// Show all errors and warnings.
	case 'development':
		error_reporting(-1);
		ini_set('display_errors', 1);
		break;
	// Don't show PHP warnings and errors.
	case 'production':
		error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE ^ E_DEPRECATED ^ E_STRICT);
		ini_set('display_errors', 0);
		break;
	default:
		Exceptions::log("The 'client.environment' option has not been set correctly!");
		Exceptions::error();
}

// PHP errors are handled by the application.
set_error_handler('Exceptions::handler');

/* End of file init.php */
