<!-- Actividad de la Sesión 2 (Módulo II). Formato: modulo1/instructor/PLANTILLA-ACTIVIDAD.md.
     Empresa FICTICIA: Kaab Orgánicos. Nada aquí revela una calificación ni una respuesta esperada. -->

# S2 · Evaluar riesgos y registrarlos

**Objetivo:** recorrer de punta a punta la cadena de SP 800-30 Rev. 1 (fuente, evento, vulnerabilidad, probabilidad, impacto y riesgo) para un riesgo real del laboratorio, y explicar por qué esa cadena importa más que la lista de problemas.

## Antes de empezar

- **En clase:** unos 50 minutos el sábado 17 de octubre, dentro de la sesión.
- **Fuera de clase:** unas 4 horas, repartidas antes de la sesión del viernes 23 de octubre.
- **Requisitos:** la tolerancia de Kaab de la [S1](../S1-marco/README.md) ya guardada, y el laboratorio levantado. Necesitas una terminal para la evidencia del paso 3.
- **Lo que NO hace falta:** conocer el catálogo SP 800-53 ni instrumentos de explotación. La evidencia que pides es de configuración y de estado, no de ataque.
- **Advertencia que arruina el trabajo:** una tarjeta que dice «la contraseña es débil» sin evidencia no se puede verificar. Una vulnerabilidad que no pudiste comprobar se anota como **hipótesis**, nunca como hallazgo.

**Herramienta:** la app *Evaluación de riesgos · Kaab*, páginas **2 · Preparar**, **3 · Activos**, **4 · Conducir la evaluación**, **5 · Registro de riesgos** y **6 · Comunicar y mantener**. Para la evidencia: `vagrant ssh grc-console`, `curl` y `nmap`.

**URL:** `http://192.168.61.10:8501`

---

## Introducción

En la S1 definiste qué riesgo tolera Kaab. Hoy construyes la evaluación. El método es el de SP 800-30 Rev. 1, Paso 2: cada riesgo es una **cadena** de decisiones. Si la fuente está mal identificada, todo lo que sigue es ruido. Si la probabilidad no sale de una evidencia, el nivel de riesgo es una opinión.

La app hace la cadena explícita. Tú decides cada eslabón; la app calcula la probabilidad global con la tabla G-5 y el riesgo con la tabla I-2, y te avisa cuando algo no cuadra.

## Resultados de aprendizaje

Al terminar podré:

- Construir una tarjeta de riesgo completa, desde la fuente hasta el nivel de riesgo, con evidencia de cada eslabón.
- Distinguir una **vulnerabilidad verificada** de una **hipótesis** y explicar cómo cambia la evaluación.
- Calcular el **impacto en contexto** de un escenario con las cifras de Kaab y justificar el nivel que elijo.
- Priorizar un registro de riesgos, de modo que no todo quede en «Alto».

## Paso a paso

### Paso 1 · Preparar la evaluación (10 min)

**Qué haces:** en **2 · Preparar**, escribe el propósito, el alcance, los supuestos y las fuentes. Elige la **tolerancia máxima** y revisa los **umbrales de impacto** en pesos.

**Cómo se hace:** el propósito responde a una decisión concreta (en qué gastar el presupuesto de seguridad de este año). Cada cifra que no tengas (por ejemplo, el costo por registro expuesto) va a supuestos, con su justificación. Los umbrales los fijas tú: son la calibración de la escala de impacto para Kaab.

**Por qué importa:** la Unidad 3 dice que un resultado sin supuestos registrados no puede repetirse ni cuestionarse. Esta página es esa constancia.

### Paso 2 · Activos críticos (5 min)

**Qué haces:** en **3 · Activos**, la tabla trae los procesos que menciona la dirección. Completa dueño, zona, criticidad y por qué importa. Agrega los sistemas que falten, por ejemplo *Tienda en línea y panel de administración*: la tarjeta del paso 4 necesita ese activo.

**Cómo se hace:** un sistema es un activo si su falla altera un proceso. Marca *Datos personales* cuando la fila guarde datos de clientes.

**Por qué importa:** cada tarjeta debe apuntar a un activo. Si el riesgo no se puede ligar a un activo, probablemente no es un riesgo de esta empresa.

### Paso 3 · Evidencia del laboratorio (10 min)

**Qué haces:** reúne evidencia de tres fuentes. Anota en una hoja qué muestra cada una.

| Fuente | Cómo | Qué buscas |
|---|---|---|
| Cabeceras de la tienda | `curl -sI http://192.168.61.20/` | Servidor y versión que se anuncian |
| Estado público | `curl -s http://192.168.61.20/status` | Qué información expone sin autenticación |
| Puertos de la tienda | `nmap -sV 192.168.61.20` (desde `vagrant ssh grc-console`) | Servicios abiertos |
| Aviso de privacidad | `curl -s http://192.168.61.10/aviso-privacidad.html` | Qué datos dice tratar y por cuánto tiempo |

**Cómo se hace:** cada comando se ejecuta desde `grc-console`. Si tienes `nmap` en tu máquina anfitriona, también puedes usarlo contra `192.168.61.20`. No escanees ningún otro rango.

**Por qué importa:** una evaluación se sostiene en lo que puedes mostrar. La evidencia de este paso es lo que irá en el campo *Evidencia* de tus tarjetas.

### Paso 4 · Tu primera tarjeta (12 min)

**Qué haces:** lee primero **Ejemplo resuelto** en **4 · Conducir la evaluación**. Después construye una tarjeta sobre el **panel de administración de la tienda**:

1. **Activo:** *Tienda en línea y panel de administración*.
2. **2-1 Fuente:** ¿quién podría entrar al panel sin autorización? Tipo, capacidad, intención y orientación hacia Kaab.
3. **2-2 Evento:** qué hace esa fuente, con un verbo. Escríbelo como acción concreta.
4. **2-3 Vulnerabilidad:** qué condición del panel lo permite. En *Evidencia*, pon el comando o la prueba del paso 3 con su resultado.
5. **2-4 Probabilidad:** elige la probabilidad de iniciación (G-2) y la de impacto adverso (G-4). La app calcula la probabilidad global (G-5).

**Prueba de control:** abre el panel de administración desde el enlace de la tienda. Registra si pide autenticación y si la cuenta sigue con la configuración inicial del fabricante. Haz esta prueba únicamente en este laboratorio, con credenciales habituales del producto, y anota el resultado tal cual.

**Cómo se hace:** no avances al paso siguiente si la evidencia no está escrita. La app te avisará si falta.

**Por qué importa:** la probabilidad sale de la fuente y de la vulnerabilidad, no de tu intuición sobre lo que «suena grave».

### Paso 5 · Impacto y registro (8 min)

**Qué haces:** en la misma tarjeta, completa el impacto (2-5) y el registro.

**Cómo se hace:**

- Elige el **escenario** más parecido a lo que pasaría. Para una caída de la base de pedidos, usa *Se cae la base de pedidos: tienda y distribuidores se detienen (días)*. Pon la **cantidad** (horas o días) y marca **temporada alta** si el escenario cae en noviembre o diciembre.
- La calculadora muestra cada componente en pesos. Elige el **nivel de impacto** (H-3) y escribe por qué ese nivel y no otro.
- En el registro, pon un **dueño** que pueda financiar o aprobar la respuesta, la **respuesta** (aceptar, evitar, mitigar, compartir o transferir) y una **contramedida** que describa la acción.

**Por qué importa:** el impacto en pesos depende de la temporada y de la duración. El mismo incidente cuesta distinto en diciembre que en marzo. Eso es lo que la Unidad 3 llama impacto en contexto.

### Paso 6 · Revisión y guardado (5 min)

**Qué haces:** lee las observaciones bajo el formulario. Corrige los errores (rojo) y decide si tomas en cuenta las advertencias (amarillo). Pulsa **Guardar tarjeta**.

**Por qué importa:** la retroalimentación de la app no califica. Te señala qué no cuadra para que lo justifiques o lo corrijas.

---

## Fuera de clase (unas 4 horas)

### Paso 7 · Al menos cinco tarjetas más (2 h 15 min)

Cada tarjeta debe tener **evidencia propia**. Puedes usar estos eventos como punto de partida, sin que sean los únicos:

- La base de pedidos deja de responder durante varios días (usa *Se cae la base de pedidos*).
- Se filtran datos de clientes de la UE (usa *Se filtran datos de clientes*).
- Un respaldo no se puede restaurar (revisa la carpeta de respaldos que describe el Expediente, sin copiar datos personales).
- Se pierde la trazabilidad de un lote y la liberación se detiene.
- Un error de precio o de promoción se publica en temporada alta.

**Regla:** si una fuente adversaria no se dirige a Kaab, la app te avisa. Justifícala o descártala.

### Paso 8 · Priorizar el registro (45 min)

**Qué haces:** abre **5 · Registro de riesgos**. Revisa la distribución por nivel y las observaciones.

**Cómo se hace:** si casi todo está en «Alto» o «Muy alto», no has priorizado. Compara los riesgos entre sí: ¿cuál de ellos afecta más ingresos, datos o contratos? Ajusta la justificación de impacto, o cambia la probabilidad si la evidencia no la sostiene.

**Por qué importa:** la Unidad 4 pide que los riesgos se puedan comparar. Un registro que todo lo marca urgente no le sirve a la dirección.

### Paso 9 · Resumen y exportación (45 min)

**Qué haces:** en **6 · Comunicar y mantener**, escribe el resumen ejecutivo (máximo 300 palabras). Después, en **5 · Registro de riesgos**, descarga el Excel.

**Cómo se hace:** el resumen tiene tres partes: los riesgos más altos, la decisión que necesitas de la dirección y los supuestos que podrían cambiarla.

**Por qué importa:** la Matriz final parte de este Excel. Lo que no escribas aquí no llegará a la dirección.

---

## Entregables

| Entregable | Dónde | Formato | Fecha |
|---|---|---|---|
| Preparación de la evaluación | **2 · Preparar** | Propósito, alcance, supuestos, fuentes, tolerancia máxima y umbrales, guardados | En clase, sábado 17 |
| Activos críticos | **3 · Activos** | Tabla con al menos 6 filas | En clase, sábado 17 |
| Registro de riesgos | **5 · Registro de riesgos** | Al menos **6 tarjetas**, cada una con evidencia | Viernes 23 de octubre, antes de la sesión |
| Resumen ejecutivo | **6 · Comunicar y mantener** | 300 palabras o menos, guardado | Viernes 23 de octubre, antes de la sesión |
| Registro exportado | **5 · Registro de riesgos** | Archivo `kaab-registro-de-riesgos.xlsx` | Viernes 23 de octubre, antes de la sesión |

## Preguntas guía

1. ¿Qué eslabón de tu tarjeta más te costó justificar? ¿Qué evidencia te faltó?
2. ¿Cambió alguno de tus niveles de riesgo al cambiar la temporada en el escenario? ¿Por qué?
3. Si la dirección solo puede financiar dos respuestas este trimestre, ¿cuáles elegirías y con qué criterio?

## Autoevaluación

Antes de entregar, revisa cada tarjeta:

- ¿La fuente y el evento son cosas distintas?
- ¿La evidencia es algo que alguien más podría repetir?
- ¿El nivel de impacto lo justifica un monto o un contrato, no solo tu intuición?
- ¿El riesgo lo calculó la app a partir de tus niveles, sin que lo escribieras a mano?

## Solución de problemas

- **Guardar tarjeta está desactivado.** Mira la línea «Pendiente de completar»: indica qué campo falta.
- **El monto del impacto no cambia.** Cambia la cantidad o el escenario; la calculadora se actualiza en vivo. El nivel no se elige solo: escoge tú el que justifiques.
- **No puedo ver la tienda ni el panel.** Revisa `vagrant status`. El servicio de la tienda tarda unos segundos en arrancar después de `vagrant up`.
- **El Monitor dice que la tienda no responde.** Es un dato, no un error de la app: el servicio de la tienda o la base de pedidos están caídos. Espera un minuto y revisa otra vez. Si persiste, anótalo como evidencia de disponibilidad.

## Uso ético

Las pruebas de esta actividad son únicamente sobre las máquinas de este laboratorio, que son ficticias y están aisladas. No escanees ni pruebes ningún otro rango de red ni ningún sistema real. Si un ejercicio te lleva fuera del laboratorio, detente y pregunta.
