<?php

/**
 * Session class
 *
 * @author Steve King <sking@ico3.com>
 */

class Session {

	/**
	 * Constructor
	 * Starts the session
	 * @return Session
	 */
	public function __construct() {
		if (session_status() == PHP_SESSION_NONE) {
		    session_start();
		}
	}

	/**
	 * Returns whether there is a logged in user or not
	 * @return bool
	 */
	public function isLoggedIn() {
		return isset($_SESSION['user_id']) ? true : false;
	}

	/**
	 * Returns the id of the logged in user or 0
	 * @return int
	 */
	public function getUserId() {
		return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
	}


	public function getUserLevel(){
		return isset($_SESSION['uLevel']) ? $_SESSION['uLevel'] : 0;
	}

	/**
	 * Checks if the user is logged in and redirects to the login page if not
	 * @param string $redirect - Override the page to redirect to
	 * @return boolean
	 */
	public function checkLogin($redirect = '/login') {

		if($this->isLoggedIn() === false) {

			$url = new Url($redirect);
			$url->redirect();

		}

		return true;

	}

}
