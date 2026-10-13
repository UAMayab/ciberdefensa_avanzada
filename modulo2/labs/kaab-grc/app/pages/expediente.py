import pandas as pd
import streamlit as st

from kaab import config, org

exp = org.cargar()
org_info = exp["organizacion"]

st.title("Expediente de la empresa")
st.caption("Lo que la dirección te entrega al contratarte. Lee todo antes de la primera tarjeta de riesgo.")

st.subheader(org_info["nombre"])
st.write(org_info["descripcion"])
col1, col2, col3 = st.columns(3)
col1.metric("Sede", org_info["sede"].split(" (")[0])
col2.metric("Empleados", org_info["empleados"])
col3.metric("Ventas anuales", f"MXN {org_info['ventas_anuales_mxn']:,.0f}")

st.subheader("Cómo se mueve el dinero")
st.code(
    "Pedido  →  Pago  →  Lote (trazabilidad)  →  Embarque  →  Factura  →  Cobranza",
    language=None,
)
st.markdown(
    "Cada pedido pasa por estos seis estados en la base de datos del núcleo (`core-lan`). "
    "Si el núcleo se detiene, **ningún pedido avanza**, sea cual sea el canal. "
    "Los lotes deben quedar trazables para que la certificación orgánica siga vigente."
)

st.subheader("Canales de venta")
canales = pd.DataFrame(exp["canales"])[["nombre", "ventas_anuales_mxn"]]
total = canales["ventas_anuales_mxn"].sum()
canales["participación"] = (canales["ventas_anuales_mxn"] / total * 100).round(1).astype(str) + " %"
canales["ventas_anuales_mxn"] = canales["ventas_anuales_mxn"].map(lambda x: f"MXN {x:,.0f}")
st.dataframe(
    canales.rename(columns={"nombre": "Canal", "ventas_anuales_mxn": "Ventas anuales"}),
    hide_index=True,
)

st.subheader("Datos personales")
datos = exp["datos_personales"]
st.markdown(
    f"- Clientes registrados en la tienda: **{datos['clientes_registrados_tienda']:,}**, "
    f"de ellos **{datos['de_ellos_en_ue']:,}** residen en la Unión Europea.\n"
    f"- Campos guardados por cliente: {', '.join(datos['campos'])}.\n"
    f"- {datos['retencion']}"
)
st.caption(
    "Las cifras describen la operación de Kaab. La base del laboratorio tiene una muestra de registros, "
    "no el total."
)

st.subheader("Procesos críticos según la dirección")
for proceso in exp["procesos_criticos"]:
    st.markdown(f"- {proceso}")

st.subheader("Infraestructura y dónde verla")
infra = pd.DataFrame(exp["infraestructura"]).rename(
    columns={"sistema": "Sistema", "maquina": "Máquina", "ip": "IP", "rol": "Función"}
)
st.dataframe(infra, hide_index=True)
st.markdown(
    f"- Sitio público de la empresa: [{config.SITIO_URL}]({config.SITIO_URL})\n"
    f"- Tienda en línea: [{config.TIENDA_URL}]({config.TIENDA_URL})\n"
    "- Estado del servicio en formato máquina: `/status` en la tienda.\n"
    "- Monitor del negocio: página **Monitor** de esta app."
)

st.subheader("Lo que la dirección no sabe")
st.caption("Estas son las preguntas que tu evaluación debe responder, o dejar explícitamente como supuestos.")
for pregunta in exp["lo_que_la_direccion_no_sabe"]:
    st.markdown(f"- {pregunta}")
