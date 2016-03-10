<?php

	header('Content-type: application/json');
	require_once __DIR__ . '/dataLayer.php';
	
	$action = $_POST['action'];

	switch($action)
	{
		case 'LOGIN':	loginAction();
						break;
		case 'REGISTER': registerAction();
						break;
		case 'EMAIL': 	sendEmail();
						break;
		case 'COOKIE': 	verifyCookies();
						break;
		case 'NEWPOST': createNewPost();
						break;
		case 'NAME':	sessionName();
						break;
		case 'CART':	loadCart();
						break;
		case 'POST_SKILL':	postSkill();
							break;
		case 'GET_SKILLS': getSkills();
							break;
		case 'BUY':		buy();
						break;
		case 'UPDATE_PROFILE': 	updateProfile();
								break;
		case 'ADD_TO_CART': addToCart();
							break;
		case 'MSG':		loadMessages();
						break;
		case 'END_SES': endSession();
						break;
		case 'GET_USER_DATA': 	getUserData();
								break;
		case 'USER_POSTS': 	userPosts();
							break;
		case 'REPLY':	addReply();
						break;
		case 'MSG_ID':	showSpecificMessage();
						break;
		case 'GET_SKILL_USER_DATA': getSkillUserData();
									break;


	}

	function loginAction()
	{
		$email = $_POST['email'];
		$pass = $_POST['password'];

		$result = login($email);

		if ($result['message'] == 'OK')
		{
		    $key = pack('H*', "bcb04b7e103a05afe34763051cef08bc55abe029fdebae5e1d417e2ffb2a00a3");
	    
		    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
		    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	    	
	    	# --- DECRYPTION ---
		    $ciphertext_dec = base64_decode($result['password']);
		    
		    $iv_dec = substr($ciphertext_dec, 0, $iv_size);
		    
		    $ciphertext_dec = substr($ciphertext_dec, $iv_size);

		    $plaintext_dec = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $ciphertext_dec, MCRYPT_MODE_CBC, $iv_dec);
		   	
		   	$count = 0;
		   	$length = strlen($plaintext_dec);

		    for ($i = $length - 1; $i >= 0; $i --)
		    {
		    	if (ord($plaintext_dec{$i}) === 0)
		    	{
		    		$count ++;
		    	}
		    }

		    $plaintext_dec = substr($plaintext_dec, 0,  $length - $count);

		   if ($plaintext_dec === $pass)
		   {	
		    	$response = array('fName' => $result['fName'], 'lName' => $result['lName']);   
			    
			    # Starting the sesion (At the server)
		    	startSession($result['fName'], $result['lName'], $result['email']);

			    # Setting the cookies
				setcookie("cookieUsername", $result['email']);
			    
			    echo json_encode($response);
			}
			else
			{
				die(json_encode(errors(206)));
			}
		}
		else
		{
			die(json_encode($result));
		}

	}

	function registerAction()
	{
		$email = $_POST['email'];
		$pass = $_POST['password'];
		$first = $_POST['firstName'];
		$last = $_POST['lastName'];

		# --- ENCRYPTION ---
	    # the key should be random binary, use scrypt, bcrypt or PBKDF2 to
	    # convert a string into a key
	    # key is specified using hexadecimal
	    $key = pack('H*', "bcb04b7e103a05afe34763051cef08bc55abe029fdebae5e1d417e2ffb2a00a3");
	    
	    # show key size use either 16, 24 or 32 byte keys for AES-128, 192
	    # and 256 respectively
	    $key_size =  strlen($key);
	    
	    $plaintext = $pass;

	    # create a random IV to use with CBC encoding
	    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
	    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	    
	    # creates a cipher text compatible with AES (Rijndael block size = 128)
	    # to keep the text confidential 
	    # only suitable for encoded input that never ends with value 00h
	    # (because of default zero padding)
	    $ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key,
	                                 $plaintext, MCRYPT_MODE_CBC, $iv);

	    # prepend the IV for it to be available for decryption
	    $ciphertext = $iv . $ciphertext;
	    
	    # encode the resulting cipher text so it can be represented by a string
	    $password_ciphertext_base64 = base64_encode($ciphertext);


		$result = register($email, $password_ciphertext_base64, $first, $last);

		if ($result['message'] == 'OK') 
		{
			echo json_encode("Registration successfull");
		}
		else
		{
			die(json_encode($result));
		}
	}

	function sendEmail()
	{
		$email = $_POST['email'];
		$message = $_POST['message'];
		$first = $_POST['firstName'];
		$last = $_POST['lastName'];

		date_default_timezone_set('Etc/UTC');
		require 'PHPMailerAutoload.php';
		
		$mail = new PHPMailer;
		
		$mail->isSMTP();
		//Enable SMTP debugging
		// 0 = off (for production use)
		// 1 = client messages
		// 2 = client and server messages
		$mail->SMTPDebug = 2;
		//Ask for HTML-friendly debug output
		$mail->Debugoutput = 'html';
		//Set the hostname of the mail server
		$mail->Host = 'smtp.gmail.com';
		
		//Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
		$mail->Port = 587;
		//Set the encryption system to use - ssl (deprecated) or tls
		$mail->SMTPSecure = 'tls';
		//Whether to use SMTP authentication
		$mail->SMTPAuth = true;
		//Username to use for SMTP authentication - use full email address for gmail
		$mail->Username = "jorgephpmailertest@gmail.com";
		//Password to use for SMTP authentication
		$mail->Password = "testphp2015";
		
		$mail->setFrom($email, $first);
		
		$mail->addAddress('jorgelp94@gmail.com', 'Skills On Demand');
		
		$mail->Subject = 'Skills On Demand Contact: ';
		
		$mail->Body = 'Name: ' . $first . ' ' . $last . '<br />' . 'Email: ' . $email . '<br />' . 'Message: ' . $message;
		$mail->AltBody = '$message';
		
		if (!$mail->send()) {
    		echo json_encode("Mailer Error: " . $mail->ErrorInfo);
		} else {
    		echo json_encode("Message sent!");
		}
	}

	function verifyCookies()
	{
		if (isset($_COOKIE['cookieUsername']))
		{
			echo json_encode(array('cookieUsername' => $_COOKIE['cookieUsername']));   	    
		}
		else
		{
		    die(json_encode(errors(417)));
		}
	}

	function createNewPost()
	{
		// TO DO: make sure session is active
		$firstName = $_SESSION['fName'];
		$lastName = $_SESSION['lName'];
		$email = $_SESSION['email'];
		$title = $_POST['title'];
		$description = $_POST['description'];

		$result = newPost($title, $description, $firstName, $lastName, $email);

		if ($result['message'] == 'OK') {
			echo json_encode("Post successfull");
		}
		else {
			die(json_encode($result));
		}
	}

	function sessionName()
	{
		$result = getSession();
		if ($result['message'] == 'OK') {
			$firstName = $result['name'];
			echo json_encode($firstName);
		} else {
			echo json_encode("ERROR no hay sesion");
		}
	}

	function loadCart()
	{
		session_start();
		if (isset($_SESSION['email']))
		{
			$result = getCartItems($_SESSION['email'], $_POST['status']);

			if ($result['message'] == 'OK')
			{
				echo json_encode($result);
			}
			else
			{
				if ($result['message'] == 'NONE')
				{
					echo json_encode($result);
				}
				else
				{
					die(json_encode($result));
				}
			}
		}
		else
		{
			die(json_encode(errors(417)));
		}
	}




	function postSkill(){
		session_start();

		$result = addSkill($_POST['data'], $_SESSION['email']);


		if ($result['status'] == 'COMPLETE'){
			echo json_encode("We succesfully added your skill to our DB");
		}
		else{
			die(json_encode($result));
		}
		

	}

	function getSkills(){
		$result = getAllSkills();
		echo json_encode($result);
	}

	function buy()
	{
		//$data = $_POST['data'];

		session_start();
		if (isset($_SESSION['email'])) {
			
			$result = hirePeople($_SESSION['email'], $_SESSION['fName']);

			if ($result['message'] == 'OK')
			{

				if ($result['emails'] == 'OK') {
					$message = "An email was sent to each client.";
				}
				$message = "Successful sale.";

				// send message to 
				echo json_encode($message);
			}
			else
			{
				if ($result['message'] == 'NONE')
				{
					echo json_encode($result);
				}
				else
				{
					die(json_encode($result));
				}
			}

		}
		else
		{
			die(json_encode(errors(417)));
		}

	}



	function updateProfile(){

		session_start();

		$result = updateUserProfile($_POST['data'], $_SESSION['email']);


		if ($result['status'] == 'COMPLETE'){
			echo json_encode("We succesfully updated your profile");
		}
		else{
			die(json_encode($result));
		}

	}


	function addToCart()
	{
		session_start();
		if (isset($_SESSION['email'])) {
			$id = $_POST['id'];

			$result = addCart($_SESSION['email'], $id);

			if ($result['message'] == 'COMPLETE') {
				$message = "Skill added to cart!";

				echo json_encode($message);
			} 
			else {
				echo json_encode($result);
			}


		}
		else
		{
			die(json_encode(errors(417)));
		}
	}

	function loadMessages()
	{
		session_start();
		if (isset($_SESSION['email'])) {
			
			$result = messages($_SESSION['email']);

			//echo var_dump($result);

			if ($result['message'] == 'OK') {
				$message = "Messages loaded correctly!";

				echo json_encode($result);
			} else {
				echo json_encode($result);
			}

		}
		else
		{
			die(json_encode(errors(417)));
		}

	}

	function endSession(){
		session_start();
		if (isset($_SESSION['fName']) && isset($_SESSION['lName']) && isset($_SESSION['email']))
		{
			unset($_SESSION['fName']);
			unset($_SESSION['lName']);
			unset($_SESSION['email']);
			session_destroy();
			
			echo json_encode(array('success' => 'Session deleted'));   	    
		}
		else
		{
			die(json_encode(errors(417)));
		}

	}

	function getUserData(){
		session_start();

		$result = getAllUserData($_SESSION['email']);

		echo json_encode($result);

	}


	function userPosts(){

		session_start();
		if (isset($_SESSION['email']))
		{
			$result = getUserPosts($_SESSION['email']);

			echo json_encode($result);
			
		}
		else
		{
			die(json_encode(errors(417)));
		}


	}

	function addReply()
	{
		session_start();
		if (isset($_SESSION['email']))
		{

			$data = $_POST['data'];
			$result = addMessageReply($_SESSION['email'], $data);

			echo json_encode($result);
			
		}
		else
		{
			die(json_encode(errors(417)));
		}
	}

	function showSpecificMessage()
	{
		session_start();
		if (isset($_SESSION['email']))
		{

			$id = $_POST['msgID'];
			
			$result = showMessageById($_SESSION['email'], $id);

			echo json_encode($result);
			
		}
		else
		{
			die(json_encode(errors(417)));
		}
	}

	function getSkillUserData(){
		session_start();

		$result = getAllSkillUserData($_POST['id']);

		echo json_encode($result);

	}

?>