"""El expediente de Kaab debe ser internamente consistente: cifras que no cuadran confunden al estudiante."""

import pytest

from kaab import org, scales


@pytest.fixture
def exp():
    return org.cargar()


def canal(exp, cid):
    return next(c for c in exp["canales"] if c["id"] == cid)


def test_canales_suman_ventas_anuales(exp):
    total = sum(c["ventas_anuales_mxn"] for c in exp["canales"])
    assert total == exp["organizacion"]["ventas_anuales_mxn"]


@pytest.mark.parametrize("cid", ["tienda", "distribuidores"])
def test_pedidos_por_ticket_cuadran_con_ventas(exp, cid):
    c = canal(exp, cid)
    diario = c["pedidos_por_dia"] * c["ticket_promedio_mxn"]
    assert diario == pytest.approx(c["ventas_anuales_mxn"] / 365, rel=0.05)


def test_clientes_ue_son_subconjunto(exp):
    datos = exp["datos_personales"]
    assert 0 < datos["de_ellos_en_ue"] <= datos["clientes_registrados_tienda"]


def test_umbrales_de_impacto_crecen_y_cubren_los_niveles(exp):
    umbrales = exp["impacto"]["umbrales_mxn"]
    assert list(umbrales) == scales.NIVELES[:-1]
    valores = list(umbrales.values())
    assert valores == sorted(valores)


def test_ip_de_cada_maquina_es_unica(exp):
    ips = [s["ip"] for s in exp["infraestructura"]]
    assert len(ips) == len(set(ips))
