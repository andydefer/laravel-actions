# EmptyRequest - Référence Technique

## Description

Requête HTTP sans données, utilisée pour les routes qui n'ont besoin d'aucune entrée utilisateur.

## Hiérarchie

```
Illuminate\Foundation\Http\FormRequest
    └── AbstractRequest
        └── EmptyRequest (final)
```

## Rôle principal

Fournir une implémentation concrète de `AbstractRequest` pour les routes qui n'ont pas besoin de données utilisateur, de validation ou d'autorisation spécifique. Retourne systématiquement un `EmptyRecord`.

## Installation

```bash
composer require andydefer/laravel-actions
```

## API / Méthodes publiques

### `rules(): array`

Définit les règles de validation de la requête.

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `array<string, array<int, string>>` - Tableau vide (aucune validation)

**Exemple :**
```php
$request = new EmptyRequest();
$rules = $request->rules(); // []
```

### `getRecord(): AbstractRecord`

Transforme la requête en un objet Record typé.

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `AbstractRecord` - Une instance de `EmptyRecord`

**Exemple :**
```php
$request = new EmptyRequest();
$record = $request->getRecord(); // Instance de EmptyRecord
```

## Cas d'utilisation

### Cas 1 : Health check endpoint

```php
// Request
final class HealthCheckRequest extends EmptyRequest
{
    // Aucune méthode à surcharger
}

// Action
final class HealthCheckAction extends AbstractAction
{
    protected function handle(AbstractRecord $request): ResponseFactory
    {
        return ResponseFactory::json(['status' => 'ok', 'timestamp' => time()]);
    }
}

// Route
ActionRoute::get('/health', HealthCheckRequest::class, HealthCheckAction::class);
```

### Cas 2 : Ping endpoint

```php
// Action sans Request personnalisée
ActionRoute::get('/ping', EmptyRequest::class, PingAction::class);

// Action
final class PingAction extends AbstractAction
{
    protected function handle(AbstractRecord $request): ResponseFactory
    {
        return ResponseFactory::json(['pong' => true]);
    }
}
```

### Cas 3 : Webhook sans données

```php
// Route pour webhook déclencheur
ActionRoute::post('/webhook/trigger', EmptyRequest::class, TriggerWebhookAction::class);

// Action
final class TriggerWebhookAction extends AbstractAction
{
    public function __construct(
        private readonly WebhookService $webhookService
    ) {}
    
    protected function handle(AbstractRecord $request): ResponseFactory
    {
        $this->webhookService->execute();
        
        return ResponseFactory::noContent();
    }
}
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Aucune | - | - |

`EmptyRequest` ne lève aucune exception spécifique. Seules les exceptions standard de Laravel (404, 500) peuvent survenir.

## Intégration

`EmptyRequest` s'intègre avec :

- **`AbstractRequest`** : Hérite de toutes les méthodes
- **`ActionRoute`** : Peut être utilisée directement dans l'enregistrement des routes
- **`EmptyRecord`** : Produit un Record sans propriétés
- **`AbstractAction`** : Reçoit l'EmptyRecord dans `handle()`

## Performance

| Aspect | Caractéristique |
|--------|----------------|
| Validation | Aucune (tableau de règles vide) |
| Autorisation | Toujours true (hérité) |
| Transformation | O(1) - instancie simplement EmptyRecord |
| Mémoire | Instance légère, aucune propriété |

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

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\Requests\EmptyRequest;
use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\Actions\Support\ActionRoute;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

// 1. Définir l'Action
final class StatusAction extends AbstractAction
{
    public function __construct(
        private readonly StatusService $statusService
    ) {}
    
    protected function handle(AbstractRecord $request): ResponseFactory
    {
        $status = $this->statusService->getCurrentStatus();
        
        return ResponseFactory::json([
            'status' => $status,
            'service' => 'API Gateway',
            'version' => '1.0.0',
        ]);
    }
}

// 2. Enregistrer la route
ActionRoute::get('/api/status', EmptyRequest::class, StatusAction::class);

// 3. Tester avec curl
// curl -X GET https://api.example.com/api/status
// Réponse: {"status":"operational","service":"API Gateway","version":"1.0.0"}
```
---