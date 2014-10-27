<?php
/**
 * Router handles connecting URLs to arbitrary controllers and actions (i.e. rewriting)
 * and redirecting to URLs.
 *
 * Sleepy, the lightweight web application framework.
 * Copyright 2012 - 2014, Alex Palaistras (http://deuill.org)
 *
 * @copyright	Copyright 2012 - 2014, Alex Palaistras (http://deuill.org)
 * @link		http://deuill.org/sleepy
 * @package		Sleepy.Core
 * @since		Sleepy 0.1.0
 */
class Router {
	/**
	 * The routing table of URLs to controllers/actions/parameters.
	 * 
	 * @var array
	 */
	private static $routes = array();

	/**
	 * Add URL 'path' to routing table, connecting it to the 'params' array.
	 *
	 * An example call would be:
	 *
	 * 	Router::add('/', array('main', 'index'));
	 *
	 * Which connects the root directory ('/') to the 'MainController' class
	 * method 'index()'.
	 * 
	 * Routes can contain wildcards, as in the following example:
	 *
	 * 	Router::add('/modules/*', array('module', 'select', array('all')));
	 *
	 * Which, when visiting a URL like the following: 'http://example.com/modules/simple/6'
	 * will call 'ModuleController::select('all')'.
	 *
	 * Routes may also contain parameters, which are matched to the URL and passed to
	 * their corresponding parameter bindings:
	 *
	 * 	Router::add('/modules/:name/:id', array('module', 'select', array(':name', ':id')));
	 *
	 * Which, for a URL like the one used in the above example will call
	 * 'ModuleController::select('simple', '6')'.
	 *
	 * @param string $path   The URL path to connect.
	 * @param array  $params An array in the form expected by 'Dispatcher::dispach()'.
	 */
	public static function add($path, $params) {
		if (!is_string($path)) {
			Exceptions::log("Routing tables are not set up correctly.");
			Exceptions::error();
		}

		self::$routes[$path] = $params;
	}

	/**
	 * Search for URL in routing table and return params array if a match is found.
	 *
	 * URLs are matched to paths according to the process outlined in the Router::add
	 * method. Routes added later are preferred to those added earler.
	 * 
	 * @param  string $url The URL to match against.
	 * @return mixed       The params array for this route, or null if none matched.
	 */
	public static function match($url) {
		// Remove trailing slash from URL, if any exists.
		if ($url !== '/' && substr($url, -1) === '/') {
			$url = substr($url, 0, -1);
		}

		$url = preg_split('/\//', $url, null, PREG_SPLIT_NO_EMPTY);

		// Start by processing last routes first.
		foreach (array_reverse(self::$routes) as $path => $params) {
			$path = preg_split('/\//', $path, null, PREG_SPLIT_NO_EMPTY);
			// If path is exactly the same as the requested URL, or the path consists
			// of a single wildcard character, return the params for this path.
			if ($url === $path) {
				return $params;
			} else if (count($path) === 1 && $path[0] === '*') {
				return $params;
			}

			// Go through each part of the URL and look at the corresponding part of the route
			// path. If it an exact match, a wildcard or a parameter, move on to the next part
			// until either the URL or the path is exhausted.
			$match = false;
			$replacements = array();
			foreach ($url as $i => $part) {
				if (isset($path[$i])) {
					$wildcard = ($path[$i] === '*') ? true : false;
					if ($wildcard || $part === $path[$i]) {
						$match = true;
						continue;
					} else if (preg_match("/:[a-zA-Z0-9]+/", $path[$i])) {
						$replacements[$path[$i]] = $part;
						$match = true;
						continue;
					}

					$match = false;
					break;
				}

				$match = false;
				break;
			}

			if ($match) {
				// Find the replacement for the route params array value, returning it if found,
				// or returning null and unsetting it if not.
				$filter = function($value, $replacements) {
					foreach ($replacements as $rk => $rv) {
						if ($value === $rk) {
							return $rv;
						}
					}

					return null;
				};

				foreach ($params as $pk => $pv) {
					if (is_array($pv)) {
						foreach ($pv as $sk => $sv) {
							if (preg_match("/:[a-zA-Z0-9]+/", $sv)) {
								$params[$pk][$sk] = $filter($sv, $replacements);
								if (!isset($params[$pk][$sk])) {
									unset($params[$pk][$sk]);
								}
							}
						}
					} else {
						if (preg_match("/:[a-zA-Z0-9]+/", $pv)) {
							$params[$pk] = $filter($pv, $replacements);
							if (!isset($params[$pk])) {
								unset($params[$pk]);
							}
						}
					}
				}

				return $params;
			}
		}

		return null;
	}

	/**
	 * Redirect to URL, with an optional status code (default is a 302 redirect).
	 * 
	 * A relative URL (a URL with no http:// or https:// URI identifier) will
	 * redirect to the relevant local URL.
	 * 
	 * @param  string  $url  The URL for the redirection.
	 * @param  integer $code The HTTP status code for the redirect,
	 * @return void
	 */
	public static function redirect($url, $code = 302) {
		if (!preg_match('#^https?://#i', $url)) {
			// Remove leading slash, if any exists.
			if (substr($url, 0, 1) === '/') {
				$url = substr($url, 1);
			}

			$url = Dispatcher::base().$url;
		}

		header('Location: '.$url, true, $code);
		exit;
	}

}

/* End of file Router.php */