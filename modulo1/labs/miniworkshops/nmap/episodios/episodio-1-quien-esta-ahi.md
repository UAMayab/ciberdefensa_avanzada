# Episodio 1 — Quién está ahí y qué puerta está abierta

> **Miniworkshop «Nmap de cero a experto» · Episodio 1 de 3**
> Certificación en Ciberdefensa Avanzada — Universidad Anáhuac Mayab

## Objetivo

Que sepas exactamente **qué paquete sale de tu máquina, qué vuelve y por qué
Nmap concluye lo que concluye** en cada uno de los cinco estados de puerto, en
lugar de leer la tabla de salida como quien lee un oráculo.

## Antes de empezar

**Qué necesitas**

- El laboratorio levantado: `cd labs/miniworkshops/nmap && vagrant up`.
- Nmap 7.94 o posterior. `nmap --version` te lo dice.
- **Privilegios de red** para la segunda mitad (a partir del Paso 4). Mira
  «Si no tienes root en tu máquina» en el [README](../README.md).

**Qué NO necesitas**

Saber programar, saber de TCP más allá de «hay algo llamado handshake», ni
haber usado Nmap antes.

**Cuánto rinde:** ~80 minutos.

**No se entrega nada.** Esta serie no se califica. Al final hay un cuaderno de
bitácora opcional, que sirve para ti y para nadie más.

**Ten abierta la animación.** [`web/handshake-tcp.html`](../web/handshake-tcp.html)
muestra, paquete a paquete y bandera a bandera, los ocho intercambios de este
episodio y del siguiente. Cada paso te dice qué escenario mirar.

> **Advertencia que de verdad importa.** Todos los comandos de este episodio se
> ejecutan contra `192.168.60.10` y `192.168.60.20`, que son tus VMs. Ejecutar
> cualquiera de ellos contra una dirección que no controlas es, en el mejor de
> los casos, una falta grave, y en muchos países un delito. Escanear no es
> «mirar»: es tocar.

**Herramienta:** Nmap 7.94 · <https://nmap.org>

---

## Introducción

Acabas de llegar a una organización y te piden un inventario de lo que hay
conectado. Nadie sabe cuántos servidores hay. El diagrama de red que te
entregan es de hace cuatro años y tiene cosas tachadas a mano.

El problema tiene dos mitades, y conviene no confundirlas:

1. **¿Qué máquinas están encendidas?** Preguntarle a 254 direcciones si hay
   alguien en casa.
2. **¿Qué puertas tiene abiertas cada una?** Y esto no es «¿qué servicios
   corre?», que es una pregunta distinta y más difícil, y es la del Episodio 2.

Nmap resuelve las dos mandando paquetes deliberadamente incompletos o raros y
mirando qué contesta el otro lado. Todo lo que hace se apoya en una idea: **el
protocolo TCP obliga a responder de cierta forma, y esa respuesta obligatoria
es información.**

Eso es también su límite. Nmap no sabe nada: **deduce**. Todo este episodio
trata de que veas la deducción, y no sólo la conclusión.

## Resultados de aprendizaje

Al terminar puedo:

- Explicar por qué un barrido `-sn` en mi propio segmento encuentra máquinas
  que no responden al ping, y qué tendría que pasar para que no las encontrara.
- Predecir, antes de ejecutar el comando, qué bandera TCP va a devolver un
  puerto abierto, uno cerrado y uno filtrado.
- Decir en qué se diferencian `-sS` y `-sT` a nivel de paquete, y por qué los
  dos dan una razón distinta ante el mismo cortafuegos.
- Leer una salida de `--packet-trace` y señalar qué línea justifica cada línea
  de la tabla de puertos.

---

## Paso 1 — Averiguar quién está encendido · 10 min

**Qué haces.** Preguntar a las 256 direcciones del segmento cuáles responden.

```bash
# 1. Barrido de descubrimiento: sin escaneo de puertos, sólo "¿estás ahí?"
nmap -sn 192.168.60.0/24
```

```
Nmap scan report for 192.168.60.10
Host is up (0.00015s latency).
MAC Address: 08:00:27:81:0A:09 (Oracle VirtualBox virtual NIC)
Nmap scan report for 192.168.60.20
Host is up (0.00025s latency).
MAC Address: 08:00:27:B1:AC:03 (Oracle VirtualBox virtual NIC)
Nmap scan report for Shamir (192.168.60.1)
Host is up.
Nmap done: 256 IP addresses (3 hosts up) scanned in 2.01 seconds
```

**Qué buscar.** Tres hosts: las dos VMs y tu propia máquina. Fíjate en que
aparece la **dirección MAC** y el fabricante de la tarjeta. Eso es una pista
enorme: Nmap no obtuvo esa MAC de ningún protocolo de red IP, sino de **ARP**.

**Por qué importa.** `-sn` se traduce a veces como «ping scan», y es un mal
nombre. En tu propio segmento de red Nmap no usa ping: usa ARP, que es la
pregunta «¿quién tiene esta IP?» que toda máquina en una Ethernet **tiene que**
contestar para que la red funcione. No es opcional.

## Paso 2 — Por qué «bloqueé el ping» no te esconde · 12 min

**Qué haces.** Comprobar que `nmap-decoy` descarta los pings, y ver que aun
así lo encuentras.

```bash
# 2. El ping de toda la vida contra el decoy
ping -c 2 192.168.60.20
```

```
2 packets transmitted, 0 received, 100% packet loss, time 1052ms
```

El cortafuegos de esa VM tira las peticiones de eco. Ahora:

```bash
# 3. La misma máquina, pero preguntando con Nmap
nmap -sn 192.168.60.20
```

```
Nmap scan report for 192.168.60.20
Host is up (0.00023s latency).
MAC Address: 08:00:27:B1:AC:03 (Oracle VirtualBox virtual NIC)
```

**Qué buscar.** `Host is up`, con MAC. El ping falla y Nmap no. Para ver por
qué, prohíbele usar ARP y déjale sólo el eco ICMP:

```bash
# 4. Sin ARP y sólo con eco ICMP: ahora sí se esconde
sudo nmap -sn -PE --disable-arp-ping 192.168.60.20
```

```
Note: Host seems down. If it is really up, but blocking our ping probes, try -Pn
Nmap done: 1 IP address (0 hosts up) scanned in 2.04 seconds
```

```bash
# 5. El mismo comando contra el objetivo, que sí responde al eco
sudo nmap -sn -PE --disable-arp-ping 192.168.60.10
```

```
Nmap scan report for 192.168.60.10
Host is up (0.00023s latency).
```

**Por qué importa.** Esta es la lección más cara de aprender tarde. «Bloqueamos
el ping en el perímetro» es una frase que se oye mucho y que protege muy poco:

- **En el mismo segmento**, ARP te delata siempre. No hay configuración que lo
  evite, porque sin ARP la máquina no puede comunicarse con nadie.
- **Desde fuera del segmento**, donde ARP no llega, `-sn` no se rinde: por
  defecto también manda un SYN al 443, un ACK al 80 y una marca de tiempo ICMP.
  Basta con que **un puerto cerrado conteste RST** para que el host quede
  descubierto. Esconderse de verdad exige descartar *todo*, y una máquina que
  descarta todo tampoco presta servicio.

Y al revés: si un barrido te dice que un host está caído, eso **no significa
que lo esté**. Significa que no contestó a las sondas que probaste. `-Pn` le
dice a Nmap «da por hecho que está vivo y escanéalo igual».

> Escenario 1 de la animación: el intercambio ARP, antes de que empiece
> cualquier cosa TCP.

## Paso 3 — Los cinco estados, y los cuatro que verás · 12 min

**Qué haces.** Escanear el objetivo entero y leer la tabla despacio.

```bash
# 6. Los 65 535 puertos TCP, pidiendo además la razón de cada veredicto
sudo nmap -sS -p- --reason -T4 192.168.60.10
```

```
Nmap scan report for 192.168.60.10
Host is up, received arp-response (0.000099s latency).
Not shown: 65527 closed tcp ports (reset)
PORT      STATE    SERVICE      REASON
21/tcp    open     ftp          syn-ack ttl 64
22/tcp    open     ssh          syn-ack ttl 64
80/tcp    open     http         syn-ack ttl 64
443/tcp   open     https        syn-ack ttl 64
445/tcp   filtered microsoft-ds admin-prohibited ttl 64
3306/tcp  open     mysql        syn-ack ttl 64
8080/tcp  filtered http-proxy   no-response
16379/tcp open     unknown      syn-ack ttl 64
MAC Address: 08:00:27:81:0A:09 (Oracle VirtualBox virtual NIC)
Nmap done: 1 IP address (1 host up) scanned in 2.00 seconds
```

**Qué buscar.** Tres cosas, en este orden.

**Primero, la columna `REASON`.** Sin `--reason` sólo verías `filtered` dos
veces y parecerían el mismo caso. No lo son:

| Puerto | Razón | Qué pasó de verdad |
|---|---|---|
| 8080 | `no-response` | Nadie contestó. Nmap no puede distinguir «hay un cortafuegos que tira el paquete» de «el paquete se perdió», así que reintenta y acaba rindiéndose |
| 445 | `admin-prohibited` | El cortafuegos contestó con un ICMP diciendo «lo bloqueé yo». Se delató |

Los dos acaban en `filtered`, pero al defensor le dicen cosas distintas y al
atacante también: `admin-prohibited` confirma que **hay** un cortafuegos con
una regla explícita para ese puerto.

**Segundo, la columna `SERVICE`.** Dice `mysql` en el 3306 y `unknown` en el
16379. Nmap **no ha comprobado nada** en ninguno de los dos: se ha limitado a
mirar el número de puerto en su fichero `nmap-services`, que es poco más que
una tabla de costumbres. El 3306 «es» MySQL porque suele serlo, y el 16379 es
`unknown` porque ese número no está en la tabla — no porque Nmap haya mirado.
Detrás del 16379 hay un Redis, y averiguarlo es el Episodio 2.

**Tercero, `Not shown: 65527 closed tcp ports (reset)`.** Sesenta y cinco mil
puertos cerrados **contestaron**, cada uno con un RST. Un escaneo completo no
es discreto ni barato.

**Por qué importa.** Los cinco estados que Nmap sabe reportar:

| Estado | Qué significa |
|---|---|
| `open` | Hay un servicio aceptando conexiones |
| `closed` | Llegó al host, pero nadie escucha ahí |
| `filtered` | Algo impide saberlo: un cortafuegos, una regla, un router |
| `open\|filtered` | O está abierto o está filtrado, y la técnica usada no distingue |
| `unfiltered` | Se alcanza, pero no se sabe si está abierto o cerrado |

Los dos últimos no aparecen en este escaneo porque `-sS` nunca los produce.
Salen en el Episodio 2, con `-sF` y `-sA`.

## Paso 4 — SYN contra connect: el mismo puerto, dos historias · 15 min

**Qué haces.** Escanear los mismos cuatro puertos con las dos técnicas y
comparar.

```bash
# 7. Escaneo SYN (half-open). Manda los paquetes a mano: necesita privilegios
sudo nmap -sS -p22,9999,445,8080 --reason 192.168.60.10
```

```
PORT     STATE    SERVICE      REASON
22/tcp   open     ssh          syn-ack ttl 64
445/tcp  filtered microsoft-ds admin-prohibited ttl 64
8080/tcp filtered http-proxy   no-response
9999/tcp closed   abyss        reset ttl 64
```

```bash
# 8. Escaneo connect. Le pide al sistema operativo que conecte: no necesita root
nmap -sT -p22,9999,445,8080 --reason 192.168.60.10
```

```
PORT     STATE    SERVICE      REASON
22/tcp   open     ssh          syn-ack
445/tcp  filtered microsoft-ds host-unreach
8080/tcp filtered http-proxy   no-response
9999/tcp closed   abyss        conn-refused
```

**Qué buscar.** El puerto 445. `-sS` dice `admin-prohibited`; `-sT` dice
`host-unreach`. **El cortafuegos mandó exactamente el mismo paquete en los dos
casos.** Compruébalo en la VM objetivo:

```bash
# 9. Qué manda de verdad el cortafuegos (desde el propio objetivo)
vagrant ssh nmap-target -c "sudo tcpdump -ni eth1 -c 2 'icmp'"
# ... y desde tu máquina, en otra terminal:
nc -w2 -z 192.168.60.10 445
```

```
IP 192.168.60.1.35208 > 192.168.60.10.445: Flags [S], seq 751716473, ...
IP 192.168.60.10 > 192.168.60.1: ICMP host 192.168.60.10 unreachable - admin prohibited filter, length 68
```

Es ICMP tipo 3, código 13: «prohibido administrativamente». Entonces, ¿por qué
`-sT` dice otra cosa? Porque **`-sT` nunca ve ese paquete**:

```bash
# 10. Los dos escaneos, mostrando cada paquete que entra y sale
sudo nmap -sS -p22 --packet-trace -n 192.168.60.10
```

```
SENT (0.0726s) TCP 192.168.60.1:49526 > 192.168.60.10:22 S ttl=55 id=40747 iplen=44  seq=483791764 win=1024 <mss 1460>
RCVD (0.0729s) TCP 192.168.60.10:22 > 192.168.60.1:49526 SA ttl=64 id=0 iplen=44  seq=2737349857 win=32120 <mss 1460>
```

```bash
nmap -sT -p22 --packet-trace -n 192.168.60.10
```

```
CONN (0.0795s) TCP localhost > 192.168.60.10:22 => Operation in progress
CONN (0.0798s) TCP localhost > 192.168.60.10:22 => Connected
```

**Por qué importa.** Ahí está toda la diferencia, y no es «uno necesita root»:

- Con **`-sS`**, Nmap construye el paquete él mismo y lee las respuestas de la
  tarjeta de red. Ve banderas, ve TTL, ve el código ICMP exacto. Por eso puede
  decir `admin-prohibited`.
- Con **`-sT`**, Nmap le pide al núcleo `connect()` y sólo se entera de lo que
  la llamada devuelve: conectó, lo rechazaron, o no se pudo llegar. El núcleo
  traduce el ICMP tipo 3/13 a un error genérico y **el matiz se pierde por el
  camino**. Nmap no está mintiendo: está informando de lo único que le dejaron
  ver.

La consecuencia práctica: `-sS` es más rápido y más informativo, y además no
completa el handshake, así que muchos servicios **no registran la conexión en
su log de aplicación** (el cortafuegos sí la ve, siempre). `-sT` es lo que te
queda sin privilegios, y es perfectamente válido mientras sepas qué te estás
perdiendo.

> Escenarios 2 y 3 de la animación: el SYN half-open frente a la conexión
> completa, con las banderas de cada paquete.

## Paso 5 — Ver el handshake de verdad · 12 min

**Qué haces.** Capturar en el objetivo lo que cada técnica deja en el cable.

En una terminal, dentro de la VM:

```bash
# 11. Escuchar sólo el puerto 22 viniendo de tu máquina
vagrant ssh nmap-target -c "sudo tcpdump -ni eth1 -c 6 'tcp port 22 and host 192.168.60.1'"
```

En otra terminal, una conexión completa y normal:

```bash
# 12. Una conexión de verdad (como la que hace -sT)
nc -w1 -z 192.168.60.10 22
```

```
IP 192.168.60.1.35024 > 192.168.60.10.22: Flags [S], seq 1986689431, win 64240, options [mss 1460,sackOK,TS val 2177619312 ecr 0,nop,wscale 7], length 0
IP 192.168.60.10.22 > 192.168.60.1.35024: Flags [S.], seq 629903496, ack 1986689432, win 31856, options [mss 1460,sackOK,TS val 2858291632 ecr 2177619312,nop,wscale 7], length 0
IP 192.168.60.1.35024 > 192.168.60.10.22: Flags [.], ack 1, win 502, ...
IP 192.168.60.1.35024 > 192.168.60.10.22: Flags [F.], seq 1, ack 1, win 502, ...
IP 192.168.60.10.22 > 192.168.60.1.35024: Flags [.], ack 2, win 249, ...
IP 192.168.60.10.22 > 192.168.60.1.35024: Flags [P.], seq 1:22, ack 2, win 249, ..., length 21: SSH: SSH-2.0-OpenSSH_9.6
```

Repite la captura y lanza ahora un SYN scan:

```bash
# 13. Un escaneo SYN sobre el mismo puerto
sudo nmap -sS -p22 192.168.60.10
```

```
IP 192.168.60.1.57850 > 192.168.60.10.22: Flags [S], seq 250831424, win 1024, options [mss 1460], length 0
IP 192.168.60.10.22 > 192.168.60.1.57850: Flags [S.], seq 117182751, ack 250831425, win 32120, options [mss 1460], length 0
IP 192.168.60.1.57850 > 192.168.60.10.22: Flags [R], seq 250831425, win 0, length 0
```

**Qué buscar.** En la notación de tcpdump, `[S]` es SYN, `[S.]` es SYN+ACK (el
punto es el ACK), `[.]` es un ACK solo, `[F.]` es FIN+ACK, `[R]` es RST y
`[P.]` es PSH+ACK. El apéndice lo desarrolla.

Tres diferencias, todas visibles:

1. La conexión completa hace `[S]` → `[S.]` → `[.]`, y **el servidor llega a
   mandar su banner** (`SSH-2.0-OpenSSH_9.6`). Hubo sesión.
2. El SYN scan hace `[S]` → `[S.]` → `[R]`. Nmap corta con un RST **antes** de
   completar el handshake. El servicio nunca llegó a aceptar la conexión.
3. Mira las opciones TCP y la ventana. El núcleo manda
   `win 64240, options [mss 1460,sackOK,TS val ...,nop,wscale 7]`. Nmap manda
   `win 1024, options [mss 1460]` y nada más.

**Por qué importa.** Ese tercer punto es la razón de que el sigilo de `-sS` sea
relativo: **una ventana de 1024 y una opción MSS suelta son una firma**. Un IDS
decente detecta un SYN scan precisamente por ahí, no por el volumen. En la
Actividad A.8 del Módulo I, cuando configures Suricata, la regla que dispara
con los escaneos de Nmap se apoya en este tipo de detalle.

> Escenario 4 de la animación: puerto cerrado, con el RST+ACK de vuelta.

## Paso 6 — Elegir qué puertos mirar, y guardar el resultado · 12 min

**Qué haces.** Aprender a no escanear los 65 535 puertos cada vez, y a dejar
constancia.

```bash
# 14. Por defecto: los 1000 puertos más comunes. Fíjate en qué falta
nmap -sT 192.168.60.10

# 15. Los 100 más comunes: rápido para una primera pasada
nmap -sT -F 192.168.60.10

# 16. Un rango y una lista concretos
nmap -sT -p1-1024,3306,16379 192.168.60.10

# 17. Todos. Es el único que encuentra el 16379
sudo nmap -sS -p- 192.168.60.10
```

**Qué buscar.** Compara la salida del comando 14 con la del 17. El escaneo por
defecto devuelve siete puertos:

```
PORT     STATE    SERVICE
21/tcp   open     ftp
22/tcp   open     ssh
80/tcp   open     http
443/tcp  open     https
445/tcp  filtered microsoft-ds
3306/tcp open     mysql
8080/tcp filtered http-proxy
```

**El 16379 no está.** No está porque el escaneo por defecto no mira los 65 535
puertos: mira los **1000 más frecuentes**, según las estadísticas que Nmap trae
en `nmap-services`. El 16379 no entra en esa lista. Y detrás de él hay un Redis
sin contraseña.

**Por qué importa.** Quien puso ese Redis en el 16379 lo hizo pensando
exactamente en eso — está escrito en `http://192.168.60.10/respaldos/notas-migracion.txt`,
que leerás en el Episodio 3. Es «seguridad por oscuridad», no funciona contra
nadie que use `-p-`, y sin embargo funciona contra casi todos los escaneos
automáticos. Por eso un inventario serio usa `-p-` al menos una vez.

Y ahora guarda lo que hiciste:

```bash
# 18. Los tres formatos a la vez, con prefijo común
sudo nmap -sS -p- -oA inventario-target 192.168.60.10
ls inventario-target.*
```

`-oA` produce tres ficheros: `.nmap` (lo que viste en pantalla), `.xml` (para
procesar con herramientas) y `.gnmap` (una línea por host, cómodo para `grep`).

```bash
# 19. Sacar sólo los puertos abiertos del formato "grepable"
grep -o '[0-9]*/open' inventario-target.gnmap
```

**Por qué importa.** En una auditoría, la salida de pantalla no es evidencia:
no tiene fecha fiable ni es reproducible. El `.xml` sí, y es lo que consumen
las herramientas de reporte. Adquiere la costumbre de `-oA` desde el primer
escaneo, no desde el que sale mal.

## Paso 7 — Los tres filtros del silencio · 12 min

**Qué haces.** Mirar de cerca el puerto 8080, que no contesta nada.

```bash
# 20. El puerto que calla, con traza de paquetes
sudo nmap -sS -p8080 --packet-trace -n 192.168.60.10 | grep -E "^(SENT|RCVD)"
```

```
SENT (0.0726s) TCP 192.168.60.1:49526 > 192.168.60.10:8080 S ttl=56 id=49808 iplen=44  seq=483791764 win=1024 <mss 1460>
SENT (1.1742s) TCP 192.168.60.1:49528 > 192.168.60.10:8080 S ttl=47 id=58493 iplen=44  seq=483660694 win=1024 <mss 1460>
```

**Qué buscar.** Dos `SENT` y ningún `RCVD`. El segundo sale **1,1 segundos**
después del primero: Nmap reintenta porque no puede distinguir un cortafuegos
silencioso de una red con pérdidas.

Fíjate también en `ttl=56` y `ttl=47`. Nmap **aleatoriza el TTL de salida** en
cada sonda, por defecto.

**Por qué importa.** Ese reintento es la razón de que los puertos filtrados
sean lo que hace lento un escaneo. Los 65 527 puertos cerrados del Paso 3 se
resolvieron al instante porque **contestaron**. Los dos filtrados costaron más
que todos los demás juntos.

De ahí sale una regla práctica: si un escaneo tarda muchísimo, casi siempre es
porque hay un cortafuegos tirando paquetes, no porque haya muchos puertos.

---

## Cuaderno de bitácora (opcional)

Para ti. Nadie lo revisa.

1. Una tabla con los cuatro estados que produce `nmap-target`, y **al lado de
   cada uno, la línea de `tcpdump`** que lo justifica. Cápturalas tú.
2. La salida de `--packet-trace` de un `-sS` y de un `-sT` contra el 445, y dos
   o tres líneas explicando por qué dan razones distintas.
3. Una frase: si mañana te dan un `/24` desconocido y 10 minutos, ¿qué comando
   lanzas primero y por qué ese y no `-p-`?

## Autoevaluación

- [ ] Sé por qué `-sn` encuentra una máquina que no responde al ping, y sé en
      qué situación dejaría de encontrarla.
- [ ] Puedo decir, sin ejecutarlo, qué bandera devuelve un puerto cerrado.
- [ ] Sé explicar por qué `-sT` reporta `host-unreach` donde `-sS` reporta
      `admin-prohibited`, sin decir «porque -sS es mejor».
- [ ] Entiendo que la columna `SERVICE` de este episodio es una conjetura
      basada en el número de puerto.
- [ ] Sé por qué un escaneo con puertos filtrados tarda más que uno con miles
      de puertos cerrados.

## Solución de problemas

| Síntoma | Qué pasa |
|---|---|
| `You requested a scan type which requires root privileges` | `-sS`, `-sU`, `-O` y `-sA` mandan paquetes a mano. Usa `sudo`, o Kali, o el contenedor del README |
| `Failed to determine route to 192.168.60.10` | Las VMs no están levantadas o el adaptador host-only no se creó. `vagrant status` y luego `vagrant up` |
| El barrido sólo encuentra 1 host | Estás escaneando desde una VM que no tiene interfaz en `192.168.60.0/24`. Comprueba con `ip -4 addr` |
| `-sT` marca todo `filtered` y tarda muchísimo | Algún cortafuegos local o antivirus está interceptando. Prueba desde la VM de Kali |
| El 16379 no aparece | Estás usando el escaneo por defecto. Necesitas `-p-` o `-p16379` |

## Uso ético

Cada comando de este episodio manda paquetes no solicitados a una máquina.
Contra tus propias VMs, es aprendizaje. Contra cualquier otra cosa, es un
escaneo no autorizado, queda registrado en el destino y puede constituir un
delito aunque no explotes nada. No hay una versión «de sólo mirar» de Nmap.

---

**Siguiente:** [Episodio 2 — Qué hay detrás de la puerta](episodio-2-detras-de-la-puerta.md),
donde la columna `SERVICE` deja de ser una conjetura.
