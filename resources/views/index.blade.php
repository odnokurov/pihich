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


<section class="branches-section catalog-section" id="branches">
    <div class="container">
        <h2 class="section-title branches-title">Наши филиалы</h2>
        @if ($selectedGym)
            <div class="branches-switcher" aria-label="Выбор филиала">
                @foreach ($gyms as $gym)
                    <a class="branch-radio @if($gym->id === $selectedGym->id) active @endif"
                       @if($gym->id === $selectedGym->id) aria-current="true" @endif
                       href="{{ route('home', ['gym' => $gym->id]) }}#branches">{{ $gym->name }}</a>
                @endforeach
            </div>
            <div class="branch-card">
                <img src="{{ asset($selectedGym->image_path ?: 'images/index/branch.png') }}" alt="Интерьер зала" class="branch-card-image">
                <div class="branch-card-text">
                    <h3>{{ $selectedGym->name }}</h3>
                    <p>{{ $selectedGym->address }}</p>
                    @if ($selectedGym->description)
                        <p>{{ $selectedGym->description }}</p>
                    @endif
                    <a class="action" href="{{ route('gyms', ['gym' => $selectedGym->id]) }}">О филиале</a>
                </div>
            </div>
        @else
            <p>Нет доступных филиалов.</p>
        @endif
    </div>
</section>
<section class="catalog-section">
    <div class="container">
        <div class="subscriptions-header">
            <h2 class="section-title subscriptions-title">Наши абонементы</h2>
            @if ($selectedGym)
                <span class="switcher-label">{{ $selectedGym->name }}</span>
            @endif
        </div>
        @include('partials.plan-cards')
        <div class="custom-subscription">
            @if ($customPlan)
                <h3 class="custom-title">Не нашли подходящий абонемент?</h3>
                <a class="custom-link" href="{{ route('plans', ['plan_id' => $customPlan->id, 'gym_id' => $selectedGym?->id]) }}">Составить свой абонемент</a>
            @else
                <a class="custom-link" href="{{ route('plans', ['gym_id' => $selectedGym?->id]) }}">Все тарифы</a>
            @endif
        </div>
    </div>
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
<section class="section coach-section catalog-section">
    <h2 class="section-title trainers-title">Наши тренеры</h2>
    <div class="coach-grid">
        @forelse ($trainers as $trainer)
            @include('partials.trainer')
        @empty
            <p>Карточки тренеров пока не добавлены.</p>
        @endforelse
    </div>
    <p class="catalog-footer"><a class="action" href="{{ route('trainers') }}">Все тренеры</a></p>
</section>
@endsection
