<?php

/**
 * Database Entity class
 * Shared functionality for database entities
 *
 * @author Steve King <sking@ico3.com>
 */

abstract class DatabaseEntity {

	/**
	 * The database connection to use
	 * @var PDO $_db
	 */
	protected $_db;

	/**
	 * The table name the entities are stored in
	 * @var string $_table
	 */
	protected static $_table;

	/**
	 * The primary key of the table
	 * @var string $_table
	 */
	protected static $_primary_key = 'id';

	/**
	 * Any fields which aren't defined will be held in this array
	 * @var array $_other
	 */
	protected $_other = array();

	/**
	 * Constructor
	 * @param PDO $db
	 * @param int|array $identifier
	 * @throws Exception
	 */
	public function __construct(PDO $db, $identifier = array()) {

		//Make sure the table is set
		if(!isset(static::$_table) || empty(static::$_table)) {
			throw new Exception('No table set for class ' . get_called_class());
		}

		//Set the database object
		$this->_db = $db;

		//If the identifier is an id
		if(is_numeric($identifier)) {

			//Query the table to get the matching row
			$statement = $this->_db->prepare("
				SELECT *
				FROM `" . static::$_table . "`
				WHERE `" . static::$_primary_key . "` = ?
			");

			$statement->execute(array($identifier));

			$identifier = $statement->fetch(PDO::FETCH_ASSOC);

		}

		//If the identifier is an array
		if(is_array($identifier)) {
			$this->initialiseFromRow($identifier);
		} else {
			throw new Exception('Failed to load class ' . get_called_class());
		}

	}

	/**
	 * Magic function when not found method is called
	 * Designed to allow use of a get{Field}
	 * without having to declair a function name
	 * @param $name string function name
	 * @param $arguments array Arguments being passed
	 * @access public
     * @return Mixed
	 */
	public function __call($name, $arguments){

		$field = static::camelUnderscore(substr($name,3));

		if(substr($name,0,3) == 'get'){

			if($this->hasVariable($field)) {
				return $this->$field;
			} else if(array_key_exists($field, $this->other)) {
				return $this->other[$field];
			}

		} elseif(substr($name,0,3) == 'set'){

			if($this->hasVariable($field)) {
				$this->$field = $arguments[0];
			} else {
				$this->other[$field] = $arguments[0];
			}

		} else{
			die('Function not found: '.$name);
		}
	}

	/**
	 * Saves the record to the database
	 * @return void
	 */
	public function save() {

		$primary = $this->getPrimaryKey();

		if(!empty($primary)) {
			$this->update();
		} else {
			$this->insert();
		}

	}

	/**
	 * Runs an update on the row this object represents
	 * @return void
	 */
	public function update() {

		//Get the primary key field
		$primary = static::$_primary_key;

		//If it's empty, we can't update
		if(empty($this->$primary)) {
			return false;
		}

		//Start the SQL
		$sql = "
			UPDATE `" . static::$_table . "`
			SET
				";

		//Start the params array
		$params = array();

		//Loop through each field / value
		foreach($this->toArray() as $field => $value) {

			//Skip the primary key field
			if($field == $primary) {
				continue;
			}

			//Add to the sql
			$sql .= '`' . $field . '` = ?, ';

			//Add to the params
			$params[] = $value;

		}

		//Get rid of the last comma
		$sql = rtrim($sql, ', ');

		//Add the where
		$sql .= "
			WHERE `{$primary}` = ?
			LIMIT 1
		";

		//Add the primary key to the params
		$params[] = $this->$primary;

		//Prepare the statement
		$statement = $this->_db->prepare($sql);

		//Run the update
		$statement->execute($params);

		return true;

	}

	/**
	 * Inserts the object in to the database
	 * @return void
	 */
	public function insert() {

		//Get the primary key
		$primary = static::$_primary_key;

		//Convert the object to an array
		$data = $this->toArray();

		//Define the arrya to hold the parameters
		$params = array();

		//Remove the primary key
		unset($data[$primary]);

		//Add the params
		foreach($data as $field => $value) {

			//If it's the primary key or it has no value, unset the field
			if($field == $primary || empty($value)) {
				unset($data[$field]);
			} else {
				$params[] = $value;
			}

		}

		//Build the SQL
		$statement = $this->_db->prepare("
			INSERT INTO `" . static::$_table . "`
				(`" . implode('`, `', array_keys($data)) . "`)
			VALUES
				(" . rtrim(str_repeat('?, ', count($params)), ', ') . ")
		");

		//Run the insert
		$statement->execute($params);

		//Set the primary key to be the insert id
		$this->$primary = $this->_db->lastInsertId();

	}

	/**
	 * Deletes the row from the database
	 * @return bool
	 */
	public function delete() {

		//Get the primary key
		$primary = static::$_primary_key;

		//If it's empty, we can't delete
		if(empty($this->$primary)) {
			return false;
		}

		//Build the sql
		$statement = $this->_db->prepare("
			DELETE FROM `" . static::$_table . "`
			WHERE `" . $primary . "` = ?
			LIMIT 1
		");

		//Run the delete
		return $statement->execute(array($this->$primary));

	}

	/**
	 * Returns an array of the entities fields
	 * @return array
	 */
	public function toArray() {

		$array = array();

		$skip = array();

		foreach(get_object_vars($this) as $index => $value) {

			if(!in_array($index, $skip) && substr($index, 0, 1) != '_') {
				$array[$index] = $value;
			}

		}

		return $array;

	}

	/**
	 * Method to validate the current object
	 * @return bool
	 */
	public function validates() {
		return true;
	}

	/**
	 * This function initialises an instance of an object from an array
	 * @param array $row
	 * @return void
	 */
	public function initialiseFromRow($row) {

		foreach($row as $attribute => $value) {

			if($this->hasVariable($attribute)) {
				$this->$attribute = $value;
			} else {
				$this->_other[$attribute] = $value;
			}

		}

	}

	/**
	 * Checks if the object has a variable called $attribute or not
	 * @param string $attribute
	 * @return bool
	 */
	protected function hasVariable($attribute) {

		$object_vars = get_object_vars($this);

		return array_key_exists($attribute, $object_vars);

	}

	/**
	 * Conversts a camelcase string to have underscores
	 * @example camelUnderscore("iLikeToTest") = "i_like_to_test"
	 * @param string $string - string to be converted
	 * @return string
	 */
	public static function camelUnderscore($string){
		//split on capital letters
		$pieces = preg_split('/(?=[A-Z0-9])/',$string);
		//Remove any empty items
		$pieces = array_filter($pieces);
		//Implode the pieces of the string
		$return_string = implode('_',$pieces);
		//make the string lower case
		$return_string = strtolower($return_string);
		//Return the new string
		return $return_string;
	}

	/**
	 * Conversts a underscores string to have camelcase
	 * @example underscoreCamel("i_like_to_test") = "iLikeToTest"
	 * @param string $string - string to be converted
	 * @return string
	 */
	public static function underscoreCamel($string){
		//Replace current underscores with spaces
		$string = str_replace('_',' ',$string);
		//Strip out any capitals
		$string = strtolower($string);
		//Uppercase first letter
		$string = ucwords($string);
		//Remove the spaces we left
		$string = str_replace(' ','',$string);
		//Return the new string
		return $string;
	}

	/**
	 * Loads every row from the table and returns an array of objects
	 * @param PDO $db
	 * @param string $order
	 * @return mixed
	 */
	public static function loadAll(PDO $db, $order = '') {

		//Prepare the sql
		$sql = "
			SELECT *
			FROM " . static::$_table . "
			{$order}
		";

		//Use loadFromSql to load the objects
		return static::loadFromSql($db, $sql);

	}

	/**
	 * Loads objects from an SQL query
	 * @param Database $db
	 * @param string $sql
	 * @param array $params
	 * @return array
	 */
	public static function loadFromSql($db, $sql, $params = array(), $single = false) {

		//This array will hold the objects to be returned
		$objects = array();

		//Get the called class so we know what object to load
		$class = get_called_class();

		//Run the sql
		$result = $db->aquery($sql, $params);

		if($single){
			return  new $class($db, $result);
		}

		//Loop through the results and load an object for each row
		foreach($result as $row) {
			$objects[] = new $class($db, $row);
		}

		return $objects;

	}

	/**
	 * Returns the value of the primary key
	 * @return int
	 */
	public function getPrimaryKey() {

		$key = static::$_primary_key;

		return $this->$key;

	}

}
