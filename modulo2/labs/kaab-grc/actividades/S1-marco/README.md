<!-- Actividad de la Sesión 1 (Módulo II). Formato: modulo1/instructor/PLANTILLA-ACTIVIDAD.md.
     Empresa FICTICIA: Kaab Orgánicos. Nada aquí revela una respuesta de la evaluación. -->

# S1 · Conocer la empresa y fijar el marco de riesgo

**Objetivo:** explicar, con tus palabras, qué significa que una empresa acepte un riesgo y cómo se distingue una fuente de amenaza de un evento.

## Antes de empezar

- **En clase:** unos 40 minutos dentro de la sesión del viernes 16 de octubre.
- **Fuera de clase:** unos 60 minutos para escribir la tolerancia de Kaab. Entrega antes de la sesión del sábado 17 de octubre.
- **Requisitos:** el laboratorio levantado (`vagrant up` en `modulo2/labs/kaab-grc/`) y tu navegador. Si no puedes levantarlo, usa la [ejecución local](../../README.md#ejecutar-la-app-sin-máquinas-virtuales).
- **Lo que NO hace falta:** instalar nada más, ni conocer normas. Las unidades 1 y 2 te dan el vocabulario.
- **Advertencia que arruina el trabajo:** una tolerancia de dos líneas («moderada») no sirve para nada. Si no dice qué se acepta, cuánto y quién lo aprueba, no puede usarse para juzgar después una sola respuesta de tu registro.

**Herramienta:** la app *Evaluación de riesgos · Kaab* (Streamlit), páginas **Inicio**, **Expediente**, **1 · Marco**.

**URL:** `http://192.168.61.10:8501`

---

## Introducción

Kaab Orgánicos vende miel orgánica por internet, a distribuidores en Europa y a cadenas de supermercados en México. Hace un año, la tienda se cayó durante el fin de semana de ventas más fuertes del año, y nadie supo cuánto costó ni qué hacer después.

La dirección te contrató para responder una pregunta: **¿dónde pongo el primer peso de seguridad?** Antes de mirar un solo sistema, necesitas saber qué entiende la empresa por riesgo y cuánto está dispuesta a tolerar. Sin eso, cualquier evaluación es una lista de preocupaciones.

## Resultados de aprendizaje

Al terminar podré:

- Explicar qué es la tolerancia al riesgo y por qué una organización la fija antes de evaluar.
- Distinguir una **fuente de amenaza** de un **evento de amenaza** en una frase concreta.
- Decir en qué **tier** (organización, proceso o sistema) se toma cada decisión.
- Comparar las cinco respuestas posibles al riesgo: aceptar, evitar, mitigar, compartir y transferir.

## Paso a paso

### Paso 1 · Diagnóstico inicial (8 min)

**Qué haces:** en **Inicio**, elige el momento *Inicio del módulo* y responde las ocho preguntas. Luego pulsa **Guardar diagnóstico**.

**Cómo se hace:** cada pregunta es de opción única. No califican: sirven para comparar contigo mismo al cierre del módulo.

**Por qué importa:** sabrás qué tanto cambió tu comprensión. Si respondes al azar, la comparación no sirve.

### Paso 2 · Leer el expediente (12 min)

**Qué haces:** abre **Expediente de la empresa** y lee todo, incluido «Lo que la dirección no sabe».

**Cómo se hace:** anota en una hoja tres datos que te parezcan críticos (por ejemplo, un proceso, un dato personal o una dependencia).

**Por qué importa:** cada riesgo que evalúes sale de algo que la empresa hace o tiene. Si no conoces el negocio, la evaluación describirá tecnología sin saber qué protege.

### Paso 3 · Clasificar cuatro frases (8 min)

**Qué haces:** abre **1 · Marco**, sección *Ejercicio: clasifica cada frase*. Asigna una categoría a cada frase y pulsa **Revisar**.

**Cómo se hace:** antes de elegir, pregúntate: ¿alguien o algo actúa (fuente)? ¿Qué hace (evento)? ¿Qué debilidad permite el daño (vulnerabilidad)? ¿Qué circunstancia lo empeora sin ser una debilidad (condición predisponente)?

**Por qué importa:** confundir fuente con evento es el error más común en las primeras evaluaciones (Unidad 2). Si lo corriges aquí, no lo arrastrarás a tus tarjetas.

### Paso 4 · Vocabulario y niveles de respuesta (7 min)

**Qué haces:** revisa las secciones *Vocabulario de SP 800-30 Rev. 1*, *Niveles de respuesta al riesgo* y *Tres tiers de decisión*.

**Cómo se hace:** para cada nivel de respuesta, escribe una situación real de una tienda en línea donde la elegirías.

**Por qué importa:** la respuesta «mitigar» solo tiene sentido cuando sabes qué riesgo tolera la organización. Sin ese criterio, todas las respuestas parecen buenas.

### Paso 5 · Cierre (5 min)

**Qué haces:** con tu compañero o el grupo, comparen una frase que clasificaron distinto. Decidan cuál categoría es la correcta y por qué.

**Por qué importa:** explicar una decisión en voz alta revela qué no entiendes todavía.

### Paso 6 · Tu tolerancia de Kaab (fuera de clase, 60 min)

**Qué haces:** en **1 · Marco**, sección *Tu tarea: la tolerancia de Kaab*, escribe **150 palabras o más** respondiendo:

1. ¿Qué pérdidas puede absorber Kaab sin pedir aprobación especial?
2. ¿Qué pérdidas no puede absorber de ninguna manera?
3. ¿Qué cambia en temporada alta (noviembre y diciembre)?
4. ¿Qué pasa con los distribuidores de la UE si el retraso se alarga?
5. ¿Quién firma cuando el riesgo supera esa tolerancia?

Pulsa **Guardar tolerancia**.

**Cómo se hace:** usa el expediente como fuente. Si una cifra no aparece, dilo como supuesto: escribe «supongo que…».

**Por qué importa:** esta tolerancia es el criterio con el que la app te avisará, más adelante, si aceptas un riesgo que la empresa no aceptaría.

---

## Entregables

| Entregable | Dónde | Formato | Fecha |
|---|---|---|---|
| Diagnóstico inicial completo | **Inicio** | 8 respuestas guardadas | En clase, viernes 16 |
| Clasificación de las cuatro frases | **1 · Marco** | Revisión guardada | En clase |
| Tolerancia de Kaab | **1 · Marco** | 150 palabras o más, guardadas | Antes de la sesión del sábado 17 |

El instructor revisa el progreso exportando el JSON desde **Inicio**, sección *Tu progreso*.

## Autoevaluación

Antes de entregar, responde sin mirar la app:

1. ¿Cuál es la diferencia entre una fuente y un evento? Dame un ejemplo de cada uno para Kaab.
2. ¿Qué significa «aceptar» un riesgo, y qué tendría que pasar para que la dirección lo acepte?
3. ¿En qué tier decide si la tienda puede vender en temporada alta sin revisión de seguridad?

## Solución de problemas

- **La app no carga.** Revisa que la máquina esté levantada (`vagrant status`). Si la máquina está arriba, reaplica el servicio con `vagrant provision grc-console`.
- **Perdí lo que escribí.** El progreso vive en la máquina virtual. Si la reiniciaste con `vagrant destroy`, se borró. Descarga el JSON desde **Inicio** antes de destruirla.
- **El diagnóstico no guarda.** Pulsa **Guardar diagnóstico** después de responder las ocho preguntas; si falta alguna, se guarda solo lo que tengas.

## Uso ético

Todo lo de esta actividad ocurre dentro del laboratorio aislado. Kaab Orgánicos es ficticia. No pruebes estos servicios contra ningún sistema real sin autorización por escrito.
