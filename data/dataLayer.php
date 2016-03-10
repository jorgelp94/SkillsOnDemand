<?php

	function connect()
	{
		$servername = "localhost";
		$username = "root";
		$password = "";
		$dbname = "SkillsOnDemand";

		$connection = new mysqli($servername, $username, $password, $dbname);
	
		// Check connection
		if ($connection->connect_error) 
		{
		    return null;
		}
		else
		{
			return $connection;
		}
	}

	function errors($type)
	{
		$header = "HTTP/1.1 ";

		switch($type)
		{
			case 500:	$header .= "500 Bad connection to Database";
						break;
			case 206:	$header .= "206 Wrong Credentials";
						break;
			case 406:	$header .= "406 User Not Found";
						break;
			case 409:	$header .= "409 Conflict, Username already in use please select another one";
						break;
			case 417:	$header .= "417 No content set in the cookie/session";
						break;
			default:	$header .= "404 Request Not Found";
		}

		header($header);
		return array('message' => 'ERROR', 'code' => $type);
	}

	function login($email)
    {
        $conn = connect();

        if ($conn != null)
        {
        	$sql = "SELECT email, fName, lName, passwrd FROM Client WHERE email = '$email'";
			$result = $conn->query($sql);

			if ($result->num_rows > 0)
			{
				while($row = $result->fetch_assoc()) 
		    	{
		    		$response = array('message' => 'OK', 'fName' => $row['fName'], 'lName' => $row['lName'], 'email' => $row['email'], 'password' => $row['passwrd']);   
		    	}

		    	$conn->close();
		    	return $response;
			}
			else
			{
				$conn->close();
				return errors(406);
			}
        }
        else
        {
        	$conn->close();
        	return errors(500);
        }
    }

    function register($email, $password, $firstName, $lastName)
    {
    	$conn = connect();

    	if ($conn != null) 
    	{
    		
    		$sql = "SELECT email FROM Client WHERE email = '$email'";
			$result = $conn->query($sql);


    		if ($result->num_rows > 0)
			{
				$conn->close();
				return errors(409);
			}
			else 
			{
	    		$sql = "INSERT INTO Client (fName, lName, email, passwrd) VALUES ('$firstName', '$lastName', '$email', '$password')";
	    	
		    	if (mysqli_query($conn, $sql)) 
	    		{
				    $response  = array('message' => 'OK');
				}

				$conn->close();
				return $response;
			}

    	}
    	else
    	{
    		$conn->close();
    		return errors(500);
    	}
    }

    function newPost($title, $description, $firstname, $lastname, $email)
    {
    	$conn = connect();

    	if ($conn != null)
    	{
    		$sql = "SELECT email FROM Post WHERE email = '$email'";
    		$result = $conn->query($sql);

    		if ($result->num_rows > 0)
    		{
    			$conn->close();
    			return errors(409);
    		}
    		else
    		{
    			$sql = "INSERT INTO Post (title, description, fName, lName, email) VALUES ('$title', '$description', '$firstName', '$lastName', '$email'";

    			if (mysqli_query($conn, $sql)) 
    			{
    				$response = array('message' => 'OK');
    			}

    			$conn->close();
    			return $response;
    		}
    	}
    	else
    	{
    		$conn->close();
    		return errors(500);
    	}
    }

    function startSession($fName, $lName, $email)
    {
		// Starting the session
	    session_start();
	    if (! isset($_SESSION['fName']))
	    {
	    	$_SESSION['fName'] = $fName;
	    }
	    if (! isset($_SESSION['lName']))
	    {
	    	$_SESSION['lName'] = $lName;
	    }
	    if (! isset($_SESSION['email']))
	    {
	    	$_SESSION['email'] = $email;
	    }
    }

    function getSession()
    {
    	session_start();
    	if (isset($_SESSION['fName'])) {
    		$response = array('message' => 'OK' ,'name' => $_SESSION['fName']);
    		return $response;
    	} else {
    		$response = array('name' => 'user');
    		return $response;
    	}
    }

    function getCartItems($email, $status)
    {
    	$conn = connect();

        if ($conn != null)
        {

        	if ($status == 'P') {
        		// Get cart elements 
        		$sql = "SELECT * FROM Cart WHERE email = '$email' AND status = 'P'";
        	} elseif ($status == 'B') {
        		// Get cart elements 
        		$sql = "SELECT * FROM Cart WHERE email = '$email' AND status = 'B'";
        	}

        	$result = $conn->query($sql);

        	if ($result->num_rows > 0)
			{

				$response;
				$data = array();
				$secondData = array();
				$thirdData = array(); 
				
				while($row = $result->fetch_assoc()) 
		    	{
		    		// Get email and skill to display on table
		    		$rowContent = $row['skill'];
		    		//echo $rowContent;
		    		$sql2 = "SELECT * FROM Skill WHERE skillId = '$rowContent'";

		    		$result2 = $conn->query($sql2);

		    		if ($result2->num_rows > 0) {
		    			

		    			while ($row2 = $result2->fetch_assoc()) {

		    				$rowEmail = $row2['email'];
		    				//echo $rowEmail;

		    				// Get client info based on email o display name on cart
		    				$sql3 = "SELECT * FROM Client WHERE email = '$rowEmail'";

		    				$result3 = $conn->query($sql3);

		    				if ($result3->num_rows > 0) {
		    					

		    					while ($row3 = $result3->fetch_assoc()) {
		    			// 			echo "Pasa" . $num;
		    			// $num = $num + 1;
		    			// 			echo "push a " . $row3['fName'];
		    						array_push($thirdData, array('name' => $row3['fName'], 'last' => $row3['lName']));
		    						//echo $thirdData;
		    					}
		    					
		    				}

		    				array_push($secondData, array('sEmail' => $row2['email'], 'sTitle' => $row2['title']));
		    			}
		    		}

		    		array_push($data, array('orderId' => $row['orderId'], 'quantity' => $row['quantity'], 'skillId' => $row['skill']));
		    	}

		    	$response = array('message' => 'OK', 'data' => $data, 'secondData' => $secondData, 'thirdData' => $thirdData);
		    	$conn->close();
		    	return $response;
			}
			else
			{
				return array('message' => 'NONE');   
			}

        }
        else
        {
        	$conn->close();
        	return errors(500);
        }
    }


    function addSkill ($data, $email){
    	$conn = connect();

    	if ($conn != null){

    		
    			$title = $data['title'];
    			$quantity = $data['quantity'];
    			$description = $data['description'];
    			$category = $data['category'];
    			$sql = "INSERT INTO Skill(title, description, email, category, quantity) VALUES ('$title','$description','$email', '$category', '$quantity')";
    			if (mysqli_query($conn, $sql)) {
		    		$conn->close();
				    return array("status" => "COMPLETE");
				} 
				else {
					$conn->close();
					return errors(409);
				}
			
			
        }
        else {
        	$conn->close();
        	return errors(500);
        }
    }


    function getAllSkills(){
    	$conn = connect();


    	session_start();
    	if (isset($_SESSION['email']) ){
    		$sessionEmail = $_SESSION['email'];
    	} else {
    		$sessionEmail = "";
    	}

        if ($conn != null) {
        	$sql = "SELECT * FROM Skill";
			$result = $conn->query($sql);

			
			# Items exist
			$response = array();
			if ($result->num_rows > 0) {
				while($row = $result->fetch_assoc()) {
					array_push($response, array('skillId' => $row['skillId'], 'title' => $row['title'], 'description' => $row['description'],'email' => $row['email'],'category' => $row['category'], 'quantity' => $row['quantity']));
				}
				array_push($response, array('sessionEmail' => $sessionEmail));

				//echo var_dump($response);

				return $response;
			}
			else {
				$conn->close();
				return $response; // no existen items
			}
        }
        else {
        	# Connection to Database was not successful
        	$conn->close();
        	return errors(500);
        }
    }

   //  function quantity($skill)
   //  {

   //  	$conn = connect();

   //  	if($conn != null)
   //  	{
   //  		$sql = "SELECT quantity FROM Skill WHERE skillId = $skill";

   //  		$result = $conn->query($sql);
   //  		$response = array();
		 //    if ($result->num_rows > 0) {
		    			
		 //    	while ($row = $result->fetch_assoc()) {
		 //    		array_push($response,'quantity' => $row['quantity']);
		 //    	}

		 //    	$conn->close();
		 //    	return $response;
			// }
			// else {
			// 	$response = array("message" => "error");
			// }
   //  	}
   //  	else {
   //      	# Connection to Database was not successful
   //      	$conn->close();
   //      	return errors(500);
    //     }
    // }

    function hirePeople($email, $name)
    {
    	$conn = connect();

    	if($conn != null)
    	{
    			$result = getCartItems($email, 'P');

    			echo var_dump($result);
    			if ($result['message'] == 'OK') {
    				//echo var_dump($result);
    				//echo $result['secondData'][0]['sEmail'];
    				//echo count($result['secondData']);
    			}

    			$sql = "UPDATE Cart SET status = 'B' WHERE email = '$email' AND status = 'P'";
    			if (mysqli_query($conn, $sql)) {
    				// $newquantity = $counter+1;

    				//$response = array("message" =>"OK", "number" => $newquantity);

    				$amount = count($result['secondData']);
    				$counter = 0;

    				while ($counter < $amount) {
    						    		// $sql = "INSERT INTO Client (fName, lName, email, passwrd) VALUES ('$firstName', '$lastName', '$email', '$password')";
    					$emailToSend = $result['secondData'][$counter]['sEmail'];
    					$personName = $result['thirdData'][$counter]['name'];
    					//echo $emailToSend;
    					$dateNow = date("Y/m/d");
    					//echo $dateNow;
    					$sql2 = "INSERT INTO Message (sentTo, sentFrom, messageDate, message, status) VALUES ('$emailToSend', '$email', '$dateNow', '$name wants to hire you!', 'N')";


    					if (mysqli_query($conn, $sql2)) {
    						$response = array("message" => "OK", "emails" => "OK");
    					} else {
    						$response = array("message" => "OK", "emails" => "OK");
    					}
    					$counter = $counter + 1;
    				}

    				
    			} else {
    				return errors(409);
    			}
    			//$counter++;
    		//}

    		return $response;
    	}
    	else 
    	{
    		$conn->close();
    		return errors(409);
    	}
    }


    function updateUserProfile($data, $userName){
        $conn = connect();

        if ($conn != null){

            
                $year = $data['yearBdate'];
                $country = $data['country'];
                $city = $data['city'];
                $website = $data['website'];
                $phone = $data['phone'];
                $university = $data['university'];
                $interests = $data['interests'];
                $additional = $data['additional'];



                $sql = "UPDATE Client SET yearBdate='$year', country='$country', city='$city', website='$website', phone='$phone', university='$university', interests='$interests',more='$additional'  WHERE email='$userName'";
                if (mysqli_query($conn, $sql)) {
                    $conn->close();
                    return array("status" => "COMPLETE");
                } 
                else {
                    $conn->close();
                    return errors(409);
                }
            
            
        }
        else {
            $conn->close();
            return errors(500);
        }
    }



    function addCart($email, $id){
        $conn = connect();

        if ($conn != null) {
            $sql = "INSERT INTO Cart (email, skill, quantity, status) VALUES ('$email', '$id', '1', 'P')";
            $response = validate($email, $id);
            //echo var_dump($response);

            if ($response["message"] == "OK") {
                if (mysqli_query($conn, $sql)) {
                    $conn->close();
                    return array("message" => "COMPLETE");
                } 
                else {
                    $conn->close();
                    return errors(409);
                }


            }
            else{
                return array("message" => "ERROR");

            }
        }
        else 
        {
            $conn->close();
            return errors(409);
        }

    }

    function validate($email, $id){
        $conn = connect();

        if ($conn != null) {

            $sql = "SELECT * FROM Cart WHERE email = '$email' AND status = 'P' AND skill='$id' ";

            $result = $conn->query($sql);

            $response = array();

            if ($result->num_rows > 0){
                $response = array("message" => "ERROR");
                $conn->close();
                return $response; 
            }
            else {
                $response = array("message" => "OK");
                $conn->close();
                return $response; 
            }
            
        }
        else 
        {
            $conn->close();
            return errors(409);
        }

    }
/*
    function addCart($email, $id)
    {
    	$conn = connect();

    	if ($conn != null) {
    		
    		$sql = "INSERT INTO Cart (email, skill, quantity, status) VALUES ('$email', '$id', '1', 'P')";

    		$response = validate($email);

    		echo var_dump($response);

    		if ($response["message"] == "OK") {

    			$amount = count($response['ID']);
    			$flag = 0;
    			for ($i = 0; $i < $amount; $i++) { 
    				//echo "ID = " . $id . ", SkillID = " . $response['ID'][$i]['skillId'];
  					if ($id == $response['ID'][$i]['skillId']) {
  						$flag++;
  					}
    			}

    			if ($flag == 0) {
    				if (mysqli_query($conn, $sql)) {
    					$response = array("message" => "OK");
    				} else {
    					$response = array("message" => "ERROR");
    				}
    			} else {
    				$response = array("message" => "ERROR2","content" => "You can't add the same skill more than one time.");
    			}

    			
    		} else {
    			$response = array("message" => "ERROR");
    		}

    		

    		return $response;

    	}
    	else 
    	{
    		$conn->close();
    		return errors(409);
    	}
    }

    function validate($email)
    {
    	$conn = connect();

    	if ($conn != null) {

    		$sql = "SELECT * FROM Cart WHERE email = '$email' AND status = 'P'";

    		$result = $conn->query($sql);

    		$response = array();

        	if ($result->num_rows > 0)
			{
				//echo "entra";
				$arrayID = array();
				
				while($row = $result->fetch_assoc()) 
		    	{
		    		//echo $row['skill'];
		    		array_push($arrayID, array('skillId' => $row['skill']));

		    	}

		    	$response = array("message" => "OK", "ID" => $arrayID);
		    	return $response;
		    }
		    else {
                $response = array("message" => "OK");
				$conn->close();
				return $response; // no existen items
			}
    		# traer los elementos de cart y regresar el skill agreagdo en cada uno
    		# para despues  comparar el id del skill que se va agregar con los resultados
    	}
    	else 
    	{
    		$conn->close();
    		return errors(409);
    	}
    }
    */

    function messages($email)
    {
    	$conn = connect();

    	if ($conn != null) {
    		$sql = "SELECT * FROM Message WHERE sentTo = '$email'";

    		$result = $conn->query($sql);

    		$response = array();
    		$data = array();
    		if ($result->num_rows > 0) {
    			while ($row = $result->fetch_assoc()) {
    				$name = getName($row['sentFrom']);
    				array_push($data, array("name" => $name, "email" => $row['sentFrom'], "id" => $row['messageId'], "message" => $row['message'], "status" => $row['status']));
    			}

    			$response = array("message" => "OK", "data" => $data);
    			//echo var_dump($response);

    			return $response;
    		}
    		else {
    			$conn->close();
    			$response = array("message" => "No messages");
				return $response;
    		}
    	}
    	else 
    	{
    		$conn->close();
    		return errors(409);
    	}
    }

    function getName($email)
    {
    	$conn = connect();

    	if ($conn != null) {
    		$sql = "SELECT fName, lName FROM Client WHERE email = '$email'";

    		$result = $conn->query($sql);

    		$data = array();
    		$response = array();

    		if ($result->num_rows > 0) {
    			while ($row = $result->fetch_assoc()) {
    				array_push($data,array("name" => $row['fName'] . " " . $row['lName']));
    			}

    			$response =$data;

    			return $response;
    		}
    		else {
				$conn->close();
				return $response; // no existen items
			}

    	}
    	else 
    	{
    		$conn->close();
    		return errors(409);
    	}

    }

    function getAllUserData($email){

        $conn = connect();

        if ($conn != null) {
            $sql = "SELECT * FROM Client WHERE email = '$email'";

            $result = $conn->query($sql);

            $data = array();
            $response = array();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    array_push($data,array("year" => $row['yearBdate'], "country" => $row['country'], "city" => $row['city'],"website" => $row['website'], "phone" => $row['phone'], "university" => $row['university'], "interests" => $row['interests'], "more" => $row['more'] ));
                }

                $response =$data;

                return $response;
            }
            else {
                $conn->close();
                return $response; // no existen items
            }

        }
        else 
        {
            $conn->close();
            return errors(409);
        }


    }

    function getUserPosts($email){

        $conn = connect();

        if ($conn != null) {
            $sql = "SELECT * FROM Skill WHERE email = '$email'";

            $result = $conn->query($sql);

            $data = array();
            $response = array();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    array_push($data,array("skillId" => $row['skillId'], "email" => $row['email'], "title" => $row['title'],"description" => $row['description'], "category" => $row['category'], "quantity" => $row['quantity']));
                }

                $response =$data;

                return $response;
            }
            else {
                $conn->close();
                return $response; // no existen items
            }

        }
        else 
        {
            $conn->close();
            return errors(409);
        }

    }

    function showMessageById($email, $id)
    {
    	$conn = connect();

        if ($conn != null) {
            $sql = "SELECT * FROM Message WHERE messageId = '$id'";

            $result = $conn->query($sql);

            $data = array();
            $response = array();

            if ($result->num_rows > 0) {
            	while ($row = $result->fetch_assoc()) {
    				$name = getName($row['sentFrom']);
    				array_push($data, array("name" => $name, "email" => $row['sentFrom'], "message" => $row['message'], "status" => $row['status']));
    			}
    			$response = array("message" => "OK", "data" => $data);
    			//echo var_dump($response);

    			return $response;
            }
            else {
    			$conn->close();
				return $response;
    		}
        }
        else 
    	{
    		$conn->close();
    		return errors(409);
    	}
    }

    function addMessageReply($from, $data)
    {
    	$conn = connect();

    	$message = $data['reply'];
    	$sendTo = $data['sendTo'];

        if ($conn != null) {
        	$dateNow = date("Y/m/d");
        	$sql = "INSERT INTO Message (sentTo, sentFrom, messageDate, message, status) VALUES ('$sendTo', '$from', '$dateNow', '$message', 'N')";

        	if (mysqli_query($conn, $sql)) {
        		$conn->close();
                return array("status" => "COMPLETE");
        	}else {
                $conn->close();
                echo "error in connection";
                return errors(409);
            }
        }
        else 
    	{
    		$conn->close();
    		echo "error null";
    		return errors(409);
    	}
    }



    function getAllSkillUserData($id){
        $conn = connect();

        if ($conn != null) {
            $sql = "SELECT email FROM Skill WHERE skillId = '$id'";

            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $email = $row['email'];
                }

                $sql = "SELECT * FROM Client WHERE email = '$email'";
                $result = $conn->query($sql);

                    $data = array();
                    $response = array();


                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            array_push($data,array("year" => $row['yearBdate'], "country" => $row['country'], "city" => $row['city'],"website" => $row['website'], "phone" => $row['phone'], "university" => $row['university'], "interests" => $row['interests'], "more" => $row['more'] ));
                          
                        }

                        $response =$data;

                        return $response;
                    }
                    else {
                        $conn->close();
                        return $response; // no existen items
                    }
            }

            else {
                $conn->close();
                return $response; // no existen items
            }

        }
        else 
        {
            $conn->close();
            return errors(409);
        }
    }

?>