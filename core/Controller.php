<?php
/**
 * Controller is the root controller class and provides functionality common in the
 * application business logic, such as setting variables used in views, rendering views etc.
 *
 * Sleepy, the lightweight web application framework.
 * Copyright 2012 - 2014, Alex Palaistras (http://thoughtmonster.org)
 *
 * @copyright	Copyright 2012 - 2014, Alex Palaistras (http://thoughtmonster.org)
 * @link		http://thoughtmonster.org/sleepy
 * @package		Sleepy.Core
 * @since		Sleepy 0.1.0
 */
class Controller {
	/**
	 * Controls the model-loading policy for the controller.
	 * 
	 * It is a choice of:
	 * 
	 * - `array()` - Use the models defined in the parent class only.
	 * - `array('File', 'User')` - Use the defined models only.
	 * - `true` - Use the default model for this controller.
	 * - `false` - Do not use any models at all.
	 *
	 * The default value is `true`.
	 * 
	 * @var mixed A value as in the above definitions.
	 */
	public $models = true;

	/**
	 * The title commonly used in the <head> section of a webpage.
	 * 
	 * HTML is not allowed and will be escaped.
	 * 
	 * @var string
	 */
	public $title = 'Page Title';

	/**
	 * The string used for separating title fragments.
	 * 
	 * HTML is allowed.
	 * 
	 * @var string
	 */
	public $separator = '&middot;';

	/**
	 * Direction in which title fragments will expand.
	 * 
	 * @var string
	 */
	public $direction = 'left';

	/**
	 * The default template object for this controller, used internally.
	 * 
	 * @var object
	 */
	private $template;

	/**
	 * Called before the controller action call.
	 * 
	 * @return void
	 */
	public function beforeCall() {
	}

	/**
	 * Called after the controller action call.
	 * 
	 * @return void
	 */
	public function afterCall() {
	}

	/**
	 * Called before the template is rendered.
	 * 
	 * @return void
	 */
	public function beforeRender() {
	}

	/**
	 * Called after the template is rendered.
	 * 
	 * @return void
	 */
	public function afterRender() {
	}

	/**
	 * Set variable for use in the default controller template.
	 * 
	 * Follows the same conventions as the 'set' method in the 'Template' module.
	 * 
	 * @param mixed $data  The data, as expected by 'Template::set()'.
	 * @param mixed $value The value, as expected by 'Template::set()'.
	 */
	public function set($data, $value = null) {
		// Extracts data from Model instances recursively.
		$extract = function(&$d, &$extract) {
			foreach ($d as $k => $v) {
				if (is_array($v)) {
					$extract($v, $extract);
				} else if ($v instanceof Model) {
					$d[$k] = $v->export();
				}
			}
		};

		// Models passed directly need special treatment in order to extract their data.
		if (is_array($data)) {
			$extract($data, $extract);
		} else if (is_string($data) && is_array($value)) {
			$extract($value, $extract);
		} else if ($value instanceof Model) {
			$value = $value->export();
		}

		return $this->template->set($data, $value);
	}

	/**
	 * Render and echo controller template.
	 * 
	 * Renders the default template for this controller, or if passed parameters as expected
	 * by 'Template::render()', overrides the default templates and renders the templates passed.
	 *
	 * This returns nothing, but echoes the rendered result back to the user.
	 * 
	 * @param  string $template The master template file path.
	 * @param  string $layout   The layout/wrapper template file path.
	 * @param  array  $partials An array of partial template file paths.
	 * @return void
	 */
	public function render($template = null, $layout = null, $partials = array()) {
		$params = Dispatcher::params();
		$title = array_map('htmlspecialchars', (array) $this->title);
		$title = ($this->direction == 'left') ? array_reverse($title) : $title;

		// Set global variables.
		$this->template->set(array(
			'app'		=>	 array(
				'base'		 =>	Dispatcher::base(),
				'controller' =>	$params['controller'],
				'action'	 =>	$params['action'],
				'lang'		 =>	Sleepy::get('app', 'lang'),
				'title'		 =>	implode(" {$this->separator} ", $title)
			)
		));

		$result = $this->template->render($template, $layout, $partials);
		if ($result === false) {
			Exceptions::error(404);
		}

		echo $result;
	}

	/**
	 * Append title to the global page title (if any). 
	 * 
	 * The title can either be a string, or an array of strings, in which case each element is
	 * seperated by the 'separator' string. Multiple calls will append title elements, unless
	 * $append is false. HTML is not allowed, and will be escaped.
	 * 
	 * @param  mixed   $title  The title to append or replace.
	 * @param  boolean $append Whether we append or replace the previously set title(s).
	 * @return void
	 */
	public function title($title, $append = true) {
		$prev = (array) $this->title;

		if ($append) {
			$this->title = array_merge($prev, (array) $title);
		} else {
			$this->title = array_merge(reset($prev), (array) $title);
		}
	}

	/**
	 * Initialize and load default functionality into the controller.
	 */
	public function __construct() {
		// Load and initialize the template module.
		$params = Dispatcher::params();

		$template = Inflector::camelize($params['controller']).'/'.$params['action'];
		$layout = 'Layouts/default';

		Sleepy::load('Template', 'modules');
		$this->template = new Template($template, $layout);

		// Set language for template.
		$this->template->setLocale();
	}
}

/* End of file Controller.php */