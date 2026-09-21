# Apéndice A — tcpdump: ver lo que de verdad sale de tu máquina

> **Miniworkshop «Nmap de cero a experto» · Apéndice**
> Certificación en Ciberdefensa Avanzada — Universidad Anáhuac Mayab

## Objetivo

Que puedas comprobar por tu cuenta cualquier cosa que Nmap te diga, mirando los
paquetes en lugar de creerte la tabla — y que leas la notación de banderas de
`tcpdump` sin traducirla mentalmente.

## Antes de empezar

**Qué necesitas**

- El laboratorio levantado.
- `tcpdump` en tu máquina (`sudo apt install tcpdump`) **o** ninguna
  instalación: las dos VMs ya lo traen, y capturar **desde el objetivo** es más
  instructivo que hacerlo desde el atacante.
- Privilegios de root allí donde captures. Dentro de las VMs los tienes con
  `sudo`, sin contraseña.

**Cuánto rinde:** ~40 minutos, pero está pensado para usarse **a trozos**,
mientras haces los tres episodios.

**Este apéndice es transversal.** No lo leas de corrido: cuando un episodio te
pida capturar algo, vuelve aquí por la sintaxis.

> **Wireshark hace lo mismo con ratón.** Si ya lo usas, esto sigue mereciendo
> la pena: en un servidor remoto por SSH no hay interfaz gráfica, y `tcpdump`
> es lo único que hay. Lo capturado con `-w` se abre en Wireshark tal cual.

---

## Por qué capturar

En el Episodio 1 aparece una contradicción: `-sS` dice que el puerto 445 está
`filtered` por `admin-prohibited`, y `-sT` dice que lo está por `host-unreach`.
Las dos salidas son de la misma herramienta contra el mismo puerto.

No se resuelve leyendo más documentación. Se resuelve mirando el paquete. Esa
es la única razón por la que este apéndice existe: **Nmap interpreta, y la
captura no interpreta.**

## 1 — Empezar a capturar

```bash
# 1. Qué interfaces hay
sudo tcpdump -D
```

```bash
# 2. Capturar en la interfaz del laboratorio, sin resolver nombres
sudo tcpdump -ni eth1
```

Las dos banderas que se ponen siempre:

| Bandera | Qué hace | Por qué importa |
|---|---|---|
| `-i eth1` | Elige la interfaz | Sin ella captura en cualquiera y te ahoga en ruido |
| `-n` | No resuelve IPs a nombres | Sin ella, cada paquete dispara una consulta DNS **que también capturas**. Y en un objetivo comprometido, avisa |

`-nn` además deja los puertos como números (`443` en vez de `https`).

**En las VMs del laboratorio la interfaz es `eth1`.** `eth0` es la NAT de
Vagrant, por donde va tu propia sesión SSH: capturar ahí es verte a ti mismo.

```bash
# 3. Lo más útil del apéndice: capturar EN el objetivo
vagrant ssh nmap-target -c "sudo tcpdump -ni eth1"
```

Otras banderas que se usan de verdad:

| Bandera | Para qué |
|---|---|
| `-c 20` | Parar tras 20 paquetes. **Ponla siempre**, o te quedas sin terminal |
| `-w captura.pcap` | Guardar en disco (se abre con Wireshark) |
| `-r captura.pcap` | Leer un fichero ya guardado |
| `-e` | Mostrar cabeceras Ethernet (las MAC) |
| `-v`, `-vv` | Más detalle: TTL, longitud, identificador IP |
| `-A` | Contenido en ASCII. Para ver credenciales en claro |
| `-X` | Contenido en hexadecimal y ASCII |
| `-tt` | Marca de tiempo absoluta, para correlacionar con logs |
| `-s 0` | Capturar el paquete entero (por defecto ya lo hace) |

## 2 — Leer la línea

Esta es una línea real, capturada en `nmap-target`:

```
07:50:24.430373 IP 192.168.60.1.35024 > 192.168.60.10.22: Flags [S], seq 1986689431, win 64240, options [mss 1460,sackOK,TS val 2177619312 ecr 0,nop,wscale 7], length 0
```

Pieza a pieza:

| Trozo | Significa |
|---|---|
| `07:50:24.430373` | Hora, con microsegundos |
| `192.168.60.1.35024` | Origen: IP **punto** puerto. El último número es el puerto |
| `>` | Dirección |
| `192.168.60.10.22` | Destino: puerto 22 |
| `Flags [S]` | Las banderas TCP. Lo importante |
| `seq 1986689431` | Número de secuencia |
| `win 64240` | Ventana anunciada |
| `options [...]` | Opciones TCP negociadas |
| `length 0` | Bytes de datos. Un SYN no lleva datos |

## 3 — Las banderas, que es lo que hay que aprender

TCP tiene ocho bits de bandera. `tcpdump` los abrevia con una letra, y **el ACK
es un punto**:

| Notación | Bandera | Cuándo aparece |
|---|---|---|
| `[S]` | SYN | Primer paquete: «quiero abrir una conexión» |
| `[S.]` | SYN+ACK | «Acepto, y confirmo el tuyo» |
| `[.]` | ACK a secas | Confirmación. El paquete más común de Internet |
| `[P.]` | PSH+ACK | Lleva datos y pide entregarlos ya |
| `[F.]` | FIN+ACK | «He terminado de enviar» |
| `[R]` | RST | «Corta ya». Sin ACK: lo manda quien aborta |
| `[R.]` | RST+ACK | El rechazo de un puerto **cerrado** |
| `[none]` | ninguna | El paquete NULL de `-sN`. No existe en el tráfico normal |
| `[FPU]` | FIN+PSH+URG | El paquete Xmas de `-sX`. Tampoco existe en la naturaleza |

**El punto es el ACK.** Si interiorizas sólo eso, ya lees el 90 % de una
captura. `[S]` es un SYN solo; `[S.]` es SYN con ACK; `[.]` es ACK sin nada
más.

> La página [`web/handshake-tcp.html`](../web/handshake-tcp.html) anima estos
> intercambios mostrando los ocho bits encendidos o apagados, y **debajo de cada
> paquete escribe la línea de `tcpdump` que produce**. Es la misma notación de
> esta tabla.

## 4 — Los seis intercambios del miniworkshop

Todas las capturas siguientes son reales, tomadas en `nmap-target` mientras se
escaneaba desde `192.168.60.1`.

### Conexión completa (lo que hace `-sT`)

```bash
vagrant ssh nmap-target -c "sudo tcpdump -ni eth1 -c 6 'tcp port 22 and host 192.168.60.1'"
# en otra terminal:  nc -w1 -z 192.168.60.10 22
```

```
192.168.60.1.35024 > 192.168.60.10.22: Flags [S], seq 1986689431, win 64240, options [mss 1460,sackOK,TS val 2177619312 ecr 0,nop,wscale 7], length 0
192.168.60.10.22 > 192.168.60.1.35024: Flags [S.], seq 629903496, ack 1986689432, win 31856, options [mss 1460,sackOK,TS val 2858291632 ecr 2177619312,nop,wscale 7], length 0
192.168.60.1.35024 > 192.168.60.10.22: Flags [.], ack 1, win 502, length 0
192.168.60.1.35024 > 192.168.60.10.22: Flags [F.], seq 1, ack 1, win 502, length 0
192.168.60.10.22 > 192.168.60.1.35024: Flags [.], ack 2, win 249, length 0
192.168.60.10.22 > 192.168.60.1.35024: Flags [P.], seq 1:22, ack 2, win 249, length 21: SSH: SSH-2.0-OpenSSH_9.6
```

`[S]` → `[S.]` → `[.]` es el handshake de tres vías. Fíjate en `ack 1986689432`:
es el `seq` del primer paquete **más uno**. Así se confirma en TCP.

Y en la última línea, el servidor manda su banner: `SSH-2.0-OpenSSH_9.6`. **De
ahí sale la columna VERSION de `-sV`.**

### SYN scan (lo que hace `-sS`)

```
192.168.60.1.57850 > 192.168.60.10.22: Flags [S], seq 250831424, win 1024, options [mss 1460], length 0
192.168.60.10.22 > 192.168.60.1.57850: Flags [S.], seq 117182751, ack 250831425, win 32120, options [mss 1460], length 0
192.168.60.1.57850 > 192.168.60.10.22: Flags [R], seq 250831425, win 0, length 0
```

Tres paquetes en vez de seis. En cuanto llega el `[S.]`, Nmap ya sabe que está
abierto y corta con `[R]`. **No hay `[.]`, no hay sesión, no hay banner.**

Compara las dos primeras líneas con las del caso anterior:

| | Conexión real | SYN scan |
|---|---|---|
| Ventana | `win 64240` | `win 1024` |
| Opciones | `mss, sackOK, TS, nop, wscale` | sólo `mss 1460` |

**Esa es la firma de Nmap.** Un IDS lo detecta por aquí, no por el volumen. Es
exactamente el patrón que buscan las reglas de Suricata de la Actividad A.8.

### Puerto cerrado

```
192.168.60.1.44826 > 192.168.60.10.9999: Flags [S], seq 890262944, win 64240, ...
192.168.60.10.9999 > 192.168.60.1.44826: Flags [R.], seq 0, ack 890262945, win 0, length 0
```

`[R.]` = RST+ACK. Eso es `closed` y la razón `reset`.

### Puerto filtrado por descarte (DROP)

```
192.168.60.1.52769 > 192.168.60.10.8080: Flags [S], seq 144826644, win 1024, options [mss 1460], length 0
192.168.60.1.52771 > 192.168.60.10.8080: Flags [S], seq 144957718, win 1024, options [mss 1460], length 0
```

Dos paquetes, los dos **salientes**. Ninguna respuesta. El segundo es el
reintento. Eso es `filtered` con razón `no-response`, y es lo que hace lentos
los escaneos.

### Puerto filtrado por rechazo (REJECT)

```
192.168.60.1.60175 > 192.168.60.10.445: Flags [S], seq 555822019, win 1024, options [mss 1460], length 0
192.168.60.10 > 192.168.60.1: ICMP host 192.168.60.10 unreachable - admin prohibited filter, length 52
```

Aquí está **la prueba** que resuelve la contradicción del Episodio 1. El
cortafuegos responde con ICMP tipo 3 código 13. `-sS` lee ese paquete y reporta
`admin-prohibited`. `-sT` sólo recibe el error que le devuelve `connect()`, que
ya no distingue el código, y reporta `host-unreach`.

Ninguna de las dos miente. Una ve el paquete y la otra no.

### UDP

```
192.168.60.1.57222 > 192.168.60.10.53: 6+ TXT CHAOS? version.bind. (30)
192.168.60.1.57222 > 192.168.60.10.161:  GetRequest(32)  .1.3.6.1.2.1.1.5.0
192.168.60.10 > 192.168.60.1: ICMP 192.168.60.10 udp port 53 unreachable, length 66
```

Nmap no manda UDP vacío: manda una consulta DNS de verdad y un GetRequest SNMP
de verdad. Y el «cerrado» de UDP es ese ICMP de la tercera línea — el que el
núcleo emite con cuentagotas, y por eso `-sU` tarda tanto.

## 5 — Filtros: capturar sólo lo que importa

La sintaxis se llama **BPF**. Lo básico:

```bash
# 4. Por máquina, por red, por puerto
sudo tcpdump -ni eth1 host 192.168.60.1
sudo tcpdump -ni eth1 net 192.168.60.0/24
sudo tcpdump -ni eth1 port 80
sudo tcpdump -ni eth1 portrange 8000-8100

# 5. Por dirección
sudo tcpdump -ni eth1 src 192.168.60.1
sudo tcpdump -ni eth1 dst port 443

# 6. Por protocolo
sudo tcpdump -ni eth1 icmp
sudo tcpdump -ni eth1 udp port 161
sudo tcpdump -ni eth1 arp

# 7. Combinando con and / or / not
sudo tcpdump -ni eth1 'host 192.168.60.1 and not port 22'
sudo tcpdump -ni eth1 'tcp port 80 or tcp port 443'
```

> **Comillas simples.** `and`, `or`, `(` y `)` los interpreta el shell si no
> entrecomillas. Cuando un filtro «no funciona», es casi siempre esto.

### Filtrar por banderas: lo que hace útil a tcpdump frente a un escaneo

`tcp[tcpflags]` lee el byte de banderas, y hay constantes con nombre:

```bash
# 8. Sólo SYN: cada intento de conexión entrante
sudo tcpdump -ni eth1 'tcp[tcpflags] & tcp-syn != 0'

# 9. SYN pero NO SYN+ACK: peticiones de apertura, sin las respuestas
sudo tcpdump -ni eth1 'tcp[tcpflags] & (tcp-syn|tcp-ack) == tcp-syn'

# 10. Sólo RST: puertos cerrados contestando. Un escaneo se ve como una ráfaga
sudo tcpdump -ni eth1 'tcp[tcpflags] & tcp-rst != 0'

# 11. Paquetes SIN ninguna bandera: el escaneo NULL. No existe legítimamente
sudo tcpdump -ni eth1 'tcp[tcpflags] == 0'

# 12. El paquete Xmas: FIN+PSH+URG a la vez
sudo tcpdump -ni eth1 'tcp[tcpflags] == (tcp-fin|tcp-push|tcp-urg)'
```

**Los comandos 11 y 12 son detección de intrusiones en una línea.** Ningún
sistema operativo genera esos paquetes en condiciones normales: si aparecen,
alguien está escaneando con `-sN` o `-sX`. Pruébalo: deja el comando 12
corriendo en `nmap-target` y lanza `sudo nmap -sX -p22 192.168.60.10`.

## 6 — Ejercicio: cazar tu propio escaneo

Deja esto corriendo en la VM:

```bash
# 13. Contar cada tipo de paquete durante un escaneo
vagrant ssh nmap-target -c "sudo tcpdump -ni eth1 -c 200 -tt 'host 192.168.60.1'" > escaneo.txt
```

Lanza desde tu máquina `sudo nmap -sS -F 192.168.60.10` y luego:

```bash
# 14. ¿Cuántos SYN entraron y cuántos RST salieron?
grep -c "Flags \[S\]," escaneo.txt
grep -c "Flags \[R\.\]" escaneo.txt

# 15. ¿En cuánto tiempo?
head -1 escaneo.txt | cut -d' ' -f1
tail -1 escaneo.txt | cut -d' ' -f1
```

**Lo que hay que ver.** Cientos de SYN desde una sola IP, a puertos distintos,
en menos de un segundo, y una avalancha de RST de vuelta. Ningún cliente
legítimo se comporta así: un navegador abre unas pocas conexiones al mismo
puerto. Esa forma —muchos puertos, un origen, muy rápido, casi todo RST— **es**
la definición práctica de «escaneo de puertos» para un IDS.

Acabas de escribir, a mano, la regla de detección que configurarás en Suricata
en la Actividad A.8 del Módulo I.

## 7 — Tráfico en claro

```bash
# 16. Ver el contenido de lo que viaja sin cifrar
vagrant ssh nmap-target -c "sudo tcpdump -ni eth1 -A -c 20 'tcp port 21'"
# en otra terminal:  ftp 192.168.60.10   (usuario: anonymous)
```

Verás `USER anonymous` y `PASS ...` legibles. Repítelo contra el 80 y luego
contra el 443: en el segundo caso sólo hay ruido, porque TLS cifra.

Ahí está el límite honesto de la herramienta: **`tcpdump` ve lo que hay en el
cable, y si está cifrado, ve que está cifrado.** Lo que sí sigue viendo —y
sigue siendo mucho— son las direcciones, los puertos, los tamaños, los tiempos
y, en TLS, el nombre del servidor en el `Server Name Indication`.

## Autoevaluación

- [ ] Sé qué significan `[S]`, `[S.]`, `[.]`, `[R]` y `[R.]` sin consultar.
- [ ] Puedo distinguir en una captura un SYN scan de una conexión real, y sé en
      qué campo me fijo.
- [ ] Sé escribir un filtro que muestre sólo los intentos de apertura.
- [ ] Puedo demostrar con una captura por qué `-sS` y `-sT` dan razones
      distintas para el puerto 445.
- [ ] Sé qué deja de ver `tcpdump` cuando el tráfico va por TLS, y qué sigue viendo.

## Solución de problemas

| Síntoma | Qué pasa |
|---|---|
| `You don't have permission to capture` | Falta root. `sudo`, o dentro de la VM |
| No se captura nada | Interfaz equivocada. En las VMs del lab es `eth1`, no `eth0` |
| Aparecen paquetes DNS que no pediste | Te falta `-n`: tcpdump está resolviendo nombres y capturando sus propias consultas |
| `syntax error` en el filtro | Falta entrecomillar. Usa comillas simples alrededor del filtro entero |
| La captura no para nunca | Faltó `-c`. `Ctrl+C` corta y muestra el resumen |
| Sólo veo mi sesión SSH | Estás capturando en `eth0`, la NAT de Vagrant. Cambia a `eth1` |

## Uso ético

Capturar tráfico es leer las comunicaciones de otros. En una red que no es tuya
—una wifi pública, la de un cliente, la de tu empresa sin autorización
explícita— es interceptación de comunicaciones, y en México y en la UE está
tipificada con independencia de lo que hagas con lo capturado. Un `.pcap` de
una red ajena contiene datos personales de terceros: si lo generas en una
auditoría autorizada, entra en el alcance de la LFPDPPP y del GDPR, hay que
custodiarlo y hay que destruirlo al cerrar el encargo.

---

**Volver a:** [Episodio 1](../episodios/episodio-1-quien-esta-ahi.md) ·
[Episodio 2](../episodios/episodio-2-detras-de-la-puerta.md) ·
[Episodio 3](../episodios/episodio-3-nse-de-usuario-a-autor.md)
