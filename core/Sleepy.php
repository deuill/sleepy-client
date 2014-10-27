<?php
/**
 * Base client class.
 *
 * Sleepy is the base client class containing methods for connecting to the server,
 * sending method requests, loading modules and handling local configuration options.
 *
 * Sleepy, the lightweight web application framework.
 * Copyright 2012 - 2014, Alex Palaistras (http://deuill.org)
 *
 * @copyright	Copyright 2012 - 2014, Alex Palaistras (http://deuill.org)
 * @link		http://deuill.org/sleepy
 * @package		Sleepy.Core
 * @since		Sleepy 0.1.0
 */
class Sleepy {
	/**
	 * Server connection resource.
	 * 
	 * @var resource
	 */
	private static $connection;

	/**
	 * Multidimensional array containing local configuration options used throughout the client.
	 * 
	 * @var array
	 */
	private static $config = array();

	/**
	 * An array of loaded modules.
	 * 
	 * @var array
	 */
	private static $modules = array();

	/**
	 * An array of modules currently being loaded (to protect against circular references).
	 * 
	 * @var array
	 */
	private static $preload = array();

	/**
	 * Contains private data relating to our request cache.
	 * 
	 * @var array
	 */
	private static $cache = array();

	/**
	 * Load source file 'module' located in 'directory'.
	 * 
	 * Loads class in module of the same name in the selected directory (a subdirectory of
	 * the client directory for relative paths) and (optionally) return the instantiated
	 * object for that class. Attempting to return an instanstiated module more than once
	 * will simply return the initially loaded object.
	 * 
	 * @param  string $module    The name of the module and class to load.
	 * @param  string $directory The directory containing the module file located under
	 *                           the client directory (the one containing 'init.php').
	 * @param  bool   $create    Whether or not to instantiate the object.
	 * @return mixed             An instance of the requested module class, if create is
	 *                           set to 'true', otherwise a boolean true.
	 */
	public static function &load($module, $directory, $create = false) {
		if (isset(self::$preload[$module])) {
			return $t = null;
		}

		if (isset(self::$modules[$module])) {
			return self::$modules[$module];
		}

		self::$preload[$module] = true;
		$directory = (strpos($directory, '/') === 0) ? $directory.'/' : CLIENT_DIR.$directory.'/';
		$filename = $directory.$module.'.php';

		if (!include($filename)) {
			unset(self::$preload[$module]);
			Exceptions::log("File '{$module}.php' not found in '{$directory}'.");
			Exceptions::error(404);
		}

		if (!class_exists($module)) {
			unset(self::$preload[$module]);
			Exceptions::log("Class '{$module}' not found in '{$module}.php'.");
			Exceptions::error(404);
		}

		if (!empty(self::$cache['data']) && filemtime($filename) > self::$cache['data']['expire']) {
			self::$cache['data'] = array();
		}

		self::$modules[$module] = ($create) ? new $module() : true;
		unset(self::$preload[$module]);

		return self::$modules[$module];
	}

	/**
	 * Call RPC method on server and return the results, if any, for that request.
	 * 
	 * @param  mixed   $namespace  The namespace, or server module, to target this method request.
	 * @param  string  $method     The method to call on the server module.
	 * @param  mixed   $parameters The request parameters as expected by the server module.
	 * @param  boolean $cache      If true, the request is cached and called in batch next request.
	 * @return mixed               The response for this request.
	 */
	public static function call($namespace, $method = null, $parameters = null, $cache = false) {
		$authkey = self::get('client', 'authkey');
		if (is_array($namespace)) {
			$params = $namespace;
		} else {		
			$params = array(
				'module'	=> $namespace,
				'method'	=> $method,
				'authkey'	=> $authkey,
				'params'	=> (array) $parameters
			);

			$hash = sha1(serialize($params));
			if (!empty(self::$cache['data']['results'][$hash])) {
				return array_shift(self::$cache['data']['results'][$hash]);
			}
		}

		$id = uniqid(mt_rand());
		$request = array(
			'jsonrpc'	=> '2.0',
			'method'	=> (is_array($namespace)) ? 'Sleepy.CallMany' : 'Sleepy.Call',
			'params'	=> array($params),
			'id'		=> $id
		);

		$json_request = json_encode($request);
		$status = fwrite(self::$connection, $json_request, strlen($json_request));

		if ($status == false) {
			self::connect(self::get('server', 'address'), self::get('server', 'port'));
			fwrite(self::$connection, $json_request, strlen($json_request));
		}

		$json_response = '';
		while (!feof(self::$connection)) {
			$json_response .= fgets(self::$connection);
			$status = stream_get_meta_data(self::$connection);
			if ($status['unread_bytes'] <= 0) {
				break;
			}
		}

		$response = json_decode($json_response);

		if ($response === null) {
			Exceptions::log("The response was empty.");
			Exceptions::error();
		} else if ($response->id != $id) {
			Exceptions::log("The request ID was not equal to the response ID.");
			Exceptions::error();
		} else if (isset($response->error)) {
			Exceptions::log($response->error);
			Exceptions::error();
		} else if (isset($response->result) || property_exists($response, 'result')) {
			if ($cache && !empty(self::$cache['data'])) {
				self::$cache['data']['hashes'][] = $hash;
				self::$cache['data']['methods'][] = array(
					'module'  => $namespace,
					'method'  => $method,
					'authkey' => $authkey,
					'params'  => (array) $parameters
				);
			}

			return $response->result;
		} else {
			Exceptions::log("Unknown error with RPC.");
			Exceptions::error();
		}
	}

	/**
	 * Send file to server efficiently.
	 *
	 * This is mostly useful for sending large binary files without intermediate processing
	 * required by RPC methods. Files are sent via FTP to Sleepy's embedded FTP server.
	 * 
	 * @param  string $file The absolute path to the file.
	 * @param  string $name The filename to use for the remote file.
	 * @return bool         Whether or not the file was uploaded successfully.
	 */
	public static function send($file, $name) {
		$conn = ftp_connect(self::get('ftp', 'address'), self::get('ftp', 'port'));
		if (!ftp_login($conn, self::get('client', 'authkey'), '')) {
			return false;
		}

		ftp_pasv($conn, true);

		if (!ftp_put($conn, $name, $file, FTP_BINARY)) {
			return false;
		}

		ftp_close($conn);
		return true;
	}

	/**
	 * Get local configuration value of 'option' under 'section'.
	 * 
	 * @param  string $section The section under which the option is located.
	 * @param  string $option  The option name for the configuration value.
	 * @return mixed           The configuration value.
	 */
	public static function get($section, $option) {
		if (!is_string($section) || !is_string($option)) {
			Exceptions::warning("Section and/or option in 'get' are not strings.", E_WARNING);
			return null;
		} else if (!isset(self::$config[$section])) {
			Exceptions::warning("Section '{$section}' not found.");
			return null;
		} else if (!isset(self::$config[$section][$option])) {
			Exceptions::warning("Option '{$option}' not found.");
			return null;
		}

		return self::$config[$section][$option];
	}

	/**
	 * Set local configuration value(s).
	 *
	 * Multiple configuration values can be set by passing an array of sections containing
	 * an array of 'option' => 'value' mappings.
	 * 
	 * @param  string  $section The section name.
	 * @param  string  $option  The option name.
	 * @param  mixed   $value   The configuration value.
	 * @return boolean          Whether or not the value was set correctly.
	 */
	public static function set($section, $option = null, $value = null) {
		if (is_array($section)) {
			foreach ($section as $value) {
				if (!is_array($value)) {
					Exceptions::warning("Incorrect nesting of arrays in 'set'", E_WARNING);
					return false;
				}
			}

			$values = $section;
		} else {
			if (!is_string($section) || !is_string($option)) {
				Exceptions::warning("Section and/or option in 'set' are not strings.", E_WARNING);
				return false;	
			}

			$values[$section][$option] = $value;
		}

		self::$config = array_replace_recursive(self::$config, $values);

		return true;
	}

	/**
	 * Connect to server.
	 *
	 * Creates a persistent socket connection to the server running on 'address', with an
	 * optional 'port' part, if running remotely.
	 *
	 * @param  string $address The socket address. Form depends on chosen type.
	 * @param  string $port    The port on which the server is running on.
	 * @return void
	 */
	public static function connect($address, $port = null) {
		$port = (empty($port)) ? -1 : $port;
		self::$connection = @pfsockopen($address, $port);
		if (self::$connection === false) {
			$address = (isset($port)) ? $address.':'.$port : $address;
			Exceptions::log("Opening socket to '{$address}' failed.");
			Exceptions::error();
		}

		self::startCache();
	}

	/**
	 * Initialize the request cache.
	 *
	 * The request cache monitors which methods are called for a particular request, caches
	 * those method calls and, calls those methods in batch at the beginning of the request.
	 * Method calls then get their results from a local cache instead of requesting the data
	 * one-by-one from the server.
	 *
	 * The cache is invalidated if any source file loaded is found to have been modified since
	 * the cache was first generated.
	 *
	 * This method initializes the cache for the particular request, optionally filling
	 * the cache data with stored values from memcache.
	 *
	 * @return void
	 */
	private static function startCache() {
		if (class_exists('Memcache')) {
			$cache = new Memcache;
			$status = @$cache->pconnect(self::get('cache', 'address'), self::get('cache', 'port'));
			if ($status !== false) {
				$key = sha1($_SERVER['SERVER_NAME'].'/'.$_SERVER['REQUEST_URI']);
				$data = unserialize($cache->get($key));
				if ($data === false) {
					$data = array(
						'expire'  => time(),
						'hashes'  => array(),
						'methods' => array(),
						'results' => array()
					);
				} else {
					$results = self::call($data['methods']);
					foreach ($results as $i => $r) {
						$data['results'][$data['hashes'][$i]][] = $r;
					}
				}

				self::$cache = array(
					'server' => $cache,
					'hash'   => $key,
					'data'   => $data
				);

				// Passing a closure allows us to subscribe a private method.
				Mediator::subscribe('beforeRender', function() {
					self::stopCache();
				});
			}
		} else {
			Exceptions::warning('Memcache extension not loaded', E_NOTICE);
		}
	}

	/**
	 * Store or clear request cache.
	 *
	 * The request cache is cleared if it has been found to be invalidated during the course
	 * of execution, or stored (minus the method results) in the memcache server for use in
	 * a future request.
	 *
	 * @return void
	 */
	private static function stopCache() {
		if (empty(self::$cache['data'])) {
			self::$cache['server']->delete(self::$cache['hash']);
			return;
		}

		self::$cache['data']['results'] = array();
		self::$cache['server']->add(self::$cache['hash'], serialize(self::$cache['data']));
		unset(self::$cache['data']);
	}
}

/* End of file Sleepy.php */
