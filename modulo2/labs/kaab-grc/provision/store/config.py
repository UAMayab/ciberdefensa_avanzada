# Configuración de la tienda de Kaab Orgánicos (FICTICIO).
# Se instala con permisos 0644: cualquier usuario local puede leerla.
# Es una configuración deliberadamente pobre, como en muchas instalaciones
# improvisadas. El estudiante la descubre desde el diagnóstico del servicio.

DB_HOST = "192.168.62.20"
DB_USER = "tienda"
DB_PASSWORD = "Tienda-2026"
DB_NAME = "kaab"

ADMIN_USER = "admin"
ADMIN_PASSWORD = "admin"

# Clave que firma las cookies de sesión (carrito y token CSRF). Se dejó la de fábrica.
SECRET_KEY = "kaab-2026"
