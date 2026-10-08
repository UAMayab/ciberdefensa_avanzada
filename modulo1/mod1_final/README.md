# Entregable final — Módulo I · Objetivo de pentest «Aseguradora Delphos»

Laboratorio para el **entregable final del Módulo I**: una prueba de penetración
completa (reconocimiento → escaneo → enumeración → detección de vulnerabilidades
→ explotación) contra un objetivo realista, cuyo resultado —un informe
profesional— se convierte en la lista de riesgos que el **Diseño de Topología de
Red Segura** (entregable oficial de la insignia) debe mitigar.

- **Enunciado del estudiante:** [`enunciado-pentest-delphos.md`](enunciado-pentest-delphos.md)
- **Guía de herramientas (OpenVAS/Metasploit):** [`plantillas/guia-openvas-metasploit.md`](plantillas/guia-openvas-metasploit.md)
- **Plantilla del informe:** [`plantillas/plantilla-informe-pentest.md`](plantillas/plantilla-informe-pentest.md)
- Material de instructor (solucionario, rúbrica, checklist): en
  `instructor/modulo1/final-pentest-delphos/` (fuera de este repositorio).

> ⚠️ **Entorno ficticio y deliberadamente vulnerable.** *Aseguradora Delphos* y
> todos sus datos (personas, CURP, RFC, pólizas, credenciales) son inventados.
> Levántalo **sólo** en la red host-only aislada de VirtualBox. Nunca lo
> expongas a Internet ni a una red de producción.

---

## Qué es

Una sola VM (`delphos-target`, Ubuntu 24.04) que levanta, en contenedores
Docker, los servicios que *Aseguradora Delphos* consolidó por error en su único
servidor perimetral. El estudiante ataca desde **su propio Kali**, conectado a
la misma red host-only.

El hecho de que todo esté en un solo host, sin segmentación, es intencional: es
el hallazgo de arquitectura que da sentido al puente con la topología segura.

## Topología

```
   HOST (tu equipo)
   └── Red host-only VirtualBox  192.168.59.0/24
        ├── 192.168.59.1   (el host; puedes abrir el sitio en el navegador)
        ├── 192.168.59.10  delphos-target  ← OBJETIVO
        └── 192.168.59.20  tu Kali (lo conectas tú, ver abajo)
```

## Mapa de servicios del objetivo (`192.168.59.10`)

| Puerto | Servicio | Qué representa en Delphos |
|---|---|---|
| 80   | nginx — sitio corporativo | cara pública; superficie de reconocimiento |
| 8080 | Apache Tomcat | app de siniestros (Java) |
| 8083 | Jenkins | integración continua del equipo de TI |
| 6379 | Redis | caché de sesiones del portal |
| 3306 | MariaDB | base de pólizas y clientes |

*(Si está habilitado, un Drupal en 8081 representa el portal viejo de clientes.)*

---

## Puesta en marcha

Requisitos en el host: VirtualBox 7.x y Vagrant. La primera vez descarga ~1.5 GB
de imágenes Docker, así que hazlo con tiempo e Internet.

```bash
cd mod1_final
vagrant up
# Al terminar verás un bloque "=== Objetivo listo ===".
```

Comprobar que los servicios están arriba:

```bash
vagrant ssh -c "docker ps --format '{{.Names}}\t{{.Status}}'"
```

Apagar / reponer:

```bash
vagrant halt         # apaga sin perder estado
vagrant destroy -f   # borra; la siguiente 'vagrant up' reconstruye desde cero
```

---

## Cómo conectar Kali al objetivo

El objetivo vive en una red **host-only** de VirtualBox (subred
`192.168.59.0/24`). Tu Kali tiene que estar en esa misma red:

1. Con el objetivo ya levantado, en **VirtualBox → Herramientas → Redes
   host-only** localiza la red cuya IPv4 es `192.168.59.1` (la creó Vagrant).
2. **Apaga tu Kali.** En **Configuración → Red → Adaptador 2**: actívalo,
   «Conectado a: **Adaptador sólo-anfitrión**», y elige esa red.
   (Deja el Adaptador 1 en NAT para que Kali conserve Internet.)
3. Arranca Kali y dale una IP en esa red, por ejemplo:
   ```bash
   sudo ip addr add 192.168.59.20/24 dev eth1   # ajusta el nombre de la interfaz
   sudo ip link set eth1 up
   ```
4. Comprueba:
   ```bash
   ping -c2 192.168.59.10
   ```

Desde el propio host también puedes abrir `http://192.168.59.10/` en el
navegador (el host es `192.168.59.1` en esa red).

---

## Estructura

```
mod1_final/
├── Vagrantfile                      # define delphos-target (host-only .10)
├── README.md                        # este archivo
├── enunciado-pentest-delphos.md     # el enunciado del estudiante
├── plantillas/
│   ├── guia-openvas-metasploit.md   # primer de las dos herramientas nuevas
│   └── plantilla-informe-pentest.md # esqueleto del informe a entregar
└── provision/
    ├── setup-delphos.sh             # instala Docker y levanta el compose
    ├── docker-compose.yml           # los cinco servicios vulnerables
    ├── nginx-default.conf           # sirve el sitio; autoindex en /backups/
    ├── web/                         # sitio corporativo ficticio + artefactos
    │   └── backups/                 # config.old y notas de migración filtradas
    ├── tomcat/                      # tomcat-users.xml + context.xml del manager
    └── mariadb/
        └── init.sql                 # esquema + datos personales ficticios
```

## Nota técnica reutilizable

El box `cloud-image/ubuntu-24.04` no monta la carpeta `/vagrant` (sus Guest
Additions no cuadran con VirtualBox 7.2). Por eso el material se sube con el
provisionador `file` (SCP), declarado **antes** del `shell` que lo instala. El
mismo patrón que el laboratorio de `labs/modulo1/`.
