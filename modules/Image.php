<?php
/**
 * Image provides methods for processing (cropping, resizing, rotating) image files.
 *
 * Sleepy, the lightweight web application framework.
 * Copyright 2012 - 2014, Alex Palaistras (http://deuill.org)
 *
 * @copyright	Copyright 2012 - 2014, Alex Palaistras (http://deuill.org)
 * @link		http://deuill.org/sleepy
 * @package		Sleepy.Modules
 * @since		Sleepy 0.2.0
 */
class Image {
	/**
	 * The image file location.
	 * 
	 * @var string
	 */
	private $image;

	/**
	 * Whether or not the file points to a URL or a local file.
	 * 
	 * @var int
	 */
	private $remote;

	/**
	 * Data contains metadata such as URLs, filenames etc. Interface is private and unstable,
	 * but is guaranteed to contain the 'url' variable at least.
	 * 
	 * @var array
	 */
	private $data;

	/**
	 * Crop image file at coordinates 'x', 'y' and dimensions 'w' and 'h'.
	 * 
	 * @param  int    $x The 'x' coordinate which the bounding box starts at.
	 * @param  int    $y The 'y' coordinate which the bounding box starts at.
	 * @param  int    $w The width of the bounding box.
	 * @param  int    $h The height of the bounding box.
	 * @return object    The image object for the new, cropped image.
	 */
	public function crop($x, $y, $w, $h) {
		$options = array(
			'x' => $x,
			'y' => $y,
			'w' => $w,
			'h' => $h
		);

		return $this->process('Crop', $options);
	}

	/**
	 * Resize image at 'width', 'height', using an optional aspect ratio.
	 * 
	 * If the aspect ratio is empty or zero, the aspect of the original image is kept as-is.
	 * 
	 * @param  int    $width  The width for the new image.
	 * @param  int    $height The height for the new image.
	 * @param  string $aspect The aspect ratio for the new image.
	 * @return object         The image object for the new, resized image.
	 */
	public function resize($width, $height, $aspect = 0) {
		$options = array(
			'w'		 =>	$width,
			'h'		 =>	$height,
			'aspect' =>	$aspect
		);

		return $this->process('Resize', $options);
	}

	/**
	 * Creates a new instance of Image. Accepts a local path or URL pointing to an image.
	 * 
	 * @param string $image A local path or remote URL pointing to an image.
	 */
	public function __construct($image) {
		// Is the path to the image a remote file?
		$this->remote = preg_match('#^https?://#i', $image);

		// Check to see if image actually exists on disk.
		if (!$this->remote && !file_exists($image)) {
			// Only return the last part of the path, for security reasons.
			$path = str_replace("\\", "/", $image);
			if (strpos($path, '/') !== false) {
				$p = explode('/', $path);
				$path = $p[count($p) - 2].'/'.end($p);
			}

			Exceptions::warning("the image at '{$path}' does not exist.");
			return;
		}

		$this->image = $image;
	}

	/**
	 * Returns variables from the private 'data' array.
	 * 
	 * A call of:
	 *
	 * 	$image = new Image('http://example.com/image.png');
	 * 	echo $image->url;
	 *
	 * Will return the URL for the uploaded image.
	 *
	 * @param  string $name The name of the variable.
	 * @return mixed        Whatever type the variable was stored as.
	 */
	public function __get($name) {
		if (!isset($this->data->$name)) {
			Exceptions::warning("property '{$name}' does not exist in image");
			return null;
		}

		return $this->data->$name;
	}

	/**
	 * Checks for variable existance when checking with 'isset()'.
	 * 
	 * @param  string  $name The name of the variable.
	 * @return boolean       Whether or not the variable is set.
	 */
	public function __isset($name) {
		return isset($this->data->$name);
	}

	/**
	 * Return the image URL when object is used as a string.
	 * 
	 * @return string The URL of the image the object points to.
	 */
	public function __toString() {
		return $this->data->url;
	}

	/**
	 * Process request and upload image file.
	 * 
	 * @param  string $operation Operation to call on image file.
	 * @param  array  $options   Options to be passed as arguments to operation.
	 * @return mixed             An Image object for the processed image, or null on error.
	 */
	private function process($operation, $options = array()) {
		if (!isset($this->image)) {
			return null;
		}

		$this->data = new stdClass;

		$filename = basename(preg_replace('/%20/', ' ', $this->image));
		$filepath = ($this->remote) ? preg_replace('/\s/', '%20', $this->image) : $this->image;
		$request = array_merge(array(
			'auth'		=>	Sleepy::get('client', 'authkey'),
			'checksum'	=>	sha1_file($filepath),
			'filename'	=>	$filename
		), $options);

		$this->data->url = Sleepy::call('Image', $operation, $request);
		if ($this->data->url === "") {
			if ($this->remote) {
				$request['remote'] = $filepath;
			} else {
				Sleepy::send($this->image, $request['checksum']);
			}

			$this->data->url = Sleepy::call('Image', $operation, $request);
		}

		$this->data->url = preg_replace('/\s/', '%20', $this->data->url);
		$this->image = $this->data->url;
		$this->remote = true;
		return $this;
	}
}

/* End of file Image.php */