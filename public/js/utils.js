/**
 * utils.js
 * Shared DOM utilities used across multiple pages.
 * Must be loaded before any page-specific script that calls these functions.
 */

/**
 * Wires up click-to-dismiss on all .flash-message .msg-close-btn elements on the page.
 */
function initFlashMessages() {
	document.querySelectorAll(".flash-message .msg-close-btn").forEach((btn) => {
		btn.addEventListener("click", () => {
			btn.closest(".flash-message").hidden = true;
		});
	});
}

/**
 * Attaches sortable-column and optional live-search behaviour to a table.
 *
 * Clicking a th[data-col] header sorts the tbody rows alphabetically by that
 * column, toggling direction on repeated clicks. A sort-direction arrow icon
 * is created on first sort and updated on subsequent sorts.
 *
 * @param {HTMLElement} tbody - The <tbody> element whose rows will be sorted.
 * @param {HTMLInputElement|null} [searchInput] - Live-search input that filters rows, or null.
 * @param {Function|null} [afterSort] - Optional callback invoked after each sort (e.g. to re-apply an external filter).
 */
function initSortableTable(tbody, searchInput = null, afterSort = null) {
	const table = tbody.closest("table");
	let allRows = Array.from(tbody.querySelectorAll("tr"));
	let sortColIdx = -1;
	let sortDir = "asc";

	table.querySelectorAll("thead th[data-col]").forEach((th) => {
		th.addEventListener("click", () => {
			const idx = th.cellIndex;
			if (sortColIdx === idx) {
				sortDir = sortDir === "asc" ? "desc" : "asc";
			} else {
				sortColIdx = idx;
				sortDir = "asc";
			}
			updateIndicators(th);
			sortRows();
			if (searchInput) filterRows();
			afterSort?.();
		});
	});

	/**
	 * Re-sorts allRows by sortColIdx / sortDir and re-inserts them into the tbody.
	 */
	function sortRows() {
		allRows.sort((a, b) => {
			const av = a.cells[sortColIdx].textContent.trim().toLowerCase();
			const bv = b.cells[sortColIdx].textContent.trim().toLowerCase();
			if (av < bv) return sortDir === "asc" ? -1 : 1;
			if (av > bv) return sortDir === "asc" ? 1 : -1;
			return 0;
		});
		allRows.forEach((row) => tbody.appendChild(row));
	}

	/**
	 * Hides rows that do not match the current search query.
	 */
	function filterRows() {
		const query = searchInput.value.trim().toLowerCase();
		allRows.forEach((row) => {
			row.style.display = query === "" || row.textContent.toLowerCase().includes(query) ? "" : "none";
		});
	}

	/**
	 * Removes any existing sort icon, then creates a new one on the active header.
	 * @param {HTMLElement} activeTh
	 */
	function updateIndicators(activeTh) {
		table.querySelectorAll("thead th[data-col]").forEach((th) => {
			th.removeAttribute("data-sort");
			th.querySelector(".sort-icon")?.remove();
		});
		activeTh.setAttribute("data-sort", sortDir);
		const img = document.createElement("img");
		img.src = sortDir === "asc" ? "/assets/icons/down-arrow.svg" : "/assets/icons/up-arrow.svg";
		img.alt = sortDir === "asc" ? "ascending" : "descending";
		img.className = "sort-icon";
		activeTh.appendChild(img);
	}

	if (searchInput) searchInput.addEventListener("input", filterRows);
}
