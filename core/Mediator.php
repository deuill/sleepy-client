<?php
/**
 * A simple Publish - Subscribe event system.
 *
 * Mediator implements a simplified version of the Mediator pattern for indirect communication
 * between classes. It allows classes to participate in an event system by subscribing
 * callables under a name and publishing data to all subscribers for that name.
 *
 * Sleepy, the lightweight web application framework.
 * Copyright 2012 - 2014, Alex Palaistras (http://deuill.org)
 *
 * @copyright	Copyright 2012 - 2014, Alex Palaistras (http://deuill.org)
 * @link		http://deuill.org/sleepy
 * @package		Sleepy.Core
 * @since		Sleepy 0.1.0
 */
class Mediator {
	/**
	 * An array of active subscriptions.
	 * 
	 * @var array
	 */
	private static $sub = array();

	/**
	 * Bind 'callable' to 'name', making it available as a target when
	 * publishing under that name.
	 * 
	 * @param  string   $name     Name of the subscription the callable will be available under.
	 * @param  callable $callable The callable to subscribe.
	 * @return boolean            Whether or not the subscription was created successfully.
	 */
	public static function subscribe($name, $callable) {
		if (is_string($callable)) {
			if (isset(self::$sub[$name][$callable])) {
				Exceptions::warning("Subscription for function in '{$name}' already exists");
				return false;
			} else if (!is_callable($callable)) {
				Exceptions::warning("Cannot subscribe non-existing function", E_WARNING);
				return false;
			}

			self::$sub[$name][$callable] = $callable;
		} else if (is_array($callable) && is_object($callable[0])) {
			if (isset(self::$sub[$name][get_class($callable[0]).'::'.$callable[1]])) {
				Exceptions::warning("Subscription for method in '{$name}' already exists");
				return false;
			} else if (!method_exists($callable[0], $callable[1])) {
				Exceptions::warning("Cannot subscribe non-existing method", E_WARNING);
				return false;
			}

			self::$sub[$name][get_class($callable[0]).'::'.$callable[1]] = $callable;
		} else if (is_callable($callable)) {
			self::$sub[$name][] = $callable;
		} else {
			Exceptions::warning("Invalid callable type in subscription", E_WARNING);
			return false;
		}

		return true;
	}

	/**
	 * Unsubscribe all callables under 'name'.
	 * 
	 * @param  string  $name The name of the subscription.
	 * @return boolean       Whether or not we unsuscribed successfully.
	 */
	public static function unsubscribe($name) {
		if (!is_string($name)) {
			Exceptions::warning("Cannot unsubscribe, name is not a string", E_WARNING);
			return false;
		} else if (!isset(self::$sub[$name])) {
			Exceptions::warning("Subscription for '{$name}' does not exist");
			return false;
		}

		unset(self::$sub[$name]);
		return true;
	}

	/**
	 * Publish data 'args' to all subscribed callables for 'name'.
	 * 
	 * @param  string  $name The name of the subscription.
	 * @param  mixed   $args The data to pass as arguments to the callables.
	 * @return boolean       Whether or not the data was published successfully.
	 */
	public static function publish($name, $args = null) {
		if (!is_string($name)) {
			Exceptions::warning("Cannot publish, name is not a string", E_WARNING);
			return false;
		} else if (!isset(self::$sub[$name])) {
			Exceptions::warning("Subscription for '{$name}' does not exist");
			return false;
		}

		$params = array_slice(func_get_args(), 1);

		foreach (self::$sub[$name] as $function) {
			call_user_func_array($function, $params);
		}

		return true;
	}
}

/* End of file Mediator.php. */