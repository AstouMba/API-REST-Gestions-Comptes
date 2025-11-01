<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_login_requires_credentials()
    {
        $response = $this->postJson('/api/v1/' . config('api.name') . '/auth/login', []);

        $response->assertStatus(422);
    }

    public function test_refresh_without_token_returns_bad_request()
    {
        $response = $this->postJson('/api/v1/' . config('api.name') . '/auth/refresh', []);

        $response->assertStatus(400);
    }

    public function test_logout_unauthenticated_is_unauthorized()
    {
        $response = $this->postJson('/api/v1/' . config('api.name') . '/auth/logout', []);

        $response->assertStatus(401);
    }
}
