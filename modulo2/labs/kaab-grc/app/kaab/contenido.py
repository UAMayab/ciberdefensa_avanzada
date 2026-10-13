"""Textos didácticos de la app. Ninguna respuesta de un ejercicio del estudiante vive aquí.

Los conceptos siguen la Unidad 2 y la Unidad 3 de sources/NIST_Mod2.md. El
ejemplo resuelto usa un escenario genérico (phishing) y no las debilidades
plantadas en Kaab, que el estudiante debe descubrir.
"""

DIAGNOSTICO = [
    {
        "id": "d1",
        "pregunta": "¿Cuál describe mejor una fuente de amenaza?",
        "opciones": [
            "Un correo de phishing concreto",
            "Quién o qué puede causar daño, por ejemplo un grupo criminal o una falla de energía",
            "La vulnerabilidad que se explota",
            "El monto de la pérdida",
        ],
        "correcta": 1,
    },
    {
        "id": "d2",
        "pregunta": "¿Qué es una vulnerabilidad?",
        "opciones": [
            "Una amenaza que todavía no ocurre",
            "Una debilidad que un evento de amenaza podría aprovechar",
            "El costo de un incidente",
            "La respuesta elegida para un riesgo",
        ],
        "correcta": 1,
    },
    {
        "id": "d3",
        "pregunta": "Según SP 800-30, ¿cómo se determina el riesgo?",
        "opciones": [
            "Contando el número de vulnerabilidades",
            "Combinando la probabilidad con el impacto",
            "Con la opinión de la dirección",
            "Sumando los costos de los controles",
        ],
        "correcta": 1,
    },
    {
        "id": "d4",
        "pregunta": "¿Cuáles son las cuatro componentes de la gestión de riesgos en SP 800-39?",
        "opciones": [
            "Identificar, medir, reportar y cerrar",
            "Enmarcar, evaluar, responder y monitorear",
            "Prevenir, detectar, responder y recuperar",
            "Planear, hacer, verificar y actuar",
        ],
        "correcta": 1,
    },
    {
        "id": "d5",
        "pregunta": "Aceptar un riesgo significa que…",
        "opciones": [
            "Nadie debe volver a revisarlo",
            "Se decide conservarlo porque está dentro de la tolerancia de la organización",
            "Ya no existe",
            "Se traslada a un seguro",
        ],
        "correcta": 1,
    },
    {
        "id": "d6",
        "pregunta": "Una contramedida sirve para…",
        "opciones": [
            "Reducir la probabilidad o el impacto de un riesgo",
            "Reducir el tamaño de la organización",
            "Eliminar la necesidad de registrar riesgos",
            "Sustituir al dueño del riesgo",
        ],
        "correcta": 0,
    },
    {
        "id": "d7",
        "pregunta": "El riesgo residual es…",
        "opciones": [
            "El riesgo antes de aplicar cualquier control",
            "El riesgo que queda después de aplicar los controles",
            "El riesgo que hereda un proveedor",
            "El riesgo que se asigna a la aseguradora",
        ],
        "correcta": 1,
    },
    {
        "id": "d8",
        "pregunta": "¿Por qué conviene registrar los supuestos de una evaluación?",
        "opciones": [
            "Para que el informe sea más largo",
            "Para que el resultado se pueda revisar, cuestionar y repetir",
            "Para no tener que pedir datos a las áreas",
            "Porque siempre es un requisito de la norma",
        ],
        "correcta": 1,
    },
]

CLASIFICACION = [
    {
        "texto": "Un grupo criminal compra credenciales de clientes en foros clandestinos.",
        "categoria": "Fuente de amenaza",
    },
    {
        "texto": "Un corte de luz de más de seis horas en el centro de datos.",
        "categoria": "Evento de amenaza",
    },
    {
        "texto": "El panel de administración acepta la contraseña de fábrica.",
        "categoria": "Vulnerabilidad",
    },
    {
        "texto": "Los pedidos se duplican en diciembre y el equipo de TI ya trabaja al límite.",
        "categoria": "Condición predisponente",
    },
]
CATEGORIAS_CLASIFICACION = ["Fuente de amenaza", "Evento de amenaza", "Vulnerabilidad", "Condición predisponente"]

VOCABULARIO = [
    ("Fuente de amenaza", "Quién o qué puede causar daño. Si es adversaria, tiene capacidad, intención y orientación."),
    ("Evento de amenaza", "Lo que la fuente podría hacer: un phishing, un corte de luz, un borrado de datos."),
    ("Vulnerabilidad", "Debilidad que un evento podría aprovechar."),
    ("Condición predisponente", "Circunstancia que aumenta la probabilidad o la gravedad, aunque no sea una debilidad por sí misma."),
    ("Probabilidad", "Qué tan probable es que un evento ocurra y cause impacto adverso."),
    ("Impacto", "Daño para la organización, sus activos, personas u otras organizaciones si el evento ocurre."),
    ("Riesgo", "Combinación de probabilidad e impacto (SP 800-30 Rev. 1)."),
]

RESPUESTAS_RIESGO = [
    ("Aceptar", "El riesgo está dentro de la tolerancia y otra respuesta no se justifica."),
    ("Evitar", "Se elimina la actividad o el activo que lo genera."),
    ("Mitigar", "Se aplican controles que reducen la probabilidad o el impacto."),
    ("Compartir", "Parte del riesgo se reparte, por ejemplo con un contrato."),
    ("Transferir", "El riesgo pasa a un tercero, por ejemplo mediante un seguro."),
]

TIERS_TEXTO = [
    ("1 · Organización", "¿Qué riesgo es aceptable para toda la empresa? Asigna recursos y fija la tolerancia."),
    ("2 · Proceso o misión", "¿Qué procesos de negocio importan más y qué riesgos cargan?"),
    ("3 · Sistema de información", "¿Qué controles protegen este sistema y cuál es el riesgo residual?"),
]

EJEMPLO = {
    "activo": "Correo electrónico de la dirección de finanzas",
    "fuente": {
        "descripcion": "Grupo criminal que envía correos falsos a empresas del sector",
        "tipo": "Adversarial", "capacidad": "Alta", "intencion": "Alta", "orientacion": "Sí",
    },
    "evento": {"descripcion": "Un correo que imita a un distribuidor pide cambiar la cuenta bancaria de pago."},
    "vulnerabilidad": {
        "descripcion": "Las cuentas de correo no exigen un segundo factor de autenticación.",
        "condicion_predisponente": "Los pagos a distribuidores se hacen por transferencia sin confirmación telefónica.",
        "evidencia": "Configuración revisada: el inicio de sesión pide solo contraseña.",
    },
    "probabilidad": {"ocurrencia": "Alto", "impacto_adverso": "Moderado"},
    "impacto": {
        "nivel": "Moderado",
        "monto_mxn": 900000,
        "justificacion": "Un pago de 900 000 MXN desviado a una cuenta falsa, más costos de recuperación.",
    },
    "registro": {
        "dueno": "Dirección de finanzas",
        "respuesta": "Mitigar",
        "contramedida": "Exigir segundo factor en el correo y confirmación telefónica antes de cambiar cuentas bancarias.",
        "estado": "En tratamiento",
        "tier": "2 · Proceso o misión",
    },
}
