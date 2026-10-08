# A.1 — Hoja de trabajo de mapeo

Se llena en dos tiempos. **Primero las columnas A, B y C leyendo el reporte, sin
abrir Navigator** (Paso 2). Después, con la herramienta, las columnas D, E y F
(Paso 3).

Duplica este archivo o cópialo a donde prefieras trabajar.

---

**Caso elegido:**
**Reporte y URL:**
**Fecha del mapeo:**

## Tiempo 1 — Sólo lectura (Paso 2)

Una fila por **acción que ejecutó el adversario**. Si el reporte no lo dice, no
va. Si es una herramienta («usaron Mimikatz»), escribe el *comportamiento*
(«volcaron credenciales del sistema»).

| # | A · Frase literal del reporte | B · La acción en mis palabras | C · ¿Qué buscaba conseguir con eso? |
|---|---|---|---|
| 1 | | | |
| 2 | | | |
| 3 | | | |
| 4 | | | |
| 5 | | | |
| 6 | | | |
| 7 | | | |
| 8 | | | |
| 9 | | | |
| 10 | | | |

> La columna C es tu primera intuición de **táctica**: «quería entrar» →
> `Initial Access`; «quería seguir dentro mañana» → `Persistence`; «quería saber
> cómo es la red» → `Discovery`.

## Tiempo 2 — Con la herramienta (Paso 3)

| # | D · Táctica | E · Técnica y ID | F · Confianza (1-3) | G · Por qué ésa y no otra |
|---|---|---|---|---|
| 1 | | | | |
| 2 | | | | |
| 3 | | | | |
| 4 | | | | |
| 5 | | | | |
| 6 | | | | |
| 7 | | | | |
| 8 | | | | |
| 9 | | | | |
| 10 | | | | |

**Escala de confianza**

| | Cuándo |
|---|---|
| **3 — explícita** | El reporte nombra el comportamiento. No hay interpretación. |
| **2 — implícita** | No lo dice con esas palabras, pero se deduce con seguridad del texto. Ojo con «often», «typically», «may have»: describen el patrón habitual del grupo, no lo observado en este incidente. |
| **1 — inferida** | Lectura razonable, pero el reporte no la afirma. Úsala poco y anota la duda. |

**Antes de pasar al Paso 4, comprueba:**

- [ ] Al menos **8 técnicas**.
- [ ] Al menos **4 tácticas** distintas en la columna D.
- [ ] **Toda** fila con algo escrito en A (sin cita no entra al mapa).
- [ ] Ningún nombre de herramienta en la columna E.
- [ ] Cada ID verificado con `view technique`, no copiado de un blog.

## Lo que descarté, y por qué

Vale oro en la revisión de la Sesión 2: casi siempre se aprende más de lo que
se descartó que de lo que se mapeó.

| Frase del reporte | Por qué NO la mapeé |
|---|---|
| | |
| | |
