"""Persistencia del progreso del estudiante: un único JSON.

Escritura atómica (archivo temporal + os.replace) para que un corte de
energía no deje el progreso a medias. Las claves que esta versión no
conoce se conservan porque siempre se guarda el diccionario completo.
"""

import json
import os
import tempfile
from pathlib import Path

from . import config

SCHEMA_VERSION = 1


def load(path=None):
    p = Path(path or config.WORKSPACE)
    if not p.exists():
        return {"schema_version": SCHEMA_VERSION}
    data = json.loads(p.read_text(encoding="utf-8"))
    version = data.get("schema_version", SCHEMA_VERSION)
    if version > SCHEMA_VERSION:
        raise ValueError(
            f"El progreso fue guardado con una versión más nueva ({version}). "
            "Actualiza el laboratorio antes de abrirlo."
        )
    data["schema_version"] = version
    return data


def save(data, path=None):
    p = Path(path or config.WORKSPACE)
    p.parent.mkdir(parents=True, exist_ok=True)
    fd, tmp = tempfile.mkstemp(dir=p.parent, prefix=".ws-", suffix=".json")
    with os.fdopen(fd, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
    os.replace(tmp, p)
