<?php
	include("./lib/startup.php");
	$result = doGet(HOST . "/" . DB . "/_design/app/_view/finduser_by_id");
	
	$resultAry = json_decode($result,true);
	
	print_r($resultAry);
?>