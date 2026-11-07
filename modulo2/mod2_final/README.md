# Proyecto final · Módulo II — Sistema de Gobernanza y Gestión de Riesgo

Una máquina virtual con Vagrant que sirve una aplicación web para conducir una **evaluación de riesgos con NIST SP 800-30 Rev. 1**: activos → vulnerabilidades → amenazas → riesgos, con tablero, mapa de calor y reporte. Es la herramienta del proyecto final del **Módulo II — Gobernanza Digital: Gestión de Riesgos y Cumplimiento Normativo**.

- **Enunciado para el estudiante:** [`Actividad-Final-Modulo-II.pdf`](Actividad-Final-Modulo-II.pdf) (fuente en `docs/`).
- **Entregable del estudiante:** un resumen ejecutivo en PDF para la dirección, más el respaldo `.json` del sistema. Entrega: **15/11/2026**, por Canvas.

> La aplicación **no contiene datos reales**. El «ejemplo guiado» (Transportes Chaac) es una empresa ficticia y está marcada así en su nombre y en su contexto.

## Requisitos

- **Vagrant** ≥ 2.4 y **VirtualBox** ≥ 7.0.
- **Memoria:** 1 GB para la VM (cuenta con 1.5 GB libres). **Disco:** unos 5 GB.
- **Internet:** solo la primera vez, para la imagen base y los paquetes. Después funciona sin conexión (Bootstrap está incluido en `provision/app/public/assets/vendor/`).

## Puesta en marcha

```bash
cd modulo2/mod2_final
vagrant up          # ~4 min la primera vez
```

Abre **http://192.168.61.30/**. Si esa red host-only no funciona en tu equipo, usa **http://localhost:8080/** (reenvío de puerto a `127.0.0.1`).

| Comando | Efecto |
|---|---|
| `vagrant halt` / `vagrant up` | Apaga y enciende conservando los datos. |
| `vagrant provision` | Reinstala la aplicación y los catálogos **sin borrar** los datos del estudiante. |
| `vagrant destroy` | Elimina la VM **y el análisis**. Antes: *Configuración → Descargar respaldo*. |

## Qué hay dentro

| Máquina | Imagen | Memoria | CPU | Red |
|---|---|---|---|---|
| `grc-final` | `cloud-image/ubuntu-24.04` | 1024 MB | 1 | host-only `192.168.61.30` + NAT con `localhost:8080` → `:80` |

Apache 2.4 · PHP 8.3 · MariaDB 10.11 · Bootstrap 5.3.3 y Bootstrap Icons 1.11.3 (incluidos, sin CDN). La red `192.168.61.0/24` es la del laboratorio *Kaab Orgánicos*, así que ambos pueden estar encendidos a la vez (el rango `192.168.56.0/21` es el que VirtualBox permite por defecto en Linux y macOS).

## La aplicación

| Pestaña | Qué hace |
|---|---|
| **Inicio** | Nivel de riesgo global, totales de activos / vulnerabilidades / amenazas / riesgos, **mapa de calor** impacto × probabilidad con el estado de cada riesgo, distribución por nivel y pendientes del análisis. |
| **Activos** | CRUD: fecha, tipo, nombre, departamento, responsable, sistema operativo, descripción de su función, participación (directa / indirecta). |
| **Vulnerabilidades** | Se elige un activo y se registra la vulnerabilidad con su descripción y su valor CVSS (severidad en vivo). |
| **Amenazas** | Muestra cada par Activo-Vulnerabilidad; cada par admite varias amenazas. |
| **Riesgos** | A cada tupla Activo-Vulnerabilidad-Amenaza: impacto, probabilidad (el nivel se calcula en vivo), tratamiento (NIST SP 800-39), justificación y estado. |
| **Reporte** | Todos los riesgos de mayor a menor nivel, con lectura rápida; imprimible / PDF y CSV. |
| **Configuración** | Perfil de la organización (objetivo de negocio, contexto, alcance, tolerancia), respaldo y restauración, ejemplo guiado y vaciado. |

Cada pestaña lleva un **panel «Guía NIST SP 800-30»** (plegable, recuerda su estado) con el paso y la tarea de NIST que le corresponde, cómo llenarla y preguntas para pensar.

**Modelo de riesgo.** Probabilidad e impacto van de 1 a 5; el nivel sale de la matriz de la **Tabla I-2 de NIST SP 800-30** (guardada como datos en `matriz_riesgo` y verificada celda por celda contra el PDF de NIST). El **nivel global** es el mayor entre el promedio de los riesgos vigentes (todos menos los *Cerrados*) y el nivel máximo menos uno, para que un solo riesgo Muy alto no se diluya.

## Base de datos `grc`

```
activos ──< vulnerabilidades ──< amenazas ──1 riesgos          (ON DELETE CASCADE; un riesgo por amenaza)
organizacion (1 fila: perfil y tolerancia)
catálogos: cat_nivel · escala · matriz_riesgo · cat_tipo_activo · cat_fuente_amenaza · cat_tratamiento · cat_estado_riesgo
vista v_riesgos: un renglón por riesgo con toda su cadena (la usan tablero, heatmap, reporte y CSV)
```

`provision/db/schema.sql` crea las tablas; `provision/db/catalogos.sql` carga los catálogos y los valores NIST. Ambos son idempotentes. Para mirar los datos: `vagrant ssh -c "sudo mariadb grc"`.

## Estructura

```
Vagrantfile
provision/provision.sh        instala y configura todo (idempotente)
provision/db/                 schema.sql · catalogos.sql
provision/app/inc/            bootstrap.php · riesgo.php · ayuda.php · layout.php · respaldo.php · ejemplo.json
provision/app/public/         las pantallas (*.php) y assets/ (css, js, Bootstrap)
docs/                         fuente del PDF del estudiante (md + css + build_pdf.py + capturas)
Actividad-Final-Modulo-II.pdf enunciado para el estudiante
```

Para regenerar el PDF tras cambiar el texto: `python3 docs/build_pdf.py` (necesita `python3-markdown` y Google Chrome).

## Seguridad de la propia aplicación

Es un laboratorio aislado, pero el código se escribió como si no lo fuera, por si los estudiantes lo auditan: consultas preparadas (PDO) en todo; salida escapada con `htmlspecialchars`; token CSRF en cada formulario que escribe; cabeceras `nosniff` / `X-Frame-Options`; `expose_php` y `display_errors` apagados; usuario de base de datos con contraseña aleatoria, solo accesible por socket local y archivo `config.php` `0640 root:www-data`; el CSV neutraliza fórmulas (`=`, `+`, `-`, `@`). **No tiene autenticación**: es de un solo usuario y solo escucha en la red host-only. No la expongas a otra red.

## Verificado

`vagrant up` desde cero sin errores; `vagrant provision` repetido conserva datos y credenciales; 67 comprobaciones funcionales sobre la VM real (CRUD de las cuatro entidades, validaciones, cascada de borrado, XSS, inyección SQL, CSRF, matriz contra la Tabla I-2, respaldo ida y vuelta con *rollback* ante un archivo corrupto); modales y cálculo en vivo probados en Chrome.
