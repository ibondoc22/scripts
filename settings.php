<?php

//Set a constant for the site root
define('SITE_ROOT', dirname(__FILE__) . '/');

//Include the functions file (includes autoloader)
require_once SITE_ROOT . 'functions.php';

//Start a session
$session = new Session();

if(defined("DB_HOST") && defined("DB_NAME") && defined("DB_USER") && defined("DB_PASS")){
	try {
		$db = new Database('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
	} catch (Exception $ex) {
		exit('Failed to connect to the database');
	}
}
