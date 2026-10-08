# Miniworkshops — especialización en herramientas

> **Certificación en Ciberdefensa Avanzada**
> Universidad Anáhuac Mayab

Las actividades `A.1`–`A.8` de cada módulo persiguen que entiendas **qué te
entrega** una herramienta y **en qué caso profesional se usa**. Rinden 40–90
minutos y se califican.

Estos miniworkshops son otra cosa. Cada serie toma **una sola herramienta** y
la recorre de cero a experto en tres episodios de complejidad creciente, hasta
el punto en que puedes extenderla. No se califican, no tienen fecha de entrega
y se hacen al ritmo de cada quien.

| Serie | Herramienta | Episodios | Laboratorio |
|---|---|---|---|
| [Nmap de cero a experto](nmap/) | Nmap 7.94 + NSE | 3 + apéndice de tcpdump | 2 VMs Alpine (Vagrant) |

Cada serie es autocontenida: trae su propio laboratorio, sus episodios y el
material de apoyo que necesite. No hace falta haber cursado ningún módulo
concreto para empezar una.

## Cómo está construida una serie

```
<herramienta>/
├── README.md        portada: laboratorio, topología, credenciales, uso ético
├── Vagrantfile      el entorno, levantado con un `vagrant up`
├── provision/       aprovisionamiento de las VMs
├── episodios/       los tres episodios, en orden
├── apendices/       material transversal que se consulta a trozos
├── nse/ (u otros)   artefactos que el estudiante construye
└── web/             apoyo visual, si la herramienta lo pide
```

**Todo lo que aparece en un bloque de salida de un episodio procede de una
ejecución real contra el laboratorio de esa serie.** Cuando una herramienta
falla, se documenta el fallo en lugar de esconderlo: aprender a diagnosticar es
parte del objetivo.

## Uso ético

Los laboratorios de estas series son **deliberadamente vulnerables** y de uso
exclusivo educativo en redes aisladas. Nunca los expongas a una red de
producción ni a Internet, y nunca ejecutes contra sistemas de terceros lo que
aprendas aquí sin autorización explícita y por escrito.
