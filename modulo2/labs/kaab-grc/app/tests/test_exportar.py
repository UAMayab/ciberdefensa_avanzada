"""El Excel del registro conserva las columnas y los valores calculados."""

from io import BytesIO

from openpyxl import load_workbook

from kaab import contenido, exportar


def test_registro_xlsx_tiene_encabezados_y_riesgo_calculado():
    libro = load_workbook(BytesIO(exportar.registro_xlsx([contenido.EJEMPLO])))
    hoja = libro["Registro de riesgos"]
    encabezados = [c.value for c in hoja[1]]
    assert encabezados == exportar.ENCABEZADOS
    fila = dict(zip(encabezados, [c.value for c in hoja[2]]))
    assert fila["Riesgo"] == "Moderado"
    assert fila["Respuesta"] == "Mitigar"
