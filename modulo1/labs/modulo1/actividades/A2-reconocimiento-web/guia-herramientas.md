# A.2 — Las diez herramientas

Cada una responde **una** pregunta. Si no sabes cuál, no sabes por qué la estás
ejecutando. Todas vienen en Kali salvo `katana`.

| Herramienta | La pregunta que responde | ¿En Kali? |
|---|---|---|
| `dig` | ¿Qué dice el DNS sobre este dominio? | sí |
| `dnsrecon` | Lo mismo, automatizado y en lote | sí |
| `curl` | ¿Qué me devuelve el servidor si se lo pido a pelo? | sí |
| `whatweb` | ¿Con qué está construido y qué versión corre? | sí |
| `feroxbuster` | ¿Qué hay publicado que no esté enlazado? | sí |
| `ffuf` | ¿Hay otros sitios sirviéndose desde esta misma IP? | sí |
| `katana` | ¿Qué endpoints, scripts y correos hay si lo rastreo entero? | **no** — `sudo apt install katana` |
| `nikto` | ¿Qué marcaría un escáner de configuración? | sí |
| `exiftool` | ¿Qué llevan dentro los documentos publicados? | sí |
| `cewl` | ¿Qué diccionario sale del vocabulario del propio sitio? | sí |

---

## `dig` — interrogar el DNS

```bash
dig @192.168.56.20 nordlysai.dk A        # dirección
dig @192.168.56.20 nordlysai.dk MX       # servidores de correo
dig @192.168.56.20 nordlysai.dk TXT      # SPF, DMARC, verificaciones de proveedor
dig @192.168.56.20 nordlysai.dk AXFR     # transferencia de zona completa
dig @192.168.56.20 -x 192.168.56.10      # resolución inversa: de IP a nombre
dig +short @192.168.56.20 www.nordlysai.dk   # sólo la respuesta
```

`AXFR` es el que importa. En un servidor bien configurado devuelve
`Transfer failed`. Si devuelve la zona entera, acabas de encontrar un fallo
serio: te llevas **todos** los nombres, incluidos los internos y los que nadie
enlaza.

Los TXT delatan proveedores: un token de verificación dice qué SaaS usa la
empresa, y eso ya orienta un ataque de suplantación.

## `dnsrecon` — el DNS en lote

```bash
dnsrecon -n 192.168.56.20 -d nordlysai.dk -a
```

`-n` fija el servidor, `-d` el dominio, `-a` incluye el intento de transferencia
de zona. Úsalo **después** de haber hecho los `dig` a mano: primero entiende qué
preguntas se están haciendo, y luego automatiza.

## `curl` — la herramienta que más rinde

```bash
curl -s  http://192.168.58.10/                  # cuerpo
curl -sI http://192.168.58.10/                  # sólo cabeceras
curl -s  http://192.168.58.10/robots.txt
curl -sO http://192.168.58.10/uploads/fichero    # descargar conservando el nombre
curl -s -H "Host: otro.dominio" http://192.168.58.10/   # forzar el vhost
```

La mitad de los hallazgos de esta actividad salen de aquí. No la subestimes por
ser la más simple.

## `whatweb` — huella tecnológica

```bash
whatweb http://192.168.58.10/
whatweb -v http://192.168.58.10/     # detalle de cada coincidencia
```

Te dice servidor, versión, framework, generador y cabeceras llamativas. Una
versión concreta es el primer paso para buscar vulnerabilidades conocidas — pero
eso ya es otra actividad.

## `feroxbuster` — descubrimiento de contenido

```bash
feroxbuster -u http://192.168.58.10 -x bak,old,txt,env,zip,tar.gz -d 2
feroxbuster -u http://192.168.58.10 -w /usr/share/wordlists/dirb/common.txt
```

Recursivo por defecto. Las extensiones (`-x`) son la mitad del truco: los
archivos que interesan casi nunca son `.html`.

Si prefieres `gobuster`, hace lo mismo sin recursión:
`gobuster dir -u http://192.168.58.10 -w <lista> -x bak,env,txt`

## `ffuf` — fuzzing, y sobre todo de vhosts

```bash
# Descubrimiento de rutas
ffuf -u http://192.168.58.10/FUZZ -w /usr/share/wordlists/dirb/common.txt

# Vhosts: ¿qué otros sitios responde esta IP?
ffuf -u http://192.168.58.10/ -H "Host: FUZZ.nordlysai.dk" \
     -w /usr/share/wordlists/dirb/common.txt -fs 0 -mc 200
```

`FUZZ` es el marcador que se sustituye. `-fs` filtra por tamaño de respuesta y
`-mc` limita a ciertos códigos: sin filtros te ahogas en ruido, porque el
servidor responde algo a todo.

El fuzzing de `Host` encuentra sitios que **no tienen registro DNS**. Es el
único camino a uno de los hallazgos de esta actividad.

## `katana` — rastreo

```bash
katana -u http://192.168.58.10 -d 2
katana -u http://192.168.58.10 -jc -d 2     # -jc: rastrea también el JavaScript
```

Donde `feroxbuster` adivina nombres, `katana` **sigue enlaces**. Son
complementarias: una encuentra lo no enlazado, la otra recorre exhaustivamente
lo que sí lo está, incluidos los `.js` donde la gente deja claves.

## `nikto` — escáner de configuración

```bash
nikto -h http://192.168.58.10
```

Comprueba una lista de fallos conocidos: archivos peligrosos, cabeceras que
faltan, versiones que se anuncian, directorios listables. Es **ruidoso** —en una
red real lo ve cualquier IDS— y produce falsos positivos. Úsalo como segunda
opinión sobre lo que ya encontraste a mano, no como sustituto.

## `exiftool` — metadatos

```bash
exiftool documento.pdf                # todo
exiftool -Keywords -Creator -Producer documento.pdf
```

Los documentos publicados llevan dentro quién los hizo, con qué programa y a
veces en qué máquina. Nadie lo revisa antes de publicar. Por eso funciona.

## `cewl` — diccionario a medida

```bash
cewl http://192.168.58.10/ -d 2 -m 6 -w palabras.txt
```

`-d` profundidad de rastreo, `-m` longitud mínima de palabra, `-w` archivo de
salida. No busca vulnerabilidades: construye un **diccionario de contraseñas con
el vocabulario de la propia víctima** — sus productos, sus clientes, su jerga.
Es el mejor argumento que existe para exigir contraseñas que no tengan nada que
ver con la empresa.

---

## Lo que NO toca esta actividad

`nmap` y todo lo que sea escaneo de puertos, detección de servicios o versiones
es **A.4**. Aquí nos quedamos en DNS, HTTP y contenido. La frontera es
deliberada: reconocimiento de superficie primero, inventario de servicios
después.
