<div class="subscription-cards">
    @forelse ($plans->where('kind', 'fixed') as $plan)
        <article class="subscription-card">
            <h3 class="subscription-name">{{ $plan->name }}</h3>
            <p class="subscription-desc">Лимит тренировок {{ $plan->visits_limit }} · {{ $plan->duration_months ? $plan->duration_months.' мес.' : '1 день' }}</p>
            <p class="subscription-price">{{ number_format($plan->price_kopecks / 100, $plan->price_kopecks % 100 ? 2 : 0, ',', ' ') }} ₽</p>
            <a class="subscribe-btn plan-choice" href="{{ route('plans', ['plan_id' => $plan->id, 'gym_id' => $selectedGym?->id]) }}" data-plan="{{ $plan->id }}">Выбрать</a>
        </article>
    @empty
        <p>Нет доступных пакетов.</p>
    @endforelse
</div>
