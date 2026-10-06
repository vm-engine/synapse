import "flatpickr/dist/flatpickr.min.css";

import $ from "jquery";
import mask from "@alpinejs/mask";
import intersect from "@alpinejs/intersect";
import resize from "@alpinejs/resize";
import focus from "@alpinejs/focus";
import collapse from "@alpinejs/collapse";
import anchor from "@alpinejs/anchor";
import morph from "@alpinejs/morph";
import sort from "@alpinejs/sort";
import flatpickr from "flatpickr";
import "./synapse-components.js";

Alpine.plugin(mask);
Alpine.plugin(intersect);
Alpine.plugin(resize);
Alpine.plugin(focus);
Alpine.plugin(collapse);
Alpine.plugin(morph);
Alpine.plugin(anchor);
Alpine.plugin(sort);

(function () {
    // helper to try init newly injected nodes
    function initAlpineOn(el) {
        try {
            // Alpine v3 exposes initTree; if unavailable, fallback to start()
            if (typeof Alpine === "undefined") return;
            if (typeof Alpine.initTree === "function") {
                Alpine.initTree(el);
            } else {
                // fallback: re-start Alpine (safe because Alpine.start() is idempotent)
                Alpine.start();
            }
        } catch (e) {
            console.warn("Alpine init error", e);
        }
    }

    // Clones each .datatable column's <th> text onto its <td>s as
    // data-label, so the CSS-only mobile card view (theme-app.css)
    // can show "Label / Value" per cell without any Blade changes.
    // <th class="datatable-col-checkbox|datatable-col-actions"> instead
    // marks that column for special card placement (see synapse-components.css).
    function initDatatableLabels(root) {
        if (!root || typeof root.querySelectorAll !== "function") return;

        root.querySelectorAll("table.datatable").forEach((table) => {
            const headerCells = Array.from(
                table.querySelectorAll(":scope > thead > tr:first-child > th")
            );
            if (!headerCells.length) return;

            const labels = headerCells.map((th) => {
                if (
                    th.classList.contains("datatable-col-checkbox") ||
                    th.classList.contains("datatable-col-actions")
                ) {
                    return null;
                }
                const text = (
                    th.querySelector("p")?.textContent ?? th.textContent
                ).trim();
                return text || null;
            });

            table
                .querySelectorAll(":scope > tbody > tr")
                .forEach((row) => {
                    Array.from(row.children).forEach((td, index) => {
                        const th = headerCells[index];
                        if (!th) return;

                        if (th.classList.contains("datatable-col-checkbox")) {
                            td.classList.add("datatable-col-checkbox");
                            return;
                        }
                        if (th.classList.contains("datatable-col-actions")) {
                            td.classList.add("datatable-col-actions");
                            return;
                        }
                        if (labels[index]) {
                            td.setAttribute("data-label", labels[index]);
                        }
                    });
                });
        });
    }

    // On first load, nothing special — Alpine runs normally.
    initDatatableLabels(document);

    // When Livewire navigates or morphs DOM, initialize Alpine on new root nodes.
    document.addEventListener("livewire:navigated", function (e) {
        // Livewire swaps a page root; try to find the root container and init
        // e.detail ? may not exist on all versions; fallback to document.body
        var root =
            e && e.detail && e.detail.root ? e.detail.root : document.body;
        initAlpineOn(root);
        initDatatableLabels(root);
    });

    // Also handle Livewire partial updates (morphs)
    Livewire?.hook?.("message.processed", (message, component) => {
        // find any elements that look like our component and init them
        // we initialize the entire component node (component.el) if available
        try {
            if (component && component.el) {
                initAlpineOn(component.el);
                initDatatableLabels(component.el);
            }
        } catch (err) {
            // ignore
        }
    });
})();
