<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_tier_boundaries(): void
    {
        $this->assertSame('Noob', User::tierFor(0));
        $this->assertSame('Noob', User::tierFor(99));
        $this->assertSame('Pro', User::tierFor(100));
        $this->assertSame('Pro', User::tierFor(299));
        $this->assertSame('Hacker', User::tierFor(300));
        $this->assertSame('Hacker', User::tierFor(599));
        $this->assertSame('God', User::tierFor(600));
    }

    public function test_register_creates_mahasiswa_and_cannot_set_role_or_points(): void
    {
        $this->post('/register', [
            'name' => 'Budi',
            'npm' => '2410000001',
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role' => 'admin',
            'points' => 9999,
        ])->assertRedirect('/');

        $user = User::firstWhere('email', 'budi@example.com');
        $this->assertSame('mahasiswa', $user->role);
        $this->assertSame(0, $user->points);
        $this->assertAuthenticatedAs($user);
    }

    public function test_validation_messages_are_indonesian(): void
    {
        $this->post('/register', [])->assertSessionHasErrors(['npm' => 'NPM wajib diisi.']);
    }

    public function test_login_redirects_by_role(): void
    {
        $admin = User::factory()->admin()->create();
        $mhs = User::factory()->create();

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect('/');
        $this->get('/')->assertRedirect(route('admin.dashboard'));
        $this->post('/logout');

        $this->post('/login', ['email' => $mhs->email, 'password' => 'password']);
        $this->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_wrong_password_is_rejected(): void
    {
        $mhs = User::factory()->create();

        $this->post('/login', ['email' => $mhs->email, 'password' => 'salah'])
            ->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.']);
        $this->assertGuest();
    }

    public function test_login_throttles_only_failed_attempts(): void
    {
        $mhs = User::factory()->create();

        foreach (range(1, 6) as $_) {
            $this->post('/login', ['email' => $mhs->email, 'password' => 'password'])->assertRedirect('/');
            $this->post('/logout');
        }

        foreach (range(1, 5) as $_) {
            $this->post('/login', ['email' => $mhs->email, 'password' => 'salah']);
        }
        $this->post('/login', ['email' => $mhs->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_throttle_shows_inline_error_not_429_page(): void
    {
        foreach (range(1, 5) as $_) {
            $this->from('/register')->post('/register', []);
        }

        $this->from('/register')->post('/register', ['name' => 'Budi'])
            ->assertRedirect('/register')
            ->assertSessionHasErrors('throttle')
            ->assertSessionHasInput('name', 'Budi');
    }

    public function test_role_middleware_returns_custom_403(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertForbidden()
            ->assertSee('Akses ditolak');

        $this->actingAs(User::factory()->admin()->create())
            ->get('/dashboard')
            ->assertForbidden();
    }

    public function test_unknown_url_returns_custom_404(): void
    {
        $this->get('/url-ngawur')->assertNotFound()->assertSee('Halaman tidak ditemukan');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/leaderboard')->assertRedirect(route('login'));
    }

    public function test_profile_and_leaderboard_render(): void
    {
        $me = User::factory()->create(['points' => 650]);
        User::factory()->create(['points' => 120]);

        $this->actingAs($me)->get('/profile')->assertOk()->assertSee('God')->assertSee('#1');
        $this->actingAs($me)->get('/leaderboard')->assertOk()->assertSee($me->name)->assertSee('Pro');
    }

    public function test_profile_update_keeps_password_when_blank(): void
    {
        $me = User::factory()->create();
        $hash = $me->password;

        $this->actingAs($me)->put('/profile', ['name' => 'Nama Baru', 'email' => $me->email])
            ->assertRedirect(route('profile.show'));

        $me->refresh();
        $this->assertSame('Nama Baru', $me->name);
        $this->assertSame($hash, $me->password);
    }
}
