<?php
	include("./lib/startup.php");
	
	if(!isset($_GET['docment_id'])){
		print 'NG';
	}else{
		$documentId = $_GET['docment_id'];
		
		mail("ken.yasue@clover-studio.com","HookupViolation","$documentId","ken.yasue@clover-studio.com");

		print 'OK';
	}
	
?>