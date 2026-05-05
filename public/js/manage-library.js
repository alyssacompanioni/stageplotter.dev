/**
 * manage-library.js
 * Handles the SVG drop-zone upload, inline label/filename editing, sortable tables,
 * and search filtering on the manage-library page.
 * Depends on utils.js (initSortableTable, initFlashMessages).
 */

(function () {
	document.querySelectorAll("form[data-confirm]").forEach((form) => {
		form.addEventListener("submit", function (e) {
			if (!confirm(this.dataset.confirm)) e.preventDefault();
		});
	});

	const zone       = document.getElementById("drop-zone");
	const input      = document.getElementById("svg_file");
	const stagedEl   = document.getElementById("drop-zone-staged");
	const fileLabel  = document.getElementById("drop-zone-filename");
	const clearBtn   = document.getElementById("clear-file-btn");
	const errorEl    = document.getElementById("drop-zone-error");
	const prompt     = zone.querySelector(".drop-zone-prompt");
	const uploadBtn  = document.getElementById("upload-btn");
	const typeRadios = document.querySelectorAll('input[name="type"]');
	const subSelect  = document.getElementById("upload-subcategory");

	const subcategoryOptions = window.SUBCATEGORY_OPTIONS || {};

	// ── Subcategory select ────────────────────────────────────────────────────────

	typeRadios.forEach((radio) => {
		radio.addEventListener("change", (e) => {
			const opts = subcategoryOptions[e.target.value] || {};
			subSelect.innerHTML = Object.entries(opts)
				.map(([val, label]) => `<option value="${val}">${label}</option>`)
				.join("");
		});
	});

	// ── Drop-zone ─────────────────────────────────────────────────────────────────

	/**
	 * Stages the given file in the drop zone, attaching it to the hidden file input and showing the filename.
	 * @param {File} file
	 */
	function setFile(file) {
		if (!file) return;
		const dt = new DataTransfer();
		dt.items.add(file);
		input.files = dt.files;
		prompt.hidden = true;
		fileLabel.textContent = file.name;
		stagedEl.hidden = false;
		uploadBtn.disabled = false;
	}

	/**
	 * Clears the staged file from the drop zone, resetting it to its initial empty state.
	 */
	function clearFile() {
		input.value = "";
		fileLabel.textContent = "";
		stagedEl.hidden = true;
		errorEl.hidden = true;
		prompt.hidden = false;
		uploadBtn.disabled = true;
	}

	clearBtn.addEventListener("click", (e) => { e.stopPropagation(); clearFile(); });
	zone.addEventListener("click", () => input.click());
	zone.addEventListener("keydown", (e) => { if (e.key === "Enter" || e.key === " ") input.click(); });
	input.addEventListener("change", () => setFile(input.files[0]));
	zone.addEventListener("dragover", (e) => { e.preventDefault(); zone.classList.add("drop-zone--active"); });
	zone.addEventListener("dragleave", () => zone.classList.remove("drop-zone--active"));
	zone.addEventListener("drop", (e) => {
		e.preventDefault();
		zone.classList.remove("drop-zone--active");
		if (!stagedEl.hidden) { errorEl.hidden = false; return; }
		setFile(e.dataTransfer.files[0]);
	});

	errorEl.querySelector(".msg-close-btn")?.addEventListener("click", () => { errorEl.hidden = true; });

	// ── Sortable tables ───────────────────────────────────────────────────────────

	document.querySelectorAll(".library-table").forEach((table) => {
		initSortableTable(table.querySelector("tbody"), null, filterLibrary);
	});

	// ── Inline editing ────────────────────────────────────────────────────────────

	/**
	 * Builds and returns the DOM nodes for an inline text editor (input, suffix span, Save/Cancel buttons, wrapper).
	 * @param {string} initialValue - Pre-filled input value.
	 * @param {string|null} [suffix] - Static text after the input (e.g. '.svg'), or null.
	 * @returns {{ input: HTMLInputElement, saveBtn: HTMLButtonElement, cancelBtn: HTMLButtonElement, wrap: HTMLDivElement }}
	 */
	function createInlineEditorUI(initialValue, suffix = null) {
		const editorInput = document.createElement("input");
		editorInput.type = "text";
		editorInput.value = initialValue;
		editorInput.className = "library-label-input";

		const saveBtn = document.createElement("button");
		saveBtn.type = "button";
		saveBtn.textContent = "Save";
		saveBtn.className = "btn btn-save-label";

		const cancelBtn = document.createElement("button");
		cancelBtn.type = "button";
		cancelBtn.textContent = "×";
		cancelBtn.className = "btn-cancel-label";
		cancelBtn.setAttribute("aria-label", "Cancel");

		const wrap = document.createElement("div");
		wrap.className = "library-label-edit-wrap";

		if (suffix) {
			const suffixSpan = document.createElement("span");
			suffixSpan.textContent = suffix;
			suffixSpan.className = "library-rename-suffix";
			wrap.append(editorInput, suffixSpan, saveBtn, cancelBtn);
		} else {
			wrap.append(editorInput, saveBtn, cancelBtn);
		}

		return { input: editorInput, saveBtn, cancelBtn, wrap };
	}

	/**
	 * Submits an array of name/value pairs as a hidden-field POST form.
	 * @param {Array<[string, string]>} fields
	 */
	function submitFormPost(fields) {
		const form = document.createElement("form");
		form.method = "post";
		fields.forEach(([name, value]) => {
			const hidden = document.createElement("input");
			hidden.type = "hidden";
			hidden.name = name;
			hidden.value = value;
			form.appendChild(hidden);
		});
		document.body.appendChild(form);
		form.submit();
	}

	/**
	 * Opens an inline editor for one cell in the same row as btn.
	 * Guards against re-entering if the cell is already being edited.
	 * @param {HTMLElement} btn - The edit button that was clicked.
	 * @param {string} cellSel - Selector for the editable cell within the row.
	 * @param {string} spanSel - Selector for the text span to hide while editing.
	 * @param {string|null} suffix - Static suffix text after the input (e.g. '.svg'), or null.
	 * @param {function(cell: HTMLElement, value: string): Array<[string,string]>} buildFields - Returns the POST field pairs.
	 */
	function openInlineEdit(btn, cellSel, spanSel, suffix, buildFields) {
		const cell = btn.closest("tr").querySelector(cellSel);
		if (!cell || cell.classList.contains("editing")) return;
		cell.classList.add("editing");

		const span = cell.querySelector(spanSel);
		span.hidden = true;

		const initVal = suffix ? cell.dataset.filename.replace(/\.svg$/i, "") : span.textContent;
		const { input: editorInput, saveBtn, cancelBtn, wrap } = createInlineEditorUI(initVal, suffix);
		cell.appendChild(wrap);
		editorInput.focus();
		editorInput.select();

		const cancel = () => { span.hidden = false; wrap.remove(); cell.classList.remove("editing"); };
		const save   = () => submitFormPost(buildFields(cell, editorInput.value.trim()));

		cancelBtn.addEventListener("click", cancel);
		saveBtn.addEventListener("click", save);
		editorInput.addEventListener("keydown", (e) => {
			if (e.key === "Enter") save();
			if (e.key === "Escape") cancel();
		});
	}

	document.querySelectorAll(".btn-edit").forEach((btn) => {
		btn.addEventListener("click", () => {
			openInlineEdit(btn, ".library-label-cell", ".library-label-text", null, (cell, value) => [
				["edit_label",        "1"],
				["label_type",        cell.dataset.type],
				["label_subcategory", cell.dataset.subcategory],
				["label_filename",    cell.dataset.filename],
				["new_label",         value],
			]);
			openInlineEdit(btn, ".library-filename-cell", ".library-filename-text", ".svg", (cell, value) => [
				["rename_file",        "1"],
				["rename_type",        cell.dataset.type],
				["rename_subcategory", cell.dataset.subcategory],
				["old_filename",       cell.dataset.filename],
				["new_filename",       value],
			]);
		});
	});

	// ── Library search ────────────────────────────────────────────────────────────

	const searchInput = document.getElementById("library-search-input");
	const searchClear = document.getElementById("library-search-clear");

	if (searchInput) {
		searchInput.addEventListener("input", () => {
			searchClear.hidden = searchInput.value === "";
			filterLibrary();
		});
	}

	if (searchClear) {
		searchClear.addEventListener("click", () => {
			searchInput.value = "";
			searchClear.hidden = true;
			filterLibrary();
			searchInput.focus();
		});
	}

	/**
	 * Filters the library tables by the current search query, hiding non-matching rows and
	 * collapsing empty subcategory and section groups.
	 */
	function filterLibrary() {
		const query = searchInput.value.trim().toLowerCase();

		document.querySelectorAll(".library-subcategory").forEach((subcategory) => {
			let hasMatch = false;
			subcategory.querySelectorAll(".library-table tbody tr").forEach((row) => {
				const matches = query === "" || row.textContent.toLowerCase().includes(query);
				row.style.display = matches ? "" : "none";
				if (matches) hasMatch = true;
			});

			const emptyMsg = subcategory.querySelector(".library-empty");
			if (emptyMsg) hasMatch = query === "";

			subcategory.hidden = !hasMatch;
			if (query !== "" && hasMatch) subcategory.open = true;
		});

		document.querySelectorAll(".library-section").forEach((section) => {
			const hasMatch = Array.from(section.querySelectorAll(".library-subcategory")).some((sub) => !sub.hidden);
			section.hidden = !hasMatch;
			if (query !== "" && hasMatch) section.open = true;
		});
	}

	// ── Maintenance scroll restoration ───────────────────────────────────────────

	const cleanupSection = document.querySelector(".cleanup-section");

	if (cleanupSection && sessionStorage.getItem("scrollToMaintenance")) {
		sessionStorage.removeItem("scrollToMaintenance");
		cleanupSection.scrollIntoView();
	}

	document.querySelectorAll(".btn-update").forEach((btn) => {
		btn.closest("form")?.addEventListener("submit", () => {
			sessionStorage.setItem("scrollToMaintenance", "1");
		});
	});

	initFlashMessages();
})();
