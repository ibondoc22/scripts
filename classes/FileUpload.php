<?php

/**
 * File Upload Class
 * 
 * @author     Steve King <sking@ico3.com>
 */

class FileUpload {
	
	/** @var string $path */
	protected $path;
	
	/** @var array $file */
	protected $file;
	
	/** @var array $valid_extensions */
	protected $valid_extensions = array();
	
	/** @var string $filename */
	protected $filename;
	
	/** @var bool $allow_overwrite */
	protected $allow_overwrite = false;
	
	/** @var bool $rename_if_exists */
	protected $rename_if_exists = true;
	
	/**
	 * Constructor
	 * @param string $path
	 * @param array $file
	 * @return fileUpload
	 * @throws Exception
	 */
	public function __construct($path, $file) {
		
		if(empty($path) || !is_string($path)) {
			throw new Exception('Invalid path provided', '400');
		}
		
		if(empty($file) || !is_array($file)) {
			throw new Exception('Invalid file array provided', '400');
		}
		
		if(isset($file['error']) && $file['error'] != 0) {
			throw new Exception('The file upload contains an error', '403');
		}
		
		$this->path = $path;
		$this->file = $file;
		
		$this->filename = str_replace(' ', '-', $this->file['name']);
		
	}
	
	/**
	 * Returns true or throws an exception depending on if the upload is valid
	 * @return bool
	 * @throws Exception
	 */
	public function validate() {
		
		if(!is_dir($this->path)) {
			throw new Exception('The path provided does not exist', '400');
		}
		
		if(!is_writeable($this->path)) {
			throw new Exception('The path provided is not writeable', '400');
		}
		
		if(file_exists($this->path . $this->filename)) {
			
			if($this->allow_overwrite === false && $this->rename_if_exists === false) {
				throw new Exception('The file ' . $this->filename . ' already exists', '400');
			}
			
		}
		
		if($this->checkExtension() === false) {
			throw new Exception('The file has an invalid extension', '400');
		}
		
		return true;
		
	}
	
	/**
	 * Processes the upload, returning the filename on success
	 * @return string
	 * @throws Exception
	 */
	public function process() {
		
		//Validate the upload
		try {
			$this->validate();
		} catch(Exception $e) {
			throw $e;
		}
		
		$filename = $this->getFilename();
		
		$full_path = $this->path . $filename;
		
		$uploaded = move_uploaded_file($this->file['tmp_name'], $full_path);
		
		if($uploaded === false) {
			throw new Exception('Upload failed', '403');
		}
		
		return $filename;
		
	}
	
	/**
	 * Returns the extension of the uploaded file
	 * @return string
	 */
	public function getExtension() {
		
		$filename = $this->file['name'];
		
		$parts = explode('.', $filename);
		$extension = end($parts);
		
		return strtolower($extension);
		
	}
	
	/**
	 * Sets the valid extensions for the upload
	 * @param array $extensions
	 * @return void
	 */
	public function setValidExtensions($extensions) {
		$this->valid_extensions = $extensions;
	}
	
	/**
	 * Overrides the filename to save as
	 * @param string $filename
	 * @return void
	 */
	public function setFilename($filename) {
		$this->filename = $filename;
	}
	
	/**
	 * Returns true or false depending on if the extension is valid
	 * @return bool
	 */
	protected function checkExtension() {
		
		//If no extensions set, all are valid
		if(empty($this->valid_extensions)) {
			return true;
		}
		
		$extension = $this->getExtension();
		
		return in_array($extension, $this->valid_extensions) ? true : false;
		
	}
	
	/**
	 * Returns the filename, generating a new one if required
	 * @return string
	 */
	public function getFilename() {
		
		$filename = $this->filename;
		
		if(file_exists($this->path . $filename) && $this->rename_if_exists === true) {
		
			//Explode the filename, pop off the extension and put it back together
			$parts = explode('.', $filename);

			$extension = array_pop($parts);

			$base_filename = implode('.', $parts);
			
			$count = 1;
			
			do {
				$count++;
			} while(file_exists($this->path . $base_filename . '_' . $count . '.' . $extension));
			
			$filename = $base_filename . '_' . $count . '.' . $extension;
		
		}
		
		return $filename;
		
	}
	
	/**
	 * Sets the rename if exists setting to what's sent in
	 * @param bool $setting
	 * @return void
	 */
	public function renameIfExists($setting = true) {
		$this->rename_if_exists = $setting;
	}
	
	/**
	 * Sets the allow overwrite setting to what's sent in
	 * @param bool $setting
	 * @return void
	 */
	public function allowOverwrite($setting = true) {
		$this->allow_overwrite = $setting;
	}

}
