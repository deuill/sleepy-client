<?php
/**
 * Model provides primitives which application models base themselves on.
 *
 * Sleepy, the lightweight web application framework.
 * Copyright 2012 - 2014, Alex Palaistras (http://thoughtmonster.org)
 *
 * @copyright	Copyright 2012 - 2014, Alex Palaistras (http://thoughtmonster.org)
 * @link		http://thoughtmonster.org/sleepy
 * @package		Sleepy.Core
 * @since		Sleepy 0.1.0
 */
class Model {
	/**
	 * The modules to load for this model.
	 * 
	 * Modules provide functionality such as database integration, file uploading etc.
	 * By default, we load no modules.
	 * 
	 * @var array
	 */
	public $modules = array();

	/**
	 * The name for this model. If left unset, it defaults to the model's class name.
	 * 
	 * @var string
	 */
	protected $name;

	/**
	 * Contains data, in most cases dynamically populated from our database, for this model.
	 * 
	 * The interface is dynamic and unstable, and should be used via the relative magic methods.
	 * 
	 * @var object
	 */
	protected $data;

	/**
	 * Contains the reference table for all models, indexed by model name.
	 * 
	 * @var array
	 */
	protected static $references;

	/**
	 * Initializes model, optionally preparing it with data passed as an array.
	 * 
	 * @param  array  $data Optional data to be passed into the model.
	 * @return object       The model object instance.
	 */
	public function create($data = array()) {
		$this->data = (object) $data;
		return $this;
	}

	/**
	 * Fetches single model from database, using the primary key as the model id.
	 * 
	 * @param  mixed  $id  The primary key to match against. Most likely a number.
	 * @return object      The model, initialized with data from the database.
	 */
	public function get($id) {
		$db = new Database;
		$table = Inflector::tableize($this->name);

		$result = $db->where("{$table}.id", $id)->get($table);
		$this->data = (empty($result)) ? array() : reset($result);
		$this->attachReferences();

		return $this;
	}

	/**
	 * Saves model into database.
	 * 
	 * @return int The new id, if model was created, or a true/false if model was updated.
	 */
	public function save() {
		$db = new Database;
		$table = Inflector::tableize($this->name);

		return $db->put($table, $this->data);
	}

	/**
	 * Deletes model from database.
	 * 
	 * @return bool Whether or not the model was successfully deleted.
	 */
	public function delete() {
		$db = new Database;
		$table = Inflector::tableize($this->name);

		return $db->where("{$table}.id", $data->id)->delete($table);
	}

	/**
	 * Fetches an array of models from the database, according to the conditions passed.
	 * 
	 * @param  array  $conditions The conditions, as expected by Database::where().
	 * @return array              An array of models.
	 */
	public function find($conditions = array()) {
		$db = new Database;
		$table = Inflector::tableize($this->name);

		if (!is_array($conditions)) {
			call_user_func_array(array($db, 'where'), func_get_args());
		} else if (!empty($conditions)) {
			$db->where($conditions);
		}

		$models = array();
		$result = $db->orderBy('order', 'ASC')->get($table);
		foreach ($result as $i => $r) {
			$models[$i] = self::generateModel($this->name);
			$models[$i]->create($r);
			$models[$i]->attachReferences();
		}

		return $models;
	}

	/**
	 * Mass export data from model.
	 * 
	 * @return array The internal data structure of the model.
	 */
	public function export() {
		return $this->data;
	}

	/**
	 * Fetch data item by name.
	 * 
	 * @param  string $name The name of the item.
	 * @return mixed        The value of the item, or null, if item doesn't exist.
	 */
	public function __get($name) {
		if (isset($this->data->$name)) {
			return $this->data->$name;
		}

		return null;
	}

	/**
	 * Set value for data item.
	 * 
	 * @param string $name  The name of the item.
	 * @param mixed  $value The value of the item.
	 */
	public function __set($name, $value) {
		$this->data->$name = $value;
	}

	/**
	 * Check if data item is set when called from isset().
	 * 
	 * @param  string  $name The name of the item.
	 * @return boolean       Whether or not the item exists.
	 */
	public function __isset($name) {
		return isset($this->data->$name);
	}

	/**
	 * Unset data item.
	 * 
	 * @param string $name The name of the item.
	 */
	public function __unset($name) {
		if (isset($this->data->$name)) {
			unset($this->data->$name);
		}
	}

	/**
	 * Creates an instance of the calling class, under a configurable name.
	 * 
	 * This is mainly used for creating virtual models for use in controllers.
	 * 
	 * @param  string $name The name of our created model.
	 * @return object       The model, an instance of the calling class.
	 */
	public static function generateModel($name) {
		$class = get_called_class();

		$model = new $class;
		$model->name = $name;

		// Resolve foreign key constraints and create a map of references.
		if (!isset(self::$references[$name])) {
			self::$references[$name] = self::getReferences($name);
		}

		// The Database module is required for our built-in functionality.
		if (empty($model->modules)) {
			$model->modules = array('Database');
		}

		return $model;
	}

	/**
	 * Attach data to this model instance using the model's table references.
	 *
	 * Returns nothing but attaches the data directly to the instance.
	 *
	 * @return void
	 */
	protected function attachReferences() {
		$db = new Database;

		foreach (self::$references[$this->name] as $key => $t) {
			foreach ($t as $ref) {
				if (is_array($ref)) {
					foreach ($ref as $junction => $child) {
						$c = preg_split('/\.|\s/', $child);

						$db->select("{$c[3]}.*")->where($junction, $this->data->{$key});
						$db->join($c[3], $child, 'left');
						$result = $db->orderBy("{$c[3]}.order", 'ASC')->get($c[0]);
						if (!empty($result)) {
							$n = preg_split('/_/', $c[0], 2);
							$this->data->{end($n)} = $result;
						}
					}
				} else {
					$c = explode('.', $ref);

					$db->where($c[1], $this->data->{$key});
					$result = $db->orderBy("{$c[1]}.order", 'ASC')->get($c[1]);
					if (!empty($result)) {
						$this->data->{$c[1]} = $result;
					}
				}
			}
		}
	}

	/**
	 * Analyze and return foreign key references for this Model's table.
	 *
	 * Fetches foreign key relationships from the server and passes through each, creating
	 * a heirarchy of relationships to be resolved by Model::attachReferences. A sample
	 * reference heirarchy appears as so:
	 *
	 * 	[Article] => (
	 * 		[id] => (
	 * 			[0] => (
	 * 				[article_categories.item_id] => article_categories.category_id = categories.id
	 * 			)
	 * 			[1] => article_photo.article_id
	 * 		)
	 * 	)
	 *
	 * Which, for the first item, attaches 'article.id' to 'categories.id' via the 'article_categories'
	 * junction table with a many-to-many relationship, and for the second item, attaches 'article.id'
	 * to 'article_photo.article_id' with a one-to-many relationship.
	 *
	 * @param  string $name The model name, as it appears in Model->name.
	 * @return array        The references array, as expected by Model::attachReferences.
	 */
	protected static function getReferences($name) {
		Sleepy::load('Database', 'modules');
		Sleepy::load('User', 'modules');

		$db = new Database;
		$table = Inflector::tableize($name);

		if (!isset(self::$references['data'])) {		
			$user = new User(Sleepy::get('client', 'authkey'));
			$dbname = $user->options('database', 'database', 'name');
			self::$references['data'] = (array) $db->query(
				"SELECT `TABLE_NAME` AS `tbl`, `COLUMN_NAME` AS `col`,
					`REFERENCED_TABLE_NAME` AS `r_tbl`, `REFERENCED_COLUMN_NAME` AS `r_col`
					FROM `INFORMATION_SCHEMA`.`KEY_COLUMN_USAGE`
					WHERE `REFERENCED_TABLE_SCHEMA` = ?", array($dbname)
			);
		}

		$references[$name] = array();
		$data = self::$references['data'];
		$count = count($data);

		// Traverse array until it is empty or until we cannot process any more items.
		while (!empty($data)) {
			list($i, $r) = each($data);
			if ($r === null && $count !== count($data)) {
				$count = count($data);
				reset($data);
				list($i, $r) = each($data);
			} else if ($r === null) {
				break;
			}

			if ($r->r_tbl === $table) {
				$c = (!empty($references[$name][$r->r_col])) ? count($references[$name][$r->r_col]) : 0;
				$references[$name][$r->r_col][$c] = $r->tbl.'.'.$r->col;
				$ref[$r->tbl] = &$references[$name][$r->r_col][$c];
				unset($data[$i]);
			} else if (isset($ref[$r->tbl])) {
				$t = array($ref[$r->tbl] => $r->tbl.'.'.$r->col.' = '.$r->r_tbl.'.'.$r->r_col);
				$ref[$r->tbl] = $t;
				unset($data[$i]);
			}
		}

		return $references[$name];
	}
}

/* End of file Model.php */