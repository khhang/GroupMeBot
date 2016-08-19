<?php
	$configs = include("config.php");
	$GLOBALS['botID'] = $configs['botID'];
	$GLOBALS['botURL'] = $configs['botURL'];
	$GLOBALS['weatherKey'] = $configs['weatherKey'];
	$GLOBALS['weatherURL'] = $configs['weatherURL'];
	$GLOBALS['log'] = $configs['log'];

	function LogMessage($file,$message){
		$fp = fopen($file, "a");
		fputs($fp,$message . "\n");
		fclose($fp);
	}

	// Run bot command
	function RunCommand($text){
		 LogMessage($GLOBALS['log'], "Inside RunCommand");
		if(preg_match("/(![A-Za-z]+)\s(([A-Za-z0-9](\s)?)+)/",$text,$matches)){
			$command = $matches[1];
			$details = $matches[2];
			 LogMessage($GLOBALS['log'], "Found a match.");
		}

		if($command == "!echo"){
		 LogMessage($GLOBALS['log'], "Inside echo.");
			BotEcho($details);
		}elseif($command == "!weather"){
			BotWeather($details);
		}elseif($command != NULL){
			BotEcho("Invalid command.");
		}
	}

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
				$weatherInfo = "An error has occured. Please check inputs and try again.";
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

	// Grab last message from GroupMe POST request
	function GrabText($json){

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
