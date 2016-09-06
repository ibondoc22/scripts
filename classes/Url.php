<?php
/** 
 * Url Class
 * Class for URL handling
 * 
 * @author George Weller <george@ico3.com>
 */
class Url {

	/**
	 * Scheme used for this url if applicable
	 * @var bool | String
	 */
	protected $scheme = false;

	/**
	 * Host used for this url if applicable
	 * @var bool | String
	 */
	protected $host = false;
	
	/**
	 * The path of this url
	 * @var $path Array
	 * @access protected
	 */
	protected $path = array();
	
	/**
	 * The additonal query_string on the page
	 * @var $query_string Array
	 * @access protected
	 */
	protected $query_string = array();

	/**
	 * Static array of additions to all query strings
	 * @var array
	 */
	protected static $all_query_string = array();
	
	/**
	 * Remove url parts from the lookup
	 * @var $strip_extentions Array
	 * @access protected
	 */
	protected $strip_extentions = array( '' );

	/**
	 * Handling for anything that needs to be added as a hash to the url when built
	 * @var string
	 */
	protected $hash = '';
	
	/**
	 * Initualise the function
	 * @access public
	 * @param $url String | Bool
	 */
	public function __construct($url = false){
		//If no url passes then it is the page used
		if($url === false)
			$url = $_SERVER['REQUEST_URI'];
		
		$parsed = parse_url($url);
		
		$this->setPath($parsed['path']);
		
		//set the querystring
		if(isset($parsed['query']))
			parse_str($parsed['query'], $this->query_string);

		if(isset($parsed['scheme']))
			$this->scheme = $parsed['scheme'];

		if(isset($parsed['host']))
			$this->host = $parsed['host'];
	}
	
	/**
	 * Sets the path of this url
	 * @access public
	 * @param $path String
	 */
	public function setPath($path){
		
		if(!is_array($path)){
			//Remove not used slashes
			$path = trim($path,'/');
			//Explode to get the parts
			$path = explode('/',$path);
		}
		
		//save the path
		$this->path = $path;
	}
	
	/**
	 * Magic function called when casting as a string
	 * @access public
	 */
	public function __toString(){
		return $this->getPath() . '/';
	}
	
	/**
	 * Returns the complete url for this page
	 * @param $amp String ampersand query string separator
	 * @access public
	 * @return String
	 */
	public function buildUrl( $amp = '&amp;' ){
		$url = $this->getPath().'/'.$this->getQueryString( $amp );
		$url = str_replace('//','/',$url);
		if( $this->hash ){
			$url .= '#'.$this->hash;
		}
		return $url;
	}
	
	/**
	 * Returns the complete url for this page including the site url befor hand
     * @param $path Bool | String
	 * @param $http String string of the previx to the url defaul 'http://'
	 * @param $amp String ampersand query string separator
	 * @access public
     * @return String
	 */
	public function buildSiteUrl($path = false, $http = 'http://', $amp = '&amp;'){
		global $settings;

		//Handling for the sceme
		if($this->scheme){
			$http = $this->scheme.'://';
		}

		//Build the url
		if( $this->getHost() ){
			$base = $http . $this->getHost();
		}
		else {
			$base = $http . $settings->getSiteUrl();
		}

		if($path)
			return $base.$this->getPath();

		return $base.$this->buildUrl( $amp );
	}
	
	/**
	 * Returns the complete path of this page
	 * @access public
	 */
	public function getPath(){
		$path = implode('/',$this->path);
		$path = trim($path,'/');
		//Failsaif against having a home link anywhere
		if($path == 'home'){
			$path = '';
		}
		return '/'.$path;
	}
	
	/**
	 * Returns the complete path of this page
	 * @param String $amp - The URL parameters separator
	 * @access public
     * @return String
	 */
	public function getQueryString($amp = '&amp;'){
		$query_string_array = static::$all_query_string + $this->query_string;
		$query_string = http_build_query($query_string_array, '', $amp);
		if(!empty($query_string)){
			$query_string = '?'.$query_string;	
		}
		return $query_string;
	}
	
	/**
	 * Returns the complete path of this page
	 * @access public
     * @return String
	 */
	public function getLast(){
		$last = end($this->path);
		$parts = explode('.',$last,2);
		
		if( isset($parts[1]) && in_array($parts[1], $this->strip_extentions) ){
			return $parts[0];
		}
		
		return $last;
	}
	
	/**
	 * Returns part of the path
	 * @access public
	 */
	public function getRawPath(){
		return $this->path;
	}

	/**
	 * Get the host for this url
	 * @return bool|String
	 */
	public function getHost(){
		return $this->host;
	}
	
	/**
	 * Returns part of the path
	 * @access public
     * @param $index Int
     * @return String
	 */
	public function getPathPart($index = 0){
		return $this->path[$index];
	}
	
	/**
	 * Redirect user to this page
	 * @access public
	 * @param $permanent Bool is this a hard redirect
     * @param $check_request Bool Should the be forced
	 */
	public function redirect($permanent = false, $check_request = true){
		$new_url = $this->buildUrl();
		$requested = $_SERVER['REQUEST_URI'];
		
		if($new_url != $requested || !$check_request){
			if($permanent) { header("HTTP/1.1 301 Moved Permanently"); }
			header("Location: ".str_replace('&amp;','&',$new_url));
			exit;
		}
	}
	
	/**
	 * Redirect user to this page
	 * @access public
	 * @param $url String where to send the user
	 */
	public static function redirectTo($url){
		//redirect to a my account page or similar
		$url = new Url($url);
		$url->redirect(false,false);
	}
	
	/**
	 * Constructs a link to the given page
	 * @access public
	 * @param $text String this is what goes in the link
	 * @param $class String | Bool optional to append to link
     * @param $title String | Bool
     * @param $rel String | Bool
	 * @param $extra String
     * @return String
     */
	public function buildAnchor ($text = '', $class = false, $title = false, $rel = false, $extra = ''){
		$url = $this->buildUrl();
		
		if($class)
			$class = ' class="'.$class.'" ';
		
		if($title)
			$title = ' title="'.$title.'" ';
			
		if($rel)
			$rel = ' rel="'.$rel.'" ';
		
		
		return '<a href="'.$url.'" '.$class.$title.$rel.$extra.'>'.$text.'</a>';
	}
	
	/**
	 * Adds a paramater to the query string
	 * @access public
	 * @param $param string key of paramater
	 * @param $value Mixed value of paramater
	 */
	public function addParameter($param, $value){
		if($value === false)
			unset($this->query_string[$param]);
		else
			$this->query_string[$param] = $value;
	}

	/**
	 * Adds a Parameter to all query strings of urls
	 * @access public
	 * @param $param string key of paramater
	 * @param $value Mixed value of paramater
	 */
	public static function addAllParameter($param, $value){
		if($value === false)
			unset(static::$all_query_string[$param]);
		else
			static::$all_query_string[$param] = $value;
	}

	/**
	 * Returns a paramater from the querystring
	 * @access public
	 * @param $param string key of paramater
     * @return Bool | String
	 */
	public function getParameter($param){
		if(isset($this->query_string[$param]))
			return $this->query_string[$param];
		
		return false;
	}
	
	/**
	 * Removes all params for this url
	 * @access public
	 */
	public function clearParameter(){
		$this->query_string = array();
	}
	
	/**
	 * Returns an array of paramasters on this url
	 * @access public
	 * @return Array
	 */
	public function getParameters(){
		return $this->query_string;
	}

	/**
	 * Sets the hash of a url if needed
	 * @param $hash
	 */
	public function setHash( $hash = false ){
		$this->hash = $hash;
	}
}
