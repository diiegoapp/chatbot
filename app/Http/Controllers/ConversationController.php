<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        $conversations = $request->user()
            ->conversations()
            ->get();

        return view('chat.index', compact('conversations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $existing = $request->user()
            ->conversations()
            ->whereNull('title')
            ->doesntHave('messages')
            ->latest()
            ->first();

        if ($existing) {
            return redirect()->route('conversations.show', $existing);
        }

        $conversation = $request->user()->conversations()->create(['title' => null]);

        return redirect()->route('conversations.show', $conversation);
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $this->authorize('view', $conversation);

        $conversations = $request->user()->conversations()->get();
        $messages = $conversation->messages()->orderBy('id')->get();

        return view('chat.show', compact('conversation', 'conversations', 'messages'));
    }

    public function update(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $title = trim($request->input('title', ''));
        if ($title === '') {
            return response()->json(['error' => 'Título não pode ser vazio.'], 422);
        }

        $conversation->update(['title' => mb_substr($title, 0, 120)]);

        return response()->json(['title' => $conversation->title]);
    }

    public function destroy(Conversation $conversation): RedirectResponse
    {
        $this->authorize('delete', $conversation);

        $conversation->delete();

        return redirect()->route('conversations.index');
    }
}
