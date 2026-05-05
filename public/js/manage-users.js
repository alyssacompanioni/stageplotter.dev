/**
 * manage-users.js
 * Handles sortable columns and live search filtering on the manage-users page.
 */

(function () {
	const searchInput = document.getElementById("user-search");
	const tbody = document.querySelector("tbody");
	let allRows = Array.from(tbody.querySelectorAll("tr"));
	let sortColIdx = -1;
	let sortDir = "asc";

	searchInput.addEventListener("input", filterRows);

	document.querySelectorAll("thead th[data-col]").forEach(function (th) {
		const img = document.createElement("img");
		img.src = "/assets/icons/down-arrow.svg";
		img.alt = "";
		img.className = "sort-icon";
		img.style.visibility = "hidden";
		th.appendChild(img);

		th.addEventListener("click", function () {
			const idx = th.cellIndex;
			if (sortColIdx === idx) {
				sortDir = sortDir === "asc" ? "desc" : "asc";
			} else {
				sortColIdx = idx;
				sortDir = "asc";
			}
			updateSortIndicators(th);
			sortRows();
			filterRows();
		});
	});

	/**
	 * Re-sorts all user rows by the currently active column and direction, then re-inserts them into the tbody.
	 */
	function sortRows() {
		allRows.sort(function (a, b) {
			const av = a.cells[sortColIdx].textContent.trim().toLowerCase();
			const bv = b.cells[sortColIdx].textContent.trim().toLowerCase();
			if (av < bv) return sortDir === "asc" ? -1 : 1;
			if (av > bv) return sortDir === "asc" ? 1 : -1;
			return 0;
		});
		allRows.forEach(function (row) {
			tbody.appendChild(row);
		});
	}

	/**
	 * Hides user rows that do not match the current search query.
	 */
	function filterRows() {
		const query = searchInput.value.trim().toLowerCase();
		allRows.forEach(function (row) {
			const text = row.textContent.toLowerCase();
			row.style.display = query === "" || text.includes(query) ? "" : "none";
		});
	}

	/**
	 * Refreshes the sort arrow icon on each column header to reflect the active sort state.
	 * @param {HTMLElement} activeTh - The header cell that was just clicked.
	 */
	function updateSortIndicators(activeTh) {
		document.querySelectorAll("thead th[data-col]").forEach(function (th) {
			th.removeAttribute("data-sort");
			th.querySelector(".sort-icon").style.visibility = "hidden";
		});
		activeTh.setAttribute("data-sort", sortDir);
		const icon = activeTh.querySelector(".sort-icon");
		icon.src = sortDir === "asc" ? "/assets/icons/down-arrow.svg" : "/assets/icons/up-arrow.svg";
		icon.alt = sortDir === "asc" ? "ascending" : "descending";
		icon.style.visibility = "visible";
	}

	document.querySelectorAll(".flash-message .msg-close-btn").forEach((btn) => {
		btn.addEventListener("click", () => {
			btn.closest(".flash-message").hidden = true;
		});
	});
})();
