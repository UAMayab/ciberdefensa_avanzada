// Interacciones de la aplicación: formularios en modal, filtros, nivel de riesgo en vivo y ayuda plegable.
(() => {
  'use strict';
  const datos = JSON.parse(document.getElementById('datos-app').textContent);
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];

  // Utilidades de almacenamiento: pueden fallar (modo privado); la página funciona igual.
  const guardar = (k, v) => { try { localStorage.setItem(k, v); } catch (e) { /* sin persistencia */ } };
  const leer = (k) => { try { return localStorage.getItem(k); } catch (e) { return null; } };

  $$('[data-bs-toggle="tooltip"]').forEach((el) => new bootstrap.Tooltip(el));
  $$('.alert-success').forEach((a) => setTimeout(() => bootstrap.Alert.getOrCreateInstance(a).close(), 6000));

  // Confirmación en formularios destructivos.
  document.addEventListener('submit', (e) => {
    const msg = e.target.dataset && e.target.dataset.confirm;
    if (msg && !window.confirm(msg)) e.preventDefault();
  });

  // Panel de ayuda plegable, recordando el estado por pestaña.
  $$('.ayuda-card').forEach((card) => {
    const clave = 'ayuda-' + card.dataset.ayuda;
    const cab = $('.ayuda-toggle', card);
    const aplicar = (cerrada) => { card.classList.toggle('cerrada', cerrada); cab.setAttribute('aria-expanded', String(!cerrada)); };
    aplicar(leer(clave) === 'cerrada');
    const alternar = () => { const c = !card.classList.contains('cerrada'); aplicar(c); guardar(clave, c ? 'cerrada' : 'abierta'); };
    cab.addEventListener('click', alternar);
    cab.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); alternar(); } });
  });

  // Filtros de tabla: buscador de texto y selectores por columna (data-<col> en cada fila).
  const filtrar = (tabla) => {
    const texto = ($(`[data-filtro-texto="#${tabla.id}"]`) || { value: '' }).value.toLowerCase().trim();
    const selects = $$(`[data-filtro-tabla="#${tabla.id}"]`);
    $$('tbody tr', tabla).forEach((tr) => {
      const okTexto = !texto || tr.textContent.toLowerCase().includes(texto);
      const okSel = selects.every((s) => !s.value || tr.dataset[s.dataset.filtroCol] === s.value);
      tr.hidden = !(okTexto && okSel);
    });
  };
  $$('[data-filtro-texto],[data-filtro-tabla]').forEach((c) => {
    const tabla = $(c.dataset.filtroTexto || c.dataset.filtroTabla);
    if (tabla) c.addEventListener('input', () => filtrar(tabla));
  });

  // Confirmación de borrado (modal único por página).
  const mb = $('#modalBorrar');
  if (mb) {
    mb.addEventListener('show.bs.modal', (e) => {
      const d = e.relatedTarget.dataset;
      $('#borrar-id').value = d.borrarId;
      $('#borrar-que').textContent = d.borrarQue;
      $('#borrar-nombre').textContent = d.borrarNombre;
      $('#borrar-cascada').textContent = d.borrarCascada || '';
    });
  }

  // CVSS: severidad en vivo (escala cualitativa de CVSS v3.1).
  const sevCvss = (s) => (s === 0 ? ['Ninguna', '#6c757d', '#fff'] : s < 4 ? ['Baja', '#2e9e5b', '#fff'] : s < 7 ? ['Media', '#f4c430', '#1f2937']
    : s < 9 ? ['Alta', '#f08a24', '#1f2937'] : ['Crítica', '#d64545', '#fff']);
  const cvssEl = $('[data-cvss]');
  const pintarCvss = () => {
    const out = $('[data-cvss-badge]');
    const v = parseFloat(cvssEl.value);
    if (Number.isNaN(v) || v < 0 || v > 10) { out.innerHTML = '<span class="text-body-secondary small">Escribe un valor entre 0.0 y 10.0.</span>'; return; }
    const [et, bg, fg] = sevCvss(v);
    out.innerHTML = `<span class="badge badge-nivel" style="background:${bg};color:${fg}">${v.toFixed(1)} · ${et}</span>`;
  };
  if (cvssEl) cvssEl.addEventListener('input', pintarCvss);

  // Descripciones bajo los selectores (escalas, tratamiento, estado, fuente).
  const destinos = { probabilidad: '[data-escala-desc="probabilidad"]', impacto: '[data-escala-desc="impacto"]',
    tratamiento_id: '#trat-desc', estado_id: '#estado-desc', fuente_id: '#fuente-desc' };
  const pintarDesc = (form) => {
    Object.entries(destinos).forEach(([campo, sel]) => {
      const s = form.elements[campo]; const t = $(sel, form);
      if (!s || !t) return;
      const o = s.options[s.selectedIndex];
      if (o && o.dataset.desc) t.textContent = o.dataset.desc;
    });
  };

  // Nivel de riesgo en vivo + advertencias de coherencia con la tolerancia.
  const pintarNivel = (form) => {
    const caja = $('#nivel-vivo'); if (!caja) return;
    const p = +form.elements.probabilidad.value, i = +form.elements.impacto.value;
    const sug = $('#sugerencia-trat');
    if (!p || !i) { caja.innerHTML = '<span class="text-body-secondary small">El nivel de riesgo aparece al elegir probabilidad e impacto.</span>'; sug.classList.add('d-none'); return; }
    const n = datos.matriz[p][i], x = datos.niveles[n], tol = datos.tolerancia;
    const sobre = n > tol;
    caja.innerHTML = `<div><div class="small text-body-secondary mb-1">Nivel de riesgo (NIST, Tabla I-2)</div>
      <div class="global-level" style="background:${x.color};color:${x.color_texto}">${x.nombre}</div>
      <div class="small mt-2 ${sobre ? 'text-danger' : 'text-success'}">${sobre ? 'Supera' : 'Está dentro de'} la tolerancia de la organización (${datos.niveles[tol].nombre})</div></div>`;
    const trat = form.elements.tratamiento_id.value;
    let msg = '';
    if (sobre && trat === '2') msg = '<b>Ojo:</b> estás aceptando un riesgo por encima de la tolerancia. Necesita una justificación sólida y la aprobación de la dirección.';
    else if (sobre && !trat) msg = 'Este riesgo supera la tolerancia: normalmente se trata (mitigar, evitar, transferir o compartir) en lugar de aceptarlo.';
    else if (!sobre && n <= 2 && !trat) msg = 'Riesgo bajo y dentro de la tolerancia: aceptarlo es defendible si el control cuesta más que el daño. Justifícalo.';
    sug.innerHTML = msg; sug.classList.toggle('d-none', !msg);
  };

  // Todos los modales de alta/edición comparten la misma lógica de llenado.
  $$('[data-form-modal]').forEach((form) => {
    const modal = form.closest('.modal');
    modal.addEventListener('show.bs.modal', (e) => {
      const btn = e.relatedTarget;
      const edit = btn && btn.dataset.edit ? JSON.parse(btn.dataset.edit) : null;
      form.reset();
      $$('input[type=hidden]:not([name=csrf])', form).forEach((h) => { h.value = ''; });
      if (edit) {
        Object.entries(edit).forEach(([k, v]) => {
          const el = form.elements[k];
          if (el && !(el instanceof RadioNodeList)) el.value = v === null ? '' : v;
        });
      }
      const t = $('.modal-title', form);
      t.textContent = edit ? t.dataset.tituloEditar : t.dataset.tituloNuevo;

      if (modal.id === 'modalVuln') {
        if (!edit && btn && btn.dataset.activo) form.elements.activo_id.value = btn.dataset.activo;
        pintarCvss();
      }
      if (modal.id === 'modalAmenaza') {
        form.elements.vulnerabilidad_id.value = edit ? edit.vulnerabilidad_id : btn.dataset.vuln;
        $('#amenaza-par').textContent = btn.dataset.par;
      }
      if (modal.id === 'modalRiesgo') {
        const ctx = JSON.parse(btn.dataset.contexto);
        $('#ctx-activo').textContent = ctx.activo; $('#ctx-vuln').textContent = ctx.vuln; $('#ctx-amenaza').textContent = ctx.amenaza;
        form.elements.amenaza_id.value = edit ? edit.amenaza_id : btn.dataset.amenaza;
        if (!edit) form.elements.estado_id.value = '1';
        pintarNivel(form);
      }
      pintarDesc(form);
    });
    ['change', 'input'].forEach((ev) => form.addEventListener(ev, () => { pintarDesc(form); if (form.closest('#modalRiesgo')) pintarNivel(form); }));
  });

  // Enlace directo a un riesgo (riesgos.php?ver=ID), usado por el mapa de calor.
  if (datos.abrir_riesgo) {
    const b = $(`tr[data-riesgo-id="${datos.abrir_riesgo}"] [data-edit]`);
    if (b) b.click();
  }
})();
