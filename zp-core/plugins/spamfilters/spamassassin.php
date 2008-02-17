<?

/* 
 * This is plugin for Spamassassin filtering
 * Author: Jerome Blion : jerome@hebergement-pro.org
 * Website: http://www.hebergement-pro.org
 * Be fair, don't remove author names ;-)
 *
 * Interface functions:
 *     getFilterOptionsSupported()
 *        called from admin Options tab
 *        returns an array of the option names the theme supports
 *        the array is indexed by the option name. The value for each option is an array:
 *          'type' => 0 says for admin to use a standard textbox for the option
 *          'type' => 1 says for admin to use a standard checkbox for the option
 *          'type' => 2 will cause admin to call handleOption to generate the HTML for the option
 *          'desc' => text to be displayed for the option description.
 *
 *     handleFilterOption($option, $currentValue)
 *       $option is the name of the option being processed
 *       $currentValue is the "before" value of the option
 *
 *     filterMessage($author, $email, $website, $body, $imageLink)
 *       $author is the author field of the comment
 *       $email is the email field of the comment
 *       $website is the website field of the comment
 *       $body is the comment text
 *       $imageLink is the url to the full image (will not be used here)
 *
 *       called from class-image as we are about to post the comment to the database and send an email
 *
 *       returns:
 *         0 if the message is SPAM
 *         1 if you don't trust spamassassin and prefer to moderate comments
 *         2 if the message is not SPAM
 *
 *       class-image conditions the database store and email on this result.
 *
 *              Required Configuration Items:
 *
 *              		User must have Spamassassin available on a TCP port
 *
 *	 	TODO : Implement socket connexion
 *
 *	version: 20071106
 */


class SpamFilter {
	var $spamassassin_host;		// Spamassassin server
	var $spamassassin_ctype;	// Connexion type
	var $spamassassin_port;		// Port of spamassassin
	var $spamassassin_socket;	// Socket of Spamassassin
	var $spamassassin_user;		// USer to use on Spamassassin box
	var $server_name;		// This webserver
	var $admin_email;		// e-Mail of the admin

	var $received_1;		// first line of "Received: headers
	var $received_2;		// Second line of "Received: headers 
	var $conn;			// Network connexion to Spamassassin
	var $date;			// Date (RFC compliant)

	function SpamFilter() {	// constructor
	
		// setup default options

		setOptionDefault('Forgiving', 0);
		setOptionDefault('SpamAssassin_host', 'localhost');
		setOptionDefault('SpamAssassin_ctype', 'tcp');
		setOptionDefault('SpamAssassin_port', '');
		setOptionDefault('SpamAssassin_socket', '');
		setOptionDefault('SpamAssassin_user', '');
		
		/* Spamassassin variables */
		$this->spamassassin_host	= getOption('SpamAssassin_host');
		$this->spamassassin_ctype	= getOption('SpamAssassin_ctype');
		if($this->spamassassin_ctype == 'tcp') {
			$this->spamassassin_port = getOption('SpamAssassin_port');
		}
		else {
			$this->spamassassin_socket = getOption('SpamAssassin_socket');
		}
		$this->spamassassin_user = getOption('SpamAssassin_user');
	
		/* Internal variables I need to fetch to use them in the class */	
		$this->server_name = php_uname('n');
		$admin_emails = getAdminEmail();
		if ($count($admin_emails) > 0) {
			$this->admin_email = $admin_emails[0];  //TODO: maybe we should send to all of them?
		}

	}

	function getOptionsSupported() {
		return array(
			'Forgiving' => array('type' => '1' , 'desc' => 'Mark suspected SPAM for moderation rather than as SPAM'),
			'SpamAssassin_host' => array('type' => '0' , 'desc' => 'SpamAssassin server'),
			'SpamAssassin_ctype' => array('type' => '2' , 'desc' => 'Connection type'),
			'SpamAssassin_port' => array('type' => '0' , 'desc' => 'TCP port of SpamAssassin'),
			'SpamAssassin_socket' => array('type' => '0' , 'desc' => 'Socket of SpamAssassin'),
			'SpamAssassin_user' => array('type' => '0' , 'desc' => 'User to use on SpamAssassin box')
		);
	}
	
	function handleOption($option, $currentValue) {
		if ($option == 'SpamAssassin_ctype') {
			echo '<select id="connectiontype" name="' . $option . '"' . ">\n";
			echo '<option value="tcp"';
			if ($currentValue == 'tcp') { 
				echo ' selected="selected"'; 
			}
			echo ">tcp</option>\n";
			echo '<option value="socket" disabled';
			if ($currentValue == 'socket') { 
				echo ' selected="selected"'; 
			}
			echo ">socket (unimplemented)</option>\n";
			echo "</select>\n";
		}
	}

	function prepareHeaders() {
		$this->date=date(r);
		$from_ip = ($_SERVER['REMOTE_ADDR'] != getenv('SERVER_ADDR')) ? $_SERVER['REMOTE_ADDR'] : getenv('HTTP_X_FORWARDED_FOR');
		$from_name = gethostbyaddr($from_ip);
		if($from_name == $from_ip ) {
			$this->received_1 = "[$from_ip]";
			$this->received_2 = "($received_1)";
		}
		else {
			$this->received_1 = $from_name;
			$this->received_2 = "($from_name [$from_ip])";
		}
	}

	function comment2Mail($name,$email,$website,$comment) {
		if (ini_get('magic_quotes_gpc') == 1) $comment = stripslashes($comment);

		$message = "From: \"$name\" <$email>\n".
			 	"To: ".$this->admin_email."\n".
			 	"Date: ".$this->date."\n".
			 	"Content-type: text/plain\n".
			 	"Received: from ".$this->received_1." ".$this->received_2." (uid ".getmyuid().")\n".
			 	"	by ".$this->server_name." with Zenphoto; ".$this->date."\n".
			 	"Message-ID: <zenphoto-".md5(time())."@".$this->server_name.">\n".
			 	"Subject: Zenphoto\n\n".
			 	wordwrap($comment." - ".$website."\r\n",76);

		return $message;
	}

	function prepareRequest($message) {
		$request = "CHECK SPAMC/3.1\n".
			 	"User: ".$this->spamassassin_user."\n".
			 	"Content-length: ".strlen($message)."\n\r\n".
			 	$message;

		return $request;
	}

	function connectMe() {
		// TODO : Manage errors a better way
		//	: Manage socket connexion
		$this->conn = @fsockopen($this->spamassassin_host, $this->spamassassin_port, $errno, $errmsg);
		if ($this->conn) {
			return true;
		}
		else {
			return false;
		}
	}
	function disconnectMe() {
		fclose($this->conn);
		return true;
	}

	function sendRequest($request) {
		$out = '';
		if(fwrite($this->conn, $request, strlen($request)) === false) return true;      // something happened...
		
		while(!feof($this->conn)) {
			/*
			 * Here is an answer of spamd:
			 *
			 * SPAMD/1.1 0 EX_OK
			 * Spam: True ; 5.7 / 5.0
			 *
			 * As the answer is short enough, the buffer is short...
			 */

			$out.=fgets($this->conn,64); // add ."\n"; for debug purposes
		}
		return $out;
	}

	function parseAnswer($output) {
		if(preg_match('/Spam: True/', $output) > 0) {
			return true;
		}
		else {
			return false;
		}
	}

	function filterMessage($author, $email, $website, $comment, $image_name) {
		$this->prepareHeaders();
		$forgedMail = $this->comment2Mail($author, $email, $website, $comment);
		$request = $this->prepareRequest ($forgedMail);

		$isConnected = $this->connectMe();
		$isSpam = true;
		if ($isConnected) {
			$output = $this->sendRequest($request);
			$isSpam = $this->parseAnswer($output);

			$this->disconnectMe();
		}

		/* 
		 * It's a little bit confusing here
		 * I'm looking for a spam while Zenphoto core is looking for a good message !
		 * So, I need to answer to : "Is it a good message?"
		 */

		if ($isSpam === true) {
			if (getOption('Forgiving') == 1) {
				// Comment has been detected as spam and has to be moderated
				return 1;
			}
			else {
				// Comment has been detected as spam and will be dropped
				// If there is any code injection that tries to modify the Forgiving variable,
				// it will go to trash :-)
				return 0;
			}
		}
		else {
			// Comment is good and do not need to be moderated
			return 2; 
		}
	}
}
?>
