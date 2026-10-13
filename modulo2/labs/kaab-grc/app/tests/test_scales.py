"""Las tablas de SP 800-30 Rev. 1 contra valores publicados en el PDF (G-5 e I-2)."""

import pytest

from kaab import scales

ORDEN = scales.NIVELES


@pytest.mark.parametrize("tabla", [scales.PROBABILIDAD_GLOBAL, scales.NIVEL_DE_RIESGO])
def test_tablas_completas(tabla):
    assert set(tabla) == set(ORDEN)
    assert all(len(fila) == len(ORDEN) for fila in tabla.values())


def test_probabilidad_global_no_decrece():
    for i, fila in enumerate(ORDEN):
        valores = [ORDEN.index(v) for v in scales.PROBABILIDAD_GLOBAL[fila]]
        assert valores == sorted(valores), f"fila {fila} no es monótona"
    for j in range(len(ORDEN)):
        columna = [ORDEN.index(scales.PROBABILIDAD_GLOBAL[f][j]) for f in ORDEN]
        assert columna == sorted(columna), f"columna {ORDEN[j]} no es monótona"


def test_nivel_de_riesgo_no_decrece():
    for fila in ORDEN:
        valores = [ORDEN.index(v) for v in scales.NIVEL_DE_RIESGO[fila]]
        assert valores == sorted(valores)
    for j in range(len(ORDEN)):
        columna = [ORDEN.index(scales.NIVEL_DE_RIESGO[f][j]) for f in ORDEN]
        assert columna == sorted(columna)


@pytest.mark.parametrize("ocurrencia, impacto_adverso, esperado", [
    ("Muy alto", "Muy bajo", "Bajo"),       # G-5, fila Very High
    ("Moderado", "Muy bajo", "Bajo"),       # G-5, fila Moderate
    ("Muy bajo", "Muy alto", "Bajo"),       # G-5, fila Very Low
    ("Bajo", "Muy alto", "Moderado"),       # G-5, fila Low
    ("Alto", "Moderado", "Moderado"),       # G-5, fila High
])
def test_probabilidad_global_publicada(ocurrencia, impacto_adverso, esperado):
    assert scales.probabilidad_global(ocurrencia, impacto_adverso) == esperado


@pytest.mark.parametrize("probabilidad, impacto, esperado", [
    ("Muy alto", "Muy alto", "Muy alto"),   # I-2, fila Very High
    ("Bajo", "Muy alto", "Moderado"),       # I-2, fila Low
    ("Moderado", "Muy bajo", "Muy bajo"),   # I-2, fila Moderate
    ("Muy bajo", "Muy alto", "Bajo"),       # I-2, fila Very Low
])
def test_nivel_de_riesgo_publicado(probabilidad, impacto, esperado):
    assert scales.nivel_de_riesgo(probabilidad, impacto) == esperado
