import createGlobe from "v3da-cobe";

/**
 * Phase 2: core COBE render with static demo markers. Lazy-boot
 * (IntersectionObserver), WebGL context-loss handling, prefers-reduced-motion,
 * and devicePixelRatio clamping are deliberately deferred to later phases —
 * see Phase 5 (SEO/accessibility) and Phase 6 (performance hardening).
 */
function boot(root) {
  var mount = root.querySelector("[data-v3datlas-globe-mount]");
  if (!mount) return;

  var markers = [];
  try {
    markers = JSON.parse(mount.getAttribute("data-markers") || "[]");
  } catch (err) {
    markers = [];
  }

  var canvas = document.createElement("canvas");
  canvas.className = "v3datlas-globe-canvas";
  mount.appendChild(canvas);

  var size = mount.clientWidth || 600;
  var dpr = window.devicePixelRatio || 1;
  canvas.width = size * dpr;
  canvas.height = size * dpr;
  canvas.style.width = size + "px";
  canvas.style.height = size + "px";

  var phi = 0;
  var globe = createGlobe(canvas, {
    devicePixelRatio: dpr,
    width: size * dpr,
    height: size * dpr,
    phi: 0,
    theta: 0.3,
    dark: 1,
    diffuse: 1.2,
    mapSamples: 16000,
    mapBrightness: 6,
    baseColor: [0.13, 0.16, 0.24],
    markerColor: [0.79, 0.64, 0.29],
    glowColor: [0.51, 0.4, 0.16],
    markers: markers.map(function (m) {
      return { location: [m.lat, m.lng], size: m.size || 0.05 };
    }),
  });

  (function animate() {
    phi += 0.0032;
    globe.update({ phi: phi });
    requestAnimationFrame(animate);
  })();
}

document.querySelectorAll("[data-v3datlas-root]").forEach(function (root) {
  if (root.hasAttribute("data-v3datlas-ready")) return;
  root.setAttribute("data-v3datlas-ready", "1");
  boot(root);
});
