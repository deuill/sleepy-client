<?php
/**
 * Global configuration file for the Sleepy client.
 *
 * Sleepy, the lightweight web application framework.
 * Copyright 2012 - 2014, Alex Palaistras (http://thoughtmonster.org)
 *
 * @copyright	Copyright 2012 - 2014, Alex Palaistras (http://thoughtmonster.org)
 * @link		http://thoughtmonster.org/sleepy
 * @package		Sleepy.Core
 * @since		Sleepy 0.1.0
 */

/*
 * Server-related configuration options.
 *
 * 'address'	Address, remote or local, of server socket, with optional type prefix.
 * 'port'		Port on which the server is listening on, if running remotely.
 *
 */

$config['server']['address'] = '127.0.0.1';
$config['server']['port']    = '6006';

/*
 * FTP server-related configuration options.
 *
 * 'address'	Address on which the server's FTP service is listening on.
 * 'port'		Port on which the server's FTP service is listening on.
 *
 */

$config['ftp']['address'] = '127.0.0.1';
$config['ftp']['port']    = '6008';

/*
 * Memcache-related configuration options.
 *
 * 'address'	Address on which the memcache server is listening on.
 * 'port'		Port on which the memcache server is listening on.
 *
 */

$config['cache']['address'] = '127.0.0.1';
$config['cache']['port']    = '11211';

/* End of file config.php */