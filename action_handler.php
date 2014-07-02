<?php
//error_reporting(E_ALL);
ini_set('display_errors', 'off');

if (isset($_POST['action'])) {
	switch (strtolower($_POST['action'])) {
	case 'account_creation_handler':
		if (isset($_POST['username'])) {
			$username = $_POST['username'];
		} else {
			$username = '';
		}
		if (isset($_POST['password'])) {
			$password = $_POST['password'];
		} else {
			$password = '';
		}
		if (isset($_POST['email'])) {
			$email = $_POST['email'];
		} else {
			$email = '';
		}
		
		require_once('./handlers.php');
		echo account_creation_handler($username, $password, $email);
		exit;
	case 'login_handler':
		if (isset($_POST['username'])) {
			$username = $_POST['username'];
		} else {
			$username = '';
		}
		if (isset($_POST['password'])) {
			$password = $_POST['password'];
		} else {
			$password = '';
		}
		if (isset($_POST['email'])) {
			$email = $_POST['email'];
		} else {
			$email = '';
		}
		
		require_once('./handlers.php');
		echo login_handler($username, $password, $email);
		exit();
	case 'login_cookie_handler':
		if (isset($_POST['uid'])) {
			$uid = $_POST['uid'];
		} else {
			$uid = 0;
		}
		
		require_once('./handlers.php');
		echo login_cookie_handler($uid);
		exit();
	case 'badge_update_handler':
		if (isset($_POST['badge_data'])) {
			$badge_data = unserialize($_POST['badge_data']);
		} else {
			$badge_data = array();
		}
		if (isset($_POST['sessionId'])) {
			$id = $_POST['sessionId'];
			$fromWeb = false;
		} elseif (isset($_POST['uid'])) {
			$id = $_POST['uid'];
			$fromWeb = true;
		} else {
			echo false;
			exit;
		}
		
		require_once('./handlers.php');
		if (isset($_POST['ensemble'])) {
			echo badge_update_handler($id, $fromWeb, $badge_data, $_POST['ensemble']);
		} else {
			echo badge_update_handler($id, $fromWeb, $badge_data);
		}
		exit;
	case 'badge_viewer_handler':
		if (isset($_POST['profile'])) {
			$profile = $_POST['profile'];
		} else {
			$profile = null;
		}
		
		require_once('./handlers.php');
		echo json_encode(badge_viewer_handler($profile));
		exit;
	case 'ensemble_upload_handler':
		if (isset($_FILES['image'])) {
			$image = $_FILES['image'];
		} else {
			$image = null;
		}
		
		require_once('./handlers.php');
		
		$sessionId = '';
		if (isset($_POST['username']) && isset($_POST['password'])) {
			$sessionId = login_handler($_POST['username'], $_POST['password']);
		} else if (isset($_POST['sessionId'])) {
			$sessionId = $_POST['sessionId'];
		}
		
		echo ensemble_upload_handler($sessionId, $image);
		exit;
	case 'ensemble_viewer_handler':
		if (isset($_POST['ensemble'])) {
			$ensemble = $_POST['ensemble'];
		} else {
			$ensemble = '';
		}
		
		require_once('./handlers.php');
		echo json_encode(all_ensembles_viewer_handler());
		exit;
	case 'upvote_handler':
		if (isset($_POST['ensemble'])) {
			$ensemble = $_POST['ensemble'];
		} else {
			$ensemble = 0;
		}
		if (isset($_POST['remove'])) {
			$remove = $_POST['remove'] === 'true';
		} else {
			$remove = false;
		}
		
		require_once('./handlers.php');
		echo upvote_handler($ensemble, $remove);
		exit;
	case 'avatar_upload_handler':
		if (isset($_FILES['image'])) {
			$image = $_FILES['image'];
		} else {
			$image = null;
		}
		
		require_once('./handlers.php');
		echo avatar_upload_handler($image);
		exit;
	case 'gamestate_save_handler':
		if (isset($_POST['sessionId'])) {
			$sessionId = $_POST['sessionId'];
		} else {
			$sessionId = '';
		}
		if (isset($_POST['gamestate'])) {
			$gamestate = $_POST['gamestate'];
		} else {
			$gamestate = '';
		}
		
		require_once('./handlers.php');
		echo gamestate_save_handler($sessionId, $gamestate);
		exit;
	case 'gamestate_retrieval_handler':
		if (isset($_POST['sessionId'])) {
			$sessionId = $_POST['sessionId'];
		} else {
			$sessionId = '';
		}
		
		require_once('./handlers.php');
		echo gamestate_retrieval_handler($sessionId);
		exit;
	default:
		break;
	}
}
echo false;
