# A.1 — Del relato al mapa: traducir un incidente real al lenguaje ATT&CK

> **Módulo I · Sesión 1 · Anexo A.1 del syllabus**
> Certificación en Ciberdefensa Avanzada — Universidad Anáhuac Mayab

## Objetivo

Que al leer el relato de un ataque real puedas **traducirlo a tácticas y
técnicas de MITRE ATT&CK**, justificar cada traducción con la frase del reporte
que la sustenta, y convertir el resultado en una imagen que sirva tanto a un
equipo técnico como a una dirección no técnica.

Lo que se evalúa no es tu manejo de la herramienta, sino que puedas **explicar
qué te entregó** y **en qué reunión profesional la usarías**.

## Antes de empezar

**Qué necesitas**

- Un navegador reciente (Firefox, Chrome o Edge) y conexión a Internet.
- Nada más. No hay VM, ni instalación, ni laboratorio que levantar.
- El laboratorio Vagrant de `labs/modulo1/` **no** se usa en esta actividad.

**Qué tienes que tener en cuenta**

- **La capa vive sólo en la pestaña del navegador.** No hay guardado
  automático, no hay cuenta, no hay «mis documentos». Si cierras la pestaña sin
  exportar, pierdes todo. Exporta en cuanto tengas tres o cuatro técnicas y
  vuelve a exportar al final.
- **Navigator no detecta ni bloquea nada.** No es un SIEM, no es un IDS, no es
  un antivirus. Es un lienzo para pintar comportamiento de adversarios. Si
  terminas la actividad sin tener esto clarísimo, no la terminaste.
- **Aquí no se ataca ni se escanea nada.** Todo el trabajo es sobre reportes
  públicos ya publicados. Ver la nota de uso ético al final (syllabus §10).
- **La herramienta y los reportes están en inglés; tu entrega va en español.**
  Los nombres de técnicas y tácticas se dejan en inglés tal cual aparecen
  (`Valid Accounts`, no «Cuentas válidas»): son identificadores, no traducción.
- Navigator admite **10 capas abiertas como máximo**. Suficiente de sobra.
- **Rinde ~60 minutos.** No es una carrera: el Paso 2 se hace en papel y es el
  que más decide la calidad del resultado.

**Herramienta**

MITRE ATT&CK® Navigator v5.3.2 — aplicación web, sin instalación.
Trabaja sobre **Enterprise ATT&CK v19**.

**URL**

<https://mitre-attack.github.io/attack-navigator/>

**Entrega**

En **Brightspace**, tarea «A.1 — ATT&CK Navigator», **antes del domingo 27 de
septiembre a las 23:59**. Sube los dos archivos del apartado *Entregables*;
Brightspace acepta varios adjuntos en una misma entrega.

---

## Introducción

Dos analistas describen el mismo ataque. El primero escribe «entraron por un
correo con un adjunto malicioso». El segundo escribe «el vector inicial fue
ingeniería social vía email con payload embebido». Dicen lo mismo. Y sin
embargo no se pueden sumar, ni comparar entre incidentes, ni contar, ni cruzar
con lo que tu organización sabe detectar. Multiplica eso por doce analistas,
cuatro proveedores y tres años de reportes: tienes un archivo lleno de prosa
que no sirve para tomar una sola decisión.

MITRE ATT&CK resuelve ese problema de la manera más aburrida y más eficaz
posible: pone nombre y número a cada comportamiento observado de un adversario
real. Las dos frases de arriba son `T1566.001 Phishing: Spearphishing
Attachment`. Ahora sí se pueden contar, comparar y cruzar.

**ATT&CK Navigator** es la superficie donde ese vocabulario se vuelve imagen.
No añade inteligencia: añade *visibilidad compartida*. Su valor aparece en tres
momentos muy concretos de la vida profesional:

- cuando tienes que explicarle a alguien que no es técnico **qué hizo** el
  atacante y **por dónde** puede volver;
- cuando quieres saber **qué parte del comportamiento de un adversario tu
  organización no vería pasar**;
- cuando el equipo rojo y el equipo azul necesitan discutir sobre el mismo
  mapa en vez de sobre dos narrativas distintas.

Esta actividad recorre ese camino completo, sobre un caso real, en una sesión.

---

## Resultados de aprendizaje

Al terminar, puedo:

1. **Distinguir** táctica (el *porqué* del adversario), técnica (el *cómo*) y
   sub-técnica (el *cómo* preciso), y ubicar cualquiera de las tres en la matriz.
2. **Traducir** una frase en prosa de un reporte de incidente a un ID de
   técnica ATT&CK, citando la evidencia que sostiene esa decisión.
3. **Justificar** mi nivel de confianza en cada mapeo y representarlo
   visualmente, en vez de fingir certeza donde no la hay.
4. **Contrastar** mi mapeo con el mapeo oficial del mismo incidente y explicar
   mis omisiones y mis excesos.
5. **Explicar** la diferencia entre el comportamiento visto en *un incidente* y
   el perfil acumulado de *un grupo*, y por qué confundirlos lleva a decisiones
   malas.
6. **Enunciar qué NO hace** Navigator, y qué decisión de arquitectura de red
   sí puede tomarse a partir de un mapa ATT&CK.

---

## Anatomía de la matriz (60 segundos de lectura, te ahorran 20 minutos)

La matriz se lee de izquierda a derecha como avanza un ataque:

| | Táctica (columna) | La pregunta que responde |
|---|---|---|
| 1 | `Reconnaissance` | ¿Qué averiguó sobre mí antes de tocarme? |
| 2 | `Resource Development` | ¿Qué se montó para atacarme? |
| 3 | `Initial Access` | ¿Por dónde entró? |
| 4 | `Execution` | ¿Cómo logró ejecutar código? |
| 5 | `Persistence` | ¿Cómo se queda aunque reinicie? |
| 6 | `Privilege Escalation` | ¿Cómo consiguió más permisos? |
| 7 | `Stealth` | ¿Cómo evita que lo vean? |
| 8 | `Defense Impairment` | ¿Cómo degrada mis defensas? |
| 9 | `Credential Access` | ¿Cómo consiguió credenciales? |
| 10 | `Discovery` | ¿Cómo aprende cómo es mi red por dentro? |
| 11 | `Lateral Movement` | ¿Cómo salta de una máquina a otra? |
| 12 | `Collection` | ¿Qué junta antes de llevárselo? |
| 13 | `Command and Control` | ¿Cómo lo maneja desde fuera? |
| 14 | `Exfiltration` | ¿Cómo lo saca? |
| 15 | `Impact` | ¿Qué daño causa al final? |

> **Aviso de versión.** ATT&CK cambia entre versiones. En **v19**, la táctica
> que durante años se llamó `Defense Evasion` ahora se llama **`Stealth`**, y
> se añadió **`Defense Impairment`**. También se renumeran técnicas: «borrar
> los registros de eventos de Windows» era `T1070.001` y hoy es `T1685.005`.
> Por eso en esta actividad **los IDs se comprueban en la herramienta, nunca
> de memoria ni copiándolos de un blog antiguo**.

Dentro de cada columna, cada celda es una **técnica**. Las que tienen un
número tipo `(0/8)` y una barrita lateral esconden **sub-técnicas**: variantes
más precisas del mismo comportamiento.

---

## Paso a paso

Tiempo total ≈ **63 minutos**.

### Paso 0 — Prepara el terreno · 5 min

**Qué haces.** Abrir Navigator y crear tu capa de trabajo.

**Cómo se hace.**

1. Entra a <https://mitre-attack.github.io/attack-navigator/>.
2. Despliega **`Create New Layer`** y elige **`Enterprise ATT&CK`**.
   (`Mobile` es para móviles, `ICS` para sistemas de control industrial. En
   este módulo siempre Enterprise.)
3. Arriba a la derecha verás tres grupos de controles:
   **`Selection Controls`** (qué está seleccionado), **`Layer Controls`** (la
   capa entera) y **`Technique Controls`** (las técnicas seleccionadas). Este
   último **sólo se activa cuando hay al menos una técnica seleccionada** — si
   los iconos están grises, es que no has seleccionado nada.
4. En **`Layer Controls`** abre **`Layer Information`** y ponle nombre a la
   capa ya, antes de empezar: `A1-<tuapellido>-<caso>`.

**Por qué importa.** Una capa sin nombre ni descripción es basura en seis
meses: nadie sabrá de qué incidente salió ni quién la hizo. Ponerle nombre
ahora es higiene profesional, no burocracia.

### Paso 1 — Elige tu caso · 3 min

**Qué haces.** Escoger un reporte público de la lista curada.

**Cómo se hace.** Abre [`casos-sugeridos.md`](casos-sugeridos.md) y elige uno
de los cuatro. Están escogidos porque **traen su propia tabla ATT&CK oficial**
al final — la vas a necesitar en el Paso 6, y es lo que hace que puedas
corregirte a ti mismo sin esperar al docente.

> **Volt Typhoon no está en la lista**: es el caso que vimos juntos en la demo
> de la Sesión 1. Elige otro.

**Por qué importa.** Un reporte de marketing («los atacantes usaron técnicas
avanzadas») no se puede mapear. Uno técnico sí. Aprender a distinguirlos es
parte del oficio.

### Paso 2 — Lee y extrae comportamientos · SIN la herramienta · 12 min

**Qué haces.** Leer el reporte y anotar, en papel o en la plantilla, cada
**acción** que ejecutó el adversario. Todavía no abres el buscador de Navigator.

**Cómo se hace.** Usa [`plantilla-mapeo.md`](plantilla-mapeo.md). Por cada
acción, una fila con la **frase literal del reporte** y **la misma acción en
tus palabras**. Deja vacías, por ahora, las columnas de táctica y técnica.

Tres reglas para decidir qué es una acción:

- **Una acción es algo que el adversario *hizo*.** «La víctima era un hospital»
  es contexto, no comportamiento.
- **Una herramienta no es una acción.** «Usaron Mimikatz» no se mapea a
  «Mimikatz»: se mapea al comportamiento, que es *volcar credenciales*. En
  ATT&CK, Mimikatz es *software*, no técnica.
- **Si el reporte no lo dice, no ocurrió.** Ya habrá tiempo de especular; esta
  columna es sólo lo que está escrito.

> **Regla de oro de la actividad: no mires todavía la tabla ATT&CK del
> reporte.** Suele estar al final, en un apéndice. Sáltala. Todo el valor del
> Paso 6 depende de que llegues a él sin haberla visto. Es formativa: hacer
> trampa aquí sólo te roba a ti la retroalimentación.

**Por qué importa.** Si abres el buscador primero, vas a torcer el relato para
que encaje con las técnicas que encuentres. El orden correcto es siempre
**comportamiento primero, identificador después**. Es exactamente lo que hace
un analista de inteligencia de amenazas, y es donde se gana o se pierde la
calidad del mapeo.

### Paso 3 — Traduce cada comportamiento a una técnica · 18 min

**Qué haces.** Convertir cada fila de tu hoja en una técnica ATT&CK anotada en
la capa, con su confianza y su evidencia.

**Cómo se hace.**

1. Abre el buscador: icono de lupa en **`Selection Controls`**
   (**`search & multiselect`**).
2. Escribe una palabra del comportamiento, no de la herramienta: `credential`,
   `remote desktop`, `scheduled task`. En **`Search Settings`** están activas
   las casillas **`Name`**, **`ATT&CK ID`** y **`Description`**: se busca
   también dentro de las descripciones, por eso a veces aparecen resultados
   que no contienen la palabra en el título.
3. **Verifica antes de aceptar.** Clic derecho sobre la técnica →
   **`view technique`**: se abre la página oficial en otra pestaña. Lee el
   primer párrafo. Si no describe lo que dice tu frase, **no es esa técnica**.
4. **¿Técnica o sub-técnica?** Si el reporte dice *cómo* exactamente, baja a la
   sub-técnica (dice «por RDP» → `T1021.001`). Si sólo dice la categoría
   genérica, quédate en la técnica padre. Bajar a una sub-técnica sin evidencia
   es inventar precisión.
5. Selecciona la técnica (clic izquierdo) y ve a **`Technique Controls`**:
   - **`scoring`** → escribe la **confianza**: `3` si el reporte nombra el
     comportamiento explícitamente, `2` si se deduce con seguridad, `1` si es
     una lectura razonable pero no afirmada.
   - **`comment`** → pega la **frase literal del reporte** y, detrás, por qué
     elegiste esa técnica y esa confianza.
6. Repite hasta tener **al menos 8 técnicas en al menos 4 tácticas distintas**.

> **Sin cita, no entra al mapa.** Si no puedes pegar la frase que la sustenta,
> esa técnica sobra. Ésta es la regla que separa un mapeo profesional de un
> ejercicio de colorear.

**Los cuatro errores clásicos**

| Error | Ejemplo | Qué hacer |
|---|---|---|
| Mapear la herramienta | «usó Mimikatz» → buscar «Mimikatz» | Mapea el comportamiento: `T1003 OS Credential Dumping` |
| Confundir táctica con técnica | poner «Persistence» como técnica | `Persistence` es la columna; la técnica es la celda |
| Sobre-mapear | añadir exfiltración porque «seguro que también» | Si el reporte no lo dice, fuera |
| Falsa precisión | elegir una sub-técnica «porque suena» | Sin evidencia del *cómo*, quédate en la técnica padre |

**Por qué importa.** Aquí es donde de verdad se aprende ATT&CK: no leyendo la
matriz, sino teniendo que decidir, con evidencia incompleta, entre dos técnicas
que se parecen. Esa duda es el trabajo real.

### Paso 4 — Haz el mapa legible para otro ser humano · 5 min

**Qué haces.** Convertir tus anotaciones en algo que alguien más pueda leer sin
que tú estés al lado explicándolo.

**Cómo se hace.**

1. **`Layer Controls`** → **`Color Setup`** → sección **`Scoring Gradient`**:
   pon **`low value`** = `1` y **`high value`** = `3`. Ahora el color significa
   confianza y no un adorno.
2. Abre la barra **`legend`** (abajo a la derecha) y con **`Add Item`** añade
   tres entradas: `3 — explícita`, `2 — implícita`, `1 — inferida`.
3. **`Layer Controls`** → **`Layer Information`** → en la descripción escribe
   de dónde salió todo: nombre del reporte, organismo y **URL**.
4. *(Opcional, queda mucho mejor)* Para una imagen limpia sólo con lo que
   mapeaste: clic derecho → **`select unannotated`**, luego
   **`Technique Controls`** → **`toggle state`** para deshabilitarlas, y en
   **`Layer Controls`** el botón de **ocultar técnicas deshabilitadas**.

**Por qué importa.** Un mapa sin leyenda es un cuadro abstracto: bonito e
inútil. La leyenda y la procedencia son lo que convierte tu capa en evidencia
presentable en una auditoría o en un comité.

### Paso 5 — Exporta · 3 min

**Qué haces.** Sacar los dos artefactos: el dato y la imagen.

**Cómo se hace.** En la barra de herramientas, botón **`save layer`** →
**`download single layer as json`**. Después, botón de cámara
(**`render layer to SVG`**) → **`download svg`**.

**Por qué importa.** No son lo mismo y en la vida profesional necesitas ambos:

- el **JSON** es *dato*: se vuelve a abrir, se compara con otra capa, se
  versiona, se procesa con un script;
- el **SVG** es *comunicación*: va en la diapositiva del comité que no va a
  abrir Navigator jamás.

### Paso 6 — La confrontación · 10 min

**Qué haces.** Ahora sí: abrir el mapeo oficial y ver cuánto te pareces.

**Cómo se hace.**

1. Ve al apéndice ATT&CK del reporte que elegiste y compara contra tu tabla.
   Clasifica cada técnica en tres montones:
   - **Aciertos** — la tienes tú y la tiene el reporte.
   - **Omisiones** — la tiene el reporte y tú no la viste.
   - **Extras** — la tienes tú y el reporte no.
2. Abre además el perfil acumulado del grupo. Sin descargar nada: pestaña
   nueva → **`Open Existing Layer`** → campo **`Load from URL`** → pega la URL
   de capa que aparece en [`casos-sugeridos.md`](casos-sugeridos.md) para tu
   caso, y pulsa la flecha.
3. Anota en el entregable, en 3–4 líneas, **cuál de los tres montones fue el
   más grande y qué te dice eso de cómo leíste el reporte**.

**Dos lecturas que tienes que hacer, y no son la misma**

- **Extras no significa error.** Puede que tu lectura sea defendible y la del
  reporte sea conservadora. Lo que decide no es coincidir con la respuesta: es
  si tu evidencia sostiene tu decisión. Dos analistas competentes mapean el
  mismo texto de forma distinta, y eso es normal.
- **Tu capa tiene 8–12 técnicas; la del grupo tiene decenas.** Eso tampoco es
  un error tuyo. Tú mapeaste **un incidente**; MITRE acumula **todo lo que
  alguien ha reportado nunca** sobre ese grupo. Confundir las dos cosas lleva a
  decisiones caras: no defiendes contra «todo lo que APT-X sabe hacer», sino
  contra lo que hace en escenarios como el tuyo.

**Por qué importa.** Es la única retroalimentación autoritativa que puedes
darte a ti mismo sin el docente delante. Aprovéchala.

### Paso 7 — El puente al entregable final del módulo · 7 min

**Qué haces.** Descubrir cuáles de las técnicas que mapeaste dependen de una
decisión de **arquitectura de red** — es decir, del entregable final de este
módulo.

**Cómo se hace.**

1. Con tu capa abierta, abre otra vez **`search & multiselect`**.
2. Escribe `segmentation` en el buscador y despliega la sección
   **`Mitigations`**. Aparecen tres que son, literalmente, el temario de las
   Sesiones 3, 4 y 5:

   | Mitigación | Se construye en |
   |---|---|
   | `M1030 Network Segmentation` | Sesión 3 — topologías, DMZ, zonas |
   | `M1037 Filter Network Traffic` | Sesión 4 — reglas de firewall, pfSense |
   | `M1035 Limit Access to Resource Over Network` | Sesión 4 — menor privilegio |

   Busca también `intrusion` para encontrar
   `M1031 Network Intrusion Prevention` → Sesión 5 (IDS/IPS).
3. Pulsa **`select`** en una de ellas: Navigator marca **todas las técnicas que
   esa mitigación contiene**. Fíjate en cuáles de *las tuyas* quedan marcadas.
4. Apunta las **tres técnicas de tu mapa** sobre las que una decisión de
   arquitectura de red tendría efecto real, y qué decisión sería.

**Por qué importa.** El entregable final del módulo es una topología de red
segura que mitigue *riesgos identificados*. Estas tres líneas **son** tus
riesgos identificados: acabas de justificar con evidencia, en la Sesión 1, por
qué el resto del módulo existe. Guárdalas: se usan otra vez en la Sesión 3.

---

## Reto opcional — ¿qué de esto vería pasar mi organización?

Para quien termine antes. No es requisito y no se califica.

1. Crea una **segunda capa** con **exactamente las mismas técnicas** que la
   tuya, pero puntuando ahora tu **cobertura defensiva**: `0` no me enteraría ·
   `1` quedaría en un log que nadie mira · `2` lo detectaría · `3` lo
   impediría. (Si no tienes una organización de referencia, usa el laboratorio
   del módulo.)
2. Pestaña nueva → **`Create Layer from Other Layers`**. Elige en **`domain`**
   `Enterprise ATT&CK MITRE ATT&CK v19` — tiene que ser el mismo dominio y
   versión de tus dos capas. En **`score expression`** escribe `a - b`, donde
   `a` y `b` son las letras amarillas que Navigator muestra sobre cada pestaña.
   Pulsa **`Create layer`**.
3. Léelo así: **valor alto = el adversario sabe hacerlo y tú no lo verías**.
   Ésa es tu lista de prioridades, y es exactamente lo que en la industria se
   llama *análisis de brechas de cobertura*.

> **Comprobado en la herramienta:** una técnica que esté **sin puntuar**
> (*unscored*) en cualquiera de las dos capas sale **sin puntuar** del
> resultado — Navigator **no** la trata como `0`. Por eso el paso 1 insiste en
> puntuar *las mismas* técnicas en ambas capas. Ojo también: en Navigator
> «sin puntuar» y «puntuado 0» son estados distintos.

---

## Entregables

Dos archivos, en la tarea «A.1 — ATT&CK Navigator» de **Brightspace**,
**antes del domingo 27 de septiembre a las 23:59**.

**1. `A1-<apellido>-capa.json`** — tu capa exportada en el Paso 5.

**2. `A1-<apellido>.md`** (o PDF) — usa
[`plantilla-entrega.md`](plantilla-entrega.md), que ya trae los apartados:

| Apartado | Extensión |
|---|---|
| Caso elegido y enlace al reporte | 1 línea |
| Tabla de mapeo con la evidencia citada | 8–12 filas |
| Confrontación: aciertos / omisiones / extras + qué te dice | 3–4 líneas |
| La técnica que te parece **más difícil de mitigar** y por qué | 4–5 líneas |
| **Pitch de 90 segundos** a un director no técnico | 5–6 líneas |
| Las **3 técnicas** que una decisión de arquitectura de red podría afectar | 3 líneas |

*Opcional:* el SVG del Paso 5.

**Sobre el pitch.** Escribe lo que dirías en voz alta, con tu capa proyectada,
a alguien que no sabe qué es una táctica: qué pasó, qué significa para la
organización y qué decisión le estás pidiendo. Sin jerga. Es el ejercicio más
difícil de los seis y el que más se parece a tu trabajo real.

---

## Autoevaluación (antes de entregar)

- [ ] ¿**Todas** mis técnicas tienen una frase del reporte en el comentario?
- [ ] ¿Cubro al menos **4 tácticas** distintas y al menos **8 técnicas**?
- [ ] ¿Verifiqué cada ID en `view technique` en vez de confiar en el buscador?
- [ ] ¿Mi capa tiene **nombre, descripción con la URL de la fuente y leyenda**?
- [ ] ¿Un colega podría abrir mi JSON y reconstruir mi razonamiento **sin
      hablar conmigo**? Si no, falta evidencia en los comentarios.
- [ ] ¿Puedo decir en una frase qué **no** hace esta herramienta?

## Preguntas guía del syllabus

La 2 y la 3 ya quedan respondidas por tus entregables: el **pitch** responde la
2, y la **autoevaluación** más la introducción responden la 3. No las repitas.
Contesta sólo ésta, en 2–3 líneas dentro del entregable:

> **¿Qué comunica esta herramienta que un documento de texto no comunica con la
> misma claridad?**

## Solución de problemas

| Síntoma | Causa y salida |
|---|---|
| Perdí la capa al cerrar la pestaña | No hay recuperación: no se guarda en ningún servidor. Exporta el JSON cada pocos minutos. |
| Los iconos de `Technique Controls` están grises | No hay ninguna técnica seleccionada. Haz clic sobre una celda primero. |
| Puse un score y la celda no cambió de color | El rango del gradiente no cubre tu valor. `Color Setup` → `Scoring Gradient` → `low value` 1, `high value` 3. |
| Encuentro un ID en un blog y Navigator no lo tiene | El blog usa una versión vieja de ATT&CK. Busca el comportamiento por su nombre; el ID pudo haberse renumerado (`T1070.001` → `T1685.005`). |
| `Load from URL` no carga la capa del grupo | Revisa que no falte ningún carácter de la URL y que sea una capa `enterprise`. |
| Quiero recuperar una capa vieja de otra versión de ATT&CK | Navigator trae un asistente de actualización de capas; avisa que **no se puede volver a abrir** una vez cerrado. |

## Uso ético

Esta actividad trabaja exclusivamente sobre **reportes públicos ya publicados**
por organismos oficiales. No se escanea, no se consulta y no se toca ninguna
infraestructura de las organizaciones mencionadas. Mapear el comportamiento de
un atacante sirve para **defender**; ése y no otro es el marco de uso de todo
lo que se ve en esta certificación (syllabus §10).

---

## Archivos de esta actividad

| Archivo | Para qué |
|---|---|
| [`casos-sugeridos.md`](casos-sugeridos.md) | Los cuatro casos y cómo proponer uno propio |
| [`plantilla-mapeo.md`](plantilla-mapeo.md) | Hoja de trabajo de los Pasos 2 y 3 |
| [`plantilla-entrega.md`](plantilla-entrega.md) | Estructura del entregable |
| [`ejemplo/ejemplo-formato.json`](ejemplo/ejemplo-formato.json) | Extracto de la capa de la demo, para ver la forma de un archivo válido |
