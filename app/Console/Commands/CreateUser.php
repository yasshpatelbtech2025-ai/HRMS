<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create
                            {--name= : The staff member\'s full name}
                            {--email= : The staff member\'s login email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a staff login (public registration is disabled)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $data = [
            'name' => $this->option('name') ?? $this->ask('Name'),
            'email' => $this->option('email') ?? $this->ask('Email'),
            'password' => $this->secret('Password'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = new User($validator->validated());
        $user->email_verified_at = now();
        $user->save();

        $this->info("Created user {$user->email}.");

        return self::SUCCESS;
    }
}
