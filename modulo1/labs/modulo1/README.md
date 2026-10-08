# Laboratorio compartido — Módulo I (Anexo A, Sección 7)

Entorno reproducible con Vagrant para las actividades prácticas del Módulo I
que necesitan una VM de laboratorio (A.2, A.3, A.4, A.7, A.8), y que también
sirve como red objetivo para A.6 (pfSense). **A.2 es la actividad que más lo
usa**: el sitio de Nordlys y el DNS de `ns-nordlys` son su objetivo completo,
con 20 banderas — ver
[`actividades/A2-reconocimiento-web/`](actividades/A2-reconocimiento-web/).

A.1 (ATT&CK Navigator) y A.5 (GNS3/EVE-NG) no usan este lab: A.1 es una app web
sin nada que aprovisionar —su material propio está en
[`actividades/A1-attack-navigator/`](actividades/A1-attack-navigator/)— y A.5 es
un simulador de topologías, categoría de herramienta distinta a una VM
Ubuntu/Alpine con servicios.

**Uso exclusivo en laboratorio aislado.** Los servicios y credenciales de
este entorno son deliberadamente inseguros (FTP y Telnet en texto claro,
credenciales de demostración débiles, sin firewall activo por defecto) para
fines pedagógicos — igual que Metasploitable2/DVWA en la Sección 4 del
syllabus. Nunca expongas estas VMs a una red de producción o a Internet (ver
Sección 10 del syllabus, nota de uso ético).

## Requisitos

- **Vagrant** ≥ 2.4 y **VirtualBox** ≥ 7.0 en la máquina del estudiante (el
  mismo hipervisor recomendado en la Sección 4 del syllabus). Probado con
  Vagrant 2.4.9 + VirtualBox 7.2.4.
- ~1.55 GB de RAM libres y ~5 GB de disco para las tres VMs (además de lo que ya
  usan Kali, Metasploitable2/DVWA y pfSense).
- Conexión a Internet solo la primera vez (`vagrant up` descarga las boxes;
  quedan cacheadas localmente para los `vagrant up` siguientes).

## Topología

```
                 Kali (existente, Sección 4 — sin cambios)
                      |                        |
              dmz_net 192.168.56.0/24   lan_net 192.168.57.0/24
              (VirtualBox internal net)  (VirtualBox internal net)
                      |          |             |
        dmz-ubuntu (.10)   ns-nordlys (.20)   int-alpine (.10)
                      |          |
              vboxnet host-only 192.168.58.0/24  →  host (192.168.58.1)
                 (.10 web)   (.20 DNS)
```

`dmz_net` y `lan_net` son **redes internas de VirtualBox** (`intnet`):
solo conectan entre sí las VMs que se les adjuntan explícitamente por
nombre; no tienen acceso al host ni entre ellas por defecto. Verificado en
las pruebas: un `ping` entre `dmz-ubuntu` y `192.168.57.10` parece
responder, pero es un artefacto del motor NAT de VirtualBox (ver
"Notas técnicas" más abajo) — una prueba real a nivel TCP confirma que no
hay ruta entre los dos segmentos. Para que Kali o pfSense participen de un
segmento, hay que agregarles un adaptador **Internal Network** con el mismo
nombre (`dmz_net` o `lan_net`); ver "Integración con Kali / pfSense".

`dmz-ubuntu` tiene **además** un tercer adaptador en una red **host-only**
(`192.168.58.10/24`), para que su sitio web sea accesible desde el navegador
del host en `http://192.168.58.10`. Esto **no** conecta `dmz_net` con el
host: los dos segmentos internos siguen aislados (verificado con `nc -zv`
desde el host a `192.168.56.10:80` y `192.168.57.10:22` — ambos hacen
timeout), y `dmz-ubuntu` no reenvía paquetes entre interfaces
(`net.ipv4.ip_forward = 0`). Ver "Notas técnicas".

Cada VM tiene además una interfaz NAT automática de Vagrant (usada solo para
`vagrant ssh` / aprovisionamiento, con reenvío de puerto al host) — no
forma parte de la topología del laboratorio y no debe usarse en las
actividades.

| VM | Box (versión verificada) | Rol | Interfaz NAT (gestión) | Interfaz de laboratorio | Interfaz host-only |
|---|---|---|---|---|---|
| `dmz-ubuntu` | `cloud-image/ubuntu-24.04` (20260814.0.0) | Host de la DMZ, rico en servicios | `enp0s3` (10.0.2.15) | `enp0s8` → `192.168.56.10` (dmz_net) | `enp0s9` → `192.168.58.10` |
| `int-alpine` | `generic/alpine319` (4.3.12) | Host mínimo de la LAN interna | `eth0` (10.0.2.15) | `eth1` → `192.168.57.10` (lan_net) | — |
| `ns-nordlys` | `generic/alpine319` (4.3.12) | DNS autoritativo de `nordlysai.dk` y resolver del lab | `eth0` (10.0.2.15) | `eth1` → `192.168.56.20` (dmz_net) | `eth2` → `192.168.58.20` |

Recursos por VM: `dmz-ubuntu` 1 vCPU / 1024 MB RAM; `int-alpine` y
`ns-nordlys` 1 vCPU / 256 MB RAM cada una.

## Credenciales de laboratorio

| VM | Usuario | Contraseña | Notas |
|---|---|---|---|
| ambas | `vagrant` | — (clave SSH) | Acceso normal vía `vagrant ssh <nombre>`; Vagrant reemplaza automáticamente la clave insegura por una única generada en el primer `vagrant up`. `sudo` sin contraseña. |
| `dmz-ubuntu` | `labdemo` | `labdemo123` | Cuenta local para el login FTP de demostración (A.3) — vsftpd envía `USER`/`PASS` en texto claro con estas credenciales. Verificado: login exitoso (`230 Login successful.`). |
| `int-alpine` | (Telnet abierto sin cuenta configurada) | — | El Telnet queda accesible por diseño como estado "antes" de A.3/A.4; no hay una cuenta de demostración lista para login — el objetivo es que se vea el servicio expuesto en el escaneo/captura, no necesariamente completar un login. |

No hay contraseña de `root` configurada en ninguna VM; todo el acceso
administrativo es vía `sudo` desde el usuario `vagrant`.

## Servicios aprovisionados

### `dmz-ubuntu`
- `nginx` (HTTP, puerto 80) — arriba, para captura y descubrimiento
  (A.3/A.4) y como objetivo de la actividad de reconocimiento. Sirve el sitio
  corporativo ficticio de «Nordlys AI ApS» (19 páginas + 16 banderas; ver
  «Sitio web de práctica»). Accesible desde el navegador del host en
  `http://192.168.58.10` (interfaz host-only) y desde el propio segmento en
  `http://192.168.56.10`. Un segundo vhost, `dev.nordlysai.dk`, responde solo
  con cabecera `Host` explícita.
- `vsftpd` (puerto 21) con login de usuario local `labdemo`/`labdemo123` —
  envía credenciales en texto claro, visibles en Wireshark (A.3).
- `openssh-server` (puerto 22).
- `ufw` — instalado pero **inactivo** (`Status: inactive`, verificado); los
  estudiantes lo activan y configuran en A.7.
- `suricata` (paquete `1:7.0.3-1build3`) — instalado con un ruleset propio en
  `/etc/suricata/rules/lab-custom.rules` (detecta ping ICMP y patrón de
  escaneo SYN tipo Nmap). El servicio queda **deshabilitado**; los
  estudiantes lo configuran y arrancan durante A.8 (ver "Guía rápida por
  actividad"). `HOME_NET` en `/etc/suricata/suricata.yaml` ya incluye
  `192.168.0.0/16` por defecto — no hace falta tocarlo para que cubra
  `dmz_net`.

Puertos escuchando en `dmz-ubuntu` (confirmado con `ss -tlnp`): `22`, `80`,
`21` en todas las interfaces, más el stub local de `systemd-resolved` en
`127.0.0.53:53`/`127.0.0.54:53` (no expuesto en `dmz_net`).

### `int-alpine`
- `openssh` (puerto 22, vía `sshd`).
- Telnet en texto claro (`busybox telnetd`, puerto 23) — dejado **abierto**
  a propósito como estado "antes" de A.3/A.4, que A.7 debe cerrar o
  restringir.
- `iptables` (1.8.9) y `nftables` (1.0.7) instalados, sin reglas propias —
  Alpine no trae UFW, así que A.7 se resuelve con sintaxis nativa de
  `iptables`/`nftables`.

### `ns-nordlys`
- `bind` (paquete `9.18.37-r0`, puerto 53 TCP/UDP) — autoritativo para `nordlysai.dk` y para la
  zona inversa `56.168.192.in-addr.arpa`, y resolver recursivo con reenvío para
  los tres segmentos del laboratorio. Verificado: resolución directa, inversa y
  salida a Internet a través de él.
- **Transferencia de zona abierta a cualquiera** (`allow-transfer { any; }`) —
  fallo deliberado, es la lección central de la Fase 1 de A.2. Verificado:
  `dig @192.168.58.20 nordlysai.dk AXFR` devuelve la zona completa.
- La zona incluye el espacio de nombres `internal.nordlysai.dk` con los mismos
  hostnames que el sitio web filtra en `.env` y `.git/config`, para que el
  reconocimiento pueda pivotar entre web y DNS en ambas direcciones.
- **`dev.nordlysai.dk` no está en la zona, a propósito**: el sitio afirma que no
  está publicado en DNS, así que sólo se encuentra por fuzzing de la cabecera
  `Host`. Verificado: devuelve NXDOMAIN.

## Sitio web de práctica: «Nordlys AI ApS»

`dmz-ubuntu` sirve un sitio web corporativo **ficticio** que actúa como objetivo
de la actividad de reconocimiento. La empresa no existe: nombre, personas, CVR,
clientes, direcciones y credenciales son inventados, y el sitio solo se sirve en
la red aislada del laboratorio. Cada archivo lleva un comentario HTML que lo
indica, para que nadie lo confunda con un sitio real si se filtra una captura.

**Empresa:** Nordlys AI ApS — consultora de servicios de IA, Ørestads Boulevard 61,
2300 København S (Dinamarca). CVR 41 92 07 65. Dominio ficticio `nordlysai.dk`.

**Acceso:**

| Desde | URL |
|---|---|
| El host (navegador, `curl`, Burp, `nmap`) | `http://192.168.58.10/` |
| Kali u otra VM en `dmz_net` | `http://192.168.56.10/` |
| Entorno de *staging* (requiere cabecera Host) | `curl -H "Host: dev.nordlysai.dk" http://192.168.58.10/` |

**Estructura (19 páginas + artefactos):**

```
/                       portada             /blog/index.html        índice del blog
/services.html          servicios           /blog/*.html            3 artículos
/industries.html        sectores            /contact.html           contacto + formulario
/case-studies.html      índice de casos     /privacy.html           política de privacidad (GDPR)
/cases/*.html           3 casos de cliente  /internal-tools/        portal de empleados
/about.html             datos societarios   /careers/offer-draft-q3.html  borrador no enlazado
/team.html              10 perfiles + correos
/careers.html           4 vacantes          /404.html               página de error propia
```

Artefactos plantados fuera de la navegación: `robots.txt`, `sitemap.xml`,
`.well-known/security.txt`, `.env`, `.git/config`, `index.html.bak` y el
directorio `/uploads/` (con listado activado) que contiene un PDF, un CSV de
personal y un `tar.gz` del «sitio antiguo».

El contenido está en danés, igual que lo estaría el sitio real de una empresa
danesa. No hace falta entenderlo para la actividad: las banderas están en
nombres de archivo, comentarios, cabeceras y metadatos, no en la prosa.

### Configuración deliberadamente débil

Todo lo que un análisis de configuración marcaría como fallo en
`provision/site/nginx-nordlys.conf` es intencionado, y es lo que hace la
actividad posible:

- `server_tokens on` → la versión de nginx aparece en las respuestas.
- `autoindex on` en `/uploads/` → listado de directorio navegable.
- **No** hay `location ~ /\. { deny all; }` → `/.env` y `/.git/config` se sirven.
- Cabeceras `X-Nordlys-Build` y `X-Powered-By` con información de compilación.
- Un vhost de *staging* (`dev.nordlysai.dk`) que responde por cabecera `Host`.
- `ufw` inactivo (los estudiantes lo activan en A.7).

## Sitio y banderas: dónde está el solucionario

El sitio de Nordlys es el objetivo de la **Actividad A.2**, que reparte
**20 banderas** en cuatro fases (DNS, reconocimiento pasivo, enumeración de
contenido y análisis de archivos).

- **Enunciado del estudiante:**
  [`actividades/A2-reconocimiento-web/`](actividades/A2-reconocimiento-web/)
- **Solucionario, reparto de puntos y rotación de banderas entre promociones:**
  `instructor/modulo1/A2-reconocimiento-web/solucionario-banderas.md` — fuera de
  este árbol a propósito, porque `labs/` es lo que ve el estudiante.

### Mantenimiento del sitio

Los archivos del sitio están en `provision/site/` (`www/` es la raíz de
producción, `www-staging/` la del vhost de *staging*, y `nginx-nordlys.conf` la
configuración). Las zonas DNS están en `provision/dns/`. Ambos se suben con el
provisionador `file` de Vagrant, que usa SCP y por tanto **no** depende de las
Guest Additions (las carpetas compartidas no funcionan en estas boxes, ver
«Notas técnicas»).

```bash
vagrant provision dmz-ubuntu     # vuelve a desplegar el sitio tras editarlo
vagrant provision ns-nordlys     # vuelve a cargar las zonas DNS
```

Al editar una zona DNS hay que **subir el número de serie** (`AAAAMMDDNN`) o los
resolvers seguirán sirviendo la versión anterior desde caché.

Tras cualquier cambio, comprobar contra `http://192.168.58.10/` y
`dig @192.168.58.20 nordlysai.dk`.
## Mapeo a las actividades

| Actividad | Qué usar de este lab |
|---|---|
| A.2 — Reconocimiento web y DNS | Dominio `nordlysai.dk` servido por `ns-nordlys` (`192.168.56.20`, o `192.168.58.20` desde el host) y sitio ficticio «Nordlys AI ApS» en `dmz-ubuntu`: 20 banderas en cuatro fases, de la transferencia de zona a los metadatos de un PDF y el fuzzing de la cabecera `Host`. Ver [`actividades/A2-reconocimiento-web/`](actividades/A2-reconocimiento-web/). |
| A.3 — Wireshark/tcpdump | Capturar HTTP (`dmz-ubuntu`), DNS, y login FTP (`labdemo`/`labdemo123`) o el banner Telnet en claro de `int-alpine`. |
| A.4 — Nmap | Escanear `192.168.56.10` y `192.168.57.10` desde Kali (agregar adaptador a `dmz_net`/`lan_net`) y comparar perfiles de servicios: `dmz-ubuntu` (22/80/21) vs. `int-alpine` (22/23). Sin Kali, `dmz-ubuntu` se puede escanear desde el host en `192.168.58.10` (mismo perfil 21/22/80). |
| A.6 — pfSense | Asignar las interfaces WAN/LAN de pfSense a las redes internas `dmz_net`/`lan_net` de VirtualBox, para tener tráfico real `dmz-ubuntu` ↔ `int-alpine` que filtrar. |
| A.7 — iptables/nftables/UFW | Endurecer `dmz-ubuntu` con `ufw` y `int-alpine` con `iptables`/`nftables`; verificar antes/después con Nmap desde Kali. Endurecer no rompe A.8: Suricata capta el tráfico a nivel de interfaz (AF_PACKET) independientemente de lo que el firewall decida hacer con el paquete. |
| A.8 — Suricata | Configurar y arrancar Suricata en `dmz-ubuntu` sobre `enp0s8`, generar escaneos Nmap/ping desde Kali contra `dmz_net`, revisar alertas. |

## Uso

```bash
cd labs/modulo1
vagrant up                  # levanta las tres VMs (primera vez descarga las boxes)
vagrant status               # estado de las VMs
vagrant ssh dmz-ubuntu
vagrant ssh int-alpine
vagrant ssh-config           # ver puertos SSH reenviados al host (pueden variar si hay colisión)
vagrant halt                 # apaga sin destruir — conserva el estado entre sesiones del curso
vagrant reload --provision   # reinicia y vuelve a correr el aprovisionamiento (restaura el estado "antes")
vagrant destroy -f           # limpia todo — para reiniciar el lab desde cero
```

Nota: Vagrant reasigna automáticamente el puerto SSH reenviado al host si
hay colisión con otra VM (por ejemplo, si Kali/Metasploitable/pfSense ya
usan el `2222` por defecto) — verificado en pruebas: `int-alpine` quedó en
el puerto `2200` en vez de `2222`. Esto es normal y no afecta el
funcionamiento; `vagrant ssh <nombre>` siempre usa el puerto correcto
automáticamente.

## Guía rápida por actividad

**A.3 (Wireshark/tcpdump) —** desde Kali (adjunta un adaptador a `dmz_net`):
```
tcpdump -i <interfaz> host 192.168.56.10 -w captura.pcap
ftp 192.168.56.10          # usuario labdemo / clave labdemo123 → visible en claro
telnet 192.168.57.10       # banner de int-alpine en claro
```

**A.4 (Nmap) —** desde Kali:
```
nmap -sV -O 192.168.56.10   # dmz-ubuntu: 22, 80, 21
nmap -sV -O 192.168.57.10   # int-alpine: 22, 23
```

**A.7 (firewall de host) —** referencia de sintaxis, no la solución del
ejercicio (que consiste en decidir qué permitir y documentarlo):
```
# dmz-ubuntu (UFW)
sudo ufw default deny incoming
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw enable

# int-alpine (iptables crudo, ya que no hay UFW en Alpine)
sudo iptables -A INPUT -p tcp --dport 22 -j ACCEPT
sudo iptables -A INPUT -j DROP
sudo /etc/init.d/iptables save   # persistir reglas
```

**A.8 (Suricata) —** comando verificado en pruebas (corre en foreground,
usa `Ctrl+C` para detener; para producción a largo plazo, agregar
`af-packet: interface: enp0s8` en `/etc/suricata/suricata.yaml` y usar
`systemctl start suricata` en su lugar):
```
sudo suricata -c /etc/suricata/suricata.yaml \
  -S /etc/suricata/rules/lab-custom.rules \
  -i enp0s8 -l /var/log/suricata --runmode single
```
Alertas en `/var/log/suricata/eve.json` (`event_type: alert`). Verificado
con tráfico real desde una segunda VM en `dmz_net`: dispara
`LAB ICMP ping detectado` (ambas direcciones) y `LAB posible escaneo Nmap
SYN` ante una ráfaga de conexiones TCP — además Suricata detecta
automáticamente los flujos de aplicación FTP y SSH sin reglas adicionales.

## Integración con Kali / pfSense

Para que Kali (o pfSense) alcance `dmz_net` y/o `lan_net`, agrégales un
adaptador **Internal Network** con el mismo nombre exacto (`dmz_net` o
`lan_net`) que usa este Vagrantfile. Con la VM apagada:

```bash
VBoxManage modifyvm "<nombre-VM-Kali>" --nic3 intnet --intnet3 dmz_net
# repetir con --nic4 / --intnet4 lan_net si también necesita esa red
```

O, desde la interfaz gráfica de VirtualBox: *Configuración → Red →
Adaptador N → Conectado a: Red interna → Nombre: `dmz_net`* (o `lan_net`).
Luego, dentro de Kali, configura la IP de esa interfaz manualmente (por
ejemplo `192.168.56.50/24` para `dmz_net`, evitando `.10` y `.99` que ya
están en uso).

Para pfSense (A.6): asigna su interfaz WAN a `dmz_net` y su interfaz LAN a
`lan_net` de la misma forma, para tener una frontera real entre `dmz-ubuntu`
y `int-alpine` que las reglas de pfSense puedan filtrar.

## Acceso desde el sistema anfitrión (sin Kali)

No hace falta Kali para trabajar contra este laboratorio: **la red host-only
`192.168.58.0/24` ya existe precisamente para eso**. VirtualBox crea la
interfaz `vboxnetN` con `192.168.58.1` en el anfitrión, y desde ahí se
alcanzan los dos servicios que necesita A.2 sin configurar nada. Esto
funciona igual en Linux, macOS y Windows, porque el adaptador host-only lo
gestiona VirtualBox, no el sistema operativo.

| Desde el anfitrión | Destino | Estado |
|---|---|---|
| Sitio web de Nordlys | `http://192.168.58.10` | Alcanzable |
| DNS autoritativo (incluida la transferencia de zona) | `192.168.58.20:53` | Alcanzable |
| `dmz_net` (`192.168.56.0/24`) | — | **No alcanzable, por diseño** |
| `lan_net` (`192.168.57.0/24`), `int-alpine` | — | **No alcanzable, por diseño** |

Comprobación rápida de que el anfitrión tiene acceso, antes de empezar:

```bash
curl -sI http://192.168.58.10/ | head -1      # -> HTTP/1.1 200 OK
dig +short @192.168.58.20 www.nordlysai.dk    # -> 192.168.56.10
```

### El detalle que hay que entender: los nombres apuntan a una red que el anfitrión no ve

Es el punto donde se atasca todo el mundo. La zona `nordlysai.dk` es
coherente con la topología **interna** del laboratorio, así que
`www.nordlysai.dk` resuelve a `192.168.56.10` — una dirección de `dmz_net`,
que el anfitrión no alcanza. Consecuencia: el DNS responde perfectamente,
pero navegar al nombre resuelto da *timeout*. No está roto; es el
aislamiento funcionando.

Hay dos formas de convivir con ello:

1. **Trabajar por IP host-only y forzar el nombre en la cabecera `Host`.**
   Es lo que ya hace [`guia-herramientas.md`](actividades/A2-reconocimiento-web/guia-herramientas.md),
   y es la opción que no toca nada del sistema:
   ```bash
   curl -s -H "Host: www.nordlysai.dk" http://192.168.58.10/
   ```
2. **Añadir los nombres al archivo `hosts` del anfitrión**, apuntándolos a
   `192.168.58.10`. Más cómodo para el navegador y para `whatweb`/`nikto`,
   que no siempre dejan fijar la cabecera con comodidad.

| Sistema | Archivo `hosts` | Cómo editarlo |
|---|---|---|
| Linux | `/etc/hosts` | `sudo nano /etc/hosts` |
| macOS | `/etc/hosts` | `sudo nano /etc/hosts` |
| Windows | `C:\Windows\System32\drivers\etc\hosts` | Bloc de notas **como Administrador** (botón derecho → *Ejecutar como administrador*) |

Entradas a añadir:

```
192.168.58.10   nordlysai.dk www.nordlysai.dk api.nordlysai.dk smtp.nordlysai.dk stats.nordlysai.dk
192.168.58.20   ns1.nordlysai.dk
```

> **Dos advertencias.** Primera: haz esto **después** de la Fase 1, no antes
> — los nombres son justamente lo que la transferencia de zona tiene que
> revelarte; escribirlos a mano antes es saltarse el ejercicio. Segunda:
> **no añadas `dev.nordlysai.dk`**. Que ese nombre no exista en el DNS es el
> diseño de la bandera 20, y sólo se encuentra fuzzeando la cabecera `Host`;
> ponerlo en el archivo `hosts` destruye la bandera.
>
> Los nombres bajo `internal.nordlysai.dk` (`db-prod-01`, `git`, `wiki`…)
> **no** van al archivo `hosts`: apuntan a máquinas que no existen. Son
> hallazgos del reconocimiento —superficie interna filtrada por un DNS mal
> configurado—, no objetivos a los que conectarse.

### Herramientas en el anfitrión

Las diez herramientas de A.2 están en Kali por defecto; en un anfitrión hay
que instalarlas. El mínimo imprescindible es `dig` (Fase 1) y `curl`
(Fases 2–4): con esos dos se sacan la mayoría de las banderas.

| Sistema | Instalación |
|---|---|
| Linux (Debian/Ubuntu) | `sudo apt install dnsutils curl whatweb nikto exiftool dnsrecon cewl` — `ffuf` y `feroxbuster` sólo están en repos recientes; si no, descarga el binario de su página de releases |
| Linux (Fedora/RHEL) | `sudo dnf install bind-utils curl perl-Image-ExifTool nikto` |
| macOS | `curl` viene de serie. El resto con [Homebrew](https://brew.sh): `brew install bind curl whatweb nikto ffuf feroxbuster exiftool`. Comprueba primero si ya tienes `dig` (`dig -v`); si no, lo aporta el paquete `bind` |
| Windows | `curl.exe` viene de serie (Windows 10 1803 en adelante). Para `dig` y el resto, lo práctico es **WSL2** con Ubuntu y luego la fila de Debian/Ubuntu — ver la nota de abajo |

> **Windows, con honestidad.** `nslookup` y `Resolve-DnsName` sirven para
> consultas normales, pero la transferencia de zona de la Fase 1 se hace mal
> o no se hace con ellos, y `whatweb`/`nikto`/`ffuf` no tienen equivalente
> nativo cómodo. WSL2 resuelve las herramientas, pero **no está verificado
> que WSL2 alcance la red host-only de VirtualBox** en todas las
> configuraciones: su red está detrás de un NAT propio. Pruébalo con un solo
> comando antes de montar nada encima:
>
> ```bash
> curl -sI --max-time 5 http://192.168.58.10/ | head -1
> ```
>
> Si responde `200 OK`, sigue por WSL2 con normalidad. Si da *timeout*, usa
> el túnel SSH de abajo, activa el modo de red *mirrored* de WSL2, o haz A.2
> desde Kali como está previsto en el diseño original.

### Si la red host-only no funciona: túnel SSH

Sirve de plan B universal —portátil corporativo con la red capada,
adaptador host-only que no levanta, WSL2 sin ruta— y funciona igual en los
tres sistemas, porque lo único que necesita es `vagrant`. Deja el comando
corriendo en una terminal y trabaja desde otra:

```bash
vagrant ssh dmz-ubuntu -- -N -L 8080:192.168.56.10:80    # web  -> http://127.0.0.1:8080
vagrant ssh dmz-ubuntu -- -N -L 5353:192.168.56.20:53    # DNS  -> 127.0.0.1:5353
```

El túnel entra por `dmz-ubuntu`, que sí está en `dmz_net`, así que aquí los
destinos son las IPs **internas** (`192.168.56.x`), no las host-only.

```bash
curl -s -H "Host: www.nordlysai.dk" http://127.0.0.1:8080/
dig +tcp @127.0.0.1 -p 5353 nordlysai.dk AXFR
```

> **`+tcp` no es opcional.** El reenvío `-L` de SSH sólo transporta TCP, y
> una consulta DNS normal va por UDP: sin `+tcp` el `dig` hace *timeout* por
> el túnel aunque el servidor esté perfectamente. La transferencia de zona
> (`AXFR`) ya usa TCP de por sí, así que ésa funciona en cualquier caso.

### Qué se puede hacer desde el anfitrión y qué no

| Actividad | Desde el anfitrión |
|---|---|
| **A.2** — Reconocimiento web y DNS | **Completa, las 20 banderas.** Es la actividad pensada para este camino: DNS en `192.168.58.20`, web en `192.168.58.10` |
| **A.4** — Nmap | **Parcial.** `nmap 192.168.58.10` da el mismo perfil de servicios (21/22/80), pero `int-alpine` no es alcanzable y el contraste DMZ vs. LAN —que es el objetivo de la actividad— se pierde |
| **A.3** — Wireshark/tcpdump | **No.** Hay que capturar *dentro* de un segmento del laboratorio. Alternativa sin Kali: `vagrant ssh dmz-ubuntu` y capturar ahí con `tcpdump -i enp0s8 -w captura.pcap`, luego copiar el `.pcap` al anfitrión para abrirlo con Wireshark |
| **A.6, A.7, A.8** | **No.** pfSense, los firewalls de host y Suricata operan sobre las interfaces internas; necesitan Kali o pfSense adjuntos a `dmz_net`/`lan_net` como se describe en la sección anterior |

## Notas técnicas y solución de problemas

- **`dmz-ubuntu` tiene un tercer adaptador host-only (`192.168.58.10`).**
  Las redes `intnet` de VirtualBox no son accesibles desde el host por
  diseño, así que el sitio web de la DMZ no se puede abrir en el navegador
  del host a través de `192.168.56.10`. El adaptador host-only resuelve eso
  sin romper el aislamiento del laboratorio: el host alcanza
  `http://192.168.58.10` (VirtualBox crea la interfaz `vboxnetN` con
  `192.168.58.1` en el host), pero no `dmz_net` ni `lan_net`. Se eligió
  `192.168.58.0/24` porque cae dentro del rango `192.168.56.0/21` que
  VirtualBox permite por defecto para redes host-only, y no choca con las
  interfaces `vboxnet` que suelen existir en `192.168.56.1`. Efecto
  secundario a tener en cuenta en A.4: `dmz-ubuntu` expone tres interfaces
  en lugar de dos, y sus servicios responden también en `192.168.58.10`.
  Alternativa sin adaptador extra, si se prefiere conservar el perfil de
  dos interfaces: túnel SSH desde el host,
  `vagrant ssh dmz-ubuntu -- -N -L 8080:192.168.56.10:80`
  y abrir `http://127.0.0.1:8080`.
- **Un `ping` entre `dmz_net` y `lan_net` "responde" aunque las redes están
  aisladas.** Es un artefacto conocido del motor NAT de VirtualBox: al no
  existir ruta real hacia el otro segmento, el paquete sale por la interfaz
  NAT por defecto (`ip route get` lo confirma) y el motor NAT responde con
  un eco sintético (`ttl=255`, payload corrupto, avisos de reloj tipo
  "time of day goes back"). Una prueba TCP real (`nc -zv <ip> <puerto>`)
  confirma el aislamiento correcto: la conexión hace timeout. No es un
  bug del laboratorio — no lo interpretes como que las redes están
  conectadas.
- **Advertencia "Guest Additions... do not match"** al hacer `vagrant up`
  de `int-alpine`: es informativa, no un error; no afecta a este
  laboratorio (no usa carpetas compartidas).
- **`Fixed port collision for 22 => 2222. Now on port 2200`**: normal
  cuando ya hay otra VM usando ese puerto en el host; Vagrant lo reasigna
  solo. Usa `vagrant ssh-config` si necesitas el puerto exacto.
- Las boxes **no están fijadas a una versión exacta** en el Vagrantfile a
  propósito, para que cada estudiante obtenga siempre la imagen Ubuntu
  LTS/Alpine más reciente disponible en Vagrant Cloud al momento de su
  primer `vagrant up`. Si algo se rompe por un cambio en una versión nueva
  de la box, se puede fijar temporalmente agregando `dmz.vm.box_version` /
  `lan.vm.box_version` con la versión verificada de la tabla de arriba.

## Verificado en pruebas (referencia)

`vagrant up`, aprovisionamiento y los siguientes puntos se probaron de punta
a punta antes de entregar este laboratorio: arranque limpio de las VMs sin
errores; IPs estáticas correctas en `dmz_net`/`lan_net`; aislamiento real
entre segmentos (prueba TCP); servicios esperados escuchando en cada VM
(`ss`/`netstat`); login FTP en claro con `labdemo`/`labdemo123` exitoso;
sintaxis del ruleset de Suricata validada (`suricata -T`); y una alerta real
de Suricata disparada con tráfico ICMP y TCP generado desde una segunda VM
en `dmz_net` (probada y luego destruida, no forma parte del laboratorio
final).

**`ns-nordlys` y las 20 banderas (18 de septiembre de 2026).** Aprovisionamiento
limpio con ambas zonas cargadas (`named-checkzone` en verde); resolución directa
de los seis nombres públicos y del MX; los registros TXT de SPF, DMARC y
verificación de proveedor; **transferencia de zona completa** en la zona directa
y en la inversa; resolución inversa devolviendo los PTR esperados; recursión a
Internet a través de `ns-nordlys` (para que Kali pueda usarlo como resolver
único); y la comprobación **negativa** de que `dev.nordlysai.dk` responde
NXDOMAIN, que es lo que sostiene el diseño de la bandera 20. `ip_forward = 0` y
el aislamiento entre `dmz_net` y `lan_net` siguen intactos tras añadir la VM.
Las **20 banderas verificadas una por una** con las herramientas declaradas:
20/20.
