/**
 * manage-users.js
 * Handles sortable columns and live search filtering on the manage-users page.
 * Depends on utils.js (initSortableTable, initFlashMessages).
 */

(function () {
	initSortableTable(document.querySelector("tbody"), document.getElementById("user-search"));
	initFlashMessages();

	document.querySelectorAll(".status-toggle").forEach((toggle) => {
		toggle.addEventListener("change", () => toggle.form.submit());
	});
})();
