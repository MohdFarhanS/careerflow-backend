<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_returns_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name'                  => 'Budi',
            'email'                 => 'budi@example.com',
            'password'              => 'Password1',
            'password_confirmation' => 'Password1',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['message', 'user', 'token']);

        $this->assertNotEmpty($response->json('token'));
        $this->assertDatabaseHas('users', ['email' => 'budi@example.com']);
    }

    public function test_login_with_valid_credentials_returns_token(): void
    {
        User::factory()->create([
            'email'    => 'siti@example.com',
            'password' => Hash::make('Password1'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'siti@example.com',
            'password' => 'Password1',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['message', 'user', 'token']);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_login_with_invalid_credentials_is_unauthorized(): void
    {
        User::factory()->create([
            'email'    => 'siti@example.com',
            'password' => Hash::make('Password1'),
        ]);

        $this->postJson('/api/login', [
            'email'    => 'siti@example.com',
            'password' => 'salah-password',
        ])->assertUnauthorized();
    }

    public function test_protected_route_accessible_with_bearer_token(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_protected_route_rejects_request_without_token(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }
}
