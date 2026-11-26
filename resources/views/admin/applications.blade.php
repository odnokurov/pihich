@extends('layout')
@section('title', 'Заявки')
@section('content')
    <div class="workspace">
        <h1>Заявки на абонементы</h1>
        @include('admin.nav')
        @forelse ($applications as $application)
            <article class="panel">
                <h2>{{ $application->user->name }} · {{ $application->gym->name }}</h2>
                <p>{{ $application->plan->name }} · {{ $application->visits_limit }} тренировок · {{ number_format($application->price_kopecks / 100, 2, ',', ' ') }} ₽</p>
                <p>Срок: {{ $application->duration_months ? $application->duration_months.' мес.' : '1 день' }}</p>
                <div class="card-grid">
                    <form method="post" action="{{ route('admin.decide', $application) }}" class="form-stack">
                        @csrf
                        <input type="hidden" name="status" value="approved">
                        <label>Дата начала (по умолчанию сегодня)
                            <input type="date" name="start_on" min="{{ now(\App\Services\Memberships::TIMEZONE)->toDateString() }}">
                        </label>
                        <button class="action">Одобрить</button>
                    </form>
                    <form method="post" action="{{ route('admin.decide', $application) }}" class="form-stack">
                        @csrf
                        <input type="hidden" name="status" value="rejected">
                        <label>Причина отказа <input name="rejection_reason" maxlength="500" required></label>
                        <button class="action">Отклонить</button>
                    </form>
                </div>
            </article>
        @empty
            <p>Новых заявок нет.</p>
        @endforelse
        @include('partials.pagination', ['paginator' => $applications])
    </div>
@endsection
