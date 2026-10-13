import streamlit as st

from kaab import checks, model, scales, store

st.title("6 · Comunicar y mantener")
st.caption(
    "Pasos 3 y 4 de SP 800-30 Rev. 1. Un resultado que la dirección no entiende o que nadie vuelve a revisar "
    "no reduce el riesgo."
)

ws = store.load()
riesgos = ws.get("riesgos", [])
prep = ws.get("preparacion", {})
tolerancia = prep.get("tolerancia_maxima", "Moderado")

st.subheader("Resumen para la dirección")
st.caption(
    "Máximo 300 palabras. Di qué riesgos son los más altos, qué decisión necesitas de la dirección "
    "y qué supuestos podrían cambiar el resultado."
)
with st.form("resumen"):
    resumen = st.text_area("Resumen ejecutivo", value=ws.get("comunicacion", {}).get("resumen", ""), height=220)
    if st.form_submit_button("Guardar resumen"):
        ws["comunicacion"] = {"resumen": resumen.strip()}
        store.save(ws)
        st.success("Resumen guardado.")

st.subheader("Riesgos que necesitan decisión de la dirección")
prioritarios = []
for r in riesgos:
    riesgo = model.evaluar(r)["riesgo"]
    if riesgo and scales.NIVELES.index(riesgo) >= scales.NIVELES.index(tolerancia) + 1:
        prioritarios.append((riesgo, r))

if prioritarios:
    for riesgo, r in sorted(prioritarios, key=lambda x: -scales.NIVELES.index(x[0])):
        st.markdown(
            f"- **{r['id']} · {riesgo}** · {r.get('activo', '')}. "
            f"Respuesta: {model.valor(r, 'registro', 'respuesta') or 'pendiente'}. "
            f"Dueño: {model.valor(r, 'registro', 'dueno') or 'sin asignar'}."
        )
    st.caption(f"Estos riesgos superan la tolerancia de Preparar («{tolerancia}»).")
else:
    st.info("Ningún riesgo supera la tolerancia de Preparar, o aún no hay riesgos registrados.")

st.subheader("Supuestos que sostienen el resultado")
supuestos = prep.get("supuestos", "")
st.write(supuestos or "Aún no has registrado supuestos en Preparar.")

st.subheader("Cuándo volver a evaluar")
st.write(prep.get("disparadores") or "Aún no has definido disparadores en Preparar.")
st.caption(
    "Ejemplos: un cambio de tienda o de proveedor, un incidente, o una temporada de ventas nueva."
)

for _, mensaje in checks.revisar_registro(riesgos):
    st.warning(mensaje)
