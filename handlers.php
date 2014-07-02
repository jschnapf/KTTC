<?php

// load Drupal so we can use its functions
//define('DRUPAL_ROOT', $_SERVER['DOCUMENT_ROOT']);
define('DRUPAL_ROOT', getcwd());
//$base_url = 'http://'.$_SERVER['HTTP_HOST'].'/artsee';
require_once(DRUPAL_ROOT . '/includes/bootstrap.inc');
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

define('USERNAME_MIN_LENGTH', 4);
define('PASSWORD_MIN_LENGTH', 4);

function authenticate_user_session($sessionId) {
	$sessionQuery = 'SELECT * FROM {sessions} WHERE sid = :sid';
	$result = db_query($sessionQuery, array(':sid' => $sessionId));
	
	$session = array();
	foreach ($result as $record) {
		$session[] = $record->uid;
	}
	
	if (count($session) > 0) {
		return $session[0];
	}
	return false;
}

/**
 * Create a new user account
 * Can be called from mobile app and web portal
 * 
 * @param $username - Name for the new account consisting of standard characters and length
 * @param $password - Password for the new account consisting of required security characters and length
 * @param $email - Valid email address to be associated with the new account
 * @param $openId - Valid openid identifier with which to associate the account
 * 
 * @return - Username, password, and/or email address approved/denied, Account ID for subsequent client calls
 */
function account_creation_handler($username, $password, $email, $openId) {
	if ($openId !== '') {
		if (user_authenticate($username, $password)) {
			$account = user_load_by_name($username);
			
			$result = db_query("SELECT * FROM {authmap} WHERE module = 'openid' AND authname = :identity", array(':identity' => $openId));
			$user = array();
			foreach ($result as $record) {
				$user[] = $record->uid;
			}
			
			if (count($user) > 0) {
				if ($user[0] === $account->uid) {
					// attempting to associate an OpenID the account's already using
					return $account->uid;
				} else {
					// attempting to associate an OpenID that's already in use by someone else
					return 'That OpenID is already in use!';
				}
			}
			user_set_authmaps($account, array('authname_openid' => $openId));
		}
	}
	
	if (!db_query("SELECT COUNT(*) FROM {users} WHERE name = :name", array(':name' => $username))->fetchField()) {
		// No one has used this $username yet
		if (strlen($username) < USERNAME_MIN_LENGTH) {
			return 'Username is too short';
		} elseif (!ctype_alnum($username)) {
			return 'Username must only contain letters and numbers';
		} elseif (contains_profanity($username)) {
			return 'Username must not contain profanity';
		}
	} else {
		return 'That username has already been used';
	}
	
	if (strlen($password) < PASSWORD_MIN_LENGTH) {
		return 'Password is too short';
	} elseif (!valid_password($password)) {
		return 'Password must contain letters, numbers, and at least one of the following symbols: !@#$%^&*()-_=+.?';
	}
	if (!valid_email_address($email)) {
		// Invalidly formatted email address
		return 'That is not a valid email address';
	} elseif (db_query("SELECT COUNT(*) FROM {users} WHERE mail = :mail", array(':mail' => $email))->fetchField()) {
		// Someone already used that email address to create an account
		return 'That email address has already been used';
	}
		
	// Set up the user fields
	$fields = array(
		'name' => $username,
		'mail' => $email,
		'pass' => $password,
		'status' => 1,
		'init' => $email,
		'roles' => array(
			DRUPAL_AUTHENTICATED_RID => 'authenticated user',
		),
	);
	
	// The first parameter is left blank so a new user is created
	$account = user_save('', $fields);
	if (!$account) {
		return false;
	}
	
	if ($openId !== '') {
		user_set_authmaps($account, array('authname_openid' => $openId));
	}
	
	return $account->uid;
}

function contains_profanity($text) {
	return false;
}

function valid_password($password) {
	$containsAlphanumeric = false;
	
	$securityCharacters = '!@#$%^&*()-_=+.?';
	$containsSecurityCharacter = false;
	
	$illegalCharacters = '`~';
	$containsIllegalCharacter = false;
	
	$length = strlen($password);
	for ($i = 0; $i < $length; $i++) {
		if (ctype_alnum($password[$i])) {
			$containsAlphanumeric = true;
		} elseif (strpos($password[$i], $securityCharacters) !== false) {
			$containsSecurityCharacter = true;
		} elseif (strpos($password[$i], $illegalCharacters) !== false) {
			$containsIllegalCharacter = true;
		}
	}
	return true;
	//return ($containsAlphanumeric && $containsSecurityCharacter && !$containsIllegalCharacter);
}

/**
 * Log in with existing credentials
 * Can be called from mobile app and web portal
 * 
 * @param $username - Username associated with account
 * @param $password - Password associated with account
 * @param $email - Email address associated with account, only used if prompted to reset password
 * 
 * @return - Username and password approved/denied, prompt to reset password if necessary
 */
function login_handler($username, $password, $email = null) {
	if (user_authenticate($username, $password)) {
		$user_obj = user_load_by_name($username);
		db_query("DELETE FROM {sessions} WHERE uid = :uid", array(':uid' => $user_obj->uid));
		$form_state = array();
		$form_state['uid'] = $user_obj->uid;
		user_login_submit(array(), $form_state);
		
		return session_id();
	} else {
		return false;
	}
	/*
	if ($email != null) {
		// check if correct email for username
		// send reset password email
	} else {
		// authenticate user
		//incorrect password x times?
	}
	//*/
	
	// probably don't want to return which one was wrong, makes it easier for
	// attackers to enumerate a list of usernames without knowing passwords
	//return array(username approved/denied, password approved/denied) || prompt to reset password
}

function openid_login_handler($identity) {
	if (!$identity) {
		return false;
	}
	
	$result = db_query("SELECT * FROM {authmap} WHERE module = 'openid' AND authname = :identity", array(':identity' => $identity));
	$user = array();
	foreach ($result as $record) {
		$user[] = $record->uid;
	}
	if (count($user) > 0) {
		$user_obj = user_load($user[0]);
		db_query("DELETE FROM {sessions} WHERE uid = :uid", array(':uid' => $user_obj->uid));
		$form_state = array();
		$form_state['uid'] = $user_obj->uid;
		user_login_submit(array(), $form_state);
		
		return session_id();
	}
	return false;
}

function logout_handler($sessionId) {
	$uid = authenticate_user_session($sessionId);
	
	if (!$uid) {
		global $user;
		$uid = $user->uid;
		$oUser = $user;
	} else {
		$oUser = user_load($uid);
	}
	
	watchdog('user', 'Session closed for %name.', array('%name' => $oUser->name));
	
	module_invoke_all('user_logout', $oUser);
	
	// Destroy the current session, and reset $user to the anonymous user.
	session_destroy();
}

/**
 * Upload "creativity" badges awarded through game play
 * Can be called from mobile app and web portal
 * 
 * @param $badge_data - Array of new badge data
 */
function badge_update_handler($id, $fromWeb, $badge_data, $ensemble = 0) {
	global $user;
	
	if ($fromWeb) {
		if (!$user->uid) {
			return false; // no one is logged in
		}
		if ($id === $user->uid) {
			return false; // can't give yourself a badge
		}
		if (!$ensemble) {
			return false; // no ensemble to give badge to
		}
		$uid = $id;
		$awarder = user_load($user->uid);
	} else {
		$uid = authenticate_user_session($id);
	}
	
	if ($uid) {
		$oUser = user_load($uid);
		if (empty($oUser->field_badge_list)) {
			$userBadges = array();
		} else {
			$userBadges = $oUser->field_badge_list[LANGUAGE_NONE];
		}
		
		// Get the list of ids for badges this user has already earned
		$user_badge_ids = array();
		foreach ($userBadges as $badge) {
			$user_badge_ids[] = $badge['value'];
		}
		
		// Want to make sure we know if the badge ids we're adding are valid
		$result = db_query("SELECT field_badge_id_value FROM {field_data_field_badge_id}");
		$valid_badge_ids = array();
		foreach ($result as $record) {
			$valid_badge_ids[] = $record->field_badge_id_value;
		}
		
		// Loop through $badge_data and only add valid ids that the user doesn't have yet
		$addedBadge = false;
		$awardedBadge = false;
		foreach ($badge_data as $badge_id) {
			$validBadge = in_array($badge_id, $valid_badge_ids);
			$hasBadge = in_array($badge_id, $user_badge_ids);
			if ($validBadge && !$hasBadge) {
				$oUser->field_badge_list[LANGUAGE_NONE][] = array('value' => $badge_id);
				$addedBadge = true;
			}
			
			if ($fromWeb) {
				// store ensemble owner's id, ensemble id, and badge id together in a csv
				$newAward = '' . $uid . ',' . $ensemble . ',' . $badge_id;
				$alreadyAwarded = false;
				
				if (!empty($awarder->field_badges_given)) {
					foreach ($awarder->field_badges_given[LANGUAGE_NONE] as $award) {
						if ($award['value'] === $newAward) {
							$alreadyAwarded = true; // can't give someone the same badge twice
							break;
						}
					}
				}
				
				if (!$alreadyAwarded) {
					$awarder->field_badges_given[LANGUAGE_NONE][] = array('value' => $newAward);
					$awardedBadge = true;
				}
			}
		}
		
		// Save the user object
		if ($addedBadge) {
			user_save($oUser);
		}
		if ($fromWeb && $awardedBadge) {
			user_save($awarder);
		}
		
		return true;
	}
	return false;
}

/**
 * View an ensemble/account information
 * Can be called from web portal only
 * 
 * @param $profile - Node id of the profile
 * 
 * @return - Array of associated badge data, if any
 */
function badge_viewer_handler($profile) {
	global $user;
	
	if ($user->uid) {
		$userQuery = 'SELECT * from {users} WHERE uid = :uid';
		$userBadgeQuery = 'SELECT field_badge_list_value FROM {field_data_field_badge_list} WHERE entity_id = :uid';
		$badgeNidQuery = 'SELECT entity_id FROM {field_data_field_badge_id} ORDER BY entity_id ASC';
		
		$userBadges = array();
		if (db_query($userQuery, array(':uid' => $profile))->fetchField()) {
			$result = db_query($userBadgeQuery, array(':uid' => $profile));
			
			foreach ($result as $badge) {
				$userBadges[] = $badge->field_badge_list_value;
			}
		}
		
		$result = db_query($badgeNidQuery);
		$badgeNids = array();
		foreach ($result as $badge) {
			$badgeNids[] = $badge->entity_id;
		}
		
		$badgeNodes = array();
		for ($i = 0; $i < count($badgeNids); $i++) {
			$badge = node_load($badgeNids[$i]);
			if ($badge) {
				$badgeNodes[] = $badge;
			}
		}
		
		$len = count($badgeNodes);
		$badges = array('unlocked' => array(), 'locked' => array());
		for ($i = 0; $i < $len; $i++) {
			$newBadge['nid'] = $badgeNodes[$i]->nid;
			$newBadge['badge_id'] = $badgeNodes[$i]->field_badge_id[LANGUAGE_NONE][0]['value'];
			$newBadge['title'] = $badgeNodes[$i]->title;
			if (!empty($badgeNodes[$i]->field_badge_description)) {
				$newBadge['description'] = $badgeNodes[$i]->field_badge_description[LANGUAGE_NONE][0]['value'];
			} else {
				$newBadge['description'] = $badgeNodes[$i]->title;
			}
			$newBadge['image_url'] = file_create_url($badgeNodes[$i]->field_badge_image[LANGUAGE_NONE][0]['uri']);
			
			if (in_array($newBadge['badge_id'], $userBadges)) {
				$badges['unlocked'][] = $newBadge;
			} else {
				$badges['locked'][] = $newBadge;
			}
		}
		
		return $badges;
	}
	return array();
}

/**
 * Upload an ensemble
 * Can be called from mobile app only
 * 
 * @param $sessionId - ID of user's session, used as user identification
 * @param $image - Image data for ensemble
 * 
 * @return - Whether the data was uploaded successfully
 */
function ensemble_upload_handler($sessionId, $image) {
	// still need to figure out how to check image integrity
	
	$uid = authenticate_user_session($sessionId);
	
	if (!$uid) {
		return false;
	}
	
	if ($uid) {
		if (!is_array($image)) {
			return false;
		}
		if (!isset($image['tmp_name']) || !isset($image['type'])) {
			return false;
		}
		
		$node = new stdClass();
		$node->type = 'ensemble';
		node_object_prepare($node);
		
		$node->title    = 'Ensemble Created on ' . date('c');
		$node->language = LANGUAGE_NONE;
		
		$file_path = $image['tmp_name'];
		$file = (object) array(
			'uid' => $uid,
			'uri' => $file_path,
			'filemime' => file_get_mimetype($file_path),
			'status' => 1,
		);
		
		$filetype = explode('/', $image['type']);
		$filetype = $filetype[1];
		$filename = 'ensemble_image_' . $uid . '_' . time() . '.' . $filetype;
		
		$oUser = user_load($uid);
		
		// Save the file to the root of the files directory.
		// You can specify a subdirectory, for example, 'public://images'
		$file = file_copy($file, 'public://'.$filename, FILE_EXISTS_REPLACE); 
		$node->field_ensemble_image[LANGUAGE_NONE][0] = (array)$file;
		$node->uid = $uid;
		$node->name = $oUser->name;
		$node->field_upvotes_ids[LANGUAGE_NONE][] = array('value' => $uid);
		
		node_save($node);
		
		// if the save was successful, $node will now be a fully populated node object
		// check if $node has a nid, indicating success
		$node_id = $node->nid;
		if ($node_id) {
			return true;
		}
	}
	return false;
}

/**
 * View an ensemble/account information
 * Can be called from web portal only
 * 
 * @param $ensemble - Node id of the ensemble
 * 
 * @return - Array of associated ensemble data, or FALSE if there is none
 */
function ensemble_viewer_handler($ensemble) {
	global $user;
	
	if ($user->uid) {
		$oEnsemble = node_load($ensemble);
		if (!$oEnsemble) {
			return false;
		}
		if ($oEnsemble->type !== 'ensemble') {
			return false;
		}
		
		$ensemble_info = array();
		$ensemble_info['nid'] = $oEnsemble->nid;
		$ensemble_info['uid'] = $oEnsemble->uid;
		$ensemble_info['user'] = $oEnsemble->name;
		$ensemble_info['created'] = $oEnsemble->created;
		$ensemble_info['time_ago'] = relativeTime($oEnsemble->created);
		$ensemble_info['title'] = $oEnsemble->title;
		$ensemble_info['image_url'] = file_create_url($oEnsemble->field_ensemble_image[LANGUAGE_NONE][0]['uri']);
		if (!empty($oEnsemble->field_upvotes_ids)) {
			foreach ($oEnsemble->field_upvotes_ids[LANGUAGE_NONE] as $upvoteid) {
				$ensemble_info['upvotes'][] = $upvoteid['value'];
			}
		} else {
			$ensemble_info['upvotes'] = array();
		}
		
		return $ensemble_info;
	}
}

/**
 * View all ensembles, limited by user and/or pagination
 * Can be called from mobile app and web portal
 * 
 * @param $profile - ID of the user whose ensembles should be retrieved, or 0 for all users
 * @param $page - page within pagination to retrieve
 * @param $limit - number of ensembles per page
 * 
 * @return - Array of associated data for all ensembles, or FALSE if there are none
 */
function all_ensembles_viewer_handler($profile = 0, $page = 1, $limit = 10) {
	global $user;
	
	if ($user->uid) {
		
		$userEnsemblesIds = array();
		if ($profile > 0) {
			$ensembleQuery = 'SELECT nid FROM {node} WHERE type = :type AND uid = :uid';
			$ensembleQueryArray = array(':type' => 'ensemble', ':uid' => $profile);
			$result = db_query($ensembleQuery, $ensembleQueryArray);
			
			foreach ($result as $ensemble) {
				$userEnsemblesIds[] = $ensemble->nid;
			}
			if (count($userEnsemblesIds) === 0) {
				return array();
			}
		}
		// get all the upvoted ensembles' ids, ordered by number of upvotes
		$ensembleQuery = 'SELECT entity_id FROM (SELECT entity_id FROM {field_data_field_upvotes_ids}) as ids GROUP BY entity_id ORDER BY count(*) DESC';
		$ensembleQueryArray = array();
		$result = db_query($ensembleQuery, $ensembleQueryArray);
		
		$allEnsemblesIds = array();
		// store all the ids of the upvoted ensembles
		foreach ($result as $ensemble) {
			$allEnsemblesIds[] = $ensemble->entity_id;
		}
		
		if ($profile > 0) {
			$ensemblesIds = array_values(array_intersect($allEnsemblesIds, $userEnsemblesIds));
		} else {
			$ensemblesIds = $allEnsemblesIds;
		}
		
		$numEnsembles = count($ensemblesIds);
		$ensembles = array();
		for ($i = (($page - 1) * $limit); $i < ($page * $limit) && $i < $numEnsembles; $i++) {
			$ensemble = ensemble_viewer_handler($ensemblesIds[$i]);
			$ensemble['page'] = array_search($ensemble['nid'], $allEnsemblesIds) + 1;
			$ensembles[] = $ensemble;
		}
		return $ensembles;
	}
	return false;
}

function ensemble_count_handler() {
	global $user;
	
	if ($user->uid) {
		$ensembleCountQuery = 'SELECT COUNT(*) FROM {node} WHERE type = :type';
		
		$result = db_query($ensembleCountQuery, array(':type' => 'ensemble'));
		$count = $result->fetchAssoc();
		return $count['COUNT(*)'];
	}
	return false;
}

function upvote_handler($ensemble, $remove = false) {
	global $user;
	
	$uid = $user->uid;
	if ($uid === 0) {
		return false; // can't upvote anonymously
	}
	
	$oEnsemble = node_load($ensemble);
	if (!$oEnsemble) {
		return false;
	}
	if ($oEnsemble->type !== 'ensemble') {
		return false;
	}
	
	if (!empty($oEnsemble->field_upvotes_ids)) {
		$upvotes = $oEnsemble->field_upvotes_ids[LANGUAGE_NONE];
		$numUpvotes = count($upvotes);
		for ($i = 0; $i < $numUpvotes; $i++) {
			if ($upvotes[$i]['value'] === $uid) {
				if ($remove) {
					array_splice($oEnsemble->field_upvotes_ids[LANGUAGE_NONE], $i, 1);
					node_save($oEnsemble);
					return true;
				} else {
					return false; // supposed to be adding, but user already voted
				}
			}
		}
		if ($remove) {
			// supposed to remove the upvote, but couldn't find the user's id
			return false;
		}
	}
	
	$oEnsemble->field_upvotes_ids[LANGUAGE_NONE][] = array('value' => $uid);
	node_save($oEnsemble);
	return true;
}

/**
 * Upload an avatar
 * Can be called from mobile app only
 * 
 * @param $image - Image data for avatar
 * 
 * @return - Whether the data was uploaded successfully
 */
function avatar_upload_handler($sessionId, $image) {
	// still need to figure out how to check image integrity
	
	$uid = authenticate_user_session($sessionId);
	
	if (!$uid) {
		return false;
	}
	
	if ($uid) {
		if (!is_array($image)) {
			return false;
		}
		if (!isset($image['tmp_name']) || !isset($image['type'])) {
			return false;
		}
		
		$file_path = $image['tmp_name'];
		$file = (object) array(
			'uid' => $uid,
			'uri' => $file_path,
			'filemime' => file_get_mimetype($file_path),
			'status' => 1,
		);
		
		$filetype = explode('/', $image['type']);
		$filetype = $filetype[1];
		$filename = 'avatar_image_' . $uid . '.' . $filetype;
		
		$oUser = user_load($uid);
		$file = file_copy($file, 'public://' . $filename, FILE_EXISTS_REPLACE); 
		$oUser->picture = $file->fid;
		user_save($oUser);
		return true;
	}
	return false;
}

function gamestate_save_handler($sessionId, $gameState) {
	if (!$gameState) {
		return false;
	}
	
	$uid = authenticate_user_session($sessionId);
	
	if (!$uid) {
		return false;
	}
	
	if ($uid) {
		$oUser = user_load($uid);
		$oUser->field_game_state[LANGUAGE_NONE][0] = array('value' => $gameState);
		user_save($oUser);
		return true;
	}
	return false;
}

function gamestate_retrieval_handler($sessionId) {
	$uid = authenticate_user_session($sessionId);
	
	if (!$uid) {
		return false;
	}
	
	if ($uid) {
		$userQuery = 'SELECT * from {users} WHERE uid = :uid';
		$userGameStateQuery = 'SELECT field_game_state_value FROM {field_data_field_game_state} WHERE entity_id = :uid';
		
		if (db_query($userQuery, array(':uid' => $uid))->fetchField()) {
			$result = db_query($userGameStateQuery, array(':uid' => $uid));
			
			foreach ($result as $gameState) {
				return $gameState->field_game_state_value;
			}
		}
	}
	return false;
}

function relativeTime($time) {
	$SECOND = 1;
	$MINUTE = 60 * $SECOND;
	$HOUR = 60 * $MINUTE;
	$DAY = 24 * $HOUR;
	$MONTH = 30 * $DAY;
	
	$delta = time() - $time;
	
	if ($delta < 1 * $MINUTE) {
		return $delta == 1 ? "1 second ago" : $delta . " seconds ago";
	}
	if ($delta < 2 * $MINUTE) {
		return "1 minute ago";
	}
	if ($delta < 45 * $MINUTE) {
		return floor($delta / $MINUTE) . " minutes ago";
	}
	if ($delta < 90 * $MINUTE) {
		return "1 hour ago";
	}
	if ($delta < 24 * $HOUR) {
		return floor($delta / $HOUR) . " hours ago";
	}
	if ($delta < 48 * $HOUR) {
		return "yesterday";
	}
	if ($delta < 30 * $DAY) {
		return floor($delta / $DAY) . " days ago";
	}
	if ($delta < 12 * $MONTH) {
		$months = floor($delta / $DAY / 30);
		return $months <= 1 ? "1 month ago" : $months . " months ago";
	} else {
		$years = floor($delta / $DAY / 365);
		return $years <= 1 ? "1 year ago" : $years . " years ago";
	}
}