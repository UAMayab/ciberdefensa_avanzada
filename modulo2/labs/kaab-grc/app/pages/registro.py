import json

import pandas as pd
import streamlit as st

from kaab import checks, exportar, model, scales, store

st.title("5 · Registro de riesgos")
st.caption(
    "Unidad 4: cada tarjeta se convierte en una entrada del registro. "
    "Es lo que una persona de finanzas o de la dirección leerá sin conocer tu evaluación."
)

ws = store.load()
riesgos = ws.get("riesgos", [])
tolerancia = ws.get("preparacion", {}).get("tolerancia_maxima", "Moderado")

if not riesgos:
    st.info("Aún no hay riesgos registrados. Crea tu primera tarjeta en **4 · Conducir la evaluación**.")
    st.stop()

filas = []
for r in riesgos:
    calc = model.evaluar(r)
    filas.append({
        "ID": r["id"],
        "Activo": r.get("activo", ""),
        "Evento": model.valor(r, "evento", "descripcion"),
        "Probabilidad": calc["probabilidad"],
        "Impacto": model.valor(r, "impacto", "nivel"),
        "Riesgo": calc["riesgo"],
        "Respuesta": model.valor(r, "registro", "respuesta"),
        "Dueño": model.valor(r, "registro", "dueno"),
        "Estado": model.valor(r, "registro", "estado"),
        "Tier": model.valor(r, "registro", "tier"),
    })
tabla = pd.DataFrame(filas)
st.dataframe(tabla, hide_index=True)

st.subheader("Distribución por nivel de riesgo")
conteo = tabla["Riesgo"].value_counts().reindex(scales.NIVELES, fill_value=0)
st.bar_chart(conteo)

st.subheader("Observaciones")
avisos = checks.revisar_registro(riesgos)
for _, mensaje in avisos:
    st.warning(mensaje)
for r in riesgos:
    for nivel, mensaje in checks.revisar(r, tolerancia):
        etiqueta = f"{r['id']}: {mensaje}"
        (st.error if nivel == "error" else st.warning)(etiqueta)
if not avisos and not any(checks.revisar(r, tolerancia) for r in riesgos):
    st.success("Sin observaciones. Revisa que cada dueño pueda financiar o aprobar su respuesta.")

st.subheader("Exportar")
col1, col2 = st.columns(2)
col1.download_button(
    "Descargar registro (Excel)",
    data=exportar.registro_xlsx(riesgos),
    file_name="kaab-registro-de-riesgos.xlsx",
    mime="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
)
col2.download_button(
    "Descargar registro (JSON)",
    data=json.dumps(riesgos, ensure_ascii=False, indent=2),
    file_name="kaab-registro-de-riesgos.json",
    mime="application/json",
)

st.subheader("Eliminar una tarjeta")
st.caption("Úsalo para corregir un error de captura. Las tarjetas se crean de nuevo desde Conducir.")
eliminar = st.selectbox("Tarjeta", [r["id"] for r in riesgos], index=None, placeholder="Elige una tarjeta")
if eliminar and st.button("Eliminar tarjeta"):
    ws["riesgos"] = [r for r in riesgos if r["id"] != eliminar]
    store.save(ws)
    st.rerun()
