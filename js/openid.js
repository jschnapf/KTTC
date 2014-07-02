function getCenteredCoords(width, height) {
	var xPos = null;
	var yPos = null;
	if (window.ActiveXObject) {
		xPos = window.event.screenX - (width / 2) + 100;
		yPos = window.event.screenY - (height / 2) - 100;
	} else {
		var parentSize = [window.outerWidth, window.outerHeight];
		var parentPos = [window.screenX, window.screenY];
		xPos = parentPos[0] + Math.max(0, Math.floor((parentSize[0] - width) / 2));
		yPos = parentPos[1] + Math.max(0, Math.floor((parentSize[1] - (height * 1.25)) / 2));
	}
	return [xPos, yPos];
}

function openPopupWindow(openid) {
	var w = window.open('login.php?verify_login&openid_identifier=' + encodeURIComponent(openid), 'openid_popup', 'width=450,height=500,location=1,status=1,resizable=yes');
	
	var coords = getCenteredCoords(450, 500);
	w.moveTo(coords[0], coords[1]);
}

function openYahooWindow() {
	openPopupWindow('Yahoo');
	return false;
}

function openAOLWindow() {
	openPopupWindow('AOL');
	return false;
}

function openGoogleWindow() {
	openPopupWindow('Google');
	return false;
}

function openOtherWindow() {
	var openIdProvider = document.getElementById('other-op').value;
	if (openIdProvider !== '') {
		openPopupWindow(document.getElementById('other-op').value);
	}
	return false;
}

function handleOpenIDResponse(openid_args) {
	ajax({
		url: 'login.php?verify_login&' + openid_args,
		data: '',
		method: 'GET',
		success: function(data) {
			
		},
		error: function() {
			alert('Error');
		}
	});
}