addEventListener("DOMContentLoaded", function () {
	// Select the cloud provider dropdown
	const cloudProviderSelect = document.querySelector(
		'select[name="advmo_settings[cloud_provider]"]',
	);

	if (!cloudProviderSelect) {
		console.error("Cloud provider select not found");
		return;
	}

	// Select the form (assuming it's the parent form of the select field)
	const form = cloudProviderSelect.closest("form");

	if (!form) {
		console.error("Parent form not found");
		return;
	}

	// Add event listener to the select field
	cloudProviderSelect.addEventListener("change", function (e) {
		// Look for a submit button
		const submitButton = form.querySelector(
			'input[type="submit"], button[type="submit"]',
		);

		if (submitButton) {
			// If found, click the submit button
			submitButton.click();
		} else {
			// If no submit button found, dispatch a submit event
			const submitEvent = new Event("submit", {
				bubbles: true,
				cancelable: true,
			});
			form.dispatchEvent(submitEvent);
		}
	});
	
	// Initial status display
	function displayInitialStatus(isConnected, lastCheckTime) {
		const buttonContainer = document.querySelector('.advmo-test-connection-container');
		const lastCheckElement = document.createElement('p');
		lastCheckElement.className = `advmo-last-check ${isConnected ? 'connected' : 'disconnected'}`;
		lastCheckElement.textContent = `${isConnected ? 'Connected' : 'Disconnected'}. ${advmo_ajax_object.i18n.last_check} ${lastCheckTime}`;
		buttonContainer.insertBefore(lastCheckElement, buttonContainer.firstChild);
	}

	// Connection test functionality
	const advmo_test_connection = document.querySelector(".advmo_js_test_connection");

	function updateConnectionStatus(isConnected, lastCheckTime, message = '', isInitial = false) {
		const error_message = document.querySelector(".advmo-test-error");
		let lastCheckElement = document.querySelector('.advmo-last-check');

		// Remove any existing status messages
		const existingMessages = document.querySelectorAll('.advmo-last-check');
		existingMessages.forEach(msg => msg.remove());

		// Create new status message
		lastCheckElement = document.createElement('p');
		lastCheckElement.className = 'advmo-last-check';

		const buttonContainer = document.querySelector('.advmo-test-connection-container');
		buttonContainer.insertBefore(lastCheckElement, buttonContainer.firstChild);

		if (isInitial) {
			// Display initial status
			lastCheckElement.classList.add(isConnected ? 'connected' : 'disconnected');
			lastCheckElement.textContent = `${isConnected ? 'Connected' : 'Disconnected'}. ${advmo_ajax_object.i18n.last_check} ${lastCheckTime}`;
		} else {
			// Display test result message
			lastCheckElement.classList.add(isConnected ? 'connected' : 'disconnected');
			lastCheckElement.textContent = `${message} (${advmo_ajax_object.i18n.last_check} ${lastCheckTime})`;

			// Set timeout to change message to initial status format
			setTimeout(function () {
				lastCheckElement.classList.add("fade-out");
				setTimeout(function () {
					lastCheckElement.classList.remove("fade-out");
					lastCheckElement.textContent = `${isConnected ? 'Connected' : 'Disconnected'}. ${advmo_ajax_object.i18n.last_check} ${lastCheckTime}`;
				}, 300);
			}, 3000);
		}

		// Hide the error message element
		if (error_message) {
			error_message.style.display = 'none';
		}
	}

	// Display initial status if last check data is available
	if (advmo_ajax_object.initial_status) {
		displayInitialStatus(
			advmo_ajax_object.initial_status.is_connected,
			advmo_ajax_object.initial_status.last_check
		);
	}

	if (advmo_test_connection) {
		advmo_test_connection.addEventListener("click", function (e) {
			e.preventDefault();

			// Save the original text and disable the link
			const originalText = advmo_test_connection.textContent;
			advmo_test_connection.textContent = "Loading...";
			advmo_test_connection.disabled = true;

			const data = {
				action: "advmo_test_connection",
				security_nonce: advmo_ajax_object.nonce,
			};

			fetch(advmo_ajax_object.ajax_url, {
				method: "POST",
				headers: {
					"Content-Type": "application/x-www-form-urlencoded",
				},
				body: new URLSearchParams(data),
			})
				.then((response) => response.json())
				.then((data) => {
					advmo_test_connection.disabled = false;

					advmo_test_connection.textContent = advmo_ajax_object.i18n.recheck;

					let lastCheckTime = data.data.last_check;
					let message = data.data.message;
					updateConnectionStatus(data.success, lastCheckTime, message, false);
				})
				.catch((error) => {
					advmo_test_connection.disabled = false;
					advmo_test_connection.textContent = advmo_ajax_object.i18n.recheck;

					const lastCheckTime = new Date().toLocaleString();
					updateConnectionStatus(false, lastCheckTime, 'Connection failed!', false);
					console.error("Error:", error.message);
				});
		});
	}

	// Enable Path Prefix input if checkbox was enabled
	var pathPrefixCheckbox = document.getElementById("path_prefix_active");
	var pathPrefixInput = document.getElementById("path_prefix");

	if (pathPrefixCheckbox && pathPrefixInput) {
		pathPrefixCheckbox.addEventListener("change", function () {
			pathPrefixInput.disabled = !this.checked;
		});
	}
});
