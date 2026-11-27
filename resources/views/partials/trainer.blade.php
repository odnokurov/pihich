<article class="coach-card">
    <div class="coach-image-wrapper">
        <img src="{{ asset($trainer->photo_path ?: 'images/anon.png') }}" alt="" class="coach-image">
    </div>
    <h3 class="coach-name">{{ $trainer->user->name }}</h3>
    <p>{{ $trainer->specialty }}</p>
    <p class="coach-location">{{ $trainer->activeGyms->pluck('name')->implode(' · ') }}</p>
    @if ($trainer->bio)
        <p>{{ $trainer->bio }}</p>
    @endif
    @if (auth()->user()?->role === 'client')
        <form method="post" action="{{ route('chat.open', $trainer) }}">@csrf <button class="action">Написать</button></form>
    @endif
</article>
