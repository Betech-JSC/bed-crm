<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatWidget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ChatConversationsController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $query = Auth::user()->account->chatConversations()
            ->with(['widget', 'lead', 'contact'])
            ->orderBy('last_message_at', 'desc');

        // Filters
        if ($request->has('widget_id') && $request->widget_id) {
            $query->where('widget_id', $request->widget_id);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('visitor_name', 'like', "%{$search}%")
                    ->orWhere('visitor_email', 'like', "%{$search}%")
                    ->orWhere('visitor_phone', 'like', "%{$search}%");
            });
        }

        return Inertia::render('ChatConversations/Index', [
            'filters' => $request->only('widget_id', 'status', 'search'),
            'conversations' => $query->paginate(20)->withQueryString()->through(fn($conv) => [
                'id' => $conv->id,
                'visitor_name' => $conv->visitor_name,
                'visitor_email' => $conv->visitor_email,
                'visitor_phone' => $conv->visitor_phone,
                'widget' => $conv->widget ? [
                    'id' => $conv->widget->id,
                    'name' => $conv->widget->name,
                ] : null,
                'lead' => $conv->lead ? [
                    'id' => $conv->lead->id,
                    'name' => $conv->lead->name,
                ] : null,
                'contact' => $conv->contact ? [
                    'id' => $conv->contact->id,
                    'name' => $conv->contact->name,
                ] : null,
                'status' => $conv->status,
                'message_count' => $conv->message_count,
                'last_message_at' => $conv->last_message_at?->format('Y-m-d H:i'),
                'created_at' => $conv->created_at->format('Y-m-d H:i'),
            ]),
            'widgets' => Auth::user()->account->chatWidgets()
                ->where('is_active', true)
                ->get()
                ->map(fn($w) => ['id' => $w->id, 'name' => $w->name]),
        ]);
    }

    public function show(ChatConversation $chatConversation): InertiaResponse
    {
        // Ensure conversation belongs to user's account
        if ($chatConversation->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        $chatConversation->load(['widget', 'lead', 'contact', 'messages']);

        return Inertia::render('ChatConversations/Show', [
            'conversation' => [
                'id' => $chatConversation->id,
                'visitor_name' => $chatConversation->visitor_name,
                'visitor_email' => $chatConversation->visitor_email,
                'visitor_phone' => $chatConversation->visitor_phone,
                'visitor_ip' => $chatConversation->visitor_ip,
                'page_url' => $chatConversation->page_url,
                'referrer_url' => $chatConversation->referrer_url,
                'status' => $chatConversation->status,
                'message_count' => $chatConversation->message_count,
                'widget' => $chatConversation->widget ? [
                    'id' => $chatConversation->widget->id,
                    'name' => $chatConversation->widget->name,
                ] : null,
                'lead' => $chatConversation->lead ? [
                    'id' => $chatConversation->lead->id,
                    'name' => $chatConversation->lead->name,
                    'url' => route('leads.edit', $chatConversation->lead->id),
                ] : null,
                'contact' => $chatConversation->contact ? [
                    'id' => $chatConversation->contact->id,
                    'name' => $chatConversation->contact->name,
                ] : null,
                'messages' => $chatConversation->messages->map(fn($msg) => [
                    'id' => $msg->id,
                    'role' => $msg->role,
                    'content' => $msg->content,
                    'created_at' => $msg->created_at->format('Y-m-d H:i:s'),
                    'tokens_used' => $msg->tokens_used,
                    'cost' => $msg->cost,
                ]),
                'created_at' => $chatConversation->created_at->format('Y-m-d H:i'),
                'last_message_at' => $chatConversation->last_message_at?->format('Y-m-d H:i'),
            ],
        ]);
    }
}
