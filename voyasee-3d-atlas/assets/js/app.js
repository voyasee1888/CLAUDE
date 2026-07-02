(function () {
  "use strict";

  // Phase 1 scaffold only. The COBE globe boot (lazy IntersectionObserver
  // init, context-loss handling, reduced-motion, DPR clamp) is wired up
  // starting Phase 2 against the [data-v3datlas-globe-mount] element below.
  document.querySelectorAll("[data-v3datlas-root]").forEach(function (root) {
    root.setAttribute("data-v3datlas-ready", "1");
  });
})();
