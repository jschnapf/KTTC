<?php
//error_reporting(E_ALL);
ini_set('display_errors', 'off');
require_once('handlers.php');

global $user;

$loggedIn = false;
if ($user->uid) {
	$loggedIn = true;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
?><!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<link href="favicon.png" rel="icon" type="image/png" />
	<title>Help - Keys to the Collection</title>
	<link rel="stylesheet" type="text/css" href="css/styles.css">
</head>
<body>
	<div id="header" class="header">
		<a href="http://www.barnesfoundation.org">
			<img id="barnes-logo" class="barnes-logo" alt="Barnes Foundation" title="Barnes Foundation" src="http://www.barnesfoundation.org/assets/images/presentation/logo.gif">
		</a>
	</div>
	<div id="navbar" class="navbar">
		<div id="tabs-container" class="tabs-container">
<?php if ($loggedIn): ?>
			<div class="inactive-tab">
				<a href="user.php"><div>Your progress</div></a>
			</div>
<?php endif; ?>
			<div class="inactive-tab">
				<a href="ensembles.php"><div>Ensembles</div></a>
			</div>
			<div class="active-tab">
				<a href=""><div>Help</div></a>
			</div>
		</div>
		<a href="http://www.drexel.edu">
			<img id="drexel-logo" class="drexel-logo" alt="Drexel University" title="Drexel University" src="http://drexel.edu/~/media/Images/test/edTest/thinking-forward/ui/drexel-logo.jpg">
		</a>
	</div>
	
	<div id="container" class="container">
		<div id="logout-link" class="logout-link">
<?php if ($loggedIn): ?>
			<?php echo($user->name);?> - <a href="login.php?logout=1">Log out</a>
<?php else: ?>
			<a href="login.php">Log In</a>
<?php endif; ?>
		</div>
		This is the help page
	</div>
</body>
</html>