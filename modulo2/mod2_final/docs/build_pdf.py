#!/usr/bin/env python3
"""Genera Actividad-Final-Modulo-II.pdf (en la carpeta superior) desde el .md de esta carpeta.

Requisitos en el equipo del instructor: python3-markdown y google-chrome.
Uso:  python3 docs/build_pdf.py
"""
import pathlib
import subprocess
import tempfile

import markdown

DOCS = pathlib.Path(__file__).resolve().parent
OUT = DOCS.parent / "Actividad-Final-Modulo-II.pdf"

texto = (DOCS / "Actividad-Final-Modulo-II.md").read_text(encoding="utf-8")
cuerpo = markdown.markdown(texto, extensions=["tables", "fenced_code", "sane_lists", "attr_list", "md_in_html"])
# Las casillas "- [ ]" de la lista de verificación pasan a casillas reales.
cuerpo = cuerpo.replace("<li>[ ] ", '<li><input type="checkbox" disabled> ')

html = f"""<!doctype html><html lang="es"><head><meta charset="utf-8">
<base href="file://{DOCS}/"><title>Proyecto final · Módulo II</title>
<link rel="stylesheet" href="pdf.css"></head><body>{cuerpo}</body></html>"""

with tempfile.TemporaryDirectory() as d:
    f = pathlib.Path(d) / "doc.html"
    f.write_text(html, encoding="utf-8")
    subprocess.run(["google-chrome", "--headless=new", "--disable-gpu", "--no-sandbox", "--no-pdf-header-footer",
                    f"--print-to-pdf={OUT}", f"file://{f}"], check=True, capture_output=True)
print(OUT)
