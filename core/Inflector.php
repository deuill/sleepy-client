<?php
/**
 * Inflector contains various methods for working with strings, e.g. romanization,
 * normalization etc.
 *
 * Sleepy, the lightweight web application framework.
 * Copyright 2012 - 2014, Alex Palaistras (http://deuill.org)
 *
 * @copyright	Copyright 2012 - 2014, Alex Palaistras (http://deuill.org)
 * @link		http://deuill.org/sleepy
 * @package		Sleepy.Core
 * @since		Sleepy 0.1.0
 */
class Inflector {
	/**
	 * Plural inflector rules.
	 * 
	 * @var array
	 */
	private static $plural = array(
		'/(quiz)$/i'					=> "$1zes",
		'/^(oxen)$/i'					=> "$1",
		'/^(ox)$/i'						=> "$1en",
		'/^(m|l)ice$/i'					=> "$1ice",
		'/^(m|l)ouse$/i'				=> "$1ice",
		'/(matr|vert|ind)(?:ix|ex)$/i'	=> "$1ices",
		'/(x|ch|ss|sh)$/i'				=> "$1es",
		'/([^aeiouy]|qu)y$/i'			=> "$1ies",
		'/(hive)$/i'					=> "$1s",
		'/(?:([^f])fe|([lr])f)$/i'		=> "$1$2ves",
		'/sis$/i'						=> "ses",
		'/([ti])a$/i'					=> "$1a",
		'/([ti])um$/i'					=> "$1a",
		'/(buffal|tomat)o$/i'			=> "$1oes",
		'/(bu)s$/i'						=> "$1ses",
		'/(alias|status)$/i'			=> "$1es",
		'/(octop|vir)i$/i'				=> "$1i",
		'/(octop|vir)us$/i'				=> "$1i",
		'/^(ax|test)is$/i'				=> "$1es",
		'/s$/i'							=> "s",
		'/$/'							=> "s"
	);

	/**
	 * Singular inflector rules.
	 * 
	 * @var array
	 */
	private static $singular = array(
		'/(database)s$/i'			=> "$1",
		'/(quiz)zes$/i'				=> "$1",
		'/(matr)ices$/i'			=> "$1ix",
		'/(vert|ind)ices$/i'		=> "$1ex",
		'/^(ox)en/i'				=> "$1",
		'/(alias|status)(es)?$/i'	=> "$1",
		'/(octop|vir)(us|i)$/i'		=> "$1us",
		'/^(a)x[ie]s$/i'			=> "$1xis",
		'/(cris|test)(is|es)$/i'	=> "$1is",
		'/(shoe)s$/i'				=> "$1",
		'/(o)es$/i'					=> "$1",
		'/(bus)(es)?$/i'			=> "$1",
		'/^(m|l)ice$/i'				=> "$1ouse",
		'/(x|ch|ss|sh)es$/i'		=> "$1",
		'/(m)ovies$/i'				=> "$1ovie",
		'/(s)eries$/i'				=> "$1eries",
		'/([^aeiouy]|qu)ies$/i'		=> "$1y",
		'/([lr])ves$/i'				=> "$1f",
		'/(tive)s$/i'				=> "$1",
		'/(hive)s$/i'				=> "$1",
		'/([^f])ves$/i'				=> "$1fe",
		'/(^analy)(sis|ses)$/i'		=> "$1sis",
		'/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)(sis|ses)$/i' => "$1sis",
		'/([ti])a$/i'				=> "$1um",
		'/(n)ews$/i'				=> "$1ews",
		'/(ss)$/i'					=> "$1",
		'/s$/i'						=> ""
	);

	/**
	 * Rules for irregular words.
	 * 
	 * @var array
	 */
	private static $irregular = array(
		'person' => 'people', 'man'  => 'men',   'child' => 'children',
		'sex'    => 'sexes',  'move' => 'moves', 'cow'   => 'kine',
		'zombie' => 'zombies'
	);

	/**
	 * Rules for words that cannot be inflected.
	 * 
	 * @var array
	 */
	private static $uncountable = array(
		'equipment', 'information', 'rice', 'money', 'species',
		'series', 'fish', 'sheep', 'jeans', 'police'
	);

	/**
	 * Rules for transliterating to Roman/ASCII.
	 * 
	 * @var array
	 */
	private static $translit = array(
		// Rules for Greek language strings.
		'/Β/' => 'B', '/Γ/' => 'C',  '/Δ/' => 'D',
		'/Ζ/' => 'Z', '/Θ/' => 'TH', '/Κ/' => 'K',
		'/Λ/' => 'L', '/Μ/' => 'M',  '/Ν/' => 'N',
		'/Ξ/' => 'X', '/Π/' => 'P',  '/Ρ/' => 'R',
		'/Σ/' => 'S', '/Τ/' => 'T',  '/Υ/' => 'Y',
		'/Φ/' => 'F', '/Χ/' => 'H',  '/Ψ/' => 'PS',
		'/β/' => 'v', '/γ/' => 'g',  '/δ/' => 'd',
		'/ζ/' => 'z', '/θ/' => 'th', '/κ/' => 'k',
		'/λ/' => 'l', '/μ/' => 'm',  '/ν/' => 'n',
		'/ξ/' => 'x', '/π/' => 'p',  '/ρ/' => 'r',
		'/τ/' => 't', '/φ/' => 'f',  '/χ/' => 'h',
		'/ψ/' => 'ps',
		'/Α|Ά/' => 'A', '/Ε|Έ/' => 'E', '/Η|Ή/' => 'H',
		'/Ι|Ί/' => 'I', '/Ο|Ό/' => 'O', '/Ω|Ώ/' => 'O',
		'/α|ά/' => 'a', '/ε|έ/' => 'e', '/σ|ς/' => 's',
		'/ο|ό|ω|ώ/' => 'o', '/υ|ύ|ϋ|ΰ/' => 'u', '/η|ή|ι|ί|ϊ|ΐ/' => 'i'
	);

	/**
	 * Rules for converting CamelCase string to underscore format.
	 * 
	 * @var string
	 */
	private static $uncamel = '/(?<=[a-z])(?=[A-Z])|(\s)|(-)/x';

	/**
	 * Romanize string using a mixed transliteration system.
	 * 
	 * @param  string $str The string which will be converted.
	 * @return string      The romanized string.
	 */
	public static function romanize($str) {
		return preg_replace(array_keys(self::$translit), array_values(self::$translit), $str);
	}

	/**
	 * Convert underscore format string to CamelCase.
	 * 
	 * @param  string $str The string which will be converted.
	 * @return string      The resulting string.
	 */
	public static function camelize($str) {
		$split = preg_split('/\s|-|_/', self::lower($str));
		return implode('', array_map('self::title', $split));
	}

	/**
	 * Convert CamelCase string to underscore format.
	 * 
	 * @param  string $str The string which will be converted.
	 * @return string      The resulting string.
	 */
	public static function underscore($str) {
		return self::lower(implode('_',  preg_split(self::$uncamel, $str)));
	}

	/**
	 * Convert string to all-lowercase, roman characters, removing all
	 * non-letter or number characters and replacing spaces, underscores
	 * and hyphens with a seperator, by default a hyphen.
	 * 
	 * @param  string $str The string which will be converted.
	 * @return string      The normalized string.
	 */
	public static function normalize($str, $sep = '-') {
		return preg_replace(
			array('/[^A-Za-z0-9-_\s]/', '/\s|-|_/'),
			array('', $sep),
			self::lower(self::romanize(trim($str)))
		);
	}

	/**
	 * Converts CamelCase or space-seperated string into an underscored,
	 * pluralized string following our database naming conventions.
	 * 
	 * @param  string $str The string which will be converted.
	 * @return string      The tableized string.
	 */
	public static function tableize($str) {
		$split = preg_split('/_/', self::underscore($str));
		$split = array_map('self::singularize', $split);
		$last = self::pluralize(array_pop($split));

		return self::normalize(implode('_', array_merge($split, (array) $last)), '_');
	}

	/**
	 * Converts underscored or space-seperated string into a CamelCase
	 * string following our class naming conventions.
	 * 
	 * @param  string $str The string which will be converted.
	 * @return string      The classified string.
	 */
	public static function classify($str) {
		return self::camelize(self::singularize($str));
	}

	/**
	 * Return english word in plural form.
	 * 
	 * @param  string $str The word to be pluralized
	 * @return string      The pluralized string (if applicable).
	 */
	public static function pluralize($str) {
		if (in_array(self::lower($str), self::$uncountable)) {
			return $str;
		}

		foreach (self::$irregular as $pattern => $result) {
			$pattern = '/'.$pattern.'$/i';

			if (preg_match($pattern, $str)) {
				return preg_replace($pattern, $result, $str);
			}
		}

		foreach (self::$plural as $pattern => $result) {
			if (preg_match($pattern, $str)) {
				return preg_replace($pattern, $result, $str);
			}
		}

		return $str;
	}

	/**
	 * Return english word in singular form.
	 * 
	 * @param  string $str The word to be singularized.
	 * @return string      The singularized string (if applicable).
	 */
	public static function singularize($str) {
		if (in_array(self::lower($str), self::$uncountable)) {
			return $str;
		}

		foreach (self::$irregular as $result => $pattern) {
			$pattern = '/'.$pattern.'$/i';

			if (preg_match($pattern, $str)) {
				return preg_replace($pattern, $result, $str);
			}
		}

		foreach (self::$singular as $pattern => $result) {
			if (preg_match($pattern, $str)) {
				return preg_replace($pattern, $result, $str);
			}
		}

		return $str;
	}

	/**
	 * Convert string to UPPER CASE.
	 * 
	 * @param  string $str The string which will be converted.
	 * @return string      The resulting string.
	 */
	public static function upper($str) {
		return mb_convert_case($str, MB_CASE_UPPER, 'UTF-8');
	}

	/**
	 * Convert string to lower case.
	 * 
	 * @param  string $str The string which will be converted.
	 * @return string      The resulting string.
	 */
	public static function lower($str) {
		return mb_convert_case($str, MB_CASE_LOWER, 'UTF-8');
	}

	/**
	 * Convert string to Title Case.
	 * 
	 * @param  string $str The string which will be converted.
	 * @return string      The resulting string.
	 */
	public static function title($str) {
		return mb_convert_case($str, MB_CASE_TITLE, 'UTF-8');
	}

	/**
	 * Convert string to Sentence case.
	 * 
	 * @param  string $str The string which will be converted.
	 * @return string      The resulting string.
	 */
	public static function sentence($str) {
		$len    = mb_strlen($str, 'UTF-8');
		$first  = mb_substr($str, 0, 1, 'UTF-8');
		$remain = mb_substr($str, 1, $len - 1, 'UTF-8');

		return self::upper($first).self::lower($remain);
	}
}

/* End of file Inflector.php */