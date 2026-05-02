// ─── Palette ───────────────────────────────────────────────────────────────────

const cardContainer = document.querySelector(".element-card-container");

// Event delegation — works for both the initial PHP-rendered cards and any
// cards injected dynamically when the user switches instrument category.
cardContainer.addEventListener("dragstart", (e) => {
  const card = e.target.closest(".element-card");
  if (!card) return;
  e.dataTransfer.setData(
    "text/plain",
    JSON.stringify({
      src: card.querySelector("img").src,
      label: card.querySelector("p").textContent,
    }),
  );
});

// Intercept category button clicks so switching categories fetches icons via
// AJAX instead of reloading the page (which would clear the canvas).
document.getElementById("instrument-subcategories").addEventListener("click", (e) => {
  const btn = e.target.closest("button[value]");
  if (!btn) return;
  e.preventDefault();
  switchPalette(btn.value, "instruments");
});

document.getElementById("equipment-subcategories").addEventListener("click", (e) => {
  const btn = e.target.closest("button[value]");
  if (!btn) return;
  e.preventDefault();
  switchPalette(btn.value, "equipment");
});

/**
 * Fetches icons for the given category and repopulates the card container.
 * @param {string} category - e.g. 'guitars', 'audio'
 * @param {string} type - 'instruments' or 'equipment'
 */
async function switchPalette(category, type = "instruments") {
  try {
    const url = `/api/get-palette.php?type=${encodeURIComponent(type)}&category=${encodeURIComponent(category)}`;
    const res = await fetch(url);
    const data = await res.json();
    if (!data.success) return;

    cardContainer.innerHTML = "";
    data.icons.forEach((icon) => {
      const card = document.createElement("div");
      card.className = "element-card";
      card.draggable = true;
      const img = document.createElement("img");
      img.src    = icon.src;
      img.alt    = icon.label + " Icon.";
      img.width  = 48;
      img.height = 48;
      const p = document.createElement("p");
      p.textContent = icon.label;
      card.appendChild(img);
      card.appendChild(p);
      cardContainer.appendChild(card);
    });
  } catch {
    // Silently fail — the existing cards remain visible
  }
}

const canvas = document.querySelector(".stage-plot-canvas");

canvas.addEventListener("dragstart", (e) => {
  if (e.target.closest(".placed-element")) e.preventDefault();
});

canvas.addEventListener("dragover", (e) => {
  e.preventDefault(); // Allows dropping
});

canvas.addEventListener("drop", (e) => {
  e.preventDefault(); // Prevents default browser behavior (e.g., opening the image)
  const data = JSON.parse(e.dataTransfer.getData("text/plain"));

  // Calculate drop position relative to canvas
  const rect = canvas.getBoundingClientRect();
  const x = e.clientX - rect.left;
  const y = e.clientY - rect.top;

  placeElement(data, x, y);
  scheduleAutoSave();
});

/**
 * Places a new element on the canvas at the specified coordinates.
 * @param {Object} data - The element data: src, label, and optional rotation, flipped, size, zIndex.
 * @param {number} x - The x-coordinate for the element's position on the canvas.
 * @param {number} y - The y-coordinate for the element's position on the canvas.
 */
function placeElement(data, x, y) {
  const el = document.createElement("div");
  el.className = "placed-element";
  el.style.position = "absolute";
  el.style.left = x + "px";
  el.style.top = y + "px";
  el.dataset.rotation = data.rotation ?? "0";
  el.dataset.flipped = data.flipped ?? "false";
  el.dataset.size = data.size ?? "48";
  if (data.zIndex) el.style.zIndex = data.zIndex;

  const size = data.size ?? 48;

  el.innerHTML = `
    <div class="element-toolbar">
      <button data-action="delete" title="Delete">✕</button>
      <button data-action="duplicate" title="Duplicate">⧉</button>
      <button data-action="enlarge" title="Enlarge">+</button>
      <button data-action="decrease" title="Decrease">−</button>
      <button data-action="rotate-right" title="Rotate Right">↻</button>
      <button data-action="rotate-left" title="Rotate Left">↺</button>
      <button data-action="flip-h" title="Flip Horizontal">↔</button>
      <button data-action="layer-up" title="Layer Up"><img src="/assets/icons/layer-up.svg" alt="Layer Up" width="16" height="16"></button>
      <button data-action="layer-down" title="Layer Down"><img src="/assets/icons/layer-down.svg" alt="Layer Down" width="16" height="16"></button>
    </div>
  `;

  const img = document.createElement("img");
  img.src    = data.src;
  img.alt    = data.label + " Icon.";
  img.width  = size;
  img.height = size;
  el.appendChild(img);

  const labelP = document.createElement("p");
  labelP.textContent = data.label;
  el.appendChild(labelP);

  canvas.appendChild(el);
  applyTransform(el);
}

// ─── Selection ────────────────────────────────────────────────────────────────

let selectedEl = null;

/**
 * Selects a placed element and deselects any previously selected one.
 * @param {HTMLElement} el - The placed element to select.
 */
function selectElement(el) {
  if (selectedEl && selectedEl !== el) selectedEl.classList.remove("selected");
  selectedEl = el;
  el.classList.add("selected");
}

/**
 * Deselects all placed elements.
 */
function deselectAll() {
  if (selectedEl) {
    selectedEl.classList.remove("selected");
    selectedEl = null;
  }
}

// Deselect when clicking the canvas background (not on a placed element)
canvas.addEventListener("click", (e) => {
  const actionBtn = e.target.closest("[data-action]");
  if (actionBtn) {
    handleAction(actionBtn.dataset.action);
    return;
  }

  const el = e.target.closest(".placed-element");
  if (el) {
    selectElement(el);
  } else {
    deselectAll();
  }
});

// ─── Toolbar Actions ───────────────────────────────────────────────────────────

/**
 * Applies the current rotation and flip state to the element's image.
 * @param {HTMLElement} el - The placed element whose image transform should be updated.
 */
function applyTransform(el) {
  const img = el.querySelector(":scope > img");
  const rotation = parseInt(el.dataset.rotation || "0");
  const flipped = el.dataset.flipped === "true";
  img.style.transform = `rotate(${rotation}deg) scaleX(${flipped ? -1 : 1})`;
}

/**
 * Handles a toolbar action on the currently selected element.
 * @param {string} action - The action identifier from the button's data-action attribute.
 */
function handleAction(action) {
  if (!selectedEl) return;

  switch (action) {
    case "delete":
      selectedEl.remove();
      selectedEl = null;
      break;

    case "duplicate": {
      const img = selectedEl.querySelector(":scope > img");
      const label = selectedEl.querySelector("p").textContent;
      placeElement(
        {
          src: img.src,
          label,
          rotation: selectedEl.dataset.rotation,
          flipped: selectedEl.dataset.flipped,
          size: parseInt(selectedEl.dataset.size),
          zIndex: selectedEl.style.zIndex,
        },
        parseInt(selectedEl.style.left) + 16,
        parseInt(selectedEl.style.top) + 16,
      );
      break;
    }

    case "enlarge": {
      const size = parseInt(selectedEl.dataset.size) + 8;
      selectedEl.dataset.size = size;
      const img = selectedEl.querySelector(":scope > img");
      img.width = size;
      img.height = size;
      break;
    }

    case "decrease": {
      const size = Math.max(16, parseInt(selectedEl.dataset.size) - 8);
      selectedEl.dataset.size = size;
      const img = selectedEl.querySelector(":scope > img");
      img.width = size;
      img.height = size;
      break;
    }

    case "rotate-right":
      selectedEl.dataset.rotation = (parseInt(selectedEl.dataset.rotation) + 45) % 360;
      applyTransform(selectedEl);
      break;

    case "rotate-left":
      selectedEl.dataset.rotation = (parseInt(selectedEl.dataset.rotation) - 45 + 360) % 360;
      applyTransform(selectedEl);
      break;

    case "flip-h":
      selectedEl.dataset.flipped = selectedEl.dataset.flipped === "true" ? "false" : "true";
      applyTransform(selectedEl);
      break;

    case "layer-up":
      selectedEl.style.zIndex = (parseInt(selectedEl.style.zIndex || "0") + 1).toString();
      break;

    case "layer-down":
      selectedEl.style.zIndex = Math.max(0, parseInt(selectedEl.style.zIndex || "0") - 1).toString();
      break;
  }
  scheduleAutoSave();
}

// ─── Drag to Reposition ────────────────────────────────────────────────────────

let activeEl = null;
let startX, startY;
let hasDragged = false;

// Skip drag initiation when clicking toolbar buttons
canvas.addEventListener("mousedown", (e) => {
  if (e.target.closest(".element-toolbar")) return;
  const el = e.target.closest(".placed-element");
  if (el) mouseDownHandler(e, el);
});

/**
 * Handles the mousedown event on a placed element, enabling it to be dragged around the canvas.
 * @param {MouseEvent} e - The mousedown event object.
 * @param {HTMLElement} el - The placed element that was clicked on.
 */
function mouseDownHandler(e, el) {
  activeEl = el;
  startX = e.clientX;
  startY = e.clientY;
  hasDragged = false;

  canvas.addEventListener("mousemove", mouseMoveHandler);
  canvas.addEventListener("mouseup", mouseUpHandler);
}

/**
 * Handles the mousemove event while dragging a placed element.
 * @param {MouseEvent} e - The mousemove event object.
 */
function mouseMoveHandler(e) {
  hasDragged = true;

  const newX = startX - e.clientX;
  const newY = startY - e.clientY;

  startX = e.clientX;
  startY = e.clientY;

  activeEl.style.left = activeEl.offsetLeft - newX + "px";
  activeEl.style.top = activeEl.offsetTop - newY + "px";
}

/**
 * Handles the end of a drag, selects the element on a plain click (no movement), and cleans up listeners.
 */
function mouseUpHandler() {
  canvas.removeEventListener("mousemove", mouseMoveHandler);
  canvas.removeEventListener("mouseup", mouseUpHandler);

  if (hasDragged) scheduleAutoSave();
  else if (activeEl) selectElement(activeEl);

  activeEl = null;
}

// ─── Save / Load ───────────────────────────────────────────────────────────────

// Tracks the database ID of the currently loaded plot (null = unsaved new plot).
let currentPlotId = null;

// JSON snapshot of the canvas + meta fields at the time of the last save or load.
// null means nothing has been saved/loaded yet this session.
let lastSavedState = null;

/**
 * Returns a JSON string representing the full current plot state (meta + elements).
 * Used to detect unsaved changes.
 * @returns {string}
 */
function getCurrentState() {
  return JSON.stringify({
    title: document.getElementById("plot-title").value.trim(),
    gig_date: document.getElementById("plot-gig-date").value.trim(),
    venue: document.getElementById("plot-venue").value.trim(),
    is_public: document.getElementById("plot-public-toggle").checked,
    elements: serializeCanvas(),
    inputs: serializeInputs(),
  });
}

/**
 * Returns true if the canvas has been modified since the last save or load.
 * @returns {boolean}
 */
function hasUnsavedChanges() {
  const current = getCurrentState();
  if (lastSavedState === null) {
    // Nothing has been saved yet — treat any content as unsaved
    const state = JSON.parse(current);
    return state.elements.length > 0 || state.title !== "" || state.gig_date !== "" || state.venue !== "";
  }
  return current !== lastSavedState;
}

/**
 * Collects the current canvas state into a plain array ready to POST to the API.
 * Uses the src pathname (not the full absolute URL) so paths stay portable.
 * @returns {Array<Object>}
 */
function serializeCanvas() {
  const elements = [];
  canvas.querySelectorAll(".placed-element").forEach((el) => {
    const img = el.querySelector(":scope > img");
    elements.push({
      src: new URL(img.src).pathname,
      label: el.querySelector("p").textContent,
      x: parseFloat(el.style.left),
      y: parseFloat(el.style.top),
      rotation: parseInt(el.dataset.rotation || "0"),
      flipped: el.dataset.flipped === "true",
      size: parseInt(el.dataset.size || "48"),
      z_index: Math.max(1, parseInt(el.style.zIndex || "1")),
    });
  });
  return elements;
}

/**
 * Collects the current inputs panel state (channels + details) into a plain object.
 * @returns {{ channels: Array<{num: string, label: string}>, details: string }}
 */
function serializeInputs() {
  const channels = Array.from(document.querySelectorAll("#channel-list .channel-row"))
    .map((row) => ({
      num: row.querySelector(".channel-num").value.trim(),
      label: row.querySelector(".channel-label").value.trim(),
    }))
    .filter((ch) => ch.num || ch.label);

  return {
    channels,
    details: document.getElementById("inputs-details").value.trim(),
  };
}

// ─── Auto-save ─────────────────────────────────────────────────────────────────

let autoSaveTimer = null;

const AUTO_SAVE_DELAY = 2000;

const STATUS_TEXT = { saving: "Saving…", saved: "Saved", unsaved: "Unsaved changes", "": "" };

function setAutoSaveStatus(status) {
  const el = document.getElementById("autosave-status");
  el.dataset.status = status;
  el.textContent = STATUS_TEXT[status] ?? "";
}

function scheduleAutoSave() {
  setAutoSaveStatus("unsaved");
  clearTimeout(autoSaveTimer);
  autoSaveTimer = setTimeout(autoSavePlot, AUTO_SAVE_DELAY);
}

const GIG_DATE_PATTERN = /^(0[1-9]|1[0-2])\/(0[1-9]|[12]\d|3[01])\/\d{4}$/;

async function autoSavePlot() {
  const title = document.getElementById("plot-title").value.trim();
  const gigDate = document.getElementById("plot-gig-date").value.trim();

  if (!currentPlotId && (!title || !gigDate)) return;
  if (gigDate && !GIG_DATE_PATTERN.test(gigDate)) return;
  if (!hasUnsavedChanges()) {
    setAutoSaveStatus("saved");
    return;
  }

  setAutoSaveStatus("saving");

  try {
    const res = await fetch("/api/save-plot.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        plot_id: currentPlotId,
        title,
        gig_date: gigDate,
        venue: document.getElementById("plot-venue").value.trim() || null,
        is_public: document.getElementById("plot-public-toggle").checked,
        elements: serializeCanvas(),
        inputs: serializeInputs(),
      }),
    });

    const data = await res.json();

    if (data.success) {
      currentPlotId = data.plot_id;
      lastSavedState = getCurrentState();
      setAutoSaveStatus("saved");
    } else {
      setAutoSaveStatus("unsaved");
    }
  } catch {
    setAutoSaveStatus("unsaved");
  }
}

/**
 * Saves the current plot (creates new or updates existing) via the API.
 * Stores the returned plot_id so subsequent saves perform an update.
 */
async function savePlot() {
  const title = document.getElementById("plot-title").value.trim();
  const gigDate = document.getElementById("plot-gig-date").value.trim();
  const venue = document.getElementById("plot-venue").value.trim();

  if (!title || !gigDate) {
    alert("Title and Gig Date are required to save.");
    return;
  }

  if (!GIG_DATE_PATTERN.test(gigDate)) {
    alert("Gig Date must be in mm/dd/yyyy format (e.g. 06/15/2026).");
    return;
  }

  closeDropdown();

  try {
    const res = await fetch("/api/save-plot.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        plot_id: currentPlotId,
        title,
        gig_date: gigDate,
        venue: venue || null,
        is_public: document.getElementById("plot-public-toggle").checked,
        elements: serializeCanvas(),
        inputs: serializeInputs(),
      }),
    });

    const data = await res.json();

    if (data.success) {
      currentPlotId = data.plot_id;
      lastSavedState = getCurrentState();
      clearTimeout(autoSaveTimer);
      setAutoSaveStatus("saved");
      alert("Plot saved!");
      return true;
    } else {
      setAutoSaveStatus("unsaved");
      alert("Save failed:\n" + (data.errors ? data.errors.join("\n") : data.error));
      return false;
    }
  } catch {
    setAutoSaveStatus("unsaved");
    alert("Error: could not reach the server.");
    return false;
  }
}

/**
 * Fetches the user's saved plots and opens the My Plots modal.
 */
async function showMyPlots() {
  closeDropdown();

  try {
    const res = await fetch("/api/load-plots.php");
    const data = await res.json();

    if (!data.success) {
      alert("Could not load plots.");
      return;
    }

    const list = document.getElementById("my-plots-list");
    list.innerHTML = "";

    if (data.plots.length === 0) {
      list.innerHTML = '<li class="my-plots-empty">No saved plots found.</li>';
    } else {
      data.plots.forEach((plot) => {
        const li = document.createElement("li");
        li.className = "my-plots-item";
        const titleSpan = document.createElement("span");
        titleSpan.className = "my-plots-title";
        titleSpan.textContent = plot.title;
        const metaSpan = document.createElement("span");
        metaSpan.className = "my-plots-meta";
        metaSpan.textContent = plot.gig_date + (plot.venue ? " — " + plot.venue : "");
        li.appendChild(titleSpan);
        li.appendChild(metaSpan);
        li.addEventListener("click", () => loadPlot(plot.id));
        list.appendChild(li);
      });
    }

    document.getElementById("my-plots-modal").removeAttribute("hidden");
  } catch {
    alert("Error: could not reach the server.");
  }
}

/**
 * Loads a saved plot from the API, clears the canvas, and restores all elements.
 * @param {number} plotId
 */
async function loadPlot(plotId) {
  closeMyPlots();

  try {
    const res = await fetch("/api/load-plot.php?id=" + plotId);
    const data = await res.json();

    if (!data.success) {
      alert("Could not load plot.");
      return;
    }

    // Restore meta fields
    document.getElementById("plot-title").value = data.title;
    document.getElementById("plot-gig-date").value = data.gig_date;
    document.getElementById("plot-venue").value = data.venue ?? "";
    document.getElementById("plot-public-toggle").checked = !!data.is_public;
    currentPlotId = data.plot_id;

    // Clear the canvas and redraw elements
    deselectAll();
    canvas.querySelectorAll(".placed-element").forEach((el) => el.remove());

    data.elements.forEach((el) => {
      placeElement(
        {
          src: el.src,
          label: el.label,
          rotation: String(el.rotation),
          flipped: el.flipped ? "true" : "false",
          size: el.size,
          zIndex: String(el.z_index),
        },
        el.x,
        el.y,
      );
    });

    // Restore inputs panel
    const inputs = data.inputs ?? { channels: [], details: "" };
    document.getElementById("channel-list").innerHTML = "";
    document.getElementById("inputs-details").value = inputs.details || "";
    if (inputs.channels.length > 0) {
      inputs.channels.forEach((ch) => {
        document.getElementById("channel-list").appendChild(createChannelRow("", ch.num, ch.label));
      });
    } else {
      const placeholders = ["Electric guitar", "Keyboard", "Snare...", "", ""];
      for (let i = 0; i < 5; i++) {
        document.getElementById("channel-list").appendChild(createChannelRow(placeholders[i], i + 1));
      }
    }

    lastSavedState = getCurrentState();
    clearTimeout(autoSaveTimer);
    setAutoSaveStatus("saved");
  } catch {
    alert("Error: could not reach the server.");
  }
}

/**
 * Resets the canvas and all plot meta fields to a blank state.
 */
function resetPlot() {
  deselectAll();
  canvas.querySelectorAll(".placed-element").forEach((el) => el.remove());
  document.getElementById("plot-title").value = "";
  document.getElementById("plot-gig-date").value = "";
  document.getElementById("plot-venue").value = "";
  document.getElementById("plot-public-toggle").checked = false;
  document.getElementById("inputs-details").value = "";
  document.getElementById("channel-list").innerHTML = "";
  currentPlotId = null;
  lastSavedState = null;
  clearTimeout(autoSaveTimer);
  setAutoSaveStatus("");
}

/**
 * Starts a new blank plot. If there are unsaved changes, prompts the user to
 * save first or confirm discarding before resetting.
 */
async function newPlot() {
  closeDropdown();

  if (hasUnsavedChanges()) {
    const wantToSave = confirm("You have unsaved changes. Save before starting a new plot?");
    if (wantToSave) {
      const saved = await savePlot();
      if (!saved) return; // Save failed or missing required fields — stay on current plot
    } else {
      if (!confirm("Discard unsaved changes and start a new plot?")) return;
    }
  }

  resetPlot();
}

/**
 * Generates (or retrieves) a share link for the current plot and shows the
 * share modal. Prompts to save first if the plot hasn't been saved yet.
 */
async function sharePlot() {
  closeDropdown();

  if (!currentPlotId) {
    const wantToSave = confirm("The plot must be saved before sharing. Save now?");
    if (!wantToSave) return;
    const saved = await savePlot();
    if (!saved) return;
  }

  try {
    const res = await fetch("/api/share-plot.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ plot_id: currentPlotId }),
    });
    const data = await res.json();

    if (!data.success) {
      alert("Could not generate share link.");
      return;
    }

    document.getElementById("share-link-input").value = data.url;
    document.getElementById("share-modal").removeAttribute("hidden");
  } catch {
    alert("Error: could not reach the server.");
  }
}

/**
 * Closes the My Plots modal.
 */
function closeMyPlots() {
  document.getElementById("my-plots-modal").setAttribute("hidden", "");
}

/**
 * Closes the plot actions dropdown by unchecking its toggle checkbox.
 */
function closeDropdown() {
  document.getElementById("plot-toolbar-toggle").checked = false;
}

/**
 * Renders an element image to a PNG data URL at the given resolution,
 * applying rotation (degrees, clockwise) and optional horizontal flip —
 * matching what applyTransform() does on the live canvas.
 * @param {string}  src         - Same-origin image path
 * @param {number}  sizePx      - Output canvas size in pixels
 * @param {number}  rotationDeg - Rotation in degrees
 * @param {boolean} flipped     - Whether to flip horizontally
 * @returns {Promise<string>} PNG data URL
 */
async function rasterizeElement(src, sizePx, rotationDeg, flipped) {
  // Fetch the file and, for SVGs, strip <foreignObject> nodes before drawing.
  // SVGs containing <foreignObject> taint the canvas and cause toDataURL() to
  // throw a SecurityError, silently dropping the element from the PDF.
  const res = await fetch(src);
  let blobUrl;

  if (src.toLowerCase().endsWith(".svg")) {
    const text = await res.text();
    const doc = new DOMParser().parseFromString(text, "image/svg+xml");
    doc.querySelectorAll("foreignObject").forEach((el) => el.remove());
    const cleaned = new XMLSerializer().serializeToString(doc.documentElement);
    blobUrl = URL.createObjectURL(new Blob([cleaned], { type: "image/svg+xml" }));
  } else {
    blobUrl = URL.createObjectURL(await res.blob());
  }

  return new Promise((resolve, reject) => {
    const img = new Image();
    img.onload = () => {
      try {
        const cvs = document.createElement("canvas");
        cvs.width = sizePx;
        cvs.height = sizePx;
        const ctx = cvs.getContext("2d");

        // SVGs exported from Illustrator often have no explicit width/height —
        // fall back to sizePx so drawImage has valid destination dimensions.
        const natW = img.naturalWidth || sizePx;
        const natH = img.naturalHeight || sizePx;
        const ratio = Math.min(sizePx / natW, sizePx / natH);
        const drawW = natW * ratio;
        const drawH = natH * ratio;

        ctx.translate(sizePx / 2, sizePx / 2);
        if (rotationDeg) ctx.rotate((rotationDeg * Math.PI) / 180);
        if (flipped) ctx.scale(-1, 1);
        ctx.drawImage(img, -drawW / 2, -drawH / 2, drawW, drawH);
        resolve(cvs.toDataURL("image/png"));
      } catch (err) {
        reject(err);
      } finally {
        URL.revokeObjectURL(blobUrl);
      }
    };
    img.onerror = (err) => {
      URL.revokeObjectURL(blobUrl);
      reject(err);
    };
    img.src = blobUrl;
  });
}

/**
 * Builds and returns a jsPDF document for the current stage plot.
 * @returns {Promise<{pdf: jsPDF, title: string}>}
 */
async function buildPdf() {
  if (!window.jspdf) throw new Error("PDF library failed to load. Please refresh and try again.");

  deselectAll();

  const title = document.getElementById("plot-title").value.trim() || "Stage Plot";
  const gigDate = document.getElementById("plot-gig-date").value.trim();
  const venue = document.getElementById("plot-venue").value.trim();
  const canvasEl = document.querySelector(".stage-plot-canvas");
  const canvasW = canvasEl.offsetWidth;
  const canvasH = canvasEl.offsetHeight;
  const elements = serializeCanvas();

  const { jsPDF } = window.jspdf;
  const pdf = new jsPDF({ orientation: "landscape", unit: "mm", format: "a4" });
  const pageW = pdf.internal.pageSize.getWidth(); // 297 mm
  const pageH = pdf.internal.pageSize.getHeight(); // 210 mm
  const margin = 10;
  let cursorY = margin;

  // ── Title
  pdf.setFontSize(18);
  pdf.setTextColor(30, 30, 30);
  pdf.text(title, margin, cursorY + 6);
  cursorY += 10;

  // ── Subtitle (date + venue)
  const subtitle = [gigDate, venue].filter(Boolean).join(" \u2014 ");
  if (subtitle) {
    pdf.setFontSize(11);
    pdf.setTextColor(85, 85, 85);
    pdf.text(subtitle, margin, cursorY + 4);
    cursorY += 8;
  }
  cursorY += 2;

  // ── Stage background — scale to fill remaining page area proportionally
  const plotW = pageW - margin * 2;
  const plotH = pageH - cursorY - margin;
  const scale = Math.min(plotW / canvasW, plotH / canvasH);
  const plotX = margin;
  const plotY = cursorY;

  pdf.setDrawColor(30, 30, 30);
  pdf.setLineWidth(1.5);
  pdf.rect(plotX, plotY, canvasW * scale, canvasH * scale, "S");

  // ── Place each element
  for (const el of elements) {
    const sizePx = Math.round(el.size * 4); // 4× for sharpness
    let dataUrl;
    try {
      dataUrl = await rasterizeElement(el.src, sizePx, el.rotation, el.flipped);
    } catch {
      continue; // skip if image fails to load
    }

    const x = plotX + el.x * scale;
    const y = plotY + el.y * scale;
    const w = el.size * scale;

    pdf.addImage(dataUrl, "PNG", x, y, w, w);

    // Label centered below the element
    pdf.setFontSize(7);
    pdf.setTextColor(255, 255, 255);
    pdf.text(el.label, x + w / 2, y + w + 3, { align: "center" });
  }

  // ── Page 2: Inputs (channels + details) ─────────────────────────────────────

  const channels = Array.from(document.querySelectorAll("#channel-list .channel-row"))
    .map((row) => ({
      num: row.querySelector(".channel-num").value.trim(),
      label: row.querySelector(".channel-label").value.trim(),
    }))
    .filter((ch) => ch.num || ch.label);

  const details = document.getElementById("inputs-details").value.trim();

  if (channels.length > 0 || details) {
    pdf.addPage();
    let y = margin;

    // Page title
    pdf.setFontSize(16);
    pdf.setTextColor(30, 30, 30);
    pdf.text("Inputs", margin, y + 6);
    y += 14;

    // ── Channel list
    if (channels.length > 0) {
      const colNum = margin;
      const colLabel = margin + 18;
      const rowH = 7;

      // Header
      pdf.setFontSize(8);
      pdf.setTextColor(100, 100, 100);
      pdf.text("CH", colNum, y);
      pdf.text("Description", colLabel, y);
      y += 4;

      // Divider
      pdf.setDrawColor(180, 180, 180);
      pdf.line(margin, y, pageW - margin, y);
      y += 5;

      // Rows
      pdf.setFontSize(10);
      channels.forEach((ch, i) => {
        if (y + rowH > pageH - margin) {
          pdf.addPage();
          y = margin;
        }
        // Alternating row background
        if (i % 2 === 0) {
          pdf.setFillColor(240, 244, 248);
          pdf.rect(margin, y - 4, pageW - margin * 2, rowH, "F");
        }
        pdf.setTextColor(30, 30, 30);
        pdf.text(ch.num, colNum, y);
        pdf.text(ch.label, colLabel, y);
        y += rowH;
      });

      y += 6;
    }

    // ── Details
    if (details) {
      pdf.setFontSize(12);
      pdf.setTextColor(30, 30, 30);
      pdf.text("Details", margin, y);
      y += 6;

      pdf.setFontSize(10);
      pdf.setTextColor(50, 50, 50);
      const lines = pdf.splitTextToSize(details, pageW - margin * 2);
      lines.forEach((line) => {
        if (y + 6 > pageH - margin) {
          pdf.addPage();
          y = margin;
        }
        pdf.text(line, margin, y);
        y += 6;
      });
    }
  }

  return { pdf, title };
}

/**
 * Exports the current stage plot as a downloadable PDF.
 */
async function exportPlot() {
  closeDropdown();
  try {
    const { pdf, title } = await buildPdf();
    const filename = title.replace(/[^a-z0-9]/gi, "_").replace(/_+/g, "_") + ".pdf";
    pdf.save(filename);
  } catch (err) {
    alert("Export failed: " + err.message);
  }
}

/**
 * Prints the current stage plot by generating the same PDF as export and
 * opening it in a new tab with the print dialog triggered automatically.
 */
async function printPlot() {
  closeDropdown();
  // Open the window immediately while still in the user-gesture context so
  // popup blockers don't intervene after the async PDF build finishes.
  const win = window.open("", "_blank");
  if (!win) return;
  try {
    const { pdf } = await buildPdf();
    pdf.autoPrint();
    const url = URL.createObjectURL(pdf.output("blob"));
    win.location.href = url;
    win.addEventListener("unload", () => URL.revokeObjectURL(url), { once: true });
  } catch (err) {
    win.close();
    alert("Print failed: " + err.message);
  }
}

document.addEventListener("mousedown", (e) => {
  const plotSettings = document.querySelector(".plot-settings");
  if (plotSettings && !plotSettings.contains(e.target)) {
    closeDropdown();
  }
}, true);

// ─── Button wiring ─────────────────────────────────────────────────────────────

document.getElementById("export-plot-btn").addEventListener("click", exportPlot);
document.getElementById("print-plot-btn").addEventListener("click", printPlot);
document.getElementById("share-plot-btn").addEventListener("click", sharePlot);
document.getElementById("new-plot-btn").addEventListener("click", newPlot);
document.getElementById("save-plot-btn").addEventListener("click", savePlot);
document.getElementById("my-plots-btn").addEventListener("click", showMyPlots);
document.getElementById("my-plots-cancel").addEventListener("click", closeMyPlots);
document.getElementById("share-modal-close").addEventListener("click", () => {
  document.getElementById("share-modal").setAttribute("hidden", "");
});
document.getElementById("copy-link-btn").addEventListener("click", () => {
  const input = document.getElementById("share-link-input");
  navigator.clipboard.writeText(input.value).then(() => {
    const btn = document.getElementById("copy-link-btn");
    btn.textContent = "Copied!";
    setTimeout(() => {
      btn.textContent = "Copy";
    }, 2000);
  });
});
document.getElementById("clear-stage-btn").addEventListener("click", () => {
  closeDropdown();
  deselectAll();
  canvas.querySelectorAll(".placed-element").forEach((el) => el.remove());
  scheduleAutoSave();
});

// ─── Auto-save change listeners ────────────────────────────────────────────────

["plot-title", "plot-gig-date", "plot-venue"].forEach((id) => {
  document.getElementById(id).addEventListener("input", scheduleAutoSave);
});
document.getElementById("plot-public-toggle").addEventListener("change", scheduleAutoSave);

// ─── Inputs Panel ──────────────────────────────────────────────────────────────

const palette = document.querySelector(".palette");
const inputsPanel = document.getElementById("inputs-panel");
const channelList = document.getElementById("channel-list");
const channelsView = document.getElementById("channels-view");
const detailsView = document.getElementById("details-view");

/**
 * Creates and returns a new channel list item with a number input, text input,
 * and a delete button.
 */
function createChannelRow(placeholder = "", channelNum = null, labelValue = "") {
  const li = document.createElement("li");
  li.className = "channel-row";
  li.draggable = true;

  const handle = document.createElement("span");
  handle.className = "channel-drag-handle";
  handle.setAttribute("aria-hidden", "true");
  handle.textContent = "⠿";

  const numInput = document.createElement("input");
  numInput.type = "number";
  numInput.className = "channel-num";
  numInput.min = "1";
  numInput.max = "999";
  numInput.placeholder = "#";
  if (channelNum !== null) numInput.value = channelNum;

  const labelInput = document.createElement("input");
  labelInput.type = "text";
  labelInput.className = "channel-label";
  labelInput.placeholder = placeholder;
  if (labelValue) labelInput.value = labelValue;

  const deleteBtn = document.createElement("button");
  deleteBtn.className = "btn btn-ghost channel-delete-btn";
  deleteBtn.setAttribute("aria-label", "Delete channel");
  deleteBtn.textContent = "✕";
  deleteBtn.addEventListener("click", () => {
    li.remove();
    scheduleAutoSave();
  });

  li.appendChild(handle);
  li.appendChild(numInput);
  li.appendChild(labelInput);
  li.appendChild(deleteBtn);

  return li;
}

// ── Channel list drag-to-reorder ────────────────────────────────────────────

let draggedRow = null;

channelList.addEventListener("dragstart", (e) => {
  draggedRow = e.target.closest(".channel-row");
  if (!draggedRow) return;
  draggedRow.classList.add("dragging");
  e.dataTransfer.effectAllowed = "move";
});

channelList.addEventListener("dragend", () => {
  if (draggedRow) draggedRow.classList.remove("dragging");
  channelList.querySelectorAll(".channel-row.drag-over").forEach((r) => r.classList.remove("drag-over"));
  draggedRow = null;
});

channelList.addEventListener("dragover", (e) => {
  e.preventDefault();
  if (!draggedRow) return;

  const target = e.target.closest(".channel-row");
  if (!target || target === draggedRow) return;

  const rect = target.getBoundingClientRect();
  const insertAfter = e.clientY > rect.top + rect.height / 2;

  channelList.querySelectorAll(".channel-row.drag-over").forEach((r) => r.classList.remove("drag-over"));
  target.classList.add("drag-over");

  if (insertAfter) {
    target.after(draggedRow);
  } else {
    target.before(draggedRow);
  }
});

const instrumentSubcategories = document.getElementById("instrument-subcategories");
const equipmentSubcategories  = document.getElementById("equipment-subcategories");

/** Shows the inputs panel and hides the regular palette. */
function showInputsPanel() {
  palette.setAttribute("hidden", "");
  inputsPanel.removeAttribute("hidden");

  // Default to channels tab
  channelsView.removeAttribute("hidden");
  detailsView.setAttribute("hidden", "");
  document.getElementById("channels-tab-btn").classList.add("active-tab");
  document.getElementById("details-tab-btn").classList.remove("active-tab");

  // Seed with 5 rows if empty
  if (channelList.children.length === 0) {
    const placeholders = ["Electric guitar", "Keyboard", "Snare...", "", ""];
    for (let i = 0; i < 5; i++) channelList.appendChild(createChannelRow(placeholders[i], i + 1));
  }
}

/** Shows the instrument palette and hides everything else. */
function showInstrumentPalette() {
  inputsPanel.setAttribute("hidden", "");
  palette.removeAttribute("hidden");
  instrumentSubcategories.removeAttribute("hidden");
  equipmentSubcategories.setAttribute("hidden", "");
}

/** Shows the equipment palette and hides everything else. */
function showEquipmentPalette() {
  inputsPanel.setAttribute("hidden", "");
  palette.removeAttribute("hidden");
  equipmentSubcategories.removeAttribute("hidden");
  instrumentSubcategories.setAttribute("hidden", "");
  switchPalette("audio", "equipment");
}

document.getElementById("instrument-palette-toggle").addEventListener("click", showInstrumentPalette);
document.getElementById("equipment-palette-toggle").addEventListener("click", showEquipmentPalette);
document.getElementById("input-palette-toggle").addEventListener("click", showInputsPanel);

document.getElementById("add-channel-btn").addEventListener("click", () => {
  channelList.appendChild(createChannelRow());
  scheduleAutoSave();
});

document.getElementById("inputs-details").addEventListener("input", scheduleAutoSave);
channelList.addEventListener("input", scheduleAutoSave);

document.getElementById("channels-tab-btn").addEventListener("click", () => {
  channelsView.removeAttribute("hidden");
  detailsView.setAttribute("hidden", "");
  document.getElementById("channels-tab-btn").classList.add("active-tab");
  document.getElementById("details-tab-btn").classList.remove("active-tab");
});

document.getElementById("details-tab-btn").addEventListener("click", () => {
  detailsView.removeAttribute("hidden");
  channelsView.setAttribute("hidden", "");
  document.getElementById("details-tab-btn").classList.add("active-tab");
  document.getElementById("channels-tab-btn").classList.remove("active-tab");
});

// Load the default palette category on page load.
switchPalette("guitars", "instruments");
