<?php

namespace Tests\Feature;

use App\Models\LoginAttempt;
use App\Models\User;
use App\Services\GeoIpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_view_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
        ]);

        // GET /login
        $response = $this->get('/login');
        $response->assertStatus(200);

        // POST /login
        $loginResponse = $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);
        $loginResponse->assertRedirect('/dashboard');

        // GET /dashboard
        $dashboardResponse = $this->actingAs($user)->get('/dashboard');
        $dashboardResponse->assertStatus(200);

        // GET /dashboard?json=1
        $jsonResponse = $this->actingAs($user)->get('/dashboard?json=1');
        $jsonResponse->assertStatus(200);
        $jsonResponse->assertJsonStructure(['cpu_usage', 'memory_usage', 'disk_usage']);
    }

    public function test_login_attempt_country_is_safely_clamped_to_max_5_chars(): void
    {
        $attempt = LoginAttempt::create([
            'email' => 'test@example.com',
            'ip_address' => '1.2.3.4',
            'country' => 'UNKNOWN_STRING_THAT_IS_TOO_LONG',
            'success' => false,
        ]);

        $this->assertLessThanOrEqual(5, strlen($attempt->country));
        $this->assertEquals('UNKNO', $attempt->country);
    }

    public function test_login_does_not_throw_500_even_when_geoip_returns_unknown(): void
    {
        $user = User::factory()->create([
            'email' => 'admin2@test.com',
            'password' => bcrypt('secret123'),
        ]);

        // Mock GeoIP service to return 'UNKNOWN' (simulating old cached value or external failure)
        $this->mock(GeoIpService::class, function ($mock) {
            $mock->shouldReceive('getCountryCode')->andReturn('UNKNOWN');
        });

        $response = $this->post('/login', [
            'email' => 'admin2@test.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        // Check login attempt was safely recorded without error
        $lastAttempt = LoginAttempt::latest('id')->first();
        $this->assertNotNull($lastAttempt);
        $this->assertLessThanOrEqual(5, strlen($lastAttempt->country));
    }

    public function test_failed_login_does_not_throw_500(): void
    {
        User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('correctpassword'),
        ]);

        $this->mock(GeoIpService::class, function ($mock) {
            $mock->shouldReceive('getCountryCode')->andReturn('UNKNOWN');
        });

        $response = $this->from('/login')->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'wrongpass',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
    }

    public function test_iran_ip_user_is_never_blocked_even_when_iran_ip_restriction_is_enabled(): void
    {
        \App\Models\SecuritySetting::set('iran_ip_restriction', 'true');

        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('correctpassword'),
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '89.40.242.238'])->get('/login');
        $response->assertStatus(200);

        $loginResponse = $this->withServerVariables(['REMOTE_ADDR' => '89.40.242.238'])->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'correctpassword',
        ]);
        $loginResponse->assertRedirect('/dashboard');
    }
}
