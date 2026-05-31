<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Actions;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\Actions\Tests\Fixtures\Data\TestAdminUsersData;
use AndyDefer\Actions\Tests\Fixtures\Data\TestAjaxData;
use AndyDefer\Actions\Tests\Fixtures\Data\TestApiWebData;
use AndyDefer\Actions\Tests\Fixtures\Data\TestCastData;
use AndyDefer\Actions\Tests\Fixtures\Data\TestCookieData;
use AndyDefer\Actions\Tests\Fixtures\Data\TestFlashData;
use AndyDefer\Actions\Tests\Fixtures\Data\TestFormData;
use AndyDefer\Actions\Tests\Fixtures\Data\TestSearchData;
use AndyDefer\Actions\Tests\Fixtures\Data\TestSessionData;
use AndyDefer\Actions\Tests\Fixtures\Data\TestUploadData;
use AndyDefer\Actions\Tests\Fixtures\Data\TestUserData;
use AndyDefer\Actions\Tests\Fixtures\Records\TestWebRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Hydration\Strategy\SingleParameterStrategy;

final class TestWebAction extends AbstractAction
{
    private const EXACT_MATCH_ROUTES = [
        'dashboard' => ['html', '<h1>Dashboard</h1>'],
        'about' => ['html', '<h1>About Us</h1>'],
        'contact' => ['html', '<h1>Contact</h1><p>contact@example.com</p>'],
        'products' => ['html', '<h1>Products List</h1>'],
        'admin/users' => ['json', TestAdminUsersData::class, ['users' => []]],
        'api/web-data' => ['json', TestApiWebData::class, ['data' => ['id' => 1, 'name' => 'Test'], 'message' => 'Success']],
        'special-chars' => ['html', '<h1>&lt;script&gt;alert("test")&lt;/script&gt;</h1>'],
    ];

    protected function handle(AbstractRecord $request): ResponseFactory
    {
        SingleParameterStrategy::class;
        /** @var TestWebRecord $request */

        // Exact match
        if (isset(self::EXACT_MATCH_ROUTES[$request->uri])) {
            return $this->buildResponse(self::EXACT_MATCH_ROUTES[$request->uri], $request);
        }

        // Pattern matches
        if ($match = $this->matchPattern($request)) {
            return $match;
        }

        // POST routes
        if ($request->method === 'POST') {
            return $this->handlePost($request);
        }

        // Default
        return $this->defaultResponse();
    }

    private function matchPattern(TestWebRecord $request): ?ResponseFactory
    {
        // Products/{id}
        if (str_starts_with($request->uri, 'products/') && $request->id) {
            return ResponseFactory::html('<h1>Product '.$request->id.'</h1>', 200);
        }

        // Users/{userId}/profile
        if (str_contains($request->uri, 'profile') && $request->userId) {
            return ResponseFactory::html('<h1>User '.$request->userId.' Profile</h1>', 200);
        }

        // Page/{id}
        if (str_starts_with($request->uri, 'page/') && $request->id) {
            return ResponseFactory::html('<h1>Page '.$request->id.'</h1>', 200);
        }

        // Cast
        if ($request->castInt !== null && $request->castFloat !== null) {
            return $this->castResponse($request);
        }

        // Ajax
        if ($request->uri === 'ajax-data') {
            return $this->ajaxResponse($request);
        }

        // Search
        if ($request->uri === 'search') {
            return $this->searchResponse($request);
        }

        // Cookie
        if ($request->uri === 'cookie-test') {
            return $this->cookieResponse($request);
        }

        // Flash
        if ($request->uri === 'flash-test') {
            return $this->flashResponse($request);
        }

        // Session
        if ($request->uri === 'session-test') {
            return $this->sessionResponse($request);
        }

        return null;
    }

    private function buildResponse(array $config, TestWebRecord $request): ResponseFactory
    {
        [$type, $content] = $config;

        if ($type === 'html') {
            return ResponseFactory::html($content, 200);
        }

        $dataClass = $content;
        $params = $config[2] ?? [];

        $data = $dataClass::from($params);

        return ResponseFactory::json($data);
    }

    private function castResponse(TestWebRecord $request): ResponseFactory
    {
        $data = TestCastData::from([
            'castInt' => $request->castInt,
            'castFloat' => $request->castFloat,
            'castBoolTrue' => $request->castBoolTrue ?? false,
            'castBoolFalse' => $request->castBoolFalse ?? false,
        ]);

        return ResponseFactory::json($data);
    }

    private function ajaxResponse(TestWebRecord $request): ResponseFactory
    {
        if ($request->ajax) {
            return ResponseFactory::json(TestAjaxData::from(['data' => 'ajax response']));
        }

        return ResponseFactory::html('<h1>AJAX Data</h1>', 200);
    }

    private function searchResponse(TestWebRecord $request): ResponseFactory
    {
        $data = TestSearchData::from([
            'searchQuery' => $request->query['q'] ?? '',
            'currentPage' => (int) ($request->query['page'] ?? 1),
        ]);

        return ResponseFactory::json($data);
    }

    private function cookieResponse(TestWebRecord $request): ResponseFactory
    {
        $data = TestCookieData::from([
            'preference' => $request->cookie['preference'] ?? null,
        ]);

        return ResponseFactory::json($data);
    }

    private function flashResponse(TestWebRecord $request): ResponseFactory
    {
        $data = TestFlashData::from([
            'flashMessage' => $request->session['flash_message'] ?? null,
        ]);

        return ResponseFactory::json($data);
    }

    private function sessionResponse(TestWebRecord $request): ResponseFactory
    {
        $data = TestSessionData::from([
            'userId' => $request->session['user_id'] ?? null,
            'userName' => $request->session['user_name'] ?? null,
        ]);

        return ResponseFactory::json($data);
    }

    private function handlePost(TestWebRecord $request): ResponseFactory
    {
        return match ($request->uri) {
            'submit-form' => $this->submitFormResponse($request),
            'upload' => ResponseFactory::json(TestUploadData::from(['message' => 'Upload successful'])),
            default => $this->defaultResponse(),
        };
    }

    private function submitFormResponse(TestWebRecord $request): ResponseFactory
    {
        $data = TestFormData::from([
            'submittedName' => $request->input['name'] ?? '',
            'submittedEmail' => $request->input['email'] ?? '',
        ]);

        return ResponseFactory::json($data);
    }

    private function defaultResponse(): ResponseFactory
    {
        $data = TestUserData::from([
            'id' => '1',
            'name' => 'Welcome User',
            'email' => 'welcome@example.com',
            'status' => 'active',
            'role' => 'user',
            'grade' => 1,
            'emailVerifiedAt' => null,
            'tags' => [],
            'createdAt' => now()->toIso8601ZuluString(),
        ]);

        return ResponseFactory::json($data);
    }
}
