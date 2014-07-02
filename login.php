<?php
//error_reporting(E_ALL);
ini_set('display_errors', 'off');

$signup = false;
$titlePrefix = 'Login';
$msg = '';
$redirect = '';

if (isset($_GET)) {
	if (isset($_GET['redirect'])) {
		$redirect = $_GET['redirect'];
	}
	
	/*
	 * Logout logic
	 */
	if (isset($_GET['logout'])) {
		if (intval($_GET['logout']) === 1) {
			require_once('handlers.php');
			
			/*
			 * trying to logout an anonymous user
			 * just redirect to login.php
			 */
			global $user;
			if (!$user->uid) {
				header('Location: login.php');
				exit;
			}
			
			/*
			 * Logged in through OpenID
			 * Get which provider they are associated with and display
			 * a custom reminder to log out of the provider as well
			 */
			if (isset($_SESSION['login_type']) && $_SESSION['login_type'] === 'openid') {
				global $user;
				$result = db_query("SELECT * FROM {authmap} WHERE module = 'openid' AND uid = :uid", array(':uid' => $user->uid));
				$userid = array();
				foreach ($result as $record) {
					$userid[] = $record->authname;
				}
				if (count($userid) == 0) {
					header('Location: login.php');
					exit;
				}
				$result = $userid[0];
				
				$googleLogin = 'https://www.google.com/accounts/o8/id';
				$yahooLogin = 'https://open.login.yahooapis.com/openid';
				$aolLogin = 'http://openid.aol.com/';
				
				$loginAccount = '';
				if(strpos($result, $googleLogin) !== false) {
					$loginAccount = 'Google';
				} elseif (strpos($result, $yahooLogin) !== false) {
					$loginAccount = 'Yahoo';
				} elseif (strpos($result, $aolLogin) !== false) {
					$loginAccount = 'AOL';
				} else {
					$loginAccount = 'other websites too';
				}
				
				logout_handler();
				$msg = 'If you are on a shared or public computer, don\'t forget to log out of ' . $loginAccount.'.';
			
			/*
			 * Logged in using username and password
			 * Just log the user out
			 */
			} else {
				logout_handler();
				header('Location: ensembles.php');
				exit;
			}
		}
	}
	
	/*
	 * Signup logic
	 */
	if (isset($_POST['signup']) && $_POST['signup'] === 'signup') {
		$username = $_POST['username'];
		$password = $_POST['password'];
		$confirm = $_POST['confirm-password'];
		$email = $_POST['email'];
		$openid_identity = '';
		
		/*
		 * Signing up through OpenID
		 */
		if (isset($_POST['openid_identity'])) {
			$openid_identity = $_POST['openid_identity'];
		}
		
		if ($username === '' || $email === '' || $password === '') {
			$msg = 'Please fill out all fields before submitting.';
		} elseif ($password !== $confirm) {
			$msg = 'Passwords did not match.';
		} else {
			
			/*
			 * Validation passed
			 * Attempt to create an account for the user
			 */
			require_once('handlers.php');
			$uid = account_creation_handler($username, $password, $email, $openid_identity);
			if (is_numeric($uid)) {
				
				/*
				 * Created account through OpenID
				 * Log user in through OpenID
				 */
				if ($openid_identity !== '') {
					
					/*
					 * Attempt to log the user in through OpenID
					 */
					if (openid_login_handler($openid_identity)) {
						$_SESSION['login_type'] = 'openid';
						
						/*
						 * Created account in pop-up window
						 * Redirect the main browser and close the pop-up
						 */
						if (isset($_GET['verify_login'])) {
?>
<html>
<head></head>
<body onload="window.opener.location='<?php
	if ($redirect !== '') {
		echo($redirect);
	} else {
		echo('ensembles.php');
	} ?>';window.close();">
</body>
</html>
<?php
							exit;
						
						/*
						 * Created account without pop-up window
						 * Just redirect the page
						 */
						} else {
							if ($redirect !== '') {
								header('Location: ' . $redirect);
								exit;
							} else {
								header('Location: ensembles.php');
								exit;
							}
						}
					}
				
				/*
				 * Created account from sign-up link
				 */
				} else {
					
					/*
					 * Attempt to log the user in with username and password
					 */
					if (login_handler($username, $password)) {
						$_SESSION['login_type'] = 'password';
						
						/*
						 * Created account in popup window
						 * Redirect the main browser and close the browser
						 */
						if (isset($_GET['verify_login'])) {
?>
<html>
<head></head>
<body onload="window.opener.location='<?php
	if ($redirect !== '') {
		echo($redirect);
	} else {
		echo('ensembles.php');
	} ?>';window.close();">
</body>
</html>
<?php
							exit;
						
						/*
						 * Created account without the pop-up
						 * Just redirect the page
						 */
						} else {
							if ($redirect !== '') {
								header('Location: ' . $redirect);
								exit;
							} else {
								header('Location: ensembles.php');
								exit;
							}
						}
					}
				}
				
				/*
				 * Account was created but for some reason could not be logged in
				 */
				$msg = 'Your account could not be logged in. Please try again.';
				$signup = false;
				$titlePrefix = 'Login';
			
			/*
			 * Account creation failed
			 */
			} else {
				//$msg = 'Your account was not successfully registered. Please try again.';
				$msg = $uid;
				$signup = true;
				$titlePrefix = 'Sign-up';
			}
		}
	}
}

/*
 * Should display the sign-up form
 */
if (isset($_GET['signup']) || $signup) {
	$signup = true;
	
	if (isset($_GET['i'])) {
		$identity = $_GET['i'];
		if (isset($_GET['e'])) {
			$email = $_GET['e'];
		}
	}

/*
 * Login form was submitted
 */
} elseif (isset($_POST['username']) && isset($_POST['password'])) {
	
	/*
	 * Make sure the username and password aren't blank
	 */
	if ($_POST['username'] !== '' && $_POST['password'] !== '') {
		require_once('handlers.php');
		
		/*
		 * Attempt to log the user in
		 */
		if (login_handler($_POST['username'], $_POST['password'])) {
			
			/*
			 * Login was successful
			 * Redirect the page
			 */
			$_SESSION['login_type'] = 'password';
			if ($redirect !== '') {
				header('Location: ' . $redirect);
				exit;
			} else {
				header('Location: ensembles.php');
				exit;
			}
		
		/*
		 * Unsuccessful login attempt
		 * Set a message to inform the user
		 */
		} else {
			$msg = 'Invalid username or password.';
		}
	}

/*
 * User attempting to login through OpenID
 */
} else {
	require('lightopenid/openid.php');
	
	try {
		# Change 'localhost' to your domain name.
		$openid = new LightOpenID('https://' . $_SERVER['HTTP_HOST']);
		
		/*
		 * First step in the process
		 * Mode hasn't been set yet
		 */
		if (!$openid->mode) {
			$openIdIdentifier = '';
			$request = '';
			
			/*
			 * Get which OpenID provider should be used
			 */
			if (isset($_POST['openid_identifier'])) {
				$request = $_POST['openid_identifier'];
			} elseif (isset($_GET['openid_identifier'])) {
				$request = $_GET['openid_identifier'];
			}
			
			if ($request !== '') {
				switch ($request) {
					case 'Google':
						$openIdIdentifier = 'https://www.google.com/accounts/o8/id';
						break;
					case 'Yahoo':
						$openIdIdentifier = 'https://me.yahoo.com/';
						break;
					case 'AOL':
						$openIdIdentifier = 'https://www.aol.com/';
						break;
					default:
						$openIdIdentifier = $request;
						break;
				}
			}
			
			/*
			 * User selected a supported Provider
			 */
			if ($openIdIdentifier !== '') {
				
				/*
				 * Set up the OpenID object
				 */
				$openid->identity = $openIdIdentifier;
				# The following two lines request email, full name, and a nickname
				# from the provider. Remove them if you don't need that data.
				$openid->required = array('contact/email');
				$openid->optional = array('namePerson', 'namePerson/friendly');
				
				/*
				 * Redirect to the Provider's login page
				 */
				header('Location: ' . $openid->authUrl());
				exit;
			}
		
		/*
		 * User cancelled the login process
		 */
		} elseif ($openid->mode == 'cancel') {
			//$msg = 'Authentication cancelled.';
			
			/*
			 * User was attempting to log in through pop-up window
			 */
			if (isset($_GET['verify_login'])) {
?>
<html>
<head></head>
<body onload="window.close();">
</body>
</html>
<?php
				exit;
			
			/*
			 * User was attempting to log in without pop-up
			 */
			} else {
				if ($redirect !== '') {
					header('Location: login.php?redirect=' . urlencode($redirect));
					exit;
				} else {
					header('Location: login.php');
					exit;
				}
			}
		
		/*
		 * User has attempted to log into Provider
		 */
		} else {
			
			/*
			 * User successfully logged into Provider
			 */
			if ($openid->validate()) {
				
				$attributes = $openid->getAttributes();
				$identity = $openid->identity;
				
				/*
				 * Somehow they were authenticated but the identity was lost?
				 */
				if (!$identity) {
					
					/*
					 * User was attempting to log in through pop-up window
					 * Close the window
					 */
					if (isset($_GET['verify_login'])) {
?>
<html>
<head></head>
<body onload="window.close();">
</body>
</html>
<?php
						exit;
					
					/*
					 * User was attempting to log in without the pop-up
					 * Set a message informing them that the login failed
					 */
					} else {
						if ($redirect !== '') {
							header('Location: login.php?redirect=' . urlencode($redirect));
							exit;
						} else {
							header('Location: login.php');
							exit;
						}
					}
				}
				
				/*
				 * Attempt to log the user in
				 */
				require_once('./handlers.php');
				if (openid_login_handler($identity)) {
					$_SESSION['login_type'] = 'openid';
					
					/*
					 * User logged in through pop-up window
					 * Redirect main browser and close the pop-up
					 */
					if (isset($_GET['verify_login'])) {
?>
<html>
<head></head>
<body onload="window.opener.location='<?php
	if ($redirect !== '') {
		echo($redirect);
	} else {
		echo('ensembles.php');
	} ?>';window.close();">
</body>
</html>
<?php
						exit;
					
					/*
					 * User logged in without the pop-up
					 * Redirect the page
					 */
					} else {
						if ($redirect !== '') {
							header('Location: ' . $redirect);
							exit;
						} else {
							header('Location: ensembles.php');
							exit;
						}
					}
				
				/*
				 * OpenID authentication was successful, but an account for the identity does not exist
				 * Show the sign-up form
				 */
				} else {
					if (isset($_GET['verify_login'])) {
?>
<html>
<head></head>
<body onload="window.opener.location='login.php?signup&i=<?php
	echo(urlencode($identity));
	if (isset($attributes['contact/email']) && $attributes['contact/email'] !== '') {
		echo('&e=' . urlencode($attributes['contact/email']));
	}
	
	if ($redirect !== '') {
		echo('&redirect=' . $redirect);
	} ?>';window.close();">
</body>
</html>
<?php
						exit;
					} else {
						$signup = true;
						$titlePrefix = 'Sign-up';
					}
				}
			
			/*
			 * Login failed
			 */
			} else {
				$msg = 'User has not logged in.';
			}
		}
	} catch(ErrorException $e) {
		$msg = $e->getMessage();
	}
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
?><!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<link href="favicon.png" rel="icon" type="image/png" />
	<title><?php echo($titlePrefix); ?> - Keys to the Collection</title>
	<link rel="stylesheet" type="text/css" href="css/styles.css">
	<script src="js/ajax.js"></script>
	<script src="js/openid.js"></script>
</head>
<body>
	<a href="http://www.barnesfoundation.org">
		<img id="barnes-logo" class="barnes-logo" alt="Barnes Foundation" title="Barnes Foundation" src="http://www.barnesfoundation.org/assets/images/presentation/logo.gif">
	</a>
	
	<div id="navbar" class="navbar">
		<a href="http://www.drexel.edu">
			<img id="drexel-logo" class="drexel-logo" alt="Drexel University" title="Drexel University" src="http://drexel.edu/~/media/Images/test/edTest/thinking-forward/ui/drexel-logo.jpg">
		</a>
	</div>
	
	<div id="form-container" class="container form-container">
<?php if ($signup): // signing up ?>
		<h2 class="form-header">Please sign up to continue</h2>
<?php else: // not signing up ?>
		<h2 class="form-header">Please login to see your progress</h2>
<?php endif; // signing up ?>
<?php if ($msg !== ''): // message to display ?>
		<div id="invalid-login-msg" class="invalid-login-msg"><?php echo($msg); ?></div>
<?php endif; // message to display ?>
<?php if ($signup): // signing up ?>
		
		<form action="login.php" method="post" onsubmit="return validate();">
			<div id="username-container">
				<input class="form-input" type="text" name="username" id="username" required="required" placeholder="Username">
			</div>
			<div id="email-container">
				<input class="form-input" type="text" name="email" id="email" required="required" placeholder="Email Address" value="<?php
if (isset($attributes['contact/email']) && $attributes['contact/email'] !== '') {
	echo($attributes['contact/email']);
} else if (isset($email)) {
	echo($email);
} ?>">
			</div>
			<div id="password-container">
				<input class="form-input" type="password" name="password" id="password" required="required" placeholder="Password">
			</div>
			<div id="confirm-password-container">
				<input class="form-input" type="password" name="confirm-password" id="confirm-password" required="required" placeholder="Confirm Password">
			</div>
<?php if (isset($identity)): // using openid ?>
			<input type="hidden" name="openid_identity" id="openid_identity" value="<?php echo($identity); ?>">
<?php endif; // using openid ?>
			<input type="hidden" name="signup" value="signup">
			<input type="submit" name="submit" value="Submit">
		</form>
<?php if (isset($identity)): //using openid ?>
		Note: If you already have an account and want to use this OpenID for it, just enter the username and password and press Submit.
<?php endif; // using openid ?>
<?php else: // not signing up ?>
		<h3>Sign in with an OpenID Provider...</h3>
		<!-- form for button login for predefined list of openid providers -->
		<form action="login.php" method="post">
			<input type="submit" title="Google Login" name="openid_identifier" class="login google-login" value="Google" onclick="return openGoogleWindow();">
			<input type="submit" title="Yahoo Login" name="openid_identifier" class="login yahoo-login" value="Yahoo" onclick="return openYahooWindow();">
			<input type="submit" title="AOL Login" name="openid_identifier" class="login aol-login" value="AOL" onclick="return openAOLWindow();">
		</form>
		<br/>
		
		<!-- form for manually typing in url of openid provider -->
		<form action="login.php" method="post" onsubmit="return openOtherWindow();">
			<div>
				<input type="text" id="other-op" title="Other OpenID Provider" name="openid_identifier" class="form-input" required="required" placeholder="Other Provider">
				<input type="submit" value="Submit">
			</div>
		</form>
		
		<h3>... or sign in with your username and password</h3>
		<!-- form for username and password login -->
		<form action="login.php" method="post" onsubmit="return validate();">
			<div id="username-container">
				<input class="form-input" type="text" title="Username" name="username" id="username" required="required" placeholder="Username">
			</div>
			<div id="password-container">
				<input class="form-input" type="password" title="Password" name="password" id="password" required="required" placeholder="Password">
			</div>
			<input type="submit" title="Submit" name="submit" value="Submit">
		</form>
		
		Don't have an account? <a href="login.php?signup">Sign up here</a>
<?php endif; // signing up ?>
	</div>
<script type="text/javascript">
function validate() {
<?php if ($signup): // signing up ?>
	var username = document.getElementById('username').value;
	var email = document.getElementById('email').value;
	var password = document.getElementById('password').value;
	var confirm = document.getElementById('confirm-password').value;
	
	if (username === '' || email === '' || password === '') {
		return false;
	}
<?php if (isset($identity)): // using openid ?>
	
	var openid = document.getElementById('openid_identity').value;
	if (openid === '') {
		return false;
	}
<?php endif; // using openid ?>	
	if (password !== confirm) {
		return false;
	}
	return true;
<?php else: // not signing up ?>
	var username = document.getElementById('username').value;
	var password = document.getElementById('password').value;
	
	if (username === '' || password === '') {
		return false;
	}
	return true;
<?php endif; // signing up ?>
}
</script>
</body>
</html>