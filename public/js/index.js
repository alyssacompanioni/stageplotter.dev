/**
 * index.js
 * Fetches and renders the public plots table on the index page, with client-side search and sort.
 */

(function () {
	const PAGE_SIZE = 20;
	let allPlots = [];
	let sortCol = "title";
	let sortDir = "asc";

	// ── Fetch ────────────────────────────────────────────────
	fetch("/api/get-public-plots.php")
		.then((r) => r.json())
		.then((data) => {
			allPlots = data.plots || [];
			updateSortIndicators();
			render();
		})
		.catch(() => {
			document.getElementById("plots-tbody").innerHTML = '<tr><td colspan="4">Failed to load plots.</td></tr>';
		});

	// ── Search ───────────────────────────────────────────────
	document.getElementById("plot-search").addEventListener("input", render);

	// ── Sort headers ─────────────────────────────────────────
	document.querySelectorAll("thead th").forEach((th) => {
		th.addEventListener("click", () => {
			const col = th.dataset.col;
			if (sortCol === col) {
				sortDir = sortDir === "asc" ? "desc" : "asc";
			} else {
				sortCol = col;
				sortDir = "asc";
			}
			updateSortIndicators();
			render();
		});
	});

	/**
	 * Refreshes the sort arrow icon on each column header to reflect the current sort column and direction.
	 */
	function updateSortIndicators() {
		document.querySelectorAll("thead th").forEach((th) => {
			th.removeAttribute("data-sort");
			const existing = th.querySelector(".sort-icon");
			if (existing) existing.remove();
			if (th.dataset.col === sortCol) {
				th.setAttribute("data-sort", sortDir);
				const img = document.createElement("img");
				img.src = sortDir === "asc" ? "/assets/icons/down-arrow.svg" : "/assets/icons/up-arrow.svg";
				img.alt = sortDir === "asc" ? "ascending" : "descending";
				img.className = "sort-icon";
				th.appendChild(img);
			}
		});
	}

	// ── Render ───────────────────────────────────────────────
	/**
	 * Filters and sorts the loaded plots, then writes the result rows into the table body.
	 */
	function render() {
		const query = document.getElementById("plot-search").value.trim().toLowerCase();
		let results = allPlots;

		if (query) {
			results = results.filter(
				(p) =>
					(p.title || "").toLowerCase().includes(query) ||
					(p.gig_date || "").toLowerCase().includes(query) ||
					(p.venue || "").toLowerCase().includes(query) ||
					(p.created_by || "").toLowerCase().includes(query),
			);
		}

		if (sortCol) {
			results = [...results].sort((a, b) => {
				let av, bv;
				if (sortCol === "gig_date") {
					av = new Date(a[sortCol] || 0).getTime();
					bv = new Date(b[sortCol] || 0).getTime();
					return sortDir === "asc" ? av - bv : bv - av;
				}
				av = (a[sortCol] || "").toLowerCase();
				bv = (b[sortCol] || "").toLowerCase();
				if (av < bv) return sortDir === "asc" ? -1 : 1;
				if (av > bv) return sortDir === "asc" ? 1 : -1;
				return 0;
			});
		}

		const slice = results.slice(0, PAGE_SIZE);
		const tbody = document.getElementById("plots-tbody");

		if (slice.length === 0) {
			tbody.innerHTML = '<tr><td colspan="4">No plots found.</td></tr>';
			return;
		}

		tbody.innerHTML = slice
			.map((p) => {
				const title = p.token
					? `<a href="/view-plot.php?token=${encodeURIComponent(p.token)}">${escHtml(p.title)}</a>`
					: escHtml(p.title);
				return `<tr>
        <td>${title}</td>
        <td>${escHtml(p.gig_date)}</td>
        <td>${escHtml(p.venue)}</td>
        <td>${escHtml(p.created_by)}</td>
      </tr>`;
			})
			.join("");
	}

	/**
	 * Returns an HTML-escaped version of a string, safe for injection into innerHTML.
	 * @param {string} str
	 * @returns {string}
	 */
	function escHtml(str) {
		return (str || "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
	}
})();
