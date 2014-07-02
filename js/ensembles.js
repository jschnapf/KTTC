var	uid, username;
function upvote(ensembleId) {
	// don't do anything until a response comes back from the server
	if (upvote.waitingForResponse) {
		return false;
	}
	
	var button = document.getElementById('upvote-button-' + ensembleId),
		removing = (button.className === 'upvote-on');
	ajax({
		url: 'action_handler.php',
		data: { action: 'upvote_handler', ensemble: ensembleId, remove: removing },
		method: 'POST',
		success: function(data) {
			if (!data) {
				// undo the changes if something went wrong
				if (!removing) {
					button.className = 'upvote-off';
					button.title = 'Like this ensemble';
				} else {
					button.className = 'upvote-on';
					button.title = 'Undo like';
				}
			}
			button.style.cursor = 'pointer';
			upvote.waitingForResponse = false;
			return false;
		},
		error: function() {
			// undo the changes if something went wrong
			if (!removing) {
				button.className = 'upvote-off';
				button.title = 'Like this ensemble';
			} else {
				button.className = 'upvote-on';
				button.title = 'Undo like';
			}
			button.style.cursor = 'pointer';
			upvote.waitingForResponse = false;
			return false;
		}
	});
	
	// change the button immediately so the user isn't waiting
	if (removing) {
		button.className = 'upvote-off';
		button.title = 'Like this ensemble';
	} else {
		button.className = 'upvote-on';
		button.title = 'Undo like';
	}
	button.style.cursor = 'wait';
	upvote.waitingForResponse = true;
	
	return false;
}
upvote.waitingForResponse = false;

function awardBadge(badgeNumber, ensembleId, userId) {
	// don't do anything until a response comes back from the server
	if (awardBadge.waitingForResponse) {
		return false;
	}
	
	var badge = document.getElementById('badge-' + badgeNumber + '-' + ensembleId);
	ajax({
		url: 'action_handler.php',
		data: { action: 'badge_update_handler', uid: userId, badge_data: [ badgeNumber ], ensemble: ensembleId },
		method: 'POST',
		success: function(data) {
			if (!data) {
				// undo the changes if something went wrong
				badge.children[0].className = '';
			}
			badge.style.cursor = 'pointer';
			awardBadge.waitingForResponse = false;
			return false;
		},
		error: function() {
			// undo the changes if something went wrong
			badge.children[0].className = '';
			badge.style.cursor = 'pointer';
			awardBadge.waitingForResponse = false;
			return false;
		}
	});
	
	// change the button immediately so the user isn't waiting
	badge.children[0].className = 'awardedBadge';
	badge.style.cursor = 'wait';
	awardBadge.waitingForResponse = true;
	
	return false;
}
awardBadge.waitingForResponse = false;

function zoomImage(ensembleId) {
	var img = document.getElementById('ensemble-image-' + ensembleId);
	if (img.className === 'ensemble-image-thumbnail') {
		img.className = 'ensemble-image';
	} else {
		img.className = 'ensemble-image-thumbnail';
	}
	document.getElementById('ensemble-container-' + ensembleId).scrollIntoView();
	return false;
}