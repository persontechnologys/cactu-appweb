# CACTU — Instrucciones Maestras para Claude Code

> Este archivo contiene las reglas técnicas, arquitectónicas y de trabajo que deben seguirse al desarrollar, refactorizar, documentar y mantener el proyecto CACTU.

---

# 1. Identidad del proyecto

**Nombre:** CACTU

**Repositorio oficial:**

```text
https://github.com/persontechnology/cactu-appweb
```

**Rama principal:**

```text
main
```

**Aplicación web:**

```text
https://cactu.ec/
```

**Tecnología backend/web:**

```text
Laravel
PHP
Blade
JavaScript
HTML
CSS
```

**Aplicación móvil:**

```text
React Native
```

**Notificaciones móviles:**

```text
Firebase
```

**Servidor de producción:**

```text
cPanel compartido
```

**Procesamiento programado disponible:**

```text
Cron
Laravel Scheduler
```

---

# 2. Objetivo general

CACTU se encuentra actualmente funcionando y debe evolucionar progresivamente hacia una plataforma:

- segura;
- mantenible;
- modular;
- documentada;
- escalable;
- reutilizable;
- consistente;
- preparada para Web y React Native;
- con reglas de negocio centralizadas;
- con procesos asíncronos;
- con una UX/UI moderna;
- y con una arquitectura clara para futuros desarrolladores.

La evolución debe realizarse sobre el código existente.

## Regla fundamental

**NO reescribir CACTU desde cero.**

El enfoque obligatorio es:

```text
AUDITAR
   ↓
ENTENDER
   ↓
DOCUMENTAR
   ↓
PLANIFICAR
   ↓
REFACTORIZAR
   ↓
PROBAR
   ↓
DOCUMENTAR
   ↓
MEJORAR
```

---

# 3. Regla principal: primero comprender, después modificar

Claude Code no debe modificar código importante simplemente porque encuentre una forma aparentemente mejor de hacerlo.

Antes de realizar cambios estructurales debe comprender:

1. Qué hace actualmente la funcionalidad.
2. Quién la utiliza.
3. Qué rutas la ejecutan.
4. Qué Controller participa.
5. Qué Model participa.
6. Qué servicios intervienen.
7. Qué datos modifica.
8. Qué vistas la utilizan.
9. Qué endpoints API intervienen.
10. Si React Native utiliza esa funcionalidad.
11. Si existen procesos duplicados.
12. Qué comportamiento se espera actualmente.
13. Qué problemas existen.
14. Qué impacto tendrá el cambio.

Cuando exista incertidumbre, inspeccionar primero el código relacionado.

No asumir.

---

# 4. Fase 0: auditoría obligatoria

La primera tarea del proyecto es realizar una auditoría completa.

Durante esta fase:

**NO realizar refactorizaciones importantes.**

El objetivo es producir un mapa confiable de CACTU.

La auditoría debe identificar:

```text
Arquitectura
Módulos
Funcionalidades
Rutas Web
Rutas API
Controllers
Models
Migrations
Form Requests
Policies
Middleware
Services
Actions
Jobs
Events
Listeners
Notifications
Commands
Views
Components
JavaScript
CSS
Integraciones externas
Firebase
Email
PDF
Queue
Scheduler
Cron
Tests
```

También identificar:

```text
Código duplicado
Código ambiguo
Código muerto
Controllers demasiado grandes
Métodos demasiado grandes
Reglas de negocio duplicadas
Problemas de seguridad
Problemas de autorización
Problemas de validación
Problemas de UX/UI
Problemas de performance
Problemas de arquitectura
Deuda técnica
```

---

# 5. Prohibición de inventar arquitectura

Claude Code deberá trabajar con el código real del repositorio.

No asumir que CACTU utiliza:

- Services;
- Actions;
- Repositories;
- Events;
- Jobs;
- Policies;
- módulos;
- componentes;

hasta comprobarlo.

Si algo no existe, indicarlo como:

```text
No implementado actualmente
```

Si existe parcialmente:

```text
Implementación parcial
```

Si existe pero presenta problemas:

```text
Implementación existente con deuda técnica
```

---

# 6. Documentación obligatoria de la auditoría

Crear inicialmente:

```text
docs/
├── README.md
├── auditoria/
│   ├── arquitectura-actual.md
│   ├── modulos.md
│   ├── rutas-web.md
│   ├── rutas-api.md
│   ├── modelos.md
│   ├── vistas.md
│   ├── servicios-y-actions.md
│   ├── jobs-y-procesos.md
│   ├── integraciones.md
│   ├── seguridad.md
│   ├── problemas-detectados.md
│   └── deuda-tecnica.md
```

La estructura definitiva puede modificarse después de analizar el proyecto real.

---

# 7. No modificar durante la auditoría

Durante la Fase 0 no se deben realizar cambios funcionales importantes.

Se permiten únicamente modificaciones necesarias para:

- documentar;
- corregir archivos de documentación;
- agregar herramientas de análisis si fueron autorizadas;
- preparar pruebas o infraestructura de auditoría;
- registrar hallazgos.

No comenzar todavía con:

```text
Gran refactorización
Migraciones innecesarias
Cambios masivos de Controllers
Cambios masivos de API
Rediseño completo
Cambios de base de datos
Cambios de autenticación
```

Primero presentar el diagnóstico.

---

# 8. Informe de auditoría

Al finalizar la Fase 0, generar:

```text
docs/auditoria/informe-general.md
```

Debe contener:

```text
1. Resumen ejecutivo
2. Arquitectura actual
3. Tecnologías detectadas
4. Módulos encontrados
5. Flujo Web
6. Flujo API
7. Flujo React Native
8. Integraciones
9. Problemas de arquitectura
10. Problemas de seguridad
11. Problemas UX/UI
12. Problemas de performance
13. Código duplicado
14. Deuda técnica
15. Riesgos
16. Recomendaciones
17. Priorización
18. Roadmap propuesto
```

---

# 9. Nunca mezclar diagnóstico con implementación

Cuando se encuentre un problema:

```text
Problema
   ↓
Documentar
   ↓
Evaluar impacto
   ↓
Proponer solución
   ↓
Priorizar
   ↓
Implementar en fase correspondiente
```

No solucionar automáticamente todo lo encontrado durante la auditoría.

---

# 10. Arquitectura objetivo

La arquitectura objetivo debe procurar:

```text
                     CACTU
                       │
        ┌──────────────┼──────────────┐
        │              │              │
       WEB         WEB MÓVIL      REACT NATIVE
        │              │              │
        └──────────────┼──────────────┘
                       │
                      API
                       │
               Controllers
                       │
                 Form Requests
                       │
                Actions / Services
                       │
                 Regla de negocio
                       │
               Modelos / Persistencia
                       │
                 Base de datos
```

La interfaz de usuario no debe contener reglas de negocio críticas.

Los Controllers no deben concentrar reglas de negocio complejas.

---

# 11. Regla de oro de negocio

Una regla de negocio debe existir una sola vez.

Si una operación puede realizarse desde:

```text
Web
Web móvil
React Native
```

no debe implementarse tres veces.

Debe centralizarse en Laravel.

Ejemplo:

```text
Web
 │
 └── Controller
       │
       ▼
RegistrarOperacionAction
       ▲
       │
API
 │
 └── Controller
       ▲
       │
React Native
```

---

# 12. Controllers

Los Controllers deben ser delgados.

Responsabilidades principales:

```text
Recibir solicitud
Validar mediante Request
Autorizar
Invocar Action/Service
Preparar respuesta
```

Evitar Controllers que contengan:

```text
Consultas complejas
Reglas de negocio extensas
Procesos repetitivos
Generación compleja de PDF
Envío directo de múltiples servicios
Lógica Firebase
Procesos largos
Transacciones complejas
```

Estas responsabilidades deberán trasladarse a la capa correspondiente.

---

# 13. Form Requests

Las validaciones de entrada deberán realizarse preferentemente mediante Form Requests.

Ejemplo:

```text
app/Http/Requests/
```

o la estructura modular que posteriormente se determine.

Evitar concentrar validaciones complejas directamente en Controllers.

---

# 14. Actions

Utilizar Actions para operaciones concretas del negocio.

Ejemplos conceptuales:

```text
CrearUsuarioAction
ActualizarUsuarioAction
RegistrarOperacionAction
CancelarOperacionAction
GenerarDocumentoAction
RegistrarDispositivoAction
ProcesarNotificacionAction
```

Una Action debe representar una operación concreta.

Evitar Actions gigantes que terminen convirtiéndose en nuevos Controllers.

---

# 15. Services

Utilizar Services para responsabilidades reutilizables o integraciones complejas.

Ejemplos potenciales:

```text
PdfService
CorreoService
FirebaseService
NotificacionService
ArchivoService
```

No crear Services solamente para mover código de un Controller.

Cada Service debe tener una responsabilidad clara.

---

# 16. Jobs

Utilizar Jobs para procesos que no necesitan bloquear la petición HTTP.

Especialmente:

```text
Generación de PDF
Envío de correo
Notificaciones Firebase
Procesamiento masivo
Procesamiento de archivos
Tareas pesadas
```

Flujo esperado:

```text
Usuario
   ↓
Controller
   ↓
Action
   ↓
Guardar información
   ↓
Dispatch Job
   ↓
Respuesta inmediata
   ↓
Queue
   ↓
Procesamiento
```

---

# 17. cPanel y procesamiento en segundo plano

La producción utiliza cPanel compartido.

No asumir disponibilidad de:

```text
Supervisor
Redis
VPS
Docker
root
systemd
```

Primero verificar qué ofrece el hosting.

Investigar y documentar:

```text
Cron Jobs
PHP CLI
Artisan
Laravel Scheduler
Queue
Driver de cola disponible
Base de datos
Storage
Logs
```

La arquitectura final deberá ser compatible con el entorno real.

---

# 18. Scheduler

Analizar las tareas periódicas que puedan gestionarse mediante Laravel Scheduler.

Ejemplo:

```text
Procesar trabajos pendientes
Limpiar archivos temporales
Procesar notificaciones
Procesar correos
Tareas de mantenimiento
```

El mecanismo de ejecución deberá adaptarse a las posibilidades de cPanel.

---

# 19. Seguridad

La seguridad debe revisarse antes de considerar terminada cualquier fase importante.

Auditar:

```text
Autenticación
Autorización
Roles
Permisos
Policies
Gates
Middleware
Sesiones
Tokens
API
CSRF
Rate limiting
Validaciones
Archivos
Subidas
Descargas
IDOR
Mass Assignment
Exposición de información
Logs
Errores
```

---

# 20. Regla de autorización

Nunca confiar únicamente en la interfaz.

Que un botón no aparezca en la Web no significa que la operación esté protegida.

Toda operación sensible deberá tener autorización en backend.

Ejemplo:

```text
Usuario
   ↓
Request
   ↓
Authentication
   ↓
Authorization
   ↓
Action
```

---

# 21. React Native

La aplicación React Native es un cliente de CACTU.

No debe duplicar reglas de negocio que corresponden al backend.

El backend Laravel debe ser la fuente principal de las reglas de negocio.

La aplicación móvil debe encargarse principalmente de:

```text
Interfaz
Estado local
Navegación
Interacción
Consumo API
Almacenamiento local necesario
Notificaciones
Experiencia móvil
```

---

# 22. API como contrato

Toda modificación de API debe analizarse considerando React Native.

Antes de cambiar un endpoint revisar:

```text
¿Quién lo utiliza?
¿La aplicación móvil lo utiliza?
¿Qué versión móvil lo utiliza?
¿Qué parámetros espera?
¿Qué respuesta espera?
¿Qué campos consume?
¿Qué permisos necesita?
```

Nunca modificar una respuesta API solamente porque parezca más conveniente sin comprobar consumidores existentes.

---

# 23. Documentación API

Cada endpoint deberá documentarse con:

```text
Método HTTP
URL
Autenticación
Permisos
Headers
Parámetros
Request
Response
Código HTTP
Errores
Reglas de negocio
Ejemplo exitoso
Ejemplo de error
Impacto móvil
```

Ejemplo:

```text
POST /api/v1/operaciones
```

Headers:

```text
Authorization: Bearer TOKEN
Accept: application/json
Content-Type: application/json
```

Request:

```json
{
    "campo": "valor"
}
```

Response:

```json
{
    "success": true,
    "message": "Operación realizada correctamente",
    "data": {}
}
```

Los ejemplos definitivos deben generarse utilizando los endpoints reales de CACTU.

---

# 24. Documentación para desarrollador móvil

Crear:

```text
docs/movil/
```

Debe contener documentación suficiente para que un desarrollador React Native pueda trabajar sin tener que preguntar constantemente cómo funciona el backend.

Documentar:

```text
Arquitectura
URL API
Autenticación
Tokens
Usuarios
Roles
Permisos
Endpoints
Requests
Responses
Errores
Códigos HTTP
Subida de archivos
Descarga de documentos
Firebase
Tokens de dispositivos
Notificaciones
Registro de dispositivos
Logout
Sesiones
Manejo de errores
Flujos de negocio
Versionamiento
Cambios
```

---

# 25. Firebase

Firebase se utiliza para notificaciones móviles.

Auditar:

```text
Configuración
Credenciales
Tokens
Dispositivos
Usuarios
Registro
Actualización
Eliminación
Tokens inválidos
Múltiples dispositivos
Envíos individuales
Envíos múltiples
Errores
Reintentos
```

El backend debe poder identificar la relación:

```text
Usuario
   │
   ├── Dispositivo A
   │      └── Token Firebase
   │
   ├── Dispositivo B
   │      └── Token Firebase
   │
   └── Dispositivo C
          └── Token Firebase
```

---

# 26. Flujo de notificación

La arquitectura deberá procurar:

```text
Evento de negocio
      ↓
Action / Service
      ↓
Notification
      ↓
Job
      ↓
Firebase
      ↓
Dispositivo
      ↓
React Native
```

No bloquear operaciones importantes esperando respuestas de Firebase cuando no sea necesario.

---

# 27. PDF

Auditar completamente:

```text
Generación
Plantillas
Datos
Diseño
Almacenamiento
Nombres
Descarga
Seguridad
Regeneración
Errores
Performance
```

Cuando la generación sea pesada, evaluar procesamiento mediante Job.

---

# 28. Email

Auditar:

```text
Configuración SMTP
Mailables
Notifications
Plantillas
Destinatarios
Adjuntos
Errores
Reintentos
Logs
```

Cuando sea apropiado, enviar correos mediante Queue.

---

# 29. UX/UI

La aplicación web será modernizada utilizando el template **Limitless** como referencia visual.

La implementación debe:

- respetar la identidad CACTU;
- reutilizar componentes;
- evitar duplicación;
- mantener consistencia;
- ser responsive;
- priorizar accesibilidad;
- mantener claridad en formularios y operaciones.

No copiar ciegamente el template.

Primero analizar cómo está construido el frontend actual.

---

# 30. Análisis de vistas

Antes de crear una nueva vista revisar:

```text
¿Existe un layout?
¿Existe un componente?
¿Existe una tabla reutilizable?
¿Existe un modal?
¿Existe un formulario equivalente?
¿Existe un componente Blade?
¿Existe JavaScript reutilizable?
```

No duplicar estructuras existentes.

---

# 31. Código en español

El código nuevo deberá utilizar español cuando sea apropiado.

Ejemplos:

```text
CrearUsuarioAction
ActualizarUsuarioAction
RegistrarOperacionAction
NotificacionService
FirebaseService
GenerarDocumentoPdfJob
```

Variables:

```text
$usuario
$operacion
$documento
$notificacion
$dispositivo
```

Métodos:

```text
crear()
actualizar()
eliminar()
procesar()
generar()
enviar()
```

No traducir conceptos técnicos propios de Laravel o librerías cuando hacerlo perjudique la comprensión estándar:

```text
Controller
Model
Request
Job
Event
Listener
Middleware
Policy
Migration
```

---

# 32. Documentación en español

La documentación propia del proyecto debe escribirse en español.

Debe explicar:

```text
Qué
Por qué
Cómo
Dónde
Impacto
Pruebas
```

No limitarse a documentar solamente qué archivo se modificó.

---

# 33. Estructura documental

La estructura inicial propuesta es:

```text
docs/
│
├── README.md
│
├── auditoria/
│   ├── informe-general.md
│   ├── arquitectura-actual.md
│   ├── modulos.md
│   ├── rutas-web.md
│   ├── rutas-api.md
│   ├── modelos.md
│   ├── vistas.md
│   ├── servicios-y-actions.md
│   ├── jobs-y-procesos.md
│   ├── integraciones.md
│   ├── seguridad.md
│   ├── problemas-detectados.md
│   └── deuda-tecnica.md
│
├── arquitectura/
│   ├── arquitectura-general.md
│   ├── arquitectura-web.md
│   ├── arquitectura-api.md
│   ├── arquitectura-movil.md
│   └── flujo-datos.md
│
├── api/
│   ├── README.md
│   ├── autenticacion.md
│   ├── usuarios.md
│   ├── operaciones.md
│   ├── documentos.md
│   └── notificaciones.md
│
├── movil/
│   ├── README.md
│   ├── configuracion.md
│   ├── autenticacion.md
│   ├── api.md
│   ├── firebase.md
│   ├── notificaciones.md
│   └── cambios-requeridos.md
│
├── modulos/
│
├── seguridad/
│
├── ux-ui/
│
├── infraestructura/
│   ├── cpanel.md
│   ├── cron.md
│   ├── colas.md
│   ├── firebase.md
│   └── despliegue.md
│
├── fases/
│
└── cambios/
    ├── changelog.md
    └── decisiones-arquitectura.md
```

Esta estructura es inicial.

No crear archivos innecesarios si la auditoría determina que una estructura diferente es más adecuada.

---

# 34. Organización por módulos

No asumir una lista de módulos antes de auditar.

Primero determinar los dominios reales del negocio.

Una posible estructura futura podría ser:

```text
app/
└── Modulos/
    ├── Usuarios/
    ├── Autenticacion/
    ├── Operaciones/
    ├── Documentos/
    ├── Notificaciones/
    └── ...
```

Pero esta estructura solo deberá implementarse después de determinar que realmente beneficia al proyecto.

No convertir CACTU a una arquitectura modular únicamente por seguir una moda.

---

# 35. Reutilización

Antes de crear código nuevo:

```text
BUSCAR
   ↓
¿Ya existe?
   │
   ├── Sí → reutilizar / refactorizar
   │
   └── No → crear
```

Evitar duplicar:

```text
Queries
Validaciones
Reglas de negocio
Componentes
Servicios
Acciones
Respuestas API
JavaScript
```

---

# 36. Pruebas

Toda modificación importante deberá probarse.

Cuando corresponda crear o actualizar:

```text
Unit Tests
Feature Tests
API Tests
Authorization Tests
Validation Tests
```

Las pruebas deberán cubrir especialmente las Actions que representen reglas importantes del negocio.

---

# 37. Migraciones y base de datos

Antes de modificar estructura de base de datos:

1. Revisar modelos.
2. Revisar migraciones existentes.
3. Revisar relaciones.
4. Revisar consultas.
5. Revisar API.
6. Revisar aplicación móvil.
7. Revisar datos existentes.
8. Evaluar impacto en producción.

No modificar columnas o relaciones únicamente para mejorar nombres sin analizar el impacto.

---

# 38. Compatibilidad hacia atrás

Cuando una modificación pueda romper funcionalidades existentes, analizar:

```text
Web actual
Web móvil
React Native
API
Datos existentes
Integraciones
Producción
```

Si es necesario introducir una nueva versión de API:

```text
/api/v1/
```

deberá evaluarse antes de romper el contrato actual.

---

# 39. Git

No trabajar directamente sobre `main` para cambios importantes.

Utilizar ramas:

```text
feature/
fix/
refactor/
security/
ux/
docs/
```

Ejemplos:

```text
refactor/arquitectura-actions
security/auditoria-api
ux/rediseño-limitless
feature/notificaciones-firebase
docs/api-movil
```

---

# 40. Commits

Los commits deben representar cambios concretos.

Ejemplos:

```text
refactor: extraer lógica de operación a Action
fix: corregir autorización de usuario
security: validar acceso a documento
docs: documentar endpoint de autenticación
ux: adaptar tabla de usuarios a Limitless
feat: procesar PDF mediante Job
```

No crear commits genéricos como:

```text
cambios
actualización
arreglos
varios
final
```

---

# 41. Documentación de cambios

Cada fase deberá actualizar:

```text
docs/cambios/changelog.md
```

Registrar:

```text
Fecha
Fase
Cambio
Motivo
Archivos importantes
Impacto
Pruebas
```

---

# 42. Decisiones arquitectónicas

Las decisiones importantes deben registrarse en:

```text
docs/cambios/decisiones-arquitectura.md
```

Ejemplos:

```text
¿Por qué se creó una Action?
¿Por qué se utilizó un Job?
¿Por qué se mantuvo un endpoint?
¿Por qué se versionó la API?
¿Por qué se cambió una vista?
¿Por qué se centralizó Firebase?
```

---

# 43. Registro de problemas

Todo problema detectado debe poder registrarse con:

```text
ID:
Módulo:
Problema:
Tipo:
Severidad:
Archivos afectados:
Comportamiento actual:
Comportamiento esperado:
Solución propuesta:
Impacto Web:
Impacto API:
Impacto móvil:
Estado:
Fase:
```

Tipos:

```text
BUG
SEGURIDAD
UX/UI
ARQUITECTURA
PERFORMANCE
DOCUMENTACIÓN
DEUDA TÉCNICA
MEJORA
```

---

# 44. Prioridad

Clasificar problemas:

```text
CRÍTICO
ALTO
MEDIO
BAJO
```

Priorizar especialmente:

```text
Seguridad
Pérdida de datos
Errores de negocio
Problemas que rompen React Native
Problemas de autenticación
Problemas de autorización
```

---

# 45. Fases del proyecto

La evolución general seguirá aproximadamente:

```text
FASE 0
Auditoría
    ↓
FASE 1
Arquitectura
    ↓
FASE 2
Seguridad
    ↓
FASE 3
UX/UI
    ↓
FASE 4
Unificación Web/API
    ↓
FASE 5
API + React Native
    ↓
FASE 6
PDF + Email + Jobs
    ↓
FASE 7
Firebase + Notificaciones
    ↓
FASE 8
Performance + Calidad
```

Las fases podrán dividirse en subfases.

---

# 46. Regla para comenzar cada fase

Antes de implementar una fase:

```text
1. Leer documentación relacionada.
2. Revisar estado actual.
3. Revisar cambios anteriores.
4. Identificar archivos afectados.
5. Identificar impacto.
6. Crear plan.
7. Implementar.
8. Probar.
9. Documentar.
10. Actualizar changelog.
```

---

# 47. Criterio de finalización

Una fase no está terminada únicamente porque el código funcione.

Debe cumplirse:

```text
[ ] Código implementado
[ ] Código revisado
[ ] Pruebas realizadas
[ ] Seguridad revisada
[ ] Web comprobada
[ ] API comprobada
[ ] React Native evaluado
[ ] Documentación actualizada
[ ] Changelog actualizado
[ ] Problemas conocidos registrados
```

---

# 48. No hacer cambios masivos sin autorización

Claude Code no debe ejecutar automáticamente:

```text
Refactorización completa
Cambio masivo de nombres
Migración completa de arquitectura
Cambio de base de datos
Cambio masivo de API
Eliminación de código
Eliminación de endpoints
Eliminación de tablas
```

sin analizar previamente el impacto.

---

# 49. Regla de archivos existentes

Antes de crear un archivo nuevo:

```text
Buscar si ya existe funcionalidad equivalente.
```

Antes de eliminar un archivo:

```text
Buscar referencias.
Buscar imports.
Buscar rutas.
Buscar llamadas.
Buscar uso desde JavaScript.
Buscar uso desde React Native/API.
```

No eliminar código únicamente porque "parece no utilizarse".

---

# 50. Regla de producción

El entorno de producción no debe utilizarse como entorno de experimentación.

Toda modificación deberá probarse primero localmente.

Cuando corresponda:

```text
Local
   ↓
Pruebas
   ↓
Branch
   ↓
Revisión
   ↓
Merge
   ↓
Producción
```

---

# 51. Estado inicial del proyecto

Antes de comenzar cualquier fase de implementación registrar:

```text
Rama actual:
Commit actual:
Estado de Git:
Versión PHP:
Versión Laravel:
Dependencias:
Base de datos:
Estado de pruebas:
API:
Firebase:
Email:
PDF:
Queue:
Scheduler:
```

---

# 52. Restricción importante: no romper funcionalidades existentes

CACTU ya es un sistema funcional.

Por lo tanto, toda refactorización debe procurar:

```text
Mejorar sin romper.
```

Si se requiere cambiar comportamiento funcional, documentar:

```text
Comportamiento anterior
Comportamiento nuevo
Motivo
Impacto
```

---

# 53. Principio SOLID

Aplicar principios SOLID cuando aporten claridad y mantenibilidad.

Especialmente:

```text
Single Responsibility
Open/Closed
Dependency Inversion
```

No aplicar patrones solamente por cumplir una regla.

El código debe ser simple de comprender.

---

# 54. Código ambiguo

Cuando se encuentre código ambiguo:

```text
No asumir su propósito.
```

Investigar:

```text
Quién lo llama.
Qué datos recibe.
Qué devuelve.
Qué efectos secundarios tiene.
Qué funcionalidades dependen de él.
```

Después documentar y refactorizar cuando corresponda.

---

# 55. Calidad del código

El código nuevo debe priorizar:

```text
Claridad
Legibilidad
Responsabilidad única
Reutilización
Testabilidad
Seguridad
Mantenibilidad
```

Evitar:

```text
Métodos gigantes
Controllers gigantes
Duplicación
Variables ambiguas
Funciones con múltiples responsabilidades
Código muerto
Magic numbers
Magic strings
Consultas innecesariamente complejas
```

---

# 56. Regla de simplicidad

No sobrearquitecturar.

La solución más compleja no es necesariamente la mejor.

Antes de crear:

```text
Repository
Service
Factory
Interface
Trait
Event
Listener
Job
Action
```

justificar si realmente es necesario.

---

# 57. Regla de documentación

Cuando una modificación sea suficientemente importante como para que otro desarrollador tenga que entenderla, debe documentarse.

La documentación debe responder:

```text
¿Qué hace?
¿Por qué existe?
¿Cómo funciona?
¿Cómo se utiliza?
¿Qué depende de ella?
```

---

# 58. Idioma

El idioma principal del proyecto será:

```text
Español
```

La documentación debe escribirse en español.

El código nuevo deberá utilizar nombres en español cuando resulte apropiado.

Los nombres técnicos propios de frameworks, paquetes y estándares pueden conservarse en inglés.

---

# 59. Comunicación de Claude Code

Cuando termine una tarea deberá informar:

```text
## Qué se hizo

## Por qué se hizo

## Archivos creados

## Archivos modificados

## Archivos eliminados

## Pruebas realizadas

## Impacto Web

## Impacto API

## Impacto React Native

## Documentación actualizada

## Problemas encontrados

## Pendientes

## Próximo paso recomendado
```

No responder únicamente:

```text
"Listo."
```

---

# 60. Primera instrucción al iniciar el proyecto

Al iniciar una nueva sesión de trabajo sobre CACTU, primero:

```text
1. Leer CLAUDE.md.
2. Verificar rama Git.
3. Verificar estado del repositorio.
4. Leer documentación relevante.
5. Inspeccionar código relacionado.
6. Comprender contexto.
7. Crear plan.
8. Ejecutar cambios.
9. Probar.
10. Documentar.
```

---

# 61. Primera misión de Claude Code

La primera misión NO es refactorizar.

La primera misión es:

> **AUDITAR CACTU COMPLETAMENTE Y GENERAR EL MAPA TÉCNICO DEL SISTEMA ACTUAL.**

Debe inspeccionar el repositorio y producir:

```text
1. Arquitectura actual
2. Módulos
3. Funcionalidades
4. Rutas Web
5. Rutas API
6. Controllers
7. Models
8. Requests
9. Policies
10. Middleware
11. Services
12. Actions
13. Jobs
14. Events
15. Listeners
16. Notifications
17. Views
18. Components
19. JavaScript
20. Firebase
21. Email
22. PDF
23. Queue
24. Scheduler
25. Cron
26. Tests
27. Problemas de seguridad
28. Problemas arquitectónicos
29. Problemas UX/UI
30. Deuda técnica
31. Código duplicado
32. Dependencias críticas
33. Dependencias entre Web y React Native
```

---

# 62. Resultado obligatorio de la Fase 0

Claude Code deberá producir:

```text
docs/auditoria/informe-general.md
```

y un roadmap:

```text
docs/fases/roadmap.md
```

El roadmap deberá priorizar:

```text
CRÍTICO
ALTO
MEDIO
BAJO
```

y separar:

```text
Correcciones
Refactorizaciones
Seguridad
UX/UI
API
React Native
PDF
Email
Firebase
Queue
Cron
Performance
Documentación
```

---

# 63. Regla final

CACTU debe evolucionar como una plataforma y no como una colección de funcionalidades independientes.

La arquitectura debe mantener esta idea:

```text
                  CACTU
                    │
       ┌────────────┼────────────┐
       │            │            │
      WEB       WEB MÓVIL    REACT NATIVE
       │            │            │
       └────────────┼────────────┘
                    │
                  API
                    │
              Laravel Backend
                    │
            Actions / Services
                    │
             Reglas de negocio
                    │
              Base de datos
                    │
        ┌───────────┼───────────┐
        │           │           │
       PDF        Email      Firebase
        │           │           │
        └───────────┼───────────┘
                    │
               Jobs / Queue
                    │
                Scheduler
                    │
                  Cron
                    │
                  cPanel
```

**Una regla de negocio debe existir una sola vez.**

**Web y React Native son clientes diferentes del mismo sistema.**

**La API es un contrato y no debe romperse sin analizar sus consumidores.**

**Primero comprender, después modificar.**

**Primero documentar, después refactorizar.**

**Cada cambio importante debe poder probarse y explicarse.**