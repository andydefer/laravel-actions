Vous avez parfaitement raison ! La méthode `ResponseFactory::json()` attend un `AbstractData`, pas un tableau brut.

Voici la documentation corrigée :

# Fonction helper `action_factory()` - Référence Technique

## Description

Fonction utilitaire qui retourne une closure contenant une `ResponseFactory` préconfigurée. Idéale pour les routes simples qui n'ont pas besoin d'une classe Action dédiée.

## Syntaxe

```php
action_factory(ResponseFactory $responseFactory): Closure
```

## Paramètres

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$responseFactory` | `ResponseFactory` | Instance préconfigurée de ResponseFactory |

## Retour

| Type | Description |
|------|-------------|
| `Closure` | Une closure qui retourne la réponse HTTP convertie |

## Règle d'utilisation

**Une ligne = action_factory()**  
**Deux lignes ou plus = Créer une Action complète**

```php
// ✅ Acceptable - Une ligne
Route::get('/health', action_factory(ResponseFactory::json(HealthData::from(['status' => 'ok']), 200)));

// ✅ Acceptable - Une ligne
Route::get('/redirect', action_factory(ResponseFactory::redirectRoute('home')));

// ✅ Acceptable - Une ligne
Route::get('/home', action_factory(ResponseFactory::view('welcome')));

// ❌ À éviter - Deux lignes, préférer une Action dédiée
Route::get('/complex', action_factory(
    ResponseFactory::view('dashboard', $heavyData)
        ->withHeaders(['X-Custom' => 'value'])
));
```

## Cas d'utilisation typiques

### 1. Health check / Status endpoint

```php
use App\Data\HealthData;

Route::get('/health', action_factory(
    ResponseFactory::json(HealthData::from(['status' => 'ok', 'timestamp' => now()->toIso8601String()]), 200)
));
```

### 2. Redirections simples

```php
Route::get('/redirect', action_factory(ResponseFactory::redirectRoute('home')));
Route::get('/old-url', action_factory(ResponseFactory::redirect('/new-url', 301)));
Route::get('/back', action_factory(ResponseFactory::redirectBack()));
```

### 3. Vues statiques

```php
Route::get('/', action_factory(ResponseFactory::view('welcome')));
Route::get('/about', action_factory(ResponseFactory::view('pages.about')));
Route::get('/contact', action_factory(ResponseFactory::view('pages.contact')));
```

### 4. Fichiers statiques

```php
Route::get('/resume', action_factory(
    ResponseFactory::fileInline(storage_path('documents/resume.pdf'))
));

Route::get('/export', action_factory(
    ResponseFactory::fileDownload(storage_path('exports/users.csv'), 'utilisateurs.csv')
));
```

### 5. Routes texte brut

```php
Route::get('/robots.txt', action_factory(
    ResponseFactory::text("User-agent: *\nDisallow: /private/")
));

Route::get('/security.txt', action_factory(
    ResponseFactory::text("Contact: security@example.com\nExpires: 2025-12-31")
));
```

### 6. Routes HTML simples

```php
Route::get('/maintenance', action_factory(
    ResponseFactory::html('<h1>Maintenance</h1><p>Revenez bientôt</p>', 503)
));
```

### 7. Réponses 204 No Content

```php
Route::delete('/api/resource/{id}', action_factory(ResponseFactory::noContent()));
```

### 8. Avec nommage de route et middleware

```php
use App\Data\HealthData;

Route::get('/health', action_factory(
    ResponseFactory::json(HealthData::from(['status' => 'ok']), 200)
))->name('api.health')->middleware('api');
```

## Exemples concrets avec DTO

### Créer un DTO pour la réponse

```php
// app/Data/HealthData.php
<?php

declare(strict_types=1);

namespace App\Data;

use AndyDefer\DomainStructures\Abstracts\AbstractData;

final class HealthData extends AbstractData
{
    public function __construct(
        public readonly string $status,
        public readonly string $timestamp,
    ) {}
}
```

### Utilisation dans la route

```php
use App\Data\HealthData;

Route::get('/health', action_factory(
    ResponseFactory::json(
        HealthData::from([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String()
        ]), 
        200
    )
))->name('api.health');
```

### Exemple avec des données plus complexes

```php
// app/Data/ServerInfoData.php
<?php

declare(strict_types=1);

namespace App\Data;

use AndyDefer\DomainStructures\Abstracts\AbstractData;

final class ServerInfoData extends AbstractData
{
    public function __construct(
        public readonly string $environment,
        public readonly string $version,
        public readonly bool $maintenance,
    ) {}
}

// Route
use App\Data\ServerInfoData;

Route::get('/info', action_factory(
    ResponseFactory::json(
        ServerInfoData::from([
            'environment' => app()->environment(),
            'version' => app()->version(),
            'maintenance' => app()->isDownForMaintenance()
        ]),
        200
    )
));
```

## Exemples d'utilisation en une ligne

```php
use function action_factory;
use AndyDefer\Actions\Http\ResponseFactory;
use App\Data\HealthData;
use App\Data\PingData;

// API simple avec DTO
Route::get('/api/ping', action_factory(
    ResponseFactory::json(PingData::from(['pong' => true, 'time' => microtime(true)]), 200)
));

// Redirection
Route::get('/dashboard', action_factory(ResponseFactory::redirectRoute('admin.dashboard')));

// Vue simple
Route::get('/legal', action_factory(ResponseFactory::view('legal.terms')));

// Fichier inline
Route::get('/invoice/{id}', action_factory(
    ResponseFactory::fileInline(storage_path("invoices/{$id}.pdf"))
));

// Téléchargement
Route::get('/download/report', action_factory(
    ResponseFactory::fileDownload(storage_path('reports/daily.csv'))
));

// 204 No Content
Route::post('/webhook', action_factory(ResponseFactory::noContent()));

// Texte simple
Route::get('/robots.txt', action_factory(
    ResponseFactory::text("User-agent: *\nDisallow: /admin/")
));
```

## Quand utiliser vs ne pas utiliser

| ✅ À utiliser (une ligne) | ❌ À éviter (préférer Action) |
|---------------------------|------------------------------|
| Health checks avec DTO simple | Logique métier complexe |
| Redirections simples | Accès base de données |
| Vues statiques sans données | Validation de données |
| Fichiers statiques | Calculs ou transformations |
| Endpoints de maintenance | Appels à des services externes |
| Routes texte de config | Plus de 2 middlewares |
| Webhooks sans traitement | Headers personnalisés complexes |

## Important : ResponseFactory::json() nécessite AbstractData

```php
// ❌ Erreur - Tableau brut non accepté
Route::get('/health', action_factory(
    ResponseFactory::json(['status' => 'ok'], 200)
));

// ✅ Correct - Utilisation d'un DTO
Route::get('/health', action_factory(
    ResponseFactory::json(HealthData::from(['status' => 'ok']), 200)
));

// ✅ Correct - Utilisation d'une collection de DTO
Route::get('/users', action_factory(
    ResponseFactory::json(UserData::collection($users), 200)
));
```

## Bonnes pratiques

1. **Créez des DTO dédiés** : Même pour les réponses simples, créez un petit DTO
2. **Une ligne maximum** : Si ça prend plus d'une ligne, créez une Action
3. **Nommez vos routes** : Utilisez `->name()` pour les routes importantes
4. **Typez vos réponses** : Utilisez `AbstractData` avec `::from()` ou `::collection()`

## Exemple de DTO pour health check

```php
// app/Data/HealthData.php
<?php

declare(strict_types=1);

namespace App\Data;

use AndyDefer\DomainStructures\Abstracts\AbstractData;

final class HealthData extends AbstractData
{
    public function __construct(
        public readonly string $status,
        public readonly string $timestamp,
    ) {}
}
```

## Note importante

`ResponseFactory::json()` n'accepte **PAS** les tableaux bruts. Vous devez toujours utiliser un DTO qui étend `AbstractData` avec `::from()` ou `::collection()`. C'est une contrainte du package pour garantir la cohérence des types et la transformation automatique en camelCase.