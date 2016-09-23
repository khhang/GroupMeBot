<?php
	$configs = include("config.php");
	$GLOBALS['botID'] = $configs['botID'];
	$GLOBALS['botURL'] = $configs['botURL'];
	$GLOBALS['weatherKey'] = $configs['weatherKey'];
	$GLOBALS['weatherURL'] = $configs['weatherURL'];
	$GLOBALS['token'] = $configs['token'];
	$GLOBALS['log'] = $configs['log'];

	function LogMessage($file,$message){
		$fp = fopen($file, "a");
		fputs($fp,$message . "\n");
		fclose($fp);
	}

	//
	//Start of bot commands
	//

	// Run bot command
	function RunCommand($text){
		 LogMessage($GLOBALS['log'], "Inside RunCommand");
		if(preg_match("/(!([A-Za-z]+))(\s((.*(\s)?)+))?/",$text,$matches)){
			$command = $matches[1];
			$details = $matches[4];
			LogMessage($GLOBALS['log'], "Found a match.");
		}

		if($command == "!echo"){
		 LogMessage($GLOBALS['log'], "Inside echo.");
			BotEcho($details);
		}elseif($command == "!weather"){
			BotWeather($details);
		}elseif($command == "!roll"){
			BotRoll($details);
		}elseif($command == "!yelp"){
			BotYelp($details);
		}elseif($command == "!help"){
			BotHelp($details);
		}elseif($command != NULL){
			BotEmoteCheck($matches[2]);
		}
	}

	function BotHelp($command){
		switch($command){
			case "echo":
				BotEcho("The echo command will display input text in chat.");
				break;
			case "weather":
				BotEcho("The weather command will display current forecast of given location.");
				break;
			case "roll":
				BotEcho("The roll command will roll a random number from 1 to given input.");
				break;
			case "yelp":
				BotEcho("The yelp command will display information about specified business around given location.");
				break;
			case "emotes":
				BotEcho("List of available emotes: \n I'll add this later.");
				break;
			default:
				BotEcho("Type help with following command for more info: \n" .
					"1. echo \n" .
					"2. weather \n" .
					"3. roll \n" .
					"4. yelp \n" .
					"5. emotes \n");
		}
	}

	// Gets weather for specified location
	function BotWeather($loc){
		$url = $GLOBALS['weatherURL'];
		$params = array("key" => $GLOBALS['weatherKey'], "q" => $loc);
		$query = $url . "?" . http_build_query($params);

	    $result = file_get_contents($query);

		if($result === FALSE){
			LogMessage($GLOBALS['log'], "No results from weather POST.");
		}else{
			LogMessage($GLOBALS['log'],$result);
			$jsonresult = json_decode($result);
			if($jsonresult->location->name != NULL){
				$weatherInfo = "Location: " . $jsonresult->location->name . ", " . $jsonresult->location->country .
	            	"\nCurrent Temp(F): " . $jsonresult->current->temp_f . ", " . $jsonresult->current->condition->text .
	            	"\nFeels Like(F): " . $jsonresult->current->feelslike_f;
			}else{
				$weatherInfo = "Could not find location.";
			}
			BotEcho($weatherInfo);
		}
	}

	// Send POST request for bot reply
	function BotEcho($message){
		LogMessage($GLOBALS['log'], "Executing echo command.");
	     $url = $GLOBALS['botURL'];
	     $reply = array("bot_id" => $GLOBALS['botID'], "text" => $message);
	     $options = array(
	     	"http" => array(
	        	"header" => "Content-type: application/json\r\n",
	            "method" => "POST",
	            "content" => json_encode($reply)
	     	)
	     );
	     $context = stream_context_create($options);
	     $result = file_get_contents($url, false, $context);

	     if($result === FALSE){ LogMessage($GLOBALS['log'], "No results."); }
	}

	function BotRoll($max){
		if($max != NULL){
			BotEcho("Rolled " . strval(rand(1,$max)) . " out of $max");
		}else{
			BotEcho("Rolled " . strval(rand(1,6)) . " out of 6");
		}
	}

	// Check if emote exists
    function BotEmoteCheck($emote){
		$imageURL = "https://image.groupme.com/pictures?access_token=".$GLOBALS['token'];
		$filename = realpath("./Emotes/".$emote.".png");
		$image = array("file" => "@".$filename);

		$ch = curl_init($imageURL);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $image);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
		curl_close($ch);

        if($result === FALSE){
			LogMessage($GLOBALS['log'], "No results.");
			BotEcho("Invalid command");
		}else{
			LogMessage($GLOBALS['log'], $result);
			$jsondata = json_decode($result);
			BotPostEmote($jsondata->payload->picture_url);
		}
    }

	// Posts bot image to group chat
	function BotPostEmote($imageURL){
		LogMessage($GLOBALS['log'], "Unleashing the KAPPA.");
		$url = $GLOBALS['botURL'];
		$reply = array(
			"bot_id" => $GLOBALS['botID'], 
			"attachments" => array(array(
				"type" => "image",
				"url" => $imageURL
				))
			);
		$options = array(
			"http" => array(
				"header" => "Content-type: application/json\r\n", 
				"method" => "POST", 
				"content" => json_encode($reply)
				)
			);

		$context = stream_context_create($options);
		LogMessage($GLOBALS['log'], json_encode($reply));
		$result = file_get_contents($url, false, $context);

		if($result === FALSE){ LogMessage($GLOBALS['log'], "No results."); }

	}

	function BotYelp($details){
		include "yelp_test/yelp.php";

		LogMessage($GLOBALS['log'], "Inside YelpCommand");

		// Break our arguments into our category and location
		// And set default parameters if empty
		list($term, $location) = explode(',', $details);
		if(empty($term)){
			$term = "manna korean bbq";
		}
		if(empty($location)){
			$location = "92129";
		}

		// If user enters in help, prompt them with correct input
		// format to get best results.
		if(strcmp($term, "help") == 0){
			$message = "Enter in command as follows:\n!yelp <name of place>, <location>\n" 
						. "Ex: !yelp manna korean bbq, mira mesa";
			BotEcho($message);
			exit();
		}

		$term_entries = explode(' ', $term);
		$search_entry = strtolower(preg_replace('/\s*/', '', $location));

		LogMessage($GLOBALS['log'], "our location: $location");
		LogMessage($GLOBALS['log'], "our restaraunt: $term");
		LogMessage($GLOBALS['log'], "our restaraunt: $term_entries[0]");
		LogMessage($GLOBALS['log'], "our location no space/case: $search_entry");
		
		// Priority queue to find the most optimal entry
		$response = json_decode(search($term, $location));
		$business = $response->businesses;
		$size = count($business);
		$pq = new SplPriorityQueue();
		
		// Query the return results to find the most accurate locations based
		// on matching entries in our term
		for($i = 0; $i < $size; $i++){
			$business_id = $business[$i]->id;
			$response = get_business($business_id);
			$business_name = json_decode($response)->name;
			$business_name = strtolower(preg_replace('/\s*/', '', $business_name));
			$priority = 0;
			for($j = 0; $j < count($term_entries); $j++){
				if(substr_count($business_name, $term_entries[$j]) > 0){
					$priority++;
				}
			}

			if($priority > 0){
				$pq->insert($response, $priority);
				LogMessage($GLOBALS['log'], "our name: $business_name");
			}
		}
		
		// Further narrow down these entries to ensure that the 1st matching neighborhood
		// that appears would be the most accurate to our search request.
		// If there is no neighborhood associated with our search value, return the top
		$pq->setExtractFlags(3);
		$pq->top();
		$accurate = json_decode($pq->current()['data']);

		while($pq->valid()){
			$response = json_decode($pq->current()['data']);
			$priority = json_decode($pq->current()['priority']);
			$best_name = False;
			$best_location = False;
			
			if(array_key_exists('neighborhoods', $response->location)){
				$neighborhood = $response->location->neighborhoods[0];
				$neighborhood = strtolower(preg_replace('/\s*/', '', $neighborhood));
				if(strcmp($neighborhood, $search_entry) == 0){
					$best_location = True;
				}
			}
			if($priority == count($term_entries)){
				$best_name = True;
			}
			if($best_location && $best_name){
				$accurate = $response;
				break;
			}
			$pq->next();
		}

		// Creating response message
		$response_url = $accurate->mobile_url;
		$rating = $accurate->rating;
		$address = $accurate->location;
		$address_message = "";
		for($i = 0; $i < count($address->display_address); $i++){
			if($i == count($address->display_address)-1){
				$address_message .= $address->display_address[$i];
				break;
			}
			$address_message .= $address->display_address[$i] . ", ";
		}

		$message = $response_url . "\n" . "Rating: $rating\n"
					. "Address: $address_message\n";
		BotEcho($message);
	}


	//
	// End of bot commands
	//

	// Grab last message from GroupMe POST request
	function GrabText($json){
		//LogMessage($GLOBALS['log'], $json);
		// Iterate through data
	    $jsonIterator = new RecursiveIteratorIterator(
	        new RecursiveArrayIterator(json_decode($json, TRUE)),
	        RecursiveIteratorIterator::SELF_FIRST);

	    foreach($jsonIterator as $key => $val){
	        if($key == "text"){
	            LogMessage($GLOBALS['log'], "\n$key => $val");
	            return $val;
			}
		}
	}
?>
