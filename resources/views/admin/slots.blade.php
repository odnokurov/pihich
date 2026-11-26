@extends('layout')
@section('title', 'Расписание')
@section('content')
    <div class="workspace">
        <h1>Расписание</h1>
        @include('admin.nav')
        @include('admin.slot-form', ['slot' => new \App\Models\TrainingSlot])
        @forelse ($slots as $slot)
            <article class="panel">
                <strong>{{ $slot->title }} · {{ $slot->gym->name }}</strong>
                <p>{{ $slot->starts_at->setTimezone(\App\Services\Memberships::TIMEZONE)->format('d.m.Y H:i') }} · {{ $slot->trainer->user->name }} · Записей {{ $slot->occupied }} из {{ $slot->capacity }}</p>
                <a href="{{ route('admin.roster', $slot) }}">Записи и посещения</a>
                @include('admin.slot-form')
            </article>
        @empty
            <p>Слотов пока нет.</p>
        @endforelse
        @include('partials.pagination', ['paginator' => $slots])
    </div>
@endsection
