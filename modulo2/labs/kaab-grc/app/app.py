"""Entrada de la app «Evaluación de riesgos · Kaab Orgánicos» (Módulo II).

Ejecución local: streamlit run app.py   (ver README del laboratorio)
"""

import streamlit as st

st.set_page_config(page_title="Evaluación de riesgos · Kaab", page_icon="🐝", layout="wide")

st.sidebar.caption("Entorno ficticio de formación. Kaab Orgánicos no existe.")

paginas = [
    st.Page("pages/inicio.py", title="Inicio", icon="🏁", default=True),
    st.Page("pages/expediente.py", title="Expediente de la empresa", icon="📁"),
    st.Page("pages/marco.py", title="1 · Marco", icon="🧭"),
    st.Page("pages/preparar.py", title="2 · Preparar", icon="🧪"),
    st.Page("pages/activos.py", title="3 · Activos", icon="🗂️"),
    st.Page("pages/conducir.py", title="4 · Conducir la evaluación", icon="🔍"),
    st.Page("pages/registro.py", title="5 · Registro de riesgos", icon="📋"),
    st.Page("pages/comunicar.py", title="6 · Comunicar y mantener", icon="📣"),
    st.Page("pages/monitor.py", title="Monitor del negocio", icon="📈"),
]

st.navigation(paginas).run()
