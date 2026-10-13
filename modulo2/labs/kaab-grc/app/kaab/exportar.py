"""Exportación del registro de riesgos a Excel (la base de la Matriz de Gestión de Riesgos)."""

from io import BytesIO

from openpyxl import Workbook
from openpyxl.styles import Font

from . import model

ENCABEZADOS = [
    "ID", "Activo", "Fuente de amenaza", "Tipo de fuente", "Evento de amenaza",
    "Vulnerabilidad", "Evidencia", "Probabilidad global", "Impacto", "Monto estimado (MXN)",
    "Riesgo", "Respuesta", "Contramedida", "Dueño", "Estado", "Tier",
]


def registro_xlsx(riesgos):
    libro = Workbook()
    hoja = libro.active
    hoja.title = "Registro de riesgos"
    hoja.append(ENCABEZADOS)
    for celda in hoja[1]:
        celda.font = Font(bold=True)

    for r in riesgos:
        calc = model.evaluar(r)
        hoja.append([
            r.get("id", ""),
            r.get("activo", ""),
            model.valor(r, "fuente", "descripcion"),
            model.valor(r, "fuente", "tipo"),
            model.valor(r, "evento", "descripcion"),
            model.valor(r, "vulnerabilidad", "descripcion"),
            model.valor(r, "vulnerabilidad", "evidencia"),
            calc["probabilidad"] or "",
            model.valor(r, "impacto", "nivel"),
            (r.get("impacto") or {}).get("monto_mxn") or "",
            calc["riesgo"] or "",
            model.valor(r, "registro", "respuesta"),
            model.valor(r, "registro", "contramedida"),
            model.valor(r, "registro", "dueno"),
            model.valor(r, "registro", "estado"),
            model.valor(r, "registro", "tier"),
        ])

    for columna in hoja.columns:
        ancho = max(len(str(c.value or "")) for c in columna)
        hoja.column_dimensions[columna[0].column_letter].width = min(max(12, ancho + 2), 60)

    salida = BytesIO()
    libro.save(salida)
    return salida.getvalue()
