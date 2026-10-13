"""Carga el expediente ficticio de Kaab desde data/org.json."""

import json
from functools import lru_cache
from pathlib import Path

_RUTA = Path(__file__).parent / "data" / "org.json"


@lru_cache(maxsize=1)
def cargar():
    return json.loads(_RUTA.read_text(encoding="utf-8"))
