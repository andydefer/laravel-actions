# AbstractRequest - Référence Technique

## Description

Classe abstraite de base pour toutes les requêtes HTTP dans le système d'Action. Étend `FormRequest` de Laravel pour fournir validation et autorisation, avec la capacité supplémentaire de retourner les données validées sous forme d'objet `StrictDataObject`.

## Hiérarchie

```
Illuminate\Foundation\Http\FormRequest
    └── AbstractRequest (abstract)
```

## Rôle principal

Faire le pont entre la requête HTTP entrante et l'Action. Valide les données, autorise l'utilisateur, puis transforme les données validées en un objet `AbstractRecord` typé qui sera passé à l'Action. La méthode `validated()` retourne désormais un `StrictDataObject` permettant un accès à la fois par tableau et par propriété.

## Installation

```bash
composer require andydefer/laravel-actions
```

## API / Méthodes publiques

### `authorize(): bool`

Détermine si l'utilisateur est autorisé à effectuer cette requête.

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `bool` - `true` si l'utilisateur est autorisé, `false` sinon

**Exemple :**
```php
public function authorize(): bool
{
    return $this->user()->can('create', Post::class);
}
```

### `rules(): array`

Définit les règles de validation applicables à la requête.

**Retourne :** `array<string, array<int, string>>` - Tableau associatif champ → règles

**Exemple :**
```php
public function rules(): array
{
    return [
        'email' => ['required', 'email', 'unique:users'],
        'name' => ['required', 'string', 'max:255'],
    ];
}
```

### `validated($key = null, $default = null): StrictDataObject|mixed`

Récupère les données validées. Retourne un `StrictDataObject` au lieu d'un tableau.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string|null` | Clé spécifique à récupérer (optionnel) |
| `$default` | `mixed` | Valeur par défaut si la clé n'existe pas |

**Retourne :** 
- `StrictDataObject` - Si aucun `$key` n'est fourni
- `mixed` - Si une clé est fournie (valeur ou `$default`)

**Exemple :**
```php
public function getRecord(): AbstractRecord
{
    $validated = $this->validated();
    
    // Accès par propriété (recommandé)
    return CreateUserRecord::from([
        'name' => $validated->name,
        'email' => $validated->email,
    ]);
    
    // Accès par tableau également possible
    // $validated['name']
}
```

### `getValidated(): StrictDataObject`

Retourne les données validées sous forme de `StrictDataObject`. Alternative explicite à `validated()`.

**Retourne :** `StrictDataObject` - Données validées

**Exemple :**
```php
$validated = $request->getValidated();
echo $validated->email;
```

### `getRecord(): AbstractRecord`

Transforme la requête HTTP validée en un objet Record typé.

**Retourne :** `AbstractRecord` - Record contenant toutes les données de la requête

**Exceptions :** Aucune (mais la validation Laravel peut lever `ValidationException`)

**Exemple :**
```php
public function getRecord(): AbstractRecord
{
    $validated = $this->validated();
    
    return CreateUserRecord::from([
        'name' => $validated->name,
        'email' => $validated->email,
        'ipAddress' => $this->ip(),
    ]);
}
```

## Cas d'utilisation

### Cas 1 : Requête API avec validation et Record

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Users;

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use App\Records\CreateUserRecord;

final class CreateUserRequest extends AbstractRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function getRecord(): AbstractRecord
    {
        $validated = $this->validated();

        return CreateUserRecord::from([
            'name' => $validated->name,
            'email' => $validated->email,
            'password' => $validated->password,
            'ipAddress' => $this->ip(),
            'userAgent' => $this->userAgent(),
        ]);
    }
}
```

### Cas 2 : Requête avec paramètres d'URL typés

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Users;

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use App\Records\ShowUserRecord;

final class ShowUserRequest extends AbstractRequest
{
    public function rules(): array
    {
        return [
            'include' => ['string', 'in:posts,comments'],
        ];
    }

    public function getRecord(): AbstractRecord
    {
        $include = $this->input('include', '');

        return ShowUserRecord::from([
            'id' => (int) $this->route('id'),
            'includePosts' => str_contains($include, 'posts'),
            'includeComments' => str_contains($include, 'comments'),
        ]);
    }
}
```

### Cas 3 : Requête web avec données de session

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Dashboard;

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use App\Records\DashboardRecord;

final class DashboardRequest extends AbstractRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'period' => ['string', 'in:day,week,month'],
        ];
    }

    public function getRecord(): AbstractRecord
    {
        $validated = $this->validated();

        return DashboardRecord::from([
            'userId' => $this->user()->id,
            'period' => $validated->period ?? 'day',
            'preferences' => session()->get('dashboard_preferences', []),
        ]);
    }
}
```

### Cas 4 : Requête sans données (EmptyRequest)

```php
<?php

declare(strict_types=1);

use AndyDefer\Actions\Http\Requests\EmptyRequest;
use AndyDefer\Actions\Support\ActionRoute;

// Route pour health check sans données
ActionRoute::get('/health', EmptyRequest::class, HealthCheckAction::class);
```

### Cas 5 : Accès flexible aux données validées

```php
public function getRecord(): AbstractRecord
{
    $validated = $this->validated();
    
    // Accès par propriété (recommandé - plus lisible)
    $name = $validated->name;
    $email = $validated->email;
    
    // Accès par tableau (également possible)
    $name = $validated['name'];
    
    // Récupération avec valeur par défaut
    $age = $validated->age ?? 18;
    $age = $validated['age'] ?? 18;
    
    // Récupération via la méthode avec clé
    $name = $this->validated('name');
    $missing = $this->validated('missing', 'default');
    
    return CreateUserRecord::from([
        'name' => $name,
        'email' => $email,
    ]);
}
```

## Flux d'exécution

<img src="../../graphics/abstract-request.png" alt="Abstract Request Flow" width="800"/>

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Autorisation refusée | `Symfony\Component\HttpKernel\Exception\HttpException` | `This action is unauthorized.` (403) |
| Validation échouée | `Illuminate\Validation\ValidationException` | Messages d'erreur formatés selon les règles |
| Route parameter manquant | `Symfony\Component\HttpKernel\Exception\NotFoundHttpException` | (404) selon la route |
| Méthode `getRecord()` non implémentée | `Error` | `Class X contains 1 abstract method and must therefore be declared abstract` |

## Intégration

`AbstractRequest` s'intègre avec :

- **Laravel FormRequest** : Hérite de toutes les fonctionnalités de validation
- **ActionRoute** : Enregistre automatiquement la route avec la Request et l'Action
- **AbstractRecord** : Produit un Record typé pour l'Action
- **Conteneur Laravel** : Résout automatiquement les dépendances du constructeur
- **StrictDataObject** : Fournit un accès unifié aux données validées

## Performance

| Aspect | Caractéristique |
|--------|----------------|
| Validation | Une fois par requête (cachée par Laravel) |
| Autorisation | Une fois par requête |
| Transformation | `O(n)` avec n = nombre de propriétés du Record |
| Conversion en StrictDataObject | Négligeable (wrapper autour du tableau) |
| Mémoire | Une instance par requête (gérée par Laravel) |

## Compatibilité

| Version | Support |
|---------|---------|
| Laravel 10.x | ✅ Complet |
| Laravel 11.x | ✅ Complet |
| Laravel 12.x | ✅ Complet |
| Laravel 13.x | ✅ Complet |
| PHP 8.1+ | ✅ Requis |

## Exemple complet

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Users;

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use App\Records\UpdateUserRecord;

final class UpdateUserRequest extends AbstractRequest
{
    public function authorize(): bool
    {
        $userId = (int) $this->route('id');
        
        return $this->user()->can('update', User::findOrFail($userId));
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,' . $this->route('id')],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function getRecord(): AbstractRecord
    {
        $validated = $this->validated();

        return UpdateUserRecord::from([
            'id' => (int) $this->route('id'),
            'name' => $validated->name ?? null,
            'email' => $validated->email ?? null,
            'hasAvatar' => $this->hasFile('avatar'),
            'avatar' => $this->file('avatar'),
            'updatedBy' => $this->user()->id,
        ]);
    }
}

// Enregistrement dans routes/api.php
use function action_route;
use App\Http\Requests\Api\Users\UpdateUserRequest;
use App\Actions\Api\Users\UpdateUserAction;

Route::put('/api/users/{id}', action_route(UpdateUserRequest::class, UpdateUserAction::class))
    ->name('api.users.update');
```

## Bonnes pratiques

| Pratique | Recommandation |
|----------|----------------|
| **Accès aux données** | Utilisez `$validated->property` plutôt que `$validated['property']` pour une meilleure lisibilité |
| **Valeurs par défaut** | Utilisez `$validated->property ?? $default` ou le paramètre `$default` de `validated()` |
| **Règles de validation** | Définissez TOUJOURS les règles pour les champs que vous utilisez dans `getRecord()` |
| **EmptyRequest** | Utilisez `EmptyRequest` pour les routes sans données (health check, ping) |
| **Type hint** | Utilisez `/** @var YourRecord $record */` avant d'utiliser le Record dans l'Action |