# ActionRoute - Référence Technique

## Description

Enregistre des routes HTTP qui associent une classe de requête (`AbstractRequest`) à une classe d'action (`AbstractAction`).

## Hiérarchie

```
ActionRoute (final)
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

```
Requête HTTP
    ↓
Laravel Router → Route::match()
    ↓
Closure interne
    ↓
Validation des classes (à l'enregistrement)
    ├── class_exists($requestClass)
    ├── is_subclass_of($requestClass, AbstractRequest::class)
    ├── class_exists($actionClass)
    └── is_subclass_of($actionClass, AbstractAction::class)
    ↓
app($requestClass) → Instance de AbstractRequest
    ↓
app($actionClass) → Instance de AbstractAction
    ↓
$action->run($request->getRecord())
    ↓
Réponse HTTP
```

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
| Laravel 10.x | ✅ Complet |
| Laravel 11.x | ✅ Complet |
| Laravel 12.x | ✅ Complet |
| PHP 8.1+ | ✅ Requis |

## Exemple complet

```php
<?php

declare(strict_types=1);

use AndyDefer\Actions\Support\ActionRoute;

// Enregistrement des routes
ActionRoute::get('/api/users', ListUsersRequest::class, ListUsersAction::class);
ActionRoute::post('/api/users', CreateUserRequest::class, CreateUserAction::class);
ActionRoute::get('/api/users/{id}', GetUserRequest::class, GetUserAction::class);
ActionRoute::put('/api/users/{id}', UpdateUserRequest::class, UpdateUserAction::class);
ActionRoute::delete('/api/users/{id}', DeleteUserRequest::class, DeleteUserAction::class);

// Routes avec middleware
Route::middleware('auth')->group(function () {
    ActionRoute::get('/api/profile', ProfileRequest::class, ProfileAction::class);
});

// Routes avec préfixe
Route::prefix('api/v1')->group(function () {
    ActionRoute::get('/products', ListProductsRequest::class, ListProductsAction::class);
});
```

## Voir aussi

- `AbstractRequest` - Classe de base pour les requêtes (doit être étendue)
- `AbstractAction` - Classe de base pour les actions (doit être étendue)
- `AbstractRecord` - Transport de données interne
- `AbstractData` - Réponse API (camelCase)
