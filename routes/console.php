<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

Artisan::command('flexclub:user {role : admin или trainer}', function () {
    $role = $this->argument('role');
    if (! in_array($role, ['admin', 'trainer'], true)) {
        $this->error('Доступные роли: admin, trainer.');

        return 1;
    }

    $name = trim((string) $this->ask('Имя и фамилия'));
    $email = Str::lower(trim((string) $this->ask('Email')));
    $password = (string) $this->secret('Пароль (не менее 8 символов)');
    $validator = Validator::make(compact('name', 'email', 'password'), [
        'name' => 'required|string|max:120',
        'email' => 'required|email|max:191|unique:users',
        'password' => 'required|string|min:8',
    ]);

    if ($validator->fails() || strlen($password) > 72 || str_contains($password, "\0")) {
        foreach ($validator->errors()->all() as $message) {
            $this->error($message);
        }
        if (strlen($password) > 72 || str_contains($password, "\0")) {
            $this->error('Пароль должен занимать не более 72 байт и не содержать нулевой символ.');
        }

        return 1;
    }

    $user = new User($validator->validated());
    $user->role = $role;
    $user->save();
    $this->info('Учётная запись создана.');

    return 0;
})->purpose('Создать учётную запись администратора или тренера');
