#!/usr/bin/env python3
"""Miniworkshop Bettercap — login de la intranet y del portal de pagos.

Cenote Logística es una empresa FICTICIA. Este servicio existe para que haya
un POST con credenciales que capturar; no guarda sesiones reales.
Escucha sólo en 127.0.0.1:8081, detrás de nginx.
"""
import secrets
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from urllib.parse import parse_qs

# host (sin puerto) -> (usuario, contraseña, nombre que se saluda)
CUENTAS = {
    "intranet.corp.lab": ("ana.canul", "Chaac-Lluvia-2026", "Ana Canul"),
    "pagos.corp.lab": ("tesoreria", "Pagos-Cenote-31", "Tesorería"),
}

PAGINA = """<!doctype html><html lang="es"><head><meta charset="utf-8">
<title>{titulo}</title><link rel="stylesheet" href="/static/estilos.css"></head>
<body><main class="caja"><h1>{titulo}</h1><p>{cuerpo}</p>
<p><a href="/">Volver al inicio</a></p></main></body></html>"""


class App(BaseHTTPRequestHandler):
    server_version = "intranet-app"

    def _responder(self, codigo, titulo, cuerpo, extra=None):
        datos = PAGINA.format(titulo=titulo, cuerpo=cuerpo).encode()
        self.send_response(codigo)
        self.send_header("Content-Type", "text/html; charset=utf-8")
        self.send_header("Content-Length", str(len(datos)))
        for k, v in (extra or {}).items():
            self.send_header(k, v)
        self.end_headers()
        self.wfile.write(datos)

    def do_POST(self):
        largo = int(self.headers.get("Content-Length", 0))
        campos = parse_qs(self.rfile.read(largo).decode(errors="replace"))
        host = self.headers.get("Host", "").split(":")[0]
        cuenta = CUENTAS.get(host)
        usuario = campos.get("usuario", [""])[0]
        clave = campos.get("clave", [""])[0]
        if cuenta and (usuario, clave) == cuenta[:2]:
            self._responder(
                200, "Sesión iniciada", f"Bienvenida/o, {cuenta[2]}.",
                {"Set-Cookie": f"sesion={secrets.token_hex(8)}; HttpOnly"})
        else:
            self._responder(401, "Acceso denegado",
                            "Usuario o contraseña incorrectos.")

    def do_GET(self):
        self._responder(405, "Método no permitido", "Use el formulario de acceso.")

    def log_message(self, fmt, *args):  # el registro útil es el de nginx
        pass


if __name__ == "__main__":
    ThreadingHTTPServer(("127.0.0.1", 8081), App).serve_forever()
