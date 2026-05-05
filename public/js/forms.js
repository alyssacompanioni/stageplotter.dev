/**
 * forms.js
 * Client-side validation for login.php, register.php, and profile.php.
 * Each section is guarded so only the forms present on the current page run.
 */

// ── Login Form ────────────────────────────────────────────────────────────────

if (document.getElementById("login-form")) {
	document.getElementById("login-form").addEventListener("submit", (e) => {
		if (!validateLoginFields()) e.preventDefault();
	});

	["username", "password"].forEach((id) => {
		document.getElementById(id).addEventListener("input", () => clearError(id));
	});
}

// ── Register Form ─────────────────────────────────────────────────────────────

if (document.getElementById("register-form")) {
	document.getElementById("register-form").addEventListener("submit", (e) => {
		const accountValid = validateAccountFields();
		const passwordValid = validateNewPassword("password", "confirm_password");
		if (!accountValid || !passwordValid) {
			e.preventDefault();
		}
	});

	["first_name", "last_name", "email", "phone", "username", "password", "confirm_password"].forEach((id) => {
		document.getElementById(id).addEventListener("input", () => clearError(id));
	});
}

// ── Profile Info Form ─────────────────────────────────────────────────────────

if (document.getElementById("profile-form")) {
	document.getElementById("profile-form").addEventListener("submit", (e) => {
		if (!validateAccountFields()) e.preventDefault();
	});

	["first_name", "last_name", "email", "phone", "username"].forEach((id) => {
		document.getElementById(id).addEventListener("input", () => clearError(id));
	});
}

// ── Change Password Form ──────────────────────────────────────────────────────

if (document.getElementById("password-form")) {
	document.getElementById("password-form").addEventListener("submit", (e) => {
		if (!validateChangePassword()) e.preventDefault();
	});

	["current_password", "new_password", "confirm_password"].forEach((id) => {
		document.getElementById(id).addEventListener("input", () => clearError(id));
	});
}

// ── Shared Validators ─────────────────────────────────────────────────────────

/**
 * Validates the login form (username + password both required).
 */
function validateLoginFields() {
	let valid = true;

	const username = document.getElementById("username").value.trim();
	const password = document.getElementById("password").value;

	if (!username) {
		setError("username", "Username cannot be blank.");
		valid = false;
	}

	if (!password) {
		setError("password", "Password cannot be blank.");
		valid = false;
	}

	return valid;
}

/**
 * Validates the five account fields shared by register.php and profile.php.
 * Returns true if all fields pass.
 */
function validateAccountFields() {
	let valid = true;

	const firstName = document.getElementById("first_name").value.trim();
	const lastName = document.getElementById("last_name").value.trim();
	const email = document.getElementById("email").value.trim();
	const phone = document.getElementById("phone").value.trim();
	const username = document.getElementById("username").value.trim();

	if (!firstName) {
		setError("first_name", "First name cannot be blank.");
		valid = false;
	} else if (firstName.length > 50) {
		setError("first_name", "First name cannot exceed 50 characters.");
		valid = false;
	}

	if (!lastName) {
		setError("last_name", "Last name cannot be blank.");
		valid = false;
	} else if (lastName.length > 50) {
		setError("last_name", "Last name cannot exceed 50 characters.");
		valid = false;
	}

	if (!email) {
		setError("email", "Email cannot be blank.");
		valid = false;
	} else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
		setError("email", "Please enter a valid email address.");
		valid = false;
	} else if (email.length > 100) {
		setError("email", "Email cannot exceed 100 characters.");
		valid = false;
	}

	if (phone && phone.length > 20) {
		setError("phone", "Phone number cannot exceed 20 characters.");
		valid = false;
	}

	if (!username) {
		setError("username", "Username cannot be blank.");
		valid = false;
	} else if (username.length > 20) {
		setError("username", "Username cannot exceed 20 characters.");
		valid = false;
	} else if (!/^[a-zA-Z0-9_]+$/.test(username)) {
		setError("username", "Username may only contain letters, numbers, and underscores.");
		valid = false;
	}

	return valid;
}

/**
 * Validates a new password + confirm pair (used on the register form).
 * @param {string} passwordId  - The id of the password field.
 * @param {string} confirmId   - The id of the confirm-password field.
 */
function validateNewPassword(passwordId, confirmId) {
	let valid = true;

	const password = document.getElementById(passwordId).value;
	const confirm = document.getElementById(confirmId).value;

	if (!password) {
		setError(passwordId, "Password cannot be blank.");
		valid = false;
	} else if (password.length < 8) {
		setError(passwordId, "Password must be at least 8 characters.");
		valid = false;
	}

	if (password !== confirm) {
		setError(confirmId, "Passwords do not match.");
		valid = false;
	}

	return valid;
}

/**
 * Validates the change-password form (current + new + confirm).
 */
function validateChangePassword() {
	let valid = true;

	const current = document.getElementById("current_password").value;
	const newPass = document.getElementById("new_password").value;
	const confirm = document.getElementById("confirm_password").value;

	if (!current) {
		setError("current_password", "Current password cannot be blank.");
		valid = false;
	}

	if (!newPass) {
		setError("new_password", "New password cannot be blank.");
		valid = false;
	} else if (newPass.length < 8) {
		setError("new_password", "New password must be at least 8 characters.");
		valid = false;
	}

	if (newPass !== confirm) {
		setError("confirm_password", "Passwords do not match.");
		valid = false;
	}

	return valid;
}

// ── DOM Helpers ───────────────────────────────────────────────────────────────

/**
 * Inserts (or updates) an inline error span immediately after the given input.
 */
function setError(fieldId, message) {
	const input = document.getElementById(fieldId);
	const errorId = fieldId + "-error";
	let span = document.getElementById(errorId);

	if (!span) {
		span = document.createElement("span");
		span.id = errorId;
		span.className = "field-error";
		input.insertAdjacentElement("afterend", span);
	}

	span.textContent = message;
	input.setAttribute("aria-invalid", "true");
	input.setAttribute("aria-describedby", errorId);
}

/**
 * Clears the inline error for the given input.
 */
function clearError(fieldId) {
	const input = document.getElementById(fieldId);
	const span = document.getElementById(fieldId + "-error");

	if (span) span.textContent = "";
	input.removeAttribute("aria-invalid");
	input.removeAttribute("aria-describedby");
}
