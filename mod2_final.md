# Modulo #2: Gobernanza Digital: Gestión de Riesgos y Cumplimiento Normativo.
## Fecha de Entrega: 15/11/2026
## Medio: Canvas

### Objetivo
Conducir un Risk Assesment sobre la infraestructura de la organización y considerando su objetivo de negocios y 
el contexto en la que se desarrolla la organización.

### Descripción
El estudiante identificará los diferentes activos de la organización considerando que lo que no se identifca no se 
puede proteger. Posteriormente, el estudiante identificará las diferentes vulnerabilidades en la infraestructura
de la organización. Una ves identificados los activos y sus correspondientes vulnerabilidades, el estudiante 
identificará las amenazas que puedan explotar dichas vulnerabilidades en los activos y poner a la organización.
El alumno deberá de identificar los riesgos asociados a consecuencia de las amenazas que puedan explotar las 
vulnerabilidades identificadas. Asi mismo, el alumno deberá de identificar el tratamiento que le dará a cada riesgo
y su correspondiente justificación. Finalmente, el alumno deberá de determinar el impacto y la probabilidad de 
ocurrencia de cada uno de los riesgos identificados.

### Sistema de Gobernanza y Gestión de Riesgo
Para poder soportar el objetivo y descripción de la presente actividad, se deberá de desarrollar una aplicación web
corriendo en un sistema Ubuntu Linux con un servidor Apache y una base de datos en MariaDB. Este sistema correrá 
dentro de una VM creada con Vagrant y su correspondiente Vagrantfile.  La interface de usuario se creará con 
Bootstrap poniendo enfasis en la experiencia del usuario.

La pantalla inicial o Home de la applicación web, deberá de mostrar un dashboard que permita facilmente identificar
el estado actual de la orgaanización en cuanto al nivel de riesgo, es decir una clasificación. Así mismo, se 
mostrara el total de activos identificados, número de vulnerabilidades, número de amenazas, y número de riesgos
identificados. Así mismo, se mostrará un heatmap (impacto x probabilidad) donde facilmente sea identificable los 
riesgos actuales y el estado en el que se encuentra cada uno de ellos.

1. Pestaña de Activos. En esta pestaña el usuario deberá de ser capaz de poder realizar las operaciones CRUD sobre
los diferentes activos identificados de la infraestructura de TI. Campos a capturar en esta pestaña son: fecha,
tipo de activo, nombre del activo, departamento, responsable directo, sistema operativo, descripción de su función,
tipo de participación en la función del negocio, directa o indirecta. 

2. Pestaña de Vulnerabilidades. El alumno seleccionara un activo, y le asignará su correspondiente vulnerabilidad,
indicando una descripción de la vulnerabilidad, asi como su valor CVSS.

3. Pestaña de Amenazas. Esta sección muestra al usuario las diferentes relaciones de Activos-Vulnerabilidades, y el
usuario podrá capturar mas de una amenaza para cada par de Activo-Vulnerabilidad. Una relación de 
Activo-Vulnerabilidad puede tener mas de una amenaza.

4. Pestaña de Riesgos. En esta sección el alumno asigna a la tupla Activo-Vulnerabilidad-Amenaza, un valor de 
Impacto y su correspondiente Probabilidad de Ocurrencia. Estos riesgos son los que se mostraran en el heatmap del 
Dashboard. El alumno deberá de identificar el tratamiento que le dará al Riesgo (Acepta, Transfiere, Mitiga, Evita, 
etc. de acuerdo a NIST SP 800-30). De igual manera deberá de justicar su decisión. Y finalmente su correspondiente
estado.

5. Pestaña de Reporte. En esta sección se mostraran todos los riesgos en un formato facil de leer y de interpretar.

Puedes sugerir valores para algunos campos así como otros campos no considerados en este documento. De igual manera,
siguiente el estandar NIST SP 800-30 sobre la Gestión de Riesgos, muesatra un panel de ayuda en cada pestaña para 
guiar al alumno sobre el proceso de Gestión de Riesgo.

### Entregable
El estudiante generará un resumen ejecutivo en formato a PDF don de se le informa a los niveles gerenciales de la 
organización, el estado que guarda la organización desde el punto de vista del Riesgo.
