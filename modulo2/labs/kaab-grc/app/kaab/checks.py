"""Retroalimentación determinista sobre las tarjetas de riesgo.

Cada regla corresponde a un error conceptual frecuente de la Unidad 2–4 del
módulo. Devuelve (nivel, mensaje) con nivel «error» (falta algo) o «aviso»
(puede ser correcto, pero hay que justificarlo). No califica al estudiante.
"""

from . import model, scales


def _norm(texto):
    return " ".join((texto or "").lower().split())


def revisar(tarjeta, tolerancia_maxima):
    avisos = []

    for etiqueta in model.faltantes(tarjeta):
        avisos.append(("error", f"Falta: {etiqueta}."))

    fuente = tarjeta.get("fuente", {})
    evento = tarjeta.get("evento", {})

    if fuente.get("descripcion") and _norm(fuente.get("descripcion")) == _norm(evento.get("descripcion")):
        avisos.append((
            "error",
            "La fuente y el evento no pueden ser lo mismo. La fuente es quién o qué puede actuar; "
            "el evento es lo que esa fuente podría hacer (Unidad 2, vocabulario de SP 800-30).",
        ))

    if (
        fuente.get("tipo") == "Adversarial"
        and fuente.get("orientacion") == "No"
        and fuente.get("intencion") in ("Ninguna", "Baja")
    ):
        avisos.append((
            "aviso",
            "Esta fuente adversaria no parece dirigirse a Kaab. Justifica por qué sigue en la evaluación "
            "o descártala (tarea 2-1: capacidad, intención y orientación).",
        ))

    if not model.valor(tarjeta, "vulnerabilidad", "evidencia").strip():
        avisos.append(("error", "Una vulnerabilidad sin evidencia no se puede verificar: indica dónde la viste."))

    riesgo = model.evaluar(tarjeta)["riesgo"]
    respuesta = model.valor(tarjeta, "registro", "respuesta")
    if riesgo and respuesta == "Aceptar" and scales.NIVELES.index(riesgo) > scales.NIVELES.index(tolerancia_maxima):
        avisos.append((
            "aviso",
            f"El riesgo es «{riesgo}», por encima de la tolerancia definida en Preparar («{tolerancia_maxima}»). "
            "Aceptarlo requiere aprobación explícita de la dirección (Unidad 2, respuesta al riesgo).",
        ))

    return avisos


def revisar_registro(riesgos):
    """Reglas que solo tienen sentido sobre el conjunto de riesgos."""
    avisos = []
    evaluados = [model.evaluar(r)["riesgo"] for r in riesgos]
    evaluados = [r for r in evaluados if r]
    if len(evaluados) >= 4:
        altos = sum(1 for r in evaluados if scales.NIVELES.index(r) >= 3)
        if altos / len(evaluados) >= 0.75:
            avisos.append((
                "aviso",
                "Casi todos los riesgos están en «Alto» o «Muy alto». Si todo es urgente, nada lo es: "
                "prioriza comparando riesgos entre sí (Unidad 4, Analizar y priorizar).",
            ))
    return avisos
