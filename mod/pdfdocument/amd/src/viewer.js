// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Protected PDF viewer for mod_pdfdocument.
 *
 * Renders each page to a canvas using the vendored PDF.js build. The text
 * layer is never rendered (so page text cannot be selected/copied) and no
 * native browser PDF viewer is involved. UI-level deterrents block the common
 * casual download paths. This is deterrence, not DRM: a determined user with
 * developer tools can still capture the network response.
 *
 * @module     mod_pdfdocument/viewer
 * @copyright  2026 Web Programacion
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** URL of the vendored PDF.js main library, relative to the site root. */
const PDFJS_LIB_URL = M.cfg.wwwroot + '/mod/pdfdocument/js/pdfjs/pdf.min.js';

/** Zoom step and bounds. */
const ZOOM_STEP = 0.25;
const ZOOM_MIN = 0.25;
const ZOOM_MAX = 4;

/**
 * Performs a native dynamic import.
 *
 * Wrapped in a Function constructor so that neither RequireJS nor the
 * Grunt/Babel AMD build rewrites the `import()` into a module require(): PDF.js
 * 4.x ships as an ES module and must be loaded as one.
 *
 * @param {string} url The module URL to import.
 * @return {Promise<Object>} Resolves with the module namespace object.
 */
const dynamicImport = (url) => (new Function('u', 'return import(u);'))(url);

/**
 * Loads the vendored PDF.js ES module and points it at the worker.
 *
 * @param {string} workerUrl Absolute URL of the PDF.js worker module.
 * @return {Promise<Object>} Resolves with the pdfjsLib module namespace.
 */
const loadPdfjs = async (workerUrl) => {
    const pdfjsLib = await dynamicImport(PDFJS_LIB_URL);
    pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl;
    return pdfjsLib;
};

/**
 * Encapsulates a single viewer instance bound to one page region.
 */
class Viewer {
    /**
     * @param {HTMLElement} root The viewer root element.
     * @param {Object} config Server-provided configuration.
     */
    constructor(root, config) {
        this.root = root;
        this.config = config;
        this.pdf = null;
        this.currentPage = 1;
        this.scale = 1; // Default to 100%; fit-width (null) recomputes on demand.
        this.rendering = false;

        this.pagesRegion = root.querySelector('[data-region="pages"]');
        this.loadingRegion = root.querySelector('[data-region="loading"]');
        this.errorRegion = root.querySelector('[data-region="error"]');
        this.pageNumInput = root.querySelector('[data-region="pagenum"]');
        this.pageCountLabel = root.querySelector('[data-region="pagecount"]');
    }

    /**
     * Loads the document and wires up the UI.
     *
     * @return {Promise<void>}
     */
    async start() {
        this.attachDeterrents();
        this.attachControls();
        try {
            const pdfjsLib = await loadPdfjs(this.config.workerUrl);
            // withCredentials so the session cookie reaches the protected endpoint.
            const task = pdfjsLib.getDocument({
                url: this.config.fileUrl,
                withCredentials: true,
                isEvalSupported: false,
            });
            this.pdf = await task.promise;
            this.pageCountLabel.textContent = String(this.pdf.numPages);
            this.pageNumInput.max = String(this.pdf.numPages);
            this.hideLoading();
            await this.renderPage(1);
        } catch (err) {
            this.showError(err);
        }
    }

    /**
     * Renders a single page onto a fresh canvas.
     *
     * @param {number} pageNumber The 1-based page number.
     * @return {Promise<void>}
     */
    async renderPage(pageNumber) {
        if (this.rendering || !this.pdf) {
            return;
        }
        if (pageNumber < 1 || pageNumber > this.pdf.numPages) {
            return;
        }
        this.rendering = true;
        this.currentPage = pageNumber;
        this.pageNumInput.value = String(pageNumber);

        const page = await this.pdf.getPage(pageNumber);

        // Determine scale: fit-to-width when no explicit zoom is set.
        const unscaled = page.getViewport({scale: 1});
        if (this.scale === null) {
            const available = this.pagesRegion.clientWidth || unscaled.width;
            this.scale = Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, available / unscaled.width));
        }
        const viewport = page.getViewport({scale: this.scale});

        const canvas = document.createElement('canvas');
        canvas.className = 'pdfdocument-page';
        const ratio = window.devicePixelRatio || 1;
        canvas.width = Math.floor(viewport.width * ratio);
        canvas.height = Math.floor(viewport.height * ratio);
        canvas.style.width = Math.floor(viewport.width) + 'px';
        canvas.style.height = Math.floor(viewport.height) + 'px';

        const ctx = canvas.getContext('2d');
        // Note: the text layer is intentionally NOT rendered.
        await page.render({
            canvasContext: ctx,
            viewport: viewport,
            transform: ratio !== 1 ? [ratio, 0, 0, ratio, 0, 0] : null,
        }).promise;

        if (this.config.watermark && this.config.watermarkText) {
            this.drawWatermark(ctx, canvas.width, canvas.height);
        }

        this.pagesRegion.replaceChildren(canvas);
        this.rendering = false;
    }

    /**
     * Draws a diagonal, semi-transparent, tiled identity watermark.
     *
     * @param {CanvasRenderingContext2D} ctx The canvas 2D context.
     * @param {number} width Canvas pixel width.
     * @param {number} height Canvas pixel height.
     */
    drawWatermark(ctx, width, height) {
        ctx.save();
        ctx.globalAlpha = 0.18;
        ctx.fillStyle = '#444';
        const fontSize = Math.max(16, Math.floor(width / 28));
        ctx.font = fontSize + 'px sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        const text = this.config.watermarkText;
        const step = fontSize * 12;
        for (let y = -height; y < height * 2; y += step) {
            for (let x = -width; x < width * 2; x += step) {
                ctx.save();
                ctx.translate(x, y);
                ctx.rotate(-Math.PI / 6);
                ctx.fillText(text, 0, 0);
                ctx.restore();
            }
        }
        ctx.restore();
    }

    /**
     * Applies a zoom delta (or fit-width when delta is null) and re-renders.
     *
     * @param {number|null} delta Amount to add to the current scale, or null to fit width.
     */
    zoom(delta) {
        if (delta === null) {
            this.scale = null; // Recompute fit-to-width on next render.
        } else {
            const base = this.scale === null ? 1 : this.scale;
            this.scale = Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, base + delta));
        }
        this.renderPage(this.currentPage);
    }

    /**
     * Wires toolbar buttons and the page-number input.
     */
    attachControls() {
        this.root.addEventListener('click', (e) => {
            const button = e.target.closest('[data-action]');
            if (!button) {
                return;
            }
            const action = button.getAttribute('data-action');
            if (action === 'prev') {
                this.renderPage(this.currentPage - 1);
            } else if (action === 'next') {
                this.renderPage(this.currentPage + 1);
            } else if (action === 'zoomin') {
                this.zoom(ZOOM_STEP);
            } else if (action === 'zoomout') {
                this.zoom(-ZOOM_STEP);
            } else if (action === 'fitwidth') {
                this.zoom(null);
            }
        });

        this.pageNumInput.addEventListener('change', () => {
            const target = parseInt(this.pageNumInput.value, 10);
            if (!isNaN(target)) {
                this.renderPage(target);
            }
        });
    }

    /**
     * Blocks the common casual capture/download paths within the viewer.
     */
    attachDeterrents() {
        // No context menu (removes "Save image as…").
        this.root.addEventListener('contextmenu', (e) => e.preventDefault());
        // Swallow Ctrl/Cmd+S and Ctrl/Cmd+P while the viewer has focus/hover.
        document.addEventListener('keydown', (e) => {
            const key = (e.key || '').toLowerCase();
            if ((e.ctrlKey || e.metaKey) && (key === 's' || key === 'p')) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    }

    /** Hides the loading indicator. */
    hideLoading() {
        if (this.loadingRegion) {
            this.loadingRegion.hidden = true;
        }
    }

    /**
     * Reveals the localized error region and logs details for debugging.
     *
     * @param {Error} err The underlying error.
     */
    showError(err) {
        this.hideLoading();
        if (this.errorRegion) {
            this.errorRegion.hidden = false;
        }
        // Detail goes to the console only, never to the user.
        window.console.error('mod_pdfdocument viewer error:', err);
    }
}

export const init = (config) => {
    const root = document.querySelector('[data-region="pdfdocument-viewer"]');
    if (!root) {
        return;
    }
    const viewer = new Viewer(root, config);
    viewer.start();
};
