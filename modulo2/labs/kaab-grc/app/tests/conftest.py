"""Hace importable el paquete `kaab` y aísla el progreso de cada corrida de pruebas."""

import os
import sys
import tempfile
from pathlib import Path

APP = Path(__file__).resolve().parent.parent
sys.path.insert(0, str(APP))

# Antes de importar kaab.config: el progreso de pruebas nunca toca /var/lib.
os.environ.setdefault("KAAB_GRC_WORKSPACE", str(Path(tempfile.mkdtemp()) / "workspace.json"))
# El monitor consulta una URL. Un puerto cerrado responde al instante, así las pruebas no esperan 3 s de timeout.
os.environ.setdefault("KAAB_STATUS_URL", "http://127.0.0.1:9/status")
os.environ.setdefault("KAAB_SITIO_URL", "http://127.0.0.1:9/")
