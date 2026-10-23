<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_user_seeder_is_idempotent(): void
    {
        config()->set('chatbot.demo_user', [
            'name' => 'Usuário Demo',
            'email' => 'demo@example.com',
            'password' => 'senha-exclusiva-de-teste',
        ]);

        $this->seed(DemoUserSeeder::class);
        $this->seed(DemoUserSeeder::class);

        $this->assertSame(1, User::query()->where('email', 'demo@example.com')->count());

        $user = User::query()->where('email', 'demo@example.com')->firstOrFail();

        $this->assertSame('Usuário Demo', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('senha-exclusiva-de-teste', $user->password));
    }
}
