@extends('layout')
@section('title', 'Личный кабинет')
@section('style', 'cabinet')
@section('content')
@php
    $tz = \App\Services\Memberships::TIMEZONE;
    $memberships = ['pending' => 'На рассмотрении', 'approved' => 'Одобрена', 'rejected' => 'Отклонена'];
    $statuses = ['booked' => 'Забронирована', 'cancelled' => 'Отменена', 'missed' => 'Пропущена'];
    $byDay = $slots->groupBy(fn ($slot) => $slot->starts_at->setTimezone($tz)->day);
    $leading = $month->dayOfWeekIso - 1;
    $weeks = (int) ceil(($leading + $month->daysInMonth) / 7);
@endphp
<div class="workspace client-cabinet">
    <div class="profile-header">
        <span class="profile-avatar"><img src="{{ asset('images/anon.png') }}" alt=""></span>
        <h1 class="profile-name">{{ auth()->user()->name }}</h1>
    </div>
    <nav aria-label="Разделы кабинета" class="filter cabinet-nav">
        <a href="#memberships">Абонементы</a>
        <a href="#schedule">Расписание</a>
        <a href="#bookings">Мои тренировки</a>
        <a href="#history">История посещений</a>
        <a href="{{ route('chat.index') }}">Чат</a>
    </nav>
    <section id="memberships" class="date-list">
        <div class="cabinet-heading">
            <h2>Заявки и абонементы</h2>
            <a class="action" href="{{ route('plans') }}">Выбрать абонемент</a>
        </div>
        @forelse ($applications as $application)
            <article class="profile-card">
                <h3 class="subscription-badge">{{ $application->plan->name }} · {{ $application->gym->name }}</h3>
                <p>{{ $memberships[$application->status] }} · {{ number_format($application->price_kopecks / 100, 2, ',', ' ') }} ₽ · Лимит {{ $application->visits_limit }}</p>
                @if ($application->status === 'approved')
                    <p>{{ $application->start_on }} — {{ $application->end_on }} (дата окончания не входит в срок)</p>
                    <div class="subscription-stats">
                        <p class="stat-item"><span class="stat-label">Остаток тренировок:</span> <strong class="stat-value">{{ max(0, $application->visits_limit - $application->used_visits) }}</strong></p>
                    </div>
                @elseif ($application->status === 'rejected')
                    <p>Причина отказа: {{ $application->rejection_reason }}</p>
                @endif
            </article>
        @empty
            <p class="profile-card">У вас пока нет заявок.</p>
        @endforelse
        @include('partials.pagination', ['paginator' => $applications])
    </section>
    <section id="schedule" class="date-list">
        <h2>Расписание тренировок</h2>
        @if ($gym)
            <form class="filter" method="get" action="{{ route('cabinet') }}#schedule">
                <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                <label>Филиал
                    <select name="gym">
                        @foreach ($gyms as $item)
                            <option value="{{ $item->id }}" @selected($item->id === $gym->id)>{{ $item->name }}</option>
                        @endforeach
                    </select>
                </label>
                <button class="action">Показать</button>
            </form>
            <div class="calendar">
                <div class="calendar-header">
                    <a class="nav-arrow" aria-label="Предыдущий месяц" href="{{ route('cabinet', ['gym' => $gym->id, 'month' => $month->subMonth()->format('Y-m')]) }}#schedule">‹</a>
                    <strong class="month-year">{{ $month->locale('ru')->isoFormat('MMMM YYYY') }}</strong>
                    <a class="nav-arrow" aria-label="Следующий месяц" href="{{ route('cabinet', ['gym' => $gym->id, 'month' => $month->addMonth()->format('Y-m')]) }}#schedule">›</a>
                </div>
                <table class="calendar-table" aria-label="Календарь тренировок">
                    <thead><tr>@foreach (['Пн','Вт','Ср','Чт','Пт','Сб','Вс'] as $weekday)<th scope="col">{{ $weekday }}</th>@endforeach</tr></thead>
                    <tbody>
                        @for ($week = 0; $week < $weeks; $week++)
                            <tr>
                                @for ($col = 0; $col < 7; $col++)
                                    @php($day = $week * 7 + $col - $leading + 1)
                                    <td>
                                        @if ($byDay->has($day))
                                            <a href="#day-{{ $day }}" aria-label="{{ $day }} число, тренировки">{{ $day }}</a>
                                        @elseif ($day >= 1 && $day <= $month->daysInMonth)
                                            {{ $day }}
                                        @endif
                                    </td>
                                @endfor
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
            @forelse ($byDay as $day => $items)
                <section id="day-{{ $day }}" class="date-list">
                    <h3>{{ $month->day($day)->format('d.m.Y') }}</h3>
                    @foreach ($items as $slot)
                        <article class="panel">
                            <h4>{{ $slot->title }}</h4>
                            <p>{{ $slot->starts_at->setTimezone($tz)->format('H:i') }} — {{ $slot->ends_at->setTimezone($tz)->format('H:i') }} · {{ $slot->trainer->user->name }}</p>
                            <p>Свободных мест: {{ max(0, $slot->capacity - $slot->occupied) }}</p>
                            <form method="post" action="{{ route('bookings.book', $slot) }}">
                                @csrf
                                <button class="action" @disabled($slot->occupied >= $slot->capacity)>Записаться</button>
                            </form>
                        </article>
                    @endforeach
                </section>
            @empty
                <p>В этом месяце нет предстоящих тренировок в выбранном зале.</p>
            @endforelse
        @else
            <p>Нет доступных филиалов.</p>
        @endif
    </section>
    <section id="bookings" class="date-list">
        <h2>Мои тренировки</h2>
        @forelse ($bookings as $booking)
            <article class="panel">
                <h3>{{ $booking->slot->title }}</h3>
                <p>{{ $booking->slot->starts_at->setTimezone($tz)->format('d.m.Y H:i') }} · {{ $booking->slot->gym->name }} · {{ $statuses[$booking->status] }}</p>
                @if ($booking->status === 'booked' && $booking->slot->starts_at->gt(now()))
                    <form method="post" action="{{ route('bookings.cancel', $booking) }}">@csrf <button class="action">Отменить запись</button></form>
                @endif
            </article>
        @empty
            <p>У вас пока нет записей.</p>
        @endforelse
        @include('partials.pagination', ['paginator' => $bookings])
    </section>
    <section id="history" class="date-list">
        <h2>История посещений</h2>
        @forelse ($history as $booking)
            <article class="panel">
                <h3>{{ $booking->slot->title }}</h3>
                <p>{{ $booking->slot->starts_at->setTimezone($tz)->format('d.m.Y H:i') }} · {{ $booking->slot->gym->name }}</p>
            </article>
        @empty
            <p>Посещённых тренировок пока нет.</p>
        @endforelse
        @include('partials.pagination', ['paginator' => $history])
    </section>
</div>
@endsection
