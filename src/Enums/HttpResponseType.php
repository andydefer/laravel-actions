<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Enums;

/**
 * Enum représentant toutes les méthodes HTTP response disponibles.
 *
 * Cet enum liste les différentes méthodes de réponse HTTP qui peuvent être
 * utilisées pour générer des réponses cohérentes dans les contrôleurs et actions.
 *
 * @author Andy Defer
 */
enum HttpResponseType: string
{
    case JSON = 'json';
    case REDIRECT = 'redirect';
    case REDIRECT_ROUTE = 'redirectRoute';
    case REDIRECT_BACK = 'redirectBack';
    case STREAM = 'stream';
    case SSE = 'sse';
    case NO_CONTENT = 'noContent';
    case INERTIA = 'inertia';
    case HTML = 'html';
    case FILE_INLINE = 'fileInline';
    case FILE_DOWNLOAD = 'fileDownload';
    case TEXT = 'text';
    case VIEW = 'view';
}
