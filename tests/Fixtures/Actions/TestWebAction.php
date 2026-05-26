<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Actions;

use AndyDefer\Actions\Actions\AbstractAction;
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
use AndyDefer\Actions\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\Actions\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\Actions\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\Actions\Tests\Fixtures\Records\TestWebRecord;
use AndyDefer\Records\Recordable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class TestWebAction extends AbstractAction
{
    protected function handle(Recordable $request): Response|JsonResponse
    {
        /** @var TestWebRecord $request */

        // Route dashboard (sans slash)
        if ($request->uri === 'dashboard') {
            return $this->html('<h1>Dashboard</h1>', 200);
        }

        // Route about
        if ($request->uri === 'about') {
            return $this->html('<h1>About Us</h1>', 200);
        }

        // Route contact
        if ($request->uri === 'contact') {
            return $this->html('<h1>Contact</h1><p>contact@example.com</p>', 200);
        }

        // Route products (sans param)
        if ($request->uri === 'products') {
            return $this->html('<h1>Products List</h1>', 200);
        }

        // Route products/{id}
        if (str_starts_with($request->uri, 'products/') && $request->id) {
            return $this->html('<h1>Product '.$request->id.'</h1>', 200);
        }

        // Route users/{userId}/profile
        if (str_contains($request->uri, 'profile') && $request->userId) {
            return $this->html('<h1>User '.$request->userId.' Profile</h1>', 200);
        }

        // Route page/{id}
        if (str_starts_with($request->uri, 'page/') && $request->id) {
            return $this->html('<h1>Page '.$request->id.'</h1>', 200);
        }

        // Route cast - doit être AVANT les conditions génériques
        if ($request->castInt !== null && $request->castFloat !== null) {
            return $this->json(new TestCastData(
                castInt: $request->castInt,
                castFloat: $request->castFloat,
                castBoolTrue: $request->castBoolTrue ?? false,
                castBoolFalse: $request->castBoolFalse ?? false,
            ));
        }

        // Route search
        if ($request->uri === 'search') {
            return $this->json(new TestSearchData(
                searchQuery: $request->query['q'] ?? '',
                currentPage: (int) ($request->query['page'] ?? 1),
            ));
        }

        // Route submit-form POST
        if ($request->method === 'POST' && $request->uri === 'submit-form') {
            return $this->json(new TestFormData(
                submittedName: $request->input['name'] ?? '',
                submittedEmail: $request->input['email'] ?? '',
            ));
        }

        // Route upload POST
        if ($request->method === 'POST' && $request->uri === 'upload') {
            return $this->json(new TestUploadData(
                message: 'Upload successful',
            ));
        }

        // Route admin/users
        if ($request->uri === 'admin/users') {
            return $this->json(new TestAdminUsersData(users: []));
        }

        // Route ajax-data
        if ($request->uri === 'ajax-data') {
            if ($request->ajax) {
                return $this->json(new TestAjaxData(data: 'ajax response'));
            }

            return $this->html('<h1>AJAX Data</h1>', 200);
        }

        // Route api/web-data
        if ($request->uri === 'api/web-data') {
            return $this->json(new TestApiWebData(
                data: ['id' => 1, 'name' => 'Test'],
                message: 'Success',
            ));
        }

        // Route cookie-test
        if ($request->uri === 'cookie-test') {
            return $this->json(new TestCookieData(
                preference: $request->cookie['preference'] ?? null,
            ));
        }

        // Route flash-test
        if ($request->uri === 'flash-test') {
            return $this->json(new TestFlashData(
                flashMessage: $request->session['flash_message'] ?? null,
            ));
        }

        // Route session-test
        if ($request->uri === 'session-test') {
            return $this->json(new TestSessionData(
                userId: $request->session['user_id'] ?? null,
                userName: $request->session['user_name'] ?? null,
            ));
        }

        // Route special-chars
        if ($request->uri === 'special-chars') {
            return $this->html('<h1>&lt;script&gt;alert("test")&lt;/script&gt;</h1>', 200);
        }

        // Route par défaut
        return $this->json(new TestUserData(
            id: '1',
            name: 'Welcome User',
            email: 'welcome@example.com',
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: [],
            createdAt: now()->toIso8601ZuluString(),
        ));
    }
}
