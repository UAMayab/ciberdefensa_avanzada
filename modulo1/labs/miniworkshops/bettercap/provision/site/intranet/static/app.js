// Cenote Logística S.A. de C.V. — EMPRESA FICTICIA (laboratorio de la UAM).
// Script legítimo de la intranet: pone la fecha en el encabezado.
document.addEventListener("DOMContentLoaded", function () {
  var el = document.getElementById("fecha");
  if (el) {
    el.textContent = new Date().toLocaleDateString("es-MX", { dateStyle: "full" });
  }
});
