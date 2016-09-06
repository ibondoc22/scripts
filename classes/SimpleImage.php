<?php

/**
 * Image handling (scale/crop/resize).
 *
 * @author George Weller <george@ico3.com>
 */

class SimpleImage {

	private $image = null;
	private $maxCacheAge;
	private $foundPath = '';

	/** Constructor.
	 *  @param file If file is given, load image from file.
	 */
	public function __construct($file = null) {
		if($file != null) {
			$this->load($file);
		}
		$this->maxCacheAge = 14 * 24 * 60 * 60; // 14 days
	}

	/** Load image from file.
	 *  @param filename The file to load
	 *  @param expires (optional) unix timestamp, return false if the file is older than this.
	 */
	public function load($filename, $expires = null) {
		if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
			if($expires < strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
				header("HTTP/1.1 304 Not Modified");
				exit;
			}
		}
		if(!file_exists($filename)) {
			return null;
		}
		if($expires != null) {
			$expireTime = max($expires, time() - $this->maxCacheAge);
			if(filemtime($filename) < $expireTime) {
				return null;
			}
		}
		$image_info = getimagesize($filename);
		$image_type = $image_info[2];
		if($image_type == IMAGETYPE_JPEG) {
			$this->image = imagecreatefromjpeg($filename);
		} else if($image_type == IMAGETYPE_GIF) {
			$this->image = imagecreatefromgif($filename);
		} else if($image_type == IMAGETYPE_PNG) {
			$this->image = imagecreatefrompng($filename);
		} else if($image_info === false) {
			echo $filename;
		}
		return true;
	}

	/** Store this image in a file. */
	public function save($filename, $image_type = IMAGETYPE_JPEG, $compression = 100, $permissions = null) {
		if($filename == false || $this->image == null) {
			return false;
		}
		if($image_type == IMAGETYPE_JPEG) {
			imagejpeg($this->image, $filename, $compression);
		} else if($image_type == IMAGETYPE_GIF) {
			imagegif($this->image, $filename);
		} else if($image_type == IMAGETYPE_PNG) {
			imagepng($this->image, $filename);
		}
		if($permissions != null) {
			chmod($filename, $permissions);
		}
	}

	/** Output the contents of this image. */
	public function output($image_type = IMAGETYPE_JPEG) {
		if($this->image == null) {
			return false;
		}
		header("Cache-Control: max-age=604800, public");
		header("Expires: " . self::gmtDate($_SERVER['REQUEST_TIME'] + 604800));
		header("ETag: " . $_GET['f']);
		header("Last-Modified: " . self::gmtDate($_SERVER['REQUEST_TIME']));
		if($image_type == IMAGETYPE_JPEG) {
			header('Content-Type: image/jpeg');
			imagejpeg($this->image, NULL, 100);
		} else if($image_type == IMAGETYPE_GIF) {
			imagegif($this->image);
		} else if($image_type == IMAGETYPE_PNG) {
			imagepng($this->image);
		}
	}

	/** Return the width of this image. */
	public function getWidth() {
		if($this->image == null) {
			return 0;
		} else {
			return imagesx($this->image);
		}
	}

	/** Return the height of this image. */
	public function getHeight() {
		if($this->image == null) {
			return 0;
		} else {
			return imagesy($this->image);
		}
	}

	/** Resize the image to fit new dimensions.
	 *  The image will only be scaled down, never up.
	 *  @param rw Requested width, or null for unrestricted. May start with 'm' for maximum.
	 *  @param rh Requested height, or null for unrestricted. May start with 'm' for maximum.
	 *  @param mode What to do when the aspect ratio differs between input and output.
	 *  Pass null to use the default.
	 *  "back" (the default) will fill empty space with the background color.
	 *  "crop" will crop to fit
	 *  @param rgb background color in format understood by getRgb()
	 */
	public function resize($rw, $rh = null, $mode = null, $rgb = null) {
		if($this->image == null) {
			return false;
		}
		if(empty($mode)) {
			$mode = "back";
		}
		// sw, sh source width/height
		// rw, rh requested width/height
		// nw, nh new width/height (the paste area for the image)
		// sx, sy source render position
		// nx, ny new render position
		$sw = $this->getWidth();
		$sh = $this->getHeight();
		if(substr($rw, 0, 1) == "m") {
			$rw = substr($rw, 1);
			$wmax = true;
		} else {
			$wmax = false;
		}
		if(substr($rh, 0, 1) == "m") {
			$rh = substr($rh, 1);
			$hmax = true;
		} else {
			$hmax = false;
		}
		$ratio = $this->getRatio($mode, $rw, $rh, $wmax, $hmax);
		if($ratio > 1) {
			// Don't EVER scale up.
			$ratio = 1;
		}
		if($rw == null || $wmax) {
			$rw = $sw * $ratio;
		}
		if($rh == null || $hmax) {
			$rh = $sh * $ratio;
		}
		//trigger_error("ratio $ratio rw $rw rh $rh");
		$nw = $ratio * $sw;
		$nh = $ratio * $sh;
		$nx = ($rw - $sw * $ratio) / 2;
		$ny = ($rh - $sh * $ratio) / 2;
		// We're cheating here. Instead of cropping the image
		// using sx and sy; nx and ny will be negative, causing
		// the cropped portion of the image to be drawn offscreen.
		$sx = 0;
		$sy = 0;
		$new_image = imagecreatetruecolor($rw, $rh);
		$this->fillColour($new_image, $rgb);
		imagecopyresampled($new_image, $this->image, $nx, $ny, $sx, $sy, $nw, $nh, $sw, $sh
		);
		//trigger_error("$nx, $ny, $sx, $sy; $nw, $nh, $sw, $sh");
		$this->image = $new_image;
	}

	/** Fill canvas using the specified color. */
	private function fillColour($image, $rgb) {
		$r = $this->getRgb($rgb);
		$c = imagecolorallocate($image, $r['r'], $r['g'], $r['b']);
		imagefill($image, 0, 0, $c);
	}

	/** Return an array with keys "r", "g", "b" and values
	 *  determined from the RGB hex string (e.g. "#FFF" or "#FFFFFF").
	 *  If hex is null, use white.
	 *  If parse error, use black.
	 */
	public static function getRgb($hex) {
		if($hex == null) {
			$hex = "#FFF";
		}
		$xc = "[A-Fa-f0-9]";
		$m = array();
		if(preg_match("%^#?($xc$xc)($xc$xc)($xc$xc)$%", $hex, $m)) {
			$r['r'] = hexdec($m[1]);
			$r['g'] = hexdec($m[2]);
			$r['b'] = hexdec($m[3]);
		} else if(preg_match("%^#?($xc)($xc)($xc)$%", $hex, $m)) {
			$r['r'] = hexdec($m[1] . $m[1]);
			$r['g'] = hexdec($m[2] . $m[2]);
			$r['b'] = hexdec($m[3] . $m[3]);
		} else {
			//Log::error("No match for hex '$hex', defaulting to black.");
			$r['r'] = 0;
			$r['g'] = 0;
			$r['b'] = 0;
		}
		return $r;
	}

	/** Return hex RGB string from decimal r, g, b values. */
	public static function getHex($r, $g, $b) {
		$r = substr(dechex($r), 0, 2);
		$g = substr(dechex($g), 0, 2);
		$b = substr(dechex($b), 0, 2);
		return "#$r$g$b";
	}

	/** Return ratio for image scaling.
	 *  @param mode what to do with aspect ratio mismatch: "crop" or "back".
	 *  @param width width
	 *  @param height height
	 *  @param widthmax true if width is a maximum
	 *  @param heightmax true if height is a maximum
	 */
	private function getRatio($mode, $width, $height, $widthmax, $heightmax) {
		$sw = $this->getWidth();
		$sh = $this->getHeight();
		if($widthmax && $sw < $width) {
			$width = $sw;
		}
		if($heightmax && $sw < $height) {
			$height = $sh;
		}
		if($sw == null) {
			$wratio = null;
		} else {
			$wratio = ($width == null) ? null : $width / $sw;
		}
		if($sh == null) {
			$hratio = null;
		} else {
			$hratio = ($height == null) ? null : $height / $sh;
		}
		if($wratio == null && $hratio == null) {
			$ratio = 1;
		} else if($wratio == null) {
			$ratio = $hratio;
		} else if($hratio == null) {
			$ratio = $wratio;
		} else if($wratio < $hratio) {
			$ratio = ($mode == "crop") ? $hratio : $wratio;
		} else {
			$ratio = ($mode == "crop") ? $wratio : $hratio;
		}
		return $ratio;
	}

	/** Return the cache filename for this image.
	 *  @param file The real filename, as output by self::findFile().
	 */
	protected static function cacheName($file, $width, $height, $type, $rgb, $filter = false) {
		if(empty($type)) {
			$type = "null";
		}
		if(is_array($filter)) {
			$string = array();
			foreach ($filter as $key => $val) {
				if(is_array($val))
					$val = implode(',', $val);
				$string[] = "{$key}{$val}";
			}
			$filter = '-' . implode(':', $string);
		}
		$path = "{$type}-{$width}-{$height}-{$rgb}{$filter}/" . base64_encode($file);
		return $path;
	}

	/** Find the real file for the requested image. */
	public static function findFile($request) {
		$file = self::findFile2($request);
		if($file) {
			return $file;
		}
		$file = self::findFile2(urldecode($request));
		if($file) {
			return $file;
		}
		$file = self::findFile2(base64_decode(urldecode($request)));
		if($file) {
			return $file;
		}
		//Log::error("Could not find original image '$request' (" . base64_decode(urldecode($request)) . ")", false);
		return null;
	}

	private static function findFile2($request) {

		$searchPaths = array(
			'',
			'img/',
			'img/uploads/'
		);
		if(
			preg_match('%thepetexpress%', $_SERVER['SERVER_NAME']) ||
			preg_match('%thearkpetshop%', $_SERVER['SERVER_NAME'])) {
			//$searchPaths[] = "http://www.vital-group.co.uk/products/images/med/";
		}
		if(isset($_GET['upload']) && !empty($_GET['upload'])) {
			global $system_settings, $shopping_cart, $session;
			$upload_path = 'upload/custom_uploads/';
			switch ($_GET['upload']) {
				case "users":
					$upload_path .= 'users/' . $session->getUserId() . '/';
					break;
				case "cart":
					$upload_path .= 'carts/' . $shopping_cart->cookie_id . '/';
					break;
			}
			array_unshift($searchPaths, $upload_path);
		}
		// Nasty hack, we should do URL handling properly.
		$request = str_replace("//", "/", $request);
		foreach ($searchPaths as $path) {
			if(file_exists($path . $request) && is_file($path . $request)) {
				//trigger_error("found2: " . $path . $request);
				return $path . $request;
			}
		}
		return null;
	}

	private function applyFilter($filters) {
		if(is_array($filters)) {
			$iterations = isset($_GET['i']) ? $_GET['i'] : 1;
			foreach ($filters as $filter) {

				$fromRGB = self::getRgb($filter['from']);
				$toRGB = self::getRgb($filter['to']);

				for ($i = 0; $i < $iterations; $i++) {
					imagetruecolortopalette($this->image, false, 255);
					$index = imagecolorclosest($this->image, $fromRGB['r'], $fromRGB['g'], $fromRGB['b']);
					imagecolorset($this->image, $index, $toRGB['r'], $toRGB['g'], $toRGB['b']);
				}
			}
		}
	}

	/** Load and output an image based on the query string.
	 *  @see loadFromQuery()
	 */
	public static function outputFromQuery() {
		$image = self::loadFromQuery();
		$image->output();
	}

	public static function outputall() {
		$image = self::loadFromQuery();
		$image->output();
	}

	/** Return a hex RGB string based on the contents of the query. */
	public static function getRgbFromQuery() {
		if(isset($_GET['rgb'])) {
			$rgb = $_GET['rgb'];
			$a = self::getRgb($rgb);
			$rgb = self::getHex($a['r'], $a['g'], $a['b']);
		} else {
			if(isset($_GET['red'])) {
				$r = $_GET['red'];
			} else {
				$r = 255;
			}
			if(isset($_GET['green'])) {
				$g = $_GET['green'];
			} else {
				$g = 255;
			}
			if(isset($_GET['blue'])) {
				$b = $_GET['blue'];
			} else {
				$b = 255;
			}
			$rgb = self::getHex($r, $g, $b);
		}
		return $rgb;
	}

	/** Load and resize image, using cache if available.
	 *  @todo Unfinished.
	 *  @returns an image resource.
	 */
	public static function loadResize($file, $type, $width, $height, $rgb) {
		$realfile = self::findFile($file);
		$image = self::loadFromCache(self::cacheName($realfile, $type, $width, $height, $rgb));
	}

	/** Load and resize an image based on the contents of the query string.
	 *  @todo Move cache handling into its own set of methods.
	 *  @todo Deal with permission denied errors
	 *  @returns an image resource.
	 *
	 *  Usage:
	 *  @htmlonly
	  <dl>
	  <dt>f</dt>
	  <dd>base64/url encoded image path</dd>
	  <dt>rgb</dt>
	  <dd>background color as an HTML hex color string (e.g. "#FFFFFF" or "#FFF")</dd>
	  <dt>w</dt>
	  <dd>(optional) width of image to return</dd>
	  <dt>mw</dt>
	  <dd>(optional) Max image width (ignored if w is specified)</dd>
	  <dt>h</dt>
	  <dd>(optional) height of image to return</dd>
	  <dt>mh</dt>
	  <dd>(optional) Max image height (ignored if h is specified)</dd>
	  <dt>type</dt>
	  <dd>operation type, one of:
	  <dl>
	  <dt>back</dt>
	  <dd>(default) use background color to fill differences in aspect ratio</dd>
	  <dt>crop</dt>
	  <dd>crop differences in aspect ratio</dd>
	  </dl>
	  </dd>
	  </dl>
	  If width or height are null or unspecified, they will be determined from the aspect ratio.
	  If both width and height are null, no resizing will occur.

	  Deprecated options:
	  <dl>
	  <dt>fp</dt>
	  <dd>plain file link</dd>
	  <dt>fs</dt>
	  <dd>"forced size", if true and no type is specified, set type to 'crop'</dd>
	  <dt>r</dt>
	  <dd>red value for background color</dd>
	  <dt>g</dt>
	  <dd>green value for background color</dd>
	  <dt>b</dt>
	  <dd>blue value for background color</dd>
	  </dl>
	 * @endhtmlonly
	 */
	public static function loadFromQuery() {
		$rgb = self::getRgbFromQuery();
		if(isset($_GET['type'])) {
			$type = $_GET['type'];
		} else if(isset($_GET['fs']) && $_GET['fs'] == true) {
			$type = "crop";
		} else {
			$type = null;
		}
		$widthmax = "";
		$heightmax = "";
		if(!empty($_GET['w']) && is_numeric($_GET['w'])) {
			$width = $_GET['w'];
		} else if(!empty($_GET['mw']) && is_numeric($_GET['mw'])) {
			$width = $_GET['mw'];
			$widthmax = "m";
		} else {
			$width = null;
		}
		if(!empty($_GET['h']) && is_numeric($_GET['h'])) {
			$height = $_GET['h'];
		} else if(!empty($_GET['mh']) && is_numeric($_GET['mh'])) {
			$height = $_GET['mh'];
			$heightmax = "m";
		} else {
			$height = null;
		}
		if(!empty($_GET['filter'])) {
			//split down into different filters
			$filter = array();
			$filters = explode('-', $_GET['filter']);
			foreach ($filters as $filterInfo) {
				if(strstr($filterInfo, ':')) {
					$filterParts = explode(':', $filterInfo, 2);
					$filter[] = array('from' => $filterParts[0], 'to' => $filterParts[1]);
				} else {
					$filter[] = array('from' => '000', 'to' => $filterInfo);
				}
			}
		} else {
			$filter = false;
		}

		if(!empty($_GET['f'])) {
			$file_name = $_GET['f'];
		} else if(!empty($_GET['filename'])) {
			$file_name = $_GET['filename'];
		} else if(!empty($_GET['fp'])) {
			$file_name = $_GET['fp'];
		} else {
			//Log::error("Error: No image file: " . var_export($_GET, true), false);
			//Log::error("Error: No image file", false);
			$image = new SimpleImage();
			if($width < 10) {
				$width = 100;
			}
			if($height < 10) {
				$height = 100;
			}
			$image->createMissing($width, $height);
			return $image;
		}
		$originalImage = self::findFile($file_name);
		if($originalImage == null) {
			$image = new SimpleImage();
			if($width <= 0) {
				$width = 100;
			}
			if($height <= 0) {
				$height = 100;
			}
			$image->createMissing($width, $height);
			return $image;
		}
		if(substr($_SERVER['DOCUMENT_ROOT'], -1) == "/") {
			$cachedir = $_SERVER['DOCUMENT_ROOT'] . "cache/images/";
		} else {
			$cachedir = $_SERVER['DOCUMENT_ROOT'] . "/cache/images/";
		}
		$height = $heightmax . $height;
		$width = $widthmax . $width;
		$cachename = self::cacheName($file_name, $width, $height, $type, $rgb, $filter);
		$save_name = $cachedir . $cachename;

		$image = new SimpleImage();
		if($image->load($save_name, filemtime($originalImage))) {
			//trigger_error("used cache $save_name for $originalImage");
			return $image;
		} else {
			$sizedir = dirname($cachename);
			if(!is_dir($cachedir)) {
				@mkdir($cachedir);
			}
			if(!is_dir($cachedir)) {
				//Log::error("Error: Could not create cachedir '$cachedir' {$_SERVER['SERVER_NAME']}");
				//return;
			}
			if(!is_dir($cachedir . $sizedir)) {
				@mkdir($cachedir . $sizedir);
			}
			if(!is_dir($cachedir . $sizedir)) {
				//Log::error("Error: Could not create sizedir '$cachedir$sizedir' {$_SERVER['SERVER_NAME']}");
				//return;
			}
			//trigger_error("regenerating $cachename $originalImage");
			$image->load($originalImage);
			if($type != 'crop') {
				$type = "back";
			}
			$image->applyFilter($filter);
			$image->resize($width, $height, $type, $rgb);
			$image->save($save_name);
		}
		return $image;
	}

	/** Create an image with placeholder text for missing images.
	 *  @returns a SimpleImage object
	 */
	public function createMissing($width, $height) {
		$this->image = imagecreatetruecolor($width, $height);
		$rgb = 240;
		$color = imagecolorallocate($this->image, $rgb, $rgb, $rgb);
		imagefill($this->image, 0, 0, $color);
		$black = imagecolorallocate($this->image, 0, 0, 0);
		$font = 4;
		$string = "No Image";
		$text_width = imagefontwidth($font) * strlen($string);
		$x = $width / 2 - (imagefontwidth($font) * strlen($string)) / 2;
		$y = $height / 2 - imagefontheight($font) / 2;
		imagestring($this->image, $font, $x, $y, $string, $black);
		return $this;
	}

	public static function gmtDate($time) {
		return gmdate('D, d M Y H:i:s \G\M\T', $time);
	}

}
