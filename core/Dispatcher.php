<?php
/**
 * Dispatcher handles requests, loading the appropriate controller and its models and calling
 * into the appropriate action for the request.
 *
 * Sleepy, the lightweight web application framework.
 * Copyright 2012 - 2014, Alex Palaistras (http://thoughtmonster.org)
 *
 * @copyright	Copyright 2012 - 2014, Alex Palaistras (http://thoughtmonster.org)
 * @link		http://thoughtmonster.org/sleepy
 * @package		Sleepy.Core
 * @since		Sleepy 0.1.0
 */
class Dispatcher {
	/**
	 * Base directory, i.e. the part between the hostname and the request parameters.
	 * 
	 * @var string
	 */
	private static $base;

	/**
	 * The URL part containing our request parameters, i.e. the part after the base directory.
	 * 
	 * @var string
	 */
	private static $url;

	/**
	 * The processed request parameters, containing the controller, action parameters and
	 * other values for this request.
	 * 
	 * @var array
	 */
	private static $params = array();

	/**
	 * Processes request, parsing arguments and loading the appropriate controller and its
	 * models. Calls into the selected action (if any) and renders the resulting view.
	 * 
	 * @return void
	 */
	public static function dispatch() {
		// Load basic Controller and Model classes.
		Sleepy::load('Model', 'core');
		Sleepy::load('Controller', 'core');

		Sleepy::load('AppModel', APP_DIR.'model');
		Sleepy::load('AppController', APP_DIR.'controller');

		// Initialize request parameters.
		self::$params = self::params();

		// Load controller and related classes.
		$name = Inflector::camelize(self::$params['controller']).'Controller';
		if (file_exists(APP_DIR.'controller/'.$name.'.php')) {
			Sleepy::load($name, APP_DIR.'controller');
		} else {
			$name = 'AppController';
		}

		$controller = new $name;

		// Load models into controller.
		self::loadModels($controller);

		// Register default events for the controller.
		Mediator::subscribe('beforeCall',   array($controller, 'beforeCall'));
		Mediator::subscribe('afterCall',    array($controller, 'afterCall'));
		Mediator::subscribe('beforeRender', array($controller, 'beforeRender'));
		Mediator::subscribe('afterRender',  array($controller, 'afterRender'));

		// Call into the selected action of the selected controller.
		$action = Inflector::camelize(self::$params['action']);
		self::call($controller, $action, self::$params['args']);

		// Render the template if it hasn't been already.
		$controller->render();

		// Show all generated warnings at the end of the page.
		Exceptions::show();
	}

	/**
	 * Verifies and calls object method and surrounding event calls.
	 *
	 * Call verifies that the object function exists and is visible according to our naming
	 * rules (methods that are declared public and begin with an uppercase letter are visible
	 * to the outside world). It also publishes the 'beforeCall' and 'afterCall' event to all
	 * subscribers.
	 *
	 * @param  object $object The Controller object containing the method to be called.
	 * @param  string $method The method name to be called. Method names are case-sensitive.
	 * @param  array  $args   An array of arguments, as expected by call_user_func_array.
	 * @return mixed          The return value of the called function.
	 */
	public static function call($object, $method, $args) {
		Mediator::publish('beforeCall');

		// Check that method exists in object, in a case-sensitive manner.
		if (in_array($method, get_class_methods($object))) {
			$result = call_user_func_array(array($object, $method), $args);
		}

		Mediator::publish('afterCall');

		return (isset($result)) ? $result : null;
	}

	/**
	 * Parse 'models' value in 'controller' and load model classes into the controller, making
	 * them available under '$controller->Model'.
	 *
	 * @param  object $controller The controller on which we will parse and attach the models.
	 * @return void
	 */
	public static function loadModels($controller) {
		$models = array();

		if (is_array($controller->models) && empty($controller->models)) {
			$parent = get_parent_class($controller);
			$t = new $parent;
			$controller->models = $t->models;
		}

		if ($controller->models === true) {
			$name = Inflector::title(self::$params['controller']);
			$models[$name] = $name;

			if (!file_exists(APP_DIR.'model/'.$name.'.php')) {
				return;
			}
		} else if (is_array($controller->models)) {
			foreach ($controller->models as $model) {
				$models[$model] = $model;
			}
		} else {
			return;
		}

		foreach ($models as $model) {
			// Use AppModel and inherit default functionality if concrete model doesn't exist.
			if (file_exists(APP_DIR.'model/'.$model.'.php')) {
				Sleepy::load($model, APP_DIR.'model');
				$instance = new $model;
			} else {
				$instance = AppModel::generateModel($model);
			}

			$alias = (isset($instance->name)) ? $instance->name : $model;
			$controller->$alias = $instance;

			// Load core modules for models.
			if (is_array($controller->$alias->modules)) {
				foreach ($controller->$alias->modules as $module) {
					Sleepy::load($module, 'modules');
				}
			}
		}
	}

	/**
	 * Parse URL and extract the base directory.
	 * 
	 * @return string The base directory, with a trailing slash.
	 */
	public static function base() {
		$uri  = explode('/', $_SERVER['REQUEST_URI']);
		$self = explode('/', dirname($_SERVER['PHP_SELF']));

		$base = implode('/', array_intersect($uri, $self));
		if (substr($base, -1) !== '/') {
			$base = $base.'/';
		}

		return $base;
	}

	/**
	 * Parse URL and extract request parameters.
	 * 
	 * @param  boolean $rewrite Apply routing table rewrites.
	 * @return array            The processed request parameters.
	 */
	public static function params($rewrite = true) {
		// Parse query string.
		self::$url = self::url();

		// Parse parameters from query string.
		$params = array_values(array_filter(explode('/', self::$url, 3)));
		$params = array(
			'controller' => (isset($params[0])) ? $params[0] : 'main',
			'action'	 => (isset($params[1])) ? $params[1] : 'index',
			'args'		 => (isset($params[2])) ? explode('/', $params[2]) : array()
		);

		// Override request parameters with ones matched from the routing tables.
		if ($rewrite) {
			$match = Router::match(self::$url);
			if (!empty($match)) {
				$match = array_values($match);
				$params = array(
					'controller' => (isset($match[0])) ? $match[0] : $params['controller'],
					'action'	 => (isset($match[1])) ? $match[1] : $params['action'],
					'args'		 => (isset($match[2])) ? $match[2] : $params['args']
				);
			}
		}

		// Fix names to conform to our naming standards.
		$params['controller'] = Inflector::normalize($params['controller']);
		$params['action'] = Inflector::normalize($params['action']);

		return $params;
	}

	/**
	 * Parse URL and extract the request part.
	 * 
	 * @return string The request part of the URL (the part after the base directory) or a
	 *                single slash ('/') if the request is empty.
	 */
	public static function url() {
		if (!empty($_SERVER['PATH_INFO'])) {
			return $_SERVER['PATH_INFO'];
		} else if (isset($_SERVER['REQUEST_URI'])) {
			$uri = $_SERVER['REQUEST_URI'];
		} else if (isset($_SERVER['PHP_SELF']) && isset($_SERVER['SCRIPT_NAME'])) {
			$uri = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['PHP_SELF']);
		}

		// Find the base directory.
		self::$base = self::base();

		if (strlen(self::$base) > 0 && strpos($uri, self::$base) === 0) {
			$uri = substr($uri, strlen(self::$base));
		}

		if (strpos($uri, '?') !== false) {
			list($uri) = explode('?', $uri, 2);
		}

		if (empty($uri) || $uri == '/' || $uri == '//') {
			return '/';
		}

		return $uri;
	}
}

/* End of file Dispatcher.php */