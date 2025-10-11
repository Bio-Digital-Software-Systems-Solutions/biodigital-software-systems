<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTestUser extends Command
{
    protected $signature = 'make:test-user';

    protected $description = 'Create a test user with article permissions';

    public function handle()
    {
        $user = User::updateOrCreate([
            'email' => 'test@test.com',
        ], [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@test.com',
            'password' => Hash::make('password'),
        ]);

        $user->givePermissionTo([
            'view articles',
            'create articles',
            'edit articles',
            'delete articles',
        ]);

        $this->info('Test user created: test@test.com / password');
    }
}
