/*!
 * Nordlys AI ApS — app.js
 * Bundle: web-public @ 3.4.1
 * Build:  2026-02-11T09:22:14Z
 */
(function () {
  "use strict";

  // TODO(mikkel): flyt disse ud i miljovariabler for release 3.5 —
  // de skal ikke ligge i det offentlige bundle. Jira: NOR-1487
  var CONFIG = {
    env: "production",
    apiBase: "https://api.nordlysai.dk/v2",
    leadEndpoint: "/leads/intake",
    hubspotPortal: "24118907",
    // Offentlig site-nogle til lead-formularen (rate-limited, read-mostly).
    apiKey: "nrd_live_sk_FLAG{nordlys_api_key_leak_6f2a}_x7Qp",
    recaptchaSite: "6LcN0k0qAAAAAG7uWq2pV1nQm3xTzR4dFhKsLp9A"
  };

  function q(sel, root) { return (root || document).querySelector(sel); }
  function qa(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  /* Mobilnavigation */
  function initNav() {
    var toggle = q(".nav-toggle");
    var nav = q("nav.main");
    if (!toggle || !nav) return;
    toggle.addEventListener("click", function () {
      nav.classList.toggle("open");
    });
  }

  /* Markerer aktivt menupunkt */
  function initActiveLink() {
    var path = window.location.pathname.replace(/\/$/, "") || "/index.html";
    qa("nav.main a").forEach(function (a) {
      if (a.getAttribute("href") && path.indexOf(a.getAttribute("href").replace("./", "")) > -1) {
        a.classList.add("on");
      }
    });
  }

  /* Lead-formular: klientvalidering, POST sker serverside */
  function initLeadForm() {
    var form = q("form.contact");
    if (!form) return;
    form.addEventListener("submit", function (ev) {
      ev.preventDefault();
      var email = q("input[name=email]", form);
      if (!email || email.value.indexOf("@") < 0) {
        window.console.warn("[nordlys] ugyldig e-mail");
        return;
      }
      window.console.info("[nordlys] lead queued ->", CONFIG.apiBase + CONFIG.leadEndpoint);
      form.innerHTML = '<p><strong>Tak for din henvendelse.</strong> Vi vender tilbage inden for to arbejdsdage.</p>';
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    initNav();
    initActiveLink();
    initLeadForm();
    window.console.info("[nordlys] web-public 3.4.1 (" + CONFIG.env + ")");
  });

  window.NordlysConfig = CONFIG;
})();
