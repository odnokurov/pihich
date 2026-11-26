<section class="login-section">
    <div class="container">
        <div class="login-card">
            <h1 class="login-title">{{ $register ? 'Регистрация' : 'Вход в личный кабинет' }}</h1>
            <form method="post" action="{{ route($register ? 'register' : 'login') }}" class="login-form form-stack">
                @csrf
                @if ($register)
                    <label>Имя и фамилия
                        <input type="text" name="name" maxlength="120" value="{{ old('name') }}" autocomplete="name" required>
                    </label>
                @endif
                <label>Email
                    <input type="email" name="email" maxlength="191" value="{{ old('email') }}" autocomplete="email" required>
                </label>
                <label>Пароль
                    <input type="password" name="password" autocomplete="{{ $register ? 'new-password' : 'current-password' }}" @if($register) minlength="8" @endif required>
                </label>
                @if ($register)
                    <label>Подтверждение пароля
                        <input type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required>
                    </label>
                @endif
                <button class="action">{{ $register ? 'Зарегистрироваться' : 'Войти' }}</button>
            </form>
            <p class="login-register">
                @if ($register)
                    Уже есть аккаунт? <a href="{{ route('login') }}">Войти</a>
                @else
                    Впервые у нас? <a href="{{ route('register') }}">Зарегистрироваться</a>
                @endif
            </p>
        </div>
    </div>
</section>
