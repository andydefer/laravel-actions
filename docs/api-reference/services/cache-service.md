```markdown
# CacheService - Référence Technique

## Description

Service de cache typé pour les objets `Transformable` du package `andydefer/laravel-actions`. Stocke et restaure automatiquement la classe concrète de la valeur mise en cache.

## Hiérarchie

```
CacheServiceInterface
    └── CacheService
```

## Rôle principal

Fournir une couche de cache qui préserve le type des objets stockés. Contrairement au cache Laravel standard (qui retourne des tableaux ou des scalaires), `CacheService` restaure l'instance d'origine via `::from()` après normalisation. Seuls les objets implémentant `Transformable` sont acceptés.

## Prérequis

- `Illuminate\Contracts\Cache\Repository` disponible dans le container.
- Helper global `action_normalizer_chain()` disponible.
- Un binding `CacheServiceInterface → CacheService` enregistré.

## API / Méthodes publiques

### `put(string $key, object $value, int $ttlSeconds): void`

Stocke un objet `Transformable` dans le cache sous la clé donnée.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé applicative (préfixée en interne par `actions:cache:`) |
| `$value` | `object` | Objet `Transformable` à stocker |
| `$ttlSeconds` | `int` | Durée de vie en secondes (clampée à 1 minimum) |

**Retourne :** `void`

**Exceptions :** `InvalidArgumentException` si `$value` n'implémente pas `Transformable`.

**Exemple :**
```php
$service->put('user.1', $user, 60);
```

### `remember(string $key, Closure $callback, int $ttlSeconds): object`

Retourne la valeur en cache si présente, sinon exécute le callback, stocke et retourne le résultat.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé applicative |
| `$callback` | `Closure` | Fonction produisant la valeur si absente |
| `$ttlSeconds` | `int` | Durée de vie en secondes |

**Retourne :** `object` - L'objet récupéré ou produit.

**Exceptions :** `InvalidArgumentException` si le callback ne retourne pas un objet, ou retourne un objet non-`Transformable`.

**Exemple :**
```php
$user = $service->remember('user.1', fn () => UserRecord::from([...]), 60);
```

### `get(string $key, string $class): ?object`

Récupère un objet depuis le cache et vérifie son appartenance à la classe demandée.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé applicative |
| `$class` | `string` | Classe attendue (ou interface parente) |

**Retourne :** `?object` - L'instance restaurée, ou `null` si absente, invalide ou de classe incompatible.

**Exceptions :** Aucune n'est levée directement. Tous les cas d'erreur retournent `null`.

**Exemple :**
```php
$user = $service->get('user.1', UserRecord::class);
```

### `has(string $key): bool`

Indique si une entrée existe dans le cache pour la clé donnée.

**Retourne :** `bool`

**Exemple :**
```php
if ($service->has('user.1')) {
    // …
}
```

### `forget(string $key): bool`

Supprime l'entrée de cache associée à la clé.

**Retourne :** `bool` - `true` si la suppression a réussi.

**Exemple :**
```php
$service->forget('user.1');
```

## Comportement de sérialisation

Chaque entrée est stockée sous la forme d'un tableau associatif :

```php
[
    'class'   => UserRecord::class,       // classe concrète de l'objet
    'payload' => [ 'name' => 'John', ... ] // normalisation via action_normalizer_chain(true)
]
```

À la restauration, `CacheService::get()` appelle `$cachedClass::from($payload)` pour reconstruire l'objet d'origine.

## Cas d'utilisation

### Cas 1 : Cacher un Record

```php
<?php

declare(strict_types=1);

use AndyDefer\Actions\Contracts\CacheServiceInterface;
use App\Records\UserRecord;

/** @var CacheServiceInterface $cache */
$cache = app(CacheServiceInterface::class);

$user = UserRecord::from([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 42,
]);

$cache->put('user.42', $user, 300);

$restored = $cache->get('user.42', UserRecord::class);
// $restored instanceof UserRecord, identique à $user
```

### Cas 2 : Cacher une Value Object

```php
use App\ValueObjects\EmailAddress;

$email = EmailAddress::from(['value' => 'john@example.com']);

$cache->put('email.42', $email, 300);

$restored = $cache->get('email.42', EmailAddress::class);
// $restored instanceof EmailAddress
```

### Cas 3 : Cacher une TypedCollection

```php
use App\Collections\UserRecordCollection;
use App\Records\UserRecord;

$collection = new UserRecordCollection;
$collection->add(UserRecord::from([...]));
$collection->add(UserRecord::from([...]));

$cache->put('users.active', $collection, 60);

$restored = $cache->get('users.active', UserRecordCollection::class);
// $restored instanceof UserRecordCollection, count = 2
```

### Cas 4 : Utiliser `remember` pour un calcul coûteux

```php
use AndyDefer\Actions\Contracts\CacheServiceInterface;
use App\Records\StatsRecord;

$stats = $cache->remember(
    'stats.daily',
    fn () => StatsRecord::from($this->computeExpensiveStats()),
    ttlSeconds: 3600,
);

// Premier appel : exécute le callback.
// Appels suivants : renvoie le StatsRecord en cache.
```

## Flux d'exécution

```
put($key, $value, $ttl)
    ↓
assertTransformable($value)
    ↓
action_normalizer_chain(true)->normalize($value) → payload
    ↓
cache->put('actions:cache:'.$key, ['class', 'payload'], $ttl)

get($key, $class)
    ↓
cache->get('actions:cache:'.$key)
    ├── non-array ou clés manquantes → null
    ├── class inexistante → null
    ├── class non sous-classe de $class → null
    └── sinon → $cachedClass::from($payload)

remember($key, $callback, $ttl)
    ↓
get($key, Transformable::class)
    ├── non-null → retourne la valeur
    └── null → $callback() → put() → retourne la valeur
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| `$value` n'est pas `Transformable` | `InvalidArgumentException` | `CacheService only accepts instances of AndyDefer\DomainStructures\Interfaces\Transformable. Got: <class>` |
| Callback de `remember()` ne retourne pas un objet | `InvalidArgumentException` | `CacheService only accepts objects implementing Transformable.` |
| `$cachedClass` n'existe pas | (aucune) | `get()` retourne `null` |
| `$cachedClass` incompatible avec `$class` | (aucune) | `get()` retourne `null` |
| Payload non-tableau | (aucune) | `get()` retourne `null` |

## Intégration

| Composant | Rôle |
|-----------|------|
| `CacheServiceInterface` | Contrat public du service |
| `Transformable` | Contrat requis pour les valeurs stockées |
| `action_normalizer_chain()` | Helper fourni par `andydefer/laravel-actions` pour la normalisation |
| `Illuminate\Contracts\Cache\Repository` | Backend de cache Laravel |

## Performance

- **Coût de `put()`** : 1 normalisation (`action_normalizer_chain`) + 1 écriture cache.
- **Coût de `get()`** : 1 lecture cache + 1 reconstruction via `::from()`.
- **Coût de `remember()`** : 1 lecture cache ; si miss, ajoute 1 exécution callback + 1 `put()`.
- **Complexité** : dépend de la taille de l'objet normalisé, généralement O(n) sur la structure.
- **Préfixe commun** : `actions:cache:` — évite les collisions avec d'autres services utilisant le même store.

## Compatibilité

| Version | Support |
|---------|---------|
| PHP 8.2+ | ✅ |
| Laravel 12+ | ✅ |
| `andydefer/laravel-actions` | ✅ Requis |
| `andydefer/domain-structures` | ✅ Requis |

## Exemple complet

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use AndyDefer\Actions\Contracts\CacheServiceInterface;
use App\Collections\UserRecordCollection;
use App\Records\UserRecord;

final class UserController extends Controller
{
    public function __construct(
        private readonly CacheServiceInterface $cache,
    ) {}

    public function show(int $id)
    {
        $user = $this->cache->remember(
            "user.{$id}",
            fn (): UserRecord => UserRecord::from([
                'name'  => "User {$id}",
                'email' => "user{$id}@example.com",
                'age'   => 30,
            ]),
            ttlSeconds: 300,
        );

        return response()->json([
            'name'  => $user->name,
            'email' => $user->email,
        ]);
    }

    public function invalidate(int $id)
    {
        $this->cache->forget("user.{$id}");

        return response()->noContent();
    }
}
```

## Voir aussi

- `CacheServiceInterface` - Contrat public
- `Transformable` - Contrat requis pour les valeurs stockées
```