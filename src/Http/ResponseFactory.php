<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Http;

use AndyDefer\Actions\Enums\HttpResponseType;
use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\PhpVo\Enums\HttpStatusCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Factory for building HTTP responses in a declarative and testable way.
 */
final class ResponseFactory
{
    private HttpResponseType $type;

    private mixed $content;

    private int $status = 200;

    private array $headers = [];

    private function __construct(HttpResponseType $type, mixed $content)
    {
        $this->type = $type;
        $this->content = $content;
    }

    /**
     * Normalize a status code (int or HttpStatusCode) into a validated int.
     *
     * @throws InvalidArgumentException When the int is not a valid HTTP status code.
     */
    private static function normalizeStatus(int|HttpStatusCode $code): int
    {
        $value = $code instanceof HttpStatusCode ? $code->value : $code;

        if (HttpStatusCode::tryFrom($value) === null) {
            throw new InvalidArgumentException(
                sprintf('Invalid HTTP status code: %d', $value),
            );
        }

        return $value;
    }

    public static function json(AbstractData $data, int|HttpStatusCode $code = HttpStatusCode::OK): self
    {
        $instance = new self(HttpResponseType::JSON, $data);
        $instance->status = self::normalizeStatus($code);

        return $instance;
    }

    public static function redirect(string $url, int|HttpStatusCode $code = HttpStatusCode::FOUND): self
    {
        $instance = new self(HttpResponseType::REDIRECT, $url);
        $instance->status = self::normalizeStatus($code);

        return $instance;
    }

    public static function redirectRoute(string $route, array $parameters = [], int|HttpStatusCode $code = HttpStatusCode::FOUND): self
    {
        $instance = new self(HttpResponseType::REDIRECT_ROUTE, [
            'route' => $route,
            'parameters' => $parameters,
        ]);
        $instance->status = self::normalizeStatus($code);

        return $instance;
    }

    public static function redirectBack(int|HttpStatusCode $code = HttpStatusCode::FOUND): self
    {
        $instance = new self(HttpResponseType::REDIRECT_BACK, $code);
        $instance->status = self::normalizeStatus($code);

        return $instance;
    }

    public static function stream(callable $callback, string $contentType = 'application/octet-stream', int|HttpStatusCode $code = HttpStatusCode::OK): self
    {
        $instance = new self(HttpResponseType::STREAM, [
            'callback' => $callback,
            'contentType' => $contentType,
        ]);
        $instance->status = self::normalizeStatus($code);

        return $instance;
    }

    public static function sse(callable $callback): self
    {
        $instance = new self(HttpResponseType::SSE, $callback);
        $instance->status = HttpStatusCode::OK->value;

        return $instance;
    }

    public static function noContent(): self
    {
        $instance = new self(HttpResponseType::NO_CONTENT, null);
        $instance->status = HttpStatusCode::NO_CONTENT->value;

        return $instance;
    }

    public static function inertia(string $component, array $props = []): self
    {
        $instance = new self(HttpResponseType::INERTIA, [
            'component' => $component,
            'props' => self::normal($props),
        ]);
        $instance->status = HttpStatusCode::OK->value;

        return $instance;
    }

    protected static function normal(mixed $data): mixed
    {
        $prefilterd = action_normalizer_chain(true)->normalize($data);

        return normalizer_chain(true)->normalize($prefilterd);
    }

    public static function html(string $html, int|HttpStatusCode $code = HttpStatusCode::OK): self
    {
        $instance = new self(HttpResponseType::HTML, $html);
        $instance->status = self::normalizeStatus($code);

        return $instance;
    }

    public static function fileInline(string $filePath, ?string $fileName = null): self
    {
        $instance = new self(HttpResponseType::FILE_INLINE, [
            'path' => $filePath,
            'name' => $fileName,
        ]);
        $instance->status = HttpStatusCode::OK->value;

        return $instance;
    }

    public static function fileDownload(string $filePath, ?string $fileName = null): self
    {
        $instance = new self(HttpResponseType::FILE_DOWNLOAD, [
            'path' => $filePath,
            'name' => $fileName,
        ]);
        $instance->status = HttpStatusCode::OK->value;

        return $instance;
    }

    public static function text(string $content, int|HttpStatusCode $code = HttpStatusCode::OK): self
    {
        $instance = new self(HttpResponseType::TEXT, $content);
        $instance->status = self::normalizeStatus($code);

        return $instance;
    }

    public static function view(string $view, array $data = [], int|HttpStatusCode $code = HttpStatusCode::OK): self
    {
        $instance = new self(HttpResponseType::VIEW, [
            'view' => $view,
            'data' => $data,
        ]);
        $instance->status = self::normalizeStatus($code);

        return $instance;
    }

    public function withHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    public function withStatus(int|HttpStatusCode $code): self
    {
        $this->status = self::normalizeStatus($code);

        return $this;
    }

    public function getType(): HttpResponseType
    {
        return $this->type;
    }

    public function getContent(): mixed
    {
        return $this->content;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

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

    private function toJsonResponse(): JsonResponse
    {
        return response()->json($this->content->toArray(), $this->status, $this->headers);
    }

    private function toRedirectResponse(): RedirectResponse
    {
        return redirect($this->content, $this->status, $this->headers);
    }

    private function toRedirectRouteResponse(): RedirectResponse
    {
        return redirect()->route(
            $this->content['route'],
            $this->content['parameters'],
            $this->status,
            $this->headers
        );
    }

    private function toRedirectBackResponse(): RedirectResponse
    {
        return back($this->status, $this->headers);
    }

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

    private function toNoContentResponse(): Response
    {
        return response('', 204, $this->headers);
    }

    private function toInertiaResponse(): InertiaResponse
    {
        return Inertia::render($this->content['component'], $this->content['props']);
    }

    private function toHtmlResponse(): Response
    {
        return response(
            $this->content,
            $this->status,
            array_merge(['Content-Type' => 'text/html'], $this->headers)
        );
    }

    private function toFileInlineResponse(): BinaryFileResponse
    {
        $fileName = $this->content['name'] ?? basename($this->content['path']);

        return response()->file(
            $this->content['path'],
            array_merge([
                'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            ], $this->headers)
        );
    }

    private function toFileDownloadResponse(): BinaryFileResponse
    {
        $fileName = $this->content['name'] ?? basename($this->content['path']);

        return response()->download($this->content['path'], $fileName, $this->headers);
    }

    private function toTextResponse(): Response
    {
        return response(
            $this->content,
            $this->status,
            array_merge(['Content-Type' => 'text/plain'], $this->headers)
        );
    }

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
