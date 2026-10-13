import streamlit as st

from kaab import contenido, store

st.title("1 · Marco: vocabulario y tolerancia")
st.caption("Unidades 1 y 2. Antes de evaluar un solo riesgo, define las palabras y cuánto riesgo acepta Kaab.")

st.subheader("Vocabulario de SP 800-30 Rev. 1")
for termino, definicion in contenido.VOCABULARIO:
    with st.expander(termino):
        st.write(definicion)

st.subheader("Ejercicio: clasifica cada frase")
st.caption("Elige la categoría de cada frase. Al revisar, verás cuáles acertaste y por qué.")
ws = store.load()
marco = ws.setdefault("marco", {})
previas = marco.get("clasificacion", {})

with st.form("clasificacion"):
    elegidas = {}
    for i, item in enumerate(contenido.CLASIFICACION):
        elegidas[i] = st.radio(
            item["texto"],
            contenido.CATEGORIAS_CLASIFICACION,
            index=contenido.CATEGORIAS_CLASIFICACION.index(previas[str(i)]) if str(i) in previas else None,
            horizontal=True,
            key=f"clasif_{i}",
        )
    if st.form_submit_button("Revisar"):
        marco["clasificacion"] = {str(i): v for i, v in elegidas.items() if v}
        store.save(ws)
        for i, item in enumerate(contenido.CLASIFICACION):
            if elegidas[i] == item["categoria"]:
                st.success(f"Frase {i + 1}: correcto. Es «{item['categoria']}».")
            elif elegidas[i]:
                st.warning(f"Frase {i + 1}: es «{item['categoria']}», no «{elegidas[i]}». Revisa el vocabulario de arriba.")

st.subheader("Niveles de respuesta al riesgo")
for nombre, descripcion in contenido.RESPUESTAS_RIESGO:
    st.markdown(f"**{nombre}.** {descripcion}")

st.subheader("Tres tiers de decisión (SP 800-39)")
for nombre, descripcion in contenido.TIERS_TEXTO:
    st.markdown(f"**{nombre}.** {descripcion}")

st.subheader("Tu tarea: la tolerancia de Kaab")
st.markdown(
    "Escribe, con tus palabras y en **150 palabras o más**, cuánto riesgo puede aceptar Kaab sin pedir "
    "aprobación especial. Considera la temporada alta, los distribuidores de la UE y los datos de clientes. "
    "Esta tolerancia es el criterio con el que después juzgarás cada respuesta de tu registro."
)
with st.form("tolerancia"):
    texto = st.text_area("Tolerancia al riesgo de Kaab", value=marco.get("tolerancia", ""), height=220)
    st.caption("Mínimo recomendado: 150 palabras. El conteo se actualiza al guardar.")
    if st.form_submit_button("Guardar tolerancia"):
        marco["tolerancia"] = texto.strip()
        store.save(ws)
        st.success("Tolerancia guardada.")
