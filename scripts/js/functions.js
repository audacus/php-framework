// checks if the given json string can be parsed to an object
function isValidJsonString(jsonString) {
	var valid = false;
	try {
		JSON.parse(jsonString);
		valid = true;
	} catch (e) {
		valid = false;
	}
	return valid;
}

// ajax helper
var Ajax = {
	// request
	request: function() {
		// ajax Methods
		var Methods = Object.freeze({
			GET: 'get',
			DELETE: 'delete',
			POST: 'post',
			PUT: 'put',
			PATCH: 'patch'
		});

		var args = (arguments[0] ? arguments[0] : {});
		// determine given parameters for the request or default parameters
		var parameters = {
			method: (args.method && Methods.hasOwnProperty(args.method.toUpperCase()) ? Methods[args.method.toUpperCase()] : Methods.GET),
			url: (args.url ? args.url : 'index'),
			async: (args.async ? args.async : true),
			beforeSend: (args.beforeSend ? args.beforeSend : function() {}),
			success: (args.success ? args.success : function() { console.log('success');console.log(arguments); }),
			failure: (args.failure ? args.failure : function() { console.log('failure');console.log(arguments); }),
			data: (args.data ? args.data : null),
			headers: (args.headers ? args.headers : {
				'X-Requested-With': 'XMLHttpRequest'
			})
		};

		// open request
		var request = new XMLHttpRequest();
		request.open(parameters.method, parameters.url , parameters.async);
		parameters.beforeSend();
		// request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

		// set headers
		var headers = parameters.headers;
		for (var key in headers) {
			if (headers.hasOwnProperty(key)) {
				console.log([key, headers[key]]);
				request.setRequestHeader(key, headers[key]);
			}
		}
		request.onreadystatechange = function() {
			if (request.readyState === 4) {
				var response = request.response;
				var success = true;
				if (isValidJsonString(response)) {
					var json = JSON.parse(response.trim());
					if ((json.errors && json.errors.length)
						|| (json.success != 'undefined' && json.success == false)) {
						success = false;
					}
				}
				if (request.status === 200 && success) {
					parameters.success(request, parameters);
				} else {
					parameters.failure(request, parameters);
				}
			}
		};

		// request send function
		this.send = function() {
			switch (parameters.method.toLowerCase()) {
				case Methods.POST:
				case Methods.PATCH:
				case Methods.PUT:
					if (!args.headers) {
						request.setRequestHeader('Content-Type', 'application/json');
						data = JSON.stringify(parameters.data);
					} else {
						data = parameters.data;
					}
					request.send(data);
					break;
				case Methods.GET:
				case Methods.DELETE:
				default:
					if (!args.headers) {
						request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
					}
					request.send();
					break;
			}
		};
	}
};

function showErrors(errors) {
	var errorElements = document.getElementsByClassName('errors');
	for (var i = 0; i < errorElements.length; i++) {
		var element = errorElements[i];
		element.innerHTML = '';
		errors.forEach(function(error) {
			element.innerHTML += error+'<br />';
		});
	}
}
function clearErrors() {
	var errorElements = document.getElementsByClassName('errors');
	for (var i = 0; i < errorElements.length; i++) {
		var element = errorElements[i];
		element.innerHTML = '';
	}
}
