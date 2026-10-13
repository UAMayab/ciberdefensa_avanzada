"""Proceso de pedidos de Kaab Orgánicos (FICTICIO).

Cada ciclo crea de uno a tres pedidos nuevos (con sus líneas y su total) y
avanza los que llevan más de un minuto en su estado actual. Los pedidos de la
tienda en línea que hacen los visitantes entran por la misma tabla, así que el
Monitor refleja también sus compras.

Uso:
  python3 worker.py --sembrar-solo   carga clientes y lotes de ejemplo y termina
  python3 worker.py                  corre en bucle (lo ejecuta el servicio systemd)
"""

import random
import sys
import time
from decimal import Decimal

import pymysql

ESTADOS = ["creado", "pagado", "en_lote", "embarcado", "facturado", "cobrado"]
CANALES = [("tienda_mx", 5), ("tienda_ue", 2), ("distribuidor", 2), ("retail_mx", 1)]
METODOS_PAGO = ["tarjeta_simulada", "transferencia"]
ENVIO_MXN = Decimal("120.00")
ENVIO_GRATIS_DESDE = Decimal("900.00")
CICLO_SEGUNDOS = 20
MINUTOS_EN_ESTADO = 1
NOMBRES = ["Lucía", "Mateo", "Valeria", "Santiago", "Camila", "Diego", "Renata", "Andrés", "Sofía", "Ricardo"]
APELLIDOS = ["Canché", "Pech", "Tun", "Chan", "Ek", "Dzul", "Uc", "Balam", "Can", "Ku"]
PAISES_UE = ["DE", "FR", "ES", "NL", "IT"]
RFC_PUBLICO_GENERAL = "XAXX010101000"  # RFC genérico de público en general


def conectar():
    return pymysql.connect(
        unix_socket="/run/mysqld/mysqld.sock", user="root", database="kaab", autocommit=True,
    )


def sembrar(cur, rng):
    cur.execute("SELECT COUNT(*) FROM clientes")
    if cur.fetchone()[0] == 0:
        for i in range(300):
            nombre = f"{rng.choice(NOMBRES)} {rng.choice(APELLIDOS)}"
            pais = "MX" if i % 16 else rng.choice(PAISES_UE)
            direccion = (
                f"Calle {rng.randint(1, 90)} #{rng.randint(100, 499)}, Mérida, Yucatán, MX"
                if pais == "MX" else f"Calle {rng.randint(1, 90)} #{rng.randint(1, 60)}, Ciudad UE, {pais}"
            )
            cur.execute(
                "INSERT INTO clientes (nombre, correo, telefono, direccion, pais, rfc, creado_en) "
                "VALUES (%s, %s, %s, %s, %s, %s, NOW() - INTERVAL %s DAY)",
                (
                    nombre,
                    f"cliente{i:04d}@ejemplo.invalid",
                    f"999{rng.randint(1000000, 9999999)}",
                    direccion,
                    pais,
                    RFC_PUBLICO_GENERAL if pais == "MX" else None,
                    rng.randint(1, 900),
                ),
            )
    cur.execute("SELECT COUNT(*) FROM lotes")
    if cur.fetchone()[0] == 0:
        productos = ["Miel multiflora 500 g", "Miel de azahar 1 kg", "Propóleo en gotas", "Cera de abeja"]
        for n in range(1, 9):
            cur.execute(
                "INSERT INTO lotes (lote_id, producto, kg, cosecha, estado) "
                "VALUES (%s, %s, %s, CURDATE() - INTERVAL %s DAY, %s)",
                (f"LOT-KAAB-{n:03d}", rng.choice(productos), rng.randint(200, 900), rng.randint(10, 300),
                 rng.choice(["certificado", "liberado", "liberado", "en_proceso"])),
            )


def envio(subtotal):
    return Decimal("0.00") if subtotal >= ENVIO_GRATIS_DESDE else ENVIO_MXN


def ciclo(cur, rng):
    cur.execute("SELECT sku, precio_mxn FROM productos WHERE activo = 1")
    catalogo = cur.fetchall()
    cur.execute("SELECT id, pais, direccion FROM clientes")
    clientes = cur.fetchall()
    cur.execute("SELECT lote_id FROM lotes WHERE estado <> 'en_proceso'")
    lotes = [fila[0] for fila in cur.fetchall()]
    mexicanos = [c for c in clientes if c[1] == "MX"]
    europeos = [c for c in clientes if c[1] != "MX"]

    canales, pesos = zip(*CANALES)
    for _ in range(rng.randint(1, 3)):
        canal = rng.choices(canales, pesos)[0]
        cliente_id, _pais, direccion = rng.choice(europeos if canal == "tienda_ue" and europeos else mexicanos or clientes)
        cantidad_max = 20 if canal in ("distribuidor", "retail_mx") else 3
        elegidos = rng.sample(catalogo, rng.randint(1, min(3, len(catalogo))))
        lineas = [(sku, precio, rng.randint(1, cantidad_max)) for sku, precio in elegidos]
        subtotal = sum(precio * cantidad for _, precio, cantidad in lineas)
        costo_envio = envio(subtotal)

        cur.execute(
            "INSERT INTO pedidos (cliente_id, canal, envio_mxn, total_mxn, metodo_pago, direccion_envio, "
            "estado, creado_en, actualizado_en) VALUES (%s, %s, %s, %s, %s, %s, 'creado', NOW(), NOW())",
            (cliente_id, canal, costo_envio, subtotal + costo_envio, rng.choice(METODOS_PAGO), direccion),
        )
        pedido_id = cur.lastrowid
        for sku, precio, cantidad in lineas:
            cur.execute(
                "INSERT INTO pedido_items (pedido_id, sku, cantidad, precio_unitario_mxn) VALUES (%s, %s, %s, %s)",
                (pedido_id, sku, cantidad, precio),
            )

    # Cada estado pasa al siguiente si lleva más de un minuto sin cambiar.
    for actual, siguiente in zip(ESTADOS, ESTADOS[1:]):
        if siguiente == "en_lote" and lotes:
            cur.execute(
                "UPDATE pedidos SET estado = %s, lote_id = %s, actualizado_en = NOW() "
                "WHERE estado = %s AND actualizado_en < NOW() - INTERVAL %s MINUTE LIMIT 50",
                (siguiente, rng.choice(lotes), actual, MINUTOS_EN_ESTADO),
            )
        else:
            cur.execute(
                "UPDATE pedidos SET estado = %s, actualizado_en = NOW() "
                "WHERE estado = %s AND actualizado_en < NOW() - INTERVAL %s MINUTE LIMIT 50",
                (siguiente, actual, MINUTOS_EN_ESTADO),
            )


def main():
    rng = random.Random()
    conn = conectar()
    cur = conn.cursor()
    sembrar(cur, rng)
    if "--sembrar-solo" in sys.argv:
        return
    while True:
        try:
            ciclo(cur, rng)
        except pymysql.MySQLError as error:
            print(f"[kaab-grc] core-lan: error en el ciclo: {error}", flush=True)
            conn.ping(reconnect=True)
            cur = conn.cursor()
        time.sleep(CICLO_SEGUNDOS)


if __name__ == "__main__":
    main()
