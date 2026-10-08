# Episodio 3 — Preguntarle al servicio, y escribir tu propia pregunta

> **Miniworkshop «Nmap de cero a experto» · Episodio 3 de 3**
> Certificación en Ciberdefensa Avanzada — Universidad Anáhuac Mayab

## Objetivo

Que uses NSE sabiendo qué hace cada script por dentro —y cuándo no puedes
fiarte de él— y que termines escribiendo uno tuyo, en Lua, que encuentre algo
que ningún script de la distribución encuentra.

## Antes de empezar

**Qué necesitas**

- Los Episodios [1](episodio-1-quien-esta-ahi.md) y
  [2](episodio-2-detras-de-la-puerta.md) hechos.
- El laboratorio levantado. Casi todo este episodio funciona **sin privilegios**;
  sólo los scripts de SNMP necesitan `sudo` porque van sobre UDP.
- Un editor de texto. Nada más: el intérprete de Lua viene dentro de Nmap.

**Qué NO necesitas**

Saber Lua. Se explica lo que hace falta sobre la marcha, y es poco.

**Cuánto rinde:** ~110 minutos. Los Pasos 6 a 9 (escribir el script) son ~50 de
ellos y se pueden hacer en otra sesión.

**No se entrega nada.**

**Herramienta:** Nmap 7.94 con NSE · <https://nmap.org/nsedoc/>

---

## Introducción

Hasta aquí Nmap ha hecho una sola cosa: mandar paquetes y clasificar
respuestas. Eso tiene un techo. Puede decirte que en el 3306 hay un MariaDB
10.11.14; no puede decirte que el usuario `root` no tiene contraseña.

Para saber eso hay que **hablar el protocolo**: abrir una conexión MySQL,
mandar un paquete de autenticación con la contraseña vacía y ver si el servidor
dice que sí. Eso ya no es escanear, es usar el servicio.

NSE (*Nmap Scripting Engine*) es el sitio donde Nmap hace eso. Son **605
scripts** escritos en Lua que se ejecutan contra los puertos que el escaneo
encontró abiertos, con acceso a librerías que hablan HTTP, SMB, SSH, MySQL,
SNMP y unas cuantas decenas de protocolos más.

Dos cosas que conviene saber desde el principio, porque nadie las dice:

1. **NSE no es un escáner de vulnerabilidades.** No tiene la cobertura de
   Nessus ni de OpenVAS, y no pretende tenerla.
2. **Los scripts son de calidad desigual.** Son aportaciones de la comunidad, y
   en este laboratorio vas a tropezarte con **dos que están rotos**. Eso no es
   un accidente del diseño del episodio: es lo que pasa en la vida real, y
   saber diagnosticarlo es la mitad de lo que separa a quien usa una
   herramienta de quien la entiende.

## Resultados de aprendizaje

Al terminar puedo:

- Elegir scripts por categoría, por nombre y con expresiones booleanas, y
  justificar por qué `--script vuln` no es una auditoría.
- Distinguir un script que lee un banner de uno que comprueba comportamiento, y
  decir cuál de los dos me creo.
- Diagnosticar un script que falla: leer el error, abrir el Lua y encontrar la
  causa.
- Escribir un script NSE con su portrule, sus argumentos y su reporte, e
  instalarlo para usarlo por nombre.

---

## Paso 1 — Qué hay en la caja · 10 min

**Qué haces.** Mirar el inventario antes de usarlo.

```bash
# 1. Cuántos scripts hay
ls /usr/share/nmap/scripts/*.nse | wc -l        # -> 605

# 2. Qué dice un script de sí mismo
nmap --script-help ftp-anon
```

```
ftp-anon
Categories: default auth safe
https://nmap.org/nsedoc/scripts/ftp-anon.html
  Checks if an FTP server allows anonymous logins.
```

**Qué buscar.** La línea `Categories`. Las catorce categorías que existen:

| Categoría | Qué agrupa |
|---|---|
| `safe` | No tumba nada, no explota nada, no abusa |
| `intrusive` | Puede cargarse el servicio o disparar alarmas |
| `default` | Los 128 que corren con `-sC`. Seleccionados por rápidos, útiles y discretos |
| `auth`, `brute` | Credenciales: los primeros las comprueban, los segundos las adivinan |
| `discovery`, `version` | Información sobre la red y afinado de `-sV` |
| `vuln`, `exploit`, `dos`, `malware` | Vulnerabilidades, explotación, denegación, puertas traseras |
| `external`, `broadcast`, `fuzzer` | Consultan servicios de terceros, emiten al segmento, o fuzzean |

**Por qué importa.** `external` es la que más disgustos da: esos scripts mandan
datos del objetivo **a servicios de Internet** (bases de whois, la API de
Shodan, la de Vulners). En una auditoría bajo acuerdo de confidencialidad, eso
puede ser una fuga de información contractualmente prohibida. `-sC` no incluye
ninguno, pero `--script vuln` sí incluye `vulners`.

## Paso 2 — Seleccionar scripts · 10 min

```bash
# 3. Los 128 de la categoría default
nmap -sC -p21,80 192.168.60.10

# 4. Una categoría entera
nmap --script safe -p21 192.168.60.10

# 5. Todos los de HTTP menos los de fuerza bruta (expresión booleana)
nmap --script "http-* and not brute" -p80 192.168.60.10

# 6. Uno concreto, con argumentos
nmap --script http-auth --script-args http-auth.path=/admin/ -p80 192.168.60.10
```

```
| http-auth:
| HTTP/1.1 401 Unauthorized\x0D
|_  Basic realm=Panel de administracion
```

**Qué buscar.** El comando 5 usa `and`, `or`, `not` y comodines. Es la forma de
decir «quiero mirar HTTP a fondo pero sin ponerme a adivinar contraseñas».

**Por qué importa.** `--script-args` es lo que separa un script útil de uno
inútil. `http-auth` por defecto mira `/`, donde no hay autenticación; apuntado a
`/admin/` encuentra el panel. **Si un script no devuelve nada, antes de
descartarlo mira qué argumentos acepta** con `--script-help`.

## Paso 3 — Recorrer los siete servicios · 20 min

**Qué haces.** Pasarle a cada puerto los scripts que le tocan, y leer.

```bash
# 7. FTP
nmap -p21 --script ftp-anon,ftp-syst 192.168.60.10
```

```
| ftp-anon: Anonymous FTP login allowed (FTP code 230)
| drwxrwxrwx    2 21       21           4096 Sep 21 06:23 entrada [NSE: writeable]
|_-rw-r--r--    1 0        0             254 Sep 21 06:23 tarifario-2026.txt
```

`[NSE: writeable]`: no sólo puedes entrar sin credenciales, puedes **subir**
ficheros. En un servidor web con FTP compartiendo directorio, eso es ejecución
remota de código.

```bash
# 8. SSH: qué criptografía acepta
nmap -p22 --script ssh2-enum-algos 192.168.60.10
```

```
|   kex_algorithms: (15)
|       diffie-hellman-group1-sha1
|       diffie-hellman-group14-sha1
|   encryption_algorithms: (10)
|       3des-cbc
|       aes128-cbc
|   mac_algorithms: (13)
|       hmac-sha1
|       hmac-md5
```

`diffie-hellman-group1-sha1` usa un primo de **1024 bits fijo y público**.
`hmac-md5` usa MD5. `3des-cbc` tiene bloques de 64 bits (ataque Sweet32). Nada
de esto es un CVE de OpenSSH: es OpenSSH 9.6, parcheado, **configurado mal**.

```bash
# 9. TLS: el certificado y las suites
nmap -p443 --script ssl-cert,ssl-enum-ciphers,ssl-dh-params 192.168.60.10
```

```
| ssl-cert: Subject: commonName=intranet.auroramaritima.example
| Public Key type: rsa
| Public Key bits: 1024
| Signature Algorithm: sha1WithRSAEncryption
| Not valid before: 2023-01-15T00:00:00
|_Not valid after:  2024-01-15T00:00:00
```

Tres defectos en seis líneas: clave de 1024 bits, firma SHA-1, y **caducado
desde enero de 2024**. Y las suites:

```
|   TLSv1.0:
|       TLS_DHE_RSA_WITH_AES_256_CBC_SHA (dh 1024) - F
|       TLS_DH_anon_WITH_AES_256_CBC_SHA (dh 1024) - F
|       TLS_RSA_WITH_AES_128_CBC_SHA (rsa 1024) - F
```

Todas con **F**. Fíjate en `DH_anon`: *anon* significa que el servidor **no se
autentica**. Cualquiera puede ponerse en medio. Y `ssl-dh-params` lo dice con
todas las letras:

```
|   VULNERABLE:
|   Anonymous Diffie-Hellman Key Exchange MitM Vulnerability
|     State: VULNERABLE
```

```bash
# 10. MySQL
nmap -p3306 --script mysql-empty-password,mysql-users,mysql-databases 192.168.60.10
```

```
| mysql-empty-password:
|   anonymous account has empty password
|_  root account has empty password
| mysql-users:
|   aurora_app
|   root
|_  mariadb.sys
```

```bash
# 11. Redis. OJO: sin -sV este script NO se ejecuta
nmap -sV -p16379 --script redis-info 192.168.60.10
```

```
16379/tcp open  redis   Redis key-value store 7.2.9 (64 bits)
| redis-info:
|   Version: 7.2.9
|   Operating System: Linux 6.6.10-0-virt x86_64
|   Process ID: 6005
```

**Prueba a quitar el `-sV`.** El script desaparece sin decir nada. La razón es
la `portrule`: `redis-info` se declara aplicable a «puerto 6379 o servicio
llamado redis». En el 16379, sin `-sV`, el servicio es `unknown`, la regla no se
cumple, y Nmap **omite el script en silencio**.

Es la trampa más habitual de NSE: *no aparece el script* y *no hay vulnerabilidad*
se ven exactamente igual en pantalla. Con `-sV` por delante, o con
`--script-args`, o forzando con `-p`, la regla se cumple y el script corre.

```bash
# 12. SNMP (necesita sudo, va por UDP)
sudo nmap -sU -p161 --script snmp-info,snmp-netstat 192.168.60.10
```

## Paso 4 — El servicio que da tres respuestas distintas · 15 min

Este paso es el corazón del episodio.

**Qué haces.** Preguntarle al FTP por su versión de tres maneras.

```bash
# 13. Primera pregunta: el banner de bienvenida
nmap -sV -p21 192.168.60.10
```

```
21/tcp open  ftp     vsftpd 2.3.4
```

vsFTPd 2.3.4 es la versión con la puerta trasera de 2011 (CVE-2011-2523):
quien mandaba un usuario acabado en `:)` conseguía una shell de root en el
puerto 6200. Si esto fuera verdad, has terminado.

```bash
# 14. Segunda pregunta: ¿existe la puerta trasera?
#     vulns.showall obliga a reportar también lo que NO es vulnerable
nmap -p21 --script ftp-vsftpd-backdoor --script-args vulns.showall 192.168.60.10
```

```
| ftp-vsftpd-backdoor:
|   NOT VULNERABLE:
|   vsFTPd version 2.3.4 backdoor
|     State: NOT VULNERABLE
|     IDs:  BID:48539  CVE:CVE-2011-2523
```

```bash
# 15. Tercera pregunta: que el servidor se describa con el comando STAT
nmap -p21 --script ftp-syst 192.168.60.10
```

```
|_     vsFTPd 3.0.5 - secure, fast, stable
```

**Qué buscar.** Tres herramientas, tres respuestas:

| Pregunta | Respuesta | Cómo lo averiguó |
|---|---|---|
| `-sV` | Es la 2.3.4 | **Leyó** el banner de bienvenida |
| `ftp-vsftpd-backdoor` | NO es vulnerable | **Intentó** la puerta trasera y no funcionó |
| `ftp-syst` | Es la 3.0.5 | **Preguntó** con el comando `STAT` del protocolo |

**Por qué importa.** La contradicción se resuelve así: el servidor es un vsftpd
3.0.5 al que le han cambiado el banner con una línea de configuración
(`ftpd_banner=`). Compruébalo:

```bash
# 16. La línea culpable, en la propia VM
vagrant ssh nmap-target -c "grep ftpd_banner /etc/vsftpd/vsftpd.conf"
```

```
ftpd_banner=(vsFTPd 2.3.4)
```

De ahí salen las tres ideas que hay que llevarse de este episodio:

1. **Hay dos clases de script.** Los que leen lo que el objetivo dice de sí
   mismo, y los que comprueban si algo es cierto. `-sV` y los scripts de banner
   son de la primera. `ftp-vsftpd-backdoor`, `mysql-empty-password` y
   `ssl-enum-ciphers` son de la segunda. **Sólo la segunda clase prueba algo.**

2. **`vulns.showall` no es opcional.** Sin él, un script que comprueba y
   concluye «no es vulnerable» **no imprime nada**, y su silencio es idéntico
   al de un script que no llegó a ejecutarse. Cuando quieras saber si algo se
   comprobó de verdad, pídelo.

3. **Esto pasa al revés todo el tiempo.** Un administrador que parchea vsftpd
   pero no cambia el banner aparecerá como vulnerable en todos los informes
   automáticos, para siempre. Y al contrario: un banner limpio no significa un
   sistema parcheado. Un informe que dice «vulnerable» citando sólo una versión
   es un informe sin verificar.

## Paso 5 — Cuando el script está roto · 15 min

**Qué haces.** Ejecutar un script que falla, y averiguar por qué.

```bash
# 17. Buscar ficheros de configuración respaldados en el docroot
nmap -p80 --script http-config-backup 192.168.60.10
```

```
|_http-config-backup: ERROR: Script execution failed (use -d to debug)
```

Primero: **¿hay algo que encontrar?**

```bash
# 18. A mano, con curl
curl -s -o /dev/null -w "%{http_code}\n" http://192.168.60.10/config.php.bak
curl -s http://192.168.60.10/config.php.bak | head -8
```

```
200
<?php
// Aurora Maritima - respaldo del 2024-11-03 antes de migrar a .env
define('DB_USER', 'root');
define('DB_PASS', '');            // pendiente: poner contrasena antes de salir a produccion
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'letmein');  // panel /admin/
```

El fichero está ahí, con las credenciales del panel. **El script falló teniendo
el hallazgo delante.** Ahora el diagnóstico, que es lo que el mensaje te pide:

```bash
# 19. Con depuración, buscando la traza de Lua
nmap -p80 --script http-config-backup -d3 192.168.60.10 2>&1 | grep -A3 "threw an error"
```

```
NSE: http-config-backup M:5ad5d95e9e58 against 192.168.60.10:80 threw an error!
/usr/share/nmap/scripts/http-config-backup.nse:220: variable 'escape_filename' is not declared
stack traceback:
	/usr/share/nmap/scripts/http-config-backup.nse:220: in function </usr/share/nmap/scripts/http-config-backup.nse:181>
```

Abre esa línea:

```bash
# 20. La línea 220 y su contexto
sed -n '218,222p' /usr/share/nmap/scripts/http-config-backup.nse
```

```lua
        -- check it if is valid before inserting
        if cfg.check(response.body) then
          local filename = stdnse.escape_filename((host.targetname or host.ip) .. url_path)
```

```bash
# 21. ¿Existe esa función en la librería?
grep -c "escape_filename" /usr/share/nmap/nselib/stdnse.lua      # -> 0
```

**Qué buscar.** `stdnse.escape_filename` **no existe** en Nmap 7.94. El script
la llama igualmente. Y como NSE carga los scripts bajo `strict.lua`, tocar una
variable global no declarada no devuelve `nil`: **aborta el script**.

**Por qué importa.** Mira *dónde* está la línea: dentro del `if` que se ejecuta
**cuando el fichero se ha encontrado y validado**. Es decir:

- Contra un servidor **sin** ficheros de respaldo, el script recorre sus ~90
  URLs, no entra nunca en ese bloque, y termina limpiamente sin reportar nada.
  Parece que funciona.
- Contra un servidor **con** un respaldo expuesto —justo el caso que te
  importa— el script se estrella.

**El script sólo falla cuando acierta.** Si tu metodología es «lanzo
`--script vuln` y leo lo que sale», este hallazgo no existe para ti. Y es el
que te da la contraseña del panel de administración.

Compruébalo: las credenciales que el script no pudo entregarte funcionan.

```bash
# 22. Con las credenciales de config.php.bak, en una lista de una línea
printf 'admin\n' > /tmp/u.txt; printf 'letmein\n' > /tmp/p.txt
nmap -p80 --script http-brute \
  --script-args http-brute.path=/admin/,userdb=/tmp/u.txt,passdb=/tmp/p.txt 192.168.60.10
```

```
| http-brute:
|   Accounts:
|     admin:letmein - Valid credentials
|_  Statistics: Performed 2 guesses in 1 seconds, average tps: 2.0
```

Y la cadena completa, que empieza en un `.git` publicado:

```bash
# 23. El repositorio Git expuesto
nmap -p80 --script http-git 192.168.60.10
```

```
|   192.168.60.10:80/.git/
|     Git repository found!
|     Remotes:
|_      https://despliegue:FerryProgreso17@git.auroramaritima.example/ti/portal-rastreo.git
```

```bash
# 24. Esa contraseña, probada contra SSH
printf 'soporte\nadmin\nroot\n' > /tmp/u.txt
printf 'FerryProgreso17\nletmein\naurora2024\n' > /tmp/p.txt
nmap -p22 --script ssh-brute \
  --script-args userdb=/tmp/u.txt,passdb=/tmp/p.txt,brute.firstonly=true 192.168.60.10
```

```
| ssh-brute:
|   Accounts:
|     soporte:FerryProgreso17 - Valid credentials
|_  Statistics: Performed 9 guesses in 1 seconds, average tps: 9.0
```

**Nueve intentos.** No hubo fuerza bruta: hubo una contraseña publicada en una
URL de la propia empresa y reutilizada en una cuenta del sistema. Así se
encadenan los hallazgos de verdad, y por eso una lista de puertos abiertos no
es un informe.

> **Sobre `--script vuln` y `vulners`.** La categoría `vuln` existe y es útil,
> pero `vulners` —uno de sus scripts— es de categoría `external`: manda las
> versiones detectadas a una API en Internet. Sin conexión no devuelve nada, y
> con conexión puede estar prohibido por el contrato de la auditoría. Nmap no
> sustituye a un escáner de vulnerabilidades; lo complementa.

## Paso 6 — Tu primer script: el esqueleto · 15 min

**Qué haces.** Escribir el script más simple que puede existir, para ver las
piezas.

Hay algo que el laboratorio expone y que **ningún script de los 605 busca**: el
fichero `.env`. `http-config-backup` busca respaldos de `config.php`; nadie
busca `.env`, que es donde las aplicaciones modernas guardan sus secretos.

```bash
# 25. Ahí está, servido por el web
curl -s http://192.168.60.10/.env | head -5
```

Crea `http-env-leak.nse` con esto:

```lua
local shortport = require "shortport"

description = [[
Busca ficheros .env servidos por error desde la raiz web.
]]

author = "Tu nombre"
license = "Same as Nmap--See https://nmap.org/book/man-legal.html"
categories = {"safe", "discovery"}

portrule = shortport.http

action = function(host, port)
  return "aqui va el hallazgo"
end
```

```bash
# 26. Ejecutarlo por ruta, sin instalarlo
nmap -p80 --script ./http-env-leak.nse 192.168.60.10
```

```
80/tcp open  http
|_http-env-leak: aqui va el hallazgo
```

**Qué buscar.** Cinco piezas, y ya están todas:

| Pieza | Para qué |
|---|---|
| `description` | Lo que sale en `--script-help`. No es decoración: es lo que lee quien decide si ejecutarlo |
| `categories` | Cómo se selecciona el script. Ponerle `safe` obliga a que de verdad lo sea |
| `portrule` | **La pieza clave.** Una función que decide si el script aplica a este puerto |
| `action` | Lo que se ejecuta si la portrule dice que sí. Lo que devuelve se imprime |
| `require` | Las librerías. `shortport` trae portrules ya hechas |

**Por qué importa.** `shortport.http` es la misma portrule que usan los ~150
scripts de HTTP: cubre el 80, el 443, el 8080 y cualquier puerto cuyo servicio
`-sV` haya identificado como HTTP. Escribirla a mano sería:

```lua
portrule = shortport.port_or_service({80,443,8080}, {"http","https"})
```

Es exactamente el mecanismo que dejó a `redis-info` sin ejecutarse en el Paso 3.

## Paso 7 — Pedir el fichero de verdad · 15 min

**Qué haces.** Hacer una petición HTTP y comprobar que la respuesta es un `.env`.

```lua
local http = require "http"
local shortport = require "shortport"
local stdnse = require "stdnse"
local table = require "table"

-- ... (description, author, license, categories, portrule igual que antes)

local DEFAULT_PATHS = {"/.env", "/.env.bak", "/.env.local"}

-- Un 200 no basta: muchos servidores devuelven 200 con una pagina de error
-- bonita en vez de un 404 honesto. Exigir dos pares CLAVE=valor separa un
-- fichero de configuracion de una pagina HTML.
local function looks_like_env(body)
  if not body or #body == 0 then return false end
  if body:lower():find("<html", 1, true) then return false end
  local found = 0
  for line in body:gmatch("[^\r\n]+") do
    if line:match("^%s*[A-Z][A-Z0-9_]*%s*=") then found = found + 1 end
  end
  return found >= 2
end

action = function(host, port)
  local paths = stdnse.get_script_args(SCRIPT_NAME .. ".paths")
  local candidates = DEFAULT_PATHS
  if paths then
    candidates = {}
    for p in tostring(paths):gmatch("[^,]+") do
      candidates[#candidates + 1] = (p:gsub("^%s*(.-)%s*$", "%1"))
    end
  end

  local out = {}
  for _, path in ipairs(candidates) do
    local response = http.get(host, port, path)
    if response and response.status == 200 and looks_like_env(response.body) then
      out[#out + 1] = string.format("%s (HTTP %d, %d bytes)",
                                    path, response.status, #response.body)
    end
  end

  if #out > 0 then return out end
end
```

```bash
# 27. Probarlo, y luego con una ruta inventada
nmap -p80 --script ./http-env-leak.nse 192.168.60.10
nmap -p80 --script ./http-env-leak.nse --script-args http-env-leak.paths=/no/existe 192.168.60.10
```

```
| http-env-leak:
|_  /.env (HTTP 200, 671 bytes)
```

Con la ruta inventada, **la línea del script no aparece en absoluto**.

**Qué buscar.** Tres decisiones de diseño que no son adorno:

1. **`http.get`, no un socket.** La librería `http` gestiona reintentos,
   redirecciones, *pipelining* y la caché de respuestas que comparten todos los
   scripts de HTTP. Un socket crudo te obliga a reimplementar HTTP mal.

2. **`looks_like_env`.** Comprobar sólo el código 200 produce falsos positivos
   en cuanto el servidor tenga una página de error «amable». **Todo script útil
   necesita una validación de contenido**; es lo que separa un script de una
   fuente de ruido.

3. **`stdnse.get_script_args(SCRIPT_NAME .. ".paths")`.** Usando `SCRIPT_NAME`
   en vez de escribir el nombre a mano, el argumento sigue funcionando si
   renombras el fichero.

Fíjate también en que `action` devuelve `nil` cuando no encuentra nada: así
Nmap no imprime la línea del script. **Devolver «no encontré nada» es ruido.**

## Paso 8 — Reportarlo como un adulto · 15 min

**Qué haces.** Cambiar la salida por un reporte estructurado con la librería
`vulns` — la misma que usa `ftp-vsftpd-backdoor`.

Añade `local vulns = require "vulns"`, pon `categories = {"safe","vuln","discovery"}`
y sustituye el final de `action`:

```lua
  local report = vulns.Report:new(SCRIPT_NAME, host, port)
  local vuln = {
    title = "Fichero de entorno (.env) expuesto en la raiz web",
    state = vulns.STATE.NOT_VULN,
    description = [[
El servidor publica un fichero .env con credenciales en texto plano. Cualquiera
que conozca la ruta se lleva la configuracion de produccion sin autenticarse.]],
    references = {
      "https://owasp.org/www-project-top-ten/2021/A05_2021-Security_Misconfiguration",
    },
  }

  -- ... dentro del bucle, cuando encuentras uno:
  --     vuln.state = vulns.STATE.EXPLOIT
  --     extra[#extra + 1] = ...

  if #extra > 0 then vuln.extra_info = extra end
  return report:make_output(vuln)
```

Y la parte que de verdad importa, redactar los valores:

```lua
-- Un escaneo tiene que DEMOSTRAR la exposicion, no volcarla: el informe de un
-- pentest circula por correo y acaba en sitios imprevistos.
local function redact(value)
  if #value == 0 then return "(vacio)" end
  if #value <= 6 then return string.rep("*", #value) end
  return value:sub(1, 6) .. string.rep("*", math.min(#value - 6, 10))
end
```

La versión completa está en [`../nse/http-env-leak.nse`](../nse/http-env-leak.nse).
Compárala con la tuya cuando la hayas intentado, no antes.

```bash
# 28. La versión final
nmap -p80 --script ../nse/http-env-leak.nse 192.168.60.10
```

```
| http-env-leak:
|   VULNERABLE:
|   Fichero de entorno (.env) expuesto en la raiz web
|     State: VULNERABLE (Exploitable)
|       El servidor publica un fichero .env con credenciales en texto plano. Cualquiera
|       que conozca la ruta se lleva la configuracion de produccion sin autenticarse.
|     Extra information:
|       Ruta: /.env (HTTP 200, 671 bytes, 19 claves)
|       Claves sensibles: DB_PASSWORD, REDIS_PASSWORD, MAIL_PASSWORD, AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, API_TOKEN
|       MAIL_PASSWORD = Kq7#ma**********
|     References:
|_      https://owasp.org/www-project-top-ten/2021/A05_2021-Security_Misconfiguration
```

```bash
# 29. El caso negativo, pedido explícitamente
nmap -p80 --script ../nse/http-env-leak.nse \
  --script-args "http-env-leak.paths=/no/existe,vulns.showall" 192.168.60.10
```

```
|   NOT VULNERABLE:
|   Fichero de entorno (.env) expuesto en la raiz web
|     State: NOT VULNERABLE
```

**Qué buscar.** Los estados que `vulns` distingue: `NOT_VULN`, `LIKELY_VULN`,
`VULN`, `DoS` y `EXPLOIT`. Aquí se usa **`EXPLOIT`**, que es el más fuerte, y
está justificado: el script no dedujo nada a partir de una versión, **leyó el
fichero**. Esa diferencia —la misma del Paso 4— ahora la estás aplicando tú al
escribir.

**Por qué importa.** Usar `vulns` te da gratis el formato que ya conocen todos
los que leen salidas de Nmap, la integración con `-oX` para informes, y el
respeto por `vulns.showall`. Un script que imprime texto suelto obliga a cada
consumidor a parsearlo a mano.

## Paso 9 — Instalarlo · 5 min

```bash
# 30. Al directorio personal de scripts
mkdir -p ~/.nmap/scripts
cp http-env-leak.nse ~/.nmap/scripts/

# 31. Ya se usa por nombre, sin ruta
nmap -p80 --script http-env-leak 192.168.60.10

# 32. Y se documenta solo
nmap --script-help http-env-leak
```

```
http-env-leak
Categories: safe vuln discovery
https://nmap.org/nsedoc/scripts/http-env-leak.html
  Busca ficheros de entorno (.env) servidos por error desde la raíz web y
  reporta las claves que contienen.
```

```bash
# 33. Como está en la categoría vuln, entra en las selecciones por categoría
nmap -p80 --script vuln 192.168.60.10 | grep -A2 env-leak
```

**Qué buscar.** `~/.nmap/scripts/` es tu directorio personal y no necesita
permisos de root. Si instalas en el del sistema (`/usr/share/nmap/scripts/`)
hay que regenerar el índice con `sudo nmap --script-updatedb`, que reconstruye
`script.db`, el fichero que asocia cada script con sus categorías.

**Por qué importa.** Un script que vive en tu directorio personal se comporta
como cualquiera de los 605: se selecciona por nombre, por comodín y por
categoría. Acabas de extender Nmap.

---

## Cuaderno de bitácora (opcional)

1. Tu `http-env-leak.nse`, con el comentario de por qué tu `looks_like_env`
   rechaza lo que rechaza.
2. La cadena completa del Paso 5 en cinco líneas: de `/.git/config` a una
   sesión SSH válida, diciendo en cada salto qué herramienta lo permitió.
3. La lista de hallazgos del laboratorio ordenada **por riesgo, no por puerto**,
   y al lado de cada uno si lo detectó un script que *leyó* o uno que
   *comprobó*.
4. Una idea para tu segundo script: algo que tu organización tenga y que
   ninguno de los 605 busque.

## Reto opcional

- Amplía `http-env-leak` para que busque también `/.git/config`,
  `/docker-compose.yml` y `/wp-config.php~`, con una función de validación
  distinta para cada uno. Es lo que hace `http-config-backup`, pero sin el
  fallo de la línea 220.
- **Repara —hasta donde se pueda— `http-config-backup`.** Copia el script a
  tu directorio, sustituye la llamada inexistente de la línea 220 por algo que
  sí exista (por ejemplo, un `gsub` que limpie el nombre de fichero) y
  ejecútalo otra vez.

  Verás que **deja de estrellarse**, y que aun así no llega a reportar el
  `/config.php.bak` que sabes que está ahí: la ejecución llega al hallazgo —se
  ve con `-d2`, que imprime `Page was '200 OK', it exists! (/config.php.bak)`—
  pero la entrada que acaba en la salida sale vacía. Hay un segundo fallo
  después del primero.

  Ese es el ejercicio completo, y su conclusión es más útil que un parche que
  funcione: **un script abandonado rara vez tiene un solo problema**, y decidir
  cuándo dejar de arreglarlo y escribir el tuyo es un criterio profesional. Los
  Pasos 6 a 9 son la otra mitad de esa decisión.

## Autoevaluación

- [ ] Sé por qué `redis-info` no se ejecuta sin `-sV`, y sé nombrar el mecanismo.
- [ ] Puedo explicar por qué `-sV`, `ftp-syst` y `ftp-vsftpd-backdoor` dan tres
      respuestas distintas sobre el mismo servidor, y cuál me creo.
- [ ] Sé qué hace `vulns.showall` y por qué su ausencia es engañosa.
- [ ] Supe diagnosticar un script roto leyendo su código Lua.
- [ ] Escribí un script con portrule, argumentos y reporte, y lo instalé.
- [ ] Puedo decir la diferencia entre `vulns.STATE.VULN` y `vulns.STATE.EXPLOIT`
      y justificar cuál usé.

## Solución de problemas

| Síntoma | Qué pasa |
|---|---|
| `'X' is not declared` | `strict.lua` detectó una global sin declarar. Casi siempre falta un `local` o un `require` |
| El script no aparece en la salida | La portrule no se cumplió. Prueba con `-sV`, o con `--script-trace` para ver si llegó a ejecutarse |
| `attempt to index a nil value (local 'response')` | `http.get` puede devolver `nil`. Comprueba `if response and response.status == ...` |
| `--script-help` no encuentra mi script | Estás fuera del directorio, o no está en `~/.nmap/scripts/`. Para el directorio del sistema, `sudo nmap --script-updatedb` |
| `ERROR: Script execution failed` sin más | Repite con `-d3` y busca `threw an error` |

## Uso ético

Los scripts de este episodio **usan** los servicios, no sólo los miran.
`ssh-brute` intenta iniciar sesión; `http-brute` envía credenciales;
`ftp-vsftpd-backdoor` intenta explotar una puerta trasera. En muchas
jurisdicciones eso ya no es reconocimiento, es acceso no autorizado, con
independencia de que funcione.

Además, `ftp-anon` con permiso de escritura y los scripts de `brute` pueden
**bloquear cuentas** o **llenar discos** en un sistema real. La categoría
`intrusive` está ahí por algo. Antes de lanzar `--script vuln` contra
infraestructura de un cliente: autorización escrita, ventana acordada, y
revisar qué scripts son `external` para no filtrar datos a terceros.

---

**Fin de la serie.** Si quieres seguir: el [Apéndice A](../apendices/apendice-a-tcpdump.md)
te enseña a leer el tráfico que tus propios escaneos generan, que es la única
forma de comprobar que Nmap te está diciendo la verdad.
