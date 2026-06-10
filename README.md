# Laravel Actions

[![Latest Version on Packagist](https://img.shields.io/packagist/v/andydefer/laravel-actions.svg)](https://packagist.org/packages/andydefer/laravel-actions)
[![PHP Version Require](https://img.shields.io/packagist/php-v/andydefer/laravel-actions.svg)](https://packagist.org/packages/andydefer/laravel-actions)
[![Laravel Version](https://img.shields.io/badge/Laravel-10%2F11%2F12%2F13%2F14%2F15-ff2d20.svg)](https://laravel.com)
[![License](https://img.shields.io/packagist/l/andydefer/laravel-actions.svg)](https://packagist.org/packages/andydefer/laravel-actions)

## Table des matières

- [Introduction](#introduction)
- [Philosophie](#philosophie)
- [Installation](#installation)
- [Concepts clés](#concepts-clés)
- [Guide de démarrage](#guide-de-démarrage)
- [Documentation détaillée](#documentation-détaillée)
- [Tests](#tests)
- [Compatibilité](#compatibilité)
- [License](#license)

---

## Introduction

**Laravel Actions** est un package qui implémente le pattern **ADR (Action-Domain-Responder)** pour Laravel. Il transforme vos contrôleurs en classes d'action simples, testables et maintenables.

Chaque route HTTP est associée à une **Action** (logique métier) et une **Request** (validation et transformation des données).

```php
// Au lieu d'un contrôleur avec 5 méthodes
class UserController extends Controller
{
    public function index() { ... }
    public function show($id) { ... }
    public function store(Request $request) { ... }
    // ...
}

// Vous avez 5 classes d'action dédiées
final class ListUsersAction extends AbstractAction { ... }
final class ShowUserAction extends AbstractAction { ... }
final class CreateUserAction extends AbstractAction { ... }
```

---

## Philosophie

| Principe | Application dans le package |
|----------|----------------------------|
| **Single Responsibility** | Une Action = une route HTTP |
| **Type Safety** | Records typés entre Request et Action |
| **Testabilité** | Actions sans helpers globaux, seulement des dépendances injectées |
| **Immutabilité** | Records et Data DTOs sont readonly |
| **Template Method** | Cycle de vie `before()` → `handle()` → `after()` |

---

## Installation

```bash
composer require andydefer/laravel-actions
```

Le package s'enregistre automatiquement via Laravel's auto-discovery.

---

## Concepts clés

### 1. AbstractAction

Classe de base pour toutes vos actions. Implémente le pattern **Template Method**.

```php
final class CreateUserAction extends AbstractAction
{
    public function __construct(
        private readonly UserRepositoryInterface $users
    ) {}
    
    protected function handle(AbstractRecord $request): ResponseFactory
    {
        $user = $this->users->create($request->toArray());
        return ResponseFactory::json(UserData::from($user), 201);
    }
}
```

**Cycle de vie :**
```
run(Record) → before() → handle() → after() → ResponseFactory
```

### 2. AbstractRequest

Classe de base pour vos requêtes. Étend `FormRequest` de Laravel.

```php
final class CreateUserRequest extends AbstractRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
        ];
    }
    
    public function getRecord(): AbstractRecord
    {
        return CreateUserRecord::from([
            'name' => $this->input('name'),
            'email' => $this->input('email'),
        ]);
    }
}
```

### 3. AbstractRecord

DTO typé pour transporter les données entre la Request et l'Action.

```php
final class CreateUserRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}
```

### 4. AbstractData

DTO immutable pour les réponses HTTP (converti automatiquement en camelCase).

```php
final class UserData extends AbstractData
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email,
    ) {}
}
```

### 5. ResponseFactory

Factory pour construire des réponses HTTP de manière déclarative.

```php
return ResponseFactory::json($userData);           // JSON API
return ResponseFactory::inertia('Dashboard/Index'); // Inertia SPA
return ResponseFactory::redirectRoute('home');     // Redirection
return ResponseFactory::noContent();               // 204 No Content
return ResponseFactory::fileDownload($path);       // Téléchargement
```

### 6. Fonction helper `action_route()`

Fonction utilitaire qui retourne une closure pour associer une Request et une Action à une route. Cette approche préserve toute l'API fluide du `RouteRegistrar`.

```php
use function action_route;

Route::get('/api/users', action_route(ListUsersRequest::class, ListUsersAction::class))
    ->name('users.index')
    ->middleware('auth');

Route::post('/api/users', action_route(CreateUserRequest::class, CreateUserAction::class))
    ->name('users.store')
    ->middleware('throttle:10,1');

Route::get('/api/users/{id}', action_route(ShowUserRequest::class, ShowUserAction::class))
    ->name('users.show')
    ->where('id', '[0-9]+');
```

### 7. Fonction helper `action_factory()`

Fonction utilitaire pour les routes simples qui n'ont pas besoin d'une Action dédiée. Parfaite pour les health checks, redirections simples ou vues statiques.

**Règle d'utilisation :** Une ligne = `action_factory()` | Deux lignes ou plus = Créer une Action complète

```php
use function action_factory;
use App\Data\HealthData;

// Health check avec DTO
Route::get('/health', action_factory(
    ResponseFactory::json(HealthData::from(['status' => 'ok', 'timestamp' => now()->toIso8601String()]), 200)
))->name('api.health')->middleware('api');

// Redirection simple
Route::get('/redirect', action_factory(ResponseFactory::redirectRoute('home')));

// Vue statique
Route::get('/home', action_factory(ResponseFactory::view('welcome')));

// Fichier inline
Route::get('/resume', action_factory(ResponseFactory::fileInline(storage_path('resume.pdf'))));

// Téléchargement
Route::get('/export', action_factory(ResponseFactory::fileDownload(storage_path('export.csv'), 'data.csv')));

// 204 No Content
Route::delete('/resource/{id}', action_factory(ResponseFactory::noContent()));

// Texte brut
Route::get('/robots.txt', action_factory(ResponseFactory::text("User-agent: *\nDisallow: /private/")));
```

**Important :** `ResponseFactory::json()` nécessite un objet `AbstractData`. Les tableaux bruts ne sont pas acceptés.

```php
// ❌ Erreur - Tableau brut non accepté
Route::get('/health', action_factory(ResponseFactory::json(['status' => 'ok'], 200)));

// ✅ Correct - Utilisation d'un DTO
Route::get('/health', action_factory(ResponseFactory::json(HealthData::from(['status' => 'ok']), 200)));
```

---

## Guide de démarrage

### Étape 1 : Créer un Record

```php
// app/Records/ShowUserRecord.php
<?php

declare(strict_types=1);

namespace App\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class ShowUserRecord extends AbstractRecord
{
    public function __construct(
        public readonly int $id,
    ) {}
}
```

### Étape 2 : Créer une Request

```php
// app/Http/Requests/ShowUserRequest.php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use App\Records\ShowUserRecord;

final class ShowUserRequest extends AbstractRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
    
    public function getRecord(): AbstractRecord
    {
        return ShowUserRecord::from([
            'id' => (int) $this->route('id'),
        ]);
    }
}
```

### Étape 3 : Créer un Data DTO

```php
// app/Data/UserData.php
<?php

declare(strict_types=1);

namespace App\Data;

use AndyDefer\DomainStructures\Abstracts\AbstractData;

final class UserData extends AbstractData
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
    ) {}
}
```

### Étape 4 : Créer une Action

```php
// app/Actions/ShowUserAction.php
<?php

declare(strict_types=1);

namespace App\Actions;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use App\Data\UserData;
use App\Models\User;

final class ShowUserAction extends AbstractAction
{
    protected function handle(AbstractRecord $request): ResponseFactory
    {
        /** @var ShowUserRecord $request */
        $user = User::findOrFail($request->id);
        
        return ResponseFactory::json(UserData::from([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]));
    }
}
```

### Étape 5 : Enregistrer la route

```php
// routes/api.php
use function action_route;
use App\Http\Requests\ShowUserRequest;
use App\Actions\ShowUserAction;

Route::get('/api/users/{id}', action_route(ShowUserRequest::class, ShowUserAction::class))
    ->name('users.show')
    ->where('id', '[0-9]+');
```

### Exemple complet : API REST

```php
// routes/api.php
use function action_route;

Route::prefix('v1')->middleware('api')->group(function () {
    
    // Routes utilisateurs
    Route::get('/users', action_route(ListUsersRequest::class, ListUsersAction::class))
        ->name('api.v1.users.index')
        ->middleware('cache:300');
    
    Route::post('/users', action_route(CreateUserRequest::class, CreateUserAction::class))
        ->name('api.v1.users.store')
        ->middleware('throttle:10,1');
    
    Route::get('/users/{id}', action_route(ShowUserRequest::class, ShowUserAction::class))
        ->name('api.v1.users.show')
        ->where('id', '[0-9]+');
    
    Route::put('/users/{id}', action_route(UpdateUserRequest::class, UpdateUserAction::class))
        ->name('api.v1.users.update')
        ->where('id', '[0-9]+');
    
    Route::delete('/users/{id}', action_route(DeleteUserRequest::class, DeleteUserAction::class))
        ->name('api.v1.users.destroy')
        ->where('id', '[0-9]+');
});
```

### Exemple : Routes simples avec action_factory()

```php
// routes/web.php
use function action_factory;
use App\Data\HealthData;

// Health check
Route::get('/health', action_factory(
    ResponseFactory::json(HealthData::from(['status' => 'ok', 'timestamp' => now()->toIso8601String()]), 200)
))->name('health');

// Redirection
Route::get('/home', action_factory(ResponseFactory::redirectRoute('dashboard')))->name('home');

// Vue statique
Route::get('/about', action_factory(ResponseFactory::view('pages.about')))->name('about');

// Fichier
Route::get('/resume', action_factory(ResponseFactory::fileInline(storage_path('resume.pdf'))));

// 204 No Content
Route::post('/webhook', action_factory(ResponseFactory::noContent()));
```

---

## Documentation détaillée

| Composant | Documentation |
|-----------|---------------|
| `AbstractAction` | [Voir la documentation](docs/api-reference/actions/abstract-action.md) |
| `AbstractRequest` | [Voir la documentation](docs/api-reference/http/abstract-request.md) |
| `EmptyRequest` | [Voir la documentation](docs/api-reference/http/empty-request.md) |
| `ResponseFactory` | [Voir la documentation](docs/api-reference/http/response-factory.md) |
| `action_route()` | [Voir la documentation](docs/api-reference/support/action-route-helper.md) |
| `action_factory()` | [Voir la documentation](docs/api-reference/support/action-factory-helper.md) |
| `ActionRoute` (déprécié) | [Voir la documentation](docs/api-reference/support/action-route.md) |
| `HttpResponseType` | [Voir la documentation](docs/api-reference/enums/http-response-type.md) |

---

## Migration depuis ActionRoute

**Ancienne syntaxe (dépréciée) :**
```php
ActionRoute::get('/api/users', ListUsersRequest::class, ListUsersAction::class);
```

**Nouvelle syntaxe (recommandée) :**
```php
use function action_route;

Route::get('/api/users', action_route(ListUsersRequest::class, ListUsersAction::class))
    ->name('users.index')
    ->middleware('auth');
```

**Pour les routes simples :**
```php
// Ancienne syntaxe (dépréciée)
ActionRoute::get('/health', HealthRequest::class, HealthAction::class);

// Nouvelle syntaxe avec action_factory()
Route::get('/health', action_factory(ResponseFactory::json(HealthData::from(['status' => 'ok']), 200)));
```

---


## Bonnes pratiques

### Convention de nommage

Le package suit une convention de nommage stricte qui garantit la cohérence et la prédictibilité du code. Les noms des classes doivent refléter exactement la structure des dossiers et l'URI de la route.

**Règle fondamentale :** Le nom de la route détermine le nom de toutes les classes associées.

```bash
# La route détermine la convention
/api/doctors/show → Api/Doctors/Show
```

**Correspondance complète :**

| Composant | Convention | Exemple (`api/doctors/show`) |
|-----------|------------|------------------------------|
| **Action** | `Actions/{Chemin}Action` | `App\Actions\Api\Doctors\ShowAction` |
| **Request** | `Http\Requests\{Chemin}Request` | `App\Http\Requests\Api\Doctors\ShowRequest` |
| **Record** | `Records\{Chemin}Record` | `App\Records\Api\Doctors\ShowRecord` |
| **Data** | `Data\{Chemin}Data` | `App\Data\Api\Doctors\ShowData` |

**Génération automatique avec la commande :**

```bash
# La commande crée automatiquement les 4 classes avec les bons noms
php artisan actions:make api/doctors/show --fully

# Résultat :
# ✅ Action:  Api/Doctors/ShowAction
# ✅ Request: Api/Doctors/ShowRequest  
# ✅ Record:  Api/Doctors/ShowRecord
# ✅ Data:    Api/Doctors/ShowData
```

**Pourquoi cette convention ?**

- **Prédictible** : Le développeur sait immédiatement où chercher chaque classe
- **Auto-documenté** : Le chemin du fichier indique la route qu'il sert
- **IDE-friendly** : La complétion automatique fonctionne parfaitement
- **Sans collision** : Plusieurs endpoints avec le même nom dans des dossiers différents coexistent

**À respecter impérativement :**

```php
// ✅ Bon : Le namespace correspond au chemin
namespace App\Actions\Api\Doctors;
final class ShowAction extends AbstractAction {}

// ❌ Mauvais : Namespace incohérent avec le chemin
namespace App\Actions\Doctors;
final class ShowDoctorAction extends AbstractAction {}
```

> **Important :** Cette convention de nommage est obligatoire pour que l'autoloading, les outils d'analyse statique (PHPStan, Psalm) et la maintenance à long terme fonctionnent correctement.

---


## Tests

```bash
composer test
```

Le package utilise PHPUnit avec deux types de tests :

- **Unit tests** : Tests rapides et isolés (`tests/Unit/`)
- **Integration tests** : Tests avec Laravel booté (`tests/Integration/`)

---

## Compatibilité

| Version | Laravel | PHP |
|---------|---------|-----|
| 1.x | 10.x, 11.x, 12.x | 8.1+ |
| 2.x | 10.x, 11.x, 12.x, 13.x, 14.x, 15.x | 8.2+ |

---



## License

MIT © [Andy Defer](https://github.com/andydefer)

---

## Crédits

- Pattern ADR inspiré par [Paul M. Jones](https://github.com/pmjones)
- Template Method pattern issu de Gamma et al. "Design Patterns"
