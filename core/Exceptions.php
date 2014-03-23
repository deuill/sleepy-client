<?php
/**
 * Contains methods for handling and displaying warning and error messages, as well as some
 * helper methods for logging and setting HTTP response codes.
 *
 * Sleepy, the lightweight web application framework.
 * Copyright 2012 - 2014, Alex Palaistras (http://thoughtmonster.org)
 *
 * @copyright	Copyright 2012 - 2014, Alex Palaistras (http://thoughtmonster.org)
 * @link		http://thoughtmonster.org/sleepy
 * @package		Sleepy.Core
 * @since		Sleepy 0.1.0
 */
class Exceptions {
	/**
	 * Holds warning messages in a buffer, so that they may be displayed at
	 * the end of the page.
	 * 
	 * @var string
	 */
	private static $buffer;

	/**
	 * Shows warning messages to screen, usually called at the end of the
	 * request.
	 * 
	 * @return void
	 */
	public static function show() {
		echo self::$buffer;
	}

	/**
	 * Display a fatal error, clearing any previous output and halting
	 * execution of the page.
	 * 
	 * @param  integer $code    The HTTP status code returned by the page.
	 * @param  string  $message The error message to be displayed to the user.
	 * @param  string  $heading The page title and header.
	 * @return void
	 */
	public static function error($code = 500, $message = null, $heading = null) {
		if (Sleepy::get('client', 'show-errors') === false) {
			return;
		}

		// Do not output anything that happened before this error.
		ob_end_clean();

		// Set response headers.
		$resp = self::code($code);

		include(CLIENT_DIR.'template/error.php');
		exit;
	}

	/**
	 * Display an inline warning message.
	 * 
	 * @param  integer $severity One of the predefined PHP error constants.
	 * @param  string  $message  The error message to be displayed.
	 * @param  string  $path     The path of the file that raised the warning.
	 *                           If NULL, we attempt to generate the information
	 *                           using a `debug_backtrace`.
	 * @param  integer $line     The line number that raised the warning.
	 * @return void
	 */
	public static function warning($message, $severity = E_NOTICE, $path = null, $line = null) {
		$levels = array(
			E_ERROR				=>	'Error',
			E_WARNING			=>	'Warning',
			E_PARSE				=>	'Parsing Error',
			E_NOTICE			=>	'Notice',
			E_CORE_ERROR		=>	'Core Error',
			E_CORE_WARNING		=>	'Core Warning',
			E_COMPILE_ERROR		=>	'Compile Error',
			E_COMPILE_WARNING	=>	'Compile Warning',
			E_USER_ERROR		=>	'User Error',
			E_USER_WARNING		=>	'User Warning',
			E_USER_NOTICE		=>	'User Notice',
			E_STRICT			=>	'Runtime Notice',
			E_RECOVERABLE_ERROR	=>	'Recoverable Error'
		);

		if ($path === null && $line === null) {
			$backtrace = reset(debug_backtrace());
			$path = $backtrace['file'];
			$line = $backtrace['line'];
		}

		$severity = (!isset($levels[$severity])) ? $severity : $levels[$severity];

		// Only return the last part of the path, for security reasons.
		$path = str_replace("\\", "/", $path);
		if (strpos($path, '/') !== false) {
			$p = explode('/', $path);
			$path = $p[count($p) - 2].'/'.end($p);
		}

		ob_start();

		include(CLIENT_DIR.'template/warning.php');

		self::$buffer .= ob_get_contents();
		ob_end_clean();
	}

	/**
	 * Log message to file in the application's 'tmp' directory.
	 * 
	 * @param  string  $message  The message which will be written.
	 * @param  string  $path     The path of the file that raised the warning.
	 * @param  integer $line     The line number that raised the warning.
	 * @return void
	 */
	public static function log($message, $path = null, $line = null) {
		if ($path === null && $line === null) {
			$backtrace = reset(debug_backtrace());
			$path = $backtrace['file'];
			$line = $backtrace['line'];
		}

		// Only return the last part of the path, for security reasons.
		$path = str_replace("\\", "/", $path);
		if (strpos($path, '/') !== false) {
			$p = explode('/', $path);
			$path = $p[count($p) - 2].'/'.end($p);
		}

		$filepath = APP_DIR.'tmp/errors-'.date('d-m-Y').'.log';
		$message = '['.date('d/m/Y H:i:s').'] ['.$path.':'.$line.'] '.$message."\n";

		// Log files larger than 512kB are compressed and set aside.
		if (file_exists($filepath) && filesize($filepath) > 524288) {
			$gzdata = gzencode(file_get_contents($filepath));
			file_put_contents($filepath.'.gz', $gzdata);
			unlink($filepath);
		}

		file_put_contents($filepath, $message, FILE_APPEND);
	}

	/**
	 * Default error handler for PHP errors. Shows an inline warning and logs
	 * error to file.
	 * 
	 * @param  integer $severity One of the predefined PHP error constants.
	 * @param  string  $message  The error message to be displayed.
	 * @param  string  $path     The path of the file that raised the warning.
	 * @param  integer $line     The line number that raised the warning.
	 * @return void
	 */
	public static function handler($severity, $message, $path, $line) {
		// Do not bother handling "strict" notices.
		if ($severity == E_STRICT) {
			return;
		}

		// Should we display this error?
		if (($severity & error_reporting()) == $severity) {
			self::warning($message, $severity, $path, $line);
		}

		self::log($message, $path, $line);
	}

	/**
	 * Set HTTP response code.
	 * 
	 * @param  integer $code The HTTP response code to be set.
	 * @return string        The HTTP response description.
	 */
	public static function code($code = 200) {
		if (!is_numeric($code)) {
			self::error('HTTP status code must be numeric.', 500);
		}

		switch ($code) {
		case 100: $text = 'Continue'; break;
		case 101: $text = 'Switching Protocols'; break;
		case 200: $text = 'OK'; break;
		case 201: $text = 'Created'; break;
		case 202: $text = 'Accepted'; break;
		case 203: $text = 'Non-Authoritative Information'; break;
		case 204: $text = 'No Content'; break;
		case 205: $text = 'Reset Content'; break;
		case 206: $text = 'Partial Content'; break;
		case 300: $text = 'Multiple Choices'; break;
		case 301: $text = 'Moved Permanently'; break;
		case 302: $text = 'Moved Temporarily'; break;
		case 303: $text = 'See Other'; break;
		case 304: $text = 'Not Modified'; break;
		case 305: $text = 'Use Proxy'; break;
		case 400: $text = 'Bad Request'; break;
		case 401: $text = 'Unauthorized'; break;
		case 402: $text = 'Payment Required'; break;
		case 403: $text = 'Forbidden'; break;
		case 404: $text = 'Not Found'; break;
		case 405: $text = 'Method Not Allowed'; break;
		case 406: $text = 'Not Acceptable'; break;
		case 407: $text = 'Proxy Authentication Required'; break;
		case 408: $text = 'Request Time-out'; break;
		case 409: $text = 'Conflict'; break;
		case 410: $text = 'Gone'; break;
		case 411: $text = 'Length Required'; break;
		case 412: $text = 'Precondition Failed'; break;
		case 413: $text = 'Request Entity Too Large'; break;
		case 414: $text = 'Request-URI Too Large'; break;
		case 415: $text = 'Unsupported Media Type'; break;
		case 500: $text = 'Internal Server Error'; break;
		case 501: $text = 'Not Implemented'; break;
		case 502: $text = 'Bad Gateway'; break;
		case 503: $text = 'Service Unavailable'; break;
		case 504: $text = 'Gateway Timeout'; break;
		case 505: $text = 'HTTP Version not supported'; break;
		default:
			self::error("Unknown HTTP status code '{$code}'", 500);
		break;
		}

		$server_protocol = (isset($_SERVER['SERVER_PROTOCOL'])) ? $_SERVER['SERVER_PROTOCOL'] : false;

		if ($server_protocol == 'HTTP/1.1' || $server_protocol == 'HTTP/1.0') {
			header("{$server_protocol} {$code} {$text}", true, $code);
		} else if (substr(php_sapi_name(), 0, 3) == 'cgi') {
			header("Status: {$code} {$text}", true);
		} else {
			header("HTTP/1.1 {$code} {$text}", true, $code);
		}

		return array(
			'code'	=>	$code,
			'text'	=>	$text
		);
	}
}

/* End of file Exceptions.php */
