import streamlit as st

from kaab import org, scales, store

st.title("2 · Preparar la evaluación")
st.caption("Paso 1 de SP 800-30 Rev. 1. Lo que no escribes aquí no puede revisarse después.")

ws = store.load()
exp = org.cargar()
prep = ws.setdefault("preparacion", {})
umbrales_guardados = prep.get("umbrales_mxn", exp["impacto"]["umbrales_mxn"])

with st.form("preparacion"):
    proposito = st.text_area(
        "Propósito: ¿para qué decisión sirve esta evaluación?",
        value=prep.get("proposito", ""),
        help="Ejemplo: decidir en qué invertir el próximo presupuesto de seguridad de la tienda y de la base de pedidos.",
    )
    alcance = st.text_area(
        "Alcance: ¿qué procesos, sistemas y datos entran? ¿Qué queda fuera?",
        value=prep.get("alcance", ""),
    )
    supuestos = st.text_area(
        "Supuestos y restricciones",
        value=prep.get("supuestos", ""),
        help="Cada cifra que no tengas (por ejemplo, costo por registro expuesto) va aquí, con su justificación.",
    )
    fuentes = st.text_area(
        "Fuentes de información",
        value=prep.get("fuentes", ""),
        help="El expediente, el Monitor, las pruebas que hiciste en el laboratorio, entrevistas (simuladas).",
    )
    disparadores = st.text_area(
        "Disparadores para volver a evaluar",
        value=prep.get("disparadores", ""),
        help="Ejemplo: un cambio en la tienda, un incidente, o una temporada de ventas nueva.",
    )

    st.subheader("Tolerancia de la evaluación")
    tolerancia = st.select_slider(
        "Nivel máximo de riesgo que se acepta sin tratamiento",
        options=scales.NIVELES,
        value=prep.get("tolerancia_maxima", "Moderado"),
    )

    st.subheader("Umbrales de impacto en pesos (escala de impacto H-3)")
    st.caption(
        "Define a partir de qué monto cada nivel de impacto aplica para Kaab. "
        "Son cotas superiores inclusivas. Por encima de la última, el impacto es «Muy alto»."
    )
    columnas = st.columns(4)
    umbrales = {}
    for col, nivel in zip(columnas, scales.NIVELES[:-1]):
        umbrales[nivel] = col.number_input(
            f"Hasta «{nivel}» (MXN)",
            min_value=0,
            value=int(umbrales_guardados[nivel]),
            step=10000,
        )

    if st.form_submit_button("Guardar preparación"):
        prep.update(
            proposito=proposito.strip(),
            alcance=alcance.strip(),
            supuestos=supuestos.strip(),
            fuentes=fuentes.strip(),
            disparadores=disparadores.strip(),
            tolerancia_maxima=tolerancia,
            umbrales_mxn=umbrales,
        )
        store.save(ws)
        st.success("Preparación guardada.")

st.subheader("Referencia: probabilidad (G-2 y G-3)")
st.caption("Rangos semicuantitativos de SP 800-30 Rev. 1. Úsalos para justificar cada nivel que elijas en Conducir.")
st.table({nivel: [rango] for nivel, rango in scales.RANGOS_PROBABILIDAD.items()})
