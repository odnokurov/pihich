@extends('layout')
@section('title', 'Тренеры')
@section('style', 'coaches')
@section('content')
    <section class="branches-banner-section container">
        <div class="branches-banner-container">
            <h1 class="branches-banner-title">Наши тренеры</h1>
        </div>
    </section>
    <section class="workspace">
        <form method="get" class="filter">
            <label>Филиал
                <select name="gym">
                    <option value="">Все филиалы</option>
                    @foreach ($gyms as $gym)
                        <option value="{{ $gym->id }}" @selected($selectedGym?->id === $gym->id)>{{ $gym->name }}</option>
                    @endforeach
                </select>
            </label>
            <button class="action">Показать</button>
        </form>
        <div class="coach-grid card-grid">
            @forelse ($trainers as $trainer)
                @include('partials.trainer')
            @empty
                <p>В этом разделе пока нет тренеров.</p>
            @endforelse
        </div>
    </section>
@endsection
