
<?php
	define("APP","spikademo");

	include("../../lib/startup.php");

	// clear first
	$result = doGet(HOST . "/" . DB . "/_design/app/_view/find_group_categories");
	$resultData = json_decode($result,true);
	
	foreach($resultData['rows'] as $data){
	
		$id = $data['value']['_id'];
		$rev = $data['value']['_rev'];
		
		
		$url = HOST . "/" . DB . "/" . $id . "?rev=" . $rev;
		
		$result = doDelete($url);
		
	}

	$filesPath = ROOT_DIR . DS . "resouces" . DS . "categoryimages";

	$imgbinary = @file_get_contents($filesPath . DS . "cat_1.png");
	$base64EncodedImage = base64_encode($imgbinary);
	
	$pathinfo = pathinfo($path);

	$dataAry = array(
		'type' => 'group_category',
		'title' => 'Test Category 1',
		'_attachments' => array(
			"picture.png" => array(
				"content_type" => "image/png",
				"data" => $base64EncodedImage
			)
		)
		
	);

	$result = doPost(HOST . "/" . DB,$dataAry);
	
	print $result . "\n";

	$imgbinary = @file_get_contents($filesPath . DS . "cat_2.png");
	$base64EncodedImage = base64_encode($imgbinary);
	
	$pathinfo = pathinfo($path);

	$dataAry = array(
		'type' => 'group_category',
		'title' => 'Test Category 2',
		'_attachments' => array(
			"picture.png" => array(
				"content_type" => "image/png",
				"data" => $base64EncodedImage
			)
		)
		
	);

	$result = doPost(HOST . "/" . DB,$dataAry);
	
	print $result . "\n";

?>
