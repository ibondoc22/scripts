<?php

/**
 * ValidatorField class
 * Used when validating a single field
 *
 * @author     Steve King <sking@ico3.com>
 */

class ValidatorField {

	/**
     * Value of the field
	 * @var Mixed $value
	 */
	protected $value;

	/**
	 * @var String $field_name
	 * The name of the field, could be the form input or a display name
	 */
	protected $field_name;

	/**
     * Array of active rulles
	 * @var Array $rules
	 */
	protected $rules = array();

	/**
     * String or error
	 * @var String $error
	 */
	protected $error;

	/**
	 * Constructor
	 * @param Mixed $value
	 * @param String $field_name
	 */
	function __construct($value, $field_name) {

		$this->value = $value;
		$this->field_name = $field_name;

	}

	/**
	 * Returns $this->value
	 * @return Mixed
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Returns $this->field_name
	 * @return Mixed
	 */
	public function getName() {
		return $this->field_name;
	}

	/**
	 * Sets a rule to say this field is required
	 * @return ValidatorField
	 */
	public function required() {
		$this->rules['required'] = true;
		return $this;
	}

	/**
	 * Sets a rule to say this field is required if not empty
	 * @return ValidatorField
	 */
	public function requiredNotEmpty() {
		$value = $this->getValue();
		if(!empty($value)){
			$this->rules['required'] = true;
		}
		return $this;
	}

	/**
	 * Sets a rule for beign an integer
	 * @return ValidatorField
	 */
	public function int() {
		$this->rules['int'] = true;
		return $this;
	}
	/**
	 * Sets a rule for the minimum a numeric value can be
	 * @param Int|Float $value
	 * @return ValidatorField
	 */
	public function min($value) {
		$this->rules['min'] = $value;
		return $this;
	}

	/**
	 * Sets a rule for the maximum a numeric value can be
	 * @param Int|Float $value
	 * @return ValidatorField
	 */
	public function max($value) {
		$this->rules['max'] = $value;
		return $this;
	}

	/**
	 * Sets a rule for the minimum length a string can be
	 * @param Int $value
	 * @return ValidatorField
	 */
	public function minLength($value) {
		$this->rules['min_length'] = (Int) $value;
		return $this;
	}

	/**
	 * Sets a rule for the maximum length a string can be
	 * @param Int $value
	 * @return ValidatorField
	 */
	public function maxLength($value) {
		$this->rules['max_length'] = (Int) $value;
		return $this;
	}

	/**
	 * Sets a rule to say the field must be a valid email address
	 * @return ValidatorField
	 */
	public function email() {
		$this->rules['email'] = true;
		return $this;
	}

	/**
	 * Sets a rule to say the field must be a valid phone number
	 * @return ValidatorField
	 */
	public function phone() {
		$this->rules['phone'] = true;
		return $this;
	}

	/**
	 * Sets a rule to say the field must be a valid mobile
	 * @return ValidatorField
	 */
	public function mobile() {
		$this->rules['mobile'] = true;
		return $this;
	}

	/**
	 * Sets a rule to say the field must be a valid username
	 * @return ValidatorField
	 */
	public function username() {
		$this->rules['username'] = true;
		return $this;
	}

	/**
	 * Sets a rule to say the field must be a valid password
	 * @return ValidatorField
	 */
	public function password() {
		$this->rules['password'] = true;
		return $this->minLength(6);
	}

	/**
	 * Sets a rule to say the field must be a valid postcode
	 * @return ValidatorField
	 */
	public function postcode() {
		$this->rules['postcode'] = true;
		return $this;
	}

	/**
	 * Sets a rule to say the field must be a valid sms country
	 * @return ValidatorField
	 */
	public function validSmsCountry() {
		$this->rules['sms_country'] = true;
		return $this;
	}

	/**
	 * Sets a rule to say the value must not already exist in a table
	 * @param $table String
	 * @param $field String
	 * @return ValidatorField
	 */
	public function unique($table, $field) {
		$this->rules['unique'] = $table . ',' . $field;
		return $this;
	}

	/**
	 * Sets a rule to say the value must not already exist in a table
	 * @param $table String
	 * @param $field String
	 * @param $where
	 * @return ValidatorField
	 */
	public function uniqueWhere($table, $field, $where, $operator = '=') {
		$this->rules['unique_where'] = array(
			'table' => $table,
			'field' => $field,
			'where' => $where,
			'operator' => $operator
		);
		return $this;
	}

	/**
	 * Sets a rule to say the value must not already exist in a table
	 * @param $input String
	 * @return ValidatorField
	 */
	public function minWords($input) {
		$this->rules['min_words'] = $input;
		return $this;
	}

	/**
	 * Checks if the value is in a specific table
	 * Default field is id but this can be sent in if you want a different field
	 * @param string $table
	 * @param string $field
	 * @return ValidatorField
	 */
	public function inTable($table, $field = 'id') {
		$this->rules['in_table'] = array(
			'table' => $table,
			'field' => $field
		);
		return $this;
	}

	/**
	 * Loops through all of the rules and checks the value against each of them
	 * @return Bool
	 */
	public function passes() {

		$valid = true;

		//If there is a value or the field is required or captcha
		if(!empty($value) || isset($this->rules['required']) || isset($this->rules['captcha'])) {

			//Loop through each of the rules
			foreach($this->rules as $rule => $value) {

				//Run it through the validateRule function
				if($this->validateRule($rule, $value) === false) {
					$valid = false;
				}

			}
		}

		return $valid;

	}

	/**
	 * Validates a specific rule against the rule sets
	 * @param String $rule
	 * @param Mixed $value
	 * @return Bool
	 */
	protected function validateRule($rule, $value) {

		$valid = true;

		switch($rule) {

			case 'required':

				if(strlen($this->value) == 0) {
					$valid = false;
					$this->appendError('has been completed');
				}

				break;

			case 'int':

				if(!is_numeric($this->value)) {
					$valid = false;
					$this->appendError('is a number');
				}

				break;

			case 'min':

				if($this->value < $value) {
					$valid = false;
					$this->appendError('is greater than ' . $value);
				}

				break;

			case 'max':

				if($this->value > $value) {
					$valid = false;
					$this->appendError('is less than ' . $value);
				}

				break;

			case 'min_length':

				if(strlen($this->value) < $value) {
					$valid = false;
					$this->appendError('is a minimum of ' . $value . ' characters');
				}

				break;

			case 'max_length':

				if(strlen($this->value) > $value) {
					$valid = false;
					$this->appendError('is a maximum of ' . $value . ' characters');
				}

				break;

			case 'email':

				if(Validator::validEmail($this->value) === false) {
					$valid = false;
					$this->appendError('is a valid email address');
				}

				break;

			case 'captcha':

				if(Validator::validCaptcha($this->value) === false) {
					$valid = false;
					$this->appendError('has been completed');
				}

				break;

			case 'phone':

				if(Validator::validPhone($this->value) === false) {
					$valid = false;
					$this->appendError('is a valid phone number');
				}

				break;

			case 'mobile':

				if(Validator::validMobile($this->value) === false) {
					$valid = false;
					$this->appendError('is a valid UK mobile number');
				}

				break;

			case 'username':

				//Check it's either a username or an email address
				$valid_username = Validator::validUsername($this->value);
				$valid_email = Validator::validEmail($this->value);

				if($valid_username === false && $valid_email === false) {
					$valid = false;
					$this->appendError('is an email address or contains only letters, numbers and underscores and doesn\'t start or end with an _');
				}

				break;

			case 'password':

				if(Validator::validPassword($this->value) === false) {
					$valid = false;
					$this->appendError('is a valid password');
				}

				break;

			case 'postcode':

				if(Validator::validPostcode($this->value) === false) {
					$valid = false;
					$this->appendError('is a valid postcode');
				}

				break;

			case 'unique':

				list($table, $field) = explode(',', $value);

				global $db;

				$sql = "
					SELECT COUNT(*)
					FROM `{$table}`
					WHERE `{$field}` = ?
				";

				$count = $db->fquery($sql, array($this->value));

				if($count > 0) {
					$valid = false;
					$this->appendError('The ' . $this->field_name . ' you entered already exists', true);
				}

				break;

			case 'unique_where':
				//populate into $data for easy reading
				$data = $value;

				//populate fields
				$table = $data['table'];
				$field = $data['field'];
				$where = $data['where'];
				$operator = $data['operator'];

				global $db;

				$sql = "
					SELECT COUNT(*)
					FROM `{$table}`
					WHERE `{$field}` = ?
				";

				$params = array($this->value);

				foreach($where as $field => $value) {

					$sql .= "
						AND `{$field}` {$operator} ?
					";

					$params[] = $value;

				}

				$count = $db->fquery($sql, $params);

				if($count > 0) {
					$valid = false;
					$this->appendError('The ' . $this->field_name . ' you entered already exists', true);
				}

				break;

			case 'sms_country':

				if(Validator::validSmsCountry($this->value) === false) {
					$valid = false;
					$this->appendError('Currently we do not support SMS to your country: '.$this->value, true);
				}

				break;

			case 'min_words':
				$array = explode(" ", $this->value);

				//ensure commas are not counted etc
				foreach($array as $key => $word){
					if( !preg_match('/([a-z|A-Z|0-9]+)/i', $word)){
						//no alpha numericas found, remove form check
					    unset($array[$key]);
					}
				}

				if(count($array) < $value){
					$valid = false;
					$diff = $value - count($array);
					$this->appendError('Please enter a minimum of '.$value.' words ('.$diff.' more required) for '.$this->field_name, true);
				}

				break;

			case 'in_table':

				global $db;

				$table = $value['table'];
				$field = $value['field'];

				$sql = "
					SELECT COUNT(*)
					FROM `{$table}`
					WHERE `{$field}` = ?
				";

				if($db->fquery($sql, array($this->value)) == '0') {
					$valid = false;
					$this->appendError('Please select a valid ' . $this->field_name, true);
				}

				break;

		}

		return $valid;

	}

	/**
	 * Returns the error string
	 * @return String
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * Appends rule error output to $this->error string
	 * @param String $error
	 * @param Bool $no_prefix - Optional, do/don't prefix error with anything
	 */
	protected function appendError($error, $no_prefix = false) {

		if($no_prefix === true) {
			$pre = empty($this->error) ? '' : '. ';
			$this->error .= $pre . $error;
		} else if(empty($this->error) && $this->field_name == 'rating') {
               $this->error = 'Please provide a rating using the stars';
        } else if(empty($this->error)) {
			$this->error = 'Please ensure the ' . $this->field_name . ' field ' . $error;
		} else if($this->field_name != 'rating') {
			$this->error .= ', and ' . $error;
		}

	}

}
