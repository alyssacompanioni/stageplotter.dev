/**
 * header.js
 * Handles the dropdown menu in the header, allowing it to be closed when clicking outside of it.
 */

document.addEventListener("click", function (e) {
	if (e.target.closest(".back-btn")) {
		history.back();
		return;
	}

	const toggle = document.getElementById("dropdown-menu-toggle");
	if (!toggle) return;

	const userMenu = document.querySelector(".user-menu");
	if (!userMenu.contains(e.target)) {
		toggle.checked = false;
	}
});
