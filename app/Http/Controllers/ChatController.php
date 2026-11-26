<?php

namespace App\Http\Controllers;

use App\Models\ChatThread;
use App\Models\Trainer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $threads = ChatThread::forParticipant($request->user())->with(['user', 'trainer.user'])
            ->latest('updated_at')->paginate(20);

        return view('chat', compact('threads'));
    }

    public function open(Request $request, Trainer $trainer)
    {
        $thread = DB::transaction(function () use ($request, $trainer) {
            $trainer = Trainer::query()->lockForUpdate()->findOrFail($trainer->id);
            $pair = ['trainer_id' => $trainer->id, 'user_id' => $request->user()->id];
            $existing = ChatThread::where($pair)->first();
            if ($existing) {
                return $existing;
            }

            if (! $trainer->is_active) {
                throw ValidationException::withMessages(['trainer' => 'Тренер недоступен для нового диалога.']);
            }

            return ChatThread::create($pair);
        }, 3);

        return redirect()->route('chat.show', $thread);
    }

    public function show(Request $request, ChatThread $thread)
    {
        $this->authorizeParticipant($request, $thread);
        $thread->load(['user', 'trainer.user']);
        $messages = $thread->messages()->with('sender')->latest('id')->paginate(30);

        return view('chat', compact('thread', 'messages'));
    }

    public function send(Request $request, ChatThread $thread)
    {
        $this->authorizeParticipant($request, $thread);
        if (is_string($request->body)) {
            $request->merge(['body' => trim($request->body)]);
        }
        $data = $request->validate(['body' => 'required|string|max:2000']);

        DB::transaction(function () use ($request, $thread, $data) {
            $thread->touch();
            $thread->messages()->create(['sender_id' => $request->user()->id, 'body' => $data['body']]);
        }, 3);

        return redirect()->route('chat.show', $thread);
    }

    private function authorizeParticipant(Request $request, ChatThread $thread): void
    {
        abort_unless(ChatThread::forParticipant($request->user())->whereKey($thread->id)->exists(), 403);
    }
}
