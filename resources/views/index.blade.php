@extends('layout')
@section('title', 'Главная')
@section('style', 'index')
@section('content')
<section class="banner">
    <div class="banner-content">
        <h1 class="banner-title">ТРЕНИРОВКИ</h1>
        <h1 class="banner-title">С ОСОЗНАННЫМ ПОДХОДОМ</h1>
        <p class="banner-subtitle">ДРУГОЙ ПОДХОД К ТРЕНИРОВКАМ</p>
        <a href="{{ route('plans') }}" class="cta-button">Оформить абонемент</a>
    </div>
</section>


<section class="workspace">
    <h2>Наши филиалы</h2>
    <div class="card-grid">
        @forelse ($gyms as $gym)
            <article class="panel">
                <h3>{{ $gym->name }}</h3>
                <p>{{ $gym->address }}</p>
                <a href="{{ route('gyms', ['gym' => $gym->id]) }}">О филиале</a>
            </article>
        @empty
            <p>Нет доступных филиалов.</p>
        @endforelse
    </div>
    <h2>Наши абонементы</h2>
    <div class="card-grid">
        @forelse ($plans as $plan)
            <article class="panel">
                <h3>{{ $plan->name }}</h3>
                <p>{{ number_format($plan->price_kopecks / 100, $plan->price_kopecks % 100 ? 2 : 0, ',', ' ') }} ₽</p>
                <a href="{{ route('plans', ['plan_id' => $plan->id]) }}">Выбрать</a>
            </article>
        @empty
            <p>Нет доступных пакетов.</p>
        @endforelse
    </div>
    <p class="filter"><a href="{{ route('plans') }}">Все тарифы и конструктор</a></p>
</section>
<section class="services-section">

        <h2 class="section-title services-title">Что мы можем предложить</h2>

        <div class="services-grid">
            <div class="service-item">
                <img src="{{ asset('images/index/service-1.png') }}" alt="Тренажерный зал" class="service-image">
                <h3 class="service-name">Тренажерный зал</h3>
            </div>

            <div class="service-item">
                <img src="{{ asset('images/index/service-2.png') }}" alt="Персональные тренировки" class="service-image">
                <h3 class="service-name">Персональные тренировки</h3>
            </div>

            <div class="service-item">
                <img src="{{ asset('images/index/service-3.png') }}" alt="Силовые тренировки" class="service-image">
                <h3 class="service-name">Силовые тренировки</h3>
            </div>

            <div class="service-item">
                <img src="{{ asset('images/index/service-4.png') }}" alt="Кардио тренировки" class="service-image">
                <h3 class="service-name">Кардио тренировки</h3>
            </div>

            <div class="service-item">
                <img src="{{ asset('images/index/service-5.png') }}" alt="Функциональный тренинг" class="service-image">
                <h3 class="service-name">Функциональный тренинг</h3>
            </div>

            <div class="service-item">
                <img src="{{ asset('images/index/service-6.png') }}" alt="Кроссфит" class="service-image">
                <h3 class="service-name">Кроссфит</h3>
            </div>
        </div>
</section>
<section class="workspace">
    <h2>Наши тренеры</h2>
    <div class="card-grid">
        @forelse ($trainers as $trainer)
            @include('partials.trainer')
        @empty
            <p>Карточки тренеров пока не добавлены.</p>
        @endforelse
    </div>
    <p class="filter"><a href="{{ route('trainers') }}">Все тренеры</a></p>
</section>
@endsection
