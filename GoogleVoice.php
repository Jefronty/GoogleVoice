<?php
/*
Upgraded Aaron Parecki's original version of this class ~ https://github.com/aaronpk
Upgraded by Kaddy Simba ~ https://simbamultimedia.com
HTML DOM Class by S.C. Chen (me578022@gmail.com) ~ http://simplehtmldom.sourceforge.net/manual.htm#section_quickstart

UPGRADES:
- Google verifies the login by asking for the city. Be sure to pass the city argument into __construct other wise you may not be able to login. 
(City: Where the GV account was created from. Ex: 'Atlanta, GA, United States')
- Used html dom class to retrieve form data which is safe. 'preg_match' was broken because of the change of input attributes order.

NOTE: I have only tested GV SMS and it worked for me.

SHOW SUPPORT:
INSTAGRAM: @kaddysimba 
FACEBOOK: @kaddysimba 
TWITTER: @kaddysimba 
YOUTUBE: @kaddys05
*/

class GoogleVoice {
	// Google account credentials.
	private $_login;
	private $_pass;
	private $_city;
	// Special string that Google requires in our POST requests.
	private $_rnr_se;

	// File handle for PHP-Curl.
	private $_ch;

	// The location of our cookies.
	private $_cookieFile;

	// Are we logged in already?
	private $_loggedIn = FALSE;

	public function __construct($login, $pass, $city = 0) {
		$this->_login = $login;
		$this->_pass = $pass;
		$this->_city = $city;
		$this->_cookieFile = '/tmp/gvCookies.txt';

		$this->_ch = curl_init();
		curl_setopt($this->_ch, CURLOPT_COOKIEJAR, $this->_cookieFile);
		curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($this->_ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)");
	}
	
	private function _logIn() {
		//global $conf;
		
		if($this->_loggedIn)
			return TRUE;

		// Fetch the Google Voice login page.
		curl_setopt($this->_ch, CURLOPT_URL, 'https://accounts.google.com/ServiceLogin?service=grandcentral&passive=1209600&continue=https://www.google.com/voice&followup=https://www.google.com/voice&ltmpl=open');
		$html = curl_exec($this->_ch);
		$html = explode('<form ',$html);
		$html = explode('</form>',$html[1]);
		$html = '<form '.$html[0].'</form>'; 
		$html = str_get_html($html); 
		$GALX = $html->find('input[name=GALX]',0)->value;
		
		if($GALX){
			
		// Send HTTP POST service login request.
		curl_setopt($this->_ch, CURLOPT_URL, 'https://accounts.google.com/ServiceLoginAuth');
		curl_setopt($this->_ch, CURLOPT_POST, TRUE);
		curl_setopt($this->_ch, CURLOPT_POSTFIELDS, array(
			'Email' => $this->_login,
			'GALX' => $GALX,
			'Passwd' => $this->_pass,
			'continue' => 'https://www.google.com/voice',
			'followup' => 'https://www.google.com/voice',
			'service' => 'grandcentral',
			'signIn' => 'Sign in'));
		$html = curl_exec($this->_ch);
		$html = str_get_html($html);
		$html = explode('<form ',$html);
		$html = explode('</form>',$html[1]);
		$html = '<form '.$html[0].'</form>';
		$html = str_get_html($html);
		$_rnr_se = $html->find('input[name=_rnr_se]',0)->value;
		if($_rnr_se){
			$this->_rnr_se = $_rnr_se;
			$this->_loggedIn = TRUE;
			
			}else{
				
				$form = $html;
				
				if(!empty($form)){
					$action = $form->find('form',0)->getAttribute('action'); 
					$challengeId = $form->find('input[name=challengeId]',0)->value;
					$challengeType = $form->find('input[name=challengeType]',0)->value;
					$TL = $form->find('input[name=TL]',0)->value;
					$gxf = $form->find('input[name=gxf]',0)->value;
					if($action && $challengeId && $challengeType && $TL && $gxf){
						curl_setopt($this->_ch, CURLOPT_URL, 'https://accounts.google.com'.$action);
						curl_setopt($this->_ch, CURLOPT_POST, TRUE);
						curl_setopt($this->_ch, CURLOPT_POSTFIELDS, array(
							'challengeId' => $challengeId,
							'challengeType' => $challengeType,
							'TL' => $TL,
							'answer' => $this->_city,
							'continue' => 'https://www.google.com/voice',
							'followup' => 'https://www.google.com/voice',
							'gxf' => $gxf
							));
						$html = curl_exec($this->_ch); 
						$html = str_get_html($html);
						$html = explode('<form ',$html);
						$html = explode('</form>',$html[1]);
						$html = '<form '.$html[0].'</form>';
						$html = str_get_html($html);
						$_rnr_se = $html->find('input[name=_rnr_se]',0)->value;
						if($_rnr_se){
							$this->_rnr_se = $_rnr_se;
							$this->_loggedIn = TRUE;
							}
						}
					
					}
				
				
				
				}

		}
		
		if(!$this->_loggedIn){
			throw new Exception('Error Logging In!');
			}
	}

	/**
	 * Place a call to $number connecting first to $fromNumber.
	 * @param $number The 10-digit phone number to call (formatted with parens and hyphens or none).
	 * @param $fromNumber The 10-digit number on your account to connect the call (no hyphens or spaces).
	 * @param $phoneType (mobile, work, home) The type of phone the $fromNumber is. The call will not be connected without this value. 
	 */
	public function callNumber($number, $from_number, $phone_type = 'mobile') {
		$types = array(
			'mobile' => 2,
			'work' => 3,
			'home' => 1
		);
	
		// Make sure phone type is set properly.
		if(!array_key_exists($phone_type, $types))
			throw new Exception('Phone type must be mobile, work, or home');
		
		// Login to the service if not already done.
		$this->_logIn();
		
		// Send HTTP POST request.
		curl_setopt($this->_ch, CURLOPT_URL, 'https://www.google.com/voice/call/connect/');
		curl_setopt($this->_ch, CURLOPT_POST, TRUE);
		curl_setopt($this->_ch, CURLOPT_POSTFIELDS, array(
			'_rnr_se' => $this->_rnr_se,
			'forwardingNumber' => '+1'.$from_number,
			'outgoingNumber' => $number,
			'phoneType' => $types[$phone_type],
			'remember' => 0,
			'subscriberNumber' => 'undefined'
			));
		curl_exec($this->_ch);
	}

	/**
	 * Cancel a call to $number connecting first to $fromNumber.
	 * @param $number The 10-digit phone number to call (formatted with parens and hyphens or none).
	 * @param $fromNumber The 10-digit number on your account to connect the call (no hyphens or spaces).
	 * @param $phoneType (mobile, work, home) The type of phone the $fromNumber is. The call will not be connected without this value. 
	 */
	public function cancelCall($number, $from_number, $phone_type = 'mobile') {
		$types = array(
			'mobile' => 2,
			'work' => 3,

			'home' => 1
		);
	
		// Make sure phone type is set properly.
		if(!array_key_exists($phone_type, $types))
			throw new Exception('Phone type must be mobile, work, or home');
		
		// Login to the service if not already done.
		$this->_logIn();

		// Send HTTP POST request.
		curl_setopt($this->_ch, CURLOPT_URL, 'https://www.google.com/voice/call/cancel/');
		curl_setopt($this->_ch, CURLOPT_POST, TRUE);
		curl_setopt($this->_ch, CURLOPT_POSTFIELDS, array(
			'_rnr_se' => $this->_rnr_se,
			'forwardingNumber' => '+1'.$from_number,
			'outgoingNumber' => $number,
			'phoneType' => $types[$phone_type],
			'remember' => 0,
			'subscriberNumber' => 'undefined'
			));
		curl_exec($this->_ch);
	}

	/**
	 * Send an SMS to $number containing $message.
	 * @param $number The 10-digit phone number to send the message to (formatted with parens and hyphens or none).
	 * @param $message The message to send within the SMS.
	 */
	public function sendSMS($number, $message) {
		// Login to the service if not already done. 
		$number = (strpos($number,'+')==false?'+'.$number:$number);
		$this->_logIn();

		// Send HTTP POST request.
		curl_setopt($this->_ch, CURLOPT_URL, 'https://www.google.com/voice/sms/send/');
		curl_setopt($this->_ch, CURLOPT_POST, TRUE);
		curl_setopt($this->_ch, CURLOPT_POSTFIELDS, array(
			'_rnr_se' => $this->_rnr_se,
			'phoneNumber' => $number,
			'text' => $message
			));
		$send = curl_exec($this->_ch);
		return $send;
	}

	/**
	 * Add a note to a message in a Google Voice Inbox or Voicemail.
	 * @param $message_id The id of the message to update.
	 * @param $note The message to send within the SMS.
	 */
	public function addNote($message_id, $note) {
		// Login to the service if not already done.
		$this->_logIn();

		// Send HTTP POST request.
		curl_setopt($this->_ch, CURLOPT_URL, 'https://www.google.com/voice/inbox/savenote/');
		curl_setopt($this->_ch, CURLOPT_POST, TRUE);
		curl_setopt($this->_ch, CURLOPT_POSTFIELDS, array(
			'_rnr_se' => $this->_rnr_se,
			'id' => $message_id,
			'note' => $note
			));
		curl_exec($this->_ch);
	}

	/**
	 * Removes a note from a message in a Google Voice Inbox or Voicemail.
	 * @param $message_id The id of the message to update.
	 */
	public function removeNote($message_id, $note) {
		// Login to the service if not already done.
		$this->_logIn();

		// Send HTTP POST request.
		curl_setopt($this->_ch, CURLOPT_URL, 'https://www.google.com/voice/inbox/deletenote/');
		curl_setopt($this->_ch, CURLOPT_POST, TRUE);
		curl_setopt($this->_ch, CURLOPT_POSTFIELDS, array(
			'_rnr_se' => $this->_rnr_se,
			'id' => $message_id,
			));
		curl_exec($this->_ch);
	}

	/**
	 * Get all of the unread SMS messages in a Google Voice inbox.
	 */
	public function getUnreadSMS() {
		// Login to the service if not already done.
		$this->_logIn();

		// Send HTTP POST request.
		curl_setopt($this->_ch, CURLOPT_URL, 'https://www.google.com/voice/inbox/recent/sms/');
		curl_setopt($this->_ch, CURLOPT_POST, FALSE);
		curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, TRUE);
		$xml = curl_exec($this->_ch);

		// Load the "wrapper" xml (contains two elements, json and html).
		$dom = new DOMDocument();
		$dom->loadXML($xml);
		$json = $dom->documentElement->getElementsByTagName("json")->item(0)->nodeValue;
		$json = json_decode($json);

		// Loop through all of the messages.
		$results = array();
		foreach($json->messages as $mid=>$convo) {
			if($convo->isRead == false) {
				$results[] = $convo;
			}
		}
		return $results;
	}

	/**
	 * Get all of the read SMS messages in a Google Voice inbox.
	 */
	public function getReadSMS() {
		// Login to the service if not already done.
		$this->_logIn();

		// Send HTTP POST request.
		curl_setopt($this->_ch, CURLOPT_URL, 'https://www.google.com/voice/inbox/recent/sms/');
		curl_setopt($this->_ch, CURLOPT_POST, FALSE);
		curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, TRUE);
		$xml = curl_exec($this->_ch);

		// Load the "wrapper" xml (contains two elements, json and html).
		$dom = new DOMDocument();
		$dom->loadXML($xml);
		$json = $dom->documentElement->getElementsByTagName("json")->item(0)->nodeValue;
		$json = json_decode($json);

		// Loop through all of the messages.
		$results = array();
		foreach($json->messages as $mid=>$convo) {
			if($convo->isRead == true) {
				$results[] = $convo;
			}
		}
		return $results;
	}

	/**
	 * Get all of the unread SMS messages from a Google Voice Voicemail.
	 */
	public function getUnreadVoicemail() {
		// Login to the service if not already done.
		$this->_logIn();

		// Send HTTP POST request.
		curl_setopt($this->_ch, CURLOPT_URL, 'https://www.google.com/voice/inbox/recent/voicemail/');
		curl_setopt($this->_ch, CURLOPT_POST, FALSE);
		curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, TRUE);
		$xml = curl_exec($this->_ch);

		// Load the "wrapper" xml (contains two elements, json and html)
		$dom = new DOMDocument();
		$dom->loadXML($xml);
		$json = $dom->documentElement->getElementsByTagName("json")->item(0)->nodeValue;
		$json = json_decode($json);

		// Loop through all of the messages.
		$results = array();
		foreach($json->messages as $mid=>$convo) {
			if($convo->isRead == false) {
				$results[] = $convo;
			}
		}
		return $results;
	}

	/**
	 * Get all of the unread SMS messages from a Google Voice Voicemail.
	 */
	public function getReadVoicemail() {
		// Login to the service if not already done.
		$this->_logIn();

		// Send HTTP POST request.
		curl_setopt($this->_ch, CURLOPT_URL, 'https://www.google.com/voice/inbox/recent/voicemail/');
		curl_setopt($this->_ch, CURLOPT_POST, FALSE);
		curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, TRUE);
		$xml = curl_exec($this->_ch);

		// load the "wrapper" xml (contains two elements, json and html)
		$dom = new DOMDocument();
		$dom->loadXML($xml);
		$json = $dom->documentElement->getElementsByTagName("json")->item(0)->nodeValue;
		$json = json_decode($json);
		
		// Loop through all of the messages.
		$results = array();
		foreach( $json->messages as $mid=>$convo ) {
			if( $convo->isRead == true ) {
				$results[] = $convo;
			}
		}
		return $results;
	}


	/**
	 * Get MP3 of a Google Voice Voicemail.
	 */
	public function getVoicemailMP3($message_id) {
		// Login to the service if not already done.
		$this->_logIn();

		// Send HTTP POST request.
		curl_setopt($this->_ch, CURLOPT_URL, "https://www.google.com/voice/media/send_voicemail/$message_id/");
		curl_setopt($this->_ch, CURLOPT_POST, FALSE);
		curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, TRUE);
		$results = curl_exec($this->_ch);

		return $results;
	}


	/**
	 * Mark a message in a Google Voice Inbox or Voicemail as read.
	 * @param $message_id The id of the message to update.
	 * @param $note The message to send within the SMS.
	 */
	public function markMessageRead($message_id) {
		// Login to the service if not already done.
		$this->_logIn();

		// Send HTTP POST request.
		curl_setopt($this->_ch, CURLOPT_URL, 'https://www.google.com/voice/inbox/mark/');
		curl_setopt($this->_ch, CURLOPT_POST, TRUE);
		curl_setopt($this->_ch, CURLOPT_POSTFIELDS, array(
			'_rnr_se' => $this->_rnr_se,
			'messages' => $message_id,
			'read' => '1'
			));
		curl_exec($this->_ch);
	}

	/**
	 * Mark a message in a Google Voice Inbox or Voicemail as unread.
	 * @param $message_id The id of the message to update.
	 * @param $note The message to send within the SMS.
	 */
	public function markMessageUnread($message_id) {
		// Login to the service if not already done.
		$this->_logIn();

		// Send HTTP POST request.
		curl_setopt($this->_ch, CURLOPT_URL, 'https://www.google.com/voice/inbox/mark/');
		curl_setopt($this->_ch, CURLOPT_POST, TRUE);
		curl_setopt($this->_ch, CURLOPT_POSTFIELDS, array(
			'_rnr_se' => $this->_rnr_se,
			'messages' => $message_id,
			'read' => '0'
			));
		curl_exec($this->_ch);
	}

	/**
	 * Delete a message or conversation.
	 * @param $message_id The ID of the conversation to delete.
	 */

	public function deleteMessage($message_id) {
		$this->_logIn();

		curl_setopt($this->_ch, CURLOPT_URL, 'https://www.google.com/voice/inbox/deleteMessages/');
		curl_setopt($this->_ch, CURLOPT_POST, TRUE);
		curl_setopt($this->_ch, CURLOPT_POSTFIELDS, array(
			'_rnr_se' => $this->_rnr_se,
			'messages' => $message_id,
			'trash' => 1
		));

		curl_exec($this->_ch);
	}
	
	public function dom_dump($obj) {
		if ($classname = get_class($obj)) {
			$retval = "Instance of $classname, node list: \n";
			switch (true) {
				case ($obj instanceof DOMDocument):
					$retval .= "XPath: {$obj->getNodePath()}\n".$obj->saveXML($obj);
					break;
				case ($obj instanceof DOMElement):
					$retval .= "XPath: {$obj->getNodePath()}\n".$obj->ownerDocument->saveXML($obj);
					break;
				case ($obj instanceof DOMAttr):
					$retval .= "XPath: {$obj->getNodePath()}\n".$obj->ownerDocument->saveXML($obj);
					break;
				case ($obj instanceof DOMNodeList):
					for ($i = 0; $i < $obj->length; $i++) {
						$retval .= "Item #$i, XPath: {$obj->item($i)->getNodePath()}\n"."{$obj->item($i)->ownerDocument->saveXML($obj->item($i))}\n";
					}
					break;
				default:
					return "Instance of unknown class";
			}
		}
		else {
			return 'no elements...';
		}
		return htmlspecialchars($retval);
	}
}

?>

