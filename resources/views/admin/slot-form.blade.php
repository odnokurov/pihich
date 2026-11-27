<details class="panel">
    <summary>{{ $slot->exists ? 'Редактировать тренировку' : 'Добавить тренировку' }}</summary>
    <form method="post" action="{{ route('admin.slot', $slot->id) }}" class="form-stack">
        @csrf
        <label>Название <input name="title" value="{{ $slot->title }}" maxlength="120" required></label>
        <label>Филиал
            <select name="gym_id" required>
                @foreach ($gyms as $gym)
                    <option value="{{ $gym->id }}" @selected($slot->gym_id === $gym->id)>{{ $gym->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Тренер
            <select name="trainer_id" required>
                @foreach ($trainers as $trainer)
                    <option value="{{ $trainer->id }}" @selected($slot->trainer_id === $trainer->id)>{{ $trainer->user->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Начало по Томску <input type="datetime-local" name="starts_at" value="{{ $slot->starts_at?->setTimezone(\App\Services\Memberships::TIMEZONE)->format('Y-m-d\TH:i') }}" required></label>
        <label>Окончание по Томску <input type="datetime-local" name="ends_at" value="{{ $slot->ends_at?->setTimezone(\App\Services\Memberships::TIMEZONE)->format('Y-m-d\TH:i') }}" required></label>
        <label>Вместимость <input type="number" name="capacity" value="{{ $slot->capacity ?? 1 }}" min="1" max="65535" required></label>
        @include('admin.visible', ['visible' => $slot->is_active ?? true])
        <button class="action">Сохранить тренировку</button>
    </form>
</details>
