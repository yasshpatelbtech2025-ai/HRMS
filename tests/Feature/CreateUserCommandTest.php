<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_staff_user(): void
    {
        $this->artisan('user:create', ['--name' => 'Jane Doe', '--email' => 'jane@example.com'])
            ->expectsQuestion('Password', 'secret-password')
            ->assertSuccessful();

        $user = User::where('email', 'jane@example.com')->firstOrFail();

        $this->assertSame('Jane Doe', $user->name);
        $this->assertTrue(Hash::check('secret-password', $user->password));
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_it_rejects_duplicate_emails(): void
    {
        User::factory()->create(['email' => 'jane@example.com']);

        $this->artisan('user:create', ['--name' => 'Jane Doe', '--email' => 'jane@example.com'])
            ->expectsQuestion('Password', 'secret-password')
            ->assertFailed();

        $this->assertSame(1, User::count());
    }
}
