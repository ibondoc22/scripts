<?php

/**
 * Database class
 * An extension of the PDO class
 * 
 * @author Steve King <sking@ico3.com>
 */

class Database extends PDO {
	
	/**
	 * Runs an SQL statement and returns the results
	 * @param string $sql
	 * @param array $params
	 * @return array
	 */
	public function getArray($sql, $params = array()) {
		$statement = $this->prepare($sql);
		$statement->execute($params);
		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}
	
	/**
	 * Alias for getArray
	 * @see $this->getArray
	 */
	public function aquery($sql, $params = array()) {
		return $this->getArray($sql, $params);
	}
	
	/**
	 * Runs an SQL statement and returns a single row
	 * @param string $sql
	 * @param array $params
	 * @return array
	 */
	public function getRow($sql, $params = array()) {
		$statement = $this->prepare($sql);
		$statement->execute($params);
		return $statement->fetch(PDO::FETCH_ASSOC);
	}
	
	/**
	 * Alias for getRow
	 * @see $this->getRow
	 */
	public function rquery($sql, $params = array()) {
		return $this->getRow($sql, $params);
	}
	
	/**
	 * Runs an SQL statement and returns the first field of the result
	 * @param string $sql
	 * @param array $params
	 * @return mixed
	 */
	public function getField($sql, $params) {
		$result = $this->getRow($sql, $params);
		return is_array($result) ? array_shift($result) : false;
	}
	
	/**
	 * Alias for getField
	 * @see $this->getField
	 */
	public function fquery($sql, $params = array()) {
		return $this->getField($sql, $params);
	}
	
}
