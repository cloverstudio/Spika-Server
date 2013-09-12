<?php

	function curPageURL() {
		$pageURL = 'http';
		
		if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
			$pageURL .= "s";
		}
		
		$pageURL .= "://";
		
		if ($_SERVER["SERVER_PORT"] != "80") {
			$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		} else {
			$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		}
		
		return $pageURL;
	}

	$currentURL = curPageURL();
	$currentURLPath = str_replace("index.php", "", $currentURL);
	$appTop = str_replace("installer/", "", $currentURLPath);
	
	define("ROOT_URL",$appTop);
	define("ROOT_DIR",dirname(dirname(__FILE__)));
	define("INSTALLMODE",true);
	
	// check PHP version
	$versionOK = false;
	if (version_compare(PHP_VERSION, '5.0.0') >= 0) {
    	$versionOK = true;
    }
	
	// check mysql
	$mysqlOK = false;
	if (function_exists('mysql_connect')){
		$mysqlOK = true;
	}
	
	// check command
	$commandLineOK = false;
	exec("php --version", $output);
	if(preg_match("/PHP/",$output[0])){
		$commandLineOK = true;
	}
	
	$everythingOK = $versionOK && $mysqlOK && $commandLineOK;
	$createOK = false;

	if(!isset($_POST['host'])){
		$_POST['host'] = "";
	}
	
	if(!isset($_POST['database'])){
		$_POST['database'] = "";
	}
	
	if(!isset($_POST['username'])){
		$_POST['username'] = "";
	}
	
	if(!isset($_POST['password'])){
		$_POST['password'] = "";
	}
	
	if(
		!empty($_POST['host']) || 
		!empty($_POST['database']) || 
		!empty($_POST['username']) || 
		!empty($_POST['password'])){
		
		$db_host = trim($_POST['host']);
		$db_user = trim($_POST['username']);
		$db_name = trim($_POST['database']);
		$db_pass = trim($_POST['password']);
		
		$connection = mysql_connect($db_host, $db_user, $db_pass,true);
		$selectDbResult = null;
		$customErr = "";
		$tableCreated = false;
		
		if ($connection) {
			$selectDbResult = mysql_select_db($db_name, $connection);
			
			if ($selectDbResult) {

				$filePath = ROOT_DIR . "/installer/create.sql";
				$createSQL = file_get_contents($filePath);
				
				if(empty($createSQL))
					$customErr = "Cannot find {$filePath}.";
					
				if(empty($customErr)){
					
					$queries = explode(";",$createSQL);
					
					foreach($queries as $query){
						
						$query = trim($query);

						if(empty($query))
							continue;
							
						$resultLink = mysql_query($query,$connection);
						
						if (!$resultLink) {
							$customErr .= "Invalid query: {$query} <br />";
						}
						
					}
				}
				
				if(empty($customErr)){
					
					$tableCreated = true;
					
				}
				
			}
		}
	}

	include('../admin/header.php');
?>
	
	<?php if(isset($tableCreated) && $tableCreated) { ?>

			<div class="container-fluid">
				<div class="page-header">
					<div class="pull-left">
						<h1>PushSrv Installer</h1>
					</div>
				</div>
				<div class="row-fluid">
				
					<div class="alert alert-success">
						<strong>Everything done !</strong> <br />
						Please copy&paste following code to lib/init.php.
					</div>
							

					<div class="span12">
						<div class="box">
							<div class="box-title">
								<h3><i class="icon-ok"></i> Please copy&paste following content to lib/init.php </h3>
							</div>
							<div class="box-content">
								
								<strong>lib/init.php</strong>
								
								<pre>
&lt;?php 

	/////////////////////////////////////////////////////////////////////////////////////
	// Database settings
	/////////////////////////////////////////////////////////////////////////////////////

	define("DB_HOST","<?php echo $db_host ?>");
	define("DB_NAME","<?php echo $db_name ?>");
	define("DB_USER","<?php echo $db_user ?>");
	define("DB_PASS","<?php echo $db_pass ?>");
	
	/////////////////////////////////////////////////////////////////////////////////////
	// Debug settings
	/////////////////////////////////////////////////////////////////////////////////////
	
	define('ShowErr',false);
	define('LogAccess',false);
	define('LogErr',false);
	define('LogQuery',false);
	
	/////////////////////////////////////////////////////////////////////////////////////
	// Pushnotification settings
	/////////////////////////////////////////////////////////////////////////////////////

	define('GCM_API_KEY',""); // please get gcm api key from google
	define('APN_DEV_CERT',"/var/www/..."); // full path to apn dev cert
	define('APN_PROD_CERT',"/var/www/..."); // full path to apn production cert
	
	/////////////////////////////////////////////////////////////////////////////////////
	// QUEUE settings
	/////////////////////////////////////////////////////////////////////////////////////
	
	define('USE_QUEUE',true); // this should be false if you can't execute command
	define('MAX_REQUESTS_PER_INTERNAL',20);
	define('RELEASE_INTERVAL',1); // sec
	
	/////////////////////////////////////////////////////////////////////////////////////
	// SYSTEM settings
	/////////////////////////////////////////////////////////////////////////////////////

	define('PHP_COMMAND',"php"); // apsolute path for php command
	define('ROOT_URL','<?php echo ROOT_URL ?>');
	define('SP_TIMEOUT',20); // sec
	
	/////////////////////////////////////////////////////////////////////////////////////
	// ADMIN settings
	/////////////////////////////////////////////////////////////////////////////////////

	define('SHOW_SERVERSTAT',true); 
	define('ADMIN_USERNAME',"admin");
	define('ADMIN_PASSWORD',"password");
?&gt;

								</pre>
							</div>
						</div>
					</div>
						
				</div>
			</div>


	<?php } else { // if($tableCreated) {?>
			<div class="container-fluid">
				<div class="page-header">
					<div class="pull-left">
						<h1>PushSrv Installer</h1>
					</div>
				</div>
				<div class="row-fluid">
										
						<div class="alert alert-warn">
							<strong>Required environment</strong> <br />
								<ul>
									<li> PHP 5.0 or higher </li>
									<li> MySQL 4.0 or higher</li>
									<li> Linux (not necessary but you probably should to change code)</li>
									<li> Permission to run command from PHP  </li>
								</ul>
						</div>

							<?php if(!$everythingOK) { ?>

								<div class="alert alert-error">
									<strong>You cannot install via installer</strong> <br />
									Installer consider your environment cannot support pushsrv.
									But you can install manually or fix source code by your self.
								</div>

							<?php } ?>

							<?php if(!empty($_POST['host']) && $connection == null) { ?>

								<div class="alert alert-error">
									<strong>Failed to connect Database</strong> <br />
									Make sure you put correct information.
								</div>

							<?php } ?>
							
							<?php if(!empty($_POST['host']) && $selectDbResult == null) { ?>

								<div class="alert alert-error">
									<strong>Failed to select Database</strong> <br />
									Please confirm database name is correct.
								</div>

							<?php } ?>
							
							
							<?php if(!empty($customErr)) { ?>

								<div class="alert alert-error">
									<?php echo $customErr ?>
								</div>

							<?php } ?>
							
					<div class="span12">
						<div class="box">
							<div class="box-title">
								<?php if ($everythingOK) { ?>
									<h3><i class="icon-ok"></i> Checking...OK</h3>
								<?php } else { ?>
									<h3><i class="icon-remove-sign"></i> FAILED!</h3>
								<?php } ?>
							</div>
							<div class="box-content">
								<form action="#" method="POST" class='form-horizontal'>
									<div class="control-group">
										<label for="textfield" class="control-label">PHP version</label>
										<div class="controls">
											<?php if ($versionOK) { ?>
												<span class="label label-success">success</span>
											<?php } else { ?>
												<span class="label label-important">failed!</span>
												Please use PHP5.0 or higher.
											<?php } ?>
										</div>
									</div>


									<div class="control-group">
										<label for="textfield" class="control-label">MySQL Support</label>
										<div class="controls">
											<?php if ($mysqlOK) { ?>
												<span class="label label-success">success</span>
											<?php } else { ?>
												<span class="label label-important">failed!</span>
											<?php } ?>
										</div>
									</div>


									<div class="control-group">
										<label for="textfield" class="control-label">Permission</label>
										<div class="controls">
											<?php if ($commandLineOK) { ?>
												<span class="label label-success">success</span>
											<?php } else { ?>
												<span class="label label-important">failed!</span>
											<?php } ?>
										</div>
									</div>

								</form>
							</div>
						</div>
					</div>
					
					<?php if ($everythingOK) { ?>
					
						<div class="span12">
							<div class="box">
								<div class="box-title">
									<h3><i class=" icon-download-alt"></i> Please input database settings</h3>
								</div>
								<div class="box-content">
									<form action="#" method="POST" class='form-horizontal'>
										<div class="control-group">
											<label for="textfield" class="control-label">Host</label>
											<div class="controls">
												<input type="text" name="host" id="textfield" value="<?php echo $_POST['host'] ?>" class="input-xlarge">
											</div>
										</div>
	
	
										<div class="control-group">
											<label for="textfield" class="control-label">Database name</label>
											<div class="controls">
												<input type="text" name="database" id="textfield" value="<?php echo $_POST['database'] ?>" class="input-xlarge">
											</div>
										</div>
	
	
										<div class="control-group">
											<label for="textfield" class="control-label">Database user name</label>
											<div class="controls">
												<input type="text" name="username" id="textfield" value="<?php echo $_POST['username'] ?>" class="input-xlarge">
											</div>
										</div>
	
	
										<div class="control-group">
											<label for="textfield" class="control-label">Password</label>
											<div class="controls">
												<input type="text" name="password" id="textfield" value="<?php echo $_POST['password'] ?>" class="input-xlarge">
											</div>
										</div>
	
	
										<div class="control-group">
											<label for="textfield" class="control-label"></label>
											<div class="controls">
												<button type="submit" class="btn btn-primary">Create tables</button>
											</div>
										</div>
	
	
									</form>
								</div>
							</div>
						
						<?php } ?>
						
					</div>
				</div>
			</div>
			
		<?php } // if($tableCreated) { ?>
		
<?php include('../admin/footer.php') ?>