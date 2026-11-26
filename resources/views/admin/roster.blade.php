@extends('layout')
@section('title', 'Посещения')
@section('content')
    <div class="workspace">
        <h1>{{ $slot->title }} · {{ $slot->gym->name }}</h1>
        <p>{{ $slot->starts_at->setTimezone(\App\Services\Memberships::TIMEZONE)->format('d.m.Y H:i') }}</p>
        @include('admin.nav')
        @php($statuses = ['booked' => 'Забронирована', 'cancelled' => 'Отменена', 'attended' => 'Посещена', 'missed' => 'Пропущена'])
        @forelse ($bookings as $booking)
            <article class="panel">
                <strong>{{ $booking->user->name }}</strong>
                <p>{{ $statuses[$booking->status] }}</p>
                @if ($booking->status === 'booked' && $slot->starts_at->lte(now()))
                    <form method="post" action="{{ route('admin.attendance', [$slot, $booking]) }}" class="filter">
                        @csrf
                        <button class="action" name="status" value="attended">Посещена</button>
                        <button class="action" name="status" value="missed">Пропущена</button>
                    </form>
                @endif
            </article>
        @empty
            <p>На эту тренировку пока нет записей.</p>
        @endforelse
        @include('partials.pagination', ['paginator' => $bookings])
    </div>
@endsection
