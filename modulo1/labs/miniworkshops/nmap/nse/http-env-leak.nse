-- Miniworkshop Nmap — versión final del script que se construye en el
-- Episodio 3. Está aquí para comparar, no para copiar: el episodio lo levanta
-- en tres iteraciones y cada una explica por qué hace falta la siguiente.

local http = require "http"
local nmap = require "nmap"
local shortport = require "shortport"
local stdnse = require "stdnse"
local string = require "string"
local table = require "table"
local vulns = require "vulns"

description = [[
Busca ficheros de entorno (.env) servidos por error desde la raíz web y
reporta las claves que contienen.

Un fichero .env es la forma habitual de guardar la configuración de una
aplicación —credenciales de base de datos, tokens de API, claves de firma—
fuera del código. El fallo no es usarlo: es dejarlo dentro del directorio que
el servidor web publica. Cuando eso pasa, cualquiera que pida la ruta se lleva
las credenciales de producción en texto plano.

El script pide cada ruta candidata, descarta las respuestas que no parecen un
.env (un 404 personalizado que devuelve 200, por ejemplo) exigiendo al menos
dos líneas con forma CLAVE=valor, y clasifica las claves encontradas
marcando las que parecen secretos. Los valores se reportan truncados: el
objetivo de un escaneo es demostrar la exposición, no coleccionar secretos.
]]

---
-- @usage
-- nmap -p80 --script http-env-leak <objetivo>
-- nmap -p80 --script http-env-leak --script-args http-env-leak.paths=/.env,/config/.env <objetivo>
--
-- @args http-env-leak.paths Lista de rutas a probar, separadas por comas.
--       Por defecto: /.env,/.env.bak,/.env.local,/.env.save,/api/.env
--
-- @output
-- PORT   STATE SERVICE
-- 80/tcp open  http
-- | http-env-leak:
-- |   VULNERABLE:
-- |   Fichero de entorno (.env) expuesto en la raiz web
-- |     State: EXPLOIT
-- |     Description:
-- |       El servidor publica un fichero .env con credenciales en texto plano.
-- |     Extra information:
-- |       Ruta: /.env (HTTP 200, 621 bytes)
-- |       Claves sensibles: DB_PASSWORD, AWS_SECRET_ACCESS_KEY, API_TOKEN
-- |_      AWS_ACCESS_KEY_ID = AKIA4XMPLE7QDE...
---

author = "Miniworkshop Nmap - UAM Ciberdefensa Avanzada"
license = "Same as Nmap--See https://nmap.org/book/man-legal.html"
categories = {"safe", "vuln", "discovery"}

portrule = shortport.http

-- Rutas por defecto. Son las que de verdad aparecen en despliegues reales:
-- el .env de siempre, y los restos que deja un editor o un despliegue a medias.
local DEFAULT_PATHS = {
  "/.env", "/.env.bak", "/.env.local", "/.env.save", "/api/.env"
}

-- Nombres de clave que, si aparecen, convierten el hallazgo en urgente.
local SENSITIVE = {
  "PASS", "PASSWORD", "SECRET", "TOKEN", "APIKEY", "API_KEY",
  "PRIVATE", "CREDENTIAL", "ACCESS_KEY", "AUTH",
}

-- ¿El cuerpo se parece de verdad a un .env?
-- Hace falta porque muchos servidores devuelven 200 con una página de error
-- bonita en vez de un 404 honesto. Exigir dos pares CLAVE=valor separa un
-- fichero de configuración de una página HTML.
local function looks_like_env(body)
  if not body or #body == 0 then return false end
  -- Una página HTML no es un .env, por muchos '=' que lleve.
  if body:lower():find("<html", 1, true) then return false end
  local pairs_found = 0
  for line in body:gmatch("[^\r\n]+") do
    if line:match("^%s*[A-Z][A-Z0-9_]*%s*=") then
      pairs_found = pairs_found + 1
    end
  end
  return pairs_found >= 2, pairs_found
end

local function is_sensitive(key)
  local upper = key:upper()
  for _, needle in ipairs(SENSITIVE) do
    if upper:find(needle, 1, true) then return true end
  end
  return false
end

-- Trunca el valor. Un escaneo tiene que DEMOSTRAR la exposición, no volcarla:
-- el informe de un pentest circula por correo y acaba en sitios imprevistos.
local function redact(value)
  if #value == 0 then return "(vacio)" end
  if #value <= 6 then return string.rep("*", #value) end
  return value:sub(1, 6) .. string.rep("*", math.min(#value - 6, 10))
end

local function parse_env(body)
  local keys, sensitive_keys, sample = {}, {}, nil
  for line in body:gmatch("[^\r\n]+") do
    local key, value = line:match("^%s*([A-Z][A-Z0-9_]*)%s*=%s*(.-)%s*$")
    if key then
      keys[#keys + 1] = key
      if is_sensitive(key) then
        sensitive_keys[#sensitive_keys + 1] = key
        if not sample and #value > 0 then
          sample = key .. " = " .. redact(value)
        end
      end
    end
  end
  return keys, sensitive_keys, sample
end

action = function(host, port)
  local paths = stdnse.get_script_args(SCRIPT_NAME .. ".paths")
  local candidates = {}

  if paths then
    for p in tostring(paths):gmatch("[^,]+") do
      candidates[#candidates + 1] = (p:gsub("^%s*(.-)%s*$", "%1"))
    end
  else
    candidates = DEFAULT_PATHS
  end

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

  local extra = {}

  for _, path in ipairs(candidates) do
    local response = http.get(host, port, path)
    if response and response.status == 200 and looks_like_env(response.body) then
      local keys, sensitive_keys, sample = parse_env(response.body)

      -- EXPLOIT y no VULN: no se ha deducido nada, se ha LEIDO el fichero.
      vuln.state = vulns.STATE.EXPLOIT
      extra[#extra + 1] = string.format("Ruta: %s (HTTP %d, %d bytes, %d claves)",
        path, response.status, #response.body, #keys)
      if #sensitive_keys > 0 then
        extra[#extra + 1] = "Claves sensibles: " .. table.concat(sensitive_keys, ", ")
      end
      if sample then
        extra[#extra + 1] = sample
      end
      stdnse.debug1("%s: .env encontrado en %s (%d claves)", SCRIPT_NAME, path, #keys)
    end
  end

  if #extra > 0 then
    -- Como tabla y no como una cadena con saltos de linea: asi la libreria
    -- vulns indenta cada renglon dentro de "Extra information".
    vuln.extra_info = extra
  end

  return report:make_output(vuln)
end
