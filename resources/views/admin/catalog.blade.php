@extends('layout')
@section('title', 'Справочники')
@section('content')
    <div class="workspace">
        <h1>Справочники</h1>
        @include('admin.nav')
        <h2>Филиалы</h2>
        @foreach ($gyms->concat([new \App\Models\Gym]) as $gym)
            <details class="panel">
                <summary>{{ $gym->name ?? 'Добавить филиал' }}</summary>
                <form method="post" action="{{ route('admin.gym', $gym->id) }}" class="form-stack">
                    @csrf
                    <label>Название <input name="name" value="{{ $gym->name }}" required maxlength="120"></label>
                    <label>Адрес <input name="address" value="{{ $gym->address }}" required maxlength="255"></label>
                    <label>Описание <textarea name="description" maxlength="10000">{{ $gym->description }}</textarea></label>
                    <label>Путь к фотографии в public <input name="image_path" value="{{ $gym->image_path }}" maxlength="255" placeholder="images/gyms/gallery-1.png"></label>
                    @include('admin.visible', ['visible' => $gym->is_active ?? true])
                    <button class="action">Сохранить филиал</button>
                </form>
            </details>
        @endforeach
        <h2>Тренеры</h2>
        @foreach ($trainers->concat([new \App\Models\Trainer]) as $trainer)
            <details class="panel">
                <summary>{{ $trainer->user?->name ?? 'Добавить карточку тренера' }}</summary>
                @if (!$trainer->exists && $accounts->isEmpty())
                    <p>Сначала создайте учётную запись тренера командой <code>php artisan flexclub:user trainer</code>.</p>
                @else
                    <form method="post" action="{{ route('admin.trainer', $trainer->id) }}" class="form-stack">
                        @csrf
                        @if (!$trainer->exists)
                            <label>Учётная запись
                                <select name="user_id" required>
                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->name }} · {{ $account->email }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif
                        <label>Специализация <input name="specialty" value="{{ $trainer->specialty }}" required maxlength="120"></label>
                        <label>О тренере <textarea name="bio" maxlength="10000">{{ $trainer->bio }}</textarea></label>
                        <label>Путь к фотографии в public <input name="photo_path" value="{{ $trainer->photo_path }}" maxlength="255" placeholder="images/coaches/coach-1.png"></label>
                        <fieldset class="form-stack"><legend>Филиалы</legend>
                            @foreach ($gyms as $gym)
                                <label class="check"><input type="checkbox" name="gyms[]" value="{{ $gym->id }}" @checked($trainer->gyms->firstWhere('id', $gym->id)?->pivot->is_active)>
                                    {{ $gym->name }} @unless($gym->is_active) (скрыт) @endunless
                                </label>
                            @endforeach
                        </fieldset>
                        @include('admin.visible', ['visible' => $trainer->is_active ?? true])
                        <button class="action">Сохранить тренера</button>
                    </form>
                @endif
            </details>
        @endforeach
        <h2>Тарифы</h2>
        @foreach ($plans->concat([new \App\Models\Plan(['kind' => 'fixed']), new \App\Models\Plan(['kind' => 'custom'])]) as $plan)
            <details class="panel">
                <summary>{{ $plan->name ?? ($plan->kind === 'custom' ? 'Добавить конструктор' : 'Добавить готовый пакет') }}</summary>
                <form method="post" action="{{ route('admin.plan', $plan->id) }}" class="form-stack">
                    @csrf
                    <input type="hidden" name="kind" value="{{ $plan->kind }}">
                    <label>Название <input name="name" value="{{ $plan->name }}" required maxlength="120"></label>
                    @if ($plan->kind === 'fixed')
                        <label>Срок в месяцах (0 для одного дня) <input type="number" name="duration_months" min="0" max="12" value="{{ $plan->duration_months }}" required></label>
                        <label>Лимит тренировок <input type="number" name="visits_limit" min="1" max="65535" value="{{ $plan->visits_limit }}" required></label>
                    @endif
                    <label>{{ $plan->kind === 'custom' ? 'Ставка за тренировку, коп.' : 'Цена пакета, коп.' }}
                        <input type="number" name="price_kopecks" min="1" max="{{ $plan->kind === 'custom' ? intdiv(4294967295, 288) : 4294967295 }}" value="{{ $plan->price_kopecks }}" required>
                    </label>
                    @include('admin.visible', ['visible' => $plan->is_active ?? true])
                    <button class="action">Сохранить тариф</button>
                </form>
            </details>
        @endforeach
    </div>
@endsection
