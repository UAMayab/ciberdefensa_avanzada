# Laboratorio · Inteligencia de amenazas con OpenCTI

Entorno reproducible con Vagrant para el **Módulo II**: una máquina virtual con Ubuntu 26.04 LTS y la plataforma de inteligencia de amenazas [OpenCTI](https://filigran.io/platforms/opencti/), instalada con Docker siguiendo la [guía oficial](https://docs.opencti.io/latest/deployment/installation/#using-docker).

> ⚠️ El laboratorio se levanta **solo** en la red host-only de VirtualBox. No lo expongas a ninguna red de producción: las credenciales de este documento son públicas.

## Acceso

| Servicio | URL | Usuario | Contraseña |
|---|---|---|---|
| **OpenCTI** | `http://192.168.63.10:8080` | `admin@cti.lab` | `UAM-Ciberdefensa-2026` |
| XTM One (asistente de Filigran, incluido en la pila oficial) | `http://192.168.63.10:8090` | `admin@cti.lab` | `UAM-Ciberdefensa-2026` |

Los demás secretos (token de API del administrador, claves de RabbitMQ y S3, clave de cifrado) se generan al azar en cada máquina y quedan en `/opt/opencti/.env`. Para ver el token de API, por ejemplo para usar `pycti`:

```bash
vagrant ssh -c "grep OPENCTI_ADMIN_TOKEN /opt/opencti/.env"
```

## Requisitos

- **Vagrant** ≥ 2.4 y **VirtualBox** ≥ 7.0.
- **Memoria:** la máquina asigna **8 GB**. En uso real ocupa unos 7 GB, así que cuenta con 8.5 GB libres en tu equipo.
- **CPU:** 4 núcleos.
- **Disco:** unos **25 GB** (imagen base y ~20 imágenes de Docker).
- **Internet:** la primera vez descarga la imagen de Ubuntu (2.2 GB) y las imágenes de Docker.

## Puesta en marcha

```bash
cd modulo2/labs/cti
vagrant up          # la primera vez tarda ~35 min: instala Docker y descarga las imágenes
```

Termina cuando imprime `[cti] OpenCTI listo en http://192.168.63.10:8080`. Abre esa dirección y entra con las credenciales de arriba.

**La base de conocimiento se carga en segundo plano.** Al arrancar por primera vez, dos conectores importan datos de referencia:

- *OpenCTI Datasets*: países, regiones y sectores. Tarda unos minutos.
- *MITRE ATT&CK*: técnicas, grupos, malware, herramientas y mitigaciones. Son unos 30 000 paquetes y tarda **varias horas**. La plataforma se puede usar mientras tanto, y los objetos aparecen poco a poco.

Levanta la máquina con tiempo, por ejemplo la víspera, y déjala encendida hasta que termine. Si la apagas antes, el conector de MITRE vuelve a enviar todo al encender. No se duplica nada, pero la carga empieza de nuevo.

El avance se ve en OpenCTI, en el menú **Integrations → Deployed** (bundles en cola y estado de cada conector).

```bash
vagrant halt        # apaga sin borrar nada: los datos se conservan
vagrant up          # la vuelve a encender (2–3 min hasta que OpenCTI responde)
vagrant provision   # vuelve a aplicar la instalación SIN regenerar secretos ni borrar datos
vagrant destroy -f  # borra la máquina y todos los datos
```

## Topología

```
host (tu equipo)  192.168.63.1
   └── red host-only 192.168.63.0/24
        └── 192.168.63.10   opencti   OpenCTI :8080 · XTM One :8090 · S3 (Silo) :9000
```

La máquina tiene además la interfaz NAT automática de Vagrant, usada para `vagrant ssh` y para descargar paquetes e imágenes.

Dentro de la máquina, la pila oficial (`/opt/opencti/docker-compose.yml`, clonada de [OpenCTI-Platform/docker](https://github.com/OpenCTI-Platform/docker)) corre como el proyecto de Compose `xtm`:

| Capa | Contenedores |
|---|---|
| Dependencias | Elasticsearch, Redis, RabbitMQ, Silo (S3), PostgreSQL (para XTM One) |
| OpenCTI | plataforma, 3 workers |
| Conectores | exportar STIX/CSV/TXT, importar STIX/documentos/YARA/referencias externas, análisis, OpenCTI Datasets, MITRE ATT&CK |
| Otros | XTM One y su worker, XTM Composer |

```bash
vagrant ssh
cd /opt/opencti
docker compose ps                  # estado de cada contenedor
docker compose logs -f opencti     # registro de la plataforma
```

## Solución de problemas

- **«Too many requests» al iniciar sesión.** OpenCTI limita los intentos fallidos. Espera un minuto y vuelve a intentarlo.
- **Los conectores *MITRE ATT&CK* y *OpenCTI Datasets* muestran un `KeyError: 'name'` en su registro** al primer arranque. Es un error de `pycti` 7.261016.0 al crear su cuenta de servicio. Docker los reinicia solos y en el segundo intento funcionan. No hay que hacer nada.
- **`vagrant up` falla esperando a OpenCTI.** Revisa el registro: `vagrant ssh -c "cd /opt/opencti && docker compose logs --tail 50 opencti"`.
- **Versiones.** La guía oficial usa las imágenes `latest`, así que cada `vagrant up` desde cero instala la versión más reciente de OpenCTI. Este laboratorio se probó con OpenCTI **7.261016.0**.
- **XTM One** viene en la pila oficial. Sus funciones de IA requieren licencia Enterprise y un proveedor de LLM, así que en este laboratorio no se usa.
- **La advertencia sobre las Guest Additions** no afecta al laboratorio: no usa carpetas compartidas.

## Uso ético

Todo el material y las máquinas de este laboratorio son de uso exclusivamente educativo, en entornos aislados y controlados. Ningún ejercicio debe realizarse contra redes, dominios o sistemas de terceros sin autorización explícita por escrito.
