"""Humo de cada página y un flujo completo: llenar una tarjeta y verla en el progreso."""

from pathlib import Path

import pytest
from streamlit.testing.v1 import AppTest

from kaab import store

PAGINAS = sorted(p.name for p in (Path(__file__).parent.parent / "pages").glob("*.py"))
APP = Path(__file__).parent.parent / "app.py"


@pytest.mark.parametrize("pagina", PAGINAS)
def test_pagina_carga_sin_excepciones(pagina):
    at = AppTest.from_file(str(APP.parent / "pages" / pagina), default_timeout=30).run()
    assert not at.exception, [e.value for e in at.exception]


def test_app_principal_carga():
    at = AppTest.from_file(str(APP), default_timeout=30).run()
    assert not at.exception, [e.value for e in at.exception]


def test_flujo_tarjeta_completa_llega_al_progreso():
    at = AppTest.from_file(str(APP.parent / "pages" / "conducir.py"), default_timeout=30).run()
    at.text_input(key="fuente_desc_0").input("Grupo criminal que envía correos falsos a empresas del sector")
    at.radio(key="tipo_0").set_value("Adversarial")
    at.selectbox(key="capacidad_0").select("Alta")
    at.selectbox(key="intencion_0").select("Alta")
    at.radio(key="orientacion_0").set_value("Sí")
    at.text_input(key="evento_desc_0").input("Un correo que imita a un distribuidor pide cambiar la cuenta de pago.")
    at.text_input(key="vuln_desc_0").input("Las cuentas de correo no exigen segundo factor.")
    at.text_input(key="cond_0").input("Pagos a distribuidores por transferencia sin confirmación.")
    at.text_input(key="evidencia_0").input("Configuración revisada: el inicio de sesión pide solo contraseña.")
    at.selectbox(key="ocurrencia_0").select("Alto")
    at.selectbox(key="impacto_adverso_0").select("Moderado")
    at.selectbox(key="escenario_0").select("fuga_datos")
    at.number_input(key="cantidad_0").set_value(36000)
    at.selectbox(key="impacto_nivel_0").select("Moderado")
    at.text_area(key="justificacion_0").input("Pago de 900 000 MXN desviado y costos de recuperación.")
    at.text_input(key="dueno_0").input("Dirección de finanzas")
    at.selectbox(key="tier_0").select("2 · Proceso o misión")
    at.radio(key="respuesta_0").set_value("Mitigar")
    at.text_area(key="contramedida_0").input("Segundo factor y confirmación telefónica.")
    at.run()
    assert not at.exception, [e.value for e in at.exception]
    assert not any(e.type == "error" for e in at.error), [e.value for e in at.error]

    at.button[0].click().run()
    assert not at.exception, [e.value for e in at.exception]

    guardado = store.load()["riesgos"]
    assert len(guardado) == 1
    assert guardado[0]["id"] == "R-001"
    assert guardado[0]["impacto"]["monto_mxn"] == 900000
    assert guardado[0]["probabilidad"]["ocurrencia"] == "Alto"
