<?php
//error_reporting(E_ALL);
ini_set('display_errors', 'off');
require_once('handlers.php');

global $user;

$uid = $user->uid;
if (isset($_GET['user']) && !empty($_GET['user'])) {
	$uid = $_GET['user'];
}

if (!$user->uid) {
	if ($uid != 0) {
		header('Location: login.php?redirect=' . urlencode('user.php?user=' . $uid));
	} else {
		header('Location: login.php?redirect=' . urlencode('user.php'));
	}
	exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

$result = db_query('SELECT * FROM {users} WHERE uid = :uid', array(':uid' => $uid));
$profile = $result->fetchAssoc();
if ($profile):
?><!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<link href="favicon.png" rel="shortcut icon" type="image/x-icon" />
	<title><?php echo($profile['name']); ?>'s Profile - Keys to the Collection</title>
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
			<div class="active-tab">
				<a href="user.php"><div>Your progress</div></a>
			</div>
			<div class="inactive-tab">
				<a href="ensembles.php"><div>Ensembles</div></a>
			</div>
			<div class="inactive-tab">
				<a href="help.php"><div>Help</div></a>
			</div>
		</div>
        
    <!-- DREXEL LOGO AND LINK
		<a href="http://www.drexel.edu">
			<img id="drexel-logo" class="drexel-logo" alt="Drexel University" title="Drexel University" src="http://drexel.edu/~/media/Images/test/edTest/thinking-forward/ui/drexel-logo.jpg">
		</a>
     -->
        
	</div>
	
	<div id="container" class="container">
		<div id="logout-link" class="logout-link">
			Welcome, <?php echo($user->name);?> - <a href="login.php?logout=1">Log out</a>
			<div id="avatar-container" class="avatar-container">
<?php
if($profile['picture']): // user has avatar picture
	$picture = file_load($profile['picture']);
	$pictureURL = file_create_url($picture->uri);
?>
				<img id="user-avatar" class="user-avatar" alt="User Avatar" src="<?php echo($pictureURL); ?>">
<?php else: // user doesn't have avatar picture ?>
				<img id="user-avatar" class="user-avatar" alt="User Avatar" src="http://www.barnesfoundation.org/assets/public/images/content/default-user-kttc.png">
<?php endif; // user has avatar picture ?>
			</div>
			<div id="badge-checklist" class="badge-checklist">
<?php
$badges = badge_viewer_handler($uid);
$badgeList = array();
$unlockedBadges = 0;
foreach ($badges['unlocked'] as $badge) {
	$badge['unlocked'] = true;
	$badgeList[] = $badge;
	$unlockedBadges++;
}
foreach ($badges['locked'] as $badge) {
	$badge['unlocked'] = false;
	$badgeList[] = $badge;
}

$percentComplete = intval(($unlockedBadges * 100) / count($badgeList));

usort($badgeList, function ($badge1, $badge2) {
    return intval($badge1['badge_id']) - intval($badge2['badge_id']);
});
?>
<?php if ($uid === $user->uid): // current user ?>
				You are <?php echo($percentComplete); ?>% complete.
<?php else: // not current user ?>
				This user is <?php echo($percentComplete); ?>% complete.
<?php endif; // current user ?>
				<div class="meter">
					<span <?php echo($percentComplete > 95 ? 'class="filled" ' : ''); ?>style="width: <?php echo($percentComplete); ?>%"></span>
				</div>
				<ul>
<?php foreach ($badgeList as $badge): ?>
					<li>
						<span class="badge-check-<?php echo($badge['unlocked'] ? 'on' : 'off'); ?>"></span>
						<?php echo($badge['title']); ?> 
					</li>
<?php endforeach; //badge list ?>
				</ul>
			</div>
		</div>
		
		<h2 class="user-header"><?php echo($profile['name']); ?>'s Profile</h2>
		
		<h3 class="badges-header">Badges Earned</h3>
<?php if (count($badges['unlocked']) > 0): // user has unlocked badges ?>
		<div id="unlocked-badge-table" class="user-badge-table">
<?php
$count = 0;
foreach ($badges['unlocked'] as $badge):
?>
			<div id="unlocked-badge-cell-<?php echo($count); ?>" class="badge-cell">
				<span id="unlocked-badge-title-<?php echo($count); ?>" class="badge-title"><?php echo($badge['title']); ?></span><br/>
				<span id="unlocked-badge-description-<?php echo($count); ?>" class="badge-description"><?php echo($badge['description']); ?></span><br/>
				<img id="unlocked-badge-image-<?php echo($count); ?>" class="badge-image" alt="Badge Image" src="<?php echo($badge['image_url']); ?>">
			</div>
			
<?php
	$count++;
endforeach; //unlocked badges
?>
		</div>
<?php else: // user doesn't have unlocked badges ?>
<?php if ($uid === $user->uid): // current user ?>
		You have not unlocked any badges.
<?php else: // not current user ?>
		This user has not unlocked any badges.
<?php endif; // current user ?>
<?php endif; // user has unlocked badges ?>
		
		<h3 class="badges-header">Badges Left</h3>
<?php if (count($badges['locked']) > 0): // user has locked badges ?>
		<div id="locked-badge-table" class="user-badge-table">
<?php
$count = 0;
foreach ($badges['locked'] as $badge):
?>
			<div id="locked-badge-cell-<?php echo($count); ?>" class="badge-cell">
				<span id="locked-badge-title-<?php echo($count); ?>" class="badge-title"><?php echo($badge['title']); ?></span><br/>
				<span id="locked-badge-description-<?php echo($count); ?>" class="badge-description"><?php echo($badge['description']); ?></span><br/>
				<img id="locked-badge-image-<?php echo($count); ?>" class="badge-image" alt="Locked Badge Image" src="<?php echo($badge['image_url']); ?>">
			</div>
			
<?php
	$count++;
endforeach; // locked badges
?>
		</div>
<?php else: // user doesn't have locked badges ?>
<?php if ($uid === $user->uid): // current user ?>
		You have unlocked all the badges.
<?php else: // not current user ?>
		This user has unlocked all the badges.
<?php endif; // current user ?>
<?php endif; // user has locked badges ?>
		
		<h3 class="ensembles-user-header">Ensembles</h3>
<?php $ensembles = all_ensembles_viewer_handler($uid); ?>
<?php if (count($ensembles) > 0): // user has ensembles ?>
		<div id="ensembles-table" class="user-ensembles-table">
<?php
$count = 0;
foreach ($ensembles as $ensemble):
?>
			<div id="ensemble-cell-<?php echo($count); ?>" class="ensemble-cell">
				<!--<span id="ensemble-title-<?php echo($count); ?>" class="ensemble-title"><?php echo($ensemble['title']); ?></span><br/>-->
				<a href="ensembles.php?page=<?php echo($ensemble['page']); ?>&limit=1">
					<img id="ensemble-image-<?php echo($count); ?>" class="ensemble-image-thumbnail" alt="Ensemble Image" src="<?php echo($ensemble['image_url']); ?>"></a><br/>
				<span id="ensemble-created-<?php echo($count); ?>" class="ensemble-created" title="<?php echo(date("m/d/Y H:i:s", $ensemble['created'])); ?>"><?php echo($ensemble['time_ago']); ?></span>
			</div>
			
<?php
	$count++;
endforeach; // ensembles
?>
		</div>
<?php else: // user doesn't have ensembles ?>
<?php if ($uid === $user->uid): // current user ?>
		You have no ensembles to display.
<?php else: // not current user ?>
		This user has no ensembles to display.
<?php endif; // current user ?>
<?php endif; // user has ensembles ?>
	</div>
</body>
</html><?php else: // profile doesn't exist ?><!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<link href="favicon.png" rel="icon" type="image/png" />
	<title>Users - Keys to the Collection</title>
</head>
<body>
	The user you are searching for does not exist.
</body>
</html><?php endif; // profile exists ?>