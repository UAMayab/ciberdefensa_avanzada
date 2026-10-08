# A.1 — Casos sugeridos

Cuatro casos reales, todos con **reporte público técnico** y **tabla ATT&CK
oficial** al final. Esa tabla es la que usarás en el Paso 6 para corregirte a ti
mismo, así que **no la abras hasta llegar ahí**.

> **Volt Typhoon no está aquí a propósito**: es el caso de la demo de la
> Sesión 1.

Los cuatro son buenos. Elige por el que te dé más curiosidad o más se parezca a
tu sector, no por el que parezca más fácil.

---

## 1 · Akira — cifrado tras entrar por la VPN

| | |
|---|---|
| **Reporte** | CISA AA24-109A — *#StopRansomware: Akira Ransomware* |
| **URL** | <https://www.cisa.gov/news-events/cybersecurity-advisories/aa24-109a> |
| **Perfil del grupo** | <https://attack.mitre.org/groups/G1024/> |
| **Capa oficial** (Paso 6) | `https://attack.mitre.org/groups/G1024/G1024-enterprise-layer.json` — 24 técnicas |
| **Dificultad** | Media — narrativa lineal y clara |

**Por qué es interesante para este módulo.** El acceso inicial llega por
servicios VPN **sin autenticación multifactor**. Es el caso que mejor conecta
con la pregunta que vas a responder en la Sesión 4: ¿qué debería haber estado
filtrado, y dónde?

## 2 · Play — entrada por el servicio que dejaste expuesto

| | |
|---|---|
| **Reporte** | CISA AA23-352A — *#StopRansomware: Play Ransomware* |
| **URL** | <https://www.cisa.gov/news-events/cybersecurity-advisories/aa23-352a> |
| **Perfil del grupo** | <https://attack.mitre.org/groups/G1040/> |
| **Capa oficial** (Paso 6) | `https://attack.mitre.org/groups/G1040/G1040-enterprise-layer.json` — 35 técnicas |
| **Dificultad** | Media |

**Por qué es interesante para este módulo.** Explotación de aplicaciones
expuestas a Internet y abuso de cuentas válidas: el argumento más directo a
favor de tener una DMZ de verdad y no una red plana. Toca casi todas las
tácticas, así que llegar a 4 tácticas distintas es cómodo.

## 3 · Scattered Spider — cuando el firewall no pinta nada

| | |
|---|---|
| **Reporte** | CISA AA23-320A — *Scattered Spider* (actualizado en julio de 2025) |
| **URL** | <https://www.cisa.gov/news-events/cybersecurity-advisories/aa23-320a> |
| **Perfil del grupo** | <https://attack.mitre.org/groups/G1015/> |
| **Capa oficial** (Paso 6) | `https://attack.mitre.org/groups/G1015/G1015-enterprise-layer.json` — 98 técnicas |
| **Dificultad** | Media-alta — mucho comportamiento humano, poco binario |

**Por qué es interesante para este módulo.** Entran llamando por teléfono a la
mesa de ayuda, haciendo *SIM swapping* y cansando a la víctima a base de
notificaciones de MFA. **Ningún firewall de este módulo habría detenido el
acceso inicial.** Si eliges éste, el Paso 7 te va a doler — y ése es justo el
aprendizaje: descubrir dónde la arquitectura de red *no* es la respuesta.

Aviso: al ser el perfil de grupo más grande de los cuatro (98 técnicas), el
salto de escala del Paso 6 se nota mucho. No te asustes: tú mapeas un
incidente, MITRE acumula años de reportes.

## 4 · Salt Typhoon — espionaje dentro de los routers

| | |
|---|---|
| **Reporte** | CISA AA25-239A — *Countering Chinese State-Sponsored Actors Compromise of Networks Worldwide to Feed Global Espionage System* (ago. 2025) |
| **URL** | <https://www.cisa.gov/news-events/cybersecurity-advisories/aa25-239a> |
| **Perfil del grupo** | <https://attack.mitre.org/groups/G1045/> |
| **Capa oficial** (Paso 6) | `https://attack.mitre.org/groups/G1045/G1045-enterprise-layer.json` — 23 técnicas |
| **Dificultad** | Alta — el reporte es largo y muy técnico |

**Por qué es interesante para este módulo.** El objetivo **es** la
infraestructura de red: routers de backbone y de borde (PE/CE), modificados
para mantener acceso durante años. Es el caso más cercano a lo que vas a
diseñar en el entregable final, y el que mejor explica por qué un router
comprometido derrumba todo lo demás. Si te sientes cómodo con redes, elige éste.

---

## ¿Quieres usar un caso de tu propio sector?

Se puede, y suele motivar más. Avísale al docente y comprueba que tu reporte
cumple **las cuatro condiciones**:

1. **Es público y de una fuente seria** — organismo oficial (CISA, NCSC, INCIBE,
   CERT nacional) o el informe técnico de una empresa de seguridad. Una nota de
   prensa no sirve.
2. **Describe acciones, no adjetivos.** Si sólo dice «ataque sofisticado con
   técnicas avanzadas», no se puede mapear. Tiene que decir *qué hicieron*.
3. **Da para 8 técnicas en 4 tácticas.** Si sólo narra el acceso inicial, se
   queda corto.
4. **Trae su propia tabla ATT&CK** o el grupo tiene ficha en
   `attack.mitre.org/groups/`. Sin esto **no puedes hacer el Paso 6**, que es
   la mitad del valor de la actividad.

Si tu caso cumple 1, 2 y 3 pero no el 4, sirve igual: pide al docente que haga
de contraste en la revisión de la Sesión 2.

---

## Nota sobre los enlaces

Las páginas de CISA a veces tardan o cambian de ruta. Si un enlace no abre:

- busca el código del advisory (`AA24-109A`, `AA23-352A`, `AA23-320A`,
  `AA25-239A`) en <https://www.cisa.gov/news-events/cybersecurity-advisories>;
- o entra por la ficha del grupo en `attack.mitre.org`, que enlaza el reporte
  original en su sección de referencias.

Enlaces comprobados el 17 de septiembre de 2026.
