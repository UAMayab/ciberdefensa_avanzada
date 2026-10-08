# Miniworkshop — Nmap de cero a experto

> **Serie de miniworkshops · Certificación en Ciberdefensa Avanzada**
> Universidad Anáhuac Mayab

Tres episodios y un apéndice para pasar de «sé que Nmap escanea puertos» a
«escribo mis propios scripts NSE y sé explicar cada paquete que sale de mi
máquina».

**Esta serie no se califica.** No hay entregable, ni fecha límite, ni rúbrica.
Se recorre al ritmo de cada quien, y su único objetivo es que la herramienta
deje de ser una caja negra.

| | |
|---|---|
| Herramienta | Nmap 7.94 (verificado con 7.94 y 7.94SVN) |
| Laboratorio | 2 VMs Alpine Linux 3.19 vía Vagrant + VirtualBox |
| Duración | ~80 + ~90 + ~110 min, más ~40 del apéndice |
| Requisitos previos | Saber qué es una dirección IP y un puerto. Nada más. |

## Los episodios

| # | Episodio | Qué sale de ahí |
|---|---|---|
| 1 | [Quién está ahí y qué puerta está abierta](episodios/episodio-1-quien-esta-ahi.md) | Descubrimiento de hosts, los cinco estados de puerto, `-sS` vs `-sT`, `--reason`, `--packet-trace` |
| 2 | [Qué hay detrás de la puerta](episodios/episodio-2-detras-de-la-puerta.md) | `-sV`, `-O`, `-sU`, `-sA`, escaneos sigilosos, tiempos y evasión vista desde el defensor |
| 3 | [Preguntarle al servicio, y escribir tu propia pregunta](episodios/episodio-3-nse-de-usuario-a-autor.md) | NSE completo, vulnerabilidades de los siete servicios, y tu propio script en Lua |
| A | [Apéndice — tcpdump](apendices/apendice-a-tcpdump.md) | Leer el tráfico que tus propios escaneos generan |

**Apoyo visual:** animación del intercambio de banderas TCP en los ocho
escenarios que producen los episodios, paquete a paquete. Los ocho salen de
capturas reales de este laboratorio.

- En el repositorio: [`web/handshake-tcp.html`](web/handshake-tcp.html) —
  ábrela directamente en el navegador; no necesita servidor ni Internet.
- En línea, para compartir: <https://claude.ai/artifact/Y1NXTe7xxQshcs1kJhwQYA>

Tenla a un lado mientras haces los ejercicios.

## Levantar el laboratorio

```bash
cd labs/miniworkshops/nmap
vagrant up
```

Tarda unos minutos la primera vez (descarga la box de Alpine; después queda
cacheada). Para comprobar que quedó bien:

```bash
nmap -sn 192.168.60.0/24
```

Debe encontrar **tres** hosts: `192.168.60.10`, `192.168.60.20` y tu propia
máquina, `192.168.60.1`. Al terminar, `vagrant halt` apaga las VMs y
`vagrant destroy -f` las borra.

## Topología

```
        Tu escáner
   Kali (.50) o el propio host (.60.1)
                 |
     red host-only 192.168.60.0/24
                 |
        +--------+--------+
        |                 |
  nmap-target (.10)   nmap-decoy (.20)
  7 servicios         sólo dropbear en 22
  + cortafuegos       + descarta ICMP echo
```

Las dos VMs están en una red **host-only** de VirtualBox: se ven entre sí y
ven al host, pero no tienen acceso a tu red real ni a Internet por esa
interfaz. Cada VM tiene además una interfaz NAT que Vagrant usa sólo para
`vagrant ssh` y el aprovisionamiento; **no forma parte del laboratorio** y no
debe usarse en los ejercicios.

| VM | Box | RAM | IP de laboratorio | Rol |
|---|---|---|---|---|
| `nmap-target` | `generic/alpine319` (4.3.12) | 1024 MB | `192.168.60.10` | Los siete servicios y el cortafuegos |
| `nmap-decoy` | `generic/alpine319` (4.3.12) | 256 MB | `192.168.60.20` | Segundo vecino del segmento |

`192.168.60.0/24` cae dentro del rango `192.168.56.0/21` que VirtualBox
permite por defecto para redes host-only, así que no hace falta tocar
`/etc/vbox/networks.conf`. Tampoco choca con el laboratorio del Módulo I, que
usa las redes `.56`, `.57` y `.58`.

### Escanear desde Kali

Añade a tu VM de Kali un adaptador **host-only** en la misma red `vboxnet` que
creó este laboratorio y dale la `192.168.60.50` (reservada). Desde ahí,
`192.168.60.10` y `192.168.60.20` son alcanzables igual que desde el host.

### Si no tienes root en tu máquina

Los escaneos `-sS`, `-sU`, `-O`, `-sA` y `-sF/-sN/-sX` mandan paquetes a mano y
necesitan privilegios. Sin ellos, Nmap cae a `-sT`, que funciona pero **no es
lo mismo** — el Episodio 1 explica exactamente en qué se diferencian. Tres
salidas:

- Usar la VM de Kali, donde ya eres root.
- `sudo nmap …` en tu host.
- Si tienes Docker pero no `sudo`, un escáner efímero con la red del host:
  ```bash
  docker run --rm --net=host --cap-add=NET_RAW --cap-add=NET_ADMIN \
    alpine:3.19 sh -c 'apk add --no-cache nmap && nmap -sS 192.168.60.10'
  ```
  Es lo que se usó para verificar los episodios: la fuente sigue siendo
  `192.168.60.1` y el contenedor desaparece al terminar.

## Los siete servicios de `nmap-target`

Todos son **deliberadamente inseguros**. No hay ningún CVE que encontrar: los
paquetes de Alpine 3.19 están parcheados. Lo que hay son desconfiguraciones y
criptografía obsoleta, que es lo que de verdad se encuentra en una auditoría, y
que NSE detecta **por comportamiento** y no leyendo números de versión.

| Puerto | Servicio | Qué tiene mal | Se destapa con |
|---|---|---|---|
| 21/tcp | vsftpd 3.0.5 | Login anónimo, carpeta de subida escribible, y un **banner que miente** diciendo ser la versión 2.3.4 | `ftp-anon`, `ftp-syst`, `ftp-vsftpd-backdoor` |
| 22/tcp | OpenSSH 9.6 | Algoritmos heredados reactivados (`diffie-hellman-group1-sha1`, `3des-cbc`, `hmac-md5`), login por contraseña | `ssh2-enum-algos`, `ssh-auth-methods`, `ssh-brute` |
| 80/tcp | nginx 1.24.0 | `.git/` publicado con la contraseña de despliegue dentro, `.env` con claves, respaldo `config.php.bak`, listados de directorio, `/admin/` con credenciales de fábrica, OPTIONS anunciando `PUT DELETE TRACE` | `http-git`, `http-enum`, `http-methods`, `http-auth`, y tu propio `http-env-leak` |
| 443/tcp | nginx 1.24.0 + TLS | Certificado autofirmado **RSA de 1024 bits, firmado con SHA-1 y caducado en enero de 2024**; TLS 1.0 y 1.1 activos; suites anónimas y de 3DES; grupo DH de 1024 bits | `ssl-cert`, `ssl-enum-ciphers`, `ssl-dh-params` |
| 3306/tcp | MariaDB 10.11.14 | `root` **y** la cuenta anónima sin contraseña, escuchando en toda la red | `mysql-empty-password`, `mysql-users`, `mysql-databases` |
| 16379/tcp | Redis 7.2.9 | Sin autenticación, y **escondido en un puerto no estándar** | `redis-info` (sólo si usas `-sV`) |
| 161/udp | net-snmp 5.9.4 | Community `public`, que entrega el kernel exacto, todas las interfaces y la lista completa de puertos a la escucha | `snmp-info`, `snmp-sysdescr`, `snmp-interfaces`, `snmp-netstat` |

### Los cuatro estados de puerto, a propósito

El cortafuegos de `nmap-target` ([`provision/nftables.conf`](provision/nftables.conf))
no protege nada: existe para que `--reason` tenga algo que explicar.

| Puerto | Estado | Razón que da Nmap | Cómo se consigue |
|---|---|---|---|
| 21, 22, 80, 443, 3306, 16379 | `open` | `syn-ack` | El servicio acepta |
| 8080 | `filtered` | `no-response` | `drop`: silencio absoluto |
| 445 | `filtered` | `admin-prohibited` | `reject`: ICMP tipo 3 código 13 |
| Los otros 65 527 | `closed` | `reset` | El kernel responde RST |

Verificado en pruebas con `nmap -sS -p- --reason`.

### `nmap-decoy`

Un solo servicio: **dropbear** 2022.83 en el 22. Está para dos cosas.

La primera, que el barrido `-sn` tenga a quién encontrar además del objetivo.
La segunda, más interesante: **descarta las peticiones de eco ICMP**. Eso
permite demostrar que «bloquear el ping» no es lo mismo que ser invisible —
ver el Episodio 1. Verificado en pruebas: `ping 192.168.60.20` pierde el 100%
de los paquetes, y aun así `nmap -sn 192.168.60.20` lo encuentra.

Que corra dropbear y no OpenSSH tampoco es casual: en el Episodio 2 sirve para
ver que «ssh» es un protocolo, no un producto, y que `-sV` sabe cuál de los dos
hay detrás.

## Credenciales del laboratorio

| Dónde | Usuario | Contraseña | Para qué |
|---|---|---|---|
| Ambas VMs | `vagrant` | (clave SSH) | `vagrant ssh <vm>`; `sudo` sin contraseña |
| `nmap-target`, SSH | `soporte` | `FerryProgreso17` | La **misma** que se filtra en `http://192.168.60.10/.git/config`. Es reutilización de credenciales, y es el puente del Episodio 3 |
| Web, `/admin/` | `admin` | `letmein` | Filtradas en `http://192.168.60.10/config.php.bak` |
| MariaDB | `root` y la cuenta anónima | (vacía) | El hallazgo de `mysql-empty-password` |
| SNMP | — | community `public` | Lectura completa del árbol MIB |

Ninguna VM tiene contraseña de `root`; todo el acceso administrativo es por
`sudo` desde `vagrant`.

## Archivos de este miniworkshop

| Ruta | Qué es |
|---|---|
| `Vagrantfile` | Definición de las dos VMs |
| `provision/` | Scripts y configuraciones de aprovisionamiento |
| `provision/site/www/` | Sitio ficticio de «Aurora Marítima S.A. de C.V.» con los artefactos filtrados |
| `episodios/` | Los tres episodios |
| `apendices/apendice-a-tcpdump.md` | El apéndice de tcpdump |
| `nse/http-env-leak.nse` | Versión final del script del Episodio 3, para comparar |
| `web/handshake-tcp.html` | La animación del intercambio de banderas |

## Uso ético

Todo lo de este laboratorio es de uso **exclusivamente educativo y en red
aislada**. Las VMs son deliberadamente vulnerables: nunca las expongas a una
red de producción ni a Internet.

Escanear es **tocar** el objetivo. Un `-sS` deja registro en cualquier
cortafuegos o IDS que esté mirando, y en muchas jurisdicciones un escaneo no
autorizado contra infraestructura ajena es un delito, independientemente de que
no hayas explotado nada. La empresa «Aurora Marítima S.A. de C.V.», su dominio,
su personal y sus clientes son inventados. **Nunca ejecutes ninguno de estos
comandos contra un sistema que no sea tuyo o para el que no tengas autorización
explícita y por escrito** (Sección 10 del syllabus del Módulo I).
