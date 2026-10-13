"""Cálculo de impacto en contexto con las cifras de org.json."""

import pytest

from kaab import impact, org


@pytest.fixture
def expediente():
    return org.cargar()


def test_caida_tienda_cuatro_horas_normal(expediente):
    assert impact.caida_tienda(expediente, 4) == pytest.approx(4383.56, abs=0.01)


def test_temporada_alta_multiplica_por_factor(expediente):
    normal = impact.caida_tienda(expediente, 10)
    alta = impact.caida_tienda(expediente, 10, alta=True)
    assert alta == pytest.approx(normal * expediente["impacto"]["factor_temporada_alta"])


def test_caida_nucleo_siete_dias_incluye_rescision(expediente):
    total = impact.estimar(expediente, "caida_nucleo", 7)["total"]
    assert total == pytest.approx(1299223.9, abs=1.0)


def test_caida_nucleo_antes_del_umbral_no_hay_rescision(expediente):
    componentes = impact.caida_nucleo(expediente, 6)
    assert componentes["Pérdida de un distribuidor (escenario extremo)"] == 0


def test_escenario_extremo_en_temporada_alta_es_alto(expediente):
    total = impact.estimar(expediente, "caida_nucleo", 14, alta=True)["total"]
    assert impact.nivel_impacto(total, expediente["impacto"]["umbrales_mxn"]) == "Alto"


def test_fuga_de_datos_de_referencia_es_moderada(expediente):
    total = impact.estimar(expediente, "fuga_datos", expediente["datos_personales"]["clientes_registrados_tienda"])["total"]
    assert impact.nivel_impacto(total, expediente["impacto"]["umbrales_mxn"]) == "Moderado"


@pytest.mark.parametrize("monto, nivel", [
    (0, "Muy bajo"),
    (40000, "Muy bajo"),       # cota inclusiva
    (40001, "Bajo"),
    (2000000, "Moderado"),
    (2000001, "Alto"),
    (6000001, "Muy alto"),
])
def test_nivel_impacto_umbrales(monto, nivel, expediente):
    assert impact.nivel_impacto(monto, expediente["impacto"]["umbrales_mxn"]) == nivel


def test_escenario_desconocido():
    with pytest.raises(ValueError):
        impact.estimar(org.cargar(), "inexistente", 1)
