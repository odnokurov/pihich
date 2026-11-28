@extends('layout')
@section('title', 'Абонементы')
@section('style', 'subscription')
@push('scripts')
    <script src="{{ asset('js/plans.js') }}" defer></script>
@endpush
@section('content')
    <section class="custom-subscription-section workspace">
        <h1 class="custom-title">Выберите абонемент</h1>
        @if (!$selectedGym || !$selectedPlan)
            <p>Сейчас нет доступных филиалов или тарифов.</p>
        @else
            @php
                $canApply = auth()->guest() || auth()->user()->role === 'client';
                $quote = app(\App\Services\Memberships::class)->quote($selectedPlan, ['duration_months' => $months, 'visits_per_month' => $visits]);
            @endphp
            <form class="custom-card form-stack" id="plan-form" method="post" action="{{ route(auth()->guest() ? 'plans.continue' : 'memberships.submit') }}">
                @csrf
                <label>Филиал
                    <select name="gym_id" required>
                        @foreach ($gyms as $gym)
                            <option value="{{ $gym->id }}" @selected($gym->id === $selectedGym->id)>{{ $gym->address }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Тариф
                    <select name="plan_id" required>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" data-kind="{{ $plan->kind }}" data-price="{{ $plan->price_kopecks }}"
                                data-months="{{ $plan->duration_months }}" data-visits="{{ $plan->visits_limit }}"
                                @selected($plan->id === $selectedPlan->id)>{{ $plan->name }}</option>
                        @endforeach
                    </select>
                </label>
                <fieldset class="form-stack" id="plan-options" @if($selectedPlan->kind !== 'custom') hidden @endif>
                    <legend>Настройте абонемент</legend>
                    <label>Тренировок в месяц
                        <input type="range" class="custom-slider" id="visits-per-month" name="visits_per_month" min="1" max="24" value="{{ $visits }}" required>
                        <output for="visits-per-month">{{ $visits }}</output>
                    </label>
                    <label>Месяцев
                        <input type="range" class="custom-slider" id="duration-months" name="duration_months" min="1" max="12" value="{{ $months }}" required>
                        <output for="duration-months">{{ $months }}</output>
                    </label>
                </fieldset>
                <p id="plan-summary" aria-live="polite">Срок {{ $quote['duration_months'] ? $quote['duration_months'].' мес.' : '1 день' }} · Тренировок {{ $quote['visits_limit'] }}</p>
                <p class="total-price" id="plan-price" aria-live="polite">{{ number_format($quote['price_kopecks'] / 100, $quote['price_kopecks'] % 100 ? 2 : 0, ',', ' ') }} ₽</p>
                @if ($canApply)
                    <button class="action">{{ auth()->guest() ? 'Войти для оформления' : 'Подать заявку' }}</button>
                @endif
            </form>
            <h2 class="section-title">Готовые абонементы</h2>
            @include('partials.plan-cards')
        @endif
    </section>
@endsection
