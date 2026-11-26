<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $this->credentials($request);
        $key = 'login:'.hash('sha256', $credentials['email'].'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Слишком много попыток. Повторите через минуту.']);
        }

        if (! Auth::attempt($credentials)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'Неверный email или пароль.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return $this->destination($request);
    }

    public function register(Request $request)
    {
        $credentials = $this->credentials($request, true);

        try {
            $user = User::create($credentials);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => 'Этот email уже зарегистрирован.']);
        }

        Auth::login($user->refresh());
        $request->session()->regenerate();

        return $this->destination($request);
    }

    private function credentials(Request $request, bool $register = false): array
    {
        if (is_string($request->email)) {
            $request->merge(['email' => Str::lower(trim($request->email))]);
        }

        return $request->validate([
            'email' => $register ? 'required|string|email|max:191|unique:users' : 'required|string|email|max:191',
            'password' => ['required', 'string', function ($attribute, $value, $fail) {
                if (strlen($value) > 72 || str_contains($value, "\0")) {
                    $fail('Пароль должен занимать не более 72 байт и не содержать нулевой символ.');
                }
            }, ...($register ? ['min:8', 'confirmed'] : [])],
            ...($register ? ['name' => 'required|string|max:120'] : []),
        ]);
    }

    private function destination(Request $request)
    {
        $selection = $request->session()->pull('flexclub.plan_selection');
        $request->session()->forget('url.intended');

        return match ($request->user()->role) {
            'admin' => redirect()->route('admin'),
            'trainer' => redirect()->route('chat.index'),
            default => $selection ? redirect()->route('plans', $selection) : redirect()->route('cabinet'),
        };
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
