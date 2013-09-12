<?php

	include("./lib/startup.php");

	$fileID = $_GET['file'];
	
	if(preg_match("/\./", $fileID))
		$fileID = substr($fileID, 0, strpos($fileID, "."));
	
	$filePath = ROOT_DIR . DS . "files" . DS . $fileID;
	
	
	$buffer = file_get_contents($filePath);
	
    header("Pragma: public");
    header('Content-disposition: attachment; filename='.$fileID);
    header("Content-type: ".mime_content_type($filePath));
    header("Content-Length: " . filesize($filePath) ."; ");
    header('Content-Transfer-Encoding: binary');
    header("HTTP/1.1 200 OK"); 
    
    unlink($filePath);
    
    echo $buffer; 
?>