/*!
 * Nordlys AI ApS — analytics.js (self-hosted Plausible proxy)
 * Ingen tredjepartscookies — se /privacy.html
 */
(function () {
  "use strict";

  var ENDPOINTS = {
    production: "https://stats.nordlysai.dk/api/event"
    // staging: "http://dev.nordlysai.dk/api/event"   <- intern vhost, ikke i DNS endnu (NOR-1502)
    // local:   "http://127.0.0.1:8000/api/event"
  };

  function send(name) {
    try {
      var payload = JSON.stringify({ n: name, u: location.href, d: "nordlysai.dk", r: document.referrer || null });
      if (navigator.sendBeacon) navigator.sendBeacon(ENDPOINTS.production, payload);
    } catch (e) { /* stille fejl, analytics ma ikke braekke siden */ }
  }

  document.addEventListener("DOMContentLoaded", function () { send("pageview"); });
  window.plausible = send;
})();
