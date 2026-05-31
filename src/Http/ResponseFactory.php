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

    public static function json(AbstractData $data, int $code = 200): self
    {
        $instance = new self(HttpResponseType::JSON, $data);
        $instance->status = $code;

        return $instance;
    }

    public static function redirect(string $url, int $code = 302): self
    {
        $instance = new self(HttpResponseType::REDIRECT, $url);
        $instance->status = $code;

        return $instance;
    }

    public static function redirectRoute(string $route, array $parameters = [], int $code = 302): self
    {
        $instance = new self(HttpResponseType::REDIRECT_ROUTE, ['route' => $route, 'parameters' => $parameters]);
        $instance->status = $code;

        return $instance;
    }

    public static function redirectBack(int $code = 302): self
    {
        $instance = new self(HttpResponseType::REDIRECT_BACK, $code);
        $instance->status = $code;

        return $instance;
    }

    public static function stream(callable $callback, string $contentType = 'application/octet-stream', int $code = 200): self
    {
        $instance = new self(HttpResponseType::STREAM, ['callback' => $callback, 'contentType' => $contentType]);
        $instance->status = $code;

        return $instance;
    }

    public static function sse(callable $callback): self
    {
        $instance = new self(HttpResponseType::SSE, $callback);
        $instance->status = 200;

        return $instance;
    }

    public static function noContent(): self
    {
        $instance = new self(HttpResponseType::NO_CONTENT, null);
        $instance->status = 204;

        return $instance;
    }

    public static function inertia(string $component, array $props = []): self
    {
        $instance = new self(HttpResponseType::INERTIA, ['component' => $component, 'props' => $props]);
        $instance->status = 200;

        return $instance;
    }

    public static function html(string $html, int $code = 200): self
    {
        $instance = new self(HttpResponseType::HTML, $html);
        $instance->status = $code;

        return $instance;
    }

    public static function fileInline(string $filePath, ?string $fileName = null): self
    {
        $instance = new self(HttpResponseType::FILE_INLINE, ['path' => $filePath, 'name' => $fileName]);
        $instance->status = 200;

        return $instance;
    }

    public static function fileDownload(string $filePath, ?string $fileName = null): self
    {
        $instance = new self(HttpResponseType::FILE_DOWNLOAD, ['path' => $filePath, 'name' => $fileName]);
        $instance->status = 200;

        return $instance;
    }

    public static function text(string $content, int $code = 200): self
    {
        $instance = new self(HttpResponseType::TEXT, $content);
        $instance->status = $code;

        return $instance;
    }

    public static function view(string $view, array $data = [], int $code = 200): self
    {
        $instance = new self(HttpResponseType::VIEW, ['view' => $view, 'data' => $data]);
        $instance->status = $code;

        return $instance;
    }

    public function withHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    public function withStatus(int $code): self
    {
        $this->status = $code;

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
        return redirect()->route($this->content['route'], $this->content['parameters'], $this->status, $this->headers);
    }

    private function toRedirectBackResponse(): RedirectResponse
    {
        return back($this->status, $this->headers);
    }

    private function toStreamResponse(): StreamedResponse
    {
        return response()->stream($this->content['callback'], $this->status, array_merge([
            'Content-Type' => $this->content['contentType'],
            'X-Accel-Buffering' => 'no',
        ], $this->headers));
    }

    private function toSseResponse(): StreamedResponse
    {
        return response()->stream($this->content, 200, array_merge([
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ], $this->headers));
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
        return response($this->content, $this->status, array_merge(['Content-Type' => 'text/html'], $this->headers));
    }

    private function toFileInlineResponse(): BinaryFileResponse
    {
        $fileName = $this->content['name'] ?? basename($this->content['path']);

        return response()->file($this->content['path'], array_merge([
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ], $this->headers));
    }

    private function toFileDownloadResponse(): BinaryFileResponse
    {
        $fileName = $this->content['name'] ?? basename($this->content['path']);

        return response()->download($this->content['path'], $fileName, $this->headers);
    }

    private function toTextResponse(): Response
    {
        return response($this->content, $this->status, array_merge(['Content-Type' => 'text/plain'], $this->headers));
    }

    private function toViewResponse(): Response
    {
        return response()->view($this->content['view'], $this->content['data'], $this->status, $this->headers);
    }
}
