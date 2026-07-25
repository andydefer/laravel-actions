# PaginationBuilderService - Référence Technique

## Description

Service de pagination qui construit automatiquement les métadonnées de pagination pour les requêtes Eloquent, incluant les URLs de navigation (page précédente/suivante).

## Hiérarchie / Implémentations

```
Sans héritage - Classe finale
Dépendances : Request, Builder, UrlVO
```

## Rôle principal

Centralise la logique de pagination en Laravel en fournissant une API unique qui :
- Récupère les paramètres `current_page` et `per_page` de la requête HTTP
- Exécute la pagination sur un `Builder` Eloquent
- Construit les URLs complètes pour la navigation (pages suivante/précédente)
- Normalise les items via `action_normalizer_chain()`
- Retourne un DTO typé `PaginationData`

## Installation

```bash
composer require andydefer/laravel-actions
```

Le service est automatiquement disponible via le conteneur Laravel.

## API / Méthodes publiques

### `build(Builder $query): PaginationData`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$query` | `Builder` | Query Builder Eloquent à paginer |

**Retourne :** `PaginationData` - DTO contenant les items normalisés et les métadonnées de pagination

**Exceptions :** `RuntimeException` - Si aucune route n'est trouvée pour construire les URLs

**Exemple :**
```php
<?php

declare(strict_types=1);

use AndyDefer\Actions\Services\PaginationBuilderService;
use App\Models\User;

$service = app(PaginationBuilderService::class);
$result = $service->build(User::query());

// $result->items => Collection d'items normalisés
// $result->currentPage => 1
// $result->perPage => 5
// $result->total => 42
// $result->lastPage => 9
// $result->nextPageUrl => UrlVO('http://localhost/users?current_page=2&per_page=5')
// $result->prevPageUrl => null
```

## Cas d'utilisation

### Cas 1 : Liste paginée d'utilisateurs

```php
<?php

declare(strict_types=1);

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\Actions\Services\PaginationBuilderService;
use App\Models\User;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class ListUsersAction extends AbstractAction
{
    public function __construct(
        private readonly PaginationBuilderService $paginationUrlBuilder,
    ) {}

    protected function handle(AbstractRecord $request): ResponseFactory
    {
        $query = User::query()->where('active', true);

        $paginationMeta = $this->paginationUrlBuilder->build($query);

        return ResponseFactory::json($paginationMeta);
    }
}
```

### Cas 2 : Pagination avec filtres

```php
<?php

declare(strict_types=1);

use AndyDefer\Actions\Services\PaginationBuilderService;
use App\Models\Product;

$service = app(PaginationBuilderService::class);

// Les filtres sont automatiquement conservés dans les URLs
$query = Product::query()
    ->where('category', 'electronics')
    ->where('price', '>', 100);

$result = $service->build($query);

// L'URL de la page suivante conservera les filtres
// /products?current_page=2&per_page=5&category=electronics&price=100
```

### Cas 3 : Utilisation dans un contrôleur Laravel

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use AndyDefer\Actions\Services\PaginationBuilderService;
use App\Models\Post;
use Illuminate\Http\JsonResponse;

final class PostController
{
    public function __construct(
        private readonly PaginationBuilderService $paginationUrlBuilder,
    ) {}

    public function index(): JsonResponse
    {
        $query = Post::query()->with('author');
        $paginationMeta = $this->paginationUrlBuilder->build($query);
        
        return $paginationMeta->toResponse();
    }
}
```

## Flux d'exécution

```
Requête HTTP (current_page, per_page)
    ↓
build(Builder $query)
    ↓
Récupération des paramètres de pagination
    ↓
$query->paginate($perPage, ['*'], 'page', $currentPage)
    ↓
resolveNextPageUrl() → buildUrl()
    ├── Si hasMorePages() → route() + paramètres
    └── Sinon → null
    ↓
resolvePrevPageUrl() → buildUrl()
    ├── Si currentPage > 1 → route() + paramètres
    └── Sinon → null
    ↓
buildResponse()
    ↓
Normalisation des items → action_normalizer_chain(true)
    ↓
PaginationData (DTO)
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Aucune route trouvée | `RuntimeException` | `Unable to build pagination URL: no route found.` |

## Intégration

### Dépendances

- **`action_normalizer_chain()`** : Helper pour normaliser les items (provient du package `laravel-actions`)
- **`UrlVO`** : Value Object pour les URLs (provient du package `php-client`)
- **`PaginationData`** : DTO de sortie (provient du package `laravel-actions`)
- **`Sequential`** : Collection typée (provient du package `domain-structures`)

### Points d'extension

Le service peut être facilement étendu via :
1. **Surcharge** des méthodes privées (en créant une sous-classe)
2. **Injection** d'une implémentation alternative de `UrlVO`

## Performance

- **Complexité** : O(n) où n est le nombre d'items (normalisation)
- **Requêtes** : 1 requête SQL pour le COUNT + 1 requête pour les items
- **Cache** : Aucun cache intégré, mais peut être combiné avec le cache de requêtes Eloquent
- **Normalisation** : S'exécute en O(n) via `action_normalizer_chain()`

## Compatibilité

| Version PHP | Support |
|-------------|---------|
| PHP 8.1+ | ✅ Complet |
| PHP 8.2+ | ✅ Complet |
| PHP 8.3+ | ✅ Complet |
| PHP 8.4+ | ✅ Complet |
| PHP 8.5+ | ✅ Complet |

| Version Laravel | Support |
|-----------------|---------|
| Laravel 10.x | ✅ Complet |
| Laravel 11.x | ✅ Complet |
| Laravel 12.x | ✅ Complet |
| Laravel 13.x | ✅ Complet |
| Laravel 14.x | ✅ Complet |
| Laravel 15.x | ✅ Complet |

## Exemple complet

```php
<?php

declare(strict_types=1);

use AndyDefer\Actions\Services\PaginationBuilderService;
use App\Models\Article;
use Illuminate\Http\Request;

final class ArticleController extends Controller
{
    public function __construct(
        private readonly PaginationBuilderService $paginationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        // Construction de la requête avec relations
        $query = Article::query()
            ->with(['author', 'category'])
            ->where('published', true)
            ->orderBy('published_at', 'desc');

        // Application des filtres
        if ($category = $request->input('category')) {
            $query->where('category_id', $category);
        }

        if ($search = $request->input('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        // Pagination
        $paginationMeta = $this->paginationService->build($query);

        return response()->json([
            'data' => $paginationMeta->items,
            'meta' => [
                'current_page' => $paginationMeta->currentPage,
                'per_page' => $paginationMeta->perPage,
                'total' => $paginationMeta->total,
                'last_page' => $paginationMeta->lastPage,
                'next_page_url' => $paginationMeta->nextPageUrl?->getValue(),
                'prev_page_url' => $paginationMeta->prevPageUrl?->getValue(),
            ],
        ]);
    }
}

// Exemple d'appel avec paramètres
// GET /api/articles?current_page=2&per_page=10&category=1&search=php
```

## Voir aussi

- `PaginationData` - DTO de sortie
- `UrlVO` - Value Object pour les URLs
- `action_normalizer_chain()` - Helper de normalisation
- `AbstractAction` - Classe de base pour les actions
- `ResponseFactory` - Fabrique de réponses HTTP