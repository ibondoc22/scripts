<?php

/**
 * Validator Class
 * Used to conduct validation on a dataset
 *
 * @author     Steve King <sking@ico3.com>
 */
class Validator {

	/**
	 * Array of data to be validated
	 * @var Array $data;
	 */
	protected $data;

	/**
	 * Field withing data to validate
	 * @var ValidatorField[] $fields
	 */
	protected $fields;

	/**
	 * Rules used
	 * @var Array $rules
	 */
	protected $rules = array();

	/**
	 * Errors found
	 * @var Array $errors
	 */
	protected $errors = array();

	/**
	 * Constructor
	 * @param Array $data
	 */
	function __construct($data) {
		$this->data = $data;
	}

	/**
	 * Adds a new field to $this->fields
	 * @param String $field
	 * @param String $display_name - Optional
	 * @return ValidatorFieldCore
	 */
	public function addField($field, $display_name = false) {

		//Make sure $field is set in $this->data
		if(!isset($this->data[$field])) {
			$this->data[$field] = '';
		}

		//Default $display_name to $field if not set
		if($display_name === false) {
			$display_name = str_replace("_", " ", $field);
		}

		//Add a new ValidatorFieldCore if there isn't already one
		if(!isset($this->fields[$field])) {
			$this->fields[$field] = new ValidatorField($this->data[$field], $display_name);
			return $this->fields[$field];
		} else {
			return $this->fields[$field];
		}
	}

	/**
	 * Sets a rule to say that 2 fields must match
	 * @param String $field1
	 * @param String $field2
	 * @return Validator
	 */
	public function match($field1, $field2) {
		$this->rules['match'] = $field1 . ',' . $field2;
		return $this;
	}

	/**
	 * Loops through each field and checks if they pass
	 * @return Bool
	 */
	public function passes() {

		$valid = true;

		foreach ($this->fields as $field) {

			if($field->passes() === false) {

				$valid = false;
				$this->errors[] = $field->getError();
			}
		}

		if($this->validate() === false) {
			$valid = false;
		}

		return $valid;
	}

	/**
	 * Runs through the rules and validates them
	 * Populates the array $this->errors
	 * @return Bool
	 */
	public function validate() {

		$valid = true;

		foreach ($this->rules as $rule => $value) {

			switch ($rule) {

				case 'match' :

					//$value is comma separated so explode
					list($field1, $field2) = explode(',', $value);

					//Make sure they are both set
					if(isset($this->fields[$field1]) && isset($this->fields[$field2])) {

						if($this->fields[$field1]->getValue() != $this->fields[$field2]->getValue()) {
							$valid = false;
							$this->errors[] = $this->fields[$field1]->getName() . ' does not match ' . $this->fields[$field2]->getName();
						}
					}

					break;
			}
		}

		return $valid;
	}

	/**
	 * Validates an email address and returns true or false
	 * @param String $email
	 * @return Bool
	 */
	public static function validEmail($email) {

		//Use PHP's Filter to validate email addresses
		$valid = filter_var($email, FILTER_VALIDATE_EMAIL) === false ? false : true;

		if($valid === true) {

			//Get the domain from the email address (In 2 steps to prevent Strict warning)
			$domain_parts = explode('@', $email);
			$domain = end($domain_parts);

			//Check the MX records for this domain
			$valid = checkdnsrr($domain, 'MX');
		}

		return $valid;
	}

	/**
	 * Validates a phone number and returns true or false
	 * @param String $number
	 * @return Bool
	 */
	public static function validPhone($number) {

		//remove all non numeric characters
		$number = preg_replace("/[^0-9]/", "", $number);

		$len = strlen($number);

		//"hack" to allow international numbers
		if(!is_numeric($number) || $len > 15 || $len < 7) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Validates a mobile number and returns true or false
	 * @param String $number
	 * @return Bool
	 */
	public static function validMobile($number) {

		$pattern = "/^(\+44\s?7\d{3}|\(?07\d{3}\)?)\s?\d{3}\s?\d{3}$/";

		$match = preg_match($pattern, $number);

		return $match === 0 ? false : true;
	}

	/**
	 * Validates a username and returns true or false
	 * Regex ensures the username starts with a letter and only contains letters, numbers and underscores
	 * @param String $username
	 * @return Bool
	 */
	public static function validUsername($username) {

		$pattern = "/^([a-z0-9]+_)*[a-z0-9]+$/i";

		$match = preg_match($pattern, $username);

		return $match === 0 ? false : true;
	}

	/**
	 * Validates a postcode and returns true or false
	 * NOTE - does UK only postcodes
	 * @param String $postcode
	 * @return Bool
	 */
	public static function validPostcode($postcode) {
		$postcode = strtoupper($postcode);

		$pattern = "#^(GIR ?0AA|[A-PR-UWYZ]([0-9]{1,2}|([A-HK-Y][0-9]([0-9ABEHMNPRV-Y])?)|[0-9][A-HJKPS-UW]) ?[0-9][ABD-HJLNP-UW-Z]{2})$#";

		$match = preg_match($pattern, $postcode);

		return $match === 0 ? false : true;
	}

	/**
	 * Validates a barcode to ensure it is correct
	 * @param String $bar_code
	 * @param Bool | Array $lengths What length barcode should be checked
	 * @return Bool
	 */
	public static function validBarcode($bar_code, $lengths = false) {

		$parts = str_split($bar_code);
		$parts = array_reverse($parts);

		$check = array_shift($parts);
		$alt = false;

		$total = 0;
		foreach ($parts as $part) {
			if($alt) {
				$total += $part;
				$alt = false;
			} else {
				$total += $part * 3;
				$alt = true;
			}
		}

		$target_check = (10 - ($total % 10));

		//Is valid
		$valid = ($target_check == $check);

		if($lengths && !in_array(strlen($bar_code), $lengths)) {
			$valid = false;
		}

		return $valid;
	}

	/**
	 * Validates a password and returns true or false
	 * @param String $password
	 * @return Bool
	 */
	public static function validPassword($password) {

		//TODO
		return true;
	}

	/**
	 * Returns the array $this->errors
	 * @return Array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Validates a supported SMS country and returns true or false
	 * @param String $password
	 * @return Bool
	 */
	public static function validSmsCountry($country) {

		//TODO: add this to a db table
		return $country == "GBR" ? true : false;
	}

	/**
	 * Returns a clean dataset removing any fields which are not in the pre defined list
	 * @return Array
	 */
	public function getFieldsData() {
		$fields_data = array();
		foreach ($this->fields as $field) {
			$fields_data[$field->getName()] = $field->getValue();
		}
		return $fields_data;
	}

}
