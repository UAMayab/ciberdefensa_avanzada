# Laboratorio compartido — Módulo I (Anexo A, Sección 7)

Entorno reproducible con Vagrant para las actividades prácticas del Módulo I
que necesitan una VM de laboratorio (A.3, A.4, A.7, A.8), y que también sirve
como red objetivo para A.6 (pfSense). A.1 (ATT&CK Navigator), A.2
(theHarvester/Recon-ng/Shodan) y A.5 (GNS3/EVE-NG) no usan este lab: A.1 es
una app web sin nada que aprovisionar, A.2 trabaja contra un dominio real y
autorizado desde el Kali de la Sección 4 (una VM interna no reproduce
"superficie expuesta en Internet"), y A.5 es un simulador de topologías,
categoría de herramienta distinta a una VM Ubuntu/Alpine con servicios.

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
- ~1.3 GB de RAM libres y ~4 GB de disco para ambas VMs (además de lo que ya
  usan Kali, Metasploitable2/DVWA y pfSense).
- Conexión a Internet solo la primera vez (`vagrant up` descarga las boxes;
  quedan cacheadas localmente para los `vagrant up` siguientes).

## Topología

```
                 Kali (existente, Sección 4 — sin cambios)
                      |                        |
              dmz_net 192.168.56.0/24   lan_net 192.168.57.0/24
              (VirtualBox internal net)  (VirtualBox internal net)
                      |                        |
              dmz-ubuntu (.10)          int-alpine (.10)
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

Cada VM tiene además una interfaz NAT automática de Vagrant (usada solo para
`vagrant ssh` / aprovisionamiento, con reenvío de puerto al host) — no
forma parte de la topología del laboratorio y no debe usarse en las
actividades.

| VM | Box (versión verificada) | Rol | Interfaz NAT (gestión) | Interfaz de laboratorio |
|---|---|---|---|---|
| `dmz-ubuntu` | `cloud-image/ubuntu-24.04` (20260814.0.0) | Host de la DMZ, rico en servicios | `enp0s3` (10.0.2.15) | `enp0s8` → `192.168.56.10` (dmz_net) |
| `int-alpine` | `generic/alpine319` (4.3.12 / Alpine 3.19) | Host mínimo de la LAN interna | `eth0` (10.0.2.15) | `eth1` → `192.168.57.10` (lan_net) |

Recursos por VM: `dmz-ubuntu` 1 vCPU / 1024 MB RAM; `int-alpine` 1 vCPU /
256 MB RAM.

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
  (A.3/A.4).
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

## Mapeo a las actividades

| Actividad | Qué usar de este lab |
|---|---|
| A.3 — Wireshark/tcpdump | Capturar HTTP (`dmz-ubuntu`), DNS, y login FTP (`labdemo`/`labdemo123`) o el banner Telnet en claro de `int-alpine`. |
| A.4 — Nmap | Escanear `192.168.56.10` y `192.168.57.10` desde Kali (agregar adaptador a `dmz_net`/`lan_net`) y comparar perfiles de servicios: `dmz-ubuntu` (22/80/21) vs. `int-alpine` (22/23). |
| A.6 — pfSense | Asignar las interfaces WAN/LAN de pfSense a las redes internas `dmz_net`/`lan_net` de VirtualBox, para tener tráfico real `dmz-ubuntu` ↔ `int-alpine` que filtrar. |
| A.7 — iptables/nftables/UFW | Endurecer `dmz-ubuntu` con `ufw` y `int-alpine` con `iptables`/`nftables`; verificar antes/después con Nmap desde Kali. Endurecer no rompe A.8: Suricata capta el tráfico a nivel de interfaz (AF_PACKET) independientemente de lo que el firewall decida hacer con el paquete. |
| A.8 — Suricata | Configurar y arrancar Suricata en `dmz-ubuntu` sobre `enp0s8`, generar escaneos Nmap/ping desde Kali contra `dmz_net`, revisar alertas. |

## Uso

```bash
cd labs/modulo1
vagrant up                  # levanta ambas VMs (primera vez descarga las boxes)
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

## Notas técnicas y solución de problemas

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
a punta antes de entregar este laboratorio: arranque limpio de ambas VMs sin
errores; IPs estáticas correctas en `dmz_net`/`lan_net`; aislamiento real
entre segmentos (prueba TCP); servicios esperados escuchando en cada VM
(`ss`/`netstat`); login FTP en claro con `labdemo`/`labdemo123` exitoso;
sintaxis del ruleset de Suricata validada (`suricata -T`); y una alerta real
de Suricata disparada con tráfico ICMP y TCP generado desde una segunda VM
en `dmz_net` (probada y luego destruida, no forma parte del laboratorio
final).
