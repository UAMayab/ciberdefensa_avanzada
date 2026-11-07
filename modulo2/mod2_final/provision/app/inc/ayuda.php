<?php
// Panel de ayuda de cada pestaña: lo que NIST SP 800-30 Rev. 1 pide en ese punto del proceso.
// Las tareas (1-1 … 4-2) son las del documento; los textos son una guía didáctica, no una cita literal.
declare(strict_types=1);

function ayuda(string $clave): array
{
    $a = [
        'inicio' => [
            'titulo' => 'Cómo leer este tablero',
            'nist'   => 'Paso 3 · Comunicar y compartir (tareas 3-1 y 3-2)',
            'que'    => 'El tablero resume el análisis para quien decide. Responde tres preguntas: ¿qué tan expuesta está la organización?, ¿cuántas piezas hemos identificado? y ¿dónde están los riesgos que más importan?',
            'pasos'  => [
                'El <b>nivel global</b> usa los riesgos vigentes (todos menos los Cerrados). Es el mayor entre el promedio y el nivel máximo menos uno: un solo riesgo Muy alto no queda diluido entre muchos bajos.',
                'En el <b>mapa de calor</b>, el color de la celda es el nivel de riesgo (Tabla I-2 de NIST) y la ficha <b>R-00X</b> es un riesgo. La forma de la ficha dice su estado: lleno = Abierto, engranaje = En tratamiento, escudo = Mitigado, pulgar = Aceptado, tachado = Cerrado.',
                'Los <b>pendientes</b> te dicen qué falta analizar: activos sin vulnerabilidades, vulnerabilidades sin amenazas, amenazas sin evaluar. Si un activo no tiene vulnerabilidades, ¿realmente no las tiene o no las has buscado?',
            ],
            'preguntas' => [
                '¿Qué riesgos están en rojo y naranja? ¿Alguno está Abierto, sin plan?',
                '¿Hay riesgos Aceptados por encima de la tolerancia de la organización?',
            ],
            'ojo'    => 'Un tablero verde no significa seguridad: significa que aún no has identificado riesgos. Mira primero los pendientes.',
        ],
        'activos' => [
            'titulo' => 'Identifica lo que hay que proteger',
            'nist'   => 'Paso 1 · Preparar (tareas 1-2 y 1-4: alcance y fuentes de información)',
            'que'    => 'Lo que no se identifica no se puede proteger. Haz un inventario de los activos que sostienen el objetivo de negocio de la organización: equipos, software, datos, servicios en la nube, instalaciones, personas y proveedores.',
            'pasos'  => [
                'Empieza por el <b>objetivo de negocio</b> (Configuración): ¿qué necesita funcionar para que la organización cumpla su misión?',
                'Registra un activo por renglón. Sé específico: «Servidor del portal de clientes», no «servidores».',
                '<b>Participación</b>: <i>Directa</i> si el negocio se detiene o se degrada cuando el activo falla; <i>Indirecta</i> si lo apoya pero hay alternativas.',
                'La <b>criticidad</b> y la <b>clasificación de la información</b> te ayudarán después a decidir el impacto.',
            ],
            'preguntas' => [
                '¿Qué activos mueven el dinero, los datos personales o la operación diaria?',
                '¿Qué activos son de terceros (nube, proveedores) pero tú respondes por ellos?',
                '¿Hay activos «invisibles»: una hoja de cálculo crítica, la cuenta de un administrador, una persona clave?',
            ],
            'ojo'    => 'Error común: inventariar solo servidores. Los datos, los procesos y las personas también son activos.',
        ],
        'vulnerabilidades' => [
            'titulo' => 'Busca las debilidades de cada activo',
            'nist'   => 'Paso 2 · Conducir — tarea 2-3 (vulnerabilidades y condiciones predisponentes)',
            'que'    => 'Una vulnerabilidad es una debilidad que una amenaza puede aprovechar. Elige un activo y registra cada debilidad que encuentres, con su puntaje CVSS.',
            'pasos'  => [
                'Selecciona el <b>activo</b> y describe la debilidad en una frase concreta (qué, dónde y por qué es un problema).',
                'El <b>CVSS</b> va de 0.0 a 10.0: 0.1–3.9 Baja · 4.0–6.9 Media · 7.0–8.9 Alta · 9.0–10.0 Crítica. Si la debilidad tiene un CVE, búscalo en el NVD y copia su puntaje y su vector. Si es una debilidad de configuración o de proceso (sin CVE), estima el puntaje con la calculadora CVSS 3.1.',
                'Fuentes: escaneos (Nmap, OpenVAS), entrevistas, revisión de configuración, políticas y procedimientos.',
            ],
            'preguntas' => [
                '¿Es técnica (sin parche), de configuración (contraseña por defecto) o de proceso (sin respaldos probados)?',
                '¿Qué condiciones agravan la debilidad? Por ejemplo, el servicio está expuesto a Internet.',
            ],
            'ojo'    => 'El CVSS mide la gravedad técnica de la debilidad, no el riesgo para tu organización. El riesgo lo calcularás en la pestaña Riesgos con impacto y probabilidad.',
        ],
        'amenazas' => [
            'titulo' => 'Pregunta: ¿quién o qué podría aprovecharla?',
            'nist'   => 'Paso 2 · Conducir — tareas 2-1 y 2-2 (fuentes y eventos de amenaza)',
            'que'    => 'Una amenaza es un evento causado por una fuente (un atacante, un error, una falla, un desastre) que puede explotar una vulnerabilidad. Cada par Activo-Vulnerabilidad puede tener varias amenazas distintas.',
            'pasos'  => [
                'Cada tarjeta es un par <b>Activo ⟷ Vulnerabilidad</b>. Agrega una amenaza por cada forma distinta en que esa debilidad podría aprovecharse.',
                'Elige la <b>fuente</b> (Tabla D-2 de NIST): <i>Adversaria</i> (alguien lo hace a propósito), <i>Accidental</i> (un error), <i>Estructural</i> (una falla del equipo o software) o <i>Ambiental</i> (huracán, incendio, corte de energía).',
                'Nombra el <b>evento</b> con un verbo: «Inyección SQL», «Ransomware», «Falla de disco».',
            ],
            'preguntas' => [
                '¿Una misma debilidad puede explotarla un atacante externo y también ser consecuencia de un error interno?',
                'Para la región donde opera la organización, ¿qué amenazas ambientales son realistas?',
            ],
            'ojo'    => 'NIST analiza primero las amenazas y después las vulnerabilidades (2-1 a 2-3). Aquí las emparejas al revés porque ya conoces la debilidad: parte de ella y pregunta quién la aprovecharía.',
        ],
        'riesgos' => [
            'titulo' => 'Evalúa, decide y justifica',
            'nist'   => 'Paso 2 · Conducir — tareas 2-4, 2-5 y 2-6 · Respuesta al riesgo (SP 800-39)',
            'que'    => 'Aquí asignas a cada tupla Activo-Vulnerabilidad-Amenaza un impacto y una probabilidad. El sistema calcula el nivel de riesgo con la matriz de NIST (Tabla I-2) y lo dibuja en el mapa de calor del tablero. Después decides qué hacer con el riesgo y por qué.',
            'pasos'  => [
                '<b>Probabilidad</b> (tarea 2-4): ¿qué tan probable es que la amenaza ocurra <i>y</i> cause un efecto adverso, dadas las debilidades y los controles que ya existen?',
                '<b>Impacto</b> (tarea 2-5): si ocurre, ¿qué tanto daña la misión, los activos, el dinero o a las personas? Piensa en el objetivo de negocio, no solo en la tecnología.',
                '<b>Tratamiento</b> (SP 800-39): Mitigar, Aceptar, Evitar, Transferir o Compartir. La <b>justificación</b> es lo que se evalúa: explica por qué esa opción y no otra.',
                'Define el <b>estado</b> y, si procede, el responsable, la fecha y el control propuesto. Al mitigar un riesgo, actualiza impacto y probabilidad al valor <i>residual</i>.',
            ],
            'preguntas' => [
                '¿Por qué esta probabilidad y no una más alta o más baja? ¿Qué evidencia tienes?',
                '¿Aceptar este riesgo es coherente con la tolerancia de la organización (Configuración)?',
                '¿Qué cuesta el control frente a lo que cuesta el incidente?',
            ],
            'ojo'    => 'Impacto y probabilidad son juicios: no hay un valor «correcto». Lo que se evalúa es que sean consistentes con el contexto de la organización y que estén justificados.',
        ],
        'reporte' => [
            'titulo' => 'Comunica el resultado',
            'nist'   => 'Paso 3 · Comunicar y compartir (3-1, 3-2) · Paso 4 · Mantener (4-1, 4-2)',
            'que'    => 'El reporte reúne todos los riesgos, de mayor a menor nivel, en un formato que cualquier directivo puede leer. Úsalo como base de tu resumen ejecutivo.',
            'pasos'  => [
                'Revisa que la lectura tenga sentido: los riesgos más altos deben ser los que más te preocupan. Si no, revisa tus valores de impacto y probabilidad.',
                'Imprime o guarda la página como PDF con el botón <b>Imprimir / PDF</b>; también puedes exportar la tabla a CSV.',
                'Tu <b>resumen ejecutivo</b> es otro documento: tradúcelo a lenguaje de negocio (dinero, clientes, continuidad), no de herramientas.',
                'El riesgo cambia: este reporte es una fotografía. NIST pide monitorear (4-1) y actualizar (4-2) el análisis.',
            ],
            'preguntas' => [
                '¿Qué decisión necesitas que tome la dirección después de leer esto?',
                '¿Qué riesgos fuera de la tolerancia no tienen plan?',
            ],
            'ojo'    => 'Un reporte técnico enseña lo que encontraste; un resumen ejecutivo pide una decisión. Procura que el tuyo haga lo segundo.',
        ],
        'configuracion' => [
            'titulo' => 'Define el contexto antes de analizar',
            'nist'   => 'Paso 1 · Preparar (tareas 1-1 a 1-5: propósito, alcance, supuestos y modelo de riesgo)',
            'que'    => 'Un riesgo solo tiene sentido frente a un objetivo de negocio y un contexto. Llena el perfil de la organización antes de capturar activos.',
            'pasos'  => [
                '<b>Objetivo de negocio</b>: lo que la organización quiere lograr. El impacto se mide contra esto.',
                '<b>Contexto</b>: tamaño, ubicación, regulaciones, clientes, temporadas críticas, dependencias.',
                '<b>Alcance</b>: qué parte de la infraestructura evalúas y qué queda fuera.',
                '<b>Tolerancia al riesgo</b>: el nivel más alto que la organización acepta sin actuar. Los riesgos Aceptados por encima de ese nivel se marcan en el reporte.',
            ],
            'preguntas' => [
                '¿Qué es lo peor que le podría pasar a la organización, desde su propio punto de vista?',
            ],
            'ojo'    => 'Haz un respaldo (Exportar) antes de entregar y antes de ejecutar <code>vagrant destroy</code>: tu trabajo vive dentro de la máquina virtual.',
        ],
    ];
    return $a[$clave];
}

function render_ayuda(string $clave): string
{
    $a = ayuda($clave);
    $li = fn(array $xs) => '<li>' . implode('</li><li>', $xs) . '</li>';
    $o  = '<div class="card ayuda-card mb-4" data-ayuda="' . h($clave) . '">';
    $o .= '<div class="card-header d-flex align-items-center gap-2 ayuda-toggle" role="button" tabindex="0" aria-expanded="true" aria-controls="ayuda-' . h($clave) . '">';
    $o .= '<i class="bi bi-book"></i><strong>Guía NIST SP 800-30 · ' . $a['titulo'] . '</strong>';
    $o .= '<span class="badge text-bg-light ms-auto d-none d-md-inline">' . $a['nist'] . '</span>';
    $o .= '<i class="bi bi-chevron-up ayuda-chevron"></i></div>';
    $o .= '<div class="card-body" id="ayuda-' . h($clave) . '"><p class="mb-3">' . $a['que'] . '</p><div class="row g-4">';
    $o .= '<div class="col-lg-7"><h6 class="text-uppercase small text-body-secondary">Cómo hacerlo</h6><ol class="mb-0">' . $li($a['pasos']) . '</ol></div>';
    $o .= '<div class="col-lg-5"><h6 class="text-uppercase small text-body-secondary">Preguntas para pensar</h6><ul class="mb-3">' . $li($a['preguntas']) . '</ul>';
    $o .= '<div class="alert alert-warning py-2 mb-0 small"><i class="bi bi-exclamation-triangle me-1"></i>' . $a['ojo'] . '</div></div>';
    $o .= '</div></div></div>';
    return $o;
}
