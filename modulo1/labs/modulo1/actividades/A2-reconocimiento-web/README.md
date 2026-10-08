# A.2 — Lo que tu organización publica sin saberlo

> **Módulo I · Sesión 1 · Anexo A.2 del syllabus**
> Certificación en Ciberdefensa Avanzada — Universidad Anáhuac Mayab

## Objetivo

Que reconstruyas el **perfil externo de una organización** —sus nombres, su
infraestructura, su gente, sus fallos de configuración— usando únicamente lo
que ella misma publica, y que entregues el resultado como un **informe que un
responsable pueda accionar**, no como un volcado de salida de comandos.

Lo que se evalúa no es cuántas banderas encuentres, sino que puedas explicar
**qué hallazgo cambia una decisión** y por qué.

## Antes de empezar

**Tu objetivo**

Nordlys AI ApS, dominio **`nordlysai.dk`**. Una consultora danesa de IA. La
empresa es **ficticia** y vive entera dentro del laboratorio del módulo.

| Desde | Servidor DNS | Web |
|---|---|---|
| Kali (con adaptador en `dmz_net`) | `192.168.56.20` | por nombre, tras apuntar el resolver |
| El host, sin Kali | `192.168.58.20` | `http://192.168.58.10/` |

Fíjate en que **no te damos la IP del servidor web**. Te damos un dominio y un
servidor de nombres, como en un encargo real. La primera IP la descubres tú.

**Qué necesitas**

- El laboratorio levantado: `cd labs/modulo1 && vagrant up`.
- Kali con un adaptador en `dmz_net` (ver «Integración con Kali» en el README
  del laboratorio). Sin Kali puedes hacer casi todo desde el host.
- De las diez herramientas, **nueve vienen preinstaladas en Kali**. La décima no:

  ```
  sudo apt install katana
  ```

**Qué tienes que tener en cuenta**

- **Esto es reconocimiento, no explotación.** Vas a leer lo que el servidor
  ofrece. No vas a entrar en ningún sitio, ni probar credenciales, ni explotar
  nada. Si encuentras una contraseña, la documentas; no la usas.
- **Nada de esto se ejecuta fuera del laboratorio.** Los mismos comandos contra
  un dominio de terceros sin autorización te meten en un problema legal real.
  La demo de clase explica dónde está exactamente esa frontera.
- **Los puertos y servicios son A.4, no A.2.** Aquí te quedas en DNS, HTTP y
  contenido. Resiste la tentación de lanzar `nmap`: ya llegará.
- **No abras `/uploads/backup-notes.txt` hasta la Fase 5.** Son las notas del
  propio administrador y arruinan el ejercicio si las lees antes. Explicado
  más abajo.
- **Rinde ~90 minutos.** Es la actividad más larga del módulo.

**Herramientas**

`dig` · `dnsrecon` · `curl` · `whatweb` · `feroxbuster` · `ffuf` · `katana` ·
`nikto` · `exiftool` · `cewl` — referencia de uso en
[`guia-herramientas.md`](guia-herramientas.md).

**Entrega**

En **Brightspace**, tarea «A.2 — Reconocimiento web», **antes del domingo 27 de
septiembre a las 23:59**.

---

## Introducción

Ninguna organización decide qué publica. Lo va publicando: un desarrollador
sube un archivo de configuración por error, alguien deja activado el listado de
un directorio, el equipo de RR. HH. exporta una lista de empleados a una carpeta
que resulta ser pública, un administrador crea un registro DNS para un servidor
que después nadie da de baja. Cada decisión fue razonable por separado. El
conjunto es un plano de la casa.

El reconocimiento consiste en leer ese plano antes que el atacante, y es la
primera fase de cualquier auditoría, de cualquier pentest y de cualquier
ejercicio serio de higiene de exposición. También es la fase más barata: casi
todo lo que vas a encontrar hoy está a un `curl` de distancia.

La pregunta que persigue esta actividad no es *«¿qué puedo encontrar?»* sino
**«¿qué de lo que encontré obliga a cambiar algo el lunes por la mañana?»**.

---

## Resultados de aprendizaje

Al terminar, puedo:

1. **Interrogar un servidor DNS** para obtener registros A, MX y TXT, intentar
   una transferencia de zona y hacer resolución inversa, y explicar qué revela
   cada uno.
2. **Reconocer una transferencia de zona mal configurada** y explicar por qué
   es grave y cómo se corrige.
3. **Descubrir contenido no enlazado** en un servidor web y distinguir entre lo
   que está oculto y lo que sólo está *sin enlazar*.
4. **Extraer información de los archivos** que una organización publica:
   metadatos, exportaciones, respaldos.
5. **Derivar las convenciones de nombres** de una organización (correo y usuario)
   a partir de datos públicos, y explicar para qué le sirven a un atacante.
6. **Escribir un informe de superficie expuesta** que priorice por riesgo y
   proponga correcciones concretas.
7. **Razonar sobre los datos personales** que produce mi propio reconocimiento.

---

## Paso a paso

Cinco fases, ~90 minutos, **20 banderas**. Ve anotándolas en
[`plantilla-inventario.md`](plantilla-inventario.md) según las encuentres.

El formato es siempre `FLAG{nordlys_<tema>_<4 hex>}`.

### Fase 1 — El DNS · 15 min · banderas 1–4

Antes de mirar la web, pregúntale al DNS. Es lo que haría cualquiera.

```bash
# 1 · ¿Qué hay detrás del dominio?
dig @192.168.56.20 nordlysai.dk A
dig @192.168.56.20 www.nordlysai.dk A
dig @192.168.56.20 nordlysai.dk MX

# 2 · Los registros TXT cuentan con quién trabajas
dig @192.168.56.20 nordlysai.dk TXT

# 3 · ¿Deja el servidor que cualquiera se lleve la zona entera?
dig @192.168.56.20 nordlysai.dk AXFR

# 4 · Y al revés: de la IP al nombre
dig @192.168.56.20 -x 192.168.56.10
```

`dnsrecon` automatiza los cuatro pasos de golpe:

```bash
dnsrecon -n 192.168.56.20 -d nordlysai.dk -a
```

**Qué buscar.** Un registro TXT de verificación dice qué proveedores usa la
empresa. La transferencia de zona, si está abierta, entrega **todos** los
nombres de una vez, incluidos los que no están enlazados en ningún sitio y los
del espacio interno. La resolución inversa revela nombres de máquina que el
sitio no menciona.

> **Reflexión obligatoria para el informe.** Si la transferencia de zona
> estuviera bien configurada, **¿cuáles de las banderas 1–4 habrías conseguido
> igual, y cuáles no?** Esa diferencia es exactamente el valor de cerrar el
> AXFR, y es lo que le vas a explicar al responsable.

### Fase 2 — Reconocimiento pasivo del sitio · 15 min · banderas 5–9

Sin herramientas pesadas: el navegador y `curl`. Aquí se saca más de lo que
parece.

```bash
curl -s   http://192.168.58.10/ | less        # comentarios en el fuente
curl -sI  http://192.168.58.10/               # cabeceras de respuesta
curl -s   http://192.168.58.10/robots.txt     # lo que piden no indexar
curl -s   http://192.168.58.10/sitemap.xml    # lo que sí quieren indexado
curl -s   http://192.168.58.10/.well-known/security.txt
curl -s   http://192.168.58.10/ruta-que-no-existe   # la página de error propia
whatweb   http://192.168.58.10/
```

**Qué buscar.** `robots.txt` es una lista de lo que no quieren que mires: úsala
como índice. Las cabeceras delatan versiones y compilaciones. La página de error
propia suele estar peor revisada que la portada.

### Fase 3 — Enumeración de contenido · 30 min · banderas 10–16

Ahora sí, a buscar lo que no está enlazado.

```bash
# Descubrimiento recursivo con extensiones interesantes
feroxbuster -u http://192.168.58.10 -x bak,old,txt,env,zip,tar.gz -d 2

# Rastreo: endpoints, JavaScript, correos, enlaces
katana -u http://192.168.58.10 -jc -d 2

# Segundo par de ojos automatizado sobre la configuración
nikto -h http://192.168.58.10

# ¿Hay más sitios en esta misma IP? Fuzzing de la cabecera Host
ffuf -u http://192.168.58.10/ -H "Host: FUZZ.nordlysai.dk" \
     -w /usr/share/wordlists/dirb/common.txt -fs 0 -mc 200
```

**Qué buscar.** Archivos de configuración (`.env`), directorios de control de
versiones (`.git/`), copias de seguridad (`.bak`), listados de directorio
abiertos, y páginas que existen pero no están enlazadas desde ningún menú.

> **Una de las banderas de esta fase no se encuentra por DNS ni navegando.**
> Hay un sitio en este servidor que sólo responde si le fijas la cabecera `Host`
> a mano — y **no tiene registro DNS a propósito**. Si en la Fase 1 hiciste la
> transferencia de zona, comprueba: no aparece. Ésa es la lección: *el DNS no
> te enseña todo lo que hay publicado.*

### Fase 4 — Análisis de los archivos hallados · 15 min · banderas 17–20

Los archivos que descargaste en la fase anterior tienen más dentro.

```bash
# Metadatos de documentos: quién, con qué, en qué máquina
curl -sO http://192.168.58.10/uploads/nordlys-ai-whitepaper-2026.pdf
exiftool nordlys-ai-whitepaper-2026.pdf

# Exportaciones de datos
curl -s http://192.168.58.10/uploads/medarbejderliste-eksport.csv

# Respaldos: un sitio viejo suele tener las credenciales de entonces
curl -sO http://192.168.58.10/uploads/site-backup-2024.tar.gz
tar -xzf site-backup-2024.tar.gz && ls -R
```

**Y la parte que no da bandera pero sí informe — las convenciones de nombres:**

```bash
# Correos y su patrón
curl -s http://192.168.58.10/team.html | grep -oE '[a-z.]+@nordlysai\.dk' | sort -u

# Usuarios del sistema: fíjate en que la convención NO es la misma
curl -s http://192.168.58.10/uploads/medarbejderliste-eksport.csv \
  | grep -v '^#' | tail -n +2 | cut -d, -f2,3,5
```

Con esas dos convenciones y la lista de personas, cualquiera construye una lista
de usuarios válidos para probar en un portal. Eso es lo que hay que explicar en
el informe, y por eso una lista de empleados publicada no es un dato inocente.

### Fase 5 — El informe, y la confrontación · 15 min

Primero genera el diccionario, que es el hallazgo que más suele sorprender:

```bash
cewl http://192.168.58.10/ -d 2 -m 6 -w nordlys-palabras.txt
wc -l nordlys-palabras.txt
```

`cewl` no da bandera. Lo que hace es construir un diccionario de contraseñas con
el vocabulario del **propio sitio web de la víctima**. Ábrelo y piensa cuántas
de esas palabras podrían estar en la contraseña de alguien de la casa.

Ahora escribe el informe en [`plantilla-reporte.md`](plantilla-reporte.md).

**Y sólo cuando lo tengas escrito, abre esto:**

```bash
curl -s http://192.168.58.10/uploads/backup-notes.txt
```

Son las notas de operación del propio administrador, y describen **sus propios
fallos de configuración**. Compara su lista con la tuya:

- ¿Qué sabían ellos que tú no encontraste?
- ¿Qué encontraste tú que ellos no tenían apuntado?
- Sabiéndolo, **¿por qué seguía estando todo ahí?**

Esa última pregunta es la más importante de la actividad, y su respuesta rara
vez es técnica.

---

## Entregables

Dos archivos, en Brightspace, antes del **domingo 27 de septiembre, 23:59**.

**1. `A2-<apellido>-inventario.md`** — desde
[`plantilla-inventario.md`](plantilla-inventario.md): las banderas que
encontraste, **con qué herramienta y con qué comando** cada una.

**2. `A2-<apellido>-reporte.md`** (o PDF) — el **Reporte de Superficie
Expuesta**, desde [`plantilla-reporte.md`](plantilla-reporte.md):

| Apartado | Extensión |
|---|---|
| Resumen para dirección, sin jerga | 5–6 líneas |
| Tabla de hallazgos: qué, cómo se encontró, riesgo alto/medio/bajo | 8–15 filas |
| Las **cinco correcciones más urgentes**, en orden | 5 líneas |
| La reflexión sobre la transferencia de zona (Fase 1) | 3–4 líneas |
| La confrontación con `backup-notes.txt` (Fase 5) | 4–5 líneas |
| Datos personales: qué recogiste y qué harías si fueran reales | 4–5 líneas |

**Sobre el apartado de datos personales.** Al terminar tendrás nombres,
correos, teléfonos y cargos de quince personas. Aquí son inventadas. Si no lo
fueran, ese archivo sería un fichero de datos personales sujeto a la LFPDPPP y
al GDPR — y lo acabarías de subir a una plataforma educativa. ¿Qué habrías hecho
distinto? Lo retomamos en la Sesión 2 con el marco legal delante.

---

## Autoevaluación

- [ ] ¿Cada bandera de mi inventario lleva el **comando exacto** con el que salió?
- [ ] ¿Mi informe prioriza por **riesgo**, o es una lista plana de hallazgos?
- [ ] ¿Un responsable no técnico entendería mi resumen sin preguntarme nada?
- [ ] ¿Mis cinco correcciones son **accionables** («cerrar la transferencia de
      zona al secundario») y no genéricas («mejorar la seguridad»)?
- [ ] ¿Distingo lo que encontré por DNS de lo que encontré por fuzzing, y sé
      por qué el vhost oculto no aparecía en la zona?

## Preguntas guía del syllabus

La 1 y la 3 las responden tu informe y la sección de datos personales. Contesta
sólo ésta, en 2–3 líneas:

> **¿Qué diferencia hay entre la información que te da consultar el DNS y la que
> te da enumerar el contenido del servidor web?**

## Solución de problemas

| Síntoma | Salida |
|---|---|
| `dig` no responde | ¿Está arriba `ns-nordlys`? `vagrant status` desde `labs/modulo1`. |
| No resuelvo por nombre desde Kali | Apunta el resolver a `192.168.56.20`, o usa `dig @192.168.56.20` explícito. |
| `katana: command not found` | `sudo apt install katana` — es la única que no viene en Kali. |
| No alcanzo `192.168.58.10` desde Kali | Esa IP es la red host-only, para el host. Desde Kali usa `192.168.56.10`. |
| `feroxbuster` tarda muchísimo | Baja la profundidad con `-d 1` y limita las extensiones. |
| Encontré credenciales, ¿las pruebo? | **No.** Se documentan. Probarlas ya no es reconocimiento. |

## Uso ético

Todo el trabajo de esta actividad ocurre contra una empresa ficticia, en una red
aislada, dentro de tu propia máquina. **Ejecutar estos mismos comandos contra un
dominio de terceros sin autorización explícita y por escrito es ilegal**, por
pasivo que parezca el comando. La demo de la Sesión 1 dedica unos minutos a
dónde está esa frontera exactamente, porque no es donde la mayoría cree
(syllabus §10).

---

## Archivos de esta actividad

| Archivo | Para qué |
|---|---|
| [`guia-herramientas.md`](guia-herramientas.md) | Las diez herramientas: qué responde cada una y su sintaxis |
| [`plantilla-inventario.md`](plantilla-inventario.md) | Registro de banderas y hallazgos |
| [`plantilla-reporte.md`](plantilla-reporte.md) | Estructura del Reporte de Superficie Expuesta |
