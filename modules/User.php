<?php
/**
 * User handles the management of remote users used by the server part of the Sleepy framework.
 *
 * Sleepy, the lightweight web application framework.
 * Copyright 2012 - 2014, Alex Palaistras (http://thoughtmonster.org)
 *
 * @copyright	Copyright 2012 - 2014, Alex Palaistras (http://thoughtmonster.org)
 * @link		http://thoughtmonster.org/sleepy
 * @package		Sleepy.Modules
 * @since		Sleepy 0.1.0
 */
class User {
	/**
	 * Data contains metadata such as the user ID, auth key etc. The interface is private
	 * and unstable, but is guaranteed to contain the 'id' and 'authkey' variables at least.
	 * 
	 * @var array
	 */
	private $data;

	/**
	 * Create user in remote server. Returns a User object.
	 * 
	 * @return object The User object.
	 */
	public function save() {
		$this->data = (object) array_change_key_case(
			(array) Sleepy::call('User', 'Save', $this->data)
		);

		return $this;
	}

	/**
	 * Remove user from remote server.
	 * 
	 * @return bool Whether or not the user was removed successfully.
	 */
	public function remove() {
		return Sleepy::call('User', 'Remove', $this->data->id);
	}

	/**
	 * Set or retrieve option for user.
	 * 
	 * Retrieves the option value (if any) if 'value' is empty, otherwise it sets the
	 * value for the corresponding option. You may set multiple options by passing an
	 * array of 'module -> section -> option -> value' pairs.
	 * 
	 * @param  mixed  $module  The module name, or an array of options, as above.
	 * @param  string $section The section name.
	 * @param  string $option  The option name.
	 * @param  mixed  $value   The optional value, if setting an option.
	 * @return mixed           The option value, if retrieving, or a bool if setting.
	 */
	public function options($module, $section = null, $option = null, $value = null) {
		$data['id'] = (int) $this->data->id;

		if (is_array($module)) {
			$data['data'] = $module;
		} else if ($value != null) {
			$data['data'][$module][$section][$option] = $value;
		}

		if (isset($data['data'])) {
			return Sleepy::call('User', 'SetOption', $data);
		}

		$data = array(
			'id'	  => (int) $this->data->id,
			'module'  => $module,
			'section' => $section,
			'option'  => $option
		);

		return Sleepy::call('User', 'GetOption', $data);
	}

	/**
	 * Initialize user, optionally retrieving user data from server by ID.
	 * 
	 * @param int $id User ID to retrieve from.
	 */
	public function __construct($id = null) {
		if (is_numeric($id)) {
			$this->data = $this->get($id);
		} else if (preg_match('/^[0-9a-f]{40}$/i', $id) === 1) {
			$this->data = $this->auth($id);
		}
	}

	/**
	 * Returns variables from the private 'data' array.
	 * 
	 * A call of:
	 *
	 * 	$user = new User(1);
	 * 	echo $user->authkey;
	 *
	 * Will return the auth key for user with ID 1.
	 *
	 * @param  string $name The name of the variable.
	 * @return mixed        Whatever type the variable was stored as.
	 */
	public function __get($name) {
		if (!isset($this->data->$name)) {
			Exceptions::warning("property '{$name}' does not exist in user");
			return null;
		}

		return $this->data->$name;
	}

	/**
	 * Checks for variable existance when checking with 'isset()'.
	 * 
	 * @param  string  $name The name of the variable.
	 * @return boolean       Whether or not the variable is set.
	 */
	public function __isset($name) {
		return isset($this->data->$name);
	}

	/**
	 * Retrieve user from remote server by ID.
	 * 
	 * @param  int $id The user's ID.
	 * @return array   The user data.
	 */
	private function get($id) {
		return (object) array_change_key_case(
			(array) Sleepy::call('User', 'Get', (int) $id)
		);
	}

	/**
	 * Retrieve user from remote server by auth key.
	 * 
	 * @param  string $authkey The user's auth key.
	 * @return array           The user data.
	 */
	private function auth($authkey) {
		return (object) array_change_key_case(
			(array) Sleepy::call('User', 'Auth', $authkey)
		);
	}

	/**
	 * Remove option for user.
	 * 
	 * Options are removed recursively down to the level specified, so setting 'module' and
	 * 'section' will remove all options for that section, setting 'module' will remove all
	 * sections and their options, and specifying nothing will delete all options for user.
	 * 
	 * @param  string $module  The module name.
	 * @param  string $section The section name.
	 * @param  string $option  The option name.
	 * @return bool            Whether or not the operation completed successfully.
	 */
	private function _unset($module = null, $section = null, $option = null) {
		$data = array(
			'id'	  => (int) $this->data->id,
			'module'  => $module,
			'section' => $section,
			'option'  => $option
		);

		return Sleepy::call('User', 'DeleteOption', $data);
	}

	/**
	 * This is a wrapper function around '_unset()', since 'unset' is a reserved name in PHP.
	 * Read the documentation on _unset for usage information.
	 * 
	 * @param  string $name The function name.
	 * @param  array  $args An array of arguments passed to the function.
	 * @return mixed        Whatever the function called returns.
	 */
	public function __call($name, $args) {
		switch ($name) {
		case 'unset':
			return call_user_func_array(array($this, '_unset'), $args);
		}
	}
}

/* End of file User.php */