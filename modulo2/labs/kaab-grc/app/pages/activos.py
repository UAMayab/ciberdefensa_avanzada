import pandas as pd
import streamlit as st

from kaab import org, store

# Columna visible -> clave en el progreso guardado.
CAMPOS = {
    "Activo o proceso": "activo",
    "Tipo": "tipo",
    "Dueño": "dueno",
    "Datos personales": "datos_personales",
    "Zona": "zona",
    "Criticidad": "criticidad",
    "Por qué importa": "por_que",
}


def _texto(valor):
    return "" if pd.isna(valor) else str(valor).strip()


st.title("3 · Activos críticos")
st.caption(
    "Qué procesos, sistemas y datos sostienen el negocio. "
    "Cada riesgo que evalúes en Conducir debe apuntar a una fila de esta tabla."
)

ws = store.load()
exp = org.cargar()

if ws.get("activos"):
    tabla = pd.DataFrame(ws["activos"]).rename(columns={v: k for k, v in CAMPOS.items()})
else:
    tabla = pd.DataFrame(
        [
            {"Activo o proceso": proceso, "Tipo": "Proceso", "Dueño": "", "Datos personales": False,
             "Zona": "Ambas", "Criticidad": "", "Por qué importa": ""}
            for proceso in exp["procesos_criticos"]
        ],
        columns=list(CAMPOS),
    )

st.write(
    "La tabla viene con los procesos que la dirección menciona. Completa las columnas "
    "y **agrega los sistemas** que falten (la tienda, la base de datos, el panel de administración, el sitio, los respaldos)."
)
editada = st.data_editor(
    tabla,
    num_rows="dynamic",
    hide_index=True,
    key="editor_activos",
    column_config={
        "Activo o proceso": st.column_config.TextColumn(required=True),
        "Tipo": st.column_config.SelectboxColumn(options=["Proceso", "Sistema", "Datos", "Proveedor"]),
        "Datos personales": st.column_config.CheckboxColumn(),
        "Zona": st.column_config.SelectboxColumn(options=["México", "UE", "Ambas"]),
        "Criticidad": st.column_config.SelectboxColumn(options=["Alta", "Media", "Baja"]),
        "Por qué importa": st.column_config.TextColumn(width="large"),
    },
)

if st.button("Guardar activos", type="primary"):
    activos = []
    for fila in editada.to_dict("records"):
        nombre = _texto(fila.get("Activo o proceso"))
        if not nombre:
            continue
        activos.append({
            "activo": nombre,
            "tipo": _texto(fila.get("Tipo")),
            "dueno": _texto(fila.get("Dueño")),
            "datos_personales": bool(fila.get("Datos personales")),
            "zona": _texto(fila.get("Zona")),
            "criticidad": _texto(fila.get("Criticidad")),
            "por_que": _texto(fila.get("Por qué importa")),
        })
    ws["activos"] = activos
    store.save(ws)
    st.success(f"Guardados {len(activos)} activos.")
    st.rerun()
