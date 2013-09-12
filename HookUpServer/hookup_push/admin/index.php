<?php
	
	include("../lib/startup.php");
	session_start();
	
	unset($_SESSION['logged_id']);
	
	$additionalFormClass = "";

	$username = "";
	$password = "";
	
	if(isset($_COOKIE['username'])){
		$username = $_COOKIE['username'];
	}

	if(isset($_COOKIE['password'])){
		$password = $_COOKIE['password'];
	}

	if(isset($_POST['username'])){
		$username = $_POST['username'];
	}

	if(isset($_POST['password'])){
		$password = $_POST['password'];
	}

	if(isset($_POST['username']) && isset($_POST['password'])){
		
		$adminUserName = trim($_POST['username']);
		$adminPassword = trim($_POST['password']);
		
		if(ADMIN_USERNAME == $adminUserName && 
			ADMIN_PASSWORD == $adminPassword){
			
			$_SESSION['logged_id'] = true;
			
			
			if(isset($_POST['remember'])){
				setcookie("username", $adminUserName, time()+60*60*24*365); // 365 days
				setcookie("password", $adminPassword, time()+60*60*24*365); // 365 days
			}
			header('Location: dashboard.php');
			
			die();
				
		}else{
			
			$additionalFormClass = "error";
			$errorMassage = "Invalid username or password";
			
		}
		
	}	
?><!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
	<!-- Apple devices fullscreen -->
	<meta name="apple-mobile-web-app-capable" content="yes" />
	<!-- Apple devices fullscreen -->
	<meta names="apple-mobile-web-app-status-bar-style" content="black-translucent" />
	
	<title>PUSHSrv - Login</title>

	<!-- Bootstrap -->
	<link rel="stylesheet" href="css/bootstrap.min.css">
	<!-- Bootstrap responsive -->
	<link rel="stylesheet" href="css/bootstrap-responsive.min.css">
	<!-- icheck -->
	<link rel="stylesheet" href="css/plugins/icheck/all.css">
	<!-- Theme CSS -->
	<link rel="stylesheet" href="css/style.css">
	<!-- Color CSS -->
	<link rel="stylesheet" href="css/themes.css">


	<!-- jQuery -->
	<script src="js/jquery.min.js"></script>
	
	<!-- Nice Scroll -->
	<script src="js/plugins/nicescroll/jquery.nicescroll.min.js"></script>
	<!-- Validation -->
	<script src="js/plugins/validation/jquery.validate.min.js"></script>
	<script src="js/plugins/validation/additional-methods.min.js"></script>
	<!-- icheck -->
	<script src="js/plugins/icheck/jquery.icheck.min.js"></script>
	<!-- Bootstrap -->
	<script src="js/bootstrap.min.js"></script>
	<script src="js/eakroko.js"></script>

	<!--[if lte IE 9]>
		<script src="js/plugins/placeholder/jquery.placeholder.min.js"></script>
		<script>
			$(document).ready(function() {
				$('input, textarea').placeholder();
			});
		</script>
	<![endif]-->
	

	<!-- Favicon -->
	<link rel="shortcut icon" href="img/favicon.ico" />
	<!-- Apple devices Homescreen icon -->
	<link rel="apple-touch-icon-precomposed" href="img/apple-touch-icon-precomposed.png" />

</head>

<body class='login'>
	<div class="wrapper">
		<h1><a href="index.php"><img src="img/logo-big.png" alt="" class='retina-ready' width="59" height="49">PUSHSrv</a></h1>
		<div class="login-body">
			<h2>SIGN IN</h2>
			<form action="index.php" method='post' class='form-validate' id="test">
				<div class="control-group <?php echo $additionalFormClass ?>">
					<div class="controls">
						<input type="text" name='username' value="<?php echo $username ?>" placeholder="User name" class='input-block-level invalid' data-rule-required="true">
					</div>
				</div>
				<div class="control-group <?php echo $additionalFormClass ?>">
					<div class="pw controls">
						<input type="password" name="password" value="<?php echo $password ?>" placeholder="Password" class='input-block-level' data-rule-required="true">
						<?php if(isset($errorMassage)) { ?>
							<span for="password" class="help-block error" style=""><?php echo $errorMassage ?></span>
						<?php } ?>
					</div>
				</div>
				<div class="submit">
					<div class="remember">
						<input type="checkbox" name="remember" class='icheck-me' data-skin="square" data-color="blue" id="remember"> <label for="remember">Remember me</label>
					</div>
					<input type="submit" value="Sign me in" class='btn btn-primary'>
				</div>
			</form>
			<div class="forget">
				<a href="#"></a>
			</div>
		</div>
	</div>
</body>

</html>
