<?php

namespace App\Http\Controllers;

use App\Models\ChatWidget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ChatWidgetsController extends Controller
{
    public function index(): InertiaResponse
    {
        $widgets = Auth::user()->account->chatWidgets()
            ->withCount(['conversations'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($widget) => [
                'id' => $widget->id,
                'name' => $widget->name,
                'widget_key' => $widget->widget_key,
                'is_active' => $widget->is_active,
                'conversations_count' => $widget->conversations_count,
                'embed_url' => $widget->getEmbedUrl(),
                'created_at' => $widget->created_at->format('Y-m-d H:i'),
            ]);

        return Inertia::render('ChatWidgets/Index', [
            'widgets' => $widgets,
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('ChatWidgets/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'welcome_message' => ['nullable', 'string', 'max:500'],
            'system_prompt' => ['nullable', 'string', 'max:2000'],
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'position' => ['nullable', 'string', 'in:bottom-right,bottom-left'],
            'is_active' => ['boolean'],
            'auto_create_leads' => ['boolean'],
            'collect_email' => ['boolean'],
            'collect_phone' => ['boolean'],
            'allowed_domains' => ['nullable', 'array'],
            'allowed_domains.*' => ['string', 'max:255'],
            'ai_model' => ['nullable', 'string', 'in:gpt-4o,gpt-4o-mini,gpt-4-turbo,gpt-3.5-turbo'],
            'temperature' => ['nullable', 'numeric', 'min:0', 'max:2'],
            'max_tokens' => ['nullable', 'integer', 'min:1', 'max:4000'],
            'rate_limit_per_hour' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $widget = ChatWidget::create([
            'account_id' => Auth::user()->account_id,
            ...$validated,
        ]);

        return Redirect::route('chat-widgets.index')
            ->with('success', 'Chat widget created successfully.');
    }

    public function edit(ChatWidget $chatWidget): InertiaResponse
    {
        // Ensure widget belongs to user's account
        if ($chatWidget->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        return Inertia::render('ChatWidgets/Edit', [
            'widget' => [
                'id' => $chatWidget->id,
                'name' => $chatWidget->name,
                'widget_key' => $chatWidget->widget_key,
                'welcome_message' => $chatWidget->welcome_message,
                'system_prompt' => $chatWidget->system_prompt,
                'primary_color' => $chatWidget->primary_color,
                'position' => $chatWidget->position,
                'is_active' => $chatWidget->is_active,
                'auto_create_leads' => $chatWidget->auto_create_leads,
                'collect_email' => $chatWidget->collect_email,
                'collect_phone' => $chatWidget->collect_phone,
                'allowed_domains' => $chatWidget->allowed_domains ?? [],
                'banners' => $chatWidget->banners ?? [],
                'show_banners' => $chatWidget->show_banners ?? true,
                'banner_rotation_seconds' => $chatWidget->banner_rotation_seconds ?? 5,
                'ai_model' => $chatWidget->ai_model,
                'temperature' => $chatWidget->temperature,
                'max_tokens' => $chatWidget->max_tokens,
                'rate_limit_per_hour' => $chatWidget->rate_limit_per_hour,
                'embed_url' => $chatWidget->getEmbedUrl(),
            ],
        ]);
    }

    public function update(Request $request, ChatWidget $chatWidget): RedirectResponse
    {
        // Ensure widget belongs to user's account
        if ($chatWidget->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'welcome_message' => ['nullable', 'string', 'max:500'],
            'system_prompt' => ['nullable', 'string', 'max:2000'],
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'position' => ['nullable', 'string', 'in:bottom-right,bottom-left'],
            'is_active' => ['boolean'],
            'auto_create_leads' => ['boolean'],
            'collect_email' => ['boolean'],
            'collect_phone' => ['boolean'],
            'allowed_domains' => ['nullable', 'array'],
            'allowed_domains.*' => ['string', 'max:255'],
            'banners' => ['nullable', 'array'],
            'banners.*.title' => ['nullable', 'string', 'max:100'],
            'banners.*.text' => ['nullable', 'string', 'max:200'],
            'banners.*.image' => ['nullable', 'url', 'max:500'],
            'banners.*.link' => ['nullable', 'url', 'max:500'],
            'banners.*.link_target' => ['nullable', 'string', 'in:_blank,_self'],
            'banners.*.button_text' => ['nullable', 'string', 'max:50'],
            'banners.*.button_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'banners.*.bg_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'banners.*.bg_color_2' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'banners.*.text_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'show_banners' => ['boolean'],
            'banner_rotation_seconds' => ['nullable', 'integer', 'min:0', 'max:60'],
            'ai_model' => ['nullable', 'string', 'in:gpt-4o,gpt-4o-mini,gpt-4-turbo,gpt-3.5-turbo'],
            'temperature' => ['nullable', 'numeric', 'min:0', 'max:2'],
            'max_tokens' => ['nullable', 'integer', 'min:1', 'max:4000'],
            'rate_limit_per_hour' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $chatWidget->update($validated);

        return Redirect::route('chat-widgets.index')
            ->with('success', 'Chat widget updated successfully.');
    }

    public function destroy(ChatWidget $chatWidget): RedirectResponse
    {
        // Ensure widget belongs to user's account
        if ($chatWidget->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        $chatWidget->delete();

        return Redirect::route('chat-widgets.index')
            ->with('success', 'Chat widget deleted successfully.');
    }

    public function preview(ChatWidget $chatWidget)
    {
        // Ensure widget belongs to user's account
        if ($chatWidget->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        // Return standalone preview page (not Inertia) so widget works properly
        // Use preview script URL that bypasses is_active check
        $previewScriptUrl = route('chat.widget.preview', $chatWidget->widget_key);
        
        return view('chat-widget-preview', [
            'widget' => $chatWidget,
            'embedUrl' => $previewScriptUrl,
        ]);
    }
}
