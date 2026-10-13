"""Impacto en contexto para Kaab (Tarea 2-5).

Convierte un escenario en pérdidas en pesos y luego en un nivel de impacto
de SP 800-30 (Tabla H-3) usando los umbrales que el estudiante fijó en
Preparar. Es una aproximación didáctica: los supuestos están en
data/org.json y en la actividad S2.
"""

from . import scales

HORAS_ANIO = 8760
ESCENARIOS = {
    "caida_tienda": "La tienda en línea deja de vender (horas)",
    "caida_nucleo": "Se cae la base de pedidos: tienda y distribuidores se detienen (días)",
    "fuga_datos": "Se filtran datos de clientes (número de registros)",
}


def _canal(org, canal_id):
    return next(c for c in org["canales"] if c["id"] == canal_id)


def _factor(org, alta):
    return org["impacto"]["factor_temporada_alta"] if alta else 1


def ingreso_por_distribuidor(org):
    """Ingreso anual promedio de un distribuidor. Supuesto: se reparte en partes iguales."""
    b2b = _canal(org, "distribuidores")
    return b2b["ventas_anuales_mxn"] / b2b["distribuidores"]


def caida_tienda(org, horas, alta=False):
    tienda = _canal(org, "tienda")
    por_hora = tienda["ventas_anuales_mxn"] / HORAS_ANIO
    return horas * por_hora * _factor(org, alta)


def caida_nucleo(org, dias, alta=False):
    b2b = _canal(org, "distribuidores")
    pedidos_afectados = b2b["pedidos_por_dia"] * dias
    # Supuesto simplificado: todos los pedidos afectados se retrasan lo mismo.
    tasa = min(b2b["penalidad_diaria_pct"] * dias, b2b["penalidad_maxima_pct"]) / 100
    componentes = {
        "Ventas perdidas en la tienda": caida_tienda(org, dias * 24, alta),
        "Penalidades a distribuidores": pedidos_afectados * b2b["ticket_promedio_mxn"] * tasa,
        "Pérdida de un distribuidor (escenario extremo)": (
            ingreso_por_distribuidor(org) if dias >= b2b["dias_para_rescision"] else 0
        ),
    }
    return componentes


def fuga_datos(org, registros):
    return {"Atención a clientes y notificaciones (supuesto)": registros * org["impacto"]["costo_por_registro_expuesto_mxn"]}


def estimar(org, escenario, cantidad, alta=False):
    """Devuelve componentes en pesos y el total para un escenario."""
    if escenario == "caida_tienda":
        componentes = {"Ventas perdidas en la tienda": caida_tienda(org, cantidad, alta)}
    elif escenario == "caida_nucleo":
        componentes = caida_nucleo(org, cantidad, alta)
    elif escenario == "fuga_datos":
        componentes = fuga_datos(org, cantidad)
    else:
        raise ValueError(f"Escenario desconocido: {escenario}")
    return {"componentes": componentes, "total": sum(componentes.values())}


def nivel_impacto(monto, umbrales):
    """Mapea un monto en pesos a un nivel de impacto (Tabla H-3).

    `umbrales` son cotas superiores inclusivas para los primeros cuatro
    niveles; por encima de la última cota, el impacto es «Muy alto».
    """
    for nivel in scales.NIVELES[:-1]:
        if monto <= umbrales[nivel]:
            return nivel
    return scales.NIVELES[-1]
