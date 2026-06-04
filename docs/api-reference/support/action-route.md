# ActionRoute - Référence Technique

> ⚠️ **DÉPRÉCIÉ** : Cette classe est dépréciée depuis la version 2.0. Utilisez plutôt la fonction helper `action_route()` qui offre plus de flexibilité et préserve l'API fluide du RouteRegistrar.

## Description

Enregistre des routes HTTP qui associent une classe de requête (`AbstractRequest`) à une classe d'action (`AbstractAction`).

## Migration

**Ancienne syntaxe (dépréciée) :**
```php
ActionRoute::get('/users', ListUsersRequest::class, ListUsersAction::class);
```

**Nouvelle syntaxe (recommandée) :**
```php
use function action_route;

Route::get('/users', action_route(ListUsersRequest::class, ListUsersAction::class))
    ->name('users.index')
    ->middleware('auth');
```

## Hiérarchie

```
ActionRoute (final) [DÉPRÉCIÉ]
    └── Utilise Laravel Route facade
```

## Rôle principal

Simplifie l'enregistrement des routes en éliminant le boilerplate des closures. Chaque route est automatiquement reliée à une requête (validation) et une action (logique métier).

## Contrainte d'extension (Type Safety)

**Toute classe passée à `ActionRoute` DOIT étendre les classes abstraites appropriées.**

| Paramètre | Doit étendre | Raison |
|-----------|--------------|--------|
| `$requestClass` | `AbstractRequest` | Fournit la méthode `getRecord()` et l'intégration avec Laravel FormRequest |
| `$actionClass` | `AbstractAction` | Fournit le template method `run()` et les hooks `before()`/`after()` |

```php
// ✅ Valide
class GetUserRequest extends AbstractRequest { ... }
class GetUserAction extends AbstractAction { ... }

ActionRoute::get('/users/{id}', GetUserRequest::class, GetUserAction::class);

// ❌ Invalide - Lance une exception
ActionRoute::get('/users/{id}', stdClass::class, GetUserAction::class);
// Exception: "Request class "stdClass" must extend AbstractRequest"
```

**Pourquoi cette contrainte ?**

1. **`AbstractRequest`** garantit l'existence de `getRecord()` qui construit le Record
2. **`AbstractAction`** garantit l'existence de `run()` qui exécute la logique métier
3. La closure interne utilise ces méthodes sans vérification supplémentaire
4. La validation à l'enregistrement évite les erreurs à l'exécution

## Installation

```bash
composer require andydefer/laravel-actions
```

## API / Méthodes publiques

### `get(string $uri, string $requestClass, string $actionClass): void`

Enregistre une route GET.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$uri` | `string` | Pattern de l'URI (ex: `/users/{id}`) |
| `$requestClass` | `string` | FQCN de la classe Request (doit étendre `AbstractRequest`) |
| `$actionClass` | `string` | FQCN de la classe Action (doit étendre `AbstractAction`) |

**Exceptions :** 
- `InvalidArgumentException` - Si une classe n'existe pas
- `InvalidArgumentException` - Si une classe n'étend pas la classe abstraite correcte

**Exemple :**
```php
ActionRoute::get('/api/users/{id}', GetUserRequest::class, GetUserAction::class);
```

### `post(string $uri, string $requestClass, string $actionClass): void`

Enregistre une route POST.

Mêmes paramètres et exceptions que `get()`.

**Exemple :**
```php
ActionRoute::post('/api/users', CreateUserRequest::class, CreateUserAction::class);
```

### `put(string $uri, string $requestClass, string $actionClass): void`

Enregistre une route PUT.

Mêmes paramètres et exceptions que `get()`.

**Exemple :**
```php
ActionRoute::put('/api/users/{id}', UpdateUserRequest::class, UpdateUserAction::class);
```

### `patch(string $uri, string $requestClass, string $actionClass): void`

Enregistre une route PATCH.

Mêmes paramètres et exceptions que `get()`.

**Exemple :**
```php
ActionRoute::patch('/api/users/{id}', PartialUpdateUserRequest::class, PartialUpdateUserAction::class);
```

### `delete(string $uri, string $requestClass, string $actionClass): void`

Enregistre une route DELETE.

Mêmes paramètres et exceptions que `get()`.

**Exemple :**
```php
ActionRoute::delete('/api/users/{id}', DeleteUserRequest::class, DeleteUserAction::class);
```

### `match(array $methods, string $uri, string $requestClass, string $actionClass): void`

Enregistre une route pour plusieurs méthodes HTTP.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$methods` | `array<string>` | Tableau des méthodes HTTP (ex: `['GET', 'POST']`) |
| `$uri` | `string` | Pattern de l'URI |
| `$requestClass` | `string` | FQCN de la classe Request (doit étendre `AbstractRequest`) |
| `$actionClass` | `string` | FQCN de la classe Action (doit étendre `AbstractAction`) |

**Exemple :**
```php
ActionRoute::match(['GET', 'POST'], '/api/resource', ResourceRequest::class, ResourceAction::class);
```

### `any(string $uri, string $requestClass, string $actionClass): void`

Enregistre une route pour toutes les méthodes HTTP standards (GET, POST, PUT, PATCH, DELETE).

**Exemple :**
```php
ActionRoute::any('/api/webhook', WebhookRequest::class, WebhookAction::class);
```

## Cas d'utilisation

### Cas 1 : API REST standard

```php
ActionRoute::get('/api/users', ListUsersRequest::class, ListUsersAction::class);
ActionRoute::post('/api/users', CreateUserRequest::class, CreateUserAction::class);
ActionRoute::get('/api/users/{id}', GetUserRequest::class, GetUserAction::class);
ActionRoute::put('/api/users/{id}', UpdateUserRequest::class, UpdateUserAction::class);
ActionRoute::delete('/api/users/{id}', DeleteUserRequest::class, DeleteUserAction::class);
```

### Cas 2 : Routes avec paramètres typés

```php
class CastParamsRequest extends AbstractRequest
{
    public function getRecord(): AbstractRecord
    {
        return CastParamsRecord::from([
            'int' => (int) $this->route('int'),
            'float' => (float) $this->route('float'),
            'boolTrue' => $this->route('boolTrue') === 'true',
            'boolFalse' => $this->route('boolFalse') === 'true',
        ]);
    }
}

ActionRoute::get('/api/cast/{int}/{float}/{boolTrue}/{boolFalse}', CastParamsRequest::class, CastParamsAction::class);
```

### Cas 3 : Routes avec middleware et préfixe

```php
Route::prefix('admin')->middleware('auth')->group(function () {
    ActionRoute::get('/dashboard', DashboardRequest::class, DashboardAction::class);
    ActionRoute::get('/users', AdminUsersRequest::class, AdminUsersAction::class);
});
```

## Flux d'exécution

<img src="../graphics/action_route_flow.png" alt="Flux d'enregistrement et d'exécution d'une route Action" width="800">

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| La classe Request n'existe pas | `InvalidArgumentException` | `Request class "X" does not exist` |
| La classe Action n'existe pas | `InvalidArgumentException` | `Action class "X" does not exist` |
| La classe Request n'étend pas `AbstractRequest` | `InvalidArgumentException` | `Request class "X" must extend AbstractRequest` |
| La classe Action n'étend pas `AbstractAction` | `InvalidArgumentException` | `Action class "X" must extend AbstractAction` |

## Intégration

`ActionRoute` s'intègre avec :

- **Laravel Router** : Utilise `Route::match()` pour l'enregistrement
- **Conteneur Laravel** : Résout automatiquement les dépendances via `app()`
- **AbstractRequest** : Doit être étendu par toutes les requêtes
- **AbstractAction** : Doit être étendu par toutes les actions

## Performance

- L'enregistrement des routes s'effectue une fois au démarrage de l'application
- Les validations (`class_exists`, `is_subclass_of`) sont exécutées uniquement lors de l'enregistrement
- La résolution des dépendances via `app()` est optimisée par le conteneur Laravel

## Compatibilité

| Version | Support |
|---------|---------|
| Laravel 10.x | ✅ Complet (déprécié) |
| Laravel 11.x | ✅ Complet (déprécié) |
| Laravel 12.x | ✅ Complet (déprécié) |
| PHP 8.1+ | ✅ Requis |

## Exemple complet (déprécié)

```php
<?php

declare(strict_types=1);

use AndyDefer\Actions\Support\ActionRoute;

// ⚠️ Cette syntaxe est dépréciée
ActionRoute::get('/api/users', ListUsersRequest::class, ListUsersAction::class);
ActionRoute::post('/api/users', CreateUserRequest::class, CreateUserAction::class);
ActionRoute::get('/api/users/{id}', GetUserRequest::class, GetUserAction::class);
ActionRoute::put('/api/users/{id}', UpdateUserRequest::class, UpdateUserAction::class);
ActionRoute::delete('/api/users/{id}', DeleteUserRequest::class, DeleteUserAction::class);
```

## Exemple de migration recommandé

```php
<?php

declare(strict_types=1);

use function action_route;

// ✅ Nouvelle syntaxe recommandée
Route::get('/api/users', action_route(ListUsersRequest::class, ListUsersAction::class))
    ->name('api.users.index');

Route::post('/api/users', action_route(CreateUserRequest::class, CreateUserAction::class))
    ->name('api.users.store');

Route::get('/api/users/{id}', action_route(GetUserRequest::class, GetUserAction::class))
    ->name('api.users.show')
    ->where('id', '[0-9]+');

Route::put('/api/users/{id}', action_route(UpdateUserRequest::class, UpdateUserAction::class))
    ->name('api.users.update');

Route::delete('/api/users/{id}', action_route(DeleteUserRequest::class, DeleteUserAction::class))
    ->name('api.users.destroy');
```

## Raison de la dépréciation

La classe `ActionRoute` présentait plusieurs limitations :

1. **Impossible de nommer les routes** - Pas de support de `->name()`
2. **Impossible d'ajouter des middlewares directement** - Nécessitait des groupes
3. **Impossible d'ajouter des contraintes where** - Pas de support de `->where()`
4. **Perte de l'API fluide** - Le chaînage des méthodes n'était pas possible

La fonction helper `action_route()` résout tous ces problèmes tout en restant plus simple et plus flexible.
```