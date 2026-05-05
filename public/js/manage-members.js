/**
 * manage-members.js
 * Handles sortable columns and live search filtering on the manage-members page.
 * Depends on utils.js (initSortableTable, initFlashMessages).
 */

(function () {
	initSortableTable(document.querySelector("tbody"), document.getElementById("member-search"));
	initFlashMessages();

	document.querySelectorAll(".status-toggle").forEach((toggle) => {
		toggle.addEventListener("change", () => toggle.form.submit());
	});
})();
