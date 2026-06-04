# Fonction helper `action_route()` - Référence Technique

## Description

Fonction utilitaire qui retourne une closure capable d'instancier et d'exécuter une paire Request/Action. Cette closure peut être utilisée directement dans les définitions de routes Laravel, préservant ainsi toute l'API fluide du `RouteRegistrar`.

## Syntaxe

```php
action_route(string $requestClass, string $actionClass): Closure
```

## Paramètres

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$requestClass` | `string` | FQCN de la classe Request (doit étendre `AbstractRequest`) |
| `$actionClass` | `string` | FQCN de la classe Action (doit étendre `AbstractAction`) |

## Retour

| Type | Description |
|------|-------------|
| `Closure` | Une closure qui exécute la Request et l'Action et retourne une réponse HTTP |

## Installation

La fonction est automatiquement chargée par le package via l'autoload `files` dans `composer.json`.

```json
{
    "autoload": {
        "files": [
            "src/helpers.php"
        ]
    }
}
```

## Exemples d'utilisation

### Exemple 1 : Route GET basique

```php
use function action_route;

Route::get('/users', action_route(ListUsersRequest::class, ListUsersAction::class));
```

### Exemple 2 : Route avec nommage

```php
Route::get('/users', action_route(ListUsersRequest::class, ListUsersAction::class))
    ->name('users.index');
```

### Exemple 3 : Route avec middleware

```php
Route::post('/users', action_route(CreateUserRequest::class, CreateUserAction::class))
    ->middleware('auth')
    ->name('users.store');
```

### Exemple 4 : Route avec contrainte de paramètre

```php
Route::get('/users/{id}', action_route(GetUserRequest::class, GetUserAction::class))
    ->name('users.show')
    ->where('id', '[0-9]+');
```

### Exemple 5 : Route avec plusieurs middlewares

```php
Route::put('/users/{id}', action_route(UpdateUserRequest::class, UpdateUserAction::class))
    ->middleware(['auth', 'admin'])
    ->name('users.update');
```

### Exemple 6 : Route DELETE

```php
Route::delete('/users/{id}', action_route(DeleteUserRequest::class, DeleteUserAction::class))
    ->name('users.destroy');
```

### Exemple 7 : Dans un groupe de routes

```php
Route::prefix('admin')->middleware('auth')->group(function () {
    Route::get('/dashboard', action_route(DashboardRequest::class, DashboardAction::class))
        ->name('admin.dashboard');
    
    Route::get('/users', action_route(AdminUsersRequest::class, AdminUsersAction::class))
        ->name('admin.users.index');
});
```

### Exemple 8 : Route avec paramètres multiples

```php
Route::get('/users/{userId}/posts/{postId}', 
    action_route(GetUserPostsRequest::class, GetUserPostsAction::class))
    ->name('users.posts.show')
    ->where(['userId' => '[0-9]+', 'postId' => '[0-9]+']);
```

### Exemple 9 : Route avec méthode match

```php
Route::match(['GET', 'POST'], '/webhook', 
    action_route(WebhookRequest::class, WebhookAction::class))
    ->name('webhook.handle');
```

### Exemple 10 : Route any

```php
Route::any('/resource', action_route(ResourceRequest::class, ResourceAction::class))
    ->name('resource.any');
```

## Avantages par rapport à ActionRoute

| Aspect | ActionRoute | action_route() |
|--------|-------------|----------------|
| API fluide | ❌ Perdue | ✅ Préservée |
| Nommage de route | ❌ Non supporté | ✅ `->name()` |
| Middleware direct | ❌ Via groupe uniquement | ✅ `->middleware()` |
| Contraintes where | ❌ Non supporté | ✅ `->where()` |
| Chaining complet | ❌ Impossible | ✅ Possible |
| Flexibilité | ❌ Limitée | ✅ Totale |

## Méthodes de chaînage disponibles

Toutes les méthodes du `RouteRegistrar` sont disponibles :

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `->name()` | Nomme la route | `->name('users.index')` |
| `->middleware()` | Ajoute des middlewares | `->middleware('auth')` |
| `->where()` | Contraintes de paramètres | `->where('id', '[0-9]+')` |
| `->prefix()` | Préfixe d'URL | Via groupe uniquement |
| `->domain()` | Sous-domaine | `->domain('api.domain.com')` |
| `->withoutMiddleware()` | Supprime des middlewares | `->withoutMiddleware('api')` |

## Contraintes

**Les classes passées doivent étendre les classes abstraites appropriées.**

| Paramètre | Doit étendre | Raison |
|-----------|--------------|--------|
| `$requestClass` | `AbstractRequest` | Fournit la méthode `getRecord()` et l'intégration avec Laravel FormRequest |
| `$actionClass` | `AbstractAction` | Fournit le template method `run()` et les hooks `before()`/`after()` |

```php
// ✅ Valide
class GetUserRequest extends AbstractRequest { ... }
class GetUserAction extends AbstractAction { ... }

Route::get('/users/{id}', action_route(GetUserRequest::class, GetUserAction::class));

// ❌ Invalide - Erreur à l'exécution
Route::get('/users/{id}', action_route('stdClass', 'stdClass'));
```

## Gestion des erreurs

| Situation | Comportement |
|-----------|--------------|
| La classe Request n'existe pas | Exception levée par Laravel lors de l'appel |
| La classe Action n'existe pas | Exception levée par Laravel lors de l'appel |
| La classe Request n'étend pas `AbstractRequest` | Erreur de type à l'exécution |
| La classe Action n'étend pas `AbstractAction` | Erreur de type à l'exécution |

## Intégration

La fonction s'intègre avec :

- **Laravel Router** : Utilise `Route::match()` pour l'enregistrement
- **Conteneur Laravel** : Résout automatiquement les dépendances via `app()`
- **AbstractRequest** : Validation et création du Record
- **AbstractAction** : Logique métier via `run()`
- **ResponseFactory** : Construction des réponses HTTP

## Performance

| Aspect | Caractéristique |
|--------|-----------------|
| **Surcharge** | Négligeable (simple closure wrapper) |
| **Validation** | Aucune à l'enregistrement (reportée à l'exécution) |
| **Résolution** | Via conteneur Laravel (caché) |

## Compatibilité

| Version | Support |
|---------|---------|
| PHP 8.1+ | ✅ Requis |
| Laravel 10.x | ✅ Complet |
| Laravel 11.x | ✅ Complet |
| Laravel 12.x | ✅ Complet |
| Laravel 13.x | ✅ Complet |
| Laravel 14.x | ✅ Complet |
| Laravel 15.x | ✅ Complet |

## Migration depuis ActionRoute

**Avant :**
```php
ActionRoute::get('/api/users', ListUsersRequest::class, ListUsersAction::class);
```

**Après :**
```php
use function action_route;

Route::get('/api/users', action_route(ListUsersRequest::class, ListUsersAction::class));
```

## Note

Cette fonction remplace la classe `ActionRoute` qui est maintenant dépréciée. Privilégiez l'utilisation de `action_route()` pour toutes les nouvelles routes.