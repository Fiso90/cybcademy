<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Modules\Employee\Models\User;
use App\Modules\Organisation\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Phase 7 Section 10 (Rate Limiting) specified aggressive per-IP/per-
 * account rate limiting on the login endpoint. AuthService::attemptLogin()
 * has implemented this since Epic E1, but - same gap as
 * MfaEnforcementTest - no prior test drove it through the actual HTTP
 * route, only called the service method directly. This proves the real
 * end-to-end behaviour a credential-stuffing bot would actually hit.
 */
final class LoginRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_failed_logins_from_the_same_source_are_eventually_rate_limited(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create([
            'password_hash' => Hash::make('correct-password'),
            'status' => 'active',
        ]);

        // AuthService allows 5 attempts before rate limiting (Epic E1).
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/login', ['email' => $user->email, 'password' => 'wrong-password']);
        }

        $response = $this->postJson('/login', ['email' => $user->email, 'password' => 'wrong-password']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
        $this->assertStringContainsString('Too many login attempts', $response->json('errors.email.0'));
    }

    public function test_a_correct_password_after_failed_attempts_but_before_the_limit_still_succeeds(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create([
            'password_hash' => Hash::make('correct-password'),
            'status' => 'active',
        ]);

        $this->postJson('/login', ['email' => $user->email, 'password' => 'wrong-1']);
        $this->postJson('/login', ['email' => $user->email, 'password' => 'wrong-2']);

        $response = $this->postJson('/login', ['email' => $user->email, 'password' => 'correct-password']);

        $response->assertRedirect(); // successful login redirects, per AuthController::login()
    }
}
