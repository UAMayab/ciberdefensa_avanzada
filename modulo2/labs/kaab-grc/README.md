# Laboratorio · Evaluación de riesgos en Kaab Orgánicos

Entorno reproducible con Vagrant para el **Módulo II — Gobernanza Digital: Gestión de Riesgos y Cumplimiento Normativo**. Simula una empresa mediana, con su tienda en línea, su base de pedidos y su sitio público, para que hagas una evaluación de riesgos completa de principio a fin con la metodología de NIST SP 800-30 Rev. 1.

> ⚠️ **Kaab Orgánicos es una empresa ficticia.** Sus personas, cifras, correos y datos son inventados. El laboratorio se levanta **solo** en la red host-only y las redes internas de VirtualBox, aisladas de tu red y de Internet. No lo expongas a ninguna red de producción.

- **Actividades del estudiante:** [S1 · Marco](actividades/S1-marco/README.md) y [S2 · Evaluar y registrar](actividades/S2-evaluar-y-registrar/README.md)
- **Empresa y cifras:** la app, en la página *Expediente de la empresa*.
- **Material del instructor** (mapa de hallazgos, rúbrica y guía de sesiones): `modulo2/instructor/`, fuera del repositorio a propósito.

---

## Qué hay aquí

| Parte | Qué es | Dónde |
|---|---|---|
| Sitio público de Kaab | Seis páginas: inicio, nosotros, productos, dónde comprar, contacto y aviso de privacidad. Bootstrap 5.3.3 incluido en `provision/site/vendor/` | `grc-console`, `http://192.168.61.10/` |
| Tienda en línea | Catálogo, producto, carrito, datos de envío y pago simulado, seguimiento del pedido, `/status` y panel de administración | `web-dmz`, `http://192.168.61.20/` |
| Base de pedidos | MariaDB con clientes, lotes y pedidos, y un proceso que los avanza por el pipeline | `core-lan`, sin acceso directo desde tu equipo |
| Evaluación de riesgos | La app Streamlit que te guía por las tareas 2-1 a 2-6 | `grc-console`, `http://192.168.61.10:8501` |

El negocio funciona de verdad: el proceso de pedidos crea pedidos y los avanza. Si apagas la base, ningún pedido avanza, y el **Monitor** de la app lo muestra.

## Requisitos

- **Vagrant** ≥ 2.4 y **VirtualBox** ≥ 7.0.
- **Memoria:** las tres máquinas asignan **2.3 GB** en total. En uso real rondan **1 GB**, así que cuenta con unos 2.5 GB libres además de lo que ya uses.
- **Disco:** unos **6 GB** para la imagen base y las tres máquinas.
- **Internet:** solo la primera vez, para descargar la imagen base y los paquetes.

## Topología

```
host (tu equipo)  192.168.61.1
   └── red host-only 192.168.61.0/24  (navegador del estudiante)
        ├── 192.168.61.10   grc-console   sitio público :80 · aplicación :8501
        └── 192.168.61.20   web-dmz       tienda :80 · /status · /admin
                  │ segunda interfaz
   └── red interna core_net 192.168.62.0/24  (solo entre máquinas del laboratorio)
        ├── 192.168.62.10   web-dmz
        └── 192.168.62.20   core-lan      MariaDB :3306 · proceso de pedidos
```

Desde tu equipo solo se llega a `grc-console` y a `web-dmz`. `core-lan` está en la red interna y solo lo alcanza la tienda, a propósito: ver esa conexión es parte de la evaluación.

Cada máquina tiene además una interfaz NAT automática de Vagrant, usada solo para `vagrant ssh` y para instalar paquetes. No forma parte del laboratorio.

| Máquina | Imagen | Memoria | CPU | Rol |
|---|---|---|---|---|
| `core-lan` | `cloud-image/ubuntu-24.04` | 768 MB | 1 | Base de datos y proceso de pedidos |
| `web-dmz` | `cloud-image/ubuntu-24.04` | 512 MB | 1 | Tienda, panel de administración y `/status` |
| `grc-console` | `cloud-image/ubuntu-24.04` | 1024 MB | 1 | Sitio público y la aplicación de evaluación |

## Puesta en marcha

Desde esta carpeta:

```bash
cd modulo2/labs/kaab-grc
vagrant up              # la primera vez descarga la imagen y aprovisiona las tres máquinas
vagrant status          # estado de cada máquina
```

Cuando termine, abre en tu navegador:

- Sitio de la empresa: `http://192.168.61.10/`
- **Aplicación de evaluación:** `http://192.168.61.10:8501`
- Tienda: `http://192.168.61.20/`

Para entrar a una máquina: `vagrant ssh grc-console` (o `web-dmz`, `core-lan`).

### Apagar, reponer y reiniciar

```bash
vagrant halt                        # apaga sin borrar nada: el progreso se conserva
vagrant up                          # lo vuelve a encender
vagrant provision grc-console       # actualiza la aplicación SIN borrar tu progreso
vagrant destroy -f                  # borra las máquinas Y el progreso (ver abajo)
```

> **Tu progreso vive en la máquina virtual.** `vagrant destroy` lo borra. Antes de destruir, descárgalo desde la página **Inicio** de la app (*Descargar progreso*). Para restaurarlo, impórtalo en esa misma página.

## Ejecutar la app sin máquinas virtuales

Si no puedes levantar el laboratorio, puedes correr la aplicación en tu equipo. Algunas páginas (el Monitor) no tendrán datos, y es normal.

```bash
cd modulo2/labs/kaab-grc/app
python3 -m venv .venv
. .venv/bin/activate                 # en Windows: .venv\Scripts\activate
pip install -r requirements.txt
KAAB_GRC_WORKSPACE="$HOME/kaab-grc-progreso.json" streamlit run app.py
```

Abre `http://localhost:8501`.

### Si la red host-only no funciona: túnel SSH

Si tu equipo no alcanza `192.168.61.10` (red corporativa, WSL2 sin ruta), reenvía el puerto a través de `grc-console`:

```bash
vagrant ssh grc-console -- -N -L 8501:127.0.0.1:8501
```

Deja esa terminal abierta y abre `http://127.0.0.1:8501` en tu navegador.

## Mapa a las actividades

| Sesión | Actividad | Qué usa de este laboratorio |
|---|---|---|
| 1 · vie 16 oct | [S1 · Marco](actividades/S1-marco/README.md) | Expediente, Marco y Diagnóstico de la app |
| 2 · sáb 17 oct | [S2 · Evaluar y registrar](actividades/S2-evaluar-y-registrar/README.md) | Evidencia de `grc-console`, Preparar, Activos, Conducir, Registro y Comunicar |
| 3 a 5 | Se publican al avanzar el módulo | Perfiles CSF, controles, simulacro de caída de `core-lan` |

## Estructura del directorio

```
kaab-grc/
├── Vagrantfile              define las tres máquinas y el orden de aprovisionamiento
├── README.md                este archivo
├── actividades/             enunciados del estudiante (S1, S2)
├── app/                     aplicación Streamlit (Evaluación de riesgos)
│   ├── app.py               entrada y navegación
│   ├── pages/               una página por paso del proceso
│   ├── kaab/                lógica: escalas SP 800-30, impacto, tarjetas y validaciones
│   ├── tests/               pruebas: `pytest` (ver abajo)
│   └── requirements.txt     dependencias fijadas
└── provision/               scripts de aprovisionamiento de cada máquina
    ├── core-lan.sh · web-dmz.sh · grc-console.sh
    ├── core/                esquema de la base y proceso de pedidos
    ├── store/               tienda en Flask
    └── site/                sitio público de la empresa
```

Para correr las pruebas de la lógica (sin máquinas):

```bash
cd modulo2/labs/kaab-grc/app
python3 -m venv .venv && . .venv/bin/activate
pip install -r requirements-dev.txt
pytest -q tests
```

## Notas técnicas y solución de problemas

- **Si ya tenías el laboratorio levantado antes de la tienda con carrito**, el esquema de la base cambió. Ejecuta `vagrant destroy -f` y luego `vagrant up`. Antes de destruir, descarga tu progreso desde **Inicio**.
- **La tienda devuelve «Tu sesión expiró»** al enviar un formulario: la sesión se perdió (por ejemplo, borraste las cookies). Vuelve a cargar la página.
- **La tienda responde «Servicio no disponible»**: `core-lan` no responde, y sin base de datos no hay catálogo ni pedidos. Es lo que el Monitor muestra como ventas detenidas.
- **`vagrant up` tarda mucho la primera vez.** Descarga la imagen base de Ubuntu y luego instala paquetes en cada máquina. Las siguientes veces es mucho más rápido.
- **La tienda o el Monitor no responden justo después de `vagrant up`.** El servicio tarda unos segundos en arrancar. Si después de un minuto sigue sin responder, revisa `vagrant ssh web-dmz -- systemctl status tienda-kaab`.
- **La app dice que no hay progreso.** Es normal la primera vez: el archivo se crea cuando guardas algo por primera vez.
- **`vagrant provision grc-console` no cambió nada.** Revisa que el cambio esté guardado en `app/` antes de correrlo. La aplicación se reinicia sola.
- **Ves el mensaje «Fixed port collision for 22 => 2222».** Es normal: Vagrant cambia el puerto SSH cuando otra máquina ya usa el 2222. `vagrant ssh <nombre>` siempre usa el correcto.
- **La advertencia sobre las Guest Additions** no afecta al laboratorio: no usa carpetas compartidas.

## Uso ético

Todo el material y las máquinas de este laboratorio son de uso exclusivamente educativo, en entornos aislados y controlados. Kaab Orgánicos no existe. Ningún ejercicio debe realizarse contra redes, dominios o sistemas de terceros sin autorización explícita por escrito.
