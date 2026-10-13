import json
import urllib.error
import urllib.request

import pandas as pd
import streamlit as st

from kaab import config

st.title("Monitor del negocio")
st.caption(
    "Lo que la tienda y la base de pedidos reportan en tiempo real. Se actualiza cada 10 segundos. "
    "Úsalo para ver qué pasa con el negocio cuando un componente se cae."
)


def leer_status():
    """Devuelve (datos, None) si responde, o (None, mensaje) si no hay respuesta válida.

    El endpoint responde 503 con JSON cuando la base no está disponible; eso también es un dato.
    """
    try:
        with urllib.request.urlopen(config.STATUS_URL, timeout=3) as r:
            return json.loads(r.read().decode("utf-8")), None
    except urllib.error.HTTPError as error:
        try:
            return json.loads(error.read().decode("utf-8")), None
        except ValueError:
            return None, f"HTTP {error.code}"
    except (urllib.error.URLError, TimeoutError, ValueError) as error:
        return None, str(getattr(error, "reason", error))


def sitio_responde():
    try:
        with urllib.request.urlopen(config.SITIO_URL, timeout=3) as r:
            return r.status == 200
    except (urllib.error.URLError, TimeoutError, ValueError):
        return False


@st.fragment(run_every=10)
def panel():
    datos, error = leer_status()
    col1, col2, col3 = st.columns(3)
    col1.metric("Sitio público", "En línea" if sitio_responde() else "Sin respuesta")

    if datos is None:
        col2.metric("Tienda", "Sin respuesta")
        col3.metric("Base de pedidos", "Desconocido")
        st.error(
            "La tienda no responde. Para el negocio, las ventas en línea están detenidas. "
            f"Detalle técnico: {error}"
        )
        return

    col2.metric("Tienda", "En línea" if datos.get("estado") == "ok" else "Degradada")
    col3.metric("Base de pedidos", "En línea" if datos.get("base_datos") == "ok" else "Sin conexión")

    if datos.get("base_datos") != "ok":
        st.error(
            "La base de pedidos no responde: ningún pedido avanza en ningún canal. "
            "Los distribuidores de la UE empiezan a acumular retraso."
        )

    col4, col5 = st.columns(2)
    col4.metric("Pedidos en la última hora", datos.get("pedidos_ultima_hora", 0))
    col5.metric("Último pedido", datos.get("ultimo_pedido") or "—")

    por_estado = datos.get("pedidos_por_estado") or {}
    if por_estado:
        st.subheader("Pedidos por estado")
        st.bar_chart(pd.Series(por_estado, name="pedidos"))

    st.caption(f"Última lectura: {datos.get('generado_en', '—')}")


panel()
