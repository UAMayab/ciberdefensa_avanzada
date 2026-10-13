"""Rutas y URLs del laboratorio. Se pueden cambiar con variables de entorno."""

import os
from pathlib import Path

WORKSPACE = Path(os.environ.get("KAAB_GRC_WORKSPACE", "/var/lib/kaab-grc/workspace.json"))
STATUS_URL = os.environ.get("KAAB_STATUS_URL", "http://192.168.61.20/status")
SITIO_URL = os.environ.get("KAAB_SITIO_URL", "http://192.168.61.10/")
TIENDA_URL = os.environ.get("KAAB_TIENDA_URL", "http://192.168.61.20/")
