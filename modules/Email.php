<?php
/**
 * Email handles the composition and dispatching of email messages.
 *
 * Sleepy, the lightweight web application framework.
 * Copyright 2012 - 2014, Alex Palaistras (http://thoughtmonster.org)
 *
 * @copyright	Copyright 2012 - 2014, Alex Palaistras (http://thoughtmonster.org)
 * @link		http://thoughtmonster.org/sleepy
 * @package		Sleepy.Modules
 * @since		Sleepy 0.4.0
 */
class Email {
	/**
	 * Used as temporary store for the mail data.
	 *
	 * @var array
	 */
	private $data;

	/**
	 * Specifies the 'From' header for the email message.
	 *
	 * Accepts an email address and an optional name.
	 * 
	 * @param  string $address The email address of the sender.
	 * @param  string $name    An optional name for the sender.
	 * @return object          The Email object, for chaining commands.
	 */
	public function from($address, $name = '') {
		$this->data['from'] = array(
			'address' => $address,
			'name'	  => $name
		);

		return $this;
	}

	/**
	 * Specifies the 'To' header for the email message.
	 *
	 * Accepts a comma-seperated list  of email addresses or an array of addresses, as strings.
	 *
	 * @param  mixed $address The email address(es) to which we will send.
	 * @return object         The Email object, for chaining commands.
	 */
	public function to($address) {
		if (is_array($address)) {
			$this->data['to'] = $address;
		} else {
			$this->data['to'] = func_get_args();
		}

		return $this;	
	}

	/**
	 * Specifies the 'Cc' header for the email message.
	 *
	 * Accepts a comma-seperated list of email addresses or an array of addresses, as strings.
	 *
	 * @param  mixed $address The email address(es) to add to the Cc list.
	 * @return object         The Email object, for chaining commands.
	 */
	public function cc($address) {
		if (is_array($address)) {
			$this->data['cc'] = $address;
		} else {
			$this->data['cc'] = func_get_args();
		}

		return $this;	
	}

	/**
	 * Specifies the 'Bcc' header for the email message.
	 *
	 * Accepts a comma-seperated list of email addresses or an array of addresses, as strings.
	 *
	 * @param  mixed $address The email address(es) to add to the Bcc list.
	 * @return object         The Email object, for chaining commands.
	 */
	public function bcc($address) {
		if (is_array($address)) {
			$this->data['bcc'] = $address;
		} else {
			$this->data['bcc'] = func_get_args();
		}

		return $this;	
	}

	/**
	 * Specifies the subject for the email message.
	 *
	 * @param  string $address The subject for this email.
	 * @return object          The Email object, for chaining commands.
	 */
	public function subject($subject) {
		$this->data['subject'] = $subject;
		return $this;
	}

	/**
	 * Specifies the message content for the email message.
	 *
	 * Accepts either a file path to a template file, or a raw message. In the case of a
	 * template file, any required data may be passed as a second parameter.
	 *
	 * @param  string $content The message content, either as a template filename or a raw string.
	 * @param  array  $data    The optional data to be passed to the template.
	 * @return object          The Email object, for chaining commands.
	 */
	public function message($content, $data = array()) {
		Sleepy::load('Template', 'modules');

		// First, check if we're dealing with a renderable template file.
		$template = new Template($content);
		if (!empty($data)) {
			$template->set($data);
		}

		$result = $template->render();
		if ($result !== false) {
			$this->data['message'] = array(
				'content' => $result,
				'type'    => 'html'
			);
		} else {
			// Treat the content as literal text, if rendering failed.
			$this->$data['message'] = array(
				'content' => $content,
				'type'    => 'text'
			);
		}

		return $this;
	}

	/**
	 * Prepares file to be attached to message.
	 *
	 * @param  string $file     Path to file.
	 * @param  string $filename An optional filename, when autodetection isn't desired.
	 * @return object           The Email object, for chaining commands.
	 */
	public function attach($file, $filename = null) {
		if (!file_exists($file)) {
			Exceptions::log("Cannot attach file, file does not exist: {$file}");
			return $this;
		}

		$info = finfo_open(FILEINFO_MIME_TYPE);
		$this->data['attach'][] = array(
			'filename'	=>	(empty($filename)) ? basename($file) : $filename,
			'type'		=>	finfo_file($info, $file),
			'data'		=>	base64_encode(file_get_contents($file))
		);

		finfo_close($info);
		return $this;
	}

	/**
	 * Sends the email message.
	 * 
	 * Optionally accepts a list of addresses as in 'Email::to()'. Returns 'true' if the
	 * message was sent successfully, and 'false' if not.
	 *
	 * @param  mixed $address The email address(es) to which we will send.
	 * @return bool           Whether or not the email was sent successfully.
	 */
	public function send($address = array()) {
		if (!empty($address)) {
			$this->to(func_get_args());
		}

		$mail = $this->data;
		unset($this->data);

		return Sleepy::call('Email', 'Send', $mail);
	}

	/**
	 * Initializes an Email object.
	 * 
	 * Optionally sets the subject line for the message.
	 * 
	 * @param string $subject The subject for the email message.
	 */
	public function __construct($subject = '') {
		$this->data['subject'] = $subject;
	}
}

// End of file Email.php