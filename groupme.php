<?php
include "groupMeHelper.php";

if($_SERVER['REQUEST_METHOD'] == 'POST'){
	$command = GrabText(file_get_contents("php://input"));
	LogMessage("counter.txt", "Running command. " . $command);
	RunCommand($command);
	LogMessage("counter.txt", "After RunCommand.");
}


echo "If you see this, the page loaded correctly.";
?>

