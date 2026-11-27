<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · FlexClub</title>
    <link rel="stylesheet" href="{{ asset('style/'.trim($__env->yieldContent('style', 'index')).'.css') }}">
    <link rel="stylesheet" href="{{ asset('style/flexclub.css') }}">
    @stack('scripts')
</head>
<body>
<header>
    <div class="header-container">
        <a class="logo" href="{{ route('home') }}">FlexClub</a>
        <nav aria-label="Основное меню">
            <ul class="nav-menu">
                <li><a href="{{ route('gyms') }}">Залы</a></li>
                <li><a href="{{ route('plans') }}">Абонемент</a></li>
                <li><a href="{{ route('trainers') }}">Тренеры</a></li>
                <li><a href="{{ route('about') }}">О нас</a></li>
                @auth
                    <li><a href="{{ route('dashboard') }}">Кабинет</a></li>
                    <li><form method="post" action="{{ route('logout') }}">@csrf <button class="login-btn">Выйти</button></form></li>
                @else
                    <li><a class="action" href="{{ route('login') }}">Войти</a></li>
                @endauth
            </ul>
        </nav>
    </div>
</header>
<main>
    @if (session('status'))
        <p class="notice" role="status">{{ session('status') }}</p>
    @endif
    @if (($errors ?? null)?->any())
        <ul class="notice notice-error" role="alert">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif
    @yield('content')
</main>
@include('footer')
</body>
</html>
