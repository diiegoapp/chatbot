<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $demoUser = config('chatbot.demo_user');

        foreach (['name', 'email', 'password'] as $field) {
            if (! is_string($demoUser[$field] ?? null) || trim($demoUser[$field]) === '') {
                $variable = 'DEMO_USER_'.strtoupper($field);

                throw new RuntimeException("{$variable} deve ser configurado para criar o usuário demo.");
            }
        }

        User::query()->updateOrCreate(
            ['email' => $demoUser['email']],
            [
                'name' => $demoUser['name'],
                'password' => $demoUser['password'],
                'email_verified_at' => now(),
            ],
        );
    }
}
