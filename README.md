Après analyse complète de ton projet, voici le fichier `README.md` professionnel pour ton package Laravel Actions.

---

# Laravel Actions

[![Latest Version on Packagist](https://img.shields.io/packagist/v/andydefer/laravel-actions.svg)](https://packagist.org/packages/andydefer/laravel-actions)
[![PHP Version Require](https://img.shields.io/packagist/php-v/andydefer/laravel-actions.svg)](https://packagist.org/packages/andydefer/laravel-actions)
[![Laravel Version](https://img.shields.io/badge/Laravel-10%2F11%2F12-ff2d20.svg)](https://laravel.com)
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

### 6. ActionRoute

Enregistrement simplifié des routes.

```php
ActionRoute::get('/api/users', ListUsersRequest::class, ListUsersAction::class);
ActionRoute::post('/api/users', CreateUserRequest::class, CreateUserAction::class);
ActionRoute::get('/api/users/{id}', ShowUserRequest::class, ShowUserAction::class);
ActionRoute::put('/api/users/{id}', UpdateUserRequest::class, UpdateUserAction::class);
ActionRoute::delete('/api/users/{id}', DeleteUserRequest::class, DeleteUserAction::class);
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
        return []; // Aucune validation spécifique
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
use AndyDefer\Actions\Support\ActionRoute;
use App\Http\Requests\ShowUserRequest;
use App\Actions\ShowUserAction;

ActionRoute::get('/api/users/{id}', ShowUserRequest::class, ShowUserAction::class);
```

---

## Documentation détaillée

| Composant | Documentation |
|-----------|---------------|
| `AbstractAction` | [Voir la documentation](docs/api-reference/actions/abstract-action.md) |
| `AbstractRequest` | [Voir la documentation](docs/api-reference/http/abstract-request.md) |
| `EmptyRequest` | [Voir la documentation](docs/api-reference/http/empty-request.md) |
| `ResponseFactory` | [Voir la documentation](docs/api-reference/http/response-factory.md) |
| `ActionRoute` | [Voir la documentation](docs/api-reference/support/action-route.md) |
| `HttpResponseType` | [Voir la documentation](docs/api-reference/enums/http-response-type.md) |

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
| 2.x | 10.x, 11.x, 12.x | 8.2+ |

---

## License

MIT © [Andy Defer](https://github.com/andydefer)

---

## Crédits

- Pattern ADR inspiré par [Paul M. Jones](https://github.com/pmjones)
- Template Method pattern issu de Gamma et al. "Design Patterns"