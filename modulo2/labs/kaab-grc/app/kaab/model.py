"""Tarjeta de riesgo: la cadena de SP 800-30 Rev. 1 (tareas 2-1 a 2-6) y su registro.

Solo se guardan entradas. Probabilidad y riesgo se calculan al leerlos, así
un cambio en las escalas o en los umbrales no deja resultados viejos.
"""

from datetime import datetime, timezone

from . import scales

RESPUESTAS = ["Aceptar", "Evitar", "Mitigar", "Compartir", "Transferir"]
ESTADOS = ["Abierto", "En tratamiento", "Cerrado"]
TIERS = ["1 · Organización", "2 · Proceso o misión", "3 · Sistema de información"]
TIPOS_FUENTE = ["Adversarial", "No adversarial"]
CAPACIDAD = ["Alta", "Media", "Baja"]
INTENCION = ["Alta", "Media", "Baja", "Ninguna"]
ORIENTACION = ["Sí", "No"]

# Campos que una tarjeta necesita para considerarse completa.
CAMPOS_OBLIGATORIOS = [
    ("fuente", "descripcion", "Fuente de amenaza"),
    ("fuente", "tipo", "Tipo de fuente"),
    ("evento", "descripcion", "Evento de amenaza"),
    ("vulnerabilidad", "descripcion", "Vulnerabilidad"),
    ("vulnerabilidad", "evidencia", "Evidencia de la vulnerabilidad"),
    ("probabilidad", "ocurrencia", "Probabilidad de iniciación u ocurrencia"),
    ("probabilidad", "impacto_adverso", "Probabilidad de impacto adverso"),
    ("impacto", "nivel", "Nivel de impacto"),
    ("impacto", "justificacion", "Justificación del impacto"),
    ("registro", "dueno", "Dueño del riesgo"),
    ("registro", "respuesta", "Respuesta al riesgo"),
]


def vacia(activo=""):
    return {
        "activo": activo,
        "fuente": {"descripcion": "", "tipo": "", "capacidad": "", "intencion": "", "orientacion": ""},
        "evento": {"descripcion": ""},
        "vulnerabilidad": {"descripcion": "", "condicion_predisponente": "", "evidencia": ""},
        "probabilidad": {"ocurrencia": "", "impacto_adverso": ""},
        "impacto": {"nivel": "", "monto_mxn": None, "justificacion": ""},
        "registro": {"dueno": "", "respuesta": "", "contramedida": "", "estado": "Abierto", "tier": ""},
    }


def valor(tarjeta, seccion, campo):
    return (tarjeta.get(seccion) or {}).get(campo) or ""


def faltantes(tarjeta):
    return [etiqueta for sec, campo, etiqueta in CAMPOS_OBLIGATORIOS if not valor(tarjeta, sec, campo).strip()]


def evaluar(tarjeta):
    """Deriva la probabilidad global (G-5) y el nivel de riesgo (I-2).

    Devuelve None donde falten entradas, para que la UI lo muestre como pendiente.
    """
    ocurrencia = valor(tarjeta, "probabilidad", "ocurrencia")
    impacto_adverso = valor(tarjeta, "probabilidad", "impacto_adverso")
    nivel_impacto = valor(tarjeta, "impacto", "nivel")
    probabilidad = scales.probabilidad_global(ocurrencia, impacto_adverso) if ocurrencia and impacto_adverso else None
    riesgo = scales.nivel_de_riesgo(probabilidad, nivel_impacto) if probabilidad and nivel_impacto else None
    return {"probabilidad": probabilidad, "riesgo": riesgo}


def siguiente_id(riesgos):
    usados = [int(r["id"].split("-")[1]) for r in riesgos if r.get("id", "").startswith("R-")]
    return f"R-{(max(usados) if usados else 0) + 1:03d}"


def marca_de_tiempo():
    return datetime.now(timezone.utc).isoformat(timespec="seconds")
