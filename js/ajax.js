/**
 * ajax.js
 * Jon Hopkins
 * 12/12/2013
 * 
 * Modelled after jQuery's ajax method, this lightweight library is used for 
 * making AJAX requests to a server and is designed to work on any browser.
 * Note that any arrays being sent are serialized in the PHP format.
 * 
 * Adapted from code at http://net.tutsplus.com/articles/news/how-to-make-ajax-requests-with-raw-javascript/
 * 
 * Example usage:
 *	ajax({
 *		url: 'test.php',
 *		data: { username: username, password: password },
 *		// data : 'username=' + encodeURIComponent(username) + '&password=' + encodeURIComponent(password)
 *		method: 'POST',
 *		success: function(data) {
 *			alert(data);
 *		},
 *		error: function() {
 *			alert('Error');
 *		}
 *	});
 * 
 * @param {Object} args An object containing attributes describing the request.
 *                      An `url` attribute is required. If there is no `method`
 *                      attribute supplied, GET is assumed. `success` is used
 *                      to define what should happen when the request finishes.
 *                      Likewise, `error` defines what should happen if the 
 *                      request should fail.
 */
function ajax(args) {
	if (args.url === undefined || !args.url) {
		console.log('No URL provided to Ajax function');
		return;
	}
	
	// request settings
	var url = args.url,
		contentType = args.contentType !== undefined ? args.contentType : 'application/x-www-form-urlencoded',
		data = args.data || '',
		method = args.method || 'GET',
		processData = args.processData !== undefined ? args.processData : true,
	
	// state change callbacks
		uninitialized = args.uninitialized || function() { },
		loading = args.loading || function() { },
		loaded = args.loaded || function() { },
		interactive = args.interactive || function() { },
		complete = args.complete || function() { },
		success = args.success || function() { },
		error = args.error ||function() { },
	
	// the request, and various attempts to handle older versions of IE
		xhr = null, versions,
	
	// miscellaneous variables
		paramString, param, i, len
		
	// constants
		STATUS_OK = 200,
		
	// readyState definitions as found at https://developer.mozilla.org/en-US/docs/Web/API/XMLHttpRequest
		UNSENT = 0, // open()has not been called yet.
		OPENED = 1, // send()has not been called yet.
		HEADERS_RECEIVED = 2, // send() has been called, and headers and status are available.
		LOADING = 3, // Downloading; responseText holds partial data.
		DONE = 4; // The operation is complete.
	
	// convert the data object to a string if necessary
	if (processData) {
		if (typeof data === 'object') {
			paramString = '';
			for (param in data) {
				if (data.hasOwnProperty(param)) {
					if (data[param] instanceof Array) {
						data[param] = serialize(data[param]);
					}
					paramString = paramString + param + '=' + encodeURIComponent(data[param]) + '&';
				}
			}
			paramString = paramString.slice(0, -1); // remove the trailing '&'
			data = paramString;
		}
	}
	
	// make sure the request method is set properly
	if (method.toLowerCase() === 'get') {
		method = 'GET';
	} else if (method.toLowerCase() === 'post') {
		method = 'POST';
	} else {
		method = 'GET';
	}
	
	// attempt to create an XMLHttpRequest
	if (XMLHttpRequest !== undefined) {
		xhr = new XMLHttpRequest();
	
	// attempt all the old versions of ActiveXObject
	} else {
		versions = [
			'MSXML2.XmlHttp.5.0',
			'MSXML2.XmlHttp.4.0',
			'MSXML2.XmlHttp.3.0',
			'MSXML2.XmlHttp.2.0',
			'Microsoft.XmlHttp'
		];
		
		for (i = 0, len = versions.length; i < len; i = i + 1) {
			try {
				xhr = new ActiveXObject(versions[i]);
				break;
			} catch(e) { /* No need to do anything here. */}
		}
	}
	
	if (xhr === null) {
		console.log('The request object could not be created.');
		return;
	}
	
	// set the request's onReadyStateChange callbacks
	xhr.addEventListener('readystatechange', function() {
		if (xhr.readyState === UNSENT) {
			uninitialized();
		} else if (xhr.readyState === OPENED) {
			loading();
		} else if (xhr.readyState === HEADERS_RECEIVED) {
			loaded();
		} else if (xhr.readyState === LOADING) {
			interactive();
		} else if (xhr.readyState === DONE) {
			if (xhr.status !== STATUS_OK) {
				error();
			} else {
				success(xhr.responseText);
			}
			complete();
		}
	}, true);
	
	// make the request
	xhr.open(method, url, true);
	if (method === 'POST') {
		if (contentType) {
			xhr.setRequestHeader('Content-type', contentType);
		}
	}
	xhr.send(data);
}

/**
 * Adapted from the serialize function at http://phpjs.org/functions/serialize/
 */
function serialize(mixed_value) {
	var val, okey, key,
		count, vals, ktype = '',
	_utf8Size = function (str) {
		var size = 0,
			i = 0,
			l = str.length,
			code = '';
		
		for (i = 0; i < l; i += 1) {
			code = str.charCodeAt(i);
			if (code < 0x0080) {
				size += 1;
			} else if (code < 0x0800) {
				size += 2;
			} else {
				size += 3;
			}
		}
		
		return size;
	},
	_getType = function (input) {
		var match, k, constructor,
			types, type = typeof input;
		
		if (type === 'object' && !input) {
			return 'null';
		}
		if (type === 'object') {
			if (!input.constructor) {
				return 'object';
			}
			constructor = input.constructor.toString();
			match = constructor.match(/(\w+)\(/);
			if (match) {
				constructor = match[1].toLowerCase();
			}
			types = ['boolean', 'number', 'string', 'array'];
			for (k in types) {
				if (types.hasOwnProperty(k)) {
					if (constructor === types[k]) {
						type = types[k];
						break;
					}
				}
			}
		}
		return type;
	},
	type = _getType(mixed_value);	
	
	switch (type) {
		case 'function': 
			val = ''; 
			break;
		case 'boolean':
			val = 'b:' + (mixed_value ? '1' : '0');
			break;
		case 'number':
			val = (Math.round(mixed_value) === mixed_value ? 'i' : 'd') + ':' + mixed_value;
			break;
		case 'string':
			val = 's:' + _utf8Size(mixed_value) + ':"' + mixed_value + '"';
			break;
		case 'array':
		case 'object':
			val = 'a';
			count = 0;
			vals = '';
			for (key in mixed_value) {
				if (mixed_value.hasOwnProperty(key)) {
					ktype = _getType(mixed_value[key]);
					if (ktype !== 'function') {
						okey = (key.match(/^[0-9]+$/) ? parseInt(key, 10) : key);
						vals += serialize(okey) + this.serialize(mixed_value[key]);
						count += 1;
					}
				}
			}
			val += ':' + count + ':{' + vals + '}';
			break;
		case 'undefined':
			val = 'N';
			break;
		default:
			val = 'N';
			break;
	}
	if (type !== 'object' && type !== 'array') {
		val += ';';
	}
	return val;
}