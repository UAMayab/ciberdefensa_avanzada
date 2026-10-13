"""Escalas cualitativas de NIST SP 800-30 Rev. 1 (Apéndices G, H e I).

Los valores se transcribieron del PDF oficial (tablas G-2 a G-5, H-3, I-2),
no de memoria. Los niveles van en español; los nombres originales están en
el comentario de cada tabla. Si cambias una celda, corre tests/test_scales.py.
"""

NIVELES = ["Muy bajo", "Bajo", "Moderado", "Alto", "Muy alto"]

# Tabla G-2 (iniciación, adversario) y G-3 (ocurrencia, no adversario).
# Rangos semicuantitativos de la misma tabla.
RANGOS_PROBABILIDAD = {
    "Muy bajo": "0–4",
    "Bajo": "5–20",
    "Moderado": "21–79",
    "Alto": "80–95",
    "Muy alto": "96–100",
}

# Tabla G-5, "Overall likelihood". Filas: probabilidad de iniciación u
# ocurrencia (G-2 o G-3). Columnas: probabilidad de impacto adverso dado
# que el evento ocurre (G-4), en el orden de NIVELES.
PROBABILIDAD_GLOBAL = {
    "Muy alto": ["Bajo", "Moderado", "Alto", "Muy alto", "Muy alto"],
    "Alto": ["Bajo", "Moderado", "Moderado", "Alto", "Muy alto"],
    "Moderado": ["Bajo", "Bajo", "Moderado", "Moderado", "Alto"],
    "Bajo": ["Muy bajo", "Bajo", "Bajo", "Moderado", "Moderado"],
    "Muy bajo": ["Muy bajo", "Muy bajo", "Bajo", "Bajo", "Bajo"],
}

# Tabla I-2, "Level of risk". Filas: probabilidad global (G-5). Columnas:
# nivel de impacto (H-3), en el orden de NIVELES.
NIVEL_DE_RIESGO = {
    "Muy alto": ["Muy bajo", "Bajo", "Moderado", "Alto", "Muy alto"],
    "Alto": ["Muy bajo", "Bajo", "Moderado", "Alto", "Muy alto"],
    "Moderado": ["Muy bajo", "Bajo", "Moderado", "Moderado", "Alto"],
    "Bajo": ["Muy bajo", "Bajo", "Bajo", "Bajo", "Moderado"],
    "Muy bajo": ["Muy bajo", "Muy bajo", "Muy bajo", "Bajo", "Bajo"],
}


def probabilidad_global(ocurrencia, impacto_adverso):
    """Tabla G-5: combina la probabilidad de que el evento ocurra con la
    probabilidad de que tenga impacto adverso."""
    return PROBABILIDAD_GLOBAL[ocurrencia][NIVELES.index(impacto_adverso)]


def nivel_de_riesgo(probabilidad, impacto):
    """Tabla I-2: combina la probabilidad global con el nivel de impacto."""
    return NIVEL_DE_RIESGO[probabilidad][NIVELES.index(impacto)]


def rango(nivel):
    """Rango semicuantitativo (0–100) de un nivel cualitativo."""
    return RANGOS_PROBABILIDAD[nivel]
