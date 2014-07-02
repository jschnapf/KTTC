<?php
//error_reporting(E_ALL);
ini_set('display_errors', 'off');
require_once('handlers.php');

global $user;

$page = 1;
if (isset($_GET['page'])) {
	$page = $_GET['page'];
}

$limit = 10;
if (isset($_GET['limit'])) {
	$limit = $_GET['limit'];
}

if (isset($_GET['ensemble_upvote'])) {
	if (isset($_GET['remove_upvote'])) {
		upvote_handler($_GET['ensemble_upvote'], $_GET['remove_upvote']);
	} else {
		upvote_handler($_GET['ensemble_upvote']);
	}
	if (isset($_GET['page']) && isset($_GET['limit'])) {
		header('Location: ensembles.php?page=' . $page . '&limit=' . $limit);
	} else {
		header('Location: ensembles.php');
	}
	exit;
}

if (isset($_GET['award_badge'])) {
	if (isset($_GET['badge']) && isset($_GET['ensemble']) && isset($_GET['user'])) {
		badge_update_handler($_GET['user'], true, array($_GET['badge']), $_GET['ensemble']);
	}
	if (isset($_GET['page']) && isset($_GET['limit'])) {
		header('Location: ensembles.php?page=' . $page . '&limit=' . $limit);
	} else {
		header('Location: ensembles.php');
	}
	exit;
}

$numEnsembles = ensemble_count_handler();
$pages = 1;
if ($numEnsembles > $limit) {
	$pages = intval($numEnsembles / $limit) + (($numEnsembles % $limit !== 0) ? 1 : 0);
}

$loggedIn = false;
if ($user->uid) {
	$loggedIn = true;
}

if ($page > $pages) {
	header('Location: ensembles.php?page=' . $pages . '&limit=' . $limit);
	exit;
}

$oUser = null;
if ($loggedIn) {
	$oUser = user_load($user->uid);
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
?><!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<link href="favicon.png" rel="icon" type="image/png" />
	<title>Ensembles - Keys to the Collection</title>
	<script src="js/ajax.js"></script>
	<script src="js/ensembles.js"></script>
	<link rel="stylesheet" type="text/css" href="css/styles.css">
<?php if ($loggedIn): ?>
	<script type="text/javascript">
	window.onload = function() {
		uid = <?php echo($user->uid); ?>;
		username = '<?php echo($user->name); ?>';
	};
	</script>
<?php endif; ?>
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
			<div class="active-tab">
				<a href=""><div>Ensembles</div></a>
			</div>
			<div class="inactive-tab">
				<a href="help.php"><div>Help</div></a>
			</div>
		</div>
		<a href="http://www.drexel.edu">
			<img id="drexel-logo" class="drexel-logo" alt="Drexel University" title="Drexel University" src="http://drexel.edu/~/media/Images/test/edTest/thinking-forward/ui/drexel-logo.jpg">
		</a>
	</div>
	
	<div id="container" class="container">
		<div id="logout-link" class="logout-link">
<?php if ($loggedIn): ?>
			Welcome, <?php echo($user->name); ?> - <a href="login.php?logout=1">Log out</a>
<?php else: ?>
			<a href="login.php">Log In</a>
<?php endif; ?>
		</div>
		
		<h3 class="ensembles-header">Ensembles:</h3>
<?php $ensembles = all_ensembles_viewer_handler(0, $page, $limit); ?>
<?php if (count($ensembles) === 0): // there are no ensembles ?>
		There are currently no ensembles to display.
<?php else: // there are ensembles ?>
		<div id="ensembles-table" class="ensembles-table">
<?php
	$count = 0;
	foreach ($ensembles as $ensemble):
		$ensembleId = $ensemble['nid'];
		$ensembleUserId = $ensemble['uid'];
?>
			<div id="ensemble-container-<?php echo($ensembleId); ?>" class="ensemble-container">
				<!--<span id="ensemble-title-<?php echo($ensembleId); ?>" class="ensemble-title"><?php echo($ensemble['title']); ?></span><br/>-->
				<span id="ensemble-user-<?php echo($ensembleId); ?>" class="ensemble-user">Created By <a href="user.php?user=<?php echo($ensembleUserId); ?>"><?php echo($ensemble['user']); ?></a></span>
				<br/>
				<a href="<?php echo($ensemble['image_url']); ?>" onclick="return zoomImage(<?php echo($ensembleId); ?>);">
					<img id="ensemble-image-<?php echo($ensembleId); ?>" class="ensemble-image-thumbnail" alt="Ensemble Image" src="<?php echo($ensemble['image_url']); ?>"></a>
				<br/>
				<span title="<?php echo(date("m/d/Y H:i:s", $ensemble['created'])); ?>"><?php echo($ensemble['time_ago']); ?></span>
				<br/>
<?php if ($loggedIn): ?>
				<div id="upvote-container-<?php echo($ensembleId); ?>" class="upvote-container">
<?php if ($user->uid === $ensemble['uid']): // ensemble belongs to current user ?>
					<span id="upvote-button-<?php echo($ensembleId); ?>" title="This is your ensemble" class="upvote-mine"></span>
<?php else: // ensemble doesn't belong to current user
	$upvoteUrl = 'ensembles.php?ensemble_upvote=' . $ensembleId;
	if (in_array($user->uid, $ensemble['upvotes'])) {
		$upvoteUrl .= '&remove_upvote=true';
	}
	$upvoteClass = (in_array($user->uid, $ensemble['upvotes']) ? 'upvote-on' : 'upvote-off');
	$upvoteTitle = (in_array($user->uid, $ensemble['upvotes']) ? 'Undo like' : 'Like this ensemble');
	
	$creativeGenius = 12;
	$hangItUp = 13;
	$loveIt = 14;
	$masterMaker = 15;
	
	$badgeOnclick1 = 'return awardBadge(' . $creativeGenius . ', ' . $ensembleId . ', ' . $ensembleUserId . ');';
	$badgeOnclick2 = 'return awardBadge(' . $hangItUp . ', ' . $ensembleId . ', ' . $ensembleUserId . ');';
	$badgeOnclick3 = 'return awardBadge(' . $loveIt . ', ' . $ensembleId . ', ' . $ensembleUserId . ');';
	$badgeOnclick4 = 'return awardBadge(' . $masterMaker . ', ' . $ensembleId . ', ' . $ensembleUserId . ');';
	
	$badgeUrl1 = 'ensembles.php?award_badge&badge=' . $creativeGenius . '&ensemble=' . $ensembleId . '&user=' . $ensembleUserId;
	$badgeUrl2 = 'ensembles.php?award_badge&badge=' . $hangItUp . '&ensemble=' . $ensembleId . '&user=' . $ensembleUserId;
	$badgeUrl3 = 'ensembles.php?award_badge&badge=' . $loveIt . '&ensemble=' . $ensembleId . '&user=' . $ensembleUserId;
	$badgeUrl4 = 'ensembles.php?award_badge&badge=' . $masterMaker . '&ensemble=' . $ensembleId . '&user=' . $ensembleUserId;
	
	
	if (isset($_GET['page']) && isset($_GET['limit'])) {
		$upvoteUrl .= '&page=' . $page . '&limit=' . $limit;
		$badgeUrl1 .= '&page=' . $page . '&limit=' . $limit;
		$badgeUrl2 .= '&page=' . $page . '&limit=' . $limit;
		$badgeUrl3 .= '&page=' . $page . '&limit=' . $limit;
		$badgeUrl4 .= '&page=' . $page . '&limit=' . $limit;
	}
	
	$badgeClass1 = '';
	$badgeClass2 = '';
	$badgeClass3 = '';
	$badgeClass4 = '';
	
	$award1 = '' . $ensembleUserId . ',' . $ensembleId . ',' . $creativeGenius;
	$award2 = '' . $ensembleUserId . ',' . $ensembleId . ',' . $hangItUp;
	$award3 = '' . $ensembleUserId . ',' . $ensembleId . ',' . $loveIt;
	$award4 = '' . $ensembleUserId . ',' . $ensembleId . ',' . $masterMaker;
	if (!empty($oUser->field_badges_given)) {
		foreach ($oUser->field_badges_given[LANGUAGE_NONE] as $award) {
			if ($award['value'] === $award1) {
				$badgeClass1 = 'awardedBadge';
				$badgeUrl1 = '';
				$badgeOnclick1 = 'return false;';
			} elseif ($award['value'] === $award2) {
				$badgeClass2 = 'awardedBadge';
				$badgeUrl2 = '';
				$badgeOnclick2 = 'return false;';
			} elseif ($award['value'] === $award3) {
				$badgeClass3 = 'awardedBadge';
				$badgeUrl3 = '';
				$badgeOnclick3 = 'return false;';
			} elseif ($award['value'] === $award4) {
				$badgeClass4 = 'awardedBadge';
				$badgeUrl4 = '';
				$badgeOnclick4 = 'return false;';
			}
		}
	}
?>
					<a id="badge-1-<?php echo($ensembleId); ?>" title="Award the Creative Genius Badge" href="<?php echo($badgeUrl1); ?>" onclick="<?php echo($badgeOnclick1); ?>" class="upvote-badge badge1">
						<img class="<?php echo($badgeClass1); ?>" src="https://keystothecollection.com/sites/default/files/creativeGenius_badge.png"></a>
					<a id="badge-2-<?php echo($ensembleId); ?>" title="Award the Hang It Up Badge" href="<?php echo($badgeUrl2); ?>" onclick="<?php echo($badgeOnclick2); ?>" class="upvote-badge badge2">
						<img class="<?php echo($badgeClass2); ?>" src="https://keystothecollection.com/sites/default/files/hangitUp_badge.png"></a>
					<a id="upvote-button-<?php echo($ensembleId); ?>" title="<?php echo($upvoteTitle); ?>" href="<?php echo($upvoteUrl); ?>" onclick="return upvote(<?php echo($ensembleId); ?>)" class="<?php echo($upvoteClass); ?>"></a>
					<a id="badge-3-<?php echo($ensembleId); ?>" title="Award the Love It! Badge" href="<?php echo($badgeUrl3); ?>" onclick="<?php echo($badgeOnclick3); ?>" class="upvote-badge badge3">
						<img class="<?php echo($badgeClass3); ?>" src="https://keystothecollection.com/sites/default/files/loveIt_badge.png"></a>
					<a id="badge-4-<?php echo($ensembleId); ?>" title="Award the Master Maker Badge" href="<?php echo($badgeUrl4); ?>" onclick="<?php echo($badgeOnclick4); ?>" class="upvote-badge badge4">
						<img class="<?php echo($badgeClass4); ?>" src="https://keystothecollection.com/sites/default/files/masterMaker_badge.png"></a>
<?php endif; // ensemble belongs to current user ?>
				</div>
<?php endif; // logged in ?>
			</div> <!-- end container for ensemble <?php echo($ensembleId); ?> -->
			
<?php
		$count++;
	endforeach; // ensembles
?>
		</div>
<?php
endif; // there no are ensembles

if ($pages > 1): // multiple pages
?>
	
	<div id="pagination-container" class="pagination-container">
<?php if ($page == 1): // first page ?>
		<div id="page-first" class="pagination-jump">
			&lt;&lt; First
		</div>
		<div id="page-previous" class="pagination-jump">
			&lt; Previous
		</div>
<?php else: // not first page ?>
		<div id="page-first" class="pagination-jump">
			<a href="ensembles.php?page=1&limit=<?php echo($limit); ?>" class="pagination-link">&lt;&lt; First</a>
		</div>
		<div id="page-previous" class="pagination-jump">
			<a href="ensembles.php?page=<?php echo($page - 1); ?>&limit=<?php echo($limit); ?>" class="pagination-link">&lt; Previous</a>
		</div>
<?php
		endif; // first page
		
		$firstEllipses = false;
		$secondEllipses = false;
		for ($i = 1; $i <= $pages; $i++): // pages
			if ($pages > 7): // more than 7 pages
				// always display the first 2 pages
				if ($i > 2 && $i < ($page - 1)):
					if (!$firstEllipses): // not first ellipses
?>
			<div id="ellipses-before-page" class="pagination-skip">
				...
			</div>
<?php
						$firstEllipses = true;
					endif; // not first ellipses
					continue;
					
				// always display the last 2 pages
				elseif ($i > ($page + 1) && $i < ($pages - 1)): // $i > 2 && $i < ($page - 1)
					if (!$secondEllipses): // not second ellipses
?>
			<div id="ellipses-after-page" class="pagination-skip">
				...
			</div>
<?php
						$secondEllipses = true;
					endif; // not second ellipses
					continue;
				endif; // $i > 2 && $i < ($page - 1)
			endif; // more than 7 pages
			
			if ($i == $page): // current page
?>
		<div id="page-<?php echo($i); ?>" class="pagination-page">
			<strong><?php echo($i); ?></strong>
		</div>
<?php else: // not current page ?>
		<div id="page-<?php echo($i); ?>" class="pagination-page">
			<a href="ensembles.php?page=<?php echo($i); ?>&limit=<?php echo($limit); ?>" class="pagination-link"><?php echo($i); ?></a>
		</div>
<?php
			endif; // current page
		endfor; // pages
		if ($page == $pages): // last page
?>
		<div id="page-next" class="pagination-jump">
			Next &gt;
		</div>
		<div id="page-last" class="pagination-jump">
			Last &gt;&gt;
		</div>
<?php else: // not last page ?>
		<div id="page-next" class="pagination-jump">
			<a href="ensembles.php?page=<?php echo($page + 1); ?>&limit=<?php echo($limit); ?>" class="pagination-link">Next &gt;</a>
		</div>
		<div id="page-last" class="pagination-jump">
			<a href="ensembles.php?page=<?php echo($pages); ?>&limit=<?php echo($limit); ?>" class="pagination-link">Last &gt;&gt;</a>
		</div>
<?php endif; // last page ?>
	</div>
<?php
endif; // multiple pages
?>
	</div>
</body>
</html>