<?php
include "groupMeHelper.php";

$bot = new GroupMeBot();
if($_SERVER['REQUEST_METHOD'] == 'POST'){
	$command = $bot->GrabText(file_get_contents("php://input"));
	$bot->RunCommand($command);
}


echo "If you see this, the page loaded correctly.";
?>

