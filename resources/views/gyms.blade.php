@extends('layout')
@section('title', 'Залы')
@section('style', 'gyms')
@section('content')
<section class="branches-banner-section container">
    <div class="branches-banner-container">
        <h1 class="branches-banner-title">Наши залы</h1>
        @if ($selectedGym)
            <p class="branches-banner-subtitle">Выберите филиал</p>
            <div class="branches-banner-switcher" aria-label="Выбор филиала">
                @foreach ($gyms as $gym)
                    <a class="branch-radio-banner @if($gym->id === $selectedGym->id) active @endif"
                       @if($gym->id === $selectedGym->id) aria-current="true" @endif
                       href="{{ route('gyms', ['gym' => $gym->id]) }}">{{ $gym->name }}</a>
                @endforeach
            </div>
        @else
            <p>Нет доступных филиалов.</p>
        @endif
    </div>
</section>
@if ($selectedGym)
    <section class="workspace gym-details">
        <h2>{{ $selectedGym->name }}</h2>
        <p>{{ $selectedGym->address }}</p>
        @if ($selectedGym->description)
            <p>{{ $selectedGym->description }}</p>
        @endif
        @if ($selectedGym->image_path)
            <img class="gym-photo" src="{{ asset($selectedGym->image_path) }}" alt="{{ $selectedGym->name }}">
        @endif
        <p class="filter">
            <a class="outline-action" href="{{ route('trainers', ['gym' => $selectedGym->id]) }}">Тренеры филиала</a>
            <a class="action" href="{{ route('plans', ['gym_id' => $selectedGym->id]) }}">Выбрать абонемент</a>
        </p>
    </section>
@endif
<section class="map-section">
    <div class="container">
        <h2 class="section-title map-title">Мы на карте</h2>

        <div class="map-card">
            <img src="{{ asset('images/gyms/map.png') }}" alt="Карта филиалов FlexClub в Томске" class="map-image">
        </div>
    </div>
</section>
<section class="gallery-section">

    <h2 class="section-title">Галерея клуба</h2>

    <div class="gallery-grid">
        <div class="gallery-item">
            <img src="{{ asset('images/gyms/gallery-1.png') }}" alt="Фото" class="service-image">
        </div>

        <div class="gallery-item">
            <img src="{{ asset('images/gyms/gallery-2.png') }}" alt="Фото" class="service-image">
        </div>

        <div class="gallery-item">
            <img src="{{ asset('images/gyms/gallery-3.png') }}" alt="Фото" class="service-image">
        </div>

        <div class="gallery-item">
            <img src="{{ asset('images/gyms/gallery-4.png') }}" alt="Фото" class="service-image">
        </div>

        <div class="gallery-item">
            <img src="{{ asset('images/gyms/gallery-5.png') }}" alt="Фото" class="service-image">
        </div>

        <div class="gallery-item">
            <img src="{{ asset('images/gyms/gallery-6.png') }}" alt="Фото" class="service-image">
        </div>
    </div>
</section>
@endsection
