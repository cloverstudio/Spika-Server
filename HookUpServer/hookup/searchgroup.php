<?php
	
	include("./lib/startup.php");

	if(!empty($_GET['n'])){
		$nameQuery = urldecode($_GET['n']);
		$startKey = "\"{$nameQuery}\"";
		$endKey = "\"{$nameQuery}ZZZZ\"";
		$query = "?startkey={$startKey}&endkey={$endKey}";
		$result = doGet(HOST . "/" . DB . "/_design/app/_view/searchgroup_name{$query}");
		$nameResult = json_decode($result,true);
		
		$result = array();
		foreach($nameResult['rows'] as $row){
			$result[] = $row['value'];
		}

		echo json_encode($result,true);
	}else{
		
		$result = doGet(HOST . "/" . DB . "/_design/app/_view/searchgroup_name");
		$nameResult = json_decode($result,true);
		
		$result = array();
		foreach($nameResult['rows'] as $row){
			$result[] = $row['value'];
		}

		echo json_encode($result,true);
	}	
	
	
	
?>