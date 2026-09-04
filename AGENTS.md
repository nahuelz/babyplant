# AGENTS.md - Guía para Agentes de IA

> **Última actualización:** Septiembre 2026  
> **Propósito:** Instrucciones y convenciones para agentes de IA que trabajen sobre este repositorio.

---

## 📋 Tabla de Contenidos

1. [Descripción General del Proyecto](#descripción-general-del-proyecto)
2. [Stack Tecnológico](#stack-tecnológico)
3. [Arquitectura del Proyecto](#arquitectura-del-proyecto)
4. [Convenciones de Nombres](#convenciones-de-nombres)
5. [Entidades y Base de Datos](#entidades-y-base-de-datos)
6. [Controladores y Routing](#controladores-y-routing)
7. [Frontend y Templates](#frontend-y-templates)
8. [Servicios](#servicios)
9. [Sistema de Permisos](#sistema-de-permisos)
10. [Docker e Infraestructura](#docker-e-infraestructura)
11. [Configuración y Variables de Entorno](#configuración-y-variables-de-entorno)
12. [Comandos Útiles](#comandos-útiles)
13. [Testing](#testing)
14. [Reglas Git](#reglas-git)
15. [⛔ RESTRICCIONES CRÍTICAS](#-restricciones-críticas)
16. [✅ Checklist Pre-Modificación](#-checklist-pre-modificación)

---

## Descripción General del Proyecto

**Babyplant** es un sistema de gestión integral para viveros/producción agrícola desarrollado en Symfony. Gestiona:

- Pedidos de clientes
- Control de stock y bandejas
- Siembras y producción
- Entregas y devoluciones
- Facturación (integración AFIP/ARCA)
- Gestión de empleados y usuarios
- Control de cuentas corrientes
- Auditoría completa de operaciones

El sistema tiene múltiples roles y permisos, con auditoría automática de todas las operaciones.

---

## Stack Tecnológico

### Backend
- **Framework:** Symfony 5.4 (PHP 8.1+)
- **ORM:** Doctrine ORM 2.12 con anotaciones
- **Base de datos:** MariaDB 10.6
- **Autenticación:** Symfony Security + Guard Authenticator
- **Extensiones:**
  - Gedmo Doctrine Extensions (Timestampable, SoftDeleteable)
  - Stof Doctrine Extensions Bundle
  - VichUploaderBundle (upload de archivos)
  - KnpTimeBundle
  - Symfonycasts ResetPasswordBundle y VerifyEmailBundle

### Frontend
- **Templating:** Twig 3.x
- **JavaScript:** Vanilla JS + jQuery
- **Tablas:** DataTables (server-side processing)
- **Selects:** Select2
- **Theme:** Bootstrap-based custom theme
- **PDF:** MPDF + TCPDF

### Infraestructura
- **Containerización:** Docker + Docker Compose
- **Web Server:** Apache 2 (en contenedor PHP 8.1-Apache)
- **Base de datos:** MariaDB 10.6 (contenedor)
- **PhpMyAdmin:** Disponible en desarrollo

### Librerías Importantes
- `afipsdk/afip.php`: Integración con AFIP
- `mpdf/mpdf`: Generación de PDFs
- `phpoffice/phpspreadsheet`: Exportación a Excel

---

## Arquitectura del Proyecto

### Patrón Arquitectónico

El proyecto sigue **MVC tradicional de Symfony** con una arquitectura centralizada:

```
┌─────────────────┐
│   Controller    │ ← Todos heredan de BaseController
│  (60+ files)    │
└────────┬────────┘
         │
    ┌────▼─────┐
    │  Base    │ ← Funcionalidad común: CRUD, filtros, paginación
    │Controller│
    └────┬─────┘
         │
    ┌────▼────┐
    │ Service │ ← Lógica de negocio
    └────┬────┘
         │
    ┌────▼────┐
    │ Entity  │ ← ORM Doctrine con Traits
    └─────────┘
```

### Estructura de Directorios

```
babyplant/
├── bin/                          # Ejecutables (console)
├── config/                       # Configuración Symfony
│   ├── packages/                 # Configuración de bundles
│   ├── routes/                   # Rutas (principalmente annotations)
│   └── services.yaml             # Servicios
├── migrations/                   # Migraciones Doctrine (gitignored)
├── public/                       # Web root
│   ├── css/                      # Estilos
│   ├── js/                       # JavaScript
│   │   └── app/                  # JS por módulo
│   ├── plugins/                  # Plugins de terceros
│   ├── theme/                    # Theme custom
│   ├── uploads/                  # Archivos subidos
│   ├── libraries/                # TCPDF y otras libs
│   └── index.php                 # Front controller
├── src/
│   ├── Controller/               # 60+ controladores
│   ├── Entity/                   # Entidades Doctrine
│   │   ├── Constants/            # Clases con constantes
│   │   ├── Enum/                 # Enums PHP 8.1
│   │   └── Traits/               # Traits reutilizables
│   ├── Form/                     # Symfony Forms
│   ├── Repository/               # Repositorios Doctrine
│   ├── Service/                  # Lógica de negocio
│   ├── EventListener/            # Event Listeners
│   ├── Security/                 # Autenticación
│   ├── DQL/                      # Funciones DQL personalizadas
│   ├── SQLViews/                 # Vistas y stored procedures SQL
│   ├── Maker/                    # Maker personalizado (MakeCrud)
│   ├── Namer/                    # Namers para VichUploader
│   ├── Twig/                     # Extensiones Twig
│   ├── Util/                     # Utilidades
│   └── Kernel.php
├── templates/                    # Templates Twig
│   ├── base.html.twig            # Template base
│   ├── {entity}/                 # Templates por entidad
│   │   ├── index.html.twig
│   │   ├── _form.html.twig
│   │   ├── edit.html.twig
│   │   └── ...
│   └── ...
├── tests/                        # Tests PHPUnit
├── var/                          # Cache y logs (gitignored)
├── vendor/                       # Dependencias Composer (gitignored)
├── .env                          # Variables de entorno (gitignored)
├── composer.json
├── Dockerfile
├── docker-compose.dev.yml        # Docker para desarrollo
├── docker-compose.prod.yml       # Docker para producción
└── phpunit.xml.dist
```

---

## Convenciones de Nombres

### ⚠️ IMPORTANTE: Estas son convenciones REALES encontradas en el código, NO inventadas.

### Entidades (PHP)

```php
// Clase de entidad
class Pedido {}              // PascalCase, singular
class PedidoProducto {}      // PascalCase compuesto

// Propiedades
private $fechaCreacion;      // camelCase
private $idUsuarioCreacion;  // camelCase
```

### Tablas y Campos (Base de Datos)

```php
/**
 * @ORM\Table(name="pedido")           // snake_case, singular
 * @ORM\Column(name="fecha_creacion")  // snake_case
 * @ORM\JoinColumn(name="id_usuario_creacion", referencedColumnName="id")
 */
```

**Convención BD:**
- Tablas: `snake_case` singular
- Columnas: `snake_case`
- FK: `id_{tabla_referenciada}`
- Tablas de relación M2M: `{tabla1}_{tabla2}` (ej: `usuario_grupo`)

### Controladores

```php
namespace App\Controller;

class PedidoController extends BaseController  // {Entity}Controller
{
    /**
     * @Route("/pedido", name="pedido_index")
     */
    public function index(): array {}
    
    /**
     * @Route("/pedido/index_table/", name="pedido_table")
     */
    public function indexTableAction(Request $request): Response {}
    
    /**
     * @Route("/pedido/new", name="pedido_new")
     */
    public function new(): array {}
    
    /**
     * @Route("/pedido/{id}/edit", name="pedido_edit")
     */
    public function edit(Request $request, int $id): array {}
}
```

**Convención de rutas:**
- Path: `/{entity}` minúscula
- Name: `{entity}_{action}` minúscula con guión bajo
- Métodos: `index()`, `indexTableAction()`, `new()`, `create()`, `edit()`, `update()`, `delete()`

### Formularios

```php
namespace App\Form;

class PedidoType extends AbstractType  // {Entity}Type
{
    public function buildForm(FormBuilderInterface $builder, array $options) {}
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Pedido::class,
        ]);
    }
}
```

### Templates

```
templates/
└── pedido/                        # Minúscula, nombre de la entidad
    ├── index.html.twig            # Vista principal
    ├── index_table.html.twig      # Tabla (DataTables)
    ├── _form.html.twig            # Formulario parcial
    ├── edit.html.twig             # Edición
    ├── show.html.twig             # Detalle
    ├── _delete_form.html.twig     # Formulario de borrado
    └── filtros.html.twig          # Filtros específicos
```

### JavaScript

```
public/js/app/
└── pedido/                        # Minúscula
    ├── index.js                   # Vista index
    ├── new.js                     # Vista new/create
    └── edit.js                    # Vista edit
```

### Servicios

```php
namespace App\Service;

class PedidoService {}          // {Entity}Service o {Funcionalidad}Service
class SelectService {}
class EntityManagementGuesser {}
```

### Repositorios

```php
namespace App\Repository;

class PedidoRepository extends ServiceEntityRepository  // {Entity}Repository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pedido::class);
    }
}
```

### Constantes

```php
namespace App\Entity\Constants;

class ConstanteTipoUsuario  // Constante{Concepto}
{
    const CLIENTE = 1;
    const TECNICO = 2;
}
```

### Enums PHP 8.1

```php
namespace App\Entity\Enum;

enum CondicionIva: string
{
    case RESPONSABLE_INSCRIPTO = 'RESPONSABLE_INSCRIPTO';
    case MONOTRIBUTISTA = 'MONOTRIBUTISTA';
    
    public function getDescripcion(): string {}
    public static function getChoices(): array {}
}
```

---

## Entidades y Base de Datos

### Traits Obligatorios

#### 1. Trait `Auditoria` (CASI TODAS LAS ENTIDADES)

```php
use App\Entity\Traits\Auditoria;

/**
 * @ORM\Entity(repositoryClass=PedidoRepository::class)
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class Pedido
{
    use Auditoria;  // ← OBLIGATORIO
}
```

**Campos que agrega:**
- `usuarioCreacion` (ManyToOne → Usuario)
- `usuarioUltimaModificacion` (ManyToOne → Usuario)
- `fechaCreacion` (DateTime, auto con Gedmo)
- `fechaUltimaModificacion` (DateTime, auto con Gedmo)
- `fechaBaja` (DateTime, nullable)

**⚠️ IMPORTANTE:** El `AuditoriaListener` setea automáticamente estos campos. NO los setees manualmente.

#### 2. Trait `Habilitado` (para entidades habilitables)

```php
use App\Entity\Traits\Habilitado;

class Usuario
{
    use Habilitado;
}
```

**Campos que agrega:**
- `habilitado` (boolean)
- `usuarioDeshabilito` (ManyToOne → Usuario)

### Soft Delete (OBLIGATORIO)

**TODAS** las entidades usan soft delete mediante Gedmo:

```php
/**
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class MiEntidad
{
    use Auditoria;  // Ya incluye $fechaBaja
}
```

**⚠️ NUNCA uses `$em->remove()` directamente.** El sistema automáticamente marca `fechaBaja` al eliminar.

### Configuración Doctrine

```yaml
# config/packages/doctrine.yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
    orm:
        auto_generate_proxy_classes: true
        naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
        auto_mapping: true
        filters:
            softdeleteable:
                class: Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter
                enabled: true
```

### Funciones DQL Personalizadas

El proyecto tiene funciones DQL custom definidas en `src/DQL/`:

```yaml
dql:
    string_functions:
        IFNULL: App\DQL\IfNull
        DAY: App\DQL\Day
        MONTH: App\DQL\Month
        YEAR: App\DQL\Year
        GROUP_CONCAT: App\DQL\GroupConcat
        DATE: App\DQL\Date
        REPLACE: App\DQL\Replace
        LPAD: App\DQL\Lpad
        IF: App\DQL\IfElse
    datetime_functions:
        DATE_FORMAT: App\DQL\DateFormat
```

Puedes usarlas en queries DQL:

```php
$query = $em->createQuery('
    SELECT IFNULL(p.nombre, "Sin nombre") 
    FROM App\Entity\Pedido p
');
```

### Estructura Típica de una Entidad

```php
<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use App\Repository\PedidoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="pedido")
 * @ORM\Entity(repositoryClass=PedidoRepository::class)
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class Pedido
{
    use Auditoria;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity=Usuario::class)
     * @ORM\JoinColumn(name="id_cliente", referencedColumnName="id")
     */
    private $cliente;

    /**
     * @ORM\OneToMany(targetEntity=PedidoProducto::class, mappedBy="pedido", cascade={"all"})
     * @ORM\OrderBy({"id" = "DESC"})
     */
    private $pedidosProductos;

    public function __construct()
    {
        $this->pedidosProductos = new ArrayCollection();
    }

    public function __toString(): string
    {
        return 'Pedido N° ' . $this->getId();
    }

    // Getters y setters...
}
```

### Enums PHP 8.1

El proyecto usa enums nativos de PHP 8.1:

```php
namespace App\Entity\Enum;

enum CondicionIva: string
{
    case RESPONSABLE_INSCRIPTO = 'RESPONSABLE_INSCRIPTO';
    case MONOTRIBUTISTA = 'MONOTRIBUTISTA';
    case EXENTO = 'EXENTO';
    case CONSUMIDOR_FINAL = 'CONSUMIDOR_FINAL';

    public function getDescripcion(): string
    {
        return match ($this) {
            self::RESPONSABLE_INSCRIPTO => 'Responsable Inscripto',
            self::MONOTRIBUTISTA => 'Monotributista',
            self::EXENTO => 'Exento',
            self::CONSUMIDOR_FINAL => 'Consumidor Final',
        };
    }

    public static function getChoices(): array
    {
        return [
            'Responsable Inscripto' => self::RESPONSABLE_INSCRIPTO,
            'Monotributista' => self::MONOTRIBUTISTA,
            'Exento' => self::EXENTO,
            'Consumidor Final' => self::CONSUMIDOR_FINAL,
        ];
    }
}
```

**Uso en entidades:**

```php
/**
 * @ORM\Column(name="condicion_iva", type="string", enumType=CondicionIva::class)
 */
private ?CondicionIva $condicionIva = CondicionIva::CONSUMIDOR_FINAL;
```

---

## Controladores y Routing

### BaseController: El Corazón del Sistema

**TODOS los controladores deben extender `BaseController`**, que proporciona:

#### Métodos Principales:

```php
class MiController extends BaseController
{
    // Renderiza vista index
    public function index(): array
    {
        return $this->baseIndexAction([
            // parámetros extra
        ]);
    }

    // Maneja DataTables con paginación server-side
    public function indexTableAction(Request $request): Response
    {
        return $this->baseIndexTableAction(
            $request,
            $columnDefinition,    // Definición de columnas
            $entityTableParam,    // null para usar entidad por defecto
            $queryTypeParam,      // ConstanteTipoConsulta::TABLE|VIEW|STORE_PROCEDURE
            $rsmParam,           // ResultSetMapping o null
            $renderPageParam,    // Template o null
            $extraParam,         // Parámetros extra
            $storedParameters,   // Para stored procedures
            $executeAditionalWhere  // false por defecto
        );
    }

    // Renderiza formulario nuevo
    public function new(): array
    {
        return $this->baseNewAction();
    }

    // Muestra detalle
    public function show(int $id): array
    {
        return $this->baseShowAction($id);
    }
}
```

#### Métodos Protegidos Útiles:

```php
// Nombre de la entidad
$entityName = $this->getEntityName();              // "Pedido"
$entityFullName = $this->getEntityFullName();      // "App\Entity\Pedido" o con namespace
$baseEntityName = $this->getBaseEntityName();      // Nombre completo con namespace

// Servicios inyectados
$selectService = $this->getSelectService();
$estadoService = $this->estadoService;
$printService = $this->printService;

// Doctrine
$em = $this->doctrine->getManager();

// Usuario actual
$user = $this->getUser();

// Rutas
$indexPath = $this->getIndexPath();
$urlPrefix = $this->getURLPrefix();
```

### Ejemplo Completo de Controlador

```php
<?php

namespace App\Controller;

use App\Entity\Pedido;
use App\Form\PedidoType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/pedido")
 */
class PedidoController extends BaseController
{
    /**
     * @Route("/", name="pedido_index", methods={"GET"})
     * @Template("pedido/index.html.twig")
     * @IsGranted("ROLE_PEDIDO")
     */
    public function index(): array
    {
        $clienteSelect = $this->getSelectService()->getClienteFilter();
        
        return array_merge($this->baseIndexAction(), [
            'clienteSelect' => $clienteSelect,
            'page_title' => 'Pedidos'
        ]);
    }

    /**
     * @Route("/index_table/", name="pedido_table", methods={"GET|POST"})
     * @IsGranted("ROLE_PEDIDO")
     */
    public function indexTableAction(Request $request): Response
    {
        $columnDefinition = [
            0 => ['field' => 'id', 'type' => ConstanteTipoFiltro::SELECT],
            1 => ['field' => 'cliente.nombre', 'type' => ConstanteTipoFiltro::STRING],
            // ...
        ];

        return $this->baseIndexTableAction($request, $columnDefinition);
    }

    /**
     * @Route("/new", name="pedido_new", methods={"GET"})
     * @Template("pedido/edit.html.twig")
     * @IsGranted("ROLE_PEDIDO")
     */
    public function new(): array
    {
        return $this->baseNewAction();
    }

    /**
     * @Route("/create", name="pedido_create", methods={"POST"})
     * @IsGranted("ROLE_PEDIDO")
     */
    public function create(Request $request): Response
    {
        $em = $this->doctrine->getManager();
        
        $entity = new Pedido();
        $form = $this->createForm(PedidoType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirectToRoute('pedido_index');
        }

        return $this->render('pedido/edit.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }
}
```

### Anotaciones Obligatorias

```php
/**
 * @Route("/ruta")                    // OBLIGATORIO: Define la ruta
 * @Template("template.html.twig")    // OPCIONAL: Auto-render (si retorna array)
 * @IsGranted("ROLE_X")                // IMPORTANTE: Seguridad
 */
```

### Tipos de Consulta (ConstanteTipoConsulta)

```php
// Tabla directa (DQL)
ConstanteTipoConsulta::TABLE

// Vista SQL nativa
ConstanteTipoConsulta::VIEW

// Stored Procedure
ConstanteTipoConsulta::STORE_PROCEDURE
```

---

## Frontend y Templates

### Estructura de Templates

Todos los templates extienden `base.html.twig`:

```twig
{% extends 'base.html.twig' %}

{% block stylesheets %}
    {{ parent() }}
    {# CSS adicional #}
{% endblock %}

{% block body %}
    {# Contenido #}
{% endblock %}

{% block javascripts %}
    {{ parent() }}
    {# JS adicional #}
{% endblock %}
```

### Templates Comunes por Entidad

```
templates/{entity}/
├── index.html.twig           # Vista principal (listado)
├── index_table.html.twig     # Tabla DataTables (renderizada vía AJAX)
├── _form.html.twig           # Formulario parcial (reutilizable)
├── edit.html.twig            # Formulario de edición/creación
├── show.html.twig            # Vista de detalle
├── _delete_form.html.twig    # Confirmación de borrado
└── filtros.html.twig         # Filtros de búsqueda
```

### DataTables (Paginación Server-Side)

El sistema usa **DataTables con server-side processing**. Configuración típica:

```javascript
// public/js/app/pedido/index.js
var table = $('#kt_datatable').KTDatatable({
    data: {
        type: 'remote',
        source: {
            read: {
                url: '/pedido/index_table/',
                method: 'GET',
                params: {
                    query: {
                        fechaDesde: function() { return $('#fechaDesde').val(); },
                        fechaHasta: function() { return $('#fechaHasta').val(); },
                    }
                }
            }
        },
        pageSize: 25,
        serverPaging: true,
        serverFiltering: true,
        serverSorting: true,
    },
    columns: [
        {field: 'id', title: 'ID'},
        {field: 'cliente', title: 'Cliente'},
        // ...
    ]
});
```

### Macros Twig

El proyecto usa macros para formularios:

```twig
{% import 'app/_macro_form.html.twig' as macro_form %}

{{ macro_form._new_field(form.campo, 'col-md-6') }}
```

### Assets

```twig
{# CSS #}
<link href="{{ asset(plugins_path ~ 'custom/datatables/datatables.bundle.css') }}" rel="stylesheet"/>

{# JS #}
<script src="{{ asset(plugins_path ~ 'custom/datatables/datatables.bundle.js') }}"></script>
<script src="{{ asset('js/app/pedido/index.js') }}"></script>
```

### Variables Globales en Templates

```twig
{{ app.user }}              {# Usuario actual #}
{{ app.request }}           {# Request actual #}
{{ is_granted('ROLE_X') }}  {# Verificar permiso #}
```

---

## Servicios

### Servicios Principales

#### EntityManagementGuesser

Infiere nombres de entidades desde el contexto del controlador:

```php
$entityName = $this->guesser->guessEntityShortName();  // "Pedido"
$formTypeName = $this->guesser->guessFormTypeName();   // "App\Form\PedidoType"
```

#### SelectService

Genera arrays para selects de formularios:

```php
$clienteSelect = $this->selectService->getClienteFilter();
$estadoSelect = $this->selectService->getEstadoSelect();
```

#### EstadoService

Gestiona estados de entidades (pedidos, entregas, etc.):

```php
$this->estadoService->cambiarEstado($entidad, $nuevoEstado);
```

#### PrintService

Genera reportes PDF:

```php
$this->printService->generarPDF($template, $datos);
```

### Crear un Servicio Nuevo

1. Crear la clase en `src/Service/`:

```php
<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class MiServicio
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function hacerAlgo()
    {
        // Lógica
    }
}
```

2. Se autoconfigura automáticamente (autowire en `services.yaml`)

3. Inyectar en controlador:

```php
class MiController extends BaseController
{
    private $miServicio;

    public function __construct(
        // ... otros parámetros del padre
        MiServicio $miServicio
    ) {
        parent::__construct(/* ... */);
        $this->miServicio = $miServicio;
    }
}
```

---

## Sistema de Permisos

### Roles

Los roles se definen en `config/packages/roles.yaml`. Ejemplos:

- `ROLE_ADMIN`: Administrador
- `ROLE_PEDIDO`: Acceso a pedidos
- `ROLE_ENTREGA`: Acceso a entregas
- `ROLE_USUARIO`: Acceso a gestión de usuarios

### Entity Usuario

```php
class Usuario implements UserInterface
{
    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $roles = [];

    /**
     * @ORM\ManyToMany(targetEntity="Grupo", inversedBy="usuarios")
     * @ORM\JoinTable(name="usuario_grupo")
     */
    protected $grupos;

    public function getRoles(): array
    {
        $roles = $this->roles;
        
        // Roles heredados de grupos
        foreach ($this->grupos as $grupo) {
            foreach ($grupo->getRoles() as $rol) {
                $roles[] = $rol;
            }
        }

        return array_unique($roles);
    }
}
```

### Proteger Rutas

```php
/**
 * @Route("/pedido")
 * @IsGranted("ROLE_PEDIDO")  // ← A nivel de clase o método
 */
class PedidoController extends BaseController {}
```

### Verificar en Templates

```twig
{% if is_granted('ROLE_PEDIDO') %}
    <a href="{{ path('pedido_new') }}">Agregar Pedido</a>
{% endif %}
```

### Verificar en Controlador

```php
if (!$this->authChecker->isGranted('ROLE_ADMIN')) {
    throw $this->createAccessDeniedException();
}
```

---

## Docker e Infraestructura

### Estructura Docker

```
docker-compose.dev.yml        # Desarrollo
docker-compose.prod.yml       # Producción
Dockerfile                    # Imagen PHP 8.1-Apache
```

### Servicios Docker (Desarrollo)

```yaml
services:
  app:
    container_name: app_babyplant
    ports: ["8080:80"]
    volumes: [".:/var/www/html"]

  mariadb:
    image: mariadb:10.6
    container_name: mariadb_babyplant
    ports: ["3308:3306"]

  phpmyadmin:
    container_name: phpmyadmin_babyplant
    ports: ["8081:80"]
```

### Comandos Docker

```bash
# Levantar entorno desarrollo
docker-compose -f docker-compose.dev.yml up -d

# Ver logs
docker logs -f app_babyplant

# Entrar al contenedor
docker exec -it app_babyplant bash

# Detener
docker-compose -f docker-compose.dev.yml down
```

### Dockerfile

- Base: `php:8.1-apache`
- Apache configurado para servir desde `/public`
- Extensiones PHP: pdo, pdo_mysql, gd, intl, zip, mbstring, xsl, opcache, apcu, amqp
- Composer instalado
- Document root: `/var/www/html/public`

---

## Configuración y Variables de Entorno

### Archivo .env

**⚠️ IMPORTANTE:** El archivo `.env` está en `.gitignore`. Crear desde `.env` local.

```env
APP_ENV=dev
APP_SECRET=tu_secret_key

# Base de datos
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=babyplant
DB_USERNAME=root
DB_PASSWORD=tu_password
DATABASE_URL="mysql://${DB_USERNAME}:${DB_PASSWORD}@${DB_HOST}:${DB_PORT}/${DB_DATABASE}?serverVersion=mariadb-10.4.11"

# MPDF
MPDF_BASE_PATH="http://localhost"

# AFIP
AFIPSDK_ACCESS_TOKEN="token"
ARCA_PRODUCTION=0
ARCA_CUIT=20382971923

# reCAPTCHA (opcional)
recaptcha_web_key=
recaptcha_secret_key=
```

### Variables Importantes

- `APP_ENV`: `dev` o `prod`
- `APP_SECRET`: Secreto de Symfony (generar único)
- `DATABASE_URL`: URL completa de conexión a DB
- `MPDF_BASE_PATH`: Base URL para PDFs (importante para imágenes)
- `AFIPSDK_ACCESS_TOKEN`: Token AFIP
- `ARCA_PRODUCTION`: 0=sandbox, 1=producción

### Acceso a Variables

En servicios:

```yaml
# config/services.yaml
parameters:
    MPDF_BASE_PATH: '%env(MPDF_BASE_PATH)%'
    ARCA_CUIT: '%env(int:ARCA_CUIT)%'

services:
    _defaults:
        bind:
            string $projectDir: '%kernel.project_dir%'
            int $arcaCuit: '%ARCA_CUIT%'
```

En controlador/servicio:

```php
$this->parameterBag->get('MPDF_BASE_PATH');
```

---

## Comandos Útiles

### Symfony Console

```bash
# Cache
php bin/console cache:clear
php bin/console cache:warmup

# Base de datos
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force
php bin/console doctrine:migrations:migrate

# Ver rutas
php bin/console debug:router

# Ver servicios
php bin/console debug:container

# Ver configuración
php bin/console debug:config doctrine

# Crear entidad
php bin/console make:entity

# Crear controlador
php bin/console make:controller

# CRUD personalizado (si existe maker custom)
php bin/console make:crud
```

### Composer

```bash
# Instalar dependencias
composer install

# Actualizar dependencias
composer update

# Autoload
composer dump-autoload
```

### Assets

```bash
# Instalar assets en public
php bin/console assets:install public
```

---

## Testing

### PHPUnit

Configurado en `phpunit.xml.dist`:

```bash
# Ejecutar todos los tests
php bin/phpunit

# Ejecutar test específico
php bin/phpunit tests/Controller/PedidoControllerTest.php

# Con coverage
php bin/phpunit --coverage-html var/coverage
```

### Estructura de Tests

```
tests/
├── bootstrap.php
├── Controller/
│   └── PedidoControllerTest.php
└── Service/
    └── EstadoServiceTest.php
```

---

## Reglas Git

### Archivos en .gitignore

```
/.env
/.env.local
/.env.*.local
/var/
/vendor/
/migrations/
/public/bundles/
docker-compose.dev.yml
docker-compose.prod.yml
```

### ⚠️ NUNCA commitear:

- Archivos `.env` (contienen credenciales)
- `docker-compose.dev.yml` / `docker-compose.prod.yml` (config específica)
- `/var/` (cache y logs)
- `/vendor/` (dependencias)
- `/migrations/` (pueden ser específicas del entorno)

### SÍ commitear:

- `composer.json` y `composer.lock`
- `symfony.lock`
- `.env` (como template sin valores sensibles)
- Código fuente (`/src`, `/templates`, `/public`)
- Configuración (`/config`)

---

## ⛔ RESTRICCIONES CRÍTICAS

### 🚫 NUNCA HACER:

1. **NO borrar o modificar `BaseController`** sin analizar impacto en 60+ controladores
2. **NO usar hard delete** (`$em->remove()` directo) - Siempre soft delete
3. **NO crear entidades sin trait `Auditoria`** (excepto `Usuario`)
4. **NO cambiar convención de nombres** DB (snake_case) vs PHP (camelCase)
5. **NO eliminar Traits** de entidades existentes (rompe auditoría)
6. **NO modificar `AuditoriaListener`** sin entender consecuencias
7. **NO ignorar anotaciones `@IsGranted`** (seguridad)
8. **NO mezclar rutas** (usar solo anotaciones, no YAML)
9. **NO commitear `.env`** con credenciales
10. **NO modificar migraciones ya aplicadas** en producción
11. **NO desactivar filtro soft delete** sin razón crítica
12. **NO crear controladores que no extiendan `BaseController`**
13. **NO usar jQuery cuando puedes usar vanilla JS** (código moderno preferido)
14. **NO duplicar lógica** que ya existe en `BaseController` o servicios

### ✅ SIEMPRE HACER:

1. **Extender `BaseController`** en nuevos controladores
2. **Usar anotaciones** `@Route`, `@Template`, `@IsGranted`
3. **Crear `{Entity}Type`** para cada formulario
4. **Aplicar trait `Auditoria`** a todas las entidades nuevas (excepto Usuario)
5. **Usar `@Gedmo\SoftDeleteable`** en todas las entidades
6. **Verificar permisos** con `@IsGranted` o `$this->authChecker->isGranted()`
7. **Crear repositorio** y asignarlo en entidad con `repositoryClass`
8. **Seguir convención de templates**: `{entity}/index.html.twig`, `{entity}/_form.html.twig`
9. **Crear JS separado** en `/public/js/app/{entity}/`
10. **Usar servicios** para lógica de negocio compleja
11. **Validar formularios** con `$form->isValid()`
12. **Flush explícitamente**: `$em->flush()` después de `persist()`

---

## ✅ Checklist Pre-Modificación

Antes de modificar o crear código, verifica:

### Para Entidades:

- [ ] ¿Usa trait `Auditoria`?
- [ ] ¿Tiene `@Gedmo\SoftDeleteable`?
- [ ] ¿Campos DB en snake_case, propiedades PHP en camelCase?
- [ ] ¿Tiene `__toString()` implementado?
- [ ] ¿Configurado `repositoryClass`?
- [ ] ¿Relaciones bien mapeadas (cascade, orphanRemoval)?

### Para Controladores:

- [ ] ¿Extiende `BaseController`?
- [ ] ¿Usa anotaciones `@Route`?
- [ ] ¿Tiene `@IsGranted` para proteger rutas?
- [ ] ¿Usa `@Template` si retorna array?
- [ ] ¿Reutiliza métodos `base*Action()` cuando es posible?
- [ ] ¿Inyecta servicios en constructor?

### Para Templates:

- [ ] ¿Extiende `base.html.twig`?
- [ ] ¿Sigue estructura `{entity}/nombre.html.twig`?
- [ ] ¿Usa macros para formularios comunes?
- [ ] ¿Incluye checks de permisos `is_granted()`?

### Para JavaScript:

- [ ] ¿Está en `/public/js/app/{entity}/`?
- [ ] ¿Sigue patrón `index.js`, `new.js`, `edit.js`?
- [ ] ¿Inicializa DataTables correctamente si aplica?

### Para Servicios:

- [ ] ¿Namespace `App\Service\`?
- [ ] ¿Inyecta dependencias en constructor?
- [ ] ¿Lógica de negocio, NO acceso a Request/Response?

### Para Base de Datos:

- [ ] ¿Creaste migración? (`doctrine:migrations:diff`)
- [ ] ¿Revisaste SQL generado antes de aplicar?
- [ ] ¿Probaste migración en desarrollo antes de producción?

### Para Seguridad:

- [ ] ¿Validaste permisos del usuario?
- [ ] ¿Sanitizaste inputs del usuario?
- [ ] ¿Validaste formularios con Symfony Forms?
- [ ] ¿No expones información sensible en errores?

---

## 🔍 Debugging y Troubleshooting

### Errores Comunes

**"Entity not found"**
- Verifica que el soft delete filter no esté ocultando registros
- Verifica que `fechaBaja IS NULL`

**"Access Denied"**
- Falta anotación `@IsGranted`
- Usuario no tiene el rol necesario
- Verifica `config/packages/roles.yaml`

**"No route found"**
- Verifica anotación `@Route`
- Clearear cache: `php bin/console cache:clear`
- Ver rutas: `php bin/console debug:router`

**"Form validation failed"**
- Verifica constraints en entidad
- Verifica `buildForm()` en FormType
- Activa debug toolbar para ver errores

**"DataTables no carga datos"**
- Verifica URL en configuración JS
- Verifica columnas en `columnDefinition` del controlador
- Ver respuesta en Network tab del navegador

### Logs

```bash
# Logs en Docker
docker logs -f app_babyplant

# Logs Symfony
tail -f var/log/dev.log
tail -f var/log/prod.log
```

### Debug Toolbar

En desarrollo (`APP_ENV=dev`), usa la toolbar de Symfony:
- Queries ejecutadas
- Tiempo de renderizado
- Errores y excepciones
- Formularios y validaciones

---

## 📚 Recursos Adicionales

### Documentación Oficial

- [Symfony 5.4](https://symfony.com/doc/5.4/index.html)
- [Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/2.12/index.html)
- [Twig](https://twig.symfony.com/doc/3.x/)

### Bundles Importantes

- [StofDoctrineExtensionsBundle](https://github.com/stof/StofDoctrineExtensionsBundle)
- [VichUploaderBundle](https://github.com/dustin10/VichUploaderBundle)
- [KnpTimeBundle](https://github.com/KnpLabs/KnpTimeBundle)

---

## 📝 Notas Finales

Este documento se basa en el análisis **real** del código del proyecto, no en suposiciones o buenas prácticas genéricas. Las convenciones aquí descritas son las que **realmente se usan** en el proyecto.

Si encontrás inconsistencias o patrones no documentados, reportalos para actualizar este archivo.

**Última revisión:** Septiembre 2026
