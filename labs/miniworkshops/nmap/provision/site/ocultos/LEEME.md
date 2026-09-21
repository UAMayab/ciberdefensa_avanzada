# Artefactos ocultos del sitio de laboratorio

Estos dos ficheros son el objetivo de dos hallazgos del Episodio 3 —el
repositorio Git publicado (`http-git`) y el fichero de entorno filtrado
(el script propio `http-env-leak`)— y en el servidor web viven como `.env` y
`.git/` dentro de la raíz del sitio.

**Aquí se guardan sin el punto inicial a propósito.** Git no puede versionar un
directorio `.git` anidado, y la regla `.env` del `.gitignore` de la raíz
excluiría el otro. Guardados así, los dos llegan al repositorio, y
`provision/nmap-target.sh` los instala con su nombre real dentro de
`/var/www/lab/` al aprovisionar.

Si renombras algo aquí, cámbialo también en `nmap-target.sh`.
