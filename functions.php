<?php

//Create an autoloader to save lots of require statements
spl_autoload_register(function($class) {

    $directories = array(
        'classes',
        'libraries'
    );

    foreach($directories as $directory) {

        $path = SITE_ROOT . $directory . '/' . $class . '.php';

        if(file_exists($path)) {
            include_once $path;
            break;
        }

    }

});

/**
 * Debug a variable
 * @param mixed $variable
 * @return void
 */
function pre_r($variable) {

	echo '<pre>';

	if(is_string($variable) || is_numeric($variable)) {
		echo $variable;
	} else {
		print_r($variable);
	}

	echo '</pre>';

}

/**
* Takes an array of errors and produces a ul list
* @param array $errors
* @return String
*/
function outputErrors($errors, $include_message = true) {

   $str = '<div class="alert callout">';

   if($include_message === true) {
	   $str .= '<h4>Please correct the following errors:</h4>';
   }

   if(is_array($errors)) {

	   $str .= '<ul>';

	   foreach($errors as $error) {
		   $str .= '<li>' . $error . '</li>';
	   }

	   $str .= '</ul>';

   } else {
	   $str .= $errors;
   }

   $str .= '</div>';

   return $str;

}

/**
 * get user access level
 * 
 */
function getUserRole($userid){
  switch($userid){
    case '2': 
      return 'Course Provider';
      break;

    case '3':
      return 'admin';
      break;

    default: return 'User';
  }
}

function limitWords($text, $limit = 20){
	if (str_word_count($text, 0) > $limit) {
          $words = str_word_count($text, 2);
          $pos = array_keys($words);
          $text = substr($text, 0, $pos[$limit]) . '...';
    }
    
    return $text;
}