<?php
/**
 * Database hooks into the Database module on the server and provides support for inserting
 * and fetching data to and from databases running on the server.
 *
 * Sleepy, the lightweight web application framework.
 * Copyright 2012 - 2014, Alex Palaistras (http://thoughtmonster.org)
 *
 * @copyright	Copyright 2012 - 2014, Alex Palaistras (http://thoughtmonster.org)
 * @link		http://thoughtmonster.org/sleepy
 * @package		Sleepy.Modules
 * @since		Sleepy 0.1.0
 */
class Database {
	/**
	 * Used to temporarily hold the query data which are then passed on to the server.
	 *
	 * @var array The query data.
	 */
	private $data;

	/**
	 * Allows you to specify the SELECT portion of your query.
	 *
	 * The function accepts either an array of values or a comma-seperated list of values
	 * containing the columns the query is to return. Values are either strings representing
	 * columns or arrays in the form of array('column' => 'alias') for aliasing the column with AS.
	 *
	 * Example usage:
	 *
	 * 	$db->select(array('id' => 'num'), 'name', 'surname')->get('table');
	 *
	 * or:
	 *
	 * 	$columns = array(array('id' => 'num'), 'name', 'surname');
	 * 	$db->select($columns)->get('table');
	 *
	 * Produces:
	 *
	 * 	SELECT `id` AS num, `name`, `surname` FROM `table`
	 *
	 * @param  mixed  $columns The list of columns which are to be returned.
	 * @return object          The database object, for chaining commands together.
	 */
	public function select($columns) {
		if (is_array($columns) && count($columns == 1) && is_string(key($columns))) {
			$this->data['select'] = array($columns);
		} else if (is_array($columns)) {
			$this->data['select'] = $columns;
		} else {
			$this->data['select'] = func_get_args();
		}

		return $this;
	}

	/**
	 * Adds the DISTINCT keyword to your query.
	 *
	 * Example usage:
	 *
	 * 	$db->distinct()->get('table');
	 *
	 * Produces:
	 *
	 * 	SELECT DISTINCT * FROM `table`;
	 * 
	 * @return object The database object, for chaining commands together.
	 */
	public function distinct() {
		$this->data['distinct'] = true;

		return $this;
	}

	/**
	 * Allows you to specify the FROM portion of your query.
	 *
	 * This is equivalent to specifying a table-name in the 'get' method, so use whichever method
	 * you prefer.
	 *
	 * Example usage:
	 *
	 * 	$db->from('table')->get();
	 *
	 * Produces:
	 *
	 * 	SELECT * FROM `table`
	 *
	 * @param  string $table The table name which will be used in the query.
	 * @return object        The database object, for chaining commands together.
	 */
	public function from($table) {
		$this->data['table'] = $table;

		return $this;
	}

	/**
	 * Adds a JOIN portion to your query.
	 *
	 * Different types of joins can be specified via the third parameter passed to the method
	 * (allowed types are: LEFT, RIGHT, INNER, OUTER, LEFT OUTER, RIGHT OUTER all case-insensitive).
	 * Multiple conditions can be passed as an array and are chained using AND.
	 *
	 * Example usage:
	 *
	 * 	$db->join('comments', 'article.id = comments.ref_id', 'right')->get('articles');
	 *
	 * Produces:
	 *
	 * 	SELECT * FROM `articles` RIGHT JOIN `comments` ON articles.id = comments.ref_id
	 *
	 * @param  string $table     The table name which will be used in the query.
	 * @param  string $condition The condition(s) on which the table will be joined.
	 * @param  string $type      The type of the join.
	 * @return object            The database object, for chaining commands together.
	 */
	public function join($table, $condition, $type = 'left') {
		$this->data['join'][] = array(
			'table'			=> $table,
			'conditions'	=> (array) $condition,
			'type'			=> $type
		);

		return $this;
	}

	/**
	 * Adds a WHERE portion to your query.
	 *
	 * The function accepts either a comma-seperated list of conditions, in the form of
	 * 'column', $value, or an associative array in the form of  'column' => $value. Both forms
	 * accept comparison operators to be appended to the column names for custom comparison
	 * operations (the default operation is '=').
	 *
	 * Multiple calls of 'where' are chained together using AND unless  preceeded by a call to or().
	 *
	 * Example usage:
	 *
	 * 	$db->where('id', 5)->get('table');
	 *
	 * or:
	 *
	 * 	$conditions = array('id' => 5);
	 * 	$db->where($conditions)->get('table');
	 *
	 * Produces:
	 *
	 * 	SELECT * FROM `table` WHERE `id` = 5;
	 *
	 * @param  mixed  $conditions The conditions which the query will match against.
	 * @return object             The database object, for chaining commands together.
	 */
	public function where($conditions) {
		if (!is_array($conditions)) {
			$conditions = array();
			$args = func_get_args();

			for ($i = 0; isset($args[$i]); $i += 2) {
				$conditions[$args[$i]] = $args[$i + 1];
			}
		}

		if (isset($this->data['or'])) {
			$this->data['filter'][] = 'or';
			unset($this->data['or']);
		}

		$this->data['filter'][] = 'where';
		$this->data['filter'][] = $conditions;

		return $this;
	}

	/**
	 * Adds a WHERE `column` IN (...) portion to your query.
	 *
	 * The function accepts a column name as a string and an array of values which will be matched
	 * against the column name.
	 * 
	 * Preceeding this with a call to not() produces a WHERE NOT query.
	 * 
	 * Example usage:
	 *
	 * 	$db->whereIn('cat_id', array(2, 3, 5))->get('table');
	 *
	 * Produces:
	 *
	 * 	SELECT * FROM `table` WHERE `id` IN (2, 3, 5);
	 *
	 * @param  string $column     The column to search in.
	 * @param  array  $conditions The list of values which will be matched against.
	 * @return object             The database object, for chaining commands together.
	 */
	public function whereIn($column, $conditions) {
		$args = array(
			'column' => $column,
			'in'	 => (array) $conditions
		);

		if (isset($this->data['or'])) {
			$this->data['filter'][] = 'or';
			unset($this->data['or']);
		}

		$this->data['filter'][] = 'where-in';
		$this->data['filter'][] = $args;

		return $this;
	}

	/**
	 * Adds a LIKE portion to your query.
	 *
	 * The function accepts its arguments in the same manner as where(), only the characters
	 * '%' and '_' have special meaning, and are used appropriately for pattern matching.
	 *
	 * Multiple clauses are chained together using AND unless preceeded by a call to or().
	 * Preceeding this with a call to not() produces a NOT LIKE query.
	 *
	 * Example usage:
	 *
	 * 	$db->like('title', '%Ipsum%')->get('table');
	 *
	 * Produces:
	 *
	 * 	SELECT * FROM `table` WHERE `title` LIKE '%Ipsum%';
	 *
	 * @param  mixed  $conditions The conditions which the query will match against.
	 * @return object             The database object, for chaining commands together.
	 */
	public function like($conditions) {
		if (!is_array($conditions)) {
			$conditions = array();
			$args = func_get_args();
			$conditions[$args[0]] = $args[1];
		}

		if (isset($this->data['or'])) {
			$this->data['filter'][] = 'or';
			unset($this->data['or']);
		}

		$this->data['filter'][] = 'like';
		$this->data['filter'][] = $conditions;

		return $this;
	}

	/**
	 * Allows you to specify the GROUP BY portion of your query.
	 *
	 * The function accepts either an array of strings or a comma-seperated list of strings
	 * containing the columns the query is to group by.
	 *
	 * Example usage:
	 *
	 * 	$db->groupBy('category')->get('table');
	 *
	 * Produces:
	 *
	 * 	SELECT * FROM `table` GROUP BY `category`;
	 * 
	 * @param  mixed  $columns The list of columns which we will group by.
	 * @return object          The database object, for chaining commands together.
	 */
	public function groupBy($columns) {
		if (is_array($columns)) {
			$this->data['group'] = $columns;
		} else {
			$this->data['group'] = func_get_args();
		}

		return $this;	
	}

	/**
	 * Adds a HAVING portion to your query.
	 *
	 * The syntax of this function is exactly the same as that of the 'where' function.
	 * 
	 * Example usage:
	 *
	 * 	$db->having('id', 5)->get('table');
	 *
	 * or:
	 *
	 * 	$conditions = array('id' => 5);
	 * 	$db->having($conditions)->get('table');
	 *
	 * Produces:
	 *
	 * 	SELECT * FROM `table` HAVING `id` = 5;
	 * 	
	 * @param  mixed  $conditions The conditions which the query will match against.
	 * @return object             The database object, for chaining commands together.
	 */
	public function having($conditions) {
		if (!is_array($conditions)) {
			$conditions = array();
			$args = func_get_args();

			for ($i = 0; isset($args[$i]); $i += 2) {
				$conditions[$args[$i]] = $args[$i + 1];
			}
		}

		if (isset($this->data['or'])) {
			$this->data['having'][] = 'or';
			unset($this->data['or']);
		}

		$this->data['having'][] = $conditions;

		return $this;
	}

	/**
	 * Allows you to specify the ORDER BY portion of your query.
	 *
	 * The function accepts a comma-seperated list in the form of 'column', 'order', and may
	 * contain more than one set of pairs. Order is either ASC, DESC or RANDOM, case-insensitive.
	 * 
	 * Example usage:
	 *
	 * 	$db->orderBy('id', 'DESC', 'date', 'ASC')->get('table');
	 *
	 * Produces:
	 *
	 * 	SELECT * FROM `table` ORDER BY `id` DESC, `date` ASC;
	 * 	
	 * @param  string $column The column to order by.
	 * @param  string $order  One of ASC, DESC or RANDOM, case-insensitive.
	 * @return object         The database object, for chaining commands together.
	 */
	public function orderBy($column, $order = 'DESC') {
		$args = func_get_args();

		for ($i = 0; isset($args[$i]); $i += 2) {
			$conditions[] = array(
				'column' => $args[$i],
				'order'  => $args[$i + 1]
			);
		}

		$this->data['order'] = $conditions;

		return $this;
	}

	/**
	 * Allows you to specify the LIMIT portion of your query.
	 *
	 * The second parameter allows you to set an optional offset to the results. This is equivalent
	 * to setting the limit and offset in the get() call, so use whichever method you like.
	 * 
	 * Example usage:
	 *
	 * 	$db->limit(10, 50)->get('table');
	 *
	 * Produces:
	 *
	 * 	SELECT * FROM `table` LIMIT 10 OFFSET 50;
	 * 	
	 * @param  integer $limit  The LIMIT for this query.
	 * @param  integer $offset The (optional) OFFSET for this query.
	 * @return object          The database object, for chaining commands together.
	 */
	public function limit($limit, $offset = null) {
		$this->data['limit'] = $limit;

		if ($offset !== null) {
			$this->data['offset'] = $offset;
		}

		return $this;
	}

	/**
	 * Specifies that the next 'WHERE', 'WHERE ... IN' AND 'LIKE' calls are to be negated with NOT.
	 *
	 * Example usage:
	 *
	 * 	$db->not()->like('title', '%Ipsum%')->get('table');
	 *
	 * Produces:
	 *
	 * 	SELECT * FROM `table` WHERE `title` NOT LIKE '%Ipsum%';
	 *
	 * @return object The database object, for chaining commands together.
	 */
	public function not() {
		$this->data['filter'][] = 'not';

		return $this;
	}

	/**
	 * Fetches and returns data from the database.
	 *
	 * Called alone, it retuns the entire table contents, optionally applying a limit and offset
	 * to the results. It returns an error if no table was set and none was found set from
	 * previous calls to from().
	 *
	 * Example usage:
	 *
	 * 	$db->get('table');
	 *
	 * Produces:
	 *
	 * 	SELECT * FROM `table`
	 *
	 * @param  string  $table  The table name to query against.
	 * @param  integer $limit  The LIMIT for this query.
	 * @param  integer $offset The OFFSET for this query.
	 * @return mixed           The raw response for this request.
	 */
	public function get($table = null, $limit = null, $offset = null) {
		if ($table !== null) {
			$this->data['table'] = $table;
		}

		if ($limit !== null) {
			$this->data['limit'] = $limit;
		}

		if ($offset !== null) {
			$this->data['offset'] = $offset;
		}

		// Reset global 'data' variable.
		$query = $this->data;
		unset($this->data);

		$query['auth'] = Sleepy::get('client', 'authkey');
		$query['sig'] = md5(serialize($query));

		return Sleepy::call('Database', 'Get', $query);
	}

	/**
	 * Inserts or updates data on database.
	 *
	 * The function accepts data to be inserted in the form of an associative array of
	 * 'column' => $value pairs. If a call to where() preceeds the put() call, the data
	 * is updated instead.
	 *
	 * Example usage:
	 *
	 * 	$db->put('table', array('name' => 'John', 'surname' => 'Doe'));
	 *
	 * Produces:
	 *
	 * 	INSERT INTO `table` (`name`, `surname`) VALUES (?, ?)
	 *
	 * Where the question marks are positional parameters, replaced by
	 * their respective values above ('John' and 'Doe' in the above
	 * example).
	 *
	 * @param  string $table The table to be updated.
	 * @param  mixed  $data  The data to be inserted or updated.
	 * @return mixed         The raw response for this request.
	 */
	public function put($table, $data) {
		$this->data['table'] = $table;
		$this->data['data'] = $data;

		// Reset global 'data' variable.
		$query = $this->data;
		unset($this->data);

		$query['auth'] = Sleepy::get('client', 'authkey');
		$query['sig'] = md5(serialize($query));

		return Sleepy::call('Database', 'Put', $query);
	}

	/**
	 * Deletes entries from database.
	 *
	 * The function accepts an array of conditions in the same form as the associative array
	 * version of a where() call. You may also use where() calls instead of the second parameter.
	 *
	 * Example usage:
	 *
	 * 	$db->where('id', 1)->delete('table');
	 *
	 * Produces:
	 *
	 * 	DELETE FROM `table` WHERE `id` = 1;
	 *
	 * @param  string $table      The table to be queried.
	 * @param  array  $conditions The query conditions, in the same format as in the 'where()' call.
	 * @return mixed              The raw response for this request.
	 */
	public function delete($table, $conditions = null) {
		$this->data['table'] = $table;

		if (!isset($this->data['conditions']) && $conditions !== null) {
			$this->where($conditions);
		}

		// Reset global 'data' variable.
		$query = $this->data;
		unset($this->data);

		$query['auth'] = Sleepy::get('client', 'authkey');
		$query['sig'] = md5(serialize($query));

		return Sleepy::call('Database', 'Delete', $query);
	}

	/**
	 * Executes raw query on database.
	 *
	 * The function accepts a query string, and an optional second argument
	 * containing an indexed array where positional parameters are used in
	 * the query.
	 *
	 * Example usage:
	 *
	 * 	$db->query('SELECT * FROM `table` WHERE `type` >= ? AND `mother` = ?', array(100, 2));
	 *
	 * @param  string $query  The query to execute.
	 * @param  array  $params An array of values to be passed as positional parameters in the query.
	 * @return mixed          The raw response for this request.
	 */
	public function query($query, $params = null) {
		$this->data['query'] = $query;

		if (isset($params)) {
			$this->data['parameters'] = (array) $params;
		}

		// Reset global 'data' variable.
		$query = $this->data;
		unset($this->data);

		$query['auth'] = Sleepy::get('client', 'authkey');
		$query['sig'] = md5(serialize($query));

		return Sleepy::call('Database', 'Query', $query);
	}

	/**
	 * Specifies that the following 'WHERE', 'WHERE ... IN', 'LIKE' and 'HAVING' calls are to use
	 * OR to seperate clauses.
	 *
	 * This is a hackish workaround due to 'or' being a reserved word in PHP.
	 *
	 * Example usage:
	 *
	 * 	$db->where('id', 1)->or()->where('id', 2)->get('table');
	 *
	 * Produces:
	 *
	 * 	SELECT * FROM `table` WHERE `id` = 1 OR `id` = 2
	 *
	 * @param  string $name The name of the function to call.
	 * @param  mixed  $args Arguments to be passed to function.
	 * @return object       The database object, for chaining commands together.
	 */
	public function __call($name, $args) {
		switch ($name) {
		case 'or':
			$this->data['or'] = true;
			break;
		}

		return $this;
	}

	/**
	 * Initializes a new database query definition.
	 * 
	 * If no database name is passed, the default database for the connecting user is used instead.
	 * 
	 * @param string $database The database to query against.
	 */
	public function __construct($database = null) {
		if (is_string($database)) {
			$this->data['db'] = $database;
		}
	}
}

/* End of file Database.php */
