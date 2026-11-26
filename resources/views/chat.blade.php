@extends('layout')
@section('title', 'Переписка')
@section('style', 'chat')
@section('content')
    <div class="workspace">
        @isset ($thread)
            <p><a href="{{ route('chat.index') }}">Все диалоги</a></p>
            <h1>{{ auth()->user()->role === 'client' ? $thread->trainer->user->name : $thread->user->name }}</h1>
            @include('partials.pagination', ['paginator' => $messages])
            @forelse ($messages->getCollection()->reverse() as $message)
                <article class="panel">
                    <strong>{{ $message->sender->name }}</strong>
                    <time>{{ $message->created_at->setTimezone(\App\Services\Memberships::TIMEZONE)->format('d.m.Y H:i') }}</time>
                    <p class="message">{{ $message->body }}</p>
                </article>
            @empty
                <p>Сообщений пока нет.</p>
            @endforelse
            <form method="post" action="{{ route('chat.send', $thread) }}" class="panel form-stack">
                @csrf
                <label>Сообщение
                    <textarea name="body" maxlength="2000" rows="3" required>{{ old('body') }}</textarea>
                </label>
                <button class="action">Отправить</button>
            </form>
        @else
            <h1>Переписка</h1>
            @forelse ($threads as $item)
                <p class="panel"><a href="{{ route('chat.show', $item) }}">{{ auth()->user()->role === 'client' ? $item->trainer->user->name : $item->user->name }}</a></p>
            @empty
                <p>У вас пока нет диалогов.</p>
                @if (auth()->user()->role === 'client')
                    <a href="{{ route('trainers') }}">Выбрать тренера</a>
                @endif
            @endforelse
            @include('partials.pagination', ['paginator' => $threads])
        @endisset
    </div>
@endsection
