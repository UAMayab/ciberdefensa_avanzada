<!-- Entregable final — Módulo I. Guía de herramientas para el estudiante.
     Versionado en git. No contiene respuestas: enseña a usar OpenVAS y
     Metasploit, no qué encontrar en Delphos. -->

# Guía mínima — OpenVAS/GVM y Metasploit

No necesitas dominar estas herramientas. Necesitas usarlas con criterio. Cada
sección responde **una** pregunta; si no sabes cuál, no sabes por qué estás
ejecutando el comando.

> Las etiquetas de menú y los nombres de paquete cambian entre versiones de
> Kali. Si algo no se llama exactamente así en tu máquina, busca el equivalente
> — el flujo es el mismo.

---

## OpenVAS / GVM — «¿qué de esto *parece* vulnerable?»

OpenVAS (hoy empaquetado como **GVM**, *Greenbone Vulnerability Management*) es
un **escáner de vulnerabilidades**: compara lo que encuentra en el objetivo con
una base de miles de pruebas conocidas y te devuelve una lista priorizada. No
explota nada; levanta hipótesis.

### Puesta en marcha (sólo la primera vez)

```bash
sudo gvm-setup        # instala y sincroniza los feeds — TARDA (20–40 min)
sudo gvm-check-setup  # confirma que todo quedó bien
sudo gvm-start        # arranca los servicios y la interfaz web
```

La primera sincronización de *feeds* baja miles de pruebas; hazla con
antelación, no la noche de la entrega. `gvm-setup` te muestra **una vez** la
contraseña del usuario `admin`: apúntala.

### Escanear el objetivo

La interfaz (Greenbone Security Assistant) abre en `https://127.0.0.1:9392`.

1. **Configuration → Targets → New Target.** Nombre: `Delphos`. Hosts:
   `192.168.59.10`. Deja el resto por defecto.
2. **Scans → Tasks → New Task.** Nombre: `Pentest Delphos`. Scan Target:
   `Delphos`. Scan Config: **Full and fast**. Guarda.
3. Pulsa **Start** (el triángulo). El progreso va de 0 a 100%; un escaneo
   completo tarda. Puedes trabajar en otra fase mientras corre.
4. Al terminar: **Reports**, abre el informe, y **Export** como PDF.

### Cómo leerlo (esto es lo que se califica)

- Ordena por **Severity**. Pero no te creas el número a ciegas: un «Medium» que
  expone datos de clientes puede importar más que un «High» teórico.
- Por cada entrada pregúntate: ¿mi enumeración manual confirma esto, o es un
  falso positivo? ¿La herramienta tiene contexto del *negocio*? (No lo tiene;
  ese contexto lo pones tú.)
- Anota los casos donde OpenVAS vio algo que tú no habías visto a mano, y al
  revés. Ese contraste es una de las preguntas guía.

---

## Metasploit — «¿esto *es* explotable, y qué se obtiene?»

Metasploit confirma, con pruebas, lo que OpenVAS sólo sugería. Un *módulo* es un
exploit o una comprobación ya escrita; tú lo configuras y lo lanzas.

### Flujo general

```bash
msfconsole -q                       # abre la consola

search <producto o servicio>        # localiza módulos relevantes
use <ruta/del/módulo>               # selecciona uno
info                                # qué hace y qué necesita
show options                        # qué tienes que configurar

set RHOSTS 192.168.59.10            # el objetivo
set RPORT <puerto>                  # el puerto de ESE servicio
set LHOST <IP_de_tu_Kali>           # tu IP, para que la shell "vuelva" a ti
# ...credenciales u otras opciones según pida 'show options'

check                               # (si el módulo lo soporta) ¿es vulnerable?
run                                 # lánzalo
```

Si obtienes una sesión:

```bash
sessions -l                         # lista de sesiones abiertas
sessions -i <n>                     # entrar a la sesión n
# dentro: id ; hostname ; whoami  -> demuestra el acceso, no destruyas nada
background                          # salir de la sesión sin cerrarla
```

### Módulos auxiliares (no todo es una shell)

No toda explotación da shell. Para servicios como bases de datos, un módulo
`auxiliary/scanner/...` que **valide credenciales** o **lea datos** es una
explotación legítima y, a menudo, el hallazgo de mayor impacto. El flujo
(`use` / `show options` / `set` / `run`) es idéntico.

### Las reglas que no se negocian

- **`LHOST` es tu IP de Kali** en la red host-only (compruébala con `ip a`), no
  `127.0.0.1`.
- **Demuestra, no destruyas.** `id`/`hostname`/leer un registro basta como
  prueba. No borres, no cambies contraseñas, no tumbes el servicio.
- **Documenta también los fallos.** Un módulo que no funciona te dice que esa
  vía está cerrada — eso también va al informe.

---

## Cómo encajan las dos

| | OpenVAS/GVM | Metasploit |
|---|---|---|
| Pregunta | ¿Qué *parece* vulnerable? | ¿Qué *es* explotable? |
| Entrega | Lista priorizada de hipótesis | Prueba del impacto real |
| Riesgo que describe | Potencial | Demostrado |
| En el informe | Anexo + insumo de la tabla | Evidencia de cada hallazgo |

Primero enumeras a mano (sabes *por qué* miras cada cosa). Luego OpenVAS amplía
y prioriza. Luego Metasploit demuestra lo que de verdad importa. El informe
cuenta esa historia.
