# A.2 — Inventario de hallazgos

**Nombre:**
**Fecha:**

## Banderas

Una fila por bandera. **La columna del comando no es opcional**: es la que
demuestra que sabes cómo llegaste, y la que te sirve a ti dentro de seis meses.

| # | Fase | Técnica | Bandera | Comando exacto con el que salió |
|---|---|---|---|---|
| 1 | DNS | TXT del apex | | |
| 2 | DNS | transferencia de zona | | |
| 3 | DNS | espacio de nombres interno | | |
| 4 | DNS | resolución inversa | | |
| 5 | Pasivo | comentario en el código fuente | | |
| 6 | Pasivo | `robots.txt` | | |
| 7 | Pasivo | ruta prohibida en `robots.txt` | | |
| 8 | Pasivo | `security.txt` | | |
| 9 | Pasivo | cabecera de respuesta | | |
| 10 | Enumeración | JavaScript del sitio | | |
| 11 | Enumeración | copia de seguridad | | |
| 12 | Enumeración | archivo de configuración | | |
| 13 | Enumeración | directorio de control de versiones | | |
| 14 | Enumeración | página no enlazada | | |
| 15 | Enumeración | listado de directorio | | |
| 16 | Enumeración | página de error propia | | |
| 17 | Análisis | metadatos de documento | | |
| 18 | Análisis | exportación de datos | | |
| 19 | Análisis | respaldo del sitio | | |
| 20 | Análisis | vhost sin registro DNS | | |

**Encontradas: ___ / 20**

> No pasa nada por no llegar a 20. Un informe bueno con 14 banderas vale más que
> 20 banderas sin informe.

## Hallazgos sin bandera

Lo que un informe real recogería y que aquí no lleva premio. Es lo que más peso
tiene en la evaluación.

**Infraestructura** — nombres de máquina, servicios internos, proveedores:

**Personas** — cuántas, con qué datos, y las dos convenciones de nombres:

| | Patrón observado | Ejemplo |
|---|---|---|
| Correo | | |
| Usuario del sistema | | |

**Credenciales o secretos encontrados** (documentar, **nunca** probar):

**Datos de la organización** — societarios, financieros, proveedores:

## Reflexión de la Fase 1

*Si la transferencia de zona estuviera bien configurada, ¿cuáles de las banderas
1–4 habrías conseguido igual y cuáles no?*

>
