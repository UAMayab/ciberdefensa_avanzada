# Syllabus — Módulo I: Fundamentos de Ciberdefensa y Arquitectura de Redes Seguras

**Certificación en Ciberdefensa Avanzada + Microcredencial en Criptografía Postcuántica**
**Universidad Anáhuac Mayab — Educación Continua**

---

## 1. Datos generales

| Campo | Detalle |
|---|---|
| Módulo | I — Fundamentos de Ciberdefensa y Arquitectura de Redes Seguras |
| Duración | 20 horas (5 sesiones de 4 horas) |
| Modalidad | A distancia, sesiones sincrónicas en vivo |
| Calendario | Vie 18 sep · Sáb 19 sep · Vie 25 sep · Sáb 26 sep · Vie 2 oct |
| Horario | Viernes 18:00–22:00 · Sábado 09:00–13:00 |
| Nivel | Intermedio (profesionales en activo del sector TI/ciberseguridad) |
| Insignia a obtener | Fundamentos de Arquitectura de Redes Blindadas |
| Entregable final | Diseño de Topología de Red Segura: esquema técnico que incluya configuración lógica de perímetros, firewalls y sistemas de detección (IDS/IPS) para mitigar riesgos identificados |
| Criterio de evaluación | 80% de asistencia + entregable aprobado |

## 2. Objetivo del módulo

Establecer defensas perimetrales e internas robustas mediante el diseño de arquitecturas de red seguras y protocolos de protección, para mitigar riesgos de intrusión y salvaguardar los activos organizacionales.

## 3. Perfil del participante

Profesionales en activo de TI, redes, administración de sistemas o seguridad informática, con conocimientos básicos previos de informática y redes. El enfoque del módulo es aplicado: cada bloque teórico se acompaña de una actividad práctica.

## 4. Entorno de laboratorio (requisito técnico)

Dado el enfoque práctico, se recomienda que cada participante prepare, antes de la Sesión 1:

- **Kali Linux** (VM actualizada, VMware/VirtualBox/UTM — mín. 4 GB RAM, 2 vCPU)
- Un **hipervisor** local (VirtualBox o VMware Workstation/Player)
- Una **máquina víctima/objetivo** para pruebas controladas: *Metasploitable2* o *DVWA* (uso exclusivo en laboratorio aislado, sin conexión a redes de producción)
- Acceso a una **VM de pfSense** (se desplegará en la Sesión 4)
- Editor de diagramas: **draw.io / diagrams.net** (gratuito, para el entregable final)

Se recomienda montar todas las VMs en una **red interna aislada** (host-only / internal network) del hipervisor, para practicar con tráfico y actividad simulada sin riesgo. **Ningún ejercicio se realiza contra redes, dominios o sistemas de terceros sin autorización explícita.**

## 5. Modelo de trabajo: demo en vivo + práctica autónoma

Cada actividad práctica del módulo dura **~60 minutos en total**, distribuidos así:

- **20 min en sesión (sincrónico):** el docente hace una demostración en vivo de la herramienta — qué hace, cómo se ejecuta, cómo se interpreta la salida — usando un caso guiado.
- **40 min de trabajo autónomo (asincrónico):** el estudiante repite el ejercicio por su cuenta sobre su propio laboratorio, con una consigna distinta o más amplia que la del docente, y produce una evidencia breve.

El objetivo de cada actividad **no es dominar la herramienta a fondo**, sino que el estudiante entienda con claridad **qué información o control entrega la herramienta** y **en qué escenarios profesionales se aplica (y en cuáles no)**. Cada actividad autónoma se revisa brevemente (10–15 min) al inicio de la sesión siguiente antes de avanzar con contenido nuevo.

El diseño detallado de cada actividad está en el **Anexo A**. Las tablas de la Sección 6 solo indican en qué bloque se hace la demo en vivo; el trabajo autónomo correspondiente se entrega antes de la sesión siguiente.

## 6. Estructura por sesiones

---

### Sesión 1 — Viernes 18 sep (18:00–22:00 hrs)
**Tema: Introducción a la ciberseguridad · Conceptos básicos · Amenazas y riesgos cibernéticos**

| Bloque | Duración | Contenido |
|---|---|---|
| 1 | 30 min | Bienvenida, encuadre del módulo, del entregable final y del modelo de trabajo (demo + práctica autónoma); verificación de laboratorios (Kali, hipervisor, VM víctima) |
| 2 | 40 min | 1.1 Introducción a la ciberseguridad: tríada CIA, superficie de ataque, actores de amenaza |
| 3 | 40 min | 1.2 Conceptos básicos: vulnerabilidad vs. amenaza vs. riesgo, vectores de ataque, Cyber Kill Chain / MITRE ATT&CK |
| 4 | **20 min** | **Demo en vivo — Actividad A.1:** MITRE ATT&CK Navigator (ver Anexo A.1). *Tarea autónoma: 40 min, entregar antes de Sesión 2.* |
| 5 | 40 min | 1.3 Amenazas y riesgos cibernéticos: reconocimiento pasivo/activo, OSINT, superficie expuesta de una organización |
| 6 | **20 min** | **Demo en vivo — Actividad A.2:** Reconocimiento externo con theHarvester/Recon-ng + Shodan (ver Anexo A.2). *Tarea autónoma: 40 min, entregar antes de Sesión 2.* |
| 7 | 30 min | Cierre: aclaración de consignas de las tareas A.1 y A.2, dudas técnicas de laboratorio |

**Resultado esperado:** el participante deja preparado su laboratorio y comprende el modelo de trabajo; al concluir sus tareas autónomas, habrá mapeado un caso de brecha en ATT&CK Navigator y producido un primer reporte de superficie expuesta.

---

### Sesión 2 — Sábado 19 sep (09:00–13:00 hrs)
**Tema: Marco legal y regulaciones · Fundamentos de redes y protocolos**

| Bloque | Duración | Contenido |
|---|---|---|
| 1 | 15 min | Revisión grupal de las Actividades A.1 y A.2 (hallazgos, dudas, buenas prácticas observadas) |
| 2 | 45 min | 1.4 Marco legal y regulaciones en ciberseguridad: LFPDPPP (México), GDPR, panorama de ISO 27001/NIST (se profundiza en Módulo II) — ejercicio de mapeo rápido de un incidente hipotético contra obligaciones legales aplicables |
| 3 | 50 min | 1.5.1 Fundamentos de redes: modelo OSI/TCP-IP, direccionamiento IP, segmentación lógica |
| 4 | 40 min | 1.5.1 Protocolos de red en profundidad: ARP, ICMP, TCP/UDP, DNS, HTTP/HTTPS |
| 5 | **20 min** | **Demo en vivo — Actividad A.3:** Captura y análisis de tráfico con Wireshark/tcpdump (ver Anexo A.3). *Tarea autónoma: 40 min, entregar antes de Sesión 3.* |
| 6 | **20 min** | **Demo en vivo — Actividad A.4:** Descubrimiento de red con Nmap (ver Anexo A.4). *Tarea autónoma: 40 min, entregar antes de Sesión 3.* |
| 7 | 50 min | Cierre y taller guiado: relación entre lo observado en Wireshark/Nmap y las decisiones de diseño de topología |

**Resultado esperado:** el participante interpreta tráfico de red real y produce un inventario de hosts/servicios de su laboratorio (segundo y tercer insumo del entregable final).

---

### Sesión 3 — Viernes 25 sep (18:00–22:00 hrs)
**Tema: Topologías de red segura**

| Bloque | Duración | Contenido |
|---|---|---|
| 1 | 15 min | Revisión grupal de las Actividades A.3 y A.4 |
| 2 | 60 min | 1.5.2 Principios de diseño seguro: defensa en profundidad, segmentación, VLANs, zonas de confianza, DMZ, principio de menor privilegio — análisis de casos de topologías mal diseñadas vs. bien diseñadas |
| 3 | 40 min | Patrones de arquitectura: red plana vs. segmentada, microsegmentación, Zero Trust (panorama) |
| 4 | **20 min** | **Demo en vivo — Actividad A.5:** Simulación de topologías con GNS3/EVE-NG (ver Anexo A.5). *Tarea autónoma: 40 min, entregar antes de Sesión 4.* |
| 5 | 105 min | Taller guiado: cada participante inicia el diseño de su propia topología segmentada (LAN corporativa, DMZ, red de servidores), usando como insumo el inventario de la Sesión 2, con retroalimentación en vivo del docente |

**Resultado esperado:** primer borrador de la topología segmentada, que se completará en las sesiones siguientes con firewalls e IDS/IPS.

---

### Sesión 4 — Sábado 26 sep (09:00–13:00 hrs)
**Tema: Configuración y gestión de firewalls**

| Bloque | Duración | Contenido |
|---|---|---|
| 1 | 15 min | Revisión grupal de la Actividad A.5 (topologías construidas) |
| 2 | 40 min | Fundamentos de firewalls: filtrado con estado vs. sin estado, ACLs, NAT, firewalls de próxima generación (panorama) |
| 3 | 35 min | Diseño de reglas de firewall: principio de "deny by default", documentación de reglas — ejercicio de diseño de matriz de reglas sobre la topología propia |
| 4 | **20 min** | **Demo en vivo — Actividad A.6:** Firewall perimetral con pfSense (ver Anexo A.6). *Tarea autónoma: 40 min, entregar antes de la siguiente actividad.* |
| 5 | **20 min** | **Demo en vivo — Actividad A.7:** Firewall de host con iptables/nftables/UFW (ver Anexo A.7). *Tarea autónoma: 40 min, entregar antes de Sesión 5.* |
| 6 | 110 min | Taller guiado: implementación de pfSense y de reglas de host sobre la topología propia, con retroalimentación en vivo |

**Resultado esperado:** la topología del entregable incorpora zonas separadas por firewall, con reglas documentadas y verificadas.

---

### Sesión 5 — Viernes 2 oct (18:00–22:00 hrs)
**Tema: Sistemas de detección de intrusos (IDS/IPS) · Integración del entregable**

| Bloque | Duración | Contenido |
|---|---|---|
| 1 | 15 min | Revisión grupal de las Actividades A.6 y A.7 |
| 2 | 40 min | Fundamentos de IDS/IPS: detección basada en firmas vs. anomalías, ubicación en la red (perimetral, interno, basado en host) |
| 3 | **20 min** | **Demo en vivo — Actividad A.8:** IDS/IPS con Suricata/Snort (ver Anexo A.8). *Tarea autónoma: 40 min, a completar durante la sesión o inmediatamente después.* |
| 4 | 45 min | Correlación de alertas y ajuste de falsos positivos (tuning básico de reglas) — ejercicio guiado sobre las alertas generadas en la Actividad A.8 |
| 5 | 100 min | Integración final del entregable: consolidar topología + reglas de firewall + ubicación de sensores IDS/IPS en un esquema técnico único — asesoría en vivo, trabajo individual con retroalimentación del docente |

**Resultado esperado:** entrega del esquema técnico final — *Diseño de Topología de Red Segura* — con perímetros, firewalls y sistemas de detección documentados.

---

## 7. Anexo A — Diseño detallado de actividades prácticas

Cada actividad sigue la misma estructura: **objetivo de comprensión** (qué entrega la herramienta y cuándo se usa), **demo en vivo (20 min)**, **trabajo autónomo (40 min)**, **evidencia a producir** y **preguntas guía** para consolidar el aprendizaje. Ninguna evidencia requiere dominio experto de la herramienta — el criterio de éxito es que el estudiante pueda explicar, con su propio ejemplo, qué le entregó la herramienta y en qué caso profesional la usaría.

**Laboratorio técnico compartido:** las Actividades A.3, A.4, A.7 y A.8 se apoyan en un entorno Vagrant reproducible ubicado en [`labs/modulo1/`](labs/modulo1/) (dos VMs: `dmz-ubuntu` y `int-alpine`, ver [`labs/modulo1/README.md`](labs/modulo1/README.md) para topología, credenciales y guía de comandos por actividad). La Actividad A.6 lo usa como red objetivo para las reglas de pfSense. Las Actividades A.1, A.2 y A.5 no requieren este laboratorio — ver la razón en cada actividad.

---

### A.1 — MITRE ATT&CK Navigator
*(Sesión 1 · asociada a 1.2 Conceptos básicos)*

**Objetivo de comprensión:** entender que ATT&CK Navigator no detecta ni bloquea nada — es una herramienta de **visualización y comunicación** que traduce el comportamiento de un atacante (tácticas y técnicas) a un lenguaje común entre equipos técnicos y no técnicos. Se aplica en threat modeling, reportes a dirección, análisis de brechas de cobertura defensiva y comunicación entre equipos rojo/azul.

- **Demo en vivo (20 min):** el docente toma el resumen público de un incidente conocido, identifica 5–6 técnicas visibles en el relato (p. ej. phishing como acceso inicial, ejecución vía PowerShell, movimiento lateral), las ubica en la matriz de Navigator, crea una "layer" coloreada por táctica y la exporta en JSON.
- **Trabajo autónomo (40 min):** el estudiante elige un reporte público distinto (un advisory de CISA, un perfil de grupo de amenaza del propio sitio de ATT&CK, o un caso de su sector), identifica al menos 8 técnicas en al menos 4 tácticas distintas, construye su propia layer y la exporta.
- **Evidencia:** archivo de layer exportado (JSON o captura) + 4–5 líneas explicando qué técnica le pareció más difícil de mitigar y por qué.
- **Preguntas guía:**
  1. ¿Qué comunica esta herramienta que un documento de texto no comunica con la misma claridad?
  2. ¿En qué reunión o entregable profesional (comité de riesgo, reporte a un SOC, auditoría) la usarías?
  3. ¿Qué NO hace el Navigator? (no detecta, no bloquea, no reemplaza un SIEM ni un IDS)

**Entorno técnico:** ninguno — app web (navegador), sin nada que aprovisionar.

---

### A.2 — Reconocimiento externo: theHarvester / Recon-ng + Shodan
*(Sesión 1 · asociada a 1.3 Amenazas y riesgos cibernéticos)*

**Objetivo de comprensión:** entender qué información de una organización es visible públicamente sin tocar su red (OSINT) y qué activos quedan indexados por buscadores de dispositivos expuestos (Shodan). Se aplica en la fase de reconocimiento de una auditoría o pentest autorizado, y en ejercicios de "higiene de exposición" que cualquier organización puede hacerse a sí misma.

- **Demo en vivo (20 min):** el docente ejecuta `theHarvester` contra un dominio de práctica autorizado (el dominio propio del curso o del participante, nunca de terceros), muestra correos/subdominios encontrados; luego corre 1–2 módulos de `recon-ng`; cierra con una búsqueda por filtros en Shodan (`org:`, `port:`, `product:`) sobre un rango propio o un ejemplo genérico, mostrando qué tipo de hallazgo generaría una alerta de exposición.
- **Trabajo autónomo (40 min):** el estudiante repite el ejercicio sobre un dominio que esté autorizado a usar (su propia organización con permiso, o un dominio de práctica provisto por el docente) y documenta: subdominios/correos encontrados, resultados de Recon-ng, y cualquier activo propio visible en Shodan.
- **Evidencia:** "Reporte de Superficie Expuesta" de una página: hallazgo, herramienta que lo generó, nivel de riesgo (bajo/medio/alto).
- **Preguntas guía:**
  1. ¿Qué encontraste que la organización probablemente no sabía que estaba expuesto?
  2. ¿Qué diferencia hay entre lo que entrega theHarvester/Recon-ng (información) y Shodan (activos indexados)?
  3. ¿Qué autorización necesitas antes de ejecutar este tipo de reconocimiento sobre un dominio?

**Entorno técnico:** ninguno — se ejecuta desde el Kali de la Sección 4 contra un dominio real y autorizado; una VM de laboratorio interna no reproduce superficie expuesta en Internet.

---

### A.3 — Captura y análisis de tráfico: Wireshark / tcpdump
*(Sesión 2 · asociada a 1.5.1 Protocolos de red)*

**Objetivo de comprensión:** entender que estas herramientas dan **visibilidad real** del tráfico —lo que de verdad ocurre en la red, más allá de lo que documenta un diagrama— y que esa visibilidad es la base para validar reglas de firewall e IDS más adelante en el módulo. Se aplica en troubleshooting, respuesta a incidentes, y validación de controles ya implementados.

- **Demo en vivo (20 min):** el docente captura tráfico en vivo en el laboratorio mientras navega (HTTP vs. HTTPS), aplica filtros (`http`, `tcp.port==`, `ip.addr==`), sigue un flujo TCP y muestra el handshake de 3 vías; muestra la misma captura hecha con `tcpdump` desde línea de comandos para exportarla como `.pcap`.
- **Trabajo autónomo (40 min):** el estudiante captura tráfico en su laboratorio mientras genera 3 tipos de eventos (navegación, una consulta DNS, y una conexión a un servicio en texto plano de su VM Metasploitable2, p. ej. FTP o Telnet) y documenta: el handshake TCP, la consulta DNS, y cualquier credencial o dato visible en texto plano.
- **Evidencia:** archivo `.pcap` o capturas de pantalla anotadas + 4–5 líneas: ¿qué hallazgo justificaría cambiar una configuración de red (p. ej. migrar FTP a SFTP)?
- **Preguntas guía:**
  1. ¿Qué visibilidad da esta herramienta que un firewall o un IDS, por sí solos, no dan?
  2. ¿Cuándo usarías tcpdump en vez de Wireshark (por ejemplo, en un servidor remoto sin interfaz gráfica)?
  3. ¿Qué limitación tiene frente a tráfico cifrado?

**Entorno técnico:** [`labs/modulo1/`](labs/modulo1/) — capturar contra `dmz-ubuntu` (HTTP, login FTP en claro `labdemo`/`labdemo123`) y `int-alpine` (Telnet en claro). Ver "Guía rápida por actividad" en el README del lab.

---

### A.4 — Descubrimiento de red: Nmap
*(Sesión 2 · asociada a 1.5.1 Fundamentos de redes)*

**Objetivo de comprensión:** entender que Nmap entrega un **inventario**: hosts vivos, puertos abiertos, servicios, versiones y, con cierta fiabilidad, sistema operativo. Se aplica en inventario de activos, auditorías de superficie expuesta, y verificación de que las reglas de firewall configuradas realmente bloquean lo que deberían (uso que se retomará en la Sesión 4).

- **Demo en vivo (20 min):** el docente ejecuta contra la VM Metasploitable2: descubrimiento de host (`-sn`), escaneo de puertos (`-sS`/`-sT`), detección de servicio/versión (`-sV`), detección de SO (`-O`) y un script básico (`-sC`); explica cuándo usar un escaneo sigiloso vs. uno exhaustivo.
- **Trabajo autónomo (40 min):** el estudiante escanea todo su subred de laboratorio y produce un inventario completo: host, IP, puertos abiertos, servicio/versión, y si ese servicio debería o no estar expuesto en un diseño seguro.
- **Evidencia:** tabla de inventario de red (host, IP, puertos, servicio/versión, ¿debería estar expuesto? sí/no + justificación). Esta tabla alimenta directamente el entregable final.
- **Preguntas guía:**
  1. ¿Qué decisión de tu topología cambia después de ver este inventario?
  2. ¿En qué escenarios (redes de producción, entornos OT/ICS) un escaneo agresivo sería inapropiado?
  3. ¿Qué no te dice Nmap que sí necesitarías para una auditoría de vulnerabilidades completa?

**Entorno técnico:** [`labs/modulo1/`](labs/modulo1/) — escanear `192.168.56.10` (`dmz-ubuntu`: 22/80/21) y `192.168.57.10` (`int-alpine`: 22/23) para comparar perfiles de servicios.

---

### A.5 — Simulación de topologías: GNS3 / EVE-NG
*(Sesión 3 · asociada a 1.5.2 Topologías de red segura)*

**Objetivo de comprensión:** entender que un simulador de topologías permite **diseñar y validar una arquitectura antes de implementarla**, sin arriesgar hardware ni redes reales. Se aplica en diseño y prueba de arquitecturas, capacitación, y documentación de propuestas técnicas.

- **Demo en vivo (20 min):** el docente construye una topología pequeña (router, dos switches/VLANs, un segmento "DMZ"), conecta los nodos y muestra pruebas de conectividad entre segmentos, guardando el proyecto.
- **Trabajo autónomo (40 min):** el estudiante construye su propia topología segmentada (mínimo 3 segmentos: LAN corporativa, DMZ, red de servidores), usando el inventario de hosts de la Actividad A.4, con un plan de direccionamiento IP coherente.
- **Evidencia:** archivo de proyecto GNS3/EVE-NG + diagrama exportado + tabla de direccionamiento IP por segmento.
- **Preguntas guía:**
  1. ¿Qué te permite probar este simulador que sería costoso o riesgoso probar en una red física real?
  2. ¿En qué etapa de un proyecto de red (diseño, prueba de concepto, documentación) usarías esta herramienta?
  3. ¿Qué limita a un simulador frente a una implementación real?

**Entorno técnico:** ninguno de `labs/modulo1/` — GNS3/EVE-NG es un simulador de topologías con sus propias imágenes de router/switch, se instala en el host o como appliance propio.

---

### A.6 — Firewall perimetral: pfSense
*(Sesión 4 · asociada a Configuración de firewalls)*

**Objetivo de comprensión:** entender que un firewall perimetral es el **punto único de control** entre zonas de confianza distintas, y que su valor no es solo bloquear tráfico sino **dejar evidencia (logs)** de qué se permitió y qué se denegó. Se aplica como punto de control entre una red interna y una DMZ o Internet.

- **Demo en vivo (20 min):** el docente despliega pfSense, configura interfaces WAN/LAN, crea una regla de permiso y una de negación entre dos zonas, y muestra el log en vivo.
- **Trabajo autónomo (40 min):** el estudiante despliega pfSense como gateway de su topología (Actividad A.5), configura sus interfaces según sus segmentos, y escribe al menos 5 reglas bajo el principio "deny by default" entre la DMZ y la LAN interna; verifica cada regla con Nmap desde otra VM y confirma el resultado en el log.
- **Evidencia:** captura de las reglas configuradas + log mostrando al menos una conexión permitida y una denegada + matriz de reglas (origen, destino, puerto, acción, justificación).
- **Preguntas guía:**
  1. ¿Qué evidencia te da pfSense para justificar una decisión de seguridad ante una auditoría?
  2. ¿En qué tipo de organización pfSense sería suficiente, y en cuál necesitarías un firewall comercial de próxima generación?
  3. Si configuraras por error una regla demasiado permisiva, ¿cómo lo detectarías con las herramientas ya vistas (Nmap, Wireshark)?

**Entorno técnico:** pfSense se despliega por separado (Sección 4); sus interfaces WAN/LAN se asignan a las redes internas `dmz_net`/`lan_net` de [`labs/modulo1/`](labs/modulo1/) para tener tráfico real `dmz-ubuntu` ↔ `int-alpine` que filtrar — ver "Integración con Kali / pfSense" en el README del lab.

---

### A.7 — Firewall de host: iptables / nftables / UFW
*(Sesión 4 · asociada a Configuración de firewalls)*

**Objetivo de comprensión:** entender que el firewall de host es **defensa en profundidad**: complementa, no sustituye, al firewall perimetral, y es indispensable cuando no todo el tráfico pasa por un único punto (servidores individuales, contenedores, microservicios).

- **Demo en vivo (20 min):** el docente muestra las reglas actuales de un host (`iptables -L -v`), agrega una regla para bloquear un puerto específico, valida el bloqueo con Nmap desde otra VM, y repite lo mismo con la sintaxis simplificada de UFW.
- **Trabajo autónomo (40 min):** el estudiante endurece al menos 2 hosts de su topología (p. ej. el servidor de la DMZ y un servidor interno), permitiendo solo los puertos/servicios necesarios para la función de cada host y negando el resto; valida con Nmap desde un segmento autorizado y desde uno no autorizado.
- **Evidencia:** exportación de reglas (`iptables-save` o `ufw status`) de cada host + resultado del Nmap de verificación antes/después.
- **Preguntas guía:**
  1. ¿Por qué no basta con el firewall perimetral (pfSense) y también se necesita esto a nivel de host?
  2. ¿Cuándo usarías nftables/iptables directamente en lugar de UFW?
  3. ¿Qué riesgo corres si bloqueas mal una regla en un servidor remoto sin acceso físico?

**Entorno técnico:** [`labs/modulo1/`](labs/modulo1/) — endurecer `dmz-ubuntu` con `ufw` e `int-alpine` con `iptables`/`nftables` (Alpine no trae UFW). Ver comandos de referencia en el README del lab.

---

### A.8 — IDS/IPS: Suricata / Snort (+ Security Onion opcional)
*(Sesión 5 · asociada a Fundamentos de IDS/IPS)*

**Objetivo de comprensión:** entender que un IDS/IPS entrega **visibilidad de actividad anómala o maliciosa que un firewall no detecta** por sí solo, y que su valor depende directamente del ajuste ("tuning") de sus reglas — sin eso, genera fatiga de alertas. Se aplica en monitoreo continuo y en cumplimiento normativo que exige detección activa (se retoma en el Módulo II).

- **Demo en vivo (20 min):** el docente despliega Suricata en modo IDS sobre un segmento, genera tráfico "sospechoso" (un escaneo de Nmap contra ese segmento) y revisa la alerta resultante en el log (`eve.json`) o en un visor simple.
- **Trabajo autónomo (40 min):** el estudiante despliega Suricata (o Snort) monitoreando la DMZ de su propia topología, repite escaneos y tráfico generado en actividades previas, revisa las alertas producidas, identifica al menos un falso positivo, y ajusta o desactiva una regla ruidosa.
- **Evidencia:** captura de al menos 3 alertas + breve análisis de cada una (¿verdadero o falso positivo? ¿qué acción tomarías?) + evidencia del ajuste de al menos 1 regla.
- **Preguntas guía:**
  1. ¿Qué detecta el IDS que el firewall (pfSense) no habría detectado?
  2. ¿Cuál es la diferencia práctica entre modo IDS y modo IPS, y cuándo elegirías cada uno?
  3. ¿Qué pasa si nunca se le dedica tiempo al ajuste de reglas?

**Entorno técnico:** [`labs/modulo1/`](labs/modulo1/) — Suricata se instala y configura en `dmz-ubuntu` (ruleset propio en `/etc/suricata/rules/lab-custom.rules`); escaneos generados desde Kali contra `dmz_net` disparan las alertas. Comando y ruta de log verificados en el README del lab.

---

## 8. Resumen de herramientas open source utilizadas

| Herramienta | Actividad | Uso en el módulo |
|---|---|---|
| Kali Linux | Base | Sistema base para todas las actividades |
| MITRE ATT&CK Navigator | A.1 | Mapeo de amenazas y tácticas |
| theHarvester / Recon-ng | A.2 | Reconocimiento OSINT |
| Shodan | A.2 | Identificación de activos expuestos |
| Wireshark / tcpdump | A.3 | Análisis de tráfico y protocolos |
| Nmap | A.4, A.6, A.7, A.8 | Descubrimiento de red y verificación de reglas |
| GNS3 / EVE-NG | A.5 | Simulación de topologías de red |
| pfSense | A.6 | Firewall perimetral |
| iptables / nftables / UFW | A.7 | Firewall a nivel de host |
| Suricata / Snort / Security Onion | A.8 | IDS/IPS |
| draw.io (diagrams.net) | Entregable final | Documentación de la topología |

## 9. Evaluación

- **Asistencia mínima:** 80% de las 5 sesiones.
- **Entregable oficial del módulo:** Diseño de Topología de Red Segura (esquema técnico descriptivo con configuración lógica de perímetros, firewalls e IDS/IPS).
- **Rúbrica sugerida del entregable:**
  - Completitud de la topología y segmentación (25%)
  - Coherencia de las reglas de firewall documentadas (25%)
  - Ubicación y justificación de los sensores IDS/IPS (25%)
  - Claridad técnica y presentación del esquema (25%)
- **Evidencias de las Actividades A.1–A.8:** son formativas — no forman parte de la rúbrica de la insignia, pero se recomienda que el docente las revise brevemente (ver "revisión grupal" al inicio de cada sesión) para confirmar comprensión antes de avanzar, ya que varias alimentan directamente el entregable final (A.4 y A.5, especialmente).

## 10. Nota sobre uso ético de las herramientas

Todos los laboratorios se ejecutan exclusivamente sobre máquinas y redes de laboratorio propias, aisladas de cualquier red de producción o de terceros. El reconocimiento externo (Actividad A.2) se limita a dominios propios o explícitamente autorizados. Se recomienda abrir la Sesión 1 recordando a los participantes el marco de uso ético y legal de las herramientas ofensivas/defensivas que se emplearán a lo largo del módulo y de la certificación completa.
