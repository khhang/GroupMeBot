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
		if(preg_match("/(!([A-Za-z]+))(\s(([A-Za-z0-9](\s)?)+))?/",$text,$matches)){
			$command = $matches[1];
			$details = $matches[3];
			 LogMessage($GLOBALS['log'], "Found a match.");
		}

		if($command == "!echo"){
		 LogMessage($GLOBALS['log'], "Inside echo.");
			BotEcho($details);
		}elseif($command == "!weather"){
			BotWeather($details);
		}elseif($command == "!roll"){
			BotRoll($details);
		}elseif($command != NULL){
			BotEmoteCheck($matches[2]);
		}
	}

	// Gets current weather for specified location
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
				$weatherInfo = "Error my bomb is no working hehe my goat xd";
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

	// Checks if emote exists
    function BotEmoteCheck($emote){
        LogMessage($GLOBALS['log'], "Testing upload");
		$imageURL = "https://image.groupme.com/pictures?access_token=".$GLOBALS['token'];
		$filename = realpath("./Emotes/".$emote.".png");
		$image = array("file" => "@".$filename)

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


	// Post Emote to group chat
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
