<?php
/**
 * File handles the uploading and retrieval of files.
 *
 * Sleepy, the lightweight web application framework.
 * Copyright 2012 - 2014, Alex Palaistras (http://deuill.org)
 *
 * @copyright	Copyright 2012 - 2014, Alex Palaistras (http://deuill.org)
 * @link		http://deuill.org/sleepy
 * @package		Sleepy.Modules
 * @since		Sleepy 0.2.0
 */
class File {
	/**
	 * Whether or not the file points to a URL or a local file.
	 * 
	 * @var int
	 */
	private $remote;

	/**
	 * Data contains metadata such as URLs, filenames etc. Interface is private and unstable,
	 * but is guaranteed to contain the 'url' and 'checksum' variables at least.
	 * 
	 * @var array
	 */
	private $data;

	/**
	 * Delete a file from the remote server pointed to by URL, filename or SHA1 hash.
	 * 
	 * Returns true on success, false for any other reason (file not found, server error).
	 * 
	 * @return bool Whether or not the file was deleted successfully.
	 */
	public function delete() {
		return Sleepy::call('File', 'Delete', array(
			'auth'		=>	Sleepy::get('client', 'authkey'),
			'checksum'	=>	$this->data->checksum
		));
	}

	/**
	 * Creates new instance of File.
	 * 
	 * Accepts a string parameter of either type:
	 *  - A URL of the type: http://example.com/file.txt
	 *  - A local file path: /tmp/tempfile.txt
	 *  - A SHA1 checksum, for retrieving files that have already been uploaded.
	 * 
	 * @param string $file A file URL, path or checksum.
	 */
	public function __construct($file) {
		// Is the path to the file a remote location?
		$this->remote = preg_match('#^https?://#i', $file);

		if ($this->remote || file_exists($file)) {
			$this->data = $this->upload($file);
		} else if (preg_match('/^[0-9a-f]{40}$/i', $file) === 1) {
			$this->data = $this->get($file);
		} else {
			Exceptions::warning("file does not exist and/or is not a valid SHA1 hash.");
		}
	}

	/**
	 * Returns variables from the private 'data' array.
	 * 
	 * A call of:
	 *
	 * 	$file = new File('folder/file.txt');
	 * 	echo $file->url;
	 *
	 * Will return the URL for the uploaded file.
	 *
	 * @param  string $name The name of the variable.
	 * @return mixed        Whatever type the variable was stored as.
	 */
	public function __get($name) {
		if (!isset($this->data->$name)) {
			Exceptions::warning("property '{$name}' does not exist in file");
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
	 * Returns the file URL when object is used as string.
	 * 
	 * @return string The URL of the file the object points to.
	 */
	public function __toString() {
		return $this->data->url;
	}

	/**
	 * Retrieve file from remote server by SHA1 checksum.
	 * 
	 * @param  string $sha1 The SHA1 string of the file we wish to retrieve.
	 * @return array        The data associated with the remote file.
	 */
	private function get($sha1) {
		$data = new stdClass;

		$data->checksum = $sha1;
		$data->url = Sleepy::call('File', 'Get', array(
			'auth'		=>	Sleepy::get('client', 'authkey'),
			'checksum'	=>	$sha1
		), true);

		$data->url = preg_replace('/\s/', '%20', $data->url);
		return $data;
	}

	/**
	 * Upload file pointed to by 'filepath', which can either be a local path or a URL
	 * to remote server
	 * 
	 * @param  string $filepath Path to file, either local or in URL form.
	 * @return array            The data associated with the remote file.
	 */
	private function upload($filepath) {
		$data = new stdClass;

		$data->checksum = sha1_file($filepath);
		$filename = basename(preg_replace('/%20/', ' ', $filepath));
		$request = array(
			'auth'		=>	Sleepy::get('client', 'authkey'),
			'checksum'	=>	$data->checksum,
			'filename'	=>	$filename
		);

		// Find if file already exists remotely.
		$data->url = Sleepy::call('File', 'Get', $request, true);
		if ($data->url !== "") {
			return $data;
		}

		// Cached version does not exist, send the file.
		if ($this->remote) {
			$request['remote'] = preg_replace('/\s/', '%20', $filepath);
		} else {
			Sleepy::send($filepath, $data->checksum);
		}

		$data->url = Sleepy::call('File', 'Upload', $request);
		$data->url = preg_replace('/\s/', '%20', $data->url);
		return $data;
	}
}

/* End of file File.php */