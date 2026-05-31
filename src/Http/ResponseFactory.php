<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Http;

use AndyDefer\Actions\Enums\HttpResponseType;
use AndyDefer\DomainStructures\Abstracts\AbstractData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Factory for building HTTP responses in a declarative and testable way.
 *
 * This factory abstracts away Laravel's global helper functions and provides
 * a fluent interface for constructing HTTP responses. Each factory instance
 * is immutable until converted to a real response object via toResponse().
 *
 * @example
 * $response = ResponseFactory::json(UserData::from($user), 201)
 *     ->withHeaders(['X-RateLimit' => '100'])
 *     ->toResponse();
 *
 * @author Andy Defer
 */
final class ResponseFactory
{
    private HttpResponseType $type;
    private mixed $content;
    private int $status = 200;
    private array $headers = [];

    /**
     * Private constructor forces use of named static constructors.
     *
     * @param HttpResponseType $type    The type of HTTP response to create
     * @param mixed            $content The raw content for the response
     */
    private function __construct(HttpResponseType $type, mixed $content)
    {
        $this->type = $type;
        $this->content = $content;
    }

    /**
     * Creates a JSON response for API endpoints.
     *
     * The AbstractData object is automatically converted to a camelCase array
     * when toResponse() is called.
     *
     * @param AbstractData $data The data to return (converted to array automatically)
     * @param int          $code HTTP status code (200, 201, 422, etc.)
     *
     * @return self Factory instance configured for JSON response
     */
    public static function json(AbstractData $data, int $code = 200): self
    {
        $instance = new self(HttpResponseType::JSON, $data);
        $instance->status = $code;

        return $instance;
    }

    /**
     * Creates a redirect response to an absolute URL.
     *
     * @param string $url  The destination URL
     * @param int    $code HTTP redirect status (301, 302, 303, 307, 308)
     *
     * @return self Factory instance configured for redirect response
     */
    public static function redirect(string $url, int $code = 302): self
    {
        $instance = new self(HttpResponseType::REDIRECT, $url);
        $instance->status = $code;

        return $instance;
    }

    /**
     * Creates a redirect response to a named route.
     *
     * @param string $route      The route name
     * @param array  $parameters Route parameters
     * @param int    $code       HTTP redirect status
     *
     * @return self Factory instance configured for route redirect
     */
    public static function redirectRoute(string $route, array $parameters = [], int $code = 302): self
    {
        $instance = new self(HttpResponseType::REDIRECT_ROUTE, [
            'route' => $route,
            'parameters' => $parameters,
        ]);
        $instance->status = $code;

        return $instance;
    }

    /**
     * Creates a redirect response back to the previous page.
     *
     * @param int $code HTTP redirect status
     *
     * @return self Factory instance configured for back redirect
     */
    public static function redirectBack(int $code = 302): self
    {
        $instance = new self(HttpResponseType::REDIRECT_BACK, $code);
        $instance->status = $code;

        return $instance;
    }

    /**
     * Creates a streaming response for large data or real-time output.
     *
     * Useful for streaming large files, generating CSV on the fly, or video streaming.
     *
     * @param callable $callback    Function that writes output to the response stream
     * @param string   $contentType MIME type of the streamed content
     * @param int      $code        HTTP status code
     *
     * @return self Factory instance configured for stream response
     */
    public static function stream(callable $callback, string $contentType = 'application/octet-stream', int $code = 200): self
    {
        $instance = new self(HttpResponseType::STREAM, [
            'callback' => $callback,
            'contentType' => $contentType,
        ]);
        $instance->status = $code;

        return $instance;
    }

    /**
     * Creates a Server-Sent Events (SSE) streaming response.
     *
     * SSE allows servers to push real-time events to clients over a single HTTP connection.
     * Useful for live notifications, real-time dashboards, or progress updates.
     *
     * @param callable $callback Function that emits SSE events using the SSE format
     *
     * @return self Factory instance configured for SSE response
     */
    public static function sse(callable $callback): self
    {
        $instance = new self(HttpResponseType::SSE, $callback);
        $instance->status = 200;

        return $instance;
    }

    /**
     * Creates a 204 No Content response.
     *
     * Used when the request was successful but there's no content to return,
     * typically after DELETE operations.
     *
     * @return self Factory instance configured for empty response
     */
    public static function noContent(): self
    {
        $instance = new self(HttpResponseType::NO_CONTENT, null);
        $instance->status = 204;

        return $instance;
    }

    /**
     * Creates an Inertia.js response for modern single-page applications.
     *
     * Renders a React/Vue component with server-side data when using Inertia.js.
     *
     * @param string $component Name of the React/Vue component to render
     * @param array  $props     Props to pass to the component
     *
     * @return self Factory instance configured for Inertia response
     */
    public static function inertia(string $component, array $props = []): self
    {
        $instance = new self(HttpResponseType::INERTIA, [
            'component' => $component,
            'props' => $props,
        ]);
        $instance->status = 200;

        return $instance;
    }

    /**
     * Creates a raw HTML response.
     *
     * Use this only for rare cases where Inertia.js is not suitable,
     * such as email previews, legacy views, or external integrations.
     *
     * @param string $html Raw HTML content to return
     * @param int    $code HTTP status code
     *
     * @return self Factory instance configured for HTML response
     */
    public static function html(string $html, int $code = 200): self
    {
        $instance = new self(HttpResponseType::HTML, $html);
        $instance->status = $code;

        return $instance;
    }

    /**
     * Creates a response that displays a file inline in the browser.
     *
     * The browser will attempt to display the file (PDF, image, video) directly
     * rather than downloading it.
     *
     * @param string      $filePath Absolute or relative path to the file
     * @param string|null $fileName Optional custom filename for inline display
     *
     * @return self Factory instance configured for inline file response
     */
    public static function fileInline(string $filePath, ?string $fileName = null): self
    {
        $instance = new self(HttpResponseType::FILE_INLINE, [
            'path' => $filePath,
            'name' => $fileName,
        ]);
        $instance->status = 200;

        return $instance;
    }

    /**
     * Creates a response that forces a file to be downloaded by the browser.
     *
     * The browser will save the file to disk rather than displaying it.
     *
     * @param string      $filePath Absolute or relative path to the file
     * @param string|null $fileName Optional custom filename for the downloaded file
     *
     * @return self Factory instance configured for file download response
     */
    public static function fileDownload(string $filePath, ?string $fileName = null): self
    {
        $instance = new self(HttpResponseType::FILE_DOWNLOAD, [
            'path' => $filePath,
            'name' => $fileName,
        ]);
        $instance->status = 200;

        return $instance;
    }

    /**
     * Creates a plain text response.
     *
     * Useful for API endpoints that return raw text, logs, or configuration files.
     *
     * @param string $content Text content to return
     * @param int    $code    HTTP status code
     *
     * @return self Factory instance configured for text response
     */
    public static function text(string $content, int $code = 200): self
    {
        $instance = new self(HttpResponseType::TEXT, $content);
        $instance->status = $code;

        return $instance;
    }

    /**
     * Creates a Blade view response.
     *
     * Prefer using Inertia for modern applications. This method exists for
     * legacy views or simple integrations.
     *
     * @param string $view View name
     * @param array  $data Data to pass to the view
     * @param int    $code HTTP status code
     *
     * @return self Factory instance configured for view response
     */
    public static function view(string $view, array $data = [], int $code = 200): self
    {
        $instance = new self(HttpResponseType::VIEW, [
            'view' => $view,
            'data' => $data,
        ]);
        $instance->status = $code;

        return $instance;
    }

    /**
     * Adds HTTP headers to the response.
     *
     * This method is fluent and returns the same instance for chaining.
     *
     * @param array<string, string> $headers Associative array of header names to values
     *
     * @return self Same instance for method chaining
     */
    public function withHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    /**
     * Changes the HTTP status code of the response.
     *
     * @param int $code HTTP status code
     *
     * @return self Same instance for method chaining
     */
    public function withStatus(int $code): self
    {
        $this->status = $code;

        return $this;
    }

    /**
     * Returns the type of HTTP response this factory will produce.
     *
     * @return HttpResponseType The response type enum
     */
    public function getType(): HttpResponseType
    {
        return $this->type;
    }

    /**
     * Returns the raw content stored in this factory.
     *
     * The content type varies depending on the response type:
     * - JSON: AbstractData instance
     * - Redirect: string URL
     * - RedirectRoute: array with 'route' and 'parameters' keys
     * - View: array with 'view' and 'data' keys
     * - File: array with 'path' and 'name' keys
     *
     * @return mixed The raw content
     */
    public function getContent(): mixed
    {
        return $this->content;
    }

    /**
     * Returns the HTTP status code.
     *
     * @return int HTTP status code
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Returns all HTTP headers configured for this response.
     *
     * @return array<string, string> Associative array of headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Converts the factory configuration into an actual HTTP response object.
     *
     * This method uses Laravel's global helper functions (response(), redirect(), back())
     * to create the appropriate Symfony/Illuminate response object.
     *
     * @return JsonResponse|RedirectResponse|Response|InertiaResponse|BinaryFileResponse|StreamedResponse
     */
    public function toResponse(): mixed
    {
        return match ($this->type) {
            HttpResponseType::JSON => $this->toJsonResponse(),
            HttpResponseType::REDIRECT => $this->toRedirectResponse(),
            HttpResponseType::REDIRECT_ROUTE => $this->toRedirectRouteResponse(),
            HttpResponseType::REDIRECT_BACK => $this->toRedirectBackResponse(),
            HttpResponseType::STREAM => $this->toStreamResponse(),
            HttpResponseType::SSE => $this->toSseResponse(),
            HttpResponseType::NO_CONTENT => $this->toNoContentResponse(),
            HttpResponseType::INERTIA => $this->toInertiaResponse(),
            HttpResponseType::HTML => $this->toHtmlResponse(),
            HttpResponseType::FILE_INLINE => $this->toFileInlineResponse(),
            HttpResponseType::FILE_DOWNLOAD => $this->toFileDownloadResponse(),
            HttpResponseType::TEXT => $this->toTextResponse(),
            HttpResponseType::VIEW => $this->toViewResponse(),
        };
    }

    /**
     * Converts to a JSON response.
     *
     * @return JsonResponse
     */
    private function toJsonResponse(): JsonResponse
    {
        return response()->json($this->content->toArray(), $this->status, $this->headers);
    }

    /**
     * Converts to a redirect response to a URL.
     *
     * @return RedirectResponse
     */
    private function toRedirectResponse(): RedirectResponse
    {
        return redirect($this->content, $this->status, $this->headers);
    }

    /**
     * Converts to a redirect response to a named route.
     *
     * @return RedirectResponse
     */
    private function toRedirectRouteResponse(): RedirectResponse
    {
        return redirect()->route(
            $this->content['route'],
            $this->content['parameters'],
            $this->status,
            $this->headers
        );
    }

    /**
     * Converts to a redirect response back to the previous page.
     *
     * @return RedirectResponse
     */
    private function toRedirectBackResponse(): RedirectResponse
    {
        return back($this->status, $this->headers);
    }

    /**
     * Converts to a streaming response.
     *
     * @return StreamedResponse
     */
    private function toStreamResponse(): StreamedResponse
    {
        return response()->stream(
            $this->content['callback'],
            $this->status,
            array_merge([
                'Content-Type' => $this->content['contentType'],
                'X-Accel-Buffering' => 'no',
            ], $this->headers)
        );
    }

    /**
     * Converts to a Server-Sent Events streaming response.
     *
     * @return StreamedResponse
     */
    private function toSseResponse(): StreamedResponse
    {
        return response()->stream(
            $this->content,
            200,
            array_merge([
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ], $this->headers)
        );
    }

    /**
     * Converts to a 204 No Content response.
     *
     * @return Response
     */
    private function toNoContentResponse(): Response
    {
        return response('', 204, $this->headers);
    }

    /**
     * Converts to an Inertia response.
     *
     * @return InertiaResponse
     */
    private function toInertiaResponse(): InertiaResponse
    {
        return Inertia::render($this->content['component'], $this->content['props']);
    }

    /**
     * Converts to an HTML response.
     *
     * @return Response
     */
    private function toHtmlResponse(): Response
    {
        return response(
            $this->content,
            $this->status,
            array_merge(['Content-Type' => 'text/html'], $this->headers)
        );
    }

    /**
     * Converts to a file inline response.
     *
     * @return BinaryFileResponse
     */
    private function toFileInlineResponse(): BinaryFileResponse
    {
        $fileName = $this->content['name'] ?? basename($this->content['path']);

        return response()->file(
            $this->content['path'],
            array_merge([
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            ], $this->headers)
        );
    }

    /**
     * Converts to a file download response.
     *
     * @return BinaryFileResponse
     */
    private function toFileDownloadResponse(): BinaryFileResponse
    {
        $fileName = $this->content['name'] ?? basename($this->content['path']);

        return response()->download($this->content['path'], $fileName, $this->headers);
    }

    /**
     * Converts to a plain text response.
     *
     * @return Response
     */
    private function toTextResponse(): Response
    {
        return response(
            $this->content,
            $this->status,
            array_merge(['Content-Type' => 'text/plain'], $this->headers)
        );
    }

    /**
     * Converts to a Blade view response.
     *
     * @return Response
     */
    private function toViewResponse(): Response
    {
        return response()->view(
            $this->content['view'],
            $this->content['data'],
            $this->status,
            $this->headers
        );
    }
}
