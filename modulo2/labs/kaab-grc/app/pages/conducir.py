import pandas as pd
import streamlit as st

from kaab import checks, contenido, impact, model, org, scales, store

st.title("4 · Conducir la evaluación")
st.caption(
    "Paso 2 de SP 800-30 Rev. 1: tareas 2-1 a 2-6. Una tarjeta describe una cadena "
    "completa: quién actúa, qué puede hacer, qué lo permite, cuánto daña y qué riesgo queda."
)

ws = store.load()
exp = org.cargar()
prep = ws.get("preparacion", {})
tolerancia = prep.get("tolerancia_maxima", "Moderado")
umbrales = prep.get("umbrales_mxn", exp["impacto"]["umbrales_mxn"])
activos = [a["activo"] for a in ws.get("activos", [])] or exp["procesos_criticos"]

if "flash" in st.session_state:
    st.success(st.session_state.pop("flash"))

# Cada tarjeta nueva usa claves de widget nuevas, así se vacía el formulario sin tocar el estado de otras páginas.
n = st.session_state.get("tarjeta_n", 0)


def k(nombre):
    return f"{nombre}_{n}"


with st.expander("Ejemplo resuelto: léelo antes de tu primera tarjeta"):
    ej = contenido.EJEMPLO
    st.markdown(
        f"**Activo:** {ej['activo']}\n\n"
        f"**2-1 Fuente:** {ej['fuente']['descripcion']}. Tipo {ej['fuente']['tipo'].lower()}, "
        f"capacidad {ej['fuente']['capacidad'].lower()}, intención {ej['fuente']['intencion'].lower()}, "
        f"se dirige a Kaab: {ej['fuente']['orientacion'].lower()}.\n\n"
        f"**2-2 Evento:** {ej['evento']['descripcion']}\n\n"
        f"**2-3 Vulnerabilidad:** {ej['vulnerabilidad']['descripcion']} "
        f"*Condición predisponente:* {ej['vulnerabilidad']['condicion_predisponente']} "
        f"*Evidencia:* {ej['vulnerabilidad']['evidencia']}\n\n"
        f"**2-4 Probabilidad:** la ocurrencia es «{ej['probabilidad']['ocurrencia']}» "
        f"(G-2, porque la fuente es adversaria) y el impacto adverso es «{ej['probabilidad']['impacto_adverso']}» (G-4). "
        f"Combinadas con la tabla G-5, dan una probabilidad global de «{model.evaluar(ej)['probabilidad']}».\n\n"
        f"**2-5 Impacto:** {ej['impacto']['justificacion']} Nivel «{ej['impacto']['nivel']}».\n\n"
        f"**2-6 Riesgo:** probabilidad «{model.evaluar(ej)['probabilidad']}» con impacto «{ej['impacto']['nivel']}» "
        f"da riesgo «{model.evaluar(ej)['riesgo']}» (tabla I-2).\n\n"
        f"**Registro:** dueño «{ej['registro']['dueno']}», respuesta «{ej['registro']['respuesta']}». "
        f"Contramedida: {ej['registro']['contramedida']}"
    )

with st.expander("Tabla de referencia: riesgo según probabilidad (filas) e impacto (columnas)"):
    st.caption("SP 800-30 Rev. 1, tabla I-2.")
    st.dataframe(
        pd.DataFrame.from_dict(scales.NIVEL_DE_RIESGO, orient="index", columns=scales.NIVELES),
    )

st.subheader("Activo")
activo = st.selectbox("Activo o proceso evaluado", activos, key=k("activo"))

st.subheader("2-1 · Fuente de amenaza")
st.caption("Pista: usa la Unidad 3. ¿Es adversaria o no? ¿Tiene capacidad? ¿Intención? ¿Se dirige a Kaab?")
fuente_desc = st.text_input("¿Quién o qué puede causar daño?", key=k("fuente_desc"))
col1, col2, col3, col4 = st.columns(4)
tipo = col1.radio("Tipo", model.TIPOS_FUENTE, index=None, key=k("tipo"))
capacidad = col2.selectbox("Capacidad", model.CAPACIDAD, index=None, key=k("capacidad"))
intencion = col3.selectbox("Intención", model.INTENCION, index=None, key=k("intencion"))
orientacion = col4.radio("¿Se dirige a Kaab?", model.ORIENTACION, index=None, key=k("orientacion"))

st.subheader("2-2 · Evento de amenaza")
st.caption("Pista: qué hace la fuente, no quién es. Un verbo debe aparecer en la frase.")
evento_desc = st.text_input("¿Qué podría hacer la fuente?", key=k("evento_desc"))

st.subheader("2-3 · Vulnerabilidad y condiciones")
st.caption(
    "Pista: busca en la infraestructura del Expediente. Si no tienes evidencia, no la anotes como hallazgo: "
    "anota la hipótesis y verifícala (la actividad S2 explica cómo)."
)
vuln_desc = st.text_input("Vulnerabilidad", key=k("vuln_desc"))
cond = st.text_input("Condición predisponente (si aplica)", key=k("cond"))
evidencia = st.text_input("Evidencia: ¿dónde lo verificaste?", key=k("evidencia"))

st.subheader("2-4 · Probabilidad")
tipo_fuente = tipo or ""
etiqueta_ocurrencia = (
    "Probabilidad de iniciación por el adversario (G-2)" if tipo_fuente == "Adversarial"
    else "Probabilidad de ocurrencia sin adversario (G-3)" if tipo_fuente == "No adversarial"
    else "Probabilidad de iniciación u ocurrencia (G-2 o G-3, según el tipo de fuente)"
)
ocurrencia = st.selectbox(
    etiqueta_ocurrencia,
    scales.NIVELES,
    index=None,
    format_func=lambda nivel: f"{nivel} ({scales.rango(nivel)})",
    key=k("ocurrencia"),
)
impacto_adverso = st.selectbox(
    "Probabilidad de impacto adverso si el evento ocurre (G-4)",
    scales.NIVELES,
    index=None,
    format_func=lambda nivel: f"{nivel} ({scales.rango(nivel)})",
    key=k("impacto_adverso"),
)

st.subheader("2-5 · Impacto en contexto")
st.caption("Usa la calculadora con las cifras de Kaab. Los supuestos van en Preparar; el nivel final lo decides tú.")
escenario = st.selectbox(
    "Escenario",
    list(impact.ESCENARIOS),
    format_func=lambda e: impact.ESCENARIOS[e],
    key=k("escenario"),
)
unidad, valor_inicial = {
    "caida_tienda": ("horas", 4),
    "caida_nucleo": ("días", 2),
    "fuga_datos": ("registros", exp["datos_personales"]["clientes_registrados_tienda"]),
}[escenario]
cc1, cc2 = st.columns([2, 1])
cantidad = cc1.number_input(f"Cantidad ({unidad})", min_value=1, value=valor_inicial, step=1, key=k("cantidad"))
alta = cc2.checkbox("Temporada alta (noviembre o diciembre)", key=k("alta"))

estimacion = impact.estimar(exp, escenario, cantidad, alta)
st.dataframe(
    pd.DataFrame(
        [{"Concepto": c, "MXN": round(m)} for c, m in estimacion["componentes"].items()] +
        [{"Concepto": "Total", "MXN": round(estimacion["total"])}]
    ),
    hide_index=True,
)
sugerido = impact.nivel_impacto(estimacion["total"], umbrales)
st.caption(f"Con los umbrales de Preparar, el monto corresponde a impacto «{sugerido}».")
impacto_nivel = st.selectbox(
    "Nivel de impacto (H-3)",
    scales.NIVELES,
    index=None,
    key=k("impacto_nivel"),
)
justificacion = st.text_area(
    "Justificación del impacto",
    key=k("justificacion"),
    help="Explica qué pierde Kaab, a quién afecta y por qué elegiste ese nivel y no otro.",
)

tarjeta = model.vacia(activo)
tarjeta["fuente"] = {
    "descripcion": fuente_desc.strip(), "tipo": tipo or "", "capacidad": capacidad or "",
    "intencion": intencion or "", "orientacion": orientacion or "",
}
tarjeta["evento"] = {"descripcion": evento_desc.strip()}
tarjeta["vulnerabilidad"] = {
    "descripcion": vuln_desc.strip(), "condicion_predisponente": cond.strip(), "evidencia": evidencia.strip(),
}
tarjeta["probabilidad"] = {"ocurrencia": ocurrencia or "", "impacto_adverso": impacto_adverso or ""}
tarjeta["impacto"] = {"nivel": impacto_nivel or "", "monto_mxn": round(estimacion["total"]), "justificacion": justificacion.strip()}

st.subheader("2-6 · Riesgo")
calc = model.evaluar(tarjeta)
if calc["riesgo"]:
    st.success(
        f"Probabilidad global «{calc['probabilidad']}» (G-5) con impacto «{impacto_nivel}» → "
        f"riesgo **{calc['riesgo']}** (I-2)."
    )
else:
    st.info("Completa la probabilidad y el impacto para ver el nivel de riesgo.")

st.subheader("Registro")
col5, col6 = st.columns(2)
dueno = col5.text_input("Dueño del riesgo: quién financia o aprueba la respuesta", key=k("dueno"))
tier = col6.selectbox("Tier de decisión", model.TIERS, index=None, key=k("tier"))
respuesta = st.radio("Respuesta al riesgo", model.RESPUESTAS, index=None, horizontal=True, key=k("respuesta"))
contramedida = st.text_area(
    "Contramedida propuesta (describe la acción, no solo el control)",
    key=k("contramedida"),
)
estado = st.selectbox("Estado", model.ESTADOS, index=0, key=k("estado"))

tarjeta["registro"] = {
    "dueno": dueno.strip(), "respuesta": respuesta or "", "contramedida": contramedida.strip(),
    "estado": estado, "tier": tier or "",
}

st.subheader("Revisión antes de guardar")
for nivel, mensaje in checks.revisar(tarjeta, tolerancia):
    if nivel == "error":
        st.error(mensaje)
    else:
        st.warning(mensaje)
faltan = model.faltantes(tarjeta)
if faltan:
    st.caption(f"Pendiente de completar ({len(faltan)}): " + "; ".join(faltan))

if st.button("Guardar tarjeta", type="primary", disabled=bool(faltan)):
    tarjeta["id"] = model.siguiente_id(ws.get("riesgos", []))
    tarjeta["creado_en"] = model.marca_de_tiempo()
    ws.setdefault("riesgos", []).append(tarjeta)
    store.save(ws)
    st.session_state["tarjeta_n"] = n + 1
    st.session_state["flash"] = f"Tarjeta {tarjeta['id']} guardada en el Registro."
    st.rerun()
