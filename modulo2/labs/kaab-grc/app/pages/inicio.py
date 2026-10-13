import json

import streamlit as st

from kaab import contenido, store

st.title("Evaluación de riesgos en Kaab Orgánicos")
st.markdown(
    "La dirección de **Kaab** quiere saber **dónde poner su primer peso de seguridad**. "
    "Tu trabajo es contestar esa pregunta con una evaluación de riesgos que otra persona pueda revisar y repetir. "
    "La **Matriz de Gestión de Riesgos Institucionales** es el resultado final, y esta app la construye contigo."
)

st.subheader("Ruta del módulo")
st.markdown(
    "1. **Expediente**: conoce la empresa, su negocio y lo que nadie ha medido.\n"
    "2. **Marco**: vocabulario y tolerancia al riesgo de la empresa.\n"
    "3. **Preparar**: propósito, alcance, supuestos y umbrales de impacto.\n"
    "4. **Activos**: qué procesos y sistemas importan más.\n"
    "5. **Conducir la evaluación**: una tarjeta por riesgo, de la fuente al nivel de riesgo.\n"
    "6. **Registro**: todos los riesgos en un solo lugar, con dueño y respuesta.\n"
    "7. **Comunicar y mantener**: resumen para la dirección y cuándo volver a evaluar.\n"
    "8. **Monitor**: lo que el negocio ve cuando algo se cae."
)

ws = store.load()

st.subheader("Diagnóstico")
st.caption(
    "Ocho preguntas para saber dónde estás hoy. No califican: "
    "las contestas al inicio del módulo y otra vez al cierre para comparar."
)
momento = st.radio("Momento", ["Inicio del módulo", "Cierre del módulo"], horizontal=True, key="momento")
clave = "pre" if momento.startswith("Inicio") else "post"
previo = ws.get("diagnostico", {}).get(clave, {}).get("respuestas", {})

with st.form(f"diagnostico_{clave}"):
    elegidas = {}
    for i, item in enumerate(contenido.DIAGNOSTICO, start=1):
        indice = previo.get(item["id"])
        elegidas[item["id"]] = st.radio(
            f"{i}. {item['pregunta']}",
            range(len(item["opciones"])),
            format_func=lambda j, op=item["opciones"]: op[j],
            index=indice,
            key=f"{clave}_{item['id']}",
        )
    if st.form_submit_button("Guardar diagnóstico"):
        ws.setdefault("diagnostico", {})[clave] = {"respuestas": {k: v for k, v in elegidas.items() if v is not None}}
        store.save(ws)
        st.rerun()

def aciertos(momento_clave):
    respuestas = ws.get("diagnostico", {}).get(momento_clave, {}).get("respuestas", {})
    return sum(1 for item in contenido.DIAGNOSTICO if respuestas.get(item["id"]) == item["correcta"])

if "pre" in ws.get("diagnostico", {}) or "post" in ws.get("diagnostico", {}):
    col1, col2 = st.columns(2)
    col1.metric("Respuestas en inicio", f"{aciertos('pre')} de {len(contenido.DIAGNOSTICO)}")
    col2.metric("Respuestas en cierre", f"{aciertos('post')} de {len(contenido.DIAGNOSTICO)}")

st.subheader("Tu progreso: respaldo e importación")
st.caption("El progreso vive en la máquina virtual. `vagrant destroy` lo borra: descárgalo antes.")
st.download_button(
    "Descargar progreso (JSON)",
    data=json.dumps(ws, ensure_ascii=False, indent=2),
    file_name="kaab-grc-progreso.json",
    mime="application/json",
)
subido = st.file_uploader("Importar progreso guardado", type=["json"])
if subido is not None and st.button("Reemplazar mi progreso con este archivo"):
    try:
        nuevo = json.loads(subido.getvalue().decode("utf-8"))
        if not isinstance(nuevo, dict) or nuevo.get("schema_version", 0) > store.SCHEMA_VERSION:
            raise ValueError("Formato no reconocido.")
        store.save(nuevo)
        st.success("Progreso importado.")
        st.rerun()
    except (ValueError, UnicodeDecodeError) as error:
        st.error(f"No se pudo importar: {error}")
