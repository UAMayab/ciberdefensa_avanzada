"""Tienda en línea de Kaab Orgánicos y su panel de administración (FICTICIO).

Flujo: catálogo → producto → carrito (en la sesión) → datos de envío y pago
simulado → pedido escrito en la base de core-lan. El proceso de pedidos avanza
ese pedido por el pipeline, y el Monitor lo refleja.

Laboratorio aislado para la evaluación de riesgos del Módulo II. Las debilidades
de esta aplicación son intencionales: el estudiante las descubre y las registra
como evidencia. Su mapa vive en modulo2/instructor/ (fuera del repo).
"""

import re
import secrets
from datetime import datetime, timezone
from decimal import Decimal
from urllib.parse import urlparse

import pymysql
from flask import (
    Flask, Response, abort, flash, jsonify, redirect, render_template, request, session, url_for,
)

import config

app = Flask(__name__)
app.secret_key = config.SECRET_KEY

ENVIO_MXN = Decimal("120.00")
ENVIO_GRATIS_DESDE = Decimal("900.00")
CANTIDAD_MAXIMA = 20
PAISES = [("MX", "México"), ("DE", "Alemania"), ("FR", "Francia"), ("ES", "España"), ("NL", "Países Bajos"), ("IT", "Italia")]
METODOS = [
    ("tarjeta_simulada", "Tarjeta (pago simulado)", "Este laboratorio no pide ni guarda datos de tarjeta."),
    ("transferencia", "Transferencia bancaria", "Tu pedido queda pendiente hasta que confirmemos el depósito."),
]
ESTADOS = ["creado", "pagado", "en_lote", "embarcado", "facturado", "cobrado"]
ETIQUETAS = {
    "creado": "Pedido recibido",
    "pagado": "Pago confirmado",
    "en_lote": "Asignado a lote de trazabilidad",
    "embarcado": "Embarcado",
    "facturado": "Factura emitida",
    "cobrado": "Completado",
}


class StockInsuficiente(Exception):
    """El inventario cambió entre que el cliente armó el carrito y pagó."""


# ---------------------------------------------------------------- base de datos

def conectar(autocommit=True):
    return pymysql.connect(
        host=config.DB_HOST, user=config.DB_USER, password=config.DB_PASSWORD,
        database=config.DB_NAME, connect_timeout=3, autocommit=autocommit, charset="utf8mb4",
    )


def consulta(sql, params=(), uno=False):
    """Devuelve filas como diccionarios (o una sola fila, o None)."""
    con = conectar()
    try:
        with con.cursor(pymysql.cursors.DictCursor) as cur:
            cur.execute(sql, params)
            return cur.fetchone() if uno else cur.fetchall()
    finally:
        con.close()


# ---------------------------------------------------------------- utilidades

def estado_stock(stock):
    if stock <= 0:
        return "Agotado", "secondary"
    if stock < 50:
        return "Pocas piezas", "warning"
    return "Disponible", "success"


def envio(subtotal):
    if subtotal <= 0 or subtotal >= ENVIO_GRATIS_DESDE:
        return Decimal("0.00")
    return ENVIO_MXN


def cantidad_valida(texto, minimo=1):
    try:
        n = int(texto)
    except (TypeError, ValueError):
        return None
    return n if minimo <= n <= CANTIDAD_MAXIMA else None


def volver(por_defecto):
    """Regresa a la página de origen si es de este mismo sitio; si no, a la ruta por defecto."""
    referencia = request.referrer
    if referencia and urlparse(referencia).netloc == request.host:
        return redirect(referencia)
    return redirect(por_defecto)


def csrf_token():
    if "_csrf" not in session:
        session["_csrf"] = secrets.token_hex(16)
    return session["_csrf"]


def carrito_actual():
    return dict(session.get("carrito", {}))


def guardar_carrito(carrito):
    session["carrito"] = carrito


def lineas_carrito():
    carrito = carrito_actual()
    if not carrito:
        return [], Decimal("0.00")
    marcas = ",".join(["%s"] * len(carrito))
    filas = consulta(
        f"SELECT sku, nombre, presentacion, precio_mxn, stock FROM productos WHERE activo = 1 AND sku IN ({marcas})",
        tuple(carrito),
    )
    por_sku = {f["sku"]: f for f in filas}
    lineas, subtotal = [], Decimal("0.00")
    for sku, cantidad in carrito.items():
        if sku not in por_sku:
            continue
        p = por_sku[sku]
        sub = p["precio_mxn"] * cantidad
        subtotal += sub
        lineas.append({**p, "cantidad": cantidad, "subtotal": sub, "excede": cantidad > p["stock"]})
    return lineas, subtotal


def validar_pedido(form):
    datos = {c: (form.get(c) or "").strip() for c in
             ["nombre", "correo", "telefono", "pais", "direccion", "codigo_postal", "rfc", "metodo_pago"]}
    errores = []
    if len(datos["nombre"]) < 3:
        errores.append("Escribe tu nombre completo.")
    if not re.fullmatch(r"[^@\s]+@[^@\s]+\.[^@\s]+", datos["correo"]):
        errores.append("El correo electrónico no es válido.")
    digitos = re.sub(r"\D", "", datos["telefono"])
    if not 10 <= len(digitos) <= 15:
        errores.append("El teléfono debe tener entre 10 y 15 dígitos.")
    paises = dict(PAISES)
    if datos["pais"] not in paises:
        errores.append("Elige un país de envío.")
    if len(datos["direccion"]) < 10:
        errores.append("Escribe la dirección de envío completa.")
    if datos["pais"] == "MX" and not re.fullmatch(r"\d{5}", datos["codigo_postal"]):
        errores.append("El código postal mexicano tiene 5 dígitos.")
    rfc = datos["rfc"].upper()
    if rfc and not re.fullmatch(r"[A-Z&Ñ]{3,4}\d{6}[A-Z0-9]{3}", rfc):
        errores.append("El RFC no tiene un formato válido (puedes dejarlo vacío).")
    if datos["metodo_pago"] not in {m[0] for m in METODOS}:
        errores.append("Elige un método de pago.")
    if not form.get("acepto"):
        errores.append("Debes aceptar el aviso de privacidad para continuar.")

    partes = [datos["direccion"]]
    if datos["codigo_postal"]:
        partes.append(f"C.P. {datos['codigo_postal']}")
    partes.append(paises.get(datos["pais"], datos["pais"]))
    datos.update(telefono=digitos, rfc=rfc, direccion_completa=", ".join(partes))
    return datos, errores


def crear_pedido(datos, carrito):
    """Descuenta inventario y registra cliente, pedido y líneas en una sola transacción."""
    skus = list(carrito)
    marcas = ",".join(["%s"] * len(skus))
    con = conectar(autocommit=False)
    try:
        with con.cursor() as cur:
            cur.execute(
                f"SELECT sku, nombre, precio_mxn, stock FROM productos WHERE activo = 1 AND sku IN ({marcas}) FOR UPDATE",
                skus,
            )
            filas = {f[0]: f for f in cur.fetchall()}
            if len(filas) != len(skus):
                raise StockInsuficiente("Uno de los productos ya no está disponible. Revisa tu carrito.")

            subtotal = Decimal("0.00")
            for sku in skus:
                _, nombre, precio, stock = filas[sku]
                if carrito[sku] > stock:
                    raise StockInsuficiente(f"Solo quedan {stock} piezas de {nombre}. Ajusta tu carrito.")
                subtotal += precio * carrito[sku]
            costo_envio = envio(subtotal)

            cur.execute("SELECT id FROM clientes WHERE correo = %s ORDER BY id LIMIT 1", (datos["correo"],))
            fila = cur.fetchone()
            if fila:
                cliente_id = fila[0]
                cur.execute(
                    "UPDATE clientes SET nombre = %s, telefono = %s, direccion = %s, pais = %s, rfc = %s WHERE id = %s",
                    (datos["nombre"], datos["telefono"], datos["direccion_completa"], datos["pais"],
                     datos["rfc"] or None, cliente_id),
                )
            else:
                cur.execute(
                    "INSERT INTO clientes (nombre, correo, telefono, direccion, pais, rfc, creado_en) "
                    "VALUES (%s, %s, %s, %s, %s, %s, NOW())",
                    (datos["nombre"], datos["correo"], datos["telefono"], datos["direccion_completa"],
                     datos["pais"], datos["rfc"] or None),
                )
                cliente_id = cur.lastrowid

            canal = "tienda_mx" if datos["pais"] == "MX" else "tienda_ue"
            cur.execute(
                "INSERT INTO pedidos (cliente_id, canal, envio_mxn, total_mxn, metodo_pago, direccion_envio, "
                "estado, creado_en, actualizado_en) VALUES (%s, %s, %s, %s, %s, %s, 'creado', NOW(), NOW())",
                (cliente_id, canal, costo_envio, subtotal + costo_envio, datos["metodo_pago"],
                 datos["direccion_completa"]),
            )
            pedido_id = cur.lastrowid
            for sku in skus:
                precio = filas[sku][2]
                cur.execute(
                    "INSERT INTO pedido_items (pedido_id, sku, cantidad, precio_unitario_mxn) VALUES (%s, %s, %s, %s)",
                    (pedido_id, sku, carrito[sku], precio),
                )
                cur.execute("UPDATE productos SET stock = stock - %s WHERE sku = %s", (carrito[sku], sku))
        con.commit()
        return pedido_id
    except Exception:
        con.rollback()
        raise
    finally:
        con.close()


# ---------------------------------------------------------------- plantillas y filtros

@app.template_filter("pesos")
def pesos(valor):
    return f"MXN {Decimal(valor):,.2f}"


@app.context_processor
def contexto_global():
    return {
        "carrito_n": sum(carrito_actual().values()),
        "csrf_token": csrf_token,
        "estado_stock": estado_stock,
        "ETIQUETAS": ETIQUETAS,
    }


@app.before_request
def proteger_formularios():
    if request.method == "POST":
        token = session.get("_csrf")
        if not token or request.form.get("_csrf") != token:
            abort(400)


# ---------------------------------------------------------------- tienda

@app.get("/")
def catalogo():
    productos = consulta(
        "SELECT sku, nombre, presentacion, precio_mxn, stock, descripcion FROM productos WHERE activo = 1 ORDER BY sku"
    )
    return render_template("catalogo.html", productos=productos)


@app.get("/producto/<sku>")
def producto(sku):
    p = consulta(
        "SELECT sku, nombre, presentacion, precio_mxn, stock, descripcion FROM productos WHERE sku = %s AND activo = 1",
        (sku,), uno=True,
    )
    if not p:
        abort(404)
    return render_template("producto.html", p=p)


@app.post("/carrito/agregar")
def carrito_agregar():
    sku = request.form.get("sku", "")
    cantidad = cantidad_valida(request.form.get("cantidad", "1"))
    if cantidad is None:
        flash("La cantidad debe estar entre 1 y 20 piezas.", "warning")
        return volver(url_for("catalogo"))

    p = consulta("SELECT sku, nombre, stock FROM productos WHERE sku = %s AND activo = 1", (sku,), uno=True)
    if not p:
        abort(404)

    carrito = carrito_actual()
    en_carrito = carrito.get(sku, 0)
    if p["stock"] <= 0:
        flash(f"{p['nombre']} está agotado por ahora.", "warning")
    elif en_carrito + cantidad > p["stock"]:
        flash(f"Solo quedan {p['stock']} piezas de {p['nombre']}. Ya tienes {en_carrito} en tu carrito.", "warning")
    else:
        carrito[sku] = en_carrito + cantidad
        guardar_carrito(carrito)
        flash(f"Agregaste {cantidad} × {p['nombre']} a tu carrito.", "success")
    return volver(url_for("catalogo"))


@app.get("/carrito")
def carrito_ver():
    lineas, subtotal = lineas_carrito()
    return render_template("carrito.html", lineas=lineas, subtotal=subtotal, envio=envio(subtotal))


@app.post("/carrito/actualizar")
def carrito_actualizar():
    sku = request.form.get("sku", "")
    try:
        cantidad = int(request.form.get("cantidad", ""))
    except ValueError:
        flash("La cantidad no es válida.", "warning")
        return redirect(url_for("carrito_ver"))

    carrito = carrito_actual()
    if sku not in carrito:
        return redirect(url_for("carrito_ver"))

    if cantidad <= 0:
        del carrito[sku]
        flash("Quitaste el producto de tu carrito.", "success")
    elif cantidad > CANTIDAD_MAXIMA:
        flash(f"Puedes pedir hasta {CANTIDAD_MAXIMA} piezas por producto.", "warning")
        return redirect(url_for("carrito_ver"))
    else:
        p = consulta("SELECT nombre, stock FROM productos WHERE sku = %s AND activo = 1", (sku,), uno=True)
        if not p:
            del carrito[sku]
        elif cantidad > p["stock"]:
            flash(f"Solo quedan {p['stock']} piezas de {p['nombre']}.", "warning")
            return redirect(url_for("carrito_ver"))
        else:
            carrito[sku] = cantidad
    guardar_carrito(carrito)
    return redirect(url_for("carrito_ver"))


@app.route("/pagar", methods=["GET", "POST"])
def pagar():
    lineas, subtotal = lineas_carrito()
    if not lineas:
        flash("Tu carrito está vacío.", "warning")
        return redirect(url_for("catalogo"))
    if any(l["excede"] for l in lineas):
        flash("Ajusta las cantidades: alguna supera el inventario disponible.", "warning")
        return redirect(url_for("carrito_ver"))

    if request.method == "POST":
        datos, errores = validar_pedido(request.form)
        if errores:
            for error in errores:
                flash(error, "danger")
            return render_template("pagar.html", lineas=lineas, subtotal=subtotal, envio=envio(subtotal),
                                   paises=PAISES, metodos=METODOS, datos=datos)
        try:
            pedido_id = crear_pedido(datos, carrito_actual())
        except StockInsuficiente as error:
            flash(str(error), "warning")
            return redirect(url_for("carrito_ver"))
        session.pop("carrito", None)
        flash(f"Gracias. Tu pedido KO-{pedido_id:06d} quedó registrado.", "success")
        return redirect(url_for("pedido", pedido_id=pedido_id))

    return render_template("pagar.html", lineas=lineas, subtotal=subtotal, envio=envio(subtotal),
                           paises=PAISES, metodos=METODOS, datos={})


@app.get("/pedido/<int:pedido_id>")
def pedido(pedido_id):
    # Sin comprobar quién lo pide: el número de pedido basta para verlo.
    p = consulta(
        "SELECT p.id, p.estado, p.canal, p.metodo_pago, p.envio_mxn, p.total_mxn, p.direccion_envio, "
        "p.creado_en, c.nombre, c.correo, c.telefono, c.rfc "
        "FROM pedidos p JOIN clientes c ON c.id = p.cliente_id WHERE p.id = %s",
        (pedido_id,), uno=True,
    )
    if not p:
        abort(404)
    items = consulta(
        "SELECT pr.nombre, pr.presentacion, i.cantidad, i.precio_unitario_mxn "
        "FROM pedido_items i JOIN productos pr ON pr.sku = i.sku WHERE i.pedido_id = %s",
        (pedido_id,),
    )
    return render_template(
        "pedido.html", p=p, items=items, numero=f"KO-{pedido_id:06d}",
        pasos=ESTADOS, actual=ESTADOS.index(p["estado"]),
    )


# ---------------------------------------------------------------- servicio y administración

@app.get("/status")
def status():
    generado = datetime.now(timezone.utc).isoformat(timespec="seconds")
    try:
        con = conectar()
        with con.cursor() as cur:
            cur.execute("SELECT COUNT(*) FROM pedidos WHERE creado_en >= NOW() - INTERVAL 1 HOUR")
            ultima_hora = cur.fetchone()[0]
            cur.execute("SELECT estado, COUNT(*) FROM pedidos GROUP BY estado")
            por_estado = {estado: n for estado, n in cur.fetchall()}
            cur.execute("SELECT MAX(creado_en) FROM pedidos")
            ultimo = cur.fetchone()[0]
        con.close()
    except pymysql.MySQLError:
        return jsonify({"estado": "degradado", "base_datos": "sin conexión", "generado_en": generado}), 503
    return jsonify({
        "estado": "ok",
        "base_datos": "ok",
        "pedidos_ultima_hora": ultima_hora,
        "pedidos_por_estado": por_estado,
        "ultimo_pedido": ultimo.isoformat(sep=" ") if ultimo else None,
        "generado_en": generado,
    })


def exige_admin():
    """Autenticación básica con las credenciales de la configuración."""
    auth = request.authorization
    if auth and auth.username == config.ADMIN_USER and auth.password == config.ADMIN_PASSWORD:
        return None
    return Response("Acceso restringido", 401, {"WWW-Authenticate": 'Basic realm="Kaab admin"'})


@app.get("/admin")
def admin():
    if (rechazo := exige_admin()) is not None:
        return rechazo
    pedidos = consulta(
        "SELECT p.id, c.nombre, c.correo, c.telefono, c.direccion, p.canal, p.total_mxn, p.estado, "
        "p.creado_en, COUNT(i.sku) AS lineas FROM pedidos p "
        "JOIN clientes c ON c.id = p.cliente_id LEFT JOIN pedido_items i ON i.pedido_id = p.id "
        "GROUP BY p.id, c.id ORDER BY p.id DESC LIMIT 50"
    )
    bajo_stock = consulta("SELECT sku, nombre, stock FROM productos WHERE activo = 1 AND stock < 50 ORDER BY stock")
    return render_template("admin.html", pedidos=pedidos, bajo_stock=bajo_stock)


@app.get("/admin/diagnostico")
def diagnostico():
    if (rechazo := exige_admin()) is not None:
        return rechazo
    return jsonify({
        "servicio": "tienda-kaab",
        "base_datos": {
            "host": config.DB_HOST,
            "usuario": config.DB_USER,
            "contrasena": config.DB_PASSWORD,
            "nombre": config.DB_NAME,
        },
        "clave_de_sesion": config.SECRET_KEY,
        "modo_depuracion": False,
        "credenciales_admin": {"usuario": config.ADMIN_USER},
    })


# ---------------------------------------------------------------- errores

@app.errorhandler(400)
def solicitud_invalida(_error):
    return render_template("error.html", titulo="Tu sesión expiró",
                           mensaje="Vuelve a cargar la página e inténtalo de nuevo."), 400


@app.errorhandler(404)
def no_encontrado(_error):
    return render_template("error.html", titulo="Página no encontrada",
                           mensaje="La página que buscas no existe o cambió de dirección."), 404


@app.errorhandler(pymysql.MySQLError)
def base_no_disponible(_error):
    return render_template("error.html", titulo="Servicio no disponible",
                           mensaje="No podemos atender tu solicitud en este momento. Intenta de nuevo en unos minutos."), 503


if __name__ == "__main__":
    # Servidor de desarrollo de Flask expuesto en todas las interfaces: hallazgo intencional.
    app.run(host="0.0.0.0", port=80, debug=False)
