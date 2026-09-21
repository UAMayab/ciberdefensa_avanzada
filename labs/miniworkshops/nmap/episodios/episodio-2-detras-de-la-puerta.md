# Episodio 2 — Qué hay detrás de la puerta

> **Miniworkshop «Nmap de cero a experto» · Episodio 2 de 3**
> Certificación en Ciberdefensa Avanzada — Universidad Anáhuac Mayab

## Objetivo

Que dejes de leer la columna `SERVICE` como un hecho y entiendas que es el
resultado de una **conversación** que Nmap mantiene con cada puerto — y que
sepas qué dice esa conversación, cuánto cuesta, y cuándo mentir sobre ella es
trivial.

## Antes de empezar

**Qué necesitas**

- El [Episodio 1](episodio-1-quien-esta-ahi.md) hecho. Se da por sabido qué es
  un estado de puerto y qué hace `--reason`.
- El laboratorio levantado y **privilegios de red** (`-sU`, `-O`, `-sA` y los
  escaneos sigilosos los necesitan).

**Cuánto rinde:** ~90 minutos.

**No se entrega nada.**

> **Nota sobre la mitad final.** Los Pasos 6 y 7 usan técnicas de evasión. Se
> enseñan desde la silla del **defensor**: el objetivo es que veas por qué casi
> no evaden nada, no que aprendas a esconderte. Contra un sistema ajeno siguen
> siendo un escaneo no autorizado.

**Herramienta:** Nmap 7.94 · <https://nmap.org>

---

## Introducción

Del Episodio 1 saliste con una lista de puertas abiertas. Sirve de poco.

«El puerto 3306 está abierto» no te deja decidir nada. «Hay un MariaDB 10.11.14
escuchando en toda la red» sí: ya sabes qué versión buscar en una base de
vulnerabilidades, qué cliente usar y qué regla de cortafuegos hace falta.

El salto entre una frase y la otra parece pequeño y no lo es. Un número de
puerto es una **convención**: nada obliga a que un servidor web esté en el 80.
Para saber qué hay detrás hay que hablar con ello. Y hablar con algo que no
sabes qué es tiene un problema de huevo y gallina: no sabes en qué idioma
saludar.

Nmap lo resuelve por fuerza bruta educada: tiene un catálogo de ~12 000 saludos
y respuestas esperadas, los prueba por orden de probabilidad, y compara. Este
episodio va de ver ese catálogo por dentro, de lo que cuesta consultarlo, y de
lo fácil que es engañarlo.

## Resultados de aprendizaje

Al terminar puedo:

- Explicar qué bytes manda `-sV` a un puerto y de dónde salen.
- Justificar por qué `-sV` dice una versión de un servicio y el propio servicio
  dice otra, sin decir «Nmap se equivocó».
- Decir por qué un escaneo UDP tarda cientos de veces más que uno TCP, y qué
  hacer al respecto.
- Leer un escaneo `-sA` y dibujar a partir de él las reglas del cortafuegos.
- Mostrar, con una captura, por qué `-D` no esconde a quien escanea.

---

## Paso 1 — Qué dice Nmap cuando saluda · 15 min

**Qué haces.** Poner nombre y versión a cada puerto abierto.

```bash
# 1. Detección de servicio y versión
nmap -sV -p21,22,80,443,3306,16379 192.168.60.10
```

```
PORT      STATE SERVICE  VERSION
21/tcp    open  ftp      vsftpd 2.3.4
22/tcp    open  ssh      OpenSSH 9.6 (protocol 2.0)
80/tcp    open  http     nginx 1.24.0
443/tcp   open  ssl/http nginx 1.24.0
3306/tcp  open  mysql    MySQL 5.5.5-10.11.14-MariaDB
16379/tcp open  redis    Redis key-value store
Service Info: OS: Unix
```

**Qué buscar.** El 16379, que en el Episodio 1 era `unknown`, ahora es `redis`.
Y el 443 ya no es `https` a secas sino `ssl/http`: Nmap negoció TLS y **habló
HTTP por dentro**.

**Por qué importa.** Mira lo que hay detrás de ese `redis`. El catálogo de
sondas de Nmap vive en `/usr/share/nmap/nmap-service-probes`:

```bash
# 2. La sonda que identifica a Redis, y las respuestas que reconoce
grep -n "redis" /usr/share/nmap/nmap-service-probes | head -4
```

```
11331:match redis m|^-ERR wrong number of arguments for 'get' command\r\n$| p/Redis key-value store/
15974:Probe TCP redis-server q|*1\r\n$4\r\ninfo\r\n|
15977:match redis m|-ERR operation not permitted\r\n|s p/Redis key-value store/ cpe:/a:redislabs:redis/
15978:match redis m|^\$\d+\r\n(?:#[^\r\n]*\r\n)*redis_version:([.\d]+)\r\n|s p/Redis key-value store/ v/$1/
```

La línea `Probe` es literalmente lo que Nmap **envía**: `*1\r\n$4\r\ninfo\r\n`,
que en el protocolo de Redis significa «ejecuta el comando `INFO`». Las líneas
`match` son expresiones regulares contra la respuesta. La última captura la
versión en `([.\d]+)` y la mete en `v/$1/`.

Es decir: `-sV` no «detecta» nada por arte de magia. **Le habla a cada puerto en
varios idiomas hasta que uno contesta con sentido.** Y eso son conexiones
reales, registradas en el log del servicio.

Ahora el coste:

```bash
# 3. Sin sondear casi nada (intensidad mínima)
nmap -sV --version-intensity 0 -p16379 192.168.60.10
```

```
16379/tcp open  unknown
```

```bash
# 4. Con todas las sondas (intensidad máxima)
nmap -sV --version-intensity 9 -p16379 192.168.60.10
```

```
16379/tcp open  redis   Redis key-value store
```

**Por qué importa.** Cada sonda tiene una «rareza» de 1 a 9. `--version-intensity`
fija hasta qué rareza se prueba; el valor por defecto es 7, y `--version-all`
equivale a 9. En un puerto estándar la intensidad baja basta. En un puerto raro
—justo donde alguien escondió algo— hace falta subirla. La regla práctica:
**cuanto más raro el puerto, más intensidad necesitas**, que es exactamente lo
contrario de lo que hace un escaneo apresurado.

## Paso 2 — Cuando el servicio miente · 12 min

**Qué haces.** Mirar con lupa el puerto 21.

```bash
# 5. ¿Qué versión de FTP hay?
nmap -sV -p21 192.168.60.10
```

```
21/tcp open  ftp     vsftpd 2.3.4
```

vsftpd 2.3.4 es una versión famosa: en julio de 2011 el propio sitio de
descarga sirvió durante unos días un tarball con una **puerta trasera**
(CVE-2011-2523). Si eso fuera cierto, sería el hallazgo del día.

Pregúntale al servicio de otra manera:

```bash
# 6. El comando STAT del propio protocolo FTP
nmap -p21 --script ftp-syst 192.168.60.10
```

```
| ftp-syst:
|   STAT:
| FTP server status:
|      Connected to 192.168.60.1
|      Logged in as ftp
|      TYPE: ASCII
|      ...
|_     vsFTPd 3.0.5 - secure, fast, stable
```

**Qué buscar.** `-sV` dice **2.3.4**. El propio servidor, contestando a `STAT`,
dice **3.0.5**. Las dos salidas son correctas y se contradicen.

**Por qué importa.** `-sV` identificó la versión leyendo el **banner de
bienvenida**, la línea que el servidor manda al conectar. Ese banner es una
cadena de configuración: en vsftpd se cambia con una línea, `ftpd_banner=`. En
esta VM se ha puesto a mentir a propósito.

O sea: **la versión que reporta `-sV` es la versión que el servidor dice tener.**
No hay ninguna comprobación detrás. Alguien puede ponerla para despistar,
para parecer parcheado, o para atraer escaneos a un señuelo.

De aquí salen dos reglas que valen más que cualquier bandera de Nmap:

1. **Un banner no es una prueba.** Es una declaración del otro lado.
2. **Contrasta siempre con una segunda fuente.** El Episodio 3 lo remata: hay
   un script que *comprueba* si la puerta trasera existe, en vez de creerse el
   número.

## Paso 3 — Adivinar el sistema operativo, y por qué es adivinar · 12 min

**Qué haces.** Pedirle a Nmap el sistema operativo y mirar qué responde.

```bash
# 7. Detección de SO
sudo nmap -O -p22,80,9999 192.168.60.10
```

```
No exact OS matches for host (If you know what OS is running on it, see https://nmap.org/submit/ ).
TCP/IP fingerprint:
OS:SCAN(V=7.94%E=4%D=9/21%OT=22%CT=9999%CU=36656%PV=Y%DS=1%DC=D%G=Y%M=08002
OS:7%TM=6AB0E179%P=x86_64-alpine-linux-musl)SEQ(SP=105%GCD=1%ISR=10D%TI=Z%C
...
Network Distance: 1 hop
```

Sin coincidencia exacta. Fuérzalo a arriesgar:

```bash
# 8. Con conjeturas agresivas
sudo nmap -O --osscan-guess -p22,80,9999 192.168.60.10
```

```
Aggressive OS guesses: Linux 2.6.32 (96%), Linux 3.2 - 4.9 (96%), Linux 4.15 - 5.8 (96%),
Linux 2.6.32 - 3.10 (96%), Linux 5.0 - 5.5 (96%), Linux 3.4 - 3.10 (95%), Linux 3.1 (95%),
Linux 3.2 (95%), AXIS 210A or 211 Network Camera (Linux 2.6.17) (95%), Linux 2.6.32 - 2.6.35 (94%)
```

**Qué buscar.** Diez conjeturas con confianzas del 94 al 96 %, que abarcan
kernels de Linux desde 2009 hasta 2020 — y **una cámara de vigilancia AXIS**.
El sistema real es Alpine Linux con kernel **6.6.10**, que no aparece en la
lista.

**Por qué importa.** `-O` no pregunta el sistema operativo: manda una tanda de
paquetes deliberadamente extraños (SYN a un puerto cerrado, TCP con banderas
imposibles, ICMP con datos raros) y mide cómo responde la pila TCP/IP —
tamaños de ventana, orden de las opciones, cómo genera los números de
secuencia. Luego compara esa huella con una base de datos de huellas conocidas.

Si el sistema no está en la base, **no hay respuesta correcta posible**. Y los
porcentajes no son probabilidades de acierto: son parecidos con entradas de la
base de datos.

Compáralo con lo que te va a dar el Paso 5. Aquí tienes diez conjeturas y una
cámara de vídeo; allí vas a obtener la cadena exacta del kernel con un solo
paquete UDP, porque alguien dejó SNMP abierto. **La técnica más sofisticada no
es la que mejor informa; la que informa es la que aprovecha la desconfiguración.**

## Paso 4 — UDP, o por qué nadie escanea UDP · 15 min

**Qué haces.** Escanear el único servicio UDP del laboratorio.

```bash
# 9. Tres puertos UDP, con razón
sudo nmap -sU -p161,162,53 --reason 192.168.60.10
```

```
PORT    STATE  SERVICE  REASON
53/udp  closed domain   port-unreach ttl 64
161/udp open   snmp     udp-response ttl 64
162/udp closed snmptrap port-unreach ttl 64
```

**Qué buscar.** Las razones. Un UDP cerrado se sabe porque el sistema devuelve
un **ICMP «puerto inalcanzable»** (tipo 3, código 3). Un UDP abierto se sabe
sólo si la aplicación **contesta algo**.

Míralo en el cable, desde la VM:

```bash
# 10. Qué pasa de verdad con un UDP cerrado
vagrant ssh nmap-target -c "sudo tcpdump -ni eth1 -c 2 'icmp and host 192.168.60.1'"
# y desde tu máquina:  sudo nmap -sU -p53 192.168.60.10
```

```
IP 192.168.60.10 > 192.168.60.1: ICMP 192.168.60.10 udp port 53 unreachable, length 66
```

Y qué manda Nmap para provocar respuesta:

```
IP 192.168.60.1.57222 > 192.168.60.10.53: 6+ TXT CHAOS? version.bind. (30)
IP 192.168.60.1.57222 > 192.168.60.10.161:  GetRequest(32)  .1.3.6.1.2.1.1.5.0
```

**Por qué importa.** Fíjate en que Nmap no manda un paquete UDP vacío: manda
una **consulta DNS real** al 53 y un **GetRequest de SNMP real** al 161. Tiene
que hacerlo, porque un servicio UDP que recibe basura normalmente calla, y
callar es indistinguible de estar filtrado. Por eso el estado natural de UDP es
`open|filtered`.

Ahora el precio. El núcleo de Linux **limita el ritmo** con el que emite errores
ICMP (`net.ipv4.icmp_ratelimit`, 1000 ms por defecto). Como el «cerrado» de UDP
depende de ese ICMP, escanear muchos puertos UDP significa esperar a que el
objetivo te deje saber que están cerrados, uno por uno.

La consecuencia práctica: `nmap -sU -p-` contra un solo host puede tardar
**horas**. Lo que se hace en la vida real:

```bash
# 11. Sólo los UDP que de verdad importan
sudo nmap -sU --top-ports 20 192.168.60.10

# 12. O una lista concreta, que es lo más honesto
sudo nmap -sU -p53,67,123,161,500,514,1900 192.168.60.10
```

Y aun así, **UDP es donde se esconden las cosas**: SNMP, TFTP, NTP, DNS, IPMI.
Un inventario que sólo mira TCP está incompleto por diseño.

## Paso 5 — El atajo: cuando el objetivo te lo cuenta todo · 10 min

**Qué haces.** Preguntarle al SNMP que acabas de encontrar.

```bash
# 13. Tres scripts de SNMP sobre el puerto 161
sudo nmap -sU -p161 --script snmp-sysdescr,snmp-netstat,snmp-interfaces 192.168.60.10
```

```
| snmp-sysdescr: Linux nmap-target 6.6.10-0-virt #1-Alpine SMP PREEMPT_DYNAMIC Mon, 08 Jan 2024 10:08:57 +0000 x86_64
|_  System uptime: 1h15m16.00s (451600 timeticks)
| snmp-netstat:
|   TCP  0.0.0.0:21           0.0.0.0:0
|   TCP  0.0.0.0:22           0.0.0.0:0
|   TCP  0.0.0.0:80           0.0.0.0:0
|   TCP  0.0.0.0:443          0.0.0.0:0
|   TCP  0.0.0.0:3306         0.0.0.0:0
|   TCP  0.0.0.0:16379        0.0.0.0:0
|_  UDP  0.0.0.0:161          *:*
| snmp-interfaces:
|   eth0
|     IP address: 10.0.2.15  Netmask: 255.255.255.0
|     MAC address: 08:00:27:28:4d:fb (Oracle VirtualBox virtual NIC)
|   eth1
|     IP address: 192.168.60.10  Netmask: 255.255.255.0
|     MAC address: 08:00:27:81:0a:09 (Oracle VirtualBox virtual NIC)
```

**Qué buscar.** Tres cosas, y cada una vale más que un escaneo entero.

1. **El kernel exacto:** `6.6.10-0-virt #1-Alpine`. Lo que `-O` no consiguió
   con veinte paquetes y una base de datos, SNMP lo regala en uno.
2. **La lista completa de puertos a la escucha**, sacada del propio sistema. No
   es una deducción: es su tabla de sockets. Incluye el 16379 que sólo
   encontrabas con `-p-`.
3. **Una segunda interfaz de red, `10.0.2.15`**, que no aparece en ningún
   escaneo porque está en otra red a la que no tienes acceso. Acabas de
   descubrir que el host está conectado a un segmento que no conocías.

**Por qué importa.** Esto no es una técnica de escaneo: es una
**desconfiguración**. La community `public` es la contraseña de fábrica de
SNMP, de solo lectura, y sigue estando activa en impresoras, switches, UPS y
cámaras de medio mundo.

La lección es de método: el camino corto casi nunca es la herramienta más
sofisticada, sino **el servicio que nadie configuró**. Un pentester que empieza
por `-O` y no mira el 161 está trabajando de más.

## Paso 6 — Dibujar el cortafuegos sin verlo · 12 min

**Qué haces.** Usar un escaneo que no busca servicios, sino reglas.

```bash
# 14. Escaneo ACK: no dice qué está abierto, dice qué está filtrado
sudo nmap -sA -p21,80,445,8080,9999 --reason 192.168.60.10
```

```
PORT     STATE      SERVICE      REASON
21/tcp   unfiltered ftp          reset ttl 64
80/tcp   unfiltered http         reset ttl 64
445/tcp  filtered   microsoft-ds admin-prohibited ttl 64
8080/tcp filtered   http-proxy   no-response
9999/tcp unfiltered abyss        reset ttl 64
```

**Qué buscar.** El 21 (abierto) y el 9999 (cerrado) dan **el mismo resultado**:
`unfiltered`. El escaneo ACK es incapaz de distinguirlos. En cambio separa
limpiamente los dos filtrados.

**Por qué importa.** `-sA` manda un ACK suelto, sin SYN previo. Según el RFC
793, una máquina que recibe un ACK que no corresponde a ninguna conexión
**debe** responder RST, esté el puerto abierto o cerrado. Así que:

- Si vuelve RST → el paquete **llegó**. No hay filtro en el camino.
- Si no vuelve nada, o vuelve un ICMP de prohibición → **hay un filtro**.

Es la herramienta para responder «¿qué bloquea el cortafuegos?» en vez de «¿qué
servicios hay?». Con esta salida ya puedes escribir las reglas:

| Puerto | Deducción |
|---|---|
| 445 | Regla explícita de rechazo, que además se identifica |
| 8080 | Regla de descarte silencioso |
| 21, 80, 9999 | Sin regla: el tráfico llega al host |

En la Actividad A.6 del Módulo I, cuando verifiques tus reglas de pfSense,
`-sA` es el comando que confirma si la regla hace lo que crees.

## Paso 7 — Escaneos sigilosos, y sus dos límites · 12 min

**Qué haces.** Probar las tres variantes que mandan banderas «imposibles».

```bash
# 15. FIN, NULL y Xmas contra un abierto y un cerrado
sudo nmap -sF -p22,9999 --reason 192.168.60.10
sudo nmap -sN -p22,9999 --reason 192.168.60.10
sudo nmap -sX -p22,9999 --reason 192.168.60.10
```

Las tres dan lo mismo:

```
22/tcp   open|filtered ssh     no-response
9999/tcp closed        abyss   reset ttl 64
```

**Qué buscar.** El puerto abierto **no contesta nada**, y el cerrado contesta
RST. Es justo al revés de lo intuitivo, y es lo que manda el RFC 793: un
segmento que llega sin SYN a un puerto **cerrado** provoca RST; a un puerto
**abierto en estado LISTEN**, se descarta en silencio.

Ahora el primer límite:

```bash
# 16. El mismo -sF contra los puertos filtrados
sudo nmap -sF -p445,8080 --reason 192.168.60.10
```

```
445/tcp  filtered      microsoft-ds admin-prohibited ttl 64
8080/tcp open|filtered http-proxy   no-response
```

**El 8080 sale `open|filtered`, exactamente igual que el 22, que sí está
abierto.** Como «abierto» y «descartado por cortafuegos» se manifiestan los dos
como silencio, la técnica no puede separarlos. Eso es el estado
`open|filtered`: no es un resultado, es una confesión de ambigüedad.

El segundo límite es de portabilidad: esto **sólo funciona contra pilas que
siguen el RFC**. Windows responde RST a todo, abierto o cerrado, así que un
`-sX` contra Windows reporta los 65 535 puertos como `closed`. Si un escaneo
sigiloso te da «todo cerrado», la conclusión correcta no es «no hay nada», es
«probablemente es Windows y esta técnica no sirve aquí».

## Paso 8 — Tiempo: la bandera que de verdad cambia las cosas · 12 min

**Qué haces.** Medir.

```bash
# 17. Cien puertos cerrados
sudo nmap -sS -p1-100 192.168.60.10      # -> 0.15 s

# 18. DOS puertos filtrados
sudo nmap -sS -p445,8080 192.168.60.10   # -> 1.34 s

# 19. Los mismos dos, sin reintentos
sudo nmap -sS -p445,8080 --max-retries 0 192.168.60.10   # -> 0.24 s
```

**Qué buscar.** Dos puertos filtrados cuestan **nueve veces más** que cien
cerrados. Los cerrados contestan al instante; los filtrados hay que esperarlos
y reintentarlos.

```bash
# 20. Los mismos 50 puertos, a dos velocidades
sudo nmap -sS -p1-50 -T4 192.168.60.10   # -> 0.19 s
sudo nmap -sS -p1-50 -T2 192.168.60.10   # -> 20.53 s
```

**Por qué importa.** `-T2` tardó **108 veces más** en hacer exactamente lo
mismo. Las plantillas van de `-T0` (paranoico, una sonda cada cinco minutos) a
`-T5` (demencial). `-T3` es el valor por defecto y `-T4` es lo razonable en una
red local.

Lo importante no es cuál es «el bueno», sino entender qué compras con el
tiempo: **`-T2` y por debajo existen para no disparar umbrales de detección**,
no para ser amables. Y `-T5` puede perder puertos abiertos en una red con
latencia, porque se rinde antes de que llegue la respuesta.

La regla: en tu laboratorio, `-T4`. Contra infraestructura ajena autorizada,
`-T3` y `--max-retries 1`. Contra equipamiento industrial o médico, **nada por
encima de `-T2`, y probablemente no escanees en absoluto** sin una ventana de
mantenimiento: hay PLCs que se reinician con un escaneo de puertos.

## Paso 9 — Evasión, vista desde el otro lado · 10 min

**Qué haces.** Lanzar un escaneo con señuelos y mirarlo desde el objetivo.

En una terminal:

```bash
# 21. Capturar los SYN que lleguen al puerto 80
vagrant ssh nmap-target -c "sudo tcpdump -ni eth1 -c 6 'tcp port 80 and tcp[tcpflags] & tcp-syn != 0'"
```

En otra:

```bash
# 22. Escanear escondiéndose entre cuatro direcciones falsas. ME = la tuya
sudo nmap -sS -p80 -D 10.0.0.7,192.168.60.99,ME,203.0.113.5 192.168.60.10
```

```
IP 10.0.0.7.63138 > 192.168.60.10.80: Flags [S], seq 4132727904, win 1024, options [mss 1460], length 0
IP 192.168.60.99.63138 > 192.168.60.10.80: Flags [S], seq 4132727904, win 1024, options [mss 1460], length 0
IP 192.168.60.1.63138 > 192.168.60.10.80: Flags [S], seq 4132727904, win 1024, options [mss 1460], length 0
IP 203.0.113.5.63138 > 192.168.60.10.80: Flags [S], seq 4132727904, win 1024, options [mss 1460], length 0
IP 192.168.60.10.80 > 192.168.60.1.63138: Flags [S.], seq 908484083, ack 4132727905, win 32120, options [mss 1460], length 0
```

**Qué buscar.** Cuatro SYN de cuatro direcciones distintas. Y ahora mira los
detalles:

- Los cuatro salen con **el mismo puerto de origen**: `63138`.
- Los cuatro llevan **el mismo número de secuencia**: `4132727904`.
- Los cuatro llegaron **en el mismo microsegundo**.
- El SYN/ACK vuelve **sólo a `192.168.60.1`**, la dirección real.

**Por qué importa.** `-D` reparte la culpa, no la elimina. Un defensor que mire
las respuestas ve inmediatamente quién es el único que las recibe; y aunque
sólo mire las peticiones, cuatro paquetes idénticos salvo la IP de origen son
una firma tan clara como una confesión.

Lo mismo vale para el resto del arsenal:

```bash
# 23. Fragmentar los paquetes para despistar a un IDS antiguo
sudo nmap -sS -f -p80 192.168.60.10

# 24. Salir desde un puerto de origen "de confianza" (DNS)
sudo nmap -sS --source-port 53 -p80 192.168.60.10

# 25. Rellenar los paquetes para que no midan lo de siempre
sudo nmap -sS --data-length 25 -p80 192.168.60.10
```

`-f` fragmenta: sirve contra inspección que no reensambla, y cualquier
cortafuegos con estado de este siglo reensambla. `--source-port 53` aprovecha
reglas mal escritas del tipo «permitir todo lo que venga del puerto 53», que
existen y son un fallo real de configuración. `--data-length` cambia el tamaño,
que es una de las cosas por las que un IDS reconoce a Nmap.

Ninguna te hace invisible. Todas dejan rastro. La utilidad profesional de
conocerlas no es usarlas para esconderte: es **saber qué buscar cuando eres tú
quien revisa los logs**, y poder decirle a un cliente «tu regla de puerto 53 de
origen es eludible, mira».

---

## Cuaderno de bitácora (opcional)

1. La tabla de inventario que de verdad sirve: host, puerto, servicio, versión
   **según `-sV`**, versión **según el propio servicio** cuando difieran, y una
   columna «¿debería estar expuesto?».
2. Las reglas del cortafuegos de `nmap-target`, deducidas sólo del `-sA`. Luego
   compáralas con `provision/nftables.conf` y mira qué acertaste.
3. Dos o tres líneas: ¿qué te dio más información sobre el objetivo, `-O` o el
   SNMP mal configurado? ¿Qué dice eso sobre por dónde empezar la próxima vez?

## Autoevaluación

- [ ] Sé decir qué bytes manda `-sV` al puerto 16379 y por qué esos.
- [ ] Puedo explicar la contradicción del puerto 21 sin decir que Nmap falló.
- [ ] Sé por qué `-sU` es lento y qué tiene que ver el núcleo del objetivo.
- [ ] Puedo leer un `-sA` y escribir las reglas del cortafuegos a partir de él.
- [ ] Entiendo por qué `open|filtered` no es un estado sino una ambigüedad.
- [ ] Sé qué mira un defensor para detectar un escaneo con `-D`.

## Solución de problemas

| Síntoma | Qué pasa |
|---|---|
| `-sV` tarda muchísimo | Está probando sondas raras en puertos que no responden. Limita con `-p` o baja `--version-intensity` |
| `-O` dice `Too many fingerprints match` | Necesita al menos un puerto abierto y uno cerrado. Dale `-p22,9999` |
| `-sU` parece colgado | Es normal. Mira el progreso con `-v`, o pulsa una tecla durante el escaneo para ver el porcentaje |
| `-sF` dice que todo está cerrado | El objetivo es Windows. La técnica no aplica |
| Los señuelos de `-D` no aparecen en la captura | Tu filtro de tcpdump limita por `host`. Quítalo o usa sólo `tcp port 80` |

## Uso ético

Este episodio manda a la red paquetes que ningún sistema operativo manda en
condiciones normales: banderas imposibles, fragmentos, direcciones de origen
falsas. Contra un sistema ajeno, eso no se parece a un error: se parece a un
ataque, y así lo va a leer quien revise los logs. Las técnicas de evasión se
explican aquí para que sepas reconocerlas como defensor. Usarlas fuera de tu
laboratorio, sin autorización escrita, es indefendible.

---

**Siguiente:** [Episodio 3 — Preguntarle al servicio, y escribir tu propia pregunta](episodio-3-nse-de-usuario-a-autor.md),
donde Nmap deja de escanear y empieza a interrogar.
