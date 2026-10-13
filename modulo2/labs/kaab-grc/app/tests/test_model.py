"""Tarjetas de riesgo, validaciones y el ejemplo resuelto."""

from kaab import checks, contenido, model


def test_tarjeta_vacia_lista_todos_los_faltantes():
    assert len(model.faltantes(model.vacia())) == len(model.CAMPOS_OBLIGATORIOS)


def test_ejemplo_resuelto_es_completo_y_coherente():
    assert model.faltantes(contenido.EJEMPLO) == []
    calc = model.evaluar(contenido.EJEMPLO)
    assert calc == {"probabilidad": "Moderado", "riesgo": "Moderado"}


def test_ejemplo_resuelto_no_genera_errores():
    avisos = checks.revisar(contenido.EJEMPLO, tolerancia_maxima="Moderado")
    assert [n for n, _ in avisos if n == "error"] == []


def test_sin_entradas_el_riesgo_queda_pendiente():
    assert model.evaluar(model.vacia()) == {"probabilidad": None, "riesgo": None}


def test_siguiente_id_no_reutiliza():
    assert model.siguiente_id([]) == "R-001"
    assert model.siguiente_id([{"id": "R-004"}, {"id": "R-002"}]) == "R-005"


def test_fuente_igual_al_evento_es_error():
    t = contenido.EJEMPLO | {"evento": {"descripcion": contenido.EJEMPLO["fuente"]["descripcion"]}}
    assert any(n == "error" and "no pueden ser lo mismo" in m for n, m in checks.revisar(t, "Moderado"))


def test_aceptar_por_encima_de_tolerancia_es_aviso():
    t = contenido.EJEMPLO | {"registro": {**contenido.EJEMPLO["registro"], "respuesta": "Aceptar"}}
    t = {**t, "probabilidad": {"ocurrencia": "Muy alto", "impacto_adverso": "Muy alto"}, "impacto": {**t["impacto"], "nivel": "Muy alto"}}
    avisos = checks.revisar(t, "Moderado")
    assert any(n == "aviso" and "tolerancia" in m for n, m in avisos)


def test_fuente_adversaria_sin_orientacion_es_aviso():
    fuente = {**contenido.EJEMPLO["fuente"], "orientacion": "No", "intencion": "Ninguna"}
    avisos = checks.revisar(contenido.EJEMPLO | {"fuente": fuente}, "Moderado")
    assert any(n == "aviso" and "no parece dirigirse" in m for n, m in avisos)


def test_vulnerabilidad_sin_evidencia_es_error():
    vuln = {**contenido.EJEMPLO["vulnerabilidad"], "evidencia": "  "}
    avisos = checks.revisar(contenido.EJEMPLO | {"vulnerabilidad": vuln}, "Moderado")
    assert any(n == "error" and "sin evidencia" in m for n, m in avisos)


def _tarjeta_con_riesgo_alto(i):
    t = contenido.EJEMPLO | {"id": f"R-{i:03d}"}
    return {**t, "probabilidad": {"ocurrencia": "Muy alto", "impacto_adverso": "Muy alto"}, "impacto": {**t["impacto"], "nivel": "Muy alto"}}


def test_registro_casi_todo_alto_avisa_sobre_priorizar():
    riesgos = [_tarjeta_con_riesgo_alto(i) for i in range(1, 5)]
    assert any("prioriza" in m for _, m in checks.revisar_registro(riesgos))


def test_registro_con_pocos_riesgos_no_avisa():
    assert checks.revisar_registro([contenido.EJEMPLO]) == []
