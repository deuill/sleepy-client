<?php
/**
 * Template handles the rendering of templates which are mainly used in the View layer of
 * applications.
 *
 * Sleepy, the lightweight web application framework.
 * Copyright 2012 - 2014, Alex Palaistras (http://deuill.org)
 *
 * @copyright	Copyright 2012 - 2014, Alex Palaistras (http://deuill.org)
 * @link		http://deuill.org/sleepy
 * @package		Sleepy.Modules
 * @since		Sleepy 0.1.0
 */
class Template {
	/**
	 * The data that will be passed to the template for rendering.
	 * 
	 * @var array
	 */
	private $data;

	/**
	 * The path to the master template.
	 * 
	 * @var string
	 */
	private $template;

	/**
	 * The path to the layout or wrapper template.
	 * 
	 * @var string
	 */
	private $layout;

	/**
	 * An array of paths to templates referenced as partials.
	 * 
	 * @var array
	 */
	private $partials = array();

	/**
	 * Has this template instance been rendered already? Subsequent calls
	 * to 'render' will do nothing if this is true.
	 * 
	 * @var boolean
	 */
	private $rendered = false;

	/**
	 * Send templates to server for rendering and return result.
	 * 
	 * Render passes the templates and data to the server for rendering and returns the rendered
	 * template as a string, or false if any of the template files were not found.
	 *
	 * @param  string $template The master template file path.
	 * @param  string $layout   The layout/wrapper template file path.
	 * @param  array  $partials An array of partial template file paths.
	 * @return mixed  The rendered template or 'false' if an error occurred.
	 */
	public function render($template = null, $layout = null, $partials = array()) {
		if ($this->rendered == true) {
			return;
		}

		if (isset($template) || isset($layout) || !empty($partials)) {
			$this->__construct($template, $layout, (array) $partials);
		}

		$template = $this->check($this->template);	
		if ($template === false) {
			return false;
		}

		if (isset($this->layout)) {
			$layout = $this->check($this->layout);
			if ($layout === false) {
				return false;
			}
		}

		if (isset($this->partials)) {
			$partials = array_values($this->partials);
		}

		$partials = array_values($this->partials);
		foreach ($partials as $i => $partial) {
			$partials[$i] = $this->check($partial);
			if ($partials[$i] === false) {
				return false;
			}
		}

		Mediator::publish('beforeRender');
		$render = $this->parse($template, $layout, $partials);
		Mediator::publish('afterRender');

		$this->rendered = true;
		return $render;
	}

	/**
	 * Attach data to the template which will be used during rendering.
	 * 
	 * There are three distinct ways of setting data:
	 *
	 * - Calling 'set' with 'data' as an array will replace values with matching
	 *   keys recursively in the already existing data.
	 *
	 * - Calling 'set with 'data as an array and 'value' set to 'false' will
	 *   instead append the array to the existing data.
	 *
	 * - Calling 'set' with 'data' as a string creates a top-level heirarchy.
	 *   A string in the form of 'first.second.third' will create a heirarchy
	 *   equivalent to applying a nested array of the form:
	 *
	 * 	'first' => array(
	 * 		'second' => array(
	 * 			'third' => $value;
	 * 		)
	 * 	)
	 * 
	 * @param mixed $data  The value or key, depending on how 'set' was called.
	 * @param mixed $value The value or 'false', depending on how 'set' was called.
	 */
	public function set($data, $value = null) {
		if (is_array($data) && $value === false) {
			$this->data = (array) $this->data + $data;
		} else if (is_array($data)) {
			$this->data = array_replace_recursive((array) $this->data, $data);
		} else if (is_string($data)) {
			// We avoid returning an error when setting a null value for a string
			// data value, as we cannot predict null value variables.
			if (!empty($value)) {
				$d = array();
				$r =& $d;

				foreach (explode('.', $data) as $k) {
					if (!isset($r[$k])) {
						$r[$k] = array();
					}

					$r =& $r[$k];
				}

				$r = $value;
				$this->data = array_replace_recursive((array) $this->data, $d);
			}
		} else {
			Exceptions::warning("setting value for template failed, wrong parameters passed.", E_WARNING);
		}
	}

	/**
	 * Set application locale.
	 * 
	 * Set locale, either by value or by determining the user's preferences by using the
	 * Accept-Language header. Returns the short language code for the selected locale.
	 * 
	 * @param  string $lang Language code for the locale.
	 * @return string The short code for the locale.
	 */
	public function setLocale($lang = null) {
		if (!include(CLIENT_DIR.'config/locale.php')) {
			Exceptions::log("File 'locale.php' not found in config directory.");
			Exceptions::error();
		}

		if (!isset($lang)) {
			if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
				$tmp = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
				if (!empty($tmp)) {
					foreach ($tmp as $lng) {
						$s = explode(';q=', $lng);
						$w = (isset($s[1])) ? ($s[1] * 100) : 100;
						$lst[$w] = trim($s[0]);
					}

					krsort($lst);
					$lang = reset($lst);
				}

				$lang = Inflector::lower(preg_replace('/_\s/', '-', $lang));
				$lang = reset(explode('-', $lang));
			} else {
				$lang = Sleepy::get('app', 'lang');
			}
		}

		if (isset($locales[$lang])) {
			Sleepy::set('app', 'lang', $lang);
		} else {
			$lang = Sleepy::get('app', 'lang');
			if (!isset($locales[$lang])) {
				Exceptions::log("Non existing default language in 'app.lang' configuration.");
				Exceptions::error();
			}
		}

		setlocale(LC_ALL, $locales[$lang]);
		setlocale(LC_CTYPE, '');

		return $lang;
	}

	/**
	 * Initialize template, with an optional layout/wrapper template and partial templates.
	 * 
	 * @param string $template The master template file path.
	 * @param string $layout   The layout/wrapper template file path.
	 * @param array  $partials An array of partial template file paths.
	 */
	public function __construct($template, $layout = null, $partials = array()) {
		$this->template = $template;

		if (isset($layout)) {
			$this->layout = $layout;
		}

		if (!empty($partials)) {
			$this->partials = $partials;
		}
	}

	/**
	 * Send template and data for rendering.
	 *
	 * Send templates and data to the server for rendering, sending first the
	 * template file checksums for existing remote copies. Return the final
	 * rendered template.
	 * 
	 * @param  string $template The master template file path.
	 * @param  string $layout   The layout/wrapper template file path.
	 * @param  array  $partials An array of partial template file paths.
	 * @return string           The rendered template.
	 */
	private function parse($template, $layout = null, $partials = array()) {
		$data = array(
			'auth'		=>	Sleepy::get('client', 'authkey'),
			'data'		=>	$this->data,
			'template'	=>	array(
				'checksum'	=>	sha1_file($template),
				'path'		=>	str_replace(APP_DIR.'view/', '', $template)
			),
		);

		if (isset($layout)) {
			$data['layout']['checksum'] = sha1_file($layout);
			$data['layout']['path'] = str_replace(APP_DIR.'view/', '', $layout);
			$tables[] = $layout;
		}

		$tables[] = $template;

		foreach ($partials as $i => $partial) {
			$data['partials'][$i]['checksum'] = sha1_file($partial);
			$data['partials'][$i]['path'] = str_replace(APP_DIR.'view/', '', $partial);
			$tables[] = $partial;
		}

		$i = 0;
		foreach ($tables as $table) {
			$info = pathinfo($table);
			$path = $info['dirname'].'/'.$info['filename'].'.i18n';

			if (file_exists($path)) {
				$data['i18n']['tables'][$i]['checksum'] = sha1_file($path);
				$data['i18n']['tables'][$i]['path'] = str_replace(APP_DIR.'view/', '', $path);
				$translations[] = $path;
				$i++;
			}
		}

		if (isset($data['i18n'])) {
			$data['i18n']['origin'] = Sleepy::get('template', 'lang');
			$data['i18n']['target'] = Sleepy::get('app', 'lang');
		}

		// Check template cache.
		$result = Sleepy::call('Template', 'Render', $data);
		if ($result != null) {
			return $result;
		}

		// Cached data does not exist, send data to be cached.
		$data['template']['data'] = file_get_contents($template);

		if (isset($layout)) {
			$data['layout']['data'] = file_get_contents($layout);
		}

		foreach ($partials as $i => $partial) {
			$data['partials'][$i]['data'] = file_get_contents($partial);
			unset($data['partials'][$i]['checksum']);
		}

		if (!empty($translations)) {		
			foreach ($translations as $i => $translation) {
				$data['i18n']['tables'][$i]['data'] = file_get_contents($translation);
				unset($data['i18n']['tables'][$i]['checksum']);
			}
		}

		unset($data['template']['checksum'], $data['layout']['checksum']);
		return Sleepy::call('Template', 'Render', $data);
	} 

	/**
	 * Check if template file exists.
	 * 
	 * If an relative path is passed, the path is considered to be relative to the application's
	 * 'view' directory. Returns 'null' if the file path is empty, 'false' if the file was not
	 * found, or the absolute file path if the check was successful.
	 * 
	 * @param  string $file The file path.
	 * @return mixed        The return value as explained in the description.
	 */
	private function check($file) {
		if ($file === '') {
			return null;
		}

		$info = pathinfo($file);
		if (!isset($info['extension'])) {
			$file = $file.'.'.Sleepy::get('template', 'type');
		}

		if (strpos($file, '/') !== 0) {
			$file = APP_DIR.'view/'.$file;
		}

		if (!file_exists($file)) {
			// Only return the last part of the path, for security reasons.
			$file = str_replace("\\", "/", $file);
			if (strpos($file, '/') !== false) {
				$p = explode('/', $file);
				$file = $p[count($p) - 2].'/'.end($p);
			}

			Exceptions::log("View '{$file}' not found.");
			return false;
		}

		return $file;
	}
}

/* End of file Template.php */