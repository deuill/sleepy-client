<?php
/**
 * Auth handles the generation and validation of secure, encrypted passwords.
 *
 * Sleepy, the lightweight web application framework.
 * Copyright 2012 - 2014, Alex Palaistras (http://deuill.org)
 *
 * @copyright	Copyright 2012 - 2014, Alex Palaistras (http://deuill.org)
 * @link		http://deuill.org/sleepy
 * @package		Sleepy.Modules
 * @since		Sleepy 0.2.0
 */
class Auth {
	/**
	 * Used as temporary store for the password data.
	 * 
	 * @var string
	 */
	private $password;

	/**
	 * Generate returns the encrypted version of the clear-text password
	 * supplied on object creation.
	 * 
	 * @return string The encrypted password.
	 */
	public function generate() {
		return Sleepy::call('Auth', 'GeneratePassword', $this->password);
	}

	/**
	 * Validate validates the clear-text password supplied on object
	 * creation with the hash passed as an argument.
	 * 
	 * @param  string $hash The encrypted password to match.
	 * @return bool         Whether or not we have a match.
	 */
	public function validate($hash) {
		return Sleepy::call('Auth', 'ValidatePassword', array($this->password, $hash));
	}

	/**
	 * Initialize object with supplied clear-text password.
	 * 
	 * @param string $password The clear-text password to process.
	 */
	public function __construct($password) {
		if (!is_string($password)) {
			Exceptions::warning("password was not a string", E_WARNING);
			return null;
		}

		$this->password = $password;
	}
}

// End of file Auth.php